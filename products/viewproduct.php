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
include('./submenu.php');

//Access permissions and request handling
$server->userMustHavePermission('viewProduct');

if (empty($_REQUEST['prokey'])) {
    $server->redirect('/products/main');
}
elseif (!empty($_REQUEST['begin']) && !empty($_REQUEST['end'])) {
    $beginDate = $_REQUEST['begin'];
    $endDate = $_REQUEST['end'];
    $product = new Product($server->pdo,$_REQUEST['prokey'],$beginDate,$endDate);
}
else {
    $product = new Product($server->pdo,$_REQUEST['prokey']);
    $beginDate = date('Y/m/d',strtotime($product->pCreateDate));
    $endDate = date('Y/m/d',time());
}

//View header options for adding the Boostrap DatePicker
$pageOptions = [
    'headinserts'=> [
        '<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/js/bootstrap-datepicker.min.js"></script>',
        '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/css/bootstrap-datepicker3.css"/>'
    ]
];

//Start the main product view
$view = $server->getViewer("Products:Viewer", $pageOptions);
$form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');

//If user has correct permissions show edit button.
if ($server->checkPermission('addProduct')) {
    $view->h1(
        "<small>Viewing:</small> {$product->pDescription}&#160;".$view->editBtnSm("/products/editproduct?prokey={$_REQUEST['prokey']}",true)
    );
}
else {
    $view->h1("<small>Viewing:</small> {$product->pDescription}");
}

//Indicate the product and heading
$view->h3("<small>Created by: <strong>{$product->pCreator}</strong>, on: <strong>"
    .$view->formatUserTimestamp($product->pCreateDate,true)
    ."</strong></small>"
);

//Determine the users permissions for control bar
$control_bar = [];
if ($server->checkPermission('viewBOM'))
    $control_bar['View/Edit BOM'] = "window.open(\"{$view->PageData['approot']}/products/bom?prokey={$_REQUEST['prokey']}\",\"_self\");";
if ($server->checkPermission('editWorkCell'))
    $control_bar['Create Work Cell'] = "window.open(\"{$view->PageData['approot']}/cells/createworkcell?prokey={$_REQUEST['prokey']}\",\"_self\");";
if ($server->checkPermission('viewWorkCell'))
    $control_bar['Work Cells'] = "window.open(\"{$view->PageData['approot']}/cells/main?action=list&prokey={$_REQUEST['prokey']}\",\"_self\");";
if (!empty($control_bar)) 
    $form->inlineButtonGroup($control_bar);


//horizontal rule for visual seperation
$view->hr();

#Product stats if allowed by 'viewProductStats' permission
if ($server->checkPermission('viewProductStats')) {
    echo "<form class='form-inline'>";
    echo "<input type='hidden' name='prokey' value='{$_REQUEST['prokey']}' />";
    echo "<strong>Date Range Lookup:</strong>&#160;";
    $view->linkButton("/products/viewproduct?prokey={$_REQUEST['prokey']}&begin=".date('Y/m/d')."&end=".date('Y/m/d'),"Today Only",'info');
    echo "<div class='input-group input-daterange'>";
    echo "<input class='form-control' type='text' name='begin' value='{$beginDate}' />";
    echo "<span class='input-group-addon'>to</span>";
    echo "<input class='form-control' type='text' name='end' value='{$endDate}'/>";
    echo "<div class='input-group-btn'>";
    echo "<button class='btn btn-light form-control' role='submit'><span class='oi oi-magnifying-glass' title='magnifying-glass' aria-hidden='true'></span></button>";
    echo "</div></div></form>";
    $view->responsiveTableStart(['Total Period Count','Total 24H Count','Period FTC %']);
    $view->tableRow([[$product->pStats['total_count'],$product->pStats['today_count'],$product->pStats['total_ftc']]]);
    $view->responsiveTableClose();
}

#Currently implemented product quality control points
if ($server->checkPermission('addProduct')) {
    $products = new Products($server->pdo);
    $view->hr();
    $view->beginBtnCollapse('Show/Hide Checkpoints');
    $view->h3("Current QC Points&#160;".$view->editBtnSm("/products/qualitycheckpoints?prokey={$_REQUEST['prokey']}",true));
    $view->responsiveTableStart(['Number','Checkpoint','Associated Cell','Date Implemented']);
    $cnt = 1;
    foreach($products->getCheckPoints($_REQUEST['prokey']) as $row) {
        echo "<tr><td>{$cnt}</td><td>{$row['description']}</td><td>{$row['cell']}</td><td>".$view->formatUserTimestamp($row['_date'],true)."</td></tr>";
        $cnt++;
    }
    $view->responsiveTableClose();
    $view->endBtnCollapse();
}

#Product log section 
if ($server->checkPermission('viewProductLog')) {
    $edit = $server->checkPermission('editProductLog');
    $view->hr();

    //Preview
    $preview = "<b>Sequence</b>&#160;&#160;&#160;";
    $preview .= "<b>Serial</b>&#160;&#160;&#160;";
    $preview .= "<b>FTC</b>&#160;&#160;&#160;";
    $cnt = 0;
    foreach($product->pLog as $row) {
        if ($cnt == 5) break; $cnt++;
        $preview .= "<hr />";
        $preview .= "{$row['sequence_number']}&#160;&#160;&#160;";
        $preview .= "{$row['serial_number']}&#160;&#160;&#160;";
        $preview .= "{$row['ftc']}&#160;&#160;&#160;";
    }

    $view->beginBtnCollapse('Show/Hide Log',null, $preview);
    if ($server->checkPermission('inspectQC')) {
        $view->h3("Production Log <a href='{$view->PageData['approot']}/products/productqc?prokey={$_REQUEST['prokey']}' 
            class='btn btn-success btn-sm' role='button'><span class='oi oi-plus' title='plus' aria-hidden='true'></span>&#160;Add Entry</a>");
    }
    else {
        $view->h3('Production Log');
    } 
    $head = ['Sequence#','Serial#','Unit Description','Unit QC','Date','Comments','Inspector'];
    if ($edit) array_unshift($head,'Edit');
    $view->responsiveTableStart($head);
    foreach($product->pLog as $row) {
        echo "<tr>";
        if ($edit) echo "<td>".$view->editBtnSm("/products/editlogentry?id={$row['id']}",true)."</td>";
        echo "<td>{$row['sequence_number']}</td>";
        echo "<td>{$row['serial_number']}</td><td>{$row['misc']}</td>";
        echo "<td>{$row['ftc']}</td><td>".$view->formatUserTimestamp($row['_date'],true)."</td>";
        echo "<td>{$row['comments']}</td><td>{$row['inspector']}</td></tr>\n";
    }
    $view->responsiveTableClose();
    $view->endBtnCollapse();
}
echo "<script>$(document).ready(function(){var options = {format:'yyyy/mm/dd',autoclose: true};";
echo "$('.input-group input').each(function(){ $(this).datepicker(options);});});</script>";
$view->addScrollTopBtn();
$view->footer();