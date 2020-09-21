<?php
/* This file is part of UData
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
$server->userMustHavePermission('adminAll');

if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'create':
            $server->processingDialog(
                [new Application($server->pdo), 'addRole'],
                [$_REQUEST['name']],
                $server->config['application-root'].'/admin/roles'
            );
        break;
        case 'delete':
            $server->processingDialog(
                [new Application($server->pdo), 'deleteRole'],
                [$_REQUEST['rid']],
                $server->config['application-root'].'/admin/roles'
            );
        break;
        case 'add':
            $server->processingDialog(
                [new Application($server->pdo),'addPermToRole'],
                [$_REQUEST['pid'],$_REQUEST['rid']],
                $server->config['application-root'].'/admin/roles'
            );
        break;
        case 'remove':
            $server->processingDialog(
                [new Application($server->pdo),'removePermFromRole'],
                [$_REQUEST['pid'],$_REQUEST['rid']],
                $server->config['application-root'].'/admin/roles'
            );
        break;
        default: main();
    }
}
else main();

function main () {
    global $server;
    include('./submenu.php');
    $app = new Application($server->pdo);
    $view = $server->getViewer('Admin: User Roles');
    $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->sideDropDownMenu($submenu);
    $view->h1("User Roles",true);
    echo "<p class='font-weight-light'>Clicking on each role button will allow you to add or remove ";
    echo "permissions from that specific role. Permissions are assigned to Roles, and in turn, Roles are ";
    echo "assigned to Users. Custom roles can be created, giving great flexibility in the security model. ";
    echo "Each View Controller has access permissions which can be seen at the bottom of each view for administrators.";
    echo "These are the permissions that must be set on a users role to access that view or parts of that view. ";
    echo "A permission that is listed as <mark>Required for Access</mark>, must be in a role assigned to a user to access ";
    echo "that particular view. Permissions that are listed under <mark>Page Access Permissions</mark>, are required for ";
    echo "accessing only parts of the that specific view. <strong>Note:</strong> the <i>adminAll</i> permission is listed ";
    echo "for all views as it is required to view the permission listing. The <i>adminAll</i> permission should never be assigned ";
    echo "to a nomral users role sets. It should be reserved for 'Adminstrators' <strong>only</strong>.</p>";
    foreach($app->getRole() as $role) {
        $unused = array();
        foreach($app->unusedPermissionSet($role['id']) as $set)
            array_push($unused,[$set['id'],$set['name']]);

        $view->beginBtnCollapse($view->bold($role['name'],true));
        echo "<a href='?action=delete&rid={$role['id']}' class='btn btn-danger float-right'>Delete {$role['name']}</a>";
        
        $form->newInlineForm();
        $form->hiddenInput('action','add');
        $form->hiddenInput('rid',$role['id']);
        $view->responsiveTableStart(['Permission','Remove']);
        foreach($app->getPermsFromRole($role['id']) as $perm)
            echo "<tr><td>{$perm['name']}</td><td>".$view->trashBtnSm('/admin/roles?action=remove&rid='.$role['id'].'&pid='.$perm['id'],true)."</td></tr>";
        echo "<tr><td>";
        $form->inlineSelectBox('pid','Permissions',$unused);
        echo "</td><td>";
        $form->inlineSubmit('Add');
        echo "</td></tr>";
        $view->responsiveTableClose();
        $form->endInlineForm();

        $view->endBtnCollapse();
        $view->hr();
    }

    $view->h3("Create New Role");
    $form->newInlineForm();
    $form->hiddenInput('action','create');
    $view->responsiveTableStart();
    echo "<tr><td>";
    $form->inlineInputCapture('name','New Role Name',null,true);
    echo "</td><td>";
    $form->inlineSubmit('Create');
    echo "</td></tr>";
    $view->responsiveTableClose();
    $form->endInlineForm();
    $view->footer();
}
