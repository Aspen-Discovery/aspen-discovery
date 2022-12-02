<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

/**
 * This will load user data from a Pika system
 */
global $serverName;

ini_set('memory_limit', '4G');

cleanupPikaReadingHistory($serverName);

function cleanupPikaReadingHistory($serverName) {
	global $aspen_db;

	echo("Starting to cleanup reading history\n");
	ob_flush();
	set_time_limit(1800);
	$startTime = time();

	//Based on the source and source id, load the correct grouped work id

	require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
	$userReadingHistoryEntry = new ReadingHistoryEntry();
	$userReadingHistoryEntry->selectAdd();
	$userReadingHistoryEntry->selectAdd('COUNT(id) as numEntries');
	$userReadingHistoryEntry->selectAdd('groupedWorkPermanentId');
	$userReadingHistoryEntry->selectAdd('source');
	$userReadingHistoryEntry->selectAdd('sourceId');
	$userReadingHistoryEntry->groupBy('groupedWorkPermanentId, source, sourceId');
	$userReadingHistoryEntry->find();
	echo("There are {$userReadingHistoryEntry->getNumResults()} unique combinations of grouped work id, source, and sourceId\n");
	ob_flush();
	$numCorrected = 0;
	$numProcessed = 0;
	$numSkipped = 0;
	$numAlreadyCorrect = 0;

	$validGroupedWorks = [];
	$invalidGroupedWorks = [];
	$movedGroupedWorks = [];

	$batchStartTime = time();
	while ($userReadingHistoryEntry->fetch()) {
		$numProcessed++;
		$groupedWorkId = $userReadingHistoryEntry->groupedWorkPermanentId;
		if (!validateGroupedWork($groupedWorkId, $validGroupedWorks, $invalidGroupedWorks, $movedGroupedWorks, $userReadingHistoryEntry->source . ':' . $userReadingHistoryEntry->sourceId, $serverName)) {
			$numSkipped++;
		} else {
			$newGroupedWorkId = getGroupedWorkId($groupedWorkId, $validGroupedWorks, $movedGroupedWorks);
			if ($groupedWorkId != $newGroupedWorkId) {
				$numUpdatesThisTime = $aspen_db->exec("UPDATE user_reading_history_work set groupedWorkPermanentId = '$newGroupedWorkId' WHERE groupedWorkPermanentId='$groupedWorkId' AND source = '{$userReadingHistoryEntry->source}' AND sourceId = '{$userReadingHistoryEntry->sourceId}'");
				if ($numUpdatesThisTime != false) {
					$numCorrected += $numUpdatesThisTime;
				}
			} else {
				/** @noinspection PhpUndefinedFieldInspection */
				$numAlreadyCorrect += $userReadingHistoryEntry->numEntries;
			}
		}
		if ($numProcessed % 2500 == 0) {
			gc_collect_cycles();
			$elapsedTime = time() - $batchStartTime;
			$batchStartTime = time();
			$totalElapsedTime = ceil((time() - $startTime) / 60);
			echo("Processed $numProcessed Reading History Entries in $elapsedTime seconds ($totalElapsedTime minutes total).\n");
			ob_flush();
			set_time_limit(600);
		}
	}
	$elapsedTime = time() - $batchStartTime;
	$totalElapsedTime = ceil((time() - $startTime) / 60);
	echo("Processed $numProcessed Reading History Entries in $elapsedTime seconds ($totalElapsedTime minutes total).\n");
	echo("$numCorrected entries were corrected\n");
	echo("$numSkipped entries were skipped\n");
	echo("$numAlreadyCorrect entries were already correct\n");
	ob_flush();
}

function validateGroupedWork($groupedWorkId, &$validGroupedWorks, &$invalidGroupedWorks, &$movedGroupedWorks, $groupedWorkResources, $serverName) {
	require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
	require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';

	if (array_key_exists($groupedWorkId, $invalidGroupedWorks)) {
		$groupedWorkValid = false;
	} elseif (array_key_exists($groupedWorkId, $validGroupedWorks)) {
		$groupedWorkValid = true;
	} elseif (array_key_exists($groupedWorkId, $movedGroupedWorks)) {
		$groupedWorkValid = true;
	} else {
		//We haven't loaded this grouped work before, get it from the database
		//We can use two approaches, look at the resources tied to it or look at it by permanent id and title/author
		//First try looking by resource.  Do this first because the grouping has been tweaked and because this better
		//Handles works that have merged or unmerged
		$groupedWorkResourceArray = explode(",", $groupedWorkResources);
		$groupedWorkValid = false;

		foreach ($groupedWorkResourceArray as $identifier) {
			[
				$source,
				$id,
			] = explode(':', $identifier);
			if (strcasecmp($source, 'hoopla') === 0 && is_numeric($id)) {
				$groupedWorkResourceArray[] = $source . ':MWT' . $id;
			}
			if ($serverName == 'nashville.aspenlocal' || $serverName == 'nashville.production') {
				if ((strcasecmp($source, 'ils') === 0) && is_numeric($id)) {
					$newId = 'CARL' . str_pad($id, 10, '0', STR_PAD_LEFT);
					$groupedWorkResourceArray[] = $source . ':' . $newId;
				}
			}
		}
		foreach ($groupedWorkResourceArray as $identifier) {
			[
				$source,
				$id,
			] = explode(':', $identifier);
			$groupedWorkPrimaryIdentifier = new GroupedWorkPrimaryIdentifier();
			$groupedWorkPrimaryIdentifier->type = $source;
			$groupedWorkPrimaryIdentifier->identifier = $id;
			if ($groupedWorkPrimaryIdentifier->find(true)) {
				$groupedWork = new GroupedWork();
				$groupedWork->id = $groupedWorkPrimaryIdentifier->grouped_work_id;
				if ($groupedWork->find(true)) {
					$groupedWorkValid = true;
					if ($groupedWorkId == $groupedWork->permanent_id) {
						$validGroupedWorks[$groupedWorkId] = $groupedWorkId;
					} else {
						$movedGroupedWorks[$groupedWorkId] = $groupedWork->permanent_id;
					}
				}
				$groupedWork->__destruct();
				$groupedWork = null;
			}
			$groupedWorkPrimaryIdentifier->__destruct();
			$groupedWorkPrimaryIdentifier = null;
			if ($groupedWorkValid) {
				break;
			}
		}

		if (!$groupedWorkValid) {
			//There is little point searching for this record by title/author since we didn't find it by id.
			//There is some potential that the record was merged with another, but it wouldn't have shown in Pika
			//So we don't need to show it here.
			$groupedWorkValid = false;
			$invalidGroupedWorks[$groupedWorkId] = $groupedWorkId;
		}
	}
	return $groupedWorkValid;
}

function getGroupedWorkId($groupedWorkId, $validGroupedWorks, $movedGroupedWorks) {
	if (array_key_exists($groupedWorkId, $validGroupedWorks)) {
		return $validGroupedWorks[$groupedWorkId];
	} else {
		return $movedGroupedWorks[$groupedWorkId];
	}
}