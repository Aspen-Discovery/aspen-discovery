<?php

require_once ROOT_DIR . '/sys/BaseLogEntry.php';
class CloudLibraryExportLogEntry extends BaseLogEntry
{
	public $__table = 'cloud_library_export_log';   // table name
	public $id;
	public $startTime;
	public $lastUpdate;
	public $endTime;
	public $notes;
    public $numProducts;
    public $numAdded;
    public $numDeleted;
    public $numUpdated;
    public $numAvailabilityChanges;
    public $numMetadataChanges;

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
