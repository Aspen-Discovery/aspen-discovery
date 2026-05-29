<?php

require_once ROOT_DIR . '/RecordDrivers/RecordInterface.php';
require_once ROOT_DIR . '/RecordDrivers/GroupedWorkSubDriver.php';
require_once ROOT_DIR . '/Drivers/PalaceProjectDriver.php';
require_once ROOT_DIR . '/sys/PalaceProject/PalaceProjectTitle.php';
require_once ROOT_DIR . '/sys/PalaceProject/PalaceProjectTitleAvailability.php';

class PalaceProjectRecordDriver extends GroupedWorkSubDriver {
	/** @var ?PalaceProjectDriver */
	private static ?PalaceProjectDriver $driver = null;

	protected ?string $id = null;
	/** @var ?PalaceProjectTitle */
	private ?PalaceProjectTitle $palaceProjectTitle;
	private ?stdClass $palaceProjectRawMetadata = null;
	private bool $valid;

	public function __construct($recordId, $groupedWork = null) {
		if (PalaceProjectRecordDriver::$driver == null) {
			PalaceProjectRecordDriver::$driver = new PalaceProjectDriver();
		}
		$this->id = $recordId;

		$this->palaceProjectTitle = new PalaceProjectTitle();
		if (is_numeric($recordId)) {
			$this->palaceProjectTitle->id = $recordId;
		}else{
			$this->palaceProjectTitle->palaceProjectId = $recordId;
		}
		if ($this->palaceProjectTitle->find(true)) {
			$this->valid = true;
			$this->palaceProjectRawMetadata = json_decode($this->palaceProjectTitle->rawResponse);
		} else {
			$this->valid = false;
			$this->palaceProjectTitle = null;
		}
		if ($this->valid) {
			parent::__construct($groupedWork);
		}
	}

	public function getIdWithSource() : string {
		return 'palace_project:' . $this->id;
	}

	/**
	 * Load the grouped work that this record is connected to.
	 */
	public function loadGroupedWork() : void {
		if ($this->groupedWork == null) {
			require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
			require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
			$groupedWork = new GroupedWork();
			$query = "SELECT grouped_work.* FROM grouped_work INNER JOIN grouped_work_primary_identifiers ON grouped_work.id = grouped_work_id WHERE type='palace_project' AND identifier = '" . $this->getUniqueID() . "'";
			$groupedWork->query($query);

			if ($groupedWork->getNumResults() == 1) {
				$groupedWork->fetch();
				$this->groupedWork = clone $groupedWork;
			}
		}
	}

	public function getModule(): string {
		return 'PalaceProject';
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display the full record information on the Staff
	 * View tab of the record view page.
	 *
	 * @return string
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

		$interface->assign('bookcoverInfo', $this->getBookcoverInfo());

		$interface->assign('palaceProjectExtract', $this->palaceProjectRawMetadata);
		$readerName = new OverDriveDriver();
		$readerName = $readerName->getReaderName();
		$interface->assign('readerName', $readerName);

		return 'RecordDrivers/PalaceProject/staff-view.tpl';
	}

	/**
	 * Get the full title of the record.
	 *
	 * @return  string
	 */
	public function getTitle() : string {
		$title = $this->palaceProjectTitle->title;
		$subtitle = $this->getSubtitle();
		if (strlen($subtitle) > 0) {
			$title .= ': ' . $subtitle;
		}
		return $title;
	}

	/**
	 * @return  string
	 */
	public function getAuthor() : string {
		if (!empty($this->palaceProjectRawMetadata->metadata->author)) {
			if (is_array($this->palaceProjectRawMetadata->metadata->author)) {
				return $this->palaceProjectRawMetadata->metadata->author[0]->name;
			}else {
				return $this->palaceProjectRawMetadata->metadata->author->name;
			}
		}else {
			return '';
		}
	}

