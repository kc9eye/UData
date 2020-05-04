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
require_once(dirname(__DIR__).'/lib/init.php');

$server->userMustHavePermission('editWorkCell');

//Controller section
if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'add':
            require_once(dirname(__DIR__).'/lib/libmath.php');
            $parts = new Materials($server->pdo);
            if (!$parts->verifySingleNumber($_REQUEST['cellid'],$_REQUEST['number'],$_REQUEST['prokey'])) duplicateNumber();
            if (!$parts->verifyMaterial($_REQUEST['number'])) partDoesNotExist();
            if (!$parts->verifyOnBOM($_REQUEST['number'], $_REQUEST['prokey'])) partNotFound();
            if (!$parts->verifyBOMQty($_REQUEST['number'], $_REQUEST['prokey'], $_REQUEST['qty'])) partQtyExceeded();
            if (!nonZeroNumber($_REQUEST['qty'])) zeroSumNumber();
            $server->processingDialog(
                [$parts,'addCellMaterial'],
                [$_REQUEST],
                $server->config['application-root'].'/cells/cellmaterial?cellid='.$_REQUEST['cellid']
            );
        break;
        case 'edit': 
            editDisplay(); 
        break;
        case 'amend':
            require_once(dirname(__DIR__).'/lib/libmath.php');
            $materials = new Materials($server->pdo);
            $part = $materials->getCellMaterialByID($_REQUEST['id']);
            $cell = new WorkCell($server->pdo,$part['cellid']);
            $old_qty = $part['qty'];
            $new_qty = $_REQUEST['qty'];
            if (!nonZeroNumber($_REQUEST['qty'])) zeroSumNumber();
            if (!$materials->resetCellMaterialQty($_REQUEST['id'])) throw new Exception("Resetting quantity failed.");
            if (!$materials->verifyBOMQty($part['number'],$cell->ProductKey,$_REQUEST['qty'])) {
                if (!$materials->amendCellMaterialQty(['rowid'=>$_REQUEST['rowid'],'qty'=>$old_qty])) throw new Exception("Update to old qty failed");
                partQtyExceeded();
            }
            $server->processingDialog(
                [$materials,'amendCellMaterialQty'],
                [$_REQUEST],
                $server->config['application-root'].'/cells/cellmaterial?cellid='.$_REQUEST['cellid']
            );
        break;
        case 'remove':
            $materials = new Materials($server->pdo);
            $part = $materials->getCellMaterialByID($_REQUEST['id']);
            $server->processingDialog(
                [$materials,'removeCellMaterial'],
                [$_REQUEST['id']],
                $server->config['application-root'].'/cells/cellmaterial?cellid='.$part['cellid']
            );
        break;
        default: listMaterial();break;
    }
}
else {
    listMaterial();
}

//Various displays directed from the controller section
//Listing and editing of material for the cell.
function listMaterial () {
    global $server;
    $cell = new WorkCell($server->pdo,$_REQUEST['cellid']);
    $view = $server->getViewer("Cells: Materials");
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->h2("<small>Material For:</small> {$cell->Name}",true);
    $view->h3("<small>Assoc. With Product:</small> {$cell->Product}".
        $view->linkButton("/products/viewproduct?prokey={$cell->ProductKey}",
            "<span class='glyphicon glyphicon-arrow-left'></span>Back",
            'info',
            true
        ),
        true
    );
    $view->hr();
    $form->newForm("Add Material",$server->config['application-root']."/cells/cellmaterial",'post');
    $form->hiddenInput('action','add');
    $form->hiddenInput('uid',$server->currentUserID);
    $form->hiddenInput('cellid',$cell->ID);
    $form->hiddenInput('prokey',$cell->ProductKey);
    $form->inputCapture('qty','Qty.',null,['number'=>'true'],"All qty's less than 1 must start with '0.'");
    $form->inputCapture('number', 'Part#', null, true,'All material must be on the BOM, if not see material supervisor.');
    $form->inputCapture('label','Label',null,false,"Optional label");
    $form->submitForm('Verify',true);
    $form->endForm();
    $view->hr();
    $view->responsiveTableStart(['Label','Qty.','Number','Description','Edit'],true);
    foreach($cell->Material as $row) {
        echo "<tr><td>{$row['label']}</td><td>{$row['qty']}</td><td>{$row['number']}</td><td>{$row['description']}</td><td>";
        $view->editBtnSm("?action=edit&id={$row['id']}", false, true);
        echo "</td></tr>\n";
    }
    $view->responsiveTableClose(true);
    $view->footer();
}

