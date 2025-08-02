<?php

require_once ROOT_DIR . '/RecordDrivers/RecordInterface.php';
require_once ROOT_DIR . '/RecordDrivers/GroupedWorkSubDriver.php';
require_once ROOT_DIR . '/sys/Axis360/Axis360Title.php';

class Axis360RecordDriver extends GroupedWorkSubDriver {
	protected $id;
	/** @var Axis360Title */
	private $axis360Title;
	private $axis360RawMetadata;
	private $valid;

	public function __construct($recordId, $groupedWork = null) {
		$this->id = $recordId;

		$this->axis360Title = new Axis360Title();
		$this->axis360Title->axis360Id = $recordId;
		if ($this->axis360Title->find(true)) {
			$this->valid = true;
			$this->axis360RawMetadata = json_decode($this->axis360Title->rawResponse);
		} else {
			$this->valid = false;
			$this->axis360Title = null;
		}
		if ($this->valid) {
			parent::__construct($groupedWork);
		}
	}

	public function getIdWithSource() {
		return 'axis360:' . $this->id;
	}

	/**
	 * Load the grouped work that this record is connected to.
	 */
	public function loadGroupedWork() {
		if ($this->groupedWork == null) {
			require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
			require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
			$groupedWork = new GroupedWork();
			$query = "SELECT grouped_work.* FROM grouped_work INNER JOIN grouped_work_primary_identifiers ON grouped_work.id = grouped_work_id WHERE type='axis360' AND identifier = '" . $this->getUniqueID() . "'";
			$groupedWork->query($query);

			if ($groupedWork->getNumResults() == 1) {
				$groupedWork->fetch();
				$this->groupedWork = clone $groupedWork;
			}
		}
	}

	public function getModule(): string {
		return 'Axis360';
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display the full record information on the Staff
	 * View tab of the record view page.
	 *
	 * @return  string Name of Smarty template file to display.
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

		$interface->assign('axis360Extract', $this->axis360RawMetadata);
		$readerName = new OverDriveDriver();
		$readerName = $readerName->getReaderName();
		$interface->assign('readerName', $readerName);

		return 'RecordDrivers/Axis360/staff-view.tpl';
	}

	/**
	 * Get the full title of the record.
	 *
	 * @return  string
	 */
	public function getTitle() {
		$title = $this->axis360Title->title;
		$subtitle = $this->getSubtitle();
		if (strlen($subtitle) > 0) {
			$title .= ': ' . $subtitle;
		}
		return $title;
	}

	/**
	 * @return  string
	 */
	public function getAuthor() {
		return $this->axis360Title->primaryAuthor;
	}

	/**
	 * The Table of Contents extracted from the record.
	 * Returns null if no Table of Contents is available.
	 *
	 * @access  public
	 * @return  array              Array of elements in the table of contents
	 */
	public function getTableOfContents() {
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
	public function getUniqueID() {
		return $this->id;
	}

	public function getDescription() {
		return '';
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
			'body' => $interface->fetch('Axis360/view-more-details.tpl'),
		];
		$this->loadSubjects();
		$moreDetailsOptions['subjects'] = [
			'label' => 'Subjects',
			'body' => $interface->fetch('RecordDrivers/Axis360/view-subjects.tpl'),
		];
		$moreDetailsOptions['citations'] = [
			'label' => 'Citations',
			'body' => $interface->fetch('Record/cite.tpl'),
		];

		if ($interface->getVariable('showStaffView')) {
			$moreDetailsOptions['staff'] = [
				'label' => 'Staff View',
				'onShow' => "AspenDiscovery.Axis360.getStaffView('{$this->id}');",
				'body' => '<div id="staffViewPlaceHolder">Loading Staff View.</div>',
			];
		}

		return $this->filterAndSortMoreDetailsOptions($moreDetailsOptions);
	}

	public function getISBNs() {
		return $this->getFieldValue('isbn');
	}

	public function getOCLCNumber() {
		return '';
	}

	public function getISSNs() {
		return [];
	}

	protected $_actions = null;

