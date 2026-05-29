<?php

class Grouping_Item {
	public string $id;
	/** @noinspection PhpPropertyOnlyWrittenInspection */
	private ?Grouping_Record $_record;
	public string $recordId;
	public string $variationId;
	public ?string $shelfLocation;
	public ?string $callNumber;
	public int $numCopies;
	public bool $isOrderItem;
	public bool $isEContent;
	public string $itemId;
	public ?string $eContentSource;
	public ?string $groupedStatus;
	public ?string $status;
	public bool $locallyOwned;
	public string $holdable;
	public string $inLibraryUseOnly;
	public string $libraryOwned;
	public ?string $locationCode;
	public ?string $subLocation;
	public string $volume = '';
	public string|int $volumeId = '';
	public string $volumeOrder = '';
	public null|string|int $lastCheckInDate;
	public bool $atLibraryMainBranch = false;
	public bool $atActiveLocation = false;
	public bool $atUserHomeLocation = false;
	public bool $atUserNearbyLocation1 = false;
	public bool $atUserNearbyLocation2 = false;
	public bool $atActiveNearbyLocation1 = false;
	public bool $atActiveNearbyLocation2 = false;

	public int $numHolds = 0;
	public bool $available = false;
	public bool $isVirtual = false;
	public string $barcode = '';
	public string $dueDate = '';
	public string $note = '';
	private array $_relatedUrls = [];
	private array $_actions = [];
	private bool $_displayByDefault = false;


