<?php

require_once ROOT_DIR . '/services/Admin/AbstractUsageGraphs.php';
require_once ROOT_DIR . '/sys/Axis360/UserAxis360Usage.php';
require_once ROOT_DIR . '/sys/Axis360/Axis360RecordUsage.php';
require_once ROOT_DIR . '/sys/Axis360/Axis360Stats.php';
require_once ROOT_DIR . '/sys/Utils/GraphingUtils.php';

class Axis360_UsageGraphs extends Admin_AbstractUsageGraphs {
	function launch(): void {
		$this->launchGraph('Boundless');
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

	protected function getAndSetInterfaceDataSeries($stat, $instanceName, $timeframes = ['year', 'month'], $custom = false): void {
		global $interface;
		$dataSeries = [];
		$columnLabels = [];
		$groupByTimeframe = implode(',', $timeframes);

		// Get data from user_axis360_usage
		if ($stat == 'activeUsers' || $stat == 'general') {
			$userUsage = new UserAxis360Usage();
			$userUsage->selectAdd();
			if (!empty($instanceName)) {
				$userUsage->instance = $instanceName;
			}
		
			if (is_array($custom)) {
				$userUsage->buildCustomPeriodQuery($custom);
			} else {
				$userUsage->groupBy($groupByTimeframe);
				foreach ($timeframes as $timeframe) {
					$userUsage->selectAdd($timeframe);
				}
				$userUsage->orderBy($groupByTimeframe);
			}

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
				$curPeriod = $custom ? $userUsage->getCustomPeriod() : $userUsage->getCurPeriod($timeframes);
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
			$recordUsage->selectAdd();
			if (!empty($instanceName)) {
				$recordUsage->instance = $instanceName;
			}
		
			if (is_array($custom)) {
				$recordUsage->buildCustomPeriodQuery($custom);
			} else {
				$recordUsage->groupBy($groupByTimeframe);
				foreach ($timeframes as $timeframe) {
					$recordUsage->selectAdd($timeframe);
				}
				$recordUsage->orderBy($groupByTimeframe);
			}

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

			$recordUsage->find();
			while ($recordUsage->fetch()) {
				$curPeriod = $custom ? $recordUsage->getCustomPeriod() : $recordUsage->getCurPeriod($timeframes);
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
			$stats->selectAdd();
			if (!empty($instanceName)) {
				$stats->instance = $instanceName;
			}
		
			if (is_array($custom)) {
				$stats->buildCustomPeriodQuery($custom);
			} else {
				$stats->groupBy($groupByTimeframe);
				foreach ($timeframes as $timeframe) {
					$stats->selectAdd($timeframe);
				}
				$stats->orderBy($groupByTimeframe);
			}

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
				$curPeriod = $custom ? $stats->getCustomPeriod() : $stats->getCurPeriod($timeframes);
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

	protected function assignGraphSpecificTitle($stat): void {
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