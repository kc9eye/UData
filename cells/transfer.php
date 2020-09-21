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
require_once(dirname(__DIR__).'/lib/libtransfer.php');
$server->userMustHavePermission('editWorkCell');

//control section
if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'begin': 
            $_SESSION['cell_transfer']['request'] = $_REQUEST;
            $server->processingDialog(
                'create_new_cell',
                [$_REQUEST],
                $server->config['application-root'].'/cells/transfer?action=tooling',
                'Creating New Work Cell'
            );
        break;
        case 'tooling':
            if ($_SESSION['cell_transfer']['create'] !== true) {
                unset($_SESSION['cell_transfer']);
                $server->newEndUserDialog(
                    "Tooling transfer requires cell creatation, can't continue.",
                    DIALOG_FAILURE,
                    $server->config['application-root'].'/cells/main'
                );
            }
            else
                $server->processingDialog(
                    'transfer_tooling',
                    [$_SESSION['cell_transfer']],
                    $server->config['application-root'].'/cells/transfer?action=safety',
                    "Transferring Tooling to New Cell"
                );
        break;
        case 'safety':
            if ($_SESSION['cell_transfer']['tooling'] !== true) {
                $feedback = $_SESSION['cell_transfer']['tooling'];
                $_SESSION['cell_transfer']['tooling'] = true;
                cancelOptionDialog($feedback,'safety');
            }
            else
                $server->processingDialog(
                    'transfer_safety',
                    [$_SESSION['cell_transfer']],
                    $server->config['application-root'].'/cells/transfer?action=material',
                    "Tranferring Safety Data to New Cell"
                );
        break;
        case 'material':
            if ($_SESSION['cell_transfer']['safety'] !== true) {
                $feedback = $_SESSION['cell_transfer']['safety'];
                $_SESSION['cell_transfer']['safety'] = true;
                cancelOptionDialog($feedback,'material');
            }
            else
                $server->processingDialog(
                    'transfer_material',
                    [$_SESSION['cell_transfer']],
                    $server->config['application-root'].'/cells/transfer?action=complete',
                    "Tranferring Material to New Cell"
                );
        break;
        case 'complete':
            if ($_SESSION['cell_transfer']['material'] !== true) {
                $feedback = $_SESSION['cell_transfer']['material'];
                $_SESSION['cell_transfer']['material'] = true;
                cancelOptionDialog("Material transfer failed",'complete');
            }
            else
                completionDialog();
        break;
        case 'abort':
            $server->processingDialog(
                'abort_transfer',
                [$_SESSION['cell_transfer']],
                $server->config['application-root'].'/cells/main',
                "Aborting Transfer"
            );
        break;
        case 'print':
            printDiscrepanciesDialog();
        break;
        default: selectionDisplay(); break;
    }
}
else {
    selectionDisplay();
}

function selectionDisplay () {
    global $server;
    $products = new Products($server->pdo);
    $cell = new WorkCell($server->pdo,$_REQUEST['id']);
    $view = $server->getViewer("Work Cells: Transfer");
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    $active_select = array();
    foreach($products->getActiveProducts() as $row) {
        if ($row['product_key'] != $cell->ProductKey) 
            array_push($active_select,[$row['product_key'],$row['description']]);
    }
    $form->newForm("<small>Transfer:</small>{$cell->Name}");
    $form->hiddenInput('action','begin');
    $form->hiddenInput('cellid',$cell->ID);
    $form->hiddenInput('uid',$server->currentUserID);
    $form->hiddenInput('cell_name',$cell->Name);
    $form->hiddenInput('safetyreviewurl',$view->PageData['approot'].'/cells/cellsafety?action=review');
    $form->selectBox('prokey','To Product',$active_select,true,"Product to transfer data to.");
    $form->submitForm('Transfer',false,$view->PageData['approot'].'/cells/editworkcell?id='.$cell->ID);
    $form->endForm();
    $view->footer();
}

function cancelOptionDialog ($feedback, $section) {
    global $server;
    $view = $server->getViewer("Options");
    $view->wrapInCard(
        "<h2 class='card-title'>{$feedback}</h2><h3>Do you wish to continue?</h3>".
        $view->linkButton('/cells/transfer?action='.$section,'Yes','info',true).
        "&nbsp;".
        $view->linkButton('/cells/transfer?action=abort','No','info',true),
        "Problem Found",
        null,
        true
    );
    $view->footer();
}

function completionDialog () {
    global $server;

    if (!empty($_SESSION['cell_transfer']['discrepancies'])) {
        $_SESSION['print']['discrepancies'] = $_SESSION['cell_transfer']['discrepancies'];
        $_SESSION['print']['cell_name'] = $_SESSION['cell_transfer']['request']['cell_name'];
        $view = $server->getViewer("Cell Transfer Complete");
        $view->h1("Transfer Record",true);
        $view->h2("The Following Material Discrepancies Where Encountered",true);
        $view->h3("This Material was not Tranferred ".$view->linkButton('/cells/transfer?action=print','Print','default',true,'_blank'),true);
        $view->responsiveTableStart(['Material #','Quantity','Reason'],true);
        foreach($_SESSION['cell_transfer']['discrepancies'] as $part) {
            echo "<tr><td>{$part[0]}</td><td>{$part[1]}</td><td>{$part[2]}</td></tr>\n";
        }
        $view->responsiveTableClose(true);
        $view->addScrollTopBtn();
        unset($_SESSION['cell_transfer']);
        if (!empty($_SESSION['multitransfer'])) {
            array_shift($_SESSION['multitransfer']['cells']);
            $view->linkButton('/cells/main?action=multitransfer',"Continue",'success');
        }
        $view->footer();        
    }
    else {
        $cellid = $_SESSION['cell_transfer']['newcellid'];
        unset($_SESSION['cell_transfer']);
        if (!empty($_SESSION['multitransfer'])) {
            array_shift($_SESSION['multitransfer']['cells']);
            $clickback = $server->config['application-root'].'/cells/main?action=multitransfer';
        }
        else {
            $clickback = $server->config['application-root'].'/cells/main?id='.$cellid;
        }
        $server->newEndUserDialog(
            "Tranfer Complete, there were no errors during the transfer.",
            DIALOG_SUCCESS,
            $clickback
        );
        
    }    
}

function printDiscrepanciesDialog () {
    global $server;
    echo "<!DOCTYPE html>\n";
    echo "<html>\n";
    echo "<head>\n";
    echo "<title>Material Discrepancies</title>\n";
    echo "<link rel='stylesheet' type='text/css' href='{$server->config['application-root']}/wwwroot/css/print.css' />\n";
    echo "<style>\n";
    echo "  body {\n";
    echo "      border:groove black 2px;\n";
    echo "  }\n";
    echo "  div#content {\n";
    echo "      margin:10px;\n";
    echo "  }\n";
    echo "</style>\n";
    echo "</head>";
    echo "<body>\n";
    echo "<h1>Material Discrepancies</h1>\n";
    echo "<h2><small>For:</small> {$_SESSION['print']['cell_name']}</h2>\n";
    echo "<div id='content'>\n";
    echo "<table border='1'>\n";
    echo "<tr><th>Material#</th><th>Qty</th><th>Discrepancy</th></tr>\n";
    foreach($_SESSION['print']['discrepancies'] as $part) {
        echo "<tr><td>{$part[0]}</td><td>{$part[1]}</td><td>{$part[2]}</td></tr>\n";
    }
    echo "</table>\n";
    echo "</div>\n";
    echo "<script>window.print();</script>\n";
    echo "</body>\n";
    echo "</html>\n";
    unset($_SESSION['print']);
    die();
}