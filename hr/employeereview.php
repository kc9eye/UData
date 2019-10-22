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

if (!$server->checkPermsArray(['initEmployeeReview','reviewEmployee'])) {
    $server->notAuthorized(true);
}

if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'initreview':
            $server->userMustHavePermission('initEmployeeReview');
            $handler = new Employees($server->pdo);
            $server->processingDialog(
                [$handler,'initiateReview'],
                [$server,$_REQUEST['eid']],
                $server->config['application-root'].'/hr/employeereview?eid='.$_REQUEST['eid']
            );
        break;
        case 'update_appraisal':
            $server->userMustHavePermission('reviewEmployee');
            $handler = new Employees($server->pdo);
            $server->processingDialog(
                [$handler, 'updateUserAppraisal'],
                [$_REQUEST['id'],$_REQUEST['appraisal']],
                $server->config['application-root'].'/hr/viewemployee?id='.$_REQUEST['eid']
            );
        break;
        case 'insert_appraisal':
            $server->userMustHavePermission('reviewEmployee');
            $handler = new Employees($server->pdo);
            $server->processingDialog(
                [$handler,'insertUserAppraisal'],
                [$_REQUEST],
                $server->config['application-root'].'/hr/viewemployee?id='.$_REQUEST['eid']
            );
        break;
        case 'viewreview':
            $server->userMustHavePermission('initEmployeeReview');
            displayPastReview($_REQUEST['revid']);
        break;
        case 'printreview':
            $server->userMustHavePermission('initEmployeeReview');
            displayPrintReview($_REQUEST['revid']);
        break;
        default: main(); break;
   }
}
else {
    main();
}

function main () {
    global $server;
    $handler = new Employees($server->pdo);
    if ($handler->getReviewStatus($_REQUEST['eid'])) displayOngoingReview($handler->getOngoingReviewID($_REQUEST['eid']));
    else displayInitReview();
}

function displayOngoingReview ($revid) {
    global $server;
    include('submenu.php');
    $review = new Review($server->pdo,$revid);
    $view = $server->getViewer('Review: '.$review->getFullName());
    $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->sideDropDownMenu($submenu);
    $view->h1("<small>Ongoing Review for:</small> ".$review->getFullName());
    $view->h3("<small>Began:</small> ".$review->getStartDate());
    $view->h3("<small>Ends:</small> ".$review->getEndDate());
    echo "<span class='bg-info text-white'>The following data represents this timeframe: <mark>".Review::DATA_TIMEFRAME."</mark></span>";
    $view->bgInfoParagraph(
        "The purpose of conducting the Performace Appraisal is to:
        <ol>
            <li>Develop better <i>communication</i> between the employee and the supervisor</li>
            <li>Improve the <i>quality</i> of work and safety</li>
            <li>Increase productivity and promote employee development</li>
        </ol>"
    );

    //Training data
    $view->hr();
    $view->beginBtnCollapse("Show/Hide Training");
    $view->h2("Training");
    $view->responsiveTableStart(['Training','Date','Trainer']);
        foreach($review->getTraining() as $row) {
            echo "<tr><td>{$row['training']}</td><td>{$row['train_date']}</td><td>{$row['trainer']}</td></tr>\n";
        }
    $view->responsiveTableClose();
    $view->endBtnCollapse();

    //Attendace data presentation
    $view->hr();
    $view->beginBtnCollapse('Show/Hide Attendance');
    $view->h2("Review Attendance");
    $attendance = $review->getReviewAttendance();
    if (empty($attendance)) {
        $view->bold("No attendance incidents found.");
    }
    else {
    $view->responsiveTableStart(['Date','Arrived Late','Left Early','Absent','Excused','Reason']);
        foreach($review->getReviewAttendance() as $row) {
            if ($row['absent'] == 'true') $absent = 'Yes';
            else $absent = 'No';
            if ($row['excused'] == 'true') $excused = 'Yes';
            else $excused = 'No';
            echo "<tr><td>{$row['occ_date']}</td><td>{$row['arrive_time']}</td><td>{$row['leave_time']}</td><td>{$absent}</td><td>{$excused}</td><td>{$row['description']}</td></tr>\n";
        }
        $view->responsiveTableClose();
    }
    $view->endBtnCollapse();

    //Supervisor comments
    $view->hr();
    $view->beginBtnCollapse('Show/Hide Management Comments');
    $view->h2("Management Comments");
    $supervisor_comments = $review->getReviewManagementComments();
    if (empty($supervisor_comments)) {
        $view->bold("No management comments found.");
    }
    else {
        $view->responsiveTableStart(['Date','Author','Comments']);
        foreach($supervisor_comments as $row) {
            echo "<tr><td>{$row['date']}</td><td>{$row['author']}</td><td>{$row['comments']}</td></tr>\n";
        }
        $view->responsiveTableClose();
    }
    $view->endBtnCollapse();

    //Review Comments
    $view->hr();
    $view->h2("Appraisal");
    $view->bgInfoParagraph(
        "When creating your appraisal, the following 6 points should be considered and touched upon:
        <ol>
            <li><strong>Safety Orientation</strong>: <i>Considers the safety of self as well as co-workers</i></li>
            <li><strong>Attendance</strong>: <i>Has acceptable attendance record; arrives on time and completes scheduled work hours</i></li>
            <li><strong>Work Ethic</strong>: <i>Follows directions promptly and accurately; is flexible; demonstrates initiative; works with minimal supervision</i></li>
            <li><strong>Judgement/Descision Making</strong>: <i>Has good communication skills.</i></li>
            <li><strong>Attitude</strong>: <i>Presents a positive attitude; demonstrates honesty and integrity; polite and approachable; works well with others; is team orientated.</i></li>
            <li><strong>Housekeeping</strong>: <i>5S orientated; makes effort to maintain a safe and clean work area, on a consistent basis.</i></li>
        </ol>"
    );
    //Others appraisals
    $view->beginBtnCollapse("Show/Hide Other's Appraisals");
    $otherappraisals = $review->getOthersAppraisals($server->currentUserID);   
    if ($otherappraisals === false) {
        $view->bold("No other appraisals found.");
    }
    else {
        echo "<div class='card bg-light'>"; 
        foreach($otherappraisals as $row) {
             echo "<div class='card-header bg-info text-white'>Reviewers Appraisal</div>";
            echo "<div class='panel-text'>{$row['comments']}</div>";
            echo "</div>";
        }
        echo "</div>";
    }
    $view->endBtnCollapse();
    //Your appraisal
    $myArray = $review->getUserAppraisal($server->currentUserID);
    $form->newForm('My Appraisal');
    if ($myArray === false) {
        $myappraisal['comments'] = '';
        $form->hiddenInput('action','insert_appraisal');
        $form->hiddenInput('eid',$review->eid);
        $form->hiddenInput('uid',$server->currentUserID);
        $form->hiddenInput('revid',$review->getReviewID());
    }
    else {
        $myappraisal = $myArray;
        $form->hiddenInput('action','update_appraisal');
        $form->hiddenInput('id',$myArray['id']);
    }

    $form->inlineTextArea('appraisal', null, $myappraisal['comments'], true, null, true);
    $form->submitForm('Submit',false, $view->PageData['approot'].'/hr/viewemployee?id='.$review->eid);
    $form->endForm();

    $view->footer();
}

