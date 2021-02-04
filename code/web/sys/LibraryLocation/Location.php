<?php
/** @noinspection RequiredAttributes */
/** @noinspection HtmlRequiredAltAttribute */

require_once ROOT_DIR . '/sys/DB/DataObject.php';
require_once ROOT_DIR . '/sys/LibraryLocation/LocationHours.php';
require_once ROOT_DIR . '/sys/LibraryLocation/LocationCombinedResultSection.php';
if (file_exists(ROOT_DIR . '/sys/Browse/BrowseCategoryGroup.php')) {
	require_once ROOT_DIR . '/sys/Browse/BrowseCategoryGroup.php';
}
if (file_exists(ROOT_DIR . '/sys/Indexing/LocationRecordOwned.php')) {
	require_once ROOT_DIR . '/sys/Indexing/LocationRecordOwned.php';
}
if (file_exists(ROOT_DIR . '/sys/Indexing/LocationRecordToInclude.php')) {
	require_once ROOT_DIR . '/sys/Indexing/LocationRecordToInclude.php';
}
if (file_exists(ROOT_DIR . '/sys/Indexing/LocationSideLoadScope.php')) {
	require_once ROOT_DIR . '/sys/Indexing/LocationSideLoadScope.php';
}

class Location extends DataObject
{
	const DEFAULT_AUTOLOGOUT_TIME = 90;
	const DEFAULT_AUTOLOGOUT_TIME_LOGGED_OUT = 450;

	public $__table = 'location';   // table name
	public $__primaryKey = 'locationId';
	public $locationId;                //int(11)
	public $libraryId;                //int(11)
	public $subdomain;
	public $code;                    //varchar(5)
	public $historicCode;
	public $subLocation;
	public $displayName;            //varchar(40)
	public $theme;
	public $showDisplayNameInHeader;
	public $headerText;
	public $address;
	public $phone;
	public $tty;
	public $description;
	public $isMainBranch; // tinyint(1)
	public $showInLocationsAndHoursList;
	public $validHoldPickupBranch;    //'1' => 'Valid for all patrons', '0' => 'Valid for patrons of this branch only', '2' => 'Not Valid'
	public $nearbyLocation1;        //int(11)
	public $nearbyLocation2;        //int(11)
	public $scope;
	public $useScope;
	public $facetLabel;
	public $groupedWorkDisplaySettingId;
	public $browseCategoryGroupId;
	public $restrictSearchByLocation;
	public /** @noinspection PhpUnused */ $overDriveScopeId;
	public /** @noinspection PhpUnused */ $hooplaScopeId;
	public /** @noinspection PhpUnused */ $rbdigitalScopeId;
	public /** @noinspection PhpUnused */ $cloudLibraryScopeId;
	public /** @noinspection PhpUnused */ $axis360ScopeId;
	public $showHoldButton;
	public $repeatSearchOption;
	public $repeatInOnlineCollection;
	public $repeatInProspector;
	public $repeatInWorldCat;
	public $systemsToRepeatIn;
	public $homeLink;
	public $defaultPType;
	public $ptypesToAllowRenewals;
	public /** @noinspection PhpUnused */ $publicListsToInclude;
	public $automaticTimeoutLength;
	public $automaticTimeoutLengthLoggedOut;
	public $additionalCss;
	public $showEmailThis;
	public $showShareOnExternalSites;
	public $showFavorites;
	public /** @noinspection PhpUnused */ $econtentLocationsToInclude;
	public /** @noinspection PhpUnused */ $includeAllLibraryBranchesInFacets;
	public /** @noinspection PhpUnused */ $additionalLocationsToShowAvailabilityFor;
//	public /** @noinspection PhpUnused */ $includeAllRecordsInShelvingFacets;
//	public /** @noinspection PhpUnused */ $includeAllRecordsInDateAddedFacets;
	public /** @noinspection PhpUnused */ $includeLibraryRecordsToInclude;

	//Combined Results (Bento Box)
	public /** @noinspection PhpUnused */ $enableCombinedResults;
	public $combinedResultsLabel;
	public /** @noinspection PhpUnused */ $defaultToCombinedResults;
	public $useLibraryCombinedResultsSettings;

	private $_hours;
	private $_moreDetailsOptions;
	private $_recordsOwned;
	private $_recordsToInclude;
	private $_sideLoadScopes;
	private $_combinedResultSections;

	function getNumericColumnNames()
	{
		return ['scope', 'isMainBranch', 'showInLocationsAndHoursList', 'validHoldPickupBranch', 'useScope', 'restrictSearchByLocation', 'showHoldButton',
			'repeatInOnlineCollection', 'repeatInProspector', 'repeatInWorldCat', 'showEmailThis', 'showShareOnExternalSites', 'showFavorites',
			'includeAllLibraryBranchesInFacets', 'includeAllRecordsInShelvingFacets', 'includeAllRecordsInDateAddedFacets', 'includeLibraryRecordsToInclude',
			'enableCombinedResults', 'defaultToCombinedResults', 'useLibraryCombinedResultsSettings'];
	}

