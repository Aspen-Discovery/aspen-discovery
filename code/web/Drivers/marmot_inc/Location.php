<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
require_once ROOT_DIR . '/Drivers/marmot_inc/LocationHours.php';
require_once ROOT_DIR . '/Drivers/marmot_inc/LocationFacetSetting.php';
require_once ROOT_DIR . '/Drivers/marmot_inc/LocationCombinedResultSection.php';
require_once ROOT_DIR . '/sys/Browse/LocationBrowseCategory.php';
require_once ROOT_DIR . '/sys/Indexing/LocationRecordOwned.php';
require_once ROOT_DIR . '/sys/Indexing/LocationRecordToInclude.php';


class Location extends DataObject
{
	const DEFAULT_AUTOLOGOUT_TIME = 90;
	const DEFAULT_AUTOLOGOUT_TIME_LOGGED_OUT = 450;

	public $__table = 'location';   // table name
    public $__primaryKey = 'locationId';
	public $locationId;				//int(11)
	public $subdomain;
	public $code;					//varchar(5)
	public $subLocation;
	public $displayName;			//varchar(40)
    public $theme;
	public $showDisplayNameInHeader;
	public $headerText;
	public $libraryId;				//int(11)
	public $address;
	public $phone;
	public $isMainBranch; // tinyint(1)
	public $showInLocationsAndHoursList;
	public $validHoldPickupBranch;	//tinyint(4)
	public $nearbyLocation1;		//int(11)
	public $nearbyLocation2;		//int(11)
	public $holdingBranchLabel;     //varchar(40)
	public $scope;
	public $useScope;
	public $facetLabel;
	public $restrictSearchByLocation;
	public $enableOverdriveCollection;
	public $includeOverDriveAdult;
	public $includeOverDriveTeen;
	public $includeOverDriveKids;
	public $showHoldButton;
	public $showStandardReviews;
	public $repeatSearchOption;
	public $repeatInOnlineCollection;
	public $repeatInProspector;
	public $repeatInWorldCat;
	public $repeatInOverdrive;
	public $systemsToRepeatIn;
	public $homeLink;
	public $defaultPType;
	public $ptypesToAllowRenewals;
	public $publicListsToInclude;
	public $automaticTimeoutLength;
	public $automaticTimeoutLengthLoggedOut;
	public $additionalCss;
	public $showEmailThis;
	public $showShareOnExternalSites;
	public $showFavorites;
	public $showComments;
	public $showStaffView;
	public $showGoodReadsReviews;
	public $econtentLocationsToInclude;
	public $availabilityToggleLabelSuperScope;
	public $availabilityToggleLabelLocal;
	public $availabilityToggleLabelAvailable;
	public $availabilityToggleLabelAvailableOnline;
	public $baseAvailabilityToggleOnLocalHoldingsOnly;
	public $includeOnlineMaterialsInAvailableToggle;
	public $defaultBrowseMode;
	public $browseCategoryRatingsMode;
	public $includeAllLibraryBranchesInFacets;
	public $additionalLocationsToShowAvailabilityFor;
	public $includeAllRecordsInShelvingFacets;
	public $includeAllRecordsInDateAddedFacets;
	public $includeLibraryRecordsToInclude;

	//Combined Results (Bento Box)
	public $enableCombinedResults;
	public $combinedResultsLabel;
	public $defaultToCombinedResults;
	public $useLibraryCombinedResultsSettings;

	/** @var  array $_data */
	protected $_data;

	function keys() {
		return array('locationId', 'code');
	}

	function getNumericColumnNames()
    {
        return ['scope'];
    }

