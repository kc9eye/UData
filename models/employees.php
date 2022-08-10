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
 * Employees Class Model
 * 
 * @package UData\Models\Database\Postgres
 * @link https://kc9eye.github.io/udata/UData_Database_Structure.html
 * @author Paul W. Lane
 * @license GPLv2
 */
class Employees extends Profiles {

    const REVIEW_TIMEFRAME = '7 days';

    public function __construct (PDO $dbh) {
        parent::__construct($dbh);
    }

    /**
     * Handles creating a new profile for an employee
     * @param Array $data The data array in the form:
     * `['uid'=>string,'start_date'=>string,'status'=>string,'first'=>string,'middle'=>string,
     * 'last=>string,'other'=>string,'email'=>string,'alt_email'=>string,'address'=>string,
     * 'address_other'=>string,'city'=>string,'state_prov'=>string,'postal_code'=>string,
     * 'home_phone'=>string,'cell_phone'=>string,'alt_phone'=>string,'e_contact_name'=>string,
     * 'e_contact_number'=>string,'e_contact_relation'=>string]`
     * @return Boolean True on success, false otherwise
     */
    public function addNewEmployee (Array $data) {
        $sql = 'INSERT INTO employees VALUES (:id, :status, :pid, :start_date::date, :end_date::date, :photo_id, now(), :uid,:smid)';
        try {
            if (!$this->createNewProfile($data)) throw new Exception("Create profile failed");
            $insert = [
                ':id'=>uniqid(),
                ':status'=>$data['status'],
                ':pid'=>$this->pid,
                ':start_date'=>$data['start_date'],
                ':end_date'=>null,
                ':photo_id'=>$data['fid'],
                ':uid'=>$data['uid'],
                ':smid'=>$data['smid']
            ];
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
     * Searches the table for matching employees
     * @param String A ts_query() formated string to search for
     * @return Array Returns a multidimensional array of results on success, false on error.
     */
    public function searchEmployees ($search_string) {
        $sql = "
            SELECT
                employees.id as id,
                profiles.first||' '||profiles.middle||' '||profiles.last||' '||profiles.other as name,
                employees.start_date as start_date, employees.end_date as end_date
            FROM employees
            INNER JOIN profiles
            ON profiles.id = employees.pid
            WHERE profiles.search_index @@ to_tsquery(?)
            AND profiles.id IN (SELECT pid FROM employees)";
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$search_string])) throw new Exception("Select failed: {$sql}");
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
     * Returns a multi-dimensional array of active employees and their UID
     * 
     * Meaning any profiles from the employee table not listing as terminated
     * @return Array In the form [['eid'=>string,'name'=>string],...]
     */
    public function getActiveEmployeeList () {
        $sql = 
            "SELECT
                employees.id as eid,
                profiles.first||' '||profiles.middle||' '||profiles.last||' '||profiles.other as name
            FROM employees
            INNER JOIN profiles ON profiles.id = employees.pid
            WHERE employees.end_date IS NULL
            ORDER BY profiles.last ASC";
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute()) throw new Exception(print_r($pntr->errorInfo(),true));
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Handles updating a profile for an employee
     * @param Array $data The data array in the form:
     * `['eid'=>string,uid'=>string,'start_date'=>string,'status'=>string,'first'=>string,'middle'=>string,
     * 'last=>string,'other'=>string,'email'=>string,'alt_email'=>string,'address'=>string,
     * 'address_other'=>string,'city'=>string,'state_prov'=>string,'postal_code'=>string,
     * 'home_phone'=>string,'cell_phone'=>string,'alt_phone'=>string,'e_contact_name'=>string,
     * 'e_contact_number'=>string,'e_contact_relation'=>string]`
     * @return Boolean True on success, false otherwise
     */
    public function updateEmployee (Array $data) {
        if (!empty($data['end_date'])) {
            $sql = 'UPDATE employees SET start_date=:start_date::date,end_date=:end_date::date,photo_id=:fid,status=:status,_date=now(),uid=:uid,smid=:smid WHERE id=:id';
            $insert = [
                ':start_date'=>$data['start_date'],
                ':end_date'=>$data['end_date'],
                ':fid'=>$data['fid'],
                ':status'=>$data['status'],
                ':uid'=>$data['uid'],
                ':id'=>$data['eid'],
                ':smid'=>$data['smid']
            ];
        }
        else {
            $sql = 'UPDATE employees SET start_date=:start_date::date,photo_id=:fid,status=:status,_date=now(),uid=:uid, smid=:smid WHERE id=:id';
            $insert = [
                ':start_date'=>$data['start_date'],
                ':fid'=>$data['fid'],
                ':status'=>$data['status'],
                ':uid'=>$data['uid'],
                ':id'=>$data['eid'],
                ':smid'=>$data['smid']
            ];
        }
        try {
            if (!$this->updateProfile($data)) throw new Exception("Failed to update employee profile.");

            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute($insert)) throw new Exception("Failed to update employee");
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Adds a new attendance record to the given employee
     * @param Array $data The data array in the form; `['eid'=>string,'occ_date'=>ISODate,'arrive_time'=>Time,'leave_time'=>Time,
     * 'absent'=>Boolean,'excused'=>Boolean,'description'=>String,uid=>String]`
     * @return Boolean True on sucess, false otherwise.
     */
    public function addAttendanceRecord (Array $data) {
        if (isset($data['probation'])) $this->startProbation($data['eid']);
        $sql = 'INSERT INTO missed_time VALUES (
            :id,
            :eid,
            :occ_date,
            :absent::boolean,
            :arrive_time::time,
            :leave_time::time,
            :description,
            :excused::boolean,
            :uid,
            now(),
            :points
        )';
        try {
            $pntr = $this->dbh->prepare($sql);
            //For issue #57
            if (!empty($data['begin_date']) && !empty($data['end_date'])) {
                $begin = new DateTime($data['begin_date']);
                $end = new DateTime($data['end_date']);
                $end = $end->modify( '+1 day' );
                $interval = new DateInterval('P1D');
                $period = new DatePeriod($begin,$interval,$end);

                //for points calculation
                $data['congruent'] = true;
                $data['period'] = $period;
                $data['points'] = $this->calculateTimePoints($data);

                //For Adam's probation notification request
                if (isset($data['probation_set']) && ($data['points'] > 0)) {
                    $this->probationNotification($data['eid']);
                } 

                $this->dbh->beginTransaction();
                foreach($period as $date) {
                    $insert = [
                        ':id'=>uniqid(),
                        ':eid'=>$data['eid'],
                        ':occ_date'=>$date->format('Y/m/d'),
                        ':arrive_time'=>$data['arrive_time'],
                        ':leave_time'=>$data['leave_time'],
                        ':absent'=>$data['absent'],
                        ':description'=>$data['description'],
                        ':uid'=>$data['uid'],
                        ':excused'=>$data['excused'],
                        ':points'=>$data['points']
                    ];
                    $pntr->execute($insert);
                }
                $this->dbh->commit();
                return true;
            }
            elseif (!empty($data['begin_date'])) {
                //for points calucation
                $data['congruent'] = false;

                $insert = [
                    ':id'=>uniqid(),
                    ':eid'=>$data['eid'],
                    ':occ_date'=>$data['begin_date'],
                    ':arrive_time'=>$data['arrive_time'],
                    ':leave_time'=>$data['leave_time'],
                    ':absent'=>$data['absent'],
                    ':description'=>$data['description'],
                    ':uid'=>$data['uid'],
                    ':excused'=>$data['excused'],
                    ':points'=>$this->calculateTimePoints($data)
                ];

                //For Adam's probation notification
                if (isset($data['probation_set']) && ($insert[':points'] > 0)) {
                    $this->probationNotification($insert[':eid']);
                }

                if (!$pntr->execute($insert)) throw new Exception("Insert failed: {$sql}");
                return true;
            }
            elseif (!empty($data['end_date'])) {
                //for points calculation

                $data['congruent'] = false;
                $insert = [
                    ':id'=>uniqid(),
                    ':eid'=>$data['eid'],
                    ':occ_date'=>$data['end_date'],
                    ':arrive_time'=>$data['arrive_time'],
                    ':leave_time'=>$data['leave_time'],
                    ':absent'=>$data['absent'],
                    ':description'=>$data['description'],
                    ':uid'=>$data['uid'],
                    ':excused'=>$data['excused'],
                    ':points'=>$this->calculateTimePoints($data)
                ];

                //Adam's probation notification request
                if (isset($data['probation_set']) && ($insert[':points'] > 0)) {
                    $this->probationNotification($insert[':eid']);
                }

                if (!$pntr->execute($insert)) throw new Exception("Insert failed: {$sql}");
                return true;
            }
            else {
                throw new Exception('Missing date data value');
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
        return false;
    }

    /**
     * Sends the probation notification
     * @param String The employees eid 
     * @return Boolean
     */
    private function probationNotification($eid) {
        include(dirname(__DIR__).'/etc/config.php');
        $mailer = new Mailer($config);
        $notifier = new Notification($this->dbh,$mailer);
        $emp = new Employee($this->dbh,$eid);
        $link = '<a href="'.$config['application-root'].'/hr/viewemployee?id='.$eid.'">'.$emp->getFullName().'</a>';
        $notifier->notify("Probation Infraction","Probation Infraction",$mailer->wrapInTemplate('probationnotification.html',$link));
        return true;
    }

    /**
     * Starts the current employee on 30 day probation
     * @return Boolean
     * @copyright 2022 Paul W. Lane
     */

    private function startProbation($eid) {
        $sql = "insert into employee_probation values (:id,now(),:eid,'30 days')";
        $pntr = $this->dbh->prepare($sql);
        if (!$pntr->execute([':eid'=>$eid,':id'=>uniqid()]))
            throw new Exception(print_r($pntr->errorInfo(),true));
        return true;
    }

    private function calculateTimePoints(Array $data) {
        if ($data['nopoints'] == 'true') return 0;
        elseif ($data['congruent']) {
            if ($data['nocall'] == 'true'){
                $count = 0;
                foreach($data['period'] as $date) {
                    $count++;
                }
                return 1/$count;
            }
            else {
                return 0;
            }
        }
        else {
            $points = 0;
            if ($data['absent'] == 'true') {
                $points += 1;
                if ($data['nocall'] == 'true') $points += 1;
            }
            elseif ($data['arrive_time'] != "00:00"||$data['leave_time'] != "00:00") {
                $points += 0.5;
            }
            return $points;
        }
    }

    public function getPointsList() :Array {
        $sql = 
        "select eid, name, sum(points) as points
        from (
            select employees.id as eid, profiles.first||' '||profiles.last as name, missed_time.points as points
            from missed_time
            inner join employees on employees.id = missed_time.eid
            inner join profiles on profiles.id = employees.pid
            where employees.end_date is null
            and missed_time.occ_date >= (current_date - interval '180 days')
        ) as foo
        where points > 0
        group by name,eid
        order by points desc";
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute()) throw new Exception(print_r($pntr->errorInfo(),true));
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return [];
        }
    }

