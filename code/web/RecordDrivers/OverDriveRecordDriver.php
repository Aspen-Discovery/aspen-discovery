<?php

require_once ROOT_DIR . '/RecordDrivers/RecordInterface.php';
require_once ROOT_DIR . '/RecordDrivers/GroupedWorkSubDriver.php';

class OverDriveRecordDriver extends GroupedWorkSubDriver {
	protected string $id;
	//This will be either blank or kindle for now
	private ?string $subSource = null;
	private ?OverDriveAPIProduct $overDriveProduct = null;
	private ?OverDriveAPIProductMetaData $overDriveMetaData = null;
	private bool $valid;

	/** @var string[]|null  */
	private ?array $isbns = null;

	/** @var string[]|null  */
	private ?array $upcs = null;

	/** @var OverDriveAPIProductFormats[]|null  */
	private ?array $items = null;

	//$groupedWork and $groupedWorkDriver are defined in GroupedWorkSubDriver

	/**
	 * Constructor.  We build the object using all the data retrieved
	 * from the (Solr) index.  Since we have to
	 * make a search call to find out which record driver to construct,
	 * we will already have this data available, so we might as well
	 * just pass it into the constructor.
	 *
	 * @param string $recordId The id of the record within OverDrive.
	 * @param GroupedWork|null $groupedWork ;
	 * @access  public
	 */
	public function __construct($recordId, ?GroupedWork $groupedWork = null) {
		if (is_string($recordId)) {
			//The record is the identifier for the overdrive title
			//Check to see if we have a subSource
			if (strpos($recordId, ':') > 0) {
				[
					$this->subSource,
					$recordId,
				] = explode(':', $recordId);
			}
			$this->id = $recordId;
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProduct.php';
			$this->overDriveProduct = new OverDriveAPIProduct();
			$this->overDriveProduct->overdriveId = $recordId;
			if ($this->overDriveProduct->find(true)) {
				$this->valid = true;
			} else {
				$this->valid = false;
			}
		} else {
			$this->valid = false;
		}
		if ($this->valid) {
			parent::__construct($groupedWork);
		}
	}

	public function getIdWithSource() : string {
		return 'overdrive:' . $this->id;
	}

	public function getModule(): string {
		return 'OverDrive';
	}

	public function getRecordType() : string {
		return 'overdrive';
	}

	/**
	 * Load the grouped work that this record is connected to.
	 */
	public function loadGroupedWork() : void {
		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		$groupedWork = new GroupedWork();
		$query = "SELECT grouped_work.* FROM grouped_work INNER JOIN grouped_work_primary_identifiers ON grouped_work.id = grouped_work_id WHERE type='overdrive' AND identifier = '" . $this->getUniqueID() . "'";
		$groupedWork->query($query);

		if ($groupedWork->getNumResults() == 1) {
			$groupedWork->fetch();
			$this->groupedWork = clone $groupedWork;
		}
	}

	public function getPermanentId() : ?string {
		return $this->getGroupedWorkId();
	}

	public function getGroupedWorkId() : ?string {
		if (!isset($this->groupedWork)) {
			$this->loadGroupedWork();
		}
		if ($this->groupedWork) {
			return $this->groupedWork->permanent_id;
		} else {
			return null;
		}

	}

	public function isValid() : bool {
		return $this->valid;
	}

	function getStatusSummary() : array {
		$availabilityInfo = $this->getAvailabilityInformation();
		$readerName = new OverDriveDriver();
		$readerName = $readerName->getReaderName();

		$holdPosition = 0;

		$availableCopies = 0;
		$totalCopies = 0;
		$onOrderCopies = 0;
		$checkedOut = 0;
		$onHold = 0;
		$wishListSize = 0;
		$numHolds = 0;
		foreach ($availabilityInfo  as $availability) {
			$availableCopies += $availability->copiesAvailable;
			$totalCopies += $availability->copiesOwned;
			$numHolds = $availability->numberOfHolds;
		}

		//Load status summary
		$statusSummary = [];
		$statusSummary['recordId'] = $this->id;
		$statusSummary['totalCopies'] = $totalCopies;
		$statusSummary['onOrderCopies'] = $onOrderCopies;
		$statusSummary['accessType'] = 'overdrive';
		$statusSummary['alwaysAvailable'] = false;

		$statusSummary['availableCopies'] = $availableCopies;
		$statusSummary['isOverDrive'] = true;
		if ($totalCopies >= 999999) {
			$statusSummary['alwaysAvailable'] = true;
		}
		if ($availableCopies > 0) {
			$statusSummary['status'] = "Available from " . $readerName;
			$statusSummary['available'] = true;
			$statusSummary['class'] = 'available';
		} else {
			$statusSummary['status'] = 'Checked Out';
			$statusSummary['available'] = false;
			$statusSummary['class'] = 'checkedOut';
			$statusSummary['isOverDrive'] = true;
		}


		//Determine which buttons to show
		$statusSummary['holdQueueLength'] = $numHolds;
		$statusSummary['numHolds'] = $numHolds;
		$statusSummary['showPlaceHold'] = $availableCopies == 0;
		$statusSummary['showCheckout'] = $availableCopies > 0;
		$statusSummary['showAddToWishlist'] = false;
		$statusSummary['showAccessOnline'] = false;

		$statusSummary['onHold'] = $onHold;
		$statusSummary['checkedOut'] = $checkedOut;
		$statusSummary['holdPosition'] = $holdPosition;
		$statusSummary['wishListSize'] = $wishListSize;

		return $statusSummary;
	}