    static function getObjectStructure(){
		//Load Libraries for lookup values
		$library = new Library();
		$library->orderBy('displayName');
		if (UserAccount::userHasRole('libraryAdmin') && !UserAccount::userHasRole('opacAdmin') || UserAccount::userHasRole('libraryManager') || UserAccount::userHasRole('locationManager')){
			$homeLibrary = Library::getPatronHomeLibrary();
			$library->libraryId = $homeLibrary->libraryId;
		}
		$library->find();
		$libraryList = array();
		while ($library->fetch()){
			$libraryList[$library->libraryId] = $library->displayName;
		}

		//Look lookup information for display in the user interface
		$location = new Location();
		$location->orderBy('displayName');
		$location->find();
		$locationList = array();
		$locationLookupList = array();
		$locationLookupList[-1] = '<No Nearby Location>';
		while ($location->fetch()){
			$locationLookupList[$location->locationId] = $location->displayName;
			$locationList[$location->locationId] = clone $location;
		}

		// get the structure for the location's hours
		$hoursStructure = LocationHours::getObjectStructure();

		// we don't want to make the locationId property editable
		// because it is associated with this location only
		unset($hoursStructure['locationId']);

		$facetSettingStructure = LocationFacetSetting::getObjectStructure();
		unset($facetSettingStructure['weight']);
		unset($facetSettingStructure['locationId']);
		unset($facetSettingStructure['numEntriesToShowByDefault']);
		unset($facetSettingStructure['showAsDropDown']);
		//unset($facetSettingStructure['sortMode']);

		$locationBrowseCategoryStructure = LocationBrowseCategory::getObjectStructure();
		unset($locationBrowseCategoryStructure['weight']);
		unset($locationBrowseCategoryStructure['locationId']);

		$locationMoreDetailsStructure = LocationMoreDetails::getObjectStructure();
		unset($locationMoreDetailsStructure['weight']);
		unset($locationMoreDetailsStructure['locationId']);

		$locationRecordOwnedStructure = LocationRecordOwned::getObjectStructure();
		unset($locationRecordOwnedStructure['locationId']);

		$locationRecordToIncludeStructure = LocationRecordToInclude::getObjectStructure();
		unset($locationRecordToIncludeStructure['locationId']);
		unset($locationRecordToIncludeStructure['weight']);

		$combinedResultsStructure = LocationCombinedResultSection::getObjectStructure();
		unset($combinedResultsStructure['locationId']);
		unset($combinedResultsStructure['weight']);

		$browseCategoryInstructions = 'For more information on how to setup browse categories, see the <a href="https://docs.google.com/document/d/11biGMw6UDKx9UBiDCCj_GBmatx93UlJBLMESNf_RtDU">online documentation</a>.';

        require_once ROOT_DIR . '/sys/Theming/Theme.php';
        $theme = new Theme();
        $availableThemes = array();
        $theme->orderBy('themeName');
        $theme->find();
        while ($theme->fetch()){
            $availableThemes[$theme->id] = $theme->themeName;
        }

	    $structure = array(
				'locationId' => array('property'=>'locationId', 'type'=>'label', 'label'=>'Location Id', 'description'=>'The unique id of the location within the database'),
				'subdomain' => array('property'=>'subdomain', 'type'=>'text', 'label'=>'Subdomain', 'description'=>'The subdomain to use while identifying this branch.  Can be left if it matches the code.', 'required'=>false),
				'code' => array('property'=>'code', 'type'=>'text', 'label'=>'Code', 'description'=>'The code for use when communicating with the ILS', 'required'=>true),
				'subLocation' => array('property'=>'subLocation', 'type'=>'text', 'label'=>'Sub Location Code', 'description'=>'The sub location or collection used to identify this '),
				'displayName' => array('property'=>'displayName', 'type'=>'text', 'label'=>'Display Name', 'description'=>'The full name of the location for display to the user', 'size'=>'40'),
                'theme' => array('property'=>'theme', 'type'=>'enum', 'label'=>'Theme', 'values' => $availableThemes, 'description'=>'The theme which should be used for the library', 'hideInLists' => true, 'default' => 'default'),
                'showDisplayNameInHeader' => array('property'=>'showDisplayNameInHeader', 'type'=>'checkbox', 'label'=>'Show Display Name in Header', 'description'=>'Whether or not the display name should be shown in the header next to the logo', 'hideInLists' => true, 'default'=>false),
				array('property'=>'libraryId', 'type'=>'enum', 'values'=>$libraryList, 'label'=>'Library', 'description'=>'A link to the library which the location belongs to'),
				'isMainBranch' => array('property'=>'isMainBranch', 'type'=>'checkbox', 'label'=>'Is Main Branch', 'description'=>'Is this location the main branch for it\'s library', /*'hideInLists' => false,*/ 'default'=>false),
				'showInLocationsAndHoursList' => array('property'=>'showInLocationsAndHoursList', 'type'=>'checkbox', 'label'=>'Show In Locations And Hours List', 'description'=>'Whether or not this location should be shown in the list of library hours and locations', 'hideInLists' => true, 'default'=>true),
				'address' => array('property'=>'address', 'type'=>'textarea', 'label'=>'Address', 'description'=>'The address of the branch.', 'hideInLists' => true),
				'phone' => array('property'=>'phone', 'type'=>'text', 'label'=> 'Phone Number', 'description'=>'The main phone number for the site .', 'size' => '40', 'hideInLists' => true),
				'nearbyLocation1' => array('property'=>'nearbyLocation1', 'type'=>'enum', 'values'=>$locationLookupList, 'label'=>'Nearby Location 1', 'description'=>'A secondary location which is nearby and could be used for pickup of materials.', 'hideInLists' => true),
				'nearbyLocation2' => array('property'=>'nearbyLocation2', 'type'=>'enum', 'values'=>$locationLookupList, 'label'=>'Nearby Location 2', 'description'=>'A tertiary location which is nearby and could be used for pickup of materials.', 'hideInLists' => true),
				'automaticTimeoutLength' => array('property'=>'automaticTimeoutLength', 'type'=>'integer', 'label'=>'Automatic Timeout Length (logged in)', 'description'=>'The length of time before the user is automatically logged out in seconds.', 'size'=>'8', 'hideInLists' => true, 'default'=>self::DEFAULT_AUTOLOGOUT_TIME),
				'automaticTimeoutLengthLoggedOut' => array('property'=>'automaticTimeoutLengthLoggedOut', 'type'=>'integer', 'label'=>'Automatic Timeout Length (logged out)', 'description'=>'The length of time before the catalog resets to the home page set to 0 to disable.', 'size'=>'8', 'hideInLists' => true,'default'=>self::DEFAULT_AUTOLOGOUT_TIME_LOGGED_OUT),

				'displaySection'=> array('property'=>'displaySection', 'type' => 'section', 'label' =>'Basic Display', 'hideInLists' => true, 'properties' => array(
						array('property'=>'homeLink', 'type'=>'text', 'label'=>'Home Link', 'description'=>'The location to send the user when they click on the home button or logo.  Use default or blank to go back to the vufind home location.', 'hideInLists' => true, 'size'=>'40'),
						array('property'=>'additionalCss', 'type'=>'textarea', 'label'=>'Additional CSS', 'description'=>'Extra CSS to apply to the site.  Will apply to all pages.', 'hideInLists' => true),
						array('property'=>'headerText', 'type'=>'html', 'label'=>'Header Text', 'description'=>'Optional Text to display in the header, between the logo and the log in/out buttons.  Will apply to all pages.', 'allowableTags' => '<a><b><em><div><span><p><strong><sub><sup><h1><h2><h3><h4><h5><h6><img>', 'hideInLists' => true),
				)),

				'ilsSection' => array('property'=>'ilsSection', 'type' => 'section', 'label' =>'ILS/Account Integration', 'hideInLists' => true, 'properties' => array(
						array('property'=>'holdingBranchLabel', 'type'=>'text', 'label'=>'Holding Branch Label', 'description'=>'The label used within the holdings table in Millennium'),
						array('property'=>'scope', 'type'=>'text', 'label'=>'Scope', 'description'=>'The scope for the system in Millennium to refine holdings to the branch.  If there is no scope defined for the branch, this can be set to 0.', 'default'=>0),
						array('property'=>'useScope', 'type'=>'checkbox', 'label'=>'Use Scope?', 'description'=>'Whether or not the scope should be used when displaying holdings.', 'hideInLists' => true),
						array('property'=>'defaultPType', 'type'=>'text', 'label'=>'Default P-Type', 'description'=>'The P-Type to use when accessing a subdomain if the patron is not logged in.  Use -1 to use the library default PType.', 'default'=>-1),
						array('property'=>'validHoldPickupBranch', 'type'=>'enum', 'values' => array('1' => 'Valid for all patrons', '0' => 'Valid for patrons of this branch only', '2' => 'Not Valid' ), 'label'=>'Valid Hold Pickup Branch?', 'description'=>'Determines if the location can be used as a pickup location if it is not the patrons home location or the location they are in.', 'hideInLists' => true, 'default' => 1),
						array('property'=>'showHoldButton', 'type'=>'checkbox', 'label'=>'Show Hold Button', 'description'=>'Whether or not the hold button is displayed so patrons can place holds on items', 'hideInLists' => true, 'default'=>true),
						array('property'=>'ptypesToAllowRenewals', 'type'=>'text', 'label'=>'PTypes that can renew', 'description'=>'A list of P-Types that can renew items or * to allow all P-Types to renew items.', 'hideInLists' => true, 'default' => '*'),
				)),

				'searchingSection' => array('property'=>'searchingSection', 'type' => 'section', 'label' =>'Searching', 'hideInLists' => true, 'properties' => array(
						array('property'=>'restrictSearchByLocation', 'type'=>'checkbox', 'label'=>'Restrict Search By Location', 'description'=>'Whether or not search results should only include titles from this location', 'hideInLists' => true, 'default'=>false),
						array('property' => 'publicListsToInclude', 'type'=>'enum', 'values' => array(0 => 'No Lists', '1' => 'Lists from this library', '4'=>'Lists from library list publishers Only', '2'=>'Lists from this location', '5'=>'Lists from list publishers at this location Only', '6'=>'Lists from all list publishers', '3' => 'All Lists'), 'label'=>'Public Lists To Include', 'description'=>'Which lists should be included in this scope'),
						array('property' => 'searchBoxSection', 'type' => 'section', 'label' => 'Search Box', 'hideInLists' => true, 'properties' => array(
								array('property'=>'systemsToRepeatIn', 'type'=>'text', 'label'=>'Systems To Repeat In', 'description'=>'A list of library codes that you would like to repeat search in separated by pipes |.', 'hideInLists' => true),
								array('property'=>'repeatSearchOption', 'type'=>'enum', 'values'=>array('none'=>'None', 'librarySystem'=>'Library System','marmot'=>'Entire Consortium'), 'label'=>'Repeat Search Options (requires Restrict Search By Location to be ON)', 'description'=>'Where to allow repeating search. Valid options are: none, librarySystem, marmot, all', 'default'=>'marmot'),
								array('property'=>'repeatInOnlineCollection', 'type'=>'checkbox', 'label'=>'Repeat In Online Collection', 'description'=>'Turn on to allow repeat search in the Online Collection.', 'hideInLists' => true, 'default'=>false),
								array('property'=>'repeatInProspector', 'type'=>'checkbox', 'label'=>'Repeat In Prospector', 'description'=>'Turn on to allow repeat search in Prospector functionality.', 'hideInLists' => true, 'default'=>false),
								array('property'=>'repeatInWorldCat', 'type'=>'checkbox', 'label'=>'Repeat In WorldCat', 'description'=>'Turn on to allow repeat search in WorldCat functionality.', 'hideInLists' => true, 'default'=>false),
								array('property'=>'repeatInOverdrive', 'type'=>'checkbox', 'label'=>'Repeat In Overdrive', 'description'=>'Turn on to allow repeat search in Overdrive functionality.', 'hideInLists' => true, 'default'=>false),
						)),
						array('property' => 'searchFacetsSection', 'type' => 'section', 'label' => 'Search Facets', 'hideInLists' => true, 'properties' => array(
								array('property'=>'availabilityToggleLabelSuperScope', 'type' => 'text', 'label' => 'SuperScope Toggle Label', 'description' => 'The label to show when viewing super scope i.e. Consortium Name / Entire Collection / Everything.  Does not show if superscope is not enabled.', 'default' => 'Entire Collection'),
								array('property'=>'availabilityToggleLabelLocal', 'type' => 'text', 'label' => 'Local Collection Toggle Label', 'description' => 'The label to show when viewing the local collection i.e. Library Name / Local Collection.  Leave blank to hide the button.', 'default' => '{display name}'),
								array('property'=>'availabilityToggleLabelAvailable', 'type' => 'text', 'label' => 'Available Toggle Label', 'description' => 'The label to show when viewing available items i.e. Available Now / Available Locally / Available Here.', 'default' => 'Available Now'),
								array('property'=>'availabilityToggleLabelAvailableOnline', 'type' => 'text', 'label' => 'Available Online Toggle Label', 'description' => 'The label to show when viewing available items i.e. Available Online.', 'default' => 'Available Online'),
								array('property'=>'baseAvailabilityToggleOnLocalHoldingsOnly', 'type'=>'checkbox', 'label'=>'Base Availability Toggle on Local Holdings Only', 'description'=>'Turn on to use local materials only in availability toggle.', 'hideInLists' => true, 'default'=>false),
								array('property'=>'includeOnlineMaterialsInAvailableToggle', 'type'=>'checkbox', 'label'=>'Include Online Materials in Available Toggle', 'description'=>'Turn on to include online materials in both the Available Now and Available Online Toggles.', 'hideInLists' => true, 'default'=>false),
								array('property'=>'facetLabel', 'type'=>'text', 'label'=>'Facet Label', 'description'=>'The label of the facet that identifies this location.', 'hideInLists' => true, 'size'=>'40'),
								array('property'=>'includeAllLibraryBranchesInFacets', 'type'=>'checkbox', 'label'=>'Include All Library Branches In Facets', 'description'=>'Turn on to include all branches of the library within facets (ownership and availability).', 'hideInLists' => true, 'default'=>true),
								array('property'=>'additionalLocationsToShowAvailabilityFor', 'type'=>'text', 'label'=>'Additional Locations to Include in Available At Facet', 'description'=>'A list of library codes that you would like included in the available at facet separated by pipes |.', 'size'=>'20', 'hideInLists' => true,),
								array('property'=>'includeAllRecordsInShelvingFacets', 'type'=>'checkbox', 'label'=>'Include All Records In Shelving Facets', 'description'=>'Turn on to include all records (owned and included) in shelving related facets (detailed location, collection).', 'hideInLists' => true, 'default'=>false),
								array('property'=>'includeAllRecordsInDateAddedFacets', 'type'=>'checkbox', 'label'=>'Include All Records In Date Added Facets', 'description'=>'Turn on to include all records (owned and included) in date added facets.', 'hideInLists' => true, 'default'=>false),
								'facets' => array(
										'property'=>'facets',
										'type'=>'oneToMany',
										'label'=>'Facets',
										'description'=>'A list of facets to display in search results',
										'keyThis' => 'locationId',
										'keyOther' => 'locationId',
										'subObjectType' => 'LocationFacetSetting',
										'structure' => $facetSettingStructure,
										'sortable' => true,
										'storeDb' => true,
										'allowEdit' => true,
										'canEdit' => true,
								),
						)),
						'combinedResultsSection' => array('property' => 'combinedResultsSection', 'type' => 'section', 'label' => 'Combined Results', 'hideInLists' => true, 'helpLink' => 'https://docs.google.com/document/d/1dcG12grGAzYlWAl6LWUnr9t-wdqcmMTJVwjLuItRNwk', 'properties' => array(
								'useLibraryCombinedResultsSettings' => array('property' => 'useLibraryCombinedResultsSettings', 'type'=>'checkbox', 'label'=>'Use Library Settings', 'description'=>'Whether or not settings from the library should be used rather than settings from here', 'hideInLists' => true, 'default' => true),
								'enableCombinedResults' => array('property' => 'enableCombinedResults', 'type'=>'checkbox', 'label'=>'Enable Combined Results', 'description'=>'Whether or not combined results should be shown ', 'hideInLists' => true, 'default' => false),
								'combinedResultsLabel' => array('property' => 'combinedResultsLabel', 'type' => 'text', 'label' => 'Combined Results Label', 'description' => 'The label to use in the search source box when combined results is active.', 'size'=>'20', 'hideInLists' => true, 'default' => 'Combined Results'),
								'defaultToCombinedResults' => array('property' => 'defaultToCombinedResults', 'type'=>'checkbox', 'label'=>'Default To Combined Results', 'description'=>'Whether or not combined results should be the default search source when active ', 'hideInLists' => true, 'default' => true),
								'combinedResultSections' => array(
										'property' => 'combinedResultSections',
										'type' => 'oneToMany',
										'label' => 'Combined Results Sections',
										'description' => 'Which sections should be shown in the combined results search display',
										'helpLink' => '',
										'keyThis' => 'locationId',
										'keyOther' => 'locationId',
										'subObjectType' => 'LocationCombinedResultSection',
										'structure' => $combinedResultsStructure,
										'sortable' => true,
										'storeDb' => true,
										'allowEdit' => true,
										'canEdit' => false,
										'additionalOneToManyActions' => array(
										)
								),
						)),
				)),

			// Catalog Enrichment //
				'enrichmentSection' => array('property'=>'enrichmentSection', 'type' => 'section', 'label' =>'Catalog Enrichment', 'hideInLists' => true, 'properties' => array(
						array('property'=>'showStandardReviews', 'type'=>'checkbox', 'label'=>'Show Standard Reviews', 'description'=>'Whether or not reviews from Content Cafe/Syndetics are displayed on the full record page.', 'hideInLists' => true, 'default'=>true),
						array('property'=>'showGoodReadsReviews', 'type'=>'checkbox', 'label'=>'Show GoodReads Reviews', 'description'=>'Whether or not reviews from GoodReads are displayed on the full record page.', 'hideInLists' => true, 'default'=>true),
						'showFavorites'  => array('property'=>'showFavorites', 'type'=>'checkbox', 'label'=>'Enable User Lists', 'description'=>'Whether or not users can maintain favorites lists', 'hideInLists' => true, 'default' => 1),
						//TODO database column rename?
				)),

			// Full Record Display //
				'fullRecordSection' => array('property'=>'fullRecordSection', 'type' => 'section', 'label' =>'Full Record Display', 'hideInLists' => true, 'properties' => array(
						'showEmailThis'  => array('property'=>'showEmailThis', 'type'=>'checkbox', 'label'=>'Show Email This', 'description'=>'Whether or not the Email This link is shown', 'hideInLists' => true, 'default' => 1),
                        'showShareOnExternalSites'  => array('property'=>'showShareOnExternalSites', 'type'=>'checkbox', 'label'=>'Show Sharing To External Sites', 'description'=>'Whether or not sharing on external sites (Twitter, Facebook, Pinterest, etc. is shown)', 'hideInLists' => true, 'default' => 1),
                        'showComments'  => array('property'=>'showComments', 'type'=>'checkbox', 'label'=>'Enable User Reviews', 'description'=>'Whether or not user reviews are shown (also disables adding user reviews)', 'hideInLists' => true, 'default' => 1),
						'showStaffView' => array('property'=>'showStaffView', 'type'=>'checkbox', 'label'=>'Show Staff View', 'description'=>'Whether or not the staff view is displayed in full record view.', 'hideInLists' => true, 'default'=>true),
						'moreDetailsOptions' => array(
								'property'=>'moreDetailsOptions',
								'type'=>'oneToMany',
								'label'=>'Full Record Options',
								'description'=>'Record Options for the display of full record',
								'keyThis' => 'locationId',
								'keyOther' => 'locationId',
								'subObjectType' => 'LocationMoreDetails',
								'structure' => $locationMoreDetailsStructure,
								'sortable' => true,
								'storeDb' => true,
								'allowEdit' => true,
								'canEdit' => true,
						),
				)),

				// Browse Category Section //
				array('property' => 'browseCategorySection', 'type' => 'section', 'label' => 'Browse Categories', 'hideInLists' => true, 'instructions' => $browseCategoryInstructions,
			      'properties' => array(
				      'defaultBrowseMode' => array('property' => 'defaultBrowseMode', 'type' => 'enum', 'label'=>'Default Viewing Mode for Browse Categories', 'description' => 'Sets how browse categories will be displayed when users haven\'t chosen themselves.', 'hideInLists' => true,
				                                   'values'=> array('' => null, // empty value option is needed so that if no option is specifically chosen for location, the library setting will be used instead.
				                                                    'covers' => 'Show Covers Only',
				                                                    'grid' => 'Show as Grid'),
				      ),
				      'browseCategoryRatingsMode' => array('property' => 'browseCategoryRatingsMode', 'type' => 'enum', 'label' => 'Ratings Mode for Browse Categories ("covers" browse mode only)', 'description' => 'Sets how ratings will be displayed and how user ratings will be enabled when a user is viewing a browse category in the "covers" browse mode. (This only applies when User Ratings have been enabled.)',
				                                           'values' => array('' => null, // empty value option is needed so that if no option is specifically chosen for location, the library setting will be used instead.
				                                                             'popup' => 'Show rating stars and enable user rating via pop-up form.',
				                                                             'stars' => 'Show rating stars and enable user ratings by clicking the stars.',
				                                                             'none' => 'Do not show rating stars.'
				                                           ),
				      ),

				      'browseCategories' => array(
					      'property'=>'browseCategories',
					      'type'=>'oneToMany',
					      'label'=>'Browse Categories',
					      'description'=>'Browse Categories To Show on the Home Screen',
					      'keyThis' => 'locationId',
					      'keyOther' => 'locationId',
					      'subObjectType' => 'LocationBrowseCategory',
					      'structure' => $locationBrowseCategoryStructure,
					      'sortable' => true,
					      'storeDb' => true,
					      'allowEdit' => false,
					      'canEdit' => false,
				      ),
			      )),


				'overdriveSection' => array('property'=>'overdriveSection', 'type' => 'section', 'label' =>'OverDrive', 'hideInLists' => true, 'properties' => array(
					'enableOverdriveCollection' => array('property'=>'enableOverdriveCollection', 'type'=>'checkbox', 'label'=>'Enable Overdrive Collection', 'description'=>'Whether or not titles from the Overdrive collection should be included in searches', 'hideInLists' => true, 'default'=>true),
					'includeOverDriveAdult' => array('property'=>'includeOverDriveAdult', 'type'=>'checkbox', 'label'=>'Include Adult Titles', 'description'=>'Whether or not adult titles from the Overdrive collection should be included in searches', 'hideInLists' => true, 'default' => true),
					'includeOverDriveTeen' => array('property'=>'includeOverDriveTeen', 'type'=>'checkbox', 'label'=>'Include Teen Titles', 'description'=>'Whether or not teen titles from the Overdrive collection should be included in searches', 'hideInLists' => true, 'default' => true),
					'includeOverDriveKids' => array('property'=>'includeOverDriveKids', 'type'=>'checkbox', 'label'=>'Include Kids Titles', 'description'=>'Whether or not kids titles from the Overdrive collection should be included in searches', 'hideInLists' => true, 'default' => true),
				)),

			array(
				'property' => 'hours',
				'type'=> 'oneToMany',
				'keyThis' => 'locationId',
				'keyOther' => 'locationId',
				'subObjectType' => 'LocationHours',
				'structure' => $hoursStructure,
				'label' => 'Hours',
				'description' => 'Library Hours',
				'sortable' => false,
				'storeDb' => true
			),

			'recordsOwned' => array(
				'property'=>'recordsOwned',
				'type'=>'oneToMany',
				'label'=>'Records Owned',
				'description'=>'Information about what records are owned by the location',
				'keyThis' => 'locationId',
				'keyOther' => 'locationId',
				'subObjectType' => 'LocationRecordOwned',
				'structure' => $locationRecordOwnedStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
			),

			'recordsToInclude' => array(
				'property'=>'recordsToInclude',
				'type'=>'oneToMany',
				'label'=>'Records To Include',
				'description'=>'Information about what records to include in this scope',
				'keyThis' => 'locationId',
				'keyOther' => 'locationId',
				'subObjectType' => 'LocationRecordToInclude',
				'structure' => $locationRecordToIncludeStructure,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
			),
			'includeLibraryRecordsToInclude' => array('property'=>'includeLibraryRecordsToInclude', 'type'=>'checkbox', 'label'=>'Include Library Records To Include', 'description'=>'Whether or not the records to include from the parent library should be included for this location', 'hideInLists' => true, 'default' => true),
		);

		if (UserAccount::userHasRole('locationManager') || UserAccount::userHasRole('libraryManager')){
			unset($structure['code']);
			unset($structure['subLocation']);
			$structure['displayName']['type'] = 'label';
			unset($structure['showDisplayNameInHeader']);
			unset($structure['displaySection']);
			unset($structure['ilsSection']);
			unset($structure['enrichmentSection']);
			unset($structure['fullRecordSection']);
			unset($structure['searchingSection']);
			unset($structure['overdriveSection']);
			unset($structure['facets']);
			unset($structure['recordsOwned']);
			unset($structure['recordsToInclude']);

		}

		if (UserAccount::userHasRole('locationManager')){
			unset($structure['nearbyLocation1']);
			unset($structure['nearbyLocation2']);
			unset($structure['showInLocationsAndHoursList']);
			unset($structure['address']);
			unset($structure['phone']);
			unset($structure['automaticTimeoutLength']);
			unset($structure['automaticTimeoutLengthLoggedOut']);
		}
		if (!UserAccount::userHasRole('opacAdmin') && !UserAccount::userHasRole('libraryAdmin')){
			unset($structure['isMainBranch']);
		}
		return $structure;
	}

