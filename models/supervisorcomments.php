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
class SupervisorComments {
    protected $dbh;
    public $newCommentID;

    public function __construct (PDO $dbh) {
        $this->dbh = $dbh;
        $this->newCommentID = null;
    }

    public function addNewSupervisorFeedback (Array $data) {
        $sql = "INSERT INTO supervisor_comments VALUES (:id,:uid,now(),:comments,:eid,:fid,:subject)";
        $insert = [
            ':id' => uniqid(),
            ':uid'=> $data['uid'],
            ':comments' => $data['comments'],
            ':eid' => $data['eid'],
            ':fid' => $data['fid'],
            ':subject' => $data['subject']
        ];
        try {
            $pntr = $this->dbh->prepare($sql);
            
            if (!$pntr->execute($insert)) throw new Exception(print_r($pntr->errorInfo(),true));
            $this->newCommentID = $insert[':id'];
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

    public function getComment ($id) {
        $sql = 
            "SELECT
                id,
                eid,
                uid,
                fid,
                (
                    SELECT profiles.first||' '||profiles.middle||' '||profiles.last||' '||profiles.other
                    FROM profiles
                    INNER JOIN employees
                    ON employees.pid = profiles.id
                    WHERE employees.id = a.eid
                ) as name,
                _date as date,
                subject,
                comments,
                (
                    SELECT firstname||' '||lastname FROM user_accts WHERE id = a.uid
                ) as author
                FROM supervisor_comments as a
                WHERE a.id = ?";
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
}