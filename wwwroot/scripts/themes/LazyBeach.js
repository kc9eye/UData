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
    $('nav').removeClass('bg-dark');
    $('nav').addClass('beach-theme-nav');
    $('#template-header').css({
        'background':"url('" + getAppRoot() + "/wwwroot/images/theme/beachheaderbg.png') center",
        'color':'#ff692e',
        "border": "1px solid #ff692e"
    });
    $('.footer').css({
        "background-color":"#874c24",
        "color": "#ff692e",
        "border": "1px solid #ff692e"
     });
     $('.view-content').css({
        "padding":"10px",
        "min-height":"65vh",
        "background":"rgb(255,255,255) url('" + getAppRoot() + "/wwwroot/images/theme/beachbg.png') center fixed no-repeat"
     });
});