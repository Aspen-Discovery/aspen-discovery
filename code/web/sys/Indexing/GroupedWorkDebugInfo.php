<?php

class GroupedWorkDebugInfo extends DataObject {
	public $__table = 'grouped_work_debug_info';
	public $id;
	public $permanent_id; //Grouped Work Permanent ID to be processed
	public $debugInfo; //Debugging information from the indexers
	public $debugTime; //When debugging information was last processed
	public $processed; //If the work has been processed
}