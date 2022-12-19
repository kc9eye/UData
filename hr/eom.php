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
        case 'nominate':
            addNomination();
        break;
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
    $noms = getCurrentMonthNominations();
    echo "<pre>",print_r($noms,true),"</pre>";
    // echo '<div id="nominationDisplay">';
    // if (!empty($noms)) {
    //     foreach($noms as $index => $value) {
    //         echo 
    //         '<div class="card">
    //             <div class="card-body">
    //                 <h3 class="card-title bg-warning">'.$index.'</h3>
    //                 <b>Nominated by:</b>
    //                 <ul class="list-group">';
    //                 foreach($value as $nominator) {
    //                     echo '<li class="list-group-item">'.$nominator.'</li>';
    //                 }
            
    //         echo 
    //         '       </ul>
    //             </div>
    //         </div>
    //         <hr />';
    //     }
    // }
    // echo
    // '   <form id="nominationForm">
    //         <input type="hidden" name="action" value="nominate" />
    //         <input type="hidden" name="uid" value="'.$server->currentUserID.'" />
    //         <div class="form-group form-selection">
    //             <label class="form-label" for="eid">Nominations</label>
    //             <select class="form-control" name="eid">';
    //             foreach(getActiveEmployees() as $row) {
    //                 echo '<option value="'.$row['eid'].'">'.$row['name'].'</option>';
    //             }
    // echo
    // '           </select>
    //         </div>
    //         <button id="submitBtn" class="btn btn-secondary" type="submit">Nominate</button>
    //     </form
    // </div>
    // <script>
    //     let form = document.getElementById("nominationForm");
    //     let btn = document.getElementById("submitBtn");
    //     btn.addEventListener("click",async (event)=>{
    //         event.preventDefault();
    //         btn.setAttribute("disabled","disabled");
    //         btn.innerHTML = "<span class=\"spinner-border spinner-border-sm\"></span>&#160;"+btn.innerHTML;
    //         let result = await fetch(
    //             "'.$server->config['application-root'].'/hr/eom",
    //             {method:"POST",body:new FormData(form)}
    //         );
    //         document.getElementById("nominationDisplay").innerHTML = await result.text();
    //         window.scrollTo(0,0);
    //     });
    // </script>';
    $view->footer();
}

function addNomination() {
    global $server;
    $server->userMustHavePermission('viewProfiles');
    try {
        $pntr = $server->pdo->prepare("insert into eotm values (:id,now(),:eid,:uid)");
        $insert = [
            ':id'=>uniqid(),
            ':eid'=> $_REQUEST['eid'],
            ':uid'=> $_REQUEST['uid']
        ];
        if (!$pntr->execute($insert)) throw new Exception(print_r($pntr->errorInfo(),true));
        echo
        '<div class="border border-secondary rounded m-3">
            <h4 class="bg-success">Completed</h4>
            <a href="'.$server->config['application-root'].'/hr/eom" class="btn btn-success m-1" role="button">Return</a>
        </div>';
    }
    catch(Exception $e) {
        trigger_error($e->getMessage(),E_USER_WARNING);
        echo
        '<div class="border border-secondary rounded m-3">
            <h4 class="bg-danger">Error</h4>
            <b>An error occurred.</b>&#160;
            <a href="'.$server->config['application-root'].'/hr/eom" class="btn btn-danger m-1" role="button">Try Again</a>
        </div>';
    }
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
        if (empty($pntr)) return $nominations;

        foreach($pntr as $row) {
            if (empty($nominations)) {
                $nominations[getNames($row['eid'],"employee")] = [getNames($row['uid'],"user")];
                continue;
            }
            else{
                foreach($nominations as $index=>$value) {
                    if ($index == getNames($row['eid'],"employee")) {
                        array_push($nominations[$index],getNames($row['uid'],"user"));
                        continue;
                    }
                    else {
                        $nominations[getNames($row['eid'],"employee")] = [getNames($row['uid'],"user")];
                        continue;
                    }
                }
            }
        }
        return $nominations;
    }
    catch(Exception $e) {
        trigger_error($e->getMessage(),E_USER_WARNING);
        return array();
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