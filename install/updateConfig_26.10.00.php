<?php

// Adds Site.fileGroupOwner to an existing config.ini. The group is taken from the site's data
// directory, which the installers already assign to the group shared by Apache and Aspen.

if (count($_SERVER['argv']) < 2) {
	echo("Please provide the server name to update as the first argument, and optionally the group to use as the second\n");
	exit(1);
}

// Only the configuration is needed, so skip the full bootstrap, which also requires a database connection
define('ROOT_DIR', __DIR__ . '/../code/web');
require_once ROOT_DIR . '/sys/ConfigArray.php';
require_once ROOT_DIR . '/sys/Storage/StorageDriverFactory.php';

$serverName = $_SERVER['argv'][1];
$configFile = ROOT_DIR . "/../../sites/$serverName/conf/config.ini";
if (!file_exists($configFile)) {
	echo("- Could not find $configFile\n");
	exit(1);
}

global $configArray;
$configArray = readConfig();

if (array_key_exists('fileGroupOwner', $configArray['Site'] ?? [])) {
	echo("- fileGroupOwner is already set, nothing to do\n");
	exit(0);
}

if (count($_SERVER['argv']) > 2) {
	$fileGroupOwner = $_SERVER['argv'][2];
	if (posix_getgrnam($fileGroupOwner) === false) {
		echo("- Group $fileGroupOwner does not exist\n");
		exit(1);
	}
} else {
	if ($configArray['System']['operatingSystem'] == 'windows') {
		echo("- fileGroupOwner is not used on Windows, nothing to do\n");
		exit(0);
	}
	$dataRoot = StorageDriverFactory::resolveDataRoot();
	if (!file_exists($dataRoot)) {
		echo("- Data directory $dataRoot does not exist, rerun with the group as the second argument\n");
		exit(1);
	}
	$groupId = filegroup($dataRoot);
	$groupInfo = posix_getgrgid($groupId);
	if ($groupId === 0 || $groupInfo === false) {
		echo("- Data directory $dataRoot is not owned by a shared group, rerun with the group as the second argument\n");
		exit(1);
	}
	$fileGroupOwner = $groupInfo['name'];
}

$lines = file($configFile);
$newLines = [];
$added = false;
foreach ($lines as $line) {
	$newLines[] = $line;
	if (!$added && trim($line) == '[Site]') {
		$newLines[] = "fileGroupOwner  = $fileGroupOwner\n";
		$added = true;
	}
}
if (!$added) {
	echo("- Could not find the [Site] section in $configFile\n");
	exit(1);
}

file_put_contents($configFile, implode('', $newLines));
echo("- Set fileGroupOwner to $fileGroupOwner\n");
