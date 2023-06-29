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
		];
	}

	public function okToExport(array $selectedFilters): bool {
		$okToExport = parent::okToExport($selectedFilters);
		if (in_array($this->instance, $selectedFilters['instances'])) {
			$okToExport = true;
		}
		return $okToExport;
	}
}