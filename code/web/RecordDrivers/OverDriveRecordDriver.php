<?php

require_once ROOT_DIR . '/RecordDrivers/RecordInterface.php';
require_once ROOT_DIR . '/RecordDrivers/GroupedWorkSubDriver.php';

class OverDriveRecordDriver extends GroupedWorkSubDriver
{
	private $id;
	//This will be either blank or kindle for now
	private $subSource;
	/** @var OverDriveAPIProduct */
	private $overDriveProduct;
	/** @var  OverDriveAPIProductMetaData */
	private $overDriveMetaData;
	private $valid;
	private $isbns = null;
	private $upcs = null;
	private $items;

	/**
	 * The Grouped Work that this record is connected to
	 * @var  GroupedWork
	 */
	protected $groupedWork;
	protected $groupedWorkDriver = null;

	/**
	 * Constructor.  We build the object using all the data retrieved
	 * from the (Solr) index.  Since we have to
	 * make a search call to find out which record driver to construct,
	 * we will already have this data available, so we might as well
	 * just pass it into the constructor.
	 *
	 * @param string $recordId The id of the record within OverDrive.
	 * @param GroupedWork $groupedWork ;
	 * @access  public
	 */
	public function __construct($recordId, $groupedWork = null)
	{
		if (is_string($recordId)) {
			//The record is the identifier for the overdrive title
			//Check to see if we have a subSource
			if (strpos($recordId, ':') > 0){
				list($this->subSource, $recordId) = explode(':', $recordId);
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

	public function getIdWithSource()
	{
		return 'overdrive:' . $this->id;
	}

	public function getModule()
	{
		return 'OverDrive';
	}

	public function getRecordType()
	{
		return 'overdrive';
	}

	/**
	 * Load the grouped work that this record is connected to.
	 */
	public function loadGroupedWork()
	{
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

	public function getPermanentId()
	{
		return $this->getGroupedWorkId();
	}

	public function getGroupedWorkId()
	{
		if (!isset($this->groupedWork)) {
			$this->loadGroupedWork();
		}
		if ($this->groupedWork) {
			return $this->groupedWork->permanent_id;
		} else {
			return null;
		}

	}

	public function isValid()
	{
		return $this->valid;
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display holdings extracted from the base record
	 * (i.e. URLs in MARC 856 fields).  This is designed to supplement,
	 * not replace, holdings information extracted through the ILS driver
	 * and displayed in the Holdings tab of the record view page.  Returns
	 * null if no data is available.
	 *
	 * @access  public
	 * @return  array              Name of Smarty template file to display.
	 */
	public function getHoldings()
	{
		require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';

		$items = $this->getItems();
		//Add links as needed
		$availability = $this->getAvailability();
		$addCheckoutLink = false;
		$addPlaceHoldLink = false;
		foreach ($availability as $availableFrom) {
			if ($availableFrom->copiesAvailable > 0) {
				$addCheckoutLink = true;
			} else {
				$addPlaceHoldLink = true;
			}
		}
		foreach ($items as $key => $item) {
			$item->links = array();
			if ($addCheckoutLink) {
				$checkoutLink = "return AspenDiscovery.OverDrive.checkOutTitle('{$this->getUniqueID()}');";
				$item->links[] = array(
					'onclick' => $checkoutLink,
					'text' => 'Check Out',
					'overDriveId' => $this->getUniqueID(),
					'action' => 'CheckOut'
				);
			} else if ($addPlaceHoldLink) {
				$item->links[] = array(
					'onclick' => "return AspenDiscovery.OverDrive.placeHold('{$this->getUniqueID()}');",
					'text' => 'Place Hold',
					'overDriveId' => $this->getUniqueID(),
					'action' => 'Hold'
				);
			}
			$items[$key] = $item;
		}

		return $items;
	}

	/**
	 * @return array
	 */
	public function getScopedAvailability()
	{
		$availability = array();
		$availability['mine'] = $this->getAvailability();
		$availability['other'] = array();
		$scopingId = $this->getLibraryScopingId();
		if ($scopingId != -1) {
			foreach ($availability['mine'] as $key => $availabilityItem) {
				if ($availabilityItem->libraryId != -1 && $availabilityItem->libraryId != $scopingId) {
					$availability['other'][$key] = $availability['mine'][$key];
					unset($availability['mine'][$key]);
				}
			}
		}
		return $availability;
	}

	public function getStatusSummary()
	{
		$holdings = $this->getHoldings();
		$scopedAvailability = $this->getScopedAvailability();

		$holdPosition = 0;

		$availableCopies = 0;
		$totalCopies = 0;
		$onOrderCopies = 0;
		$checkedOut = 0;
		$onHold = 0;
		$wishListSize = 0;
		$numHolds = 0;
		if (count($scopedAvailability['mine']) > 0) {
			foreach ($scopedAvailability['mine'] as $curAvailability) {
				$availableCopies += $curAvailability->copiesAvailable;
				$totalCopies += $curAvailability->copiesOwned;
				if ($curAvailability->numberOfHolds > $numHolds) {
					$numHolds = $curAvailability->numberOfHolds;
				}
			}
		}

		//Load status summary
		$statusSummary = array();
		$statusSummary['recordId'] = $this->id;
		$statusSummary['totalCopies'] = $totalCopies;
		$statusSummary['onOrderCopies'] = $onOrderCopies;
		$statusSummary['accessType'] = 'overdrive';
		$statusSummary['isOverDrive'] = false;
		$statusSummary['alwaysAvailable'] = false;
		$statusSummary['class'] = 'checkedOut';
		$statusSummary['available'] = false;
		$statusSummary['status'] = 'Not Available';

		$statusSummary['availableCopies'] = $availableCopies;
		$statusSummary['isOverDrive'] = true;
		if ($totalCopies >= 999999) {
			$statusSummary['alwaysAvailable'] = true;
		}
		if ($availableCopies > 0) {
			$statusSummary['status'] = "Available from OverDrive";
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
		$statusSummary['showPlaceHold'] = $availableCopies == 0 && count($scopedAvailability['mine']) > 0;
		$statusSummary['showCheckout'] = $availableCopies > 0 && count($scopedAvailability['mine']) > 0;
		$statusSummary['showAddToWishlist'] = false;
		$statusSummary['showAccessOnline'] = false;

		$statusSummary['onHold'] = $onHold;
		$statusSummary['checkedOut'] = $checkedOut;
		$statusSummary['holdPosition'] = $holdPosition;
		$statusSummary['numHoldings'] = count($holdings);
		$statusSummary['wishListSize'] = $wishListSize;

		return $statusSummary;
	}

	public function getSeries()
	{
		$seriesData = $this->getGroupedWorkDriver()->getSeries();
		if ($seriesData == null) {
			$seriesName = isset($this->getOverDriveMetaData()->getDecodedRawData()->series) ? $this->getOverDriveMetaData()->getDecodedRawData()->series : null;
			if ($seriesName != null) {
				$seriesData = array(
					'seriesTitle' => $seriesName,
					'fromNovelist' => false,
				);
			}
		}
		return $seriesData;
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display the full record information on the Staff
	 * View tab of the record view page.
	 *
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getStaffView()
	{
		global $interface;

		$this->getGroupedWorkDriver()->assignGroupedWorkStaffView();

		$interface->assign('bookcoverInfo', $this->getBookcoverInfo());

		$overDriveAPIProduct = new OverDriveAPIProduct();
		$overDriveAPIProduct->overdriveId = strtolower($this->id);
		if ($overDriveAPIProduct->find(true)) {
			$interface->assign('overDriveProduct', $overDriveAPIProduct);
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductMetaData.php';
			$overDriveAPIProductMetaData = new OverDriveAPIProductMetaData();
			$overDriveAPIProductMetaData->productId = $overDriveAPIProduct->id;
			if ($overDriveAPIProductMetaData->find(true)) {
				$overDriveMetadata = $overDriveAPIProductMetaData->rawData;
				//Replace http links to content reserve with https so we don't get mixed content warnings
				$overDriveMetadata = str_replace('http://images.contentreserve.com', 'https://images.contentreserve.com', $overDriveMetadata);
				$overDriveMetadata = json_decode($overDriveMetadata);
				$interface->assign('overDriveMetaDataRaw', $overDriveMetadata);
			}
		}

		return 'RecordDrivers/OverDrive/staff.tpl';
	}

	/**
	 * The Table of Contents extracted from the record.
	 * Returns null if no Table of Contents is available.
	 *
	 * @access  public
	 * @return  array              Array of elements in the table of contents
	 */
	public function getTableOfContents()
	{
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
	public function getUniqueID()
	{
		return $this->id;
	}

	function getLanguage()
	{
		$metaData = $this->getOverDriveMetaData()->getDecodedRawData();
		$languages = array();
		if (isset($metaData->languages)) {
			foreach ($metaData->languages as $language) {
				$languages[] = $language->name;
			}
		}
		return $languages;
	}

	private $availability = null;

	/**
	 * @return OverDriveAPIProductAvailability[]
	 */
	function getAvailability()
	{
		global $library;
		if ($this->availability == null) {
			$this->availability = array();
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductAvailability.php';
			$availability = new OverDriveAPIProductAvailability();
			$availability->productId = $this->overDriveProduct->id;
			$availability->settingId = $library->getOverdriveScope()->settingId;
			//Only include shared collection if include digital collection is on
			$searchLibrary = Library::getSearchLibrary();
			$searchLocation = Location::getSearchLocation();
			$includeSharedTitles = true;
			if ($searchLocation != null) {
				$includeSharedTitles = $searchLocation->overDriveScopeId != 0;
			} elseif ($searchLibrary != null) {
				$includeSharedTitles = $searchLibrary->overDriveScopeId != 0;
			}
			$libraryScopingId = $this->getLibraryScopingId();
			if ($includeSharedTitles) {
				//$availability->whereAdd('libraryId = -1 OR libraryId = ' . $libraryScopingId);
			} else {
				if ($libraryScopingId == -1) {
					return $this->availability;
				} else {
					$availability->whereAdd('libraryId = ' . $libraryScopingId);
				}
			}
			$availability->find();
			$copiesInLibraryCollection = 0;
			while ($availability->fetch()) {
				$this->availability[$availability->libraryId] = clone $availability;
				if ($availability->libraryId != -1) {
					if ($availability->shared) {
						$copiesInLibraryCollection += $availability->copiesOwned;
					}
				}
			}
			if ($copiesInLibraryCollection > 0) {
				$this->availability[-1]->copiesOwned -= $copiesInLibraryCollection;
			}
		}
		return $this->availability;
	}

	public function getLibraryScopingId()
	{
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
			return $homeLibrary->getGroupedWorkDisplaySettings()->includeOutOfSystemExternalLinks ? -1 : $homeLibrary->libraryId;
		} else if (!is_null($activeLocation)) {
			$activeLibrary = Library::getLibraryForLocation($activeLocation->locationId);
			return $activeLibrary->getGroupedWorkDisplaySettings()->includeOutOfSystemExternalLinks ? -1 : $activeLibrary->libraryId;
		} else if (isset($activeLibrary)) {
			return $activeLibrary->getGroupedWorkDisplaySettings()->includeOutOfSystemExternalLinks ? -1 : $activeLibrary->libraryId;
		} else if (!is_null($searchLocation)) {
			$searchLibrary = Library::getLibraryForLocation($searchLibrary->locationId);
			return $searchLibrary->getGroupedWorkDisplaySettings()->includeOutOfSystemExternalLinks ? -1 : $searchLocation->libraryId;
		} else if (isset($searchLibrary)) {
			return $searchLibrary->getGroupedWorkDisplaySettings()->includeOutOfSystemExternalLinks ? -1 : $searchLibrary->libraryId;
		} else {
			return -1;
		}
	}

	public function getDescriptionFast()
	{
		$metaData = $this->getOverDriveMetaData();
		return $metaData->fullDescription;
	}

	public function getDescription()
	{
		$metaData = $this->getOverDriveMetaData();
		return $metaData->fullDescription;
	}

	/**
	 * Return the first valid ISBN found in the record (favoring ISBN-10 over
	 * ISBN-13 when possible).
	 *
	 * @return  mixed
	 */
	public function getCleanISBN()
	{
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
	 * Get an array of all ISBNs associated with the record (may be empty).
	 *
	 * @access  protected
	 * @return  array
	 */
	public function getISBNs()
	{
		//Load ISBNs for the product
		if ($this->isbns == null) {
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductIdentifiers.php';
			$overDriveIdentifiers = new OverDriveAPIProductIdentifiers();
			$overDriveIdentifiers->type = 'ISBN';
			$overDriveIdentifiers->productId = $this->overDriveProduct->id;
			$this->isbns = array();
			$overDriveIdentifiers->find();
			while ($overDriveIdentifiers->fetch()) {
				$this->isbns[] = $overDriveIdentifiers->value;
			}
		}
		return $this->isbns;
	}

	/**
	 * Get an array of all UPCs associated with the record (may be empty).
	 *
	 * @access  protected
	 * @return  array
	 */
	public function getUPCs()
	{
		//Load UPCs for the product
		if ($this->upcs == null) {
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductIdentifiers.php';
			$overDriveIdentifiers = new OverDriveAPIProductIdentifiers();
			$overDriveIdentifiers->type = 'UPC';
			$overDriveIdentifiers->productId = $this->overDriveProduct->id;
			$this->upcs = array();
			$overDriveIdentifiers->find();
			while ($overDriveIdentifiers->fetch()) {
				$this->upcs[] = $overDriveIdentifiers->value;
			}
		}
		return $this->upcs;
	}

	public function getSubjects()
	{
		return $this->getOverDriveMetaData()->getDecodedRawData()->subjects;
	}

	/**
	 * Get the full title of the record.
	 *
	 * @return  string
	 */
	public function getTitle()
	{
		return $this->overDriveProduct->title;
	}

	public function getShortTitle()
	{
		return $this->overDriveProduct->title;
	}

	public function getSubtitle()
	{
		return $this->overDriveProduct->subtitle;
	}

	/**
	 * Get an array of all the formats associated with the record.
	 *
	 * @access  protected
	 * @return  array
	 */
	public function getFormats()
	{
		$formats = array();
		if ($this->subSource == 'kindle') {
			$formats[] = 'Kindle';
		}else{
			foreach ($this->getItems() as $item) {
				$formats[] = $item->name;
			}
		}
		return $formats;
	}

	/**
	 * Get an array of all the format categories associated with the record.
	 *
	 * @return  array
	 */
	public function getFormatCategory()
	{
		$formats = array();
		foreach ($this->getItems() as $item) {
			$formats[] = $item->name;
		}
		return $formats;
	}

	/**
	 * @return OverDriveAPIProductFormats[]
	 */
	public function getItems()
	{
		if ($this->items == null) {
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductFormats.php';
			$overDriveFormats = new OverDriveAPIProductFormats();
			$this->items = array();
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

	public function getAuthor()
	{
		return $this->overDriveProduct->primaryCreatorName;
	}

	public function getPrimaryAuthor()
	{
		return $this->overDriveProduct->primaryCreatorName;
	}

	public function getContributors()
	{
		return array();
	}

	public function getBookcoverUrl($size = 'small', $absolutePath = false)
	{
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

	public function getCoverUrl($size = 'small')
	{
		return $this->getBookcoverUrl($size);
	}

	private function getOverDriveMetaData()
	{
		if ($this->overDriveMetaData == null) {
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductMetaData.php';
			$this->overDriveMetaData = new OverDriveAPIProductMetaData();
			$this->overDriveMetaData->productId = $this->overDriveProduct->id;
			$this->overDriveMetaData->find(true);
		}
		return $this->overDriveMetaData;
	}

	public function getRatingData()
	{
		require_once ROOT_DIR . '/services/API/WorkAPI.php';
		$workAPI = new WorkAPI();
		$groupedWorkId = $this->getGroupedWorkId();
		if ($groupedWorkId == null) {
			return null;
		} else {
			return $workAPI->getRatingData($this->getGroupedWorkId());
		}
	}

	public function getMoreDetailsOptions()
	{
		global $interface;

		$isbn = $this->getCleanISBN();

		/** @var OverDriveAPIProductFormats[] $holdings */
		$holdings = $this->getHoldings();
		$scopedAvailability = $this->getScopedAvailability();
		$interface->assign('availability', $scopedAvailability['mine']);
		$interface->assign('availabilityOther', $scopedAvailability['other']);
		$numberOfHolds = 0;
		foreach ($scopedAvailability['mine'] as $availability) {
			if ($availability->numberOfHolds > 0) {
				$numberOfHolds = $availability->numberOfHolds;
				break;
			}
		}
		$interface->assign('numberOfHolds', $numberOfHolds);
		$showAvailability = true;
		$showAvailabilityOther = true;
		$interface->assign('showAvailability', $showAvailability);
		$interface->assign('showAvailabilityOther', $showAvailabilityOther);
		$showOverDriveConsole = false;
		$showAdobeDigitalEditions = false;
		foreach ($holdings as $item) {
			if (in_array($item->textId, array('ebook-epub-adobe', 'ebook-pdf-adobe'))) {
				$showAdobeDigitalEditions = true;
			} else if (in_array($item->textId, array('video-wmv', 'music-wma', 'music-wma', 'audiobook-wma', 'audiobook-mp3'))) {
				$showOverDriveConsole = true;
			}
		}
		$interface->assign('showOverDriveConsole', $showOverDriveConsole);
		$interface->assign('showAdobeDigitalEditions', $showAdobeDigitalEditions);

		$interface->assign('holdings', $holdings);

		//Load more details options
		$moreDetailsOptions = $this->getBaseMoreDetailsOptions($isbn);
		$moreDetailsOptions['formats'] = array(
			'label' => 'Formats',
			'body' => $interface->fetch('OverDrive/view-formats.tpl'),
			'openByDefault' => true
		);
		//Other editions if applicable (only if we aren't the only record!)
		$relatedRecords = $this->getGroupedWorkDriver()->getRelatedRecords();
		if (count($relatedRecords) > 1) {
			$interface->assign('relatedManifestations', $this->getGroupedWorkDriver()->getRelatedManifestations());
			$interface->assign('workId', $this->getGroupedWorkDriver()->getPermanentId());
			$moreDetailsOptions['otherEditions'] = array(
				'label' => 'Other Editions and Formats',
				'body' => $interface->fetch('GroupedWork/relatedManifestations.tpl'),
				'hideByDefault' => false
			);
		}

		$moreDetailsOptions['moreDetails'] = array(
			'label' => 'More Details',
			'body' => $interface->fetch('OverDrive/view-more-details.tpl'),
		);
		$moreDetailsOptions['citations'] = array(
			'label' => 'Citations',
			'body' => $interface->fetch('Record/cite.tpl'),
		);
		$moreDetailsOptions['copyDetails'] = array(
			'label' => 'Copy Details',
			'body' => $interface->fetch('OverDrive/view-copies.tpl'),
		);
		if ($interface->getVariable('showStaffView')) {
			$moreDetailsOptions['staff'] = array(
				'label' => 'Staff View',
				'onShow' => "AspenDiscovery.OverDrive.getStaffView('{$this->id}');",
				'body' => '<div id="staffViewPlaceHolder">Loading Staff View.</div>',
			);
		}

		return $this->filterAndSortMoreDetailsOptions($moreDetailsOptions);
	}

	public function getRecordUrl()
	{
		$id = $this->getUniqueID();
		if ($this->subSource) {
			$linkUrl = "/OverDrive/{$this->subSource}:" . $id . '/Home';
		}else{
			$linkUrl = "/OverDrive/" . $id . '/Home';
		}
		return $linkUrl;
	}

	function getPublishers()
	{
		$publishers = array();
		if (isset($this->overDriveMetaData->publisher)) {
			$publishers[] = $this->overDriveMetaData->publisher;
		}
		return $publishers;
	}

	function getPublicationDates()
	{
		$publicationDates = array();
		if (isset($this->getOverDriveMetaData()->getDecodedRawData()->publishDateText)) {
			$publishDate = $this->getOverDriveMetaData()->getDecodedRawData()->publishDateText;
			$publishYear = substr($publishDate, -4);
			$publicationDates[] = $publishYear;
		}
		return $publicationDates;
	}

	function getPlacesOfPublication()
	{
		return array();
	}

	/**
	 * Get an array of publication detail lines combining information from
	 * getPublicationDates(), getPublishers() and getPlacesOfPublication().
	 *
	 * @access  public
	 * @return  array
	 */
	function getPublicationDetails()
	{
		$places = $this->getPlacesOfPublication();
		$names = $this->getPublishers();
		$dates = $this->getPublicationDates();

		$i = 0;
		$returnVal = array();
		while (isset($places[$i]) || isset($names[$i]) || isset($dates[$i])) {
			// Put all the pieces together, and do a little processing to clean up
			// unwanted whitespace.
			$publicationInfo = (isset($places[$i]) ? $places[$i] . ' ' : '') .
				(isset($names[$i]) ? $names[$i] . ' ' : '') .
				(isset($dates[$i]) ? $dates[$i] : '');
			$returnVal[] = trim(str_replace('  ', ' ', $publicationInfo));
			$i++;
		}

		return $returnVal;
	}

	public function getEditions()
	{
		$edition = isset($this->overDriveMetaData->getDecodedRawData()->edition) ? $this->overDriveMetaData->getDecodedRawData()->edition : null;
		if (is_array($edition) || is_null($edition)) {
			return $edition;
		} else {
			return array($edition);
		}
	}

	public function getStreetDate()
	{
		return isset($this->overDriveMetaData->getDecodedRawData()->publishDateText) ? $this->overDriveMetaData->getDecodedRawData()->publishDateText : null;
	}

	public function getGroupedWorkDriver()
	{
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		if ($this->groupedWorkDriver == null) {
			$this->groupedWorkDriver = new GroupedWorkDriver($this->getPermanentId());
		}
		return $this->groupedWorkDriver;
	}

	public function getRecordActions($relatedRecord, $isAvailable, $isHoldable, $isBookable, $volumeData = null)
	{
		$actions = array();
		if ($isAvailable) {
			$actions[] = array(
				'title' => 'Check Out OverDrive',
				'onclick' => "return AspenDiscovery.OverDrive.checkOutTitle('{$this->id}');",
				'requireLogin' => false,
				'type' => 'overdrive_checkout'
			);
		} else {
			$actions[] = array(
				'title' => 'Place Hold OverDrive',
				'onclick' => "return AspenDiscovery.OverDrive.placeHold('{$this->id}');",
				'requireLogin' => false,
				'type' => 'overdrive_hold'
			);
		}

		$actions = array_merge($actions, $this->getPreviewActions());

		return $actions;
	}

	function getPreviewActions(){
		$items = $this->getItems();
		$previewLinks = [];
		require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
		$actions = [];
		foreach ($items as $item) {
			if (!empty($item->sampleUrl_1) && !in_array($item->sampleUrl_1, $previewLinks) && !StringUtils::endsWith($item->sampleUrl_1, '.epub') && !StringUtils::endsWith($item->sampleUrl_1, '.wma')) {
				$previewLinks[] = $item->sampleUrl_1;
				$actions[] = array(
					'title' => 'Preview ' . $item->sampleSource_1,
					'onclick' => "return AspenDiscovery.OverDrive.showPreview('{$this->id}', '{$item->id}', '1');",
					'requireLogin' => false,
					'type' => 'overdrive_sample',
					'btnType' => 'btn-info'
				);
			}
			if (!empty($item->sampleUrl_2) && !in_array($item->sampleUrl_2, $previewLinks) && !StringUtils::endsWith($item->sampleUrl_2, '.epub') && !StringUtils::endsWith($item->sampleUrl_2, '.wma')) {
				$previewLinks[] = $item->sampleUrl_2;
				$actions[] = array(
					'title' => 'Preview ' . $item->sampleSource_2,
					'onclick' => "return AspenDiscovery.OverDrive.showPreview('{$this->id}', '{$item->id}', '2');",
					'requireLogin' => false,
					'type' => 'overdrive_sample',
					'btnType' => 'btn-info'
				);
			}
		}
		return $actions;
	}

	function getNumHolds()
	{
		$totalHolds = 0;
		/** @var OverDriveAPIProductAvailability $availabilityInfo */
		foreach ($this->getAvailability() as $availabilityInfo) {
			//Holds is set once for everyone so don't add them up.
			if ($availabilityInfo->numberOfHolds > $totalHolds) {
				$totalHolds = $availabilityInfo->numberOfHolds;
			}
		}
		return $totalHolds;
	}

	public function getSemanticData()
	{
		// Schema.org
		// Get information about the record
		require_once ROOT_DIR . '/RecordDrivers/LDRecordOffer.php';
		$relatedRecord = $this->getRelatedRecord();
		if ($relatedRecord != null) {
			$linkedDataRecord = new LDRecordOffer($relatedRecord);
			$semanticData [] = array(
				'@context' => 'http://schema.org',
				'@type' => $linkedDataRecord->getWorkType(),
				'name' => $this->getTitle(),
				'creator' => $this->getAuthor(),
				'bookEdition' => $this->getEditions(),
				'isAccessibleForFree' => true,
				'image' => $this->getBookcoverUrl('medium', true),
				"offers" => $linkedDataRecord->getOffers()
			);

			global $interface;
			$interface->assign('og_title', $this->getTitle());
			$interface->assign('og_type', $this->getGroupedWorkDriver()->getOGType());
			$interface->assign('og_image', $this->getBookcoverUrl('medium', true));
			$interface->assign('og_url', $this->getAbsoluteUrl());
			return $semanticData;
		} else {
			//AspenError::raiseError('OverDrive Record did not have an associated record in grouped work ' . $this->getPermanentId());
			return null;
		}
	}

	function getRelatedRecord()
	{
		$id = strtolower('overdrive:' . $this->id);
		return $this->getGroupedWorkDriver()->getRelatedRecord($id);
	}

	/**
	 * Get an array of all ISSNs associated with the record (may be empty).
	 *
	 * @access  public
	 * @return  array
	 */
	public function getISSNs()
	{
		return [];
	}
}