//Edit display for a single part on a work cell
function editDisplay () {
    global $server;
    $materials = new Materials($server->pdo);
    $part = $materials->getCellMaterialByID($_REQUEST['id']);
    $cell = new WorkCell($server->pdo,$part['cellid']);
    $view = $server->getViewer("Cells:Materials (Edit)");
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->h2("<small>Material For:</small> {$cell->Name}");
    $view->h3("<small>Assoc. With Product:</small> {$cell->Product} ");
    $view->hr();
    $form->newForm("Edit Material Qty. ".$view->trashBtnSm('/cells/cellmaterial?action=remove&id='.$_REQUEST['id'],true));
    $form->hiddenInput('action','amend');
    $form->hiddenInput('cellid',$cell->ID);
    $form->hiddenInput('uid',$server->currentUserID);
    $form->hiddenInput('rowid',$_REQUEST['id']);
    $form->inputCapture('label','Label',$part['label'],false,"Optional");
    $form->inputCapture('qty','Qty',$part['qty'],['number'=>'true'],"Edit quantity (Qty's less than 1 must begin with '0.'");
    $form->labelContent("Number",$part['number']);
    $form->labelContent("Description",$part['description']);
    $form->submitForm("Edit",false,$view->PageData['approot'].'/cells/cellmaterial?cellid='.$part['cellid']);
    $form->endForm();
    $view->footer();
}

//Dialog if the part is not found on the BOM
function partNotFound () {
    global $server;
    $cell = new WorkCell($server->pdo,$_REQUEST['cellid']);
    $server->newEndUserDialog(
        "The material number entered: {$_REQUEST['number']}, was not found on the {$cell->Product} Bill of Materials.
        See a Material Handling Specialist to add this material.",
        DIALOG_FAILURE,
        $server->config['application-root'].'/cells/cellmaterial?cellid='.$_REQUEST['cellid']
    );
}

//Dialog if the part is not found to exist
function partDoesNotExist () {
    global $server;
    $server->newEndUserDialog(
        "The material entered is not found to be a valid material, or is unknown. Re-enter the material or 
        see a Material Handling Specialist.",
        DIALOG_FAILURE,
        $server->config['application-root'].'/cells/cellmaterial?cellid='.$_REQUEST['cellid']
    );
}

//Dialog if qty is already exceeded
function partQtyExceeded () {
    global $server;
    $server->newEndUserDialog(
        "Adding this part to this cell will exceed the limit specified in the BOM for this product.
        See a Material Handling Specialist to add an exemption for this material, or correct the quantities.",
        DIALOG_FAILURE,
        $server->config['application-root'].'/cells/cellmaterial?cellid='.$_REQUEST['cellid']
    );
}

//Dialog if number is duplicate
function duplicateNumber () {
    global $server;
    $server->newEndUserDialog(
        "This material number is already associated with this work cell. You may consider editing the existsing quantity.",
        DIALOG_FAILURE,
        $server->config['application-root'].'/cells/cellmaterial?cellid='.$_REQUEST['cellid']
    );
}

//Fixes issue #63
function zeroSumNumber () {
    global $server;
    $server->newEndUserDialog(
        "Zero quantities for material are not allowed!",
        DIALOG_FAILURE,
        $server->config['application-root'].'/cells/cellmaterial?cellid='.$_REQUEST['cellid']
    );
}