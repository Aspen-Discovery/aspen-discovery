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
$exportPath = $dataPath . '/carlx_export/';

if (!file_exists($exportPath)) {
	echo("Could not find export path " . $exportPath . "\n");
	printInstructions($exportPath);
} else {
	$existingUsers = [];
	$missingUsers = [];
	$validRecords = []; // An array mapping the record id to the grouped work id
	$invalidRecords = []; //An array containing any records that no longer exist and therefore are not imported.

	$startTime = time();
	if (file_exists($exportPath . "lists.txt")) {
		importLists($startTime, $exportPath, $existingUsers, $missingUsers, $validRecords, $invalidRecords);
	} else {
		echo("Could not find lists.txt to import\n");
		printInstructions($exportPath);
	}
}

function printInstructions($exportPath) {
	echo("To import lists from CARL.X create a csv separated file named lists.txt with the following columns\n");
	echo("- PatronID\n");
	echo("- ListID\n");
	echo("- List Name\n");
	echo("- List Description\n");
	echo("- Scope\n");
	echo("- List Creation Date\n");
	echo("- List Update Date\n");
	echo("- BID\n");
	echo("That file should be placed in $exportPath\n");
	echo("The CSV file may need to be generated based on an Excel file exported by TLC\n");
}

function importLists($startTime, $exportPath, &$existingUsers, &$missingUsers, &$validRecords, &$invalidRecords) {
	echo("Starting to import lists\n");

	require_once ROOT_DIR . '/sys/UserLists/UserList.php';
	require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
	$listsfHnd = fopen($exportPath . "lists.txt", 'r');

	$numImports = 0;
	$batchStartTime = time();
	$listsCreated = 0;
	$existingLists = [];
	$removedLists = [];
	$usersWithSearchPermissions = [];

	//Skip the first row which is titles.
	fgetcsv($listsfHnd);
	$lastListId = -1;
	$entryWeight = 1;
	while ($patronListRow = fgetcsv($listsfHnd, 0, ',')) {
		$numImports++;

		if (sizeof($patronListRow) != 8) {
			//We got a bad export, likely
			continue;
		}
		//Figure out the user for the list
		$patronBarcode = cleancsv($patronListRow[0]);
		$listId = cleancsv($patronListRow[1]);
		if ($listId != $lastListId){
			$lastListId = $listId;
			$entryWeight = 1;
		} else {
			$entryWeight++;
		}
		$listName = cleancsv($patronListRow[2]);
		$listDescription = cleancsv($patronListRow[3]);
		$listScope = cleancsv($patronListRow[4]);
		$listCreationDate = cleancsv($patronListRow[5]);
		$listEntryBid = cleancsv($patronListRow[7]);
		$listEntryBid = 'CARL' . str_pad($listEntryBid, 10, '0', STR_PAD_LEFT);

		$dateAddedToList = strtotime($listCreationDate);
		if ($dateAddedToList == false) {
			$firstDot = strpos($listCreationDate, '.');
			$timeWithHoursAndMinutes = substr($listCreationDate, 0, $firstDot);
			$dateAddedToList = strtotime($timeWithHoursAndMinutes);
		}

		//Figure out the user for the list
		$userId = getUserIdForBarcode($patronBarcode, $existingUsers, $missingUsers, $usersWithSearchPermissions);
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
				$userList->description = $listDescription;
				if ($listScope == 'Public') {
					$userList->public = true;
				}else {
					$userList->public = false;
				}
				$userList->defaultSort = 'custom';
				$userList->importedFrom = 'Carl.X';
				$userList->searchable = false;
				$userList->insert();
				$existingLists[$listId] = $userList->id;
				$listsCreated++;
			}
			$userList->__destruct();
		}
		$userListId = $existingLists[$listId];

		//Get the grouped work id for the bib record
		$groupedWorkForInfo = getGroupedWorkInfoForRecordId($listEntryBid, $validRecords, $invalidRecords);
		if ($groupedWorkForInfo != null) {
			$listEntry = new UserListEntry();
			$listEntry->listId = $userListId;
			$listEntry->source = 'GroupedWork';
			$listEntry->sourceId = $groupedWorkForInfo[0];
			if (!$listEntry->find(true)) {
				$listEntry->dateAdded = $dateAddedToList;
				$listEntry->notes = '';
				$listEntry->weight = $entryWeight;
				$listEntry->importedFrom = "Enterprise";
				$listEntry->title = strlen($groupedWorkForInfo[1]) > 50 ? substr($groupedWorkForInfo[1], 0, 50) : $groupedWorkForInfo[1];
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
			$elapsedTime = time() - $batchStartTime;
			$batchStartTime = time();
			$totalElapsedTime = ceil((time() - $startTime) / 60);
			echo("Processed $numImports Lists entries in $elapsedTime seconds ($totalElapsedTime minutes total).\n");
			//Take a break!
			sleep(5);
		}
	}

	fclose($listsfHnd);
	$elapsedTime = time() - $batchStartTime;
	$totalElapsedTime = ceil((time() - $startTime) / 60);
	echo("Processed $numImports List Titles in $elapsedTime seconds ($totalElapsedTime minutes total).\n");
	echo("Created $listsCreated lists\n");
	echo("Removed " . count($removedLists) . " lists because the user is not valid\n");
}

function getGroupedWorkInfoForRecordId($bibNumber, &$validRecords, &$invalidRecords): ?array {
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
				$validRecords[$bibNumber] = [$groupedWork->permanent_id, $groupedWork->full_title];
				$groupedWork->__destruct();
				$groupedWorkPrimaryIdentifier->__destruct();
				return $validRecords[$bibNumber];
			} else {
				$invalidRecords[$bibNumber] = true;
				$groupedWork->__destruct();
				$groupedWorkPrimaryIdentifier->__destruct();
				return [$groupedWork->permanent_id, 'Unknown'];
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
		$user->ils_barcode = $userBarcode;
		if (!$user->find(true)) {
			$user = UserAccount::findNewUser($userBarcode, '');
			if ($user == false) {
				$missingUsers[$userBarcode] = $userBarcode;
				echo("Could not find user for $userBarcode\r\n");
				return -1;
			} else {
				echo("Found user for barcode $userBarcode\r\n");
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

function cleancsv($field) {
	if ($field == '\N') {
		return null;
	}
	if (substr($field, 0, 1) == "'" && substr($field, -1, 1) == "'") {
		$field = substr($field, 1, strlen($field) - 2);
	}
	$field = str_replace('\"', '"', $field);
	$field = str_replace("\r\\\n", '<br/>', $field);
	$field = str_replace("\\\n", '<br/>', $field);
	return $field;
}