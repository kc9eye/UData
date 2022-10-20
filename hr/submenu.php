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
$submenu = [
    'Employee Profiles'=>$server->config['application-root'].'/hr/main',
    'Employee Training'=>$server->config['application-root'].'/hr/skills',
    'Employee Injuries'=>$server->config['application-root'].'/hr/incidents',
    'Employee Time'=>$server->config['application-root'].'/hr/time',
    'Attendance Points'=>$server->config['application-root'].'/hr/points',
    'Attendance Occurrences'=>$server->config['application-root'].'/hr/attendanceoccurences',
    'Employee Matrix'=>$server->config['application-root'].'/hr/matrixreport'
];