<?php


class UserSideLoadUsage extends DataObject
{
	public $__table = 'user_sideload_usage';
	public $id;
	public $instance;
	public $userId;
	public $sideLoadId;
	public $year;
	public $month;
	public $usageCount; //Number of clicks
}