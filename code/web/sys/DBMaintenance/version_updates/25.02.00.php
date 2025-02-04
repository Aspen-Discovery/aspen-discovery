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

		//katherine

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
		// Leo Stoyanov - BWS
		'deduplicate_reading_history' => [
			'title' => 'De-duplicate reading history & add unique key',
			'description' => 'Combine multiple rows per (userId, sourceId) into one row before adding unique key.',
			'continueOnError' => true,
			'sql' => [
				// 1. Keep the latest entry of the duplicated titles.
				"DELETE t1 FROM user_reading_history_work t1
				INNER JOIN user_reading_history_work t2 
				WHERE 
					t1.id < t2.id AND
					t1.userId = t2.userId AND
					t1.sourceId = t2.sourceId;",

				// 2. Create the unique index.
				"ALTER TABLE user_reading_history_work 
				ADD UNIQUE INDEX user_source (userId, sourceId);"
			],
		], //deduplicate_reading_history

		//Lucas Montoya - Theke Solutions

		//other

	];
}
