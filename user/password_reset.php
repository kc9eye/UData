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

$admin = new UserServices($server);

if (!empty($_REQUEST['username'])) {
    if ($admin->resetPassword($_REQUEST['username'])) {
        $server->newEndUserDialog('Email verification sent', DIALOG_SUCCESS);
    }
    else {
        $server->newEndUserDialog('Something went wrong with the request, try again later',DIALOG_FAILURE);
    }
}
elseif (!empty($_REQUEST['id'])) {
    if ($admin->verifyResetCode($_REQUEST['id'])) {
        $view = $server->getViewer('Reset Password');
        $form = new FormWidgets($view->PageData['wwwroot'].'scripts');
        $form->newForm('Reset Password',$server->config['application-root'].'/user/password_reset');
        $form->hiddenInput('verify',$_REQUEST['id']);
        $form->passwordCapture('password','New Password',null,['minlength'=>'5']);
        $form->passwordCapture('confirm','Confirm Password',null,['equalTo'=>"'#password'"]);
        $form->submitForm('Reset',false,$server->config['application-root'].'/user/login');
        $form->endForm();
        $view->footer();
    }
    else {
        $server->notAuthorized();
    }
}
elseif (!empty($_REQUEST['password'])) {
    if ($admin->finishReset($_REQUEST)) {
        $server->newEndUserDialog('Password reset successfully',DIALOG_SUCCESS,$server->config['application-root'].'/user/login');
    }
    else {
        $server->newEndDialog('Something went wrong with the reset',DIALOG_FAILURE);
    }
}
else {
    $view = $server->getViewer('Reset Password');
    $form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
    $form->newForm('Reset Password');
    $form->emailCapture('username','Username',null,true);
    $form->submitForm('Reset',false,$server->config['application-root'].'/user/login');
    $form->endForm();

    $view->footer();
}