<?php /** @noinspection PhpMissingFieldTypeInspection */

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

	function setSlowness(float $elapsedTime) : void {
		if ($elapsedTime < 0.5) {
			$this->__set('timesFast', ++$this->timesFast);
		} elseif ($elapsedTime < 1) {
			$this->__set('timesAcceptable', ++$this->timesAcceptable);
		} elseif ($elapsedTime < 2) {
			$this->__set('timesSlow', ++$this->timesSlow);
		} elseif ($elapsedTime < 4) {
			$this->__set('timesSlower', ++$this->timesSlower);
		} else {
			$this->__set('timesVerySlow', ++$this->timesVerySlow);
		}
	}

	public function okToExport(array $selectedFilters): bool {
		return true;
	}

	public function setMonth(string $date) : void {
		$this->__set('month', $date);
	}

	public function setYear(string $date) : void {
		$this->__set('year', $date);
	}

	public function setModule(?string $module) : void {
		$this->__set('module', $module);
	}

	public function setAction(?string $action) : void {
		$this->__set('action', $action);
	}

	public function getModule() : string {
		return $this->module;
	}

	public function getAction() : string {
		return $this->action;
	}

	public function getTimesFast() : int {
		return $this->timesFast ?? 0;
	}

	public function getTimesAcceptable() : int {
		return $this->timesAcceptable ?? 0;
	}

	public function getTimesSlow() : int {
		return $this->timesSlow ?? 0;
	}

	public function getTimesSlower() : int {
		return $this->timesSlower ?? 0;
	}

	public function getTimesVerySlow() : int {
		return $this->timesVerySlow ?? 0;
	}

}