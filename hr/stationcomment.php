<?php
/**
* Copyright 2025 Paul W. Lane <kc9eye@gmail.com>
*
* Licensed under the Apache License, Version 2.0 (the "License");
* you may not use this file except in compliance with the License.
* You may obtain a copy of the License at
*
*   http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and
* limitations under the License.
**/
require_once(dirname(__DIR__).'/lib/init.php');
$server->userMustHavePermission("editSupervisorComments");

if (!empty($_REQUEST)) {
    switch($_REQUEST['action']) {
        case 'addEmployee': addEmployee(); break;
        case 'resetSession': resetSession();break;
        case 'stationComment': stationComment();break;
        default: displayForm();break;
    }
}
else displayForm();

function displayForm() {
    global $server;
    $emps = new Employees($server->pdo);
    $view = $server->getViewer("Station Comment");
    echo
    '<h1>Station Comment</h1>
    <form id="selectEmployees">
        <input type="hidden" name="action" value="addEmployee" />
        <label class="form-label">Add Station Employees</label>
        <div class="input-group">
            <select class="form-control" name="employee">';
            foreach($emps->getActiveEmployeeList() as $row) {
                echo '<option value="'.$row['eid'].'">'.$row['name'].'</option>';
            }
    echo
            '</select>
            <button id="addEmployeeBtn" type="submit" class="btn btn-secondary">Add</button>
        </div>
    </form>';
    if (!empty($_SESSION['station_employee_comment'])) {
        echo
        '<h3>Added Employees</h3>
        <form id="somethingDifferent">
            <input type="hidden" name="action" value="resetSession" />
            <ul class="list-group">';
            foreach($_SESSION['station_employee_comment'] as $row) {
                echo '<li class="list-group-item">'.$row['name'].'</li>';
            }
            echo
            '</ul>
            <button type="submit" class="btn btn-secondary" id="resetEmployees">Clear</button>
        </form>';
    }
    echo
    '<form id="stationComment">
        <input type="hidden" name="action" value="stationComment" />
        <div class="form-group mb-3">
            <label class="form-label" for="comments">Comments</label>
            <textarea class="form-control" name="comments"></textarea>
        </div>
        <button id="stationCommentBtn" class="btn btn-secondary" type="submit">Submit</button>
    </form>';
    $view->footer();
}

function addEmployee() {
    global $server;
    if (!isset($_SESSION['station_employee_comment'])) $_SESSION['station_employee_comment'] = [];
    $emp = new Employee($server->pdo,$_REQUEST['employee']);
    array_push($_SESSION['station_employee_comment'],['eid'=>$emp->getEID(),'name'=>$emp->getFullName()]);
    displayForm();
}

function resetSession() {
    unset($_SESSION['station_employee_comment']);
    displayForm();
}

function stationComment() {
    global $server;

    if (empty($_SESSION['station_employee_comment'])) {
        $viewer = $server->getViewer("Error");
        echo
        '<div class="border border-dark rounded bg-danger text-dark">
            <h3>Exception</h3>
            <small class="m-3">There were no employees found to comment on</small><br>
            <a href="'.$server->config['application-root'].'/hr/stationcomment" class="btn btn-secondary" role="button">Back</a>
        </div>';
        $viewer->footer();
        exit();
    }
    $uid = $server->security->secureUserID;
    $class = new SupervisorComments($server->pdo);
    foreach($_SESSION['station_employee_comment'] as $emp) {
        $data = ['id'=>uniqid(),'uid'=>$uid,'comments'=>$_REQUEST['comments'],'eid'=>$emp['eid'],'fid'=>"",'subject'=>"Station Comment"];
        if (!$class->addNewSupervisorFeedback($data)) {
            $viewer = $server->getViewer("Error");
            echo
            '<div class="border border-dark rounded bg-danger text-dark">
                <h3>Exception</h3>
                <small class="m-3">There was an error submitting the comment.</small><br>
                <a href="'.$server->config['application-root'].'/hr/stationcomment" class="btn btn-secondary" role="button">Back</a>
            </div>';
            $viewer->footer();
            exit();
        }
    }
    $viewer = $server->getViewer("Success");
    echo
    '<div class="border border-dark rounded bg-success text-dark p-3">
        <h3>Sucess</h3>
        <small class="m-3">Added multiple employee comments</small><br>
        <a href="'.$server->config['application-root'].'/hr/stationcomment" class="btn btn-secondary" role="button">Back</a>
    </div>';
    $viewer->footer();
    unset($_SESSION['station_employee_comment']);
    exit();
}