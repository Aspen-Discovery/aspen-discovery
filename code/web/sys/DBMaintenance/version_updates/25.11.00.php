<?php

/** @noinspection PhpUnused */
function getUpdates25_11_00(): array {
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
		'library_allow_automatic_faceting' => [
			'title' => 'Library - Allow Automatic Faceting',
			'description' => 'Add a setting of whether applying automatic facets to search term is allowed.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library add COLUMN allowAutomaticFaceting TINYINT DEFAULT 0'
			]
		], //library_allow_automatic_faceting
		'search_interpreter_settings' => [
			'title' => 'Search Interpreter Settings',
			'description' => 'Add a table to control how searches are processed',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE library CHANGE column allowAutomaticFaceting enableSearchInterpreter TINYINT DEFAULT 0',
				"DROP TABLE IF EXISTS search_interpreter_settings",
				"DROP TABLE IF EXISTS search_interpreter_terms_to_skip",
				"DROP TABLE IF EXISTS search_interpreter_special_terms",
				"CREATE TABLE IF NOT EXISTS search_interpreter_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					processFormatCategories TINYINT DEFAULT 1,
					formatCategoriesToSkip TINYTEXT,
					processFormats TINYINT DEFAULT 1,
					formatsToSkip TINYTEXT,
					processPluralFormats TINYINT DEFAULT 1,
					pluralFormatsToSkip TINYTEXT,
					processAudiences TINYINT DEFAULT 1,
					audiencesToSkip TINYTEXT,
					processPluralAudiences TINYINT DEFAULT 1,
					pluralAudiencesToSkip TINYTEXT,
					audienceFacet VARCHAR(50) DEFAULT 'target_audience',
					processFictionNonFiction TINYINT DEFAULT 1,
					fictionNonFictionFacet VARCHAR(50) DEFAULT 'literary_form',
					processNew TINYINT DEFAULT 1,
					processAvailable TINYINT DEFAULT 1
				) ENGINE = InnoDB",
				'CREATE TABLE IF NOT EXISTS search_interpreter_terms_to_skip (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					settingId INT NOT NULL,
					term VARCHAR(75) NOT NULL
				) ENGINE = InnoDB',
				'CREATE TABLE IF NOT EXISTS search_interpreter_special_terms (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					settingId INT NOT NULL,
					term TINYTEXT NOT NULL,
					combineWithNewFilter TINYINT DEFAULT 1,
					facetsToApply TEXT,
					sortToApply VARCHAR(50)
				) ENGINE = InnoDB',
				//Create a default settings
				'INSERT INTO search_interpreter_settings (id) VALUES (1)'
			]
		], //search_interpreter_settings
		'add_permission_for_search_interpreter' => [
			'title' => 'Add permissions for Search Interpreter',
			'description' => 'Add permissions for Search Interpreter',
			'continueOnError' => false,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Grouped Work Display', 'Administer Search Interpreter', '', 60, 'Allows users to administer the search interpreter to change how searches are processed.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Search Interpreter'))",
			]
		], //add_permission_for_econtent_sorting
		'google_translate_api_key' => [
			'title' => 'Add Google Translate API Key',
			'description' => 'Add the ability to store a Google Translate API Key.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE google_api_settings ADD COLUMN googleTranslateKey varchar(60) DEFAULT NULL'
			]
		], //google_translate_api_key
		'translator_indicate_google_translations' => [
			'title' => 'Translator - Indicate Google Translations',
			'description' => 'Track which translations were loaded using Google Translate.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE translations ADD COLUMN googleTranslated TINYINT DEFAULT 0',
			]
		], //translator_indicate_google_translations
		'event_search_options' => [
			'title' => 'Event Search Options',
			'description' => 'Event Search Options (for Aspen Events)',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library ADD COLUMN aspenEventsToInclude INT DEFAULT 2',
				'UPDATE library SET aspenEventsToInclude = 1 WHERE isConsortialCatalog = 1'
			]
		], //event_search_options
		'remove_location_event_settings' => [
			'title' => 'Remove Location Event Settings',
			'description' => 'Remove Location Event Settings',
			'continueOnError' => false,
			'sql' => [
				'DROP TABLE location_events_setting'
			]
		], //event_search_options
		'force_full_events_index_25_11' => [
			'title' => 'Force Full Events Index 25.11',
			'description' => 'Method…',
			'continueOnError' => false,
			'sql' => [
				'UPDATE events_indexing_settings set runFullUpdate = 1'
			]
		], //event_search_options

		//katherine - Grove

		//kirstien - Grove
		'add_user_list_group_table' => [
			'title' => 'Add list group table',
			'description' => 'Add table to support grouping of lists',
			'continueOnError' => false,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS user_list_group (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					title VARCHAR(255) NOT NULL,
					parentGroupId INT(11) DEFAULT NULL,
					userId INT(11) NOT NULL
				)'
			],
		],
		//add_user_list_group_table
		'add_last_used_group_list_view_to_user' => [
			'title' => 'Add last viewed list group to user',
			'description' => 'Add column to track last viewed list group for users',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE user ADD COLUMN lastListGroupViewed INT(11) DEFAULT NULL',
			]
		],
		//add_last_used_group_list_view_to_user
		'add_last_used_group_list_added_to_user' => [
			'title' => 'Add last added used group list to user',
			'description' => 'Add column to track last added list group for users',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE user ADD COLUMN lastListGroupAdded INT(11) DEFAULT NULL',
			]
		],
		//add_last_used_group_list_added_to_user
		'add_group_list_id_to_user_list' => [
			'title' => 'Add group list id to user list',
			'description' => 'Add column to track group list id for user lists',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE user_list ADD COLUMN listGroupId INT(11) DEFAULT -1',
			]
		],
		//add_group_list_id_to_user_list

		//kodi - Grove
		'event_field_calendar_options' => [
			'title' => 'Event Field Calendar Options',
			'description' => 'Create event_field_calendar_options table.',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS event_field_calendar_options (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					calendarDisplaySettingId INT NOT NULL,
					eventFieldId INT NOT NULL,
					weight INT DEFAULT 0,
					displayedOnline TINYINT(1) DEFAULT 0,
					printedCalendar TINYINT(1) DEFAULT 0,
					printedAgenda TINYINT(1) DEFAULT 0
				) ENGINE = InnoDB',
			]
		], //event_field_calendar_options
		'calendar_display_setting_library' => [
			'title' => 'Calendar Settings by Library',
			'description' => 'Create calendar_display_setting_library_table.',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS calendar_display_setting_library (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					calendarDisplaySettingId INT NOT NULL,
					libraryId INT NOT NULL
				) ENGINE = InnoDB',
			]
		], //calendar_display_setting_library

		// Myranda - Grove

		//Yanjun Li - ByWater

		// Leo Stoyanov - BWS
		'web_resource_library_urls' => [
			'title' => 'Web Resource Library-Specific URLs',
			'description' => 'Add support for library-specific URLs in Web Resources, allowing different libraries to use different URLs for the same resource. This enables use cases like Massachusetts statewide databases where each library has their own URL for the same resource.',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE library_web_builder_resource ADD COLUMN IF NOT EXISTS url VARCHAR(500) DEFAULT NULL"
			]
		], //web_resource_library_urls
		'indexing_profile_displayTitleStripRegex' => [
			'title' => 'Indexing Profile - Display Title Strip Regex',
			'description' => 'Add regex field to the Indexing Profile to strip text from display titles of ILS records.',
			'sql' => [
				'ALTER TABLE indexing_profiles ADD COLUMN IF NOT EXISTS displayTitleStripRegex TEXT'
			]
		], //indexing_profile_displayTitleStripRegex
		'record_grouping_overrides' => [
			'title' => 'Create Record Grouping Overrides Table',
			'description' => 'Create table to store record-level grouping overrides that force specific records to stay in specific grouped works.',
			'continueOnError' => false,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS record_grouping_overrides (
					id INT AUTO_INCREMENT PRIMARY KEY,
					source VARCHAR(50) NOT NULL,
					record_id VARCHAR(50) NOT NULL,
					grouped_work_permanent_id VARCHAR(40) NOT NULL,
					added_by INT,
					date_added INT,
					UNIQUE KEY unique_record (source, record_id),
					KEY grouped_work_permanent_id_idx (grouped_work_permanent_id),
					KEY date_added_idx (date_added)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
			]
		], //record_grouping_overrides
		'add_hide_manifestations_in_mobile_view_setting' => [
			'title' => 'Add Hide Manifestations in Mobile View Setting',
			'description' => 'Allow libraries to control whether grouped work formats are condensed on mobile devices.',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE grouped_work_display_settings ADD COLUMN IF NOT EXISTS hideManifestationsInMobileView TINYINT(1) DEFAULT 1'
			]
		], // add_hide_manifestations_in_mobile_view_setting
		'grouped_work_display_settings_showItemBarcodes' => [
			'title' => 'Grouped Work Display Settings - Show Item Barcodes',
			'description' => 'Add option to show item barcodes in copy details.',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE grouped_work_display_settings ADD COLUMN IF NOT EXISTS showItemBarcodes TINYINT(1) DEFAULT 0",
			],
		], //grouped_work_display_settings_showItemBarcodes
		'add_theme_soft_delete_columns' => [
			'title' => 'Add Soft Delete Columns to Themes',
			'description' => 'Add metadata needed to support Object Restorations for Themes.',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE themes ADD COLUMN IF NOT EXISTS deleted TINYINT(1) DEFAULT 0",
				"ALTER TABLE themes ADD COLUMN IF NOT EXISTS dateDeleted INT(11) DEFAULT 0",
				"ALTER TABLE themes ADD COLUMN IF NOT EXISTS deletedBy INT(11) DEFAULT NULL",
			],
		], //add_theme_soft_delete_columns
		'manually_grouped_works' => [
			'title' => 'Manual Grouped Works',
			'description' => 'Add tables to support manually creating and managing grouped works',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS manually_grouped_works (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					title VARCHAR(500) NOT NULL,
					description TEXT,
					created_by INT NOT NULL,
					date_created INT NOT NULL,
					last_updated INT NOT NULL,
					INDEX (title)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

				"CREATE TABLE IF NOT EXISTS manually_grouped_work_records (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					manually_grouped_work_id INT NOT NULL,
					type VARCHAR(50) NOT NULL,
					identifier VARCHAR(150) NOT NULL,
					user_provided_identifier VARCHAR(255) NULL,
					identifier_type ENUM('record_id', 'isbn', 'barcode') NOT NULL DEFAULT 'record_id',
					date_added INT NOT NULL,
					CONSTRAINT manually_grouped_work_records_work_id FOREIGN KEY (manually_grouped_work_id) 
					REFERENCES manually_grouped_works(id) ON DELETE CASCADE,
					UNIQUE KEY unique_manual_group_record (manually_grouped_work_id, type, identifier)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
			],
		],
		'add_description_for_grouped_work_display_info' => [
			'title' => 'Add Description For Grouped Work Display Info',
			'description' => 'Store the grouped work\'s custom description in grouped_work_display_info.',
			'sql' => [
				"ALTER TABLE grouped_work_display_info ADD COLUMN IF NOT EXISTS description TEXT NULL",
			],
		], //add_description_for_grouped_work_display_info

		//mark - grove
		'add_grouped_work_permanent_id_to_manually_grouped_works'=> [
			'title' => 'Add grouped work permanent id to manually grouped works',
			'description' => 'Add the permanent id of the grouped work that is created.',
			'sql' => [
				"ALTER TABLE manually_grouped_works ADD COLUMN IF NOT EXISTS grouped_work_permanent_id VARCHAR(40)",
			],
		], //add_grouped_work_permanent_id_to_manually_grouped_works

		//alexander - Open Fifth
		'add_use_library_name_for_maps' => [
			'title' => 'Add Use Library Name For Maps',
			'description' => 'Allow libraries to use library name for google maps',
			'sql' => [
				"ALTER TABLE location ADD COLUMN useLocationNameForMaps TINYINT(1) DEFAULT 0",
			]
		], //add_use_library_name_for_maps
		'change_data_types_for_grapes_js_columns' => [
			'title' => 'Change Data Types For Grapes JS Columns',
			'description' => 'Update column types to allow for longer pages',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE grapes_web_builder MODIFY templateContent LONGTEXT",
				"ALTER TABLE grapes_web_builder MODIFY htmlData LONGTEXT",
				"ALTER TABLE grapes_web_builder MODIFY cssData LONGTEXT",
			]
		], //change_data_types_for_grapes_js_columns
		'add_staff_complete_email_sent_tracking_for_user_campaigns' => [
			'title' => 'Add Staff Complete Email Sent Tracking For User Campaigns',
			'description' => 'Add tracking to check whether the email has been sent to staff to track completion of the user campaign',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE ce_user_campaign ADD COLUMN staffCampaignCompleteEmailSent TINYINT(1) DEFAULT 0",
			]
		], //add_staff_complete_email_sent_tracking_for_user_campaigns

		//chloe - Open Fifth


		//Jacob - Open Fifth

		//Pedro - Open Fifth


		//James Staub - Nashville Public Library

		//Lucas Montoya - Theke Solutions
		'forceDebugLog' => [
			'title' => 'Enable Forced Logging of Debugging Information for WorldPay Payments',
			'description' => 'Enable to show debugging information about WorldPay payments regardless of whether the user IP is authorized or not',
			'sql' => [
				'ALTER TABLE worldpay_settings ADD COLUMN forceDebugLog TINYINT(1) DEFAULT 0',
			]
		], //enable_worldpay_debug_logging

		//other

		//Talpa Search

		// Brendan Lawlor

	];
}
