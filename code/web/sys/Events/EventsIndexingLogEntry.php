<?php


class EventsIndexingLogEntry extends DataObject {
	public $__table = 'events_indexing_log';
	public $id;
	public $startTime;
	public $endTime;
	public $lastUpdate;
	public $name;
	public $notes;
	public $numEvents;
	public $numErrors;
	public $numAdded;
	public $numDeleted;
	public $numUpdated;

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