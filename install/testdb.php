<?php
/* This file is part of UData.
 * Copyright (C) 2018 Paul W. Lane <kc9eye@outlook.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */
require_once(dirname(__DIR__).'/etc/config.php');
require_once(dirname(__DIR__).'/class/dbobjects.php');
?>
<html>
    <head><title>TESING DATABASE</title></head>
    <body>
        <h1>Testing Database</h1>
        <pre>
        <?php
            $simple = 'CREATE TABLE test1 (id varchar primary key, col1 integer, col2 varchar)';
            $complex = [
                'CREATE TABLE complex1 (col1 varchar, col2 timestamp with time zone default now())',
                'CREATE TABLE complex2 (col1 integer primary key, col2 varchar not null)',
                'CREATE TABLE complex3 (col1 varchar, col2 integer references complex2(col1))'
            ];
            echo "\n";
            echo 'Attempting to connect to database...';
            try {
                $pdo = new PDO($config['dbpdo'], $config['dbuser'], $config['dbpass']);
                $db = new DBObjects($pdo);
                echo " OK\n";
            }
            catch (Exception $e) {
                echo " FAILED\n";
                vardump($e);
            }
            echo 'Attempting simple test...';
            try {
                $db->createObject($simple);
                echo " OK\n";
            }
            catch (Exception $e) {
                echo " FAILED\n";
                var_dump($e);
            }
            echo 'Attempting complex test...';
            try {
                $db->createObject($complex);
                echo " OK\n";
            }
            catch (Exception $e) {
                echo " FAILED\n";
                var_dump($e);
            }
            echo "Cleaning up ...";
            try {
                $db->createObject([
                    'DROP TABLE test1',
                    'DROP TABLE complex1',
                    'DROP TABLE complex2',
                    'DROP TABLE complex3'
                ]);
                echo " DONE\n";
            }
            catch (Exception $e) {
                echo " FAILED\n";
                var_dump($e);
            }
        ?>
        </pre>
    </body>
</html>