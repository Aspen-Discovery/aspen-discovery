<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class OpenArchivesRecordUsage extends DataObject {
	public $__table = 'open_archives_record_usage';
	public $id;
	public $instance;
	public $openArchivesRecordId;
	public $year;
	public $month;
	public $timesViewedInSearch;
	public $timesUsed;

	public function getUniquenessFields(): array {
		return [
			'instance',
			'openArchivesRecordId',
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