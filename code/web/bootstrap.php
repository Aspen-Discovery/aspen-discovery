<?php
define ('ROOT_DIR', __DIR__);

require_once ROOT_DIR . '/sys/SystemLogging/AspenUsage.php';
global $aspenUsage;
$aspenUsage = new AspenUsage();
$aspenUsage->year = date('Y');
$aspenUsage->month = date('n');

global $errorHandlingEnabled;
$errorHandlingEnabled = true;

$startTime = microtime(true);
require_once ROOT_DIR . '/sys/Logger.php';

require_once ROOT_DIR . '/sys/ConfigArray.php';
global $configArray;
$configArray = readConfig();
require_once ROOT_DIR . '/sys/Timer.php';
global $timer;
$timer = new Timer($startTime);
require_once ROOT_DIR . '/sys/MemoryWatcher.php';
global $memoryWatcher;
$memoryWatcher = new MemoryWatcher();

global $logger;
$logger = new Logger();
$timer->logTime("Read Config");

if ($configArray['System']['debug']) {
	ini_set('display_errors', true);
	error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

//Use output buffering to allow session cookies to have different values
// this can't be determined before session_start is called
ob_start();

initMemcache();
initDatabase();

try{
	$aspenUsage->find(true);
}catch (Exception $e){
	//Table has not been created yet, ignore it
}

$timer->logTime("Initialized Database");
requireSystemLibraries();
initLocale();

$timer->logTime("Basic Initialization");
loadLibraryAndLocation();
$timer->logTime("Finished load library and location");
loadSearchInformation();

$timer->logTime('Bootstrap done');

function initMemcache(){
	//Connect to memcache
	/** @var Memcache $memCache */
	global $memCache;
	global $timer;
    /*global $configArray;
    // Set defaults if nothing set in config file.
    $host = isset($configArray['Caching']['memcache_host']) ? $configArray['Caching']['memcache_host'] : 'localhost';
    $port = isset($configArray['Caching']['memcache_port']) ? $configArray['Caching']['memcache_port'] : 11211;
    $timeout = isset($configArray['Caching']['memcache_connection_timeout']) ? $configArray['Caching']['memcache_connection_timeout'] : 1;

    // Connect to Memcache:
    $memCache = new Memcache();
    if (!@$memCache->pconnect($host, $port, $timeout)) {
        //Try again with a non-persistent connection
        if (!$memCache->connect($host, $port, $timeout)) {
            AspenError::raiseError(new AspenError("Could not connect to Memcache (host = {$host}, port = {$port})."));
        }
    }*/
    require_once ROOT_DIR . '/sys/MemoryCache/Memcache.php';
	$memCache = new Memcache();
	$timer->logTime("Initialize Memcache");
}

function initDatabase(){
	global $configArray;
	/** @var PDO */
	global $aspen_db;

	try{
        $aspen_db = new PDO($configArray['Database']['database_dsn'],$configArray['Database']['database_user'],$configArray['Database']['database_password']);
        $aspen_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
	    global $serverName;
	    echo("Server name: $serverName<br>\r\n");
	    echo("Could not connect to database {$configArray['Database']['database_dsn']}, define database connection information in config.pwd.ini<br>\r\n$e\r\n");
	    die();
    }
}

function requireSystemLibraries(){
	// Require System Libraries
	require_once ROOT_DIR . '/sys/Interface.php';
	require_once ROOT_DIR . '/sys/UserAccount.php';
	require_once ROOT_DIR . '/sys/Account/User.php';
	require_once ROOT_DIR . '/sys/Account/AccountProfile.php';
	require_once ROOT_DIR . '/sys/Translator.php';
	require_once ROOT_DIR . '/sys/SearchObject/SearchObjectFactory.php';
	require_once ROOT_DIR . '/Drivers/marmot_inc/Library.php';
	require_once ROOT_DIR . '/Drivers/marmot_inc/Location.php';
	require_once ROOT_DIR . '/Drivers/AbstractIlsDriver.php';
	require_once ROOT_DIR . '/RecordDrivers/RecordDriverFactory.php';

}

function initLocale(){
	global $configArray;
	// Try to set the locale to UTF-8, but fail back to the exact string from the config
	// file if this doesn't work -- different systems may vary in their behavior here.
	setlocale(LC_MONETARY, array($configArray['Site']['locale'] . ".UTF-8",
	$configArray['Site']['locale']));
	date_default_timezone_set($configArray['Site']['timezone']);
}

function loadLibraryAndLocation(){
	global $timer;
	global $librarySingleton;
	global $locationSingleton;
	global $configArray;

	//Create global singleton instances for Library and Location
	$librarySingleton = new Library();
	$timer->logTime('Created library');
	$locationSingleton = new Location();
	$timer->logTime('Created Location');

	global $active_ip;
	$active_ip = $locationSingleton->getActiveIp();
	if (!isset($_COOKIE['test_ip']) || $active_ip != $_COOKIE['test_ip']){
		if ($active_ip == ''){
			setcookie('test_ip', $active_ip, time() - 1000, '/');
		}else{
			setcookie('test_ip', $active_ip, 0, '/');
		}
	}
	$timer->logTime('Got active ip address');

	$branch = $locationSingleton->getBranchLocationCode();
	if (!isset($_COOKIE['branch']) || $branch != $_COOKIE['branch']){
		if ($branch == ''){
			setcookie('branch', $branch, time() - 1000, '/');
		}else{
			setcookie('branch', $branch, 0, '/');
		}
	}
	$timer->logTime('Got branch');

	$subLocation = $locationSingleton->getSublocationCode();
	if (!isset($_COOKIE['sublocation']) || $subLocation != $_COOKIE['sublocation']) {
		if (empty($subLocation)) {
			setcookie('sublocation', $subLocation, time() - 1000, '/');
		} else {
			setcookie('sublocation', $subLocation, 0, '/');
		}
	}
	$timer->logTime('Got subLocation');

	//Update configuration information for scoping now that the database is setup.
	$configArray = updateConfigForScoping($configArray);
	$timer->logTime('Updated config for scoping');
}

function loadSearchInformation(){
	//Determine the Search Source, need to do this always.
	global $searchSource;
	global $library;
	/** @var Memcache $memCache */
	global $memCache;
	global $instanceName;
	global $configArray;

	$module = (isset($_GET['module'])) ? $_GET['module'] : null;
	$module = preg_replace('/[^\w]/', '', $module);

	$searchSource = 'global';
	if (isset($_GET['searchSource'])){
		if (is_array($_GET['searchSource'])){
			$_GET['searchSource'] = reset($_GET['searchSource']);
		}
		$searchSource = $_GET['searchSource'];
		$_REQUEST['searchSource'] = $searchSource; //Update request since other check for it here
		$_SESSION['searchSource'] = $searchSource; //Update the session so we can remember what the user was doing last.
	}else{
		if ( isset($_SESSION['searchSource'])){ //Didn't get a source, use what the user was doing last
			$searchSource = $_SESSION['searchSource'];
			$_REQUEST['searchSource'] = $searchSource;
		}else{
			//Use a default search source
			if ($module == 'Person'){
				$searchSource = 'genealogy';
			}elseif ($module == 'Archive'){
				$searchSource = 'islandora';
            }elseif ($module == 'OpenArchives'){
                $searchSource = 'open_archives';
            }elseif ($module == 'List'){
                $searchSource = 'lists';
            }elseif ($module == 'EBSCO'){
				$searchSource = 'ebsco';
			}else{
				require_once(ROOT_DIR . '/Drivers/marmot_inc/SearchSources.php');
				$searchSources = new SearchSources();
				global $locationSingleton;
				$location = $locationSingleton->getActiveLocation();
				list($enableCombinedResults, $showCombinedResultsFirst) = $searchSources::getCombinedSearchSetupParameters($location, $library);
				if ($enableCombinedResults && $showCombinedResultsFirst){
					$searchSource = 'combinedResults';
				}else{
					$searchSource = 'local';
				}
			}
			$_REQUEST['searchSource'] = $searchSource;
		}
	}

	/** @var Library $searchLibrary */
	$searchLibrary = Library::getSearchLibrary($searchSource);
	$searchLocation = Location::getSearchLocation($searchSource);

	if ($searchSource == 'marmot' || $searchSource == 'global'){
		$searchSource = $searchLibrary->subdomain;
	}

	//Based on the search source, determine the search scope and set a global variable
	global $solrScope;
	global $scopeType;
	global $isGlobalScope;
	$solrScope = false;
	$scopeType = '';
	$isGlobalScope = false;

	if ($searchLibrary){
		$solrScope = $searchLibrary->subdomain;
		$scopeType = 'Library';
		if (!$searchLibrary->restrictOwningBranchesAndSystems){
			$isGlobalScope = true;
		}
	}
	if ($searchLocation){
		if ($searchLibrary && strtolower($searchLocation->code) == $solrScope){
			$solrScope .= 'loc';
		}else{
			$solrScope = strtolower($searchLocation->code);
		}
		if (!empty($searchLocation->subLocation)){
			$solrScope = strtolower($searchLocation->subLocation);
		}
		$scopeType = 'Location';
	}

	$solrScope = trim($solrScope);
	$solrScope = preg_replace('/[^a-zA-Z0-9_]/', '', $solrScope);
	if (strlen($solrScope) == 0){
		$solrScope = false;
		$scopeType = 'Unscoped';
	}

	$searchLibrary = Library::getSearchLibrary($searchSource);
	$searchLocation = Location::getSearchLocation($searchSource);

	global $millenniumScope;
	if ($library){
		if ($searchLibrary){
			$millenniumScope = $searchLibrary->scope;
		}elseif (isset($searchLocation)){
			Millennium::$scopingLocationCode = $searchLocation->code;
		}else{
			$millenniumScope = isset($configArray['OPAC']['defaultScope']) ? $configArray['OPAC']['defaultScope'] : '93';
		}
	}else{
		$millenniumScope = isset($configArray['OPAC']['defaultScope']) ? $configArray['OPAC']['defaultScope'] : '93';
	}

	//Load indexing profiles
	require_once ROOT_DIR . '/sys/Indexing/IndexingProfile.php';
	/** @var $indexingProfiles IndexingProfile[] */
	global $indexingProfiles;
	$indexingProfiles = array();
	$indexingProfile = new IndexingProfile();
	$indexingProfile->orderBy('name');
	$indexingProfile->find();
	while ($indexingProfile->fetch()){
		$indexingProfiles[$indexingProfile->name] = clone($indexingProfile);
	}
	if (!$memCache->set("{$instanceName}_indexing_profiles", $indexingProfiles, 0, $configArray['Caching']['indexing_profiles'])) {
		global $logger;
		$logger->log("Failed to update memcache variable {$instanceName}_indexing_profiles", Logger::LOG_ERROR);
	};
}

function disableErrorHandler(){
	global $errorHandlingEnabled;
	$errorHandlingEnabled = false;
}
function enableErrorHandler(){
	global $errorHandlingEnabled;
	$errorHandlingEnabled = true;
}

function array_remove_by_value($array, $value){
	return array_values(array_diff($array, array($value)));
}