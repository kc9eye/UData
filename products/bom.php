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

$server->userMustHavePermission('viewBOM');

if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'import':
            $_REQUEST['file'] = new FileUpload(FileIndexer::UPLOAD_NAME);
            $server->processingDialog(
                [new BillOfMaterials($server->pdo),'importBOMCSV'],
                [$_REQUEST],
                $server->config['application-root'].'/products/bom?prokey='.$_REQUEST['prokey']
            );
        break;
        default: main(); break;
    }
}
else {
    main();
}

function main () {
    global $server;
    $product = new Product($server->pdo,$_REQUEST['prokey']);
    if (is_null($product->pBOM) or empty($product->pBOM)) displayUploadDialog($product);
    else {
        $edit = $server->checkPermission('editBOM');
        $view = $server->getViewer('Product BOM');
        $heading = '<small>Bill of Materials for:</small> '.$product->pDescription.
            " <a href='{$server->config['application-root']}/products/viewproduct?prokey={$_REQUEST['prokey']}'
            class='btn btn-info' role='button'><span class='glyphicon glyphicon-arrow-left'></span> Back</a>";
        if ($edit) $heading .= " ".$view->linkButton('/products/editbom?action=rebase&prokey='.$_REQUEST['prokey'],"<span class='oi oi-action-redo'></span> Rebase",'danger',true);
        if ($edit) $heading .= " ".$view->linkButton('/products/editbom?action=multidelete&prokey='.$_REQUEST['prokey'],"<span class='oi oi-trash'></span> Delete Mulitple",'danger',true);
        if ($edit) $heading .= " ".$view->linkButton('/products/editbom?action=addendum&prokey='.$_REQUEST['prokey'],"<span class='oi oi-plus'></span> Addendum",'warning',true);
        if ($edit) $heading .= " ".$view->linkButton('/products/unusedbommats?prokey='.$_REQUEST['prokey'],'List Unused Material','info',true);
        if ($edit) $heading .= " ".$view->linkButton('/products/bomaccounting?prokey='.$_REQUEST['prokey'],'Material Accounting','info',true);
        $view->h2($heading);
        echo "<div class='table-responsive'><table class='table'>\n";
        echo "<tr><th>".htmlentities('Part#')."</th><th>Description</th><th>Qty.</th>";
        if ($edit) echo "<th>Edit</th>";
        echo "</tr>\n";
        foreach($product->pBOM as $row) {
            echo "<tr><td>{$row['number']}</td><td>{$row['description']}</td><td>{$row['qty']}</td>";
            if ($edit) {
                echo "<td>".$view->editBtnSm("/products/editbom?id={$row['id']}",true)."</td>";
            }
            echo "</tr>\n";
        }
        echo "</table></div>\n";
        $view->addScrollTopBtn();
    }
}

function displayUploadDialog (Product $product) {
    global $server;
    $view = $server->getViewer("Upload Product BOM");
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->h2('<small>Import BOM:</small> '.$product->pDescription);
    $view->bgInfoParagraph(
        "Importing the Bill of Materials is done by uploading it as a
        comma seperated values format file. More commonly referred to as 
        a .csv file. The format of the csv file should be: 'part number(with or without hyphen)',
        'description','qty'. All fields containing spaces should be quoted. Each record should be on 
        a seperate line. The easiest way to produce this file is to use a spreadsheet, remove extraneous
        information and either export or 'save as' .csv with fields quoted."
    );
    $form->newMultipartForm('Import BOM.csv');
    $form->hiddenInput('action','import');
    $form->hiddenInput('prokey',$_REQUEST['prokey']);
    $form->hiddenInput('uid',$server->currentUserID);
    $form->fileUpload(FileIndexer::UPLOAD_NAME,true);
    $form->submitForm('Import',true);
    $form->endForm();
    $view->footer();
}
