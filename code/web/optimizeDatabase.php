<?php

require_once 'bootstrap.php';
require_once ROOT_DIR . '/sys/Utils/SwitchDatabase.php';
global $timer;
global $logger;

//This initializes the database
$library = new Library();
$library->find();

ob_start();
echo("<br>Starting to optimize tables<br/>\r\n");
$logger->log('Starting to optimize tables', PEAR_LOG_INFO);
ob_flush();

foreach ($configArray['Database'] as $key => $value){
	if (preg_match('/table_(.*)/', $key, $matches)){
		if ($value =='vufind'){
			SwitchDatabase::switchToVuFind();
		}else{
			SwitchDatabase::switchToEcontent();
		}
		$tableName = $matches[1];

	}
}

$logger->log('Finished optimizing tables', PEAR_LOG_INFO);

function optimizeTable($tableName){
	global $logger;
	set_time_limit(1000);
	echo("Optimizing $tableName<br/>\r\n");
	mysql_query("OPTIMIZE TABLE $tableName;");
	$logger->log('Optimized table: ' . $tableName, PEAR_LOG_INFO);
}