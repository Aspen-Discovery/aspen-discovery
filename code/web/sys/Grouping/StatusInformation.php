<?php


class Grouping_StatusInformation
{
	private $_available = false;
	private $_availableLocally = false;
	private $_availableHere = false;
	private $_availableOnline = false;
	private $_inLibraryUseOnly = true;
	private $_allLibraryUseOnly = true;
	private $_hasLocalItem = false;
	public $_groupedStatus = 'Currently Unavailable';
	private $_onOrderCopies = 0;
	private $_numHolds = 0;
	private $_copies = 0;
	private $_availableCopies = 0;
	private $_localCopies = 0;
	private $_localAvailableCopies = 0;
	private $_isEcontent = false;
	private $_isShowStatus = false;

	/**
	 * @return bool
	 */
	public function isAvailable()
	{
		return $this->_available;
	}

	/**
	 * @param Grouping_StatusInformation $statusInformation
	 */
	public function updateStatus($statusInformation)
	{
		if ($statusInformation->isAvailableLocally()) {
			$this->_availableLocally = true;
		}
		if ($statusInformation->isAvailableHere()) {
			$this->_availableHere = true;
		}
		if ($statusInformation->isAvailableOnline()) {
			$this->_availableOnline = true;
		}
		if ($statusInformation->isEContent()){
			$this->_isEcontent = true;
		}
		if (!$this->_available && $statusInformation->isAvailable()) {
			$this->_available = true;
		}
		if ($statusInformation->isInLibraryUseOnly()) {
			$this->_inLibraryUseOnly = true;
		} else {
			$this->_allLibraryUseOnly = false;
		}
		if ($statusInformation->hasLocalItem()) {
			$this->_hasLocalItem = true;
		}
		if ($statusInformation->getNumHolds()) {
			$this->_numHolds += $statusInformation->getNumHolds();
		}
		$this->_onOrderCopies += $statusInformation->getOnOrderCopies();

		$this->_groupedStatus = GroupedWorkDriver::keepBestGroupedStatus($this->_groupedStatus, $statusInformation->getGroupedStatus());

		$this->_copies += $statusInformation->getCopies();
		$this->_availableCopies += $statusInformation->getAvailableCopies();
		if ($statusInformation->getLocalCopies() > 0) {
			$this->_localCopies += $statusInformation->getLocalCopies();
			$this->_localAvailableCopies += $statusInformation->getLocalAvailableCopies();
			$this->_hasLocalItem = true;
		}
		if ($statusInformation->isShowStatus()){
			$this->_isShowStatus = true;
		}
	}

	/**
	 * @return bool
	 */
	public function isAvailableHere(): bool
	{
		return $this->_availableHere;
	}

	/**
	 * @return bool
	 */
	public function isAvailableOnline(): bool
	{
		return $this->_availableOnline;
	}

	/**
	 * @return bool
	 */
	public function isAllLibraryUseOnly(): bool
	{
		return $this->_allLibraryUseOnly;
	}

	/**
	 * @return bool
	 */
	public function isAvailableLocally(): bool
	{
		return $this->_availableLocally;
	}

	/**
	 * @return bool|mixed
	 */
	public function hasLocalItem()
	{
		return $this->_hasLocalItem;
	}

	/**
	 * @return bool
	 */
	public function isInLibraryUseOnly(): bool
	{
		return $this->_inLibraryUseOnly;
	}

	/**
	 * @return string
	 */
	public function getGroupedStatus(): string
	{
		return $this->_groupedStatus;
	}

	/**
	 * @param string $groupedStatus
	 */
	public function setGroupedStatus(string $groupedStatus): void
	{
		$this->_groupedStatus = $groupedStatus;
	}

	/**
	 * @return int
	 */
	public function getNumHolds() : int
	{
		return $this->_numHolds;
	}

	/**
	 * @return int
	 */
	public function getOnOrderCopies()
	{
		return $this->_onOrderCopies;
	}

	/**
	 * @return int
	 */
	public function getCopies()
	{
		return $this->_copies;
	}

	/**
	 * @return int
	 */
	public function getAvailableCopies()
	{
		return $this->_availableCopies;
	}

	/**
	 * @return int
	 */
	public function getLocalCopies(): int
	{
		return $this->_localCopies;
	}

	/**
	 * @return int
	 */
	public function getLocalAvailableCopies(): int
	{
		return $this->_localAvailableCopies;
	}