	public function getSeries() : array {
		$seriesData = $this->getGroupedWorkDriver()->getSeries();
		if ($seriesData == null) {
			$seriesName = isset($this->getOverDriveMetaData()->getDecodedRawData()->series) ? $this->getOverDriveMetaData()->getDecodedRawData()->series : null;
			if ($seriesName != null) {
				$seriesData = [
					'seriesTitle' => $seriesName,
					'fromNovelist' => false,
					'fromSeriesIndex' => false
				];
			}
		}
		return $seriesData;
	}

	/**
	 * Returns the template for the staff view.
	 * @return string
	 */
	public function getStaffView() : string {
		global $interface;

		$interface->assign('bookcoverInfo', $this->getBookcoverInfo());

		$groupedWorkDriver = $this->getGroupedWorkDriver();
		if ($groupedWorkDriver != null) {
			$this->getGroupedWorkDriver()->assignGroupedWorkStaffView();
			if ($groupedWorkDriver->isValid()) {
				$interface->assign('hasValidGroupedWork', true);
				$this->getGroupedWorkDriver()->assignGroupedWorkStaffView();

				require_once ROOT_DIR . '/sys/Grouping/NonGroupedRecord.php';
				$nonGroupedRecord = new NonGroupedRecord();
				$nonGroupedRecord->source = $this->getRecordType();
				$nonGroupedRecord->recordId = $this->id;
				if ($nonGroupedRecord->find(true)) {
					$interface->assign('isUngrouped', true);
					$interface->assign('ungroupingId', $nonGroupedRecord->id);
				} else {
					$interface->assign('isUngrouped', false);
				}
			} else {
				$interface->assign('hasValidGroupedWork', false);
			}
		} else {
			$interface->assign('hasValidGroupedWork', false);
		}

		$overDriveAPIProduct = new OverDriveAPIProduct();
		$overDriveAPIProduct->overdriveId = strtolower($this->id);
		if ($overDriveAPIProduct->find(true)) {
			$interface->assign('overDriveProduct', $overDriveAPIProduct);
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductMetaData.php';
			$overDriveAPIProductMetaData = new OverDriveAPIProductMetaData();
			$overDriveAPIProductMetaData->productId = $overDriveAPIProduct->id;
			if ($overDriveAPIProductMetaData->find(true)) {
				$overDriveMetadata = $overDriveAPIProductMetaData->rawData;
				//Replace http links to content reserve with https, so we don't get mixed content warnings
				/** @noinspection HttpUrlsUsage */
				$overDriveMetadata = str_replace('http://images.contentreserve.com', 'https://images.contentreserve.com', $overDriveMetadata);
				$overDriveMetadata = json_decode($overDriveMetadata);
				$interface->assign('overDriveMetaDataRaw', $overDriveMetadata);
			}
		}

		$readerName = new OverDriveDriver();
		$readerName = $readerName->getReaderName();
		$interface->assign('readerName', $readerName);

		return 'RecordDrivers/OverDrive/staff.tpl';
	}

	/**
	 * The Table of Contents extracted from the record.
	 * Returns null if no Table of Contents is available.
	 *
	 * @access  public
	 * @return  null|array              Array of elements in the table of contents
	 */
	public function getTableOfContents() : ?array {
		return null;
	}

	/**
	 * Return the unique identifier of this record within the Solr index;
	 * useful for retrieving additional information (like tags and user
	 * comments) from the external MySQL database.
	 *
	 * @access  public
	 * @return  string              Unique identifier.
	 */
	public function getUniqueID() : string {
		return $this->id;
	}

	/**
	 * @return string[]
	 */
	function getLanguage() : array {
		$metaData = $this->getOverDriveMetaData()->getDecodedRawData();
		$languages = [];
		if (isset($metaData->languages)) {
			foreach ($metaData->languages as $language) {
				$languages[] = $language->name;
			}
		}
		return $languages;
	}

	/** @var OverDriveAPIProductAvailability[]|null  */
	private ?array $availability = null;

	/**
	 * @return OverDriveAPIProductAvailability[]
	 */
	function getAvailabilityInformation() : array {
		global $library;
		if ($this->availability == null) {
			$this->availability = [];
			$overDriveScopes = $library->getOverdriveScopeObjects();
			if (!empty($overDriveScopes)) {
				require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductAvailability.php';
				foreach ($overDriveScopes as $overDriveScope) {
					$availability = new OverDriveAPIProductAvailability();
					$availability->productId = $this->overDriveProduct->id;

					$availability->settingId = $overDriveScope->settingId;
					$libraryScopingId = $this->getLibraryScopingId();
					//OverDrive availability now returns correct counts for the library including shared items for each library.
					// Get the correct availability for with either the library (if available) or the shared collection
					$availability->whereAdd("libraryId = $libraryScopingId OR libraryId = -1");
					$availability->orderBy("libraryId DESC");
					if ($availability->find(true)) {
						$this->availability[] = clone $availability;
					}
				}
			}
		}
		return $this->availability;
	}

