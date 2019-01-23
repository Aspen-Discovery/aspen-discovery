<?php
/**
 * Stores information related to a hold that has been placed when the system is offline.
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/29/13
 * Time: 9:49 AM
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class OfflineCirculationEntry extends DB_DataObject{
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