<?php /** @noinspection PhpMissingFieldTypeInspection */


class AccountSummary extends DataObject {
	public $__table = 'user_account_summary';
	public $id;
	public $source;
	public $userId;
	public $numCheckedOut;
	public $numCheckoutsRemaining; //Currently used for Hoopla Only
	public $numOverdue;
	public $numAvailableHolds;
	public $numUnavailableHolds;
	public $totalFines;
	public $expirationDate;
	public $lastLoaded;
	public $hasUpdatedSavedSearches;
	//This determines if the data stored with account summary is stale so we can force a reload
	public $dataIsStale;
	public $holdsAreStale;
	public $holdCacheTime;
	public $checkoutsAreStale;
	public $checkoutCacheTime;

	protected $_materialsRequests;
	protected $_readingHistory;
	protected $_numUpdatedSearches;

	public function getNumericColumnNames(): array {
		return [
			'userId',
			'numCheckedOut',
			'numCheckoutsRemaining',
			'numOverdue',
			'numAvailableHolds',
			'numUnavailableHolds',
			'totalFines',
			'expirationDate',
			'lastLoaded',
			'hasUpdatedSavedSearches',
			'dataIsStale',
			'holdsAreStale',
			'holdCacheTime',
			'checkoutsAreStale',
			'checkoutCacheTime',
		];
	}

	function objectHistoryEnabled() : bool {
		return false;
	}

	/**
	 * @return int
	 */
	public function getMaterialsRequests() : int {
		return $this->_materialsRequests;
	}

	/**
	 * @param int $materialsRequests
	 */
	public function setMaterialsRequests(int $materialsRequests): void {
		$this->_materialsRequests = $materialsRequests;
	}

	public function getNumHolds() : int {
		return $this->numAvailableHolds + $this->numUnavailableHolds;
	}

	/**
	 * @return int
	 */
	public function getReadingHistory() : int {
		return $this->_readingHistory;
	}

	/**
	 * @param int $readingHistory
	 */
	public function setReadingHistory(int $readingHistory): void {
		$this->_readingHistory = $readingHistory;
	}

	public function setNumUpdatedSearches(int $numUpdatedSearches): void {
		$this->_numUpdatedSearches = $numUpdatedSearches;
	}

	private ?ExpirationInformation $_expirationInformation = null;

	private function getExpirationInformation() : ExpirationInformation {
		if ($this->_expirationInformation === null) {
			require_once ROOT_DIR . '/sys/User/ExpirationInformation.php';
			$this->_expirationInformation = $this->loadIlsExpirationInformation() ?? $this->buildLocalExpirationInformation();
		}
		return $this->_expirationInformation;
	}

	private function loadIlsExpirationInformation() : ?ExpirationInformation {
		if (empty($this->userId)) {
			return null;
		}
		require_once ROOT_DIR . '/sys/Account/User.php';
		$user = new User();
		$user->id = $this->userId;
		if (!$user->find(true) || !$user->hasIlsConnection()) {
			return null;
		}
		return $user->getExpirationInformation();
	}

	private function buildLocalExpirationInformation() : ExpirationInformation {
		$info = new ExpirationInformation();
		$info->expirationDate = (int) ($this->expirationDate ?? 0);
		return $info;
	}

	public function setRenewalWindowDays(int $days) : void {
		$this->getExpirationInformation()->renewalWindowDays = $days;
	}

	public function isExpired() : bool {
		return $this->getExpirationInformation()->isExpired();
	}

	public function isExpirationClose() : bool {
		return $this->getExpirationInformation()->isExpirationClose();
	}

	public function expiresOn() : string {
		return $this->getExpirationInformation()->expiresOn();
	}

	//This is set and then returned as part of the toArray method
	private $_expirationNotice = '';

	public function setExpirationNotice(string $notice) : void {
		$this->_expirationNotice = $notice;
	}

	private $_finesBadge = '';

	public function setFinesBadge(string $notice) : void {
		$this->_finesBadge = $notice;
	}

	public function toArray($includeRuntimeProperties = true, $encryptFields = false): array {
		$return = parent::toArray($includeRuntimeProperties, $encryptFields);
		$return['expires'] = date('M j, Y', $this->expirationDate);
		$return['expired'] = $this->isExpired();
		$return['expireClose'] = $this->isExpirationClose();
		$return['expirationNotice'] = $this->_expirationNotice;
		$return['numHolds'] = $this->getNumHolds();
		if ($this->_numUpdatedSearches > 0) {
			$return['savedSearches'] = translate([
				'text' => '%1% Updated',
				1 => $this->_numUpdatedSearches,
				'isPublicFacing' => true,
			]);
		} else {
			$return['savedSearches'] = '';
		}
		$return['finesBadge'] = $this->_finesBadge;
		return $return;
	}

	/**
	 * @return void
	 */
	public function resetCounters() : void {
		$this->numCheckedOut = 0;
		$this->numCheckoutsRemaining = 0;
		$this->numOverdue = 0;
		$this->numAvailableHolds = 0;
		$this->numUnavailableHolds = 0;
		$this->totalFines = 0;
		$this->expirationDate = 0;
	}

	/**
	 * Increments the number of unavailable holds after a hold is placed.
	 * @return void
	 */
	public function incrementNumberOfUnavailableHolds() : void {
		$this->__set('numUnavailableHolds', ++$this->numUnavailableHolds);
		$this->update();
	}

	public function areHoldsStale() : bool {
		return $this->holdsAreStale || ((time() - $this->holdCacheTime) > 5 * 60);
	}
	public function markHoldsStale() : void {
		$this->__set('holdsAreStale', 1);
		$this->update();
	}

	public function clearHoldsStale() : void {

		$this->__set('holdsAreStale', 0);
		$this->__set('holdCacheTime', time());
		$this->update();
	}

	public function decrementAvailableHolds() : void {
		$this->__set('numAvailableHolds', --$this->numAvailableHolds);
		$this->update();
	}

	public function decrementUnavailableHolds() : void {
		$this->__set('numUnavailableHolds', --$this->numUnavailableHolds);
		$this->update();
	}

	public function areCheckoutsStale() : bool {
		return $this->checkoutsAreStale || ((time() - $this->checkoutCacheTime) > 5 * 60);
	}

	public function incrementNumberOfCheckouts() : void {
		$this->__set('numCheckedOut', ++$this->numCheckedOut);
		if ($this->numCheckoutsRemaining > 0) {
			$this->__set('numCheckoutsRemaining', --$this->numCheckoutsRemaining);
		}
		$this->update();
	}

	public function decrementNumberOfCheckouts() : void {
		$this->__set('numCheckedOut', --$this->numCheckedOut);
		if ($this->numCheckoutsRemaining > 0) {
			$this->__set('numCheckoutsRemaining', ++$this->numCheckoutsRemaining);
		}
		$this->update();
	}

	public function markCheckoutsStale() : void {
		$this->__set('checkoutsAreStale', 1);
		$this->update();
	}

	public function clearCheckoutsStale() : void {
		$this->__set('checkoutsAreStale', 0);
		$this->__set('checkoutCacheTime', time());
		$this->update();
	}
}