<?php


class APIUsage extends DataObject
{
	public $__table = 'api_usage';
	public $id;
	public $instance;
	public $year;
	public $month;
	public $module;
	public $method;
	public $numCalls;

	static function incrementStat($module, $method){
		try {
			$apiUsage = new APIUsage();
			$apiUsage->year = date('Y');
			$apiUsage->month = date('n');
			$apiUsage->instance = $_SERVER['SERVER_NAME'];
			$apiUsage->module = $module;
			$apiUsage->method = $method;
			if ($apiUsage->find(true)) {
				$apiUsage->numCalls++;
				$apiUsage->update();
			} else {
				$apiUsage->numCalls = 1;
				$apiUsage->insert();
			}
		}catch (PDOException $e){
			//This happens if the table has not been created, ignore it
		}
	}
}