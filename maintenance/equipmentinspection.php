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

$server->userMustHavePermission('equipmentInspector');

if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'edit': editEquipmentForm(); break;
        case 'create':
            $server->userMustHavePermission('addEquipmentInspection');
            $petty = new Maintenance($server->pdo);
            $server->processingDialog(
                [$petty,'addNewEquipment'],
                [$_REQUEST],
                $server->config['application-root'].'/maintenance/equipmentinspection?action=edit'
            );
        break;
        case 'delete':
            $server->userMustHavePermission('addEquipmentInspection');
            $petty = new Maintenance($server->pdo);
            $server->processingDialog(
                [$petty,'removeEquipment'],
                [$_REQUEST['id']],
                $server->config['application-root'].'/maintenance/equipmentinspection?action=edit'
            );
        break;
        case 'points':
            editInspectionPoints();
        break;
        case 'newpoint':
            $server->userMustHavePermission('addEquipmentInspection');
            $petty = new Maintenance($server->pdo);
            $server->processingDialog(
                [$petty,'addInspectionPoint'],
                [$_REQUEST],
                $server->config['application-root'].'/maintenance/equipmentinspection?action=points&id='.$_REQUEST['eqid']
            );
        break;
        case 'remove':
            $server->userMustHavePermission('addEquipmentInspection');
            $petty = new Maintenance($server->pdo);
            $server->processingDialog(
                [$petty,'removeInspectionPoint'],
                [$_REQUEST['id']],
                $server->config['application-root'].'/maintenance/equipmentinspection?action=points&id='.$_REQUEST['eqid']
            );
        break;
        case 'inspect':
            inspectionFormDisplay();
        break;
        case 'submit':
            $petty = new Maintenance($server->pdo);
            $server->processingDialog(
                [$petty,'addNewEquipmentInspection'],
                [$_REQUEST['uid'],$_REQUEST['eqid'],$_REQUEST['comments']],
                $server->config['application-root'].'/maintenance/equipmentinspection'
            );
        break;
        case 'past':
            viewPastInspections();
        break;
        case 'print':
            printInspections();
        break;
        default: selectEquipment(); break;
    }
}
else
    selectEquipment();

function selectEquipment () {
    global $server;
    include('submenu.php');

    $petty = new Maintenance($server->pdo);
    $view = $server->getViewer('Equipment Inspections');

    $heading = 'Select Equipment to Inspect ';
    if ($server->checkPermission('addEquipmentInspection')) 
        $heading .= $view->editBtnSm('/maintenance/equipmentinspection?action=edit',true);
    $view->sideDropDownMenu($submenu);
    $view->h1($heading);
    $view->responsiveTableStart();
    foreach($petty->getEquipement() as $row) {
        echo "<tr><th>Inspect:</th><td>";
        $view->linkButton('/maintenance/equipmentinspection?action=inspect&id='.$row['id'],$row['description'],'info');
        echo "</td></tr>\n";
    }
    $view->responsiveTableClose();

    $view->footer();
}

function editEquipmentForm () {
    global $server;
    include('submenu.php');

    $server->userMustHavePermission('addEquipmentInspection');
    $petty = new Maintenance($server->pdo);

    $view = $server->getViewer('Create Equipment');
    $view->sideDropDownMenu($submenu);
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');

    $view->h1('Create New Equipment',true);
    $form->newForm();
    $form->hiddenInput('action','create');
    $form->hiddenInput('uid',$server->currentUserID);
    $form->inputCapture('description','Equipment',null,true);
    $form->selectBox(
        'interval',
        'TimeFrame',
        [
            ['1 days','1 Day'],
            ['7 days','7 Days'],
            ['30 days','30 Days'],
            ['60 days','60 Days'],
            ['90 days','90 Days'],
            ['180 days','180 Days'],
            ['360 days','360 Days']
        ],
        false,
        'Interval between inspections for notifications'
    );
    $form->submitForm('Create',false,$view->PageData['approot'].'/maintenance/equipmentinspection');
    $form->endForm();
    $view->hr();
    $view->responsiveTableStart();
    foreach($petty->getEquipement() as $row) {
        echo "<tr><td>{$row['description']}&#160;";
        $view->editBtnSm("/maintenance/equipmentinspection?action=points&id={$row['id']}");
        echo "</td><td>{$row['timeframe']}</td><td>";
        $view->trashBtnSm("/maintenance/equipmentinspection?action=delete&id={$row['id']}");
        echo "</td></tr>\n";
    }
    $view->responsiveTableClose();
    $view->footer();
}

