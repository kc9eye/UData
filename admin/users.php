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

$server->userMustHavePermission('adminAll');

if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'search':
            $app = new Application($server->pdo);
            $results = $app->searchUsers($_REQUEST['user_search']);
            main($results);
        break;
        case 'listall':
            displayAllUsers();
        break;
        case 'view':
            displayUserAdmin();
        break;
        default: main(); break;
    }
}
else {
    main();
}

function main ($search_results = null) {
    global $server;
    include('submenu.php');
    $view = $server->getViewer('Admin: Users');
    $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->sideDropDownMenu($submenu);
    $view->h1("User Account Adminstration",true);
    $view->linkButton('/admin/users?action=listall','List All Users','info');
    $view->br();
    echo "&#160;";
    $form->fullPageSearchBar('user_search','Search Users');
    if (!is_null($search_results)) {
        if (empty($search_results)) $view->bold('Nothing Found');
        else {
             $view->responsiveTableStart();
            foreach($search_results as $row) 
                $view->tableRow([[
                    "<span class='oi oi-person title='User' aria-hidden='true'></span>&#160;<a href='{$view->PageData['approot']}/admin/users?action=view&uid={$row['id']}'>{$row['firstname']} {$row['lastname']}</a>",
                    "{$row['username']}"
                ]]);
            $view->responsiveTableClose();
        }
    }
    $view->footer();
}

function displayAllUsers () {
    global $server;
    include('submenu.php');
    $app = new Application($server->pdo);
    $view = $server->getViewer('Admin: Users');
    $view->sideDropDownMenu($submenu);
    $view->h1("User Account Administartion");
    $view->responsiveTableStart();
    foreach($app->getUserList() as $row) {
        $view->tableRow([[
            "<span class='oi oi-person' title='User' aria-hidden='true'></span>&#160;<a href='{$view->PageData['approot']}/admin/users?action=view&uid={$row['id']}'>{$row['firstname']} {$row['lastname']}</a>",
            $row['username']
        ]]);
    }
    $view->responsiveTableClose();
    $view->footer();
}

function displayUserAdmin () {
    global $server;
    include('submenu.php');
    $app = new Application($server->pdo);
    $notifier = new Notification($server->pdo,$server->mailer);
    $user = $app->getUserData($_REQUEST['uid']);
    $roles = $app->getUserRoles($_REQUEST['uid']);
    $notifications = $notifier->getUserNotifications($_REQUEST['uid']);
    $unusedroles = array();
    $unusedalerts = array();
    foreach($app->unusedRoleSet($_REQUEST['uid']) as $row)
        array_push($unusedroles,[$row['id'],$row['name']]);
    foreach($notifier->getUnusedNotifications($_REQUEST['uid']) as $row) 
        array_push($unusedalerts,[$row['id'],$row['description']]);

    $view = $server->getViewer("Admin: Users");
    $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->sideDropDownMenu($submenu);
    $view->h2("User Info ".$view->linkButton('/admin/users?action=delete&uid='.$_REQUEST['uid'].'&pid='.$user['pid'],'Delete','danger',true));
    $view->responsiveTableStart();
    echo "<tr><th>UID:</th><td>{$user['id']}</td></tr>\n";
    echo "<tr><th>Username:</th><td>{$user['username']}</td></tr>\n";
    echo "<tr><th>First Name:</th><td>{$user['firstname']}</td></tr>\n";
    echo "<tr><th>Last Name:</th><td>{$user['lastname']}</td></tr>\n";
    echo "<tr><th>Alt. Email:</th><td>{$user['alt_email']}</td></tr>\n";
    $view->responsiveTableClose();

    $view->h2("User Roles");
    $view->responsiveTableStart();
    foreach($roles as $row) {
        echo "<tr><td>{$row['name']}</td><td>";
        $view->trashBtnSm('/admin/users?action=removerole&rid='.$row['id'].'&uid='.$_REQUEST['uid']);
        echo "</td></tr>\n";
    }
    echo "<tr><td colspan='2'>";
    $form->newInlineForm();
    $form->hiddenInput('action','addrole');
    $form->hiddenInput('uid',$_REQUEST['uid']);
    $form->inlineSelectBox('rid','Add Role',$unusedroles,true);
    $form->inlineSubmit();
    $form->endInlineForm();
    echo "</td></tr>\n";
    $view->responsiveTableClose();

    $view->h2("User Notifications");
    $view->responsiveTableStart();
    foreach($notifications as $row) {
        echo "<tr><td>{$row['description']}</td><td>";
        $view->trashBtnSm('/admin/users?action=removenotification&uid='.$_REQUEST['uid'].'&nid='.$row['id']);
        echo "</td></tr>\n";
    }
    echo "<tr><td colspan='2'>";
    $form->newInlineForm();
    $form->hiddenInput('action','addnotification');
    $form->hiddenInput('uid',$_REQUEST['uid']);
    $form->inlineSelectBox('nid',"Add Notification",$unusedalerts,true);
    $form->inlineSubmit();
    $form->endInlineForm();
    echo "</td></tr>\n";
    $view->responsiveTableClose();
    $view->footer();
}