<?php

if (count($_SERVER['argv']) > 1) {
	$serverName = $_SERVER['argv'][1];
	//Check to see if the update already exists properly.
	$fhnd = fopen("/usr/local/aspen-discovery/sites/$serverName/conf/crontab_settings.txt", 'r');
	if ($fhnd) {
		$lines = [];
		$insertFetchILSMessages = true;
		$insertSendILSMessages = true;
		while (($line = fgets($fhnd)) !== false) {
			if (strpos($line, 'fetchILSMessages') > 0) {
				$insertFetchILSMessages = false;
			}
			if(strpos($line, 'sendILSMessages') > 0) {
				$insertSendILSMessages = false;
			}
			$lines[] = $line;
		}
		fclose($fhnd);

		if ($insertFetchILSMessages) {
			$lines[] = "######################################\n";
			$lines[] = "# Fetch ILS Messages #\n";
			$lines[] = "######################################\n";
			$lines[] = "*/30 * * * * root php /usr/local/aspen-discovery/code/web/cron/fetchILSMessages.php $serverName\n";
		}
		if ($insertFetchILSMessages) {
			$newContent = implode('', $lines);
			file_put_contents("/usr/local/aspen-discovery/sites/$serverName/conf/crontab_settings.txt", $newContent);
		}

		if ($insertSendILSMessages) {
			$lines[] = "######################################\n";
			$lines[] = "# Send ILS Messages #\n";
			$lines[] = "######################################\n";
			$lines[] = "*/59 * * * * root php /usr/local/aspen-discovery/code/web/cron/sendILSMessages.php $serverName\n";
		}
		if ($insertSendILSMessages) {
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

function getOSInformation() {
	if (false == function_exists('shell_exec') || false == is_readable('/etc/os-release')) {
		return null;
	}

	$os = shell_exec('cat /etc/os-release');
	$listIds = preg_match_all('/.*=/', $os, $matchListIds);
	$listIds = $matchListIds[0];

	$listVal = preg_match_all('/=.*/', $os, $matchListVal);
	$listVal = $matchListVal[0];

	array_walk($listIds, function (&$v, $k) {
		$v = strtolower(str_replace('=', '', $v));
	});

	array_walk($listVal, function (&$v, $k) {
		$v = preg_replace('/=|"/', '', $v);
	});

	return array_combine($listIds, $listVal);
}