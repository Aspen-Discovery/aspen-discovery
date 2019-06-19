<?php
require_once 'bootstrap.php';

global $aspenUsage;

global $timer;
global $memoryWatcher;

//Do additional tasks that are only needed when running the full website
loadModuleActionId();
$timer->logTime("Loaded Module and Action Id");
$memoryWatcher->logMemory("Loaded Module and Action Id");
spl_autoload_register('aspen_autoloader');
initializeSession();
$timer->logTime("Initialized session");

if (isset($_REQUEST['test_role'])){
	if ($_REQUEST['test_role'] == ''){
		setcookie('test_role', $_REQUEST['test_role'], time() - 1000, '/');
	}else{
		setcookie('test_role', $_REQUEST['test_role'], 0, '/');
	}
}

// Start Interface
$interface = new UInterface();
$timer->logTime('Create interface');

//Set footer information
/** @var Location $locationSingleton */
global $locationSingleton;
getGitBranch();

$interface->loadDisplayOptions();
$timer->logTime('Loaded display options within interface');

global $active_ip;

$googleAnalyticsId        = isset($configArray['Analytics']['googleAnalyticsId'])        ? $configArray['Analytics']['googleAnalyticsId'] : false;
$googleAnalyticsLinkingId = isset($configArray['Analytics']['googleAnalyticsLinkingId']) ? $configArray['Analytics']['googleAnalyticsLinkingId'] : false;
$interface->assign('googleAnalyticsId', $googleAnalyticsId);
$interface->assign('googleAnalyticsLinkingId', $googleAnalyticsLinkingId);
if ($googleAnalyticsId) {
	$googleAnalyticsDomainName = isset($configArray['Analytics']['domainName']) ? $configArray['Analytics']['domainName'] : strstr($_SERVER['SERVER_NAME'], '.');
	// check for a config setting, use that if found, otherwise grab domain name  but remove the first subdomain
	$interface->assign('googleAnalyticsDomainName', $googleAnalyticsDomainName);
}
global $library;
global $offlineMode;

$interface->assign('islandoraEnabled', $configArray['Islandora']['enabled']);

//Get the name of the active instance
//$inLibrary, is used to pre-select auto-logout on place hold forms;
// to hide the remember me option on login pages;
// and to show the Location in the page footer
if ($locationSingleton->getIPLocation() != null){
	$interface->assign('inLibrary', true);
	$physicalLocation = $locationSingleton->getIPLocation()->displayName;
}else{
	$interface->assign('inLibrary', false);
	$physicalLocation = 'Home';
}
$interface->assign('physicalLocation', $physicalLocation);

$productionServer = $configArray['Site']['isProduction'];
$interface->assign('productionServer', $productionServer);

$location = $locationSingleton->getActiveLocation();

// Determine Module and Action
$module = (isset($_GET['module'])) ? $_GET['module'] : null;
$module = preg_replace('/[^\w]/', '', $module);
$action = (isset($_GET['action'])) ? $_GET['action'] : null;
$action = preg_replace('/[^\w]/', '', $action);

//Redirect some common spam components so they go to a valid place, and redirect old actions to new
if ($action == 'trackback'){
	$action = null;
}
if ($action == 'SimilarTitles'){
	$action = 'Home';
}
//Set these initially in case user login fails, we will need the module to be set.
$interface->assign('module', $module);
$interface->assign('action', $action);

global $solrScope;
global $scopeType;
global $isGlobalScope;
$interface->assign('scopeType', $scopeType);
$interface->assign('solrScope', "$solrScope - $scopeType");
$interface->assign('isGlobalScope', $isGlobalScope);

//Set that the interface is a single column by default
$interface->assign('page_body_style', 'one_column');

$interface->assign('showFines', $configArray['Catalog']['showFines']);

$interface->assign('activeIp', Location::getActiveIp());

// Check system availability
$mode = checkAvailabilityMode();
if ($mode['online'] === false) {
	// Why are we offline?
	switch ($mode['level']) {
		// Forced Downtime
		case "unavailable":
			$interface->display($mode['template']);
			break;

			// Should never execute. checkAvailabilityMode() would
			//    need to know we are offline, but not why.
		default:
			$interface->display($mode['template']);
			break;
	}
	exit();
}
$timer->logTime('Checked availability mode');

