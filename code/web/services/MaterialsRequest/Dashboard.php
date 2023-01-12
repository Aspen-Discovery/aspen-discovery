<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Dashboard.php';
require_once ROOT_DIR . '/sys/MaterialsRequestUsage.php';

class MaterialsRequest_Dashboard extends Admin_Dashboard {
	function launch() {
		global $interface;
		$userHomeLibrary = Library::getPatronHomeLibrary();
		if (is_null($userHomeLibrary)) {
			//User does not have a home library, this is likely an admin account.  Use the active library
			global $library;
			$userHomeLibrary = $library;
		}
		$libraryId = $userHomeLibrary->libraryId;
		$interface->assign('selectedLocation', $libraryId);

		$this->loadDates();

		$allStatuses = [];
		$statuses = new MaterialsRequestStatus();
		$statuses->libraryId = $libraryId;
		$statuses->find();
		while ($statuses->fetch()) {
			$allStatuses[$statuses->id]['id'] = $statuses->id;
			$allStatuses[$statuses->id]['label'] = $statuses->description;
				$allStatuses[$statuses->id]['usageThisMonth'] = $this->getStats($libraryId, $this->thisMonth, $this->thisYear, $statuses);
				$allStatuses[$statuses->id]['usageLastMonth'] = $this->getStats($libraryId, $this->lastMonth, $this->lastMonthYear, $statuses);
				$allStatuses[$statuses->id]['usageThisYear'] = $this->getStats($libraryId, null, $this->thisYear, $statuses);
				$allStatuses[$statuses->id]['usageLastYear'] = $this->getStats($libraryId, null, $this->lastYear, $statuses);
				$allStatuses[$statuses->id]['usageAllTime'] = $this->getStats($libraryId, null, null, $statuses);

		}

		$interface->assign('allStats', $allStatuses);

		//Check to see if we are exporting to Excel
		if (isset($_REQUEST['exportToExcel'])) {
			$this->exportToExcel();
		}

		$this->display('dashboard.tpl', 'Materials Request Dashboard');
	}

	public function getStats($location, $month, $year, $status) {
		if (is_array($location)) {
			$allStats = 0;
			foreach ($location as $loc) {
				if ($loc['displayLabel'] != "All") {
					$stats = new MaterialsRequestUsage();
					$stats->libraryId = $loc['id'];
					if ($month != null) {
						$stats->month = $month;
					}
					if ($year != null) {
						$stats->year = $year;
					}
					if ($status != null) {
						$stats->statusId = $status->id;
					}

					$stats->selectAdd(null);
					$stats->selectAdd('SUM(numUsed) as numUsed');

					if ($stats->find(true)) {
						$allStats += $stats->numUsed != null ? intval($stats->numUsed) : "0";
					}
				}
			}
			return $allStats;
		} else {
			$stats = new MaterialsRequestUsage();
			if (!empty($location)) {
				$stats->libraryId = $location;
			}
			if ($month != null) {
				$stats->month = $month;
			}
			if ($year != null) {
				$stats->year = $year;
			}

			if ($status != null) {
				$stats->statusId = $status->id;
			}

			$stats->selectAdd(null);
			$stats->selectAdd('SUM(numUsed) as numUsed');

			if ($stats->find(true)) {
				return $stats->numUsed != null ? $stats->numUsed : "0";
			} else {
				return 0;
			}
		}
	}

	public function getAllPeriods() {
		$usage = new MaterialsRequestUsage();
		$usage->selectAdd(null);
		$usage->selectAdd('DISTINCT year, month');
		$usage->find();

		$stats = [];
		while ($usage->fetch()) {
			$stats[$usage->month . '-' . $usage->year]['year'] = $usage->year;
			$stats[$usage->month . '-' . $usage->year]['month'] = $usage->month;
		}
		return $stats;
	}

	function exportToExcel() {
		$periods = $this->getAllPeriods();

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment;filename="MaterialsRequestDashboardReport.csv"');
		header('Cache-Control: max-age=0');
		$fp = fopen('php://output', 'w');

		$header[] = 'Date';

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
			$thisStatus->libraryId = $locations->libraryId;
			$thisStatus->find();

			while ($thisStatus->fetch()) {
				$header[] = $thisStatus->description;
			}
			fputcsv($fp, $header);

			foreach ($periods as $period) {
				$materialsRequestUsage = new MaterialsRequestUsage();
				$materialsRequestUsage->year = $period['year'];
				$materialsRequestUsage->month = $period['month'];
				$materialsRequestUsage->statusId = $thisStatus->id;
				$materialsRequestUsage->find();

				$row = [];
				$date = "{$materialsRequestUsage->month}-{$materialsRequestUsage->year}";
				$row[] = $date;

				$thisStatus = new MaterialsRequestStatus();
				$thisStatus->libraryId = $locations->libraryId;
				$thisStatus->find();

				while ($thisStatus->fetch()){
					$materialsRequestUsage = new MaterialsRequestUsage();
					$materialsRequestUsage->year = $period['year'];
					$materialsRequestUsage->month = $period['month'];
					$materialsRequestUsage->statusId = $thisStatus->id;
					if ($materialsRequestUsage->find(true)){
						$row[] = $materialsRequestUsage->numUsed ?? 0;
					}else{
						$row[] = 0;
					}
				}
				fputcsv($fp, $row);
			}
		}
		exit;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#materialsrequest', 'Materials Request');
		$breadcrumbs[] = new Breadcrumb('/MaterialsRequest/Dashboard', 'Usage Dashboard');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'materials_request';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'View System Reports',
			'View Dashboards',
		]);
	}
}