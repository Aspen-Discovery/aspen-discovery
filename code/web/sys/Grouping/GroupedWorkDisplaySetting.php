<?php
require_once ROOT_DIR . '/sys/LibraryLocation/LibraryFacetSetting.php';
require_once ROOT_DIR . '/sys/Grouping/GroupedWorkFacetGroup.php';

/**
 * Class GroupedWorkDisplaySetting
 * Stores information about display settings for Grouped Work searches and full records
 * so they can be configured once and applied to different libraries and locations
 */
class GroupedWorkDisplaySetting extends DataObject
{
	public $__table = 'grouped_work_display_settings';
	public $id;
	public $name;

	//Processing search
	public $applyNumberOfHoldingsBoost;

	//Search Results Display
	public $showSearchTools;
	public $showQuickCopy;
	public $showInSearchResultsMainDetails;
	public $alwaysShowSearchResultsMainDetails;

	//Contents of search
	public $includeOutOfSystemExternalLinks;

	//Availability Toggles
	public $availabilityToggleLabelSuperScope;
	public $availabilityToggleLabelLocal;
	public $availabilityToggleLabelAvailable;
	public $availabilityToggleLabelAvailableOnline;
	public $baseAvailabilityToggleOnLocalHoldingsOnly;
	public $includeOnlineMaterialsInAvailableToggle;

	//Faceting
	public $includeAllRecordsInShelvingFacets;
	public $includeAllRecordsInDateAddedFacets;
	public $facetGroupId;

	//Enrichment
	public $showStandardReviews;
	public $showGoodReadsReviews;
	public $preferSyndeticsSummary;
	public $showSimilarTitles;
	public $showSimilarAuthors;
	public $showRatings; // User Ratings
	public $showComments; // User Reviews switch
	public $hideCommentsWithBadWords; //tinyint(4)

	//Full record display
	public $show856LinksAsTab;
	public $showCheckInGrid;
	public $showStaffView;
	public $showLCSubjects; // Library of Congress Subjects
	public $showBisacSubjects;
	public $showFastAddSubjects;
	public $showOtherSubjects;
	public $showInMainDetails;

	private $__moreDetailsOptions;

	// Use this to set which details will be shown in the the Main Details section of the record in the search results.
	// You should be able to add options here without needing to change the database.
	// set the key to the desired SMARTY template variable name, set the value to the label to show in the library configuration page
	static $searchResultsMainDetailsOptions = array(
		'showSeries'               => 'Show Series',
		'showPublisher'            => 'Publisher',
		'showPublicationDate'      => 'Publisher Date',
		'showEditions'             => 'Editions',
		'showPhysicalDescriptions' => 'Physical Descriptions',
		'showLanguages'            => 'Show Language',
		'showArInfo'               => 'Show Accelerated Reader Information',
		'showLexileInfo'           => 'Show Lexile Information',
		'showFountasPinnell'       => 'Show Fountas &amp; Pinnell Information  (This data must be present in MARC records)',
	);

	// Use this to set which details will be shown in the the Main Details section of the record view.
	// You should be able to add options here without needing to change the database.
	// set the key to the desired SMARTY template variable name, set the value to the label to show in the library configuration page
	static $showInMainDetailsOptions = array(
		'showSeries'               => 'Series',
		'showPublicationDetails'   => 'Published',
		'showFormats'              => 'Formats',
		'showEditions'             => 'Editions',
		'showPhysicalDescriptions' => 'Physical Descriptions',
		'showISBNs'                => 'ISBNs',
		'showArInfo'               => 'Show Accelerated Reader Information',
		'showLexileInfo'           => 'Show Lexile Information',
		'showFountasPinnell'       => 'Show Fountas &amp; Pinnell Information  (This data must be present in MARC records)',
	);

