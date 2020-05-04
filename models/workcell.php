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
 * WorkCell Class Model
 * 
 * @package UData\Models\Database\Postgres
 * @link https://kc9eye.github.io/udata/UData_Database_Structure.html
 * @author Paul W. Lane
 * @license GPLv2
 */
class WorkCell {
    protected $dbh;
    protected $rawCellData;

    /**
     * @var String $ID The cell's unique identifier
     */
    public $ID;
    /**
     * @var String $ProductKey The key of the porduct the cell is associated with
     */
    public $ProductKey;
    /**
     * @var String $Product The Decsrcription of the product the cell is associated with
     */
    public $Product;
    /**
     * @var String $Name The name of the cell
     */
    public $Name;
    /**
     * @var String $Author The name of the creator of the cell
     */
    public $Author;
    /**
     * @var String $Date The timestamp of when the cell was created
     */
    public $Date;
    /**
     * @var String $FTC The quality control number for the cell currently (First Time Capablility)
     */
    public $FTC;
    /**
     * @var Array $QCP An array containing the Quality Checkpoints associated with the cell
     */
    public $QCP;
    /**
     * @var Array $Material An array containing the Materials from the product BOM associated with the cell
     */
    public $Material;
    /**
     * @var Array $Tools An array containing the Tools associated with the cell
     */
    public $Tools;
    /**
     * @var Array $Safety An Array containing the safety assessment data associated with the cell.
     */
    public $Safety;

    /**
     * Class constructor
     * @param PDO $dbh The database handle
     * @param String The ID of the cell to spool data for
     */
    public function __construct (PDO $dbh, $cellid) {
        $this->dbh = $dbh;
        $this->ID = $cellid;
        $this->rawCellData = $this->getCellData();
    }

    private function getCellData () {
        $sql = 'SELECT * FROM work_cell WHERE id = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$this->ID])) throw new Exception("Select failed: {$sql}");
            $this->rawCellData = $pntr->fetchAll(PDO::FETCH_ASSOC)[0];
            $this->ProductKey = $this->rawCellData['prokey'];
            $this->Name = $this->rawCellData['cell_name'];
            $this->Date = $this->rawCellData['_date'];
            $this->FTC = $this->getCellStatistics();
            $this->Product = $this->getProductFromKey();
            $this->Author = $this->getCellAuthor();
            $this->Material = $this->getCellMaterial();
            $this->Tools = $this->getCellTools();
            $this->Safety = $this->getCellSafety();
            $this->SafetyReview = $this->getSafetyReviewDoc();
            $this->QCP = $this->getCheckPoints();
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

    private function getProductFromKey () {
        $sql = "SELECT description FROM products WHERE product_key = ?";
        $pntr = $this->dbh->prepare($sql);
        if (!$pntr->execute([$this->rawCellData['prokey']])) throw new Exception("Select failed: {$sql}");
        return $pntr->fetch(PDO::FETCH_ASSOC)['description'];
    }

    private function getCellAuthor () {
        $sql = "SELECT firstname||' '||lastname as author FROM user_accts WHERE id = ?";
        $pntr = $this->dbh->prepare($sql);
        if (!$pntr->execute([$this->rawCellData['uid']])) throw new Exception("Select failed: {$sql}");
        return $pntr->fetch(PDO::FETCH_ASSOC)['author'];
    }

    private function getCellMaterial () {
        $sql = '
            SELECT 
                a.id,
                (SELECT number FROM material WHERE id = (SELECT partid FROM bom WHERE id = a.bomid)),
                (SELECT description FROM material WHERE id = (SELECT partid FROM bom WHERE id = a.bomid)),
                a.qty,
                a.label
            FROM cell_material AS a
            WHERE a.cellid = ?';
        $pntr = $this->dbh->prepare($sql);
        if (!$pntr->execute([$this->ID])) throw new Exception("Select failed: {$sql}");
        return $pntr->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getCellTools () {
        $sql = 
            "SELECT
            id, 
            (SELECT description FROM tools WHERE id = a.toolid),
            (SELECT category FROM tools WHERE id = a.toolid),
            qty,uid,_date,torque_val,torque_units,torque_label 
            FROM cell_tooling as a 
            WHERE cellid = ? ORDER BY category ASC";
        $pntr = $this->dbh->prepare($sql);
        if (!$pntr->execute([$this->ID])) throw new Exception(print_r($pntr->errorInfo(),true));
        return $pntr->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getCellSafety () {
        $sql = "SELECT id, state, _date, body,
        (select firstname||' '||lastname from user_accts where id = a.oid) as author,
        name,(select firstname||' '||lastname from user_accts where id = a.aid) as approver,
        a_date FROM documents as a WHERE name = ? AND state = 'approved'";
        $pntr = $this->dbh->prepare($sql);
        if (!$pntr->execute([$this->ID])) throw new Exception("Select failed: {$sql}");
        if (!empty(($rtn = $pntr->fetchAll(PDO::FETCH_ASSOC)))) {
            return $rtn[0];
        }
        else {
            return array();
        }
    }

    private function getCellStatistics () {
        if ($this->rawCellData['control'] == 0) {
            return 100;
        }
        $ftc = ($this->rawCellData['quality']/$this->rawCellData['control'])*100;
        return round($ftc,2);
    }

    private function getCheckPoints () {
        $sql = 'SELECT * FROM quality_control WHERE cellid = ?';
        $pntr = $this->dbh->prepare($sql);
        if (!$pntr->execute([$this->ID])) throw new Exception("Select failed: {$sql}");
        return $pntr->fetchAll(PDO::FETCH_ASSOC);
    }

        /**
     * Returns an array of data for the given assessment edit seeking approval
     * 
     * @param String $docname This corresponds with the work cell ID.
     * @return Array
     */
    private function getSafetyReviewDoc () {
        $sql = 'SELECT * FROM documents WHERE name = :name AND state = :state';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([':name'=>$this->ID,':state'=>DocumentViewer::SEEKING])) throw new Exception("Select failed: {$sql}");
            if (!empty(($rtn = $pntr->fetchAll(PDO::FETCH_ASSOC)))) {
                return $rtn[0];
            }
            else {
                return null;
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
}