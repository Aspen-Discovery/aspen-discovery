<?php

require_once ROOT_DIR . '/Action.php';
require_once(ROOT_DIR . '/services/Admin/Admin.php');
require_once(ROOT_DIR . '/sys/MaterialsRequest.php');
require_once(ROOT_DIR . '/sys/MaterialsRequestStatus.php');
require_once(ROOT_DIR . "/PHPExcel.php");

class MaterialsRequest_SummaryReport extends Admin_Admin {

	function launch()
	{
		global $interface;

		$period = isset($_REQUEST['period']) ? $_REQUEST['period'] : 'week';
		if ($period == 'week'){
			$periodLength  = new DateInterval("P1W");
		}elseif ($period == 'day'){
			$periodLength = new DateInterval("P1D");
		}elseif ($period == 'month'){
			$periodLength = new DateInterval("P1M");
		}else{ //year
			$periodLength = new DateInterval("P1Y");
		}
		$interface->assign('period', $period);

		$endDate = (isset($_REQUEST['endDate']) && strlen($_REQUEST['endDate']) > 0) ? DateTime::createFromFormat('Y-m-d', $_REQUEST['endDate']) : new DateTime();
		$interface->assign('endDate', $endDate->format('Y-m-d'));

		if (isset($_REQUEST['startDate']) && strlen($_REQUEST['startDate']) > 0){
			$startDate = DateTime::createFromFormat('Y-m-d', $_REQUEST['startDate']);
		} else{
			if ($period == 'day'){
				$startDate = new DateTime($endDate->format('Y-m-d') . " - 7 days");
			}elseif ($period == 'week'){
				//Get the sunday after this
				$endDate->setISODate($endDate->format('Y'), $endDate->format("W"), 0);
				$endDate->modify("+7 days");
				$startDate = new DateTime($endDate->format('Y-m-d') . " - 28 days");
			}elseif ($period == 'month'){
				$endDate->modify("+1 month");
				$numDays = $endDate->format("d");
				$endDate->modify(" -$numDays days");
				$startDate = new DateTime($endDate->format('Y-m-d') . " - 6 months");
			}else{ //year
				$endDate->modify("+1 year");
				$numDays = $endDate->format("m");
				$endDate->modify(" -$numDays months");
				$numDays = $endDate->format("d");
				$endDate->modify(" -$numDays days");
				$startDate = new DateTime($endDate->format('Y-m-d') . " - 2 years");
			}
		}

		$interface->assign('startDate', $startDate->format('Y-m-d'));

		//Set the end date to the end of the day
		$endDate->setTime(24, 0, 0);
		$startDate->setTime(0, 0, 0);

		//Create the periods that are being represented
		$periods = array();
		$periodEnd = clone $endDate;
		while ($periodEnd >= $startDate){
			array_unshift($periods, clone $periodEnd);
			$periodEnd->sub($periodLength);
		}
		//print_r($periods);

		//Load data for each period
		//this will be a two dimensional array
		//         Period 1, Period 2, Period 3
		//Status 1
		//Status 2
		//Status 3
		$periodData = array();

		$locationsToRestrictTo = '';
		if (UserAccount::userHasPermission('View Materials Requests Reports')){
			//Need to limit to only requests submitted for the user's home location
			$userHomeLibrary = Library::getPatronHomeLibrary();
			if (is_null($userHomeLibrary)) {
				//User does not have a home library, this is likely an admin account.  Use the active library
				global $library;
				$userHomeLibrary = $library;
			}
			$locations = new Location();
			$locations->libraryId = $userHomeLibrary->libraryId;
			$locations->find();
			$locationsForLibrary = array();
			while ($locations->fetch()){
				$locationsForLibrary[] = $locations->locationId;
			}
			$locationsToRestrictTo = implode(', ', $locationsForLibrary);

		}

		for ($i = 0; $i < count($periods) - 1; $i++){
			/** @var DateTime $periodStart */
			$periodStart = clone $periods[$i];
			/** @var DateTime $periodEnd */
			$periodEnd = clone $periods[$i+1];

			$periodData[$periodStart->getTimestamp()] = array();
			//Determine how many requests were created
			$materialsRequest = new MaterialsRequest();
			$materialsRequest->joinAdd(new User(), 'INNER', 'user', 'createdBy', 'id');
			$materialsRequest->selectAdd();
			$materialsRequest->selectAdd('COUNT(materials_request.id) as numRequests');
			$materialsRequest->whereAdd('dateCreated >= ' . $periodStart->getTimestamp() . ' AND dateCreated < ' . $periodEnd->getTimestamp());
			if ($locationsToRestrictTo != ''){
				$materialsRequest->whereAdd('user.homeLocationId IN (' . $locationsToRestrictTo . ')');
			}

			$materialsRequest->find();
			while ($materialsRequest->fetch()){
				$periodData[$periodStart->getTimestamp()]['Created'] = $materialsRequest->numRequests;
			}

			//Get a list of all requests by the status of the request
			$materialsRequest = new MaterialsRequest();
			$materialsRequest->joinAdd(new MaterialsRequestStatus(), 'INNER', 'status', 'status', 'id');
			$materialsRequest->joinAdd(new User(), 'INNER', 'user', 'createdBy', 'id');
			$materialsRequest->selectAdd();
			$materialsRequest->selectAdd('COUNT(materials_request.id) as numRequests, description as description');
			$materialsRequest->whereAdd('dateUpdated >= ' . $periodStart->getTimestamp() . ' AND dateUpdated < ' . $periodEnd->getTimestamp());
			if (UserAccount::userHasPermission('View Materials Requests Reports')){
				$materialsRequest->whereAdd('user.homeLocationId IN (' . $locationsToRestrictTo . ')');
			}
			$materialsRequest->groupBy('status');
			$materialsRequest->orderBy('status');
			$materialsRequest->find();
			while ($materialsRequest->fetch()){
				$periodData[$periodStart->getTimestamp()][$materialsRequest->description] = $materialsRequest->numRequests;
			}
		}

		$interface->assign('periodData', $periodData, $periods);

		//Get a list of all of the statuses that will be shown
		$statuses = array();
		foreach ($periodData as $periodInfo){
			foreach ($periodInfo as $status => $numRequests){
				$statuses[$status] = translate(['text'=>$status, 'isAdminFacing'=>true]);
			}
		}
		$interface->assign('statuses', $statuses);

		//Check to see if we are exporting to Excel
		if (isset($_REQUEST['exportToExcel'])){
			$this->exportToExcel($periodData, $statuses);
		}else{
			//Generate the graph
			$this->generateGraph($periodData, $statuses);
		}

		$this->display('summaryReport.tpl', 'Materials Request Summary Report');
	}