	static function getObjectStructure(){
		$facetGroups = [];
		$facetGroup = new GroupedWorkFacetGroup();
		$facetGroup->orderBy('name');
		$facetGroup->find();
		while ($facetGroup->fetch()){
			$facetGroups[$facetGroup->id] = $facetGroup->name;
		}

		$moreDetailsStructure = GroupedWorkMoreDetails::getObjectStructure();
		unset($moreDetailsStructure['weight']);
		unset($moreDetailsStructure['groupedWorkSettingsId']);

		$structure = [
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id within the database'),
			'name' => array('property' => 'name', 'type' => 'text', 'label' => 'Display Name', 'description' => 'The name of the settings', 'size' => '40', 'maxLength'=>255),

			'searchingSection' => ['property'=>'searchingSection', 'type' => 'section', 'label' =>'Searching', 'hideInLists' => true,
				'helpLink'=>'https://docs.google.com/document/d/1QQ7bNfGx75ImTguxEOmf7eCtdrVN9vi8FpWtWY_O3OU',
				'properties' => [
					'applyNumberOfHoldingsBoost'               => array('property' => 'applyNumberOfHoldingsBoost', 'type'=>'checkbox', 'label'=>'Apply Number Of Holdings Boost', 'description'=>'Whether or not the relevance will use boosting by number of holdings in the catalog.', 'hideInLists' => true, 'default' => 1),
					'includeOutOfSystemExternalLinks'          => array('property' => 'includeOutOfSystemExternalLinks', 'type'=>'checkbox', 'label'=>'Include Out Of System External Links', 'description'=>'Whether or not to include external links from other library systems.  Should only be enabled for global scope.', 'hideInLists' => true, 'default'=>0),
					'searchResultsSection' => array('property' => 'searchResultsSection', 'type' => 'section', 'label' => 'Search Results', 'hideInLists' => true, 'properties' => array(
						'showSearchTools'                        => array('property' => 'showSearchTools',                    'type' => 'checkbox',    'label' => 'Show Search Tools',                                          'description' => 'Turn on to activate search tools (save search, export to excel, rss feed, etc).', 'hideInLists' => true),
						'showQuickCopy'                          => array('property' => 'showQuickCopy',                      'type' => 'checkbox',    'label' => 'Show Quick Copy',                                            'description' => 'Turn on to to show Quick Copy Link in search results.', 'hideInLists' => true),
						'showInSearchResultsMainDetails'         => array('property' => 'showInSearchResultsMainDetails',     'type' => 'multiSelect', 'label' => 'Optional details to show for a record in search results : ', 'description' => 'Selected details will be shown in the main details section of a record on a search results page.', 'listStyle' => 'checkboxSimple', 'values' => self::$searchResultsMainDetailsOptions),
						'alwaysShowSearchResultsMainDetails'     => array('property' => 'alwaysShowSearchResultsMainDetails', 'type' => 'checkbox',    'label' => 'Always Show Selected Search Results Main Details',           'description' => 'Turn on to always show the selected details even when there is no info supplied for a detail, or the detail varies due to multiple formats and/or editions). Does not apply to Series & Language', 'hideInLists' => true),
					)),
					'searchFacetsSection' => array('property' => 'searchFacetsSection', 'type' => 'section', 'label' => 'Search Facets', 'hideInLists' => true, 'properties' => array(
						'availabilityToggleLabelSuperScope'        => array('property' => 'availabilityToggleLabelSuperScope',        'type' => 'text',     'label' => 'SuperScope Toggle Label',                                  'description' => 'The label to show when viewing super scope i.e. Consortium Name / Entire Collection / Everything.  Does not show if super scope is not enabled.', 'default' => 'Entire Collection'),
						'availabilityToggleLabelLocal'             => array('property' => 'availabilityToggleLabelLocal',             'type' => 'text',     'label' => 'Local Collection Toggle Label',                            'description' => 'The label to show when viewing the local collection i.e. Library Name / Local Collection.  Leave blank to hide the button.', 'default' => ''),
						'availabilityToggleLabelAvailable'         => array('property' => 'availabilityToggleLabelAvailable',         'type' => 'text',     'label' => 'Available Toggle Label',                                   'description' => 'The label to show when viewing available items i.e. Available Now / Available Locally / Available Here.', 'default' => 'Available Now'),
						'availabilityToggleLabelAvailableOnline'   => array('property' => 'availabilityToggleLabelAvailableOnline',   'type' => 'text', 'label' => 'Available Online Toggle Label', 'description' => 'The label to show when viewing available items i.e. Available Online.', 'default' => 'Available Online'),
						'includeOnlineMaterialsInAvailableToggle'  => array('property' => 'includeOnlineMaterialsInAvailableToggle',  'type'=>'checkbox', 'label'=>'Include Online Materials in Available Toggle', 'description'=>'Turn on to include online materials in both the Available Now and Available Online Toggles.', 'hideInLists' => true, 'default'=>false),
						'includeAllRecordsInShelvingFacets'        => array('property' => 'includeAllRecordsInShelvingFacets',        'type' => 'checkbox', 'label' => 'Include All Records In Shelving Facets',                   'description'=>'Turn on to include all records (owned and included) in shelving related facets (detailed location, collection).', 'hideInLists' => true, 'default'=>false),
						'includeAllRecordsInDateAddedFacets'       => array('property' => 'includeAllRecordsInDateAddedFacets',       'type' => 'checkbox', 'label' => 'Include All Records In Date Added Facets',                 'description'=>'Turn on to include all records (owned and included) in date added facets.', 'hideInLists' => true, 'default'=>false),
						'facetGroupId' => ['property' => 'facetGroupId', 'type'=>'enum', 'values' => $facetGroups, 'label' => 'Facet Group'],
					)),
				]
			],

			// Catalog Enrichment //
			'enrichmentSection' => ['property'=>'enrichmentSection', 'type' => 'section', 'label' =>'Catalog Enrichment', 'hideInLists' => true,
				'helpLink' => 'https://docs.google.com/document/d/1fJ2Sc62fTieJlPvaFz4XUoSr8blou_3MfxDGh1luI84', 'properties' => [
					'showStandardReviews'      => array('property'=>'showStandardReviews', 'type'=>'checkbox', 'label'=>'Show Standard Reviews', 'description'=>'Whether or not reviews from Content Cafe/Syndetics are displayed on the full record page.', 'hideInLists' => true, 'default' => 1),
					'showGoodReadsReviews'     => array('property'=>'showGoodReadsReviews', 'type'=>'checkbox', 'label'=>'Show GoodReads Reviews', 'description'=>'Whether or not reviews from GoodReads are displayed on the full record page.', 'hideInLists' => true, 'default'=>true),
					'preferSyndeticsSummary'   => array('property'=>'preferSyndeticsSummary', 'type'=>'checkbox', 'label'=>'Prefer Syndetics/Content Cafe Description', 'description'=>'Whether or not the Description loaded from an enrichment service should be preferred over the Description in the Marc Record.', 'hideInLists' => true, 'default' => 1),
					'showSimilarAuthors'       => array('property'=>'showSimilarAuthors', 'type'=>'checkbox', 'label'=>'Show Similar Authors', 'description'=>'Whether or not Similar Authors from Novelist is shown.', 'default' => 1, 'hideInLists' => true,),
					'showSimilarTitles'        => array('property'=>'showSimilarTitles', 'type'=>'checkbox', 'label'=>'Show Similar Titles', 'description'=>'Whether or not Similar Titles from Novelist is shown.', 'default' => 1, 'hideInLists' => true,),
					'showGoDeeper'             => array('property'=>'showGoDeeper', 'type'=>'checkbox', 'label'=>'Show Content Enrichment (TOC, Excerpts, etc)', 'description'=>'Whether or not additional content enrichment like Table of Contents, Excerpts, etc are shown to the user', 'default' => 1, 'hideInLists' => true,),
					'showRatings'              => array('property'=>'showRatings', 'type'=>'checkbox', 'label'=>'Enable User Ratings', 'description'=>'Whether or not ratings are shown', 'hideInLists' => true, 'default' => 1),
					'showComments'             => array('property'=>'showComments', 'type'=>'checkbox', 'label'=>'Enable User Reviews', 'description'=>'Whether or not user reviews are shown (also disables adding user reviews)', 'hideInLists' => true, 'default' => 1),
					'hideCommentsWithBadWords' => array('property'=>'hideCommentsWithBadWords', 'type'=>'checkbox', 'label'=>'Hide Comments with Bad Words', 'description'=>'If checked, any User Lists or User Reviews with bad words are completely removed from the user interface for everyone except the original poster.', 'hideInLists' => true,),
				]
			],

			// Full Record Display //
			'fullRecordSection' => array('property'=>'fullRecordSection', 'type' => 'section', 'label' =>'Full Record Display', 'hideInLists' => true,
				'helpLink'=>'https://docs.google.com/document/d/1ZZsoKW2NOfGMad36BkWeF5ROqH5Wyg5up3eIhki5Lec', 'properties' => array(
					'show856LinksAsTab'        => array('property'=>'show856LinksAsTab',        'type'=>'checkbox', 'label'=>'Show 856 Links in their own section',             'description'=>'Whether or not 856 links will be shown in their own tab or on the same tab as holdings.', 'hideInLists' => true, 'default' => 1),
					'showCheckInGrid'          => array('property'=>'showCheckInGrid',          'type'=>'checkbox', 'label'=>'Show Check-in Grid',                'description'=>'Whether or not the check-in grid is shown for periodicals.', 'default' => 1, 'hideInLists' => true,),
					'showStaffView'            => array('property'=>'showStaffView',            'type'=>'checkbox', 'label'=>'Show Staff View',                   'description'=>'Whether or not the staff view is displayed in full record view.', 'hideInLists' => true, 'default'=>true),
					'showLCSubjects'           => array('property'=>'showLCSubjects',           'type'=>'checkbox', 'label'=>'Show Library of Congress Subjects', 'description'=>'Whether or not standard (LC) subjects are displayed in full record view.', 'hideInLists' => true, 'default'=>true),
					'showBisacSubjects'        => array('property'=>'showBisacSubjects',        'type'=>'checkbox', 'label'=>'Show Bisac Subjects',               'description'=>'Whether or not Bisac subjects are displayed in full record view.', 'hideInLists' => true, 'default'=>true),
					'showFastAddSubjects'      => array('property'=>'showFastAddSubjects',      'type'=>'checkbox', 'label'=>'Show OCLC Fast Subjects',           'description'=>'Whether or not OCLC Fast Add subjects are displayed in full record view.', 'hideInLists' => true, 'default'=>true),
					'showOtherSubjects'        => array('property'=>'showOtherSubjects',        'type'=>'checkbox', 'label'=>'Show Other Subjects',               'description'=>'Whether or other subjects from the MARC are displayed in full record view.', 'hideInLists' => true, 'default'=>true),

					'showInMainDetails' => array('property' => 'showInMainDetails', 'type' => 'multiSelect', 'label'=>'Which details to show in the main/top details section : ', 'description'=> 'Selected details will be shown in the top/main section of the full record view. Details not selected are moved to the More Details accordion.',
						'listStyle' => 'checkboxSimple',
						'values' => self::$showInMainDetailsOptions,
					),
					'moreDetailsOptions' => array(
						'property' => 'moreDetailsOptions',
						'type' => 'oneToMany',
						'label' => 'Full Record Options',
						'description' => 'Record Options for the display of full record',
						'keyThis' => 'libraryId',
						'keyOther' => 'libraryId',
						'subObjectType' => 'GroupedWorkMoreDetails',
						'structure' => $moreDetailsStructure,
						'sortable' => true,
						'storeDb' => true,
						'allowEdit' => true,
						'canEdit' => false,
						'additionalOneToManyActions' => array(
							0 => array(
								'text' => 'Reset More Details To Default',
								'url' => '/Admin/Libraries?id=$id&amp;objectAction=resetMoreDetailsToDefault',
								'class' => 'btn-warning',
							)
						)
					),
				)),
		];

		global $configArray;
		$ils = $configArray['Catalog']['ils'];
		if ($ils != 'Millennium' && $ils != 'Sierra') {
			unset($structure['fullRecordSection']['properties']['showCheckInGrid']);
		}

		return $structure;
	}

