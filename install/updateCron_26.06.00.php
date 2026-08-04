<?php
if (count($_SERVER['argv']) > 1) {
	$serverName = $_SERVER['argv'][1];
	
	// Check to see if the update already exists properly.
	$fhnd = fopen("/usr/local/aspen-discovery/sites/$serverName/conf/crontab_settings.txt", 'r');
	
	if ($fhnd) {
		$lines = [];
		$insert = true;
		$insertUpdateEventRegistrationInvites = true;
		$updateEventRegistrationInvitationsInserted = false;
		
		
		// Go through each line of the cron settings file
		while (($line = fgets($fhnd)) !== false) {
			if (strpos($line, 'updateEventRegistrationInvitations') > 0) {
				$insertUpdateEventRegistrationInvites = false;
			}
			// Check for the specific marker for event invites cron job
			if (strpos($line, 'Update Event Registration Invites Job') > 0) {
				if ($insertUpdateEventRegistrationInvites) {
					// Add the cron job for sending campaign emails before the end of the file
					$lines[] = "######################################\n";
					$lines[] = "# Update Event Registration Invites Job\n";
					$lines[] = "######################################\n";
					$lines[] = "*/5 * * * * aspen php /usr/local/aspen-discovery/code/web/cron/updateEventRegistrationInvitations.php $serverName\n";
					$updateEventRegistrationInvitationsInserted = true;
				}
			}
			$lines[] = $line;
		}
		
		fclose($fhnd);

		if ($insertUpdateEventRegistrationInvites && !$updateEventRegistrationInvitationsInserted) {
			// If the cron job was not found, add it at the end
			$lines[] = "######################################\n";
			$lines[] = "# Update Event Registration Invites Job\n";
			$lines[] = "######################################\n";
			$lines[] = "*/5 * * * * aspen php /usr/local/aspen-discovery/code/web/cron/updateEventRegistrationInvitations.php $serverName\n";
		}
		
		// Write the updated content back into the crontab settings file
		if ($insertUpdateEventRegistrationInvites) {
			$newContent = implode('', $lines);
			file_put_contents("/usr/local/aspen-discovery/sites/$serverName/conf/crontab_settings.txt", $newContent);
		}
	} else {
		echo("- Could not find cron settings file\n");
	}

} else {
	echo 'Must provide server name as the first argument';
	exit();
}
