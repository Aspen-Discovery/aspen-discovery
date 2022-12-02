<?php

require_once ROOT_DIR . '/sys/BaseLogEntry.php';

class SideLoadLogEntry extends BaseLogEntry {
	public $__table = 'sideload_log';   // table name
	public $id;
	public $lastUpdate;
	public $notes;
	public $numSideLoadsUpdated;
	public $sideLoadsUpdated;
	public $numProducts;
	public $numErrors;
	public $numAdded;
	public $numDeleted;
	public $numUpdated;
	public $numSkipped;

}
