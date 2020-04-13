<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
require_once ROOT_DIR . '/sys/LibraryLocation/Holiday.php';
require_once ROOT_DIR . '/sys/LibraryLocation/LibraryFacetSetting.php';
require_once ROOT_DIR . '/sys/LibraryLocation/LibraryArchiveSearchFacetSetting.php';
require_once ROOT_DIR . '/sys/LibraryLocation/LibraryCombinedResultSection.php';
if (file_exists(ROOT_DIR . '/sys/Indexing/LibraryRecordOwned.php')) {
	require_once ROOT_DIR . '/sys/Indexing/LibraryRecordOwned.php';
}
if (file_exists(ROOT_DIR . '/sys/Indexing/LibraryRecordToInclude.php')) {
	require_once ROOT_DIR . '/sys/Indexing/LibraryRecordToInclude.php';
}
if (file_exists(ROOT_DIR . '/sys/Indexing/LibrarySideLoadScope.php')) {
	require_once ROOT_DIR . '/sys/Indexing/LibrarySideLoadScope.php';
}
if (file_exists(ROOT_DIR . '/sys/Browse/BrowseCategoryGroup.php')) {
	require_once ROOT_DIR . '/sys/Browse/BrowseCategoryGroup.php';
}
if (file_exists(ROOT_DIR . '/sys/LibraryArchiveMoreDetails.php')) {
	require_once ROOT_DIR . '/sys/LibraryArchiveMoreDetails.php';
}
require_once ROOT_DIR . '/sys/LibraryLocation/LibraryLink.php';
require_once ROOT_DIR . '/sys/LibraryLocation/LibraryTopLinks.php';
if (file_exists(ROOT_DIR . '/sys/MaterialsRequestFieldsToDisplay.php')) {
	require_once ROOT_DIR . '/sys/MaterialsRequestFieldsToDisplay.php';
}
if (file_exists(ROOT_DIR . '/sys/MaterialsRequestFormats.php')) {
	require_once ROOT_DIR . '/sys/MaterialsRequestFormats.php';
}
if (file_exists(ROOT_DIR . '/sys/MaterialsRequestFormFields.php')) {
	require_once ROOT_DIR . '/sys/MaterialsRequestFormFields.php';
}

class Library extends DataObject
{
	public $__table = 'library';    // table name
	public $__primaryKey = 'libraryId';
	public $__displayNameColumn = 'displayName';
	//Basic configuration
	public $isDefault;
	public $libraryId; 				//int(11)
	public $subdomain; 				//varchar(15)
	public $baseUrl;

	//Display information specific to the library
	public $displayName; 			//varchar(50)
	public $showDisplayNameInHeader;
	public $headerText;
	public $systemMessage;

	public $generateSitemap;

	//More general display configurations
	public $themeName; 				//varchar(15)
	public $theme;
	public $layoutSettingId;  //Link to LayoutSetting
	public $groupedWorkDisplaySettingId; //Link to GroupedWorkDisplaySettings

	public $browseCategoryGroupId;

	public $restrictSearchByLibrary;

	//For Millennium and Sierra
	public $scope; 					//smallint(6)
	public $useScope;		 		//tinyint(4)

	//Account integration settings
	public $ilsCode;
	public $allowProfileUpdates;   //tinyint(4)
	public $allowFreezeHolds;   //tinyint(4)
	public $showHoldButton;
	public $showHoldButtonInSearchResults;
	public $showHoldButtonForUnavailableOnly;
	public $showLoginButton;
	public $showEmailThis;
	public $showFavorites;
	public $showConvertListsFromClassic;
	public $inSystemPickupsOnly;
	public $validPickupSystems;
	public /** @noinspection PhpUnused */ $pTypes; //This is used as part of the indexing process
	public $defaultPType;
	public $facetLabel;
	public $showAvailableAtAnyLocation;
	public $finePaymentType;
	public $finesToPay;
	public $finePaymentOrder;
	public $payFinesLink;
	public $payFinesLinkText;
	public $minimumFineAmount;
	public $showRefreshAccountButton;    // specifically to refresh account after paying fines online
	public $payPalSandboxMode;
	public $payPalClientId;
	public $payPalClientSecret;

	public /** @noinspection PhpUnused */ $repeatSearchOption;
	public /** @noinspection PhpUnused */ $repeatInOnlineCollection;
	public /** @noinspection PhpUnused */ $repeatInProspector;
	public /** @noinspection PhpUnused */ $repeatInWorldCat;
	public $overDriveScopeId;

	public $hooplaLibraryID;
	public /** @noinspection PhpUnused */ $hooplaScopeId;
	public /** @noinspection PhpUnused */ $rbdigitalScopeId;
	public /** @noinspection PhpUnused */ $cloudLibraryScopeId;
	public /** @noinspection PhpUnused */ $systemsToRepeatIn;
	public $additionalLocationsToShowAvailabilityFor;
	public $homeLink;
	public $showAdvancedSearchbox;
	public $enableProspectorIntegration;
	public /** @noinspection PhpUnused */ $showProspectorResultsAtEndOfSearch;
	public /** @noinspection PhpUnused */ $prospectorCode;
	public /** @noinspection PhpUnused */ $enableGenealogy;
	public $showHoldCancelDate;
	public /** @noinspection PhpUnused */ $enableCourseReserves;
	public $enableSelfRegistration;
	public $selfRegistrationLocationRestrictions;
	public $promptForBirthDateInSelfReg;
	public $showItsHere;
	public $holdDisclaimer;
	public $enableMaterialsRequest;
	public $externalMaterialsRequestUrl;
	public /** @noinspection PhpUnused */ $eContentLinkRules;
	public /** @noinspection PhpUnused */ $includeNovelistEnrichment;
	public /** @noinspection PhpUnused */ $allowAutomaticSearchReplacements;

	public /** @noinspection PhpUnused */ $worldCatUrl;
	public /** @noinspection PhpUnused */ $worldCatQt;
	public /** @noinspection PhpUnused */ $showGoDeeper;
	public $defaultNotNeededAfterDays;

	public /** @noinspection PhpUnused */ $publicListsToInclude;
	public /** @noinspection PhpUnused */ $showWikipediaContent;
	public $eContentSupportAddress;
	public $restrictOwningBranchesAndSystems;
	public $allowPatronAddressUpdates;
	public $showWorkPhoneInProfile;
	public $showNoticeTypeInProfile;
	public $showPickupLocationInProfile;
	public $showAlternateLibraryOptionsInProfile;
	public $additionalCss;
	public $maxRequestsPerYear;
	public $maxOpenRequests;
	// Contact Links //
	public $twitterLink;
	public $facebookLink;
	public $youtubeLink;
	public $instagramLink;
	public $goodreadsLink;
	public $generalContactLink;

	public $allowPinReset;
	public $enableForgotPasswordLink;
	public /** @noinspection PhpUnused */ $preventExpiredCardLogin;
	public /** @noinspection PhpUnused */ $showLibraryHoursNoticeOnAccountPages;
	public $showShareOnExternalSites;
	public /** @noinspection PhpUnused */ $barcodePrefix;
	public /** @noinspection PhpUnused */ $minBarcodeLength;
	public /** @noinspection PhpUnused */ $maxBarcodeLength;
	public $econtentLocationsToInclude;
	public $showExpirationWarnings;
	public /** @noinspection PhpUnused */ $loginFormUsernameLabel;
	public $loginFormPasswordLabel;
	public $showDetailedHoldNoticeInformation;
	public $treatPrintNoticesAsPhoneNotices;
	public /** @noinspection PhpUnused */ $includeDplaResults;

	public /** @noinspection PhpUnused */ $selfRegistrationFormMessage;
	public /** @noinspection PhpUnused */ $selfRegistrationSuccessMessage;
	public /** @noinspection PhpUnused */ $selfRegistrationTemplate;
	public $addSMSIndicatorToPhone;

	public $enableMaterialsBooking;
	public $allowLinkedAccounts;
	public $enableArchive;
	public $archiveNamespace;
	public $archivePid;
	public $allowRequestsForArchiveMaterials;
	public $archiveRequestMaterialsHeader;
	public $claimAuthorshipHeader;
	public $archiveRequestEmail;
	public /** @noinspection PhpUnused */ $hideAllCollectionsFromOtherLibraries;
	public /** @noinspection PhpUnused */ $collectionsToHide;
	public /** @noinspection PhpUnused */ $objectsToHide;
	public /** @noinspection PhpUnused */ $defaultArchiveCollectionBrowseMode;

	public $maxFinesToAllowAccountUpdates;
	public /** @noinspection PhpUnused */ $edsApiProfile;
	public /** @noinspection PhpUnused */ $edsApiUsername;
	public /** @noinspection PhpUnused */ $edsApiPassword;
	public /** @noinspection PhpUnused */ $edsSearchProfile;
	protected $patronNameDisplayStyle; //Needs to be protected so __get and __set are called
	private $_patronNameDisplayStyleChanged = false; //Track changes so we can clear values for existing patrons
//	public /** @noinspection PhpUnused */ $includeAllRecordsInShelvingFacets;
//	public /** @noinspection PhpUnused */ $includeAllRecordsInDateAddedFacets;
	public $alwaysShowSearchResultsMainDetails;
	public /** @noinspection PhpUnused */ $casHost;
	public /** @noinspection PhpUnused */ $casPort;
	public /** @noinspection PhpUnused */ $casContext;
	public /** @noinspection PhpUnused */ $masqueradeAutomaticTimeoutLength;
	public $allowMasqueradeMode;
	public $allowReadingHistoryDisplayInMasqueradeMode;
	public /** @noinspection PhpUnused */ $newMaterialsRequestSummary;  // (Text at the top of the Materials Request Form.)
	public /** @noinspection PhpUnused */ $materialsRequestDaysToPreserve;
	public $showGroupedHoldCopiesCount;
	public $interLibraryLoanName;
	public $interLibraryLoanUrl;
	public $expiredMessage;
	public $expirationNearMessage;
	public $showOnOrderCounts;

	//Combined Results (Bento Box)
	public /** @noinspection PhpUnused */ $enableCombinedResults;
	public /** @noinspection PhpUnused */ $combinedResultsLabel;
	public /** @noinspection PhpUnused */ $defaultToCombinedResults;

	// Archive Request Form Field Settings
	public /** @noinspection PhpUnused */ $archiveRequestFieldName;
	public /** @noinspection PhpUnused */ $archiveRequestFieldAddress;
	public /** @noinspection PhpUnused */ $archiveRequestFieldAddress2;
	public /** @noinspection PhpUnused */ $archiveRequestFieldCity;
	public /** @noinspection PhpUnused */ $archiveRequestFieldState;
	public /** @noinspection PhpUnused */ $archiveRequestFieldZip;
	public /** @noinspection PhpUnused */ $archiveRequestFieldCountry;
	public /** @noinspection PhpUnused */ $archiveRequestFieldPhone;
	public /** @noinspection PhpUnused */ $archiveRequestFieldAlternatePhone;
	public /** @noinspection PhpUnused */ $archiveRequestFieldFormat;
	public /** @noinspection PhpUnused */ $archiveRequestFieldPurpose;

	public /** @noinspection PhpUnused */ $archiveMoreDetailsRelatedObjectsOrEntitiesDisplayMode;

	//OAI
	public $enableOpenArchives;

	static $archiveRequestFormFieldOptions = array('Hidden', 'Optional', 'Required');

	static $archiveMoreDetailsDisplayModeOptions = array(
		'tiled' => 'Tiled',
		'list'  => 'List',
	);

	static $subdomains = null;
	public static function getAllSubdomains()
	{
		if (Library::$subdomains == null){
			$libraries = new Library();
			Library::$subdomains = $libraries->fetchAll('subdomain');
		}
		return Library::$subdomains;
	}

	function keys() {
		return array('libraryId', 'subdomain');
	}

