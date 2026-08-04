<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/AbstractUsage.php';

class Axis360RecordUsage extends AbstractUsage {
	public $__table = 'axis360_record_usage';
	public $id;
	public $instance;
	public $axis360Id;
	public $year;
	public $month;
	public $day;
	public $timesHeld;
	public $timesCheckedOut;

	public function getUniquenessFields(): array {
		return [
			'instance',
			'axis360Id',
			'year',
			'day',
			'month',
		];
	}

	public function okToExport(array $selectedFilters): bool {
		$okToExport = parent::okToExport($selectedFilters);
		if (in_array($this->instance, $selectedFilters['instances'])) {
			$okToExport = true;
		}
		return $okToExport;
	}
}