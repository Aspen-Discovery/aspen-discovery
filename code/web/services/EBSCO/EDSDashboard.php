<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Dashboard.php';
require_once ROOT_DIR . '/sys/Ebsco/UserEbscoEdsUsage.php';
require_once ROOT_DIR . '/sys/Ebsco/EbscoEdsRecordUsage.php';

class EBSCO_EDSDashboard extends Admin_Dashboard
{
	function launch()
	{
		global $interface;

		$instanceName = $this->loadInstanceInformation('EbscoEdsRecordUsage');
		$this->loadDates();

		//Generate stats

		$activeUsersThisMonth = $this->getUserStats($instanceName, $this->thisMonth, $this->thisYear);
		$interface->assign('activeUsersThisMonth', $activeUsersThisMonth);
		$activeUsersLastMonth = $this->getUserStats($instanceName, $this->lastMonth, $this->lastMonthYear);
		$interface->assign('activeUsersLastMonth', $activeUsersLastMonth);
		$activeUsersThisYear = $this->getUserStats($instanceName, null, $this->thisYear);
		$interface->assign('activeUsersThisYear', $activeUsersThisYear);
		$activeUsersLastYear = $this->getUserStats($instanceName, null, $this->lastYear);
		$interface->assign('activeUsersLastYear', $activeUsersLastYear);
		$activeUsersAllTime = $this->getUserStats($instanceName, null, null);
		$interface->assign('activeUsersAllTime', $activeUsersAllTime);

		$thisMonthStats = $this->getRecordStats($instanceName, $this->thisMonth, $this->thisYear);
		$interface->assign('thisMonthStats', $thisMonthStats);
		$lastMonthStats = $this->getRecordStats($instanceName, $this->lastMonth, $this->lastMonthYear);
		$interface->assign('lastMonthStats', $lastMonthStats);
		$thisYearStats = $this->getRecordStats($instanceName, null, $this->thisYear);
		$interface->assign('thisYearStats', $thisYearStats);
		$lastYearStats = $this->getRecordStats($instanceName, null, $this->lastYear);
		$interface->assign('lastYearStats', $lastYearStats);
		$allTimeStats = $this->getRecordStats($instanceName, null, null);
		$interface->assign('allTimeStats', $allTimeStats);

		$this->display('edsDashboard.tpl', 'EBSCO EDS Dashboard');
	}

	/**
	 * @param string|null $instanceName
	 * @param string|null $month
	 * @param string|null $year
	 * @return int
	 */
	public function getUserStats($instanceName, $month, $year): int
	{
		$userUsage = new UserEbscoEdsUsage();
		if (!empty($instanceName)){
			$userUsage->instance = $instanceName;
		}
		if ($month != null){
			$userUsage->month = $month;
		}
		if ($year != null){
			$userUsage->year = $year;
		}
		return $userUsage->count();
	}

	/**
	 * @param string|null $instanceName
	 * @param string|null $month
	 * @param string|null $year
	 * @return array
	 */
	public function getRecordStats($instanceName, $month, $year): array
	{
		$usage = new EbscoEdsRecordUsage();
		if (!empty($instanceName)){
			$usage->instance = $instanceName;
		}
		if ($month != null){
			$usage->month = $month;
		}
		if ($year != null){
			$usage->year = $year;
		}
		$usage->selectAdd(null);
		$usage->selectAdd('COUNT(ebscoId) as recordsUsed');
		$usage->selectAdd('SUM(IF(timesViewedInSearch>0,1,0)) as numRecordsViewed');
		$usage->selectAdd('SUM(IF(timesUsed>0,1,0)) as numRecordsUsed');
		$usage->selectAdd('SUM(timesUsed) as numClicks');
		$usage->find(true);

		/** @noinspection PhpUndefinedFieldInspection */
		return [
			'recordsUsed' => $usage->recordsUsed,
			'numRecordsViewed' => (($usage->numRecordsViewed != null) ? $usage->numRecordsViewed : 0),
			'numRecordsUsed' => (($usage->numRecordsUsed != null) ? $usage->numRecordsUsed : 0),
			'numClicks' => (($usage->numClicks != null) ? $usage->numClicks : 0)
		];
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#ebsco', 'EBSCO');
		$breadcrumbs[] = new Breadcrumb('/EBSCO/EDSDashboard', 'EDS Usage Dashboard');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'ebsco';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission(['View System Reports', 'View Dashboards']);
	}
}