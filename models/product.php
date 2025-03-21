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
 * Product Class Model
 *
 * @package UData\Models\Database\Postgres
 * @link https://kc9eye.github.io/udata/UData_Database_Structure.html
 * @author Paul W. Lane
 * @license GPLv2
 */
class Product {
    private $dbh;

    /**
     * @var String $beginDate Optional ISO8601 beginning date range
     */
    public $beginDate;
    /**
     * @var String $endDate Optional ISO8601 ending date range
     */
    public $endDate;
    /**
     * @var String $pKey The products master key
     */
    public $pKey;
    /**
     * @var String $pDescription The products description
     */
    public $pDescription;
    /**
     * @var String $pState The products production state
     */
    public $pState;
    /**
     * @var String $pCreator The products creator
     */
    public $pCreator;
    /**
     * @var String $pCreateDate The date the creator created the product
     */
    public $pCreateDate;
    /**
     * @var Array $pQualityControl Array of quality contorl points for the product
     */
    public $pQualityControl;
    /**
     * @var Array $pLog Array containing the production log of the product
     */
    public $pLog;
    /**
     * @var Array $pStats Array of statistics for the products production
     */
    public $pStats;
    /**
     * @var Array $pBOM Array containing the products bill of materials
     */
    public $pBOM;

    /**
     * @var Array The products current work cells list
     */
    public $pWorkCells;

    /**
     * Class constructor
     * @param PDO $dbh The database handle
     * @param String $prokey The products master key
     * @param String $beginDate Optional beginning of a date range
     * @param String $endDate Optional ending of a date range
     */
    public function __construct (PDO $dbh, $prokey, $beginDate = null, $endDate = null) {
        $this->dbh = $dbh;
        $this->pKey = $prokey;
        $this->beginDate = $beginDate;
        $this->endDate = $endDate;
        $this->getProduct();
        $this->getBOM();
        $this->calcProductStatistics();
        $this->setWorkCellData();
    }