	public $pickupUsers;
	// Used to track multiple linked users having the same pick-up locations

	/**
	 * @param User $patronProfile
	 * @param int $selectedBranchId
	 * @param bool $isLinkedUser
	 * @return Location[]
	 */
	function getPickupBranches($patronProfile, $selectedBranchId = null, $isLinkedUser = false) {
		// Note: Some calls to this function will set $patronProfile to false. (No Patron is logged in)
		// For Example: MaterialsRequest_NewRequest
		$homeLibaryInList = false;
		$alternateLibraryInList = false;

		//Get the library for the patron's home branch.
		/** @var Library $librarySingleton */
		global $librarySingleton;
		if ($patronProfile){
			$homeLibrary = $librarySingleton->getLibraryForLocation($patronProfile->homeLocationId);
		}

		if (isset($homeLibrary) && $homeLibrary->inSystemPickupsOnly == 1){
			/** The user can only pickup within their home system */
			if (strlen($homeLibrary->validPickupSystems) > 0){
				/** The system has additional related systems that you can pickup within */
				$pickupIds = array();
				$pickupIds[] = $homeLibrary->libraryId;
				$validPickupSystems = explode('|', $homeLibrary->validPickupSystems);
				foreach ($validPickupSystems as $pickupSystem){
					$pickupLocation = new Library();
					$pickupLocation->subdomain = $pickupSystem;
					$pickupLocation->find();
					if ($pickupLocation->N == 1){
						$pickupLocation->fetch();
						$pickupIds[] = $pickupLocation->libraryId;
					}
				}
				$this->whereAdd("libraryId IN (" . implode(',', $pickupIds) . ")", 'AND');
				//Deal with Steamboat Springs Juvenile which is a special case.
				$this->whereAdd("code <> 'ssjuv'", 'AND');
			}else{
				/** Only this system is valid */
				$this->whereAdd("libraryId = {$homeLibrary->libraryId}", 'AND');
				$this->whereAdd("validHoldPickupBranch = 1", 'AND');
				//$this->whereAdd("locationId = {$patronProfile['homeLocationId']}", 'OR');
			}
		}else{
			$this->whereAdd("validHoldPickupBranch = 1");
		}

		$this->orderBy('displayName');

		$this->find();


		// Add the user id to each pickup location to track multiple linked accounts having the same pick-up location.
 		if ($patronProfile) {
 			$this->pickupUsers[] = $patronProfile->id;
	  }

		//Load the locations and sort them based on the user profile information as well as their physical location.
		$physicalLocation = $this->getPhysicalLocation();
		$locationList = array();
		while ($this->fetch()) {
			if (($this->validHoldPickupBranch == 1) || ($this->validHoldPickupBranch == 0 && !empty($patronProfile) && $patronProfile->homeLocationId == $this->locationId)){
				if (!empty($selectedBranchId) && $this->locationId == $selectedBranchId) {
					$selected = 'selected';
				} else {
					$selected = '';
				}
				$this->setSelected($selected);
				// Each location is prepended with a number to keep precedence for given locations when sorted by ksort below
				if (isset($physicalLocation) && $physicalLocation->locationId == $this->locationId) {
					//If the user is in a branch, those holdings come first.
					$locationList['1' . $this->displayName] = clone $this;
				} else if (!empty($patronProfile) && $this->locationId == $patronProfile->homeLocationId) {
					//Next come the user's home branch if the user is logged in or has the home_branch cookie set.
					$locationList['21' . $this->displayName] = clone $this;
					$homeLibaryInList = true;
				} else if (isset($patronProfile->myLocation1Id) && $this->locationId == $patronProfile->myLocation1Id) {
					//Next come nearby locations for the user
					$locationList['3' . $this->displayName] = clone $this;
					$alternateLibraryInList = true;
				} else if (isset($patronProfile->myLocation2Id) && $this->locationId == $patronProfile->myLocation2Id) {
					//Next come nearby locations for the user
					$locationList['4' . $this->displayName] = clone $this;
				} else if (isset($homeLibrary) && $this->libraryId == $homeLibrary->libraryId) {
					//Other locations that are within the same library system
					$locationList['5' . $this->displayName] = clone $this;
				} else {
					//Finally, all other locations are shown sorted alphabetically.
					$locationList['6' . $this->displayName] = clone $this;
				}
			}
		}
		ksort($locationList);

		//MDN 8/14/2015 always add the home location #PK-81
		// unless the option to pickup at the home location is specifically disabled #PK-1250
		//if (count($locationList) == 0 && (isset($homeLibrary) && $homeLibrary->inSystemPickupsOnly == 1)){
		if (!empty($patronProfile) && $patronProfile->homeLocationId != 0){
			/** @var Location $homeLocation */
			$homeLocation = new Location();
			$homeLocation->locationId = $patronProfile->homeLocationId;
			if ($homeLocation->find(true)){
				if ($homeLocation->validHoldPickupBranch != 2){
					//We didn't find any locations.  This for schools where we want holds available, but don't want the branch to be a
					//pickup location anywhere else.
					$homeLocation->pickupUsers[] = $patronProfile->id; // Add the user id to each pickup location to track multiple linked accounts having the same pick-up location.
					$existingLocation = false;
					foreach ($locationList as $location) {
						if ($location->libraryId == $homeLocation->libraryId && $location->locationId == $homeLocation->locationId) {
							$existingLocation = true;
							if (!$isLinkedUser) {$location->setSelected('selected');}
							//TODO: update sorting key as well?
							break;
						}
					}
					if (!$existingLocation) {
						if (!$isLinkedUser) {
							$homeLocation->setSelected('selected');
							$locationList['1' . $homeLocation->displayName] = clone $homeLocation;
							$homeLibaryInList = true;
						} else {
							$locationList['22' . $homeLocation->displayName] = clone $homeLocation;
						}
					}
				}
			}
		}

		if (!$homeLibaryInList && !$alternateLibraryInList && !$isLinkedUser){
			$locationList['0default'] = "Please Select a Location";
		}

		return $locationList;
	}

