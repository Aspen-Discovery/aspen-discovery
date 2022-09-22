<?php

class RecordParent extends DataObject
{
	public $__table = 'record_parents';
	public $id;
	public $childRecordId;
	public $parentRecordId;
}