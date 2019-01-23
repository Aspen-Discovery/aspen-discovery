<?php
/**
 * Information about the device for the session.
 *
 * @category VuFind-Plus
 * @author Mark Noble <mark@marmot.org>
 * Date: 4/12/13
 * Time: 11:47 AM
 */

require_once 'DB/DataObject.php';

class Analytics_Device extends DB_DataObject
{
	public $__table = 'analytics_device';                        // table name
	public $id;
	public $value;
}