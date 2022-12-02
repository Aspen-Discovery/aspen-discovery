<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class CronProcessLogEntry extends DataObject {
	public $__table = 'cron_process_log';   // table name
	public $id;
	public $cronId;
	public $processName;
	public $startTime;
	public $lastUpdate;
	public $endTime;
	public $numErrors;
	public $numUpdated;
	public $numSkipped;
	public $notes;

	function getElapsedTime() {
		if (!isset($this->endTime) || is_null($this->endTime)) {
			return "";
		} else {
			$elapsedTimeMin = ceil(($this->endTime - $this->startTime) / 60);
			if ($elapsedTimeMin < 60) {
				return $elapsedTimeMin . " min";
			} else {
				$hours = floor($elapsedTimeMin / 60);
				$minutes = $elapsedTimeMin - (60 * $hours);
				return "$hours hours, $minutes min";
			}
		}
	}
}
