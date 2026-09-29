<?php

$jobsToChange = [
	'updateCommunityTranslations.php',
	'generateMaterialRequestHoldCandidates.php',
	'dismissYearInReviewMessages.php',
	'cleanupSharedSessions.php',
	'talpaWorksCron.php',
	'talpaRecalculationCron.php',
	'sendCampaignEmails.php',
	'sendCampaignEndingEmails.php',
	'loadInitialReadingHistory.php',
	'fetchILSMessages.php',
	'sendILSMessages.php',
	'purgeSoftDeleted.php',
	'updateCommunityEngagementMilestones.php',
	'purgeUserAppRequestLogs.php'
];

# Match a possible comment delimiter and indent as well as the {5} timing
# sections of a cron line in a group to retain them, followed by "root" which we
# replace with "aspen".
$pattern = '/^((?:#\\s*)?(?:\\S+\\s+){5})root(\\s)/';
$replacement = '\\1aspen\\2';

function validateCron($crontab) {
	$proc = proc_open(
		'crontab -n -',
		[
			0 => ['pipe', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		],
		$pipes
	);

	if(!$proc) {
		// Error creating process, assume the crontab is valid
		return true;
	}

	fwrite($pipes[0], $crontab);
	fclose($pipes[0]);

	$stderr = stream_get_contents($pipes[2]);

	# 0 return code on success
	if(proc_close($proc) == 0) {
		return true;
	}

	echo $stderr;

	return false;
}

if (count($_SERVER['argv']) > 1) {
	$serverName = $_SERVER['argv'][1];

	// Check to see if the update already exists properly.
	$fhnd = fopen("/usr/local/aspen-discovery/sites/$serverName/conf/crontab_settings.txt", 'r');

	if ($fhnd) {
		$lines = [];
		$changed = false;

		// Go through each line of the cron settings file
		while (($line = fgets($fhnd)) !== false) {
			// Detect if this is a line we should update
			$matched = false;
			foreach($jobsToChange as $needle) {
				if(strpos($line, $needle) > 0) {
					$matched = true;
					break;
				}
			}

			if($matched) {
				$changed = true;
				$original_line = $line;
				$line = preg_replace($pattern, $replacement, $line);

				$original_prefix = '# Before DIS-2753 update: ';
				if($original_line != $line && !str_starts_with($original_line, $original_prefix)) {
					// Keep original line in a comment for reference
					$lines[] = $original_prefix . $original_line;
				}

			}
			$lines[] = $line;
		}

		fclose($fhnd);
		// Write the updated content back into the crontab settings file
		if ($changed) {
			$newContent = implode('', $lines);

			if(validateCron($newContent)) {
				file_put_contents("/usr/local/aspen-discovery/sites/$serverName/conf/crontab_settings.txt", $newContent);
			} else {
				echo "Resulting crontab was invalid, not overwriting\n";
			}
		}
	} else {
		echo("- Could not find cron settings file\n");
	}

} else {
	echo 'Must provide server name as the first argument';
	exit();
}
