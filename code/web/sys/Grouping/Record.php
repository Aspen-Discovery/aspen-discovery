<?php

require_once ROOT_DIR . '/sys/Grouping/StatusInformation.php';
require_once ROOT_DIR . '/sys/Grouping/Item.php';

class Grouping_Record {
	public string $id;
	public string|int $databaseId;

	public string $format;
	public string $formatCategory;
	public ?string $edition;
	public ?string $audience;
	public string $language;
	public ?string $publisher;
	public ?string $publicationDate;
	private null|int|string $sortablePublicationDate = null;
	public ?string $placeOfPublication;
	public ?string $physical;
	public bool|string $closedCaptioned;
	public string $variationFormat;
	public string|int $variationId;
	/** @var Grouping_Variation[] */
	public ?array $recordVariations;
	public bool $hasParentRecord;
	public bool $hasChildRecord;

	protected ?GroupedWorkSubDriver $_driver;
	protected string $_url;

	/** @var Grouping_StatusInformation */
	protected Grouping_StatusInformation $_statusInformation;

	protected bool $_isEContent = false;
	public ?string $_eContentSource = null;
	public array $_volumeHolds;
	public bool $_hasLocalItem = false;
	public string $_shelfLocation = '';
	public bool $_holdable = false;
	public bool $_locallyHoldable = false;
	public array $_itemSummary = [];
	public ?array $_itemsDisplayedByDefault = null;
	public array $_itemDetails = [];

	public string $source;
	public array $_actions = [];
	/** @var Grouping_Item[] */
	private array $_items = [];

	/** @var  IlsVolumeInfo[] */
	private array $_volumeData;
	private ?array $_unsuppressedVolumeData = null;
	private ?array $_unsuppressedLocalVolumeData = null;

	//Is the record an OverDrive record?
	//If so, the number of owned and available copies are already set.
	private bool $_isOverDrive = false;

	/**
	 * Grouping_Record constructor.
	 * @param string $recordId
	 * @param array $recordDetails
	 * @param GroupedWorkSubDriver $recordDriver
	 * @param IlsVolumeInfo[] $volumeData
	 * @param string $source
	 * @param bool $useAssociativeArray
	 * @param ?Grouping_Variation $variation
	 */
	public function __construct(string $recordId, array $recordDetails, GroupedWorkSubDriver $recordDriver, array $volumeData, string $source, bool $useAssociativeArray = false, ?Grouping_Variation $variation = null) {
		$this->_driver = $recordDriver;
		$this->_url = $recordDriver->getRecordUrl();
		$this->id = $recordId;
		if ($useAssociativeArray) {
			//Loaded from Database
			$this->format = $recordDetails['format'];
			$this->formatCategory = $recordDetails['formatCategory'];
			$this->databaseId = $recordDetails['id'];
			$this->edition = $recordDetails['edition'];
			$this->audience = $recordDetails['audience'];
			$this->publisher = $recordDetails['publisher'];
			$this->publicationDate = $recordDetails['publicationDate'];
			$this->placeOfPublication = $recordDetails['placeOfPublication'];
			$this->physical = $recordDetails['physicalDescription'];
			$this->language = $recordDetails['language'];
			if (isset($recordDetails['isClosedCaptioned'])) {
				$this->closedCaptioned = $recordDetails['isClosedCaptioned'];
			}
			$this->hasParentRecord = $recordDetails['hasParentRecord'];
			$this->hasChildRecord = $recordDetails['hasChildRecord'];

			if ($variation != null) {
				$this->variationFormat = $variation->manifestation->format;
				$this->variationId = $variation->databaseId;
				//Let the variation format override the format stored in the database.
				$this->format = $this->variationFormat;
			}
		} else {
			//Loaded from Solr
			$this->format = $recordDetails[1];
			$this->formatCategory = $recordDetails[2];
			$this->edition = $recordDetails[3];
			$this->language = $recordDetails[4];
			$this->publisher = $recordDetails[5];
			$this->publicationDate = $recordDetails[6];
			$this->physical = $recordDetails[7];
			$this->placeOfPublication = $recordDetails[8];
			$this->audience = $recordDetails[15];
		}

		if (empty($this->language)) {
			$this->language = 'English';
		}
		$this->source = $source;
		$this->_statusInformation = new Grouping_StatusInformation();
		$this->_statusInformation->setNumHolds($recordDriver->getNumHolds());
		if ($recordDriver instanceof OverDriveRecordDriver) {
			$statusSummary = $recordDriver->getStatusSummary();
			$this->_statusInformation->addCopies($statusSummary['totalCopies']);
			$this->_statusInformation->addAvailableCopies($statusSummary['availableCopies']);
			$this->_statusInformation->setAvailableOnline($statusSummary['available']);
			$this->_isOverDrive = true;
		}
		$this->_volumeHolds = $recordDriver->getVolumeHolds($volumeData);
		$this->_volumeData = $volumeData;
		if (!empty($volumeData)) {
			$this->_volumeData = [];
			foreach ($volumeData as $volumeInfo) {
				if ($volumeInfo->recordId == $this->id) {
					$this->_volumeData[] = $volumeInfo;
				}
			}
		}
		if ($recordDriver instanceof SideLoadedRecord) {
			$this->_statusInformation->setIsShowStatus($recordDriver->isShowStatus());
		} else {
			$this->_statusInformation->setIsShowStatus(true);
		}
	}

