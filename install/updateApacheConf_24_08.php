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
				$changeMade = false;
				while (($line = fgets($fhnd)) !== false) {
					if (strpos($line, '# Note: The following RewriteRule directives include the [B] flag to escape') !== false) {
						//Strip this line and add the new Rewrite Rules
						$lines[] = "\t\t\t# Anything that is a direct php file still goes to that\r\n";
						$lines[] = "\t\t\tRewriteRule  ^(.*?\.php).*$         $1  [NC,L]\r\n";
						$lines[] = "\t\t\t# Rewrite everything else to go through index.php\r\n";
						$lines[] = "\t\t\tRewriteRule   ^(.*)$                index.php  [NC,L]\r\n";
						$changeMade = true;
					}else if (strpos($line, '# backreferences.  This prevents encoding problems caused by special characters') !== false ||
						strpos($line, '# like & if they show up in ids.  Note that the flag doesn\'t work in some') !== false ||
						strpos($line, '# versions of Apache prior to 2.2.12; if you run into trouble, try upgrading.') !== false ||
						strpos($line, 'RewriteRule   ^(MyAccount)/([^/]+)/(.+)$   index.php?module=$1&action=$2&id=$3   [B,L,QSA]') !== false ||
						strpos($line, 'RewriteRule   ^(Record)/([^/]+)/(.+)$       index.php?module=$1&id=$2&action=$3   [B,L,QSA]') !== false ||
						strpos($line, 'RewriteRule   ^(Record)/(.+)$               index.php?module=$1&id=$2             [B,L,QSA]') !== false ||
						strpos($line, 'RewriteRule   ^([^/]+)/(.+)$                index.php?module=$1&action=$2         [B,L,QSA]') !== false ||
						strpos($line, 'RewriteRule   ^(Record|EcontentRecord)/([^/]+)/(.+)$       index.php?module=$1&id=$2&action=$3   [B,L,QSA]') !== false ||
						strpos($line, 'RewriteRule   ^(Record|EcontentRecord)/(.+)$               index.php?module=$1&id=$2             [B,L,QSA]') !== false ||
						strpos($line, 'RewriteRule   ^(Search)/?$                  index.php?module=$1                   [B,L,QSA]') !== false ||
						strpos($line, '#RewriteCond   %{REQUEST_URI}    !^/?themes') !== false
						) {
						//Skip this line to remove it
						$changeMade = true;
					} else {
						$lines[] = $line;
					}
				}
				fclose($fhnd);
				if ($changeMade) {
					$newContent = implode("", $lines);
					file_put_contents($fullFileName, $newContent);
				}
			}
		}
	}

} else {
	echo 'Must provide servername as first argument';
	exit();
}