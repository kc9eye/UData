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

main();

function main(){
    global $server;
    include('submenu.php');
    $view = $server->getViewer("Lock Out/Tag Out");
    $view->sideDropDownMenu($submenu);
    $view->h1("Lock Out/Tag Out Document");
    if ($server->checkPermission('approveLoto')){
        $view->linkButton('https://docs.google.com/document/d/12y6sMb_7dBlRLs1J1WkA7E3cJbLrqwxcN8seehNw5WQ/edit?usp=sharing','Edit Document',null,false,'_blank',true);
    }
    $view->hr();
    echo '<iframe id="printFrame" name="printFrame" src="https://docs.google.com/document/d/e/2PACX-1vTq_6H50JXePCayWIjdkJe85dKbqruiwB8am217tjxHLmyp58_UInXw93LFfQgIUXEg1KhJb-GRBky6/pub?embedded=true" width="800" height="600"></iframe>';
    $view->footer();
}