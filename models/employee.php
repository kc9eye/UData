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
class Employee {
    private $data;
    protected $dbh;

    /**
     * Retrieves dat given the Employee ID
     * @param PDO $dbh The instance PDO object, database access handle
     * @param String $eid The employee ID to retrieve
     * @return Employee
     */
    public function __construct (PDO $dbh, $eid) {
        $this->dbh = $dbh;
        $this->data = array();
        $this->eid = $eid;
        $this->setData();
    }

    protected function setData () {
        try {
        if (!$this->setEmployeeData()) throw new Exception("Failed to set employee data, can't continue.");
        if (!$this->setProfileData()) throw new Exception("Failed to set profile data, can't continue.");
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
        $this->setAttendanceData();
        $this->setTrainingData();
        $this->setInjuriesData();
        $this->setCommentsData();
    }

    private function setEmployeeData () {
        $sql = 'SELECT * FROM employees WHERE id = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$this->eid])) throw new Exception("Select failed: {$sql}");
            $this->Employee = $pntr->fetchAll(PDO::FETCH_ASSOC)[0];
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

    private function setProfileData () {
        $sql = 'SELECT * FROM profiles WHERE id = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$this->Employee['pid']])) throw new Exception("Select failed: {$sql}");
            $profile = $pntr->fetchAll(PDO::FETCH_ASSOC)[0];
            if (!empty($this->Employee['photo_id'])) {
                $profile['image'] = $this->getImageFilename();
            }
            $this->Profile = $profile;
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

    private function setAttendanceData () {
        $sql = 'SELECT * FROM missed_time WHERE eid = ? ORDER BY occ_date DESC';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$this->Employee['id']])) throw new Exception("Select faile: {$sql}");
            $this->Attendance = $pntr->fetchAll(PDO::FETCH_ASSOC);
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    private function setTrainingData () {
        $sql =
            "SELECT 
                (SELECT description FROM training WHERE id = a.trid) as training,
                train_date,
                (SELECT firstname||' '||lastname FROM user_accts WHERE id = a.uid) as trainer
            FROM emp_training as a
            WHERE eid = ?
            ORDER BY train_date DESC";
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$this->Employee['id']])) throw new Exception("Failed to retrieve training data.");
            $this->Training = $pntr->fetchAll(PDO::FETCH_ASSOC);
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    private function setInjuriesData () {
        $sql = 
            "SELECT 
             id, 
             injury_date, 
             injury_description, 
             recordable, 
             witnesses,
             followup_medical,
             (SELECT firstname||' '||lastname FROM user_accts WHERE id = a.uid) as recorder,
             _date as report_date 
             FROM injuries as a
             WHERE eid = ?";
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$this->Employee['id']])) throw new Exception("Failed to retrieve injury data.");
            $this->Injuries = $pntr->fetchAll(PDO::FETCH_ASSOC);
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    private function setCommentsData () {
        $sql = 
            "SELECT
                id,
                (SELECT firstname||' '||lastname FROM user_accts WHERE id = a.uid) as author,
                _date as date,
                comments
            FROM supervisor_comments as a
            WHERE eid = ?";
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$this->Employee['id']])) throw new Exception("Failed to retrieve comment data.");
            $this->Comments = $pntr->fetchAll(PDO::FETCH_ASSOC);
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
    }

    private function getImageFilename () {
        $indexer = new FileIndexer($this->dbh, null);
        $index = $indexer->getIndexByID($this->Employee['photo_id']);
        return $index[0]['file'];
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