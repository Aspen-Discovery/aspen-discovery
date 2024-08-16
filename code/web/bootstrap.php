<?php
define('ROOT_DIR', __DIR__);

/**
 * Load and register Smarty Autoloader
 */
if (!class_exists('Smarty_Autoloader')) {
	include ROOT_DIR . '/sys/Smarty/Autoloader.php';
}
Smarty_Autoloader::register(true);

require_once ROOT_DIR . '/sys/DB/DataObject.php';
require_once ROOT_DIR . '/sys/Interface.php';
require_once ROOT_DIR . '/sys/AspenError.php';
require_once ROOT_DIR . '/sys/Module.php';
require_once ROOT_DIR . '/sys/SystemLogging/AspenUsage.php';
require_once ROOT_DIR . '/sys/SystemVariables.php';
require_once ROOT_DIR . '/sys/SystemLogging/UsageByIPAddress.php';
require_once ROOT_DIR . '/sys/IP/IPAddress.php';
require_once ROOT_DIR . '/sys/SystemLogging/UserAgent.php';
require_once ROOT_DIR . '/sys/SystemLogging/UsageByUserAgent.php';
require_once ROOT_DIR . '/sys/Utils/EncryptionUtils.php';
require_once ROOT_DIR . '/sys/SystemLogging/ExternalRequestLogEntry.php';
require_once ROOT_DIR . '/sys/LibraryLocation/Library.php';
require_once ROOT_DIR . '/sys/LibraryLocation/Location.php';
require_once ROOT_DIR . '/sys/LibraryLocation/HostInformation.php';
global $aspenUsage;
global $serverName;
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
} else {
	$aspenUsage->instance = 'aspen_internal';
}

//This has to be done after reading configuration so we can get the servername
global $usageByIPAddress;
$usageByIPAddress = new UsageByIPAddress();
$usageByIPAddress->year = date('Y');
$usageByIPAddress->month = date('n');
$usageByIPAddress->ipAddress = IPAddress::getClientIP();
$usageByIPAddress->instance = $aspenUsage->getInstance();

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

global $userAgent;
$userAgentString = 'Unknown';
if (isset($_SERVER['HTTP_USER_AGENT'])) {
	$userAgentString = $_SERVER['HTTP_USER_AGENT'];
}
try {
	$userAgent = new UserAgent();
	if (strlen($userAgentString) > 512) {
		$userAgentString = substr($userAgentString, 0, 512);
	}
	$userAgent->userAgent = $userAgentString;
	if ($userAgent->find(true)) {
		$userAgentId = $userAgent->id;
	}else{
		if (!$userAgent->insert()) {
			$logger->log("Could not insert user agent $userAgentString", Logger::LOG_ERROR);
			$logger->log($userAgent->getLastError(), Logger::LOG_ERROR);
		}
		$userAgentId = $userAgent->id;
	}
	require_once ROOT_DIR . '/sys/SystemLogging/UsageByUserAgent.php';
	$usageByUserAgent = new UsageByUserAgent();
	$usageByUserAgent->userAgentId = $userAgentId;
	$usageByUserAgent->year = date('Y');
	$usageByUserAgent->month = date('n');
	global $aspenUsage;
	$usageByUserAgent->instance = $aspenUsage->getInstance();

	if ($userAgent->blockAccess) {
		$usageByUserAgent->numBlockedRequests++;
		if ($usageByUserAgent->update() == 0){
			$logger->log("Could not update user agent usage", Logger::LOG_ERROR);
			$logger->log($usageByUserAgent->getLastError(), Logger::LOG_ERROR);
		}
		http_response_code(403);
		echo("<h1>Forbidden</h1><p><strong>We are unable to handle your request.</strong></p>");
		die();
	}else{
		$usageByUserAgent->numRequests++;
		if ($usageByUserAgent->update() == 0){
			$logger->log("Could not update user agent usage", Logger::LOG_ERROR);
			$logger->log($usageByUserAgent->getLastError(), Logger::LOG_ERROR);
		}
	}
}catch (Exception $e) {
	//This happens before tables are created, ignore it
}

