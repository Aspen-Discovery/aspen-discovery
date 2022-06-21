<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
class OfflineHold extends DataObject{
	public $__table = 'offline_hold';
    public $id;
	public $timeEntered;
	public $bibId;
	public $patronBarcode;
	public $patronId;
	public $status; //valid values - 'Not Processed', 'Hold Placed', 'Hold Failed'
	public $notes;
}