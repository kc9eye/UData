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
    $labor = array();
    foreach(getEmployees() as $person) {
        array_push($labor,getEmployeeMatrix($person['id']));
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
        $matrix[$product['description']] = $final;
    }

    $view->wrapInPre(print_r($matrix,true));
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