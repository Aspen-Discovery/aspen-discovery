<?php

function getUpdates24_06_00(): array {
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
		'user_agent_tracking' => [
			'title' => 'User Agent Tracking',
			'description' => 'Allow tracking of traffic to Aspen by User Agent',
			'continueOnError' => false,
			'sql' => [
				'CREATE TABLE user_agent (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					userAgent TEXT, 
					isBot TINYINT NOT NULL DEFAULT 0,
					blockAccess TINYINT NOT NULL DEFAULT 0
				) ENGINE = InnoDB',
				'ALTER TABLE user_agent ADD UNIQUE (userAgent(512))',
				'CREATE TABLE usage_by_user_agent (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					userAgentId INT(11) NOT NULL, 
					instance VARCHAR(255),
					year INT(4) NOT NULL,
					month INT(2) NOT NULL,
					numRequests INT NOT NULL DEFAULT 0,
					numBlockedRequests INT NOT NULL DEFAULT 0
				) ENGINE = InnoDB',
				'ALTER TABLE usage_by_user_agent ADD INDEX (userAgentId, year, instance, month)',
			]
		], //user_agent_tracking
		'permissions_create_administer_user_agents' => [
			'title' => 'Administer User Agents Permission',
			'description' => 'Create Administer User Agents Permission',
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration', 'Administer User Agents', '', 55, 'Allows the user to administer User Agents for Aspen Discovery.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer User Agents'))",
			],
		], //permissions_create_administer_user_agents
		'indexing_profile_under_consideration_order_records' => [
			'title' => 'Indexing Profiles - Add Order Record Status to treat as under consideration',
			'description' => 'Add Order Record Status to treat as under consideration',
			'sql' => [
				"ALTER TABLE indexing_profiles ADD COLUMN orderRecordStatusToTreatAsUnderConsideration VARCHAR(10) DEFAULT ''",
			],
		], //indexing_profile_under_consideration_order_records
		'sideload_convert_to_econtent' => [
			'title' => 'Sideloads convert to eContent',
			'description' => 'Add an option to allow sideloads to not be treated as eContent',
			'sql' => [
				"ALTER TABLE sideloads ADD COLUMN convertFormatToEContent TINYINT DEFAULT 1",
			],
		], //sideload_convert_to_econtent
		'sideload_use_link_text_for_button_label' => [
			'title' => 'Sideloads Use Link Text For Button Label',
			'description' => 'Add an option to allow sideloads to use the URL link text for the button URL',
			'sql' => [
				"ALTER TABLE sideloads ADD COLUMN useLinkTextForButtonLabel TINYINT DEFAULT 0",
			],
		], //sideload_use_link_text_for_button_label
		'increase_patron_type_length' => [
			'title' => 'Increase Patron Type Length',
			'description' => 'Increase the length of the patron type field in the user table to match the ptype table',
			'sql' => [
				"ALTER TABLE user CHANGE COLUMN patronType patronType VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT ''",
			],
		], //increase_patron_type_length
		'add_additional_control_over_format_mapping' => [
			'title' => 'Add additional control over format mapping',
			'description' => 'Allow administrators to control what fields each row of the format map apply to',
			'sql' => [
				"ALTER TABLE format_map_values ADD COLUMN appliesToBibLevel TINYINT(1) DEFAULT 1",
				"ALTER TABLE format_map_values ADD COLUMN appliesToItemShelvingLocation TINYINT(1) DEFAULT 1",
				"ALTER TABLE format_map_values ADD COLUMN appliesToItemSublocation TINYINT(1) DEFAULT 1",
				"ALTER TABLE format_map_values ADD COLUMN appliesToItemCollection TINYINT(1) DEFAULT 1",
				"ALTER TABLE format_map_values ADD COLUMN appliesToItemType TINYINT(1) DEFAULT 1",
				"ALTER TABLE format_map_values ADD COLUMN appliesToItemFormat TINYINT(1) DEFAULT 1",
			],
		], //add_additional_control_over_format_mapping
		'add_additional_control_over_format_mapping_part2' => [
			'title' => 'Add additional control over format mapping part 2',
			'description' => 'Allow administrators to control what fields each row of the format map apply to',
			'sql' => [
				"ALTER TABLE format_map_values ADD COLUMN appliesToMatType TINYINT(1) DEFAULT 1",
				"ALTER TABLE format_map_values ADD COLUMN appliesToFallbackFormat TINYINT(1) DEFAULT 1",
			],
		], //add_additional_control_over_format_mapping_part2
		'grouped_work_debugging' => [
			'title' => 'Grouped Work Debugging',
			'description' => 'Allow additional debugging information to be output for grouped works during indexing',
			'sql' => [
				"CREATE TABLE grouped_work_debug_info (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					permanent_id CHAR(40) NOT NULL UNIQUE, 
					debugInfo TEXT,
					debugTime INT,
					processed TINYINT
				) ENGINE INNODB",
			],
		], //grouped_work_debugging
		'remove_deprecated_self_reg_columns' => [
			'title' => 'Remove Deprecated Self Reg Columns',
			'description' => 'Remove Self Reg from library table since they have moved to the self reg forms',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE library DROP COLUMN promptForParentInSelfReg",
				"ALTER TABLE library DROP COLUMN promptForSMSNoticesInSelfReg",
				"ALTER TABLE library DROP COLUMN selfRegRequirePhone",
				"ALTER TABLE library DROP COLUMN selfRegRequireEmail",
				"ALTER TABLE library DROP COLUMN enableThirdPartyRegistration",
			],
		], //remove_deprecated_self_reg_columns

		//kirstien - ByWater
		'accessibleBrowseCategories' => [
			'title' => 'Accessible browse categories',
			'description' => 'Adds an option to enable more accessible browse categories.',
			'sql' => [
				'ALTER TABLE themes ADD COLUMN accessibleBrowseCategories TINYINT NOT NULL DEFAULT 0',
			],
		], //accessibleBrowseCategories
		'autoPickUserHomeLocation' => [
			'title' => 'Add option to auto-select user home location for Branded LiDA',
			'description' => 'Adds an option to auto-select the users home location when logging into a Branded Aspen LiDA',
			'sql' => [
				'ALTER TABLE aspen_lida_branded_settings ADD COLUMN autoPickUserHomeLocation TINYINT NOT NULL DEFAULT 1',
			],
		], //accessibleBrowseCategories

		//kodi - ByWater
		'full_text_limiter' => [
			'title' => 'Full Text Limiter',
			'description' => 'Adds toggle for defaulting the full text limiter on/off for Ebsco EDS.',
			'sql' => [
				"ALTER TABLE ebsco_eds_settings ADD COLUMN fullTextLimiter TINYINT NOT NULL DEFAULT 1;",
			],
		], //full_text_limiter

		//pedro - PTFS-Europe
		'smtp_settings' => [
			'title' => 'SMTP Settings',
			'description' => 'Allow configuration of SMTP to send mail from',
			'sql' => [
				"CREATE TABLE smtp_settings (
					id int(11) NOT NULL AUTO_INCREMENT,
					name varchar(80) NOT NULL,
					host varchar(80) NOT NULL DEFAULT 'localhost',
					port int(11) NOT NULL DEFAULT 25,
					ssl_mode enum('disabled','ssl','tls') NOT NULL,
					from_address varchar(80) DEFAULT NULL,
					user_name varchar(80) DEFAULT NULL,
					password varchar(80) DEFAULT NULL,
					PRIMARY KEY (`id`)
				) ENGINE=InnoDB",
			],
		], //smtp_settings

		'permissions_create_administer_smtp' => [
			'title' => 'Create Administer SMTP Permission',
			'description' => 'Controls if the user can change SMTP settings',
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration', 'Administer SMTP', '', 30, 'Controls if the user can change SMTP settings.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer SMTP'))",
			],
		], //permissions_create_administer_smtp

		//other



	];
}