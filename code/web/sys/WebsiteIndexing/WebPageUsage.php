<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class WebPageUsage extends DataObject
{
	public $__table = 'website_page_usage';
	public $id;
	public $instance;
	public $webPageId;
	public $year;
	public $month;
	public $timesViewedInSearch;
	public $timesUsed;

	public function getUniquenessFields(): array
	{
		return ['instance','webPageId','year', 'month'];
	}

	public function okToExport(array $selectedFilters): bool
	{
		$okToExport = parent::okToExport($selectedFilters);
		if (in_array($this->instance, $selectedFilters['instances'])){
			$okToExport = true;
		}
		return $okToExport;
	}
}