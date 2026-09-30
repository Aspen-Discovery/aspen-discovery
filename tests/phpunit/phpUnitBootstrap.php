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

//Remove all existing foreign constraints
$foreignConstraintResults = $aspen_db->query("SELECT CONSTRAINT_NAME, TABLE_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_TYPE = 'FOREIGN KEY' AND TABLE_SCHEMA = '$dbName'");
$allForeignConstraints = $foreignConstraintResults->fetchAll(PDO::FETCH_ASSOC);
$aspen_db->exec("SET foreign_key_checks = 0;");
foreach ($allForeignConstraints as $foreignConstraint) {
	$tableName = $foreignConstraint['TABLE_NAME'];
	$constraintName = $foreignConstraint['CONSTRAINT_NAME'];

	// Construct the DROP FOREIGN KEY statement
	$dropSql = "ALTER TABLE $tableName DROP FOREIGN KEY $constraintName";
	$aspen_db->exec($dropSql);
}
$aspen_db->exec("SET foreign_key_checks = 1;");

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

function applyPendingDatabaseUpdates() : void {
	require_once ROOT_DIR . '/services/API/SystemAPI.php';
	$systemAPI = new SystemAPI();
	$pendingUpdates = $systemAPI->getPendingDatabaseUpdates();
	$numFailed = 0;
	foreach (array_keys($pendingUpdates) as $updateKey) {
		$updateResult = $systemAPI->runDatabaseUpdate($pendingUpdates, $updateKey);
		if ($updateResult['success']) {
			continue;
		}
		$numFailed++;
		$updateMessage = strip_tags(str_replace('<br/>', "\n", $updateResult['message']));
		echo "Database update $updateKey failed: $updateMessage\n";
	}
	$numApplied = count($pendingUpdates) - $numFailed;
	echo "Applied $numApplied pending database updates, $numFailed failed\n";
}

$runPendingDatabaseUpdates = !empty(getenv('ASPEN_TESTS_RUN_DB_UPDATES'));
if ($runPendingDatabaseUpdates) {
	applyPendingDatabaseUpdates();
}

echo "Aspen Discovery PHPUnit tests starting\n";
