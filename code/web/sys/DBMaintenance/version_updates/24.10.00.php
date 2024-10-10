<?php

function getUpdates24_10_00(): array {
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
		'additional_administration_locations' => [
			'title' => 'Additional Administration Locations',
			'description' => 'Add a table to store additional locations that a user can administer',
			'continueOnError' => false,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS `user_administration_locations` (
					id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					userId INT(11) NOT NULL,
					locationId INT(11) NOT NULL,
					UNIQUE INDEX (userId,locationId)
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;'
			]
		], //additional_administration_locations
		'add_place_holds_for_materials_request_permission' => [
			'title' => 'Add Place Holds For Materials Request Permission',
			'description' => 'Add Place Holds For Materials Request Permission',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Materials Requests', 'Place Holds For Materials Requests', '', 25, 'Allows users to place holds for users that have active Materials Requests once titles are added to the catalog.')",
			]
		], //add_place_holds_for_materials_request_permission
		'add_hold_options_for_materials_request_statuses' => [
			'title' => 'Add Hold Options for Materials Request Statuses',
			'description' => 'Add new options to control what statuses should be used when placing holds for materials requests',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE materials_request_status ADD COLUMN checkForHolds TINYINT(1) DEFAULT 0",
				"ALTER TABLE materials_request_status ADD COLUMN holdPlacedSuccessfully TINYINT(1) DEFAULT 0",
				"ALTER TABLE materials_request_status ADD COLUMN holdFailed TINYINT(1) DEFAULT 0",
			]
		], //add_hold_options_for_materials_request_statuses
		'add_materials_request_format_mapping' => [
			'title' => 'Add Materials Request Format Mapping',
			'description' => 'Add new a new table to define mapping between Aspen Materials Request Formats and Aspen Catalog Formats',
			'continueOnError' => false,
			'sql' => [
				"CREATE TABLE IF NOT EXISTS materials_request_format_mapping (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					libraryId INT(11) NOT NULL,
					catalogFormat VARCHAR(255) NOT NULL,
					materialsRequestFormatId INT(11) NOT NULL ,
					UNIQUE (libraryId, catalogFormat)
				) ENGINE INNODB"
			]
		], //add_materials_request_format_mapping
		'materials_request_ready_for_holds' => [
			'title' => 'Materials Request Ready For Holds',
			'description' => 'Add a new flag to materials requests to indicate they are ready for holds to be placed',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE materials_request ADD COLUMN readyForHolds TINYINT(1) DEFAULT 0'
			]
		], //materials_request_ready_for_holds
		'materials_request_hold_candidates' => [
			'title' => 'Add Materials Request Format Mapping',
			'description' => 'Add new a new table to define mapping between Aspen Materials Request Formats and Aspen Catalog Formats',
			'continueOnError' => false,
			'sql' => [
				"CREATE TABLE IF NOT EXISTS materials_request_hold_candidate (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					requestId INT(11) NOT NULL,
					source VARCHAR(255) NOT NULL,
					sourceId VARCHAR(255) NOT NULL,
					UNIQUE (requestId, source, sourceId)
				) ENGINE INNODB"
			]
		], //materials_request_hold_candidates
		'materials_request_selected_hold_candidate' => [
			'title' => 'Materials Request - Selected Hold Candidate',
			'description' => 'Add new a column to store the selected hold candidate for a request',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE materials_request ADD COLUMN selectedHoldCandidateId INT(11) DEFAULT 0'
			]
		], //materials_request_selected_hold_candidate
		'materials_request_hold_failure_message' => [
			'title' => 'Materials Request - Hold Failure Message',
			'description' => 'Add new a column to failure message when placing a hold',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE materials_request ADD COLUMN holdFailureMessage TEXT'
			]
		], //materials_request_hold_failure_message
		'update_default_request_statuses' => [
			'title' => 'Update default material request statuses',
			'description' => 'Add new material request statuses',
			'continueOnError' => false,
			'sql' => [
				"UPDATE materials_request_status SET isOpen = 1, checkForHolds = 1 where description='Item purchased' and libraryId = -1",
				"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen, holdPlacedSuccessfully, libraryId) 
					VALUES ('Hold Placed', 1, '{title} has been received by the library and you have been added to the hold queue. 

Thank you for your purchase suggestion!', 0, 1, -1)",
				"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen, holdFailed, libraryId) 
					VALUES ('Hold Failed', 1, '{title} has been received by the library, however we were not able to add you to the hold queue. Please ensure that your account is in good standing and then visit our catalog to place your hold.

	Thanks', 0, 1, -1)",
			]
		], //update_default_request_statuses
		'materials_request_hold_candidate_generation_log' => [
			'title' => 'Materials Request Hold Candidate Generation Log',
			'description' => 'Create a table to store information about generating hold candidates for materials requests',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS materials_request_hold_candidate_generation_log (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					startTime INT NOT NULL,
					endTime INT, 
					numRequestsChecked INT DEFAULT 0,
					numRequestsWithNewSuggestions INT DEFAULT 0,
					numSearchErrors INT DEFAULT 0,
					notes TEXT,
					index (startTime)
				) ENGINE INNODB'
			]
		], //materials_request_hold_candidate_generation_log

		//mark - Grove DIS-28 Library cost savings
		'administer_replacement_costs_permission' => [
			'title' => 'Add Administer Replacement Costs Permission',
			'description' => 'Add Administer Replacement Costs Permission',
			'continueOnError' => false,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration', 'Administer Replacement Costs', '', 100, 'Allows users to administer replacement costs for all libraries.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Replacement Costs'))",
			]
		], //administer_replacement_costs_permission
		'library_enable_cost_savings' => [
			'title' => 'Library Enable Cost Savings',
			'description' => 'Add new settings  to determine if costs savings functionality is enabled within a library',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE library add column enableCostSavings TINYINT(0) DEFAULT 0",
			]
		], //library_enable_cost_savings
		'user_cost_savings' => [
			'title' => 'User Cost Savings',
			'description' => 'Add fields to store information for the user related to library cost savings',
			'sql' => [
				"ALTER TABLE user add column enableCostSavings TINYINT(0) DEFAULT 0",
				"ALTER TABLE user add column totalCostSavings DECIMAL(10,2) DEFAULT 0",
				"ALTER TABLE user add column currentCostSavings DECIMAL(10,2) DEFAULT 0",
			],
		], //user_cost_savings
		'replacement_costs' => [
			'title' => 'Replacement Costs',
			'description' => 'Create a table to store replacement costs',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS replacement_costs (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					catalogFormat VARCHAR(255) NOT NULL UNIQUE,
					replacementCost DECIMAL(10,2) DEFAULT 0
				) ENGINE InnoDB DEFAULT CHARSET=utf8"
			]
		], //replacement_costs
		'indexing_profile_replacement_cost_subfield' => [
			'title' => 'Indexing Profile - Replacement Cost',
			'description' => 'Add a replacement cost subfield to Indexing profile',
			'sql' => [
				"ALTER TABLE indexing_profiles ADD COLUMN replacementCostSubfield CHAR(1) DEFAULT ''",
				"UPDATE indexing_profiles SET replacementCostSubfield = 'v' WHERE catalogDriver = 'Koha'"
			]
		], //indexing_profile_replacement_cost_subfield
		'reading_history_entry_cost_savings' => [
			'title' => 'Reading History Entry - Replacement Cost',
			'description' => 'Add a field to store cost savings for a reading history entry',
			'sql' => [
				'ALTER TABLE user_reading_history_work ADD COLUMN costSavings DECIMAL(10,2) DEFAULT 0',
			]
		], //reading_history_entry_cost_savings

		//katherine - ByWater

		//kirstien - ByWater
		'add_enableSelfRegistration_LiDA' => [
			'title' => 'Add Enable Self-Registration to General LiDA Settings',
			'description' => 'Add Enable Self-Registration to General LiDA Settings',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE aspen_lida_general_settings ADD COLUMN enableSelfRegistration TINYINT DEFAULT 0'
			]
		], //add_enableSelfRegistration_LiDA

		//kodi - ByWater

		//alexander - PTFS-Europe
		'update_cookie_management_preferences_more_options' => [
			'title' => 'Update Cookie Management Preferences: More Options',
			'description' => 'Update cookie management preferences for user tracking - adding more options',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE user ADD COLUMN userCookiePreferenceEvents TINYINT(1) DEFAULT 0",
				"ALTER TABLE user ADD COLUMN userCookiePreferenceOpenArchives TINYINT(1) DEFAULT 0",
				"ALTER TABLE user ADD COLUMN userCookiePreferenceWebsite TINYINT(1) DEFAULT 0",
				"ALTER TABLE user ADD COLUMN userCookiePreferenceExternalSearchServices TINYINT(1) DEFAULT 0",
			],
		], 
		'add_local_analytics_column_to_user' => [
			'title' => 'Add Local Analytics Column To User',
			'description' => 'Add a column to hold local analytics tracking choices',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE user ADD COLUMN userCookiePreferenceLocalAnalytics TINYINT(1) DEFAULT 0",
				"UPDATE user
					INNER JOIN location ON user.homeLocationId = location.locationId
					INNER JOIN library ON location.libraryId = library.libraryId
					SET user.userCookiePreferenceLocalAnalytics = CASE
						WHEN library.cookieStorageConsent = 0 THEN 1
						ELSE 0
					END"
			],
		],
		'drop_columns_from_user_table' => [
			'title' => 'Drop Columns From User Table',
			'description' => 'Remove unneeded columns from user table',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE user DROP COLUMN userCookiePreferenceEvents",
				"ALTER TABLE user DROP COLUMN userCookiePreferenceOpenArchives",
				"ALTER TABLE user DROP COLUMN userCookiePreferenceWebsite",
				"ALTER TABLE user DROP COLUMN userCookiePreferenceExternalSearchServices",
			],
		],
		'add_analytics_data_cleared_flag' => [
			'title' => 'Add Analytics Data Cleared Flag',
			'description' => 'Add a flag to ensure analytics data clearing fucntion runs only once',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE user ADD COLUMN analyticsDataCleared TINYINT DEFAULT 0",
			],
		],

		//chloe - PTFS-Europe

		//pedro - PTFS-Europe

		//James Staub - Nashville Public Library
		'drop_snappayToken_column' => [
			'title' => 'Drop SnapPay Token column from User Payments',
			'description' => 'Drop SnapPay Token column from User Payments',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE user_payments DROP COLUMN snappayToken',
			]
		], //drop_snappayToken_column

		//Jeremy Eden - Howell Carnegie District Library
		'add_openarchives_dateformatting_field' => [
			'title' => 'Add Open Archives date formatting setting',
			'description' => 'Add Open Archives date formatting setting',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE open_archives_collection ADD COLUMN dateFormatting tinyint default 1',
			]
		], //add_defaultContent_field

		//other

	];
}