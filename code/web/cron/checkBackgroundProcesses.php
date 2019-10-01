<?php
require_once __DIR__ . '/../bootstrap.php';

global $configArray;
global $serverName;
$runningProcesses = [];
if ($configArray['System']['operatingSystem'] == 'windows'){
	exec("WMIC PROCESS get Processid,Commandline", $processes);
	$processRegEx = '/.*?java\s+-jar\s(.*?)\.jar.*?\s+(\d+)/ix';
	$processIdIndex = 2;
	$processNameIndex = 1;
	$solrRegex = "/{$serverName}\solr7/";
}else{
	exec("ps -ef | grep java", $processes);
	$processRegEx = '/(\d+)\s+.*?\d{2}:\d{2}:\d{2}\sjava\s-jar\s(.*?)\.jar.*/ix';
	$processIdIndex = 1;
	$processNameIndex = 2;
	$solrRegex = "/{$serverName}\/solr7/";
}
$solrRunning = false;
foreach ($processes as $processInfo){
	if (preg_match($processRegEx, $processInfo, $matches)) {
		$processId = $matches[$processIdIndex];
		$process = $matches[$processNameIndex];
		$runningProcesses[$process] = [
			'name' => $process,
			'pid' => $processId
		];
		//echo("Process: $process ($processId)\r\n");
	}else if (preg_match($solrRegex, $processInfo, $matches)) {
		$solrRunning = true;
	}
}

if (!$solrRunning){
	$results .= "Solr is not running for {$serverName} expected {$module->backgroundProcess}\r\n";
}
require_once ROOT_DIR . '/sys/Module.php';
$module = new Module();
$module->enabled = true;
$module->find();

$results = "";
while ($module->fetch()){
	if (!empty($module->backgroundProcess)){
		if (isset($runningProcesses[$module->backgroundProcess])){
			unset($runningProcesses[$module->backgroundProcess]);
		}else{
			$results .= "No process found for '{$module->name}' expected '{$module->backgroundProcess}'\r\n";
			//Attempt to restart the service
			$local = $configArray['Site']['local'];
			//The local path include web, get rid of that
			$local = substr($local, 0, strrpos($local, '/') -1);
			$processPath = $local . '/' . $module->backgroundProcess;
			if (file_exists($processPath)){
				if (file_exists($processPath . "/{$module->backgroundProcess}.jar")){
					$processStartCmd = "cd $processPath; java -jar {$module->backgroundProcess}.jar $serverName &";
					exec($processStartCmd);
				}else{
					$results .= "Could not automatically restart {$module->name}, the jar $processPath/{$module->backgroundProcess}.jar did not exist\r\n";
				}
			}else{
				$results .= "Could not automatically restart {$module->name}, the directory $processPath did not exist\r\n";
			}
		}
	}
}

foreach ($runningProcesses as $process){
	if ($process['name'] != 'cron'){
		$results .= "Found process '{$process['name']}' that does not have a module for it\r\n";
	}
}

if (strlen($results) > 0){
	require_once ROOT_DIR . '/sys/Email/Mailer.php';
	$mailer = new Mailer();
	$mailer->send("issues@turningleaftechnologies.com", "$serverName Error with Background processes", $results);
}