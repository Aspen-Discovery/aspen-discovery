<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
require_once ROOT_DIR . '/sys/LibraryLocation/Holiday.php';
require_once ROOT_DIR . '/sys/LibraryLocation/LibraryFacetSetting.php';
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
require_once ROOT_DIR . '/sys/LibraryLocation/LibraryLink.php';
if (file_exists(ROOT_DIR . '/sys/MaterialsRequestFieldsToDisplay.php')) {
	require_once ROOT_DIR . '/sys/MaterialsRequestFieldsToDisplay.php';
}
if (file_exists(ROOT_DIR . '/sys/MaterialsRequestFormats.php')) {
	require_once ROOT_DIR . '/sys/MaterialsRequestFormats.php';
}
if (file_exists(ROOT_DIR . '/sys/MaterialsRequestFormFields.php')) {
	require_once ROOT_DIR . '/sys/MaterialsRequestFormFields.php';
}
if (file_exists(ROOT_DIR . '/sys/CloudLibrary/LibraryCloudLibraryScope.php')) {
	require_once ROOT_DIR . '/sys/CloudLibrary/LibraryCloudLibraryScope.php';
}
if (file_exists(ROOT_DIR . '/sys/AspenLiDA/QuickSearchSetting.php')) {
	require_once ROOT_DIR . '/sys/AspenLiDA/QuickSearchSetting.php';
}
if (file_exists(ROOT_DIR . '/sys/AspenLiDA/NotificationSetting.php')) {
	require_once ROOT_DIR . '/sys/AspenLiDA/NotificationSetting.php';
}

require_once ROOT_DIR . '/sys/CurlWrapper.php';

class Library extends DataObject {
	public $__table = 'library';    // table name
	public $__primaryKey = 'libraryId';
	public $__displayNameColumn = 'displayName';
	//Basic configuration
	public $isDefault;
	public $libraryId;                //int(11)
	public $subdomain;                //varchar(15)
	public $baseUrl;
	public $isConsortialCatalog;

	//Display information specific to the library
	public $displayName;            //varchar(50)
	public $createSearchInterface;
	public $showInSelectInterface;
	public $showDisplayNameInHeader;
	public $headerText;
	public $footerText;
	public $systemMessage;

	public $generateSitemap;

	//More general display configurations
	public $theme;
	public $layoutSettingId;  //Link to LayoutSetting
	public $groupedWorkDisplaySettingId; //Link to GroupedWorkDisplaySettings

	public $browseCategoryGroupId;

	public $restrictSearchByLibrary;

	//For Millennium and Sierra
	public $scope;                    //smallint(6)
	public $useScope;                //tinyint(4)

	//Account integration settings
	public $ilsCode;
	public $workstationId;
	public $allowProfileUpdates;   //tinyint(4)
	public $allowHomeLibraryUpdates;
	public $allowUsernameUpdates;
	public $showMessagingSettings;
	public $allowFreezeHolds;   //tinyint(4)
	public $maxDaysToFreeze;
	public $showHoldButton;
	public $showHoldButtonInSearchResults;
	public $showHoldButtonForUnavailableOnly;
	public $allowRememberPickupLocation;
	public $treatBibOrItemHoldsAs;
	public $showVolumesWithLocalCopiesFirst;
	public $showLoginButton;
	public $showEmailThis;
	public $showFavorites;
	public $enableListDescriptions;
	public $allowableListNames;
	public $showConvertListsFromClassic;
	public $showUserCirculationModules;
	public $showUserPreferences;
	public $showUserContactInformation;
	public $inSystemPickupsOnly;
	public $validPickupSystems;
	/** @noinspection PhpUnused */
	public $pTypes; //This is used as part of the indexing process
	public $facetLabel;
	public $showAvailableAtAnyLocation;
	public $finePaymentType; //0 = None, 1 = ILS, 2 = PayPal
	public $finesToPay;
	public $finePaymentOrder;
	public $payFinesLink;
	public $payFinesLinkText;
	public $minimumFineAmount;
	public $showRefreshAccountButton;    // specifically to refresh account after paying fines online
	public $msbUrl;
	public $symphonyPaymentType;
	public $compriseSettingId;
	public $payPalSettingId;
	public $proPaySettingId;
	public $worldPaySettingId;
	public $xpressPaySettingId;
	public $aciSpeedpaySettingId;
	public $invoiceCloudSettingId;

	public /** @noinspection PhpUnused */
		$repeatSearchOption;
	public /** @noinspection PhpUnused */
		$repeatInOnlineCollection;
	public /** @noinspection PhpUnused */
		$repeatInProspector;
	public /** @noinspection PhpUnused */
		$repeatInWorldCat;
	public $overDriveScopeId;

	public $hooplaLibraryID;
	public /** @noinspection PhpUnused */
		$hooplaScopeId;
	public /** @noinspection PhpUnused */
		$axis360ScopeId;
	public /** @noinspection PhpUnused */
		$systemsToRepeatIn;
	public $additionalLocationsToShowAvailabilityFor;
	public $homeLink;
	public $showAdvancedSearchbox;
	public $enableProspectorIntegration;
	public /** @noinspection PhpUnused */
		$showProspectorResultsAtEndOfSearch;
	public /** @noinspection PhpUnused */
		$prospectorCode;
	public /** @noinspection PhpUnused */
		$enableGenealogy;
	public $showHoldCancelDate;
	public $showLogMeOutAfterPlacingHolds;
	public $displayItemBarcode;
	public $displayHoldsOnCheckout;
	public $enableSelfRegistration;
	public $selfRegistrationPasswordNotes;
	public $selfRegistrationUrl;
	public $selfRegistrationLocationRestrictions;
	public $promptForBirthDateInSelfReg;
	public $promptForParentInSelfReg;
	public $promptForSMSNoticesInSelfReg;
	public $selfRegRequirePhone;
	public $selfRegRequireEmail;
	public $showItsHere;
	public $holdDisclaimer;
	public $availableHoldDelay;
	public $holdPlacedAt;
	public $holdRange;
	public $systemHoldNote;
	public $systemHoldNoteMasquerade;
	public $enableMaterialsRequest;
	public $displayMaterialsRequestToPublic;
	public $allowDeletingILSRequests;
	public $externalMaterialsRequestUrl;
	public /** @noinspection PhpUnused */
		$eContentLinkRules;
	public /** @noinspection PhpUnused */
		$includeNovelistEnrichment;
	public /** @noinspection PhpUnused */
		$allowAutomaticSearchReplacements;

	public /** @noinspection PhpUnused */
		$worldCatUrl;
	public /** @noinspection PhpUnused */
		$worldCatQt;
	public /** @noinspection PhpUnused */
		$showGoDeeper;
	public $defaultNotNeededAfterDays;

	public /** @noinspection PhpUnused */
		$publicListsToInclude;
	public /** @noinspection PhpUnused */
		$showWikipediaContent;
	public $showCitationStyleGuides;
	public $restrictOwningBranchesAndSystems;
	public $allowNameUpdates;
	public $allowDateOfBirthUpdates;
	public $allowPatronAddressUpdates;
	public $cityStateField;
	public $allowPatronPhoneNumberUpdates;
	public $useAllCapsWhenUpdatingProfile;
	public $requireNumericPhoneNumbersWhenUpdatingProfile;
	public $bypassReviewQueueWhenUpdatingProfile;
	public $showWorkPhoneInProfile;
	public $showNoticeTypeInProfile;
	public $allowPickupLocationUpdates;
	public $showAlternateLibraryOptionsInProfile;
	public $additionalCss;
	public $maxRequestsPerYear;
	public $maxOpenRequests;
	// Contact Links //
	public $twitterLink;
	public $facebookLink;
	public $youtubeLink;
	public $instagramLink;
	public $pinterestLink;
	public $goodreadsLink;
	public $tiktokLink;
	public $generalContactLink;
	public $contactEmail;

	public $allowPinReset;
	public $minPinLength;
	public $maxPinLength;
	public $onlyDigitsAllowedInPin;
	public $enableForgotPasswordLink;
	public /** @noinspection PhpUnused */
		$preventExpiredCardLogin;
	public /** @noinspection PhpUnused */
		$showLibraryHoursNoticeOnAccountPages;
	public $showShareOnExternalSites;
	public /** @noinspection PhpUnused */
		$barcodePrefix;
	public $libraryCardBarcodeStyle;
	public /** @noinspection PhpUnused */
		$minBarcodeLength;
	public /** @noinspection PhpUnused */
		$maxBarcodeLength;

	public $showAlternateLibraryCard;
	public $alternateLibraryCardStyle;
	public $showAlternateLibraryCardPassword;
	public $alternateLibraryCardLabel;
	public $alternateLibraryCardPasswordLabel;

	public $econtentLocationsToInclude;
	public $showCardExpirationDate;
	public $showExpirationWarnings;
	public /** @noinspection PhpUnused */
		$loginFormUsernameLabel;
	public $loginFormPasswordLabel;
	public $loginNotes;
	public $allowLoginToPatronsOfThisLibraryOnly;
	public $messageForPatronsOfOtherLibraries;
	public $preventLogin;
	public $preventLoginMessage;

	public /** @noinspection PhpUnused */
		$includeDplaResults;
	public $showWhileYouWait;

	public $useAllCapsWhenSubmittingSelfRegistration;
	public $validSelfRegistrationStates;
	public $validSelfRegistrationZipCodes;
	public /** @noinspection PhpUnused */
		$selfRegistrationFormMessage;
	public /** @noinspection PhpUnused */
		$selfRegistrationSuccessMessage;
	public /** @noinspection PhpUnused */
		$selfRegistrationTemplate;
	public $selfRegistrationUserProfile;
	public $addSMSIndicatorToPhone;

	public $allowLinkedAccounts;

	public $maxFinesToAllowAccountUpdates;

	public $patronNameDisplayStyle;
	private $_patronNameDisplayStyleChanged = false; //Track changes so we can clear values for existing patrons
	public $alwaysShowSearchResultsMainDetails;
	public /** @noinspection PhpUnused */
		$casHost;
	public /** @noinspection PhpUnused */
		$casPort;
	public /** @noinspection PhpUnused */
		$casContext;
	public /** @noinspection PhpUnused */
		$masqueradeAutomaticTimeoutLength;
	public $allowMasqueradeMode;
	public $allowReadingHistoryDisplayInMasqueradeMode;
	public $enableReadingHistory;
	public $enableSavedSearches;
	public /** @noinspection PhpUnused */
		$newMaterialsRequestSummary;  // (Text at the top of the Materials Request Form.)
	public /** @noinspection PhpUnused */
		$materialsRequestDaysToPreserve;
	public $showGroupedHoldCopiesCount;
	public $interLibraryLoanName;
	public $interLibraryLoanUrl;
	public $expiredMessage;
	public $expirationNearMessage;
	public $showOnOrderCounts;

	//Notes
	public $showOpacNotes;
	public $showBorrowerMessages;
	public $showDebarmentNotes;

	//EBSCO Settings
	public $edsSettingsId;
	public $ebscohostSearchSettingId;

	//SSO
	public /** @noinspection PhpUnused */
		$ssoName;
	public /** @noinspection PhpUnused */
		$ssoXmlUrl;
	public /** @noinspection PhpUnused */
		$ssoMetadataFilename;
	public /** @noinspection PhpUnused */
		$ssoEntityId;
	public /** @noinspection PhpUnused */
		$ssoUniqueAttribute;
	public /** @noinspection PhpUnused */
		$ssoIdAttr;
	public /** @noinspection PhpUnused */
		$ssoUsernameAttr;
	public /** @noinspection PhpUnused */
		$ssoFirstnameAttr;
	public /** @noinspection PhpUnused */
		$ssoLastnameAttr;
	public /** @noinspection PhpUnused */
		$ssoEmailAttr;
	public /** @noinspection PhpUnused */
		$ssoDisplayNameAttr;
	public /** @noinspection PhpUnused */
		$ssoPhoneAttr;
	public /** @noinspection PhpUnused */
		$ssoPatronTypeAttr;
	public /** @noinspection PhpUnused */
		$ssoPatronTypeFallback;
	public /** @noinspection PhpUnused */
		$ssoAddressAttr;
	public /** @noinspection PhpUnused */
		$ssoCityAttr;
	public /** @noinspection PhpUnused */
		$ssoLibraryIdAttr;
	public /** @noinspection PhpUnused */
		$ssoLibraryIdFallback;
	public /** @noinspection PhpUnused */
		$ssoCategoryIdAttr;
	public /** @noinspection PhpUnused */
		$ssoCategoryIdFallback;

	//Combined Results (Bento Box)
	public /** @noinspection PhpUnused */
		$enableCombinedResults;
	public /** @noinspection PhpUnused */
		$combinedResultsLabel;
	public /** @noinspection PhpUnused */
		$defaultToCombinedResults;

	//OAI
	public $enableOpenArchives;

	//Web Builder
	public $enableWebBuilder;

	//Donations
	public $donationSettingId;
	public $enableDonations;

	//Course Reserves
	public /** @noinspection PhpUnused */
		$enableCourseReserves;
	public $courseReserveLibrariesToInclude;

	//Curbside Pickup
	public $curbsidePickupSettingId;

	//2FA settings ID
	public $twoFactorAuthSettingId;

	//SSO
	public $ssoSettingId;

	public $defaultRememberMe;

	//LiDA settings
	public $lidaNotificationSettingId;
	public $lidaQuickSearchId;

	private $_cloudLibraryScopes;
	private $_libraryLinks;

	public function getNumericColumnNames(): array {
		return [
			'compriseSettingId',
			'proPaySettingId',
			'worldPaySettingId',
			'payPalSettingId',
			'ebscohostSearchSettingId',
			'invoiceCloudSettingId'
		];
	}

