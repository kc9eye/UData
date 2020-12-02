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

$server->userMustHavePermission('viewMaterial');

if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'search':
            $materials = new Materials($server->pdo);
            $formater = new SearchStringFormater($_REQUEST['mat_search']);
            searchDisplay($materials->searchMaterial($formater->formatedString));
        break;
        default:
            searchDisplay();
        break;
    }
}
else {
    searchDisplay();
}

function searchDisplay ($results = null) {
    global $server;
    include('submenu.php');
    $view = $server->getViewer("Material");
    $view->sideDropDownMenu($submenu);
    $view->h1('Search Materials',true);
    if ($server->checkPermission('addMaterial')) {
        $view->linkButton('/material/addnew','Add Material','info');
        $view->br();
        $view->insertTab();
    }
    $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');
    $form->fullPageSearchBar('mat_search');
    if (!is_null($results)) {
        $view->responsiveTableStart(null,true);
        foreach($results as $row) {
            echo "<tr><td><a href='{$server->config['application-root']}/material/viewmaterial?id={$row['id']}'>{$row['number']}</td>";
            echo "<td>{$row['description']}</td></tr>\n";
        }
        $view->responsiveTableClose(true);
    }
    elseif (!is_null($results) && empty($results)) {
        $view->bold("Nothing Found");
    }

    $view->footer();
}