	function addItem(Grouping_Item $item) : void {
		$item->setRecord($this);
		$this->_items[] = $item;
		//Update the record with information from the item and from scoping.
		if ($item->isEContent) {
			if (empty($this->_eContentSource)) {
				$this->setEContentSource($item->eContentSource);
			} elseif (!str_contains($this->_eContentSource, $item->eContentSource)){
				$this->setEContentSource($this->_eContentSource . ', ' . $item->eContentSource);
			}
			if ($this->_driver instanceof OverDriveRecordDriver) {
				$this->_driver->setNumHoldsForItem($item);
			}
			$this->setIsEContent(true);
			$this->_statusInformation->setIsEContent(true);
		}
		if (!$this->_isOverDrive) {
			if ($item->available) {
				if ($item->isEContent) {
					$this->_statusInformation->setAvailableOnline(true);
				} else {
					$this->_statusInformation->setAvailable(true);
				}
				$this->_statusInformation->addAvailableCopies($item->numCopies);
			}
		}

		if (!$item->inLibraryUseOnly) {
			$this->_statusInformation->setInLibraryUseOnly(false);
			$this->_statusInformation->setAllLibraryUseOnly(false);
		}
		if ($item->holdable) {
			$this->_holdable = true;
			if ($item->locallyOwned || $item->libraryOwned) {
				$this->_locallyHoldable = true;
			}
			$this->_statusInformation->addHoldableCopies($item->numCopies);
		}

		if (!$this->_isOverDrive) {
			if ($item->isOrderItem) {
				$this->addOnOrderCopies($item->numCopies);
			} else {
				if (!$item->isVirtual) {
					$this->addCopies($item->numCopies);
				}
			}

			$searchLocation = Location::getSearchLocation();
			if ($searchLocation != null) {
				if ($item->locallyOwned) {
					$this->_statusInformation->setIsLocallyOwned(true);
					$this->_statusInformation->addLocalCopies($item->numCopies);
					if ($item->available) {
						$this->_statusInformation->setAvailableLocally(true);
						if (!$item->isEContent) {
							global $locationSingleton;
							$physicalLocation = $locationSingleton->getPhysicalLocation();
							if (!empty($physicalLocation)) {
								$this->_statusInformation->setAvailableHere(true);
							}
						}
					}
				}
				if ($item->libraryOwned) {
					$this->_statusInformation->setIsLibraryOwned(true);
				}
			} else {
				if ($item->libraryOwned) {
					$this->_statusInformation->setIsLibraryOwned(true);
					$this->_statusInformation->addLocalCopies($item->numCopies);
					if ($item->available) {
						$this->_statusInformation->setAvailableLocally(true);
					}
				}
			}
		}

		$this->_statusInformation->setGroupedStatus(GroupedWorkDriver::keepBestGroupedStatus($this->getStatusInformation()->getGroupedStatus(), $item->groupedStatus));

		if (!empty($this->_volumeData)) {
			foreach ($this->_volumeData as $volumeInfo) {
				if ((strlen($volumeInfo->relatedItems) != 0) && (str_contains($volumeInfo->relatedItems, $item->itemId))) {
					$item->volume = $volumeInfo->displayLabel;
					$item->volumeId = $volumeInfo->volumeId;
					$item->volumeOrder = $volumeInfo->displayOrder;
				}
			}
		}
	}

