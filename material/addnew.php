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
include('submenu.php');

$server->userMustHavePermission('addMaterial');

if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'add':
            $materials = new Materials($server->pdo);
            if ($materials->verifyMaterial($_REQUEST['number'])) numberExists();
            $server->processingDialog([$materials,'addNewMaterial'],[$_REQUEST],$server->config['application-root'].'/material/main');
        break;
        default:formDisplay();
    }
}
else 
    formDisplay();

function formDisplay () {
    global $server,$submenu;
    $view = $server->getViewer('Material:Add New Material');
    $view->sideDropDownMenu($submenu);
    $view->h1('<small>Material:</small> Add New',true);
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    $form->newForm();
    $form->hiddenInput('action','add');
    $form->hiddenInput('uid',$server->currentUserID);
    $form->inputCapture('number','Material#',null,true);
    $form->inputCapture('description','Description',null,true);
    $form->inputCapture('uom','UOM','0',false,"Optional");
    $form->inputCapture('cat','Category','0',false,"Optional");
    $form->submitForm('Add',true,$server->config['application-root'].'/material/main');
    $form->endForm();
    $view->footer();
}

function numberExists () {
    global $server;
    $server->newEndUserDialog(
        "That number already exists in the database.",
        DIALOG_FAILURE,
        $server->config['application-root'].'/material/addnew'
    );
}