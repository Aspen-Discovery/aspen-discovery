<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

global $library;
$accountProfile = $library->getAccountProfile();

if ($accountProfile) {
	$catalogDriver = trim($accountProfile->driver);
	if (!empty($catalogDriver)) {
		$catalog = CatalogFactory::getCatalogConnectionInstance($catalogDriver, $accountProfile);
		try {
			$catalog->updateMessageQueue();
		} catch (PDOException $e) {
			echo("Could not update message queue for library $library->libraryId.");
		}
	}
}

global $aspen_db;
$aspen_db = null;

die();