	function getSchemaOrgBookFormat() : string {
		return match ($this->format) {
			'Book', 'Large Print', 'Manuscript' => 'Hardcover',
			'Audio', 'Audio Cassette', 'Audio CD', 'CD', 'eAudiobook', 'Playaway' => 'AudiobookFormat',
			'eBook', 'eMagazine' => 'EBook',
			'Graphic Novel', 'Journal' => 'Paperback',
			default => '',
		};
	}

	function getSchemaOrgType() : string {
		return match ($this->format) {
			'Audio', 'Audio Book', 'Audio Cassette', 'Audio CD', 'Book', 'Book Club Kit', 'eAudiobook', 'eBook', 'eMagazine', 'CD', 'Journal', 'Large Print', 'Manuscript', 'Musical Score', 'Newspaper', 'Playaway', 'Serial' => 'Book',
			'eComic', 'Graphic Novel' => 'ComicStory',
			'eMusic', 'Music Recording', 'Phonograph' => 'MusicRecording',
			'Blu-ray', 'DVD', 'eVideo', 'VHS', 'Video' => 'Movie',
			'Map' => 'Map',
			'Nintendo 3DS', 'Nintendo DS', 'Nintendo Switch', 'Nintendo Switch 2', 'Nintendo Wii', 'Nintendo Wii U', 'PlayStation', 'PlayStation 2', 'PlayStation 3', 'PlayStation 4', 'PlayStation 5', 'PlayStation Vita', 'Windows Game', 'Xbox 360', 'Xbox 360 Kinect', 'Xbox One', 'Xbox Series X' => 'Game',
			'Web Content' => 'WebPage',
			default => 'CreativeWork',
		};
	}

	/**
	 * @return int
	 */
	public function getAvailableCopies(): int {
		return $this->_statusInformation->getAvailableCopies();
	}

	/**
	 * @return int
	 */
	public function getCopies(): int {
		return $this->_statusInformation->getCopies();
	}

	/**
	 * @param int $copies
	 */
	public function addCopies(int $copies): void {
		$this->_statusInformation->addCopies($copies);
	}

	/**
	 * @return bool
	 */
	public function isHoldable(): bool {
		return $this->_holdable;
	}

	/**
	 * @return bool
	 */
	public function isLocallyHoldable(): bool {
		return $this->_locallyHoldable;
	}

	/**
	 * @return bool
	 */
	public function hasLocalItem(): bool {
		return $this->_hasLocalItem;
	}

	/**
	 * @param string $variationId The variation to get the item summary for
	 * @return array
	 */
	public function getItemSummary(string $variationId = '') : array {
		if ($variationId == '') {
			$variationId = 'any';
		}
		if (!array_key_exists($variationId, $this->_itemSummary)) {
			//Load details and summary
			$this->getItemDetails($variationId);
		}
		return $this->_itemSummary[$variationId];
	}

	public function getItemsDisplayedByDefault(): array {
		if ($this->_itemsDisplayedByDefault == null) {
			//Make sure everything gets initialized
			$this->getItemDetails();
		}
		return $this->_itemsDisplayedByDefault;
	}

	public function hasItemSummary($variationId, $itemKey): bool {
		return isset($this->_itemSummary[$variationId][$itemKey]);
	}

