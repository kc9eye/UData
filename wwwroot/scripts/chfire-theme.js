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
    var iconimg = "<img src='"+getAppRoot()+"/wwwroot/images/theme/chfireicon.png' style='float:right;margin-top:-65px;width:75px;height:75px;' />";
    $('#template-header').css({
        "background-color":"#00215b",
        "color": "#ce1432",
        "border": "1px solid #ce1432"
    });
    $('#template-header').append(iconimg);
    $('.footer').css({
        "background-color":"#00215b",
        "color": "#ce1432",
        "border": "1px solid #ce1432",
     });
     $('nav').css({
         "background-color":"#00215b"
     });
     $('.view-content').css({
        "padding":"10px",
        "min-height":"65vh",
        "background": "rgb(255,255,255) url('" + getAppRoot() + "/wwwroot/images/theme/chfirebg.png') fixed center no-repeat"
     });
});