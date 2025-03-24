<?php /** @noinspection PhpMissingFieldTypeInspection */
/** @noinspection RequiredAttributes */
/** @noinspection HtmlRequiredAltAttribute */

require_once ROOT_DIR . '/sys/DB/DataObject.php';
require_once ROOT_DIR . '/sys/LibraryLocation/LocationHours.php';
require_once ROOT_DIR . '/sys/LibraryLocation/LocationCombinedResultSection.php';
require_once ROOT_DIR . '/sys/LibraryLocation/LocationTheme.php';
if (file_exists(ROOT_DIR . '/sys/Browse/BrowseCategoryGroup.php')) {
	require_once ROOT_DIR . '/sys/Browse/BrowseCategoryGroup.php';
}
if (file_exists(ROOT_DIR . '/sys/Indexing/LocationRecordToInclude.php')) {
	require_once ROOT_DIR . '/sys/Indexing/LocationRecordToInclude.php';
}
if (file_exists(ROOT_DIR . '/sys/Indexing/LocationSideLoadScope.php')) {
	require_once ROOT_DIR . '/sys/Indexing/LocationSideLoadScope.php';
}
if (file_exists(ROOT_DIR . '/sys/AspenLiDA/SelfCheckSetting.php')) {
	require_once ROOT_DIR . '/sys/AspenLiDA/SelfCheckSetting.php';
}
if (file_exists(ROOT_DIR . '/sys/OpenArchives/OpenArchivesFacet.php')) {
	require_once ROOT_DIR . '/sys/OpenArchives/OpenArchivesFacet.php';
}
if (file_exists(ROOT_DIR . '/sys/WebsiteIndexing/WebsiteFacet.php')) {
	require_once ROOT_DIR . '/sys/WebsiteIndexing/WebsiteFacet.php';
}

require_once ROOT_DIR . '/sys/CloudLibrary/LocationCloudLibraryScope.php';
require_once ROOT_DIR . '/sys/Events/EventsBranchMapping.php';


class Location extends DataObject {
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
	public $createSearchInterface;
	public $showInSelectInterface;
	public $showOnDonationsPage;
	public $enableAppAccess;
	public $appReleaseChannel;
	public $theme;
	public $useLibraryThemes;
	public $_themes;
	public $showDisplayNameInHeader;
	public $languageAndDisplayInHeader;
	public $displayExploreMoreBarInSummon;
	public $displayExploreMoreBarInEbscoEds;
	public $displayExploreMoreBarInEbscoHost;
	public $displayExploreMoreBarInCatalogSearch;
	public $headerText;
	public $address;
	public $phone;
	public $secondaryPhoneNumber;
	public $contactEmail;
	public $latitude;
	public $longitude;
	public $unit;
	public $tty;
	public $description;
	public $isMainBranch; // tinyint(1)
	public $showInLocationsAndHoursList;
	public $validHoldPickupBranch;    //'1' => 'Valid for all patrons', '0' => 'Valid for patrons of this branch only', '2' => 'Not Valid', '3' => 'Valid for patrons of this library only'
	public $validSelfRegistrationBranch;
	public $nearbyLocation1;        //int(11)
	public $nearbyLocation2;        //int(11)
	public $scope;
	public $useScope;
	public $statGroup;
	public $circulationUsername;
	public $facetLabel;
	public $groupedWorkDisplaySettingId;
	public $browseCategoryGroupId;
	public $restrictSearchByLocation;
	public /** @noinspection PhpUnused */
		$hooplaScopeId;
	public /** @noinspection PhpUnused */
		$axis360ScopeId;
	public /** @noinspection PhpUnused */
		$palaceProjectScopeId;
	public $showHoldButton;
	public $curbsidePickupInstructions;
	public $repeatSearchOption;
	public $repeatInOnlineCollection;
	public $repeatInInnReach;
	public $repeatInWorldCat;
	public $repeatInShareIt;
	public $repeatInCloudSource;
	public $vdxFormId;
	public $vdxLocation;
	public $localIllFormId;
	public $systemsToRepeatIn;
	public $homeLink;
	public $ptypesToAllowRenewals;
	public /** @noinspection PhpUnused */
		$publicListsToInclude;
	public $automaticTimeoutLength;
	public $automaticTimeoutLengthLoggedOut;
	public $additionalCss;
	public $showEmailThis;
	public $showShareOnExternalSites;
	public $showFavorites;
	public /** @noinspection PhpUnused */
		$econtentLocationsToInclude;
	public /** @noinspection PhpUnused */
		$includeAllLibraryBranchesInFacets;
	public /** @noinspection PhpUnused */
		$additionalLocationsToShowAvailabilityFor;
	public /** @noinspection PhpUnused */
		$includeLibraryRecordsToInclude;

	//Combined Results (Bento Box)
	public /** @noinspection PhpUnused */
		$enableCombinedResults;
	public $combinedResultsLabel;
	public /** @noinspection PhpUnused */
		$defaultToCombinedResults;
	public $useLibraryCombinedResultsSettings;

	/** @noinspection PhpUnused */
	public $allowUpdatingHoursFromILS;

	protected $_hours;
	private $_moreDetailsOptions;
	private $_recordsToInclude;
	private $_sideLoadScopes;
	private $_combinedResultSections;
	private $_cloudLibraryScope;
	/**
	 * @var array|LocationHours[]|mixed|null
	 */
	public $ebscohostSearchSettingId;

	private $_sublocations;

	//LiDA Settings
	public $lidaLocationSettingId;
	public $lidaSelfCheckSettingId;

	//Facet Settings
	public $openArchivesFacetSettingId;
	public $websiteIndexingFacetSettingId;

	public $locationImage;

	function getNumericColumnNames(): array {
		return [
			'scope',
			'statGroup',
			'isMainBranch',
			'showInLocationsAndHoursList',
			'validHoldPickupBranch',
			'useScope',
			'restrictSearchByLocation',
			'showHoldButton',
			'repeatInOnlineCollection',
			'repeatInInnReach',
			'repeatInWorldCat',
			'repeatInShareIt',
			'repeatInCloudSource',
			'showEmailThis',
			'showShareOnExternalSites',
			'showFavorites',
			'includeAllLibraryBranchesInFacets',
			'includeAllRecordsInShelvingFacets',
			'includeAllRecordsInDateAddedFacets',
			'includeLibraryRecordsToInclude',
			'enableCombinedResults',
			'defaultToCombinedResults',
			'useLibraryCombinedResultsSettings',
			'ebscohostSearchSettingId',
			'lidaSelfCheckSettingId'
		];
	}

