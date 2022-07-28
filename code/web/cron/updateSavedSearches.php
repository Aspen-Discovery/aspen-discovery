<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

require_once ROOT_DIR . '/sys/SearchEntry.php';
require_once ROOT_DIR . '/sys/SearchUpdateLogEntry.php';

//Create a log entry
$searchUpdateLogEntry = new SearchUpdateLogEntry();
$searchUpdateLogEntry->startTime = time();
$searchUpdateLogEntry->insert();

set_time_limit(0);

//Get a list of all saved searches
$search = new SearchEntry();
$search->saved = 1;
$search->searchSource = 'local';
$search->find();
if ($search->getNumResults() > 0){
	$searchUpdateLogEntry->numSearches = $search->getNumResults();
	$searchUpdateLogEntry->update();
	$allSearches = $search->fetchAll('id');
	$numProcessed = 0;
	foreach ($allSearches as $searchId){
		$searchEntry = new SearchEntry();
		$searchEntry->id = $searchId;
		if ($searchEntry->find(true)) {

			$searchObject = SearchObjectFactory::initSearchObject();
			$size = strlen($searchEntry->search_object);
			$minSO = unserialize($searchEntry->search_object);
			$searchObject = SearchObjectFactory::deminify($minSO);

			$searchObject->addFilter('time_since_added:Week');
			$searchObject->setFieldsToReturn('id');
			$searchObject->setLimit(100);

			$searchResult = $searchObject->processSearch(false,false);
			if (!$searchResult instanceof AspenError && empty($searchResult['error'])) {
				$numResults = $searchObject->getResultTotal();
				$hasNewResults = $numResults > 0;
				$searchEntry->hasNewResults = $hasNewResults;
				$searchEntry->update();
				if ($searchEntry->update() > 0){
					$searchUpdateLogEntry->numUpdated++;
					if ($hasNewResults){
						//TODO: Trigger notification here
					}
				}
			}else{
				if ($searchEntry->hasNewResults) {
					$searchEntry->hasNewResults = false;
					$searchEntry->update();
				}
			}
		}
		$numProcessed++;
		if ($numProcessed % 100 == 0){
			$searchUpdateLogEntry->update();
		}
	}
}
$searchUpdateLogEntry->update();

$searchUpdateLogEntry->addNote("Finished updating saved searches");
$searchUpdateLogEntry->endTime = time();
$searchUpdateLogEntry->update();