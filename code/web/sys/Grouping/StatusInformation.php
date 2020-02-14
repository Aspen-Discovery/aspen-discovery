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

		$statusRankings = array(
			'currently unavailable' => 1,
			'on order' => 2,
			'coming soon' => 3,
			'in processing' => 3.5,
			'checked out' => 4,
			'library use only' => 5,
			'available online' => 6,
			'in transit' => 6.5,
			'on shelf' => 7
		);
		if ($statusInformation->getGroupedStatus() != '') {
			$groupedStatus = $this->_groupedStatus;

			//Check to see if we have a better status here
			if (array_key_exists(strtolower($statusInformation->getGroupedStatus()), $statusRankings)) {
				if ($groupedStatus == '') {
					$groupedStatus = $statusInformation->getGroupedStatus();
					//Check to see if we are getting a better status
				} elseif ($statusRankings[strtolower($statusInformation->getGroupedStatus())] > $statusRankings[strtolower($groupedStatus)]) {
					$groupedStatus = $statusInformation->getGroupedStatus();
				}
				$this->_groupedStatus = $groupedStatus;
			}
		}

		$this->_copies += $statusInformation->getCopies();
		$this->_availableCopies += $statusInformation->getAvailableCopies();
		if ($statusInformation->getLocalCopies() > 0) {
			$this->_localCopies += $statusInformation->getLocalCopies();
			$this->_localAvailableCopies += $statusInformation->getLocalAvailableCopies();
			$this->_hasLocalItem = true;
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
	public function getNumHolds()
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

}