<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

/**
 * This will load user lists from Sirsi-Dynix Enterprise.
 * All bib ids should be in the active system
 */
global $serverName;

set_time_limit(-1);

ini_set('memory_limit', '4G');

$dataPath = '/data/aspen-discovery/' . $serverName;
$exportPath = $dataPath . '/symphony_export/';

if (!file_exists($exportPath)) {
	echo("Could not find export path " . $exportPath . "\n");
} else {
	$existingUsers = [];
	$missingUsers = [];
	$validRecords = []; // An array mapping the record id to the grouped work id
	$invalidRecords = []; //An array containing any records that no longer exist and therefore are not imported.

	$startTime = time();
	if (file_exists($exportPath . "lists.txt")) {
		importLists($startTime, $exportPath, $existingUsers, $missingUsers, $validRecords, $invalidRecords);
	}
}

function importLists($startTime, $exportPath, &$existingUsers, &$missingUsers, &$validRecords, &$invalidRecords) {
	echo("Starting to import staff lists\n");

	require_once ROOT_DIR . '/sys/UserLists/UserList.php';
	require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
	$listsfHnd = fopen($exportPath . "lists.txt", 'r');

	$numImports = 0;
	$batchStartTime = time();
	$existingLists = [];
	$removedLists = [];
	$usersWithSearchPermissions = [];

	//Skip the first row which is titles.
	fgetcsv($listsfHnd);
	while ($patronListRow = fgetcsv($listsfHnd, 0, ',')) {
		$numImports++;

		if (sizeof($patronListRow) != 10) {
			//We got a bad export, likely
			continue;
		}
		//Figure out the user for the list
		$patronName = $patronListRow[0];
		$userBarcode = $patronListRow[1];
		$userKey = $patronListRow[2];
		$listName = $patronListRow[3];
		$listId = $patronListRow[4];
		$myListOrder = $patronListRow[5];
		$originalTime = $patronListRow[6];
		$dateAddedToList = strtotime($originalTime);
		if ($dateAddedToList == false) {
			$firstDot = strpos($originalTime, '.');
			$secondDot = strpos($originalTime, '.', $firstDot + 1);
			$thirdDot = strpos($originalTime, '.', $secondDot + 1);
			$timeWithHoursAndMinutes = substr($originalTime, 0, $thirdDot);
			if (strpos($originalTime, 'AM')) {
				$timeWithHoursAndMinutes .= ' AM';
			} else {
				$timeWithHoursAndMinutes .= ' PM';
			}
			$timeWithHoursAndMinutes = str_replace('.', ':', $timeWithHoursAndMinutes);
			$dateAddedToList = strtotime($timeWithHoursAndMinutes);
		}
		$documentId = $patronListRow[7];
		$title = $patronListRow[8];
		$titleOrder = $patronListRow[9];

		//Figure out the user for the list
		$userId = getUserIdForBarcode($userBarcode, $existingUsers, $missingUsers, $usersWithSearchPermissions);
		if ($userId == -1) {
			$removedLists[$listId] = $listId;
			continue;
		}

		if (!array_key_exists($listId, $existingLists)) {
			$userList = new UserList();
			$userList->user_id = $userId;
			$userList->title = $listName;
			if ($userList->find(true)) {
				$existingLists[$listId] = $userList->id;
			} else {
				$userList = new UserList();
				$userList->user_id = $userId;
				$userList->created = $dateAddedToList;
				$userList->title = $listName;
				$userList->description = '';
				$userList->public = false;
				$userList->defaultSort = 'custom';
				$userList->importedFrom = 'Enterprise';
				$userList->searchable = false;
				$userList->insert();
				$existingLists[$listId] = $userList->id;
			}
			$userList->__destruct();
		}
		$userListId = $existingLists[$listId];

		//Get the grouped work id for the bib record
		$bibNumber = 'a' . str_replace('ent://SD_ILS/0/SD_ILS:', '', $documentId);
		$groupedWorkForRecordId = getGroupedWorkForRecordId($bibNumber, $validRecords, $invalidRecords);
		if ($groupedWorkForRecordId != null) {
			$listEntry = new UserListEntry();
			$listEntry->listId = $userListId;
			$listEntry->source = 'GroupedWork';
			$listEntry->sourceId = $groupedWorkForRecordId;
			if (!$listEntry->find(true)) {
				$listEntry->dateAdded = $dateAddedToList;
				$listEntry->notes = '';
				$listEntry->weight = $titleOrder + 1;
				$listEntry->importedFrom = "Enterprise";
				$listEntry->title = strlen($title) > 50 ? substr($title, 0, 50) : $title;
				$ret = $listEntry->insert(false);
				if ($ret != 1) {
					echo("Error adding list entry");
				}
			}
			$listEntry->__destruct();
		}

		if ($numImports % 2500 == 0) {
			gc_collect_cycles();
			ob_flush();
			usleep(10);
			$elapsedTime = time() - $batchStartTime;
			$batchStartTime = time();
			$totalElapsedTime = ceil((time() - $startTime) / 60);
			echo("Processed $numImports Lists in $elapsedTime seconds ($totalElapsedTime minutes total).\n");
		}
	}

	fclose($listsfHnd);
	$elapsedTime = time() - $batchStartTime;
	$totalElapsedTime = ceil((time() - $startTime) / 60);
	echo("Processed $numImports List Titles in $elapsedTime seconds ($totalElapsedTime minutes total).\n");
	echo("Removed " . count($removedLists) . " lists because the user is not valid\n");
}

