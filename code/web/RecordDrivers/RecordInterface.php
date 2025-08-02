<?php

/**
 * Record Driver Interface
 *
 * This interface class is the definition of the required methods for
 * interacting with a particular metadata record format.
 */
abstract class RecordInterface {
	//Used when displaying the title as part of a list
	private $listNotes;
	private $listEntryId;
	private $listEntryWeight;

	/**
	 * Constructor.  We build the object using all the data retrieved
	 * from the (Solr) index.  Since we have to
	 * make a search call to find out which record driver to construct,
	 * we will already have this data available, so we might as well
	 * just pass it into the constructor.
	 *
	 * @param array|File_MARC_Record|string $recordData Data to construct the driver from
	 * @access  public
	 */
	public abstract function __construct($recordData);

	public abstract function getBookcoverUrl($size = 'small', $absolutePath = false);

	/**
	 * Get text that can be displayed to represent this record in
	 * breadcrumbs.
	 *
	 * @access  public
	 * @return  string              Breadcrumb text to represent this record.
	 */
	public function getBreadcrumb() {
		return $this->getTitle();
	}

	function getRecordUrl() {
		$recordId = $this->getUniqueID();

		return '/' . $this->getModule() . '/' . $recordId;
	}

	function getAbsoluteUrl() {
		global $configArray;
		$recordId = $this->getUniqueID();

		return $configArray['Site']['url'] . '/' . $this->getModule() . '/' . $recordId;
	}

	protected $_linkUrl = null;

	public function getLinkUrl($absolutePath = false) {
		if ($this->_linkUrl == null) {
			global $interface;
			$this->_linkUrl = $this->getRecordUrl();

			$extraParams = [];
			if ($interface != null && strlen($interface->get_template_vars('searchId')) > 0) {
				$extraParams[] = 'searchId=' . $interface->get_template_vars('searchId');
				$extraParams[] = 'recordIndex=' . $interface->get_template_vars('recordIndex');
				$extraParams[] = 'page=' . $interface->get_template_vars('page');
			}
			if ($interface != null && !empty($interface->get_template_vars('searchSource'))) {
				$extraParams[] = 'searchSource=' . $interface->get_template_vars('searchSource');
			}
			if ($interface != null && !empty($interface->get_template_vars('activeFormat'))) {
				$extraParams[] = 'activeFormat=' . $interface->get_template_vars('activeFormat');
			}

			if (count($extraParams) > 0) {
				$this->_linkUrl .= '?' . implode('&', $extraParams);
			}
		}

		if ($absolutePath) {
			global $configArray;
			return $configArray['Site']['url'] . $this->_linkUrl;
		} else {
			return $this->_linkUrl;
		}
	}

	public abstract function getModule(): string;


	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display the full record information on the Staff
	 * View tab of the record view page.
	 *
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public abstract function getStaffView();

	/**
	 * Get the full title of the record.
	 *
	 * @return  string
	 */
	public abstract function getTitle();

	public function getSortableTitle() {
		return $this->getTitle();
	}

	/**
	 * Return the unique identifier of this record within the Solr index;
	 * useful for retrieving additional information (like tags and user
	 * comments) from the external MySQL database.
	 *
	 * @access  public
	 * @return  string              Unique identifier.
	 */
	public abstract function getUniqueID();

	public abstract function getDescription();

	public abstract function getMoreDetailsOptions();

