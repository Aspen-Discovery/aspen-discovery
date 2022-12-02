<?php
require_once __DIR__ . '/../bootstrap.php';

global $configArray;
global $serverName;
$runningProcesses = [];
if ($configArray['System']['operatingSystem'] == 'windows') {
	exec("WMIC PROCESS get Processid,Commandline", $processes);
	$solrRegex = "/{$serverName}\\\\solr7/ix";
} else {
	exec("ps -ef | grep java", $processes);
	$solrRegex = "/{$serverName}\/solr7/ix";
}

$results = "";

$solrRunning = false;
foreach ($processes as $processInfo) {
	if (preg_match($solrRegex, $processInfo)) {
		$solrRunning = true;
	}
}

if (!$solrRunning) {
	$results .= "Solr is not running for {$serverName}\r\n";
	if ($configArray['System']['operatingSystem'] == 'windows') {
		$solrCmd = "/web/aspen-discovery/sites/{$serverName}/{$serverName}.bat start";
	} else {
		if (!file_exists("/usr/local/aspen-discovery/sites/{$serverName}/{$serverName}.sh")) {
			$results .= "/usr/local/aspen-discovery/sites/{$serverName}/{$serverName}.sh does not exist";
		} elseif (!is_executable("/usr/local/aspen-discovery/sites/{$serverName}/{$serverName}.sh")) {
			$results .= "/usr/local/aspen-discovery/sites/{$serverName}/{$serverName}.sh is not executable";
		}
		$solrCmd = "/usr/local/aspen-discovery/sites/{$serverName}/{$serverName}.sh start";
	}
	exec($solrCmd);
	$results .= "Started solr using command \r\n$solrCmd\r\n";
}

if (strlen($results) > 0) {
	//For debugging
	echo($results);
	try {
		require_once ROOT_DIR . '/sys/SystemVariables.php';
		$systemVariables = new SystemVariables();
		if ($systemVariables->find(true) && !empty($systemVariables->errorEmail)) {
			require_once ROOT_DIR . '/sys/Email/Mailer.php';
			$mailer = new Mailer();
			$mailer->send($systemVariables->errorEmail, "$serverName Error with Background processes", $results);
		}
	} catch (Exception $e) {
		//This happens if the table has not been created
	}
}

function execInBackground($cmd) {
	if (substr(php_uname(), 0, 7) == "Windows") {
		pclose(popen("start /B " . $cmd, "r"));
	} else {
		exec($cmd . " > /dev/null &");
	}
}