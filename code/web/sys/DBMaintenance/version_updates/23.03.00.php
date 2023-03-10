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
		'increase_ill_link_size' => [
			'title' => 'Increase the length of the  Interlibrary Loan URL',
			'description' => 'Increase the length of the  Interlibrary Loan URL',
			'sql' => [
				"ALTER TABLE library CHANGE COLUMN interLibraryLoanUrl interLibraryLoanUrl VARCHAR(200);",
			],
		],

		'allow_decimal_series_display_orders' => [
			'title' => 'Allow Decimal Series Display Orders',
			'description' => 'Allow Decimal Series Display Orders',
			'sql' => [
				"ALTER TABLE grouped_work_display_info CHANGE COLUMN seriesDisplayOrder seriesDisplayOrder DECIMAL(6,2);",
			],
		],
		'add_iiiLoginConfiguration' => [
			'title' => 'Add III Login Configuration to Account Profile',
			'description' => 'Add III Login Configuration to Account Profile',
			'sql' => [
				"ALTER TABLE account_profiles ADD COLUMN iiiLoginConfiguration enum('', 'barcode_pin','name_barcode', 'name_barcode_pin') COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';",
				"UPDATE account_profiles SET iiiLoginConfiguration = loginConfiguration WHERE ils IN ('millennium', 'sierra')"
			],
		],
		'move_includePersonalAndCorporateNamesInTopics' => [
			'title' => 'Move Include Personal And Corporate Names In Topics',
			'description' => 'Add includePersonalAndCorporateNamesInTopics to System Variables',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE indexing_profiles ADD COLUMN includePersonalAndCorporateNamesInTopics TINYINT(1) NOT NULL DEFAULT 1;",
				"ALTER TABLE sideloads ADD COLUMN includePersonalAndCorporateNamesInTopics TINYINT(1) NOT NULL DEFAULT 1;",
				"UPDATE indexing_profiles set includePersonalAndCorporateNamesInTopics = (SELECT includePersonalAndCorporateNamesInTopics from system_variables)",
				"UPDATE sideloads set includePersonalAndCorporateNamesInTopics = (SELECT includePersonalAndCorporateNamesInTopics from system_variables)",
				"ALTER TABLE system_variables DROP COLUMN includePersonalAndCorporateNamesInTopics",
			]
		], //includePersonalAndCorporateNamesInTopics
		'assign_novelist_settings_to_libraries' => [
			'title' => 'Assign Novelist Settings to Libraries',
			'description' => 'Assign Novelist Settings to Libraries',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE library ADD COLUMN novelistSettingId INT(11) DEFAULT -1",
				"UPDATE library set novelistSettingId = IFNULL((SELECT id from novelist_settings LIMIT 0, 1), -1)",
			]
		],

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
		'add_materials_requests_limit_by_ptype' => [
			'title' => 'Add option to allow materials requests by ptype',
			'description' => 'Add option to allow materials requests by ptype',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE ptype ADD COLUMN canSuggestMaterials TINYINT(1) DEFAULT 1',
			],
		],
		//add_materials_requests_limit_by_ptype
		'extend_grouped_work_id_not_interested' => [
			'title' => 'Extend groupedRecordPermanentId in user_not_interested',
			'description' => 'Extend groupedRecordPermanentId in user_not_interested',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE user_not_interested MODIFY COLUMN groupedRecordPermanentId VARCHAR(40)",
			]
		],
		//extend_grouped_work_id_not_interested
		'extend_bookcover_info_source' => [
			'title' => 'Extend imageSource in bookcover_info',
			'description' => 'Extend column for storing imageSource in bookcover_info table',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE bookcover_info MODIFY COLUMN imageSource VARCHAR(100)',
			]
		],
		//extend_bookcover_info_source
		'add_donateToLibrary' => [
			'title' => 'Add field to store location name for donation',
			'description' => 'Add field to store location name for donation for filtering',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE donations ADD COLUMN donateToLibrary VARCHAR(60) DEFAULT NULL',
				'addDonationLocationName'
			],
		],
		//add_donateToLibrary
		'add_donationEarmark' => [
			'title' => 'Add earmark label to donations table',
			'description' => 'Add earmark label to donations table for filtering',
			'continueOnError' => true,
			'sql' => [
				'addDonationEarmark'
			],
		],
		//add_donationEarmark
		'truncate_donation_form_fields' => [
			'title' => 'Truncate donation form fields to trigger a reload of defaults',
			'description' => 'Truncate donation form fields to trigger a reload of defaults',
			'continueOnError' => true,
			'sql' => [
				'TRUNCATE TABLE donations_form_fields',
			],
		],
		//truncate_donation_form_fields
		'drop_sso_mapping_constraints' => [
			'title' => 'Remove table constraints on sso_mapping',
			'description' => 'Remove table constraints on sso_mapping',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE sso_mapping DROP INDEX aspenField'
			]
		],
		//drop_sso_mapping_constraints
		'add_sso_mapping_constraints' => [
			'title' => 'Add table constraints on sso_mapping',
			'description' => 'Add table constraints on sso_mapping',
			'continueOnError' => true,
			'sql' => [
				'CREATE INDEX mapping ON sso_mapping (aspenField, ssoSettingId)',
			]
		],
		//add_sso_mapping_constraints


		//kodi
		'google_bucket' => [
			'title' => 'Google Bucket',
			'description' => 'Add variable for Google backup bucket in system variables',
			'sql' => [
				'ALTER TABLE system_variables ADD COLUMN googleBucket VARCHAR(128) DEFAULT NULL'
			],
		],
		//google_bucket

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
		if(!empty($oldLibrarySettingsRow['ssoEntityId']) && $oldLibrarySettingsRow['ssoEntityId'] != '') {
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

/** @noinspection PhpUnused */
function addDonationLocationName(/** @noinspection PhpUnusedParameterInspection */ &$update) {
	global $aspen_db;
	$donationsSQL = 'SELECT id, donateToLibraryId FROM donations';
	$donationsRS = $aspen_db->query($donationsSQL, PDO::FETCH_ASSOC);
	$donationsRow = $donationsRS->fetch();

	while ($donationsRow != null) {
		require_once ROOT_DIR . '/sys/Donations/Donation.php';
		$donation = new Donation();
		$donation->id = $donationsRow['id'];
		if($donation->find(true)) {
			if(!empty($donationsRow['donateToLibraryId']) && $donationsRow['donateToLibraryId'] != 0 && $donationsRow['donateToLibraryId'] != '0') {
				require_once ROOT_DIR . '/sys/LibraryLocation/Location.php';
				$location = new Location();
				$location->locationId = $donationsRow['donateToLibraryId'];
				if ($location->find(true)) {
					$donation->donateToLibrary = $location->displayName;
				} else {
					$donation->donateToLibrary = 'Unknown';
				}
			} else {
				$donation->donateToLibrary = 'None';
			}

			$donation->update();
		}

		$donationsRow = $donationsRS->fetch();
	}
}

/** @noinspection PhpUnused */
function addDonationEarmark(/** @noinspection PhpUnusedParameterInspection */ &$update) {
	global $aspen_db;
	$donationsSQL = 'SELECT id, comments FROM donations';
	$donationsRS = $aspen_db->query($donationsSQL, PDO::FETCH_ASSOC);
	$donationsRow = $donationsRS->fetch();

	while ($donationsRow != null) {
		require_once ROOT_DIR . '/sys/Donations/Donation.php';
		$donation = new Donation();
		$donation->id = $donationsRow['id'];
		if ($donation->find(true)) {
			if (!empty($donationsRow['comments'])) {
				if ($donationsRow['comments'] != 0 && $donationsRow['comments'] != '0' && $donationsRow['comments'] != 'null') {
					require_once ROOT_DIR . '/sys/Donations/DonationEarmark.php';
					$earmark = new DonationEarmark();
					$earmark->id = $donationsRow['comments'];
					if ($earmark->find(true)) {
						$donation->comments = $earmark->label;
					}
				} elseif($donationsRow['comments'] == 0 || $donationsRow['comments'] == '0' || $donationsRow['comments'] == 'null') {
					$donation->comments = 'None';
				}
			} else {
				$donation->comments = 'None';
			}
			$donation->update();
		}
		$donationsRow = $donationsRS->fetch();
	}
}