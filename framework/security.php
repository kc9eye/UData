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
 * The application security model.
 * 
 * This class defines the security model employed by the applcation.
 * This class should not be used stand alone. Instead it is instantiated
 * by the Instance class. The methods here should rarely if ever be required
 * to be used stand alone. Instead they are called by other methods in the 
 * Instance class. Those methods in Instance class should be employed, not 
 * the ones here.
 * @package UData\Framework\Database\Postgres
 * @see Instance::getAuthority()
 * @see Instance::mustBeValidUser()
 * @see Instance::userMustHavePermission()
 * @see Instance::userMusthaveRole()
 * @see Instance::checkPermission()
 * @see Instance::checkPermsArray()
 */
Class Security {
    const BAD_USERNAME = 2112;
    const BAD_PASSWORD = 1221;
    const COOKIE_ID = 'UDIDIT';

    /**
     * The PDO object
     * @var PDO $dbh
     */
    private $dbh;

    /**
     * Used if there is an active persistent log on present
     * @var String $persistentid Holds the persistent log on ID for the machine
     */
    private $persistentid;

    /**
     * The current users ID.
     * @var String $secureUserID The current users ID, defaults to null if the current user is
     * not signed in or valid.
     */
    public $secureUserID;

    /**
     * The current signed in users account information.
     * @var Array $user Indexed array of user information of the current signed in user, default is null.
     */
    public $user;

    /**
     * Class constructor
     * @param PDO $dbh A PDO object to the current application database.
     * @return Security
     */
    public function __construct (PDO $dbh) {
        $this->dbh = $dbh;
        $this->secureUserID = null;
        $this->user = null;
    }

    /**
     * Sets security data for the current user
     * 
     * @uses Security::$secureUserID
     * @return Void
     */
    public function setUser () {
        $test = $this->dbh->prepare('SELECT COUNT(*) FROM user_accts WHERE id = ?');
        $test->execute([$this->secureUserID]);
        $res = $test->fetchAll(PDO::FETCH_ASSOC);

        if ($res[0]['count'] > 1) {
            throw new Exception("User id has more than 1 entry, database is corrupted");
        }
        elseif ($res[0]['count'] == 0) {
            return false;
        }
        elseif ($res[0]['count'] == 1) {
            $pntr = $this->dbh->prepare('SELECT * FROM user_accts WHERE id = ?');
            $pntr->execute([$this->secureUserID]);
            $user = $pntr->fetchAll(PDO::FETCH_ASSOC)[0];
            $this->user['email'] = $user['username'];
            $this->user['firstname'] = $user['firstname'];
            $this->user['lastname'] = $user['lastname'];
            $this->user['alt_email'] = $user['alt_email'];
            $this->user['uid'] = $user['id'];
        }
    }

    /**
     * Validates any persistent log on requests
     * 
     * If the user has requested a persistent log on,
     * this method checks that log on upon user return.
     * @uses Security::COOKIE_ID
     * @return Boolean `true` if valid, otherwise `fasle`
     */
    public function checkPersistentLogOn () {
        #Verify the cache exists
        if (! isset($_COOKIE[self::COOKIE_ID]) ) {
            return false;
        }

        $split = explode(':', $_COOKIE[self::COOKIE_ID]);

        try {
            #Get the log in data and verify
            $sql = 'SELECT * FROM auth_tokens WHERE selector = ?';
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$split[0]])) throw new Exception(print_r($pntr->errorInfo(),true));
            if (count(($res = $pntr->fetchAll(PDO::FETCH_ASSOC))) != 1) {
                setcookie(self::COOKIE_ID, '', time() - 1, '/');
                return false;
            }
            $res = $res[0];            
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            setcookie(self::COOKIE_ID, '', time() - 1, '/');
            return false;
        }

        if ( hash_equals( hash('sha256', $split[1]), $res['validator'] ) && $_SERVER['REMOTE_ADDR'] == $res['host']) {
            #Reset the data cache and exit if valid
            $this->secureUserID = $res['uid'];
            $this->persistentid = $res['id'];
            return true;
        }
        return false;
    }

    /**
     * Verifies a given username and password combo.
     * 
     * Takes a username and password as paramters and compares them
     * against a valid users credentials.
     * @return Boolean `true` if passwords match for given user, `false` otherwise.
     * @param String $username The username to compare passwords
     * @param String $password The given password to compare
     */
    public function verifyLogOn ($username, $password) {
        #Verify the username exists
        if (! $this->verifyUsername($username)) {
            return self::BAD_USERNAME;
        }
        
        #Get users account data
        $sql = 'SELECT * FROM user_accts WHERE username = ?';
        $pntr = $this->dbh->prepare($sql);
        $pntr->execute([$username]);
        $res = $pntr->fetchAll(PDO::FETCH_ASSOC)[0];

        #Verify correct password
        if (password_verify($password, $res['password'])) {
            $this->secureUserID = $res['id'];
            return true;
        }
        else {
            return self::BAD_PASSWORD;
        }
        return false;
    }

    /**
     * Sets a persistent log on for a given user.
     * 
     * Upon a successful log on the user has the option to remain
     * signed in. This method sets that persistent log on to 
     * be used on subsequent log on's from the given users machine.
     * @param String $uid The users ID to use for persistent log on's
     * @return Boolean
     */
    public function setPersistentLogOn ($uid) {
        $sql = 'INSERT INTO auth_tokens VALUES (:id,NOW(),:selector,:validator,:uid,:host)';
        $selector = hash('sha256', uniqid());
        $validator = bin2hex(random_bytes(32));
        $this->persistentid = uniqid();
        $dataCache = array(
            ':selector' => $selector,
            ':validator' => hash('sha256', $validator),
            ':id' => $this->persistentid,
            ':uid' => $uid,
            ':host'=>$_SERVER['REMOTE_ADDR']
        );
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute($dataCache)) throw new Exception(print_r($pntr->errorInfo(),true));
            setcookie(self::COOKIE_ID, $selector.':'.$validator, time()+(60*60*24*365), "/");
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Removes a persistent log on for a user.
     * 
     * Persistent log on's set with Security::setPersistentLogOn() are
     * removed with this method.
     * @return Boolean
     */
    public function deletePersistentLogOn () {
        $sql = 'DELETE FROM auth_tokens WHERE uid = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$this->secureUserID])) throw new Exception(print_r($pntr->errorInfo(),true));
            setcookie(self::COOKIE_ID, '', time() - 1, '/');
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Verifies that the supplied username is not already in use.
     * @param String $username The username to check.
     * @return Boolean Returns `false` if the username is not already in use, `true` if it is
     * @todo Possibly rewrite so true is returned if it is not in the database
     */
    protected function verifyUsername ($username) {
        $sql = 'SELECT count(*) FROM user_accts WHERE username = ?';
        $pntr = $this->dbh->prepare($sql);
        $pntr->execute([$username]);
        $res = $pntr->fetchAll(PDO::FETCH_ASSOC);
        if ($res[0]['count'] == 0) {
            return false;
        }
        elseif ($res[0]['count'] > 1) {
            throw new Exception('More than one user with same username, database corrupt');
        }
        else {
            return true;
        }
    }

    /**
     * Determines if the current user has the given role.
     * 
     * Returns true if user has the role, false otherwise.
     * @param String $role The role to check the user for
     * @return Boolean 
     */
    public function userHasRole ($role) {
        if (is_null($this->secureUserID)) {
            return false;
        }
        try {
            $sql = 'SELECT count(roles.id) FROM roles
                    INNER JOIN user_roles ON user_roles.rid = roles.id
                    WHERE user_roles.uid = :uid AND
                    roles.name = :name';
            $pntr = $this->dbh->prepare($sql);
            $pntr->execute([':uid'=>$this->secureUserID,':name'=>$role]);
            $res = $pntr->fetch(PDO::FETCH_ASSOC);
            if ($res['count'] >= 1) {
                return true;
            }
            return false;
        }
        catch (PDOException $e) {
            trigger_error($e->message, E_USER_WARNING);
            return false;
        }
    }

    /**
     * Determines if the current user has the given permission
     * 
     * Returns true if user has the permission, false otherwise.
     * @param String $permission The permission to check the user for
     * @return Boolean
     */
    public function userHasPermission ($permission) {
        if (is_null($this->secureUserID)) {
            return false;
        }
        try {
            $sql = 'SELECT count(id) FROM roles
                    INNER JOIN user_roles ON user_roles.rid = roles.id
                    WHERE user_roles.uid = :uid AND
                    user_roles.rid IN (
                        SELECT user_roles.rid FROM user_roles
                        INNER JOIN role_perms ON role_perms.rid = user_roles.rid
                        WHERE role_perms.pid IN (
                            SELECT perms.id FROM perms
                            WHERE perms.name = :perm
                        )
                    )';
            $pntr = $this->dbh->prepare($sql);
            $pntr->execute([':uid'=>$this->secureUserID,':perm'=>$permission]);
            $res = $pntr->fetch(PDO::FETCH_ASSOC);
            if ($res['count'] >= 1) {
                return true;
            }
            return false;
        }
        catch (PDOException $e) {
            trigger_error($e->message, E_USER_NOTICE);
            return false;
        }
    }

    /**
     * Get a array of users who have the given permission
     * 
     * Returns either a PDO array of users who have the given permission
     * or false if no users have the permission.
     * @param String $perm The permission to search for
     * @return Mixed
     */
    public function getUsersByPerm ($perm) {
        try {
            $sql = 'SELECT DISTINCT 
            user_accts.id, user_accts.username,
            user_accts.password, user_accts.firstname, 
            user_accts.lastname, user_accts.alt_email
            FROM user_accts
            INNER JOIN user_roles ON user_roles.uid = user_accts.id
            WHERE user_roles.rid IN (
                SELECT role_perms.rid FROM role_perms
                INNER JOIN perms ON perms.id = role_perms.pid
                WHERE perms.name = ?
            )';
            $pntr = $this->dbh->prepare($sql);
            $pntr->execute([$perm]);
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (PDOException $e) {
            trigger_error($e->message,E_USER_WARNING);
            return false;
        }
    }

    /**
     * Returns a PDO array of users who have the given role.
     * 
     * Either an array of users with the given role is returned 
     * or false if no users have the role.
     * @param String $role The role to search for.
     * @return Mixed
     */
    public function getUsersByRole ($role) {
        try {
            $sql = 'SELECT DISTINCT user_accts.id,user_accts.username,user_accts.password,user_accts.firstname,user_accts.lastname,user_accts.alt_email
                    FROM user_accts
                    INNER JOIN user_roles ON user_roles.uid = user_accts.id
                    WHERE user_roles.rid = (
                        SELECT roles.id FROM roles WHERE roles.name = ?
                    )';
            $pntr = $this->dbh->prepare($sql);
            $pntr->execute([$role]);
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (PDOException $e) {
            trigger_error($e->message,E_USER_WARNING);
            return false;
        }
    }
}