	public function getLibraryScopingId() : int {
		//For econtent, we need to be more specific when restricting copies
		//since patrons can't use copies that are only available to other libraries.
		$searchLibrary = Library::getSearchLibrary();
		$searchLocation = Location::getSearchLocation();
		$activeLibrary = Library::getActiveLibrary();
		global $locationSingleton;
		$activeLocation = $locationSingleton->getActiveLocation();
		$homeLibrary = Library::getPatronHomeLibrary();

		//Load the holding label for the branch where the user is physically.
		if (!is_null($homeLibrary)) {
			return $homeLibrary->libraryId;
		} elseif (!is_null($activeLocation)) {
			$activeLibrary = Library::getLibraryForLocation($activeLocation->locationId);
			return $activeLibrary->libraryId;
		} elseif (isset($activeLibrary)) {
			return $activeLibrary->libraryId;
		} elseif (!is_null($searchLocation)) {
			return $searchLocation->libraryId;
		} elseif (isset($searchLibrary)) {
			return $searchLibrary->libraryId;
		} else {
			return -1;
		}
	}

	public function getDescriptionFast() {
		$metaData = $this->getOverDriveMetaData();
		return $metaData->fullDescription;
	}

	public function getDescription() {
		$metaData = $this->getOverDriveMetaData();
		return $metaData->fullDescription;
	}

	/**
	 * Return the first valid ISBN found in the record (favoring ISBN-10 over
	 * ISBN-13 when possible).
	 *
	 * @return  mixed
	 */
	public function getCleanISBN() : string {
		require_once ROOT_DIR . '/sys/ISBN.php';

		// Get all the ISBNs and initialize the return value:
		$isbns = $this->getISBNs();
		$isbn13 = false;

		// Loop through the ISBNs:
		foreach ($isbns as $isbn) {
			// Strip off any unwanted notes:
			if ($pos = strpos($isbn, ' ')) {
				$isbn = substr($isbn, 0, $pos);
			}

			// If we find an ISBN-10, return it immediately; otherwise, if we find
			// an ISBN-13, save it if it is the first one encountered.
			$isbnObj = new ISBN($isbn);
			if ($isbn10 = $isbnObj->get10()) {
				return $isbn10;
			}
			if (!$isbn13) {
				$isbn13 = $isbnObj->get13();
			}
		}
		return $isbn13;
	}

	/**
	 * Get an array of all ISBNs associated with the record (might be empty).
	 *
	 * @access  protected
	 * @return  string[]
	 */
	public function getISBNs() : array {
		//Load ISBNs for the product
		if ($this->isbns == null) {
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductIdentifiers.php';
			$overDriveIdentifiers = new OverDriveAPIProductIdentifiers();
			$overDriveIdentifiers->type = 'ISBN';
			$overDriveIdentifiers->productId = $this->overDriveProduct->id;
			$this->isbns = [];
			$overDriveIdentifiers->find();
			while ($overDriveIdentifiers->fetch()) {
				$this->isbns[] = $overDriveIdentifiers->value;
			}
		}
		return $this->isbns;
	}

	public function getOCLCNumber() : string {
		return '';
	}

	/**
	 * Get an array of all UPCs associated with the record (might be empty).
	 *
	 * @access  protected
	 * @return  string[]
	 */
	public function getUPCs() : array {
		//Load UPCs for the product
		if ($this->upcs == null) {
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductIdentifiers.php';
			$overDriveIdentifiers = new OverDriveAPIProductIdentifiers();
			$overDriveIdentifiers->type = 'UPC';
			$overDriveIdentifiers->productId = $this->overDriveProduct->id;
			$this->upcs = [];
			$overDriveIdentifiers->find();
			while ($overDriveIdentifiers->fetch()) {
				$this->upcs[] = $overDriveIdentifiers->value;
			}
		}
		return $this->upcs;
	}

	/** @noinspection PhpUnused */
	public function getSubjects() : array {
		return $this->getOverDriveMetaData()->getDecodedRawData()->subjects ?? [];
	}

	/**
	 * Get the full title of the record.
	 *
	 * @return  string
	 */
	public function getTitle() : string {
		return $this->overDriveProduct->title;
	}

	/**
	 * Get the full title of the record.
	 *
	 * @return  string
	 */
	public function getSortableTitle() : string {
		return $this->overDriveProduct->title;
	}

	public function getShortTitle() : string {
		return $this->overDriveProduct->title;
	}

	public function getSubtitle() : string {
		return $this->overDriveProduct->subtitle;
	}

	/**
	 * @var string[]|null
	 */
	private ?array $_overDriveFormats = null;
	/**
	 * Get an array of all the formats associated with the record.
	 *
	 * @access  protected
	 * @return  string[]
	 * @noinspection PhpUnused
	 */
	public function getOverDriveFormats() : array {
		if ($this->_overDriveFormats == null) {
			$this->_overDriveFormats = [];
			$settingsToProcess = $this->getValidCollectionsForRecord(UserAccount::getActiveUserObj());
			//Look for available actions, we don't want to get duplicate actions for the same reader name
			if (count($settingsToProcess) > 0) {
				$relatedRecord = $this->getRelatedRecord();

				foreach ($settingsToProcess as $settingId => $librarySettings) {
					require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
					$overDriveDriver = OverDriveDriver::getOverDriveDriver($settingId);
					$readerName = $overDriveDriver->getReaderName();
					$this->_overDriveFormats[$readerName . ' ' . $relatedRecord->getFormat()] = $readerName . ' ' . $relatedRecord->getFormat();
				}

				$overDriveFormats = $this->getItems();
				foreach ($overDriveFormats as $format) {
					if ($format->textId == 'ebook-kindle'){
						$this->_overDriveFormats['Kindle'] = 'Kindle';
					}
				}
			}
		}

		return $this->_overDriveFormats;
	}

