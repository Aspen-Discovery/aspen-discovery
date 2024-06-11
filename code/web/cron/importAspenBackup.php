<?php
require_once __DIR__ . '/../bootstrap.php';

set_time_limit(0);
global $configArray;
global $serverName;

global $aspen_db;

$dbUser = $configArray['Database']['database_user'];
$dbPassword = $configArray['Database']['database_password'];
$dbName = $configArray['Database']['database_aspen_dbname'];
$dbHost = $configArray['Database']['database_aspen_host'];
$dbPort = $configArray['Database']['database_aspen_dbport'];

//List the files to import
$sqlBackupDir = "/data/aspen-discovery/$serverName/sql_backup/";
if (file_exists($sqlBackupDir)) {
	$exportFiles = scandir($sqlBackupDir);
	foreach ($exportFiles as $exportFile) {
		if ($exportFile != '.' && $exportFile != '..' && is_file($sqlBackupDir . $exportFile)) {
#			/** @noinspection PhpStrFunctionsInspection */
#			if (strpos($exportFile, ".sql") > 0 && strpos($exportFile, 'mysql') === false) {

			if (strpos($exportFile, ".sql") > 0 && strpos($exportFile, 'mysql') === false) {
				// Trimming the first two lines of the file
				$filePath = $sqlBackupDir . $exportFile;
				$fileContents[] = file($filePath)[0];
				if ($fileContents !== false) {
					if ($fileContents[0] == "/*!999999\- enable the sandbox mode */ \n"){
						$fileContents = file($filePath);
						$trimmedContents = array_slice($fileContents, 1);
						file_put_contents($filePath, implode("", $trimmedContents));
					} else{
						echo("Importing $exportFile\n");
						$importCommand = "mysql -u$dbUser -p$dbPassword -h$dbHost -P$dbPort $dbName < $sqlBackupDir$exportFile ";
					}
				}
				$results = [];
				exec($importCommand, $results);
				echo(implode("\n", $results));
				ob_flush();
			}
		}
	}
}

$configArray = null;
$aspen_db = null;

die();