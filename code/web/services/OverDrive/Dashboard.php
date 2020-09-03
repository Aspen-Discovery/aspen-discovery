<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/OverDrive/UserOverDriveUsage.php';
require_once ROOT_DIR . '/sys/OverDrive/OverDriveRecordUsage.php';

class OverDrive_Dashboard extends Admin_Admin
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

		$this->display('dashboard.tpl', 'OverDrive Dashboard');
	}

	/**
	 * @param string|null $month
	 * @param string|null $year
	 * @return int
	 */
	public function getUserStats($month, $year): int
	{
		$userUsage = new UserOverDriveUsage();
		if ($month != null) {
			$userUsage->month = $month;
		}
		if ($year != null) {
			$userUsage->year = $year;
		}
		$activeUsersThisMonth = $userUsage->count();
		return $activeUsersThisMonth;
	}

	/**
	 * @param string|null $month
	 * @param string|null $year
	 * @return array
	 */
	public function getRecordStats($month, $year): array
	{
		$usage = new OverDriveRecordUsage();
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
		return [$usage->recordsUsed, ($usage->totalCheckouts == null ? 0 : $usage->totalCheckouts), ($usage->totalHolds == null ? 0 : $usage->totalHolds)];
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#overdrive', 'OverDrive');
		$breadcrumbs[] = new Breadcrumb('/OverDrive/Dashboard', 'Usage Dashboard');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'overdrive';
	}

	function canView()
	{
		return UserAccount::userHasPermission(['View System Reports', 'View Dashboards']);
	}
}