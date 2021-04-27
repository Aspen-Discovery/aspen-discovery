<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

/**
 * This will load user data from Bibliocommons.
 * All bib ids should be in the active system (so if the library is also converting from a different ILS to Koha, all IDs should be updated prior to running this)
 */
global $serverName;

set_time_limit(-1);

ini_set('memory_limit','4G');
$dataPath = '/data/aspen-discovery/' . $serverName;
$exportPath = $dataPath . '/bibliocommons_export/';

if (!file_exists($exportPath)){
	echo("Could not find export path " . $exportPath . "\n");
}else{
	$existingUsers = [];
	$missingUsers = [];
	$validRecords = []; // An array mapping the record id to the grouped work id
	$invalidRecords = []; //An array containing any records that no longer exist and therefore are not imported.

	$startTime = time();
	if (file_exists($exportPath . "ratings.csv")) {
		importRatings($startTime, $exportPath, $existingUsers, $missingUsers, $validRecords, $invalidRecords);
	}
	if (file_exists($exportPath . "patronshelves.csv")) {
		importPatronShelves($startTime, $exportPath, $existingUsers, $missingUsers, $validRecords, $invalidRecords);
	}
	if (file_exists($exportPath . "stafflist.csv")) {
		importStaffLists($startTime, $exportPath, $existingUsers, $missingUsers, $validRecords, $invalidRecords);
	}
}

function importRatings($startTime, $exportPath, &$existingUsers, &$missingUsers, &$validRecords, &$invalidRecords)
{
	echo("Starting to import ratings\n");

	require_once ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';
	$patronsRatingsHnd = fopen($exportPath . "ratings.csv", 'r');
	$numImports = 0;
	$batchStartTime = time();
	$ratingsSkipped = 0;
	$existingUsers = [];
	$missingUsers = [];
	while ($patronRatingRow = fgetcsv($patronsRatingsHnd)) {
		$numImports++;
		$userBarcode = $patronRatingRow[0];
		$bibNumber = $patronRatingRow[1];
		$isPrivate = $patronRatingRow[2];
		$rating = $patronRatingRow[3];
		//$createdDate = strtotime($patronRatingRow[4]);
		$updatedDate = strtotime($patronRatingRow[5]);

		if ($isPrivate == 'FALSE'){
			$userId = getUserIdForBarcode($userBarcode, $existingUsers, $missingUsers, $usersWithSearchPermissions);
			if ($userId == -1){
				$ratingsSkipped++;
				continue;
			}

			$groupedWorkForRecordId = getGroupedWorkForRecordId($bibNumber, $validRecords, $invalidRecords);
			if ($groupedWorkForRecordId != null){
				$userReview = new UserWorkReview();
				$userReview->userId = $userId;
				$userReview->groupedRecordPermanentId = $groupedWorkForRecordId;
				if (!$userReview->find(true)){
					//The rating does not exist, insert a new one
					$userReview->importedFrom = 'BiblioCommons';
					$userReview->dateRated = $updatedDate;
					if ($rating >= 9){
						$userReview->rating = 5;
					}elseif ($rating >= 7){
						$userReview->rating = 4;
					}elseif ($rating >= 5){
						$userReview->rating = 3;
					}elseif ($rating >= 3){
						$userReview->rating = 2;
					}else{
						$userReview->rating = 1;
					}
					$userReview->insert();
				}
			}else{
				$ratingsSkipped++;
				continue;
			}
		}

		if ($numImports % 2500 == 0){
			gc_collect_cycles();
			ob_flush();
			usleep(10);
			$elapsedTime = time() - $batchStartTime;
			$batchStartTime = time();
			$totalElapsedTime = ceil((time() - $startTime) / 60);
			echo("Processed $numImports Ratings in $elapsedTime seconds ($totalElapsedTime minutes total).\n");
		}
	}

	$elapsedTime = time() - $batchStartTime;
	$totalElapsedTime = ceil((time() - $startTime) / 60);
	echo("Processed $numImports Ratings in $elapsedTime seconds ($totalElapsedTime minutes total).\n");

	fclose($patronsRatingsHnd);
}

