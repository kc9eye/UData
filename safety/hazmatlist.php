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

$view = $server->getViewer('Hazardous Mat. List');
$view->sideDropDownMenu($submenu);

$sds = new SDSHandler($server->pdo,$server->config['data-root']);
$content = $sds->getHazMatList();
?>
<div>
    <h1>Hazardous Materials Inventory List</h1>
    <p class='text-justify small'>
        If you have any questions about anything here, or if you have something
        that isn't on this list; <span class='bg-info'>Discuss it with a Supervisor.</span><br />
        There are currently <strong><?php echo $sds->getHazMatListCount()[0]['count'];?></strong> hazardous materials on inventory.
    </p>
    <div class='table-responsive'>
        <table class='table'>
            <tr><th>Material</th><th>Location</th></tr>
            <?php
                foreach($content as $row) {
                    echo "<tr><td>{$row['name']}</td><td>{$row['used']}</td></tr>\n";
                }
            ?>
        </table>
    </div>
</div>
<?php
$view->addScrollTopBtn();
$view->footer();