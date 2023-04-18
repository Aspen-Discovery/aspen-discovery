<?php
/** @noinspection PhpUnused */
function getUpdates23_04_00(): array {
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
		'use_library_themes_for_location' => [
			'title' => 'Use Library Themes for Location',
			'description' => 'Use Library Themes for Location',
			'sql' => [
				"ALTER TABLE location ADD COLUMN useLibraryThemes TINYINT(1) DEFAULT 1",
				"UPDATE location set useLibraryThemes = 1 where theme = -1",
			],
		],
		'allow_multiple_themes_for_libraries' => [
			'title' => 'Allow Multiple Themes for Libraries',
			'description' => 'Allow Multiple Themes for Libraries',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS library_themes (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					libraryId INT(11) NOT NULL,
					themeId  INT(11) NOT NULL,
					weight INT(11) DEFAULT 0,
					INDEX libraryToTheme(libraryId, themeId)
				) ENGINE INNODB",
				'INSERT INTO library_themes (libraryId, themeId) (SELECT libraryId, theme from library)'
			],
		], //allow_multiple_themes_for_libraries
		'allow_multiple_themes_for_locations' => [
			'title' => 'Allow Multiple Themes for Locations',
			'description' => 'Allow Multiple Themes for Locations',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS location_themes (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					locationId INT(11) NOT NULL,
					themeId  INT(11) NOT NULL,
					weight INT(11) DEFAULT 0,
					INDEX libraryToTheme(locationId, themeId)
				) ENGINE INNODB",
				'INSERT INTO location_themes (locationId, themeId) (SELECT locationId, theme from location)'
			],
		], //allow_multiple_themes_for_locations
		'add_display_name_to_themes' => [
			'title' => 'Add Display name to themes',
			'description' => 'Add Display name to themes',
			'sql' => [
				"ALTER TABLE themes add column displayName VARCHAR(60) NOT NULL",
				"UPDATE themes set displayName = themeName",
			],
		], //add_display_name_to_themes
		'allow_users_to_change_themes' => [
			'title' => 'Allow Users to change themes',
			'description' => 'Allow Users to change themes',
			'sql' => [
				"ALTER TABLE user add column preferredTheme int(11) default -1",
			],
		], //allow_users_to_change_themes
		'shared_content_in_greenhouse' => [
			'title' => 'Add shared content to the greenhouse',
			'description' => 'Allow libraries to share content within the community',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS shared_content (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					type VARCHAR(50) NOT NULL,
					name VARCHAR(100) NOT NULL,
					description TEXT,
					sharedFrom VARCHAR(50) NOT NULL,
					sharedByUserName VARCHAR(256) NOT NULL,
					shareDate int(11), 
					approved TINYINT(1) DEFAULT 0,
					approvalDate int(11),
					approvedBy int(11),
					data TEXT                                          
				) ENGINE INNODB",
			],
		], //shared_content_in_greenhouse
		'lowercase_all_tables' => [
			'title' => 'Make all tables lower case',
			'description' => 'Make all tables lower case for improved cross platform compatibility when installing clean via docker',
			'continueOnError' => true,
			'sql' => [
				"RENAME TABLE indexed_callNumber TO indexed_call_number;",
				"RENAME TABLE indexed_eContentSource TO indexed_econtent_source;",
				"RENAME TABLE indexed_itemType TO indexed_item_type;",
				"RENAME TABLE indexed_groupedStatus TO indexed_grouped_status;",
				"RENAME TABLE indexed_locationCode TO indexed_location_code;",
				"RENAME TABLE indexed_shelfLocation TO indexed_shelf_location;",
				"RENAME TABLE indexed_subLocationCode TO indexed_sub_location_code;",
				"RENAME TABLE indexed_physicalDescription TO indexed_physical_description;",
				"RENAME TABLE indexed_publicationDate TO indexed_publication_date;",
			],
		], //lowercase_all_tables
		'allow_ip_tracking_to_be_disabled' => [
			'title' => 'Allow IP tracking to be disabled',
			'description' => 'Allow IP tracking to be disabled for GDPR compliance',
			'sql' => [
				"ALTER TABLE system_variables add column trackIpAddresses TINYINT(1) DEFAULT 0"
			],
		], //allow_ip_tracking_to_be_disabled
		'create_community_content_url' => [
			'title' => 'Create Community Content URL',
			'description' => 'Create Community Content URL',
			'sql' => [
				"ALTER TABLE system_variables add column communityContentUrl VARCHAR(128) DEFAULT ''",
				"UPDATE system_variables set communityContentUrl = greenhouseUrl"
			],
		], //create_community_content_url
		'add_last_check_in_community_for_translations' => [
			'title' => 'Add Last Check In Community for Translations',
			'description' => 'Add Last Check In Community for Translations',
			'sql' => [
				"ALTER TABLE translations add column lastCheckInCommunity INT(11) default 0",
			],
		], //add_last_check_in_community_for_translations
		'permissions_community_sharing' => [
			'title' => 'Add permissions for Sharing and retrieving content from the Community Server',
			'description' => 'Add permissions for Sharing and retrieving content from the Community Server',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Community Sharing', 'Share Content with Community', '', 10, 'Controls if the user can share content with other members of the Aspen Discovery Community they are connected with.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Community Sharing', 'Import Content from Community', '', 20, 'Controls if the user can import content created by other members of the Aspen Discovery Community they are connected with.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Share Content with Community'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Import Content from Community'))",
			],
		],
		'sierra_order_record_options' => [
			'title' => 'Sierra Order Record Options',
			'description' => 'Add new options for controlling the export and indexing of order records for Sierra',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE indexing_profiles ADD COLUMN orderRecordsStatusesToInclude VARCHAR(25) DEFAULT 'o|1'",
				"ALTER TABLE indexing_profiles ADD COLUMN hideOrderRecordsForBibsWithPhysicalItems TINYINT(1) DEFAULT 0",
				"ALTER TABLE indexing_profiles ADD COLUMN orderRecordsToSuppressByDate TINYINT(1) DEFAULT 1",
				"updateSierraOrderRecordSettings",
			],
		],

		//kirstien
		'add_ecommerce_deluxe' => [
			'title' => 'Add eCommerce vendor Certified Payments by Deluxe',
			'description' => 'Create Certified Payments by Deluxe settings table',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS deluxe_certified_payments_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					name VARCHAR(50) NOT NULL UNIQUE,
					sandboxMode TINYINT(1) DEFAULT 0,
					applicationId VARCHAR(500) NOT NULL,
					securityId VARCHAR(500) NOT NULL
				) ENGINE INNODB',
				'ALTER TABLE library ADD COLUMN deluxeCertifiedPaymentsSettingId INT(11) DEFAULT -1',
			],
		],
		//add_ecommerce_deluxe
		'permissions_ecommerce_deluxe' => [
			'title' => 'Add permissions for Certified Payments by Deluxe',
			'description' => 'Create permissions for administration of Certified Payments by Deluxe',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('eCommerce', 'Administer Certified Payments by Deluxe', '', 10, 'Controls if the user can change Certified Payments by Deluxe settings. <em>This has potential security and cost implications.</em>')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Certified Payments by Deluxe'))",
			],
		],
		// permissions_ecommerce_deluxe
		'add_deluxe_remittance_id' => [
			'title' => 'Add remittance id for Deluxe user payments',
			'description' => 'Store remittance id in user_payments for Certified Payments by Deluxe',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE user_payments ADD COLUMN deluxeRemittanceId VARCHAR(24) DEFAULT null',
			],
		],
		//add_deluxe_remittance_id
		'add_deluxe_security_id' => [
			'title' => 'Add security id for Deluxe user payments',
			'description' => 'Store security id in user_payments for Certified Payments by Deluxe',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE user_payments ADD COLUMN deluxeSecurityId VARCHAR(32) DEFAULT null',
			],
		],
		//add_deluxe_security_id
		'update_deluxe_remittance_id' => [
			'title' => 'Update remittance id type for Deluxe user payments',
			'description' => 'Change remittance id column type in user_payments for Certified Payments by Deluxe',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE user_payments MODIFY COLUMN deluxeRemittanceId VARCHAR(24) DEFAULT null',
			],
		],
		//update_deluxe_remittance_id
		'extend_web_form_label' => [
			'title' => 'Extend label in web_builder_custom_form_field',
			'description' => 'Extend column for storing field label in web_builder_custom_form_field table',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE web_builder_custom_form_field MODIFY COLUMN label VARCHAR(255)',
			]
		],
		//extend_web_form_label
		'add_high_contrast_checkbox' => [
			'title' => 'Add checkbox for if theme is high contrast or not',
			'description' => 'Adds checkbox to themes for additional CSS modifications applicable to high contrast themes',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE themes ADD COLUMN isHighContrast TINYINT(1) DEFAULT 0',
			]
		],
		//add_high_contrast_checkbox
		'add_branded_app_name' => [
			'title' => 'Add name in branded app settings',
			'description' => 'Adds column to store name for branded apps',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE aspen_lida_branded_settings ADD COLUMN appName VARCHAR(100)',
			]
		],
		//add_branded_app_name

		//kodi
		'permissions_create_events_communico' => [
			'title' => 'Alters permissions for Events',
			'description' => 'Create permissions for Communico',
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'Administer Communico Settings', 'Events', 20, 'Allows the user to administer integration with Communico for all libraries.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Communico Settings'))",
			],
		],
		// permissions_create_events_communico
		'communico_settings' => [
			'title' => 'Define events settings for Communico integration',
			'description' => 'Initial setup of the Communico integration',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS communico_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(100) NOT NULL UNIQUE,
					baseUrl VARCHAR(255) NOT NULL,
					clientId VARCHAR(36) NOT NULL,
					clientSecret VARCHAR(36) NOT NULL
				) ENGINE INNODB',
			],
		],

		// communico_settings
		'communico_events' => [
			'title' => 'Communico Events Data',
			'description' => 'Setup tables to store events data for Communico',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS communico_events (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					settingsId INT NOT NULL,
					externalId varchar(36) NOT NULL,
					title varchar(255) NOT NULL,
					rawChecksum BIGINT,
					rawResponse MEDIUMTEXT,
					deleted TINYINT default 0,
					UNIQUE (settingsId, externalId)
				)',
			],
		],
		// communico_events
		'user_list_entry_length' => [
			'title' => 'User List Entry sourceId Length',
			'description' => 'Increase allowed length for sourceId in user list entries.',
			'sql' => [
				"ALTER TABLE user_list_entry CHANGE COLUMN sourceId sourceId VARCHAR(50) NOT NULL",
			],
		],
		// user_list_entry_length
		'user_events_entry' => [
			'title' => 'User Saved Events Data',
			'description' => 'Setup table to store saved events data for patrons',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS user_events_entry (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					userId INT(11) NOT NULL, 
					sourceId varchar(50) NOT NULL,
					title varchar(255) NOT NULL,
					eventDate INT (11),
					regRequired TINYINT DEFAULT 0,
					location varchar(255),
					dateAdded INT(11),
					UNIQUE (sourceId)
				)',
			],
		],
		//user_events_entry_length
		'user_events_entry_length' => [
			'title' => 'User Events Entry sourceId Length',
			'description' => 'Increase allowed length for sourceId in user event entries.',
			'sql' => [
				"ALTER TABLE user_events_entry CHANGE COLUMN sourceId sourceId VARCHAR(50) NOT NULL",
			],
		],
		//user_events_entry_location_length
		'user_events_entry_location_length' => [
			'title' => 'User Events Entry location Length',
			'description' => 'Increase allowed length for location in user event entries.',
			'sql' => [
				"ALTER TABLE user_events_entry CHANGE COLUMN location location VARCHAR(255) NOT NULL",
			],
		],
		//user_events_entry_unique
		'user_events_entry_unique' => [
			'title' => 'Changes UNIQUE key for user_events_entry',
			'description' => 'Changes unique key for user_events_entry from (sourceId) to (userId, sourceId).',
			'sql' => [
				"ALTER TABLE user_events_entry DROP INDEX sourceId",
				"ALTER TABLE user_events_entry ADD UNIQUE (userId, sourceId)",
			],
		],
		// Set default weight for (library|location)_records_to_include to 0
		'default_records_to_include_weight' => [
			'title' => 'Default Value for Records to Include Weight',
			'description' => 'Add a default of 0 for weight in the library_records_to_include and location_records_to_include tables',
			'sql' => [
				'ALTER TABLE library_records_to_include ALTER COLUMN weight SET DEFAULT 0',
				'ALTER TABLE location_records_to_include ALTER COLUMN weight SET DEFAULT 0',
			],
		],
	];
}

