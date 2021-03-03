<?php


class UserRBdigitalUsage extends DataObject
{
	public $__table = 'user_rbdigital_usage';
	public $id;
	public $instance;
	public $userId;
	public $year;
	public $month;
	public $usageCount; //Number of holds/clicks
}