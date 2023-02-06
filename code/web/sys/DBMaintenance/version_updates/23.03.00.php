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