// Setup Translator
global $language;
global $serverName;
//Get the active language
$userLanguage = UserAccount::getUserInterfaceLanguage();
if ($userLanguage == ''){
	$language = strip_tags((isset($_SESSION['language'])) ? $_SESSION['language'] : 'en');
}else{
	$language = $userLanguage;
}
if (isset($_REQUEST['myLang'])) {
	$newLanguage = strip_tags($_REQUEST['myLang']);
	if (($userLanguage != '') && ($newLanguage != UserAccount::getUserInterfaceLanguage())){
		$userObject = UserAccount::getActiveUserObj();
		$userObject->interfaceLanguage = $newLanguage;
		$userObject->update();
	}
	if ($language != $newLanguage){
		$language = $newLanguage;
		$_SESSION['language'] = $language;
		//Clear the preference cookie
		if (isset($_COOKIE['searchPreferenceLanguage'])){
			//Clear the cookie when we change languages
			setcookie('searchPreferenceLanguage', $_COOKIE['searchPreferenceLanguage'], time() - 1000, '/');
			unset($_COOKIE['searchPreferenceLanguage']);
		}
	}
}
if (!UserAccount::isLoggedIn() && isset($_COOKIE['searchPreferenceLanguage'])) {
	$showLanguagePreferencesBar = true;
	$interface->assign('searchPreferenceLanguage', $_COOKIE['searchPreferenceLanguage']);
}elseif (UserAccount::isLoggedIn()){
	$showLanguagePreferencesBar = $language != 'en' && UserAccount::getActiveUserObj()->searchPreferenceLanguage == -1;
	$interface->assign('searchPreferenceLanguage', UserAccount::getActiveUserObj()->searchPreferenceLanguage);
}else{
	$showLanguagePreferencesBar = $language != 'en';
	$interface->assign('searchPreferenceLanguage', -1);
}

$interface->assign('showLanguagePreferencesBar', $showLanguagePreferencesBar);

// Make sure language code is valid, reset to default if bad:
$validLanguages = [];
try{
	require_once ROOT_DIR . '/sys/Translation/Language.php';
	$validLanguage = new Language();
	$validLanguage->orderBy("weight");
	$validLanguage->find();
	while ($validLanguage->fetch()){
		$validLanguages[$validLanguage->code] = clone $validLanguage;
	}
}catch(Exception $e){
	$defaultLanguage = new Language();
	$defaultLanguage->code = 'en';
	$defaultLanguage->displayName = 'English';
	$defaultLanguage->displayNameEnglish = 'English';
	$defaultLanguage->facetValue = 'English';
	$validLanguages['en'] = $defaultLanguage;
	$language = 'en';
}

if (!array_key_exists($language, $validLanguages)) {
	$language = 'en';
}
/** @var Language $activeLanguage */
global $activeLanguage;
global $translator;
$activeLanguage = $validLanguages[$language];
$interface->assign('validLanguages', $validLanguages);
if ($translator == null){
	$translator = new Translator('lang', $language);
}
$timer->logTime('Translator setup');

$interface->setLanguage($activeLanguage);

//Set System Message after translator has been setup
if ($configArray['System']['systemMessage']){
	$interface->assign('systemMessage', translate($configArray['System']['systemMessage']));
}else if ($offlineMode){
	$interface->assign('systemMessage', "<p class='alert alert-warning'>" . translate(['text'=>'offline_notice', 'defaultText'=>"<strong>The library system is currently offline.</strong> We are unable to retrieve information about your account at this time."]) . "</p>");
}else{
	if ($library && strlen($library->systemMessage) > 0){
		$interface->assign('systemMessage', translate($library->systemMessage));
	}
}

$deviceName = get_device_name();
$interface->assign('deviceName', $deviceName);

//Look for spammy searches and kill them
if (isset($_REQUEST['lookfor'])) {
	// Advanced Search with only the default search group (multiple search groups are named lookfor0, lookfor1, ... )
	if (is_array($_REQUEST['lookfor'])) {
		foreach ($_REQUEST['lookfor'] as $i => $searchTerm) {
			if (preg_match('/http:|mailto:|https:/i', $searchTerm)) {
				AspenError::raiseError("Sorry it looks like you are searching for a website, please rephrase your query.");
				$_REQUEST['lookfor'][$i] = '';
				$_GET['lookfor'][$i]     = '';
			}
			if (strlen($searchTerm) >= 256) {
				AspenError::raiseError("Sorry your query is too long, please rephrase your query.");
				$_REQUEST['lookfor'][$i] = '';
				$_GET['lookfor'][$i]     = '';
			}
		}
	}
	// Basic Search
	else {
		$searchTerm = $_REQUEST['lookfor'];
		if (preg_match('/http:|mailto:|https:/i', $searchTerm)) {
			AspenError::raiseError("Sorry it looks like you are searching for a website, please rephrase your query.");
			$_REQUEST['lookfor'] = '';
			$_GET['lookfor']     = '';
		}
		if (strlen($searchTerm) >= 256) {
			AspenError::raiseError("Sorry your query is too long, please rephrase your query.");
			$_REQUEST['lookfor'] = '';
			$_GET['lookfor']     = '';
		}
	}
}

$isLoggedIn = UserAccount::isLoggedIn();
$timer->logTime('Check if user is logged in');