	private static $activeLocation = 'unset';
	/**
	 * Returns the active location to use when doing search scoping, etc.
	 * This does not include the IP address
	 *
	 * @return Location|string
	 */
	function getActiveLocation(){
		if (Location::$activeLocation != 'unset') {
			return Location::$activeLocation;
		}

		//default value
		Location::$activeLocation = null;

		//load information about the library we are in.
		global $library;
		if (is_null($library)){
			//If we are not in a library, then do not allow branch scoping, etc.
			Location::$activeLocation = null;
		}else{

			//Check to see if a branch location has been specified.
			$locationCode = $this->getBranchLocationCode();
			if (!empty($locationCode) && $locationCode != 'all'){
				$activeLocation = new Location();
				$activeLocation->subLocation = $locationCode;
				if ($activeLocation->find(true)){
					//Only use the location if we are in the subdomain for the parent library
					if ($library->libraryId == $activeLocation->libraryId){
						Location::$activeLocation = clone $activeLocation;
					}else{
						// If the active location doesn't belong to the library we are browsing at, turn off the active location
						Location::$activeLocation = null;
					}
				}else{
					//Check to see if we can get the active location based off the sublocation
					$activeLocation = new Location();
					$activeLocation->code = $locationCode;
					if ($activeLocation->find(true)){
						//Only use the location if we are in the subdomain for the parent library
						if ($library->libraryId == $activeLocation->libraryId){
							Location::$activeLocation = clone $activeLocation;
						}else{
							// If the active location doesn't belong to the library we are browsing at, turn off the active location
							Location::$activeLocation = null;
						}
					}else{
						//Check to see if we can get the active location based off the sublocation
						$activeLocation = new Location();
						$activeLocation->subdomain = $locationCode;
						if ($activeLocation->find(true)){
							//Only use the location if we are in the subdomain for the parent library
							if ($library->libraryId == $activeLocation->libraryId){
								Location::$activeLocation = clone $activeLocation;
							}else{
								// If the active location doesn't belong to the library we are browsing at, turn off the active location
								Location::$activeLocation = null;
							}
						}
					}
				}
			}else{
				// Check if we know physical location by the ip table
				$physicalLocation = $this->getPhysicalLocation();
				if ($physicalLocation != null){
					if ($library->libraryId == $physicalLocation->libraryId){
						Location::$activeLocation = $physicalLocation;
					}else{
						// If the physical location doesn't belong to the library we are browsing at, turn off the active location
						Location::$activeLocation = null;
					}
				}
			}
			global $timer;
			$timer->logTime('Finished getActiveLocation');
		}

		return Location::$activeLocation;
	}
	function setActiveLocation($location){
		Location::$activeLocation = $location;
	}

