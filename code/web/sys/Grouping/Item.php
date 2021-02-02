<?php

class Grouping_Item
{
	public $id;
	public $recordId;
	public $shelfLocation;
	public $callNumber;
	public $numCopies;
	/** @var bool */
	public $isOrderItem;
	/** @var bool */
	public $isEContent;
	/** @var string */
	public $itemId;
	/** @var string */
	public $scopeKey;
	/** @var string */
	public $eContentSource;
	/** @var string */
	public $groupedStatus;
	/** @var string */
	public $status;
	/** @var bool */
	public $locallyOwned;
	/** @var bool */
	public $holdable;
	/** @var bool */
	public $bookable;
	/**
	 * @var bool
	 */
	public $inLibraryUseOnly;
	/**
	 * @var bool
	 */
	public $libraryOwned;
	/**
	 * @var string
	 */
	public $bookablePTypes;
	/**
	 * @var string
	 */
	public $locationCode;
	/**
	 * @var string
	 */
	public $subLocation;
	public $volume;
	public $volumeId;
	public $volumeOrder;
	/**
	 * @var array
	 */
	public $holdablePTypes;
	public $numHolds = 0;
	public $available = false;
	private $_relatedUrls = [];
	private $_actions = [];
	private $_displayByDefault = false;


	/**
	 * Grouping_Item constructor.
	 * @param array $itemDetails
	 * @param array $scopingInfo
	 * @param string[] $activePTypes
	 * @param Location $searchLocation
	 * @param Library $library
	 */
	public function __construct($itemDetails, $scopingInfo, $activePTypes, $searchLocation, $library)
	{
		$this->itemId = $itemDetails[1] == 'null' ? '' : $itemDetails[1];
		$this->scopeKey = $itemDetails[0] . ':' . $this->itemId;

		$this->shelfLocation = $itemDetails[2];
		$this->callNumber = $itemDetails[3];
		$this->numCopies = is_numeric($itemDetails[6]) ? $itemDetails[6] : 0;
		$this->isOrderItem = $itemDetails[7] == 'true';
		$this->isEContent = $itemDetails[8] == 'true';

		$scopingDetails = $scopingInfo[$this->scopeKey];
		if ($this->isEContent) {
			if (strlen($scopingDetails[12]) > 0) {
				$this->_relatedUrls[] = array(
					'source' => $itemDetails[9],
					'file' => $itemDetails[10],
					'url' => $scopingDetails[12]
				);
			} else {
				$this->_relatedUrls[] = array(
					'source' => $itemDetails[9],
					'file' => $itemDetails[10],
					'url' => $itemDetails[11]
				);
			}

			$this->eContentSource = $itemDetails[9];
			$this->isEContent = true;
		}

		//Get Scoping information for this record
		$this->groupedStatus = $scopingDetails[2];
		$this->status = $itemDetails[13];
		$this->locallyOwned = $scopingDetails[4] == 'true';
		$this->available = $scopingDetails[5] == 'true';
		if ($this->status == 'Library Use Only' && !$this->available) {
			$this->status = 'Checked Out (library use only)';
		}
		$this->holdable = $scopingDetails[6] == 'true';
		$this->bookable = $scopingDetails[7] == 'true';
		$this->inLibraryUseOnly = $scopingDetails[8] == 'true';
		$this->libraryOwned = $scopingDetails[9] == 'true';
		$this->holdablePTypes = isset($scopingDetails[10]) ? $scopingDetails[10] : '';
		$this->bookablePTypes = isset($scopingDetails[11]) ? $scopingDetails[11] : '';
		$this->locationCode = isset($curItem[15]) ? $itemDetails[15] : '';
		$this->subLocation = isset($curItem[16]) ? $itemDetails[16] : '';
		if (strlen($this->holdablePTypes) > 0 && $this->holdablePTypes != '999') {
			$this->holdablePTypes = explode(',', $this->holdablePTypes);
			$matchingPTypes = array_intersect($this->holdablePTypes, $activePTypes);
			if (count($matchingPTypes) == 0) {
				$this->holdable = false;
			}
		}
		if (strlen($this->bookablePTypes) > 0 && $this->bookablePTypes != '999') {
			$this->bookablePTypes = explode(',', $this->bookablePTypes);
			$matchingPTypes = array_intersect($this->bookablePTypes, $activePTypes);
			if (count($matchingPTypes) == 0) {
				$this->bookable = false;
			}
		}
		if ($this->available) {
			if ($searchLocation) {
				$this->_displayByDefault = $this->locallyOwned || $this->isEContent;
			} elseif ($library) {
				$this->_displayByDefault = $this->libraryOwned || $this->isEContent;
			}
		}
	}

	/**
	 * @return bool
	 */
	public function isInLibraryUseOnly(): bool
	{
		return $this->inLibraryUseOnly;
	}

	/**
	 * @return bool
	 */
	public function isDisplayByDefault(): bool
	{
		return $this->_displayByDefault;
	}

	/**
	 * @return array
	 */
	public function getActions(): array
	{
		return $this->_actions;
	}

	/**
	 * @param array $actions
	 */
	public function setActions(array $actions): void
	{
		$this->_actions = $actions;
	}

	public function getRelatedUrls()
	{
		return $this->_relatedUrls;
	}
}