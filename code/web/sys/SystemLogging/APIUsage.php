<?php


class APIUsage extends DataObject {
	public $__table = 'api_usage';
	public $id;
	public $instance;
	public $year;
	public $month;
	public $module;
	public $method;
	public $numCalls;

	public function getUniquenessFields(): array {
		return [
			'instance',
			'year',
			'month',
			'module',
			'method',
		];
	}

	static function incrementStat($module, $method) {
		try {
			$apiUsage = new APIUsage();
			$apiUsage->year = date('Y');
			$apiUsage->month = date('n');
			global $aspenUsage;
			$apiUsage->instance = $aspenUsage->instance;
			$apiUsage->module = $module;
			$apiUsage->method = $method;
			if ($apiUsage->find(true)) {
				$apiUsage->numCalls++;
				$apiUsage->update();
			} else {
				$apiUsage->numCalls = 1;
				$apiUsage->insert();
			}
		} catch (PDOException $e) {
			//This happens if the table has not been created, ignore it
		}
	}

	public function okToExport(array $selectedFilters): bool {
		$okToExport = parent::okToExport($selectedFilters);
		if (in_array($this->instance, $selectedFilters['instances'])) {
			$okToExport = true;
		}
		return $okToExport;
	}
}