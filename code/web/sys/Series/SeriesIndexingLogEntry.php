<?php
require_once ROOT_DIR . '/sys/BaseLogEntry.php';

class SeriesIndexingLogEntry extends BaseLogEntry {
	public $__table = 'series_indexing_log';   // table name
	public $id;
	public $notes;
	public $numSeries;
	public $numAdded;
	public $numDeleted;
	public $numUpdated;
	public $numSkipped;
}