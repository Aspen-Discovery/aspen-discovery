<?php
/**
 * Rewrites the Java entries of a site's crontab (conf/crontab) to start them through runJavaProcess.php, so they get
 * the JVM options from the [JavaProcesses] section of config.ini (DIS-3191).
 *
 * The crontab is created from docker/files/cron/crontab once, when the site is created, and kept in the site's
 * conf volume, so sites created before 26.12 still start these processes with a plain java -jar. This runs on every
 * cron container start; it only changes plain java -jar entries, so running it again makes no changes.
 *
 * Usage: php updateJavaCron.php <crontab file>
 */

require_once __DIR__ . '/../logger/DockerLogger.php';
require_once __DIR__ . '/../../../code/web/sys/Utils/JavaProcessUtils.php';
DockerLogger::init('CRON');

$cronFile = $argv[1] ?? '';
if ($cronFile === '' || !is_file($cronFile)) {
	DockerLogger::warn("Crontab not found, not updating Java entries: $cronFile");
	exit(0);
}

$lines = file($cronFile);
$changed = false;
foreach ($lines as $i => $line) {
	$newLine = JavaProcessUtils::rewriteCronLine($line);
	if ($newLine !== $line) {
		DockerLogger::info('Updated cron entry to use runJavaProcess.php: ' . trim($newLine));
		$lines[$i] = $newLine;
		$changed = true;
	} elseif (JavaProcessUtils::isCustomJavaCronLine($line)) {
		DockerLogger::warn('Cron entry starts java with its own options, not updated; move them to [JavaProcesses] in config.ini: ' . trim($line));
	}
}

if ($changed && file_put_contents($cronFile, implode('', $lines)) === false) {
	DockerLogger::error("Could not write $cronFile");
}
