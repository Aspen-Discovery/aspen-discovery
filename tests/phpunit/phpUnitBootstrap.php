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

//Import blank database
$importCommand = "mysql -u$dbUser -p$dbPassword -h$dbHost -P$dbPort $dbName < $baseAspenSQL";
exec($importCommand);

$unitTestsSQL = "$curDir/../../install/unit_tests.sql";
//Import unit test specific data
$importCommand = "mysql -u$dbUser -p$dbPassword -h$dbHost -P$dbPort $dbName < $unitTestsSQL";
exec($importCommand);

//Make sure solr is running?


require_once '../../code/web/bootstrap_aspen.php';

//Setup interface
global $interface;
$interface = new UInterface();

echo "Aspen Discovery PHPUnit tests starting\n";