<?php

require_once ROOT_DIR . '/RecordDrivers/IndexRecordDriver.php';
require_once ROOT_DIR . '/sys/File/MARC.php';

class GroupedWorkDriver extends IndexRecordDriver
{

	public $isValid = true;

	/** @var SearchObject_GroupedWorkSearcher */
	private static $recordLookupSearcher = null;
	public function __construct($indexFields)
	{
		if (is_string($indexFields)) {
			//We were just given the id of a record to load
			$id = $indexFields;
			$id = str_replace('groupedWork:', '', $id);
			//Just got a record id, let's load the full record from Solr
			// Setup Search Engine Connection
			if (GroupedWorkDriver::$recordLookupSearcher == null){
				GroupedWorkDriver::$recordLookupSearcher = SearchObjectFactory::initSearchObject();
				GroupedWorkDriver::$recordLookupSearcher->disableScoping();
			}

			if (function_exists('disableErrorHandler')) {
				disableErrorHandler();
			}

			// Retrieve the record from Solr
			if (!($record = GroupedWorkDriver::$recordLookupSearcher->getRecord($id))) {
				$this->isValid = false;
			} else {
				$this->fields = $record;
			}
			if (function_exists('enableErrorHandler')) {
				enableErrorHandler();
			}

		} else {
			//We were passed information from Solr
			if ($indexFields == null) {
				$this->isValid = false;
			} else {
				parent::__construct($indexFields);
			}
		}
	}

	public function assignBasicTitleDetails()
	{
		global $interface;
		$relatedRecords = $this->getRelatedRecords();

		$summPublisher = null;
		$summPubDate = null;
		$summPhysicalDesc = null;
		$summEdition = null;
		$summLanguage = null;
		$isFirst = true;
		foreach ($relatedRecords as $relatedRecord) {
			if ($isFirst) {
				$summPublisher = $relatedRecord->publisher;
				$summPubDate = $relatedRecord->publicationDate;
				$summPhysicalDesc = $relatedRecord->physical;
				$summEdition = $relatedRecord->edition;
				$summLanguage = $relatedRecord->language;
			} else {
				if ($summPublisher != $relatedRecord->publisher) {
					$summPublisher = null;
				}
				if ($summPubDate != $relatedRecord->publicationDate) {
					$summPubDate = null;
				}
				if ($summPhysicalDesc != $relatedRecord->physical) {
					$summPhysicalDesc = null;
				}
				if ($summEdition != $relatedRecord->edition) {
					$summEdition = null;
				}
				if ($summLanguage != $relatedRecord->language) {
					$summLanguage = null;
				}
			}
			$isFirst = false;
		}
		$interface->assign('summPublisher', $summPublisher);
		$interface->assign('summPubDate', $summPubDate);
		$interface->assign('summPhysicalDesc', $summPhysicalDesc);
		$interface->assign('summEdition', $summEdition);
		$interface->assign('summLanguage', $summLanguage);
		$interface->assign('summArInfo', $this->getAcceleratedReaderDisplayString());
		$interface->assign('summLexileInfo', $this->getLexileDisplayString());
		$interface->assign('summFountasPinnell', $this->getFountasPinnellLevel());
	}

	/**
	 * @param Grouping_Record $a
	 * @param Grouping_Record $b
	 * @return int
	 */
	static function compareAvailabilityForRecords($a, $b)
	{
		$availableLocallyA = $a->getStatusInformation()->isAvailableLocally();
		$availableLocallyB = $b->getStatusInformation()->isAvailableLocally();
		if (($availableLocallyA == $availableLocallyB)) {
			$availableA = $a->getStatusInformation()->isAvailable() && $a->isHoldable();
			$availableB = $b->getStatusInformation()->isAvailable() && $b->isHoldable();
			if (($availableA == $availableB)) {
				return 0;
			} elseif ($availableA) {
				return -1;
			} else {
				return 1;
			}
		} else if ($availableLocallyA) {
			return -1;
		} else {
			return 1;
		}
	}

	/**
	 * @param string $literaryForm
	 * @param Grouping_Record $a
	 * @param Grouping_Record $b
	 * @return int
	 */
	static function compareEditionsForRecords($literaryForm, $a, $b)
	{
		//We only want to compare editions if the work is non-fiction
		if ($a->format == 'eMagazine' && $b->format == 'eMagazine') {
			if ($a->getShelfLocation() == $b->getShelfLocation()){
				return 0;
			} else if ($a->getShelfLocation() > $b->getShelfLocation()) {
				return -1;
			} else {
				return 1;
			}
		}elseif ($literaryForm == 'Non Fiction') {
			$editionA = GroupedWorkDriver::normalizeEdition($a->edition);
			$editionB = GroupedWorkDriver::normalizeEdition($b->edition);
			if ($editionA == $editionB) {
				return 0;
			} else if ($editionA > $editionB) {
				return -1;
			} else {
				return 1;
			}
		}
		return 0;
	}

	/**
	 * @param Grouping_Record $a
	 * @param Grouping_Record $b
	 * @return int
	 */
	static function compareHoldability($a, $b)
	{
		if ($a->isHoldable() == $b->isHoldable()) {
			return 0;
		} else if ($a->isHoldable()) {
			return -1;
		} else {
			return 1;
		}
	}

	/**
	 * @param Grouping_Record $a
	 * @param Grouping_Record $b
	 * @return int
	 */
	static function compareLanguagesForRecords($a, $b)
	{
		$aHasEnglish = false;
		if (is_array($a->language)) {
			$languageA = strtolower(reset($a->language));
			foreach ($a->language as $language) {
				if (strcasecmp('english', $language) == 0) {
					$aHasEnglish = true;
					break;
				}
			}
		} else {
			$languageA = strtolower($a->language);
			if (strcasecmp('english', $languageA) == 0) {
				$aHasEnglish = true;
			}
		}
		$bHasEnglish = false;
		if (is_array($b->language)) {
			$languageB = strtolower(reset($b->language));
			foreach ($b->language as $language) {
				if (strcasecmp('english', $language) == 0) {
					$bHasEnglish = true;
					break;
				}
			}
		} else {
			$languageB = strtolower($b->language);
			if (strcasecmp('english', $languageB) == 0) {
				$bHasEnglish = true;
			}
		}
		if ($aHasEnglish && $bHasEnglish) {
			return 0;
		} else {
			if ($aHasEnglish) {
				return -1;
			} else if ($bHasEnglish) {
				return 1;
			} else {
				return -strcmp($languageA, $languageB);
			}
		}
	}

	/**
	 * @param Grouping_Record $a
	 * @param Grouping_Record $b
	 * @return int
	 */
	static function compareLocalAvailableItemsForRecords($a, $b)
	{
		$statusA = $a->getStatusInformation();
		$statusB = $b->getStatusInformation();
		if (($statusA->isAvailableHere() || $statusA->isAvailableOnline()) && ($statusB->isAvailableHere() || $statusB->isAvailableOnline())) {
			if (($statusA->isAvailableLocally() || $statusA->isAvailableOnline()) && ($statusB->isAvailableLocally() || $statusB->isAvailableOnline())) {
				return 0;
			} elseif ($statusA->isAvailableLocally() || $statusA->isAvailableOnline()) {
				return -1;
			} elseif ($statusB->isAvailableLocally() || $statusB->isAvailableOnline()) {
				return 1;
			} else {
				return 0;
			}
		} elseif ($statusA->isAvailableHere() || $statusA->isAvailableOnline()) {
			return -1;
		} elseif ($statusB->isAvailableHere() || $statusB->isAvailableOnline()) {
			return 1;
		} else {
			return 0;
		}
	}

	/**
	 * @param Grouping_Record $a
	 * @param Grouping_Record $b
	 * @return int
	 */
	static function compareLocalItemsForRecords($a, $b)
	{
		if ($a->hasLocalItem() && $b->hasLocalItem()) {
			return 0;
		} elseif ($a->hasLocalItem()) {
			return -1;
		} elseif ($b->hasLocalItem()) {
			return 1;
		} else {
			return 0;
		}
	}

	/**
	 * @param Grouping_Record $a
	 * @param Grouping_Record $b
	 * @return int
	 */
	function compareRelatedRecords($a, $b)
	{
		//Get literary form to determine if we should compare editions
		$literaryForm = '';
		if (isset($this->fields['literary_form'])) {
			if (is_array($this->fields['literary_form'])) {
				$literaryForm = reset($this->fields['literary_form']);
			} else {
				$literaryForm = $this->fields['literary_form'];
			}
		}
		//First sort by format
		$format1 = $a->format;
		$format2 = $b->format;
		$formatComparison = strcasecmp($format1, $format2);
		//Make sure that book is the very first format always
		if ($formatComparison != 0) {
			if ($format1 == 'Book') {
				return -1;
			} elseif ($format2 == 'Book') {
				return 1;
			}
		}
		if ($formatComparison == 0) {
			//1) Put anything that is holdable first
			$holdabilityComparison = GroupedWorkDriver::compareHoldability($a, $b);
			if ($holdabilityComparison == 0) {
				//2) Compare by language to put english titles before spanish by default
				$languageComparison = GroupedWorkDriver::compareLanguagesForRecords($a, $b);
				if ($languageComparison == 0) {
					//3) Compare editions for non-fiction if available
					$editionComparisonResult = GroupedWorkDriver::compareEditionsForRecords($literaryForm, $a, $b);
					if ($editionComparisonResult == 0) {
						//4) Put anything with locally available items first
						$localAvailableItemComparisonResult = GroupedWorkDriver::compareLocalAvailableItemsForRecords($a, $b);
						if ($localAvailableItemComparisonResult == 0) {
							//5) Anything that is available elsewhere goes higher
							$availabilityComparisonResults = GroupedWorkDriver::compareAvailabilityForRecords($a, $b);
							if ($availabilityComparisonResults == 0) {
								//6) Put anything with a local copy higher
								$localItemComparisonResult = GroupedWorkDriver::compareLocalItemsForRecords($a, $b);
								if ($localItemComparisonResult == 0) {
									//7) All else being equal, sort by hold ratio
									if ($a->getHoldRatio() == $b->getHoldRatio()) {
										//8) Hold Ratio is the same, last thing to check is the number of copies
										if ($a->getCopies() == $b->getCopies()) {
											return 0;
										} elseif ($a->getCopies() > $b->getCopies()) {
											return -1;
										} else {
											return 1;
										}
									} elseif ($a->getHoldRatio() > $b->getHoldRatio()) {
										return -1;
									} else {
										return 1;
									}
								} else {
									return $localItemComparisonResult;
								}
							} else {
								return $availabilityComparisonResults;
							}
						} else {
							return $localAvailableItemComparisonResult;
						}
					} else {
						return $editionComparisonResult;
					}
				} else {
					return $languageComparison;
				}
			} else {
				return $holdabilityComparison;
			}
		} else {
			return $formatComparison;
		}
	}

	/**
	 * @param Grouping_Record $a
	 * @param Grouping_Record $b
	 * @return int
	 */
	function compareRelatedManifestations($a, $b)
	{
		//First sort by format
		$format1 = $a->format;
		$format2 = $b->format;
		$formatComparison = strcasecmp($format1, $format2);
		//Make sure that book is the very first format always
		if ($formatComparison != 0) {
			if ($format1 == 'Book') {
				return -1;
			} elseif ($format2 == 'Book') {
				return 1;
			}
		}
		return $formatComparison;
	}

	public function getAcceleratedReaderData()
	{
		$hasArData = false;
		$arData = array();
		if (isset($this->fields['accelerated_reader_point_value'])) {
			$arData['pointValue'] = $this->fields['accelerated_reader_point_value'];
			$hasArData = true;
		}
		if (isset($this->fields['accelerated_reader_reading_level'])) {
			$arData['readingLevel'] = $this->fields['accelerated_reader_reading_level'];
			$hasArData = true;
		}
		if (isset($this->fields['accelerated_reader_interest_level'])) {
			$arData['interestLevel'] = $this->fields['accelerated_reader_interest_level'];
			$hasArData = true;
		}

		if ($hasArData) {
			if ($arData['pointValue'] == 0 && $arData['readingLevel'] == 0) {
				return null;
			}
			return $arData;
		} else {
			return null;
		}
	}

	public function getAcceleratedReaderDisplayString()
	{
		$acceleratedReaderInfo = $this->getAcceleratedReaderData();
		if ($acceleratedReaderInfo != null) {
			$arDetails = '';
			if (isset($acceleratedReaderInfo['interestLevel'])) {
				$arDetails .= 'IL: <strong>' . $acceleratedReaderInfo['interestLevel'] . '</strong>';
			}
			if (isset($acceleratedReaderInfo['readingLevel'])) {
				if (strlen($arDetails) > 0) {
					$arDetails .= ' - ';
				}
				$arDetails .= 'BL: <strong>' . $acceleratedReaderInfo['readingLevel'] . '</strong>';
			}
			if (isset($acceleratedReaderInfo['pointValue'])) {
				if (strlen($arDetails) > 0) {
					$arDetails .= ' - ';
				}
				$arDetails .= 'AR Pts: <strong>' . $acceleratedReaderInfo['pointValue'] . '</strong>';
			}
			return $arDetails;
		}
		return null;
	}

	private $archiveLink = 'unset';

