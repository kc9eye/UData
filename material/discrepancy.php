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
require_once(dirname(__DIR__).'/lib/init.php');
require_once(dirname(__DIR__).'/lib/libdiscrepancy.php');
include('submenu.php');

//Authentication------------------//
$server->userMustHavePermission('addDiscrepancy');

//Controls------------------------//
if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'submit': handleDiscrepancy(); break;
        default:discrepancyDisplay();break;
    }
}
elseif (!empty($_REQUEST['number'])) {
    selectedDisplay();
}
else {
    discrepancyDisplay();
}

//Displays----------------------//
function selectedDisplay () {
    global $server,$submenu;
    $material = new Material($server->pdo,$_REQUEST['number']);
    $product = new Product($server->pdo,$_REQUEST['prokey']);

    $view = $server->getViewer("Material: Discrepancy");
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->sideDropDownMenu($submenu);
    $view->h1("Material Discrepancy Reporting",true);
    $view->h3("<small>Product:</small> {$product->pDescription}",true);
    $form->newMultiPartForm("Record Discrepancy");
    $form->hiddenInput('action', 'submit');
    $form->hiddenInput('uid', $server->currentUserID);
    $form->hiddenInput('number', $_REQUEST['number']);
    $form->hiddenInput('prokey', $_REQUEST['prokey']);
    $form->selectBox('type','Type',[[Materials::PDN_TYPE,'PDN'],[Materials::PDIH_TYPE,'PDIH']],true);
    $form->inputCapture('qty','Qty.',null,['digits'=>'true']);
    $form->labelContent('Number',$_REQUEST['number']);
    $form->labelContent('Description',$material->material['description']);
    $form->textArea('description','Discreapncy','',true);
    $form->fileUpload(FileIndexer::UPLOAD_NAME,"Photo",null,false,true,"Only one file maybe assigned to this discrepancy, of 2MB or less size.");
    $form->submitForm('Submit',false,$view->PageData['approot'].'/material/viewdiscrepancy');
    $form->endForm();

    $view->footer();
}

function discrepancyDisplay () {
    global $server,$submenu;
    $products = new Products($server->pdo);
    $active = $products->getActiveProducts();
    $select = array();
    foreach($active as $row) {
        array_push($select,[$row['product_key'],$row['description']]);
    }
    $view = $server->getViewer("Material: Discrepancy");
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');

    $view->sideDropDownMenu($submenu);
    $view->h1("Material Discrepancy Reporting",true);
    $form->newMultipartForm("Record Discrepancy");
    $form->hiddenInput('action','submit');
    $form->hiddenInput('uid',$server->currentUserID);
    $form->selectBox('type','Type',[[Materials::PDN_TYPE,'PDN'],[Materials::PDIH_TYPE,'PDIH']],true);
    $form->selectBox('prokey','Product',$select,true);
    $form->inputCapture('qty','Qty.',null,['digits'=>'true']);
    $form->inputCapture('number','Material#',null,['digits'=>'true']);
    $form->textArea('description','Discrepancy','',true);
    $form->fileUpload(FileIndexer::UPLOAD_NAME,"Photo",null,false,true,"Only one photo file can be assigned to this discrepancy");
    $form->submitForm('Submit',false,$view->PageData['approot'].'/material/viewdiscrepancy');
    $form->endForm();

    $view->footer();
}