	public function getBaseMoreDetailsOptions($isbn) {
		global $interface;
		/** Library $library */
		global $library;
		global $timer;
		$hasSyndeticsUnbound = false;
		require_once ROOT_DIR . '/sys/Enrichment/SyndeticsSetting.php';
		$syndeticsSettings = new SyndeticsSetting();
		$syndeticsSettings->id = $library->syndeticsSettingId;
		if ($syndeticsSettings->find(true)) {
			if ($syndeticsSettings->syndeticsUnbound) {
				$interface->assign('unboundAccountNumber', $syndeticsSettings->unboundAccountNumber);
				$interface->assign('unboundInstanceNumber', $syndeticsSettings->unboundInstanceNumber);
				$hasSyndeticsUnbound = true;
			}
		}
		$interface->assign('hasSyndeticsUnbound');

		$hasNovelistAllInOne = false;
		if ($library->novelistSettingId > 0) {
			require_once ROOT_DIR . '/sys/Enrichment/NovelistSetting.php';
			$novelistSetting = new NovelistSetting();
			$novelistSetting->id = $library->novelistSettingId;
			if ($novelistSetting->find(true)) {
				$interface->assign('novelistProfile', $novelistSetting->profile);
				$interface->assign('novelistKey', $novelistSetting->pwd);
				$primaryISBN = $this->getCleanISBN();
				if (!empty($primaryISBN)) {
					$interface->assign('primaryISBN', $primaryISBN);
					$hasNovelistAllInOne = true;
				}
			}
		}


		$moreDetailsOptions = [];
		$moreDetailsOptions['description'] = [
			'label' => 'Description',
			'body' => '<div id="descriptionPlaceholder">' . translate([
					'text' => 'Loading Description...',
					'isPublicFacing' => true,
				]) . '</div>',
			'hideByDefault' => false,
			'openByDefault' => true,
		];
		$timer->logTime('Loaded Description');
		if (SystemVariables::getSystemVariables()->enableNovelistSeriesIntegration) {
			$moreDetailsOptions['series'] = [
				'label' => 'Also in this Series',
				'body' => $interface->fetch('GroupedWork/series.tpl'),
				'hideByDefault' => false,
				'openByDefault' => true,
			];
			$timer->logTime('Loaded Series Data');
		}

		$moreDetailsOptions['moreLikeThis'] = [
			'label' => 'More Like This',
			'body' => $interface->fetch('GroupedWork/moreLikeThis.tpl'),
			'hideByDefault' => false,
			'openByDefault' => true,
		];

		$timer->logTime('Loaded More Like This');
		if ($interface->getVariable('enableInnReachIntegration')) {
			$moreDetailsOptions['innReach'] = [
				'label' => 'More Copies In ' . $library->interLibraryLoanName,
				'body' => '<div id="inInnReachPlaceholder">' . translate(['text'=>"Loading $library->interLibraryLoanName Copies...", 'isPublicFacing'=> true]) . '</div>',
				'hideByDefault' => false,
			];
		}
		if ($interface->getVariable('enableShareItIntegration')) {
			$moreDetailsOptions['shareIt'] = [
				'label' => 'More Copies In ' . $library->interLibraryLoanName,
				'body' => '<div id="inShareItPlaceholder">' . translate(['text'=>"Loading $library->interLibraryLoanName Copies...", 'isPublicFacing'=> true]) . '</div>',
				'hideByDefault' => false,
			];
		}
		if ($hasNovelistAllInOne) {
			$moreDetailsOptions['novelist'] = [
				'label' => 'NoveList',
				'body' => $interface->fetch('GroupedWork/novelist.tpl'),
				'hideByDefault' => false,
			];
		}
		if ($hasSyndeticsUnbound) {
			$moreDetailsOptions['syndeticsUnbound'] = [
				'label' => 'Syndetics Unbound',
				'body' => $interface->fetch('GroupedWork/syndeticsUnbound.tpl'),
				'hideByDefault' => false,
			];
		}

		$moreDetailsOptions['tableOfContents'] = [
			'label' => 'Table of Contents',
			'body' => $interface->fetch('GroupedWork/tableOfContents.tpl'),
			'hideByDefault' => true,
		];
		$timer->logTime('Loaded Table of Contents');
		$moreDetailsOptions['excerpt'] = [
			'label' => 'Excerpt',
			'body' => '<div id="excerptPlaceholder">Loading Excerpt...</div>',
			'hideByDefault' => true,
		];
		$moreDetailsOptions['authornotes'] = [
			'label' => 'Author Notes',
			'body' => '<div id="authornotesPlaceholder">Loading Author Notes...</div>',
			'hideByDefault' => true,
		];
		if ($interface->getVariable('showComments')) {
			$moreDetailsOptions['borrowerReviews'] = [
				'label' => 'Borrower Reviews',
				'body' => "<div id='customerReviewPlaceholder'></div>",
			];
		}
		if ($isbn) {
			$moreDetailsOptions['syndicatedReviews'] = [
				'label' => 'Published Reviews',
				'body' => "<div id='syndicatedReviewPlaceholder'></div>",
			];
			if ($interface->getVariable('showGoodReadsReviews')) {
				$moreDetailsOptions['goodreadsReviews'] = [
					'label' => 'Reviews from GoodReads',
					'onShow' => "AspenDiscovery.GroupedWork.getGoodReadsComments('$isbn');",
					'body' => '<div id="goodReadsPlaceHolder">Loading GoodReads Reviews.</div>',
				];
			}

			if ($interface->getVariable('showSimilarTitles')) {
				$moreDetailsOptions['similarTitles'] = [
					'label' => 'Similar Titles From NoveList',
					'body' => '<div id="novelistTitlesPlaceholder"></div>',
					'hideByDefault' => true,
				];
			}
			if ($interface->getVariable('showSimilarAuthors')) {
				$moreDetailsOptions['similarAuthors'] = [
					'label' => 'Similar Authors From NoveList',
					'body' => '<div id="novelistAuthorsPlaceholder"></div>',
					'hideByDefault' => true,
				];
			}
			if ($interface->getVariable('showSimilarTitles')) {
				$moreDetailsOptions['similarSeries'] = [
					'label' => 'Similar Series From NoveList',
					'body' => '<div id="novelistSeriesPlaceholder"></div>',
					'hideByDefault' => true,
				];
			}
		}
		//Do the filtering and sorting here so subclasses can use this directly
		return $this->filterAndSortMoreDetailsOptions($moreDetailsOptions);
	}

