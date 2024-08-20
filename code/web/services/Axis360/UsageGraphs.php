<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Axis360/UserAxis360Usage.php';
require_once ROOT_DIR . '/sys/Axis360/Axis360RecordUsage.php';
require_once ROOT_DIR . '/sys/Axis360/Axis360Stats.php';
require_once ROOT_DIR . '/sys/Utils/GraphingUtils.php';

class Axis360_UsageGraphs extends Admin_Admin {
	function launch() {
		global $interface;
		$stat = $_REQUEST['stat'];
		$title = 'Boundless Usage Graph';
		if (!empty($_REQUEST['instance'])) {
			$instanceName = $_REQUEST['instance'];
		} else {
			$instanceName = '';
		}

		$interface->assign('graphTitle', $title);
		$this->assignGraphSpecificTitle($stat);
		$this->getAndSetInterfaceDataSeries($stat, $instanceName);
		$interface->assign('stat', $stat);
		$interface->assign('propName', 'exportToCSV');
		$interface->assign('showCSVExportButton', true);
		$interface->assign('section', 'Axis360');

		$this->display('../Admin/usage-graph.tpl', $title);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#axis360', 'Boundless');
		$breadcrumbs[] = new Breadcrumb('/Axis360/Dashboard', 'Usage Dashboard');
		$breadcrumbs[] = new Breadcrumb('', 'Usage Graph');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'boundless';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'View System Reports',
			'View Dashboards',
		]);
	}

	public function buildCSV() {
		global $interface;

		$stat = $_REQUEST['stat'];
		if (!empty($_REQUEST['instance'])) {
			$instanceName = $_REQUEST['instance'];
		} else {
			$instanceName = '';
		}
		$this->getAndSetInterfaceDataSeries($stat, $instanceName);
		$dataSeries = $interface->getVariable('dataSeries');

		$filename = "BoundlessUsageData_{$stat}.csv";
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
		header('Content-Type: text/csv; charset=utf-8');
		header("Content-Disposition: attachment;filename={$filename}");
		$fp = fopen('php://output', 'w');
		$graphTitles = array_keys($dataSeries);
		$numGraphTitles = count($dataSeries);

		// builds the header for each section of the table in the CSV - column headers: Dates, and the title of the graph
		for($i = 0; $i < $numGraphTitles; $i++) {
			$dataSerie = $dataSeries[$graphTitles[$i]];
			$numRows = count($dataSerie['data']);
			$dates = array_keys($dataSerie['data']);
			$header = ['Dates', $graphTitles[$i]];
			fputcsv($fp, $header);

				// builds each subsequent data row - aka the column value
				for($j = 0; $j < $numRows; $j++) {
					$date = $dates[$j];
					$value = $dataSerie['data'][$date];
					$row = [$date, $value];
					fputcsv($fp, $row);
				}
		}
		exit();
	}

	private function getAndSetInterfaceDataSeries($stat, $instanceName) {
		global $interface;
		$dataSeries = [];
		$columnLabels = [];

		// Get data from user_axis360_usage
		if ($stat == 'activeUsers' || $stat == 'general') {
			$userUsage = new UserAxis360Usage();
			$userUsage->groupBy('year, month');
			if (!empty($instanceName)) {
				$userUsage->instance = $instanceName;
			}
			$userUsage->selectAdd();
			$userUsage->selectAdd('year');
			$userUsage->selectAdd('month');

			if ($stat == 'activeUsers' || $stat == 'general') {
				$dataSeries['Unique Users'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
				$userUsage->selectAdd('COUNT(*) as numUsers');
			}
			if ($stat == 'general') { 
				$dataSeries['Total Usage'] = GraphingUtils::getDataSeriesArray(count($dataSeries)); // does not appear on the dashboard, but was present on the earlier iteration fo the general graph
				$userUsage->selectAdd('SUM(usageCount) as sumUsage');
			}
			$userUsage->orderBy('year, month');
			$userUsage->find();
			while ($userUsage->fetch()) {
				$curPeriod = "{$userUsage->month}-{$userUsage->year}";
				$columnLabels[] = $curPeriod;
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['Total Usage']['data'][$curPeriod] = $userUsage->sumUsage;
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['Unique Users']['data'][$curPeriod] = $userUsage->numUsers;
			}
		}

		// Get data from axis360_record_usage
		if ($stat == 'recordsWithUsage' ||
			$stat == 'loans' ||
			$stat == 'holds' ||
			$stat == 'general') {
			$recordUsage = new Axis360RecordUsage();
			$recordUsage->groupBy('year, month');
			if (!empty($instanceName)) {
				$recordUsage->instance = $instanceName;
			}
			$recordUsage->selectAdd();
			$recordUsage->selectAdd('year');
			$recordUsage->selectAdd('month');
			$recordUsage->orderBy('year, month');

			if ($stat == 'recordsWithUsage' || $stat == 'general') {
				$dataSeries['Records With Usage'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
				$recordUsage->selectAdd('COUNT(id) as recordsWithUsage');
			}
			if ($stat == 'loans' || $stat == 'general') {
				$dataSeries['Loans'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
				$recordUsage->selectAdd('SUM(timesCheckedOut) as totalCheckouts');
			}
			if ($stat == 'holds' || $stat == 'general') {
				$dataSeries['Holds'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
				$recordUsage->selectAdd('SUM(timesHeld) as totalHolds');
			}

			$recordUsage->orderBy('year, month');
			$recordUsage->find();
			while ($recordUsage->fetch()) {
				$curPeriod = "{$recordUsage->month}-{$recordUsage->year}";
				if ( $stat != 'general' || !in_array("{$recordUsage->month}-{$recordUsage->year}", $columnLabels)) { // prevents the multiple addition of a curPeriod
					$columnLabels[] = $curPeriod;
				}
				if ($stat == 'recordsWithUsage' || $stat == 'general') {
					/** @noinspection PhpUndefinedFieldInspection */
					$dataSeries['Records With Usage']['data'][$curPeriod] = $recordUsage->recordsWithUsage;
				}
				if ($stat == 'loans' || $stat == 'general') {
					/** @noinspection PhpUndefinedFieldInspection */
					$dataSeries['Loans']['data'][$curPeriod] = $recordUsage->totalCheckouts;
				}
				if ($stat == 'holds' || $stat == 'general') {
					/** @noinspection PhpUndefinedFieldInspection */
					$dataSeries['Holds']['data'][$curPeriod] = $recordUsage->totalHolds;
				}
			}
		}

		// Get data from axis360_stats
		if (
			$stat == 'renewals' ||
			$stat == 'earlyReturns' ||
			$stat == 'holdsCancelled' ||
			$stat == 'holdsFrozen' ||
			$stat == 'holdsThawed' ||
			$stat == 'apiErrors' ||
			$stat == 'connectionFailures' ||
			$stat == 'general') {

			$stats = new Axis360Stats();
			$stats->groupBy('year, month');
			if (!empty($instanceName)) {
				$stats->instance = $instanceName;
			}
			$stats->selectAdd();
			$stats->selectAdd('year');
			$stats->selectAdd('month');
			$stats->orderBy('year, month');

			if ($stat == 'renewals' || $stat == 'general') {
				$dataSeries['Total Renewals'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
				$stats->selectAdd('SUM(numEarlyReturns) as numEarlyReturns');
			}
			if ($stat == 'earlyReturns' || $stat == 'general') {
				$dataSeries['Total Early Returns'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
				$stats->selectAdd('SUM(numRenewals) as numRenewals');
			}
			if ($stat == 'holdsCancelled' || $stat == 'general') {
				$dataSeries['Total Holds Cancelled'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
				$stats->selectAdd('SUM(numHoldsCancelled) as numHoldsCancelled');
			}
			if ($stat == 'holdsFrozen' || $stat == 'general') {
				$dataSeries['Total Holds Frozen'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
				$stats->selectAdd('SUM(numHoldsFrozen) as numHoldsFrozen');
			}
			if ($stat == 'holdsThawed' || $stat == 'general') {
				$dataSeries['Total Holds Thawed'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
				$stats->selectAdd('SUM(numHoldsThawed) as numHoldsThawed');
			}
			if ($stat == 'apiErrors' || $stat == 'general') {
				$dataSeries['Total API Errors'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
				$stats->selectAdd('SUM(numApiErrors) as numApiErrors');
			}
			if ($stat == 'connectionFailures' || $stat == 'general') {
				$dataSeries['Total Connection Failures'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
				$stats->selectAdd('SUM(numConnectionFailures) as numConnectionFailures');
			}
			$stats->find();
			while ($stats->fetch()) {
				$curPeriod = "{$stats->month}-{$stats->year}";
				if ( $stat != 'general' || !in_array("{$stats->month}-{$stats->year}", $columnLabels)) {  // prevents the multiple addition of a curPeriod
					$columnLabels[] = $curPeriod;
				}
				if ($stat == 'renewals' || $stat == 'general') {
					/** @noinspection PhpUndefinedFieldInspection */
					$dataSeries['Total Early Returns']['data'][$curPeriod] = $stats->numEarlyReturns;
				}
				if ($stat == 'earlyReturns' || $stat == 'general') {
					/** @noinspection PhpUndefinedFieldInspection */
					$dataSeries['Total Renewals']['data'][$curPeriod] = $stats->numRenewals;
				}
				if ($stat == 'holdsCancelled' || $stat == 'general') {
					/** @noinspection PhpUndefinedFieldInspection */
					$dataSeries['Total Holds Cancelled']['data'][$curPeriod] = $stats->numHoldsCancelled;
				}
				if ($stat == 'holdsFrozen' || $stat == 'general') {
					/** @noinspection PhpUndefinedFieldInspection */
					$dataSeries['Total Holds Frozen']['data'][$curPeriod] = $stats->numHoldsFrozen;
				}
				if ($stat == 'holdsThawed' || $stat == 'general') {
					/** @noinspection PhpUndefinedFieldInspection */
					$dataSeries['Total Holds Thawed']['data'][$curPeriod] = $stats->numHoldsThawed;
				}
				if ($stat == 'apiErrors' || $stat == 'general') {
					/** @noinspection PhpUndefinedFieldInspection */
					$dataSeries['Total API Errors']['data'][$curPeriod] = $stats->numApiErrors;
				}
				if ($stat == 'connectionFailures' || $stat == 'general') {
					/** @noinspection PhpUndefinedFieldInspection */
					$dataSeries['Total Connection Failures']['data'][$curPeriod] = $stats->numConnectionFailures;
				}
			}
		}
		$interface->assign('columnLabels', $columnLabels);
		$interface->assign('dataSeries', $dataSeries);
		$interface->assign('translateDataSeries', true);
		$interface->assign('translateColumnLabels', false);
	}

	private function assignGraphSpecificTitle($stat) {
		global $interface;
		$title = $interface->getVariable('graphTitle');
		switch ($stat) {
			case 'activeUsers':
				$title .= ' - Active Users';
				break;
			case 'recordsWithUsage':
				$title .= ' - Records With Usage';
				break;
			case 'loans':
				$title .= ' - Loans';
				break;
			case 'holds':
				$title .= ' - Holds';
				break;
			case 'renewals':
				$title .= ' - Renewals';
				break;
			case 'earlyReturns':
				$title .= ' - Early Returns';
				break;
			case 'holdsCancelled':
				$title .= ' - Holds Cancelled';
				break;
			case 'holdsFrozen':
				$title .= ' - Holds Frozen';
				break;
			case 'holdsThawed':
				$title .= ' - Holds Thawed';
				break;
			case 'apiErrors':
				$title .= ' - API Errors';
				break;
			case 'connectionFailures':
				$title .= ' - Connection Failures';
				break;
			case 'general':
				$title .= ' - General';
				break;
		}
		$interface->assign('graphTitle', $title);
	}
}