<?php

require_once ROOT_DIR . '/Action.php';
require_once(ROOT_DIR . '/services/Admin/Admin.php');
require_once(ROOT_DIR . '/sys/MaterialsRequest.php');
require_once(ROOT_DIR . '/sys/MaterialsRequestStatus.php');
require_once(ROOT_DIR . "/PHPExcel.php");

class MaterialsRequest_UserReport extends Admin_Admin {

	function launch()
	{
		global $configArray;
		global $interface;
		//Load status information
		$materialsRequestStatus = new MaterialsRequestStatus();
		$materialsRequestStatus->orderBy('isDefault DESC, isOpen DESC, description ASC');
		$homeLibrary = Library::getPatronHomeLibrary();
		$materialsRequestStatus->libraryId = $homeLibrary->libraryId;

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
		$materialsRequest->joinAdd(new User(), 'INNER', 'user', 'createdBy', 'id');
		$materialsRequest->joinAdd(new MaterialsRequestStatus(), 'INNER', 'status', 'status', 'id');
		$materialsRequest->selectAdd();
		$materialsRequest->selectAdd('COUNT(materials_request.id) as numRequests');
		$materialsRequest->selectAdd('user.id as userId, createdBy, status, description');
		if (UserAccount::userHasPermission('View Materials Requests Reports')){
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
			$statusSql .= $materialsRequest->escape($status);
		}
		$materialsRequest->whereAdd("status in ($statusSql)");
		$materialsRequest->groupBy('createdBy, status');
		$materialsRequest->find();

		$userData = array();
		while ($materialsRequest->fetch()){
			if (!array_key_exists($materialsRequest->createdBy, $userData)){
				$userData[$materialsRequest->createdBy] = array();
				$userData[$materialsRequest->createdBy]['firstName'] = $materialsRequest->getCreatedByFirstName();
				$userData[$materialsRequest->createdBy]['lastName'] = $materialsRequest->getCreatedByLastName();
				$userData[$materialsRequest->createdBy]['barcode'] = $materialsRequest->getCreatedByUserBarcode();
				$userData[$materialsRequest->createdBy]['totalRequests'] = 0;
				$userData[$materialsRequest->createdBy]['requestsByStatus'] = array();
			}
			$userData[$materialsRequest->createdBy]['requestsByStatus'][$materialsRequest->description] = $materialsRequest->numRequests;
			$userData[$materialsRequest->createdBy]['totalRequests'] += $materialsRequest->numRequests;
		}
		$interface->assign('userData', $userData);

		//Get a list of all of the statuses that will be shown
		$statuses = array();
		foreach ($userData as $userInfo){
			foreach ($userInfo['requestsByStatus'] as $status => $numRequests){
				$statuses[$status] = translate(['text'=>$status, 'isAdminFacing'=>true]);
			}
		}
		$interface->assign('statuses', $statuses);

		//Check to see if we are exporting to Excel
		if (isset($_REQUEST['exportToExcel'])){
			$this->exportToExcel($userData, $statuses);
		}

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

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MaterialsRequest/ManageRequests', 'Manage Materials Requests');
		$breadcrumbs[] = new Breadcrumb('', 'User Report');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'materials_request';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission('View Materials Requests Reports');
	}
}