	/**
	 * Get the authors of the work.
	 *
	 * @access  protected
	 * @return  string
	 */
	public function getAuthors()
	{
		return isset($this->fields['author']) ? $this->fields['author'] : null;
	}

	function getBookcoverUrl($size = 'small', $absolutePath = false)
	{
		global $configArray;

		if ($absolutePath) {
			$bookCoverUrl = $configArray['Site']['url'];
		} else {
			$bookCoverUrl = '';
		}
		$bookCoverUrl .= "/bookcover.php?id={$this->getUniqueID()}&size={$size}&type=grouped_work";

		if (isset($this->fields['format_category'])) {
			if (is_array($this->fields['format_category'])) {
				$bookCoverUrl .= "&category=" . reset($this->fields['format_category']);
			} else {
				$bookCoverUrl .= "&category=" . $this->fields['format_category'];
			}
		}

		return $bookCoverUrl;
	}

	public function getBrowseResult()
	{
		global $interface;
		$id = $this->getUniqueID();
		$interface->assign('summId', $id);

		$url = $this->getMoreInfoLinkUrl();

		$interface->assign('summUrl', $url);
		$shortTitle = $this->getShortTitle();
		if (empty($shortTitle)){
			$interface->assign('summTitle', $this->getTitle());
			$interface->assign('summSubTitle', '');
		}else {
			$interface->assign('summTitle', $this->getShortTitle());
			$interface->assign('summSubTitle', $this->getSubtitle());
		}
		$interface->assign('summAuthor', $this->getPrimaryAuthor());

		//Get Rating
		$interface->assign('ratingData', $this->getRatingData());
		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));
		// Rating Settings
		global $library;
		global $location;
		if ($location) { // Try Location Setting
			$browseCategoryRatingsMode = $location->getBrowseCategoryGroup()->browseCategoryRatingsMode;
		}else{
			$browseCategoryRatingsMode = $library->getBrowseCategoryGroup()->browseCategoryRatingsMode;
		}
		$interface->assign('browseCategoryRatingsMode', $browseCategoryRatingsMode);

		return 'RecordDrivers/GroupedWork/browse_result.tpl';
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
	public function getCitation($format)
	{
		require_once ROOT_DIR . '/sys/CitationBuilder.php';

		// Build author list:
		$authors = array();
		$primary = $this->getPrimaryAuthor();
		if (!empty($primary)) {
			$authors[] = $primary;
		}

		// Collect all details for citation builder:
		$publishers = $this->getPublishers();
		$pubDates = $this->getPublicationDates();
		//$pubPlaces = $this->getPlacesOfPublication();
		$details = array(
			'authors' => $authors,
			'title' => $this->getShortTitle(),
			'subtitle' => $this->getSubtitle(),
			//'pubPlace' => count($pubPlaces) > 0 ? $pubPlaces[0] : null,
			'pubName' => count($publishers) > 0 ? $publishers[0] : null,
			'pubDate' => count($pubDates) > 0 ? $pubDates[0] : null,
			'edition' => $this->getEditions(),
			'format' => $this->getFormats()
		);

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
	public function getCitationFormats()
	{
		return array('AMA', 'APA', 'ChicagoHumanities', 'ChicagoAuthDate', 'MLA');
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

		//Check to see if we already have NovelistData loaded with a primary ISBN
		require_once ROOT_DIR . '/sys/Enrichment/NovelistData.php';
		$novelistData = new NovelistData();
		$novelistData->groupedRecordPermanentId = $this->getPermanentId();
		if (!isset($_REQUEST['reload']) && $this->getPermanentId() != null && $this->getPermanentId() != '' && $novelistData->find(true) && $novelistData->primaryISBN != null) {
			return $novelistData->primaryISBN;
		} else {
			// Get all the ISBNs and initialize the return value:
			$isbns = $this->getISBNs();
			$isbn10 = false;

			// Loop through the ISBNs:
			foreach ($isbns as $isbn) {
				// If we find an ISBN-13, return it immediately; otherwise, if we find
				// an ISBN-10, save it if it is the first one encountered.
				$isbnObj = new ISBN($isbn);
				if ($isbnObj->isValid()) {
					if ($isbn13 = $isbnObj->get13()) {
						return $isbn13;
					}
					if (!$isbn10) {
						$isbn10 = $isbnObj->get10();
					}
				}
			}
			return $isbn10;
		}
	}

	public function getCleanUPC()
	{
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

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display a summary of the item suitable for use in
	 * search results.
	 *
	 * @access  public
	 * @param string $view The current view.
	 *
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getCombinedResult($view = 'list')
	{
		if ($view == 'covers') { // Displaying Results as bookcover tiles
			return $this->getBrowseResult();
		}

		// Displaying results as the default list
		global $configArray;
		global $interface;
		global $timer;
		global $memoryWatcher;

		$interface->assign('displayingSearchResults', true);

		$id = $this->getUniqueID();
		$timer->logTime("Starting to load search result for grouped work $id");
		$interface->assign('summId', $id);
		if (substr($id, 0, 1) == '.') {
			$interface->assign('summShortId', substr($id, 1));
		} else {
			$interface->assign('summShortId', $id);
		}
		$relatedManifestations = $this->getRelatedManifestations();
		$interface->assign('relatedManifestations', $relatedManifestations);
		$timer->logTime("Loaded related manifestations");
		$memoryWatcher->logMemory("Loaded related manifestations for {$this->getUniqueID()}");

		//Build the link URL.
		//If there is only one record for the work we will link straight to that.
		$relatedRecords = $this->getRelatedRecords();
		$timer->logTime("Loaded related records");
		$memoryWatcher->logMemory("Loaded related records");
		if (count($relatedRecords) == 1) {
			$firstRecord = reset($relatedRecords);
			$linkUrl = $firstRecord->getUrl();
			$linkUrl .= '?searchId=' . $interface->get_template_vars('searchId') . '&amp;recordIndex=' . $interface->get_template_vars('recordIndex') . '&amp;page=' . $interface->get_template_vars('page');
		} else {
			$linkUrl = '/GroupedWork/' . $id . '/Home?searchId=' . $interface->get_template_vars('searchId') . '&amp;recordIndex=' . $interface->get_template_vars('recordIndex') . '&amp;page=' . $interface->get_template_vars('page');
			$linkUrl .= '&amp;searchSource=' . $interface->get_template_vars('searchSource');
		}

		$interface->assign('summUrl', $linkUrl);
		$shortTitle = $this->getShortTitle();
		if (empty($shortTitle)){
			$interface->assign('summTitle', $this->getTitle());
			$interface->assign('summSubTitle', '');
		}else {
			$interface->assign('summTitle', $this->getShortTitle());
			$interface->assign('summSubTitle', $this->getSubtitle());
		}
		$interface->assign('summAuthor', rtrim($this->getPrimaryAuthor(true), ','));
		$isbn = $this->getCleanISBN();
		$interface->assign('summISBN', $isbn);
		$interface->assign('summFormats', $this->getFormats());
		$interface->assign('numRelatedRecords', count($relatedRecords));
		$acceleratedReaderInfo = $this->getAcceleratedReaderDisplayString();
		$interface->assign('summArInfo', $acceleratedReaderInfo);
		$lexileInfo = $this->getLexileDisplayString();
		$interface->assign('summLexileInfo', $lexileInfo);
		$interface->assign('summFountasPinnell', $this->getFountasPinnellLevel());
		$timer->logTime("Finished assignment of main data");
		$memoryWatcher->logMemory("Finished assignment of main data");

		$summPublisher = null;
		$summPubDate = null;
		$summPhysicalDesc = null;
		$summEdition = null;
		$summLanguage = null;
		$isFirst = true;
		global $library;
		$alwaysShowMainDetails = $library->getGroupedWorkDisplaySettings()->alwaysShowSearchResultsMainDetails;
		foreach ($relatedRecords as $relatedRecord) {
			if ($isFirst) {
				$summPublisher = $relatedRecord->publisher;
				$summPubDate = $relatedRecord->publicationDate;
				$summPhysicalDesc = $relatedRecord->physical;
				$summEdition = $relatedRecord->edition;
				$summLanguage = $relatedRecord->language;
			} else {
				if ($summPublisher != $relatedRecord->publisher) {
					$summPublisher = $alwaysShowMainDetails ? translate(['text' => 'Varies, see individual formats and editions', 'isPublicFacing'=>true]) : null;
				}
				if ($summPubDate != $relatedRecord->publicationDate) {
					$summPubDate = $alwaysShowMainDetails ? translate(['text' => 'Varies, see individual formats and editions', 'isPublicFacing'=>true]) : null;
				}
				if ($summPhysicalDesc != $relatedRecord->physical) {
					$summPhysicalDesc = $alwaysShowMainDetails ? translate(['text' => 'Varies, see individual formats and editions', 'isPublicFacing'=>true]) : null;
				}
				if ($summEdition != $relatedRecord->edition) {
					$summEdition = $alwaysShowMainDetails ? translate(['text' => 'Varies, see individual formats and editions', 'isPublicFacing'=>true]) : null;
				}
				if ($summLanguage != $relatedRecord->language) {
					$summLanguage = $alwaysShowMainDetails ? translate(['text' => 'Varies, see individual formats and editions', 'isPublicFacing'=>true]) : null;
				}
			}
			$isFirst = false;
		}
		$interface->assign('summPublisher', rtrim($summPublisher, ','));
		$interface->assign('summPubDate', $summPubDate);
		$interface->assign('summPhysicalDesc', $summPhysicalDesc);
		$interface->assign('summEdition', $summEdition);
		$interface->assign('summLanguage', $summLanguage);
		$timer->logTime("Finished assignment of data based on related records");

		if (IPAddress::showDebuggingInformation()) {
			$interface->assign('summScore', $this->getScore());
			$interface->assign('summExplain', $this->getExplain());
		}
		$timer->logTime("Finished assignment of data based on solr debug info");

		//Get Rating
		$interface->assign('summRating', $this->getRatingData());
		$timer->logTime("Finished loading rating data");

		//Description
		$interface->assign('summDescription', $this->getDescriptionFast(true));
		$timer->logTime('Finished Loading Description');
		$memoryWatcher->logMemory("Finished Loading Description");
		if ($this->hasCachedSeries()) {
			$interface->assign('ajaxSeries', false);
			$interface->assign('summSeries', $this->getSeries(false));
		} else {
			$interface->assign('ajaxSeries', true);
			$interface->assign('summSeries', null);
		}
		$timer->logTime('Finished Loading Series');
		$memoryWatcher->logMemory("Finished Loading Series");

		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));

		$interface->assign('recordDriver', $this);

		return 'RecordDrivers/GroupedWork/combinedResult.tpl';
	}

	public function getContributors()
	{
		return $this->fields['author2-role']; //Include the role when displaying contributor
	}

	function getDescription()
	{
		$description = null;
		$cleanIsbn = $this->getCleanISBN();
		global $library;
		if ($description == null) {
			$description = $this->getDescriptionFast();
		}
		if ($library->getGroupedWorkDisplaySettings()->preferSyndeticsSummary == 1 || $description == null || strlen($description) == 0) {
			if ($cleanIsbn != null && strlen($cleanIsbn) > 0) {
				require_once ROOT_DIR . '/Drivers/marmot_inc/GoDeeperData.php';
				$summaryInfo = GoDeeperData::getSummary($this->getPermanentId(), $cleanIsbn, $this->getCleanUPC());
				if (isset($summaryInfo['summary'])) {
					$description = $summaryInfo['summary'];
				}
			}
		}


		if ($description == null || strlen($description) == 0) {
			$description = translate(['text' => 'Description Not Provided', 'isPublicFacing'=>true]);
		}
		return $description;
	}

	private $fastDescription = null;

	function getDescriptionFast($useHighlighting = false)
	{
		// Don't check for highlighted values if highlighting is disabled:
		if ($this->highlight && $useHighlighting) {
			if (isset($this->fields['_highlighting']['display_description'][0])) {
				return $this->fields['_highlighting']['display_description'][0];
			}
		}
		if ($this->fastDescription != null) {
			return $this->fastDescription;
		}
		if (!empty($this->fields['display_description'])) {
			$this->fastDescription = $this->fields['display_description'];
		} else {
			$this->fastDescription = "";

		}
		return $this->fastDescription;
	}

	private $detailedContributors = null;

	public function getDetailedContributors()
	{
		if ($this->detailedContributors == null) {
			$this->detailedContributors = array();
			if (isset($this->fields['author2-role'])) {
				$contributorsInIndex = $this->fields['author2-role'];
				if (is_string($contributorsInIndex)) {
					$contributorsInIndex[] = $contributorsInIndex;
				}
				foreach ($contributorsInIndex as $contributor) {
					if (strpos($contributor, '|')) {
						$contributorInfo = explode('|', $contributor);
						$curContributor = array(
							'name' => $contributorInfo[0],
							'roles' =>explode(',', $contributorInfo[1]),
						);
						ksort($curContributor['roles']);
					} else {
						$curContributor = array(
							'name' => $contributor,
							'roles' => []
						);
					}
					if (array_key_exists($curContributor['name'], $this->detailedContributors)){
						$this->detailedContributors[$curContributor['name']]['roles'] = array_keys(array_merge(array_flip($this->detailedContributors[$curContributor['name']]['roles']), array_flip($curContributor['roles'])));
						ksort($this->detailedContributors[$curContributor['name']]['roles']);
					}else{
						$this->detailedContributors[$curContributor['name']] = $curContributor;
					}
				}
				ksort($this->detailedContributors);
			}
		}
		return $this->detailedContributors;
	}

	/**
	 * Get the edition of the current record.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getEditions()
	{
		if (isset($this->fields['edition'])) {
			if (is_array(isset($this->fields['edition']))) {
				return $this->fields['edition'];
			} else {
				return [isset($this->fields['edition'])];
			}
		} else {
			return [];
		}
	}

	/**
	 * Get the text to represent this record in the body of an email.
	 *
	 * @access  public
	 * @return  string              Text for inclusion in email.
	 */
	public function getEmail()
	{
		return "  " . $this->getTitle() . "\n";
	}

	public function getExploreMoreInfo()
	{
		return [];
	}

	public function getFountasPinnellLevel()
	{
		return isset($this->fields['fountas_pinnell']) ? $this->fields['fountas_pinnell'] : null;
	}

	public function getFormatsArray()
	{
		global $solrScope;
		if (isset($this->fields['format_' . $solrScope])) {
			$formats = $this->fields['format_' . $solrScope];
			if (is_array($formats)) {
				return $formats;
			} else {
				return array($formats);
			}
		} else {
			return array();
		}
	}

	public function getFormatCategory()
	{
		global $solrScope;
		if (isset($this->fields['format_category_' . $solrScope])) {
			if (is_array($this->fields['format_category_' . $solrScope])) {
				return reset($this->fields['format_category_' . $solrScope]);
			} else {
				return $this->fields['format_category_' . $solrScope];
			}
		}
		return "";
	}

	protected $_indexedSeries = false;
	public function getIndexedSeries()
	{
		if ($this->_indexedSeries === false) {
			global $timer;
			$this->_indexedSeries = null;
			if (isset($this->fields['series_with_volume'])) {
				$this->_indexedSeries = [];
				$rawSeries = $this->fields['series_with_volume'];
				if (is_string($rawSeries)) {
					$rawSeries[] = $rawSeries;
				}
				foreach ($rawSeries as $seriesInfo) {
					if (strpos($seriesInfo, '|') > 0) {
						$seriesInfoSplit = explode('|', $seriesInfo);
						$this->_indexedSeries[] = array(
							'seriesTitle' => $seriesInfoSplit[0],
							'volume' => $seriesInfoSplit[1]
						);
					} else {
						$this->_indexedSeries[] = array(
							'seriesTitle' => $seriesInfo
						);
					}
				}
			}
			$timer->logTime("Loaded indexed series information");
		}
		return $this->_indexedSeries;
	}

	/**
	 * Get an array of all ISBNs associated with the record (may be empty).
	 * The primary ISBN is the first entry
	 *
	 * @access  protected
	 * @return  array
	 */
	public function getISBNs()
	{
		// If ISBN is in the index, it should automatically be an array... but if
		// it's not set at all, we should normalize the value to an empty array.
		$isbns = array();
		$primaryIsbn = $this->getPrimaryIsbn();
		if ($primaryIsbn != null) {
			$isbns[] = $primaryIsbn;
		}
		if (isset($this->fields['isbn'])) {
			if (is_array($this->fields['isbn'])) {
				$additionalIsbns = $this->fields['isbn'];
			} else {
				$additionalIsbns = array($this->fields['isbn']);
			}
		} else {
			$additionalIsbns = array();
		}
		//This makes sure that the primary ISBN is first
		$additionalIsbns = array_remove_by_value($additionalIsbns, $primaryIsbn);
		$isbns = array_merge($isbns, $additionalIsbns);
		return $isbns;
	}

	/**
	 * Get an array of all ISBNs associated with the record (may be empty).
	 *
	 * @access  protected
	 * @return  array
	 */
	public function getISSNs()
	{
		// If ISBN is in the index, it should automatically be an array... but if
		// it's not set at all, we should normalize the value to an empty array.
		if (isset($this->fields['issn'])) {
			if (is_array($this->fields['issn'])) {
				return $this->fields['issn'];
			} else {
				return array($this->fields['issn']);
			}
		} else {
			return array();
		}
	}

	public function getLexileCode()
	{
		return isset($this->fields['lexile_code']) ? $this->fields['lexile_code'] : null;
	}

	public function getLexileDisplayString()
	{
		$lexileScore = $this->getLexileScore();
		if ($lexileScore != null) {
			$lexileInfo = '';
			$lexileCode = $this->getLexileCode();
			if ($lexileCode != null) {
				$lexileInfo .= $lexileCode . ' ';
			}
			$lexileInfo .= $lexileScore . 'L';
			return $lexileInfo;
		}
		return null;
	}

	public function getLexileScore()
	{
		if (isset($this->fields['lexile_score'])) {
			if ($this->fields['lexile_score'] > 0) {
				return $this->fields['lexile_score'];
			}
		}
		return null;
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display a summary of the item suitable for use in
	 * user's favorites list.
	 *
	 * @access  public
	 * @param int $listId ID of list containing desired tags/notes (or
	 *                              null to show tags/notes from all user's lists).
	 * @param bool $allowEdit Should we display edit controls?
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getListEntry($listId = null, $allowEdit = true)
	{
		global $interface;
		global $timer;

		$id = $this->getUniqueID();
		$timer->logTime("Starting to load search result for grouped work $id");
		$interface->assign('summId', $id);
		if (substr($id, 0, 1) == '.') {
			$interface->assign('summShortId', substr($id, 1));
		} else {
			$interface->assign('summShortId', $id);
		}

		$relatedManifestations = $this->getRelatedManifestations();
		$interface->assign('relatedManifestations', $relatedManifestations);

		//Build the link URL.
		//If there is only one record for the work we will link straight to that.
		$linkUrl = $this->getMoreInfoLinkUrl();
		$linkUrl .= '?searchId=' . $interface->get_template_vars('searchId') . '&amp;recordIndex=' . $interface->get_template_vars('recordIndex') . '&amp;page=' . $interface->get_template_vars('page');

		$interface->assign('summUrl', $linkUrl);
		$shortTitle = $this->getShortTitle();
		if (empty($shortTitle)){
			$interface->assign('summTitle', $this->getTitle());
			$interface->assign('summSubTitle', '');
		}else {
			$interface->assign('summTitle', $this->getShortTitle());
			$interface->assign('summSubTitle', $this->getSubtitle());
		}
		$interface->assign('summAuthor', $this->getPrimaryAuthor());
		$isbn = $this->getCleanISBN();
		$interface->assign('summISBN', $isbn);
		$interface->assign('summFormats', $this->getFormats());

		$this->assignBasicTitleDetails();


		$interface->assign('numRelatedRecords', $this->getNumRelatedRecords());

		if (IPAddress::showDebuggingInformation()) {
			$interface->assign('summScore', $this->getScore());
			$interface->assign('summExplain', $this->getExplain());
		}

		//Get Rating
		$interface->assign('summRating', $this->getRatingData());

		//Description
		$interface->assign('summDescription', $this->getDescriptionFast());
		$timer->logTime('Finished Loading Description');
		if ($this->hasCachedSeries()) {
			$interface->assign('ajaxSeries', false);
			$interface->assign('summSeries', $this->getSeries(false));
		} else {
			$interface->assign('ajaxSeries', true);
			$interface->assign('summSeries', '');
		}

		$timer->logTime('Finished Loading Series');

		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));

		$interface->assign('recordDriver', $this);

		return 'RecordDrivers/GroupedWork/listEntry.tpl';
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display a summary of the item suitable for use in
	 * user's favorites list.
	 *
	 * @access  public
	 * @param int $listId ID of list containing desired tags/notes (or
	 *                              null to show tags/notes from all user's lists).
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getCourseReserveEntry($listId = null)
	{
		global $configArray;
		global $interface;
		global $timer;

		$id = $this->getUniqueID();
		$timer->logTime("Starting to load search result for grouped work $id");
		$interface->assign('summId', $id);
		if (substr($id, 0, 1) == '.') {
			$interface->assign('summShortId', substr($id, 1));
		} else {
			$interface->assign('summShortId', $id);
		}

		$relatedManifestations = $this->getRelatedManifestations();
		$interface->assign('relatedManifestations', $relatedManifestations);

		//Build the link URL.
		//If there is only one record for the work we will link straight to that.
		$linkUrl = $this->getMoreInfoLinkUrl();
		$linkUrl .= '?searchId=' . $interface->get_template_vars('searchId') . '&amp;recordIndex=' . $interface->get_template_vars('recordIndex') . '&amp;page=' . $interface->get_template_vars('page');

		$interface->assign('summUrl', $linkUrl);
		$shortTitle = $this->getShortTitle();
		if (empty($shortTitle)){
			$interface->assign('summTitle', $this->getTitle());
			$interface->assign('summSubTitle', '');
		}else {
			$interface->assign('summTitle', $this->getShortTitle());
			$interface->assign('summSubTitle', $this->getSubtitle());
		}
		$interface->assign('summAuthor', $this->getPrimaryAuthor());
		$isbn = $this->getCleanISBN();
		$interface->assign('summISBN', $isbn);
		$interface->assign('summFormats', $this->getFormats());

		$this->assignBasicTitleDetails();


		$interface->assign('numRelatedRecords', $this->getNumRelatedRecords());

		if (IPAddress::showDebuggingInformation()) {
			$interface->assign('summScore', $this->getScore());
			$interface->assign('summExplain', $this->getExplain());
		}

		//Get Rating
		$interface->assign('summRating', $this->getRatingData());

		//Description
		$interface->assign('summDescription', $this->getDescriptionFast());
		$timer->logTime('Finished Loading Description');
		if ($this->hasCachedSeries()) {
			$interface->assign('ajaxSeries', false);
			$interface->assign('summSeries', $this->getSeries(false));
		} else {
			$interface->assign('ajaxSeries', true);
			$interface->assign('summSeries', '');
		}

		$timer->logTime('Finished Loading Series');

		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));

		$interface->assign('recordDriver', $this);

		return 'RecordDrivers/GroupedWork/courseReserveEntry.tpl';
	}

	public function getSpotlightResult(CollectionSpotlight $collectionSpotlight, string $index){
		global $interface;
		$interface->assign('showRatings', $collectionSpotlight->showRatings);

		$interface->assign('key', $index);

		if ($collectionSpotlight->coverSize == 'small'){
			$imageUrl = $this->getBookcoverUrl('small');
		}else{
			$imageUrl = $this->getBookcoverUrl('medium');
		}

		$interface->assign('title', $this->getTitle());
		$interface->assign('author', $this->getPrimaryAuthor());
		$interface->assign('description', $this->getDescriptionFast());
		$interface->assign('shortId', $this->getId());
		$interface->assign('id', $this->getId());
		$interface->assign('titleURL', $this->getRecordUrl());
		$interface->assign('imageUrl', $imageUrl);

		if ($collectionSpotlight->showRatings){
			$interface->assign('ratingData', $this->getRatingData());
			$interface->assign('showNotInterested', false);
		}

		$result = [
			'title' => $this->getTitle(),
			'author' => $this->getPrimaryAuthor(),
		];
		if ($collectionSpotlight->style == 'text-list'){
			$result['formattedTextOnlyTitle'] = $interface->fetch('CollectionSpotlight/formattedTextOnlyTitle.tpl');
		}elseif ($collectionSpotlight->style == 'horizontal-carousel'){
			$result['formattedTitle'] = $interface->fetch('CollectionSpotlight/formattedHorizontalCarouselTitle.tpl');
		}else{
			$result['formattedTitle']= $interface->fetch('CollectionSpotlight/formattedTitle.tpl');
		}

		return $result;
	}

	public function getSuggestionSpotlightResult(string $index){
		global $interface;
		$interface->assign('showRatings', false);

		$interface->assign('key', $index);

		$imageUrl = $this->getBookcoverUrl('medium');

		$interface->assign('title', $this->getTitle());
		$interface->assign('author', $this->getPrimaryAuthor());
		$interface->assign('description', $this->getDescriptionFast());
		$interface->assign('shortId', $this->getId());
		$interface->assign('id', $this->getId());
		$interface->assign('titleURL', $this->getRecordUrl());
		$interface->assign('imageUrl', $imageUrl);

		$result = [
			'title' => $this->getTitle(),
			'author' => $this->getPrimaryAuthor(),
		];
		$result['formattedTitle'] = $interface->fetch('CollectionSpotlight/formattedHorizontalCarouselTitle.tpl');

		return $result;
	}

	public function getSummaryInformation()
	{
		return array(
			'id' => $this->getPermanentId(),
			'shortId' => $this->getPermanentId(),
			'recordtype' => 'grouped_work',
			'image' => $this->getBookcoverUrl('medium'),
			'small_image' => $this->getBookcoverUrl('small'),
			'title' => $this->getTitle(),
			'titleURL' => $this->getLinkUrl(true),
			'author' => $this->getPrimaryAuthor(),
			'description' => $this->getDescriptionFast(),
			'length' => '',
			'publisher' => '',
			'ratingData' => $this->getRatingData(),
		);
	}


	public function getModule() : string
	{
		return 'GroupedWork';
	}

	public function getMoreDetailsOptions()
	{
		global $interface;

		$isbn = $this->getCleanISBN();

		$tableOfContents = $this->getTableOfContents();
		$interface->assign('tableOfContents', $tableOfContents);

		//Load more details options
		$moreDetailsOptions = $this->getBaseMoreDetailsOptions($isbn);
		$moreDetailsOptions['moreDetails'] = array(
			'label' => 'More Details',
			'body' => $interface->fetch('GroupedWork/view-title-details.tpl'),
		);
		$this->loadSubjects();
		$moreDetailsOptions['subjects'] = array(
			'label' => 'Subjects',
			'body' => $interface->fetch('GroupedWork/view-subjects.tpl'),
		);
		if ($interface->getVariable('showStaffView')) {
			$moreDetailsOptions['staff'] = array(
				'label' => 'Staff View',
				'onShow' => "AspenDiscovery.GroupedWork.getStaffView('{$this->getPermanentId()}');",
				'body' => '<div id="staffViewPlaceHolder">Loading Staff View.</div>',
			);
		}

		return $this->filterAndSortMoreDetailsOptions($moreDetailsOptions);
	}

	protected $_moreInfoLink = null;
	function getMoreInfoLinkUrl()
	{
		if ($this->_moreInfoLink == null) {
			// if the grouped work consists of only 1 related item, return the record url, otherwise return the grouped-work url
			//Rather than loading all related records which can be slow, just get the count
			$numRelatedRecords = $this->getNumRelatedRecords();

			if ($numRelatedRecords == 1) {
				//Now that we know that we need more detailed information, load the related record.
				$relatedRecords = $this->getRelatedRecords(false);
				$onlyRecord = reset($relatedRecords);
				$this->_moreInfoLink = $onlyRecord->getUrl();
			} else {
				$this->_moreInfoLink = $this->getLinkUrl();
			}
		}
		return $this->_moreInfoLink;
	}

	public function getMpaaRating()
	{
		return isset($this->fields['mpaaRating']) ? $this->fields['mpaaRating'] : null;
	}

	private $numRelatedRecords = -1;

	private function getNumRelatedRecords()
	{
		if ($this->numRelatedRecords == -1) {
			if ($this->relatedRecords != null) {
				$this->numRelatedRecords = count($this->relatedRecords);
			} else {
				global $solrScope;

				$relatedRecordFieldName = 'related_record_ids';
				if ($solrScope) {
					if (isset($this->fields["related_record_ids_$solrScope"])) {
						$relatedRecordFieldName = "related_record_ids_$solrScope";
					}
				}
				if (isset($this->fields[$relatedRecordFieldName])) {
					$this->numRelatedRecords = count($this->fields[$relatedRecordFieldName]);
				} else {
					$this->numRelatedRecords = 0;
				}
			}
		}
		return $this->numRelatedRecords;
	}

	function getOGType()
	{
		$format = strtolower($this->getFormatCategory());
		switch ($format) {
			case 'books':
			case 'ebook':
			case 'audio books':
				return 'book';

			case 'music':
				return 'music.album';

			case 'movies':
				return 'video.movie';

			default:
				return 'website';
		}
	}

	public function getPermanentId()
	{
		return $this->fields['id'];
	}

	public function getPrimaryAuthor($useHighlighting = false)
	{
		// Don't check for highlighted values if highlighting is disabled:
		// MDN: 1/26 - author actually contains more information than author display.
		//  It also includes dates lived so we will use that instead if possible
		if ($this->highlight && $useHighlighting) {
			if (isset($this->fields['_highlighting']['author'][0])) {
				return $this->fields['_highlighting']['author'][0];
			} else if (isset($this->fields['_highlighting']['author_display'][0])) {
				return $this->fields['_highlighting']['author_display'][0];
			}
		}
		if (isset($this->fields['author_display'])) {
			return $this->fields['author_display'];
		} else {
			return isset($this->fields['author']) ? $this->fields['author'] : '';
		}
	}

	public function getPrimaryIsbn()
	{
		if (isset($this->fields['primary_isbn'])) {
			return $this->fields['primary_isbn'];
		} else {
			return null;
		}
	}

	function getPublicationDates()
	{
		return isset($this->fields['publishDate']) ? $this->fields['publishDate'] : array();
	}

	function getEarliestPublicationDate()
	{
		return isset($this->fields['publishDateSort']) ? $this->fields['publishDateSort'] : '';
	}

	/**
	 * Get the publishers of the record.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getPublishers()
	{
		return isset($this->fields['publisher']) ? $this->fields['publisher'] : array();
	}

	public function getRatingData()
	{
		require_once ROOT_DIR . '/services/API/WorkAPI.php';
		$workAPI = new WorkAPI();
		return $workAPI->getRatingData($this->getPermanentId());
	}

	public function getRecordUrl()
	{
		$recordId = $this->getUniqueID();

		return '/GroupedWork/' . urlencode($recordId) . '/Home';
	}

	/** @var Grouping_Manifestation[] $_relatedManifestations */
	private $_relatedManifestations = null;
	/**
	 * The vast majority of record information is stored within the index.
	 * This routine parses the information from the index and restructures it for use within the user interface.
	 *
	 * @return Grouping_Manifestation[]|null
	 */
	public function getRelatedManifestations()
	{
		if ($this->_relatedManifestations == null) {
			global $timer;
			global $memoryWatcher;
			$timer->logTime("Starting to load related records in getRelatedManifestations");
			$relatedRecords = $this->getRelatedRecords();
			$timer->logTime("Finished loading related records in getRelatedManifestations");
			$memoryWatcher->logMemory("Finished loading related records");
			//Group the records based on format (if this wasn't done while loading which happens if loading from the database)
			if ($this->_relatedManifestations == null) {
				$this->_relatedManifestations = array();
				require_once ROOT_DIR . '/sys/Grouping/Manifestation.php';
				foreach ($relatedRecords as $curRecord) {
					if (!array_key_exists($curRecord->format, $this->_relatedManifestations)) {
						$this->_relatedManifestations[$curRecord->format] = new Grouping_Manifestation($curRecord);
					} else {
						$this->_relatedManifestations[$curRecord->format]->addRecord($curRecord);
					}
				}
				$timer->logTime("Finished initial processing of related records");
				$memoryWatcher->logMemory("Finished initial processing of related records");
			}

			//Check to see if we have applied a format or format category facet
			$selectedFormat = [];
			$selectedFormatCategory = [];
			$selectedAvailability = [];
			$selectedDetailedAvailability = null;
			$selectedLanguages = [];
			$selectedEcontentSources = [];
			if (isset($_REQUEST['filter'])) {
				foreach ($_REQUEST['filter'] as $filter) {
					if (preg_match('/^format_category\w*:"?(.+?)"?$/', $filter, $matches)) {
						$selectedFormatCategory[] = $matches[1];
					} elseif (preg_match('/^format\w*:"?(.+?)"?$/', $filter, $matches)) {
						$selectedFormat[] = $matches[1];
					} elseif (preg_match('/^availability_toggle\w*:"?(.+?)"?$/', $filter, $matches)) {
						if ($matches[1] != '"') {
							$selectedAvailability[] = $matches[1];
						}
					} elseif (preg_match('/^availability_by_format[\w_]*:"?(.+?)"?$/', $filter, $matches)) {
						$selectedAvailability[] = $matches[1];
					} elseif (preg_match('/^available_at[\w_]*:"?(.+?)"?$/', $filter, $matches)) {
						$selectedDetailedAvailability = $matches[1];
					} elseif (preg_match('/^econtent_source[\w_]*:"?(.+?)"?$/', $filter, $matches)) {
						$selectedEcontentSources[] = $matches[1];
					} elseif (preg_match('/^language:"?(.+?)"?$/', $filter, $matches)) {
						$selectedLanguages[] = $matches[1];
					}
				}
			}

			if (empty($selectedLanguages)) {
				if (UserAccount::isLoggedIn()) {
					$searchPreferenceLanguage = UserAccount::getActiveUserObj()->searchPreferenceLanguage;
				} elseif (isset($_COOKIE['searchPreferenceLanguage'])) {
					$searchPreferenceLanguage = $_COOKIE['searchPreferenceLanguage'];
				} else {
					$searchPreferenceLanguage = 0;
				}

				global $activeLanguage;
				if ($activeLanguage->code != 'en' && ($searchPreferenceLanguage == 2)) {
					$selectedLanguages[] = $activeLanguage->facetValue;
				}
			}

			//Check to see what we need to do for actions, and determine if the record should be hidden by default
			$searchLibrary = Library::getSearchLibrary();
			$searchLocation = Location::getSearchLocation();
			$isSuperScope = false;
			if ($searchLocation) {
				$isSuperScope = !$searchLocation->restrictSearchByLocation;
			} elseif ($searchLibrary) {
				$isSuperScope = !$searchLibrary->restrictSearchByLibrary;
			}

			$addOnlineMaterialsToAvailableNow = true;
			if ($searchLocation != null) {
				$addOnlineMaterialsToAvailableNow = $searchLocation->getGroupedWorkDisplaySettings()->includeOnlineMaterialsInAvailableToggle;
			} elseif ($searchLibrary != null) {
				$addOnlineMaterialsToAvailableNow = $searchLibrary->getGroupedWorkDisplaySettings()->includeOnlineMaterialsInAvailableToggle;
			}

			global $searchSource;

			/**
			 * @var  $key
			 * @var Grouping_Manifestation $manifestation
			 */
			foreach ($this->_relatedManifestations as $key => $manifestation) {
				$manifestation->setHideByDefault($selectedFormat, $selectedFormatCategory, $selectedAvailability, $selectedDetailedAvailability, $addOnlineMaterialsToAvailableNow, $selectedEcontentSources, $selectedLanguages, $searchSource, $isSuperScope);

				$this->_relatedManifestations[$key] = $manifestation;
			}

			uasort($this->_relatedManifestations, array($this, "compareRelatedManifestations"));
			$timer->logTime("Finished loading related manifestations");
			$memoryWatcher->logMemory("Finished loading related manifestations");
		}

		return $this->_relatedManifestations;
	}

	private $relatedRecords = null;
	private $relatedItemsByRecordId = null;

	/**
	 * @param bool $forCovers
	 * @return Grouping_Record[]
	 */
	public function getRelatedRecords($forCovers = false)
	{
		$this->loadRelatedRecords($forCovers);
		return $this->relatedRecords;
	}

	/**
	 * @param $recordIdentifier
	 * @return Grouping_Record
	 */
	public function getRelatedRecord($recordIdentifier)
	{
		$this->loadRelatedRecords();
		if (isset($this->relatedRecords[$recordIdentifier])) {
			return $this->relatedRecords[$recordIdentifier];
		} elseif (isset($this->relatedRecords[strtolower($recordIdentifier)])) {
			return $this->relatedRecords[strtolower($recordIdentifier)];
		} else {
			return null;
		}
	}

	public function getScrollerTitle($index, $scrollerName)
	{
		global $interface;
		$interface->assign('index', $index);
		$interface->assign('scrollerName', $scrollerName);
		$interface->assign('id', $this->getPermanentId());
		$interface->assign('title', $this->getTitle());
		$interface->assign('linkUrl', $this->getLinkUrl());
		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));

		$interface->assign('recordDriver', $this);

		return array(
			'id' => $this->getPermanentId(),
			'image' => $this->getBookcoverUrl('medium'),
			'title' => $this->getTitle(),
			'author' => $this->getPrimaryAuthor(),
			'formattedTitle' => $interface->fetch('RecordDrivers/GroupedWork/scroller-title.tpl')
		);
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display a summary of the item suitable for use in
	 * search results.
	 *
	 * @access  public
	 * @param string $view The current view.
	 *
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getSearchResult($view = 'list')
	{
		if ($view == 'covers') { // Displaying Results as bookcover tiles
			return $this->getBrowseResult();
		}

		// Displaying results as the default list
		global $interface;
		global $timer;
		global $memoryWatcher;

		$interface->assign('displayingSearchResults', true);

		$id = $this->getUniqueID();
		$timer->logTime("Starting to load search result for grouped work $id");
		$interface->assign('summId', $id);
		if (substr($id, 0, 1) == '.') {
			$interface->assign('summShortId', substr($id, 1));
		} else {
			$interface->assign('summShortId', $id);
		}
		$relatedManifestations = $this->getRelatedManifestations();
		$interface->assign('relatedManifestations', $relatedManifestations);
		$timer->logTime("Loaded related manifestations");
		$memoryWatcher->logMemory("Loaded related manifestations for {$this->getUniqueID()}");

		//Build the link URL.
		//If there is only one record for the work we will link straight to that.
		$relatedRecords = $this->getRelatedRecords();
		$timer->logTime("Loaded related records");
		$memoryWatcher->logMemory("Loaded related records");
		if (count($relatedRecords) == 1) {
			$firstRecord = reset($relatedRecords);
			$linkUrl = $firstRecord->getUrl();
			$linkUrl .= '?searchId=' . $interface->get_template_vars('searchId') . '&amp;recordIndex=' . $interface->get_template_vars('recordIndex') . '&amp;page=' . $interface->get_template_vars('page');
		} else {
			$linkUrl = '/GroupedWork/' . $id . '/Home?searchId=' . $interface->get_template_vars('searchId') . '&amp;recordIndex=' . $interface->get_template_vars('recordIndex') . '&amp;page=' . $interface->get_template_vars('page');
			$linkUrl .= '&amp;searchSource=' . $interface->get_template_vars('searchSource');
		}

		$interface->assign('summUrl', $linkUrl);
		$shortTitle = $this->getShortTitle();
		if (empty($shortTitle)){
			$interface->assign('summTitle', $this->getTitle());
			$interface->assign('summSubTitle', '');
		}else {
			$interface->assign('summTitle', $this->getShortTitle());
			$interface->assign('summSubTitle', $this->getSubtitle());
		}
		$interface->assign('summAuthor', rtrim($this->getPrimaryAuthor(true), ','));
		$isbn = $this->getCleanISBN();
		$interface->assign('summISBN', $isbn);
		$interface->assign('summFormats', $this->getFormats());
		$interface->assign('numRelatedRecords', count($relatedRecords));
		$acceleratedReaderInfo = $this->getAcceleratedReaderDisplayString();
		$interface->assign('summArInfo', $acceleratedReaderInfo);
		$lexileInfo = $this->getLexileDisplayString();
		$interface->assign('summLexileInfo', $lexileInfo);
		$interface->assign('summFountasPinnell', $this->getFountasPinnellLevel());
		$timer->logTime("Finished assignment of main data");
		$memoryWatcher->logMemory("Finished assignment of main data");

		//Check to see if there are lists the record is on
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		$appearsOnLists = UserList::getUserListsForRecord('GroupedWork', $this->getPermanentId());
		$interface->assign('appearsOnLists', $appearsOnLists);

		$this->loadReadingHistoryIndicator();

		$summPublisher = null;
		$summPubDate = null;
		$summPhysicalDesc = null;
		$summEdition = null;
		$summLanguage = null;
		$isFirst = true;
		global $library;
		$alwaysShowMainDetails = $library ? $library->alwaysShowSearchResultsMainDetails : false;
		foreach ($relatedRecords as $relatedRecord) {
			if ($isFirst) {
				$summPublisher = $relatedRecord->publisher;
				$summPubDate = $relatedRecord->publicationDate;
				$summPhysicalDesc = $relatedRecord->physical;
				$summEdition = $relatedRecord->edition;
				$summLanguage = $relatedRecord->language;
			} else {
				if ($summPublisher != $relatedRecord->publisher) {
					$summPublisher = $alwaysShowMainDetails ? translate(['text' => 'Varies, see individual formats and editions', 'isPublicFacing'=>true]) : null;
				}
				if ($summPubDate != $relatedRecord->publicationDate) {
					$summPubDate = $alwaysShowMainDetails ? translate(['text' => 'Varies, see individual formats and editions', 'isPublicFacing'=>true]) : null;
				}
				if ($summPhysicalDesc != $relatedRecord->physical) {
					$summPhysicalDesc = $alwaysShowMainDetails ? translate(['text' => 'Varies, see individual formats and editions', 'isPublicFacing'=>true]) : null;
				}
				if ($summEdition != $relatedRecord->edition) {
					$summEdition = $alwaysShowMainDetails ? translate(['text' => 'Varies, see individual formats and editions', 'isPublicFacing'=>true]) : null;
				}
				if ($summLanguage != $relatedRecord->language) {
					$summLanguage = $alwaysShowMainDetails ? translate(['text' => 'Varies, see individual formats and editions', 'isPublicFacing'=>true]) : null;
				}
			}
			$isFirst = false;
		}
		$interface->assign('summPublisher', rtrim($summPublisher, ','));
		$interface->assign('summPubDate', $summPubDate);
		$interface->assign('summPhysicalDesc', $summPhysicalDesc);
		$interface->assign('summEdition', $summEdition);
		$interface->assign('summLanguage', $summLanguage);
		$timer->logTime("Finished assignment of data based on related records");

		if (IPAddress::showDebuggingInformation()) {
			$interface->assign('summScore', $this->getScore());
			$interface->assign('summExplain', $this->getExplain());
		}
		$timer->logTime("Finished assignment of data based on solr debug info");

		//Get Rating
		$interface->assign('summRating', $this->getRatingData());
		$timer->logTime("Finished loading rating data");

		//Description
		$interface->assign('summDescription', $this->getDescriptionFast(true));
		$timer->logTime('Finished Loading Description');
		$memoryWatcher->logMemory("Finished Loading Description");
		if ($this->hasCachedSeries()) {
			$interface->assign('ajaxSeries', false);
			$interface->assign('summSeries', $this->getSeries(false));
		} else {
			$interface->assign('ajaxSeries', true);
			$interface->assign('summSeries', null);
		}
		$timer->logTime('Finished Loading Series');
		$memoryWatcher->logMemory("Finished Loading Series");

		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));

		$interface->assign('recordDriver', $this);

		$timer->logTime("Assigned all information to show search results");
		return 'RecordDrivers/GroupedWork/result.tpl';
	}

	public function getSemanticData()
	{
		//Schema.org
		$semanticData[] = array(
			'@context' => 'http://schema.org',
			'@type' => 'CreativeWork',
			'name' => $this->getTitle(),
			'author' => $this->getPrimaryAuthor(),
			'isAccessibleForFree' => true,
			'image' => $this->getBookcoverUrl('medium', true),
			'workExample' => $this->getSemanticWorkExamples(),
		);

		//BibFrame
		$semanticData[] = array(
			'@context' => array(
				"bf" => 'http://bibframe.org/vocab/',
				"bf2" => 'http://bibframe.org/vocab2/',
				"madsrdf" => 'http://www.loc.gov/mads/rdf/v1#',
				"rdf" => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
				"rdfs" => 'http://www.w3.org/2000/01/rdf-schema',
				"relators" => "http://id.loc.gov/vocabulary/relators/",
				"xsd" => "http://www.w3.org/2001/XMLSchema#"
			),
			'@graph' => array(
				array(
					'@type' => 'bf:Work', /* TODO: This should change to a more specific type Book/Movie as applicable */
					'bf:title' => $this->getTitle(),
					'bf:creator' => $this->getPrimaryAuthor(),
				),
			)
		);

		//Open graph data (goes in meta tags)
		global $interface;
		$interface->assign('og_title', $this->getTitle());
		$interface->assign('og_description', $this->getDescriptionFast());
		$interface->assign('og_type', $this->getOGType());
		$interface->assign('og_image', $this->getBookcoverUrl('medium', true));
		$interface->assign('og_url', $this->getLinkUrl(true));

		//TODO: add audience, award, content
		return $semanticData;
	}

	private function getSemanticWorkExamples()
	{
		global $configArray;
		$relatedWorkExamples = array();
		$relatedRecords = $this->getRelatedRecords();
		foreach ($relatedRecords as $record) {
			$relatedWorkExample = array(
				'@id' => $configArray['Site']['url'] . $record->getUrl(),
				'@type' => $record->getSchemaOrgType()
			);
			if ($record->getSchemaOrgBookFormat()) {
				$relatedWorkExample['bookFormat'] = $record->getSchemaOrgBookFormat();
			}
			$relatedWorkExamples[] = $relatedWorkExample;
		}
		return $relatedWorkExamples;
	}

	private $seriesData;

	public function getSeries($allowReload = true)
	{
		if (empty($this->seriesData)) {
			//Get a list of isbns from the record
			$relatedIsbns = $this->getISBNs();
			$novelist = NovelistFactory::getNovelist();
			$novelistData = $novelist->loadBasicEnrichment($this->getPermanentId(), $relatedIsbns, $allowReload);
			if ($novelistData != null && !empty($novelistData->seriesTitle)) {
				$this->seriesData = array(
					'seriesTitle' => $novelistData->seriesTitle,
					'volume' => $novelistData->volume,
					'fromNovelist' => true,
				);
			} else {
				$seriesFromIndex = $this->getIndexedSeries();
				if ($seriesFromIndex != null && count($seriesFromIndex) > 0) {
					$firstSeries = $seriesFromIndex[0];
					$this->seriesData = array(
						'seriesTitle' => $firstSeries['seriesTitle'],
						'volume' => isset($firstSeries['volume']) ? $firstSeries['volume'] : '',
						'fromNovelist' => false,
					);
				} else {
					return null;
				}
			}
		}
		return $this->seriesData;
	}

	public function getShortTitle($useHighlighting = false)
	{
		// Don't check for highlighted values if highlighting is disabled:
		if ($this->highlight && $useHighlighting) {
			if (isset($this->fields['_highlighting']['title_short'][0])) {
				return $this->fields['_highlighting']['title_short'][0];
			} else if (isset($this->fields['_highlighting']['title'][0])) {
				return $this->fields['_highlighting']['title'][0];
			}
		}

		if (isset($this->fields['title_short'])) {
			if (is_array($this->fields['title_short'])) {
				return reset($this->fields['title_short']);
			} else {
				return $this->fields['title_short'];
			}
		} else {
			if (isset($this->fields['title'])) {
				if (is_array($this->fields['title'])) {
					return reset($this->fields['title']);
				} else {
					return $this->fields['title'];
				}
			} else {
				return '';
			}
		}
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

		$fields = $this->fields;
		ksort($fields);
		$interface->assign('details', $fields);

		if (IPAddress::showDebuggingInformation()) {
			require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
			$groupedWork = new GroupedWork();
			$groupedWork->permanent_id = $this->getUniqueID();
			if (!empty($groupedWork->permanent_id) && $groupedWork->find(true)) {
				global $aspen_db;
				//Get the scopeId for the active scope
				global $solrScope;
				$scopeIdQuery = "SELECT id from scope where name = '$solrScope'";
				$scopeId = -1;
				$results = $aspen_db->query($scopeIdQuery, PDO::FETCH_ASSOC);
				if ($scopeResults = $results->fetch()) {
					$scopeId = $scopeResults['id'];
				}

				$interface->assign('groupedWorkInternalId',  $groupedWork->id);
				$interface->assign('activeScopeId',  $scopeId);
				$databaseIds = $this->getVariationRecordAndItemIdsFromDB($scopeId, $groupedWork->id);
				$interface->assign('variationData', $this->getRawVariationsDataFromDB($databaseIds['uniqueVariationIds']));
				$interface->assign('recordData', $this->getRawRecordDataFromDB($databaseIds['uniqueRecordIds']));
				$interface->assign('itemData', $this->getRawItemDataFromDB($databaseIds['uniqueItemIds']));
			}
		}

		$this->assignGroupedWorkStaffView();

		$interface->assign('bookcoverInfo', $this->getBookcoverInfo());

		return 'RecordDrivers/GroupedWork/staff-view.tpl';
	}

	public function assignGroupedWorkStaffView(){
		global $interface;

		$interface->assign('groupedWorkDetails', $this->getGroupedWorkDetails());

		$interface->assign('alternateTitles', $this->getAlternateTitles());

		$interface->assign('primaryIdentifiers', $this->getPrimaryIdentifiers());

		$interface->assign('specifiedDisplayInfo', $this->getSpecifiedDisplayInfo());
	}

	public function getSpecifiedDisplayInfo() {
		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkDisplayInfo.php';
		$existingDisplayInfo  = new GroupedWorkDisplayInfo();
		$existingDisplayInfo->permanent_id = $this->getPermanentId();
		if ($existingDisplayInfo->find(true)){
			return $existingDisplayInfo;
		}else{
			return null;
		}
	}

	public function getAlternateTitles(){
		//Load alternate titles
		if (UserAccount::userHasPermission('Set Grouped Work Display Information')){
			require_once ROOT_DIR . '/sys/Grouping/GroupedWorkAlternateTitle.php';
			$alternateTitle = new GroupedWorkAlternateTitle();
			$alternateTitle->permanent_id = $this->getPermanentId();
			$alternateTitle->find();
			$alternateTitles = [];
			while ($alternateTitle->fetch()){
				$alternateTitles[$alternateTitle->id] = clone $alternateTitle;
			}
			return $alternateTitles;
		}
		return null;
	}

	public function getPrimaryIdentifiers(){
		$primaryIdentifiers = [];
		if (UserAccount::userHasPermission('Manually Group and Ungroup Works')){
			require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
			$groupedWork = new GroupedWork();
			$groupedWork->permanent_id = $this->getUniqueID();
			if ($groupedWork->find(true)){
				require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
				$primaryIdentifier = new GroupedWorkPrimaryIdentifier();
				$primaryIdentifier->grouped_work_id = $groupedWork->id;
				$primaryIdentifier->find();
				while ($primaryIdentifier->fetch()){
					$primaryIdentifiers[] = clone($primaryIdentifier);
				}
			}
		}
		return $primaryIdentifiers;
	}

	public function getSolrField($fieldName)
	{
		return isset($this->fields[$fieldName]) ? $this->fields[$fieldName] : null;
	}

	public function loadSubjects()
	{
		global $library;
		global $interface;

		$subjects = array();
		$otherSubjects = array();
		$lcSubjects = array();
		$bisacSubjects = array();
		$oclcFastSubjects = array();
		$localSubjects = array();

		if (!empty($this->fields['lc_subject'])) {
			$lcSubjects = $this->fields['lc_subject'];
			$subjects = array_merge($subjects, $this->fields['lc_subject']);
		}

		if (!empty($this->fields['bisac_subject'])) {
			$bisacSubjects = $this->fields['bisac_subject'];
			$subjects = array_merge($subjects, $this->fields['bisac_subject']);
		}

		if (!empty($this->fields['topic_facet'])) {
			$subjects = array_merge($subjects, $this->fields['topic_facet']);
		}

		if (!empty($this->fields['subject_facet'])) {
			$subjects = array_merge($subjects, $this->fields['subject_facet']);
		}

		// TODO: get local Subjects
		// TODO: get oclc Fast Subjects
		// TODO: get other subjects

		$normalizedSubjects = array();
		require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
		foreach ($subjects as $subject) {
			$subject = StringUtils::removeTrailingPunctuation($subject);
			$subjectLower = strtolower($subject);
			if (!array_key_exists($subjectLower, $subjects)) {
				$normalizedSubjects[$subjectLower] = $subject;
			}
		}
		$subjects = $normalizedSubjects;

		natcasesort($subjects);
		$interface->assign('subjects', $subjects);
		$interface->assign('showLCSubjects', $library->getGroupedWorkDisplaySettings()->showLCSubjects);
		$interface->assign('showBisacSubjects', $library->getGroupedWorkDisplaySettings()->showBisacSubjects);
		$interface->assign('showFastAddSubjects', $library->getGroupedWorkDisplaySettings()->showFastAddSubjects);
		$interface->assign('showOtherSubjects', $library->getGroupedWorkDisplaySettings()->showOtherSubjects);

		if ($library->getGroupedWorkDisplaySettings()->showLCSubjects) {
			natcasesort($lcSubjects);
			$interface->assign('lcSubjects', $lcSubjects);
		}
		if ($library->getGroupedWorkDisplaySettings()->showBisacSubjects) {
			natcasesort($bisacSubjects);
			$interface->assign('bisacSubjects', $bisacSubjects);
		}
		if ($library->getGroupedWorkDisplaySettings()->showFastAddSubjects) {
			natcasesort($oclcFastSubjects);
			$interface->assign('oclcFastSubjects', $oclcFastSubjects);
		}
		if ($library->getGroupedWorkDisplaySettings()->showOtherSubjects) {
			natcasesort($otherSubjects);
			$interface->assign('otherSubjects', $otherSubjects);
		}
		natcasesort($localSubjects);
		$interface->assign('localSubjects', $localSubjects);

	}

	/**
	 * @param bool $useHighlighting Whether or not the subtitle is highlighted
	 * @return string The subtitle
	 */
	public function getSubtitle($useHighlighting = false)
	{
		// Don't check for highlighted values if highlighting is disabled:
		if ($useHighlighting) {
			if (isset($this->fields['_highlighting']['subtitle_display'][0])) {
				return $this->fields['_highlighting']['subtitle_display'][0];
			}
		}
		return isset($this->fields['subtitle_display']) ?
			$this->fields['subtitle_display'] : '';
	}

	public function getTitle($useHighlighting = false)
	{
		// Don't check for highlighted values if highlighting is disabled:
		if ($this->highlight && $useHighlighting) {
			if (isset($this->fields['_highlighting']['title_display'][0])) {
				return $this->fields['_highlighting']['title_display'][0];
			} else if (isset($this->fields['_highlighting']['title_full'][0])) {
				return $this->fields['_highlighting']['title_full'][0];
			}
		}

		if (isset($this->fields['title_display'])) {
			return $this->fields['title_display'];
		} else {
			if (isset($this->fields['title_full'])) {
				if (is_array($this->fields['title_full'])) {
					return reset($this->fields['title_full']);
				} else {
					return $this->fields['title_full'];
				}
			} else {
				return '';
			}
		}
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
		$tableOfContents = array();
		foreach ($this->getRelatedRecords() as $record) {
			if ($record->_driver) {
				$driver = $record->_driver;
				/** @var GroupedWorkSubDriver $driver */
				$recordTOC = $driver->getTableOfContents();
				if ($recordTOC != null && count($recordTOC) > 0) {
					$editionDescription = "{$record->format}";
					if ($record->edition) {
						$editionDescription .= " - {$record->edition}";
					}
					$tableOfContents = array_merge($tableOfContents, array("<h4>From the $editionDescription</h4>"), $recordTOC);
				}
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
	public function getUniqueID()
	{
		return $this->fields['id'];
	}

	/**
	 * Get the UPC associated with the record (may be empty).
	 *
	 * @return  array
	 */
	public function getUPCs()
	{
		// If UPCs is in the index, it should automatically be an array... but if
		// it's not set at all, we should normalize the value to an empty array.
		if (isset($this->fields['upc'])) {
			if (is_array($this->fields['upc'])) {
				return $this->fields['upc'];
			} else {
				return array($this->fields['upc']);
			}
		} else {
			return array();
		}
	}

	/**
	 * @return UserWorkReview[]
	 */
	public function getUserReviews()
	{
		$reviews = array();

		// Determine if we should censor bad words or hide the comment completely.
		global $library;
		$censorWords = !$library->getGroupedWorkDisplaySettings()->hideCommentsWithBadWords; // censor if not hiding
		require_once ROOT_DIR . '/sys/LocalEnrichment/BadWord.php';
		$badWords = new BadWord();

		// Get the Reviews
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';
		$userReview = new UserWorkReview();
		$userReview->groupedRecordPermanentId = $this->getUniqueID();
		$userReview->find();
		while ($userReview->fetch()) {
			$userForReview = new User();
			$userForReview->id = $userReview->userId;
			$userForReview->find(true);
			// Set the display Name for the review
			if (!$userForReview->displayName) {
				if (strlen(trim($userForReview->firstname)) >= 1) {
					$userReview->setDisplayName(substr($userForReview->firstname, 0, 1) . '. ' . $userForReview->lastname);
				} else {
					$userReview->setDisplayName($userForReview->lastname);
				}
			}else{
				$userReview->setDisplayName($userForReview->displayName);
			}

			// Clean-up User Review Text
			if ($userReview->review) { // if the review has content to check
				if ($censorWords) { // replace bad words
					$userReview->review = $badWords->censorBadWords($userReview->review);
				} else { // skip reviews with bad words
					if ($badWords->hasBadWords($userReview->review)) continue;
				}
			}

			$reviews[] = clone $userReview;
		}
		return $reviews;
	}

	public function hasCachedSeries()
	{
		//Get a list of isbns from the record
		$novelist = NovelistFactory::getNovelist();
		return $novelist->doesGroupedWorkHaveCachedSeries($this->getPermanentId());
	}

	public function isValid()
	{
		return $this->isValid;
	}

	static $statusRankings = array(
		'Currently Unavailable' => 1,
		'On Order' => 2,
		'Coming Soon' => 3,
		'In Processing' => 3.5,
		'In Transit' => 3.75, //This used to show as 6.5 (above available online), moved down because we don't know if it's in transit to another library, or if it's in transit to a hold shelf.
		'Checked Out' => 4,
		'Library Use Only' => 5,
		'Available Online' => 6,
		'On Shelf' => 7
	);

	public static function keepBestGroupedStatus($groupedStatus, $groupedStatus1)
	{
		if ($groupedStatus == $groupedStatus1){
			return $groupedStatus;
		}
		if (isset(GroupedWorkDriver::$statusRankings[$groupedStatus])) {
			$ranking1 = GroupedWorkDriver::$statusRankings[$groupedStatus];
		} else {
			$ranking1 = 1.5;
		}
		if (isset(GroupedWorkDriver::$statusRankings[$groupedStatus1])) {
			$ranking2 = GroupedWorkDriver::$statusRankings[$groupedStatus1];
		} else {
			$ranking2 = 1.5;
		}
		if ($ranking1 > $ranking2) {
			return $groupedStatus;
		} else {
			return $groupedStatus1;
		}
	}

	public function loadEnrichment()
	{
		global $memoryWatcher;
		$isbn = $this->getCleanISBN();
		$enrichment = array();
		if ($isbn == null || strlen($isbn) == 0) {
			return $enrichment;
		}
		$novelist = NovelistFactory::getNovelist();
		$memoryWatcher->logMemory('Setup Novelist Connection');
		$enrichment['novelist'] = $novelist->loadEnrichment($this->getPermanentId(), $this->getISBNs());
		return $enrichment;
	}

	/**
	 * @param $validItemIds
	 * @return array
	 */
	protected function loadItemDetailsFromIndex($validItemIds)
	{
		$relatedItemsFieldName = 'item_details';
		$itemsFromIndex = array();
		if (isset($this->fields[$relatedItemsFieldName])) {
			$itemsFromIndexRaw = $this->fields[$relatedItemsFieldName];
			if (!is_array($itemsFromIndexRaw)) {
				$itemsFromIndexRaw = array($itemsFromIndexRaw);
			}
			foreach ($itemsFromIndexRaw as $tmpItem) {
				$itemDetails = explode('|', $tmpItem);
				$itemIdentifier = $itemDetails[0] . ':' . $itemDetails[1];
				if (in_array($itemIdentifier, $validItemIds)) {
					$itemsFromIndex[] = $itemDetails;
					if (!array_key_exists($itemDetails[0], $this->relatedItemsByRecordId)) {
						$this->relatedItemsByRecordId[$itemDetails[0]] = array();
					}
					$this->relatedItemsByRecordId[$itemDetails[0]][] = $itemDetails;
				}
			}
			return $itemsFromIndex;
		}
		return $itemsFromIndex;
	}

	/**
	 * Get related records from the index filtered according to the current scope
	 *
	 * @param $validRecordIds
	 * @return array
	 */
	protected function loadRecordDetailsFromIndex($validRecordIds)
	{
		$relatedRecordFieldName = "record_details";
		$recordsFromIndex = array();
		if (isset($this->fields[$relatedRecordFieldName])) {
			$relatedRecordIdsRaw = $this->fields[$relatedRecordFieldName];
			if (!is_array($relatedRecordIdsRaw)) {
				$relatedRecordIdsRaw = array($relatedRecordIdsRaw);
			}
			foreach ($relatedRecordIdsRaw as $tmpItem) {
				$recordDetails = explode('|', $tmpItem);
				//Check to see if the record is valid
				if (in_array($recordDetails[0], $validRecordIds)) {
					$recordsFromIndex[$recordDetails[0]] = $recordDetails;
				}
			}
		}
		return $recordsFromIndex;
	}

	private function loadRelatedRecords($forCovers = false)
	{
		global $timer;
		global $memoryWatcher;
		if ($this->relatedRecords == null || isset($_REQUEST['reload'])) {
			$timer->logTime("Starting to load related records for {$this->getUniqueID()}");

			$this->relatedItemsByRecordId = array();

			global $solrScope;
			global $library;
			$scopingInfoFieldName = 'scoping_details_' . $solrScope;
			$relatedRecords = array();
			if (isset($this->fields[$scopingInfoFieldName])) {
				$user = UserAccount::getActiveUserObj();

				$searchLocation = Location::getSearchLocation();
				list($scopingInfo, $validRecordIds, $validItemIds) = $this->loadScopingDetails($solrScope);
				$timer->logTime("Loaded Scoping Details from the index");
				$memoryWatcher->logMemory("Loaded scoping details from the index");

				$recordsFromIndex = $this->loadRecordDetailsFromIndex($validRecordIds);
				$timer->logTime("Loaded Record Details from the index");
				$memoryWatcher->logMemory("Loaded Record Details from the index");

				//Get a list of related items filtered according to scoping
				$this->loadItemDetailsFromIndex($validItemIds);
				$timer->logTime("Loaded Item Details from the index");
				$memoryWatcher->logMemory("Loaded Item Details from the index");

				//Load the work from the database so we can use it in each record diver
				require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
				$groupedWork = new GroupedWork();
				$groupedWork->permanent_id = $this->getUniqueID();
				//This will be false if the record is old
				//Protect against loading every record in the database!
				if (!empty($groupedWork->permanent_id)) {
					if ($groupedWork->find(true)) {
						//Generate record information based on the information we have in the index
						foreach ($recordsFromIndex as $recordDetails) {
							$relatedRecord = $this->setupRelatedRecordDetails($recordDetails, $groupedWork, $timer, $scopingInfo, $searchLocation, $library, $forCovers);
							if ($relatedRecord != null) {
								$relatedRecords[$relatedRecord->id] = $relatedRecord;
								$memoryWatcher->logMemory("Setup related record details for " . $relatedRecord->id);
							} else {
								global $logger;
								$logger->log("Error setting up related record " . $recordDetails, LOG_NOTICE);
							}
						}
					}
				}
			}else{
				$searchLocation = Location::getSearchLocation();

				require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
				require_once ROOT_DIR . '/sys/Grouping/Manifestation.php';
				require_once ROOT_DIR . '/sys/Grouping/Variation.php';
				require_once ROOT_DIR . '/sys/Grouping/Record.php';
				require_once ROOT_DIR . '/sys/Grouping/Item.php';
				$groupedWork = new GroupedWork();
				$groupedWork->permanent_id = $this->getUniqueID();
				if (!empty($groupedWork->permanent_id) && $groupedWork->find(true)) {
					global $aspen_db;
					//Get the scopeId for the active scope
					$scopeIdQuery = "SELECT id from scope where name = '$solrScope'";
					$scopeId = -1;
					$results = $aspen_db->query($scopeIdQuery, PDO::FETCH_ASSOC);
					if ($scopeResults = $results->fetch()) {
						$scopeId = $scopeResults['id'];
					}

					//Get the ids of all the variations, records, and items attached to the work
					$databaseIds = $this->getVariationRecordAndItemIdsFromDB($scopeId, $groupedWork->id);

					$variations = $this->getRawVariationsDataFromDB($databaseIds['uniqueVariationIds']);
					$this->_relatedManifestations = array();
					/** @var  $allVariations Grouping_Variation[] */
					$allVariations = [];
					foreach ($variations as $variation){
						if (!array_key_exists($variation['format'], $this->_relatedManifestations)) {
							$this->_relatedManifestations[$variation['format']] = new Grouping_Manifestation($variation);
						}
						$variationObj = new Grouping_Variation($variation);
						$this->_relatedManifestations[$variation['format']]->addVariation($variationObj);
						$allVariations[$variationObj->databaseId] = $variationObj;
					}

					$records = $this->getRawRecordDataFromDB($databaseIds['uniqueRecordIds']);

					/** @var Grouping_Record[] $allRecords */
					$allRecords = [];
					foreach ($records as $record){
						/** GroupedWorkSubDriver $recordDriver */
						require_once ROOT_DIR . '/RecordDrivers/RecordDriverFactory.php';
						$recordId = $record['source'];
						$recordId .= ($record['subSource'] != null ? ':' . $record['subSource'] : '');
						$recordId .= ':' . $record['recordIdentifier'];
						$recordDriver = RecordDriverFactory::initRecordDriverById($recordId, $groupedWork);

						$volumeData = $this->getVolumeDataForRecord($recordId);
						$relatedRecord = new Grouping_Record($recordId, $record, $recordDriver, $volumeData, $record['source'], true);
						$relatedRecords[$relatedRecord->id] = $relatedRecord;
						$allRecords[$relatedRecord->databaseId] = $relatedRecord;
					}

					$scopedItems = $this->getRawItemDataFromDB($databaseIds['uniqueItemIds']);

					foreach ($scopedItems as $scopedItem) {
						$relatedRecord = $allRecords[$scopedItem['groupedWorkRecordId']];
						$relatedVariation = $allVariations[$scopedItem['groupedWorkVariationId']];
						$scopedItem['isEcontent'] = $relatedVariation->isEcontent;
						$scopedItem['eContentSource'] = $relatedVariation->econtentSource;
						$scopedItem['scopeId'] = $scopeId;
						//Look for urls for the item
						$itemUrlQuery = "SELECT url from grouped_work_record_item_url where groupedWorkItemId = {$scopedItem['groupedWorkItemId']} AND (scopeId = -1 OR scopeId = $scopeId) ORDER BY scopeId desc limit 1";
						$results = $aspen_db->query($itemUrlQuery, PDO::FETCH_ASSOC);
						$itemUrls = $results->fetchAll();
						if (count($itemUrls) > 0){
							$scopedItem['localUrl'] = $itemUrls[0]['url'];
						}
						$results->closeCursor();
						$itemData = new Grouping_Item($scopedItem, null, $searchLocation, $library);
						$relatedRecord->addItem($itemData);
					}

					//Finally add records to the correct manifestation (so status updates properly)
					foreach ($allRecords as $record) {
						//Add to the correct manifestation
						if (isset( $this->_relatedManifestations[$record->format])) {
							$this->_relatedManifestations[$record->format]->addRecord($record);
						}else{
							//This should not happen
							$manifestation = new Grouping_Manifestation($record);
							$this->_relatedManifestations[$record->format] = $manifestation;
							global $logger;
							$logger->log("Manifestation not found for record {$record->id} {$record->format}", Logger::LOG_ERROR);
						}
					}

					//Sort Records within each manifestation and variation
					foreach ($this->_relatedManifestations as $manifestationKey => $manifestation){
						$relatedRecordsForManifestation = $manifestation->getRelatedRecords();
						if (count($relatedRecordsForManifestation) > 1) {
							uasort($relatedRecordsForManifestation, array($this, "compareRelatedRecords"));
							$manifestation->setSortedRelatedRecords($relatedRecordsForManifestation);
							foreach ($manifestation->getVariations() as $variationKey => $variation) {
								$relatedRecordsForVariation = $variation->getRelatedRecords();
								if (count($relatedRecordsForVariation) > 1){
									uasort($relatedRecordsForVariation, array($this, "compareRelatedRecords"));
									$variation->setSortedRelatedRecords($relatedRecordsForVariation);
								}elseif (count($relatedRecordsForVariation) == 0){
									$manifestation->removeVariation($variationKey);
								}
							}
						}elseif (count($relatedRecordsForManifestation) == 0){
							unset($this->_relatedManifestations[$manifestationKey]);
						}
					}

					uasort($this->_relatedManifestations, array($this, "compareRelatedManifestations"));
				}
			}

			//Sort the records based on format and then edition
			uasort($relatedRecords, array($this, "compareRelatedRecords"));

			$this->relatedRecords = $relatedRecords;
			$timer->logTime("Finished loading related records {$this->getUniqueID()}");
		}
	}

	/**
	 * @param $solrScope
	 * @return array
	 */
	protected function loadScopingDetails($solrScope)
	{
		//First load scoping information from the index.  This is stored as multiple values
		//within the scoping details field for the scope.
		//Each field is
		$scopingInfoFieldName = 'scoping_details_' . $solrScope;
		$scopingInfo = array();
		$validRecordIds = array();
		$validItemIds = array();
		if (isset($this->fields[$scopingInfoFieldName])) {
			$scopingInfoRaw = $this->fields[$scopingInfoFieldName];
			if (!is_array($scopingInfoRaw)) {
				$scopingInfoRaw = array($scopingInfoRaw);
			}
			foreach ($scopingInfoRaw as $tmpItem) {
				$scopingDetails = explode('|', $tmpItem);
				$scopeKey = $scopingDetails[0] . ':' . ($scopingDetails[1] == 'null' ? '' : $scopingDetails[1]);
				$scopingInfo[$scopeKey] = $scopingDetails;
				$validRecordIds[] = $scopingDetails[0];
				$validItemIds[] = $scopeKey;
			}
		}
		return array($scopingInfo, $validRecordIds, $validItemIds);
	}

	private function getVariationRecordAndItemIdsFromDB($scopeId, $groupedWorkId){
		global $aspen_db;
		$getIdsQuery = "select groupedWorkId, groupedWorkVariationId, groupedWorkRecordId, grouped_work_record_items.id as groupedRecordItemId FROM 
										grouped_work_record_items inner join grouped_work_records on groupedWorkRecordId = grouped_work_records.id where 
										(locationOwnedScopes like '%~$scopeId~%' OR libraryOwnedScopes like '%~$scopeId~%' OR recordIncludedScopes LIKE '%~$scopeId~%') and groupedWorkId = {$groupedWorkId}";
		$results = $aspen_db->query($getIdsQuery, PDO::FETCH_ASSOC);
		$allIds = $results->fetchAll();
		$results->closeCursor();
		$uniqueVariationIds = [];
		$uniqueRecordIds = [];
		$uniqueItemIds = [];
		foreach ($allIds as $id) {
			$uniqueVariationIds[$id['groupedWorkVariationId']] = $id['groupedWorkVariationId'];
			$uniqueRecordIds[$id['groupedWorkRecordId']] = $id['groupedWorkRecordId'];
			$uniqueItemIds[$id['groupedRecordItemId']] = $id['groupedRecordItemId'];
		}
		return [
			'uniqueVariationIds' => $uniqueVariationIds,
			'uniqueRecordIds' => $uniqueRecordIds,
			'uniqueItemIds' => $uniqueItemIds
		];
	}
	private function getRawVariationsDataFromDB($uniqueVariationIds){
		global $aspen_db;

		//Load manifestation and variation information
		if (count($uniqueVariationIds) == 0) {
			$variations = [];
		}else{
			$uniqueVariationsIdsString = implode(',', $uniqueVariationIds);
			$variationQuery = "SELECT grouped_work_variation.id, indexed_language.language, indexed_eContentSource.eContentSource, indexed_format.format, indexed_format_category.formatCategory FROM grouped_work_variation 
									  LEFT JOIN indexed_language on primaryLanguageId = indexed_language.id
									  LEFT JOIN indexed_eContentSource on eContentSourceId = indexed_eContentSource.id
									  LEFT JOIN indexed_format on formatId = indexed_format.id
									  LEFT JOIN indexed_format_category on formatCategoryId = indexed_format_category.id
									  where grouped_work_variation.id IN ($uniqueVariationsIdsString)";
			$variationResults = $aspen_db->query($variationQuery, PDO::FETCH_ASSOC);
			$variations = $variationResults->fetchAll();
			$variationResults->closeCursor();
		}
		return $variations;
	}

	private function getRawRecordDataFromDB($uniqueRecordIds){
		global $aspen_db;

		//Load record information
		if (count($uniqueRecordIds) == 0){
			$records = [];
		}else {
			$uniqueRecordIdsString = implode(',', $uniqueRecordIds);
			$recordQuery = "SELECT grouped_work_records.id, recordIdentifier, indexed_record_source.source, indexed_record_source.subSource, indexed_edition.edition, indexed_publisher.publisher, indexed_publicationDate.publicationDate, indexed_physicalDescription.physicalDescription, indexed_format.format, indexed_format_category.formatCategory, indexed_language.language FROM grouped_work_records 
								  LEFT JOIN indexed_record_source ON sourceId = indexed_record_source.id
								  LEFT JOIN indexed_edition ON editionId = indexed_edition.id
								  LEFT JOIN indexed_publisher ON publisherId = indexed_publisher.id
								  LEFT JOIN indexed_publicationDate ON publicationDateId = indexed_publicationDate.id
								  LEFT JOIN indexed_physicalDescription ON physicalDescriptionId = indexed_physicalDescription.id
								  LEFT JOIN indexed_format on formatId = indexed_format.id
								  LEFT JOIN indexed_format_category on formatCategoryId = indexed_format_category.id
								  LEFT JOIN indexed_language on languageId = indexed_language.id
								  where grouped_work_records.id IN ($uniqueRecordIdsString)";
			$results = $aspen_db->query($recordQuery, PDO::FETCH_ASSOC);
			$records = $results->fetchAll();
			$results->closeCursor();
		}
		return $records;
	}

	private function getRawItemDataFromDB($uniqueItemIds){
		global $aspen_db;
		//Load item/scope information
		if (count($uniqueItemIds) == 0){
			$scopedItems = [];
		}else {
			$uniqueItemIdsString = implode(',', $uniqueItemIds);
			$scopeQuery = "SELECT grouped_work_record_items.id as groupedWorkItemId, available, holdable, inLibraryUseOnly, locationOwnedScopes, libraryOwnedScopes, groupedStatusTbl.status as groupedStatus, statusTbl.status as status, 
								  grouped_work_record_items.groupedWorkRecordId, grouped_work_record_items.groupedWorkVariationId, grouped_work_record_items.itemId, indexed_callNumber.callNumber, indexed_shelfLocation.shelfLocation, numCopies, isOrderItem, dateAdded, 
       							  indexed_locationCode.locationCode, indexed_subLocationCode.subLocationCode, lastCheckInDate
								  FROM grouped_work_record_items
								  LEFT JOIN indexed_status as groupedStatusTbl on groupedStatusId = groupedStatusTbl.id 
								  LEFT JOIN indexed_status as statusTbl on statusId = statusTbl.id 
								  LEFT JOIN indexed_callNumber ON callNumberId = indexed_callNumber.id
								  LEFT JOIN indexed_shelfLocation ON shelfLocationId = indexed_shelfLocation.id
								  LEFT JOIN indexed_locationCode on locationCodeId = indexed_locationCode.id
								  LEFT JOIN indexed_subLocationCode on subLocationCodeId = indexed_subLocationCode.id
								  where grouped_work_record_items.id IN ($uniqueItemIdsString)";
			$results = $aspen_db->query($scopeQuery, PDO::FETCH_ASSOC);
			$scopedItems = $results->fetchAll();
			$results->closeCursor();
		}
		return $scopedItems;
	}

	private static function normalizeEdition($edition)
	{
		$edition = strtolower($edition);
		$edition = str_replace('first', '1', $edition);
		$edition = str_replace('second', '2', $edition);
		$edition = str_replace('third', '3', $edition);
		$edition = str_replace('fourth', '4', $edition);
		$edition = str_replace('fifth', '5', $edition);
		$edition = str_replace('sixth', '6', $edition);
		$edition = str_replace('seventh', '7', $edition);
		$edition = str_replace('eighth', '8', $edition);
		$edition = str_replace('ninth', '9', $edition);
		$edition = str_replace('tenth', '10', $edition);
		$edition = str_replace('eleventh', '11', $edition);
		$edition = str_replace('twelfth', '12', $edition);
		$edition = str_replace('thirteenth', '13', $edition);
		$edition = str_replace('fourteenth', '14', $edition);
		$edition = str_replace('fifteenth', '15', $edition);
		$edition = preg_replace('/\D/', '', $edition);
		return $edition;
	}

	/**
	 * @param $recordDetails
	 * @param GroupedWork $groupedWork
	 * @param Timer $timer
	 * @param $scopingInfo
	 * @param Location $searchLocation
	 * @param Library $library
	 * @param bool $forCovers Optimization if we are only loading info for the covers
	 * @return Grouping_Record
	 */
	protected function setupRelatedRecordDetails($recordDetails, $groupedWork, $timer, $scopingInfo, $searchLocation, $library, $forCovers = false)
	{
		global $memoryWatcher;
		//Check to see if we have any volume data for the record
		$volumeData = $this->getVolumeDataForRecord($recordDetails[0]);

		//		list($source) = explode(':', $recordDetails[0], 1); // this does not work for 'overdrive:27770ba9-9e68-410c-902b-de2de8e2b7fe', returns 'overdrive:27770ba9-9e68-410c-902b-de2de8e2b7fe'
		// when loading book covers.
		list($source) = explode(':', $recordDetails[0], 2);
		/** GroupedWorkSubDriver $recordDriver */
		require_once ROOT_DIR . '/RecordDrivers/RecordDriverFactory.php';
		$recordDriver = RecordDriverFactory::initRecordDriverById($recordDetails[0], $groupedWork);
		$timer->logTime("Loaded Record Driver for  $recordDetails[0]");
		$memoryWatcher->logMemory("Loaded Record Driver for  $recordDetails[0]");

		require_once ROOT_DIR . '/sys/Grouping/Record.php';
		$relatedRecord = new Grouping_Record($recordDetails[0], $recordDetails, $recordDriver, $volumeData, $source);

		$timer->logTime("Setup base related record");
		$memoryWatcher->logMemory("Setup base related record");

		//Process the items for the record and add additional information as needed
		$localShelfLocation = null;
		$libraryShelfLocation = null;
		$localCallNumber = null;
		$libraryCallNumber = null;

		global $locationSingleton;
		$physicalLocation = $locationSingleton->getPhysicalLocation();

		$i = 0;
		foreach ($this->relatedItemsByRecordId[$relatedRecord->id] as $curItem) {
			require_once ROOT_DIR . '/sys/Grouping/Item.php';
			$item = new Grouping_Item($curItem, $scopingInfo, $searchLocation, $library);
			$relatedRecord->addItem($item);

			$description = $item->shelfLocation . ':' . $item->callNumber;

			$volume = null;
			$volumeId = null;
			$volumeOrder = null;
			if (count($volumeData) > 0) {
				/** @var IlsVolumeInfo $volumeDataPoint */
				foreach ($volumeData as $volumeDataPoint) {
					if ((strlen($volumeDataPoint->relatedItems) == 0) || (strpos($volumeDataPoint->relatedItems, $curItem[1]) !== false)) {
						if ($item->holdable) {
							$volumeDataPoint->holdable = true;
						}
						if (strlen($volumeDataPoint->relatedItems) > 0) {
							$volume = $volumeDataPoint->displayLabel;
							$volumeId = $volumeDataPoint->volumeId;
							$volumeOrder = $volumeDataPoint->displayOrder;
							break;
						}
					}
				}
			}
			$key = str_pad($volumeOrder, 10, '0', STR_PAD_LEFT) . $description;

			$section = 'Other Locations';
			if ($item->locallyOwned) {
				if ($localShelfLocation == null) {
					$localShelfLocation = $item->shelfLocation;
				}
				if ($localCallNumber == null) {
					$localCallNumber = $item->callNumber;
				}
				if ($item->available && !$item->isEContent) {
					//Set available here only if we're in the library
					if (!empty($physicalLocation)) {
						$relatedRecord->getStatusInformation()->setAvailableHere(true);
					}
					$relatedRecord->getStatusInformation()->setAvailableLocally(true);
					$relatedRecord->setClass('here');
				}
				$relatedRecord->addLocalCopies($item->numCopies);
				$relatedRecord->setHasLocalItem(true);
				$key = '1 ' . $key;
				$sectionId = 1;
				$section = 'In this library';
			} elseif ($item->libraryOwned) {
				if ($libraryShelfLocation == null) {
					$libraryShelfLocation = $item->shelfLocation;
				}
				if ($libraryCallNumber == null) {
					$libraryCallNumber = $item->callNumber;
				}
				//We don't want to set this if we are in a location scope and it isn't available at that branch
				//if it was, location owned would be set
				if ($item->available && !$item->isEContent && empty($physicalLocation)) {
					$relatedRecord->getStatusInformation()->setAvailableLocally(true);
				}
				$relatedRecord->addLocalCopies($item->numCopies);
				//If we are inside a branch, we only set that it has a local item if locationOwned is true
				if (empty($physicalLocation) && ($searchLocation == null || $item->isEContent)) {
					$relatedRecord->setHasLocalItem(true);
				}
				$key = '5 ' . $key;
				$sectionId = 5;
				$section = $library->displayName;
			} elseif ($item->isOrderItem) {
				$key = '7 ' . $key;
				$sectionId = 7;
				$section = 'On Order';
			} else {
				$key = '6 ' . $key;
				$sectionId = 6;
			}

			$callNumber = $item->callNumber;
			if ((strlen($volume) > 0) && !substr($item->callNumber, -strlen($volume)) == $volume) {
				$callNumber = trim($item->callNumber . ' ' . $volume);
			}
			//Add the item to the item summary
			$itemSummaryInfo = array(
				'description' => $description,
				'shelfLocation' => $item->shelfLocation,
				'callNumber' => $callNumber,
				'totalCopies' => $item->numCopies,
				'availableCopies' => ($item->available && !$item->isOrderItem) ? $item->numCopies : 0,
				'isLocalItem' => $item->locallyOwned,
				'isLibraryItem' => $item->libraryOwned,
				'inLibraryUseOnly' => $item->inLibraryUseOnly,
				'allLibraryUseOnly' => $item->inLibraryUseOnly,
				'displayByDefault' => $item->isDisplayByDefault(),
				'onOrderCopies' => $item->isOrderItem ? $item->numCopies : 0,
				'status' => $item->groupedStatus,
				'statusFull' => $item->status,
				'available' => $item->available,
				'holdable' => $item->holdable,
				'sectionId' => $sectionId,
				'section' => $section,
				'relatedUrls' => $item->getRelatedUrls(),
				'lastCheckinDate' => isset($curItem[14]) ? $curItem[14] : '',
				'volume' => $volume,
				'volumeId' => $volumeId,
				'isEContent' => $item->isEContent,
				'locationCode' => $item->locationCode,
				'subLocation' => $item->subLocation,
				'itemId' => $item->itemId
			);
			if (!$forCovers) {
				$item->setActions($recordDriver != null ? $recordDriver->getItemActions($itemSummaryInfo) : []);
				$itemSummaryInfo['actions'] = $item->getActions();
			}

			//Group the item based on location and call number for display in the summary
			$relatedRecord->addItemSummary($key, $itemSummaryInfo, $item->groupedStatus);
			//Also add to the details for display in the full list
			$relatedRecord->addItemDetails($key . $i++, $itemSummaryInfo);
		}
		if ($localShelfLocation != null) {
			$relatedRecord->setShelfLocation($localShelfLocation);
		} elseif ($libraryShelfLocation != null) {
			$relatedRecord->setShelfLocation($libraryShelfLocation);
		}
		if ($localCallNumber != null) {
			$relatedRecord->setCallNumber($localCallNumber);
		} elseif ($libraryCallNumber != null) {
			$relatedRecord->setCallNumber($libraryCallNumber);
		}
		$timer->logTime("Setup record items " . count($this->relatedItemsByRecordId[$relatedRecord->id]));
		$relatedRecord->sortItemSummary();
		$timer->logTime("Sorted Item Summary");
		$relatedRecord->sortItemDetails();
		$timer->logTime("Sorted Item Details");
		$memoryWatcher->logMemory("Setup record items");

		if (!$forCovers) {
			$relatedRecord->setActions($recordDriver != null ? $recordDriver->getRecordActions($relatedRecord, $relatedRecord->getStatusInformation()->isAvailableLocally() || $relatedRecord->getStatusInformation()->isAvailableOnline(), $relatedRecord->isHoldable(), $volumeData) : array());
			$timer->logTime("Loaded actions");
			$memoryWatcher->logMemory("Loaded actions");
		}

		$recordDriver = null;
		return $relatedRecord;
	}

	/**
	 * @return array
	 */
	public function getGroupedWorkDetails()
	{
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		$groupedWork = new GroupedWork();
		$groupedWork->permanent_id = $this->getPermanentId();
		$groupedWorkDetails = array();
		if ($groupedWork->find(true)) {
			$groupedWorkDetails['Full title'] = $groupedWork->full_title;
			$groupedWorkDetails['Author'] = $groupedWork->author;
			$groupedWorkDetails['Grouping Category'] = $groupedWork->grouping_category;
			$groupedWorkDetails['Last Update'] = date('Y-m-d H:i:sA', $groupedWork->date_updated);
			if ($this->fields != null && array_key_exists('last_indexed', $this->fields)) {
				$groupedWorkDetails['Last Indexed'] = date('Y-m-d H:i:sA', strtotime($this->fields['last_indexed']));
			}
		} else {
			$groupedWorkDetails['Deleted?'] = 'This work has been deleted from the database and should be re-indexed';
		}
		return $groupedWorkDetails;
	}

	public function getBookcoverInfo()
	{
		require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
		$bookCoverInfo = new BookCoverInfo();
		$bookCoverInfo->recordId = $this->getPermanentId();
		$bookCoverInfo->recordType = 'grouped_work';
		if ($bookCoverInfo->find(true)){
			return $bookCoverInfo;
		}else{
			return null;
		}
	}

	function getWhileYouWait(){
		global $library;
		if (!$library->showWhileYouWait){
			return [];
		}
		//Load Similar titles (from Solr)
		global $configArray;
		require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
		/** @var SearchObject_GroupedWorkSearcher $db */
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();
		$searchObject->disableScoping();
		$similar = $searchObject->getMoreLikeThis($this->getPermanentId(), true, false, 3);
		// Send the similar items to the template; if there is only one, we need
		// to force it to be an array or things will not display correctly.
		if (isset($similar) && count($similar['response']['docs']) > 0) {
			$whileYouWaitTitles = array();
			foreach ($similar['response']['docs'] as $similarTitle){
				$similarTitleDriver = new GroupedWorkDriver($similarTitle);
				$formatCategoryInfo = [];
				$relatedManifestations = $similarTitleDriver->getRelatedManifestations();
				foreach ($relatedManifestations as $relatedManifestation){
					if ($relatedManifestation->isAvailable() || $relatedManifestation->isAvailableOnline()){
						$formatCategoryInfo[$relatedManifestation->formatCategory] = [
							'formatCategory' => $relatedManifestation->formatCategory,
							'available' => true,
							'image' => $configArray['Site']['url'] . '/interface/themes/responsive/images/' . strtolower(str_replace(' ', '', $relatedManifestation->formatCategory)) . "_available.png"
						];
					}else{
						if (!array_key_exists($relatedManifestation->formatCategory, $formatCategoryInfo)){
							$formatCategoryInfo[$relatedManifestation->formatCategory] = [
								'formatCategory' => $relatedManifestation->formatCategory,
								'available' => false,
								'image' => $configArray['Site']['url'] . '/interface/themes/responsive/images/' . strtolower(str_replace(' ', '', $relatedManifestation->formatCategory)) . "_small.png",
							];
						}
					}
				}
				$whileYouWaitTitles[] = [
					'driver' => $similarTitleDriver,
					'url' => $similarTitleDriver->getLinkUrl(),
					'title' => $similarTitleDriver->getTitle(),
					'coverUrl' => $similarTitleDriver->getBookcoverUrl('medium', true),
					'formatCategories' => $formatCategoryInfo,
				];
			}
			return $whileYouWaitTitles;
		}else{
			return [];
		}
	}

	public function loadReadingHistoryIndicator(): void
	{
		global $interface;
		$interface->assign('inReadingHistory', false);
		if (UserAccount::isLoggedIn()) {
			require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
			$readingHistoryEntry = new ReadingHistoryEntry();
			$readingHistoryEntry->userId = UserAccount::getActiveUserId();
			$readingHistoryEntry->deleted = 0;
			$readingHistoryEntry->groupedWorkPermanentId = $this->getPermanentId();
			$readingHistoryEntry->groupBy('groupedWorkPermanentId');
			$readingHistoryEntry->selectAdd();
			$readingHistoryEntry->selectAdd('MAX(checkOutDate) as checkOutDate');
			if ($readingHistoryEntry->find(true)) {
				$interface->assign('inReadingHistory', true);
				$interface->assign('lastCheckedOut', $readingHistoryEntry->checkOutDate);
			}
		}
	}

	/**
	 * @param $recordDetails
	 * @return array
	 */
	private function getVolumeDataForRecord($recordId): array
	{
		require_once ROOT_DIR . '/sys/ILS/IlsVolumeInfo.php';
		$volumeData = array();
		$volumeDataDB = new IlsVolumeInfo();
		$volumeDataDB->recordId = $recordId;
		$volumeDataDB->orderBy('displayOrder ASC, displayLabel ASC');
		//D-81 show volume information even if there aren't related items
		//$volumeDataDB->whereAdd('length(relatedItems) > 0');
		if ($volumeDataDB->find()) {
			while ($volumeDataDB->fetch()) {
				$volumeData[] = clone($volumeDataDB);
			}
		}
		$volumeDataDB = null;
		unset($volumeDataDB);
		return $volumeData;
	}
}