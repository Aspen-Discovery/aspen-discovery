<?php

require_once __DIR__ . '/../logger/DockerLogger.php';
DockerLogger::init('BACKEND');

// =============================================================================
// Argument Validation
// =============================================================================

if (count($argv) < 2) {
	DockerLogger::error("Configuration directory required as argument");
} elseif (count($argv) > 2) {
	DockerLogger::error("Too many arguments provided");
}

$siteDir = $argv[1];

if (!file_exists($siteDir)) {
	DockerLogger::error("Directory does not exist: {$siteDir}");
}

if (!is_dir($siteDir)) {
	DockerLogger::error("Path is not a directory: {$siteDir}");
}

DockerLogger::info("Creating configuration files for site directory: {$siteDir}");

// =============================================================================
// Environment Variables Collection
// =============================================================================

$variables = [
	//ASPEN
	'sitename' => getenv('SITE_NAME'),
	'servername' => preg_replace('~https?://~', '', getenv('URL')),
	'supportingCompany' => getenv('SUPPORTING_COMPANY') ?? 'ByWater Solutions',
	'library' => getenv('LIBRARY'),
	'title' => getenv('TITLE'),
	'url' => getenv('URL'),
	'configDir' => $siteDir,
	'solrHost' => getenv('SOLR_HOST') ?? 'localhost',
	'solrPort' => getenv('SOLR_PORT') ?? 8983,
	'phpFpmHost' => getenv('PHP_FPM_HOST') ?? 'localhost',
	'phpFpmPort' => getenv('PHP_FPM_PORT') ?? '9000',
	'timezone' => getenv('TZ') ?? 'US/Central',
	'aspenAdminPassword' => getenv('ASPEN_ADMIN_PASSWORD'),
	'databaseHost' => getenv('DATABASE_HOST') ?? 'localhost',
	'databasePort' => getenv('DATABASE_PORT') ?? 3306,
	'databaseName' => getenv('DATABASE_NAME'),
	'databaseUser' => getenv('DATABASE_USER'),
	'databasePassword' => getenv('DATABASE_PASSWORD'),
	'enableKoha' => strtolower(getenv('ENABLE_KOHA')),
];

// =============================================================================
// Variable Validation
// =============================================================================

$mandatory = [
	'sitename', 'servername', 'solrHost', 'solrPort', 'phpFpmHost', 'phpFpmPort',
	'configDir', 'timezone', 'aspenAdminPassword', 'databaseHost', 'databasePort',
	'databaseName', 'databaseUser', 'databasePassword'
];

$missingVars = [];
foreach ($variables as $key => $value) {
	if (in_array($key, $mandatory) && empty($value)) {
		$missingVars[] = $key;
	}
}

if (!empty($missingVars)) {
	DockerLogger::error("Missing mandatory variables: " . implode(', ', $missingVars));
}

DockerLogger::info("Environment variables validated successfully");

// =============================================================================
// Directory Validation
// =============================================================================

$requiredDirs = [
	'templateDir' => "/usr/local/aspen-discovery/sites/template.linux",
	'defaultDir' => "/usr/local/aspen-discovery/sites/default",
	'dockerDir' => "/usr/local/aspen-discovery/docker"
];

foreach ($requiredDirs as $name => $dir) {
	if (!file_exists($dir)) {
		DockerLogger::error("Required directory does not exist: {$dir}");
	}
	if (!is_dir($dir)) {
		DockerLogger::error("Path is not a directory: {$dir}");
	}
}

DockerLogger::info("Required directories validated successfully");

// =============================================================================
// Configuration File Creation
// =============================================================================

set_error_handler("customErrorHandler");

try {
	DockerLogger::info("Copying configuration templates");
	
	// Copy from template and replace variables
	copy($requiredDirs['templateDir'] . '/httpd-{sitename}.conf', "$siteDir/httpd-{$variables['sitename']}.conf");
	recursive_copy($requiredDirs['templateDir'] . '/conf', $siteDir . '/conf');
	rename($siteDir . '/conf/config.pwd.ini.template', $siteDir . "/conf/config.pwd.ini");

	DockerLogger::info("Replacing template variables");
	
	// Replace variables in configuration files
	$configFiles = [
		"$siteDir/httpd-{$variables['sitename']}.conf",
		"$siteDir/conf/config.ini",
		"$siteDir/conf/config.cron.ini", 
		"$siteDir/conf/config.pwd.ini",
		"$siteDir/conf/crontab_settings.txt"
	];
	
	foreach ($configFiles as $file) {
		replaceVariables($file, $variables);
	}

	DockerLogger::info("Copying additional configuration files");
	
	// Copy from default site directory
	copy($requiredDirs['defaultDir'] . "/conf/badBotsLocal.conf", $siteDir . "/conf/badBotsLocal.conf");
	copy($requiredDirs['defaultDir'] . "/conf/badBotsDefault.conf", $siteDir . "/conf/badBotsDefault.conf");

	// Copy from docker directory
	copy($requiredDirs['dockerDir'] . "/files/php_fpm/php-fpm.conf", $siteDir . "/conf/php-fpm.conf");
	copy($requiredDirs['dockerDir'] . "/files/cron/crontab", "$siteDir/conf/crontab");

	// Replace variables in docker files
	replaceVariables($siteDir . "/conf/php-fpm.conf", $variables);
	replaceVariables($siteDir . "/conf/crontab", $variables);

	// Create temp directory
	if (!file_exists('/tmp')) {
		mkdir('/tmp');
	}

} catch (ErrorException $e) {
	DockerLogger::error("Configuration creation failed: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
}

DockerLogger::info("Configuration files created successfully");

// =============================================================================
// Helper Functions
// =============================================================================

function customErrorHandler(int $errno, string $errstr, string $errfile, int $errline): void {
	if (!(error_reporting() & $errno)) {
		return;
	}
	if ($errno === E_DEPRECATED || $errno === E_USER_DEPRECATED) {
		return;
	}
	throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

function replaceVariables($filename, $variables): void {
	$contents = file($filename);
	$fHnd = fopen($filename, 'w');
	foreach ($contents as $line) {
		foreach ($variables as $name => $value) {
			$line = str_replace('{' . $name . '}', $value, $line);
		}
		fwrite($fHnd, $line);
	}
	fclose($fHnd);
}

function recursive_copy($src, $dst): void {
	$dir = opendir($src);
	@mkdir($dst);
	while (($file = readdir($dir))) {
		if (($file != '.') && ($file != '..')) {
			if (is_dir($src . '/' . $file)) {
				recursive_copy($src . '/' . $file, $dst . '/' . $file);
			} else {
				copy($src . '/' . $file, $dst . '/' . $file);
			}
		}
	}
	closedir($dir);
}
?>
