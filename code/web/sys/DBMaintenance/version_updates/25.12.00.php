<?php

/** @noinspection PhpUnused */
function getUpdates25_12_00(): array {
	return [
		/*'name' => [
			 'title' => '',
			 'description' => '',
			 'continueOnError' => false,
			 'sql' => [
				 ''
			 ]
		 ], //name*/

		//mark - Grove
		'library_options_to_disable_pickup_locations' => [
			'title' => 'Library Options to Disable Pickup Locations',
			'description' => 'Add options to library settings to allow disabling pickup locations',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library ADD COLUMN hidePickupLocationPrompt TINYINT(1) DEFAULT 0',
				'ALTER TABLE library ADD COLUMN allowChangingPickupLocationForUnavailableHolds TINYINT(1) DEFAULT 1',
			]
		], //library_options_to_disable_pickup_locations
		'increase_hoopla_status_field' => [
			'title' => 'Increase Hoopla Status Field Length',
			'description' => 'Increase Hooopla Status Field Length',
			'sql' => [
				'ALTER TABLE hoopla_flex_availability CHANGE COLUMN status status VARCHAR(20) NOT NULL'
			]
		], //increase_hoopla_status_field
		'search_interpreter_remove_custom_facets' => [
			'title' => 'Remove Custom Facets from Search Interpreter',
			'description' => 'Remove Custom Facets from Search Interpreter in favor of special terms',
			'sql' => [
				'ALTER TABLE search_interpreter_settings DROP COLUMN audienceFacet',
				'ALTER TABLE search_interpreter_settings DROP COLUMN fictionNonFictionFacet'
			]
		], //search_interpreter_remove_custom_facets
		'library_enable_website_search' => [
			'title' => 'Library - Enable Website Search',
			'description' => 'Add Enable Website Search to Library Settings',
			'sql' => [
				'ALTER TABLE library ADD COLUMN showWebsiteSearch TINYINT default 1'
			]
		], //library_enable_website_search
		'prioritized_shelf_locations' => [
			'title' => 'Prioritized Shelf Locations',
			'description' => 'Add Prioritized Shelf Locations Table',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS prioritized_shelf_locations (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					groupedWorkSettingsId INT NOT NULL,
					shelfLocation varchar(250) DEFAULT '',
					weight INT(11) DEFAULT 0,
					INDEX (groupedWorkSettingsId, weight)
				) ENGINE = InnoDB"
			]
		], //prioritized_shelf_locations
		'message_bee_settings' => [
			'title' => 'Message Bee Settings',
			'description' => 'Message Bee Settings table and link to library',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS message_bee_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(100) NOT NULL,
					customerToken VARCHAR(50) NOT NULL 
				) ENGINE = InnoDB",
				"ALTER TABLE library ADD COLUMN messageBeeSettingId INT DEFAULT -1",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Third Party Enrichment', 'Administer MessageBee Keys', '', 70, 'Allows users to administer MessageBee Keys.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer MessageBee Keys'))",
			]
		], //message_bee_settings
		'limit_access_to_shared_records' => [
			'title' => 'Limit Access To Shared Records',
			'description' => 'Limit Access To Shared Administration Records',
			'sql' => [
				"CREATE TABLE administration_record_lock (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					module varchar(30) NOT NULL,
					toolName varchar(100) NOT NULL,
					recordId INT NOT NULL,
					UNIQUE (module, toolName, recordId)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('System Administration', 'Lock Administration Records', '', 70, 'Allows the user to lock administration records and change locked records.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Lock Administration Records'))",
			]
		], //limit_access_to_shared_records
		'library_google_analytics' => [
			'title' => 'Library Google Analytics',
			'description' => 'Add Library Google Analytics Table',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS library_google_analytics (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					libraryId int(11) NOT NULL,
					googleApiSettingId INT(11) NOT NULL,
					googleAnalyticsTrackingId varchar(50) DEFAULT NULL,
					UNIQUE (libraryId, googleApiSettingId)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci"
			]
		], //library_google_analytics
		'remove_google_analytics_3' => [
			'title' => 'Remove Google Analytics 3',
			'description' => 'Remove Google Analytics 3 support',
			'sql' => [
				'ALTER TABLE google_api_settings DROP COLUMN googleAnalyticsLinkingId',
				'ALTER TABLE google_api_settings DROP COLUMN googleAnalyticsLinkedProperties',
				'ALTER TABLE google_api_settings DROP COLUMN googleAnalyticsDomainName',
				"UPDATE google_api_settings SET googleAnalyticsVersion='v4' WHERE true"
			],
		], //remove_google_analytics_3
		'monitorWaitTime' => [
			'title' => 'Add an option to allow Wait Time to not be monitored',
			'description' => 'Add an option to allow Wait Time to not be monitored',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE system_variables ADD COLUMN monitorWaitTime TINYINT(1) DEFAULT 1'
			]
		], //monitorWaitTime
		'system_maintenance_permission' => [
			'title' => 'Add System Maintenance Permission',
			'description' => 'Add System Maintenance Permission',
			'continueOnError' => false,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('System Administration', 'Perform System Maintenance', '', 40, 'Allows users to perform system maintenance to keep Aspen running smoothly.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Perform System Maintenance'))",
			]
		], //system_maintenance_permission
		'increase_background_process_notes_length' => [
			'title' => 'Increase Background Process Notes Length',
			'description' => 'Increase Background Process Notes Length',
			'sql' => [
				'ALTER TABLE background_process CHANGE COLUMN notes notes LONGTEXT'
			]
		], //increase_background_process_notes_length
		'loral_settings' => [
			'title' => 'Loral Integration',
			'description' => 'Create Settings for Loral Integration',
			'continueOnError' => false,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS loral_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					loralUrl varchar(255),
					loralId varchar(10),
					password varchar(50) NOT NULL,
					enabled tinyint(1) DEFAULT 1
				)',
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Third Party Enrichment', 'Administer Loral', '', 40, 'Allows users to administer Loral content Enrichment.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Loral'))",
			]
		], //loral_settings
		'link_loral_and_libraries' => [
			'title' => 'Link Loral and Libraries',
			'description' => 'Link Loral and libraries so each library can have a different Loral subscription',
			'sql' => [
				"ALTER TABLE library ADD COLUMN loralSettingId INT DEFAULT -1",
				"ALTER TABLE loral_settings ADD COLUMN name TINYTEXT default 'default' UNIQUE",
			]
		], //link_loral_and_libraries
		'encrypt_loral_password' => [
			'title' => 'Encrypt Loral Password',
			'description' => 'Extend password field to store Loral Password encrypted',
			'sql' => [
				'ALTER TABLE loral_settings CHANGE COLUMN password password VARCHAR(250)'
			]
		], //encrypt_loral_password
		'increase_new_york_times_key_length' => [
			'title' => 'Increase New York Times Key Length',
			'description' => 'Increase the length of the key for the new NYT API Keys',
			'sql' => [
				'ALTER TABLE nyt_api_settings CHANGE COLUMN booksApiKey booksApiKey VARCHAR(48) NOT NULL'
			]
		], //increase_new_york_times_key_length

		//kirstien - Grove

		//kodi - Grove

		// Myranda - Grove

		//Yanjun Li - ByWater
		'add_hoopla_version_to_system_variables' => [
			'title' => 'Add Hoopla Version to System Variables',
			'description' => 'Add Hoopla Version to System Variables',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE system_variables ADD COLUMN hooplaVersion INT DEFAULT 1',
			]
		], //add_hoopla_version_to_system_variables

		// Leo Stoyanov - BWS
		'grouped_work_display_settings_showIndexedSeriesWithNoveList' => [
			'title' => 'Grouped Work Display Settings - Add Show Indexed Series with NoveList',
			'description' => 'Add showIndexedSeriesWithNoveList field to grouped_work_display_settings table to control whether indexed series are displayed alongside NoveList or manual override series.',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE grouped_work_display_settings ADD COLUMN IF NOT EXISTS showIndexedSeriesWithNoveList TINYINT(1) DEFAULT 0",
			]
		], //grouped_work_display_settings_showIndexedSeriesWithNoveList
		'grouped_work_display_settings_numSeriesToShowBeforeMore' => [
			'title' => 'Grouped Work Display Settings - Add Number of Series to Show Before "More Series" Link',
			'description' => 'Add numSeriesToShowBeforeMore field to grouped_work_display_settings table to configure the number of series entries displayed before showing "More Series..." link.',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE grouped_work_display_settings ADD COLUMN IF NOT EXISTS numSeriesToShowBeforeMore INT(11) DEFAULT 3",
			]
		], //grouped_work_display_settings_numSeriesToShowBeforeMore
		'grouped_work_display_settings_hideIndexedEContentSeries' => [
			'title' => 'Grouped Work Display Settings - Add Hide Indexed E-Content Series',
			'description' => 'Add hideIndexedEContentSeries field to grouped_work_display_settings table to control whether indexed series from eContent sources (OverDrive, Hoopla) are hidden from display.',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE grouped_work_display_settings ADD COLUMN IF NOT EXISTS hideIndexedEContentSeries TINYINT(1) DEFAULT 0",
			]
		], //grouped_work_display_settings_hideIndexedEContentSeries
		'hide_soft_delete_list_ui' => [
			'title' => 'Hide Soft Delete List UI',
			'description' => 'Add setting to hide soft delete messaging and checkbox when deleting lists',
			'sql' => [
				"ALTER TABLE library ADD COLUMN IF NOT EXISTS hideSoftDeleteListUI TINYINT(1) DEFAULT 0",
			],
		], //hide_soft_delete_list_ui
		'list_format_filter_persistence' => [
			'title' => 'Allow Persistence of User List Format Filters',
			'description' => 'Add userListFilters column to persist format filters per list for users.',
			'sql' => [
				'ALTER TABLE user_page_defaults ADD COLUMN IF NOT EXISTS userListFilters VARCHAR(512) DEFAULT NULL'
			]
		], //list_format_filter_persistence
		'library_user_defined_fields_table' => [
			'title' => 'Library User Defined Fields Table',
			'description' => 'Create a table for library user defined fields.',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS library_user_defined_field (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					libraryId INT NOT NULL,
					fieldNumber VARCHAR(30) NOT NULL,
					label VARCHAR(255) DEFAULT '',
					required TINYINT(1) DEFAULT 0,
					maxLength INT DEFAULT 255,
					INDEX (libraryId),
					UNIQUE KEY library_field (libraryId, fieldNumber)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			],
		], //library_user_defined_fields_table
		'populate_location_facet_labels' => [
			'title' => 'Populate Location Facet Labels',
			'description' => 'Copy legacy "location" Translation Map values into the Location table facet labels based on matching codes.',
			'continueOnError' => false,
			'sql' => [
				// First, handle exact matches (non-regex entries).
				"UPDATE location
					INNER JOIN translation_maps tm ON tm.name = 'location' AND tm.usesRegularExpressions = 0
					INNER JOIN translation_map_values tmv ON tmv.translationMapId = tm.id
					SET location.facetLabel = tmv.translation
					WHERE LOWER(location.code) = LOWER(tmv.value)
					AND (location.facetLabel IS NULL OR location.facetLabel = '')",

				// Then, handle regex matches (regex entries).
				"UPDATE location
					INNER JOIN translation_maps tm ON tm.name = 'location' AND tm.usesRegularExpressions = 1
					INNER JOIN translation_map_values tmv ON tmv.translationMapId = tm.id
					SET location.facetLabel = tmv.translation
					WHERE LOWER(location.code) REGEXP LOWER(tmv.value)
					AND (location.facetLabel IS NULL OR location.facetLabel = '')"
			]
		], //populate_location_facet_labels
		'library_self_reg_form_message_translations' => [
			'title' => 'Library - Translate Self Registration Form Message',
			'description' => 'Copy existing self registration form messages into the text block translation table for all available languages.',
			'continueOnError' => false,
			'sql' => [
				"INSERT INTO text_block_translation (objectType, objectId, fieldName, languageId, translation)
				SELECT 'Library', l.libraryId, 'selfRegistrationFormMessage', lang.id, l.selfRegistrationFormMessage
				FROM library l
				JOIN languages lang ON 1=1
				LEFT JOIN text_block_translation existing ON existing.objectType = 'Library' AND existing.objectId = l.libraryId AND existing.fieldName = 'selfRegistrationFormMessage' AND existing.languageId = lang.id
				WHERE l.selfRegistrationFormMessage IS NOT NULL AND l.selfRegistrationFormMessage <> '' AND existing.id IS NULL AND lang.code NOT IN ('ubb','pig')"
			]
		], //library_self_reg_form_message_translations
		'library_self_reg_success_message_translations' => [
			'title' => 'Library - Translate Self Registration Success Message',
			'description' => 'Copy existing self registration success messages into the text block translation table for all available languages.',
			'continueOnError' => false,
			'sql' => [
				"INSERT INTO text_block_translation (objectType, objectId, fieldName, languageId, translation)
				SELECT 'Library', l.libraryId, 'selfRegistrationSuccessMessage', lang.id, l.selfRegistrationSuccessMessage
				FROM library l
				JOIN languages lang ON 1=1
				LEFT JOIN text_block_translation existing ON existing.objectType = 'Library' AND existing.objectId = l.libraryId AND existing.fieldName = 'selfRegistrationSuccessMessage' AND existing.languageId = lang.id
				WHERE l.selfRegistrationSuccessMessage IS NOT NULL AND l.selfRegistrationSuccessMessage <> '' AND existing.id IS NULL AND lang.code NOT IN ('ubb','pig')"
			]
		], //library_self_reg_success_message_translations
		'reading_history_add_barcode' => [
			'title' => 'Reading History - Add Barcode Field',
			'description' => 'Add barcode column to user_reading_history_work table to store item barcode for historical checkouts.',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE user_reading_history_work ADD COLUMN IF NOT EXISTS barcode VARCHAR(50) DEFAULT NULL AFTER sourceId",
			]
		],
		'reading_history_add_edited_checkin_date' => [
			'title' => 'Reading History - Add Edited Check-In Date Field',
			'description' => 'Add editedCheckInDate column to user_reading_history_work table to store user-edited check-in dates while preserving original checkInDate.',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE user_reading_history_work ADD COLUMN IF NOT EXISTS editedCheckInDate BIGINT(20) DEFAULT NULL AFTER checkInDate",
			]
		],
		'library_display_call_number_and_volume_in_checkout_history' => [
			'title' => 'Library - Display Call Number and Volume in Checkout History',
			'description' => 'Add options to display call number and volume in checkout history.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library ADD COLUMN IF NOT EXISTS displayCallNumberInCheckoutHistory TINYINT(1) DEFAULT 0',
				'ALTER TABLE library ADD COLUMN IF NOT EXISTS displayVolumeInCheckoutHistory TINYINT(1) DEFAULT 0',
			]
		], //library_display_call_number_and_volume_in_checkout_history
		'reading_history_add_call_number_and_volume' => [
			'title' => 'Reading History - Add Call Number and Volume Fields',
			'description' => 'Add callNumber and volume columns to user_reading_history_work table to store item call number and volume information.',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE user_reading_history_work ADD COLUMN IF NOT EXISTS callNumber VARCHAR(255) DEFAULT NULL AFTER barcode",
				"ALTER TABLE user_reading_history_work ADD COLUMN IF NOT EXISTS volume VARCHAR(255) DEFAULT NULL AFTER callNumber",
			]
		], //reading_history_add_call_number_and_volume

		// Imani -BWS
		'externalRequestSettings' => [
			'title' => 'Add External Request Settings',
			'description' => 'Create a table for External Request Settings',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS `external_request_settings` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`requestType` varchar(50) DEFAULT NULL,
				`enabled` tinyint(1) DEFAULT 0,
				`expireDate` DATE DEFAULT NULL,
				PRIMARY KEY (`id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;'
			]
		], //add external request settings table

		//alexander - Open Fifth

		//chloe - Open Fifth

		//James Staub - Nashville Public Library

		//Lucas Montoya - Theke Solutions

		// Galen Charlton - Equinox
		'chilifresh_cover_art_settings' => [
			'title' => 'ChiliFresh covert art settings',
			'description' => 'ChiliFresh covert art settings',
			'continueOnError' => false,
			'sql' => [
				"CREATE TABLE IF NOT EXISTS chilifresh_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					enabled TINYINT(1) NOT NULL DEFAULT 1,
					genericArtCode TINYTEXT
				) ENGINE = InnoDB",
			]
		],

		// Tomas Cohen Arazi - Theke Solutions
		'configurable_solr_spellcheck_collation' => [
			'title' => 'SolR - Spellcheck Collation max tries',
			'description' => 'Make SolR spellcheck.maxCollationTries configurable',
			'sql' => [
				"ALTER TABLE system_variables ADD COLUMN spellcheckMaxCollationTries int(11) DEFAULT 25 AFTER solrQueryTimeout;"
			],
		],

		//other

	];
}
