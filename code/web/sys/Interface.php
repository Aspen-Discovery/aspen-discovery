<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once 'Smarty/Smarty.class.php';
require_once ROOT_DIR . '/sys/mobile_device_detect.php';

// Smarty Extension class
class UInterface extends Smarty
{
	public $lang;
	private $vufindTheme;   // which theme(s) are active?
	private $themes; // The themes that are active
	private $isMobile = false;
	private $url;

	function UInterface()
	{
		global $configArray;
		global $timer;

		$local = $configArray['Site']['local'];
		$this->vufindTheme = $configArray['Site']['theme'];

		$this->isMobile = mobile_device_detect();
		$this->assign('isMobile', $this->isMobile ? 'true' : 'false');
		$this->assign('device', get_device_name());

		//Figure out google translate id
		if (isset($configArray['Translation']['google_translate_key']) && strlen($configArray['Translation']['google_translate_key']) > 0){
			$this->assign('google_translate_key', $configArray['Translation']['google_translate_key']);
			$this->assign('google_included_languages', $configArray['Translation']['includedLanguages']);
		}

		//Check to see if we have a google site verification key
		if (isset($configArray['Site']['google_verification_key']) && strlen($configArray['Site']['google_verification_key']) > 0){
			$this->assign('google_verification_key', $configArray['Site']['google_verification_key']);
		}

		//Get all images related to the event
		if (isset($configArray['Maps']) && isset($configArray['Maps']['apiKey'])){
			$mapsKey = $configArray['Maps']['apiKey'];
			$this->assign('mapsKey', $mapsKey);
		}
		if (isset($configArray['Maps']) && isset($configArray['Maps']['browserKey'])){
			$mapsKey = $configArray['Maps']['browserKey'];
			$this->assign('mapsBrowserKey', $mapsKey);
		}

		if (isset($_REQUEST['print'])) {
			$this->assign('print', true);
		}

		// Check to see if multiple themes were requested; if so, build an array,
		// otherwise, store a single string.
		$themeArray = explode(',', $this->vufindTheme);
		//Make sure we always fall back to the default theme so a template does not have to be overridden.
		$themeArray[] = 'default';
		if (count($themeArray) > 1) {
			$this->template_dir = array();
			foreach ($themeArray as $currentTheme) {
				$currentTheme = trim($currentTheme);
				$this->template_dir[] = "$local/interface/themes/$currentTheme";
			}
		} else {
			$this->template_dir  = "$local/interface/themes/{$this->vufindTheme}";
		}
		$this->themes = $themeArray;
		if (isset($timer)){
			$timer->logTime('Set theme');
		}

		// Create an MD5 hash of the theme name -- this will ensure that it's a
		// writeable directory name (since some config.ini settings may include
		// problem characters like commas or whitespace).
		$md5 = md5($this->vufindTheme);
		$this->compile_dir   = "$local/interface/compile/$md5";
		if (!is_dir($this->compile_dir)) {
			if (!mkdir($this->compile_dir)){
				echo("Could not create compile directory {$this->compile_dir}");
				die();
			}
		}
		$this->cache_dir     = "$local/interface/cache/$md5";
		if (!is_dir($this->cache_dir)) {
			if (!mkdir($this->cache_dir)){
				echo("Could not create cache directory {$this->cache_dir}");
				die();
			}
		}
		$this->plugins_dir   = array('plugins', "$local/interface/plugins");
		$this->caching       = false;
		$this->debug         = true;
		$this->compile_check = true;

		unset($local);

		$this->register_block('display_if_inconsistent', 'display_if_inconsistent');
//		$this->register_block('display_if_inconsistent_in_any_manifestation', 'display_if_inconsistent_in_any_manifestation');
		$this->register_block('display_if_set', 'display_if_set');
		$this->register_function('translate', 'translate');
		$this->register_function('char', 'char');

		$this->assign('site', $configArray['Site']);
		$this->assign('path', $configArray['Site']['path']);
		$defaultConfig = $configArray['Site']['path'];
		$url = $_SERVER['SERVER_NAME'];
		if (isset($_SERVER['HTTPS'])){
			$url = "https://" . $url;
		}else{
			$url = "http://" . $url;
		}
		if (strlen($configArray['Site']['path']) > 0){
			$url .= '/' . $configArray['Site']['path'];
		}
		$this->url = $url;
		$this->assign('template_dir',$this->template_dir);
		$this->assign('url', $url);
		$this->assign('coverUrl', $configArray['Site']['coverUrl']);

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
		$this->assign('ils', $configArray['Catalog']['ils']);
		if (isset($configArray['Catalog']['url'])){
			$this->assign('classicCatalogUrl', $configArray['Catalog']['url']);
		}else if (isset($configArray['Catalog']['hipUrl'])){
			$this->assign('classicCatalogUrl', $configArray['Catalog']['hipUrl']);
		}
		$this->assign('showLinkToClassicInMaintenanceMode', $configArray['Catalog']['showLinkToClassicInMaintenanceMode']);
		$this->assign('showConvertListsFromClassic', $configArray['Catalog']['showConvertListsFromClassic']);

		$this->assign('theme', $this->vufindTheme);
		$this->assign('primaryTheme', reset($themeArray));
		$this->assign('device', get_device_name());

		// Determine Offline Mode
		global $offlineMode;
		$offlineMode = false;
		if ($configArray['Catalog']['offline']){
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

		$displaySidebarMenu = false;
		if (isset($configArray['Site']['sidebarMenu'])) {
			$displaySidebarMenu = (bool) $configArray['Site']['sidebarMenu'];
		}
		$this->assign('displaySidebarMenu', $displaySidebarMenu);

		$this->assign('currentTab', 'Search');

		$this->assign('authMethod', $configArray['Authentication']['method']);

		if ($configArray['System']['debug']){
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
			$ie8 = stristr($_SERVER['HTTP_USER_AGENT'], 'msie 8') || stristr($_SERVER['HTTP_USER_AGENT'], 'trident/5'); //trident/5 should catch ie9 compability modes
			$this->assign('ie8', $ie8);
		}

		$session = new Session();
		$session->session_id = session_id();
		if ($session->find(true)){
			$this->assign('session', session_id() . ', remember me ' . $session->remember_me);
		}else{
			$this->assign('session', session_id() . ' - not saved');
		}

		/** @var IndexingProfile $activeRecordProfile */
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
			$user = UserAccount::getLoggedInUser();
			//Figure out if we should show a link to pay fines.
			$homeLibrary = Library::getLibraryForLocation($user->homeLocationId);
			$showEcomerceLink     = isset($homeLibrary) && $homeLibrary->showEcommerceLink == 1;

			if ($showEcomerceLink) {
				$this->assign('minimumFineAmount', $homeLibrary->minimumFineAmount);
				$this->assign('payFinesLinkText', $homeLibrary->payFinesLinkText);
				$this->assign('showRefreshAccountButton', $homeLibrary->showRefreshAccountButton);

				// Determine E-comerce Link
				$ecomerceLink = null;
				if ($homeLibrary->payFinesLink == 'default') {
					global $configArray;
					$defaultEcommerceLink = $configArray['Site']['ecommerceLink'];
					if (!empty($defaultEcommerceLink)) {
						$ecomerceLink = $defaultEcommerceLink;
					} else {
						$showEcomerceLink = false;
					}
				} elseif (!empty($homeLibrary->payFinesLink)) {
						$ecomerceLink = $homeLibrary->payFinesLink;
				} else {
					$showEcomerceLink = false;
				}
				$this->assign('ecommerceLink', $ecomerceLink);
			}
			$this->assign('showEcommerceLink', $showEcomerceLink);
		}
	}

