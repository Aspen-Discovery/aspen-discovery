<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Ebsco/UserEbscoEdsUsage.php';
require_once ROOT_DIR . '/sys/Ebsco/EbscoEdsRecordUsage.php';

class EBSCO_EDSDashboard extends Admin_Admin
{
	function launch()
	{
		global $interface;

		$thisMonth = date('n');
		$thisYear = date('Y');
		$lastMonth = $thisMonth - 1;
		$lastMonthYear = $thisYear;
		if ($lastMonth == 0){
			$lastMonth = 12;
			$lastMonthYear--;
		}
		$lastYear = $thisYear -1 ;
		//Generate stats

		$activeUsersThisMonth = $this->getUserStats($thisMonth, $thisYear);
		$interface->assign('activeUsersThisMonth', $activeUsersThisMonth);
		$activeUsersLastMonth = $this->getUserStats($lastMonth, $lastMonthYear);
		$interface->assign('activeUsersLastMonth', $activeUsersLastMonth);
		$activeUsersThisYear = $this->getUserStats(null, $thisYear);
		$interface->assign('activeUsersThisYear', $activeUsersThisYear);
		$activeUsersLastYear = $this->getUserStats(null, $lastYear);
		$interface->assign('activeUsersLastYear', $activeUsersLastYear);
		$activeUsersAllTime = $this->getUserStats(null, null);
		$interface->assign('activeUsersAllTime', $activeUsersAllTime);

		$thisMonthStats = $this->getRecordStats($thisMonth, $thisYear);
		$interface->assign('thisMonthStats', $thisMonthStats);
		$lastMonthStats = $this->getRecordStats($lastMonth, $lastMonthYear);
		$interface->assign('lastMonthStats', $lastMonthStats);
		$thisYearStats = $this->getRecordStats(null, $thisYear);
		$interface->assign('thisYearStats', $thisYearStats);
		$lastYearStats = $this->getRecordStats(null, $lastYear);
		$interface->assign('lastYearStats', $lastYearStats);
		$allTimeStats = $this->getRecordStats(null, null);
		$interface->assign('allTimeStats', $allTimeStats);

		$this->display('edsDashboard.tpl', 'EBSCO EDS Dashboard');
	}

	function getAllowableRoles(){
		return array('opacAdmin');
	}

	/**
	 * @param string|null $month
	 * @param string|null $year
	 * @return int
	 */
	public function getUserStats($month, $year): int
	{
		$userUsage = new UserEbscoEdsUsage();
		if ($month != null){
			$userUsage->month = $month;
		}
		if ($year != null){
			$userUsage->year = $year;
		}
		return $userUsage->count();
	}

	/**
	 * @param string|null $month
	 * @param string|null $year
	 * @return array
	 */
	public function getRecordStats($month, $year): array
	{
		$usage = new EbscoEdsRecordUsage();
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

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#ebsco', 'EBSCO');
		$breadcrumbs[] = new Breadcrumb('/EBSCO/EDSDashboard', 'EDS Usage Dashboard');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'ebsco';
	}
}