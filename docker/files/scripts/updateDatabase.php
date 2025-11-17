<?php

require_once __DIR__ . '/../logger/DockerLogger.php';
DockerLogger::init('BACKEND');

require_once __DIR__ . '/../database/DatabaseHealth.php';

// Set server name
$_SERVER['SERVER_NAME'] = getenv('SITE_NAME');

require_once __DIR__ . '/../../../code/web/bootstrap.php';
require_once __DIR__ . '/../../../code/web/bootstrap_aspen.php';

require_once ROOT_DIR . '/sys/Updates/ScheduledUpdate.php';
require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';

if (file_exists(ROOT_DIR . '/sys/Greenhouse/CompanionSystem.php')) {
	require_once ROOT_DIR . '/sys/Greenhouse/CompanionSystem.php';
}

global $configArray;
global $serverName;

DockerLogger::info("Running pending database updates");

# Run Updates
$completedUpdates = runPendingDatabaseUpdates();
DockerLogger::info("Database updates completed - Success: " . $completedUpdates['success']);
DockerLogger::info("Update message: " . $completedUpdates['message']);

if (isset($completedUpdates['errors']) && $completedUpdates['errors']){
	DockerLogger::warn("Database update errors found:");
	foreach ($completedUpdates['errors'] as $errorArray) {
		foreach($errorArray as $updateName => $error){
			if(empty($error)){
				continue;
			} else{
				DockerLogger::warn("Update '{$updateName}': {$error}");
			}
		}
	}
}

# Update CSS
DockerLogger::info("Updating CSS for all themes");
$result = updateCssForAllThemes();
if ($result['success'] != true) {
	DockerLogger::warn("Error updating CSS: " . $result['message']);
} else {
	DockerLogger::info($result['message']);
}


function updateCssForAllThemes() : array {

	global $interface;
	$interface = new UInterface();

	Theme::updateCssForAllThemes();
	return [
		'success' => true,
		'message' => "Updated CSS for All Themes",
	];
}


function getDatabaseUpdates(): array {
	require_once ROOT_DIR . '/sys/DBMaintenance/library_location_updates.php';
	$library_location_updates = getLibraryLocationUpdates();
	require_once ROOT_DIR . '/sys/DBMaintenance/summon_updates.php';
	$summonUpdates = getSummonUpdates();
	require_once ROOT_DIR . '/sys/DBMaintenance/cloud_library_updates.php';
	$cloudLibraryUpdates = getCloudLibraryUpdates();
	require_once ROOT_DIR . '/sys/DBMaintenance/grapes_web_builder_updates.php';
	$grapesWebBuilderUpdates = getGrapesWebBuilderUpdates();
	require_once ROOT_DIR . '/sys/DBMaintenance/heycentric_updates.php';
	$heycentricUpdates = getHeyCentricUpdates();
	require_once ROOT_DIR . '/sys/DBMaintenance/community_engagement_updates.php';
	$communityEngagementUpdates = getCommunityEngagementUpdates();
	require_once ROOT_DIR . '/sys/DBMaintenance/talpa_updates.php';
	$talpaUpdates = getTalpaUpdates();
	
	$baseUpdates = array_merge($library_location_updates, $summonUpdates, $cloudLibraryUpdates, $grapesWebBuilderUpdates, $communityEngagementUpdates, $talpaUpdates, $heycentricUpdates);
	//Get version updates
	require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
	$versionUpdates = scandir(ROOT_DIR . '/sys/DBMaintenance/version_updates', SCANDIR_SORT_ASCENDING);
	foreach ($versionUpdates as $updateFile) {
		if (is_file(ROOT_DIR . '/sys/DBMaintenance/version_updates/' . $updateFile)) {
			if (StringUtils::endsWith($updateFile, '.php')) {
				include_once ROOT_DIR . "/sys/DBMaintenance/version_updates/$updateFile";
				$version = substr($updateFile, 0, strrpos($updateFile, '.'));
				$updateFunction = 'getUpdates' . str_replace('.', '_', $version);
				$updates = $updateFunction();
				$baseUpdates = array_merge($baseUpdates, $updates);
			}
		}
	}
	return $baseUpdates;
}

function getPendingDatabaseUpdates() : array {
	$availableUpdates = getDatabaseUpdates();
	$availableUpdates = pruneCompletedUpdates($availableUpdates);
	$pendingUpdates = [];
	foreach ($availableUpdates as $key => $update) {
		if (!$update['alreadyRun']) {
			$pendingUpdates[$key] = $update;
		}
	}
	return $pendingUpdates;
}

function pruneCompletedUpdates($availableUpdates) {

	global $aspen_db;
	foreach ($availableUpdates as $key => $update) {    
		$update['alreadyRun'] = false;
		$result = $aspen_db->query("SELECT * from db_update where update_key = " . $aspen_db->quote($key));
		if ($result != false && $result->rowCount() > 0) {
			$update['alreadyRun'] = true;
		}
		$availableUpdates[$key] = $update;
	}
	return $availableUpdates;
}

