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
 * BillOfMaterials Class Model
 * 
 * @package UData\Models\Database\Postgres
 * @link https://kc9eye.github.io/udata/UData_Database_Structure.html
 * @author Paul W. Lane
 * @license GPLv2
 */
class BillOfMaterials {
    protected $dbh;
    protected $prokey;
    protected $uid;

    public function __construct (PDO $dbh) {
        $this->dbh = $dbh;
        $this->prokey = null;
        $this->uid = null;
    }

    public function importBOMCSV ($data) {
        $bom = [];
        $this->prokey = $data['prokey'];
        $this->uid = $data['uid'];
        try {
            $fh = fopen($data['file']->files[0]['tmp_name'],'r');
            while(($line = fgetcsv($fh)) !== false) {
                $line[0] = str_ireplace('-','',$line[0]);
                $line[1] = trim($line[1]);
                $line[2] = (float) $line[2];
                array_push($bom,$line);
            }
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
        if (!$this->addNewMaterialsFromArray($bom)) return false;
        if (!$this->addBOMArray($bom)) return false;
        if (!$this->removeDupes($this->prokey)) return false;
        return true;


    }

    public function addBOMArray (Array $materials) {
        $addMaterials = [];
        if (is_array($materials[0])) {
            foreach($materials as $part) {
                array_push($addMaterials,$part);
            }
        }
        else {
            array_push($addMaterials, $materials);
        }

        try {
            $sql = 
            'INSERT INTO bom (id,prokey,qty,uid,partid)
                SELECT :id, :prokey, :qty, :uid, id FROM material WHERE number = :num';
            $this->dbh->beginTransaction();
            $pntr = $this->dbh->prepare($sql);
            foreach($addMaterials as $part) {
                $data = [
                    ':id'=>uniqid(),
                    ':prokey'=>$this->prokey,
                    ':num'=>$part[0],
                    ':qty'=>(float) $part[2],
                    ':uid'=>$this->uid
                ];
                if (!$pntr->execute($data)) throw new Exception(print_r($pntr->errorInfo(),true));
            }
            $this->dbh->commit();
            return true;
        }
        catch (Exception $e) {
            $this->dbh->rollBack();
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    public function addNewMaterialsFromArray (Array $materials) {
        $sql = 'INSERT INTO material (id,number,description,uid) VALUES (:id,:number,:description,:uid)';
        $pntr = $this->dbh->prepare($sql);
        if (is_array($materials[0])) {
            foreach($materials as &$part) {
                if ($this->verifyMaterialNotEntered($part[0])) {
                    $insert = [
                        ':id'=>uniqid(),
                        ':number'=>$part[0],
                        ':description'=>$part[1],
                        ':uid'=>$this->uid
                    ];
                    try {
                        if (!$pntr->execute($insert)) throw new Exception(print_r($pntr->errorInfo(),true));
                    }
                    catch (Exception $e) {
                        trigger_error($e->getMessage(),E_USER_WARNING);
                        return false;
                    }
                }
            }
            return true;
        }
        elseif (is_string($materials[0]) && $this->verifyMaterialNotEntered($materials[0])) {
             $insert = [
                ':id'=>uniqid(),
                ':number'=>$materials[0],
                ':description'=>$materials[1],
                ':uid'=>$this->uid
            ];
            try {
                if (!$pntr->execute($insert)) throw new Exception(print_r($pntr->errorInfo(),true));
            }
            catch (Exception $e) {
                trigger_error($e->getMessage(),E_USER_WARNING);
                return false;
            }
        }
        return false;
    }

    /**
     * Retrieves a record by ID
     * @param String $id The records ID
     * @return Array The record in array form or false otherwise.
     */
    public function getmaterialByID ($id) {
        $sql = '
            SELECT *,
            (SELECT description as "productname" FROM products WHERE product_key = a.prokey),
            (SELECT number FROM material WHERE id = a.partid),
            (SELECT description FROM material WHERE id = a.partid)
            FROM bom as a
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
     * Updates an existing record on the BOM
     * @param Array $data The data in array form of `['id'=>string,'uid'=>string,'qty'=>float]`
     * @return Boolean True on succes, false otherwise
     */
    public function amendBOMByID (Array $data) {
        $sql = 'UPDATE bom SET qty = :qty, uid = :uid WHERE id = :id';
        try {
            $pntr = $this->dbh->prepare($sql);
            $update = [
                ':qty' =>(float)$data['qty'],
                ':uid' =>$data['uid'],
                ':id' =>$data['id']
            ];
            if (!$pntr->execute($update)) throw new Exception("Update failed: {$sql}");
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
     * Deletes the given record from the BOM
     * @param String $id The record ID
     * @return Boolean True on success, false on failure.
     */
    public function removeMaterialByID ($id) {
        $sql = 'DELETE FROM bom WHERE id = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$id])) throw new Exception("Delete failed: {$sql}");
            return true;
        }
        catch (PDOException $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            $this->dbh->rollBack();
            return false;
        }
    }

    /**
     * Adds an addendum to an existsing BOM
     * @param Array $data `['prokey'=>string,'number'=>string,'qty'=>float,'uid'=>string]`
     * @return Boolean true on success, false otherwise
     */
    public function addendumBOM ($data) {
        if ($this->verifyMaterialNotEntered($data['number'])) return false;
        $sql = 'INSERT INTO bom (id,prokey,partid,qty,uid)
            SELECT :id,:prokey,(SELECT id FROM material WHERE number = :num),:qty,:uid';
        try {
            $pntr = $this->dbh->prepare($sql);
            $insert = [
                ':id'=>uniqid(),
                ':prokey'=>$data['prokey'],
                ':num'=>$data['number'],
                ':qty'=>$data['qty'],
                ':uid'=>$data['uid']
            ];
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

    protected function verifyMaterialNotEntered ($number) {
        $sql = 'SELECT * FROM material WHERE number = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$number])) throw new Exception(print_r($pntr->errorInfo(),true));
            $result = empty($pntr->fetchAll(PDO::FETCH_COLUMN)) ? true : false;
            return $result;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);
        }
    }

    /**
     * Verifies that a given material number exists
     * @param String $number The material part number
     * @return Boolean True if the material exists, false otherwise
     */
    public function verifyMaterialExists ($number) {
        if ($this->verifyMaterialNotEntered($number)) return false;
        return true;
    }

    /**
     * Returns an array of rows of material.
     * 
     * Returns an array of rows from the BOM that are not
     * presently assigned to a work cell as material from the 
     * given master product key.
     * @param String $prokey The master product ID
     * @return Array An array of rows in the form :
     * `['id'=>string,'qty'=>string,'number'=>string,'description'=>string]`,
     * or false on error. This method may also return an empty array, indicating
     * there were no items found.
     */
    public function getUnassignedMaterial ($prokey) {
        $sql = 
        'SELECT 
            a.id AS id,
            a.qty AS qty,
            (SELECT number FROM material WHERE id = a.partid) AS number,
            (SELECT description FROM material WHERE id = a.partid) AS description
        FROM bom AS a
        WHERE
            prokey = :prokey
            AND
            id NOT IN (
                SELECT DISTINCT cell_material.bomid
                FROM cell_material
                INNER JOIN work_cell ON work_cell.id = cell_material.cellid
                WHERE work_cell.prokey = :prokey
            )';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([':prokey'=>$prokey])) throw new Exception(print_r($pntr->errorInfo(),true));
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Returns the qty on the bom and the quantity listed on work cells for the given product.
     * @param String $prokey The product key
     * @return Array In the form [['partid'=>string,'number'=>string,'description'=>string,'used'=>integer,'required'=>integer],...],
     * or false on error.
     */
    public function bomAccounting ($prokey) {
        $sql =
            'SELECT
                a.partid as partid,
                (SELECT number FROM material WHERE id = a.partid) as number,
                (SELECT description FROM material WHERE id = a.partid) as description,
                (
                    SELECT SUM(qty) 
                    FROM cell_material
                    INNER JOIN work_cell on work_cell.id = cell_material.cellid 
                    WHERE bomid = a.id
                    AND work_cell.prokey = :cell_prokey
                ) as used,
                a.qty as required
            FROM bom as a
            WHERE prokey = :prokey
            ORDER BY number ASC';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([':prokey'=>$prokey,':cell_prokey'=>$prokey])) throw new Exception(print_r($pntr->errorInfo(),true));
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Rebase an existing BOM with an addendum file
     * 
     * @param Array $data in the form `['file'=>FileUpload Object,'prokey'=>string,'uid'=>string]
     * @return Boolean True on success, false otherwise
     */
    public function rebaseExistingBOM ($data) {
        $rebase = [];        
        $this->prokey = $data['prokey'];
        $this->uid = $data['uid'];
        try {
            $fh = fopen($data['file']->files[0]['tmp_name'],'r');
            while(($line = fgetcsv($fh)) !== false) {
                $line[0] = str_ireplace('-','',$line[0]);
                $line[1] = trim($line[1]);
                $line[2] = (float) $line[2];
                array_push($rebase,$line);
            }
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }

        if (!$this->addNewMaterialsFromArray($rebase)) return false;
        if (!$this->addBOMArray($rebase)) return false;
        if (!$this->removeDupes($this->prokey)) return false;
        return true;
    }

    public function removeDupes ($prokey) {
        $sql = 'SELECT * FROM bom WHERE prokey = ? ORDER BY partid ASC';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$prokey])) throw new Exception(print_r($pntr->errorInfo(),true));
            $bom = $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
        $subtends = [];
        for($cnt = 0; $cnt < count($bom);$cnt++) {
           if ($bom[$cnt]['partid'] == $bom[($cnt +1)]['partid']) {
               if (!$this->materialInUse($bom[$cnt]['id'],$prokey)) array_push($subtends,$bom[$cnt]);
               elseif (!$this->materialInUse($bom[($cnt +1)]['id'],$prokey)) array_push($subtends,$bom[($cnt + 1)]);
               else array_push($subtends,$bom[($cnt + 1)]);
            }
        }
        $sql = 'DELETE FROM bom WHERE id = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            $this->dbh->beginTransaction();
            foreach($subtends as $sb) {
                if (!$pntr->execute([$sb['id']])) throw new Exception(print_r($pntr->errorInfo(),true));
            }
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
     * Delete multiple items from BOM by ID given in an array
     * @param Array $deletes An unindexed array containing ID's of row's to delete
     * @return Boolean True on success, false otherwise. 
     */
    public function deleteFromIDArray (Array $deletes) {
        $sql = 'DELETE FROM bom WHERE id = ?';
        $this->dbh->beginTransaction();
        $pntr = $this->dbh->prepare($sql);
        try {
            foreach($deletes as $id) {
                if (!$pntr->execute([$id])) throw new Exception(print_r($pntr->errorInfo(),true));
            }
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
     * Verifies that a material is not assigned to a work cell
     * @param String $bomid The ID of the BOM matieral to check
     * @param String $prokey The product key of the material to check
     * @return Boolean Returns true if the material is on work cell already, false if not.
     */
    public function materialInUse ($bomid, $prokey) {
        $sql = 
        'select count(*)
        from cell_material
        where cellid in (
            select id from work_cell where prokey = :prokey
        )
        and bomid = :bomid';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([':prokey'=>$prokey,':bomid'=>$bomid])) throw new Exception(print_r($pntr->errorInfo(),true));
            $count = $pntr->fetchAll(PDO::FETCH_ASSOC);
            if ($count[0]['count'] > 0) return true;
            else return false;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return null;
        }
    }
}