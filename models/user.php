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
class User {
    private $data;

    public function __construct (PDO $dbh, $uid) {
        $data = array();
        $this->uid = $uid;
        $this->dbh = $dbh;
        $this->getUserInfo();
        $this->getProfileData();
        $this->getNotificationData();
    }

    public function __set ($name,$value) {
        $this->data[$name] = $value;
    }

    public function __get ($name) {
        try {
            return $this->data[$name];
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    private function getUserInfo () {
        $sql = 'SELECT * FROM user_accts WHERE id = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$this->uid])) throw new Exception("Select failed: {$sql}");
            $user = $pntr->fetchAll(PDO::FETCH_ASSOC)[0];
            foreach ($user as $index => $value) {
                $this->$index = $value;
            }
            $this->password = null;
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

    private function getProfileData () {
        $sql = 'SELECT * FROM profiles WHERE id = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$this->pid])) throw new Exception(print_r($pntr->errorInfo(),true));
            $this->data['profile'] = $pntr->fetchAll(PDO::FETCH_ASSOC)[0];
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            $this->data['profile'] = null;
        }
    }

    private function getNotificationData () {
        $sql = 
            'SELECT DISTINCT
                notifications.id as id,
                notifications.description
            FROM notifications
            INNER JOIN notify ON notify.nid = notifications.id
            WHERE notify.uid = ?
            ORDER BY notifications.description ASC';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$this->uid])) throw new Exception(print_r($pntr->errorInfo(),true));
            $this->data['notifications'] = $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            $this->data['notifications'] = null;
        }
    }

    /**
     * Returns the users UID
     * @return String 
     */
    public function getUID () {
        return $this->uid;
    }

    /**
     * Returns the users username aka email
     * @return String 
     */
    public function getUserName () {
        return $this->username;
    }

    /**
     * Returns the users First name
     * @return String
     */
    public function getFirstName () {
        return $this->firstname;
    }

    /**
     * Returns the users lastname, if any
     * @return String
     */
    public function getLastName () {
        return $this->lastname;
    }

    /**
     * Returns the profile array
     * @return Array
     */
    public function getProfileArray () {
        return $this->data['profile'];
    }

    /**
     * Returns any alternate emails given for the user
     * @return String
     */
    public function getAltEmail () {
        if (!is_null($this->alt_email)) return $this->alt_email;
        elseif(!is_null($this->data['profile']['alt_email'])) return $this->data['profile']['alt_email'];
        else return '';
    }

    /**
     * Returns the timestamp that the account was created
     * @return String
     */
    public function getCreationDate () {
        return $this->_date;
    }

    /**
     * Returns the users full concatenated name
     * @return String
     */
    public function getFullName () {
        return "{$this->data['profile']['first']} {$this->data['profile']['middle']} {$this->data['profile']['last']} {$this->data['profile']['other']}";
    }

    /**
     * Returns the users profile address
     * @return String
     */
    public function getFullAddress () {
        return "{$this->data['profile']['address']} {$this->data['profile']['address_other']} {$this->data['profile']['city']}, {$this->data['profile']['state_prov']} {$this->data['profile']['postal_code']}";
    }

    /**
     * Returns the users home phone number
     * @return String
     */
    public function getHomePhone () {
        return $this->data['profile']['home_phone'];
    }

    /**
     * Returns the users cell phone number
     * @return String
     */
    public function getCellPhone () {
        return $this->data['profile']['cell_phone'];
    }

    /**
     * Returns the users alternate phone number
     * @return String
     */
    public function getAltPhone () {
        return $this->data['profile']['alt_phone'];
    }

    /**
     * Return the users emergency contact info
     * @return String
     */
    public function getEmergencyContact () {
        return "{$this->data['profile']['e_contact_name']}({$this->data['profile']['e_contact_relation']}) {$this->data['profile']['e_contact_number']}";
    }

    /**
     * Returns the users choosen theme
     * @return String
     */
    public function getUserTheme () {
        return $this->data['profile']['theme'];
    }

    /**
     * Returns the users choosen date format string
     * @return String
     */
    public function getUserDateFormat () {
        return $this->data['profile']['date_display'];
    }

    /**
     * Returns an array of the users current Notificions list
     * @return Array
     */
    public function getUserNotifications () {
        return $this->data['notifications'];
    }
}