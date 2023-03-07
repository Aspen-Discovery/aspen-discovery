<?php
require_once __DIR__ . '/../bootstrap.php';

set_time_limit(0);
global $configArray;
global $serverName;

global $aspen_db;

$dbUser = $configArray['Database']['database_user'];
$dbPassword = $configArray['Database']['database_password'];
$dbName = $configArray['Database']['database_aspen_dbname'];

//Make sure our backup directory exists
if (!file_exists("/data/aspen-discovery/$serverName/sql_backup")) {
	mkdir("/data/aspen-discovery/$serverName/sql_backup", 700, true);
}

//Remove any backups older than 3 days
$backupDir = "/data/aspen-discovery/$serverName/sql_backup";
exec("find $backupDir/ -mindepth 1 -maxdepth 1 -name *.sql -type f -mtime +3 -delete");
exec("find $backupDir/ -mindepth 1 -maxdepth 1 -name *.sql.gz -type f -mtime +3 -delete");
exec("find $backupDir/ -mindepth 1 -maxdepth 1 -name *.tar -type f -mtime +3 -delete");
exec("find $backupDir/ -mindepth 1 -maxdepth 1 -name *.tar.gz -type f -mtime +3 -delete");


//Create the export files
$listTablesStmt = $aspen_db->query("SHOW TABLES");
$allTables = $listTablesStmt->fetchAll(PDO::FETCH_COLUMN);
$curDateTime = date('ymdHis');
foreach ($allTables as $table) {
	$exportFile = "/tmp/$serverName.$curDateTime.$table.sql";
	$createTableStmt = $aspen_db->query("SHOW CREATE TABLE $table");
	$createTableString = $createTableStmt->fetch();
	$dumpCommand = "mysqldump -u$dbUser -p$dbPassword $dbName $table > $exportFile";
	exec($dumpCommand);
}

$backupFile = "/data/aspen-discovery/$serverName/sql_backup/aspen.$curDateTime.tar.gz";

//tar and gzip them
exec("cd /tmp;tar -czf $backupFile $serverName.$curDateTime.*");

//Optionally move the file to the Google backup bucket
// Load the system settings
require_once ROOT_DIR . '/sys/SystemVariables.php';
$systemVariables = new SystemVariables();

// See if we have a bucket to backup to
if ($systemVariables->find(true) && !empty($systemVariables->googleBucket)) {
	//Perform the backup
	$bucketName = $systemVariables->googleBucket;
	exec("gsutil cp $backupFile gs://$bucketName/");
}

//Cleanup the files
foreach ($allTables as $table) {
	$exportFile = "/tmp/$serverName.$curDateTime.$table.sql";
	if (file_exists($exportFile)) {
		unlink($exportFile);
	}
}