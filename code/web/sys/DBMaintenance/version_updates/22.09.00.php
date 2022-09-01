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
        'add_library_sso_config_options' => [
			'title' => 'SSO - Library config options',
			'description' => 'Allow SSO configuration options to be specified',
			'sql' => [
				"ALTER TABLE library ADD column ssoName VARCHAR(255)",
				"ALTER TABLE library ADD column ssoXmlUrl VARCHAR(255)",
				"ALTER TABLE library ADD column ssoUniqueAttribute VARCHAR(255)",
				"ALTER TABLE library ADD column ssoMetadataFilename VARCHAR(255)",
				"ALTER TABLE library ADD column ssoIdAttr VARCHAR(255)",
				"ALTER TABLE library ADD column ssoUsernameAttr VARCHAR(255)",
				"ALTER TABLE library ADD column ssoFirstnameAttr VARCHAR(255)",
				"ALTER TABLE library ADD column ssoLastnameAttr VARCHAR(255)",
				"ALTER TABLE library ADD column ssoEmailAttr VARCHAR(255)",
				"ALTER TABLE library ADD column ssoDisplayNameAttr VARCHAR(255)",
				"ALTER TABLE library ADD column ssoPhoneAttr VARCHAR(255)",
				"ALTER TABLE library ADD column ssoPatronTypeAttr VARCHAR(255)",
				"ALTER TABLE library ADD column ssoPatronTypeFallback VARCHAR(255)",
				"ALTER TABLE library ADD column ssoAddressAttr VARCHAR(255)",
				"ALTER TABLE library ADD column ssoCityAttr VARCHAR(255)",
				"ALTER TABLE library ADD column ssoLibraryIdAttr VARCHAR(255)",
				"ALTER TABLE library ADD column ssoLibraryIdFallback VARCHAR(255)",
				"ALTER TABLE library ADD column ssoCategoryIdAttr VARCHAR(255)",
				"ALTER TABLE library ADD column ssoCategoryIdFallback VARCHAR(255)"
            ]
		], //add_library_sso_config_options
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
		'vdx_requests_2' => [
			'title' => 'VDX Requests 2',
			'description' => 'Add additional fields for vdx requests',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE user_vdx_request ADD COLUMN note text',
				'ALTER TABLE user_vdx_request ADD COLUMN pickupLocation VARCHAR(75)',
			]
		], //vdx_requests_2
		'vdx_request_id' => [
			'title' => 'VDX Request IDs',
			'description' => 'Add vdxid to requests',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE user_vdx_request ADD COLUMN vdxId INT',
			]
		], //vdx_request_id
		'vdx_setting_updates' => [
			'title' => 'VDX Setting Updates',
			'description' => 'Add additional information to be sent with the email',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE vdx_settings ADD COLUMN patronKey VARCHAR(50)',
				'ALTER TABLE vdx_settings ADD COLUMN reqVerifySource VARCHAR(50)',
				'ALTER TABLE location ADD COLUMN vdxLocation VARCHAR(50)',
			]
		], //vdx_setting_updates
		'vdx_form_updates_locations' => [
			'title' => 'VDX Form Location Updates',
			'description' => 'Update linking forms with locations',
			'continueOnError' => true,
			'sql' => [
				'DROP TABLE vdx_form_location',
				'ALTER TABLE location ADD COLUMN vdxFormId INT(11)',
			]
		], //vdx_form_updates_locations
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
		'add_pushToken_user_notifications' => [
			'title' => 'Add pushToken column to user_notifications table',
			'description' => 'Add pushToken column to user_notifications table',
			'sql' => [
				"ALTER TABLE user_notifications ADD COLUMN pushToken VARCHAR(500) default NULL",
			]
		], //add_pushToken_user_notifications
		'notifications_report_permissions' => [
			'title' => 'Add permissions for Notifications report',
			'description' => 'Add permissions for Notifications report',
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Aspen LiDA', 'View Notifications Reports', '', 6, 'Controls if the user can view the Notifications Report.</em>')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='View Notifications Reports'))",
				"UPDATE permissions set sectionName = 'Aspen LiDA' where name = 'Administer Aspen LiDA Settings'"
			]
		], //notifications_report_permissions
		'add_device_notification_tokens' => [
			'title' => 'Add deviceModel to user_notification_tokens',
			'description' => 'Add deviceModel to user_notification_tokens',
			'sql' => [
				"ALTER TABLE user_notification_tokens ADD COLUMN deviceModel VARCHAR(75) default NULL",
			]
		], //add_device_notification_tokens
        'change_default_formatSource_KohaOnly' => [
            'title' => 'Change default format source to "Item Record" for Koha libraries',
            'description' => 'Changes the default format source to "Item Record" for Koha libraries',
            'sql' => [
                "UPDATE indexing_profiles SET formatSource = 'item' WHERE catalogDriver = 'Koha'",
            ]
        ], //change_default_formatSource_KohaOnly
		'add_user_not_interested_index' => [
			'title' => 'Add index for user not interested',
			'description' => 'Add index for user not interested',
			'sql' => [
				"alter table user_not_interested add index groupedRecordPermanentId(groupedRecordPermanentId, userId)",
			]
		], //add_user_not_interested_index
		'add_additional_format_pickup_options' => [
			'title' => 'Add additional format pickup options',
			'description' => 'Add index for user not interested',
			'sql' => [
				"alter table format_map_values CHANGE COLUMN mustPickupAtHoldingBranch pickupAt TINYINT(1) DEFAULT 0",
			]
		], //add_additional_format_pickup_options

		//mark
		'symphony_self_registration_profile' => [
			'title' => 'Add Self Registration Profile for Symphony',
			'description' => 'Add Self Registration Profile for Symphony',
			'sql' => [
				"alter table library ADD COLUMN selfRegistrationUserProfile VARCHAR(20) DEFAULT 'SELFREG'",
			]
		], //symphony_self_registration_profile

		//kirstien
		'aci_speedpay_settings' => [
			'title' => 'Add settings for ACI Speedpay',
			'description' => 'Add settings for ACI Speedpay integration',
			'continueOnError' => true,
			'sql' => array(
				"CREATE TABLE IF NOT EXISTS aci_speedpay_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					name VARCHAR(50) UNIQUE,
					sandboxMode TINYINT(1) DEFAULT 0,
					clientId VARCHAR(100),
					clientSecret VARCHAR(100),
					apiAuthKey VARCHAR(100),
					billerId VARCHAR(100),
					billerAccountId VARCHAR(100)
				) ENGINE INNODB",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('eCommerce', 'Administer ACI Speedpay', '', 10, 'Controls if the user can change ACI Speedpay settings. <em>This has potential security and cost implications.</em>')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer ACI Speedpay'))",
				"ALTER TABLE library ADD COLUMN aciSpeedpaySettingId INT(11) DEFAULT -1"
			),
		], //aci_speedpay_settings
		'add_aci_token_payment' => [
			'title' => 'Add aciToken to user_payments',
			'description' => 'Add aciToken to user_payments',
			'sql' => [
				"ALTER TABLE user_payments ADD COLUMN aciToken VARCHAR(255) default NULL",
			]
		], //add_aci_token_payment

		//kodi

		//other
		'hide_subject_facet_permission' => [
			'title' => 'Add permission for Hide Subject Facets',
			'description' => 'Add permission for Hide Subject Facets',
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Cataloging & eContent', 'Hide Subject Facets', '', 85, 'Controls if the user can hide subject facets.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Hide Subject Facets'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='cataloging'), (SELECT id from permissions where name='Hide Subject Facets'))"
			]
		], // hide_subject_facets_permission
		'hide_subject_facets' => [
			'title' => 'Add subjects to exclude from subject facet',
			'description' => 'Add subjects to exclude from subject, era, genre, region, and topic facets',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS hide_subject_facets (
                            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            subjectTerm VARCHAR(512) NOT NULL UNIQUE,
                            subjectNormalized VARCHAR(512) NOT NULL UNIQUE,
                            dateAdded INT(11)
                        ) ENGINE INNODB',
			],
		], // hide_subject_facets
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
