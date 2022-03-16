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
    '<h2>Training Change Form</h2>
    <h3><span class="text-muted fs-5">Training for:</span>'.$emp->getFullName().'</h3>
    <hr>
    <div class="newContent m-2">
        <form id="empTraining">';
    foreach($skills->getAllAvailableTraining() as $row) {
        echo '<div class="form-check">';
        echo '<input type="checkbox" class="form-check-input" id="'.$row['id'].'" name="trainging[]" value="'.$row['id'].'" ';
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
    '   </form>
    </div>';

    $view->footer();
}