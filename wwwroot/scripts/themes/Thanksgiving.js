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
    var iconimg = "<img src='"+getAppRoot()+"/wwwroot/images/theme/thanksgivingicon.png' class='rounded float-right border border-dark' style='max-height:75px;max-width:100px;margin-top:-50px;' />";
    $('#template-header').css({
        'background-color':'#d29060',
        'color':'#a60802',
        "border": "1px solid #cf0900"
    });
    $('nav').addClass('thanksgiving-theme-nav');
    $('#template-header').append(iconimg);
    $('.view-content').css({
        "padding":"10px",
        "min-height":"65vh",
        "background":"#f5e7de url('" + getAppRoot() + "/wwwroot/images/theme/thanksgivingbg.png') center fixed no-repeat",
     });
     $('.footer').css({
        'background-color':'#d29060',
        'color':'#a60802',
        "border": "1px solid #cf0900"
    });
    $('.footer a:link').css({
        "color": "rgb(0,0,0)"
    });
});