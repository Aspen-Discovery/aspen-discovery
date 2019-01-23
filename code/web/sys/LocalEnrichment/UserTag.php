<?php
/**
 * Tags that have been added to works
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 3/20/14
 * Time: 10:47 AM
 */

class UserTag extends DB_DataObject {
	public $__table = 'user_tags';                            // table name
	public $id;
	public $tag;
	public $groupedRecordPermanentId;
	public $userId;
	public $dateTagged;

	//A count of the number of times the tag has been added to the work
	public $cnt;
	public $userAddedThis;
}