	public function filterAndSortMoreDetailsOptions($allOptions) {
		global $library;
		global $locationSingleton;
		$activeLocation = $locationSingleton->getActiveLocation();

		$moreDetailsFilters = [];
		$useDefault = true;
		if ($library && count($library->getGroupedWorkDisplaySettings()->getMoreDetailsOptions()) > 0) {
			$useDefault = false;
			/** @var GroupedWorkMoreDetails $option */
			foreach ($library->getGroupedWorkDisplaySettings()->getMoreDetailsOptions() as $option) {
				$moreDetailsFilters[$option->source] = $option->collapseByDefault ? 'closed' : 'open';
			}
		}
		if ($activeLocation && count($activeLocation->getGroupedWorkDisplaySettings()->getMoreDetailsOptions()) > 0) {
			$useDefault = false;
			/** @var LocationMoreDetails $option */
			foreach ($activeLocation->getGroupedWorkDisplaySettings()->getMoreDetailsOptions() as $option) {
				$moreDetailsFilters[$option->source] = $option->collapseByDefault ? 'closed' : 'open';
			}
		}

		if ($useDefault) {
			$moreDetailsFilters = RecordInterface::getDefaultMoreDetailsOptions();
		}

		$filteredMoreDetailsOptions = [];
		foreach ($moreDetailsFilters as $option => $initialState) {
			if (array_key_exists($option, $allOptions)) {
				$detailOptions = $allOptions[$option];
				$detailOptions['openByDefault'] = $initialState == 'open';
				$filteredMoreDetailsOptions[$option] = $detailOptions;
			}
		}
		return $filteredMoreDetailsOptions;
	}

	public static function getValidMoreDetailsSources() {
		return [
			'description' => 'Description',
			'series' => 'Also in this Series',
			'formats' => 'Formats',
			'copies' => 'Copies',
			'parentRecords' => 'Contained By',
			'childRecords' => 'Contains',
			'continuesRecords' => 'Continues',
			'continuedByRecords' => 'Continued By',
			'marcHoldings' => 'Holdings',
			'links' => 'Links',
			'moreLikeThis' => 'More Like This',
			'otherEditions' => 'Other Editions and Formats',
			'innReach' => 'INN-Reach',
			'shareIt' => 'SHAREit',
			'tableOfContents' => 'Table of Contents  (MARC/Syndetics/ContentCafe)',
			'excerpt' => 'Excerpt (Syndetics/ContentCafe)',
			'authornotes' => 'Author Notes (Syndetics/ContentCafe)',
			'subjects' => 'Subjects',
			'moreDetails' => 'More Details',
			'syndeticsUnbound' => 'Syndetics Unbound',
			'novelist' => 'NoveList (All in One)',
			'similarSeries' => 'Similar Series From NoveList',
			'similarTitles' => 'Similar Titles From NoveList',
			'similarAuthors' => 'Similar Authors From NoveList',
			'borrowerReviews' => 'Borrower Reviews',
			'syndicatedReviews' => 'Syndicated Reviews (Syndetics/ContentCafe)',
			'goodreadsReviews' => 'GoodReads Reviews',
			'citations' => 'Citations',
			'copyDetails' => 'Copy Details (OverDrive)',
			'staff' => 'Staff View',
		];
	}