function updateSierraOrderRecordSettings() {
	//Check to see if we have a Sierra indexing profile
	global $indexingProfiles;
	global $configArray;
	foreach ($indexingProfiles as $indexingProfile) {
		if ($indexingProfile->indexingClass == 'III') {
			if (isset($configArray['Index']['suppressOrderRecordsThatAreReceivedAndCataloged']) && $configArray['Index']['suppressOrderRecordsThatAreReceivedAndCataloged'] == true) {
				$indexingProfile->orderRecordsToSuppressByDate = 4;
			}elseif (isset($configArray['Index']['suppressOrderRecordsThatAreCataloged']) && $configArray['Index']['suppressOrderRecordsThatAreCataloged'] == true && isset($configArray['Index']['suppressOrderRecordsThatAreReceived']) && $configArray['Index']['suppressOrderRecordsThatAreReceived'] == true) {
				$indexingProfile->orderRecordsToSuppressByDate = 4;
			}elseif (isset($configArray['Index']['suppressOrderRecordsThatAreReceived']) && $configArray['Index']['suppressOrderRecordsThatAreReceived'] == true) {
				$indexingProfile->orderRecordsToSuppressByDate = 3;
			}elseif (isset($configArray['Index']['suppressOrderRecordsThatAreCataloged']) && $configArray['Index']['suppressOrderRecordsThatAreCataloged'] == true) {
				$indexingProfile->orderRecordsToSuppressByDate = 2;
			}else{
				$indexingProfile->orderRecordsToSuppressByDate = 1;
			}
			if (isset($configArray['Reindex']['orderStatusesToExport'])) {
				$indexingProfile->orderRecordsStatusesToInclude = $configArray['Reindex']['orderStatusesToExport'];
			}
			$indexingProfile->update();
		}
	}
}