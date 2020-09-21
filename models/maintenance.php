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
 * Maintenance Class Model
 * 
 * @package UData\Models\Database\Postgres
 * @link https://kc9eye.github.io/udata/UData_Database_Structure.html
 * @author Paul W. Lane
 * @license GPLv2
 */
class Maintenance {
    
    private $dbh;

    /**
     * Class constructor
     * 
     * A valid PDO object is required
     * @param PDO The PDO object to use for database access.
     */
    public function __construct (PDO $dbh) {
        $this->dbh = $dbh;
    }

    /**
     * Logs a maintenance inspection
     * 
     * Inserts new inspection data given in the `$data` parameter. This is not a checklist
     * and does not track faults in the inspection. It merely records that an inspection
     * was done by a person using an inspection form of the given version.
     * @param Array $data The data from the inspection in an array of the form `['form_name'=>string,'form_version'=>string,'uid'=>string
     * ,'comments'=>string,'form_date'=>string]`
     * @return Boolean True on success, false otherwise.
     */
    public function logInspection ($data) {
        try {
            $sql = 'INSERT INTO inspections VALUES (:id,:name,:version,now(),:uid,:comments,:form_date)';
            $pntr = $this->dbh->prepare($sql);
             $insert = [
                ':id'=>uniqid(),
                ':name'=>$data['form_name'],
                ':version'=>$data['form_version'],
                ':uid'=>$data['uid'],
                ':comments'=> $data['comments'],
                ':form_date'=>$data['form_date'],
            ];
            $pntr->execute($insert);
            return true;
        }
        catch (PDOException $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        } 
        catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
    }

