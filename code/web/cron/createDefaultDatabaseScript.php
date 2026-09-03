<?php
$serverNameProvidedAsArgument = empty($_SERVER['aspen_server']) && !empty($argv[1]);
if ($serverNameProvidedAsArgument) {
	$_SERVER['aspen_server'] = $argv[1];
}
require_once __DIR__ . '/../bootstrap.php';

set_time_limit(0);
global $configArray;
global $aspen_db;

require_once ROOT_DIR . '/sys/DBMaintenance/DefaultDatabaseExporter.php';

$localDirectory = $configArray['Site']['local'];
$installDirectory = $localDirectory . '/../../install/';

$exporter = new DefaultDatabaseExporter($aspen_db);
$exporter->exportToFile($installDirectory . 'aspen.sql');
