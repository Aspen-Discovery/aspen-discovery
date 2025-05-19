<?php


if (count($argv) < 2) {
	echo "To create new configuration files, a directory (where the files will be stored) will be necessary as argument.\n";
	die();
} elseif (count($argv) > 2) {
	echo "Too many arguments have been passed. The script just needs a directory to start\n";
	die(1);
}

$siteDir = $argv[1];

if (!file_exists($siteDir)) {
	echo "The directory '$siteDir' does not exist.\n";
	die(1);
}

if (!is_dir($siteDir)) {
	echo "'$siteDir' is not a directory.\n";
	die(1);
}

echo "--> Creating new configuration files...\n";

$variables = [
	//ASPEN
	'sitename' => getenv('SITE_NAME'),
	'servername' => preg_replace('~https?://~', '', getenv('URL')),
	'supportingCompany' => getenv('SUPPORTING COMPANY') ?? 'ByWater Solutions',
	'library' => getenv('LIBRARY'),
	'title' => getenv('TITLE'),
	'url' => getenv('URL'),
	'configDir' => $siteDir,
	'solrHost' => getenv('SOLR_HOST') ?? 'localhost',
	'solrPort' => getenv('SOLR_PORT') ?? 8983,
	'timezone' => getenv('TIMEZONE') ?? 'US/Central',
	'aspenAdminPassword' => getenv('ASPEN_ADMIN_PASSWORD'),
	'databaseHost' => getenv('DATABASE_HOST') ?? 'localhost',
	'databasePort' => getenv('DATABASE_PORT') ?? 3306,
	'databaseName' => getenv('DATABASE_NAME'),
	'databaseUser' => getenv('DATABASE_USER'),
	'databasePassword' => getenv('DATABASE_PASSWORD'),

	'enableKoha' => strtolower(getenv('ENABLE_KOHA')),
];

if ($variables['enableKoha'] === "yes") {
	$variables['ilsDriver'] = 'Koha';
	$variables['ilsUrl'] = getenv('KOHA_OPAC_URL');
	$variables['ilsStaffUrl'] = getenv('KOHA_STAFF_URL');
	$variables['ilsDatabaseName'] = getenv('KOHA_DATABASE_NAME');
	$variables['ilsDatabaseHost'] = getenv('KOHA_DATABASE_HOST');
	$variables['ilsDatabasePort'] = getenv('KOHA_DATABASE_PORT');
	$variables['ilsDatabaseUser'] = getenv('KOHA_DATABASE_USER');
	$variables['ilsDatabasePassword'] = getenv('KOHA_DATABASE_PASSWORD');
	$variables['ilsDatabaseTimeZone'] = getenv('KOHA_DATABASE_TIMEZONE') ?? 'US/Central';
	$variables['ilsClientId'] = getenv('KOHA_CLIENT_ID');
	$variables['ilsClientSecret'] = getenv('KOHA_CLIENT_SECRET');
} else {
	$variables['ilsDriver'] = ucfirst(getenv('ILS_DRIVER'));
}

$mandatory = ['sitename', 'servername', 'solrHost', 'solrPort', 'configDir', 'timezone', 'aspenAdminPassword', 'databaseHost', 'databasePort', 'databaseName', 'databaseUser', 'databasePassword'];

if ($variables['enableKoha'] === "yes") {
	$kohaKeys = ['ilsDriver', 'ilsUrl', 'ilsStaffUrl', 'ilsDatabaseName', 'ilsDatabaseHost', 'ilsDatabasePort', 'ilsDatabaseUser', 'ilsDatabasePassword', 'ilsDatabaseTimeZone'];
	$mandatory = array_merge($mandatory, $kohaKeys);
}

$emptyVariables = 0;

foreach ($variables as $key => $value) {
	if (in_array($key, $mandatory) && empty($value)) {
		echo "WARNING: Mandatory variable '" . $key . "' is empty.\n";
		$emptyVariables++;
	}
}

if ($emptyVariables > 0) {
	die(1);
}

$templateDir = "/usr/local/aspen-discovery/sites/template.linux";
$defaultDir = "/usr/local/aspen-discovery/sites/default";
$dockerDir = "/usr/local/aspen-discovery/docker";
$apacheDir = "/etc/apache2";

if (!file_exists($templateDir)) {
	echo "ERROR: The template directory '" . $templateDir . "' does not exists.\n";
	die(1);
}
if (!file_exists($defaultDir)) {
	echo "ERROR: The default site directory '" . $defaultDir . "' does not exists.\n";
	die(1);
}
if (!file_exists($dockerDir)) {
	echo "ERROR: The docker directory '" . $dockerDir . "' does not exists.\n";
	die(1);
}

//Capture any error as ErrorException
set_error_handler("customErrorHandler");

try {
//Copy from template and replace variables
	copy($templateDir . '/httpd-{sitename}.conf', "$siteDir/httpd-{$variables['sitename']}.conf");
	recursive_copy($templateDir . '/conf', $siteDir . '/conf');
	rename($siteDir . '/conf/config.pwd.ini.template', $siteDir . "/conf/config.pwd.ini");

	replaceVariables($siteDir . "/httpd-{$variables['sitename']}.conf", $variables);
	replaceVariables($siteDir . '/conf/config.ini', $variables);
	replaceVariables($siteDir . '/conf/config.cron.ini', $variables);
	replaceVariables($siteDir . '/conf/config.pwd.ini', $variables);
	replaceVariables($siteDir . "/conf/crontab_settings.txt", $variables);

//Copy from default site directory
	copy($defaultDir . "/conf/badBotsLocal.conf", $siteDir . "/conf/badBotsLocal.conf");
	copy($defaultDir . "/conf/badBotsDefault.conf", $siteDir . "/conf/badBotsDefault.conf");

//Copy from docker directory
	copy("$dockerDir/files/cron/crontab", "$siteDir/conf/crontab");
	copy("$dockerDir/files/apache/data-alias.conf", "$apacheDir/conf-available/data-alias.conf");
	replaceVariables($siteDir . "/conf/crontab", $variables);
	replaceVariables($apacheDir . "/conf-available/data-alias.conf", $variables);

//Set timezone
	exec('sudo timedatectl set-timezone "' . $variables['timezone'] . '"');
//Create temp directory
		!file_exists('/tmp') ?? mkdir('/tmp');

} catch (ErrorException $e) {
	echo "ERROR MESSAGE : " . $e->getMessage() . "\n";
	echo "IN : " . $e->getFile() . ":" . $e->getLine() . "\n";
	die(1);
}

echo "--> Configurations have been set successfully\n";

function customErrorHandler(int $errno, string $errstr, string $errfile, int $errline): void {
	if (!(error_reporting() & $errno)) {
		// This error code is not included in error_reporting.
		return;
	}
	if ($errno === E_DEPRECATED || $errno === E_USER_DEPRECATED) {
		// Do not throw an Exception for deprecation warnings as new or unexpected
		// deprecations would break the application.
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