function displayPastReview ($revid) {
    global $server;
    include('submenu.php');
    $review = new Review($server->pdo,$revid);
    $view = $server->getViewer("Review: ".$review->getFullName());
    $view->sideDropDownMenu($submenu);
    $view->linkButton("/hr/employeereview?action=printreview&revid={$revid}","Print",'secondary');
    $view->h1($review->getFullName());
    $view->h2("<small>Began:</small> ".$review->getStartDate());
    $view->h2("<small>Ended:</small> ".$review->getEndDate());

    //Training
    $view->hr();
    $view->h3("Training");
    $view->responsiveTableStart(['Training','Date','Trainer']);
    foreach($review->getTraining() as $row) {
        echo "<tr><td>{$row['training']}</td><td>{$row['train_date']}</td><td>{$row['trainer']}</td></tr>\n";
    }
    $view->responsiveTableClose();

    //Attendance
    $view->hr();
    $view->h3("Attendance");
    $attendance = $review->getReviewAttendance();
    if (empty($attendance)) {
        $view->bold("No attendance incidents found.");
    }
    else {
    $view->responsiveTableStart(['Date','Arrived Late','Left Early','Absent','Excused','Reason']);
        foreach($attendance as $row) {
            if ($row['absent'] == 'true') $absent = 'Yes';
            else $absent = 'No';
            if ($row['excused'] == 'true') $excused = 'Yes';
            else $excused = 'No';
            echo "<tr><td>{$row['occ_date']}</td><td>{$row['arrive_time']}</td><td>{$row['leave_time']}</td><td>{$absent}</td><td>{$excused}</td><td>{$row['description']}</td></tr>\n";
        }
        $view->responsiveTableClose();
    }

    //Supervisor comments
    $view->hr();
    $view->h3("Management Comments");
    $supervisor_comments = $review->getReviewManagementComments();
    if (empty($supervisor_comments)) {
        $view->bold("No management comments found.");
    }
    else {
        $view->responsiveTableStart(['Date','Author','Comments']);
        foreach($supervisor_comments as $row) {
            echo "<tr><td>{$row['date']}</td><td>{$row['author']}</td><td>{$row['comments']}</td></tr>\n";
        }
        $view->responsiveTableClose();
    }

    //Appraisals
    $view->hr();
    $view->h3("Appraisals");
    echo "<div class='card'>";
    foreach($review->getAllAppraisals() as $row) {
        echo "<div class='card-header bg-info text-white'>{$row['author']}</div>";
        echo "<div class='card-body bg-light'>";
        echo "<h5 class='card-title'>Appraisal</h5>";
        echo "<div class='card-text '>{$row['comments']}</div>";
        echo "</div>";
    }
    echo "</div>";
    $view->footer();
}

