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

$server->userMustHavePermission('editInjuryReport');

if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'listall':
            $handler = new Injuries($server->pdo);
            listAllDisplay($handler->listAll());
        break;
        default: 
            searchDisplay(); 
        break;
    }
}
elseif (!empty($_REQUEST['search_incidents'])) {
    $handler = new Injuries($server->pdo);
    $formater = new SearchStringFormater();
    resultsDisplay(
        $handler->searchInjuries(
            $formater->formatSearchString($_REQUEST['search_incidents'])
        )
    );
}
else
    searchDisplay();

function searchDisplay () {
    global $server;
    include('submenu.php');
    
    $view = $server->getViewer('HR: Incidents');
    $view->sideDropDownMenu($submenu);
    $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->h1('Search Incidents');
    $form->inlineButtonGroup([
        'Add New'=>"window.open(\"{$view->PageData['approot']}/hr/main\",\"_self\");",
        'List All'=>"window.open(\"{$view->PageData['approot']}/hr/incidents?action=listall\",\"_self\");"
    ]);
    $view->br();
    $view->insertTab();
    $view->br();
    $form->fullPageSearchBar('search_incidents','Search Incidents');
    $view->footer();
}

function resultsDisplay (Array $results) {
    global $server;
    include('submenu.php');

    $view = $server->getViewer('HR: Incidents');
    $view->sideDropDownMenu($submenu);
    $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->h1('Search Incidents');
    $form->inlineButtonGroup([
        'Add New'=>"window.open(\"{$view->PageData['approot']}/hr/main\",\"_self\");",
        'List All'=>"window.open(\"{$view->PageData['approot']}/hr/incidents?action=listall\",\"_self\");"
    ]);
    $view->br();
    $view->insertTab();
    $view->br();
    $form->fullPageSearchBar('search_incidents','Search Incidents');

    if (!empty($results)) {
        $view->responsiveTableStart(['ID','Date','Injured','Recordable','Clinic','Reporter']);
        foreach($results as $row) {
            $clinic = ($row['followup_medical']) ? 'Yes' : 'No';
            $recordable = ($row['recordable']) ? 'Yes' : 'No';
            echo "<tr><td><a href='{$view->PageData['approot']}/hr/injuryreport?action=view&id={$row['id']}'>{$row['id']}</a></td>";
            echo "<td>".$view->formatUserTimestamp($row['injury_date'],true)."</td><td>{$row['injured']}</td><td>{$recordable}</td><td>{$clinic}</td><td>{$row['reporter']}</td></tr>\n";
        }
        $view->responsiveTableClose();
    }
    else 
        echo "Nothing found for: {$_REQUEST['search_incidents']}";
    $view->footer();
}

function listAllDisplay (Array $results) {
    global $server;
    include('submenu.php');

    $view = $server->getViewer('HR: Incidents');
    $view->sideDropDownMenu($submenu);
    $view->linkButton('/hr/incidents',"<span class='glyphicon glyphicon-arrow-left'></span> Back");
    $view->responsiveTableStart(['ID','Injured','Date','Recordable','Clinic','Reporter']);
    foreach($results as $row) {
        $recordable = ($row['recordable']) ? 'Yes' : 'No';
        $clinic = ($row['followup_medical']) ? 'Yes' : 'No';
        echo "<tr><td><a href='{$view->PageData['approot']}/hr/injuryreport?action=view&id={$row['id']}'>{$row['id']}</a></td>";
        echo "<td>{$row['injured']}</td><td>".$view->formatUserTimestamp($row['injury_date'],true)."</td><td>{$recordable}</td><td>{$clinic}</td><td>{$row['reporter']}</td></tr>\n";
    }
    $view->responsiveTableClose();
    $view->footer();
}