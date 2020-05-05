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

function create_new_cell (Array $data) {
    global $server;
    $wc = new WorkCells($server->pdo);
    try {
        if (!$wc->addNewCell($data)) throw new Exception("Failed to create new work cell.");
        else $_SESSION['cell_transfer']['newcellid'] = $wc->newCellID;
        $_SESSION['cell_transfer']['create'] = true;
        return true;
    }
    catch (Exception $e) {
        trigger_error($e->getMessage(),E_USER_WARNING);
        $_SESSION['cell_transfer']['create'] = $e->getMessage();
        return false;
    }
}

function transfer_tooling (Array $data) {
    global $server;
    $wc = new WorkCells($server->pdo);

    $sql = 'SELECT * FROM cell_tooling WHERE cellid = ?';
    try {
        $pntr = $server->pdo->prepare($sql);
        if (!$pntr->execute([$data['request']['cellid']])) throw new Exception("Select failed: {$sql}");
        if (empty(($tools = $pntr->fetchAll(PDO::FETCH_ASSOC)))) {
            $_SESSION['cell_transfer']['tooling'] = "No tools found to transfer.";
            return false;
        }
        foreach($tools as $tool) {
            $insert = [
                'toolid'=>$tool['toolid'],
                'qty'=>$tool['qty'],
                'uid'=>$data['request']['uid'],
                'cellid'=>$data['newcellid']
            ];
            if (!$wc->addToolingToCell($insert)) throw new Exception("Insert tooling failed at: ".var_export($insert,true));
        }
        $_SESSION['cell_transfer']['tooling'] = true;
        return true;
    }
    catch (PDOException $e) {
        trigger_error($e->getMessage(),E_USER_WARNING);
        return false;
    }
    catch (Exception $e) {
        trigger_error($e->getMessage(),E_USER_WARNING);
        return false;
    }
}

function transfer_safety (Array $data) {
    global $server;
    $sql = 'SELECT * FROM documents WHERE name = ?';
    $wc = new WorkCells($server->pdo);
    try {
        $pntr = $server->pdo->prepare($sql);
        if (!$pntr->execute([$data['request']['cellid']])) throw new Exception(print_r($pntr->errorInfo(),true));
        elseif (empty(($safety = $pntr->fetchAll(PDO::FETCH_ASSOC)[0]))) {
            $_SESSION['cell_transfer']['safety'] = "No safety data found to transfer.";
            return false;
        }
        $insert = [
            'uid'=>$data['request']['uid'],
            'name'=>$data['newcellid'],
            'body'=>$safety['body'],
            'url'=>$data['request']['safetyreviewurl'],
            'cellname'=>$data['request']['cell_name']
        ];
        if (!$wc->seekSafetyApproval($insert,$server->mailer)) throw new Exception("Failed to seek safety approval.");
        $_SESSION['cell_transfer']['safety'] = true;
        return true;
    }
    catch (PDOException $e) {
        trigger_error($e->getMessage(),E_USER_WARNING);
        return false;
    }
    catch (Exception $e) {
        trigger_error($e->getMessage(),E_USER_WARNING);
        return false;
    }
}

function transfer_material (Array $data) {
    global $server;
    $wc = new WorkCells($server->pdo);
    $materials = new Materials($server->pdo);

    $sql = '
            SELECT 
            (SELECT number FROM material WHERE id = (
                SELECT partid FROM bom WHERE id = a.bomid
                )
            ) as number,
            qty
        FROM cell_material as a
        WHERE cellid = ?
    ';
    try {
        $pntr = $server->pdo->prepare($sql);
        if (!$pntr->execute([$data['request']['cellid']])) throw new Exception("Material select failed, due to a database issue.");
        elseif (empty(($mats = $pntr->fetchAll(PDO::FETCH_ASSOC)))) {
            $_SESSION['cell_transfer']['material'] = "No material found to transfer.";
            return false;
        }
        unset($pntr);
        unset($sql);
    }
    catch (PDOException $e) {
        trigger_error($e->getMessage(),E_USER_WARNING);
        return false;
    }
    catch (Exception $e) {
        trigger_error($e->getMessage(),E_USER_WARNING);
        return false;
    }

    try {
        $faults = array();
        foreach($mats as $part) {
            $insert = [
                'label'=>"",
                'number'=>$part['number'],
                'qty'=>$part['qty'],
                'cellid'=>$data['newcellid'],
                'uid'=>$data['request']['uid'],
                'prokey'=>$data['request']['prokey']
            ];
            if (!$materials->verifyOnBOM($part['number'],$data['request']['prokey'])) {
                array_push($faults,[$part['number'],$part['qty'],'Not found on BOM.']);
                continue;
            }
            elseif (!$materials->verifyBOMQty($part['number'],$data['request']['prokey'],$part['qty'])) {
                array_push($faults,[$part['number'],$part['qty'],'BOM quantity exceeded.']);
                continue;
            }
            elseif (!$materials->addCellMaterial($insert)) 
                throw new Exception("Failed to add material to cell, can't continue.");
        }

        if (!empty($faults)) {
            $_SESSION['cell_transfer']['discrepancies'] = $faults;
        }
        $_SESSION['cell_transfer']['material'] = true;
        return true;
    }
    catch (PDOException $e) {
        trigger_error($e->getMessage(),E_USER_WARNING);
        return false;
    }
    catch (Exception $e) {
        trigger_error($e->getMessage(),E_USER_WARNING);
        return false;
    }
}

function abort_transfer (Array $data) {
    global $server;    
    try {
        $sql = 'DELETE FROM cell_tooling WHERE cellid = ?';
        $pntr = $server->pdo->prepare($sql);
        if (!$pntr->execute([$data['newcellid']])) throw new Exception("Tooling rollback failed.");

        $sql = 'DELETE FROM documents WHERE name = ?';
        $pntr = $server->pdo->prepare($sql);
        if (!$pntr->execute([$data['newcellid']])) throw new Exception("Safety rollback failed.");

        $sql = 'DELETE FROM cell_material WHERE cellid = ?';
        $pntr = $server->pdo->prepare($sql);
        if (!$pntr->execute([$data['newcellid']])) throw new Exception("Material rollback failed.");

        $sql = 'DELETE FROM work_cell WHERE id = ?';
        $pntr = $server->pdo->prepare($sql);
        if (!$pntr->execute([$data['newcellid']])) throw new Exception("Workcell rollback failed.");

        unset($_SESSION['cell_transfer']);
        if (isset($_SESSION['multitransfer'])) unset($_SESSION['multitransfer']);

        return true;
    }
    catch (PDOException $e) {
        trigger_error($e->getMessage(),E_USER_WARNING);
        return false;
    }
    catch (Exception $e) {
        trigger_error($e->getMessage(),E_USER_WARNING);
        return false;
    }
}