    /**
     * Returns the inspection records given the inspection form name
     * 
     * @param String $form_name The name of the inspection form to retreive records for
     * @return Mixed An array of records on success, false on error or otherwise.
     */
    public function getInspections ($form_name) {
        try {
            $sql = 'SELECT 
            inspections.inspection_date,
            user_accts.firstname || \' \' ||user_accts.lastname as inspector,
            inspections.form_name,
            inspections.form_version,
            inspections.form_date,
            inspections.comments
        FROM inspections
        INNER JOIN user_accts ON user_accts.id = inspections.inspector_uid
        WHERE inspections.form_name = ?
        ORDER BY inspections.inspection_date ASC';
            $pntr = $this->dbh->prepare($sql);
            $pntr->execute([$form_name]);
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (PDOException $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
    }

    /**
     * Retrieves all records for equipment that have inspections available
     * @return Array An array of records, or false on failure
     */
    public function getEquipement () {
        $sql = 'SELECT * FROM equipment';
        try {
            if (!($pntr = $this->dbh->query($sql))) throw new Exception("Select failed: {$sql}");
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
     * Adds new equipment for inspections
     * @param Array $data In the form `['uid'=>string,'description'=>string,'interval'=>string]`
     * @return Boolean True on success, or false on failure.
     */
    public function addNewEquipment (Array $data) {
        $sql = 'INSERT INTO equipment VALUES (:id,:description,:timeframe,now(),:uid)';
        $insert = [
            ':id'=>uniqid(),
            ':description'=>$data['description'],
            ':timeframe'=>$data['interval'],
            ':uid'=>$data['uid']
        ];
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute($insert)) throw new Exception("Insert failed: {$sql}");
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Adds a new inspection point to the given equipment inspection
     * @param Array $data `['uid'=>string,'inspection'=>string,'eqid'=>string]`
     * @return Boolean True on success, false on failure
     */
    public function addInspectionPoint (Array $data) {
        $sql = 'INSERT INTO insp_forms VALUES (:id,:eqid,:inspection,now(),:uid)';
        $insert = [
            ':id'=>uniqid(),
            ':eqid'=>$data['eqid'],
            ':inspection'=>$data['inspection'],
            ':uid'=>$data['uid']
        ];
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute($insert)) throw new Exception("Insert failed: {$sql}");
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Removes an inspection point from an inspection form
     * @param String $id The inspection point ID
     * @return Boolean True on success, other wise false.
     */
    public function removeInspectionPoint ($id) {
        $sql = 'DELETE FROM insp_forms WHERE id = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$id])) throw new Exception("Delete failed: {$sql}");
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Removes an equipment reocrd from the database
     * @param String $id The equipment ID to remove
     * @return Boolean True on success, false otherwise.
     */
    public function removeEquipment ($id) {
        $sql = 'DELETE FROM equipment WHERE id = ?';
        $sql1 = 'DELETE FROM insp_forms WHERE eqid = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            $pntr1 = $this->dbh->prepare($sql);
            if (!$pntr->execute([$id])) throw new Exception("Delete failed: {$sql}");
            if (!$pntr->execute([$id])) throw new Exception("Delete failed: {$sql}");
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Adds a new equipment inspection
     * @param String $uid The inspectors User ID
     * @param String $eqid The Equipment ID
     * @param String $comments Any truths the inspector would like to say why they lied about the validatity of their inspection.
     * @return Boolean True on success, false otherwise
     */
    public function addNewEquipmentInspection ($uid, $eqid, $comments) {
        $sql = 'INSERT INTO inspections VALUES (:id,:eqid,now(),:uid,:comments)';
        $insert = [
            ':id'=>uniqid(),
            ':eqid'=>$eqid,
            ':uid'=>$uid,
            ':comments'=>$comments
        ];
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute($insert)) throw new Exception("Insert failed: {$sql}");
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Retrieves a listing of existsing tool catergories
     * 
     * Returns an array of records for each catergory already existsing.
     * @return Array If an error is encountered then false is returned.
     */
    public function getExistingCategories () {
        $sql = 'SELECT DISTINCT category FROM tools';
        try {
            $pntr = $this->dbh->query($sql);
            $rtn = [];
            foreach($pntr->fetchAll(PDO::FETCH_ASSOC) as $row) {
                array_push($rtn,$row['category']);
            }
            return $rtn;
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
     * Stores a new record to the tools list
     * 
     * Imports a new record to the master tool list
     * given, the record data.
     * @param Array $data The data array in the form `['uid'=>string,'category'=>string|'newcat'=>string,'description'=>string]`
     * @return Boolean True on success, otherwise false
     */
    public function addNewTool (Array $data) {
        $sql = 'INSERT INTO tools VALUES (:id,:description,:category,:uid,now())';
        $category = empty($data['category']) ? $data['newcat'] : $data['category'];
        try {
            $pntr = $this->dbh->prepare($sql);
            $insert = [
                ':id'=>uniqid(),
                ':description'=>$data['description'],
                ':category'=>$category,
                ':uid'=>$data['uid']
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
     * Returns a category filtered tool listing
     * @return Array the array of data, false otherwise
     */
    public function getToolListing () {
        $sql = 'SELECT * FROM tools ORDER BY category ASC';
        try {
            $pntr = $this->dbh->query($sql);
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
     * Returns records from a search of tools
     * @param String The search string to search for
     * @return Array An array of records on success, false otherwise
     */
    public function searchTools ($search) {
        $formatter = new SearchStringFormater();
        $terms = $formatter->formatSearchString($search);
        $sql = 'SELECT * FROM tools WHERE search @@ to_tsquery(?)';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$terms])) throw new Exception("Select failed: {$sql}");
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
     * Returns an individual tools record given it's ID
     * @param String $id The tools ID
     * @return Array The record array on success, false otherwise
     */
    public function getToolFromID ($id) {
        $sql = 'SELECT * FROM tools WHERE id = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$id])) throw new Exception("Select failed: {$sql}");
            return $pntr->fetchAll(PDO::FETCH_ASSOC)[0];
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
     * Updates an existing tool record given the tool data
     * @param Array $data The data array in the form `['id'=>string,'category'=>string|'newcat'=>string,'description'=>string,'uid'=>string]`
     * @return Boolean True on succes, otherwise false
     */
    public function updateToolByID ($data) {
        $sql = 'UPDATE tools SET category = :cat, description = :desc, uid = :uid, _date = now() WHERE id = :id';
        $category = empty($data['category']) ? $data['newcat'] :$data['category'];
        try {
            $pntr = $this->dbh->prepare($sql);
            $insert = [
                ':cat'=>$category,
                ':desc'=>$data['description'],
                ':uid'=>$data['uid'],
                ':id'=>$data['id']
            ];
            if (!$pntr->execute($insert)) throw new Exception("Update failed: {$sql}");
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
     * Removes a tool record given the tool ID
     * @param String $id The tool id to remove
     * @return Boolean True on success, otherwise false
     */
    public function removeToolByID ($id) {
        $sql = 'DELETE FROM tools WHERE id = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$id])) throw new Exception("Delete failed: {$sql}");
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
}