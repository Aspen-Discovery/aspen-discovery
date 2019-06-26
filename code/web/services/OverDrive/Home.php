<?php

require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProduct.php';
require_once ROOT_DIR . '/RecordDrivers/OverDriveRecordDriver.php';

class OverDrive_Home extends Action{
	private $id;

	function launch(){
		global $interface;
		global $configArray;

		if (isset($_REQUEST['searchId'])){
			$_SESSION['searchId'] = $_REQUEST['searchId'];
			$interface->assign('searchId', $_SESSION['searchId']);
		}else if (isset($_SESSION['searchId'])){
			$interface->assign('searchId', $_SESSION['searchId']);
		}

		$this->id = strip_tags($_REQUEST['id']);
		$interface->assign('id', $this->id);
		$recordDriver = new OverDriveRecordDriver($this->id);

		if (!$recordDriver->isValid()){
			$this->display('../Record/invalidRecord.tpl', 'Invalid Record');
			die();
		}

		$groupedWork = $recordDriver->getGroupedWorkDriver();
		if (is_null($groupedWork) || !$groupedWork->isValid()){  // initRecordDriverById itself does a validity check and returns null if not.
			$this->display('../Record/invalidRecord.tpl', 'Invalid Record');
			die();
		}else{
			$interface->assign('recordDriver', $recordDriver);
			$interface->assign('groupedWorkDriver', $recordDriver->getGroupedWorkDriver());

			//Load status summary
            require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
            $holdingsSummary = $recordDriver->getStatusSummary();
			if (($holdingsSummary instanceof AspenError)) {
				AspenError::raiseError($holdingsSummary);
			}
			$interface->assign('holdingsSummary', $holdingsSummary);

			//Load the citations
			$this->loadCitations($recordDriver);

			// Retrieve User Search History
			$interface->assign('lastSearch', isset($_SESSION['lastSearchURL']) ?
			$_SESSION['lastSearchURL'] : false);

			//Get Next/Previous Links
			$searchSource = isset($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';
			$searchObject = SearchObjectFactory::initSearchObject();
			$searchObject->init($searchSource);
			$searchObject->getNextPrevLinks();

			// Set Show in Main Details Section options for templates
			// (needs to be set before moreDetailsOptions)
			global $library;
			foreach ($library->showInMainDetails as $detailOption) {
				$interface->assign($detailOption, true);
			}

			$interface->assign('moreDetailsOptions', $recordDriver->getMoreDetailsOptions());

			$interface->assign('semanticData', json_encode($recordDriver->getSemanticData()));

			// Display Page
			$this->display('full-record.tpl', $recordDriver->getTitle(), 'Search/home-sidebar.tpl', false);

		}
	}


	/**
	 * @param OverDriveRecordDriver $recordDriver
	 */
	function loadCitations($recordDriver){
		global $interface;

		$citationCount = 0;
		$formats = $recordDriver->getCitationFormats();
		foreach($formats as $current) {
			$interface->assign(strtolower($current), $recordDriver->getCitation($current));
			$citationCount++;
		}
		$interface->assign('citationCount', $citationCount);
	}
}