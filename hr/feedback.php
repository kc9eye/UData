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
            addNewComment();
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
                $server->config['application-root'].'/hr/feedback?action=view&id='.$_REQUEST['cid']
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
    echo 
        '<div id="content">
            <form id="commentForm">
                <h1>Add Comment to:'.$emp->getFullName().'</h1>
                <input type="hidden" name="action" value="add" />
                <input type="hidden" name="eid" value="'.$_REQUEST['id'].'" />
                <input type="hidden" name="uid" value="'.$server->currentUserID.'" />
                <input type="hidden" name="fid" value="" />';
    if ($server->checkPermission("approveProbation")) {
        echo 
        '<div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="probation" value="1" />
            <label class="form-check-label" for="probation">Begin Probation</label>
        </div>';
    }
    echo'
                <div class="form-group">
                    <label class="form-label" for="subject">Subject</label>
                    <input class="form-control" type="text" name="subject" />
                </div>
                <div class="form-group">
                    <label class="form-label" for="comments">Comment</label>
                    <textarea class="form-control" name="comments"></textarea>
                </div>
                <button class="btn btn-success" type="button" id="submitBtn">Submit</button>
            </form>';
    echo '</div>';
    echo 
    '<script>
            let pageForm = document.getElementById("commentForm");
            let btn = document.getElementById("submitBtn");
            btn.addEventListener("click",async (event)=>{
                event.preventDefault();
                btn.setAttribute("disabled","disabled");
                btn.innerHTML = "<span class=\'spinner-border spinner-border-sm\'></span>&#160;"+btn.innerHTML;
                let res = await fetch(
                    "'.$view->PageData['approot'].'/hr/feedback",
                    {method:"POST",body:new FormData(pageForm)}
                );
                document.getElementById("content").innerHTML = await res.text();
                btn.innerHTML = "Submit";
                btn.removeAttribute("disabled");
            });
        </script>';
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
            $view->hr();
        }
    }
    $view->footer();
}

function addNewComment () {
    global $server;
    $server->userMustHavePermission('editSupervisorComments');
    $handler = new SupervisorComments($server->pdo);
    $notify = new Notification($server->pdo,$server->mailer);

    try {
        if (isset($_REQUEST['probation'])) {
            $sql = "insert into employee_probation values (:id,now(),:eid,'30 days')";
            $pntr = $server->pdo->prepare($sql);
            if (!$pntr->execute([':eid'=>$_REQUEST['eid'],':id'=>uniqid()])) throw new Exception(print_r($pntr->errorInfo(),true));
        }
        if ($handler->addNewSupervisorFeedback($_REQUEST)) {
            $body = file_get_contents(INCLUDE_ROOT.'/wwwroot/templates/email/supervisorfeedback.html');
            $body .= "<a href='{$server->config['application-root']}/hr/feedback?action=view&id={$handler->newCommentID}'>View Supervisor Feedback</a>";
            $notify->notify('New Supervisor Comment','New Supervisor Comment',$body);
        }
        echo 
        '<a href="'.$server->config['application-root'].'/hr/viewemployee?id='.$_REQUEST['eid'].'" class="btn btn-success" role="button">
            Completed Successfully
        </a>';
    }
    catch(Exception $e) {
        trigger_error($e->getMessage(),E_USER_ERROR);
        echo '<p class="bg-danger">Something went wrong with the request</p>';
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
    $view->h1("<small>Addendum To:</small> {$emp->Profile['first']} {$emp->Profile['middle']} {$emp->Profile['last']} {$emp->Profile['other']}",true);
    $form->newMultipartForm();
    $form->hiddenInput('action','submit_note');
    $form->hiddenInput('cid',$_REQUEST['cid']);
    $form->hiddenInput('uid',$server->currentUserID);
    $view->h2('Feedback',true);
    $form->textArea('note',null,'',true,'Enter comments for the individual',true);
    $form->submitForm('Submit',false,$server->config['application-root'].'/hr/feedback?action=view&id='.$_REQUEST['cid']);
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