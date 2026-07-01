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
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer DP.LA Exclusions'))",
			],
		],
		// permissions_create_events_localhop
		'series_to_group_with' => [
			'title' => 'Add Column in Series Table for Grouping',
			'description' => 'Add column for seriesToGroupWithId in Series table to be used for merging/grouping series.',
			'sql' => [
				'ALTER TABLE series ADD COLUMN seriesToGroupWithId CHAR(40)',
			]
		],

		//yanjun
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
