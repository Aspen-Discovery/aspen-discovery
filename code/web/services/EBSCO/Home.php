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

		// Display Page
		global $configArray;
		if ($configArray['Catalog']['showExploreMoreForFullRecords']) {
			$interface->assign('showExploreMore', true);
		}

		$this->display('full-record.tpl', $this->recordDriver->getTitle(), 'Search/home-sidebar.tpl', false);
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		if (!empty($this->lastSearch)){
			$breadcrumbs[] = new Breadcrumb($this->lastSearch, 'Article & Database Search Results');
		}
		$breadcrumbs[] = new Breadcrumb('', $this->recordDriver->getTitle());
		return $breadcrumbs;
	}
}