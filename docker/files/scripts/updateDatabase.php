<?php

require_once __DIR__ . '/../logger/DockerLogger.php';
DockerLogger::init('BACKEND');

require_once __DIR__ . '/../database/DatabaseHealth.php';

require_once __DIR__ . '/../../../code/web/bootstrap.php';
require_once __DIR__ . '/../../../code/web/bootstrap_aspen.php';

require_once ROOT_DIR . '/sys/Updates/ScheduledUpdate.php';
require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';

if (file_exists(ROOT_DIR . '/sys/Greenhouse/CompanionSystem.php')) {
	require_once ROOT_DIR . '/sys/Greenhouse/CompanionSystem.php';
}

require_once ROOT_DIR . '/services/API/SystemAPI.php';

global $configArray;
global $serverName;
global $aspen_db;

if (!checkDatabaseConnection($aspen_db) || !isDatabaseInitialized($aspen_db)) {
	DockerLogger::error("Cannot connect to the database to run updates");
	exit(1);
}

DockerLogger::info("Running pending database updates");

$systemAPI = new SystemAPI();
$completedUpdates = $systemAPI->runPendingDatabaseUpdates();

DockerLogger::info("Database updates completed - Success: " . ($completedUpdates['success'] ? 'true' : 'false'));
DockerLogger::info("Update message: " . strip_tags($completedUpdates['message']));

if (!$completedUpdates['success']) {
	DockerLogger::warn("Some database updates failed. See message above for details.");
}

DockerLogger::info("Updating CSS for all themes");
global $interface;
$interface = new UInterface();
$result = $systemAPI->updateCssForAllThemes();
if ($result['success'] != true) {
	DockerLogger::warn("Error updating CSS: " . $result['message']);
} else {
	DockerLogger::info($result['message']);
}
