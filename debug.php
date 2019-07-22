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
require_once('lib/init.php');
$replace = explode('/',$server->config['application-root']);
array_push($replace,'//');
$uri = str_replace($replace,'',$_SERVER['REQUEST_URI']);

$debug = array(
    'replace_var'=>$replace,
    'request_uri'=>$_SERVER['REQUEST_URI'],
    'app_root'=>$server->config['application-root'],
    'session_uri'=>$uri
);
$server->getDebugViewer(var_export($debug,true));