	static function getObjectStructure($context = ''): array {
		// get the structure for the library system's holidays
		$holidaysStructure = Holiday::getObjectStructure($context);

		// we don't want to make the libraryId property editable
		// because it is associated with this library system only
		unset($holidaysStructure['libraryId']);

		$libraryLinksStructure = LibraryLink::getObjectStructure($context);
		unset($libraryLinksStructure['weight']);
		unset($libraryLinksStructure['libraryId']);

		$libraryRecordOwnedStructure = LibraryRecordOwned::getObjectStructure($context);
		unset($libraryRecordOwnedStructure['libraryId']);

		$libraryRecordToIncludeStructure = LibraryRecordToInclude::getObjectStructure($context);
		unset($libraryRecordToIncludeStructure['libraryId']);
		unset($libraryRecordToIncludeStructure['weight']);

		$librarySideLoadScopeStructure = LibrarySideLoadScope::getObjectStructure($context);
		unset($librarySideLoadScopeStructure['libraryId']);

		$manageMaterialsRequestFieldsToDisplayStructure = MaterialsRequestFieldsToDisplay::getObjectStructure($context);
		unset($manageMaterialsRequestFieldsToDisplayStructure['libraryId']); //needed?
		unset($manageMaterialsRequestFieldsToDisplayStructure['weight']);

		$materialsRequestFormatsStructure = MaterialsRequestFormats::getObjectStructure($context);
		unset($materialsRequestFormatsStructure['libraryId']); //needed?
		unset($materialsRequestFormatsStructure['weight']);

		$materialsRequestFormFieldsStructure = MaterialsRequestFormFields::getObjectStructure($context);
		unset($materialsRequestFormFieldsStructure['libraryId']); //needed?
		unset($materialsRequestFormFieldsStructure['weight']);

		$combinedResultsStructure = LibraryCombinedResultSection::getObjectStructure($context);
		unset($combinedResultsStructure['libraryId']);
		unset($combinedResultsStructure['weight']);

		require_once ROOT_DIR . '/sys/Theming/Theme.php';
		$theme = new Theme();
		$availableThemes = [];
		$theme->orderBy('themeName');
		$theme->find();
		while ($theme->fetch()) {
			$availableThemes[$theme->id] = $theme->themeName;
		}

		$materialsRequestOptions = [
			0 => 'None',
			1 => 'Aspen Request System',
			2 => 'ILS Request System',
			3 => 'External Request Link',
		];
		$catalog = CatalogFactory::getCatalogConnectionInstance();
		if ($catalog == null || !$catalog->hasMaterialsRequestSupport()) {
			unset($materialsRequestOptions[2]);
		}

		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkDisplaySetting.php';
		$groupedWorkDisplaySetting = new GroupedWorkDisplaySetting();
		$groupedWorkDisplaySetting->orderBy('name');
		$groupedWorkDisplaySettings = [];
		$groupedWorkDisplaySetting->find();
		$defaultSettingId = '';
		while ($groupedWorkDisplaySetting->fetch()) {
			if ($groupedWorkDisplaySetting->isDefault) {
				$defaultSettingId = $groupedWorkDisplaySetting->id;
			}
			$groupedWorkDisplaySettings[$groupedWorkDisplaySetting->id] = $groupedWorkDisplaySetting->name;
		}

		require_once ROOT_DIR . '/sys/Browse/BrowseCategoryGroup.php';
		$browseCategoryGroup = new BrowseCategoryGroup();
		$browseCategoryGroups = [];
		$browseCategoryGroup->orderBy('name');
		$browseCategoryGroup->find();
		while ($browseCategoryGroup->fetch()) {
			$browseCategoryGroups[$browseCategoryGroup->id] = $browseCategoryGroup->name;
		}

		require_once ROOT_DIR . '/sys/Theming/LayoutSetting.php';
		$layoutSetting = new LayoutSetting();
		$layoutSetting->orderBy('name');
		$layoutSettings = [];
		$layoutSetting->find();
		while ($layoutSetting->fetch()) {
			$layoutSettings[$layoutSetting->id] = $layoutSetting->name;
		}

		require_once ROOT_DIR . '/sys/ECommerce/CompriseSetting.php';
		$compriseSetting = new CompriseSetting();
		$compriseSetting->orderBy('customerName');
		$compriseSettings = [];
		$compriseSetting->find();
		$compriseSettings[-1] = 'none';
		while ($compriseSetting->fetch()) {
			$compriseSettings[$compriseSetting->id] = $compriseSetting->customerName;
		}

		require_once ROOT_DIR . '/sys/ECommerce/ProPaySetting.php';
		$proPaySetting = new ProPaySetting();
		$proPaySetting->orderBy('name');
		$proPaySettings = [];
		$proPaySetting->find();
		$proPaySettings[-1] = 'none';
		while ($proPaySetting->fetch()) {
			$proPaySettings[$proPaySetting->id] = $proPaySetting->name;
		}

		require_once ROOT_DIR . '/sys/ECommerce/PayPalSetting.php';
		$payPalSetting = new PayPalSetting();
		$payPalSetting->orderBy('name');
		$payPalSettings = [];
		$payPalSetting->find();
		$payPalSettings[-1] = 'none';
		while ($payPalSetting->fetch()) {
			$payPalSettings[$payPalSetting->id] = $payPalSetting->name;
		}

		require_once ROOT_DIR . '/sys/ECommerce/WorldPaySetting.php';
		$worldPaySetting = new WorldPaySetting();
		$worldPaySetting->orderBy('name');
		$worldPaySettings = [];
		$worldPaySetting->find();
		$worldPaySettings[-1] = 'none';
		while ($worldPaySetting->fetch()) {
			$worldPaySettings[$worldPaySetting->id] = $worldPaySetting->name;
		}

		require_once ROOT_DIR . '/sys/ECommerce/XpressPaySetting.php';
		$xpressPaySetting = new XpressPaySetting();
		$xpressPaySetting->orderBy('name');
		$xpressPaySettings = [];
		$xpressPaySetting->find();
		$xpressPaySettings[-1] = 'none';
		while ($xpressPaySetting->fetch()) {
			$xpressPaySettings[$xpressPaySetting->id] = $xpressPaySetting->name;
		}

		require_once ROOT_DIR . '/sys/ECommerce/ACISpeedpaySetting.php';
		$aciSpeedpaySetting = new ACISpeedpaySetting();
		$aciSpeedpaySetting->orderBy('name');
		$aciSpeedpaySettings = [];
		$aciSpeedpaySetting->find();
		$aciSpeedpaySettings[-1] = 'none';
		while ($aciSpeedpaySetting->fetch()) {
			$aciSpeedpaySettings[$aciSpeedpaySetting->id] = $aciSpeedpaySetting->name;
		}

		require_once ROOT_DIR . '/sys/ECommerce/InvoiceCloudSetting.php';
		$invoiceCloudSetting = new InvoiceCloudSetting();
		$invoiceCloudSetting->orderBy('name');
		$invoiceCloudSettings = [];
		$invoiceCloudSetting->find();
		$invoiceCloudSettings[-1] = 'none';
		while ($invoiceCloudSetting->fetch()) {
			$invoiceCloudSettings[$invoiceCloudSetting->id] = $invoiceCloudSetting->name;
		}

		require_once ROOT_DIR . '/sys/Hoopla/HooplaScope.php';
		$hooplaScope = new HooplaScope();
		$hooplaScope->orderBy('name');
		$hooplaScopes = [];
		$hooplaScope->find();
		$hooplaScopes[-1] = 'none';
		while ($hooplaScope->fetch()) {
			$hooplaScopes[$hooplaScope->id] = $hooplaScope->name;
		}

		require_once ROOT_DIR . '/sys/Axis360/Axis360Scope.php';
		$axis360Scope = new Axis360Scope();
		$axis360Scope->orderBy('name');
		$axis360Scopes = [];
		$axis360Scope->find();
		$axis360Scopes[-1] = 'none';
		while ($axis360Scope->fetch()) {
			$axis360Scopes[$axis360Scope->id] = $axis360Scope->name;
		}

		require_once ROOT_DIR . '/sys/Ebsco/EDSSettings.php';
		$edsSetting = new EDSSettings();
		$edsSetting->orderBy('name');
		$edsSettings = [];
		$edsSetting->find();
		$edsSettings[-1] = 'none';
		while ($edsSetting->fetch()) {
			$edsSettings[$edsSetting->id] = $edsSetting->name;
		}


		require_once ROOT_DIR . '/sys/Ebsco/EBSCOhostSetting.php';
		$ebscohostSetting = new EBSCOhostSearchSetting();
		$ebscohostSetting->orderBy('name');
		$ebscohostSettings = [];
		$ebscohostSetting->find();
		$ebscohostSettings[-1] = 'none';
		while ($ebscohostSetting->fetch()) {
			$ebscohostSettings[$ebscohostSetting->id] = $ebscohostSetting->name;
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
		} catch (Exception $e) {
			//OverDrive scopes are likely not defined
		}

		require_once ROOT_DIR . '/sys/AspenLiDA/NotificationSetting.php';
		$notificationSetting = new NotificationSetting();
		$notificationSetting->orderBy('name');
		$notificationSettings = [];
		$notificationSetting->find();
		$notificationSettings[-1] = 'none';
		while ($notificationSetting->fetch()) {
			$notificationSettings[$notificationSetting->id] = $notificationSetting->name;
		}

		require_once ROOT_DIR . '/sys/AspenLiDA/QuickSearchSetting.php';
		$quickSearchSetting = new QuickSearchSetting();
		$quickSearchSetting->orderBy('name');
		$quickSearchSettings = [];
		$quickSearchSetting->find();
		$quickSearchSettings[-1] = 'none';
		while ($quickSearchSetting->fetch()) {
			$quickSearchSettings[$quickSearchSetting->id] = $quickSearchSetting->name;
		}

		$cloudLibraryScopeStructure = LibraryCloudLibraryScope::getObjectStructure($context);
		unset($cloudLibraryScopeStructure['libraryId']);

		$barcodeTypes = [
			'none' => 'Do not show the barcode',
			'CODE128' => 'CODE128 (automatic mode switching)',
			'codabar' => 'CODABAR',
			'CODE128A' => 'CODE128 Mode A',
			'CODE128B' => 'CODE128 Mode B',
			'CODE128C' => 'CODE128 Mode C',
			'CODE39' => 'CODE39',
			'EAN13' => 'EAN-13',
			'EAN8' => 'EAN-8',
			'EAN5' => 'EAN-5',
			'ITF14' => 'ITF 14',
			"MSI" => "MSI",
		];

		$validSelfRegistrationOptions = [
			0 => 'No Self Registration',
			1 => 'ILS Based Self Registration',
			2 => 'Redirect to Self Registration URL',
		];
		require_once ROOT_DIR . '/sys/Enrichment/QuipuECardSetting.php';
		$quipuECardSettings = new QuipuECardSetting();
		if ($quipuECardSettings->find(true)) {
			$validSelfRegistrationOptions[3] = 'Quipu eCARD';
		}


		/** @noinspection HtmlRequiredAltAttribute */
		/** @noinspection RequiredAttributes */
		$structure = [
			'isDefault' => [
				'property' => 'isDefault',
				'type' => 'checkbox',
				'label' => 'Default Library (one per install!)',
				'description' => 'The default library instance for loading scoping information etc',
				'hideInLists' => true,
				'permissions' => ['Library Domain Settings'],
			],
			'libraryId' => [
				'property' => 'libraryId',
				'type' => 'label',
				'label' => 'Library Id',
				'description' => 'The unique id of the library within the database',
				'uniqueProperty' => true,
			],
			'subdomain' => [
				'property' => 'subdomain',
				'type' => 'text',
				'label' => 'Subdomain',
				'description' => 'A unique id to identify the library within the system',
				'uniqueProperty' => true,
				'forcesReindex' => true,
				'required' => true,
				'permissions' => ['Library Domain Settings'],
			],
			'baseUrl' => [
				'property' => 'baseUrl',
				'type' => 'text',
				'label' => 'Base URL',
				'description' => 'The Base URL for the library instance including the protocol (http or https).',
				'permissions' => ['Library Domain Settings'],
				'note' => 'Include <code>http://</code> or <code>https://</code> as appropriate',
			],
			'displayName' => [
				'property' => 'displayName',
				'type' => 'text',
				'label' => 'Display Name',
				'description' => 'A name to identify the library within the system',
				'size' => '40',
				'uniqueProperty' => true,
				'forcesReindex' => true,
				'required' => true,
				'maxLength' => 80,
				'editPermissions' => ['Library Domain Settings'],
			],
			'showDisplayNameInHeader' => [
				'property' => 'showDisplayNameInHeader',
				'type' => 'checkbox',
				'label' => 'Show Display Name in Header',
				'description' => 'Whether or not the display name should be shown in the header next to the logo',
				'hideInLists' => true,
				'default' => false,
				'permissions' => ['Library Theme Configuration'],
			],
			'isConsortialCatalog' => [
				'property' => 'isConsortialCatalog',
				'type' => 'checkbox',
				'label' => 'Consortial Interface?',
				'description' => 'If this library is a consortial view',
				'hideInLists' => true,
				'permissions' => ['Library Domain Settings'],
				'forcesReindex' => true,
			],
			'createSearchInterface' => [
				'property' => 'createSearchInterface',
				'type' => 'checkbox',
				'label' => 'Create Search Interface',
				'description' => 'Whether or not a search interface is created.  Things like lockers and drive through windows do not need search interfaces.',
				'forcesReindex' => true,
				'editPermissions' => ['Library Domain Settings'],
				'default' => true,
			],
			'showInSelectInterface' => [
				'property' => 'showInSelectInterface',
				'type' => 'checkbox',
				'label' => 'Show In Select Interface (requires Create Search Interface)',
				'description' => 'Whether or not this Library will show in the Select Interface Page.',
				'forcesReindex' => false,
				'editPermissions' => ['Library Domain Settings'],
				'default' => true,
			],
			'systemMessage' => [
				'property' => 'systemMessage',
				'type' => 'html',
				'label' => 'System Message',
				'description' => 'A message to be displayed at the top of the screen',
				'size' => '80',
				'maxLength' => '512',
				'allowableTags' => "<p><em><i><strong><b><a><ul><ol><li><h1><h2><h3><h4><h5><h6><h7><pre><code><hr><table><tbody><tr><th><td><caption><img><br><div><span><sub><sup><script>",
				'hideInLists' => true,
				'permissions' => ['Library Theme Configuration'],
			],
			'generateSitemap' => [
				'property' => 'generateSitemap',
				'type' => 'checkbox',
				'label' => 'Generate Sitemap',
				'description' => 'Whether or not a sitemap should be generated for the library.',
				'hideInLists' => true,
				'permissions' => ['Library Domain Settings'],
			],

			// Basic Display //
			'displaySection' => [
				'property' => 'displaySection',
				'type' => 'section',
				'label' => 'Basic Display',
				'hideInLists' => true,
				'properties' => [
					'theme' => [
						'property' => 'theme',
						'type' => 'enum',
						'label' => 'Theme',
						'values' => $availableThemes,
						'description' => 'The theme which should be used for the library',
						'hideInLists' => true,
						'default' => 'default',
						'permissions' => ['Library Theme Configuration'],
					],
					'layoutSettingId' => [
						'property' => 'layoutSettingId',
						'type' => 'enum',
						'values' => $layoutSettings,
						'label' => 'Layout Settings',
						'description' => 'Layout Settings to apply to this interface',
						'permissions' => ['Library Theme Configuration'],
					],
					'homeLink' => [
						'property' => 'homeLink',
						'type' => 'text',
						'label' => 'Home Link',
						'description' => 'The location to send the user when they click on the home button or logo.  Use default or blank to go back to the Aspen Discovery home location.',
						'size' => '40',
						'hideInLists' => true,
						'editPermissions' => ['Library Contact Settings'],
					],
					'additionalCss' => [
						'property' => 'additionalCss',
						'type' => 'textarea',
						'label' => 'Additional CSS',
						'description' => 'Extra CSS to apply to the site.  Will apply to all pages.',
						'hideInLists' => true,
						'permissions' => ['Library Theme Configuration'],
					],
					'headerText' => [
						'property' => 'headerText',
						'type' => 'html',
						'label' => 'Header Text',
						'description' => 'Optional Text to display in the header, between the logo and the log in/out buttons.  Will apply to all pages.',
						'allowableTags' => '<p><em><i><strong><b><a><ul><ol><li><h1><h2><h3><h4><h5><h6><h7><pre><code><hr><table><tbody><tr><th><td><caption><img><br><div><span><sub><sup>',
						'hideInLists' => true,
						'editPermissions' => ['Library Theme Configuration'],
					],
					'footerText' => [
						'property' => 'footerText',
						'type' => 'html',
						'label' => 'Footer Text',
						'description' => 'Optional Text to display in the footer above the footer logo if displayed.  Will apply to all pages.',
						'allowableTags' => '<p><em><i><strong><b><a><ul><ol><li><h1><h2><h3><h4><h5><h6><h7><pre><code><hr><table><tbody><tr><th><td><caption><img><br><div><span><sub><sup>',
						'hideInLists' => true,
						'editPermissions' => ['Library Theme Configuration'],
					],
				],
			],

			// Contact Links //
			'contactSection' => [
				'property' => 'contact',
				'type' => 'section',
				'label' => 'Contact Links',
				'hideInLists' => true,
				'permissions' => ['Library Contact Settings'],
				'properties' => [
					'facebookLink' => [
						'property' => 'facebookLink',
						'type' => 'text',
						'label' => 'Facebook Link Url',
						'description' => 'The url to Facebook (leave blank if the library does not have a Facebook account',
						'size' => '40',
						'maxLength' => 255,
						'hideInLists' => true,
					],
					'twitterLink' => [
						'property' => 'twitterLink',
						'type' => 'text',
						'label' => 'Twitter Link Url',
						'description' => 'The url to Twitter (leave blank if the library does not have a Twitter account',
						'size' => '40',
						'maxLength' => 255,
						'hideInLists' => true,
					],
					'youtubeLink' => [
						'property' => 'youtubeLink',
						'type' => 'text',
						'label' => 'Youtube Link Url',
						'description' => 'The url to Youtube (leave blank if the library does not have a Youtube account',
						'size' => '40',
						'maxLength' => 255,
						'hideInLists' => true,
					],
					'instagramLink' => [
						'property' => 'instagramLink',
						'type' => 'text',
						'label' => 'Instagram Link Url',
						'description' => 'The url to Instagram (leave blank if the library does not have a Instagram account',
						'size' => '40',
						'maxLength' => 255,
						'hideInLists' => true,
					],
					'pinterestLink' => [
						'property' => 'pinterestLink',
						'type' => 'text',
						'label' => 'Pinterest Link Url',
						'description' => 'The url to Pinterest (leave blank if the library does not have a Pinterest account',
						'size' => '40',
						'maxLength' => 255,
						'hideInLists' => true,
					],
					'goodreadsLink' => [
						'property' => 'goodreadsLink',
						'type' => 'text',
						'label' => 'GoodReads Link Url',
						'description' => 'The url to GoodReads (leave blank if the library does not have a GoodReads account',
						'size' => '40',
						'maxLength' => 255,
						'hideInLists' => true,
					],
					'tiktokLink' => [
						'property' => 'tiktokLink',
						'type' => 'text',
						'label' => 'TikTok Link Url',
						'description' => 'The url to TikTok (leave blank if the library does not have a TikTok account',
						'size' => '40',
						'maxLength' => 255,
						'hideInLists' => true,
					],
					'generalContactLink' => [
						'property' => 'generalContactLink',
						'type' => 'text',
						'label' => 'General Contact Link Url',
						'description' => 'The url to a General Contact Page, i.e web form or mailto link',
						'size' => '40',
						'maxLength' => 255,
						'hideInLists' => true,
					],
					'contactEmail' => [
						'property' => 'contactEmail',
						'type' => 'text',
						'label' => 'General Email Address (LiDA only)',
						'description' => 'A general email address for the public to contact the library',
						'size' => '40',
						'maxLength' => 255,
						'hideInLists' => true,
						'affectsLiDA' => true,
					],
				],
			],
			'ssoSection' => [
				'property' => 'ssoSection',
				'type' => 'section',
				'label' => 'Single Sign-on',
				'hideInLists' => true,
				'properties' => [
					'ssoName' => [
						'property' => 'ssoName',
						'type' => 'text',
						'label' => 'Name of service',
						'description' => 'The name to be displayed when referring to the authentication service',
						'size' => '512',
						'hideInLists' => false,
						'permissions' => ['Library ILS Connection'],
					],
					'ssoXmlUrl' => [
						'property' => 'ssoXmlUrl',
						'type' => 'text',
						'label' => 'URL of service metadata XML',
						'description' => 'The URL at which the metadata XML document for this identity provider can be obtained',
						'size' => '512',
						'hideInLists' => false,
						'permissions' => ['Library ILS Connection'],
					],
					'ssoMetadataFilename' => [
						'path' => '/data/aspen-discovery/sso_metadata',
						'property' => 'ssoMetadataFilename',
						'type' => 'file',
						'label' => 'XML metadata file',
						'description' => 'The XML metadata file if no URL is available',
						'readOnly' => true,
						'permissions' => ['Library ILS Connection'],
					],
					'ssoEntityId' => [
						'property' => 'ssoEntityId',
						'type' => 'text',
						'label' => 'Entity ID of SSO provider',
						'description' => 'The entity ID of the SSO IdP. This can be found in the IdP\'s metadata',
						'size' => '512',
						'hideInLists' => false,
						'permissions' => ['Library ILS Connection'],
					],
					'ssoUniqueAttribute' => [
						'property' => 'ssoUniqueAttribute',
						'type' => 'text',
						'label' => 'Name of the identity provider attribute that uniquely identifies a user',
						'description' => 'This should be unique to each user',
						'size' => '512',
						'hideInLists' => false,
						'permissions' => ['Library ILS Connection'],
					],
					'ssoIdAttr' => [
						'property' => 'ssoIdAttr',
						'type' => 'text',
						'label' => 'Name of the identity provider attribute that contains the user ID',
						'description' => 'This should be unique to each user',
						'size' => '512',
						'hideInLists' => false,
						'permissions' => ['Library ILS Connection'],
					],
					'ssoUsernameAttr' => [
						'property' => 'ssoUsernameAttr',
						'type' => 'text',
						'label' => 'Name of the identity provider attribute that contains the user\'s username',
						'description' => 'The user\'s username',
						'size' => '512',
						'hideInLists' => false,
						'permissions' => ['Library ILS Connection'],
					],
					'ssoFirstnameAttr' => [
						'property' => 'ssoFirstnameAttr',
						'type' => 'text',
						'label' => 'Name of the identity provider attribute that contains the user\'s first name',
						'description' => 'The user\'s first name',
						'size' => '512',
						'hideInLists' => false,
						'permissions' => ['Library ILS Connection'],
					],
					'ssoLastnameAttr' => [
						'property' => 'ssoLastnameAttr',
						'type' => 'text',
						'label' => 'Name of the identity provider attribute that contains the user\'s last name',
						'description' => 'The user\'s last name',
						'size' => '512',
						'hideInLists' => false,
						'permissions' => ['Library ILS Connection'],
					],
					'ssoEmailAttr' => [
						'property' => 'ssoEmailAttr',
						'type' => 'text',
						'label' => 'Name of the identity provider attribute that contains the user\'s email address',
						'description' => 'The user\'s email address',
						'size' => '512',
						'hideInLists' => false,
						'permissions' => ['Library ILS Connection'],
					],
					'ssoDisplayNameAttr' => [
						'property' => 'ssoDisplayNameAttr',
						'type' => 'text',
						'label' => 'Name of the identity provider attribute that contains the user\'s display name',
						'description' => 'The user\'s display name, if one is not supplied, a name for display will be assembled from first and last names',
						'size' => '512',
						'hideInLists' => false,
						'permissions' => ['Library ILS Connection'],
					],
					'ssoPhoneAttr' => [
						'property' => 'ssoPhoneAttr',
						'type' => 'text',
						'label' => 'Name of the identity provider attribute that contains the user\'s phone number',
						'description' => 'The user\'s phone number',
						'size' => '512',
						'hideInLists' => false,
						'permissions' => ['Library ILS Connection'],
					],
					'ssoAddressAttr' => [
						'property' => 'ssoAddressAttr',
						'type' => 'text',
						'label' => 'Name of the identity provider attribute that contains the user\'s address',
						'description' => 'The user\'s address',
						'size' => '512',
						'hideInLists' => false,
						'permissions' => ['Library ILS Connection'],
					],
					'ssoCityAttr' => [
						'property' => 'ssoCityAttr',
						'type' => 'text',
						'label' => 'Name of the identity provider attribute that contains the user\'s city',
						'description' => 'The user\'s city',
						'size' => '512',
						'hideInLists' => false,
						'permissions' => ['Library ILS Connection'],
					],
					'ssoPatronTypeSection' => [
						'property' => 'ssoPatronTypeSection',
						'type' => 'section',
						'label' => 'Patron type',
						'hideInLists' => true,
						'permissions' => ['Library ILS Options'],
						'properties' => [
							'ssoPatronTypeAttr' => [
								'property' => 'ssoPatronTypeAttr',
								'serverValidation' => 'validatePatronType',
								'type' => 'text',
								'label' => 'Name of the identity provider attribute that contains the user\'s patron type',
								'description' => 'The user\'s patron type, this should be a value that is recognised by Aspen. If this is not supplied, please provide a fallback value below',
								'size' => '512',
								'hideInLists' => false,
								'permissions' => ['Library ILS Connection'],
							],
							'ssoPatronTypeFallback' => [
								'property' => 'ssoPatronTypeFallback',
								'type' => 'text',
								'label' => 'A fallback value for patron type',
								'description' => 'A value to be used in the event the identity provider does not supply a patron type attribute, this should be a value that is recognised by Aspen.',
								'size' => '512',
								'hideInLists' => false,
								'permissions' => ['Library ILS Connection'],
							],
						],
					],
					'ssoLibraryIdSection' => [
						'property' => 'ssoLibraryIdSection',
						'type' => 'section',
						'label' => 'Library ID',
						'hideInLists' => true,
						'permissions' => ['Library ILS Options'],
						'properties' => [
							'ssoLibraryIdAttr' => [
								'property' => 'ssoLibraryIdAttr',
								'serverValidation' => 'validateLibraryId',
								'type' => 'text',
								'label' => 'Name of the identity provider attribute that contains the user\'s library ID',
								'description' => 'The user\'s library ID, this should be an ID that is recognised by your LMS. If this is not supplied, please provide a fallback value below',
								'size' => '512',
								'hideInLists' => false,
								'permissions' => ['Library ILS Connection'],
							],
							'ssoLibraryIdFallback' => [
								'property' => 'ssoLibraryIdFallback',
								'type' => 'text',
								'label' => 'A fallback value for library ID',
								'description' => 'A value to be used in the event the identity provider does not supply a library ID attribute, this should be an ID that is recognised by your LMS',
								'size' => '512',
								'hideInLists' => false,
								'permissions' => ['Library ILS Connection'],
							],
						],
					],
					'ssoCategoryIdSection' => [
						'property' => 'ssoCategoryIdSection',
						'type' => 'section',
						'label' => 'Patron category ID',
						'hideInLists' => true,
						'permissions' => ['Library ILS Options'],
						'properties' => [
							'ssoCategoryIdAttr' => [
								'property' => 'ssoCategoryIdAttr',
								'serverValidation' => 'validateCategoryId',
								'type' => 'text',
								'label' => 'Name of the identity provider attribute that contains the user\'s patron category ID',
								'description' => 'The user\'s patron category ID, this should be an ID that is recognised by your LMS. If this is not supplied, please provide a fallback value below',
								'size' => '512',
								'hideInLists' => false,
								'permissions' => ['Library ILS Connection'],
							],
							'ssoCategoryIdFallback' => [
								'property' => 'ssoCategoryIdFallback',
								'type' => 'text',
								'label' => 'A fallback value for category ID',
								'description' => 'A value to be used in the event the identity provider does not supply a category ID attribute, this should be an ID that is recognised by your LMS',
								'size' => '512',
								'hideInLists' => false,
								'permissions' => ['Library ILS Connection'],
							],
						],
					],
				],
			],

			// ILS/Account Integration //
			'ilsSection' => [
				'property' => 'ilsSection',
				'type' => 'section',
				'label' => 'ILS/Account Integration',
				'hideInLists' => true,
				'properties' => [
					'ilsCode' => [
						'property' => 'ilsCode',
						'type' => 'text',
						'label' => 'ILS Code',
						'description' => 'The location code that all items for this location start with.',
						'size' => '4',
						'hideInLists' => false,
						'forcesReindex' => true,
						'permissions' => ['Library ILS Connection'],
					],
					'workstationId' => [
						'property' => 'workstationId',
						'type' => 'text',
						'label' => 'Workstation Id (Polaris)',
						'maxLength' => 10,
						'description' => 'Optional workstation ID for transactions. If different than main workstation ID set for the account profile.',
						'permissions' => ['Library ILS Connection'],
					],
					'scope' => [
						'property' => 'scope',
						'type' => 'text',
						'label' => 'Scope',
						'description' => 'The scope for the system in Millennium to refine holdings for the user.',
						'size' => '4',
						'hideInLists' => true,
						'default' => 0,
						'forcesReindex' => true,
						'permissions' => ['Library ILS Connection'],
					],
					'useScope' => [
						'property' => 'useScope',
						'type' => 'checkbox',
						'label' => 'Use Scope',
						'description' => 'Whether or not the scope should be used when displaying holdings.',
						'hideInLists' => true,
						'permissions' => ['Library ILS Connection'],
					],
					'showCardExpirationDate' => [
						'property' => 'showCardExpirationDate',
						'type' => 'checkbox',
						'label' => 'Show Card Expiration Date',
						'description' => 'Whether or not the user should be shown their cards expiration date on the My Library Card Page.',
						'hideInLists' => true,
						'default' => 1,
						'permissions' => ['Library ILS Options'],
					],
					'showExpirationWarnings' => [
						'property' => 'showExpirationWarnings',
						'type' => 'checkbox',
						'label' => 'Show Expiration Warnings',
						'description' => 'Whether or not the user should be shown expiration warnings if their card is nearly expired.',
						'hideInLists' => true,
						'default' => 1,
						'permissions' => ['Library ILS Options'],
					],
					'expirationNearMessage' => [
						'property' => 'expirationNearMessage',
						'type' => 'text',
						'label' => 'Expiration Near Message',
						'description' => 'A message to show in the menu when the user account will expire soon',
						'hideInLists' => true,
						'default' => '',
						'permissions' => ['Library ILS Options'],
						'note' => 'Use the token <code>%date%</code> to insert the expiration date',
					],
					'expiredMessage' => [
						'property' => 'expiredMessage',
						'type' => 'text',
						'label' => 'Expired Message',
						'description' => 'A message to show in the menu when the user account has expired',
						'hideInLists' => true,
						'default' => '',
						'permissions' => ['Library ILS Options'],
						'note' => 'Use the token <code>%date%</code> to insert the expiration date',
					],
					'showWhileYouWait' => [
						'property' => 'showWhileYouWait',
						'type' => 'checkbox',
						'label' => 'Show While You Wait',
						'description' => 'Whether or not the user should be shown suggestions of other titles they might like.',
						'hideInLists' => true,
						'default' => 1,
						'permissions' => ['Library ILS Options'],
					],
					'showMessagingSettings' => [
						'property' => 'showMessagingSettings',
						'type' => 'checkbox',
						'label' => 'Show Messaging Settings (Koha, Symphony)',
						'description' => 'Whether or not the user should be able to view their messaging settings.',
						'hideInLists' => true,
						'default' => 1,
						'permissions' => ['Library ILS Options'],
					],
					'allowLinkedAccounts' => [
						'property' => 'allowLinkedAccounts',
						'type' => 'checkbox',
						'label' => 'Allow Linked Accounts',
						'description' => 'Whether or not users can link multiple library cards under a single Aspen Discovery account.',
						'hideInLists' => true,
						'default' => 1,
						'permissions' => ['Library ILS Options'],
					],
					'showLibraryHoursNoticeOnAccountPages' => [
						'property' => 'showLibraryHoursNoticeOnAccountPages',
						'type' => 'checkbox',
						'label' => 'Show Library Hours Notice on Account Pages',
						'description' => 'Whether or not the Library Hours notice should be shown at the top of Your Account\'s Checked Out, and Holds pages.',
						'hideInLists' => true,
						'default' => true,
						'permissions' => ['Library ILS Options'],
					],
					'displayItemBarcode' => [
						'property' => 'displayItemBarcode',
						'type' => 'checkbox',
						'label' => 'Display item barcodes in patron checkouts',
						'description' => 'Whether or not patrons can see item barcodes to materials they have checked out.',
						'hideInLists' => true,
						'permissions' => ['Library ILS Connection'],
					],
					'displayHoldsOnCheckout' => [
						'property' => 'displayHoldsOnCheckout',
						'type' => 'checkbox',
						'label' => 'Display if patron checkouts have holds on them (Koha)',
						'description' => 'Whether or not patrons can see if checked out items have holds on them.',
						'hideInLists' => true,
						'permissions' => ['Library ILS Connection'],
					],
					'enableReadingHistory' => [
						'property' => 'enableReadingHistory',
						'type' => 'checkbox',
						'label' => 'Enable Reading History',
						'description' => 'Whether or not users reading history is shown within Aspen.',
						'hideInLists' => true,
						'default' => 1,
						'permissions' => ['Library ILS Options'],
					],
					'enableSavedSearches' => [
						'property' => 'enableSavedSearches',
						'type' => 'checkbox',
						'label' => 'Enable Saved Searches',
						'description' => 'Whether or not users can save searches within Aspen.',
						'hideInLists' => true,
						'default' => 1,
						'permissions' => ['Library ILS Options'],
					],
					'showUserCirculationModules' => [
						'property' => 'showUserCirculationModules',
						'type' => 'checkbox',
						'label' => 'Show Circulation Modules to Users in My Account',
						'description' => 'Whether or not users can see the circulation modules (checkouts, holds, fines, library card) in My Account.',
						'hideInLists' => true,
						'default' => 1,
						'permissions' => ['Library ILS Options'],
					],
					'showUserContactInformation' => [
						'property' => 'showUserContactInformation',
						'type' => 'checkbox',
						'label' => 'Show Contact Information to Users in My Account',
						'description' => 'Whether or not users can see their contact information (user profile) in My Account.',
						'hideInLists' => true,
						'default' => 1,
						'permissions' => ['Library ILS Options'],
					],
					'showUserPreferences' => [
						'property' => 'showUserPreferences',
						'type' => 'checkbox',
						'label' => 'Show Preferences to Users in My Account',
						'description' => 'Whether or not users can see their preferences in My Account.',
						'hideInLists' => true,
						'default' => 1,
						'permissions' => ['Library ILS Options'],
					],
					'barcodeSection' => [
						'property' => 'barcodeSection',
						'type' => 'section',
						'label' => 'Barcode',
						'hideInLists' => true,
						'permissions' => ['Library ILS Options'],
						'properties' => [
							'libraryCardBarcodeStyle' => [
								'property' => 'libraryCardBarcodeStyle',
								'type' => 'enum',
								'values' => $barcodeTypes,
								'label' => 'Library Barcode Style',
								'description' => 'The style to show for the barcode on the Library Card page',
								'hideInLists' => true,
								'default' => 'none',
							],
							'minBarcodeLength' => [
								'property' => 'minBarcodeLength',
								'type' => 'integer',
								'label' => 'Min Barcode Length',
								'description' => 'A minimum length the patron barcode is expected to be. Leave as 0 to extra processing of barcodes.',
								'hideInLists' => true,
								'default' => 0,
							],
							'maxBarcodeLength' => [
								'property' => 'maxBarcodeLength',
								'type' => 'integer',
								'label' => 'Max Barcode Length',
								'description' => 'The maximum length the patron barcode is expected to be. Leave as 0 to extra processing of barcodes.',
								'hideInLists' => true,
								'default' => 0,
							],
							'barcodePrefix' => [
								'property' => 'barcodePrefix',
								'type' => 'text',
								'label' => 'Barcode Prefix',
								'description' => 'A barcode prefix to apply to the barcode if it does not start with the barcode prefix or if it is not within the expected min/max range.  Multiple prefixes can be specified by separating them with commas. Leave blank to avoid additional processing of barcodes.',
								'hideInLists' => true,
								'default' => '',
							],
						],
					],
					'alternateLibraryCardSection' => [
						'property' => 'alternateLibraryCardSection',
						'type' => 'section',
						'label' => 'Alternate Library Card',
						'hideInLists' => true,
						'permissions' => ['Library ILS Options'],
						'properties' => [
							'showAlternateLibraryCard' => [
								'property' => 'showAlternateLibraryCard',
								'type' => 'checkbox',
								'label' => 'Show Alternate Library Card',
								'description' => 'Whether or not the patron can enter an alternate library card.',
								'hideInLists' => true,
								'default' => 0,
							],
							'alternateLibraryCardStyle' => [
								'property' => 'alternateLibraryCardStyle',
								'type' => 'enum',
								'values' => $barcodeTypes,
								'label' => 'Alternate Library Card Barcode Style',
								'description' => 'The style to show for the alternate barcode on the Library Card page',
								'hideInLists' => true,
								'default' => 'none',
							],
							'showAlternateLibraryCardPassword' => [
								'property' => 'showAlternateLibraryCardPassword',
								'type' => 'checkbox',
								'label' => 'Show Alternate Library Card PIN/Password',
								'description' => 'Whether or not the patron can enter an PIN/Password for their alternate library card',
								'hideInLists' => true,
								'default' => 0,
							],
							'alternateLibraryCardLabel' => [
								'property' => 'alternateLibraryCardLabel',
								'type' => 'text',
								'label' => 'Alternate Library Card Label',
								'description' => 'A label describing the alternate library card.',
								'hideInLists' => true,
								'default' => '',
							],
							'alternateLibraryCardPasswordLabel' => [
								'property' => 'alternateLibraryCardPasswordLabel',
								'type' => 'text',
								'label' => 'Alternate Library Card PIN/Password Label',
								'description' => 'A label describing the PIN/Password field for the alternate library card',
								'hideInLists' => true,
								'default' => '',
							],
						],
					],
					'userProfileSection' => [
						'property' => 'userProfileSection',
						'type' => 'section',
						'label' => 'User Profile',
						'hideInLists' => true,
						'helpLink' => '',
						'properties' => [
							'patronNameDisplayStyle' => [
								'property' => 'patronNameDisplayStyle',
								'type' => 'enum',
								'values' => [
									'firstinitial_lastname' => 'First Initial. Last Name',
									'lastinitial_firstname' => 'First Name Last Initial.',
								],
								'label' => 'Patron Display Name Style',
								'description' => 'How to generate the patron display name',
								'permissions' => ['Library ILS Options'],
							],
							'allowProfileUpdates' => [
								'property' => 'allowProfileUpdates',
								'type' => 'checkbox',
								'label' => 'Allow Profile Updates',
								'description' => 'Whether or not the user can update their own profile.',
								'hideInLists' => true,
								'default' => 1,
								'readonly' => false,
								'permissions' => ['Library ILS Connection'],
							],
							'allowUsernameUpdates' => [
								'property' => 'allowUsernameUpdates',
								'type' => 'checkbox',
								'label' => 'Allow Patrons to Update Their Username',
								'description' => 'Whether or not the user can update their username.',
								'hideInLists' => true,
								'default' => 0,
								'readonly' => false,
								'permissions' => ['Library ILS Connection'],
							],
							'allowNameUpdates' => [
								'property' => 'allowNameUpdates',
								'type' => 'checkbox',
								'label' => 'Allow Patrons to Update Their Name (Setting applies to Koha only)',
								'description' => 'Whether or not patrons should be able to update their name in their profile.',
								'hideInLists' => true,
								'default' => 1,
								'readOnly' => false,
								'permissions' => ['Library ILS Connection'],
							],
							'allowDateOfBirthUpdates' => [
								'property' => 'allowDateOfBirthUpdates',
								'type' => 'checkbox',
								'label' => 'Allow Patrons to Update Their Date of Birth (Setting applies to Koha only)',
								'description' => 'Whether or not patrons should be able to update their date of birth in their profile.',
								'hideInLists' => true,
								'default' => 1,
								'readOnly' => false,
								'permissions' => ['Library ILS Connection'],
							],
							'allowPatronAddressUpdates' => [
								'property' => 'allowPatronAddressUpdates',
								'type' => 'checkbox',
								'label' => 'Allow Patrons to Update Their Address',
								'description' => 'Whether or not patrons should be able to update their own address in their profile.',
								'hideInLists' => true,
								'default' => 1,
								'readOnly' => false,
								'permissions' => ['Library ILS Connection'],
							],
							'allowPatronPhoneNumberUpdates' => [
								'property' => 'allowPatronPhoneNumberUpdates',
								'type' => 'checkbox',
								'label' => 'Allow Patrons to Update Their Phone Number',
								'description' => 'Whether or not patrons should be able to update their own phone number in their profile.',
								'hideInLists' => true,
								'default' => 1,
								'readOnly' => false,
								'permissions' => ['Library ILS Connection'],
							],
							'allowHomeLibraryUpdates' => [
								'property' => 'allowHomeLibraryUpdates',
								'type' => 'checkbox',
								'label' => 'Allow Patrons to Update Their Home Library',
								'description' => 'Whether or not the user can update their home library.',
								'hideInLists' => true,
								'default' => 1,
								'readonly' => false,
								'permissions' => ['Library ILS Options'],
							],
							'useAllCapsWhenUpdatingProfile' => [
								'property' => 'useAllCapsWhenUpdatingProfile',
								'type' => 'checkbox',
								'label' => 'Use All Caps When Updating Profile',
								'description' => 'Whether or not modifications to the patron profile will be submitted using all caps',
								'default' => 0,
								'permissions' => ['Library ILS Options'],
							],
							'requireNumericPhoneNumbersWhenUpdatingProfile' => [
								'property' => 'requireNumericPhoneNumbersWhenUpdatingProfile',
								'type' => 'checkbox',
								'label' => 'Require Numeric Phone Numbers When Updating Profile',
								'description' => 'Whether or not modifications to the patron phone numbers will be submitted with numbers only',
								'default' => 0,
								'permissions' => ['Library ILS Options'],
							],
							'bypassReviewQueueWhenUpdatingProfile' => [
								'property' => 'bypassReviewQueueWhenUpdatingProfile',
								'type' => 'checkbox',
								'label' => 'Bypass Review Queue Updating Profile (Koha only)',
								'description' => 'Whether or not the Koha review queue for patron modifications is bypassed when updates are submitted',
								'default' => 0,
								'permissions' => ['Library ILS Connection'],
							],
							'enableForgotPasswordLink' => [
								'property' => 'enableForgotPasswordLink',
								'type' => 'checkbox',
								'label' => 'Enable Forgot Password Link',
								'description' => 'Whether or not the user can click a link to reset their password.',
								'hideInLists' => true,
								'default' => 1,
								'permissions' => ['Library ILS Connection'],
							],
							'showAlternateLibraryOptionsInProfile' => [
								'property' => 'showAlternateLibraryOptionsInProfile',
								'type' => 'checkbox',
								'label' => 'Allow Patrons to Update their Alternate Libraries',
								'description' => 'Allow Patrons to See and Change Alternate Library Settings in the Catalog Options Tab in their profile.',
								'hideInLists' => true,
								'default' => 1,
								'permissions' => ['Library ILS Options'],
							],
							'showWorkPhoneInProfile' => [
								'property' => 'showWorkPhoneInProfile',
								'type' => 'checkbox',
								'label' => 'Show Work Phone in Profile',
								'description' => 'Whether or not patrons should be able to change a secondary/work phone number in their profile.',
								'hideInLists' => true,
								'default' => 0,
								'permissions' => ['Library ILS Connection'],
							],
							'showNoticeTypeInProfile' => [
								'property' => 'showNoticeTypeInProfile',
								'type' => 'checkbox',
								'label' => 'Show Notice Type in Profile',
								'description' => 'Whether or not patrons should be able to change how they receive notices in their profile.',
								'hideInLists' => true,
								'default' => 0,
								'permissions' => ['Library ILS Connection'],
							],
							'cityStateField' => [
								'property' => 'cityStateField',
								'type' => 'enum',
								'values' => [
									0 => 'CITY / STATE field',
									1 => 'CITY and STATE fields',
								],
								'label' => 'City / State Field (Symphony Only)',
								'description' => 'The field to load and update city and state from (symphony only).',
								'hideInLists' => true,
								'default' => 0,
								'permissions' => ['Library ILS Connection'],
							],
							'addSMSIndicatorToPhone' => [
								'property' => 'addSMSIndicatorToPhone',
								'type' => 'checkbox',
								'label' => 'Add SMS Indicator to Primary Phone',
								'description' => 'Whether or not add ### TEXT ONLY to the user\'s primary phone number when they opt in to SMS notices.',
								'hideInLists' => true,
								'default' => 0,
								'permissions' => ['Library ILS Connection'],
							],
							'maxFinesToAllowAccountUpdates' => [
								'property' => 'maxFinesToAllowAccountUpdates',
								'type' => 'currency',
								'displayFormat' => '%0.2f',
								'label' => 'Maximum Fine Amount to Allow Account Updates',
								'description' => 'The maximum amount that a patron can owe and still update their account. Any value <= 0 will disable this functionality.',
								'hideInLists' => true,
								'default' => 10,
								'permissions' => ['Library ILS Options'],
							],
						],
					],
					'pinSection' => [
						'property' => 'pinSection',
						'type' => 'section',
						'label' => 'PIN / Password',
						'hideInLists' => true,
						'helpLink' => '',
						'permissions' => ['Library ILS Connection'],
						'properties' => [
							'allowPinReset' => [
								'property' => 'allowPinReset',
								'type' => 'checkbox',
								'label' => 'Allow PIN Reset',
								'description' => 'Whether or not the user can reset their PIN if they forget it.',
								'hideInLists' => true,
								'default' => 0,
								'permissions' => ['Library ILS Connection'],
							],
							'minPinLength' => [
								'property' => 'minPinLength',
								'type' => 'integer',
								'label' => 'Minimum PIN Length',
								'description' => 'The minimum PIN length.',
								'hideInLists' => true,
								'default' => 4,
								'permissions' => ['Library ILS Connection'],
							],
							'maxPinLength' => [
								'property' => 'maxPinLength',
								'type' => 'integer',
								'label' => 'Maximum PIN Length',
								'description' => 'The maximum PIN length.',
								'hideInLists' => true,
								'default' => 4,
								'permissions' => ['Library ILS Connection'],
							],
							'onlyDigitsAllowedInPin' => [
								'property' => 'onlyDigitsAllowedInPin',
								'type' => 'checkbox',
								'label' => 'Only digits allowed in PIN',
								'description' => 'Whether or not the user can use only digits in the PIN.',
								'hideInLists' => true,
								'default' => 1,
								'permissions' => ['Library ILS Connection'],
							],
						],
					],
					'holdsSection' => [
						'property' => 'holdsSection',
						'type' => 'section',
						'label' => 'Holds',
						'hideInLists' => true,
						'helpLink' => '',
						'permissions' => [
							'Library ILS Connection',
							'Library ILS Options',
						],
						'properties' => [
							'showHoldButton' => [
								'property' => 'showHoldButton',
								'type' => 'checkbox',
								'label' => 'Show Hold Button',
								'description' => 'Whether or not the hold button is displayed so patrons can place holds on items',
								'hideInLists' => true,
								'default' => 1,
							],
							'showHoldButtonInSearchResults' => [
								'property' => 'showHoldButtonInSearchResults',
								'type' => 'checkbox',
								'label' => 'Show Hold Button within the search results',
								'description' => 'Whether or not the hold button is displayed within the search results so patrons can place holds on items',
								'hideInLists' => true,
								'default' => 1,
							],
							'showHoldButtonForUnavailableOnly' => [
								'property' => 'showHoldButtonForUnavailableOnly',
								'type' => 'checkbox',
								'label' => 'Show Hold Button for items that are checked out only',
								'description' => 'Whether or not the hold button is displayed within the search results so patrons can place holds on items',
								'hideInLists' => true,
								'default' => 0,
							],
							'allowPickupLocationUpdates' => [
								'property' => 'allowPickupLocationUpdates',
								'type' => 'checkbox',
								'label' => 'Allow Patrons to Update Their Pickup Location',
								'description' => 'Whether or not patrons should be able to update their preferred pickup location in their profile.',
								'hideInLists' => true,
								'default' => 0,
							],
							'allowRememberPickupLocation' => [
								'property' => 'allowRememberPickupLocation',
								'type' => 'checkbox',
								'label' => 'Allow Patrons to remember their preferred pickup location',
								'description' => 'Whether or not patrons can remember their preferred pickup location when placing holds',
								'hideInLists' => true,
								'default',
								'true',
							],
							'showHoldCancelDate' => [
								'property' => 'showHoldCancelDate',
								'type' => 'checkbox',
								'label' => 'Show Cancellation Date',
								'description' => 'Whether or not the patron should be able to set a cancellation date (not needed after date) when placing holds.',
								'hideInLists' => true,
								'default' => 1,
							],
							'showLogMeOutAfterPlacingHolds' => [
								'property' => 'showLogMeOutAfterPlacingHolds',
								'type' => 'checkbox',
								'label' => 'Show Log Me Out After Placing Holds',
								'description' => 'Whether or a checkbox should be shown that will automatically log patrons out after a hold is placed.',
								'hideInLists' => true,
								'default' => 1,
							],
							'treatBibOrItemHoldsAs' => [
								'property' => 'treatBibOrItemHoldsAs',
								'type' => 'enum',
								'values' => [
									'1' => 'Either Bib or Item Level Hold',
									'2' => 'Force Bib Level Hold',
									'3' => 'Force Item Level Hold',
								],
								'label' => 'Treat holds for formats that allow either bib or item holds as ',
								'description' => 'How to handle holds when either bib or item level holds are allowed.',
							],
							'showVolumesWithLocalCopiesFirst' => [
								'property' => 'showVolumesWithLocalCopiesFirst',
								'type' => 'checkbox',
								'label' => 'Show volumes with local copies first when placing holds',
								'description' => 'When true, volumes that have at least one copy owned locally are shown before volumes with no local copies.',
								'default' => 0,
							],
							'allowFreezeHolds' => [
								'property' => 'allowFreezeHolds',
								'type' => 'checkbox',
								'label' => 'Allow Freezing Holds',
								'description' => 'Whether or not the user can freeze their holds.',
								'hideInLists' => true,
								'default' => 1,
							],
							'maxDaysToFreeze' => [
								'property' => 'maxDaysToFreeze',
								'type' => 'integer',
								'label' => 'Max Days to Freeze Holds',
								'description' => 'Number of days that a user can suspend a hold for. Use -1 for no limit.',
								'hideInLists' => true,
								'default' => 365,
							],
							'defaultNotNeededAfterDays' => [
								'property' => 'defaultNotNeededAfterDays',
								'type' => 'integer',
								'label' => 'Default Not Needed After Days',
								'description' => 'Number of days to use for not needed after date by default. Use -1 for no default.',
								'hideInLists' => true,
							],
							'inSystemPickupsOnly' => [
								'property' => 'inSystemPickupsOnly',
								'type' => 'checkbox',
								'label' => 'In System Pickups Only',
								'description' => 'Restrict pickup locations to only locations within this library system.',
								'hideInLists' => true,
								'default' => true,
								'permissions' => ['Library ILS Connection'],
							],
							'validPickupSystems' => [
								'property' => 'validPickupSystems',
								'type' => 'text',
								'label' => 'Valid Pickup Library Systems',
								'description' => 'Additional Library Systems that can be used as pickup locations if the &quot;In System Pickups Only&quot; is on. List the libraries\' subdomains separated by pipes |',
								'size' => '20',
								'hideInLists' => true,
								'permissions' => ['Library ILS Connection'],
							],
							'holdDisclaimer' => [
								'property' => 'holdDisclaimer',
								'type' => 'textarea',
								'label' => 'Hold Disclaimer',
								'description' => 'A disclaimer to display to patrons when they are placing a hold on items letting them know that their information may be available to other libraries.  Leave blank to not show a disclaimer.',
								'hideInLists' => true,
							],
							'availableHoldDelay' => [
								'property' => 'availableHoldDelay',
								'type' => 'integer',
								'label' => 'Delay showing holds available for # of days',
								'description' => 'Delay showing holds as a available for a specific number of days to account for shelving time',
								'hideInLists' => true,
								'default' => 0,
							],
							'holdPlacedAt' => [
								'property' => 'holdPlacedAt',
								'type' => 'enum',
								'values' => [
									0 => 'Use Active Library Catalog',
									1 => 'Use Patron Home Library',
									2 => 'Use Pickup Location',
								],
								'label' => 'Hold Placed At (Symphony Only)',
								'description' => 'Determines how the hold placed at value should be set when placing holds',
								'hideInLists' => true,
								'default' => 0,
							],
							'holdRange' => [
								'property' => 'holdRange',
								'type' => 'enum',
								'values' => [
									'SYSTEM' => 'System',
									'GROUP' => 'Group'
								],
								'label' => 'Hold Range (Symphony Only)',
								'description' => 'The hold range to use when placing holds in Symphony',
								'default' => 'SYSTEM',
							],
							'systemHoldNote' => [
								'property' => 'systemHoldNote',
								'type' => 'text',
								'label' => 'System Hold Note (Symphony Only)',
								'description' => 'A note to automatically add when placing a hold',
								'hideInLists' => true,
								'maxLength' => 50,
								'default' => '',
							],
							'systemHoldNoteMasquerade' => [
								'property' => 'systemHoldNoteMasquerade',
								'type' => 'text',
								'label' => 'System Hold Note Masquerade (Symphony Only)',
								'description' => 'A note to automatically add when placing a hold when a librarian is Masquerading and places a hold',
								'hideInLists' => true,
								'maxLength' => 50,
								'default' => '',
							],
						],
					],
					'loginSection' => [
						'property' => 'loginSection',
						'type' => 'section',
						'label' => 'Login',
						'hideInLists' => true,
						'permissions' => [
							'Library ILS Connection',
							'Library ILS Options',
						],
						'properties' => [
							'showLoginButton' => [
								'property' => 'showLoginButton',
								'type' => 'checkbox',
								'label' => 'Show Login Button',
								'description' => 'Whether or not the login button is displayed so patrons can login to the site',
								'hideInLists' => true,
								'default' => 1,
							],
							'preventLogin' => [
								'property' => 'preventLogin',
								'type' => 'checkbox',
								'label' => 'Prevent Login for Patrons of this Library',
								'description' => 'Patrons of this library will not be allowed to login (useful in consortial catalogs to prevent access in other interfaces).',
								'hideInLists' => true,
								'default' => 0,
							],
							'preventLoginMessage' => [
								'property' => 'preventLoginMessage',
								'type' => 'html',
								'label' => 'Prevented Login Message',
								'description' => 'A message to show to patrons for patrons of this library if they are prevented from logging in',
								'hideInLists' => true,
							],
							'preventExpiredCardLogin' => [
								'property' => 'preventExpiredCardLogin',
								'type' => 'checkbox',
								'label' => 'Prevent Login for Expired Cards',
								'description' => 'Users with expired cards will not be allowed to login. They will receive an expired card notice instead.',
								'hideInLists' => true,
								'default' => 0,
							],
							'defaultRememberMe' => [
								'property' => 'defaultRememberMe',
								'type' => 'checkbox',
								'label' => 'Check "Remember Me" by default when outside the library',
								'description' => 'Whether or not to check the "Remember Me" option by default when logging in outside the library',
								'hideInLists' => true,
								'default' => 0,
							],
							'loginFormUsernameLabel' => [
								'property' => 'loginFormUsernameLabel',
								'type' => 'text',
								'label' => 'Login Form Username Label',
								'description' => 'The label to show for the username when logging in',
								'size' => '100',
								'hideInLists' => true,
								'default' => 'Your Name',
							],
							'loginFormPasswordLabel' => [
								'property' => 'loginFormPasswordLabel',
								'type' => 'text',
								'label' => 'Login Form Password Label',
								'description' => 'The label to show for the password when logging in',
								'size' => '100',
								'hideInLists' => true,
								'default' => 'Library Card Number',
							],
							'loginNotes' => [
								'property' => 'loginNotes',
								'type' => 'markdown',
								'label' => 'Login Notes',
								'description' => 'Additional notes to display under the login fields',
								'hideInLists' => true,
							],
							'allowLoginToPatronsOfThisLibraryOnly' => [
								'property' => 'allowLoginToPatronsOfThisLibraryOnly',
								'type' => 'checkbox',
								'label' => 'Allow Login to Patrons of this Library Only',
								'description' => 'Whether or not only patrons with a library in this system can login',
								'hideInLists' => true,
								'default' => 0,
							],
							'messageForPatronsOfOtherLibraries' => [
								'property' => 'messageForPatronsOfOtherLibraries',
								'type' => 'html',
								'label' => 'Message for Patrons of Other Libraries',
								'description' => 'A message to show to patrons of other libraries if they are denied access',
								'hideInLists' => true,
							],
						],
					],
					'messagesSection' => [
						'property' => 'messagesSection',
						'type' => 'section',
						'label' => 'Messages',
						'hideInLists' => true,
						'permissions' => ['Library ILS Connection'],
						'properties' => [
							'showOpacNotes' => [
								'property' => 'showOpacNotes',
								'type' => 'checkbox',
								'label' => 'Show OPAC Notes',
								'description' => 'Whether or not OPAC/Web Notes from the ILS should be shown',
								'hideInLists' => true,
								'default' => 0,
							],
							'showBorrowerMessages' => [
								'property' => 'showBorrowerMessages',
								'type' => 'checkbox',
								'label' => 'Show Borrower Notes',
								'description' => 'Whether or not Borrower Messages from the ILS should be shown',
								'hideInLists' => true,
								'default' => 0,
							],
							'showDebarmentNotes' => [
								'property' => 'showDebarmentNotes',
								'type' => 'checkbox',
								'label' => 'Show Debarment Notes',
								'description' => 'Whether or not Debarment Messages from the ILS should be shown',
								'hideInLists' => true,
								'default' => 0,
							],
						],
					],
					'selfRegistrationSection' => [
						'property' => 'selfRegistrationSection',
						'type' => 'section',
						'label' => 'Self Registration',
						'hideInLists' => true,
						'permissions' => ['Library Self Registration'],
						'properties' => [
							'enableSelfRegistration' => [
								'property' => 'enableSelfRegistration',
								'type' => 'enum',
								'values' => $validSelfRegistrationOptions,
								'label' => 'Enable Self Registration',
								'description' => 'Whether or not patrons can self register on the site',
								'hideInLists' => true,
							],
							'selfRegistrationLocationRestrictions' => [
								'property' => 'selfRegistrationLocationRestrictions',
								'type' => 'enum',
								'values' => [
									0 => 'No Restrictions',
									1 => 'All Library Locations',
									2 => 'All Self Registration Locations',
									3 => 'Self Registration Locations for the library',
								],
								'label' => 'Valid Self Registration Locations',
								'description' => 'Indicates which locations are valid pickup locations',
								'hideInLists' => true,
							],
							'selfRegistrationPasswordNotes' => [
								'property' => 'selfRegistrationPasswordNotes',
								'type' => 'text',
								'label' => 'Self Registration Password Notes',
								'description' => 'Notes to be displayed when setting the password for self registration',
								'hideInLists' => true,
								'default' => '',
							],
							'promptForBirthDateInSelfReg' => [
								'property' => 'promptForBirthDateInSelfReg',
								'type' => 'checkbox',
								'label' => 'Prompt For Birth Date',
								'description' => 'Whether or not to prompt for birth date when self registering',
							],
							'selfRegRequirePhone' => [
								'property' => 'selfRegRequirePhone',
								'type' => 'checkbox',
								'label' => 'Self Registration requires Phone Number (Symphony Only)',
								'description' => 'Whether or not phone number is required when self registering',
							],
							'selfRegRequireEmail' => [
								'property' => 'selfRegRequireEmail',
								'type' => 'checkbox',
								'label' => 'Self Registration requires Email (Symphony Only)',
								'description' => 'Whether or not email is required when self registering',
							],
							'promptForParentInSelfReg' => [
								'property' => 'promptForParentInSelfReg',
								'type' => 'checkbox',
								'label' => 'Prompt For Parent Information (Symphony Only)',
								'description' => 'Whether or not parent information should be requested if the person registering is a juvenile.',
							],
							'promptForSMSNoticesInSelfReg' => [
								'property' => 'promptForSMSNoticesInSelfReg',
								'type' => 'checkbox',
								'label' => 'Prompt For SMS Notices (Symphony Only)',
								'description' => 'Whether or not SMS Notification information should be requested',
							],
							'useAllCapsWhenSubmittingSelfRegistration' => [
								'property' => 'useAllCapsWhenSubmittingSelfRegistration',
								'type' => 'checkbox',
								'label' => 'Use All Caps When Submitting Self Registration',
								'description' => 'Whether or not self registration will be submitted using all caps',
							],
							'validSelfRegistrationStates' => [
								'property' => 'validSelfRegistrationStates',
								'type' => 'text',
								'label' => 'Valid States for Self Registration',
								'description' => 'The states that can be used in self registration (separate multiple states with pipes |)',
								'hideInLists' => true,
								'default' => '',
							],
							'validSelfRegistrationZipCodes' => [
								'property' => 'validSelfRegistrationZipCodes',
								'type' => 'regularExpression',
								'label' => 'Valid Zip/Postal Codes for Self Registration (regular expression)',
								'description' => 'The zip codes/postal codes that can be used in self registration',
								'hideInLists' => true,
								'default' => '',
							],
							'selfRegistrationUrl' => [
								'property' => 'selfRegistrationUrl',
								'type' => 'url',
								'label' => 'Self Registration URL',
								'description' => 'An external URL where users can self register',
								'hideInLists' => true,
							],
							'selfRegistrationFormMessage' => [
								'property' => 'selfRegistrationFormMessage',
								'type' => 'html',
								'label' => 'Self Registration Form Message',
								'description' => 'Message shown to users with the form to submit the self registration.  Leave blank to give users the default message.',
								'hideInLists' => true,
							],
							'selfRegistrationSuccessMessage' => [
								'property' => 'selfRegistrationSuccessMessage',
								'type' => 'html',
								'label' => 'Self Registration Success Message',
								'description' => 'Message shown to users when the self registration has been completed successfully.  Leave blank to give users the default message.',
								'hideInLists' => true,
							],
							'selfRegistrationTemplate' => [
								'property' => 'selfRegistrationTemplate',
								'type' => 'text',
								'label' => 'Self Registration Template',
								'description' => 'The ILS template to use during self registration (Sierra and Millennium).',
								'hideInLists' => true,
								'default' => 'default',
							],
							'selfRegistrationUserProfile' => [
								'property' => 'selfRegistrationUserProfile',
								'type' => 'text',
								'label' => 'Self Registration Profile',
								'description' => 'The Profile to use during self registration (Symphony).',
								'hideInLists' => true,
								'default' => 'SELFREG',
							],
						],
					],
					'masqueradeModeSection' => [
						'property' => 'masqueradeModeSection',
						'type' => 'section',
						'label' => 'Masquerade Mode',
						'hideInLists' => true,
						'permissions' => ['Library ILS Connection'],
						'properties' => [
							'allowMasqueradeMode' => [
								'property' => 'allowMasqueradeMode',
								'type' => 'checkbox',
								'label' => 'Allow Masquerade Mode',
								'description' => 'Whether or not staff users (depending on pType setting) can use Masquerade Mode.',
								'hideInLists' => true,
								'default' => true,
							],
							'masqueradeAutomaticTimeoutLength' => [
								'property' => 'masqueradeAutomaticTimeoutLength',
								'type' => 'integer',
								'label' => 'Masquerade Mode Automatic Timeout Length',
								'description' => 'The length of time before an idle user\'s Masquerade session automatically ends in seconds.',
								'size' => '8',
								'hideInLists' => true,
								'max' => 240,
							],
							'allowReadingHistoryDisplayInMasqueradeMode' => [
								'property' => 'allowReadingHistoryDisplayInMasqueradeMode',
								'type' => 'checkbox',
								'label' => 'Allow Display of Reading History in Masquerade Mode',
								'description' => 'This option allows Guiding Users to view the Reading History of the masqueraded user.',
								'hideInLists' => true,
								'default' => false,
							],
						],
					],
				],
			],

			'ecommerceSection' => [
				'property' => 'ecommerceSection',
				'type' => 'section',
				'label' => 'Fines/e-commerce',
				'hideInLists' => true,
				'helpLink' => '',
				'permissions' => ['Library eCommerce Options'],
				'properties' => [
					'finePaymentType' => [
						'property' => 'finePaymentType',
						'type' => 'enum',
						'label' => 'Show E-Commerce Link',
						'values' => [
							0 => 'No Payment',
							1 => 'Link to ILS',
							2 => 'PayPal',
							3 => 'MSB',
							4 => 'Comprise SMARTPAY',
							5 => 'ProPay',
							6 => 'Xpress-pay',
							7 => 'FIS WorldPay',
							8 => 'ACI Speedpay',
							9 => 'InvoiceCloud'
						],
						'description' => 'Whether or not users should be allowed to pay fines',
						'hideInLists' => true,
					],
					'finesToPay' => [
						'property' => 'finesToPay',
						'type' => 'enum',
						'label' => 'Which fines should be paid',
						'values' => [
							0 => 'All Fines',
							1 => 'Selected Fines',
							2 => 'Partial payment of selected fines',
						],
						'description' => 'The fines that should be paid',
						'hideInLists' => true,
					],
					'finePaymentOrder' => [
						'property' => 'finePaymentOrder',
						'type' => 'text',
						'label' => 'Fine Payment Order by type (separated with pipes)',
						'description' => 'The order fines should be paid in separated by pipes',
						'hideInLists' => true,
						'default' => 'default',
						'size' => 80,
					],
					'payFinesLink' => [
						'property' => 'payFinesLink',
						'type' => 'text',
						'label' => 'Pay Fines Link',
						'description' => 'The link to pay fines.  Leave as default to link to classic (should have eCommerce link enabled)',
						'hideInLists' => true,
						'default' => 'default',
						'size' => 80,
					],
					'payFinesLinkText' => [
						'property' => 'payFinesLinkText',
						'type' => 'text',
						'label' => 'Pay Fines Link Text',
						'description' => 'The text when linking to pay fines.',
						'hideInLists' => true,
						'default' => 'Click to Pay Fines Online',
						'size' => 80,
					],
					'minimumFineAmount' => [
						'property' => 'minimumFineAmount',
						'type' => 'currency',
						'displayFormat' => '%0.2f',
						'label' => 'Minimum Fine Amount',
						'description' => 'The minimum fine amount to display the e-commerce link',
						'hideInLists' => true,
					],
					'showRefreshAccountButton' => [
						'property' => 'showRefreshAccountButton',
						'type' => 'checkbox',
						'label' => 'Show Refresh Account Button',
						'description' => 'Whether or not a Show Refresh Account button is displayed in a pop-up when a user clicks the E-Commerce Link',
						'hideInLists' => true,
						'default' => true,
					],

					'compriseSettingId' => [
						'property' => 'compriseSettingId',
						'type' => 'enum',
						'values' => $compriseSettings,
						'label' => 'Comprise SMARTPAY Settings',
						'description' => 'The Comprise SMARTPAY settings to use',
						'hideInLists' => true,
						'default' => -1,
					],
					'worldPaySettingId' => [
						'property' => 'worldPaySettingId',
						'type' => 'enum',
						'values' => $worldPaySettings,
						'label' => 'FIS World Pay Settings',
						'description' => 'The FIS WorldPay settings to use',
						'hideInLists' => true,
						'default' => -1,
					],
					'payPalSettingId' => [
						'property' => 'payPalSettingId',
						'type' => 'enum',
						'values' => $payPalSettings,
						'label' => 'PayPal Settings',
						'description' => 'The PayPal settings to use',
						'hideInLists' => true,
						'default' => -1,
					],
					'proPaySettingId' => [
						'property' => 'proPaySettingId',
						'type' => 'enum',
						'values' => $proPaySettings,
						'label' => 'ProPay Settings',
						'description' => 'The ProPay settings to use',
						'hideInLists' => true,
						'default' => -1,
					],
					'xpressPaySettingId' => [
						'property' => 'xpressPaySettingId',
						'type' => 'enum',
						'values' => $xpressPaySettings,
						'label' => 'Xpress-pay Settings',
						'description' => 'The Xpress-pay settings to use',
						'hideInLists' => true,
						'default' => -1,
					],
					'aciSpeedpaySettingId' => [
						'property' => 'aciSpeedpaySettingId',
						'type' => 'enum',
						'values' => $aciSpeedpaySettings,
						'label' => 'ACI Speedpay Settings',
						'description' => 'The ACI Speedpay settings to use',
						'hideInLists' => true,
						'default' => -1,
					],
					'invoiceCloudSettingId' => [
						'property' => 'invoiceCloudSettingId',
						'type' => 'enum',
						'values' => $invoiceCloudSettings,
						'label' => 'InvoiceCloud Settings',
						'description' => 'The InvoiceCloud settings to use',
						'hideInLists' => true,
						'default' => -1,
					],
					'msbUrl' => [
						'property' => 'msbUrl',
						'type' => 'text',
						'label' => 'MSB URL',
						'description' => 'The MSB payment form URL and path (but NOT the query or parameters)',
						'hideInLists' => true,
						'default' => '',
						'size' => 80,
					],
					'symphonyPaymentType' => [
						'property' => 'symphonyPaymentType',
						'type' => 'text',
						'label' => 'Symphony Payment Type',
						'description' => 'Payment type to use when adding transactions to Symphony.',
						'hideInLists' => true,
						'default' => '',
						'maxLength' => 8,
					],
					//'symphonyPaymentPolicy' => array('property'=>'symphonyPaymentPolicy', 'type'=>'text', 'label'=>'Symphony Payment Policy', 'description'=>'Payment policy to use when adding transactions to Symphony.', 'hideInLists' => true, 'default' => '', 'maxLength' => 8),
				],
			],

			//Grouped Work Display
			'groupedWorkDisplaySettingId' => [
				'property' => 'groupedWorkDisplaySettingId',
				'type' => 'enum',
				'values' => $groupedWorkDisplaySettings,
				'label' => 'Grouped Work Display Settings',
				'hideInLists' => false,
				'default' => $defaultSettingId,
				'permissions' => ['Library Catalog Options'],
				'forcesReindex' => true,
			],

			// Searching //
			'searchingSection' => [
				'property' => 'searchingSection',
				'type' => 'section',
				'label' => 'Searching',
				'hideInLists' => true,
				'helpLink' => '',
				'permissions' => ['Library Catalog Options'],
				'properties' => [
					'restrictSearchByLibrary' => [
						'property' => 'restrictSearchByLibrary',
						'type' => 'checkbox',
						'label' => 'Restrict Search By Library',
						'description' => 'Whether or not search results should only include titles from this library',
						'hideInLists' => true,
						'forcesReindex' => true,
					],
					'publicListsToInclude' => [
						'property' => 'publicListsToInclude',
						'type' => 'enum',
						'values' => [
							0 => 'No Lists',
							'1' => 'Lists from this library',
							'3' => 'Lists from library list publishers Only',
							'4' => 'Lists from all list publishers',
							'2' => 'All Lists',
						],
						'label' => 'Public Lists To Include',
						'description' => 'Which lists should be included in this scope',
						'forcesListReindex' => true,
						'default' => 4,
					],
					'allowAutomaticSearchReplacements' => [
						'property' => 'allowAutomaticSearchReplacements',
						'type' => 'checkbox',
						'label' => 'Allow Automatic Search Corrections',
						'description' => 'Turn on to allow Aspen Discovery to replace search terms that have no results if the current search term looks like a misspelling.',
						'hideInLists' => true,
						'default' => true,
					],

					'searchBoxSection' => [
						'property' => 'searchBoxSection',
						'type' => 'section',
						'label' => 'Search Box',
						'hideInLists' => true,
						'properties' => [
							'systemsToRepeatIn' => [
								'property' => 'systemsToRepeatIn',
								'type' => 'text',
								'label' => 'Systems To Repeat In',
								'description' => 'A list of library codes that you would like to repeat search in separated by pipes |.',
								'size' => '20',
								'hideInLists' => true,
							],
							'repeatSearchOption' => [
								'property' => 'repeatSearchOption',
								'type' => 'enum',
								'label' => 'Repeat Search Options (requires Restrict Search to Library to be ON)',
								'description' => 'Where to allow repeating search. Valid options are: none, librarySystem, marmot, all',
								'values' => [
									'none' => 'None',
									'librarySystem' => 'Library System',
									'marmot' => 'Consortium',
								],
							],
							'repeatInOnlineCollection' => [
								'property' => 'repeatInOnlineCollection',
								'type' => 'checkbox',
								'label' => 'Repeat In Online Collection',
								'description' => 'Turn on to allow repeat search in the Online Collection.',
								'hideInLists' => true,
								'default' => false,
							],
							'showAdvancedSearchbox' => [
								'property' => 'showAdvancedSearchbox',
								'type' => 'checkbox',
								'label' => 'Show Advanced Search Link',
								'description' => 'Whether or not users should see the advanced search link below the search box.',
								'hideInLists' => true,
								'default' => 1,
							],
						],
					],

					'searchFacetsSection' => [
						'property' => 'searchFacetsSection',
						'type' => 'section',
						'label' => 'Search Facets',
						'hideInLists' => true,
						'properties' => [
							'facetLabel' => [
								'property' => 'facetLabel',
								'type' => 'text',
								'label' => 'Library System Facet Label',
								'description' => 'The label for the library system in the Library System Facet.',
								'size' => '40',
								'hideInLists' => true,
								'maxLength' => 75,
								'forcesReindex' => true,
							],
							'restrictOwningBranchesAndSystems' => [
								'property' => 'restrictOwningBranchesAndSystems',
								'type' => 'checkbox',
								'label' => 'Restrict Library System and Branch Facets to this library',
								'description' => 'Restrict Owning Library and Owning Branches Facets to this library',
								'default' => 1,
								'forcesReindex' => true,
							],
							'showAvailableAtAnyLocation' => [
								'property' => 'showAvailableAtAnyLocation',
								'type' => 'checkbox',
								'label' => 'Show Available At Any Location?',
								'description' => 'Whether or not to show any library Location within the Available At facet',
								'hideInLists' => true,
							],
							'additionalLocationsToShowAvailabilityFor' => [
								'property' => 'additionalLocationsToShowAvailabilityFor',
								'type' => 'text',
								'label' => 'Additional Locations to Include in Available At Facet',
								'description' => 'A list of library codes that you would like included in the available at facet separated by pipes |.',
								'size' => '20',
								'hideInLists' => true,
							],
						],
					],
				],
			],

			'combinedResultsSection' => [
				'property' => 'combinedResultsSection',
				'type' => 'section',
				'label' => 'Combined Results',
				'hideInLists' => true,
				'permissions' => ['Library Catalog Options'],
				'properties' => [
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
						'keyThis' => 'libraryId',
						'keyOther' => 'libraryId',
						'subObjectType' => 'LibraryCombinedResultSection',
						'structure' => $combinedResultsStructure,
						'sortable' => true,
						'storeDb' => true,
						'allowEdit' => true,
						'canEdit' => false,
						'canAddNew' => true,
						'additionalOneToManyActions' => [],
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
					//TODO database column rename for showFavorites to showLists?
					'showFavorites' => [
						'property' => 'showFavorites',
						'type' => 'checkbox',
						'label' => 'Enable User Lists',
						'description' => 'Whether or not users can maintain favorites lists',
						'hideInLists' => true,
						'default' => 1,
					],
					'enableListDescriptions' => [
						'property' => 'enableListDescriptions',
						'type' => 'checkbox',
						'label' => 'Enable List Descriptions & Notes',
						'description' => 'Whether or not users can add descriptions & title notes to their lists',
						'hideInLists' => true,
						'default' => 1,
					],
					'allowableListNames' => [
						'property' => 'allowableListNames',
						'type' => 'text',
						'label' => 'Allowable List Names',
						'description' => 'A pipe separated list of valid names for the patron to choose, leave blank to allow the patron to enter their own name for a list.',
						'hideInLists' => true,
						'default' => '',
						'maxLength' => '500',
					],
					'showConvertListsFromClassic' => [
						'property' => 'showConvertListsFromClassic',
						'type' => 'checkbox',
						'label' => 'Enable Importing Lists From Old Catalog',
						'description' => 'Whether or not users can import lists from the ILS',
						'hideInLists' => true,
						'default' => 0,
					],
					'showWikipediaContent' => [
						'property' => 'showWikipediaContent',
						'type' => 'checkbox',
						'label' => 'Show Wikipedia Content',
						'description' => 'Whether or not Wikipedia content should be shown on author page',
						'default' => '1',
						'hideInLists' => true,
					],
					'showCitationStyleGuides' => [
						'property' => 'showCitationStyleGuides',
						'type' => 'checkbox',
						'label' => 'Show Citation Style Guides',
						'description' => 'Whether or not citations style guides should be shown',
						'default' => '1',
						'hideInLists' => true,
					],
				],
			],

			// Full Record Display //
			'fullRecordSection' => [
				'property' => 'fullRecordSection',
				'type' => 'section',
				'label' => 'Full Record Display',
				'hideInLists' => true,
				'permissions' => ['Library Catalog Options'],
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
						'description' => 'Whether or not sharing on external sites (Twitter, Facebook, Pinterest, etc.) is shown',
						'hideInLists' => true,
						'default' => 1,
					],
				],
			],

