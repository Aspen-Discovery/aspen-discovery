<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
class UserOpenArchivesUsage extends DataObject
{
	public $__table = 'user_open_archives_usage';
	public $id;
	public $instance;
	public $userId;
	public $openArchivesCollectionId;
	public $year;
	public $month;
	public $usageCount;
}