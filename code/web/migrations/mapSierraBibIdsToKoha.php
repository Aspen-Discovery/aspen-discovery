<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

/**
 * This will convert unique bibiIds from Sierra to Koha
 */
global $serverName;

set_time_limit(-1);

$dataPath = '/data/aspen-discovery/' . $serverName;
$exportPath = $dataPath . '/migration/';

if (!file_exists($exportPath)) {
	echo("Could not find migration path " . $exportPath . "\n");
} else {
	$bibMap = $exportPath . 'bib_map.csv';
	if (!file_exists($bibMap)) {
		echo("Could not find bib id map " . $bibMap);
	} else {
		//Set the username field for all rows to old- value
		global $aspen_db;

		$bibIdMapFHnd = fopen($bibMap, 'r');
		$rowsProcessed = 0;
		while ($bibMapRow = fgetcsv($bibIdMapFHnd)) {
			if (count($bibMapRow) == 2) {
				$oldValue = $bibMapRow[0];
				$newValue = $bibMapRow[1];
				$aspen_db->query("UPDATE ils_record_usage set recordId = $newValue where recordId = '{$oldValue}'");
				$aspen_db->query("UPDATE record_files set identifier = $newValue where identifier = '{$oldValue}'");
				$aspen_db->query("UPDATE user_reading_history_work set sourceId = $newValue where sourceId = '{$oldValue}'");
				$rowsProcessed++;
			}
		}
		echo("Processed $rowsProcessed patrons");

		fclose($bibIdMapFHnd);
	}
}