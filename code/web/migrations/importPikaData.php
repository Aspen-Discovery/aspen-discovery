<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

/**
 * This will load user data from a Pika system
 */
global $serverName;

ini_set('memory_limit', '4G');
$dataPath = '/data/aspen-discovery/' . $serverName;
$exportPath = $dataPath . '/pika_export/';

if (count($_SERVER['argv']) > 2) {
	$flipIds = $_SERVER['argv'][2];
} else {
	$flipIds = 'Y';
}

if (!file_exists($exportPath)) {
	echo("Could not find export path " . $exportPath . "\n");
} else {

	//Make sure we have all the right files
	//validateFileExists($exportPath, "users.csv");
	//validateFileExists($exportPath, "userRoles.csv");
	//validateFileExists($exportPath, "staffSettings.csv");
	//validateFileExists($exportPath, "saved_searches.csv");
	//validateFileExists($exportPath, "materials_request.csv");
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

	$startTime = time();
	$bibNumberMap = [];
	if (file_exists($exportPath . "bibNumMap.csv")) {
		loadBibNumberMap($exportPath, $bibNumberMap);
	}
	if (file_exists($exportPath . "users.csv")) {
		importUsers($startTime, $exportPath, $existingUsers, $missingUsers, $serverName, $flipIds);
	}
	if (file_exists($exportPath . "saved_searches.csv")) {
		importSavedSearches($startTime, $exportPath, $existingUsers, $missingUsers, $serverName);
	}
	if (file_exists($exportPath . 'mergedGroupedWorks.csv')) {
		importMergedWorks($startTime, $exportPath, $existingUsers, $missingUsers, $serverName, $validGroupedWorks, $invalidGroupedWorks, $movedGroupedWorks);
	}
	importLists($startTime, $exportPath, $existingUsers, $missingUsers, $validGroupedWorks, $invalidGroupedWorks, $movedGroupedWorks, $bibNumberMap);
	//importListWidgets($startTime, $exportPath, $existingUsers, $missingUsers, $serverName);
	importNotInterested($startTime, $exportPath, $existingUsers, $missingUsers, $validGroupedWorks, $invalidGroupedWorks, $movedGroupedWorks, $bibNumberMap);
	importRatingsAndReviews($startTime, $exportPath, $existingUsers, $missingUsers, $validGroupedWorks, $invalidGroupedWorks, $movedGroupedWorks, $bibNumberMap);
	importReadingHistory($startTime, $exportPath, $existingUsers, $missingUsers, $validGroupedWorks, $invalidGroupedWorks, $movedGroupedWorks, $bibNumberMap);

	//Materials Request
	//Linked Users
}

