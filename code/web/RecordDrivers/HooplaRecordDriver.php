<?php

require_once ROOT_DIR . '/RecordDrivers/RecordInterface.php';
require_once ROOT_DIR . '/RecordDrivers/GroupedWorkSubDriver.php';
require_once ROOT_DIR . '/sys/Hoopla/HooplaExtract.php';

class HooplaRecordDriver extends GroupedWorkSubDriver {
	protected $id;
	/** @var HooplaExtract */
	private $hooplaExtract;
	private $hooplaRawMetadata;
	private $valid;
	private $dateFirstDetected;

	/**
	 * Constructor.  We build the object using data from the Hoopla records stored on disk.
	 * Will be similar to a MarcRecord with slightly different functionality
	 *
	 * @param string $recordId
	 * @param GroupedWork $groupedWork ;
	 * @access  public
	 */
	public function __construct($recordId, $groupedWork = null) {
		$this->id = $recordId;

		$this->hooplaExtract = HooplaExtract::getHooplaTitleForId($recordId);
		if ($this->hooplaExtract !== null) {
			$this->valid = true;
			$this->hooplaRawMetadata = json_decode($this->hooplaExtract->rawResponse);
			$this->dateFirstDetected = $this->hooplaExtract->dateFirstDetected;
		} else {
			$this->valid = false;
			$this->hooplaExtract = null;
		}
		if ($this->valid) {
			parent::__construct($groupedWork);
		}
	}

	public function getIdWithSource() {
		return 'hoopla:' . $this->id;
	}

	/**
	 * Load the grouped work that this record is connected to.
	 */
	public function loadGroupedWork() {
		if ($this->groupedWork == null) {
			require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
			require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
			$groupedWork = new GroupedWork();
			$query = "SELECT grouped_work.* FROM grouped_work INNER JOIN grouped_work_primary_identifiers ON grouped_work.id = grouped_work_id WHERE type='hoopla' AND identifier = '" . $this->getUniqueID() . "'";
			$groupedWork->query($query);

			if ($groupedWork->getNumResults() == 1) {
				$groupedWork->fetch();
				$this->groupedWork = clone $groupedWork;
			}
		}
	}

