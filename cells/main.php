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

//Check permission for access
$server->userMustHavePermission('viewWorkCell');

//Handle request to see what to display
if (!empty($_REQUEST['action'])) {
    switch ($_REQUEST['action']) {
        case 'list':
        $wc = new WorkCells($server->pdo);
        $cells = $wc->getCellsFromKey($_REQUEST['prokey']);
            if (empty($cells)) {
                if ($server->checkPermission('editWorkCell')) {
                    $server->newendUserDialog(
                        "No cells found for this product.",
                        DIALOG_FAILURE,
                        $server->config['application-root'].'/cells/createworkcell?prokey='.$_REQUEST['prokey']
                    );
                }
                else {
                    $server->newEndUserDialog(
                        "No cells found for this product, see your Production Manager",
                        DIALOG_FAILURE,
                        $server->config['application-root'].'/viewproduct?prokey='.$_REQUEST['prokey']
                    );
                }
            }
            else {
                listWorkCells($cells);
            }
        break;
        case 'view':
            if (empty($_REQUEST['id'])) {
                $server->newEndUserDialog(
                    "You must select a cell to view.",
                    DIALOG_FAILURE,
                    $server->config['application-root'].'/cells/main'
                );
            }
            displayCell();
        break;
        case 'search':
            $wc = new WorkCells($server->pdo);
            if (!empty(($pntr = $wc->searchCells($_REQUEST['cell_search'])))) {
                displaySearchBar($pntr);
            }
            else {
                displaySearchBar("{$_REQUEST['cell_search']} not found.");
            }
        break;
        default: displaySearchBar();
    }
}
else {
    displaySearchBar();
}


//The individual displays

/**
 * Displays all the individual cell data
 * @param WorkCell The cell object that contains the cell data
 * @return Void
 */
function displayCell () {
    global $server;
    $cell = new WorkCell($server->pdo, $_REQUEST['id']);
    $view = $server->getViewer("Products: Work Cell");
    $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');

    //The cell data (name, edit button, product association)
    $form->inlineButtonGroup([
        "Product View"=>"window.open(\"{$view->PageData['approot']}/products/viewproduct?prokey={$cell->ProductKey}\",\"_self\");",
        "Product Cells View"=>"window.open(\"?action=list&prokey={$cell->ProductKey}\",\"_self\");"
    ]);
    if ($server->checkPermission('editWorkCell')) {
        $view->h1("<small>Work Cell:</small> {$cell->Name} ".$view->editBtnSm("/cells/editworkcell?id={$_REQUEST['id']}",true));
    }
    else {
        $view->h1("<small>Work Cell:</small> {$cell->Name}");
    }
    $view->h2("<small>Associated Product:</small> {$cell->Product}");

    //The quality data for the cell (FTC,QCP with correct permissions and edit button)
    if ($server->checkPermission('addProduct')) {
        $view->beginBtnCollapse('Show/Hide QCP');
        $view->h3(
            "Current Quality Statistic: {$cell->FTC}% ".$view->editBtnSm("/cells/cellcheckpoints?id={$cell->ID}",true)
        );
        if (!empty($cell->QCP)) {
            echo "<div class='table-responsive'><table class='table'>\n";
            echo "<tr><th>Checkpoint</th></tr>\n";
            foreach($cell->QCP as $row) {
                echo "<tr><td>{$row['description']}</td></tr>\n";
            }
            echo "</table></div>";
        }
        else {
            $view->bold("No checkpoints found");
        }
        $view->endBtnCollapse();    
    }
    else {
        $view->h3("Current Quality Statistic: {$cell->FTC}%");
    }

    //Materials data associated with the cell
    $view->hr();
    $view->beginBtnCollapse('Show/Hide Material');
    $heading = "Material";
    if ($server->checkPermission('editWorkCell')) {
        $heading .= " ".$view->editBtnSm("/cells/cellmaterial?cellid={$_REQUEST['id']}",true);
    }
    $heading .= ' '.$view->linkButton("/cells/materialcontrols?cellid={$cell->ID}","Material Controls","info",true,'_self');
    $view->h3($heading);
    if (!empty($cell->Material)) {
        echo "<div class='table-responsive'><table class='table'>\n";
        echo "<tr><th>Quantity</th><th>Number</th><th>Description</th><th>Print</th><th>Discrepancy</th></tr>";
        foreach($cell->Material as $row) {
            echo "<tr><td>{$row['qty']}</td><td>{$row['number']}</td><td>{$row['description']}</td>";
            echo "<td>".$view->linkButton(
                "https://techcenter.uhaul.net/ApprovedDrawing/Viewer?num={$row['number']}",
                "View Print",
                "info",
                true,
                "_blank",
                true
            )."</td>";
            echo "<td>".$view->linkButton('/material/discrepancy?number='.$row['number'].'&prokey='.$cell->ProductKey,'Discrepancy','warning',true)."</td></tr>\n";
        }
        echo "</table></div>\n";
    }
    else {
        $view->bold('No Material Found for this Cell');
    }
    $view->endBtnCollapse();

    //Tooling data associated with the cell
    $view->hr();
    $view->beginBtnCollapse('Show/Hide Tooling');
    $tool_heading = "Tooling ";
    if ($server->checkPermission('editWorkCell')) $tool_heading .= $view->editBtnSm("/cells/celltools?id={$_REQUEST['id']}",true);
    if ($server->checkPermission('maintenanceAccess')) 
        $tool_heading .= "&#160;".$view->linkButton("/maintenance/toolpicklist?cellid={$_REQUEST['id']}","Export Pick List",'default',true,'_blank'); 
    $view->h3($tool_heading);
    if (!empty($cell->Tools)) {
        $view->responsiveTableStart(['Qty.','Description','Category','Torque Value','Torque Units','Torque Label']);
        foreach($cell->Tools as $row) {
            echo "<tr><td>{$row['qty']}</td><td>{$row['description']}</td><td>{$row['category']}</td>";
            echo "<td>{$row['torque_val']}</td><td>{$row['torque_units']}</td><td>{$row['torque_label']}</td></tr>";
        }
        $view->responsiveTableClose();
     }
    else {
        $view->bold("No Tooling Found");
    }
    $view->endBtnCollapse();

    //Safety data associated with the cell
    $view->hr();
    $view->beginBtnCollapse('Show/Hide Assessment');
    $heading = "Safety Assessment".$view->insertTab(1,true).$view->linkButton("/cells/printsafety?cellid={$cell->ID}","Print",'default',true, '_blank');
    if ($server->checkPermission('editWorkCell')) 
        $heading .= $view->insertTab(1,true).$view->editBtnSm("/cells/cellsafety?cellid={$_REQUEST['id']}",true);
    if (!is_null($cell->SafetyReview) && $server->checkPermission('approveCellSafety'))
        $heading .= $view->insertTab(1,true).$view->linkButton("/cells/cellsafety?action=review&name={$_REQUEST['id']}","Awaiting approval",'info',true);
    $view->h3($heading);
    if (!empty($cell->Safety)) {
        echo "<div class='panel panel-default'>\n";
        echo "<div class='panel-heading'>Authored by: {$cell->Safety['author']} on ".$view->formatUserTimestamp($cell->Safety['_date'],true);
        echo "<br />Approved By:{$cell->Safety['approver']} on ".$view->formatUserTimestamp($cell->Safety['a_date'],true)."</div>\n";
        echo "<div class='panel-content'>{$cell->Safety['body']}</div>\n";
        echo "</div>\n";
    }
    else {
        $view->bold("No Assessment Found");
    }
    $view->endBtnCollapse();
    $view->addScrollTopBtn();
    $view->footer();
}

