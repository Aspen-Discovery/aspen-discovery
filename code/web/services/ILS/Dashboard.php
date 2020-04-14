<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/ILS/UserILSUsage.php';
require_once ROOT_DIR . '/sys/ILS/ILSRecordUsage.php';

class ILS_Dashboard extends Admin_Admin
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

		/** @var IndexingProfile[] $indexingProfiles */
		global $indexingProfiles;
		$profilesToGetStatsFor = [];
		foreach ($indexingProfiles as $indexingProfile) {
			$profilesToGetStatsFor[$indexingProfile->id] = $indexingProfile->name;
		}
		$interface->assign('profiles', $profilesToGetStatsFor);

		$activeUsersThisMonth = $this->getUserStats($thisMonth, $thisYear, $profilesToGetStatsFor);
		$interface->assign('activeUsersThisMonth', $activeUsersThisMonth);
		$activeUsersLastMonth = $this->getUserStats($lastMonth, $lastMonthYear, $profilesToGetStatsFor);
		$interface->assign('activeUsersLastMonth', $activeUsersLastMonth);
		$activeUsersThisYear = $this->getUserStats(null, $thisYear, $profilesToGetStatsFor);
		$interface->assign('activeUsersThisYear', $activeUsersThisYear);
		$activeUsersLastYear = $this->getUserStats(null, $lastYear, $profilesToGetStatsFor);
		$interface->assign('activeUsersLastYear', $activeUsersLastYear);
		$activeUsersAllTime = $this->getUserStats(null, null, $profilesToGetStatsFor);
		$interface->assign('activeUsersAllTime', $activeUsersAllTime);

		$activeRecordsThisMonth = $this->getRecordStats($thisMonth, $thisYear, $profilesToGetStatsFor);
		$interface->assign('activeRecordsThisMonth', $activeRecordsThisMonth);
		$activeRecordsLastMonth = $this->getRecordStats($lastMonth, $lastMonthYear, $profilesToGetStatsFor);
		$interface->assign('activeRecordsLastMonth', $activeRecordsLastMonth);
		$activeRecordsThisYear = $this->getRecordStats(null, $thisYear, $profilesToGetStatsFor);
		$interface->assign('activeRecordsThisYear', $activeRecordsThisYear);
		$activeRecordsLastYear = $this->getRecordStats(null, $lastYear, $profilesToGetStatsFor);
		$interface->assign('activeRecordsLastYear', $activeRecordsLastYear);
		$activeRecordsAllTime = $this->getRecordStats(null, null, $profilesToGetStatsFor);
		$interface->assign('activeRecordsAllTime', $activeRecordsAllTime);

		$selfRegistrationsThisMonth = $this->getSelfRegistrationStats($thisMonth, $thisYear, $profilesToGetStatsFor);
		$interface->assign('selfRegistrationsThisMonth', $selfRegistrationsThisMonth);
		$selfRegistrationsLastMonth = $this->getSelfRegistrationStats($lastMonth, $lastMonthYear, $profilesToGetStatsFor);
		$interface->assign('selfRegistrationsLastMonth', $selfRegistrationsLastMonth);
		$selfRegistrationsThisYear = $this->getSelfRegistrationStats(null, $thisYear, $profilesToGetStatsFor);
		$interface->assign('selfRegistrationsThisYear', $selfRegistrationsThisYear);
		$selfRegistrationsLastYear = $this->getSelfRegistrationStats(null, $lastYear, $profilesToGetStatsFor);
		$interface->assign('selfRegistrationsLastYear', $selfRegistrationsLastYear);
		$selfRegistrationsAllTime = $this->getSelfRegistrationStats(null, null, $profilesToGetStatsFor);
		$interface->assign('selfRegistrationsAllTime', $selfRegistrationsAllTime);

		$this->display('dashboard.tpl', 'ILS & Side Load Dashboard');
	}

	function getAllowableRoles()
	{
		return array('opacAdmin');
	}

	/**
	 * @param string|null $month
	 * @param string|null $year
	 * @param int[] $profilesToGetStatsFor
	 * @return int[]
	 */
	public function getUserStats($month, $year, $profilesToGetStatsFor): array
	{
		$userUsage = new UserILSUsage();
		if ($month != null) {
			$userUsage->month = $month;
		}
		if ($year != null) {
			$userUsage->year = $year;
		}
		$userUsage->groupBy('indexingProfileId');
		$userUsage->selectAdd();
		$userUsage->selectAdd('indexingProfileId');
		$userUsage->selectAdd('COUNT(id) as numUsers');
		$userUsage->find();
		$usageStats = [];
		foreach ($profilesToGetStatsFor as $id => $name) {
			$usageStats[$id] = 0;
		}
		while ($userUsage->fetch()) {
			/** @noinspection PhpUndefinedFieldInspection */
			$usageStats[$userUsage->indexingProfileId] = $userUsage->numUsers;
		}
		return $usageStats;
	}

	public function getSelfRegistrationStats($month, $year, $profilesToGetStatsFor): array
	{
		$userUsage = new UserILSUsage();
		if ($month != null) {
			$userUsage->month = $month;
		}
		if ($year != null) {
			$userUsage->year = $year;
		}
		$userUsage->userId = -1;
		$userUsage->groupBy('indexingProfileId');
		$userUsage->selectAdd();
		$userUsage->selectAdd('indexingProfileId');
		$userUsage->selectAdd('SUM(selfRegistrationCount) AS numSelfRegistrations');
		$userUsage->find();
		$usageStats = [];
		foreach ($profilesToGetStatsFor as $id => $name) {
			$usageStats[$id] = 0;
		}
		while ($userUsage->fetch()) {
			/** @noinspection PhpUndefinedFieldInspection */
			$usageStats[$userUsage->indexingProfileId] = $userUsage->numSelfRegistrations;
		}
		return $usageStats;
	}

	/**
	 * @param string|null $month
	 * @param string|null $year
	 * @param int[] $profilesToGetStatsFor
	 * @return int[]
	 */
	public function getRecordStats($month, $year, $profilesToGetStatsFor): array
	{
		$usage = new ILSRecordUsage();
		if ($month != null) {
			$usage->month = $month;
		}
		if ($year != null) {
			$usage->year = $year;
		}
		$usage->groupBy('indexingProfileId');
		$usage->selectAdd(null);
		$usage->selectAdd('indexingProfileId');

		$usage->selectAdd('COUNT(*) as numRecordViewed');
		$usage->selectAdd('SUM(IF(timesUsed>0,1,0)) as numRecordsUsed');
		$usage->find();

		$usageStats = [];
		foreach ($profilesToGetStatsFor as $id => $name) {
			$usageStats[$id] = [
				'numRecordViewed' => 0,
				'numRecordsUsed' => 0
			];
		}
		while ($usage->fetch()) {
			/** @noinspection PhpUndefinedFieldInspection */
			$usageStats[$usage->indexingProfileId] = [
				'numRecordViewed' => $usage->numRecordViewed,
				'numRecordsUsed' => $usage->numRecordsUsed
			];
		}
		return $usageStats;
	}

}