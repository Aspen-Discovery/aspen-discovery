<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
 
if (!isset($argv[1])) {
    die('No delete file specified');
}

require_once 'util.inc.php';        // set up util environment
require_once 'File/MARC.php';
require_once 'sys/Solr.php';

// Read Config file
$configArray = parse_ini_file('../web/conf/config.ini', true);

// Setup Solr Connection
$url = $configArray['Index']['url'];
$solr = new Solr($url);
if ($configArray['System']['debugSolr']) {
    $solr->debug = true;
}

// Parse delete.mrc file
$collection = new File_MARC($argv[1]);

// Iterate through the retrieved records
$i = 0;
while ($record = $collection->next()) {
    $idField = $record->getField('001');
    $id = $idField->getData();
    $solr->deleteRecord($id);
    $i++;
}

// Commit and Optimize
if ($i) {
    $solr->commit();
    $solr->optimize();
}
?>