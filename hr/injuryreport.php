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

$server->userMustHavePermission('editInjuryReport');

if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'add':
            $server->processingDialog(
                'processNewInjury',
                [],
                $server->config['application-root'].'/hr/viewemployee?id='.$_REQUEST['eid']
            );
        break;
        case 'edit':
            editReportDisplay();
        break;
        case 'amend':
            $handler = new Injuries($server->pdo);
            $server->processingDialog(
                [$handler,'amendInjuryReport'],
                [$_REQUEST],
                $server->config['application-root'].'/hr/injuryreport?action=view&id='.$_REQUEST['id']
            );
        break;
        case 'view':
            viewReportDisplay();
        break;
        case 'print':
            printReport();
        break;
        default: injuryFormDisplay(); break;
    }
}
else
    injuryFormDisplay();

function injuryFormDisplay () {
    global $server;
    $emp = new Employee($server->pdo,$_REQUEST['id']);

    $view = $server->getViewer('HR: Injury Reporting');
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->h1("<small>Injury for:</small> 
        {$emp->Profile['first']} {$emp->Profile['middle']} {$emp->Profile['last']} {$emp->Profile['other']}&#160;".
        $view->linkButton(
            '/hr/viewemployee?id='.$_REQUEST['id'],
            "<span class='glyphicon glyphicon-arrow-left'></span> Back",
            'info',true
            )
    );
    $form->newForm();
    $form->hiddenInput('action','add');
    $form->hiddenInput('eid',$_REQUEST['id']);
    $form->hiddenInput('uid',$server->currentUserID);
    $form->inputCapture('injury_date','Injury Date',date('Y-m-d'),['dateISO'=>'true']);
    $form->inputCapture('witnesses','Witnesses');
    $form->checkBox('recordable',['Recordable','Yes'],'true',false,null,'false');
    $form->checkBox('followup_medical',['Clinic','Yes'],'true',false,null,'false');
    $form->textArea('injury_description',null,'',true,'Describe the injury and how it happened.',true);
    $form->submitForm('Add',true,$server->config['application-root'].'/hr/viewemployee?id='.$_REQUEST['id']);
    $form->endForm();
    $view->footer();
}

function editReportDisplay () {
    global $server;

    $handler = new Injuries($server->pdo);
    $report = $handler->getReport($_REQUEST['id']);
    $view = $server->getViewer('HR: Amend Injury Report');
    $view->h1("<small>Amend Report for: {$report['name']}");
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    $form->newForm();
    $form->hiddenInput('action','amend');
    $form->hiddenInput('id',$report['id']);
    $form->hiddenInput('eid',$report['eid']);
    $form->hiddenInput('uid',$server->currentUserID);
    $form->inputCapture('injury_date','Injury Date',$report['injury_date'],['dateISO'=>'true']);
    $form->inputCapture('witnesses','Witnesses',$report['witnesses']);
    switch($report['recordable']) {
        case true: $form->checkBox('recordable',['Recordable','No'],'false',false,null,'true'); break;
        case false: $form->checkBox('recordable',['Recordable','Yes'],'true',false,null,'false'); break;
    }
    switch($report['followup_medical']) {
        case true: $form->checkBox('followup_medical',['Clinic','No'],'false',false,null,'true'); break;
        case false: $form->checkBox('followup_medical',['Clinic','Yes'],'true',false,null,'false'); break;
    }
    $form->textArea('injury_description',null,$report['injury_description'],true,'Amend the report about the injury and how it occurred',true);
    $form->submitForm('Amend',false,$server->config['application-root'].'/hr/viewemployee?id='.$report['eid']);
    $form->endForm();
    $view->footer();
}

function viewReportDisplay () {
    global $server;
    include('submenu.php');
    $handler = new Injuries($server->pdo);
    $report = $handler->getReport($_REQUEST['id']);
    $view = $server->getViewer('HR: Injury Report View');
    $view->sideDropDownMenu($submenu);
    $view->h1("<small>Report for:</small> {$report['name']}");
    if ($server->checkPermission('amendInjuryReport')) {
        $view->editBtnSm('/hr/injuryreport?action=edit&id='.$_REQUEST['id']);
        $view->insertTab();
    }
    $view->linkButton('/hr/injuryreport?action=print&id='.$_REQUEST['id'],'Print','default',false,'_blank');
    $view->responsiveTableStart();
    echo "<tr><th>Injury Date:</th><td>".$view->formatUserTimestamp($report['injury_date'],true)."</td></tr>\n";
    echo "<tr><th>Last Reporter:</th><td>{$report['reporter']}</td></tr>\n";
    echo "<tr><th>Witnesses:</th><td>{$report['witnesses']}</td></tr>\n";
    $recordable = ($report['recordable']) ? 'Yes' : 'No';
    $clinic = ($report['followup_medical']) ? 'Yes' : 'No';
    echo "<tr><th>Recordable:</th><td>{$recordable}</td></tr>\n";
    echo "<tr><th>Clinic:</th><td>{$clinic}</td></tr>\n";
    echo "<tr><th colspan='2'>Injury Description:</th></tr>\n";
    echo "<tr><td colspan='2'>{$report['injury_description']}</td></tr>\n";
    $view->responsiveTableClose();
    $view->footer();
}

function printReport () {
    global $server;
    $handler = new Injuries($server->pdo);
    $report = $handler->getReport($_REQUEST['id']);
    $recordable = ($report['recordable']) ? 'Yes' : 'No';
    $clinic = ($report['followup_medical']) ? 'Yes' : 'No';
    echo "<!DOCTYPE html>\n";
    echo "<html>\n";
    echo "<head>\n";
    echo "<title>Injury Report for: {$report['name']}</title>\n";
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
            text-align: right;
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
    echo "<h1>Injury Report For: {$report['name']}</h1>";
    echo "<table>\n";
    echo "<tr><th>Injury Date:</th><td>{$report['injury_date']}</td></tr>\n";
    echo "<tr><th>Injured Person:</th><td>{$report['name']}</td></tr>\n";
    echo "<tr><th>Last Reporter:</th><td>{$report['reporter']}</td></tr>\n";
    echo "<tr><th>Witnesses:</th><td>{$report['witnesses']}</td></tr>\n";
    echo "<tr><th>Recordable:</th><td>{$recordable}</td></tr>\n";
    echo "<tr><th>Follow Up Medical:</th><td>{$clinic}</td></tr>\n";
    echo "<tr><td colspan='2'>{$report['injury_description']}</td></tr>\n";
    echo "</table>";
    echo "<script>window.print();</script>\n";
    echo "</body>\n";
    echo "</html>\n";
}

function processNewInjury () {
    global $server;
    $handler = new Injuries($server->pdo);
    if (!$handler->addInjuryReport($_REQUEST)) return false;

    $notify = new Notification($server->pdo,$server->mailer);
    $body = file_get_contents(INCLUDE_ROOT.'/wwwroot/templates/email/newinjury.html');
    $body .= "<a href='{$server->config['application-root']}/hr/injuryreport?action=view&id={$handler->injuryID}'>New Injury Report</a>";
    $notify->notify('New Injury Report', 'New Injury Report Created', $body);
    return true;
}