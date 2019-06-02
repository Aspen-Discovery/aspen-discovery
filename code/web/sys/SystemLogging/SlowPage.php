<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
class SlowPage extends DataObject
{
	public $__table = 'slow_page';
	public $id;
	public $year;
	public $month;
	public $module;
	public $action;
	public $timesSlow;
}