	/**
	 * Override the fetch functionality to fetch related objects
	 *
	 * @see DB/DB_DataObject::fetch()
	 */
	public function fetch(){
		$return = parent::fetch();
		if ($return) {
			if (isset($this->showInSearchResultsMainDetails) && is_string($this->showInSearchResultsMainDetails) && !empty($this->showInSearchResultsMainDetails)) {
				// convert to array retrieving from database
				$this->showInSearchResultsMainDetails = unserialize($this->showInSearchResultsMainDetails);
				if (!$this->showInSearchResultsMainDetails) $this->showInSearchResultsMainDetails = array();
			}

			if (isset($this->showInMainDetails) && is_string($this->showInMainDetails) && !empty($this->showInMainDetails)) {
				// convert to array retrieving from database
				try{
					$this->showInMainDetails = unserialize($this->showInMainDetails);
					if (!$this->showInMainDetails) $this->showInMainDetails = array();
				}catch (Exception $e){
					global $logger;
					$logger->log("Error loading GroupedWorkDisplaySetting $this->id $e", Logger::LOG_DEBUG);
				}
			}elseif (empty($this->showInMainDetails)) {
				// when a value is not set, assume set to show all options, eg null = all
				$default = self::$showInMainDetailsOptions;
				// remove options below that aren't to be part of the default
				unset($default['showISBNs']);
				unset($default['showLexileInfo']);
				unset($default['showFountasPinnell']);
				$default = array_keys($default);
				$this->showInMainDetails = $default;
			}
		}
		return $return;
	}

