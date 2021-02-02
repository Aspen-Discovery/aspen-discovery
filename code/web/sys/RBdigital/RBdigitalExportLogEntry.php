<?php

require_once ROOT_DIR . '/sys/BaseLogEntry.php';
class RBdigitalExportLogEntry extends BaseLogEntry
{
	public $__table = 'rbdigital_export_log';   // table name
	public $id;
	public $settingId;
	public $lastUpdate;
	public $notes;
    public $numProducts;
    public $numErrors;
    public $numAdded;
    public $numDeleted;
    public $numUpdated;
    public $numAvailabilityChanges;
    public $numMetadataChanges;

}
