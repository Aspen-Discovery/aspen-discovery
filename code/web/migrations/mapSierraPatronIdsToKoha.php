<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

/**
 * This will convert unique ids from one system to another.
 * To avoid conflicts, it will first prepend old- to all the old ids and then map from
 * the old id to the new id.
 */
global $serverName;

set_time_limit(-1);

$dataPath = '/data/aspen-discovery/' . $serverName;
$exportPath = $dataPath . '/migration/';

if (!file_exists($exportPath)){
	echo("Could not find migration path " . $exportPath . "\n");
}else{
	$patronIdMap = $exportPath . 'patron_map.csv';
	if (!file_exists($patronIdMap)){
		echo("Could not find patron id map " . $patronIdMap);
	}else{
		//Set the username field for all rows to old- value
		global $aspen_db;
		$aspen_db->query("UPDATE user set username = CONCAT('old-', username) WHERE username NOT LIKE 'old-%'" );

		$patronIdMapFHnd = fopen($patronIdMap, 'r');
		$rowsProcessed = 0;
		while ($patronMapRow = fgetcsv($patronIdMapFHnd)) {
			if (count($patronMapRow) == 2) {
				//Remove p from the start
				$oldValue = str_replace('p', '', $patronMapRow[0]);
				//Remove the check digit
				$oldValue = substr($oldValue, 0, strlen($oldValue) - 1);
				$newValue = $patronMapRow[1];
				$aspen_db->query("UPDATE user set username = '$newValue' where username = 'old-{$oldValue}'" );
				$rowsProcessed++;
			}
		}
		echo ("Processed $rowsProcessed patrons");
		fclose($patronIdMapFHnd);

		//Remove the old- designation so things like aspen_admin still work
		$aspen_db->query("UPDATE user set username = replace(username, 'old-', '') WHERE username LIKE 'old-%'" );
	}
}