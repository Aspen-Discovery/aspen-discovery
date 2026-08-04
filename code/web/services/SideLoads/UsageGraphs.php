<?php

require_once ROOT_DIR . '/services/Admin/AbstractUsageGraphs.php';
require_once ROOT_DIR . '/sys/Indexing/UserSideLoadUsage.php';
require_once ROOT_DIR . '/sys/Indexing/SideLoadedRecordUsage.php';
require_once ROOT_DIR . '/sys/Utils/GraphingUtils.php';


class SideLoads_UsageGraphs extends Admin_AbstractUsageGraphs {
	function launch(): void {
		$this->launchGraph('SideLoads');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#side_loads', 'Side Loads');
		$breadcrumbs[] = new Breadcrumb('/SideLoads/Dashboard', 'Usage Dashboard');
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

	/*
		The only unique identifier available to determine for which
		sideload to fetch data is the sideload's name as $profileName. It is used
		here to find the sideloads' id as only this exists on the sideload
		usage tables
	*/
	private function getSideloadIdBySideLoadName($name): int {
		$sideload = new SideLoad();
		$sideload->name = $name;
		$sideload->selectAdd();
		$sideload->find();
		return $sideload->fetch()->id;
	}

	protected function getAndSetInterfaceDataSeries($stat, $instanceName, $timeframes = ['year', 'month'], $custom = false): void {
		global $interface;

		$dataSeries = [];
		$columnLabels = [];
		$usage = [];
		$groupByTimeframe = implode(',', $timeframes);

		if ($_REQUEST['sideloadId']) {
			$sideloadId = $_REQUEST['sideloadId'];
		} else {
			$profileName= $_REQUEST['profileName'];
			$sideloadId = $this->getSideloadIdBySideLoadName($profileName);
		}

		if ($stat == 'activeUsers') {
			$usage = new UserSideLoadUsage();
		} elseif ($stat == 'recordsAccessedOnline') {
			$usage = new SideLoadedRecordUsage();
		}
		
		if (is_array($custom)) {
			$usage->buildCustomPeriodQuery($custom);
		} else {
			$usage->groupBy($groupByTimeframe);
			foreach ($timeframes as $timeframe) {
				$usage->selectAdd($timeframe);
			}
			$usage->orderBy($groupByTimeframe);
		}
		$usage->sideloadId = $sideloadId;

		if ($stat == 'activeUsers') {
			$dataSeries['Active Users'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
			$usage->selectAdd('COUNT(id) as numUsers');
		} elseif ($stat == 'recordsAccessedOnline') {
			$dataSeries['Records Accessed Online'] = GraphingUtils::getDataSeriesArray(count($dataSeries));
			$usage->selectAdd('SUM(IF(timesUsed>0,1,0)) as numRecordsUsed');
		}

		// collect results
		$usage->find();
		while ($usage->fetch()) {
			$curPeriod = $custom ? $usage->getCustomPeriod() : $usage->getCurPeriod($timeframes);
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
		$interface->assign('sideloadId', $sideloadId);
	}

	protected function assignGraphSpecificTitle($stat): void {
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
