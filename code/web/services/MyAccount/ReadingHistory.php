<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
require_once ROOT_DIR . '/sys/Pager.php';

class ReadingHistory extends MyAccount
{
	function launch()
	{
		global $configArray;
		global $interface;
		$user = UserAccount::getLoggedInUser();

		global $library;
		if (isset($library)){
			$interface->assign('showRatings', $library->showRatings);
		}else{
			$interface->assign('showRatings', 1);
		}

		global $offlineMode;
		if (!$offlineMode) {
			$interface->assign('offline', false);

			// Get My Transactions
			if ($user) {
				$linkedUsers = $user->getLinkedUsers();
				$patronId = empty($_REQUEST['patronId']) ?  $user->id : $_REQUEST['patronId'];

				$patron = $user->getUserReferredTo($patronId);
				if (count($linkedUsers) > 0) {
					array_unshift($linkedUsers, $user);
					$interface->assign('linkedUsers', $linkedUsers);
				}
				$interface->assign('selectedUser', $patronId); // needs to be set even when there is only one user so that the patronId hidden input gets a value in the reading history form.

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
					if (isset($_REQUEST['pagesize'])){
						$params[] = 'pagesize=' . $_REQUEST['pagesize'];
					}
					if (isset($_REQUEST['patronId'])){
						$params[] = 'patronId=' . $_REQUEST['patronId'];
					}
					if (count($params) > 0){
						$additionalParams = implode('&', $params);
						$newLocation .= '?' . $additionalParams;
					}
					header("Location: $newLocation");
					die();
				}

				// Define sorting options
				$sortOptions = array('title' => 'Title',
				                     'author' => 'Author',
				                     'checkedOut' => 'Checkout Date',
				                     'format' => 'Format',
				);
				$selectedSortOption = isset($_REQUEST['accountSort']) ? $_REQUEST['accountSort'] : 'checkedOut';
				$interface->assign('sortOptions', $sortOptions);

				$interface->assign('defaultSortOption', $selectedSortOption);
				$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
				$interface->assign('page', $page);

				$recordsPerPage = isset($_REQUEST['pagesize']) && (is_numeric($_REQUEST['pagesize'])) ? $_REQUEST['pagesize'] : 25;
				$interface->assign('recordsPerPage', $recordsPerPage);
				if (isset($_REQUEST['readingHistoryAction']) && $_REQUEST['readingHistoryAction'] == 'exportToExcel'){
					$recordsPerPage = -1;
					$page = 1;
				}

				if (!$patron){
					PEAR_Singleton::RaiseError(new PEAR_Error("The patron provided is invalid"));
				}
				$result = $patron->getReadingHistory($page, $recordsPerPage, $selectedSortOption);

				$link = $_SERVER['REQUEST_URI'];
				if (preg_match('/[&?]page=/', $link)){
					$link = preg_replace("/page=\\d+/", "page=%d", $link);
				}else if (strpos($link, "?") > 0){
					$link .= "&page=%d";
				}else{
					$link .= "?page=%d";
				}
				if ($recordsPerPage != '-1'){
					$options = array('totalItems' => $result['numTitles'],
					                 'fileName'   => $link,
					                 'perPage'    => $recordsPerPage,
					                 'append'     => false,
					                 );
					$pager = new VuFindPager($options);
					$interface->assign('pageLinks', $pager->getLinks());
				}
				if (!PEAR_Singleton::isError($result)) {
					$interface->assign('historyActive', $result['historyActive']);
					$interface->assign('transList', $result['titles']);
					if (isset($_REQUEST['readingHistoryAction']) && $_REQUEST['readingHistoryAction'] == 'exportToExcel'){
						$this->exportToExcel($result['titles']);
					}
				}
			}
		}

		$this->display('readingHistory.tpl', 'Reading History');
	}

	public function exportToExcel($readingHistory) {
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
		exit;

	}
}