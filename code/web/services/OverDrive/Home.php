<?php

require_once ROOT_DIR . '/GroupedWorkSubRecordHomeAction.php';
require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProduct.php';
require_once ROOT_DIR . '/RecordDrivers/OverDriveRecordDriver.php';

class OverDrive_Home extends GroupedWorkSubRecordHomeAction
{
	function launch()
	{
		global $interface;

		if (!$this->recordDriver->isValid()) {
			$this->display('../Record/invalidRecord.tpl', 'Invalid Record', '');
			die();
		}

		$groupedWork = $this->recordDriver->getGroupedWorkDriver();
		if (is_null($groupedWork) || !$groupedWork->isValid()) {  // initRecordDriverById itself does a validity check and returns null if not.
			$interface->assign('invalidWork', true);
			$this->display('../Record/invalidRecord.tpl', 'Invalid Record', '');
			die();
		} else {
			$interface->assign('recordDriver', $this->recordDriver);
			$interface->assign('groupedWorkDriver', $this->recordDriver->getGroupedWorkDriver());

			//Load status summary
			require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
			$holdingsSummary = $this->recordDriver->getStatusSummary();
			if (($holdingsSummary instanceof AspenError)) {
				AspenError::raiseError($holdingsSummary);
			}
			$interface->assign('holdingsSummary', $holdingsSummary);

			//Get actions
			$interface->assign('actions', $this->recordDriver->getRecordActions(null, $holdingsSummary['available'], true, false, null));

			//Load the citations
			$this->loadCitations();

			// Retrieve User Search History
			$interface->assign('lastSearch', isset($_SESSION['lastSearchURL']) ?
				$_SESSION['lastSearchURL'] : false);

			//Get Next/Previous Links
			$searchSource = !empty($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';
			$searchObject = SearchObjectFactory::initSearchObject();
			$searchObject->init($searchSource);
			$searchObject->getNextPrevLinks();

			//Check to see if there are lists the record is on
			require_once ROOT_DIR . '/sys/UserLists/UserList.php';
			$appearsOnLists = UserList::getUserListsForRecord('GroupedWork', $this->recordDriver->getPermanentId());
			$interface->assign('appearsOnLists', $appearsOnLists);

			// Set Show in Main Details Section options for templates
			// (needs to be set before moreDetailsOptions)
			global $library;
			foreach ($library->getGroupedWorkDisplaySettings()->showInMainDetails as $detailOption) {
				$interface->assign($detailOption, true);
			}

			$interface->assign('moreDetailsOptions', $this->recordDriver->getMoreDetailsOptions());

			$interface->assign('semanticData', json_encode($this->recordDriver->getSemanticData()));

			// Display Page
			$this->display('full-record.tpl', $this->recordDriver->getTitle(), '', false);

		}
	}

	function loadRecordDriver($id)
	{
		$this->recordDriver = new OverDriveRecordDriver($id);
	}
}