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
 * SDSHandler Class Model
 * 
 * @package UData\Models\Database\Postgres
 * @link https://kc9eye.github.io/udata/UData_Database_Structure.html
 * @author Paul W. Lane
 * @license GPLv2
 */
class SDSHandler {

    protected $dbh;
    protected $storage;

    public function __construct (PDO $dbh, $data_root) {
        $this->dbh = $dbh;
        $this->storage = $data_root;
    }

    public function searchSDS ($search_term) {
        try{
            $search = new SearchStringFormater();
            $search_term = $search->formatSearchString($search_term);        
            $sql = 'SELECT sds.id,sds.name,sds.dist,sds.fid,file_index.file
                    FROM sds 
                    INNER JOIN file_index ON sds.fid = file_index.id 
                    WHERE to_tsquery(?) @@ sds.search';        
            $pntr = $this->dbh->prepare($sql);
            $pntr->execute([$search_term]);
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (PDOException $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    public function latestAdded () {
        try {
            $sql = 'SELECT sds.id,sds.name,sds.dist,sds.fid,file_index.file 
                    FROM sds
                    INNER JOIN file_index ON sds.fid = file_index.id 
                    ORDER BY sds._date DESC LIMIT 5';
            $pntr = $this->dbh->query($sql);
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (PDOException $e) {
            trigger_error($e->getMessage(),E_USER_WARING);
            return false;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    public function getHazMatList () {
        $sql = 'SELECT name, used FROM sds ORDER BY name ASC';
        try {
            $pntr = $this->dbh->query($sql);
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (PDOException $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
        }
    }

    public function getHazMatListCount () {
        try {
            $pntr = $this->dbh->query('SELECT count(*) FROM sds');
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (PDOException $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
    }

    public function addUploaded ($data) {
        try {
            $indexer = new FileIndexer($this->dbh,$this->storage);
            $upload = new FileUpload(FileIndexer::UPLOAD_NAME);
            $res = $indexer->indexFiles($upload,$data['uid']);
            if ($res === false) {
                throw new Exception('File indexer failed');
            }
            $pntr = $this->dbh->prepare(
                'INSERT INTO sds (id,name,used,dist,_date,added_by,meta,fid)
                 VALUES (:id,:name,:used,:dist,now(),:uid,:meta,:fid)'
                 );
            $insert = [
                ':id'=>uniqid(),
                ':name'=>$data['name'],
                ':dist'=>$data['dist'],
                ':used'=>$data['used'],
                ':uid'=>$data['uid'],
                ':meta'=>$data['meta'],
                ':fid'=>$res[0]
            ];
            if (!$pntr->execute($insert)) {
                throw new Exception('Insert failed at SDS');
            }
            return true;
        }
        catch (PDOException $epdo) {
            trigger_error($epdo->getMessage(),E_USER_WARNING);
            return false;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_ERROR);
            return false;
        }
    }

    public function deleteSDS ($id) {
        try {
            $indexer = new FileIndexer($this->dbh, $this->storage);
            $del = explode(':',$id);
            $pntr = $this->dbh->prepare('DELETE FROM sds WHERE id = ?');
            if ( $indexer->removeIndexedFiles($indexer->getIndexByID($del[1])) ) {
                $pntr->execute([$del[0]]);
                return true;
            }
            else {
                throw new Exception('File indexer failed to remove file.');
            }
        }
        catch(PDOException $e) {
            $this->dbh->rollBack();
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
        catch (Exception $e) {
            $this->dbh->rollBack();
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }
}