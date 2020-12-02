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
require_once(dirname(__DIR__).'/lib/init.php');
$server->userMustHavePermission("writePPE");

if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']){
        case 'addppe': addPPEDisplay(); break;
        case 'enterNewPPE': 
            $server->userMustHavePermission('executePPE');
            $server->processingDialog(
                [new ShopSupplies($server->pdo),'addNewPPE'],
                [$_REQUEST],
                $server->config['application-root'].'/material/ppesupplies'
            );
        break;
        case 'updateItem':
            $server->userMustHavePermission('executePPE');
            $server->processingDialog(
                [new ShopSupplies($server->pdo),"updatePPEItem"],
                [$_REQUEST],
                $server->config['application-root'].'/material/ppesupplies'
            );
        break;
        case 'delete':
            $server->userMustHavePermission('executePPE');
            $server->processingDialog(
                [new ShopSupplies($server->pdo),'deletePPEItem'],
                [$_REQUEST['id']],
                $server->config['application-root'].'/material/ppesupplies'
            );
        break;
        case 'editPPE':
            editPPEItemDisplay();
        break;
        case 'assignPPE':
            $server->userMustHavePermission('writePPE');
            $server->processingDialog(
                [new ShopSupplies($server->pdo),'assignPPE'],
                [$_REQUEST],
                $server->config['application-root'].'/material/ppesupplies'
            );
        break;
        case 'timeframeReport': timeframeReportDisplay(); break;
        default: mainDisplay(); break;
    }
}
else {
    mainDisplay();
}

function mainDisplay() {
    global $server;
    include('submenu.php');

    $emp = new Employees($server->pdo);
    $selectEmployee = [];
    foreach($emp->getActiveEmployeeList() as $i) {
        array_push($selectEmployee,[$i['eid'],$i['name']]);
    }

    $ppe = new ShopSupplies($server->pdo);
    $selectPPE = [];
    foreach($ppe->getAvailablePPE() as $i) {
        array_push($selectPPE, [$i['id'],$i['description']]);
    }

    $pageOptions = [
        'headinserts'=> [
            '<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/js/bootstrap-datepicker.min.js"></script>',
            '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/css/bootstrap-datepicker3.css"/>'
        ]
    ];
    $view = $server->getViewer("PPE Supplies",$pageOptions);
    $view->sideDropDownMenu($submenu);
    $view->h1("Personal Protective Equipment");
    $view->hr();
    if ($server->checkPermission("executePPE")) adminPPEDisplay($view);
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    $form->newForm("Assign PPE");
    $form->hiddenInput('action','assignPPE');
    $form->hiddenInput('uid',$server->currentUserID);
    $form->selectBox("eid","Employee",$selectEmployee,true);
    $form->selectBox('ppeid',"PPE Item",$selectPPE,true);
    $form->checkBox("returned",["Optional","Returned"],"true",false,null,"false");
    $form->submitForm("Assign PPE");
    $form->endForm();
    $view->footer();
}

function adminPPEDisplay(ViewMaker $view) {
    global $server;
    $ppe = new ShopSupplies($server->pdo);
    $available = $ppe->getAvailablePPE();

    $view->beginBtnCollapse("Administer PPE");
    $view->br();
    $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');
    $form->newInlineForm();
    $form->hiddenInput("action","timeframeReport");
    $view->h3("Timeframe Report: &#160;");
    echo
        "<div class='input-group input-daterange'>
            <input class='form-control' type='text' name='begin_date' />
            <span class='input-group-addon'>to</span>
            <input class='form-control' type='text' name='end_date' />
            <div class='input-group-append'>
                <input type='submit' value='Get Report' class='btn btn-success' />
            </div>
        </div>";
    $form->endInlineForm();
    $view->hr();
    $view->h3("Available PPE");
    $view->responsiveTableStart(["Description","Vendor","Unit Price","Edit"]);
    foreach ($available as $item) {
        echo "<tr><td>{$item['description']}</td><td>{$item['vendor']}</td><td>{$item['unit_price']}</td><td>".$view->editBtnSm("/material/ppesupplies?ppeid={$item['id']}&action=editPPE",true)."</td></tr>";
    }
    $view->responsiveTableClose();
    $view->linkButton("/material/ppesupplies?action=addppe","Add Available PPE &#160;<span class='oi oi-plus' title='plus' aria-hidden='true'></span>",'success');
    echo "<script>$(document).ready(function(){
        var options = {
            format:'yyyy/mm/dd',
            autoclose: true
        };
        $('.input-group input').each(function(){
            $(this).datepicker(options);
        });
    });</script>";
    $view->endBtnCollapse();
    $view->hr();
}

function addPPEDisplay() {
    global $server;
    include('submenu.php');
    $server->userMustHavePermission('executePPE');
    $view = $server->getViewer("Add PPE");
    $view->sideDropDownMenu($submenu);
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    $form->newForm("Add PPE");
    $form->hiddenInput("action","enterNewPPE");
    $form->hiddenInput("uid",$server->currentUserID);
    $form->inputCapture("description","PPE Description",null,true,"What the PPE is.");
    $form->inputCapture("vendor","Item Vendor","UHaul",true,"Where the PPE item comes from.");
    $form->inputCapture("cost","Pricing",null,true,"A decimal value, with no money sign, indicating each units cost");
    $form->submitForm("Add PPE Item");
    $form->endForm();
    $view->footer();
}

function editPPEItemDisplay() {
    global $server;
    $server->userMustHavePermission('executePPE');
    include('submenu.php');

    $ppe = new ShopSupplies($server->pdo);
    $item = $ppe->getPPEItem($_REQUEST['ppeid']);
    $view = $server->getViewer("Edit PPE Item");
    $view->sideDropDownMenu($submenu);
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    $form->newForm("Edit PPE Item &#160;" .$view->trashBtnSm("/material/ppesupplies?action=delete&id={$_REQUEST['ppeid']}",true));
    $form->hiddenInput('id',$_REQUEST['ppeid']);
    $form->hiddenInput('uid',$server->currentUserID);
    $form->hiddenInput('action','updateItem');
    $form->inputCapture('description','Description',$item['description'],true);
    $form->inputCapture('vendor',"Vendor",$item['vendor'],true);
    $form->inputCapture('cost','Cost',$item['unit_price'],true);
    $form->submitForm("Update");
    $form->endForm();
}

function timeframeReportDisplay() {
    global $server;
    $server->userMustHavePermission("executePPE");
    include('submenu.php');
    $ppe = new ShopSupplies($server->pdo);
    $report = $ppe->getTimeFrameReport($_REQUEST);

    $view = $server->getViewer("PPE Timeframe Report");
    $view->sideDropDownMenu($submenu);
    $view->h1("Timeframe PPE Report");
    $view->h2("Beginning: {$_REQUEST['begin_date']}, Ending: {$_REQUEST['end_date']}");
    $view->printButton();
    $view->responsiveTableStart(["Total Expense","Qty. Assigned", "Qty. Exchanged", "Item"]);
    foreach($report as $row) {
        echo "<tr><td>{$row['Expense']}</td><td>{$row['Issued']}</td><td>{$row['Exchanged']}</td><td>{$row['Item']}</td></tr>";
    }
    $view->responsiveTableClose();
    $view->footer();
}