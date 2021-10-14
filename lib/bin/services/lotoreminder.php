<?php
/**
 * Copyright (C) 2021 Paul W. Lane <kc9eye@gmail.com>
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
class lotoreminder implements Service {
    private $server;
    private $data;

    public function __construct (Instance $server) {
        $this->server = $server;
        $this->data = array();
    }

    public function cronjob () {
        return true;
    }

    public function kill () {
        return true;
    }

    public function run() {
        $notifier = new Notification($this->server->pdo,$this->server->mailer);
        $body = $this->server->mailer->wrapInTemplate('lotoreminder.html',"");
        $notifier->notify("LOTO Reminder","LOTO Annual Review DUE",$body);
        return true;
    }
}