<?php
/**
 * Shows all titles that are on hold for a user (combines all sources)
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 10/10/13
 * Time: 1:11 PM
 */

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
class MyAccount_Holds extends MyAccount{
	function launch()
	{
		global $configArray,
		       $interface,
		       $library;

		$user = UserAccount::getLoggedInUser();
		//Check to see if any user accounts are allowed to freeze holds
		$interface->assign('allowFreezeHolds', true);

		$ils = $configArray['Catalog']['ils'];
		$showPosition = ($ils == 'Horizon' || $ils == 'Koha' || $ils == 'Symphony' || $ils == 'CarlX');
		$showExpireTime = ($ils == 'Horizon' || $ils == 'Symphony');
		$suspendRequiresReactivationDate = ($ils == 'Horizon' || $ils == 'CarlX' || $ils == 'Symphony'|| $ils == 'Koha');
		$interface->assign('suspendRequiresReactivationDate', $suspendRequiresReactivationDate);
		$canChangePickupLocation = ($ils != 'Koha');
		$interface->assign('canChangePickupLocation', $canChangePickupLocation);
		$showPlacedColumn = ($ils == 'Symphony');
		$interface->assign('showPlacedColumn', $showPlacedColumn);

		// Define sorting options
		$unavailableHoldSortOptions = array(
			'title'  => 'Title',
			'author' => 'Author',
			'format' => 'Format',
			'status' => 'Status',
			'location' => 'Pickup Location',
		);
		if ($showPosition){
			$unavailableHoldSortOptions['position'] = 'Position';
		}
		if ($showPlacedColumn) {
			$unavailableHoldSortOptions['placed'] = 'Date Placed';
		}

		$availableHoldSortOptions = array(
			'title'  => 'Title',
			'author' => 'Author',
			'format' => 'Format',
			'expire' => 'Expiration Date',
			'location' => 'Pickup Location',
		);

		if (count($user->getLinkedUsers()) > 0){
			$unavailableHoldSortOptions['libraryAccount'] = 'Library Account';
			$availableHoldSortOptions['libraryAccount']   = 'Library Account';
		}

		$interface->assign('sortOptions', array(
			'available'   => $availableHoldSortOptions,
			'unavailable' => $unavailableHoldSortOptions
		));

		$selectedAvailableSortOption   = !empty($_REQUEST['availableHoldSort']) ? $_REQUEST['availableHoldSort'] : 'expire';
		$selectedUnavailableSortOption = !empty($_REQUEST['unavailableHoldSort']) ? $_REQUEST['unavailableHoldSort'] : ($showPosition ? 'position' : 'title') ;
		$interface->assign('defaultSortOption', array(
			'available'   => $selectedAvailableSortOption,
			'unavailable' => $selectedUnavailableSortOption
			));

		if ($library->showLibraryHoursNoticeOnAccountPages) {
			$libraryHoursMessage = Location::getLibraryHoursMessage($user->homeLocationId);
			$interface->assign('libraryHoursMessage', $libraryHoursMessage);
		}

		$allowChangeLocation = ($ils == 'Millennium' || $ils == 'Sierra');
		$interface->assign('allowChangeLocation', $allowChangeLocation);
		$showDateWhenSuspending = ($ils == 'Horizon' || $ils == 'CarlX' || $ils == 'Symphony' || $ils == 'Koha');
		$interface->assign('showDateWhenSuspending', $showDateWhenSuspending);

		$interface->assign('showPosition', $showPosition);
		$interface->assign('showNotInterested', false);

		// Get My Transactions
		global $offlineMode;
		if (!$offlineMode) {
			if ($user) {

				// Paging not implemented on holds page
//				$recordsPerPage = isset($_REQUEST['pagesize']) && (is_numeric($_REQUEST['pagesize'])) ? $_REQUEST['pagesize'] : 25;
//				$interface->assign('recordsPerPage', $recordsPerPage);

				$allHolds = $user->getMyHolds(true, $selectedUnavailableSortOption, $selectedAvailableSortOption);
				$interface->assign('recordList', $allHolds);

				//make call to export function
				if ((isset($_GET['exportToExcelAvailable'])) || (isset($_GET['exportToExcelUnavailable']))) {
					if (isset($_GET['exportToExcelAvailable'])) {
						$exportType = "available";
					} else {
						$exportType = "unavailable";
					}
					$this->exportToExcel($allHolds, $exportType, $showDateWhenSuspending, $showPosition, $showExpireTime);
				}
			}
		}

// Not displayed, so skipping fetching offline holds for the patron
//		//Load holds that have been entered offline
//		if ($user){
//			//TODO: Offline holds are not displayed on the My Holds page
//			require_once ROOT_DIR . '/sys/OfflineHold.php';
//			$twoDaysAgo = time() - 48 * 60 * 60;
//			$twoWeeksAgo = time() - 14 * 24 * 60 * 60;
//			$offlineHoldsObj = new OfflineHold();
//			$offlineHoldsObj->patronId = $user->id;
//			$offlineHoldsObj->whereAdd("status = 'Not Processed' OR (status = 'Hold Placed' AND timeEntered >= $twoDaysAgo) OR (status = 'Hold Failed' AND timeEntered >= $twoWeeksAgo)");
//			// mysql has these functions as well: "status = 'Not Processed' OR (status = 'Hold Placed' AND timeEntered >= DATE_SUB(NOW(), INTERVAL 2 DAYS)) OR (status = 'Hold Failed' AND timeEntered >= DATE_SUB(NOW(), INTERVAL 2 WEEKS))");
//			$offlineHolds = array();
//			if ($offlineHoldsObj->find()){
//				while ($offlineHoldsObj->fetch()){
//					//Load the title
//					$offlineHold = array();
//					require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
//					$recordDriver = new MarcRecord($offlineHoldsObj->bibId);
//					if ($recordDriver->isValid()){
//						$offlineHold['title'] = $recordDriver->getTitle();
//					}
//					$offlineHold['bibId'] = $offlineHoldsObj->bibId;
//					$offlineHold['timeEntered'] = $offlineHoldsObj->timeEntered;
//					$offlineHold['status'] = $offlineHoldsObj->status;
//					$offlineHold['notes'] = $offlineHoldsObj->notes;
//					$offlineHolds[] = $offlineHold;
//				}
//			}
//			$interface->assign('offlineHolds', $offlineHolds);
//		}

		if (!$library->showDetailedHoldNoticeInformation){
			$notification_method = '';
		}else{
			$notification_method = ($user->noticePreferenceLabel != 'Unknown') ? $user->noticePreferenceLabel : '';
			if ($notification_method == 'Mail' && $library->treatPrintNoticesAsPhoneNotices){
				$notification_method = 'Telephone';
			}
		}
		$interface->assign('notification_method', strtolower($notification_method));

		//print_r($patron);

		// Present to the user
		$this->display('holds.tpl', 'My Holds');
	}

