<?php
/**
 * Copyright (C) 2022  Paul W. Lane <kc9eye@gmail.com>
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
require(dirname(__DIR__).'/lib/init.php');
$server->userMustHavePermission("viewProfiles");
include('submenu.php');
$view = $server->getViewer('HR: Employee Profile');
$view->sideDropDownMenu($submenu);
$view->h1("Career Attendance Data");

try {
    $eids = $server->pdo->query(
        'select employees.id 
        from employees
        inner join profiles on profiles.id = employees.pid 
        where end_date is null
        order by profiles.last asc'
    );
    $view->responsiveTableStart(['Name','Current Points','Career Ratio','Total Occurences','Start Date']);
    foreach($eids as $row) {
        $emp = new Employee($server->pdo,$row['id']);
        echo 
            '<tr><td><a href="'.$view->PageData['approot'].'/hr/viewemployee?id='.$row['id'].'">'.$emp->getFullName().'</a></td>
            <td>'.$emp->getAttendancePoints().'</td>
            <td>'.$emp->getAttendanceRatio().htmlentities("%").'</td>
            <td>'.$emp->getAttendanceOcurrences()[0]['count'].'</td>
            <td>'.$view->formatUserTimestamp($emp->getStartDate(),true).'</td></tr>';
        unset($emp);
    }

    $view->responsiveTableClose();
}
catch(Exception $e) {
    trigger_error($e->getMessage(),E_USER_ERROR);
}

$view->footer();