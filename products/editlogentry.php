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

$product = new Products($server->pdo);

#Access control and request hadnling
$server->userMustHavePermission('editProductLog');
$entry = $product->getQCLogEntryByID($_REQUEST['id']);

if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'update':
            $server->processingDialog(
                [$product,'updateExistingQCLog'],
                [$_REQUEST],
                $server->config['application-root']."/products/viewproduct?prokey={$entry['prokey']}"
            );
        break;
        case 'delete':
            $server->processingDialog(
                [$product,'removeExistingQCLogEntry'],
                [$_REQUEST['id']],
                $server->config['application-root']."/products/viewproduct?prokey={$entry['prokey']}"
            );
        default:
            $server->newEndUserDialog(
                "I did not understand your request.",
                DIALOG_FAILURE,
                $server->config['application-root']."/products/editlogentry?id={$_REQUEST['id']}"
            );
        break;
    }
}

#Controller view starts here
$view = $server->getViewer("Products: Update QC Log");

$form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
$form->newForm(
    "<small>Edit:</small> Log Entry ".$view->trashBtnSm("/products/editlogentry?action=delete&id={$_REQUEST['id']}",true)
);
$form->hiddenInput('action','update');
$form->hiddenInput('uid',$server->currentUserID);
$form->inputCapture('sequence',htmlentities('Sequence#'),$entry['sequence_number'],true);
$form->inputCapture('serial',htmlentities('Serial#'),$entry['serial_number'],true);
$form->inputCapture('misc','Misc.',$entry['misc'],true);
$form->inputCapture('ftc','FTC',$entry['ftc'],true);
$form->inputCapture('comments','Comments',$entry['comments']);
$form->submitForm('Update',false,$view->PageData['approot'].'/products/viewproduct?prokey='.$entry['prokey']);
$form->endForm();
$view->footer();