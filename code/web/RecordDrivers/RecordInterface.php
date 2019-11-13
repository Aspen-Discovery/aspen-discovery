<?php

/**
 * Record Driver Interface
 *
 * This interface class is the definition of the required methods for
 * interacting with a particular metadata record format.
 */
abstract class RecordInterface {
	/**
	 * Constructor.  We build the object using all the data retrieved
	 * from the (Solr) index.  Since we have to
	 * make a search call to find out which record driver to construct,
	 * we will already have this data available, so we might as well
	 * just pass it into the constructor.
	 *
	 * @param   array|File_MARC_Record|string   $recordData     Data to construct the driver from
	 * @access  public
	 */
	public abstract function __construct($recordData);

	public abstract function getBookcoverUrl($size = 'small');

    /**
     * Get text that can be displayed to represent this record in
     * breadcrumbs.
     *
     * @access  public
     * @return  string              Breadcrumb text to represent this record.
     */
    public function getBreadcrumb()
    {
        return $this->getTitle();
    }

    function getRecordUrl(){
        $recordId = $this->getUniqueID();

        return '/' . $this->getModule() . '/' . $recordId;
    }

    function getAbsoluteUrl(){
        global $configArray;
        $recordId = $this->getUniqueID();

        return $configArray['Site']['url'] . '/' . $this->getModule() . '/' . $recordId;
    }

    public function getLinkUrl($absolutePath = false) {
        global $interface;
        if ($absolutePath) {
            $linkUrl = $this->getAbsoluteUrl();
        } else {
            $linkUrl = $this->getRecordUrl();
        }

        $extraParams = array();
        if ($interface != null && strlen($interface->get_template_vars('searchId')) > 0){
            $extraParams[] = 'searchId=' . $interface->get_template_vars('searchId');
            $extraParams[] = 'recordIndex=' . $interface->get_template_vars('recordIndex');
            $extraParams[] = 'page='  . $interface->get_template_vars('page');
            $extraParams[] = 'searchSource=' . $interface->get_template_vars('searchSource');
        }

        if (count($extraParams) > 0){
            $linkUrl .= '?' . implode('&', $extraParams);
        }
        return $linkUrl;
    }

	public abstract function getModule();



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

	public function getBaseMoreDetailsOptions($isbn){
		global $interface;
		global $configArray;
		global $timer;
		$moreDetailsOptions = array();
		$moreDetailsOptions['description'] = array(
				'label' => 'Description',
				'body' => '<div id="descriptionPlaceholder">Loading Description...</div>',
				'hideByDefault' => false,
				'openByDefault' => true
		);
		$timer->logTime('Loaded Description');
		$moreDetailsOptions['series'] = array(
				'label' => 'Also in this Series',
				'body' => $interface->fetch('GroupedWork/series.tpl'),
				'hideByDefault' => false,
				'openByDefault' => true
		);
		$timer->logTime('Loaded Series Data');
		if (!$configArray['Catalog']['showExploreMoreForFullRecords']) {
			$moreDetailsOptions['moreLikeThis'] = array(
					'label' => 'More Like This',
					'body' => $interface->fetch('GroupedWork/moreLikeThis.tpl'),
					'hideByDefault' => false,
					'openByDefault' => true
			);
		}
		$timer->logTime('Loaded More Like This');
		if ($interface->getVariable('enableProspectorIntegration')){
			$moreDetailsOptions['prospector'] = array(
					'label' => 'More Copies In Prospector',
					'body' => '<div id="inProspectorPlaceholder">Loading Prospector Copies...</div>',
					'hideByDefault' => false
			);
		}
		$moreDetailsOptions['tableOfContents'] = array(
				'label' => 'Table of Contents',
				'body'  => $interface->fetch('GroupedWork/tableOfContents.tpl'),
				'hideByDefault' => true
		);
		$timer->logTime('Loaded Table of Contents');
		$moreDetailsOptions['excerpt'] = array(
				'label' => 'Excerpt',
				'body'  => '<div id="excerptPlaceholder">Loading Excerpt...</div>',
				'hideByDefault' => true
		);
		$moreDetailsOptions['authornotes'] = array(
				'label' => 'Author Notes',
				'body'  =>  '<div id="authornotesPlaceholder">Loading Author Notes...</div>',
				'hideByDefault' => true,
		);
		if ($interface->getVariable('showComments')){
			$moreDetailsOptions['borrowerReviews'] = array(
					'label' => 'Borrower Reviews',
					'body' => "<div id='customerReviewPlaceholder'></div>",
			);
		}
		if ($isbn){
			$moreDetailsOptions['syndicatedReviews'] = array(
					'label' => 'Published Reviews',
					'body' => "<div id='syndicatedReviewPlaceholder'></div>",
			);
			if ($interface->getVariable('showGoodReadsReviews')){
				$moreDetailsOptions['goodreadsReviews'] = array(
						'label' => 'Reviews from GoodReads',
						'onShow' => "AspenDiscovery.GroupedWork.getGoodReadsComments('$isbn');",
						'body' => '<div id="goodReadsPlaceHolder">Loading GoodReads Reviews.</div>'
				);
			}
			if (!$configArray['Catalog']['showExploreMoreForFullRecords']) {
				if ($interface->getVariable('showSimilarTitles')) {
					$moreDetailsOptions['similarTitles'] = array(
							'label' => 'Similar Titles From NoveList',
							'body' => '<div id="novelistTitlesPlaceholder"></div>',
							'hideByDefault' => true
					);
				}
				if ($interface->getVariable('showSimilarAuthors')) {
					$moreDetailsOptions['similarAuthors'] = array(
							'label' => 'Similar Authors From NoveList',
							'body' => '<div id="novelistAuthorsPlaceholder"></div>',
							'hideByDefault' => true
					);
				}
				if ($interface->getVariable('showSimilarTitles')) {
					$moreDetailsOptions['similarSeries'] = array(
							'label' => 'Similar Series From NoveList',
							'body' => '<div id="novelistSeriesPlaceholder"></div>',
							'hideByDefault' => true
					);
				}
			}
		}
		//Do the filtering and sorting here so subclasses can use this directly
		return $this->filterAndSortMoreDetailsOptions($moreDetailsOptions);
	}

