<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';
require_once ROOT_DIR . '/services/API/ListAPI.php';

// instantiate class with api key
require_once ROOT_DIR . '/sys/NYTApi.php';

require_once ROOT_DIR . '/sys/Enrichment/NewYorkTimesSetting.php';
require_once ROOT_DIR . '/sys/UserLists/NYTUpdateLogEntry.php';
require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';

//Create a NYTUpdateLogEntry
$nytUpdateLog = new NYTUpdateLogEntry();
$nytUpdateLog->startTime = time();
$nytUpdateLog->insert();

set_time_limit(0);

global $configArray;
$nytSettings = new NewYorkTimesSetting();
if (!$nytSettings->find(true)) {
	$nytUpdateLog->addError("No settings found, not updating lists");
} else {
	//Pass the log entry to the API, so we can update it there
	$nyt_api = NYTApi::getNYTApi($nytSettings->booksApiKey);

	$availableLists = null;
	//Get the raw response from the API with a list of all the names. Now that we get everything in one pass, we will only try once
	$availableLists = $nyt_api->getListsOverview();
	if (empty($availableLists)) {
		if ($nytUpdateLog != null) {
			$nytUpdateLog->addError("Did not get a good response from the API");
		}
	} else{
		//Record the number of lists to be processed
		$nytUpdateLog->numLists = count($availableLists);
		$nytUpdateLog->update();

		$listAPI = new ListAPI();
		foreach ($availableLists as $list) {
			$listName = $list->display_name;
			if (!empty($listName)) {
				try {
					$listAPI->createUserListFromNYT($list->list_name_encoded, $nytUpdateLog);
				} catch (Exception $e) {
					$nytUpdateLog->addError("Error updating $listName " . $e->getMessage());
				}
				$nytUpdateLog->lastUpdate = time();
				$nytUpdateLog->update();
			}
		}
	}

	$nyt_api = null;
}

$nytUpdateLog->addNote("Finished updating lists");
$nytUpdateLog->endTime = time();
$nytUpdateLog->update();

$nytSettings->__destruct();
$nytSettings = null;

$nytUpdateLog->__destruct();
$nytUpdateLog = null;

global $aspen_db;
$aspen_db = null;

die();