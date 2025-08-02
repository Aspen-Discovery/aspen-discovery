<?php

require_once ROOT_DIR . '/Action.php';

class GroupedWork_Home extends Action {
	/** @var GroupedWorkDriver $recordDriver */
	private $recordDriver;
	private $lastSearch;

	function launch() {
		global $interface;
		global $timer;
		global $logger;

		$id = strip_tags($_REQUEST['id']);
		$_SESSION['returnToAction'] = $id;
		$_SESSION['returnToModule'] = 'GroupedWork';

		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$this->recordDriver = new GroupedWorkDriver($id);
		$hasValidRecord = false;
		if (!$this->recordDriver->isValid) {
			//Check to see if the ID was generated prior to the language enhancements and if so try to find the correct grouped work based on the language.
			if (strlen($id) == 36) {
				require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
				$groupedWork = new GroupedWork();
				$groupedWork->permanent_id = $id . '-eng';
				if ($groupedWork->find(true)) {
					$this->recordDriver = new GroupedWorkDriver($id . '-eng');
					if ($this->recordDriver->isValid) {
						$hasValidRecord = true;
						$id = $id . '-eng';
						header("Location: /GroupedWork/$id");
					}
				}
				if (!$hasValidRecord) {
					//Check other languages and get the first
					$groupedWork = new GroupedWork();
					global $aspen_db;
					$groupedWork->whereAdd('permanent_id like ' . $aspen_db->quote($id . '-%'));
					$groupedWork->find();
					while ($groupedWork->fetch()) {
						$id = $groupedWork->permanent_id;
						$this->recordDriver = new GroupedWorkDriver($id);
						if ($this->recordDriver->isValid) {
							$hasValidRecord = true;
							header("Location: /GroupedWork/$id");
						}
					}
				}
			}
		} else {
			$hasValidRecord = true;
		}
		if (!$hasValidRecord) {
			$interface->assign('id', $id);
			$logger->log("Did not find a record for id {$id} in solr.", Logger::LOG_DEBUG);
			$this->display('../Record/invalidRecord.tpl', 'Invalid Record', '');
			die();
		}
		$interface->assign('recordDriver', $this->recordDriver);
		$timer->logTime('Loaded Grouped Work Driver');

		//For display in metadata
		$interface->assign('description', $this->recordDriver->getDescriptionFast(true));

		// Set Show in Search Results Main Details Section options for template
		// (needs to be set before moreDetailsOptions)
		global $library;
		foreach ($library->getGroupedWorkDisplaySettings()->showInMainDetails as $detailOption) {
			$interface->assign($detailOption, true);
		}

		$this->recordDriver->assignBasicTitleDetails();
		$timer->logTime('Initialized the Record Driver');

		// Retrieve User Search History
		$this->lastSearch = isset($_SESSION['lastSearchURL']) ? $_SESSION['lastSearchURL'] : false;
		$interface->assign('lastSearch', $this->lastSearch);

		//Get Next/Previous Links
		$searchSource = !empty($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';
		/** @var SearchObject_AbstractGroupedWorkSearcher $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init($searchSource);
		$searchObject->getNextPrevLinks();
		$timer->logTime('Got next and previous links');

		//Check to see if there are lists the record is on
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		$appearsOnLists = UserList::getUserListsForRecord('GroupedWork', $this->recordDriver->getPermanentId());
		$interface->assign('appearsOnLists', $appearsOnLists);

		$this->recordDriver->loadReadingHistoryIndicator();

		$interface->assign('moreDetailsOptions', $this->recordDriver->getMoreDetailsOptions());
		$timer->logTime('Got more details options');

		$exploreMoreInfo = $this->recordDriver->getExploreMoreInfo();
		$interface->assign('exploreMoreInfo', $exploreMoreInfo);
		$timer->logTime('Got explore more information');

		$interface->assign('metadataTemplate', 'GroupedWork/metadata.tpl');

		$interface->assign('semanticData', json_encode($this->recordDriver->getSemanticData()));
		$timer->logTime('Loaded semantic data');

		$interface->assign('activeFormat', $_REQUEST['activeFormat'] ?? null);
		$interface->assign('searchSource', $_REQUEST['activeSearchSource'] ?? 'global');

		// Display Page
		$this->display('full-record.tpl', $this->recordDriver->getTitle(), '', false);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		if (!empty($this->lastSearch)) {
			$breadcrumbs[] = new Breadcrumb($this->lastSearch, 'Catalog Search Results');
		}
		$breadcrumbs[] = new Breadcrumb('', $this->recordDriver->getTitle(), false);
		return $breadcrumbs;
	}
}