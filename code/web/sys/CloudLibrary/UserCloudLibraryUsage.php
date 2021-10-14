<?php


class UserCloudLibraryUsage extends DataObject
{
	public $__table = 'user_cloud_library_usage';
	public $id;
	public $instance;
	public $userId;
	public $year;
	public $month;
	public $usageCount; //Number of holds/clicks
}