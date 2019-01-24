<?php
/**
 * Contains information about a merged record
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 8/23/13
 * Time: 9:57 AM
 */

class MergedRecord extends DataObject{
	public $__table = 'merged_records';   // table name
    public $id;
	public $original_record;
	public $new_record;

}