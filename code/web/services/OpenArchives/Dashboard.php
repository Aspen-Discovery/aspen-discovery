<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/OpenArchives/OpenArchivesCollection.php';
require_once ROOT_DIR . '/sys/OpenArchives/OpenArchivesRecord.php';
require_once ROOT_DIR . '/sys/OpenArchives/UserOpenArchivesUsage.php';
require_once ROOT_DIR . '/sys/OpenArchives/OpenArchivesRecordUsage.php';

class OpenArchives_Dashboard extends Admin_Admin
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
		$collection = new OpenArchivesCollection();
		$collectionsToGetStatsFor = [];
		$collection->orderBy('name ASC');
		$collection->find();
		while ($collection->fetch()) {
			$collectionsToGetStatsFor[$collection->id] = $collection->name;
		}

		$activeUsersThisMonth = $this->getUserStats($thisMonth, $thisYear, $collectionsToGetStatsFor);
		$interface->assign('activeUsersThisMonth', $activeUsersThisMonth);
		$activeUsersLastMonth = $this->getUserStats($lastMonth, $lastMonthYear, $collectionsToGetStatsFor);
		$interface->assign('activeUsersLastMonth', $activeUsersLastMonth);
		$activeUsersThisYear = $this->getUserStats(null, $thisYear, $collectionsToGetStatsFor);
		$interface->assign('activeUsersThisYear', $activeUsersThisYear);
		$activeUsersLastYear = $this->getUserStats(null, $lastYear, $collectionsToGetStatsFor);
		$interface->assign('activeUsersLastYear', $activeUsersLastYear);
		$activeUsersAllTime = $this->getUserStats(null, null, $collectionsToGetStatsFor);
		$interface->assign('activeUsersAllTime', $activeUsersAllTime);

		$activeRecordsThisMonth = $this->getRecordStats($thisMonth, $thisYear, $collectionsToGetStatsFor);
		$interface->assign('activeRecordsThisMonth', $activeRecordsThisMonth);
		$activeRecordsLastMonth = $this->getRecordStats($lastMonth, $lastMonthYear, $collectionsToGetStatsFor);
		$interface->assign('activeRecordsLastMonth', $activeRecordsLastMonth);
		$activeRecordsThisYear = $this->getRecordStats(null, $thisYear, $collectionsToGetStatsFor);
		$interface->assign('activeRecordsThisYear', $activeRecordsThisYear);
		$activeRecordsLastYear = $this->getRecordStats(null, $lastYear, $collectionsToGetStatsFor);
		$interface->assign('activeRecordsLastYear', $activeRecordsLastYear);
		$activeRecordsAllTime = $this->getRecordStats(null, null, $collectionsToGetStatsFor);
		$interface->assign('activeRecordsAllTime', $activeRecordsAllTime);

		if (count($collectionsToGetStatsFor) > 1) {
			$collectionsToGetStatsFor = ['-1' => 'All Collections'] + $collectionsToGetStatsFor;
		}
		$interface->assign('collections', $collectionsToGetStatsFor);

		$this->display('dashboard.tpl', 'OpenArchives Dashboard');
	}

	function getAllowableRoles()
	{
		return array('opacAdmin', 'libraryAdmin');
	}

	/**
	 * @param string|null $month
	 * @param string|null $year
	 * @param int[] $collectionsToGetStatsFor
	 * @return int[]
	 */
	public function getUserStats($month, $year, $collectionsToGetStatsFor): array
	{
		$userUsage = new UserOpenArchivesUsage();
		if ($month != null) {
			$userUsage->month = $month;
		}
		if ($year != null) {
			$userUsage->year = $year;
		}
		$userUsage->groupBy('openArchivesCollectionId');
		$userUsage->selectAdd();
		$userUsage->selectAdd('openArchivesCollectionId');
		$userUsage->selectAdd('COUNT(id) as numUsers');
		$userUsage->find();
		$usageStats = [];
		$usageStats[-1] = 0;
		foreach ($collectionsToGetStatsFor as $collectionId => $collectionName) {
			$usageStats[$collectionId] = 0;
		}
		while ($userUsage->fetch()) {
			/** @noinspection PhpUndefinedFieldInspection */
			$usageStats[$userUsage->openArchivesCollectionId] = $userUsage->numUsers;
			/** @noinspection PhpUndefinedFieldInspection */
			$usageStats[-1] += $userUsage->numUsers;
		}
		return $usageStats;
	}

	/**
	 * @param string|null $month
	 * @param string|null $year
	 * @param int[] $collectionsToGetStatsFor
	 * @return int[]
	 */
	public function getRecordStats($month, $year, $collectionsToGetStatsFor): array
	{
		$usage = new OpenArchivesRecordUsage();
		$recordInfo = new OpenArchivesRecord();
		$usage->joinAdd($recordInfo, 'INNER', 'record', 'openArchivesRecordId', 'id');
		if ($month != null) {
			$usage->month = $month;
		}
		if ($year != null) {
			$usage->year = $year;
		}
		$usage->groupBy('sourceCollection');
		$usage->selectAdd(null);
		$usage->selectAdd('record.sourceCollection');

		$usage->selectAdd('SUM(IF(timesViewedInSearch>0,1,0)) as numRecordViewed');
		$usage->selectAdd('SUM(timesViewedInSearch) as numViews');
		$usage->selectAdd('SUM(IF(timesUsed>0,1,0)) as numRecordsUsed');
		$usage->selectAdd('SUM(timesUsed) as numClicks');
		$usage->find();

		$usageStats = [];
		$usageStats[-1] = [
			'numRecordViewed' => 0,
			'numViews' => 0,
			'numRecordsUsed' => 0,
			'numClicks' => 0
		];
		foreach ($collectionsToGetStatsFor as $collectionId => $collectionName) {
			$usageStats[$collectionId] = [
				'numRecordViewed' => 0,
				'numViews' => 0,
				'numRecordsUsed' => 0,
				'numClicks' => 0
			];
		}
		while ($usage->fetch()) {
			/** @noinspection PhpUndefinedFieldInspection */
			$usageStats[$usage->sourceCollection] = [
				'numRecordViewed' => $usage->numRecordViewed,
				'numViews' => $usage->numViews,
				'numRecordsUsed' => $usage->numRecordsUsed,
				'numClicks' => $usage->numClicks
			];
			/** @noinspection PhpUndefinedFieldInspection */
			$usageStats[-1]['numRecordViewed'] += $usage->numRecordViewed;
			/** @noinspection PhpUndefinedFieldInspection */
			$usageStats[-1]['numViews'] += $usage->numViews;
			/** @noinspection PhpUndefinedFieldInspection */
			$usageStats[-1]['numRecordsUsed'] += $usage->numRecordsUsed;
			/** @noinspection PhpUndefinedFieldInspection */
			$usageStats[-1]['numClicks'] += $usage->numClicks;
		}
		return $usageStats;
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#open_archives', 'Open Archives');
		$breadcrumbs[] = new Breadcrumb('/OpenArchives/Dashboard', 'Usage Dashboard');
		return $breadcrumbs;
	}
}