<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Dashboard.php';
require_once ROOT_DIR . '/sys/BorrowBox/UserBorrowBoxUsage.php';
require_once ROOT_DIR . '/sys/BorrowBox/BorrowBoxRecordUsage.php';
require_once ROOT_DIR . '/sys/BorrowBox/BorrowBoxStats.php';

class BorrowBox_Dashboard extends Admin_Dashboard {
	function launch(): void {
		global $interface;

		$instanceName = $this->loadInstanceInformation('UserBorrowBoxUsage');
		$this->loadDates();

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

		[
			$activeRecordsThisMonth,
			$loansThisMonth,
			$holdsThisMonth,
		] = $this->getRecordStats($instanceName, $this->thisMonth, $this->thisYear);
		$interface->assign('activeRecordsThisMonth', $activeRecordsThisMonth);
		$interface->assign('loansThisMonth', $loansThisMonth);
		$interface->assign('holdsThisMonth', $holdsThisMonth);
		[
			$activeRecordsLastMonth,
			$loansLastMonth,
			$holdsLastMonth,
		] = $this->getRecordStats($instanceName, $this->lastMonth, $this->lastMonthYear);
		$interface->assign('activeRecordsLastMonth', $activeRecordsLastMonth);
		$interface->assign('loansLastMonth', $loansLastMonth);
		$interface->assign('holdsLastMonth', $holdsLastMonth);
		[
			$activeRecordsThisYear,
			$loansThisYear,
			$holdsThisYear,
		] = $this->getRecordStats($instanceName, null, $this->thisYear);
		$interface->assign('activeRecordsThisYear', $activeRecordsThisYear);
		$interface->assign('loansThisYear', $loansThisYear);
		$interface->assign('holdsThisYear', $holdsThisYear);
		[
			$activeRecordsLastYear,
			$loansLastYear,
			$holdsLastYear,
		] = $this->getRecordStats($instanceName, null, $this->lastYear);
		$interface->assign('activeRecordsLastYear', $activeRecordsLastYear);
		$interface->assign('loansLastYear', $loansLastYear);
		$interface->assign('holdsLastYear', $holdsLastYear);
		[
			$activeRecordsAllTime,
			$loansAllTime,
			$holdsAllTime,
		] = $this->getRecordStats($instanceName, null, null);
		$interface->assign('activeRecordsAllTime', $activeRecordsAllTime);
		$interface->assign('loansAllTime', $loansAllTime);
		$interface->assign('holdsAllTime', $holdsAllTime);

		$this->display('dashboard.tpl', 'BorrowBox Dashboard');
	}

	/**
	 * @param string|null $instanceName
	 * @param string|null $month
	 * @param string|null $year
	 * @return int
	 */
	public function getUserStats(?string $instanceName, ?string $month, ?string $year): int {
		$userUsage = new UserBorrowBoxUsage();
		if (!empty($instanceName)) {
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
	public function getRecordStats(?string $instanceName, ?string $month, ?string $year): array {
		$usage = new BorrowBoxRecordUsage();
		if (!empty($instanceName)) {
			$usage->instance = $instanceName;
		}
		if ($month != null) {
			$usage->month = $month;
		}
		if ($year != null) {
			$usage->year = $year;
		}
		$usage->selectAdd();
		$usage->selectAdd('COUNT(id) as recordsUsed');
		$usage->selectAdd('SUM(timesHeld) as totalHolds');
		$usage->selectAdd('SUM(timesCheckedOut) as totalCheckouts');
		$usage->find(true);

		/** @noinspection PhpUndefinedFieldInspection */
		return [
			$usage->recordsUsed,
			($usage->totalCheckouts == null ? 0 : $usage->totalCheckouts),
			($usage->totalHolds == null ? 0 : $usage->totalHolds),
		];
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#borrowbox', 'BorrowBox');
		$breadcrumbs[] = new Breadcrumb('/BorrowBox/Dashboard', 'Usage Dashboard');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'borrowbox';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'View System Reports',
			'View Dashboards',
		]);
	}

	/**
	 * @param string|null $instanceName
	 * @param string|null $month
	 * @param string|null $year
	 * @return BorrowBoxStats
	 */
	public function getStats(?string $instanceName, ?string $month, ?string $year): BorrowBoxStats {
		$stats = new BorrowBoxStats();
		if (!empty($instanceName)) {
			$stats->instance = $instanceName;
		}
		if ($month != null) {
			$stats->month = $month;
		}
		if ($year != null) {
			$stats->year = $year;
		}
		$stats->selectAdd();
		$stats->selectAdd('SUM(numCheckouts) as numCheckouts');
		$stats->selectAdd('SUM(numFailedCheckouts) as numFailedCheckouts');
		$stats->selectAdd('SUM(numRenewals) as numRenewals');
		$stats->selectAdd('SUM(numEarlyReturns) as numEarlyReturns');
		$stats->selectAdd('SUM(numHoldsPlaced) as numHoldsPlaced');
		$stats->selectAdd('SUM(numFailedHolds) as numFailedHolds');
		$stats->selectAdd('SUM(numHoldsCancelled) as numHoldsCancelled');
		$stats->selectAdd('SUM(numApiErrors) as numApiErrors');
		$stats->selectAdd('SUM(numConnectionFailures) as numConnectionFailures');

		if ($stats->find(true)) {
			return $stats;
		} else {
			return new BorrowBoxStats();
		}
	}
}
