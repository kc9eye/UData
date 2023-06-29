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
class Materials {

    const PDN_TYPE = 'PDN';
    const PDIH_TYPE = 'PDIH';
    const MATERIAL_NOT_FOUND = false;

    private $dbh;

    public $addedDiscrepancyID;

    public function __construct (PDO $dbh) {
        $this->dbh = $dbh;
    }

    /**
     * Adds the material to the cells inventory
     * @param Array $data In the form, `['number'=>string,'qty'=>string,'cellid'=>string,'uid'=>string,'prokey'=>string]`
     * @return Boolean True on success, false otherwise.
     */
    public function addCellMaterial ($data) {
        try {
            if (!$this->verifyMaterial($data['number'])) 
                throw new Exception("Material does not exist, consider running check before submitting.");
            if (!$this->verifyOnBOM($data['number'],$data['prokey'])) 
                throw new Exception("Material is not on the BOM for this product, consider running checks before submitting.");
            if (!$this->verifyBOMQty($data['number'],$data['prokey'],$data['qty'])) 
                throw new Exception("Material quantity exceeds the BOM limit. Consider running checks before submitting.");
            
            $sql = '
            INSERT INTO cell_material (id,cellid,bomid,qty,uid,_date,label)
            select :id,:cellid,
                (select id from bom where partid = (
                    select id from material where number = :number
                    )
                 and prokey = :prokey
                ),
                :qty,
                :uid,
                now(),
                :label';

            $insert = [
                ':id'=>uniqid(),
                ':cellid'=>$data['cellid'],
                ':qty'=>$data['qty'],
                ':uid'=>$data['uid'],
                ':prokey'=>$data['prokey'],
                ':number'=>$data['number'],
                ':label'=>$data['label']
            ];
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute($insert)) throw new Exception("Insert failed: {$sql}");
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
     * Verifies whether a material exists in the given products BOM
     * 
     * @param String $number The material's part number.
     * @param String $prokey The products master key.
     * @return Boolean True on success, false otherwise.
     */
    public function verifyOnBOM ($number,$prokey) {
        $sql = 'SELECT * FROM bom WHERE partid = (SELECT id FROM material WHERE number = :num) AND prokey = :pkey';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([':num'=>$number,':pkey'=>$prokey])) return false;
            elseif (empty(($rtn = $pntr->fetchAll(PDO::FETCH_ASSOC)))) return false;
            else return true;
        }
        catch (PDOException $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage()<E_USER_WARNING);
            return false;
        }
    }

    /**
     * Verifies that the material is present in the database.
     * 
     * @param String $number The materials part number.
     * @return Boolean True on success, false otherwise.
     */
    public function verifyMaterial ($number) {
        $sql = 'SELECT * FROM material WHERE number = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$number])) return self::MATERIAL_NOT_FOUND;
            elseif (empty(($rtn = $pntr->fetchAll(PDO::FETCH_ASSOC)))) return false;
            else return true;
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
     * Verifies whether the quantity specified wouold exceed the BOM qty.
     * @param String $number The material part number
     * @param String $prokey The product master key
     * @return Boolean True if qty is NOT exceeded, false otherwise.
     */
    public function verifyBOMQty ($number,$prokey,$qty) {
        $sql = '
        select (
            select qty from bom where partid = (
                select id from material where number = :num
            )
            and prokey = :pkey
        )
        -
        (
            select coalesce(sum(qty),0)
            from cell_material
            where cellid in (
                select id from work_cell where prokey = :prokey
            )
            and bomid = (
                select id from bom where partid = (
                    select id from material where number = :number
                )
                and prokey = :prok
            )
        ) as "difference"
        ';
        try {
            $pntr = $this->dbh->prepare($sql);
            $data = [':num'=>$number,':number'=>$number,':pkey'=>$prokey,':prokey'=>$prokey,':prok'=>$prokey];
            if (!$pntr->execute($data)) throw new Exception("Select failed: {$sql}");
            if ($pntr->fetchAll(PDO::FETCH_ASSOC)[0]['difference'] >= $qty) return true;
            else return false;
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
     * Verifies the part number is not already on the cell list
     * @param String $cellid The cell's ID
     * @param String $number The material's part number
     * @param String $prokey The products master key
     * @return Boolean True if the number is not found, false otherwise
     */
    public function verifySingleNumber ($cellid,$number,$prokey) {
        $sql = '
            SELECT * FROM cell_material
            WHERE bomid = (
                SELECT id FROM bom where partid = (
                    SELECT id FROM material WHERE number = :num)
                AND prokey = :prokey
            )
            AND cellid = :cellid';
        try {
            $pntr = $this->dbh->prepare($sql);
            $data = [':cellid'=>$cellid,':num'=>$number,':prokey'=>$prokey];
            if (!$pntr->execute($data)) throw new Exception("Select failed: {$sql}");
            if (!empty($pntr->fetchAll(PDO::FETCH_ASSOC))) return false;
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
     * Retrieves cell material data by row
     * @param $id The row ID for the material
     * @return Array The data in indexed array form, false on error
     */
    public function getCellMaterialByID ($id) {
        $sql = 'SELECT *,
        (SELECT number FROM material WHERE id = (SELECT partid FROM bom WHERE id = a.bomid)),
        (SELECT description FROM material WHERE id = (SELECT partid FROM bom WHERE id = a.bomid))
        FROM cell_material as a 
        WHERE id = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$id])) throw new Exception("Select failed: {$sql}");
            if (!empty(($rtn = $pntr->fetchAll(PDO::FETCH_ASSOC)))) return $rtn[0];
            else return false;
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
     * Updates the quantity of a given row of cell material
     * @param Array $data The data array, should caontain at least `['rowid'=>string,'qty'=>float]`
     * @return Boolean True on success, false otherwise.
     */
    public function amendCellMaterialQty (Array $data) {
        if (empty($data['label'])) $data['label'] = "";
        $sql = 'UPDATE cell_material SET qty = :qty, label = :label WHERE id = :id';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([':qty'=>$data['qty'],':label'=>$data['label'],':id'=>$data['rowid']])) 
                throw new Exception("Update failed: {$sql}");
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
     * Reduces the inventory on hand for the given product
     * 
     * When called, inventory is reduced by the quantities given in 
     * the products BOM. Essentially, subtract the entire BOM of the 
     * product from any on hand quantities one time.
     * @param String $prokey The master product key
     */
    public function reduceInventory ($prokey) {
        trigger_error('Someone called this method?!',E_USER_NOTICE);
    }

    public function resetCellMaterialQty ($id) {
        $sql = 'UPDATE cell_material SET qty = 0 WHERE id = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$id])) throw new Exception("Update failed: {$sql}");
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
     * Removes a cell material entry from the table
     * @param String $id The row Id
     * @return Boolean True on success, false otheriwse
     */
    public function removeCellMaterial ($id) {
        $sql = 'DELETE FROM cell_material WHERE id = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$id])) throw new Exception("Delete falied: {$sql}");
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
     * Records a material discrepancy
     * @param Array $data The data array in the form 
     * `['prokey'=>string,'number'=>string,'qty'=>integer,'type'=>string,'uid'=>string,'description'=>string,'fid'=>string]`
     * @return Boolean true on success, otherwise false
     */
    public function addDiscrepancy (Array $data) {
        $sql = 'INSERT INTO discrepancies (id,prokey,partid,qty,discrepancy,fid,_date,uid,type)
                SELECT :did,:prokey,id,:qty,:dis,:fid,now(),:uid,:type FROM material WHERE number = :num';
        $insert = [
            ':did' => uniqid(),
            ':prokey' => $data['prokey'],
            ':qty' => (float)$data['qty'],
            ':dis' => $data['description'],
            ':fid' => $data['fid'],
            ':uid' => $data['uid'],
            ':type' => $data['type'],
            ':num' => $data['number']
        ];
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute($insert)) throw new Exception("Insert failed: {$sql}");
            $this->addedDiscrepancyID = $insert[':did'];
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
     * Amends an existsing discrepancy with additional notes.
     * @param Array $data The data to insert in the form `['amendum'=>string,'uid'=>string,'id'=>string]`
     * @return Boolean True on success, otherwise false.
     */
    public function amendDiscrepancy (Array $data) {
        $sql = 'UPDATE discrepancies SET notes = :notes, a_uid = :a_uid WHERE id = :id';
        try {
            $pntr = $this->dbh->prepare($sql);
            $insert = [':notes'=>$data['amendum'],':a_uid'=>$data['uid'],':id'=>$data['id']];
            if (!$pntr->execute($insert)) throw new Exception(print_r($pntr->errorInfo(),true));
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Searches for discrepancies matching a given pattern
     * @param String $pattern The pattern to search for, should be a `SearchStringFormater formated string
     * @return Array An array of rows that matched, false otherwise
     */
    public function searchDiscrepancies ($pattern) {
        $sql = "
            SELECT 
                id,
                (SELECT description FROM products WHERE product_key = a.prokey) as product,
                (SELECT number FROM material WHERE id = a.partid) as number,
                (SELECT description FROM material WHERE id = a.partid) as description,
                qty as quantity,
                _date as date,
                type
            FROM discrepancies as a
            WHERE (SELECT to_tsvector(description) FROM products WHERE product_key = a.prokey)
            ||':'||(SELECT search||':'||to_tsvector(number) FROM material WHERE id = a.partid)
            @@ to_tsquery(?) ORDER BY _date DESC";
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$pattern])) throw new Exception("Select failed: {$sql}");
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
     * Retrieves discrepancies by a given daterange
     * @param String $begin The beginning date, given in ISO format
     * @param String $end The ending date, given in ISO format
     * @param String $type One of either 'all'|'PDN'|'PDIH'
     * @return Array An array of results in the form 
     * ['id'=>string,'product'=>string,'number'=>string,'description'=>string,'quantity'=>float,'date'=>string,'type'=>string],
     * or false on error.
     */
    public function getDiscrepanciesByDateRange ($begin,$end,$type) {
        $sql = 
            "SELECT
                id,
                (SELECT description FROM products WHERE product_key = a.prokey) as product,
                (SELECT number FROM material WHERE id = a.partid) as number,
                (SELECT description FROM material WHERE id = a.partid) as description,
                qty as quantity,
                _date as date,
                type
            FROM discrepancies as a
            WHERE _date >= :begin
            AND _date <= :end";
        switch($type) {
            case 'PDIH': 
                $sql .= " AND type = :type";
                $insert = [':begin'=>$begin,':end'=>$end,':type'=>'PDIH'];
            break;
            case 'PDN': 
                $sql .= " AND type = :type";
                $insert = [':begin'=>$begin,':end'=>$end,':type'=>'PDN'];
            break;
            default:
                $insert = [':begin'=>$begin,':end'=>$end];
            break;
        }
        $sql .= " ORDER BY number";
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute($insert)) throw new Exception(print_r($pntr->errorInfo(),true));
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Returns the last 5 discrepancies added
     * @return Array In the form [['product'=>string,'number'=>string,'description'=>string,'quantity'=>string,'date'=>string,'type'=string],...]
     */
    public function getRecentDiscrepancies () {
        $sql = 
            "SELECT 
                id,
                (SELECT description FROM products WHERE product_key = a.prokey) as product,
                (SELECT number FROM material WHERE id = a.partid) as number,
                (SELECT description FROM material WHERE id = a.partid) as description,
                qty as quantity,
                _date as date,
                type
            FROM discrepancies as a
            ORDER BY date DESC
            LIMIT 5";
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
     * Searches material records for matching
     * @param String $search_string A database formated search string
     * @return Array An array of results, possibly empty, or false on error
     */
    public function searchMaterial ($search_string) {
        $sql = "SELECT * FROM material WHERE to_tsvector(number)||':'||search @@ to_tsquery(?) ORDER BY number";
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$search_string])) throw new Exception("Select failed: {$sql}");
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Inserts a new material record
     * @param Array $data The data array in the form `['uid'=>string,'number'=>string,'description'=>string,'uom'=>string,'cat'=>string]`
     * @return Boolean True on success, false otherwise.
     */
    public function addNewMaterial (Array $data) {
        $sql = 'INSERT INTO material (id,number,description,uom,uid,category) VALUES (:id,:num,:dis,:uom,:uid,:cat)';
        $insert = [
            ':id'=>uniqid(),
            ':num'=>$data['number'],
            ':dis'=>$data['description'],
            ':uom'=>(float)$data['uom'],
            ':cat'=>$data['cat'],
            ':uid'=>$data['uid']
        ];
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute($insert)) throw new Exception("Insert failed: {$sql}");
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
}