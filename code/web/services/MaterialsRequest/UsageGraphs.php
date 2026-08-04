<?php

require_once ROOT_DIR . '/services/Admin/AbstractUsageGraphs.php';
require_once ROOT_DIR . '/sys/SystemLogging/AspenUsage.php';
require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestUsage.php';
require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestStatus.php';
require_once ROOT_DIR . '/sys/Utils/GraphingUtils.php';

class MaterialsRequest_UsageGraphs extends Admin_AbstractUsageGraphs {
	function launch(): void {
		$this->launchGraph('MaterialsRequest');
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

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#materialsrequest', 'Materials Request');
		$breadcrumbs[] = new Breadcrumb('/MaterialsRequest/Dashboard', 'Usage Dashboard');
		$breadcrumbs[] = new Breadcrumb('', 'Usage Graph');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'materials_request';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'View Dashboards',
			'View System Reports',
		]);
	}

	protected function assignGraphSpecificTitle($stat): void {} //unnecessary as achieved programmatically in getAndSetInterfaceDataSeries

	private function getMaterialsRequestStatusDescription($status, $libraryId) {
		$thisStatus = new MaterialsRequestStatus();
		$thisStatus->id = $status;
		$thisStatus->libraryId = $libraryId;
		$thisStatus->find();
		return $thisStatus->fetch()->description;
	}

	protected function getAndSetInterfaceDataSeries($stat, $instanceName, $timeframes = ['year', 'month'], $custom = false): void {
		global $interface;

		$statusId = $_REQUEST['stat'];
		$interface->assign('curStatus', $statusId);
		$dataSeries = [];
		$groupByTimeframe = implode(',', $timeframes);

		$userHomeLibrary = Library::getPatronHomeLibrary();
		if (is_null($userHomeLibrary)) {
			//User does not have a home library, this is likely an admin account.  Use the active library
			global $library;
			$userHomeLibrary = $library;
		}
		$libraryId = $userHomeLibrary->libraryId;
		$statusDescription = $this->getMaterialsRequestStatusDescription($statusId, $libraryId);

		$title = 'Materials Request Usage Graph - ' . $statusDescription;
		$materialsRequestUsage = new MaterialsRequestUsage();
		$materialsRequestUsage->selectAdd();
		$materialsRequestUsage->selectAdd('SUM(numUsed) as numUsed');
		if (!empty($instanceName)) {
			$materialsRequestUsage->instance = $instanceName;
		}
	
		if (is_array($custom)) {
			$materialsRequestUsage->buildCustomPeriodQuery($custom);
		} else {
			$materialsRequestUsage->groupBy($groupByTimeframe);
			foreach ($timeframes as $timeframe) {
				$materialsRequestUsage->selectAdd($timeframe);
			}
			$materialsRequestUsage->orderBy($groupByTimeframe);
		}

		$materialsRequestUsage->statusId = $statusId;
		$materialsRequestUsage->libraryId = $libraryId;

		$dataSeries[$statusDescription] = GraphingUtils::getDataSeriesArray(count($dataSeries));

		//Collect results
		$materialsRequestUsage->find();

		$columnLabels = [];
		while ($materialsRequestUsage->fetch()) {
			$curPeriod = $custom ? $materialsRequestUsage->getCustomPeriod() : $materialsRequestUsage->getCurPeriod($timeframes);
			$columnLabels[] = $curPeriod;
			$dataSeries[$statusDescription]['data'][$curPeriod] = $materialsRequestUsage->numUsed;
		}
		
		$interface->assign('columnLabels', $columnLabels);
		$interface->assign('dataSeries', $dataSeries);
		$interface->assign('graphTitle', $title);
		$interface->assign('translateDataSeries', true);
		$interface->assign('translateColumnLabels', false);
	}
}
