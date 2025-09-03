<?php

require_once 'Smarty/Smarty.class.php';
require_once ROOT_DIR . '/sys/mobile_device_detect.php';
require_once ROOT_DIR . '/sys/Theming/Theme.php';
require_once ROOT_DIR . '/sys/Variable.php';
require_once ROOT_DIR . '/sys/Session/Session.php';

// Smarty Extension class
class UInterface extends Smarty {
	public $lang;
	private $themes; // The themes that are active
	private $theme;
	/** @var Theme */
	private $appliedTheme = null;
	private $isMobile;
	private $url;
	private $debug = false;

	function __construct() {
		parent::__construct();

		global $configArray;
		global $timer;

		$this->caching = false;

		$local = $configArray['Site']['local'];

		$this->isMobile = mobile_device_detect();
		$this->assign('isMobile', $this->isMobile ? true : false);
		$this->assign('device', get_device_name());

		//Figure out google translate id
		try {
			require_once ROOT_DIR . '/sys/Enrichment/GoogleApiSetting.php';
			$googleSettings = new GoogleApiSetting();
			if ($googleSettings->find(true)) {
				//Get all images related to the event
				if (!empty($googleSettings->googleMapsKey)) {
					$this->assign('mapsKey', $googleSettings->googleMapsKey);
				}
			}
		} catch (Exception $e) {
			//This happens when google analytics isn't setup yet
			$this->assign('enableLanguageSelector', true);
		}
		$this->assign('enableLanguageSelector', true);

		//Check to see if we have a google site verification key
		if (isset($configArray['Site']['google_verification_key']) && strlen($configArray['Site']['google_verification_key']) > 0) {
			$this->assign('google_verification_key', $configArray['Site']['google_verification_key']);
		}

		if (isset($_REQUEST['print'])) {
			$this->assign('print', true);
		}

		//Make sure we always fall back to the default (responsive) theme so a template does not have to be overridden.
		//TODO: This is a bad hack.  ConfigArray appends the library theme to the Site theme array.  We can streamline
		//to just set the themes in use globally someplace rather than passing through the INI
		$themeArray = ['responsive'];
		$this->template_dir = "$local/interface/themes/responsive/";
		if (isset($timer)) {
			$timer->logTime('Set theme');
		}

		// Create an MD5 hash of the theme name -- this will ensure that it's a
		// writable directory name (since some config.ini settings may include
		// problem characters like commas or whitespace).
		$this->compile_dir = $configArray['System']['interfaceCompileDir'];
		if (file_exists($this->compile_dir)) {
			if (!is_writable($this->compile_dir)) {
				echo("Compile directory {$this->compile_dir} exists, but is not writable");
				die();
			}
		} else {
			if (!is_dir($this->compile_dir)) {
				if (!mkdir($this->compile_dir, 0755, true)) {
					if (empty($this->compile_dir)) {
						echo("compile directory was empty, specify in System - interface compile dir");
					} else {
						echo("Could not create compile directory {$this->compile_dir}");
					}

					die();
				}
			}
		}


		$this->plugins_dir = [
			'plugins',
			"$local/interface/plugins",
		];
		$this->caching = false;
		$this->debug = true;
		$this->compile_check = true;

		unset($local);

		$this->assign('site', $configArray['Site']);
		if (isset($_SERVER['SERVER_NAME'])) {
			$url = $_SERVER['SERVER_NAME'];
		} else {
			$url = $configArray['Site']['url'];
		}
		if (isset($_SERVER['HTTPS']) || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == "https")) {
			$url = "https://" . $url;
		} else {
			$url = "http://" . $url;
		}
		$this->url = $url;

		$this->assign('template_dir', $this->template_dir);
		$this->assign('url', $url);

		global $enabledModules;
		$this->assign('enabledModules', $enabledModules);

		if (isset($_SERVER['REQUEST_URI'])) {
			$this->assign('fullPath', str_replace('&', '&amp;', $_SERVER['REQUEST_URI']));
			$this->assign('requestHasParams', strpos($_SERVER['REQUEST_URI'], '?') > 0);
		}
		if (isset($configArray['Site']['email'])) {
			$this->assign('supportEmail', $configArray['Site']['email']);
		}
		if (isset($configArray['Site']['libraryName'])) {
			$this->assign('consortiumName', $configArray['Site']['libraryName']);
		}
		$this->assign('libraryName', $configArray['Site']['title']);

		$this->assign('primaryTheme', reset($themeArray));
		$this->assign('device', get_device_name());

		// Determine Offline Mode
		global $offlineMode;
		global $loginAllowedWhileOffline;
		$offlineMode = false;
		$loginAllowedWhileOffline = false;
		require_once ROOT_DIR . '/sys/SystemVariables.php';
		$systemVariables = SystemVariables::getSystemVariables();
		if (!empty($systemVariables)) {
			if ($systemVariables->catalogStatus == 2) {
				$offlineMode = true;
				$loginAllowedWhileOffline = true;
				$this->assign('enableEContentWhileOffline', true);
				$this->assign('offlineMessage', $systemVariables->offlineMessage);
			} elseif ($systemVariables->catalogStatus == 1) {
				$offlineMode = true;
				$loginAllowedWhileOffline = false;
				$this->assign('enableEContentWhileOffline', false);
				$this->assign('offlineMessage', $systemVariables->offlineMessage);
			}
		}
		$this->assign('offline', $offlineMode);

		$timer->logTime('Basic configuration');

		if (IPAddress::showDebuggingInformation()) {
			$this->assign('debug', true);
		}
		if ($configArray['System']['debugJs']) {
			$this->assign('debugJs', true);
		}
		if (isset($configArray['System']['debugCss']) && $configArray['System']['debugCss']) {
			$this->assign('debugCss', true);
		}

		$this->assign('isForSearchResults', false);

