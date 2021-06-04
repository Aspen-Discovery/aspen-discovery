<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Dashboard.php';
require_once ROOT_DIR . '/sys/Indexing/UserSideLoadUsage.php';
require_once ROOT_DIR . '/sys/Indexing/SideLoadedRecordUsage.php';

class SideLoads_Dashboard extends Admin_Dashboard
{
	function launch()
	{
		global $interface;

		$instanceName = $this->loadInstanceInformation('SideLoadedRecordUsage');
		$this->loadDates();

		global $sideLoadSettings;
		$profilesToGetStatsFor = [];
		foreach ($sideLoadSettings as $sideLoad) {
			$profilesToGetStatsFor[$sideLoad->id] = $sideLoad->name;
		}
		$interface->assign('profiles', $profilesToGetStatsFor);

		//Generate stats
		$activeUsersThisMonth = $this->getUserStats($instanceName, $this->thisMonth, $this->thisYear, $profilesToGetStatsFor);
		$interface->assign('activeUsersThisMonth', $activeUsersThisMonth);
		$activeUsersLastMonth = $this->getUserStats($instanceName, $this->lastMonth, $this->lastMonthYear, $profilesToGetStatsFor);
		$interface->assign('activeUsersLastMonth', $activeUsersLastMonth);
		$activeUsersThisYear = $this->getUserStats($instanceName, null, $this->thisYear, $profilesToGetStatsFor);
		$interface->assign('activeUsersThisYear', $activeUsersThisYear);
		$activeUsersLastYear = $this->getUserStats($instanceName, null, $this->lastYear, $profilesToGetStatsFor);
		$interface->assign('activeUsersLastYear', $activeUsersLastYear);
		$activeUsersAllTime = $this->getUserStats($instanceName, null, null, $profilesToGetStatsFor);
		$interface->assign('activeUsersAllTime', $activeUsersAllTime);

		$activeRecordsThisMonth = $this->getRecordStats($instanceName, $this->thisMonth, $this->thisYear, $profilesToGetStatsFor);
		$interface->assign('activeRecordsThisMonth', $activeRecordsThisMonth);
		$activeRecordsLastMonth = $this->getRecordStats($instanceName, $this->lastMonth, $this->lastMonthYear, $profilesToGetStatsFor);
		$interface->assign('activeRecordsLastMonth', $activeRecordsLastMonth);
		$activeRecordsThisYear = $this->getRecordStats($instanceName, null, $this->thisYear, $profilesToGetStatsFor);
		$interface->assign('activeRecordsThisYear', $activeRecordsThisYear);
		$activeRecordsLastYear = $this->getRecordStats($instanceName, null, $this->lastYear, $profilesToGetStatsFor);
		$interface->assign('activeRecordsLastYear', $activeRecordsLastYear);
		$activeRecordsAllTime = $this->getRecordStats($instanceName, null, null, $profilesToGetStatsFor);
		$interface->assign('activeRecordsAllTime', $activeRecordsAllTime);

		$this->display('dashboard.tpl', 'Side Load Dashboard');
	}

	/**
	 * @param string|null $instanceName
	 * @param string|null $month
	 * @param string|null $year
	 * @param int[] $profilesToGetStatsFor
	 * @return int[]
	 */
	public function getUserStats($instanceName, $month, $year, $profilesToGetStatsFor): array
	{
		$userUsage = new UserSideLoadUsage();
		if (!empty($instanceName)){
			$userUsage->instance = $instanceName;
		}
		if ($month != null) {
			$userUsage->month = $month;
		}
		if ($year != null) {
			$userUsage->year = $year;
		}
		$userUsage->groupBy('sideLoadId');
		$userUsage->selectAdd();
		$userUsage->selectAdd('sideLoadId');
		$userUsage->selectAdd('COUNT(id) as numUsers');
		$userUsage->find();
		$usageStats = [];
		foreach ($profilesToGetStatsFor as $id => $name) {
			$usageStats[$id] = 0;
		}
		while ($userUsage->fetch()) {
			/** @noinspection PhpUndefinedFieldInspection */
			$usageStats[$userUsage->sideLoadId] = $userUsage->numUsers;
		}
		return $usageStats;
	}

	/**
	 * @param string|null $instanceName
	 * @param string|null $month
	 * @param string|null $year
	 * @param int[] $profilesToGetStatsFor
	 * @return int[]
	 */
	public function getRecordStats($instanceName, $month, $year, $profilesToGetStatsFor): array
	{
		$usage = new SideLoadedRecordUsage();
		if (!empty($instanceName)){
			$usage->instance = $instanceName;
		}
		if ($month != null) {
			$usage->month = $month;
		}
		if ($year != null) {
			$usage->year = $year;
		}
		$usage->groupBy('sideLoadId');
		$usage->selectAdd(null);
		$usage->selectAdd('sideLoadId');

		$usage->selectAdd('SUM(IF(timesUsed>0,1,0)) as numRecordsUsed');
		$usage->find();

		$usageStats = [];
		foreach ($profilesToGetStatsFor as $id => $name) {
			$usageStats[$id] = [
				'numRecordsUsed' => 0
			];
		}
		while ($usage->fetch()) {
			/** @noinspection PhpUndefinedFieldInspection */
			$usageStats[$usage->sideLoadId] = [
				'numRecordsUsed' => $usage->numRecordsUsed
			];
		}
		return $usageStats;
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#side_loads', 'Side Loads');
		$breadcrumbs[] = new Breadcrumb('/SideLoads/Dashboard', 'Usage Dashboard');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'side_loads';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission(['View System Reports', 'View Dashboards']);
	}
}