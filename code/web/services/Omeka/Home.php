<?php
require_once ROOT_DIR . '/GroupedWorkSubRecordHomeAction.php';
require_once ROOT_DIR . '/sys/Omeka/OmekaTitle.php';
require_once ROOT_DIR . '/RecordDrivers/OmekaRecordDriver.php';

class Omeka_Home extends GroupedWorkSubRecordHomeAction {

	function launch() {
		global $interface;

		if (!$this->recordDriver->isValid()) {
			$this->display('../Record/invalidRecord.tpl', 'Invalid Record', '');
			die();
		}

		$groupedWork = $this->recordDriver->getGroupedWorkDriver();
		if (is_null($groupedWork) || !$groupedWork->isValid()) {
			$interface->assign('invalidWork', true);
			$this->display('../Record/invalidRecord.tpl', 'Invalid Record', '');
			die();
		}

		$interface->assign('recordDriver', $this->recordDriver);
		$interface->assign('groupedWorkDriver', $this->recordDriver->getGroupedWorkDriver());

		$holdingsSummary = $this->recordDriver->getStatusSummary();
		$interface->assign('holdingsSummary', $holdingsSummary);

		$interface->assign('actions', $this->recordDriver->getRecordActions(null, null, $holdingsSummary['available'], true, null));

		$this->loadCitations();

		$this->lastSearch = $_SESSION['lastSearchURL'] ?? false;
		$interface->assign('lastSearch', $this->lastSearch);

		$searchSource = !empty($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init($searchSource);
		$searchObject->getNextPrevLinks();

		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		$appearsOnLists = UserList::getUserListsForRecord('GroupedWork', $this->recordDriver->getPermanentId());
		$interface->assign('appearsOnLists', $appearsOnLists);

		$groupedWork->loadReadingHistoryIndicator();

		global $library;
		$groupedWorkDisplaySettings = $library->getGroupedWorkDisplaySettings();
		foreach ($groupedWorkDisplaySettings->showInMainDetails as $detailOption) {
			$interface->assign($detailOption, true);
		}

		$interface->assign('moreDetailsOptions', $this->recordDriver->getMoreDetailsOptions());

		$interface->assign('semanticData', json_encode($this->recordDriver->getSemanticData()));

		$_SESSION['returnToAction'] = $this->id;
		$_SESSION['returnToModule'] = 'Omeka';

		$this->display('full-record.tpl', $this->recordDriver->getTitle(), '', false);
	}

	function loadRecordDriver($id) {
		$this->recordDriver = new OmekaRecordDriver($id);
	}
}
