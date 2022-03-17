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

$server->userMustHavePermission('editSkills');

if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'add':
            $handler = new Training($server->pdo);
            $server->processingDialog(
                [$handler,'addSkillToEmployee'],
                [$_REQUEST],
                $server->config['application-root']."/hr/addskills?id={$_REQUEST['eid']}"
            );
        break;
        case 'update':
            $handler = new Training($server->pdo);
            $server->processingDialog(
                [$handler,'updateSkillTraining'],
                [$_REQUEST],
                $server->config['application-root']."/hr/addskills?id={$_REQUEST['eid']}"
            );
        break;
        case 'remove':
            $handler = new Training($server->pdo);
            $server->processingDialog(
                [$handler,'removeSkillFromEmployee'],
                [$_REQUEST['eid'],$_REQUEST['trid']],
                $server->config['application-root']."/hr/addskills?id={$_REQUEST['eid']}"
            );
        break;
        case 'saveTraining':
            saveTraining();
        break;
        default:
            addSkillsDisplay();
        break;
    }
}
else 
    addSkillsDisplay();

function addSkillsDisplay () {
    global $server;
    include('submenu.php');

    $skills = new Training($server->pdo);
    $emp = new Employee($server->pdo,$_REQUEST['id']);
    $et = $skills->getEmployeeTraining($_REQUEST['eid']);
    $view = $server->getViewer("HR: Add Skill Training");
    echo 
    '<h2>Training Change Form</h2>
    <h3><span class="text-muted fs-6">Training for:</span><b>'.$emp->getFullName().'</b></h3>
    <hr>
    <div id="newContent" class="m-2">
        <h4>Update Training Dates</h4>
        <form id="updateDates">
            <input type="hidden" name="eid" value="'.$_REQUEST['id'].'" />';
    foreach($et as $row) {
        echo '<div class="mb-2">';
        echo '<label class="form-label" for="'.$row['trid'].'">'.$row['description'].'</label>';
        echo '<input type="date" name="'.$row['trid'].'" value="'.$row['train_date'].'" required />';
        echo '</div>';
    }
    echo
    '        <button type="button" id="topSave" class="btn btn-outline-secondary">Save Changes</button>
        </form>
        <hr>
        <h4>Add New Training</h4>
        <form id="empTraining">
            <input type="hidden" name="action" value="saveTraining" />
            <input type="hidden" name="eid" value="'.$_REQUEST['id'].'" />';
    foreach($skills->getAllAvailableTraining() as $row) {
        echo '<div class="form-check">';
        echo '<input type="checkbox" class="form-check-input" id="'.$row['id'].'" name="training[]" value="'.$row['id'].'" ';
        foreach($et as $training) {
            if ($training['trid'] == $row['id']) {
                echo "checked ";
            }
        }
        echo "/>";
        echo '<label for="'.$row['id'].'" class="form-check-label">'.$row['description'].'</lable>';
        echo '</div>';
    }
    echo 
    '       <button type="button" id="bottomSave" class="btn btn-outline-secondary">Save Changes</button>
        </form>
    </div>
    <script>
        let theForm = document.getElementById("empTraining");
        let tSave = document.getElementById("topSave");
        let bSave = document.getElementById("bottomSave");
        tSave.addEventListener("click",()=>{alert("FIX ME");});
        bSave.addEventListener("click",saveChanges);

        async function saveChanges(event){
            event.preventDefault();
            tSave.setAttribute("disabled","disabled");
            bSave.setAttribute("disabled","disabled");
            tSave.innerHTML = "<span class=\'spinner-border spinner-border-sm\'></span>";
            bSave.innerHTML = "<span class=\'spinner-border spinner-border-sm\'></span>";
            let resp = await fetch(
                "'.$server->config['application-root'].'/hr/addskills",
                {method:"POST",body:new FormData(theForm)}
            );
            document.getElementById("newContent").innerHTML = await resp.text();
        }
    </script>';

    $view->footer();
}

function saveTraining() {
    global $server;
    $pntr = $server->pdo->prepare("insert into emp_training values (:eid,:trid,now(),:uid)");
    $training = new Training($server->pdo);
    $existing = array();
    foreach($training->getEmployeeTraining($_REQUEST['eid']) as $et) {
        array_push($existing,$et['trid']);
    }
    $diff = array_diff($_REQUEST['training'],$existing);
    if (!empty($diff)) {
        $server->pdo->beginTransaction();
        try {
            foreach($diff as $new) {
                if (!$pntr->execute([':eid'=>$_REQUEST['eid'],':trid'=>$new,':uid'=>$server->security->secureUserID]))
                    throw new Exception(print_r($pntr->errorInfo(),true));
            }
            $server->pdo->commit();
            exit(
                '<h6 class="text-success m-2">Update Successful</h6>
                <button class="btn btn-outline-success" type="button" onclick="window.open(\''.$server->config['application-root'].'/hr/addskills?id='.$_REQUEST['eid'].'\',\'_self\')">
                Back
                </button>'
            );
        }
        catch (Exception $e) {
            $server->pdo->rollBack();
            trigger_error($e->getMessage(),E_USER_WARNING);
            exit('<span class="text-monospace text-danger">There was an exception during the update, can not continue...</span>');
        } 
    }
    else exit("<pre>Nothing to add</pre>");   
    exit("<pre>Unkown Error</pre>");
}