	public function addItemSummary($variationId, $key, $itemSummaryInfo, $groupedStatus): void {
		if ($this->hasItemSummary($variationId, $key)) {
			$this->_itemSummary[$variationId][$key]['totalCopies'] += $itemSummaryInfo['totalCopies'];
			$this->_itemSummary[$variationId][$key]['availableCopies'] += $itemSummaryInfo['availableCopies'];
			$this->_itemSummary[$variationId][$key]['available'] = $this->_itemSummary[$variationId][$key]['availableCopies'] > 0;
			if ($itemSummaryInfo['displayByDefault']) {
				$this->_itemSummary[$variationId][$key]['displayByDefault'] = true;
			}
			$this->_itemSummary[$variationId][$key]['onOrderCopies'] += $itemSummaryInfo['onOrderCopies'];
			$lastStatus = $this->_itemSummary[$variationId][$key]['status'];
			$this->_itemSummary[$variationId][$key]['status'] = GroupedWorkDriver::keepBestGroupedStatus($lastStatus, $groupedStatus);
			if ($lastStatus != $this->_itemSummary[$variationId][$key]['status']) {
				$this->_itemSummary[$variationId][$key]['statusFull'] = $itemSummaryInfo['statusFull'];
			}
			$this->_itemSummary[$variationId][$key]['numHolds'] += $itemSummaryInfo['numHolds'];
		} else {
			if (!isset($this->_itemSummary[$variationId])) {
				$this->_itemSummary[$variationId] = [];
			}
			$this->_itemSummary[$variationId][$key] = $itemSummaryInfo;
		}

		if ($this->_itemsDisplayedByDefault == null) {
			$this->_itemsDisplayedByDefault = [];
		}
		if ($itemSummaryInfo['displayByDefault']) {
			if (isset($this->_itemsDisplayedByDefault[$key])) {
				$this->_itemsDisplayedByDefault[$key]['totalCopies'] += $itemSummaryInfo['totalCopies'];
				$this->_itemsDisplayedByDefault[$key]['availableCopies'] += $itemSummaryInfo['availableCopies'];
				$this->_itemsDisplayedByDefault[$key]['available'] = $this->_itemsDisplayedByDefault[$key]['availableCopies'] > 0;
				$this->_itemsDisplayedByDefault[$key]['onOrderCopies'] += $itemSummaryInfo['onOrderCopies'];
				$lastStatus = $this->_itemsDisplayedByDefault[$key]['status'];
				$this->_itemsDisplayedByDefault[$key]['status'] = GroupedWorkDriver::keepBestGroupedStatus($lastStatus, $groupedStatus);
				if ($lastStatus != $this->_itemsDisplayedByDefault[$key]['status']) {
					$this->_itemsDisplayedByDefault[$key]['statusFull'] = $itemSummaryInfo['statusFull'];
				}
			} else {
				$this->_itemsDisplayedByDefault[$key] = $itemSummaryInfo;
			}
		}
	}

	public function sortItemSummary($variationId): void {
		global $library;
		$ils = 'Unknown';
		if ($library->getAccountProfile() != null) {
			$ils = $library->getAccountProfile()->ils;

		}
		$isPeriodical = false;
		$format = $this->format;
		require_once ROOT_DIR . '/sys/Indexing/FormatMapValue.php';
		if ($ils == 'sierra' || $ils == 'millennium') {
			$formatValue = new FormatMapValue();
			$formatValue->format = $format;
			$formatValue->displaySierraCheckoutGrid = 1;
			if ($formatValue->find(true)) {
				$isPeriodical = true;
			}
		} else {
			if ($format == 'Journal' || $format == 'Newspaper' || $format == 'Print Periodical' || $format == 'Magazine') {
				$isPeriodical = true;
			}
		}
		require_once ROOT_DIR . '/sys/Utils/GroupingUtils.php';
		if ($isPeriodical) {
			$this->_itemSummary[$variationId] = sortPeriodicalItemsByShelfLocationAndCallNumber($this->_itemSummary[$variationId]);
		}else{
			$this->_itemSummary[$variationId] = sortItemsByShelfLocationAndCallNumber($this->_itemSummary[$variationId]);
		}
	}

	/**
	 * @param string $itemId The ID to return
	 * @return ?Grouping_Item
	 */
	public function getItemById(string $itemId = ''): ?Grouping_Item {
		foreach ($this->_items as $item) {
			if ($item->itemId == $itemId) {
				return $item;
			}
		}
		return null;
	}

	/**
	 * @param string $variationId The variation to return
	 * @return array
	 */
	public function getItemDetails(string $variationId = ''): array {
		if (empty($variationId)) {
			$variationId = 'any';
		}
		if (!array_key_exists($variationId, $this->_itemDetails)) {
			$this->_itemDetails[$variationId] = [];
			if (!isset($this->_itemSummary[$variationId])) {
				$this->_itemSummary[$variationId] = [];
			}
			foreach ($this->_items as $item) {
				if (!$item->isVirtual && ($variationId == 'any' || $variationId == $item->variationId)) {
					$key = $item->getSummaryKey();
					$itemSummary = $item->getSummary();
					//Get the correct variation
					$itemSummary['format'] = $this->getFormat();
					foreach ($this->recordVariations as $variationLabel => $recordVariation) {
						if ($recordVariation->databaseId == $item->variationId) {
							$itemSummary['format'] = $variationLabel;
							break;
						}
					}
					$this->addItemDetails($variationId,$key . $item->itemId, $itemSummary);
					$this->addItemSummary($variationId, $key, $itemSummary, $item->groupedStatus);
				}
			}
			$this->sortItemDetails($variationId);
			$this->sortItemSummary($variationId);
			if ($this->_itemsDisplayedByDefault == null) {
				$this->_itemsDisplayedByDefault = [];
			}
		}
		return $this->_itemDetails[$variationId];
	}

