<?php

require_once ROOT_DIR . '/sys/BaseLogEntry.php';

class OpenArchivesExportLogEntry extends BaseLogEntry {
	public $__table = 'open_archives_export_log';   // table name
	public $id;
	public $collectionName;
	public $lastUpdate;
	public $notes;
	public $numRecords;
	public $numErrors;
	public $numAdded;
	public $numDeleted;
	public $numUpdated;
	public $numSkipped;

}
