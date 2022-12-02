<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class IlsHoldSummary extends DataObject {
	public $__table = 'ils_hold_summary';    // table name
	public $id;
	public $ilsId;
	public $numHolds;
} 