	/**
	 * @var string|Location
	 */
	private static $userHomeLocation = 'unset';

	/**
	 * Get the home location for the currently logged in user.
	 *
	 * @return Location
	 */
	static function getUserHomeLocation(){
		if (isset(Location::$userHomeLocation) && Location::$userHomeLocation != 'unset') {
			return Location::$userHomeLocation;
		}

		// default value
		Location::$userHomeLocation = null;

		if (UserAccount::isLoggedIn()){
			$homeLocation = new Location();
			$homeLocation->locationId = UserAccount::getUserHomeLocationId();
			if ($homeLocation->find(true)){
				Location::$userHomeLocation = clone($homeLocation);
			}
		}

		return Location::$userHomeLocation;
	}


	private $branchLocationCode = 'unset';
	function getBranchLocationCode(){
		if (isset($this->branchLocationCode) && $this->branchLocationCode != 'unset') return $this->branchLocationCode;
		if (isset($_GET['branch'])){
			$this->branchLocationCode = $_GET['branch'];
		}elseif (isset($_COOKIE['branch'])){
			$this->branchLocationCode = $_COOKIE['branch'];
		}else{
			$this->branchLocationCode = '';
		}
		if ($this->branchLocationCode == 'all'){
			$this->branchLocationCode = '';
		}
		return $this->branchLocationCode;
	}

	/**
	 * The physical location where the user is based on
	 * IP address and branch parameter, and only for It's Here messages
	 *
	 */
	private $physicalLocation = 'unset';
	function getPhysicalLocation(){
		if ($this->physicalLocation != 'unset'){
				return $this->physicalLocation;
		}

		if ($this->getBranchLocationCode() != ''){
			$this->physicalLocation = $this->getActiveLocation();
		}else{
			$this->physicalLocation = $this->getIPLocation();
		}
		return $this->physicalLocation;
	}

	static $searchLocation  = array();

	/**
	 * @param null $searchSource
	 * @return Location|null
	 */
	static function getSearchLocation($searchSource = null){
		if ($searchSource == null){
			global $searchSource;
		}
		if ($searchSource == 'combinedResults'){
			$searchSource = 'local';
		}
		if (!array_key_exists($searchSource, Location::$searchLocation)){
			$scopingSetting = $searchSource;
			if ($searchSource == null){
				Location::$searchLocation[$searchSource] = null;
			}else  if ($scopingSetting == 'local' || $scopingSetting == 'econtent' || $scopingSetting == 'location'){
				global $locationSingleton;
				Location::$searchLocation[$searchSource] = $locationSingleton->getActiveLocation();
			}else if ($scopingSetting == 'marmot' || $scopingSetting == 'unscoped'){
				Location::$searchLocation[$searchSource] = null;
			}else{
				$location = new Location();
				$location->code = $scopingSetting;
				$location->find();
				if ($location->N > 0){
					$location->fetch();
					Location::$searchLocation[$searchSource] = clone($location);
				}else{
					Location::$searchLocation[$searchSource] = null;
				}
			}
		}
		return Location::$searchLocation[$searchSource];
	}

