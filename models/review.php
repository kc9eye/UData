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
class Review extends Employee {
    const DATA_TIMEFRAME = '6 months';

    private $review;
    public $revid;

    public function __construct (PDO $dbh, $revid) {
        $this->dbh = $dbh;
        $this->review = array();
        $this->revid = $revid;
        $this->setReviewData();

        parent::__construct($this->dbh,$this->review['raw_review'][0]['eid']);
        
        $this->setAttendanceData();
        $this->setManagementComments();
        $this->setAppraisalData();
        $this->setLastReview();
    }

    protected function setLastReview() {
        $sql = 
            'select MAX(end_date) 
            from reviews 
            where eid = :eid
            and end_date not in (
                select MAX(end_date) from reviews where eid = :uid
            )';
        $pntr = $this->dbh->prepare($sql);
        $eid = $this->review['raw_review'][0]['eid'];
        if (!$pntr->execute([':eid'=>$eid,':uid'=>$eid])) throw new Exception(print_r($pntr->errorInfo(),true));
        $this->review['last_review'] = $pntr->fetchAll(PDO::FETCH_ASSOC)[0]['max'];
    }

    protected function setReviewData () {
        //Get the raw review table data
        $sql = 'SELECT * FROM reviews WHERE id = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$this->revid])) throw new Exception(print_r($pntr->errorInfo(),true));
            $this->review['raw_review'] = $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

        //Get the review attendance data
    protected function setAttendanceData () {
        $sql = 'SELECT * FROM missed_time WHERE eid = :eid AND (occ_date >= (:start_date::date - :timeframe::interval)) ORDER BY occ_date DESC';
        try {
            $pntr = $this->dbh->prepare($sql);
            $params = [
                ':eid'=>$this->review['raw_review'][0]['eid'],
                ':start_date'=>$this->review['raw_review'][0]['start_date'],
                ':timeframe'=>self::DATA_TIMEFRAME
            ];
            if (!$pntr->execute($params)) throw new Exception(print_r($pntr->errorInfo(),true));
            $this->review['attendance'] = $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

        //Get the comments data
    protected function setManagementComments () {
        $sql = 
            "SELECT
                id,
                (SELECT firstname||' '||lastname FROM user_accts WHERE id = a.uid) as author,
                _date as date,
                comments
            FROM supervisor_comments as a
            WHERE eid = :eid AND (_date >= (:start_date::timestamp - :timeframe::interval))
            ORDER BY _date DESC";
        $params = [
            ':eid'=>$this->review['raw_review'][0]['eid'],
            ':start_date'=>$this->review['raw_review'][0]['start_date'],
            ':timeframe'=>self::DATA_TIMEFRAME
        ];
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute($params)) throw new Exception(print_r($pntr->errorInfo(),true));
            $this->review['comments'] = $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    protected function setAppraisalData () {
        //Get review appraisals
        $sql = 
            'SELECT
                id,
                uid,
                (SELECT firstname||\' \'||lastname as author FROM user_accts WHERE id = a.uid),
                revid,
                comments,
                _date
             FROM review_comments as a
             WHERE revid = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$this->revid])) throw new Exception(print_r($pntr->errorInfo(),true));
            $this->review['review_comments'] = $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * @return The active review ID, false otherwise
     */
    public function getReviewID () {
        if (empty($this->review['raw_review'][0]['id'])) return false;
        else return $this->review['raw_review'][0] ['id'];
    }

    /**
     * Returns the review start date;
     * @return String The review start data.
     */
    public function getStartDate () {
        return $this->review['raw_review'][0]['start_date'];
    }

    /**
     * Returns the review end date;
     * @return String The review end date
     */
    public function getEndDate () {
        return $this->review['raw_review'][0]['end_date'];
    }

    /**
     * Returns an array of attendance data in the Review::DATA_TIMEFRAME timeframe
     * @return Array In the form 
     * `['id'=>string,'eid'=>string,'occ_date'=>string,'absent'=>bool,'arrive_time'=>string,
     *   'leave_time'=>string,'description'=>string,'excused'=>bool,'uid'=>string,'_date'=>string]`
     */
    public function getReviewAttendance () {
        return $this->review['attendance'];
    }

    /**
     * Returns an array of management comment data
     * @return Array In the form: 
     * `['id'=>string,'author'=>string,'_date'=>string,'comments'=>string]`
     */
    public function getReviewManagementComments () {
        return $this->review['comments'];
    }

    /**
     * Returns appraisals which are not from the given UID
     * @param String $uid The UID of the user to exclude
     * @return Array In the form 
     * `['id'=>string,'uid'=>string,'author'=>string,'revid'=>string,'comments'=>string,'_date'=>string]`,
     * or false if nothing found.
     */
    public function getOthersAppraisals ($uid) {
        $return = array();
        foreach($this->review['review_comments'] as $row) {
            if ($row['uid'] != $uid) array_push($return,$row);
        }
        if (empty($return)) return false;
        else return $return;
    }

    /**
     * Returns only the appraisal from the users UID specified
     * @param String $uid The users UID of the appraisal to return
     * @return Array In the form :
     * `['id'=>string,'uid'=>string,'author'=>string,'revid'=>string,'comments'=>string,'_date'=>string]`,
     * or false if nothing found
     */
    public function getUserAppraisal ($uid) {
        foreach($this->review['review_comments'] as $row) {
            if ($row['uid'] == $uid) return $row;
        }
        return false;
    }

    /**
     * Returns all appraisals including the appraisers name
     * @return Array of records in the form :
     * `['id'=>string,'uid'=>string,'revid'=>string,'comments'=>string,'_date'=>string,'author'=>string]`
     */
    public function getAllAppraisals () {
        return $this->review['review_comments'];
    }

    public function getLastReview () {
        return $this->review['last_review'];
    }

    public function getMeetingComments() {
        return $this->review['raw_review'][0]['meeting_comments'];
    }

    public function commitComments($comments) {
        $sql = 'update reviews set meeting_comments = :comments where id = :revid';
        $pntr = $this->dbh->prepare($sql);
        if (!$pntr->execute(['comments'=>$comments,':revid'=>$this->revid])) throw new Exception(print_r($pntr->errorInfo(),true));
        else return true;
    }

    public function getScheduledFollowup($eid) {
        $pntr = $this->dbh->prepare('select * from scheduled_reviews where eid = ?');
        if (!$pntr->execute([$eid])) throw new Exception(print_r($pntr->errorInfo(),true));
        if (empty(($results = $pntr->fetchAll(\PDO::FETCH_ASSOC)))) return null;
        else return $results[0];
    }

    public function scheduleFollowup($data) {
        //check if employee has a review scheduled
    }
}