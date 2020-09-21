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

$server->userMustHavePermission('maintenanceAccess');

$view = $server->getViewer('Maintenance');
$view->sideDropDownMenu($submenu);
$view->bold("<span class='glyphicon glyphicon-arrow-left'></span> Select API with the left menu.");
echo "<div class='row'>\n";
echo "<div class='col-md-3'></div>\n";
echo "<div class='col-md-6 col-xs-12'>\n";
$view->responsiveImage($view->PageData['wwwroot'].'/images/maintenance.jpg');
echo "</div>\n";
echo "<div class='col-md-3'></div>\n";
echo "</div>\n";

$view->footer();