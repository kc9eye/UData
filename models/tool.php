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
 * Tool Class Model
 * 
 * @package UData\Model\Database\Postgres
 * @author Paul W. Lane
 * @license GPLv2
 */
class Tool {
    private $dbh;

    public $ID;
    public $Description;
    public $Category;
    public $Author;
    public $Created;

    public function __construct (PDO $dbh, $id) {
        $this->dbh = $dbh;
        $this->ID = $id;
        $sql = 'SELECT * FROM tools WHERE id = ?';
        try {
            $pntr = $this->dbh->prepare($sql);
            if (!$pntr->execute([$id])) throw new Exception("Select failed: {$sql}");
            if (!is_array(($result = $pntr->fetchAll(PDO::FETCH_ASSOC)))) throw new Exception("Select did not return array: {$sql}");
            $this->Description = $result[0]['description'];
            $this->Category = $result[0]['category'];
            $this->Author = $result[0]['uid'];
            $this->Created = $result[0]['_date'];
            return $this;
        }
        catch (PDOException $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    public function getID () {
        return $this->ID;
    }

    public function getDescription () {
        return $this->Description;
    }

    public function getCategory () {
        return $this->Category;
    }

    public function getAuthor () {
        return $this->Author;
    }

    public function getDate () {
        return $this->Created;
    }
}