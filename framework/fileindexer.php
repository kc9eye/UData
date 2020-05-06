<?php
/* This file is part of UData.
 * Copyright (C) 2018 Paul W. Lane <kc9eye@outlook.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */
/**
 * Indexes uploaded files in the application database
 * 
 * @uses FileUpload
 * @package UData\FrameWork\Database\Postgres
 * @author Paul W. Lane
 */
class FileIndexer {
    const UPLOAD_NAME = 'files';
    const VERSION_NUMBER = 'v1_0_';

    /**
     * @var PDO $dbh The application database handle
     */
    protected $dbh;

    /**
     * @var String $storage File path to where files will be stored on disk
     */
    protected $storage;

    /**
     * @var Array $indexed File paths that have been indexed already in the case 
     * of a multiple file upload.
     */
    public $indexed;

    /**
     * @var Array $indexed_ids File ID's that have been indexed in the case 
     * of multiple file uploads.
     */
    public $indexed_ids;

    /**
     * Class constructor
     * @param PDO $dbh The application database handle
     * @param String $storage The filepath to where files are being stored after being indexed
     * @return FileIndexer
     */
    public function __construct (PDO $dbh, $storage) {
        $this->dbh = $dbh;
        $this->storage = $storage;
        $this->indexed = [];
        $this->indexed_ids = [];
    }

    /**
     * Indexes files given in a FileUpload class
     * @param FileUpload $f The files to be indexed given in a FileUpload class
     * @param String $uid The user ID of the current user indexing the files
     * @return Array An unindexed array containing the FID's succesfully indexed files,
     * otherwise on error, it returns false.
     */
    public function indexFiles (FileUpload $f, $uid) {
        try {
            $sql = 'INSERT INTO file_index VALUES (:id,:file,:mime,:orig,now(),:uid)';
            $pntr = $this->dbh->prepare($sql);
            $this->dbh->beginTransaction();
            foreach($f->files as $file) {
                $mime = empty($file['type']) ? mime_content_type($file['tmp_name']) : $file['type'];
                $ext = pathinfo($file['name'],PATHINFO_EXTENSION);
                $insert = [
                    ':id'=>uniqid(),
                    ':file'=>uniqid(self::VERSION_NUMBER).'.'.$ext,
                    ':mime'=>$mime,
                    ':orig'=>$file['name'],
                    ':uid'=>$uid
                ];
                if (!$pntr->execute($insert)) throw new Exception("Insert failed: {$sql}");
                if (move_uploaded_file($file['tmp_name'], $this->storage.'/'.$insert[':file'])) {
                    array_push($this->indexed, $this->storage.'/'.$insert[':file']);
                    array_push($this->indexed_ids, $insert[':id']);
                }
                else {
                    throw new Exception('Failed to move uploaded file');
                }
            }
            $this->dbh->commit();
            return $this->indexed_ids;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            $this->dbh->rollBack();
            foreach($this->indexed as $file) {
                unlink($file);
            }
            return false;
        }
    }

    /**
     * A transaction rollback method
     * 
     * In case of a transaction interruption this method prevents
     * partial files from being indexed and stored. It attempts to remove any files
     * already indexed or stored on files.
     * @param Array $indexed Already indexed file for this transaction
     */
    public function removeIndexedFiles (Array $index) {
        try {
            $pntr = $this->dbh->prepare('DELETE FROM file_index WHERE id = ?');
            $this->dbh->beginTransaction();
            foreach($index as $row) {
                if (unlink($this->storage.'/'.$row['file'])) {
                    $pntr->execute([$row['id']]);
                }
                else {
                    throw new Exception('Unable to remove file '.$this->storage.'/'.$row['file']);
                }
            }
            $this->dbh->commit();
            return true;
        }
        catch (Exception $e) {
            $this->dbh->rollBack();
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
    }

    /**
     * Returns an array of a requested file index
     * 
     * Gets a database indexed array of a requested file by file index ID
     * @param String $id The files index ID
     * @return Mixed An array on success, otherwise `false`
     */
    public function getIndexByID ($id) {
        try {
            $sql = 'SELECT * FROM file_index WHERE id = ?';
            $pntr = $this->dbh->prepare($sql);
            $pntr->execute([$id]);
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (PDOException $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Gets a files index information by file name
     * 
     * Searches the database for file indexes by a given 
     * string filename
     * @param String $search The file name search pattern
     * @return Mixed Array on succes, `false` otheriwse.
     */
    public function getIndexByFilename ($search) {
        try {
            $name = str_replace(' ',' & ',$search);
            $sql = "SELECT * FROM file_index WHERE to_tsvector(file ||' '|| orig_name) @@ to_tsquery(?)";
            $pntr = $this->dbh->prepare($sql);
            $pntr->execute([$name]);
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (PDOException $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
    }

    /**
     * Removes an indexed file by it's ID
     * 
     * @param String $fid The files index ID to remove
     * @return Boolean True on success false otherwise
     */
    public function removeFilesByFID ($fid) {
        $sql = 'DELETE FROM file_index WHERE id = ?';
        try {
            if (($file = $this->getIndexByID($fid)) === false) throw new Exception("Unable to find indexed file ID:{$fid}");
            $pntr = $this->dbh->prepare($sql);
            if (unlink($this->storage.'/'.$file[0]['file'])) {
                if (!$pntr->execute([$fid])) throw new Exception(print_r($pntr->errorInfo(),true));
                return true;
            }
            else throw new Exception("Unable to remove physical file:{$this->storage}/{$file['file']}");
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

}