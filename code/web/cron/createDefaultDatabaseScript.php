<?php
$firstArgumentIsOption = !empty($argv[1]) && str_starts_with($argv[1], '--');
$serverNameProvidedAsArgument = empty($_SERVER['aspen_server']) && !empty($argv[1]) && !$firstArgumentIsOption;
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

function getOptionValue(array $argv, string $option): string {
	foreach ($argv as $arg) {
		$isRequestedOption = str_starts_with($arg, "$option=");
		if ($isRequestedOption) {
			return substr($arg, strlen("$option="));
		}
	}
	return '';
}

$exporter = new DefaultDatabaseExporter($aspen_db);

$exportSeedDataFile = getOptionValue($argv, '--export-seed-data');
$exportSeedDataRequested = !empty($exportSeedDataFile);
if ($exportSeedDataRequested) {
	$exporter->exportSeedDataToFile($exportSeedDataFile);
	return;
}

$seedDataFile = getOptionValue($argv, '--seed-data');
$exporter->exportToFile($installDirectory . 'aspen.sql', $seedDataFile);