	/**
	 * Get an array of all the formats associated with the record.
	 *
	 * @access  protected
	 * @return  string[]
	 */
	public function getFormats() : array {
		$relatedRecord = $this->getRelatedRecord();
		$formats = [];
		if ($relatedRecord != null) {
			$formats[$relatedRecord->getFormat()] = $relatedRecord->getFormat();
			if ($this->subSource == 'kindle') {
				$formats[] = 'Kindle';
			}
		}
		return $formats;
	}

	/**
	 * Get an array of all the format categories associated with the record.
	 *
	 * @return  string[]
	 */
	public function getFormatCategory() : array {
		return [$this->getGroupedWorkDriver()->getFormatCategory()];
	}

	/**
	 * TODO: Rename this to something that better represents what it is doing
	 *
	 * @return OverDriveAPIProductFormats[]
	 */
	public function getItems() : array {
		if ($this->items == null) {
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductFormats.php';
			$overDriveFormats = new OverDriveAPIProductFormats();
			$this->items = [];
			if ($this->valid) {
				$overDriveFormats->productId = $this->overDriveProduct->id;
				if ($this->subSource == 'kindle') {
					$overDriveFormats->textId = 'ebook-kindle';
				}
				$overDriveFormats->find();
				while ($overDriveFormats->fetch()) {
					$this->items[] = clone $overDriveFormats;
				}
			}

			global $timer;
			$timer->logTime("Finished getItems for OverDrive record {$this->overDriveProduct->id}");
		}
		return $this->items;
	}

	public function getAuthor() : string {
		return $this->overDriveProduct->primaryCreatorName;
	}

	public function getPrimaryAuthor() : string {
		return $this->overDriveProduct->primaryCreatorName;
	}

	/**
	 * @return string[]
	 */
	public function getContributors() : array {
		$contributors = [];
		$rawData = $this->getOverDriveMetaData()->getDecodedRawData();
		foreach ($rawData->creators as $creator) {
			$contributors[$creator->fileAs] = $creator->fileAs;
		}
		return $contributors;
	}

	private ?array $detailedContributors = null;

	/** @noinspection PhpUnused */
	public function getDetailedContributors() : array {
		if ($this->detailedContributors == null) {
			$this->detailedContributors = [];
			$rawData = $this->getOverDriveMetaData()->getDecodedRawData();
			foreach ($rawData->creators as $creator) {
				if (!array_key_exists($creator->fileAs, $this->detailedContributors)) {
					$this->detailedContributors[$creator->fileAs] = [
						'name' => $creator->fileAs,
						'title' => '',
						'roles' => [],
					];
				}
				$this->detailedContributors[$creator->fileAs]['roles'][] = $creator->role;
			}
		}
		return $this->detailedContributors;
	}

	public function getBookcoverUrl($size = 'small', $absolutePath = false) : string {
		global $configArray;
		if ($absolutePath) {
			$bookCoverUrl = $configArray['Site']['url'];
		} else {
			$bookCoverUrl = '';
		}
		$bookCoverUrl .= '/bookcover.php?size=' . $size;
		$bookCoverUrl .= '&id=' . $this->id;
		$bookCoverUrl .= '&type=overdrive';
		return $bookCoverUrl;
	}

	private function getOverDriveMetaData() : OverDriveAPIProductMetaData{
		if ($this->overDriveMetaData == null) {
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductMetaData.php';
			$this->overDriveMetaData = new OverDriveAPIProductMetaData();
			$this->overDriveMetaData->productId = $this->overDriveProduct->id;
			$this->overDriveMetaData->find(true);
		}
		return $this->overDriveMetaData;
	}

	public function getRatingData() : ?array {
		require_once ROOT_DIR . '/services/API/WorkAPI.php';
		$workAPI = new WorkAPI();
		$groupedWorkId = $this->getGroupedWorkId();
		if ($groupedWorkId == null) {
			return null;
		} else {
			return $workAPI->getRatingData($this->getGroupedWorkId());
		}
	}

