<?php

require_once ROOT_DIR . '/services/Admin/AbstractUsageGraphs.php';
require_once ROOT_DIR . '/sys/BorrowBox/UserBorrowBoxUsage.php';
require_once ROOT_DIR . '/sys/BorrowBox/BorrowBoxRecordUsage.php';
require_once ROOT_DIR . '/sys/BorrowBox/BorrowBoxStats.php';
require_once ROOT_DIR . '/sys/Utils/GraphingUtils.php';

class BorrowBox_UsageGraphs extends Admin_AbstractUsageGraphs {
	function launch(): void {
		$this->launchGraph('BorrowBox');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#borrowbox', 'BorrowBox');
		$breadcrumbs[] = new Breadcrumb('/BorrowBox/Dashboard', 'Usage Dashboard');
		$breadcrumbs[] = new Breadcrumb('', 'Usage Graph');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'borrowbox';
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

		$showActiveUsers = $stat == 'activeUsers' || $stat == 'general';
		$showRecordsWithUsage = $stat == 'recordsWithUsage' || $stat == 'general';
		$showLoans = $stat == 'loans' || $stat == 'general';
		$showHolds = $stat == 'holds' || $stat == 'general';
		if ($showActiveUsers) {
			$userUsage = new UserBorrowBoxUsage();
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

			$dataSeries['Unique Users'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
			$userUsage->selectAdd('COUNT(*) as numUsers');
			if ($stat == 'general') {
				$dataSeries['Total Usage'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
				$userUsage->selectAdd('SUM(usageCount) as sumUsage');
			}
			$userUsage->find();
			while ($userUsage->fetch()) {
				$curPeriod = $custom ? $userUsage->getCustomPeriod() : $userUsage->getCurPeriod($timeframes);
				$columnLabels[] = $curPeriod;
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['Unique Users']['data'][$curPeriod] = $userUsage->numUsers;
				if ($stat == 'general') {
					/** @noinspection PhpUndefinedFieldInspection */
					$dataSeries['Total Usage']['data'][$curPeriod] = $userUsage->sumUsage;
				}
			}
		}

		$loadRecordUsage = in_array($stat, ['recordsWithUsage', 'loans', 'holds', 'general']);
		if ($loadRecordUsage) {
			$recordUsage = new BorrowBoxRecordUsage();
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

			if ($showRecordsWithUsage) {
				$dataSeries['Records With Usage'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
				$recordUsage->selectAdd('COUNT(id) as recordsWithUsage');
			}
			if ($showLoans) {
				$dataSeries['Loans'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
				$recordUsage->selectAdd('SUM(timesCheckedOut) as totalCheckouts');
			}
			if ($showHolds) {
				$dataSeries['Holds'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
				$recordUsage->selectAdd('SUM(timesHeld) as totalHolds');
			}

			$recordUsage->find();
			while ($recordUsage->fetch()) {
				$curPeriod = $custom ? $recordUsage->getCustomPeriod() : $recordUsage->getCurPeriod($timeframes);
				$isNewPeriod = $stat != 'general' || !in_array($curPeriod, $columnLabels);
				if ($isNewPeriod) {
					$columnLabels[] = $curPeriod;
				}
				if ($showRecordsWithUsage) {
					/** @noinspection PhpUndefinedFieldInspection */
					$dataSeries['Records With Usage']['data'][$curPeriod] = $recordUsage->recordsWithUsage;
				}
				if ($showLoans) {
					/** @noinspection PhpUndefinedFieldInspection */
					$dataSeries['Loans']['data'][$curPeriod] = $recordUsage->totalCheckouts;
				}
				if ($showHolds) {
					/** @noinspection PhpUndefinedFieldInspection */
					$dataSeries['Holds']['data'][$curPeriod] = $recordUsage->totalHolds;
				}
			}
		}

		$statsFields = [
			'failedLoans' => ['numFailedCheckouts', 'Total Failed Loans'],
			'failedHolds' => ['numFailedHolds', 'Total Failed Holds'],
			'renewals' => ['numRenewals', 'Total Renewals'],
			'earlyReturns' => ['numEarlyReturns', 'Total Early Returns'],
			'holdsCancelled' => ['numHoldsCancelled', 'Total Holds Cancelled'],
			'apiErrors' => ['numApiErrors', 'Total API Errors'],
			'connectionFailures' => ['numConnectionFailures', 'Total Connection Failures'],
		];
		$statsToLoad = $stat == 'general' ? array_keys($statsFields) : (array_key_exists($stat, $statsFields) ? [$stat] : []);
		if (!empty($statsToLoad)) {
			$stats = new BorrowBoxStats();
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

			foreach ($statsToLoad as $statToLoad) {
				[$field, $label] = $statsFields[$statToLoad];
				$dataSeries[$label] = GraphingUtils::getDataSeriesArray(count($dataSeries));
				$stats->selectAdd("SUM($field) as $field");
			}
			$stats->find();
			while ($stats->fetch()) {
				$curPeriod = $custom ? $stats->getCustomPeriod() : $stats->getCurPeriod($timeframes);
				$isNewPeriod = $stat != 'general' || !in_array($curPeriod, $columnLabels);
				if ($isNewPeriod) {
					$columnLabels[] = $curPeriod;
				}
				foreach ($statsToLoad as $statToLoad) {
					[$field, $label] = $statsFields[$statToLoad];
					$dataSeries[$label]['data'][$curPeriod] = $stats->$field;
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
		$titles = [
			'activeUsers' => 'Active Users',
			'recordsWithUsage' => 'Records With Usage',
			'loans' => 'Loans',
			'failedLoans' => 'Failed Loans',
			'holds' => 'Holds',
			'failedHolds' => 'Failed Holds',
			'renewals' => 'Renewals',
			'earlyReturns' => 'Early Returns',
			'holdsCancelled' => 'Holds Cancelled',
			'apiErrors' => 'API Errors',
			'connectionFailures' => 'Connection Failures',
			'general' => 'General',
		];
		if (array_key_exists($stat, $titles)) {
			$title .= ' - ' . $titles[$stat];
		}
		$interface->assign('graphTitle', $title);
	}
}
