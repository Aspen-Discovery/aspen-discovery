<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';

class Admin_APIUsageGraphs extends Admin_Admin
{
	function launch()
	{
		global $interface;

		$stat = $_REQUEST['stat'];
		if (!empty($_REQUEST['instance'])) {
			$instanceName = $_REQUEST['instance'];
		} else {
			$instanceName = '';
		}

		$title = 'Aspen Discovery API Usage Graph';
		$interface->assign('graphTitle', $title);
		$this->assignGraphSpecificTitle($stat);
		$this->getAndSetInterfaceDataSeries($stat, $instanceName);
		$interface->assign('stat', $stat);
		$interface->assign('propName', 'exportToCSV');
		$title = $interface->getVariable('graphTitle');
		$this->display('usage-graph.tpl', $title);
	}
	function getBreadcrumbs(): array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_reports', 'System Reports');
		$breadcrumbs[] = new Breadcrumb('/Admin/APIUsageDashboard', 'Usage Dashboard');
		$breadcrumbs[] = new Breadcrumb('', 'Usage Graph');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string
	{
		return 'system_reports';
	}

	function canView(): bool
	{
		return UserAccount::userHasPermission([
			'View Dashboards',
			'View System Reports',
		]);
	}

	public function buildCSV()
	{
		global $interface;

		$stat = $_REQUEST['stat'];
		if (!empty($_REQUEST['instance'])) {
			$instanceName = $_REQUEST['instance'];
		} else {
			$instanceName = '';
		}
		$this->getAndSetInterfaceDataSeries($stat, $instanceName);
		$dataSeries = $interface->getVariable('dataSeries');

		$filename = "AspenAPIUsageData_{$stat}.csv";
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
		for ($i = 0; $i < $numGraphTitles; $i++) {
			$dataSerie = $dataSeries[$graphTitles[$i]];
			$numRows = count($dataSerie['data']);
			$dates = array_keys($dataSerie['data']);
			$header = ['Dates', $graphTitles[$i]];
			fputcsv($fp, $header);

			// builds each subsequent data row - aka the column value
			for ($j = 0; $j < $numRows; $j++) {
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
		$usage = new APIUsage();
		$usage->groupBy('year, month');
		if (!empty($instanceName)) {
			$usage->instance = $instanceName;
		}
		$usage->selectAdd();
		$usage->selectAdd('year');
		$usage->selectAdd('month');
		$usage->orderBy('year, month');

		// get runPendingDatabaseUpdates stats
		$dataSeries['runPendingDatabaseUpdates'] = [
			'borderColor' => 'rgba(255, 99, 132, 1)',
			'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
			'data' => [],
		];
		$usage->selectAdd('SUM(numCalls) as numCalls');

		//Collect results
		$usage->find();

		while ($usage->fetch()) {
			$curPeriod = "{$usage->month}-{$usage->year}";
			$columnLabels[] = $curPeriod;
			/** @noinspection PhpUndefinedFieldInspection */
			$dataSeries['runPendingDatabaseUpdates']['data'][$curPeriod] = $usage->numCalls;
		}
		$interface->assign('columnLabels', $columnLabels);
		$interface->assign('dataSeries', $dataSeries);
		$interface->assign('translateDataSeries', true);
		$interface->assign('translateColumnLabels', false);
	}

	private function assignGraphSpecificTitle()
	{
		global $interface;
		$title = 'Aspen Discovery API Usage Graph';
		$title .= ' - runPendingDatabaseUpdates';
		$interface->assign('graphTitle', $title);
	}
}
