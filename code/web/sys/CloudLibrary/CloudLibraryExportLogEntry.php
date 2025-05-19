<?php

require_once ROOT_DIR . '/sys/BaseLogEntry.php';

class CloudLibraryExportLogEntry extends BaseLogEntry {
	public $__table = 'cloud_library_export_log';   // table name
	public $id;
	public $settingId;
	public $notes;
	public $numProducts;
	public $numAdded;
	public $numDeleted;
	public $numUpdated;
	public $numAvailabilityChanges;
	public $numMetadataChanges;
	public $numRegrouped;
	public $numInvalidRecords;
}
