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

$server->userMustHavePermission('addProduct');

$products = new Products($server->pdo);

if (!empty($_REQUEST)) {
    try {
        $products->verifyProductDescription($_REQUEST['product_description']) ||
        $server->newEndUserDialog(
            "{$_REQUEST['product_description']} already exists, please try again.",
            DIALOG_FAILURE,
            $server->config['application-root'].'/products/addnewproduct'
        );

        (
            $products->addNewProduct($_REQUEST) &&
            $server->newEndUserDialog(
                "{$_REQUEST['product_description']} added successfully.",
                DIALOG_SUCCESS,
                $server->config['application-root'].'/products/viewproduct?prokey='.$products->NewProductKey
            )
        ) ||
        $server->newEndUserDialog(
            "Something went wrong with the request",
            DIALOG_FAILURE,
            $server->config['application-root'].'/products/addnewproduct'
        );
    }
    catch (Exception $e) {
        $products->removeProductFromMaster(['product_description'=>$_REQUEST['product_description']]);
        throw $e;
    }
}

#Form starts here, if request doesn't interrupt
$view = $server->getViewer('products: Add Product');
$form = new FormWidgets($view->PageData['approot'].'/scripts');
$form->newForm("Add New Product");
$form->hiddenInput('uid',$server->currentUserID);
$form->inputCapture('product_description','Description',null,true,"A uinque products run description.");
$form->hiddenInput('active_product','0'); #<-- Default value for following checkbox.
$form->checkBox('active_product',['Active?','Yes'],'1',false,"Is this product going in to products now?");
$form->submitForm();
$form->endForm();
$view->footer();