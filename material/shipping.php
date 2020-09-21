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
require_once(dirname(__DIR__).'/lib/init.php');

$server->userMustHavePermission('viewMaterial');

if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'view':
            viewCategory();
        break;
        case 'new_shipment':
            $server->userMustHavePermission("shipEquipment");
            newShipment();
        break;
        case 'new_item':
            $server->userMustHavePermission('shipEquipment');
            $server->processingDialog(
                [new Shipments($server->pdo), 'addNewItemToShipment'],
                [$_REQUEST],
                $server->config['application-root'].'/material/shipping?action=add_items&shipid='.$_REQUEST['shipid']
            );
        break;
        case 'add_items':
            addItemsToShipment();
        break;
        case 'remove_item':
            $server->userMustHavePermission("shipEquipment");
            $server->processingDialog(
                [new Shipments($server->pdo),"removeItemFromShipment"],
                [$_REQUEST['id']],
                $server->config['application-root'].'/material/shipping?action=add_items&shipid='.$_REQUEST['shipid']
            );
        break;
        case 'create_new_shipment':
            $server->userMustHavePermission("shipEquipment");
            $server->processingDialog(
                [new Shipments($server->pdo),"createNewShipment"],
                [$_REQUEST],
                $server->config['application-root'].'/material/shipping?action=add_items&shipid='.$_REQUEST['shipid'],
                "Creating New Shipment"
            );
        break;
        case 'new_cat':
            addNewCat();
        break;
        case 'edit_cat':
            editCat();
        break;
        case 'update_cat':
            $server->userMustHavePermission('createShippingCatefory');
            $server->processingDialog(
                [new Shipments($server->pdo),"updateCategoryByID"],
                [$_REQUEST],
                $server->config['application-root'].'/material/shipping'
            );
        break;
        case 'create_cat':
            $server->userMustHavePermission("createShippingCategory");
            $server->processingDialog(
                [new Shipments($server->pdo), "addNewCategory"],
                [$_REQUEST],
                $server->config['application-root'].'/material/shipping'
            );
        break;
        case 'view_shipment':
            $ship = new Shipments($server->pdo);
            $shipment = $ship->getShipmentByID($_REQUEST['shipid']);
            if ($shipment['ready'] == '0') addItemsToShipment();
            else viewAmendShipment();
        break;
        case 'amend_shipment':
            $server->userMustHavePermission("editShippingLog");
            $server->processingDialog(
                [new SHipments($server->pdo),"amendShipmentComments"],
                [$_REQUEST,new Notification($server->pdo,$server->mailer)],
                $server->config['application-root'].'/material/shipping?action=view_shipment&shipid='.$_REQUEST['shipid']
            );
        break;
        case 'shipment_ready':
            $server->userMustHavePermission("shipEquipment");
            $server->processingDialog(
                [new Shipments($server->pdo),"shipmentReady"],
                [$_REQUEST['shipid'],new Notification($server->pdo,$server->mailer)],
                $server->config['application-root'].'/material/shipping'
            );
        break;
        default: main(); break;
    }
}
else main();

function main () {
    global $server;
    include("submenu.php");
    $ship = new Shipments($server->pdo);
    $view = $server->getViewer("Material: Shipping");
    $view->sideDropDownMenu($submenu);
    $view->h1("Shipping Records");
    $view->hr();
    $heading = "Shipping Categories";
    if ($server->checkPermission("createShippingCategory"))
        $heading .= " ".$view->linkButton('/material/shipping?action=new_cat',"<span class='oi oi-plus'></span> Add New",'success',true);
    $view->h3($heading);
    if ($server->checkPermission("createShippingCategroy")) 
        $th = ['Category','Notifications','Edit'];
    else
        $th = ['Category','Notifications'];
    $view->responsiveTableStart($th);
    foreach($ship->getShippingCategories() as $row) {
        echo "<tr><td>";
        $view->linkButton('/material/shipping?action=view&catid='.$row['id'],"<span class='oi oi-eye'></span> View","secondary");
        $view->insertTab();
        $notify = ($row['notify'] == 1) ? "Yes" : "No";
        echo "{$row['name']}</td><td>{$notify}</td>";
        if ($server->checkPermission("createShippingCategory"))  {
            echo "<td>".$view->editBtnSm('/material/shipping?action=edit_cat&id='.$row['id'],true)."</td>";
        }
        echo "</tr>";
    }
    $view->responsiveTableClose();
    $view->footer();
}

