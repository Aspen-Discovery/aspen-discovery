<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class EbscohostRecordUsage extends DataObject {
	public $__table = 'ebscohost_usage';
	public $id;
	public $instance;
	public $ebscohostId;
	public $year;
	public $month;
	public $timesViewedInSearch;
	public $timesUsed;

	public function getUniquenessFields(): array {
		return [
			'instance',
			'ebscohostId',
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