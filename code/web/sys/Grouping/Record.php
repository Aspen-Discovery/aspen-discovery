<?php

require_once ROOT_DIR . '/sys/Grouping/StatusInformation.php';
require_once ROOT_DIR . '/sys/Grouping/Item.php';

class Grouping_Record {
	public $id;
	public $databaseId;

	public $format;
	public $formatCategory;
	public $edition;
	public $language;
	public $publisher;
	public $publicationDate;
	public $placeOfPublication;
	public $physical;
	public $closedCaptioned;
	public $variationFormat;
	public $variationId;
	/** @var Grouping_Variation[] */
	public $recordVariations;
	public $hasParentRecord;
	public $hasChildRecord;

	protected $_driver;
	protected $_url;
	protected $_callNumber = '';

	/** @var Grouping_StatusInformation */
	protected $_statusInformation;

	protected $_isEContent = false;
	public $_eContentSource;
	public $_volumeHolds;
	public $_hasLocalItem = false;
	public $_holdRatio = 0;
	public $_shelfLocation = '';
	public $_holdable = false;
	public $_locallyHoldable = false;
	public $_itemSummary = [];
	public $_itemsDisplayedByDefault = null;
	public $_itemDetails = [];

	public $source;
	public $_class = '';
	public $_actions = [];
	/** @var Grouping_Item[] */
	private $_items;

	/** @var  IlsVolumeInfo[] */
	private $_volumeData;
	private $_unsuppressedVolumeData = null;
	private $_unsuppressedLocalVolumeData = null;

	//Is the record an OverDrive record?
	//If so, the number of owned and available copies are already set.
	private $_isOverDrive = false;

	/**
	 * Grouping_Record constructor.
	 * @param string $recordId
	 * @param array $recordDetails
	 * @param GroupedWorkSubDriver $recordDriver
	 * @param IlsVolumeInfo[] $volumeData
	 * @param string $source
	 * @param bool $useAssociativeArray
	 */
	public function __construct(string $recordId, array $recordDetails, GroupedWorkSubDriver $recordDriver, array $volumeData, string $source, bool $useAssociativeArray = false, $variation = null) {
		$this->_driver = $recordDriver;
		$this->_url = $recordDriver != null ? $recordDriver->getRecordUrl() : '';
		$this->id = $recordId;
		if ($useAssociativeArray) {
			//Loaded from Database
			$this->format = $recordDetails['format'];
			$this->formatCategory = $recordDetails['formatCategory'];
			$this->databaseId = $recordDetails['id'];
			$this->edition = $recordDetails['edition'];
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
		$this->_volumeHolds = $recordDriver != null ? $recordDriver->getVolumeHolds($volumeData) : null;
		$this->_volumeData = $volumeData;
		if (!empty($volumeData)) {
			$this->_volumeData = [];
			foreach ($volumeData as $volumeInfo) {
				if ($volumeInfo->recordId == $this->id) {
					$this->_volumeData[] = $volumeInfo;
				}
			}
		}
		if ($recordDriver != null && $recordDriver instanceof SideLoadedRecord) {
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

		if ($this->_isOverDrive == false) {
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
						$this->_statusInformation->addAvailableCopies($item->numCopies);
						$this->_statusInformation->setAvailableHere(true);
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
						$this->_statusInformation->addAvailableCopies($item->numCopies);
						$this->_statusInformation->setAvailableLocally(true);
					}
				}
			}
		}

		$this->_statusInformation->setGroupedStatus(GroupedWorkDriver::keepBestGroupedStatus($this->getStatusInformation()->getGroupedStatus(), $item->groupedStatus));

