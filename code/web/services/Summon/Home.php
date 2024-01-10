<?php

require_once ROOT_DIR . '/RecordDrivers/SummonRecordDriver.php';

class Summon_Home extends Action {

	private $recordDriver;

	function launch() {
		global $interface;
		$id = urldecode($_REQUEST['id']);

		$this->recordDriver = new SummonRecordDriver($id);
		$interface->assign('recordDriver', $this->recordDriver);

		$exploreMoreInfo = $this->recordDriver->getExploreMoreInfo();
		$interface->assign('exploreMoreInfo', $exploreMoreInfo);

		//Check to see if there are lists the record is on
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		$appearsOnLists = UserList::getUserListsForRecord('Summon', $this->recordDriver->getPermanentId());
		$interface->assign('appearsOnLists', $appearsOnLists);

		// Display Page
		$this->display('full-record.tpl', $this->recordDriver->getTitle(), 'Search/home-sidebar.tpl', false);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		if (!empty($this->recordDriver->lastSearch)) {
			$breadcrumbs[] = new Breadcrumb($this->recordDriver->lastSearch, 'Article & Database Search Results');
		}
		$breadcrumbs[] = new Breadcrumb('', $this->recordDriver->getTitle());
		return $breadcrumbs;
	}
}