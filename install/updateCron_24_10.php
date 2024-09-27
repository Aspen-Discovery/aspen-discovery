<?php

if (count($_SERVER['argv']) > 1) {
	$serverName = $_SERVER['argv'][1];
	//Check to see if the update already exists properly.
	$fhnd = fopen("/usr/local/aspen-discovery/sites/$serverName/conf/crontab_settings.txt", 'r');
	if ($fhnd) {
		$lines = [];
		$insertGenerateMaterialRequestHoldCandidates = true;
		$generateMaterialRequestHoldCandidatesInserted = false;
		while (($line = fgets($fhnd)) !== false) {
			if (strpos($line, 'generateMaterialRequestHoldCandidates') > 0) {
				$insertGenerateMaterialRequestHoldCandidates = false;
			}
			if (strpos($line, 'Debian needs a blank line at the end of cron') > 0) {
				if ($insertGenerateMaterialRequestHoldCandidates) {
					//Add these before the end of the file in debian
					$lines[] = "##############################################\n";
					$lines[] = "# Generate Materials Request Hold Candidates #\n";
					$lines[] = "##############################################\n";
					$lines[] = "0 9 * * * root php /usr/local/aspen-discovery/code/web/cron/generateMaterialRequestHoldCandidates.php $serverName\n\n";
					$generateMaterialRequestHoldCandidatesInserted = true;
				}
			}
			$lines[] = $line;
		}
		fclose($fhnd);

		if ($insertGenerateMaterialRequestHoldCandidates && !$generateMaterialRequestHoldCandidatesInserted) {
			//Add at the end for everything else
			$lines[] = "##############################################\n";
			$lines[] = "# Generate Materials Request Hold Candidates #\n";
			$lines[] = "##############################################\n";
			$lines[] = "0 9 * * * root php /usr/local/aspen-discovery/code/web/cron/generateMaterialRequestHoldCandidates.php $serverName\n\n";
		}
		if ($generateMaterialRequestHoldCandidatesInserted) {
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