			'browseCategoryGroupId' => [
				'property' => 'browseCategoryGroupId',
				'type' => 'enum',
				'values' => $browseCategoryGroups,
				'label' => 'Browse Category Group',
				'description' => 'The group of browse categories to show for this library',
				'hideInLists' => true,
				'permissions' => ['Library Browse Category Options'],
			],

			'holdingsSummarySection' => [
				'property' => 'holdingsSummarySection',
				'type' => 'section',
				'label' => 'Holdings Summary',
				'hideInLists' => true,
				'permissions' => ['Library Catalog Options'],
				'properties' => [
					'showItsHere' => [
						'property' => 'showItsHere',
						'type' => 'checkbox',
						'label' => 'Show It\'s Here',
						'description' => 'Whether or not the holdings summary should show It\'s here based on IP and the currently logged in patron\'s location.',
						'hideInLists' => true,
						'default' => 1,
					],
					'showGroupedHoldCopiesCount' => [
						'property' => 'showGroupedHoldCopiesCount',
						'type' => 'checkbox',
						'label' => 'Show Hold and Copy Counts',
						'description' => 'Whether or not the hold count and copies counts should be visible for grouped works when summarizing formats.',
						'hideInLists' => true,
						'default' => 1,
					],
					'showOnOrderCounts' => [
						'property' => 'showOnOrderCounts',
						'type' => 'checkbox',
						'label' => 'Show On Order Counts',
						'description' => 'Whether or not counts of On Order Items should be shown.',
						'hideInLists' => true,
						'default' => 1,
					],
				],
			],

