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
		
		$profileName= $_REQUEST['profileName'];
		$sideloadId = $this->getSideloadIdBySideLoadName($profileName);
		$this->getAndSetInterfaceDataSeries($stat, $instanceName, $sideloadId);
		$interface->assign('profileName', $profileName);

		$interface->assign('stat', $stat);
		$interface->assign('propName', 'exportToCSV');
		$interface->assign('showCSVExportButton', true);
		$interface->assign('section', 'SideLoads');

		$title = $interface->getVariable('graphTitle');
		$this->display('../Admin/usage-graph.tpl', $title);
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

	// note that this will only handle tables with one stat (as is needed for Summon usage data)
	// to see a version that handle multpile stats, see the Admin/UsageGraphs.php implementation
	public function buildCSV() {
		global $interface;
		$stat = $_REQUEST['stat'];
		if (!empty($_REQUEST['instance'])) {
			$instanceName = $_REQUEST['instance'];
		} else {
			$instanceName = '';
		}
		
		$profileName= $_REQUEST['profileName'];
		$sideloadId = $this->getSideloadIdBySideLoadName($profileName);
		$this->getAndSetInterfaceDataSeries($stat, $instanceName, $sideloadId);
		$dataSeries = $interface->getVariable('dataSeries');

		$filename = "SideLoadsUsageData_{$stat}.csv";
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
		header('Content-Type: text/csv; charset=utf-8');
		header("Content-Disposition: attachment;filename={$filename}");
		$fp = fopen('php://output', 'w');

		// builds the first row of the table in the CSV - column headers: Dates, and the title of the graph
		fputcsv($fp, ['Dates', $stat]);

		// builds each subsequent data row - aka the column value
		foreach ($dataSeries as $dataSerie) {
			$data = $dataSerie['data'];
			$numRows = count($data);
			$dates = array_keys($data);
			for($i = 0; $i < $numRows; $i++) {
				$date = $dates[$i];
				$value = $data[$date];
				$row = [$date, $value];
				fputcsv($fp, $row);
			}
		}
		exit();
	}

	/*
		The only unique identifier available to determine for which
		sideload to fetch data is the sideload's name as $profileName. It is used
		here to find the sideloads' id as only this exists on the sideload
		usage tables
	*/
	private function getSideloadIdBySideLoadName($name) {
		$sideload = new SideLoad();
		$sideload->whereAdd('name = "' . $name .'"');
		$sideload->selectAdd();
		$sideload->find();
		return $sideload->fetch()->id;
	}

	private function getAndSetInterfaceDataSeries($stat, $instanceName, $sideloadId) {
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
			$usage->whereAdd("sideloadId = $sideloadId");
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
