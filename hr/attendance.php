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
        case 'addRecord':
            addAttendanceRecord();
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
            amendAttendanceRecord();
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

    $view = $server->getViewer("HR: Attendance");
    $view->sideDropDownMenu($submenu);
    $view->h1("<small>Add Attendance Record:</small> {$emp->Profile['first']} {$emp->Profile['middle']} {$emp->Profile['last']} {$emp->Profile['other']}".
        $view->linkButton("/hr/viewemployee?id={$_REQUEST['id']}","<span class='glyphicon glyphicon-arrow-left'></span> Back",'info',true)
    );
    echo 
    '<div id="form-display">
    <form id="addRecord">
                <input type="hidden" name="eid" value="'.$_REQUEST['id'].'" />
                <input type="hidden" name="uid" value="'.$server->currentUserID.'" />
                <input type="hidden" name="action" value="addRecord" />
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
                <div class="form-group mb-3">
                    <label class="form-label" for="description">Description</label>
                    <select class="form-control mb-3" name="description">
                        <option value="Absence">Absence</option>
                        <option value="Late">Late</option>
                        <option value="Left Early">Left Early</option>
                        <option value="Left/Returned">Left/Returned</option>
                        <option value="No Time Lost">No Time Lost</option>
                        <option value="No Call/No Show">No Call/No Show</option>
                        <option value="Vacation">Vacation</option>
                    </select>
                </div>
                <hr />
                <h4>Points</h4>
                <select class="form-control mb-3" name="points" required="required">
                    <option value="" selected></option>
                    <option value="0">0</option>
                    <option value="0.25">0.25</option>
                    <option value="0.50">0.50</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                </select>
                <button id="submitBtn" class="btn btn-secondary mb-3" type="submit">Add Record</button>
            </form>
            </div>
            <script>
                let form = document.getElementById("addRecord");
                let btn = document.getElementById("submitBtn");

                btn.addEventListener("click",async (event)=>{
                    event.preventDefault();
                    //Validate the form
                    inputs = form.querySelectorAll(".needs-validation");
                    inputs.foreach((input)=>{
                        if (!form.checkValidity()) {
                            event.stopPropagation();
                        }
                        input.classList.add("was-validated");
                    });

                    //Submit the form
                    btn.setAttribute("disabled","disabled");
                    btn.innerHTML = "<span class=\"spinner-border spinner-border-sm\"></span>&#160;"+btn.innerHTML;
                    result = await fetch(
                        "'.$server->config['application-root'].'/hr/attendance",
                        {method:"POST",body:new FormData(form)}
                    );
                    document.getElementById("form-display").innerHTML = await result.text();
                    window.scrollTo(0,0);
                });
            </script>';
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
    $view->footer();
}