	public function filterAndSortMoreDetailsOptions($allOptions){
		global $library;
		global $locationSingleton;
		$activeLocation = $locationSingleton->getActiveLocation();

		$moreDetailsFilters = [];
		$useDefault = true;
		if ($library && count($library->moreDetailsOptions) > 0){
			$useDefault = false;
			/** @var LibraryMoreDetails $option */
			foreach ($library->moreDetailsOptions as $option){
				$moreDetailsFilters[$option->source] = $option->collapseByDefault ? 'closed' : 'open';
			}
		}
		/** @noinspection PhpUndefinedFieldInspection */
		if ($activeLocation && count($activeLocation->moreDetailsOptions) > 0){
			$useDefault = false;
			/** @noinspection PhpUndefinedFieldInspection */
			/** @var LocationMoreDetails $option */
			foreach ($activeLocation->moreDetailsOptions as $option){
				$moreDetailsFilters[$option->source] = $option->collapseByDefault ? 'closed' : 'open';
			}
		}

		if ($useDefault){
			$moreDetailsFilters = RecordInterface::getDefaultMoreDetailsOptions();
		}

		$filteredMoreDetailsOptions = array();
		foreach ($moreDetailsFilters as $option => $initialState){
			if (array_key_exists($option, $allOptions)){
				$detailOptions = $allOptions[$option];
				$detailOptions['openByDefault'] = $initialState == 'open';
				$filteredMoreDetailsOptions[$option] = $detailOptions;
			}
		}
		return $filteredMoreDetailsOptions;
	}

	public static function getValidMoreDetailsSources() {
		return array(
				'description' => 'Description',
				'series' => 'Also in this Series',
				'formats' => 'Formats',
				'copies' => 'Copies',
				'links' => 'Links',
				'moreLikeThis' => 'More Like This',
				'otherEditions' => 'Other Editions and Formats',
				'prospector' => 'Prospector',
				'tableOfContents' => 'Table of Contents  (MARC/Syndetics/ContentCafe)',
				'excerpt' => 'Excerpt (Syndetics/ContentCafe)',
				'authornotes' => 'Author Notes (Syndetics/ContentCafe)',
				'subjects' => 'Subjects',
				'moreDetails' => 'More Details',
				'similarSeries' => 'Similar Series From NoveList',
				'similarTitles' => 'Similar Titles From NoveList',
				'similarAuthors' => 'Similar Authors From NoveList',
				'borrowerReviews' => 'Borrower Reviews',
				'syndicatedReviews' => 'Syndicated Reviews (Syndetics/ContentCafe)',
				'goodreadsReviews' => 'GoodReads Reviews',
				'citations' => 'Citations',
				'copyDetails' => 'Copy Details (OverDrive)',
				'staff' => 'Staff View',
		);
	}

	public static function getDefaultMoreDetailsOptions() {
		return array(
				'description' => 'open',
				'series' => 'open',
				'formats' => 'open',
				'copies' => 'open',
				'moreLikeThis' => 'open',
				'otherEditions' => 'closed',
				'prospector' => 'closed',
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
		);
	}

	public abstract function getItemActions($itemInfo);

	public abstract function getRecordActions($isAvailable, $isHoldable, $isBookable, $relatedUrls = null);

}