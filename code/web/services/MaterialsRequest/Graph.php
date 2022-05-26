<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/SystemLogging/AspenUsage.php';
require_once ROOT_DIR . '/sys/MaterialsRequestUsage.php';

class MaterialsRequest_Graph extends Admin_Admin
{
	function launch()
	{
		global $interface;
		$title = 'Materials Request Usage Graph';
		$status = $_REQUEST['status'];
		$location = $_REQUEST['location'];

		$interface->assign('curStatus', $status);
		$interface->assign('curLocation', $location);


		$dataSeries = [];
		$columnLabels = [];

		if($location !== '') {
			$thisStatus = new MaterialsRequestStatus();
			$thisStatus->id = $status;
			$thisStatus->libraryId = $location;
			$thisStatus->find(true);
			$title = 'Materials Request Usage Graph - ' . $thisStatus->description;
			$materialsRequestUsage = new MaterialsRequestUsage();
			$materialsRequestUsage->groupBy('year, month');
			$materialsRequestUsage->selectAdd();
			$materialsRequestUsage->statusId = $status;
			$materialsRequestUsage->locationId = $location;
			$materialsRequestUsage->selectAdd('year');
			$materialsRequestUsage->selectAdd('month');
			$materialsRequestUsage->selectAdd('SUM(numUsed) as numUsed');
			$materialsRequestUsage->orderBy('year, month');

			$dataSeries[$thisStatus->description] = [
				'borderColor' => 'rgba(255, 99, 132, 1)',
				'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
				'data' => []
			];

			//Collect results
			$materialsRequestUsage->find();

			while ($materialsRequestUsage->fetch()) {
				$curPeriod = "{$materialsRequestUsage->month}-{$materialsRequestUsage->year}";
				$columnLabels[] = $curPeriod;
				$dataSeries[$thisStatus->description]['data'][$curPeriod] = $materialsRequestUsage->numUsed != null ? $materialsRequestUsage->numUsed : "0";
			}

			$interface->assign('columnLabels', $columnLabels);
			$interface->assign('dataSeries', $dataSeries);
		} else {
			$userHomeLibrary = Library::getPatronHomeLibrary();
			if (is_null($userHomeLibrary)) {
				//User does not have a home library, this is likely an admin account.  Use the active library
				global $library;
				$userHomeLibrary = $library;
			}
			$locations = new Location();
			$locations->libraryId = $userHomeLibrary->libraryId;
			$locations->find();
			while ($locations->fetch()){
				$thisStatus = new MaterialsRequestStatus();
				$thisStatus->id = $status;
				$thisStatus->libraryId = $locations->locationId;
				$thisStatus->find();
				while($thisStatus->fetch()) {
					$title = 'Materials Request Usage Graph - ' . $thisStatus->description;
					$materialsRequestUsage = new MaterialsRequestUsage();
					$materialsRequestUsage->groupBy('year, month');
					$materialsRequestUsage->selectAdd();
					$materialsRequestUsage->statusId = $status;
					$materialsRequestUsage->selectAdd('year');
					$materialsRequestUsage->selectAdd('month');
					$materialsRequestUsage->selectAdd('SUM(numUsed) as numUsed');
					$materialsRequestUsage->orderBy('year, month');

					$dataSeries[$thisStatus->description] = [
						'borderColor' => 'rgba(255, 99, 132, 1)',
						'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
						'data' => []
					];

					//Collect results
					$materialsRequestUsage->find();

					while ($materialsRequestUsage->fetch()) {
						$curPeriod = "{$materialsRequestUsage->month}-{$materialsRequestUsage->year}";
						$columnLabels[] = $curPeriod;
						$dataSeries[$thisStatus->description]['data'][$curPeriod] = $materialsRequestUsage->numUsed;
					}
				}
			}

			$interface->assign('columnLabels', $columnLabels);
			$interface->assign('dataSeries', $dataSeries);
		}

		$interface->assign('graphTitle', $title);

		//Check to see if we are exporting to Excel
		if (isset($_REQUEST['exportToExcel'])){
			$this->exportToExcel();
		}

		$this->display('graph.tpl', $title);
	}

	public function getAllPeriods()
	{
		$usage = new MaterialsRequestUsage();
		$usage->selectAdd(null);
		$usage->selectAdd('DISTINCT year, month');
		$usage->find();

		$stats = [];
		while($usage->fetch()) {
			$stats[$usage->month . '-' . $usage->year]['year'] = $usage->year;
			$stats[$usage->month . '-' . $usage->year]['month'] = $usage->month;
		}
		return $stats;
	}