	/**
	 * The Table of Contents extracted from the record.
	 * Returns null if no Table of Contents is available.
	 *
	 * @access  public
	 * @return  array              Array of elements in the table of contents
	 */
	public function getTableOfContents() : array {
		return [];
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

	public function getDescription() {
		if (!empty($this->palaceProjectRawMetadata->metadata->description)) {
			return $this->palaceProjectRawMetadata->metadata->description;
		}else{
			return '';
		}
	}

	public function getMoreDetailsOptions() : array {
		global $interface;

		$isbn = $this->getCleanISBN();

		//Load table of contents
		$tableOfContents = $this->getTableOfContents();
		$interface->assign('tableOfContents', $tableOfContents);

		//Load more details options
		$moreDetailsOptions = $this->getBaseMoreDetailsOptions($isbn);

		//Other editions if applicable (only if we aren't the only record!)
		$groupedWorkDriver = $this->getGroupedWorkDriver();
		if ($groupedWorkDriver != null) {
			$relatedRecords = $groupedWorkDriver->getRelatedRecords();
			if (count($relatedRecords) > 1) {
				$interface->assign('relatedManifestations', $groupedWorkDriver->getRelatedManifestations());
				$interface->assign('workId', $groupedWorkDriver->getPermanentId());
				$moreDetailsOptions['otherEditions'] = [
					'label' => 'Other Editions and Formats',
					'body' => $interface->fetch('GroupedWork/relatedManifestations.tpl'),
					'hideByDefault' => false,
				];
			}
		}

		$moreDetailsOptions['moreDetails'] = [
			'label' => 'More Details',
			'body' => $interface->fetch('PalaceProject/view-more-details.tpl'),
		];
		$this->loadSubjects();
		$moreDetailsOptions['subjects'] = [
			'label' => 'Subjects',
			'body' => $interface->fetch('RecordDrivers/PalaceProject/view-subjects.tpl'),
		];
		$moreDetailsOptions['citations'] = [
			'label' => 'Citations',
			'body' => $interface->fetch('Record/cite.tpl'),
		];

		if ($interface->getVariable('showStaffView')) {
			$moreDetailsOptions['staff'] = [
				'label' => 'Staff View',
				'onShow' => "AspenDiscovery.PalaceProject.getStaffView('$this->id');",
				'body' => '<div id="staffViewPlaceHolder">Loading Staff View.</div>',
			];
		}

		return $this->filterAndSortMoreDetailsOptions($moreDetailsOptions);
	}

	public function getISBNs() : array {
		return [];
	}

	public function getOCLCNumber() : string {
		return '';
	}

	public function getISSNs() : array {
		return [];
	}

	protected ?array $_actions = null;

	public function getRecordActions($relatedRecord, $variationId, $isAvailable, $isHoldable, $volumeData = null) : array {
		if ($this->_actions === null) {
			$this->_actions = [];
			// To start, we will just display an Access Online Link
			//Check to see if the title is on hold or checked out to the patron.
			$loadDefaultActions = true;
			if (UserAccount::isLoggedIn()) {
				$user = UserAccount::getActiveUserObj();
				$this->_actions = array_merge($this->_actions, $user->getCirculatedRecordActionsWithLazyLoading('palace_project', $this->id));
				$loadDefaultActions = count($this->_actions) == 0;
			}
			//Check if catalog is offline and login for eResources should be allowed for offline
			global $offlineMode;
			global $loginAllowedWhileOffline;
			if ($loadDefaultActions && (!$offlineMode || $loginAllowedWhileOffline)) {
				$needsLazyLoading = false;
				if (UserAccount::isLoggedIn()) {
					$user = UserAccount::getActiveUserObj();
					if (!$user->areCirculationActionsDisabled()) $needsLazyLoading = !$user->isCirculationCacheFresh();
				}

				$titleAvailability = $this->getTitleAvailability();
				if ($titleAvailability != null) {
					if (!$titleAvailability->needsHold) {
						$checkoutAction = [
							'title' => translate([
								'text' => 'Check Out Palace Project',
								'isPublicFacing' => true,
							]),
							'onclick' => "return AspenDiscovery.PalaceProject.checkOutTitle('$this->id', this);",
							'requireLogin' => false,
							'type' => 'palace_project_checkout',
						];
						if ($needsLazyLoading) {
							$checkoutAction['data-needs-refresh'] = 'true';
							$checkoutAction['data-record-id'] = $this->id;
							$checkoutAction['data-record-source'] = 'palace_project';
						}
						$this->_actions[] = $checkoutAction;
					}else{
						$holdAction = [
							'title' => translate([
								'text' => 'Place Hold Palace Project',
								'isPublicFacing' => true,
							]),
							'onclick' => "return AspenDiscovery.PalaceProject.placeHold('$this->id', this);",
							'requireLogin' => false,
							'type' => 'palace_project_hold',
						];
						if ($needsLazyLoading) {
							$holdAction['data-needs-refresh'] = 'true';
							$holdAction['data-record-id'] = $this->id;
							$holdAction['data-record-source'] = 'palace_project';
						}
						$this->_actions[] = $holdAction;
					}
				}
			}

			$this->_actions = array_merge($this->_actions, $this->getPreviewActions());
		}
		return $this->_actions;
	}

	function getBorrowLink() : ?string {
		$titleAvailability = $this->getTitleAvailability();
		if ($titleAvailability != null) {
			return $titleAvailability->borrowLink;
		}

		return null;
	}

	function getActiveCollectionIds() : array {
		return PalaceProjectRecordDriver::$driver->getActiveCollectionIds();
	}

	function getTitleAvailability() : ?PalaceProjectTitleAvailability{
		$collections = $this->getActiveCollectionIds();
		if (!empty($collections)){
			$titleAvailability = new PalaceProjectTitleAvailability();
			$titleAvailability->titleId = $this->id;
			$titleAvailability->whereAddIn('collectionId', $collections, false);
			$titleAvailability->deleted = 0;
			if ($titleAvailability->find(true)){
				return $titleAvailability;
			}
		}

		return null;
	}

	function getPreviewUrl() : ?string {
		//Get the preview URL based on the title availability.
		//To do that, we need to get the settings for the active user and/or library
		//Then we can get a list of availability
		$titleAvailability = $this->getTitleAvailability();
		if ($titleAvailability !== null){
			return $titleAvailability->previewLink;
		}
		return null;
	}

	function getPreviewActions() : array {
		$actions = [];
		if ($this->getPreviewUrl() != null) {
			//eBook preview
			$actions[] = [
				'title' => translate([
					'text' => 'Preview',
					'isPublicFacing' => true,
				]),
				'onclick' => "return AspenDiscovery.PalaceProject.showPreview('$this->id');",
				'requireLogin' => false,
				'type' => 'project_palace_sample',
				'btnType' => 'btn-info',
			];
		}
		return $actions;
	}

	/**
	 * Returns an array of contributors to the title, ideally with the role appended after a pipe symbol
	 * @return array
	 */
	function getContributors() : array {
		$contributors = [];
		if (!empty($this->palaceProjectRawMetadata->metadata->author)) {
			if (!empty($this->palaceProjectRawMetadata->metadata->author)) {
				if (is_array($this->palaceProjectRawMetadata->metadata->author)) {
					foreach ($this->palaceProjectRawMetadata->metadata->author as $author) {
						$contributors[] = $author->name;
					}
				}
			}
		}
		if (!empty($this->palaceProjectRawMetadata->metadata->narrator)) {
			$narrator = $this->palaceProjectRawMetadata->metadata->narrator;
			$contributors[] = $narrator->name . '|Narrator';
		}
		return $contributors;
	}

	/**
	 * Get the edition of the current record.
	 *
	 * @access  protected
	 * @return  array
	 */
	function getEditions() : array {
		// No specific information provided by Palace Project
		return [];
	}

	function getType() : string {
		$metadata = $this->palaceProjectRawMetadata->metadata;
		return $metadata->{'@type'};
	}
	/**
	 * @return array
	 */
	function getFormats() : array {
		return match ($this->getType()) {
			'http://schema.org/EBook', 'http://schema.org/Book' => ['eBook'],
			'http://bib.schema.org/Audiobook', 'http://schema.org/Audiobook' => ['eAudiobook'],
			default => ['Unknown'],
		};

	}

	/**
	 * Get an array of all the format categories associated with the record.
	 */
	function getFormatCategory() : string|array|null {
		return match ($this->getType()) {
			'http://schema.org/EBook', 'http://schema.org/Book' => ['eBook'],
			'http://bib.schema.org/Audiobook', 'http://schema.org/Audiobook' => ['Audio Books'],
			default => ['Unknown'],
		};
	}

	public function getLanguage() {
		//TODO: Translate this to not use the 2 letter code
		return  $this->palaceProjectRawMetadata->metadata->language;
	}

	public function getNumHolds(): int {
		//TODO:  Check to see if we can determine number of holds on a title
		return 0;
	}

	/**
	 * @return array
	 */
	function getPlacesOfPublication() : array {
		//Not provided within the metadata
		return [];
	}

	/**
	 * Returns the primary author of the work
	 * @return String
	 */
	function getPrimaryAuthor() : string {
		if (!empty($this->palaceProjectRawMetadata->metadata->author)) {
			if (is_array($this->palaceProjectRawMetadata->metadata->author)) {
				return $this->palaceProjectRawMetadata->metadata->author[0]->name;
			}else {
				return $this->palaceProjectRawMetadata->metadata->author->name;
			}
		}else {
			return '';
		}
	}

	/**
	 * @return array
	 */
	function getPublishers() : array {
		$publishers = [];
		if (!empty($this->palaceProjectRawMetadata->metadata->publisher)) {
			$publishers[] = $this->palaceProjectRawMetadata->metadata->publisher->name;
		}
		return $publishers;
	}

	/**
	 * @return array
	 */
	function getPublicationDates() : array {
		$publicationDates = [];
		if (!empty($this->palaceProjectRawMetadata->metadata->published)) {
			$publicationDates[] = date('Y', strtotime($this->palaceProjectRawMetadata->metadata->published));
		}
		return $publicationDates;
	}

	public function getRecordType() : string {
		return 'palace_project';
	}

	function getRelatedRecord() : ?Grouping_Record {
		$id = 'palace_project:' . $this->id;
		return $this->getGroupedWorkDriver()->getRelatedRecord($id);
	}

	public function getSemanticData() : ?array {
		// Schema.org
		// Get information about the record
		$relatedRecord = $this->getRelatedRecord();
		if ($relatedRecord != null) {
			require_once ROOT_DIR . '/RecordDrivers/LDRecordOffer.php';
			$linkedDataRecord = new LDRecordOffer($relatedRecord);
			$semanticData [] = [
				'@context' => 'http://schema.org',
				'@type' => $linkedDataRecord->getWorkType(),
				'name' => $this->getTitle(),
				'creator' => $this->getPrimaryAuthor(),
				'bookEdition' => $this->getEditions(),
				'isAccessibleForFree' => true,
				'image' => $this->getBookcoverUrl('medium'),
				"offers" => $linkedDataRecord->getOffers(),
			];

			global $interface;
			$interface->assign('og_title', $this->getTitle());
			$interface->assign('og_description', $this->getDescription());
			$interface->assign('og_type', $this->getGroupedWorkDriver()->getOGType());
			$interface->assign('og_image', $this->getBookcoverUrl('medium'));
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
	function getShortTitle() : string {
		return $this->palaceProjectTitle->title;
	}

	/**
	 * Returns subtitle
	 *
	 * @return string
	 */
	function getSubtitle() : string {
		if (!empty($this->palaceProjectRawMetadata->subtitle)) {
			return $this->palaceProjectRawMetadata->subtitle;
		} else {
			return "";
		}
	}

	function isValid() : bool {
		return $this->valid;
	}

	function loadSubjects() : void {
		$subjects = [];
		if (!empty($this->palaceProjectRawMetadata->metadata->subject)) {
			$rawSubjects = $this->palaceProjectRawMetadata->metadata->subject;
			foreach ($rawSubjects as $subject) {
				$subjects[] = $subject->name;
			}
		}
		global $interface;
		$interface->assign('subjects', $subjects);
	}

	function getStatusSummary() : array {
		$relatedRecord = $this->getRelatedRecord();
		$statusSummary = [];
		if ($relatedRecord == null) {
			$statusSummary['status'] = "Unavailable";
			$statusSummary['available'] = false;
			$statusSummary['class'] = 'unavailable';
		} else {
			if ($relatedRecord->getAvailableCopies() > 0) {
				$statusSummary['status'] = "Available from Palace Project";
				$statusSummary['available'] = true;
				$statusSummary['class'] = 'available';
			} else {
				$statusSummary['status'] = 'Checked Out';
				$statusSummary['class'] = 'checkedOut';
				$statusSummary['available'] = false;
			}
		}
		return $statusSummary;
	}

	function getPalaceProjectBookcoverUrl() {
		if (!empty($this->palaceProjectRawMetadata->images)) {
			$images = $this->palaceProjectRawMetadata->images;
			foreach ($images as $image) {
				if ($image->rel == 'http://opds-spec.org/image') {
					return $image->href;
				}
			}
		}
		return null;
	}
}