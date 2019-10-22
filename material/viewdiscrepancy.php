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
include('submenu.php');
$server->userMustHavePermission('viewDiscrepancy');

if (!empty($_REQUEST['dis_search'])) {
    $materials = new Materials($server->pdo);
    $search_string = new SearchStringFormater($_REQUEST['dis_search']);
    $discrepancies = $materials->searchDiscrepancies($search_string->formatedString);
    resultsDisplay($discrepancies);
}
elseif (!empty($_REQUEST['id'])) {
    $discrepancy = new MaterialDiscrepancy($server->pdo,$_REQUEST['id']);
    discrepancyDisplay($discrepancy);
}
else{
    searchDisplay();
}

function resultsDisplay ($discrepancies) {
    global $server,$submenu;
    $view = $server->getViewer('Material:Discrepancy');
    $view->sideDropDownMenu($submenu);
    $view->h1('Search Discrepancies',true);
    $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');
    if ($server->checkPermission('addDiscrepancy'))
        $form->inlineButtonGroup(['New Discrepancy'=>"window.open(\"{$server->config['application-root']}/material/discrepancy\",\"_self\");"]);
    $view->br();
    $view->insertTab();
    $form->fullPageSearchBar('dis_search');
    if (!empty($_REQUEST['dis_search']) && !empty($discrepancies)) {
        $view->responsiveTableStart(['ID','Qty.','Material#','Date','Product'],true);
        foreach($discrepancies as $row) {
            echo "<tr><td>";
            echo "<a href='{$server->config['application-root']}/material/viewdiscrepancy?id={$row['id']}'>{$row['id']}</a></td>";
            echo "<td>{$row['quantity']}</td><td>{$row['number']}</td><td>{$row['date']}</td><td>{$row['product']}</td></tr>\n";
        }
        $view->responsiveTableClose(true);
    }
    elseif (!empty($_REQUEST['dis_search'] && empty($discrepancies))) {
        $view->bold('Nothing Found');
    }
    $view->footer();
}

function searchDisplay () {
    global $server,$submenu;
    $view = $server->getViewer('Material:Discrepancy');
    $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->sideDropDownMenu($submenu);
    $view->h1('Search Discrepancies',true);
    if ($server->checkPermission('addDiscrepancy'))
        $form->inlineButtonGroup(['New Discrepancy'=>"window.open(\"{$server->config['application-root']}/material/discrepancy\",\"_self\");"]);
    $view->br();
    $view->insertTab();
    $form->fullPageSearchBar('dis_search');
    $view->footer();
}

function discrepancyDisplay (MaterialDiscrepancy $dis) {
    global $server,$submenu;
    $view = $server->getViewer('Material:Discrepancy');
    $view->sideDropDownMenu($submenu);
    $heading = "<small>Discrepancy Type:</small> {$dis->type} ";
    if ($server->checkPermission('editDiscrepancy'))
        $heading .= $view->linkButton('/material/amenddiscrepancy?id='.$dis->id,'Add Notes','info',true);
    $view->h1($heading,true);
    $view->responsiveTableStart(null,true);
    echo "<tr><th>ID:</th><td>{$dis->id}</td></tr>\n";
    echo "<tr><th>Type:</th><td>{$dis->type}</td></tr>\n";
    echo "<tr><th>Date:</th><td>{$dis->date}</td></tr>\n";
    echo "<tr><th>Author:</th><td>{$dis->author}</td></tr>\n";
    echo "<tr><th>Product:</th><td>{$dis->product}</td></tr>\n";
    echo "<tr><th>Quantity:</th><td>{$dis->quantity}</td></tr>\n";
    echo "<tr><th>Number:</th><td>{$dis->number}</td></tr>\n";
    echo "<tr><th>Description:</th><td>{$dis->description}</td></tr>\n";
    echo "<tr><th>Discrepancy:</th><td>{$dis->discrepancy}</td></tr>\n";
    if (!empty($dis->notes)) {
        echo "<tr><th>Addendum By: {$dis->amender}</th><td>{$dis->notes}</td></tr>\n";
    }
    echo "<tr><td colspan='2'>";
    switch(pathinfo($server->config['data-root'].'/'.$dis->file,PATHINFO_EXTENSION)) {
        case 'gif':
            $view->responsiveImage("{$server->config['application-root']}/data/files?dis=inline&file={$dis->file}");
            // echo "<img class='img-responsive' src='{$server->config['application-root']}/data/files?dis=inline&file={$dis->file}' />\n";
        break;
        case 'GIF':
            $view->responsiveImage("{$server->config['application-root']}/data/files?dis=inline&file={$dis->file}");
            // echo "<img class='img-responsive' src='{$server->config['application-root']}/data/files?dis=inline&file={$dis->file}' />\n";
        break;
        case 'jpg':
            $view->responsiveImage("{$server->config['application-root']}/data/files?dis=inline&file={$dis->file}");
            // echo "<img class='img-responsive' src='{$server->config['application-root']}/data/files?dis=inline&file={$dis->file}' />\n";
        break;
        case 'jpeg':
            $view->responsiveImage("{$server->config['application-root']}/data/files?dis=inline&file={$dis->file}");
            // echo "<img class='img-responsive' src='{$server->config['application-root']}/data/files?dis=inline&file={$dis->file}' />\n";
        break;
        case 'JPG':
            $view->responsiveImage("{$server->config['application-root']}/data/files?dis=inline&file={$dis->file}");
            // echo "<img class='img-responsive' src='{$server->config['application-root']}/data/files?dis=inline&file={$dis->file}' />\n";
        break;
        case 'JPEG':
            $view->responsiveImage("{$server->config['application-root']}/data/files?dis=inline&file={$dis->file}");
            // echo "<img class='img-responsive' src='{$server->config['application-root']}/data/files?dis=inline&file={$dis->file}' />\n";
        break;
        case 'PNG':
            $view->responsiveImage("{$server->config['application-root']}/data/files?dis=inline&file={$dis->file}");
            // echo "<img class='img-responsive' src='{$server->config['application-root']}/data/files?dis=inline&file={$dis->file}' />\n";
        break;
        case 'png':
            $view->responsiveImage("{$server->config['application-root']}/data/files?dis=inline&file={$dis->file}");
            // echo "<img class='img-responsive' src='{$server->config['application-root']}/data/files?dis=inline&file={$dis->file}' />\n";
        break;
        default:
            $view->linkButton('/data/files?dis=inline&file='.$dis->file,'Download File','info');
        break;
    }
    echo "</td></tr>\n";
    $view->responsiveTableClose(true);
    $view->footer();
}