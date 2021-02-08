<?php

require_once 'Smarty/Smarty.class.php';
require_once ROOT_DIR . '/sys/mobile_device_detect.php';
require_once ROOT_DIR . '/sys/Theming/Theme.php';
require_once ROOT_DIR . '/sys/Variable.php';
require_once ROOT_DIR . '/sys/Session/Session.php';

// Smarty Extension class
class UInterface extends Smarty
{
	public $lang;
	private $themes; // The themes that are active
	private $theme;
	/** @var Theme */
	private $appliedTheme = null;
	private $isMobile;
	private $url;
	private $debug = false;

	function __construct()
	{
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
		}catch (Exception $e){
			//This happens when google analytics isn't setup yet
			$this->assign('enableLanguageSelector', true);
		}
		$this->assign('enableLanguageSelector', true);

		//Check to see if we have a google site verification key
		if (isset($configArray['Site']['google_verification_key']) && strlen($configArray['Site']['google_verification_key']) > 0){
			$this->assign('google_verification_key', $configArray['Site']['google_verification_key']);
		}

		if (isset($_REQUEST['print'])) {
			$this->assign('print', true);
		}

		//Make sure we always fall back to the default (responsive) theme so a template does not have to be overridden.
		//TODO: This is a bad hack.  ConfigArray appends the library theme to the Site theme array.  We can streamline
		//to just set the themes in use globally someplace rather than passing through the INI
		$themeArray = explode(',', $configArray['Site']['theme']);
		$this->themes = $themeArray;
		$this->template_dir  = "$local/interface/themes/responsive/";
		if (isset($timer)){
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


		$this->plugins_dir   = array('plugins', "$local/interface/plugins");
		$this->caching       = false;
		$this->debug         = true;
		$this->compile_check = true;

		unset($local);

		$this->register_block('display_if_inconsistent', 'display_if_inconsistent');
		$this->register_block('display_if_field_inconsistent', 'display_if_field_inconsistent');
		$this->register_function('translate', 'translate');
		$this->register_function('char', 'char');

		$this->assign('site', $configArray['Site']);
		if (isset($_SERVER['SERVER_NAME'])) {
			$url = $_SERVER['SERVER_NAME'];
		}else {
			$url = $configArray['Site']['url'];
		}
		if (isset($_SERVER['HTTPS'])) {
			$url = "https://" . $url;
		} else {
			$url = "http://" . $url;
		}
		$this->url = $url;

		$this->assign('template_dir',$this->template_dir);
		$this->assign('url', $url);

		global $enabledModules;
		$this->assign('enabledModules', $enabledModules);

		if (isset($configArray['Islandora']['repositoryUrl'])) {
			$this->assign('repositoryUrl', $configArray['Islandora']['repositoryUrl']);
			$this->assign('encodedRepositoryUrl', str_replace('/', '\/', $configArray['Islandora']['repositoryUrl']));
		}

		$this->assign('fullPath', str_replace('&', '&amp;', $_SERVER['REQUEST_URI']));
		$this->assign('requestHasParams', strpos($_SERVER['REQUEST_URI'], '?') > 0);
		if (isset($configArray['Site']['email'])) {
			$this->assign('supportEmail', $configArray['Site']['email']);
		}
		if (isset($configArray['Site']['libraryName'])){
			$this->assign('consortiumName', $configArray['Site']['libraryName']);
		}
		$this->assign('libraryName', $configArray['Site']['title']);

		if (array_key_exists('Catalog', $configArray)) {
			$this->assign('ils', $configArray['Catalog']['ils']);
			if (isset($configArray['Catalog']['url'])) {
				$this->assign('classicCatalogUrl', $configArray['Catalog']['url']);
			} else if (isset($configArray['Catalog']['hipUrl'])) {
				$this->assign('classicCatalogUrl', $configArray['Catalog']['hipUrl']);
			}
			$this->assign('showLinkToClassicInMaintenanceMode', $configArray['Catalog']['showLinkToClassicInMaintenanceMode']);
		}

		$this->assign('primaryTheme', reset($themeArray));
		$this->assign('device', get_device_name());

		// Determine Offline Mode
		global $offlineMode;
		$offlineMode = false;
		if (!empty($configArray['Catalog']['offline']) && $configArray['Catalog']['offline'] == true){
			$offlineMode = true;
			if (isset($configArray['Catalog']['enableLoginWhileOffline'])){
				$this->assign('enableLoginWhileOffline', $configArray['Catalog']['enableLoginWhileOffline']);
			}else{
				$this->assign('enableLoginWhileOffline', false);
			}
		}else{
			if (!empty($configArray['Catalog']['enableLoginWhileOffline'])) {
				// unless offline login is enabled, don't check the offline mode system variable
				$offlineModeSystemVariable = new Variable();
				$offlineModeSystemVariable->get('name', 'offline_mode_when_offline_login_allowed');
				if ($offlineModeSystemVariable && ($offlineModeSystemVariable->value == 'true' || $offlineModeSystemVariable == '1')) {
					$this->assign('enableLoginWhileOffline', true);
					$offlineMode = true;
				}
			}
		}
		$this->assign('offline', $offlineMode);

		$timer->logTime('Basic configuration');

		if (IPAddress::showDebuggingInformation()){
			$this->assign('debug', true);
		}
		if ($configArray['System']['debugJs']){
			$this->assign('debugJs', true);
		}
		if (isset($configArray['System']['debugCss']) && $configArray['System']['debugCss']){
			$this->assign('debugCss', true);
		}

		// Detect Internet Explorer 8 to include respond.js for responsive css support
		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$ie8 = stristr($_SERVER['HTTP_USER_AGENT'], 'msie 8') || stristr($_SERVER['HTTP_USER_AGENT'], 'trident/5'); //trident/5 should catch ie9 compatibility modes
			$this->assign('ie8', $ie8);
		}

