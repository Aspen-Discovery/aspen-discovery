<?php


class UsageByIPAddress extends DataObject {
	public $__table = 'usage_by_ip_address';
	protected $id;
	protected $instance;
	protected $ipAddress;
	protected $year;
	protected $month;
	protected $numRequests;
	protected $numBlockedRequests;
	protected $numBlockedApiRequests;
	protected $lastRequest;
	protected $numLoginAttempts;
	protected $numFailedLoginAttempts;
	protected $numSpammyRequests;

	public function getUniquenessFields(): array {
		return [
			'instance',
			'ipAddress',
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