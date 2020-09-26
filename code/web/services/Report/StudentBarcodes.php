<?php
/**
 * Displays Student Barcodes Created by cron
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * @author James Staub <james.staub@nashville.gov>
 * Date: 7/16/2018
 */

require_once(ROOT_DIR . '/services/Admin/Admin.php');
class Report_StudentBarcodes extends Admin_Admin {
	function launch(){
		global $interface;
		global $configArray;
		$user = UserAccount::getLoggedInUser();

		//Get a list of all reports the user has access to
		$reportDir = $configArray['Site']['reportPath'];

		$allowableLocationCodes = "";
		if (UserAccount::userHasRole('opacAdmin')){
			$allowableLocationCodes = '.*';
		}elseif (UserAccount::userHasRole('libraryAdmin')){
			$homeLibrary = Library::getPatronHomeLibrary();
			$allowableLocationCodes = trim($homeLibrary->ilsCode) . '.*';
		}elseif (UserAccount::userHasRole('locationReports')){
			$homeLocation = Location::getUserHomeLocation();
			$allowableLocationCodes = trim($homeLocation->code) . '.*';
		}
		$availableReports = array();
		$dh  = opendir($reportDir);
		while (false !== ($filename = readdir($dh))) {
			if (is_file($reportDir . '/' . $filename)){
				if (preg_match('/^(.*?)_school_barcodes\.csv/i', $filename, $matches)){
					$locationCode = $matches[1];
					if (preg_match("/$allowableLocationCodes/", $locationCode)){
						$availableReports[$locationCode] = $filename;
					}
				}
			}
		}
		asort($availableReports);
		$interface->assign('availableReports', $availableReports);

		$selectedReport = isset($_REQUEST['selectedReport']) ? $availableReports[$_REQUEST['selectedReport']] : reset($availableReports);
		$interface->assign('selectedReport', $selectedReport);
//		$showOverdueOnly = isset($_REQUEST['showOverdueOnly']) ? $_REQUEST['showOverdueOnly'] == 'overdue': true;
//		$interface->assign('showOverdueOnly', $showOverdueOnly);
		$now = time();
		$fileData = array();
		if ($selectedReport){
			$filemtime = date('Y-m-d H:i:s',filemtime($reportDir . '/' . $selectedReport));
			$interface->assign('reportDateTime', $filemtime);
			$fhnd = fopen($reportDir . '/' . $selectedReport, "r");
			if ($fhnd){
				while (($data = fgetcsv($fhnd)) !== FALSE){
					$okToInclude = true;
//					if ($showOverdueOnly){
//						$dueDate = $data[12];
//						$dueTime = strtotime($dueDate);
//						if ($dueTime >= $now){
//							$okToInclude = false;
//						}
//					}
					if ($okToInclude || count($fileData) == 0){
						$fileData[] = $data;
					}
				}
				$interface->assign('reportData', $fileData);
			}
		}

		if (isset($_REQUEST['download'])){
			header('Content-Type: text/csv');
			header('Content-Disposition: attachment; filename=' . $selectedReport);
			header('Content-Length:' . filesize($reportDir . '/' . $selectedReport));
			foreach ($fileData as $row){
				foreach ($row as $index => $cell){
					if ($index != 0){
						echo(",");
					}
					if (strpos($cell, ',') != false){
						echo('"' . $cell . '"');
					}else{
						echo($cell);
					}

				}
				echo("\r\n");
			}
			exit;
		}

		$interface->setPageTitle('Student Barcodes');
		$interface->assign('sidebar', 'MyAccount/account-sidebar.tpl');
		$interface->setTemplate('studentBarcodes.tpl');
		$interface->display('layout.tpl');
	}

	function getAllowableRoles(){
		return array('opacAdmin', 'libraryAdmin', 'locationReports');
	}
}
