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

#DIsable the time limit for large files
ignore_user_abort(true);
set_time_limit(0);

#Where we called with no query
if (empty($_REQUEST)) {
    $server->pageNotFound();
}

$file = urldecode($_REQUEST['file']);
$path = $server->config['data-root'].'/'.$file;
$mime = empty($_REQUEST['mime']) ? _mime_content_type($path) : $_REQUEST['mime'];
$disposition = empty($_REQUEST['dis']) ? 'attachment' : 'inline';

#Check if the file exists
if (!file_exists($path)) {
    $server->pageNotFound();
}
else {
    $fpntr = fopen($path,'r');
}

#Clear the buffer, just in case
$buff = ob_get_contents();
ob_clean();

#Stream the file
if (!empty($mime)) {
    header('Content-type: '.$mime);
}
else {
    header('Content-type: application/octet-stream');
}
if ($disposition == 'inline') {
    header('Content-Disposition: inline; filename="'.$file.'"');
}
else {
    header('Content-Disposition: attachment; filename="'.$file.'"');
}
header('Content-length: '.filesize($path));
header('Cache-control: private');

while (!feof($fpntr)) {
    $buffer = fread($fpntr, 2048);
    echo $buffer;
}
fclose($fpntr);
die();

function _mime_content_type($filename) {
    $result = new finfo();

    if (is_resource($result) === true) {
        return $result->file($filename, FILEINFO_MIME_TYPE);
    }

    return false;
}