// Process Authentication, must be done here so we can redirect based on user information
// immediately after logging in.
$interface->assign('loggedIn', $isLoggedIn);
if ($isLoggedIn) {
	$activeUserId = UserAccount::getActiveUserId();
	$interface->assign('activeUserId', $activeUserId);
	$activeUserObject = UserAccount::getActiveUserObj();
	$interface->assign('user', $activeUserObject);
} else if ( (isset($_POST['username']) && isset($_POST['password']) && ($action != 'Account' && $module != 'AJAX')) || isset($_REQUEST['casLogin']) ) {
	//The user is trying to log in
    try {
        $user = UserAccount::login();
    } catch (UnknownAuthenticationMethodException $e) {
        AspenError::raiseError("Error authenticating patron " . $e->getMessage());
    }
    $timer->logTime('Login the user');
	if ($user instanceof AspenError) {
		require_once ROOT_DIR . '/services/MyAccount/Login.php';
		$launchAction = new MyAccount_Login();
		$error_msg    = translate($user->getMessage());
		$launchAction->launch($error_msg);
		exit();
	}elseif(!$user){
		require_once ROOT_DIR . '/services/MyAccount/Login.php';
		$launchAction = new MyAccount_Login();
		$launchAction->launch("Unknown error logging in");
		exit();
	}
	$interface->assign('user', $user);
	$interface->assign('loggedIn', $user == false ? 'false' : 'true');
	if ($user){
		$interface->assign('activeUserId', $user->id);
	}

	//Check to see if there is a followup module and if so, use that module and action for the next page load
	if (isset($_REQUEST['returnUrl'])) {
		$followupUrl = $_REQUEST['returnUrl'];
		header("Location: " . $followupUrl);
		exit();
	}
	if ($user){
		if (isset($_REQUEST['followupModule']) && isset($_REQUEST['followupAction'])) {

			// For Masquerade Follow up, start directly instead of a redirect
			if ($_REQUEST['followupAction'] == 'Masquerade' && $_REQUEST['followupModule'] == 'MyAccount') {
				global $logger;
				$logger->log("Processing Masquerade after logging in", Logger::LOG_ERROR);
				require_once ROOT_DIR . '/services/MyAccount/Masquerade.php';
				$masquerade = new MyAccount_Masquerade();
				$masquerade->launch();
				die;
			}

			echo("Redirecting to followup location");
			$followupUrl = $configArray['Site']['path'] . "/". strip_tags($_REQUEST['followupModule']);
			if (!empty($_REQUEST['recordId'])) {
				$followupUrl .= "/" . strip_tags($_REQUEST['recordId']);
			}
			$followupUrl .= "/" .  strip_tags($_REQUEST['followupAction']);
			if(isset($_REQUEST['comment'])) $followupUrl .= "?comment=" . urlencode($_REQUEST['comment']);
			header("Location: " . $followupUrl);
			exit();
		}
	}
	if (isset($_REQUEST['followup']) || isset($_REQUEST['followupModule'])){
		$module = isset($_REQUEST['followupModule']) ? $_REQUEST['followupModule'] : $configArray['Site']['defaultModule'];
		$action = isset($_REQUEST['followup']) ? $_REQUEST['followup'] : (isset($_REQUEST['followupAction']) ? $_REQUEST['followupAction'] : 'Home');
		if (isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
		}elseif (isset($_REQUEST['recordId'])){
			$id = $_REQUEST['recordId'];
		}
		if (isset($id)){
			$_REQUEST['id'] = $id;
		}
		$_REQUEST['module'] = $module;
		$_REQUEST['action'] = $action;
	}
}
$timer->logTime('User authentication');

//Load user data for the user as long as we aren't in the act of logging out.
if (UserAccount::isLoggedIn() && (!isset($_REQUEST['action']) || $_REQUEST['action'] != 'Logout')){
	$userDisplayName = UserAccount::getUserDisplayName();
	$interface->assign('userDisplayName', $userDisplayName);
	$userRoles = UserAccount::getActiveRoles();
	$interface->assign('userRoles', $userRoles);
	$disableCoverArt = UserAccount::getDisableCoverArt();
	$interface->assign('disableCoverArt', $disableCoverArt);
	$hasLinkedUsers = UserAccount::hasLinkedUsers();
	$interface->assign('hasLinkedUsers', $hasLinkedUsers);
	$interface->assign('pType', UserAccount::getUserPType());
	$interface->assign('canMasquerade', UserAccount::getActiveUserObj()->canMasquerade());
	$masqueradeMode = UserAccount::isUserMasquerading();
	$interface->assign('masqueradeMode', $masqueradeMode);
	if ($masqueradeMode){
		$guidingUser = UserAccount::getGuidingUserObject();
		$interface->assign('guidingUser', $guidingUser);
	}
	$interface->assign('userHasCatalogConnection', UserAccount::getUserHasCatalogConnection());


	$homeLibrary = Library::getLibraryForLocation(UserAccount::getUserHomeLocationId());
	if (isset($homeLibrary)){
		$interface->assign('homeLibrary', $homeLibrary->displayName);
	}
	$timer->logTime('Load patron pType');
}else{
	$interface->assign('pType', 'logged out');
	$interface->assign('homeLibrary', 'n/a');
	$masqueradeMode = false;
}

//Find a reasonable default location to go to
if ($module == null && $action == null){
	//We have no information about where to go, go to the default location from config
	$module = $configArray['Site']['defaultModule'];
	$action = 'Home';
}elseif ($action == null){
	$action = 'Home';
}
//Override MyAccount Home as needed
if ($module == 'MyAccount' && $action == 'Home' && UserAccount::isLoggedIn()){
	$user = UserAccount::getLoggedInUser();
	if ($user->getNumCheckedOutTotal() > 0){
		$action ='CheckedOut';
		header('Location:/MyAccount/CheckedOut');
		exit();
	}elseif ($user->getNumHoldsTotal() > 0){
		header('Location:/MyAccount/Holds');
		exit();
	}
}

