<?php

class Grouping_Item {
	public $id;
	/** @var Grouping_Record */
	private $_record;
	public $recordId;
	public $variationId;
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
	public $atLibraryMainBranch;
	public $atActiveLocation;
	public $atUserHomeLocation;
	public $atUserNearbyLocation1;
	public $atUserNearbyLocation2;
	public $atActiveNearbyLocation1;
	public $atActiveNearbyLocation2;

	/**
	 * @var array
	 */
	public $numHolds = 0;
	public $available = false;
	public $isVirtual = false;
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
	public function __construct($itemDetails, $scopingInfo, $searchLocation, $library, $activeLocationScopeId, $mainLocationScopeId, $homeLocationScopeId, $userNearbyLocation1ScopeId, $userNearbyLocation2ScopeId, $atNearbyLocation1, $atNearbyLocation2) {
		if (is_null($scopingInfo)) {
			//Item details stored in the database
			$this->itemId = $itemDetails['itemId'];
			$this->shelfLocation = $itemDetails['shelfLocation'];
			$this->callNumber = $itemDetails['callNumber'];
			$this->numCopies = $itemDetails['numCopies'];
			$this->isOrderItem = (bool)$itemDetails['isOrderItem'];
			$this->isEContent = $itemDetails['isEcontent'];
			$this->eContentSource = $itemDetails['eContentSource'];
			if ($this->isEContent && !empty($itemDetails['localUrl'])) {
				$this->_relatedUrls[] = [
					'source' => $itemDetails['eContentSource'],
					'file' => '',
					'url' => $itemDetails['localUrl'],
				];
			}
			$this->groupedStatus = $itemDetails['groupedStatus'];
			$this->status = $itemDetails['status'];
			$this->locallyOwned = strpos($itemDetails['locationOwnedScopes'], "~{$itemDetails['scopeId']}~") !== false;
			$this->libraryOwned = $this->locallyOwned || strpos($itemDetails['libraryOwnedScopes'], "~{$itemDetails['scopeId']}~") !== false;
			if ($activeLocationScopeId !== false) {
				$this->atActiveLocation = strpos($itemDetails['locationOwnedScopes'], "~{$activeLocationScopeId}~") !== false;
			}
			if ($mainLocationScopeId !== false) {
				$this->atLibraryMainBranch = strpos($itemDetails['locationOwnedScopes'], "~{$mainLocationScopeId}~") !== false;
			}
			if ($homeLocationScopeId !== false) {
				$this->atUserHomeLocation = strpos($itemDetails['locationOwnedScopes'], "~{$homeLocationScopeId}~") !== false;
			}
			if ($userNearbyLocation1ScopeId !== false) {
				$this->atUserNearbyLocation1 = strpos($itemDetails['locationOwnedScopes'], "~{$userNearbyLocation1ScopeId}~") !== false;
			}
			if ($userNearbyLocation2ScopeId !== false) {
				$this->atUserNearbyLocation2 = strpos($itemDetails['locationOwnedScopes'], "~{$userNearbyLocation2ScopeId}~") !== false;
			}
			if ($atNearbyLocation1 !== false) {
				$this->atActiveNearbyLocation1 = strpos($itemDetails['locationOwnedScopes'], "~{$atNearbyLocation1}~") !== false;
			}
			if ($atNearbyLocation2 !== false) {
				$this->atActiveNearbyLocation2 = strpos($itemDetails['locationOwnedScopes'], "~{$atNearbyLocation2}~") !== false;
			}
			$this->available = $itemDetails['available'] == "1";
			$this->holdable = $itemDetails['holdable'] == "1";
			$this->inLibraryUseOnly = $itemDetails['inLibraryUseOnly'] == "1";
			$this->locationCode = $itemDetails['locationCode'];
			$this->subLocation = $itemDetails['subLocationCode'];
			$this->lastCheckInDate = $itemDetails['lastCheckInDate'];
			$this->isVirtual = $itemDetails['isVirtual'];
			$this->variationId = $itemDetails['groupedWorkVariationId'];
		} else {
			//Item details stored in solr
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
					$this->_relatedUrls[] = [
						'source' => $itemDetails[9],
						'file' => $itemDetails[10],
						'url' => $scopingDetails[12],
					];
				} else {
					$this->_relatedUrls[] = [
						'source' => $itemDetails[9],
						'file' => $itemDetails[10],
						'url' => $itemDetails[11],
					];
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
	public function isInLibraryUseOnly(): bool {
		return $this->inLibraryUseOnly;
	}

	/**
	 * @return bool
	 */
	public function isDisplayByDefault(): bool {
		return $this->_displayByDefault;
	}

	/**
	 * @return array
	 */
	public function getActions(): array {
		return $this->_actions;
	}

	/**
	 * @param array $actions
	 */
	public function setActions(array $actions): void {
		$this->_actions = $actions;
	}

	public function getRelatedUrls(): array {
		return $this->_relatedUrls;
	}

	public function getSummaryKey(): string {
		$key = str_pad($this->volumeOrder, 10, '0', STR_PAD_LEFT);
		$key .= $this->shelfLocation . ':' . $this->callNumber;
		if ($this->atActiveLocation) {
			$key = '01 ' . $key;
		}else if ($this->atUserHomeLocation) {
			$key = '02 ' . $key;
		}else if ($this->locallyOwned) {
			$key = '03 ' . $key;
		}else if ($this->atActiveNearbyLocation1) {
			$key = '04 ' . $key;
		}else if ($this->atActiveNearbyLocation2) {
			$key = '05 ' . $key;
		}else if ($this->atUserNearbyLocation1) {
			$key = '06 ' . $key;
		}else if ($this->atUserNearbyLocation2) {
			$key = '07 ' . $key;
		} elseif ($this->atLibraryMainBranch) {
			$key = '08 ' . $key;
		} elseif ($this->libraryOwned) {
			$key = '09 ' . $key;
		} elseif ($this->isOrderItem) {
			$key = '10 ' . $key;
		} else {
			$key = '11 ' . $key;
		}
		return $key;
	}

	public function getSummary(): array {
		global $library;

		if (!empty($this->volume)) {
			$description = $this->volume . " ";
		} else {
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
			$lastCheckInDate = $date->format('M j, Y');
		}
		$itemSummaryInfo = [
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
			'variationId' => $this->variationId,
			'actions' => $this->getActions(),
		];
		return $itemSummaryInfo;
	}

	public function setRecord(Grouping_Record $record) {
		$this->_record = $record;
	}
}