	/**
	 * Override the update functionality to save related objects
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update()
	{
		if (isset($this->showInSearchResultsMainDetails) && is_array($this->showInSearchResultsMainDetails)) {
			// convert array to string before storing in database
			$this->showInSearchResultsMainDetails = serialize($this->showInSearchResultsMainDetails);
		}

		if (isset($this->showInMainDetails) && is_array($this->showInMainDetails)) {
			// convert array to string before storing in database
			$this->showInMainDetails = serialize($this->showInMainDetails);
		}

		$ret = parent::update();
		if ($ret !== FALSE ){
			$this->saveMoreDetailsOptions();
		}
		return $ret;
	}

	/**
	 * Override the insert functionality to save the related objects
	 *
	 * @see DB/DB_DataObject::insert()
	 */
	public function insert(){
		if (isset($this->showInSearchResultsMainDetails) && is_array($this->showInSearchResultsMainDetails)) {
			// convert array to string before storing in database
			$this->showInSearchResultsMainDetails = serialize($this->showInSearchResultsMainDetails);
		}
		if (isset($this->showInMainDetails) && is_array($this->showInMainDetails)) {
			// convert array to string before storing in database
			$this->showInMainDetails = serialize($this->showInMainDetails);
		}

		$ret = parent::insert();
		if ($ret !== FALSE ){
			$this->saveMoreDetailsOptions();
		}
		return $ret;
	}

