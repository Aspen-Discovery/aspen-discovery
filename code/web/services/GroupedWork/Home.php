<?php
/**
 * Description goes here
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 11/27/13
 * Time: 12:14 PM
 */
require_once ROOT_DIR  . '/Action.php';
class GroupedWork_Home extends Action{
	function launch() {
		global $interface;
		global $timer;
		global $logger;

		$id = strip_tags($_REQUEST['id']);

		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$recordDriver = new GroupedWorkDriver($id);
		if (!$recordDriver->isValid){
			$interface->assign('id', $id);
			$logger->log("Did not find a record for id {$id} in solr." , PEAR_LOG_DEBUG);
			$this->display('../Record/invalidRecord.tpl', 'Invalid Record');
			die();
		}
		$interface->assign('recordDriver', $recordDriver);
		$timer->logTime('Loaded Grouped Work Driver');

		// Set Show in Search Results Main Details Section options for template
		// (needs to be set before moreDetailsOptions)
		global $library;
		foreach ($library->showInMainDetails as $detailoption) {
			$interface->assign($detailoption, true);
		}

		$recordDriver->assignBasicTitleDetails();
		$timer->logTime('Initialized the Record Driver');

		// Retrieve User Search History
		$interface->assign('lastsearch', isset($_SESSION['lastSearchURL']) ? $_SESSION['lastSearchURL'] : false);

		//Get Next/Previous Links
		$searchSource = isset($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';
		/** @var SearchObject_Solr $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init($searchSource);
		$searchObject->getNextPrevLinks();
		$timer->logTime('Got next and previous links');

		$interface->assign('moreDetailsOptions', $recordDriver->getMoreDetailsOptions());
		$timer->logTime('Got more details options');

		$exploreMoreInfo = $recordDriver->getExploreMoreInfo();
		$interface->assign('exploreMoreInfo', $exploreMoreInfo);
		$timer->logTime('Got explore more information');

		$interface->assign('metadataTemplate', 'GroupedWork/metadata.tpl');

		$interface->assign('semanticData', json_encode($recordDriver->getSemanticData()));
		$timer->logTime('Loaded semantic data');

		// Send down text for inclusion in breadcrumbs
		$interface->assign('breadcrumbText', $recordDriver->getBreadcrumb());
		$timer->logTime('Loaded breadcrumbs');

		// Display Page
		$this->display('full-record.tpl', $recordDriver->getTitle());
	}


}