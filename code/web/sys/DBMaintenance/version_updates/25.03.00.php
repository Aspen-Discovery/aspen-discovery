<?php

function getUpdates25_03_00(): array {
	$curTime = time();
	return [
		/*'name' => [
			 'title' => '',
			 'description' => '',
			 'continueOnError' => false,
			 'sql' => [
				 ''
			 ]
		 ], //name*/

		//Yanjun Li - ByWater
		'prefer_ils_description' => [
			'title' => 'Prefer ILS Description',
			'description' => 'Add a new setting to prefer ILS Description over eContent Description',
			'sql' => [
				"ALTER TABLE grouped_work_display_settings ADD COLUMN preferIlsDescription TINYINT(1) DEFAULT 0",
			]
		], //prefer_ils_description

		//mark - Grove
		'make_app_icons_os_specific' => [
			'title' => 'Make App Icons OS Specific',
			'description' => 'Update settings to store separate icons per OS',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE aspen_lida_branded_settings add COLUMN logoAppIconAndroid varchar(100) DEFAULT NULL'
			]
		], //make_app_icons_os_specific
		'remove_unused_updates_properties' => [
			'title' => 'Remove unused updates properties',
			'description' => 'Update system variables to remove unused columns',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE system_variables DROP COLUMN allowScheduledUpdates',
				'ALTER TABLE system_variables DROP COLUMN doQuickUpdates',
			]
		], //remove_unused_updates_properties
		'theme_app_header_logo' => [
			'title' => 'Theme - App Header Logo',
			'description' => 'Allow an app header logo to be added for display in LiDA',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE themes ADD COLUMN headerLogoApp VARCHAR(100) DEFAULT NULL',
			]
		], //theme_app_header_logo
		'move_uploaded_list_images' => [
			'title' => 'Move uploaded images',
			'description' => "Move uploaded images uploaded to their own directory so they don't conflict with uploaded records",
			'sql'=> [
				'moveUploadedListImages'
			]
		], //move_list_images

		//katherine - Grove

		'add_series_settings' => [
			'title' => 'Add Series Search settings to Library Systems',
			'description' => 'Add Series Search settings to Library Systems',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE library ADD COLUMN useSeriesSearchIndex TINYINT(1) DEFAULT 0"
			]
		], //add_series_settings
		'add_series_module' => [
			'title' => 'Create Series module',
			'description' => 'Setup modules for Series Search',
			'sql' => [
				"INSERT INTO modules (name, indexName, backgroundProcess, logClassPath, logClassName, settingsClassPath, settingsClassName) VALUES ('Series', 'series', 'series_indexer', '/sys/Series/SeriesIndexingLogEntry.php', 'SeriesIndexingLog', '/sys/Series/SeriesIndexingSettings.php', 'SeriesIndexingSettings')",
			],
		], // add_series_module
		'series_log_class_name' => [
			'title' => 'Update Series Log Class Name',
			'description' => 'Update Series Log Class Name',
			'sql' => [
				"UPDATE modules set logClassName='SeriesIndexingLogEntry' where name = 'Series'",
			],
		], // series_log_class_name
		'add_administer_series_permission' => [
			'title' => 'Manage Series Permission',
			'description' => 'Add new permission to manage series',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Local Enrichment', 'Administer Series', 'Series', 12, 'Allows an administrator to add and modify series.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Series'))",
			],
		], //add_administer_series
		'add_series_search_tables' => [
			'title' => 'Add Series Search tables',
			'description' => 'Add Series Search tables',
			'continueOnError' => true,
			'sql' => [
				"CREATE TABLE series (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					groupedWorkSeriesTitle VARCHAR(500),
					displayName VARCHAR(500),
					author VARCHAR(500),
					description TEXT,
					audience TINYTEXT,
					cover VARCHAR(100),
					isIndexed TINYINT(1) DEFAULT 1,
					deleted TINYINT(1) DEFAULT 0,
					dateUpdated INT(11),
					created INT(11)
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci",
				"CREATE TABLE series_member (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					seriesId INT NOT NULL,
					isPlaceholder TINYINT(1) DEFAULT 0,
					groupedWorkPermanentId CHAR(40),
					displayName VARCHAR(500),
					author VARCHAR(200),
					description TEXT,
					cover VARCHAR(100),
					volume VARCHAR(50),
					pubDate INT,
					weight INT NOT NULL DEFAULT 0,
					userAdded TINYINT(1) DEFAULT 0
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci",
				"CREATE TABLE series_indexing_log (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					startTime INT(11) NOT NULL,
					endTime INT(11) DEFAULT NULL,
					lastUpdate INT(11) DEFAULT NULL,
					notes MEDIUMTEXT DEFAULT NULL,
					numSeries INT(11) DEFAULT 0,
					numAdded INT(11) DEFAULT 0,
					numDeleted INT(11) DEFAULT 0,
					numUpdated INT(11) DEFAULT 0,
					numSkipped INT(11) DEFAULT 0,
					numErrors INT(11) DEFAULT 0
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci",
				"CREATE TABLE series_indexing_settings (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					runFullUpdate TINYINT(1) DEFAULT 1,
					lastUpdateOfChangedSeries INT(11) DEFAULT 0,
					lastUpdateOfAllSeries INT(11) DEFAULT 0
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci",
				"INSERT INTO series_indexing_settings VALUES (1,1,0,0);",
			]
		], //add_series_tables
		'add_excluded_column_to_series_member' => [
			'title' => 'Add excluded column to series_member',
			'description' => 'Add excluded column to series_member table',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE series_member ADD COLUMN excluded TINYINT(1) DEFAULT 0;'
			],
		], // add_excluded_column_to_series_member
		'add_series_indexes' => [
			'title' => 'Add Series Indexes',
			'description' => 'Add Indexes to series table for efficient querying',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE series_member ADD INDEX groupedWorkPermanentId(groupedWorkPermanentId)',
				'ALTER TABLE series_member ADD INDEX seriesId(seriesId)',
    			'ALTER TABLE series_member ADD INDEX volume(volume)',
				'ALTER TABLE series_member ADD INDEX displayName(displayName)',
				'ALTER TABLE series_member ADD INDEX author(author)',
				'ALTER TABLE series ADD INDEX groupedWorkSeriesTitle(groupedWorkSeriesTitle)',
				'ALTER TABLE series_member ADD INDEX seriesWorkId(seriesId, groupedWorkPermanentId, userAdded)',
				'ALTER TABLE series ADD INDEX displayName(displayName)',
				'ALTER TABLE series ADD INDEX author(author)',
			]
		], //add_series_indexes
		'update_series_character_sets' => [
			'title' => 'Update Series Character Sets',
			'description' => 'Update series tables to use multi-byte character sets',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE series CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci',
				'ALTER TABLE series_member CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci',
				'ALTER TABLE series_indexing_settings CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci',
				'ALTER TABLE series_indexing_log CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci'
			]
		], //update_series_character_sets

		'track_event_length_in_minutes' => [
			'title' => 'Track Event Length In Minutes',
			'description' => 'Multiply existing event lengths by 60 to get minutes',
			'sql' => [
				'UPDATE event SET eventLength = eventLength * 60;',
				'UPDATE event_instance SET length = length * 60;'
			]
		], //track_event_length_in_minutes
		'event_calendar_display_settings' => [
			'title' => 'Event Calendar Display Settings',
			'description' => 'Add table to store calendar display settings',
			'sql' => [
				"CREATE TABLE calendar_display_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(50),
					cover VARCHAR(100),
					altText VARCHAR(100)
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci",
			]
		], //event_calendar_display_settings
		'add_event_column_week_number' => [
			'title' => 'Add weekNumber column to Event table',
			'description' => 'Add weekNumber column to Event table',
			'sql' => [
				"ALTER TABLE event ADD COLUMN weekNumber TINYINT DEFAULT 1"
			]
		], //add_event_column_week_number
		'separate_library_events_settings_and_library_events_facet_settings' => [
			'title' => 'Separate library event settings from library event facet settings',
			'description' => 'Create a separate table to store library event facet settings so that these can be controlled independently',
			'continueOnError' => true,
			'sql' => [
				"CREATE TABLE library_events_facet_setting (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					libraryId INT NOT NULL,
					eventsFacetGroupId INT NOT NULL
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci",
				"INSERT INTO library_events_facet_setting (libraryId, eventsFacetGroupId) SELECT DISTINCT libraryId, eventsFacetSettingsId FROM library_events_setting;"
			]
		], //separate_library_events_settings_and_library_events_facet_settings
		'update_events_character_sets' => [
			'title' => 'Update Event Character Sets',
			'description' => 'Update event and event type tables to use multi-byte character sets',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE event CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci',
				'ALTER TABLE event_type CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci'
			]
		], //update_events_character_sets
		'remove_system_variable_to_enable_aspen_events' => [
			'title' => 'Remove enableAspenEvents from System Variables',
			'description' => 'Do not require Aspen Events to be turned on in System Variables',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE system_variables DROP COLUMN enableAspenEvents;'
			]
		], //remove_system_variable_to_enable_aspen_events
		'add_sublocationId_to_event_instances' => [
			'title' => 'Add sublocation to Event Instances',
			'description' => 'Add a sublocation field to event instances to allow override of the overall event sublocation',
			'sql' => [
				'ALTER TABLE event_instance ADD sublocationId INT'
			]
		],
		'change_event_event_field_and_default_field_value_to_text' => [
			'title' => 'Change event_event_field value and event_field default value to TEXT',
			'description' => 'Change the value and default value to TEXT to allow longer entries',
			'sql' => [
				'ALTER TABLE event_event_field MODIFY COLUMN value TEXT',
				'ALTER TABLE event_field MODIFY COLUMN defaultValue TEXT'
			]
		],
		//kirstien - Grove

		//kodi - Grove
		'custom_web_resource_pages_permissions' => [
			'title' => 'Custom Web Resource Page Permissions',
			'description' => 'Setup permissions for Custom Web Resource Pages',
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES
					('Web Builder', 'Administer All Custom Web Resource Pages', 'Web Builder', 152, 'Allows the user to define custom web resource pages for all libraries.'),
					('Web Builder', 'Administer Library Custom Web Resource Pages', 'Web Builder', 153, 'Allows the user to define custom web resource pages for their home library.')",
			],
		], //custom_web_resource_pages_roles
		'custom_web_resource_pages_roles_for_permissions' => [
			'title' => 'Custom Web Resource Page Roles',
			'description' => 'Setup roles for Custom Web Resource Page Permissions',
			'sql' => [
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer All Custom Web Resource Pages'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='Web Admin'), (SELECT id from permissions where name='Administer All Custom Web Resource Pages'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='Library Web Admin'), (SELECT id from permissions where name='Administer Library Custom Web Resource Pages'))"
			],
		], //custom_web_resource_pages_roles_for_permissions
		'create_custom_web_resource_page_table' => [
			'title' => 'Create Custom Web Resource Page Table',
			'description' => 'Create custom web resource page table',
			'sql' => [
				"DROP TABLE IF EXISTS web_builder_custom_web_resource_page",
				"CREATE TABLE IF NOT EXISTS web_builder_custom_web_resource_page (
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					title VARCHAR(100),
					urlAlias VARCHAR(100),
					addToIndex TINYINT(1) DEFAULT 0,
					requireLogin TINYINT(1) DEFAULT 0,
					requireLoginUnlessInLibrary TINYINT(1) DEFAULT 0,
					lastUpdate INT(11) DEFAULT 0
				) ENGINE=INNODB",
			],
		], //create_custom_web_resource_page_table
		'create_web_builder_custom_resource_page_access_table' => [
			'title' => 'Create Custom Web Resource Page Access Table',
			'description' => 'Create custom web resource page access table',
			'sql' => [
				"DROP TABLE IF EXISTS web_builder_custom_resource_page_access",
				'CREATE TABLE `web_builder_custom_resource_page_access` (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					customResourcePageId INT(11) NOT NULL,
					patronTypeId int(11) NOT NULL,
					UNIQUE KEY `customResourcePageId` (`customResourcePageId`,`patronTypeId`)
				) ENGINE=InnoDB',
			],
		], //create_web_builder_custom_resource_page_access_table
		'create_web_builder_custom_resource_page_audience_table' => [
			'title' => 'Create Custom Web Resource Page Audience Table',
			'description' => 'Create custom web resource page audience table',
			'sql' => [
				"DROP TABLE IF EXISTS web_builder_custom_web_resource_page_audience",
				'CREATE TABLE `web_builder_custom_web_resource_page_audience` (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					customResourcePageId INT(11) NOT NULL,
					audienceId int(11) NOT NULL,
					UNIQUE KEY `customResourcePageId` (`customResourcePageId`,`audienceId`)
				) ENGINE=InnoDB',
			],
		], //create_web_builder_custom_resource_page_access_table
		'create_web_builder_custom_resource_page_category_table' => [
			'title' => 'Create Custom Web Resource Page Category Table',
			'description' => 'Create custom web resource page category table',
			'sql' => [
				"DROP TABLE IF EXISTS web_builder_custom_web_resource_page_category",
				'CREATE TABLE `web_builder_custom_web_resource_page_category` (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					customResourcePageId INT(11) NOT NULL,
					categoryId int(11) NOT NULL,
					UNIQUE KEY `customResourcePageId` (`customResourcePageId`,`categoryId`)
				) ENGINE=InnoDB',
			],
		], //create_web_builder_custom_resource_page_access_table
		'create_library_web_builder_custom_web_resource_page_table' => [
			'title' => 'Create Library Web Resource Page Table',
			'description' => 'Create custom web resource page table',
			'sql' => [
				"DROP TABLE IF EXISTS library_web_builder_custom_web_resource_page",
				'CREATE TABLE IF NOT EXISTS library_web_builder_custom_web_resource_page (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					libraryId INT(11) NOT NULL,
					customResourcePageId INT(11) NOT NULL,
					INDEX libraryId(libraryId),
					INDEX customResourcePageId(customResourcePageId)
				) ENGINE INNODB',
			],
		], //create_library_web_builder_custom_web_resource_page_table
		'portal_cell_custom_image' => [
			'title' => 'Portal Cell Custom Image',
			'description' => 'Add customImage column to web_builder_portal_cell table.',
			'sql' => [
				"ALTER TABLE web_builder_portal_cell ADD COLUMN customImage VARCHAR(255) DEFAULT NULL",
			],
		], //portal_cell_custom_image
		'portal_cell_show_hide_description' => [
			'title' => 'Portal Cell Show/Hide Description for Custom Web Resource Page',
			'description' => 'Add hideDescription column to web_builder_portal_cell table.',
			'sql' => [
				"ALTER TABLE web_builder_portal_cell ADD COLUMN hideDescription TINYINT(1) DEFAULT 0",
			],
		], //portal_cell_show_hide_description
		'create_web_resources_settings_table' => [
			'title' => 'Create Web Resources Settings Table',
			'description' => 'Create custom web resource page table',
			'sql' => [
				"DROP TABLE IF EXISTS web_builder_web_resources_settings",
				'CREATE TABLE IF NOT EXISTS web_builder_web_resources_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(100),
					indexAtoZ TINYINT(1) DEFAULT 0
				) ENGINE INNODB',
			],
		], //create_web_resources_settings_table
		'create_web_resources_to_index_table' => [
			'title' => 'Create Web Resources To Index Table',
			'description' => 'Create custom web resource page table',
			'sql' => [
				"DROP TABLE IF EXISTS web_builder_web_resources_to_index",
				'CREATE TABLE IF NOT EXISTS web_builder_web_resources_to_index (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					webResourcesSettingId INT(11) NOT NULL,
					webResourcePageURL VARCHAR(100),
					webResourcePageType VARCHAR(100),
					customWebResourcePageId VARCHAR(100),
					webResourceAudienceId VARCHAR(100),
					webResourceCategoryId VARCHAR(100)
				) ENGINE INNODB',
			],
		], //create_web_resources_to_index_table
		'add_web_resources_setting_id_to_library_table' => [
			'title' => 'Web Resources Setting ID',
			'description' => 'Add web resources setting ID to library table',
			'sql' => [
				"ALTER TABLE library ADD COLUMN webResourcesSettingId INT(11) DEFAULT -1",
			],
		], //add_web_resources_setting_id_to_library_table
		'placard_source_type' => [
			'title' => 'Placard Source Type',
			'description' => 'Add sourceType and sourceId columns to placards table.',
			'sql' => [
				"ALTER TABLE placards ADD COLUMN sourceType VARCHAR(30) DEFAULT NULL",
				"ALTER TABLE placards ADD COLUMN sourceId VARCHAR(30) DEFAULT NULL",
				"ALTER TABLE placards ADD COLUMN generatedFromSource VARCHAR(30) DEFAULT NULL",
				"ALTER TABLE placards ADD COLUMN isCustomized TINYINT(1) DEFAULT 0",
			],
		], //placard_source_type
		'web_resource_generate_placard' => [
			'title' => 'Web Resource: Generate Placard',
			'description' => 'Add option to generate a placard for a web resource.',
			'sql' => [
				"ALTER TABLE web_builder_resource ADD COLUMN generatePlacard TINYINT(1) DEFAULT 0",
			],
		], //web_resource_generate_placard
		'alter_web_builder_custom_web_resource_page_table' => [
			'title' => 'Alter Custom Web Resource Page Table',
			'description' => 'Remove addToIndex column - this is handled in the web_builder_web_resources_to_index table.',
			'sql' => [
				"ALTER TABLE web_builder_custom_web_resource_page DROP COLUMN addToIndex",
			],
		], //alter_web_builder_custom_web_resource_page_table

		// Leo Stoyanov - BWS
		'use_original_cover_urls_settings' => [
			'title' => 'Add Option to Use Original Cover URLs, Last URL Validation to BookCoverInfo, and Original Cover URLs',
			'description' => 'Add an option to allow the use of original cover URLs and the original URL itself rather than cached images in the file system along with tracking last URL validation.',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE system_variables ADD COLUMN IF NOT EXISTS useOriginalCoverUrls TINYINT(1) DEFAULT 0",
				"ALTER TABLE bookcover_info ADD COLUMN IF NOT EXISTS last_url_validation INT(11) DEFAULT NULL",
				"ALTER TABLE bookcover_info ADD COLUMN IF NOT EXISTS original_url TEXT DEFAULT NULL"
			]
		], //use_original_cover_urls_setting

		//alexander - PTFS-Europe

		'filter_books_from_summon_results' => [
			'title' => 'Filter Books From Summon Results',
			'description' => 'Add the option of filtering out records with the content type of book or ebook from Summon results',
			'sql' => [
				"ALTER TABLE summon_settings ADD COLUMN filterOutBooksAndEbooks TINYINT(1) NOT NULL DEFAULT 0",
			],
		], //filter_books_from_summon_results
		'allow_filtering_of_linked_users_in_holds' => [
			'title' => 'Allow Filtering of Linked Users in Holds',
			'description' => 'Allow libraries the option of allowing users to filter their holds by linked user',
			'sql' => [
				'ALTER TABLE library ADD COLUMN allowFilteringOfLinkedAccountsInHolds TINYINT(1) DEFAULT 0',
			]
		], //allow_filtering_of_linked_users_in_holds
		'allow_selecting_holds_to_display' => [
			'title' => 'Allow Selecting Holds to Display',
			'description' => 'Allow libraries the option of allowing users to display only selected holds',
			'sql' => [
				'ALTER TABLE library ADD COLUMN allowSelectingHoldsToDisplay TINYINT(1) DEFAULT 0',
			]
		], //allow_selecting_holds_to_display
		'allow_selecting_holds_to_export' => [
			'title' => 'Allow Selecting Holds to Export',
			'description' => 'Allow libraries the option of allowing users to export only selected holds',
			'sql' => [
				'ALTER TABLE library DROP COLUMN allowSelectingHoldsToDisplay',
				'ALTER TABLE library ADD COLUMN allowSelectingHoldsToExport TINYINT(1) DEFAULT 0'
			]
		], //allow_selecting_holds_to_display

		//chloe - PTFS-Europe

		//James Staub - Nashville Public Library

		//Lucas Montoya - Theke Solutions

		//Yanjun Li - ByWater
		'sierra_self_reg_form_updates' => [
			'title' => 'Sierra Self Reg updates',
			'description' => 'Add new fields to Sierra self registration forms',
			'sql' => [
				'ALTER TABLE self_registration_form_sierra ADD COLUMN selfRegNoticePref CHAR(1) DEFAULT "-"',
				'ALTER TABLE self_registration_form_sierra ADD COLUMN selfRegTelephoneField VARCHAR(5) DEFAULT NULL',
				'ALTER TABLE self_registration_form_sierra ADD COLUMN selfRegExpirationDays INT DEFAULT 30',
				'ALTER TABLE self_registration_form_sierra ADD COLUMN selfRegPcode1 VARCHAR(25) DEFAULT NULL',
				'ALTER TABLE self_registration_form_sierra ADD COLUMN selfRegPcode2 VARCHAR(25) DEFAULT NULL',
				'ALTER TABLE self_registration_form_sierra ADD COLUMN selfRegPcode3 INT DEFAULT NULL',
				'ALTER TABLE self_registration_form_sierra ADD COLUMN selfRegPcode4 INT DEFAULT NULL',
				'ALTER TABLE self_registration_form_sierra ADD COLUMN selfRegPatronMessage VARCHAR(35) DEFAULT NULL',
				'ALTER TABLE self_registration_form_sierra ADD COLUMN selfRegAgency INT DEFAULT NULL',
				'ALTER TABLE self_registration_form_sierra CHANGE COLUMN selfRegPatronCode selfRegPatronType INT DEFAULT NULL',
				'ALTER TABLE self_registration_form_sierra ADD COLUMN selfRegBarcodePrefix VARCHAR(10) DEFAULT NULL',
				'ALTER TABLE self_registration_form_sierra ADD COLUMN selfRegBarcodeSuffixLength INT DEFAULT 7',
				'ALTER TABLE self_registration_form_sierra ADD COLUMN selfRegGuardianField VARCHAR(10) DEFAULT NULL',
			],
		], //sierra_self_reg_form_updates

		//other

	];
}

function moveUploadedListImages(&$update) : void {
	require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
	$uploadedListCovers = new BookCoverInfo();
	$uploadedListCovers->setRecordType('list');
	$uploadedListCovers->setImageSource('upload');
	$uploadedListCovers->find();
	global $configArray;
	$originalPath = $configArray['Site']['coverPath'] . '/original/';
	$newPath = $configArray['Site']['coverPath'] . '/original/lists/';

	if (!file_exists($newPath)) {
		mkdir($newPath, 0755, true);
	}

	$numCoversCopied = 0;
	while($uploadedListCovers->fetch()) {
		$listId = $uploadedListCovers->getRecordId();
		if (file_exists($originalPath . $listId . '.png') && !file_exists($newPath . $listId . '.png')) {
			copy($originalPath . $listId . '.png', $newPath . $listId . '.png');
			$numCoversCopied++;
		}
	}

	$update['status'] = "Moved $numCoversCopied List covers so they will not conflict with records";
	$update['success'] = true;
}