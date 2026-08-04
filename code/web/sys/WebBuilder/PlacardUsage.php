<?php
require_once ROOT_DIR . '/sys/DB/DataObject.php';

class PlacardUsage extends DataObject {
	public $__table = 'placard_usage';
	public $id;
	public $year;
	public $month;
	public $instance;
	public $placardName;
	public $timesShown;
	public $pageViews;
	public $pageViewsByAuthenticatedUsers;
	public $pageViewsInLibrary;

	function getUniquenessFields(): array {
		return ['year', 'month', 'instance', 'placardName'];
	}

	public function getNumericColumnNames(): array {
		return [
			'pageViews',
			'pageViewsByAuthenticatedUsers',
			'pageViewsInLibrary',
			'timesShown'
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
