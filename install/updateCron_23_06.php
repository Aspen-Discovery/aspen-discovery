<?php

if (count($_SERVER['argv']) > 1) {
	$serverName = $_SERVER['argv'][1];
	//Check to see if the update already exists properly.
	$fhnd = fopen("/usr/local/aspen-discovery/sites/$serverName/conf/crontab_settings.txt", 'r');
	if ($fhnd) {
		$lines = [];
		$insertUpdate = true;
		while (($line = fgets($fhnd)) !== false) {
			if (strpos($line, 'updateCommunityTranslations') > 0) {
				$insertUpdate = false;
			}
		}
		fclose($fhnd);
		if ($insertUpdate) {
			$lines[] = "#########################\n";
			$lines[] = "# Update Translations from Community #\n";
			$lines[] = "#########################\n";
			$lines[] = "15 1 * * * root php /usr/local/aspen-discovery/code/web/cron/updateCommunityTranslations.php $serverName\n";
		}
		if ($insertUpdate) {
			$newContent = implode('', $lines);
			file_put_contents("/usr/local/aspen-discovery/sites/$serverName/conf/crontab_settings.txt", $newContent);
		}
	} else {
		echo("- Could not find cron settings file\n");
	}

} else {
	echo 'Must provide servername as first argument';
	exit();
}