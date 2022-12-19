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
    echo "<pre>",print_r(getCurrentMonthNominations(),true);
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

function getCurrentMonthNominations() {
    global $server;
    try {
        $pntr = $server->pdo->query(
            "select * from eotm where gen_date between date_trunc('month',current_date) and date_trunc('month',(current_date + interval '30 days'))"
        );

        //bundle the nominations
        $nominations = array();
        if (empty(($data = $pntr->fetchAll(PDO::FETCH_ASSOC)))) return $nominations;

        foreach($data as $row) {
            if (empty($nominations)) {
                $nominations[getNames($row['eid'],'employee')] = [getNames($row['uid'],'user')];
                continue;
            }
            else {
                foreach($nominations as $index => $value) {
                    if ($nominations[$index] == getNames($row['eid'],"employee")) {
                        array_push($nominations[$index],getNames($row['uid'],"user"));
                        continue;
                    }
                }
            }
        }
    }
    catch(Exception $e) {
        trigger_error($e->getMessage(),E_USER_WARNING);
    }

}

function getNames($id,$storage) {
    global $server;
    try {
        switch ($storage) {
            case 'employee':
                $pntr = $server->pdo->prepare("select profiles.first||' '||profiles.last as \"name\"from employees inner join profiles on profiles.id = employees.pid where employees.id = ?");
                if (!$pntr->execute([$id])) throw new Exception(print_r($pntr->errorInfo(),true));
                return $pntr->fetchAll(PDO::FETCH_ASSOC)[0]['name'];
            break;
            case 'user':
                $pntr = $server->pdo->prepare("select profiles.first||' '||profiles.last as \"name\"from user_accts inner join profiles on profiles.id = user_accts.pid where user_accts.id = ?");
                if (!$pntr->execute([$id])) throw new Exception(print_r($pntr->errorInfo(),true));
                return $pntr->fetchAll(PDO::FETCH_ASSOC)[0]['name'];
            break;
        }
    }
    catch (Exception $e) {
        trigger_error($e->getMessage(),E_USER_WARNING);
    }
   
    
}