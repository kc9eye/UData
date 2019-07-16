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
            $server->processingDialog(
                [$handler,'addAttendanceRecord'],
                [$_REQUEST],
                $server->config['application-root'].'/hr/viewemployee?id='.$_REQUEST['eid']
            );
        break;
        case 'edit':
            editAttendanceDisplay();
        break;
        case 'amend':
            $handler = new Employees($server->pdo);
            $server->processingDialog(
                [$handler,'amendAttendanceRecord'],
                [$_REQUEST],
                $server->config['application-root'].'/hr/main'
            );
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

    $view = $server->getViewer("HR: Attendance");
    $view->sideDropDownMenu($submenu);
    $view->h1("<small>Add Attendance Record:</small> {$emp->Profile['first']} {$emp->Profile['middle']} {$emp->Profile['last']} {$emp->Profile['other']}".
        $view->linkButton("/hr/viewemployee?id={$_REQUEST['id']}","<span class='glyphicon glyphicon-arrow-left'></span> Back",'info',true)
    );
    $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');
    $form->newForm();
    $form->hiddenInput('action','add');
    $form->hiddenInput('uid',$server->currentUserID);
    $form->hiddenInput('eid',$_REQUEST['id']);
    $form->inputCapture('occ_date','Date',date('Y/m/d'),['dateISO'=>'true']);
    $form->inputCapture('arrive_time','Time Arrived','00:00');
    $form->inputCapture('leave_time','Time Left','00:00');
    $form->checkBox('absent',['Absent','Yes'],'true',false,null,'false');
    $form->checkBox('excused',['Excused','Yes'],'true',false,null,'false');
    $form->textArea('description',null,'',true);
    $form->submitForm('Add',false,$view->PageData['approot'].'/hr/viewemployee?id='.$_REQUEST['id']);
    $form->endForm();
    $view->responsiveTableStart(['Date','Arrived Late','Left Early','Absent','Excused','Reason','Edit']);
    if (!empty($emp->Attendance)) {
        foreach($emp->Attendance as $row) {
            $absent = ($row['absent'] == 'true') ? 'Yes' : 'No';
            $excused = ($row['excused'] == 'true') ? 'Yes' : 'No';
            echo "<tr><td>{$row['occ_date']}</td><td>{$row['arrive_time']}</td><td>{$row['leave_time']}</td>";
            echo "<td>{$absent}</td><td>{$excused}</td><td>{$row['description']}</td><td>";
            $view->editBtnSm('/hr/attendance?action=edit&id='.$row['id']);
            echo "</td></tr>\n";
        }
    }
    $view->responsiveTableClose();
    $view->footer();
}

function editAttendanceDisplay() {
    global $server;
    include('submenu.php');
    $handler = new Employees($server->pdo);
    $row = $handler->getAttendanceByID($_REQUEST['id']);
    $view = $server->getViewer("HR:Attendace Amend");
    $view->sideDropDownMenu($submenu);
    $view->h1("<small>Amend Record#:</small> {$_REQUEST['id']}");
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    $form->newForm();
    $form->hiddenInput('action','amend');
    $form->hiddenInput('uid',$server->currentUserID);
    $form->inputCapture('occ_date','Date',$row['occ_date'],['dateISO'=>'true']);
    $form->inputCapture('arrive_time','Time Arrived',$row['arrive_time']);
    $form->inputCapture('leave_time','Time Left',$row['leave_time']);
    $form->checkBox('absent',['Absent','Yes'],'true',false,null,$row['absent']);
    $form->checkBox('excused',['Excused','Yes'],'true',false,null,$row['excused']);
    $form->textArea('description',null,$row['description'],true);
    $form->submitForm('Add',false,$view->PageData['approot'].'/hr/main');
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
    echo "<tr><th>Date</th><th>Arrived Late</th><th>Left Early</th><th>Absent</th><th>Excused</th><th>Reason</th></tr>\n";
    foreach($emp->Attendance as $row) {
        $absent = ($row['absent'] == 'true') ? 'Yes' : 'No';
        $excused = ($row['excused'] == 'true') ? 'Yes' : 'No';
        echo "<tr><td>{$row['occ_date']}</td><td>{$row['arrive_time']}</td><td>{$row['leave_time']}</td>";
        echo "<td>{$absent}</td><td>{$excused}</td><td>{$row['description']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    echo "<script>window.print();</script>\n";
    echo "</body>\n";
    echo "</html>\n";
}