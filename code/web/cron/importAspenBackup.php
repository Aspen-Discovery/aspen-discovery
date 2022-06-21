<?php
require_once __DIR__ . '/../bootstrap.php';

set_time_limit(0);
global $configArray;
global $serverName;

global $aspen_db;

$dbUser = $configArray['Database']['database_user'];
$dbPassword = $configArray['Database']['database_password'];
$dbName = $configArray['Database']['database_aspen_dbname'];

//List the files to import
$sqlBackupDir = "/data/aspen-discovery/$serverName/sql_backup/";
if (file_exists($sqlBackupDir)){
	$exportFiles = scandir($sqlBackupDir);
	foreach ($exportFiles as $exportFile){
		if ($exportFile != '.' && $exportFile != '..' && is_file($sqlBackupDir . $exportFile)) {
			if (strpos($exportFile, ".sql") > 0) {
				echo("Importing $exportFile\n");
				$importCommand = "mysql -u$dbUser -p$dbPassword $dbName < $sqlBackupDir$exportFile";
				$results = [];
				exec($importCommand, $results);
				echo(implode("\n", $results));
				ob_flush();
			}
		}
	}
}