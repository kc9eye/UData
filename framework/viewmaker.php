<?php
/* This file is part of Udata.
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
 * The application interface widgets
 * 
 * Creates the main interface in a consistent manner.
 * 
 * This class can be used stand alone, however it is best instanitated with the 
 * Instance class. In so doing all the heavy lifting is done for you.
 * @package UData\Framework\UI\Boostrap3
 * @see Instance::getViewer()
 * @uses Security
 * @uses config.php
 * @author Paul W. Lane
 */
Class ViewMaker implements ViewWidgets {

    /**
     * @var Array Contains an array of strings used in th ecurrent view. Typically used by a model
     * to push data to an interface.
     */
    public $ViewData;
    
    /**
     * @var Array Contains an array of strings which are typically used on a per page basis
     */
    public $PageData;

    /**
     * Class constructor
     * @param Instance $server The server instance class object
     * @see Instance::getViewer()
     * @return ViewMaker
     */
    public function __construct (Instance $server) {
        $this->PageData = $server->config; 
        $this->PageData['approot'] = !empty($this->PageData['application-root']) ? $this->PageData['application-root'] : '/';
        $this->PageData['wwwroot'] = $this->PageData['application-root'].'/wwwroot';
        $this->PageData['sidenav'] = false;
        if (!is_null($server->currentUserID)) {
            $this->ViewData['user'] = new User($server->pdo,$server->currentUserID);
            $this->ViewData['admin'] = $server->checkPermission('adminAll');
            $this->ViewData['theme'] = $this->ViewData['user']->getUserTheme();
        }
        else {
            $this->ViewData['user'] = null;
            $this->ViewData['admin'] = false;
        }
    }

    /**
     * The interface header.
     * 
     * Ouputs the beginning of the interface view to the stream.
     * @return Void
     * @uses ViewMaker::sideNav
     * @uses ViewMaker::navBar()
     * @todo rewrite to use `echo` instead of hard coded html
     */
    public function header () {
        echo "<!DOCTYPE html>";
        echo "<html>";
        echo "<head>";
        echo "<title>{$this->ViewData['pagetitle']}</title>";
        echo "<meta charset='utf-8' />";
        echo "<meta name='viewport' content='width=device-width, initial-scale=1' />";
        echo "<meta name='Copyright' content='2010-19 Paul W. Lane' />";
        echo "<meta name='License' content='MIT' />";
        echo "<meta name='msapplication-TileColor' content='#ffffff'>";
        echo "<meta name='msapplication-TileImage' content='{$this->PageData['wwwroot']}/images/favicons/ms-icon-144x144.png'>";
        echo "<meta name='theme-color' content='#ffffff'>";
        echo "<link rel='stylesheet' href='https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css' integrity='sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T' crossorigin='anonymous'>";
        echo "<link rel='stylesheet' href='{$this->PageData['approot']}/third-party/open-iconic-master/font/css/open-iconic-bootstrap.css' />";
        echo "<link rel='stylesheet' href='{$this->PageData['wwwroot']}/css/dark-header.css' />";
        echo "<link rel='stylesheet' href='{$this->PageData['wwwroot']}/css/print.css' />";
        echo "<link rel='icon' type='image/png' sizes='32x32' href='{$this->PageData['wwwroot']}/images/favicons/favicon-32x32.png' />";
        echo "<link rel='icon' type='image/png' sizes='96x96' href='{$this->PageData['wwwroot']}/images/favicons/favicon-96x96.png' />";
        echo "<link rel='icon' type='image/png' sizes='16x16' href='{$this->PageData['wwwroot']}/images/favicons/favicon-16x16.png' />";
        echo "<link rel='manifest' href='{$this->PageData['wwwroot']}/scripts/manifest.json' />";
        echo "<script src='https://code.jquery.com/jquery-3.3.1.slim.min.js' integrity='sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo' crossorigin='anonymous'></script>";
        echo "<script src='https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js' integrity='sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1' crossorigin='anonymous'></script>";
        echo "<script src='https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js' integrity='sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM' crossorigin='anonymous'></script>";
        echo "<script src='{$this->PageData['wwwroot']}/scripts/header.js'></script>";
        echo "<script>function getAppRoot() {return '{$this->PageData['approot']}';}</script>";
        if (!empty($this->ViewData['theme']))
            echo "<script src='{$this->PageData['wwwroot']}/scripts/themes/{$this->ViewData['theme']}'></script><link rel='stylesheet' href='{$this->PageData['wwwroot']}/css/theme-header.css' />";
        if (!empty($this->PageData['headinserts']) && is_array($this->PageData['headinserts'])) {
            foreach($this->PageData['headinserts'] as $insert) {
                echo "{$insert}";
            }
        }
        echo "</head>";
        echo "<body>";
        echo "<div class='page-header' id='template-header'>";
        echo "<h1>{$this->PageData['company-name']}</h1>";
        echo "<span>{$this->PageData['company-motto']}</span>";
        echo "</div>";
        $this->navBar();
        echo "<div class='container-float view-content'>";
    }

    
    /**
     * Generates the upper interface view navigation bar.
     * 
     * This method should not be called standalone as the `ViewMaker::header()`
     * method calls it as part of the standard interface. It is a seperate method
     * only as it would make the header method overly complex.
     * @uses ViewMaker::PageData
     * @uses ViewMaker::ViewData
     * @uses ViewMaker::security
     * @uses ViewMaker::config
     * @return Void
     */
    public function navBar () {
        echo "<nav class='navbar navbar-expand-lg bg-dark navbar-dark sticky-top'>";
        echo "<a class='navbar-brand' href='{$this->PageData['approot']}'>{$this->PageData['home-name']}</a>";
        echo "<button class='navbar-toggler' type='button' data-toggle='collapse' data-target='#toggledNavSm' aria-controls='toggledNavSm' aria-expanded='false' aria-label='Toggle navigation'>";
        echo "<span class='navbar-toggler-icon'></span>";
        echo "</button>";
        echo "<div class='collapse navbar-collapse' id='toggledNavSm'>";
        echo "<ul class='navbar-nav mr-auto'>";
        foreach($this->PageData['navbar-links'] as $name=>$link) {
            echo "<li class='nav-item'><a class='nav-link' href='{$this->PageData['approot']}{$link}'>{$name}</a></li>";
        }
        echo "</ul>";
        echo "<ul class='navbar-nav'>";
        if (is_null($this->ViewData['user'])) {
            echo "<li class='nav-item'><a class='nav-link' href='{$this->PageData['approot']}/user/createaccount'>Create Account</a></li>";
            echo "<li class='nav-item'><a class='nav-link' href='{$this->PageData['approot']}/user/login'>Sign In</a></li>";
        }
        else {
            echo "<li class='nav-item dropdown'>";
            echo "<a class='nav-link dropdown-toggle' id='navbarDropDown' role='button' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false' href='#'>";
            echo "<span class='oi oi-person' title='person' aria-hidden='true'></span>&#160;";
            echo "{$this->ViewData['user']->getFullName()}</a>";
            echo "<div class='dropdown-menu' aria-labelledby='navbarDropDown'>";
            if ($this->ViewData['admin']) {
                echo "<a class='dropdown-item' href='{$this->PageData['approot']}/admin/main'>";
                echo "<span class='oi oi-cog' title='settings' aria-hidden='true'></span>&#160;Settings</a>";
            }
            echo "<a class='dropdown-item' href='{$this->PageData['approot']}/user/myaccount'>";
            echo "<span class='oi oi-dashboard' title='dashboard' aria-hidden='true'></span>&#160;My Account</a>";
            echo "<a class='dropdown-item' href='{$this->PageData['approot']}/user/logout'>";
            echo "<span class='oi oi-delete' title='delete' aria-hidden='true'></span>&#160;Log Out</a>";
            echo "</div>";        
        }
        echo "</ul>";
        echo "</div>";
        echo "</nav>";
    }

    /**
     * Outputs the interface view footer.
     * 
     * Closes the interface content section, once the view is complete.
     * You are responsible for closing the view after your content.
     * 
     * @param Array $script_links Optional array of hyperlinks to add as script files
     * that may be required for you content.
     * @uses ViewMaker::sideNav
     * @return Void
     */
    public function footer (Array $script_links = null) {
        if ($this->PageData['sidenav']) {
            echo "</div></div>";
        }
        echo "</div>";
        if (!empty($_SESSION['controller-security'])) {
            echo "<div class='panel panel-default' id='admin-perms'>";
            echo "<div class='panel-heading'>";
            echo htmlentities('Roles & Permissions on this Page');
            echo "</div><div class='panel-body'>";
            $displayed = null;
            foreach($_SESSION['controller-security'] as $i=>$y) {
                if ($i == 'required') {
                    echo "<strong>Required for Access:</strong>";
                    echo "<ul class='list-group'>";
                    echo "<li class='list-group-item'>{$y}</li>";
                    echo "</ul>";
                }
                if ($i == 'roles') {
                    echo "<strong>Page Access Roles:</strong>";
                    echo "<ul class='list-group'>";
                    foreach($_SESSION['controller-security']['roles'] as $role) {
                        if ($role == $displayed) continue;
                        echo "<li class='list-group-item'>{$role}</li>";
                        $displayed = $role;
                    }
                    echo "</ul>";
                }
                if ($i == 'permissions') {
                    echo "<strong>Page Access Permissions:</strong>";
                    echo "<ul class='list-group'>";
                    foreach($_SESSION['controller-security']['permissions'] as $perm) {
                        if ($perm == $displayed) continue;
                        echo "<li class='list-group-item'>{$perm}</li>";
                        $displayed = $perm;
                    }
                    echo "</ul>";
                }
            }
            echo "</div></div>";
            unset($_SESSION['controller-security']);
        }
        echo "<div class='footer'>";
        echo "UData v".APP_VERSION." Copyright (C) 2008-2020 Paul W. Lane";
        $this->insertTab(2);
        echo "<a href='{$this->PageData['error-support-link']}' target='_blank'>Problem with this page?</a>";
        echo "<a href='https://github.com/kc9eye/UData/issues?utf8=%E2%9C%93&q=is%3Aissue+is%3Aclosed' class='mr-2 float-right' target='_blank'>Release Notes</a>";
        echo "</div>";
        if (!is_null($script_links)) {
            foreach($script_links as $link) {
                echo "<script src='".$link."'></script>";
            }
        }
        echo "</body></html>";
    }

    /**
     * Generates an off canvas sliding navigation bar.
     * 
     * This method of navigation is optional and has been given up
     * for the cleaner looking, on mobile, method of ViewMaker::sideDropDownMenu().
     * it can however still be used per preference.
     * @uses ViewMaker::sideNav
     * @param Array $navLinks Optional indexed string array of navigation links. 
     * The array should be indexed as such: `link_text=>link_address`.
     * @return Void
     */
    public function offCanvasSideNav (Array $navLinks = null) {
        $this->PageData['sidenav'] = true;
        echo "<div class='row'>";
        echo "<div class='col-md-1 col-xs-12'>";
        echo "<button type='button' class='btn btn-lg btn-info' id='offCanvasSideNavOpen'>";
        echo "Menu<br />";
        echo "<span class='glyphicon glyphicon-forward'></span>";
        echo "</button>";
        echo "<div id='offCanvasSideNav' class='offCanvasSideNav'>";
        echo "<a href='javascript:void(0)' class='closebtn' id='offCanvasSideNavClose'>&times;</a>";
        echo "<div class='offCanvasSideNavContent'>";
        if (!is_null($navLinks)) {
            foreach($navLinks as $link => $url) {
                if (is_array($url)){
                    if ($this->security->userHasPermission($url[1])) {
                        echo "<a href='{$url[0]}'>{$link}</a>";
                    }
                }
                else {
                    echo "<a href='{$url}'>{$link}</a>";
                }
            }
        }
        echo "</div>";
        echo "</div>";
        echo "</div>";
        echo "<div class='col-md-11 col-xs-12'>";        
    }

    /**
     * Simple debugging interface method
     * 
     * Generates a scrollable `<pre>` section to the output
     * stream with whatever string is given to the `$debug`
     * variable.
     * @param String @debug What ever you want output to the stream inside
     * a scrollable pre section.
     * @return Void
     */
    public function preDebug ($debug) {
        echo "<pre class='pre-scrollable'>{$debug}</pre><br />";
    }

    /**
     * Generates the left side drop down navigation menu.
     * 
     * Outputs a side navaigation drop down menu to the stream.
     * Same parameters as ViewMaker::offCanvasSideNav(). Cleaner look
     * on mobile than off canvas though.
     * @param Array $links An indexed array in the form `link_text=>link_address`
     * @see ViewMaker::offCanvasSideNav()
     * @uses ViewMaker::sideNav
     * @return Void
     */
    public function sideDropDownMenu (Array $links) {
        $this->PageData['sidenav'] = true;
        echo "<div class='row'>";
        echo "<div class='col-md-2 col-xs-12'>";
        echo "<div class='dropdown'>";
        echo "<button class='btn btn-primary dropdown-toggle' type='button' data-toggle='dropdown'>";
        echo "Section Menu <span class='caret'></span>";
        echo "</button>";
        echo "<div class='dropdown-menu'>";
        foreach($links as $item => $info) {
            if (is_array($info)) {
                if ($this->security->userHasPermission($info[1])) {
                    echo "<a class='dropdown-item' href='{$info[0]}'>{$item}</a>";
                }
                else {
                    echo "<a class='dropdown-item disabled'>{$item}</a>";
                }
            }
            else {
                echo "<a class='dropdown-item' href='{$info}'>{$item}</a>";
            }
        }
        echo "</div>";
        echo "</div>";
        echo "</div>";
        echo "<div class='col-md-10 col-xs-12 view-content'>";
    }

    /**
     * Outputs a file icon image to screen.
     * 
     * Based on the filename extension given as parameter
     * will output an image link to the stream. The images 
     * icons are the open source icons that are included 
     * with the Apache Web Server.
     * @param String $file_name The file name to use to retrieve an icon for.
     * @param String $icon_root If not null, taken as the standard apache /icons root
     * @return Void
     */
    public function getFileIcon ($file_name, $icon_root=null) {
        $ext = @pathinfo($file_name, PATHINFO_EXTENSION);
        if (!is_null($icon_root)) $dir = $icon_root;
        else $dir = '/icons';
        if (!empty($ext)) {
            switch ($ext) {
                case 'pdf': $icon = $dir.'/pdf.png';break;
                case 'odt': $icon = $dir.'/odf6odt.png';break;
                case 'doc': $icon = $dir.'/odf6odt.png';break;
                case 'docx': $icon = $dir.'/odf6odt.png';break;
                case 'ods': $icon = $dir.'/odf6ods.png';break;
                case 'xls': $icon = $dir.'/odf6ods.png';break;
                case 'xlsx': $icon = $dir.'/odf6ods.png';break;
                case 'png': $icon = $dir.'/image2.png';break;
                case 'bmp': $icon = $dir.'/image2.png';break;
                case 'gif': $icon = $dir.'/image2.png';break;
                case 'jpg': $icon = $dir.'/image2.png';break;
                case 'odp': $icon = $dir.'/odf6odp.png';break;
                case 'ppt': $icon = $dir.'/odt6odp.png';break;
                case 'pptx': $icon = $dir.'/odt6odp.png';break;
                case 'tar': $icon = $dir.'/tar.png';break;
                case 'tar.gz': $icon = $dir.'/compressed.png';break;
                case 'gz': $icon = $dir.'/compressed.png';break;
                case 'zip': $icon = $dir.'/compressed.png';break;
                case 'iso': $icon = $dir.'/diskimg.png';break;
                case 'exe': $icon = $dir.'/binary.png';break;
                case 'txt': $icon = $dir.'/text.png';break;
                case 'edit': $icon = $dir.'/quill.png';break;
                default: $icon = $dir.'/unknown.png';break;
            }
            return "<img src='{$icon}' alt='[ ? ]' />";
        }
        else {
            return "<img src='{$dir}/unknown.png' alt='[ ? ]' />";
        }
    }

    /**
     * Outputs a scrolltopbutton to the stream
     * 
     * Generates a button upon view scrolling that allows the 
     * user to immediately reach the top of the current view scroll
     * @return Void
     */
    public function addScrollTopBtn () {
        echo "<button type='button' class='btn btn-primary' onclick='document.body.scrollTop=0;document.documentElement.scrollTop=0;' ";
        echo "id='scrollTopBtn' title='Go to top' style='display:none;position:fixed;bottom:20px;right:30px;z-index:99;'>";
        echo "<span class='oi oi-arrow-thick-top' title='arrow-thick-top' aria-hidden='true'></span>&#160;Top</button>";
        echo "<script>$(document).ready(function(){window.onscroll = function() {if (document.body.scrollTop > 20||document.documentElement.scrollTop > 20) {";
        echo "$('#scrollTopBtn').css('display','block');} else {\$('#scrollTopBtn').css('display','none');}};});</script>";
    }

    /**
     * Outputs a standard tab to the stream.
     * 
     * Inserts a standardized tab to the stream in the form
     * of HTML nonbreaking spaces.
     * @param Int $num Optional, the number of tabs to insert.
     * @param Boolean $return If true output is returned instead of output to the stream
     * @return Void
     */
    public function insertTab ($num = 1, $return = false) {
        $tab = '';
        for ($cnt = 0; $cnt != $num; $cnt++) {
            $tab .= "&#160;&#160;&#160;";
        }
        if ($return) return $tab;
        else echo $tab;
    }

    /**
     * Outputs an HTML horizontal rule to the stream
     * @return Void
     */
    public function hr () {
        echo "<hr />";
    }

    /**
     * Outputs an HTML line break to the stream
     * @return Void
     */
    public function br () {
        echo "<br />";
    }

    /**
     * Encapsulates `$content` in an HTML heading.
     * @param String $content The string to encapsulate.
     * @return Void
     */
    public function h1 ($content, $centered=false) {
        if ($centered) 
            echo "<div class='row'><div class='col-md-3'></div><div class='col-md-6 col-xs-12'><h1>{$content}</h1></div><div class='col-md-3'></div></div>";
        else
            echo "<h1>{$content}</h1>";
    }

    /**
     * Ecapsulates `$content` in an HTML heading.
     * @param String $content The string to encapsulate
     * @return Void
     */
    public function h2 ($content, $centered=false) {
        if ($centered)
            echo "<div class='row'><div class='col-md-3'></div><div class='col-md-6 col-xs-12'><h2>{$content}</h2></div><div class='col-md-3'></div></div>";
        else
            echo "<h2>{$content}</h2>";
    }

    /**
     * Encapsulates `$content` in an HTML heading.
     * @param String $content The string to encasulate
     * @return Void
     */
    public function h3 ($content, $centered=false) {
        if ($centered)
            echo "<div class='row'><div class='col-md-3'></div><div class='col-md-6 col-xs-6'><h3>{$content}</h3></div><div class='col-md-3'></div></div>";
        else
            echo "<h3>{$content}</h3>";
    }

    /**
     * Encapsulates `$content` in an HTML strong section.
     * 
     * This is an inline method bolding.
     * @param String $content The string to encapsulate.
     * @param Boolean $return Optionally true to return the string instead of outputting it
     * @return Void
     */
    public function bold ($content, $return = false) {
        $string = "<strong>{$content}</strong>";
        if ($return) return $string;
        else echo $string;
    }

    /**
     * Outputs a given array as an unordered inline list
     * @param Array $list In the form ['list-item','list-item',...]
     * @param Boolean $return Optional true to return the list as a string, otherwise output to buffer
     * @return Mixed Void without $return, otherwise string.
     */
    public function inlineList (Array $list, $return = false) {
        $string = "<ul class='list-inline'>";
        foreach($list as $item) $string .= "<li class='list-inline-item'>{$item}</li>";
        $string .= "</ul>";
        if ($return) return $string;
        else echo $string;
    }

    /**
     * Encapsulate `$content` in an HTML paragraph section in the bg-info bootstrap style.
     * @param String $content The string to be in the paragraph.
     * @return Void
     */
    public function bgInfoParagraph ($content, $centered=false) {
        if ($centered)
            echo "<div class='row'><div class='col-md-3'></div><div class='col-md-6 col-xs-12'><p class='bg-info text-white'>{$content}</p></div><div class='col-md-3'></div></div>";
        else
            echo "<p class='bg-info text-white'>{$content}</p>";
    }

    /**
     * Outputs a print button to the stream.
     * 
     * Executes the javascript print function
     * @param Boolean $return Default = false. If true will return the string instead of outputing to buffer stream
     * @return Mixed Void if $return is false, String if $return true
     */
    public function printButton ($return = false) {
        $btn = "<button class='btn btn-secondary' onclick='window.print();'>Print</button>";
        if ($return) return $btn;
        else echo $btn;
    }

    /**
     * Outputs an edit button that when clicked goes to the given application centric addtress
     * @param String $addr The application centric API address the user should be sent to.
     * @param Boolean $passthru If true the address is passed through with alteration.
     * @param Boolean $return If true the method returns the string instead of echoing it
     * @return Mixed
     */
    public function editBtnSm ($addr, $return = false, $passthru = false) {
        $addr = $passthru ? $addr : $this->PageData['approot'].$addr;
        $str = "<a href='{$addr}' class='btn btn-sm btn-warning' role='button'><span class='oi oi-pencil' title='pencil' aria-hidden='true'></span></a>";
        if ($return) return $str;
        else echo $str;
    }

    /**
     * Outputs a delete button to the stream that when clicked opens the API given
     * @param String $addr The application centric API address the user should be sent to.
     * @param Boolean $passthru If true the address is passed through with alteration.
     * @param Boolean $return If true the method returns the string instead of echoing it
     * @return Mixed
     */
    public function trashBtnSm ($addr, $return = false, $passthru = false) {
        $addr = $passthru ? $addr : $this->PageData['approot'].$addr;
        // $str = "<a href='{$addr}' class='btn btn-sm btn-danger' role='button'><span class='oi oi-trash' title='trash' aria-hidden='true'></span></a>";
        $mid = uniqid('trashconf-');
        $str = "<button class='btn btn-sm btn-danger' data-toggle='modal' data-target='#{$mid}'>";
        $str .= "<span class='oi oi-trash' title='trash' aria-hidden='true'></span>";
        $str .= "</button>";
        $str .= "<div class='modal' id='{$mid}'>";
        $str .= "<div class='modal-dialog'>";
        $str .= "<div class='modal-content'>";
        $str .= "<div class='modal-header'>";
        $str .= "<h4 class='modal-title'>Confirm Delete</h4>";
        $str .= "</div>";
        $str .= "<div class='modal-body'>";
        $str .= "<span style='font-weight:normal;font-size:14pt'>Are you sure you want to delete this data?</span>";
        $str .= "</div>";
        $str .= "<div class='modal-footer'>";
        $str .= "<a href='{$addr}' class='btn btn-danger' role='button'>DELETE</a>";
        $str .= "<button type='button' class='btn btn-secondary' data-dismiss='modal'>CANCEL</button>";
        $str .= "</div></div></div></div>";
        if ($return) return $str;
        else echo $str;

    }

    /**
     * Ouputs the beginnig responsive table preamable to stream.
     * @param Array $columnHeadings The headings for the table columns if any
     * @param Boolean $centered Whether or not to include Bootstrap centering
     * @return Void
     */
    public function responsiveTableStart (Array $columnHeadings = null, $centered = false, $return = false) {
        $output = "";
        if ($centered) {
            $output = "<div class='row'><div class='col-md-3'></div><div class='col-md-6 col-xs-12'><div class='table-responsive'><table class='table'>";
        }
        else {
            $output = "<div class='table-responsive'><table class='table'>";
        }
        if (!is_null($columnHeadings)) {
            $output .= "<tr>";
            foreach($columnHeadings as $heading) {
                $output .= "<th>{$heading}</th>";
            }
            $output .= "</tr>";
        }
        if ($return) return $output;
        else echo $output;
    }

    /**
     * Outputs a table row based on the given array
     * @param Array $rows Multi-dimensional array in the form [[cell-data,...],...]
     * @param Boolean $return Optionally true, to return the string instead of outputting to stream
     * @return Mixed Void if not $return, and the array will be output the stream in the for '<tr><td>cell-data</td>...</tr>
     */
    public function tableRow (Array $rows, $return = false) {
        $string = "";
        foreach($rows as $row) {
            $string .= "<tr>";
            foreach($row as $data) {
                $string .= "<td>{$data}</td>";
            }
            $string .= "</tr>";
        }
        if ($return) return $string;
        else echo $string;
    }

    /**
     * Ouputs the closing responsive table to the stream
     * @param Boolean $centered Whether to center the table with Bootstrap
     */
    public function responsiveTableClose ($centered = false, $return = false) {
        if ($centered)
            $output = "</table></div></div><div class='col-md-3'></div></div>";
        else
            $output = "</table></div>";
        if ($return) return $output;
        else echo $output;
    }

    /**
     * Outputs a link formatted as a Bootstrap info button
     * @param String $link Application centric link for the button
     * @param String $name The name to display on the button
     * @param String $type Available format options 'default'|'primary'|'success'|'info'|'warning'|'danger'|'secondary'
     * @param Boolean $return Optional, if set true then the output is returned and not output to the stream buffer
     * @param String $target Optional target argument to launch new window, default is '_self'
     * @param Boolean $passthru Optional, if set true then the link is passed through unaltered.
     * @return Mixed Either a String if $return is true or Void otherwise
     */
    public function linkButton ($link ,$name, $type = null, $return=false, $target='_self', $passthru=false) {
        $link = $passthru ? $link : $this->PageData['approot'].$link;
        $class = 'btn ';
        if (!is_null($type)) {
            switch($type) {
                case 'default': $class .= 'btn-light'; break;
                case 'primary': $class .= 'btn-primary'; break;
                case 'success': $class .= 'btn-success'; break;
                case 'info': $class .= 'btn-info'; break;
                case 'warning': $class .= 'btn-warning'; break;
                case 'danger': $class .= 'btn-danger'; break;
                case 'secondary': $class .= 'btn-secondary';break;
                default: $class .= 'btn-info';
            }
        }
        else {
            $class .= 'btn-info';
        }
        $string = "<a href='{$link}' class='{$class}' role='button' target='{$target}'>{$name}</a>";
        if ($return) return $string;
        else echo $string;
    }

    /**
     * Outputs a button and beginning preamble for a collapse section.
     * 
     * Everything after this method call output to the stream will
     * be wrapped in a collapse division until the subsequent `ViewMaker::endBtnCollapse()`
     * is called.
     * @param String $id The collapse ID for the button
     * @param String $name The label name of the collapse button.
     * @return Void This method outputs to the stream and returns nothing.
     */
    public function beginBtnCollapse ($name = 'Show/Hide Content',$id = null, $popoverid = null) {
        if (is_null($id)) $id = uniqid('collapse-');
        if (!is_null($popoverid)) {
            echo "<script>";
            echo "$(document).ready(function(){";
            echo "$('#popoverToggle').popover({";
            echo "html:true,";
            echo "title:'<h4>Preview</h4>',";
            echo "content:'{$popoverid}',";
            echo "trigger:'hover'});});";
            echo "</script>";
            echo "<button id='popoverToggle' data-toggle='collapse' data-target='#{$id}' class='btn btn-secondary'>{$name}</button>";
        }
        else {
            echo "<button data-toggle='collapse' data-target='#{$id}' class='btn btn-secondary'>{$name}</button>";
        }        
        echo "<div id='{$id}' class='collapse'>";

    }

    /**
     * Outputs the closing button collapse division
     * @return Void All out put is directed to the stream and returns no value.
     * @see ViewMaker::beginBtnCollapse()
     */
    public function endBtnCollapse () {
        echo "</div>";
    }

    /**
     * Inserts a bootstrap card into the stream
     * @param String $body The body text of the card
     * @param String $header Optional $header for the card
     * @param String $footer Optional $footer for the card
     * @param Boolean $centered Optional true to center the card in one of three rows on md screens
     * @param Boolean $return Optional true to return the card, otherwise it is ouput to the buffer
     * @return Mixed Void if $return is false, otherwise returns a String.
     */
    public function wrapInCard ($body, $header = null, $footer = null, $centered = false, $return = false) {
        $string = "";
        if ($centered) $string .= "<div class='row'><div class='col-md-3'></div><div class='col-md-6 col-xs-12'>";
        $string .= "<div class='card'>";
        if (!is_null($header)) $string .= "<div class='card-header'>{$header}</div>";
        $string .= "<div class='card-body'><div class='card-text'>{$body}</div></div>";
        if (!is_null($footer)) $string .= "<div class='card-footer'>{$footer}</div>";
        $string .= "</div>";
        if ($centered) $string .= "</div><div class='col-md-3'></div></div>";
        if ($return) return $string;
        else echo $string;
    }

    /**
     * Inserts content wrapped in a bootstrap scrollable '<pre>' section
     * @param String $content The content to wrap
     * @return Void Outputs directly to the stream and returns nothing
     */
    public function wrapInPre ($content) {
        echo "<pre class='pre-scrollable'>{$content}</pre>";
    }

    /**
     * Inserts a responsive image into the buffer stream, or returns as such.
     * @param String $file The URI of the image file to insert
     * @param Boolean $return Optional true if the method should return the image instead of inserting to the stream
     */
    public function responsiveImage ($file, $return = false) {
        $image = "<img class='img-fluid' src='{$file}' alt='[IMAGE]' />";
        if ($return) return $image;
        else echo $image;
    }

    /**
     * Inserts an image thumbnail to stream, or returns as such
     * @param String $file The URI of the image file to insert
     * @param Boolean $return Optional true if the method should return the image instead of inserting to the stream
     * @deprecated replaced by Bootstrap 4 card class
     */
    public function imageThumbnail ($file, $return = false) {
        $image = "<img class='img-thumbnail' src='{$file}' alt='[IMAGE]' />";
        if ($return) return $image;
        else echo $image;
    }

    /**
     * Converts given time format strings to user designated ones
     * @param String $timestamp The timestamp to format
     * @param Boolean $return Optionally true to return a string, otherwise result is output to the stream
     * @return Mixed If $return then string, else void, false on error
     */
    public function formatUserTimestamp ($timestamp,$return = false) {
        try {
            if (is_null($this->ViewData['user'])) $string = $timestamp;
            elseif (empty($this->ViewData['user']->getUserDateFormat())) $string = $timestamp;
            else $string = date($this->ViewData['user']->getUserDateFormat(),strtotime($timestamp));
            if ($return) return $string;
            else echo $string;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    public function genBarcode ($code,$caption = null) {
        echo "<img src='{$this->PageData['application-root']}/data/barcode?barcode={$code}&width=200&height=50&format=png&text=0' alt='[BARCODE]' />";
        if (!is_null($caption)) {
            echo "<figcaption class='figure-caption text-center'>{$caption}</figcaption>";
        }
    }

    public function accordianFromArray (Array $accord, $return = false) {
        $accordianID = uniqid("accordian-");
        $out = "<div id='{$accordianID}'>";
        foreach($accord as $i => $v) {
            $section = uniqid("section-");
            $out .= "<div class='card'><div class='card-header'>";
            $out .= "<a class='card-link' data-toggle='collapse' href='#{$section}'>{$i}</a>";
            $out .= "</div><div id='{$section}' class='collapse' data-parent='#{$accordianID}'>";
            $out .= "<div class='card-body'>{$v}</div></div>";
        }
        $out .= "</div>";
        if ($return) return $out;
        else echo $out;
    }
}