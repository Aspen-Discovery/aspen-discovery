<?php

if (count($_SERVER['argv']) > 1) {
	$serverName = $_SERVER['argv'][1];
	//Check to see if the update already exists properly.
	$fhnd = fopen("/usr/local/aspen-discovery/sites/$serverName/conf/crontab_settings.txt", 'r');
	if ($fhnd) {
		$lines = [];
		$insertUpdateTranslations = true;
		while (($line = fgets($fhnd)) !== false) {
			if (strpos($line, 'updateCommunityTranslations') > 0) {
				$insertUpdateTranslations = false;
			}
			$lines[] = $line;
		}
		fclose($fhnd);
		if ($insertUpdateTranslations) {
			$lines[] = "######################################\n";
			$lines[] = "# Update Translations from Community #\n";
			$lines[] = "######################################\n";
			$lines[] = "15 1 * * * root php /usr/local/aspen-discovery/code/web/cron/updateCommunityTranslations.php $serverName\n";
		}
		if ($insertUpdateTranslations) {
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

function getOSInformation()
{
	if (false == function_exists("shell_exec") || false == is_readable("/etc/os-release")) {
		return null;
	}

	$os         = shell_exec('cat /etc/os-release');
	$listIds    = preg_match_all('/.*=/', $os, $matchListIds);
	$listIds    = $matchListIds[0];

	$listVal    = preg_match_all('/=.*/', $os, $matchListVal);
	$listVal    = $matchListVal[0];

	array_walk($listIds, function(&$v, $k){
		$v = strtolower(str_replace('=', '', $v));
	});

	array_walk($listVal, function(&$v, $k){
		$v = preg_replace('/=|"/', '', $v);
	});

	return array_combine($listIds, $listVal);
}