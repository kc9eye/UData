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
 * WorkCells Class Model
 * 
 * @package UData\Models\Database\Postgres
 * @link https://kc9eye.github.io/udata/UData_Database_Structure.html
 * @author Paul W. Lane
 * @license GPLv2
 */
class WorkCells {

    protected $dbh;
    private $data;

    public function __construct (PDO $dbh) {
        $this->dbh = $dbh;
    }

    /**
     * Adds a new work cell with the given data
     * 
     * @param Array $data The cell data given in an indexed array,
     * generally the $_REQUEST array is given with the following indexes;
     * `['cell_name'=>string,'uid'=>string,'prokey'=>string]`.
     * @return Mixed Returns true on success, false otherwise.
     * if the given description is already in use, or false on error.
     */
    public function addNewCell (Array $data) {
        $sql = 'INSERT INTO work_cell (id,cell_name,_date,uid,prokey) VALUES (:id,:cell_name,now(),:uid,:prokey)';
        $insert = [
            ':id'=>uniqid(),
            ':cell_name'=>$data['cell_name'],
            ':uid'=>$data['uid'],
            ':prokey'=>$data['prokey']
        ];
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute($insert)) throw new Exception("Insert failed: {$sql}");
            $this->newCellID = $insert[':id'];
            return true;
        }
        catch (PDOException $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Verifies whether or not the given cell description is unique or not.
     * 
     * @param String $description The description to check for uniqueness.
     * @return Boolean True if the description is unique, false otherwise.
     */
    public function verifyUniqueCell ($description) {
        $sql = 'SELECT count(*) FROM work_cell WHERE cell_name = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$description])) throw new Exception("Select failed: {$sql}");
            if ($pntr->fetch(PDO::FETCH_ASSOC)['count'] > 0) return false;
            else return true;
        }
        catch (PDOException $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Returns an array of work cells for a given product
     * 
     * @param String $key The product key to retrieve cells for
     * @return Mixed An array containing the cell data, or false on error
     */
    public function getCellsFromKey ($key) {
        $sql = 
            "SELECT id,cell_name,_date,prokey,((quality/control)*100) as qc,
             (SELECT firstname||' '||lastname as author FROM user_accts WHERE id = a.uid)
             FROM work_cell as a
             WHERE prokey = ?
            ";
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$key])) throw new Exception("Select failed: {$sql}");
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (PDOException $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Searches the work_cell table given the search string and returns the data
     * @param String $search The unmodified search string
     * @return Mixed An array (possibly empty) of data on success false otherwise
     */
    public function searchCells ($search) {
        $sql = 'SELECT * FROM work_cell WHERE search @@ to_tsquery(?)';
        $string = new SearchStringFormater();
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$string->formatSearchString($search)])) throw new Exception("Select failed: {$sql}");
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (PDOException $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Returns an array of checkpoints for the given cell ID
     * @param String $cellid The ID to reteive checkpoints for
     * @return Array An array of checkpoints associated with the cell.
     */
    public function getCheckPointsByCellID ($cellid) {
        $sql = 'SELECT * FROM quality_control WHERE cellid = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$cellid])) throw new Exception("Select failed: {$sql}");
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (PDOException $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return array();
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return array();
        }
    }

    /**
     * Removes a work cell completely along with all it's associated items
     * @param String $cellid The ID of the cell to remove.
     * @return Boolean True on success, false otherwise.
     */
    public function removeWorkCell ($cellid) {
        $sql = "DELETE FROM work_cell WHERE id = ?";
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$cellid])) throw new Exception("Delete failed: {$sql}");
            return true;
        }
        catch (PDOException $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Updates a work cell with given data
     * @param Array $data The data given in array in the form `['cellid'=>string id,'cell_name'=>string name,'transfer_to_product'=>string prokey]`
     * @return Boolean True on success, otherwise false.
     * @todo Implement transfer function with materials cross ref to BOM
     */
    public function updateWorkCell (Array $data) {
        $sql = 'UPDATE work_cell SET cell_name = :name,uid = :uid WHERE id = :id';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([':name'=>$data['cell_name'],':uid'=>$data['uid'],':id'=>$data['cellid']]))
                throw new Exception("Update failed: {$sql}");
            return true;
        }
        catch (PDOException $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
}

    /**
     * Adds the given tooling data to the cell
     * 
     * @param Array $data The tool data in the form `['toolid'=>string,'qty'=>string,'cellid'=>string,'uid'=>string]
     * @return Boolean True on success, false otherwise.
     */
    public function addToolingToCell (Array $data) {
        $sql = 
            "INSERT INTO cell_tooling VALUES (:id,:cellid,:toolid,:qty,:uid,now(),:tv,:tu,:tl)";
        try {
            $pntr = $this->dbh->prepare($sql);
            $insert = [
                ':id'=>uniqid(),
                ':cellid'=>$data['cellid'],
                ':toolid'=>$data['toolid'],
                ':qty'=>$data['qty'],
                ':uid'=>$data['uid'],
                ':tv'=>$data['torque_val'],
                ':tu'=>$data['torque_units'],
                ':tl'=>$data['torque_label']
            ];
            if (!$pntr->execute($insert)) throw new Exception("Insert failed: {$sql}");
            return true;
        }
        catch (PDOException $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Removes a row containing the data for one workcell tool
     * 
     * @param String $id The row's cell_tooling ID
     * @return Boolean True on success, false otherwise
     */
    public function removeToolingFromCell ($id) {
        $sql = "DELETE FROM cell_tooling WHERE id = ?";
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$id])) throw new Exception("Delete failed: {$sql}");
            return true;
        }
        catch (PDOException $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Records a safety document seeking approval
     * 
     * Records a cell safety assessment seeking approval and
     * emails the users that can approve the document.
     * @param Array $data The data to record in the form `['uid'=>string,'name'=>string,'body'=>string,'url'=>string,'cellname'=>string]`
      * @param Mailer The server mailer for seeking approval emails.
     * @return Boolean True on success, false otherwise.
     */
    public function seekSafetyApproval (Array $data, Mailer $mailer) {
        $sql = 'INSERT INTO documents (id,name,state,body,oid) VALUES (:id,:name,:state,:body,:oid)';
        $insert = [
            ':id'=>uniqid(),
            ':name'=>$data['name'],
            ':state'=>DocumentViewer::SEEKING,
            ':body'=>$data['body'],
            ':oid'=>$data['uid']
        ];
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute($insert)) throw new Exception("Insert failed: {$sql}");
            return $this->emailReviewer($data, 'New Cell Safety', $mailer);

        }
        catch (PDOException $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Updates an existsing rejected seeking_approval document
     * @param Array $data An array of data for the document `['oid'=>'string','body'=>'string','docid'=>'string']`
     * @param Mailer $mailer The server mailer object
     */
    public function editRejected ($data, $mailer) {
        $sql = 'UPDATE documents SET _date = now(),oid = :oid,body = :body WHERE id = :id';
        $insert = [
            ':oid' => $data['uid'],
            ':body' => $data['body'],
            ':id' => $data['docid']
        ];
        try {
            $pntr = $this->dbh->prepare($sql);
            if (! $pntr->execute($insert)) throw new Exception("Update failed: {$sql}");
            return $this->emailReviewer($data, 'New Cell Safety', $mailer);
        }
        catch (PDOException $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Verifies an approval of a work cell safety assessment
     * @param Array $data The data submitted;
     * `['aid'=>string,'docname'=>string,'pending_id'=>string,'obsolete_id'=>string,'username'=>string,'password'=>string]`
     * @param Security $security The server security object
     * @return Boolean True on success, false otherwise.
     */
    public function approveSafety (Array $data, Security $security) {
        $approved = 'UPDATE documents SET state = :state, aid = :aid, a_date = now() WHERE id = :id';
        $obsolete = 'UPDATE documents SET state = :state WHERE id = :id';
        try {
            if ($security->verifyLogOn($data['username'],$data['password'])) {
                $pntr = $this->dbh->prepare($approved);
                $insert = [
                    ':state'=>DocumentViewer::APPROVED,
                    ':aid'=>$data['aid'],
                    ':id'=>$data['pending_id']
                ];
                if ($pntr->execute($insert)) {
                    $pntr = $this->dbh->prepare($obsolete);
                    $insert = [
                        ':state'=>DocumentViewer::OBSOLETE,
                        ':id'=>$data['obsolete_id']
                    ];
                    if (!$pntr->execute($insert)) throw new Exception("Update failed; {$obsolete}");
                    return true;
                }
                else {
                    throw new Exception("Update failed: {$approved}");
                }
            }
        }
        catch (PDOException $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    private function emailReviewer (Array $data, $notification, Mailer $mailer) {
        try {
            $notifier = new Notification($this->dbh, $mailer);
            $body = $mailer->wrapInTemplate(
                "docreview.html",
                "<a href='{$data['url']}&name={$data['name']}'>{$data['cellname']}</a>"
            );
            $notifier->notify($notification,'Pending Document Change',$body);
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->message,E_USER_WARNING);
            return false;
        }
    }

    /**
     * Adds a print to the cell
     * 
     * @param Array $data The given data required to add the cell
     * in the form ['cellid'=>String,'number'=>String,'uid'=>String]
     * @return Boolean True on success, false otherwise.
     */
    public function addNewCellPrint (Array $data) {
        $sql = 'INSERT INTO cell_prints VALUES (:id,:cellid,:number,:uid,now())';
        $insert = [
            ':id'=>uniqid(),
            ':cellid'=>$data['cellid'],
            ':number'=>$data['number'],
            ':uid'=>$data['uid']
        ];
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute($insert)) throw new Exception(print_r($pntr->errorInfo(),true));
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Removes a the given cell print from the cell_prints table
     * 
     * @param String $id The ID of the row to be removed
     * @return Boolean True on success, false otherwise
     */
    public function removeCellPrint ($id) {
        $sql = 'DELETE FROM cell_prints WHERE id = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$id])) throw new Exception(print_r($pntr->errorInfo(),true));
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Adds a file to the given cell for association
     * 
     * @param Array $data The data for the association in the form:
     * ['cellid'=>string,'uid'=>string,'storage'=>string]
     * @param FileUpload $file A FileUpload object containing the upload data
     * @return Boolean True on success, false otherwise.
     */
    public function addFileToCell (Array $data, FileUpload $file) {
        $sql = 'INSERT INTO cell_files VALUES(:id,:fid,:cellid,:uid,now())';
        $indexer = new FileIndexer($this->dbh,$data['storage']);
        if (($index = $indexer->indexFiles($file,$data['uid'])) !== false) {
            try {
                $insert = [
                    ':id'=>uniqid(),
                    ':fid'=>$index[0],
                    ':cellid'=>$data['cellid'],
                    ':uid'=>$data['uid']
                ];
                $pntr = $this->dbh->prepare($sql);
                if (!$pntr->execute($insert)) throw new Exception(print_r($pntr->errorInfo(),true));
                return true;
            }
            catch (Exception $e) {
                $indexer->removeFilesByFID($index[0]);
                trigger_error($e->getMessage(),E_USER_WARNING);
                return false;
            }
        }
    }

    /**
     * Removes a file from association with a cell, and deletes the file
     * 
     * @param String $id The id for the record in question from cell_files
     * @param String $storage the path to the local data storage
     * @return Boolean True on success false otheriwse
     */
    public function removeCellFile ($id,$storage) {
        $sql = 'SELECT * FROM cell_files WHERE id = ?';
        $indexer = new FileIndexer($this->dbh,$storage);
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$id])) throw new Exception(print_r($pntr->errorInfo(),true));
            $index = $pntr->fetchAll(PDO::FETCH_ASSOC);
            if (!$indexer->removeFilesByFID($index[0]['fid'])) throw new Exception("Indexer failed to remove file");
            else {
                $sql = 'DELETE FROM cell_files WHERE id = ?';
                $pntr = $this->dbh->prepare($sql);
                if (!$pntr->execute([$id])) throw new Exception(print_r($pntr->errorInfo(),true));
            }
            return true;            
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    public function __set ($name, $value) {
        $this->data[$name] = $value;
    }

    public function __get ($name) {
        if (!empty($this->data[$name])) return $this->data[$name];
        else return null;
    }

    public function __isset ($name) {
        return isset($this->data[$name]);
    }

    public function __unset ($name) {
        unset($this->data[$name]);
    }
}