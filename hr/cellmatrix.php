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
            editMatrix();
        break;
    }
}
else
    editMatrix();

function editMatrix() {
    global $server;
    include('submenu.php');
    $server->userMustHavePermission('viewProfiles');
    $emp = new Employee($server->pdo,$_REQUEST['id']);
    $view = $server->getViewer('HR: Edit Matrix');
    $view->sideDropDownMenu($submenu);
    $view->h1("Matrix for: <small>".$emp->getFullName()."</small>");
    $view->wrapInPre(getCellList());
    $view->footer();
}

function getCellList() {
    global $server;
    $sql =
    'select products.description,work_cell.cell_name
    from work_cell
    inner join products on products.product_key = work_cell.prokey
    where products.active is true
    sort by products.description
    order by products.description';

    try{
        $pntr = $server->pdo->prepare($sql);
        if (!$pntr->execute()) throw new Exception(print_r($pntr->errorInfo(),true));
        return $pntr->fetchAll(PDO::FETCH_ASSOC);
    }
    catch(Exception $e) {
        trigger_error($e->getMessage(),E_USER_WARNING);
        return false;
    }
}