/**
 * Displays the individual for cells given the cell data
 * @param Array $cells The array of cells to display
 * @return Void
 */
function listWorkCells ($cells) {
    global $server;
    $edit = $server->checkPermission('editWorkCell');
    //For #46 ----^^^^^^
    $product = new Products($server->pdo);
    $product_description = $product->getProductDescriptionFromKey($_REQUEST['prokey']);
    $view = $server->getViewer("Products: Work Cell");
    $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');
    echo "<div class='row'><div class='col-md-3'></div>\n";
    echo "<div class='col-md-6 col-xs-12'>\n";
    $view->h2("<small>Cells For:</small> {$product_description}");
    $form->inlineButtonGroup([
        'Product View'=>"window.open(\"{$view->PageData['approot']}/products/viewproduct?prokey={$_REQUEST['prokey']}\",\"_self\");",
        'Create New Cell'=>"window.open(\"{$view->PageData['approot']}/cells/createworkcell?prokey={$_REQUEST['prokey']}\",\"_self\");"
    ]);
    echo "<div class='table-repsonive'><table class='table'>\n";
    echo "<tr><th>Cell Name</th>";
    if ($edit) echo "<th>QC</th>";
    //For #46 ---^^^^^^^
    echo "<th>Last Updated</th><th>Last Author</th></tr>\n";
    foreach($cells as $cell) {
        echo "<tr><td><a href='?action=view&id={$cell['id']}'>{$cell['cell_name']}</a></td>";
        if ($edit) echo "<td>".round($cell['qc'],2)."%</td>";
        //For #46 ---^^^^^^^^
        echo "<td>".$view->formatUserTimestamp($cell['_date'],true)."</td><td>{$cell['author']}</td></tr>\n";
    }
    echo "</table></div><div class='col-md-3'></div>\n";
    echo "</div>\n";
    $view->footer();
}

/**
 * Displays the full page search bar to search for a cell by name
 * @return Void
 */
function displaySearchBar ($content = null) {
    global $server;
    $view = $server->getViewer("Products: Work Cell");
    $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->h2("Search Work Cells");

    if ($server->checkPermission('editWorkCell')) 
        echo "<a href='{$view->PageData['approot']}/cells/createworkcell' class='btn btn-info' role='button'>Create Work Cell</a>\n<br />\n";
    
    $view->hr();
    $form->fullPageSearchBar('cell_search','Cell Search',null,true);
    if (!is_null($content)) {
        if (is_array($content)) {
            $products = new Products($server->pdo);
            echo "<div class='table-responsive'><table class='table'>\n";
            echo "<tr><th>Cell Name</th><th>Product</th></tr>\n";
            foreach($content as $row) {
                echo "<tr><td><a href='?action=view&id={$row['id']}'>{$row['cell_name']}</a></td><td>".$products->getProductDescriptionFromKey($row['prokey'])."</td></tr>\n";
            }
            echo "</table></div>\n";
        }
        else {
            $view->bold($content);
        }
    }
    $view->footer();
}