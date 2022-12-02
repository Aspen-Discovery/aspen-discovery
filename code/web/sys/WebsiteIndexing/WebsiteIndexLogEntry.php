<?php

require_once ROOT_DIR . '/sys/BaseLogEntry.php';

class WebsiteIndexLogEntry extends BaseLogEntry {
	public $__table = 'website_index_log';   // table name
	public $id;
	public $websiteName;
	public $lastUpdate;
	public $notes;
	public $numPages;
	public $numErrors;
	public $numAdded;
	public $numDeleted;
	public $numUpdated;
}
