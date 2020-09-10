<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Hoopla/UserHooplaUsage.php';
require_once ROOT_DIR . '/sys/Hoopla/HooplaRecordUsage.php';

class Hoopla_Dashboard extends Admin_Admin
{
	function launch()
	{
		global $interface;

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

		list($activeRecordsThisMonth, $loansThisMonth) = $this->getRecordStats($thisMonth, $thisYear);
		$interface->assign('activeRecordsThisMonth', $activeRecordsThisMonth);
		$interface->assign('loansThisMonth', $loansThisMonth);
		list($activeRecordsLastMonth, $loansLastMonth) = $this->getRecordStats($lastMonth, $lastMonthYear);
		$interface->assign('activeRecordsLastMonth', $activeRecordsLastMonth);
		$interface->assign('loansLastMonth', $loansLastMonth);
		list($activeRecordsThisYear, $loansThisYear) = $this->getRecordStats(null, $thisYear);
		$interface->assign('activeRecordsThisYear', $activeRecordsThisYear);
		$interface->assign('loansThisYear', $loansThisYear);
		list($activeRecordsLastYear, $loansLastYear) = $this->getRecordStats(null, $lastYear);
		$interface->assign('activeRecordsLastYear', $activeRecordsLastYear);
		$interface->assign('loansLastYear', $loansLastYear);
		list($activeRecordsAllTime, $loansAllTime) = $this->getRecordStats(null, null);
		$interface->assign('activeRecordsAllTime', $activeRecordsAllTime);
		$interface->assign('loansAllTime', $loansAllTime);

		$this->display('dashboard.tpl', 'Hoopla Dashboard');
	}

	/**
	 * @param string|null $month
	 * @param string|null $year
	 * @return int
	 */
	public function getUserStats($month, $year): int
	{
		$userUsage = new UserHooplaUsage();
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
		$usage = new HooplaRecordUsage();
		if ($month != null) {
			$usage->month = $month;
		}
		if ($year != null) {
			$usage->year = $year;
		}
		$usage->selectAdd(null);
		$usage->selectAdd('COUNT(id) as recordsUsed');
		$usage->selectAdd('SUM(timesCheckedOut) as totalCheckouts');
		$usage->find(true);

		/** @noinspection PhpUndefinedFieldInspection */
		return [$usage->recordsUsed, (($usage->totalCheckouts != null) ? $usage->totalCheckouts : 0)];
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#hoopla', 'Hoopla');
		$breadcrumbs[] = new Breadcrumb('/Hoopla/Dashboard', 'Usage Dashboard');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'hoopla';
	}

	function canView()
	{
		return UserAccount::userHasPermission(['View System Reports', 'View Dashboards']);
	}
}