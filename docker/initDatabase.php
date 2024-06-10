<?php

if (count($argv) < 2) {
    echo "To initialize a database connection, an .ini file will be necessary as argument.\n";
    die();
} elseif (count($argv) > 2) {
    echo "Too many arguments have been passed. The script just need an .ini file to start\n";
    die();
}

$configFile = parse_ini_file($argv[1], true);

//Initialize database
$aspenAdminPassword = $configFile['Site']['aspen_admin_password'];
$databaseHost = $configFile['Database']['database_host'];
$databasePort = $configFile['Database']['database_port'];
$databaseName = $configFile['Database']['database_aspen_dbname'];
$databaseUser = $configFile['Database']['database_user'];
$databasePassword = $configFile['Database']['database_password'];
$databaseDsn = $configFile['Database']['database_dsn'];

$mysqlConnectionCommand = "mariadb -u$databaseUser -p$databasePassword -h$databaseHost";
$databasePort != "3306" ?? $mysqlConnectionCommand .= " --port=$databasePort";

//Check if aspen database has already been initialized
$statement = 'SELECT libraryId FROM library LIMIT 1;';
$aspenDatabase = new PDO($databaseDsn, $databaseUser, $databasePassword);
$updateUserStmt = $aspenDatabase->prepare($statement);
try {
    $updateUserStmt->execute();
    echo "Aspen database has already been initialized!\n";
    die();
} catch (PDOException $e) {

}

//Load default database
$aspenDir = '/usr/local/aspen-discovery/';
echo "Loading default database.\n";
exec("$mysqlConnectionCommand $databaseName < $aspenDir/install/aspen.sql",$output,$errorCode);
if ($errorCode != 0) {
    echo "The database '{$databaseName}' could not be loaded.\n";
    die();
}
echo "Default database has been successfully loaded.\n";

//Connect to the database
$aspenDatabase = new PDO($databaseDsn, $databaseUser, $databasePassword);
$updateUserStmt = $aspenDatabase->prepare("UPDATE user set cat_password=" . $aspenDatabase->quote($aspenAdminPassword) . ", password=" . $aspenDatabase->quote($aspenAdminPassword) . " where username = 'aspen_admin'");
$updateUserStmt->execute();
