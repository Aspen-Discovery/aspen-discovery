<?php

if (count($_SERVER['argv']) > 1) {
	$serverName = $_SERVER['argv'][1];
	//Check to see if the update already exists properly.
	$fhnd = fopen("/usr/local/aspen-discovery/sites/$serverName/conf/crontab_settings.txt", 'r');
	if ($fhnd) {
		$lines = [];
		$insertClamScan = true;
		$replaceClamScan = false;
		while (($line = fgets($fhnd)) !== false) {
			if (strpos($line, 'clamscan') > 0) {
				$insertClamScan = false;
				$replaceClamScan = true;
			} else {
				$lines[] = $line;
			}
		}
		fclose($fhnd);
		if ($insertClamScan) {
			$lines[] = "######################################\n";
			$lines[] = "# Run Clam AV Scans                  #\n";
			$lines[] = "######################################\n";
			$lines[] = "00 2 * * * root /bin/clamscan --recursive=yes --quiet -i --exclude-dir=/var/lib/mysql --exclude-dir=/sys --exclude-dir=/data/aspen-discovery/$serverName/solr7/ --exclude-dir=/var/log/aspen-discovery/$serverName --exclude-dir=/data/aspen-discovery/$serverName/covers/small --exclude-dir=/data/aspen-discovery/$serverName/covers/medium --exclude-dir=/data/aspen-discovery/$serverName/covers/large --log=/var/log/aspen-discovery/clam_av.log /\n";
		}else if ($replaceClamScan) {
			$lines[] = "00 2 * * * root /bin/clamscan --recursive=yes --quiet -i --exclude-dir=/var/lib/mysql --exclude-dir=/sys --exclude-dir=/data/aspen-discovery/$serverName/solr7/ --exclude-dir=/var/log/aspen-discovery/$serverName --exclude-dir=/data/aspen-discovery/$serverName/covers/small --exclude-dir=/data/aspen-discovery/$serverName/covers/medium --exclude-dir=/data/aspen-discovery/$serverName/covers/large --log=/var/log/aspen-discovery/clam_av.log /\n";
		}
		if ($insertClamScan || $replaceClamScan) {
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