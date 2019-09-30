<?php
require_once __DIR__ . '/../bootstrap.php';

global $configArray;
$runningProcesses = [];
if ($configArray['System']['operatingSystem'] == 'windows'){
	exec("WMIC PROCESS get Processid,Commandline", $processes);
	$processRegEx = '/.*?java\s+-jar\s(.*?)\.jar.*?\s+(\d+)/ix';
	$processIdIndex = 2;
	$processNameIndex = 1;
}else{
	exec("ps -ef | grep java", $processes);
	$processRegEx = '/(\d+)\s+.*?\d{2}:\d{2}:\d{2}\sjava\s-jar\s(.*?)\.jar.*/ix';
	$processIdIndex = 1;
	$processNameIndex = 2;
}
foreach ($processes as $processInfo){
	if (preg_match($processRegEx, $processInfo, $matches)) {
		$processId = $matches[$processIdIndex];
		$process = $matches[$processNameIndex];
		$runningProcesses[$process] = [
			'name' => $process,
			'pid' => $processId
		];
		//echo("Process: $process ($processId)\r\n");
	}
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
			$results .= "No process found for {$module->name} expected {$module->backgroundProcess}\r\n";
		}
	}
}

foreach ($runningProcesses as $process){
	$results .= "Found process {$process['name']} that does not have a module for it\r\n";
}

if (strlen($results) > 0){
	require_once ROOT_DIR . '/sys/Email/Mailer.php';
	$mailer = new Mailer();
	global $serverName;
	$mailer->send("issues@turningleaftechnologies.com", "$serverName Error with Background processes", $results);
}