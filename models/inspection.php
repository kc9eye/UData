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
class Inspection {
    private $data;
    protected $dbh;

    public function __construct (PDO $dbh,$id) {
        $this->dbh = $dbh;
        $this->data = array();
        try {
            if (!$this->getInspection($id)) throw new Exception("Failed to retrieve inspection: {$id}");
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
        }
    }

    public function __set ($name, $value) {
        $this->data[$name] = $value;
    }

    public function __get ($name) {
        if (!empty($this->data[$name])) return $this->data[$name];
        else return null;
    }

    public function __isset ($name) {
        return isset($this->data[$name]);
    }

    public function __unset ($name) {
        unset($this->data[$name]);
    }

    private function getInspection ($id) {
        $e_sql = 'SELECT * FROM equipment WHERE id = ?';
        $p_sql = 'SELECT * FROM insp_forms WHERE eqid = ?';
        $i_sql = 
            "SELECT 
                (SELECT user_accts.firstname||' '||user_accts.lastname FROM user_accts WHERE id = a.uid) as inspector,
                _date,
                comments
            FROM inspections as a
            WHERE eqid = ?
            ORDER BY _date DESC";
        try {
            $e_pntr = $this->dbh->prepare($e_sql);
            $p_pntr = $this->dbh->prepare($p_sql);
            $i_pntr = $this->dbh->prepare($i_sql);
            if (!$e_pntr->execute([$id])) throw new Exception("Select failed: {$e_sql}");
            if (!$p_pntr->execute([$id])) throw new Exception("Select failed: {$p_sql}");
            if (!$i_pntr->execute([$id])) throw new Exception("Select failed: {$i_sql}");
            $equip = $e_pntr->fetchAll(PDO::FETCH_ASSOC);
            $this->EquipmentName = $equip[0]['description'];
            $this->EquipmentDetails = $equip;
            $this->InspectionPoints = $p_pntr->fetchAll(PDO::FETCH_ASSOC);
            $this->PastInspections = $i_pntr->fetchAll(PDO::FETCH_ASSOC);

            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }    
}