	/**
	 * @param int $numHolds
	 */
	public function setNumHolds(int $numHolds): void
	{
		$this->_numHolds = $numHolds;
	}

	/**
	 * @param bool $available
	 */
	function setAvailable(bool $available): void
	{
		$this->_available = $available;
	}

	/**
	 * @param bool $availableOnline
	 */
	function setAvailableOnline(bool $availableOnline): void
	{
		$this->_availableOnline = $availableOnline;
	}

	function addAvailableCopies(int $numCopies): void
	{
		$this->_availableCopies += $numCopies;
	}

	/**
	 * @param bool $allLibraryUseOnly
	 */
	function setAllLibraryUseOnly(bool $allLibraryUseOnly): void
	{
		$this->_allLibraryUseOnly = $allLibraryUseOnly;
	}

	/**
	 * @param bool $inLibraryUseOnly
	 */
	function setInLibraryUseOnly(bool $inLibraryUseOnly): void
	{
		$this->_inLibraryUseOnly = $inLibraryUseOnly;
	}

	/**
	 * @param bool $availableHere
	 */
	function setAvailableHere(bool $availableHere): void
	{
		$this->_availableHere = $availableHere;
	}

	/**
	 * @param bool $availableLocally
	 */
	function setAvailableLocally(bool $availableLocally): void
	{
		$this->_availableLocally = $availableLocally;
	}

	/**
	 * @param int $copies
	 */
	function addCopies(int $copies)
	{
		$this->_copies += $copies;
	}

	/**
	 * @param int $localCopies
	 */
	function addLocalCopies(int $localCopies): void
	{
		$this->_localCopies += $localCopies;
	}

	function addOnOrderCopies(int $numCopies): void
	{
		$this->_onOrderCopies += $numCopies;
	}

	function getNumberOfCopiesMessage(){
		//Build the string to be translated
		$numberOfCopiesMessage = '';
		global $library;
		//If we don't have holds or on order copies, we don't need to show anything.
		if ($this->getNumHolds() == 0 && $this->getOnOrderCopies() == 0){
			$numberOfCopiesMessage = '';
		}else {
			if ($this->getAvailableCopies() > 9999){
				$numberOfCopiesMessage .= 'Always Available';
			}else {
				if ($this->getNumHolds() == 0) {
					if ($this->getAvailableCopies() == 1) {
						$numberOfCopiesMessage .= '1 copy available';
					} elseif ($this->getAvailableCopies() > 1) {
						$numberOfCopiesMessage .= '%1% copies available';
					}
				}
				if ($this->getNumHolds() > 0) {
					if ($this->getCopies() == 1) {
						$numberOfCopiesMessage .= '1 copy';
					} elseif ($this->getCopies() > 1) {
						$numberOfCopiesMessage .= '%1% copies';
					}
					if (!empty($numberOfCopiesMessage)) {
						$numberOfCopiesMessage .= ', ';
					}
					if ($this->getNumHolds() == 1) {
						$numberOfCopiesMessage .= '1 person is on the wait list';
					} else {
						$numberOfCopiesMessage .= '%2% people are on the wait list';
					}
				}
			}
			if (!empty($numberOfCopiesMessage)){
				$numberOfCopiesMessage .= '. ';
			}
			if ($this->getOnOrderCopies() > 0 && $this->getCopies() < 10000){
				if ($library->showOnOrderCounts){
					if ($this->getOnOrderCopies() == 1){
						$numberOfCopiesMessage .= '1 copy on order.';
					}else{
						$numberOfCopiesMessage .= '%3% copies on order.';
					}
				}else{
					//Only show that additional copies are on order if there are existing copies
					if ($this->getCopies() > 0){
						$numberOfCopiesMessage .= 'Additional copies on order.';
					}
				}
			}
		}

		return translate([
			'text' => $numberOfCopiesMessage,
			1 => $this->getCopies(),
			2 => $this->getNumHolds(),
			3 => $this->getOnOrderCopies(),
			'isPublicFacing' => true,
		]);
	}

	public function setIsEContent(bool $flag)
	{
		$this->_isEcontent = $flag;
	}

	public function isEContent(){
		return $this->_isEcontent;
	}

	/**
	 * @return bool
	 */
	public function isShowStatus(): bool
	{
		return $this->_isShowStatus;
	}

	/**
	 * @param bool $isShowStatus
	 */
	public function setIsShowStatus(bool $isShowStatus): void
	{
		$this->_isShowStatus = $isShowStatus;
	}
}