function addNewCat () {
    global $server;
    $server->userMustHavePermission("createShippingCategory");
    include('submenu.php');
    $view = $server->getViewer("Shipping: New Cat.");
    $view->sideDropDownMenu($submenu);
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    $form->newForm("Add New Category");
    $form->hiddenInput('action','create_cat');
    $form->hiddenInput('uid',$server->currentUserID);
    $form->inputCapture('category','Category',null,true,"The name of the category being added.");
    $form->checkBox('notify',["Notifications","Yes"],"true",false,"Whether shippment ready notifications are required.","false");
    $form->submitForm("Add");
    $form->endForm();
    $view->footer();
}

function editCat () {
    global $server;
    $server->userMustHavePermission("createShippingCategory");
    include('submenu.php');
    $view = $server->getViewer("Shipping: Edit Cat.");
    $view->sideDropDownMenu($submenu);
    $ship = new Shipments($server->pdo);
    $cat = $ship->getCategoryByID($_REQUEST['id']);
    $view->h1("Edit Category");
    $view->hr();
    $view->h3("<small>Category:</small> {$cat['name']}");
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    $form->newForm();
    $form->hiddenInput('action','update_cat');
    $form->hiddenInput('catid',$_REQUEST['id']);
    $form->hiddenInput('uid',$server->currentUserID);
    $form->inputCapture('category',"Category",$cat['name'],true);
    if ($cat['notify'] == 1) 
        $form->checkBox('notify',["Notifications","No"],"false",false,"Stop notifications?","true");
    else
        $form->checkBox("notify",["Notifications","Yes"],"true",false,"Turn on notifications?","false");
    $form->submitForm("Update");
    $form->endForm();
    $view->footer();
}

function viewCategory () {
    global $server;
    include('submenu.php');
    $ship = new Shipments($server->pdo);
    $cat = $ship->getCategoryByID($_REQUEST['catid']);
    $sent = $ship->getShipmentsByCatID($_REQUEST['catid']);
    $view = $server->getViewer("Shipping: View Shipments");
    $view->sideDropDownMenu($submenu);
    $heading = "<small>Shipments For:</small> {$cat['name']}";
    if ($server->checkPermission("shipEquipment")) 
        $heading .= $view->InsertTab(1,true).
        $view->linkButton(
            '/material/shipping?action=new_shipment&catid='.$_REQUEST['catid'],
            "<span class='oi oi-plus'></span> New Shipment",
            'success',true
        );
    $view->h1($heading);
    $view->hr();
    $th = ['View','Date','Shipper'];
    $view->responsiveTableStart($th);
    foreach($sent as $row) {
        echo "<tr><td>";
        $view->linkButton('/material/shipping?action=view_shipment&shipid='.$row['id'],"<span class='oi oi-eye'></span> View",'secondary');
        $view->insertTab();
        echo "{$row['id']}</td><td>".$view->formatUserTimestamp($row['_date'],true)."</td><td>{$row['shipper']}</td>";
        echo "</tr>";
    }
    $view->responsiveTableClose();
    $view->footer();
}

