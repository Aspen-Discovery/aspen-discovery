<?php
require_once __DIR__ . '/../bootstrap.php';

/**
 * This will load user data from a Pika system
 */
global $serverName;

ini_set('memory_limit','2G');
$dataPath = '/data/aspen-discovery/' . $serverName;
$exportPath = $dataPath . '/pika_export/';

if (!file_exists($exportPath)){
	echo("Could not find export path " . $exportPath);
}else{

	//Make sure we have all the right files
	validateFileExists($exportPath, "users.csv");
	validateFileExists($exportPath, "userRoles.csv");
	validateFileExists($exportPath, "staffSettings.csv");
	validateFileExists($exportPath, "savedSearches.csv");
	validateFileExists($exportPath, "materials_request.csv");
	validateFileExists($exportPath, "patronLists.csv");
	validateFileExists($exportPath, "patronListEntries.csv");
	validateFileExists($exportPath, "patronRatingsAndReviews.csv");
	validateFileExists($exportPath, "patronNotInterested.csv");
	validateFileExists($exportPath, "patronReadingHistory.csv");

	$existingUsers = [];
	$missingUsers = [];
	$validGroupedWorks = [];
	$invalidGroupedWorks = [];
	$movedGroupedWorks = [];

	importUsers($exportPath, $existingUsers, $missingUsers);
//	importLists($exportPath, $existingUsers, $missingUsers, $validGroupedWorks, $invalidGroupedWorks, $movedGroupedWorks);
//	importReadingHistory($exportPath, $existingUsers, $missingUsers, $validGroupedWorks, $invalidGroupedWorks, $movedGroupedWorks);
//	importNotInterested($exportPath, $existingUsers, $missingUsers, $validGroupedWorks, $invalidGroupedWorks, $movedGroupedWorks);
//	importRatingsAndReviews($exportPath, $existingUsers, $missingUsers, $validGroupedWorks, $invalidGroupedWorks, $movedGroupedWorks);
}