	function exportToExcel($periodData, $statuses){
		global $configArray;
		// Create new PHPExcel object
		$objPHPExcel = new PHPExcel();

		// Set properties
		$objPHPExcel->getProperties()->setCreator($configArray['Site']['title'])
				->setLastModifiedBy($configArray['Site']['title'])
				->setTitle("Materials Request Summary Report")
				->setSubject("Materials Request")
				->setCategory("Materials Request Summary Report");

		// Add some data
		$objPHPExcel->setActiveSheetIndex(0);
		$activeSheet = $objPHPExcel->getActiveSheet();
		$activeSheet->setCellValue('A1', 'Materials Request Summary Report');
		$activeSheet->setCellValue('A3', 'Date');
		$column = 1;
		foreach ($statuses as $statusLabel){
			$activeSheet->setCellValueByColumnAndRow($column++, 3, $statusLabel);
		}

		$row = 4;
		$column = 0;
		//Loop Through The Report Data
		foreach ($periodData as $date => $periodInfo) {
			$activeSheet->setCellValueByColumnAndRow($column++, $row, date('M-d-Y', $date));
			foreach ($statuses as $status => $statusLabel){
				$activeSheet->setCellValueByColumnAndRow($column++, $row, isset($periodInfo[$status]) ? $periodInfo[$status] : 0);
			}
			$row++;
			$column = 0;
		}
		for ($i = 0; $i < count($statuses) + 1; $i++){
			$activeSheet->getColumnDimensionByColumn($i)->setAutoSize(true);
		}

		// Rename sheet
		$activeSheet->setTitle('Summary Report');

		// Redirect output to a client's web browser (Excel5)
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="MaterialsRequestSummaryReport.xls"');
		header('Cache-Control: max-age=0');

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
		exit;
	}

	function generateGraph($periodData, $statuses){
		global $interface;

		$graphColors = [
			0 => [255, 99, 132],
			1 => [54, 162, 235],
			2 => [255, 159, 64],
			3 => [0, 255, 55, 1],
			4 => [154, 75, 244],
			5 => [255, 206, 86],
			6 => [75, 192, 192],
			7 => [153, 102, 255],
			8 => [165, 42, 42],
			9 => [50, 205, 50],
			10 => [220, 60, 20],
		];
		$dataSeries = [];
		$columnLabels = [];

		//Add points for each status
		$statusNumber = 0;
		foreach ($periodData as $date => $periodInfo){
			$columnLabels[] = date('M-d-Y', $date);;
		}
		foreach ($statuses as $status => $statusLabel){
			$curColor = $statusNumber % 10;
			$dataSeries[$statusLabel] = [
				'borderColor' => "rgba({$graphColors[$curColor][0]}, {$graphColors[$curColor][1]}, {$graphColors[$curColor][2]}, 1)",
				'backgroundColor' => "rgba({$graphColors[$curColor][0]}, {$graphColors[$curColor][1]}, {$graphColors[$curColor][2]}, 0.2)",
				'data' => []
			];
			foreach ($periodData as $date => $periodInfo){
				$dataSeries[$statusLabel]['data'][$date] = isset($periodInfo[$status]) ? $periodInfo[$status] : 0;
			}
			$statusNumber++;
		}

		$interface->assign('columnLabels', $columnLabels);
		$interface->assign('dataSeries', $dataSeries);
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MaterialsRequest/ManageRequests', 'Manage Materials Requests');
		$breadcrumbs[] = new Breadcrumb('', 'Summary Report');
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
