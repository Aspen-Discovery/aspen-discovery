<?php

/**
 * DIS-3191: start the Java processes in cron (nightly reindexer, oai_indexer and cron.jar) through
 * runJavaProcess.php so they get the JVM options from the [JavaProcesses] section of config.ini.
 *
 * Rewrites lines like
 *   0 2 * * * aspen cd /usr/local/aspen-discovery/code/reindexer; java -jar reindexer.jar {sitename} nightly
 * to
 *   0 2 * * * aspen php /usr/local/aspen-discovery/code/web/cron/runJavaProcess.php {sitename} reindexer nightly
 * keeping the schedule, user, extra arguments and any output redirection. Lines that already pass JVM options to java
 * are left alone so local customizations are not lost; those are reported instead.
 */

const JAVA_CRON_PATTERN = '~cd[ \t]+(/\S+)/code/(\w+)[ \t]*(?:;|&&)[ \t]*java[ \t]+-jar[ \t]+\2\.jar[ \t]+(\S+)((?:[ \t]+[^\s<>|&;]+)*)~';

function updateJavaCronLine(string $line): string {
	return preg_replace(JAVA_CRON_PATTERN, 'php $1/code/web/cron/runJavaProcess.php $3 $2$4', $line);
}

if (count($_SERVER['argv']) > 1) {
	$serverName = $_SERVER['argv'][1];
	$cronFile = "/usr/local/aspen-discovery/sites/$serverName/conf/crontab_settings.txt";

	$fhnd = fopen($cronFile, 'r');
	if ($fhnd) {
		$lines = [];
		$changed = false;
		while (($line = fgets($fhnd)) !== false) {
			$newLine = updateJavaCronLine($line);
			if ($newLine !== $line) {
				echo "- Updated cron entry:\n    $line    $newLine";
				$changed = true;
			} elseif (preg_match('/java[ \t]+-\S+.*-jar\s/', $line) && !preg_match('/^\s*#/', $line)) {
				echo "- Not updated, the java command line has custom options; move them to [JavaProcesses] in config.ini:\n    $line";
			}
			$lines[] = $newLine;
		}
		fclose($fhnd);

		if ($changed) {
			file_put_contents($cronFile, implode('', $lines));
		}
	} else {
		echo '- Could not find cron settings file.' . PHP_EOL;
	}
} else {
	echo 'Must provide server name as first argument.' . PHP_EOL;
	exit();
}
