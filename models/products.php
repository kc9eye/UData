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
 * Products Class Model
 *
 * @package UData\Models\Database\Postgres
 * @link https://kc9eye.github.io/udata/UData_Database_Structure.html
 * @author Paul W. Lane
 * @license GPLv2
 */
class Products {
    protected $dbh;

    public $NewProductKey;

    public function __construct (PDO $dbh) {
        $this->dbh = $dbh;
        $this->NewProductKey = null;
    }

    /**
     * Verifies if a product description does not already exists
     * @param String $description The product description to verify
     * @return Boolean Returns 'true' if the product does not exist,
     * otherwise false.
     */
    public function verifyProductDescription ($description) {
        $sql = 'SELECT count(*) FROM products WHERE description = ?';
        $pntr = $this->dbh->prepare($sql);
        try {
            $pntr->execute([$description]);
            $count = $pntr->fetchAll(PDO::FETCH_ASSOC);
            if ($count[0]['count'] == 0) {
                return true;
            }
            else {
                return false;
            }
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
    }

    /**
     * Adds a new product to the master product list
     * @param Array $data In the form `['product_description'=>'description','uid'=>'users_id_number']`
     * Generally in the form of $_REQUEST array.
     * @return Boolean `true` on success, `false` otherwise.
     */
    public function addNewProduct ($data) {
        $sql =
            'INSERT INTO products (product_key,description,active,_date,uid)
            VALUES (:product_key,:description,:active,now(),:uid)';
        $pntr = $this->dbh->prepare($sql);
        $insert = [
            ':product_key'=>uniqid('master_product_key_'),
            ':description'=>$data['product_description'],
            ':active'=>$data['active_product'],
            ':uid'=>$data['uid']
        ];
        try {
            $this->dbh->beginTransaction();
            $pntr->execute($insert);
            $this->dbh->commit();
            if ($pntr->rowCount() == 0) {
                throw new Exception('Zero rows inserted');
            }
            else {
                $this->NewProductKey = $insert[':product_key'];
                return true;
            }
        }
        catch (PDOException $e) {
            $this->dbh->rollBack();
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
    }

    /**
     * Updates an already existsing product.
     * @param String $prokey The products master key
     * @param String $description The products updated description
     * @param String $status Either 'true' for active or 'false' for inactive status
     * @return Boolean
     */
    public function updateExistingProduct ($prokey,$description,$status) {
        $sql = 'UPDATE products SET description = :descr,active = :active WHERE product_key = :prokey';
        $update = [
            ':descr'=>$description,
            ':active'=>$status,
            ':prokey'=>$prokey
        ];
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute($update)) throw new Exception("Update failed: {$sql}");
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }

    }

