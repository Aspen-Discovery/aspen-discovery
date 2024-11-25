<?php
require_once __DIR__ . '/../../../code/web/bootstrap.php';

global $configArray;
global $serverName;

//Check to see if there are processes that should be stopped
require_once ROOT_DIR . '/sys/Greenhouse/ProcessToStop.php';
$processToStop = new ProcessToStop();
$processToStop->stopAttempted = 0;
$processToStop->find();
$processesToStop = [];
while ($processToStop->fetch()) {
	$processesToStop[$processToStop->processId] = clone $processToStop;
}

$runningProcesses = [];

exec("ps -ef | grep java", $processes);
$processRegEx = '/(\d+)\s+.*?\d{2}:\d{2}:\d{2}\sjava\s-jar\s(.*?)\.jar\s' . $serverName . '/ix';
$processIdIndex = 1;
$processNameIndex = 2;
$nightlyReindexRegex = '/(\d+)\s+.*?\d{2}:\d{2}:\d{2}\sjava\s-jar\sreindexer\.jar\s' . $serverName . '\snightly/ix';

$results = "";

$nightlyReindexRunning = false;
foreach ($processes as $processInfo) {
	if (preg_match($nightlyReindexRegex, $processInfo, $matches)) {
		$nightlyReindexRunning = true;
	} elseif (preg_match($processRegEx, $processInfo, $matches)) {
		$processId = $matches[$processIdIndex];
		$process = $matches[$processNameIndex];

		//Check to see if this process should be killed.
		if (array_key_exists($processId, $processesToStop)) {
			/** @var ProcessToStop $processToStop */
			$processToStop = $processesToStop[$processId];
			$processToStop->stopAttempted = true;
			$processToStop->update();

			$stopResults = "attempting to stop {$processToStop->processName}<br>";
			exec("kill -9 $processId", $stopResultsRaw);
			$stopResults .= implode("<br> - ", $stopResultsRaw) . "<br>";
			
			$processToStop->stopResults = $stopResults;
			$processToStop->update();
		} else {
			if (array_key_exists($process, $runningProcesses)) {
				$results .= "There is more than one process for $process PID: {$runningProcesses[$process]['pid']} and $processId\r\n";
			} else {
				$runningProcesses[$process] = [
					'name' => $process,
					'pid' => $processId,
				];
			}
		}
		//echo("Process: $process ($processId)\r\n");
	}
}

//Check to see if reindex processes are running for each module unless the nightly index is running.
if (!$nightlyReindexRunning) {
	require_once ROOT_DIR . '/sys/Module.php';
	$aspenModule = new Module();
	$aspenModule->enabled = true;
	$aspenModule->find();

	$backgroundProcessesToRun = [];
	while ($aspenModule->fetch()) {
		if (!empty($aspenModule->backgroundProcess)) {
			$backgroundProcessesToRun[$aspenModule->backgroundProcess] = $aspenModule->backgroundProcess;
		}
	}
	foreach ($backgroundProcessesToRun as $backgroundProcess) {
		if (isset($runningProcesses[$backgroundProcess])) {
			unset($runningProcesses[$backgroundProcess]);
		} else {
			//Don't message starting background processes since this can happen nightly. Only show an error if the restart fails.
			//Attempt to restart the service
			$local = $configArray['Site']['local'];
			//The local path include web, get rid of that
			$local = substr($local, 0, strrpos($local, '/'));
			$processPath = $local . '/' . $backgroundProcess;
			if (file_exists($processPath)) {
				if (file_exists($processPath . "/$backgroundProcess.jar")) {
					execInBackground("cd $processPath; java -jar $backgroundProcess.jar $serverName");
					//Don't send an error message when successfully starting a process.
				} else {
					$results .= "Could not automatically restart $backgroundProcess, the jar $processPath/$backgroundProcess.jar did not exist\r\n";
				}
			} else {
				$results .= "Could not automatically restart $backgroundProcess, the directory $processPath did not exist\r\n";
			}
		}
	}
	$aspenModule->__destruct();
	$aspenModule = null;

	foreach ($runningProcesses as $process) {
		if ($process['name'] != 'cron' && $process['name'] != 'oai_indexer' && $process['name'] != 'reindexer') {
			$results .= "Found process '{$process['name']}' that does not have a module for it\r\n";
		}
	}
}

//if (strlen($results) > 0) {
//	//For debugging
//	try {
//		require_once ROOT_DIR . '/sys/SystemVariables.php';
//		$systemVariables = new SystemVariables();
//		if ($systemVariables->find(true) && !empty($systemVariables->errorEmail)) {
//			require_once ROOT_DIR . '/sys/Email/Mailer.php';
//			$mailer = new Mailer();
//			$mailer->send($systemVariables->errorEmail, "$serverName Error with Background processes", $results);
//			$mailer = null;
//		}
//		$systemVariables = null;
//	} catch (Exception $e) {
//		//This happens if the table has not been created
//	}
//}

global $aspen_db;
$aspen_db = null;
$configArray = null;

die();

/////// END OF PROCESS ///////

function execInBackground($cmd) {
	exec($cmd . " > /dev/null &");	
}
