<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Indexing/UserSideLoadUsage.php';
require_once ROOT_DIR . '/sys/Indexing/SideLoadedRecordUsage.php';

class SideLoads_UsageGraphs extends Admin_Admin {
	function launch() {
		global $interface;
		$stat = $_REQUEST['stat'];
		if (!empty($_REQUEST['instance'])) {
			$instanceName = $_REQUEST['instance'];
		} else {
			$instanceName = '';
		}
		$title = 'Side Loading Usage Graph';

		$interface->assign('graphTitle', $title);
		$this->assignGraphSpecificTitle($stat);
		$this->getAndSetInterfaceDataSeries($stat, $instanceName);
		$title = $interface->getVariable('graphTitle');
		$this->display('usage-graph.tpl', $title);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#side_loads', 'Side Loads');
		$breadcrumbs[] = new Breadcrumb('/SideLoads/UsageDashboard', 'Usage Dashboard');
		$breadcrumbs[] = new Breadcrumb('', 'Usage Graph');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'side_loads';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'View Dashboards',
			'View System Reports',
		]);
	}

	private function getAndSetInterfaceDataSeries($stat, $instanceName) {
		global $interface;

		$dataSeries = [];
		$columnLabels = [];
		$usage = [];

		// for the graph displaying data retrieved from the user_sideload_usage table
		if ($stat == 'activeUsers') {
			$usage = new UserSideLoadUsage();
			$usage->groupBy('year, month');
			if (!empty($instanceName)) {
				$usage->instance = $instanceName;
			}
			$usage->selectAdd();
			$usage->selectAdd('year');
			$usage->selectAdd('month');
			$usage->orderBy('year, month');

			$dataSeries['Active Users'] = [
				'borderColor' => 'rgba(255, 99, 132, 1)',
				'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
				'data' => [],
			];
			$usage->selectAdd('COUNT(id) as numUsers');
		}

		// for the graph displaying data retrieved from the sideload_record_usage table
		if ($stat == 'recordsAccessedOnline' ) {
			$usage = new SideLoadedRecordUsage();
			$usage->groupBy('year, month');
			if (!empty($instanceName)) {
				$usage->instance = $instanceName;
			}

			$usage->selectAdd(null);
			$usage->selectAdd();
			$usage->selectAdd('year');
			$usage->selectAdd('month');
			$usage->orderBy('year, month');

			$dataSeries['Records Accessed Online'] = [
				'borderColor' => 'rgba(255, 99, 132, 1)',
				'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
				'data' => [],
			];
			$usage->selectAdd('SUM(IF(timesUsed>0,1,0)) as numRecordsUsed');
		}

		// collect results
		$usage->find();
		while ($usage->fetch()) {
			$curPeriod = "{$usage->month}-{$usage->year}";
			$columnLabels[] = $curPeriod;
			if ($stat == 'activeUsers') {
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['Active Users']['data'][$curPeriod] = $usage->numUsers;
			}
			if ($stat == 'recordsAccessedOnline') {
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['Records Accessed Online']['data'][$curPeriod] = $usage->numRecordsUsed;
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
		if ($stat == 'activeUsers') {
			$title .= ' - Active Users';
		}
		if ($stat == 'recordsAccessedOnline') {
			$title .= ' - Records Accessed Online';
		}
		$interface->assign('graphTitle', $title);
	}
}
