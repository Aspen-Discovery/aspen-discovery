<?php
/** @noinspection PhpUnused */
function getUpdates23_03_00(): array {
	$curTime = time();
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'continueOnError' => false,
			'sql' => [
				''
			]
		], //sample*/

		//mark

		//kirstien
		'add_ldap_to_sso' => [
			'title' => 'Add LDAP configuration to SSO Settings',
			'description' => 'Adds initial LDAP configuration options for single sign-on settings',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE sso_setting ADD COLUMN ldapHosts VARCHAR(500) default NULL',
				'ALTER TABLE sso_setting ADD COLUMN ldapUsername VARCHAR(75) default NULL',
				'ALTER TABLE sso_setting ADD COLUMN ldapPassword VARCHAR(75) default NULL',
				'ALTER TABLE sso_setting ADD COLUMN ldapBaseDN VARCHAR(500) default NULL',
				'ALTER TABLE sso_setting ADD COLUMN ldapIdAttr VARCHAR(75) default NULL',
				'ALTER TABLE sso_setting ADD COLUMN ldapOrgUnit VARCHAR(225) default NULL'
			]
		],
		//add_ldap_to_sso
		'add_ldap_label' => [
			'title' => 'Add LDAP Label to SSO Settings',
			'description' => 'Add field to give LDAP service a user-facing name for single sign-on settings',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE sso_setting ADD COLUMN ldapLabel VARCHAR(75) default NULL',
			]
		],
		//add_ldap_label
		'add_account_profile_library_settings' => [
			'title' => 'Add account profile to library settings',
			'description' => 'Add account profile to library settings, then run script to update value to existing ils profile',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library ADD COLUMN accountProfileId INT(10) default -1',
				'updateAccountProfileInLibrarySettings',
			]
		],
		//add_account_profile_library_settings
		'add_sso_settings_account_profile' => [
			'title' => 'Add SSO settings to account profile',
			'description' => 'Add column to store assigned single sign-on settings in account profile',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE account_profiles ADD COLUMN ssoSettingId TINYINT(11) default -1',
			]
		],
		//add_sso_settings_account_profile
		'add_fallback_sso_mapping' => [
			'title' => 'Add fallback column to SSO Mapping',
			'description' => 'Add column to store fallback value for SSO user data mapping table',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE sso_mapping ADD COLUMN fallbackValue VARCHAR(255) default NULL',
			]
		],
		//add_fallback_sso_mapping
		'add_sso_account_profiles' => [
			'title' => 'Modify authenticationMethod in Account Profiles',
			'description' => 'Modify enum authenticationMethod to include sso option in Account Profiles',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE account_profiles MODIFY COLUMN authenticationMethod enum('ils','sip2','db','ldap', 'sso')",
			]
		],
		//add_sso_account_profiles
		'add_sso_auth_only' => [
			'title' => 'Add option to SSO Settings to authenticate only with SSO',
			'description' => 'Add option to SSO settings to authenticate only with SSO and not DB or ILS',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE sso_setting ADD COLUMN ssoAuthOnly TINYINT(1) default 0",
			]
		],
		//add_sso_auth_only
		'migrate_library_sso_settings' => [
			'title' => 'Migrate Library SSO Settings to SSO Settings',
			'description' => 'Migrate any existing SSO Settings in Library Systems to SSO Settings',
			'continueOnError' => false,
			'sql' => [
				'moveLibrarySSOSettings',
			]
		],
		//migrate_library_sso_settings
		'rename_general_settings_table' => [
			'title' => 'Rename LiDA general settings to location settings',
			'description' => 'Rename the aspen_lida_general_settings table to aspen_lida_location_settings',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE aspen_lida_general_settings RENAME TO aspen_lida_location_settings',
				'ALTER TABLE location CHANGE COLUMN lidaGeneralSettingId lidaLocationSettingId INT(11) default -1',
			]
		],
		//rename_general_app_settings
		'add_aspen_lida_general_settings_table' => [
			'title' => 'Add Aspen LiDA General Settings',
			'description' => 'Add table to store general app settings for Aspen LiDA',
			'continueOnError' => false,
			'sql' => [
				"CREATE TABLE IF NOT EXISTS aspen_lida_general_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					name VARCHAR(50) NOT NULL,
					autoRotateCard TINYINT(1) DEFAULT 0
				) ENGINE INNODB",
				'ALTER TABLE library ADD COLUMN lidaGeneralSettingId INT(11) default -1',
			]
		],
		//add_aspen_lida_general_settings
		'add_send_emails_new_materials_request' => [
			'title' => 'Add options for sending emails for new materials requests',
			'description' => 'Add options for sending emails when a new materials request has been created and/or assigned to a staff member',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library ADD COLUMN materialsRequestSendStaffEmailOnNew TINYINT(1) DEFAULT 0',
				'ALTER TABLE library ADD COLUMN materialsRequestSendStaffEmailOnAssign TINYINT(1) DEFAULT 0',
				'ALTER TABLE library ADD COLUMN materialsRequestNewEmail VARCHAR(75) DEFAULT NULL',
				'ALTER TABLE user ADD COLUMN materialsRequestSendEmailOnAssign TINYINT(1) DEFAULT 0',
				'ALTER TABLE materials_request ADD COLUMN createdEmailSent TINYINT(1) DEFAULT 0'
			],
		],
		//add_send_emails_new_materials_request
		'add_staff_settings_to_user' => [
			'title' => 'Migrate staff settings into the user table',
			'description' => 'Migrate staff settings into the user table from old user_staff_settings table',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE user ADD COLUMN materialsRequestReplyToAddress VARCHAR(70) DEFAULT NULL',
				'ALTER TABLE user ADD COLUMN materialsRequestEmailSignature TEXT DEFAULT NULL',
				'moveStaffUserSettings',
			],
		],
		//add_staff_settings_to_user
		'drop_user_staff_settings' => [
			'title' => 'Drop unused staff settings table',
			'description' => 'Drop unused user_staff_settings table',
			'continueOnError' => true,
			'sql' => [
				'DROP TABLE user_staff_settings',
			],
		],
		//drop_user_staff_settings

		//kodi

		//other
	];
}

