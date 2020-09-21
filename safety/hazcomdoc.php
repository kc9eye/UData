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

$doc = new DocumentViewer($server);

$doc->docURL = $server->config['application-root'].'/safety/hazcomdoc';
$doc->access = [DocumentViewer::EDIT_ACCESS_NAME=>['editHazcom'],DocumentViewer::APPROVE_ACCESS_NAME=>['approveHazcom']];
$doc->setDocument('Hazcom Document');

if (!empty($_REQUEST)) {
    switch($_REQUEST['action']) {
        case 'edit': 
            $doc->editDisplay($submenu) or $server->notAuthorized(); 
        break;
        case 'approve': 
            $doc->approveDisplay($submenu) or $server->notAuthorized(); 
        break;
        case 'submit': 
            $doc->submitForApproval($_REQUEST)
            && $server->newEndUserDialog('Document submitted for approval',DIALOG_SUCCESS,$doc->docURL)
            or $server->newEndUserDialog('Something went wrong, you may not have access to do this',DIALOG_FAILURE,$doc->docURL);
        break;
        case 'submitapproval': 
            $doc->approvalGranted($_REQUEST)
            && $server->newEndUserDialog('Document edition was approved',DIALOG_SUCCESS,$doc->docURL)
            or $server->newEndUserDialog('Something went wrong, you may not have access to do this',DIALOG_FAILURE,$doc->docURL);
        break;
        case 'reject': $doc->rollBack($_REQUEST['id'])
            && $server->newEndUserDialog('Document was rejected',DIALOG_SUCCESS,$doc->docURL)
            or $server->newEndUserDialog('Something went wrong, you may not have access to do this',DIALOG_FAILURE,$doc->docURL);
        break;
        default: 
            $doc->displayDoc($submenu); 
        break;
    }
}
else {
    $doc->displayDoc($submenu);
}