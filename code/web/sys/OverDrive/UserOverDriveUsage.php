<?php


class UserOverDriveUsage extends DataObject
{
	public $__table = 'user_overdrive_usage';
	public $id;
	public $instance;
	public $userId;
	public $year;
	public $month;
	public $usageCount; //Number of holds/checkouts
}