function viewAmendShipment () {
    global $server;
    include('submenu.php');
    $ship = new Shipments($server->pdo);
    $shipment = $ship->getShipmentByID($_REQUEST['shipid']);
    $shipper = new User($server->pdo,$shipment['uid']);
    $view = $server->getViewer("Shipping: View Shipment");
    $view->sideDropDownMenu($submenu);
    $view->printButton();
    $view->h1("<small>Carrier:</small> {$shipment['carrier']}");
    $view->h2("<small>Vehicle Number:</small> {$shipment['vehicle_number']}");
    $view->h3("<small>Shipper:</small> {$shipper->getFullName()}");
    $view->h3("<small>Shipment ID:</small> {$shipment['id']}");
    if ($server->checkPermission("editShippingLog") && !empty($_REQUEST['add_comment'])) {
        $form = new inlineFormWidgets($view->PageData['wwwroot'].'/scripts');
        $form->newInlineForm("Comments");
        $form->hiddenInput("action","amend_shipment");
        $form->hiddenInput("shipid",$_REQUEST['shipid']);
        $form->hiddenInput('uid',$server->currentUserID);
        $view->responsiveTableStart();
        echo "<tr><td>";
        $form->inlineTextArea('comments',null,$shipment['comments'],true,null,true);
        echo "</td></tr><tr><td>";
        $form->inlineSubmit('Comment');
        echo "</td></tr>";
        $view->responsiveTableClose();
        $form->endInlineForm();
    }
    else {
        if ($server->checkPermission("editShippingLog")) {
            $view->wrapInCard(
                $shipment['comments'],
                "Comments",
                $view->editBtnSm("/material/shipping?action=view_shipment&shipid={$_REQUEST['shipid']}&add_comment=true",true)
            );
        }
        else {
            $view->wrapInCard(
                $shipment['comments'],
                "Comments"
            );
        }

    }
    $view->responsiveTableStart(['Item Shipped']);
    foreach($ship->getItemsByShipmentID($_REQUEST['shipid']) as $item) {
        echo "<tr><td>{$item['item']}</td></tr>";
    }
    $view->responsiveTableClose();
    $view->footer();
}

function newShipment () {
    global $server;
    include('submenu.php');
    $server->userMustHavePermission("shipEquipment");
    $user = new User($server->pdo,$server->currentUserID);
    $view = $server->getViewer("Shipping: New Shipment");
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->sideDropDownMenu($submenu);
    $view->h1("Create New Shipment");
    $view->h2("<small>By:</small> {$user->getFullName()}");
    $view->hr();
    $form->newForm("New Shipment Details");
    $form->hiddenInput("action","create_new_shipment");
    $form->hiddenInput("shipid",uniqid("Shipment->"));
    $form->hiddenInput("uid",$server->currentUserID);
    $form->hiddenInput('catid',$_REQUEST['catid']);
    $form->inputCapture('carrier',"Carrier",null,true,"The name of the carrier company or shipper.");
    $form->inputCapture('vnumber',"Vehicle Number",null,true,"Carrier vehicle number of other identifying info.");
    $form->submitForm("Add Items to Shipment",false,$view->PageData['approot'].'/material/shipping');
    $form->endForm();
    $view->footer();
}

function addItemsToShipment () {
    global $server;
    include('submenu.php');
    $server->userMustHavePermission("shipEquipment");
    $ship = new Shipments($server->pdo);
    $shipment = $ship->getShipmentByID($_REQUEST['shipid']);
    $user = new User($server->pdo,$shipment['uid']);
    $view = $server->getViewer("Shipping: Add Items");
    $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->sideDropDownMenu($submenu);
    $view->h1("New Shipment ".$view->linkButton('/material/shipping?action=shipment_ready&shipid='.$_REQUEST['shipid'],"Shipment Ready",'warning',true));
    $view->responsiveTableStart();
    echo "<tr><th>Shipment ID:</th><td>{$shipment['id']}</td></tr>";
    echo "<tr><th>Carrier:</th><td>{$shipment['carrier']}</td></tr>";
    echo "<tr><th>Vehicle Number:</th><td>{$shipment['vehicle_number']}</td></tr>";
    echo "<tr><th>Created by:</th><td>{$user->getFullName()}</td></tr>";
    $view->responsiveTableClose();
    $view->h3("Edit Shipment Items");
    $view->responsiveTableStart(['Added Item',"Remove"]);
    foreach($ship->getItemsByShipmentID($shipment['id']) as $item) {
        echo "<tr><td colspan='2'>{$item['item']}</td><td>";
        $view->trashBtnSm('/material/shipping?action=remove_item&id='.$item['id'].'&shipid='.$_REQUEST['shipid']);
        echo "</td></tr>";
    }
    $view->responsiveTableClose();
    $view->hr();
    $view->h3("Add Item");
    $form->newInlineForm();
    $form->hiddenInput("action","new_item");
    $form->hiddenInput("shipid",$_REQUEST['shipid']);
    $form->hiddenInput("uid",$server->currentUserID);
    $form->inlineInputCapture('item',"Description",null,true,"Item being inlcuded.");
    $form->inlineSubmit("<span class='oi oi-plus'></span> Add Item");
    $form->endInlineForm();
    $view->footer();
}