	function isValidTimeStamp($timestamp) {
		return is_numeric($timestamp)
			&& ($timestamp <= PHP_INT_MAX)
			&& ($timestamp >= ~PHP_INT_MAX);
	}

	public function exportToExcel($result, $exportType, $showDateWhenSuspending, $showPosition, $showExpireTime) {
		//PHPEXCEL
		// Create new PHPExcel object
		$objPHPExcel = new PHPExcel();

		// Set properties
		$objPHPExcel->getProperties()->setCreator("DCL")
		->setLastModifiedBy("DCL")
		->setTitle("Office 2007 XLSX Document")
		->setSubject("Office 2007 XLSX Document")
		->setDescription("Office 2007 XLSX, generated using PHP.")
		->setKeywords("office 2007 openxml php")
		->setCategory("Holds");

		if ($exportType == "available") {
			// Add some data
			$objPHPExcel->setActiveSheetIndex(0)
			->setCellValue('A1', 'Holds - '.ucfirst($exportType))
			->setCellValue('A3', 'Title')
			->setCellValue('B3', 'Author')
			->setCellValue('C3', 'Format')
			->setCellValue('D3', 'Placed')
			->setCellValue('E3', 'Pickup')
			->setCellValue('F3', 'Available')
			->setCellValue('G3', translate('Pick-Up By'));
		} else {
			$objPHPExcel->setActiveSheetIndex(0)
			->setCellValue('A1', 'Holds - '.ucfirst($exportType))
			->setCellValue('A3', 'Title')
			->setCellValue('B3', 'Author')
			->setCellValue('C3', 'Format')
			->setCellValue('D3', 'Placed')
			->setCellValue('E3', 'Pickup');

			if ($showPosition){
				$objPHPExcel->getActiveSheet()->setCellValue('F3', 'Position')
				->setCellValue('G3', 'Status');
				if ($showExpireTime){
					$objPHPExcel->getActiveSheet()->setCellValue('H3', 'Expires');
				}
			}else{
				$objPHPExcel->getActiveSheet()
				->setCellValue('F3', 'Status');
				if ($showExpireTime){
					$objPHPExcel->getActiveSheet()->setCellValue('G3', 'Expires');
				}
			}
		}


		$a=4;
		//Loop Through The Report Data
		foreach ($result[$exportType] as $row) {

			$titleCell = preg_replace("/(\/|:)$/", "", $row['title']);
			if (isset ($row['title2'])){
				$titleCell .= preg_replace("/(\/|:)$/", "", $row['title2']);
			}

			if (isset ($row['author'])){
				if (is_array($row['author'])){
					$authorCell = implode(', ', $row['author']);
				}else{
					$authorCell = $row['author'];
				}
				$authorCell = str_replace('&nbsp;', ' ', $authorCell);
			}else{
				$authorCell = '';
			}
			if (isset($row['format'])){
				if (is_array($row['format'])){
					$formatString = implode(', ', $row['format']);
				}else{
					$formatString = $row['format'];
				}
			}else{
				$formatString = '';
			}

			if (empty($row['create'])) {
				$placedDate = '';
			} else {
				$placedDate = $this->isValidTimeStamp($row['create']) ? $row['create'] : strtotime($row['create']);
				$placedDate = date('M d, Y', $placedDate);
			}

			if (empty($row['expire'])) {
				$expireDate = '';
			} else {
				$expireDate = $this->isValidTimeStamp($row['expire']) ? $row['expire'] : strtotime($row['create']);
				$expireDate = date('M d, Y', $expireDate);
			}

			if ($exportType == "available") {
				if (empty($row['availableTime'])) {
					$availableDate = 'Now';
				} else {
					$availableDate = $this->isValidTimeStamp($row['availableTime']) ? $row['availableTime'] : strtotime($row['availableTime']);
					$availableDate =  date('M d, Y', $availableDate);
				}
				$objPHPExcel->getActiveSheet()
				->setCellValue('A'.$a, $titleCell)
				->setCellValue('B'.$a, $authorCell)
				->setCellValue('C'.$a, $formatString)
				->setCellValue('D'.$a, $placedDate)
				->setCellValue('E'.$a, $row['location'])
				->setCellValue('F'.$a, $availableDate)
				->setCellValue('G'.$a, $expireDate);
			} else {
				if (isset($row['status'])){
					$statusCell = $row['status'];
				}else{
					$statusCell = '';
				}

				if (isset($row['frozen']) && $row['frozen'] && $showDateWhenSuspending && !empty($row['reactivateTime'])){
					$reactivateTime = $this->isValidTimeStamp($row['reactivateTime']) ? $row['reactivateTime'] : strtotime($row['reactivateTime']);
					$statusCell .= " until " . date('M d, Y',$reactivateTime);
				}
				$objPHPExcel->getActiveSheet()
				->setCellValue('A'.$a, $titleCell)
				->setCellValue('B'.$a, $authorCell)
				->setCellValue('C'.$a, $formatString)
				->setCellValue('D'.$a, $placedDate);
				if (isset($row['location'])){
					$objPHPExcel->getActiveSheet()->setCellValue('E'.$a, $row['location']);
				}else{
					$objPHPExcel->getActiveSheet()->setCellValue('E'.$a, '');
				}

				if ($showPosition){
					if (isset($row['position'])){
						$objPHPExcel->getActiveSheet()->setCellValue('F'.$a, $row['position']);
					}else{
						$objPHPExcel->getActiveSheet()->setCellValue('F'.$a, '');
					}

					$objPHPExcel->getActiveSheet()->setCellValue('G'.$a, $statusCell);
					if ($showExpireTime){
						$objPHPExcel->getActiveSheet()->setCellValue('H'.$a, $expireDate);
					}
				}else{
					$objPHPExcel->getActiveSheet()->setCellValue('F'.$a, $statusCell);
					if ($showExpireTime){
						$objPHPExcel->getActiveSheet()->setCellValue('G'.$a, $expireDate);
					}
				}
			}
			$a++;
		}
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);


		// Rename sheet
		$objPHPExcel->getActiveSheet()->setTitle('Holds');

		// Redirect output to a client's web browser (Excel5)
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="Holds.xls"');
		header('Cache-Control: max-age=0');

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
		exit;

	}
}