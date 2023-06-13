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

/**
 * The main server init script.
 * 
 * This file should be sourced in the first line of any controller
 * scripts. This file creates the server instance and makes 
 * it available globally as the `$server` variable. This gives you access
 * to the Security model, the instance PDO, configuration variables,
 * interface widgets and the like. 
 * 
 * @return Instance in the form of the global variable `$server`
 * @uses config.php Configuration variables
 * @uses autoloader.php The class autoloader
 * @uses errorhandler.php The application error/exception handler
 * @uses instance.php The server class instance
 */
session_name('UDID');
session_start();
ob_start(null,0,PHP_OUTPUT_HANDLER_STDFLAGS^PHP_OUTPUT_HANDLER_REMOVABLE);

require_once(dirname(__DIR__).'/etc/constants.php');
require_once(dirname(__DIR__).'/etc/config.php');
require_once(dirname(__DIR__).'/framework/autoloader.php');
require_once(dirname(__DIR__).'/framework/errorhandler.php');

$loader = new Autoloader(dirname(__DIR__),['.php','.lib.php','.class.php','.obj.php','.int.php']);
$error = new Errorhandler($config['error-log-file-path'], $config['error-support-link']);
die("I initialized the error handler!");


if (empty($_SESSION['viewer'])) {
    try {
        include(dirname(__DIR__).'/third-party/Mobile_Detect/Mobile_Detect.php');
        $mobile = new Mobile_Detect();
        $_SESSION['viewer'] = ($mobile->isMobile() ? ($mobile->isTablet() ? 'tablet' : 'mobile') : 'standard');
        unset($mobile);
    }
    catch (Exception $e) {
        echo "Uncaught init Exception: ".$e->getMessage();
    }
}
try {
    $server = new Instance($config);
}
catch (Exception $e) {
    trigger_error($e->getMessage(), E_USER_ERROR);
}