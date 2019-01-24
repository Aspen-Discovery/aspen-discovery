<?php
/**
 * Information about the patron type who initiated the session takes place.
 *
 * @category VuFind-Plus
 * @author Mark Noble <mark@marmot.org>
 * Date: 4/12/13
 * Time: 11:47 AM
 */

require_once ROOT_DIR .'/sys/DB/DataObject.php';

class Analytics_PatronType extends DataObject
{
	public $__table = 'analytics_patron_type';                        // table name
	public $id;
	public $value;
}