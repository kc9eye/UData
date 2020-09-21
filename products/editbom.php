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
//Source intialization, and check security access
require_once(dirname(__DIR__).'/lib/init.php');

$server->userMustHavePermission('editBOM');

//Controlling section, based on API URL
if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'amend':
            $bom = new BillOfMaterials($server->pdo);
            $server->processingDialog(
                [$bom,'amendBOMByID'],
                [$_REQUEST],
                $server->config['application-root'].'/products/bom?prokey='.$_REQUEST['prokey']
            );
        break;
        case 'remove':
            $bom = new BillOfMaterials($server->pdo);
            $server->processingDialog(
                [$bom,'removeMaterialByID'],
                [$_REQUEST['id']],
                $server->config['application-root'].'/products/bom?prokey='.$_REQUEST['prokey']
            );
        break;
        case 'add':
            $bom = new BillOfMaterials($server->pdo);
            if (!$bom->verifyMaterialExists($_REQUEST['number'])) 
                $server->newEndUserDialog(
                    "This material number does not yet exists, first add it as a valid material.",
                    DIALOG_FAILURE,
                    $server->config['application-root'].'/material/addnew'
                );
            else 
                $server->processingDialog(
                    [$bom,'addendumBOM'],
                    [$_REQUEST],
                    $server->config['application-root'].'/products/bom?prokey='.$_REQUEST['prokey']
                );
        break;
        case 'rerebase':
            $_REQUEST['file'] = new FileUpload(FileIndexer::UPLOAD_NAME);
            $server->processingDialog(
                [new BillOfMaterials($server->pdo), 'rebaseExistingBOM'],
                [$_REQUEST],
                $server->config['application-root'].'/products/bom?prokey='.$_REQUEST['prokey']
            );
        break;
        case 'deletemultiple':
            if (!empty($_REQUEST['delete'])) {
                $server->processingDialog(
                    [new BillOfMaterials($server->pdo),'deleteFromIDArray'],
                    [$_REQUEST['delete']],
                    $server->config['application-root'].'/products/bom?prokey='.$_REQUEST['prokey']
                );
            }
            else 
                $server->newEndUserDialog(
                    "Use must select items to delete!",
                    DIALOG_FAILURE,
                    $server->config['application-root'].'/products/editbom?action=multidelete&prokey='.$_REQUEST['prokey']
                );
        break;
        case 'multidelete':
            displayMultiDelete();
        break;
        case 'addendum': addendumDisplay(); break;
        case 'rebase': displayRebase(); break;
        default: editMaterial(); break;
    }
}
else editMaterial();

//Various displays called by the controlling section
function editMaterial () {
    global $server;
    $bom = new BillOfMaterials($server->pdo);
    $line = $bom->getMaterialByID($_REQUEST['id']);

    $view = $server->getViewer("BOM: Edit");
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    
    $view->h1("<small>Material for:</small> {$line['productname']}",true);
    $form->newForm("<small>Delete From BOM:</small> ".$view->trashBtnSm('/products/editbom?action=remove&id='.$_REQUEST['id'].'&prokey='.$line['prokey'],true));
    $form->hiddenInput('action','amend');
    $form->hiddenInput('id',$_REQUEST['id']);
    $form->hiddenInput('uid',$server->currentUserID);
    $form->hiddenInput('prokey',$line['prokey']);
    $form->inputCapture('qty','Qty.',$line['qty'],true,"This is the quatity subtracted from inventory for each of this products units.");
    $form->labelContent('Number',$line['number']);
    $form->labelContent('Description',$line['description']);
    $form->submitForm('Amend',false,$view->PageData['approot'].'/products/bom?prokey='.$line['prokey']);
    $form->endForm();
    $view->footer();
}

function addendumDisplay () {
    global $server;
    $product = new Product($server->pdo,$_REQUEST['prokey']);
    $view = $server->getViewer("BOM: Addendum");
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->h1("<small>Addendum for:</small> {$product->pDescription}",true);
    $form->newForm("BOM: Addendum");
    $form->hiddenInput('action','add');
    $form->hiddenInput('uid',$server->currentUserID);
    $form->hiddenInput('prokey',$_REQUEST['prokey']);
    $form->inputCapture('qty','Qty.',null,true);
    $form->inputCapture('number',htmlentities('Part#'),null,true);
    $form->submitForm('Add',true);
    $form->endForm();
    $view->footer();
}

function displayRebase () {
    global $server;
    $product = new Product($server->pdo,$_REQUEST['prokey']);
    $view = $server->getViewer("Rebase BOM with File");
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->h2("Rebase BOM",true);
    $view->bgInfoParagraph(
        "Import a new CVS file in format: 'part number(with or without hyphen)',
        'description','qty'; to be used to rebase an existsing BOM for product; <strong>{$product->pDescription}.",
        true
    );
    $form->newMultipartForm('Import CSV');
    $form->hiddenInput('action','rerebase');
    $form->hiddenInput('prokey',$_REQUEST['prokey']);
    $form->hiddenInput('uid',$server->currentUserID);
    $form->fileUpload(FileIndexer::UPLOAD_NAME,true);
    $form->submitForm('Rebase',true);
    $form->endForm();
    $view->footer();
}

function displayMultiDelete () {
    global $server;
    $product = new Product($server->pdo,$_REQUEST['prokey']);
    $view = $server->getViewer('Edit BOM: MultiDelete');
    $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->h1("<small>Delete Multiple From:</small> {$product->pDescription}");
    $form->newInlineForm();
    $form->hiddenInput('action','deletemultiple');
    $form->inlineSubmit('Delete Multiple',true,$view->PageData['approot'].'/products/bom?prokey='.$_REQUEST['prokey']);
    $view->responsiveTableStart(['Delete',htmlentities("Part #"),'Description','Qty.']);
    foreach($product->pBOM as $part) {
        echo "<tr><td>";
        $form->inlineCheckbox('delete[]',"Delete",$part['id']);
        echo "</td><td>{$part['number']}</td><td>{$part['description']}</td><td>{$part['qty']}</td></tr>";
    }
    $view->responsiveTableClose();
    $form->endInlineForm();
    $view->addScrollTopBtn();
    $view->footer();
}