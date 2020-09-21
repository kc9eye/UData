<?php
/* This file is part of Udata.
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

$products = new Products($server->pdo);

if (!empty($_REQUEST['action'])) {
    $cell = new WorkCells($server->pdo);
    if ($cell->verifyUniqueCell($_REQUEST['cell_name'])) 
        $server->processingDialog(
            [$cell,'addNewCell'],
            [$_REQUEST],$server->config['application-root'].'/cells/main?action=list&prokey='.$_REQUEST['prokey']
        );
    else 
        $server->newEndUserDialog(
            "That cell name is already taken, choose another or realign existsing cell",
            DIALOG_FAILURE,
            $server->config['application-root'].'/cells/createworkcell?prokey='.$_REQUEST['prokey']
        );
}
if (empty($_REQUEST['prokey'])) {
    $formtitle = 'Create Work Cell';
    $active = [];
    foreach($products->getActiveProducts() as $row) {
        array_push($active,[$row['product_key'],$row['description']]);
    }
}
if (empty($formtitle)) {
    $formtitle = "<small>Create Cell For:</small> ".
    $products->getProductDescriptionFromKey($_REQUEST['prokey']).
    " <a href='{$server->config['application-root']}/products/viewproduct?prokey={$_REQUEST['prokey']}' class='btn btn-sm btn-info' role='button'>
    <span class='glyphicon glyphicon-arrow-left'></span> Back</a>";
}
else {
    $formtitle = "Create New Work Cell";
}

$view = $server->getViewer('Products: Work Cell');
$form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
$form->newForm($formtitle);
$form->hiddenInput('action','create');
$form->hiddenInput('uid',$server->currentUserID);
if (!empty($active)) 
    $form->selectBox('prokey','For Product',$active,true,"Which product is this cell associated with?");
else
    $form->hiddenInput('prokey', $_REQUEST['prokey']);
$form->inputCapture('cell_name','Cell Name',null,true);
$form->submitForm('Add Cell',false,$_SERVER['HTTP_REFERER']);
$form->endForm();

$view->footer();