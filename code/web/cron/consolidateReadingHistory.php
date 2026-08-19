<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

require_once ROOT_DIR . '/services/API/SystemAPI.php';
require_once ROOT_DIR . '/sys/Administration/BackgroundProcess.php';
require_once ROOT_DIR . '/sys/SystemVariables.php';
require_once ROOT_DIR . '/sys/DBMaintenance/hoopla_version2_updates.php';

$backgroundProcess = null;
if ($argc > 2) {
	$backgroundProcessId = $argv[2];
	$backgroundProcess = new BackgroundProcess();
	$backgroundProcess->id = $backgroundProcessId;
	if (!$backgroundProcess->find(true)) {
		$backgroundProcess = null;
		echo("Could not find the specified background process\n");
		die();
	} elseif (!$backgroundProcess->isRunning) {
		$backgroundProcess->addNote('Error, attempted to restart previously completed background process');
		die();
	}
}
$barcodeToUpdate = 'all';
if ($argc > 3) {
	$barcodeToUpdate = $argv[3];
}

logMessage("Consolidating Reading History for $barcodeToUpdate.", $backgroundProcess);

$user = new User();
$user->trackReadingHistory = 1;
$user->initialReadingHistoryLoaded = 1;
if ($barcodeToUpdate != 'all') {
	$user->ils_barcode = $barcodeToUpdate;
}
$numUsersToUpdate = $user->count();
if ($barcodeToUpdate != 'all') {
	if ($numUsersToUpdate == 0) {
		finish("Could not find user to update", $backgroundProcess);
	}
}else{
	logMessage("There are $numUsersToUpdate users to consolidate reading history for.", $backgroundProcess);
}

$readingHistoryDB2 = new ReadingHistoryEntry();
$readingHistoryDB2->whereAdd('deleted =  0');
$numAtStartEntries = $readingHistoryDB2->count();
logMessage("At the start of processing there are $numAtStartEntries Entries in the database.", $backgroundProcess);

