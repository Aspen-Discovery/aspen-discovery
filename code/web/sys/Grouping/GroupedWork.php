<?php
/**
 * Description goes here
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 12/6/13
 * Time: 9:50 AM
 */

class GroupedWork extends DB_DataObject {
	public $__table = 'grouped_work';    // table name
	public $id;
	public $permanent_id;
	public $full_title;
	public $author;
	public $grouping_category;
	public $date_updated;
} 