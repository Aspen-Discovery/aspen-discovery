<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Dashboard.php';
require_once ROOT_DIR . '/sys/MaterialsRequestUsage.php';
require_once(ROOT_DIR . "/PHPExcel.php");

class MaterialsRequest_Dashboard extends Admin_Dashboard
{
	function launch()
	{
		global $interface;
		$userHomeLibrary = Library::getPatronHomeLibrary();
		if (is_null($userHomeLibrary)) {
			//User does not have a home library, this is likely an admin account.  Use the active library
			global $library;
			$userHomeLibrary = $library;
		}
		$locations = new Location();
		$locations->libraryId = $userHomeLibrary->libraryId;
		$locations->find();
		$locationsForLibrary = [];
		$locationsForLibrary['']['displayLabel'] = translate(['text' => 'All', 'isAdminFacing' => true, 'inAttribute' => true]);
		while ($locations->fetch()){
			$locationsForLibrary[$locations->locationId]['id'] = $locations->locationId;
			$locationsForLibrary[$locations->locationId]['displayLabel'] = $locations->displayName;
		}

		if (!empty($_REQUEST['location'])){
			$locationId = $_REQUEST['location'];
		}else{
			$locationId = '';
		}
		$interface->assign('selectedLocation', $locationId);
		$interface->assign('locationsToRestrictTo', $locationsForLibrary);

		$this->loadDates();

		$allStatuses = [];
		$statuses = new MaterialsRequestStatus();
		$statuses->libraryId = $userHomeLibrary->libraryId;
		$statuses->find();
		while($statuses->fetch()) {
			$allStatuses[$statuses->id]['id'] = $statuses->id;
			$allStatuses[$statuses->id]['label'] = $statuses->description;
			if($locationId !== '') {
				$allStatuses[$statuses->id]['usageThisMonth'] = $this->getStats($locationId, $this->thisMonth, $this->thisYear, $statuses);
				$allStatuses[$statuses->id]['usageLastMonth'] = $this->getStats($locationId, $this->lastMonth, $this->lastMonthYear, $statuses);
				$allStatuses[$statuses->id]['usageThisYear'] = $this->getStats($locationId, null, $this->thisYear, $statuses);
				$allStatuses[$statuses->id]['usageLastYear'] = $this->getStats($locationId, null, $this->lastYear, $statuses);
				$allStatuses[$statuses->id]['usageAllTime'] = $this->getStats($locationId, null, null, $statuses);
			} else {
				$allStatuses[$statuses->id]['usageThisMonth'] = $this->getStats($locationsForLibrary, $this->thisMonth, $this->thisYear, $statuses);
				$allStatuses[$statuses->id]['usageLastMonth'] = $this->getStats($locationsForLibrary, $this->lastMonth, $this->lastMonthYear, $statuses);
				$allStatuses[$statuses->id]['usageThisYear'] = $this->getStats($locationsForLibrary, null, $this->thisYear, $statuses);
				$allStatuses[$statuses->id]['usageLastYear'] = $this->getStats($locationsForLibrary, null, $this->lastYear, $statuses);
				$allStatuses[$statuses->id]['usageAllTime'] = $this->getStats($locationsForLibrary, null, null, $statuses);
			}
		}

		$interface->assign('allStats', $allStatuses);

		//Check to see if we are exporting to Excel
		if (isset($_REQUEST['exportToExcel'])){
			$this->exportToExcel();
		}

		$this->display('dashboard.tpl', 'Materials Request Dashboard');
	}

	public function getStats($location, $month, $year, $status)
	{
		if(is_array($location)) {
			$allStats = 0;
			foreach($location as $loc) {
				if($loc['displayLabel'] != "All") {
					$stats = new MaterialsRequestUsage();
					$stats->locationId = $loc['id'];
					if ($month != null) {
						$stats->month = $month;
					}
					if ($year != null) {
						$stats->year = $year;
					}
					if($status != null) {
						$stats->statusId = $status->id;
					}

					$stats->selectAdd(null);
					$stats->selectAdd('SUM(numUsed) as numUsed');

					if ($stats->find(true)){
						$allStats += $stats->numUsed != null ? intval($stats->numUsed) : "0";
					}
				}
			}
			return $allStats;
		} else {
			$stats = new MaterialsRequestUsage();
			if (!empty($location)){
				$stats->locationId = $location;
			}
			if ($month != null) {
				$stats->month = $month;
			}
			if ($year != null) {
				$stats->year = $year;
			}

			if($status != null) {
				$stats->statusId = $status->id;
			}

			$stats->selectAdd(null);
			$stats->selectAdd('SUM(numUsed) as numUsed');

			if ($stats->find(true)){
				return $stats->numUsed != null ? $stats->numUsed : "0";
			}else{
				return 0;
			}
		}
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
			$thisStatus->libraryId = $location;
			$thisStatus->find();
			$alphas = range('B', 'Z');
			$currentAlpha = 0;
			$curCol = 1;
			while ($thisStatus->fetch()) {
				$curRow = 4;
				$labelCell = $alphas[$currentAlpha] . '3';
				$activeSheet->setCellValue($labelCell, $thisStatus->description);
				foreach($periods as $period) {
					$materialsRequestUsage = new MaterialsRequestUsage();
					$materialsRequestUsage->groupBy('year, month');
					$materialsRequestUsage->selectAdd();
					$materialsRequestUsage->locationId = $location;
					$materialsRequestUsage->year = $period['year'];
					$materialsRequestUsage->month = $period['month'];
					$materialsRequestUsage->statusId = $thisStatus->id;
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
					}

				}
				$currentAlpha++;
				$curCol++;
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
				$thisStatus->libraryId = $locations->locationId;
				$thisStatus->find();

				$currentAlpha = 0;
				$alphas = range('B', 'Z');
				$curCol = 1;
				while ($thisStatus->fetch()) {
					$curRow = 4;
					$labelCell = $alphas[$currentAlpha] . '3';
					$activeSheet->setCellValue($labelCell, $thisStatus->description);
					foreach($periods as $period) {
						$materialsRequestUsage = new MaterialsRequestUsage();
						$materialsRequestUsage->groupBy('year, month');
						$materialsRequestUsage->selectAdd();
						$materialsRequestUsage->year = $period['year'];
						$materialsRequestUsage->month = $period['month'];
						$materialsRequestUsage->statusId = $thisStatus->id;
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
						}

					}
					$currentAlpha++;
					$curCol++;
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
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'materials_request';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission(['View System Reports', 'View Dashboards']);
	}
}