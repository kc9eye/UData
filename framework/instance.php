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
  * Main server instance. Is the first object instantiated from *lib/init.php*
  *
  * This class handles the spool of the PDO, Security model, and other methods
  * that make it easier to construct concise controllers.
  *
  * @param array $config Should contain an array for comfiguration options.
  * @package UData\FrameWork
  * @example ../docs/example_ui.php
  * @return Instance
  * @see etc/config.php
  */
Class Instance {

    const DIALOG_UNFINISHED = 1024;
    const DIALOG_FINISHED = 4096;
    const DIALOG_ERROR = 2112;
    
    /**
     * Contains the publicly accessible configuraiton array
     * 
     * @var Array $config
     */
    public $config;

    /**
     * Contains the publicly accessible PDO object
     * 
     * @var PDO $pdo
     */
    public $pdo;

    /**
     * Contains the publicly accessible security object
     * 
     * @var Security $security
     */
    public $security;

    /**
     * Contains the publicly accessible mailer object
     * 
     * @var Mailer $mailer
     */
    public $mailer;

    /**
     * Contains the current user ID
     * 
     * @var String $currentUserID Null if no valid users
     */
    public $currentUserID;

    /**
     * The class constructor
     * 
     * Creates the most needed objects for models and controllers and stores them 
     * in properties
     * 
     * @param Array $config Should be the configuration array. 
     * @return Object Instance
     * @see config.php
     * @author Paul W. Lane
     */
    public function __construct ($config) {
        $this->config = $config;
        $this->pdo = new PDO($this->config['dbpdo'], $this->config['dbuser'], $this->config['dbpass']);
        $this->security = $this->getAuthority();
        $this->mailer = new Mailer($this->config);
        $this->currentUserID = $this->security->secureUserID;
    }

    /**
     * Instantiates the ViewMaker object and outputs UI controller interface stub.
     * 
     * Initializes the ViewMaker object with all the required objects and outputs
     * the initial framework UI interface. It is up to you to end that interface stub
     * with a `$myobjectvar->footer()` method call. This method also returns the
     * ViewMaker object. It should be called thusly: `$view = $server->getViewer('My Page Title')`.
     * 
     * @param String $pagetitle Optional pagetitle for the browser page. Defaults to configuration option
     * `$config['company-name']`.
     * @param Array $pagedata Optional array of PageData for content manipulation.
     * @return Object ViewMaker
     * @uses ViewMaker
     * 
     * @author Paul W. Lane
     */
    public function getViewer ($pagetitle = null, $pagedata = null) {
        $view = new ViewMaker($this->security, $this->config);
        if (!is_null($pagedata)) {
            if (is_array($pagedata)) {
                foreach($pagedata as $i=>$v) {
                    $view->PageData[$i] = $v;
                }
            }
        }
        $view->ViewData['pagetitle'] = is_null($pagetitle) ? $this->config['company-name'] : $pagetitle;
        $view->header();
        return $view;
    }
    
    /**
     * Outputs an entire HTML interface with debugging information.
     * 
     * This method will ouput string given encapsulated in a scrollable `<pre>`
     * section, wrapped in an entire standard framework interface to the output stream.
     * It is intended to be used for debugging controllers.
     * 
     * @param String $debug Anything passed in this variable as string will be output
     * to the stream.
     * 
     * @return void
     * 
     * @author Paul W. Lane
     */
    public function getDebugViewer ($debug) {
        $buff = ob_get_contents();
        ob_clean();
        $this->userMustHaveRole('Administrator');
        $view = new ViewMaker($this->security,$this->config);
        $view->ViewData['pagetitle'] = 'DEBUG CONTENT';
        $view->header();
        echo "<pre class='scrollable'>\n";
        echo "Your debug info:\n----------\n{$debug}\n---------\n\n";
        echo "Session info\n--------\n".print_r($_SESSION, true)."\n----------\n\n";
        echo "Server Variables\n-----------\n".print_r($_SERVER, true)."\n-----------\n\n";
        echo "</pre>\n";
        $view->footer();
        die();
    }

    /**
     * Is called by the constructor to instantiate the `Security` model and object
     * 
     * @return Object Security
     * @author Paul W. Lane
     */
    private function getAuthority () {
        $security = new Security($this->pdo);
        if ($security->checkPersistentLogOn()) {
            $security->setUser();
            $_SESSION['uid'] = $security->secureUserID;
            $this->currentUserID = $security->secureUserID;
        }
        elseif (isset($_SESSION['uid'])) {
            $security->secureUserID = $_SESSION['uid'];
            $security->setUser();
            $this->currentUserID = $security->secureUserID;
        }
        return $security;
    }

    /**
     * Outputs a message to the stream for the end user wrapped in an interface.
     * 
     * Outputs a standard interface message the end user via the `$message` paramater.
     * The interface can be styled in one of two ways using the `$success` parameter. The interface 
     * will also display a click button that will redirect the user to any hyperlink given
     * using the `$target_link` parameter.
     * 
     * @param String $message The message to be displayed to the user.
     * @param Boolean $success Optional, defaults to `FALSE` indicating a failure styling.
     * @param String $target_link Optional, hyperlink to redirect the user to,
     * defaults to `$config['application-root']`.
     * 
     * @author Paul W. Lane
     */
    public function newEndUserDialog ($message, $success = DIALOG_FAILURE, $target_link = null) {
        $target_link = !is_null($target_link) ? $target_link : $this->config['application-root'];
        $view = $this->getViewer('User Dialog');
        echo "<div class='col-md-3'></div>\n";
        echo "<div class='col-xs-12 col-md-6'>\n";
        echo "<div class='well well-lg'>\n";
        echo "<h2>";
        if ($success) echo 'Success';
        else echo 'Failure';
        echo "!</h2>\n";
        echo "<p class='";
        if ($success) echo 'bg-success';
        else echo 'bg-danger';
        echo "'>{$message}</p>\n";
        echo "<button type='button' class='btn ";
        if ($success) echo "btn-success";
        else echo "btn-danger";
        echo "' onclick='window.open(\"{$target_link}\",\"_self\")'>Close</button>\n";
        echo "</div></div><div class='col-md-3'></div>\n";
        $view->footer();
        die();
    }

    /**
     * Produces a processing dialog for the user while a background handler is called.
     * 
     * This method produces a feedback dialog while the given handler is initiated to 
     * process users request. It essentially lets your handler run in the background 
     * preventing the users from clicky, clicky while their request is processed by the 
     * handler. The given handler must return `boolean true` upon successful completion.
     * @return Void The process ends with either a successful dialog or failure dialog,
     * depending on the return of the handler. The user will return to the API given in 
     * the `$click` parameter or the main API URL by default.
     * @param Mixed $handler The handler given should be in the form as would be given to 
     * the `call_user_func_array` ({@link http://php.net/manual/en/function.call-user-func-array.php}).
     * @param Array $args The arguments to the handler in the same form as given to 
     * `call_user_func_array` ({@link http://php.net/manual/en/function.call-user-func-array.php}).
     * @param String $click The URL of the API to give the user once the end dialog is reached.
     * @param String $feedback Optional, The feedback to display to the user during the dialog.
     * @author Paul W. lane
     */
    public function processingDialog ($handler = null, Array $args = null, $click = null, $feedback = null) {
        $dialog = function($feedback){
            $feedback = is_null($feedback) ? 'Processing' : $feedback;
            $view = $this->getViewer('Processing...');
            echo "<div class='row'><div class='col-md-3'></div>\n";
            echo "<div class='col-md-6 col-xs-12 vertical-center'><h3>{$feedback}...</h3><div class='progress'>\n";
            echo "<div class='progress-bar progress-bar-striped active' style='width:100%;'>Please Wait...";
            echo "</div></div></div><div class='col-md-3'></div></div>\n";
            echo "<script>
            setTimeout(function(){
                window.open('{$view->PageData['approot']}{$_SESSION['bg_process']['pid']}','_self');
            },3000);";
            echo "</script>\n";
            $view->footer();
        };

        $bg_process = function($handler, $args) {
            header('Connection: close');
            header('Content-length: '.ob_get_length());
            ob_flush();
            flush();
            if (call_user_func_array($handler, $args)) {
                $_SESSION['bg_process']['status'] = 'success';
            }
            else {
                $_SESSION['bg_process']['status'] = 'failed';
            }
        };

        if (empty($_SESSION['bg_process']['pid'])) {
            $_SESSION['bg_process']['pid'] = '/var/'.uniqid('process_id_');
            $_SESSION['bg_process']['redirect'] = is_null($click) ? $this->config['application-root'] : $click;
            $_SESSION['bg_process']['time_out'] = 4;

            $pid = fopen(INCLUDE_ROOT.$_SESSION['bg_process']['pid'].'.php','w');
            fwrite($pid,"<?php require_once(dirname(__DIR__).'/lib/init.php');\$server->processingDialog();");
            fclose($pid);

            $dialog($feedback);
            register_shutdown_function($bg_process,$handler,$args);
        }
        elseif (empty($_SESSION['bg_process']['status'])) {
            if ($_SESSION['bg_process']['time_out'] != 0) {
                $_SESSION['bg_process']['time_out']--;
                $dialog($feedback);
            }
            else {
                $_SESSION['bg_process']['status'] = 'timeout';
                $dialog($feedback);
            }
        }
        elseif (!empty($_SESSION['bg_process']['status'])) {
            switch($_SESSION['bg_process']['status']) {
                case 'error':
                    $url = $_SESSION['bg_process']['redirect'];
                    unlink(INCLUDE_ROOT.$_SESSION['bg_process']['pid'].'.php');
                    unset($_SESSION['bg_process']);
                    $this->newEndUserDialog("There was an error processing the request.",DIALOG_FAILURE,$url);
                break;
                case 'failed':
                    $url = $_SESSION['bg_process']['redirect'];
                    unlink(INCLUDE_ROOT.$_SESSION['bg_process']['pid'].'.php');
                    unset($_SESSION['bg_process']);
                    $this->newEndUserDialog("Something went wrong with the request.",DIALOG_FAILURE,$url);
                break;
                case 'success':
                    $url = $_SESSION['bg_process']['redirect'];
                    unlink(INCLUDE_ROOT.$_SESSION['bg_process']['pid'].'.php');
                    unset($_SESSION['bg_process']);
                    $this->newEndUserDialog("Request succeeded.",DIALOG_SUCCESS,$url);
                break;
                case 'timeout':
                    $url = $_SESSION['bg_process']['redirect'];
                    unlink(INCLUDE_ROOT.$_SESSION['bg_process']['pid'].'.php');
                    unset($_SESSION['bg_process']);
                    $this->newEndUserDialog("The operation encountered an error and has timed out.",DIALOG_FAILURE,$url);
                break;
                default:
                    $url = $_SESSION['bg_process']['redirect'];
                    unlink(INCLUDE_ROOT.$_SESSION['bg_process']['pid'].'.php');
                    unset($_SESSOIN['bg_process']);
                    $this->newEndUserDialog("An Unkown Error Occurred.",DIALOG_FAILURE,$url);
                break;
            }            
        }
        die();
     }

    /**
     * Determines if the current user is valid and logged in.
     * 
     * This method is used in controllers to determine whether the current
     * user is logged in and that the log in is valid. It returns true if the 
     * user is logged in and valid or outputs the `notAuthorized` interface
     * and returns false otherwise. This is a must have method. This method
     * can not return false as execution is halted if it fails.
     * 
     * @return Mixed Boolean|Void
     * @see Instance::notAuthorized()
     * @author Paul W. lane
     */
    public function mustBeValidUser () {
        if (is_null($this->security->user)) {
            $this->notAuthorized();
        }
        else {
            return true;
        }
    }

    /**
     * Checks whether or not the user has a specific permission.
     * 
     * The `Security` model is queried and a boolean is returned 
     * to indicate whether of not the current user has the given
     * permission. The method outputs the `notAuthorized()` interface
     * to the stream on failure. This is a must have method. It can not
     * return false as execution is halted if it fails.
     * 
     * @param String $perm The permission to check against the current user
     * @return Mixed Boolean|Void
     * @see Instance::notAuthorized()
     */
    public function userMustHavePermission ($perm) {
        if ($this->checkPermission('adminAll')) {
            $_SESSION['controller-security']['required'] = $perm;
            return true;
        }
        elseif ($this->checkPermission($perm)) {
            return true;
        }
        else {
            $this->notAuthorized();
         }
    }

    /**
     * Checks whether the current user has the given role.
     * 
     * The `Security` model is queried as to whether the current user 
     * has the specified role. Returns true if the user has the 
     * specified role or the user has the role of 'Administrator'.
     * Otherwise returns false and outputs the `notAuthorized()` interface
     * to the stream. This is a must have method. It can not return false
     * as execution is halted if it fails.
     * 
     * @param String $role The role to check the current user against
     * @return Mixed Boolean|Void
     * @see Instance::notAuthorized()
     * @author Paul W. Lane
     */
    public function userMustHaveRole ($role) {
        if ($this->security->userHasRole('Administrator')) {
            $_SESSION['controller-security']['required'] = $role;
            return true;
        }
        elseif ($this->security->userHasRole($role)) {
            return true;
        }
        else {
            $this->notAuthorized();
        }
    }

    /**
     * Determines if the current user has a role.
     * 
     * If the current user has the given role or has the role
     * of Adminstrator; returns true, otherwise false. 
     * This is a shortcut method to the Security model.
     * @param String $role The role to check if the users has.
     * @return Boolean True if user has the role, false otherwise.
     * @see Security::userHasRole()
     * @author Paul W. Lane
     */
    public function checkRole ($role) {
        try {
            if (is_null($this->security->secureUserID)) {
                return false;
            }
            elseif ($this->security->userHasRole('Administrator')) {
                if (isset($_SESSION['controller-security']['roles'])) array_push($_SESSION['controller-security']['roles'],$role);
                else $_SESSION['controller-security']['roles'][0] = $role;
                return true;
            }
            else {
                return $this->security->userHasRole($role);
            }
            return false;
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * Checks whether the current user has the given permission
     * 
     * Check whether or not the current user has the given permission
     * and returns true on success, or false on failure. Unlike the 
     * must have methods, this method does not terminate execution.
     * 
     * @param String $permission The permission to check the user for
     * @return Boolean
     * @author Paul W. Lane
     */
    public function checkPermission ($permission) {
        if (is_null($this->security->user)) {
           return false;
        }
        elseif ($this->security->userHasPermission('adminAll')) {
            if (isset($_SESSION['controller-security']['permissions'])) array_push($_SESSION['controller-security']['permissions'],$permission);
            else $_SESSION['controller-security']['permissions'][0] = $permission;
            return true;
        }
        elseif ($this->security->userHasPermission($permission)) {
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * Checks a given array of strings against the current users permissions
     * 
     * Given an unindexed array of permission strings, with the optional `$and`
     * parameter set to true then *all* permissions in the given array must be in the
     * users permission set. Otherwise, will only succeed if *any* of the permissions
     * are in the users permission set.
     * 
     * @param Array $perms The unindexed array of permissions to check
     * @param Boolean $and If set to true requires all given permissions in the array be present in the 
     * users permission set.
     * @return Boolean
     * @author Paul W. Lane
     * 
     */
    public function checkPermsArray (Array $perms, $and = false) {
        $aggregate = 0;
        foreach ($perms as $permission) {
            if ($this->checkPermission($permission)) {
                $aggregate++;
            }
        }
        if ($and) {
            if (count($perms) == $aggregate) {
                return true;
            }
            else {
                return false;
            }
        }
        elseif ($aggregate >= 1) {
            return true;
        }
        else {
            return false;
        }
        return false;
    }

    /**
     * Stops script execution and outputs a not authorized interface to the stream.
     * 
     * This method stops current script execution after ouputing an interface to the 
     * stream styled as not authorized.
     * 
     * @return Void
     * @author Paul W. Lane 
     */
    public function notAuthorized () {
        $buff = ob_get_contents();
        ob_clean();
        $this->loginRedirectHere();
        $this->redirect('/user/login');
        /*
        $view = $this->getViewer("Not Authorized!");
        ?>
        <!--<div class="row">
            <div class='col-md-3'></div>
            <div class="col-xs-12 col-md-6">
                <img src='<?php echo $view->PageData['wwwroot'];?>/images/403.jpg' class='img-responsive' />
            </div>
            <div class='col-md-3'></div>
        </div>-->

        <?php
        $view->footer();
        die();
        */
    }

    /**
     * Outputs a 404 error to the stream.
     * 
     * Method outputs a 404 error styled interface to the stream and 
     * stops script execution.
     * 
     * @return Void
     * @author Paul W. Lane
     */
    public function pageNotFound () {
        $buff = ob_get_contents();
        ob_clean();
        $view = $this->getViewer('NOT FOUND');
        ?>
        <div class='row' style='background-color:#ccb999;'>
            <div class='col-xs-12 center-text'>
                <img class='img-responsive' src='<?php echo $view->PageData['wwwroot'];?>/images/404.jpg' />
            </div>
        </div>
        <?php
        $view->footer();
        die();
    }

    /**
     * Represents a sane way to redirect to other links.
     * 
     * This method is used to redirect to other links in a sane manner,
     * with little code. It must be called prior to any other output
     * to the stream. Given a relative path link it uses the `$config['application-root']`
     * configuration variable as preamble to the `$link` parameter. Otherwise setting the 
     * optional paramter of `$offsite` to `true` will cause the link given to be untouched.
     * 
     * @param String $link The link to redirect to.
     * @param Boolean $offsite Whether or not the `$link` should be preambled with the `$config['application-root']` variable.
     * 
     * @return Void
     * @author Paul W. Lane
     */
    public function redirect ($link, $offsite = false) {
        $buff = ob_get_contents();
        ob_clean();
        if ($offsite) {
            header('Location: '.$link);
        }
        elseif (empty($this->config['application-root'])) {
            header('Location: /'.$link);
        }
        else {
            header('Location: '.$this->config['application-root'].$link);
        }
        die();
    }

    /**
     * I have no idea what this is for
     * 
     * This examplifies the reasons I started this documentation project.
     * I am leaving it in place until I can not find where I used it. I believe
     * it is obsoleted by something else.
     * 
     * @author Paul W. Lane
     * @deprecated
     */
    public function loginRedirectHere ($url = null) {
        if (is_null($url)) {
            $replace = explode('/',$this->config['application-root']);
            array_push($replace,'//');
            $uri = str_replace($replace,'',$_SERVER['REQUEST_URI']);
        }
        else {
            $uri = $url;
        }
        $_SESSION['login-redirect'] = $uri;
        return true;
    }

}
