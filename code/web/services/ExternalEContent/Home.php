<?php
require_once ROOT_DIR . '/GroupedWorkSubRecordHomeAction.php';
require_once ROOT_DIR . '/RecordDrivers/ExternalEContentDriver.php';

class ExternalEContent_Home extends GroupedWorkSubRecordHomeAction {

	function launch() {
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

			$this->loadCitations();

			$interface->assign('cleanDescription', strip_tags($this->recordDriver->getDescriptionFast(), '<p><br><b><i><em><strong>'));

			// Retrieve User Search History
			$this->lastSearch = isset($_SESSION['lastSearchURL']) ? $_SESSION['lastSearchURL'] : false;
			$interface->assign('lastSearch', $this->lastSearch);

			//Get Next/Previous Links
			$searchSource = !empty($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';
			$searchObject = SearchObjectFactory::initSearchObject();
			$searchObject->init($searchSource);
			$searchObject->getNextPrevLinks();

			//Check to see if there are lists the record is on
			require_once ROOT_DIR . '/sys/UserLists/UserList.php';
			$appearsOnLists = UserList::getUserListsForRecord('GroupedWork', $this->recordDriver->getPermanentId());
			$interface->assign('appearsOnLists', $appearsOnLists);

			$groupedWork->loadReadingHistoryIndicator();

			//Get Related Records to make sure we initialize items
			$recordInfo = $this->recordDriver->getGroupedWorkDriver()->getRelatedRecord($this->recordDriver->getIdWithSource());
			if ($recordInfo == null) {
				$this->display('../Record/invalidRecord.tpl', 'Invalid Record', '');
				die();
			}
			$interface->assign('actions', $recordInfo->getActions());

			// Set Show in Main Details Section options for templates
			// (needs to be set before moreDetailsOptions)
			global $library;
			$groupedWorkDisplaySettings = $library->getGroupedWorkDisplaySettings();
			foreach ($groupedWorkDisplaySettings->showInSearchResultsMainDetails as $detailOption) {
				$interface->assign($detailOption, true);
			}

			$interface->assign('moreDetailsOptions', $this->recordDriver->getMoreDetailsOptions());

			$interface->assign('semanticData', json_encode($this->recordDriver->getSemanticData()));

			//Load Staff Details
			$interface->assign('staffDetails', $this->recordDriver->getStaffView());

			// Display Page
			$this->display('full-record.tpl', $this->recordDriver->getTitle(), '', false);

		}
	}

	function loadRecordDriver($id) {
		global $activeRecordProfile;
		$subType = '';
		if (isset($activeRecordProfile)) {
			$subType = $activeRecordProfile;
		} else {
			$indexingProfile = new IndexingProfile();
			$indexingProfile->name = 'ils';
			if ($indexingProfile->find(true)) {
				$subType = $indexingProfile->name;
			} else {
				$indexingProfile = new IndexingProfile();
				$indexingProfile->id = 1;
				if ($indexingProfile->find(true)) {
					$subType = $indexingProfile->name;
				}
			}
		}


		$this->recordDriver = new ExternalEContentDriver($subType . ':' . $id);
	}
}