/** @noinspection PhpUnused */
function updateAccountProfileInLibrarySettings(/** @noinspection PhpUnusedParameterInspection */ &$update) {
	require_once ROOT_DIR . '/sys/Account/AccountProfile.php';
	require_once ROOT_DIR . '/sys/LibraryLocation/Library.php';

	$accountProfileId = -1;
	$accountProfile = new AccountProfile();
	$accountProfile->name = 'ils';
	if($accountProfile->find(true)) {
		$accountProfileId = $accountProfile->id;
	}

	$libraries = [];
	$library = new Library();
	$library->orderBy('isDefault desc');
	$library->orderBy('displayName');
	$library->find();
	while($library->fetch()) {
		$libraries[$library->libraryId] = clone $library;
	}

	if(!empty($libraries)) {
		foreach ($libraries as $librarySettings) {
			$librarySettings->accountProfileId = $accountProfileId;
			$librarySettings->update();
		}
	}
}

/** @noinspection PhpUnused */
function moveLibrarySSOSettings(/** @noinspection PhpUnusedParameterInspection */ &$update) {
	global $aspen_db;
	$oldLibrarySettingsSQL = 'SELECT libraryId, displayName, ssoXmlUrl, ssoUsernameAttr, ssoUniqueAttribute, ssoPhoneAttr, ssoPatronTypeFallback, ssoPatronTypeAttr, ssoName, ssoMetadataFilename, ssoLibraryIdFallback, ssoLibraryIdAttr, ssoLastnameAttr, ssoIdAttr, ssoFirstnameAttr, ssoEntityId, ssoEmailAttr, ssoDisplayNameAttr, ssoCityAttr, ssoCategoryIdFallback, ssoCategoryIdAttr, ssoAddressAttr FROM library WHERE ssoSettingId = -1';
	$oldLibrarySettingsRS = $aspen_db->query($oldLibrarySettingsSQL, PDO::FETCH_ASSOC);
	$oldLibrarySettingsRow = $oldLibrarySettingsRS->fetch();

	require_once ROOT_DIR . '/sys/Authentication/SSOSetting.php';
	while ($oldLibrarySettingsRow != null) {
		$ssoSettingId = '-1';
		$ssoSetting = new SSOSetting();
		$ssoSetting->ssoEntityId = $oldLibrarySettingsRow['ssoEntityId'];
		$ssoSetting->ssoXmlUrl = $oldLibrarySettingsRow['ssoXmlUrl'];
		$ssoSetting->ssoUsernameAttr = $oldLibrarySettingsRow['ssoUsernameAttr'];
		$ssoSetting->ssoUniqueAttribute = $oldLibrarySettingsRow['ssoUniqueAttribute'];
		$ssoSetting->ssoPhoneAttr = $oldLibrarySettingsRow['ssoPhoneAttr'];
		$ssoSetting->ssoPatronTypeAttr = $oldLibrarySettingsRow['ssoPatronTypeAttr'];
		$ssoSetting->ssoPatronTypeFallback = $oldLibrarySettingsRow['ssoPatronTypeFallback'];
		$ssoSetting->ssoName = $oldLibrarySettingsRow['ssoName'];
		$ssoSetting->ssoMetadataFilename = $oldLibrarySettingsRow['ssoMetadataFilename'];
		$ssoSetting->ssoLibraryIdAttr = $oldLibrarySettingsRow['ssoLibraryIdAttr'];
		$ssoSetting->ssoLibraryIdFallback = $oldLibrarySettingsRow['ssoLibraryIdFallback'];
		$ssoSetting->ssoLastnameAttr = $oldLibrarySettingsRow['ssoLastnameAttr'];
		$ssoSetting->ssoIdAttr = $oldLibrarySettingsRow['ssoIdAttr'];
		$ssoSetting->ssoFirstnameAttr = $oldLibrarySettingsRow['ssoFirstnameAttr'];
		$ssoSetting->ssoEmailAttr = $oldLibrarySettingsRow['ssoEmailAttr'];
		$ssoSetting->ssoDisplayNameAttr = $oldLibrarySettingsRow['ssoDisplayNameAttr'];
		$ssoSetting->ssoCityAttr = $oldLibrarySettingsRow['ssoCityAttr'];
		$ssoSetting->ssoCategoryIdAttr = $oldLibrarySettingsRow['ssoCategoryIdAttr'];
		$ssoSetting->ssoCategoryIdFallback = $oldLibrarySettingsRow['ssoCategoryIdFallback'];
		$ssoSetting->ssoAddressAttr = $oldLibrarySettingsRow['ssoAddressAttr'];
		$ssoSetting->service = 'saml';
		if ($ssoSetting->find(true)) {
			$ssoSettingId = $ssoSetting->id;
		} else {
			$ssoSetting->name = $oldLibrarySettingsRow['displayName'] . ' SAML Settings';
			$ssoSetting->service = 'saml';
			if ($ssoSetting->insert()) {
				$ssoSettingId = $ssoSetting->id;
			}
		}

		$library = new Library();
		$library->libraryId = $oldLibrarySettingsRow['libraryId'];
		if ($library->find(true)) {
			$library->ssoSettingId = $ssoSettingId;
			$library->update();
		}

		$oldLibrarySettingsRow = $oldLibrarySettingsRS->fetch();
	}
}

/** @noinspection PhpUnused */
function moveStaffUserSettings(/** @noinspection PhpUnusedParameterInspection */ &$update) {
	global $aspen_db;
	$oldStaffSettingsSQL = 'SELECT userId, materialsRequestEmailSignature, materialsRequestReplyToAddress FROM user_staff_settings';
	$oldStaffSettingsRS = $aspen_db->query($oldStaffSettingsSQL, PDO::FETCH_ASSOC);
	$oldStaffSettingsRow = $oldStaffSettingsRS->fetch();

	require_once ROOT_DIR . '/sys/Account/User.php';
	while ($oldStaffSettingsRow != null) {
		$user = new User();
		$user->id = $oldStaffSettingsRow['userId'];
		if ($user->find(true)) {
			$user->materialsRequestEmailSignature = $oldStaffSettingsRow['materialsRequestEmailSignature'];
			$user->materialsRequestReplyToAddress = $oldStaffSettingsRow['materialsRequestReplyToAddress'];
			$user->update();
		}

		$oldStaffSettingsRow = $oldStaffSettingsRS->fetch();
	}
}