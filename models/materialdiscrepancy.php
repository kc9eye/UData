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
class MaterialDiscrepancy {
    private $dbh;
    public $data;

    public function __construct (PDO $dbh,$id) {
        $this->dbh = $dbh;
        $this->data = array();
        $this->getDiscrepancy($id);
    }

    public function getDiscrepancy ($id) {
        $sql = "
            select 
                id,
                prokey,
                (select description from products where product_key = a.prokey) as product,
                partid,
                (select number from material where id = a.partid) as number,
                (select description from material where id = a.partid) as description,
                qty as quantity,
                discrepancy,
                (select file from file_index where id = a.fid) as file,
                _date as date,
                (select firstname||' '||lastname from user_accts where id = a.uid) as author,
                type as type,
                notes as notes,
                (select firstname||' '||lastname from user_accts where id = a.a_uid) as amender
            from discrepancies as a
            where id = ?";
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$id])) throw new Exception("Select failed: {$sql}");
            if (!empty(($results = $pntr->fetchAll(PDO::FETCH_ASSOC)[0]))) {
                foreach($results as $name => $value) {
                    $this->$name = $value;
                }
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

    public function __set ($name, $value) {
        $this->data[$name] = $value;
    }

    public function __unset ($name) {
        unset($this->data[$name]);
    }

    public function __get ($name) {
        if (!empty($this->data[$name])) return $this->data[$name];
        else return null;
    }

    public function __isset ($name) {
        return isset($this->data[$name]);
    }

}