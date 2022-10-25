<?php

require_once ROOT_DIR . '/sys/BaseLogEntry.php';

class OverDriveExtractLogEntry extends BaseLogEntry
{
	public $__table = 'overdrive_extract_log';   // table name
	public $id;
	public $settingId;
	public $lastUpdate;
	public $notes;
	public $numProducts;
	public $numErrors;
	public $numAdded;
	public $numDeleted;
	public $numUpdated;
	public $numSkipped;
	public $numAvailabilityChanges;
	public $numMetadataChanges;
	public $numInvalidRecords;
}