	/**
	 * The location we are in based solely on IP address.
	 * @var string
	 */
	private $ipLocation = 'unset';
	private $ipId = 'unset';
	function getIPLocation(){
		if ($this->ipLocation != 'unset'){
			return $this->ipLocation;
		}
		global $timer;
		/** @var Memcache $memCache */
		global $memCache;
		global $configArray;
		global $logger;
		//Check the current IP address to see if we are in a branch
		$activeIp = $this->getActiveIp();
		$this->ipLocation = $memCache->get('location_for_ip_' . $activeIp);
		$this->ipId = $memCache->get('ipId_for_ip_' . $activeIp);
		if ($this->ipId == -1){
			$this->ipLocation = false;
		}

		if ($this->ipLocation == false || $this->ipId == false){
			$timer->logTime('Starting getIPLocation');
			//echo("Active IP is $activeIp");
			require_once ROOT_DIR . '/Drivers/marmot_inc/subnet.php';
			$subnet = new subnet();
			$ipVal = ip2long($activeIp);

			$this->ipLocation = null;
			$this->ipId = -1;
			if (is_numeric($ipVal)){
				disableErrorHandler();
				$subnet->whereAdd('startIpVal <= ' . $ipVal);
				$subnet->whereAdd('endIpVal >= ' . $ipVal);
				$subnet->orderBy('(endIpVal - startIpVal)');
				if ($subnet->find(true)){
					$matchedLocation = new Location();
					$matchedLocation->locationId = $subnet->locationid;
					if ($matchedLocation->find(true)){
						//Only use the physical location regardless of where we are
						$this->ipLocation = clone($matchedLocation);
						$this->ipLocation->setOpacStatus( (boolean) $subnet->isOpac);

						$this->ipId = $subnet->id;
					}else{
						$logger->log("Did not find location for ip location id {$subnet->locationid}", Logger::LOG_WARNING);
					}
				}
				enableErrorHandler();
			}

			$memCache->set('ipId_for_ip_' . $activeIp, $this->ipId, 0, $configArray['Caching']['ipId_for_ip']);
			$memCache->set('location_for_ip_' . $activeIp, $this->ipLocation, 0, $configArray['Caching']['location_for_ip']);
			$timer->logTime('Finished getIPLocation');
		}

		return $this->ipLocation;
	}

	/**
	 * Must be called after the call to getIPLocation
	 * Enter description here ...
	 */
	function getIPid(){
		return $this->ipId;
	}

	private static $activeIp = null;
	static function getActiveIp(){
		if (!is_null(Location::$activeIp)) return Location::$activeIp;
		global $timer;
		//Make sure gets and cookies are processed in the correct order.
		if (isset($_GET['test_ip'])){
			$ip = $_GET['test_ip'];
			//Set a cookie so we don't have to transfer the ip from page to page.
			setcookie('test_ip', $ip, 0, '/');
//		}elseif (isset($_COOKIE['test_ip']) && $_COOKIE['test_ip'] != '127.0.0.1' && strlen($_COOKIE['test_ip']) > 0){
		}elseif (!empty($_COOKIE['test_ip']) && $_COOKIE['test_ip'] != '127.0.0.1'){
			$ip = $_COOKIE['test_ip'];
		}else{
			if (isset($_SERVER["HTTP_CLIENT_IP"])){
				$ip = $_SERVER["HTTP_CLIENT_IP"];
			}elseif (isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
				$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
			}elseif (isset($_SERVER["HTTP_X_FORWARDED"])){
				$ip = $_SERVER["HTTP_X_FORWARDED"];
			}elseif (isset($_SERVER["HTTP_FORWARDED_FOR"])){
				$ip = $_SERVER["HTTP_FORWARDED_FOR"];
			}elseif (isset($_SERVER["HTTP_FORWARDED"])){
				$ip = $_SERVER["HTTP_FORWARDED"];
			}elseif (isset($_SERVER['REMOTE_HOST']) && strlen($_SERVER['REMOTE_HOST']) > 0){
				$ip = $_SERVER['REMOTE_HOST'];
			}elseif (isset($_SERVER['REMOTE_ADDR']) && strlen($_SERVER['REMOTE_ADDR']) > 0){
				$ip = $_SERVER['REMOTE_ADDR'];
			}else{
				$ip = '';
			}
		}
		Location::$activeIp = $ip;
		$timer->logTime("getActiveIp");
		return Location::$activeIp;
	}

/* Add on if the Main Branch gets used more frequently
	private static $mainBranchLocation = 'unset';
	function getMainBranchLocation() {
		if (Location::$mainBranchLocation != 'unset') return Location::$mainBranchLocation;
		Location::$mainBranchLocation = null; // set default value
		global $library;
		if (!empty($library->libraryId)) {
			$mainBranch = new Location();
			$mainBranch->libraryId = $library->libraryId;
			$mainBranch->isMainBranch = true;
			if ($mainBranch->find(true)) {
				Location::$mainBranchLocation =  clone $mainBranch;
			}
		}
		return Location::$mainBranchLocation;
	}
*/

	private $sublocationCode = 'unset';
	function getSublocationCode(){
		if ($this->sublocationCode == 'unset') {
			if (isset($_GET['sublocation'])) {
				$this->sublocationCode = $_GET['sublocation'];
			} elseif (isset($_COOKIE['sublocation'])) {
				$this->sublocationCode = $_COOKIE['sublocation'];
			} else {
				$this->sublocationCode = '';
			}
		}
		return $this->sublocationCode;
	}

	function getLocationsFacetsForLibrary($libraryId){
		$location = new Location();
		$location->libraryId = $libraryId;
		$location->find();
		$facets = array();
		if ($location->N > 0){
			while ($location->fetch()){
				$facets[] = $location->facetLabel;
			}
		}
		return $facets;
	}


	public function __get($name){
		if ($name == "hours") {
			if (!isset($this->hours)){
				$this->hours = array();
				if ($this->locationId){
					$hours = new LocationHours();
					$hours->locationId = $this->locationId;
					$hours->orderBy('day');
					$hours->find();
					while($hours->fetch()){
						$this->hours[$hours->id] = clone($hours);
					}
				}
			}
			return $this->hours;
		}elseif ($name == "moreDetailsOptions") {
			if (!isset($this->moreDetailsOptions) && $this->libraryId){
				$this->moreDetailsOptions = array();
				$moreDetailsOptions = new LocationMoreDetails();
				$moreDetailsOptions->locationId = $this->locationId;
				$moreDetailsOptions->orderBy('weight');
				$moreDetailsOptions->find();
				while($moreDetailsOptions->fetch()){
					$this->moreDetailsOptions[$moreDetailsOptions->id] = clone($moreDetailsOptions);
				}
			}
			return $this->moreDetailsOptions;
		}elseif ($name == "facets") {
			if (!isset($this->facets)){
				$this->facets = array();
				if ($this->locationId){
					$facet = new LocationFacetSetting();
					$facet->locationId = $this->locationId;
					$facet->orderBy('weight');
					$facet->find();
					while($facet->fetch()){
						$this->facets[$facet->id] = clone($facet);
					}
				}
			}
			return $this->facets;
		}elseif ($name == 'recordsOwned'){
			if (!isset($this->recordsOwned) && $this->locationId){
				$this->recordsOwned = array();
				$object = new LocationRecordOwned();
				$object->locationId = $this->locationId;
				$object->find();
				while($object->fetch()){
					$this->recordsOwned[$object->id] = clone($object);
				}
			}
			return $this->recordsOwned;
		}elseif ($name == 'recordsToInclude'){
			if (!isset($this->recordsToInclude) && $this->locationId){
				$this->recordsToInclude = array();
				$object = new LocationRecordToInclude();
				$object->locationId = $this->locationId;
				$object->orderBy('weight');
				$object->find();
				while($object->fetch()){
					$this->recordsToInclude[$object->id] = clone($object);
				}
			}
			return $this->recordsToInclude;
		}elseif  ($name == 'browseCategories'){
			if (!isset($this->browseCategories) && $this->locationId){
				$this->browseCategories = array();
				$browseCategory = new LocationBrowseCategory();
				$browseCategory->locationId = $this->locationId;
				$browseCategory->orderBy('weight');
				$browseCategory->find();
				while($browseCategory->fetch()){
					$this->browseCategories[$browseCategory->id] = clone($browseCategory);
				}
			}
			return $this->browseCategories;
		}elseif ($name == 'combinedResultSections') {
			if (!isset($this->combinedResultSections) && $this->locationId){
				$this->combinedResultSections = array();
				$combinedResultSection = new LocationCombinedResultSection();
				$combinedResultSection->locationId = $this->locationId;
				$combinedResultSection->orderBy('weight');
				if ($combinedResultSection->find()) {
					while ($combinedResultSection->fetch()) {
						$this->combinedResultSections[$combinedResultSection->id] = clone $combinedResultSection;
					}
				}
				return $this->combinedResultSections;
			}
		}else{
			return $this->_data[$name];
		}
		return null;
	}

