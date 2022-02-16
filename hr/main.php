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

if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'search':
            $model = new Employees($server->pdo);
            $formater = new SearchStringFormater();
            $results = $model->searchEmployees($formater->formatSearchString($_REQUEST['emp_search']));
            resultsDisplay($results);
        break;
        default: searchDisplay(); break;
    }
}
else searchDisplay();

function searchDisplay () {
    global $server;
    include('submenu.php');
    $server->userMustHavePermission('viewProfiles');
    $view = $server->getViewer('HR: Profiles');
    $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->sideDropDownMenu($submenu);

    $buttongrp['List Active Profiles'] = "window.open(\"{$view->PageData['approot']}/hr/lists?action=activeprofiles\",\"_self\");";
    if ($server->checkPermsArray(['initEmployeeReview','reviewEmployee']))
        $buttongrp['List Open Reviews'] = "window.open(\"{$view->PageData['approot']}/hr/lists?action=openreviews\",\"_self\");";
    if ($server->checkPermission('addNewProfile'))
        $buttongrp['Add New Profile'] = "window.open(\"{$view->PageData['approot']}/hr/addnew\",\"_self\");";
    if ($server->checkPermission('initEmployeeReview'))
        $buttongrp['Pending Follow Up Reviews'] = "window.open(\"{$view->PageData['approot']}/hr/pending\",\"_self\");";

    $view->h1('Search Employee Profiles');
    $form->inlineButtonGroup($buttongrp);
    $view->br();
    echo "&#160;";
    $form->fullPageSearchBar('emp_search','Employee Search',null,false,"Search for people to add injuries,training,comments");
    $view->footer();
}

function resultsDisplay ($results) {
    global $server;
    include('submenu.php');
    $view = $server->getViewer("HR: Employees");
    $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');    
    $view->sideDropDownMenu($submenu);
    
    $buttongrp['List Active Profiles'] = "window.open(\"{$view->PageData['approot']}/hr/lists?action=activeprofiles\",\"_self\");";
    if ($server->checkPermsArray(['initEmployeeReview','reviewEmployee']))
        $buttongrp['List Open Reviews'] = "window.open(\"{$view->PageData['approot']}/hr/lists?action=openreviews\",\"_self\");";
    if ($server->checkPermission('addNewProfile'))
        $buttongrp['Add New Profile'] = "window.open(\"{$view->PageData['approot']}/hr/addnew\",\"_self\");";

    $view->h1('Search Employee Profiles');
    $form->inlineButtonGroup($buttongrp);
    $view->br();
    echo "&#160;";
    $form->fullPageSearchBar('emp_search','Employee Search',null,false,"Search for people to add injuries,training,comments");
    if (empty($results)) {
        $view->bold("Nothing Found matching: {$_REQUEST['emp_search']}");
    }
    else {
        $view->responsiveTableStart(['Name','Start Date','End Date']);
        foreach($results as $row) {
            echo "<tr><td><a href='{$server->config['application-root']}/hr/viewemployee?id={$row['id']}'>{$row['name']}<a></td>";
            echo "<td>{$row['start_date']}</td><td>{$row['end_date']}</td></tr>\n";
        }
        $view->responsiveTableClose();
    }
    $view->footer();
}