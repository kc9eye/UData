<?php
/* This file is part of UData.
 * Copyright (C) 2019 Paul W. Lane <kc9eye@outlook.com>
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
class Training {
    protected $dbh;

    public function __construct (PDO $dbh) {
        $this->dbh = $dbh;
    }

    /**
     * Adds new employee training to training list
     * @param Array $data The training to add in the form:
     * `['description'=>String,'reoccur_time_frame'=>Interval,'required'=>Boolean,'uid'=>String]`
     * @return Boolean True on success, false otherwise
     */
    public function addAvailableTraining (Array $data) {
        $sql = 'INSERT INTO training VALUES (:id,:description,:time_frame::interval,:required::boolean,now(),:uid)';
        try {
            $insert = [
                ':id'=>uniqid(),
                ':description'=>$data['description'],
                ':time_frame'=>$data['reoccur_time_frame'],
                ':required'=>$data['required'],
                ':uid'=>$data['uid']
            ];
        $pntr = $this->dbh->prepare($sql);
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
     * Returns all available training in array format
     * @return Array All records bu array format
     */
    public function getAllAvailableTraining () {
        $sql = 'SELECT * FROM training';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute()) throw new Exception("Select failed: {$sql}");
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
     * Removes training from the available list record
     * @param String $id The ID of the record to remove
     * @return Boolean True on success, false otherwise
     */
    public function removeAvailableTrainingByID ($id) {
        $sql = 'DELETE FROM training WHERE id = ?;';
        $sql1 = ' DELETE FROM emp_training WHERE trid = :trid';
        try {
            $pntr = $this->dbh->prepare($sql);
            $pntr1 = $this->dbh->prepare($sql1);
            if (!$pntr->execute([$id])) throw new Exception("Delete failed: {$sql}");
            if (!$pntr1->execute([$id])) throw new Exception("Delete failed: {$sql}");
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
     * Retrieves all training the is NOT in the given persons EID
     * @param String The employee's ID (EID)
     * @return Array The result set in multidimensional array format, or false on failure
     */
    public function getUnusedTraining ($id) {
        $sql = 'SELECT * FROM training WHERE id NOT IN (SELECT trid FROM emp_training WHERE eid = ?)';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$id])) throw new Exception("Select failed: {$sql}");
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
     * Retrieves the used set of training associated with the given EID
     * @param String $id The employee's ID
     * @return Array The result set in mutidimensional array format, or false on failure
     */
    public function getEmployeeTraining ($id) {
        $sql = 'SELECT training.id as trid,training.description,emp_training.train_date 
                FROM emp_training
                INNER JOIN training ON emp_training.trid = training.id
                WHERE emp_training.eid = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$id])) throw new Exception("Select failed: {$sql}");
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
     * Updates and existing employee skill record
     * @param Array $data In the form `['trid'=>string,'eid'=>string,'uid'=>string,'train_date'=dateISO]`
     * @return Boolean True on success, or false on failure
     */
    public function updateSkillTraining (Array $data) {
        $sql = 'UPDATE emp_training SET train_date = :date, uid = :uid WHERE trid = :trid AND eid = :eid';
        $insert = [
            ':date'=>$data['train_date'],
            ':uid'=>$data['uid'],
            ':trid'=>$data['trid'],
            ':eid'=>$data['eid']
        ];
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute($insert)) throw new Exception("Update failed: {$sql}");
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Adds the given data to an employee record
     * @param Array $data In the form `['trid'=>string,'eid'=>string,uid'=>string]
     * @return Boolean True on success, false otherwise
     */
    public function addSkillToEmployee (Array $data) {
        $sql = 'INSERT INTO emp_training VALUES (:eid,:trid,CURRENT_DATE,:uid)';
        $insert = [
            ':eid'=>$data['eid'],
            ':trid'=>$data['trid'],
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
     * Removes an employee skill record
     * @param String $eid The employee ID
     * @param String $trid The skill record ID
     * @return Boolean True on success, false otherwise
     */
    public function removeSkillFromEmployee ($eid,$trid) {
        $sql = 'DELETE FROM emp_training WHERE eid = :eid AND trid = :trid';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([':eid'=>$eid,':trid'=>$trid])) throw new Exception("Delete failed: {$sql}");
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Searches for employees that have the specific training given
     * @param String $terms The search string formatted with the search string formater.
     * @return Array An array of indexed results, or false on failure
     */
    public function searchSkills ($terms) {
        $sql = "SELECT DISTINCT
                    a.id AS eid,
                    (SELECT first||' '||middle||' '||last||' '||other FROM profiles WHERE id = a.pid) AS name,
                    b.train_date
                FROM employees AS a
                INNER JOIN emp_training AS b ON b.eid = a.id
                WHERE b.trid IN (
                        SELECT trid
                        FROM emp_training AS b
                        INNER JOIN training ON training.id = b.trid
                        WHERE training.search @@ to_tsquery(?)
                    )
                    AND a.end_date IS NULL";
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$terms])) throw new Exception("Select failed: {$sql}");
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }
}