<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/SystemLogging/AspenUsage.php';
require_once ROOT_DIR . '/sys/Summon/UserSummonUsage.php';
require_once ROOT_DIR . '/sys/Summon/SummonRecordUsage.php';

class Summon_UsageGraphs extends Admin_Admin {
	function launch() {
		global $interface;
		$title = 'Summon Usage Graph';
		$stat = $_REQUEST['stat'];
		if (!empty($_REQUEST['instance'])) {
			$instanceName = $_REQUEST['instance'];
		} else {
			$instanceName = '';
		}

		$interface->assign('graphTitle', $title);
		$this->assignGraphSpecificTitle($stat);
		$this->getAndSetInterfaceDataSeries($stat, $instanceName);
		$interface->assign('stat', $stat);
		$this->display('usage-graph.tpl', $title);
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
		$this->getAndSetInterfaceDataSeries($stat, $instanceName);
		$dataSeries = $interface->getVariable('dataSeries');

		$filename = "SummonUsageData_{$stat}.csv";
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

	private function getAndSetInterfaceDataSeries($stat, $instanceName) {
		global $interface;
		$dataSeries = [];
		$columnLabels = [];

		// gets data from from user_summon_usage
		if ($stat == 'activeUsers') {
			$userSummonUsage = new UserSummonUsage();
			$userSummonUsage->groupBy('year, month');
			if (!empty($instanceName)) {
				$userSummonUsage->instance = $instanceName;
			}
			$userSummonUsage->selectAdd();
			$userSummonUsage->selectAdd('year');
			$userSummonUsage->selectAdd('month');
			$userSummonUsage->orderBy('year, month');

			$dataSeries['Active Users'] = [
				'borderColor' => 'rgba(255, 99, 132, 1)',
				'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
				'data' => [],
			];
			$userSummonUsage->selectAdd('COUNT(DISTINCT userId) as activeUsers');

			// Collects results
			$userSummonUsage->find();
			while($userSummonUsage->fetch()) {
				$curPeriod = "{$userSummonUsage->month}-{$userSummonUsage->year}";
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
			$summonRecordUsage->groupBy('year, month');
			if (!empty($instanceName)) {
				$summonRecordUsage->instance = $instanceName;
			}
			$summonRecordUsage->selectAdd();
			$summonRecordUsage->selectAdd('year');
			$summonRecordUsage->selectAdd('month');
			$summonRecordUsage->orderBy('year, month');
		
			if ($stat == 'numRecordsViewed') {
				$dataSeries['Number of Records Viewed'] = [
					'borderColor' => 'rgba(255, 99, 132, 1)',
					'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
					'data' => [],
				];
				$summonRecordUsage ->selectAdd('SUM(IF(timesViewedInSearch>0,1,0)) as numRecordsViewed');
			}
			if ($stat == 'numRecordsClicked') {
				$dataSeries['Number of Records Clicked'] = [
					'borderColor' => 'rgba(255, 99, 132, 1)',
					'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
					'data' => [],
				];
				$summonRecordUsage ->selectAdd('SUM(IF(timesUsed>0,1,0)) as numRecordsUsed');
			}
			if ($stat == 'totalClicks') {
				$dataSeries['Total Clicks'] = [
					'borderColor' => 'rgba(255, 99, 132, 1)',
					'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
					'data' => [],
				];
				$summonRecordUsage ->selectAdd('SUM(timesUsed) as numClicks');
			}
			// Collect results
			$summonRecordUsage->find();
			while ($summonRecordUsage->fetch()) {
				$curPeriod = "{$summonRecordUsage->month}-{$summonRecordUsage->year}";
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

	private function assignGraphSpecificTitle($stat) {
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