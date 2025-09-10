<?php
/**
* Copyright 2025 Paul W. Lane <kc9eye@gmail.com>
*
* Licensed under the Apache License, Version 2.0 (the "License");
* you may not use this file except in compliance with the License.
* You may obtain a copy of the License at
*
*   http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and
* limitations under the License.
**/
require_once(dirname(__DIR__).'/lib/init.php');
$server->userMustHavePermission("editSupervisorComments");

if (!empty($_REQUEST)) {
    switch($_REQUEST['action']) {
        default: displayForm();break;
    }
}
else displayForm();

function displayForm() {
    global $server;
    $view = $server->getViewer("Station Comment");
    $view->footer();
}