	/**
	 * Grouping_Item constructor.
	 */
	public function __construct(array $itemDetails, ?Location $searchLocation, false|int|string $activeLocationScopeId, false|int|string $mainLocationScopeId, false|int|string $homeLocationScopeId, false|int|string $userNearbyLocation1ScopeId, false|int|string $userNearbyLocation2ScopeId, false|int|string $atNearbyLocation1, false|int|string $atNearbyLocation2) {
		//Item details stored in the database
		$this->itemId = $itemDetails['itemId'];
		$this->shelfLocation = $itemDetails['shelfLocation'];
		$this->callNumber = $itemDetails['callNumber'];
		$this->numCopies = $itemDetails['numCopies'];
		$this->isOrderItem = (bool)$itemDetails['isOrderItem'];
		$this->isEContent = $itemDetails['isEContent'];
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
		$this->locallyOwned = str_contains($itemDetails['locationOwnedScopes'], "~{$itemDetails['scopeId']}~");
		$this->libraryOwned = $this->locallyOwned || str_contains($itemDetails['libraryOwnedScopes'], "~{$itemDetails['scopeId']}~");
		if ($activeLocationScopeId !== false) {
			$this->atActiveLocation = str_contains($itemDetails['locationOwnedScopes'], "~$activeLocationScopeId~");
		}
		if ($mainLocationScopeId !== false) {
			$this->atLibraryMainBranch = str_contains($itemDetails['locationOwnedScopes'], "~$mainLocationScopeId~");
		}
		if ($homeLocationScopeId !== false) {
			$this->atUserHomeLocation = str_contains($itemDetails['locationOwnedScopes'], "~$homeLocationScopeId~");
		}
		if ($userNearbyLocation1ScopeId !== false) {
			$this->atUserNearbyLocation1 = str_contains($itemDetails['locationOwnedScopes'], "~$userNearbyLocation1ScopeId~");
		}
		if ($userNearbyLocation2ScopeId !== false) {
			$this->atUserNearbyLocation2 = str_contains($itemDetails['locationOwnedScopes'], "~$userNearbyLocation2ScopeId~");
		}
		if ($atNearbyLocation1 !== false) {
			$this->atActiveNearbyLocation1 = str_contains($itemDetails['locationOwnedScopes'], "~$atNearbyLocation1~");
		}
		if ($atNearbyLocation2 !== false) {
			$this->atActiveNearbyLocation2 = str_contains($itemDetails['locationOwnedScopes'], "~$atNearbyLocation2~");
		}
		$this->available = $itemDetails['available'] == "1";
		$this->holdable = $itemDetails['holdable'] == "1" ? 1 : 0;
		$this->inLibraryUseOnly = $itemDetails['inLibraryUseOnly'] == "1";
		$this->locationCode = $itemDetails['locationCode'];
		$this->subLocation = $itemDetails['subLocationCode'];
		$this->lastCheckInDate = $itemDetails['lastCheckInDate'];
		$this->isVirtual = $itemDetails['isVirtual'];
		$this->variationId = $itemDetails['groupedWorkVariationId'];
		$this->barcode = $itemDetails['barcode'] ?? '';
		$this->dueDate = $itemDetails['dueDate'] ?? '';
		$this->note = $itemDetails['note'] ?? '';

		if ($this->status == 'Library Use Only' && !$this->available) {
			$this->status = 'Checked Out (library use only)';
		}
		if ($this->available) {
			if ($searchLocation) {
				$this->_displayByDefault = $this->locallyOwned || $this->isEContent;
			} else {
				$this->_displayByDefault = $this->libraryOwned || $this->locallyOwned || $this->isEContent;
			}
		}
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

	public function getLocationKey() : int {
		if ($this->atActiveLocation) {
			$key = 1;
		}else if ($this->atUserHomeLocation) {
			$key = 2;
		}else if ($this->locallyOwned) {
			$key = 3;
		}else if ($this->atUserNearbyLocation1) {
			$key = 4;
		}else if ($this->atUserNearbyLocation2) {
			$key = 5;
		} elseif ($this->atLibraryMainBranch) {
			$key = 6;
		} elseif ($this->libraryOwned) {
			$key = 7;
		}else if ($this->atActiveNearbyLocation1) {
			$key = 8;
		}else if ($this->atActiveNearbyLocation2) {
			$key = 9;
		} elseif ($this->isOrderItem) {
			$key = 10;
		} else {
			$key = 11;
		}
		return $key;
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
		}else if ($this->atUserNearbyLocation1) {
			$key = '04 ' . $key;
		}else if ($this->atUserNearbyLocation2) {
			$key = '05 ' . $key;
		} elseif ($this->atLibraryMainBranch) {
			$key = '06 ' . $key;
		} elseif ($this->libraryOwned) {
			$key = '07 ' . $key;
		}else if ($this->atActiveNearbyLocation1) {
			$key = '08 ' . $key;
		}else if ($this->atActiveNearbyLocation2) {
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
			$section = 'Other Locations';
		}

		$lastCheckInDate = '';
		if (!empty($this->lastCheckInDate)) {
			$date = new DateTime();
			$date->setTimestamp($this->lastCheckInDate);
			$lastCheckInDate = $date->format('M j, Y');
		}
		/** @noinspection PhpUnnecessaryLocalVariableInspection */
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
			'numHolds' => $this->numHolds,
			'sectionId' => $sectionId,
			'section' => $section,
			'locationKey' => $this->getLocationKey(),
			'relatedUrls' => $this->getRelatedUrls(),
			'lastCheckinDate' => $lastCheckInDate,
			'volume' => $this->volume,
			'volumeId' => $this->volumeId,
			'isEContent' => $this->isEContent,
			'locationCode' => $this->locationCode,
			'locationName' => $this->getLocationName(),
			'subLocation' => $this->subLocation,
			'barcode' => $this->barcode,
			'note' => $this->note,
			'dueDate' => $this->dueDate,
			'itemId' => $this->itemId,
			'variationId' => $this->variationId,
			'actions' => $this->getActions(),
		];
		return $itemSummaryInfo;
	}

	public function setRecord(Grouping_Record $record) : void {
		$this->_record = $record;
	}

	private static ?array $locationCodeToDisplayName = null;
	public function getLocationName() {
		if (Grouping_Item::$locationCodeToDisplayName === null) {
			$location = new Location();
			Grouping_Item::$locationCodeToDisplayName = $location->fetchAll('code', 'displayName', true);
		}
		$lowerLocationCode = strtolower($this->locationCode);
		if (array_key_exists($lowerLocationCode, Grouping_Item::$locationCodeToDisplayName)) {
			return Grouping_Item::$locationCodeToDisplayName[$lowerLocationCode];
		}else{
			return $this->locationCode;
		}
	}
}