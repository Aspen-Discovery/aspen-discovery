<?php
/**
 * Table Definition for loading number of holds by ils id
 *
 * @category VuFind-Plus-2014 
 * @author Mark Noble <mark@marmot.org>
 * Date: 10/15/14
 * Time: 9:09 AM
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';
class IlsHoldSummary extends DB_DataObject{
	public $__table = 'ils_hold_summary';    // table name
	public $id;
	public $ilsId;
	public $numHolds;

	function keys() {
		return array('id');
	}
} 