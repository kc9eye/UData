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

if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'add':
            handleData();
        break;
        default: 
            addNewFormDisplay();
        break;
    }
}
else addNewFormDisplay();

function addNewFormDisplay() {
    global $server;
    $server->userMustHavePermission('addNewProfile');
    include('submenu.php');

    $view = $server->getViewer('HR: Add New Profile');
    $view->sideDropDownMenu($submenu);
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');

    $form->newMultipartForm('Add New Employee Profile');
    $form->hiddenInput('action','add');
    $form->hiddenInput('uid',$server->currentUserID);
    $form->inputCapture('start_date','Start Date',date('Y/m/d'),['dateISO'=>'true']);
    $form->selectBox('status','Status',[
        ['Full Time','Full Time'],
        ['Part Time','Part Time'],
        ['Moonlighter','Moonlighter'],
        ['Seasonal','Seasonal'],
        ['Temporary','Temporary']
    ],true);
    $form->inputCapture('first','First Name',null,true);
    $form->inputCapture('middle','Middle/Int.');
    $form->inputCapture('last','Last Name',null, true);
    $form->inputCapture('other','Jr./Sr./Other');
    $form->emailCapture('email','Primary Email');
    $form->emailCapture('alt_email','Alt. Email');
    $form->inputCapture('address','Address');
    $form->inputCapture('address_other','Address Cont.');
    $form->inputCapture('city','City');
    $form->inputCapture('state_prov','State/Prov.');
    $form->inputCapture('postal_code','Postal/Zip');
    $form->inputCapture('home_phone','Home Phone');
    $form->inputCapture('cell_phone','Cell Phone');
    $form->inputCapture('alt_phone','Other Phone');
    $form->inputCapture('e_contact_name','Emergency Contact');
    $form->inputCapture('e_contact_number','Emergency Phone');
    $form->inputCapture('e_contact_relation','Emergency Relationship');
    $form->fileUpload(FileIndexer::UPLOAD_NAME,'Photo',null,false,false,"Optional photo file upload.");
    $form->submitForm('Add',true,$server->config['application-root'].'/hr/main');
    $form->endForm();
    $view->footer();
}

function handleData () {
    global $server;
    if ($_FILES[FileIndexer::UPLOAD_NAME]['size'] != 0)
        $_REQUEST['fid'] = handleUploadedFile();
    else
        $_REQUEST['fid'] = '';

    $handler = new Employees($server->pdo);
    $server->processingDialog(
        [$handler,'addNewEmployee'],
        [$_REQUEST],
        $server->config['application-root'].'/hr/main'
    );
}

function handleUploadedFile () {
    global $server;
    $upload = new FileUpload(FileIndexer::UPLOAD_NAME);
    if ($upload->multiple) 
        $server->newEndUserDialog(
            'Multiple files are not permitted in this operation.',
            DIALOG_FAILURE,
            $server->config['application-root'].'/hr/addnew'
        );

    $indexer = new FileIndexer($server->pdo,$server->config['data-root']);
    try {
        if (($result = $indexer->indexFiles($upload,$_REQUEST['uid'])) !== false) 
            return $result[0];
        else
            throw new Exception("Indexer failed to index the file.");
    }
    catch (Exception $e) {
        trigger_error($e->getMessage(),E_USER_WARNING);
        $rollback = [];
        foreach($indexer->indexed_ids as $id) {
            array_push($rollback,$indexer->getIndexByID($id));
        }
        $indexer->removeIndexedFiles($rollback);
        $server->newEndUserDialog(
            "There was an error while indexing the uploaded file.",
            DIALOG_FAILURE,
            $server->config['application-root'].'/hr/addnew'
        );
    }
}