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

/**
 * Handles the uploading of discrepancies
 * 
 * This is a library file as it uses objects as 
 * opposed to being an object, however it is not a controller
 * @return Void 
 */
function handleDiscrepancy () {
    global $server;
    $materials = new Materials($server->pdo);
    if (!is_null(($file = errorCheckInput($materials)))) {
        $server->processingDialog('addNewPDN',[$materials,$file],$server->config['application-root'].'/material/viewdiscrepancy');
    }
    else {
        $_REQUEST['fid'] = 'NA';
        $server->processingDialog('addNewPDIH',[$materials],$server->config['application-root'].'/material/viewdiscrepancy');
    }          
}

function errorCheckInput ($materials) {
    global $server;
    if (!$materials->verifyMaterial($_REQUEST['number'])) nAN();
    if (!$materials->verifyOnBOM($_REQUEST['number'],$_REQUEST['prokey'])) notOnBOM();
    if ($_REQUEST['type'] == Materials::PDN_TYPE) {
        try {
            if (!($file = new FileUpload(FileIndexer::UPLOAD_NAME))) requiredPhoto();
            if ($file->multiple) noMultiplesDialog();
        }
        catch (UploadException $e) {
            trigger_error($e->getMessage(),E_USER_WARNING);
            requiredPhoto();
        }
    }
    elseif($_REQUEST['type'] == Materials::PDIH_TYPE) {
        $file = null;
    }
    return $file;
}

function addNewPDN ($materials,$file) {
    global $server;
    try {
        $indexer = new FileIndexer($server->pdo,$server->config['data-root']);
        if (($indexed = $indexer->indexFiles($file,$_REQUEST['uid'])) !== false) {
            $_REQUEST['fid'] = $indexed[0];
            if (!$materials->addDiscrepancy($_REQUEST)) {
                $indexer->removeIndexedFiles($indexer->getIndexByID($_REQUEST['fid']));
                throw new Exception("Failed to add new discrepancy, rolling back.");
            }
            else {
                $body = file_get_contents($server->config['template-root'].'/email/newdiscrepancy.html');
                $body .= "<a href='{$server->config['application-root']}/material/viewdiscrepancy?id={$materials->addedDiscrepancyID}'>New PDN Type</a>";
                $notify = new Notification($server->pdo,$server->mailer);
                $notify->notify('New PDN','New PDN Notification', $body);
                return true;
            }
        }
        else throw new Exception("Failed to index the PDN file.");
    }
    catch (Exception $e) {
        trigger_error($e->getMessage(),E_USER_WARNING);
        return false;
    }
}

function addNewPDIH ($materials) {
    global $server;
    try {
        $notify = new Notification($server->pdo,$server->mailer);
        $body = file_get_contents($server->config['template-root'].'/email/newdiscrepancy.html');
        if (!$materials->addDiscrepancy($_REQUEST)) throw new Exception("Failed ot add discrepancy");
        $body .= "<a href='{$server->config['application-root']}/material/viewdiscrepancy?id={$materials->addedDiscrepancyID}'>New PDIH Type</a>";
        $notify->notify('New PDIH','New PDIH Notification',$body);
        return true;
    }
    catch (Exception $e) {
        trigger_error($e->getMessage(),E_USER_WARNING);
        return false;
    }
}

function noMultiplesDialog () {
    global $server;
    $server->newEndUserDialog(
        "Only a single file maybe used per upload.",
        DIALOG_FAILURE,
        $server->config['application-root'].'/material/discrepancy?number='.$_REQUEST['number'].'&prokey='.$_REQUEST['prokey']
    );
}

function nAN () {
    global $server;
    $server->newEndUserDialog(
        "The material number: {$_REQUEST['number']}, was not found to exist.",
        DIALOG_FAILURE,
        $server->config['application-root'].'/material/discrepancy'
    );
}

function notOnBOM () {
    global $server;
    $product = new Product($server->pdo,$_REQUEST['prokey']);
    $server->newEndUserDialog(
        "The material number: {$_REQUEST['number']}, was not found on the {$p->pDescription} BOM.",
        DIALOG_FAILURE,
        $server->config['application-root'].'/material/discrepancy'
    );
}

function fileIndexFailed () {
    global $server;
    $server->newEndUserDialog(
        "The database failed to index the uploaded file.",
        DIALOG_FAILURE,
        $server->config['application-root'].'/material/discrepancy'
    );
}

function requiredPhoto () {
    global $server;
    $server->newEndUserDialog(
        "A discrepancy of type:".Materials::PDN_TYPE." requires a photo file accompany the discrepancy. There was an issue with the given file.",
        DIALOG_FAILURE,
        $server->config['application-root'].'/material/discrepancy?number='.$_REQUEST['number'].'&prokey='.$_REQUEST['prokey']
    );
}