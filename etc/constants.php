<?php
/* This file is part of Udata.
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
#Instance constants
/**
 * @var String INCLUDE_ROOT File path to the directory root of the application, relative to the machine.
 */
define('INCLUDE_ROOT', dirname(__DIR__));
/**
 * @var String PHPMAILER_DIR File path to the current PHPMailer classes
 */
define('PHPMAILER_DIR', dirname(__DIR__).'/third-party/PHPMailer/src');
/**
 * @var Boolean DIALOG_SUCCESS Simplifies calls to newEndUserDialog
 * @see Instance::newEndUserDialog()
 */
define('DIALOG_SUCCESS', true);
/**
 * @var Boolean DIALOG_FAILURE Simplifies calls to newEndUserDialog
 * @see Instance::newEndUserDialog()
 */
define('DIALOG_FAILURE', false);
/**
 * @var String APP_VERSION The current version string of the application in dotted format major.minor.patch
 */
define('APP_VERSION', '1.3.17');
/**
 * @var String BOOTSTRAP_VERSION The current version string of Bootstrap the application UI utilizes in dotted format major.minor.patch
 */
define('BOOTSTRAP_VERSION','4.3.1');