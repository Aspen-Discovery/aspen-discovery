<?php


class Axis360RecordUsage extends DataObject
{
	public $__table = 'axis360_record_usage';
	public $id;
	public $instance;
	public $axis360Id;
	public $year;
	public $month;
	public $timesHeld;
	public $timesCheckedOut;
}