	static function getObjectStructure(){
		// get the structure for the library system's holidays
		$holidaysStructure = Holiday::getObjectStructure();

		// we don't want to make the libraryId property editable
		// because it is associated with this library system only
		unset($holidaysStructure['libraryId']);

		$archiveSearchFacetSettingStructure = LibraryArchiveSearchFacetSetting::getObjectStructure();
		unset($archiveSearchFacetSettingStructure['weight']);
		unset($archiveSearchFacetSettingStructure['libraryId']);
		unset($archiveSearchFacetSettingStructure['numEntriesToShowByDefault']);
		unset($archiveSearchFacetSettingStructure['showAsDropDown']);
		unset($archiveSearchFacetSettingStructure['showAboveResults']);
		unset($archiveSearchFacetSettingStructure['showInAdvancedSearch']);
		//unset($archiveSearchFacetSettingStructure['sortMode']);

		$libraryArchiveMoreDetailsStructure = LibraryArchiveMoreDetails::getObjectStructure();
		unset($libraryArchiveMoreDetailsStructure['weight']);
		unset($libraryArchiveMoreDetailsStructure['libraryId']);

		$libraryLinksStructure = LibraryLink::getObjectStructure();
		unset($libraryLinksStructure['weight']);
		unset($libraryLinksStructure['libraryId']);

		$libraryTopLinksStructure = LibraryTopLinks::getObjectStructure();
		unset($libraryTopLinksStructure['weight']);
		unset($libraryTopLinksStructure['libraryId']);

		$libraryRecordOwnedStructure = LibraryRecordOwned::getObjectStructure();
		unset($libraryRecordOwnedStructure['libraryId']);

		$libraryRecordToIncludeStructure = LibraryRecordToInclude::getObjectStructure();
		unset($libraryRecordToIncludeStructure['libraryId']);
		unset($libraryRecordToIncludeStructure['weight']);

		$librarySideLoadScopeStructure = LibrarySideLoadScope::getObjectStructure();
		unset($librarySideLoadScopeStructure['libraryId']);

		$manageMaterialsRequestFieldsToDisplayStructure = MaterialsRequestFieldsToDisplay::getObjectStructure();
		unset($manageMaterialsRequestFieldsToDisplayStructure['libraryId']); //needed?
		unset($manageMaterialsRequestFieldsToDisplayStructure['weight']);

		$materialsRequestFormatsStructure = MaterialsRequestFormats::getObjectStructure();
		unset($materialsRequestFormatsStructure['libraryId']); //needed?
		unset($materialsRequestFormatsStructure['weight']);

		$archiveExploreMoreBarStructure = ArchiveExploreMoreBar::getObjectStructure();
		unset($materialsRequestFormatsStructure['libraryId']); //needed?
		unset($materialsRequestFormatsStructure['weight']);

		$materialsRequestFormFieldsStructure = MaterialsRequestFormFields::getObjectStructure();
		unset($materialsRequestFormFieldsStructure['libraryId']); //needed?
		unset($materialsRequestFormFieldsStructure['weight']);

		$combinedResultsStructure = LibraryCombinedResultSection::getObjectStructure();
		unset($combinedResultsStructure['libraryId']);
		unset($combinedResultsStructure['weight']);

		require_once ROOT_DIR . '/sys/Theming/Theme.php';
		$theme = new Theme();
		$availableThemes = array();
		$theme->orderBy('themeName');
		$theme->find();
		while ($theme->fetch()) {
			$availableThemes[$theme->id] = $theme->themeName;
		}

		$materialsRequestOptions = [
			0 => 'None',
			1 => 'Aspen Request System',
			2 => 'ILS Request System',
			3 => 'External Request Link'
		];
		$catalog = CatalogFactory::getCatalogConnectionInstance();
		if (!$catalog->hasMaterialsRequestSupport()) {
			unset($materialsRequestOptions[2]);
		}

		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkDisplaySetting.php';
		$groupedWorkDisplaySetting = new GroupedWorkDisplaySetting();
		$groupedWorkDisplaySetting->orderBy('name');
		$groupedWorkDisplaySettings = [];
		$groupedWorkDisplaySetting->find();
		while ($groupedWorkDisplaySetting->fetch()){
			$groupedWorkDisplaySettings[$groupedWorkDisplaySetting->id] = $groupedWorkDisplaySetting->name;
		}

		require_once ROOT_DIR . '/sys/Browse/BrowseCategoryGroup.php';
		$browseCategoryGroup = new BrowseCategoryGroup();
		$browseCategoryGroups = [];
		$browseCategoryGroup->orderBy('name');
		$browseCategoryGroup->find();
		while ($browseCategoryGroup->fetch()){
			$browseCategoryGroups[$browseCategoryGroup->id] = $browseCategoryGroup->name;
		}

		require_once ROOT_DIR . '/sys/Theming/LayoutSetting.php';
		$layoutSetting = new LayoutSetting();
		$layoutSetting->orderBy('name');
		$layoutSettings = [];
		$layoutSetting->find();
		while ($layoutSetting->fetch()){
			$layoutSettings[$layoutSetting->id] = $layoutSetting->name;
		}

		require_once ROOT_DIR . '/sys/Hoopla/HooplaScope.php';
		$hooplaScope = new HooplaScope();
		$hooplaScope->orderBy('name');
		$hooplaScopes = [];
		$hooplaScope->find();
		$hooplaScopes[-1] = 'none';
		while ($hooplaScope->fetch()){
			$hooplaScopes[$hooplaScope->id] = $hooplaScope->name;
		}

		$overDriveScopes = [];
		$overDriveScopes[-1] = 'none';
		try {
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveScope.php';
			$overDriveScope = new OverDriveScope();
			$overDriveScope->orderBy('name');
			$overDriveScope->find();
			while ($overDriveScope->fetch()) {
				$overDriveScopes[$overDriveScope->id] = $overDriveScope->name;
			}
		}catch (Exception $e){
			//OverDrive scopes are likely not defined
		}

		require_once ROOT_DIR . '/sys/RBdigital/RBdigitalScope.php';
		require_once ROOT_DIR . '/sys/RBdigital/RBdigitalSetting.php';
		$rbdigitalScope = new RBdigitalScope();
		$rbdigitalScope->orderBy('name');
		$rbdigitalScopes = [];
		$rbdigitalScope->find();
		$rbdigitalScopes[-1] = 'none';
		while ($rbdigitalScope->fetch()){
			$rbdigitalSetting = new RBdigitalSetting();
			$rbdigitalSetting->id = $rbdigitalScope->settingId;
			$rbdigitalSetting->find(true);
			$rbdigitalScopes[$rbdigitalScope->id] = $rbdigitalScope->name . ' ' . $rbdigitalSetting->userInterfaceUrl;
		}

		require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibraryScope.php';
		$cloudLibraryScope = new CloudLibraryScope();
		$cloudLibraryScope->orderBy('name');
		$cloudLibraryScopes = [];
		$cloudLibraryScope->find();
		$cloudLibraryScopes[-1] = 'none';
		while ($cloudLibraryScope->fetch()){
			$cloudLibraryScopes[$cloudLibraryScope->id] = $cloudLibraryScope->name;
		}

		//$Instructions = 'For more information on ???, see the <a href="">online documentation</a>.';

		/** @noinspection HtmlRequiredAltAttribute */
		/** @noinspection RequiredAttributes */
		$structure = array(
			'isDefault' => array('property' => 'isDefault', 'type'=>'checkbox', 'label' => 'Default Library (one per install!)', 'description' => 'The default library instance for loading scoping information etc', 'hideInLists' => true),
			'libraryId' => array('property'=>'libraryId', 'type'=>'label', 'label'=>'Library Id', 'description'=>'The unique id of the library within the database', 'uniqueProperty' => true),
			'subdomain' => array('property'=>'subdomain', 'type'=>'text', 'label'=>'Subdomain', 'description'=>'A unique id to identify the library within the system', 'uniqueProperty' => true),
			'baseUrl' => array('property'=>'baseUrl', 'type'=>'text', 'label'=>'Base URL', 'description'=>'The Base URL for the library instance including the protocol (http or https).'),
			'displayName' => array('property'=>'displayName', 'type'=>'text', 'label'=>'Display Name', 'description'=>'A name to identify the library within the system', 'size'=>'40', 'uniqueProperty' => true),
			'showDisplayNameInHeader' => array('property'=>'showDisplayNameInHeader', 'type'=>'checkbox', 'label'=>'Show Display Name in Header', 'description'=>'Whether or not the display name should be shown in the header next to the logo', 'hideInLists' => true, 'default'=>false),
			'systemMessage' => array('property'=>'systemMessage', 'type'=>'html', 'label'=>'System Message', 'description'=>'A message to be displayed at the top of the screen', 'size'=>'80', 'maxLength' =>'512', 'allowableTags' => '<a><b><em><div><script><span><p><strong><sub><sup>', 'hideInLists' => true),
			'generateSitemap' => array('property'=>'generateSitemap', 'type'=>'checkbox', 'label'=>'Generate Sitemap', 'description'=>'Whether or not a sitemap should be generated for the library.', 'hideInLists' => true,),

			// Basic Display //
			'displaySection' =>array('property'=>'displaySection', 'type' => 'section', 'label' =>'Basic Display', 'hideInLists' => true,
					'helpLink' => '', 'properties' => array(
				'themeName' => array('property'=>'themeName', 'type'=>'text', 'label'=>'Theme Name', 'description'=>'The name of the theme which should be used for the library', 'hideInLists' => true, 'default' => 'default'),
				'theme' => array('property' => 'theme', 'type' => 'enum', 'label' => 'Theme', 'values' => $availableThemes, 'description' => 'The theme which should be used for the library', 'hideInLists' => true, 'default' => 'default'),
				'layoutSettingId' => ['property' => 'layoutSettingId', 'type' => 'enum', 'values' => $layoutSettings, 'label'=>'Layout Settings', 'description' => 'Layout Settings to apply to this interface'],
				'homeLink' => array('property'=>'homeLink', 'type'=>'text', 'label'=>'Home Link', 'description'=>'The location to send the user when they click on the home button or logo.  Use default or blank to go back to the Pika home location.', 'size'=>'40', 'hideInLists' => true,),
				'additionalCss' => array('property'=>'additionalCss', 'type'=>'textarea', 'label'=>'Additional CSS', 'description'=>'Extra CSS to apply to the site.  Will apply to all pages.', 'hideInLists' => true),
				'headerText' => array('property'=>'headerText', 'type'=>'html', 'label'=>'Header Text', 'description'=>'Optional Text to display in the header, between the logo and the log in/out buttons.  Will apply to all pages.', 'allowableTags' => '<a><b><em><div><span><p><strong><sub><sup><h1><h2><h3><h4><h5><h6><img>', 'hideInLists' => true),
			)),

			// Contact Links //
			'contactSection' => array('property'=>'contact', 'type' => 'section', 'label' =>'Contact Links', 'hideInLists' => true,
					'helpLink'=>'','properties' => array(
				'eContentSupportAddress' => array('property' => 'eContentSupportAddress', 'type' => 'multiemail', 'label' => 'E-Content Support Address', 'description' => 'An email address to receive support requests for patrons with eContent problems.', 'size' => '80', 'hideInLists' => true, 'default' => ''),
				'facebookLink' => array('property'=>'facebookLink', 'type'=>'text', 'label'=>'Facebook Link Url', 'description'=>'The url to Facebook (leave blank if the library does not have a Facebook account', 'size'=>'40', 'maxLength' => 255, 'hideInLists' => true),
				'twitterLink' => array('property'=>'twitterLink', 'type'=>'text', 'label'=>'Twitter Link Url', 'description'=>'The url to Twitter (leave blank if the library does not have a Twitter account', 'size'=>'40', 'maxLength' => 255, 'hideInLists' => true),
				'youtubeLink' => array('property'=>'youtubeLink', 'type'=>'text', 'label'=>'Youtube Link Url', 'description'=>'The url to Youtube (leave blank if the library does not have a Youtube account', 'size'=>'40', 'maxLength' => 255, 'hideInLists' => true),
				'instagramLink' => array('property'=>'instagramLink', 'type'=>'text', 'label'=>'Instagram Link Url', 'description'=>'The url to Instagram (leave blank if the library does not have a Instagram account', 'size'=>'40', 'maxLength' => 255, 'hideInLists' => true),
				'goodreadsLink' => array('property'=>'goodreadsLink', 'type'=>'text', 'label'=>'GoodReads Link Url', 'description'=>'The url to GoodReads (leave blank if the library does not have a GoodReads account', 'size'=>'40', 'maxLength' => 255, 'hideInLists' => true),
				'generalContactLink' => array('property'=>'generalContactLink', 'type'=>'text', 'label'=>'General Contact Link Url', 'description'=>'The url to a General Contact Page, i.e web form or mailto link', 'size'=>'40', 'maxLength' => 255, 'hideInLists' => true),
			)),

			// ILS/Account Integration //
			'ilsSection' => array('property'=>'ilsSection', 'type' => 'section', 'label' =>'ILS/Account Integration', 'hideInLists' => true,
					'helpLink'=>'', 'properties' => array(
				'ilsCode'                              => array('property'=>'ilsCode', 'type'=>'text', 'label'=>'ILS Code', 'description'=>'The location code that all items for this location start with.', 'size'=>'4', 'hideInLists' => false,),
				'scope'                                => array('property'=>'scope', 'type'=>'text', 'label'=>'Scope', 'description'=>'The scope for the system in Millennium to refine holdings for the user.', 'size'=>'4', 'hideInLists' => true,'default'=>0),
				'useScope'                             => array('property'=>'useScope', 'type'=>'checkbox', 'label'=>'Use Scope', 'description'=>'Whether or not the scope should be used when displaying holdings.', 'hideInLists' => true,),
				'showExpirationWarnings'               => array('property'=>'showExpirationWarnings', 'type'=>'checkbox', 'label'=>'Show Expiration Warnings', 'description'=>'Whether or not the user should be shown expiration warnings if their card is nearly expired.', 'hideInLists' => true, 'default' => 1),
				'expirationNearMessage'                => array('property'=>'expirationNearMessage', 'type'=>'text', 'label'=>'Expiration Near Message (use the token %date% to insert the expiration date)', 'description'=>'A message to show in the menu when the user account will expire soon', 'hideInLists' => true, 'default' => ''),
				'expiredMessage'                       => array('property'=>'expiredMessage', 'type'=>'text', 'label'=>'Expired Message (use the token %date% to insert the expiration date)', 'description'=>'A message to show in the menu when the user account has expired', 'hideInLists' => true, 'default' => ''),
				'enableMaterialsBooking'               => array('property'=>'enableMaterialsBooking', 'type'=>'checkbox', 'label'=>'Enable Materials Booking', 'description'=>'Check to enable integration of Sierra\'s Materials Booking module.', 'hideInLists' => true, 'default' => 0),
				'allowLinkedAccounts'                  => array('property'=>'allowLinkedAccounts', 'type'=>'checkbox', 'label'=>'Allow Linked Accounts', 'description' => 'Whether or not users can link multiple library cards under a single Pika account.', 'hideInLists' => true, 'default' => 1),
				'showLibraryHoursNoticeOnAccountPages' => array('property'=>'showLibraryHoursNoticeOnAccountPages', 'type'=>'checkbox', 'label'=>'Show Library Hours Notice on Account Pages', 'description'=>'Whether or not the Library Hours notice should be shown at the top of My Account\'s Checked Out, Holds and Bookings pages.', 'hideInLists' => true, 'default'=>true),
				'enableCourseReserves'                 => array('property'=>'enableCourseReserves', 'type'=>'checkbox', 'label'=>'Enable Repeat Search in Course Reserves', 'description'=>'Whether or not patrons can repeat searches within course reserves.', 'hideInLists' => true,),
				'pTypesSection'                        => array('property' => 'pTypesSectionSection', 'type' => 'section', 'label' => 'P-Types', 'hideInLists' => true,
						'helpLink'=>'','properties' => array(
					'pTypes'       => array('property'=>'pTypes', 'type'=>'text', 'label'=>'P-Types', 'description'=>'A list of pTypes that are valid for the library.  Separate multiple pTypes with commas.'),
					'defaultPType' => array('property'=>'defaultPType', 'type'=>'text', 'label'=>'Default P-Type', 'description'=>'The P-Type to use when accessing a subdomain if the patron is not logged in.','default'=>-1),
				)),
				'barcodeSection' => array('property' => 'barcodeSection', 'type' => 'section', 'label' => 'Barcode', 'hideInLists' => true,
						'helpLink' => '', 'properties' => array(
					'minBarcodeLength' => array('property'=>'minBarcodeLength', 'type'=>'integer', 'label'=>'Min Barcode Length', 'description'=>'A minimum length the patron barcode is expected to be. Leave as 0 to extra processing of barcodes.', 'hideInLists' => true, 'default'=>0),
					'maxBarcodeLength' => array('property'=>'maxBarcodeLength', 'type'=>'integer', 'label'=>'Max Barcode Length', 'description'=>'The maximum length the patron barcode is expected to be. Leave as 0 to extra processing of barcodes.', 'hideInLists' => true, 'default'=>0),
					'barcodePrefix'    => array('property'=>'barcodePrefix', 'type'=>'text', 'label'=>'Barcode Prefix', 'description'=>'A barcode prefix to apply to the barcode if it does not start with the barcode prefix or if it is not within the expected min/max range.  Multiple prefixes can be specified by separating them with commas. Leave blank to avoid additional processing of barcodes.', 'hideInLists' => true,'default'=>''),
				)),
				'userProfileSection' => array('property' => 'userProfileSection', 'type' => 'section', 'label' => 'User Profile', 'hideInLists' => true,
						'helpLink'=>'', 'properties' => array(
					'patronNameDisplayStyle'               => array('property'=>'patronNameDisplayStyle', 'type'=>'enum', 'values'=>array('firstinitial_lastname'=>'First Initial. Last Name', 'lastinitial_firstname'=>'First Name Last Initial.'), 'label'=>'Patron Display Name Style', 'description'=>'How to generate the patron display name'),
					'allowProfileUpdates'                  => array('property'=>'allowProfileUpdates', 'type'=>'checkbox', 'label'=>'Allow Profile Updates', 'description'=>'Whether or not the user can update their own profile.', 'hideInLists' => true, 'default' => 1, 'readonly' => false),
					'allowPatronAddressUpdates'            => array('property' => 'allowPatronAddressUpdates', 'type'=>'checkbox', 'label'=>'Allow Patrons to Update Their Address', 'description'=>'Whether or not patrons should be able to update their own address in their profile.', 'hideInLists' => true, 'default' => 1, 'readOnly' => false),
					'allowPinReset'                        => array('property'=>'allowPinReset', 'type'=>'checkbox', 'label'=>'Allow PIN Reset', 'description'=>'Whether or not the user can reset their PIN if they forget it.', 'hideInLists' => true, 'default' => 0),
					'enableForgotPasswordLink'             => array('property'=>'enableForgotPasswordLink', 'type'=>'checkbox', 'label'=>'Enable Forgot Password Link', 'description'=>'Whether or not the user can click a link to reset their password.', 'hideInLists' => true, 'default' => 1),
					'showAlternateLibraryOptionsInProfile' => array('property' => 'showAlternateLibraryOptionsInProfile', 'type'=>'checkbox', 'label'=>'Allow Patrons to Update their Alternate Libraries', 'description'=>'Allow Patrons to See and Change Alternate Library Settings in the Catalog Options Tab in their profile.', 'hideInLists' => true, 'default' => 1),
					'showWorkPhoneInProfile'               => array('property' => 'showWorkPhoneInProfile', 'type'=>'checkbox', 'label'=>'Show Work Phone in Profile', 'description'=>'Whether or not patrons should be able to change a secondary/work phone number in their profile.', 'hideInLists' => true, 'default' => 0),
					'treatPrintNoticesAsPhoneNotices'      => array('property' => 'treatPrintNoticesAsPhoneNotices', 'type' => 'checkbox', 'label' => 'Treat Print Notices As Phone Notices', 'description' => 'When showing detailed information about hold notices, treat print notices as if they are phone calls', 'hideInLists' => true, 'default' => 0),
					'showNoticeTypeInProfile'              => array('property' => 'showNoticeTypeInProfile', 'type'=>'checkbox', 'label'=>'Show Notice Type in Profile', 'description'=>'Whether or not patrons should be able to change how they receive notices in their profile.', 'hideInLists' => true, 'default' => 0),
					'showPickupLocationInProfile'          => array('property' => 'showPickupLocationInProfile', 'type'=>'checkbox', 'label'=>'Allow Patrons to Update Their Pickup Location', 'description'=>'Whether or not patrons should be able to update their preferred pickup location in their profile.', 'hideInLists' => true, 'default' => 0),
					'addSMSIndicatorToPhone'               => array('property' => 'addSMSIndicatorToPhone', 'type'=>'checkbox', 'label'=>'Add SMS Indicator to Primary Phone', 'description'=>'Whether or not add ### TEXT ONLY to the user\'s primary phone number when they opt in to SMS notices.', 'hideInLists' => true, 'default' => 0),
					'maxFinesToAllowAccountUpdates'        => array('property' => 'maxFinesToAllowAccountUpdates', 'type'=>'currency', 'displayFormat'=>'%0.2f', 'label'=>'Maximum Fine Amount to Allow Account Updates', 'description'=>'The maximum amount that a patron can owe and still update their account. Any value <= 0 will disable this functionality.', 'hideInLists' => true, 'default' => 10)
				)),
				'holdsSection' => array('property' => 'holdsSection', 'type' => 'section', 'label' => 'Holds', 'hideInLists' => true,
					'helpLink'=>'', 'properties' => array(
					'showHoldButton'                    => array('property'=>'showHoldButton', 'type'=>'checkbox', 'label'=>'Show Hold Button', 'description'=>'Whether or not the hold button is displayed so patrons can place holds on items', 'hideInLists' => true, 'default' => 1),
					'showHoldButtonInSearchResults'     => array('property'=>'showHoldButtonInSearchResults', 'type'=>'checkbox', 'label'=>'Show Hold Button within the search results', 'description'=>'Whether or not the hold button is displayed within the search results so patrons can place holds on items', 'hideInLists' => true, 'default' => 1),
					'showHoldButtonForUnavailableOnly'  => array('property'=>'showHoldButtonForUnavailableOnly', 'type'=>'checkbox', 'label'=>'Show Hold Button for items that are checked out only', 'description'=>'Whether or not the hold button is displayed within the search results so patrons can place holds on items', 'hideInLists' => true, 'default' => 1),
					'showHoldCancelDate'                => array('property'=>'showHoldCancelDate', 'type'=>'checkbox', 'label'=>'Show Cancellation Date', 'description'=>'Whether or not the patron should be able to set a cancellation date (not needed after date) when placing holds.', 'hideInLists' => true, 'default' => 1),
					'allowFreezeHolds'                  => array('property'=>'allowFreezeHolds', 'type'=>'checkbox', 'label'=>'Allow Freezing Holds', 'description'=>'Whether or not the user can freeze their holds.', 'hideInLists' => true, 'default' => 1),
					'defaultNotNeededAfterDays'         => array('property'=>'defaultNotNeededAfterDays', 'type'=>'integer', 'label'=>'Default Not Needed After Days', 'description'=>'Number of days to use for not needed after date by default. Use -1 for no default.', 'hideInLists' => true,),
					'showDetailedHoldNoticeInformation' => array('property' => 'showDetailedHoldNoticeInformation', 'type' => 'checkbox', 'label' => 'Show Detailed Hold Notice Information', 'description' => 'Whether or not the user should be presented with detailed hold notification information, i.e. you will receive an email/phone call to xxx when the hold is available', 'hideInLists' => true, 'default' => 1),
					'inSystemPickupsOnly'               => array('property'=>'inSystemPickupsOnly', 'type'=>'checkbox', 'label'=>'In System Pickups Only', 'description'=>'Restrict pickup locations to only locations within this library system.', 'hideInLists' => true, 'default' => true),
					'validPickupSystems'                => array('property'=>'validPickupSystems', 'type'=>'text', 'label'=>'Valid Pickup Library Systems', 'description'=>'Additional Library Systems that can be used as pickup locations if the &quot;In System Pickups Only&quot; is on. List the libraries\' subdomains separated by pipes |', 'size'=>'20', 'hideInLists' => true,),
					'holdDisclaimer'                    => array('property'=>'holdDisclaimer', 'type'=>'textarea', 'label'=>'Hold Disclaimer', 'description'=>'A disclaimer to display to patrons when they are placing a hold on items letting them know that their information may be available to other libraries.  Leave blank to not show a disclaimer.', 'hideInLists' => true,),
				)),
				'loginSection' => array('property' => 'loginSection', 'type' => 'section', 'label' => 'Login', 'hideInLists' => true,
						'helpLink' => '', 'properties' => array(
					'showLoginButton'         => array('property'=>'showLoginButton', 'type'=>'checkbox', 'label'=>'Show Login Button', 'description'=>'Whether or not the login button is displayed so patrons can login to the site', 'hideInLists' => true, 'default' => 1),
					'preventExpiredCardLogin' => array('property'=>'preventExpiredCardLogin', 'type'=>'checkbox', 'label'=>'Prevent Login for Expired Cards', 'description'=>'Users with expired cards will not be allowed to login. They will receive an expired card notice instead.', 'hideInLists' => true, 'default' => 0),
					'loginFormUsernameLabel'  => array('property'=>'loginFormUsernameLabel', 'type'=>'text', 'label'=>'Login Form Username Label', 'description'=>'The label to show for the username when logging in', 'size'=>'100', 'hideInLists' => true, 'default'=>'Your Name'),
					'loginFormPasswordLabel'  => array('property'=>'loginFormPasswordLabel', 'type'=>'text', 'label'=>'Login Form Password Label', 'description'=>'The label to show for the password when logging in', 'size'=>'100', 'hideInLists' => true, 'default'=>'Library Card Number'),
				)),
				'selfRegistrationSection' => array('property' => 'selfRegistrationSection', 'type' => 'section', 'label' => 'Self Registration', 'hideInLists' => true,
						'helpLink' => '', 'properties' => array(
					'enableSelfRegistration'         => array('property'=>'enableSelfRegistration', 'type'=>'checkbox', 'label'=>'Enable Self Registration', 'description'=>'Whether or not patrons can self register on the site', 'hideInLists' => true),
					'selfRegistrationLocationRestrictions' => ['property' => 'selfRegistrationLocationRestrictions', 'type' => 'enum', 'values' => [0 => 'No Restrictions', 1 => 'All Library Locations', 2 => 'All Hold Pickup Locations', 3 => 'Pickup Locations for the library'], 'label' => 'Valid Registration Locations', 'description' => 'Indicates which locations are valid pickup locations', 'hideInLists' => true],
					'promptForBirthDateInSelfReg'    => array('property' => 'promptForBirthDateInSelfReg', 'type' => 'checkbox', 'label' => 'Prompt For Birth Date', 'description'=>'Whether or not to prompt for birth date when self registering'),
					'selfRegistrationFormMessage'    => array('property'=>'selfRegistrationFormMessage', 'type'=>'html', 'label'=>'Self Registration Form Message', 'description'=>'Message shown to users with the form to submit the self registration.  Leave blank to give users the default message.', 'hideInLists' => true),
					'selfRegistrationSuccessMessage' => array('property'=>'selfRegistrationSuccessMessage', 'type'=>'html', 'label'=>'Self Registration Success Message', 'description'=>'Message shown to users when the self registration has been completed successfully.  Leave blank to give users the default message.', 'hideInLists' => true),
					'selfRegistrationTemplate'       => array('property'=>'selfRegistrationTemplate', 'type'=>'text', 'label'=>'Self Registration Template', 'description'=>'The ILS template to use during self registration (Sierra and Millennium).', 'hideInLists' => true, 'default' => 'default'),
				)),
				'masqueradeModeSection' => array('property' => 'masqueradeModeSection', 'type' => 'section', 'label' => 'Masquerade Mode', 'hideInLists' => true, 'properties' => array(
					'allowMasqueradeMode'                        => array('property'=>'allowMasqueradeMode', 'type'=>'checkbox', 'label'=>'Allow Masquerade Mode', 'description' => 'Whether or not staff users (depending on pType setting) can use Masquerade Mode.', 'hideInLists' => true, 'default' => false),
					'masqueradeAutomaticTimeoutLength'           => array('property'=>'masqueradeAutomaticTimeoutLength', 'type'=>'integer', 'label'=>'Masquerade Mode Automatic Timeout Length', 'description'=>'The length of time before an idle user\'s Masquerade session automatically ends in seconds.', 'size'=>'8', 'hideInLists' => true, 'max' => 240),
					'allowReadingHistoryDisplayInMasqueradeMode' => array('property'=>'allowReadingHistoryDisplayInMasqueradeMode', 'type'=>'checkbox', 'label'=>'Allow Display of Reading History in Masquerade Mode', 'description'=>'This option allows Guiding Users to view the Reading History of the masqueraded user.', 'hideInLists' => true, 'default' => false),
				)),
			)),

			'ecommerceSection' => array('property'=>'ecommerceSection', 'type' => 'section', 'label' =>'Fines/e-commerce', 'hideInLists' => true,
					'helpLink'=>'', 'properties' => array(
				'finePaymentType'          => array('property'=>'finePaymentType', 'type'=>'enum', 'label'=>'Show E-Commerce Link', 'values' => array(0 => 'No Payment', 1 => 'Link to ILS', 2 => 'PayPal'), 'description'=>'Whether or not users should be allowed to pay fines', 'hideInLists' => true,),
				'finesToPay'               => array('property'=>'finesToPay', 'type'=>'enum', 'label'=>'Which fines should be paid', 'values' => array(0 => 'All Fines', 1 => 'Selected Fines', 2 => 'Partial payment of selected fines'), 'description'=>'The fines that should be paid', 'hideInLists' => true,),
				'finePaymentOrder'         => array('property'=>'finePaymentOrder', 'type'=>'text', 'label'=>'Fine Payment Order by type (separated with pipes)', 'description'=>'The order fines should be paid in separated by pipes', 'hideInLists' => true, 'default' => 'default', 'size' => 80),
				'payFinesLink'             => array('property'=>'payFinesLink', 'type'=>'text', 'label'=>'Pay Fines Link', 'description'=>'The link to pay fines.  Leave as default to link to classic (should have eCommerce link enabled)', 'hideInLists' => true, 'default' => 'default', 'size' => 80),
				'payFinesLinkText'         => array('property'=>'payFinesLinkText', 'type'=>'text', 'label'=>'Pay Fines Link Text', 'description'=>'The text when linking to pay fines.', 'hideInLists' => true, 'default' => 'Click to Pay Fines Online', 'size' => 80),
				'minimumFineAmount'        => array('property'=>'minimumFineAmount', 'type'=>'currency', 'displayFormat'=>'%0.2f', 'label'=>'Minimum Fine Amount', 'description'=>'The minimum fine amount to display the e-commerce link', 'hideInLists' => true,),
				'showRefreshAccountButton' => array('property'=>'showRefreshAccountButton', 'type'=>'checkbox', 'label'=>'Show Refresh Account Button', 'description'=>'Whether or not a Show Refresh Account button is displayed in a pop-up when a user clicks the E-Commerce Link', 'hideInLists' => true, 'default' => true),
				'payPalSandboxMode'        => array('property'=>'payPalSandboxMode', 'type'=>'checkbox', 'label'=>'Use PayPal Sandbox', 'description'=>'Whether or not users to use PayPal in Sandbox mode', 'hideInLists' => true,),
				'payPalClientId'           => array('property'=>'payPalClientId', 'type'=>'text', 'label'=>'PayPal ClientID', 'description'=>'The Client ID to use when paying fines.', 'hideInLists' => true, 'default' => '', 'size' => 80),
				'payPalClientSecret'       => array('property'=>'payPalClientSecret', 'type'=>'storedPassword', 'label'=>'PayPal Client Secret', 'description'=>'The Client Secret to use when paying fines.', 'hideInLists' => true, 'default' => '', 'size' => 80),
			)),

			//Grouped Work Display
			'groupedWorkDisplaySettingId' => array('property' => 'groupedWorkDisplaySettingId', 'type' => 'enum', 'values'=>$groupedWorkDisplaySettings, 'label' => 'Grouped Work Display Settings', 'hideInLists' => false),

			// Searching //
			'searchingSection' => array('property'=>'searchingSection', 'type' => 'section', 'label' =>'Searching', 'hideInLists' => true,
					'helpLink'=>'', 'properties' => array(
				'restrictSearchByLibrary'                  => array('property' => 'restrictSearchByLibrary', 'type'=>'checkbox', 'label'=>'Restrict Search By Library', 'description'=>'Whether or not search results should only include titles from this library', 'hideInLists' => true),
				'publicListsToInclude'                     => array('property' => 'publicListsToInclude', 'type'=>'enum', 'values' => array(0 => 'No Lists', '1' => 'Lists from this library', '3'=>'Lists from library list publishers Only', '4'=>'Lists from all list publishers', '2' => 'All Lists'), 'label'=>'Public Lists To Include', 'description'=>'Which lists should be included in this scope'),
				'allowAutomaticSearchReplacements'         => array('property' => 'allowAutomaticSearchReplacements', 'type'=>'checkbox', 'label'=>'Allow Automatic Search Corrections', 'description'=>'Turn on to allow Pika to replace search terms that have no results if the current search term looks like a misspelling.', 'hideInLists' => true, 'default'=>true),

				'searchBoxSection' => array('property' => 'searchBoxSection', 'type' => 'section', 'label' => 'Search Box', 'hideInLists' => true, 'properties' => array(
					'systemsToRepeatIn'                      => array('property' => 'systemsToRepeatIn',        'type' => 'text',   'label' => 'Systems To Repeat In',        'description' => 'A list of library codes that you would like to repeat search in separated by pipes |.', 'size'=>'20', 'hideInLists' => true,),
					'repeatSearchOption'                     => array('property' => 'repeatSearchOption',       'type'=>'enum',     'label' => 'Repeat Search Options (requires Restrict Search to Library to be ON)',       'description'=>'Where to allow repeating search. Valid options are: none, librarySystem, marmot, all', 'values'=>array('none'=>'None', 'librarySystem'=>'Library System','marmot'=>'Consortium'),),
					'repeatInOnlineCollection'               => array('property' => 'repeatInOnlineCollection', 'type'=>'checkbox', 'label' => 'Repeat In Online Collection', 'description'=>'Turn on to allow repeat search in the Online Collection.', 'hideInLists' => true, 'default'=>false),
					'showAdvancedSearchbox'                  => array('property' => 'showAdvancedSearchbox',    'type'=>'checkbox', 'label' => 'Show Advanced Search Link',   'description'=>'Whether or not users should see the advanced search link below the search box.', 'hideInLists' => true, 'default' => 1),
				)),

				'searchFacetsSection' => array('property' => 'searchFacetsSection', 'type' => 'section', 'label' => 'Search Facets', 'hideInLists' => true, 'properties' => array(
					'facetLabel'                               => array('property' => 'facetLabel',                               'type' => 'text',     'label' => 'Library System Facet Label',                               'description'=>'The label for the library system in the Library System Facet.', 'size'=>'40', 'hideInLists' => true, 'maxLength' => 75),
					'showAvailableAtAnyLocation'               => array('property' => 'showAvailableAtAnyLocation',               'type' => 'checkbox', 'label' => 'Show Available At Any Location?',                          'description'=>'Whether or not to show any Marmot Location within the Available At facet', 'hideInLists' => true),
					'additionalLocationsToShowAvailabilityFor' => array('property' => 'additionalLocationsToShowAvailabilityFor', 'type' => 'text',     'label' => 'Additional Locations to Include in Available At Facet',    'description'=>'A list of library codes that you would like included in the available at facet separated by pipes |.', 'size'=>'20', 'hideInLists' => true,),
				)),
			)),

			'combinedResultsSection' => array('property' => 'combinedResultsSection', 'type' => 'section', 'label' => 'Combined Results', 'hideInLists' => true,
					'helpLink' => '',
					'properties' => array(
				'enableCombinedResults' => array('property' => 'enableCombinedResults', 'type'=>'checkbox', 'label'=>'Enable Combined Results', 'description'=>'Whether or not combined results should be shown ', 'hideInLists' => true, 'default' => false),
				'combinedResultsLabel' => array('property' => 'combinedResultsLabel', 'type' => 'text', 'label' => 'Combined Results Label', 'description' => 'The label to use in the search source box when combined results is active.', 'size'=>'20', 'hideInLists' => true, 'default' => 'Combined Results'),
				'defaultToCombinedResults' => array('property' => 'defaultToCombinedResults', 'type'=>'checkbox', 'label'=>'Default To Combined Results', 'description'=>'Whether or not combined results should be the default search source when active ', 'hideInLists' => true, 'default' => true),
				'combinedResultSections' => array(
					'property' => 'combinedResultSections',
					'type' => 'oneToMany',
					'label' => 'Combined Results Sections',
					'description' => 'Which sections should be shown in the combined results search display',
					'helpLink' => '',
					'keyThis' => 'libraryId',
					'keyOther' => 'libraryId',
					'subObjectType' => 'LibraryCombinedResultSection',
					'structure' => $combinedResultsStructure,
					'sortable' => true,
					'storeDb' => true,
					'allowEdit' => true,
					'canEdit' => false,
					'additionalOneToManyActions' => array(
					)
				),
			)),

			// Catalog Enrichment //
			'enrichmentSection' => ['property'=>'enrichmentSection', 'type' => 'section', 'label' =>'Catalog Enrichment', 'hideInLists' => true,
				'helpLink' => '', 'properties' => [
					//TODO database column rename for showFavorites to showLists?
					'showFavorites'            => array('property'=>'showFavorites', 'type'=>'checkbox', 'label'=>'Enable User Lists', 'description'=>'Whether or not users can maintain favorites lists', 'hideInLists' => true, 'default' => 1),
					'showConvertListsFromClassic' => array('property'=>'showConvertListsFromClassic', 'type'=>'checkbox', 'label'=>'Enable Importing Lists From Old Catalog', 'description'=>'Whether or not users can import lists from the ILS', 'hideInLists' => true, 'default' => 0),
					'showWikipediaContent'     => array('property'=>'showWikipediaContent', 'type'=>'checkbox', 'label'=>'Show Wikipedia Content', 'description'=>'Whether or not Wikipedia content should be shown on author page', 'default'=>'1', 'hideInLists' => true,),
				]
			],

			// Full Record Display //
			'fullRecordSection' => array('property'=>'fullRecordSection', 'type' => 'section', 'label' =>'Full Record Display', 'hideInLists' => true,
				'helpLink'=>'', 'properties' => array(
					'showEmailThis'            => array('property'=>'showEmailThis',            'type'=>'checkbox', 'label'=>'Show Email This',                   'description'=>'Whether or not the Email This link is shown', 'hideInLists' => true, 'default' => 1),
					'showShareOnExternalSites' => array('property'=>'showShareOnExternalSites', 'type'=>'checkbox', 'label'=>'Show Sharing To External Sites',    'description'=>'Whether or not sharing on external sites (Twitter, Facebook, Pinterest, etc. is shown)', 'hideInLists' => true, 'default' => 1),
				)
			),

			'browseCategoryGroupId' => array('property' => 'browseCategoryGroupId', 'type' => 'enum', 'values' => $browseCategoryGroups, 'label' => 'Browse Category Group', 'description' => 'The group of browse categories to show for this library', 'hideInLists' => true),

			'holdingsSummarySection' => array('property'=>'holdingsSummarySection', 'type' => 'section', 'label' =>'Holdings Summary', 'hideInLists' => true,
					'helpLink' => '', 'properties' => array(
				'showItsHere' => array('property'=>'showItsHere', 'type'=>'checkbox', 'label'=>'Show It\'s Here', 'description'=>'Whether or not the holdings summary should show It\'s here based on IP and the currently logged in patron\'s location.', 'hideInLists' => true, 'default' => 1),
				'showGroupedHoldCopiesCount' => array('property'=>'showGroupedHoldCopiesCount', 'type'=>'checkbox', 'label'=>'Show Hold and Copy Counts', 'description'=>'Whether or not the hold count and copies counts should be visible for grouped works when summarizing formats.', 'hideInLists' => true, 'default' => 1),
				'showOnOrderCounts' => array('property'=>'showOnOrderCounts', 'type'=>'checkbox', 'label'=>'Show On Order Counts', 'description'=>'Whether or not counts of Order Items should be shown .', 'hideInLists' => true, 'default' => 1),
			)),

			'materialsRequestSection'=> array('property'=>'materialsRequestSection', 'type' => 'section', 'label' =>'Materials Request', 'hideInLists' => true,
					'helpLink'=>'',
					'properties' => array(
				'enableMaterialsRequest'      => array('property'=>'enableMaterialsRequest', 'type'=>'enum', 'values'=>$materialsRequestOptions, 'label'=>'Materials Request System', 'description'=>'Materials Request functionality so patrons can request items not in the catalog.', 'hideInLists' => true, 'onchange' => 'return AspenDiscovery.Admin.updateMaterialsRequestFields();', 'default'=>0),
				'externalMaterialsRequestUrl' => array('property'=>'externalMaterialsRequestUrl', 'type'=>'text', 'label'=>'External Materials Request URL', 'description'=>'A link to an external Materials Request System to be used instead of the built in Pika system', 'hideInList' => true),
				'maxRequestsPerYear'          => array('property'=>'maxRequestsPerYear', 'type'=>'integer', 'label'=>'Max Requests Per Year', 'description'=>'The maximum number of requests that a user can make within a year', 'hideInLists' => true, 'default' => 60),
				'maxOpenRequests'             => array('property'=>'maxOpenRequests', 'type'=>'integer', 'label'=>'Max Open Requests', 'description'=>'The maximum number of requests that a user can have open at one time', 'hideInLists' => true, 'default' => 5),
				'newMaterialsRequestSummary'  => array('property'=>'newMaterialsRequestSummary', 'type'=>'html', 'label'=>'New Request Summary', 'description'=>'Text displayed at the top of Materials Request form to give users important information about the request they submit', 'size'=>'40', 'maxLength' =>'512', 'allowableTags' => '<a><b><em><div><script><span><p><strong><sub><sup>', 'hideInLists' => true),
				'materialsRequestDaysToPreserve' => array('property' => 'materialsRequestDaysToPreserve', 'type'=>'integer', 'label'=>'Delete Closed Requests Older than (days)', 'description' => 'The number of days to preserve closed requests.  Requests will be preserved for a minimum of 366 days.  We suggest preserving for at least 395 days.  Setting to a value of 0 will preserve all requests', 'hideInLists' => true, 'default' => 396),

				'materialsRequestFieldsToDisplay' => array(
					'property'      => 'materialsRequestFieldsToDisplay',
					'type'          => 'oneToMany',
					'label'         => 'Fields to display on Manage Materials Request Table',
					'description'   => 'Fields displayed when materials requests are listed for Managing',
					'keyThis'       => 'libraryId',
					'keyOther'      => 'libraryId',
					'subObjectType' => 'MaterialsRequestFieldsToDisplay',
					'structure'     => $manageMaterialsRequestFieldsToDisplayStructure,
					'sortable'      => true,
					'storeDb'       => true,
					'allowEdit'     => false,
					'canEdit'       => false,
				),

				'materialsRequestFormats' => array(
					'property'      => 'materialsRequestFormats',
					'type'          => 'oneToMany',
					'label'         => 'Formats of Materials that can be Requested',
					'description'   => 'Determine which material formats are available to patrons for request',
					'keyThis'       => 'libraryId',
					'keyOther'      => 'libraryId',
					'subObjectType' => 'MaterialsRequestFormats',
					'structure'     => $materialsRequestFormatsStructure,
					'sortable'      => true,
					'storeDb'       => true,
					'allowEdit'     => false,
					'canEdit'       => false,
					'additionalOneToManyActions' => array(
						0 => array(
							'text' => 'Set Materials Request Formats To Default',
							'url' => '/Admin/Libraries?id=$id&amp;objectAction=defaultMaterialsRequestFormats',
							'class' => 'btn-warning',
						)
					)
				),

				'materialsRequestFormFields' => array(
					'property'      => 'materialsRequestFormFields',
					'type'          => 'oneToMany',
					'label'         => 'Materials Request Form Fields',
					'description'   => 'Fields that are displayed in the Materials Request Form',
					'keyThis'       => 'libraryId',
					'keyOther'      => 'libraryId',
					'subObjectType' => 'MaterialsRequestFormFields',
					'structure'     => $materialsRequestFormFieldsStructure,
					'sortable'      => true,
					'storeDb'       => true,
					'allowEdit'     => false,
					'canEdit'       => false,
					'additionalOneToManyActions' => array(
						0 => array(
							'text' => 'Set Materials Request Form Structure To Default',
							'url' => '/Admin/Libraries?id=$id&amp;objectAction=defaultMaterialsRequestForm',
								'class' => 'btn-warning',
						)
					)
				),

			)),
			'interLibraryLoanSection' => array('property'=>'interLibraryLoanSectionSection', 'type' => 'section', 'label' =>'Interlibrary loans', 'hideInLists' => true,  'properties' => array(
				'interLibraryLoanName' => array('property'=>'interLibraryLoanName', 'type'=>'text', 'label'=>'Name of Interlibrary Loan Service', 'description'=>'The name to be displayed in the link to the ILL service ', 'hideInLists' => true, 'size'=>'80'),
				'interLibraryLoanUrl' => array('property'=>'interLibraryLoanUrl',   'type'=>'text', 'label'=>'Interlibrary Loan URL', 'description'=>'The link for the ILL Service.', 'hideInLists' => true, 'size'=>'80'),

				'prospectorSection' => array('property'=>'prospectorSection', 'type' => 'section', 'label' =>'Prospector', 'hideInLists' => true,
						'helpLink'=>'', 'properties' => array(
					'repeatInProspector'  => array('property'=>'repeatInProspector', 'type'=>'checkbox', 'label'=>'Repeat In Prospector', 'description'=>'Turn on to allow repeat search in Prospector functionality.', 'hideInLists' => true, 'default' => 1),
					'prospectorCode' => array('property'=>'prospectorCode', 'type'=>'text', 'label'=>'Prospector Code', 'description'=>'The code used to identify this location within Prospector. Leave blank if items for this location are not in Prospector.', 'hideInLists' => true,),
					'enableProspectorIntegration'=> array('property'=>'enableProspectorIntegration', 'type'=>'checkbox', 'label'=>'Enable Prospector Integration', 'description'=>'Whether or not Prospector Integrations should be displayed for this library.', 'hideInLists' => true, 'default' => 1),
					'showProspectorResultsAtEndOfSearch' => array('property'=>'showProspectorResultsAtEndOfSearch', 'type'=>'checkbox', 'label'=>'Show Prospector Results At End Of Search', 'description'=>'Whether or not Prospector Search Results should be shown at the end of search results.', 'hideInLists' => true, 'default' => 1),
				)),
				'worldCatSection' => array('property'=>'worldCatSection', 'type' => 'section', 'label' =>'WorldCat', 'hideInLists' => true,
						'helpLink'=>'', 'properties' => array(
					'repeatInWorldCat'  => array('property'=>'repeatInWorldCat', 'type'=>'checkbox', 'label'=>'Repeat In WorldCat', 'description'=>'Turn on to allow repeat search in WorldCat functionality.', 'hideInLists' => true,),
					'worldCatUrl' => array('property'=>'worldCatUrl', 'type'=>'text', 'label'=>'WorldCat URL', 'description'=>'A custom World Cat URL to use while searching.', 'hideInLists' => true, 'size'=>'80'),
					'worldCatQt' => array('property'=>'worldCatQt', 'type'=>'text', 'label'=>'WorldCat QT', 'description'=>'A custom World Cat QT term to use while searching.', 'hideInLists' => true, 'size'=>'40'),
				)),
			)),

			'overdriveSection' => array('property'=>'overdriveSection', 'type' => 'section', 'label' =>'OverDrive', 'hideInLists' => true, 'renderAsHeading' => true, 'properties' => array(
				'overDriveScopeId'               => array('property' => 'overDriveScopeId', 'type' => 'enum', 'values' => $overDriveScopes, 'label' => 'OverDrive Scope', 'description' => 'The OverDrive scope to use', 'hideInLists' => true, 'default' => -1),
			)),
			'hooplaSection' => array('property' => 'hooplaSection', 'type' => 'section', 'label' => 'Hoopla', 'hideInLists' => true, 'renderAsHeading' => true, 'properties' => array(
				'hooplaLibraryID' => array('property' => 'hooplaLibraryID', 'type' => 'integer', 'label' => 'Hoopla Library ID', 'description' => 'The ID Number Hoopla uses for this library', 'hideInLists' => true),
				'hooplaScopeId' => array('property' => 'hooplaScopeId', 'type' => 'enum', 'values' => $hooplaScopes, 'label' => 'Hoopla Scope', 'description' => 'The hoopla scope to use', 'hideInLists' => true, 'default' => -1),
			)),
			'rbdigitalSection' => array('property'=>'rbdigitalSection', 'type' => 'section', 'label' =>'RBdigital', 'hideInLists' => true, 'renderAsHeading' => true, 'properties' => array(
				'rbdigitalScopeId'        => array('property'=>'rbdigitalScopeId', 'type'=>'enum','values'=>$rbdigitalScopes, 'label'=>'RBdigital Scope', 'description'=>'The RBdigital scope to use', 'hideInLists' => true, 'default'=>-1),
			)),
			'cloudLibrarySection' => array('property'=>'cloudLibrarySection', 'type' => 'section', 'label' =>'Cloud Library', 'hideInLists' => true, 'renderAsHeading' => true, 'properties' => array(
				'cloudLibraryScopeId'        => array('property'=>'cloudLibraryScopeId', 'type'=>'enum','values'=>$cloudLibraryScopes,  'label'=>'Cloud Library Scope', 'description'=>'The Cloud Library scope to use', 'hideInLists' => true, 'default'=>-1),
			)),
			'genealogySection' => array('property' => 'genealogySection', 'type' => 'section', 'label' => 'Genealogy', 'hideInLists' => true, 'renderAsHeading' => true, 'properties' => [
					'enableGenealogy' => array('property' => 'enableGenealogy', 'type' => 'checkbox', 'label' => 'Enable Genealogy Functionality', 'description' => 'Whether or not patrons can search genealogy.', 'hideInLists' => true, 'default' => 1),
			]),
			'archiveSection' => array('property'=>'archiveSection', 'type' => 'section', 'label' =>'Local Content Archive', 'hideInLists' => true, 'helpLink'=>'', 'properties' => array(
				'enableArchive' => array('property'=>'enableArchive', 'type'=>'checkbox', 'label'=>'Allow Searching the Archive', 'description'=>'Whether or not information from the archive is shown in Pika.', 'hideInLists' => true, 'default' => 0),
				'archiveNamespace' => array('property'=>'archiveNamespace', 'type'=>'text', 'label'=>'Archive Namespace', 'description'=>'The namespace of your library in the archive', 'hideInLists' => true, 'maxLength' => 30, 'size'=>'30'),
				'archivePid' => array('property'=>'archivePid', 'type'=>'text', 'label'=>'Organization PID for Library', 'description'=>'A link to a representation of the library in the archive', 'hideInLists' => true, 'maxLength' => 50, 'size'=>'50'),
				'hideAllCollectionsFromOtherLibraries' => array('property'=>'hideAllCollectionsFromOtherLibraries', 'type'=>'checkbox', 'label'=>'Hide Collections from Other Libraries', 'description'=>'Whether or not collections created by other libraries is shown in Pika.', 'hideInLists' => true, 'default' => 0),
				'collectionsToHide' => array('property'=>'collectionsToHide', 'type'=>'textarea', 'label'=>'Collections To Hide', 'description'=>'Specific collections to hide.', 'hideInLists' => true),
				'objectsToHide' => array('property'=>'objectsToHide', 'type'=>'textarea', 'label'=>'Objects To Hide', 'description'=>'Specific objects to hide.', 'hideInLists' => true),
				'defaultArchiveCollectionBrowseMode' => array('property' => 'defaultArchiveCollectionBrowseMode', 'type' => 'enum', 'label'=>'Default Viewing Mode for Archive Collections (Exhibits)', 'description' => 'Sets how archive collections will be displayed by default when users haven\'t chosen a mode themselves.', 'hideInLists' => true, 'values'=> array('covers' => 'Show Covers', 'list' => 'Show List'), 'default' => 'covers'),

				'archiveMoreDetailsSection' => array('property'=>'archiveMoreDetailsSection', 'type' => 'section', 'label' => 'Archive More Details ', 'hideInLists' => true, 'properties' => array(
					'archiveMoreDetailsRelatedObjectsOrEntitiesDisplayMode' => array('property' => 'archiveMoreDetailsRelatedObjectsOrEntitiesDisplayMode', 'label' => 'Related Object/Entity Sections Display Mode', 'type' => 'enum', 'values' => self::$archiveMoreDetailsDisplayModeOptions, 'default' => 'tiled', 'description' => 'How related objects and entities will be displayed in the More Details accordion on Archive pages.'),

					'archiveMoreDetailsOptions' => array(
						'property' => 'archiveMoreDetailsOptions',
						'type' => 'oneToMany',
						'label' => 'More Details Configuration',
						'description' => 'Configuration for the display of the More Details accordion for archive object views',
						'keyThis' => 'libraryId',
						'keyOther' => 'libraryId',
						'subObjectType' => 'LibraryArchiveMoreDetails',
						'structure' => $libraryArchiveMoreDetailsStructure,
						'sortable' => true,
						'storeDb' => true,
						'allowEdit' => true,
						'canEdit' => false,
						'additionalOneToManyActions' => array(
							0 => array(
								'text' => 'Reset Archive More Details To Default',
								'url' => '/Admin/Libraries?id=$id&amp;objectAction=resetArchiveMoreDetailsToDefault',
								'class' => 'btn-warning',
							)
						)
					),
				)),

				'archiveRequestSection' => array('property'=>'archiveRequestSection', 'type' => 'section', 'label' =>'Archive Copy Requests ', 'hideInLists' => true, 'properties' => array(

					'allowRequestsForArchiveMaterials' => array('property'=>'allowRequestsForArchiveMaterials', 'type'=>'checkbox', 'label'=>'Allow Requests for Copies of Archive Materials', 'description'=>'Enable to allow requests for copies of your archive materials'),
					'archiveRequestMaterialsHeader' => array('property'=>'archiveRequestMaterialsHeader', 'type'=>'html', 'label'=>'Archive Request Header Text', 'description'=>'The text to be shown above the form for requests of copies for archive materials'),
					'claimAuthorshipHeader' => array('property'=>'claimAuthorshipHeader', 'type'=>'html', 'label'=>'Claim Authorship Header Text', 'description'=>'The text to be shown above the form when people try to claim authorship of archive materials'),
					'archiveRequestEmail' => array('property'=>'archiveRequestEmail', 'type'=>'email', 'label'=>'Email to send archive requests to', 'description'=>'The email address to send requests for archive materials to', 'hideInLists' => true),

					// Archive Form Fields
					'archiveRequestFieldName'           => array('property'=>'archiveRequestFieldName',           'type'=>'enum', 'values'=> self::$archiveRequestFormFieldOptions, 'default'=> 2, 'label'=>'Copy Request Field : Name', 'description'=>'Should this field be hidden, or displayed as an optional field or a required field'),
					'archiveRequestFieldAddress'        => array('property'=>'archiveRequestFieldAddress',        'type'=>'enum', 'values'=> self::$archiveRequestFormFieldOptions, 'default'=> 1, 'label'=>'Copy Request Field : Address', 'description'=>'Should this field be hidden, or displayed as an optional field or a required field'),
					'archiveRequestFieldAddress2'       => array('property'=>'archiveRequestFieldAddress2',       'type'=>'enum', 'values'=> self::$archiveRequestFormFieldOptions, 'default'=> 1, 'label'=>'Copy Request Field : Address2', 'description'=>'Should this field be hidden, or displayed as an optional field or a required field'),
					'archiveRequestFieldCity'           => array('property'=>'archiveRequestFieldCity',           'type'=>'enum', 'values'=> self::$archiveRequestFormFieldOptions, 'default'=> 1, 'label'=>'Copy Request Field : City', 'description'=>'Should this field be hidden, or displayed as an optional field or a required field'),
					'archiveRequestFieldState'          => array('property'=>'archiveRequestFieldState',          'type'=>'enum', 'values'=> self::$archiveRequestFormFieldOptions, 'default'=> 1, 'label'=>'Copy Request Field : State', 'description'=>'Should this field be hidden, or displayed as an optional field or a required field'),
					'archiveRequestFieldZip'            => array('property'=>'archiveRequestFieldZip',            'type'=>'enum', 'values'=> self::$archiveRequestFormFieldOptions, 'default'=> 1, 'label'=>'Copy Request Field : Zip Code', 'description'=>'Should this field be hidden, or displayed as an optional field or a required field'),
					'archiveRequestFieldCountry'        => array('property'=>'archiveRequestFieldCountry',        'type'=>'enum', 'values'=> self::$archiveRequestFormFieldOptions, 'default'=> 1, 'label'=>'Copy Request Field : Country', 'description'=>'Should this field be hidden, or displayed as an optional field or a required field'),
					'archiveRequestFieldPhone'          => array('property'=>'archiveRequestFieldPhone',          'type'=>'enum', 'values'=> self::$archiveRequestFormFieldOptions, 'default'=> 2, 'label'=>'Copy Request Field : Phone', 'description'=>'Should this field be hidden, or displayed as an optional field or a required field'),
					'archiveRequestFieldAlternatePhone' => array('property'=>'archiveRequestFieldAlternatePhone', 'type'=>'enum', 'values'=> self::$archiveRequestFormFieldOptions, 'default'=> 1, 'label'=>'Copy Request Field : Alternate Phone', 'description'=>'Should this field be hidden, or displayed as an optional field or a required field'),
					'archiveRequestFieldFormat'         => array('property'=>'archiveRequestFieldFormat',         'type'=>'enum', 'values'=> self::$archiveRequestFormFieldOptions, 'default'=> 1, 'label'=>'Copy Request Field : Format', 'description'=>'Should this field be hidden, or displayed as an optional field or a required field'),
					'archiveRequestFieldPurpose'        => array('property'=>'archiveRequestFieldPurpose',        'type'=>'enum', 'values'=> self::$archiveRequestFormFieldOptions, 'default'=> 2, 'label'=>'Copy Request Field : Purpose', 'description'=>'Should this field be hidden, or displayed as an optional field or a required field'),
				)),

				'exploreMoreBar' => array(
					'property'      => 'exploreMoreBar',
					'type'          => 'oneToMany',
					'label'         => 'Archive Explore More Bar Configuration',
					'description'   => 'Control the order of Explore More Sections and if they are open by default',
					'keyThis'       => 'libraryId',
					'keyOther'      => 'libraryId',
					'subObjectType' => 'ArchiveExploreMoreBar',
					'structure'     => $archiveExploreMoreBarStructure,
					'sortable'      => true,
					'storeDb'       => true,
					'allowEdit'     => false,
					'canEdit'       => false,
					'additionalOneToManyActions' => array(
						0 => array(
							'text'  => 'Set Archive Explore More Options To Default',
							'url'   => '/Admin/Libraries?id=$id&amp;objectAction=defaultArchiveExploreMoreOptions',
							'class' => 'btn-warning',
						)
					)
				),

				'archiveSearchFacets' => array(
					'property' => 'archiveSearchFacets',
					'type' => 'oneToMany',
					'label' => 'Archive Search Facets',
					'description' => 'A list of facets to display in archive search results',
					'helpLink' => '',
					'keyThis' => 'libraryId',
					'keyOther' => 'libraryId',
					'subObjectType' => 'LibraryArchiveSearchFacetSetting',
					'structure' => $archiveSearchFacetSettingStructure,
					'sortable' => true,
					'storeDb' => true,
					'allowEdit' => true,
					'canEdit' => true,
					'additionalOneToManyActions' => array(
						array(
							'text' => 'Copy Library Archive Search Facets',
							'url' => '/Admin/Libraries?id=$id&amp;objectAction=copyArchiveSearchFacetsFromLibrary',
						),
						array(
							'text' => 'Reset Archive Search Facets To Default',
							'url' => '/Admin/Libraries?id=$id&amp;objectAction=resetArchiveSearchFacetsToDefault',
							'class' => 'btn-warning',
						),
					)
				),
			)),

			'oaiSection' => array('property' => 'oaiSection', 'type' => 'section', 'label' => 'Open Archives Results', 'hideInLists' => true, 'helpLink' => '', 'renderAsHeading' => true, 'properties' => array(
				'enableOpenArchives' => array('property' => 'enableOpenArchives', 'type' => 'checkbox', 'label' => 'Allow Searching Open Archives', 'description' => 'Whether or not information from indexed Open Archives is shown.', 'hideInLists' => true, 'default' => 0),
			)),

			'edsSection' => array('property' => 'edsSection', 'type' => 'section', 'label' => 'EBSCO EDS', 'hideInLists' => true, 'properties' => array(
				'edsApiProfile' => array('property' => 'edsApiProfile', 'type' => 'text', 'label' => 'EDS API Profile', 'description' => 'The profile to use when connecting to the EBSCO API', 'hideInLists' => true),
				'edsSearchProfile' => array('property' => 'edsSearchProfile', 'type' => 'text', 'label' => 'EDS Search Profile', 'description' => 'The profile to use when linking to EBSCO EDS', 'hideInLists' => true),
				'edsApiUsername' => array('property' => 'edsApiUsername', 'type' => 'text', 'label' => 'EDS API Username', 'description' => 'The username to use when connecting to the EBSCO API', 'hideInLists' => true),
				'edsApiPassword' => array('property' => 'edsApiPassword', 'type' => 'text', 'label' => 'EDS API Password', 'description' => 'The password to use when connecting to the EBSCO API', 'hideInLists' => true),
			)),

			'casSection' => array('property'=>'casSection', 'type' => 'section', 'label' =>'CAS Single Sign On', 'hideInLists' => true, 'helpLink'=>'', 'properties' => array(
				'casHost' => array('property'=>'casHost', 'type'=>'text', 'label'=>'CAS Host', 'description'=>'The host to use for CAS authentication', 'hideInLists' => true),
				'casPort' => array('property'=>'casPort', 'type'=>'integer', 'label'=>'CAS Port', 'description'=>'The port to use for CAS authentication (typically 443)', 'hideInLists' => true),
				'casContext' => array('property'=>'casContext', 'type'=>'text', 'label'=>'CAS Context', 'description'=>'The context to use for CAS', 'hideInLists' => true),
			)),

			'dplaSection' => array('property'=>'dplaSection', 'type' => 'section', 'label' =>'DPLA', 'hideInLists' => true, 'helpLink'=> '', 'properties' => array(
				'includeDplaResults' => array('property'=>'includeDplaResults', 'type'=>'checkbox', 'label'=>'Include DPLA content in search results', 'description'=>'Whether or not DPLA data should be included for this library.', 'hideInLists' => true, 'default' => 0),
			)),

			'holidays' => array(
				'property' => 'holidays',
				'type' => 'oneToMany',
				'label' => 'Holidays',
				'description' => 'Holidays',
				'helpLink' => '',
				'keyThis' => 'libraryId',
				'keyOther' => 'libraryId',
				'subObjectType' => 'Holiday',
				'structure' => $holidaysStructure,
				'sortable' => false,
				'storeDb' => true
			),

			'libraryLinks' => array(
				'property' => 'libraryLinks',
				'type' => 'oneToMany',
				'label' => 'Sidebar Links',
				'description' => 'Links To Show in the sidebar',
				'helpLink' => '',
				'keyThis' => 'libraryId',
				'keyOther' => 'libraryId',
				'subObjectType' => 'LibraryLink',
				'structure' => $libraryLinksStructure,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => true,
			),

			'libraryTopLinks' => array(
				'property' => 'libraryTopLinks',
				'type' => 'oneToMany',
				'label' => 'Header Links',
				'description' => 'Links To Show in the header',
				'helpLink' => '',
				'keyThis' => 'libraryId',
				'keyOther' => 'libraryId',
				'subObjectType' => 'LibraryTopLinks',
				'structure' => $libraryTopLinksStructure,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
			),

			'recordsOwned' => array(
				'property' => 'recordsOwned',
				'type' => 'oneToMany',
				'label' => 'Records Owned',
				'description' => 'Information about what records are owned by the library',
				'helpLink' => '',
				'keyThis' => 'libraryId',
				'keyOther' => 'libraryId',
				'subObjectType' => 'LibraryRecordOwned',
				'structure' => $libraryRecordOwnedStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
			),

			'recordsToInclude' => array(
				'property' => 'recordsToInclude',
				'type' => 'oneToMany',
				'label' => 'Records To Include',
				'description' => 'Information about what records to include in this scope',
				'helpLink' => '',
				'keyThis' => 'libraryId',
				'keyOther' => 'libraryId',
				'subObjectType' => 'LibraryRecordToInclude',
				'structure' => $libraryRecordToIncludeStructure,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
			),

			'sideLoadScopes' => array(
				'property' => 'sideLoadScopes',
				'type' => 'oneToMany',
				'label' => 'Side Loaded eContent Scopes',
				'description' => 'Information about what Side Loads to include in this scope',
				'keyThis' => 'libraryId',
				'keyOther' => 'libraryId',
				'subObjectType' => 'LibrarySideLoadScope',
				'structure' => $librarySideLoadScopeStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => true,
			),
		);

		if (UserAccount::userHasRole('libraryManager')){
			$structure['subdomain']['type'] = 'label';
			$structure['displayName']['type'] = 'label';
			unset($structure['showDisplayNameInHeader']);
			unset($structure['displaySection']);
			unset($structure['ilsSection']);
			unset($structure['ecommerceSection']);
			unset($structure['searchingSection']);
			unset($structure['enrichmentSection']);
			unset($structure['fullRecordSection']);
			unset($structure['holdingsSummarySection']);
			unset($structure['materialsRequestSection']);
			unset($structure['prospectorSection']);
			unset($structure['worldCatSection']);
			unset($structure['overdriveSection']);
			unset($structure['archiveSection']);
			unset($structure['edsSection']);
			unset($structure['dplaSection']);
			unset($structure['recordsOwned']);
			unset($structure['recordsToInclude']);
			unset($structure['sideLoadScopes']);
		}

		//Update settings based on what we have access to
		global $configArray;
		if (!$configArray['Islandora']['enabled']) {
			unset($structure['archiveSection']);
		}
		$ils = $configArray['Catalog']['ils'];
		if ($ils != 'Millennium' && $ils != 'Sierra') {
			unset($structure['displaySection']['properties']['enableCourseReserves']);
			unset($structure['ilsSection']['properties']['scope']);
			unset($structure['ilsSection']['properties']['useScope']);
			unset($structure['ilsSection']['properties']['enableMaterialsBooking']);
			unset($structure['ilsSection']['properties']['pTypesSection']);
		}
		if ($ils == 'Koha') {
			//unset($structure['ilsSection']['properties']['userProfileSection']['properties']['allowProfileUpdates']);
			//unset($structure['ilsSection']['properties']['userProfileSection']['properties']['allowPatronAddressUpdates']);
			unset($structure['ilsSection']['properties']['userProfileSection']['properties']['showWorkPhoneInProfile']);
			unset($structure['ilsSection']['properties']['userProfileSection']['properties']['treatPrintNoticesAsPhoneNotices']);
			unset($structure['ilsSection']['properties']['userProfileSection']['properties']['showNoticeTypeInProfile']);
			unset($structure['ilsSection']['properties']['userProfileSection']['properties']['addSMSIndicatorToPhone']);
			unset($structure['ilsSection']['properties']['userProfileSection']['properties']['maxFinesToAllowAccountUpdates']);
			unset($structure['ilsSection']['properties']['selfRegistrationSection']['properties']['promptForBirthDateInSelfReg']);
			unset($structure['ilsSection']['properties']['selfRegistrationSection']['properties']['selfRegistrationTemplate']);
		}
		if (!$configArray['EDS']['enabled']) {
			unset($structure['edsSection']);
		}
		if (!$configArray['CAS']['enabled']) {
			unset($structure['casSection']);
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
		if (!array_key_exists('Open Archives', $enabledModules)){
			unset($structure['oaiSection']);
		}
		return $structure;
	}

	static $searchLibrary  = array();
	static function getSearchLibrary($searchSource = null){
		if ($searchSource == null){
			global $searchSource;
		}
		if ($searchSource == 'combinedResults'){
			$searchSource = 'local';
		}
		if (!array_key_exists($searchSource, Library::$searchLibrary)){
			$scopingSetting = $searchSource;
			if ($scopingSetting == null){
				return null;
			} else if ($scopingSetting == 'local' || $scopingSetting == 'econtent' || $scopingSetting == 'library' || $scopingSetting == 'location'){
				Library::$searchLibrary[$searchSource] = Library::getActiveLibrary();
			}else if ($scopingSetting == 'marmot' || $scopingSetting == 'unscoped'){
				//Get the default library
				$library = new Library();
				$library->isDefault = true;
				$library->find();
				if ($library->getNumResults() > 0){
					$library->fetch();
					Library::$searchLibrary[$searchSource] = clone($library);
				}else{
					Library::$searchLibrary[$searchSource] = null;
				}
			}else{
				$location = Location::getSearchLocation();
				if (is_null($location)){
					//Check to see if we have a library for the subdomain
					$library = new Library();
					$library->subdomain = $scopingSetting;
					$library->find();
					if ($library->getNumResults() > 0){
						$library->fetch();
						Library::$searchLibrary[$searchSource] = clone($library);
						return clone($library);
					}else{
						Library::$searchLibrary[$searchSource] = null;
					}
				}else{
					Library::$searchLibrary[$searchSource] = self::getLibraryForLocation($location->locationId);
				}
			}
		}
		return Library::$searchLibrary[$searchSource];
	}

	static function getActiveLibrary(){
		global $library;
		//First check to see if we have a library loaded based on subdomain (loaded in index)
		if (isset($library)) {
			return $library;
		}
		//If there is only one library, that library is active by default.
		$activeLibrary = new Library();
		$activeLibrary->find();
		if ($activeLibrary->getNumResults() == 1){
			$activeLibrary->fetch();
			return $activeLibrary;
		} else if ($activeLibrary->getNumResults() == 0) {
			echo("No libraries are configured for the system.  Please configure at least one library before proceeding.");
			die();
		}
		//Next check to see if we are in a library.
		/** @var Location $locationSingleton */
		global $locationSingleton;
		$physicalLocation = $locationSingleton->getActiveLocation();
		if (!is_null($physicalLocation)){
			//Load the library based on the home branch for the user
			return self::getLibraryForLocation($physicalLocation->libraryId);
		}

		//Return the active library
		$activeLibrary->isDefault = 1;
		$activeLibrary->find(true);
		if ($activeLibrary->getNumResults() == 0) {
			echo("There is not a default library configured in the system.  Please configure one default library before proceeding.");
			die();
		} else if ($activeLibrary->getNumResults() > 1) {
			echo("There are multiple default libraries configured in the system.  Please set only one library to be the default before proceeding.");
			die();
		}
		return $activeLibrary;
	}

	/**
	 * @param User|null $tmpUser
	 * @return Library|null
	 */
	static function getPatronHomeLibrary($tmpUser = null){
		//Finally check to see if the user has logged in and if so, use that library
		if ($tmpUser != null){
			return self::getLibraryForLocation($tmpUser->homeLocationId);
		}
		if (UserAccount::isLoggedIn()){
			//Load the library based on the home branch for the user
			return self::getLibraryForLocation(UserAccount::getUserHomeLocationId());
		}else{
			return null;
		}
	}

	static function getLibraryForLocation($locationId){
		if (isset($locationId)){
			$libLookup = new Library();
			$libLookup->whereAdd('libraryId = (SELECT libraryId FROM location WHERE locationId = ' . $libLookup->escape($locationId) . ')');
			$libLookup->find();
			if ($libLookup->getNumResults() > 0){
				$libLookup->fetch();
				return clone $libLookup;
			}
		}
		return null;
	}

	public function __get($name){
		if ($name == "holidays") {
			if (!isset($this->holidays) && $this->libraryId){
				$this->holidays = array();
				$holiday = new Holiday();
				$holiday->libraryId = $this->libraryId;
				$holiday->orderBy('date');
				$holiday->find();
				while($holiday->fetch()){
					$this->holidays[$holiday->id] = clone($holiday);
				}
			}
			return $this->holidays;
		}elseif ($name == "archiveMoreDetailsOptions") {
			if (!isset($this->archiveMoreDetailsOptions) && $this->libraryId){
				$this->archiveMoreDetailsOptions = array();
				$moreDetailsOptions = new LibraryArchiveMoreDetails();
				$moreDetailsOptions->libraryId = $this->libraryId;
				$moreDetailsOptions->orderBy('weight');
				$moreDetailsOptions->find();
				while($moreDetailsOptions->fetch()){
					$this->archiveMoreDetailsOptions[$moreDetailsOptions->id] = clone($moreDetailsOptions);
				}
			}
			return $this->archiveMoreDetailsOptions;
		}elseif ($name == "archiveSearchFacets") {
			if (!isset($this->archiveSearchFacets) && $this->libraryId){
				$this->archiveSearchFacets = array();
				$facet = new LibraryArchiveSearchFacetSetting();
				$facet->libraryId = $this->libraryId;
				$facet->orderBy('weight');
				$facet->find();
				while($facet->fetch()){
					$this->archiveSearchFacets[$facet->id] = clone($facet);
				}
			}
			return $this->archiveSearchFacets;
		}elseif ($name == 'libraryLinks'){
			if (!isset($this->libraryLinks) && $this->libraryId){
				$this->libraryLinks = array();
				$libraryLink = new LibraryLink();
				$libraryLink->libraryId = $this->libraryId;
				$libraryLink->orderBy('weight');
				$libraryLink->find();
				while ($libraryLink->fetch()){
					$this->libraryLinks[$libraryLink->id] = clone($libraryLink);
				}
			}
			return $this->libraryLinks;
		}elseif ($name == 'libraryTopLinks'){
			if (!isset($this->libraryTopLinks) && $this->libraryId){
				$this->libraryTopLinks = array();
				$libraryLink = new LibraryTopLinks();
				$libraryLink->libraryId = $this->libraryId;
				$libraryLink->orderBy('weight');
				$libraryLink->find();
				while($libraryLink->fetch()){
					$this->libraryTopLinks[$libraryLink->id] = clone($libraryLink);
				}
			}
			return $this->libraryTopLinks;
		}elseif ($name == 'recordsOwned'){
			if (!isset($this->recordsOwned) && $this->libraryId){
				$this->recordsOwned = array();
				$object = new LibraryRecordOwned();
				$object->libraryId = $this->libraryId;
				$object->find();
				while($object->fetch()){
					$this->recordsOwned[$object->id] = clone($object);
				}
			}
			return $this->recordsOwned;
		}elseif ($name == 'recordsToInclude'){
			if (!isset($this->recordsToInclude) && $this->libraryId){
				$this->recordsToInclude = array();
				$object = new LibraryRecordToInclude();
				$object->libraryId = $this->libraryId;
				$object->orderBy('weight');
				$object->find();
				while($object->fetch()){
					$this->recordsToInclude[$object->id] = clone($object);
				}
			}
			return $this->recordsToInclude;
		}elseif ($name == 'sideLoadScopes'){
			if (!isset($this->sideLoadScopes) && $this->libraryId){
				$this->sideLoadScopes = array();
				$object = new LibrarySideLoadScope();
				$object->libraryId = $this->libraryId;
				$object->find();
				while($object->fetch()){
					$this->sideLoadScopes[$object->id] = clone($object);
				}
			}
			return $this->sideLoadScopes;
		} elseif ($name == 'materialsRequestFieldsToDisplay') {
			if (!isset($this->materialsRequestFieldsToDisplay) && $this->libraryId) {
				$this->materialsRequestFieldsToDisplay = array();
				$materialsRequestFieldsToDisplay = new MaterialsRequestFieldsToDisplay();
				$materialsRequestFieldsToDisplay->libraryId = $this->libraryId;
				$materialsRequestFieldsToDisplay->orderBy('weight');
				if ($materialsRequestFieldsToDisplay->find()) {
					while ($materialsRequestFieldsToDisplay->fetch()) {
						$this->materialsRequestFieldsToDisplay[$materialsRequestFieldsToDisplay->id] = clone $materialsRequestFieldsToDisplay;
					}
				}
				return $this->materialsRequestFieldsToDisplay;
			}
		} elseif ($name == 'materialsRequestFormats') {
			if (!isset($this->materialsRequestFormats) && $this->libraryId) {
				$this->materialsRequestFormats = array();
				$materialsRequestFormats = new MaterialsRequestFormats();
				$materialsRequestFormats->libraryId = $this->libraryId;
				$materialsRequestFormats->orderBy('weight');
				if ($materialsRequestFormats->find()) {
					while ($materialsRequestFormats->fetch()) {
						$this->materialsRequestFormats[$materialsRequestFormats->id] = clone $materialsRequestFormats;
					}
				}
				return $this->materialsRequestFormats;
			}
		} elseif ($name == 'materialsRequestFormFields') {
			if (!isset($this->materialsRequestFormFields) && $this->libraryId) {
				$this->materialsRequestFormFields = array();
				$materialsRequestFormFields = new MaterialsRequestFormFields();
				$materialsRequestFormFields->libraryId = $this->libraryId;
				$materialsRequestFormFields->orderBy('weight');
				if ($materialsRequestFormFields->find()) {
					while ($materialsRequestFormFields->fetch()) {
						$this->materialsRequestFormFields[$materialsRequestFormFields->id] = clone $materialsRequestFormFields;
					}
				}
				return $this->materialsRequestFormFields;
			}
		} elseif ($name == 'exploreMoreBar') {
			if (!isset($this->exploreMoreBar) && $this->libraryId) {
				$this->exploreMoreBar = array();
				$exploreMoreBar = new ArchiveExploreMoreBar();
				$exploreMoreBar->libraryId = $this->libraryId;
				$exploreMoreBar->orderBy('weight');
				if ($exploreMoreBar->find()) {
					while ($exploreMoreBar->fetch()) {
						$this->exploreMoreBar[$exploreMoreBar->id] = clone $exploreMoreBar;
					}
				}
				return $this->exploreMoreBar;
			}
		} elseif ($name == 'combinedResultSections') {
			if (!isset($this->combinedResultSections) && $this->libraryId) {
				$this->combinedResultSections = array();
				$combinedResultSection = new LibraryCombinedResultSection();
				$combinedResultSection->libraryId = $this->libraryId;
				$combinedResultSection->orderBy('weight');
				if ($combinedResultSection->find()) {
					while ($combinedResultSection->fetch()) {
						$this->combinedResultSections[$combinedResultSection->id] = clone $combinedResultSection;
					}
				}
				return $this->combinedResultSections;
			}
		} elseif ($name == 'patronNameDisplayStyle') {
			return $this->patronNameDisplayStyle;
		} else {
			return $this->_data[$name];
		}
		return null;
	}

	public function __set($name, $value){
		if ($name == "holidays") {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->holidays = $value;
		}elseif ($name == "archiveMoreDetailsOptions") {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->archiveMoreDetailsOptions = $value;
		}elseif ($name == "archiveSearchFacets") {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->archiveSearchFacets = $value;
		}elseif ($name == 'libraryLinks'){
			/** @noinspection PhpUndefinedFieldInspection */
			$this->libraryLinks = $value;
		}elseif ($name == 'recordsOwned'){
			/** @noinspection PhpUndefinedFieldInspection */
			$this->recordsOwned = $value;
		}elseif ($name == 'recordsToInclude'){
			/** @noinspection PhpUndefinedFieldInspection */
			$this->recordsToInclude = $value;
		}elseif ($name == 'sideLoadScopes'){
			/** @noinspection PhpUndefinedFieldInspection */
			$this->sideLoadScopes = $value;
		}elseif ($name == 'libraryTopLinks'){
			/** @noinspection PhpUndefinedFieldInspection */
			$this->libraryTopLinks = $value;
		}elseif ($name == 'materialsRequestFieldsToDisplay') {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->materialsRequestFieldsToDisplay = $value;
		}elseif ($name == 'materialsRequestFormats') {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->materialsRequestFormats = $value;
		}elseif ($name == 'materialsRequestFormFields') {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->materialsRequestFormFields = $value;
		}elseif ($name == 'exploreMoreBar') {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->exploreMoreBar = $value;
		}elseif ($name == 'combinedResultSections') {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->combinedResultSections = $value;
		}elseif ($name == 'patronNameDisplayStyle'){
			if ($this->patronNameDisplayStyle != $value){
				$this->patronNameDisplayStyle = $value;
				if (!$this->__fetchingFromDB) {
					$this->_patronNameDisplayStyleChanged = true;
				}
			}
		}else{
			$this->_data[$name] = $value;
		}
	}

	/**
	 * Override the update functionality to save related objects
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update(){
		//Updates to properly update settings based on the ILS
		global $configArray;
		$ils = $configArray['Catalog']['ils'];
		if ($ils == 'Koha') {
			$this->showWorkPhoneInProfile = 0;
			$this->treatPrintNoticesAsPhoneNotices = 0;
			$this->showNoticeTypeInProfile = 0;
			$this->addSMSIndicatorToPhone = 0;
		}
		$ret = parent::update();
		if ($ret !== FALSE ){
			$this->saveHolidays();
			$this->saveArchiveSearchFacets();
			$this->saveRecordsOwned();
			$this->saveRecordsToInclude();
			$this->saveSideLoadScopes();
			$this->saveMaterialsRequestFieldsToDisplay();
			$this->saveMaterialsRequestFormFields();
			$this->saveLibraryLinks();
			$this->saveLibraryTopLinks();
			$this->saveArchiveMoreDetailsOptions();
			$this->saveExploreMoreBar();
			$this->saveCombinedResultSections();
		}
		if ($this->_patronNameDisplayStyleChanged){
			$libraryLocations = new Location();
			$libraryLocations->libraryId = $this->libraryId;
			$libraryLocations->find();
			while ($libraryLocations->fetch()){
				$user = new User();
				$user->query("update user set displayName = '' where homeLocationId = {$libraryLocations->locationId}");
			}
		}
		// Do this last so that everything else can update even if we get an error here
		$deleteCheck = $this->saveMaterialsRequestFormats();
		if ($deleteCheck instanceof AspenError) {
			$ret = false;
		};

		return $ret;
	}

	/**
	 * Override the insert functionality to save the related objects
	 *
	 * @see DB/DB_DataObject::insert()
	 */
	public function insert(){
		$ret = parent::insert();
		if ($ret !== FALSE ){
			$this->saveHolidays();
			$this->saveArchiveSearchFacets();
			$this->saveRecordsOwned();
			$this->saveRecordsToInclude();
			$this->saveSideLoadScopes();
			$this->saveMaterialsRequestFieldsToDisplay();
			$this->saveMaterialsRequestFormats();
			$this->saveMaterialsRequestFormFields();
			$this->saveLibraryLinks();
			$this->saveLibraryTopLinks();
			$this->saveExploreMoreBar();
			$this->saveCombinedResultSections();
		}
		return $ret;
	}

	public function saveLibraryLinks(){
		if (isset ($this->libraryLinks) && is_array($this->libraryLinks)){
			$this->saveOneToManyOptions($this->libraryLinks, 'libraryId');
			unset($this->libraryLinks);
		}
	}

	public function saveLibraryTopLinks(){
		if (isset ($this->libraryTopLinks) && is_array($this->libraryTopLinks)){
			$this->saveOneToManyOptions($this->libraryTopLinks, 'libraryId');
			unset($this->libraryTopLinks);
		}
	}

	public function saveRecordsOwned(){
		if (isset ($this->recordsOwned) && is_array($this->recordsOwned)){
			$this->saveOneToManyOptions($this->recordsOwned, 'libraryId');
			unset($this->recordsOwned);
		}
	}

	public function saveRecordsToInclude(){
		if (isset ($this->recordsToInclude) && is_array($this->recordsToInclude)){
			$this->saveOneToManyOptions($this->recordsToInclude, 'libraryId');
			unset($this->recordsToInclude);
		}
	}

	public function saveSideLoadScopes(){
		if (isset ($this->sideLoadScopes) && is_array($this->sideLoadScopes)){
			$this->saveOneToManyOptions($this->sideLoadScopes, 'libraryId');
			unset($this->sideLoadScopes);
		}
	}

	public function saveMaterialsRequestFieldsToDisplay(){
		if (isset ($this->materialsRequestFieldsToDisplay) && is_array($this->materialsRequestFieldsToDisplay)){
			$this->saveOneToManyOptions($this->materialsRequestFieldsToDisplay, 'libraryId');
			unset($this->materialsRequestFieldsToDisplay);
		}
	}

	public function saveMaterialsRequestFormats(){
		if (isset ($this->materialsRequestFormats) && is_array($this->materialsRequestFormats)){
			/** @var MaterialsRequestFormats $object */
			foreach ($this->materialsRequestFormats as $object){
				if (isset($object->deleteOnSave) && $object->deleteOnSave == true){
					$deleteCheck = $object->delete();
					if (!$deleteCheck) {
						$errorString = 'Materials Request(s) are present for the format "' . $object->format . '".';
						$error = new AspenError($errorString);
						return $error;
					}
				}else{
					if (isset($object->id) && is_numeric($object->id)){ // (negative ids need processed with insert)
						$object->update();
					}else{
						$object->libraryId = $this->libraryId;
						$object->insert();
					}
				}
			}
			unset($this->materialsRequestFormats);
		}
		return true;
	}

	public function saveMaterialsRequestFormFields(){
		if (isset ($this->materialsRequestFormFields) && is_array($this->materialsRequestFormFields)){
			$this->saveOneToManyOptions($this->materialsRequestFormFields, 'libraryId');
			unset($this->materialsRequestFormFields);
		}
	}

	private function saveExploreMoreBar() {
		if (isset ($this->exploreMoreBar) && is_array($this->exploreMoreBar)){
			$this->saveOneToManyOptions($this->exploreMoreBar, 'libraryId');
			unset($this->exploreMoreBar);
		}
	}

	public function clearExploreMoreBar(){
		$this->clearOneToManyOptions('ArchiveExploreMoreBar', 'libraryId');
		/** @noinspection PhpUndefinedFieldInspection */
		$this->exploreMoreBar = array();
	}

	public function saveArchiveMoreDetailsOptions(){
		if (isset ($this->archiveMoreDetailsOptions) && is_array($this->archiveMoreDetailsOptions)){
			$this->saveOneToManyOptions($this->archiveMoreDetailsOptions, 'libraryId');
			unset($this->archiveMoreDetailsOptions);
		}
	}

	public function clearArchiveMoreDetailsOptions(){
		$this->clearOneToManyOptions('LibraryArchiveMoreDetails', 'libraryId');
		/** @noinspection PhpUndefinedFieldInspection */
		$this->archiveMoreDetailsOptions = array();
	}

	public function clearMaterialsRequestFormFields(){
		$this->clearOneToManyOptions('MaterialsRequestFormFields', 'libraryId');
		/** @noinspection PhpUndefinedFieldInspection */
		$this->materialsRequestFormFields = array();
	}

	public function clearMaterialsRequestFormats(){
		$this->clearOneToManyOptions('MaterialsRequestFormats', 'libraryId');
		/** @noinspection PhpUndefinedFieldInspection */
		$this->materialsRequestFormats = array();
	}

	public function saveArchiveSearchFacets(){
		if (isset ($this->archiveSearchFacets) && is_array($this->archiveSearchFacets)){
			$this->saveOneToManyOptions($this->archiveSearchFacets, 'libraryId');
			unset($this->archiveSearchFacets);
		}
	}

	public function clearArchiveSearchFacets(){
		$this->clearOneToManyOptions('LibraryArchiveSearchFacetSetting', 'libraryId');
		/** @noinspection PhpUndefinedFieldInspection */
		$this->archiveSearchfacets = array();
	}

	public function saveCombinedResultSections(){
		if (isset ($this->combinedResultSections) && is_array($this->combinedResultSections)){
			$this->saveOneToManyOptions($this->combinedResultSections, 'libraryId');
			unset($this->combinedResultSections);
		}
	}

	public function saveHolidays(){
		if (isset ($this->holidays) && is_array($this->holidays)){
			$this->saveOneToManyOptions($this->holidays, 'libraryId');
			unset($this->holidays);
		}
	}

	static function getDefaultArchiveSearchFacets($libraryId = -1) {
		$defaultFacets = array();
		$defaultFacetsList = LibraryArchiveSearchFacetSetting::$defaultFacetList;
		foreach ($defaultFacetsList as $facetName => $facetDisplayName){
			$facet = new LibraryArchiveSearchFacetSetting();
			$facet->setupSideFacet($facetName, $facetDisplayName, false);
			$facet->libraryId = $libraryId;
			$facet->collapseByDefault = true;
			$facet->weight = count($defaultFacets) + 1;
			$defaultFacets[] = $facet;
		}

		return $defaultFacets;
	}

	public function getNumLocationsForLibrary(){
		$location = new Location;
		$location->libraryId = $this->libraryId;
		return $location->count();
	}

	public function getArchiveRequestFormStructure() {
		$defaultForm = ArchiveRequest::getObjectStructure();
		foreach ($defaultForm as $index => &$formField) {
			$libraryPropertyName = 'archiveRequestField' . ucfirst($formField['property']);
			if (isset($this->$libraryPropertyName)) {
				$setting = is_null($this->$libraryPropertyName) ? $formField['default'] : $this->$libraryPropertyName;
				switch ($setting) {
					case 0:
						//unset field
						unset($defaultForm[$index]);
						break;
					case 1:
						// set field as optional
						$formField['required'] = false;
						break;
					case 2:
						// set field as required
						$formField['required'] = true;
						break;
				}

			}
		}
		return $defaultForm;
	}

	protected $_browseCategoryGroup = null;
	public function getBrowseCategoryGroup(){
		if ($this->_browseCategoryGroup == null){
			require_once ROOT_DIR . '/sys/Browse/BrowseCategoryGroup.php';
			$browseCategoryGroup = new BrowseCategoryGroup();
			$browseCategoryGroup->id = $this->browseCategoryGroupId;
			if ($browseCategoryGroup->find(true)){
				$this->_browseCategoryGroup = $browseCategoryGroup;
			}
		}
		return $this->_browseCategoryGroup;
	}

	protected $_groupedWorkDisplaySettings = null;
	/** @return GroupedWorkDisplaySetting */
	public function getGroupedWorkDisplaySettings()
	{
		if ($this->_groupedWorkDisplaySettings == null){
			try {
				require_once ROOT_DIR . '/sys/Grouping/GroupedWorkDisplaySetting.php';
				$groupedWorkDisplaySettings = new GroupedWorkDisplaySetting();
				$groupedWorkDisplaySettings->id = $this->groupedWorkDisplaySettingId;
				if ($groupedWorkDisplaySettings->find(true)){
					$this->_groupedWorkDisplaySettings = $groupedWorkDisplaySettings;
				}else{
					$this->_groupedWorkDisplaySettings = GroupedWorkDisplaySetting::getDefaultDisplaySettings();
				}

			}catch(Exception $e){
				global $logger;
				$logger->log('Error loading grouped work display settings ' . $e, Logger::LOG_ERROR);
				$this->_groupedWorkDisplaySettings = GroupedWorkDisplaySetting::getDefaultDisplaySettings();
			}
		}
		return $this->_groupedWorkDisplaySettings;
	}

	protected $_layoutSettings = null;
	/** @return LayoutSetting */
	public function getLayoutSettings()
	{
		if ($this->_layoutSettings == null){
			try {
				require_once ROOT_DIR . '/sys/Theming/LayoutSetting.php';
				$this->_layoutSettings = new LayoutSetting();
				$this->_layoutSettings->id = $this->layoutSettingId;
				$this->_layoutSettings->find(true);
			}catch(Exception $e){
				global $logger;
				$logger->log('Error loading grouped work display settings ' . $e, Logger::LOG_ERROR);
			}
		}
		return $this->_layoutSettings;
	}

	function getEditLink(){
		return '/Admin/Libraries?objectAction=edit&id=' . $this->libraryId;
	}

	/**
	 * @return array
	 */
	static function getLibraryList(): array
	{
		$library = new Library();
		$library->orderBy('displayName');
		if (UserAccount::userHasRole('libraryAdmin')) {
			$homeLibrary = Library::getPatronHomeLibrary();
			$library->libraryId = $homeLibrary->libraryId;
		}
		$library->find();
		$libraryList = [];
		while ($library->fetch()) {
			$libraryList[$library->libraryId] = $library->displayName;
		}
		return $libraryList;
	}

	/** @var OverDriveScope */
	private $_overdriveScope = null;
	public function getOverdriveScope()
	{
		if ($this->_overdriveScope == null && $this->overDriveScopeId > 0){
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveScope.php';
			$this->_overdriveScope = new OverDriveScope();
			$this->_overdriveScope->id = $this->overDriveScopeId;
			$this->_overdriveScope->find(true);
		}
		return $this->_overdriveScope;
	}
}
