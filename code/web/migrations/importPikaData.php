<?php
require_once __DIR__ . '/../bootstrap.php';

/**
 * This will load user data from Pika based on exports performed by Marmot
 */
global $serverName;

ini_set('memory_limit','512M');
$dataPath = '/data/aspen-discovery/' . $serverName;
$exportPath = $dataPath . '/pika_export/';

if (!file_exists($exportPath)){
	echo("Could not find export path " . $exportPath);
}else{
	//Make sure we have all the right files
	if (!file_exists($exportPath . "patronLists.csv")){
		echo("Could not find patronLists.csv in export path " . $exportPath);
		die();
	}
	if (!file_exists($exportPath . "patronListEntries.csv")){
		echo("Could not find patronListEntries.csv in export path " . $exportPath);
		die();
	}
	if (!file_exists($exportPath . "patronRatingsAndReviews.csv")){
		echo("Could not find patronRatingsAndReviews.csv in export path " . $exportPath);
		die();
	}
	if (!file_exists($exportPath . "patronReadingHistory.csv")){
		echo("Could not find patronReadingHistory.csv in export path " . $exportPath);
		die();
	}

	importLists($exportPath);
}

function importLists($exportPath){
	$existingUsers = [];
	$missingUsers = [];
	$existingLists = [];
	$removedLists = [];

	set_time_limit(600);
	require_once ROOT_DIR . '/sys/LocalEnrichment/UserList.php';
	$patronsListHnd = fopen($exportPath . "patronLists.csv", 'r');
	$numImports = 0;
	while ($patronListRow = fgetcsv($patronsListHnd)){
		$numImports++;
		//Figure out the user for the list
		$userBarcode = $patronListRow[0];
		$listId = $patronListRow[1];
		if (array_key_exists($userBarcode, $missingUsers)) {
			$removedLists[$listId] = $listId;
			continue;
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
					$removedLists[$listId] = $listId;
					continue;
				}
			}
			$existingUsers[$userBarcode] = $user->id;
			$userId = $user->id;
		}

		$existingLists[$listId] = $listId;
		$listName = cleancsv($patronListRow[2]);
		$listDescription = cleancsv($patronListRow[3]);
		$dateCreated = $patronListRow[4]; //Not sure this is correct, but seems likely
		$public = $patronListRow[5];
		$sort = cleancsv($patronListRow[6]);
		$userList = new UserList();
		$userList->id = $listId;
		$listExists = false;
		if ($userList->find(true)){
			$listExists = true;
		}
		$userList->user_id = $userId;
		$userList->created = $dateCreated;
		$userList->title = $listName;
		$userList->description = $listDescription;
		$userList->public = $public;
		$userList->defaultSort = $sort;
		if ($listExists){
			if (count($userList->getListTitles()) > 0){
				$userList->removeAllListEntries(false);
			}
			$userList->update();
		}else{
			$userList->insert();
		}

		$userList->__destruct();
		$userList = null;

		if ($numImports % 250 == 0){
			gc_collect_cycles();
			ob_flush();
		}
	}
	fclose($patronsListHnd);

	//Load the list entries
	set_time_limit(600);
	$patronListEntriesHnd = fopen($exportPath . "patronListEntries.csv", 'r');
	$numImports = 0;
	while ($patronListEntryRow = fgetcsv($patronListEntriesHnd)){
		$numImports++;
		$listId = $patronListEntryRow[1];
		$notes = cleancsv($patronListEntryRow[2]);
		$dateAdded = $patronListEntryRow[3];
		$title = cleancsv($patronListEntryRow[4]);
		$author = cleancsv($patronListEntryRow[5]);
		$groupedWorkId = $patronListEntryRow[6];

		if (array_key_exists($listId, $removedLists)){
			//Skip this list entry since the list wasn't imported (because the user no longer exists)
			continue;
		}elseif (!array_key_exists($listId, $existingLists)){
			echo("List $listId has not been imported yet\r\n");
		}

		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		$groupedWork = new GroupedWork();
		$groupedWork->permanent_id = $groupedWorkId;
		if (!$groupedWork->find(true)){
			echo("Grouped Work $groupedWorkId - $title by $author on list $listId does not exist\r\n");
			continue;
		}elseif ($groupedWork->full_title != $title || $groupedWork->author != $author){
			echo("Warning grouped Work $groupedWorkId - $title by $author on list $listId may have matched incorrectly {$groupedWork->full_title} {$groupedWork->author}");
		}

		require_once ROOT_DIR . '/sys/LocalEnrichment/UserListEntry.php';
		$listEntry = new UserListEntry();
		$listEntry->listId = $listId;
		$listEntry->groupedWorkPermanentId = $groupedWorkId;
		$entryExists = false;
		if ($listEntry->find(true)){
			$entryExists = true;
		}
		$listEntry->dateAdded = $dateAdded;
		$listEntry->notes = $notes;
		if ($entryExists){
			$listEntry->update(false);
		}else{
			$listEntry->insert(false);
		}
		$groupedWork->__destruct();
		$groupedWork = null;
		$listEntry->__destruct();
		$listEntry = null;
		if ($numImports % 250 == 0){
			gc_collect_cycles();
			ob_flush();
		}
	}
	fclose($patronListEntriesHnd);

	ob_flush();
}

function cleancsv($field){
	if ($field == '\N'){
		return null;
	}
	$field = str_replace('\"', "", $field);
	$field = str_replace("\r\\\n", '<br/>', $field);
	$field = str_replace("\\\n", '<br/>', $field);
	return $field;
}