<?php
/** @noinspection SqlResolve */
/** @noinspection SqlDialectInspection */

/**
 * Remove Aspen patron records from a list of ILS patron IDs supplied by the ILS.
 *
 * This is intended to be a general-purpose mechanism; as long as the ILS driver
 * sets user.unique_ils_id and the ILS is capable of producing a report of the
 * IDs of patrons that should be removed from Aspen, it should work.
 *
 * The typical use would be removing Aspen's user records for patrons that have
 * been deleted from the ILS.
 *
 * The input file is /data/aspen-discovery/{sitename}/ils/deleted_patrons.csv
 *
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';
require_once ROOT_DIR . '/sys/Account/User.php';

$cronLogEntry = new CronLogEntry();
$cronLogEntry->startTime = time();
$cronLogEntry->name = 'Purge Deleted ILS Users';
$cronLogEntry->insert();

global $configArray;
global $serverName;
global $aspen_db;
global $logger;

set_time_limit(0);

$numProcessed = 0;
$numDeleted = 0;
$numErrors = 0;

$dataPath = '/data/aspen-discovery/' . $serverName . '/ils/deleted_patrons.csv';
if (file_exists($dataPath)) {
	$cronLogEntry->notes .= "<br/>Processing input file $dataPath";
	$cronLogEntry->update();
	$handle = fopen($dataPath, 'r');
	while ($patronIdRow = fgetcsv($handle)) {
		$patronIlsId = $patronIdRow[0];
		if (!empty($patronIlsId)) {
			# we'll just ignore empty rows
			$numProcessed++;
			handleDeletion($patronIlsId, $cronLogEntry, $numDeleted, $numErrors);
		}
	}
	fclose($handle);
	unlink($dataPath);
} else {
	$cronLogEntry->notes .= "<br/>No input file $dataPath; nothing to do";
	$cronLogEntry->update();
}

$cronLogEntry->notes .= "<br/>Finished purging deleted ILS users. Processed $numProcessed lines, deleting $numDeleted users with $numErrors errors.";
$cronLogEntry->endTime = time();
$cronLogEntry->update();

function handleDeletion($patronIlsId, $cronLogEntry, &$numDeleted, &$numErrors) {
	$user = new User();
	$user->source = 'ils';
	$user->unique_ils_id = $patronIlsId;
	if ($user->find(true)) {
		if ($user->delete(false, true)) {
			$numDeleted++;
		} else {
			$cronLogEntry->numErrors++;
			$cronLogEntry->notes .= "<br/>Could not delete patron with ILS ID $patronIlsId";
			$cronLogEntry->update();
		}
	} else {
		$numErrors++;
		$cronLogEntry->numErrors++;
		$cronLogEntry->notes .= "<br/>Did not find user for ILS ID $patronIlsId";
		$cronLogEntry->update();
	}
}