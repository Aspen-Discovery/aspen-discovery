<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class ReindexLogEntry extends BaseLogEntry
{
	public $__table = 'reindex_log';   // table name
	public $id;
	public $lastUpdate;
	public $notes;
	public $numWorksProcessed;
	public $numErrors;
	public $numInvalidRecords;
}
