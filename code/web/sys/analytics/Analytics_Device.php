<?php
/**
 * Information about the device for the session.
 *
 * @category VuFind-Plus
 * @author Mark Noble <mark@marmot.org>
 * Date: 4/12/13
 * Time: 11:47 AM
 */

require_once ROOT_DIR .'/sys/DB/DataObject.php';

class Analytics_Device extends DataObject
{
	public $__table = 'analytics_device';                        // table name
	public $id;
	public $value;
}