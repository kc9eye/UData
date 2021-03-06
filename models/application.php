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
 * Application Class Model
 * @package UData\Models\Database\Postgres
 * @link https://kc9eye.github.io/udata/UData_Database_Structure.html
 * @author Paul W. Lane
 * @license GPLv2
 */
class Application {
    /**
     * @var $dbh Holds the PDO handle
     */
    private $dbh;

    /**
     * Class Constructor
     * @var $dbh A PDO database object
     */
    public function __construct (PDO $dbh) {
        $this->dbh = $dbh;
    }

    /**
     * Retrieves information about user roles
     * @param String $rid An optional role ID for a specific role
     * @return Array Either an array or false on error
     */
    public function getRole ($rid = null) {
        try {
            if (is_null($rid)) {
                $sql = 'SELECT * FROM roles ORDER BY name ASC';
                $pntr = $this->dbh->query($sql);
                return $pntr->fetchAll(PDO::FETCH_ASSOC);
            }
            else {
                $sql = 'SELECT * FROM roles WHERE id = ?';
                $pntr = $this->dbh->prepare($sql);
                if (!$pntr->execute([$rid])) throw new Exception(print_r($pntr->errorInfo(),true));
                return $pntr->fetchAll(PDO::FETCH_ASSOC);
            }
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
    }

    /**
     * Adds a new user role
     * @param String $role The role name
     * @return Boolean 
     */
    public function addRole ($role) {
        $sql = 'INSERT INTO roles VALUES (:id,:name)';
        try {
            $this->dbh->beginTransaction();
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([':id'=>uniqid(),':name'=>$role])) throw new Exception(print_r($pntr->errorInfo(),true));
            $this->dbh->commit();
            return true;
        }
        catch (Exception $e) {
            $this->dbh->rollBack();
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
    }

    /**
     * Removes a role entirely
     * @param String $rid The role ID to remove
     * @return Boolean
     */
    public function deleteRole ($rid) {
        $sql = [
            'DELETE FROM user_roles WHERE rid = ?',
            'DELETE FROM role_perms WHERE rid = ?',
            'DELETE FROM roles WHERE id = ?'
        ];
        try {
            $this->dbh->beginTransaction();
            foreach ($sql as $statement) {
                $pntr = $this->dbh->prepare($statement);
                if (!$pntr->execute([$rid])) throw new Exception(print_r($pntr->errorInfo(),true));
            }
            $this->dbh->commit();
            return true;
        }
        catch (Exception $e) {
            $this->dbh->rollBack();
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Retrieves all the permissions associated with a given role
     * @param String $rid The roles ID
     * @return Array Or false upon error
     */
    public function getPermsFromRole ($rid) {
        $sql = 
        'SELECT perms.id,perms.name
         FROM perms
         INNER JOIN role_perms ON perms.id = role_perms.pid
         WHERE role_perms.rid = ?
         ORDER BY perms.name ASC';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$rid])) throw new Exception(print_r($pntr->errorInfo(),true));
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
    }

    /**
     * Adds a given permission to the given role's permission set
     * @param String $permid The permissions ID to add
     * @param String $rid The role's ID to add the permission to
     * @return Boolean
     */
    public function addPermToRole ($permid,$rid) {
        $sql = 'INSERT INTO role_perms VALUES (:permid,:rid)';
        try {
            $this->dbh->beginTransaction();
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([':permid'=>$permid,':rid'=>$rid])) throw new Exception(print_r($pntr->errorInfo(),true));
            $this->dbh->commit();
            return true;
        }
        catch (Exception $e) {
            $this->dbh->rollBack();
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
    }

    /**
     * Removes a given permission from the set of a given role
     * @param String $permid The permission ID
     * @param String $rid The role ID
     * @return Boolean
     */
    public function removePermFromRole ($permid, $rid) {
        $sql = 'DELETE FROM role_perms WHERE pid = :permid AND rid = :rid';
        try{
            $this->dbh->beginTransaction();
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([':permid'=>$permid,':rid'=>$rid])) throw new Exception(print_r($pntr->errorInfo(),true));
            $this->dbh->commit();
            return true;
        }
        catch (PDOException $e) {
            $this->dbh->rollBack();
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
    }

    /**
     * Retrieves the unassigned roles for a given user
     * @param String $uid The users ID
     * @return Array On success, otherwise false
     */
    public function unusedRoleSet ($uid) {
        $sql = 
            'SELECT * FROM roles WHERE id NOT IN (
                SELECT rid FROM user_roles WHERE uid = ?
             )';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$uid])) throw new Exception(print_r($pntr->errorInfo(),true));
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }


    /**
     * Retrieves all permissions in the set
     * @param String An optional permission ID to retreive
     * @return Mixed Array on success, otherwise false
     */
    public function getPermission ($permid = null) {
        try {
            if (is_null($permid)) {
                $pntr = $this->dbh->query('SELECT * FROM perms');
                return $pntr->fetchAll(PDO::FETCH_ASSOC);
            }
            else {
                $sql = 'SELECT * FROM perms WHERE id = ?';
                $pntr = $this->dbh->prepare($sql);
                if (!$pntr->execute([$permid])) throw new Exception(print_r($pntr->errorInfo(),true));
                return $pntr->fetchAll(PDO::FETCH_ASSOC);
            }
        }
        catch (PDOException $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Adds a new permission to the available set pool
     * @param String $name The permission name
     * @return Boolean
     */
    public function addPermission ($name) {
        $sql = 'INSERT INTO perms VALUES (:id,:name)';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([':id'=>uniqid(),':name'=>$name])) {
                throw new Exception(print_r($pntr->errorInfo(),true));
            }
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
     * Removes a given permission from the set pool
     * @param String $permid The permissions ID to remove
     * @return Boolean
     */
    public function deletePermission ($permid) {
        $sql = 'DELETE FROM perms WHERE id = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$permid])) {
                throw new Exception(print_r($pntr->errorInfo(),true));
            }
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
     * Returns permissions not assigned to the given role.
     * 
     * Given a role ID, it returns an array of permissions not in that role.
     * @param String $rid The role ID to return unused permissions.
     * @return Mixed An array on success,otherwise false.
     */
    public function unusedPermissionSet ($rid) {
        $sql = 
            'SELECT * FROM perms
             WHERE id NOT IN (
                 SELECT pid FROM role_perms WHERE rid = ?
                 )';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$rid])) throw new Exception(print_r($pntr->errorInfo(),true));
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Searches for a given user and returns the result
     * @param String $search The search string
     * @return Mixed An array on success, otheriwse false
     */
    public function searchUsers ($search) {
        $s = new SearchStringFormater();
        $search_term = $s->formatSearchString($search);
        $sql = 
            "SELECT * FROM user_accts WHERE 
             to_tsvector(
                 username||' '||firstname||' '||coalesce(lastname,'')||' '||coalesce(alt_email,'')
             ) @@ to_tsquery(?)";
        
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$search_term])) throw new Exception(print_r($pntr->errorInfo(),true));
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
     * Retrieves the users information
     * @param String $uid The users ID 
     * @return Array On success, otherwise false;
     */
    public function getUserData ($uid) {
        $sql = 'SELECT * FROM user_accts WHERE id = ?';
        try{
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$uid])) throw new Exception(print_r($pntr->errorInfo(),true));
            if (count(($data = $pntr->fetchAll(PDO::FETCH_ASSOC))) != 1) {
                return false;
            }
            else {
                return $data[0];
            }
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Retrieves the roles assigned to a given user
     * @param String $uid The users ID
     * @return Array On success, otherwise false
     */
    public function getUserRoles ($uid) {
        $sql =
            'SELECT roles.id,roles.name FROM roles
             INNER JOIN user_roles ON user_roles.rid = roles.id
             WHERE user_roles.uid = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$uid])) throw new Exception(print_r($pntr->errorInfo(),true));
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Adds the given role to a user
     * @param String $rid The user roles ID
     * @param String $uid The users ID
     * @return Boolean
     */
    public function addRoleToUser ($rid,$uid) {
        $sql = 'INSERT INTO user_roles VALUES (:uid,:rid)';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([':rid'=>$rid,':uid'=>$uid])) {
                throw new Exception(print_r($pntr->errorInfo(),true));
            }
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
     * Removes a role from a user
     * @param String $uid The users ID
     * @param String $rid The roles ID to remove
     * @return Boolean
     */
    public function removeRoleFromUser ($uid, $rid) {
        $sql = 'DELETE FROM  user_roles WHERE uid = :uid AND rid = :rid';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([':uid'=>$uid, ':rid'=>$rid])) {
                throw new Exception(print_r($pntr->errorInfo(),true));;
            }
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
     * Removes the given user from existance
     * @param String $uid The users ID to remove
     * @param String $pid The users profile ID
     */
    public function deleteUser ($uid, $pid) {
        $sql = [
            'DELETE FROM profiles WHERE id = ?',
            'DELETE FROM user_roles WHERE uid = ?',
            'DELETE FROM user_accts WHERE id = ?'
        ];
        $data = [[$pid],[$uid],[$uid]];
        try {
            $this->dbh->beginTransaction();
            for($cnt = 0; $cnt < count($sql); $cnt++) {
                $pntr = $this->dbh->prepare($sql[$cnt]);
                if (!$pntr->execute($data[$cnt])) {
                    throw new Exception(print_r($pntr->errorInfo(),true));
                }
            }
            $this->dbh->commit();
            return true;
        }
        catch (PDOException $e) {
            $this->dbh->rollBack();
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
        catch (Exception $e) {
            $this->dbh->rollBack();
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Returns an array containing a listing of all registered users
     * @return Array Multi-dimensional array of a users record, or false on error
     */
    public function getUserList () {
        $sql = 'SELECT * FROM user_accts ORDER BY firstname ASC';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute()) throw new Exception(print_r($pntr->errorInfo(),true));
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
    }

    /**
     * Returns a listing of users whom have active persistent logins
     * @return array On success, false on failure
     */
    public function getPersitentLogins () {
        $sql = 
            'SELECT auth_tokens.id, user_accts.username as "username", auth_tokens._date as "date"
             FROM auth_tokens
             INNER JOIN user_accts ON user_accts.id = auth_tokens.uid
             ORDER BY user_accts.username ASC, auth_tokens._date DESC';
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
     * Deletes the given persistent login 
     * @param string $id The login ID to delete
     * @return boolean true on success, false otherwise
     */
    public function logoutPersistentUser ($id) {
        $sql = 'DELETE FROM auth_tokens WHERE id = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$id])) throw new Exception(print_r($pntr->errorInfo(),true));
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }
}