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

$server->userMustHavePermission('inspectQC');
if (empty($_REQUEST['prokey'])) $server->redirect('/products/main');

if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'submit') {
    $production = new Products($server->pdo);
    $server->processingDialog([$production,'addToQcLog'],[$_REQUEST], $server->config['application-root']."/products/viewproduct?prokey={$_REQUEST['prokey']}");
}

$product = new Product($server->pdo,$_REQUEST['prokey']);
$view = $server->getViewer("Products: Quality Control");
$view->sideDropDownMenu($submenu);
$form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
$form->newForm("<small>Quality Control:</small> {$product->pDescription}");
$form->hiddenInput('action','submit');
$form->hiddenInput('uid',$server->currentUserID);
$form->inputCapture('serial',htmlentities('Unit Serial#'),null,true,"See your supervisor with questions.");
$form->inputCapture('misc',htmlentities('Misc. Info.'),null,true,"See your supervisor with questions.");
$cnt = 0;
foreach($product->pQualityControl as $row) {
    $good = empty($row['cellid']) ? '1' : "1:{$row['cellid']}";
    $bad = empty($row['cellid']) ? '0' : "0:{$row['cellid']}";
    $form->radioButtons("qc[{$cnt}]",'Checkpoint',["{$row['description']}"=>$good,"Defect"=>$bad],true);
    $cnt++;
}
$form->inputCapture('comments','Comments');
$form->submitForm('Submit',true);
$form->endForm();
$view->footer();