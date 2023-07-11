<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class SlowPage extends DataObject {
	public $__table = 'slow_page';
	protected $id;
	protected $year;
	protected $month;
	protected $module;
	protected $action;
	protected $timesFast; //Less than .5 seconds
	protected $timesAcceptable; //.5 to 1 second
	protected $timesSlow; //1 second to 2 second
	protected $timesSlower; //2 second to 4 second
	protected $timesVerySlow; //4+ seconds

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
			$this->__set('timesFast', $this->timesFast++);
		} elseif ($elapsedTime < 1) {
			$this->__set('timesAcceptable', $this->timesAcceptable++);
		} elseif ($elapsedTime < 2) {
			$this->__set('timesSlow', $this->timesSlow++);
		} elseif ($elapsedTime < 4) {
			$this->__set('timesSlower', $this->timesSlower++);
		} else {
			$this->__set('timesVerySlow', $this->timesVerySlow++);
		}
	}

	public function okToExport(array $selectedFilters): bool {
		return true;
	}
}