function importUsers($startTime, $exportPath, &$existingUsers, &$missingUsers, $serverName, $flipIds) {
	global $aspen_db;

	echo("Starting to import users\n");
	ob_flush();
	set_time_limit(1800);

	$preValidatedIds = []; //Key is barcode, value is the unique id
	//Optionally we can have a list of all patron ids in the ILS currently.
	//Expects 2 columns
	//Column 1: Unique ID in the ILS
	//Column 2: Patron barcode
	if (file_exists($exportPath . '/patron_ids.csv')) {
		$patronIdsHnd = fopen($exportPath . "patron_ids.csv", 'r');
		while ($patronIdRow = fgetcsv($patronIdsHnd)) {
			$preValidatedIds[$patronIdRow[1]] = $patronIdRow[0];
		}
		fclose($patronIdsHnd);
	} else {
		echo("No patron_ids.csv file found.  This import process is much faster if patron ids are prevalidated.");
		ob_flush();
	}

	//Flipping the user ids helps to deal with cases where the unique id within Aspen is different than the unique ID in Pika.
	//This only happens after the initial conversion when users log in to Aspen and Pika in different orders.
	if ($flipIds == 'Y' || $flipIds == 'y') {
		flipUserIds();
		echo("Flipped User Ids\n");
		ob_flush();
	}

	set_time_limit(600);
	//Load users, make sure to validate that each still exists in the ILS as we load them
	$numImports = 0;
	$userHnd = fopen($exportPath . "users.csv", 'r');
	$batchStartTime = time();
	while ($userRow = fgetcsv($userHnd)) {
		$numImports++;
		$userFromCSV = loadUserInfoFromCSV($userRow);
		//echo("Processing User {$userFromCSV->id}\tBarcode {$userFromCSV->ils_barcode}\tUsername {$userFromCSV->unique_ils_id}\n");
		//ob_flush();
		if (count($preValidatedIds) > 0) {
			if (array_key_exists($userFromCSV->ils_barcode, $preValidatedIds)) {
				$username = $preValidatedIds[$userFromCSV->ils_barcode];
				if ($username != $userFromCSV->unique_ils_id) {
					$existingUser = false;
				} else {
					$existingUser = new User();
					//For nashville
					if ($serverName == 'nashville.aspenlocal' || $serverName == 'nashville.production') {
						$existingUser->source = 'carlx';
					} else {
						$existingUser->source = 'ils';
					}

					$existingUser->username = $username;
					$existingUser->unique_ils_id = $username;
					$existingUser->ils_barcode = $userFromCSV->ils_barcode;
					if (!$existingUser->find(true)) {
						//Didn't find the combination of username and ils_barcode (barcode) see if it exists with just the username
						$existingUser = new User();
						if ($serverName == 'nashville.aspenlocal' || $serverName == 'nashville.production') {
							$existingUser->source = 'carlx';
						} else {
							$existingUser->source = 'ils';
						}
						$existingUser->username = $username;
						$existingUser->unique_ils_id = $username;
						if (!$existingUser->find(true)) {
							//The user does not exist in the database.  We can create it by first inserting it and then cloning it so the rest of the process works
							$userFromCSV->insert();
							$existingUser = clone $userFromCSV;
						}
					}
				}
			} else {
				$existingUser = false;
			}
		} else {
			$existingUser = UserAccount::validateAccount($userFromCSV->ils_barcode, $userFromCSV->ils_password);
		}
		if ($existingUser != false && !($existingUser instanceof AspenError)) {
			//echo("Found an existing user with id {$existingUser->id}\n");
			//ob_flush();
			$existingUserId = $existingUser->id;
			if ($existingUserId != $userFromCSV->id) {
				//Have to delete the old user before inserting the new to avoid errors with primary keys
				$existingUser->delete();
				$userFromCSV->insert();

				if ($existingUserId < 0) {
					//Move all existing user data from the old id to the new id
					$aspen_db->query("UPDATE grouped_work_alternate_titles set addedBy = $userFromCSV->id WHERE addedBy = $existingUserId");
					$aspen_db->query("UPDATE materials_request set createdBy = $userFromCSV->id WHERE createdBy = $existingUserId");
					$aspen_db->query("UPDATE materials_request set assignedTo = $userFromCSV->id WHERE assignedTo = $existingUserId");
					$aspen_db->query("UPDATE search set user_id = $userFromCSV->id WHERE user_id = $existingUserId");
					$aspen_db->query("UPDATE user_cloud_library_usage set userId = $userFromCSV->id WHERE userId = $existingUserId");
					$aspen_db->query("UPDATE user_hoopla_usage set userId = $userFromCSV->id WHERE userId = $existingUserId");
					$aspen_db->query("UPDATE user_ils_usage set userId = $userFromCSV->id WHERE userId = $existingUserId");
					$aspen_db->query("UPDATE user_list set user_id = $userFromCSV->id WHERE user_id = $existingUserId");
					$aspen_db->query("UPDATE user_not_interested set userId = $userFromCSV->id WHERE userId = $existingUserId");
					$aspen_db->query("UPDATE user_open_archives_usage set userId = $userFromCSV->id WHERE userId = $existingUserId");
					$aspen_db->query("UPDATE user_overdrive_usage set userId = $userFromCSV->id WHERE userId = $existingUserId");
					$aspen_db->query("UPDATE user_payments set userId = $userFromCSV->id WHERE userId = $existingUserId");
					$aspen_db->query("UPDATE user_rbdigital_usage set userId = $userFromCSV->id WHERE userId = $existingUserId");
					$aspen_db->query("UPDATE user_reading_history_work set userId = $userFromCSV->id WHERE userId = $existingUserId");
					$aspen_db->query("UPDATE user_roles set userId = $userFromCSV->id WHERE userId = $existingUserId");
					$aspen_db->query("UPDATE user_sideload_usage set userId = $userFromCSV->id WHERE userId = $existingUserId");
					$aspen_db->query("UPDATE user_staff_settings set userId = $userFromCSV->id WHERE userId = $existingUserId");
					$aspen_db->query("UPDATE user_work_review set userId = $userFromCSV->id WHERE userId = $existingUserId");
				} else {
					//User already exists and had a different id.  There should be no enrichment to copy.  This happens when we insert since the new id is auto generated
					//echo("User {$userFromCSV->ils_barcode} exists, but has a different id in the database {$existingUserId} than the csv {$userFromCSV->id} changing the id");
					$aspen_db->query("UPDATE user set id = $userFromCSV->id WHERE id = $existingUserId");
				}
			} else {
				//User already exists and has the same id.  Just update with the info in the database.
				if (!$existingUser->isEqualTo($userFromCSV)) {
					loadUserInfoFromCSV($userRow, $existingUser);
					$existingUser->update();
				}
			}
			$existingUsers[$userFromCSV->ils_barcode] = $userFromCSV->id;
		} else {
			//User no longer exists in the ILS
			$missingUsers[$userFromCSV->ils_barcode] = $userFromCSV->ils_barcode;
		}
		if (!empty($existingUser)) {
			$existingUser->__destruct();
		}
		$existingUser = null;
		$userFromCSV = null;

		if ($numImports % 2500 == 0) {
			gc_collect_cycles();
			$elapsedTime = time() - $batchStartTime;
			$batchStartTime = time();
			$totalElapsedTime = ceil((time() - $startTime) / 60);
			echo("Processed $numImports Users in $elapsedTime seconds ($totalElapsedTime minutes total).\n");
			ob_flush();
			set_time_limit(600);
		}
	}
	fclose($userHnd);
	$elapsedTime = time() - $batchStartTime;
	$totalElapsedTime = ceil((time() - $startTime) / 60);
	echo("Processed $numImports Users in $elapsedTime seconds ($totalElapsedTime minutes total).\n");
	echo(count($missingUsers) . " users were part of the export, but no longer exist in the ILS\n");
	ob_flush();

	//TODO: Delete any users that still have an id of -1 (other than aspen_admin)?

	//Import roles
	//Import material requests
	//Import linked users

}

/**
 * @param array|null $userRow
 * @return User
 */
function loadUserInfoFromCSV(?array $userRow, User $existingUser = null): User {
	$curCol = 0;
	if ($existingUser == null) {
		$userFromCSV = new User();
	} else {
		$userFromCSV = $existingUser;
	}

	$userFromCSV->id = $userRow[$curCol++];
	$userFromCSV->username = cleancsv($userRow[$curCol++]);
	$userFromCSV->unique_ils_id = $userFromCSV->username;
	$userFromCSV->password = cleancsv($userRow[$curCol++]);
	$userFromCSV->firstname = cleancsv($userRow[$curCol++]);
	$userFromCSV->lastname = cleancsv($userRow[$curCol++]);
	$userFromCSV->email = cleancsv($userRow[$curCol++]);
	$userFromCSV->cat_username = cleancsv($userRow[$curCol++]);
	$userFromCSV->ils_barcode = cleancsv($userRow[$curCol++]);
	$userFromCSV->cat_password = cleancsv($userRow[$curCol++]);
	$userFromCSV->ils_password = cleancsv($userRow[$curCol++]);
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

function getUserIdForBarcode($userBarcode, &$existingUsers, &$missingUsers) {
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
			}
		}
		$existingUsers[$userBarcode] = $user->id;
		$userId = $user->id;
	}
	return $userId;
}

