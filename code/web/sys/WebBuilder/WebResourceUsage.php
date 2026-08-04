<?php /** @noinspection PhpMissingFieldTypeInspection */


class WebResourceUsage extends DataObject {
	public $__table = 'web_builder_resource_usage';
	public $id;
	public $instance;
	public $year;
	public $month;
	public $resourceName;
	public $pageViews;
	public $pageViewsByAuthenticatedUsers;
	public $pageViewsInLibrary;
	public $pageViewsFromPlacard;

	public function getUniquenessFields(): array {
		return [
			'resourceName',
			'year',
			'month',
		];
	}

	public function getNumericColumnNames(): array {
		return [
			'pageViews',
			'pageViewsByAuthenticatedUsers',
			'pageViewsInLibrary',
			'pageViewsFromPlacard'
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