<?php

require_once ROOT_DIR . '/RecordDrivers/RecordInterface.php';
require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
require_once ROOT_DIR . '/RecordDrivers/GroupedWorkSubDriver.php';
require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibraryProduct.php';
require_once ROOT_DIR . '/sys/Utils/EncodingUtils.php';

class CloudLibraryRecordDriver extends MarcRecordDriver {
	/** @var CloudLibraryProduct */
	private $cloudLibraryProduct;

	public function __construct($recordId, $groupedWork = null) {
		$this->id = $recordId;

		$this->cloudLibraryProduct = new CloudLibraryProduct();
		$this->cloudLibraryProduct->cloudLibraryId = $recordId;
		if ($this->cloudLibraryProduct->find(true)) {
			$this->valid = true;
		} else {
			$this->valid = false;
			$this->cloudLibraryProduct = null;
		}
		if ($this->valid) {
			$marcRecord = $this->getMarcRecord();
			parent::__construct($marcRecord, $groupedWork);
		}
	}

	/**
	 * @return ?File_MARC_Record
	 */
	public function getMarcRecord(): ?File_MARC_Record {
		if ($this->marcRecord == null) {
			$marcData = $this->cloudLibraryProduct->rawResponse;
			$marcRecordList = new File_MARC($marcData, File_MARC::SOURCE_STRING);
			$this->marcRecord = $marcRecordList->next();
		}
		return $this->marcRecord;
	}

	public function getIdWithSource() {
		return 'cloud_library:' . $this->id;
	}

	/**
	 * Load the grouped work that this record is connected to.
	 */
	public function loadGroupedWork() {
		if ($this->groupedWork == null) {
			require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
			require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
			$groupedWork = new GroupedWork();
			$query = "SELECT grouped_work.* FROM grouped_work INNER JOIN grouped_work_primary_identifiers ON grouped_work.id = grouped_work_id WHERE type='cloud_library' AND identifier = '" . $this->getUniqueID() . "'";
			$groupedWork->query($query);

			if ($groupedWork->getNumResults() == 1) {
				$groupedWork->fetch();
				$this->groupedWork = clone $groupedWork;
			}
		}
	}

	public function getCloudLibraryBookcoverUrl() {
		$marcRecord = $this->getMarcRecord();
		/** @var File_MARC_Data_Field[] $fields */
		$fields = $marcRecord->getFields("856");
		foreach ($fields as $field) {
			$subfield3 = $field->getSubfield('3');
			if (!empty($subfield3) && $subfield3->getData() == 'Cover Image') {
				$subfieldU = $field->getSubfield('u');
				if ($subfieldU->getData() != 'token=nobody') {
					return $subfieldU->getData();
				}
			}
		}
		$url = "https://images.yourcloudlibrary.com/delivery/img?type=DOCUMENTIMAGE&documentID={$this->id}&size=NORMAL&src=norm";
		return $url;
	}

	public function getModule(): string {
		return 'CloudLibrary';
	}

	/**
	 * Get the full title of the record.
	 *
	 * @return  string
	 */
	public function getTitle() {
		$title = $this->cloudLibraryProduct->title;
		$subtitle = $this->getSubtitle();
		if (strlen($subtitle) > 0) {
			$title .= ': ' . $subtitle;
		}
		return EncodingUtils::toUTF8($title);
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

	public function getMoreDetailsOptions() {
		global $interface;

		$isbn = $this->getCleanISBN();

		//Load table of contents
		$tableOfContents = $this->getTableOfContents();
		$interface->assign('tableOfContents', $tableOfContents);

		//Load more details options
		$moreDetailsOptions = $this->getBaseMoreDetailsOptions($isbn);

		$availability = $this->getAvailability();
		$interface->assign('availability', $availability);

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
			'body' => $interface->fetch('CloudLibrary/view-more-details.tpl'),
		];
		$this->loadSubjects();
		$moreDetailsOptions['subjects'] = [
			'label' => 'Subjects',
			'body' => $interface->fetch('Record/view-subjects.tpl'),
		];
		$moreDetailsOptions['citations'] = [
			'label' => 'Citations',
			'body' => $interface->fetch('Record/cite.tpl'),
		];
		$moreDetailsOptions['copyDetails'] = [
			'label' => 'Copy Details',
			'body' => $interface->fetch('CloudLibrary/view-copies.tpl'),
		];
		if ($interface->getVariable('showStaffView')) {
			$moreDetailsOptions['staff'] = [
				'label' => 'Staff View',
				'onShow' => "AspenDiscovery.CloudLibrary.getStaffView('{$this->id}');",
				'body' => '<div id="staffViewPlaceHolder">' . translate([
						'text' => 'Loading Staff View.',
						'isPublicFacing' => true,
					]) . '</div>',
			];
		}

		return $this->filterAndSortMoreDetailsOptions($moreDetailsOptions);
	}

	protected ?array $_actions = null;

