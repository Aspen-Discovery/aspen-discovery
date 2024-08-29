<?php

// Initialize database
$aspenAdminPassword = getenv('ASPEN_ADMIN_PASSWORD');
$supportingCompany = getenv('SUPPORTING_COMPANY') ?? 'ByWater Solutions';
$databaseHost = getenv('DATABASE_HOST') ?? 'localhost';
$databasePort = getenv('DATABASE_PORT') ?? 3306;
$databaseName = getenv('DATABASE_NAME');
$databaseUser = getenv('DATABASE_USER');
$databasePassword = getenv('DATABASE_PASSWORD');
$databaseDsn = "mysql:host=$databaseHost;port=$databasePort;dbname=$databaseName";

$mysqlConnectionCommand = "mariadb -u$databaseUser -p$databasePassword -h$databaseHost";
	$databasePort != "3306" ?? $mysqlConnectionCommand .= " --port=$databasePort";

//Check if aspen database has already been initialized
try {
	$statement = 'SELECT libraryId FROM library LIMIT 1;';
	$aspenDatabase = new PDO($databaseDsn, $databaseUser, $databasePassword);
	$updateUserStmt = $aspenDatabase->prepare($statement);
} catch (PDOException $e) {
	echo "%   ERROR MESSAGE : " . $e->getMessage() . "\n";
	echo "%   IN : " . $e->getFile() . ":" . $e->getLine() . "\n";
	die(1);
}

try {
	$updateUserStmt->execute();
	echo "%    --> Aspen database has already been initialized!\n";
	die(0);
} catch (PDOException $e) {
	# Aspen database is still empty
}

//Load default database
$aspenDir = '/usr/local/aspen-discovery/';
echo "%    --> Loading default database\n";
exec("$mysqlConnectionCommand $databaseName < $aspenDir/install/aspen.sql", $output, $errorCode);
if ($errorCode != 0) {
	echo "%   ERROR: Database '{$databaseName}' could not be loaded\n";
	die(1);
}
echo "%    --> Default database has been successfully loaded\n";

// Connect to the database
$aspenDatabase = new PDO($databaseDsn, $databaseUser, $databasePassword);
$updateUserStmt = $aspenDatabase->prepare("UPDATE user set cat_password=" . $aspenDatabase->quote($aspenAdminPassword) . ", password=" . $aspenDatabase->quote($aspenAdminPassword) . " where username = 'aspen_admin'");
$updateUserStmt->execute();

// Assign supportingCompany in the database

$postSupportingCompanyStmt = $aspenDatabase->prepare("UPDATE system_variables set supportingCompany=" . $aspenDatabase->quote($supportingCompany));
$postSupportingCompanyStmt->execute();
