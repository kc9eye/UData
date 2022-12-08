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

$server->userMustHavePermission('editEmployeeAttendance');

if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'add':
            $handler = new Employees($server->pdo);
            // $server->getDebugViewer(var_export($handler->addAttendanceRecord($_REQUEST),true));
            $server->processingDialog(
                [$handler,'addAttendanceRecord'],
                [$_REQUEST],
                $server->config['application-root'].'/hr/attendance?id='.$_REQUEST['eid']
            );
    break;
        case 'edit':
            editAttendanceDisplay();
        break;
        case 'delete':
            $handler = new Employees($server->pdo);
            $server->processingDialog(
                [$handler,'removeAttendanceRecord'],
                [$_REQUEST['id']],
                $server->config['application-root'].'/hr/attendance?id='.$_REQUEST['uid']
            );
        break;
        case 'amend':
            $handler = new Employees($server->pdo);
            $server->processingDialog(
                [$handler,'amendAttendanceRecord'],
                [$_REQUEST],
                $server->config['application-root'].'/hr/attendance?id='.$_REQUEST['eid']
            );
        break;
        case 'range':
            dateRangeDisplay();
        break;
        case 'print':
            printDisplay();
        break;
        default: attendanceDisplay(); break;
    }
}
else attendanceDisplay();

function attendanceDisplay () {
    global $server;
    include('submenu.php');
    
    $emp = new Employee($server->pdo,$_REQUEST['id']);

    //View header options for adding the Boostrap DatePicker
    $pageOptions = [
        'headinserts'=> [
            '<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/js/bootstrap-datepicker.min.js"></script>',
            '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/css/bootstrap-datepicker3.css"/>'
        ]
    ];

    $view = $server->getViewer("HR: Attendance",$pageOptions);
    $view->sideDropDownMenu($submenu);
    $view->h1("<small>Add Attendance Record:</small> {$emp->Profile['first']} {$emp->Profile['middle']} {$emp->Profile['last']} {$emp->Profile['other']}".
        $view->linkButton("/hr/viewemployee?id={$_REQUEST['id']}","<span class='glyphicon glyphicon-arrow-left'></span> Back",'info',true)
    );
    echo 
    '<form id="addRecord">
                <input type="hidden" name="eid" value="'.$_REQUEST['eid'].'" />
                <input type="hidden" name="uid" value="'.Authentication::getValidUser()->getUID().'" />
                <div class="form-group mb-3">
                    <label class="form-label" for="occ_date">Single Occurrence Date</label>
                    <input type="date" class="form-control" name="occ_date" />
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Ranged Occurrence</label>
                    <div class="input-group">
                        <input class="form-control" type="date" name="begin_date_range" />
                        <span class="input-group-text">to</span>
                        <input class="form-control" type="date" name="end_date_range" />
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label" for="arrival_time">Arrive Late</label>
                    <input class="form-control" type="time" name="arrival_time" />
                </div>
                <div class="form-group mb-3">
                    <label class="form-label" for="departure_time">Left Early</label>
                    <input class="form-control" type="time" name="departure_time" />
                </div>
                <hr />
                <h4>Modifiers</h4>
                <div class="form-group mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="excused" value="1" />
                        <label class="form-check-label" for="excused">Excused/No Points</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="perfect_uneffected" value="1" />
                        <label class="form-check-label" for="perfect">Does not effect perfect Attendance</label>
                    </div>
                </div>
                <button id="submitBtn" class="btn btn-secondary mb-3" type="button">Add Record</button>
            </form>';
    $view->h3("<small>Attendance Points:</small> {$emp->AttendancePoints}");
    $view->responsiveTableStart(['Date','Arrived Late','Left Early','Absent','Reason','Points','Edit']);
    if (!empty($emp->Attendance)) {
        foreach($emp->Attendance as $row) {
            $absent = ($row['absent'] == 'true') ? 'Yes' : 'No';
            // $excused = ($row['excused'] == 'true') ? 'Yes' : 'No';
            echo "<tr><td>{$row['occ_date']}</td><td>{$row['arrive_time']}</td><td>{$row['leave_time']}</td>";
            echo "<td>{$absent}</td><td>{$row['description']}</td><td>{$row['points']}</td><td>";
            $view->editBtnSm('/hr/attendance?action=edit&id='.$row['id'].'&uid='.$_REQUEST['id']);
            echo "</td></tr>\n";
        }
    }
    $view->responsiveTableClose();
    echo "<script>$(document).ready(function(){
        var options = {
            format:'yyyy/mm/dd',
            autoclose: true
        };
        $('.input-group input').each(function(){
            $(this).datepicker(options);
        });
    });\n</script>";
    $view->footer();
}