			'materialsRequestSection' => [
				'property' => 'materialsRequestSection',
				'type' => 'section',
				'label' => 'Materials Request',
				'hideInLists' => true,
				'permissions' => ['Library Materials Request Options'],
				'properties' => [
					'enableMaterialsRequest' => [
						'property' => 'enableMaterialsRequest',
						'type' => 'enum',
						'values' => $materialsRequestOptions,
						'label' => 'Materials Request System',
						'description' => 'Materials Request functionality so patrons can request items not in the catalog.',
						'hideInLists' => true,
						'onchange' => 'return AspenDiscovery.Admin.updateMaterialsRequestFields();',
						'default' => 0,
					],
					'displayMaterialsRequestToPublic' => [
						'property' => 'displayMaterialsRequestToPublic',
						'type' => 'checkbox',
						'label' => 'Turn on Materials Request for Public',
						'description' => 'Whether or not Materials Request should be shown.',
						'hideInLists' => true,
						'default' => 1,
					],
					'allowDeletingILSRequests' => [
						'property' => 'allowDeletingILSRequests',
						'type' => 'checkbox',
						'label' => 'Allow Deleting ILS Materials Requests',
						'description' => 'Whether or not Materials Requests made in the ILS can be deleted.',
						'hideInLists' => true,
						'onchange' => 'return AspenDiscovery.Admin.updateMaterialsRequestFields();',
						'default' => 1,
					],
					'externalMaterialsRequestUrl' => [
						'property' => 'externalMaterialsRequestUrl',
						'type' => 'text',
						'label' => 'External Materials Request URL',
						'description' => 'A link to an external Materials Request System to be used instead of the built in Aspen Discovery system',
						'hideInList' => true,
					],
					'maxRequestsPerYear' => [
						'property' => 'maxRequestsPerYear',
						'type' => 'integer',
						'label' => 'Max Requests Per Year',
						'description' => 'The maximum number of requests that a user can make within a year',
						'hideInLists' => true,
						'default' => 60,
					],
					'maxOpenRequests' => [
						'property' => 'maxOpenRequests',
						'type' => 'integer',
						'label' => 'Max Open Requests',
						'description' => 'The maximum number of requests that a user can have open at one time',
						'hideInLists' => true,
						'default' => 5,
					],
					'newMaterialsRequestSummary' => [
						'property' => 'newMaterialsRequestSummary',
						'type' => 'html',
						'label' => 'New Request Summary',
						'description' => 'Text displayed at the top of Materials Request form to give users important information about the request they submit',
						'size' => '40',
						'maxLength' => '512',
						'allowableTags' => '<p><em><i><strong><b><a><ul><ol><li><h1><h2><h3><h4><h5><h6><h7><pre><code><hr><table><tbody><tr><th><td><caption><img><br><div><span><sub><sup><script>',
						'hideInLists' => true,
					],
					'materialsRequestDaysToPreserve' => [
						'property' => 'materialsRequestDaysToPreserve',
						'type' => 'integer',
						'label' => 'Delete Closed Requests Older than (days)',
						'description' => 'The number of days to preserve closed requests.  Requests will be preserved for a minimum of 366 days.  We suggest preserving for at least 395 days.  Setting to a value of 0 will preserve all requests',
						'hideInLists' => true,
						'default' => 396,
					],

					'materialsRequestFieldsToDisplay' => [
						'property' => 'materialsRequestFieldsToDisplay',
						'type' => 'oneToMany',
						'label' => 'Fields to display on Manage Materials Request Table',
						'description' => 'Fields displayed when materials requests are listed for Managing',
						'keyThis' => 'libraryId',
						'keyOther' => 'libraryId',
						'subObjectType' => 'MaterialsRequestFieldsToDisplay',
						'structure' => $manageMaterialsRequestFieldsToDisplayStructure,
						'sortable' => true,
						'storeDb' => true,
						'allowEdit' => false,
						'canAddNew' => true,
						'canEdit' => false,
					],

					'materialsRequestFormats' => [
						'property' => 'materialsRequestFormats',
						'type' => 'oneToMany',
						'label' => 'Formats of Materials that can be Requested',
						'description' => 'Determine which material formats are available to patrons for request',
						'keyThis' => 'libraryId',
						'keyOther' => 'libraryId',
						'subObjectType' => 'MaterialsRequestFormats',
						'structure' => $materialsRequestFormatsStructure,
						'sortable' => true,
						'storeDb' => true,
						'allowEdit' => false,
						'canEdit' => false,
						'canAddNew' => true,
						'additionalOneToManyActions' => [
							0 => [
								'text' => 'Set Materials Request Formats To Default',
								'url' => '/Admin/Libraries?id=$id&amp;objectAction=defaultMaterialsRequestFormats',
								'class' => 'btn-warning',
							],
						],
					],

					'materialsRequestFormFields' => [
						'property' => 'materialsRequestFormFields',
						'type' => 'oneToMany',
						'label' => 'Materials Request Form Fields',
						'description' => 'Fields that are displayed in the Materials Request Form',
						'keyThis' => 'libraryId',
						'keyOther' => 'libraryId',
						'subObjectType' => 'MaterialsRequestFormFields',
						'structure' => $materialsRequestFormFieldsStructure,
						'sortable' => true,
						'storeDb' => true,
						'allowEdit' => false,
						'canEdit' => false,
						'canAddNew' => true,
						'additionalOneToManyActions' => [
							0 => [
								'text' => 'Set Materials Request Form Structure To Default',
								'url' => '/Admin/Libraries?id=$id&amp;objectAction=defaultMaterialsRequestForm',
								'class' => 'btn-warning',
							],
						],
					],

				],
			],
			'courseReservesSection' => [
				'property' => 'courseReservesSection',
				'type' => 'section',
				'label' => 'Course Reserves',
				'hideInLists' => true,
				'permissions' => [
					'Administer Course Reserves',
					'Library ILS Connection',
				],
				'properties' => [
					'enableCourseReserves' => [
						'property' => 'enableCourseReserves',
						'type' => 'enum',
						'values' => [
							0 => 'None',
							1 => 'Link to ILS Course Reserves',
							2 => 'Aspen Course Reserves',
						],
						'label' => 'Enable Repeat Search in Course Reserves',
						'description' => 'Whether or not patrons can repeat searches within course reserves.',
						'hideInLists' => true,
						'permissions' => [
							'Administer Course Reserves',
							'Library ILS Connection',
						],
					],
					'courseReserveLibrariesToInclude' => [
						'property' => 'courseReserveLibrariesToInclude',
						'type' => 'regularExpression',
						'label' => 'Course Reserve Libraries To Include (regex)',
						'description' => 'A regular expression for the libraries to include in the index',
						'maxLength' => 50,
					],
				],
			],
			'interLibraryLoanSection' => [
				'property' => 'interLibraryLoanSectionSection',
				'type' => 'section',
				'label' => 'Interlibrary loans',
				'hideInLists' => true,
				'permissions' => ['Library ILL Options'],
				'properties' => [
					'interLibraryLoanName' => [
						'property' => 'interLibraryLoanName',
						'type' => 'text',
						'label' => 'Name of Interlibrary Loan Service',
						'description' => 'The name to be displayed in the link to the ILL service ',
						'hideInLists' => true,
						'size' => '80',
					],
					'interLibraryLoanUrl' => [
						'property' => 'interLibraryLoanUrl',
						'type' => 'text',
						'label' => 'Interlibrary Loan URL',
						'description' => 'The link for the ILL Service.',
						'hideInLists' => true,
						'size' => '80',
					],

					'prospectorSection' => [
						'property' => 'prospectorSection',
						'type' => 'section',
						'label' => 'Prospector',
						'hideInLists' => true,
						'helpLink' => '',
						'properties' => [
							'repeatInProspector' => [
								'property' => 'repeatInProspector',
								'type' => 'checkbox',
								'label' => 'Repeat In Prospector',
								'description' => 'Turn on to allow repeat search in Prospector functionality.',
								'hideInLists' => true,
								'default' => 0,
							],
							'prospectorCode' => [
								'property' => 'prospectorCode',
								'type' => 'text',
								'label' => 'Prospector Code',
								'description' => 'The code used to identify this location within Prospector. Leave blank if items for this location are not in Prospector.',
								'hideInLists' => true,
							],
							'enableProspectorIntegration' => [
								'property' => 'enableProspectorIntegration',
								'type' => 'checkbox',
								'label' => 'Enable Prospector Integration',
								'description' => 'Whether or not Prospector Integrations should be displayed for this library.',
								'hideInLists' => true,
								'default' => 0,
							],
							'showProspectorResultsAtEndOfSearch' => [
								'property' => 'showProspectorResultsAtEndOfSearch',
								'type' => 'checkbox',
								'label' => 'Show Prospector Results At End Of Search',
								'description' => 'Whether or not Prospector Search Results should be shown at the end of search results.',
								'hideInLists' => true,
								'default' => 0,
							],
						],
					],
					'worldCatSection' => [
						'property' => 'worldCatSection',
						'type' => 'section',
						'label' => 'WorldCat',
						'hideInLists' => true,
						'helpLink' => '',
						'properties' => [
							'repeatInWorldCat' => [
								'property' => 'repeatInWorldCat',
								'type' => 'checkbox',
								'label' => 'Repeat In WorldCat',
								'description' => 'Turn on to allow repeat search in WorldCat functionality.',
								'hideInLists' => true,
							],
							'worldCatUrl' => [
								'property' => 'worldCatUrl',
								'type' => 'text',
								'label' => 'WorldCat URL',
								'description' => 'A custom World Cat URL to use while searching.',
								'hideInLists' => true,
								'size' => '80',
							],
							'worldCatQt' => [
								'property' => 'worldCatQt',
								'type' => 'text',
								'label' => 'WorldCat QT',
								'description' => 'A custom World Cat QT term to use while searching.',
								'hideInLists' => true,
								'size' => '40',
							],
						],
					],
				],
			],
			'axis360Section' => [
				'property' => 'axis360Section',
				'type' => 'section',
				'label' => 'Axis 360',
				'hideInLists' => true,
				'renderAsHeading' => true,
				'permissions' => ['Library Records included in Catalog'],
				'properties' => [
					'axis360ScopeId' => [
						'property' => 'axis360ScopeId',
						'type' => 'enum',
						'values' => $axis360Scopes,
						'label' => 'Axis 360 Scope',
						'description' => 'The Axis 360 scope to use',
						'hideInLists' => true,
						'default' => -1,
						'forcesReindex' => true,
					],
				],
			],
			'cloudLibrarySection' => [
				'property' => 'cloudLibrarySection',
				'type' => 'section',
				'label' => 'cloudLibrary',
				'hideInLists' => true,
				'renderAsHeading' => true,
				'permissions' => ['Library Records included in Catalog'],
				'properties' => [
					'cloudLibraryScopes' => [
						'property' => 'cloudLibraryScopes',
						'type' => 'oneToMany',
						'keyThis' => 'libraryId',
						'keyOther' => 'libraryId',
						'subObjectType' => 'LibraryCloudLibraryScope',
						'structure' => $cloudLibraryScopeStructure,
						'label' => 'cloudLibrary Scopes',
						'description' => 'The scopes that apply to this library',
						'sortable' => false,
						'storeDb' => true,
						'allowEdit' => true,
						'canEdit' => true,
						'forcesReindex' => true,
						'canAddNew' => true,
					],
				],
			],
			'hooplaSection' => [
				'property' => 'hooplaSection',
				'type' => 'section',
				'label' => 'Hoopla',
				'hideInLists' => true,
				'renderAsHeading' => true,
				'permissions' => ['Library Records included in Catalog'],
				'properties' => [
					'hooplaLibraryID' => [
						'property' => 'hooplaLibraryID',
						'type' => 'integer',
						'label' => 'Hoopla Library ID',
						'description' => 'The ID Number Hoopla uses for this library',
						'hideInLists' => true,
						'forcesReindex' => true,
					],
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
			],
			'overdriveSection' => [
				'property' => 'overdriveSection',
				'type' => 'section',
				'label' => 'OverDrive',
				'hideInLists' => true,
				'renderAsHeading' => true,
				'permissions' => ['Library Records included in Catalog'],
				'properties' => [
					'overDriveScopeId' => [
						'property' => 'overDriveScopeId',
						'type' => 'enum',
						'values' => $overDriveScopes,
						'label' => 'OverDrive Scope',
						'description' => 'The OverDrive scope to use',
						'hideInLists' => true,
						'default' => -1,
						'forcesReindex' => true,
					],
				],
			],
			'genealogySection' => [
				'property' => 'genealogySection',
				'type' => 'section',
				'label' => 'Genealogy',
				'hideInLists' => true,
				'renderAsHeading' => true,
				'permissions' => ['Library Genealogy Content'],
				'properties' => [
					'enableGenealogy' => [
						'property' => 'enableGenealogy',
						'type' => 'checkbox',
						'label' => 'Enable Genealogy Functionality',
						'description' => 'Whether or not patrons can search genealogy.',
						'hideInLists' => true,
						'default' => 0,
					],
				],
			],

			'oaiSection' => [
				'property' => 'oaiSection',
				'type' => 'section',
				'label' => 'Open Archives Results',
				'hideInLists' => true,
				'renderAsHeading' => true,
				'permissions' => ['Library Archive Options'],
				'properties' => [
					'enableOpenArchives' => [
						'property' => 'enableOpenArchives',
						'type' => 'checkbox',
						'label' => 'Allow Searching Open Archives',
						'description' => 'Whether or not information from indexed Open Archives is shown.',
						'hideInLists' => true,
						'default' => 0,
					],
				],
			],

			'webBuilderSection' => [
				'property' => 'webBuilderSection',
				'type' => 'section',
				'label' => 'Web Builder Results',
				'hideInLists' => true,
				'renderAsHeading' => true,
				'permissions' => ['Library Web Builder Options'],
				'properties' => [
					'enableWebBuilder' => [
						'property' => 'enableWebBuilder',
						'type' => 'checkbox',
						'label' => 'Allow searching locally created web content',
						'description' => 'Whether or not information from indexed local web content is shown.',
						'hideInLists' => true,
						'default' => 0,
					],
				],
			],

			'ebscoSection' => [
				'property' => 'ebscoSection',
				'type' => 'section',
				'label' => 'EBSCO',
				'hideInLists' => true,
				'renderAsHeading' => true,
				'permissions' => ['Library EDS Options'],
				'properties' => [
					'edsSettingsId' => [
						'property' => 'edsSettingsId',
						'type' => 'enum',
						'values' => $edsSettings,
						'label' => 'EDS Settings',
						'description' => 'The EDS Settings to use for connection',
						'hideInLists' => true,
						'default' => -1,
					],
					'ebscohostSearchSettingId' => [
						'property' => 'ebscohostSearchSettingId',
						'type' => 'enum',
						'values' => $ebscohostSettings,
						'label' => 'EBSCOhost Settings',
						'description' => 'The EBSCOhost SEarch Settings to use for connection',
						'hideInLists' => true,
						'default' => -1,
					],
				],
			],

			'casSection' => [
				'property' => 'casSection',
				'type' => 'section',
				'label' => 'CAS Single Sign On',
				'hideInLists' => true,
				'helpLink' => '',
				'permissions' => ['Library ILS Connection'],
				'properties' => [
					'casHost' => [
						'property' => 'casHost',
						'type' => 'text',
						'label' => 'CAS Host',
						'description' => 'The host to use for CAS authentication',
						'hideInLists' => true,
					],
					'casPort' => [
						'property' => 'casPort',
						'type' => 'integer',
						'label' => 'CAS Port',
						'description' => 'The port to use for CAS authentication (typically 443)',
						'hideInLists' => true,
					],
					'casContext' => [
						'property' => 'casContext',
						'type' => 'text',
						'label' => 'CAS Context',
						'description' => 'The context to use for CAS',
						'hideInLists' => true,
					],
				],
			],

			'dplaSection' => [
				'property' => 'dplaSection',
				'type' => 'section',
				'label' => 'DPLA',
				'hideInLists' => true,
				'helpLink' => '',
				'renderAsHeading' => true,
				'permissions' => ['Library Archive Options'],
				'properties' => [
					'includeDplaResults' => [
						'property' => 'includeDplaResults',
						'type' => 'checkbox',
						'label' => 'Include DPLA content in search results',
						'description' => 'Whether or not DPLA data should be included for this library.',
						'hideInLists' => true,
						'default' => 0,
					],
				],
			],

			'holidays' => [
				'property' => 'holidays',
				'type' => 'oneToMany',
				'label' => 'Holidays',
				'renderAsHeading' => true,
				'description' => 'Holidays (automatically loaded from Koha)',
				'keyThis' => 'libraryId',
				'keyOther' => 'libraryId',
				'subObjectType' => 'Holiday',
				'structure' => $holidaysStructure,
				'sortable' => false,
				'storeDb' => true,
				'permissions' => ['Library Holidays'],
				'canAddNew' => true,
			],

			'libraryLinks' => [
				'property' => 'libraryLinks',
				'type' => 'oneToMany',
				'label' => 'Menu Links',
				'renderAsHeading' => true,
				'description' => 'Links To Show in the menu',
				'keyThis' => 'libraryId',
				'keyOther' => 'libraryId',
				'subObjectType' => 'LibraryLink',
				'structure' => $libraryLinksStructure,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => true,
				'permissions' => ['Library Menu'],
				'canAddNew' => true,
			],

			'recordsOwned' => [
				'property' => 'recordsOwned',
				'type' => 'oneToMany',
				'label' => 'Records Owned',
				'renderAsHeading' => true,
				'description' => 'Information about what records are owned by the library',
				'keyThis' => 'libraryId',
				'keyOther' => 'libraryId',
				'subObjectType' => 'LibraryRecordOwned',
				'structure' => $libraryRecordOwnedStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'forcesReindex' => true,
				'permissions' => ['Library Records included in Catalog'],
				'canAddNew' => true,
			],

			'recordsToInclude' => [
				'property' => 'recordsToInclude',
				'type' => 'oneToMany',
				'label' => 'Records To Include',
				'renderAsHeading' => true,
				'description' => 'Information about what records to include in this scope',
				'keyThis' => 'libraryId',
				'keyOther' => 'libraryId',
				'subObjectType' => 'LibraryRecordToInclude',
				'structure' => $libraryRecordToIncludeStructure,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'forcesReindex' => true,
				'permissions' => ['Library Records included in Catalog'],
				'canAddNew' => true,
			],

			'sideLoadScopes' => [
				'property' => 'sideLoadScopes',
				'type' => 'oneToMany',
				'label' => 'Side Loaded eContent Scopes',
				'renderAsHeading' => true,
				'description' => 'Information about what Side Loads to include in this scope',
				'keyThis' => 'libraryId',
				'keyOther' => 'libraryId',
				'subObjectType' => 'LibrarySideLoadScope',
				'structure' => $librarySideLoadScopeStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => true,
				'forcesReindex' => true,
				'permissions' => ['Library Records included in Catalog'],
				'canAddNew' => true,
			],

			'aspenLiDASection' => [
				'property' => 'aspenLiDASection',
				'type' => 'section',
				'label' => 'Aspen LiDA',
				'hideInLists' => true,
				'renderAsHeading' => true,
				'permissions' => ['Administer Aspen LiDA Settings'],
				'properties' => [
					'lidaNotificationSettingId' => [
						'property' => 'lidaNotificationSettingId',
						'type' => 'enum',
						'values' => $notificationSettings,
						'label' => 'Notification Settings',
						'description' => 'The Notification Settings to use for Aspen LiDA',
						'hideInLists' => true,
						'default' => -1,
					],
					'lidaQuickSearchId' => [
						'property' => 'lidaQuickSearchId',
						'type' => 'enum',
						'values' => $quickSearchSettings,
						'label' => 'Quick Search Settings',
						'description' => 'The Quick Search Settings to use for Aspen LiDA',
						'hideInLists' => true,
						'default' => -1,
					],
				],
			],
		];

