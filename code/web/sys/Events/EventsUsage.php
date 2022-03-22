<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class EventsUsage extends DataObject
{
	public $__table = 'events_usage';
	public $id;
	public $type;
	public $source;
	public $identifier;
	public $year;
	public $month;
	public $timesViewedInSearch;
	public $timesUsed;

	public function getUniquenessFields(): array
	{
		return ['type','source', 'identifier','year', 'month'];
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