function addAttendanceRecord() {
    global $server;
    if ($_REQUEST['occ_date'] == '') {
        if ($_REQUEST['begin_date_range'] != '' && $_REQUEST['end_date_range'] == '') {
            echo 
        '<div class="border border-secondary rounded m-3">
            <h4 class="bg-danger">Error</h4>
            <b>End date for a date range is required.</b>&#160;
            <a href="'.$server->config['application-root'].'/hr/attendance?id='.$_REQUEST['eid'].'" class="btn btn-danger m-1" role="button">Try Again</a>
        </div>';
        exit();
        }
        else if ($_REQUEST['begin_date_range'] == '' && $_REQUEST['end_date_range'] != '') {
            echo 
        '<div class="border border-secondary rounded m-3">
            <h4 class="bg-danger">Error</h4>
            <b>A begin date is required for a date range.</b>&#160;
            <a href="'.$server->config['application-root'].'/hr/attendance?id='.$_REQUEST['eid'].'" class="btn btn-danger m-1" role="button">Try Again</a>
        </div>';
        exit();
        }
        //Date interval
        $interval = new DateInterval('P1D');
        $end_date = new DateTime($_REQUEST['end_date_range']);
        $end_date->add($interval);
        $period = new DatePeriod(new DateTime($_REQUEST['begin_date_range']),$interval, $end_date);

        try {
            $sql =
            'insert into missed_time values (:id,:eid,:occ_date,:absent,:arrive_time,:leave_time,:description,:excused,:uid,now(),:points)';
            $pntr = $server->pdo->prepare($sql);
            $server->pdo->beginTransaction();
            foreach($period as $date) {
                $insert = [
                    ':id'=>uniqid(),
                    ':eid'=>$_REQUEST['eid'],
                    ':occ_date'=>$date->format('Y-m-d'),
                    ':absent'=> ($_REQUEST['description'] == 'Absence') ? 1 : 0,
                    ':arrive_time'=>($_REQUEST['arrival_time'] == '') ? "00:00:00" : $_REQUEST['arrival_time'],
                    ':leave_time' =>($_REQUEST['departure_time'] == '') ? "00:00:00" : $_REQUEST['departure_time'],
                    ':description'=> $_REQUEST['description'],
                    ':excused'=>0,
                    ':uid'=>$_REQUEST['uid'],
                    ':points'=>$_REQUEST['points']
                ];
                if (!$pntr->execute($insert)) throw new Exception(print_r($pntr->errorInfo(),true));
            }
            $server->pdo->commit();
            echo 
            '<div class="m-3">
                <h4 class="bg-success">Record/s Added</h4>
                <a href="'.$server->config['application-root'].'/hr/attendance?id='.$_REQUEST['eid'].'" class="btn btn-secondary m-1" role="button">Back</a>
            </div>';
        }
        catch(Exception $e) {
            $server->pdo->rollBack();
            trigger_error($e->getMessage(),E_USER_WARNING);
            echo 
            '<div class="border border-secondary rounded m-3">
                <h4 class="bg-danger">Error</h4>
                <b>A database error occurred</b>&#160;
                <a href="'.$server->config['application-root'].'/hr/attendance?id='.$_REQUEST['eid'].'" class="btn btn-danger m-1" role="button">Try Again</a>
            </div>';
            exit();
        }
    }
    else if ($_REQUEST['occ_date'] != '') {
        $sql =
            'insert into missed_time values (:id,:eid,:occ_date,:absent,:arrive_time,:leave_time,:description,:excused,:uid,now(),:points)';
        $pntr = $server->pdo->prepare($sql);
        try {
            $insert = [
                ':id'=>uniqid(),
                ':eid'=>$_REQUEST['eid'],
                ':occ_date'=>$_REQUEST['occ_date'],
                ':absent'=> ($_REQUEST['description'] == 'Absence') ? 1 : 0,
                ':arrive_time'=>($_REQUEST['arrival_time'] == '') ? "00:00:00" : $_REQUEST['arrival_time'],
                ':leave_time' =>($_REQUEST['departure_time'] == '') ? "00:00:00" : $_REQUEST['departure_time'],
                ':description'=> $_REQUEST['description'],
                ':excused'=>0,
                ':uid'=>$_REQUEST['uid'],
                ':points'=>$_REQUEST['points']
            ];
            if (!$pntr->execute($insert)) throw new Exception(print_r($pntr->errorInfo(),true));
            echo 
            '<div class="m-3">
                <h4 class="bg-success">Record/s Added</h4>
                <a href="'.$server->config['application-root'].'/hr/attendance?id='.$_REQUEST['eid'].'" class="btn btn-secondary m-1" role="button">Back</a>
            </div>';
        }
        catch(Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            echo 
            '<div class="border border-secondary rounded m-3">
                <h4 class="bg-danger">Error</h4>
                <b>A database error occurred</b>&#160;
                <a href="'.$server->config['application-root'].'/hr/attendance?id='.$_REQUEST['eid'].'" class="btn btn-danger m-1" role="button">Try Again</a>
            </div>';
            exit();
        }
    }
    else {
        echo 
        '<div class="border border-secondary rounded m-3">
            <h4 class="bg-danger">Error</h4>
            <b>A date is required for every entry.</b>&#160;
            <a href="'.$server->config['application-root'].'/hr/attendance?id='.$_REQUEST['eid'].'" class="btn btn-danger m-1" role="button">Try Again</a>
        </div>';
        exit();
    }
    exit();
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
    echo
    '<div id="form-display">
    <form id="editForm">
        <input type="hidden" name="action" value="amend" />
        <input type="hidden" name="uid" value="'.$server->currentUserID.'" />
        <input type="hidden" name="rid" value="'.$_REQUEST['id'].'" />
        <input type="hidden" name="eid" value="'.$_REQUEST['uid'].'" />
        <div class="form-group mb-3">
            <label class="form-label" for="occ_date">Date</label>
            <input class="form-control" type="date" name="occ_date" value="'.$row['occ_date'].'" />
        </div>
        <div class="form-group mb-3">
            <label class="form-label" for="arrive_time">Time Arrived</label>
            <input class="form-control" type="time" name="arrive_time" value="'.$row['arrive_time'].'" />
        </div>
        <div class="form-group mb-3">
            <label class="form-label" for="leave_time">Time Left</label>
            <input class="form-conrol" type="time" name="leave_time" value="'.$row['leave_time'].'" />
        </div>
        <div class="form-group mb-3">
            <label class="form-label" for="points">Points</label>
            <input class="form-control" type="text" name="points" value="'.$row['points'].'" />
        </div>
        <button class="btn btn-secondary" type="button" id="submitBtn">Amend</button>
    </form>
    </div>
    <script>
        let form = document.getElementById("editForm");
        let btn = document.getElementById("submitBtn");
        btn.addEventListener("click",async (event) =>{
            event.preventDefault();
            btn.setAttribute("disabled","disabled");
            btn.innerHTML = "<span class=\"spinner-border spinner-border-sm\"></span>&#160;"+btn.innerHTML;
            let result = await fetch(
                "'.$server->config['application-root'].'/hr/attendance",
                {method:"POST",body:new FormData(form)}
            );
            document.getElementById("form-display").innerHTML = await result.text();
            window.scrollTo(0,0);
        });
    </script>';
    $view->footer();
}

