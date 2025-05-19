<?php
/** @noinspection PhpMissingFieldTypeInspection - can't do field types for DataObjects because we access fields before initialization */
require_once ROOT_DIR . '/sys/LibraryLocation/LibraryFacetSetting.php';
require_once ROOT_DIR . '/sys/Grouping/GroupedWorkFacetGroup.php';
require_once ROOT_DIR . '/sys/Grouping/GroupedWorkMoreDetails.php';
require_once ROOT_DIR . '/sys/Grouping/GroupedWorkFormatSortingGroup.php';

/**
 * Class GroupedWorkDisplaySetting
 * Stores information about display settings for Grouped Work searches and full records,
 * so they can be configured once and applied to different libraries and locations
 */
class GroupedWorkDisplaySetting extends DataObject {
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
	public $showEditionCovers;

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
	public $formatSortingGroupId;

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
//	public $show856LinksAsAccessOnlineButtons;
	public $showCheckInGrid;
	public $showStaffView;
	public $showLCSubjects; // Library of Congress Subjects
	public $showBisacSubjects;
	public $showFastAddSubjects;
	public $showOtherSubjects;
	public $showInMainDetails;
	public $preferIlsDescription;

	//search options
	public $searchSpecVersion;
	public $limitBoosts;
	public $maxTotalBoost;
	public $maxPopularityBoost;
	public $maxFormatBoost;
	public $maxHoldingsBoost;

	//Item details
	public $showItemDueDates;
	public $showItemNotes;

	private $_moreDetailsOptions;

	// Use this to set which details will be shown in the Main Details section of the record in the search results.
	// You should be able to add options here without needing to change the database.
	// Set the key to the desired SMARTY template variable name, set the value to the label to show in the library configuration page
	static $searchResultsMainDetailsOptions = [
		'showSeries' => 'Show Series',
		'showPublisher' => 'Publisher',
		'showPublicationDate' => 'Publisher Date',
		'showPlaceOfPublication' => 'Place of Publication',
		'showEditions' => 'Editions',
		'showPhysicalDescriptions' => 'Physical Descriptions',
		'showLanguages' => 'Show Language',
		'showArInfo' => 'Show Accelerated Reader Information',
		'showLexileInfo' => 'Show Lexile Information',
		'showFountasPinnell' => 'Show Fountas &amp; Pinnell Information  (This data must be present in MARC records)',
		'showAudience' => 'Audience',
	];

	// Use this to set which details will be shown in the Main Details section of the record view.
	// You should be able to add options here without needing to change the database.
	// Set the key to the desired SMARTY template variable name, set the value to the label to show in the library configuration page
	static $showInMainDetailsOptions = [
		'showSeries' => 'Series',
		'showPublicationDetails' => 'Published',
		'showFormats' => 'Formats',
		'showEditions' => 'Editions',
		'showPhysicalDescriptions' => 'Physical Descriptions',
		'showISBNs' => 'ISBNs / ISSNs',
		'showArInfo' => 'Show Accelerated Reader Information',
		'showLexileInfo' => 'Show Lexile Information',
		'showFountasPinnell' => 'Show Fountas &amp; Pinnell Information  (This data must be present in MARC records)',
		'showAudience' => 'Audience',
	];

	private $_libraries;
	private $_locations;

	static function getObjectStructure($context = ''): array {
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Grouped Work Display Settings'));
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Grouped Work Display Settings'));

		$facetGroups = [];
		$facetGroup = new GroupedWorkFacetGroup();
		$facetGroup->orderBy('name');
		$facetGroup->find();
		while ($facetGroup->fetch()) {
			$facetGroups[$facetGroup->id] = $facetGroup->name;
		}

		$formatSortGroups = [];
		$formatSort = new GroupedWorkFormatSortingGroup();
		$formatSort->orderBy('name');
		$formatSort->find();
		while ($formatSort->fetch()) {
			$formatSortGroups[$formatSort->id] = $formatSort->name;
		}

		$moreDetailsStructure = GroupedWorkMoreDetails::getObjectStructure($context);
		unset($moreDetailsStructure['weight']);
		unset($moreDetailsStructure['groupedWorkSettingsId']);

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
				'uniqueProperty' => true,
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Display Name',
				'description' => 'The name of the settings',
				'size' => '40',
				'maxLength' => 255,
				'uniqueProperty' => true,
			],

