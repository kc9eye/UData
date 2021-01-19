<?php
//File: points.php
//
//About: License
//
//Copyright (C)2020 Paul W. Lane <kc9eye@gmail.com>
//
//This program is free software; you can redistribute it and/or modify
//
//it under the terms of the GNU General Public License as published by
//
//the Free Software Foundation; version 2 of the License.
//
//This program is distributed in the hope that it will be useful,
//
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
//
//GNU General Public License for more details.
//
//You should have received a copy of the GNU General Public License along
//
//with this program; if not, write to the Free Software Foundation, Inc.
//
//51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
require_once(dirname(__DIR__).'/lib/init.php');

if (!empty($_REQUEST['action'])) {
    switch ($_REQUEST['action']) {
        default: mainDisplay();break;
    }
}
else mainDisplay();

function mainDisplay() {
    global $server;
    include('submenu.php');
    $server->userMustHavePermission('viewProfiles');
    $e = new Employees($server->pdo);
    $points = $e->getPointsList();
    $view = $server->getViewer("Attendance Points");
    $view->sideDropDownMenu($submenu);
    $view->h2("Attendance Points Report");
    $view->h3("<small>As of:</small>".date("Y/m/d"));
    $view->printButton();
    $view->responsiveTableStart(["Employee Name","Total Points"]);
    foreach($points as $row) {
        echo "<tr><td>{$row['name']}</td><td>{$row['points']}</td></tr>";
    }
    $view->responsiveTableClose();
    $view->footer();
}