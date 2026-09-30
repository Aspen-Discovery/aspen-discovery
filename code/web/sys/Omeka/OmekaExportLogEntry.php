<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/BaseLogEntry.php';

class OmekaExportLogEntry extends BaseLogEntry {
	public $__table = 'omeka_export_log';
	public $id;
	public $settingId;
	public $notes;
	public $numProducts;
	public $numErrors;
	public $numAdded;
	public $numDeleted;
	public $numUpdated;
	public $numSkipped;
	/** @noinspection PhpUnused */
	public $numInvalidRecords;
}
