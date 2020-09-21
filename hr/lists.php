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

if (!$server->checkPermsArray(['initEmployeeReview','reviewEmployee','viewProfiles'])) $server->notAuthorized(true);

if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'activeprofiles':
            listActiveEmployeeProfiles();
        break;
        case 'openreviews':
            listOpenReviews();
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
    $view = $server->getViewer('HR Lists');
    $view->sideDropDownMenu($submenu);
    $view->h1('HR Listings');
    $view-h2('Nothing here, you may have arrived by accident.');
    $view->footer();
}

function listActiveEmployeeProfiles () {
    global $server;
    include('submenu.php');
    $server->userMustHavePermission('viewProfiles');
    $handler = new Employees($server->pdo);
    $view = $server->getViewer('Active Employee Profiles');
    $view->sideDropDownMenu($submenu);
    $view->h1('Active Employee Profiles');
    $view->responsiveTableStart(['Active Employee Name']);
    foreach($handler->getActiveEmployeeList() as $row) {
        echo "<tr><td>";
        echo "<span class='oi oi-person' title='Person' aria-hidden='true'></span>";
        echo "<a href='{$view->PageData['approot']}/hr/viewemployee?id={$row['eid']}'>";
        echo "&#160;{$row['name']}</a></td></tr>";
    }
    $view->responsiveTableClose();
    $view->addScrollTopBtn();
    $view->footer();
}

function listOpenReviews () {
    global $server;
    include('submenu.php');
    if (!$server->checkPermsArray(['initEmployeeReview','reviewEmployee'])) $server->notAuthorized(true);
    $handler = new Employees($server->pdo);
    $view = $server->getViewer('Open Reviews');
    $view->sideDropDownMenu($submenu);
    $view->h1('Currently Open Reviews');
    $view->responsiveTableStart(['Active Employee Name']);
    foreach($handler->getActiveEmployeeList() as $row) {
        if ($handler->getReviewStatus($row['eid'])) {
            echo "<tr><td>";
            echo "<span class='oi oi-person' title='Person' aria-hidden='true'></span>";
            echo "<a href='{$view->PageData['approot']}/hr/employeereview?eid={$row['eid']}'>";
            echo "&#160;{$row['name']}</a></td></tr>";
        }
    }
    $view->responsiveTableClose();
    $view->addScrollTopBtn();
    $view->footer();
}