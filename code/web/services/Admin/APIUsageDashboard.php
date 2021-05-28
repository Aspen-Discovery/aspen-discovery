<?php
require_once ROOT_DIR . '/services/Admin/Dashboard.php';
require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';

class Admin_APIUsageDashboard extends Admin_Dashboard
{
	function launch()
	{
		global $interface;

		$instanceName = $this->loadInstanceInformation('APIUsage');
		$this->loadDates();

		//Load stats by module.
		//moduleName => [
		//		method => [
		//			usageThisMonth = number
		//			usageLastMonth = number
		//			usageThisYear = number
		//			usageAllTime = number
		$statsByModule = [];
		$this->getStats($instanceName, $this->thisMonth, $this->thisYear, $statsByModule, 'usageThisMonth');
		$this->getStats($instanceName, $this->lastMonth, $this->lastMonthYear, $statsByModule, 'usageLastMonth');
		$this->getStats($instanceName, null, $this->thisYear, $statsByModule, 'usageThisYear');
		$this->getStats($instanceName, null, null, $statsByModule, 'usageAllTime');

		$interface->assign('statsByModule', $statsByModule);

		$this->display('api_usage_dashboard.tpl', 'Aspen Usage Dashboard');
	}

	/**
	 * @param string|null $instanceName
	 * @param string|null $month
	 * @param string|null $year
	 * @param array $statsByModule The stats being loaded
	 * @param string $statsPeriodName The period of stats being loaded
	 * @return void
	 */
	function getStats($instanceName, $month, $year, &$statsByModule, $statsPeriodName)
	{
		$usage = new APIUsage();
		if (!empty($instanceName)){
			$usage->instance = $instanceName;
		}
		if ($month != null){
			$usage->month = $month;
		}
		if ($year != null){
			$usage->year = $year;
		}
		$usage->selectAdd();
		$usage->selectAdd('module');
		$usage->selectAdd('method');
		$usage->selectAdd('SUM(numCalls) as numCalls');
		$usage->orderBy('module, method');
		$usage->groupBy('module, method');

		$usage->find();

		while ($usage->fetch()){
			if (!array_key_exists($usage->module, $statsByModule)){
				$statsByModule[$usage->module] = [];
			}
			if (!array_key_exists($usage->method, $statsByModule[$usage->module])){
				$statsByModule[$usage->module][$usage->method] = [
					'usageThisMonth' => 0,
					'usageLastMonth' => 0,
					'usageThisYear' => 0,
					'usageAllTime' => 0
				];
			}
			$statsByModule[$usage->module][$usage->method][$statsPeriodName] = $usage->numCalls;
		}
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_reports', 'System Reports');
		$breadcrumbs[] = new Breadcrumb('', 'Usage Dashboard');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'system_reports';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission(['View Dashboards', 'View System Reports']);
	}
}