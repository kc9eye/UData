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
    const OPEN_REVIEW = 1;
    
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
        $this->setMatrixData();
        $this->setInjuriesData();
        $this->setCommentsData();
    }

    private function setEmployeeData () {
        $sql = 'SELECT * FROM employees WHERE id = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$this->eid])) throw new Exception(print_r($pntr->errorInfo(),true));
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
            $this->AttendancePoints = $this->getAttendancepoints();
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

    private function setMatrixData () {
        $sql =
            'select work_cell.cell_name,cell_matrix.gen_date,cell_matrix.trained
            from cell_matrix
            inner join work_cell on work_cell.id = cell_matrix.cellid
            where eid = ?
            order by gen_date desc';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$this->Employee['id']])) throw new Exception("Failed to retreive matrix data.");
            $this->Matrix = $pntr->fetchAll(PDO::FETCH_ASSOC);
            return true;
        }
        catch(Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
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
                subject,
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

    public function getAttendanceDateRange($begin,$end) {
        $sql = 'SELECT * FROM missed_time WHERE eid = :eid AND occ_date <= :end AND occ_date >= :begin ORDER by occ_date';
        $data = [
            ':eid'=>$this->Employee['id'],
            ':begin'=>$begin,
            ':end'=>$end
        ];
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute($data)) throw new Exception(print_r($pntr->errorInfo(),true));
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch(Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return [];
        }
    }

    public function getAttendancePoints() {
        $sql = "select sum(points) from missed_time where eid = ? and occ_date >= (current_date - interval '180 days')";
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$this->eid])) throw new Exception(print_r($pntr->errorInfo(),true));
            return $pntr->fetchColumn(0);
        }
        catch(Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return null;
        }
    }

    public function getAttendanceOcurrences() {
        $sql = 
        'select count(*)
        from missed_time
        where 
            to_tsquery(\'absence | late | left | tardy\')
            @@
            to_tsvector(description)
        and
            eid = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$this->eid])) throw new Exception(print_r($pntr->errorInfo(),true));
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch(Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return null;
        }
    }

    public function getAttendanceRatio() {
        try {
            $pntr = $this->dbh->prepare('select (current_date - start_date) as "career_days" from employees where id = ?');
            if (!$pntr->execute([$this->Employee['id']])) throw new Exception(print_r($pntr->errorInfo(),true));

            $career_years = $pntr->fetchAll(PDO::FETCH_ASSOC)[0]['career_days']/365;
            $ratio = $this->getAttendanceOcurrences()[0]['count']/(207*$career_years);

            return round($ratio*100,2);
        }
        catch(Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return 'ERROR';
        }
    }

    /**
     * Returns the image file name of the employee
     * @return String The image's disk filename
     */
    public function getImageFilename () {
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

    /**
     * Returns the employees full name as a string
     * @return String The employees full profile name.
     */
    public function getFullName () {
        return "{$this->data['Profile']['first']} {$this->data['Profile']['middle']} {$this->data['Profile']['last']} {$this->data['Profile']['other']}";
    }

    /**
     * Returns the employees full address
     * @return String The employees full address
     */
    public function getFullAddress () {
        return "{$this->data['Profile']['address']} {$this->data['Profile']['address_other']} {$this->data['Profile']['city']}, {$this->data['Profile']['state_prov']} {$this->data['Profile']['postal_code']}";
    }

    /**
     * Returns the employees phone numbers
     * @return String Phone #'s
     */
    public function getPhoneNumbers () {
        return "Home: {$this->data['Profile']['home_phone']} Cell: {$this->data['Profile']['cell_phone']} Other: {$this->data['Profile']['alt_phone']}";
    }

    /**
     * Returns the employees emergency contact
     * @return String Emergency contact
     */

     public function getEmergencyContact () {
         return "Name: {$this->data['Profile']['e_contact_name']} ({$this->data['Profile']['e_contact_relation']}): {$this->data['Profile']['e_contact_number']}";
     }

     /**
      * Returns the employees EID number
      * @return String EID
      */
      public function getEID () {
          return $this->data['eid'];
      }

      /**
       * Returns the employee employment data
       * @return Array An array containing the employment data in the form:
       * `['id'=>string,'status'=>string,'pid'=>string,'start_date'=>string,'end_date'=>string,'photo_id'=>string,'_date'=>string,'uid'=>string]`
       * _date is the date the entry was made, uid is the UID of the user making the entry.
       * pid is the profile id number, status is their employment status, and id is their EID
       */
      public function getEmployment () {
          return $this->data['Employee'];
      }

      /**
       * Returns the employee attendance data
       * @return Array An array of missed time occurrences in the form:
       * `['id'=string,'eid'=>string,'occ_date'=>string,'absent'=>bool,'arrive_time'=>string,'leave_time'=>string,'description'=>string,'excused'=>bool,'uid'=>string,'_date'=>string]`
       */
      public function getAttendance () {
          return $this->data['Attendance'];
      }

      /**
       * Returns employee injuries data
       * @return Array In the form:
       * `['id'=>string,'eid'=>string,'injury_date'=>string,'injury_description'=>string,'witnesses'=>string,'recordable'=>bool,'followup_medical'=>bool,'record_date'=>string,'recorder'=>string]`
       */
      public function getInjuries () {
          return $this->data['Injuries'];
      }

      /**
       * Returns the employees training data
       * @return Array In the form :
       * `['training'=>string,'train_date'=>string,'trainer'=>string]`
       */
      public function getTraining () {
          return $this->data['Training'];
      }

      /**
       * Returns any supervisor comments about the employee
       * @return Array In the form :
       * `['id'=>string,'author'=>string,'date'=>string,'comments'=>string]`
       */
      public function getComments () {
          return $this->data['Comments'];
      }

      /**
       * Returns whether or not the employee is on attendance probation
       * @return Boolean
       * @author Paul W. Lane 2022
       * @copyright 2022 Paul W. Lane
       */
      public function getProbationStatus() {
        $sql =
            'select * from employee_probation
            where eid = ?
            and (date_trunc(\'day\',_date) + period) > CURRENT_DATE';
        $pntr = $this->dbh->prepare($sql);
        if (!$pntr->execute([$this->getEID()])) throw new Exception(print_r($pntr->errorInfo(),true));
        if (empty(($result = $pntr->fetchAll(PDO::FETCH_ASSOC)))) return false;
        else return true;
      }

}