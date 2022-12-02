<?php


class HooplaRecordUsage extends DataObject {
	public $__table = 'hoopla_record_usage';
	public $id;
	public $instance;
	public $hooplaId;
	public $year;
	public $month;
	public $timesHeld;
	public $timesCheckedOut;

	public function getUniquenessFields(): array {
		return [
			'instance',
			'hooplaId',
			'year',
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