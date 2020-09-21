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
        case 'create':
            createNewAccount();
        break;
        default: main(); break;
    }
}
else {
    main();  
}

function main () {
    global $server;
    $view = $server->getViewer('Create New Account');
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    $form->newForm('Create Account',null,'post','createaccount');
    $form->hiddenInput('action','create');
    $form->emailCapture('email','Username',null,null,'Enter a valid email for account verification, this will also serve as your username');
    $form->passwordCapture('password','Password');
    $form->passwordCapture('verify','Verify',);
    $form->inputCapture('firstname','First Name');
    $form->inputCapture('lastname','Last Name');
    $form->inputCapture('altemail','Alt. Email');
    $form->submitForm('Create Account');
    $form->endForm();
    echo "<script src='https://cdn.jsdelivr.net/npm/jquery-validation@1.17.0/dist/jquery.validate.min.js'></script>\n";
    echo "<script src='https://cdn.jsdelivr.net/npm/jquery-validation@1.17.0/dist/additional-methods.min.js'></script>\n";
    echo "<script src='{$view->PageData['wwwroot']}/scripts/createaccount.js'></script>";
    $view->footer();
}

function createNewAccount () {
    global $server;
    $handler = new UserServices($server);
    $namecheck = $handler->checkUsernameNotTaken($_REQUEST['email']);
    $notcreated = $handler->verifySingleSignUpAttempt($_REQUEST['email']);
    if (is_null($namecheck)||is_null($notcreated)) {
        $server->newEndUserDialog(
            "There was an unknown error attempting to create the account."
        );
    }
    if ($namecheck) {
        $server->newEndUserDialog(
            'That username already exists, please choose another one.',
            DIALOG_FAILURE,
            $server->config['application-root'].'/user/createaccount'
        );
    }
    elseif (!$notcreated) {
        $server->newEndUserDialog(
            "That username is already registered and awaiting verification, check your email for the verification.",
            DIALOG_FAILURE,
            $server->config['application-root']
        );
    }
    $server->processingDialog(
        [$handler,'createAccountToVerify'],
        [$_REQUEST],
        $server->config['application-root']
    );
}