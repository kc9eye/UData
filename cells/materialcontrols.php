<?php
/*
 * Copyright (C) 2020  Paul W. Lane <kc9eye@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */
require_once(dirname(__DIR__).'/lib/init.php');
$server->userMustHavePermission('viewWorkCell');
if (!empty($_REQUEST['action'])) {
    switch ($_REQUEST['action']) {
        case 'add': addCounts(); break;
        case 'remove': removeCounts(); break;
        case 'printlist': printList(); break;
        case 'printcodes': printCodes(); break;
        case 'countsheet': countSheet(); break;
        case 'printTotals': inventoryTotals(); break;
        default: main();
    }
}
else main();

function main () {
    global $server;
    $cell = new WorkCell($server->pdo, $_REQUEST['cellid']);
    $view = $server->getViewer("Cells: Material Controls");
    $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->h1("<small>Work Cell:</small> {$cell->Name}");
    $view->h2("<small>Associated Product:</small> {$cell->Product}");
    $view->linkButton("/cells/main?action=view&id={$_REQUEST['cellid']}","Back",'primary');
    $view->hr();
    $view->linkButton("/cells/materialcontrols?cellid={$cell->ID}&action=printlist","Print List","info",false,'_blank');
    $view->insertTab();
    $view->linkButton("/cells/materialcontrols?action=printcodes&cellid={$cell->ID}","Print Barcodes","info",false,'_blank');
    $view->insertTab();
    $view->linkButton("/cells/materialcontrols?cellid={$cell->ID}&action=countsheet","Print Count Sheet","info",false,"_blank");

    if ($server->checkPermission('editWorkCell')) {
        if (empty($_SESSION['inventory'][$cell->ID])) {
            $_SESSION['inventory'][$cell->ID] = [
                'parts'=>[]
            ];
        }
        $view->h2("Inventory Counts");
        $view->responsiveTableStart(["Number","Description","Cell Qty.","WIP / Count"]);
        foreach($cell->Material as $row) {
            echo "<tr><td>{$row['number']}</td><td>{$row['description']}</td><td>{$row['qty']}</td><td>";
            if (empty($_SESSION['inventory'][$cell->ID]['parts'][$row['number']])) {
                $form->newInlineForm();
                $form->hiddenInput("action","add");
                $form->hiddenInput('number',$row['number']);
                $form->hiddenInput('qty',$row['qty']);
                $form->hiddenInput("cellid",$cell->ID);
                $form->inlineInputCapture("wip","x WIP",null,['number'=>'true','min'=>'0']);
                $form->inlineInputCapture("cnt","+ CNT",null,['number'=>'true','min'=>'0']);
                $view->insertTab();
                $form->inlineSubmit("Add",true);
                $form->endInlineForm();
            }
            else {
                $view->bold("Total Submitted: {$_SESSION['inventory'][$cell->ID]['parts'][$row['number']]}");
                $view->insertTab();
                $view->linkButton("/cells/materialcontrols?action=remove&number={$row['number']}&cellid={$cell->ID}","Remove Count","danger");
            }
            echo "</td></tr>";
        }
        $view->responsiveTableClose();
        $view->linkButton("/cells/materialcontrols?action=printTotals&cellid={$cell->ID}","Print Inventory Barcodes",'success',false,'_blank');
    }
    else {
        $view->responsiveTableStart(["Quantity","Number","Description"]);
        foreach($cell->Material as $row) {
            echo "<tr><td>{$row['qty']}</td><td>{$row['number']}</td><td>{$row['description']}</td></tr>";
        }
        $view->responsiveTableClose();
    }
    $view->footer();
}

function addCounts () {
    global $server;
    $server->userMustHavePermission('editWorkCell');
    if (!empty($_SESSION['inventory'])) {
        $_SESSION['inventory'][$_REQUEST['cellid']]['parts'][$_REQUEST['number']] = (($_REQUEST['qty']*$_REQUEST['wip'])+$_REQUEST['cnt']);
        main();
    }
}

function removeCounts () {
    global $server;
    $server->userMustHavePermission('editWorkCell');
    if (!empty($_SESSION['inventory'])) {
        unset($_SESSION['inventory'][$_REQUEST['cellid']]['parts'][$_REQUEST['number']]);
        main();
    }    
}

function printList () {
    global $server;
    $server->userMustHavePermission('viewWorkCell');
    $cell = new WorkCell($server->pdo,$_REQUEST['cellid']);
    echo "<!DOCTYPE html>\n";
    echo "<html>\n";
    echo "<head>\n";
    echo "<title>{$cell->Name}</title>\n";
    echo "<link rel='stylesheet' type='text/css' href='{$server->config['application-root']}/wwwroot/css/print.css' />\n";
    echo "<style>\n";
    echo "table {
            width:100%;
        }
        table, td, th {
            border-collapse: collapse;
            border: 1px solid black;
        }
        th,td {
            text-align:center;
        }
        td {
            height:30px;
            vertical-align: center;
        }
        #notes {
            width:35%;
        }\n";
    echo "</style>\n";
    echo "</head>\n";
    echo "<body>\n";
    echo "<h1>{$cell->Product} / {$cell->Name}</h1>\n";
    echo "<p>Generated by: {$server->security->user['firstname']} {$server->security->user['lastname']} at: ".date('c')."</p>\n";
    echo "<hr />\n";
    echo "<table style='line-height:1.5'>\n";
    echo "<tr><th>Qty.</th><th>Number</th><th>Description</th><th id='notes'>Notes</th></tr>\n";
    foreach($cell->Material as $row) {
        echo "<tr><td style='text-align:center;'>{$row['qty']}</td><td>{$row['number']}</td><td style='text-align:left;'>{$row['description']}</td><td id='notes'>&nbsp;</td></tr>\n";
    }
    echo "</table>\n";
    echo "<script>window.print()</script>\n";
    echo "</body>\n";
    echo "</html>\n";
}

