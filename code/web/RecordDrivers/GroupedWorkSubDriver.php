<?php
/**
 * An interface that fully defines records that can be constituent components
 * of a grouped work.  These are not stored within the index so they load data
 * from the database or from MARC records
 */
require_once ROOT_DIR . '/RecordDrivers/RecordInterface.php';

abstract class GroupedWorkSubDriver extends RecordInterface {
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
	 * @param GroupedWork $groupedWork ;
	 * @access  public
	 */
	public function __construct($groupedWork = null) {
		if ($groupedWork == null) {
			$this->loadGroupedWork();
		} else {
			$this->groupedWork = $groupedWork;
		}
	}

	public abstract function getIdWithSource();

	public function getId() {
		return $this->id;
	}

	public function getAcceleratedReaderData() {
		return $this->getGroupedWorkDriver()->getAcceleratedReaderData();
	}

	public function getAcceleratedReaderDisplayString() {
		return $this->getGroupedWorkDriver()->getAcceleratedReaderDisplayString();
	}

	function getBookcoverUrl($size = 'small', $absolutePath = false) {
		$id = $this->getIdWithSource();
		$bookCoverUrl = "/bookcover.php?id={$id}&amp;size={$size}";
		$isbn = $this->getCleanISBN();
		if ($isbn) {
			$bookCoverUrl .= "&amp;isn={$isbn}";
		}
		$upc = $this->getCleanUPC();
		if ($upc) {
			$bookCoverUrl .= "&amp;upc={$upc}";
		}
		$issn = $this->getCleanISSN();
		if ($issn) {
			$bookCoverUrl .= "&amp;issn={$issn}";
		}
		if ($absolutePath) {
			global $configArray;
			$bookCoverUrl = $configArray['Site']['url'] . $bookCoverUrl;
		}
		return $bookCoverUrl;
	}

	/**
	 * Get text that can be displayed to represent this record in
	 * breadcrumbs.
	 *
	 * @access  public
	 * @return  string              Breadcrumb text to represent this record.
	 */
	public function getBreadcrumb() {
		return $this->getShortTitle();
	}

	/**
	 * Assign necessary Smarty variables and return a template name
	 * to load in order to display the requested citation format.
	 * For legal values, see getCitationFormats().  Returns null if
	 * format is not supported.
	 *
	 * @param string $format Citation format to display.
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getCitation($format) {
		require_once ROOT_DIR . '/sys/CitationBuilder.php';
		global $interface;
		// Build author list:
		$authors = [];
		$primary = $this->getPrimaryAuthor();
		if (!empty($primary)) {
			$authors[] = $primary;
		}
		$authors = array_unique(array_merge($authors, $this->getContributors()));

		// Collect all details for citation builder:
		$publishers = $this->getPublishers();
		$pubDates = $this->getPublicationDates();
		$pubPlaces = $this->getPlacesOfPublication();
		$placesOfPublication = $this->getPlacesOfPublication();
		$details = [
			'authors' => $authors,
			'title' => $this->getShortTitle(),
			'subtitle' => $this->getSubtitle(),
			'placeOfPublication' => count($placesOfPublication) > 0 ? $placesOfPublication[0] : null,
			'pubName' => count($publishers) > 0 ? $publishers[0] : null,
			'pubDate' => count($pubDates) > 0 ? $pubDates[0] : null,
			'edition' => $this->getEditions(),
			'format' => $this->getFormats(),
		];

		$interface->assign('dc_pubName', count($publishers) > 0 ? $publishers[0] : null);
		$interface->assign('dc_pubDate', count($pubDates) > 0 ? $pubDates[0] : null);
		$interface->assign('dc_placeOfPublication', count($placesOfPublication) > 0 ? $placesOfPublication[0] : null);

		// Build the citation:
		$citation = new CitationBuilder($details);
		switch ($format) {
			case 'APA':
				return $citation->getAPA();
			case 'AMA':
				return $citation->getAMA();
			case 'ChicagoAuthDate':
				return $citation->getChicagoAuthDate();
			case 'ChicagoHumanities':
				return $citation->getChicagoHumanities();
			case 'MLA':
				return $citation->getMLA();
			case 'Harvard':
				return $citation->getHarvard();
		}
		return '';
	}

	/**
	 * Get an array of strings representing citation formats supported
	 * by this record's data (empty if none).  Legal values: "APA", "MLA".
	 *
	 * @access  public
	 * @return  array               Strings representing citation formats.
	 */
	public function getCitationFormats() {
		return [
			'AMA',
			'APA',
			'ChicagoHumanities',
			'ChicagoAuthDate',
			'MLA',
			'Harvard',
		];
	}

