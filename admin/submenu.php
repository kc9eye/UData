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
    'Application Info'=>$server->config['application-root'].'/admin/main',
    'Users'=>$server->config['application-root'].'/admin/users',
    'Roles'=>$server->config['application-root'].'/admin/roles',
    'Error Log'=>$server->config['application-root'].'/admin/errlog',
    'Access Log'=>$server->config['application-root'].'/admin/accesslog',
    'API Documentation'=>$server->config['application-root'].'/docs/api/index.html',
    'UDatabase Scheme'=>$server->config['application-root'].'/docs/database_structure/UData_Database_Structure.html'
];