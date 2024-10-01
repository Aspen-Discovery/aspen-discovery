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
	// only try if we have mydumper
	if (`which mydumper`) {
		if (!file_exists($backupDir)) {
			mkdir($backupDir, 700, true);
  	}
	$dumperDir = "$backupDir/mydumper";
  	if (!file_exists($dumperDir)) {
  		mkdir($dumperDir, 700, true);
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
  	$backupName = "aspen.$serverName.$curDateTime.tar.gz";
  	$backupFile = "$backupDir/$backupName";

  	//Create the export files
  	//TODO: ignore sessions and cached_values tables - mydumper does this with an external file with a list of tables
  	//    
		$dumpCommand = "mydumper --database=$dbName --host=$dbHost --user=$dbUser --password=$dbPassword --outputdir=$dumperDir --rows=500000 --compress --build-empty-files --threads=18 --kill-long-queries --lock-all-tables --compress-protocol";
		/** @noinspection PhpConditionAlreadyCheckedInspection */
		exec_advanced($dumpCommand, $debug);

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		exec_advanced("cd $backupDir; tar -zcvf $backupName $dumperDir", $debug);

  	//Optionally move the file to the Google backup bucket
  	// Load the system settings
  	require_once ROOT_DIR . '/sys/SystemVariables.php';
  	$systemVariables = new SystemVariables();

  	// See if we have a bucket to back up to
  	if ($systemVariables->find(true) && !empty($systemVariables->googleBucket)) {  
			//Perform the backup
	  	$bucketName = $systemVariables->googleBucket;
	  	exec_advanced("gsutil cp $backupFile gs://$bucketName/", $debug);
  	}

  	$aspen_db = null;
  	$configArray = null;
  	die();
	}
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
