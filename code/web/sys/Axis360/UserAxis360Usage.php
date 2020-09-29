<?php


class UserAxis360Usage extends DataObject
{
	public $__table = 'user_axis360_usage';
	public $id;
	public $instance;
	public $userId;
	public $recordId;
	public $year;
	public $month;
	public $usageCount; //Number of holds/clicks
}