function displayInitReview () {
    global $server;
    include('submenu.php');
    $employee = new Employee($server->pdo,$_REQUEST['eid']);
    $handler = new Employees($server->pdo);
    $view = $server->getViewer('Review: '.$employee->getFullName());
    $view->sideDropDownMenu($submenu);
    $view->h2($employee->getFullName()." <small class='bg-danger'>Is currently not in review</small>");
    if ($server->checkPermission('initEmployeeReview')) {
        $view->h3(
            'You can <i class=\'bg-primary\'>initiate</i> the review process here: '.$view->linkButton('/hr/employeereview?eid='.$employee->eid.'&action=initreview','Begin Review Process','danger',true)
        );
        $view->hr();
        $view->h3("Or Look at Past Reviews");
        $view->responsiveTableStart(['ID','Start Date','End Date',]);
        foreach($handler->getPastReviews($employee->eid) as $row) {
            echo "<tr><td><a href='{$view->PageData['approot']}/hr/employeereview?action=viewreview&revid={$row['id']}'>{$row['id']}</a></td>";
            echo "<td>{$row['start_date']}</td><td>{$row['end_date']}</td></tr>\n";
        }
        $view->responsiveTableClose();
    }

    $view->footer();
}

function displayPrintReview ($revid) {
    global $server;
    $review = new Review($server->pdo,$revid);
    echo "<head>\n";
    echo "  <title>Review: ".$review->getFullName()."</title>\n";
    echo "  <link type='text/css' rel='stylesheet' href='{$server->config['application-root']}/wwwroot/css/print.css' />\n";
    echo "  <script>window.print();</script>\n";
    echo "  <style>\n";
    echo "      div.well {\n";
    echo "          border: 5px solid #000000;\n";
    echo "          padding: 5px;\n";
    echo "          }\n";
    echo "      div.panel {\n";
    echo "          border: 2px solid #000000;\n";
    echo "          padding: 1px;\n";
    echo "          }\n";
    echo "      div.panel-heading {\n";
    echo "          border-bottom: 1px inset #000000;\n";
    echo "          font-weight:bold;\n";
    echo "          }\n";
    echo "      div.panel-body {\n";
    echo "          margin:2px;\n";
    echo "          padding:1px;\n";
    echo "          }\n";
    echo "  </style>\n";
    echo "</head>\n";
    echo "<body>\n";
    echo "  <h1>Review for:".$review->getFullName()."</h1>\n";
    echo "  <h2>Began: ".$review->getStartDate()."</h2>\n";
    echo "  <h2>Ended: ".$review->getEndDate()."</h2>\n";
    echo "  <div class='well'>\n";
    echo "      <h3>Training</h3>\n";
    echo "      <table>\n";
    echo "          <tr><th>Training</th><th>Date</th><th>Trainer</th><tr>\n";
    foreach($review->getTraining() as $row) {
        echo "              <tr><td>{$row['training']}</td><td>{$row['train_date']}</td><td>{$row['trainer']}</td></tr>\n";
    }
    echo "      </table>\n";
    echo "  </div>\n";
    echo "  <div class='well'>\n";
    echo "      <h3>Attendance</h3>\n";
    $attendance = $review->getReviewAttendance();
    if (empty($attendance)) {
        echo "<strong>No attendance incidents found.</strong>";
    }
    else {
        echo "      <table>\n";
        echo "          <tr><th>Date</th><th>Arrived Late</th><th>Left Early</th><th>Absent</th><th>Excused</th><th>Reason</th></tr>\n";
        foreach($attendance as $row) {
            if ($row['absent'] == 'true') $absent = 'Yes';
            else $absent = 'No';
            if ($row['excused'] == 'true') $excused = 'Yes';
            else $excused = 'No';
            echo "              <tr><td>{$row['occ_date']}</td><td>{$row['arrive_time']}</td><td>{$row['leave_time']}</td><td>{$absent}</td><td>{$excused}</td><td>{$row['description']}</td></tr>\n";
        }
        echo "      </table>\n";
    }
    echo "  </div>\n";
    echo "  <div class='well'>";
    echo "      <h3>Management Comments</h3>\n";
    $supervisor_comments = $review->getReviewManagementComments();
    if (empty($supervisor_comments)) {
        echo "<strong>No management comments found.</strong>";
    }
    else {
        echo "      <table>\n";
        echo "              <tr><th>Date</th><th>Author</th><th>Comments</th></tr>\n";
        foreach($supervisor_comments as $row) {
            echo "<tr><td>{$row['date']}</td><td>{$row['author']}</td><td>{$row['comments']}</td></tr>\n";
        }
        echo "      </table>\n";
    }
    echo "  </div>\n";
    echo "  <div class='well'>\n";
    echo "      <h3>Appraisals</h3>\n";
    foreach($review->getAllAppraisals() as $row) {
            echo "      <div class='panel'>\n";
            echo "          <div class='panel-heading'>{$row['author']} Appraisal</div>\n";
            echo "          <div class='panel-body'>{$row['comments']}</div>\n";
            echo "      </div>\n";
    }
    echo "  </div>\n";
    echo "</body>\n";
    echo "</html>\n";    
}