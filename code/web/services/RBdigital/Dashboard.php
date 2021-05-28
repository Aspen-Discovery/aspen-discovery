<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Dashboard.php';
require_once ROOT_DIR . '/sys/RBdigital/UserRBdigitalUsage.php';
require_once ROOT_DIR . '/sys/RBdigital/RBdigitalRecordUsage.php';
require_once ROOT_DIR . '/sys/RBdigital/RBdigitalMagazineUsage.php';

class RBdigital_Dashboard extends Admin_Dashboard
{
	function launch()
	{
		global $interface;

		$instanceName = $this->loadInstanceInformation('UserRBdigitalUsage');
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

		list($activeRecordsThisMonth, $loansThisMonth, $holdsThisMonth, $activeMagazinesThisMonth, $magazineLoansThisMonth) = $this->getRecordStats($instanceName, $this->thisMonth, $this->thisYear);
		$interface->assign('activeRecordsThisMonth', $activeRecordsThisMonth);
		$interface->assign('loansThisMonth', $loansThisMonth);
		$interface->assign('holdsThisMonth', $holdsThisMonth);
		$interface->assign('activeMagazinesThisMonth', $activeMagazinesThisMonth);
		$interface->assign('magazineLoansThisMonth', $magazineLoansThisMonth);
		list($activeRecordsLastMonth, $loansLastMonth, $holdsLastMonth, $activeMagazinesLastMonth, $magazineLoansLastMonth) = $this->getRecordStats($instanceName, $this->lastMonth, $this->lastMonthYear);
		$interface->assign('activeRecordsLastMonth', $activeRecordsLastMonth);
		$interface->assign('loansLastMonth', $loansLastMonth);
		$interface->assign('holdsLastMonth', $holdsLastMonth);
		$interface->assign('activeMagazinesLastMonth', $activeMagazinesLastMonth);
		$interface->assign('magazineLoansLastMonth', $magazineLoansLastMonth);
		list($activeRecordsThisYear, $loansThisYear, $holdsThisYear, $activeMagazinesThisYear, $magazineLoansThisYear) = $this->getRecordStats($instanceName, null, $this->thisYear);
		$interface->assign('activeRecordsThisYear', $activeRecordsThisYear);
		$interface->assign('loansThisYear', $loansThisYear);
		$interface->assign('holdsThisYear', $holdsThisYear);
		$interface->assign('activeMagazinesThisYear', $activeMagazinesThisYear);
		$interface->assign('magazineLoansThisYear', $magazineLoansThisYear);
		list($activeRecordsLastYear, $loansLastYear, $holdsLastYear, $activeMagazinesLastYear, $magazineLoansLastYear) = $this->getRecordStats($instanceName, null, $this->lastYear);
		$interface->assign('activeRecordsLastYear', $activeRecordsLastYear);
		$interface->assign('loansLastYear', $loansLastYear);
		$interface->assign('holdsLastYear', $holdsLastYear);
		$interface->assign('activeMagazinesLastYear', $activeMagazinesLastYear);
		$interface->assign('magazineLoansLastYear', $magazineLoansLastYear);
		list($activeRecordsAllTime, $loansAllTime, $holdsAllTime, $activeMagazinesAllTime, $magazineLoansAllTime) = $this->getRecordStats($instanceName, null, null);
		$interface->assign('activeRecordsAllTime', $activeRecordsAllTime);
		$interface->assign('loansAllTime', $loansAllTime);
		$interface->assign('holdsAllTime', $holdsAllTime);
		$interface->assign('activeMagazinesAllTime', $activeMagazinesAllTime);
		$interface->assign('magazineLoansAllTime', $magazineLoansAllTime);

		$this->display('dashboard.tpl', 'RBdigital Dashboard');
	}

	/**
	 * @param string|null $instanceName
	 * @param string|null $month
	 * @param string|null $year
	 * @return int
	 */
	public function getUserStats($instanceName, $month, $year): int
	{
		$userUsage = new UserRBdigitalUsage();
		if (!empty($instanceName)){
			$userUsage->instance = $instanceName;
		}
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
	 * @param string|null $instanceName
	 * @param string|null $month
	 * @param string|null $year
	 * @return array
	 */
	public function getRecordStats($instanceName, $month, $year): array
	{
		$usage = new RBdigitalRecordUsage();
		if (!empty($instanceName)){
			$usage->instance = $instanceName;
		}
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

		$magazineUsage = new RBdigitalMagazineUsage();
		if (!empty($instanceName)){
			$magazineUsage->instance = $instanceName;
		}
		if ($month != null) {
			$magazineUsage->month = $month;
		}
		if ($year != null) {
			$magazineUsage->year = $year;
		}
		$magazineUsage->selectAdd(null);
		$magazineUsage->selectAdd('COUNT(id) as recordsUsed');
		$magazineUsage->selectAdd('SUM(timesCheckedOut) as totalCheckouts');
		$magazineUsage->find(true);

		/** @noinspection PhpUndefinedFieldInspection */
		return [
			$usage->recordsUsed,
			(($usage->totalCheckouts != null) ? $usage->totalCheckouts : 0),
			(($usage->totalHolds != null) ? $usage->totalHolds : 0),
			$magazineUsage->recordsUsed,
			(($magazineUsage->totalCheckouts != null) ? $magazineUsage->totalCheckouts : 0),
		];
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#rbdigital', 'RBdigital');
		$breadcrumbs[] = new Breadcrumb('/RBdigital/Dashboard', 'Usage Dashboard');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'rbdigital';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission(['View System Reports', 'View Dashboards']);
	}
}