		$session = new Session();
		$session->session_id = session_id();
		if ($session->find(true)){
			$this->assign('session', session_id() . ', remember me ' . $session->remember_me);
		}else{
			$this->assign('session', session_id() . ' - not saved');
		}

		global $activeRecordProfile;
		if ($activeRecordProfile){
			$this->assign('activeRecordProfileModule', $activeRecordProfile->recordUrlComponent);
		}
	}

	/**
	 *  Set template variables used in the My Account sidebar section dealing with fines.
	 */
	function setFinesRelatedTemplateVariables() {

		if (UserAccount::isLoggedIn()){
			$user = UserAccount::getActiveUserObj();
			//Figure out if we should show a link to pay fines.
			$homeLibrary = Library::getLibraryForLocation($user->homeLocationId);
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
			}elseif ($finePaymentType == 2 || $finePaymentType == 3){
				$this->assign('eCommerceLink', '/MyAccount/Fines');
			}
			$this->assign('finePaymentType', $finePaymentType);
		}
	}

	public function getUrl(){
		return $this->url;
	}

	/*
	 * Get a list of themes that are active in the interface
	 *
	 * @return array
	 */
	public function getThemes(){
		return $this->themes;
	}

	function setTemplate($tpl)
	{
		$this->assign('pageTemplate', $tpl);
	}

	/**
	 * @return string|null
	 */
	function getTemplate(){
		return $this->getVariable('pageTemplate');
	}

	function setPageTitle($title, $translateTitle = true)
	{
		//Marmot override, add the name of the site to the title unless we are using the mobile interface.
		if ($translateTitle){
			$translatedTitle = translate($title);
			$translatedTitleAttribute = translate(['text'=>$title, 'inAttribute'=>true]);
		}else{
			$translatedTitle = $title;
			$translatedTitleAttribute = $title;
		}
		$this->assign('pageTitleShort', $translatedTitle);
		$this->assign('pageTitleShortAttribute', $translatedTitleAttribute);
		if ($this->isMobile){
			$this->assign('pageTitle', $translatedTitle);
		}else{
			$this->assign('pageTitle', $translatedTitle . ' | ' . $this->get_template_vars('librarySystemName'));
		}
	}

	function getLanguage()
	{
		return $this->lang;
	}

	/**
	 * @param Language $lang
	 */
	function setLanguage($lang)
	{
		$this->lang = $lang->code;
		$this->assign('userLang', $lang);
	}

	/**
	 * executes & returns or displays the template results
	 *
	 * @param string $resource_name
	 * @param string $cache_id
	 * @param string $compile_id
	 * @param boolean $display
	 *
	 * @return string
	 */
	function fetch($resource_name, $cache_id = null, $compile_id = null, $display = false)
	{
		global $timer;
		$resource = parent::fetch($resource_name, $cache_id, $compile_id, $display);
		$timer->logTime("Finished fetching $resource_name");
		return $resource;
	}

	function getAppliedTheme(){
		return $this->appliedTheme;
	}

	function loadDisplayOptions(){
		global $library;
		global $locationSingleton;
		global $configArray;

		$allAppliedThemes = [];
		$primaryTheme = null;

		try {
			$theme = new Theme();
			$theme->id = $library->theme;
			if ($theme->find(true)) {
				$allAppliedThemes = $theme->getAllAppliedThemes();
				$primaryTheme = $theme;
				$this->appliedTheme = $primaryTheme;
			}

			//Get Logo
			$logoName = null;
			foreach ($allAppliedThemes as $theme) {
				if (!is_null($theme->logoName)) {
					$logoName = $theme->logoName;
					break;
				}
			}
			if ($logoName) {
				$this->assign('responsiveLogo', '/files/original/' . $logoName);
			} else {
				if (isset($configArray['Site']['responsiveLogo'])) {
					$this->assign('responsiveLogo', $configArray['Site']['responsiveLogo']);
				}
			}

			//Get Footer Logo
			$footerLogo = null;
			foreach ($allAppliedThemes as $theme) {
				if (!is_null($theme->footerLogo)) {
					$footerLogo = $theme->footerLogo;
					break;
				}
			}
			if ($footerLogo) {
				$this->assign('footerLogo', '/files/original/' . $footerLogo);
			}

			$footerLogoLink = null;
			foreach ($allAppliedThemes as $theme) {
				if (!is_null($theme->footerLogoLink)) {
					$footerLogoLink = $theme->footerLogoLink;
					break;
				}
			}
			if ($footerLogo) {
				$this->assign('footerLogoLink', $footerLogoLink);
			}

			//Get favicon
			$favicon = null;
			foreach ($allAppliedThemes as $theme) {
				if (!is_null($theme->favicon)) {
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
			}
		}catch (PDOException $e){
			global $logger;
			$logger->log("Theme interface not found", Logger::LOG_ALERT);
		}

		/** @var Location $location */
		$location = $locationSingleton->getActiveLocation();
		$this->assign('logoLink', '');
		$this->assign('logoAlt', 'Return to Catalog Home');
		if ($library->getLayoutSettings()->useHomeLinkForLogo){
			if (isset($location) && strlen($location->homeLink) > 0 && $location->homeLink != 'default'){
				$this->assign('logoAlt', 'Library Home Page');
				$this->assign('logoLink', $location->homeLink);
			}elseif (strlen($library->homeLink) > 0 && $library->homeLink != 'default'){
				$this->assign('logoAlt', 'Library Home Page');
				$this->assign('logoLink', $library->homeLink);
			}
		}

		if (isset($location) && strlen($location->homeLink) > 0 && $location->homeLink != 'default'){
			$this->assign('homeLink', $location->homeLink);
		}elseif (strlen($library->homeLink) > 0 && $library->homeLink != 'default'){
			$this->assign('homeLink', $library->homeLink);
		}

		//Load JavaScript Snippets
		$customJavascript = '';
		if (!isset($_REQUEST['noCustomJavaScript']) && !isset($_REQUEST['noCustomJavascript'])) {
			try {
				if (isset($location)) {
					require_once ROOT_DIR . '/sys/LocalEnrichment/JavaScriptSnippetLocation.php';
					$javascriptSnippetLocation = new JavaScriptSnippetLocation();
					$javascriptSnippetLocation->locationId = $location->locationId;
					$javascriptSnippetLocation->find();
					while ($javascriptSnippetLocation->fetch()) {
						require_once ROOT_DIR . '/sys/LocalEnrichment/JavaScriptSnippet.php';
						$javascriptSnippet = new JavaScriptSnippet();
						$javascriptSnippet->id = $javascriptSnippetLocation->javascriptSnippetId;
						if ($javascriptSnippet->find(true)) {
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
					$javascriptSnippetLibrary->find();
					while ($javascriptSnippetLibrary->fetch()) {
						require_once ROOT_DIR . '/sys/LocalEnrichment/JavaScriptSnippet.php';
						$javascriptSnippet = new JavaScriptSnippet();
						$javascriptSnippet->id = $javascriptSnippetLibrary->javascriptSnippetId;
						if ($javascriptSnippet->find(true)) {
							if (strlen($customJavascript) > 0) {
								$customJavascript .= "\n";
							}
							$customJavascript .= trim($javascriptSnippet->snippet);
						}
					}
				}
			} catch (PDOException $e) {
				//This happens before the database update runs
			}
		}
		$this->assign('customJavascript', $customJavascript);

		$this->assign('facebookLink', $library->facebookLink);
		$this->assign('twitterLink', $library->twitterLink);
		$this->assign('youtubeLink', $library->youtubeLink);
		$this->assign('instagramLink', $library->instagramLink);
		$this->assign('pinterestLink', $library->pinterestLink);
		$this->assign('goodreadsLink', $library->goodreadsLink);
		$this->assign('generalContactLink', $library->generalContactLink);
		$this->assign('showLoginButton', $library->showLoginButton);
		$this->assign('showAdvancedSearchbox', $library->showAdvancedSearchbox);
		$this->assign('enableProspectorIntegration', $library->enableProspectorIntegration);
		$this->assign('showRatings', $library->getGroupedWorkDisplaySettings()->showRatings);
		$this->assign('show856LinksAsTab', $library->getGroupedWorkDisplaySettings()->show856LinksAsTab);
		$this->assign('showSearchTools', $library->getGroupedWorkDisplaySettings()->showSearchTools);
		$this->assign('showQuickCopy', $library->getGroupedWorkDisplaySettings()->showQuickCopy);
		$this->assign('alwaysShowSearchResultsMainDetails', $library->getGroupedWorkDisplaySettings()->alwaysShowSearchResultsMainDetails);
		$this->assign('showExpirationWarnings', $library->showExpirationWarnings);
		$this->assign('expiredMessage', $library->expiredMessage);
		$this->assign('expirationNearMessage', $library->expirationNearMessage);
		$this->assign('showWhileYouWait', $library->showWhileYouWait);

		$this->assign('showItsHere', $library->showItsHere);
		$this->assign('enableMaterialsBooking', $library->enableMaterialsBooking);
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

		if ($location != null){ // library and location
			$this->assign('showFavorites', $location->showFavorites && $library->showFavorites);
			$this->assign('showComments', $location->getGroupedWorkDisplaySettings()->showComments);
			$this->assign('showEmailThis', $location->showEmailThis && $library->showEmailThis);
			$this->assign('showStaffView', $location->getGroupedWorkDisplaySettings()->showStaffView);
			$this->assign('showShareOnExternalSites', $location->showShareOnExternalSites && $library->showShareOnExternalSites);
			$this->assign('showGoodReadsReviews', $location->getGroupedWorkDisplaySettings()->showGoodReadsReviews);
			$showHoldButton = (($location->showHoldButton == 1) && ($library->showHoldButton == 1)) ? 1 : 0;
			$showHoldButtonInSearchResults = (($location->showHoldButton == 1) && ($library->showHoldButtonInSearchResults == 1)) ? 1 : 0;
			$this->assign('showSimilarTitles', $location->getGroupedWorkDisplaySettings()->showSimilarTitles);
			$this->assign('showSimilarAuthors', $location->getGroupedWorkDisplaySettings()->showSimilarAuthors);
			$this->assign('showStandardReviews', $location->getGroupedWorkDisplaySettings()->showStandardReviews);
		}else{ // library only
			$this->assign('showFavorites', $library->showFavorites);
			$showHoldButton = $library->showHoldButton;
			$showHoldButtonInSearchResults = $library->showHoldButtonInSearchResults;
			$this->assign('showComments', $library->getGroupedWorkDisplaySettings()->showComments);
			$this->assign('showEmailThis', $library->showEmailThis);
			$this->assign('showShareOnExternalSites', $library->showShareOnExternalSites);
			$this->assign('showStaffView', $library->getGroupedWorkDisplaySettings()->showStaffView);
			$this->assign('showSimilarTitles', $library->getGroupedWorkDisplaySettings()->showSimilarTitles);
			$this->assign('showSimilarAuthors', $library->getGroupedWorkDisplaySettings()->showSimilarAuthors);
			$this->assign('showGoodReadsReviews', $library->getGroupedWorkDisplaySettings()->showGoodReadsReviews);
			$this->assign('showStandardReviews', $library->getGroupedWorkDisplaySettings()->showStandardReviews);
		}
		if ($showHoldButton == 0){
			$showHoldButtonInSearchResults = 0;
		}
		if (!empty($library->additionalCss)){
			$this->assign('additionalCss', $library->additionalCss);
		}
		if (!empty($location->additionalCss)){
			$this->assign('additionalCss', $location->additionalCss);
		}
		if (!empty($library->headerText)){
			$this->assign('headerText', $library->headerText);
		}
		if (!empty($location->headerText)){
			$this->assign('headerText', $location->headerText);
		}
		$this->assign('showHoldButton', $showHoldButton);
		$this->assign('showHoldButtonInSearchResults', $showHoldButtonInSearchResults);
		$this->assign('showNotInterested', true);

		$this->assign('showRatings', $library->getGroupedWorkDisplaySettings()->showRatings);
		$this->assign('allowPinReset', $library->allowPinReset);
		$this->assign('allowAccountLinking', ($library->allowLinkedAccounts == 1));
		$this->assign('librarySystemName', $library->displayName);
		$this->assign('showLibraryHoursAndLocationsLink', $library->getLayoutSettings()->showLibraryHoursAndLocationsLink);
		//Check to see if we should just call it library location
		$numLocations = $library->getNumLocationsForLibrary();
		$this->assign('numLocations', $numLocations);
		if ($numLocations == 1){
			$locationForLibrary = new Location();
			$locationForLibrary->libraryId = $library->libraryId;
			$locationForLibrary->find(true);

			$this->assign('hasValidHours', $locationForLibrary->hasValidHours());
		}
		$this->assign('showDisplayNameInHeader', $library->showDisplayNameInHeader);
		$this->assign('externalMaterialsRequestUrl', $library->externalMaterialsRequestUrl);

		if ($location != null){
			$this->assign('showDisplayNameInHeader', $location->showDisplayNameInHeader);
			$this->assign('librarySystemName', $location->displayName);
		}

		//Determine whether or not materials request functionality should be enabled
		if (file_exists(ROOT_DIR . '/sys/MaterialsRequest.php')) {
			require_once ROOT_DIR . '/sys/MaterialsRequest.php';
			$this->assign('enableAspenMaterialsRequest', MaterialsRequest::enableAspenMaterialsRequest());
			$materialRequestType = $library->enableMaterialsRequest;
			$this->assign('materialRequestType', $materialRequestType);
		}else{
			$this->assign('enableAspenMaterialsRequest', false);
		}

		//Determine whether or not Rosen LevelUP functionality should be enabled
		try {
			require_once ROOT_DIR . '/sys/Rosen/RosenLevelUPSetting.php';
			$rosenLevelUPSetting = new RosenLevelUPSetting();
			if ($rosenLevelUPSetting->find(true)) {
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
		foreach ($links as $libraryLink){
			if ($libraryLink->showToLoggedInUsersOnly && !UserAccount::isLoggedIn()){
				continue;
			}
			if (!$libraryLink->published && !UserAccount::userHasPermission('View Unpublished Content')){
				continue;
			}
			if (empty($libraryLink->category)){
				$libraryLink->category = 'none-' . $libraryLink->id;
			}
			if (!array_key_exists($libraryLink->category, $libraryLinks)){
				$libraryLinks[$libraryLink->category] = array();
			}
			$libraryLinks[$libraryLink->category][$libraryLink->linkText] = $libraryLink;
			if ($libraryLink->showExpanded){
				$expandedLinkCategories[$libraryLink->category] = 1;
			}
		}
		$this->assign('libraryLinks', $libraryLinks);
		$this->assign('expandedLinkCategories', $expandedLinkCategories);
	}

	/**
	 * @param $variableName
	 * @return string|array|null
	 */
	public function getVariable($variableName) {
		return $this->get_template_vars($variableName);
	}

	public function assignAppendToExisting($variableName, $newValue) {
		$originalValue = $this->get_template_vars($variableName);
		if ($originalValue == null){
			$this->assign($variableName, $newValue);
		}else{
			if (is_array($originalValue)){
				$valueToAssign = array_merge($originalValue, $newValue);
			}else{
				$valueToAssign = array();
				$valueToAssign[] = $originalValue;
				$valueToAssign[] = $newValue;
			}
			$this->assign($variableName, $valueToAssign);
		}
	}

	public function assignAppendUniqueToExisting($variableName, $newValue) {
		$originalValue = $this->get_template_vars($variableName);
		if ($originalValue == null){
			$this->assign($variableName, $newValue);
		}else{
			if (is_array($originalValue)){
				$valueToAssign = $originalValue;
				foreach($newValue as $tmpValue){
					if (!in_array($tmpValue, $valueToAssign)){
						$valueToAssign[] = $tmpValue;
					}
				}
			}else{
				if ($newValue != $originalValue){
					$valueToAssign = array();
					$valueToAssign[] = $originalValue;
					$valueToAssign[] = $newValue;
				}else{
					return;
				}
			}
			$this->assign($variableName, $valueToAssign);
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
		if (empty($activeLanguage)){
			$code = 'en';
		}else{
			$code = $activeLanguage->code;
		}
		$translator = new Translator('lang', $code);
	}
	if (is_array($params)) {
		$defaultText = isset($params['defaultText']) ? $params['defaultText'] : null;
		$inAttribute = isset($params['inAttribute']) ? $params['inAttribute'] : false;
		$replacementValues = [];
		foreach ($params as $index => $param){
			if (is_numeric($index)){
				$replacementValues[$index] = $param;
			}
		}
		return $translator->translate($params['text'], $defaultText, $replacementValues, $inAttribute);
	} else {
		return $translator->translate($params, null, [], false);
	}
}


/** @noinspection PhpUnused */
function display_if_inconsistent($params, $content, /** @noinspection PhpUnusedParameterInspection */ &$smarty, /** @noinspection PhpUnusedParameterInspection */ &$repeat){
	//This function is called twice, once for the opening tag and once for the
	//closing tag.  Content is only set if
	if (isset($content)) {
		$array = $params['array'];
		$key = $params['key'];

		if (count($array) === 1) {
			// If we have only one row of items, display that row
			return empty($array[0][$key]) ? '' : $content;
		}
		$consistent = true;
		$firstValue = null;
		$iterationNumber = 0;
		foreach ($array as $arrayValue){
			if ($iterationNumber == 0){
				$firstValue = $arrayValue[$key];
			}else{
				if ($firstValue != $arrayValue[$key]){
					$consistent = false;
					break;
				}
			}
			$iterationNumber++;
		}
		if ($consistent == false){
			return $content;
		}else{
			return "";
		}
	}
	return null;
}

/** @noinspection PhpUnused */
function display_if_field_inconsistent($params, $content, /** @noinspection PhpUnusedParameterInspection */ &$smarty, /** @noinspection PhpUnusedParameterInspection */ &$repeat)
{
	if (isset($content)) {
		global $interface;
		$array = $params['array'];
		$key = $params['key'];
		$var = $params['var'];

		if (count($array) === 1) {
			// If we have only one row of items, display that row
			if (empty($array[0]->$key)) {
				$interface->assign($var, false);
				$returnValue = '';
			} else {
				$interface->assign($var, true);
				$returnValue = $content;
			}

			return $returnValue;
		}
		$consistent = true;
		$firstValue = null;
		$iterationNumber = 0;
		foreach ($array as $arrayValue) {
			if ($iterationNumber == 0) {
				$firstValue = $arrayValue->$key;
			} else {
				if ($firstValue != $arrayValue->$key) {
					$consistent = false;
					break;
				}
			}
			$iterationNumber++;
		}
		if ($consistent == false) {
			$interface->assign($var, true);
			return $content;
		} else {
			$interface->assign($var, false);
			return "";
		}
	}
	return null;
}