			'sortOwnedEditionsFirst' => [
				'property' => 'sortOwnedEditionsFirst',
				'type' => 'checkbox',
				'label' => 'Sort Owned Editions First',
				'description' => 'Sort owned editions first within editions list.',
				'hideInLists' => true,
			],
			'searchingSection' => [
				'property' => 'searchingSection',
				'type' => 'section',
				'label' => 'Searching',
				'renderAsHeading' => true,
				'hideInLists' => true,
				'helpLink' => '',
				'properties' => [
					'applyNumberOfHoldingsBoost' => [
						'property' => 'applyNumberOfHoldingsBoost',
						'type' => 'checkbox',
						'label' => 'Apply Number Of Holdings Boost',
						'description' => 'Whether or not the relevance will use boosting by number of holdings in the catalog.',
						'hideInLists' => true,
						'default' => 1,
					],
					'includeOutOfSystemExternalLinks' => [
						'property' => 'includeOutOfSystemExternalLinks',
						'type' => 'checkbox',
						'label' => 'Include Out Of System External Links',
						'description' => 'Whether or not to include external links from other library systems.  Should only be enabled for global scope.',
						'hideInLists' => true,
						'expandByDefault' => true,
						'default' => 0,
					],
					'searchResultsSection' => [
						'property' => 'searchResultsSection',
						'type' => 'section',
						'label' => 'Search Results',
						'hideInLists' => true,
						'properties' => [
							'showSearchTools' => [
								'property' => 'showSearchTools',
								'type' => 'checkbox',
								'label' => 'Enable Search Tools',
								'description' => 'Turn on to activate search tools (save search, export to excel, rss feed, etc).',
								'onchange' => 'return AspenDiscovery.Admin.updateGroupedWorkDisplayFields();',
								'hideInLists' => true,
							],
							'showSearchToolsAtTop' => [
								'property' => 'showSearchToolsAtTop',
								'type' => 'checkbox',
								'label' => 'Show Search Tools at Top of Results',
								'description' => 'Whether or not to move search tools to the top of the results page',
								'hideInLists' => true,
							],
							'showQuickCopy' => [
								'property' => 'showQuickCopy',
								'type' => 'enum',
								'values' => [
									2 => 'Show first 3 available copies & Where Is It link always',
									1 => 'Show first 3 available copies  & Where Is It link only if there additional copies',
									0 => 'Show first 3 available copies only',
									3 => 'Show Where Is It link only',
								],
								'label' => 'Copy Information to show',
								'description' => 'What to show for copy summary and in the Where Is It link.',
								'hideInLists' => true,
							],
							'showInSearchResultsMainDetails' => [
								'property' => 'showInSearchResultsMainDetails',
								'type' => 'multiSelect',
								'label' => 'Optional details to show for a record in search results : ',
								'description' => 'Selected details will be shown in the main details section of a record on a search results page.',
								'listStyle' => 'checkboxSimple',
								'values' => self::$searchResultsMainDetailsOptions,
							],
							'alwaysShowSearchResultsMainDetails' => [
								'property' => 'alwaysShowSearchResultsMainDetails',
								'type' => 'checkbox',
								'label' => 'Always Show Selected Search Results Main Details',
								'description' => 'Turn on to always show the selected details even when there is no info supplied for a detail, or the detail varies due to multiple formats and/or editions). Does not apply to Series & Language',
								'hideInLists' => true,
							],
							'alwaysFlagNewTitles' => [
								'property' => 'alwaysFlagNewTitles',
								'type' => 'checkbox',
								'label' => 'Always Flag New Titles',
								'description' => 'Turn on to add a flag to any title that has been added to the catalog in the last week',
								'hideInLists' => true,
							],
							'showRelatedRecordLabels' => [
								'property' => 'showRelatedRecordLabels',
								'type' => 'checkbox',
								'label' => 'Show Related Record Labels',
								'description' => 'Turn on to show labels next to edition information in grouped works. Ex: Published, Physical Description, etc',
								'default' => true,
								'hideInLists' => true,
							],
							'showEditionCovers' => [
								'property' => 'showEditionCovers',
								'type' => 'checkbox',
								'label' => 'Show Covers for Editions',
								'description' => 'Turn on to show individual covers for each edition',
								'default' => true,
								'hideInLists' => true,
							],
						],
					],
					'searchFacetsSection' => [
						'property' => 'searchFacetsSection',
						'type' => 'section',
						'label' => 'Search Facets',
						'hideInLists' => true,
						'expandByDefault' => true,
						'properties' => [
							'availabilityToggleLabelSuperScope' => [
								'property' => 'availabilityToggleLabelSuperScope',
								'type' => 'text',
								'label' => 'Entire Collection Toggle Label',
								'description' => 'The label to show when viewing super scope i.e. Consortium Name / Entire Collection / Everything.  Does not show if super scope is not enabled.',
								'default' => 'Entire Collection',
							],
							'availabilityToggleLabelLocal' => [
								'property' => 'availabilityToggleLabelLocal',
								'type' => 'text',
								'label' => 'Local Collection Toggle Label',
								'description' => 'The label to show when viewing the local collection i.e. Library Name / Local Collection.  Leave blank to hide the button.',
								'default' => '',
							],
							'availabilityToggleLabelAvailable' => [
								'property' => 'availabilityToggleLabelAvailable',
								'type' => 'text',
								'label' => 'Available Toggle Label',
								'description' => 'The label to show when viewing available items i.e. Available Now / Available Locally / Available Here.',
								'default' => 'Available Now',
							],
							'availabilityToggleLabelAvailableOnline' => [
								'property' => 'availabilityToggleLabelAvailableOnline',
								'type' => 'text',
								'label' => 'Available Online Toggle Label',
								'description' => 'The label to show when viewing available items i.e. Available Online.',
								'default' => 'Available Online',
							],
							'defaultAvailabilityToggle' => [
								'property' => 'defaultAvailabilityToggle',
								'type' => 'enum',
								'values' => [
									'global' => 'Entire Collection',
									'local' => 'Local Collection',
									'available' => 'Available',
									'available_online' => 'Available Online',
								],
								'label' => 'Default Toggle',
								'description' => 'The default toggle to apply if the user does not select one',
								'default' => 'entire_scope',
							],
							'baseAvailabilityToggleOnLocalHoldingsOnly' => [
								'property' => 'baseAvailabilityToggleOnLocalHoldingsOnly',
								'type' => 'checkbox',
								'label' => 'Base Availability Toggle On Local Holdings Only',
								'default' => false,
								'forcesReindex' => true,
							],
							'includeOnlineMaterialsInAvailableToggle' => [
								'property' => 'includeOnlineMaterialsInAvailableToggle',
								'type' => 'checkbox',
								'label' => 'Include Online Materials in Available Toggle',
								'description' => 'Turn on to include online materials in both the Available Now and Available Online Toggles.',
								'hideInLists' => true,
								'default' => false,
								'forcesReindex' => true,
							],
							'includeAllRecordsInShelvingFacets' => [
								'property' => 'includeAllRecordsInShelvingFacets',
								'type' => 'checkbox',
								'label' => 'Include All Records In Shelving Facets',
								'description' => 'Turn on to include all records (owned and included) in shelving related facets (detailed location, collection).',
								'hideInLists' => true,
								'default' => false,
								'forcesReindex' => true,
							],
							'includeAllRecordsInDateAddedFacets' => [
								'property' => 'includeAllRecordsInDateAddedFacets',
								'type' => 'checkbox',
								'label' => 'Include All Records In Date Added Facets',
								'description' => 'Turn on to include all records (owned and included) in date added facets.',
								'hideInLists' => true,
								'default' => false,
								'forcesReindex' => true,
							],
							'facetCountsToShow' => [
								'property' => 'facetCountsToShow',
								'type' => 'enum',
								'values' => [
									'1' => 'Show all counts (exact and approximate)',
									'2' => 'Show exact counts only',
									'3' => 'Show no counts',
								],
								'label' => 'Facet Counts To Show',
								'description' => 'The counts to show for facets',
							],
							'facetGroupId' => [
								'property' => 'facetGroupId',
								'type' => 'enum',
								'values' => $facetGroups,
								'label' => 'Facet Group',
							],
						],
					],
					'formatSortingGroupId' => [
						'property' => 'formatSortingGroupId',
						'type' => 'enum',
						'values' => $formatSortGroups,
						'label' => 'Format Sorting',
						'description' => 'Required format sorting configuration that can be created under Format Sorting.',
						'required' => true,
						'default' => 1
					],
					'searchAlgorithm' => [
						'property' => 'searchAlgorithm',
						'type' => 'section',
						'label' => 'Search Algorithm (Experimental)',
						'hideInLists' => true,
						'expandByDefault' => false,
						'properties' => [
							'searchSpecVersion' => [
								'property' => 'searchSpecVersion',
								'type' => 'enum',
								'values' => [
									1 => '23.11 and before',
									2 => '23.12 de-emphasize series in title search '
								],
								'label' => 'Search Specification Version',
								'default' => 2,
							],
							'limitBoosts' => [
								'property' => 'limitBoosts',
								'type' => 'checkbox',
								'label' => 'Limit boosts',
								'default' => true
							],
							'maxTotalBoost' => [
								'property' => 'maxTotalBoost',
								'type' => 'enum',
								'values' => [
									1 => 'Apply no boosting',
									50 => 'Low Boosting (50)',
									250 => 'Medium-Low Boosting (250)',
									500 => 'Medium Boosting (500)',
									750 => 'Medium-High Boosting (750)',
									1000 => 'High Boosting (1000)',
									10000 => 'Very High Boosting (10000)',
								],
								'label' => 'Maximum Total Boost',
								'default' => 500,
							],
							'maxPopularityBoost' => [
								'property' => 'maxPopularityBoost',
								'type' => 'enum',
								'values' => [
									1 => 'Apply no boosting',
									5 => 'Low Boosting (5)',
									10 => 'Medium-Low Boosting (10)',
									25 => 'Medium Boosting (25)',
									50 => 'Medium-High Boosting (50)',
									100 => 'High Boosting (100)',
									1000 => 'Very High Boosting (1000)',
								],
								'label' => 'Maximum Popularity (Checkouts & Usage) Boost',
								'default' => 25,
							],
							'maxFormatBoost' => [
								'property' => 'maxFormatBoost',
								'type' => 'enum',
								'values' => [
									1 => 'Apply no boosting',
									5 => 'Low Boosting (5)',
									10 => 'Medium-Low Boosting (10)',
									25 => 'Medium Boosting (25)',
									50 => 'Medium-High Boosting (50)',
									100 => 'High Boosting (100)',
									1000 => 'Very High Boosting (1000)',
								],
								'label' => 'Maximum Format Boost',
								'default' => 25,
							],
							'maxHoldingsBoost' => [
								'property' => 'maxHoldingsBoost',
								'type' => 'enum',
								'values' => [
									1 => 'Apply no boosting',
									5 => 'Low Boosting (5)',
									10 => 'Medium-Low Boosting (10)',
									25 => 'Medium Boosting (25)',
									50 => 'Medium-High Boosting (50)',
									100 => 'High Boosting (100)',
									1000 => 'Very High Boosting (1000)',
								],
								'label' => 'Maximum Holdings (number of copies) Boost',
								'default' => 25,
							]
						]
					]
				],
			],

