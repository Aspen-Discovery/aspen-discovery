<?php

function getUpdates25_02_00(): array {
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

		//mark - Grove
		'ptype_account_profile' => [
			'title' => 'Patron Type - Add Account Profile',
			'description' => 'Add Information about which account profile a patron type belongs to',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE ptype ADD COLUMN accountProfileId INT',
				"UPDATE ptype set accountProfileId = (SELECT MIN(id) from account_profiles where ils <> 'na' and name <> 'admin')",
				"ALTER TABLE ptype DROP INDEX ptype",
				"ALTER TABLE ptype ADD UNIQUE INDEX ptype_profile(ptype, accountProfileId)",
			]
		], //ptype_account_profile
		'manage_local_administrators_permission' => [
			'title' => 'Manage Local Administrators Permission',
			'description' => 'Add new permission to manage local administrators',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('System Administration', 'Manage Local Administrators', '', 12, 'Allows an administrator to add, edit, and delete local administrators.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='userAdmin'), (SELECT id from permissions where name='Manage Local Administrators'))",
			],
		], //manage_local_administrators_permission
		'account_profile_admin_updates' => [
			'title' => 'Admin Account Profile Updates',
			'description' => 'Clean up default admin account profile',
			'continueOnError' => true,
			'sql' => [
				"UPDATE account_profiles set vendorOpacUrl = '', patronApiUrl = '', ils = 'na', driver = '', recordSource = '' where name = 'admin'",
			],
		], //account_profile_admin_updates
		'two_factor_authentication' => [
			'title' => 'Two Factor Authentication Updates',
			'description' => 'Remove unused settings and add new link to account profile for two factor authentication',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE two_factor_auth_settings DROP COLUMN authMethods',
				'ALTER TABLE two_factor_auth_settings ADD COLUMN accountProfileId INT',
				"UPDATE two_factor_auth_settings set accountProfileId = (SELECT MIN(id) from account_profiles where ils <> 'na' and name <> 'admin')"
			]
		], //two_factor_authentication
		'indexing_profile_remove_unused_properties' => [
			'title' => 'Indexing Profile remove unused properties',
			'description' => 'Remove unused properties from indexing profiles',
			'sql' => [
				'ALTER TABLE indexing_profiles DROP column groupingClass',
				'ALTER TABLE indexing_profiles DROP column recordDriver',
			]
		], //indexing_profile_remove_unused_properties
		'sideload_remove_unused_properties' => [
			'title' => 'SideLoad remove unused properties',
			'description' => 'Remove unused properties from sideloads',
			'sql' => [
				'ALTER TABLE sideloads DROP column groupingClass',
				'ALTER TABLE sideloads DROP column recordDriver',
			]
		], //sideload_remove_unused_properties
		// Leo Stoyanov - BWS
		'aggregate_usage_by_user_agent' => [
			'title' => 'Aggregate usage_by_user_agent & add unique key',
			'description' => 'Combine multiple rows per (userAgentId, year, month, instance) into one row before adding unique key',
			'continueOnError' => true,
			'sql' => [
				// 1. Create new empty table with original structure.
				"CREATE TABLE usage_by_user_agent_temp LIKE usage_by_user_agent",

				// 2. Remove old non-unique index from temp table.
				"ALTER TABLE usage_by_user_agent_temp DROP INDEX userAgentId",

				// 3. Insert aggregated data directly into temp table.
				"INSERT INTO usage_by_user_agent_temp (userAgentId, year, month, instance, numRequests, numBlockedRequests)
				 SELECT userAgentId, year, month, instance,
						SUM(numRequests),
						SUM(numBlockedRequests)
				 FROM usage_by_user_agent
				 GROUP BY userAgentId, year, month, instance",

				// 4. Add unique index to temp table before swap.
				"ALTER TABLE usage_by_user_agent_temp 
				 ADD UNIQUE INDEX userAgentId (userAgentId, year, month, instance)",

				// 5. Atomic table swap.
				"DROP TABLE usage_by_user_agent",
				"RENAME TABLE usage_by_user_agent_temp TO usage_by_user_agent",
			],
		], //aggregate_usage_by_user_agent
		'branded_app_api_keys' => [
			'title' => 'Branded App API Keys',
			'description' => 'Add API keys to branded app settings',
			'sql' => [
				"ALTER TABLE aspen_lida_branded_settings ADD COLUMN apiKey1 varchar(256) DEFAULT NULL",
				"ALTER TABLE aspen_lida_branded_settings ADD COLUMN apiKey2 varchar(256) DEFAULT NULL",
				"ALTER TABLE aspen_lida_branded_settings ADD COLUMN apiKey3 varchar(256) DEFAULT NULL",
				"ALTER TABLE aspen_lida_branded_settings ADD COLUMN apiKey4 varchar(256) DEFAULT NULL",
				"ALTER TABLE aspen_lida_branded_settings ADD COLUMN apiKey5 varchar(256) DEFAULT NULL",
			]
		], //branded_app_api_keys
		'library_requestCalendarStartDate' => [
			'title' => 'Library Request Calendar Start Date',
			'description' => 'Add library request calendar start date',
			'sql' => [
				"ALTER TABLE library add COLUMN requestCalendarStartDate CHAR(5) DEFAULT '01-01'"
			]
		], //library_requestCalendarStartDate
		'library_checkRequestsForExistingTitles' => [
			'title' => 'Library - Check Requests for Existing Titles',
			'description' => 'Add a toggle for whether or not requests should be checked for existing titles',
			'sql' => [
				"ALTER TABLE library add COLUMN checkRequestsForExistingTitles TINYINT DEFAULT 1"
			]
		], //library_checkRequestsForExistingTitles
		'materialsRequestExistingTitle' => [
			'title' => 'Materials Request - Existing Title Fields',
			'description' => 'Add fields for determining if a materials request has an existing record in the catalog',
			'sql' => [
				"ALTER TABLE materials_request ADD COLUMN hasExistingRecord TINYINT(1) DEFAULT 0",
				"ALTER TABLE materials_request ADD COLUMN lastCheckForExistingRecord INT DEFAULT -1",
				"ALTER TABLE materials_request ADD COLUMN existingRecordUrl TINYTEXT"
			],
		],

		//katherine
		'native_events_permissions' => [
			'title' => 'Native Events Permissions',
			'description' => 'Add new permissions for native events',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'Administer Field Sets', 'Events', 30, 'Allows the user to administer field sets for native events.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'Administer Event Types', 'Events', 40, 'Allows the user to administer native event types.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'Administer Events for All Locations', 'Events', 50, 'Allows the user to administer native events for all locations.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'Administer Events for Home Library Locations', 'Events', 51, 'Allows the user to administer native events for home library locations.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'Administer Events for Home Location', 'Events', 52, 'Allows the user to administer native events for home location.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'View Private Events for All Locations', 'Events', 60, 'Allows the user to view private native events for all locations.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'View Private Events for Home Library Locations', 'Events', 61, 'Allows the user to view private native events for home library locations.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'View Private Events for Home Location', 'Events', 62, 'Allows the user to view private native events for home location.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'View Event Reports for All Libraries', 'Events', 70, 'Allows the user to view event reports for all libraries.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'View Event Reports for Home Library', 'Events', 71, 'Allows the user to view event reports for their home library.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'Print Calendars with Header Images', 'Events', 80, 'Allows the user to print calendars with header images.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Field Sets'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Event Types'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Events for All Locations'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='View Private Events for All Locations'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='View Event Reports for All Libraries'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Print Calendars with Header Images'))",
			],
		], //native_events_permissions
		'native_event_tables' => [
			'title' => 'Native Event Tables',
			'description' => 'Add new tables for native events',
			'continueOnError' => true,
			'sql' => [
				"CREATE TABLE event_field (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(50) NOT NULL UNIQUE,
					description VARCHAR(100) NOT NULL,
					type TINYINT NOT NULL DEFAULT 0,
					allowableValues VARCHAR(150),
					defaultValue VARCHAR(150),
					facetName INT NOT NULL DEFAULT 0
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci",
				"CREATE TABLE event_field_set (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(50) NOT NULL UNIQUE
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci",
				"CREATE TABLE event_field_set_field (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					eventFieldId INT NOT NULL,
					eventFieldSetId INT NOT NULL
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci",
				"CREATE TABLE event_type (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					eventFieldSetId INT NOT NULL,
					title VARCHAR(50) NOT NULL UNIQUE,
					titleCustomizable TINYINT(1) NOT NULL DEFAULT 1,
					description VARCHAR(100) NOT NULL,
					descriptionCustomizable TINYINT(1) NOT NULL DEFAULT 1,
					cover VARCHAR(100) DEFAULT NULL,
					coverCustomizable TINYINT(1) NOT NULL DEFAULT 1,
					eventLength FLOAT NOT NULL DEFAULT 1,
					lengthCustomizable TINYINT(1) NOT NULL DEFAULT 1,
					archived TINYINT(1) NOT NULL DEFAULT 0
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci",
				"CREATE TABLE event_type_library (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					eventTypeId INT NOT NULL,
					libraryId INT NOT NULL
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci",
				"CREATE TABLE event_type_location (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					eventTypeId INT NOT NULL,
					locationId INT NOT NULL
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci",
				"CREATE TABLE event (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					eventTypeId INT NOT NULL,
					locationId INT NOT NULL,
					sublocationId INT,
					title VARCHAR(50),
					description VARCHAR(500),
					cover VARCHAR(100) DEFAULT NULL,
					private TINYINT(1) NOT NULL DEFAULT 1,
					startDate DATE NOT NULL DEFAULT (CURRENT_DATE),
					startTime TIME NOT NULL DEFAULT (CURRENT_TIME),
					eventLength INT NOT NULL DEFAULT 60,
					recurrenceOption TINYINT,
					recurrenceFrequency TINYINT,
					recurrenceInterval INT NOT NULL DEFAULT 1,
					weekDays VARCHAR(25),
					monthlyOption TINYINT,
					monthDay TINYINT,
					monthDate TINYINT,
					monthOffset TINYINT,
					endOption TINYINT,
					recurrenceEnd DATE,
					recurrenceCount INT
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci",
				"CREATE TABLE event_event_field (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					eventId INT NOT NULL,
					eventFieldId INT NOT NULL,
					value VARCHAR(150) NOT NULL
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci",
				"CREATE TABLE event_instance (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					eventId INT NOT NULL,
					date DATE NOT NULL,
					time TIME NOT NULL,
					length INT NOT NULL,
					status TINYINT(1) NOT NULL DEFAULT 1,
					note VARCHAR(150)
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci",
				"ALTER TABLE sublocation ADD COLUMN isValidEventLocation TINYINT(1) DEFAULT 0",
			]
		], //native_events_tables
		'native_events_indexing_tables' => [
			'title' => 'Native Events Indexing Tables',
			'description' => 'Add new tables for native events related to indexing',
			'continueOnError' => true,
			'sql' => [
				"CREATE TABLE events_indexing_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					numberOfDaysToIndex INT DEFAULT 365,
					runFullUpdate TINYINT(1) DEFAULT 0,
					lastUpdateOfAllEvents INT,
					lastUpdateOfChangedEvents INT
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci",
			]
		], //native_events_indexing_tables
		'native_events_system_variable' => [
			'title' => 'Native Events System Variable',
			'description' => 'Add system variable to turn on native events',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE system_variables ADD COLUMN enableAspenEvents TINYINT(1) DEFAULT 0"
			]
		], //native_events_indexing_tables
		'increase_event_field_lengths' => [
			'title' => 'Increase field lengths for some Aspen Events tables',
			'description' => 'Increase field lengths for some Aspen Events tables',
			'sql' => [
				'ALTER TABLE event_field CHANGE COLUMN allowableValues allowableValues TEXT',
				'ALTER TABLE event CHANGE COLUMN description description TEXT',
				'ALTER TABLE event_type CHANGE COLUMN description description TEXT',
				'ALTER TABLE event_instance CHANGE COLUMN note note TEXT',
			]
		], //increase_event_field_lengths
		'add_dateUpdated_and_deleted_fields_to_event_and_eventInstance' => [
			'title' => 'Add dateUpdated and deleted fields to Event and Event_Instance',
			'description' => 'Add dateUpdated to Event and Event_Instance to allow incremental indexing',
			'sql' => [
				'ALTER TABLE event ADD COLUMN dateUpdated INT(11)',
				'ALTER TABLE event_instance ADD COLUMN dateUpdated INT(11)',
				'ALTER TABLE event ADD COLUMN deleted TINYINT(1) DEFAULT 0 NOT NULL',
				'ALTER TABLE event_instance ADD COLUMN deleted TINYINT(1) DEFAULT 0 NOT NULL',
			]
		], //add_dateUpdated_and_deleted_fields_to_event_and_eventInstance

		//kirstien - Grove
		'lida_general_settings_add_more_info' => [
			'title' => 'Add option in Aspen LiDA General Settings to display More Info button',
			'description' => 'Add option in Aspen LiDA General Settings to display More Info button',
			'sql' => [
				"ALTER TABLE aspen_lida_general_settings add COLUMN showMoreInfoBtn TINYINT(1) DEFAULT 1"
			]
		],
		//lida_general_settings_add_more_info

		//kodi

		//alexander - PTFS-Europe

		//chloe - PTFS-Europe
		'save_library_ils_consent_feature_toggle_value' => [
			'title' => 'Save Library ILS Consent Feature Toggle Value',
			'description' => 'Allows to record whether a library has enabled the ILS Consent feature or not',
			'continueOnError' => false,
			'sql' => ['ALTER TABLE library ADD COLUMN ilsConsentEnabled tinyint(1) DEFAULT 0'],
		], //'save_library_ils_consent_feature_toggle_value'

		//James Staub - Nashville Public Library
		'user_checkout_add_ilsStatus' => [
			'title' => 'User Checkout Add ILS Status',
			'description' => 'Add ILS Status to User Checkout',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE user_checkout ADD COLUMN ilsStatus VARCHAR(50) DEFAULT NULL",
			]
		], //user_checkout_add_ilsStatus
		'user_checkout_add_showFineButton' => [
			'title' => 'User Checkout Add Show Fine Button',
			'description' => 'Add Show Fine Button to User Checkout',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE user_checkout ADD COLUMN showFineButton TINYINT(1) DEFAULT 0",
			]
		], //user_checkout_add_showFineButton

		//Lucas Montoya - Theke Solutions

		//other

	];
}