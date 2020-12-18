<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Axis360/UserAxis360Usage.php';
require_once ROOT_DIR . '/sys/Axis360/Axis360RecordUsage.php';
require_once ROOT_DIR . '/sys/Axis360/Axis360Stats.php';

class Graphs extends Admin_Admin
{
	function launch()
	{
		global $interface;
		$title = 'Axis 360 Usage Graph';
		$stat = $_REQUEST['stat'];
		if (!empty($_REQUEST['instance'])){
			$instanceName = $_REQUEST['instance'];
		}else{
			$instanceName = '';
		}

		$dataSeries = [];
		$columnLabels = [];
		switch ($stat){
			case 'activeUsers':
				$dataSeries['Total Usage'] = [
					'borderColor' => 'rgba(255, 99, 132, 1)',
					'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
					'data' => []
				];
				$dataSeries['Unique Users'] = [
					'borderColor' => 'rgba(54, 162, 235, 1)',
					'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
					'data' => []
				];
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
				}
		}
		$interface->assign('columnLabels', $columnLabels);
		$interface->assign('dataSeries', $dataSeries);

		$interface->assign('graphTitle', $title);
		$this->display('../Admin/usage-graph.tpl', $title);
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#axis360', 'Axis 360');
		$breadcrumbs[] = new Breadcrumb('/Axis360/Dashboard', 'Usage Dashboard');
		$breadcrumbs[] = new Breadcrumb('', 'Usage Graph');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'axis360';
	}

	function canView()
	{
		return UserAccount::userHasPermission(['View System Reports', 'View Dashboards']);
	}
}