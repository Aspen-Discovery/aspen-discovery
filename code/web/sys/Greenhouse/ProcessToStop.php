<?php

class ProcessToStop extends DataObject {
	public $__table = 'processes_to_stop';
	public $id;
	public $processId;
	public $processName;
	// 0 = no, 1 = yes, 2 = stopped outside aspen
	public $stopAttempted;
	public $stopResults;
	public $dateSet;

	public function getFormattedDateSet() {
		return date("Y-m-d H:i:s", $this->dateSet);
	}
}