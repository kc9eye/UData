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
require_once(dirname(__DIR__).'/lib/init.php');
include('submenu.php');

$server->userMustHavePermission('editDiscrepancy');

if (!empty($_REQUEST['action'])) {
    switch($_REQUEST['action']) {
        case 'update':
            $server->processingDialog(
                'amendDiscrepancy',
                [],
                $server->config['application-root'].'/material/viewdiscrepancy?id='.$_REQUEST['id']
            );
        break;
        default:
            amendDisplay();
        break;
    }
}
else {
    amendDisplay();
}

function amendDisplay () {
    global $server;
    $pageOptions = [
        'headinserts'=>[
            "<script src='{$server->config['application-root']}/third-party/tinymce/tinymce.min.js'></script>"
        ]
    ];
    $dis = new MaterialDiscrepancy($server->pdo,$_REQUEST['id']);
    $view = $server->getViewer("Material: Amend Discrepancy",$pageOptions);
    $form = new InlineFormWidgets($view->PageData['wwwroot'].'/scripts');
    $view->responsiveTableStart([],true);
    echo "<tr><th>ID:</th><td>{$dis->id}</td></tr>\n";
    echo "<tr><th>Type:</th><td>{$dis->type}</td></tr>\n";
    echo "<tr><th>Date:</th><td>".$view->formatUserTimestamp($dis->date,true)."</td></tr>\n";
    echo "<tr><th>Author:</th><td>{$dis->author}</td></tr>\n";
    echo "<tr><th>Product:</th><td>{$dis->product}</td></tr>\n";
    echo "<tr><th>Number:</th><td>{$dis->number}</td></tr>\n";
    echo "<tr><th>Description:</th><td>{$dis->description}</td></tr>\n";
    echo "<tr><th>Discrepancy:</th><td>{$dis->discrepancy}</td></tr>\n";
    echo "<tr><td colspan='2'>";
    $form->newInlineForm();
    $form->hiddenInput('id', $_REQUEST['id']);
    $form->hiddenInput('uid', $server->currentUserID);
    $form->hiddenInput('action','update');
    $label = !empty($dis->a_uid) ? "Last Update By: {$dis->a_uid}" : "Notes";
    $form->inlineTextArea('amendum',$label ,$dis->notes,true);
    $view->br();
    $form->inlineSubmit('Update',null,$server->config['application-root'].'/material/viewdiscrepancy?id='.$_REQUEST['id']);
    $form->endInlineForm();
    echo "</td></tr>\n";
    echo "<tr><td colspan='2'>";
    switch(pathinfo($server->config['data-root'].'/'.$dis->file,PATHINFO_EXTENSION)) {
        case 'gif':
            $view->responsiveImage("{$server->config['application-root']}/data/files?dis=inline&file={$dis->file}");
        break;
        case 'jpg':
            $view->responsiveImage("{$server->config['application-root']}/data/files?dis=inline&file={$dis->file}");
        break;
        case 'png':
            $view->responsiveImage("{$server->config['application-root']}/data/files?dis=inline&file={$dis->file}");
        break;
        default:
            $view->linkButton('/data/files?dis=inline&file='.$dis->file,'Download File','info');
        break;
    }
    echo "</td></tr>\n";
    $view->responsiveTableClose(true);
    echo "<script>
    $(document).ready(function(){
        tinymce.init({
            selector: 'textarea',
            plugins: 'autoresize'
        });
    });
    </script>\n";
    $view->footer();
}

function amendDiscrepancy () {
    global $server;
    $materials = new Materials($server->pdo);
    $notify = new Notification($server->pdo,$server->mailer);
    $body = $server->mailer->wrapInTemplate(
        "updateddiscrepancy.html",
        "<a href='{$server->config['application-root']}/material/viewdiscrepancy?action=view&id={$_REQUEST['id']}'>Updated Material Discrepancy</a>"
    );
    if ($materials->amendDiscrepancy($_REQUEST)) {
        $notify->notify('Updated Discrepancy','Discrepancy Updated',$body);
        return true;
    }
    else return false;
}