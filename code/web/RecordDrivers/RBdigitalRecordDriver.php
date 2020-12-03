<?php

require_once ROOT_DIR . '/RecordDrivers/RecordInterface.php';
require_once ROOT_DIR . '/RecordDrivers/GroupedWorkSubDriver.php';
require_once ROOT_DIR . '/sys/RBdigital/RBdigitalProduct.php';

class RBdigitalRecordDriver extends GroupedWorkSubDriver
{
	private $id;
	/** @var RBdigitalProduct */
	private $rbdigitalProduct;
	private $rbdigitalRawMetadata;
	private $valid;

	public function __construct($recordId, $groupedWork = null)
	{
		$this->id = $recordId;

		$this->rbdigitalProduct = new RBdigitalProduct();
		$this->rbdigitalProduct->rbdigitalId = $recordId;
		if ($this->rbdigitalProduct->find(true)) {
			$this->valid = true;
			$this->rbdigitalRawMetadata = json_decode($this->rbdigitalProduct->rawResponse);
		} else {
			$this->valid = false;
			$this->rbdigitalProduct = null;
		}
		if ($this->valid) {
			parent::__construct($groupedWork);
		}
	}

	public function getIdWithSource()
	{
		return 'rbdigital:' . $this->id;
	}

	/**
	 * Load the grouped work that this record is connected to.
	 */
	public function loadGroupedWork()
	{
		if ($this->groupedWork == null) {
			require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
			require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
			$groupedWork = new GroupedWork();
			$query = "SELECT grouped_work.* FROM grouped_work INNER JOIN grouped_work_primary_identifiers ON grouped_work.id = grouped_work_id WHERE type='rbdigital' AND identifier = '" . $this->getUniqueID() . "'";
			$groupedWork->query($query);

			if ($groupedWork->getNumResults() == 1) {
				$groupedWork->fetch();
				$this->groupedWork = clone $groupedWork;
			}
		}
	}

	public function getRBdigitalBookcoverUrl($size = 'small')
	{
		$images = $this->rbdigitalRawMetadata->images;
		foreach ($images as $image) {
			if ($image->name == 'medium' && $size == 'small') {
				return $image->url;
			}
			if ($image->name == 'large' && $size == 'medium') {
				return $image->url;
			}
			if ($image->name == 'xx-large' && $size == 'large') {
				return $image->url;
			}
		}
		return null;
	}

	public function getModule()
	{
		return 'RBdigital';
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

		$interface->assign('rbdigitalExtract', $this->rbdigitalRawMetadata);
		return 'RecordDrivers/RBdigital/staff-view.tpl';
	}

	/**
	 * Get the full title of the record.
	 *
	 * @return  string
	 */
	public function getTitle()
	{
		$title = $this->rbdigitalProduct->title;
		$subtitle = $this->getSubtitle();
		if (strlen($subtitle) > 0) {
			$title .= ': ' . $subtitle;
		}
		return $title;
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
		// TODO: Implement getTableOfContents() method.
		return array();
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

	public function getDescription()
	{
		return $this->rbdigitalRawMetadata->shortDescription;
	}

	public function getMoreDetailsOptions()
	{
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
				$moreDetailsOptions['otherEditions'] = array(
					'label' => 'Other Editions and Formats',
					'body' => $interface->fetch('GroupedWork/relatedManifestations.tpl'),
					'hideByDefault' => false
				);
			}
		}

		$moreDetailsOptions['moreDetails'] = array(
			'label' => 'More Details',
			'body' => $interface->fetch('RBdigital/view-more-details.tpl'),
		);
		$this->loadSubjects();
		$moreDetailsOptions['subjects'] = array(
			'label' => 'Subjects',
			'body' => $interface->fetch('RecordDrivers/RBdigital/view-subjects.tpl'),
		);
		$moreDetailsOptions['citations'] = array(
			'label' => 'Citations',
			'body' => $interface->fetch('Record/cite.tpl'),
		);

		if ($interface->getVariable('showStaffView')) {
			$moreDetailsOptions['staff'] = array(
				'label' => 'Staff View',
				'onShow' => "AspenDiscovery.RBdigital.getStaffView('{$this->id}');",
				'body' => '<div id="staffViewPlaceHolder">Loading Staff View.</div>',
			);
		}

