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

$server->userMustHavePermission('viewLostTime');

if (!empty($_REQUEST['action'])) {
    $handler = new LostTime($server->pdo);
    switch($_REQUEST['action']) {
        case 'excused':
            displayLostTime($handler->getLostTimeDateRange($_REQUEST['begin_date'],$_REQUEST['end_date'],true));
        break;
        case 'unexcused':
            displayLostTime($handler->getLostTimeDateRange($_REQUEST['begin_date'],$_REQUEST['end_date'],false));
        break;
        case 'perfect':
            displayPerfect($handler->getPerfectAttendanceDateRange($_REQUEST['begin_date'],$_REQUEST['end_date']));
        break;
        default: main(); break;
    }
}
else {
    main();
}

function main ($request = false) {
    global $server;
    include('submenu.php');

    //View header options for adding the Boostrap DatePicker
    $pageOptions = [
        'headinserts'=> [
            '<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/js/bootstrap-datepicker.min.js"></script>',
            '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/css/bootstrap-datepicker3.css"/>'
        ]
    ];
    $view = $server->getViewer('Lost Time',$pageOptions);
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->sideDropDownmenu($submenu);
    $view->h1('Employee Time Data');
    $form->newForm();
    $form->labelContent(
        'Date',
        "<div class='input-group input-daterange'>\n
        <input class='form-control' type='text' name='begin_date' />\n
        <span class='input-group-addon'>to</span>\n
        <input class='form-control' type='text' name='end_date' />\n
        </div>\n"
    );
    $form->selectBox(
        'action',
        'Query Type',
        [
            ['excused','Lost Time (include excused)'],
            ['unexcused','Lost Time (excluding excused)'],
            ['perfect','Perfect Attendance']
        ],
        true
    );
    $form->submitForm("Lookup");
    $form->endForm();

    //Script for the bootstrap date picker
    echo "<script>$(document).ready(function(){
        var options = {
            format:'yyyy/mm/dd',
            autoclose: true
        };
        $('.input-group input').each(function(){
            $(this).datepicker(options);
        });
    });</script>";
    $view->footer();
}

function displayLostTime ($request) {
    global $server;
    include('submenu.php');
    $view = $server->getViewer("Lost Time");
    $view->sideDropDownMenu($submenu);
    if (is_array($request)) $count = count($request);
    else $count = 0;
    $view->printButton();
    $view->h2("{$count} Lost Time Records Found");
    $view->responsiveTableStart(['Date','Name','Absent','Arrive Time','Leave Time','Description','Excused','Recorder','Record Date']);
    foreach($request as $row) {
        $absent = $row['absent'] ? 'Yes' : 'No';
        $excused = $row['excused'] ? 'Yes' : 'No';
        echo "<tr><td>".$view->formatUserTimestamp($row['occ_date'],true)."</td><td>{$row['name']}</td>";
        echo "<td>{$absent}</td><td>{$row['arrive_time']}</td><td>{$row['leave_time']}</td>";
        echo "<td>{$row['description']}</td><td>{$excused}</td><td>{$row['recorder']}</td>";
        echo "<td>".$view->formatUserTimestamp($row['recorded'],true)."</td></tr>\n";
    }
    $view->responsiveTableClose();
    $view->footer();
}

function displayPerfect ($request) {
    global $server;
    include('submenu.php');
    $view = $server->getViewer("Perfect Time");
    $view->sideDropDownMenu($submenu);
    if (is_array($request)) $count = count($request);
    else $count = 0;
    $view->printButton();
    $view->h2("{$count} Perfect Records Found");
    $view->responsiveTableStart(['Name']);
    foreach($request as $row) {
        echo "<tr><td>{$row['name']}</td></tr>";
    }
    $view->responsiveTableClose();
    $view->footer();
}