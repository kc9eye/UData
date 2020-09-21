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
class UserServices {
    private $server;
    protected $dbh;
    protected $mailer;
    protected $config;

    public function __construct (Instance $server) {
        $this->server = $server;
        $this->dbh = $this->server->pdo;
        $this->mailer = $this->server->mailer;
        $this->config = $this->server->config;
    }

    public function resetPassword ($username) {
        try {
            $sql = 'SELECT * FROM user_accts WHERE username = ?';
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$username])) throw new Exception(print_r($pntr->errorInfo(),true));
            if (count(($res = $pntr->fetchAll(PDO::FETCH_ASSOC))) != 1) {
                return false;
            }

            $code = bin2hex(random_bytes(32));
            $sql = 'INSERT INTO user_accts_holding VALUES (:id,:username,:password,:firstname,:lastname,:alt_email,now(),:verify_code)';
            $pntr = $this->dbh->prepare($sql);
            $insert = [
                ':id'=>$res[0]['id'],
                ':username'=>$res[0]['username'],
                ':password'=>'PASSWORD_RESET_IN_PROGRESS',
                ':firstname'=>$res[0]['firstname'],
                ':lastname'=>$res[0]['lastname'],
                ':alt_email'=>$res[0]['alt_email'],
                ':verify_code'=>hash('sha256',$code)
            ];
            $this->dbh->beginTransaction();
            if (!$pntr->execute($insert)) throw new Exception(print_r($pntr->errorInfo(),true));

            $body = $this->mailer->wrapInTemplate(
                'verifyemail.html',
                "<a href='{$this->config['application-root']}/user/password_reset?id={$code}'><strong>Reset Password</strong></a>"
            );
            $this->mailer->sendMail(['to'=>$res[0]['username'],'subject'=>'Password Reset','body'=>$body]);
            if (!empty($res[0]['alt_email'])) {
                $body = $this->mailer->wrapInTemplate(
                    'twofactorverify.html',
                    "<a href='{$this->config['error-support-link']}'><strong>Contact Support</strong></a>"
                );
                $this->mailer->sendMail(['to'=>$res[0]['alt_email'],'subject'=>'Security Notice','body'=>$body]);
            }
            $this->dbh->commit();
            return true;
        }
        catch (PDOException $e) {
            $this->dbh->rollback();
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
        catch (Exception $e) {
            $this->dbh->rollback();
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    public function finishReset ($data) {
        try {
            $code = hash('sha256',$data['verify']);
            $sql = 'SELECT * FROM user_accts_holding WHERE verify_code = ?';
            $pntr = $this->dbh->prepare($sql);
            $pntr->execute([$code]);
            if (count(($res = $pntr->fetchAll(PDO::FETCH_ASSOC))) != 1) {
                return false;
            }
            elseif (!hash_equals($res[0]['verify_code'],$code)) {
                return false;
            }
            $this->dbh->beginTransaction();
            $pntr = $this->dbh->prepare('UPDATE user_accts SET password = :password WHERE id = :id');
            $pntr1 = $this->dbh->prepare('DELETE FROM user_accts_holding WHERE verify_code = ?');
            $pntr->execute([':password'=>password_hash($data['password'],PASSWORD_DEFAULT), ':id'=>$res[0]['id']]);
            $pntr1->execute([$code]);
            $this->dbh->commit();
            return true;
        }
        catch (PDOException $e) {
            $this->dbh->rollback();
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
        catch (Exception $e) {
            $this->dbh->rollback();
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    public function verifyResetCode ($code) {
        $code = hash('sha256', $code);
        $sql = 'SELECT * FROM user_accts_holding WHERE verify_code = ?';
        $pntr = $this->dbh->prepare($sql);
        $pntr->execute([$code]);
        if (count(($res = $pntr->fetchAll(PDO::FETCH_ASSOC))) != 1) {
            return false;
        }
        elseif (!hash_equals($res[0]['verify_code'],$code)) {
            return false;
        }
        else {
            return true;
        }
    }

     /**
     * Verifies the username given is not already in use.
     * @param String $username The username to check
     * @return Boolean True if the username is already in use, false if not. 
     * Returns null on error.
     */
    public function checkUsernameNotTaken ($username) {
        $sql = 'SELECT count(*) FROM user_accts WHERE username = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$username])) throw new Exception(print_r($pntr->errorInfo(),true));
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return null;
        }
        if ($pntr->fetchAll(PDO::FETCH_ASSOC)[0]['count'] != 0) return true;
        else return false;
    }

        /**
     * Verifies that the username given has not already attempted an account creation
     * @param String $username The username to check
     * @return Boolean True if the username is not already attempted account creation, false otherwise.
     * Returns null on error.
     */
    public function verifySingleSignUpAttempt ($username) {
        $sql = "SELECT count(*) FROM user_accts_holding WHERE username = ?";
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$username])) throw new Exception(print_r($pntr->errorInfo(),true));
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return null;
        }
        if ($pntr->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
            return false;
        }
        else {
            return true;
        }
    }

    /**
     * Creates a new account and sends the verification email
     * @param Array $data The data to create the account with in the form : 
     * ['email'=>string,'password'=>string,'firstname'=>string,'lastname'=>string,'altemail'=>string]
     */
    public function createAccountToVerify ($data) {
        $sql = 'INSERT INTO user_accts_holding VALUES (:id,:username,:password,:firstname,:lastname,:alt_email,now(),:verifycode)';
        $insert = [
            ':id' => uniqid(),
            ':username' => $data['email'],
            ':password' => password_hash($data['password'],PASSWORD_DEFAULT),
            ':firstname' => $data['firstname'],
            ':lastname' => $data['lastname'],
            ':alt_email' => $data['altemail'],
            ':verifycode' => hash('sha256', bin2hex(random_bytes(32)))
        ];
        try {
            $pntr = $this->dbh->prepare($sql);
            $this->dbh->beginTransaction();
            if (!$pntr->execute($insert)) throw new Exception(print_r($pntr->errorInfo(),true));

            $body = $this->mailer->wrapInTemplate(
                "verifyemail.html",
                "<a href='{$this->config['application-root']}/user/verify?action=verify&id=".urlencode($insert[':verifycode'])."'><strong>Verify Email</strong></a>"
            );
            if ($this->mailer->sendMail(['to'=>$data['email'],'subject'=>'Verify Email/Changes','body'=>$body]) !== true)
                throw new Exception("Failed to send verification mail.");            
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
     * Verifies proper processing of a newly created account given the verification code of the account
     * @param String $code The verification code
     */
    public function verifyAccount ($code) {
        try {
            $data = hash('sha256', $code);
            $pntr = $this->dbh->prepare('SELECT * FROM user_accts_holding WHERE verify_code = ?');
            if (!$pntr->execute([$code])) throw new Exception(print_r($pntr->errorInfo(),true));
            $user = $pntr->fetchAll(PDO::FETCH_ASSOC);
            if (count($user) != 1) {
                throw new Exception("Database corrupt, wrong number of results returned.");
            }
            elseif (!hash_equals($user[0]['verify_code'],$code)) {
                throw new Exception("Hashes not equal, possible hash timing exploit.");
            }
            elseif (!$this->copyUserData($user[0])) {
                throw new Exception("Failed to copy the users data.");
            }
            else {
                return true;
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
    }

    private function copyUserData (Array $data) {
        $pid = uniqid();
        $sql = [
            'INSERT INTO user_accts VALUES (:id,:uname,:pword,:first,:last,:alt,:date,:code,:pid)',
            'INSERT INTO profiles (id, first, last, email, alt_email) VALUES (:pid,:fname,:lname,:mail,:alt)',
            'DELETE FROM user_accts_holding WHERE id = :id'
        ];
        $inserts = [
            [
                ':id'=>$data['id'],':uname'=>$data['username'],':pword'=>$data['password'],':first'=>$data['firstname'],
                ':last'=>$data['lastname'],':alt'=>$data['alt_email'],':date'=>$data['_date'],':code'=>$data['verify_code'],
                ':pid'=>$pid
            ],
            [
                ':pid'=>$pid,':fname'=>$data['firstname'],':lname'=>$data['lastname'],':mail'=>$data['username'],':alt'=>$data['alt_email']
            ],
            [':id'=>$data['id']]
        ];
        try {
            $this->dbh->beginTransaction();
            for($cnt = 0; $cnt < count($sql); $cnt++) {
                $pntr = $this->dbh->prepare($sql[$cnt]);
                if (!$pntr->execute($inserts[$cnt])) {
                    throw new Exception(print_r($pntr->errorInfo(),true));
                }
            }
            $this->dbh->commit();
            $this->emailAdmin($data['id']);
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

    private function emailAdmin ($uid) {
        $sql = 
            'SELECT DISTINCT username FROM user_accts WHERE id in (
                SELECT uid FROM user_roles WHERE rid = (
                    SELECT id FROM roles WHERE name = ?
                )
            )';
        $pntr = $this->dbh->prepare($sql);
        $pntr->execute(['Administrator']);
        $body = $this->mailer->wrapInTemplate(
            "adminnewacct.html",
            "<a href='{$this->config['application-root']}/admin/users?action=view&uid={$uid}'><strong>Click Here</strong></a>"
        );
        foreach($pntr->fetchAll(PDO::FETCH_ASSOC) as $res) {
            $this->mailer->sendMail(['to'=>$res['username'],'subject'=>'New Account Created','body'=>$body]);
        }
        return true;
    }

    public function deleteUserAccount ($uid) {
        //Get the account info
        $sql = 'SELECT * FROM user_accts WHERE id = ?';
        $this->dbh->beginTransaction();
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$uid])) throw new Exception(print_r($pntr->errorInfo(),true));
            $account = $pntr->fetchAll(PDO::FETCH_ASSOC)[0];
        }
        catch (Exception $e) {
            $this->dbh->rollBack();
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
        //remove profile
        $sql = 'DELETE FROM profiles WHERE id = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$account['pid']])) throw new Exception(print_r($pntr->errorInfo(),true));
        }
        catch (Exception $e) {
            $this->dbh->rollBack();
            trigger_erro($e->getMessage(),E_USER_WARNING);
            return false;
        }
        //remove any roles referencing the user
        $sql = 'DELETE FROM user_roles WHERE uid = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$account['id']])) throw new Exception(print_r($pntr->errorInfo(),true));
        }
        catch (Exception $e) {
            $this->dbh->rollBack();
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
        //remove any notifications referencing the user
        $sql = 'DELETE FROM notify WHERE uid = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$account['id']])) throw new Exception(print_r($pntr->errorInfo(),true));
        }
        catch (Exception $e) {
            $this->dbh->rollBack();
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
        //finally remove the user account
        $sql = 'DELETE FROM user_accts WHERE id = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$account['id']])) throw new Exception(print_r($pntr->errorInfo(),true));
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
     * Updates a users application theme
     * @param String $theme The theme file name
     * @param String $pid The account profile ID to update.
     * @return Boolean True on success, false otherwise.
     */
    public function updateAccountTheme ($theme,$pid) {
        $sql = 'UPDATE profiles SET theme = :theme WHERE id = :pid';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([':pid'=>$pid,':theme'=>$theme])) throw new Exception(print_r($pntr->errorInfo(),true));
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Updates a users profile date display format
     * @param String $format A date format string, in the same form used by the PHP `date()` function
     * @param String $pid The users Profile id (pid);
     * @return Boolean True on success, false otherwise
     */
    public function updateAccountDateFormat ($format, $pid) {
        $sql = 'UPDATE profiles SET date_display = :format WHERE id = :pid';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([':format'=>$format,':pid'=>$pid])) throw new Exception(print_r($pntr->errorInfo(),true));
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Updates an existing user profile
     * @param Array $data The users profile data to update
     * in the form ['pid'=>string,'first'=>string,'middle'=>string,'last'=>string,'other'=>string,
     * 'address'=>string,'address_other'=>string,'city'=>string,'state_prov'=>string,'postal_code'=>string,
     * 'home_phone'=>string,'cell_phone'=>string,'alt_phone'=>string,'e_contact_name'=>string,'e_contact_relation'=>string,
     * 'e_contact_number'=>string]
     * @return Boolean True on success, false otherwise
     */
    public function updateAccountProfile (Array $data) {
        $sql = 
            'UPDATE profiles SET
            first = :first,
            middle = :middle,
            last = :last,
            other = :other,
            address = :address,
            address_other = :address_other,
            city = :city,
            state_prov = :state_prov,
            postal_code = :postal_code,
            home_phone = :home_phone,
            cell_phone = :cell_phone,
            alt_phone = :alt_phone,
            e_contact_name = :e_contact_name,
            e_contact_relation = :e_contact_relation,
            e_contact_number = :e_contact_number
            WHERE id = :pid';
        $insert = [
            ':pid'=>$data['pid'],
            ':first'=>$data['first'],
            ':middle'=>$data['middle'],
            ':last'=>$data['last'],
            ':other'=>$data['other'],
            ':address'=>$data['address'],
            ':address_other'=>$data['address_other'],
            ':city'=>$data['city'],
            ':state_prov'=>$data['state_prov'],
            ':postal_code'=>$data['postal_code'],
            ':home_phone'=>$data['home_phone'],
            ':cell_phone'=>$data['cell_phone'],
            ':alt_phone'=>$data['alt_phone'],
            ':e_contact_name'=>$data['e_contact_name'],
            ':e_contact_relation'=>$data['e_contact_relation'],
            ':e_contact_number'=>$data['e_contact_number']
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
     * Returns an array of results of pending users
     * @return array An array in the form ['id'=>string,'email'=>string,'firstname'=>string,'lastname'=>string,'alt_email'=>string,'date'=>timestamp,'code'=>string],
     * or false on error
     */
    public function getPendingUsers () {
        $sql = 'SELECT id,username AS "email",firstname,lastname,alt_email,_date as "date",verify_code as "code" FROM user_accts_holding';
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
     * Removes a pending user from pending status
     * @param string $id The ID of the request to cancel
     * @return boolean True on success, false otheriwse
     */
    public function cancelPendingUser ($id) {
        $sql = 'DELETE FROM user_accts_holding WHERE id = ?';
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