    private function getProduct () {
        $sql = 'SELECT * FROM products WHERE product_key = ?';
        $pntr = $this->dbh->prepare($sql);
        if (!$pntr->execute([$this->pKey])) throw new Exception("Select failed: {$sql}");
        $p = $pntr->fetchAll(PDO::FETCH_ASSOC)[0];
        $this->pDescription = $p['description'];
        $this->pState = ($p['active'] == 'true') ? 'Active' : 'Not Active';
        $this->pCreateDate = $p['_date'];

        $sql = "SELECT firstname||' '||lastname as name FROM user_accts WHERE id = ?";
        $pntr = $this->dbh->prepare($sql);
        if (!$pntr->execute([$p['uid']])) throw new Exception("Select failed: {$sql}");
        $this->pCreator = $pntr->fetch(PDO::FETCH_ASSOC)['name'];

        $sql = 'SELECT * FROM quality_control WHERE prokey = ?';
        $pntr = $this->dbh->prepare($sql);
        if (!$pntr->execute([$this->pKey])) throw new Exception("Select failed: {$sql}");
        $this->pQualityControl = $pntr->fetchAll(PDO::FETCH_ASSOC);

        if (is_null($this->beginDate) && is_null($this->endDate)) {
            $sql = 'SELECT *,(SELECT firstname||\' \'||lastname as "inspector" FROM user_accts WHERE id = a.uid),
            (select first||\' \'||last as driver from employees inner join profiles on profiles.id = employees.pid
            where employees.id = a.test_driver_id)
            FROM production_log AS a WHERE prokey = ? ORDER BY sequence_number DESC';
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$this->pKey])) throw new Exception("Select failed: {$sql}");
            $this->pLog = $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        else {
            $sql = 'SELECT *,(SELECT firstname||\' \'||lastname as "inspector" FROM user_accts WHERE id = a.uid),
            (select first||\' \'||last as driver from employees inner join profiles on profiles.id = employees.pid
            where employees.id = a.test_driver_id)
            FROM production_log as a WHERE prokey = :key AND date_trunc(\'day\',_date) BETWEEN :begin AND :end ORDER BY sequence_number DESC';
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([':key'=>$this->pKey,':begin'=>$this->beginDate,':end'=>$this->endDate])) {
                 throw new Exception("Select failed: {$sql}");
            }
            $this->pLog = $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    private function calcProductStatistics () {
        if (is_null($this->beginDate) && is_null($this->endDate)) {
            $sql = [
                'total_count' => "SELECT count(*) as \"number\" FROM production_log WHERE prokey = :prokey",
                'total_ftc' => "SELECT avg(CAST (ftc as float)) as \"number\" FROM production_log WHERE prokey = :prokey"
            ];
            $execute = [':prokey'=>$this->pKey];
            foreach($sql as $i=>$v) {
                $pntr = $this->dbh->prepare($v);
                if (!$pntr->execute($execute)) throw new Exception("SQL Failed: {$v}");
                $number = $pntr->fetch(PDO::FETCH_ASSOC)['number'];
                $number = ($number == null) ? 0.000 : $number;
                $this->pStats[$i] = round($number,2,PHP_ROUND_HALF_UP);
            }
        }
        else {
            $sql = [
                'total_count' => "SELECT count(*) as \"number\" FROM production_log WHERE prokey = :prokey AND date_trunc('day',_date) BETWEEN :begin AND :end",
                'total_ftc' => "SELECT avg(CAST (ftc as float)) as \"number\" FROM production_log WHERE prokey = :prokey AND date_trunc('day',_date) BETWEEN :begin AND :end"
            ];
            $execute = [':prokey'=>$this->pKey,':begin'=>$this->beginDate,':end'=>$this->endDate];
            foreach($sql as $i=>$v) {
                $pntr = $this->dbh->prepare($v);
                if (!$pntr->execute($execute)) throw new Exception("SQL Failed: {$v}");
                $number = $pntr->fetch(PDO::FETCH_ASSOC)['number'];
                $number = ($number == null) ? 0.000 : $number;
                $this->pStats[$i] = round($number,2,PHP_ROUND_HALF_UP);
            }
        }
        $sql['today_count'] = "SELECT count(*) as \"number\" FROM production_log WHERE prokey = :prokey AND date_trunc('day', _date) = CURRENT_DATE";
        $pntr = $this->dbh->prepare($sql['today_count']);
        if (!$pntr->execute([$this->pKey])) throw new Exception("SQL Failed: {$sql['today_count']}");
        $number = $pntr->fetch(PDO::FETCH_ASSOC)['number'];
        $number = ($number == null) ? 0.000 : $number;
        $this->pStats['today_count'] = round($number,2,PHP_ROUND_HALF_UP);
    }

    private function getBOM () {
        $sql = 'SELECT count(*) FROM bom WHERE prokey = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$this->pKey])) throw new Exception("Select failed: {$sql}");
            if ($pntr->fetch(PDO::FETCH_ASSOC)['count'] == 0) {
                $this->pBOM = null;
            }
            else {
                $sql =
                    'SELECT
                        id,
                        (SELECT number FROM material WHERE id = a.partid),
                        (SELECT description FROM material WHERE id = a.partid),
                        qty
                    FROM bom as a
                    WHERE prokey = :key ORDER BY number ASC';
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$this->pKey])) throw new Exception("Select failed: {$sql}");
            $this->pBOM = $pntr->fetchAll(PDO::FETCH_ASSOC);
            }
        }
        catch (PDOException $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            $this->pBOM = null;
        }
    }

    private function setWorkCellData () {
        $sql = 'SELECT * FROM work_cell WHERE prokey = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$this->getProductKey()])) throw new Exception(print_r($pntr->errorInfo(),true));
            $this->pWorkCells = $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            $this->pWorkCells = null;
        }
    }

    /**
     * Returns the products master product key
     * @return String Prokey
     */
    public function getProductKey () {
        return $this->pKey;
    }

    /**
     * Returns the products description
     * @return String Description
     */
    public function getProductDescription () {
        return $this->pDescription;
    }

    /**
     * Returns the products current state
     * @return String State
     */
    public function getProductState () {
        return $this->pState;
    }

    /**
     * Returns the product creator
     * @return String Creator
     */
    public function getProductCreator () {
        return $this->pCreator;
    }

    /**
     * Returns the date the product was created
     * @return String Date
     */
    public function getCreationDate () {
        return $this->pCreateDate;
    }

    /**
     * Returns an array of inspection points for the product
     * @return Array inspection points
     */
    public function getInspectionPoints () {
        return $this->pQualityControl;
    }

    /**
     * Returns an array of the products production log
     * @return Array production log
     */
    public function getProductionLog () {
        return $this->pLog;
    }

    /**
     * Returns an array of production statistics
     * @return Array production stats
     */
    public function getProductionStats () {
        return $this->pStats;
    }

    /**
     * Returns an array of the bill of materials for the product
     * @return Array BOM
     */
    public function getProductBOM () {
        return $this->pBOM;
    }

    /**
     * Returns an array of work cell data for the product
     * @return Array Work Cells
     */
    public function getWorkCells () {
        return $this->pWorkCells;
    }
}