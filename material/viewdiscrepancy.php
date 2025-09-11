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

if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'search':
            $materials = new Materials($server->pdo);
            $search_string = new SearchStringFormater($_REQUEST['dis_search']);
            $discrepancies = $materials->searchDiscrepancies($search_string->formatedString);
            resultsDisplay($discrepancies);
        break;
        case 'date_range_lookup':
            $materials = new Materials($server->pdo);
            $_REQUEST['dis_search'] = null;
            resultsDisplay($materials->getDiscrepanciesByDateRange($_REQUEST['begin_date'],$_REQUEST['end_date'],$_REQUEST['type']));
        break;
        case 'view':
            $discrepancy = new MaterialDiscrepancy($server->pdo,$_REQUEST['id']);
            discrepancyDisplay($discrepancy);
        break;
        default:
            searchDisplay();
        break;
    }
}
else
    searchDisplay();

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
    if (!empty($discrepancies)) {
        $view->responsiveTableStart(['View','Qty.','Material#','Description','Type','Date','Product']);
        foreach($discrepancies as $row) {
            echo "<tr><td>";
            echo $view->linkButton("/material/viewdiscrepancy?action=view&id={$row['id']}","<span class='oi oi-eye'></span>","secondary",true)."</td>";
            echo "<td>{$row['quantity']}</td><td>{$row['number']}</td><td>{$row['description']}</td>";
            echo "<td>{$row['type']}</td><td>".$view->formatUserTimestamp($row['date'],true)."</td><td>{$row['product']}</td></tr>\n";
        }
        $view->responsiveTableClose();
    }
    elseif (!is_null($_REQUEST['dis_search'] && empty($discrepancies))) {
        $view->bold('Nothing Found');
    }
    $view->footer();
}

function searchDisplay () {
    global $server,$submenu;
    $pageOptions = [
        'headinserts'=> [
            '<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/js/bootstrap-datepicker.min.js"></script>',
            '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/css/bootstrap-datepicker3.css"/>'
        ]
    ];
    $handler = new Materials($server->pdo);
    $view = $server->getViewer('Material:Discrepancy',$pageOptions);
    $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->sideDropDownMenu($submenu);
    $view->h1('Search Discrepancies',true);
    if ($server->checkPermission('addDiscrepancy'))
        $form->inlineButtonGroup(['New Discrepancy'=>"window.open(\"{$server->config['application-root']}/material/discrepancy\",\"_self\");"]);
    $view->br();
    $view->insertTab();
    $form->fullPageSearchBar('dis_search');
    $view->hr();
    $form->newInlineForm("Date Range Lookup");
    $form->hiddenInput('action','date_range_lookup');
    //Datreange inputs inline
    //@todo need method for inline and form
    echo "<div class='input-group input-daterange'>";
    echo "<input class='form-control' type='text' name='begin_date' required/>";
    echo "<span class='input-group-addon'>to</span>";
    echo "<input class='form-control' type='text' name='end_date' required/>";
    echo "</div>";
    //End inline date ragne
    $view->insertTab();
    $form->inlineSelectBox(
        'type',
        'Query Type',
        [
            ['PDIH','PDIH Only'],
            ['PDN','PDN Only'],
            ['all','All']
        ],
        true
    );
    $view->insertTab();
    $form->inlineSubmit();
    $form->endInlineForm();
    $view->hr();
    $view->h3('Latest Added');
    $view->responsiveTableStart(['View','Qty','Material#','Type','Date','Product']);
    foreach($handler->getRecentDiscrepancies() as $row) {
        echo "<tr><td>".$view->linkButton("/material/viewdiscrepancy?action=view&id={$row['id']}","<span class='oi oi-eye'></span>",'secondary',true)."</td>";
        echo "<td>{$row['quantity']}</td><td>{$row['number']}</td><td>{$row['type']}</td><td>".$view->formatUserTimestamp($row['date'],true)."</td><td>{$row['product']}</td></tr>";
    }
    $view->responsiveTableClose();
    echo "<script>$(document).ready(function(){
        var options = {
            format:'yyyy/mm/dd',
            autoclose: true
        };
        $('.input-daterange input').each(function(){
            $(this).datepicker(options);
        });
    });</script>";
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
    $view->responsiveTableStart([],true);
    echo "<tr><th>ID:</th><td>{$dis->id}</td></tr>\n";
    echo "<tr><th>Type:</th><td>{$dis->type}</td></tr>\n";
    echo "<tr><th>Date:</th><td>".$view->formatUserTimestamp($dis->date,true)."</td></tr>\n";
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
        break;
        case 'GIF':
            $view->responsiveImage("{$server->config['application-root']}/data/files?dis=inline&file={$dis->file}");
        break;
        case 'jpg':
            $view->responsiveImage("{$server->config['application-root']}/data/files?dis=inline&file={$dis->file}");
        break;
        case 'jpeg':
            $view->responsiveImage("{$server->config['application-root']}/data/files?dis=inline&file={$dis->file}");
        break;
        case 'JPG':
            $view->responsiveImage("{$server->config['application-root']}/data/files?dis=inline&file={$dis->file}");
        break;
        case 'JPEG':
            $view->responsiveImage("{$server->config['application-root']}/data/files?dis=inline&file={$dis->file}");
        break;
        case 'PNG':
            $view->responsiveImage("{$server->config['application-root']}/data/files?dis=inline&file={$dis->file}");
        break;
        case 'png':
            $view->responsiveImage("{$server->config['application-root']}/data/files?dis=inline&file={$dis->file}");
        break;
        default:
            $view->linkButton('/data/files?dis=inline&file='.$dis->file,'Download File','info');
        break;
    }
    echo "</td></tr>\n";
    $view->responsiveTableClose(true);
    $view->footer();
}