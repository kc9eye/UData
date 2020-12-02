<?php
/**
 * Copyright (C) 2020  Paul W. Lane <kc9eye@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */
class ShopSupplies {
    protected $dbh;

    public function __construct(PDO $dbh) {
        $this->dbh = $dbh;
    }

    public function getAvailablePPE() {
        try {
            $pntr = $this->dbh->query('select * from ppe');
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
        }
    }

    public function addNewPPE (Array $data) {
        $sql = 'insert into ppe values (:id,:desc,:vendor,:price,:uid,now())';
        try {
            $pntr = $this->dbh->prepare($sql);
            $insert = [
                ':id'=>uniqid(),
                ':desc'=>$data['description'],
                ':vendor'=>$data['vendor'],
                ':price'=>$data['cost'],
                ':uid'=>$data['uid']
            ];
            if (!$pntr->execute($insert)) throw new Exception(print_r($pntr->errorInfo(),true));
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    public function getPPEItem ($id) {
        $sql = 'select * from ppe where id = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$id])) throw new Exception(print_r($pntr->errorInfo(),true));
            return $pntr->fetch(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    public function updatePPEItem (Array $data) {
        $sql = "update ppe set description = :desc, vendor = :vendor, unit_price = :cost, uid = :uid where id = :id";
        $insert = [
            ':desc'=>$data['description'],
            ':vendor'=>$data['vendor'],
            ':cost'=>$data['cost'],
            ':id'=>$data['id'],
            ':uid'=>$data['uid'],
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

    public function deletePPEItem ($id) {
        $sql = 'delete from ppe where id = ?';
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

    public function assignPPE (Array $data) {
        $sql = 'insert into ppe_usage values (:id,:eid,:ppeid,:returned,:uid,now(),(select unit_price from ppe where id = :ppid))';
        $insert = [
            ':id'=>uniqid(),
            ':eid'=>$data['eid'],
            ':ppeid'=>$data['ppeid'],
            ':ppid'=>$data['ppeid'],
            ':returned'=>$data['returned'],
            ':uid'=>$data['uid']
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
    
    public function getAssignedPPE ($eid) {
        $sql = 
            'select ppe.description as ppe, ppe.unit_price as cost from ppe_usage
             inner join ppe on ppe_usage.ppeid = ppe.id
             where ppe_usage.eid  = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$eid])) throw new Exception(print_r($pntr->errorInfo(),true));
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    public function getAggregatePPE ($id) {
        $sql =
            "select 
                sum(cost) as \"Expense\", 
                count(*) as \"Issued\", 
                description as \"Item\",
                count(case when returned then 1 end) as \"Exchanged\"
            from 
            (
                select ppe_usage.cost as \"cost\", ppe.description as \"description\", ppe_usage.returned as \"returned\"
                from ppe_usage
                inner join ppe on ppe.id = ppe_usage.ppeid 
                where ppe_usage.eid = ?
            ) as foo
            group by description";
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$id])) throw Exception(print_r($pntr->errorInfo(),true));
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    public function getTimeframeReport(Array $data) {
        $sql = 
        "select 
                sum(cost) as \"Expense\", 
                count(*) as \"Issued\", 
                description as \"Item\",
                count(case when returned then 1 end) as \"Exchanged\"
            from 
            (
                select ppe_usage.cost as \"cost\", ppe.description as \"description\", ppe_usage.returned as \"returned\"
                from ppe_usage
                inner join ppe on ppe.id = ppe_usage.ppeid 
                where ppe_usage.gen_date >= :begin AND ppe_usage.gen_date <= :end
            ) as foo
            group by description";
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([':begin'=>$data['begin_date'],':end'=>$data['end_date']]))
                throw new Exception(print_r($pntr->errorInfo(),true));
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }
}