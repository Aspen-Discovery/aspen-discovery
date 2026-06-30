<?php

require_once ROOT_DIR . '/services/Admin/AbstractUsageGraphs.php';
require_once ROOT_DIR . '/sys/SystemLogging/AspenUsage.php';
require_once ROOT_DIR . '/sys/Summon/UserSummonUsage.php';
require_once ROOT_DIR . '/sys/Summon/SummonRecordUsage.php';
require_once ROOT_DIR . '/sys/Utils/GraphingUtils.php';

class Summon_UsageGraphs extends Admin_AbstractUsageGraphs {
	function launch(): void {
		$this->launchGraph('Summon');
	}

	function getActiveAdminSection(): string {
		return 'summon';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'View Dashboards',
			'View System Reports',
		]);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#summon', 'Summon');
		$breadcrumbs[] = new Breadcrumb('/Summon/SummonDashboard', 'Summon Usage Dashboard');
		$breadcrumbs[] = new Breadcrumb('', 'Usage Graph');
		return $breadcrumbs;
	}

	protected function getAndSetInterfaceDataSeries($stat, $instanceName, $timeframes = ['year', 'month'], $custom = false): void {
		global $interface;
		$dataSeries = [];
		$columnLabels = [];
		$groupByTimeframe = implode(',', $timeframes);

		// gets data from from user_summon_usage
		if ($stat == 'activeUsers') {
			$userSummonUsage = new UserSummonUsage();
			if (!empty($instanceName)) {
				$userSummonUsage->instance = $instanceName;
			}
			if (is_array($custom)) {
				$userSummonUsage->buildCustomPeriodQuery($custom);
			} else {
				$userSummonUsage->groupBy($groupByTimeframe);
				foreach ($timeframes as $timeframe) {
					$userSummonUsage->selectAdd($timeframe);
				}
				$userSummonUsage->orderBy($groupByTimeframe);
			}

			$dataSeries['Active Users'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
			$userSummonUsage->selectAdd('COUNT(DISTINCT userId) as activeUsers');

			// Collects results
			$userSummonUsage->find();
			while($userSummonUsage->fetch()) {
				$curPeriod = $custom ? $userSummonUsage->getCustomPeriod() : $userSummonUsage->getCurPeriod($timeframes);
				$columnLabels[] = $curPeriod;
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['Active Users']['data'][$curPeriod] = $userSummonUsage->activeUsers;
			}
		}
			
		// gets data from from summon_usage
		if (
			$stat == 'numRecordsViewed' ||
			$stat == 'numRecordsClicked' ||
			$stat == 'totalClicks'
		){
			$summonRecordUsage = new SummonRecordUsage();
			$summonRecordUsage->groupBy($groupByTimeframe);
			if (!empty($instanceName)) {
				$summonRecordUsage->instance = $instanceName;
			}
			if (is_array($custom)) {
				$summonRecordUsage->buildCustomPeriodQuery($custom);
			} else {
				$summonRecordUsage->groupBy($groupByTimeframe);
				foreach ($timeframes as $timeframe) {
					$summonRecordUsage->selectAdd($timeframe);
				}
				$summonRecordUsage->orderBy($groupByTimeframe);
			}
		
			if ($stat == 'numRecordsViewed') {
				$dataSeries['Number of Records Viewed'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
				$summonRecordUsage ->selectAdd('SUM(IF(timesViewedInSearch>0,1,0)) as numRecordsViewed');
			}
			if ($stat == 'numRecordsClicked') {
				$dataSeries['Number of Records Clicked'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
				$summonRecordUsage ->selectAdd('SUM(IF(timesUsed>0,1,0)) as numRecordsUsed');
			}
			if ($stat == 'totalClicks') {
				$dataSeries['Total Clicks'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
				$summonRecordUsage ->selectAdd('SUM(timesUsed) as numClicks');
			}
			// Collect results
			$summonRecordUsage->find();
			while ($summonRecordUsage->fetch()) {
				$curPeriod = $custom ? $summonRecordUsage->getCustomPeriod() : $summonRecordUsage->getCurPeriod($timeframes);
				$columnLabels[] = $curPeriod;
				if ($stat == 'numRecordsViewed') {
					/** @noinspection PhpUndefinedFieldInspection */
					$dataSeries['Number of Records Viewed']['data'][$curPeriod] = $summonRecordUsage->numRecordsViewed;
				}
				if ($stat == 'numRecordsClicked') {
					/** @noinspection PhpUndefinedFieldInspection */
					$dataSeries['Number of Records Clicked']['data'][$curPeriod] = $summonRecordUsage->numRecordsUsed;
				}
				if ($stat == 'totalClicks') {
					/** @noinspection PhpUndefinedFieldInspection */
					$dataSeries['Total Clicks']['data'][$curPeriod] = $summonRecordUsage->numClicks;
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
			case 'numRecordsViewed':
				$title .= ' - Number of Records Viewed';
			break;
			case 'numRecordsClicked':
				$title .= ' - Number of Records Clicked';
			break;
			case 'totalClicks':
				$title .= ' - Total Clicks';
			break;
		}
		$interface->assign('graphTitle', $title);
	}
}