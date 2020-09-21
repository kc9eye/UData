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

$server->userMustHavePermission('editBOM');

if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'deletemultiple':
            if (!empty($_REQUEST['delete'])) 
                $server->processingDialog(
                    [new BillOfMaterials($server->pdo),'deleteFromIDArray'],
                    [$_REQUEST['delete']],
                    $server->config['application-root'].'/products/unusedbommats?prokey='.$_REQUEST['prokey']
                );
            else
                $server->newEndUserDialog(
                    "You must select items to delete!",
                    DIALOG_FAILURE,
                    $server->config['application-root'].'/products/unusedbommats?prokey='.$_REQUEST['prokey']
                );
        break;
        default: main(); break;
   }
}
else {
   main();
}

function main () {
   global $server;
   $product = new Product($server->pdo,$_REQUEST['prokey']);
   $bom = new BillOfMaterials($server->pdo);
   $unassigned = $bom->getUnassignedMaterial($_REQUEST['prokey']);
   $view = $server->getViewer('Unutilized Material:'.$product->getProductDescription());
   $view->printButton();
   $view->h1(
       '<small>Material Unassigned For:</small> '
       .$product->getProductDescription()
       .'&#160;'.$view->linkButton('/products/bom?prokey='.$_REQUEST['prokey'],"<span class='glyphicon glyphicon-arrow-left'></span>Back",'info',true)
    );
    $view->beginBtnCollapse("Show/Hide WorkCell List");
    $view->h3("Current Work Cells");
    $view->responsiveTableStart(['Name','Date Created']);
    foreach($product->getWorkCells() as $row) {
        echo "<tr><td><a href='{$view->PageData['approot']}/cells/main?action=view&id={$row['id']}'>{$row['cell_name']}</a></td>";
        echo "<td>{$row['_date']}</td></tr>\n";
    }
    $view->responsiveTableClose();
    $view->endBtnCollapse();
    $view->hr();
    $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');
    $form->newInlineForm();
    $form->hiddenInput('action','deletemultiple');
    $form->inlineSubmit('Delete Multiple',true);
    $view->responsiveTableStart(['Delete','Part#','Description','Qty','Edit']);
    foreach($unassigned as $row) {
        echo "<tr><td>";
        $form->inlineCheckbox('delete[]','Delete',$row['id']);
        echo "</td><td>{$row['number']}</td><td>{$row['description']}</td><td>{$row['qty']}</td>";
        echo "<td>".$view->editBtnSm("/products/editbom?id={$row['id']}",true)."</td></tr>\n";
    }
    $view->responsiveTableClose();
    $form->endInlineForm();
    $view->addScrollTopBtn();
   $view->footer();
}