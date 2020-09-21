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

$server->userMustHavePermission('searchSkills');

if (!empty($_REQUEST['skill_search'])) {
    $model = new Training($server->pdo);
    $formater = new SearchStringFormater();
    $results = $model->searchSkills($formater->formatSearchString($_REQUEST['skill_search']));
    resultsDisplay($results);
}
else searchDisplay();

function searchDisplay () {
    global $server;
    include('submenu.php');
    
    $view = $server->getViewer('HR: Skills');
    $view->sideDropDownMenu($submenu);
    $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');
    if ($server->checkPermission('addNewTraining'))
        $view->h1('Search Employee Skills '.$view->linkButton('/hr/addtraining',"<span class='glyphicon glyphicon-plus'></span> Add Training",'info',true));
    else
        $view->h1('Search Employee Skills');
    $form->fullPageSearchBar('skill_search','Skill Search');
    $view->footer();
}

function resultsDisplay ($results) {
    global $server;
    $view = $server->getViewer("HR: Skills");
    include('submenu.php');
    $view->sideDropDownMenu($submenu);
    $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');
    if ($server->checkPermission('addNewTraining'))
        $view->h1('Search Employee Skills '.$view->linkButton('/hr/addtraining',"<span class='oi oi-plus'></span> Add Training",'info',true));
    else
        $view->h1('Search Employee Skills');
    $form->fullPageSearchBar('skill_search','Employee Search');
    if (empty($results)) {
        $view->bold("Nothing Found matching: {$_REQUEST['skill_search']}");
    }
    else {
        $view->responsiveTableStart(['Name','Training Date']);
        foreach($results as $row) {
            echo "<tr><td><a href='{$server->config['application-root']}/hr/viewemployee?id={$row['eid']}'>{$row['name']}<a></td>";
            echo "<td>".$view->formatUserTimestamp($row['train_date'],true)."</td></tr>\n";
        }
        $view->responsiveTableClose();
    }
    $view->footer();
}