if ($aspenUsage->getInstance() != 'aspen_internal') {
	$isValidServerName = true;
	//Validate that we are getting a valid, non-spoofed name.
	if (!empty($_SERVER['SERVER_NAME'])) {
		if (strip_tags($_SERVER['SERVER_NAME']) !== $_SERVER['SERVER_NAME']) {
			$isValidServerName = false;
		} elseif (html_entity_decode($_SERVER['SERVER_NAME']) !== $_SERVER['SERVER_NAME']) {
			$isValidServerName = false;
		}
	}

	if ($isValidServerName) {
		$isValidServerName = false;
		$validServerNames = getValidServerNames();

		foreach ($validServerNames as $validServerName) {
			if (strcasecmp($aspenUsage->getInstance(), $validServerName) === 0) {
				$isValidServerName = true;
				break;
			}
		}
	}
	if (!$isValidServerName) {
		http_response_code(404);
		if (IPAddress::showDebuggingInformation()) {
			echo("<html><head><title>Invalid Request</title></head><body>Invalid Host $aspenUsage->getInstance(), valid instances are " . implode(', ', $validServerNames) . "</body></html>");
		} else {
			echo("<html><head><title>Invalid Request</title></head><body>Invalid Host</body></html>");
		}
		die();
	}
}

//Check to see if timings should be enabled
if (IPAddress::logTimingInformation()) {
	$timer->enableTimings(true);
}
$timer->logTime("Initial configuration");

try {
	$aspenUsage->find(true);
} catch (Exception $e) {
	//Table has not been created yet, ignore it
}

try {
	$usageByIPAddress->find(true);
} catch (Exception $e) {
	//Table has not been created yet, ignore it
}
$usageByIPAddress->lastRequest = time();
$usageByIPAddress->numRequests++;

$timer->logTime("Initialized Database");
requireSystemLibraries();
initLocale();

//Check to see if we should be blocking based on the IP address
if (IPAddress::isClientIpBlocked()) {
	$aspenUsage->blockedRequests++;
	$aspenUsage->update();
	try {
		$usageByIPAddress->numBlockedRequests++;
		if (SystemVariables::getSystemVariables()->trackIpAddresses) {
			$usageByIPAddress->update();
		}
	} catch (Exception $e) {
		//Ignore this, the class has not been created yet
	}

	http_response_code(403);
	echo("<h1>Forbidden</h1><p><strong>We are unable to handle your request.</strong></p>");
	die();
}
if (IPAddress::showDebuggingInformation()) {
	ini_set('display_errors', true);
	error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}else{
	ini_set('display_errors', false);
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
} catch (Exception $e) {
	//Modules are not installed yet
}

$timer->logTime("Basic Initialization");
loadLibraryAndLocation();

$timer->logTime('Bootstrap done');

function initMemcache() {
	//Connect to memcache
	global $memCache;

	require_once ROOT_DIR . '/sys/MemoryCache/Memcache.php';
	$memCache = new Memcache();
}

function initDatabase() {
	global $configArray;
	/** @var PDO */ global $aspen_db;

	try {
		$aspen_db = new PDO($configArray['Database']['database_dsn'], $configArray['Database']['database_user'], $configArray['Database']['database_password']);
		$aspen_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$aspen_db->exec("SET NAMES utf8mb4");
	} catch (PDOException $e) {
		global $serverName;
		echo("Server name: $serverName<br>\r\n");
		if ($configArray['System']['debug']) {
			echo("Could not connect to database {$configArray['Database']['database_dsn']}, define database connection information in config.pwd.ini<br>\r\n");
		} else {
			echo("Could not connect to database\r\n");
		}
		die();
	}
}

