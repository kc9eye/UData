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

if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'add':
            if (!$server->checkPermsArray(['editSBM','adminAll'])) $server->notAuthorized(true);
            uploadNewMinutes();
        break;
        case 'delete':
            if (!$server->checkPermsArray(['editSBM','adminAll'])) $server->notAuthorized(true);
            $server->processingDialog(
                [new SafetyBoardMinutes($server->pdo,$server->config['data-root']),'deleteSBMFileEntry'],
                [$_REQUEST],
                $server->config['application-root'].'/safety/sbm'
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
    include('submenu.php');
    $edit = $server->checkPermsArray(['editSBM','adminAll']);
    $sbm = new SafetyBoardMinutes($server->pdo,$server->config['data-root']);
    $view = $server->getViewer('Safety Board Minutes');
    $view->sideDropDownMenu($submenu);
    $view->h1('Safety Meeting Minutes');
    if ($edit) {
        $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');
        $view->bold('Upload Minutes:(<2MB)');
        $view->insertTab();
        $form->newInlineMultipartForm();
        $form->hiddenInput('action','add');
        $form->hiddenInput('uid',$server->currentUserID);
        $form->inlineInputCapture('date','Meeting Date',null,['dateISO'=>'true']);
        $view->insertTab();
        $form->inlineFileUpload(FileIndexer::UPLOAD_NAME,false,true);
        $form->inlineSubmit('Upload');
        $form->endInlineForm();
    }
    $th = $edit ? ['Meeting Date','Delete'] : ['Meeting Date'];
    $view->responsiveTableStart($th);
    foreach($sbm->getListing() as $row) {
        echo "<tr><td><a href='{$view->PageData['approot']}/data/files?dis=inline&file={$row['file']}'>";
        $view->formatUserTimestamp($row['meeting_date']);
        echo " Meeting</a></td>";
        if ($edit) echo "<td>".$view->trashBtnSm("/safety/sbm?action=delete&ref={$row['id']}:{$row['fid']}",true)."</td>";
        echo "</tr>";
    }
    $view->responsiveTableclose();
    $view->footer();
}

function uploadNewMinutes () {
    global $server;
    try {
        $upload = new FileUpload(FileIndexer::UPLOAD_NAME);
    }
    catch (UploadException $e) {
        $server->newEndUserDialog(
            $e->getMessage(),
            DIALOG_FAILURE,
            $server->config['application-root'].'/safety/sbm'
        );
    }
    $server->processingDialog(
        [new SafetyBoardMinutes($server->pdo,$server->config['data-root']), 'addNewSBMFile'],
        [$_REQUEST, $upload],
        $server->config['application-root'].'/safety/sbm'
    );
}