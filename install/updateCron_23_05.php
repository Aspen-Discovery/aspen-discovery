<?php

if (count($_SERVER['argv']) > 1) {
	$serverName = $_SERVER['argv'][1];
	//Check to see if the update already exists properly.
	$fhnd = fopen("/usr/local/aspen-discovery/sites/$serverName/conf/crontab_settings.txt", 'r');
	if ($fhnd) {
		$lines = [];
		$insertUpdate = true;
		$changeToRoot = false;
		while (($line = fgets($fhnd)) !== false) {
			if (strpos($line, 'runScheduledUpdate') > 0) {
				$insertUpdate = false;
				if (strpos($line, 'aspen ') > 0) {
					$lines[] = "*/5 * * * * root php /usr/local/aspen-discovery/code/web/cron/runScheduledUpdate.php $serverName";
				}else{
					$lines[] = $line;
				}
			} else {
				$lines[] = $line;
			}
		}
		fclose($fhnd);
		if ($insertUpdate) {
			$lines[] = "#########################";
			$lines[] = "# Run Scheduled Updates #";
			$lines[] = "#########################";
			$lines[] = "*/5 * * * * root php /usr/local/aspen-discovery/code/web/cron/runScheduledUpdate.php $serverName";
		}
		if ($changeToRoot || $insertUpdate) {
			$newContent = implode("\n", $lines);
			file_put_contents("/usr/local/aspen-discovery/sites/$serverName/conf/crontab_settings.txt", $newContent);
		}
	}

} else {
	echo 'Must provide servername as first argument';
	exit();
}