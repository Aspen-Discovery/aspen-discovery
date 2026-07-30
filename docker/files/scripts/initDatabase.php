<?php

require_once __DIR__ . '/../logger/DockerLogger.php';
DockerLogger::init('BACKEND');

// Initialize database
$aspenAdminPassword = getenv('ASPEN_ADMIN_PASSWORD');
$supportingCompany = getenv('SUPPORTING_COMPANY') ?? 'ByWater Solutions';
$databaseHost = getenv('DATABASE_HOST') ?? 'localhost';
$databasePort = getenv('DATABASE_PORT') ?? 3306;
$databaseName = getenv('DATABASE_NAME');
$databaseUser = getenv('DATABASE_USER');
$databasePassword = getenv('DATABASE_PASSWORD');

if (!$databaseName || !$databaseUser || !$databasePassword) {
	DockerLogger::error("Missing required database environment variables");
}

$databaseDsn = "mysql:host=$databaseHost;port=$databasePort;dbname=$databaseName";

$mysqlConnectionCommand = "mariadb -u$databaseUser -p$databasePassword -h$databaseHost";
if ($databasePort != "3306") {
	$mysqlConnectionCommand .= " --port=$databasePort";
}

DockerLogger::info("Checking database connection to: {$databaseHost}:{$databasePort}");

//Check if aspen database has already been initialized
$maxTries = 5;
$tries = 0;
$databaseIsDown = true;

while ($databaseIsDown) {
	try {
		$statement = 'SELECT libraryId FROM library LIMIT 1;';
		$aspenDatabase = new PDO($databaseDsn, $databaseUser, $databasePassword);
		$updateUserStmt = $aspenDatabase->prepare($statement);
		$databaseIsDown = false;
	} catch (PDOException $e) {
		$tries++;
		if ($tries >= $maxTries) {
			DockerLogger::error("Database connection failed after {$maxTries} attempts: " . $e->getMessage());
		}
		DockerLogger::warn("Database not ready, retrying... (attempt {$tries}/{$maxTries})");
		sleep(5);
	}
}

try {
	$updateUserStmt->execute();
	DockerLogger::info("Aspen database has already been initialized");
	exit(0);
} catch (PDOException $e) {
	DockerLogger::info("Database is empty, initializing...");
}

//Load default database
$aspenDir = '/usr/local/aspen-discovery/';
DockerLogger::info("Loading default database schema");
exec("$mysqlConnectionCommand $databaseName < $aspenDir/install/aspen.sql", $output, $errorCode);
if ($errorCode != 0) {
	DockerLogger::error("Database '{$databaseName}' could not be loaded");
}
DockerLogger::info("Default database schema loaded successfully");

// Connect to the database
$aspenDatabase = new PDO($databaseDsn, $databaseUser, $databasePassword);
$updateUserStmt = $aspenDatabase->prepare("UPDATE user set cat_password=" . $aspenDatabase->quote($aspenAdminPassword) . ", password=" . $aspenDatabase->quote($aspenAdminPassword) . " where username = 'aspen_admin'");
$updateUserStmt->execute();

// Assign supportingCompany in the database
$postSupportingCompanyStmt = $aspenDatabase->prepare("UPDATE system_variables set supportingCompany=" . $aspenDatabase->quote($supportingCompany));
$postSupportingCompanyStmt->execute();

DockerLogger::info("Database initialization completed successfully");

// Close connection 
$aspenDatabase = null;
?>
