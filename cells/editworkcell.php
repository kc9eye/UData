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

if (!empty($_REQUEST['action'])) {
    $wc = new WorkCells($server->pdo);
    switch($_REQUEST['action']) {
        case 'remove':
            $server->processingDialog(
                [$wc,'removeWorkCell'],
                [$_REQUEST['id'],$server->config['data-root']],
                $server->config['application-root'].'/cells/main?action=list&prokey='.$_REQUEST['prokey']
            );
        break;
        case 'update':
            $server->processingDialog(
                [$wc,'updateWorkCell'],
                [$_REQUEST],
                $server->config['application-root'].'/cells/main?action=view&id='.$_REQUEST['cellid']
            );
        break;
        default:
            updateDisplay();
        break;
    }
}
else 
    updateDisplay();

function updateDisplay () {
    global $server;
    $cell = new WorkCell($server->pdo,$_REQUEST['id']);
    $view = $server->getViewer("Products: Work Cell (Edit)");
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    echo "<div class='row'><div class='col-md-3'></div><div class='col-md-6 col-xs-12'>\n";
    $view->h2("<small>Work Cell:</small> {$cell->Name} "
        .$view->trashBtnSm("/cells/editworkcell?action=remove&id={$_REQUEST['id']}&prokey={$cell->ProductKey}",true));
    $view->h3("<small>Associated With:</small> {$cell->Product}");
    echo "</div><div class='col-md-3'></div></div>\n";
    $form->newForm("<small>Tranfer Cell:</small> ".$view->linkButton('/cells/transfer?id='.$_REQUEST['id'],'Start','info',true));
    $form->hiddenInput('action','update');
    $form->hiddenInput('cellid', $_REQUEST['id']);
    $form->hiddenInput('uid', $server->currentUserID);
    $form->hiddenInput('url',$view->PageData['approot'].'/cells/cellsafety?action=review');
    $form->inputCapture('cell_name','Description',$cell->Name,true);
    $form->submitForm('Update',false,$view->PageData['approot']."/cells/main?action=view&id={$_REQUEST['id']}");
    $form->endForm();

    $view->footer();
}