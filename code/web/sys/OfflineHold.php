<?php
/**
 * Stores information related to a hold that has been placed when the system is offline.
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/29/13
 * Time: 9:49 AM
 */
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