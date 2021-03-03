<?php


class RBdigitalRecordUsage extends DataObject
{
	public $__table = 'rbdigital_record_usage';
	public $id;
	public $instance;
	public $rbdigitalId;
	public $year;
	public $month;
	public $timesHeld;
	public $timesCheckedOut;
}