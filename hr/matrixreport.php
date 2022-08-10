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
            displayReport();
        break;
    }
}
else
    displayReport();

function displayReport() {
    global $server;
    $server->userMustHavePermission('viewProfiles');
    $view = $server->getViewer("Employee Matrix");
    $view->wrapInPre(var_export(getData(),true));
    $view->footer();
}

function getData() {
    global $server;
    $sql = 
    'select 
        profiles.first,
        profiles.middle,
        profiles.last,
        employees.id
    from employees
    inner join profiles on profiles.id = employees.pid
    where employees.end_date is null
    order by profiles.last asc';
    try {
        $pntr = $server->pdo->prepare($sql);
        if (!$pntr->execute()) throw new Exception(print_r($pntr->errorInfo(),true));
        $emps = $pntr->fetchAll(PDO::FETCH_ASSOC);
    }
    catch(Exception $e) {
        trigger_error($e->getMessage(),E_USER_WARNING);
        return "NOPE";
    }

    $sql =
    'select *
    from cell_matrix
    where eid = ?
    order by gen_date desc
    limit 1';
    $pntr = $server->pdo->prepare($sql);
    foreach($emps as $row) {
        try {
            if (!$pntr->execute($row['id'])) throw new Exception(print_r($pntr->errorInfo(),true));
            
        }
    }
}