function printCodes () {
    global $server;
    $server->userMustHavePermission('viewWorkCell');
    $cell = new WorkCell($server->pdo,$_REQUEST['cellid']);
    echo "<!DOCTYPE html>\n";
    echo "<html>\n";
    echo "<head>\n";
    echo "<title>{$cell->Name}</title>\n";
    echo "<link rel='stylesheet' type='text/css' href='{$server->config['application-root']}/wwwroot/css/print.css' />\n";
    echo "<style>\n";
    echo "media print {
        tr {
            page-break-inside: avoid;
        }
    }";
    echo "table {
            width:100%;
        }
        table, td, th {
            border-collapse: collapse;
            border: 1px solid black;
        }
        th,td {
            text-align:center;
        }
        td {
            height:30px;
            vertical-align: center;
        }
        #notes {
            width:35%;
        }\n";
    echo "</style>\n";
    echo "</head>\n";
    echo "<body>\n";
    echo "<h1>{$cell->Product} / {$cell->Name}</h1>\n";
    echo "<p>Generated by: {$server->security->user['firstname']} {$server->security->user['lastname']} at: ".date('c')."</p>\n";
    echo "<hr />\n";
    echo "<table>";
    echo "<tr><th>Number</th><th>Quantity</th><th>Description</th></tr>";
    foreach($cell->Material as $row) {
        echo "<tr><td>";
        echo "<img src='{$server->config['application-root']}/data/barcode?barcode={$row['number']}&&width=200&height=50&format=png&text=0' alt='[BARCODE]' />";
        echo "<br />";
        echo "<span class='text-center'>{$row['number']}</span>";
        echo "</td>";
        echo "<td>{$row['qty']}</td>";
        echo "<td>{$row['description']}</td></tr>";
    }
    echo "</table>";
    echo "</body>\n";
    echo "</html>\n";
}

function countSheet () {
    global $server;
    $server->userMustHavePermission('viewWorkCell');
    $cell = new WorkCell($server->pdo,$_REQUEST['cellid']);
    echo "<!DOCTYPE html>\n";
    echo "<html>\n";
    echo "<head>\n";
    echo "<title>{$cell->Name}</title>\n";
    echo "<link rel='stylesheet' type='text/css' href='{$server->config['application-root']}/wwwroot/css/print.css' />\n";
    echo "<style>\n";
    echo "media print {
        tr {
            page-break-inside: avoid;
        }
    }";
    echo "table {
            width:100%;
        }
        table, td, th {
            border-collapse: collapse;
            border: 1px solid black;
        }
        th,td {
            text-align:center;
        }
        td {
            height:30px;
            vertical-align: center;
        }
        #notes {
            width:35%;
        }\n";
    echo "</style>\n";
    echo "</head>\n";
    echo "<body>\n";
    echo "<h1>{$cell->Product} / {$cell->Name}</h1>\n";
    echo "<h2>Floor/Cart Counts Only</h2>";
    echo "<p>Generated by: {$server->security->user['firstname']} {$server->security->user['lastname']} at: ".date('c')."</p>\n";
    echo "<hr />\n";
    echo "<table>";
    echo "<tr><th>Total Counted</th><th>Number</th><th>Description</th></tr>";
    foreach($cell->Material as $row) {
        echo "<tr><td>&#160;</td><td>{$row['number']}</td><td>{$row['description']}</td></tr>";
    }
    echo "</table>";
    echo "<script>window.print()</script>\n";
    echo "</body>\n";
    echo "</html>\n";
}

function inventoryTotals () {
    global $server;
    $server->userMustHavePermission('editWorkCell');
    $cell = new WorkCell($server->pdo,$_REQUEST['cellid']);
    echo "<!DOCTYPE html>\n";
    echo "<html>\n";
    echo "<head>\n";
    echo "<title>{$cell->Name}</title>\n";
    echo "<link rel='stylesheet' type='text/css' href='{$server->config['application-root']}/wwwroot/css/print.css' />\n";
    echo "<style>\n";
    echo "media print {
        tr {
            page-break-inside: avoid;
        }
    }";
    echo "table {
            width:100%;
        }
        table, td, th {
            border-collapse: collapse;
            border: 1px solid black;
        }
        th,td {
            text-align:center;
        }
        td {
            height:30px;
            vertical-align: center;
        }
        #notes {
            width:35%;
        }\n";
    echo "</style>\n";
    echo "</head>\n";
    echo "<body>\n";
    echo "<h1>{$cell->Product} / {$cell->Name}</h1>\n";
    echo "<h2>Inventory Total Counts (including WIP)</h2>";
    echo "<p>Generated by: {$server->security->user['firstname']} {$server->security->user['lastname']} at: ".date('c')."</p>\n";
    echo "<hr />\n";
    echo "<table>";
    echo "<tr><th>Number</th><th>Quantity</th><th>Description</th></tr>";
    foreach($_SESSION['inventory'][$cell->ID]['parts'] as $number => $total) {
        echo "<tr><td>";
        echo "<img src='{$server->config['application-root']}/data/barcode?barcode={$number}&width=200&height=50&format=png&text=0' alt='[BARCODE]' />";
        echo "<br />";
        echo "<span class='text-center'>{$number}</span>";
        echo "</td>";
        echo "<td>{$total}</td>";
        foreach($cell->Material as $row) {
            if ($row['number'] == $number) {
                echo "<td>{$row['description']}</td>";
            }
        }
        echo "</tr>";
    }
    echo "</table>";
    echo "</body>\n";
    echo "</html>\n";
    unset($_SESSION['inventory'][$cell->ID]);
}