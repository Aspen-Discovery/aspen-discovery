<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Axis360/UserAxis360Usage.php';
require_once ROOT_DIR . '/sys/Axis360/Axis360RecordUsage.php';
require_once ROOT_DIR . '/sys/Axis360/Axis360Stats.php';

class Axis360_Dashboard extends Admin_Admin
{
	function launch()
	{
		global $interface;

		$instanceName = null;

		$thisMonth = date('n');
		$thisYear = date('Y');
		$lastMonth = $thisMonth - 1;
		$lastMonthYear = $thisYear;
		if ($lastMonth == 0) {
			$lastMonth = 12;
			$lastMonthYear--;
		}
		$lastYear = $thisYear - 1;
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

		$statsThisMonth = $this->getStats($instanceName, $thisMonth, $thisYear);
		$interface->assign('statsThisMonth', $statsThisMonth);
		$statsLastMonth = $this->getStats($instanceName, $lastMonth, $lastMonthYear);
		$interface->assign('statsLastMonth', $statsLastMonth);
		$statsThisYear = $this->getStats($instanceName, null, $thisYear);
		$interface->assign('statsThisYear', $statsThisYear);
		$statsLastYear = $this->getStats($instanceName, null, $lastYear);
		$interface->assign('statsLastYear', $statsLastYear);
		$statsAllTime = $this->getStats($instanceName, null, null);
		$interface->assign('statsAllTime', $statsAllTime);

		list($activeRecordsThisMonth, $loansThisMonth, $holdsThisMonth) = $this->getRecordStats($thisMonth, $thisYear);
		$interface->assign('activeRecordsThisMonth', $activeRecordsThisMonth);
		$interface->assign('loansThisMonth', $loansThisMonth);
		$interface->assign('holdsThisMonth', $holdsThisMonth);
		list($activeRecordsLastMonth, $loansLastMonth, $holdsLastMonth) = $this->getRecordStats($lastMonth, $lastMonthYear);
		$interface->assign('activeRecordsLastMonth', $activeRecordsLastMonth);
		$interface->assign('loansLastMonth', $loansLastMonth);
		$interface->assign('holdsLastMonth', $holdsLastMonth);
		list($activeRecordsThisYear, $loansThisYear, $holdsThisYear) = $this->getRecordStats(null, $thisYear);
		$interface->assign('activeRecordsThisYear', $activeRecordsThisYear);
		$interface->assign('loansThisYear', $loansThisYear);
		$interface->assign('holdsThisYear', $holdsThisYear);
		list($activeRecordsLastYear, $loansLastYear, $holdsLastYear) = $this->getRecordStats(null, $lastYear);
		$interface->assign('activeRecordsLastYear', $activeRecordsLastYear);
		$interface->assign('loansLastYear', $loansLastYear);
		$interface->assign('holdsLastYear', $holdsLastYear);
		list($activeRecordsAllTime, $loansAllTime, $holdsAllTime) = $this->getRecordStats(null, null);
		$interface->assign('activeRecordsAllTime', $activeRecordsAllTime);
		$interface->assign('loansAllTime', $loansAllTime);
		$interface->assign('holdsAllTime', $holdsAllTime);

		$this->display('dashboard.tpl', 'Axis 360 Dashboard');
	}

	/**
	 * @param string|null $month
	 * @param string|null $year
	 * @return int
	 */
	public function getUserStats($month, $year): int
	{
		$userUsage = new UserAxis360Usage();
		if ($month != null) {
			$userUsage->month = $month;
		}
		if ($year != null) {
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
		$usage = new Axis360RecordUsage();
		if ($month != null) {
			$usage->month = $month;
		}
		if ($year != null) {
			$usage->year = $year;
		}
		$usage->selectAdd(null);
		$usage->selectAdd('COUNT(id) as recordsUsed');
		$usage->selectAdd('SUM(timesHeld) as totalHolds');
		$usage->selectAdd('SUM(timesCheckedOut) as totalCheckouts');
		$usage->find(true);

		/** @noinspection PhpUndefinedFieldInspection */
		return [
			$usage->recordsUsed,
			(($usage->totalCheckouts != null) ? $usage->totalCheckouts : 0),
			(($usage->totalHolds != null) ? $usage->totalHolds : 0),
		];
	}

	/**
	 * @param string|null $instanceName
	 * @param string|null $month
	 * @param string|null $year
	 * @return Axis360Stats
	 */
	public function getStats($instanceName, $month, $year): Axis360Stats
	{
		$stats = new Axis360Stats();
		if ($instanceName != null){
			$stats->instance = $instanceName;
		}
		if ($month != null) {
			$stats->month = $month;
		}
		if ($year != null) {
			$stats->year = $year;
		}
		$stats->selectAdd(null);
		$stats->selectAdd('SUM(numCheckouts) as numCheckouts');
		$stats->selectAdd('SUM(numRenewals) as numRenewals');
		$stats->selectAdd('SUM(numEarlyReturns) as numEarlyReturns');
		$stats->selectAdd('SUM(numHoldsPlaced) as numHoldsPlaced');
		$stats->selectAdd('SUM(numHoldsCancelled) as numHoldsCancelled');
		$stats->selectAdd('SUM(numHoldsFrozen) as numHoldsFrozen');
		$stats->selectAdd('SUM(numHoldsThawed) as numHoldsThawed');
		$stats->selectAdd('SUM(numApiErrors) as numApiErrors');
		$stats->selectAdd('SUM(numConnectionFailures) as numConnectionFailures');

		if ($stats->find(true)){
			return $stats;
		}else{
			return new Axis360Stats();
		}


	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#axis360', 'Axis 360');
		$breadcrumbs[] = new Breadcrumb('/Axis360/Dashboard', 'Usage Dashboard');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'axis360';
	}

	function canView()
	{
		return UserAccount::userHasPermission(['View System Reports', 'View Dashboards']);
	}
}