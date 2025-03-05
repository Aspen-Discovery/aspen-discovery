<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';
require_once ROOT_DIR . '/services/API/ListAPI.php';
require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';

// instantiate class with api key
require_once ROOT_DIR . '/sys/NYTApi.php';

require_once ROOT_DIR . '/sys/Enrichment/NewYorkTimesSetting.php';
require_once ROOT_DIR . '/sys/UserLists/NYTUpdateLogEntry.php';

//Create a NYTUpdateLogEntry
$nytUpdateLog = new NYTUpdateLogEntry();
$nytUpdateLog->startTime = time();
$nytUpdateLog->insert();
$nytUpdateLog->addNote("Starting NYT list update process.");

set_time_limit(0);

global $configArray;
$nytSettings = new NewYorkTimesSetting();
if (!$nytSettings->find(true)) {
	$nytUpdateLog->addError("No settings found, not updating lists.");
} else {
	$nytUpdateLog->addExtensiveNote("Found NYT settings, API key: " . substr($nytSettings->booksApiKey, 0, 4) . "...");

	// Check if we're forcing a full update
	$forceFullUpdate = $nytSettings->runFullUpdate;
	if ($forceFullUpdate) {
		$nytUpdateLog->addNote("Force Full Update enabled; all lists will be completely rebuilt regardless of last modified date.");
	}

	//Pass the log entry to the API, so we can update it there
	$nyt_api = new NYTApi($nytSettings->booksApiKey);

	$retry = true;
	$numTries = 0;
	$availableLists = null;
	while ($retry == true) {
		$retry = false;
		$numTries++;
		$nytUpdateLog->addExtensiveNote("Retrieving available NYT lists (attempt $numTries).");
		//Get the raw response from the API with a list of all the names
		$availableListsRaw = $nyt_api->get_list('names');
		//Convert into an object that can be processed
		$availableLists = json_decode($availableListsRaw);
		if (empty($availableLists->status) || $availableLists->status != "OK") {
			if (!empty($availableLists->fault)) {
				if (strpos($availableLists->fault->faultstring, 'quota violation')) {
					$retry = ($numTries <= 3);
					if ($retry) {
						$nytUpdateLog->addExtensiveNote("Hit quota limit, retrying after sleep (attempt $numTries).");
						sleep(rand(60, 300));
					} else {
						if ($nytUpdateLog != null) {
							$nytUpdateLog->addError("Did not get a good response from the API. {$availableLists->fault->faultstring}.");
						}
					}
				} else {
					if ($nytUpdateLog != null) {
						$nytUpdateLog->addError("Did not get a good response from the API. {$availableLists->fault->faultstring}.");
					}
				}
			} else {
				if ($nytUpdateLog != null) {
					$nytUpdateLog->addError("Did not get a good response from the API");
				}
			}
		} else {
			$nytUpdateLog->addNote("Successfully retrieved " . count($availableLists->results) . " available NYT lists.");
		}
	}

	$listAPI = new ListAPI();

	if ($availableLists != null && isset($availableLists->results)) {
		$prevYear = date("Y-m-d", strtotime("-1 year"));
		$allListsNames = [];
		foreach ($availableLists->results as $availableList) {
			if ($availableList->newest_published_date > $prevYear) {
				$allListsNames[] = $availableList->list_name_encoded;
				$nytUpdateLog->addExtensiveNote("List '{$availableList->display_name}' (encoded: {$availableList->list_name_encoded}) is current with newest date: {$availableList->newest_published_date}.");
			} else {
				$nytUpdateLog->addExtensiveNote("Skipping list '{$availableList->display_name}' (encoded: {$availableList->list_name_encoded}) as it's older than one year; newest date: {$availableList->newest_published_date}.");
			}
		}
		$nytUpdateLog->numLists = count($allListsNames);
		$nytUpdateLog->update();
		$nytUpdateLog->addNote("Processing " . count($allListsNames) . " NYT lists that are newer than $prevYear.");

		foreach ($allListsNames as $listName) {
			$nytUpdateLog->addExtensiveNote("Starting update for list: $listName.");
			try {
				$result = $listAPI->createUserListFromNYT($listName, $nytUpdateLog, $forceFullUpdate);
				$nytUpdateLog->addNote("Finished update for list: $listName - Success: " . ($result['success'] ? 'Yes' : 'No') . ", Message: " . $result['message'] . ".");
			} catch (Exception $e) {
				$nytUpdateLog->addError("Error updating $listName: " . $e->getMessage());
			}
			$nytUpdateLog->lastUpdate = time();
			$nytUpdateLog->update();
			//Make sure we don't hit our quota.  Wait between updates
			sleep(7);
		}
	} else {
		$nytUpdateLog->addExtensiveError("No available lists found or invalid response structure.");
	}

	$nyt_api = null;

	// Reset the force full update flag if it was enabled
	if ($forceFullUpdate) {
		$nytSettings->runFullUpdate = 0;
		$nytSettings->update();
		$nytUpdateLog->addNote("Force Full Update setting has been reset.");
	}

	if ($nytSettings->enableExtensiveLogging) {
		$nytSettings->enableExtensiveLogging = 0;
		$nytSettings->update();
		$nytUpdateLog->addNote("Enable Extensive Logging setting has been reset.");
	}
}

$nytUpdateLog->addNote("Finished updating NYT lists.");
$nytUpdateLog->endTime = time();
$nytUpdateLog->update();

$nytSettings->__destruct();
$nytSettings = null;

$nytUpdateLog->__destruct();
$nytUpdateLog = null;

global $aspen_db;
$aspen_db = null;

die();