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
/**
 * DBObjects Class Framework
 * 
 * @package UData\Framework\Database\Postgres
 * @author Paul W. Lane
 * @license GPLv2
 */
Class DBObjects {

    private $dbh;

    public function __construct (PDO $dbh) {
        $this->dbh = $dbh;
    }

    public function createObject ($object) {
        if (is_array($object)) {
            return $this->createObjectsFromArray($object);
        }
        try {
            $this->dbh->beginTransaction();
            $this->dbh->query($object);
            $this->dbh->commit();
            return true;
        }
        catch (Exception $e) {
            $this->dbh->rollback();
            return $e->getMessage();
        }
        return false;
    }

    public function createObjectsFromArray (Array $objects) {
        try{
            $this->dbh->beginTransaction();

            foreach($objects as $obj) {
                $this->dbh->query($obj);
            }

            $this->dbh->commit();
            return true;
        }
        catch (Exception $e) {
            $this->dbh->rollback();
            return $e->getMessage();
        }
        return false;
    }

    
}