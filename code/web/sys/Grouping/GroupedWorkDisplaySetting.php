<?php
require_once ROOT_DIR . '/sys/LibraryLocation/LibraryFacetSetting.php';
require_once ROOT_DIR . '/sys/Grouping/GroupedWorkFacetGroup.php';
require_once ROOT_DIR . '/sys/Grouping/GroupedWorkMoreDetails.php';

/**
 * Class GroupedWorkDisplaySetting
 * Stores information about display settings for Grouped Work searches and full records
 * so they can be configured once and applied to different libraries and locations
 */
class GroupedWorkDisplaySetting extends DataObject
{
	public $__table = 'grouped_work_display_settings';
	public $__displayNameColumn = 'name';
	public $id;
	public $name;
	public $isDefault;

	public $sortOwnedEditionsFirst;

	//Processing search
	public $applyNumberOfHoldingsBoost;

	//Search Results Display
	public $showSearchTools;
	public $showSearchToolsAtTop;
	public $showQuickCopy;
	public $showInSearchResultsMainDetails;
	public $alwaysShowSearchResultsMainDetails;
	public $alwaysFlagNewTitles;
	public $showRelatedRecordLabels;

	//Contents of search
	public $includeOutOfSystemExternalLinks;

	//Availability Toggles
	public $availabilityToggleLabelSuperScope;
	public $availabilityToggleLabelLocal;
	public $availabilityToggleLabelAvailable;
	public $availabilityToggleLabelAvailableOnline;
	public $defaultAvailabilityToggle;
	public $baseAvailabilityToggleOnLocalHoldingsOnly;
	public $includeOnlineMaterialsInAvailableToggle;

	//Faceting
	public $includeAllRecordsInShelvingFacets;
	public $includeAllRecordsInDateAddedFacets;
	public $facetCountsToShow;
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
	public $show856LinksAsAccessOnlineButtons;
	public $showCheckInGrid;
	public $showStaffView;
	public $showLCSubjects; // Library of Congress Subjects
	public $showBisacSubjects;
	public $showFastAddSubjects;
	public $showOtherSubjects;
	public $showInMainDetails;

	//Item details
	public $showItemDueDates;

	private $_moreDetailsOptions;

	// Use this to set which details will be shown in the the Main Details section of the record in the search results.
	// You should be able to add options here without needing to change the database.
	// set the key to the desired SMARTY template variable name, set the value to the label to show in the library configuration page
	static $searchResultsMainDetailsOptions = array(
		'showSeries' => 'Show Series',
		'showPublisher' => 'Publisher',
		'showPublicationDate' => 'Publisher Date',
		'showEditions' => 'Editions',
		'showPhysicalDescriptions' => 'Physical Descriptions',
		'showLanguages' => 'Show Language',
		'showArInfo' => 'Show Accelerated Reader Information',
		'showLexileInfo' => 'Show Lexile Information',
		'showFountasPinnell' => 'Show Fountas &amp; Pinnell Information  (This data must be present in MARC records)',
	);

	// Use this to set which details will be shown in the the Main Details section of the record view.
	// You should be able to add options here without needing to change the database.
	// set the key to the desired SMARTY template variable name, set the value to the label to show in the library configuration page
	static $showInMainDetailsOptions = array(
		'showSeries' => 'Series',
		'showPublicationDetails' => 'Published',
		'showFormats' => 'Formats',
		'showEditions' => 'Editions',
		'showPhysicalDescriptions' => 'Physical Descriptions',
		'showISBNs' => 'ISBNs / ISSNs',
		'showArInfo' => 'Show Accelerated Reader Information',
		'showLexileInfo' => 'Show Lexile Information',
		'showFountasPinnell' => 'Show Fountas &amp; Pinnell Information  (This data must be present in MARC records)',
	);

	private $_libraries;
	private $_locations;

