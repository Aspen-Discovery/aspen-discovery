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
				//echo("Found runScheduled Update line\n");
				$insertUpdate = false;
				if (strpos($line, 'aspen ') > 0) {
					$changeToRoot = true;
					//echo("- Need to convert to run as root\n");
					$lines[] = "*/5 * * * * root php /usr/local/aspen-discovery/code/web/cron/runScheduledUpdate.php $serverName\n";
				}else{
					$lines[] = $line;
				}
			} else {
				$lines[] = $line;
			}
		}
		fclose($fhnd);
		if ($insertUpdate) {
			//echo("- Inserting run scheduled update cron\n");
			$lines[] = "#########################\n";
			$lines[] = "# Run Scheduled Updates #\n";
			$lines[] = "#########################\n";
			$lines[] = "*/5 * * * * root php /usr/local/aspen-discovery/code/web/cron/runScheduledUpdate.php $serverName\n";
		}
		if ($changeToRoot || $insertUpdate) {
			//echo("- Writing new cron\n");
			$newContent = implode("", $lines);
			file_put_contents("/usr/local/aspen-discovery/sites/$serverName/conf/crontab_settings.txt", $newContent);
		}
	}else {
		echo("- Could not find cron settings file\n");
	}

} else {
	echo 'Must provide servername as first argument';
	exit();
}