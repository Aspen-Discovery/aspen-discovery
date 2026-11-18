<?php
/**
 * Runs one of Aspen's Java processes in the foreground with the JVM options from the [JavaProcesses] section of
 * config.ini, so processes started from cron get the same options as the ones checkBackgroundProcesses.php starts.
 *
 * Usage: php runJavaProcess.php <sitename> <process> [arguments...]
 *   e.g. php runJavaProcess.php aspen.localhost reindexer nightly
 *
 * The exit code of the Java process is returned. This does not load bootstrap.php so it does not depend on the
 * database or the cache being available.
 */
require_once __DIR__ . '/../sys/Utils/JavaProcessUtils.php';

if ($argc < 3) {
	fwrite(STDERR, "Usage: php runJavaProcess.php <sitename> <process> [arguments...]\n");
	exit(1);
}

$serverName = $argv[1];
$processName = $argv[2];
$arguments = array_slice($argv, 3);

if (!preg_match('/^[A-Za-z0-9._-]+$/', $serverName) || str_contains($serverName, '..')) {
	fwrite(STDERR, "Invalid site name '$serverName'\n");
	exit(1);
}
if (!JavaProcessUtils::isValidProcessName($processName)) {
	fwrite(STDERR, "Invalid process name '$processName'\n");
	exit(1);
}

$codeDirectory = dirname(__DIR__, 2);
$configArray = JavaProcessUtils::loadConfig(dirname($codeDirectory) . '/sites', $serverName);
if ($configArray === null) {
	fwrite(STDERR, "Could not read the configuration for $serverName\n");
	exit(1);
}

$processDirectory = "$codeDirectory/$processName";
if (!file_exists("$processDirectory/$processName.jar")) {
	fwrite(STDERR, "Could not start $processName, the jar $processDirectory/$processName.jar does not exist\n");
	exit(1);
}

$jvmOptions = JavaProcessUtils::getJvmOptions($configArray, $processName, $ignoredOptions);
if (!empty($ignoredOptions)) {
	fwrite(STDERR, "Ignoring invalid JVM options for $processName: " . implode(' ', $ignoredOptions) . "\n");
}

$isWindows = strtolower($configArray['System']['operatingSystem'] ?? '') == 'windows';
passthru(JavaProcessUtils::buildCommand($processDirectory, $processName, $serverName, $arguments, $jvmOptions, $isWindows), $exitCode);
exit($exitCode);