	static function getObjectStructure(): array
	{
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Grouped Work Display Settings'));
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Grouped Work Display Settings'));

		$facetGroups = [];
		$facetGroup = new GroupedWorkFacetGroup();
		$facetGroup->orderBy('name');
		$facetGroup->find();
		while ($facetGroup->fetch()) {
			$facetGroups[$facetGroup->id] = $facetGroup->name;
		}

		$moreDetailsStructure = GroupedWorkMoreDetails::getObjectStructure();
		unset($moreDetailsStructure['weight']);
		unset($moreDetailsStructure['groupedWorkSettingsId']);

		$structure = [
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id within the database', 'uniqueProperty' => true),
			'name' => array('property' => 'name', 'type' => 'text', 'label' => 'Display Name', 'description' => 'The name of the settings', 'size' => '40', 'maxLength' => 255, 'uniqueProperty' => true),

			'sortOwnedEditionsFirst' => array('property' => 'sortOwnedEditionsFirst', 'type' => 'checkbox', 'label' => 'Sort Owned Editions First', 'description' => 'Sort owned editions first within editions list.', 'hideInLists' => true),
			'searchingSection' => ['property' => 'searchingSection', 'type' => 'section', 'label' => 'Searching', 'renderAsHeading' => true, 'hideInLists' => true,
				'helpLink' => '',
				'properties' => [
					'applyNumberOfHoldingsBoost' => array('property' => 'applyNumberOfHoldingsBoost', 'type' => 'checkbox', 'label' => 'Apply Number Of Holdings Boost', 'description' => 'Whether or not the relevance will use boosting by number of holdings in the catalog.', 'hideInLists' => true, 'default' => 1),
					'includeOutOfSystemExternalLinks' => array('property' => 'includeOutOfSystemExternalLinks', 'type' => 'checkbox', 'label' => 'Include Out Of System External Links', 'description' => 'Whether or not to include external links from other library systems.  Should only be enabled for global scope.', 'hideInLists' => true, 'expandByDefault' => true, 'default' => 0),
					'searchResultsSection' => array('property' => 'searchResultsSection', 'type' => 'section', 'label' => 'Search Results', 'hideInLists' => true, 'properties' => array(
						'showSearchTools' => array('property' => 'showSearchTools', 'type' => 'checkbox', 'label' => 'Show Search Tools', 'description' => 'Turn on to activate search tools (save search, export to excel, rss feed, etc).', 'hideInLists' => true),
						'showSearchToolsAtTop' => array('property'=>'showSearchToolsAtTop', 'type'=>'checkbox', 'label'=>'Show Search Tools at Top of Results', 'description'=>'Whether or not to move search tools to the top of the results page', 'hideInLists' => true),
						'showQuickCopy' => array('property' => 'showQuickCopy', 'type' => 'checkbox', 'label' => 'Show Quick Copy', 'description' => 'Turn on to to show Quick Copy Link in search results.', 'hideInLists' => true),
						'showInSearchResultsMainDetails' => array('property' => 'showInSearchResultsMainDetails', 'type' => 'multiSelect', 'label' => 'Optional details to show for a record in search results : ', 'description' => 'Selected details will be shown in the main details section of a record on a search results page.', 'listStyle' => 'checkboxSimple', 'values' => self::$searchResultsMainDetailsOptions),
						'alwaysShowSearchResultsMainDetails' => array('property' => 'alwaysShowSearchResultsMainDetails', 'type' => 'checkbox', 'label' => 'Always Show Selected Search Results Main Details', 'description' => 'Turn on to always show the selected details even when there is no info supplied for a detail, or the detail varies due to multiple formats and/or editions). Does not apply to Series & Language', 'hideInLists' => true),
						'alwaysFlagNewTitles' => array('property' => 'alwaysFlagNewTitles', 'type' => 'checkbox', 'label' => 'Always Flag New Titles', 'description' => 'Turn on to add a flag to any title that has been added to the catalog in the last week', 'hideInLists' => true),
						'showRelatedRecordLabels' => array('property' => 'showRelatedRecordLabels', 'type' => 'checkbox', 'label' => 'Show Related Record Labels', 'description' => 'Turn on to show labels next to information about the records that make up information about a record in a format', 'default' => true, 'hideInLists' => true),
					)),
					'searchFacetsSection' => array('property' => 'searchFacetsSection', 'type' => 'section', 'label' => 'Search Facets', 'hideInLists' => true, 'expandByDefault' => true, 'properties' => array(
						'availabilityToggleLabelSuperScope' => array('property' => 'availabilityToggleLabelSuperScope', 'type' => 'text', 'label' => 'Entire Collection Toggle Label', 'description' => 'The label to show when viewing super scope i.e. Consortium Name / Entire Collection / Everything.  Does not show if super scope is not enabled.', 'default' => 'Entire Collection'),
						'availabilityToggleLabelLocal' => array('property' => 'availabilityToggleLabelLocal', 'type' => 'text', 'label' => 'Local Collection Toggle Label', 'description' => 'The label to show when viewing the local collection i.e. Library Name / Local Collection.  Leave blank to hide the button.', 'default' => ''),
						'availabilityToggleLabelAvailable' => array('property' => 'availabilityToggleLabelAvailable', 'type' => 'text', 'label' => 'Available Toggle Label', 'description' => 'The label to show when viewing available items i.e. Available Now / Available Locally / Available Here.', 'default' => 'Available Now'),
						'availabilityToggleLabelAvailableOnline' => array('property' => 'availabilityToggleLabelAvailableOnline', 'type' => 'text', 'label' => 'Available Online Toggle Label', 'description' => 'The label to show when viewing available items i.e. Available Online.', 'default' => 'Available Online'),
						'defaultAvailabilityToggle' => array('property' => 'defaultAvailabilityToggle', 'type' => 'enum', 'values' => ['global' => 'Entire Collection', 'local' => 'Local Collection', 'available' => 'Available', 'available_online' => 'Available Online'], 'label' => 'Default Toggle', 'description' => 'The default toggle to apply if the user does not select one', 'default' => 'entire_scope'),
						'baseAvailabilityToggleOnLocalHoldingsOnly' => array('property' => 'baseAvailabilityToggleOnLocalHoldingsOnly', 'type' => 'checkbox', 'label' => 'Base Availability Toggle On Local Holdings Only', 'default' => false, 'forcesReindex' => true),
						'includeOnlineMaterialsInAvailableToggle' => array('property' => 'includeOnlineMaterialsInAvailableToggle', 'type' => 'checkbox', 'label' => 'Include Online Materials in Available Toggle', 'description' => 'Turn on to include online materials in both the Available Now and Available Online Toggles.', 'hideInLists' => true, 'default' => false, 'forcesReindex' => true),
						'includeAllRecordsInShelvingFacets' => array('property' => 'includeAllRecordsInShelvingFacets', 'type' => 'checkbox', 'label' => 'Include All Records In Shelving Facets', 'description' => 'Turn on to include all records (owned and included) in shelving related facets (detailed location, collection).', 'hideInLists' => true, 'default' => false, 'forcesReindex' => true),
						'includeAllRecordsInDateAddedFacets' => array('property' => 'includeAllRecordsInDateAddedFacets', 'type' => 'checkbox', 'label' => 'Include All Records In Date Added Facets', 'description' => 'Turn on to include all records (owned and included) in date added facets.', 'hideInLists' => true, 'default' => false, 'forcesReindex' => true),
						'facetCountsToShow' => array('property' => 'facetCountsToShow', 'type' => 'enum', 'values' => ['1' => 'Show all counts (exact and approximate)', '2' => 'Show exact counts only', '3' => 'Show no counts'], 'label' => 'Facet Counts To Show', 'description' => 'The counts to show for facets'),
						'facetGroupId' => ['property' => 'facetGroupId', 'type' => 'enum', 'values' => $facetGroups, 'label' => 'Facet Group'],
					)),
				]
			],

			// Catalog Enrichment //
			'enrichmentSection' => ['property' => 'enrichmentSection', 'type' => 'section', 'label' => 'Catalog Enrichment', 'renderAsHeading' => true, 'hideInLists' => true, 'properties' => [
				'showStandardReviews' => array('property' => 'showStandardReviews', 'type' => 'checkbox', 'label' => 'Show Standard Reviews', 'description' => 'Whether or not reviews from Content Cafe/Syndetics are displayed on the full record page.', 'hideInLists' => true, 'default' => 1),
				'showGoodReadsReviews' => array('property' => 'showGoodReadsReviews', 'type' => 'checkbox', 'label' => 'Show GoodReads Reviews', 'description' => 'Whether or not reviews from GoodReads are displayed on the full record page.', 'hideInLists' => true, 'default' => true),
				'preferSyndeticsSummary' => array('property' => 'preferSyndeticsSummary', 'type' => 'checkbox', 'label' => 'Prefer Syndetics/Content Cafe Description', 'description' => 'Whether or not the Description loaded from an enrichment service should be preferred over the Description in the Marc Record.', 'hideInLists' => true, 'default' => 1),
				'showSimilarAuthors' => array('property' => 'showSimilarAuthors', 'type' => 'checkbox', 'label' => 'Show Similar Authors', 'description' => 'Whether or not Similar Authors from Novelist is shown.', 'default' => 1, 'hideInLists' => true,),
				'showSimilarTitles' => array('property' => 'showSimilarTitles', 'type' => 'checkbox', 'label' => 'Show Similar Titles', 'description' => 'Whether or not Similar Titles from Novelist is shown.', 'default' => 1, 'hideInLists' => true,),
				//'showGoDeeper'             => array('property'=>'showGoDeeper', 'type'=>'checkbox', 'label'=>'Show Content Enrichment (TOC, Excerpts, etc)', 'description'=>'Whether or not additional content enrichment like Table of Contents, Excerpts, etc are shown to the user', 'default' => 1, 'hideInLists' => true,),
				'showRatings' => array('property' => 'showRatings', 'type' => 'checkbox', 'label' => 'Enable User Ratings', 'description' => 'Whether or not ratings are shown', 'hideInLists' => true, 'default' => 1),
				'showComments' => array('property' => 'showComments', 'type' => 'checkbox', 'label' => 'Enable User Reviews', 'description' => 'Whether or not user reviews are shown (also disables adding user reviews)', 'hideInLists' => true, 'default' => 1),
				'hideCommentsWithBadWords' => array('property' => 'hideCommentsWithBadWords', 'type' => 'checkbox', 'label' => 'Hide User Content with Bad Words', 'description' => 'If checked, any User Lists or User Reviews with bad words are completely removed from the user interface for everyone except the original poster.', 'hideInLists' => true,),
			]
			],

			// Full Record Display //
			'fullRecordSection' => array('property' => 'fullRecordSection', 'type' => 'section', 'label' => 'Full Record Display', 'renderAsHeading' => true, 'hideInLists' => true,
				'helpLink' => '', 'properties' => array(
					'show856LinksAsTab' => array('property' => 'show856LinksAsTab', 'type' => 'checkbox', 'label' => 'Show 856 Links in their own section', 'description' => 'Whether or not 856 links will be shown in their own tab or on the same tab as holdings.', 'hideInLists' => true, 'default' => 1),
					'show856LinksAsAccessOnlineButtons' => array('property' => 'show856LinksAsAccessOnlineButtons', 'type' => 'checkbox', 'label' => 'Show 856 Links as Access Online Buttons', 'description' => 'Whether or not 856 links with indicator 1 of 4 and indicator 2 of 0 will be shown as access online buttons.', 'hideInLists' => true, 'default' => 0),
					'showCheckInGrid' => array('property' => 'showCheckInGrid', 'type' => 'checkbox', 'label' => 'Show Check-in Grid', 'description' => 'Whether or not the check-in grid is shown for periodicals.', 'default' => 1, 'hideInLists' => true,),
					'showStaffView' => array('property' => 'showStaffView', 'type' => 'enum', 'values' => [0 => 'Do not show', 1 => 'Show for all users', 2 => 'Show for staff only'], 'label' => 'Show Staff View', 'description' => 'Whether or not the staff view is displayed in full record view.', 'hideInLists' => true, 'default' => 1),
					'showLCSubjects' => array('property' => 'showLCSubjects', 'type' => 'checkbox', 'label' => 'Show Library of Congress Subjects', 'description' => 'Whether or not standard (LC) subjects are displayed in full record view.', 'hideInLists' => true, 'default' => true),
					'showBisacSubjects' => array('property' => 'showBisacSubjects', 'type' => 'checkbox', 'label' => 'Show Bisac Subjects', 'description' => 'Whether or not Bisac subjects are displayed in full record view.', 'hideInLists' => true, 'default' => true),
					'showFastAddSubjects' => array('property' => 'showFastAddSubjects', 'type' => 'checkbox', 'label' => 'Show OCLC Fast Subjects', 'description' => 'Whether or not OCLC Fast Add subjects are displayed in full record view.', 'hideInLists' => true, 'default' => true),
					'showOtherSubjects' => array('property' => 'showOtherSubjects', 'type' => 'checkbox', 'label' => 'Show Other Subjects', 'description' => 'Whether or not other subjects from the MARC are displayed in full record view.', 'hideInLists' => true, 'default' => true),
					'showItemDueDates' => array('property' => 'showItemDueDates', 'type' => 'checkbox', 'label' => 'Show Item Due Dates', 'description' => 'Whether or not due dates for items are shown within the copy details.', 'hideInLists' => true, 'default' => true),
					'showInMainDetails' => array('property' => 'showInMainDetails', 'type' => 'multiSelect', 'label' => 'Which details to show in the main/top details section : ', 'description' => 'Selected details will be shown in the top/main section of the full record view. Details not selected are moved to the More Details accordion.',
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
								'url' => '/Admin/GroupedWorkDisplay?id=$id&amp;objectAction=resetMoreDetailsToDefault',
								'class' => 'btn-warning',
							)
						)
					),
				)
			),

