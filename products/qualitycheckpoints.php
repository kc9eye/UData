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

$server->userMustHavePermission('addProduct');

$products = new Products($server->pdo);
$cells = new WorkCells($server->pdo);

if (empty($_REQUEST)) {
    $server->newEndUserDialog("You must select a cell or product.",DIALOG_FAILURE,$server->config['application-root'].'/products/main');
}
if (!empty($_REQUEST['action'])) {
    switch ($_REQUEST['action']) {
        case 'add':
            $server->processingDialog(
                [$products,'addCheckPoint'],
                [$_REQUEST],
                $server->config['application-root']."/products/qualitycheckpoints?prokey={$_REQUEST['prokey']}"
            );
        break;
        case 'remove':
            $server->processingDialog(
                [$products,'removeCheckPoint'],
                [$_REQUEST['prokey'],$_REQUEST['id']],
                $server->config['application-root']."/products/qualitycheckpoints?prokey={$_REQUEST['prokey']}"
            );
        break;
        }
}
$cnt = 1;
$view = $server->getViewer("Products: Quality Control");
$form = new FormWidgets($view->PageData['wwwroot'].'/scripts');

$form->newForm("<small>Product:</small> ".$products->getProductDescriptionFromKey($_REQUEST['prokey']). 
    " <a href='{$server->config['application-root']}/products/viewproduct?prokey={$_REQUEST['prokey']}'
    class='btn btn-sm btn-info' role='button'><span class='glyphicon glyphicon-arrow-left'></span> Back</a>"
);
echo "<div class='table-responsive'><table class='table'>\n";
echo "<tr><th>Number</th><th>Description</th><th>Cell</th><th>Remove</th></tr>\n";
foreach($products->getCheckPoints($_REQUEST['prokey']) as $row) {
    echo "<tr><td>{$cnt}</td><td>{$row['description']}</td><td>{$row['cell']}</td>";
    echo "<td>".$view->trashBtnSm("/products/qualitycheckpoints?action=remove&id={$row['id']}&prokey={$_REQUEST['prokey']}",true)."</td></tr>";
    $cnt++;
}
echo "</table>,</div>\n";
$form->hiddenInput('action','add');
$form->hiddenInput('uid',$server->currentUserID);
$form->hiddenInput('prokey',$_REQUEST['prokey']);
$form->labelContent('Add','Quality Control Point');
$form->inputCapture('description',"Control Point",null,true);
$select = array();
foreach($cells->getCellsFromKey($_REQUEST['prokey']) as $row) {
    array_push($select,[$row['id'],$row['cell_name']]);
}
$form->selectBox('cellid','Associate With',$select);
$form->submitForm('Add',false,$server->config['application-root']."/products/viewproduct?prokey={$_REQUEST['prokey']}");
$form->endForm();
$view->addScrollTopBtn();
$view->footer();