		//Update settings based on what we have access to
		global $configArray;
		$ils = $configArray['Catalog']['ils'];
		if ($ils != 'Millennium' && $ils != 'Sierra') {
			unset($structure['displaySection']['properties']['enableCourseReserves']);
			unset($structure['ilsSection']['properties']['scope']);
			unset($structure['ilsSection']['properties']['useScope']);
		}
		if ($ils == 'Koha') {
			unset($structure['ilsSection']['properties']['userProfileSection']['properties']['showWorkPhoneInProfile']);
			unset($structure['ilsSection']['properties']['userProfileSection']['properties']['showNoticeTypeInProfile']);
			unset($structure['ilsSection']['properties']['userProfileSection']['properties']['addSMSIndicatorToPhone']);
			unset($structure['ilsSection']['properties']['userProfileSection']['properties']['maxFinesToAllowAccountUpdates']);
			unset($structure['ilsSection']['properties']['selfRegistrationSection']['properties']['promptForBirthDateInSelfReg']);
			unset($structure['ilsSection']['properties']['selfRegistrationSection']['properties']['selfRegistrationTemplate']);
		} else {
			unset($structure['ilsSection']['properties']['selfRegistrationSection']['properties']['bypassReviewQueueWhenUpdatingProfile']);
		}
		if (!$configArray['CAS']['enabled']) {
			unset($structure['casSection']);
		}
		global $enabledModules;
		if (!array_key_exists('EBSCO EDS', $enabledModules)) {
			unset($structure['edsSection']);
		}
		if (!array_key_exists('Genealogy', $enabledModules)) {
			unset($structure['genealogySection']);
		}
		if (!array_key_exists('OverDrive', $enabledModules)) {
			unset($structure['overdriveSection']);
		}
		if (!array_key_exists('Hoopla', $enabledModules)) {
			unset($structure['hooplaSection']);
		}
		if (!array_key_exists('Cloud Library', $enabledModules)) {
			unset($structure['cloudLibrarySection']);
		}
		if (!array_key_exists('Side Loads', $enabledModules)) {
			unset($structure['sideLoadScopes']);
		}
		if (!array_key_exists('Open Archives', $enabledModules)) {
			unset($structure['oaiSection']);
		}
		return $structure;
	}

	static $searchLibrary = [];

	static function getSearchLibrary($searchSource = null) {
		if ($searchSource == null) {
			global $searchSource;
		}
		if ($searchSource == 'combined') {
			$searchSource = 'local';
		}
		if (!array_key_exists($searchSource, Library::$searchLibrary)) {
			$scopingSetting = $searchSource;
			if ($scopingSetting == null) {
				return null;
			} elseif ($scopingSetting == 'local' || $scopingSetting == 'econtent' || $scopingSetting == 'library' || $scopingSetting == 'location' || $scopingSetting == 'websites' || $scopingSetting == 'lists' || $scopingSetting == 'open_archives' || $scopingSetting == 'course_reserves') {
				Library::$searchLibrary[$searchSource] = Library::getActiveLibrary();
			} elseif ($scopingSetting == 'marmot' || $scopingSetting == 'unscoped') {
				//Get the default library
				$library = new Library();
				$library->isDefault = true;
				$library->find();
				if ($library->getNumResults() > 0) {
					$library->fetch();
					Library::$searchLibrary[$searchSource] = clone($library);
				} else {
					Library::$searchLibrary[$searchSource] = null;
				}
			} else {
				$location = Location::getSearchLocation();
				if (is_null($location)) {
					//Check to see if we have a library for the subdomain
					$library = new Library();
					$library->subdomain = $scopingSetting;
					$library->find();
					if ($library->getNumResults() > 0) {
						$library->fetch();
						Library::$searchLibrary[$searchSource] = clone($library);
						return clone($library);
					} else {
						Library::$searchLibrary[$searchSource] = null;
					}
				} else {
					Library::$searchLibrary[$searchSource] = self::getLibraryForLocation($location->locationId);
				}
			}
		}
		return Library::$searchLibrary[$searchSource];
	}

	static function getActiveLibrary() {
		global $library;
		//First check to see if we have a library loaded based on subdomain (loaded in index)
		if (isset($library)) {
			return $library;
		}
		//If there is only one library, that library is active by default.
		$activeLibrary = new Library();
		$activeLibrary->find();
		if ($activeLibrary->getNumResults() == 1) {
			$activeLibrary->fetch();
			return $activeLibrary;
		} elseif ($activeLibrary->getNumResults() == 0) {
			echo("No libraries are configured for the system.  Please configure at least one library before proceeding.");
			die();
		}
		//Next check to see if we are in a library.
		global $locationSingleton;
		$physicalLocation = $locationSingleton->getActiveLocation();
		if (!is_null($physicalLocation)) {
			//Load the library based on the home branch for the user
			return self::getLibraryForLocation($physicalLocation->locationId);
		}

		//Return the active library
		$activeLibrary->isDefault = 1;
		$activeLibrary->find(true);
		if ($activeLibrary->getNumResults() == 0) {
			echo("There is not a default library configured in the system.  Please configure one default library before proceeding.");
			die();
		} elseif ($activeLibrary->getNumResults() > 1) {
			echo("There are multiple default libraries configured in the system.  Please set only one library to be the default before proceeding.");
			die();
		}
		return $activeLibrary;
	}

	/**
	 * @param User|null $tmpUser
	 * @return Library|null
	 */
	static function getPatronHomeLibrary($tmpUser = null) {
		//Finally check to see if the user has logged in and if so, use that library
		if ($tmpUser != null) {
			return self::getLibraryForLocation($tmpUser->homeLocationId);
		}
		if (UserAccount::isLoggedIn()) {
			//Load the library based on the home branch for the user
			return self::getLibraryForLocation(UserAccount::getUserHomeLocationId());
		} else {
			return null;
		}
	}

	static function getLibraryForLocation($locationId) {
		if (isset($locationId)) {
			$libLookup = new Library();
			$libLookup->whereAdd('libraryId = (SELECT libraryId FROM location WHERE locationId = ' . $libLookup->escape($locationId) . ')');
			$libLookup->find();
			if ($libLookup->getNumResults() > 0) {
				$libLookup->fetch();
				return clone $libLookup;
			}
		}
		return null;
	}

	public function validateSso($attrField, $fallbackField, $errorMessage) {
		$validationResults = [
			'validatedOk' => true,
			'errors' => [],
		];
		// Only validate everything else if we have a populated SSO entity ID
		// (we infer SSO auth usage from this)
		if (!$this->ssoEntityId || strlen($this->ssoEntityId) == 0) {
			return $validationResults;
		}
		if ((!$this->$attrField || strlen($this->$attrField) == 0) && (!$this->$fallbackField || strlen($this->$fallbackField) == 0)) {
			$validationResults['errors'][] = $errorMessage;
			$validationResults['validatedOk'] = false;
		}
		return $validationResults;

	}

	public function validatePatronType() {
		return $this->validateSso('ssoPatronTypeAttr', 'ssoPatronTypeFallback', 'Single sign-on patron type: You must enter either an identity provider attribute name or fallback value');
	}

	public function validateLibraryId() {
		return $this->validateSso('ssoLibraryIdAttr', 'ssoLibraryIdFallback', 'Single sign-on library ID: You must enter either an identity provider attribute name or fallback value');
	}

	public function validateCategoryId() {
		return $this->validateSso('ssoCategoryIdAttr', 'ssoCategoryIdFallback', 'Single sign-on category ID: You must enter either an identity provider attribute name or fallback value');
		$validationResults = [
			'validatedOk' => true,
			'errors' => [],
		];
	}

	public function __get($name) {
		if ($name == "holidays") {
			if (!isset($this->holidays) && $this->libraryId) {
				$this->holidays = [];
				$holiday = new Holiday();
				$holiday->libraryId = $this->libraryId;
				$holiday->orderBy('date');
				$holiday->find();
				while ($holiday->fetch()) {
					$this->holidays[$holiday->id] = clone($holiday);
				}
			}
			return $this->holidays;
		} elseif ($name == 'libraryLinks') {
			if (!isset($this->_libraryLinks) && $this->libraryId) {
				$this->_libraryLinks = [];
				$libraryLink = new LibraryLink();
				$libraryLink->libraryId = $this->libraryId;
				$libraryLink->orderBy('weight');
				$libraryLink->find();
				while ($libraryLink->fetch()) {
					$this->_libraryLinks[$libraryLink->id] = clone($libraryLink);
				}
			}
			return $this->_libraryLinks;
		} elseif ($name == 'recordsOwned') {
			if (!isset($this->recordsOwned) && $this->libraryId) {
				$this->recordsOwned = [];
				$object = new LibraryRecordOwned();
				$object->libraryId = $this->libraryId;
				$object->find();
				while ($object->fetch()) {
					$this->recordsOwned[$object->id] = clone($object);
				}
			}
			return $this->recordsOwned;
		} elseif ($name == 'recordsToInclude') {
			if (!isset($this->recordsToInclude) && $this->libraryId) {
				$this->recordsToInclude = [];
				$object = new LibraryRecordToInclude();
				$object->libraryId = $this->libraryId;
				$object->orderBy('weight');
				$object->find();
				while ($object->fetch()) {
					$this->recordsToInclude[$object->id] = clone($object);
				}
			}
			return $this->recordsToInclude;
		} elseif ($name == 'sideLoadScopes') {
			if (!isset($this->sideLoadScopes) && $this->libraryId) {
				$this->sideLoadScopes = [];
				$object = new LibrarySideLoadScope();
				$object->libraryId = $this->libraryId;
				$object->find();
				while ($object->fetch()) {
					$this->sideLoadScopes[$object->id] = clone($object);
				}
			}
			return $this->sideLoadScopes;
		} elseif ($name == 'materialsRequestFieldsToDisplay') {
			if (!isset($this->materialsRequestFieldsToDisplay) && $this->libraryId) {
				$this->materialsRequestFieldsToDisplay = [];
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
			return $this->getMaterialsRequestFormats();
		} elseif ($name == 'materialsRequestFormFields') {
			return $this->getMaterialsRequestFormFields();
		} elseif ($name == 'combinedResultSections') {
			if (!isset($this->combinedResultSections) && $this->libraryId) {
				$this->combinedResultSections = [];
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
		} elseif ($name == 'cloudLibraryScopes') {
			return $this->getCloudLibraryScopes();
		} elseif ($name == 'quickSearches') {
			return $this->getQuickSearches();
		} else {
			return $this->_data[$name] ?? null;
		}
		return null;
	}

	public function __set($name, $value) {
		if ($name == "holidays") {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->holidays = $value;
		} elseif ($name == 'libraryLinks') {
			$this->_libraryLinks = $value;
		} elseif ($name == 'recordsOwned') {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->recordsOwned = $value;
		} elseif ($name == 'recordsToInclude') {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->recordsToInclude = $value;
		} elseif ($name == 'sideLoadScopes') {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->sideLoadScopes = $value;
		} elseif ($name == 'materialsRequestFieldsToDisplay') {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->materialsRequestFieldsToDisplay = $value;
		} elseif ($name == 'materialsRequestFormats') {
			$this->_materialsRequestFormats = $value;
		} elseif ($name == 'materialsRequestFormFields') {
			$this->_materialsRequestFormFields = $value;
		} elseif ($name == 'combinedResultSections') {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->combinedResultSections = $value;
		} elseif ($name == 'cloudLibraryScopes') {
			$this->_cloudLibraryScopes = $value;
		} elseif ($name == 'quickSearches') {
			$this->_quickSearches = $value;
		} else {
			$this->_data[$name] = $value;
		}
	}

	/**
	 * Override the update functionality to save related objects
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update($context = '') {
		//Updates to properly update settings based on the ILS
		global $configArray;
		$ils = $configArray['Catalog']['ils'];
		if ($ils == 'Koha') {
			$this->showWorkPhoneInProfile = 0;
			$this->showNoticeTypeInProfile = 0;
			$this->addSMSIndicatorToPhone = 0;
		}
		$ret = false;
		// We process the SSO additional work before the DB is updated because we set
		// a value on this object which needs to be persisted to the DB
		$ssoOk = $this->processSso();
		if ($ssoOk instanceof AspenError) {
			$this->setLastError($ssoOk->getMessage());
		} else {
			$ret = parent::update();
		}
		if ($ret !== FALSE) {
			$this->saveHolidays();
			$this->saveRecordsOwned();
			$this->saveRecordsToInclude();
			$this->saveSideLoadScopes();
			$this->saveMaterialsRequestFieldsToDisplay();
			$this->saveMaterialsRequestFormFields();
			$this->saveLibraryLinks();
			$this->saveCombinedResultSections();
			$this->saveCloudLibraryScopes();
			//$this->saveQuickSearches();
		}
		if ($this->_patronNameDisplayStyleChanged) {
			$libraryLocations = new Location();
			$libraryLocations->libraryId = $this->libraryId;
			$libraryLocations->find();
			while ($libraryLocations->fetch()) {
				$user = new User();
				/** @noinspection SqlResolve */
				$user->query("update user set displayName = '' where homeLocationId = {$libraryLocations->locationId}");
			}
		}
		// Do this last so that everything else can update even if we get an error here
		$deleteCheck = $this->saveMaterialsRequestFormats();
		if ($deleteCheck instanceof AspenError) {
			$this->setLastError($deleteCheck->getMessage());
			$ret = false;
		}

		return $ret;
	}

	/**
	 * @param string $propertyName
	 * @param $newValue
	 * @param array|null $propertyStructure
	 *
	 * @return boolean true if the property changed, or false if it did not
	 * @noinspection PhpUnused
	 */
	public function setProperty($propertyName, $newValue, $propertyStructure): bool {
		$propertyChanged = parent::setProperty($propertyName, $newValue, $propertyStructure);
		if ($propertyName == 'patronNameDisplayStyle' && $propertyChanged) {
			$this->_patronNameDisplayStyleChanged = true;
		}
		return $propertyChanged;
	}

	/**
	 * Override the insert functionality to save the related objects
	 *
	 * @see DB/DB_DataObject::insert()
	 */
	public function insert($context = '') {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveHolidays();
			$this->saveRecordsOwned();
			$this->saveRecordsToInclude();
			$this->saveSideLoadScopes();
			$this->saveMaterialsRequestFieldsToDisplay();
			$this->saveMaterialsRequestFormats();
			$this->saveMaterialsRequestFormFields();
			$this->saveLibraryLinks();
			$this->saveCombinedResultSections();
			$this->saveCloudLibraryScopes();
			//$this->saveQuickSearches();
			$this->processSso();
		}
		return $ret;
	}

	public function saveLibraryLinks() {
		if (isset ($this->_libraryLinks) && is_array($this->_libraryLinks)) {
			$this->saveOneToManyOptions($this->_libraryLinks, 'libraryId');
			unset($this->_libraryLinks);
		}
	}

	public function saveRecordsOwned() {
		if (isset ($this->recordsOwned) && is_array($this->recordsOwned)) {
			$this->saveOneToManyOptions($this->recordsOwned, 'libraryId');
			unset($this->recordsOwned);
		}
	}

	public function saveRecordsToInclude() {
		if (isset ($this->recordsToInclude) && is_array($this->recordsToInclude)) {
			$this->saveOneToManyOptions($this->recordsToInclude, 'libraryId');
			unset($this->recordsToInclude);
		}
	}

	public function saveSideLoadScopes() {
		if (isset ($this->sideLoadScopes) && is_array($this->sideLoadScopes)) {
			$this->saveOneToManyOptions($this->sideLoadScopes, 'libraryId');
			unset($this->sideLoadScopes);
		}
	}

	public function saveMaterialsRequestFieldsToDisplay() {
		if (isset ($this->materialsRequestFieldsToDisplay) && is_array($this->materialsRequestFieldsToDisplay)) {
			$this->saveOneToManyOptions($this->materialsRequestFieldsToDisplay, 'libraryId');
			unset($this->materialsRequestFieldsToDisplay);
		}
	}

	public function saveMaterialsRequestFormats() {
		if (isset ($this->_materialsRequestFormats) && is_array($this->_materialsRequestFormats)) {
			/** @var MaterialsRequestFormats $object */
			foreach ($this->_materialsRequestFormats as $object) {
				if ($object->_deleteOnSave == true) {
					$deleteCheck = $object->delete();
					if (!$deleteCheck) {
						$errorString = "Cannot delete {$object->format} because Materials Request(s) are present for the format.";
						return new AspenError($errorString);
					}
				} else {
					if (isset($object->id) && is_numeric($object->id)) { // (negative ids need processed with insert)
						$object->update();
					} else {
						$object->libraryId = $this->libraryId;
						$object->insert();
					}
				}
			}
			unset($this->_materialsRequestFormats);
		}
		return true;
	}

	public function saveMaterialsRequestFormFields() {
		if (isset ($this->_materialsRequestFormFields) && is_array($this->_materialsRequestFormFields)) {
			$this->saveOneToManyOptions($this->_materialsRequestFormFields, 'libraryId');
			unset($this->_materialsRequestFormFields);
		}
	}

	/**
	 * @return LibraryCloudLibraryScope[]
	 */
	public function getCloudLibraryScopes(): ?array {
		if (!isset($this->_cloudLibraryScopes) && $this->libraryId) {
			$this->_cloudLibraryScopes = [];
			$cloudLibraryScope = new LibraryCloudLibraryScope();
			$cloudLibraryScope->libraryId = $this->libraryId;
			if ($cloudLibraryScope->find()) {
				while ($cloudLibraryScope->fetch()) {
					$this->_cloudLibraryScopes[$cloudLibraryScope->id] = clone $cloudLibraryScope;
				}
			}
		}
		return $this->_cloudLibraryScopes;
	}

	public function saveCloudLibraryScopes() {
		if (isset ($this->_cloudLibraryScopes) && is_array($this->_cloudLibraryScopes)) {
			$this->saveOneToManyOptions($this->_cloudLibraryScopes, 'libraryId');
			unset($this->_cloudLibraryScopes);
		}
	}

	public function clearMaterialsRequestFormFields() {
		$this->clearOneToManyOptions('MaterialsRequestFormFields', 'libraryId');
		/** @noinspection PhpUndefinedFieldInspection */
		$this->materialsRequestFormFields = [];
	}

	public function clearMaterialsRequestFormats() {
		$this->clearOneToManyOptions('MaterialsRequestFormats', 'libraryId');
		$this->_materialsRequestFormats = [];
	}

	public function saveCombinedResultSections() {
		if (isset ($this->combinedResultSections) && is_array($this->combinedResultSections)) {
			$this->saveOneToManyOptions($this->combinedResultSections, 'libraryId');
			unset($this->combinedResultSections);
		}
	}

	public function saveHolidays() {
		if (isset ($this->holidays) && is_array($this->holidays)) {
			$this->saveOneToManyOptions($this->holidays, 'libraryId');
			unset($this->holidays);
		}
	}

	public function getNumLocationsForLibrary() {
		$location = new Location;
		$location->libraryId = $this->libraryId;
		return $location->count();
	}

	public function getNumSearchLocationsForLibrary() {
		$location = new Location;
		$location->libraryId = $this->libraryId;
		$location->createSearchInterface = 1;
		return $location->count();
	}

	protected $_browseCategoryGroup = null;

	public function getBrowseCategoryGroup() {
		if ($this->_browseCategoryGroup == null) {
			require_once ROOT_DIR . '/sys/Browse/BrowseCategoryGroup.php';
			$browseCategoryGroup = new BrowseCategoryGroup();
			$browseCategoryGroup->id = $this->browseCategoryGroupId;
			if ($browseCategoryGroup->find(true)) {
				$this->_browseCategoryGroup = $browseCategoryGroup;
			}
		}
		return $this->_browseCategoryGroup;
	}

	protected $_groupedWorkDisplaySettings = null;

	/** @return GroupedWorkDisplaySetting */
	public function getGroupedWorkDisplaySettings() {
		if ($this->_groupedWorkDisplaySettings == null) {
			try {
				require_once ROOT_DIR . '/sys/Grouping/GroupedWorkDisplaySetting.php';
				$groupedWorkDisplaySettings = new GroupedWorkDisplaySetting();
				$groupedWorkDisplaySettings->id = $this->groupedWorkDisplaySettingId;
				if ($groupedWorkDisplaySettings->find(true)) {
					$this->_groupedWorkDisplaySettings = $groupedWorkDisplaySettings;
				} else {
					$this->_groupedWorkDisplaySettings = GroupedWorkDisplaySetting::getDefaultDisplaySettings();
				}

			} catch (Exception $e) {
				global $logger;
				$logger->log('Error loading grouped work display settings ' . $e, Logger::LOG_ERROR);
				$this->_groupedWorkDisplaySettings = GroupedWorkDisplaySetting::getDefaultDisplaySettings();
			}
		}
		return $this->_groupedWorkDisplaySettings;
	}

	protected $_layoutSettings = null;

	/** @return LayoutSetting */
	public function getLayoutSettings() {
		if ($this->_layoutSettings == null) {
			try {
				require_once ROOT_DIR . '/sys/Theming/LayoutSetting.php';
				$this->_layoutSettings = new LayoutSetting();
				$this->_layoutSettings->id = $this->layoutSettingId;
				$this->_layoutSettings->find(true);
			} catch (Exception $e) {
				global $logger;
				$logger->log('Error loading grouped work display settings ' . $e, Logger::LOG_ERROR);
			}
		}
		return $this->_layoutSettings;
	}

	function getEditLink($context): string {
		return '/Admin/Libraries?objectAction=edit&id=' . $this->libraryId;
	}

	/**
	 * @param boolean $restrictByHomeLibrary whether or not only the patron's home library should be returned
	 * @return array
	 */
	static function getLibraryList($restrictByHomeLibrary): array {
		$library = new Library();
		$library->orderBy('displayName');
		if ($restrictByHomeLibrary) {
			$homeLibrary = Library::getPatronHomeLibrary();
			if ($homeLibrary != null) {
				$library->libraryId = $homeLibrary->libraryId;
			}
		}
		$library->find();
		$libraryList = [];
		while ($library->fetch()) {
			$libraryList[$library->libraryId] = $library->displayName;
		}
		return $libraryList;
	}

	static $libraryListAsObjects = null;

	/**
	 * @param boolean $restrictByHomeLibrary whether or not only the patron's home library should be returned
	 * @return Library[]
	 */
	static function getLibraryListAsObjects($restrictByHomeLibrary): array {
		if (Library::$libraryListAsObjects == null) {
			$library = new Library();
			$library->orderBy('displayName');
			if ($restrictByHomeLibrary) {
				$homeLibrary = Library::getPatronHomeLibrary();
				if ($homeLibrary != null) {
					$library->libraryId = $homeLibrary->libraryId;
				}
			}
			$library->find();
			Library::$libraryListAsObjects = [];
			while ($library->fetch()) {
				Library::$libraryListAsObjects[$library->libraryId] = clone $library;
			}
		}
		return Library::$libraryListAsObjects;
	}

	/** @var OverDriveScope */
	private $_overdriveScope = null;

	public function getOverdriveScope() {
		if ($this->_overdriveScope == null && $this->overDriveScopeId > 0) {
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveScope.php';
			$this->_overdriveScope = new OverDriveScope();
			$this->_overdriveScope->id = $this->overDriveScopeId;
			$this->_overdriveScope->find(true);
		}
		return $this->_overdriveScope;
	}


	private $_materialsRequestFormFields;

	public function setMaterialsRequestFormFields($value) {
		$this->_materialsRequestFormFields = $value;
	}

	/**
	 * @return array|null
	 */
	public function getMaterialsRequestFormFields() {
		if (!isset($this->_materialsRequestFormFields) && $this->libraryId) {
			$this->_materialsRequestFormFields = [];
			$materialsRequestFormFields = new MaterialsRequestFormFields();
			$materialsRequestFormFields->libraryId = $this->libraryId;
			$materialsRequestFormFields->orderBy('weight');
			if ($materialsRequestFormFields->find()) {
				while ($materialsRequestFormFields->fetch()) {
					$this->_materialsRequestFormFields[$materialsRequestFormFields->id] = clone $materialsRequestFormFields;
				}
			}
		}
		return $this->_materialsRequestFormFields;
	}

	private $_materialsRequestFormats;

	public function setMaterialsRequestFormats($value) {
		$this->_materialsRequestFormats = $value;
	}

	/**
	 * @return array|null
	 */
	public function getMaterialsRequestFormats() {
		if (!isset($this->_materialsRequestFormats) && $this->libraryId) {
			$this->_materialsRequestFormats = [];
			$materialsRequestFormats = new MaterialsRequestFormats();
			$materialsRequestFormats->libraryId = $this->libraryId;
			$materialsRequestFormats->orderBy('weight');
			if ($materialsRequestFormats->find()) {
				while ($materialsRequestFormats->fetch()) {
					$this->_materialsRequestFormats[$materialsRequestFormats->id] = clone $materialsRequestFormats;
				}
			}
		}
		return $this->_materialsRequestFormats;
	}

	/**
	 * @return Location[]
	 */
	public function getLocations(): array {
		$locations = [];
		$location = new Location();
		$location->orderBy('isMainBranch desc');
		$location->orderBy('displayName');
		$location->libraryId = $this->libraryId;
		$location->find();
		while ($location->fetch()) {
			$locations[$location->locationId] = clone($location);
		}
		return $locations;
	}

	/**
	 * @return array|null
	 */
	public function getQuickSearches() {
		$quickSearches = [];
		$quickSearchSettings = new QuickSearchSetting();
		$quickSearchSettings->id = $this->lidaQuickSearchId;
		if ($quickSearchSettings->find()) {
			$quickSearch = new QuickSearch();
			$quickSearch->quickSearchSettingId = $quickSearchSettings->id;
			$quickSearch->orderBy('weight');
			if ($quickSearch->find()) {
				while ($quickSearch->fetch()) {
					$quickSearches[$quickSearch->id] = clone $quickSearch;
				}
			}
		}

		return $quickSearches;
	}

	/**
	 * @return array|null
	 */
	public function getLiDANotifications() {
		$lidaNotifications = [];

		$notificationSettings = new NotificationSetting();
		$notificationSettings->id = $this->lidaNotificationSettingId;
		if ($notificationSettings->find(true)) {
			$lidaNotifications = clone $notificationSettings;
		}

		return $lidaNotifications;
	}

