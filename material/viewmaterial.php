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
$server->userMustHavePermission('viewMaterial');

$part = new Material($server->pdo,null,$_REQUEST['id']);
$products = new Products($server->pdo);

$view = $server->getViewer("Material: View Material");
$view->sideDropDownMenu($submenu);

$view->h1("<small>Material:</small> {$part->material['number']} ".
    $view->linkButton(
        "https://techcenter.uhaul.net/ApprovedDrawing/Viewer?num={$part->material['number']}",
        "View Print",
        "warning",
        true,
        "_blank",
        true
    )
);
$view->h2("<small>Description:</small> {$part->material['description']}");
$view->hr();

//Receiveing info data
$view->h3("Receiving Info");
if (!is_null($part->receiving)) {
    $view->responsiveTableStart(null);
    //do something
    $view->responsiveTableClose();
}
else 
    $view->bold("No receiving data found");

//Inventory data info
$view->hr();
$view->h3("Inventory");
if (!is_null($part->inventory)) {
    $view->responsiveTableStart(null);
    //do something
    $view->responsiveTableClose();
}
else
    $view->bold("No active product inventory found.");

//Descrepancy data info
$view->hr();
$view->h3("Discrepancies");
if (!is_null($part->discrepancies)) {
    $view->responsiveTableStart(["ID","Type","Product","Qty.","Date"]);
    foreach($part->discrepancies as $row) {
        if ($products->isActiveProduct($row['prokey'])) {
            echo "<tr><td><a href='{$server->config['application-root']}/material/viewdiscrepancy?action=view&id={$row['id']}'>{$row['id']}</a></td>";
            echo "<td>{$row['type']}</td><td>".$products->getProductDescriptionFromKey($row['prokey'])."</td>";
            echo "<td>{$row['qty']}</td><td>{$row['_date']}</td></tr>\n";
        }
    }
    $view->responsiveTableClose();
}
else
    $view->bold("No active product discrepancies found");

//Workcell data info
$view->hr();
$view->h3("Workcells");
$view->beginBtnCollapse();
if (!is_null($part->workcells)) {
    $view->responsiveTableStart(['Product','Cell','Qty']);
    foreach($part->workcells as $row) {
        echo "<tr><td>{$row['product']}</td>";
        echo "<td><a href='{$view->PageData['approot']}/cells/main?action=view&id={$row['id']}'>{$row['work_cell']}</a></td>";
        echo "<td>{$row['qty']}</td></tr>\n";
    }
    $view->responsiveTableClose();
}
$view->endBtnCollapse();
$view->footer();