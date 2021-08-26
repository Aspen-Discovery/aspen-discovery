<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
class AspenUsage extends DataObject
{
	public $__table = 'aspen_usage';
	public $id;
	public $instance;
	public $year;
	public $month;
	public $pageViews;
	public $pageViewsByBots;
	public $pageViewsByAuthenticatedUsers;
	public $pagesWithErrors;
	public $sessionsStarted;
	public $ajaxRequests;
	public $coverViews;
	public $genealogySearches;
	public $groupedWorkSearches;
	public $openArchivesSearches;
	public $userListSearches;
	public $websiteSearches;
	public $eventsSearches;
	public $ebscoEdsSearches;
	public $blockedRequests;
	public $blockedApiRequests;

	public function getNumericColumnNames() : array
	{
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
			'eventsSearches'
		];
	}
}