    /**
     * Retrieves an attendance record by it's given ID
     * @param String $id The ID of the record to retrieve
     * @return Array The record in an indexed array format, or false on failure.
     */
    public function getAttendanceByID ($id) {
        $sql = 'SELECT * FROM missed_time WHERE id = ?';
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
     * Amends an attendance record with given data
     * @param Array $data The data array in the form 
     * `['id'=>String,'occ_date'=>dateISO,'absent'=>Boolean,'arrive_time'=>Time,'leave_time'=>Time,'description'=>String,'excused'=>Boolean,'uid'=>String]`
     * @return Boolean True on success, false otherwise.
     */
    public function amendAttendanceRecord (Array $data) {
        $sql = 
            'UPDATE missed_time 
             SET occ_date=:occ_date,absent=:absent::boolean,arrive_time=:arrive_time::time,
             leave_time=:leave_time::time,description=:description,excused=:excused::boolean,uid=:uid,
             points=:points
             WHERE id = :id';
        try {
            $pntr = $this->dbh->prepare($sql);
            $points = (is_null($data['points'])||empty($data['points'])||$data['points'] == "") ? 0 : $data['points'];
            $insert = [
                ':id'=> $data['id'],
                ':occ_date'=>$data['occ_date'],
                ':absent'=>$data['absent'],
                ':arrive_time'=>$data['arrive_time'],
                ':leave_time'=>$data['leave_time'],
                ':description'=>$data['description'],
                ':excused'=>$data['excused'],
                ':uid'=>$data['uid'],
                ':points'=>$points
            ];
            if (!$pntr->execute($insert)) throw new Exception(print_r($pntr->errorInfo(),true));
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
     * Removes an attendance record from the database table
     * 
     * Removes attendance record from the attendance table
     * @param String $id The ID of the record to remove
     * @return Boolean True on success, false otherwise
     */
    public function removeAttendanceRecord ($id) {
        $sql = 'DELETE FROM missed_time WHERE id = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$id])) throw new Exception('Failed to remove record: '.$id);
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Initializes a new employee review event email notifications
     * 
     * Starts a new review process for the given employee (by UID)
     * @param Instance $server The system Instance object.
     * @param String $eid The employees ID to begin the process
     * @param String $uid The user ID initiating the review
     * @return Boolean True on success, false otherwise
     */
    public function initiateReview (Instance $server, $eid) {
        $sql = "INSERT INTO reviews (id,eid,start_date,end_date,uid) VALUES (:id,:eid,now(),(now() + :timeframe::interval),:uid)";
        $insert = [
            ':id'=>uniqid(),
            ':eid'=>$eid,
            ':timeframe'=>self::REVIEW_TIMEFRAME,
            ':uid'=>$server->currentUserID
        ];
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute($insert)) throw new Exception(print_r($pntr->errorInfo(),true));
            $employee = new Employee($this->dbh,$eid);
            $notifier = new Notification($this->dbh,$server->mailer);
            $body = $server->mailer->wrapInTemplate(
                'reviewinitialized.html',
                "<a href='{$server->config['application-root']}/hr/employeereview?eid={$eid}'>".$employee->getFullName()."</a>"
            );
            if (!$notifier->notify('Review Started','Review Process Initialized',$body)) {
                $sql = "DELETE FROM reviews WHERE id = '{$insert[':id']}'";
                $pntr = $this->dbh->query($sql);
                return false;
            }
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Inserts a new appraisal for an employee
     * @param Array $data The data to insert in the form :
     * `['revid'=>string,'uid'=>string,'appraisal'=>string]`
     * @return Boolean True on success, false otherwise
     */
    public function insertUserAppraisal (Array $data) {
        $sql = 'INSERT INTO review_comments VALUES (:id,:uid,:revid,:comments,now())';
        $insert = [
            ':id'=>uniqid(),
            ':uid'=>$data['uid'],
            ':revid'=>$data['revid'],
            ':comments'=>$data['appraisal']
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
     * Updates an existing appraisal
     * @param String $id The appraisal ID to update.
     * @param String $appraisal The updated appraisal
     * @return Boolean True on success, false otherwise.
     */
    public function updateUserAppraisal ($id,$appraisal) {
        $sql = "UPDATE review_comments SET comments = :appraisal WHERE id = :id";
        $insert = [
            ':appraisal'=>$appraisal,
            ':id'=>$id
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
     * Returns an array of past reviews references for the given EID
     * @param String $eid The employees ID to lookup
     * @return Array Results as multidimensial array (possibly empty) on success, false on error
     */
    public function getPastReviews ($eid) {
        $sql = 'SELECT * FROM reviews WHERE eid = ? AND end_date <= now() ORDER BY end_date DESC';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$eid])) throw new Exception(print_r($pntr->errorInfo(),true));
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Returns whether or not the given employee is currently in review
     * @param String $eid The employees ID
     * @return Boolean True if in review, false otherwise.
     */
    public function getReviewStatus ($eid) {
        $sql = 'SELECT count(*) FROM reviews WHERE end_date >= now() AND eid = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$eid])) throw new Exception(print_r($pntr->errorInfo(),true));
            $result = $pntr->fetchAll(PDO::FETCH_ASSOC)[0]['count'];
            if ($result != 1 && $result != 0) throw new Exception("Result returned an invalid value");
            if ($result == 1) return true;
            else return false;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Returns the ID of any ongoing review
     * @param String $eid The employee ID to find ongoing review ID
     * @return String The ID of any ongoing review, false on error.
     */
    public function getOngoingReviewID ($eid) {
        $sql = 'SELECT id FROM reviews WHERE eid = ? AND end_date >= now()';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$eid])) throw new Exception(print_r($pntr->errorInfo(),true));
            return $pntr->fetchAll(PDO::FETCH_ASSOC)[0]['id'];
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Retuns an array list of pending scheduled reviews
     * @return Array A 2 dimensional array of [due date, Employee Object]
     * @author Paul W. Lane
     */
    public function getPendingScheduledReviews() {
        $sql = "select eid, date_trunc('day',(_date + schedule)) as due from scheduled_reviews where (date_trunc('day',_date) + schedule) > CURRENT_DATE";
        $pntr = $this->dbh->prepare($sql);
        if (!$pntr->execute()) throw new Exception(print_r($pntr->errorInfo(),true));
        foreach($pntr->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $scheduled[] = [$row['due'], new Employee($this->dbh, $row['eid'])];
        }
        return $scheduled;
    }

    public function addEmployeeToMatrix($data) {
        $sql =
        'insert into cell_matrix values (:id,:eid,:cellid,now(),:uid)';
        try {
            $pntr = $this->dbh->prepare($sql);
            $insert = [
                ':id'=>uniqid(),
                'eid'=>$data['eid'],
                ':cellid'=>$data['cellid'],
                ':uid'=>$data['uid']
            ];
            if (!$pntr->execute($insert)) throw new Exception(print_r($pntr->errorInfo(),true));
            return true;
        }
        catch(Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }
}
