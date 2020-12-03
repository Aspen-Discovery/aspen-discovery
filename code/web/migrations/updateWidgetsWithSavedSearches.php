<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

/**
* This will load user data from a Pika system
*/
global $serverName;

ini_set('memory_limit','4G');
$dataPath = '/data/aspen-discovery/' . $serverName;
$exportPath = $dataPath . '/pika_export/';

$existingUsers = [];
$missingUsers = [];
importSavedSearches($startTime, $exportPath, $existingUsers, $missingUsers);
importListWidgets($startTime, $exportPath);

function importSavedSearches($startTime, $exportPath, &$existingUsers, &$missingUsers){
	if (file_exists($exportPath . 'saved_searches.csv')){
		echo ("Starting to import saved searches\n");
		$savedSearchesHnd = fopen($exportPath . 'saved_searches.csv', 'r');
		$removedSearches = [];
		$numImports = 0;
		$batchStartTime = time();
		//TODO: Do we need to flip the ids of the searches to preserve the id?
		while ($savedSearchRow = fgetcsv($savedSearchesHnd)){
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
			$userId = -1;
			if (array_key_exists($userBarcode, $existingUsers)) {
				$userId = $existingUsers[$userBarcode];
			}else if (array_key_exists($userBarcode, $missingUsers)) {
				$userId = $missingUsers[$userBarcode];
			}else {
				$tmpUser = new User();
				$tmpUser->cat_username = $userBarcode;
				if ($tmpUser->find(true)){
					$existingUsers[$userBarcode] = $tmpUser->id;
					$userId = $tmpUser->id;
				}else{
					$missingUsers[$userBarcode] = true;
				}
				$tmpUser->__destruct();
			}
			if ($userId != -1){
				$userId = $existingUsers[$userBarcode];
				require_once ROOT_DIR . '/sys/SearchEntry.php';
				$savedSearch = new SearchEntry();
				$savedSearch->id = $searchId;
				$searchExists = false;
				if ($savedSearch->find(true)){
					$searchExists = true;
				}
				$savedSearch->user_id = $userId;
				$savedSearch->session_id = $sessionId;
				$savedSearch->created = $created;
				$savedSearch->searchSource = $searchSource;
				$savedSearch->search_object = $searchObject;
				$savedSearch->saved = $saved;
				if ($searchExists){
					$savedSearch->update();
				}else{
					$savedSearch->insert();
				}
				$savedSearch->__destruct();
				$savedSearch = null;
			}

			if ($numImports % 2500 == 0){
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
		echo("Processed $numImports Saved Searches in $elapsedTime seconds ($totalElapsedTime minutes total).\n");
		echo("Removed " . count($removedSearches) . " saved searches because the user is not valid\n");
	}else{
		echo ("No saved searches provided, skipping\n");
	}
	ob_flush();
}

function importListWidgets($startTime, $exportPath)
{
	if (file_exists($exportPath . 'list_widget_lists.csv')) {
		$batchStartTime = time();
		$listWidgetListsHnd = fopen($exportPath . 'list_widget_lists.csv', 'r');

		require_once ROOT_DIR . '/sys/LocalEnrichment/CollectionSpotlight.php';

		$numListWidgetListsUpdated = 0;
		while ($listWidgetListRow = fgetcsv($listWidgetListsHnd)) {
			$curCol = 0;
			$listWidgetListWidgetListId = $listWidgetListRow[$curCol++];
			$listWidgetId = $listWidgetListRow[$curCol++];
			$collectionSpotlightList = new CollectionSpotlightList();
			$collectionSpotlightList->id = $listWidgetListWidgetListId;
			if ($collectionSpotlightList->find(true)){
				//Only update lists that exist
				$collectionSpotlightList->collectionSpotlightId = $listWidgetId;
				$collectionSpotlightList->weight = $listWidgetListRow[$curCol++];
				$collectionSpotlightList->displayFor = $listWidgetListRow[$curCol++];
				$collectionSpotlightList->name = $listWidgetListRow[$curCol++];

				$source = $listWidgetListRow[$curCol];
				list($type, $identifier) = explode(':', $source);
				//Only update spotlight lists that are from searches
				if ($type == 'search'){
					//Only update if we don't currently have search terms or filters
					if (empty($collectionSpotlightList->searchTerm) && empty($collectionSpotlightList->defaultFilter)) {
						/** @var SearchObject_GroupedWorkSearcher $searcher */
						$searcher = SearchObjectFactory::initSearchObject('GroupedWork');
						$savedSearch = $searcher->restoreSavedSearch($identifier, false, true);
						if ($savedSearch !== false) {
							$collectionSpotlightList->updateFromSearch($savedSearch);
							$collectionSpotlightList->update();
							$numListWidgetListsUpdated++;
						} else {
							echo("Could not load saved search $identifier for collection spotlight list $listWidgetListWidgetListId in $listWidgetId\n");
							continue;
						}
					}
				}
			}
		}

		fclose($listWidgetListsHnd);
		$elapsedTime = time() - $batchStartTime;
		$totalElapsedTime = ceil((time() - $startTime) / 60);
		echo("Processed $numListWidgetListsUpdated List Widget Lists in $elapsedTime seconds ($totalElapsedTime minutes total).\n");
	}else{
		echo ("No list widgets provided, skipping\n");
	}
	ob_flush();
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
