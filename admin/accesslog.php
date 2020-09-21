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
        case 'reset':
            $server->processingDialog(
                'resetLogFile',
                [],
                $server->config['application-root'].'/admin/accesslog'
            );
        break;
        default: main(); break;
    }
}
else {
    main();
}

function main () {
    global $server;
    include('submenu.php');

    $view = $server->getViewer('Permission Escalation');
    $view->sideDropDownMenu($submenu);
    $view->h1('User Privilege Escalation Log&#160;'.$view->linkButton('/admin/accesslog?action=reset','Reset Log File','danger',true));
    if (file_exists(INCLUDE_ROOT.'/var/access_log.xml')) {
        try {
        $log = simplexml_load_file(INCLUDE_ROOT.'/var/access_log.xml');
        $attempts = array_reverse($log->xpath('attempt'));
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            $attempts = array();
        }
        echo "<div class='list-group'>";
        foreach($attempts as $attempt) {
            $user = new User($server->pdo,$attempt->uid);
            echo "<a href='#' class='list-group-item list-group-item-action' data-toggle='collapse' data-target='#ID_{$attempt->id}'>";
            $view->bold("Escalation:&#160;");
            echo "{$attempt->id} <span class='badge badge-info float-right m-2'>{$attempt->date}</span></a>";
            echo "<div class='collapse' id='ID_{$attempt->id}'>";
            $view->responsiveTableStart();
            echo "<tr><th>ID:</th><td>{$attempt->id}</td></tr>";
            echo "<tr><th>Date:</th><td>{$attempt->date}</td></tr>";
            echo "<tr><th>Username:</th><td>".$user->getUserName()."</td></tr>";
            echo "<tr><th>IRL Name:</th><td>".$user->getFullName()."</td></tr>";
            echo "<tr><th>API Attempt:</th><td>{$attempt->api}</td></tr>";
            $view->responsiveTableclose();
            echo "</div>";
        }
        echo "</div>";
    }
    else 
        $view->bold('Access log file not found or is empty.');

    $view->footer();
}

function resetLogFile () {
    return unlink(INCLUDE_ROOT.'/var/access_log.xml');
}