	public function getUrl(){
		return $this->url;
	}

	/**
	 * Get the current active theme setting.
	 *
	 * @access  public
	 * @return  string
	 */
	public function getVuFindTheme()
	{
		return $this->vufindTheme;
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

	function setPageTitle($title)
	{
		//Marmot override, add the name of the site to the title unless we are using the mobile interface.
		$this->assign('pageTitleShort', translate($title));
		if ($this->isMobile){
			$this->assign('pageTitle', translate($title));
		}else{
			$this->assign('pageTitle', translate($title) . ' | ' . $this->get_template_vars('librarySystemName'));
		}
	}

	function  getShortPageTitle(){
		return $this->get_template_vars('shortPageTitle');
	}

	function getLanguage()
	{
		return $this->lang;
	}

	function setLanguage($lang)
	{
		global $configArray;

		$this->lang = $lang;
		$this->assign('userLang', $lang);
		$this->assign('allLangs', $configArray['Languages']);
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

	public function isMobile(){
		return $this->isMobile;
	}

	public function getPrimaryTheme(){
		if (is_array($this->themes)){
			return reset($this->themes);
		}else{
			return $this->themes;
		}
	}

	function loadDisplayOptions(){
		global $library;
		global $locationSingleton;
		global $configArray;
		$location = $locationSingleton->getActiveLocation();
		$showHoldButton = 1;
		$showHoldButtonInSearchResults = 1;
		$this->assign('logoLink', $configArray['Site']['path']);
		$this->assign('logoAlt', 'Return to Catalog Home');
		if (isset($library) && $library->useHomeLinkForLogo){
			if (isset($location) && strlen($location->homeLink) > 0 && $location->homeLink != 'default'){
				$this->assign('logoAlt', 'Library Home Page');
				$this->assign('logoLink', $location->homeLink);
			}elseif (isset($library) && strlen($library->homeLink) > 0 && $library->homeLink != 'default'){
				$this->assign('logoAlt', 'Library Home Page');
				$this->assign('logoLink', $library->homeLink);
			}
		}

		if (isset($location) && strlen($location->homeLink) > 0 && $location->homeLink != 'default'){
			$this->assign('homeLink', $location->homeLink);
		}elseif (isset($library) && strlen($library->homeLink) > 0 && $library->homeLink != 'default'){
			$this->assign('homeLink', $library->homeLink);
		}
		if (isset($library)){
			$this->assign('facebookLink', $library->facebookLink);
			$this->assign('twitterLink', $library->twitterLink);
			$this->assign('youtubeLink', $library->youtubeLink);
			$this->assign('instagramLink', $library->instagramLink);
			$this->assign('goodreadsLink', $library->goodreadsLink);
			$this->assign('generalContactLink', $library->generalContactLink);
			$this->assign('showLoginButton', $library->showLoginButton);
			$this->assign('showAdvancedSearchbox', $library->showAdvancedSearchbox);
			$this->assign('enablePospectorIntegration', $library->enablePospectorIntegration);
			$this->assign('showTagging', $library->showTagging);
			$this->assign('showRatings', $library->showRatings);
			$this->assign('show856LinksAsTab', $library->show856LinksAsTab);
			$this->assign('showSearchTools', $library->showSearchTools);
			$this->assign('alwaysShowSearchResultsMainDetails', $library->alwaysShowSearchResultsMainDetails);
			$this->assign('showExpirationWarnings', $library->showExpirationWarnings);
			$this->assign('expiredMessage', $library->expiredMessage);
			$this->assign('expirationNearMessage', $library->expirationNearMessage);
			$this->assign('showSimilarTitles', $library->showSimilarTitles);
			$this->assign('showSimilarAuthors', $library->showSimilarAuthors);
			$this->assign('showItsHere', $library->showItsHere);
			$this->assign('enableMaterialsBooking', $library->enableMaterialsBooking);
			$this->assign('showHoldButtonForUnavailableOnly', $library->showHoldButtonForUnavailableOnly);
			$this->assign('horizontalSearchBar', $library->horizontalSearchBar);
			$this->assign('sideBarOnRight', $library->sideBarOnRight);
			$this->assign('showHoldCancelDate', $library->showHoldCancelDate);
			$this->assign('showPikaLogo', $library->showPikaLogo);
			$this->assign('allowMasqueradeMode', $library->allowMasqueradeMode);
			$this->assign('allowReadingHistoryDisplayInMasqueradeMode', $library->allowReadingHistoryDisplayInMasqueradeMode);
			$this->assign('interLibraryLoanName', $library->interLibraryLoanName);
			$this->assign('interLibraryLoanUrl', $library->interLibraryLoanUrl);

			if ($this->getVariable('displaySidebarMenu') && !$library->showSidebarMenu){
				$this->assign('displaySidebarMenu', false);
			}
			$this->assign('sidebarMenuButtonText', $library->sidebarMenuButtonText);
			$this->assign('showGroupedHoldCopiesCount', $library->showGroupedHoldCopiesCount);
			$this->assign('showOnOrderCounts', $library->showOnOrderCounts);
		}else{
			$this->assign('showLoginButton', 1);
			$this->assign('showAdvancedSearchbox', 1);
			$this->assign('enablePospectorIntegration', isset($configArray['Content']['Prospector']) && $configArray['Content']['Prospector'] == true ? 1 : 0);
			$this->assign('showTagging', 1);
			$this->assign('showRatings', 1);
			$this->assign('show856LinksAsTab', 1);
			$this->assign('showSearchTools', 1);
			$this->assign('alwaysShowSearchResultsMainDetails', 0);
			$this->assign('showExpirationWarnings', 1);
			$this->assign('showSimilarTitles', 1);
			$this->assign('showSimilarAuthors', 1);
			$this->assign('showItsHere', 0);
			$this->assign('enableMaterialsBooking', 0);
			$this->assign('showHoldButtonForUnavailableOnly', 0);
			$this->assign('horizontalSearchBar', 0);
			$this->assign('sideBarOnRight', 0);
			$this->assign('showHoldCancelDate', 0);
			$this->assign('showPikaLogo', 1);
			$this->assign('allowMasqueradeMode', 0);
			$this->assign('allowReadingHistoryDisplayInMasqueradeMode', 0);
			$this->assign('showGroupedHoldCopiesCount', 1);
			$this->assign('showOnOrderCounts', true);
		}
		if (isset($library) && $location != null){ // library and location
			$this->assign('showFavorites', $location->showFavorites && $library->showFavorites);
			$this->assign('showComments', $location->showComments && $library->showComments);
//			$this->assign('showTextThis', $location->showTextThis && $library->showTextThis);
			$this->assign('showEmailThis', $location->showEmailThis && $library->showEmailThis);
			$this->assign('showStaffView', $location->showStaffView && $library->showStaffView);
			$this->assign('showShareOnExternalSites', $location->showShareOnExternalSites && $library->showShareOnExternalSites);
			$this->assign('showQRCode', $location->showQRCode && $library->showQRCode);
			$this->assign('showStaffView', $location->showStaffView && $library->showStaffView);
			$this->assign('showGoodReadsReviews', $location->showGoodReadsReviews && $library->showGoodReadsReviews);
			$showHoldButton = (($location->showHoldButton == 1) && ($library->showHoldButton == 1)) ? 1 : 0;
			$showHoldButtonInSearchResults = (($location->showHoldButton == 1) && ($library->showHoldButtonInSearchResults == 1)) ? 1 : 0;
			$this->assign('showSimilarTitles', $library->showSimilarTitles);
			$this->assign('showSimilarAuthors', $library->showSimilarAuthors);
			$this->assign('showStandardReviews', (($location->showStandardReviews == 1) && ($library->showStandardReviews == 1)) ? 1 : 0);
		}elseif ($location != null){ // location only
			$this->assign('showFavorites', $location->showFavorites);
			$this->assign('showComments', $location->showComments);
//			$this->assign('showTextThis', $location->showTextThis);
			$this->assign('showEmailThis', $location->showEmailThis);
			$this->assign('showShareOnExternalSites', $location->showShareOnExternalSites);
			$this->assign('showStaffView', $location->showStaffView);
			$this->assign('showQRCode', $location->showQRCode);
			$this->assign('showStaffView', $location->showStaffView);
			$this->assign('showGoodReadsReviews', $location->showGoodReadsReviews);
			$this->assign('showStandardReviews', $location->showStandardReviews);
			$showHoldButton = $location->showHoldButton;
		}elseif (isset($library)){ // library only
			$this->assign('showFavorites', $library->showFavorites);
			$showHoldButton = $library->showHoldButton;
			$showHoldButtonInSearchResults = $library->showHoldButtonInSearchResults;
			$this->assign('showComments', $library->showComments);
//			$this->assign('showTextThis', $library->showTextThis);
			$this->assign('showEmailThis', $library->showEmailThis);
			$this->assign('showShareOnExternalSites', $library->showShareOnExternalSites);
			$this->assign('showStaffView', $library->showStaffView);
			$this->assign('showQRCode', $library->showQRCode);
			$this->assign('showStaffView', $library->showStaffView);
			$this->assign('showGoodReadsReviews', $library->showGoodReadsReviews);
			$this->assign('showStandardReviews', $library->showStandardReviews);
		}else{ // neither library nor location
			$this->assign('showFavorites', 1);
			$this->assign('showComments', 1);
//			$this->assign('showTextThis', 1);
			$this->assign('showEmailThis', 1);
			$this->assign('showShareOnExternalSites', 1);
			$this->assign('showQRCode', 1);
			$this->assign('showStaffView', 1);
			$this->assign('showGoodReadsReviews', 1);
			$this->assign('showStandardReviews', 1);
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
		$this->assign('librarySystemName', 'Marmot'); //TODO: need better default
		if (isset($library)){
			$this->assign('showRatings', $library->showRatings);
			$this->assign('allowPinReset', $library->allowPinReset);
			$this->assign('librarySystemName', $library->displayName);
			$this->assign('showLibraryHoursAndLocationsLink', $library->showLibraryHoursAndLocationsLink);
			//Check to see if we should just call it library location
			$numLocations = $library->getNumLocationsForLibrary();
			$this->assign('numLocations', $numLocations);
			if ($numLocations == 1){
				$locationForLibrary = new Location();
				$locationForLibrary->libraryId = $library->libraryId;
				$locationForLibrary->find(true);
				$numHours = $locationForLibrary->getNumHours();
				$this->assign('numHours', $numHours);
			}
			$this->assign('showDisplayNameInHeader', $library->showDisplayNameInHeader);
			$this->assign('externalMaterialsRequestUrl', $library->externalMaterialsRequestUrl);
		}else{
			$this->assign('showLibraryHoursAndLocationsLink', 1);
			$this->assign('showRatings', 1);
			$this->assign('allowPinReset', 0);
			$this->assign('showDisplayNameInHeader', 0);
		}
		if ($location != null){
			$this->assign('showDisplayNameInHeader', $location->showDisplayNameInHeader);
			$this->assign('librarySystemName', $location->displayName);
		}

		//Determine whether or not materials request functionality should be enabled
		require_once ROOT_DIR . '/sys/MaterialsRequest.php';
		$this->assign('enableMaterialsRequest', MaterialsRequest::enableMaterialsRequest());

		//Load library links
		if (isset($library)){
			$links = $library->libraryLinks;
			$libraryHelpLinks = array();
			$libraryAccountLinks = array();
			$expandedLinkCategories = array();
			/** @var LibraryLink $libraryLink */
			foreach ($links as $libraryLink){
				if ($libraryLink->showInHelp || (!$libraryLink->showInHelp && !$libraryLink->showInAccount)){
					if (!array_key_exists($libraryLink->category, $libraryHelpLinks)){
						$libraryHelpLinks[$libraryLink->category] = array();
					}
					$libraryHelpLinks[$libraryLink->category][$libraryLink->linkText] = $libraryLink;
				}
				if ($libraryLink->showInAccount){
					if (!array_key_exists($libraryLink->category, $libraryAccountLinks)){
						$libraryAccountLinks[$libraryLink->category] = array();
					}
					$libraryAccountLinks[$libraryLink->category][$libraryLink->linkText] = $libraryLink;
				}
				if ($libraryLink->showExpanded){
					$expandedLinkCategories[$libraryLink->category] = 1;
				}
			}
			$this->assign('libraryAccountLinks', $libraryAccountLinks);
			$this->assign('libraryHelpLinks', $libraryHelpLinks);
			$this->assign('expandedLinkCategories', $expandedLinkCategories);

			$topLinks = $library->libraryTopLinks;
			$this->assign('topLinks', $topLinks);
		}
	}

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
		global $configArray;

		$translator = new I18N_Translator('lang', $configArray['Site']['language'],
		$configArray['System']['missingTranslations']);
	}
	if (is_array($params)) {
		return $translator->translate($params['text']);
	} else {
		return $translator->translate($params);
	}
}

function display_if_inconsistent($params, $content, &$smarty, &$repeat){
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

//function display_if_inconsistent_in_any_manifestation($params, $content, &$smarty, &$repeat){
//	//This function is called twice, once for the opening tag and once for the
//	//closing tag.  Content is only set if
//	if (isset($content)) {
//		$manifestations = $params['array'];
//		$key            = $params['key'];
//
////		if (count($manifestations) === 1) {
////			// If we have only one row of items, display that row
////			return empty($manifestations[0][$key]) ? '' : $content;
////		}
//		$consistent      = true;
//		$firstValue      = null;
//		$iterationNumber = 0;
//		foreach ($manifestations as $manifestation) {
//
//			foreach ($manifestation['relatedRecords'] as $arrayValue) {
//				if ($iterationNumber == 0) {
//					$firstValue = $arrayValue[$key];
//				} else {
//					if ($firstValue != $arrayValue[$key]) {
//						$consistent = false;
//						break;
//					}
//				}
//				$iterationNumber++;
//			}
//		}
//		if ($consistent == false){
//			return $content;
//		}else{
//			return "";
//		}
//	}
//	return null;
//}

function display_if_set($params, $content, &$smarty, &$repeat){
	//This function is called twice, once for the opening tag and once for the
	//closing tag.  Content is only set if
	if (isset($content)) {
		$hasData = false;
		$firstValue = null;
		$array = $params['array'];
		$key = $params['key'];
		foreach ($array as $arrayValue){
			if (isset($arrayValue[$key]) && !empty($arrayValue[$key])){
				$hasData = true;
			}
		}
		if ($hasData){
			return $content;
		}else{
			return "";
		}
	}
	return null;
}