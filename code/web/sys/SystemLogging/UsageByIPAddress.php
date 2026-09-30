<?php /** @noinspection PhpMissingFieldTypeInspection */

class UsageByIPAddress extends DataObject {
	public $__table = 'usage_by_ip_address';
	protected $id;

	protected $instance;
	/** @noinspection PhpUnused */
	protected $ipAddress;
	protected $year;
	protected $month;

	/** These are set dynamically in incrementField so they appear to be unused */
	/** @noinspection PhpUnused */
	protected $numRequests;
	/** @noinspection PhpUnused */
	protected $numBlockedRequests;
	/** @noinspection PhpUnused */
	protected $numBlockedApiRequests;
	protected $lastRequest;
	/** @noinspection PhpUnused */
	protected $numLoginAttempts;
	/** @noinspection PhpUnused */
	protected $numFailedLoginAttempts;
	/** @noinspection PhpUnused */
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

	function objectHistoryEnabled() : bool {
		return false;
	}

	/** @noinspection PhpUnused */
	public function incrementNumRequests(): bool {
		return $this->incrementField('numRequests');
	}

	/** @noinspection PhpUnused */
	public function incrementNumBlockedRequests() : bool {
		return $this->incrementField('numBlockedRequests');
	}

	/** @noinspection PhpUnused */
	public function incrementNumBlockedApiRequests() : bool {
		return $this->incrementField('numBlockedApiRequests');
	}

	/** @noinspection PhpUnused */
	public function incrementNumSpammyRequests() : bool {
		return $this->incrementField('numSpammyRequests');
	}

	/** @noinspection PhpUnused */
	public function incrementNumLoginAttempts() : bool {
		return $this->incrementField('numLoginAttempts');
	}

	/** @noinspection PhpUnused */
	public function incrementNumFailedLoginAttempts() : bool {
		return $this->incrementField('numFailedLoginAttempts');
	}

	public function getId() : ?int {
		return $this->id;
	}

	/** @noinspection PhpUnused */
	public function getNumSpammyRequests() : int {
		return $this->numSpammyRequests;
	}

	private function incrementField(string $fieldName) : bool {
		if (!property_exists($this, $fieldName)) {
			return false;
		}
		$now = time();
		$this->lastRequest = $now;
		$this->$fieldName++;
		try {
			if (SystemVariables::getSystemVariables()->trackIpAddresses) {
				//The unique key on ( year, month, instance, ipAddress ) lets one statement create this month's row or
				//add to it if another request got there first, so a race can no longer throw the increment away
				$instance = $this->escape($this->instance);
				$ipAddress = $this->escape($this->ipAddress);
				$year = (int)$this->year;
				$month = (int)$this->month;
				return $this->query("INSERT INTO usage_by_ip_address (instance, ipAddress, year, month, $fieldName, lastRequest) VALUES ($instance, $ipAddress, $year, $month, 1, $now) ON DUPLICATE KEY UPDATE $fieldName = $fieldName + 1, lastRequest = IF (lastRequest < $now, $now, lastRequest)");
			}else{
				return true;
			}
		} catch (Exception) {
			//Ignore this, the table has not been created yet
			return true;
		}
	}
}