function importPatronShelves($startTime, $exportPath, &$existingUsers, &$missingUsers, &$validRecords, &$invalidRecords)
{
	echo("Starting to import patron shelves\n");

	require_once ROOT_DIR . '/sys/UserLists/UserList.php';
	require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
	$patronsShelvesHnd = fopen($exportPath . "patronshelves.csv", 'r');
	$numImports = 0;

	$usersSkipped = 0;
	$batchStartTime = time();
	$existingLists = [];
	$existingUsers = [];
	$missingUsers = [];
	$usersWithSearchPermissions = [];

	while ($patronShelfRow = fgetcsv($patronsShelvesHnd)) {
		$numImports++;
		$listType = $patronShelfRow[0];
		$bibNumber = $patronShelfRow[1];
		$userBarcode = $patronShelfRow[2];
		$createdDate = strtotime($patronShelfRow[3]);
		//$updatedDate = strtotime($patronShelfRow[4]);
		$isPrivate = $patronShelfRow[5];

		//Figure out the user for the list
		$userId = getUserIdForBarcode($userBarcode, $existingUsers, $missingUsers, $usersWithSearchPermissions);
		if ($userId == -1){
			$usersSkipped++;
			continue;
		}

		switch ($listType){
			case "COMPLETED":
				$listName = "Completed";
				break;
			case "LATER":
				$listName = "For Later";
				break;
			case "PROGRESS":
				$listName = "In Progress";
				break;
			default:
				$listName = $listType;
		}

		if (!array_key_exists($userId . $listName, $existingLists)){
			$userList = new UserList();
			$userList->user_id = $userId;
			$userList->title = $listName;
			if ($userList->find(true)){
				$existingLists[$userId . $listName] = $userList;
			}else{
				$userList = new UserList();
				$userList->user_id = $userId;
				$userList->created = $createdDate;
				$userList->title = $listName;
				$userList->description = '';
				$userList->public = false;
				$userList->defaultSort = 'dateAdded';
				$userList->importedFrom = 'BiblioCommons';
				$searchable = !$isPrivate && array_key_exists($userBarcode, $usersWithSearchPermissions);
				$userList->searchable = $searchable; //This should only be true
				$userList->insert();
				$existingLists[$userId . $listName] = $userList;
			}
		}else{
			$userList = $existingLists[$userId . $listName];
		}

		//Get the grouped work id for the bib record
		$groupedWorkForRecordId = getGroupedWorkForRecordId($bibNumber, $validRecords, $invalidRecords);
		if ($groupedWorkForRecordId != null){
			$listEntry = new UserListEntry();
			$listEntry->listId = $userList->id;
			$listEntry->source = 'GroupedWork';
			$listEntry->sourceId = $groupedWorkForRecordId;
			if (!$listEntry->find(true)){
				$listEntry->importedFrom = 'BiblioCommons';
				$listEntry->dateAdded = $createdDate;
				$listEntry->insert(false);
			}
		}

		if ($numImports % 2500 == 0){
			gc_collect_cycles();
			ob_flush();
			usleep(10);
			$elapsedTime = time() - $batchStartTime;
			$batchStartTime = time();
			$totalElapsedTime = ceil((time() - $startTime) / 60);
			echo("Processed $numImports Shelves in $elapsedTime seconds ($totalElapsedTime minutes total).\n");
		}
	}

	fclose($patronsShelvesHnd);
	$elapsedTime = time() - $batchStartTime;
	$totalElapsedTime = ceil((time() - $startTime) / 60);
	echo("Processed $numImports Shelves in $elapsedTime seconds ($totalElapsedTime minutes total).\n");
}