function importReadingHistory($startTime, $exportPath, &$existingUsers, &$missingUsers, &$validGroupedWorks, &$invalidGroupedWorks, &$movedGroupedWorks, $bibNumberMap) {
	echo("Starting to import reading history\n");
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
	$batchStartTime = time();
	$numSkipped = 0;
	while ($patronsReadingHistoryRow = fgetcsv($readingHistoryHnd)) {
		$numImports++;

		//Figure out the appropriate user for reading history
		$userBarcode = $patronsReadingHistoryRow[0];
		$userId = getUserIdForBarcode($userBarcode, $existingUsers, $missingUsers);
		if ($userId == -1) {
			continue;
		} else {
			$user = new User();
			$user->id = $userId;
			if ($user->find(true)) {
				if ($user->initialReadingHistoryLoaded == false || $user->trackReadingHistory == false) {
					$user->initialReadingHistoryLoaded = 1;
					$user->trackReadingHistory = 1;
					$user->update();
				}
			}
			$user->__destruct();
			$user = null;
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
		if ($author == null && $groupedWorkAuthor != null) {
			$author = ucwords($groupedWorkAuthor);
		}
		$groupedWorkId = $patronsReadingHistoryRow[9];
		$groupedWorkResources = $patronsReadingHistoryRow[10];

		if (!validateGroupedWork($groupedWorkId, $groupedWorkTitle, $groupedWorkAuthor, $validGroupedWorks, $invalidGroupedWorks, $movedGroupedWorks, $groupedWorkResources, $bibNumberMap)) {
			$numSkipped++;
			continue;
		}

		$readingHistoryEntry = new ReadingHistoryEntry();
		$readingHistoryEntry->userId = $userId;
		$readingHistoryEntry->groupedWorkPermanentId = getGroupedWorkId($groupedWorkId, $validGroupedWorks, $movedGroupedWorks);
		if (!$readingHistoryEntry->find(true)) {
			$readingHistoryEntry->source = $source;
			$readingHistoryEntry->sourceId = $sourceId;
			$readingHistoryEntry->title = substr($title, 0, 150);
			$readingHistoryEntry->author = substr($author, 0, 75);
			$readingHistoryEntry->format = $format;
			$readingHistoryEntry->checkInDate = $checkoutDate;
			$readingHistoryEntry->checkOutDate = $checkoutDate;

			try {
				$readingHistoryEntry->insert();
			} catch (Exception $e) {
				echo("Error importing Reading History Entry $e \n");
				print_r($readingHistoryEntry);
			}
		} else {
			if (empty($readingHistoryEntry->author) && !empty($author)) {
				$readingHistoryEntry->author = substr($author, 0, 75);
				try {
					$readingHistoryEntry->update();
				} catch (Exception $e) {
					echo("Error updating Reading History Entry $e \n");
					print_r($readingHistoryEntry);
				}
			}
		}
		$readingHistoryEntry->__destruct();
		$readingHistoryEntry = null;

		if ($numImports % 2500 == 0) {
			$elapsedTime = time() - $batchStartTime;
			$batchStartTime = time();
			$totalElapsedTime = ceil((time() - $startTime) / 60);
			echo("Processed $numImports Reading History Entries in $elapsedTime seconds ($totalElapsedTime minutes total).\n");
			gc_collect_cycles();
			ob_flush();
			set_time_limit(600);
		}
	}
	$elapsedTime = time() - $batchStartTime;
	$totalElapsedTime = ceil((time() - $startTime) / 60);
	echo("Processed $numImports Reading History Entries in $elapsedTime seconds ($totalElapsedTime minutes total).\n");
	echo("Skipped $numSkipped reading history entries because the title is no longer in the catalog\n");
	fclose($readingHistoryHnd);
	ob_flush();
}

function importNotInterested($startTime, $exportPath, &$existingUsers, &$missingUsers, &$validGroupedWorks, &$invalidGroupedWorks, &$movedGroupedWorks, $bibNumberMap) {
	echo("Starting to import not interested titles\n");
	set_time_limit(600);
	require_once ROOT_DIR . '/sys/LocalEnrichment/NotInterested.php';
	$patronNotInterestedHnd = fopen($exportPath . "patronNotInterested.csv", 'r');
	$numImports = 0;

	$batchStartTime = time();
	$numSkipped = 0;
	while ($patronNotInterestedRow = fgetcsv($patronNotInterestedHnd)) {
		$numImports++;
		//Figure out the user for the review
		$userBarcode = $patronNotInterestedRow[0];
		$userId = getUserIdForBarcode($userBarcode, $existingUsers, $missingUsers);
		if ($userId == -1) {
			continue;
		}

		$dateMarked = $patronNotInterestedRow[1];
		$title = cleancsv($patronNotInterestedRow[2]);
		$author = cleancsv($patronNotInterestedRow[3]);
		$groupedWorkId = $patronNotInterestedRow[4];
		$groupedWorkResources = $patronNotInterestedRow[5];

		if (!validateGroupedWork($groupedWorkId, $title, $author, $validGroupedWorks, $invalidGroupedWorks, $movedGroupedWorks, $groupedWorkResources, $bibNumberMap)) {
			$numSkipped++;
			continue;
		}

		$notInterested = new NotInterested();
		$notInterested->userId = $userId;
		$notInterested->groupedRecordPermanentId = getGroupedWorkId($groupedWorkId, $validGroupedWorks, $movedGroupedWorks);
		if ($notInterested->find(true)) {
			$notInterested->dateMarked = $dateMarked;
			$notInterested->update();
		} else {
			$notInterested->dateMarked = $dateMarked;
			$notInterested->insert();
		}

		if ($numImports % 2500 == 0) {
			$elapsedTime = time() - $batchStartTime;
			$batchStartTime = time();
			$totalElapsedTime = ceil((time() - $startTime) / 60);
			echo("Processed $numImports Not Interested in $elapsedTime seconds ($totalElapsedTime minutes total).\n");
			gc_collect_cycles();
			ob_flush();
		}
	}
	$elapsedTime = time() - $batchStartTime;
	$totalElapsedTime = ceil((time() - $startTime) / 60);
	echo("Processed $numImports Not Interested in $elapsedTime seconds ($totalElapsedTime minutes total).\n");
	echo("Skipped $numSkipped not interested because the title is no longer in the catalog");

	fclose($patronNotInterestedHnd);
}

function importRatingsAndReviews($startTime, $exportPath, &$existingUsers, &$missingUsers, &$validGroupedWorks, &$invalidGroupedWorks, &$movedGroupedWorks, $bibNumberMap) {
	echo("Starting to import ratings and reviews\n");
	set_time_limit(600);
	require_once ROOT_DIR . '/sys/UserLists/UserList.php';
	$patronsRatingsAndReviewsHnd = fopen($exportPath . "patronRatingsAndReviews.csv", 'r');
	$numImports = 0;

	$batchStartTime = time();
	$numSkipped = 0;
	while ($patronsRatingsAndReviewsRow = fgetcsv($patronsRatingsAndReviewsHnd)) {
		$numImports++;
		//Figure out the user for the review
		$userBarcode = $patronsRatingsAndReviewsRow[0];
		$userId = getUserIdForBarcode($userBarcode, $existingUsers, $missingUsers);
		if ($userId == -1) {
			continue;
		}

		$rating = $patronsRatingsAndReviewsRow[1];
		$review = cleancsv($patronsRatingsAndReviewsRow[2]);
		$dateRated = $patronsRatingsAndReviewsRow[3];
		$title = cleancsv($patronsRatingsAndReviewsRow[4]);
		$author = cleancsv($patronsRatingsAndReviewsRow[5]);
		$groupedWorkId = cleancsv($patronsRatingsAndReviewsRow[6]);
		$groupedWorkResources = cleancsv($patronsRatingsAndReviewsRow[7]);

		if (!validateGroupedWork($groupedWorkId, $title, $author, $validGroupedWorks, $invalidGroupedWorks, $movedGroupedWorks, $groupedWorkResources, $bibNumberMap)) {
			$numSkipped++;
			continue;
		}

		require_once ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';
		$userWorkReview = new UserWorkReview();
		try {
			$userWorkReview->groupedRecordPermanentId = getGroupedWorkId($groupedWorkId, $validGroupedWorks, $movedGroupedWorks);
			$userWorkReview->userId = $userId;
			$reviewExists = false;
			if ($userWorkReview->find(true)) {
				$reviewExists = true;
			}
			$userWorkReview->rating = $rating;
			$userWorkReview->review = $review;
			$userWorkReview->dateRated = $dateRated;
			if ($reviewExists) {
				$userWorkReview->update();
			} else {
				$userWorkReview->insert();
			}
		} catch (PDOException $exception) {
			//Continue processing
			echo "Error adding review " . print_r($userWorkReview, true) . "\n";
			if (strpos($exception->getMessage(), "for column 'review'") === false) {
				echo $exception->getMessage() . "\n";
				echo $exception->getTraceAsString() . "\n";
			}
		}
		if ($numImports % 2500 == 0) {
			$elapsedTime = time() - $batchStartTime;
			$batchStartTime = time();
			$totalElapsedTime = ceil((time() - $startTime) / 60);
			echo("Processed $numImports Ratings and Reviews in $elapsedTime seconds ($totalElapsedTime minutes total).\n");
			gc_collect_cycles();
			ob_flush();
		}
	}
	$totalElapsedTime = ceil((time() - $startTime) / 60);
	fclose($patronsRatingsAndReviewsHnd);
	echo("Processed $numImports Ratings and Reviews in $totalElapsedTime minutes.\n");
	echo("Skipped $numSkipped ratings and reviews because the title is no longer in the catalog\n");
}

function importLists($startTime, $exportPath, &$existingUsers, &$missingUsers, &$validGroupedWorks, &$invalidGroupedWorks, &$movedGroupedWorks, $bibNumberMap) {
	echo("Starting to import lists\n");
	global $memoryWatcher;
	$memoryWatcher->logMemory("Start of list import");

	//Create a map of the old pika list id to the new list id in aspen to make sure nothing gets overwritten if this isn't a clean install.
	$pikaToAspenListIds = [];

	set_time_limit(600);
	require_once ROOT_DIR . '/sys/UserLists/UserList.php';
	$patronsListHnd = fopen($exportPath . "patronLists.csv", 'r');
	$numImports = 0;
	$batchStartTime = time();
	$initialStartTime = time();
	while ($patronListRow = fgetcsv($patronsListHnd)) {
		$numImports++;
		//Figure out the user for the list
		$userBarcode = $patronListRow[0];
		$listId = $patronListRow[1];

		$userId = getUserIdForBarcode($userBarcode, $existingUsers, $missingUsers);
		if ($userId == -1) {
			$removedLists[$listId] = $listId;
			continue;
		}

		$listName = cleancsv($patronListRow[2]);
		$listDescription = cleancsv($patronListRow[3]);
		$dateCreated = $patronListRow[4]; //Not sure this is correct, but seems likely
		$public = $patronListRow[5];
		$sort = cleancsv($patronListRow[6]);
		$userList = new UserList();
		$userList->user_id = $userId;
		$userList->title = $listName;
		$listExists = false;
		if ($userList->find(true)) {
			$listExists = true;
		} else {
			$userList->created = $dateCreated;
		}
		$userList->description = $listDescription;
		$userList->public = $public;
		if (empty($sort)) {
			$userList->defaultSort = 'title';
		} else {
			$userList->defaultSort = $sort;
		}
		if ($listExists) {
			//MDN - do delete list entries that already exist within Aspen
//			if (count($userList->getListTitles()) > 0) {
//				$userList->removeAllListEntries(false);
//			}
			$userList->update();
		} else {
			$userList->insert();
		}
		$pikaToAspenListIds[$listId] = $userList->id;

		$userList->__destruct();
		$userList = null;

		if ($numImports % 2500 == 0) {
			gc_collect_cycles();
			ob_flush();
			usleep(10);
			$memoryWatcher->logMemory("Imported $numImports Lists");
			$elapsedTime = time() - $batchStartTime;
			$batchStartTime = time();
			$totalElapsedTime = ceil((time() - $startTime) / 60);
			echo("Processed $numImports Lists in $elapsedTime seconds ($totalElapsedTime minutes total).\n");
		}
	}
	$totalElapsedTime = ceil((time() - $startTime) / 60);
	fclose($patronsListHnd);
	echo("Processed $numImports Lists in $totalElapsedTime minutes.\n");
	echo("Removed " . count($removedLists) . " lists because the user is not valid\n");

	echo("Starting to import list entries\n");
	//Load the list entries
	set_time_limit(600);
	$patronListEntriesHnd = fopen($exportPath . "patronListEntries.csv", 'r');
	$numImports = 0;
	$validGroupedWorks = [];
	$invalidGroupedWorks = [];
	$batchStartTime = time();
	$numSkipped = 0;
	while ($patronListEntryRow = fgetcsv($patronListEntriesHnd)) {
		$numImports++;
		//This needs
		$listId = $patronListEntryRow[1];

		$notes = cleancsv($patronListEntryRow[2]);
		$dateAdded = $patronListEntryRow[3];
		$title = cleancsv($patronListEntryRow[4]);
		$author = cleancsv($patronListEntryRow[5]);
		$groupedWorkId = cleancsv($patronListEntryRow[6]);
		$groupedWorkResources = cleancsv($patronListEntryRow[7]);

		if (array_key_exists($listId, $removedLists)) {
			//Skip this list entry since the list wasn't imported (because the user no longer exists)
			continue;
		} elseif (!array_key_exists($listId, $pikaToAspenListIds)) {
			echo("List $listId has not been imported yet\r\n");
			continue;
		} else {
			$listId = $pikaToAspenListIds[$listId];
		}

		if (!validateGroupedWork($groupedWorkId, $title, $author, $validGroupedWorks, $invalidGroupedWorks, $movedGroupedWorks, $groupedWorkResources, $bibNumberMap)) {
			$numSkipped++;
			continue;
		}

		require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
		$listEntry = new UserListEntry();
		try {
			$listEntry->listId = $listId;
			$listEntry->source = 'GroupedWork';
			$listEntry->sourceId = getGroupedWorkId($groupedWorkId, $validGroupedWorks, $movedGroupedWorks);
			$entryExists = false;
			if ($listEntry->find(true)) {
				$entryExists = true;
			}
			$listEntry->dateAdded = $dateAdded;
			$listEntry->notes = $notes;
			$listEntry->importedFrom = 'Pika';
			if ($entryExists) {
				$listEntry->update(false);
			} else {
				$listEntry->insert(false);
			}
		} catch (PDOException $exception) {
			//Continue processing
			echo "Error adding list entry " . print_r($listEntry, true) . "\n";
			if (strpos($exception->getMessage(), "for column 'notes'") === false) {
				echo $exception->getMessage() . "\n";
				echo $exception->getTraceAsString() . "\n";
			}
		}
		$listEntry->__destruct();
		$listEntry = null;
		if ($numImports % 2500 == 0) {
			gc_collect_cycles();
			$elapsedTime = time() - $batchStartTime;
			$batchStartTime = time();
			$totalElapsedTime = ceil((time() - $startTime) / 60);
			echo("Processed $numImports List Entries in $elapsedTime seconds ($totalElapsedTime minutes total).\n");
			ob_flush();
			set_time_limit(600);
		}
	}
	fclose($patronListEntriesHnd);
	$totalElapsedTime = ceil((time() - $startTime) / 60);
	echo("Processed $numImports List Entries in $totalElapsedTime minutes.\n");
	echo("Skipped $numSkipped list entries because the title is no longer in the catalog\n");

	ob_flush();
}

function importSavedSearches($startTime, $exportPath, &$existingUsers, &$missingUsers, $serverName) {
	if (file_exists($exportPath . 'saved_searches.csv')) {
		echo("Starting to import saved searches\n");
		$savedSearchesHnd = fopen($exportPath . 'saved_searches.csv', 'r');
		$removedSearches = [];
		$numImports = 0;
		$batchStartTime = time();
		//TODO: Do we need to flip the ids of the searches to preserve the id?
		while ($savedSearchRow = fgetcsv($savedSearchesHnd)) {
			$numImports++;
			$userBarcode = $savedSearchRow[0];
			$searchId = $savedSearchRow[1];
			$sessionId = $savedSearchRow[2];
			//$folderId = $savedSearchRow[3]; Folder ID does not get used anymore in Aspen
			$created = $savedSearchRow[4];
			//$title = cleancsv($savedSearchRow[5]); Title does not get used anymore in Aspen
			$saved = $savedSearchRow[6];
			$searchObject = cleancsv($savedSearchRow[7]);
			$searchSource = cleancsv($savedSearchRow[8]);
			$userId = getUserIdForBarcode($userBarcode, $existingUsers, $missingUsers);
			if ($userId == -1) {
				$removedSearches[$searchId] = $searchId;
				continue;
			}

			require_once ROOT_DIR . '/sys/SearchEntry.php';
			$savedSearch = new SearchEntry();
			$savedSearch->id = $searchId;
			$searchExists = false;
			if ($savedSearch->find(true)) {
				$searchExists = true;
			}
			$savedSearch->user_id = $userId;
			$savedSearch->session_id = $sessionId;
			$savedSearch->created = $created;
			$savedSearch->searchSource = $searchSource;
			$savedSearch->search_object = $searchObject;
			$savedSearch->saved = $saved;
			if ($searchExists) {
				$savedSearch->update();
			} else {
				$savedSearch->insert();
			}
			$savedSearch->__destruct();
			$savedSearch = null;

			if ($numImports % 2500 == 0) {
				gc_collect_cycles();
				ob_flush();
				usleep(10);
				$elapsedTime = time() - $batchStartTime;
				$batchStartTime = time();
				$totalElapsedTime = ceil((time() - $startTime) / 60);
				echo("Processed $numImports Saved Searches in $elapsedTime seconds ($totalElapsedTime minutes total).\n");
			}
		}
		fclose($savedSearchesHnd);
		$totalElapsedTime = ceil((time() - $startTime) / 60);
		echo("Processed $numImports Saved Searches in $totalElapsedTime minutes.\n");
		echo("Removed " . count($removedSearches) . " saved searches because the user is not valid\n");
	} else {
		echo("No saved searches provided, skipping\n");
	}
	ob_flush();
}

function importMergedWorks($startTime, $exportPath, &$existingUsers, &$missingUsers, $serverName, $validGroupedWorks, $invalidGroupedWorks, $movedGroupedWorks) {
	if (file_exists($exportPath . 'mergedGroupedWorks.csv')) {
		$aspenAdminUser = new User();
		$aspenAdminUser->source = 'admin';
		$aspenAdminUser->username = 'aspen_admin';
		$aspenAdminUser->find(true);
		$mergedWorksHnd = fopen($exportPath . 'mergedGroupedWorks.csv', 'r');
		$numImports = 0;
		$numSkipped = 0;
		$batchStartTime = time();
		$numRecordsMergedCorrectlyAlready = 0;
		$numRecordsWithAlternateTitlesAdded = 0;
		while ($mergedWorksRow = fgetcsv($mergedWorksHnd)) {
			$numImports++;

			$destinationTitle = cleancsv($mergedWorksRow[0]);
			$destinationAuthor = cleancsv($mergedWorksRow[1]);
			$destinationGroupedWorkID = cleancsv($mergedWorksRow[2]);
			$destinationRecords = cleancsv($mergedWorksRow[3]);

			//Find the work for the given title & author
			if (!validateGroupedWork($destinationGroupedWorkID, $destinationTitle, $destinationAuthor, $validGroupedWorks, $invalidGroupedWorks, $movedGroupedWorks, $destinationRecords)) {
				$numSkipped++;
				continue;
			}

			$aspenGroupedWorkId = getGroupedWorkId($destinationGroupedWorkID, $validGroupedWorks, $movedGroupedWorks);
			$resourcesList = preg_split('/[,;]/', $destinationRecords);
			$allResourcesAttachedToSameRecord = true;
			$alternateTitleAuthors = [];
			foreach ($resourcesList as $resourceId) {
				if (strpos($resourceId, ':') === false) {
					continue;
				}
				[
					$source,
					$id,
				] = explode(':', $resourceId);
				$groupedWorkPrimaryIdentifier = new GroupedWorkPrimaryIdentifier();
				$groupedWorkPrimaryIdentifier->type = $source;
				$groupedWorkPrimaryIdentifier->identifier = $id;
				if ($groupedWorkPrimaryIdentifier->find(true)) {
					$groupedWork = new GroupedWork();
					$groupedWork->id = $groupedWorkPrimaryIdentifier->grouped_work_id;
					if ($groupedWork->find(true)) {
						if ($groupedWork->permanent_id != $aspenGroupedWorkId) {
							$allResourcesAttachedToSameRecord = false;
							$alternateTitleAuthors[$groupedWork->full_title . ':' . $groupedWork->author] = [
								'title' => $groupedWork->full_title,
								'author' => $groupedWork->author,
							];
						}
					}
					$groupedWork->__destruct();
					$groupedWork = null;
				}
				$groupedWorkPrimaryIdentifier->__destruct();
				$groupedWorkPrimaryIdentifier = null;
			}
			if (!$allResourcesAttachedToSameRecord) {
				foreach ($alternateTitleAuthors as $titleAuthor) {
					require_once ROOT_DIR . '/sys/Grouping/GroupedWorkAlternateTitle.php';
					$alternateTitle = new GroupedWorkAlternateTitle();
					$alternateTitle->permanent_id = $aspenGroupedWorkId;
					$alternateTitle->alternateTitle = $titleAuthor['title'];
					$alternateTitle->alternateAuthor = $titleAuthor['author'];
					if (!$alternateTitle->find(true)) {
						$alternateTitle->addedBy = $aspenAdminUser->id;
						$alternateTitle->dateAdded = time();
						$alternateTitle->insert();
					}
				}
				$numRecordsWithAlternateTitlesAdded++;
			} else {
				$numRecordsMergedCorrectlyAlready++;
			}
			//- if found, check all records attached in Aspen (same normalization in Pika and Aspen)
			//  - if a record is not attached to the correct work - create an Alternate Title with the title/author in Aspen
			//- if not found (different title/author normalization in Pika and Aspen)
			//  - figure out which work has the most ils records attached to it and then follow setup Alternate titles for everything else.
			//  - If nothing has more than anything else, pick the first ils record, or the first record if no ils titles.

			if ($numImports % 2500 == 0) {
				gc_collect_cycles();
				ob_flush();
				usleep(10);
				$elapsedTime = time() - $batchStartTime;
				$batchStartTime = time();
				$totalElapsedTime = ceil((time() - $startTime) / 60);
				echo("Processed $numImports Merged Works in $elapsedTime seconds ($totalElapsedTime minutes total).\n");
			}
		}
		fclose($mergedWorksHnd);
		$totalElapsedTime = ceil((time() - $startTime) / 60);
		echo("Processed $numImports Merged Works in $totalElapsedTime minutes total.\n");
		echo("$numRecordsMergedCorrectlyAlready works were already merged correctly.\n");
		echo("$numRecordsWithAlternateTitlesAdded works had alternate titles added to them.\n");
		echo("Skipped $numSkipped merged works because the title is no longer in the catalog");
	} else {
		echo("No merged grouped works provided, skipping\n");
	}
	ob_flush();
}

function importListWidgets($startTime, $exportPath, $existingUsers, $missingUsers, $serverName) {
	if (file_exists($exportPath . 'list_widgets.csv') && file_exists($exportPath . 'list_widget_lists.csv')) {
		$listWidgetHnd = fopen($exportPath . 'list_widgets.csv', 'r');
		$listWidgetListsHnd = fopen($exportPath . 'list_widget_lists.csv', 'r');
		$numListWidgetImports = 0;
		$numListWidgetListImports = 0;
		$batchStartTime = time();

		require_once ROOT_DIR . '/sys/LocalEnrichment/CollectionSpotlight.php';
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';

		//Import Collection spotlights
		while ($listWidgetRow = fgetcsv($listWidgetHnd)) {
			$numListWidgetImports++;
			$curCol = 0;
			$widgetId = $listWidgetRow[$curCol++];
			$widgetName = $listWidgetRow[$curCol++];
			$collectionSpotlight = new CollectionSpotlight();
			$collectionSpotlight->id = $widgetId;
			$spotlightExists = false;
			if ($collectionSpotlight->find(true)) {
				$spotlightExists = true;
				if ($widgetName != $collectionSpotlight->name) {
					echo("Widget ID $widgetId changed names was $widgetName, now it's {$collectionSpotlight->name} using name from Pika\n");
					$collectionSpotlight->name = $widgetName;
				}
			} else {
				$collectionSpotlight->name = $widgetName;
			}

			$collectionSpotlight->description = $listWidgetRow[$curCol++];
			$collectionSpotlight->showTitleDescriptions = $listWidgetRow[$curCol++];
			$collectionSpotlight->onSelectCallback = $listWidgetRow[$curCol++];
			$collectionSpotlight->customCss = $listWidgetRow[$curCol++];
			$collectionSpotlight->listDisplayType = $listWidgetRow[$curCol++];
			$collectionSpotlight->autoRotate = $listWidgetRow[$curCol++];
			$collectionSpotlight->showMultipleTitles = $listWidgetRow[$curCol++];
			$collectionSpotlight->libraryId = $listWidgetRow[$curCol++];
			$collectionSpotlight->style = $listWidgetRow[$curCol++];
			$collectionSpotlight->coverSize = $listWidgetRow[$curCol++];
			$collectionSpotlight->showRatings = $listWidgetRow[$curCol++];
			$collectionSpotlight->showTitle = $listWidgetRow[$curCol++];
			$collectionSpotlight->showAuthor = $listWidgetRow[$curCol++];
			$collectionSpotlight->showViewMoreLink = $listWidgetRow[$curCol++];
			$collectionSpotlight->viewMoreLinkMode = $listWidgetRow[$curCol++];
			$collectionSpotlight->showListWidgetTitle = $listWidgetRow[$curCol++];
			$collectionSpotlight->numTitlesToShow = $listWidgetRow[$curCol];

			if ($spotlightExists) {
				$collectionSpotlight->update();

				//Delete all lists from all collection spotlights and rebuild them later
				$allSpotlightLists = new CollectionSpotlightList();
				$allSpotlightLists->collectionSpotlightId = $collectionSpotlight->id;
				$allSpotlightLists->delete(true);
			} else {
				$collectionSpotlight->id = $widgetId;
				$collectionSpotlight->insert();
			}
			$collectionSpotlight->__destruct();
			$collectionSpotlight = null;
		}
		while ($listWidgetListRow = fgetcsv($listWidgetListsHnd)) {
			$numListWidgetListImports++;
			$curCol = 0;
			$listWidgetListWidgetListId = $listWidgetListRow[$curCol++];
			$listWidgetId = $listWidgetListRow[$curCol++];
			$collectionSpotlightList = new CollectionSpotlightList();
			$collectionSpotlightList->id = $listWidgetListWidgetListId;
			$widgetListExists = false;
			if ($collectionSpotlightList->find(true)) {
				$widgetListExists = true;
			}
			$collectionSpotlightList->collectionSpotlightId = $listWidgetId;
			$collectionSpotlightList->weight = $listWidgetListRow[$curCol++];
			$collectionSpotlightList->displayFor = $listWidgetListRow[$curCol++];
			$collectionSpotlightList->name = $listWidgetListRow[$curCol++];
			$source = $listWidgetListRow[$curCol];
			[
				$type,
				$identifier,
			] = explode(':', $source);
			if ($type == 'list') {
				$collectionSpotlightList->source = 'List';
				$collectionSpotlightList->sourceListId = $identifier;
				//Validate that the list exists
				$list = new UserList();
				$list->id = $identifier;
				if (!$list->find(true)) {
					echo("Could not find list $identifier for widget list $listWidgetListWidgetListId in $listWidgetId\n");
					continue;
				}
			} elseif ($type == 'search') {
				/** @var SearchObject_AbstractGroupedWorkSearcher $searcher */
				$searcher = SearchObjectFactory::initSearchObject('GroupedWork');
				$savedSearch = $searcher->restoreSavedSearch($identifier, false, true);
				if ($savedSearch !== false) {
					$collectionSpotlightList->updateFromSearch($savedSearch);
				} else {
					echo("Could not load saved search $identifier for collection spotlight list $listWidgetListWidgetListId in $listWidgetId\n");
					continue;
				}
			}

			if (empty($collectionSpotlightList->defaultSort)) {
				$collectionSpotlightList->defaultSort = 'relevance';
			}

			if ($widgetListExists) {
				$collectionSpotlightList->update();
			} else {
				$collectionSpotlightList->insert();
			}
		}

		//Delete any spotlights that have no lists
		$collectionSpotlight = new CollectionSpotlight();
		$collectionSpotlight->find();
		while ($collectionSpotlight->fetch()) {
			if ($collectionSpotlight->getNumLists() == 0) {
				$spotlightToDelete = new CollectionSpotlight();
				$spotlightToDelete->id = $collectionSpotlight->id;
				$spotlightToDelete->delete();
				echo("Deleted collection spotlight {$collectionSpotlight->id} because it had no lists\n");
			} elseif (preg_match('/delete me|please delete/i', $collectionSpotlight->name)) {
				$spotlightToDelete = new CollectionSpotlight();
				$spotlightToDelete->id = $collectionSpotlight->id;
				$spotlightToDelete->delete();
				echo("Deleted collection spotlight {$collectionSpotlight->id} because it had was named delete me or please delete\n");
			}
		}
		fclose($listWidgetHnd);
		fclose($listWidgetListsHnd);
		$elapsedTime = time() - $batchStartTime;
		$totalElapsedTime = ceil((time() - $startTime) / 60);
		echo("Processed $numListWidgetImports List Widgets and $numListWidgetListImports List Widget Lists in $elapsedTime seconds ($totalElapsedTime minutes total).\n");
	} else {
		echo("No list widgets provided, skipping\n");
	}
	ob_flush();
}

function cleancsv($field) {
	if ($field == '\N') {
		return null;
	}
	$field = str_replace('\"', '"', $field);
	$field = str_replace("\r\\\n", '<br/>', $field);
	$field = str_replace("\\\n", '<br/>', $field);
	return $field;
}

function validateGroupedWork($groupedWorkId, $title, $author, &$validGroupedWorks, &$invalidGroupedWorks, &$movedGroupedWorks, $groupedWorkResources, $bibNumberMap) {
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
		$groupedWorkResourceArray = preg_split('/[,;]/', $groupedWorkResources);
		$groupedWorkValid = false;
		foreach ($groupedWorkResourceArray as $identifier) {
			if (strlen(trim($identifier)) == 0) {
				continue;
			}
			if (strpos($identifier, ':') === false) {
				echo("Identifier $identifier did not have a source list of resources = $groupedWorkResources\n");
				continue;
			}
			[
				$source,
				$id,
			] = explode(':', $identifier);
			$source = trim($source);
			$id = trim($id);
			$groupedWorkPrimaryIdentifier = new GroupedWorkPrimaryIdentifier();
			$groupedWorkPrimaryIdentifier->type = $source;
			if ($source == 'hoopla') {
				$id = str_replace('MWT', '', $id);
			}
			if (array_key_exists($id, $bibNumberMap)) {
				$id = $bibNumberMap[$id];
			}else if (array_key_exists('ils:' . $id, $bibNumberMap)) {
				$id = $bibNumberMap['ils:' . $id];
			}
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
function loadBibNumberMap($exportPath, &$bibNumberMap){
	if (file_exists($exportPath . "bibNumMap.csv")) {
		$bibNumberMapHnd = fopen($exportPath . 'bibNumMap.csv', 'r');
		while ($bibNumberMapRow = fgetcsv($bibNumberMapHnd)) {
			if ($bibNumberMapRow[1]) {
				$bibNumberMap[$bibNumberMapRow[0]] = $bibNumberMapRow[1];
			}
		}
	}
}

/**
 * @param string $exportPath
 * @param string $file
 */
function validateFileExists(string $exportPath, string $file): void {
	if (!file_exists($exportPath . $file)) {
		echo("Could not find $file in export path " . $exportPath . "\n");
		die();
	}
}

function getGroupedWorkId($groupedWorkId, $validGroupedWorks, $movedGroupedWorks) {
	if (array_key_exists($groupedWorkId, $validGroupedWorks)) {
		return $validGroupedWorks[$groupedWorkId];
	} else {
		return $movedGroupedWorks[$groupedWorkId];
	}
}

/**
 * Switch any users that have a positive id in the user table to have a negative id to avoid conflicts on import
 */
function flipUserIds() {
	global $aspen_db;
	$aspen_db->query("UPDATE user set id = -id WHERE id > 1");
	$aspen_db->query("UPDATE grouped_work_alternate_titles set addedBy = -addedBy WHERE addedBy > 1");
	$aspen_db->query("UPDATE materials_request set createdBy = -createdBy WHERE createdBy > 1");
	$aspen_db->query("UPDATE materials_request set assignedTo = -assignedTo WHERE assignedTo > 1");
	$aspen_db->query("UPDATE search set user_id = -user_id WHERE user_id > 1");
	$aspen_db->query("UPDATE user_cloud_library_usage set userId = -userId WHERE userId > 1");
	$aspen_db->query("UPDATE user_hoopla_usage set userId = -userId WHERE userId > 1");
	$aspen_db->query("UPDATE user_ils_usage set userId = -userId WHERE userId > 1");
	$aspen_db->query("UPDATE user_list set user_id = -user_id WHERE user_id > 1");
	$aspen_db->query("UPDATE user_not_interested set userId = -userId WHERE userId > 1");
	$aspen_db->query("UPDATE user_open_archives_usage set userId = -userId WHERE userId > 1");
	$aspen_db->query("UPDATE user_overdrive_usage set userId = -userId WHERE userId > 1");
	$aspen_db->query("UPDATE user_payments set userId = -userId WHERE userId > 1");
	$aspen_db->query("UPDATE user_rbdigital_usage set userId = -userId WHERE userId > 1");
	$aspen_db->query("UPDATE user_reading_history_work set userId = -userId WHERE userId > 1");
	$aspen_db->query("UPDATE user_roles set userId = -userId WHERE userId > 1");
	$aspen_db->query("UPDATE user_sideload_usage set userId = -userId WHERE userId > 1");
	$aspen_db->query("UPDATE user_staff_settings set userId = -userId WHERE userId > 1");
	$aspen_db->query("UPDATE user_work_review set userId = -userId WHERE userId > 1");
}
