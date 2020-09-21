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

$server->userMustHavePermission('addNewTraining');

if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'new':
            $handler = new Training($server->pdo);
            $server->processingDialog(
                [$handler,'addAvailableTraining'],
                [$_REQUEST],
                $server->config['application-root'].'/hr/addtraining'
            );
        break;
        case 'remove':
            //$server->getDebugViewer(var_export($_REQUEST,true));
            //die();
            $handler = new Training($server->pdo);
            $server->processingDialog(
                [$handler,'removeAvailableTrainingByID'],
                [$_REQUEST['id']],
                $server->config['application-root'].'/hr/addtraining'
            );
        break;
        default: addNewTrainingDisplay(); break;
    }
}
else
    addNewTrainingDisplay();

function addNewTrainingDisplay () {
    global $server;
    include('submenu.php');
    $training = new Training($server->pdo);
    $view = $server->getViewer('Add New Training');
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->sideDropDownMenu($submenu);
    $view->h1("Add New Training",true);
    $form->newForm();
    $form->hiddenInput('uid',$server->currentUserID);
    $form->hiddenInput('action','new');
    $form->inputCapture('description','Training',null,true,'The training description.');
    $form->checkBox('required',['Required','Required for employment'],'true',false,null,'false');
    $form->selectBox(
        'reoccur_time_frame',
        'Reoccuring',
        [
            ['30 days','30 Days'],
            ['60 days','60 Days'],
            ['90 days','90 Days'],
            ['6 months','6 Months'],
            ['1 year','1 Year'],
            ['2 years','2 Years'],
            ['3 years','3 Years'],
            ['4 years','4 Years'],
            ['5 years','5 Years']
        ],
        false,"Timeframe for retraining notifications.",
        ['0 days','Not Reoccurring']
    );
    $form->submitForm("Add",true);
    $form->endForm();
    $view->hr();
    $view->h2("Current Available Training");
    $view->responsiveTableStart(['Training','Required','Reoccurring','Edit']);
    foreach($training->getAllAvailableTraining() as $row) {
        $required = ($row['required'] == '1') ? 'Yes' : 'No';
        $time = ($row['reoccur_time_frame'] == '00:00:00') ? 'Not reoccurring' : $row['reoccur_time_frame'];
        echo "<tr><td>{$row['description']}</td><td>{$required}</td><td>{$time}</td><td>";
        $view->trashBtnSm('/hr/addtraining?action=remove&id='.$row['id']);
        echo "</td></tr>\n";
    }
    $view->responsiveTableClose();
    $view->footer();
}