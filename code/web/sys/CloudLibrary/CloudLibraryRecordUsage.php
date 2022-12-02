<?php


class CloudLibraryRecordUsage extends DataObject {
	public $__table = 'cloud_library_record_usage';
	public $id;
	public $instance;
	public $cloudLibraryId;
	public $year;
	public $month;
	public $timesHeld;
	public $timesCheckedOut;

	public function getUniquenessFields(): array {
		return [
			'instance',
			'cloudLibraryId',
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