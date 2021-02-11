<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Dashboard.php';
require_once ROOT_DIR . '/sys/OverDrive/UserOverDriveUsage.php';
require_once ROOT_DIR . '/sys/OverDrive/OverDriveRecordUsage.php';
require_once ROOT_DIR . '/sys/OverDrive/OverDriveStats.php';

class OverDrive_Dashboard extends Admin_Dashboard
{
	function launch()
	{
		global $interface;

		$instanceName = $this->loadInstanceInformation('UserOverDriveUsage');
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

		$this->display('dashboard.tpl', 'OverDrive Dashboard');
	}

	/**
	 * @param string|null $instanceName
	 * @param string|null $month
	 * @param string|null $year
	 * @return int
	 */
	public function getUserStats($instanceName, $month, $year): int
	{
		$userUsage = new UserOverDriveUsage();
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
		$usage = new OverDriveRecordUsage();
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

	/**
	 * @param string|null $instanceName
	 * @param string|null $month
	 * @param string|null $year
	 * @return OverDriveStats
	 */
	public function getStats($instanceName, $month, $year): OverDriveStats
	{
		$stats = new OverDriveStats();
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
		$stats->selectAdd('SUM(numFailedCheckouts) as numFailedCheckouts');
		$stats->selectAdd('SUM(numRenewals) as numRenewals');
		$stats->selectAdd('SUM(numEarlyReturns) as numEarlyReturns');
		$stats->selectAdd('SUM(numHoldsPlaced) as numHoldsPlaced');
		$stats->selectAdd('SUM(numFailedHolds) as numFailedHolds');
		$stats->selectAdd('SUM(numHoldsCancelled) as numHoldsCancelled');
		$stats->selectAdd('SUM(numHoldsFrozen) as numHoldsFrozen');
		$stats->selectAdd('SUM(numHoldsThawed) as numHoldsThawed');
		$stats->selectAdd('SUM(numDownloads) as numDownloads');
		$stats->selectAdd('SUM(numPreviews) as numPreviews');
		$stats->selectAdd('SUM(numOptionsUpdates) as numOptionsUpdates');
		$stats->selectAdd('SUM(numApiErrors) as numApiErrors');
		$stats->selectAdd('SUM(numConnectionFailures) as numConnectionFailures');

		if ($stats->find(true)){
			return $stats;
		}else{
			return new OverDriveStats();
		}
	}
}