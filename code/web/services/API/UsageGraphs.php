<?php

require_once ROOT_DIR . '/services/Admin/AbstractUsageGraphs.php';
require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
require_once ROOT_DIR . '/sys/Utils/GraphingUtils.php';

class API_UsageGraphs extends Admin_AbstractUsageGraphs {
	function launch(): void {
		$this->launchGraph('API');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_reports', 'System Reports');
		$breadcrumbs[] = new Breadcrumb('/Admin/APIUsageDashboard', 'Usage Dashboard');
		$breadcrumbs[] = new Breadcrumb('', 'Usage Graph');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'system_reports';
	}

	protected function getAndSetInterfaceDataSeries($stat, $instanceName, $timeframes = ['year', 'month'], $custom = false): void {
		global $interface;

		$groupByTimeframe = implode(',', $timeframes);
		$dataSeries = [];
		$columnLabels = [];
		$usage = new APIUsage();
		$usage->selectAdd();
		if (!empty($instanceName)) {
			$usage->instance = $instanceName;
		}
		$usage->method = $stat;

		if (is_array($custom)) {
			$usage->buildCustomPeriodQuery($custom);
		} else {
			$usage->groupBy($groupByTimeframe);
			foreach ($timeframes as $timeframe) {
				$usage->selectAdd($timeframe);
			}
			$usage->orderBy($groupByTimeframe);
		}
		
		$dataSeries[$stat] = GraphingUtils::getDataSeriesArray(count($dataSeries));
		$usage->selectAdd('SUM(numCalls) as numCalls');

		//Collect results
		$usage->find();

		while ($usage->fetch()) {
			$curPeriod = $custom ? $usage->getCustomPeriod() : $usage->getCurPeriod($timeframes);
			$columnLabels[] = $curPeriod;
			/** @noinspection PhpUndefinedFieldInspection */
			$dataSeries[$stat]['data'][$curPeriod] = $usage->numCalls;
		}
		$interface->assign('columnLabels', $columnLabels);
		$interface->assign('dataSeries', $dataSeries);
		$interface->assign('translateDataSeries', true);
		$interface->assign('translateColumnLabels', false);
	}

	protected function assignGraphSpecificTitle($stat): void {
		global $interface;
		$title = 'Aspen Discovery API Usage Graph';
		$title .= " - $stat";
		$interface->assign('graphTitle', $title);
	}
}
