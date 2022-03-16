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
    // $used_skills = $skills->getEmployeeTraining($_REQUEST['id']);
    // $unused_skills = $skills->getUnusedTraining($_REQUEST['id']);
    // $select = array();
    // foreach($unused_skills as $row) {
    //     array_push($select,[$row['id'],$row['description']]);
    // }

    $view = $server->getViewer("HR: Add Skill Training");
    foreach($skills->getEmployeeTraining($_REQUEST['id']) as $row) {
        echo '<div class="form-check">';
        echo '<input type="checkbox" class="form-check-input" id="'.$row['trid'].'" name="trainging[]" value="'.$row['trid'].'" ';
        foreach($skills->getAllAvailableTraining() as $training) {
            if ($training['id'] == $row['trid']) {
                echo "checked ";
            }
            echo "/>";
        }
        echo '<label for="'.$row['trid'].'" class="form-check-label">'.$row['description'].'</lable>';
        echo '</div>';
    }
    // $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');
    // $view->sideDropDownMenu($submenu);
    // $view->h1(
    //     "<small>Add Skills to:</small>
    //     {$emp->Profile['first']} {$emp->Profile['middle']} {$emp->Profile['last']} {$emp->Profile['other']}".
    //     $view->linkButton("/hr/viewemployee?id={$_REQUEST['id']}","<span class='glyphicon glyphicon-arrow-left'></span> Back",'info',true)
    // );
    // $form->newInlineForm();
    // $form->hiddenInput('action','add');
    // $form->hiddenInput('uid',$server->currentUserID);
    // $form->hiddenInput('eid',$_REQUEST['id']);
    // $form->inlineSelectBox('trid','Add',$select,true);
    // $form->inlineSubmit('Add');
    // $form->endInlineForm();

    // $view->responsiveTableStart(['Training','','Trash']);
    // foreach($used_skills as $row) {
    //     echo "<tr><td>{$row['description']}</td><td>";
    //     $form->newInlineForm();
    //     $form->hiddenInput('action','update');
    //     $form->hiddenInput('uid',$server->currentUserID);
    //     $form->hiddenInput('eid',$_REQUEST['id']);
    //     $form->hiddenInput('trid',$row['trid']);
    //     $form->inlineInputCapture('train_date','Date',$row['train_date'],['dateISO'=>'true']);
    //     $form->inlineSubmit('Update');
    //     $form->endInlineForm();
    //     echo "</td><td>";
    //     $view->trashBtnSm("/hr/addskills?action=remove&trid={$row['trid']}&eid={$_REQUEST['id']}");
    //     echo "</td></tr>\n";
    // }
    // $view->responsiveTableClose();
    $view->footer();
}