		return $this->filterAndSortMoreDetailsOptions($moreDetailsOptions);
	}

	public function getISBNs()
	{
		$isbns = [];
		$isbns[] = $this->rbdigitalRawMetadata->isbn;
		return $isbns;
	}

	public function getISSNs()
	{
		return array();
	}

	public function getRecordActions($relatedRecord, $isAvailable, $isHoldable, $isBookable, $volumeData = null)
	{
		$actions = array();
		if ($isAvailable) {
			$actions[] = array(
				'title' => 'Check Out RBdigital',
				'onclick' => "return AspenDiscovery.RBdigital.checkOutTitle('{$this->id}');",
				'requireLogin' => false,
				'type' => 'rbdigital_checkout'
			);
		} else {
			$actions[] = array(
				'title' => 'Place Hold RBdigital',
				'onclick' => "return AspenDiscovery.RBdigital.placeHold('{$this->id}');",
				'requireLogin' => false,
				'type' => 'rbdigital_hold'
			);
		}
		return $actions;
	}

	/**
	 * Returns an array of contributors to the title, ideally with the role appended after a pipe symbol
	 * @return array
	 */
	function getContributors()
	{
		// TODO: Implement getContributors() method.
		$contributors = array();
		if (isset($this->rbdigitalRawMetadata->authors)) {
			$authors = $this->rbdigitalRawMetadata->authors;
			foreach ($authors as $author) {
				//TODO: Reverse name?
				$contributors[] = $author->text;
			}
		}
		if (isset($this->rbdigitalRawMetadata->narrators)) {
			$authors = $this->rbdigitalRawMetadata->narrators;
			foreach ($authors as $author) {
				//TODO: Reverse name?
				$contributors[] = $author->text . '|Narrator';
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
	function getEditions()
	{
		// No specific information provided by RBdigital
		return array();
	}

	/**
	 * @return array
	 */
	function getFormats()
	{
		if ($this->rbdigitalProduct->mediaType == "eAudio") {
			return ['eAudiobook'];
		} elseif ($this->rbdigitalProduct->mediaType == "eMagazine") {
			return ['eMagazine'];
		} else {
			return ['eBook'];
		}
	}

	/**
	 * Get an array of all the format categories associated with the record.
	 *
	 * @return  array
	 */
	function getFormatCategory()
	{
		if ($this->rbdigitalProduct->mediaType == "eAudio") {
			return ['eBook', 'Audio Books'];
		} else {
			return ['eBook'];
		}
	}

	public function getLanguage()
	{
		return $this->rbdigitalProduct->language;
	}

	public function getNumHolds()
	{
		//TODO:  Check to see if we can determine number of holds on a title
		return 0;
	}

	/**
	 * @return array
	 */
	function getPlacesOfPublication()
	{
		//Not provided within the metadata
		return array();
	}

	/**
	 * Returns the primary author of the work
	 * @return String
	 */
	function getPrimaryAuthor()
	{
		return $this->rbdigitalProduct->primaryAuthor;
	}

	/**
	 * @return array
	 */
	function getPublishers()
	{
		return [$this->rbdigitalRawMetadata->publisher->text];
	}

	/**
	 * @return array
	 */
	function getPublicationDates()
	{
		return [$this->rbdigitalRawMetadata->releasedDate];
	}

	public function getRecordType()
	{
		return 'rbdigital';
	}

	function getRelatedRecord()
	{
		$id = 'rbdigital:' . $this->id;
		return $this->getGroupedWorkDriver()->getRelatedRecord($id);
	}

	public function getSemanticData()
	{
		// Schema.org
		// Get information about the record
		$relatedRecord = $this->getRelatedRecord();
		if ($relatedRecord != null) {
			require_once ROOT_DIR . '/RecordDrivers/LDRecordOffer.php';
			$linkedDataRecord = new LDRecordOffer($relatedRecord);
			$semanticData [] = array(
				'@context' => 'http://schema.org',
				'@type' => $linkedDataRecord->getWorkType(),
				'name' => $this->getTitle(),
				'creator' => $this->getPrimaryAuthor(),
				'bookEdition' => $this->getEditions(),
				'isAccessibleForFree' => true,
				'image' => $this->getBookcoverUrl('medium'),
				"offers" => $linkedDataRecord->getOffers()
			);

			global $interface;
			$interface->assign('og_title', $this->getTitle());
			$interface->assign('og_type', $this->getGroupedWorkDriver()->getOGType());
			$interface->assign('og_image', $this->getBookcoverUrl('medium'));
			$interface->assign('og_url', $this->getAbsoluteUrl());
			return $semanticData;
		}else{
			return null;
		}
	}

	/**
	 * Returns title without subtitle
	 *
	 * @return string
	 */
	function getShortTitle()
	{
		return $this->rbdigitalProduct->title;
	}

	/**
	 * Returns subtitle
	 *
	 * @return string
	 */
	function getSubtitle()
	{
		if ($this->rbdigitalRawMetadata->hasSubtitle) {
			return $this->rbdigitalRawMetadata->subtitle;
		} else {
			return "";
		}
	}

	function isValid()
	{
		return $this->valid;
	}

	function loadSubjects()
	{
		$subjects = [];
		if ($this->rbdigitalRawMetadata->genres) {
			foreach ($this->rbdigitalRawMetadata->genres as $genre) {
				$subjects[] = $genre->text;
			}
		}
		global $interface;
		$interface->assign('subjects', $subjects);
	}

	/**
	 * @param User $patron
	 * @return string mixed
	 */
	public function getAccessOnlineLinkUrl($patron)
	{
		global $configArray;
		return $configArray['Site']['url'] . '/RBdigital/' . $this->id . '/AccessOnline?patronId=' . $patron->id;
	}

	function getStatusSummary()
	{
		$relatedRecord = $this->getRelatedRecord();
		$statusSummary = array();
		if ($relatedRecord == null){
			$statusSummary['status'] = "Unavailable";
			$statusSummary['available'] = false;
			$statusSummary['class'] = 'unavailable';
			$statusSummary['showPlaceHold'] = false;
			$statusSummary['showCheckout'] = false;
		}else{
			if ($relatedRecord->getAvailableCopies() > 0) {
				$statusSummary['status'] = "Available from RBdigital";
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
}