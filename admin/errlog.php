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
                $server->config['application-root'].'/admin/errlog'
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

    $view = $server->getViewer('Error Log');
    $view->sideDropDownMenu($submenu);
    $view->h1('Error Log &#160;'.$view->linkButton('/admin/errlog?action=reset','Reset Log File','danger',true));
    echo "<p class='text-weight-light'>Clicking on each error will display the error, as well as give access to the backtrace.</p>";
    if (file_exists($server->config['error-log-file-path'])) {
        $log = simplexml_load_file($server->config['error-log-file-path']);
        $errors = array_reverse($log->xpath('error'));
        echo "<div class='list-group'>";
        foreach($errors as $err) {
            echo "<a href='#' class='list-group-item list-group-item-action' data-toggle='collapse' data-target='#{$err->id}'>";
            $view->bold("Error:&nbsp;");
            echo "{$err->id} <span class='badge badge-info float-right m-2'>{$err->date}</span></a>";
            echo "<div class='collapse' id='{$err->id}'>";
            $view->responsiveTableStart();
            echo "<tr><th>ID:</th><td>{$err->id}</td></tr>";
            echo "<tr><th>Date:</th><td>{$err->date}</td></tr>";
            echo "<tr><th>Error Code:</th><td>{$err->number}</td></tr>";
            echo "<tr><th>File:</th><td>{$err->file}</td></tr>";
            echo "<tr><th>Line:</th><td>{$err->line}</td></tr>";
            echo "<tr><th>Message:</th><td>{$err->message}</td></tr>";
            $view->responsiveTableClose();
            $view->beginBtnCollapse("Show/Hide Backtrace");
            $view->wrapInPre(html_entity_decode($err->trace));
            $view->endBtnCollapse();
            echo "</div>";
        }
        echo "</div>";
    }
    else $view->bold('Error log file not found or empty.');
    $view->footer();
}

function resetLogFile () {
    global $server;
    return unlink($server->config['error-log-file-path']); 
}