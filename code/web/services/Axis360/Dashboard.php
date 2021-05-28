<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Dashboard.php';
require_once ROOT_DIR . '/sys/Axis360/UserAxis360Usage.php';
require_once ROOT_DIR . '/sys/Axis360/Axis360RecordUsage.php';
require_once ROOT_DIR . '/sys/Axis360/Axis360Stats.php';

class Axis360_Dashboard extends Admin_Dashboard
{
	function launch()
	{
		global $interface;

		$instanceName = $this->loadInstanceInformation('Axis360Stats');
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

		$statsThisMonth = $this->getStats($instanceName, $this->thisMonth, $this->thisYear);
		$interface->assign('statsThisMonth', $statsThisMonth);
		$statsLastMonth = $this->getStats($instanceName, $this->lastMonth, $this->lastMonthYear);
		$interface->assign('statsLastMonth', $statsLastMonth);
		$statsThisYear = $this->getStats($instanceName, null, $this->thisYear);
		$interface->assign('statsThisYear', $statsThisYear);
		$statsLastYear = $this->getStats($instanceName, null, $this->lastYear);
		$interface->assign('statsLastYear', $statsLastYear);
		$statsAllTime = $this->getStats($instanceName, null, null);
		$interface->assign('statsAllTime', $statsAllTime);

		list($activeRecordsThisMonth, $loansThisMonth, $holdsThisMonth) = $this->getRecordStats($instanceName, $this->thisMonth, $this->thisYear);
		$interface->assign('activeRecordsThisMonth', $activeRecordsThisMonth);
		$interface->assign('loansThisMonth', $loansThisMonth);
		$interface->assign('holdsThisMonth', $holdsThisMonth);
		list($activeRecordsLastMonth, $loansLastMonth, $holdsLastMonth) = $this->getRecordStats($instanceName, $this->lastMonth, $this->lastMonthYear);
		$interface->assign('activeRecordsLastMonth', $activeRecordsLastMonth);
		$interface->assign('loansLastMonth', $loansLastMonth);
		$interface->assign('holdsLastMonth', $holdsLastMonth);
		list($activeRecordsThisYear, $loansThisYear, $holdsThisYear) = $this->getRecordStats($instanceName, null, $this->thisYear);
		$interface->assign('activeRecordsThisYear', $activeRecordsThisYear);
		$interface->assign('loansThisYear', $loansThisYear);
		$interface->assign('holdsThisYear', $holdsThisYear);
		list($activeRecordsLastYear, $loansLastYear, $holdsLastYear) = $this->getRecordStats($instanceName, null, $this->lastYear);
		$interface->assign('activeRecordsLastYear', $activeRecordsLastYear);
		$interface->assign('loansLastYear', $loansLastYear);
		$interface->assign('holdsLastYear', $holdsLastYear);
		list($activeRecordsAllTime, $loansAllTime, $holdsAllTime) = $this->getRecordStats($instanceName, null, null);
		$interface->assign('activeRecordsAllTime', $activeRecordsAllTime);
		$interface->assign('loansAllTime', $loansAllTime);
		$interface->assign('holdsAllTime', $holdsAllTime);

		$this->display('dashboard.tpl', 'Axis 360 Dashboard');
	}

	/**
	 * @param string|null $instanceName
	 * @param string|null $month
	 * @param string|null $year
	 * @return int
	 */
	public function getUserStats($instanceName, $month, $year): int
	{
		$userUsage = new UserAxis360Usage();
		if (!empty($instanceName)){
			$userUsage->instance = $instanceName;
		}
		if ($month != null) {
			$userUsage->month = $month;
		}
		if ($year != null) {
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
		$usage = new Axis360RecordUsage();
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
		if (!empty($instanceName)){
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

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#axis360', 'Axis 360');
		$breadcrumbs[] = new Breadcrumb('/Axis360/Dashboard', 'Usage Dashboard');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'axis360';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission(['View System Reports', 'View Dashboards']);
	}
}