	public function addItemDetails($variationId, $key, $itemSummaryInfo): void {
		if (!array_key_exists($variationId, $this->_itemDetails)) {
			$this->_itemDetails[$variationId] = [];
		}
		$this->_itemDetails[$variationId][$key] = $itemSummaryInfo;
	}

	public function sortItemDetails($variationId): void {
		ksort($this->_itemDetails[$variationId], SORT_NATURAL);
	}

	/**
	 * @return string
	 */
	public function getShelfLocation(): string {
		return $this->_shelfLocation;
	}

	private array $_allActions = [];

	/**
	 * @param ?string $variationId The variation to get actions for
	 * @return array
	 */
	public function getActions(?string $variationId = ''): array {
		if (empty($variationId)) {
			$variationId = 'any';
		}
		if (!array_key_exists($variationId, $this->_allActions)) {
			$this->_allActions[$variationId] = [];

			//TODO: Add volume information
			if ($this->getDriver() != null) {
				$this->setActions($variationId, $this->getDriver()->getRecordActions($this, $variationId, $this->getStatusInformation()->isAvailableLocally() || $this->getStatusInformation()->isAvailableOnline(), $this->isHoldable(), []));
			}

			$actionsToReturn = $this->_actions[$variationId];
			if (is_null($actionsToReturn)) {
				$actionsToReturn = [];
			}
			if (empty($actionsToReturn) && $this->getDriver() != null) {
				foreach ($this->_items as $item) {
					if ($item->variationId == $variationId || $variationId == 'any') {
						$item->setActions($this->getDriver()->getItemActions($item));
						$actionsToReturn = array_merge($actionsToReturn, $item->getActions());
					}
				}
			}

			$this->_allActions[$variationId] = $actionsToReturn;
		}

		return $this->_allActions[$variationId];
	}

	/**
	 * @param string|null $variationId The Variation ID to set the actions for
	 * @param array $actions
	 */
	public function setActions(?string $variationId, array $actions): void {
		if ($variationId == null) {
			$variationId = 'any';
		}
		$this->_actions[$variationId] = $actions;
	}

	/**
	 * @return ?string
	 */
	public function getEContentSource() : ?string {
		return $this->_eContentSource;
	}

	/**
	 * @param ?string $eContentSource
	 */
	public function setEContentSource(?string $eContentSource): void {
		$this->_eContentSource = $eContentSource;
	}

	/**
	 * @return bool
	 */
	public function isEContent(): bool {
		return $this->_isEContent;
	}

	/**
	 * @param bool $isEContent
	 */
	public function setIsEContent(bool $isEContent): void {
		$this->_isEContent = $isEContent;
	}

	/** @noinspection PhpUnused */
	public function showCopySummary() : bool {
		if (!$this->_isEContent) {
			return true;
		}else{
			//For eContent, we will only show if there is more than one item
			if (count($this->getItems()) > 1) {
				return true;
			}
		}
		return false;
	}

	private ?float $_holdRatio = null;
	/**
	 * @return float
	 */
	function getHoldRatio(): float {
		if ($this->_holdRatio == null) {
			if ($this->getCopies() > 0) {
				$this->_holdRatio = $this->_statusInformation->getNumHolds() / $this->getCopies();
			} else {
				$this->_holdRatio = 0;
			}
		}
		return $this->_holdRatio;
	}

	/**
	 * @return string
	 */
	function getUrl(): string {
		return $this->_url;
	}


	function getStatusInformation(): Grouping_StatusInformation {
		return $this->_statusInformation;
	}

	function isAvailable() : bool {
		return $this->_statusInformation->isAvailable();
	}

	function isAvailableOnline() : bool {
		return $this->_statusInformation->isAvailableOnline();
	}

	function getGroupedStatus(): string {
		return $this->_statusInformation->getGroupedStatus();
	}

	private ?float $statusRanking = null;
	function getStatusRanking(): float {
		if (is_null($this->statusRanking)) {
			if (isset(GroupedWorkDriver::$statusRankings[$this->getGroupedStatus()])) {
				$this->statusRanking = GroupedWorkDriver::$statusRankings[$this->getGroupedStatus()];
			}else{
				return 3.5;
			}
		}
		return $this->statusRanking;
	}

	function addOnOrderCopies($numCopies) :void {
		$this->_statusInformation->addOnOrderCopies($numCopies);
	}

