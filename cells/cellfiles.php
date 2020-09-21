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
            $file = new FileUpload(FileIndexer::UPLOAD_NAME);
            $_REQUEST['storage'] = $server->config['data-root'];
            $server->processingDialog(
                [$handler,'addFileToCell'],
                [$_REQUEST,$file],
                $server->config['application-root']."/cells/cellfiles?cellid={$_REQUEST['cellid']}"
            );
        break;
        case 'delete':
            $indexer = new FileIndexer($server->pdo,$server->config['data-root']);
            // $pntr = $server->pdo->prepare('SELECT * FROM cell_files WHERE id = ?');
            // $pntr->execute([$_REQUEST['id']]);
            // $file = $pntr->fetchAll(PDO::FETCH_ASSOC);
            // // $server->getDebugViewer(var_export($file,true));
            // $server->getDebugViewer(var_export($indexer->getIndexByID($file[0]['fid']),true));
            $handler = new WorkCells($server->pdo);
            $server->processingDialog(
                [$handler,'removeCellFile'],
                [$_REQUEST['id'],$server->config['data-root']],
                $server->config['application-root']."/cells/cellfiles?cellid={$_REQUEST['cellid']}"
            );
        break;
        default: main(); break;
    }
}
else main();

function main () {
    global $server;
    $cell = new WorkCell($server->pdo,$_REQUEST['cellid']);
    $indexer = new FileIndexer($server->pdo, $server->config['application-root']);
    $view = $server->getViewer("Cell: Files");
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    
    $view->h1(
        "<small>Files for cell:</small> {$cell->Name} "
        .$view->linkButton("/cells/main?id={$_REQUEST['cellid']}&action=view",
            "<span class='oi oi-arrow-left'></span> Back",'info',true
        )
    );
    $view->h2("<small>Associated with:</small> {$cell->Product}");
    $form->newMultipartForm("File Upload");
    $form->hiddenInput('action','add');
    $form->hiddenInput('uid',$server->currentUserID);
    $form->hiddenInput('cellid',$_REQUEST['cellid']);
    $form->fileUpload(FileIndexer::UPLOAD_NAME,'File',null,false,true,"Only one file of 2MB size or smaller is accepted.");
    $form->submitForm('Upload',true);
    $form->endForm();
    $view->hr();

    $view->responsiveTableStart(['Filename'],true);
    foreach($cell->Files as $row) {
        $file = $indexer->getIndexByID($row['fid']);
        echo "<tr><td>{$file[0]['upload_name']}";
        $view->insertTab();
        $view->trashBtnSm("/cells/cellfiles?action=delete&id={$row['id']}&cellid={$_REQUEST['cellid']}");
        echo "</td></tr>";
    }
    $view->responsiveTableClose(true);

    $view->footer();
}