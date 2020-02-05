<?php

class GroupedWork extends DataObject {
	public $__table = 'grouped_work';    // table name
	public $id;
	public $permanent_id;
	public $full_title;
	public $author;
	public $grouping_category;
	public $date_updated;

	public function forceReindex()
	{
		require_once ROOT_DIR . '/sys/Indexing/GroupedWorkScheduledWorkIndex.php';
		$scheduledWork = new GroupedWorkScheduledWorkIndex();
		$scheduledWork->permanent_id = $this->permanent_id;
		$scheduledWork->indexAfter = time();
		$scheduledWork->processed = 0;
		$scheduledWork->insert();
	}
} 