	static function getObjectStructure($context = ''): array {
		//Load Libraries for lookup values
		$library = new Library();
		$library->orderBy('displayName');
		if (!UserAccount::userHasPermission('Administer All Libraries')) {
			$homeLibrary = Library::getPatronHomeLibrary();
			$library->libraryId = $homeLibrary->libraryId;
		}
		$library->find();
		$libraryList = [];
		while ($library->fetch()) {
			$libraryList[$library->libraryId] = $library->displayName;
		}

		//Look lookup information for display in the user interface
		$location = new Location();
		$location->orderBy('displayName');
		$location->find();
		$locationList = [];
		$locationLookupList = [];
		$locationLookupList[-1] = '<No Nearby Location>';
		while ($location->fetch()) {
			$locationLookupList[$location->locationId] = $location->displayName;
			$locationList[$location->locationId] = clone $location;
		}

		// get the structure for the location's hours
		$hoursStructure = LocationHours::getObjectStructure($context);

		// we don't want to make the locationId property editable
		// because it is associated with this location only
		unset($hoursStructure['locationId']);

		require_once ROOT_DIR . '/sys/Browse/BrowseCategoryGroup.php';
		$browseCategoryGroup = new BrowseCategoryGroup();
		$browseCategoryGroups = [];
		$browseCategoryGroups[-1] = 'Use Library Setting';
		$browseCategoryGroup->orderBy('name');
		$browseCategoryGroup->find();
		while ($browseCategoryGroup->fetch()) {
			$browseCategoryGroups[$browseCategoryGroup->id] = $browseCategoryGroup->name;
		}

		$locationMoreDetailsStructure = LocationMoreDetails::getObjectStructure($context);
		unset($locationMoreDetailsStructure['weight']);
		unset($locationMoreDetailsStructure['locationId']);

		$locationRecordToIncludeStructure = LocationRecordToInclude::getObjectStructure($context);
		unset($locationRecordToIncludeStructure['locationId']);
		unset($locationRecordToIncludeStructure['weight']);

		$combinedResultsStructure = LocationCombinedResultSection::getObjectStructure($context);
		unset($combinedResultsStructure['locationId']);
		unset($combinedResultsStructure['weight']);

		$locationThemeStructure = LocationTheme::getObjectStructure($context);
		unset($locationThemeStructure['locationId']);
		unset($locationThemeStructure['weight']);

		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkDisplaySetting.php';
		$groupedWorkDisplaySetting = new GroupedWorkDisplaySetting();
		$groupedWorkDisplaySetting->orderBy('name');
		$groupedWorkDisplaySettings = [];
		$groupedWorkDisplaySettings[-1] = 'Use Library Settings';
		$groupedWorkDisplaySetting->find();
		while ($groupedWorkDisplaySetting->fetch()) {
			$groupedWorkDisplaySettings[$groupedWorkDisplaySetting->id] = $groupedWorkDisplaySetting->name;
		}

		require_once ROOT_DIR . '/sys/VDX/VdxSetting.php';
		$vdxActive = false;
		$vdxForms = [];
		$vdxSettings = new VdxSetting();
		if ($vdxSettings->find(true)) {
			$vdxActive = true;
			require_once ROOT_DIR . '/sys/VDX/VdxForm.php';
			$vdxForm = new VdxForm();
			$vdxForm->find();
			$vdxForm->orderBy('name');
			$vdxForms[-1] = 'Select a form';
			while ($vdxForm->fetch()) {
				$vdxForms[$vdxForm->id] = $vdxForm->name;
			}
		}

		require_once ROOT_DIR . '/sys/InterLibraryLoan/LocalIllForm.php';
		$localIllForms = [];
		require_once ROOT_DIR . '/sys/InterLibraryLoan/LocalIllForm.php';
		$localIllForm = new LocalIllForm();
		$localIllForm->find();
		$localIllForm->orderBy('name');
		$localIllForms[-1] = 'Select a form';
		while ($localIllForm->fetch()) {
			$localIllForms[$localIllForm->id] = $localIllForm->name;
		}

		$hasScoping = false;
		$isKohaActive = false;
		foreach (UserAccount::getAccountProfiles() as $accountProfileInfo) {
			/** @var AccountProfile $accountProfile */
			$accountProfile = $accountProfileInfo['accountProfile'];
			if ($accountProfile->ils == 'sierra' || $accountProfile->ils == 'millennium') {
				$hasScoping = true;
				$isKohaActive = true;
			}
		}
		global $enabledModules;

		require_once ROOT_DIR . '/sys/LibraryLocation/Sublocation.php';
		$sublocationSettingsStructure = Sublocation::getObjectStructure('locations');
		unset($sublocationSettingsStructure['weight']);
		unset($sublocationSettingsStructure['locationId']);

		$structure = [
			'locationId' => [
				'property' => 'locationId',
				'type' => 'label',
				'label' => 'Location Id',
				'description' => 'The unique id of the location within the database',
			],
			'subdomain' => [
				'property' => 'subdomain',
				'type' => 'text',
				'label' => 'Subdomain',
				'description' => 'The subdomain to use while identifying this branch.  Can be left if it matches the code.',
				'required' => false,
				'forcesReindex' => true,
				'canBatchUpdate' => false,
				'permissions' => ['Location Domain Settings'],
			],
			'code' => [
				'property' => 'code',
				'type' => 'text',
				'label' => 'Code',
				'description' => 'The code for use when communicating with the ILS',
				'required' => true,
				'forcesReindex' => true,
				'canBatchUpdate' => false,
				'permissions' => ['Location Domain Settings'],
			],
			'historicCode' => [
				'property' => 'historicCode',
				'type' => 'text',
				'label' => 'Historic Code',
				'description' => 'A historic code that can be used in some instances as a substitute for code',
				'hideInLists' => true,
				'required' => false,
				'forcesReindex' => false,
				'canBatchUpdate' => false,
				'permissions' => ['Location Domain Settings'],
			],
			'subLocation' => [
				'property' => 'subLocation',
				'type' => 'text',
				'label' => 'Sub Location Code',
				'description' => 'The sub location or collection used to identify this ',
				'forcesReindex' => true,
				'canBatchUpdate' => false,
				'permissions' => ['Location Domain Settings'],
			],
			'displayName' => [
				'property' => 'displayName',
				'type' => 'text',
				'label' => 'Display Name',
				'description' => 'The full name of the location for display to the user',
				'size' => '40',
				'forcesReindex' => true,
				'canBatchUpdate' => false,
				'editPermissions' => ['Location Domain Settings'],
			],
			'locationImage' => [
				'property' => 'locationImage',
				'type' => 'image',
				'label' => 'Location Image',
				'description' => '',
				'required' => false,
				'thumbWidth' => 400,
				'maxWidth' => 1170,
				'maxHeight' => 400,
				'hideInLists' => true,
				'affectsLiDA' => true,
			],
			'createSearchInterface' => [
				'property' => 'createSearchInterface',
				'type' => 'checkbox',
				'label' => 'Create Search Interface',
				'description' => 'Whether or not a search interface is created.  Things like lockers and drive through windows do not need search interfaces.',
				'forcesReindex' => true,
				'editPermissions' => ['Location Domain Settings'],
				'default' => true,
			],
			'showInSelectInterface' => [
				'property' => 'showInSelectInterface',
				'type' => 'checkbox',
				'label' => 'Show In Select Interface (requires Create Search Interface)',
				'description' => 'Whether or not this Location will show in the Select Interface Page.',
				'forcesReindex' => false,
				'editPermissions' => ['Location Domain Settings'],
				'default' => true,
			],
			'showOnDonationsPage' => [
				'property' => 'showOnDonationsPage',
				'type' => 'checkbox',
				'label' => 'Show Location on Donations page',
				'description' => 'Whether or not this Location will show on the Donation page.',
				'forcesReindex' => false,
				'editPermissions' => ['Location Domain Settings'],
				'default' => true,
			],
			'useLibraryThemes' => [
				'property' => 'useLibraryThemes',
				'type' => 'checkbox',
				'label' => 'Use Library Themes',
				'description' => "Whether or not this location will use it's own themes or use themes from the parent library.",
				'forcesReindex' => false,
				'editPermissions' => ['Location Theme Configuration'],
				'default' => true,
				'onchange' => 'return AspenDiscovery.Admin.updateLocationFields()'
			],
			'themes' => [
				'property' => 'themes',
				'type' => 'oneToMany',
				'label' => 'Themes',
				'description' => 'The themes which can be used for the location',
				'keyThis' => 'locationId',
				'keyOther' => 'locationId',
				'subObjectType' => 'LocationTheme',
				'structure' => $locationThemeStructure,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => true,
				'canAddNew' => true,
				'canDelete' => true,
				'editPermissions' => ['Location Theme Configuration'],
			],
			'libraryId' => [
				'property' => 'libraryId',
				'type' => 'enum',
				'values' => $libraryList,
				'label' => 'Library',
				'description' => 'A link to the library which the location belongs to',
				'editPermissions' => ['Location Domain Settings'],
			],
			'isMainBranch' => [
				'property' => 'isMainBranch',
				'type' => 'checkbox',
				'label' => 'Is Main Branch',
				'description' => 'Is this location the main branch for it\'s library',
				'default' => false,
				'canBatchUpdate' => false,
				'editPermissions' => ['Location Domain Settings'],
			],
			'showInLocationsAndHoursList' => [
				'property' => 'showInLocationsAndHoursList',
				'type' => 'checkbox',
				'label' => 'Show In Locations And Hours List',
				'description' => 'Whether or not this location should be shown in the list of library hours and locations',
				'hideInLists' => true,
				'default' => true,
				'editPermissions' => ['Location Address and Hours Settings'],
				'affectsLiDA' => true,
			],
			'address' => [
				'property' => 'address',
				'type' => 'textarea',
				'label' => 'Address',
				'description' => 'The address of the branch.',
				'hideInLists' => true,
				'editPermissions' => ['Location Address and Hours Settings'],
				'affectsLiDA' => true,
			],
			'phone' => [
				'property' => 'phone',
				'type' => 'text',
				'label' => 'Phone Number',
				'description' => 'The main phone number for the site .',
				'maxLength' => '25',
				'hideInLists' => true,
				'editPermissions' => ['Location Address and Hours Settings'],
				'affectsLiDA' => true,
			],
			'secondaryPhoneNumber' => [
				'property' => 'secondaryPhoneNumber',
				'type' => 'text',
				'label' => 'Secondary Phone Number',
				'description' => 'The secondary phone number for the site .',
				'maxLength' => '25',
				'hideInLists' => true,
				'editPermissions' => ['Location Address and Hours Settings'],
			],
			'contactEmail' => [
				'property' => 'contactEmail',
				'type' => 'text',
				'label' => 'Email Address',
				'description' => 'The main public email address for the site .',
				'hideInLists' => true,
				'editPermissions' => ['Location Address and Hours Settings'],
				'affectsLiDA' => true,
			],
			'latitude' => [
				'property' => 'latitude',
				'type' => 'text',
				'label' => 'Address Latitude',
				'description' => 'The latitude of the address provided.',
				'maxLength' => '25',
				'hideInLists' => true,
				'editPermissions' => ['Location Address and Hours Settings'],
				'affectsLiDA' => true,
			],
			'longitude' => [
				'property' => 'longitude',
				'type' => 'text',
				'label' => 'Address Longitude',
				'description' => 'The longitude of the address provided',
				'maxLength' => '25',
				'hideInLists' => true,
				'editPermissions' => ['Location Address and Hours Settings'],
				'affectsLiDA' => true,
			],
			'unit' => [
				'property' => 'unit',
				'type' => 'text',
				'label' => 'Units for Distance (Mi/Km)',
				'description' => 'The unit of measurement for distance',
				'maxLength' => '2',
				'hideInLists' => true,
				'editPermissions' => ['Location Address and Hours Settings'],
				'affectsLiDA' => true,
			],
			'tty' => [
				'property' => 'tty',
				'type' => 'text',
				'label' => 'TTY Number',
				'description' => 'The tty number for the site .',
				'maxLength' => '25',
				'hideInLists' => true,
				'editPermissions' => ['Location Address and Hours Settings'],
			],
			'description' => [
				'property' => 'description',
				'type' => 'markdown',
				'label' => 'Description',
				'description' => 'Allows the display of a description in the Location and Hours dialog',
				'hideInLists' => true,
				'editPermissions' => ['Location Address and Hours Settings'],
			],
			'nearbyLocation1' => [
				'property' => 'nearbyLocation1',
				'type' => 'enum',
				'values' => $locationLookupList,
				'label' => 'Nearby Location 1',
				'description' => 'A secondary location which is nearby and could be used for pickup of materials.',
				'hideInLists' => true,
				'permissions' => ['Location Catalog Options'],
			],
			'nearbyLocation2' => [
				'property' => 'nearbyLocation2',
				'type' => 'enum',
				'values' => $locationLookupList,
				'label' => 'Nearby Location 2',
				'description' => 'A tertiary location which is nearby and could be used for pickup of materials.',
				'hideInLists' => true,
				'permissions' => ['Location Catalog Options'],
			],
			'automaticTimeoutLength' => [
				'property' => 'automaticTimeoutLength',
				'type' => 'integer',
				'label' => 'Automatic Timeout Length (logged in)',
				'description' => 'The length of time before the user is automatically logged out in seconds.',
				'size' => '8',
				'hideInLists' => true,
				'default' => self::DEFAULT_AUTOLOGOUT_TIME,
				'permissions' => ['Location Catalog Options'],
			],
			'automaticTimeoutLengthLoggedOut' => [
				'property' => 'automaticTimeoutLengthLoggedOut',
				'type' => 'integer',
				'label' => 'Automatic Timeout Length (logged out)',
				'description' => 'The length of time before the catalog resets to the home page set to 0 to disable.',
				'size' => '8',
				'hideInLists' => true,
				'default' => self::DEFAULT_AUTOLOGOUT_TIME_LOGGED_OUT,
				'permissions' => ['Location Catalog Options'],
			],

			'displaySection' => [
				'property' => 'displaySection',
				'type' => 'section',
				'label' => 'Basic Display',
				'hideInLists' => true,
				'properties' => [
					'showDisplayNameInHeader' => [
						'property' => 'showDisplayNameInHeader',
						'type' => 'checkbox',
						'label' => 'Show Display Name in Header',
						'description' => 'Whether or not the display name should be shown in the header next to the logo',
						'hideInLists' => true,
						'default' => false,
						'permissions' => ['Location Theme Configuration'],
					],
					'languageAndDisplayInHeader' => [
						'property' => 'languageAndDisplayInHeader',
						'type' => 'checkbox',
						'label' => 'Show language and display settings in page header',
						'description' => 'Whether or not to display the language and display settings in the page header',
						'hideInLists' => true,
						'default' => true,
						'permissions' => ['Location Theme Configuration'],
					],
					[
						'property' => 'homeLink',
						'type' => 'text',
						'label' => 'Home Link',
						'description' => 'The location to send the user when they click on the home button or logo.  Use default or blank to go back to the aspen home location.',
						'hideInLists' => true,
						'size' => '40',
						'editPermissions' => ['Location Domain Settings'],
						'affectsLiDA' => true,
					],
					[
						'property' => 'additionalCss',
						'type' => 'textarea',
						'label' => 'Additional CSS',
						'description' => 'Extra CSS to apply to the site.  Will apply to all pages.',
						'hideInLists' => true,
						'permissions' => ['Location Theme Configuration'],
					],
					[
						'property' => 'headerText',
						'type' => 'html',
						'label' => 'Header Text',
						'description' => 'Optional Text to display in the header, between the logo and the log in/out buttons.  Will apply to all pages.',
						'allowableTags' => '<p><em><i><strong><b><a><ul><ol><li><h1><h2><h3><h4><h5><h6><h7><pre><code><hr><table><tbody><tr><th><td><caption><img><br><div><span><sub><sup>',
						'hideInLists' => true,
						'permissions' => ['Location Theme Configuration'],
					],
				],
			],

			'ilsSection' => [
				'property' => 'ilsSection',
				'type' => 'section',
				'label' => 'ILS/Account Integration',
				'hideInLists' => true,
				'properties' => [
					'scope' => [
						'property' => 'scope',
						'type' => 'text',
						'label' => 'Scope',
						'description' => 'The scope for the system in Millennium to refine holdings to the branch.  If there is no scope defined for the branch, this can be set to 0.',
						'default' => 0,
						'forcesReindex' => true,
						'permissions' => ['Location ILS Connection'],
					],
					'useScope' => [
						'property' => 'useScope',
						'type' => 'checkbox',
						'label' => 'Use Scope?',
						'description' => 'Whether or not the scope should be used when displaying holdings.',
						'hideInLists' => true,
						'forcesReindex' => true,
						'permissions' => ['Location ILS Connection'],
					],
					'statGroup' => [
						'property' => 'statGroup',
						'type' => 'integer',
						'label' => 'Stat Group',
						'description' => 'The statistical group to use for reporting checkouts',
						'default' => -1,
						'permissions' => ['Location ILS Connection'],
						'note' => 'Sierra only, set to -1 to ignore',
					],
					'circulationUsername' => [
						'property' => 'circulationUsername',
						'type' => 'text',
						'label' => 'Circulation Username',
						'description' => 'The username to use when checking titles in and out for Sierra',
						'default' => '',
						'permissions' => ['Location ILS Connection'],
						'note' => 'Sierra only',
					],
					[
						'property' => 'validHoldPickupBranch',
						'type' => 'enum',
						'values' => [
							'1' => 'Valid for all patrons',
							'3' => 'Valid for patrons of this library only',
							'0' => 'Valid for patrons of this branch only',
							'2' => 'Not Valid',
						],
						'label' => 'Valid Hold Pickup Branch?',
						'description' => 'Determines if the location can be used as a pickup location if it is not the patrons home location or the location they are in.',
						'hideInLists' => true,
						'default' => '1',
						'permissions' => ['Location ILS Options'],
					],
					[
						'property' => 'validSelfRegistrationBranch',
						'type' => 'enum',
						'values' => [
							'1' => 'Valid for all libraries',
							'3' => 'Valid for this library only',
							'2' => 'Not Valid',
						],
						'label' => 'Valid Self Registration Branch?',
						'description' => 'Determines if the location can be used for self registration.',
						'hideInLists' => true,
						'default' => '1',
						'permissions' => ['Location ILS Options'],
					],
					[
						'property' => 'showHoldButton',
						'type' => 'checkbox',
						'label' => 'Show Hold Button',
						'description' => 'Whether or not the hold button is displayed so patrons can place holds on items',
						'hideInLists' => true,
						'default' => true,
						'permissions' => ['Location ILS Options'],
					],
					[
						'property' => 'ptypesToAllowRenewals',
						'type' => 'text',
						'label' => 'PTypes that can renew (Millennium/Sierra)',
						'description' => 'A list of P-Types that can renew items or * to allow all P-Types to renew items.',
						'hideInLists' => true,
						'default' => '*',
						'permissions' => ['Location ILS Connection'],
					],
				],
			],

			// Catalog Enrichment //
			'enrichmentSection' => [
				'property' => 'enrichmentSection',
				'type' => 'section',
				'label' => 'Catalog Enrichment',
				'hideInLists' => true,
				'permissions' => ['Library Catalog Options'],
				'properties' => [
					'showFavorites' => [
						'property' => 'showFavorites',
						'type' => 'checkbox',
						'label' => 'Enable User Lists',
						'description' => 'Whether or not users can maintain favorites lists',
						'hideInLists' => true,
						'default' => 1,
					],
				],
			],

			//Curbside pickup for Koha plugin
			'curbsidePickupSettings' => [
				'property' => 'curbsidePickupInstructions',
				'type' => 'textarea',
				'label' => 'Patron instructions for curbside pickup',
				'description' => 'Instructions specific to this location for instructions to patrons when checking-in for picking up curbside.',
				'hideInLists' => true,
				'permissions' => ['Location ILS Connection'],
				'note' => 'Koha only, requires Curbside Pickup plugin',
			],

			//Grouped Work Display
			'groupedWorkDisplaySettingId' => [
				'property' => 'groupedWorkDisplaySettingId',
				'type' => 'enum',
				'values' => $groupedWorkDisplaySettings,
				'label' => 'Grouped Work Display Settings',
				'hideInLists' => false,
				'permissions' => ['Location Catalog Options'],
			],

			'searchingSection' => [
				'property' => 'searchingSection',
				'type' => 'section',
				'label' => 'Searching',
				'hideInLists' => true,
				'properties' => [
					[
						'property' => 'restrictSearchByLocation',
						'type' => 'checkbox',
						'label' => 'Restrict Search By Location',
						'description' => 'Whether or not search results should only include titles from this location',
						'hideInLists' => true,
						'default' => false,
						'forcesReindex' => true,
						'permissions' => ['Location Records included in Catalog'],
					],
					[
						'property' => 'publicListsToInclude',
						'type' => 'enum',
						'values' => [
							0 => 'No Lists',
							'1' => 'Lists from this library',
							'4' => 'Lists from library list publishers Only',
							'2' => 'Lists from this location',
							'5' => 'Lists from list publishers at this location Only',
							'6' => 'Lists from all list publishers',
							'3' => 'All Lists',
						],
						'label' => 'Public Lists To Include',
						'description' => 'Which lists should be included in this scope',
						'default' => '4',
						'forcesListReindex' => true,
						'permissions' => ['Location Catalog Options'],
					],
					[
						'property' => 'searchBoxSection',
						'type' => 'section',
						'label' => 'Search Box',
						'hideInLists' => true,
						'permissions' => ['Location Catalog Options'],
						'properties' => [
							[
								'property' => 'systemsToRepeatIn',
								'type' => 'text',
								'label' => 'Systems To Repeat In',
								'description' => 'A list of library codes that you would like to repeat search in separated by pipes |.',
								'hideInLists' => true,
							],
							[
								'property' => 'repeatSearchOption',
								'type' => 'enum',
								'values' => [
									'none' => 'None',
									'librarySystem' => 'Library System',
									'marmot' => 'Entire Consortium',
								],
								'label' => 'Repeat Search Options (requires Restrict Search By Location to be ON)',
								'description' => 'Where to allow repeating search. Valid options are: none, librarySystem, marmot, all',
								'default' => 'marmot',
							],
							[
								'property' => 'repeatInOnlineCollection',
								'type' => 'checkbox',
								'label' => 'Repeat In Online Collection',
								'description' => 'Turn on to allow repeat search in the Online Collection.',
								'hideInLists' => true,
								'default' => false,
							],
							[
								'property' => 'repeatInInnReach',
								'type' => 'checkbox',
								'label' => 'Repeat In INN-Reach',
								'description' => 'Turn on to allow repeat search in INN-Reach functionality.',
								'hideInLists' => true,
								'default' => false,
							],
							[
								'property' => 'repeatInShareIt',
								'type' => 'checkbox',
								'label' => 'Repeat In SHAREit',
								'description' => 'Turn on to allow repeat search in SHAREit functionality.',
								'hideInLists' => true,
								'default' => false,
							],
							[
								'property' => 'repeatInWorldCat',
								'type' => 'checkbox',
								'label' => 'Repeat In WorldCat',
								'description' => 'Turn on to allow repeat search in WorldCat functionality.',
								'hideInLists' => true,
								'default' => false,
							],
							[
								'property' => 'repeatInCloudSource',
								'type' => 'checkbox',
								'label' => 'Repeat In CloudSource',
								'description' => 'Turn on to allow repeat search in CloudSource functionality.',
								'hideInLists' => true,
								'default' => false,
							],
						],
					],
					[
						'property' => 'searchFacetsSection',
						'type' => 'section',
						'label' => 'Search Facets',
						'hideInLists' => true,
						'permissions' => ['Location Catalog Options'],
						'properties' => [
							[
								'property' => 'facetLabel',
								'type' => 'text',
								'label' => 'Facet Label',
								'description' => 'The label of the facet that identifies this location.',
								'hideInLists' => true,
								'size' => '40',
								'maxLength' => 75,
								'forcesReindex' => true,
							],
							[
								'property' => 'includeAllLibraryBranchesInFacets',
								'type' => 'checkbox',
								'label' => 'Include All Library Branches In Facets',
								'description' => 'Turn on to include all branches of the library within facets (ownership and availability).',
								'hideInLists' => true,
								'default' => true,
								'forcesReindex' => true,
							],
							[
								'property' => 'additionalLocationsToShowAvailabilityFor',
								'type' => 'text',
								'label' => 'Additional Locations to Include in Available At Facet',
								'description' => 'A list of library codes that you would like included in the available at facet separated by pipes |.',
								'size' => '20',
								'hideInLists' => true,
								'forcesReindex' => true,
							],
						],
					],
					'combinedResultsSection' => [
						'property' => 'combinedResultsSection',
						'type' => 'section',
						'label' => 'Combined Results',
						'hideInLists' => true,
						'permissions' => ['Location Catalog Options'],
						'properties' => [
							'useLibraryCombinedResultsSettings' => [
								'property' => 'useLibraryCombinedResultsSettings',
								'type' => 'checkbox',
								'label' => 'Use Library Settings',
								'description' => 'Whether or not settings from the library should be used rather than settings from here',
								'hideInLists' => true,
								'default' => true,
							],
							'enableCombinedResults' => [
								'property' => 'enableCombinedResults',
								'type' => 'checkbox',
								'label' => 'Enable Combined Results',
								'description' => 'Whether or not combined results should be shown ',
								'hideInLists' => true,
								'default' => false,
							],
							'combinedResultsLabel' => [
								'property' => 'combinedResultsLabel',
								'type' => 'text',
								'label' => 'Combined Results Label',
								'description' => 'The label to use in the search source box when combined results is active.',
								'size' => '20',
								'hideInLists' => true,
								'default' => 'Combined Results',
							],
							'defaultToCombinedResults' => [
								'property' => 'defaultToCombinedResults',
								'type' => 'checkbox',
								'label' => 'Default To Combined Results',
								'description' => 'Whether or not combined results should be the default search source when active ',
								'hideInLists' => true,
								'default' => true,
							],
							'combinedResultSections' => [
								'property' => 'combinedResultSections',
								'type' => 'oneToMany',
								'label' => 'Combined Results Sections',
								'description' => 'Which sections should be shown in the combined results search display',
								'keyThis' => 'locationId',
								'keyOther' => 'locationId',
								'subObjectType' => 'LocationCombinedResultSection',
								'structure' => $combinedResultsStructure,
								'sortable' => true,
								'storeDb' => true,
								'allowEdit' => true,
								'canEdit' => false,
								'additionalOneToManyActions' => [],
								'canAddNew' => true,
								'canDelete' => true,
							],
						],
					],
				],
			],

			'exploreMoreBarSection' => [
				'property' => 'exploreMoreBarSection',
				'type' => 'section',
				'label' => 'Explore More Bar Section',
				'hideInLists' => true,
				'properties' => [
					'displayExploreMoreBarInCatalogSearch' => [
						'property' => 'displayExploreMoreBarInCatalogSearch',
						'type' => 'checkbox',
						'label' => 'Display Explore More Bar in Catalog Search Results',
						'description' => 'Whether to display the Explore More Bar in catalog search results',
						'hideInLists' => true,
						'default' => true,
					],
					'displayExploreMoreBarInSummon' => [
						'property' => 'displayExploreMoreBarInSummon',
						'type' => 'checkbox',
						'label' => 'Display Explore More Bar in Summon Search Results',
						'description' => 'Whether to display the Explore More Bar in Summon search results',
						'hideInLists' => true,
						'default' => true,
					],
					'displayExploreMoreBarInEbscoEds' => [
						'property' => 'displayExploreMoreBarInEbscoEds',
						'type' => 'checkbox',
						'label' => 'Display Explore More Bar in Ebsco EDS Search Results',
						'description' => 'Whether to display the Explore More Bar in Ebsco EDS search results',
						'hideInLists' => true,
						'default' => true,
					],
					'displayExploreMoreBarInEbscoHost' => [
						'property' => 'displayExploreMoreBarInEbscoHost',
						'type' => 'checkbox',
						'label' => 'Display Explore More Bar in Ebsco Host Search Results',
						'description' => 'Whether to display the Explore More Bar in Ebsco Host search results',
						'hideInLists' => true,
						'default' => true,
					],
				],
			],

			// Full Record Display //
			'fullRecordSection' => [
				'property' => 'fullRecordSection',
				'type' => 'section',
				'label' => 'Full Record Display',
				'hideInLists' => true,
				'permissions' => ['Location Catalog Options'],
				'properties' => [
					'showEmailThis' => [
						'property' => 'showEmailThis',
						'type' => 'checkbox',
						'label' => 'Show Email This',
						'description' => 'Whether or not the Email This link is shown',
						'hideInLists' => true,
						'default' => 1,
					],
					'showShareOnExternalSites' => [
						'property' => 'showShareOnExternalSites',
						'type' => 'checkbox',
						'label' => 'Show Sharing To External Sites',
						'description' => 'Whether or not sharing on external sites (Twitter, Facebook, Pinterest, etc. is shown)',
						'hideInLists' => true,
						'default' => 1,
					],
					'moreDetailsOptions' => [
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
						'canAddNew' => true,
						'canDelete' => true,
					],
				],
			],

			'browseCategoryGroupId' => [
				'property' => 'browseCategoryGroupId',
				'required' => true,
				'type' => 'enum',
				'affectsLiDA' => true,
				'values' => $browseCategoryGroups,
				'label' => 'Browse Category Group',
				'renderAsHeading' => true,
				'description' => 'The group of browse categories to show for this library',
				'hideInLists' => true,
				'permissions' => ['Location Browse Category Options'],
			],

			'interLibraryLoanSection' => [
				'property' => 'interLibraryLoanSectionSection',
				'type' => 'section',
				'label' => 'Interlibrary loans',
				'hideInLists' => true,
				'properties' => [
					'localIllFormId' => [
						'property' => 'localIllFormId',
						'type' => 'enum',
						'values' => $localIllForms,
						'label' => 'Local ILL Form',
						'description' => 'The form to use when submitting Local ILL requests',
						'permissions' => [
							'Library ILL Options',
							'Administer All Local ILL Forms'
						],
					],
					'vdxLocation' => [
						'property' => 'vdxLocation',
						'type' => 'text',
						'label' => 'VDX Location',
						'description' => 'The location code to send in the VDX email',
						'maxLength' => 50,
						'permissions' => ['Library ILL Options'],
					],
					'vdxFormId' => [
						'property' => 'vdxFormId',
						'type' => 'enum',
						'values' => $vdxForms,
						'label' => 'VDX Form',
						'description' => 'The form to use when submitting VDX requests',
						'permissions' => ['Library ILL Options'],
					],
				],
			],
		];

		if (array_key_exists('Axis 360', $enabledModules)) {
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

			$structure['axis360Section'] = [
				'property' => 'axis360Section',
				'type' => 'section',
				'label' => 'Boundless',
				'hideInLists' => true,
				'renderAsHeading' => true,
				'permissions' => ['Location Records included in Catalog'],
				'properties' => [
					'axis360ScopeId' => [
						'property' => 'axis360ScopeId',
						'type' => 'enum',
						'values' => $axis360Scopes,
						'label' => 'Boundless Scope',
						'description' => 'The Boundless scope to use',
						'hideInLists' => true,
						'default' => -1,
						'forcesReindex' => true,
					],
				],
			];
		}

		if (array_key_exists('Cloud Library', $enabledModules)) {
			$cloudLibraryScopes = [];
			$cloudLibraryScopes[-1] = 'none';
			require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibraryScope.php';
			$cloudLibraryScope = new CloudLibraryScope();
			$cloudLibraryScope->orderBy('name');
			$cloudLibraryScope->find();
			while ($cloudLibraryScope->fetch()) {
				$cloudLibraryScopes[$cloudLibraryScope->id] = $cloudLibraryScope->name;
			}
			$structure['cloudLibrarySection'] = [
				'property' => 'cloudLibrarySection',
				'type' => 'section',
				'label' => 'cloudLibrary',
				'hideInLists' => true,
				'renderAsHeading' => true,
				'permissions' => ['Location Records included in Catalog'],
				'properties' => [
					'cloudLibraryScope' => [
						'property' => 'cloudLibraryScope',
						'type' => 'enum',
						'values' => $cloudLibraryScopes,
						'label' => 'cloudLibrary Scope',
						'description' => 'The cloudLibrary scope to use',
						'hideInLists' => true,
						'default' => -1,
						'forcesReindex' => true,
					],
				],
			];
		}

		if (array_key_exists('Hoopla', $enabledModules)) {
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
			$structure['hooplaSection'] = [
				'property' => 'hooplaSection',
				'type' => 'section',
				'label' => 'Hoopla',
				'hideInLists' => true,
				'renderAsHeading' => true,
				'permissions' => ['Location Records included in Catalog'],
				'properties' => [
					'hooplaScopeId' => [
						'property' => 'hooplaScopeId',
						'type' => 'enum',
						'values' => $hooplaScopes,
						'label' => 'Hoopla Scope',
						'description' => 'The hoopla scope to use',
						'hideInLists' => true,
						'default' => -1,
						'forcesReindex' => true,
					],
				],
			];
		}

		if (array_key_exists('OverDrive', $enabledModules)) {
			require_once ROOT_DIR . '/sys/OverDrive/LocationOverDriveScope.php';
			$locationOverDriveScopeStructure = LocationOverDriveScope::getObjectStructure($context);
			unset($locationOverDriveScopeStructure['locationId']);

			$structure['overdriveSection'] = [
				'property' => 'overdriveSection',
				'type' => 'section',
				'label' => "Libby/Sora",
				'hideInLists' => true,
				'renderAsHeading' => true,
				'permissions' => ['Location Records included in Catalog'],
				'properties' => [
					'overDriveScopes' => [
						'property' => 'overDriveScopes',
						'type' => 'oneToMany',
						'label' => "Libby/Sora Scopes",
						'description' => "The Libby/Sora scopes to use",
						'keyThis' => 'locationId',
						'keyOther' => 'locationId',
						'subObjectType' => 'LocationOverDriveScope',
						'structure' => $locationOverDriveScopeStructure,
						'sortable' => false,
						'storeDb' => true,
						'allowEdit' => true,
						'canEdit' => true,
						'canAddNew' => true,
						'canDelete' => true,
						'forcesReindex' => true,
					],
				],
			];
		}

		if (array_key_exists('Palace Project', $enabledModules)) {
			require_once ROOT_DIR . '/sys/PalaceProject/PalaceProjectScope.php';
			$palaceProjectScope = new PalaceProjectScope();
			$palaceProjectScope->orderBy('name');
			$palaceProjectScopes = [];
			$palaceProjectScope->find();
			$palaceProjectScopes[-2] = 'None';
			$palaceProjectScopes[-1] = 'Use Library Setting';
			while ($palaceProjectScope->fetch()) {
				$palaceProjectScopes[$palaceProjectScope->id] = $palaceProjectScope->name;
			}
			$structure['palaceProjectSection'] = [
				'property' => 'palaceProjectSection',
				'type' => 'section',
				'label' => 'Palace Project',
				'hideInLists' => true,
				'renderAsHeading' => true,
				'permissions' => ['Location Records included in Catalog'],
				'properties' => [
					'palaceProjectScopeId' => [
						'property' => 'palaceProjectScopeId',
						'type' => 'enum',
						'values' => $palaceProjectScopes,
						'label' => 'Palace Project Scope',
						'description' => 'The Palace Project scope to use',
						'hideInLists' => true,
						'default' => -1,
						'forcesReindex' => true,
					],
				],
			];
		}

		if (array_key_exists('EBSCOhost', $enabledModules)) {
			require_once ROOT_DIR . '/sys/Ebsco/EBSCOhostSetting.php';
			$ebscohostSetting = new EBSCOhostSearchSetting();
			$ebscohostSetting->orderBy('name');
			$ebscohostSettings = [];
			$ebscohostSetting->find();
			$ebscohostSettings[-2] = 'None';
			while ($ebscohostSetting->fetch()) {
				$ebscohostSettings[$ebscohostSetting->id] = $ebscohostSetting->name;
			}
			$structure['ebscoSection'] = [
				'property' => 'ebscoSection',
				'type' => 'section',
				'label' => 'EBSCOhost',
				'hideInLists' => true,
				'renderAsHeading' => true,
				'permissions' => ['Location Records included in Catalog'],
				'properties' => [
					'ebscohostSearchSettingId' => [
						'property' => 'ebscohostSearchSettingId',
						'type' => 'enum',
						'values' => $ebscohostSettings,
						'label' => 'EBSCOhost Search Settings',
						'description' => 'The EBSCOhost Search Settings to use for connection',
						'hideInLists' => true,
						'default' => -2,
					],
				],
			];
		}

		$structure['hoursSection'] = [
			'property' => 'hoursSection',
			'type' => 'section',
			'label' => 'Library Hours',
			'hideInLists' => true,
			'renderAsHeading' => false,
			'permissions' => [
				'Location ILS Connection',
				'Location Address and Hours Settings'
			],
			'properties' => [
				'allowUpdatingHoursFromILS' => [
					'property' => 'allowUpdatingHoursFromILS',
					'type' => 'checkbox',
					'label' => 'Automatically update hours from the ILS',
					'description' => 'Whether closures should be automatically updated (Koha Only).',
					'hideInLists' => true,
					'default' => 1,
					'permissions' => ['Location ILS Connection'],
				],

				'hours' => [
					'property' => 'hours',
					'type' => 'oneToMany',
					'keyThis' => 'locationId',
					'keyOther' => 'locationId',
					'subObjectType' => 'LocationHours',
					'structure' => $hoursStructure,
					'label' => 'Hours',
					'renderAsHeading' => true,
					'description' => 'Library Hours',
					'sortable' => false,
					'storeDb' => true,
					'permissions' => ['Location Address and Hours Settings'],
					'canAddNew' => true,
					'canDelete' => true,
				],
			],
		];

		$structure['recordsToInclude'] = [
			'property' => 'recordsToInclude',
			'type' => 'oneToMany',
			'label' => 'Records To Include',
			'renderAsHeading' => true,
			'description' => 'Information about what records to include in this scope',
			'keyThis' => 'locationId',
			'keyOther' => 'locationId',
			'subObjectType' => 'LocationRecordToInclude',
			'structure' => $locationRecordToIncludeStructure,
			'sortable' => true,
			'storeDb' => true,
			'allowEdit' => false,
			'canEdit' => false,
			'forcesReindex' => true,
			'permissions' => ['Location Records included in Catalog'],
			'canAddNew' => true,
			'canDelete' => true,
		];
		$structure['includeLibraryRecordsToInclude'] = [
			'property' => 'includeLibraryRecordsToInclude',
			'type' => 'checkbox',
			'label' => 'Include Library Records To Include',
			'description' => 'Whether or not the records to include from the parent library should be included for this location',
			'hideInLists' => true,
			'default' => true,
			'forcesReindex' => true,
			'permissions' => ['Location Records included in Catalog'],
		];

		if (array_key_exists('Side Loads', $enabledModules)) {
			$locationSideLoadScopeStructure = LocationSideLoadScope::getObjectStructure($context);
			unset($locationSideLoadScopeStructure['locationId']);
			$structure['sideLoadScopes'] = [
				'property' => 'sideLoadScopes',
				'type' => 'oneToMany',
				'label' => 'Side Loaded Content Scopes',
				'renderAsHeading' => true,
				'description' => 'Information about what Side Loads to include in this scope',
				'keyThis' => 'libraryId',
				'keyOther' => 'libraryId',
				'subObjectType' => 'LocationSideLoadScope',
				'structure' => $locationSideLoadScopeStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => true,
				'forcesReindex' => true,
				'permissions' => ['Location Records included in Catalog'],
				'canAddNew' => true,
				'canDelete' => true,
			];
		}

		if (array_key_exists('Aspen LiDA', $enabledModules)) {
			require_once ROOT_DIR . '/sys/AspenLiDA/LocationSetting.php';
			$appLocationSetting = new LocationSetting();
			$appLocationSetting->orderBy('name');
			$appLocationSettings = [];
			$appLocationSetting->find();
			$appLocationSettings[-2] = 'None';
			while ($appLocationSetting->fetch()) {
				$appLocationSettings[$appLocationSetting->id] = $appLocationSetting->name;
			}

			require_once ROOT_DIR . '/sys/AspenLiDA/SelfCheckSetting.php';
			$appSelfCheckSetting = new AspenLiDASelfCheckSetting();
			$appSelfCheckSetting->orderBy('name');
			$appSelfCheckSettings = [];
			$appSelfCheckSetting->find();
			$appSelfCheckSettings[-1] = 'none';
			while ($appSelfCheckSetting->fetch()) {
				$appSelfCheckSettings[$appSelfCheckSetting->id] = $appSelfCheckSetting->name;
			}

			$structure['aspenLiDASection'] = [
				'property' => 'aspenLiDASection',
				'type' => 'section',
				'label' => 'Aspen LiDA',
				'hideInLists' => true,
				'renderAsHeading' => true,
				'permissions' => ['Administer Aspen LiDA Settings'],
				'properties' => [
					'lidaLocationSettingId' => [
						'property' => 'lidaLocationSettingId',
						'type' => 'enum',
						'values' => $appLocationSettings,
						'label' => 'Location Settings',
						'description' => 'The location settings to use for Aspen LiDA',
						'hideInLists' => true,
						'default' => -1,
					],
					'lidaSelfCheckSettingId' => [
						'property' => 'lidaSelfCheckSettingId',
						'type' => 'enum',
						'values' => $appSelfCheckSettings,
						'label' => 'Self-Check Settings',
						'description' => 'The self-check settings to use for Aspen LiDA',
						'hideInLists' => true,
						'default' => -1,
					]
				]
			];
		}

		$structure['sublocationsSection'] = [
			'property' => 'sublocationsSection',
			'type' => 'section',
			'label' => 'Sublocations',
			'hideInLists' => true,
			'renderAsHeading' => true,
			'properties' => [
				'sublocations' => [
					'property' => 'sublocations',
					'type' => 'oneToMany',
					'label' => "Sublocation Settings",
					'description' => "Additional Settings information for sublocations assigned to this location",
					'keyThis' => 'locationId',
					'keyOther' => 'locationId',
					'subObjectType' => 'subLocation',
					'structure' => $sublocationSettingsStructure,
					'sortable' => true,
					'storeDb' => true,
					'allowEdit' => true,
					'canEdit' => true,
					'canAddNew' => true,
					'canDelete' => true,
				],
			]
		];

		if (!UserAccount::userHasPermission('Administer All Libraries')) {
			unset($structure['isMainBranch']);
		}
		if (!$hasScoping) {
			unset($structure['ilsSection']['properties']['scope']);
			unset($structure['ilsSection']['properties']['useScope']);
		}
		if (!$isKohaActive) {
			unset($structure['curbsidePickupSettings']);
		}

		if (!$vdxActive) {
			unset($structure['interLibraryLoanSection']['properties']['vdxFormId']);
			unset($structure['interLibraryLoanSection']['properties']['vdxLocation']);
		}
		return $structure;
	}