	public function __set($name, $value){
		if ($name == "hours") {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->hours = $value;
		}elseif ($name == "moreDetailsOptions") {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->moreDetailsOptions = $value;
		}elseif ($name == "facets") {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->facets = $value;
		}elseif ($name == 'browseCategories'){
			/** @noinspection PhpUndefinedFieldInspection */
			$this->browseCategories = $value;
		}elseif ($name == 'recordsOwned'){
			/** @noinspection PhpUndefinedFieldInspection */
			$this->recordsOwned = $value;
		}elseif ($name == 'recordsToInclude'){
			/** @noinspection PhpUndefinedFieldInspection */
			$this->recordsToInclude = $value;
		}elseif ($name == 'combinedResultSections') {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->combinedResultSections = $value;
		}else{
			$this->_data[$name] = $value;
		}
	}

	/**
	 * Override the update functionality to save the hours
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update(){
		$ret = parent::update();
		if ($ret !== FALSE ){
			$this->saveHours();
			$this->saveFacets();
			$this->saveBrowseCategories();
			$this->saveMoreDetailsOptions();
			$this->saveRecordsOwned();
			$this->saveRecordsToInclude();
			$this->saveCombinedResultSections();
		}
		return $ret;
	}

	/**
	 * Override the update functionality to save the hours
	 *
	 * @see DB/DB_DataObject::insert()
	 */
	public function insert(){
		$ret = parent::insert();
		if ($ret !== FALSE ){
			$this->saveHours();
			$this->saveFacets();
			$this->saveBrowseCategories();
			$this->saveMoreDetailsOptions();
			$this->saveRecordsOwned();
			$this->saveRecordsToInclude();
			$this->saveCombinedResultSections();
		}
		return $ret;
	}

	public function saveBrowseCategories(){
		if (isset ($this->browseCategories) && is_array($this->browseCategories)){
			$this->saveOneToManyOptions($this->browseCategories);
			unset($this->browseCategories);
		}
	}

	public function clearBrowseCategories(){
		$this->clearOneToManyOptions('LocationBrowseCategory');
		$this->browseCategories = array();
	}

	public function saveMoreDetailsOptions(){
		if (isset ($this->moreDetailsOptions) && is_array($this->moreDetailsOptions)){
			$this->saveOneToManyOptions($this->moreDetailsOptions);
			unset($this->moreDetailsOptions);
		}
	}

	public function clearMoreDetailsOptions(){
		$this->clearOneToManyOptions('LocationMoreDetails');
		$this->moreDetailsOptions = array();
	}

	public function saveCombinedResultSections(){
		if (isset ($this->combinedResultSections) && is_array($this->combinedResultSections)){
			$this->saveOneToManyOptions($this->combinedResultSections);
			unset($this->combinedResultSections);
		}
	}

	public function clearCombinedResultSections(){
		$this->clearOneToManyOptions('LibraryCombinedResultSection');
		$this->combinedResultSections = array();
	}

	public function saveFacets(){
		if (isset ($this->facets) && is_array($this->facets)){
			$this->saveOneToManyOptions($this->facets);
			unset($this->facets);
		}
	}

	public function clearFacets(){
		$this->clearOneToManyOptions('LocationFacetSetting');
		$this->facets = array();
	}

	public function saveHours(){
		if (isset ($this->hours) && is_array($this->hours)){
			$this->saveOneToManyOptions($this->hours);
			unset($this->hours);
		}
	}

	public static function getLibraryHours($locationId, $timeToCheck){
		$location = new Location();
		$location->locationId = $locationId;
		if ($locationId > 0 && $location->find(true)){
			// format $timeToCheck according to MySQL default date format
			$todayFormatted = date('Y-m-d', $timeToCheck);

			// check to see if today is a holiday
			require_once ROOT_DIR . '/Drivers/marmot_inc/Holiday.php';
			$holiday = new Holiday();
			$holiday->date = $todayFormatted;
			$holiday->libraryId = $location->libraryId;
			if ($holiday->find(true)){
				return array(
					'closed' => true,
					'closureReason' => $holiday->name
				);
			}

			// get the day of the week (0=Sunday to 6=Saturday)
			$dayOfWeekToday = strftime ('%w', $timeToCheck);

			// find library hours for the above day of the week
			require_once ROOT_DIR . '/Drivers/marmot_inc/LocationHours.php';
			$hours = new LocationHours();
			$hours->locationId = $locationId;
			$hours->day = $dayOfWeekToday;
			if ($hours->find(true)){
				$hours->fetch();
				return array(
					'open' => ltrim($hours->open, '0'),
					'close' => ltrim($hours->close, '0'),
					'closed' => $hours->closed ? true : false,
					'openFormatted' => ($hours->open == '12:00' ? 'Noon' : date("g:i A", strtotime($hours->open))),
					'closeFormatted' => ($hours->close == '12:00' ? 'Noon' : date("g:i A", strtotime($hours->close)))
				);
			}
		}


		// no hours found
		return null;
	}

	public static function getLibraryHoursMessage($locationId){
		$today = time();
		$todaysLibraryHours = Location::getLibraryHours($locationId, $today);
		if (isset($todaysLibraryHours) && is_array($todaysLibraryHours)){
			if (isset($todaysLibraryHours['closed']) && ($todaysLibraryHours['closed'] == true || $todaysLibraryHours['closed'] == 1)){
				if (isset($todaysLibraryHours['closureReason'])){
					$closureReason = $todaysLibraryHours['closureReason'];
				}
				//Library is closed now
				$nextDay = time() + (24 * 60 * 60);
				$nextDayHours = Location::getLibraryHours($locationId,  $nextDay);
				$daysChecked = 0;
				while (isset($nextDayHours['closed']) && $nextDayHours['closed'] == true && $daysChecked < 7){
					$nextDay += (24 * 60 * 60);
					$nextDayHours = Location::getLibraryHours($locationId,  $nextDay);
					$daysChecked++;
				}

				$nextDayOfWeek = strftime ('%a', $nextDay);
				if (isset($nextDayHours['closed']) && $nextDayHours['closed'] == true){
					if (isset($closureReason)){
						$libraryHoursMessage = "The library is closed today for $closureReason.";
					}else{
						$libraryHoursMessage = "The library is closed today.";
					}
				}else{
					if (isset($closureReason)){
						$libraryHoursMessage = "The library is closed today for $closureReason. It will reopen on $nextDayOfWeek from {$nextDayHours['openFormatted']} to {$nextDayHours['closeFormatted']}";
					}else{
						$libraryHoursMessage = "The library is closed today. It will reopen on $nextDayOfWeek from {$nextDayHours['openFormatted']} to {$nextDayHours['closeFormatted']}";
					}
				}
			}else{
				//Library is open
				$currentHour = strftime ('%H', $today);
				$openHour = strftime ('%H', strtotime($todaysLibraryHours['open']));
				$closeHour = strftime ('%H', strtotime($todaysLibraryHours['close']));
				if ($closeHour == 0 && $closeHour < $openHour){
					$closeHour = 24;
				}
				if ($currentHour < $openHour){
					$libraryHoursMessage = "The library will be open today from " . $todaysLibraryHours['openFormatted'] . " to " . $todaysLibraryHours['closeFormatted'] . ".";
				}else if ($currentHour > $closeHour){
					$tomorrowsLibraryHours = Location::getLibraryHours($locationId,  time() + (24 * 60 * 60));
					if (isset($tomorrowsLibraryHours['closed'])  && ($tomorrowsLibraryHours['closed'] == true || $tomorrowsLibraryHours['closed'] == 1)){
						if (isset($tomorrowsLibraryHours['closureReason'])){
							$libraryHoursMessage = "The library will be closed tomorrow for {$tomorrowsLibraryHours['closureReason']}.";
						}else{
							$libraryHoursMessage = "The library will be closed tomorrow";
						}

					}else{
						$libraryHoursMessage = "The library will be open tomorrow from " . $tomorrowsLibraryHours['openFormatted'] . " to " . $tomorrowsLibraryHours['closeFormatted'] . ".";
					}
				}else{
					$libraryHoursMessage = "The library is open today from " . $todaysLibraryHours['openFormatted'] . " to " . $todaysLibraryHours['closeFormatted'] . ".";
				}
			}
		}else{
			$libraryHoursMessage = null;
		}
		return $libraryHoursMessage;
	}