$user->find();
$numProcessed = 0;
while ($user->fetch()) {
	$consolidationNotes = '';
	$numEntriesMerged = 0;
	$numDuplicateEntriesDeleted = 0;
	$numFullyContainedEntriesDeleted = 0;
	$numEntriesOverlappingMerged = 0;

	//Get a list of all reading history entries for the user grouped based on how they will display
	require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
	$readingHistoryDB = new ReadingHistoryEntry();
	$readingHistoryDB->userId = $user->id;
	$readingHistoryDB->whereAdd('deleted =  0');
	if (!empty($filter)) {
		$escapedFilter = $readingHistoryDB->escape('%' . $filter . '%');
		$readingHistoryDB->whereAdd("title LIKE $escapedFilter OR author LIKE $escapedFilter OR format LIKE $escapedFilter");
	}
	$readingHistoryDB->selectAdd();
	$readingHistoryDB->selectAdd('MAX(id) as id');
	$readingHistoryDB->selectAdd('groupedWorkPermanentId');
	$readingHistoryDB->selectAdd('MAX(title) as title');
	$readingHistoryDB->selectAdd('MAX(author) as author');
	$readingHistoryDB->selectAdd('MAX(checkInDate) as checkInDate');
	$readingHistoryDB->selectAdd('MAX(checkOutDate) as checkOutDate');
	$readingHistoryDB->selectAdd('SUM(CASE WHEN checkInDate IS NULL THEN 1 END) as checkedOut');
	$readingHistoryDB->selectAdd('COUNT(id) as timesUsed');
	$readingHistoryDB->selectAdd('GROUP_CONCAT(DISTINCT(format)) as format');
	// Group by groupedWorkPermanentId to consolidate entries with the same work
	// but different title/author punctuation variations. For NULL permanent IDs,
	// group by title and author to prevent unrelated items from merging.
	$readingHistoryDB->groupBy([
		'groupedWorkPermanentId',
		'CASE WHEN groupedWorkPermanentId IS NULL THEN title ELSE NULL END',
		'CASE WHEN groupedWorkPermanentId IS NULL THEN author ELSE NULL END'
	]);

	//Log the count of the number of entries that will display
	$numTitles = $readingHistoryDB->count();

	//Log the count of the total individual checkouts
	$readingHistoryDB2 = new ReadingHistoryEntry();
	$readingHistoryDB2->userId = $user->id;
	$readingHistoryDB2->whereAdd('deleted =  0');
	$numEntries = $readingHistoryDB2->count();
	logMessage("User $user->ils_barcode has a total of $numTitles Works, $numEntries Entries in their reading history", $backgroundProcess);

	//Loop through all reading history groups
	$readingHistoryDB->find();
	while ($readingHistoryDB->fetch()) {
		$detailRecords = [];
		$checkoutsForReadingHistoryEntry = new ReadingHistoryEntry();
		$checkoutsForReadingHistoryEntry->userId = $user->id;
		$checkoutsForReadingHistoryEntry->deleted = 0;

		if (!empty($readingHistoryDB->groupedWorkPermanentId)) {
			$checkoutsForReadingHistoryEntry->groupedWorkPermanentId = $readingHistoryDB->groupedWorkPermanentId;
		} else {
			// For entries without a permanent ID, fall back to title and author matching.
			$checkoutsForReadingHistoryEntry->title = $readingHistoryDB->title;
			$checkoutsForReadingHistoryEntry->author = $readingHistoryDB->author;
		}
		$checkoutsForReadingHistoryEntry->orderBy('checkOutDate ASC');
		$checkoutsForReadingHistoryEntry->find();

		//Loop through all details to see if they represent consecutive checkouts of the same title
		$lastReadingHistoryEntryCheckout = null;
		$allCheckoutsForReadingHistoryEntry = $checkoutsForReadingHistoryEntry->fetchAll();
		foreach ($allCheckoutsForReadingHistoryEntry as $checkoutsForReadingHistoryEntry) {
			if ($lastReadingHistoryEntryCheckout == null) {
				$lastReadingHistoryEntryCheckout = $checkoutsForReadingHistoryEntry;
			}else{
				$sourceMatches = false;
				if ($lastReadingHistoryEntryCheckout->source == $checkoutsForReadingHistoryEntry->source) {
					if ($lastReadingHistoryEntryCheckout->sourceId == $checkoutsForReadingHistoryEntry->sourceId) {
						$sourceMatches = true;
					}else {
						//There is some old data in the database which stored source as id 1 incorrectly.
						if ($lastReadingHistoryEntryCheckout->source == 'ils' && $lastReadingHistoryEntryCheckout->sourceId == '1') {
							$sourceMatches = true;
						}elseif ($checkoutsForReadingHistoryEntry->source == 'ils' && $checkoutsForReadingHistoryEntry->sourceId == '1') {
							$sourceMatches = true;
						}
					}
				}
				if ($sourceMatches) {
					//This is the same thing that was checked out previously, see if the last check in date is the same as the new checkout date
					$lastCheckInDate = date('Y-m-d', $lastReadingHistoryEntryCheckout->checkInDate);
					$lastCheckOutDate = date('Y-m-d', $lastReadingHistoryEntryCheckout->checkOutDate);
					$currentCheckInDate = date('Y-m-d', $checkoutsForReadingHistoryEntry->checkInDate);
					$currentCheckOutDate = date('Y-m-d', $checkoutsForReadingHistoryEntry->checkOutDate);
					if ($lastCheckInDate == $currentCheckInDate && $lastCheckOutDate == $currentCheckOutDate) {
						//This is an exact match, and we should remove the second entry
						$checkoutEntryToDelete = new ReadingHistoryEntry();
						$checkoutEntryToDelete->id = $checkoutsForReadingHistoryEntry->id;
						$checkoutEntryToDelete->delete();
						$numDuplicateEntriesDeleted++;
					}else if ($lastCheckInDate == $currentCheckOutDate) {
						//The two entries are consecutive (checked out on the same day the previous was checked in)
						//Extend the check in date for the user based on the consecutive checkout
						$lastReadingHistoryEntryCheckout->checkInDate = $checkoutsForReadingHistoryEntry->checkInDate;
						$lastReadingHistoryEntryCheckout->update();
						//The second entry can be deleted
						$checkoutEntryToDelete = new ReadingHistoryEntry();
						$checkoutEntryToDelete->id = $checkoutsForReadingHistoryEntry->id;
						$checkoutEntryToDelete->delete();
						$numEntriesMerged++;
					}else if ($lastReadingHistoryEntryCheckout->checkOutDate <= $checkoutsForReadingHistoryEntry->checkOutDate && $lastReadingHistoryEntryCheckout->checkInDate >= $checkoutsForReadingHistoryEntry->checkInDate) {
						//The second entry is fully contained in the first, remove the second
						$checkoutEntryToDelete = new ReadingHistoryEntry();
						$checkoutEntryToDelete->id = $checkoutsForReadingHistoryEntry->id;
						$checkoutEntryToDelete->delete();
						$numFullyContainedEntriesDeleted++;
					}else if ($lastReadingHistoryEntryCheckout->checkOutDate <= $checkoutsForReadingHistoryEntry->checkOutDate && $checkoutsForReadingHistoryEntry->checkOutDate <= $lastReadingHistoryEntryCheckout->checkInDate) {
						//The second record was checked out while the first was checked out, treat them the same.
						if ($checkoutsForReadingHistoryEntry->checkInDate > $lastReadingHistoryEntryCheckout->checkInDate) {
							//The second entry ends later than the first, extend the check in date
							$lastReadingHistoryEntryCheckout->checkInDate = $checkoutsForReadingHistoryEntry->checkInDate;
							$lastReadingHistoryEntryCheckout->update();
						}

						//The second entry check in date is within the checkout period for the first, remove the second
						$checkoutEntryToDelete = new ReadingHistoryEntry();
						$checkoutEntryToDelete->id = $checkoutsForReadingHistoryEntry->id;
						$checkoutEntryToDelete->delete();
						$numEntriesOverlappingMerged++;
					}else{
						//This should not be merged reset the last reading history entry
						$lastReadingHistoryEntryCheckout = $checkoutsForReadingHistoryEntry;
					}
				}else{
					$lastReadingHistoryEntryCheckout = $checkoutsForReadingHistoryEntry;
				}
			}
		}
	}

	logMessage("  $numEntriesMerged merged with previous entry, $numFullyContainedEntriesDeleted were fully contained, $numEntriesOverlappingMerged were overlapping, $numDuplicateEntriesDeleted exact match.", $backgroundProcess);

	//Log the count of the total individual checkouts
	$readingHistoryDB2 = new ReadingHistoryEntry();
	$readingHistoryDB2->userId = $user->id;
	$readingHistoryDB2->whereAdd('deleted =  0');
	$numEntries = $readingHistoryDB2->count();
	logMessage("  now has $numEntries Entries in their reading history", $backgroundProcess);

	$numProcessed++;
}

$readingHistoryDB2 = new ReadingHistoryEntry();
$readingHistoryDB2->whereAdd('deleted =  0');
$numEntriesAtEnd = $readingHistoryDB2->count();
logMessage("At the end of processing there are $numEntriesAtEnd Entries in the database.", $backgroundProcess);
$numEntriesRemoved = $numAtStartEntries - $numEntriesAtEnd;
logMessage("A total of $numEntriesRemoved entries were removed.", $backgroundProcess);

finish('Finished consolidating reading history.', $backgroundProcess);

function logMessage (string $message, ?BackgroundProcess $backgroundProcess) : void {
	if ($backgroundProcess !== null) {
		$backgroundProcess->addNote('[' . date('Y M d H:i:s') . '] ' . $message);
	} else {
		echo $message . PHP_EOL;
	}
}

function finish (string $message, ?BackgroundProcess $backgroundProcess) : never {
	logMessage($message, $backgroundProcess);
	if ($backgroundProcess !== null) {
		$backgroundProcess->endProcess(null);
	}
	die();
}