function importUsers($exportPath, &$existingUsers, &$missingUsers){
	/** @var PDO $aspen_db */
	global $aspen_db;

	echo ("Starting to import users");
	set_time_limit(600);

	$preValidatedIds = []; //Key is barcode, value is the unique id
	if (file_exists($exportPath . '/patron_ids.csv')){
		$patronIdsHnd = fopen($exportPath . "patron_ids.csv", 'r');
		while ($patronIdRow = fgetcsv($patronIdsHnd)) {
			$preValidatedIds[$patronIdRow[1]] = $patronIdRow[0];
		}
		fclose($patronIdsHnd);
	}

	//Load users, make sure to validate that each still exists in the ILS as we load them
	$numImports = 0;
	$userHnd = fopen($exportPath . "users.csv", 'r');
	while ($userRow = fgetcsv($userHnd)) {
		$numImports++;
		$userFromCSV = loadUserInfoFromCSV($userRow);
		echo("Processing User {$userFromCSV->id}\tBarcode {$userFromCSV->cat_username}\tUsername {$userFromCSV->username}\n");
		if (count($preValidatedIds) > 0){
			if (array_key_exists($userFromCSV->cat_username, $preValidatedIds)){
				$username = $preValidatedIds[$userFromCSV->cat_username];
				if ($username != $userFromCSV->username){
					$existingUser = false;
				}else{
					$existingUser = new User();
					$existingUser->username = $username;
					$existingUser->cat_username = $userFromCSV->cat_username;
					if (!$existingUser->find(true)){
						//Didn't find the combination of username and cat_username (barcode) see if it exists with just the username
						$existingUser = new User();
						$existingUser->username = $username;
						if (!$existingUser->find(true)) {
							//The user does not exist in the database.  We can create it by first inserting it and then cloning it so the rest of the process works
							$userFromCSV->insert();
							$existingUser = clone $userFromCSV;
						}
					}
				}
			}else{
				$existingUser = false;
			}
		}else{
			$existingUser = UserAccount::validateAccount($userFromCSV->cat_username, $userFromCSV->cat_password);
		}
		if ($existingUser != false && !($existingUser instanceof AspenError)){
			$existingUserId = $existingUser->id;
			if ($existingUserId != $userFromCSV->id){
				//Have to delete the old user before inserting the new to avoid errors with primary keys
				$existingUser->delete();
				$userFromCSV->insert();

				if ($existingUserId < 0){
					//Move all existing user data from the old id to the new id
					$aspen_db->query("UPDATE grouped_work_alternate_titles set addedBy = $userFromCSV->id WHERE addedBy = $existingUserId" );
					$aspen_db->query("UPDATE materials_request set createdBy = $userFromCSV->id WHERE createdBy = $existingUserId" );
					$aspen_db->query("UPDATE materials_request set assignedTo = $userFromCSV->id WHERE assignedTo = $existingUserId" );
					$aspen_db->query("UPDATE search set user_id = $userFromCSV->id WHERE user_id = $existingUserId" );
					$aspen_db->query("UPDATE user_cloud_library_usage set userId = $userFromCSV->id WHERE userId = $existingUserId" );
					$aspen_db->query("UPDATE user_hoopla_usage set userId = $userFromCSV->id WHERE userId = $existingUserId" );
					$aspen_db->query("UPDATE user_ils_usage set userId = $userFromCSV->id WHERE userId = $existingUserId" );
					$aspen_db->query("UPDATE user_list set user_id = $userFromCSV->id WHERE user_id = $existingUserId" );
					$aspen_db->query("UPDATE user_not_interested set userId = $userFromCSV->id WHERE userId = $existingUserId" );
					$aspen_db->query("UPDATE user_open_archives_usage set userId = $userFromCSV->id WHERE userId = $existingUserId" );
					$aspen_db->query("UPDATE user_overdrive_usage set userId = $userFromCSV->id WHERE userId = $existingUserId" );
					$aspen_db->query("UPDATE user_payments set userId = $userFromCSV->id WHERE userId = $existingUserId" );
					$aspen_db->query("UPDATE user_rbdigital_usage set userId = $userFromCSV->id WHERE userId = $existingUserId" );
					$aspen_db->query("UPDATE user_sideload_usage set userId = $userFromCSV->id WHERE userId = $existingUserId" );
					$aspen_db->query("UPDATE user_staff_settings set userId = $userFromCSV->id WHERE userId = $existingUserId" );
					$aspen_db->query("UPDATE user_work_review set userId = $userFromCSV->id WHERE userId = $existingUserId" );
				}else{
					//User already exists and had a different id.  There should be no enrichment to copy.  This happens when we insert since the new id is auto generated
					//echo("User {$userFromCSV->cat_username} exists, but has a different id in the database {$existingUserId} than the csv {$userFromCSV->id} changing the id");
					$aspen_db->query("UPDATE user set id = $userFromCSV->id WHERE id = $existingUserId" );
				}
			}else{
				//User already exists and has the same id.  Just update with the info in the database.
				if (!$existingUser->isEqualTo($userFromCSV)) {
					loadUserInfoFromCSV($userRow, $existingUser);
					$existingUser->update();
				}
			}
			$existingUsers[$userFromCSV->cat_username] = $userFromCSV->id;
		}else{
			//User no longer exists in the ILS
			$missingUsers[$userFromCSV->cat_username] = $userFromCSV->cat_username;
		}

		if ($numImports % 250 == 0){
			gc_collect_cycles();
			echo("Processed $numImports Users");
			ob_flush();
			set_time_limit(600);
		}
	}
	fclose($userHnd);
	//TODO: Delete any users that still have an id of -1 (other than aspen_admin)

	//Import roles
	//Import material requests

}

/**
 * @param array|null $userRow
 * @return User
 */
