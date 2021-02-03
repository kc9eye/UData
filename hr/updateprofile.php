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

$server->userMustHavePermission('addNewProfile');

if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'update': handleData(); break;
        default: editProfileDisplay(); break;
    }
}
else {
    editProfileDisplay();
}

function editProfileDisplay () {
    global $server;
    include('submenu.php');

    $emp = new Employee($server->pdo,$_REQUEST['id']);

    $view = $server->getViewer("HR: Edit Profile");
    $view->sideDropDownMenu($submenu);
    $view->h1("<small>Edit:</small> {$emp->Profile['first']} {$emp->Profile['middle']} {$emp->Profile['last']} {$emp->Profile['other']}");
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');

    $form->newMultipartForm('Add New Employee Profile');
    $form->hiddenInput('action','update');
    $form->hiddenInput('eid',$_REQUEST['id']);
    $form->hiddenInput('uid',$server->currentUserID);
    $form->hiddenInput('fid',$emp->Employee['photo_id']);
    $form->hiddenInput('pid',$emp->Employee['pid']);
    $form->inputCapture('start_date','Start Date',$emp->Employee['start_date'],['dateISO'=>'true']);
    $form->inputCapture('end_date','End Date',$emp->Employee['end_date']);
    $form->selectBox('status','Status',[
        ['Full Time','Full Time'],
        ['Part Time','Part Time'],
        ['Moonlighter','Moonlighter'],
        ['Seasonal','Seasonal'],
        ['Temporary','Temporary']
    ],true);
    $form->inputCapture('smid','SMID',$emp->Employee['smid'],true);
    $form->inputCapture('first','First Name',$emp->Profile['first'],true);
    $form->inputCapture('middle','Middle/Int.',$emp->Profile['middle']);
    $form->inputCapture('last','Last Name',$emp->Profile['last'], true);
    $form->inputCapture('other','Jr./Sr./Other',$emp->Profile['other']);
    $form->emailCapture('email','Primary Email',$emp->Profile['email']);
    $form->emailCapture('alt_email','Alt. Email',$emp->Profile['alt_email']);
    $form->inputCapture('address','Address',$emp->Profile['address']);
    $form->inputCapture('address_other','Address Cont.',$emp->Profile['address_other']);
    $form->inputCapture('city','City',$emp->Profile['city']);
    $form->inputCapture('state_prov','State/Prov.',$emp->Profile['state_prov']);
    $form->inputCapture('postal_code','Postal/Zip',$emp->Profile['postal_code']);
    $form->inputCapture('home_phone','Home Phone',$emp->Profile['home_phone']);
    $form->inputCapture('cell_phone','Cell Phone',$emp->Profile['cell_phone']);
    $form->inputCapture('alt_phone','Other Phone',$emp->Profile['alt_phone']);
    $form->inputCapture('e_contact_name','Emergency Contact',$emp->Profile['e_contact_name']);
    $form->inputCapture('e_contact_number','Emergency Phone',$emp->Profile['e_contact_number']);
    $form->inputCapture('e_contact_relation','Emergency Relationship',$emp->Profile['e_contact_relation']);
    $form->fileUpload(FileIndexer::UPLOAD_NAME,'Photo',null,false,false,"Optional photo file upload.");
    $form->submitForm('Update',false,$server->config['application-root'].'/hr/viewemployee?id='.$_REQUEST['id']);
    $form->endForm();
    $view->footer();
}

function handleData () {
    global $server;
    if ($_FILES[FileIndexer::UPLOAD_NAME]['size'] != 0)
        $_REQUEST['fid'] = handleUploadedFile();

    $handler = new Employees($server->pdo);
    $server->processingDialog(
        [$handler,'updateEmployee'],
        [$_REQUEST],
        $server->config['application-root'].'/hr/viewemployee?id='.$_REQUEST['id']
    );
}

function handleUploadedFile () {
    global $server;
    $upload = new FileUpload(FileIndexer::UPLOAD_NAME);
    if ($upload->multiple) 
        $server->newEndUserDialog(
            'Multiple files are not permitted in this operation.',
            DIALOG_FAILURE,
            $server->config['application-root'].'/hr/updateprofile?id='.$_REQUEST['id']
        );

    $indexer = new FileIndexer($server->pdo,$server->config['data-root']);
    //Remmove old file index if it exists.
    if (!empty($_REQUEST['fid'])) {
        try {
            if (!$indexer->removeIndexedFiles($indexer->getIndexByID($_REQUEST['fid']))) throw new Exception("File removal failed.");
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_ERROR);
        }
    }

    //Index the new file if it exists.
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