	static function getObjectStructure()
	{
		//Load Libraries for lookup values
		$library = new Library();
		$library->orderBy('displayName');
		if (!UserAccount::userHasPermission('Administer All Libraries')) {
			$homeLibrary = Library::getPatronHomeLibrary();
			$library->libraryId = $homeLibrary->libraryId;
		}
		$library->find();
		$libraryList = array();
		while ($library->fetch()) {
			$libraryList[$library->libraryId] = $library->displayName;
		}

		//Look lookup information for display in the user interface
		$location = new Location();
		$location->orderBy('displayName');
		$location->find();
		$locationList = array();
		$locationLookupList = array();
		$locationLookupList[-1] = '<No Nearby Location>';
		while ($location->fetch()) {
			$locationLookupList[$location->locationId] = $location->displayName;
			$locationList[$location->locationId] = clone $location;
		}

		// get the structure for the location's hours
		$hoursStructure = LocationHours::getObjectStructure();

		// we don't want to make the locationId property editable
		// because it is associated with this location only
		unset($hoursStructure['locationId']);

		require_once ROOT_DIR . '/sys/Browse/BrowseCategoryGroup.php';
		$browseCategoryGroup = new BrowseCategoryGroup();
		$browseCategoryGroups = [];
		$browseCategoryGroups[-1] = 'Use Library Setting';
		$browseCategoryGroup->orderBy('name');
		$browseCategoryGroup->find();
		while ($browseCategoryGroup->fetch()){
			$browseCategoryGroups[$browseCategoryGroup->id] = $browseCategoryGroup->name;
		}

		$locationMoreDetailsStructure = LocationMoreDetails::getObjectStructure();
		unset($locationMoreDetailsStructure['weight']);
		unset($locationMoreDetailsStructure['locationId']);

		$locationRecordOwnedStructure = LocationRecordOwned::getObjectStructure();
		unset($locationRecordOwnedStructure['locationId']);

		$locationRecordToIncludeStructure = LocationRecordToInclude::getObjectStructure();
		unset($locationRecordToIncludeStructure['locationId']);
		unset($locationRecordToIncludeStructure['weight']);

		$locationSideLoadScopeStructure = LocationSideLoadScope::getObjectStructure();
		unset($locationSideLoadScopeStructure['locationId']);

		$combinedResultsStructure = LocationCombinedResultSection::getObjectStructure();
		unset($combinedResultsStructure['locationId']);
		unset($combinedResultsStructure['weight']);

		require_once ROOT_DIR . '/sys/Theming/Theme.php';
		$theme = new Theme();
		$availableThemes = array();
		$theme->orderBy('themeName');
		$theme->find();
		$availableThemes[-1] = 'Use Library Setting';
		while ($theme->fetch()) {
			$availableThemes[$theme->id] = $theme->themeName;
		}

		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkDisplaySetting.php';
		$groupedWorkDisplaySetting = new GroupedWorkDisplaySetting();
		$groupedWorkDisplaySetting->orderBy('name');
		$groupedWorkDisplaySettings = [];
		$groupedWorkDisplaySettings[-1] = 'Use Library Settings';
		$groupedWorkDisplaySetting->find();
		while ($groupedWorkDisplaySetting->fetch()){
			$groupedWorkDisplaySettings[$groupedWorkDisplaySetting->id] = $groupedWorkDisplaySetting->name;
		}

		require_once ROOT_DIR . '/sys/Hoopla/HooplaScope.php';
		$hooplaScope = new HooplaScope();
		$hooplaScope->orderBy('name');
		$hooplaScopes = [];
		$hooplaScope->find();
		$hooplaScopes[-2] = 'None';
		$hooplaScopes[-1] = 'Use Library Setting';
		while ($hooplaScope->fetch()) {
			$hooplaScopes[$hooplaScope->id] = $hooplaScope->name;
		}

		require_once ROOT_DIR . '/sys/Axis360/Axis360Scope.php';
		$axis360Scope = new Axis360Scope();
		$axis360Scope->orderBy('name');
		$axis360Scopes = [];
		$axis360Scope->find();
		$axis360Scopes[-2] = 'None';
		$axis360Scopes[-1] = 'Use Library Setting';
		while ($axis360Scope->fetch()) {
			$axis360Scopes[$axis360Scope->id] = $axis360Scope->name;
		}

		require_once ROOT_DIR . '/sys/OverDrive/OverDriveScope.php';
		$overDriveScope = new OverDriveScope();
		$overDriveScope->orderBy('name');
		$overDriveScopes = [];
		$overDriveScope->find();
		$overDriveScopes[-2] = 'None';
		$overDriveScopes[-1] = 'Use Library Setting';
		while ($overDriveScope->fetch()){
			$overDriveScopes[$overDriveScope->id] = $overDriveScope->name;
		}

		require_once ROOT_DIR . '/sys/RBdigital/RBdigitalScope.php';
		$rbdigitalScope = new RBdigitalScope();
		$rbdigitalScope->orderBy('name');
		$rbdigitalScopes = [];
		$rbdigitalScope->find();
		$rbdigitalScopes[-2] = 'None';
		$rbdigitalScopes[-1] = 'Use Library Setting';
		while ($rbdigitalScope->fetch()) {
			$rbdigitalScopes[$rbdigitalScope->id] = $rbdigitalScope->name;
		}

		require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibraryScope.php';
		$cloudLibraryScope = new CloudLibraryScope();
		$cloudLibraryScope->orderBy('name');
		$cloudLibraryScopes = [];
		$cloudLibraryScope->find();
		$cloudLibraryScopes[-2] = 'None';
		$cloudLibraryScopes[-1] = 'Use Library Setting';
		while ($cloudLibraryScope->fetch()) {
			$cloudLibraryScopes[$cloudLibraryScope->id] = $cloudLibraryScope->name;
		}

		$structure = array(
			'locationId' => array('property' => 'locationId', 'type' => 'label', 'label' => 'Location Id', 'description' => 'The unique id of the location within the database'),
			'subdomain' => array('property' => 'subdomain', 'type' => 'text', 'label' => 'Subdomain', 'description' => 'The subdomain to use while identifying this branch.  Can be left if it matches the code.', 'required' => false, 'forcesReindex' => true, 'canBatchUpdate'=>false),
			'code' => array('property' => 'code', 'type' => 'text', 'label' => 'Code', 'description' => 'The code for use when communicating with the ILS', 'required' => true, 'forcesReindex' => true, 'canBatchUpdate'=>false),
			'historicCode' => array('property' => 'historicCode', 'type' => 'text', 'label' => 'Historic Code', 'description' => 'A historic code that can be used in some instances as a substitute for code', 'hideInLists' => true, 'required' => false, 'forcesReindex' => false, 'canBatchUpdate'=>false),
			'subLocation' => array('property' => 'subLocation', 'type' => 'text', 'label' => 'Sub Location Code', 'description' => 'The sub location or collection used to identify this ', 'forcesReindex' => true, 'canBatchUpdate'=>false),
			'displayName' => array('property' => 'displayName', 'type' => 'text', 'label' => 'Display Name', 'description' => 'The full name of the location for display to the user', 'size' => '40', 'forcesReindex' => true, 'canBatchUpdate'=>false),
			'theme' => array('property' => 'theme', 'type' => 'enum', 'label' => 'Theme', 'values' => $availableThemes, 'description' => 'The theme which should be used for the library', 'hideInLists' => true, 'default' => 'default'),
			'showDisplayNameInHeader' => array('property' => 'showDisplayNameInHeader', 'type' => 'checkbox', 'label' => 'Show Display Name in Header', 'description' => 'Whether or not the display name should be shown in the header next to the logo', 'hideInLists' => true, 'default' => false),
			'libraryId' => array('property' => 'libraryId', 'type' => 'enum', 'values' => $libraryList, 'label' => 'Library', 'description' => 'A link to the library which the location belongs to'),
			'isMainBranch' => array('property' => 'isMainBranch', 'type' => 'checkbox', 'label' => 'Is Main Branch', 'description' => 'Is this location the main branch for it\'s library', 'default' => false, 'canBatchUpdate'=>false),
			'showInLocationsAndHoursList' => array('property' => 'showInLocationsAndHoursList', 'type' => 'checkbox', 'label' => 'Show In Locations And Hours List', 'description' => 'Whether or not this location should be shown in the list of library hours and locations', 'hideInLists' => true, 'default' => true),
			'address' => array('property' => 'address', 'type' => 'textarea', 'label' => 'Address', 'description' => 'The address of the branch.', 'hideInLists' => true),
			'phone' => array('property' => 'phone', 'type' => 'text', 'label' => 'Phone Number', 'description' => 'The main phone number for the site .', 'maxLength' => '25', 'hideInLists' => true),
			'tty' => array('property' => 'tty', 'type' => 'text', 'label' => 'TTY Number', 'description' => 'The tty number for the site .', 'maxLength' => '25', 'hideInLists' => true),
			'description' => array('property' => 'description', 'type' => 'markdown', 'label' => 'Description', 'description' => 'Allows the display of a description in the Location and Hours dialog', 'hideInLists' => true),
			'nearbyLocation1' => array('property' => 'nearbyLocation1', 'type' => 'enum', 'values' => $locationLookupList, 'label' => 'Nearby Location 1', 'description' => 'A secondary location which is nearby and could be used for pickup of materials.', 'hideInLists' => true),
			'nearbyLocation2' => array('property' => 'nearbyLocation2', 'type' => 'enum', 'values' => $locationLookupList, 'label' => 'Nearby Location 2', 'description' => 'A tertiary location which is nearby and could be used for pickup of materials.', 'hideInLists' => true),
			'automaticTimeoutLength' => array('property' => 'automaticTimeoutLength', 'type' => 'integer', 'label' => 'Automatic Timeout Length (logged in)', 'description' => 'The length of time before the user is automatically logged out in seconds.', 'size' => '8', 'hideInLists' => true, 'default' => self::DEFAULT_AUTOLOGOUT_TIME),
			'automaticTimeoutLengthLoggedOut' => array('property' => 'automaticTimeoutLengthLoggedOut', 'type' => 'integer', 'label' => 'Automatic Timeout Length (logged out)', 'description' => 'The length of time before the catalog resets to the home page set to 0 to disable.', 'size' => '8', 'hideInLists' => true, 'default' => self::DEFAULT_AUTOLOGOUT_TIME_LOGGED_OUT),

			'displaySection' => array('property' => 'displaySection', 'type' => 'section', 'label' => 'Basic Display', 'hideInLists' => true, 'properties' => array(
				array('property' => 'homeLink', 'type' => 'text', 'label' => 'Home Link', 'description' => 'The location to send the user when they click on the home button or logo.  Use default or blank to go back to the aspen home location.', 'hideInLists' => true, 'size' => '40'),
				array('property' => 'additionalCss', 'type' => 'textarea', 'label' => 'Additional CSS', 'description' => 'Extra CSS to apply to the site.  Will apply to all pages.', 'hideInLists' => true),
				array('property' => 'headerText', 'type' => 'html', 'label' => 'Header Text', 'description' => 'Optional Text to display in the header, between the logo and the log in/out buttons.  Will apply to all pages.', 'allowableTags' => '<a><b><em><div><span><p><strong><sub><sup><h1><h2><h3><h4><h5><h6><img>', 'hideInLists' => true),
			)),

			'ilsSection' => array('property' => 'ilsSection', 'type' => 'section', 'label' => 'ILS/Account Integration', 'hideInLists' => true, 'properties' => array(
				'scope' => array('property' => 'scope', 'type' => 'text', 'label' => 'Scope', 'description' => 'The scope for the system in Millennium to refine holdings to the branch.  If there is no scope defined for the branch, this can be set to 0.', 'default' => 0, 'forcesReindex' => true),
				'useScope' => array('property' => 'useScope', 'type' => 'checkbox', 'label' => 'Use Scope?', 'description' => 'Whether or not the scope should be used when displaying holdings.', 'hideInLists' => true, 'forcesReindex' => true),
				array('property' => 'defaultPType', 'type' => 'text', 'label' => 'Default P-Type', 'description' => 'The P-Type to use when accessing a subdomain if the patron is not logged in.  Use -1 to use the library default PType.', 'default' => -1, 'forcesReindex' => true),
				array('property' => 'validHoldPickupBranch', 'type' => 'enum', 'values' => array('1' => 'Valid for all patrons', '0' => 'Valid for patrons of this branch only', '2' => 'Not Valid'), 'label' => 'Valid Hold Pickup Branch?', 'description' => 'Determines if the location can be used as a pickup location if it is not the patrons home location or the location they are in.', 'hideInLists' => true, 'default' => 1),
				array('property' => 'showHoldButton', 'type' => 'checkbox', 'label' => 'Show Hold Button', 'description' => 'Whether or not the hold button is displayed so patrons can place holds on items', 'hideInLists' => true, 'default' => true),
				array('property' => 'ptypesToAllowRenewals', 'type' => 'text', 'label' => 'PTypes that can renew', 'description' => 'A list of P-Types that can renew items or * to allow all P-Types to renew items.', 'hideInLists' => true, 'default' => '*'),
			)),

			//Grouped Work Display
			'groupedWorkDisplaySettingId' => array('property' => 'groupedWorkDisplaySettingId', 'type' => 'enum', 'values'=>$groupedWorkDisplaySettings, 'label' => 'Grouped Work Display Settings', 'hideInLists' => false),

			'searchingSection' => array('property' => 'searchingSection', 'type' => 'section', 'label' => 'Searching', 'hideInLists' => true, 'properties' => array(
				array('property' => 'restrictSearchByLocation', 'type' => 'checkbox', 'label' => 'Restrict Search By Location', 'description' => 'Whether or not search results should only include titles from this location', 'hideInLists' => true, 'default' => false, 'forcesReindex' => true),
				array('property' => 'publicListsToInclude', 'type' => 'enum', 'values' => array(0 => 'No Lists', '1' => 'Lists from this library', '4' => 'Lists from library list publishers Only', '2' => 'Lists from this location', '5' => 'Lists from list publishers at this location Only', '6' => 'Lists from all list publishers', '3' => 'All Lists'), 'label' => 'Public Lists To Include', 'description' => 'Which lists should be included in this scope', 'default' => '4', 'forcesListReindex' => true),
				array('property' => 'searchBoxSection', 'type' => 'section', 'label' => 'Search Box', 'hideInLists' => true, 'properties' => array(
					array('property' => 'systemsToRepeatIn', 'type' => 'text', 'label' => 'Systems To Repeat In', 'description' => 'A list of library codes that you would like to repeat search in separated by pipes |.', 'hideInLists' => true),
					array('property' => 'repeatSearchOption', 'type' => 'enum', 'values' => array('none' => 'None', 'librarySystem' => 'Library System', 'marmot' => 'Entire Consortium'), 'label' => 'Repeat Search Options (requires Restrict Search By Location to be ON)', 'description' => 'Where to allow repeating search. Valid options are: none, librarySystem, marmot, all', 'default' => 'marmot'),
					array('property' => 'repeatInOnlineCollection', 'type' => 'checkbox', 'label' => 'Repeat In Online Collection', 'description' => 'Turn on to allow repeat search in the Online Collection.', 'hideInLists' => true, 'default' => false),
					array('property' => 'repeatInProspector', 'type' => 'checkbox', 'label' => 'Repeat In Prospector', 'description' => 'Turn on to allow repeat search in Prospector functionality.', 'hideInLists' => true, 'default' => false),
					array('property' => 'repeatInWorldCat', 'type' => 'checkbox', 'label' => 'Repeat In WorldCat', 'description' => 'Turn on to allow repeat search in WorldCat functionality.', 'hideInLists' => true, 'default' => false),
				)),
				array('property' => 'searchFacetsSection', 'type' => 'section', 'label' => 'Search Facets', 'hideInLists' => true, 'properties' => array(
					array('property' => 'facetLabel', 'type' => 'text', 'label' => 'Facet Label', 'description' => 'The label of the facet that identifies this location.', 'hideInLists' => true, 'size' => '40', 'maxLength' => 75, 'forcesReindex' => true),
					array('property' => 'includeAllLibraryBranchesInFacets', 'type' => 'checkbox', 'label' => 'Include All Library Branches In Facets', 'description' => 'Turn on to include all branches of the library within facets (ownership and availability).', 'hideInLists' => true, 'default' => true, 'forcesReindex' => true),
					array('property' => 'additionalLocationsToShowAvailabilityFor', 'type' => 'text', 'label' => 'Additional Locations to Include in Available At Facet', 'description' => 'A list of library codes that you would like included in the available at facet separated by pipes |.', 'size' => '20', 'hideInLists' => true, 'forcesReindex' => true),
				)),
				'combinedResultsSection' => array('property' => 'combinedResultsSection', 'type' => 'section', 'label' => 'Combined Results', 'hideInLists' => true, 'helpLink' => '', 'properties' => array(
					'useLibraryCombinedResultsSettings' => array('property' => 'useLibraryCombinedResultsSettings', 'type' => 'checkbox', 'label' => 'Use Library Settings', 'description' => 'Whether or not settings from the library should be used rather than settings from here', 'hideInLists' => true, 'default' => true),
					'enableCombinedResults' => array('property' => 'enableCombinedResults', 'type' => 'checkbox', 'label' => 'Enable Combined Results', 'description' => 'Whether or not combined results should be shown ', 'hideInLists' => true, 'default' => false),
					'combinedResultsLabel' => array('property' => 'combinedResultsLabel', 'type' => 'text', 'label' => 'Combined Results Label', 'description' => 'The label to use in the search source box when combined results is active.', 'size' => '20', 'hideInLists' => true, 'default' => 'Combined Results'),
					'defaultToCombinedResults' => array('property' => 'defaultToCombinedResults', 'type' => 'checkbox', 'label' => 'Default To Combined Results', 'description' => 'Whether or not combined results should be the default search source when active ', 'hideInLists' => true, 'default' => true),
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
						'additionalOneToManyActions' => []
					),
				)),
			)),

			// Full Record Display //
			'fullRecordSection' => array('property' => 'fullRecordSection', 'type' => 'section', 'label' => 'Full Record Display', 'hideInLists' => true, 'properties' => array(
				'showEmailThis' => array('property' => 'showEmailThis', 'type' => 'checkbox', 'label' => 'Show Email This', 'description' => 'Whether or not the Email This link is shown', 'hideInLists' => true, 'default' => 1),
				'showShareOnExternalSites' => array('property' => 'showShareOnExternalSites', 'type' => 'checkbox', 'label' => 'Show Sharing To External Sites', 'description' => 'Whether or not sharing on external sites (Twitter, Facebook, Pinterest, etc. is shown)', 'hideInLists' => true, 'default' => 1),
				'moreDetailsOptions' => array(
					'property' => 'moreDetailsOptions',
					'type' => 'oneToMany',
					'label' => 'Full Record Options',
					'description' => 'Record Options for the display of full record',
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

			'browseCategoryGroupId' => array('property' => 'browseCategoryGroupId', 'type' => 'enum', 'values' => $browseCategoryGroups, 'label' => 'Browse Category Group', 'description' => 'The group of browse categories to show for this library', 'hideInLists' => true),

			'axis360Section' => array('property' => 'axis360Section', 'type' => 'section', 'label' => 'Axis 360', 'hideInLists' => true, 'renderAsHeading' => true, 'properties' => array(
				'axis360ScopeId' => array('property' => 'axis360ScopeId', 'type' => 'enum', 'values' => $axis360Scopes, 'label' => 'Axis 360 Scope', 'description' => 'The Axis 360 scope to use', 'hideInLists' => true, 'default' => -1, 'forcesReindex' => true),
			)),
			
			'cloudLibrarySection' => array('property' => 'cloudLibrarySection', 'type' => 'section', 'label' => 'Cloud Library', 'hideInLists' => true, 'renderAsHeading' => true, 'properties' => array(
				'cloudLibraryScopeId' => array('property' => 'cloudLibraryScopeId', 'type' => 'enum', 'values' => $cloudLibraryScopes, 'label' => 'Cloud Library Scope', 'description' => 'The Cloud Library scope to use', 'hideInLists' => true, 'default' => -1, 'forcesReindex' => true),
			)),

			'hooplaSection' => array('property' => 'hooplaSection', 'type' => 'section', 'label' => 'Hoopla', 'hideInLists' => true, 'renderAsHeading' => true, 'properties' => array(
				'hooplaScopeId' => array('property' => 'hooplaScopeId', 'type' => 'enum', 'values' => $hooplaScopes, 'label' => 'Hoopla Scope', 'description' => 'The hoopla scope to use', 'hideInLists' => true, 'default' => -1, 'forcesReindex' => true),
			)),

			'rbdigitalSection' => array('property' => 'rbdigitalSection', 'type' => 'section', 'label' => 'RBdigital', 'hideInLists' => true, 'renderAsHeading' => true, 'properties' => array(
				'rbdigitalScopeId' => array('property' => 'rbdigitalScopeId', 'type' => 'enum', 'values' => $rbdigitalScopes, 'label' => 'RBdigital Scope', 'description' => 'The RBdigital scope to use', 'hideInLists' => true, 'default' => -1, 'forcesReindex' => true),
			)),

			'overdriveSection' => array('property' => 'overdriveSection', 'type' => 'section', 'label' => 'OverDrive', 'hideInLists' => true, 'renderAsHeading' => true, 'properties' => array(
				'overDriveScopeId'               => array('property' => 'overDriveScopeId', 'type' => 'enum', 'values' => $overDriveScopes, 'label' => 'OverDrive Scope', 'description' => 'The OverDrive scope to use', 'hideInLists' => true, 'default' => -1, 'forcesReindex' => true),
			)),

			array(
				'property' => 'hours',
				'type' => 'oneToMany',
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
				'property' => 'recordsOwned',
				'type' => 'oneToMany',
				'label' => 'Records Owned',
				'description' => 'Information about what records are owned by the location',
				'keyThis' => 'locationId',
				'keyOther' => 'locationId',
				'subObjectType' => 'LocationRecordOwned',
				'structure' => $locationRecordOwnedStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'forcesReindex' => true
			),

			'recordsToInclude' => array(
				'property' => 'recordsToInclude',
				'type' => 'oneToMany',
				'label' => 'Records To Include',
				'description' => 'Information about what records to include in this scope',
				'keyThis' => 'locationId',
				'keyOther' => 'locationId',
				'subObjectType' => 'LocationRecordToInclude',
				'structure' => $locationRecordToIncludeStructure,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'forcesReindex' => true
			),
			'includeLibraryRecordsToInclude' => array('property' => 'includeLibraryRecordsToInclude', 'type' => 'checkbox', 'label' => 'Include Library Records To Include', 'description' => 'Whether or not the records to include from the parent library should be included for this location', 'hideInLists' => true, 'default' => true, 'forcesReindex' => true),

			'sideLoadScopes' => array(
				'property' => 'sideLoadScopes',
				'type' => 'oneToMany',
				'label' => 'Side Loaded eContent Scopes',
				'description' => 'Information about what Side Loads to include in this scope',
				'keyThis' => 'libraryId',
				'keyOther' => 'libraryId',
				'subObjectType' => 'LocationSideLoadScope',
				'structure' => $locationSideLoadScopeStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => true,
				'forcesReindex' => true
			),
		);

		if (!UserAccount::userHasPermission('Administer All Libraries')) {
			unset($structure['isMainBranch']);
		}
		global $configArray;
		$ils = $configArray['Catalog']['ils'];
		if ($ils != 'Millennium' && $ils != 'Sierra') {
			unset($structure['ilsSection']['properties']['scope']);
			unset($structure['ilsSection']['properties']['useScope']);
		}
		global $enabledModules;
		if (!array_key_exists('OverDrive', $enabledModules)){
			unset($structure['overdriveSection']);
		}
		if (!array_key_exists('Hoopla', $enabledModules)){
			unset($structure['hooplaSection']);
		}
		if (!array_key_exists('RBdigital', $enabledModules)){
			unset($structure['rbdigitalSection']);
		}
		if (!array_key_exists('Cloud Library', $enabledModules)){
			unset($structure['cloudLibrarySection']);
		}
		if (!array_key_exists('Side Loads', $enabledModules)){
			unset($structure['sideLoadScopes']);
		}
		return $structure;
	}

	public $pickupUsers;
	// Used to track multiple linked users having the same pick-up locations

	/**
	 * @param User $patronProfile
	 * @param bool $isLinkedUser
	 * @return Location[]
	 */
	function getPickupBranches($patronProfile, $isLinkedUser = false)
	{
		// Note: Some calls to this function will set $patronProfile to false. (No Patron is logged in)
		// For Example: MaterialsRequest_NewRequest
		$homeLibraryInList = false;
		$alternateLibraryInList = false;
		$hasSelectedLocation = false;

		//Get the library for the patron's home branch.
		global $librarySingleton;
		if ($patronProfile) {
			$homeLibrary = $librarySingleton->getLibraryForLocation($patronProfile->homeLocationId);
		}

		if (isset($homeLibrary) && $homeLibrary->inSystemPickupsOnly == 1) {
			/** The user can only pickup within their home system */
			if (strlen($homeLibrary->validPickupSystems) > 0) {
				/** The system has additional related systems that you can pickup within */
				$pickupIds = array();
				$pickupIds[] = $homeLibrary->libraryId;
				$validPickupSystems = explode('|', $homeLibrary->validPickupSystems);
				foreach ($validPickupSystems as $pickupSystem) {
					$pickupLocation = new Library();
					$pickupLocation->subdomain = $pickupSystem;
					$pickupLocation->find();
					if ($pickupLocation->getNumResults() == 1) {
						$pickupLocation->fetch();
						$pickupIds[] = $pickupLocation->libraryId;
					}
				}
				$this->whereAdd("libraryId IN (" . implode(',', $pickupIds) . ")", 'AND');
			} else {
				/** Only this system is valid */
				$this->whereAdd("libraryId = {$homeLibrary->libraryId}", 'AND');
				$this->whereAdd("validHoldPickupBranch = 1", 'AND');
			}
		} else {
			$this->whereAdd("validHoldPickupBranch = 1");
		}

		$this->orderBy('displayName');

		$tmpLocations = $this->fetchAll();

		//Load the locations and sort them based on the user profile information as well as their physical location.
		$physicalLocation = $this->getPhysicalLocation();
		$locationList = array();
		foreach ($tmpLocations as $tmpLocation){
			// Add the user id to each pickup location to track multiple linked accounts having the same pick-up location.
			if ($patronProfile) {
				$tmpLocation->pickupUsers[] = $patronProfile->id;
			}
			if (($tmpLocation->validHoldPickupBranch == 1) || ($tmpLocation->validHoldPickupBranch == 0 && !empty($patronProfile) && $patronProfile->homeLocationId == $tmpLocation->locationId)) {
				// Each location is prepended with a number to keep precedence for given locations when sorted below
				if (isset($physicalLocation) && $physicalLocation->locationId == $tmpLocation->locationId) {
					//If the user is in a branch, those holdings come first.
					$locationList['1' . $tmpLocation->displayName] = $tmpLocation;
				} else if (!empty($patronProfile) && $tmpLocation->locationId == $patronProfile->pickupLocationId) {
					//Next comes the user's preferred pickup branch if the user is logged in.
					$locationList['21' . $tmpLocation->displayName] = $tmpLocation;
				} else if (!empty($patronProfile) && $tmpLocation->locationId == $patronProfile->homeLocationId) {
					//Next comes the user's home branch if the user is logged in or has the home_branch cookie set.
					$locationList['22' . $tmpLocation->displayName] = $tmpLocation;
					$homeLibraryInList = true;
				} else if (isset($patronProfile->myLocation1Id) && $tmpLocation->locationId == $patronProfile->myLocation1Id) {
					//Next come nearby locations for the user
					$locationList['3' . $tmpLocation->displayName] = $tmpLocation;
					$alternateLibraryInList = true;
				} else if (isset($patronProfile->myLocation2Id) && $tmpLocation->locationId == $patronProfile->myLocation2Id) {
					//Next come nearby locations for the user
					$locationList['4' . $tmpLocation->displayName] = $tmpLocation;
				} else if (isset($homeLibrary) && $tmpLocation->libraryId == $homeLibrary->libraryId) {
					//Other locations that are within the same library system
					$locationList['5' . $tmpLocation->displayName] = $tmpLocation;
				} else {
					//Finally, all other locations are shown sorted alphabetically.
					$locationList['6' . $tmpLocation->displayName] = $tmpLocation;
				}
			}
		}
		ksort($locationList);

		//MDN 8/14/2015 always add the home location #PK-81
		// unless the option to pickup at the home location is specifically disabled #PK-1250
		//if (count($locationList) == 0 && (isset($homeLibrary) && $homeLibrary->inSystemPickupsOnly == 1)){
		if (!empty($patronProfile) && $patronProfile->homeLocationId != 0) {
			$homeLocation = new Location();
			$homeLocation->locationId = $patronProfile->homeLocationId;
			if ($homeLocation->find(true)) {
				if ($homeLocation->validHoldPickupBranch != 2) {
					//We didn't find any locations.  This for schools where we want holds available, but don't want the branch to be a
					//pickup location anywhere else.
					$homeLocation->pickupUsers[] = $patronProfile->id; // Add the user id to each pickup location to track multiple linked accounts having the same pick-up location.
					$existingLocation = false;
					foreach ($locationList as $location) {
						if ($location->libraryId == $homeLocation->libraryId && $location->locationId == $homeLocation->locationId) {
							$existingLocation = true;
							//TODO: update sorting key as well?
							break;
						}
					}
					if (!$existingLocation) {
						if (!$isLinkedUser) {
							$locationList['1' . $homeLocation->displayName] = clone $homeLocation;
							$homeLibraryInList = true;
						} else {
							$locationList['23' . $homeLocation->displayName] = clone $homeLocation;
						}
					}
				}
			}
		}

		if (!$homeLibraryInList && !$alternateLibraryInList && !$isLinkedUser) {
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
	function getActiveLocation()
	{
		if (Location::$activeLocation != 'unset') {
			return Location::$activeLocation;
		}

		//default value
		Location::$activeLocation = null;

		//load information about the library we are in.
		global $library;
		if (is_null($library)) {
			//If we are not in a library, then do not allow branch scoping, etc.
			Location::$activeLocation = null;
		} else {

			//Check to see if a branch location has been specified.
			$locationCode = $this->getBranchLocationCode();
			if (!empty($locationCode) && $locationCode != 'all') {
				$activeLocation = new Location();
				$activeLocation->subLocation = $locationCode;
				if ($activeLocation->find(true)) {
					//Only use the location if we are in the subdomain for the parent library
					if ($library->libraryId == $activeLocation->libraryId) {
						Location::$activeLocation = clone $activeLocation;
					} else {
						// If the active location doesn't belong to the library we are browsing at, turn off the active location
						Location::$activeLocation = null;
					}
				} else {
					//Check to see if we can get the active location based off the sublocation
					$activeLocation = new Location();
					$activeLocation->code = $locationCode;
					if ($activeLocation->find(true)) {
						//Only use the location if we are in the subdomain for the parent library
						if ($library->libraryId == $activeLocation->libraryId) {
							Location::$activeLocation = clone $activeLocation;
						} else {
							// If the active location doesn't belong to the library we are browsing at, turn off the active location
							Location::$activeLocation = null;
						}
					} else {
						//Check to see if we can get the active location based off the sublocation
						$activeLocation = new Location();
						$activeLocation->subdomain = $locationCode;
						if ($activeLocation->find(true)) {
							//Only use the location if we are in the subdomain for the parent library
							if ($library->libraryId == $activeLocation->libraryId) {
								Location::$activeLocation = clone $activeLocation;
							} else {
								// If the active location doesn't belong to the library we are browsing at, turn off the active location
								Location::$activeLocation = null;
							}
						}
					}
				}
			} else {
				// Check if we know physical location by the ip table
				$physicalLocation = $this->getPhysicalLocation();
				if ($physicalLocation != null) {
					if ($library->libraryId == $physicalLocation->libraryId) {
						Location::$activeLocation = $physicalLocation;
					} else {
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

	function setActiveLocation($location)
	{
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
	static function getUserHomeLocation()
	{
		if (isset(Location::$userHomeLocation) && Location::$userHomeLocation != 'unset') {
			return Location::$userHomeLocation;
		}

		// default value
		Location::$userHomeLocation = null;

		if (UserAccount::isLoggedIn()) {
			$homeLocation = new Location();
			$homeLocation->locationId = UserAccount::getUserHomeLocationId();
			if ($homeLocation->find(true)) {
				Location::$userHomeLocation = clone($homeLocation);
			}
		}

		return Location::$userHomeLocation;
	}


	private $branchLocationCode = 'unset';

	function getBranchLocationCode()
	{
		if (isset($this->branchLocationCode) && $this->branchLocationCode != 'unset') return $this->branchLocationCode;
		if (isset($_GET['branch'])) {
			$this->branchLocationCode = $_GET['branch'];
		} elseif (isset($_COOKIE['branch'])) {
			$this->branchLocationCode = $_COOKIE['branch'];
		} else {
			$this->branchLocationCode = '';
		}
		if ($this->branchLocationCode == 'all') {
			$this->branchLocationCode = '';
		}
		return $this->branchLocationCode;
	}

	/**
	 * The physical location where the user is based on
	 * IP address and branch parameter, and only for It's Here messages
	 *
	 */
	private $_physicalLocation = 'unset';

	function getPhysicalLocation()
	{
		if ($this->_physicalLocation != 'unset') {
			return $this->_physicalLocation;
		}

		if ($this->getBranchLocationCode() != '') {
			$this->_physicalLocation = $this->getActiveLocation();
		} else {
			$this->_physicalLocation = $this->getIPLocation();
		}
		return $this->_physicalLocation;
	}

	static $searchLocation = array();

	/**
	 * @param null $searchSource
	 * @return Location|null
	 */
	static function getSearchLocation($searchSource = null)
	{
		if ($searchSource == null) {
			global $searchSource;
		}
		if ($searchSource == 'combinedResults') {
			$searchSource = 'local';
		}
		if (!array_key_exists($searchSource, Location::$searchLocation)) {
			$scopingSetting = $searchSource;
			if ($searchSource == null) {
				Location::$searchLocation[$searchSource] = null;
			} else if ($scopingSetting == 'local' || $scopingSetting == 'econtent' || $scopingSetting == 'location' || $scopingSetting == 'websites' || $scopingSetting == 'lists') {
				global $locationSingleton;
				Location::$searchLocation[$searchSource] = $locationSingleton->getActiveLocation();
			} else if ($scopingSetting == 'marmot' || $scopingSetting == 'unscoped') {
				Location::$searchLocation[$searchSource] = null;
			} else {
				$location = new Location();
				$location->code = $scopingSetting;
				$location->find();
				if ($location->getNumResults() > 0) {
					$location->fetch();
					Location::$searchLocation[$searchSource] = clone($location);
				} else {
					Location::$searchLocation[$searchSource] = null;
				}
			}
		}
		return Location::$searchLocation[$searchSource];
	}

	/**
	 * The location we are in based solely on IP address.
	 * @var Location|string
	 */
	private $_ipLocation = 'unset';

	/**
	 * @return Location|bool|null
	 */
	function getIPLocation()
	{
		if ($this->_ipLocation != 'unset') {
			return $this->_ipLocation;
		}
		global $timer;
		global $memCache;
		global $configArray;
		global $logger;
		//Check the current IP address to see if we are in a branch
		$activeIp = IPAddress::getActiveIp();
		$this->_ipLocation = $memCache->get('location_for_ip_' . $activeIp);
		$_ipId = $memCache->get('ipId_for_ip_' . $activeIp);
		if ($_ipId == -1) {
			$this->_ipLocation = false;
		}

		if ($this->_ipLocation == false || $_ipId == false) {
			$timer->logTime('Starting getIPLocation');
			//echo("Active IP is $activeIp");
			require_once ROOT_DIR . '/sys/IP/IPAddress.php';
			$this->_ipLocation = null;
			$_ipId = -1;
			$subnet = IPAddress::getIPAddressForIP($activeIp);
			if ($subnet != false){
				$matchedLocation = new Location();
				$matchedLocation->locationId = $subnet->locationid;
				if ($matchedLocation->find(true)) {
					//Only use the physical location regardless of where we are
					$this->_ipLocation = clone($matchedLocation);
					$_ipId = $subnet->id;
				} else {
					$logger->log("Did not find location for ip location id {$subnet->locationid}", Logger::LOG_WARNING);
				}
			}

			$memCache->set('ipId_for_ip_' . $activeIp, $_ipId, $configArray['Caching']['ipId_for_ip']);
			$memCache->set('location_for_ip_' . $activeIp, $this->_ipLocation, $configArray['Caching']['location_for_ip']);
			$timer->logTime('Finished getIPLocation');
		}

		return $this->_ipLocation;
	}


	private $sublocationCode = 'unset';

	function getSublocationCode()
	{
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

	function getLocationsFacetsForLibrary($libraryId)
	{
		$location = new Location();
		$location->libraryId = $libraryId;
		$location->find();
		$facets = array();
		if ($location->getNumResults() > 0) {
			while ($location->fetch()) {
				$facets[] = $location->facetLabel;
			}
		}
		return $facets;
	}


	public function __get($name)
	{
		if ($name == "hours") {
			return $this->getHours();
		} elseif ($name == "moreDetailsOptions") {
			if (!isset($this->_moreDetailsOptions) && $this->libraryId) {
				$this->_moreDetailsOptions = array();
				$moreDetailsOptions = new LocationMoreDetails();
				$moreDetailsOptions->locationId = $this->locationId;
				$moreDetailsOptions->orderBy('weight');
				$moreDetailsOptions->find();
				while ($moreDetailsOptions->fetch()) {
					$this->_moreDetailsOptions[$moreDetailsOptions->id] = clone($moreDetailsOptions);
				}
			}
			return $this->_moreDetailsOptions;
		} elseif ($name == 'recordsOwned') {
			if (!isset($this->_recordsOwned) && $this->locationId) {
				$this->_recordsOwned = array();
				$object = new LocationRecordOwned();
				$object->locationId = $this->locationId;
				$object->find();
				while ($object->fetch()) {
					$this->_recordsOwned[$object->id] = clone($object);
				}
			}
			return $this->_recordsOwned;
		} elseif ($name == 'recordsToInclude') {
			if (!isset($this->_recordsToInclude) && $this->locationId) {
				$this->_recordsToInclude = array();
				$object = new LocationRecordToInclude();
				$object->locationId = $this->locationId;
				$object->orderBy('weight');
				$object->find();
				while ($object->fetch()) {
					$this->_recordsToInclude[$object->id] = clone($object);
				}
			}
			return $this->_recordsToInclude;
		} elseif ($name == 'sideLoadScopes') {
			if (!isset($this->_sideLoadScopes) && $this->locationId) {
				$this->_sideLoadScopes = array();
				$object = new LocationSideLoadScope();
				$object->locationId = $this->locationId;
				$object->find();
				while ($object->fetch()) {
					$this->_sideLoadScopes[$object->id] = clone($object);
				}
			}
			return $this->_sideLoadScopes;
		} elseif ($name == 'combinedResultSections') {
			if (!isset($this->_combinedResultSections) && $this->locationId) {
				$this->_combinedResultSections = array();
				$combinedResultSection = new LocationCombinedResultSection();
				$combinedResultSection->locationId = $this->locationId;
				$combinedResultSection->orderBy('weight');
				if ($combinedResultSection->find()) {
					while ($combinedResultSection->fetch()) {
						$this->_combinedResultSections[$combinedResultSection->id] = clone $combinedResultSection;
					}
				}
				return $this->_combinedResultSections;
			}
		} else {
			return $this->_data[$name];
		}
		return null;
	}

	public function __set($name, $value)
	{
		if ($name == "hours") {
			$this->_hours = $value;
		} elseif ($name == "moreDetailsOptions") {
			$this->_moreDetailsOptions = $value;
		} elseif ($name == 'recordsOwned') {
			$this->_recordsOwned = $value;
		} elseif ($name == 'recordsToInclude') {
			$this->_recordsToInclude = $value;
		} elseif ($name == 'sideLoadScopes') {
			$this->_sideLoadScopes = $value;
		} elseif ($name == 'combinedResultSections') {
			$this->_combinedResultSections = $value;
		} else {
			$this->_data[$name] = $value;
		}
	}

	/**
	 * Override the update functionality to save the hours
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update()
	{
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveHours();
			$this->saveMoreDetailsOptions();
			$this->saveRecordsOwned();
			$this->saveRecordsToInclude();
			$this->saveSideLoadScopes();
			$this->saveCombinedResultSections();
		}
		return $ret;
	}

	/**
	 * Override the update functionality to save the hours
	 *
	 * @see DB/DB_DataObject::insert()
	 */
	public function insert()
	{
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveHours();
			$this->saveMoreDetailsOptions();
			$this->saveRecordsOwned();
			$this->saveRecordsToInclude();
			$this->saveSideLoadScopes();
			$this->saveCombinedResultSections();
		}
		return $ret;
	}

	public function saveMoreDetailsOptions()
	{
		if (isset ($this->_moreDetailsOptions) && is_array($this->_moreDetailsOptions)) {
			$this->saveOneToManyOptions($this->_moreDetailsOptions, 'locationId');
			unset($this->_moreDetailsOptions);
		}
	}

	public function saveCombinedResultSections()
	{
		if (isset ($this->_combinedResultSections) && is_array($this->_combinedResultSections)) {
			$this->saveOneToManyOptions($this->_combinedResultSections, 'locationId');
			unset($this->_combinedResultSections);
		}
	}

	public function saveHours()
	{
		if (isset ($this->_hours) && is_array($this->_hours)) {
			$this->saveOneToManyOptions($this->_hours, 'locationId');
			unset($this->_hours);
		}
	}

	public static function getLibraryHours($locationId, $timeToCheck)
	{
		$location = new Location();
		$location->locationId = $locationId;
		if ($locationId > 0 && $location->find(true)) {
			// format $timeToCheck according to MySQL default date format
			$todayFormatted = date('Y-m-d', $timeToCheck);

			// check to see if today is a holiday
			require_once ROOT_DIR . '/sys/LibraryLocation/Holiday.php';
			$holiday = new Holiday();
			$holiday->date = $todayFormatted;
			$holiday->libraryId = $location->libraryId;
			if ($holiday->find(true)) {
				return [
					'closed' => true,
					'closureReason' => $holiday->name
				];
			}

			// get the day of the week (0=Sunday to 6=Saturday)
			$dayOfWeekToday = strftime('%w', $timeToCheck);

			// find library hours for the above day of the week
			$hours = new LocationHours();
			$hours->locationId = $locationId;
			$hours->day = $dayOfWeekToday;
			$hours->orderBy('open asc');
			$allClosed = true;
			if ($hours->find()) {
				$openHours = [];
				$ctr = 0;
				while ($hours->fetch()) {
					$openHours[$ctr] = array(
						'open' => ltrim($hours->open, '0'),
						'close' => ltrim($hours->close, '0'),
						'closed' => $hours->closed ? true : false,
						'openFormatted' => ($hours->open == '12:00' ? 'Noon' : date("g:i A", strtotime($hours->open))),
						'closeFormatted' => ($hours->close == '12:00' ? 'Noon' : date("g:i A", strtotime($hours->close)))
					);
					if (($openHours[$ctr]['open'] == $openHours[$ctr]['close'])){
						$openHours[$ctr]['closed'] = true;
					}
					if ($openHours[$ctr]['closed'] == false){
						$allClosed = false;
					}
					$ctr++;
				}
				if ($allClosed){
					return [
						'closed' => true
					];
				}
				return $openHours;
			}
		}

		// no hours found
		return null;
	}

	public static function getLibraryHoursMessage(int $locationId): string
	{
		$today = time();
		$location = new Location();
		$location->locationId = $locationId;
		if ($location->find(true)) {
			$todaysLibraryHours = Location::getLibraryHours($locationId, $today);
			if (isset($todaysLibraryHours) && is_array($todaysLibraryHours)) {
				if (isset($todaysLibraryHours['closed']) && ($todaysLibraryHours['closed'] == true || $todaysLibraryHours['closed'] == 1)) {
					if (isset($todaysLibraryHours['closureReason'])) {
						$closureReason = $todaysLibraryHours['closureReason'];
					}
					//Library is closed now
					$nextDay = time() + (24 * 60 * 60);
					$nextDayHours = Location::getLibraryHours($locationId, $nextDay);
					$daysChecked = 0;
					while (isset($nextDayHours['closed']) && $nextDayHours['closed'] == true && $daysChecked < 7) {
						$nextDay += (24 * 60 * 60);
						$nextDayHours = Location::getLibraryHours($locationId, $nextDay);
						$daysChecked++;
					}

					$nextDayOfWeek = strftime('%a', $nextDay);
					if (isset($nextDayHours['closed']) && $nextDayHours['closed'] == true) {
						if (isset($closureReason)) {
							$libraryHoursMessage = translate(['text' => "%1% is closed today for %2%.", 1 => $location->displayName, 2 => $closureReason]);
						} else {
							$libraryHoursMessage = translate(['text' => "%1% is closed today.", 1 => $location->displayName]);
						}
					} else {
						$openMessage = Location::getOpenHoursMessage($nextDayHours);
						if (isset($closureReason)) {
							$libraryHoursMessage = translate(['text' => 'closed_reopen_reason', 'defaultText' => "%1% is closed today for %2%. It will reopen on %3% from %4%", 1 => $location->displayName, 2 => $closureReason, 3 => $nextDayOfWeek, 4 => $openMessage]);
						} else {
							$libraryHoursMessage = translate(['text' => 'closed_reopen_no_reason', 'defaultText' => "%1% is closed today. It will reopen on %2% from %3%", 1 => $location->displayName, 2 => $nextDayOfWeek, 3 => $openMessage]);
						}
					}
				} else {
					//Library is open
					$currentHour = strftime('%H', $today);
					$openHour = strftime('%H', strtotime($todaysLibraryHours[0]['open']));
					$closeHour = strftime('%H', strtotime($todaysLibraryHours[sizeof($todaysLibraryHours) - 1]['close']));
					if ($closeHour == 0 && $closeHour < $openHour) {
						$closeHour = 24;
					}
					if ($currentHour < $openHour) {
						$libraryHoursMessage = translate(['text' => "%1% will be open today from %2%", 1 => $location->displayName, 2 => Location::getOpenHoursMessage($todaysLibraryHours)]);
					} else if ($currentHour > $closeHour) {
						$tomorrowsLibraryHours = Location::getLibraryHours($locationId, time() + (24 * 60 * 60));
						if (isset($tomorrowsLibraryHours['closed']) && ($tomorrowsLibraryHours['closed'] == true || $tomorrowsLibraryHours['closed'] == 1)) {
							if (isset($tomorrowsLibraryHours['closureReason'])) {
								$libraryHoursMessage = translate(['text' => 'closed_tomorrow_reason', 'defaultText' => "%1% will be closed tomorrow for %2%", 1 => $location->displayName, 2 => $tomorrowsLibraryHours['closureReason']]);
							} else {
								$libraryHoursMessage = translate(['text' => "%1% will be closed tomorrow", 1 => $location->displayName]);
							}

						} else {
							$libraryHoursMessage = translate(['text' => 'open_tomorrow', 'defaultText' => "%1% will be open tomorrow from %2%", 1 => $location->displayName, 2 => Location::getOpenHoursMessage($tomorrowsLibraryHours)]);
						}
					} else {
						$libraryHoursMessage = translate(['text' => "%1% is open today from %2%", 1 => $location->displayName, 2 => Location::getOpenHoursMessage($todaysLibraryHours)]);
					}
				}
			} else {
				$libraryHoursMessage = '';
			}
		} else {
			$libraryHoursMessage = '';
		}
		return $libraryHoursMessage;
	}

	public static function getOpenHoursMessage($hours)
	{
		$formattedMessage = '';
		for ($i = 0; $i < sizeof($hours); $i++) {
			if (strlen($formattedMessage) != 0 && (sizeof($hours) > 2)) {
				$formattedMessage .= ', ';
			}
			if (($i == (sizeof($hours) - 1)) && count($hours) > 1) {
				$formattedMessage .= translate(' and ');
			}
			$formattedMessage .= translate(['text' => '%1% to %2%', 1 => $hours[$i]['openFormatted'], 2 => $hours[$i]['closeFormatted']]);
		}
		return $formattedMessage;
	}

	public function saveRecordsOwned()
	{
		if (isset ($this->_recordsOwned) && is_array($this->_recordsOwned)) {
			/** @var LocationRecordOwned $object */
			foreach ($this->_recordsOwned as $object) {
				if (isset($object->deleteOnSave) && $object->deleteOnSave == true) {
					$object->delete();
				} else {
					if (isset($object->id) && is_numeric($object->id)) {
						$object->update();
					} else {
						$object->locationId = $this->locationId;
						$object->insert();
					}
				}
			}
			unset($this->_recordsOwned);
		}
	}

	public function saveRecordsToInclude()
	{
		if (isset ($this->_recordsToInclude) && is_array($this->_recordsToInclude)) {
			/** @var LocationRecordOwned $object */
			foreach ($this->_recordsToInclude as $object) {
				if (isset($object->deleteOnSave) && $object->deleteOnSave == true) {
					$object->delete();
				} else {
					if (isset($object->id) && is_numeric($object->id)) {
						$object->update();
					} else {
						$object->locationId = $this->locationId;
						$object->insert();
					}
				}
			}
			unset($this->_recordsToInclude);
		}
	}

	public function saveSideLoadScopes()
	{
		if (isset ($this->_sideLoadScopes) && is_array($this->_sideLoadScopes)) {
			$this->saveOneToManyOptions($this->_sideLoadScopes, 'locationId');
			unset($this->_sideLoadScopes);
		}
	}

	/** @return LocationHours[] */
	function getHours()
	{
		if (!isset($this->_hours)) {
			$this->_hours = array();
			if ($this->locationId) {
				$hours = new LocationHours();
				$hours->locationId = $this->locationId;
				$hours->orderBy('day');
				$hours->find();
				while ($hours->fetch()) {
					$this->_hours[$hours->id] = clone($hours);
				}
			}
		}
		return $this->_hours;
	}

	public function hasValidHours()
	{
		$hours = new LocationHours();
		$hours->locationId = $this->locationId;
		$hours->find();
		$hasValidHours = false;
		while ($hours->fetch()) {
			if ($hours->open != '00:30' || $hours->close != '00:30') {
				$hasValidHours = true;

			}
		}
		return $hasValidHours;
	}

	private $_opacStatus = null;

	/**
	 * Check whether or not the system is an opac station.
	 * - First check to see if an opac parameter has been passed.  If so, use that information and set a cookie for future pages.
	 * - Next check the cookie to see if we have overridden the value
	 * - Finally check to see if we have an active location based on the IP address.  If we do, use that to determine if this is an opac station
	 * @return bool
	 */
	public function getOpacStatus()
	{
		if (is_null($this->_opacStatus)) {
			if (isset($_GET['opac'])) {
				$this->_opacStatus = $_GET['opac'] == 1 || strtolower($_GET['opac']) == 'true' || strtolower($_GET['opac']) == 'on';
				if ($_GET['opac'] == '') {
					//Clear any existing cookie
					setcookie('opac', $this->_opacStatus, time() - 1000, '/');
				} elseif (!isset($_COOKIE['opac']) || $this->_opacStatus != $_COOKIE['opac']) {
					setcookie('opac', $this->_opacStatus ? '1' : '0', 0, '/');
				}
			} elseif (isset($_COOKIE['opac'])) {
				$this->_opacStatus = (boolean)$_COOKIE['opac'];
			} else {
				$activeIP = IPAddress::getActiveIp();
				require_once ROOT_DIR . '/sys/IP/IPAddress.php';
				$subnet = IPAddress::getIPAddressForIP($activeIP);
				if ($subnet != false) {
					$this->_opacStatus = $subnet->isOpac;
				} else {
					$this->_opacStatus = false;
				}
			}
		}
		return $this->_opacStatus;
	}

	protected $_groupedWorkDisplaySettings = null;

	/** @return GroupedWorkDisplaySetting */
	public function getGroupedWorkDisplaySettings()
	{
		if ($this->_groupedWorkDisplaySettings == null) {
			try {
				if ($this->groupedWorkDisplaySettingId == -1) {
					$library = Library::getLibraryForLocation($this->locationId);
					$this->groupedWorkDisplaySettingId = $library->groupedWorkDisplaySettingId;
				}
				$groupedWorkDisplaySettings = new GroupedWorkDisplaySetting();
				$groupedWorkDisplaySettings->id = $this->groupedWorkDisplaySettingId;
				$groupedWorkDisplaySettings->find(true);
				$this->_groupedWorkDisplaySettings = clone $groupedWorkDisplaySettings;
			}catch(Exception $e){
				global $logger;
				$logger->log('Error loading grouped work display settings ' . $e, Logger::LOG_ERROR);
			}
		}
		return $this->_groupedWorkDisplaySettings;
	}

	function getEditLink()
	{
		return '/Admin/Locations?objectAction=edit&id=' . $this->libraryId;
	}

	protected $_parentLibrary = null;
	/** @return Library */
	public function getParentLibrary()
	{
		if ($this->_parentLibrary == null){
			$this->_parentLibrary = new Library();
			$this->_parentLibrary->libraryId = $this->libraryId;
			$this->_parentLibrary->find(true);
		}
		return $this->_parentLibrary;
	}

	public function setGroupedWorkDisplaySettings(GroupedWorkDisplaySetting $newGroupedWorkDisplaySettings)
	{
		$this->_groupedWorkDisplaySettings = $newGroupedWorkDisplaySettings;
		$this->groupedWorkDisplaySettingId = $newGroupedWorkDisplaySettings->id;
	}

	/**
	 * @param boolean $restrictByHomeLibrary whether or not only locations for the patron's home library should be returned
	 * @return array
	 */
	static function getLocationList($restrictByHomeLibrary): array
	{
		$location = new Location();
		$location->orderBy('displayName');
		if ($restrictByHomeLibrary) {
			$homeLibrary = Library::getPatronHomeLibrary();
			if ($homeLibrary != null) {
				$location->libraryId = $homeLibrary->libraryId;
			}
		}
		$location->find();
		$locationList = [];
		while ($location->fetch()) {
			$locationList[$location->locationId] = $location->displayName;
		}
		return $locationList;
	}

	protected $_browseCategoryGroup = null;

	/**
	 * @return BrowseCategoryGroup|null
	 */
	public function getBrowseCategoryGroup(){
		if ($this->_browseCategoryGroup == null){
			if ($this->browseCategoryGroupId == -1){
				$this->_browseCategoryGroup = $this->getParentLibrary()->getBrowseCategoryGroup();
			}else{
				require_once ROOT_DIR . '/sys/Browse/BrowseCategoryGroup.php';
				$browseCategoryGroup = new BrowseCategoryGroup();
				$browseCategoryGroup->id = $this->browseCategoryGroupId;
				if ($browseCategoryGroup->find(true)){
					$this->_browseCategoryGroup = $browseCategoryGroup;
				}
			}
		}
		return $this->_browseCategoryGroup;
	}
}
