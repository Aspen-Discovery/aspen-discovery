<?php
/**
 *
 * Copyright (C) Anythink Libraries 2012.
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
 * @author Mark Noble <mnoble@turningleaftech.com>
 * @copyright Copyright (C) Anythink Libraries 2012.
 *
 */

require_once ROOT_DIR . '/Action.php';
require_once(ROOT_DIR . '/services/Admin/Admin.php');
require_once(ROOT_DIR . '/sys/MaterialsRequest.php');
require_once(ROOT_DIR . '/sys/MaterialsRequestStatus.php');
require_once(ROOT_DIR . "/sys/pChart/class/pData.class.php");
require_once(ROOT_DIR . "/sys/pChart/class/pDraw.class.php");
require_once(ROOT_DIR . "/sys/pChart/class/pImage.class.php");
require_once(ROOT_DIR . "/PHPExcel.php");

class MaterialsRequest_UserReport extends Admin_Admin {

	function launch()
	{
		global $configArray;
		global $interface;
		$user = UserAccount::getLoggedInUser();

		//Load status information
		$materialsRequestStatus = new MaterialsRequestStatus();
		$materialsRequestStatus->orderBy('isDefault DESC, isOpen DESC, description ASC');
		if (UserAccount::userHasRole('library_material_requests')){
			$homeLibrary = Library::getPatronHomeLibrary();
			$materialsRequestStatus->libraryId = $homeLibrary->libraryId;
		}
		$materialsRequestStatus->find();
		$availableStatuses = array();
		$defaultStatusesToShow = array();
		while ($materialsRequestStatus->fetch()){
			$availableStatuses[$materialsRequestStatus->id] = $materialsRequestStatus->description;
			if ($materialsRequestStatus->isOpen == 1 || $materialsRequestStatus->isDefault == 1){
				$defaultStatusesToShow[] = $materialsRequestStatus->id;
			}
		}
		$interface->assign('availableStatuses', $availableStatuses);

		if (isset($_REQUEST['statusFilter'])){
			$statusesToShow = $_REQUEST['statusFilter'];
		}else{
			$statusesToShow = $defaultStatusesToShow;
		}
		$interface->assign('statusFilter', $statusesToShow);

		//Get a list of users that have requests open
		$materialsRequest = new MaterialsRequest();
		$materialsRequest->joinAdd(array('createdBy', new User(), 'id'));
		$materialsRequest->joinAdd(new MaterialsRequestStatus());
		$materialsRequest->selectAdd();
		$materialsRequest->selectAdd('COUNT(materials_request.id) as numRequests');
		$materialsRequest->selectAdd('user.id as userId, status, description, user.firstName, user.lastName, user.cat_username, user.cat_password');
		if (UserAccount::userHasRole('library_material_requests')){
			//Need to limit to only requests submitted for the user's home location
			$userHomeLibrary = Library::getPatronHomeLibrary();
			$locations = new Location();
			$locations->libraryId = $userHomeLibrary->libraryId;
			$locations->find();
			$locationsForLibrary = array();
			while ($locations->fetch()){
				$locationsForLibrary[] = $locations->locationId;
			}

			$materialsRequest->whereAdd('user.homeLocationId IN (' . implode(', ', $locationsForLibrary) . ')');
		}
		$statusSql = "";
		foreach ($statusesToShow as $status){
			if (strlen($statusSql) > 0) $statusSql .= ",";
			$statusSql .= "'" . $materialsRequest->escape($status) . "'";
		}
		$materialsRequest->whereAdd("status in ($statusSql)");
		$materialsRequest->groupBy('userId, status');
		$materialsRequest->find();

		$userData = array();
		$barcodeProperty = $configArray['Catalog']['barcodeProperty'];
		while ($materialsRequest->fetch()){
			if (!array_key_exists($materialsRequest->userId, $userData)){
				$userData[$materialsRequest->userId] = array();
				$userData[$materialsRequest->userId]['firstName'] = $materialsRequest->firstName;
				$userData[$materialsRequest->userId]['lastName'] = $materialsRequest->lastName;
				$userData[$materialsRequest->userId]['barcode'] = $materialsRequest->$barcodeProperty;
				$userData[$materialsRequest->userId]['totalRequests'] = 0;
				$userData[$materialsRequest->userId]['requestsByStatus'] = array();
			}
			$userData[$materialsRequest->userId]['requestsByStatus'][$materialsRequest->description] = $materialsRequest->numRequests;
			$userData[$materialsRequest->userId]['totalRequests'] += $materialsRequest->numRequests;
		}
		$interface->assign('userData', $userData);

		//Get a list of all of the statuses that will be shown
		$statuses = array();
		foreach ($userData as $userInfo){
			foreach ($userInfo['requestsByStatus'] as $status => $numRequests){
				$statuses[$status] = translate($status);
			}
		}
		$interface->assign('statuses', $statuses);

		//Check to see if we are exporting to Excel
		if (isset($_REQUEST['exportToExcel'])){
			$this->exportToExcel($userData, $statuses);
		}

//		$interface->setTemplate('userReport.tpl');
//		$interface->setPageTitle('Materials Request User Report');
//		$interface->assign('sidebar', 'MyAccount/account-sidebar.tpl');
//		$interface->display('layout.tpl');

		$this->display('userReport.tpl', 'Materials Request User Report');
	}