	function exportToExcel(){
		global $configArray;
		// Create new PHPExcel object
		$objPHPExcel = new PHPExcel();

		$location = $_REQUEST['location'];
		$status = $_REQUEST['status'];

		$periods = $this->getAllPeriods();

		// Set properties
		$objPHPExcel->getProperties()->setCreator($configArray['Site']['title'])
			->setLastModifiedBy($configArray['Site']['title'])
			->setTitle("Materials Request Dashboard Report")
			->setSubject("Materials Request")
			->setCategory("Materials Request Dashboard Report");

		// Add some data
		$objPHPExcel->setActiveSheetIndex(0);
		$activeSheet = $objPHPExcel->getActiveSheet();
		$activeSheet->setCellValue('A1', 'Materials Request Dashboard Report');
		$activeSheet->setCellValue('A3', 'Date');

		if($location !== '' && $location !== null) {
			$thisStatus = new MaterialsRequestStatus();
			$thisStatus->id = $status;
			$thisStatus->libraryId = $location;
			$currentAlpha = 0;
			$alphas = range('B', 'Z');
			$curCol = 1;
			if($thisStatus->find(true)) {
				$curRow = 4;
				$labelCell = $alphas[$currentAlpha] . '3';
				$activeSheet->setCellValue($labelCell, $thisStatus->description);

				foreach($periods as $period) {
					$materialsRequestUsage = new MaterialsRequestUsage();
					$materialsRequestUsage->groupBy('year, month');
					$materialsRequestUsage->selectAdd();
					$materialsRequestUsage->statusId = $status;
					$materialsRequestUsage->locationId = $location;
					$materialsRequestUsage->year = $period['year'];
					$materialsRequestUsage->month = $period['month'];
					$materialsRequestUsage->selectAdd('year');
					$materialsRequestUsage->selectAdd('month');
					$materialsRequestUsage->selectAdd('SUM(numUsed) as numUsed');
					$materialsRequestUsage->orderBy('year, month');

					if($materialsRequestUsage->find(true)) {
						$activeSheet->setCellValueByColumnAndRow(0, $curRow, "{$materialsRequestUsage->month}-{$materialsRequestUsage->year}");
						$activeSheet->setCellValueByColumnAndRow($curCol, $curRow, $materialsRequestUsage->numUsed ?? "0");
						$curRow++;
					} else {
						$activeSheet->setCellValueByColumnAndRow($curCol, $curRow, "0");
						$curRow++;
					};
				}
			}

		} else {
			$userHomeLibrary = Library::getPatronHomeLibrary();
			if (is_null($userHomeLibrary)) {
				//User does not have a home library, this is likely an admin account.  Use the active library
				global $library;
				$userHomeLibrary = $library;
			}
			$locations = new Location();
			$locations->libraryId = $userHomeLibrary->libraryId;
			$locations->find();
			while ($locations->fetch()) {
				$thisStatus = new MaterialsRequestStatus();
				$thisStatus->id = $status;
				$currentAlpha = 0;
				$alphas = range('B', 'Z');
				$curCol = 1;
				if($thisStatus->find(true)) {
					$curRow = 4;
					$labelCell = $alphas[$currentAlpha] . '3';
					$activeSheet->setCellValue($labelCell, $thisStatus->description);

					foreach($periods as $period) {
						$materialsRequestUsage = new MaterialsRequestUsage();
						$materialsRequestUsage->groupBy('year, month');
						$materialsRequestUsage->selectAdd();
						$materialsRequestUsage->statusId = $status;
						$materialsRequestUsage->year = $period['year'];
						$materialsRequestUsage->month = $period['month'];
						$materialsRequestUsage->selectAdd('year');
						$materialsRequestUsage->selectAdd('month');
						$materialsRequestUsage->selectAdd('SUM(numUsed) as numUsed');
						$materialsRequestUsage->orderBy('year, month');

						if($materialsRequestUsage->find(true)) {
							$activeSheet->setCellValueByColumnAndRow(0, $curRow, "{$materialsRequestUsage->month}-{$materialsRequestUsage->year}");
							$activeSheet->setCellValueByColumnAndRow($curCol, $curRow, $materialsRequestUsage->numUsed ?? "0");
							$curRow++;
						} else {
							$activeSheet->setCellValueByColumnAndRow($curCol, $curRow, "0");
							$curRow++;
						};
					}
				}
			}
		}

		// Rename sheet
		$activeSheet->setTitle('Dashboard Report');

		// Redirect output to a client's web browser (Excel5)
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="MaterialsRequestDashboardReport.xls"');
		header('Cache-Control: max-age=0');

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
		exit;
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#materialsrequest', 'Materials Request');
		$breadcrumbs[] = new Breadcrumb('/MaterialsRequest/Dashboard', 'Usage Dashboard');
		$breadcrumbs[] = new Breadcrumb('', 'Usage Graph');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'materials_request';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission(['View Dashboards', 'View System Reports']);
	}
}