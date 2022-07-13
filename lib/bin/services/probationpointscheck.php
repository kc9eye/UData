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
class probationpointscheck {
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

    public function run () {
        $sql =
        'with probation as (
            select
                employees.id,
                profiles.first,
                profiles.last,
                sum(missed_time.points) as "points"
            from
                employees
            inner join
                profiles on profiles.id = employees.pid
            inner join
                missed_time on missed_time.eid = employees.id
            where
                current_date <= (employees.start_date + interval \'60 days\')
            and
                employees.end_date is null
            group by
                employees.id,profiles.first,profiles.last
            order by
                points
        )
        select * from probation
        where points >= 3';
        try {
            $pntr = $this->server->pdo->query($sql);
            $result = $pntr->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($result)) return $this->notify($result);
            else return true;
        }
        catch(Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    private function notify($data) {
        $body = "<!DOCTYPE html>";
        $body .= "<html><head><title>Inspection Overdue</title></head><body>";
        $body .= "<h1><img src='/favicon-16x16.png' />UData</h1>";
        $body .= "<h2>Probation Period Points Violations";
        $body .= "<table border='1'>";
        $body .= "<tr><th>Name</th><th>Points</th>";
        foreach($data as $row) {
            $body .= "<tr><td>{$row['first']} {$row['last']}</td><td>{$row['points']}</td></tr>";
        }
        $body .= "</table></body></html>";
        $notify = New Notification($this->server->pdo,$this->server->mailer);
        return $notify->notify("Probation Points Violation","Probation Points Violations",$body);
    }
}