	public function getMoreDetailsOptions() : array {
		global $interface;
		global $library;

		$isbn = $this->getCleanISBN();

		$relatedRecord = $this->getRelatedRecord();
		//We have overdrive scopes to process
		$settingsToProcess = $this->getValidCollectionsForRecord(UserAccount::getActiveUserObj());

		$availablePlatforms = [];
		//Look for available actions, we don't want to get duplicate actions for the same reader name
		if (count($settingsToProcess) > 0) {
			foreach ($settingsToProcess as $settingId => $librarySettings) {
				require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
				$overDriveDriver = OverDriveDriver::getOverDriveDriver($settingId);
				$readerName = $overDriveDriver->getReaderName();
				$availablePlatforms[$readerName] = [
					'name' => $readerName,
					'notes' => translate(['text' => 'Titles may be read via %1%. %1% is a free app that allows users to borrow and read digital media from their local library, including ebooks, audiobooks, and magazines. Users can access %1% through the %1% app or online. The app is available for Android and iOS devices.', 1=>$readerName, 'isPublicFacing' => 1])
				];
			}
			$overDriveFormats = $this->getItems();
			foreach ($overDriveFormats as $format) {
				if ($format->textId == 'ebook-kindle'){
					$availablePlatforms['Kindle'] = [
						'name' => 'Kindle',
						'notes' => translate(['text' => 'Titles may be read using Kindle devices or with the Kindle app. ', 'isPublicFacing' => 1])
					];
				}
			}
		}
		$interface->assign('availablePlatforms', $availablePlatforms);

		$availabilityInfo = $this->getAvailabilityInformation();
		$interface->assign('availability', $availabilityInfo);
		if (empty($availabilityInfo)) {
			$showAvailability = false;
			$showAvailabilityOther = false;
		}else{
			$interface->assign('numberOfHolds', $this->getNumHolds());
			$showAvailability = true;
			$showAvailabilityOther = true;
		}
		$interface->assign('showAvailability', $showAvailability);
		$interface->assign('showAvailabilityOther', $showAvailabilityOther);

		//Load more details options
		$moreDetailsOptions = $this->getBaseMoreDetailsOptions($isbn);
		//MDN - 10/18/2024
		$moreDetailsOptions['formats'] = [
			'label' => 'Available Platforms',
			'body' => $interface->fetch('OverDrive/view-platforms.tpl'),
			'openByDefault' => true,
		];
		//Other editions if applicable (only if we aren't the only record!)
		$relatedRecords = $this->getGroupedWorkDriver()->getRelatedRecords();
		if (count($relatedRecords) > 1) {
			$interface->assign('relatedManifestations', $this->getGroupedWorkDriver()->getRelatedManifestations());
			$interface->assign('workId', $this->getGroupedWorkDriver()->getPermanentId());
			$moreDetailsOptions['otherEditions'] = [
				'label' => 'Other Editions and Formats',
				'body' => $interface->fetch('GroupedWork/relatedManifestations.tpl'),
				'hideByDefault' => false,
			];
		}

		$moreDetailsOptions['moreDetails'] = [
			'label' => 'More Details',
			'body' => $interface->fetch('OverDrive/view-more-details.tpl'),
		];
		$moreDetailsOptions['citations'] = [
			'label' => 'Citations',
			'body' => $interface->fetch('Record/cite.tpl'),
		];
		$moreDetailsOptions['copyDetails'] = [
			'label' => 'Copy Details',
			'body' => $interface->fetch('OverDrive/view-copies.tpl'),
		];
		if ($interface->getVariable('showStaffView')) {
			$moreDetailsOptions['staff'] = [
				'label' => 'Staff View',
				'onShow' => "AspenDiscovery.OverDrive.getStaffView('$this->id');",
				'body' => '<div id="staffViewPlaceHolder">' . translate([
						'text' => 'Loading Staff View.',
						'isPublicFacing' => true,
					]) . '</div>',
			];
		}

		return $this->filterAndSortMoreDetailsOptions($moreDetailsOptions);
	}

	public function getRecordUrl() : string {
		$id = $this->getUniqueID();
		if ($this->subSource) {
			$linkUrl = "/OverDrive/$this->subSource:" . $id . '/Home';
		} else {
			$linkUrl = "/OverDrive/" . $id . '/Home';
		}
		return $linkUrl;
	}

	/**
	 * @return string[]
	 */
	function getPublishers() : array {
		$publishers = [];
		if (isset($this->overDriveMetaData->publisher)) {
			$publishers[] = $this->overDriveMetaData->publisher;
		}
		return $publishers;
	}

	/**
	 * @return string[]
	 */
	function getPublicationDates() : array {
		$publicationDates = [];
		if (isset($this->getOverDriveMetaData()->getDecodedRawData()->publishDateText)) {
			$publishDate = $this->getOverDriveMetaData()->getDecodedRawData()->publishDateText;
			$publishYear = substr($publishDate, -4);
			$publicationDates[] = $publishYear;
		}
		return $publicationDates;
	}

	/**
	 * @return string[]
	 */
	function getPlacesOfPublication() : array {
		return [];
	}

	/**
	 * Get an array of publication detail lines combining information from
	 * getPublicationDates(), getPublishers() and getPlacesOfPublication().
	 *
	 * @access  public
	 * @return  string[]
	 */
	function getPublicationDetails() : array {
		$places = $this->getPlacesOfPublication();
		$placesOfPublication = $this->getPlacesOfPublication();
		$names = $this->getPublishers();
		$dates = $this->getPublicationDates();

		$i = 0;
		$returnVal = [];
		while (isset($places[$i]) || isset($placesOfPublication[$i]) || isset($names[$i]) || isset($dates[$i])) {
		// while (isset($places[$i]) || isset($names[$i]) || isset($dates[$i])) {
			// Put all the pieces together, and do a little processing to clean up
			// unwanted whitespace.
			$publicationInfo = (isset($places[$i]) ? $places[$i] . ' ' : '') . (isset($placesOfPublication[$i]) ? $placesOfPublication[$i] . ' ': '') . (isset($names[$i]) ? $names[$i] . ' ' : '') . (isset($dates[$i]) ? (', ' . $dates[$i] . '.') : '');
			// $publicationInfo = (isset($places[$i]) ? $places[$i] . ' ' : '') . (isset($names[$i]) ? $names[$i] . ' ' : '') . (isset($dates[$i]) ? $dates[$i] : '');
			$returnVal[] = trim(str_replace('  ', ' ', $publicationInfo));
			$i++;
		}

		return $returnVal;
	}

	/**
	 * @return string[]
	 */
	public function getEditions() : array {
		$edition = isset($this->getOverDriveMetaData()->getDecodedRawData()->edition) ? $this->getOverDriveMetaData()->getDecodedRawData()->edition : null;
		if (is_array($edition)) {
			return $edition;
		} elseif (is_null($edition)) {
			return [];
		} else {
			return [$edition];
		}
	}

