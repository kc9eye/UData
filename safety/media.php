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
require_once(dirname(__DIR__).'/lib/init.php');
include('submenu.php');

$edit = $server->checkPermission('editVIDS') || $server->security->userHasRole('EHS');

$media = new SafetyMedia($server->pdo, $server->config['data-root']);

if (!empty($_REQUEST['action']) && $edit) {
    switch($_REQUEST['action']) {
        case 'add': $result = $media->addNewSafetyMedia($_REQUEST); break;
        case 'delete': $result = $media->deleteSafetyMedia($_REQUEST); break;
    }
    ($result &&
    $server->newEndUserDialog('Request completed successfully',DIALOG_SUCCESS, $server->config['application-root'].'/safety/media')) ||
    $server->newEndUserDialog('Something went wrong with the request',DIALOG_FAILURE,$server->config['application-root'].'/safety/media');
}

$view = $server->getViewer('Safety Media');
$view->sideDropDownMenu($submenu);
$view->h1('Safety Media');

if ($edit) {
    $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->hr();
    $view->bold('Add Media');
    $form->newInlineMultipartForm();
    $form->hiddenInput('action','add');
    $form->hiddenInput('uid', $server->security->secureUserID);
    $form->inlineInputCapture('title','Title',null,true);
    $form->inlineFileUpload(FileIndexer::UPLOAD_NAME,false, true);
    $form->inlineSubmit('Upload');
    $form->endInlineForm();
    $view->hr();
}

if (!empty($_REQUEST['view'])) {
    echo "<div class='embed-responsive embed-responsive-16by9'>\n";
    echo "<video controls='controls' autoplay autobuffer>\n";
    echo "<source src='{$view->PageData['approot']}/data/files?file={$_REQUEST['view']}' />\n";
    echo "Your browser does not support HTML5 video tags! Consider upgrading.\n";
    echo "</video>\n";
    echo "</div>\n";
}
else{
    echo "<div class='table-responsive'>\n";
    echo "<table class='table'>\n";
    echo "<tr><th>Title</th><th>Genre</th>";
    if ($edit) {echo "<th>Edit</th>";}
    echo "</tr>\n";
    foreach ($media->getList() as $row) {
        echo "<tr><td><a href='?view={$row['file']}'>{$row['name']}</a></td><td>{$row['genre']}</td>";
        if ($edit) {
            echo "<td><a href='?action=delete&ref={$row['id']}:{$row['fid']}' class='btn btn-danger' role='button'><span class='glyphicon glyphicon-trash'></span></a></td>";
        }
        echo "</tr>\n";
    }
    echo "</table>\n";
    echo "</div>\n";
}
$view->addScrollTopBtn();
$view->footer();