function importStaffLists($startTime, $exportPath, &$existingUsers, &$missingUsers, &$validRecords, &$invalidRecords)
{
	echo("Starting to import staff lists\n");

	require_once ROOT_DIR . '/sys/UserLists/UserList.php';
	require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
	$staffListfHnd = fopen($exportPath . "stafflist.csv", 'r');
	$numImports = 0;
	$batchStartTime = time();
	$existingLists = [];
	$removedLists = [];
	$usersWithSearchPermissions = [];
	//Read the headers
	fgetcsv($staffListfHnd);
	while ($patronListRow = fgetcsv($staffListfHnd, 0, ',', '"', '"')) {
		$numImports++;

		if (sizeof($patronListRow) != 11){
			//We got a bad export, likely
			continue;
		}
		//Figure out the user for the list
		$userBarcode = $patronListRow[0];
		$listId = $patronListRow[1];
		$listName = cleancsv($patronListRow[2]);
		$description = cleancsv($patronListRow[3]);
		$isPrivate = $patronListRow[4];
		$userAnnotation = cleancsv($patronListRow[6]);
		$bibNumber = $patronListRow[7];
		$index = $patronListRow[8];
		//$updatedDate = strtotime($patronListRow[9]);
		$createdDate = strtotime($patronListRow[10]);

		//Figure out the user for the list
		$userId = getUserIdForBarcode($userBarcode, $existingUsers, $missingUsers, $usersWithSearchPermissions);
		if ($userId == -1){
			$removedLists[$listId] = $listId;
			continue;
		}

		if (!array_key_exists($listId, $existingLists)){
			$userList = new UserList();
			$userList->user_id = $userId;
			$userList->title = $listName;
			if ($userList->find(true)){
				$existingLists[$listId] = $userList->id;
			}else{
				$userList = new UserList();
				$userList->user_id = $userId;
				$userList->created = $createdDate;
				$userList->title = $listName;
				$userList->description = $description;
				$userList->public = !$isPrivate;
				$userList->defaultSort = 'custom';
				$userList->importedFrom = 'BiblioCommons';
				$searchable = !$isPrivate && array_key_exists($userBarcode, $usersWithSearchPermissions);
				$userList->searchable = $searchable; //This should only be true
				$userList->insert();
				$existingLists[$listId] = $userList->id;
			}
			$userList->__destruct();
		}
		$userListId = $existingLists[$listId];

		//Get the grouped work id for the bib record
		$groupedWorkForRecordId = getGroupedWorkForRecordId($bibNumber, $validRecords, $invalidRecords);
		if ($groupedWorkForRecordId != null){
			$listEntry = new UserListEntry();
			$listEntry->listId = $userListId;
			$listEntry->source = 'GroupedWork';
			$listEntry->sourceId = $groupedWorkForRecordId;
			if (!$listEntry->find(true)){
				$listEntry->dateAdded = $createdDate;
				$listEntry->notes = $userAnnotation;
				$listEntry->weight = $index;
				$listEntry->importedFrom = "BiblioCommons";
				$listEntry->insert(false);
			}
			$listEntry->__destruct();
		}

		if ($numImports % 2500 == 0){
			gc_collect_cycles();
			ob_flush();
			usleep(10);
			$elapsedTime = time() - $batchStartTime;
			$batchStartTime = time();
			$totalElapsedTime = ceil((time() - $startTime) / 60);
			echo("Processed $numImports Lists in $elapsedTime seconds ($totalElapsedTime minutes total).\n");
		}
	}

	fclose($staffListfHnd);
	$elapsedTime = time() - $batchStartTime;
	$totalElapsedTime = ceil((time() - $startTime) / 60);
	echo("Processed $numImports Lists in $elapsedTime seconds ($totalElapsedTime minutes total).\n");
	echo("Removed " . count($removedLists) . " lists because the user is not valid\n");
}

function getGroupedWorkForRecordId($bibNumber, &$validRecords, &$invalidRecords) : ?string
{
	require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
	require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
	if (array_key_exists($bibNumber, $validRecords)){
		return $validRecords[$bibNumber];
	}elseif (array_key_exists($bibNumber, $invalidRecords)){
		return null;
	}else{
		$groupedWorkPrimaryIdentifier = new GroupedWorkPrimaryIdentifier();
		$groupedWorkPrimaryIdentifier->type = 'ils';
		$groupedWorkPrimaryIdentifier->identifier = $bibNumber;
		if ($groupedWorkPrimaryIdentifier->find(true)){
			$groupedWork = new GroupedWork();
			$groupedWork->id = $groupedWorkPrimaryIdentifier->grouped_work_id;
			if ($groupedWork->find(true)){
				$validRecords[$bibNumber] = $groupedWork->permanent_id;
				$groupedWork->__destruct();
				$groupedWorkPrimaryIdentifier->__destruct();
				return $groupedWork->permanent_id;
			}else{
				$invalidRecords[$bibNumber] = true;
				$groupedWork->__destruct();
				$groupedWorkPrimaryIdentifier->__destruct();
				return $groupedWork->permanent_id;
			}
		}else{
			$invalidRecords[$bibNumber] = true;
			$groupedWorkPrimaryIdentifier->__destruct();
			return null;
		}
	}
}

function getUserIdForBarcode($userBarcode, &$existingUsers, &$missingUsers, &$usersWithSearchPermissions) : int {
	if (array_key_exists($userBarcode, $missingUsers)) {
		$userId = -1;
	}elseif (array_key_exists($userBarcode, $existingUsers)){
		$userId = $existingUsers[$userBarcode];
	}else{
		$user = new User();
		$user->cat_username = $userBarcode;
		if (!$user->find(true)){
			$user = UserAccount::findNewUser($userBarcode);
			if ($user == false){
				$missingUsers[$userBarcode] = $userBarcode;
				echo("Could not find user for $userBarcode\r\n");
				return -1;
			}
		}
		$existingUsers[$userBarcode] = $user->id;
		if ($user->hasPermission('Include Lists In Search Results')){
			$usersWithSearchPermissions[$userBarcode] = true;
		}
		$userId = $user->id;
	}
	return $userId;
}

function cleancsv($field){
	if ($field == '\N'){
		return null;
	}
	$field = str_replace('\"', '"', $field);
	$field = str_replace("\r\\\n", '<br/>', $field);
	$field = str_replace("\\\n", '<br/>', $field);
	return $field;
}