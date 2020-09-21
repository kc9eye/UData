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
Interface ViewWidgets {

    /**
     * Required by every controller view.
     * 
     * The header() should completely output the entire top portion of
     * the controller view up to and including the first `<body>` tag.
     * The header need no stop there however, typically it also includes
     * any standard nav bars and page headers. The controller view should be 
     * wrapped between this call and the `footer()` call. The header should 
     * leave space enough prior to the `footer` call for the view's content.
     * @author Paul W. Lane
     */
    public function header ();


    /**
     * Required by every controller view.
     * 
     * The `footer()` call should be the last call to close all
     * dangling tags left from the `header()` and round out the bottom
     * of the contorller view port. It is up to the content creator to 
     * close all tags in their content between the `header()` and `footer()`
     * call.
     * @author Paul W. Lane
     */
    public function footer ();
}