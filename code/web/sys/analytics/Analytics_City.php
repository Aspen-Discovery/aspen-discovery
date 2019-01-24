<?php
/**
 * Information about the city where a session takes place.
 *
 * @category VuFind-Plus
 * @author Mark Noble <mark@marmot.org>
 * Date: 4/12/13
 * Time: 11:47 AM
 */

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class Analytics_City extends DataObject
{
	public $__table = 'analytics_city';                        // table name
	public $id;
	public $value;
}