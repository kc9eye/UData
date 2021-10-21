<?php
/* This file is part of UData.
 * Copyright (C) 2018 Paul W. Lane <kc9eye@outlook.com>
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

//Access permissions check
$server->userMustHavePermission('editWorkCell');

//Controller section, take action and/or decide on view to present
if (!empty($_REQUEST['search_tools'])) {
    $tools = new Maintenance($server->pdo);
    $search_results = $tools->searchTools($_REQUEST['search_tools']);
    editView();
}
elseif (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'add':
            addView();
        break;
        case 'commit':
            $cells = new WorkCells($server->pdo);
            $server->processingDialog([$cells,'addToolingToCell'],[$_REQUEST],$server->config['application-root'].'/cells/celltools?id='.$_REQUEST['cellid']);
        break;
        case 'edit':
            editCellToolView();
        break;
        case 'remove':
            $cells = new WorkCells($server->pdo);
            $server->processingDialog([$cells,'removeToolingFromCell'],[$_REQUEST['toolid']],$server->config['application-root'].'/cells/celltools?id='.$_REQUEST['cellid']);
        break;
        default:
            editView();
        break;
    }
}
else {
    editView();
}

//Available views
function editView () {
    global $server,$search_results;
    $cell = new WorkCell($server->pdo,$_REQUEST['id']);
    $view = $server->getViewer("Workcell: Tooling");
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->h2("<small>Add Tooling to:</small> {$cell->Name} 
        <a href='{$view->PageData['approot']}/cells/main?action=view&id={$_REQUEST['id']}' class='btn btn-info' role='button'>
            <span class='glyphicon glyphicon-arrow-left'></span> Back
        </a>"
    );
    $form->searchBox('search_tools','Search for Tools to Add',null,false,"To narrow search results include '&' or 'AND' between search terms.");
    if (!empty($search_results)) {
        if (is_array($search_results)) {
            $view->responsiveTableStart(['Description','Category'],true);
            foreach($search_results as $row) {
                echo "<tr><td><a href='?action=add&id={$row['id']}&cellid={$_REQUEST['id']}'>{$row['description']}</td><td>{$row['category']}</td></tr>\n";
            }
            $view->responsiveTableClose(true);
        }
        else {
            $view->bold($_REQUEST['search_tools']);
            echo " not found.";
        }
    }
    $view->hr();
    $view->responsiveTableStart(['Qty.','Description','Category','Torque Value','Torque Units','Torque Label','Edit']);
    foreach($cell->Tools as $row) {
        echo "<tr><td>{$row['qty']}</td><td>{$row['description']}</td><td>{$row['category']}</td><td>{$row['torque_val']}</td>";
        echo "<td>{$row['torque_units']}</td><td>{$row['torque_label']}</td>";
        // echo "<td>".$view->trashBtnSm("/cells/celltools?action=remove&toolid={$row['id']}&cellid={$_REQUEST['id']}",true)."</td></tr>";
        echo "<td>".$view->editBtnSm("/cells/celltools?action=edit&toolid={$row['id']}&cellid={$_REQUEST['id']}",true)."</td></tr>";
    }
    $view->responsiveTableClose();
    $view->hr();

    $view->footer();
}

function editCellToolView() {
    global $server;
    $celltool = (new WorkCells($server->pdo))->getCellToolData($_REQUEST['toolid']);
    $tool = (new Tool($server->pdo,$celltool['toolid']));
    $cell = new WorkCell($server->pdo,$_REQUEST['cellid']);
    $view = $server->getViewer("Edit Cell Tool");
    $view->h1("{$cell->Product} {$cell->Name}");
    $view->h2("{$tool->Category} {$tool->Description}");
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    $form->inputCapture("qty","Quantity",$celltool['qty'],true);
    $form->submitForm("submit",false,'/cells/celltools?id='.$_REQUEST['cellid']);
    $form->endForm();
    $view->footer();
}

function addView () {
    global $server;
    $cell = new WorkCell($server->pdo,$_REQUEST['cellid']);
    $tool = new Tool($server->pdo,$_REQUEST['id']);
    $view = $server->getViewer("WorkCell: Add Tool");
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->h2("<small>Add Tool to:</small>{$cell->Name}",true);
    $form->newForm();
    $form->hiddenInput('cellid',$_REQUEST['cellid']);
    $form->hiddenInput('toolid',$_REQUEST['id']);
    $form->hiddenInput('uid',$server->currentUserID);
    $form->hiddenInput('action','commit');
    $form->inputCapture('qty','Quantity','1',true,'How many of these tools are required?');
    if ($tool->Category == 'torque wrench') {
        $value = [];
        for($cnt=1;$cnt<=500;$cnt++) {
            array_push($value,["{$cnt}","{$cnt}"]);
        }
        $form->selectBox('torque_val','Torque Value',$value,true);
        $form->selectBox('torque_units','Torque Units',[['in/lbs','Inch Pounds'],['ft/lbs','Foot Pounds'],['nwt/mts','Newton Meters']],true);
        $form->inputCapture('torque_label','Torque Label',null,true);

    }
    else {
        $form->hiddenInput('torque_val','');
        $form->hiddenInput('torque_units','');
        $form->hiddenInput('torque_label','');
    }
    $form->labelContent('Tool',$tool->Description);
    $form->submitForm('Add',false,'?id='.$_REQUEST['cellid']);
    $form->endForm();
    $view->footer();
}