function editAttendanceDisplay() {
    global $server;
    include('submenu.php');

    $handler = new Employees($server->pdo);
    $row = $handler->getAttendanceByID($_REQUEST['id']);

    $view = $server->getViewer("HR:Attendace Amend");
    $view->sideDropDownMenu($submenu);
    $view->h1(
        "<small>Amend Record#:</small> {$_REQUEST['id']}&#160;".
        $view->trashBtnSm('/hr/attendance?action=delete&id='.$_REQUEST['id'].'&uid='.$_REQUEST['uid'],true)
    );
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    $form->newForm();
    $form->hiddenInput('action','amend');
    $form->hiddenInput('uid',$server->currentUserID);
    $form->hiddenInput('eid',$_REQUEST['uid']);
    $form->inputCapture('occ_date','Date',$row['occ_date'],['dateISO'=>'true']);
    $form->inputCapture('arrive_time','Time Arrived',$row['arrive_time']);
    $form->inputCapture('leave_time','Time Left',$row['leave_time']);
    $form->inputCapture('points','Points',$row['points']);
    if ($row['absent']) 
        $form->checkBox('absent',['Absent','No'],'false',false,null,'true');
    else
        $form->checkBox('absent',['Absent','Yes'],'true',false,null,'false');
    if ($row['excused'])
        $form->checkBox('excused',['Perfect Attendace','No'],'false',false,null,'true');
    else
        $form->checkBox('excused',['Perfect Attendance','Yes'],'true',false,null,'false');
    $form->textArea('description',null,$row['description'],true);
    $form->submitForm('Amend',false,$view->PageData['approot'].'/hr/attendance?id='.$_REQUEST['uid']);
    $form->endForm();
    $view->footer();
}

function printDisplay () {
    global $server;
    $emp = new Employee($server->pdo,$_REQUEST['id']);
    echo "<!DOCTYPE html>\n";
    echo "<html>\n";
    echo "<head>\n";
    echo "<title>{$emp->Profile['first']} {$emp->Profile['middle']} {$emp->Profile['last']} {$emp->Profile['other']}</title>\n";
    echo "<link rel='stylesheet' type='text/css' href='{$server->config['application-root']}/wwwroot/css/print.css' />\n";
    echo "<style>\n";
    echo "table {
            width:100%;
        }
        table, td, th {
            border-collapse: collapse;
            border: 1px solid black;
        }
        th,td {
            text-align:center;
        }
        td {
            height:30px;
            vertical-align: center;
        }
        #notes {
            width:35%;
        }\n";
    echo "</style>\n";
    echo "</head>\n";
    echo "<body>\n";
    echo "<h1>Missed Time for: {$emp->Profile['first']} {$emp->Profile['middle']} {$emp->Profile['last']} {$emp->Profile['other']}</h1>\n";
    echo "<table border='1'>\n";
    echo "<tr><th>Date</th><th>Arrived Late</th><th>Left Early</th><th>Absent</th><th>Reason</th><th>Points</th></tr>\n";
    foreach($emp->Attendance as $row) {
        $absent = ($row['absent'] == 'true') ? 'Yes' : 'No';
        //$excused = ($row['excused'] == 'true') ? 'Yes' : 'No';
        echo "<tr><td>{$row['occ_date']}</td><td>{$row['arrive_time']}</td><td>{$row['leave_time']}</td>";
        echo "<td>{$absent}</td><td>{$row['description']}</td><td>{$row['points']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    echo "<script>window.print();</script>\n";
    echo "</body>\n";
    echo "</html>\n";
}

function dateRangeDisplay() {
    global $server;
    $emp = new Employee($server->pdo,$_REQUEST['id']);
    $absent = $emp->getAttendanceDateRange($_REQUEST['begin'],$_REQUEST['end']);
    $view = $server->getViewer("Attendance Range");
    include('submenu.php');
    $view->sideDropDownMenu($submenu);
    $view->h1("Attendance Date Range",true);
    $view->h2("<small>Employee:</small> ".$emp->getFullName(),true);
    $view->h3("<small>Range:</small> ".$view->formatUserTimestamp($_REQUEST['begin'],true)." - ".$view->formatUserTimestamp($_REQUEST['end'],true),true);
    $view->hr();
    $view->printButton();
    $view->responsiveTableStart(['Date','Arrived Late','Left Early','Absent','Excused','Reason']);
    foreach($absent as $row) {
        if ($row['absent'] == 'true') $absent = 'Yes';
        else $absent = 'No';
        if ($row['excused'] == 'true') $excused = 'Yes';
        else $excused = 'No';
        echo "<tr><td>".$view->formatUserTimestamp($row['occ_date'],true)."</td><td>{$row['arrive_time']}</td><td>{$row['leave_time']}</td><td>{$absent}</td><td>{$excused}</td><td>{$row['description']}</td></tr>\n";
    }
    $view->responsiveTableClose();
    $view->footer();
}