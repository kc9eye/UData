<?php
/*
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

$server->userMustHavePermission('editWorkCell');

if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'add':
            $handler = new WorkCells($server->pdo); 
            $server->processingDialog(
                [$handler,"addNewCellPrint"],
                [$_REQUEST],
                $server->config['application-root'].'/cells/cellprints?cellid='.$_REQUEST['cellid']
            );
        break;
        case 'delete':
            $handler = new WorkCells($server->pdo);
            $server->processingDialog(
                [$handler,'removeCellPrint'],
                [$_REQUEST['id']],
                $server->config['application-root'].'/cells/cellprints?cellid='.$_REQUEST['cellid']
            );
        break;
        default: main();
    }
}
else main();

function main () {
    global $server;
    $cell = new WorkCell($server->pdo,$_REQUEST['cellid']);
    $view = $server->getViewer("Cell: Edit Prints");
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');

    $view->h1("<small>Edit Prints for:</small> {$cell->Name} ".$view->linkButton('/cells/main?action=view&id='.$_REQUEST['cellid'],'Back','info',true));
    $view->h2("<small>Associated with:</small> {$cell->Product}");

    $form->newForm("Add Prints");
    $form->hiddenInput('action','add');
    $form->hiddenInput('cellid',$_REQUEST['cellid']);
    $form->hiddenInput('uid',$server->currentUserID);
    $form->inputCapture('number',"Print Number",null,true);
    $form->submitForm('Add',true);
    $form->endForm();
    $view->hr();
    $view->responsiveTableStart(['Print Number'],true);
    foreach($cell->Prints as $row) {
        echo "<tr><td>{$row['number']}";
        $view->insertTab();
        $view->trashBtnSm('/cells/cellprints?action=delete&id='.$row['id'].'&cellid='.$_REQUEST['cellid']);
        echo "</td></tr>";
    }
    $view->responsiveTableClose(true);

    $view->footer();
}