	/** @noinspection PhpUnused */
	public function getStreetDate() : ?string {
		return isset($this->overDriveMetaData->getDecodedRawData()->publishDateText) ? $this->overDriveMetaData->getDecodedRawData()->publishDateText : null;
	}

	public function getGroupedWorkDriver() : ?GroupedWorkDriver {
		$permanentId = $this->getPermanentId();
		if ($permanentId == null) {
			return null;
		}else {
			require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
			if ($this->groupedWorkDriver == null) {
				$this->groupedWorkDriver = new GroupedWorkDriver($this->getPermanentId());
			}
			return $this->groupedWorkDriver;
		}
	}

	protected ?array $_actions = null;

	/**
	 * Determines which item should be used for circulation actions including
	 * - the collection (setting)
	 * - if the title is available
	 * - number of holds if the title is not available
	 * Aspen takes into account the following information in determining the best option
	 * - Number of holds remaining within each collection
	 * - Number of checkouts remaining within each collection
	 * - Availability of each title
	 * - Hold ratio for each title
	 *
	 * This function does require loading information about holds and checkouts for all OverDrive accounts
	 * the user has access to. Therefore, it can be a bit slow, so it is not used to proactively determine
	 * if a user has reached their maximum checkouts or holds when showing actions within search results.
	 *
	 * @param ?User $patron - The patron to check circulation options for
	 *
	 * @return array
	 */
	public function getBestCirculationOption(?User $patron) : ?Grouping_Item {
		//These are sorted in the way that the library would prefer they be used.
		$validCollections = $this->getValidCollectionsForRecord($patron);
		$firstAvailableItem = null;
		$firstHoldableItem = null;
		$firstHoldableItemHoldRatio = 99999999;
		$firstHoldableItemIgnoringMaxHolds = null;
		$firstHoldableItemIgnoringMaxHoldsHoldRatio = 99999999;
		foreach ($validCollections as $settingId => $collection) {
			require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
			$overDriveDriver = OverDriveDriver::getOverDriveDriver($collection->settingId);

			$hasRemainingCheckouts = false;
			$hasRemainingHolds = false;
			if ($patron != null && $patron->isValidForEContentSource('overdrive')) {
				if ($overDriveDriver->isUserValidForOverDrive($overDriveDriver->getActiveSettings(), $patron)) {
					$patronOptions = $overDriveDriver->getOptions($patron);
					$accountSummary = $overDriveDriver->getAccountSummary($patron);

					//Check to see if there are checkouts remaining
					$hasRemainingCheckouts = $accountSummary->numCheckedOut < $patronOptions['checkoutLimit'];
					$hasRemainingHolds = ($accountSummary->numAvailableHolds + $accountSummary->numUnavailableHolds) < $patronOptions['holdLimit'];
				}
			}

			$itemsForCollection = $this->getItemsForCollection($settingId);

			foreach ($itemsForCollection as $item) {
				if ($item->available) {
					if ($hasRemainingCheckouts) {
						//We can check out the title
						if ($firstAvailableItem == null) {
							$firstAvailableItem = $item;
						}
					}else{
						//We have no remaining checkouts for this account, so a hold would be placed,
						// but it will be filled immediately as soon as the user checks something in
						$itemHoldRatio = 0;
						if ($hasRemainingHolds) {
							if ($firstHoldableItem == null || $itemHoldRatio < $firstHoldableItemHoldRatio) {
								$firstHoldableItem = $item;
								$firstHoldableItemHoldRatio = $itemHoldRatio;
							}
						}else{
							if ($firstHoldableItemIgnoringMaxHolds == null || $itemHoldRatio < $firstHoldableItemIgnoringMaxHoldsHoldRatio) {
								$firstHoldableItemIgnoringMaxHolds = $item;
								$firstHoldableItemIgnoringMaxHoldsHoldRatio = $itemHoldRatio;
							}
						}
					}
				}else{
					//Item is not available, figure out the hold ratio
					if ($item->numCopies > 0) {
						$itemHoldRatio = $item->numHolds / $item->numCopies;
					}else{
						$itemHoldRatio = 99999999;
					}

					if ($hasRemainingHolds) {
						if ($firstHoldableItem == null || $itemHoldRatio < $firstHoldableItemHoldRatio) {
							$firstHoldableItem = $item;
							$firstHoldableItemHoldRatio = $itemHoldRatio;
						}
					}else{
						if ($firstHoldableItemIgnoringMaxHolds == null || $itemHoldRatio < $firstHoldableItemIgnoringMaxHoldsHoldRatio) {
							$firstHoldableItemIgnoringMaxHolds = $item;
							$firstHoldableItemIgnoringMaxHoldsHoldRatio = $itemHoldRatio;
						}
					}
				}
			} // End loop through items in the collection
		} // End loop through collections

		if ($firstAvailableItem != null) {
			return $firstAvailableItem;
		}else if ($firstHoldableItem != null) {
			return $firstHoldableItem;
		}else if ($firstHoldableItemIgnoringMaxHolds != null) {
			return $firstHoldableItemIgnoringMaxHolds;
		}else{
			return null;
		}
	}

