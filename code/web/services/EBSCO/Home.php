<?php

require_once ROOT_DIR . '/RecordDrivers/EbscoRecordDriver.php';
class EBSCO_Home extends Action{

	private $recordDriver;
	function launch() {
		global $interface;
		$id = urldecode($_REQUEST['id']);

		$this->recordDriver = new EbscoRecordDriver($id);
		$interface->assign('recordDriver', $this->recordDriver);

		$exploreMoreInfo = $this->recordDriver->getExploreMoreInfo();
		$interface->assign('exploreMoreInfo', $exploreMoreInfo);

		//Check to see if there are lists the record is on
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		$appearsOnLists = UserList::getUserListsForRecord('EbscoEds', $this->recordDriver->getPermanentId());
		$interface->assign('appearsOnLists', $appearsOnLists);

		// Display Page
		$this->display('full-record.tpl', $this->recordDriver->getTitle(), 'Search/home-sidebar.tpl', false);
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		if (!empty($this->lastSearch)){
			$breadcrumbs[] = new Breadcrumb($this->lastSearch, 'Article & Database Search Results');
		}
		$breadcrumbs[] = new Breadcrumb('', $this->recordDriver->getTitle());
		return $breadcrumbs;
	}
}