	private $_pickupUsers;

	// Used to track multiple linked users having the same pick-up locations

	public function getPickupUsers(): array {
		if ($this->_pickupUsers == null) {
			$this->_pickupUsers = [];
		}
		return $this->_pickupUsers;
	}

	public function setPickupUsers($pickupUsers): void {
		$this->_pickupUsers = $pickupUsers;
	}

	public function addPickupUser(string $userId): void {
		if ($this->_pickupUsers == null) {
			$this->_pickupUsers = [];
		}
		$this->_pickupUsers[] = $userId;
	}

	/**
	 * @param User|bool|null $patronProfile
	 * @param bool $isLinkedUser
	 * @return Location[]
	 */
	function getPickupBranches(User|bool|null $patronProfile, bool $isLinkedUser = false): array {
		// Note: Some calls to this function will set $patronProfile to false. (No Patron is logged in)
		// For example, MaterialsRequest_NewRequest
		$homeLibraryInList = false;
		$alternateLibraryInList = false;

		//Get the library for the patron's home branch.
		global $librarySingleton;
		if ($patronProfile) {
			/** @var Library $homeLibrary */
			$homeLibrary = $librarySingleton->getLibraryForLocation($patronProfile->homeLocationId);
		} else {
			$homeLibrary = null;
		}

		//Set up our query to get the correct locations from the location table.
		if (isset($homeLibrary) && $homeLibrary->inSystemPickupsOnly == 1) {
			/** The user can only pick up within their home system */
			if (strlen($homeLibrary->validPickupSystems) > 0) {
				/** The system has additional related systems that you can pick up materials from */
				$pickupIds = [];
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
				$this->whereAdd("libraryId IN (" . implode(',', $pickupIds) . ")");
				//TODO: Do we need to limit based on validHoldPickupBranch
			} else {
				/** Only this system is valid */
				$this->whereAdd("libraryId = $homeLibrary->libraryId");
				$this->whereAdd("validHoldPickupBranch = 1 OR validHoldPickupBranch = 3");
			}
		} else {
			//The user can pick up at any system
			$this->whereAdd("validHoldPickupBranch = 1");
			if ($homeLibrary !== null) {
				$this->whereAdd("validHoldPickupBranch = 3 AND libraryId = $homeLibrary->libraryId", 'OR');
			}
		}

		$this->orderBy('displayName');

		/** @var Location[] $tmpLocations */
		$tmpLocations = $this->fetchAll();

		//Load the locations and sort them based on the user profile information as well as their physical location.
		$physicalLocation = $this->getPhysicalLocation();
		$locationList = [];
		foreach ($tmpLocations as $tmpLocation) {
			// Add the user id to each pickup location to track multiple linked accounts having the same pick-up location.
			if (!empty($patronProfile)) {
				$tmpLocation->addPickupUser($patronProfile->id);
			}
			if (($tmpLocation->validHoldPickupBranch == 1) || ($tmpLocation->validHoldPickupBranch == 0 && !empty($patronProfile) && $patronProfile->homeLocationId == $tmpLocation->locationId) || ($tmpLocation->validHoldPickupBranch == 3 && !empty($patronProfile) && $patronProfile->getHomeLibrary()->libraryId == $tmpLocation->libraryId)) {
				// Each location is prepended with a number to keep precedence for given locations when sorted below
				if (isset($physicalLocation) && $physicalLocation->locationId == $tmpLocation->locationId) {
					//If the user is in a branch, those holdings come first.
					$locationList['1' . $tmpLocation->displayName] = $tmpLocation;
				} elseif (!empty($patronProfile) && $tmpLocation->locationId == $patronProfile->pickupLocationId) {
					//Next comes the user's preferred pickup branch if the user is logged in.
					$locationList['21' . $tmpLocation->displayName] = $tmpLocation;
				} elseif (!empty($patronProfile) && $tmpLocation->locationId == $patronProfile->homeLocationId) {
					//Next comes the user's home branch if the user is logged in or has the home_branch cookie set.
					$locationList['22' . $tmpLocation->displayName] = $tmpLocation;
					$homeLibraryInList = true;
				} elseif (isset($patronProfile->myLocation1Id) && $tmpLocation->locationId == $patronProfile->myLocation1Id) {
					//Next come nearby locations for the user
					$locationList['3' . $tmpLocation->displayName] = $tmpLocation;
					$alternateLibraryInList = true;
				} elseif (isset($patronProfile->myLocation2Id) && $tmpLocation->locationId == $patronProfile->myLocation2Id) {
					//Next come nearby locations for the user
					$locationList['4' . $tmpLocation->displayName] = $tmpLocation;
				} elseif (isset($homeLibrary) && $tmpLocation->libraryId == $homeLibrary->libraryId) {
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
					$homeLocation->addPickupUser($patronProfile->id); // Add the user id to each pickup location to track multiple linked accounts having the same pick-up location.
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

	/**
	 * Get a list of sublocations where a user can pick up from
	 * @param ?User $patron The patron to filter valid sublocations by
	 * @return Sublocation[]
	 */
	function getPickupSublocations(?User $patron = null): array {
		require_once ROOT_DIR . '/sys/LibraryLocation/Sublocation.php';
		$sublocations = [];
		$object = new Sublocation();
		$object->locationId = $this->locationId;
		$object->isValidHoldPickupAreaILS = 1;
		$object->isValidHoldPickupAreaAspen = 1;
		$object->orderBy('weight');
		$object->find();

		if ($patron != null) {
			$patronPType = $patron->getPTypeObj();
		}else{
			$patronPType = null;
		}
	
		while ($object->fetch()) {
			$isValid = true;
			if ($patron != null && $patronPType != null) {
				require_once ROOT_DIR . '/sys/LibraryLocation/SublocationPatronType.php';
				$sublocationPType = new SublocationPatronType();
				$sublocationPType->patronTypeId = $patronPType->id;
				$sublocationPType->sublocationId = $object->id;
				if ($sublocationPType->find(true)) {
					$isValid = true;
				}
			}
			if ($isValid) {
				$sublocations[$object->id] = clone($object);
			}
		}

		return $sublocations;
	}

	/**
	 * Get a list of sublocations where a user can pick up from
	 * @param ?int $locationId The location to get sublocations for - don't limit by location if null
	 * @return Sublocation[]
	 */
	static function getEventSublocations($locationId): array {
		require_once ROOT_DIR . '/sys/LibraryLocation/Sublocation.php';
		$sublocations = [];
		$object = new Sublocation();
		if ($locationId != null) {
			$object->locationId = $locationId;
		}
		$object->isValidEventLocation = 1;
		$object->orderBy('weight');
		$object->find();

		while ($object->fetch()) {
			$sublocations[$object->id] = $object->name;
		}

		return $sublocations;
	}

	/** @var string|Location|null */
	private static $activeLocation = 'unset';

	/**
	 * Returns the active location to use when doing search scoping, etc.
	 * This does not include the IP address
	 *
	 * @return Location|null
	 */
	function getActiveLocation(): ?Location {
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
					//Check to see if we can get the active location based off the subdomain
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
					} else {
						//Check to see if we can get the active location based off the location code
						$activeLocation = new Location();
						$activeLocation->code = $locationCode;
						if ($activeLocation->find(true) && empty($activeLocation->subdomain)) {
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

	static $_defaultLocationForUser = null;

	/**
	 * @return Location|null
	 */
	static function getDefaultLocationForUser(): ?Location {
		if (Location::$_defaultLocationForUser == null) {
			//Check to see if we have an active user
			if (UserAccount::isLoggedIn()) {
				$homeLocationId = UserAccount::getUserHomeLocationId();
				if ($homeLocationId > 0) {
					$location = new Location();
					$location->locationId = $homeLocationId;
					if ($location->find(true)) {
						Location::$_defaultLocationForUser = $location;
					}
				}
			}
			if (Location::$_defaultLocationForUser == null) {
				global $locationSingleton;
				$activeLocation = $locationSingleton->getActiveLocation();
				if ($activeLocation != null) {
					Location::$_defaultLocationForUser = $activeLocation;
				} else {
					//get the main location for the library or if there isn't one, get the first
					global $library;
					$location = new Location();
					$location->libraryId = $library->libraryId;
					$location->orderBy('isMainBranch desc'); // gets the main branch first or the first location
					if ($location->find(true)) {
						Location::$_defaultLocationForUser = $location;
					} else {
						//Get the first location
						$location = new Location();
						if ($location->find(true)) {
							Location::$_defaultLocationForUser = $location;
						} else {
							//There isn't anything to tie it to, leave it null
						}
					}
				}
			}
		}
		return Location::$_defaultLocationForUser;
	}

	function setActiveLocation($location): void {
		Location::$activeLocation = $location;
	}

	/**
	 * @var string|Location
	 */
	private static $userHomeLocation = 'unset';

	/**
	 * Get the home location for the currently logged-in user.
	 *
	 * @return Location|null
	 */
	static function getUserHomeLocation(): ?Location {
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

	function getBranchLocationCode() {
		if (isset($this->branchLocationCode) && $this->branchLocationCode != 'unset') {
			return $this->branchLocationCode;
		}
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

	function getPhysicalLocation(): Location|null|string {
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

	static $searchLocation = [];

	/**
	 * @param null $searchSource
	 * @return Location|null
	 */
	static function getSearchLocation($searchSource = null): ?Location {
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
			} elseif ($scopingSetting == 'local' || $scopingSetting == 'econtent' || $scopingSetting == 'location' || $scopingSetting == 'websites' || $scopingSetting == 'lists' || $scopingSetting == 'series') {
				global $locationSingleton;
				Location::$searchLocation[$searchSource] = $locationSingleton->getActiveLocation();
			} elseif ($scopingSetting == 'marmot' || $scopingSetting == 'unscoped') {
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
	 * @return Location|null
	 */
	function getIPLocation(): ?Location {
		if ($this->_ipLocation != 'unset') {
			return $this->_ipLocation;
		}
		global $timer;
		//Check the current IP address to see if we are in a branch
		$activeIp = IPAddress::getActiveIp();

		$timer->logTime('Starting getIPLocation');
		//echo("Active IP is $activeIp");
		require_once ROOT_DIR . '/sys/IP/IPAddress.php';
		$this->_ipLocation = null;
		$subnet = IPAddress::getIPAddressForIP($activeIp);
		if ($subnet !== false) {
			$matchedLocation = new Location();
			$matchedLocation->locationId = $subnet->locationid;
			if ($matchedLocation->find(true)) {
				//Only use the physical location regardless of where we are
				$this->_ipLocation = clone($matchedLocation);
			}
		}

		$timer->logTime('Finished getIPLocation');

		return $this->_ipLocation;
	}


	private $sublocationCode = 'unset';

	function getSublocationCode() {
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

	/**
	 * @param $libraryId
	 * @return string[]
	 */
	function getLocationsFacetsForLibrary($libraryId): array {
		$location = new Location();
		$location->libraryId = $libraryId;
		$location->find();
		$facets = [];
		if ($location->getNumResults() > 0) {
			while ($location->fetch()) {
				if (empty($location->facetLabel)) {
					$facets[] = $location->displayName;
				} else {
					$facets[] = $location->facetLabel;
				}
			}
		}
		return $facets;
	}


	public function __get($name) {
		if ($name == "hours") {
			return $this->getHours();
		} elseif ($name == "moreDetailsOptions") {
			return $this->getMoreDetailsOptions();
		} elseif ($name == 'recordsToInclude') {
			return $this->getRecordsToInclude();
		} elseif ($name == 'sideLoadScopes') {
			return $this->getSideLoadScopes();
		} elseif ($name == 'overDriveScopes') {
			return $this->getLocationOverDriveScopes();
		} elseif ($name == 'combinedResultSections') {
			return $this->getCombinedResultSections();
		} elseif ($name == 'cloudLibraryScope') {
			return $this->getCloudLibraryScope();
		} elseif ($name == 'themes') {
			return $this->getThemes();
		} elseif ($name == 'sublocations') {
			return $this->getSublocations();
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == "hours") {
			$this->_hours = $value;
		} elseif ($name == "moreDetailsOptions") {
			$this->_moreDetailsOptions = $value;
		} elseif ($name == 'recordsToInclude') {
			$this->_recordsToInclude = $value;
		} elseif ($name == 'sideLoadScopes') {
			$this->_sideLoadScopes = $value;
		} elseif ($name == 'overDriveScopes') {
			$this->_locationOverDriveScopes = $value;
		} elseif ($name == 'combinedResultSections') {
			$this->_combinedResultSections = $value;
		} elseif ($name == 'cloudLibraryScope') {
			$this->_cloudLibraryScope = $value;
		} elseif ($name == 'themes') {
			$this->_themes = $value;
		} elseif ($name == 'sublocations') {
			$this->_sublocations = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	/**
	 * Override the update functionality to save the hours
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update($context = '') {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveHours();
			$this->saveMoreDetailsOptions();
			$this->saveRecordsToInclude();
			$this->saveSideLoadScopes();
			$this->saveOverDriveScopes();
			$this->saveCombinedResultSections();
			$this->saveCloudLibraryScopes();
			$this->saveCoordinates();
			$this->saveThemes();
			$this->saveEventMapping();
			$this->saveSublocations();
		}
		return $ret;
	}

	/**
	 * Override the update functionality to save the hours
	 *
	 * @see DB/DB_DataObject::insert()
	 */
	public function insert($context = '') {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveHours();
			$this->saveMoreDetailsOptions();
			$this->saveRecordsToInclude();
			$this->saveSideLoadScopes();
			$this->saveOverDriveScopes();
			$this->saveCombinedResultSections();
			$this->saveCloudLibraryScopes();
			$this->saveCoordinates();
			$this->saveThemes();
			$this->saveEventMapping();
			$this->saveSublocations();
		}
		return $ret;
	}

	public function delete($useWhere = false): int {
		$ret = parent::delete($useWhere);
		if ($ret && !empty($this->id)) {
			$locationMap = new EventsBranchMapping();
			$locationMap->locationId = $this->locationId;
			$locationMap->delete(true);
		}
		return $ret;
	}

	public function getMoreDetailsOptions(): array {
		if (!isset($this->_moreDetailsOptions)) {
			$this->_moreDetailsOptions = [];
			if (!empty($this->locationId)) {
				$moreDetailsOptions = new LocationMoreDetails();
				$moreDetailsOptions->locationId = $this->locationId;
				$moreDetailsOptions->orderBy('weight');
				$moreDetailsOptions->find();
				while ($moreDetailsOptions->fetch()) {
					$this->_moreDetailsOptions[$moreDetailsOptions->id] = clone($moreDetailsOptions);
				}
			}
		}
		return $this->_moreDetailsOptions;
	}

	public function saveMoreDetailsOptions(): void {
		if (isset ($this->_moreDetailsOptions) && is_array($this->_moreDetailsOptions)) {
			$this->saveOneToManyOptions($this->_moreDetailsOptions, 'locationId');
			unset($this->_moreDetailsOptions);
		}
	}

	/**
	 * @return LocationCombinedResultSection[]
	 */
	public function getCombinedResultSections(): array {
		if (!isset($this->_combinedResultSections)) {
			$this->_combinedResultSections = [];
			if (!empty($this->locationId)) {
				$combinedResultSection = new LocationCombinedResultSection();
				$combinedResultSection->locationId = $this->locationId;
				$combinedResultSection->orderBy('weight');
				if ($combinedResultSection->find()) {
					while ($combinedResultSection->fetch()) {
						$this->_combinedResultSections[$combinedResultSection->id] = clone $combinedResultSection;
					}
				}
			}
		}
		return $this->_combinedResultSections;
	}

	public function saveCombinedResultSections(): void {
		if (isset ($this->_combinedResultSections) && is_array($this->_combinedResultSections)) {
			$this->saveOneToManyOptions($this->_combinedResultSections, 'locationId');
			unset($this->_combinedResultSections);
		}
	}

	public function saveHours(): void {
		if (isset ($this->_hours) && is_array($this->_hours)) {
			$this->saveOneToManyOptions($this->_hours, 'locationId');
			unset($this->_hours);
		}
	}

	public function getCloudLibraryScope(): null|string|int {
		if ($this->_cloudLibraryScope == null && $this->locationId) {
			require_once ROOT_DIR . '/sys/CloudLibrary/LocationCloudLibraryScope.php';
			$locationCloudLibraryScope = new LocationCloudLibraryScope();
			$locationCloudLibraryScope->locationId = $this->locationId;
			if ($locationCloudLibraryScope->find(true)) {
				require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibraryScope.php';
				$cloudLibraryScope = new CloudLibraryScope();
				$cloudLibraryScope->id = $locationCloudLibraryScope->scopeId;
				if ($cloudLibraryScope->find(true)) {
					$this->_cloudLibraryScope = $cloudLibraryScope->id;
				}
			}
		}
		return $this->_cloudLibraryScope;
	}

	public function saveCloudLibraryScopes(): void {
		if (isset ($this->_cloudLibraryScope)) {
			$locationCloudLibraryScope = new LocationCloudLibraryScope();
			$locationCloudLibraryScope->locationId = $this->locationId;
			$locationCloudLibraryScope->find(true);

			$obj = $this->_cloudLibraryScope;
			/** @var DataObject $obj */
			if ($obj->_deleteOnSave) {
				if ($obj->getPrimaryKeyValue() > 0) {
					$obj->delete();
				}
			} else {
				if ($locationCloudLibraryScope->scopeId != $this->_cloudLibraryScope) {
					$locationCloudLibraryScope->scopeId = $this->_cloudLibraryScope;
					$locationCloudLibraryScope->update();
				}
			}

			// cleanup other scopes location is assigned to
			$unassignedLocationCloudLibraryScope = new LocationCloudLibraryScope();
			$unassignedLocationCloudLibraryScope->locationId = $this->locationId;
			$unassignedLocationCloudLibraryScope->find();
			while ($unassignedLocationCloudLibraryScope->fetch()) {
				if ($unassignedLocationCloudLibraryScope->scopeId != $this->_cloudLibraryScope) {
					$unassignedLocationCloudLibraryScope->delete();
				}
			}

			unset($this->_cloudLibraryScope);
		}
	}

	public static function getLibraryHours($locationId, $timeToCheck): ?array {
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
					'closureReason' => $holiday->name,
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
					$openHours[$ctr] = [
						'open' => ltrim($hours->open, '0'),
						'close' => ltrim($hours->close, '0'),
						'closed' => (bool)$hours->closed,
						'openFormatted' => ($hours->open == '12:00' ? 'Noon' : date("g:i A", strtotime($hours->open))),
						'closeFormatted' => ($hours->close == '12:00' ? 'Noon' : date("g:i A", strtotime($hours->close))),
					];
					if (($openHours[$ctr]['open'] == $openHours[$ctr]['close'])) {
						$openHours[$ctr]['closed'] = true;
					}
					if ($openHours[$ctr]['closed'] === false) {
						$allClosed = false;
					}
					$ctr++;
				}
				if ($allClosed) {
					return [
						'closed' => true,
					];
				}
				return $openHours;
			}
		}

		// no hours found
		return null;
	}

	public static function getLibraryHoursMessage(int $locationId, bool $simpleOutput = false): string {
		$today = time();
		$location = new Location();
		$location->locationId = $locationId;
		if ($location->find(true)) {
			$todaysLibraryHours = Location::getLibraryHours($locationId, $today);
			if (isset($todaysLibraryHours) && is_array($todaysLibraryHours)) {
				if (isset($todaysLibraryHours['closed']) && ($todaysLibraryHours['closed'] === true || $todaysLibraryHours['closed'] == 1)) {
					if (isset($todaysLibraryHours['closureReason'])) {
						$closureReason = $todaysLibraryHours['closureReason'];
					}
					//The library is closed now
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
							if ($simpleOutput) {
								$libraryHoursMessage = translate([
									'text' => 'Closed today for %1%',
									1 => $closureReason,
									'isPublicFacing' => true,
								]);
							} else {
								$libraryHoursMessage = translate([
									'text' => '%1% is closed today for %2%.',
									1 => htmlentities($location->displayName),
									2 => $closureReason,
									'isPublicFacing' => true
								]);
							}
						} else {
							if ($simpleOutput) {
								$libraryHoursMessage = translate([
									'text' => 'Closed today',
									'isPublicFacing' => true
								]);
							} else {
								$libraryHoursMessage = translate([
									'text' => '%1% is closed today.',
									1 => htmlentities($location->displayName),
									'isPublicFacing' => true
								]);
							}
						}
					} else {
						$openMessage = Location::getOpenHoursMessage($nextDayHours);
						if (isset($closureReason)) {
							$libraryHoursMessage = translate([
								'text' => "%1% is closed today for %2%. It will reopen on %3% from %4%",
								1 => htmlentities($location->displayName),
								2 => $closureReason,
								3 => $nextDayOfWeek,
								4 => $openMessage,
								'isPublicFacing' => true,
							]);
						} else {
							$libraryHoursMessage = translate([
								'text' => "%1% is closed today. It will reopen on %2% from %3%",
								1 => htmlentities($location->displayName),
								2 => $nextDayOfWeek,
								3 => $openMessage,
								'isPublicFacing' => true,
							]);
						}

						if ($simpleOutput) {
							$libraryHoursMessage = translate([
								'text' => 'Closed today',
								'isPublicFacing' => true,
							]);
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
						if ($simpleOutput) {
							$libraryHoursMessage = translate([
								'text' => 'Open until %1%',
								1 => Location::getOpenHoursMessage($todaysLibraryHours, true),
								'isPublicFacing' => true
							]);
						} else {
							$libraryHoursMessage = translate([
								'text' => '%1% will be open today from %2%',
								1 => htmlentities($location->displayName),
								2 => Location::getOpenHoursMessage($todaysLibraryHours),
								'isPublicFacing' => true
							]);
						}
					} elseif ($currentHour > $closeHour) {
						$tomorrowsLibraryHours = Location::getLibraryHours($locationId, time() + (24 * 60 * 60));
						if (isset($tomorrowsLibraryHours['closed']) && ($tomorrowsLibraryHours['closed'] === true || $tomorrowsLibraryHours['closed'] == 1)) {
							if (isset($tomorrowsLibraryHours['closureReason'])) {
								$libraryHoursMessage = translate([
									'text' => "%1% will be closed tomorrow for %2%",
									1 => htmlentities($location->displayName),
									2 => $tomorrowsLibraryHours['closureReason'],
									'isPublicFacing' => true,
								]);
							} else {
								$libraryHoursMessage = translate([
									'text' => "%1% will be closed tomorrow",
									1 => htmlentities($location->displayName),
									'isPublicFacing' => true,
								]);
							}

							if ($simpleOutput) {
								$libraryHoursMessage = translate([
									'text' => 'Closed tomorrow',
									1 => htmlentities($location->displayName),
									'isPublicFacing' => true,
								]);
							}

						} else {
							if ($simpleOutput) {
								$libraryHoursMessage = translate([
									'text' => 'Closed until tomorrow %2%',
									1 => htmlentities($location->displayName),
									2 => Location::getOpenHoursMessage($tomorrowsLibraryHours, true, true),
									'isPublicFacing' => true,
								]);
							} else {
								$libraryHoursMessage = translate([
									'text' => '%1% will be open tomorrow from %2%',
									1 => htmlentities($location->displayName),
									2 => Location::getOpenHoursMessage($tomorrowsLibraryHours),
									'isPublicFacing' => true,
								]);
							}
						}
					} else {
						if ($simpleOutput) {
							$libraryHoursMessage = translate([
								'text' => 'Open until %1%',
								1 => Location::getOpenHoursMessage($todaysLibraryHours, true),
								'isPublicFacing' => true
							]);
						} else {
							$libraryHoursMessage = translate([
								'text' => '%1% is open today from %2%',
								1 => htmlentities($location->displayName),
								2 => Location::getOpenHoursMessage($todaysLibraryHours),
								'isPublicFacing' => true
							]);
						}
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

	public static function getOpenHoursMessage($hours, $simpleOutput = false, $openTomorrow = false): string {
		$formattedMessage = '';
		if (empty($hours)) {
			return $formattedMessage;
		}
		for ($i = 0; $i < sizeof($hours); $i++) {
			if (strlen($formattedMessage) != 0 && (sizeof($hours) > 2)) {
				$formattedMessage .= ', ';
			}
			if (($i == (sizeof($hours) - 1)) && count($hours) > 1) {
				$formattedMessage .= translate([
					'text' => ' and ',
					'isPublicFacing' => true,
				]);
			}
			if ($simpleOutput) {
				if (!$openTomorrow) {
					$formattedMessage .= translate([
						'text' => '%1%',
						1 => $hours[$i]['closeFormatted'],
						'isPublicFacing' => true,
					]);
				} else {
					$formattedMessage .= translate([
						'text' => '%1%',
						1 => $hours[$i]['openFormatted'],
						'isPublicFacing' => true,
					]);
				}
			} else {
				$formattedMessage .= translate([
					'text' => '%1% to %2%',
					1 => $hours[$i]['openFormatted'],
					2 => $hours[$i]['closeFormatted'],
					'isPublicFacing' => true,
				]);
			}
		}
		return $formattedMessage;
	}

	public function getRecordsToInclude() {
		if (!isset($this->_recordsToInclude)) {
			$this->_recordsToInclude = [];
			if (!empty($this->locationId)) {
				$object = new LocationRecordToInclude();
				$object->locationId = $this->locationId;
				$object->orderBy('weight');
				$object->find();
				while ($object->fetch()) {
					$this->_recordsToInclude[$object->id] = clone($object);
				}
			}
		}
		return $this->_recordsToInclude;
	}

	public function saveRecordsToInclude() {
		if (isset ($this->_recordsToInclude) && is_array($this->_recordsToInclude)) {
			$this->saveOneToManyOptions($this->_recordsToInclude, 'locationId');
			unset($this->_recordsToInclude);
		}
	}

	/**
	 * @return LocationSideLoadScope[]
	 */
	public function getSideLoadScopes(): array {
		if (!isset($this->_sideLoadScopes)) {
			$this->_sideLoadScopes = [];
			if (!empty($this->locationId)) {
				$object = new LocationSideLoadScope();
				$object->locationId = $this->locationId;
				$object->find();
				while ($object->fetch()) {
					$this->_sideLoadScopes[$object->id] = clone($object);
				}
			}
		}
		return $this->_sideLoadScopes;
	}

	public function saveSideLoadScopes() {
		if (isset ($this->_sideLoadScopes) && is_array($this->_sideLoadScopes)) {
			$this->saveOneToManyOptions($this->_sideLoadScopes, 'locationId');
			unset($this->_sideLoadScopes);
		}
	}

	/** @var OverDriveScope[] */
	private $_overdriveScopes = null;

	/**
	 * @return OverDriveScope[]
	 */
	public function getOverdriveScopeObjects(): array {
		if ($this->_overdriveScopes == null) {
			$this->_overdriveScopes = [];
			if ($this->locationId > 0) {
				$locationOverDriveScopes = $this->getLocationOverdriveScopes();

				require_once ROOT_DIR . '/sys/OverDrive/OverDriveScope.php';
				$overdriveScope = new OverDriveScope();
				$overdriveScope->whereAddIn('id', array_keys($locationOverDriveScopes), false);
				$this->_overdriveScopes = $overdriveScope->fetchAll(null, null, false, true);
			}
		}
		return $this->_overdriveScopes;
	}

	/** @var LocationOverDriveScope[] */
	private $_locationOverDriveScopes = null;

	/**
	 * @return LocationOverDriveScope[]
	 */
	public function getLocationOverdriveScopes(): array {
		if ($this->_locationOverDriveScopes == null) {
			$this->_locationOverDriveScopes = [];
			if ($this->locationId > 0) {
				require_once ROOT_DIR . '/sys/OverDrive/LocationOverDriveScope.php';
				$locationOverDriveScope = new LocationOverDriveScope();
				$locationOverDriveScope->locationId = $this->locationId;
				$this->_locationOverDriveScopes = $locationOverDriveScope->fetchAll(null, null, false, true);
			}
		}
		return $this->_locationOverDriveScopes;
	}

	public function saveOverDriveScopes(): void {
		if (isset ($this->_locationOverDriveScopes) && is_array($this->_locationOverDriveScopes)) {
			$this->saveOneToManyOptions($this->_locationOverDriveScopes, 'locationId');
			unset($this->_libraryOverDriveScopes);
		}
	}

	public function saveCoordinates() {
		if ($this->address && empty($this->latitude) && empty($this->longitude)) {
			$address = str_replace("\r\n", ",", $this->address);
			$address = str_replace(" ", "+", $address);
			$address = str_replace("#", "", $address);

			require_once ROOT_DIR . '/sys/Enrichment/GoogleApiSetting.php';
			$googleSettings = new GoogleApiSetting();
			if ($googleSettings->find(true)) {
				if (!empty($googleSettings->googleMapsKey)) {
					$apiKey = $googleSettings->googleMapsKey;
					$url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . $address . '&key=' . $apiKey;

					// fetch google geocode data
					$curl = new CurlWrapper();
					$response = $curl->curlGetPage($url);
					$data = json_decode($response);
					$curl->close_curl();

					if ($data->status == 'OK') {
						$this->setProperty('longitude', $data->results[0]->geometry->location->lng, null);
						$this->setProperty('latitude', $data->results[0]->geometry->location->lat, null);
						$components = $data->results[0]->address_components;

						$country = '';
						foreach ($components as $component) {
							if ($component->types[0] == 'country') {
								$country = $component->short_name;
							}
						}

						if ($country == 'CA') {
							$this->setProperty('unit', 'Km', null);
						} else {
							$this->setProperty('unit', 'Mi', null);
						}
						parent::update();
					}
				}
			}
		}
	}

	/** @return LocationHours[] */
	function getHours(): array {
		if (!isset($this->_hours)) {
			$this->_hours = [];
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

	public function hasValidHours(): bool {
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
	public function getOpacStatus(): bool {
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
	public function getGroupedWorkDisplaySettings(): GroupedWorkDisplaySetting {
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
			} catch (Exception $e) {
				global $logger;
				$logger->log('Error loading grouped work display settings ' . $e, Logger::LOG_ERROR);
			}
		}
		return $this->_groupedWorkDisplaySettings;
	}

	protected $_openArchivesFacetSettings = null;

	/** @return OpenArchivesFacetGroup */
	public function getOpenArchivesFacetSettings(): OpenArchivesFacetGroup {
		require_once ROOT_DIR . '/sys/OpenArchives/OpenArchivesFacetGroup.php';
		if ($this->_openArchivesFacetSettings == null) {
			try {
				$searchLocation = new Location();
				$searchLocation->locationId = $this->locationId;
				if ($searchLocation->find(true)) {
					if ($this->openArchivesFacetSettingId == -1) {
						$library = Library::getLibraryForLocation($this->locationId);
						$this->openArchivesFacetSettingId = $library->openArchivesFacetSettingId;
					}
					$openArchivesFacetSettings = new OpenArchivesFacetGroup();
					$openArchivesFacetSettings->id = $this->openArchivesFacetSettingId;
					$openArchivesFacetSettings->find(true);
					$this->_openArchivesFacetSettings = clone $openArchivesFacetSettings;
				}
			} catch (Exception $e) {
				global $logger;
				$logger->log('Error loading grouped work display settings ' . $e, Logger::LOG_ERROR);
			}
		}
		return $this->_openArchivesFacetSettings;
	}

	protected $_websiteFacetSettings = null;

	/** @return WebsiteFacetGroup */
	public function getWebsiteFacetSettings(): WebsiteFacetGroup {
		require_once ROOT_DIR . '/sys/WebsiteIndexing/WebsiteFacetGroup.php';
		if ($this->_websiteFacetSettings == null) {
			try {
				$searchLocation = new Location();
				$searchLocation->locationId = $this->locationId;
				if ($searchLocation->find(true)) {
					if ($this->websiteIndexingFacetSettingId == -1) {
						$library = Library::getLibraryForLocation($this->locationId);
						$this->websiteIndexingFacetSettingId = $library->websiteIndexingFacetSettingId;
					}
					$websiteFacetSetting = new WebsiteFacetGroup();
					$websiteFacetSetting->id = $this->websiteIndexingFacetSettingId;
					$websiteFacetSetting->find(true);
					$this->_websiteFacetSettings = clone $websiteFacetSetting;
				}
			} catch (Exception $e) {
				global $logger;
				$logger->log('Error loading grouped work display settings ' . $e, Logger::LOG_ERROR);
			}
		}
		return $this->_websiteFacetSettings;
	}

	function getEditLink($context): string {
		return '/Admin/Locations?objectAction=edit&id=' . $this->libraryId;
	}

	protected $_parentLibrary = null;

	/** @return Library */
	public function getParentLibrary(): ?Library {
		if ($this->_parentLibrary == null) {
			$this->_parentLibrary = new Library();
			$this->_parentLibrary->libraryId = $this->libraryId;
			$this->_parentLibrary->find(true);
		}
		return $this->_parentLibrary;
	}

	public function setGroupedWorkDisplaySettings(GroupedWorkDisplaySetting $newGroupedWorkDisplaySettings) {
		$this->_groupedWorkDisplaySettings = $newGroupedWorkDisplaySettings;
		$this->groupedWorkDisplaySettingId = $newGroupedWorkDisplaySettings->id;
	}

	/**
	 * @param boolean $restrictByHomeLibrary whether only locations for the patron's home library should be returned
	 * @param boolean $valueIsCode whether the value returned is the location code or location id (default)
	 * @return array
	 */
	static function getLocationList(bool $restrictByHomeLibrary, bool $valueIsCode = false): array {
		$location = new Location();
		$location->orderBy('displayName');
		if ($restrictByHomeLibrary) {
			$homeLibrary = Library::getPatronHomeLibrary();
			if ($homeLibrary != null) {
				$location->whereAdd("libraryId = $homeLibrary->libraryId");
			}
			if (UserAccount::isLoggedIn()) {
				$user = UserAccount::getActiveUserObj();
				$additionalAdministrationLocations = $user->getAdditionalAdministrationLocations();
				if (!empty($additionalAdministrationLocations)) {
					$location->whereAddIn('locationId', array_keys($additionalAdministrationLocations), false, 'OR');
				}
			}
		}
		$selectValue = 'locationId';
		if ($valueIsCode) {
			$selectValue = 'code';
		}
		$location->find();
		$locationList = [];
		while ($location->fetch()) {
			$locationList[$location->$selectValue] = $location->displayName;
		}
		return $locationList;
	}

	static $locationListAsObjects = null;

	/**
	 * @param boolean $restrictByHomeLibrary whether locations for the patron's home library should be returned
	 * @return Location[]
	 */
	static function getLocationListAsObjects(bool $restrictByHomeLibrary): array {
		if (Location::$locationListAsObjects == null) {
			$location = new Location();
			$location->orderBy('displayName');
			if ($restrictByHomeLibrary) {
				$homeLibrary = Library::getPatronHomeLibrary();
				if ($homeLibrary != null) {
					$location->whereAdd("libraryId = $homeLibrary->libraryId");
				}
				if (UserAccount::isLoggedIn()) {
					$user = UserAccount::getActiveUserObj();
					$additionalAdministrationLocations = $user->getAdditionalAdministrationLocations();
					if (!empty($additionalAdministrationLocations)) {
						$location->whereAddIn('locationId', array_keys($additionalAdministrationLocations), false, 'OR');
					}
				}
			}
			$location->find();
			Location::$locationListAsObjects = [];
			while ($location->fetch()) {
				Location::$locationListAsObjects[$location->locationId] = clone $location;
			}
		}
		return Location::$locationListAsObjects;
	}

	protected $_browseCategoryGroup = null;

	/**
	 * @return BrowseCategoryGroup|null
	 * @noinspection PhpUnused
	 */
	public function getBrowseCategoryGroup(): ?BrowseCategoryGroup {
		if ($this->_browseCategoryGroup == null) {
			if ($this->browseCategoryGroupId == -1) {
				$this->_browseCategoryGroup = $this->getParentLibrary()->getBrowseCategoryGroup();
			} else {
				require_once ROOT_DIR . '/sys/Browse/BrowseCategoryGroup.php';
				$browseCategoryGroup = new BrowseCategoryGroup();
				$browseCategoryGroup->id = $this->browseCategoryGroupId;
				if ($browseCategoryGroup->find(true)) {
					$this->_browseCategoryGroup = $browseCategoryGroup;
				}
			}
		}
		return $this->_browseCategoryGroup;
	}

	public function getPrimaryTheme() {
		$allThemes = $this->getThemes();
		return reset($allThemes);
	}

	/**
	 * @return LibraryTheme[]|null
	 */
	public function getThemes(): ?array {
		if (!isset($this->_themes) && $this->locationId) {
			$this->_themes = [];
			$locationTheme = new LocationTheme();
			$locationTheme->locationId = $this->locationId;
			$locationTheme->orderBy('weight');
			if ($locationTheme->find()) {
				while ($locationTheme->fetch()) {
					$this->_themes[$locationTheme->id] = clone $locationTheme;
				}
			}
		}
		return $this->_themes;
	}

	public function saveThemes() {
		if (isset ($this->_themes) && is_array($this->_themes)) {
			foreach ($this->_themes as $obj) {
				/** @var DataObject $obj */
				if ($obj->_deleteOnSave) {
					$obj->delete();
				} else {
					if (isset($obj->{$obj->__primaryKey}) && is_numeric($obj->{$obj->__primaryKey})) {
						if ($obj->{$obj->__primaryKey} <= 0) {
							$obj->locationId = $this->{$this->__primaryKey};
							$obj->insert();
						} else {
							if ($obj->hasChanges()) {
								$obj->update();
							}
						}
					} else {
						// set appropriate weight for new theme
						$weight = 0;
						$existingThemesForLocation = new LocationTheme();
						$existingThemesForLocation->locationId = $this->locationId;
						if ($existingThemesForLocation->find()) {
							while ($existingThemesForLocation->fetch()) {
								$weight = $weight + 1;
							}
						}

						$obj->locationId = $this->{$this->__primaryKey};
						$obj->weight = $weight;
						$obj->insert();
					}
				}
			}
			unset($this->_themes);
		}
	}

	public function saveEventMapping() {
		$locationMap = new EventsBranchMapping();
		$locationMap->locationId = $this->locationId;
		if ($locationMap->find(true)) {
			if ($this->displayName != $locationMap->aspenLocation) { //only need to update if the location name changed
				$locationMap->aspenLocation = $this->displayName;
				$locationMap->update();
			}
		} else { //insert new info if it's a new location
			$locationMap->locationId = $this->locationId;
			$locationMap->libraryId = $this->libraryId;
			$locationMap->aspenLocation = $this->displayName;
			$locationMap->eventsLocation = $this->displayName;
			$locationMap->insert();
		}
	}

	public function getApiInfo(): array {
		global $configArray;
		$parentLibrary = $this->getParentLibrary();
		$apiInfo = [
			'locationId' => (int)$this->locationId,
			'isMainBranch' => (bool)$this->isMainBranch,
			'displayName' => $this->displayName,
			'parentLibraryDisplayName' => $parentLibrary->displayName,
			'libraryId' => $this->libraryId,
			'address' => $this->address,
			'latitude' => floatval($this->latitude),
			'longitude' => floatval($this->longitude),
			'phone' => $this->phone,
			'secondaryPhone' => $this->secondaryPhoneNumber,
			'tty' => $this->tty,
			'description' => $this->description,
			'vdxFormId' => (int)$this->vdxFormId,
			'vdxLocation' => $this->vdxLocation,
			'localIllFormId' => (int)$this->localIllFormId,
			'showInLocationsAndHoursList' => (string)$this->showInLocationsAndHoursList,
			'hoursMessage' => Location::getLibraryHoursMessage($this->locationId),
			'hours' => [],
			'code' => $this->code,
			'unit' => $this->unit,
			'solrScope' => $parentLibrary->subdomain,
		];
		if ($this->theme == "-1") {
			$apiInfo['theme'] = $parentLibrary->getPrimaryTheme();
		} else {
			$apiInfo['theme'] = $this->getPrimaryTheme();
		}
		if ((empty($this->homeLink) || $this->homeLink == "default" || $this->homeLink == "/")) {
			if ($parentLibrary == null) {
				$apiInfo['homeLink'] = '';
			} else {
				$apiInfo['homeLink'] = $parentLibrary->homeLink;
			}
		} else {
			$apiInfo['homeLink'] = $this->homeLink;
		}
		if ((empty($this->contactEmail) || $this->contactEmail == null)) {
			if ($parentLibrary == null) {
				$apiInfo['email'] = null;
			} else {
				$apiInfo['email'] = $parentLibrary->contactEmail;
			}
		} else {
			$apiInfo['email'] = $this->contactEmail;
		}
		unset($this->_hours);
		$hours = $this->getHours();
		foreach ($hours as $hour) {
			$apiInfo['hours'][] = [
				'day' => (int)$hour->day,
				'dayName' => LocationHours::$dayNames[$hour->day],
				'isClosed' => (bool)$hour->closed,
				'open' => $hour->open,
				'close' => $hour->close,
				'notes' => $hour->notes,
			];
		}

		$superScopeLabel = $this->getGroupedWorkDisplaySettings()->availabilityToggleLabelSuperScope;
		$localLabel = $this->getGroupedWorkDisplaySettings()->availabilityToggleLabelLocal;
		$localLabel = str_ireplace('{display name}', $this->displayName, $localLabel);
		$availableLabel = $this->getGroupedWorkDisplaySettings()->availabilityToggleLabelAvailable;
		$availableLabel = str_ireplace('{display name}', $this->displayName, $availableLabel);
		$availableOnlineLabel = $this->getGroupedWorkDisplaySettings()->availabilityToggleLabelAvailableOnline;
		$availableOnlineLabel = str_ireplace('{display name}', $this->displayName, $availableOnlineLabel);
		$availabilityToggleValue = $this->getGroupedWorkDisplaySettings()->defaultAvailabilityToggle;
		$facetCountsToShow = $this->getGroupedWorkDisplaySettings()->facetCountsToShow;

		$apiInfo['groupedWorkDisplaySettings']['superScopeLabel'] = $superScopeLabel;
		$apiInfo['groupedWorkDisplaySettings']['localLabel'] = $localLabel;
		$apiInfo['groupedWorkDisplaySettings']['availableLabel'] = $availableLabel;
		$apiInfo['groupedWorkDisplaySettings']['availableOnlineLabel'] = $availableOnlineLabel;
		$apiInfo['groupedWorkDisplaySettings']['availabilityToggleValue'] = $availabilityToggleValue;
		$apiInfo['groupedWorkDisplaySettings']['facetCountsToShow'] = $facetCountsToShow;

		$apiInfo['locationImage'] = null;
		if (isset($this->locationImage)) {
			$apiInfo['locationImage'] = $configArray['Site']['url'] . '/files/original/' . rawurlencode($this->locationImage);
		}

		$baseUrl = $parentLibrary->baseUrl;
		if (empty($baseUrl)) {
			$baseUrl = $configArray['Site']['url'];
		}
		$apiInfo['baseUrl'] = $baseUrl;

		$apiInfo['useAlternateLibraryCardForCloudLibrary'] = 0;
		require_once ROOT_DIR . '/sys/CloudLibrary/LocationCloudLibraryScope.php';
		$locationCloudLibraryScope = new LocationCloudLibraryScope();
		$locationCloudLibraryScope->locationId = $this->locationId;
		if ($locationCloudLibraryScope->find(true)) {
			require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibraryScope.php';
			$cloudLibraryScope = new CloudLibraryScope();
			$cloudLibraryScope->id = $locationCloudLibraryScope->scopeId;
			if ($cloudLibraryScope->find(true)) {
				require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibrarySetting.php';
				$cloudLibrarySetting = new CloudLibrarySetting();
				$cloudLibrarySetting->id = $cloudLibraryScope->settingId;
				if ($cloudLibrarySetting->find(true)) {
					$apiInfo['useAlternateCardForCloudLibrary'] = $cloudLibrarySetting->useAlternateLibraryCard;
				}
			}
		}

		return $apiInfo;
	}

	public function loadCopyableSubObjects() {
		if (empty($_REQUEST['aspenLida'])) {
			$this->lidaLocationSettingId = -1;
			$this->lidaSelfCheckSettingId = -1;
		}
		if (!empty($_REQUEST['combinedResults'])) {
			$this->getCombinedResultSections();
			$index = -1;
			foreach ($this->_combinedResultSections as $subObject) {
				$subObject->id = $index;
				unset($subObject->locationId);
				$index--;
			}
		}
		if (empty($_REQUEST['eContent'])) {
			$this->axis360ScopeId = -1;
			$this->hooplaScopeId = -1;
			$this->overDriveScopeId = -1;
			$this->palaceProjectScopeId = -1;
		} else {
			$this->getCloudLibraryScope();
			$index = -1;
			foreach ($this->_cloudLibraryScopes as $subObject) {
				$subObject->id = $index;
				unset($subObject->locationId);
				$index--;
			}
			$this->getSideLoadScopes();
			$index = -1;
			foreach ($this->_sideLoadScopes as $subObject) {
				$subObject->id = $index;
				unset($subObject->locationId);
				$index--;
			}
			$this->getLocationOverdriveScopes();
			$index = -1;
			foreach ($this->_locationOverDriveScopes as $subObject) {
				$subObject->id = $index;
				unset($subObject->locationId);
				$index--;
			}
		}
		if (!empty($_REQUEST['moreDetails'])) {
			$this->getMoreDetailsOptions();
			$index = -1;
			foreach ($this->_moreDetailsOptions as $subObject) {
				$subObject->id = $index;
				unset($subObject->locationId);
				$index--;
			}
		}
		if (!empty($_REQUEST['hours'])) {
			$this->getHours();
			$index = -1;
			foreach ($this->_hours as $subObject) {
				$subObject->id = $index;
				unset($subObject->locationId);
				$index--;
			}
		}
		if (!empty($_REQUEST['recordsToInclude'])) {
			$this->getRecordsToInclude();
			$index = -1;
			foreach ($this->_recordsToInclude as $subObject) {
				$subObject->id = $index;
				unset($subObject->libraryId);
				$index--;
			}
		}
		if (!empty($_REQUEST['themes'])) {
			$this->getThemes();
			$index = -1;
			foreach ($this->_themes as $subObject) {
				$subObject->id = $index;
				unset($subObject->libraryId);
				$index--;
			}
		}
	}

	function getInterlibraryLoanType(): string {
		try {
			//Check to see if local ILL is available
			$parentLibrary = $this->getParentLibrary();
			if ($parentLibrary != null) {
				if ($parentLibrary->localIllRequestType != 0) {
					if ($this->localIllFormId > 0) {
						return 'localIll';
					}
				}
			}
			//Local ILL is not available, check to see if VDX is available.
			require_once ROOT_DIR . '/sys/VDX/VdxSetting.php';
			require_once ROOT_DIR . '/sys/VDX/VdxForm.php';
			$vdxSettings = new VdxSetting();
			if ($vdxSettings->find(true)) {
				//Get configuration for the form.
				if ($this->vdxFormId != -1) {
					return 'vdx';
				}
			}
		} catch (Exception $e) {
			//This happens if the tables aren't setup, ignore
		}
		return 'none';
	}

	/**
	 * @return Sublocation[]
	 */
	public function getSublocations(): array {
		if (!isset($this->_sublocations)) {
			$this->_sublocations = [];
			if (!empty($this->locationId)) {
				$object = new Sublocation();
				$object->locationId = $this->locationId;
				$object->orderBy('weight');
				$object->find();
				while ($object->fetch()) {
					$this->_sublocations[$object->id] = clone($object);
				}
			}
		}
		return $this->_sublocations;
	}

	public function saveSublocations() {
		if (isset ($this->_sublocations) && is_array($this->_sublocations)) {
			$this->saveOneToManyOptions($this->_sublocations, 'locationId');
			unset($this->_sublocations);
		}
	}
}
