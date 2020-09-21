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

$server->userMustHavePermission('editBOM');

if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        default: main(); break;
    }
}
else {
    main();
}

function main () {
    global $server;
    $product = new Product($server->pdo,$_REQUEST['prokey']);
    $bom = new BillOfMaterials($server->pdo);
    $accounting = $bom->bomAccounting($_REQUEST['prokey']);
    $difference = array();
    foreach($accounting as $row) {
        $diff = ($row['required']-$row['used']);
        if ($diff > 0) {
            $row['difference'] = $diff;
            array_push($difference,$row);
        }
    }
    $view = $server->getViewer('BOM Accounting:'.$product->getProductDescription());
    $view->printButton();
    $view->h1(
        '<small>BOM Accounting for:</small>'
        .$product->getProductDescription()
       .'&#160;'.$view->linkButton('/products/bom?prokey='.$_REQUEST['prokey'],"<span class='glyphicon glyphicon-arrow-left'></span>Back",'info',true)
    );
    if (empty($difference)) {
        $view->bold("No accounting errors found");
    }
    else {
        $view->responsiveTableStart(['Number','Description','Required','Used','Difference']);
        foreach($difference as $row) {
            echo "<tr><td><a href='{$view->PageData['approot']}/material/viewmaterial?id={$row['partid']}'>{$row['number']}</a></td>";
            echo "<td>{$row['description']}</td><td>{$row['required']}</td><td>{$row['used']}</td><td>{$row['difference']}</td></tr>\n";
        }
        $view->responsiveTableClose();
    }
    $view->addScrollTopBtn();
    $view->footer();
}