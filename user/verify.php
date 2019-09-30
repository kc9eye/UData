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

if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'verify':
            verifyAccountCreation();
        break;
        default: main(); break;
    }
}
else {
    main();
}

function main () {
    global $server;
    $view = $server->getViewer('Verifying Account');
    trigger_error('Unknown access attempt',E_USER_ERROR);
    $view->footer();
}

function verifyAccountCreation () {
    global $server;
    $handler = new UserServices($server);
    if ($handler->verifyAccount(urldecode($_REQUEST['id']))) {
        $_SESSION['login-redirect'] = '/user/myaccount';
        $server->newEndUserDialog(
            'Account verified! Please log in with your new account.',
            DIALOG_SUCCESS,
            $server->config['application-root'].'/user/login'
        );
    }
    else {
        $server->newEndUserDialog(
            'Something went wrong with your request, contact an administrator.',
            DIALOG_FAILURE
        );
    }
}