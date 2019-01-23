<?php

/**
 * Displays full record information
 *
 * @category VuFind-Plus-2014
 * @author Mark Noble <mark@marmot.org>
 * Date: 3/2/2016
 * Time: 10:31 AM
 */

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