	/**
	 * @param int $collectionId - the setting/collectionId to return information for
	 * @return Grouping_Item[]
	 */
	public function getItemsForCollection (int $collectionId) : array {
		$relatedRecord = $this->getRelatedRecord();
		$itemsForCollection = [];

		if ($relatedRecord != null) {
			foreach ($relatedRecord->getItems() as $item) {
				if (substr_count($item->itemId, ':') >= 2) {
					list(, $settingId, ) = explode(':', $item->itemId, 3);
					if ($settingId == $collectionId) {
						$itemsForCollection[] = $item;
					}
				}else{
					//This isn't attached to a setting, assume it is good
					$itemsForCollection[] = $item;
				}
			}
		}
		return $itemsForCollection;
	}

	/**
	 * Return a list of the collections (settings) that are valid for a record
	 * since a record may exist in one or more collections for a library
	 *
	 * This can optionally be done for a specific patron which will use their home library settings
	 *
	 * @return LibraryOverDriveSettings[]
	 */
	public function getValidCollectionsForRecord(null|false|User $patron) : array {
		$validCollections = [];

		global $library;
		if ($patron == null) {
			$activeLibrary = $library;
		}else{
			$activeLibrary = $patron->getHomeLibrary();
			if (empty($activeLibrary)) {
				//The patron does not have a library, so it can't use OverDrive
				return $validCollections;
			}
		}
		$overDriveSettings = $activeLibrary->getLibraryOverdriveSettings();

		if (count($overDriveSettings) > 0) {
			$relatedRecord = $this->getRelatedRecord();

			if ($relatedRecord != null) {
				foreach ($relatedRecord->getItems() as $item) {
					//Get the settings for the item
					if (substr_count($item->itemId, ':') >= 2) {
						list(, $settingId, ) = explode(':', $item->itemId, 3);
						foreach ($overDriveSettings as $settings) {
							if ($settings->settingId == $settingId) {
								$validCollections[$settingId] = $settings;
								break;
							}
						}
					}else{
						//This is still indexed assuming one setting id per library
						// use the first collection
						$firstSetting = reset($overDriveSettings);
						$validCollections[$firstSetting->settingId] = $firstSetting;
					}
				}
			}
		}

		return $validCollections;
	}

	public function getRecordActions($relatedRecord, $variationId, $isAvailable, $isHoldable, $volumeData = null) : array {
		if ($this->_actions === null) {
			if ($relatedRecord == null) {
				$relatedRecord = $this->getRelatedRecord();
			}
			$this->_actions = [];
			global $library;

			$settingsToProcess = $this->getValidCollectionsForRecord(UserAccount::getActiveUserObj());

			if (!empty($settingsToProcess)) {
				//Check to see if the title is on hold or checked out to the patron.
				$loadDefaultActions = true;
				if (UserAccount::isLoggedIn()) {
					$activeUser = UserAccount::getActiveUserObj();
					if ($activeUser->isValidForEContentSource('overdrive')) {
						$this->_actions = array_merge($this->_actions, $activeUser->getCirculatedRecordActions('overdrive', $this->id));
					}
					$loadDefaultActions = count($this->_actions) == 0;
				}else{
					$activeUser = null;
				}

				if ($loadDefaultActions) {
					require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
					$overDriveDriver = new OverDriveDriver();
					$availableReaders = $overDriveDriver->getReaderNames();
					$actionsByReader = [];
					foreach ($availableReaders as $reader) {
						$actionsByReader[$reader] = [
							'accessOnline' => null,
							'checkout' => null,
							'placeHold' => null
						];
					}

					foreach ($settingsToProcess as $settingId => $librarySettings) {
						//Check to see if OverDrive circulation is enabled
						require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
						$overDriveDriver = OverDriveDriver::getOverDriveDriver($settingId);
						$readerName = $overDriveDriver->getReaderName();
						//Check if catalog is offline and login for eResources should be allowed for offline
						global $offlineMode;
						global $loginAllowedWhileOffline;
						$activeLibrary = UserAccount::isLoggedIn() ? UserAccount::getActiveUserObj()->getHomeLibrary() : $library;
						//Show a link to the OverDrive record when the catalog is offline and can't do logins
						if ((!is_null($activeUser) && !$activeUser->isValidForEContentSource('overdrive')) || !$overDriveDriver->isCirculationEnabled($activeLibrary, $overDriveDriver->getActiveSettings()) || ($offlineMode && !$loginAllowedWhileOffline)) {
							$overDriveMetadata = $this->getOverDriveMetaData();
							$crossRefId = $overDriveMetadata->getDecodedRawData()->crossRefId;
							$productUrl = $overDriveDriver->getProductUrl($overDriveDriver->getActiveSettings(), $crossRefId);
							if (!empty($productUrl)) {
								$actionsByReader[$readerName]['accessOnline'] = [
									'title' => translate([
										'text' => 'Access in %1%',
										1 => $readerName,
										'isPublicFacing' => true,
									]),
									'url' => $overDriveDriver->getProductUrl($overDriveDriver->getActiveSettings(), $crossRefId),
									'target' => 'blank',
									'requireLogin' => false,
									'type' => 'overdrive_access_online',
								];
							}
						} else {
							if ($loadDefaultActions && (!$offlineMode || $loginAllowedWhileOffline)) {
								if ($isAvailable) {
									//Only one setting with a checkout link so far using this reader name
									$actionsByReader[$readerName]['checkout'] = [
										'title' => translate([
											'text' => "Borrow with %1%",
											1 => $readerName,
											"isPublicFacing" => true,
										]),
										'onclick' => "return AspenDiscovery.OverDrive.checkOutTitle('$this->id', '$readerName');",
										'requireLogin' => false,
										'type' => 'overdrive_checkout',
									];
								} else {
									$actionsByReader[$readerName]['placeHold'] = [
										'title' => translate([
											'text' => 'Place Hold with %1%',
											1 => $readerName,
											'isPublicFacing' => true,
										]),
										'onclick' => "return AspenDiscovery.OverDrive.placeHold('$this->id', '$readerName');",
										'requireLogin' => false,
										'type' => 'overdrive_hold',
									];
								}
							}
						} //End checking if circulation is enabled
					} // Loop through each setting

					//Add the appropriate actions to the action array
					foreach ($actionsByReader as $readerActions) {
						if (!is_null($readerActions['checkout'])){
							$this->_actions[] = $readerActions['checkout'];
						}elseif (!is_null($readerActions['placeHold'])){
							$this->_actions[] = $readerActions['placeHold'];
						}elseif (!is_null($readerActions['accessOnline'])){
							$this->_actions[] = $readerActions['accessOnline'];
						}
					}

				} // End checking if we should load default actions
			} // End check of if we have any scopes that apply to the library

			$this->_actions = array_merge($this->_actions, $this->getPreviewActions());
		}
		return $this->_actions;
	}

