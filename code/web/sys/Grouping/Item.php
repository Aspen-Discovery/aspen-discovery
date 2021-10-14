<?php

class Grouping_Item
{
	public $id;
	/** @var Grouping_Record */
	private $_record;
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
	public $eContentSource;
	/** @var string */
	public $groupedStatus;
	/** @var string */
	public $status;
	/** @var bool */
	public $locallyOwned;
	/** @var bool */
	public $holdable;
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
	public $locationCode;
	/**
	 * @var string
	 */
	public $subLocation;
	public $volume;
	public $volumeId;
	public $volumeOrder;
	public $lastCheckInDate;
	/**
	 * @var array
	 */
	public $numHolds = 0;
	public $available = false;
	private $_relatedUrls = [];
	private $_actions = [];
	private $_displayByDefault = false;


	/**
	 * Grouping_Item constructor.
	 * @param array $itemDetails
	 * @param array|null $scopingInfo
	 * @param Location $searchLocation
	 * @param Library $library
	 */
	public function __construct($itemDetails, $scopingInfo, $searchLocation, $library)
	{
		if (is_null($scopingInfo)){
			$this->itemId = $itemDetails['itemId'];
			$this->shelfLocation = $itemDetails['shelfLocation'];
			$this->callNumber = $itemDetails['callNumber'];
			$this->numCopies = $itemDetails['numCopies'];
			$this->isOrderItem = (bool)$itemDetails['isOrderItem'];
			$this->isEContent = $itemDetails['isEcontent'];
			$this->eContentSource = $itemDetails['eContentSource'];
			if ($this->isEContent && !empty($itemDetails['localUrl'])){
				$this->_relatedUrls[] = array(
					'source' => $itemDetails['eContentSource'],
					'file' => '',
					'url' => $itemDetails['localUrl']
				);
			}
			$this->groupedStatus = $itemDetails['groupedStatus'];
			$this->status = $itemDetails['status'];
			$this->locallyOwned = strpos($itemDetails['locationOwnedScopes'], "~{$itemDetails['scopeId']}~") !== false;
			$this->libraryOwned = $this->locallyOwned || strpos($itemDetails['libraryOwnedScopes'], "~{$itemDetails['scopeId']}~") !== false;
			$this->available = $itemDetails['available'] == "1";
			$this->holdable = $itemDetails['holdable'] == "1";
			$this->inLibraryUseOnly = $itemDetails['inLibraryUseOnly'] == "1";
			$this->locationCode = $itemDetails['locationCode'];
			$this->subLocation = $itemDetails['subLocationCode'];
			$this->lastCheckInDate = $itemDetails['lastCheckInDate'];
		}else {
			$this->itemId = $itemDetails[1] == 'null' ? '' : $itemDetails[1];
			$scopeKey = $itemDetails[0] . ':' . $this->itemId;

			$this->shelfLocation = $itemDetails[2];
			$this->callNumber = $itemDetails[3];
			$this->numCopies = is_numeric($itemDetails[6]) ? $itemDetails[6] : 0;
			$this->isOrderItem = $itemDetails[7] == 'true';
			$this->isEContent = $itemDetails[8] == 'true';

			$scopingDetails = $scopingInfo[$scopeKey];
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
			$this->holdable = $scopingDetails[6] == 'true';
			$this->inLibraryUseOnly = $scopingDetails[8] == 'true';
			$this->libraryOwned = $scopingDetails[9] == 'true';
			$this->locationCode = isset($itemDetails[15]) ? $itemDetails[15] : '';
			$this->subLocation = isset($itemDetails[16]) ? $itemDetails[16] : '';
		}
		if ($this->status == 'Library Use Only' && !$this->available) {
			$this->status = 'Checked Out (library use only)';
		}
		if ($this->available) {
			if ($searchLocation) {
				$this->_displayByDefault = $this->locallyOwned || $this->isEContent;
			} elseif ($library) {
				$this->_displayByDefault = $this->libraryOwned || $this->locallyOwned || $this->isEContent;
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

	public function getRelatedUrls() : array
	{
		return $this->_relatedUrls;
	}

	public function getSummaryKey() : string
	{
		$key = str_pad($this->volumeOrder, 10, '0', STR_PAD_LEFT);
		$key .= $this->shelfLocation . ':' . $this->callNumber;
		if ($this->locallyOwned) {
			$key = '1 ' . $key;
		} elseif ($this->libraryOwned) {
			$key = '5 ' . $key;
		} elseif ($this->isOrderItem) {
			$key = '7 ' . $key;
		} else {
			$key = '6 ' . $key;
		}
		return $key;
	}

	public function getSummary() : array
	{
		global $library;

		if (!empty($this->volume)) {
			$description = $this->volume . " ";
		}else{
			$description = '';
		}
		$description .= $this->shelfLocation . ": " . $this->callNumber;

		$description .= ' - ' . $this->status;
		$section = 'Other Locations';
		if ($this->locallyOwned) {
			$sectionId = 1;
			$section = 'In this library';
		} elseif ($this->libraryOwned) {
			$sectionId = 5;
			$section = $library->displayName;
		} elseif ($this->isOrderItem) {
			$sectionId = 7;
			$section = 'On Order';
		} else {
			$sectionId = 6;
		}

		$lastCheckInDate = '';
		if (!empty($this->lastCheckInDate)) {
			$date = new DateTime();
			$date->setTimestamp($this->lastCheckInDate);
			$lastCheckInDate =$date->format( 'M j, Y');
		}
		$itemSummaryInfo = array(
			'description' => $description,
			'shelfLocation' => $this->shelfLocation,
			'callNumber' => $this->callNumber,
			'totalCopies' => $this->numCopies,
			'availableCopies' => ($this->available && !$this->isOrderItem) ? $this->numCopies : 0,
			'isLocalItem' => $this->locallyOwned,
			'isLibraryItem' => $this->libraryOwned,
			'inLibraryUseOnly' => $this->inLibraryUseOnly,
			'allLibraryUseOnly' => $this->inLibraryUseOnly,
			'displayByDefault' => $this->isDisplayByDefault(),
			'onOrderCopies' => $this->isOrderItem ? $this->numCopies : 0,
			'status' => $this->groupedStatus,
			'statusFull' => $this->status,
			'available' => $this->available,
			'holdable' => $this->holdable,
			'sectionId' => $sectionId,
			'section' => $section,
			'relatedUrls' => $this->getRelatedUrls(),
			'lastCheckinDate' => $lastCheckInDate,
			'volume' => $this->volume,
			'volumeId' => $this->volumeId,
			'isEContent' => $this->isEContent,
			'locationCode' => $this->locationCode,
			'subLocation' => $this->subLocation,
			'itemId' => $this->itemId,
			'actions' => $this->getActions()
		);
		return $itemSummaryInfo;
	}

	public function setRecord(Grouping_Record $record)
	{
		$this->_record = $record;
	}
}