<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Axis360/UserAxis360Usage.php';
require_once ROOT_DIR . '/sys/Axis360/Axis360RecordUsage.php';
require_once ROOT_DIR . '/sys/Axis360/Axis360Stats.php';

class Axis360_Graphs extends Admin_Admin
{
	function launch()
	{
		global $interface;
		$title = 'Axis 360 Usage Graph';
		if (!empty($_REQUEST['instance'])){
			$instanceName = $_REQUEST['instance'];
		}else{
			$instanceName = '';
		}

		$dataSeries = [];
		$columnLabels = [];

		require_once ROOT_DIR . '/sys/Utils/GraphingUtils.php';
		$dataSeries['Total Usage'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
		$dataSeries['Unique Users'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
		$dataSeries['Records Used'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
		$dataSeries['Total Holds'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
		$dataSeries['Total Checkouts'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
		$dataSeries['Total Renewals'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
		$dataSeries['Total Early Returns'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
		$dataSeries['Total Holds Cancelled'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
		$dataSeries['Total Holds Frozen'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
		$dataSeries['Total Holds Thawed'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
		$dataSeries['Total API Errors'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
		$dataSeries['Total Connection Failures'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
		$userUsage = new UserAxis360Usage();
		$userUsage->groupBy('year, month');
		if (!empty($instanceName)){
			$userUsage->instance = $instanceName;
		}
		$userUsage->selectAdd();
		$userUsage->selectAdd('year');
		$userUsage->selectAdd('month');
		$userUsage->selectAdd('COUNT(*) as numUsers');
		$userUsage->selectAdd('SUM(usageCount) as sumUsage');
		$userUsage->orderBy('year, month');
		$userUsage->find();
		while ($userUsage->fetch()){
			$curPeriod = "{$userUsage->month}-{$userUsage->year}";
			$columnLabels[] = $curPeriod;
			/** @noinspection PhpUndefinedFieldInspection */
			$dataSeries['Total Usage']['data'][$curPeriod] = $userUsage->sumUsage;
			/** @noinspection PhpUndefinedFieldInspection */
			$dataSeries['Unique Users']['data'][$curPeriod] = $userUsage->numUsers;

			//Make sure we have default values for all the other series
			$dataSeries['Records Used']['data'][$curPeriod] = 0;
			$dataSeries['Total Holds']['data'][$curPeriod] = 0;
			$dataSeries['Total Checkouts']['data'][$curPeriod] = 0;
			$dataSeries['Total Early Returns']['data'][$curPeriod] = 0;
			$dataSeries['Total Renewals']['data'][$curPeriod] = 0;
			$dataSeries['Total Holds Cancelled']['data'][$curPeriod] = 0;
			$dataSeries['Total Holds Frozen']['data'][$curPeriod] = 0;
			$dataSeries['Total Holds Thawed']['data'][$curPeriod] = 0;
			$dataSeries['Total API Errors']['data'][$curPeriod] = 0;
			$dataSeries['Total Connection Failures']['data'][$curPeriod] = 0;
		}

		//Load Record Stats
		$stats = new Axis360Stats();
		$stats->groupBy('year, month');
		if (!empty($instanceName)){
			$stats->instance = $instanceName;
		}
		$stats->selectAdd();
		$stats->selectAdd('year');
		$stats->selectAdd('month');
		$stats->selectAdd('COUNT(id) as recordsUsed');
		$stats->selectAdd('SUM(numHoldsPlaced) as totalHolds');
		$stats->selectAdd('SUM(numCheckouts) as totalCheckouts');
		$stats->selectAdd('SUM(numEarlyReturns) as numEarlyReturns');
		$stats->selectAdd('SUM(numRenewals) as numRenewals');
		$stats->selectAdd('SUM(numHoldsCancelled) as numHoldsCancelled');
		$stats->selectAdd('SUM(numHoldsFrozen) as numHoldsFrozen');
		$stats->selectAdd('SUM(numHoldsThawed) as numHoldsThawed');
		$stats->selectAdd('SUM(numApiErrors) as numApiErrors');
		$stats->selectAdd('SUM(numConnectionFailures) as numConnectionFailures');
		$stats->orderBy('year, month');
		$stats->find();
		while ($stats->fetch()){
			$curPeriod = "{$stats->month}-{$stats->year}";
			/** @noinspection PhpUndefinedFieldInspection */
			$dataSeries['Records Used']['data'][$curPeriod] = $stats->recordsUsed;
			/** @noinspection PhpUndefinedFieldInspection */
			$dataSeries['Total Holds']['data'][$curPeriod] = $stats->totalHolds;
			/** @noinspection PhpUndefinedFieldInspection */
			$dataSeries['Total Checkouts']['data'][$curPeriod] = $stats->totalCheckouts;
			$dataSeries['Total Early Returns']['data'][$curPeriod] = $stats->numEarlyReturns;
			$dataSeries['Total Renewals']['data'][$curPeriod] = $stats->numRenewals;
			$dataSeries['Total Holds Cancelled']['data'][$curPeriod] = $stats->numHoldsCancelled;
			$dataSeries['Total Holds Frozen']['data'][$curPeriod] = $stats->numHoldsFrozen;
			$dataSeries['Total Holds Thawed']['data'][$curPeriod] = $stats->numHoldsThawed;
			$dataSeries['Total API Errors']['data'][$curPeriod] = $stats->numApiErrors;
			$dataSeries['Total Connection Failures']['data'][$curPeriod] = $stats->numConnectionFailures;
		}

		$interface->assign('columnLabels', $columnLabels);
		$interface->assign('dataSeries', $dataSeries);

		$interface->assign('graphTitle', $title);
		$this->display('../Admin/usage-graph.tpl', $title);
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#axis360', 'Axis 360');
		$breadcrumbs[] = new Breadcrumb('/Axis360/Dashboard', 'Usage Dashboard');
		$breadcrumbs[] = new Breadcrumb('', 'Usage Graph');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'axis360';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission(['View System Reports', 'View Dashboards']);
	}
}