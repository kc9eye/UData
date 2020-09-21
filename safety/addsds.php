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
include('submenu.php');

$server->userMustHavePermission('addSDS');
$sds = new SDSHandler($server->pdo, $server->config['data-root']);

if (!empty($_REQUEST)) {
    $server->processingDialog([$sds, 'addUploaded'],[$_REQUEST]);
}

$view = $server->getViewer('Add SDS');
$view->sideDropDownMenu($submenu);

$form = new FormWidgets($view->PageData['wwwroot'].'/scripts');

$form->newMultipartForm('Add SDS');
$form->hiddenInput('MAX_FILE_SIZE','100000000');
$form->hiddenInput('uid',$server->security->secureUserID);
$form->inputCapture('name','Product Name',null,true);
$form->inputCapture('dist','Distributor',null,true);
$form->inputCapture('used','Location Used',null,true);
$form->inputCapture('meta','Keywords');
$form->fileUpload(FileIndexer::UPLOAD_NAME,'File',null,true,true);
$form->submitForm('Add SDS',false,$server->config['application-root'].'/safety/main');
$form->endForm();

$view->footer();