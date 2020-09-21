#!/usr/bin/php
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
require_once(dirname(dirname(__DIR__)).'/lib/init.php');

try {
    if (!empty($argv[1])) {
        $service = new $argv[1]($server);
        if (!$service->run()) throw new Exception("{$argv[1]} failed to complete.");
    }
    else {
        foreach(new DirectoryIterator(dirname(__FILE__).'/services') as $fileinfo) {
            if (!$fileinfo->isDot() && !$fileinfo->isDir() && $fileinfo->getFilename() != '.git' && $fileinfo->isFile()) {
                    $servicename = basename($fileinfo->getFilename(),".php");
                    $service = new $servicename($server);
                    if (!$service->cronjob()) {
                        if (!$service->run()) throw new Exception("{$servicename} failed to complete succesfully.");
                        echo "Ran service: {$servicename}\n";
                    }
                    else
                        continue;
            }
        }
    }
}
catch (Exception $e) {
    trigger_error($e->getMessage(),E_USER_WARNING);
    $service->kill();
}