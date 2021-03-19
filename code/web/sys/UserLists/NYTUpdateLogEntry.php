<?php
require_once ROOT_DIR . '/sys/BaseLogEntry.php';

class NYTUpdateLogEntry extends BaseLogEntry
{
	public $__table = 'nyt_update_log';   // table name
	public $id;
	public $lastUpdate;
	public $notes;
	public $numLists;
	public $numAdded;
	public $numUpdated;
}