			// Catalog Enrichment //
			'enrichmentSection' => [
				'property' => 'enrichmentSection',
				'type' => 'section',
				'label' => 'Catalog Enrichment',
				'renderAsHeading' => true,
				'hideInLists' => true,
				'properties' => [
					'showStandardReviews' => [
						'property' => 'showStandardReviews',
						'type' => 'checkbox',
						'label' => 'Show Syndicated Reviews',
						'description' => 'Whether or not reviews from Content Cafe/Syndetics are displayed on the full record page.',
						'hideInLists' => true,
						'default' => 1,
					],
					'showGoodReadsReviews' => [
						'property' => 'showGoodReadsReviews',
						'type' => 'checkbox',
						'label' => 'Show GoodReads Reviews',
						'description' => 'Whether or not reviews from GoodReads are displayed on the full record page.',
						'hideInLists' => true,
						'default' => true,
					],
					'preferSyndeticsSummary' => [
						'property' => 'preferSyndeticsSummary',
						'type' => 'checkbox',
						'label' => 'Prefer Syndetics/Content Cafe Description',
						'description' => 'Whether or not the Description loaded from an enrichment service should be preferred over the Description in the Marc Record.',
						'hideInLists' => true,
						'default' => 1,
					],
					'showSimilarAuthors' => [
						'property' => 'showSimilarAuthors',
						'type' => 'checkbox',
						'label' => 'Show Similar Authors',
						'description' => 'Whether or not Similar Authors from NoveList is shown.',
						'default' => 1,
						'hideInLists' => true,
					],
					'showSimilarTitles' => [
						'property' => 'showSimilarTitles',
						'type' => 'checkbox',
						'label' => 'Show Similar Titles',
						'description' => 'Whether or not Similar Titles from NoveList is shown.',
						'default' => 1,
						'hideInLists' => true,
					],
					//'showGoDeeper'             => array('property'=>'showGoDeeper', 'type'=>'checkbox', 'label'=>'Show Content Enrichment (TOC, Excerpts, etc)', 'description'=>'Whether additional content enrichment like Table of Contents, Excerpts, etc. are shown to the user', 'default' => 1, 'hideInLists' => true,),
					'showRatings' => [
						'property' => 'showRatings',
						'type' => 'checkbox',
						'label' => 'Enable User Ratings',
						'description' => 'Whether or not ratings are shown',
						'hideInLists' => true,
						'default' => 1,
					],
					'showComments' => [
						'property' => 'showComments',
						'type' => 'checkbox',
						'label' => 'Enable User Reviews',
						'description' => 'Whether or not user reviews are shown (also disables adding user reviews)',
						'hideInLists' => true,
						'default' => 1,
					],
					'hideCommentsWithBadWords' => [
						'property' => 'hideCommentsWithBadWords',
						'type' => 'checkbox',
						'label' => 'Hide User Content with Bad Words',
						'description' => 'If checked, any User Lists or User Reviews with bad words are completely removed from the user interface for everyone except the original poster.',
						'hideInLists' => true,
					],
				],
			],

