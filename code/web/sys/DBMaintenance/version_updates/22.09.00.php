<?php
/** @noinspection PhpUnused */
function getUpdates22_09_00() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'move_aspen_lida_settings' => [
			'title' => 'Move Aspen LiDA settings to own section',
			'description' => 'Moves quick searches, general app config, branded app config, and adds notification settings',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS aspen_lida_notification_setting (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					name VARCHAR(50) UNIQUE NOT NULL,
					sendTo TINYINT(1) DEFAULT 0,
					notifySavedSearch TINYINT(1) DEFAULT 0
				) ENGINE INNODB',
				'CREATE TABLE IF NOT EXISTS aspen_lida_quick_search_setting (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					name VARCHAR(50) UNIQUE NOT NULL
				) ENGINE INNODB',
				'CREATE TABLE IF NOT EXISTS aspen_lida_general_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					name VARCHAR(50) UNIQUE,
					enableAccess TINYINT(1) DEFAULT 0,
					releaseChannel TINYINT(1) DEFAULT 0
				) ENGINE INNODB',
				"ALTER TABLE library ADD COLUMN lidaNotificationSettingId INT(11) DEFAULT -1",
				"ALTER TABLE library ADD COLUMN lidaQuickSearchId INT(11) DEFAULT -1",
				"ALTER TABLE location ADD COLUMN lidaGeneralSettingId INT(11) DEFAULT -1",
				"ALTER TABLE aspen_lida_quick_searches ADD COLUMN quickSearchSettingId INT(11) DEFAULT -1",
				"ALTER TABLE aspen_lida_settings RENAME TO aspen_lida_branded_settings",
			]
		], //move_aspen_lida_settings
		'move_library_quick_searches' => [
			'title' => 'Move library quick searches',
			'description' => 'Preserve previously setup quick searches to new admin area',
			'continueOnError' => true,
			'sql' => [
				'moveLibraryQuickSearchesToSettings'
			]
		], //move_library_quick_searches
		'move_location_app_settings' => [
			'title' => 'Move location app settings',
			'description' => 'Preserve previous settings for the app to new admin area',
			'continueOnError' => true,
			'sql' => [
				'moveLocationAppSettings'
			]
		], //move_location_app_settings
	];
}

/** @noinspection PhpUnused */
function moveLibraryQuickSearchesToSettings(/** @noinspection PhpUnusedParameterInspection */ &$update) {
	global $aspen_db;

	$oldQuickSearchSQL = "SELECT libraryId, weight, label, searchTerm FROM aspen_lida_quick_searches WHERE quickSearchSettingId = -1";
	$oldQuickSearchRS = $aspen_db->query($oldQuickSearchSQL, PDO::FETCH_ASSOC);
	$oldQuickSearchRow = $oldQuickSearchRS->fetch();

	require_once ROOT_DIR . '/sys/AspenLiDA/QuickSearchSetting.php';
	require_once ROOT_DIR . '/sys/AspenLiDA/QuickSearch.php';
	while($oldQuickSearchRow != null) {
		$library = new Library();
		$library->libraryId = $oldQuickSearchRow['libraryId'];
		if($library->find(true)) {
			$quickSearchSettingId = "-1";
			$quickSearchSetting = new QuickSearchSetting();
			$quickSearchSetting->name = $library->displayName . " - Quick Searches";
			if($quickSearchSetting->insert()) {
				$quickSearchSettingId = $quickSearchSetting->id;
			}

			$quickSearch = new QuickSearch();
			$quickSearch->quickSearchSettingId = "-1";
			$quickSearch->libraryId = $library->libraryId;
			$quickSearch->find();
			while($quickSearch->fetch()) {
				$quickSearch->quickSearchSettingId = $quickSearchSettingId;
				$quickSearch->update();
			}

			$library->lidaQuickSearchId = $quickSearchSettingId;
			$library->update();
		}

		$oldQuickSearchRow = $oldQuickSearchRS->fetch();
	}
}

/** @noinspection PhpUnused */
function moveLocationAppSettings(/** @noinspection PhpUnusedParameterInspection */ &$update) {
	global $aspen_db;

	$oldLocationSettingsSQL = "SELECT locationId, displayName, enableAppAccess, appReleaseChannel FROM location WHERE lidaGeneralSettingId = -1";
	$oldLocationSettingsRS = $aspen_db->query($oldLocationSettingsSQL, PDO::FETCH_ASSOC);
	$oldLocationSettingsRow = $oldLocationSettingsRS->fetch();

	require_once ROOT_DIR . '/sys/AspenLiDA/AppSetting.php';
	while($oldLocationSettingsRow != null) {
		$appSettingId = "-1";
		$appSetting = new AppSetting();
		$appSetting->enableAccess = $oldLocationSettingsRow['enableAppAccess'];
		$appSetting->releaseChannel = $oldLocationSettingsRow['appReleaseChannel'];
		if($appSetting->find(true)) {
			$appSettingId = $appSetting->id;
		} else {
			$appSetting->name = $oldLocationSettingsRow['displayName'] . " - App Settings";
			if($appSetting->insert()) {
				$appSettingId = $appSetting->id;
			}
		}

		$location = new Location();
		$location->locationId = $oldLocationSettingsRow['locationId'];
		if($location->find(true)) {
			$location->lidaGeneralSettingId = $appSettingId;
			$location->update();
		}

		$oldLocationSettingsRow = $oldLocationSettingsRS->fetch();
	}
}