	public function getModule(): string {
		return 'Hoopla';
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display the full record information on the Staff
	 * View tab of the record view page.
	 *
	 * @return string Name of Smarty template file to display.
	 */
	public function getStaffView(): string {
		global $interface;
		$groupedWorkDriver = $this->getGroupedWorkDriver();
		if ($groupedWorkDriver != null) {
			if ($groupedWorkDriver->isValid()) {
				$interface->assign('hasValidGroupedWork', true);
				$groupedWorkDriver->assignGroupedWorkStaffView();

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

		$readerName = new OverDriveDriver();
		$readerName = $readerName->getReaderName();
		$interface->assign('readerName', $readerName);

		$interface->assign('bookcoverInfo', $this->getBookcoverInfo());

		$interface->assign('dateFirstDetected', $this->dateFirstDetected);

		$interface->assign('hooplaExtract', $this->hooplaRawMetadata);
		return 'RecordDrivers/Hoopla/staff-view.tpl';
	}

	/**
	 * Get the full title of the record.
	 *
	 * @return  string
	 */
	public function getTitle() {
		//if episode or subtitle data, match what is displayed in search results
		if (!empty($this->hooplaRawMetadata->episode)) {
			if (!empty($this->hooplaRawMetadata->titleTitle)) {
				return $this->hooplaRawMetadata->titleTitle . ': ' . $this->hooplaExtract->title;
			} else {
				return $this->hooplaExtract->title;
			}
		} elseif (!empty($this->hooplaRawMetadata->subtitle)) {
			return $this->hooplaExtract->title . ': ' . $this->hooplaRawMetadata->subtitle;
		} else {
			return $this->hooplaExtract->title;
		}
	}

	/**
	 * The Table of Contents extracted from the record.
	 * Returns null if no Table of Contents is available.
	 *
	 * @access  public
	 * @return  array              Array of elements in the table of contents
	 */
	public function getTableOfContents() {
		$tableOfContents = [];
		$segments = $this->hooplaRawMetadata->segments;
		if (!empty($segments)) {
			foreach ($segments as $segment) {
				$label = $segment->name;
				if ($segment->seconds) {
					$hours = floor($segment->seconds / 3600);
					$mins = floor($segment->seconds / 60 % 60);
					$secs = floor($segment->seconds % 60);

					if ($hours > 0) {
						$label .= sprintf(' (%01d:%02d:%02d)', $hours, $mins, $secs);
					} else {
						$label .= sprintf(' (%01d:%02d)', $mins, $secs);
					}

				}
				$tableOfContents[] = $label;
			}
		}
		return $tableOfContents;
	}

	/**
	 * Return the unique identifier of this record within the Solr index;
	 * useful for retrieving additional information (like tags and user
	 * comments) from the external MySQL database.
	 *
	 * @access  public
	 * @return  string              Unique identifier.
	 */
	public function getUniqueID() {
		return $this->id;
	}

	public function getDescription() {
		if (!empty($this->hooplaRawMetadata->synopsis)) {
			return $this->hooplaRawMetadata->synopsis;
		} else {
			return "";
		}
	}

	public function getMoreDetailsOptions() {
		global $interface;

		$isbn = $this->getCleanISBN();

		//Load table of contents
		$tableOfContents = $this->getTableOfContents();
		$interface->assign('tableOfContents', $tableOfContents);

		//Load more details options
		$moreDetailsOptions = $this->getBaseMoreDetailsOptions($isbn);
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
			'body' => $interface->fetch('Hoopla/view-more-details.tpl'),
		];
		$this->loadSubjects();
		$moreDetailsOptions['subjects'] = [
			'label' => 'Subjects',
			'body' => $interface->fetch('RecordDrivers/Hoopla/view-subjects.tpl'),
		];
		$moreDetailsOptions['citations'] = [
			'label' => 'Citations',
			'body' => $interface->fetch('Record/cite.tpl'),
		];
		if ($interface->getVariable('showStaffView')) {
			$moreDetailsOptions['staff'] = [
				'label' => 'Staff View',
				'body' => $interface->fetch($this->getStaffView()),
			];
		}

		return $this->filterAndSortMoreDetailsOptions($moreDetailsOptions);
	}

	public function getISBNs() {
		$isbns = [];
		if (!empty($this->hooplaRawMetadata->isbn)) {
			$isbns[] = $this->hooplaRawMetadata->isbn;
		}
		return $isbns;
	}

	public function getOCLCNumber() {
		return '';
	}

	public function getISSNs() {
		return [];
	}

	protected $_actions = null;

	function getRecordActions($relatedRecord, $variationId, $isAvailable, $isHoldable, $volumeData = null) : array {
		if ($this->_actions === null) {
			$this->_actions = [];
			//Check to see if the title is on hold or checked out to the patron.
			$loadDefaultActions = true;
			if (UserAccount::isLoggedIn()) {
				$user = UserAccount::getActiveUserObj();
				$this->_actions = array_merge($this->_actions, $user->getCirculatedRecordActions('hoopla', $this->id));
				$loadDefaultActions = count($this->_actions) == 0;
			}

			//Check if catalog is offline and login for eResources should be allowed for offline
			global $offlineMode;
			global $loginAllowedWhileOffline;
			if ($loadDefaultActions && (!$offlineMode || $loginAllowedWhileOffline)) {
				/** @var Library $searchLibrary */
				$searchLibrary = Library::getSearchLibrary();
				if ($searchLibrary->hooplaLibraryID > 0) { // Library is enabled for Hoopla patron action integration
					$id = $this->id;
					$hooplaType = $this->getHooplaType();
					if (!$isAvailable) {
						$title = translate([
							'text' => 'Place Hold Hoopla',
							'isPublicFacing' => true,
						]);
						$this->_actions[] = [
							'onclick' => "return AspenDiscovery.Hoopla.placeHold('$id')",
							'title' => $title,
							'type' => 'hoopla_hold',
						];
					} else {
						$title = translate([
							'text' => 'Check Out Hoopla',
							'isPublicFacing' => true,
						]);
						$this->_actions[] = [
							'onclick' => "return AspenDiscovery.Hoopla.getCheckOutPrompts('$id', '$hooplaType')",
							'title' => $title,
							'type' => 'hoopla_checkout',
						];
					}
				} else {
					$this->_actions[] = $this->getAccessLink();
				}
			}
		}

		return $this->_actions;
	}

	/**
	 * Returns an array of contributors to the title, ideally with the role appended after a pipe symbol
	 * @return array
	 */
	function getContributors() {
		$contributors = [];
		if (isset($this->hooplaRawMetadata->artists)) {
			$authors = $this->hooplaRawMetadata->artists;
			foreach ($authors as $author) {
				//TODO: Reverse name?
				$contributors[] = $author->name . '|' . ucwords($author->relationship);
			}
		}
		return $contributors;
	}

	/**
	 * Get the edition of the current record.
	 *
	 * @access  protected
	 * @return  array
	 */
	function getEditions() {
		// No specific information provided by Hoopla
		return [];
	}

	/**
	 * @return array
	 */
	function getFormats() {
		if ($this->hooplaExtract->kind == "MOVIE" || $this->hooplaExtract->kind == "TELEVISION") {
			return ['eVideo'];
		} elseif ($this->hooplaExtract->kind == "AUDIOBOOK") {
			return ['eAudiobook'];
		} elseif ($this->hooplaExtract->kind == "EBOOK") {
			return ['eBook'];
		} elseif ($this->hooplaExtract->kind == "ECOMIC") {
			return ['eComic'];
		} elseif ($this->hooplaExtract->kind == "MUSIC") {
			return ['eMusic'];
		} else {
			return ['eBook'];
		}
	}

	/**
	 * Get an array of all the format categories associated with the record.
	 *
	 * @return  array
	 */
	function getFormatCategory() {
		if ($this->hooplaExtract->kind == "AUDIOBOOK") {
			return [
				'eBook',
				'Audio Books',
			];
		} elseif ($this->hooplaExtract->kind == "MOVIE" || $this->hooplaExtract->kind == "TELEVISION") {
			return ['Movies'];
		} elseif ($this->hooplaExtract->kind == "MUSIC") {
			return ['Music'];
		} else {
			return ['eBook'];
		}
	}

	public function getLanguage() {
		return ucfirst(strtolower($this->hooplaRawMetadata->language));
	}

	public function getNumHolds(): int {
		return 0;
	}

	public function getHooplaType() : string {
		if (!empty($this->hooplaExtract->hooplaType)) {
			return $this->hooplaExtract->hooplaType;
		} else {
			return 'Instant';
		}
	}

	/**
	 * @return array
	 */
	function getPlacesOfPublication() {
		//Not provided within the metadata
		return [];
	}

	/**
	 * Returns the primary author of the work
	 * @return String
	 */
	function getPrimaryAuthor() {
		return $this->getAuthor();
	}

	public function getAuthor() {
		if (!empty($this->hooplaRawMetadata->artist)) {
			return $this->hooplaRawMetadata->artist;
		} else {
			return '';
		}
	}

	/**
	 * @return array
	 */
	function getPublishers() {
		return [$this->hooplaRawMetadata->publisher];
	}

	/**
	 * @return array
	 */
	function getPublicationDates() {
		return [$this->hooplaRawMetadata->year];
	}

	public function getRecordType() {
		return 'hoopla';
	}

	function getRelatedRecord() {
		$id = 'hoopla:' . $this->id;
		return $this->getGroupedWorkDriver()->getRelatedRecord($id);
	}

	public function getSemanticData() {
		// Schema.org
		// Get information about the record
		require_once ROOT_DIR . '/RecordDrivers/LDRecordOffer.php';
		$relatedRecord = $this->getGroupedWorkDriver()->getRelatedRecord($this->getIdWithSource());
		if ($relatedRecord != null) {
			$linkedDataRecord = new LDRecordOffer($this->getRelatedRecord());
			$semanticData [] = [
				'@context' => 'http://schema.org',
				'@type' => $linkedDataRecord->getWorkType(),
				'name' => $this->getTitle(),
				'creator' => $this->getPrimaryAuthor(),
				'bookEdition' => $this->getEditions(),
				'isAccessibleForFree' => true,
				'image' => $this->getBookcoverUrl('medium', true),
				"offers" => $linkedDataRecord->getOffers(),
			];

			global $interface;
			$interface->assign('og_title', $this->getTitle());
			$interface->assign('og_description', $this->getDescription());
			$interface->assign('og_type', $this->getGroupedWorkDriver()->getOGType());
			$interface->assign('og_image', $this->getBookcoverUrl('medium', true));
			$interface->assign('og_url', $this->getAbsoluteUrl());
			return $semanticData;
		} else {
			return null;
		}
	}

	/**
	 * Returns title without subtitle
	 *
	 * @return string
	 */
	function getShortTitle() {
		return $this->getTitle();
	}

	/**
	 * Returns subtitle
	 *
	 * @return string
	 */
	function getSubtitle() {
		return "";
	}

	function isValid() {
		return $this->valid;
	}

	function loadSubjects() {
		$subjects = [];
		if ($this->hooplaRawMetadata->genres) {
			$subjects = $this->hooplaRawMetadata->genres;
		}
		global $interface;
		$interface->assign('subjects', $subjects);
	}

	function getActions() {
		//TODO: If this is added to the related record, pass in the value
		$actions = [];

		/** @var Library $searchLibrary */
		$searchLibrary = Library::getSearchLibrary();
		if ($searchLibrary->hooplaLibraryID > 0) { // Library is enabled for Hoopla patron action integration
			$title = translate([
				'text' => 'Check Out Hoopla',
				'isPublicFacing' => true,
			]);
			$actions[] = [
				'onclick' => "return AspenDiscovery.Hoopla.getCheckOutPrompts('{$this->id}')",
				'title' => $title,
			];

		} else {
			$actions[] = $this->getAccessLink();
		}

		return $actions;
	}

	public function getAccessLink() {
		$title = translate([
			'text' => 'hoopla_url_action',
			'isPublicFacing' => true,
		]);
		$accessLink = [
			'url' => $this->hooplaRawMetadata->url,
			'title' => $title,
			'requireLogin' => false,
		];
		return $accessLink;
	}

	/**
	 * Get an array of physical descriptions of the item.
	 *
	 * @access  protected
	 * @return  array
	 */
	public function getPhysicalDescriptions() {
		$physicalDescriptions = [];
		if (!empty($this->hooplaRawMetadata->duration)) {
			$physicalDescriptions[] = $this->hooplaRawMetadata->duration;
		}
		return $physicalDescriptions;
	}

	function getHooplaCoverUrl() {
		return $this->hooplaRawMetadata->coverImageUrl;
	}

	function getStatusSummary() : array {
		$relatedRecord = $this->getRelatedRecord();
		$statusSummary = [];
		if ($relatedRecord == null) {
			$statusSummary['status'] = "Unavailable";
			$statusSummary['available'] = false;
			$statusSummary['class'] = 'unavailable';
			$statusSummary['showPlaceHold'] = false;
			$statusSummary['showCheckout'] = false;
		} else {
			// Check if it's a Flex title
			if ($this->getHooplaType() == 'Flex') {
				$availableCopies = $relatedRecord->getAvailableCopies();
				if ($availableCopies > 0) {
					$statusSummary['status'] = "Available from Hoopla";
					$statusSummary['available'] = true;
					$statusSummary['class'] = 'available';
					$statusSummary['showPlaceHold'] = false;
					$statusSummary['showCheckout'] = true;
				} else {
					$statusSummary['status'] = "Checked Out";
					$statusSummary['available'] = false;
					$statusSummary['class'] = 'checkedOut';
					$statusSummary['showPlaceHold'] = true;
					$statusSummary['showCheckout'] = false;
				}
			} else {
				$statusSummary['status'] = "Available from Hoopla";
				$statusSummary['available'] = true;
				$statusSummary['class'] = 'available';
				$statusSummary['showPlaceHold'] = false;
				$statusSummary['showCheckout'] = true;
			}
		}
		return $statusSummary;
	}
}