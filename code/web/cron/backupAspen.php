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
if (!file_exists("/data/aspen-discovery/$serverName/sql_backup")) {
	mkdir("/data/aspen-discovery/$serverName/sql_backup", 700, true);
}

//Remove any backups older than 3 days
$backupDir = "/data/aspen-discovery/$serverName/sql_backup";
if ($configArray['System']['operatingSystem'] != 'windows') {
	exec_advanced("find $backupDir/ -mindepth 1 -maxdepth 1 -name *.sql -type f -mtime +3 -delete", $debug);
	exec_advanced("find $backupDir/ -mindepth 1 -maxdepth 1 -name *.sql.gz -type f -mtime +3 -delete", $debug);
	exec_advanced("find $backupDir/ -mindepth 1 -maxdepth 1 -name *.tar -type f -mtime +3 -delete", $debug);
	exec_advanced("find $backupDir/ -mindepth 1 -maxdepth 1 -name *.tar.gz -type f -mtime +3 -delete", $debug);
}

//Create the tar file
$curDateTime = date('ymdHis');
$backupFile = "$backupDir/aspen.$serverName.$curDateTime.tar";
//exec("tar -cf $backupFile");
if ($configArray['System']['operatingSystem'] != 'windows') {
	exec_advanced("cd $backupDir", $debug);
}

//Create the export files
$listTablesStmt = $aspen_db->query("SHOW TABLES");
$allTables = $listTablesStmt->fetchAll(PDO::FETCH_COLUMN);
foreach ($allTables as $table) {
	$exportData = true;
	//Ignore
	if ($table == 'session' || $table == 'cached_values' || $table == 'external_request_log') {
		$exportData = false;
	}

	$exportFile = "$serverName.$curDateTime.$table.sql";
	$fullExportFilePath = "$backupDir/$exportFile";

	$createTableStmt = $aspen_db->query("SHOW CREATE TABLE " . $table);
	$createTablesRS = $createTableStmt->fetchAll(PDO::FETCH_ASSOC);
	$fhnd = fopen($fullExportFilePath, 'w');
	fwrite($fhnd, "DROP TABLE IF EXISTS $table;\n");
	foreach ($createTablesRS as $createTableSql) {
		$createTableValue = $createTableSql['Create Table'];
		//Remove the auto increment id
		$createTableValue = preg_replace('/AUTO_INCREMENT=\d+/', '', $createTableValue);
		fwrite($fhnd, $createTableValue . ";\n");
	}
	$createTableStmt->closeCursor();
	$createTableStmt = null;
	fflush($fhnd);

	if ($exportData) {
		$exportDataStmt = $aspen_db->query("SELECT * FROM " . $table);
		$isFirstRow = true;
		$hasData = false;
		$numRowsWritten = 0;
		while ($row = $exportDataStmt->fetch(PDO::FETCH_ASSOC)) {
			$hasData = true;
			if ($isFirstRow) {
				$columns = implode(',', array_keys($row));
				$insertStatement = "INSERT INTO $table ($columns) VALUES ";
				fwrite($fhnd, $insertStatement);
			}
			$values = [];
			$isFirstValue = true;
			if (!$isFirstRow) {
				fwrite($fhnd, ", ");
			}
			fwrite($fhnd, "(");
			foreach ($row as $value) {
				if (!$isFirstValue) {
					fwrite($fhnd, ",");
				}
				if (is_null($value)) {
					fwrite($fhnd, 'NULL');
				}else if (is_numeric($value)) {
					fwrite($fhnd, $value);
				}else{
					fwrite($fhnd, "'");
					fwrite($fhnd, str_replace("'", "/'", $value));
					fwrite($fhnd, "'");
					$values[] = "'" . str_replace("'", "/'", $value) . "'";
				}
				$isFirstValue = false;
			}
			fwrite($fhnd, ")");
			if ($numRowsWritten++ % 2500 == 0) {
				fflush($fhnd);
				usleep(250);
			}

			$isFirstRow = false;
		}
		if ($hasData) {
			fwrite($fhnd, ";\n");
		}
		$exportDataStmt->closeCursor();
		$exportDataStmt = null;

		sleep(1);
	}
	fclose($fhnd);
//	if ($exportData) {
//		$dumpCommand = "mariadb-dump --quick -u$dbUser -p$dbPassword -h$dbHost -P$dbPort $dbName $table > $fullExportFilePath";
//	}else{
//		$dumpCommand = "mariadb-dump --quick -u$dbUser -p$dbPassword -h$dbHost -P$dbPort --no-data $dbName $table > $fullExportFilePath";
//	}
//	exec_advanced($dumpCommand, $debug);

	//remove the exported file
	if (file_exists($fullExportFilePath)) {
		//Add the file to the archive
		if ($configArray['System']['operatingSystem'] != 'windows') {
			exec_advanced("cd $backupDir; tar -rf $backupFile $exportFile", $debug);

			unlink($fullExportFilePath);
		}
	}

}

//zip up the archive
if ($configArray['System']['operatingSystem'] != 'windows') {
	exec_advanced("gzip $backupFile", $debug);
}

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