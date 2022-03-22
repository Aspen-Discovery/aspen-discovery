<?php


class AccountSummary extends DataObject
{
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

	protected $_materialsRequests;
	protected $_readingHistory;

	public function getNumericColumnNames() : array
	{
		return ['userId','numCheckedOut','numCheckoutsRemaining','numOverdue','numAvailableHolds','numUnavailableHolds','totalFines','expirationDate','lastLoaded'];
	}

	/**
	 * @return int
	 */
	public function getMaterialsRequests()
	{
		return $this->_materialsRequests;
	}

	/**
	 * @param int $materialsRequests
	 */
	public function setMaterialsRequests($materialsRequests): void
	{
		$this->_materialsRequests = $materialsRequests;
	}

	public function getNumHolds(){
		return $this->numAvailableHolds + $this->numUnavailableHolds;
	}

	/**
	 * @return int
	 */
	public function getReadingHistory()
	{
		return $this->_readingHistory;
	}

	/**
	 * @param int $readingHistory
	 */
	public function setReadingHistory($readingHistory): void
	{
		$this->_readingHistory = $readingHistory;
	}

	private $_expired = null;
	private $_expireClose = null;
	private function loadExpirationInfo()
	{
		if ($this->expirationDate > 0) {
			$timeNow = time();
			$this->_expired = 0;
			$timeToExpire = $this->expirationDate - $timeNow;
			if ($timeToExpire <= 30 * 24 * 60 * 60) {
				if ($timeToExpire <= 0) {
					$this->_expired = 1;
				}
				$this->_expireClose = 1;
			} else {
				$this->_expireClose = 0;
			}
		} else {
			$this->_expired = 0;
			$this->_expireClose = 0;
		}
	}

	public function isExpired(){
		if ($this->_expired === null){
			$this->loadExpirationInfo();
		}
		return $this->_expired;
	}

	public function isExpirationClose(){
		if ($this->_expireClose === null){
			$this->loadExpirationInfo();
		}
		return $this->_expireClose;
	}

	public function expiresOn(){
		return date('M j, Y', $this->expirationDate);
	}

	private $_expirationFinesNotice = '';
	public function setExpirationFinesNotice(string $notice)
	{
		$this->_expirationFinesNotice = $notice;
	}

	/**
	 * @return string
	 */
	public function getExpirationFinesNotice(): string
	{
		return $this->_expirationFinesNotice;
	}

	public function toArray($includeRuntimeProperties = true, $encryptFields = false) : array{
		$return = parent::toArray($includeRuntimeProperties, $encryptFields);
		$return['expires'] = date('M j, Y', $this->expirationDate);
		$return['expired'] = $this->isExpired();
		$return['expireClose'] = $this->isExpirationClose();
		$return['expirationFinesNotice'] = $this->_expirationFinesNotice;
		$return['numHolds'] = $this->getNumHolds();
		return $return;
	}

	public function resetCounters()
	{
		$this->numCheckedOut = 0;
		$this->numCheckoutsRemaining = 0;
		$this->numOverdue = 0;
		$this->numAvailableHolds = 0;
		$this->numUnavailableHolds = 0;
		$this->totalFines = 0;
		$this->expirationDate = 0;
	}
}