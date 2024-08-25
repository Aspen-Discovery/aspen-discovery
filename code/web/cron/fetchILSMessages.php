<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

require_once ROOT_DIR . '/CatalogFactory.php';

global $library;
$accountProfile = $library->getAccountProfile();

// Temporary disabling to re-evaluate how to handle large server queries
/*if ($accountProfile) {
	$catalogDriver = trim($accountProfile->driver);
	if (!empty($catalogDriver)) {
		$catalog = CatalogFactory::getCatalogConnectionInstance($catalogDriver, $accountProfile);
		try {
			$catalog->updateMessageQueue();
		} catch (PDOException $e) {
			echo("Could not update message queue for library $library->libraryId.");
		}
	}
}*/

global $aspen_db;
$aspen_db = null;

die();