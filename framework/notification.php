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
class Notification {

    const EMAIL_TYPE = 'EMAIL';

    private $dbh;
    public $mailer;

    public function __construct (PDO $dbh, Mailer $mailer) {
        $this->dbh = $dbh;
        $this->mailer = $mailer;
    }

    /**
     * Sends the given email notification
     * @param String $notification The notification type to look for users of the system
     * @param String $subject The subject of the email.
     * @param String $body The body of the email to send.
     * @param Array $attachments An unindexed array of file path's to attach to the email
     * @return Boolean True on success, false other wise.
     */
    public function notify ($notification, $subject, $body, Array $attachments = []) {
        $sql = 'SELECT (SELECT username FROM user_accts WHERE id = a.uid) as email
                FROM notify as a WHERE
                nid = (SELECT id FROM notifications WHERE description = ?)';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$notification])) throw new Exception(print_r($pntr->errorInfo(),true));
            else $results = $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }

        try {
            $send = ['to'=>[],'subject'=> $subject, 'body'=>$body];
            if (!empty($attachments)) $send['attach'] = $attachments;
            foreach($results as $user) {
                array_push($send['to'],$user['email']);
            }
            if (!$this->mailer->sendMail($send)) throw new Exception("Failed to send notifications.");
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Retrieves all notification records
     * @return Array A multidimensial array on success, false otherwise
     */
    public function getAllNotifications () {
        $sql = 'SELECT * FROM notifications';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute()) throw new Exception("Select failed: {$sql}");
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Deletes the given record
     * @param String $id The ID of the record to delete
     * @return Boolean True on success, false otherwise
     */
    public function deleteNotification ($id) {
        $sql = 'DELETE FROM notifications WHERE id = ?';
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
     * Returns the notification records the given user is listed in
     * @param String $uid The users ID
     * @return Array A multidimensial array of records, or false otherwise
     */
    public function getUserNotifications ($uid) {
        $sql = 'SELECT * FROM notifications WHERE id IN (
            SELECT nid FROM notify WHERE uid = ?
            )';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$uid])) throw new Exception("Select failed: {$sql}");
            else return $pntr->fetchAll(PDO::FETCH_ASSOC);
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
     * Returns the notification records the user is not listed in
     * @param String $uid The users ID to find the listing for
     * @return Array A multidimensial array of records or false otherwise
     */
    public function getUnusedNotifications ($uid) {
        $sql = 'SELECT * FROM notifications WHERE id NOT IN (
            SELECT nid FROM notify WHERE uid = ?
            )';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$uid])) throw new Exception("Select failed: {$sql}");
            else return $pntr->fetchAll(PDO::FETCH_ASSOC);
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
     * Adds the given notification to the given user
     * @param String $nid The notification ID to add
     * @param String $uid The user ID to add the notification to
     * @param Boolean True on success, otherwise false
     */
    public function addNotificationToUser ($nid,$uid) {
        $sql = 'INSERT INTO notify (nid,uid) VALUES (:nid,:uid)';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([':nid'=>$nid,':uid'=>$uid])) throw new Exception("Insert failed: {$sql}");
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Removes the given notification from the given user
     * @param String $nid The notification ID to remove
     * @param String $uid The user to remove from the notification
     * @return Boolean True on success, otherwise false
     */
    public function removeNotificationFromUser ($nid, $uid) {
        $sql = 'DELETE FROM notify WHERE nid = :nid AND uid = :userid';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([':nid'=>$nid,':userid'=>$uid])) throw new Exception("Delete failed: {$sql}");
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
     * Adds a new notification
     * @param Array $data The new notification data in the form `['description'=>string,'type'=>string,'uid'=>string]`
     * @return Boolean True on success, otherwise false.
     */
    public function addNewNotification (Array $data) {
        $sql = 'INSERT INTO notifications VALUES (:id,:dis,:type,now(),:uid)';
        try {
            $pntr = $this->dbh->prepare($sql);
            $insert = [
                ':id'=>uniqid(),
                ':dis'=>$data['description'],
                ':type'=>$data['type'],
                ':uid'=>$data['uid']
            ];
            if (!$pntr->execute($insert)) throw new Exception("Insert failed: {$sql}");
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }
}