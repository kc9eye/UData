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

//Control
if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'seeking':
            $server->userMustHavePermission('editCellSafety');
            $handler = new WorkCells($server->pdo);
            $server->processingDialog(
                [$handler, 'seekSafetyApproval'],
                [$_REQUEST, $server->mailer],
                $server->config['application-root'].'/cells/main?id='.$_REQUEST['name']
            );
        break;
        case 'review':
            reviewDisplay();
        break;
        case 'approve':
            $server->userMustHavePermission('approveCellSafety');
            $handler = new WorkCells($server->pdo);
            $server->processingDialog(
                [$handler,'approveSafety'],
                [$_REQUEST, $server->security],
                $server->config['application-root'].'/cells/main?id='.$_REQUEST['docname']
            );
        case 'rejected':
            rejectedEditDisplay();
        break;
        case 'edit_rejected':
            $handler = new WorkCells($server->pdo);
            $server->processingDialog(
                [$handler, 'editRejected'],
                [$_REQUEST, $server->mailer],
                $server->config['application-root'].'/cells/main?id='.$_REQUEST['name']
            );
        break;
        default:
            editDisplay();
        break;
    }
}
else {
    editDisplay();
}

//Views
function editDisplay () {
    global $server;
    $server->userMustHavePermission('editCellSafety');
    $pageOptions = [
        'headinserts'=>[
            "<script src='{$server->config['application-root']}/third-party/tinymce/tinymce.min.js'></script>"
        ]
    ];
    $cell = new WorkCell($server->pdo,$_REQUEST['cellid']);
    $view = $server->getViewer("Work Cells: Safety Assess. Edit",$pageOptions);
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    if (empty($cell->Safety['body'])) $cell->Safety['body'] = "<strong>Process Steps:</strong><br /><strong>Hazards:</strong><br /><strong>Required PPE:</strong><br />";
    $form->newForm("Edit: {$cell->Name}");
    $form->hiddenInput('uid', $server->currentUserID);
    $form->hiddenInput('name', $_REQUEST['cellid']);
    $form->hiddenInput('cellname', $cell->Name);
    $form->hiddenInput('action', 'seeking');
    $form->hiddenInput('url', $view->PageData['approot'].'/cells/cellsafety?action=review');
    $form->textArea('body', null, $cell->Safety['body'],true);
    $form->submitForm('Submit for Approval', false, $view->PageData['approot'].'/cells/main?id='.$_REQUEST['cellid']);
    $form->endForm();
    echo "<script>
    $(document).ready(function(){
        tinymce.init({
            selector: 'textarea',
            plugins: 'autoresize'
        });
    });
    </script>\n";
    $view->footer();
}

function reviewDisplay () {
    global $server;
    $server->userMustHavePermission('approveCellSafety');

    $cell = new WorkCell($server->pdo, $_REQUEST['name']);
    if (is_null($cell->SafetyReview)) {
        $server->newEndUserDialog(
            "Couldn't find any data to review for this document. Perhaps it has already been approved or rejected.",
            DIALOG_FAILURE,
            $server->config['application-root']
        ); 
    }
    $user = new User($server->pdo, $cell->SafetyReview['oid']);
    $reviewer = new User($server->pdo, $server->currentUserID);

    $view = $server->getViewer("WorkCells: Safety Asses. Review");
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');

    $view->h1("<small>Pending Changes for:</small> {$cell->Name}");
    $view->h2("<small>Changes Requested by:</small> {$user->firstname} {$user->lastname}");
    $view->wrapInCard($cell->SafetyReview['body']);
    $form->newForm('Approve');
    $form->hiddenInput('aid',$server->currentUserID);
    $form->hiddenInput('username',$reviewer->username);
    $form->hiddenInput('docname',$cell->ID);
    $form->hiddenInput('action','approve');
    $form->hiddenInput('pending_id', $cell->SafetyReview['id']);
    if (!empty($cell->Safety)) 
        $form->hiddenInput('obsolete_id', $cell->Safety['id']);
    else
        $form->hiddenInput('obsolete_id','');
    $form->passwordCapture('password','Password',null,true,"Approvers Password");
    $form->submitForm('Approve',false,['label'=>'Reject','type'=>'danger','url'=>$view->PageData['approot'].'/cells/rejectcellsafety?cellid='.$cell->ID]);
    $form->endForm();
    $view->footer();
}

function rejectedEditDisplay() {
    global $server;
    $server->userMustHavePermission('editCellSafety');
    $pageOptions = [
        'headinserts'=>[
            "<script src='{$server->config['application-root']}/third-party/tinymce/tinymce.min.js'></script>"
        ]
    ];
    $cell = new WorkCell($server->pdo,$_REQUEST['cellid']);
    $view = $server->getViewer("Work Cells: Safety Assess. Edit",$pageOptions);
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    $form->newForm("Edit: {$cell->Name}");
    $form->hiddenInput('uid', $server->currentUserID);
    $form->hiddenInput('name', $_REQUEST['cellid']);
    $form->hiddenInput('cellname', $cell->Name);
    $form->hiddenInput('action', 'edit_rejected');
    $form->hiddenInput('docid',$cell->SafetyReview['id']);
    $form->hiddenInput('url', $view->PageData['approot'].'/cells/cellsafety?action=review');
    $form->textArea('body', null, $cell->SafetyReview['body'],true);
    $form->submitForm('Submit for Approval',  false, $view->PageData['approot'].'/cells/main?id='.$_REQUEST['cellid']);
    $form->endForm();
    echo "<script>
    $(document).ready(function(){
        tinymce.init({
            selector: 'textarea',
            plugins: 'autoresize'
        });
    });
    </script>\n";
    $view->footer();
}