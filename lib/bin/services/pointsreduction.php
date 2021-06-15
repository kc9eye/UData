<?php
//File: pointsreduction.php
//
//About: License
//
//Copyright (C)Year Paul W. Lane <kc9eye@gmail.com>
//
//This program is free software; you can redistribute it and/or modify
//
//it under the terms of the GNU General Public License as published by
//
//the Free Software Foundation; version 2 of the License.
//
//This program is distributed in the hope that it will be useful,
//
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
//
//GNU General Public License for more details.
//
//You should have received a copy of the GNU General Public License along
//
//with this program; if not, write to the Free Software Foundation, Inc.
//
//51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

class pointsreduction {
    private $server;
    private $data;

    public function __construct (Instance $server) {
        $this->server = $server;
        $this->data = array();
    }

    public function cronjob () {
        return true;
    }

    public function kill () {
        return true;
    }

    public function run () {
        $sql = 
            "update missed_time set points = 0
            from employees
            where missed_time.eid = employees.id
            and missed_time.occ_date <= (current_date - interval '180 days')
            and employees.end_date is null";
        try {
            $pntr = $this->server->pdo->prepare($sql);
            if (!$pntr->execute()) throw new Exception(print_r($pntr->errorInfo(),true));
            return true;
        }
        catch (Exception $e) {
            $this->server->pdo->rollBack();
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }
}