		// Detect Internet Explorer 8 to include respond.js for responsive css support
		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$ie8 = stristr($_SERVER['HTTP_USER_AGENT'], 'msie 8') || stristr($_SERVER['HTTP_USER_AGENT'], 'trident/5'); //trident/5 should catch ie9 compatibility modes
			$this->assign('ie8', $ie8);
		}

		global $session;
		if (!empty($session->id)) {
			$this->assign('session', session_id() . ', remember me ' . $session->remember_me);
		} else {
			$this->assign('session', session_id() . ' - not saved');
		}

		global $activeRecordProfile;
		if ($activeRecordProfile) {
			$this->assign('activeRecordProfileModule', $activeRecordProfile->recordUrlComponent);
		}
	}

	/**
	 *  Set template variables used in the Your Account sidebar section dealing with fines.
	 */
	function setFinesRelatedTemplateVariables() {

		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getActiveUserObj();
			//Figure out if we should show a link to pay fines.
			$homeLibrary = Library::getLibraryForLocation($user->homeLocationId);

			$systemVariables = SystemVariables::getSystemVariables();
			if ($systemVariables->libraryToUseForPayments == 1) {
				global $library;
				$homeLibrary = $library;
			}

			if ($homeLibrary != null) {
				$finePaymentType = isset($homeLibrary) ? $homeLibrary->finePaymentType : 0;

				$this->assign('minimumFineAmount', $homeLibrary->minimumFineAmount);
				$this->assign('payFinesLinkText', $homeLibrary->payFinesLinkText);
				if ($finePaymentType == 1) {
					$this->assign('showRefreshAccountButton', $homeLibrary->showRefreshAccountButton);

					// Determine E-commerce Link
					$eCommerceLink = null;
					if ($homeLibrary->payFinesLink == 'default') {
						global $configArray;
						$defaultECommerceLink = $configArray['Site']['ecommerceLink'];
						if (!empty($defaultECommerceLink)) {
							$eCommerceLink = $defaultECommerceLink;
						} else {
							$finePaymentType = 0;
						}
					} elseif (!empty($homeLibrary->payFinesLink)) {
						$eCommerceLink = $homeLibrary->payFinesLink;
					} else {
						$finePaymentType = 0;
					}
					$this->assign('eCommerceLink', $eCommerceLink);
				} elseif ($finePaymentType >= 2) {
					$this->assign('eCommerceLink', '/MyAccount/Fines');
				}
			} else {
				$finePaymentType = 0;
			}
			$this->assign('finePaymentType', $finePaymentType);
		}
	}

	public function getUrl() {
		return $this->url;
	}

	function setTemplate($tpl) {
		$this->assign('pageTemplate', $tpl);
	}

	/**
	 * @return string|null
	 */
	function getTemplate() {
		return $this->getVariable('pageTemplate');
	}

	function setPageTitle($title, $translateTitle = true, $isPublicFacing = false, $isAdminFacing = false) {
		//Marmot override, add the name of the site to the title unless we are using the mobile interface.
		if ($translateTitle) {
			$translatedTitle = translate([
				'text' => $title,
				'inAttribute' => false,
				'isPublicFacing' => $isPublicFacing,
				'isAdminFacing' => $isAdminFacing,
			]);
			$translatedTitleAttribute = translate([
				'text' => $title,
				'inAttribute' => true,
				'isPublicFacing' => $isPublicFacing,
				'isAdminFacing' => $isAdminFacing,
			]);
		} else {
			$translatedTitle = $title;
			$translatedTitleAttribute = $title;
		}
		$this->assign('pageTitleShort', $translatedTitle);
		$this->assign('pageTitleShortAttribute', $translatedTitleAttribute);
		if ($this->isMobile) {
			$this->assign('pageTitle', $translatedTitle);
		} else {
			$this->assign('pageTitle', $translatedTitle . ' | ' . $this->get_template_vars('librarySystemName'));
		}
	}

	function getLanguage() {
		return $this->lang;
	}

	/**
	 * @param Language $lang
	 */
	function setLanguage($lang) {
		$this->lang = $lang->code;
		$this->assign('userLang', $lang);
	}

	/**
	 * executes & returns or displays the template results
	 *
	 * @param string $resource_name
	 * @param string $cache_id
	 * @param string $compile_id
	 * @param object $parent
	 *
	 * @return string
	 */
	function fetch($resource_name = null, $cache_id = null, $compile_id = null, $parent = null) {
		global $timer;
		$resource = parent::fetch($resource_name, $cache_id, $compile_id, $parent);
		$timer->logTime("Finished fetching $resource_name");
		return $resource;
	}

	function getAppliedTheme() {
		return $this->appliedTheme;
	}

	function loadDisplayOptions($fromBookCoverProcessing = false) {
		global $library;
		global $locationSingleton;
		global $configArray;

		$allAppliedThemes = [];
		$primaryTheme = null;

		$hasSqlUpdates = false;
		if (UserAccount::userHasPermission('Run Database Maintenance')) {
			require_once ROOT_DIR . '/services/API/SystemAPI.php';
			$systemAPI = new SystemAPI();
			$adminUser = $systemAPI->displayAdminAlert();
			if ($adminUser) {
				$hasSqlUpdates = $systemAPI->hasPendingDatabaseUpdates();
			}
		}
		$this->assign('hasSqlUpdates', $hasSqlUpdates);
		$hasOptionalUpdates = false;
		if (UserAccount::userHasPermission('Run Optional Updates')){
			require_once ROOT_DIR . '/sys/DBMaintenance/OptionalUpdate.php';
			$optionalUpdate = new OptionalUpdate();
			$optionalUpdate->status = 1;
			$hasOptionalUpdates = $optionalUpdate->count() > 0;
		}
		$this->assign('hasOptionalUpdates', $hasOptionalUpdates);
		$this->assign('shouldShowAdminAlert', $hasSqlUpdates || $hasOptionalUpdates);

		$this->assign('allActiveThemes', []);

		try {
			$theme = new Theme();
			//Check to see if we are at a location and if we are, check if there is a theme applied to it
			$location = $locationSingleton->getActiveLocation();

			$allActiveThemes = [];

			$activeThemeId = -1;
			if (isset($location) && !$location->useLibraryThemes && !empty($location->getThemes())) {
				$theme->id = $location->getPrimaryTheme()->themeId;
				$allIds = [];
				foreach ($location->getThemes() as $tmpTheme) {
					$allIds[$tmpTheme->themeId] = $tmpTheme->themeId;
				}
				$tmpTheme = new Theme();
				$tmpTheme->whereAddIn('id', $allIds, false);
				$themeNames = $tmpTheme->fetchAll('id', 'displayName');
				foreach ($allIds as $id) {
					$allActiveThemes[$id] = $themeNames[$id];
				}
			} else {
				$theme->id = $library->getPrimaryTheme()->themeId;
				$allIds = [];
				foreach ($library->getThemes() as $tmpTheme) {
					$allIds[$tmpTheme->themeId] = $tmpTheme->themeId;
				}
				$tmpTheme = new Theme();
				$tmpTheme->whereAddIn('id', $allIds, false);
				if (count($allIds) > 0) {
					$themeNames = $tmpTheme->fetchAll('id', 'displayName');
					foreach ($allIds as $id) {
						$allActiveThemes[$id] = $themeNames[$id];
					}
				}
			}
			if (UserAccount::isLoggedIn()) {
				$userObject = UserAccount::getActiveUserObj();
				if ($userObject->preferredTheme != -1 && array_key_exists($userObject->preferredTheme, $allActiveThemes)) {
					$theme->id = $userObject->preferredTheme;
				}
			}else{
				if (isset($_SESSION['preferredTheme']) && array_key_exists($_SESSION['preferredTheme'], $allActiveThemes)) {
					$theme->id = $_SESSION['preferredTheme'];
				}
			}
			$this->assign('allActiveThemes', $allActiveThemes);
			if ($theme->find(true)) {
				$allAppliedThemes = $theme->getAllAppliedThemes();
				$primaryTheme = $theme;
				$this->appliedTheme = $primaryTheme;
				$this->assign('activeThemeId', $primaryTheme->id);
			}

			//Get extended theme info
			if ($theme->extendsTheme) {
				$this->assign('extendedTheme', $theme->extendsTheme);
			}

			$this->assign('fullWidthTheme', $theme->fullWidth);
			$this->assign('coverStyle', $theme->coverStyle);

			$browseCategoryLayoutStyle = "masonry";
			if ($theme->browseImageLayout == 1) {
				$browseCategoryLayoutStyle = "grid";
			}

			$this->assign('browseStyle', $browseCategoryLayoutStyle);

			$accessibleBrowseCategories = 0;
			if($theme->accessibleBrowseCategories) {
				$accessibleBrowseCategories = $theme->accessibleBrowseCategories;
			}

			$this->assign('accessibleBrowseCategories', $accessibleBrowseCategories);

			//Get Logo
			$logoName = null;
			foreach ($allAppliedThemes as $theme) {
				if (!empty($theme->logoName)) {
					$logoName = $theme->logoName;
					break;
				}
			}
			if (!empty($logoName)) {
				$this->assign('responsiveLogo', '/files/original/' . $logoName);
			} else {
				if (isset($configArray['Site']['responsiveLogo'])) {
					$this->assign('responsiveLogo', $configArray['Site']['responsiveLogo']);
				}
			}

			//Get Footer Logo
			$footerLogo = null;
			foreach ($allAppliedThemes as $theme) {
				if (!empty($theme->footerLogo)) {
					$footerLogo = $theme->footerLogo;
					break;
				}
			}
			if ($footerLogo) {
				$this->assign('footerLogo', '/files/original/' . $footerLogo);
			}

			$footerLogoLink = null;
			foreach ($allAppliedThemes as $theme) {
				if (!empty($theme->footerLogoLink)) {
					$footerLogoLink = $theme->footerLogoLink;
					break;
				}
			}
			if ($footerLogo) {
				$this->assign('footerLogoLink', $footerLogoLink);
			}

			$footerLogoAlt = null;
			foreach ($allAppliedThemes as $theme) {
				if (!empty($theme->footerLogoAlt)) {
					$footerLogoAlt = $theme->footerLogoAlt;
					break;
				}
			}
			if ($footerLogoAlt) {
				$this->assign('footerLogoAlt', $footerLogoAlt);
			}

			//Get favicon
			$favicon = null;
			foreach ($allAppliedThemes as $theme) {
				if (!empty($theme->favicon)) {
					$favicon = $theme->favicon;
					break;
				}
			}
			if ($favicon) {
				$this->assign('favicon', '/files/original/' . $favicon);
			}

			if ($primaryTheme != null) {
				$themeCss = $primaryTheme->generatedCss;
				$this->assign('themeCss', $themeCss);
				$this->assign('primaryThemeObject', $primaryTheme);
				$this->assign('bodyBackgroundColor', $primaryTheme->bodyBackgroundColor);
				$this->assign('bodyTextColor', $primaryTheme->bodyTextColor);
				$this->assign('primaryBackgroundColor', $primaryTheme->primaryBackgroundColor);
				$this->assign('primaryForegroundColor', $primaryTheme->primaryForegroundColor);
				$this->assign('secondaryBackgroundColor', $primaryTheme->secondaryBackgroundColor);
				$this->assign('secondaryForegroundColor', $primaryTheme->secondaryForegroundColor);
				$this->assign('tertiaryBackgroundColor', $primaryTheme->tertiaryBackgroundColor);
				$this->assign('tertiaryForegroundColor', $primaryTheme->tertiaryForegroundColor);
				$this->assign('primaryButtonBackgroundColor', $primaryTheme->primaryButtonBackgroundColor);
				$this->assign('primaryButtonForegroundColor', $primaryTheme->primaryButtonForegroundColor);
				$this->assign('bodyFont', $primaryTheme->bodyFont);
			}
		} catch (PDOException $e) {
			global $logger;
			$logger->log("Theme interface not found", Logger::LOG_ALERT);
		}

		/** @var Location $location */
		$location = $locationSingleton->getActiveLocation();
		$this->assign('logoLink', '');
		$this->assign('logoAlt', 'Return to Catalog Home');
		$useHomeLink = $library->getLayoutSettings()->useHomeLink;
		if ($useHomeLink == '2' || $useHomeLink == '3') {
			if ((isset($location) && $location->homeLink == 'default')) {
				$this->assign('logoLink', '/');
			}
			if (isset($location) && strlen($location->homeLink) > 0 && $location->homeLink != 'default') {
				$this->assign('logoAlt', 'Library Home Page');
				$this->assign('logoLink', $location->homeLink);
			} elseif (strlen($library->homeLink) > 0 && $library->homeLink != 'default') {
				$this->assign('logoAlt', 'Library Home Page');
				$this->assign('logoLink', $library->homeLink);
			}
		}
		$this->assign('useHomeLink', $useHomeLink);
		$this->assign('showBookIcon', $library->getLayoutSettings()->showBookIcon);
		$this->assign('languageAndDisplayInHeader', $library->getLayoutSettings()->languageAndDisplayInHeader);

		// set minimum theme contrast ratio
		$this->assign('contrastRatio', $library->getLayoutSettings()->contrastRatio);

		if (isset($location) && strlen($location->homeLink) > 0 && $location->homeLink != 'default') {
			$this->assign('homeLink', $location->homeLink);
		} elseif (strlen($library->homeLink) > 0 && $library->homeLink != 'default') {
			$this->assign('homeLink', $library->homeLink);
		} elseif ($library->homeLink == 'default' || empty($library->homeLink)) {
			$this->assign('homeLink', '/');
		}

		$showTopOfPageButton = $library->getLayoutSettings()->showTopOfPageButton;
		$this->assign('showTopOfPageButton', $showTopOfPageButton);

		$dismissPlacardLocation = $library->getLayoutSettings()->dismissPlacardButtonLocation;
		$this->assign('dismissPlacardLocation', $dismissPlacardLocation);

		$dismissPlacardButtonAsIcon = $library->getLayoutSettings()->dismissPlacardButtonIcon;
		$this->assign('dismissPlacardButtonAsIcon', $dismissPlacardButtonAsIcon);

		//Load JavaScript Snippets
		$customJavascript = '';
		if (!isset($_REQUEST['noCustomJavaScript']) && !isset($_REQUEST['noCustom'])) {
			try {
				if (isset($_COOKIE["cookieConsent"])) {
					$cookie = json_decode(urldecode($_COOKIE["cookieConsent"]), true);
					if ($cookie != null) {
						$analyticsPref = $cookie['Analytics'];
					}else{
						$analyticsPref = 0;
					}
				}else{
					$cookie = null;
					$analyticsPref = 0;
				}

				if (isset($location)) {
					require_once ROOT_DIR . '/sys/LocalEnrichment/JavaScriptSnippetLocation.php';
					$javascriptSnippetLocation = new JavaScriptSnippetLocation();
					$javascriptSnippetLocation->locationId = $location->locationId;
					$javaScriptSnippetIds = $javascriptSnippetLocation->fetchAll('javascriptSnippetId', 'javascriptSnippetId');
					require_once ROOT_DIR . '/sys/LocalEnrichment/JavaScriptSnippet.php';
					$javascriptSnippet = new JavaScriptSnippet();
					$javascriptSnippet->whereAddIn('id', $javaScriptSnippetIds, false);
					$javascriptSnippet->find();
					while ($javascriptSnippet->fetch()) {
						if (empty($library->cookieStorageConsent) ||
							(!empty($library->cookieStorageConsent) && empty($javascriptSnippet->containsAnalyticsCookies)) ||
							(!empty($library->cookieStorageConsent) && !empty($javascriptSnippet->containsAnalyticsCookies) && $analyticsPref == 1)
						) {
							if (strlen($customJavascript) > 0) {
								$customJavascript .= "\n";
							}
							$customJavascript .= trim($javascriptSnippet->snippet);
						}
					}
				} else {
					require_once ROOT_DIR . '/sys/LocalEnrichment/JavaScriptSnippetLibrary.php';
					$javascriptSnippetLibrary = new JavaScriptSnippetLibrary();
					$javascriptSnippetLibrary->libraryId = $library->libraryId;
					$javaScriptSnippetIds = $javascriptSnippetLibrary->fetchAll('javascriptSnippetId', 'javascriptSnippetId');
					if (count($javaScriptSnippetIds) > 0) {
						require_once ROOT_DIR . '/sys/LocalEnrichment/JavaScriptSnippet.php';
						$javascriptSnippet = new JavaScriptSnippet();
						$javascriptSnippet->whereAddIn('id', $javaScriptSnippetIds, false);
						$javascriptSnippet->find();
						while ($javascriptSnippet->fetch()) {
							if (empty($library->cookieStorageConsent) ||
								(!empty($library->cookieStorageConsent) && empty($javascriptSnippet->containsAnalyticsCookies)) ||
								(!empty($library->cookieStorageConsent) && !empty($javascriptSnippet->containsAnalyticsCookies) && $analyticsPref == 1)
							) {
								if (strlen($customJavascript) > 0) {
									$customJavascript .= "\n";
								}
								$customJavascript .= trim($javascriptSnippet->snippet);
							}
						}
					}

				}
			} catch (PDOException $e) {
				//This happens before the database update runs
			}
		}
		$this->assign('customJavascript', $customJavascript);

		global $offlineMode;

		$this->assign('facebookLink', $library->facebookLink);
		$this->assign('twitterLink', $library->twitterLink);
		$this->assign('youtubeLink', $library->youtubeLink);
		$this->assign('instagramLink', $library->instagramLink);
		$this->assign('pinterestLink', $library->pinterestLink);
		$this->assign('goodreadsLink', $library->goodreadsLink);
		$this->assign('tiktokLink', $library->tiktokLink);
		$this->assign('blueskyLink', $library->blueskyLink);
		$this->assign('threadsLink', $library->threadsLink);
		$this->assign('generalContactLink', $library->generalContactLink);
		$this->assign('showLoginButton', $library->showLoginButton && ($offlineMode == false || $this->getVariable('enableEContentWhileOffline')));
		$this->assign('showAdvancedSearchbox', $library->showAdvancedSearchbox);
		$this->assign('enableInnReachIntegration', $library->enableInnReachIntegration);
		$this->assign('enableShareItIntegration', $library->ILLSystem == 3);
		$groupedWorkDisplaySettings = $library->getGroupedWorkDisplaySettings();
		$this->assign('showRatings', $groupedWorkDisplaySettings->showRatings);
		$this->assign('show856LinksAsTab', $groupedWorkDisplaySettings->show856LinksAsTab);
		$this->assign('showSearchTools', $groupedWorkDisplaySettings->showSearchTools);
		$this->assign('showSearchToolsAtTop', $groupedWorkDisplaySettings->showSearchToolsAtTop);
		$this->assign('showQuickCopy', $groupedWorkDisplaySettings->showQuickCopy);
		$this->assign('alwaysShowSearchResultsMainDetails', $groupedWorkDisplaySettings->alwaysShowSearchResultsMainDetails);
		$this->assign('showRelatedRecordLabels', $groupedWorkDisplaySettings->showRelatedRecordLabels);
		$this->assign('showEditionCovers', $groupedWorkDisplaySettings->showEditionCovers);
		$this->assign('showExpirationWarnings', $library->showExpirationWarnings);
		$this->assign('expiredMessage', $library->expiredMessage);
		$this->assign('expirationNearMessage', $library->expirationNearMessage);
		$this->assign('showWhileYouWait', $library->showWhileYouWait);
		$this->assign('showYouMightAlsoLike', $library->showYouMightAlsoLike);

		$hasEventSettings = $library->hasEventSettings();
		$this->assign('hasEventSettings', $hasEventSettings);

		$this->assign('showItsHere', $library->showItsHere);

		$this->assign('displayItemBarcode', $library->displayItemBarcode);
		$this->assign('displayHoldsOnCheckout', $library->displayHoldsOnCheckout);

		$this->assign('allowMaxDaysToFreeze', $library->maxDaysToFreeze);
		if ($library->maxDaysToFreeze > -1) {
			$this->assign('maxDaysToFreeze', strtotime('+' . $library->maxDaysToFreeze . ' days'));
		}

		$this->assign('showHoldButtonForUnavailableOnly', $library->showHoldButtonForUnavailableOnly);
		$this->assign('showHoldCancelDate', $library->showHoldCancelDate);
		$this->assign('allowMasqueradeMode', $library->allowMasqueradeMode);
		$this->assign('allowReadingHistoryDisplayInMasqueradeMode', $library->allowReadingHistoryDisplayInMasqueradeMode);
		$this->assign('interLibraryLoanName', $library->interLibraryLoanName);
		$this->assign('interLibraryLoanUrl', $library->interLibraryLoanUrl);
		$this->assign('showGroupedHoldCopiesCount', $library->showGroupedHoldCopiesCount);
		$this->assign('showOnOrderCounts', $library->showOnOrderCounts);

		$this->assign('showConvertListsFromClassic', $library->showConvertListsFromClassic);

		$this->assign('showAlternateLibraryCard', $library->showAlternateLibraryCard);

		$this->assign('showCurbsidePickups', ($library->curbsidePickupSettingId != -1) ? 1 : 0);

		$this->assign('enableSavedSearches', $library->enableSavedSearches);
		$this->assign('showCitationStyleGuides', $library->showCitationStyleGuides);

		$this->assign('showUserCirculationModules', $library->showUserCirculationModules);
		$this->assign('showUserContactInformation', $library->showUserContactInformation);
		$this->assign('showUserPreferences', $library->showUserPreferences);
		$this->assign('privacyConsentEnabled', $library->cookieStorageConsent || $library->ilsConsentEnabled);
		if ($location != null) { // library and location
			$groupedWorkDisplaySettings = $location->getGroupedWorkDisplaySettings();
			$this->assign('showFavorites', $location->showFavorites && $library->showFavorites);
			$this->assign('showComments', $groupedWorkDisplaySettings->showComments);
			$this->assign('showEmailThis', $location->showEmailThis && $library->showEmailThis);
			$showStaffView = $groupedWorkDisplaySettings->showStaffView;
			$this->assign('showShareOnExternalSites', $location->showShareOnExternalSites && $library->showShareOnExternalSites);
			$this->assign('showGoodReadsReviews', $groupedWorkDisplaySettings->showGoodReadsReviews);
			$showHoldButton = (($location->showHoldButton == 1) && ($library->showHoldButton == 1)) ? 1 : 0;
			$showHoldButtonInSearchResults = (($location->showHoldButton == 1) && ($library->showHoldButtonInSearchResults == 1)) ? 1 : 0;
			$this->assign('showSimilarTitles', $groupedWorkDisplaySettings->showSimilarTitles);
			$this->assign('showSimilarAuthors', $groupedWorkDisplaySettings->showSimilarAuthors);
			$this->assign('showStandardReviews', $groupedWorkDisplaySettings->showStandardReviews);
			$this->assign('showRelatedRecordLabels', $groupedWorkDisplaySettings->showRelatedRecordLabels);
			$this->assign('showEditionCovers', $groupedWorkDisplaySettings->showEditionCovers);
		} else { // library only
			$groupedWorkDisplaySettings = $library->getGroupedWorkDisplaySettings();
			$this->assign('showFavorites', $library->showFavorites);
			$showHoldButton = $library->showHoldButton;
			$showHoldButtonInSearchResults = $library->showHoldButtonInSearchResults;
			$this->assign('showComments', $groupedWorkDisplaySettings->showComments);
			$this->assign('showEmailThis', $library->showEmailThis);
			$this->assign('showShareOnExternalSites', $library->showShareOnExternalSites);
			$showStaffView = $library->getGroupedWorkDisplaySettings()->showStaffView;
			$this->assign('showSimilarTitles', $groupedWorkDisplaySettings->showSimilarTitles);
			$this->assign('showSimilarAuthors', $groupedWorkDisplaySettings->showSimilarAuthors);
			$this->assign('showGoodReadsReviews', $groupedWorkDisplaySettings->showGoodReadsReviews);
			$this->assign('showStandardReviews', $groupedWorkDisplaySettings->showStandardReviews);
			$this->assign('showRelatedRecordLabels', $groupedWorkDisplaySettings->showRelatedRecordLabels);
			$this->assign('showEditionCovers', $groupedWorkDisplaySettings->showEditionCovers);
		}
		if ($showStaffView == 2) {
			$showStaffView = UserAccount::isStaff();
		}
		$this->assign('showStaffView', $showStaffView);

		if ($showHoldButton == 0) {
			$showHoldButtonInSearchResults = 0;
		}

		$additionalCSS = '';
		if (!isset($_REQUEST['noCustomCSS']) && !isset($_REQUEST['noCustom'])) {
			if (!empty($library->additionalCss)) {
				$additionalCSS = $library->additionalCss;
			}
			if (!empty($location->additionalCss)) {
				$additionalCSS = $location->additionalCss;
			}
		}
		$this->assign('additionalCss', $additionalCSS);

		if (!empty($library->headerText)) {
			$this->assign('headerText', $library->headerText);
		}
		if (!empty($location->headerText)) {
			$this->assign('headerText', $location->headerText);
		}
		if (!empty($library->footerText)) {
			$this->assign('footerText', $library->footerText);
		}
		$this->assign('showHoldButton', $showHoldButton);
		$this->assign('showHoldButtonInSearchResults', $showHoldButtonInSearchResults);
		$this->assign('showNotInterested', true);

		$this->assign('showRatings', $library->getGroupedWorkDisplaySettings()->showRatings);
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::getActiveUserObj()->hasIlsConnection()) {
				$this->assign('allowPinReset', $library->allowPinReset);
			}else{
				//If we are not connected to an ILS, we will always allow PIN Reset
				$this->assign('allowPinReset', true);
			}
		}else{
			$this->assign('allowPinReset', $library->allowPinReset);
		}
		$this->assign('allowAccountLinking', ($library->allowLinkedAccounts == 1));
		$this->assign('librarySystemName', $library->displayName);
		$this->assign('showLibraryHoursAndLocationsLink', $library->getLayoutSettings()->showLibraryHoursAndLocationsLink);
		//Check to see if we should just call it library location
		$numLocations = $library->getNumLocationsForLibrary();
		$this->assign('numLocations', $numLocations);
		if ($numLocations == 1) {
			$locationForLibrary = new Location();
			$locationForLibrary->libraryId = $library->libraryId;
			$locationForLibrary->find(true);

			$this->assign('hasValidHours', $locationForLibrary->hasValidHours());
		}
		$this->assign('showDisplayNameInHeader', $library->showDisplayNameInHeader);
		$this->assign('externalMaterialsRequestUrl', $library->externalMaterialsRequestUrl);
		$this->assign('languageAndDisplayInHeader', $library->languageAndDisplayInHeader);
		$this->assign('displayExploreMoreBarInSummon', $library->displayExploreMoreBarInSummon);
		$this->assign('displayExploreMoreBarInEbscoEds', $library->displayExploreMoreBarInEbscoEds);
		$this->assign('displayExploreMoreBarInCatalogSearch', $library->displayExploreMoreBarInCatalogSearch);
		$this->assign('displayExploreMoreBarInEbscoHost', $library->displayExploreMoreBarInEbscoHost);

		if ($location != null) {
			$this->assign('showDisplayNameInHeader', $location->showDisplayNameInHeader);
			$this->assign('languageAndDisplayInHeader', $location->languageAndDisplayInHeader);
			$this->assign('librarySystemName', $location->displayName);
			$this->assign('displayExploreMoreBarInSummon', $location->displayExploreMoreBarInSummon);
			$this->assign('displayExploreMoreBarInEbscoEds', $location->displayExploreMoreBarInEbscoEds);
			$this->assign('displayExploreMoreBarInCatalogSearch', $location->displayExploreMoreBarInCatalogSearch);
			$this->assign('displayExploreMoreBarInEbscoHost', $location->displayExploreMoreBarInEbscoHost);
		}

		if (!$fromBookCoverProcessing) {
			//Determine if materials request functionality should be enabled
			if (file_exists(ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequest.php')) {
				require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequest.php';
				$this->assign('enableAspenMaterialsRequest', MaterialsRequest::enableAspenMaterialsRequest());
				$materialRequestType = $library->enableMaterialsRequest;
				$this->assign('materialRequestType', $materialRequestType);
			} else {
				$this->assign('enableAspenMaterialsRequest', false);
			}

			//Determine whether or not to display materials request to patrons
			$this->assign('displayMaterialsRequest', $library->displayMaterialsRequestToPublic || UserAccount::isStaff());

			//Determine whether or not donations functionality should be enabled
			$enableDonationsModule = false;
			try {
				require_once ROOT_DIR . '/sys/ECommerce/DonationsSetting.php';
				$donationSettings = new DonationsSetting();
				$donationSettings->id = $library->donationSettingId;
				if ($donationSettings->find(true)) {
					$enableDonationsModule = true;
					$allowDonationsToBranch = $donationSettings->allowDonationsToBranch;
					$this->assign('allowDonationsToBranch', $allowDonationsToBranch);
					$allowDonationEarmark = $donationSettings->allowDonationEarmark;
					$this->assign('allowDonationEarmark', $allowDonationEarmark);
					$allowDonationDedication = $donationSettings->allowDonationDedication;
					$this->assign('allowDonationDedication', $allowDonationDedication);
					$donationsContent = $donationSettings->donationsContent;
					$this->assign('donationsContent', $donationsContent);
					$donationEmailTemplate = $donationSettings->donationEmailTemplate;
					$this->assign('donationEmailTemplate', $donationEmailTemplate);
				}
			} catch (Exception $e) {
				//Donations are not setup yet.
			}

			$this->assign('enableDonations', $enableDonationsModule);

			//Determine whether or not Rosen LevelUP functionality should be enabled
			try {
				require_once ROOT_DIR . '/sys/Rosen/RosenLevelUPSetting.php';
				$rosenLevelUPSetting = new RosenLevelUPSetting();
				if ($rosenLevelUPSetting->count() > 0) {
					$this->assign('enableRosenLevelUP', true);
				} else {
					$this->assign('enableRosenLevelUP', false);
				}
			} catch (PDOException $e) {
				global $logger;
				$logger->log("Rosen LevelUP API Settings table not yet built in database: run DBMaintenance", Logger::LOG_ALERT);
			}

			//Load library links
			$links = $library->libraryLinks;
			$libraryLinks = [];
			$expandedLinkCategories = [];
			/** @var LibraryLink $libraryLink */
			foreach ($links as $libraryLink) {
				if (!$libraryLink->isValidForDisplay()) {
					continue;
				}

				if (empty($libraryLink->category)) {
					$libraryLink->category = 'none-' . $libraryLink->id;
				}
				if (!array_key_exists($libraryLink->category, $libraryLinks)) {
					$libraryLinks[$libraryLink->category] = [];
				}
				$libraryLinks[$libraryLink->category][$libraryLink->linkText] = $libraryLink;
				if ($libraryLink->showExpanded) {
					$expandedLinkCategories[$libraryLink->category] = 1;
				}
			}
			$this->assign('libraryLinks', $libraryLinks);
			$this->assign('expandedLinkCategories', $expandedLinkCategories);

			try {
				require_once ROOT_DIR . '/sys/SystemVariables.php';
				$systemVariables = SystemVariables::getSystemVariables();
				if ($systemVariables != false) {
					$this->assign('useHtmlEditorRatherThanMarkdown', $systemVariables->useHtmlEditorRatherThanMarkdown);
				} else {
					$this->assign('useHtmlEditorRatherThanMarkdown', 0);
				}
			} catch (Exception $e) {
				//This happens prior to the table being created
			}
		}

		$ssoIsEnabled = false;

		// if using SSO, determine if it's available to only staff users or not
		$ssoStaffOnly = false;
		$bypassAspenLogin = false;
		$bypassAspenPatronLogin = false;
		$bypassLoginUrl = '';
		$ssoService = null;
		$samlEntityId = null;
		$ssoSettingId = -1;
		$ssoRestrictedByIP = false;

		try {
			if(UserAccount::isPrimaryAccountAuthenticationSSO()) {
				require_once ROOT_DIR . '/sys/Account/AccountProfile.php';
				$accountProfile = new AccountProfile();
				$accountProfile->id = $library->accountProfileId;
				if($accountProfile->find(true)) {
					$ssoSettingId = $accountProfile->ssoSettingId;
				}
			} else {
				$ssoSettingId = $library->ssoSettingId;
			}

			global $enabledModules;
			if (array_key_exists('Single sign-on', $enabledModules) && $ssoSettingId > 0) {
				require_once ROOT_DIR . '/sys/Authentication/SSOSetting.php';
				$ssoSettings = new SSOSetting();
				$ssoSettings->id = $ssoSettingId;
				if($ssoSettings->find(true)) {
					$ssoStaffOnly = $ssoSettings->staffOnly;
					$ssoService = $ssoSettings->service;
					$bypassAspenLogin = $ssoSettings->bypassAspenLogin ?? true;
					$bypassAspenPatronLogin = $ssoSettings->bypassAspenPatronLogin ?? false;
					$samlEntityId = $ssoSettings->ssoEntityId;
					$ssoIsEnabled = true;
					$ssoRestrictedByIP = $ssoSettings->restrictByIP;
					if($bypassAspenPatronLogin) {
						if ($ssoSettings->service === 'oauth') {
							$bypassLoginUrl = $configArray['Site']['url'] . '/init_oauth.php';
						}
						if ($ssoSettings->service === 'saml') {
							$bypassLoginUrl = $configArray['Site']['url'] . '/Authentication/SAML2?init';
						}
					}
				}
			}
		} catch (Exception $e) {
			//This happens if the SSOSetting table does not exist yet.
		}

		$this->assign('ssoIsEnabled', $ssoIsEnabled);
		$this->assign('ssoStaffOnly', $ssoStaffOnly);
		$this->assign('ssoService', $ssoService);
		$this->assign('bypassAspenLogin', $bypassAspenLogin);
		$this->assign('bypassAspenPatronLogin', $bypassAspenPatronLogin);
		$this->assign('bypassLoginUrl', $bypassLoginUrl);
		$this->assign('samlEntityId', $samlEntityId);
		$this->assign('ssoRestrictedByIP', $ssoRestrictedByIP);

		$loadRecaptcha = false;
		require_once ROOT_DIR . '/sys/Enrichment/RecaptchaSetting.php';
		$recaptcha = new RecaptchaSetting();
		if ($recaptcha->find(true) && !empty($recaptcha->publicKey)) {
			$loadRecaptcha = true;
		}
		$this->assign('loadRecaptcha', $loadRecaptcha);
	}

	/**
	 * @param $variableName
	 * @return string|array|null
	 */
	public function getVariable($variableName) {
		$variable = $this->get_template_vars($variableName);
		if ($variable instanceof Smarty_Variable) {
			return $variable->value;
		} else {
			return $variable;
		}
	}

	public function assignAppendToExisting($variableName, $newValue) {
		$originalValue = $this->get_template_vars($variableName);
		if ($originalValue == null) {
			$this->assign($variableName, $newValue);
		} else {
			if (is_array($originalValue)) {
				$valueToAssign = array_merge($originalValue, $newValue);
			} else {
				$valueToAssign = [];
				$valueToAssign[] = $originalValue;
				$valueToAssign[] = $newValue;
			}
			$this->assign($variableName, $valueToAssign);
		}
	}

	public function assignAppendUniqueToExisting($variableName, $newValue) {
		$originalValue = $this->get_template_vars($variableName);
		if ($originalValue == null) {
			$this->assign($variableName, $newValue);
		} else {
			if (is_array($originalValue)) {
				$valueToAssign = $originalValue;
				foreach ($newValue as $tmpValue) {
					if (!in_array($tmpValue, $valueToAssign)) {
						$valueToAssign[] = $tmpValue;
					}
				}
			} else {
				if ($newValue != $originalValue) {
					$valueToAssign = [];
					$valueToAssign[] = $originalValue;
					$valueToAssign[] = $newValue;
				} else {
					return;
				}
			}
			$this->assign($variableName, $valueToAssign);
		}
	}


	/**
	 * Returns an array containing template variables
	 *
	 * @param string $name
	 * @return string|array
	 */
	function &get_template_vars($name = null) {
		if (!isset($name)) {
			return $this->tpl_vars;
		} elseif (isset($this->tpl_vars[$name])) {
			return $this->tpl_vars[$name];
		} else {
			// var non-existent, return valid reference
			$_tmp = null;
			return $_tmp;
		}
	}

	public function template_exists($templateName) {
		if (file_exists($this->template_dir . $templateName)) {
			return true;
		} else {
			return false;
		}
	}

	public function resetActiveTheme($userId) {
		global $library;
		$preferredTheme = $library->theme;
		$user = new User();
		$user->id = $userId;
		if($user->find(true)) {
			if($user->preferredTheme !== '-1' || $user->preferredTheme !== -1) {
				$preferredTheme = $user->preferredTheme;
			}
		}

		$theme = new Theme();
		$theme->id = $preferredTheme;
		if ($theme->find(true)) {
			$allAppliedThemes = $theme->getAllAppliedThemes();
			$primaryTheme = $theme;
			$this->appliedTheme = $primaryTheme;
			$this->assign('activeThemeId', $primaryTheme->id);
			//Get extended theme info
			if ($theme->extendsTheme) {
				$this->assign('extendedTheme', $theme->extendsTheme);
			}

			$this->assign('parentTheme', $theme->getParentTheme());
			$this->assign('fullWidthTheme', $theme->fullWidth);
			$this->assign('coverStyle', $theme->coverStyle);

			$browseCategoryLayoutStyle = 'masonry';
			if ($theme->browseImageLayout == 1) {
				$browseCategoryLayoutStyle = 'grid';
			}

			$this->assign('browseStyle', $browseCategoryLayoutStyle);

			$accessibleBrowseCategories = 0;
			if($theme->accessibleBrowseCategories) {
				$accessibleBrowseCategories = $theme->accessibleBrowseCategories;
			}

			$this->assign('accessibleBrowseCategories', $accessibleBrowseCategories);

			//Get Logo
			$logoName = null;
			foreach ($allAppliedThemes as $theme) {
				if (!empty($theme->logoName)) {
					$logoName = $theme->logoName;
					break;
				}
			}
			if (!empty($logoName)) {
				$this->assign('responsiveLogo', '/files/original/' . $logoName);
			} else {
				if (isset($configArray['Site']['responsiveLogo'])) {
					$this->assign('responsiveLogo', $configArray['Site']['responsiveLogo']);
				}
			}

			//Get Footer Logo
			$footerLogo = null;
			foreach ($allAppliedThemes as $theme) {
				if (!empty($theme->footerLogo)) {
					$footerLogo = $theme->footerLogo;
					break;
				}
			}
			if ($footerLogo) {
				$this->assign('footerLogo', '/files/original/' . $footerLogo);
			}

			$footerLogoLink = null;
			foreach ($allAppliedThemes as $theme) {
				if (!empty($theme->footerLogoLink)) {
					$footerLogoLink = $theme->footerLogoLink;
					break;
				}
			}
			if ($footerLogo) {
				$this->assign('footerLogoLink', $footerLogoLink);
			}

			$footerLogoAlt = $theme->footerLogoAlt;
			if ($footerLogoAlt) {
				$this->assign('footerLogoAlt', $footerLogoAlt);
			}
			
			
			$systemVariables = SystemVariables::getSystemVariables();
			if (!empty($systemVariables)) {
				$supportingCompany = $systemVariables->supportingCompany;

				if (!empty ($supportingCompany)) {
					$this->assign('supportingCompany', $supportingCompany);
				}
			}

			//Get favicon
			$favicon = null;
			foreach ($allAppliedThemes as $theme) {
				if (!empty($theme->favicon)) {
					$favicon = $theme->favicon;
					break;
				}
			}
			if ($favicon) {
				$this->assign('favicon', '/files/original/' . $favicon);
			}

			$themeCss = $primaryTheme->generatedCss;
			$this->assign('themeCss', $themeCss);
			$this->assign('primaryThemeObject', $primaryTheme);
			$this->assign('bodyBackgroundColor', $primaryTheme->bodyBackgroundColor);
			$this->assign('bodyTextColor', $primaryTheme->bodyTextColor);
			$this->assign('primaryBackgroundColor', $primaryTheme->primaryBackgroundColor);
			$this->assign('primaryForegroundColor', $primaryTheme->primaryForegroundColor);
			$this->assign('secondaryBackgroundColor', $primaryTheme->secondaryBackgroundColor);
			$this->assign('secondaryForegroundColor', $primaryTheme->secondaryForegroundColor);
			$this->assign('tertiaryBackgroundColor', $primaryTheme->tertiaryBackgroundColor);
			$this->assign('tertiaryForegroundColor', $primaryTheme->tertiaryForegroundColor);
			$this->assign('primaryButtonBackgroundColor', $primaryTheme->primaryButtonBackgroundColor);
			$this->assign('primaryButtonForegroundColor', $primaryTheme->primaryButtonForegroundColor);
			$this->assign('bodyFont', $primaryTheme->bodyFont);
		}
	}
}

