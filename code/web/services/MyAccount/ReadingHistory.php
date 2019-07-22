<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
require_once ROOT_DIR . '/sys/Pager.php';

class ReadingHistory extends MyAccount
{
	function launch()
	{
		global $configArray;
		global $interface;
		global $library;
		$interface->assign('showRatings', $library->showRatings);

		global $offlineMode;
		if (!$offlineMode) {
			$interface->assign('offline', false);

			$user = UserAccount::getActiveUserObj();

			// Get My Transactions
			if ($user) {
				$linkedUsers = $user->getLinkedUsers();
				if (count($linkedUsers) > 0) {
					array_unshift($linkedUsers, $user);
					$interface->assign('linkedUsers', $linkedUsers);
				}
				$patronId = empty($_REQUEST['patronId']) ?  $user->id : $_REQUEST['patronId'];

				$patron = $user->getUserReferredTo($patronId);

				$interface->assign('selectedUser', $patronId); // needs to be set even when there is only one user so that the patronId hidden input gets a value in the reading history form.

				$interface->assign('historyActive', $patron->trackReadingHistory);
				//Check to see if there is an action to perform.
				if (!empty($_REQUEST['readingHistoryAction']) && $_REQUEST['readingHistoryAction'] != 'exportToExcel'){
					//Perform the requested action
					$selectedTitles = isset($_REQUEST['selected']) ? $_REQUEST['selected'] : array();
					$readingHistoryAction = $_REQUEST['readingHistoryAction'];
					$patron->doReadingHistoryAction($readingHistoryAction, $selectedTitles);

					//redirect back to the current location without the action.
					$newLocation = "{$configArray['Site']['path']}/MyAccount/ReadingHistory";
					if (isset($_REQUEST['page']) && $readingHistoryAction != 'deleteAll' && $readingHistoryAction != 'optOut'){
						$params[] = 'page=' . $_REQUEST['page'];
					}
					if (isset($_REQUEST['accountSort'])){
						$params[] = 'accountSort=' . $_REQUEST['accountSort'];
					}
					if (isset($_REQUEST['pageSize'])){
						$params[] = 'pageSize=' . $_REQUEST['pageSize'];
					}
					if (isset($_REQUEST['patronId'])){
						$params[] = 'patronId=' . $_REQUEST['patronId'];
					}
					if (!empty($params)){
						$additionalParams = implode('&', $params);
						$newLocation .= '?' . $additionalParams;
					}
					header("Location: $newLocation");
					die();
				}
			}
		}

		$this->display('readingHistory.tpl', 'Reading History');
	}

	public function exportToExcel($readingHistory) {
		try{
			// Create new PHPExcel object
			$objPHPExcel = new PHPExcel();

			// Set properties
			$objPHPExcel->getProperties()->setCreator("DCL")
			->setLastModifiedBy("DCL")
			->setTitle("Office 2007 XLSX Document")
			->setSubject("Office 2007 XLSX Document")
			->setDescription("Office 2007 XLSX, generated using PHP.")
			->setKeywords("office 2007 openxml php")
			->setCategory("Checked Out Items");

			$objPHPExcel->setActiveSheetIndex(0)
			->setCellValue('A1', 'Reading History')
			->setCellValue('A3', 'Title')
			->setCellValue('B3', 'Author')
			->setCellValue('C3', 'Format')
			->setCellValue('D3', 'From')
			->setCellValue('E3', 'To');

			$a=4;
			//Loop Through The Report Data
			foreach ($readingHistory as $row) {

				$format = is_array($row['format']) ? implode(',', $row['format']) : $row['format'];
				$lastCheckout = isset($row['lastCheckout']) ? date('Y-M-d', $row['lastCheckout']) : '';
				$objPHPExcel->setActiveSheetIndex(0)
				->setCellValue('A'.$a, $row['title'])
				->setCellValue('B'.$a, $row['author'])
				->setCellValue('C'.$a, $format)
				->setCellValue('D'.$a, date('Y-M-d', $row['checkout']))
				->setCellValue('E'.$a, $lastCheckout);

				$a++;
			}
			$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
			$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
			$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
			$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
			$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);

			// Rename sheet
			$objPHPExcel->getActiveSheet()->setTitle('Reading History');

			// Redirect output to a client's web browser (Excel5)
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment;filename="ReadingHistory.xls"');
			header('Cache-Control: max-age=0');

			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$objWriter->save('php://output');
		}catch (Exception $e){
			global $logger;
			$logger->log("Error exporting to Excel " . $e, Logger::LOG_ERROR );
		}
		exit;
	}
}