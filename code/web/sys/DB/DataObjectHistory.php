<?php


class DataObjectHistory extends DataObject
{
	public $__table = 'object_history';
	public $id;
	public $objectType;
	public $objectId;
	public $propertyName;
	public $oldValue;
	public $newValue;
	public $changedBy;
	public $changeDate;
}