	public function getRecordActions($relatedRecord, $variationId, $isAvailable, $isHoldable, $volumeData = null) : array {
		if ($this->_actions === null) {
			$this->_actions = [];
			//Check to see if the title is on hold or checked out to the patron.
			$loadDefaultActions = true;
			if (UserAccount::isLoggedIn()) {
				$user = UserAccount::getActiveUserObj();
				$this->_actions = array_merge($this->_actions, $user->getCirculatedRecordActions('cloud_library', $this->id));
				$loadDefaultActions = count($this->_actions) == 0;
			}

			//Check if catalog is offline and login for eResources should be allowed for offline
			global $offlineMode;
			global $loginAllowedWhileOffline;
			if ($loadDefaultActions && (!$offlineMode || $loginAllowedWhileOffline)) {
				if ($isAvailable) {
					$userId = UserAccount::getActiveUserId();
					if ($userId == false) {
						$userId = 'null';
					}
					$this->_actions[] = [
						'title' => translate([
							'text' => 'Check Out cloudLibrary',
							'isPublicFacing' => true,
						]),
						'onclick' => "return AspenDiscovery.CloudLibrary.checkOutTitle({$userId}, '{$this->id}');",
						'requireLogin' => false,
						'type' => 'cloud_library_checkout',
					];
				} else {
					$this->_actions[] = [
						'title' => translate([
							'text' => 'Place Hold cloudLibrary',
							'isPublicFacing' => true,
						]),
						'onclick' => "return AspenDiscovery.CloudLibrary.placeHold('{$this->id}');",
						'requireLogin' => false,
						'type' => 'cloud_library_hold',
					];
				}
			}
		}
		return $this->_actions;
	}

	/**
	 * @return array
	 */
	function getFormats() {
		if ($this->cloudLibraryProduct) {
			return [$this->cloudLibraryProduct->format];
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
		if ($this->cloudLibraryProduct) {
			if ($this->cloudLibraryProduct->format == "eAudio") {
				return [
					'eBook',
					'Audio Books',
				];
			} else {
				return ['eBook'];
			}
		} else {
			return ['Unknown'];
		}
	}

	public function getNumHolds(): int {
		//TODO:  Check to see if we can determine number of holds on a title
		return 0;
	}

	/**
	 * Returns the primary author of the work
	 * @return String
	 */
	function getPrimaryAuthor() {
		return $this->cloudLibraryProduct->author;
	}

	public function getRecordType() {
		return 'cloud_library';
	}

	function getRelatedRecord() {
		$id = 'cloud_library:' . $this->id;
		return $this->getGroupedWorkDriver()->getRelatedRecord($id);
	}

	public function getSemanticData() {
		// Schema.org
		// Get information about the record
		if ($this->getRelatedRecord() != null) {
			require_once ROOT_DIR . '/RecordDrivers/LDRecordOffer.php';
			$linkedDataRecord = new LDRecordOffer($this->getRelatedRecord());
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
		}

		global $interface;
		$interface->assign('og_title', $this->getTitle());
		$interface->assign('og_description', $this->getDescriptionFast());
		if ($this->getGroupedWorkDriver() != null) {
			$interface->assign('og_type', $this->getGroupedWorkDriver()->getOGType());
		}
		$interface->assign('og_image', $this->getBookcoverUrl('medium'));
		$interface->assign('og_url', $this->getAbsoluteUrl());
		return $semanticData;
	}

	/**
	 * Returns title without subtitle
	 *
	 * @return string
	 */
	function getShortTitle() {
		return EncodingUtils::toUTF8($this->cloudLibraryProduct->title);
	}

	/**
	 * Returns subtitle
	 *
	 * @return string
	 */
	function getSubtitle() {
		return EncodingUtils::toUTF8($this->cloudLibraryProduct->subTitle);
	}

	function isValid() {
		return $this->valid;
	}

	function getStatusSummary() : array {
		$relatedRecord = $this->getRelatedRecord();
		$statusSummary = [];
		if ($relatedRecord != null && $relatedRecord->getAvailableCopies() > 0) {
			$statusSummary['status'] = "Available from cloudLibrary";
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
		return $statusSummary;
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display the full record information on the Staff
	 * View tab of the record view page.
	 *
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getStaffView() {
		global $interface;
		$this->getGroupedWorkDriver()->assignGroupedWorkStaffView();

		$interface->assign('bookcoverInfo', $this->getBookcoverInfo());

		$interface->assign('cloudLibraryProduct', $this->cloudLibraryProduct);

		$interface->assign('marcRecord', $this->getMarcRecord());

		return 'RecordDrivers/CloudLibrary/staff.tpl';
	}

	function getAvailability() {
		require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibraryAvailability.php';
		$availability = new CloudLibraryAvailability();
		$availability->cloudLibraryId = $this->id;
		$availability->find(true);
		return $availability;
	}

	function getAccessOnlineLinkUrl($patronId) {
		global $configArray;
		return $configArray['Site']['url'] . '/CloudLibrary/' . $this->id . '/AccessOnline?patronId=' . $patronId;
	}
}