function translate($params) {
	global $translator;
	// If no translator exists yet, create one -- this may be necessary if we
	// encounter a failure before we are able to load the global translator
	// object.
	if (!is_object($translator)) {
		global $activeLanguage;
		if (empty($activeLanguage)) {
			$code = 'en';
		} else {
			$code = $activeLanguage->code;
		}
		$translator = new Translator('lang', $code);
	}
	if (is_array($params)) {
		$defaultText = $params['defaultText'] ?? '';
		$inAttribute = $params['inAttribute'] ?? false;
		$isPublicFacing = $params['isPublicFacing'] ?? false;
		$isAdminFacing = $params['isAdminFacing'] ?? false;
		$isMetadata = $params['isMetadata'] ?? false;
		$isAdminEnteredData = $params['isAdminEnteredData'] ?? false;
		$translateParameters = $params['translateParameters'] ?? false;
		$replacementValues = [];
		foreach ($params as $index => $param) {
			if (is_numeric($index)) {
				$replacementValues[$index] = $param;
			}
		}
		return $translator->translate($params['text'], $defaultText, $replacementValues, $inAttribute, $isPublicFacing, $isAdminFacing, $isMetadata, $isAdminEnteredData, $translateParameters);
	} else {
		return $translator->translate($params);
	}
}
