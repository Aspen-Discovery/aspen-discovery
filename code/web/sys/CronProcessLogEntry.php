<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class CronProcessLogEntry extends DB_DataObject
{
	public $__table = 'cron_process_log';   // table name
	public $id;
	public $cronId;
	public $processName;
	public $startTime;
	public $lastUpdate;
	public $endTime;
	public $numErrors;
	public $numUpdated;
	public $notes;

	function keys() {
		return array('id');
	}
	
	function getElapsedTime(){
		if (!isset($this->endTime) || is_null($this->endTime)){
			return "";
		}else{
			$elapsedTimeMin = ceil(($this->endTime - $this->startTime) / 60);
			if ($elapsedTimeMin < 60){
				return $elapsedTimeMin . " min";
			}else{
				$hours = floor($elapsedTimeMin / 60);
				$minutes = $elapsedTimeMin - (60 * $hours);
				return "$hours hours, $minutes min" ;
			}
		}
	}
}
