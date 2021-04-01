//File: Name
//
//About: License
//
//Copyright (C)2021 Paul W. Lane <kc9eye@gmail.com>
//
//This program is free software; you can redistribute it and/or modify
//
//it under the terms of the GNU General Public License as published by
//
//the Free Software Foundation; version 2 of the License.
//
//This program is distributed in the hope that it will be useful,
//
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
//
//GNU General Public License for more details.
//
//You should have received a copy of the GNU General Public License along
//
//with this program; if not, write to the Free Software Foundation, Inc.
//
//51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
document.addEventListener("DOMContentLoaded",function(e){
    var scrollPos = window.sessionStorage.getItem('scroll');
    if (scrollPos) window.scrollTo(0,scrollPos);
    sessionStorage.clear();
});

window.onbeforeunload = function(e){
    sessionStorage.setItem('scroll',window.scrollY)
;}