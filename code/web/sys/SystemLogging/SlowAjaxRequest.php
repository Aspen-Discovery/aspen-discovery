<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
class SlowAjaxRequest extends DataObject
{
	public $__table = 'slow_ajax_request';
	public $id;
	public $year;
	public $month;
	public $module;
	public $action;
	public $method;
	public $timesSlow;
}