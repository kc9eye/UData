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

$server->mustBeValidUser();

if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'updatetheme':
            $handler = new UserServices($server);
            $server->processingDialog(
                [$handler,'updateAccountTheme'],
                [$_REQUEST['theme'],$_REQUEST['pid']],
                $server->config['application-root'].'/user/myaccount'
            );
        break;
        case 'date_format':
            $server->processingDialog(
                [new UserServices($server),'updateAccountDateFormat'],
                [$_REQUEST['format'],$_REQUEST['pid']],
                $server->config['application-root'].'/user/myaccount'
            );
        break;
        case 'updateprofile':
            $handler = new UserServices($server);
            $server->processingDialog(
                [$handler,'updateAccountProfile'],
                [$_REQUEST],
                $server->config['application-root'].'/user/myaccount'
            );
        break;
        default: main(); break;
    }
}
else {
    main();
}

function main () {
    global $server;
    $user = new User($server->pdo,$server->currentUserID);

    //Theme selection is now a glob of the themes directory. Placing a new 
    //theme file in that directory, will be automatically included.
    $themes = [['','Dark']];
    foreach(glob(\INCLUDE_ROOT.'/wwwroot/scripts/themes/*.js') as $theme) {
        array_push($themes,[basename($theme),basename($theme,'.js')]);
    }

    //Available formats for the date property
    $formats = [
        ['','Unformated'],
        ['c','ISO 8601'],
        ['Y/m/d H:i','YYYY/MM/dd 24H:00'],
        ['Y-m-d H:i','YYYY-MM-dd 24H:00'],
        ['m/d/Y H:i','MM/dd/YYYY 24H:00'],
        ['m-d-Y H:i','MM-dd-YYYY 24H:00'],
        ['n/j/y H:i','M/d/YY 24:00'],
        ['Y/m/d g:i','YYYY/MM/dd 12H:00'],
        ['Y-m-d g:i','YYYY-MM-dd 12H:00'],
        ['m/d/Y g:i','MM/dd/YYYY 12H:00'],
        ['m-d-Y g:i','MM-dd-YYYY 12H:00'],
        ['n/j/y g:i','M/d/YY 12H:00'],
        ['Y/m/d','YYYY/MM/dd'],
        ['Y-m-d','YYYY-MM-dd'],
        ['m/d/Y','MM/dd/YYYY'],
        ['n/j/y','M/d/YY']
    ];

    $view = $server->getViewer('My Account');
    $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->h1('<small>UData Account Settings for:</small>'.$user->getFullName());
    $view->hr();
    $view->h3('Account Actions');
    $view->linkButton('/user/password_reset?username='.$user->getUserName(),'Reset Password','warning');

    $view->br();
    $view->insertTab();
    $view->br();

    $view->beginBtnCollapse('Update Profile');
    $view->h3('Account Profile');
    $profile = $user->getProfileArray();
    $form->newForm();
    $form->hiddenInput('action','updateprofile');
    $form->hiddenInput('pid',$user->pid);
    $form->inputCapture('first','First Name',$profile['first']);
    $form->inputCapture('middle','Middle Name',$profile['middle']);
    $form->inputCapture('last','Last Name',$profile['last']);
    $form->inputCapture('other','Other Name',$profile['other']);
    $form->inputCapture('address','Address',$profile['address']);
    $form->inputCapture('address_other','Address Cont.',$profile['address_other']);
    $form->inputCapture('city','City',$profile['city']);
    $form->inputCapture('state_prov','State/Prov.',$profile['state_prov']);
    $form->inputCapture('postal_code','Postal Code',$profile['postal_code']);
    $form->inputCapture('home_phone','Home Phone',$profile['home_phone']);
    $form->inputCapture('cell_phone','Cell Phone',$profile['cell_phone']);
    $form->inputCapture('alt_phone','Other Phone',$profile['alt_phone']);
    $form->inputCapture('e_contact_name','Emergency Contact',$profile['e_contact_name']);
    $form->inputCapture('e_contact_relation','Contact Relation',$profile['e_contact_relation']);
    $form->inputCapture('e_contact_number','Emergency Number',$profile['e_contact_number']);
    $form->submitForm('Update');
    $form->endForm();
    $view->endBtnCollapse();

    $view->br();
    $view->insertTab();
    $view->br();

    $view->bold("Change UI Theme");
    $view->br();
    $form->newInlineForm();
    $form->hiddenInput('action','updatetheme');
    $form->hiddenInput('pid',$user->pid);
    $form->inlineSelectBox('theme','',$themes);
    $form->inlineSubmit();
    $form->endInlineForm();

    $view->br();
    $view->insertTab();
    $view->br();

    $view->bold("Change Date Formating");
    $view->br();
    $form->newInlineForm();
    $form->hiddenInput('action','date_format');
    $form->hiddenInput('pid',$user->pid);
    $form->inlineSelectBox('format','',$formats);
    $form->inlineSubmit();
    $form->endInlineForm();

    $view->hr();
    $view->h3('Notifications I Receive');
    $view->responsiveTableStart();
    foreach($user->getUserNotifications() as $row) {
        echo "<tr><td>{$row['description']}</td></tr>\n";
    }
    $view->responsiveTableClose();
    $view->footer();
}