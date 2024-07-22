<?php
$_SERVER['aspen_server'] = 'unit_tests.localhost';

require_once '../../code/web/bootstrap.php';
//Load a clean database at the start of unit testing?
global $configArray;
global $aspen_db;

$dbUser = $configArray['Database']['database_user'];
$dbPassword = $configArray['Database']['database_password'];
$dbName = $configArray['Database']['database_aspen_dbname'];
$dbHost = $configArray['Database']['database_aspen_host'];
$dbPort = $configArray['Database']['database_aspen_dbport'];

$curDir = __DIR__;
$baseAspenSQL = "$curDir/../../install/aspen.sql";

//Remove all existing database tables
$result = $aspen_db->query("SELECT TABLE_NAME FROM information_schema.tables where TABLE_SCHEMA = '$dbName'");
$allTables = $result->fetchAll(PDO::FETCH_ASSOC);
foreach ($allTables as $table) {
	$aspen_db->exec("DROP TABLE {$table['TABLE_NAME']}");
}

//Import blank database
$importCommand = "mysql -u$dbUser -p$dbPassword -h$dbHost -P$dbPort $dbName < $baseAspenSQL";
exec($importCommand);

////Import unit test specific data
$unitTestsSQL = "$curDir/../../tests/unit_tests.sql";
$importCommand = "mysql -u$dbUser -p$dbPassword -h$dbHost -P$dbPort $dbName < $unitTestsSQL";
$results = [];
exec($importCommand, $results);

//Make sure solr is running?


require_once '../../code/web/bootstrap_aspen.php';

//Setup interface
global $interface;
$interface = new UInterface();

echo "Aspen Discovery PHPUnit tests starting\n";