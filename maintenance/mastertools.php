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
include('./submenu.php');

//Access permissions
$server->userMustHavePermission('viewMasterTools');

//Class construction
$bm = new Maintenance($server->pdo);

//Controlling section
if (!empty($_REQUEST['tool_search'])) {
    if (($content = $bm->searchTools($_REQUEST['tool_search'])) === false) {
        $content = "{$_REQUEST['tool_search']} not found..";
    }
    searchDisplay();
}
elseif (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'add':
            $server->userMustHavePermission('editMasterTools');
            addForm();
        break;
        case 'update':
            $server->userMustHavePermission('editMasterTools');
            $server->processingDialog([$bm,'updateToolByID'],[$_REQUEST],$server->config['application-root'].'/maintenance/mastertools');
        break;
        case 'remove':
            $server->userMustHavePermission('editMasterTools');
            $server->processingDialog([$bm,'removeToolByID'],[$_REQUEST['id']],$server->config['application-root'].'/maintenance/mastertools');
        break;
        case 'submit':
            $server->userMustHavepermission('editMasterTools');
            $server->processingDialog([$bm,'addNewTool'],[$_REQUEST],$server->config['application-root'].'/maintenance/mastertools?action=add');
        break;
        case 'list':
            listDisplay();
        break;
        case 'edit':
            $server->userMustHavePermission('editMasterTools');
            editDisplay();
        break;
        default:
            searchDisplay();
        break;
    }
}
else {
    searchDisplay();
}

//Display available
function editDisplay () {
    global $server,$bm,$submenu;
    $tool = $bm->getToolFromID($_REQUEST['id']);
    $cats = $bm->getExistingCategories();
    $select = [];
    foreach($cats as $cat) {
        array_push($select,[$cat,$cat]);
    }
    $view = $server->getViewer("Maintenance: Edit Tool");
    $view->sideDropDownMenu($submenu);
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    $form->newForm("Edit Tool ".$view->trashBtnSm("/maintenance/mastertools?action=remove&id={$_REQUEST['id']}",true));
    $form->inputCapture('category','Current Cat.',$tool['category'],['required'=>'#newcat:blank'],'To select a category below, you must remove the current category, that is in this box.');
    $form->selectBox('newcat','New Category',$select,['required'=>'#category:blank']);
    $form->inputCapture('description','Description',$tool['description'],true);
    $form->hiddenInput('action','update');
    $form->hiddenInput('uid',$server->currentUserID);
    $form->hiddenInput('id',$_REQUEST['id']);
    $form->submitForm('Update',false,$server->config['application-root'].'/maintenance/mastertools');
    $form->endForm();
    $view->footer();
}

function addForm () {
    global $server,$bm,$submenu;
    $cats = $bm->getExistingCategories();
    $select = [['torque wrench','torque wrench']];
    foreach($cats as $cat) {
        array_push($select,[$cat,$cat]);
    }
    $view = $server->getViewer("Maintenance: Add Tooling");
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->sideDropDownMenu($submenu);
    $form->newForm("Add Tooling");
    $form->hiddenInput('uid',$server->currentUserID);
    $form->hiddenInput('action','submit');
    $form->selectBox('category','Category',$select,['required'=>"'#newcat:blank'"]);
    $form->inputCapture('newcat','New Category',null,['required'=>"'#categroy:blank'"]);
    $form->inputCapture('description','Description',null,true,'The description of the tool being added.');
    $form->submitForm('Add Tool',true,$view->PageData['approot'].'/maintenance/mastertools?action=list');
    $form->endForm();
    $view->footer();
}

function listDisplay () {
    global $server,$bm,$submenu;
    $edit = $server->checkPermission('editMasterTools');
    $view = $server->getViewer("Maintenance: Tooling");
    $view->sideDropDownMenu($submenu);
    $view->h1("Tooling Master List");
    $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');
    $btns['Master Tool Search'] = 'window.open("'.$view->PageData['approot'].'/maintenance/mastertools","_self");';
    if ($edit) $btns['Add Tooling'] = 'window.open("'.$view->PageData['approot'].'/maintenance/mastertools?action=add","_self");';
    $form->inlineButtonGroup($btns);
    $view->hr();
    $heading = $edit ? ['Category','Description','Edit'] : ['Category','Description'];
    $view->responsiveTableStart($heading);
    foreach($bm->getToolListing() as $row) {
         echo "<tr><td>{$row['category']}</td><td>{$row['description']}</td>";
         if ($edit)
            echo "<td>".$view->editBtnSm("{$view->PageData['approot']}/maintenance/mastertools?action=edit&id={$row['id']}",true,true)."</td>";
        echo "</tr>\n";
    }
    $view->responsiveTableClose();
    $view->footer();
}

function searchDisplay () {
    global $server,$bm,$submenu,$content;
    $view = $server->getViewer("Maintenance: Search Tools");
    $view->sideDropDownMenu($submenu);
    $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');
    $btns = ['List All'=>'window.open("?action=list","_self");'];
    if ($server->checkPermission('editMasterTools')) $btns['Add Tooling'] = 'window.open("'.$view->PageData['approot'].'/maintenance/mastertools?action=add","_self");';
    $view->h1("Master Tool List Search");
    $form->inlineButtonGroup($btns);
    $view->hr();
    $form->fullPageSearchBar('tool_search','Tooling Search',null,false,"To narrow search results include '&' or 'AND' between search terms.");
    if (is_array($content)) {
        $view->responsiveTableStart(['Description','Category']);
        foreach($content as $row) {
            echo "<tr><td><a href='{$view->PageData['approot']}/maintenance/mastertools?action=edit&id={$row['id']}'>{$row['description']}</a></td><td>{$row['category']}</td></tr>\n";
        }
        $view->responsiveTableClose();
    }
    else {
        echo $content;
    }
    $view->footer();
}