	private $__facetGroup;
	/** @return GroupedWorkFacet[] */
	public function getFacets()
	{
		try {
			if ($this->__facetGroup == null) {
				$this->__facetGroup = new GroupedWorkFacetGroup();
				$this->__facetGroup->id = $this->facetGroupId;
				if (!$this->__facetGroup->find(true)) {
					$this->__facetGroup = null;
				}
			}
			return $this->__facetGroup->getFacets();
		}catch (Exception $e){
			return [];
		}
	}

	public function __get($name){
		if ($name == 'moreDetailsOptions'){
			return $this->getMoreDetailsOptions();
		}else{
			return $this->__data[$name];
		}
	}

	public function __set($name, $value){
		if ($name == 'moreDetailsOptions'){
			$this->setMoreDetailsOptions($value);
		}else{
			$this->__data[$name] = $value;
		}
	}
	public function getMoreDetailsOptions(){
		if (!isset($this->__moreDetailsOptions) && $this->libraryId){
			$this->__moreDetailsOptions = array();
			$moreDetailsOptions = new GroupedWorkMoreDetails();
			$moreDetailsOptions->groupedWorkSettingsId = $this->id;
			$moreDetailsOptions->orderBy('weight');
			$moreDetailsOptions->find();
			while($moreDetailsOptions->fetch()){
				$this->__moreDetailsOptions[$moreDetailsOptions->id] = clone($moreDetailsOptions);
			}
		}
		return $this->__moreDetailsOptions;
	}
	public function setMoreDetailsOptions($value){
		$this->__moreDetailsOptions = $value;
	}

	public function saveMoreDetailsOptions(){
		if (isset ($this->__moreDetailsOptions) && is_array($this->__moreDetailsOptions)){
			$this->saveOneToManyOptions($this->__moreDetailsOptions, 'groupedWorkSettingsId');
			unset($this->__moreDetailsOptions);
		}
	}

	public function clearMoreDetailsOptions(){
		$this->clearOneToManyOptions('GroupedWorkMoreDetails', 'groupedWorkSettingsId');
		/** @noinspection PhpUndefinedFieldInspection */
		$this->__moreDetailsOptions = array();
	}


}