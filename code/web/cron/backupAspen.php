<?php
require_once __DIR__ . '/../bootstrap.php';

set_time_limit(0);
global $configArray;
global $serverName;

global $aspen_db;

$debug = false;

$dbUser = $configArray['Database']['database_user'];
$dbPassword = $configArray['Database']['database_password'];
$dbName = $configArray['Database']['database_aspen_dbname'];
$dbHost = $configArray['Database']['database_aspen_host'];
$dbPort = $configArray['Database']['database_aspen_dbport'];

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
$backupFile = "$backupDir/aspen.$serverName.$curDateTime.tar";
//exec("tar -cf $backupFile");
exec_advanced("cd $backupDir", $debug);

//Create the export files
$listTablesStmt = $aspen_db->query("SHOW TABLES");
$allTables = $listTablesStmt->fetchAll(PDO::FETCH_COLUMN);
foreach ($allTables as $table) {
	$exportData = true;
	//Ignore
	if ($table == 'session' || $table == 'cached_values') {
		$exportData = false;
	}

	$exportFile = "$serverName.$curDateTime.$table.sql";
	$fullExportFilePath = "$backupDir/$exportFile";
	if ($exportData) {
		$dumpCommand = "mysqldump -u$dbUser -p$dbPassword -h$dbHost -P$dbPort $dbName $table > $fullExportFilePath";
	}else{
		$dumpCommand = "mysqldump -u$dbUser -p$dbPassword -h$dbHost -P$dbPort --no-data $dbName $table > $fullExportFilePath";
	}
	exec_advanced($dumpCommand, $debug);

	//remove the exported file
	if (file_exists($fullExportFilePath)) {
		//Add the file to the archive
		exec_advanced("cd $backupDir; tar -rf $backupFile $exportFile", $debug);

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
	exec_advanced("gsutil cp $backupFile.gz gs://$bucketName/", $debug);
}

function exec_advanced($command, $log) {
	if ($log) {
		console_log($command, 'RUNNING: ');
	}
	$result = exec($command);
	if ($log) {
		console_log($result, 'RESULT: ');
	}
}
function console_log($message, $prefix = '') {
	$STDERR = fopen("php://stderr", "w");
	fwrite($STDERR, $prefix.$message."\n");
	fclose($STDERR);
}