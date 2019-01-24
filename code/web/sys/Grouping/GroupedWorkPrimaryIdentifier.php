<?php
/**
 * The primary identifier for a particular record in the database.
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 12/6/13
 * Time: 9:51 AM
 */

class GroupedWorkPrimaryIdentifier extends DataObject{
	public $__table = 'grouped_work_primary_identifiers';    // table name

	public $id;
	public $grouped_work_id;
	public $type;
	public $identifier;
}