function loadUserInfoFromCSV(?array $userRow, User $existingUser = null): User
{
	$curCol = 0;
	if ($existingUser == null){
		$userFromCSV = new User();
	}else{
		$userFromCSV = $existingUser;
	}

	$userFromCSV->id = $userRow[$curCol++];
	$userFromCSV->username = cleancsv($userRow[$curCol++]);
	$userFromCSV->password = cleancsv($userRow[$curCol++]);
	$userFromCSV->firstname = cleancsv($userRow[$curCol++]);
	$userFromCSV->lastname = cleancsv($userRow[$curCol++]);
	$userFromCSV->email = cleancsv($userRow[$curCol++]);
	$userFromCSV->cat_username = cleancsv($userRow[$curCol++]);
	$userFromCSV->cat_password = cleancsv($userRow[$curCol++]);
	$userFromCSV->college = cleancsv($userRow[$curCol++]);
	$userFromCSV->major = cleancsv($userRow[$curCol++]);
	$userFromCSV->created = cleancsv($userRow[$curCol++]);
	$userFromCSV->homeLocationId = $userRow[$curCol++];
	$userFromCSV->myLocation1Id = $userRow[$curCol++];
	$userFromCSV->myLocation2Id = $userRow[$curCol++];
	$userFromCSV->trackReadingHistory = $userRow[$curCol++];
	$userFromCSV->bypassAutoLogout = $userRow[$curCol++];
	$userFromCSV->displayName = cleancsv($userRow[$curCol++]);
	$userFromCSV->disableCoverArt = $userRow[$curCol++];
	$userFromCSV->disableRecommendations = $userRow[$curCol++];
	$userFromCSV->phone = cleancsv($userRow[$curCol++]);
	$userFromCSV->patronType = cleancsv($userRow[$curCol++]);
	$userFromCSV->overdriveEmail = cleancsv($userRow[$curCol++]);
	$userFromCSV->promptForOverdriveEmail = $userRow[$curCol++];
	$userFromCSV->preferredLibraryInterface = cleancsv($userRow[$curCol++]);
	$userFromCSV->initialReadingHistoryLoaded = $userRow[$curCol++];
	$userFromCSV->noPromptForUserReviews = $userRow[$curCol++];
	$userFromCSV->source = cleancsv($userRow[$curCol++]);
	$userFromCSV->hooplaCheckOutConfirmation = $userRow[$curCol];

	//Set defaults
	$userFromCSV->rbdigitalId = -1;
	$userFromCSV->interfaceLanguage = 'en';
	$userFromCSV->searchPreferenceLanguage = -1;
	$userFromCSV->rememberHoldPickupLocation = 0;
	return $userFromCSV;
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

function importReadingHistory($exportPath, &$existingUsers, &$missingUsers, &$validGroupedWorks, &$invalidGroupedWorks, &$movedGroupedWorks){
	echo ("Starting to import reading history");
	set_time_limit(600);
	require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';

	//Clear all existing reading history data
	//Shouldn't need to clear everything
//	$readingHistoryEntry = new ReadingHistoryEntry();
//	$readingHistoryEntry->whereAdd();
//	$readingHistoryEntry->whereAdd("userId > 0");
//	$readingHistoryEntry->delete(true);
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
		if ($author == null && $groupedWorkAuthor != null){
			$author = ucwords($groupedWorkAuthor);
		}
		$groupedWorkId = $patronsReadingHistoryRow[9];
		$groupedWorkResources = $patronsReadingHistoryRow[10];

		if (!validateGroupedWork($groupedWorkId, $groupedWorkTitle, $groupedWorkAuthor, $validGroupedWorks, $invalidGroupedWorks, $movedGroupedWorks)){
			continue;
		}

		$readingHistoryEntry = new ReadingHistoryEntry();
		$readingHistoryEntry->userId = $userId;
		$readingHistoryEntry->groupedWorkPermanentId = $groupedWorkId;
		if (!$readingHistoryEntry->find(true)){
			$readingHistoryEntry->source = $source;
			$readingHistoryEntry->sourceId = $sourceId;
			$readingHistoryEntry->title = substr($title, 0, 150);
			$readingHistoryEntry->author = substr($author, 0, 75);
			$readingHistoryEntry->format = $format;
			$readingHistoryEntry->checkInDate = $checkoutDate;
			$readingHistoryEntry->checkOutDate = $checkoutDate;

			$readingHistoryEntry->insert();
		}else{
			if (empty($readingHistoryEntry->author) && !empty($author)){
				$readingHistoryEntry->author = substr($author, 0, 75);
				$readingHistoryEntry->update();
			}
		}

		if ($numImports % 250 == 0){
			gc_collect_cycles();
			ob_flush();
			set_time_limit(600);
		}
	}
	fclose($readingHistoryHnd);
}