    /**
     * Searches the master list for products
     *
     * Either returns an array of matches found or false if
     * no matches were found.
     * @param String $search_term A formatted search string
     * @return Mixed Either an array of found rows, or false
     */
    public function searchProducts($search_term) {
        $sql = 'SELECT * FROM products WHERE to_tsquery(?) @@ search';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$search_term])) throw new Exception(print_r($pntr->errorInfo(),true));
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
    }


    /**
     * Removes a product from the master list
     *
     * Removes a product from only the master list. The parameter array
     * can have either the `product_description` index, or `product_key` index.
     * If both are given, the product matching both will be removed
     * @param Array $data An array containing either or both of the given indexes
     * @return Boolean `true` on succes, otherwise `false`
     */
    public function removeProductFromMaster (Array $data) {
        $description = !empty($data['product_description']) ? $data['product_description'] : false;
        $key = !empty($data['product_key']) ? $data['product_key'] : false;
        if ($description === false && $key === false) {
            throw new Exception("Null argument value exception, array needs one of 'product_description' or 'product_key'.");
        }
        elseif ($description !== false && $key !== false) {
            $sql = 'DELETE FROM products WHERE product_key = :key AND product_description = :description';
            $insert = [':description'=>$description,':key'=>$key];
        }
        elseif ($description !== false && $key === false) {
            $sql = 'DELETE FROM products WHERE product_description = :description';
            $insert = [':description'=>$description];
        }
        elseif ($description === false && $key !== false) {
            $sql = 'DELETE FROM products WHERE product_key = :key';
            $insert = [':key'=>$key];
        }
        else {
            throw new Exception("No array indexes matched.");
        }

        try {
            $pntr = $this->dbh->prepare($sql);
            $pntr->execute($insert);
            if ($pntr->rowCount() != 0) {
                return true;
            }
            else {
                return false;
            }
        }
        catch (PDOException $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }

    }

    /**
     * Returns the product description given it's product key
     * @param String $key The product key to look up.
     * @return Mixed Either the product description or false on failure.
     */
    public function getProductDescriptionFromKey ($key) {
        $sql = 'SELECT description FROM products WHERE product_key = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            $pntr->execute([$key]);
            if (!empty(($result = $pntr->fetchAll(PDO::FETCH_ASSOC)[0]['description']))) {
                return $result;
            }
            else {
                return false;
            }
        }
        catch (PDOException $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Retrieves a product QC log entry by it's ID
     * @param String $id The log entries ID
     * @return Array The log entries data
     */
    public function getQCLogEntryByID ($id) {
        $sql = 'SELECT * FROM production_log WHERE id = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$id])) throw new Exception("Select failed: {$sql}");
            return $pntr->fetchAll(PDO::FETCH_ASSOC)[0];
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Updates an existsing QC log entry from an Array
     * @param Array $data The data for the entry;
     * `['id'=>string,'serial'=>string,'sequence'=>string,'misc'=>string,'ftc'=>string,'comments'=>string,'uid'=>string]`
     * @return Boolean
     */
    public function updateExistingQCLog ($data) {
        $sql =
            'UPDATE production_log
             SET sequence_number = :sequence,
             serial_number = :serial,
             misc = :misc,
             ftc = :ftc,
             comments = :comments,
             uid = :uid
             WHERE id = :id';
        $update = [
            ':sequence'=>$data['sequence'],
            ':serial'=>$data['serial'],
            ':misc'=>$data['misc'],
            ':ftc'=>$data['ftc'],
            ':comments'=>$data['comments'],
            ':uid'=>$data['uid'],
            ':id'=>$data['id']
        ];
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute($update)) throw new Exception("Update failed: {$sql}");
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Removes a product log entry and realigns existing sequence numbers
     *
     * Deletes the given entry from the log denoting it's sequence and aligns
     * all remaining entries sequence numbers missing the deleted entry.
     */
    public function removeExistingQCLogEntry ($id) {
        $entry = $this->getQCLogEntryByID($id);
        $delete = 'DELETE FROM production_log WHERE id = ?';
        $update =
            'UPDATE production_log SET sequence_number = (sequence_number - 1)
             WHERE prokey = :pkey AND sequence_number > :seq';
        $this->dbh->beginTransaction();
        try {
            $rm = $this->dbh->prepare($delete);
            $seq = $this->dbh->prepare($update);
            $rm->execute([$entry['id']]);
            $seq->execute([':pkey'=>$entry['prokey'],':seq'=>$entry['sequence_number']]);
            $this->dbh->commit();
            return true;
        }
        catch (PDOException $e) {
            $this->dbh->rollBack();
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
        catch (Exception $e) {
            $this->dbh->rollBack();
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }


    /**
     * Retrieves the checkpoints for a given product
     *
     * @param String $key The product master key to retreive checkponits for
     * @return Mixed Either a multi-dimensional array of checkpoints or false on error
     */
    public function getCheckPoints ($key) {
        $sql = "SELECT
            id,prokey,description,_date,cellid,
            (SELECT cell_name as cell FROM work_cell WHERE id = a.cellid),
            (SELECT firstname||' '||lastname as author FROM user_accts WHERE id = a.uid)
            FROM quality_control as a WHERE prokey = ?";
        try {
            $pntr = $this->dbh->prepare($sql);
            $pntr->execute([$key]);
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (PDOException $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Adds a given checkpoint to a product
     *
     * The `$data` parameter needs to be an indexed array containing the data to insert.
     * Namely the users uid, and a description of the checkpoint as well as the product
     * key the product belongs to in the form `['uid'=>string,'description'=>string,'prokey'=>string,'cellid'=>string]`
     * @param Array $data Usually the $_REQUEST array from a form to add the point.
     * @return Boolean `true` on success, otherwise `false`
     */
    public function addCheckPoint (Array $data) {
        $sql = 'INSERT INTO quality_control VALUES (:id,:prokey,:description,now(),:uid,:cellid)';
        $pntr = $this->dbh->prepare($sql);
        $insert = [
            ':id'=>uniqid(),
            ':prokey'=>$data['prokey'],
            ':description'=>$data['description'],
            ':uid'=>$data['uid'],
            ':cellid'=>empty($data['cellid']) ? '' :$data['cellid']
        ];
        try {
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
     * Removes a quality checkpoint from a product
     * @param String $prokey The product key to remove the checkpoint from
     * @param String $id The ID of the check point to remove
     * @return Boolean
     */
    public function removeCheckPoint ($prokey, $id) {
        $sql = 'DELETE FROM quality_control WHERE prokey = :prokey AND id = :id';
        $pntr = $this->dbh->prepare($sql);
        try {
            $this->dbh->beginTransaction();
            $pntr->execute([':prokey'=>$prokey,':id'=>$id]);
            $this->dbh->commit();
            return true;
        }
        catch (PDOException $e) {
            $this->dbh->rollBack();
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
        catch (Exception $e) {
            $this->dbh->rollBack();
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Adds an entry to the Quality Control Log
     *
     * The `$data` array should be in the form
     * `['prokey'=>string,'uid'=>string,serial'=>string,'misc'=>string,'comments'=>string,['qc'=>[1/0,...]]]`
     * The array can contain others, but they are not used or changed.
     * @param Array $data The data to insert
     * @return Boolean
     */
    public function addToQcLog (Array $data) {
        $sql =
            "INSERT INTO production_log
             SELECT :id,:prokey,:serial_num,(
                (SELECT count(*) from production_log WHERE prokey = :pkey) + 1
             ),:misc,:ftc,:comments,:uid,now(),:driver";

        #Calculate FTC
        $qc = count($data['qc']);
        $defect = $qc;
        foreach($data['qc'] as $point) {
            $split = explode(':',$point);
            if ($split[0] == 0) {
                $defect--;
                if (!empty($split[1])) $this->defectWorkCell($split[1]);
            }
            elseif (!empty($split[1]))
                $this->compWorkCell($split[1]);
        }
        $ftc = round((($defect/$qc) * 100),2, PHP_ROUND_HALF_UP);

        #insert the data
        $insert = [
            ':id'=>uniqid(),
            ':prokey'=>$data['prokey'],
            ':pkey'=>$data['prokey'],
            ':serial_num'=>$data['serial'],
            ':misc'=>$data['misc'],
            ':ftc'=>$ftc,
            ':comments'=>$data['comments'],
            ':uid'=>$data['uid'],
            ':driver'=>$data['driver']
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

    /**
     * Returns an array of active Products
     *
     * Returns an array of products which have the
     * designation of active.
     * @return Mixed A multidimensional array of active products, or FALSE on error.
     * @author Paul W. Lane
     */
    public function getActiveProducts () {
        try {
            $pntr = $this->dbh->query('SELECT * FROM products WHERE active');
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
     * Increases a work cells control without incereasing quality.
     *
     * Decrements a work cell as to show a defect in that work cell
     * @param String $cellid The ID of the work cell
     * @return Boolean True on success, false otherwise.
     */
    protected function defectWorkCell ($cellid) {
        $sql = 'UPDATE work_cell SET control = (control +1) WHERE id = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$cellid])) throw new Exception("Update failed: {$sql}");
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
     * Complement a work cell by incrementing control and quality
     *
     * @param String $cellid The ID of the string
     * @return Boolean True on success, false otherwise
     */
    protected function compWorkCell ($cellid) {
        $sql = 'UPDATE work_cell SET control = (control + 1), quality = (quality + 1) WHERE id = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$cellid])) throw new Exception("Udate failed: {$sql}");
            return true;
        }
        catch (PDOException $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
    }

    /**
     * Determines if the given product is active or not
     * @param String $prokey The product master product key
     * @return Boolean True if the given product is active, false otherwise
     */
    public function isActiveProduct ($prokey) {
        $sql = 'SELECT active FROM products WHERE product_key = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$prokey])) throw new Exception("Select failed: {$sql}");
            if (empty(($result = $pntr->fetchAll(PDO::FETCH_ASSOC)))) return false;
            elseif ($result[0]['active'] == 'true') return true;
            else return false;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
    }
}
