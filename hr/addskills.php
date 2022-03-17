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

    $view = $server->getViewer("HR: Add Skill Training");
    echo 
    '<h2>Add Training Form</h2>
    <h3><span class="text-muted fs-6">Training for:</span><b>'.$emp->getFullName().'</b></h3>
    <hr>
    <div id="newContent" class="m-2">
        <form id="empTraining">
            <input type="hidden" name="action" value="saveTraining" />
            <input type="hidden" name="eid" value="'.$_REQUEST['id'].'" />
            <button type="button" id="topSave" class="btn btn-outline-secondary">Save Changes</button>';
    foreach($skills->getAllAvailableTraining() as $row) {
        echo '<div class="form-check">';
        echo '<input type="checkbox" class="form-check-input" id="'.$row['id'].'" name="training[]" value="'.$row['id'].'" ';
        foreach($skills->getEmployeeTraining($_REQUEST['id']) as $training) {
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
        tSave.addEventListener("click",saveChanges);
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

    echo "<pre>",var_export([$server->security->secureUserID,$_REQUEST],true),"</pre>";
}