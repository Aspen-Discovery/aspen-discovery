<?php
require_once __DIR__ . '/../bootstrap.php';

set_time_limit(0);
global $configArray;
global $serverName;

global $aspen_db;

$dbUser = $configArray['Database']['database_user'];
$dbPassword = $configArray['Database']['database_password'];
$dbName = $configArray['Database']['database_aspen_dbname'];

//Create the export files
$listTablesStmt = $aspen_db->query("SHOW TABLES");
$allTables = $listTablesStmt->fetchAll(PDO::FETCH_COLUMN);
$curDateTime = date('ymdHis');
foreach ($allTables as $table){
	$exportFile = "/tmp/$serverName.$curDateTime.$table.sql";
	$createTableStmt = $aspen_db->query("SHOW CREATE TABLE $table");
	$createTableString = $createTableStmt->fetch();
	$dumpCommand = "mysqldump -u$dbUser -p$dbPassword $dbName $table > $exportFile";
	exec($dumpCommand);
}
if (!file_exists("/data/aspen-discovery/$serverName/sql_backup")){
	mkdir("/data/aspen-discovery/$serverName/sql_backup", 700, true);
}

//tar and gzip them
exec("cd /tmp;tar -czf /data/aspen-discovery/$serverName/sql_backup/aspen.$curDateTime.tar.gz $serverName.$curDateTime.*");

//Cleanup the files
foreach ($allTables as $table){
	$exportFile = "/tmp/$serverName.$curDateTime.$table.sql";
	if (file_exists($exportFile)){
		unlink($exportFile);
	}
}