$interface->assign('module', $module);
$interface->assign('action', $action);
$timer->logTime('Assign module and action');

require_once(ROOT_DIR . '/Drivers/marmot_inc/SearchSources.php');
$searchSources = new SearchSources();
list($enableCombinedResults, $showCombinedResultsFirst, $combinedResultsName) = $searchSources::getCombinedSearchSetupParameters($location, $library);

$interface->assign('curFormatCategory', 'Everything');
if (isset($_REQUEST['filter'])){
	foreach ($_REQUEST['filter'] as $curFilter){
		if (!is_array($curFilter)){
			$filterInfo = explode(":", $curFilter);
			if ($filterInfo[0] == 'format_category'){
				$curFormatCategory = str_replace('"', '', $filterInfo[1]);
				$interface->assign('curFormatCategory', $curFormatCategory);
				break;
			}
		}
	}
}

$searchSource = isset($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';

$interface->assign('searchSource', $searchSource);

//Determine if the top search box and breadcrumbs should be shown.  Not showing these
//Does have a slight performance advantage.
$isAJAX = false;
if ($action == "AJAX" || $action == "JSON" || $module == 'API'){
	$isAJAX = true;
	$interface->assign('showTopSearchBox', 0);
	$interface->assign('showBreadcrumbs', 0);
	if (BotChecker::isRequestFromBot()){
		$aspenUsage->pageViewsByBots++;
	}else{
		$aspenUsage->ajaxRequests++;
	}
}else{
	require_once ROOT_DIR . '/sys/BotChecker.php';
	if (BotChecker::isRequestFromBot()){
		$aspenUsage->pageViewsByBots++;
	}else{
		$aspenUsage->pageViews++;
	}
	if ($isLoggedIn){
		$aspenUsage->pageViewsByAuthenticatedUsers++;
	}

	//Load basic search types for use in the interface.
	/** @var SearchObject_GroupedWorkSearcher $searchObject */
	$searchObject = SearchObjectFactory::initSearchObject();
	$timer->logTime('Create Search Object');
	$searchObject->init();
	$timer->logTime('Init Search Object');
	$catalogSearchIndexes = is_object($searchObject) ? $searchObject->getSearchIndexes() : array();
	$interface->assign('catalogSearchIndexes', $catalogSearchIndexes);

	// Set search results display mode in search-box //
	if ($searchObject->getView()) $interface->assign('displayMode', $searchObject->getView());

	//Load repeat search options
	$interface->assign('searchSources', $searchSources->getSearchSources());

	/** @var SearchObject_ListsSearcher $listSearchIndexes */
    $listSearchIndexes = SearchObjectFactory::initSearchObject('Lists');
    $interface->assign('listSearchIndexes', is_object($listSearchIndexes) ? $listSearchIndexes->getSearchIndexes() : array());

	if ($library->enableGenealogy){
		$genealogySearchObject = SearchObjectFactory::initSearchObject('Genealogy');
		$interface->assign('genealogySearchIndexes', is_object($genealogySearchObject) ? $genealogySearchObject->getSearchIndexes() : array());
        $interface->assign('enableOpenGenealogy', true);
	}

	if ($library->enableArchive){
		$islandoraSearchObject = SearchObjectFactory::initSearchObject('Islandora');
		$interface->assign('islandoraSearchIndexes', is_object($islandoraSearchObject) ? $islandoraSearchObject->getSearchIndexes() : array());
		$interface->assign('enableArchive', true);
	}

    if ($library->enableOpenArchives){
        $openArchivesSearchObject = SearchObjectFactory::initSearchObject('OpenArchives');
        $interface->assign('openArchivesSearchIndexes', is_object($openArchivesSearchObject) ? $openArchivesSearchObject->getSearchIndexes() : array());
        $interface->assign('enableOpenArchives', true);
    }

	//TODO: Re-enable once we do full EDS integration
	/*if ($library->edsApiProfile){
		require_once ROOT_DIR . '/sys/Ebsco/EDS_API.php';
		$ebscoSearchObject = new EDS_API();
		$interface->assign('ebscoSearchTypes', $ebscoSearchObject->getSearchTypes());
	}*/

	if (!($module == 'Search' && $action == 'Home')){
		/** @var SearchObject_BaseSearcher $savedSearch */
		$savedSearch = $searchObject->loadLastSearch();
		//Load information about the search so we can display it in the search box
		if (!is_null($savedSearch)){
			$interface->assign('lookfor',             $savedSearch->displayQuery());
			$interface->assign('searchType',          $savedSearch->getSearchType());
			$searchIndex = $savedSearch->getSearchIndex();
			$interface->assign('searchIndex',         $searchIndex);
			$interface->assign('filterList', $savedSearch->getFilterList());
			$interface->assign('savedSearch', $savedSearch->isSavedSearch());
		}
		$timer->logTime('Load last search for redisplay');
	}

	if (($action =="Home" && $module=="Search") || $action == "AJAX" || $action == "JSON"){
		$interface->assign('showTopSearchBox', 0);
		$interface->assign('showBreadcrumbs', 0);
	}else{
		$interface->assign('showTopSearchBox', 1);
		$interface->assign('showBreadcrumbs', 1);
		if ($library->useHomeLinkInBreadcrumbs){
			$interface->assign('homeBreadcrumbLink', $library->homeLink);
		}else{
			$interface->assign('homeBreadcrumbLink', '/');
		}
		$interface->assign('homeLinkText', $library->homeLinkText);
	}

}

//Determine if we should include autoLogout Code
$ipLocation = $locationSingleton->getPhysicalLocation();
if (!empty($ipLocation) && !empty($library) && $ipLocation->libraryId != $library->libraryId){
	// This is to cover the case of being within one library but the user is browsing another library catalog
	// This will turn off the auto-log out and Internal IP functionality
	// (unless the user includes the opac parameter)
	$ipLocation = null;
}
$isOpac = $locationSingleton->getOpacStatus();
$interface->assign('isOpac', $isOpac);

$onInternalIP = false;
$includeAutoLogoutCode = false;
$automaticTimeoutLength = 0;
$automaticTimeoutLengthLoggedOut = 0;
if (($isOpac || $masqueradeMode || (!empty($ipLocation) && $ipLocation->getOpacStatus()) ) && !$offlineMode) {
	// Make sure we don't have timeouts if we are offline (because it's super annoying when doing offline checkouts and holds)

	//$isOpac is set by URL parameter or cookie; ipLocation->getOpacStatus() returns $opacStatus private variable which comes from the ip tables

	// Turn on the auto log out
	$onInternalIP                    = true;
	$includeAutoLogoutCode           = true;
	$automaticTimeoutLength          = $locationSingleton::DEFAULT_AUTOLOGOUT_TIME;
	$automaticTimeoutLengthLoggedOut = $locationSingleton::DEFAULT_AUTOLOGOUT_TIME_LOGGED_OUT;

	if ($masqueradeMode) {
		// Masquerade Time Out Lengths
			$automaticTimeoutLength = empty($library->masqueradeAutomaticTimeoutLength) ? 90 : $library->masqueradeAutomaticTimeoutLength;
	} else {
		// Determine Regular Time Out Lengths
		if (UserAccount::isLoggedIn()) {
			if (!isset($user)){
				$user = UserAccount::getActiveUserObj();
			}

			// User has bypass AutoLog out setting turned on
			if ($user->bypassAutoLogout == 1) {
				// The account setting profile template only presents this option to users that are staff
				$includeAutoLogoutCode = false;
			}
		}else{
			// Not logged in only include auto logout code if we are not on the home page
			if ($module == 'Search' && $action == 'Home') {
				$includeAutoLogoutCode = false;
			}
		}

		// If we know the branch, use the timeout settings from that branch
		if ($isOpac && $location) {
			$automaticTimeoutLength          = $location->automaticTimeoutLength;
			$automaticTimeoutLengthLoggedOut = $location->automaticTimeoutLengthLoggedOut;
		} // If we know the branch by ip location, use the settings based on that location
		elseif ($ipLocation) {
			$automaticTimeoutLength          = $ipLocation->automaticTimeoutLength;
			$automaticTimeoutLengthLoggedOut = $ipLocation->automaticTimeoutLengthLoggedOut;
		} // Otherwise, use the main branch's settings or the first location's settings
		elseif ($library) {
			$firstLocation            = new Location();
			$firstLocation->libraryId = $library->libraryId;
			$firstLocation->orderBy('isMainBranch DESC');
			if ($firstLocation->find(true)) {
				// This finds either the main branch, or if there isn't one a location
				$automaticTimeoutLength          = $firstLocation->automaticTimeoutLength;
				$automaticTimeoutLengthLoggedOut = $firstLocation->automaticTimeoutLengthLoggedOut;
			}
		}
	}
}
$interface->assign('automaticTimeoutLength', $automaticTimeoutLength);
$interface->assign('automaticTimeoutLengthLoggedOut', $automaticTimeoutLengthLoggedOut);
$interface->assign('onInternalIP', $onInternalIP);
$interface->assign('includeAutoLogoutCode', $includeAutoLogoutCode);

$timer->logTime('Check whether or not to include auto logout code');

// Process Login Followup
//TODO:  this code may need to move up with there other followUp processing above
if (isset($_REQUEST['followup'])) {
	processFollowup();
	$timer->logTime('Process followup');
}

//If there is a hold_message, make sure it gets displayed.
/* //TODO deprecated, but there are still references in scripts that likely need removed
if (isset($_SESSION['hold_message'])) {
	$interface->assign('hold_message', formatHoldMessage($_SESSION['hold_message']));
	unset($_SESSION['hold_message']);
}*/

// Process Solr shard settings
processShards();
$timer->logTime('Process Shards');

// Call Action
// Note: ObjectEditor classes typically have the class name of DB_Object with an 's' added to the end.
//       This distinction prevents the DB_Object from being mistakenly called as the Action class.
if (!is_dir(ROOT_DIR . "/services/$module")){
	$module = 'Error';
	$module = 'Handle404';
	$interface->assign('module','Error');
	$interface->assign('action','Handle404');
	require_once ROOT_DIR . "/services/Error/Handle404.php";
	$actionClass = new Error_Handle404();
	$actionClass->launch();
}else if (is_readable("services/$module/$action.php")) {
	$actionFile = ROOT_DIR . "/services/$module/$action.php";
    /** @noinspection PhpIncludeInspection */
    require_once $actionFile;
	$moduleActionClass = "{$module}_{$action}";
	if (class_exists($moduleActionClass, false)) {
		/** @var Action $service */
		$service = new $moduleActionClass();
		$timer->logTime('Start launch of action');
		try {
			$service->launch();
		}catch (Error $e){
			AspenError::raiseError(new AspenError($e->getMessage(), $e->getTrace()));
		}catch (Exception $e){
			AspenError::raiseError(new AspenError($e->getMessage(), $e->getTrace()));
		}
		$timer->logTime('Finish launch of action');
	}else if (class_exists($action, false)) {
		/** @var Action $service */
		$service = new $action();
		$timer->logTime('Start launch of action');
		try {
			$service->launch();
		}catch (Error $e){
			AspenError::raiseError(new AspenError($e->getMessage(), $e->getTrace()));
		}catch (Exception $e){
			AspenError::raiseError(new AspenError($e->getMessage(), $e->getTrace()));
		}
		$timer->logTime('Finish launch of action');
	}else{
		AspenError::raiseError(new AspenError('Unknown Action'));
	}
} else {
	$interface->assign('showBreadcrumbs', false);
	$interface->assign('sidebar', 'Search/home-sidebar.tpl');
	$requestURI = $_SERVER['REQUEST_URI'];
	$cleanedUrl = strip_tags(urldecode($_SERVER['REQUEST_URI']));
	if ($cleanedUrl != $requestURI){
		AspenError::raiseError(new AspenError("Cannot Load Action and Module the URL provided is invalid"));
	}else{
		AspenError::raiseError(new AspenError("Cannot Load Action '$action' for Module '$module' request '$requestURI'"));
	}
}
$timer->logTime('Finished Index');
$timer->writeTimings();
$memoryWatcher->logMemory("Finished index");
$memoryWatcher->writeMemory();
try{
	$elapsedTime = $timer->getElapsedTime();

	if (!BotChecker::isRequestFromBot()) {
		if ($isAJAX) {
			$aspenUsage->slowAjaxRequests++;
			require_once ROOT_DIR . '/sys/SystemLogging/SlowAjaxRequest.php';
			$slowRequest = new SlowAjaxRequest();
			$slowRequest->year = date('Y');
			$slowRequest->month = date('n');
			$slowRequest->module = $module;
			$slowRequest->method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
			$slowRequest->action = $action;
			if ($slowRequest->find(true)) {
				$slowRequest->setSlowness($elapsedTime);
				$slowRequest->update();
			} else {
				$slowRequest->setSlowness($elapsedTime);
				$slowRequest->insert();
			}
		} else {
			$aspenUsage->slowPages++;
			require_once ROOT_DIR . '/sys/SystemLogging/SlowPage.php';
			$slowPage = new SlowPage();
			$slowPage->year = date('Y');
			$slowPage->month = date('n');
			$slowPage->module = $module;
			$slowPage->action = $action;
			if ($slowPage->find(true)) {
				$slowPage->setSlowness($elapsedTime);
				$slowPage->update();
			} else {
				$slowPage->setSlowness($elapsedTime);
				$slowPage->insert();
			}
		}
	}

	if ($aspenUsage->id){
		$aspenUsage->update();
	}else{
		$aspenUsage->insert();
	}
}catch(Exception $e){
	//Table not created yet, ignore
	global $logger;
	$logger->log("Exception updating aspen usage/slow pages: " . $e, Logger::LOG_DEBUG);
}

function processFollowup(){
	global $configArray;

	switch($_REQUEST['followup']) {
		case 'SaveSearch':
			header("Location: {$configArray['Site']['path']}/".$_REQUEST['followupModule']."/".$_REQUEST['followupAction']."?".$_REQUEST['recordId']);
			die();
			break;
	}
}

/**
 * Process Solr-shard-related parameters and settings.
 *
 * @return void
 */
function processShards()
{
	global $configArray;
	global $interface;

	// If shards are not configured, give up now:
	if (!isset($configArray['IndexShards']) || empty($configArray['IndexShards'])) {
		return;
	}

	// If a shard selection list is found as an incoming parameter, we should save
	// it in the session for future reference:
	$useDefaultShards = false;
	if (array_key_exists('shard', $_REQUEST)) {
		if ($_REQUEST['shard'] == ''){
			$useDefaultShards = true;
		}else{
			$_SESSION['shards'] = $_REQUEST['shard'];
		}

	} else if (!array_key_exists('shards', $_SESSION)) {
		$useDefaultShards = true;
	}
	if ($useDefaultShards){
		// If no selection list was passed in, use the default...

		// If we have a default from the configuration, use that...
		if (isset($configArray['ShardPreferences']['defaultChecked'])
				&& !empty($configArray['ShardPreferences']['defaultChecked'])
				) {
			$checkedShards = $configArray['ShardPreferences']['defaultChecked'];
			$_SESSION['shards'] = is_array($checkedShards) ?
			$checkedShards : array($checkedShards);
		} else {
			// If no default is configured, use all shards...
			$_SESSION['shards'] = array_keys($configArray['IndexShards']);
		}
	}

	// If we are configured to display shard checkboxes, send a list of shards
	// to the interface, with keys being shard names and values being a boolean
	// value indicating whether or not the shard is currently selected.
	if (isset($configArray['ShardPreferences']['showCheckboxes'])
	&& $configArray['ShardPreferences']['showCheckboxes'] == true
	) {
		$shards = array();
		foreach ($configArray['IndexShards'] as $shardName => $shardAddress) {
			$shards[$shardName] = in_array($shardName, $_SESSION['shards']);
		}
		$interface->assign('shards', $shards);
	}
}

// Check for the various stages of functionality
function checkAvailabilityMode() {
	global $configArray;
	$mode = array();

	// If the config file 'available' flag is
	//    set we are forcing downtime.
	if (!$configArray['System']['available']) {
		//Unless the user is accessing from a maintenance IP address

		$isMaintenance = false;
		if (isset($configArray['System']['maintenanceIps'])){
			$activeIp = $_SERVER['REMOTE_ADDR'];
			$maintenanceIp =  $configArray['System']['maintenanceIps'];

			$maintenanceIps = explode(",", $maintenanceIp);
			foreach ($maintenanceIps as $curIp){
				if ($curIp == $activeIp){
					$isMaintenance = true;
					break;
				}
			}

		}

		if ($isMaintenance){
			global $interface;
			$interface->assign('systemMessage', 'You are currently accessing the site in maintenance mode. Remember to turn off maintenance when you are done.');
		}else{
			$mode['online']   = false;
			$mode['level']    = 'unavailable';
			$mode['template'] = 'unavailable.tpl';
			return $mode;
		}
	}

	// No problems? We are online then
	$mode['online'] = true;
	return $mode;
}

function getGitBranch(){
	global $interface;
	global $configArray;

	$gitName = $configArray['System']['gitVersionFile'];
	$branchName = 'Unknown';
	if ($gitName == 'HEAD'){
		$stringFromFile = file('../../.git/HEAD', FILE_USE_INCLUDE_PATH);
		$stringFromFile = $stringFromFile[0]; //get the string from the array
		$explodedString = explode("/", $stringFromFile); //separate out by the "/" in the string
		$branchName = $explodedString[2]; //get the one that is always the branch name
	}else{
		$stringFromFile = file('../../.git/FETCH_HEAD', FILE_USE_INCLUDE_PATH);
		$stringFromFile = $stringFromFile[0]; //get the string from the array
		if (preg_match('/(.*?)\s+branch\s+\'(.*?)\'.*/', $stringFromFile, $matches)){
			$branchName = $matches[2] . ' (' . $matches[1] . ')'; //get the branch name
		}
	}
	$interface->assign('gitBranch', $branchName);
}
// Set up autoloader (needed for YAML)
function aspen_autoloader($class) {
	if (substr($class, 0, 4) == 'CAS_') {
		if (CAS_autoload($class)){
		    return;
        }
	}
	if (strpos($class, '.php') > 0){
		$class = substr($class, 0, strpos($class, '.php'));
	}
	$nameSpaceClass = str_replace('_', '/', $class) . '.php';
	try{
		if (file_exists('sys/' . $class . '.php')){
			$className = ROOT_DIR . '/sys/' . $class . '.php';
            /** @noinspection PhpIncludeInspection */
			require_once $className;
		}elseif (file_exists('Drivers/' . $class . '.php')){
			$className = ROOT_DIR . '/Drivers/' . $class . '.php';
            /** @noinspection PhpIncludeInspection */
			require_once $className;
		}elseif (file_exists('services/MyAccount/lib/' . $class . '.php')){
			$className = ROOT_DIR . '/services/MyAccount/lib/' . $class . '.php';
            /** @noinspection PhpIncludeInspection */
			require_once $className;
		}else{
            /** @noinspection PhpIncludeInspection */
            require_once $nameSpaceClass;
		}
	}catch (Exception $e){
		AspenError::raiseError("Error loading class $class");
	}
}

function loadModuleActionId(){
	//Cleanup method information so module, action, and id are set properly.
	//This ensures that we don't have to change the http.conf file when new types are added.
	//Deal with old path based urls by removing the leading path.
	$requestURI = $_SERVER['REQUEST_URI'];
	/** IndexingProfile[] $indexingProfiles */
	global $indexingProfiles;
	$allRecordModules = "OverDrive|GroupedWork|Record|ExternalEContent|Person|Library|Rbdigital";
	foreach ($indexingProfiles as $profile){
		$allRecordModules .= '|' . $profile->recordUrlComponent;
	}
	if (preg_match("/(MyAccount)\/([^\/?]+)\/([^\/?]+)(\?.+)?/", $requestURI, $matches)){
		$_GET['module'] = $matches[1];
		$_GET['id'] = $matches[3];
		$_GET['action'] = $matches[2];
		$_REQUEST['module'] = $matches[1];
		$_REQUEST['id'] = $matches[3];
		$_REQUEST['action'] = $matches[2];
	}elseif (preg_match("/(MyAccount)\/([^\/?]+)(\?.+)?/", $requestURI, $matches)){
		$_GET['module'] = $matches[1];
		$_GET['action'] = $matches[2];
		$_REQUEST['id'] = '';
		$_REQUEST['module'] = $matches[1];
		$_REQUEST['action'] = $matches[2];
		$_REQUEST['id'] = '';
	}elseif (preg_match("/(MyAccount)\/?/", $requestURI, $matches)){
		$_GET['module'] = $matches[1];
		$_GET['action'] = 'Home';
		$_REQUEST['id'] = '';
		$_REQUEST['module'] = $matches[1];
		$_REQUEST['action'] = 'Home';
		$_REQUEST['id'] = '';
	}elseif (preg_match('/\/(Archive)\/((?:[\\w\\d:]|%3A)+)\/([^\/?]+)/', $requestURI, $matches)){
		$_GET['module'] = $matches[1];
		$_GET['id'] =  urldecode($matches[2]); // Decodes colons % codes back into colons.
		$_GET['action'] = $matches[3];
		$_REQUEST['module'] = $matches[1];
		$_REQUEST['id'] = urldecode($matches[2]);  // Decodes colons % codes back into colons.
		$_REQUEST['action'] = $matches[3];
		//Redirect things /GroupedWork/AJAX to the proper action
	}elseif (preg_match("/($allRecordModules)\/([a-zA-Z]+)(?:\?|\/?$)/", $requestURI, $matches)){
		$_GET['module'] = $matches[1];
		$_GET['action'] = $matches[2];
		$_REQUEST['module'] = $matches[1];
		$_REQUEST['action'] = $matches[2];
		//Redirect things /Record/.b3246786/Home to the proper action
		//Also things like /OverDrive/84876507-043b-b3ce-2930-91af93d2a4f0/Home
	}elseif (preg_match("/($allRecordModules)\/([^\/?]+?)\/([^\/?]+)/", $requestURI, $matches)){
		$_GET['module'] = $matches[1];
		$_GET['id'] = $matches[2];
		$_GET['action'] = $matches[3];
		$_REQUEST['module'] = $matches[1];
		$_REQUEST['id'] = $matches[2];
		$_REQUEST['action'] = $matches[3];
		//Redirect things /Record/.b3246786 to the proper action
	}elseif (preg_match("/($allRecordModules)\/([^\/?]+?)(?:\?|\/?$)/", $requestURI, $matches)){
		$_GET['module'] = $matches[1];
		$_GET['id'] = $matches[2];
		$_GET['action'] = 'Home';
		$_REQUEST['module'] = $matches[1];
		$_REQUEST['id'] = $matches[2];
		$_REQUEST['action'] = 'Home';
	}elseif (preg_match("/([^\/?]+)\/([^\/?]+)/", $requestURI, $matches)){
		$_GET['module'] = $matches[1];
		$_GET['action'] = $matches[2];
		$_REQUEST['module'] = $matches[1];
		$_REQUEST['action'] = $matches[2];
	}
	//Correct some old actions
	if (isset($_GET['action'])) {
		if ($_GET['action'] == 'OverdriveHolds') {
			$_GET['action'] = 'Holds';
			$_REQUEST['action'] = 'Holds';
		} else {
			if ($_GET['action'] == 'OverdriveCheckedOut') {
				$_GET['action'] = 'CheckedOut';
				$_REQUEST['action'] = 'CheckedOut';
			}
		}
	}
	global $activeRecordProfile;
	//Check to see if the module is a profile
	if (isset($_REQUEST['module'])){
		/** @var IndexingProfile[] */
		/** @var IndexingProfile $profile */
		global $indexingProfiles;
		foreach ($indexingProfiles as $profile) {
			if ($profile->recordUrlComponent == $_REQUEST['module']) {
				$newId = $profile->name . ':' . $_REQUEST['id'];
				$_GET['id'] = $newId;
				$_REQUEST['id'] = $newId;
				if (!file_exists(ROOT_DIR . '/services/' . $_REQUEST['module'])){
					$_GET['module'] = 'Record';
					$_REQUEST['module'] = 'Record';
				}
				$activeRecordProfile = $profile;
				break;
			}
		}
	}
}

function initializeSession(){
	global $configArray;
	global $timer;
	// Initiate Session State
	$session_type = $configArray['Session']['type'];
	$session_lifetime = $configArray['Session']['lifetime'];
	$session_rememberMeLifetime = $configArray['Session']['rememberMeLifetime'];
	//register_shutdown_function('session_write_close');
	$sessionClass = ROOT_DIR . '/sys/' . $session_type . '.php';
    /** @noinspection PhpIncludeInspection */
	require_once $sessionClass;
	if (class_exists($session_type)) {
		/** @var SessionInterface $session */
		$session = new $session_type();
		$session->init($session_lifetime, $session_rememberMeLifetime);
	}
	$timer->logTime('Session initialization ' . $session_type);
}