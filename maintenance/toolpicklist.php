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
require_once(dirname(__DIR__).'/lib/init.php');

$server->userMustHavePermission('accessMaintenance');

if (empty($_REQUEST['cellid'])) {
    $server->newEndUserDialog('No cell ID given, unable to handle request.',DIALOG_FAILURE,$server->config['application-root'].'/');
}

$cell = new WorkCell($server->pdo,$_REQUEST['cellid']);
echo "<!DOCTYPE html>\n";
echo "<html>\n";
echo "<head>\n";
echo "<title>{$cell->Name} Pick List</title>\n";
echo "<link rel='stylesheet' type='text/css' href='{$server->config['application-root']}/wwwroot/css/print.css' />\n";
echo "<style>\n";
echo "table {
        width:100%;
    }
    table, td, th {
        border-collapse: collapse;
        border: 1px solid black;
    }
    th,td {
        text-align:center;
    }
    td {
        height:30px;
        vertical-align: center;
    }\n";
echo "</style>\n";
echo "<head>\n";
echo "<body>\n";
echo "<h1>Tool Pick List for: {$cell->Name}</h1>\n";
echo "<p>Generated by: {$server->security->user['firstname']} {$server->security->user['lastname']} at: ".date('c')."</p>\n";
echo "<hr />\n";
echo "<table style='line-height:1.5'>\n";
echo "<tr><th>Picked</th><th>Qty.</th><th>Category</th><th>Tool</th></tr>\n";
foreach($cell->Tools as $tool) {
    echo "<tr><td><input type='checkbox' /></td><td>{$tool['qty']}</td><td>{$tool['category']}</td><td>{$tool['description']}</td></tr>\n";
}
echo "<tr><td style='height:300px;text-align:left;vertical-align:top;' colspan='4'>Amendments and Annotations:</td></tr>\n";
echo "</table>\n";
echo "<script>window.print()</script>\n";
echo "</body>\n";
echo "</html>\n";