			'libraries' => array(
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use this browse category group',
				'values' => $libraryList,
			),

			'locations' => array(
				'property' => 'locations',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Locations',
				'description' => 'Define locations that use this browse category group',
				'values' => $locationList,
			),
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
	public function fetch()
	{
		$return = parent::fetch();
		if ($return) {
			if (isset($this->showInSearchResultsMainDetails) && is_string($this->showInSearchResultsMainDetails) && !empty($this->showInSearchResultsMainDetails)) {
				// convert to array retrieving from database
				$unSerialized = unserialize($this->showInSearchResultsMainDetails);
				$this->showInSearchResultsMainDetails = array_combine($unSerialized, $unSerialized);
				if (!$this->showInSearchResultsMainDetails) $this->showInSearchResultsMainDetails = array();
			}

			if (isset($this->showInMainDetails) && is_string($this->showInMainDetails) && !empty($this->showInMainDetails)) {
				// convert to array retrieving from database
				try {
					$unSerialized = unserialize($this->showInMainDetails);
					$this->showInMainDetails = array_combine($unSerialized, $unSerialized);
					if (!$this->showInMainDetails) $this->showInMainDetails = array();
				} catch (Exception $e) {
					global $logger;
					$logger->log("Error loading GroupedWorkDisplaySetting $this->id $e", Logger::LOG_DEBUG);
				}
			} elseif (empty($this->showInMainDetails)) {
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
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
			$this->saveMoreDetailsOptions();
		}
		return $ret;
	}

	/**
	 * Override the insert functionality to save the related objects
	 *
	 * @see DB/DB_DataObject::insert()
	 */
	public function insert()
	{
		if (isset($this->showInSearchResultsMainDetails) && is_array($this->showInSearchResultsMainDetails)) {
			// convert array to string before storing in database
			$this->showInSearchResultsMainDetails = serialize($this->showInSearchResultsMainDetails);
		}
		if (isset($this->showInMainDetails) && is_array($this->showInMainDetails)) {
			// convert array to string before storing in database
			$this->showInMainDetails = serialize($this->showInMainDetails);
		}

		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
			$this->saveMoreDetailsOptions();
		}
		return $ret;
	}

