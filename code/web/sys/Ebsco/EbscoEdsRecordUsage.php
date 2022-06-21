<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
class EbscoEdsRecordUsage extends DataObject
{
    public $__table = 'ebsco_eds_usage';
    public $id;
    public $instance;
    public $ebscoId;
    public $year;
    public $month;
    public $timesViewedInSearch;
    public $timesUsed;

	public function getUniquenessFields(): array
	{
		return ['instance','ebscoId','year', 'month'];
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