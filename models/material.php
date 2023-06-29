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
/**
 * Material object class
 * @param PDO $dbh The application database PDO handle
 * @param String $number Optional part number to set
 * @param String $id Optional part id to set
 * @return Material The Material object
 */
class Material {
    private $data;

    public function __construct (PDO $dbh, $number = null, $id = null) {
        if (is_null($number) && is_null($id)) throw new Exception("Material::__construct() expects at least one parameter, NULL given.");
        $this->data = [];
        $this->number = $number;
        $this->id = $id;
        $this->dbh = $dbh;
        if (!is_null($number) && is_null($id)) $this->getMaterialDataByNumber();
        elseif (!is_null($id) && is_null($number)) $this->getMaterialDataByID();
        elseif (!is_null($id) && !is_null($number)) $this->getMaterialDataByID();
    }

    public function __set ($name,$value) {
        $this->data[$name] = $value;
    }

    public function __get ($name) {
        try {
            if (!empty($this->data[$name]))
                return $this->data[$name];
            else
                return null;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    public function __isset ($name) {
        return isset($this->data[$name]);
    }

    public function __unset ($name) {
        unset($this->data[$name]);
    }

    private function getMaterialDataByNumber () {
        try {
            $sql = 'SELECT * FROM material WHERE number = ?';
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$this->number])) throw new Exception("Select failed: {$sql}");
            if (!empty(($rtn = $pntr->fetchAll(PDO::FETCH_ASSOC)))) $this->setData(['material'=>$rtn[0]]);
            foreach(['discrepancies','inventory'] as $store) {
                $sql = "SELECT * FROM {$store} WHERE partid = ?";
                $pntr = $this->dbh->prepare($sql);
                if (!$pntr->execute([$this->material['id']])) $this->setData([$store=>NULL]);
                if (!empty(($rtn = $pntr->fetchAll(PDO::FETCH_ASSOC)))) $this->setData([$store=>$rtn]);
                else $this->setData([$store=>NULL]);
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

    private function getMaterialDataByID () {
        try {
            $sql = 'SELECT * FROM material WHERE id = ?';
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$this->id])) throw new Exception("Select failed: {$sql}");
            if (!empty(($rtn = $pntr->fetchAll(PDO::FETCH_ASSOC)))) $this->setData(['material'=>$rtn[0]]);
            foreach(['discrepancies','inventory'] as $store) {
                $sql = "SELECT * FROM {$store} WHERE partid = ?";
                $pntr = $this->dbh->prepare($sql);
                if (!$pntr->execute([$this->material['id']])) $this->setData([$store=>NULL]);
                if (!empty(($rtn = $pntr->fetchAll(PDO::FETCH_ASSOC)))) $this->setData([$store=>$rtn]);
                else $this->setData([$store=>NULL]);
            }
            $this->setData(['workcells'=>$this->getWorkCellsFromMaterial($this->id)]);
        }
        catch (PDOException $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            
        }
    }

    private function getWorkCellsFromMaterial ($partid) {
        $sql = 
        'SELECT
            a.id as id, 
            (SELECT  description FROM products WHERE product_key = a.prokey) AS product,
            a.cell_name AS work_cell,
            cell_material.qty AS qty
        FROM work_cell AS a
        INNER JOIN cell_material ON cell_material.cellid = a.id
        INNER JOIN bom ON bom.id = cell_material.bomid
        INNER JOIN products ON products.product_key = bom.prokey
        WHERE bom.partid = ?
        AND products.active IS TRUE
        ORDER BY product ASC';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$partid])) throw new Exception("Select failed: {$sql}; for partid: {$partid}");
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (PDOException $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return null;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return null;
        }
    }

    private function setData (Array $data) {
        foreach($data as $index=>$value) {
            $this->$index = $value;
        }
    }
}