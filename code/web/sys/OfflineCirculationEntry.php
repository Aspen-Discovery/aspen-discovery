<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
class OfflineCirculationEntry extends DataObject{
	public $__table = 'offline_circulation';
	public $id;
	public $timeEntered;
	public $timeProcessed;
	public $itemBarcode;
	public $patronBarcode;
	public $patronId;
	public $login;
	public $loginPassword;
	public $initials;
	public $initialsPassword;
	public $type; //valid values - 'Check In', 'Check Out'
	public $status; //valid values - 'Not Processed', 'Hold Placed', 'Hold Failed'
	public $notes;
}