	/**
	 * @return null|GroupedWorkSubDriver
	 */
	function getDriver() : ?GroupedWorkSubDriver{
		if (is_null($this->_driver)) {
			require_once ROOT_DIR . '/RecordDrivers/RecordDriverFactory.php';
			$this->_driver = RecordDriverFactory::initRecordDriverById($this->id);
		}
		return $this->_driver;
	}

	/**
	 * @return Grouping_Item[]
	 */
	public function getItems() : array {
		return $this->_items;
	}

	/**
	 * @return IlsVolumeInfo[]
	 */
	public function getUnsuppressedVolumeData(bool $excludeNonHoldableItems = false) : array {
		if (is_null($this->_unsuppressedVolumeData)) {
			$this->_unsuppressedVolumeData = [];
			foreach ($this->_volumeData as $key => $volumeInfo) {
				foreach ($this->_items as $item) {
					if ($excludeNonHoldableItems && !$item->holdable) continue;
					if ($item->volumeId == $volumeInfo->volumeId) {
						$this->_unsuppressedVolumeData[$key] = $volumeInfo;
						break;
					}
				}
			}
		}
		return $this->_unsuppressedVolumeData;
	}

	/**
	 * @return IlsVolumeInfo[]
	 */
	public function getUnsuppressedLocallyOwnedVolumes() : array{
		if (is_null($this->_unsuppressedLocalVolumeData)) {
			$this->_unsuppressedLocalVolumeData = [];
			foreach ($this->_volumeData as $key => $volumeInfo) {
				foreach ($this->_items as $item) {
					if ($item->volumeId == $volumeInfo->volumeId && $this->isLocallyOwned()) {
						$this->_unsuppressedLocalVolumeData[$key] = $volumeInfo;
						break;
					}
				}
			}
		}
		return $this->_unsuppressedLocalVolumeData;
	}

	/**
	 * @return string
	 */
	public function getFormat(): string {
		return $this->format ?? 'Unknown';
	}

	public function isLocallyOwned(): bool {
		return $this->_statusInformation->isLocallyOwned();
	}


	public function isLibraryOwned(): bool {
		return $this->_statusInformation->isLibraryOwned();
	}

	public function getHoldPickupSetting() {
		$result = 0;
		global $indexingProfiles;

		if (count($this->recordVariations) > 1){
			foreach ($this->recordVariations as $variation) {
				$formatValue = $variation->manifestation->format;
				if (array_key_exists($this->source, $indexingProfiles)) {
					$indexingProfile = $indexingProfiles[$this->source];
					$formatMap = $indexingProfile->formatMap;
					//Loop through the format map
					/** @var FormatMapValue $formatMapValue */
					//Check for a format with a hold type that is not 'none'
					foreach ($formatMap as $formatMapValue) {
						if (strcasecmp($formatMapValue->format, $formatValue) === 0) {
							$holdType = $formatMapValue->holdType;
							if ($holdType != 'none') {
								return max($result, $formatMapValue->pickupAt);
							}
						}
					}
				}
			}
		}
		//if only one variation or if all hold types are 'none'
		if (array_key_exists($this->source, $indexingProfiles)) {
			$indexingProfile = $indexingProfiles[$this->source];
			$formatMap = $indexingProfile->formatMap;
			//Loop through the format map to figure out if there are restrictions on the pickup location
			//Grab the first value we find (if a format is listed multiple times with inconsistent pickup location restrictions,
			//we don't handle that currently).
			/** @var FormatMapValue $formatMapValue */
			foreach ($formatMap as $formatMapValue) {
				if (strcasecmp($formatMapValue->format, $this->format) === 0) {
					return max($result, $formatMapValue->pickupAt);
				}
			}
		}
		return $result;
	}

	public function discardDriver() : void {
		$this->_driver = null;
	}

	public function getBookcoverUrl(string $size) : ?string {
		$bookcoverUrl = null;
		$recordDriver = $this->getDriver();
		if ($recordDriver) {
			$bookcoverUrl = $recordDriver->getBookcoverUrl($size);
		}
		return $bookcoverUrl;
	}

	public function getSortablePublicationDate() : int|string {
		if ($this->sortablePublicationDate === null) {
			if ($this->publicationDate == null) {
				$this->sortablePublicationDate = 0;
			}else{
				$this->sortablePublicationDate = preg_replace('/[^0-9]/', '', $this->publicationDate);
			}
		}
		return $this->sortablePublicationDate;
	}
}
