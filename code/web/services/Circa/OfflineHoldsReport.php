<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
class Circa_OfflineHoldsReport extends Admin_Admin{
	public function launch(){
		global $interface;

		$startDate = null;
		if (isset($_REQUEST['startDate'])){
			try {
				$startDate = new DateTime($_REQUEST['startDate']);
			} catch (Exception $e) {
				$startDate = null;
			}
		}
		if ($startDate == null){
			$startDate = new DateTime();
			date_sub($startDate, new DateInterval('P1D')); // one day ago
		}
		$endDate = null;
		if (isset($_REQUEST['endDate'])){
			try {
				$endDate = new DateTime($_REQUEST['endDate']);
			} catch (Exception $e) {
				$endDate = null;
			}
		}
		if ($endDate == null){
			$endDate = new DateTime();
		}
		$endDate->setTime(23,59,59); //second before midnight
		$hideNotProcessed = isset($_REQUEST['hideNotProcessed']);
		$hideFailed = isset($_REQUEST['hideFailed']);
		$hideSuccess = isset($_REQUEST['hideSuccess']);

		$interface->assign('startDate', $startDate->getTimestamp());
		$interface->assign('endDate', $endDate->getTimestamp());
		$interface->assign('hideNotProcessed', $hideNotProcessed);
		$interface->assign('hideFailed', $hideFailed);
		$interface->assign('hideSuccess', $hideSuccess);


		$offlineHolds = array();
		$offlineHoldsObj = new OfflineHold();
		$offlineHoldsObj->whereAdd("timeEntered >= " . $startDate->getTimestamp() . " AND timeEntered <= " . $endDate->getTimestamp());
		if ($hideFailed){
			$offlineHoldsObj->whereAdd("status != 'Hold Failed'", 'AND');
		}
		if ($hideSuccess){
			$offlineHoldsObj->whereAdd("status != 'Hold Succeeded'", 'AND');
		}
		if ($hideNotProcessed){
			$offlineHoldsObj->whereAdd("status != 'Not Processed'", 'AND');
		}
		$offlineHoldsObj->find();
		while ($offlineHoldsObj->fetch()){
			$offlineHold = array();
			require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
			$recordDriver = new MarcRecordDriver($offlineHoldsObj->bibId);
			if ($recordDriver->isValid()){
				$offlineHold['title'] = $recordDriver->getTitle();
			}
			$offlineHold['patronBarcode'] = $offlineHoldsObj->patronBarcode;
			$offlineHold['bibId'] = $offlineHoldsObj->bibId;
			$offlineHold['timeEntered'] = $offlineHoldsObj->timeEntered;
			$offlineHold['status'] = $offlineHoldsObj->status;
			$offlineHold['notes'] = $offlineHoldsObj->notes;
			$offlineHolds[] = $offlineHold;
		}

		$interface->assign('offlineHolds', $offlineHolds);
		$this->display('offlineHoldsReport.tpl', 'Offline Holds Report');
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#ils_integration', 'ILS Integration');
		$breadcrumbs[] = new Breadcrumb('', 'Offline Holds Report');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'ils_integration';
	}

	function canView()
	{
		return UserAccount::userHasPermission('View Offline Holds Report');
	}
}