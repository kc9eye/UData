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

// $server->userMustHavePermission('permission');

if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'search':
            $sds = new SDSHandler($server->pdo,$server->config['data-root']);
            displaySearchResults($sds->searchSDS($_REQUEST['sds_search']));
        break;
        default: main();
    }
}
else {
    main();
}

function main () {
    global $server;
    include('submenu.php');
    $sds = new SDSHandler($server->pdo, $server->config['data-root']);
    $view = $server->getViewer('Safety First');
    $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->sideDropDownMenu($submenu);
    $view->h1('Hazard Communication');
    $form->fullPageSearchBar('sds_search','Search',null,false,'Search for SDS here');
    $h2 = "Latest Added";
    if ($server->checkPermission('addSDS')) 
        $h2 .= "&#160;".$view->linkButton("/safety/addsds","<span class='oi oi-plus' title='Add' aria-hidden='true'></span> Add SDS",'success',true);
    $view->h2($h2);
    $cols = ['Product','Distributor'];
    if ($server->checkPermission('editSDS')) {
        array_push($cols,'Delete');
        $edit = true;
    }
    else
        $edit = false;
    $view->responsiveTableStart($cols);
    foreach($sds->latestAdded() as $row) {
        echo "<tr><td><a href='{$view->PageData['approot']}/data/files?dis=inline&file=".urlencode($row['file'])."' download='{$row['file']}'>";
        echo "{$row['name']}</a></td><td>{$row['dist']}</td>";
        if ($edit) echo "<td>".$view->trashBtnSm("?action=delete&id={$row['id']}&fid={$row['fid']}",true)."</td>";
        echo "</tr>";
    }
    $view->responsiveTableClose();
    $view->footer();
}

function displaySearchResults ($results) {
    global $server;
    include('submenu.php');
    $view = $server->getViewer('Safety First');
    $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->sideDropDownMenu($submenu);
    $view->h1('Hazard Communication');
    $form->fullPageSearchBar('sds_search','Search',null,false,'Search for SDS here');
    $view->h2("Search Results");
    if (empty($results)) $view->bold("Nothing Found");
    else {
        $view->responsiveTableStart(['Product','Distributor']);
        foreach($results as $row) {
            echo "<tr><td><a href='{$view->PageData['approot']}/data/files?dis=inline&file=".urlencode($row['file'])."' download='{$row['file']}'>";
            echo "{$row['name']}</a></td><td>{$row['dist']}</td></tr>";
        }
        $view->responsiveTableClose();
    }
    $view->footer();
}