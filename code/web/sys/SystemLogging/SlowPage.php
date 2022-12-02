<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class SlowPage extends DataObject {
	public $__table = 'slow_page';
	public $id;
	public $year;
	public $month;
	public $module;
	public $action;
	public $timesFast; //Less than .5 seconds
	public $timesAcceptable; //.5 to 1 second
	public $timesSlow; //1 second to 2 second
	public $timesSlower; //2 second to 4 second
	public $timesVerySlow; //4+ seconds

	public function getUniquenessFields(): array {
		return [
			'module',
			'action',
			'year',
			'month',
		];
	}

	function setSlowness(float $elapsedTime) {
		if ($elapsedTime < 0.5) {
			$this->timesFast++;
		} elseif ($elapsedTime < 1) {
			$this->timesAcceptable++;
		} elseif ($elapsedTime < 2) {
			$this->timesSlow++;
		} elseif ($elapsedTime < 4) {
			$this->timesSlower++;
		} else {
			$this->timesVerySlow++;
		}
	}

	public function okToExport(array $selectedFilters): bool {
		return true;
	}
}