function getGroupedWorkForRecordId($bibNumber, &$validRecords, &$invalidRecords): ?string {
	require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
	require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
	if (array_key_exists($bibNumber, $validRecords)) {
		return $validRecords[$bibNumber];
	} elseif (array_key_exists($bibNumber, $invalidRecords)) {
		return null;
	} else {
		$groupedWorkPrimaryIdentifier = new GroupedWorkPrimaryIdentifier();
		$groupedWorkPrimaryIdentifier->type = 'ils';
		$groupedWorkPrimaryIdentifier->identifier = $bibNumber;
		if ($groupedWorkPrimaryIdentifier->find(true)) {
			$groupedWork = new GroupedWork();
			$groupedWork->id = $groupedWorkPrimaryIdentifier->grouped_work_id;
			if ($groupedWork->find(true)) {
				$validRecords[$bibNumber] = $groupedWork->permanent_id;
				$groupedWork->__destruct();
				$groupedWorkPrimaryIdentifier->__destruct();
				return $groupedWork->permanent_id;
			} else {
				$invalidRecords[$bibNumber] = true;
				$groupedWork->__destruct();
				$groupedWorkPrimaryIdentifier->__destruct();
				return $groupedWork->permanent_id;
			}
		} else {
			$invalidRecords[$bibNumber] = true;
			$groupedWorkPrimaryIdentifier->__destruct();
			return null;
		}
	}
}

function getUserIdForBarcode($userBarcode, &$existingUsers, &$missingUsers, &$usersWithSearchPermissions): int {
	if (array_key_exists($userBarcode, $missingUsers)) {
		$userId = -1;
	} elseif (array_key_exists($userBarcode, $existingUsers)) {
		$userId = $existingUsers[$userBarcode];
	} else {
		$user = new User();
		$user->cat_username = $userBarcode;
		if (!$user->find(true)) {
			$user = UserAccount::findNewUser($userBarcode);
			if ($user == false) {
				$missingUsers[$userBarcode] = $userBarcode;
				echo("Could not find user for $userBarcode\r\n");
				return -1;
			}
		}
		$existingUsers[$userBarcode] = $user->id;
		if ($user->hasPermission('Include Lists In Search Results')) {
			$usersWithSearchPermissions[$userBarcode] = true;
		}
		$userId = $user->id;
	}
	return $userId;
}