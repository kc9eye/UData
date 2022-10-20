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
require(dirname(__DIR__).'/lib/init.php');
$server->userMustHavePermission("viewProfiles");
try {
    $eids = $server->pdo->query(
        'select employees.id 
        from employees
        inner join profiles on profiles.id = employees.pid 
        where end_date is null
        order by profiles.last asc'
    );
    echo "<pre>";
    foreach($eids as $row) {
        $emp = new Employee($server->pdo,$row['id']);
        echo $emp->getFullName()," ",$emp->getAttendanceOcurrences()[0]['count'],"\n"; 
    }
    echo "</pre>";
}
catch (Exception $e) {
    trigger_error($e->getMessage(),E_USER_ERROR);
}