	private $_facetGroup = false;

	/** @return GroupedWorkFacet[] */
	public function getFacets()
	{
		try {
			return $this->getFacetGroup()->getFacets();
		} catch (Exception $e) {
			return [];
		}
	}

	public function getFacetGroup(): ?GroupedWorkFacetGroup
	{
		try {
			if ($this->_facetGroup === false) {
				$this->_facetGroup = new GroupedWorkFacetGroup();
				$this->_facetGroup->id = $this->facetGroupId;
				if (!$this->_facetGroup->find(true)) {
					$this->_facetGroup = null;
				}
			}
			return $this->_facetGroup;
		} catch (Exception $e) {
			return null;
		}
	}

	public function __get($name)
	{
		if ($name == "libraries") {
			if (!isset($this->_libraries) && $this->id) {
				$this->_libraries = [];
				$obj = new Library();
				$obj->groupedWorkDisplaySettingId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_libraries[$obj->libraryId] = $obj->libraryId;
				}
			}
			return $this->_libraries;
		} elseif ($name == "locations") {
			if (!isset($this->_locations) && $this->id) {
				$this->_locations = [];
				$obj = new Location();
				$obj->groupedWorkDisplaySettingId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_locations[$obj->locationId] = $obj->locationId;
				}
			}
			return $this->_locations;
		} elseif ($name == 'moreDetailsOptions') {
			return $this->getMoreDetailsOptions();
		} else {
			return $this->_data[$name];
		}
	}

	public function __set($name, $value)
	{
		if ($name == "libraries") {
			$this->_libraries = $value;
		} elseif ($name == "locations") {
			$this->_locations = $value;
		} elseif ($name == 'moreDetailsOptions') {
			$this->setMoreDetailsOptions($value);
		} else {
			$this->_data[$name] = $value;
		}
	}

	public function getMoreDetailsOptions()
	{
		if (!isset($this->_moreDetailsOptions) && $this->id) {
			$this->_moreDetailsOptions = array();
			$moreDetailsOptions = new GroupedWorkMoreDetails();
			$moreDetailsOptions->groupedWorkSettingsId = $this->id;
			$moreDetailsOptions->orderBy('weight');
			$moreDetailsOptions->find();
			while ($moreDetailsOptions->fetch()) {
				$this->_moreDetailsOptions[$moreDetailsOptions->id] = clone($moreDetailsOptions);
			}
		}
		return $this->_moreDetailsOptions;
	}

	public function setMoreDetailsOptions($value)
	{
		$this->_moreDetailsOptions = $value;
	}

	public function saveMoreDetailsOptions()
	{
		if (isset ($this->_moreDetailsOptions) && is_array($this->_moreDetailsOptions)) {
			$this->saveOneToManyOptions($this->_moreDetailsOptions, 'groupedWorkSettingsId');
			unset($this->_moreDetailsOptions);
		}
	}

	public function clearMoreDetailsOptions()
	{
		$this->clearOneToManyOptions('GroupedWorkMoreDetails', 'groupedWorkSettingsId');
		$this->_moreDetailsOptions = array();
	}

	public static function getDefaultDisplaySettings()
	{
		$defaultDisplaySettings = new GroupedWorkDisplaySetting();
		$defaultDisplaySettings->name = 'default';
		$defaultDisplaySettings->applyNumberOfHoldingsBoost = true;
		$defaultDisplaySettings->includeOutOfSystemExternalLinks = false;
		$defaultDisplaySettings->showSearchTools = true;
		$defaultDisplaySettings->showQuickCopy = true;
		$defaultDisplaySettings->alwaysShowSearchResultsMainDetails = false;
		$defaultDisplaySettings->availabilityToggleLabelSuperScope = 'Entire Collection';
		$defaultDisplaySettings->availabilityToggleLabelLocal = '';
		$defaultDisplaySettings->availabilityToggleLabelAvailable = 'Available Now';
		$defaultDisplaySettings->availabilityToggleLabelAvailableOnline = 'Available Online';
		return $defaultDisplaySettings;
	}

	public function saveLibraries()
	{
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Grouped Work Display Settings'));
			foreach ($libraryList as $libraryId => $displayName) {
				$library = new Library();
				$library->libraryId = $libraryId;
				$library->find(true);
				if (in_array($libraryId, $this->_libraries)) {
					//We want to apply the scope to this library
					if ($library->groupedWorkDisplaySettingId != $this->id) {
						$library->groupedWorkDisplaySettingId = $this->id;
						$library->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->groupedWorkDisplaySettingId == $this->id) {
						$library->groupedWorkDisplaySettingId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}

	public function saveLocations()
	{
		if (isset ($this->_locations) && is_array($this->_locations)) {
			$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Grouped Work Display Settings'));
			/**
			 * @var int $locationId
			 * @var Location $location
			 */
			foreach ($locationList as $locationId => $displayName) {
				$location = new Location();
				$location->locationId = $locationId;
				$location->find(true);
				if (in_array($locationId, $this->_locations)) {
					//We want to apply the scope to this library
					if ($location->groupedWorkDisplaySettingId != $this->id) {
						$location->groupedWorkDisplaySettingId = $this->id;
						$location->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($location->groupedWorkDisplaySettingId == $this->id) {
						$library = new Library();
						$library->libraryId = $location->libraryId;
						$library->find(true);
						$location->groupedWorkDisplaySettingId = -1;
						$location->update();
					}
				}
			}
			unset($this->_locations);
		}
	}

	/** @return Library[]
	 * @noinspection PhpUnused
	 */
	public function getLibraries()
	{
		return $this->_libraries;
	}

	/** @return Location[]
	 * @noinspection PhpUnused
	 */
	public function getLocations()
	{
		return $this->_locations;
	}

	/** @noinspection PhpUnused */
	public function setLibraries($val)
	{
		$this->_libraries = $val;
	}

	/** @noinspection PhpUnused */
	public function setLocations($val)
	{
		$this->_libraries = $val;
	}

	/** @noinspection PhpUnused */
	public function clearLibraries()
	{
		$this->clearOneToManyOptions('Library', 'groupedWorkDisplaySettingId');
		unset($this->_libraries);
	}

	/** @noinspection PhpUnused */
	public function clearLocations()
	{
		$this->clearOneToManyOptions('Location', 'groupedWorkDisplaySettingId');
		unset($this->_locations);
	}

	function getAdditionalListJavascriptActions() : array{
		$objectActions[] = array(
			'text' => 'Copy',
			'onClick' => "return AspenDiscovery.Admin.showCopyDisplaySettingsForm('$this->id')",
			'icon' => 'fas fa-copy'
		);

		return $objectActions;
	}
}