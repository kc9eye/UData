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
    include('submenu.php');
    $server->userMustHavePermission('viewProfiles');
    $view = $server->getViewer("Employee Matrix");
    $view->sideDropDownMenu($submenu);
    $matrix = getMatrix();
    foreach($matrix as $product) {
        if ($product['product'] != "indirect") {
            $view->h3($product['product']);
        }
    } 

    // $view->wrapInPre(print_r(getMatrix(),true));
    $view->footer();
}

function getProducts() {
    global $server;
    $pntr = $server->pdo->prepare("select * from products where active is true");
    try {
        if (!$pntr->execute()) throw new Exception(print_r($pntr->errorInfo(),true));
        return $pntr->fetchAll(PDO::FETCH_ASSOC);
    }
    catch (Exception $e) {
        trigger_error($e->getMessage(), E_USER_WARNING);
        return false;
    }
}

function getProductWorkCells($prokey) {
    global $server;
    $pntr = $server->pdo->prepare("select * from work_cell where prokey = ?");
    try {
        if (!$pntr->execute([$prokey])) throw new Exception(print_r($pntr->errorInfo(),true));
        return $pntr->fetchAll(PDO::FETCH_ASSOC);
    }
    catch (Exception $e) {
        trigger_error($e->getMessage(),E_USER_WARNING);
        return false;
    }
}

function getEmployees() {
    global $server;
    $pntr = $server->pdo->prepare("select * from employees where end_date is null");
    try {
        if (!$pntr->execute()) throw new Exception(print_r($pntr->errorInfo(),true));
        return $pntr->fetchAll(PDO::FETCH_ASSOC);
    }
    catch(Exception $e) {
        trigger_error($e->getMessage(),E_USER_WARNING);
        return false;
    }
}

function getEmployeeMatrix($eid) {
    global $server;
    $pntr = $server->pdo->prepare('select * from cell_matrix where eid = ? order by gen_date desc limit 1');
    try {
        if (!$pntr->execute([$eid])) throw new Exception(print_r($pntr->errorInfo(),true));
        return $pntr->fetch(PDO::FETCH_ASSOC);
    }
    catch(Exception $e) {
        trigger_error($e->getMessage(),E_USER_WARNING);
        return false;
    }
}

function getMatrix() {
    $labor = array();
    $indirect = array();
    $matrix = array();

    foreach(getEmployees() as $person) {
        $ass = getEmployeeMatrix($person['id']);
        if (empty($ass)) {
            $emp = getEmployeeName($person['id']);
            array_push($indirect,"{$emp['first']} {$emp['last']}");
        }
        else array_push($labor,$ass);
    }

    foreach(getProducts() as $product) {
        $final = array();
        foreach(getProductWorkCells($product['product_key']) as $cell) {
            $cell['labor'] = array();
            foreach($labor as $person) {
                if (!empty($person))
                    if ($person['cellid'] == $cell['id'])
                        array_push($cell['labor'],$person);
            }
            array_push($final, $cell);
        }
        array_push($matrix,['product'=>$product['description'],'cells'=>$final]);
    }

    array_push($matrix,['product' =>"indirect",'cells'=>$indirect]);
    return $matrix;
}

function getEmployeeName($eid) {
    global $server;
    $sql =
    'select profiles.first,profiles.last
    from employees
    inner join profiles on profiles.id = employees.pid
    where employees.id = ?';
    $pntr = $server->pdo->prepare($sql);
    try {
        if (!$pntr->execute([$eid])) throw new Exception(print_r($pntr->errorInfo(),true));
        return $pntr->fetch(PDO::FETCH_ASSOC);
    }
    catch(Exception $e) {
        trigger_error($e->getMessage(),E_USER_WARNING);
        return false;
    }
}