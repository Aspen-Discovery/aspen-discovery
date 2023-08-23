<?php
/** @noinspection PhpUnused */
function getUpdates23_08_10(): array {
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


		//mark - ByWater
		'split_user_fields' => [
			'title' => 'Split User Fields',
			'description' => 'Split up user fields including barcode and username',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE user ADD COLUMN unique_ils_id varchar(36) COLLATE utf8mb4_general_ci NOT NULL",
				"ALTER TABLE user ADD COLUMN ils_barcode varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL",
				"ALTER TABLE user ADD COLUMN ils_username varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL",
				"ALTER TABLE user ADD COLUMN ils_password varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL",
				"UPDATE user set unique_ils_id = username where source NOT IN ('admin', 'admin_sso')",
				"UPDATE user set ils_barcode = cat_username where source NOT IN ('admin', 'admin_sso')",
				"UPDATE user set ils_password = cat_password where source NOT IN ('admin', 'admin_sso')",
				"UPDATE user set cat_username = '' where source IN ('admin', 'admin_sso')",
				"UPDATE user set cat_password = '' where source IN ('admin', 'admin_sso')",
			],
		],
		//split_user_fields
		'add_number_of_days_to_index_to_event_indexers' => [
			'title' => 'Add number of days to index to event indexers',
			'description' => 'Add number of days to index to event indexers',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE communico_settings ADD COLUMN numberOfDaysToIndex INT DEFAULT 365',
				'ALTER TABLE springshare_libcal_settings ADD COLUMN numberOfDaysToIndex INT DEFAULT 365',
				'ALTER TABLE lm_library_calendar_settings ADD COLUMN numberOfDaysToIndex INT DEFAULT 365',
			],
		],
		//add_number_of_days_to_index_to_event_indexers

		//kodi - ByWater
		'permissions_events_facets' => [
			'title' => 'Alters permissions for Events Facets',
			'description' => 'Create permissions for altering events facets',
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'Administer Events Facet Settings', 'Events', 20, 'Allows the user to alter events facets for all libraries.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Events Facet Settings'))",
			],
		],
		//permissions_events_facets
		'events_facets' => [
			'title' => 'Events Facet Tables',
			'description' => 'Adds tables for events facets',
			'sql' => [
				"CREATE TABLE events_facet_groups (
					id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(255) NOT NULL UNIQUE,
					eventFacetCountsToShow TINYINT DEFAULT 1
				)",
				"CREATE TABLE events_facet (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					facetGroupId INT NOT NULL, 
					displayName VARCHAR(50) NOT NULL, 
					displayNamePlural VARCHAR(50),
					facetName VARCHAR(50) NOT NULL,
					weight INT NOT NULL DEFAULT '0',
					numEntriesToShowByDefault INT NOT NULL DEFAULT '5',
					showAsDropDown TINYINT NOT NULL DEFAULT '0',
					sortMode ENUM ('alphabetically', 'num_results') NOT NULL DEFAULT 'num_results',
					showAboveResults TINYINT NOT NULL DEFAULT '0',
					showInResults TINYINT NOT NULL DEFAULT '1',
					showInAdvancedSearch TINYINT NOT NULL DEFAULT '1',
					collapseByDefault TINYINT DEFAULT '1',
					useMoreFacetPopup TINYINT DEFAULT 1,
					translate TINYINT DEFAULT 0,
					multiSelect TINYINT DEFAULT 0,
					canLock TINYINT DEFAULT 0
				) ENGINE = InnoDB",
				"ALTER TABLE events_facet ADD UNIQUE groupFacet (facetGroupId, facetName)",
			],
		],
		//events_facets
		'events_facets_default' => [
			'title' => 'Events Facet Default Values',
			'description' => 'Adds a default event facet group that applies to all libraries unless edited',
			'sql' => [
				"INSERT INTO events_facet_groups (id, name) VALUES (1, 'default')",
				"INSERT INTO events_facet VALUES 
                             (1,1, 'Age Group/Audience', 'Age Groups/Audiences', 'age_group_facet', 1, 5, 0, 'num_results', 0, 1, 1, 1, 1, 0, 1, 1),
                             (2,1, 'Branch', 'Branches', 'branch', 2, 5, 0, 'num_results', 0, 1, 1, 1, 1, 0, 1, 1),
                             (3,1, 'Room', 'Rooms', 'room', 3, 5, 0, 'num_results', 0, 1, 1, 1, 1, 0, 1, 1),
                             (4,1, 'Event Type', 'Event Types', 'event_type', 4, 5, 0, 'num_results', 0, 1, 1, 1, 1, 0, 1, 1),
                             (5,1, 'Program Type', 'Program Types', 'program_type_facet', 5, 5, 0, 'num_results', 0, 1, 1, 1, 1, 0, 1, 1),
                             (6,1, 'Registration Required?', 'Registration Required?', 'registration_required', 6, 5, 0, 'num_results', 0, 1, 1, 1, 1, 0, 1, 1),
                             (7,1, 'Category', 'Categories', 'internal_category', 7, 5, 0, 'num_results', 0, 1, 1, 1, 1, 0, 1, 1),
                             (8,1, 'Reservation State', 'Reservation State', 'reservation_state', 8, 5, 0, 'num_results', 0, 1, 1, 1, 1, 0, 1, 1),
                             (9,1, 'Event State', 'Event State', 'event_state', 9, 5, 0, 'num_results', 0, 1, 1, 1, 1, 0, 1, 1)",

			],
		],
		'events_start_date_facet' => [
			'title' => 'Events Start Date Facet',
			'description' => 'Adds a facet for Event date to the default facet group',
			'sql' => [
				"INSERT INTO events_facet VALUES 
                             (10,1, 'Event Date', 'Event Dates', 'start_date', 10, 5, 0, 'num_results', 0, 1, 1, 1, 1, 0, 1, 1)",

			],
		],
		//events_facets_default
		'events_facet_settingsId' => [
			'title' => "Events Facet Settings Id for Library",
			'description' => "Adds column for events facet settings id to library_events_setting table",
			'sql' => [
				"ALTER TABLE library_events_setting ADD COLUMN eventsFacetSettingsId INT(11) DEFAULT 1",
			],
		],
		//events_facet_settingsId

		// kirstien - ByWater
		'checkoutIsILL' => [
			'title' => 'Checkout - Is ILL',
			'description' => 'Add a property to determine if a checkout is ILL',
			'sql' => [
				'ALTER TABLE user_checkout ADD COLUMN isIll TINYINT(1) DEFAULT 0',
			],
		],
		//checkoutIsILL
		'readingHistoryIsILL' => [
			'title' => 'Reading History Work - Is ILL',
			'description' => 'Add a property to determine if a reading history work is ILL',
			'sql' => [
				'ALTER TABLE user_reading_history_work ADD COLUMN isIll TINYINT(1) DEFAULT 0',
			],
		],
		//readingHistoryIsILL
		'add_ill_itype' => [
			'title' => 'Add table to store ILL item type codes',
			'description' => 'Adds tables for ILL item type codes',
			'sql' => [
				'CREATE TABLE library_ill_item_type (
					id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					libraryId INT(11) NOT NULL,
					code VARCHAR(75)
				) ENGINE = InnoDB',
			],
		],
		//add_ill_itype
	];
}