function amendAttendanceRecord() {
    global $server;
    $sql = 'update missed_time set occ_date = :occ_date, arrive_time = :arrive_time, leave_time = :leave_time, points = :points
    where id = :id';
    $pntr = $server->pdo->prepare($sql);
    try {
        $insert = [
            ':occ_date'=>$_REQUEST['occ_date'],
            ':arrive_time'=>($_REQUEST['arrive_time'] == '') ? "00:00:00" : $_REQUEST['arrive_time'],
            ':leave_time'=> ($_REQUEST['leave_time'] == '') ? "00:00:00" : $_REQUEST['leave_time'],
            ':points'=> $_REQUEST['points'],
            ':id'=>$_REQUEST['rid']
        ];
        if (!$pntr->execute($insert)) throw new Exception(print_r($pntr->errorInfo(),true));
        echo
        '<div class="border border-success rounded m-3">
            <h4 class="bg-success">Record/s Added</h4>
            <a href="'.$server->config['application-root'].'/hr/attendance?id='.$_REQUEST['eid'].'" class="btn btn-secondary m-1" role="button">Back</a>
        </div>';
    }
    catch (Exception $e) {
        trigger_error($e->getMessage(),E_USER_WARNING);
        echo 
        '<div class="border border-secondary rounded m-3">
            <h4 class="bg-danger">Error</h4>
            <b>A date is required for every entry.</b>&#160;
            <a href="'.$server->config['application-root'].'/hr/attendance?id='.$_REQUEST['eid'].'" class="btn btn-danger m-1" role="button">Try Again</a>
        </div>';
        exit();
    }
    exit();
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