	public static function getDefaultMoreDetailsOptions() {
		return [
			'description' => 'open',
			'series' => 'open',
			'formats' => 'open',
			'copies' => 'open',
			'parentRecords' => 'open',
			'childRecords' => 'open',
			'continuesRecords' => 'open',
			'continuedByRecords' => 'open',
			'marcHoldings' => 'open',
			'moreLikeThis' => 'open',
			'syndeticsUnbound' => 'open',
			'otherEditions' => 'closed',
			'innReach' => 'closed',
			'shareIt' => 'closed',
			'links' => 'closed',
			'tableOfContents' => 'closed',
			'excerpt' => 'closed',
			'authornotes' => 'closed',
			'subjects' => 'closed',
			'moreDetails' => 'closed',
			'similarSeries' => 'closed',
			'similarTitles' => 'closed',
			'similarAuthors' => 'closed',
			'borrowerReviews' => 'closed',
			'syndicatedReviews' => 'closed',
			'goodreadsReviews' => 'closed',
			'citations' => 'closed',
			'copyDetails' => 'closed',
			'staff' => 'closed',
		];
	}

	public function getSpotlightResult(CollectionSpotlight $collectionSpotlight, string $index) {
		global $interface;
		$interface->assign('showRatings', $collectionSpotlight->showRatings);

		$interface->assign('key', $index);

		if ($collectionSpotlight->coverSize == 'small') {
			$imageUrl = $this->getBookcoverUrl('small');
		} else {
			$imageUrl = $this->getBookcoverUrl('medium');
		}

		$interface->assign('title', $this->getTitle());
		$interface->assign('author', $this->getPrimaryAuthor());
		$interface->assign('description', $this->getDescription());
		$interface->assign('shortId', $this->getId());
		$interface->assign('id', $this->getId());
		$interface->assign('titleURL', $this->getLinkUrl());
		$interface->assign('imageUrl', $imageUrl);

		if ($collectionSpotlight->showRatings) {
			$interface->assign('ratingData', null);
			$interface->assign('showNotInterested', false);
		}

		$result = [
			'title' => $this->getTitle(),
			'author' => $this->getPrimaryAuthor(),
		];
		if ($collectionSpotlight->style == 'text-list') {
			$result['formattedTextOnlyTitle'] = $interface->fetch('CollectionSpotlight/formattedTextOnlyTitle.tpl');
		} elseif ($collectionSpotlight->style == 'horizontal-carousel') {
			$result['formattedTitle'] = $interface->fetch('CollectionSpotlight/formattedHorizontalCarouselTitle.tpl');
		} else {
			$result['formattedTitle'] = $interface->fetch('CollectionSpotlight/formattedTitle.tpl');
		}

		return $result;
	}

	function setListNotes($listNotes) {
		$this->listNotes = $listNotes;
	}

	function getListNotes() {
		return $this->listNotes;
	}

	function setListEntryId($listEntryId) {
		$this->listEntryId = $listEntryId;
	}

	function getListEntryId() {
		return $this->listEntryId;
	}

	function setListEntryWeight($listEntryWeight) {
		$this->listEntryWeight = $listEntryWeight;
	}

	function getListEntryWeight() {
		return $this->listEntryWeight;
	}

	function getPrimaryISBN() {
		return null;
	}

	function getCleanISBN() {
		return null;
	}
}