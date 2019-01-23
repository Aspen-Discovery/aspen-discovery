<?php
/**
 * A report of holds that have been placed offline with their status.
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/26/13
 * Time: 10:39 AM
 */
require_once ROOT_DIR . '/services/Admin/Admin.php';
class Circa_OfflineHoldsReport extends Admin_Admin{
	public function launch(){
		global $interface;

		if (isset($_REQUEST['startDate'])){
			$startDate = new DateTime($_REQUEST['startDate']);
		}else{
			$startDate = new DateTime();
			date_sub($startDate, new DateInterval('P1D')); // one day ago
		}
		if (isset($_REQUEST['endDate'])){
			$endDate = new DateTime($_REQUEST['endDate']);
		}else{
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
			require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
			$recordDriver = new MarcRecord($offlineHoldsObj->bibId);
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

		$interface->setPageTitle('Offline Holds Report');
		$interface->assign('sidebar', 'MyAccount/account-sidebar.tpl');
		$interface->assign('offlineHolds', $offlineHolds);
		$interface->setTemplate('offlineHoldsReport.tpl');
		$interface->display('layout.tpl');
	}

	function getAllowableRoles() {
		return array('opacAdmin', 'libraryAdmin', 'circulationReports');
	}
}