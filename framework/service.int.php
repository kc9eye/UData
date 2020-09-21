<?php
/* This file is part of UData.
 * Copyright (C) 2019 Paul W. Lane <kc9eye@outlook.com>
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
interface Service {
    /**
     * Instantiates the service with the server Instance class
     * @param Instance $server The server init.php Instance class
     */
    public function __construct (Instance $server);

    /**
     * Runs the specific service
     * @return Boolean The service should return a boolean upon completion, true for successful, false otherwise
     */
    public function run ();

    /**
     * Kills the specific service and unloads any hanging processes
     * @return Boolean Should return true on succes or false on failure
     */
     public function kill ();

     /**
      * Returns whether or not the service should be run with cron or not.
      * @return Boolean True if the service should defer, false if it should be run
      * at any time
      */
      public function cronjob ();
}