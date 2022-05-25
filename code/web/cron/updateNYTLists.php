<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';
require_once ROOT_DIR . '/services/API/ListAPI.php';

// instantiate class with api key
require_once ROOT_DIR . '/sys/NYTApi.php';

require_once ROOT_DIR . '/sys/Enrichment/NewYorkTimesSetting.php';
require_once ROOT_DIR . '/sys/UserLists/NYTUpdateLogEntry.php';

//Create a NYTUpdateLogEntry
$nytUpdateLog = new NYTUpdateLogEntry();
$nytUpdateLog->startTime = time();
$nytUpdateLog->insert();

set_time_limit(0);

global $configArray;
$nytSettings = new NewYorkTimesSetting();
if (!$nytSettings->find(true)) {
	$nytUpdateLog->addError("No settings found, not updating lists");
}else{
	//Pass the log entry to the API so we can update it there
	$nyt_api = new NYTApi($nytSettings->booksApiKey);

	$retry = true;
	$numTries = 0;
	$availableLists = null;
	while ($retry == true) {
		$retry = false;
		$numTries++;
		//Get the raw response from the API with a list of all the names
		$availableListsRaw = $nyt_api->get_list('names');
		//Convert into an object that can be processed
		$availableLists = json_decode($availableListsRaw);
		if (empty($availableLists->status) || $availableLists->status != "OK") {
			if (!empty($availableLists->fault)) {
				if (strpos($availableLists->fault->faultstring, 'quota violation')) {
					$retry = ($numTries <= 3);
					if ($retry){
						sleep(rand(60, 300));
					}else{
						if ($nytUpdateLog != null) {
							$nytUpdateLog->addError("Did not get a good response from the API. {$availableLists->fault->faultstring}");
						}
					}
				} else {
					if ($nytUpdateLog != null) {
						$nytUpdateLog->addError("Did not get a good response from the API. {$availableLists->fault->faultstring}");
					}
				}
			} else {
				if ($nytUpdateLog != null) {
					$nytUpdateLog->addError("Did not get a good response from the API");
				}
			}
		}
	}

	$listAPI = new ListAPI();

	if ($availableLists != null && isset($availableLists->results)) {
		$allListsNames = [];
		foreach ($availableLists->results as $listInfo) {
			$allListsNames[] = $listInfo->list_name_encoded;
		}
		$nytUpdateLog->numLists = count($allListsNames);
		$nytUpdateLog->update();

		foreach ($allListsNames as $listName) {

			try {
				$listAPI->createUserListFromNYT($listName, $nytUpdateLog);
			} catch (Exception $e) {
				$nytUpdateLog->addError("Error updating $listName " . $e->getMessage());
			}
			$nytUpdateLog->lastUpdate = time();
			$nytUpdateLog->update();
			//Make sure we don't hit our quota.  Wait between updates
			sleep(7);
		}
	}
}

$nytUpdateLog->addNote("Finished updating lists");
$nytUpdateLog->endTime = time();
$nytUpdateLog->update();