<?php

require_once ROOT_DIR . '/sys/Grouping/StatusInformation.php';
require_once ROOT_DIR . '/sys/Grouping/Item.php';

class Grouping_Record
{
	public $id;
	public $databaseId;

	public $format;
	public $formatCategory;
	public $edition;
	public $language;
	public $publisher;
	public $publicationDate;
	public $physical;

	public $_driver;
	public $_url;
	public $_callNumber = '';

	/** @var Grouping_StatusInformation */
	private $_statusInformation;

	public $_isEContent = false;
	public $_eContentSource;
	public $_volumeHolds;
	public $_hasLocalItem = false;
	public $_holdRatio = 0;
	public $_shelfLocation = '';
	public $_holdable = false;
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
	public function __construct($recordId, $recordDetails, $recordDriver, $volumeData, $source, $useAssociativeArray = false)
	{
		$this->_driver = $recordDriver;
		$this->_url = $recordDriver != null ? $recordDriver->getRecordUrl() : '';
		$this->id = $recordId;
		if ($useAssociativeArray){
			$this->format = $recordDetails['format'];
			$this->formatCategory = $recordDetails['formatCategory'];
			$this->databaseId = $recordDetails['id'];
			$this->edition = $recordDetails['edition'];
			$this->publisher = $recordDetails['publisher'];
			$this->publicationDate = $recordDetails['publicationDate'];
			$this->physical = $recordDetails['physicalDescription'];
			$this->language = $recordDetails['language'];
		}else{
			$this->format = $recordDetails[1];
			$this->formatCategory = $recordDetails[2];
			$this->edition = $recordDetails[3];
			$this->language = $recordDetails[4];
			$this->publisher = $recordDetails[5];
			$this->publicationDate = $recordDetails[6];
			$this->physical = $recordDetails[7];
		}

		if (empty($this->language)) {
			$this->language = 'English';
		}
		$this->source = $source;
		$this->_statusInformation = new Grouping_StatusInformation();
		$this->_statusInformation->setNumHolds($recordDriver != null ? $recordDriver->getNumHolds() : 0);
		if ($recordDriver != null && $recordDriver instanceof OverDriveRecordDriver){
			$availability = $recordDriver->getAvailability();
			if ($availability != null) {
				$this->_statusInformation->addCopies($availability->copiesOwned);
				$this->_statusInformation->addAvailableCopies($availability->copiesAvailable);
				$this->_statusInformation->setAvailableOnline($availability->copiesAvailable > 0);
			}else{
				$this->_statusInformation->addCopies(0);
				$this->_statusInformation->addAvailableCopies(0);
				$this->_statusInformation->setAvailableOnline(false);
			}
			$this->_isOverDrive = true;
		}
		$this->_volumeHolds = $recordDriver != null ? $recordDriver->getVolumeHolds($volumeData) : null;
		$this->_volumeData = $volumeData;
		if (!empty($volumeData)) {
			$this->_volumeData = [];
			foreach ($volumeData as $volumeInfo){
				if ($volumeInfo->recordId == $this->id){
					$this->_volumeData[] = $volumeInfo;
				}
			}
		}
		if ($recordDriver != null && $recordDriver instanceof SideLoadedRecord){
			$this->_statusInformation->setIsShowStatus($recordDriver->isShowStatus());
		}else{
			$this->_statusInformation->setIsShowStatus(true);
		}
	}

	function addItem(Grouping_Item $item)
	{
		$item->setRecord($this);
		$this->_items[] = $item;
		//Update the record with information from the item and from scoping.
		if ($item->isEContent) {
			$this->setEContentSource($item->eContentSource);
			$this->setIsEContent(true);
			$this->_statusInformation->setIsEContent(true);
		}
		if ($this->_isOverDrive == false) {
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
		}

		if ($this->_isOverDrive == false) {
			if ($item->isOrderItem) {
				$this->addOnOrderCopies($item->numCopies);
			} else {
				$this->addCopies($item->numCopies);
			}

			$searchLocation = Location::getSearchLocation();
			if ($searchLocation != null) {
				if ($item->locallyOwned) {
					$this->_statusInformation->addLocalCopies($item->numCopies);
					if ($item->available) {
						$this->_statusInformation->addLocalCopies($item->numCopies);
						$this->_statusInformation->setAvailableHere(true);
					}
				}
			} else {
				if ($item->libraryOwned) {
					$this->_statusInformation->addLocalCopies($item->numCopies);
					if ($item->available) {
						$this->_statusInformation->addAvailableCopies($item->numCopies);
						$this->_statusInformation->setAvailableLocally(true);
					}
				}
			}
		}

		$this->_statusInformation->setGroupedStatus(GroupedWorkDriver::keepBestGroupedStatus($this->getStatusInformation()->getGroupedStatus(), $item->groupedStatus));

		if (!empty($this->_volumeData)){
			foreach ($this->_volumeData as $volumeInfo){
				if ((strlen($volumeInfo->relatedItems) != 0) && (strpos($volumeInfo->relatedItems, $item->itemId) !== false)) {
					$item->volume = $volumeInfo->displayLabel;
					$item->volumeId = $volumeInfo->volumeId;
					$item->volumeOrder = $volumeInfo->displayOrder;
				}
			}
		}
	}

