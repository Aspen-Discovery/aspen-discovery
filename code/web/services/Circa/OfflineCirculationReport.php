<?php
/**
 * A report of check ins and check outs that have been placed offline with their status.
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/26/13
 * Time: 10:39 AM
 */
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR .'/sys/OfflineCirculationEntry.php';
class Circa_OfflineCirculationReport extends Admin_Admin{
	public function launch(){
		global $interface;

		if (isset($_REQUEST['startDate'])){
			$startDate = new DateTime($_REQUEST['startDate']);
		}else{
			$startDate = new DateTime();
			date_sub($startDate, new DateInterval('P1D')); // 1 day ago
		}
		if (isset($_REQUEST['endDate'])){
			$endDate = new DateTime($_REQUEST['endDate']);
		}else{
			$endDate = new DateTime();
		}
		$endDate->setTime(23,59,59); //second before midnight
		$typesToInclude = isset($_REQUEST['typesToInclude']) ? $_REQUEST['typesToInclude'] : 'everything';
		$loginsToInclude = isset($_REQUEST['loginsToInclude']) ? $_REQUEST['loginsToInclude'] : '';
		$hideNotProcessed = isset($_REQUEST['hideNotProcessed']);
		$hideFailed = isset($_REQUEST['hideFailed']);
		$hideSuccess = isset($_REQUEST['hideSuccess']);

		$interface->assign('startDate', $startDate->getTimestamp());
		$interface->assign('endDate', $endDate->getTimestamp());
		$interface->assign('typesToInclude', $typesToInclude);
		$interface->assign('loginsToInclude', $loginsToInclude);
		$interface->assign('hideNotProcessed', $hideNotProcessed);
		$interface->assign('hideFailed', $hideFailed);
		$interface->assign('hideSuccess', $hideSuccess);


		$offlineCirculationEntries = array();
		$offlineCirculationEntryObj = new OfflineCirculationEntry();
		$offlineCirculationEntryObj->whereAdd("timeEntered >= " . $startDate->getTimestamp() . " AND timeEntered <= " . $endDate->getTimestamp());
		if ($typesToInclude == 'checkouts'){
			$offlineCirculationEntryObj->type = 'Check Out';
		}else if ($typesToInclude == 'checkins'){
			$offlineCirculationEntryObj->type = 'Check In';
		}
		if ($hideFailed){
			$offlineCirculationEntryObj->whereAdd("status != 'Processing Failed'", 'AND');
		}
		if ($hideSuccess){
			$offlineCirculationEntryObj->whereAdd("status != 'Processing Succeeded'", 'AND');
		}
		if ($hideNotProcessed){
			$offlineCirculationEntryObj->whereAdd("status != 'Not Processed'", 'AND');
		}
		if (strlen($loginsToInclude) > 0){
			$logins = explode(',', $loginsToInclude);
			$loginsToFind = '';
			foreach ($logins as $login){
				$login = trim($login);
				if (strlen($loginsToFind) > 0){
					$loginsToFind .= ", ";
				}
				$loginsToFind .= "'{$login}'";
			}
			if (strlen($loginsToFind) > 0){
				$offlineCirculationEntryObj->whereAdd("login IN ($loginsToFind)", 'AND');
			}
		}
		$offlineCirculationEntryObj->find();
		$totalRecords = 0;
		$totalPassed = 0;
		$totalFailed = 0;
		$totalNotProcessed = 0;
		while ($offlineCirculationEntryObj->fetch()){
			$offlineCirculationEntries[] = clone $offlineCirculationEntryObj;
			$totalRecords++;
			if ($offlineCirculationEntryObj->status == 'Not Processed'){
				$totalNotProcessed++;
			}elseif ($offlineCirculationEntryObj->status == 'Processing Succeeded'){
				$totalPassed++;
			}else{
				$totalFailed++;
			}
		}
		$interface->assign('totalRecords', $totalRecords);
		$interface->assign('totalPassed', $totalPassed);
		$interface->assign('totalFailed', $totalFailed);
		$interface->assign('totalNotProcessed', $totalNotProcessed);

		$interface->setPageTitle('Offline Circulation Report');
		$interface->assign('sidebar', 'MyAccount/account-sidebar.tpl');
		$interface->assign('offlineCirculation', $offlineCirculationEntries);
		$interface->setTemplate('offlineCirculationReport.tpl');
		$interface->display('layout.tpl');
	}

	function getAllowableRoles() {
		return array('opacAdmin', 'libraryAdmin', 'circulationReports');
	}
}