<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class CampaignData extends DataObject {
	public $__table = 'ce_campaign_data';
	public $id;
	public $instance;
	public $campaignId;
	public $year;
	public $month;
	public $totalEnrollments;
	public $currentEnrollments;
	public $totalUnenrollments;

	public function getUniquenessFields(): array
	{
		return [
			'instance',
			'campaignId',
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