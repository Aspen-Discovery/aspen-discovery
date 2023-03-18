<?php
require_once __DIR__ . '/../bootstrap.php';

set_time_limit(0);
global $configArray;
global $serverName;

global $aspen_db;

$debug = true;

$dbUser = $configArray['Database']['database_user'];
$dbPassword = $configArray['Database']['database_password'];
$dbName = $configArray['Database']['database_aspen_dbname'];

//Make sure our backup directory exists
if (!file_exists("/data/aspen-discovery/$serverName/sql_backup")) {
	mkdir("/data/aspen-discovery/$serverName/sql_backup", 700, true);
}

//Remove any backups older than 3 days
$backupDir = "/data/aspen-discovery/$serverName/sql_backup";
exec_advanced("find $backupDir/ -mindepth 1 -maxdepth 1 -name *.sql -type f -mtime +3 -delete", $debug);
exec_advanced("find $backupDir/ -mindepth 1 -maxdepth 1 -name *.sql.gz -type f -mtime +3 -delete", $debug);
exec_advanced("find $backupDir/ -mindepth 1 -maxdepth 1 -name *.tar -type f -mtime +3 -delete", $debug);
exec_advanced("find $backupDir/ -mindepth 1 -maxdepth 1 -name *.tar.gz -type f -mtime +3 -delete", $debug);

//Create the tar file
$curDateTime = date('ymdHis');
$backupFile = "$backupDir/aspen.$curDateTime.tar";
//exec("tar -cf $backupFile");
exec_advanced("cd $backupDir", $debug);

//Create the export files
$listTablesStmt = $aspen_db->query("SHOW TABLES");
$allTables = $listTablesStmt->fetchAll(PDO::FETCH_COLUMN);
foreach ($allTables as $table) {
	$exportFile = "$serverName.$curDateTime.$table.sql";
	$fullExportFilePath = "$backupDir/$exportFile";
	$createTableStmt = $aspen_db->query("SHOW CREATE TABLE $table");
	$createTableString = $createTableStmt->fetch();
	$dumpCommand = "mysqldump -u$dbUser -p$dbPassword $dbName $table > $fullExportFilePath";
	exec($dumpCommand, $debug);

	//remove the exported file
	if (file_exists($fullExportFilePath)) {
		//Add the file to the archive
		exec_advanced("tar -rf $backupFile $exportFile", $debug);

		unlink($fullExportFilePath);
	}
}

//zip up the archive
exec_advanced("gzip $backupFile", $debug);

//Optionally move the file to the Google backup bucket
// Load the system settings
require_once ROOT_DIR . '/sys/SystemVariables.php';
$systemVariables = new SystemVariables();

// See if we have a bucket to backup to
if ($systemVariables->find(true) && !empty($systemVariables->googleBucket)) {
	//Perform the backup
	$bucketName = $systemVariables->googleBucket;
	exec_advanced("gsutil cp $backupFile gs://$bucketName/", $debug);
}

function exec_advanced($command, $log) {
	if ($log) {
		console_log($command);
	}
	exec($command);
}
function console_log($message) {
	$STDERR = fopen("php://stderr", "w");
	fwrite($STDERR, "\n".$message."\n\n");
	fclose($STDERR);
}