	public function getRecordActions($relatedRecord, $variationId, $isAvailable, $isHoldable, $volumeData = null) : array {
		if ($this->_actions === null) {
			$this->_actions = [];
			//Check to see if the title is on hold or checked out to the patron.
			$loadDefaultActions = true;
			if (UserAccount::isLoggedIn()) {
				$user = UserAccount::getActiveUserObj();
				$this->_actions = array_merge($this->_actions, $user->getCirculatedRecordActions('axis360', $this->id));
				$loadDefaultActions = count($this->_actions) == 0;
			}

			//Check if catalog is offline and login for eResources should be allowed for offline
			global $offlineMode;
			global $loginAllowedWhileOffline;
			if ($loadDefaultActions && (!$offlineMode || $loginAllowedWhileOffline)) {
				if ($isAvailable) {
					$this->_actions[] = [
						'title' => translate([
							'text' => 'Check Out Boundless',
							'isPublicFacing' => true,
						]),
						'onclick' => "return AspenDiscovery.Axis360.checkOutTitle('{$this->id}');",
						'requireLogin' => false,
						'type' => 'axis360_checkout',
					];
				} else {
					$this->_actions[] = [
						'title' => translate([
							'text' => 'Place Hold Boundless',
							'isPublicFacing' => true,
						]),
						'onclick' => "return AspenDiscovery.Axis360.placeHold('{$this->id}');",
						'requireLogin' => false,
						'type' => 'axis360_hold',
					];
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
		// TODO: Implement getContributors() method.
		$contributors = [];
		if (!empty($this->axis360RawMetadata->authors)) {
			$authors = $this->axis360RawMetadata->authors;
			if (is_array($authors->author)) {
				foreach ($authors->author as $author) {
					$contributors[] = $author;
				}
			} else {
				$contributors[] = $authors->author;
			}
		}
		if (!empty($this->axis360RawMetadata->narrators)) {
			$authors = $this->axis360RawMetadata->narrators;
			if (is_array($authors->author)) {
				foreach ($authors->author as $author) {
					$contributors[] = $author . '|Narrator';
				}
			} else {
				$contributors[] = $authors->author . '|Narrator';
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
		// No specific information provided by Boundless
		return [];
	}

	/**
	 * @return array
	 */
	function getFormats() {
		if ($this->axis360RawMetadata->formatType == 'eBook') {
			return ['eBook'];
		} elseif ($this->axis360RawMetadata->formatType == 'eAudiobook') {
			return ['eAudiobook'];
		} else {
			return ['Unknown'];
		}
	}

	/**
	 * Get an array of all the format categories associated with the record.
	 *
	 * @return  array
	 */
	function getFormatCategory() {
		if ($this->axis360RawMetadata->formatType == 'eBook') {
			return ['eBook'];
		} elseif ($this->axis360RawMetadata->formatType == 'eAudiobook') {
			return ['Audio Books'];
		} else {
			return ['Unknown'];
		}
	}

	public function getLanguage() {
		return 'English';
	}

	public function getNumHolds(): int {
		//TODO:  Check to see if we can determine number of holds on a title
		return 0;
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
		return $this->axis360Title->primaryAuthor;
	}

	/**
	 * @return array
	 */
	function getPublishers() {
		return [];
	}

	/**
	 * @return array
	 */
	function getPublicationDates() {
		return [];
	}

	public function getRecordType() {
		return 'axis360';
	}

	function getRelatedRecord() {
		$id = 'axis360:' . $this->id;
		return $this->getGroupedWorkDriver()->getRelatedRecord($id);
	}

	public function getSemanticData() {
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
	function getShortTitle() {
		return $this->axis360Title->title;
	}

	/**
	 * Returns subtitle
	 *
	 * @return string
	 */
	function getSubtitle() {
		if (!empty($this->axis360RawMetadata->subtitle)) {
			return $this->axis360RawMetadata->subtitle;
		} else {
			return "";
		}
	}

	function isValid() {
		return $this->valid;
	}

	function loadSubjects() {
		$subjects = [];
		$rawSubjects = $this->getMetadataFieldArray('subject');
		foreach ($rawSubjects as $key => $subject) {
			$subjects[$key] = str_replace('/', ' -- ', $subject);
		}
		global $interface;
		$interface->assign('subjects', $subjects);
	}

	function getMetadataFieldArray($fieldName) {
		foreach ($this->axis360RawMetadata->fields as $fieldInfo) {
			if ($fieldInfo->name == $fieldName) {
				return $fieldInfo->values;
			}
		}
		return [];
	}

	/**
	 * @param User $patron
	 * @return string mixed
	 */
	public function getAccessOnlineLinkUrl($patron) {
		global $configArray;
		return $configArray['Site']['url'] . '/Axis360/' . $this->id . '/AccessOnline?patronId=' . $patron->id;
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
			if ($relatedRecord->getAvailableCopies() > 0) {
				$statusSummary['status'] = "Available from Boundless";
				$statusSummary['available'] = true;
				$statusSummary['class'] = 'available';
				$statusSummary['showPlaceHold'] = false;
				$statusSummary['showCheckout'] = true;
			} else {
				$statusSummary['status'] = 'Checked Out';
				$statusSummary['class'] = 'checkedOut';
				$statusSummary['available'] = false;
				$statusSummary['showPlaceHold'] = true;
				$statusSummary['showCheckout'] = false;
			}
		}
		return $statusSummary;
	}

	function getFieldValue($fieldName) {
		foreach ($this->axis360RawMetadata->fields as $field) {
			if ($field->name == $fieldName) {
				return $field->values;
			}
		}
		return "";
	}
}