<?php
/**
 * Copyright (C) 2022 Paul W. Lane <kc9eye@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * 		http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
require_once(dirname(__DIR__).'/lib/init.php');
if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        default:
            EOMDisplay();
        break;
    }
}
else
    EOMDisplay();

function EOMDisplay() {
    global $server;
    $server->userMustHavePermission('viewProfiles');
    include('submenu.php');
    $view = $server->getViewer("Employee of the Month");
    $view->sideDropDownMenu($submenu);
    $view->h1("Employee of the Month Nominations");
    echo "<pre>",print_r(getActiveEmployees(),true),"</pre>";
    $view->footer();
}

function getActiveEmployees() {
    global $server;
    $pntr = $server->pdo->query(
        "select profiles.first||' '||profiles.last as \"name\",employees.id as \"eid\"
        from employees
        inner join profiles on profiles.id = employees.pid
        where employees.end_date is null
        order by profiles.last asc"
    );
    return $pntr->fetchAll(PDO::FETCH_ASSOC);
}