		if (!empty($this->_volumeData)) {
			foreach ($this->_volumeData as $volumeInfo) {
				if ((strlen($volumeInfo->relatedItems) != 0) && (strpos($volumeInfo->relatedItems, $item->itemId) !== false)) {
					$item->volume = $volumeInfo->displayLabel;
					$item->volumeId = $volumeInfo->volumeId;
					$item->volumeOrder = $volumeInfo->displayOrder;
				}
			}
		}
	}

	function getSchemaOrgBookFormat() {
		switch ($this->format) {
			case 'Book':
			case 'Large Print':
			case 'Manuscript':
				return 'Hardcover';

			case 'Audio':
			case 'Audio Cassette':
			case 'Audio CD':
			case 'CD':
			case 'eAudiobook':
			case 'Playaway':
				return 'AudiobookFormat';

			case 'eBook':
			case 'eMagazine':
				return 'EBook';

			case 'Graphic Novel':
			case 'Journal':
				return 'Paperback';

			default:
				return '';
		}
	}

	function getSchemaOrgType() {
		switch ($this->format) {
			case 'Audio':
			case 'Audio Book':
			case 'Audio Cassette':
			case 'Audio CD':
			case 'Book':
			case 'Book Club Kit':
			case 'eAudiobook':
			case 'eBook':
			case 'eMagazine':
			case 'CD':
			case 'Journal':
			case 'Large Print':
			case 'Manuscript':
			case 'Musical Score':
			case 'Newspaper':
			case 'Playaway':
			case 'Serial':
				return 'Book';

			case 'eComic':
			case 'Graphic Novel':
				return 'ComicStory';

			case 'eMusic':
			case 'Music Recording':
			case 'Phonograph':
				return 'MusicRecording';

			case 'Blu-ray':
			case 'DVD':
			case 'eVideo':
			case 'VHS':
			case 'Video':
				return 'Movie';

			case 'Map':
				return 'Map';

			case 'Nintendo 3DS':
			case 'Nintendo Wii':
			case 'Nintendo Wii U':
			case 'PlayStation':
			case 'PlayStation 3':
			case 'PlayStation 4':
			case 'Windows Game':
			case 'Xbox 360':
			case 'Xbox 360 Kinect':
			case 'Xbox One':
				return 'Game';

			case 'Web Content':
				return 'WebPage';

			default:
				return 'CreativeWork';
		}
	}

	/**
	 * @return int
	 */
	public function getAvailableCopies(): int {
		return $this->_statusInformation->getAvailableCopies();
	}

	/**
	 * @param int $availableCopies
	 */
	public function addAvailableCopies(int $availableCopies): void {
		$this->_statusInformation->addAvailableCopies($availableCopies);
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
	 * @param bool $holdable
	 */
	public function setHoldable(bool $holdable): void {
		$this->_holdable = $holdable;
	}

	/**
	 * @return bool
	 */
	public function isLocallyHoldable(): bool {
		return $this->_locallyHoldable;
	}

	/**
	 * @param bool $locallyHoldable
	 */
	public function setLocallyHoldable(bool $locallyHoldable): void {
		$this->_locallyHoldable = $locallyHoldable;
	}

	/**
	 * @return string
	 */
	public function getClass(): string {
		return $this->_class;
	}

	/**
	 * @param string $class
	 */
	public function setClass(string $class): void {
		$this->_class = $class;
	}

	/**
	 * @return int
	 */
	public function getLocalCopies(): int {
		return $this->_statusInformation->getLocalCopies();
	}

	/**
	 * @param int $localCopies
	 */
	public function addLocalCopies(int $localCopies): void {
		$this->_statusInformation->addLocalCopies($localCopies);
	}

	/**
	 * @return bool
	 */
	public function hasLocalItem(): bool {
		return $this->_hasLocalItem;
	}

	/**
	 * @param bool $hasLocalItem
	 */
	public function setHasLocalItem(bool $hasLocalItem): void {
		$this->_hasLocalItem = $hasLocalItem;
	}

	/**
	 * @return array
	 */
	public function getItemSummary($variationId = '') : array {
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
		if ($isPeriodical) {
			$sorter = function ($a, $b){
				if ($a['shelfLocation'] == $b['shelfLocation']) {
					if ($a['callNumber'] == $b['callNumber']) {
						return 0;
					}
					return strnatcasecmp($b['callNumber'], $a['callNumber']);
				}
				return strnatcasecmp($a['shelfLocation'], $b['shelfLocation']);
			};
			uasort($this->_itemSummary[$variationId], $sorter);
		} else {
			ksort($this->_itemSummary[$variationId], SORT_NATURAL);
		}
	}

	/**
	 * @return Grouping_Item
	 */
	public function getItemById($itemId = ''): ?Grouping_Item {
		if ($this->_items != null) {
			foreach ($this->_items as $item) {
				if ($item->itemId == $itemId) {
					return $item;
				}
			}
		}
		return null;
	}

	/**
	 * @return array
	 */
	public function getItemDetails($variationId = ''): array {
		if (empty($variationId)) {
			$variationId = 'any';
		}
		if (!array_key_exists($variationId, $this->_itemDetails)) {
			$this->_itemDetails[$variationId] = [];
			if (!isset($this->_itemSummary[$variationId])) {
				$this->_itemSummary[$variationId] = [];
			}
			if ($this->_items != null) {
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

	/**
	 * @param string $shelfLocation
	 */
	public function setShelfLocation(string $shelfLocation): void {
		$this->_shelfLocation = $shelfLocation;
	}

	/**
	 * @return string
	 */
	public function getCallNumber(): string {
		return $this->_callNumber;
	}

	/**
	 * @param string $callNumber
	 */
	public function setCallNumber(string $callNumber): void {
		$this->_callNumber = $callNumber;
	}

	private $_allActions = [];

	/**
	 * @return array
	 */
	public function getActions($variationId = ''): array {
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
				if ($this->_items != null) {
					foreach ($this->_items as $item) {
						if ($item->variationId == $variationId || $variationId == 'any') {
							$item->setActions($this->getDriver()->getItemActions($item));
							$actionsToReturn = array_merge($actionsToReturn, $item->getActions());
						}
					}
				}
			}

			$this->_allActions[$variationId] = $actionsToReturn;
		}

		return $this->_allActions[$variationId];
	}

	/**
	 * @param array $actions
	 */
	public function setActions(?string $variationId, array $actions): void {
		if ($variationId == null) {
			$variationId = 'any';
		}
		$this->_actions[$variationId] = $actions;
	}

	/**
	 * @return mixed
	 */
	public function getEContentSource() {
		return $this->_eContentSource;
	}

	/**
	 * @param mixed $eContentSource
	 */
	public function setEContentSource($eContentSource): void {
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

	/**
	 * @return int
	 */
	function getHoldRatio(): int {
		if ($this->getCopies() > 0) {
			return $this->_statusInformation->getNumHolds() / $this->getCopies();
		} else {
			return 0;
		}
	}

	/**
	 * @return int
	 */
	function getLocalAvailableCopies(): int {
		return $this->_statusInformation->getLocalAvailableCopies();
	}

	/**
	 * @return string
	 */
	function getUrl(): string {
		return $this->_url;
	}

	/**
	 * @param string $url
	 */
	function setUrl(string $url): void {
		$this->_url = $url;
	}

	function getStatusInformation() {
		return $this->_statusInformation;
	}

	function isAvailable() {
		return $this->_statusInformation->isAvailable();
	}

	function isAvailableOnline() {
		return $this->_statusInformation->isAvailableOnline();
	}

	function getGroupedStatus() {
		return $this->_statusInformation->getGroupedStatus();
	}

	/**
	 * @return int
	 */
	function getOnOrderCopies(): int {
		return $this->_statusInformation->getOnOrderCopies();
	}

	function addOnOrderCopies($numCopies) {
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
	 * @return null|Grouping_Item[]
	 */
	public function getItems() : ?array {
		return $this->_items;
	}

	/**
	 * @return IlsVolumeInfo[]
	 */
	public function getVolumeData() : array{
		return $this->_volumeData;
	}

	/**
	 * @return IlsVolumeInfo[]
	 */
	public function getUnsuppressedVolumeData() : array{
		if (is_null($this->_unsuppressedVolumeData)) {
			$this->_unsuppressedVolumeData = [];
			foreach ($this->_volumeData as $key => $volumeInfo) {
				foreach ($this->_items as $item) {
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

	public function isLocallyOwned() {
		return $this->_statusInformation->isLocallyOwned();
	}


	public function isLibraryOwned() {
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
			//We don't handle that currently.
			/** @var FormatMapValue $formatMapValue */
			foreach ($formatMap as $formatMapValue) {
				if (strcasecmp($formatMapValue->format, $this->format) === 0) {
					return max($result, $formatMapValue->pickupAt);
				}
			}
		}
		return $result;
	}

	public function discardDriver() {
		$this->_driver = null;
	}

	public function getBookcoverUrl($size) {
		$bookcoverUrl = null;
		$recordDriver = $this->getDriver();
		if($recordDriver) {
			$bookcoverUrl = $recordDriver->getBookcoverUrl($size);
		}
		return $bookcoverUrl;
	}
}
