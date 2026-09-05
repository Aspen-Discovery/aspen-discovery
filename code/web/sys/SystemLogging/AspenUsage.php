<?php
/** @noinspection PhpUnused */
/** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/AbstractUsage.php';

class AspenUsage extends AbstractUsage {
	public $__table = 'aspen_usage';
	protected $id;
	protected $instance;
	protected $year;
	protected $month;
	protected $day;
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
	protected $galeSearches;
	protected $summonSearches;
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
			'day',
		];
	}

	public function getNumericColumnNames(): array {
		return [
			'year',
			'month',
			'day',
			'pageViews',
			'pageViewsByBots',
			'pageViewsByAuthenticatedUsers',
			'pagesWithErrors',
			'sessionsStarted',
			'ajaxRequests',
			'coverViews',
			'genealogySearches',
			'groupedWorkSearches',
			'openArchivesSearches',
			'userListSearches',
			'websiteSearches',
			'eventsSearches',
			'ebscoEdsSearches',
			'ebscohostSearches',
			'galeSearches',
			'summonSearches',
			'blockedRequests',
			'blockedApiRequests',
			'timedOutSearches',
			'timedOutSearchesWithHighLoad',
			'searchesWithErrors',
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

	public function getAspenUsageStats($instanceName, $month, $year) : AspenUsage {
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
		$usage->selectAdd('SUM(galeSearches) as totalGaleSearches');
		$usage->selectAdd('SUM(summonSearches) as totalSummonSearches');
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

	public function incEmailsSent() : bool {
		return $this->incrementField('emailsSent');
	}

	public function incEmailsFailed() : bool {
		return $this->incrementField('emailsFailed');
	}

	public function incGroupedWorkSearches() : bool {
		return $this->incrementField('groupedWorkSearches');
	}

	public function incTimedOutSearchesWithHighLoad() : bool {
		return $this->incrementField('timedOutSearchesWithHighLoad');
	}

	public function incTimedOutSearches() : bool {
		return $this->incrementField('timedOutSearches');
	}

	public function incSearchesWithErrors() : bool {
		return $this->incrementField('searchesWithErrors');
	}

	public function incPageViews() : bool {
		return $this->incrementField('pageViews');
	}

	public function incPageViewsByBots() : bool {
		return $this->incrementField('pageViewsByBots');
	}

	public function incPageViewsByAuthenticatedUsers() : bool {
		return $this->incrementField('pageViewsByAuthenticatedUsers');
	}

	public function incPagesWithErrors() : bool {
		return $this->incrementField('pagesWithErrors');
	}

	public function incSessionsStarted() : bool {
		return $this->incrementField('sessionsStarted');
	}

	public function incAjaxRequests() : bool {
		return $this->incrementField('ajaxRequests');
	}

	public function incCoverViews() : bool {
		return $this->incrementField('coverViews');
	}

	public function incGenealogySearches() : bool {
		return $this->incrementField('genealogySearches');
	}

	public function incOpenArchivesSearches() : bool {
		return $this->incrementField('openArchivesSearches');
	}

	public function incUserListSearches() : bool {
		return $this->incrementField('userListSearches');
	}

	public function incWebsiteSearches() : bool {
		return $this->incrementField('websiteSearches');
	}

	public function incEventsSearches() : bool {
		return $this->incrementField('eventsSearches');
	}

	public function incEbscoEdsSearches() : bool {
		return $this->incrementField('ebscoEdsSearches');
	}

	public function incEbscohostSearches() : bool {
		return $this->incrementField('ebscohostSearches');
	}

	public function incGaleSearches() : bool {
		return $this->incrementField('galeSearches');
	}

	public function incSummonSearches() : bool {
		return $this->incrementField('summonSearches');
	}

	public function incBlockedRequests() : bool {
		return $this->incrementField('blockedRequests');
	}

	public function incBlockedApiRequests() : bool {
		return $this->incrementField('blockedApiRequests');
	}

	private function incrementField(string $fieldName) : bool {
		if (!property_exists($this, $fieldName)) {
			return false;
		}
		$this->$fieldName++;
		try {
			if (empty($this->id)) {
				//insert catches PDO exceptions internally and returns false, a duplicate key error does not throw
				if ($this->insert() !== false) {
					return true;
				}
				//Another request created the row for today first, load it and increment atomically
				$today = new AspenUsage();
				$today->instance = $this->instance;
				$today->year = $this->year;
				$today->month = $this->month;
				$today->day = $this->day;
				if (!$today->find(true)) {
					return false;
				}
				$this->id = $today->id;
			}
			return $this->query("UPDATE aspen_usage SET $fieldName = $fieldName + 1 WHERE id = $this->id");
		} catch (Exception) {
			//Ignore this, the table has not been created yet
			return true;
		}
	}
}