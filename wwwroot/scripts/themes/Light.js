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
$(document).ready(function(){
    //The navbar goes to default
    $('nav').removeClass('bg-dark navbar-dark');
    $('nav').addClass('bg-light navbar-light');

    //Header and footer should be the same
    $('#template-header').css({
        "background-color":"rgb(235, 235, 235)",
        "color": "rgb(35, 35, 35)"
    });
    $('.footer').css({
        "background-color":"rgb(235, 235, 235)",
        "color": "rgb(35, 35, 35)"
    });
});