	public function saveRecordsOwned(){
		if (isset ($this->recordsOwned) && is_array($this->recordsOwned)){
			/** @var LibraryRecordOwned $object */
			foreach ($this->recordsOwned as $object){
				if (isset($object->deleteOnSave) && $object->deleteOnSave == true){
					$object->delete();
				}else{
					if (isset($object->id) && is_numeric($object->id)){
						$object->update();
					}else{
						$object->locationId = $this->locationId;
						$object->insert();
					}
				}
			}
			unset($this->recordsOwned);
		}
	}

	public function clearRecordsOwned(){
		$object = new LocationRecordOwned();
		$object->locationId = $this->locationId;
		$object->delete(true);
		$this->recordsOwned = array();
	}

	public function saveRecordsToInclude(){
		if (isset ($this->recordsToInclude) && is_array($this->recordsToInclude)){
			/** @var LibraryRecordOwned $object */
			foreach ($this->recordsToInclude as $object){
				if (isset($object->deleteOnSave) && $object->deleteOnSave == true){
					$object->delete();
				}else{
					if (isset($object->id) && is_numeric($object->id)){
						$object->update();
					}else{
						$object->locationId = $this->locationId;
						$object->insert();
					}
				}
			}
			unset($this->recordsToInclude);
		}
	}

	public function clearRecordsToInclude(){
		$object = new LibraryRecordToInclude();
		$object->locationId = $this->locationId;
		$object->delete(true);
		$this->recordsToInclude = array();
	}

	static function getDefaultFacets($locationId = -1){
		global $configArray;
		$defaultFacets = array();

		$facet = new LocationFacetSetting();
		$facet->setupTopFacet('format_category', 'Format Category');
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		if ($configArray['Index']['enableDetailedAvailability']){
			$facet = new LocationFacetSetting();
			$facet->setupTopFacet('availability_toggle', 'Available?');
			$facet->locationId = $locationId;
			$facet->weight = count($defaultFacets) + 1;
			$defaultFacets[] = $facet;
		}

		if ($configArray['Index']['enableDetailedAvailability']){
			$facet = new LocationFacetSetting();
			$facet->setupSideFacet('available_at', 'Available Now At', true);
			$facet->locationId = $locationId;
			$facet->weight = count($defaultFacets) + 1;
			$defaultFacets[] = $facet;
		}

		$facet = new LocationFacetSetting();
		$facet->setupSideFacet('format', 'Format', true);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupSideFacet('literary_form', 'Literary Form', true);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupSideFacet('target_audience', 'Reading Level', true);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$facet->numEntriesToShowByDefault = 8;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupSideFacet('topic_facet', 'Subject', true);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupSideFacet('time_since_added', 'Added in the Last', true);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupSideFacet('authorStr', 'Author', true);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupAdvancedFacet('awards_facet', 'Awards');
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupAdvancedFacet('econtent_source', 'eContent Collection');
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupAdvancedFacet('era', 'Era');
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupSideFacet('genre_facet', 'Genre', true);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupSideFacet('itype', 'Item Type', true);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupSideFacet('language', 'Language', true);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupAdvancedFacet('lexile_code', 'Lexile code');
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupAdvancedFacet('lexile_score', 'Lexile measure');
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupAdvancedFacet('mpaa_rating', 'Movie Rating');
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupSideFacet('owning_library', 'Owning System', true);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupSideFacet('owning_location', 'Owning Branch', true);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupSideFacet('publishDate', 'Publication Date', true);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupAdvancedFacet('geographic_facet', 'Region');
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupSideFacet('rating_facet', 'User Rating', true);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		return $defaultFacets;
	}

	/** @return LocationHours[] */
	function getHours(){
		return $this->hours;
	}

	public function hasValidHours() {
		$hours = new LocationHours();
		$hours->locationId = $this->locationId;
        $hours->find();
        $hasValidHours = false;
		while ($hours->fetch()){
		    if ($hours->open != '00:30' || $hours->close != '00:30'){
                $hasValidHours = true;

            }
        }
		return $hasValidHours;
	}

	private $opacStatus = null;

	/**
	 * Check whether or not the system is an opac station.
	 * - First check to see if an opac paramter has been passed.  If so, use that information and set a cookie for future pages.
	 * - Next check the cookie to see if we have overridden the value
	 * - Finally check to see if we have an active location based on the IP address.  If we do, use that to determine if this is an opac station
	 * @return bool
	 */
	public function getOpacStatus(){
		if (is_null($this->opacStatus)) {
			if (isset($_GET['opac'])) {
				$this->opacStatus = $_GET['opac'] == 1 || strtolower($_GET['opac']) == 'true' || strtolower($_GET['opac']) == 'on';
				if ($_GET['opac'] == '') {
					//Clear any existing cookie
					setcookie('opac', $this->opacStatus, time() - 1000, '/');
				}elseif (!isset($_COOKIE['opac']) || $this->opacStatus != $_COOKIE['opac']){
					setcookie('opac', $this->opacStatus ? '1' : '0', 0, '/');
				}
			} elseif (isset($_COOKIE['opac'])) {
				$this->opacStatus = (boolean) $_COOKIE['opac'];
			} else {
				if ($this->getIPLocation()){
					$this->opacStatus = $this->getIPLocation()->opacStatus;
				}else{
					$this->opacStatus = false;
				}
			}
		}
		return $this->opacStatus;
	}

	/**
	 * Primarily Intended to set the opac status for the ipLocation object
	 * when the iptable indicates that the ip is to be treated as a public opac
	 * @param null $opacStatus
	 */
	public function setOpacStatus($opacStatus = null)
	{
		$this->opacStatus = $opacStatus;
	}

	private function saveOneToManyOptions($oneToManySettings) {
	    /** @var DataObject $oneToManyDBObject */
        foreach ($oneToManySettings as $oneToManyDBObject){
			if (isset($oneToManyDBObject->deleteOnSave) && $oneToManyDBObject->deleteOnSave == true){
				$oneToManyDBObject->delete();
			}else{
				if (isset($oneToManyDBObject->id) && is_numeric($oneToManyDBObject->id)){ // (negative ids need processed with insert)
					$oneToManyDBObject->update();
				}else{
					$oneToManyDBObject->locationId = $this->locationId;
					$oneToManyDBObject->insert();
				}
			}
		}
	}

	private function clearOneToManyOptions($oneToManyDBObjectClassName) {
	    /** @var DataObject $oneToManyDBObject */
		$oneToManyDBObject = new $oneToManyDBObjectClassName();
		$oneToManyDBObject->libraryId = $this->libraryId;
		$oneToManyDBObject->delete(true);
	}

	private $_selected;
    private function setSelected(string $selected)
    {
        $this->_selected = $selected;
    }

    public function getSelected(){
        return $this->_selected;
    }

}
