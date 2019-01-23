<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class SierraExportLogEntry extends DB_DataObject
{
	public $__table = 'sierra_api_export_log';   // table name
	public $id;
	public $startTime;
	public $lastUpdate;
	public $endTime;
	public $numRecordsToProcess;
	public $numRecordsProcessed;
	public $numErrors;
	public $numRemainingRecords;
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