function editInspectionPoints () {
    global $server;
    include('submenu.php');

    $server->userMustHavePermission('addEquipmentInspection');

    $inspect = new Inspection($server->pdo,$_REQUEST['id']);

    $view = $server->getViewer('Inspection Points');
    $view->sideDropDownMenu($submenu);
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->h1('<small>Inspection Points for:</small> '.$inspect->EquipmentName, true);
    $form->newForm();
    $form->hiddenInput('action','newpoint');
    $form->hiddenInput('uid',$server->currentUserID);
    $form->hiddenInput('eqid',$_REQUEST['id']);
    $form->inputCapture('inspection','Inspection',null,true,"New inspection point for inspector");
    $form->submitForm('Add',false,$view->PageData['approot'].'/maintenance/equipmentinspection?action=edit');
    $form->endForm();
    $view->hr();
    if (!empty($inspect->InspectionPoints)) {
    $view->responsiveTableStart();
        foreach($inspect->InspectionPoints as $point) {
            echo "<tr><th>Inspection Point:</th><td>{$point['inspection']}</td><td>";
            $view->trashBtnSm('/maintenance/equipmentinspection?action=remove&id='.$point['id'].'&eqid='.$_REQUEST['id']);
            echo "</td></tr>\n";
        }
        $view->responsiveTableClose();
    }
    $view->Footer();
}

function inspectionFormDisplay () {
    global $server;
    include('submenu.php');

    $inspect = new Inspection($server->pdo,$_REQUEST['id']);
    $view = $server->getViewer('Inpsect: '.$inspect->EquipmentName);
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');

    $view->sideDropDownMenu($submenu);
    $view->linkButton('/maintenance/equipmentinspection?action=past&id='.$_REQUEST['id'],'View Past Inspections','info');
    $view->h1("<small>Inspection for:</small> {$inspect->EquipmentName}",true);    
    $form->newForm();
    $form->hiddenInput('action','submit');
    $form->hiddenInput('eqid',$_REQUEST['id']);
    $form->hiddenInput('uid',$server->currentUserID);
    $cnt = 0;
    if (empty($inspect->InspectionPoints)) {
        $view->h2('No inspection points found for this equipment.');
        $view->linkButton('/maintenance/equipmentinspection','Cancel','default');
    }
    else {
        foreach($inspect->InspectionPoints as $row) {
            $form->checkBox(
                $cnt++,
                ['Inspect',$row['inspection']],
                '1',
                true
            );
        }
        $form->textArea('comments',null,'',false,'Enter any comments about the inspection.',true);
        $form->submitForm('Affirm Inspection',false,$view->PageData['approot'].'/maintenance/equipmentinspection');
    }
    $form->endForm();
    $view->footer();
}

function viewPastInspections () {
    global $server;
    include('submenu.php');

    $inspect = new Inspection($server->pdo,$_REQUEST['id']);
    $view = $server->getViewer('Inspection: View');
    $view->sideDropDownMenu($submenu);
    $view->linkButton('/maintenance/equipmentinspection?action=print&id='.$_REQUEST['id'],'Print','default',false,'_blank');
    $view->h1('<small>Inspections for:</small> '.$inspect->EquipmentName);
    
    if (!empty($inspect->PastInspections)) {
        $view->responsiveTableStart(['Date','Inspector','Comments']);
        foreach($inspect->PastInspections as $row) {
            echo "<tr><td>".$view->formatUserTimestamp($row['_date'],true)."</td><td>{$row['inspector']}</td><td>{$row['comments']}</tr>\n";
        }
        $view->responsiveTableClose();
    }
    else {
        $view->h2('No past inspections found for this equipment');
    }
    $view->footer();
}

function printInspections () {
    global $server;

    $inspect = new Inspection($server->pdo,$_REQUEST['id']);

    echo "<html>\n";
    echo "<title>Inpsections for: {$inspect->EquipmentName}</title>\n";
    echo "<link rel='stylesheet' type='text/css' href='{$server->config['application-root']}/wwwroot/css/print.css' />\n";
    echo "<style>\n";
    echo "table {
            width:100%;
        }
        table, td, th {
            border-collapse: collapse;
            border: 1px solid black;
        }
        th {
            text-align: center;
            height: 30px;
            width: 25%;
        }
        td {
            height:30px;
            vertical-align: center;
            text-align: left
        }\n";
    echo "</style>\n";
    echo "</head>\n";
    echo "<body>\n";
    echo "<h1>{$inspect->EquipmentName}</h1>\n";
    echo "<h2>Inspection Points</h2>\n";
    echo "<ol>\n";
    foreach($inspect->InspectionPoints as $row) {
        echo "<li>{$row['inspection']}</li>\n";
    }
    echo "</ol>\n";
    echo "<h2>Inpsection Log</h2>\n";
    echo "<table>\n";
    echo "<tr><th>Date</th><th>Inspector</th><th>Comments</th></tr>\n";
    foreach($inspect->PastInspections as $row) {
        echo "<tr><td>{$row['_date']}</td><td>{$row['inspector']}</td><td>{$row['comments']}</td></tr>\n";
    }
    echo "</table>\n";
    echo "<script>window.print();</script>\n";
    echo "</body>\n";
    echo "</html>\n";
}