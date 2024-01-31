<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class AspenUsage extends DataObject {
	public $__table = 'aspen_usage';
	protected $id;
	protected $instance;
	protected $year;
	protected $month;
	protected $pageViews;
	protected $pageViewsByBots;
	protected $pageViewsByAuthenticatedUsers;
	protected $pagesWithErrors;
	protected $sessionsStarted;
	protected $ajaxRequests;
	protected $coverViews;
	protected $genealogySearches;
	protected $groupedWorkSearches;
	protected $openArchivesSearches;
	protected $userListSearches;
	protected $websiteSearches;
	protected $eventsSearches;
	protected $ebscoEdsSearches;
	protected $ebscohostSearches;
	protected $blockedRequests;
	protected $blockedApiRequests;
	protected $timedOutSearches;
	protected $timedOutSearchesWithHighLoad;
	protected $searchesWithErrors;
	protected $emailsSent;
	protected $emailsFailed;

	public function getUniquenessFields(): array {
		return [
			'instance',
			'year',
			'month',
		];
	}

	public function getNumericColumnNames(): array {
		return [
			'pageViews',
			'pageViewsByBots',
			'pageViewsByAuthenticatedUsers',
			'pagesWithErrors',
			'slowPages',
			'ajaxRequests',
			'slowAjaxRequests',
			'coverViews',
			'genealogySearches',
			'groupedWorkSearches',
			'openArchivesSearches',
			'userListSearches',
			'websiteSearches',
			'eventsSearches',
			'timedOutSearches',
			'timedOutSearchesWithHighLoad',
			'searchesWithErrors',
			'ebscohostSearches',
			'emailsSent',
			'emailsFailed'
		];
	}

	public function okToExport(array $selectedFilters): bool {
		$okToExport = parent::okToExport($selectedFilters);
		if (in_array($this->instance, $selectedFilters['instances'])) {
			$okToExport = true;
		}
		return $okToExport;
	}

	public function getAspenUsageStats($instanceName, $month, $year) {
		$usage = new AspenUsage();
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
		$usage->selectAdd('SUM(pageViews) as totalViews');
		$usage->selectAdd('SUM(pageViewsByBots) as totalPageViewsByBots');
		$usage->selectAdd('SUM(pageViewsByAuthenticatedUsers) as totalPageViewsByAuthenticatedUsers');
		$usage->selectAdd('SUM(sessionsStarted) as totalSessionsStarted');
		$usage->selectAdd('SUM(coverViews) as totalCovers');
		$usage->selectAdd('SUM(pagesWithErrors) as totalErrors');
		$usage->selectAdd('SUM(ajaxRequests) as totalAsyncRequests');
		$usage->selectAdd('SUM(genealogySearches) as totalGenealogySearches');
		$usage->selectAdd('SUM(groupedWorkSearches) as totalGroupedWorkSearches');
		$usage->selectAdd('SUM(openArchivesSearches) as totalOpenArchivesSearches');
		$usage->selectAdd('SUM(userListSearches) as totalUserListSearches');
		$usage->selectAdd('SUM(websiteSearches) as totalWebsiteSearches');
		$usage->selectAdd('SUM(eventsSearches) as totalEventsSearches');
		$usage->selectAdd('SUM(ebscoEdsSearches) as totalEbscoEdsSearches');
		$usage->selectAdd('SUM(ebscohostSearches) as totalEbscohostSearches');
		$usage->selectAdd('SUM(blockedRequests) as totalBlockedRequests');
		$usage->selectAdd('SUM(blockedApiRequests) as totalBlockedApiRequests');
		$usage->selectAdd('SUM(timedOutSearches) as totalTimedOutSearches');
		$usage->selectAdd('SUM(timedOutSearchesWithHighLoad) as totalTimedOutSearchesWithHighLoad');
		$usage->selectAdd('SUM(searchesWithErrors) as totalSearchesWithErrors');
		$usage->selectAdd('SUM(emailsSent) as totalEmailsSent');
		$usage->selectAdd('SUM(emailsFailed) as totalFailedEmails');

		$usage->find(true);

		return $usage;
	}

	public function getInstance() {
		return $this->instance;
	}

	public function incEmailsSent() {
		$this->__set('emailsSent', $this->emailsSent+1);
	}

	public function incEmailsFailed() {
		$this->__set('emailsFailed', $this->emailsFailed+1);
	}
}