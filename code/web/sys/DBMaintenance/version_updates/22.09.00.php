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
		'vdx_hold_groups' => [
			'title' => 'VDX Hold Group setup',
			'description' => 'Add the ability to add VDX Hold Groups to the site',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS vdx_hold_groups(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							name VARCHAR(50) NOT NULL UNIQUE
						) ENGINE = INNODB;',
				'CREATE TABLE vdx_hold_group_location (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							vdxHoldGroupId INT,
							locationId INT,
							UNIQUE INDEX vdxHoldGroupLocation(vdxHoldGroupId, locationId)
						) ENGINE = INNODB;',
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES
							('ILL Integration', 'Administer VDX Hold Groups', '', 15, 'Allows the user to define Hold Groups for Interlibrary Loans with VDX.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer VDX Hold Groups'))",
			]
		], //vdx_hold_groups
		'vdx_settings' => [
			'title' => 'VDX Settings setup',
			'description' => 'Add the ability to add VDX Settings to the site',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS vdx_settings(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							name VARCHAR(50) NOT NULL UNIQUE,
							baseUrl VARCHAR(255) NOT NULL,
							submissionEmailAddress VARCHAR(255) NOT NULL
						) ENGINE = INNODB;',
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES
							('ILL Integration', 'Administer VDX Settings', '', 10, 'Allows the user to define settings for Interlibrary Loans with VDX.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer VDX Settings'))",
			]
		], //vdx_settings
		'vdx_forms' => [
			'title' => 'VDX From setup',
			'description' => 'Add the ability to configure VDX forms for locations',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS vdx_form(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							name VARCHAR(50) NOT NULL UNIQUE,
							introText TEXT,
							showAuthor TINYINT(1) DEFAULT 0,
							showPublisher TINYINT(1) DEFAULT 0,
							showIsbn TINYINT(1) DEFAULT 0,
							showAcceptFee TINYINT(1) DEFAULT 0,
							showMaximumFee TINYINT(1) DEFAULT 0,
							feeInformationText TEXT,
							showCatalogKey TINYINT(1) DEFAULT 0
						) ENGINE = INNODB;',
				'CREATE TABLE vdx_form_location (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							vdxFormId INT,
							locationId INT,
							UNIQUE INDEX vdxFormLocation(vdxFormId, locationId)
						) ENGINE = INNODB;',
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES
							('ILL Integration', 'Administer All VDX Forms', '', 20, 'Allows the user to define administer all VDX Forms.'), 
							('ILL Integration', 'Administer Library VDX Forms', '', 22, 'Allows the user to define administer VDX Forms for their library.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer All VDX Forms'))",
			]
		], //vdx_forms
		'vdx_requests' => [
			'title' => 'VDX Requests',
			'description' => 'Add the ability to track VDX Requests for a user',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS user_vdx_request(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							userId INT(11),
							datePlaced INT(11),
							title VARCHAR(255),
							author VARCHAR(255),
							publisher VARCHAR(255),
							isbn VARCHAR(20),
							feeAccepted TINYINT(1),
							maximumFeeAmount VARCHAR(10),
							catalogKey VARCHAR(20),
							status VARCHAR(20)
						) ENGINE = INNODB;',
			]
		], //vdx_requests
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
		'create_user_notification_tokens' => [
			'title' => 'Add user notification push tokens',
			'description' => 'Setup table to store user notification push tokens from Expo',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS user_notification_tokens (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					userId INT(11),
					pushToken VARCHAR(500)
				) ENGINE INNODB',
			]
		], //create_user_notification_tokens
		'create_user_notifications' => [
			'title' => 'Add user notification receipts',
			'description' => 'Setup table to store user notification receipts from Expo',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS user_notifications (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					userId INT(11),
					notificationType VARCHAR(75),
					notificationDate INT(11),
					receiptId VARCHAR(500),
					completed TINYINT(1),
					error TINYINT(1),
					message VARCHAR(500)
				) ENGINE INNODB',
			]
		], //create_user_notifications
		'greenhouse_add_accessToken' => [
			'title' => 'Add notificationAccessToken for Greenhouse',
			'description' => 'Add access token for notification api access',
			'sql' => [
				"ALTER TABLE greenhouse_settings ADD COLUMN notificationAccessToken VARCHAR(256) default NULL",
			]
		], //greenhouse_add_accessToken
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