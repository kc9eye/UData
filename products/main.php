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

$server->userMustHavePermission('viewProduct');

if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'search':
            $formater = new SearchStringFormater();
            $search_term = $formater->formatSearchString($_REQUEST['product_search']);
            $handler = new Products($server->pdo);
            main($handler->searchProducts($search_term));
        break;
        default: main(); break;
    }
}
else main();

function main ($results = null) {
    global $server;
    $products = new Products($server->pdo);

    $view = $server->getViewer("Production: Main");
    $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->h1("Products");

    #Production Manager options
    if ($server->checkPermission('addProduct')) 
        $view->linkButton('/products/addnewproduct','Add New Product','success');

    $view->br();
    $view->insertTab();
    $view->br();

    $form->fullPageSearchBar('product_search','Product Search');
    if (!is_null($results)) {
        $view->hr();
        if (!empty($results)) {
            $view->responsiveTableStart();
            foreach($results as $row) {
                echo "<tr><td><span class='oi oi-eye' title='View' aria-hidden='true'>&#160;";
                echo "<a href='{$view->PageData['approot']}/products/viewproduct?prokey={$row['product_key']}'>";
                echo "{$row['description']}</a></td></tr>";
            }
            $view->responsiveTableClose();
        }
        else $view->bold('Nothing Found');
    }
    else {
        $view->hr();
        $view->h3('Current Active Products');
        $view->responsiveTableStart();
        foreach($products->getActiveProducts() as $row) {
            echo "<tr><td><span class='oi oi-eye' title='View' aria-hidden='true'></span>&#160;";
            echo "<a href='{$view->PageData['approot']}/products/viewproduct?prokey={$row['product_key']}'>";
            echo "{$row['description']}</a></td></tr>";
        }
        $view->responsiveTableClose();
    }

    $view->footer();
}