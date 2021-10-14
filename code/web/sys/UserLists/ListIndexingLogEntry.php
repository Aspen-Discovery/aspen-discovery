<?php
require_once ROOT_DIR . '/sys/BaseLogEntry.php';

class ListIndexingLogEntry extends BaseLogEntry
{
	public $__table = 'list_indexing_log';   // table name
	public $id;
	public $lastUpdate;
	public $notes;
	public $numLists;
	public $numAdded;
	public $numDeleted;
	public $numUpdated;
	public $numSkipped;
}