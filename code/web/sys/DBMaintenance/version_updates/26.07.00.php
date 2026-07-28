<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_07_00(): array {
	$now = time();

	return [
		/*'name' => [
			 'title' => '',
			 'description' => '',
			 'continueOnError' => false,
			 'sql' => [
				 ''
			 ]
		 ], //name*/

		//mark n
		'multi_copy_holds_support' => [
			'title' => 'Add multi-copy holds support',
			'description' => 'Add multi-copy holds support within library table.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library ADD enableMultiCopyHolds TINYINT(1) NOT NULL DEFAULT 0',
			],
		], //multi_copy_holds_support
		'remove_vdx_settings_and_permissions' => [
			'title' => 'Remove VDX settings and permissions',
			'description' => 'Remove VDX settings and permissions.',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE ptype DROP COLUMN vdxClientCategory',
				'ALTER TABLE location DROP COLUMN vdxLocation',
				'ALTER TABLE location DROP COLUMN vdxFormId',
				'DROP TABLE user_vdx_request',
				'DROP TABLE vdx_form',
				'DROP TABLE vdx_settings',
				"DELETE FROM role_permissions where permissionId IN (SELECT id from permissions where name IN ('Administer VDX Settings', 'Administer All VDX Forms', 'Administer Library VDX Forms'))",
				"DELETE FROM permissions where name IN ('Administer VDX Settings', 'Administer All VDX Forms', 'Administer Library VDX Forms')",
			]
		], //remove_vdx_settings_and_permissions
		'remove_vdx_settings_and_permissions_2' => [
			'title' => 'Remove VDX settings and permissions pt 2',
			'description' => 'Removes additional permission.',
			'continueOnError' => true,
			'sql' => [
				"DELETE FROM role_permissions where permissionId IN (SELECT id from permissions where name IN ('Administer VDX Forms'))",
				"DELETE FROM permissions where name IN ('Administer VDX Forms')",
				"UPDATE permissions set description = 'Allows the user to define Hold Groups with Aspen' WHERE name = 'Administer Hold Groups'",
			]
		], //remove_vdx_settings_and_permissions_2
		'remove_vdx_permission_group' => [
			'title' => 'Remove VDX permission group',
			'description' => 'Removes permission group for VDX.',
			'continueOnError' => true,
			'sql' => [
				"DELETE FROM permission_group_permissions where groupId = (SELECT id from permission_groups where groupKey = 'adminVdxForms')",
				"DELETE FROM permission_groups where groupKey = 'adminVdxForms'",
			]
		], //remove_vdx_permission_group

		//kirstien

		//kodi
		'symphony_municipalities' => [
			'title' => 'Add new table for Symphony municipalities',
			'description' => 'Add new table for symphony municipalities for self registration.',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS self_reg_municipality_values_symphony (
					`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					`selfRegistrationFormId` int(11) NOT NULL,
					`municipality` varchar(255) default '' NOT NULL,
					`ilsMunicipality` varchar(255) default '' NOT NULL,
					`municipalityType` varchar(10),
					`selfRegAllowed` tinyint(1) NOT NULL DEFAULT '1'
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
			]
		], //symphony_municipalities
		'symphony_county_codes' => [
			'title' => 'Add new table for County Codes',
			'description' => 'Add new table for symphony county codes for self registration.',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS self_reg_county_code_values_symphony (
					`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					`selfRegistrationFormId` int(11) NOT NULL,
					`countyCode` varchar(255) default '' NOT NULL,
					`countyName` varchar(255) default '' NOT NULL,
					UNIQUE (countyCode)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
			]
		], //symphony_county_codes
		'dpla_exclusions' => [
			'title' => 'Add Table for DP.LA Excluded Titles',
			'description' => 'Add table for DP.LA excluded titles.',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS dpla_exclusion_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					dplaLink VARCHAR(255) NOT NULL
				) ENGINE INNODB',
			],
		], // dpla_exclusions
		'permissions_dpla_exclusions' => [
			'title' => 'Alters permissions for DP.LA Exclusions',
			'description' => 'Create permissions for DP.LA Exclusions',
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Third Party Enrichment', 'Administer DP.LA Exclusions', '', 0, 'Allows the user to define DP.LA results exclusions for all libraries.')",
				"INSERT INTO role_permissions (roleId, permissionId) SELECT r.roleId, p.id FROM roles r JOIN permissions p ON p.name = 'Administer DP.LA Exclusions' WHERE r.name = 'opacAdmin'",
			],
		],
		// permissions_create_events_localhop
		'series_to_group_with' => [
			'title' => 'Add Column in Series Table for Grouping',
			'description' => 'Add column for seriesToGroupWithId in Series table to be used for merging/grouping series.',
			'sql' => [
				'ALTER TABLE series ADD COLUMN seriesToGroupWithId CHAR(40)',
			]
		], // series_to_group_with
		'remove_duplicate_series_members' => [
			'title' => 'Remove Duplicate Series Members',
			'description' => 'Remove duplicate series members.',
			'continueOnError' => false,
			'sql' => [
				'removeDuplicateSeriesMembers',
			]
		], //remove_duplicate_series_members
		'unique_series_members' => [
			'title' => 'Ensure Series Members Are Unique',
			'description' => 'Ensure unique series members are unique by preventing duplicate matches on seriesId & groupedWorkPermanentId.',
			'sql' => [
				'ALTER TABLE series_member ADD UNIQUE (seriesId, groupedWorkPermanentId, volume, displayName)'
			]
		], // unique_series_members

		//yanjun
		'user_agent_cleanup_2607' => [
			'title' => 'User Agent Cleanup',
			'description' => 'Clean up user agent data to keep only recent usage history for 26.07 upgrade.',
			'continueOnError' => false,
			'sql' => [
				"DELETE FROM usage_by_user_agent WHERE year < 2026 OR (year = 2026 AND month < 5)",
				"DELETE ua FROM user_agent ua LEFT JOIN usage_by_user_agent u ON u.userAgentId = ua.id WHERE u.userAgentId IS NULL",
			],
		],
		'add_user_agent_retention_months' => [
			'title' => 'Add User Agent Retention Months',
			'description' => 'Add userAgentRetentionMonths column to system_variables',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE system_variables ADD COLUMN userAgentRetentionMonths INT NOT NULL DEFAULT 3'
			]
		], //add_user_agent_retention_months
		'add_user_agent_usage_year_month_index' => [
			'title' => 'Add User Agent Usage Year Month Index',
			'description' => 'Add an index on year and month to usage_by_user_agent for retention cleanup',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE usage_by_user_agent ADD INDEX idx_year_month (year, month)'
			]
		], //add_user_agent_usage_year_month_index

		//imani

		//galen

		//chloe
		'library_show_checkout_renewal_fee_message' => [
			'title' => 'Add Show Checkout Renewal Fee Message to Library',
			'description' => 'Adds a setting to control whether the checkout renewal fee message is shown to patrons',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library ADD COLUMN showCheckoutRenewalFeeMessage TINYINT(1) DEFAULT 1',
			]
		], //library_show_checkout_renewal_fee_message
		'library_show_hold_fee_message' => [
			'title' => 'Add Show Hold Fee Message to Library',
			'description' => 'Adds a setting to control whether the hold fee message is shown to patrons',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library ADD COLUMN showHoldFeeMessage TINYINT(1) DEFAULT 1',
			]
		], //library_show_hold_fee_message
		'create_table_palace_project_stats' => [
			'title' => 'Record Palace Project Stats Separately',
			'description' => 'Adds a Palace Project Stats table',
			'continueOnError' => false,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS palace_project_stats (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					instance varchar(100) DEFAULT NULL,
					year int(11) NOT NULL,
					month int(11) NOT NULL,
					day int(11) NOT NULL,
					numCheckouts int(11) NOT NULL DEFAULT 0,
					numRenewals int(11) NOT NULL DEFAULT 0,
					numEarlyReturns int(11) NOT NULL DEFAULT 0,
					numHoldsPlaced int(11) NOT NULL DEFAULT 0,
					numHoldsCancelled int(11) NOT NULL DEFAULT 0,
					numHoldsFrozen int(11) NOT NULL DEFAULT 0,
					numHoldsThawed int(11) NOT NULL DEFAULT 0,
					numApiErrors int(11) NOT NULL DEFAULT 0,
					numConnectionFailures int(11) NOT NULL DEFAULT 0
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
			]
		], //create_table_palace_project_stats

		//pedro

		//mark j
		'user_middlename' => [
			'title' => 'Add middle name to user table',
			'description' => 'Adds a middlename column to the user table to cache the patron middle name from the ILS.',
			'sql' => [
				"ALTER TABLE user ADD COLUMN middlename varchar(256) DEFAULT NULL AFTER firstname",
			],
		], //user_middlename

		//lucas

		//tomas

		// stephen
		'theme_full_width_content' => [
			'title' => 'Add fullWidthContent column',
			'description' => 'Add fullWidthContent column to themes table.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE themes ADD COLUMN fullWidthContent TINYINT(1) DEFAULT 0',
			]
		], //theme_full_width_content

		//other

	];
}

function removeDuplicateSeriesMembers(): void {
	global $aspen_db;

	try {
		$sql = "
			DELETE sm1 FROM series_member sm1
			INNER JOIN series_member sm2
			ON sm1.seriesId = sm2.seriesId
			AND sm1.groupedWorkPermanentId <=> sm2.groupedWorkPermanentId
			AND sm1.volume <=> sm2.volume
			AND sm1.displayName <=> sm2.displayName
			AND sm1.id > sm2.id ";

		$stmt = $aspen_db->prepare($sql);
		$stmt->execute();

	} catch (PDOException $e) {
		global $logger;
		if (isset($logger)) {
			$logger->log('Error removing duplicate series_member rows: ' . $e->getMessage(), Logger::LOG_ERROR);
		}
	}
}