	function getSchemaOrgBookFormat()
	{
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

	function getSchemaOrgType()
	{
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
	public function getAvailableCopies(): int
	{
		return $this->_statusInformation->getAvailableCopies();
	}

	/**
	 * @param int $availableCopies
	 */
	public function addAvailableCopies(int $availableCopies): void
	{
		$this->_statusInformation->addAvailableCopies($availableCopies);
	}

	/**
	 * @return int
	 */
	public function getCopies(): int
	{
		return $this->_statusInformation->getCopies();
	}

	/**
	 * @param int $copies
	 */
	public function addCopies(int $copies): void
	{
		$this->_statusInformation->addCopies($copies);
	}

	/**
	 * @return bool
	 */
	public function isHoldable(): bool
	{
		return $this->_holdable;
	}

	/**
	 * @param bool $holdable
	 */
	public function setHoldable(bool $holdable): void
	{
		$this->_holdable = $holdable;
	}

	/**
	 * @return string
	 */
	public function getClass(): string
	{
		return $this->_class;
	}

	/**
	 * @param string $class
	 */
	public function setClass(string $class): void
	{
		$this->_class = $class;
	}

	/**
	 * @return int
	 */
	public function getLocalCopies(): int
	{
		return $this->_statusInformation->getLocalCopies();
	}

	/**
	 * @param int $localCopies
	 */
	public function addLocalCopies(int $localCopies): void
	{
		$this->_statusInformation->addLocalCopies($localCopies);
	}

	/**
	 * @return bool
	 */
	public function hasLocalItem(): bool
	{
		return $this->_hasLocalItem;
	}

	/**
	 * @param bool $hasLocalItem
	 */
	public function setHasLocalItem(bool $hasLocalItem): void
	{
		$this->_hasLocalItem = $hasLocalItem;
	}

	/**
	 * @return array
	 */
	public function getItemSummary(): array
	{
		if (empty($this->_itemSummary)){
			foreach ($this->_items as $item){
				$key = $item->getSummaryKey();
				$itemSummary = $item->getSummary();
				$this->addItemDetails($key, $itemSummary);
				$this->addItemSummary($key, $itemSummary, $item->groupedStatus);
			}
			$this->sortItemDetails();
			$this->sortItemSummary();
		}
		return $this->_itemSummary;
	}

	public function getItemsDisplayedByDefault(): array
	{
		if ($this->_itemsDisplayedByDefault == null) {
			//Make sure everything gets initialized
			$this->getItemDetails();
		}
		return $this->_itemsDisplayedByDefault;
	}

	public function hasItemSummary($itemKey): bool
	{
		return isset($this->_itemSummary[$itemKey]);
	}

	public function addItemSummary($key, $itemSummaryInfo, $groupedStatus): void
	{
		if ($this->hasItemSummary($key)) {
			$this->_itemSummary[$key]['totalCopies'] += $itemSummaryInfo['totalCopies'];
			$this->_itemSummary[$key]['availableCopies'] += $itemSummaryInfo['availableCopies'];
			if ($itemSummaryInfo['displayByDefault']) {
				$this->_itemSummary[$key]['displayByDefault'] = true;
			}
			$this->_itemSummary[$key]['onOrderCopies'] += $itemSummaryInfo['onOrderCopies'];
			$lastStatus = $this->_itemSummary[$key]['status'];
			$this->_itemSummary[$key]['status'] = GroupedWorkDriver::keepBestGroupedStatus($lastStatus, $groupedStatus);
			if ($lastStatus != $this->_itemSummary[$key]['status']) {
				$this->_itemSummary[$key]['statusFull'] = $itemSummaryInfo['statusFull'];
			}
		} else {
			$this->_itemSummary[$key] = $itemSummaryInfo;
		}

		if ($this->_itemsDisplayedByDefault == null) {
			$this->_itemsDisplayedByDefault = [];
		}
		if ($itemSummaryInfo['displayByDefault']){
			if (isset($this->_itemsDisplayedByDefault[$key])){
				$this->_itemsDisplayedByDefault[$key]['totalCopies'] += $itemSummaryInfo['totalCopies'];
				$this->_itemsDisplayedByDefault[$key]['availableCopies'] += $itemSummaryInfo['availableCopies'];
				$this->_itemsDisplayedByDefault[$key]['onOrderCopies'] += $itemSummaryInfo['onOrderCopies'];
				$lastStatus = $this->_itemsDisplayedByDefault[$key]['status'];
				$this->_itemsDisplayedByDefault[$key]['status'] = GroupedWorkDriver::keepBestGroupedStatus($lastStatus, $groupedStatus);
				if ($lastStatus != $this->_itemsDisplayedByDefault[$key]['status']) {
					$this->_itemsDisplayedByDefault[$key]['statusFull'] = $itemSummaryInfo['statusFull'];
				}
			}else{
				$this->_itemsDisplayedByDefault[$key] = $itemSummaryInfo;
			}
		}
	}

	public function sortItemSummary(): void
	{
		ksort($this->_itemSummary);
	}

	/**
	 * @return array
	 */
	public function getItemDetails(): array
	{
		if (empty($this->_itemDetails)){
			foreach ($this->_items as $item){
				$key = $item->getSummaryKey();
				$itemSummary = $item->getSummary();
				$this->addItemDetails($key . $item->itemId, $itemSummary);
				$this->addItemSummary($key, $itemSummary, $item->groupedStatus);
			}
			$this->sortItemDetails();
			$this->sortItemSummary();
		}
		return $this->_itemDetails;
	}

	public function addItemDetails($key, $itemSummaryInfo): void
	{
		$this->_itemDetails[$key] = $itemSummaryInfo;
	}

	public function sortItemDetails(): void
	{
		ksort($this->_itemDetails);
	}

	/**
	 * @return string
	 */
	public function getShelfLocation(): string
	{
		return $this->_shelfLocation;
	}

	/**
	 * @param string $shelfLocation
	 */
	public function setShelfLocation(string $shelfLocation): void
	{
		$this->_shelfLocation = $shelfLocation;
	}

	/**
	 * @return string
	 */
	public function getCallNumber(): string
	{
		return $this->_callNumber;
	}

	/**
	 * @param string $callNumber
	 */
	public function setCallNumber(string $callNumber): void
	{
		$this->_callNumber = $callNumber;
	}

	private $_allActions = null;

	/**
	 * @return array
	 */
	public function getActions(): array
	{
		if ($this->_allActions == null) {
			//TODO: Add volume information
			if ($this->_driver != null) {
				$this->setActions($this->_driver->getRecordActions($this, $this->getStatusInformation()->isAvailableLocally() || $this->getStatusInformation()->isAvailableOnline(), $this->isHoldable(), []));
			}
			
			$actionsToReturn = $this->_actions;
			if (empty($this->_actions) && $this->_driver != null){
				foreach ($this->_items as $item) {
					$item->setActions($this->_driver->getItemActions($item));
					$actionsToReturn = array_merge($actionsToReturn, $item->getActions());
				}
			}

			$this->_allActions = $actionsToReturn;
		}

		return $this->_allActions;
	}

	/**
	 * @param array $actions
	 */
	public function setActions(array $actions): void
	{
		$this->_actions = $actions;
	}

	/**
	 * @return mixed
	 */
	public function getEContentSource()
	{
		return $this->_eContentSource;
	}

	/**
	 * @param mixed $eContentSource
	 */
	public function setEContentSource($eContentSource): void
	{
		$this->_eContentSource = $eContentSource;
	}

	/**
	 * @return bool
	 */
	public function isEContent(): bool
	{
		return $this->_isEContent;
	}

	/**
	 * @param bool $isEContent
	 */
	public function setIsEContent(bool $isEContent): void
	{
		$this->_isEContent = $isEContent;
	}

	/**
	 * @return int
	 */
	function getHoldRatio(): int
	{
		if ($this->getCopies() > 0) {
			return $this->_statusInformation->getNumHolds() / $this->getCopies();
		}else{
			return 0;
		}
	}

	/**
	 * @return int
	 */
	function getLocalAvailableCopies(): int
	{
		return $this->_statusInformation->getLocalAvailableCopies();
	}

	/**
	 * @return string
	 */
	function getUrl(): string
	{
		return $this->_url;
	}

	/**
	 * @param string $url
	 */
	function setUrl(string $url): void
	{
		$this->_url = $url;
	}

	function getStatusInformation()
	{
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
	function getOnOrderCopies(): int
	{
		return $this->_statusInformation->getOnOrderCopies();
	}

	function addOnOrderCopies($numCopies)
	{
		$this->_statusInformation->addOnOrderCopies($numCopies);
	}

	/**
	 * @return null|GroupedWorkSubDriver
	 */
	function getDriver()
	{
		return $this->_driver;
	}

	public function getItems()
	{
		return $this->_items;
	}

	public function getVolumeData()
	{
		return $this->_volumeData;
	}

	/**
	 * @return string
	 */
	public function getFormat(): string
	{
		return $this->format;
	}



}