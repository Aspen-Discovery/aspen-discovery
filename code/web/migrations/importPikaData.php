<?php
require_once __DIR__ . '/../bootstrap.php';

/**
 * This will load user data from Pika based on exports performed by Marmot
 */
global $serverName;

ini_set('memory_limit','1G');
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

	$existingUsers = [];
	$missingUsers = [];
	$validGroupedWorks = [];
	$invalidGroupedWorks = [];
	$movedGroupedWorks = [];

	importReadingHistory($exportPath, $existingUsers, $missingUsers, $validGroupedWorks, $invalidGroupedWorks, $movedGroupedWorks);
	importRatingsAndReviews($exportPath, $existingUsers, $missingUsers, $validGroupedWorks, $invalidGroupedWorks, $movedGroupedWorks);
	importLists($exportPath, $existingUsers, $missingUsers, $validGroupedWorks, $invalidGroupedWorks, $movedGroupedWorks);
}

function importReadingHistory($exportPath, $existingUsers, $missingUsers, &$validGroupedWorks, &$invalidGroupedWorks, &$movedGroupedWorks){
	set_time_limit(600);
	require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';

	//Clear all existing reading history data
	$readingHistoryEntry = new ReadingHistoryEntry();
	$readingHistoryEntry->whereAdd();
	$readingHistoryEntry->whereAdd("userId > 0");
	$readingHistoryEntry->delete(true);
	$numImports = 0;
	$readingHistoryHnd = fopen($exportPath . "patronReadingHistory.csv", 'r');
	while ($patronsReadingHistoryRow = fgetcsv($readingHistoryHnd)){
		$numImports++;

		//Figure out the appropriate user for reading history
		$userBarcode = $patronsReadingHistoryRow[0];
		$userId = getUserIdForBarcode($userBarcode, $existingUsers, $missingUsers);
		if ($userId == -1){
			continue;
		}else{
			$user = new User();
			$user->id = $userId;
			if ($user->find(true)){
				if ($user->initialReadingHistoryLoaded == false || $user->trackReadingHistory == false){
					$user->initialReadingHistoryLoaded = 1;
					$user->trackReadingHistory = 1;
					$user->update();
				}
			}
		}

		//Get the grouped work
		$source = $patronsReadingHistoryRow[1];
		$sourceId = $patronsReadingHistoryRow[2];
		$title = cleancsv($patronsReadingHistoryRow[3]);
		$author = cleancsv($patronsReadingHistoryRow[4]);
		$format = cleancsv($patronsReadingHistoryRow[5]);
		$checkoutDate = $patronsReadingHistoryRow[6];
		$groupedWorkTitle = cleancsv($patronsReadingHistoryRow[7]);
		$groupedWorkAuthor = cleancsv($patronsReadingHistoryRow[8]);
		$groupedWorkId = $patronsReadingHistoryRow[9];
		$groupedWorkResources = $patronsReadingHistoryRow[10];

		if (!validateGroupedWork($groupedWorkId, $groupedWorkTitle, $groupedWorkAuthor, $validGroupedWorks, $invalidGroupedWorks, $movedGroupedWorks)){
			continue;
		}

		$readingHistoryEntry = new ReadingHistoryEntry();
		$readingHistoryEntry->userId = $userId;
		$readingHistoryEntry->source = $source;
		$readingHistoryEntry->sourceId = $sourceId;
		$readingHistoryEntry->title = $title;
		$readingHistoryEntry->author = $author;
		$readingHistoryEntry->format = $format;
		$readingHistoryEntry->checkInDate = $checkoutDate;
		$readingHistoryEntry->checkOutDate = $checkoutDate;
		$readingHistoryEntry->groupedWorkPermanentId = $groupedWorkId;

		$readingHistoryEntry->insert();

		if ($numImports % 250 == 0){
			gc_collect_cycles();
			ob_flush();
		}
	}
	fclose($readingHistoryHnd);
}

function importRatingsAndReviews($exportPath, $existingUsers, $missingUsers, &$validGroupedWorks, &$invalidGroupedWorks, &$movedGroupedWorks){
	set_time_limit(600);
	require_once ROOT_DIR . '/sys/LocalEnrichment/UserList.php';
	$patronsRatingsAndReviewsHnd = fopen($exportPath . "patronRatingsAndReviews.csv", 'r');
	$numImports = 0;

	while ($patronsRatingsAndReviewsRow = fgetcsv($patronsRatingsAndReviewsHnd)){
		$numImports++;
		//Figure out the user for the review
		$userBarcode = $patronsRatingsAndReviewsRow[0];
		$userId = getUserIdForBarcode($userBarcode, $existingUsers, $missingUsers);
		if ($userId == -1){
			continue;
		}

		$rating = $patronsRatingsAndReviewsRow[1];
		$review = cleancsv($patronsRatingsAndReviewsRow[2]);
		$dateRated = $patronsRatingsAndReviewsRow[3];
		$title = cleancsv($patronsRatingsAndReviewsRow[4]);
		$author = cleancsv($patronsRatingsAndReviewsRow[5]);
		$groupedWorkId = $patronsRatingsAndReviewsRow[6];

		if (!validateGroupedWork($groupedWorkId, $title, $author, $validGroupedWorks, $invalidGroupedWorks, $movedGroupedWorks)){
			continue;
		}

		require_once ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';
		$userWorkReview = new UserWorkReview();
		$userWorkReview->groupedRecordPermanentId = $groupedWorkId;
		$userWorkReview->userId = $userId;
		$reviewExists = false;
		if ($userWorkReview->find(true)){
			$reviewExists = true;
		}
		$userWorkReview->rating = $rating;
		$userWorkReview->review = $review;
		$userWorkReview->dateRated = $dateRated;
		if ($reviewExists){
			$userWorkReview->update();
		}else{
			$userWorkReview->insert();
		}
		if ($numImports % 250 == 0){
			gc_collect_cycles();
			ob_flush();
		}
	}
	fclose($patronsRatingsAndReviewsHnd);
}

