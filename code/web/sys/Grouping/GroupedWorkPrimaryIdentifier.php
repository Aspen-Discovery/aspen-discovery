<?php

class GroupedWorkPrimaryIdentifier extends DataObject {
	public $__table = 'grouped_work_primary_identifiers';    // table name

	public $id;
	public $grouped_work_id;
	public $type;
	public $identifier;
}