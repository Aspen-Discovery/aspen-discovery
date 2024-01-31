<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class SummonRecordUsage extends DataObject {
	public $__table = 'summon_usage';
	public $id;
	public $instance;
	public $ebscoId;
	public $year;
	public $month;
	public $timesViewedInSearch;
	public $timesUsed;

	public function getUniquenessFields(): array {
		return [
			'instance',
			'summonId',
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