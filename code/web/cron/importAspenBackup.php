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
			/** @noinspection PhpStrFunctionsInspection */
			if (strpos($exportFile, ".sql") > 0 && strpos($exportFile, 'mysql') === false) {
				// Trimming the first two lines of the file
				$filePath = $sqlBackupDir . $exportFile;
				$fhnd = fopen($filePath, 'r');
				$line = fgets($fhnd);
				fclose($fhnd);

				echo("Importing $exportFile\n");
				if (strpos($line, "/*!999999\- enable the sandbox mode") === 0){
					$importCommand = "mysql -u$dbUser -p$dbPassword -h$dbHost -P$dbPort -D $dbName --force < $sqlBackupDir$exportFile";
				}else{
					$importCommand = "mysql -u$dbUser -p$dbPassword -h$dbHost -P$dbPort $dbName < $sqlBackupDir$exportFile ";
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