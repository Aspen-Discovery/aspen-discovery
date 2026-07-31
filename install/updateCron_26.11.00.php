<?php

if (count($_SERVER['argv']) > 1) {
	$serverName = $_SERVER['argv'][1];
	// Check to see if the update already exists properly.
	$fhnd = fopen('/usr/local/aspen-discovery/sites/' . $serverName . '/conf/crontab_settings.txt', 'r');
	if ($fhnd) {
		$lines = [];
		$insertPurgeDeletedILSPatrons = true;
		$purgeDeletedILSPatronsInserted = false;
		while (($line = fgets($fhnd)) !== false) {
			// Detect if the cron job is already present.
			if (str_contains($line, 'purgeDeletedILSPatrons.php')) {
				$insertPurgeDeletedILSPatrons = false;
				$line = "30 23 * * * aspen php /usr/local/aspen-discovery/code/web/cron/purgeDeletedILSPatrons.php $serverName\n";
			}
			// Insert before Debian end-of-file marker.
			if ($insertPurgeDeletedILSPatrons && str_contains($line, 'Debian needs a blank line at the end of cron')) {
				if (!empty($lines) && trim(end($lines)) !== '') {
					$lines[] = "\n";
				}
				$lines[] = "#######################################################\n";
				$lines[] = "# Purge patron records that were deleted from the ILS #\n";
				$lines[] = "#######################################################\n";
				$lines[] = "30 23 * * * aspen php /usr/local/aspen-discovery/code/web/cron/purgeDeletedILSPatrons.php $serverName\n";
				$lines[] = "\n";
				$purgeDeletedILSPatronsInserted = true;
			}
			$lines[] = $line;
		}
		fclose($fhnd);

		// Fallback: If marker was not found, add at the end.
		if ($insertPurgeDeletedILSPatrons && !$purgeDeletedILSPatronsInserted) {
			if (!empty($lines) && trim(end($lines)) !== '') {
				$lines[] = "\n";
			}
			$lines[] = "#######################################################\n";
			$lines[] = "# Purge patron records that were deleted from the ILS #\n";
			$lines[] = "#######################################################\n";
			$lines[] = "30 23 * * * aspen php /usr/local/aspen-discovery/code/web/cron//purgeDeletedILSPatrons.php $serverName\n";
			$lines[] = "\n";
			$purgeDeletedILSPatronsInserted = true;
		}

		// Write the file only if the new cron job was inserted.
		if ($purgeDeletedILSPatronsInserted) {
			$newContent = implode('', $lines);
			file_put_contents('/usr/local/aspen-discovery/sites/' . $serverName . '/conf/crontab_settings.txt', $newContent);
		}
	} else {
		echo '- Could not find cron settings file.' . PHP_EOL;
	}
} else {
	echo 'Must provide server name as first argument.' . PHP_EOL;
	exit();
}