			// Full Record Display //
			'fullRecordSection' => [
				'property' => 'fullRecordSection',
				'type' => 'section',
				'label' => 'Full Record Display',
				'renderAsHeading' => true,
				'hideInLists' => true,
				'helpLink' => '',
				'properties' => [
					'show856LinksAsTab' => [
						'property' => 'show856LinksAsTab',
						'type' => 'checkbox',
						'label' => 'Show 856 Links in their own section',
						'description' => 'Whether or not 856 links will be shown in their own tab or on the same tab as holdings.',
						'hideInLists' => true,
						'default' => 1,
					],
//					'show856LinksAsAccessOnlineButtons' => [
//						'property' => 'show856LinksAsAccessOnlineButtons',
//						'type' => 'checkbox',
//						'label' => 'Show 856 Links as Access Online Buttons',
//						'description' => 'Whether 856 links with indicator 1 of 4 and indicator 2 of 0 will be shown as access online buttons.',
//						'hideInLists' => true,
//						'default' => 0,
//					],
					'showCheckInGrid' => [
						'property' => 'showCheckInGrid',
						'type' => 'checkbox',
						'label' => 'Show Check-in Grid',
						'description' => 'Whether or not the check-in grid is shown for periodicals.',
						'default' => 1,
						'hideInLists' => true,
					],
					'showStaffView' => [
						'property' => 'showStaffView',
						'type' => 'enum',
						'values' => [
							0 => 'Do not show',
							1 => 'Show for all users',
							2 => 'Show for staff only',
						],
						'label' => 'Show Staff View',
						'description' => 'Whether or not the staff view is displayed in full record view.',
						'hideInLists' => true,
						'default' => 1,
					],
					'showLCSubjects' => [
						'property' => 'showLCSubjects',
						'type' => 'checkbox',
						'label' => 'Show Library of Congress Subjects',
						'description' => 'Whether or not standard (LC) subjects are displayed in full record view.',
						'hideInLists' => true,
						'default' => true,
					],
					'showBisacSubjects' => [
						'property' => 'showBisacSubjects',
						'type' => 'checkbox',
						'label' => 'Show Bisac Subjects',
						'description' => 'Whether or not Bisac subjects are displayed in full record view.',
						'hideInLists' => true,
						'default' => true,
					],
					'showFastAddSubjects' => [
						'property' => 'showFastAddSubjects',
						'type' => 'checkbox',
						'label' => 'Show OCLC Fast Subjects',
						'description' => 'Whether or not OCLC Fast Add subjects are displayed in full record view.',
						'hideInLists' => true,
						'default' => true,
					],
					'showOtherSubjects' => [
						'property' => 'showOtherSubjects',
						'type' => 'checkbox',
						'label' => 'Show Other Subjects',
						'description' => 'Whether or not other subjects from the MARC are displayed in full record view.',
						'hideInLists' => true,
						'default' => true,
					],
					'showItemDueDates' => [
						'property' => 'showItemDueDates',
						'type' => 'checkbox',
						'label' => 'Show Item Due Dates',
						'description' => 'Whether or not due dates for items are shown within the copy details.',
						'hideInLists' => true,
						'default' => true,
					],
					'showItemNotes' => [
						'property' => 'showItemNotes',
						'type' => 'checkbox',
						'label' => 'Show Item Notes',
						'description' => 'Whether or notes for items are shown within the copy details if available.',
						'hideInLists' => true,
						'default' => true,
					],
					'preferIlsDescription' => [
						'property' => 'preferIlsDescription',
						'type' => 'checkbox',
						'label' => 'Prefer ILS Description',
						'description' => 'Whether or not the Description loaded from ILS should be preferred over eContent Description',
						'hideInLists' => true,
						'default' => false,
					],
					'showInMainDetails' => [
						'property' => 'showInMainDetails',
						'type' => 'multiSelect',
						'label' => 'Which details to show in the main/top details section : ',
						'description' => 'Selected details will be shown in the top/main section of the full record view. Details not selected are moved to the More Details accordion.',
						'listStyle' => 'checkboxSimple',
						'values' => self::$showInMainDetailsOptions,
					],
					'moreDetailsOptions' => [
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
						'canAddNew' => true,
						'canDelete' => true,
						'additionalOneToManyActions' => [
							0 => [
								'text' => 'Reset More Details To Default',
								'url' => '/Admin/GroupedWorkDisplay?id=$id&amp;objectAction=resetMoreDetailsToDefault',
								'class' => 'btn-warning',
							],
						],
					],
				],
			],

			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use this browse category group',
				'values' => $libraryList,
			],

			'locations' => [
				'property' => 'locations',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Locations',
				'description' => 'Define locations that use this browse category group',
				'values' => $locationList,
			],
		];

		$hasCheckInGrid = false;
		foreach (UserAccount::getAccountProfiles() as $accountProfileInfo) {
			/** @var AccountProfile $accountProfile */
			$accountProfile = $accountProfileInfo['accountProfile'];
			if ($accountProfile->ils == 'millennium' || $accountProfile->ils == 'sierra') {
				$hasCheckInGrid = true;
			}
		}
		if (!$hasCheckInGrid) {
			unset($structure['fullRecordSection']['properties']['showCheckInGrid']);
		}

		if (!UserAccount::getActiveUserObj()->isAspenAdminUser()) {
			unset($structure['searchingSection']['properties']['searchAlgorithm']);
		}

		return $structure;
	}

	/**
	 * Override the fetch functionality to fetch related objects
	 *
	 * @see DB/DB_DataObject::fetch()
	 */
	public function fetch(): bool|DataObject|null {
		$return = parent::fetch();
		if ($return) {
			if (!empty($this->showInSearchResultsMainDetails) && is_string($this->showInSearchResultsMainDetails) ) {
				// convert to array retrieving from the database
				$unSerialized = @unserialize($this->showInSearchResultsMainDetails);
				if (!empty($unSerialized)) {
					$this->showInSearchResultsMainDetails = array_combine($unSerialized, $unSerialized);
					if (!$this->showInSearchResultsMainDetails) {
						$this->showInSearchResultsMainDetails = [];
					}
				}else{
					$this->showInSearchResultsMainDetails = [];
				}
			}

			if (!empty($this->showInMainDetails) && is_string($this->showInMainDetails)) {
				// convert to array retrieving from the database
				try {
					$unSerialized = unserialize($this->showInMainDetails);
					if (!empty($unSerialized)) {
						$this->showInMainDetails = array_combine($unSerialized, $unSerialized);
						if (!$this->showInMainDetails) {
							$this->showInMainDetails = [];
						}
					}else{
						$this->showInMainDetails = [];
					}
				} catch (Exception $e) {
					global $logger;
					$logger->log("Error loading GroupedWorkDisplaySetting $this->id $e", Logger::LOG_DEBUG);
				}
			} elseif (empty($this->showInMainDetails)) {
				// when a value is not set, assume that we should show all options, e.g., null = all
				$default = self::$showInMainDetailsOptions;
				// remove options below that aren't meant to be part of the default
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
	public function update($context = '') {
		if (isset($this->showInSearchResultsMainDetails) && is_array($this->showInSearchResultsMainDetails)) {
			// convert the array to string before storing in the database
			$this->showInSearchResultsMainDetails = serialize($this->showInSearchResultsMainDetails);
		}

		if (isset($this->showInMainDetails) && is_array($this->showInMainDetails)) {
			// convert the array to string before storing in the database
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
	public function insert($context = '') {
		if (isset($this->showInSearchResultsMainDetails) && is_array($this->showInSearchResultsMainDetails)) {
			// convert the array to string before storing in the database
			$this->showInSearchResultsMainDetails = serialize($this->showInSearchResultsMainDetails);
		}
		if (isset($this->showInMainDetails) && is_array($this->showInMainDetails)) {
			// convert the array to string before storing in the database
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

	private GroupedWorkFacetGroup|false|null $_facetGroup = false;

	/** @return GroupedWorkFacet[] */
	public function getFacets() : array {
		try {
			return $this->getFacetGroup()->getFacets();
		} /** @noinspection PhpUnusedLocalVariableInspection */
		catch (Exception $e) {
			return [];
		}
	}

	public function getFacetGroup(): ?GroupedWorkFacetGroup {
		try {
			if ($this->_facetGroup === false) {
				$this->_facetGroup = new GroupedWorkFacetGroup();
				$this->_facetGroup->id = $this->facetGroupId;
				if (!$this->_facetGroup->find(true)) {
					$this->_facetGroup = null;
				}
			}
			return $this->_facetGroup;
		} /** @noinspection PhpUnusedLocalVariableInspection */
		catch (Exception $e) {
			return null;
		}
	}

	public function __get($name) {
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
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == "libraries") {
			$this->_libraries = $value;
		} elseif ($name == "locations") {
			$this->_locations = $value;
		} elseif ($name == 'moreDetailsOptions') {
			$this->setMoreDetailsOptions($value);
		} else {
			parent::__set($name, $value);
		}
	}

	/**
	 * @return GroupedWorkMoreDetails[]|null
	 */
	public function getMoreDetailsOptions() : ?array {
		if (!isset($this->_moreDetailsOptions) && $this->id) {
			$this->_moreDetailsOptions = [];
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

	public function setMoreDetailsOptions($value) : void {
		$this->_moreDetailsOptions = $value;
	}

	public function saveMoreDetailsOptions() : void {
		if (isset ($this->_moreDetailsOptions)) {
			$this->saveOneToManyOptions($this->_moreDetailsOptions, 'groupedWorkSettingsId');
			unset($this->_moreDetailsOptions);
		}
	}

	public function clearMoreDetailsOptions() : void {
		$this->clearOneToManyOptions('GroupedWorkMoreDetails', 'groupedWorkSettingsId');
		$this->_moreDetailsOptions = [];
	}

	public static function getDefaultDisplaySettings() : GroupedWorkDisplaySetting {
		$defaultDisplaySettings = new GroupedWorkDisplaySetting();
		$defaultDisplaySettings->name = 'default';
		$defaultDisplaySettings->sortOwnedEditionsFirst = true;
		$defaultDisplaySettings->applyNumberOfHoldingsBoost = true;
		$defaultDisplaySettings->includeOutOfSystemExternalLinks = false;
		$defaultDisplaySettings->showSearchTools = true;
		$defaultDisplaySettings->showSearchToolsAtTop = true;
		$defaultDisplaySettings->showQuickCopy = true;
		$defaultDisplaySettings->showInSearchResultsMainDetails = '';
		$defaultDisplaySettings->alwaysShowSearchResultsMainDetails = false;
		$defaultDisplaySettings->alwaysFlagNewTitles = false;
		$defaultDisplaySettings->showRelatedRecordLabels = true;
		$defaultDisplaySettings->showEditionCovers = true;
		$defaultDisplaySettings->availabilityToggleLabelSuperScope = 'Entire Collection';
		$defaultDisplaySettings->availabilityToggleLabelLocal = '';
		$defaultDisplaySettings->availabilityToggleLabelAvailable = 'Available Now';
		$defaultDisplaySettings->availabilityToggleLabelAvailableOnline = 'Available Online';
		$defaultDisplaySettings->defaultAvailabilityToggle = 'global';
		$defaultDisplaySettings->baseAvailabilityToggleOnLocalHoldingsOnly = false;
		$defaultDisplaySettings->includeOnlineMaterialsInAvailableToggle = false;
		$defaultDisplaySettings->includeAllRecordsInShelvingFacets = false;
		$defaultDisplaySettings->includeAllRecordsInDateAddedFacets = false;
		$defaultDisplaySettings->facetCountsToShow = 2;
		return $defaultDisplaySettings;
	}

	public function saveLibraries() : void {
		if (isset ($this->_libraries)) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Grouped Work Display Settings'));
			$this->saveOneToManyOptions($this->_libraries, 'groupedWorkDisplaySettingId', $libraryList, 'Library');
			unset($this->_libraries);
		}
	}

	public function saveLocations() : void {
		if (isset ($this->_locations)) {
			$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Grouped Work Display Settings'));
			$this->saveOneToManyOptions($this->_locations, 'groupedWorkDisplaySettingId', $locationList, 'Location');
			unset($this->_locations);
		}
	}

	/** @return Library[]
	 * @noinspection PhpUnused
	 */
	public function getLibraries() : array {
		return $this->_libraries;
	}

	/** @return Location[]
	 * @noinspection PhpUnused
	 */
	public function getLocations() : array {
		return $this->_locations;
	}

	/** @noinspection PhpUnused */
	public function setLibraries($val) : void {
		$this->_libraries = $val;
	}

	/** @noinspection PhpUnused */
	public function setLocations($val) : void {
		$this->_libraries = $val;
	}

	/** @noinspection PhpUnused */
	public function clearLibraries() : void {
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Grouped Work Display Settings'));
		$this->clearOneToManyOptions('Library', 'groupedWorkDisplaySettingId', $libraryList);
		unset($this->_libraries);
	}

	/** @noinspection PhpUnused */
	public function clearLocations() : void {
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Grouped Work Display Settings'));
		$this->clearOneToManyOptions('Location', 'groupedWorkDisplaySettingId', $locationList);
		unset($this->_locations);
	}

	function getAdditionalListJavascriptActions(): array {
		$objectActions[] = [
			'text' => 'Copy',
			'onClick' => "return AspenDiscovery.Admin.showCopyDisplaySettingsForm('$this->id')",
			'icon' => 'fas fa-copy',
		];

		return $objectActions;
	}

	public function getLinkedObjectStructure() : array {
		return [
			[
				'object' => 'Location',
				'class' => ROOT_DIR . '/sys/LibraryLocation/Location.php',
				'linkingProperty' => 'groupedWorkDisplaySettingId',
				'objectName' => 'Location',
				'objectNamePlural' => 'Locations',
			],
			[
				'object' => 'Library',
				'class' => ROOT_DIR . '/sys/LibraryLocation/Library.php',
				'linkingProperty' => 'groupedWorkDisplaySettingId',
				'objectName' => 'Library',
				'objectNamePlural' => 'Libraries',
			],
		];
	}

	private GroupedWorkFormatSortingGroup|false|null $_formatSortingGroup = false;
	public function getFormatSortingGroup() : ?GroupedWorkFormatSortingGroup {
		if ($this->_formatSortingGroup === false) {
			$this->_formatSortingGroup = new GroupedWorkFormatSortingGroup();
			$this->_formatSortingGroup->id = $this->formatSortingGroupId;
			if (!$this->_formatSortingGroup->find(true)) {
				$this->_formatSortingGroup = null;
			}
		}
		return $this->_formatSortingGroup;
	}
}