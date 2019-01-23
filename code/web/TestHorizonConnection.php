<?php
ini_set('display_errors', true);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

require_once 'bootstrap.php';

global $configArray;
global $logger;

try{
	if (strcasecmp($configArray['System']['operatingSystem'], 'windows') == 0 ){
		sybase_min_client_severity(11);
		$db = @sybase_connect($configArray['Catalog']['database'] ,
		$configArray['Catalog']['username'],
		$configArray['Catalog']['password']);
	}else{
		$db = mssql_connect($configArray['Catalog']['host'] . ':' . $configArray['Catalog']['port'],
		$configArray['Catalog']['username'],
		$configArray['Catalog']['password']);

		// Select the database
		mssql_select_db($configArray['Catalog']['database']);
	}
	echo("Connected to Horizon Database correctly");
}catch (Exception $e){
	echo("Could not load Horizon database ");
	echo $e;
}
