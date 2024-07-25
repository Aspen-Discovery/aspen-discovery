<?php
require_once __DIR__ . '/../bootstrap.php';

set_time_limit(0);
ini_set('memory_limit', '2G');
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
$backupDir = "/data/aspen-discovery/$serverName/sql_backup";
if (!file_exists($backupDir)) {
	mkdir($backupDir, 700, true);
}

//Remove any backups older than 2 days
$currentFilesInBackup = scandir($backupDir);
$earliestTimeToKeep = time() - (2 * 24 * 60 * 60);
foreach ($currentFilesInBackup as $file) {
	$okToProcess = false;
	if (strlen($file) > 4) {
		$last4 = substr($file, -4);
		if ($last4 == ".sql" || $last4 == ".tar") {
			$okToProcess = true;
		}
	}
	if (!$okToProcess && strlen($file) > 7) {
		$last4 = substr($file, -7);
		if ($last4 == ".tar.gz" || $last4 == ".sql.gz") {
			$okToProcess = true;
		}
	}
	if ($okToProcess) {
		//Backup files we should delete after 3 days
		$lastModified = filemtime($backupDir . '/'. $file);
		if ($lastModified != false && $lastModified < $earliestTimeToKeep) {
			unlink($backupDir . '/'. $file);
		}
	}
}

//Create the tar file
$curDateTime = date('ymdHis');
$backupFile = "$backupDir/aspen.$serverName.$curDateTime.tar";
//exec("tar -cf $backupFile");
if ($configArray['System']['operatingSystem'] != 'windows') {
	/** @noinspection PhpConditionAlreadyCheckedInspection */
	exec_advanced("cd $backupDir", $debug);
}

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
		$dumpCommand = "mariadb-dump -u$dbUser -p$dbPassword -h$dbHost -P$dbPort $dbName $table > $fullExportFilePath";
	}else{
		$dumpCommand = "mariadb-dump -u$dbUser -p$dbPassword -h$dbHost -P$dbPort --no-data $dbName $table > $fullExportFilePath";
	}
	/** @noinspection PhpConditionAlreadyCheckedInspection */
	exec_advanced($dumpCommand, $debug);

	//Add the file to the archive
	if (file_exists($fullExportFilePath)) {
		if ($configArray['System']['operatingSystem'] != 'windows') {
			/** @noinspection PhpConditionAlreadyCheckedInspection */
			exec_advanced("cd $backupDir; tar -rf $backupFile $exportFile", $debug);

			unlink($fullExportFilePath);
		}
	}
}
$listTablesStmt->closeCursor();

//zip up the archive
if ($configArray['System']['operatingSystem'] != 'windows') {
	/** @noinspection PhpConditionAlreadyCheckedInspection */
	exec_advanced("gzip $backupFile", $debug);
}

//Optionally move the file to the Google backup bucket
// Load the system settings
require_once ROOT_DIR . '/sys/SystemVariables.php';
$systemVariables = new SystemVariables();

// See if we have a bucket to back up to
if ($systemVariables->find(true) && !empty($systemVariables->googleBucket)) {
	//Perform the backup
	$bucketName = $systemVariables->googleBucket;
	exec_advanced("gsutil cp $backupFile.gz gs://$bucketName/", $debug);
}

$aspen_db = null;
$configArray = null;
die();

/////// END OF PROCESS ///////

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