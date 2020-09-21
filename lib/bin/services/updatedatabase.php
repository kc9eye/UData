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
class UpdateDatabase implements Service {

    const UPDATE_FILE_NAME = \INCLUDE_ROOT.'/var/dbupdate.php';
    const DB_VERSION_FILE = \INCLUDE_ROOT.'/etc/db_version.php';

    private $server;

    public function __construct (Instance $server) {
        $this->server = $server;
    }

    public function kill () {
        return true;
    }

    public function cronjob () {
        return false;
    }

    public function run () {
        if (file_exists(self::DB_VERSION_FILE)) {
            include(self::DB_VERSION_FILE);
        }
        else {
            //This is the first run of the applicaiton, build the database
            $current_version = 0;            
        }
        if (file_exists(self::UPDATE_FILE_NAME)) {
            include(self::UPDATE_FILE_NAME);
        }
        else {
            include(\INCLUDE_ROOT.'/install/requiredobjects.php');
        }
        if ($update_version > $current_version) {
            echo "Database being updated from version {$current_version} to {$update_version}, please wait...";
            if (!empty($sql)) {
                $this->server->pdo->beginTransaction();
                foreach($sql as $statement) {
                    try {
                        $pntr = $this->server->pdo->prepare($statement);
                        if (!$pntr->execute()) throw new Exception("{$statement} failed: ".print_r($pntr->errorInfo(),true));
                    }
                    catch (Exception $e) {
                        echo "update failed!";
                        $this->server->pdo->rollBack();
                        trigger_error($e->getMessage(),E_USER_WARNING);
                        return false;
                    }
                }
                $this->server->pdo->commit();
            }
            if (!empty($inserts)) {
                foreach($inserts as $sql => $data) {
                    try {
                        $pntr = $this->server->pdo->prepare($sql);
                        $this->server->pdo->beginTransaction();
                        foreach($data as $insert) {
                            if (!$pntr->execute($insert)) throw new Exception("{$sql} failed: ".print_r($pntr->errorInfo(),true));
                        }
                        $this->server->pdo->commit();
                    }
                    catch (Exception $e) {
                        $this->server->pdo->rollBack();
                        echo "Update failed!";
                        trigger_error($e->getMessage(),E_USER_WARNING);
                        return false;
                    }
                }
            }
            try {
                $fh = fopen(self::DB_VERSION_FILE, 'w');
                $result = fwrite($fh,'<?php $current_version = '.$update_version.';');
                if ($result === false) throw new Exception("Update succeeded, but file write failed");
                fclose($fh);
                echo " Succeeded!";
                return true;
            }
            catch (Exception $e) {
                echo " Failed!";
                trigger_error($e->getMessage(),E_USER_WARNING);
                return false;
            }
        }
        else {
            return true;
        }
    }
}