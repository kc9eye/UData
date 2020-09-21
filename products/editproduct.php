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
include('./submenu.php');

#Access control
$server->userMustHavePermission('addProduct');
if (empty($_REQUEST['prokey'])) $server->redirect('/products/main');

$product = new Products($server->pdo);
$current_description = $product->getProductDescriptionFromKey($_REQUEST['prokey']);

#Request handling
if (!empty($_REQUEST['action'])&&$_REQUEST['action']=='update') {
    if ($_REQUEST['description'] != $current_description && !$product->verifyProductDescription($_REQUEST['description'])) {
        $server->newEndUserDialog(
            "That product description already exists, choose another.",
            DIALOG_FAILURE,
            $server->config['application-root']."/products/editproduct?prokey={$_REQUEST['prokey']}"
        );
    }
    else {
        $server->processingDialog(
            [$product,'updateExistingProduct'],
            [$_REQUEST['prokey'],$_REQUEST['description'],$_REQUEST['active']],
            $server->config['application-root']."/products/editproduct?prokey={$_REQUEST['prokey']}"
        );
    }
}


#start the view
$view = $server->getViewer('Products: Edit Product');
$form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
$form->newForm("Update Product");
$form->hiddenInput('action','update');
$form->hiddenInput('uid',$server->currentUserID);
$form->inputCapture('description','Product',$current_description,true);
$form->selectBox('active','Active',[['true','Active'],['false','Not Active']],true);
$form->submitForm('Submit',false,$view->PageData['approot']."/products/viewproduct?prokey={$_REQUEST['prokey']}");
$form->endForm();
$view->footer();