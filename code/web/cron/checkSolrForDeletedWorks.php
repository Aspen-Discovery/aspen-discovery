<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';
require_once __DIR__ . '/../sys/SolrUtils.php';

require_once ROOT_DIR . '/sys/CronLogEntry.php';

set_time_limit(0);

$cronLogEntry = new CronLogEntry();
$cronLogEntry->startTime = time();
$cronLogEntry->name = 'Check Solr For Deleted Works';
$cronLogEntry->insert();

//Do a quick search to see how many results we have
/** @var SearchObject_AbstractGroupedWorkSearcher $searchObject */
$searchObject = SearchObjectFactory::initSearchObject();
$searchObject->init();
$searchObject->disableSpelling();
$searchObject->disableScoping();
$searchObject->disableBoosting();
$searchObject->disableDefaultAvailabilityToggle();
$searchObject->disableEditionLimiters();
$searchObject->setFieldsToReturn('id');
$searchObject->setLimit(1);
$solrConnection = $searchObject->getIndexEngine();
$result = $searchObject->processSearch();

$recordsToDeleteFromSolr = [];
$numRecordsDeleted = 0;
if (!$result instanceof AspenError && empty($result['error'])) {
	//First get a list of all records to be deleted (we don't want to change Solr while querying it)
	$numResults = $searchObject->getResultTotal();
	$cronLogEntry->notes .= date('g:i:s A') . " There are $numResults records in Solr.<br/>";
	$solrBatchSize = 250;
	$searchObject->setTimeout(60);
	$searchObject->setLimit($solrBatchSize);
	$searchObject->clearFacets();
	$numBatches = (int)ceil($numResults / $solrBatchSize);
	$cronLogEntry->notes .= date('g:i:s A') . " Processing in $numBatches batches.<br/>";
	for ($batchIndex = 1; $batchIndex <= $numBatches; $batchIndex++) {
		if ($batchIndex % 100 == 0) {
			$cronLogEntry->notes .= date('g:i:s A') . " Processing batch $batchIndex.<br/>";
		}
		$cronLogEntry->lastUpdate = time();
		$cronLogEntry->update();
		$searchObject->setPage($batchIndex);
		$result = $searchObject->processSearch(true);
		if (!$result instanceof AspenError && empty($result['error'])) {
			$recordsInBatch = [];
			foreach ($result['response']['docs'] as $doc) {
				$recordsInBatch[] = $doc['id'];
			}
			//Check the database to see if the IDs exist.
			require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
			$groupedWork = new GroupedWork();
			$groupedWork->selectAdd();
			$groupedWork->selectAdd('permanent_id');
			$groupedWork->whereAddIn('permanent_id', $recordsInBatch, true);
			$allResultsFromDB = $groupedWork->fetchAll('permanent_id', 'permanent_id');
			if (count($allResultsFromDB) != count($recordsInBatch)) {
				//Loop through to figure out which record(s) are missing
				foreach ($recordsInBatch as $groupedWorkId) {
					if (!isset($allResultsFromDB[$groupedWorkId])) {
						$cronLogEntry->notes .= date('g:i:s A') . " $groupedWorkId does not exist in the database and needs to be deleted.<br/>";
						$recordsToDeleteFromSolr[] = $groupedWorkId;
						$cronLogEntry->update();
					}
				}
			}
		}
	}

	//Now delete all the records
	$cronLogEntry->notes .= date('g:i:s A') . " Deleting " . count($recordsToDeleteFromSolr) . " works.<br/>";
	$cronLogEntry->update();
	foreach ($recordsToDeleteFromSolr as $groupedWorkId) {
		if (!$solrConnection->deleteRecord($groupedWorkId)) {
			$cronLogEntry->notes .= date('g:i:s A') . " ERROR $groupedWorkId could not be deleted.<br/>";
			$cronLogEntry->numErrors++;
			$cronLogEntry->update();
		}else{
			$numRecordsDeleted++;
			if ($numRecordsDeleted % 250 == 0) {
				$cronLogEntry->notes .= date('g:i:s A') . " Deleted $numRecordsDeleted.<br/>";
				$cronLogEntry->update();
				$solrConnection->commit();
			}
		}
	}
}else{
	$cronLogEntry->notes .= date('g:i:s A') . " Could not connect to Solr.<br/>";
}
if ($numRecordsDeleted > 0) {
	$cronLogEntry->notes .= date('g:i:s A') . " Starting final commit of solr after deleting works.<br/>";
	$cronLogEntry->update();
	$solrConnection->commit();
}

$cronLogEntry->notes .= date('g:i:s A') . " Deleted $numRecordsDeleted records.";
$cronLogEntry->endTime = time();
$cronLogEntry->update();