// If the URL of the XML metadata has changed in any way, and is populated,
// we need to use it to fetch the metadata and store the metadata's filename
// in the DB, otherwise we delete the file
	public function processSso() {
		if (is_array($this->_changedFields) && in_array('ssoXmlUrl', $this->_changedFields)) {
			$filename = $this->fetchAndStoreSsoMetadata();
			if (!$filename instanceof AspenError) {
				// Update the ssoMetadataFilename in the DB
				$this->ssoMetadataFilename = $filename;
			}
			return $filename;
		} else {
			return false;
		}
	}

	// Fetch the XML metadata from an IdP (using the URL specified in the config)
	// and store it
	public function fetchAndStoreSsoMetadata() {
		global $logger;
		global $configArray;
		global $serverName;
		$ssoXmlDataPath = '/data/aspen-discovery/sso_metadata/';
		$url = trim($this->ssoXmlUrl);
		if (strlen($url) > 0) {
			// We've got a new or updated URL
			// First try and retrieve the metadata
			$curlWrapper = new CurlWrapper();
			$curlWrapper->setTimeout(10);
			$xml = $curlWrapper->curlGetPage($url);
			if (strlen($xml) > 0) {
				// Check it's a valid SAML message
				try {
					require_once '/usr/share/simplesamlphp/lib/_autoload.php';
					\SimpleSAML\Utils\XML::checkSAMLMessage($xml, 'saml-meta');
				} catch (Exception $e) {
					$logger->log($e, Logger::LOG_ERROR);
					return new AspenError('Unable to use SSO IdP metadata, please check "URL of service metadata XML"');
				}
				$fileName = $serverName . '.xml';
				$ssoMetadataFilename = $ssoXmlDataPath . $fileName;
				$written = file_put_contents($ssoMetadataFilename, $xml);
				if ($written === false) {
					$logger->log('Failed to write SSO metadata to ' . $ssoMetadataFilename . ' for site ' . $configArray['Site']['title'], Logger::LOG_ERROR);
					return new AspenError('Unable to use SSO IdP metadata, cannot create XML file');
				} else {
					chmod($ssoMetadataFilename, 0764);
				}
			} else {
				$logger->log('Failed to retrieve any SSO metadata from ' . $url . ' for site ' . $configArray['Site']['title'], Logger::LOG_ERROR);
				return new AspenError('Unable to use SSO IdP metadata, did not receive any metadata, please check "URL of service metadata XML"');
			}
			return $fileName;
		} else {
			// The URL has been removed
			// We don't remove the metadata file because
			// another site may use it
			return '';
		}
	}

	public function getApiInfo(): array {
		global $configArray;
		global $interface;
		$apiInfo = [
			'libraryId' => $this->libraryId,
			'isDefault' => $this->isDefault,
			'baseUrl' => $this->baseUrl,
			'displayName' => $this->displayName,
			'homeLink' => $this->homeLink,
			'twitterLink' => $this->twitterLink,
			'facebookLink' => $this->facebookLink,
			'youtubeLink' => $this->youtubeLink,
			'instagramLink' => $this->instagramLink,
			'pinterestLink' => $this->pinterestLink,
			'goodreadsLink' => $this->goodreadsLink,
			'tiktokLink' => $this->tiktokLink,
			'generalContactLink' => $this->generalContactLink,
			'email' => $this->contactEmail,
			'themeId' => $this->theme,
			'allowLinkedAccounts' => $this->allowLinkedAccounts,
			'allowUserLists' => $this->showFavorites,
			'showHoldButton' => $this->showHoldButton,
			'allowFreezeHolds' => $this->allowFreezeHolds,
			'maxDaysToFreeze' => $this->maxDaysToFreeze,
			'showCardExpiration' => $this->showCardExpirationDate,
			'showCardExpirationWarnings' => $this->showExpirationWarnings,
			'enableReadingHistory' => $this->enableReadingHistory,
			'enableSavedSearches' => $this->enableSavedSearches,
			'allowPinReset' => $this->allowPinReset,
			'allowProfileUpdates' => $this->allowProfileUpdates,
			'showShareOnExternalSites' => $this->showShareOnExternalSites,
			'discoveryVersion' => $interface->getVariable('gitBranchWithCommit'),
		];
		if (empty($this->baseUrl)) {
			$apiInfo['baseUrl'] = $configArray['Site']['url'];
		}
		if ($this->libraryCardBarcodeStyle != "none") {
			$apiInfo['barcodeStyle'] = $this->libraryCardBarcodeStyle;
		} else {
			$apiInfo['barcodeStyle'] = null;
		}
		$quickSearches = $this->getQuickSearches();
		$apiInfo['quickSearches'] = [];
		foreach ($quickSearches as $quickSearch) {
			$apiInfo['quickSearches'][$quickSearch->id] = [
				'id' => $quickSearch->id,
				'label' => $quickSearch->label,
				'searchTerm' => $quickSearch->searchTerm,
				'weight' => $quickSearch->weight,
			];
		}
		$apiInfo['notifications'] = $this->getLiDANotifications();
		$activeTheme = new Theme();
		$activeTheme->id = $this->theme;
		if ($activeTheme->find(true)) {
			$activeTheme->applyDefaults();
			if ($activeTheme->logoName) {
				$apiInfo['logo'] = $configArray['Site']['url'] . '/files/original/' . $activeTheme->logoName;
			}
			if ($activeTheme->favicon) {
				$apiInfo['favicon'] = $configArray['Site']['url'] . '/files/original/' . $activeTheme->favicon;
			}
			if ($activeTheme->logoApp) {
				$apiInfo['logoApp'] = $configArray['Site']['url'] . '/files/original/' . $activeTheme->logoApp;
			}
			$apiInfo['primaryBackgroundColor'] = $activeTheme->primaryBackgroundColor;
			$apiInfo['primaryForegroundColor'] = $activeTheme->primaryForegroundColor;
			$apiInfo['secondaryBackgroundColor'] = $activeTheme->secondaryBackgroundColor;
			$apiInfo['secondaryForegroundColor'] = $activeTheme->secondaryForegroundColor;
			$apiInfo['tertiaryBackgroundColor'] = $activeTheme->tertiaryBackgroundColor;
			$apiInfo['tertiaryForegroundColor'] = $activeTheme->tertiaryForegroundColor;
		}
		$locations = $this->getLocations();
		$apiInfo['locations'] = [];
		foreach ($locations as $location) {
			$apiInfo['locations'][$location->locationId] = [
				'id' => $location->locationId,
				'displayName' => $location->displayName,
				'isMainBranch' => (bool)$location->isMainBranch,
				'showInLocationsAndHoursList' => (bool)$location->showInLocationsAndHoursList,
			];
		}
		return $apiInfo;
	}


}