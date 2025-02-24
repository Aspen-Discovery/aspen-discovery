<?php /** @noinspection PhpMissingFieldTypeInspection */

class UsageByIPAddress extends DataObject {
	public $__table = 'usage_by_ip_address';
	protected $id;

	protected $instance;
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

	public function incrementNumRequests(): bool {
		return $this->incrementField('numRequests');
	}

	public function incrementNumBlockedRequests() : bool {
		return $this->incrementField('numBlockedRequests');
	}

	public function incrementNumBlockedApiRequests() : bool {
		return $this->incrementField('numBlockedApiRequests');
	}

	public function incrementNumSpammyRequests() : bool {
		return $this->incrementField('numSpammyRequests');
	}

	public function incrementNumLoginAttempts() : bool {
		return $this->incrementField('numLoginAttempts');
	}

	public function incrementNumFailedLoginAttempts() : bool {
		return $this->incrementField('numFailedLoginAttempts');
	}

	public function getId() : int {
		return $this->id;
	}

	public function getNumSpammyRequests() : int {
		return $this->numSpammyRequests;
	}

	private function incrementField(string $fieldName) : bool {
		$now = time();
		$this->lastRequest = $now;
		$this->$fieldName++;
		try {
			if (SystemVariables::getSystemVariables()->trackIpAddresses) {
				if (empty($this->id)) {
					return $this->insert() !== false;
				}else{
					return $this->query("UPDATE usage_by_ip_address SET $fieldName = $fieldName + 1, lastRequest = IF (lastRequest < $now, $now, lastRequest) WHERE id = $this->id");
				}
			}else{
				return true;
			}
		} catch (Exception $e) {
			//Ignore this, the table has not been created yet
			return true;
		}
	}
}