function requireSystemLibraries() {
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

function initLocale() {
	global $configArray;
	// Try to set the locale to UTF-8, but fail back to the exact string from the config
	// file if this doesn't work -- different systems may vary in their behavior here.
	setlocale(LC_MONETARY, [
		$configArray['Site']['locale'] . ".UTF-8",
		$configArray['Site']['locale'],
	]);
	date_default_timezone_set($configArray['Site']['timezone']);
}

function loadLibraryAndLocation() {
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
	$timer->logTime('Got active ip address');

	$branch = $locationSingleton->getBranchLocationCode();
	if (!isset($_COOKIE['branch']) || $branch != $_COOKIE['branch']) {
		if ($branch == '') {
			setcookie('branch', $branch, time() - 1000, '/');
		} else {
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

function disableErrorHandler() {
	global $errorHandlingEnabled;
	$errorHandlingEnabled--;
}

function enableErrorHandler() {
	global $errorHandlingEnabled;
	$errorHandlingEnabled++;
}

function array_remove_by_value($array, $value) {
	return array_values(array_diff($array, [$value]));
}

function getValidServerNames(): array {
	//Don't cache for now since server names get long
	/* Memcache $memCache */ //global $memCache;
	//$validServerNames = $memCache->get('validServerNames');
	$validServerNames = null;
	if (empty($validServerNames) || isset($_REQUEST['reload'])) {
		//Get a list of valid server names
		global $instanceName;
		global $configArray;
		$mainServer = $instanceName;
		$mainServerBase = null;
		$isTestServer = !$configArray['Site']['isProduction'];
		if (strpos($mainServer, '.') != strrpos($mainServer, '.')) {
			$mainServerBase = substr($mainServer, strpos($mainServer, '.') + 1);
		}
		$validServerNames = [$instanceName];
		$libraryInfo = new Library();
		$libraryUrls = $libraryInfo->fetchAll('subdomain', 'baseUrl');
		foreach ($libraryUrls as $subdomain => $libraryUrl) {
			if (!empty($libraryUrl)) {
				if (preg_match('~^https?://(.*?)/?$~', $libraryUrl, $matches)) {
					$validServerNames[] = $matches[1];
				}
			}
			$validServerNames[] = "$subdomain.$mainServer";
			$validServerNames[] = "$subdomain.aspendiscovery.org";
			if ($mainServerBase != null) {
				$validServerNames[] = "$subdomain.$mainServerBase";
			}
			if ($isTestServer) {
				$validServerNames[] = "{$subdomain}t.$mainServer";
				if ($mainServerBase != null) {
					$validServerNames[] = "{$subdomain}t.$mainServerBase";
				}
			}
		}
		$locationInfo = new Location();
		$locationUrls = $locationInfo->fetchAll('code');
		foreach ($locationUrls as $code => $locationUrl) {
			$validServerNames[] = "$code.$mainServer";
			$validServerNames[] = "$code.aspendiscovery.org";
			if ($mainServerBase != null) {
				$validServerNames[] = "$code.$mainServerBase";
			}
			if ($isTestServer) {
				$validServerNames[] = "{$code}t.$mainServer";
				$validServerNames[] = "{$code}x.$mainServer";
				if ($mainServerBase != null) {
					$validServerNames[] = "{$code}t.$mainServerBase";
				}
			}
		}
		$locationInfo = new Location();
		$locationSubdomains = $locationInfo->fetchAll('subdomain');
		foreach ($locationSubdomains as $subdomain => $subdomain2) {
			if (!empty($subdomain)) {
				$validServerNames[] = "$subdomain.$mainServer";
				$validServerNames[] = "$subdomain.aspendiscovery.org";
				if ($mainServerBase != null) {
					$validServerNames[] = "$subdomain.$mainServerBase";
				}
				if ($isTestServer) {
					$validServerNames[] = "{$subdomain}t.$mainServer";
					if ($mainServerBase != null) {
						$validServerNames[] = "{$subdomain}t.$mainServerBase";
					}
				}
			}
		}
		$hostInfo = new HostInformation();
		$hosts = $hostInfo->fetchAll('host');
		foreach ($hosts as $host) {
			if (!empty($host)) {
				$validServerNames[] = "$host";
			}
		}
		//$memCache->set('validServerNames', $validServerNames, 5 * 60 * 60);
	}
	return $validServerNames;
}

function getGitBranch() {
	global $interface;
	global $configArray;

	$gitName = $configArray['System']['gitVersionFile'];
	$branchName = 'Unknown';
	$branchNameWithCommit = 'Unknown';
	if ($gitName == 'HEAD') {
		$stringFromFile = file(ROOT_DIR . '/../../.git/HEAD');
		$stringFromFile = $stringFromFile[0]; //get the string from the array
		$explodedString = explode("/", $stringFromFile); //separate out by the "/" in the string
		$branchName = trim($explodedString[2]); //get the one that is always the branch name
		$branchNameWithCommit = $branchName;
	} else {
		if (file_exists(ROOT_DIR . '/../../.git/FETCH_HEAD')) {
			$stringFromFile = file(ROOT_DIR . '/../../.git/FETCH_HEAD');
			if (!empty($stringFromFile)) {
				$stringFromFile = $stringFromFile[0]; //get the string from the array
				if (preg_match('/(.*?)\s+branch\s+\'(.*?)\'.*/', $stringFromFile, $matches)) {
					$branchName = $matches[2]; //get the branch name
					$branchNameWithCommit = $matches[2] . ' (' . substr($matches[1], 0, 7) . ')'; //get the branch name
				}
			}
		} else {
			$branchName = 'Unknown';
			$branchNameWithCommit = 'Unknown';
		}
	}

	if (!empty($interface)) {
		$interface->assign('gitBranch', $branchName);
		$interface->assign('gitBranchWithCommit', $branchNameWithCommit);
	}

	return $branchName;
}