	/**
	 * Return the first valid ISBN found in the record (favoring ISBN-10 over
	 * ISBN-13 when possible).
	 *
	 * @return  mixed
	 */
	public function getCleanISBN() {
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

	public function getCleanISBNs() {
		require_once ROOT_DIR . '/sys/ISBN.php';

		$cleanIsbns = [];
		// Get all the ISBNs and initialize the return value:
		$isbns = $this->getISBNs();

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
				if (!array_key_exists($isbn10, $cleanIsbns)) {
					$cleanIsbns[$isbn10] = $isbn10;
				}
			}
			if ($isbn13 = $isbnObj->get13()) {
				if (!array_key_exists($isbn13, $cleanIsbns)) {
					$cleanIsbns[$isbn13] = $isbn13;
				}
			}
		}
		return $cleanIsbns;
	}

	/**
	 * Get just the base portion of the first listed ISSN (or false if no ISSNs).
	 *
	 * @access  protected
	 * @return  mixed
	 */
	protected function getCleanISSN() {
		$issns = $this->getISSNs();
		if (empty($issns)) {
			return false;
		}
		$issn = $issns[0];
		if ($pos = strpos($issn, ' ')) {
			$issn = substr($issn, 0, $pos);
		}
		return $issn;
	}

	public function getCleanUPC() {
		$upcs = $this->getUPCs();
		if (empty($upcs)) {
			return false;
		}
		$upc = $upcs[0];
		if ($pos = strpos($upc, ' ')) {
			$upc = substr($upc, 0, $pos);
		}
		return $upc;
	}

	public function getCleanUPCs() {
		$cleanUPCs = [];
		$upcs = $this->getUPCs();
		if (empty($upcs)) {
			return $cleanUPCs;
		}
		foreach ($upcs as $upc) {
			if ($pos = strpos($upc, ' ')) {
				$upc = substr($upc, 0, $pos);
			}
			if (!array_key_exists($upc, $cleanUPCs)) {
				$cleanUPCs[$upc] = $upc;
			}
		}

		return $cleanUPCs;
	}

	/**
	 * Returns an array of contributors to the title, ideally with the role appended after a pipe symbol
	 * @return array
	 */
	abstract function getContributors();

	/**
	 * Get the edition of the current record.
	 *
	 * @access  protected
	 * @return  array
	 */
	abstract function getEditions();

	public function getExploreMoreInfo() {
		return [];
	}

	/**
	 * @return String[]
	 */
	abstract function getFormats();

	/**
	 * @return string
	 */
	public function getPrimaryFormat() {
		$formats = $this->getFormats();
		return reset($formats);
	}

	/**
	 * Get an array of all the format categories associated with the record.
	 *
	 * @return  array
	 */
	abstract function getFormatCategory();

	public function getFountasPinnellLevel() {
		return $this->getGroupedWorkDriver()->getFountasPinnellLevel();
	}

	public function getGroupedWorkId() {
		if ($this->groupedWork == null) {
			return null;
		} else {
			return $this->groupedWork->permanent_id;
		}
	}

	public function getGroupedWorkDriver() {
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		if ($this->groupedWorkDriver == null) {
			$this->groupedWorkDriver = new GroupedWorkDriver($this->getPermanentId());
		}
		return $this->groupedWorkDriver;
	}

	public function hasValidGroupedWorkDriver() {
		$groupedWorkDriver = $this->getGroupedWorkDriver();
		return $groupedWorkDriver != null && $groupedWorkDriver->isValid();
	}

	/**
	 * Get an array of all ISBNs associated with the record (may be empty).
	 *
	 * @access  protected
	 * @return  array
	 */
	public abstract function getISBNs();

	public abstract function getOCLCNumber();

	/**
	 * Get an array of all ISSNs associated with the record (may be empty).
	 *
	 * @access  public
	 * @return  array
	 */
	public abstract function getISSNs();

	public function getItemActions($itemInfo) {
		return [];
	}

	public function getLexileCode() {
		return $this->getGroupedWorkDriver()->getLexileCode();
	}

	public function getLexileScore() {
		return $this->getGroupedWorkDriver()->getLexileScore();
	}

	public function getLexileDisplayString() {
		return $this->getGroupedWorkDriver()->getLexileDisplayString();
	}

	public abstract function getLanguage();

	public abstract function getNumHolds(): int;

	public function getPermanentId() {
		return $this->getGroupedWorkId();
	}

	/**
	 * @return array
	 */
	abstract function getPlacesOfPublication();

	/**
	 * Returns the primary author of the work
	 * @return String
	 */
	abstract function getPrimaryAuthor();

	/**
	 * @return array
	 */
	abstract function getPublishers();

	/**
	 * @return array
	 */
	abstract function getPublicationDates();

	/**
	 * Get an array of publication detail lines combining information from
	 * getPublicationDates(), getPublishers() and getPlacesOfPublication().
	 *
	 * @access  public
	 * @return  array
	 */
	public function getPublicationDetails() : array {
		// $places = $this->getPlacesOfPublication();
		$placesOfPublication = $this->getPlacesOfPublication();
		$names = $this->getPublishers();
		$dates = $this->getPublicationDates();

		$i = 0;
		$returnVal = [];
		// while (isset($places[$i]) || isset($placesOfPublication[$i]) || isset($names[$i]) || isset($dates[$i])) {
		while (isset($placesOfPublication[$i]) || isset($names[$i]) || isset($dates[$i])) {
			// Put all the pieces together, and do a little processing to clean up
			// unwanted whitespace.
			// $publicationInfo = (isset($places[$i]) ? $places[$i] . ' ' : '') . (isset($placesOfPublication[$i]) ? $placesOfPublication[$i] . ' ': '') . (isset($names[$i]) ? $names[$i] . ' ' : '') . (isset($dates[$i]) ? (', ' . $dates[$i] . '.') : '');
			$publicationInfo = (isset($placesOfPublication[$i]) ? $placesOfPublication[$i] . ' ': '') . (isset($names[$i]) ? $names[$i] . ' ' : '') . (isset($dates[$i]) ? (', ' . $dates[$i] . '.') : '');
			$publicationInfo = trim(str_replace('  ', ' ', $publicationInfo));
			$publicationInfo = str_replace(' ,', ',', $publicationInfo);
			$publicationInfo = htmlentities($publicationInfo);
			$returnVal[] = $publicationInfo;
			$i++;
		}

		return $returnVal;
	}

	public function getRatingData() {
		require_once ROOT_DIR . '/services/API/WorkAPI.php';
		$workAPI = new WorkAPI();
		return $workAPI->getRatingData($this->getGroupedWorkId());
	}

	/**
	 * @param null|Grouping_Record $relatedRecord
	 * @param boolean $isAvailable
	 * @param boolean $isHoldable
	 * @param null|IlsVolumeInfo[] $volumeData
	 * @return array
	 */
	public abstract function getRecordActions($relatedRecord, $variationId, $isAvailable, $isHoldable, $volumeData = null): array ;

	/**
	 * Load Record actions when we don't have detailed information about the record yet
	 */
	public function getRecordActionsFromIndex() : array {
		$groupedWork = $this->getGroupedWorkDriver();
		if ($groupedWork != null) {
			$relatedRecords = $groupedWork->getRelatedRecords();
			foreach ($relatedRecords as $relatedRecord) {
				if ($relatedRecord->id == $this->getIdWithSource()) {
					return $relatedRecord->getActions();
				} elseif ($relatedRecord->id == strtolower($this->getIdWithSource())) {
					return $relatedRecord->getActions();
				}
			}
		}
		return [];
	}

	abstract public function getRecordType();

	public abstract function getSemanticData();

	public function getSeries() {
		return $this->getGroupedWorkDriver()->getSeries();
	}

	/**
	 * Returns title without subtitle
	 *
	 * @return string
	 */
	abstract function getShortTitle();

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

		return 'RecordDrivers/Index/staff.tpl';
	}

	/**
	 * Returns subtitle
	 *
	 * @return string
	 */
	abstract function getSubtitle();

	/**
	 * The Table of Contents extracted from the record.
	 * Returns null if no Table of Contents is available.
	 *
	 * @access  public
	 * @return  array              Array of elements in the table of contents
	 */
	public abstract function getTableOfContents();

	/**
	 * Get the UPC associated with the record (may be empty).
	 *
	 * @return  array
	 */
	public function getUPCs() {
		return [];
	}

	public function getUPC() {
		// If UPCs is in the index, it should automatically be an array... but if
		// it's not set at all, we should normalize the value to an empty array.
		$upcs = $this->getUPCs();
		if (count($upcs) > 0) {
			return '';
		} else {
			return reset($upcs);
		}
	}

	/**
	 * @param IlsVolumeInfo[] $volumeData
	 * @return int
	 */
	function getVolumeHolds(/** @noinspection PhpUnusedParameterInspection */ $volumeData) {
		return 0;
	}

	static $groupedWorks = [];

	/**
	 * Load the grouped work that this record is connected to.
	 */
	public function loadGroupedWork() {
		if ($this->groupedWork == null) {
			global $timer;
			require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
			require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
			$groupedWorkPrimaryIdentifier = new GroupedWorkPrimaryIdentifier();
			$groupedWorkPrimaryIdentifier->type = $this->getRecordType();
			$groupedWorkPrimaryIdentifier->identifier = $this->getUniqueID();
			if ($groupedWorkPrimaryIdentifier->find(true)) {
				$groupedWork = new GroupedWork();
				$groupedWork->id = $groupedWorkPrimaryIdentifier->grouped_work_id;
				if ($groupedWork->find(true)) {
					$this->groupedWork = clone $groupedWork;
				}
			}

			$timer->logTime("Loaded Grouped Work for record");
		}
	}

	public function getBookcoverInfo() {
		require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
		$bookcoverInfo = new BookCoverInfo();
		$bookcoverInfo->recordId = $this->getUniqueID();
		$bookcoverInfo->recordType = $this->getRecordType();
		if ($bookcoverInfo->find(true)) {
			return $bookcoverInfo;
		} else {
			return $this->getGroupedWorkDriver()->getBookcoverInfo();
		}
	}

	public function getCancelledIsbns() {
		return [];
	}

	public function hasMarcRecord() {
		return false;
	}

	public function __destruct() {
		$this->groupedWork = null;
		$this->groupedWorkDriver = null;
	}
}