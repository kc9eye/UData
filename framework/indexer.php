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
 * Indexer Class Framework
 * 
 * @package UData\Framework\UI\Bootstrap3
 * @author Paul W. Lane
 * @license GPLv2
 */
class Indexer extends ViewMaker {

    const EDIT_ACCESS_NAME = 'edit';

    private $rights;
    private $ls;

    public $access;
    public $dirName;

    public function __construct (Security $security, Array $config) {
        parent::__construct($security, $config);
        $this->rights = 0;
        $this->dirName = '';
        $this->ls['file'] = [];
        $this->ls['dir'] = [];
    }

    public function setDirectory ($dir) {
        try {
            $this->setAccessRights();
            $this->dirName .= $dir;
            $ls = scandir($this->config['data-root'].$this->dirName);
            foreach($ls as $obj) {
                if ($obj != '.' && $obj != '..') {
                    if (is_dir($this->config['data-root'].$this->dirName.'/'.$obj)) {
                        array_push($this->ls['dir'],$obj); 
                    }
                    elseif (is_file($this->config['data-root'].$this->dirName.'/'.$obj)) {
                        array_push($this->ls['file'], $obj);
                    }
                } 
            }
            return $dir;
        }
        catch (Exception $e) {
            trigger_error('Unable to set directory',E_USER_WARNING);
            return false; 
        }
    }

    private function setAccessRights () {
        if (!empty($this->access[self::EDIT_ACCESS_NAME])) {
            foreach($this->access[self::EDIT_ACCESS_NAME] as $perm) {
                if ($this->security->userHasPermission($perm)) {
                    $this->rights = 1;
                }
                elseif ($this->security->userHasRole($perm)) {
                    $this->rights = 1;
                }
            }
        }
    }

    public function getIndexView ($submenu = null) {
        $this->ViewData['pagetitle'] = $this->dirName;
        $this->header();
        if (!is_null($submenu)) {
            $this->sideDropDownMenu($submenu);
        }
        if ($this->rights >= 1) {
            $this->addEditControls();
        }
        echo "<div class='table-responsive'>\n";
        echo "<table class='table'>\n";
        echo "<tr><th>Name</th><th>&#160;</th><th>Modified Date</th></tr>\n";
        foreach($this->ls['dir'] as $obj) {
            echo "<tr><td><span class='glyphicon glypicon-folder-closed'></span>";
            $this->insertTab();
            echo "<a href='?d={$this->dirName}/{$obj}'>{$obj}</a></td>";
            if ($this->rights >= 1) {
                echo "<td><a href='?delete={$this->dirName}/{$obj}' class='btn btn-danger' role='button'><span class='glyphicon glyphicon-trash'></span></a></td>";
            }
            else { 
                echo "<td>&#160;</td>";
            }
            echo "<td>".date('r', filemtime($this->config['data-root'].'/'.$this->dirName.'/'.$obj))."</td></tr>\n";
        }
        foreach($this->ls['file'] as $obj) {
            echo "<tr><td><span class='glyphicon glyphicon-file'></span>";
            $this->insertTab();
            echo "<a href='?f={$this->dirName}/{$obj}'>{$obj}</a></td>";
            if ($this->rights >= 1) {

                echo "<td><a href='?delete={$this->dirName}/{$obj}' class='btn btn-danger' role='button'><span class='glyphicon glyphicon-trash'></span></a></td>";
            }
            else {
                echo "<td>&#160;</td>";
            }
            echo "<td>".date('r',filemtime($this->config['data-root'].'/'.$this->dirName.'/'.$obj))."</td></tr>\n";
        }
        echo "</table></div>";
        $this->addScrollTopBtn();
        $this->footer([
            'https://cdn.jsdelivr.net/npm/jquery-validation@1.17.0/dist/jquery.validate.min.js',
            'https://cdn.jsdelivr.net/npm/jquery-validation@1.17.0/dist/additional-methods.min.js',
            $this->PageData['wwwroot'].'/scripts/btstrapfileinputhack.js'
        ]);
    }

    protected function addEditControls () {
?>
<div class='row'>
    <div class='col-md-5 col-xs-12'>
        <strong>Create Folder:</strong>
        <form method='post'>
            <input type='hidden' name='directory' value='<?php echo $this->dirName;?>' />
            <div class='input-group'>
                <input id='dir_name' type='text' class='form-control' name='create' placeholder='Create Folder' required />
                <div class='input-group-btn'>
                    <button type='submit' class='btn btn-default'><span class='glyphicon glyphicon-plus-sign'></span></button>
                </div>
            </div>
        </form>
    </div>
    <div class='col-md-5 col-xs-12'>
        <strong>Upload Files:</strong>
        <form enctype='multipart/form-data' method='post'>
            <input type='hidden' name='directory' value='<?php echo $this->dirName;?>' />
            <div class='input-group'>
                <label class='input-group-btn'>
                    <span class='btn btn-info'>
                        Browse&hellip;
                        <input type='file' name='uploads[]' style='display:none;' multiple required/>
                    </span>
                </label>
                <input type="text" class="form-control" readonly>
                <div class='input-group-btn'>
                    <button type='submit' class='btn btn-default'><span class='glyphicon glyphicon-cloud-upload'></span></button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php
    }

    public function removeObj ($data) {
        return false;
    }

    public function createFolder ($data) {
        if ($this->access == 0) {
            $this->notAuthorized();
        }
        $dir = $this->$this->dirName.'/'.$data['dir_name'];

    }

    public function addFiles () {
        return false;
    }

    private function notAuthorized() {
        $buff = ob_get_contents();
        ob_clean();
        $this->PageData['pagetitle'] = 'Not Authorized';
        $this->header();
        ?>
        <div class="row">
            <div class='col-md-3'></div>
            <div class="col-xs-12 col-md-6">
                <img src='<?php echo $this->PageData['wwwroot'];?>/images/403.jpg' class='img-responsive' />
            </div>
            <div class='col-md-3'></div>
        </div>
        <?php
        $this->footer();
        die();
    }
}