function importNotInterested($exportPath, &$existingUsers, &$missingUsers, &$validGroupedWorks, &$invalidGroupedWorks, &$movedGroupedWorks){
	echo ("Starting to import not interested titles");
	set_time_limit(600);
	require_once ROOT_DIR . '/sys/LocalEnrichment/NotInterested.php';
	$patronNotInterestedHnd = fopen($exportPath . "patronNotInterested.csv", 'r');
	$numImports = 0;

	while ($patronNotInterestedRow = fgetcsv($patronNotInterestedHnd)){
		$numImports++;
		//Figure out the user for the review
		$userBarcode = $patronNotInterestedRow[0];
		$userId = getUserIdForBarcode($userBarcode, $existingUsers, $missingUsers);
		if ($userId == -1){
			continue;
		}

		$dateMarked = $patronNotInterestedRow[1];
		$title = cleancsv($patronNotInterestedRow[2]);
		$author = cleancsv($patronNotInterestedRow[3]);
		$groupedWorkId = $patronNotInterestedRow[4];

		if (!validateGroupedWork($groupedWorkId, $title, $author, $validGroupedWorks, $invalidGroupedWorks, $movedGroupedWorks)){
			continue;
		}

		$notInterested = new NotInterested();
		$notInterested->userId = $userId;
		$notInterested->groupedRecordPermanentId = $groupedWorkId;
		if ($notInterested->find(true)){
			$notInterested->dateMarked = $dateMarked;
			$notInterested->update();
		}else{
			$notInterested->dateMarked = $dateMarked;
			$notInterested->insert();
		}

		if ($numImports % 250 == 0){
			gc_collect_cycles();
			ob_flush();
		}
	}
	fclose($patronNotInterestedHnd);
}

function importRatingsAndReviews($exportPath, &$existingUsers, &$missingUsers, &$validGroupedWorks, &$invalidGroupedWorks, &$movedGroupedWorks){
	echo ("Starting to import ratings and reviews");
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
	echo ("Starting to import lists");
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

	echo ("Starting to import list entries");
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
					$title = preg_replace('~\ss\s~', 's ', $title);
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
				$searchObject->__destruct();
				$searchObject = null;
			}else{
				//There was no title or author provided, it looks like this was deleted in Pika
				//echo("Grouped Work $groupedWorkId - $title by $author does not exist\r\n");
				$groupedWorkValid = false;
				$invalidGroupedWorks[$groupedWorkId] = $groupedWorkId;
			}
		}elseif ($groupedWork->full_title != $title || $groupedWork->author != $author){
			echo("WARNING grouped Work $groupedWorkId - $title by $author may have matched incorrectly {$groupedWork->full_title} {$groupedWork->author}");
		}
		if ($groupedWorkValid && $title == null && $author == null){
			echo "Grouped work with no title and author was valid\r\n";
			$groupedWorkValid = false;
			$invalidGroupedWorks[$groupedWorkId] = $groupedWorkId;
		}
		if ($groupedWorkValid){
			$validGroupedWorks[$groupedWorkId] = $groupedWorkId;
		}
		$groupedWork->__destruct();
		$groupedWork = null;
	}
	return $groupedWorkValid;
}

/**
 * @param string $exportPath
 * @param string $file
 */
function validateFileExists(string $exportPath, string $file): void
{
	if (!file_exists($exportPath . $file)) {
		echo("Could not find $file in export path " . $exportPath);
		die();
	}
}