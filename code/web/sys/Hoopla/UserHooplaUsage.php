<?php


class UserHooplaUsage extends DataObject
{
	public $__table = 'user_hoopla_usage';
	public $id;
	public $instance;
	public $userId;
	public $year;
	public $month;
	public $usageCount; //Number of holds/clicks to online for sideloads
}