	function exportToExcel($userData, $statuses){
		global $configArray;
		//PHPEXCEL
		// Create new PHPExcel object
		$objPHPExcel = new PHPExcel();

		// Set properties
		$objPHPExcel->getProperties()->setCreator($configArray['Site']['title'])
		->setLastModifiedBy($configArray['Site']['title'])
		->setTitle("Office 2007 XLSX Document")
		->setSubject("Office 2007 XLSX Document")
		->setDescription("Office 2007 XLSX, generated using PHP.")
		->setKeywords("office 2007 openxml php")
		->setCategory("Materials Request User Report");

		// Add some data
		$objPHPExcel->setActiveSheetIndex(0);
		$activeSheet = $objPHPExcel->getActiveSheet();
		$activeSheet->setCellValue('A1', 'Materials Request User Report');
		$activeSheet->setCellValue('A3', 'Last Name');
		$activeSheet->setCellValue('B3', 'First Name');
		$activeSheet->setCellValue('C3', 'Barcode');
		$column = 3;
		foreach ($statuses as $statusLabel){
			$activeSheet->setCellValueByColumnAndRow($column++, 3, $statusLabel);
		}
		$activeSheet->setCellValueByColumnAndRow($column, 3, 'Total');

		$row = 4;
		$column = 0;
		//Loop Through The Report Data
		foreach ($userData as $userInfo) {
			$activeSheet->setCellValueByColumnAndRow($column++, $row, $userInfo['lastName']);
			$activeSheet->setCellValueByColumnAndRow($column++, $row, $userInfo['firstName']);
			$activeSheet->setCellValueByColumnAndRow($column++, $row, $userInfo['barcode']);
			foreach ($statuses as $status => $statusLabel){
				$activeSheet->setCellValueByColumnAndRow($column++, $row, isset($userInfo['requestsByStatus'][$status]) ? $userInfo['requestsByStatus'][$status] : 0);
			}
			$activeSheet->setCellValueByColumnAndRow($column, $row, $userInfo['totalRequests']);
			$row++;
			$column = 0;
		}
		for ($i = 0; $i < count($statuses) + 3; $i++){
			$activeSheet->getColumnDimensionByColumn($i)->setAutoSize(true);
		}

		// Rename sheet
		$activeSheet->setTitle('User Report');

		// Redirect output to a client's web browser (Excel5)
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="MaterialsRequestUserReport.xls"');
		header('Cache-Control: max-age=0');

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
		exit;

	}

	function getAllowableRoles(){
		return array('library_material_requests');
	}
}
