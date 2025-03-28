<?php

if (count($_SERVER['argv']) > 1) {
	$serverName = $_SERVER['argv'][1];
	
	// Check to see if the update already exists properly.
	$fhnd = fopen("/usr/local/aspen-discovery/sites/$serverName/conf/crontab_settings.txt", 'r');
	
	if ($fhnd) {
		$lines = [];
		$insertSendCampaignEmails = true;
		$sendCampaignEmailsInserted = false;
		$insertSendCampaignEndingEmails = true;
		$sendCampaignEndingEmailsInserted = false;
		
		// Go through each line of the cron settings file
		while (($line = fgets($fhnd)) !== false) {
			if (strpos($line, 'sendCampaignEmails') > 0) {
				$insertSendCampaignEmails = false;
			}
			if (strpos($line, 'sendCampaignEndingEmails') > 0) {
				$insertSendCampaignEndingEmails = false;
			}
			// Check for the specific marker for campaign email cron job
			if (strpos($line, 'Campaign Email Job') > 0) {
				if ($insertSendCampaignEmails) {
					// Add the cron job for sending campaign emails before the end of the file
					$lines[] = "######################################\n";
					$lines[] = "# Campaign Email Job\n";
					$lines[] = "######################################\n";
					$lines[] = "0 6 * * * root php /usr/local/aspen-discovery/code/web/cron/sendCampaignEmails.php $serverName\n";
					$sendCampaignEmailsInserted = true;
				}
				if ($insertSendCampaignEndingEmails) {
					$lines[] = "######################################\n";
					$lines[] = "# Ending Campaign Email Job\n";
					$lines[] = "######################################\n";
					$lines[] = "0 6 * * * root php /usr/local/aspen-discovery/code/web/cron/sendCampaignEndingEmails.php $serverName\n";
					$sendCampaignEndingEmailsInserted = true;
				}
			}
			$lines[] = $line;
		}
		
		fclose($fhnd);

		if ($insertSendCampaignEmails && !$sendCampaignEmailsInserted) {
			// If the cron job was not found, add it at the end
			$lines[] = "######################################\n";
			$lines[] = "# Campaign Email Job\n";
			$lines[] = "######################################\n";
			$lines[] = "0 6 * * * root php /usr/local/aspen-discovery/code/web/cron/sendCampaignEmails.php $serverName\n";
		}

		if ($insertSendCampaignEndingEmails && !$sendCampaignEndingEmailsInserted) {
			// If the cron job was not found, add it at the end
			$lines[] = "######################################\n";
			$lines[] = "# Ending Campaign Email Job\n";
			$lines[] = "######################################\n";
			$lines[] = "0 6 * * * root php /usr/local/aspen-discovery/code/web/cron/sendCampaignEndingEmails.php $serverName\n";
		}
		
		// Write the updated content back into the crontab settings file
		if ($insertSendCampaignEmails || $insertSendCampaignEndingEmails) {
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
