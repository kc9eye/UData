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
        case 'login': verifyLogin(); break;
        default: main(); break;
    }
}
else {
    main();
}

function main ($failed = false) {
    global $server;
    $view = $server->getViewer('Login');
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    $form->newForm('Log On');
    if ($failed) {
        echo "<div class='row'>";
        echo "<div class='col-md-3'></div>";
        echo "<div class='col-xs-12 col-md-6 alert alert-danger'>";
        echo "<h5>Warning: <small>Incorrect username or password: &#160;";
        echo "<a href='{$server->config['application-root']}/user/password_reset'>Forgot my password</a>";
        echo "</small></h5></div><div class='col-md-3'></div></div>";
    }
    elseif (isset($_SESSION['user_privilege_escalation'])) {
        unset($_SESSION['user_privilege_escalation']);
        if (isset($_SESSION['uid'])) $server->userRightsEscalation($_SESSION['uid'],$_SESSION['login-redirect']);
        echo "<div class='row'><div class='col-md-3'></div>";
        echo "<div class='col-xs-12 col-md-6 alert alert-danger'>";
        echo "<h5>Warning:<small>The current account does not have sufficient privileges to access this page.</small></h5>";
        echo "</div><div class='col-md-3'></div></div>";
    }
    $form->hiddenInput('action','login');
    $form->emailCapture('username','Email',null,['email'=>'true']);
    $form->passwordCapture('password','Password',null,true);
    $form->checkBox('remember',['Remember','Stay logged in'],1);
    $form->submitForm('Log On');
    $form->endForm();
    $view->footer();
}

function verifyLogin () {
    global $server;
    if ($server->security->verifyLogOn($_REQUEST['username'],$_REQUEST['password']) === true) {
        $_SESSION['uid'] = $server->security->secureUserID;
        $server->security->deletePersistentLogOn();
        if (isset($_REQUEST['remember']) && $_REQUEST['remember'] == 1) {
            $server->security->setPersistentLogOn($server->security->secureUserID);
        }
        if (isset($_SESSION['login-redirect'])) {
            $url = $_SESSION['login-redirect'];
            unset($_SESSION['login-redirect']);
            $server->redirect($url);
        }
        else {
            $server->redirect('');
        }
    }
    else {
        main(true);
    }
}