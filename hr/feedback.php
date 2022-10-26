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
            $server->userMustHavePermission('editSupervisorComments');
            $server->processingDialog(
                'addNewComment',
                [],
                $server->config['application-root'].'/hr/viewemployee?id='.$_REQUEST['id']
            );
        break;
        case 'view':
            viewCommentDisplay();
        break;
        case 'add_note':
            addCommentNote();
        break;
        case 'submit_note':
            $server->userMustHavePermission('editSupervisorComments');
            $server->processingDialog(
                'addendumNote',
                [],
                $server->config['application-root'].'/hr/feedback?id='.$_REQUEST['cid']
            );
        break;
        default:
            commentFormDisplay();
        break;
    }
}
else 
    commentFormDisplay();

function commentFormDisplay () {
    global $server;
    $server->userMustHavePermission('editSupervisorComments');
    include('submenu.php');
    $emp = new Employee($server->pdo,$_REQUEST['id']);
    $view = $server->getViewer('Employee Feedback');
    $view->sideDropDownMenu($submenu);
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->h1("<small>Add Comment to:</small> {$emp->Profile['first']} {$emp->Profile['middle']} {$emp->Profile['last']} {$emp->Profile['other']}",true);
    $form->newMultipartForm();
    $form->hiddenInput('action','add');
    $form->hiddenInput('eid',$_REQUEST['id']);
    $form->hiddenInput('uid',$server->currentUserID);
    $view->h2('Feedback',true);
    $form->inputCapture('subject','Subject',null,true);
    $form->textArea('comments',null,'',true,'Enter comments for the individual',true);
    $form->fileUpload(FileIndexer::UPLOAD_NAME,'',null,false,false,"Uploaded file can not exceed ".FileUpload::MAX_UPLOAD_SIZE." bytes.");
    $form->submitForm('Submit',false,$server->config['application-root'].'/hr/viewemployee?id='.$_REQUEST['id']);
    $form->endForm();
    $view->footer();
}

function viewCommentDisplay () {
    global $server;
    $server->userMustHavePermission('viewSupervisorComments');
    include('submenu.php');

    $handler = new SupervisorComments($server->pdo);
    $comment = $handler->getComment($_REQUEST['id']);

    $view = $server->getViewer('HR: Comments');
    $view->sideDropDownMenu($submenu);
    $view->linkButton('/hr/viewemployee?id='.$comment['eid'],"<span class='glyphicon glyphicon-arrow-left'></span> Back");
    $view->responsiveTableStart();
    echo "<tr><th>Employee Name:</th><td>{$comment['name']}</td></tr>";
    echo "<tr><th>Comment ID:</th><td>{$comment['id']}</td></tr>";
    echo "<tr><th>Comment Author:</th><td>{$comment['author']}</td></tr>";
    echo "<tr><th>Comment Date/Time:</th><td>{$comment['date']}</td></tr>";
    echo "<tr><th>Subject:</th><td>{$comment['subject']}</td></tr>";
    echo "<tr><th>Comment:</th><td>{$comment['comments']}</td></tr>";
    if (!empty($comment['fid'])){
        $indexer = new FileIndexer($server->pdo,$server->config['data-root']);
        $index = $indexer->getIndexByID($comment['fid']);
        echo "<tr><td colspan='2'>";
        $view->responsiveImage($view->PageData['approot'].'/data/files?dis=inline&file='.$index[0]['file']);
        echo "</td></tr>";
    }
    $view->responsiveTableClose();
    $view->hr();
    if ($server->security->userHasPermission('eidtSupervisorComments')||$server->security->userHasPermission('adminAll')) {
        $view->linkButton('/hr/feedback?action=add_note&cid='.$_REQUEST['id'].'&eid='.$comment['eid'],"Add Note");
    }
    if (!empty(($adds = $handler->getCommentNotes($_REQUEST['id'])))) {
        $view->h3('Addendums');
        foreach($adds as $row) {
            $user = new User($server->pdo,$row['uid']);
            $view->responsiveTableStart();
            echo "<tr><th>Date:</th><td>".$view->formatUserTimestamp($row['gen_date'],true)."</td></tr>";
            echo "<tr><th>Author:</th><td>".$user->getFirstName()." ".$user->getLastName()."</td></tr>";
            echo "<tr><th>Addendum</th><td>".$row['note']."</td></tr>";
            $view->responsiveTableClose();
        }
    }
    $view->footer();
}

function addNewComment () {
    global $server;
    $server->userMustHavePermission('editSupervisorComment');
    $handler = new SupervisorComments($server->pdo);
    $notify = new Notification($server->pdo,$server->mailer);
    try {
        $upload = new FileUpload(FileIndexer::UPLOAD_NAME);
        if ($upload !== false) {
            if ($upload->multiple) {
                $server->newEndUserDialog(
                    "Only one file may be associated with this entry.",
                    DIALOG_FAILURE,
                    $server->config['application-root'].'/hr/feedback?id='.$_REQUEST['eid']
                );
            }
            $indexer = new FileIndexer($server->pdo,$server->config['data-root']);
            if (($indexed = $indexer->indexFiles($upload,$_REQUEST['uid'])) !== false)
                $_REQUEST['fid'] = $indexed[0];
        }
        else $_REQUEST['fid'] = '';
    }
    catch (UploadException $e) {
        if ($e->getCode() != UPLOAD_ERR_NO_FILE) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
        $_REQUEST['fid'] = '';
    }

    if ($handler->addNewSupervisorFeedback($_REQUEST)) {
        $body = file_get_contents(INCLUDE_ROOT.'/wwwroot/templates/email/supervisorfeedback.html');
        $body .= "<a href='{$server->config['application-root']}/hr/feedback?action=view&id={$handler->newCommentID}'>View Supervisor Feedback</a>";
        $notify->notify('New Supervisor Comment','New Supervisor Comment',$body);
        return true;
    }
    else {
        return false;
    }
}

function addCommentNote() {
    global $server;
    $server->userMustHavePermission('editSupervisorComment');
    include('submenu.php');
    $emp = new Employee($server->pdo,$_REQUEST['eid']);
    $view = $server->getViewer('Comment Addendum');
    $view->sideDropDownMenu($submenu);
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->h1("<small>Add Comment to:</small> {$emp->Profile['first']} {$emp->Profile['middle']} {$emp->Profile['last']} {$emp->Profile['other']}",true);
    $form->newMultipartForm();
    $form->hiddenInput('action','submit_note');
    $form->hiddenInput('cid',$_REQUEST['cid']);
    $form->hiddenInput('uid',$server->currentUserID);
    $view->h2('Feedback',true);
    $form->inputCapture('note','Subject',null,true);
    $form->textArea('comments',null,'',true,'Enter comments for the individual',true);
    $form->submitForm('Submit',false,$server->config['application-root'].'/hr/feedback?id='.$_REQUEST['cid']);
    $form->endForm();
    $view->footer();
}

function addendumNote() {
    global $server;
    $sql = 'insert into supervisor_comment_notes values (:id,:cid,now(),:uid,:note)';
    $insert = [
        ':id'=>uniqid(),
        ':cid'=>$_REQUEST['cid'],
        ':uid'=>$_REQUEST['uid'],
        ':note'=>$_REQUEST['note']
    ];
    try {
        $pntr = $server->pdo->prepare($sql);
        if (!$pntr->execute($insert)) throw new Exception(print_r($pntr->errorInfo(),true));
        return true;
    }
    catch(Exception $e) {
        trigger_error($e->getMessage(),E_USER_WARNING);
        return false;
    }
}