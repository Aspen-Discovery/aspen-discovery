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
		],
		'indexing_profile_under_consideration_order_records' => [
			'title' => 'Indexing Profiles - Add Order Record Status to treat as under consideration',
			'description' => 'Add Order Record Status to treat as under consideration',
			'sql' => [
				"ALTER TABLE indexing_profiles ADD COLUMN orderRecordStatusToTreatAsUnderConsideration VARCHAR(10) DEFAULT ''",
			],

		],

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

		//alexander - PTFS Europe
		'summon_ip_addresses' => [
			'title' => 'Summon IP address configuration',
			'description' => 'Allow configuration of which IP addresses should automatically authenticate with Summon',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE ip_lookup ADD COLUMN authenticatedForSummon TINYINT DEFAULT 0',
			]
			],
		//other


	];
}