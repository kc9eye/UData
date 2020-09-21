<?php
/*
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
class Shipments {
    protected $dbh;

    /**
     * Shipments
     * 
     * @param PDO $dbh The server database handle PDO
     * @return Void
     */
    public function __construct (PDO $dbh) {
        $this->dbh = $dbh;
    }

    /**
     * Retrieves a list of all categories
     * @return Array An unindexd array of DB rows
     */
    public function getShippingCategories () {
        $sql = 'SELECT * FROM shipping';
        try{
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
     * Adds new shipping category
     * @param Array $data An array conatining the category info in 
     * the form ['category'=>string,'uid'=>string,'notify'=>string{"true"|"false"}]
     * @return Boolean True on success, false otherwise
     */
    public function addNewCategory (Array $data) {
        $sql = 'INSERT INTO shipping VALUES (:id,:name,:uid,:notify,now())';
        try {
            $pntr = $this->dbh->prepare($sql);
            $insert = [
                ':id'=>uniqid(),
                ':name'=>$data['category'],
                ':uid'=>$data['uid'],
                ':notify'=>$data['notify']
            ];
            if (!$pntr->execute($insert)) throw new Exception(print_r($pntr->errorInfo(),true));
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Returns category row given row id.
     * @param String $id The id of the row to return
     * @return Array An indexed array of the row, false on error
     */
    public function getCategoryByID ($id) {
        $sql = 'SELECT * FROM shipping WHERE id = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$id])) throw new Exception(print_r($pntr->errorInfo(),true));
            return $pntr->fetchAll(PDO::FETCH_ASSOC)[0];
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Updates a category row given updated data
     * @param Array $data The data for update in the form 
     * ['id'=>string,'category'=>string,'notify'=>string{"true"|"false"},'uid'=>string]
     * @return Boolean True on success, false otherwise
     */
    public function updateCategoryByID (Array $data) {
        $sql = 'UPDATE shipping SET name = :cat, notify = :notify, uid = :uid WHERE id = :id';
        $update = [
            ':cat'=>$data['category'],
            ':notify'=>$data['notify'],
            ':uid'=>$data['uid'],
            ':id'=>$data['id']
        ];
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute($update)) throw new Exception(print_r($pntr->errorInfo(),true));
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Gets shippment rows given the shipping category ID
     * @param String $id The shipping category ID
     * @return Array An unindexed array of rows records, false on failure
     */
    public function getShipmentsByCatID ($id) {
        $sql = "SELECT *,(SELECT firstname||' '||lastname FROM user_accts WHERE id = a.uid) as shipper FROM shipment as a WHERE a.shipid = ? ORDER BY a._date DESC";
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$id])) throw new Exception(print_r($pntr->errorInfo(),true));
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_ERROR);
            return false;
        }
    }

    /**
     * Creates a new shipment record given the data for the record
     * @param Array $data The data for the shipment in the form
     * ['shipid'=>string,'catid'=>string,'uid'=>string,'carrier'=>string,'vnumber'=>string]
     * @return Boolean True on success, false otherwise
     */
    public function createNewShipment (Array $data) {
        $sql = "INSERT INTO shipment VALUES (:id,:catid,:carrier,:vnumber,:comments,'false',:uid,now())";
        $insert = [
            ':id'=>$data['shipid'],
            ':catid'=>$data['catid'],
            ':carrier'=>$data['carrier'],
            ':comments'=>"",
            ':vnumber'=>$data['vnumber'],
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

    /**
     * Retrieves a shipment record by row ID
     * @param String $id The row ID of the shipment
     * @return Array An indexed array of the record, or false on error
     */
    public function getShipmentByID ($id) {
        $sql = 'SELECT * FROM shipment WHERE id = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$id])) throw new Exception(print_r($pntr->errorInfo(),true));
            return $pntr->fetchAll(PDO::FETCH_ASSOC)[0];
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Returns a unindex array of rows for the given shipment id
     * @param String $id The shipment ID of the rows to return
     * @return Array An unindexed array of row records
     */
    public function getItemsByShipmentID ($id) {
        $sql = 'SELECT * FROM shipping_log WHERE shipmentid = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$id])) throw new Exception(print_r($pntr->errorInfo(),true));
            return $pntr->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Adds a new item record to the shipping_log table
     * @param Array $data An indexed array in the form
     * ['shipid'=>string,'uid'=>string,'item'=>string]
     */
    public function addNewItemToShipment (Array $data) {
        $sql = 'INSERT INTO shipping_log VALUES (:id,:item,:shipid,:uid,now())';
        $insert = [
            ':id'=>uniqid(),
            ':item'=>$data['item'],
            ':shipid'=>$data['shipid'],
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

    /**
     * Removes an item from the shipping log given the row ID
     * @param String $id The row ID of the item to remove
     * @return Boolean True on success, false otherwise
     */
    public function removeItemFromSHipment ($id) {
        $sql = 'DELETE FROM shipping_log WHERE id = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$id])) throw new Execption(print_r($pntr->errorInfo(),true));
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Marks the given shipment row ID ready, and generates an email as such
     * @param String $id The row ID of the shipment
     * @param Notification $notify The Notification class object
     */
    public function shipmentReady ($id,$notify) {
        $sql = "UPDATE shipment SET ready = 'true' WHERE id = ?";
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$id])) throw new Exception(print_r($pntr->errorInfo(),true));
            $body = $notify->mailer->wrapInTemplate(
                "shipmentready.html",
                "<a href='{$notify->mailer->config['application-root']}/material/shipping?action=view_shipment&shipid={$id}'>New Shipment</a>"
            );
            $notify->notify("Shipment Ready","New Shipment Ready",$body);
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Updates a shipment comments field given the appropriate data
     * @param Array $data A data array in the form 
     * ['uid'=>string,'shipid'=>string,'comment'=>string]
     * @param Notification $notify The notification class object
     * @return Boolean True on success, false otherwise.
     */
    public function amendSHipmentComments (Array $data,Notification $notify) {
        $sql = 'UPDATE shipment SET uid = :uid, comments = :comment, _date = now() WHERE id = :id';
        $insert = [
            ':id'=>$data['shipid'],
            ':uid'=>$data['uid'],
            ':comment'=>$data['comments']
        ];
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute($insert)) throw new Exception(print_r($pntr->errorInfo(),true));
            $body = $notify->mailer->wrapInTemplate(
                "shippingcomment.html",
                "<a href='{$notify->mailer->config['application-root']}/material/shipping?view_shipment&shipid={$data['shipid']}'>View Comment</a>"
            );
            $notify->notify("Shipping Comment","New Shipping Comment",$body);
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }
}