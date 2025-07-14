<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

require_once ROOT_DIR . '/CatalogFactory.php';

global $library;
//Because this is run from cron, we will loop through all account profiles and update account notifications for
// each one where account notifications are enabled.
$accountProfiles = UserAccount::getAccountProfiles();
foreach ($accountProfiles as $accountProfileInfo) {
	/** @var AccountProfile $accountProfile */
	$accountProfile = $accountProfileInfo['accountProfile'];
	if ($accountProfile->enableFetchingIlsMessages) {
		$ilsNotificationSetting = new ILSNotificationSetting();
		$ilsNotificationSetting->accountProfileId =  $accountProfile->id;
		if ($ilsNotificationSetting->find(true)) {
			$catalogDriver = trim($accountProfile->driver);
			if (!empty($catalogDriver)) {
				$catalog = CatalogFactory::getCatalogConnectionInstance($catalogDriver, $accountProfile);
				try {
					$catalog->updateAccountNotifications($ilsNotificationSetting);
				} catch (PDOException $e) {
					echo("Could not update message queue for library $library->libraryId.");
				}
			}
		}
	}
}

global $aspen_db;
$aspen_db = null;

die();