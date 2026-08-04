<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/AbstractUsage.php';

class SummonRecordUsage extends AbstractUsage {
	public $__table = 'summon_usage';
	public $id;
	public $instance;
	public $summonId;
	public $year;
	public $month;
	public $day;
	public $timesViewedInSearch;
	public $timesUsed;

	public function getUniquenessFields(): array {
		return [
			'instance',
			'summonId',
			'year',
			'month',
			'day',
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