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
$server->userMustHavePermission('approveCellSafety');

if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'feedback': giveFeedback(); break;
        default: rejectReviewDisplay(); break;
    }
}
else {
    rejectReviewDisplay();
}

function rejectReviewDisplay() {
    global $server;

    $cell = new WorkCell($server->pdo,$_REQUEST['cellid']);
    $author = new User($server->pdo,$cell->SafetyReview['oid']);
    $reviewer = new User($server->pdo,$server->currentUserID);
    $view = $server->getViewer('WorkCell: Reject Safety Asses.');
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');

    $form->newForm('Reason for Rejection');
    $form->hiddenInput('cellid',$_REQUEST['cellid']);
    $form->hiddenInput('oid_email',$author->username);
    $form->hiddenInput('aid_email',$reviewer->username);
    $form->hiddenInput('cell_name',$cell->Name);
    $form->hiddenInput('action','feedback');
    $form->textArea('review',null,'',true,'Review Feedback');
    $form->submitForm('Feedback');
    $form->endForm();
    $view->footer();
}

function giveFeedBack () {
    global $server;
    $body = "<h1><img src='favicon-16x16.png' />UData</h1>";
    $body .= "<p>The safety assessment for work cell <strong>{$_REQUEST['cell_name']}</strong> has been rejected.";
    $body .= "The reviewer left the following feedback regarding the rejection:</p>";
    $body .= "<hr><p>{$_REQUEST['review']}</p><hr><p>The submitted document can be found at ";
    $body .= "<a href='{$server->config['application-root']}/cells/cellsafety?action=rejected&cellid={$_REQUEST['cellid']}'>{$_REQUEST['cell_name']}</a>";
    $server->processingDialog(
        [$server->mailer,'sendMail'],
        [['to'=>$_REQUEST['oid_email'],'subject'=>'Safety Assessment Feedback','body'=>$body]],
        $server->config['application-root'].'/cells/main'
    );
}