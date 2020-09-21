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

$server->userMustHavePermission('maintenanceAccess');

$form_version_number = '1.0.1';
$form_version_date = '2018-08-21';
$form_name = 'Manlift-Inspection';

if (!empty($_REQUEST['action'])) {
    $herb = new Maintenance($server->pdo);
    switch ($_REQUEST['action']) {
        case 'submit':
            $server->processingDialog([$herb,'logInspection'],[$_REQUEST],$server->config['application-root'].'/maintenance/manlift?action=view');
        break;
        case 'view':
            $view = $server->getViewer('Man Lift Inspection');
            $view->sideDropDownMenu($submenu);
            echo "<a href='{$view->PageData['approot']}/maintenance/manlift' class='btn btn-info' role='button'>Inspection Form</a>";
            $view->h2('Inspections Report');
            echo "<div class='table-responsive'>
                <table class='table'>
                    <tr><th>Inspection Date</th><th>Inspector</th><th>Form</th><th>Form Version</th><th>Form Date</th></td>";
            foreach($herb->getInspections($form_name) as $row) {
                echo "<tr>\n";
                echo "<td>{$row['inspection_date']}</td>\n";
                echo "<td>{$row['inspector']}</td>\n";
                echo "<td>{$row['form_name']}</td>\n";
                echo "<td>{$row['form_version']}</td>\n";
                echo "<td>{$row['form_date']}</td>\n";
                echo "</tr>\n";
                if (!empty($row['comments'])) {
                    echo "<tr><th colspan='5'>Comments:</th></tr>\n";
                    echo "<tr><td colspan='5'>{$row['comments']}</td></tr>\n";
                }
            }
            echo "</table></div>\n";
            $view->footer();
            die();
        break;
    }
}
$view = $server->getViewer('Man Lift Inspection');
$view->sideDropDownMenu($submenu);
if ($server->checkPermission('editHerb')) {
    echo "<a href='?action=view' role='button' class='btn btn-info'>View Past Inspections</a>";
}
$form = new FormWidgets($view->PageData['wwwroot'].'/scripts');
$form->newForm('Man Lift Operators Inspection');
$view->hr();
$form->h2('Pre-operation Inspection');
$form->checkBox(
    'manuals', 
    ['Manuals','Be Sure that the operator\'s, safety and responsibilities manuals are complete, legible and in the storage container located on the platform'],
    '1',
    true
);
$form->checkBox(
    'decals',
    ['Decals','Be sure that all decals are legible and in place.'],
    '1',
    true
);
$form->checkBox(
    'hydralic',
    ['Hydralics','Check for hydraulic oil leaks and proper oil level. Add oil if needed.'],
    '1',
    true
);
$form->checkBox(
    'battery',
    ['Battery','Check for battery fluid leaks and proper fluid level. Add distilled water if needed.'],
    '1',
    true
);
$view->hr();
$form->h2('Check the following components or areas for damage, modifications and improperly installed or missing parts');
$form->checkBox(
    'electrical',
    ['Electrical','Electrical components, wiring and electrical cables'],
    '1',
    true
);
$form->checkBox(
    'power',
    ['Power','Hydraulic power unit, reservoir, hoses, fittings, cylinders and manifolds'],
    '1',
    true
);
$form->checkBox(
    'drive',
    ['Drives','Drive and turntable motors and torque hubs'],
    '1',
    true
);
$form->checkBox(
    'boom',
    ['Boom','Boom wear pads'],
    '1',
    true
);
$form->checkBox(
    'tires',
    ['Tires','Tires and wheels'],
    '1',
    true
);
$form->checkBox(
    'switchs',
    ['Switches','Limit switches, alarms and horn'],
    '1',
    true
);
$form->checkBox(
    'fasteners',
    ['Fasteners','Nuts, bolts, and other fasteners'],
    '1',
    true
);
$form->checkbox(
    'platform',
    ['Platform','Platform entry mid-rail/gate'],
    '1',
    true
);
$form->checkBox(
    'beacon',
    ['Beacon','Beacon and alarms (if equipped)'],
    '1',
    true
);
$view->hr();
$form->h2('Check Entire Machine for:');
$form->checkBox(
    'cracks',
    ['Cracks','Cracks in welds or structural components'],
    '1',
    true
);
$form->checkBox(
    'dents',
    ['Dents','Dents or damage to machine'],
    '1',
    true
);
$form->checkBox(
    'structure',
    ['Structure',
    'Be sure that all structural and other critical components
     are present and all associated fasteners and pins are in place
     and properly tightened'],
    '1',
    true
);
$form->checkBox(
    'packs',
    ['Packs','Be sure that both battery packs are in place, latched and properly connected.'],
    '1',
    true
);
$form->checkBox(
    'after',
    ['After','After you complete your inspection, be sure that all compartment covers are in place and latched'],
    '1',
    true
);
$form->hiddenInput('uid',$server->security->secureUserID);
$form->hiddenInput('form_version', $form_version_number);
$form->hiddenInput('form_date', $form_version_date);
$form->hiddenInput('form_name', $form_name);
$form->hiddenInput('comments','');
$form->hiddenInput('action','submit');
$form->submitForm('Affirm Inspection',false,$view->PageData['approot']);
$form->endForm();
$view->hr();
echo "<div class='row'>";
echo "<div class='col-md-4 col-xs-12'>";
echo "Form version:<strong>{$form_version_number}</strong>";
echo "</div>";
echo "<div class='col-md-4 col-xs-12'>";
echo "Form version date:<strong>{$form_version_date}</strong>";
echo "</div>";
echo "<div class='col-md-4 col-xs-12'>";
echo "Derived From: Genie Z-30/20N Manual: 43651 Page 11";
echo "</div></div>";
$view->hr();
$view->footer();