function runPendingDatabaseUpdates() {

	# Check database connection
	global $aspen_db;
	if (!checkDatabaseConnection($aspen_db) || !isDatabaseInitialized($aspen_db)) {
		DockerLogger::error("Cannot connect to the database to run updates)");
		exit(1);
	}
	
	$pendingUpdates = getPendingDatabaseUpdates();
	$updates = 0;
	$failedUpdates = 0;
	$errors = [];
	foreach ($pendingUpdates as $updateName => $updateProperties) {
		$updates++;
		$response = runDatabaseUpdate($pendingUpdates,$updateName);
		if ($response['success'] != "true") {
			$failedUpdates++;
			$errors[] = $response['errors'];
		}
	}

	// Make sure full nightly index is set to run after completing DB updates
	prepareNightlyFullIndexing();

	if ($failedUpdates == 0) {
		return [
			'success' => "true",
			'message' => $updates . " updates ran successfully",
			'errors' => false
		];
	} else {
		return [
			'success' => "false",
			'message' => $updates - $failedUpdates . " of " . $updates . " updates ran successfully",
			'errors' => $errors
		];
	}
}

# ================================================================================

function prepareNightlyFullIndexing(): void {
	require_once ROOT_DIR . '/sys/SystemVariables.php';
	$systemVariables = SystemVariables::getSystemVariables();
	if ($systemVariables->find(true)) {
		if($systemVariables->runNightlyFullIndex == 0) {
			$systemVariables->runNightlyFullIndex = 1;
			$systemVariables->update();
		}
	}
}

function runDatabaseUpdate(&$availableUpdates, $updateName): array {
	if ($availableUpdates == null) {
		$availableUpdates = getDatabaseUpdates();
	}

	if (!isset($availableUpdates[$updateName])) {
		return [
			'success' => false,
			'message' => 'Could not find update to run',
			'errors'  => []
		];
	}

	$updateToRun = $availableUpdates[$updateName];
	$sqlErrors   = [];
	$updateOk    = true;

	if (!isset($updateToRun['sql'])) {
		print($updateName . " doesn't have SQL statements to execute\n");
		return [
			'success' => false,
			'message' => 'No SQL statements',
			'errors'  => []
		];
	}

	foreach ($updateToRun['sql'] as $sql) {
		if (function_exists($sql)) {
			$sql($updateToRun);
		} else {
			$queryResult = runSQLStatement($updateToRun, $sql);
			$updateOk = $updateOk && $queryResult['success'];
			if (!$queryResult['success']) {
				$sqlErrors[$updateName] = $queryResult['status'] ?? 'Unknown error';
			}
		}
	}


	markUpdateAsRun($updateName);


	$availableUpdates[$updateName] = $updateToRun;

	return [
		'success' => $updateOk,
		'message' => empty($updateToRun['status']) ? 'Status not returned' : $updateToRun['status'],
		'errors'  => $sqlErrors
	];
}

function runSQLStatement(&$update, $sql): array {
	global $aspen_db;
	set_time_limit(500);

	$red    = "\033[31m";
	$yellow = "\033[33m";
	$reset  = "\033[0m";
	$bold   = "\033[1m";

	$result = [
		'success' => false,
		'status'  => '',
	];

	try {
		$aspen_db->query($sql);

		if (!isset($update['status'])) {
			$update['success'] = true;
			$update['status']  = translate([
				'text'          => 'Update succeeded',
				'isAdminFacing' => true,
			]);
		}

		$result['success'] = $update['success'];
		$result['status']  = $update['status'];

	} catch (PDOException $e) {
		$update['success'] = false;

		if (isset($update['continueOnError']) && $update['continueOnError']) {
			if (!isset($update['status'])) {
				$update['status'] = '';
			}
			$update['status'] .= '<br/><strong>' . $sql . '</strong><br/>Warning: ' . $e;
			$result['status'] = "[{$yellow}WARNING{$reset}] {$bold}$sql{$reset} : " . $e->getMessage() . PHP_EOL;
		} else {
			$update['status'] = '<br/><strong>' . $sql . '</strong><br/>Update failed: ' . $e;
			$result['status'] = "[{$red}ERROR{$reset}] {$bold}$sql{$reset} : " . $e->getMessage() . PHP_EOL;
		}

		$result['success'] = $update['success'];
	}

	return $result;
}


function markUpdateAsRun($update_key) {
	global $aspen_db;
	$result = $aspen_db->query("SELECT * from db_update where update_key = " . $aspen_db->quote($update_key));
	if ($result->rowCount() != false) {
		//Update the existing value
		$aspen_db->query("UPDATE db_update SET date_run = CURRENT_TIMESTAMP WHERE update_key = " . $aspen_db->quote($update_key));
	} else {
		$aspen_db->query("INSERT INTO db_update (update_key) VALUES (" . $aspen_db->quote($update_key) . ")");
	}
}
?>
