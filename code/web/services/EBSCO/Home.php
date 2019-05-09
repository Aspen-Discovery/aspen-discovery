<?php

require_once ROOT_DIR . '/RecordDrivers/EbscoRecordDriver.php';
class EBSCO_Home extends Action{

	function launch() {
		global $interface;
		$id = urldecode($_REQUEST['id']);

		$recordDriver = new EbscoRecordDriver($id);
		$interface->assign('recordDriver', $recordDriver);

		$exploreMoreInfo = $recordDriver->getExploreMoreInfo();
		$interface->assign('exploreMoreInfo', $exploreMoreInfo);

		// Display Page
		global $configArray;
		if ($configArray['Catalog']['showExploreMoreForFullRecords']) {
			$interface->assign('showExploreMore', true);
		}

		$this->display('view.tpl', $recordDriver->getTitle());
	}
}