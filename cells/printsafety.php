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
$server->userMustHavePermission('viewWorkCell');
$cell = new WorkCell($server->pdo,$_REQUEST['cellid']);
echo "<!DOCTYPE html>\n";
echo "<html>\n";
echo "<head>\n";
echo "<title>{$cell->Name} Safety Assessment</title>\n";
echo "<link rel='stylesheet' type='text/css' href='{$server->config['application-root']}/wwwroot/css/print.css' />\n";
echo "<style>\n";
echo "  body {\n";
echo "      border:groove black 2px;\n";
echo "  }\n";
echo "  div#content {\n";
echo "      margin:10px;\n";
echo "  }\n";
echo "</style>\n";
echo "</head>";
echo "<body>\n";
echo "<h1>{$cell->Name} Safety Assessment</h1>\n";
echo "<h3>by: {$cell->Author} {$cell->Date}</h3>\n";
echo "<hr />\n";
echo "<div id='content'>\n";
echo "{$cell->Safety['body']}";
echo "</div>\n";
echo "<script>window.print();</script>\n";
echo "</body>\n";
echo "</html>\n";
die();