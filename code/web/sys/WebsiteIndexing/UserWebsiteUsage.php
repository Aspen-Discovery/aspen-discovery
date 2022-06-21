<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class UserWebsiteUsage extends DataObject
{
	public $__table = 'user_website_usage';
	public $id;
	public $instance;
	public $userId;
	public $websiteId;
	public $year;
	public $month;
	public $usageCount;
}