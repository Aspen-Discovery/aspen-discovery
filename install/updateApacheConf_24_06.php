<?php
require_once __DIR__ . '/../code/web/bootstrap.php';
require_once __DIR__ . '/../code/web/bootstrap_aspen.php';

global $configArray;

if (count($_SERVER['argv']) > 1) {
	$serverName = $_SERVER['argv'][1];
	//Loop through all http conf files
	$operatingSystem = $configArray['System']['operatingSystem'];
	$linuxDistribution = '';
	if (strcasecmp($operatingSystem, 'windows') == 0) {
		$sitesDir = "c:\\web\\aspen-discovery\\sites\\$serverName";
	} else {
		$sitesDir = "/usr/local/aspen-discovery/sites/$serverName";
	}
	$sitesDirFiles = scandir($sitesDir);
	foreach ($sitesDirFiles as $sitesDirFile) {
		if (strpos($sitesDirFile, 'http') === 0) {
			$fullFileName = $sitesDir . DIRECTORY_SEPARATOR . $sitesDirFile;
			$fhnd = fopen($sitesDir . DIRECTORY_SEPARATOR . $sitesDirFile, 'r');
			if ($fhnd) {
				$lines = [];
				$insertBadBots = true;
				while (($line = fgets($fhnd)) !== false) {
					if (strpos($line, 'Bot Blocking') > 0) {
						$insertBadBots = false;
					}elseif (strpos($line, 'RewriteRule  ^robots\.txt$ /robots.php [NC,L]') !== false) {
						if (strcasecmp($operatingSystem, 'windows') == 0) {
							$lines[] = "\t\t\t# Bot Blocking\r\n";
							$lines[] = "\t\t\tInclude C:\\web\\aspen-discovery\\sites\\$serverName\\conf\\localBadBots.conf\r\n";
							$lines[] = "\t\t\tInclude C:\web\aspen-discovery\sites\default\conf\defaultBadBots.conf\r\n";
							$lines[] = "\r\n";
						} else {
							$lines[] = "\t\t\t# Bot Blocking\n";
							$lines[] = "\t\t\tInclude /usr/local/aspen-discovery/sites/$serverName/conf/localBadBots.conf\n";
							$lines[] = "\t\t\tInclude /usr/local/aspen-discovery/sites/default/conf/defaultBadBots.conf\n";
							$lines[] = "\n";
						}
					}
					$lines[] = $line;
				}
				fclose($fhnd);

				if ($insertBadBots) {
					$newContent = implode("", $lines);
					file_put_contents($fullFileName, $newContent);
				}
			}
		}
	}

	if (strcasecmp($operatingSystem, 'windows') == 0) {
		copy localBadBots file if it doesn't exist already
	} else {
		copy localBadBots file if it doesn't exist already
	}

} else {
	echo 'Must provide servername as first argument';
	exit();
}