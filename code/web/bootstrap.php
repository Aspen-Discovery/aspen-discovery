<?php
define ('ROOT_DIR', __DIR__);

require_once ROOT_DIR . '/sys/DB/DataObject.php';
require_once ROOT_DIR . '/sys/Interface.php';
require_once ROOT_DIR . '/sys/AspenError.php';
require_once ROOT_DIR . '/sys/Module.php';
require_once ROOT_DIR . '/sys/SystemLogging/AspenUsage.php';
require_once ROOT_DIR . '/sys/SystemLogging/UsageByIPAddress.php';
require_once ROOT_DIR . '/sys/IP/IPAddress.php';
require_once ROOT_DIR . '/sys/Utils/EncryptionUtils.php';
global $aspenUsage;
$aspenUsage = new AspenUsage();
$aspenUsage->year = date('Y');
$aspenUsage->month = date('n');

global $errorHandlingEnabled;
$errorHandlingEnabled = 0;

$startTime = microtime(true);
require_once ROOT_DIR . '/sys/Logger.php';

require_once ROOT_DIR . '/sys/ConfigArray.php';
global $configArray;
$configArray = readConfig();

if (isset($_SERVER['SERVER_NAME'])) {
	$aspenUsage->instance = $_SERVER['SERVER_NAME'];
}else{
	$aspenUsage->instance = 'aspen_internal';
}

//This has to be done after reading configuration so we can get the servername
global $usageByIPAddress;
global $fullServerName;
$usageByIPAddress = new UsageByIPAddress();
$usageByIPAddress->year = date('Y');
$usageByIPAddress->month = date('n');
$usageByIPAddress->ipAddress = IPAddress::getClientIP();
if (isset($_SERVER['SERVER_NAME'])) {
	$usageByIPAddress->instance = $_SERVER['SERVER_NAME'];
}else{
	$usageByIPAddress->instance = 'aspen_internal';
}

require_once ROOT_DIR . '/sys/Timer.php';
global $timer;
$timer = new Timer($startTime);
require_once ROOT_DIR . '/sys/MemoryWatcher.php';
global $memoryWatcher;
$memoryWatcher = new MemoryWatcher();

global $logger;
$logger = new Logger();

//Use output buffering to allow session cookies to have different values
// this can't be determined before session_start is called
ob_start();

initMemcache();
initDatabase();

//Check to see if timings should be enabled
if (IPAddress::logTimingInformation()) {
	$timer->enableTimings(true);
}
$timer->logTime("Initial configuration");

try{
	$aspenUsage->find(true);
}catch (Exception $e){
	//Table has not been created yet, ignore it
}

try{
	$usageByIPAddress->find(true);
}catch (Exception $e){
	//Table has not been created yet, ignore it
}
$usageByIPAddress->lastRequest = time();
$usageByIPAddress->numRequests++;

$timer->logTime("Initialized Database");
requireSystemLibraries();
initLocale();

//Check to see if we should be blocking based on the IP address
if (IPAddress::isClientIpBlocked()){
	$aspenUsage->blockedRequests++;
	$aspenUsage->update();
	try {
		$usageByIPAddress->numBlockedRequests++;
		$usageByIPAddress->update();
	}catch (Exception $e){
		//Ignore this, the class has not been created yet
	}

	http_response_code(403);
	echo("<h1>Forbidden</h1><p><strong>We are unable to handle your request.</strong></p>");
	die();
}
if (IPAddress::showDebuggingInformation()) {
	ini_set('display_errors', true);
	error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

global $enabledModules;
$enabledModules = [];
try {
	$aspenModule = new Module();
	$aspenModule->enabled = true;
	$aspenModule->find();
	while ($aspenModule->fetch()) {
		$enabledModules[$aspenModule->name] = clone $aspenModule;
	}
}catch (Exception $e){
	//Modules are not installed yet
}

$timer->logTime("Basic Initialization");
loadLibraryAndLocation();

$timer->logTime('Bootstrap done');

function initMemcache(){
	//Connect to memcache
	global $memCache;

    require_once ROOT_DIR . '/sys/MemoryCache/Memcache.php';
	$memCache = new Memcache();
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
	    if ($configArray['System']['debug']) {
		    echo("Could not connect to database {$configArray['Database']['database_dsn']}, define database connection information in config.pwd.ini<br>\r\n$e\r\n");
	    }else{
		    echo("Could not connect to database");
	    }
	    die();
    }
}

function requireSystemLibraries(){
	// Require System Libraries
	require_once ROOT_DIR . '/sys/UserAccount.php';
	require_once ROOT_DIR . '/sys/Account/AccountProfile.php';
	require_once ROOT_DIR . '/sys/Account/User.php';
	require_once ROOT_DIR . '/sys/LibraryLocation/Library.php';
	require_once ROOT_DIR . '/sys/LibraryLocation/Location.php';
	require_once ROOT_DIR . '/sys/Translation/Translator.php';
	require_once ROOT_DIR . '/Drivers/AbstractIlsDriver.php';
	require_once ROOT_DIR . '/sys/IP/IPAddress.php';
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
	$active_ip = IPAddress::getActiveIp();
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

function disableErrorHandler(){
	global $errorHandlingEnabled;
	$errorHandlingEnabled--;
}
function enableErrorHandler(){
	global $errorHandlingEnabled;
	$errorHandlingEnabled++;
}

function array_remove_by_value($array, $value){
	return array_values(array_diff($array, array($value)));
}