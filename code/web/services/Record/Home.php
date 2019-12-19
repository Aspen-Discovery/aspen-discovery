<?php

require_once 'Record.php';

/** @noinspection PhpUnused */
class Record_Home extends Record_Record{
	function launch(){
		global $interface;
		global $timer;

		$recordId = $this->id;

		$this->loadCitations();
		$timer->logTime('Loaded Citations');

		if (isset($_REQUEST['searchId'])){
			$_SESSION['searchId'] = $_REQUEST['searchId'];
			$interface->assign('searchId', $_SESSION['searchId']);
		}else if (isset($_SESSION['searchId'])){
			$interface->assign('searchId', $_SESSION['searchId']);
		}

		$interface->assign('recordId', $recordId);

		// Set Show in Main Details Section options for templates
		// (needs to be set before moreDetailsOptions)
		global $library;
		foreach ($library->getGroupedWorkDisplaySettings()->showInMainDetails as $detailOption) {
			$interface->assign($detailOption, true);
		}

		//Get the actions for the record
		$actions = $this->recordDriver->getRecordActionsFromIndex();
		$interface->assign('actions', $actions);

		$interface->assign('moreDetailsOptions', $this->recordDriver->getMoreDetailsOptions());
		$exploreMoreInfo = $this->recordDriver->getExploreMoreInfo();
		$interface->assign('exploreMoreInfo', $exploreMoreInfo);

		$interface->assign('semanticData', json_encode($this->recordDriver->getSemanticData()));

		// Display Page
		global $configArray;
		if ($configArray['Catalog']['showExploreMoreForFullRecords']) {
			$interface->assign('showExploreMore', true);
		}

		$this->display('full-record.tpl', $this->recordDriver->getTitle());

	}

	function loadCitations(){
		global $interface;

		$citationCount = 0;
		$formats = $this->recordDriver->getCitationFormats();
		foreach($formats as $current) {
			$interface->assign(strtolower($current),
					$this->recordDriver->getCitation($current));
			$citationCount++;
		}
		$interface->assign('citationCount', $citationCount);
	}
}