function importLists($exportPath, &$existingUsers, &$missingUsers, &$validGroupedWorks, &$invalidGroupedWorks, &$movedGroupedWorks){
	global $memoryWatcher;
	$memoryWatcher->logMemory("Start of list import");

	set_time_limit(600);
	require_once ROOT_DIR . '/sys/LocalEnrichment/UserList.php';
	$patronsListHnd = fopen($exportPath . "patronLists.csv", 'r');
	$numImports = 0;
	while ($patronListRow = fgetcsv($patronsListHnd)){
		$numImports++;
		//Figure out the user for the list
		$userBarcode = $patronListRow[0];
		$listId = $patronListRow[1];

		$userId = getUserIdForBarcode($userBarcode, $existingUsers, $missingUsers);
		if ($userId == -1){
			$removedLists[$listId] = $listId;
			continue;
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
			usleep(10);
			$memoryWatcher->logMemory("Imported $numImports Lists");
		}
	}
	fclose($patronsListHnd);

	//Load the list entries
	set_time_limit(600);
	$patronListEntriesHnd = fopen($exportPath . "patronListEntries.csv", 'r');
	$numImports = 0;
	$validGroupedWorks = [];
	$invalidGroupedWorks = [];
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

		if (!validateGroupedWork($groupedWorkId, $title, $author, $validGroupedWorks, $invalidGroupedWorks, $movedGroupedWorks)){
			continue;
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

function getUserIdForBarcode($userBarcode, &$existingUsers, &$missingUsers){
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
		$userId = $user->id;
	}
	return $userId;
}

function validateGroupedWork(&$groupedWorkId, $title, $author, &$validGroupedWorks, &$invalidGroupedWorks, &$movedGroupedWorks){
	require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';

	if (array_key_exists($groupedWorkId, $invalidGroupedWorks)){
		$groupedWorkValid = false;
	}elseif (array_key_exists($groupedWorkId, $validGroupedWorks)) {
		$groupedWorkValid = true;
	}elseif (array_key_exists($groupedWorkId, $movedGroupedWorks)) {
		$groupedWorkValid = true;
		$groupedWorkId = $movedGroupedWorks[$groupedWorkId];
	}else{
		//Try to validate the grouped work
		$groupedWork = new GroupedWork();
		$groupedWork->permanent_id = $groupedWorkId;
		$groupedWorkValid = true;
		if (!$groupedWork->find(true)){
			if ($title != null || $author != null){
				require_once ROOT_DIR . '/sys/SearchObject/SearchObjectFactory.php';
				//Search for the record by title and author
				$searchObject = SearchObjectFactory::initSearchObject();
				$searchObject->init();
				$searchTerm = '';
				if ($title != null){
					$searchTerm = $title;
				}
				if ($author != null) {
					$searchTerm .= ' ' . $author;
				}
				$searchTerm = trim($searchTerm);
				$searchObject->setBasicQuery($searchTerm);
				$result = $searchObject->processSearch(true, false);
				if ($result instanceof AspenError) {
					echo("Unable to query solr for grouped work {$result->getMessage()}\r\n");
					$groupedWorkValid = false;
					$invalidGroupedWorks[$groupedWorkId] = $groupedWorkId;
				}else{
					$recordSet = $searchObject->getResultRecordSet();
					if ($searchObject->getResultTotal() == 1) {
						//We found it by searching
						$movedGroupedWorks[$groupedWorkId] = $recordSet[0]['id'];
						$groupedWorkId = $recordSet[0]['id'];
						$groupedWorkValid = true;
					}elseif ($searchObject->getResultTotal() > 1) {
						//We probably found it by searching
						echo("WARNING: More than one work found when searching for $title by $author\r\n");
						$movedGroupedWorks[$groupedWorkId] = $recordSet[0]['id'];
						$groupedWorkId = $recordSet[0]['id'];
						$groupedWorkValid = true;
					}else{
						echo("Grouped Work $groupedWorkId - $title by $author could not be found by searching\r\n");
						$groupedWorkValid = false;
						$invalidGroupedWorks[$groupedWorkId] = $groupedWorkId;
					}
				}
			}else{
				echo("Grouped Work $groupedWorkId - $title by $author does not exist\r\n");
				$groupedWorkValid = false;
				$invalidGroupedWorks[$groupedWorkId] = $groupedWorkId;
			}
		}elseif ($groupedWork->full_title != $title || $groupedWork->author != $author){
			echo("WARNING grouped Work $groupedWorkId - $title by $author may have matched incorrectly {$groupedWork->full_title} {$groupedWork->author}");
		}
		if ($groupedWorkValid){
			$validGroupedWorks[$groupedWorkId] = $groupedWorkId;
		}
		$groupedWork->__destruct();
		$groupedWork = null;
	}
	return $groupedWorkValid;
}