	function getPreviewActions() : array {
		$items = $this->getItems();
		$previewLinks = [];
		require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
		$previewActions = [];
		foreach ($items as $item) {
			if (!empty($item->sampleUrl_1) && !in_array($item->sampleUrl_1, $previewLinks) && !StringUtils::endsWith($item->sampleUrl_1, '.epub') && !StringUtils::endsWith($item->sampleUrl_1, '.wma')) {
				$previewLinks[] = $item->sampleUrl_1;
				$previewActions[] = [
					'title' => translate([
						'text' => 'Preview ' . ucwords($item->sampleSource_1),
						'isPublicFacing' => true,
						'isAdminEnteredData' => true,
					]),
					'onclick' => "return AspenDiscovery.OverDrive.showPreview('$this->id', '$item->id', '1');",
					'requireLogin' => false,
					'type' => 'overdrive_sample',
					'btnType' => 'btn-info',
					'formatId' => $item->id,
					'sampleNumber' => 1,
				];
			}
			if (!empty($item->sampleUrl_2) && !in_array($item->sampleUrl_2, $previewLinks) && !StringUtils::endsWith($item->sampleUrl_2, '.epub') && !StringUtils::endsWith($item->sampleUrl_2, '.wma')) {
				$previewLinks[] = $item->sampleUrl_2;
				$previewActions[] = [
					'title' => translate([
						'text' => 'Preview ' . ucwords($item->sampleSource_2),
						'isPublicFacing' => true,
						'isAdminEnteredData' => true,
					]),
					'onclick' => "return AspenDiscovery.OverDrive.showPreview('$this->id', '$item->id', '2');",
					'requireLogin' => false,
					'type' => 'overdrive_sample',
					'btnType' => 'btn-info',
					'formatId' => $item->id,
					'sampleNumber' => 2,
				];
			}
		}
		return $previewActions;
	}

	function getNumHolds(): int {
		$availabilityInfo = $this->getAvailabilityInformation();
		$numHolds = 0;
		foreach ($availabilityInfo as $availability) {
			$numHolds += $availability->numberOfHolds;
		}
		return $numHolds;
	}

	public function getSemanticData() : ?array {
		// Schema.org
		// Get information about the record
		require_once ROOT_DIR . '/RecordDrivers/LDRecordOffer.php';
		$relatedRecord = $this->getRelatedRecord();
		if ($relatedRecord != null) {
			$linkedDataRecord = new LDRecordOffer($relatedRecord);
			$semanticData [] = [
				'@context' => 'https://schema.org',
				'@type' => $linkedDataRecord->getWorkType(),
				'name' => $this->getTitle(),
				'creator' => $this->getAuthor(),
				'bookEdition' => $this->getEditions(),
				'isAccessibleForFree' => true,
				'image' => $this->getBookcoverUrl('medium', true),
				"offers" => $linkedDataRecord->getOffers(),
			];

			global $interface;
			$interface->assign('og_title', $this->getTitle());
			$interface->assign('og_description', $this->getDescriptionFast());
			$interface->assign('og_type', $this->getGroupedWorkDriver()->getOGType());
			$interface->assign('og_image', $this->getBookcoverUrl('medium', true));
			$interface->assign('og_url', $this->getAbsoluteUrl());
			return $semanticData;
		} else {
			return null;
		}
	}

	function getRelatedRecord() : ?Grouping_Record {
		$id = strtolower('overdrive:' . $this->id);
		$groupedWorkDriver = $this->getGroupedWorkDriver();
		if ($groupedWorkDriver == null) {
			return null;
		}else{
			return $groupedWorkDriver->getRelatedRecord($id);
		}
	}

	/**
	 * Get an array of all ISSNs associated with the record (might be empty).
	 *
	 * @access  public
	 * @return  array
	 */
	public function getISSNs() : array {
		return [];
	}

	public function setNumHoldsForItem(Grouping_Item $item) : void {
		list(,$itemSetting,) = explode(':', $item->itemId);
		$availabilities = $this->getAvailabilityInformation();
		foreach ($availabilities as $availability) {
			if ($itemSetting == $availability->settingId) {
				$item->numHolds = $availability->numberOfHolds;
			}
		}

	}
}
