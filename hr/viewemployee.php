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
        default:
            employeeViewDisplay();
        break;
    }
}
else
    employeeViewDisplay();

function employeeViewDisplay () {
    //View header options for adding the Boostrap DatePicker
    $pageOptions = [
        'headinserts'=> [
            '<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/js/bootstrap-datepicker.min.js"></script>',
            '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/css/bootstrap-datepicker3.css"/>'
        ]
    ];
    global $server;
    include('submenu.php');
    $server->userMustHavePermission('viewProfiles');
    $emp = new Employee($server->pdo,$_REQUEST['id']);
    $view = $server->getViewer('HR: Employee Profile',$pageOptions);
    $view->sideDropDownMenu($submenu);
    $view->h1("Employee Profile");

    //Main profile data
    $view->hr();
    
    $title = "<small>Name:</small> ".$emp->getFullName();
    if ($server->checkPermission('addNewProfile')) 
        $title .= "&#160;".$view->editBtnSm('/hr/updateprofile?id='.$_REQUEST['id'],true);
    if ($server->checkPermsArray(['initEmployeeReview','reviewEmployee']))
        $title .= "&#160;".$view->linkButton('/hr/employeereview?eid='.$emp->eid, 'Employee Review', 'info', true);
    $view->h2($title);    
    
    if (!empty($emp->Profile['image'])) {
        $view->responsiveTableStart(['Image','Start Date','End Date','Status']);
        echo "<tr><td>";
        echo 
            "<a href='{$server->config['application-root']}/data/files?file=".$emp->getImageFilename()."'>
             <img 
                class='img-fluid'
                src='{$server->config['application-root']}/data/files?file=".$emp->getImageFilename()."'
                alt='[IMAGE NOT FOUND]'
                style='max-width:400px;max-height:400px;'
             />
             </a>";
        echo "</td>";
    }
    else {
        $view->responsiveTableStart(['Start Date','End Date', 'Status','SMID']);
        echo "<tr>";
    }
    echo "<td>{$emp->Employee['start_date']}</td><td>{$emp->Employee['end_date']}</td><td>{$emp->Employee['status']}</td><td>{$emp->Employee['smid']}</td></tr>\n";
    $view->responsiveTableClose();

    //Profile data
    $view->hr();
    $view->beginBtnCollapse("Show/Hide Profile");
    $view->h3('Profile Data');
    $view->responsiveTableStart(['Address','Contact','Emergency Contact']);
    echo "<tr><td>";
        $view->responsiveTableStart();
            echo "<tr><th>Address:</th><td>{$emp->Profile['address']}</td></tr>\n";
            echo "<tr><th>Continued:</th><td>{$emp->Profile['address_other']}</td></tr>\n";
            echo "<tr><th>City:</th><td>{$emp->Profile['city']}</td></tr>\n";
            echo "<tr><th>State/Prov:</th><td>{$emp->Profile['state_prov']}</td></tr>\n";
            echo "<tr><th>Postal Code:</th><td>{$emp->Profile['postal_code']}</td></tr>\n";
        $view->responsiveTableClose();
    echo "</td><td>";
        $view->responsiveTableStart();
            echo "<tr><th>Home Phone:</th><td>{$emp->Profile['home_phone']}</td></tr>\n";
            echo "<tr><th>Cell Phone:</th><td>{$emp->Profile['cell_phone']}</td><tr>\n";
            echo "<tr><th>Other Phone:</th><td>{$emp->Profile['alt_phone']}</td></tr>\n";
            echo "<tr><th>Email:</th><td>{$emp->Profile['email']}</td></tr>\n";
            echo "<tr><th>Alt. Email:</th><td>{$emp->Profile['alt_email']}</td></tr>\n";
        $view->responsiveTableClose();
    echo "</td><td>";
        $view->responsiveTableStart();
            echo "<tr><th>Emergency Contact:</th><td>{$emp->Profile['e_contact_name']}</td><tr>\n";
            echo "<tr><th>Emergency Number:</th><td>{$emp->Profile['e_contact_number']}</td></tr>\n";
            echo "<tr><th>Emergency Relation:</th></td>{$emp->Profile['e_contact_relation']}</td></tr>\n";
        $view->responsiveTableClose();
    echo "</td></tr>\n";
    $view->responsiveTableClose();
    $view->endBtnCollapse();

    //Attendance data
    $view->hr();
    $view->beginBtnCollapse("Show/Hide Attendance");
    $heading = $server->checkPermission('editEmployeeAttendance') ?
        "Attendance ".$view->editBtnSm('/hr/attendance?id='.$_REQUEST['id'],true) : "Attendance ";
    $view->h3(
        $heading.'&#160'
        .$view->linkButton('/hr/attendance?action=print&id='.$_REQUEST['id'],'Print','default',true,'_blank')
    );
    echo "<div class='w-50'><b>View Date Range</b>
            <div class='input-group input-daterange'>
                <input class='form-control' type='text' id='beginDate' name='begin_date' />
                <span class='input-group-addon'>to</span>
                <input class='form-control' type='text' id='endDate' name='end_date' />
                <div class='input-group-append'>
                    <button type='button' class='btn btn-primary' onclick='dateRangeLookup()'>Submit</button>
                </div>
            </div>
        </div>
    <script>
    function dateRangeLookup(){
        var url = '".$server->config['application-root']."/hr/attendance?action=range&id=".$_REQUEST['id']."&begin='+$('#beginDate').val()+'&end='+$('#endDate').val();
        window.open(url,'_self');
    }
    $(document).ready(function(){
        var options = {
            format:'yyyy/mm/dd',
            autoclose: true
        };
        $('.input-group input').each(function(){
            $(this).datepicker(options);
        });
    });\n</script>";
    if (!empty($emp->Attendance)) {
        $view->h3("<small>Attendance Points:</small> {$emp->AttendancePoints}");
        $view->responsiveTableStart(['Date','Arrived Late','Left Early','Absent','Reason','Points']);
        foreach($emp->Attendance as $row) {
            if ($row['absent'] == 'true') $absent = 'Yes';
            else $absent = 'No';
            // if ($row['excused'] == 'true') $excused = 'Yes';
            // else $excused = 'No';
            echo "<tr><td>".$view->formatUserTimestamp($row['occ_date'],true)."</td><td>{$row['arrive_time']}</td><td>{$row['leave_time']}</td><td>{$absent}</td><td>{$row['description']}</td><td>{$row['points']}</td></tr>\n";
        }
        $view->responsiveTableClose();
    }
    else $view->bold('No attendance data found');
    $view->endBtnCollapse();

    //Training Data
    $view->hr();
    $view->beginBtnCollapse("Show/Hide Training");
    $heading = $server->checkPermission('editSkills') ?
        "Training ".$view->editBtnSm('/hr/addskills?id='.$_REQUEST['id'],true) : "Training";
    $view->h3($heading);
    if (!empty($emp->Training)) {
        $view->responsiveTableStart(['Training','Date','Trainer']);
        foreach($emp->Training as $row) {
            echo "<tr><td>{$row['training']}</td><td>{$row['train_date']}</td><td>{$row['trainer']}</td></tr>\n";
        }
        $view->responsiveTableClose();
    }
    else $view->bold("No training data found");
    $view->endBtnCollapse();

    //Injuries Section
    $view->hr();
    $view->beginBtnCollapse("Show/Hide Injuries");
    $heading = $server->checkPermission('editEmployeeInjuries') ?
        "Injuries ".
        $view->linkButton('/hr/injuryreport?id='.$_REQUEST['id'],"<span class='glypicon glyphicon-plus'</span>New",'success',true) :
        "Injuries";
    $view->h3($heading);
    if (!empty($emp->Injuries)) {
        $view->responsiveTableStart(['ID','Date','Recordable','Medical Followup','Reporter','Edit']);
        foreach($emp->Injuries as $row) {
            $recordable = ($row['recordable'] == 'true') ? 'Yes' : 'No';
            $clinic = ($row['followup_medical'] == 'true') ? 'Yes' : 'No';
            echo "<tr><td><a href='{$server->config['application-root']}/hr/injuryreport?action=view&id={$row['id']}'>{$row['id']}</a></td>";
            echo "<td>{$row['injury_date']}</td><td>{$recordable}</td><td>{$clinic}</td><td>{$row['recorder']}</td><td>";
            $view->editBtnSm('/hr/injuryreport?action=edit&id='.$row['id']);
            echo "</td></tr>\n";
        }
        $view->responsiveTableClose();
    }
    else $view->bold("No injury data found");
    $view->endBtnCollapse();

    //Comments section
    if ($server->checkPermission('viewSupervisorComments')) {
        $view->hr();
        $view->beginBtnCollapse("Show/Hide Comments");
        $heading = $server->checkPermission('editSupervisorComments') ?
            "Comments ".$view->linkButton('/hr/feedback?id='.$_REQUEST['id'],"<span class='glyphicon glyphicon-plus'></span>Add",'info',true) 
            : "Comments";
        $view->h3($heading);
        if (!empty($emp->Comments)) {
            $view->responsiveTableStart(['ID','Date','Subject','Author']);
            foreach($emp->Comments as $row) {
                echo "<tr><td><a href='{$server->config['application-root']}/hr/feedback?action=view&id={$row['id']}'>{$row['id']}</a></td>";
                echo "<td>{$row['date']}</td><td>{$row['subject']}</td><td>{$row['author']}</td></tr>\n";
            }
            $view->responsiveTableClose();
        }
        else $view->bold("No comment data found");
        $view->endBtnCollapse();
    }    

    //PPE Data
    if ($server->checkPermission('readPPE')) {
        $ppe = new ShopSupplies($server->pdo);
        $agg = $ppe->getAggregatePPE($_REQUEST['id']);
        $view->hr();
        $view->beginBtnCollapse("PPE Data");
        $view->responsiveTableStart(["Total Expense","Qty.","Total Exchanged","Description"]);
        foreach ($agg as $row) {
            echo "<tr><td>{$row['Expense']}</td><td>{$row['Issued']}</td><td>{$row['Exchanged']}</td><td>{$row['Item']}</td></tr>";
        }
        $view->responsiveTableClose();
        $view->endBtnCollapse();
    }

    $view->footer();
}