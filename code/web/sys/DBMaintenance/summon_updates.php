<?php
/** @noinspection SqlResolve */
function getSummonUpdates() {
	return [
        'createSummonModule' => [
			'title' => 'Create Summon module',
			'description' => 'Setup modules for Summon Integration',
			'sql' => [
				"INSERT INTO modules (name, indexName, backgroundProcess) VALUES ('Summon', '', '')",

			],
		],
		'createSettingsForSummon' => [
			'title' => 'Create Summon settings',
			'description' => 'Create settings to store information for Summon Integrations',
			'continueOnError' => true,
			'sql' => [
				"CREATE TABLE summon_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(50) NOT NULL,
					summonBaseApi VARCHAR(50) DEFAULT '',
					summonApiId VARCHAR(50) DEFAULT '',
					summonApiPassword VARCHAR(50) DEFAULT ''
				) ENGINE INNODB",
				'ALTER TABLE library ADD COLUMN summonSettingsId INT(11) DEFAULT -1',
			],
		],
		'aspen_usage_summon' => [
			'title' => 'Aspen Usage for Summon Searches',
			'description' => 'Add a column to track usage of Summon searches within Aspen',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE aspen_usage ADD COLUMN summonSearches INT(11) DEFAULT 0',
			],
		],
		'track_summon_user_usage' => [
			'title' => 'Summon Usage by user',
			'description' => 'Add a table to track how often a particular user uses Summon.',
			'sql' => [
				"CREATE TABLE user_summon_usage (
				    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				    userId INT(11) NOT NULL,
				    month INT(2) NOT NULL,
				    year INT(4) NOT NULL,
				    usageCount INT(11)
				) ENGINE = InnoDB",
				"ALTER TABLE user_summon_usage ADD INDEX (year, month, userId)",
			],
		],
		'summon_record_usage' => [
			'title' => 'Summon Usage',
			'description' => 'Add a table to track how Summon is used.',
			'continueOnError' => true,
			'sql' => [
				"CREATE TABLE summon_usage (
				    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				    summonId VARCHAR(15) NOT NULL,
				    month INT(2) NOT NULL,
				    year INT(4) NOT NULL,
				    timesViewedInSearch INT(11) NOT NULL,
				    timesUsed INT(11) NOT NULL
				) ENGINE = InnoDB",
				"ALTER TABLE summon_usage ADD INDEX (summonId, year, month)",
			],
		],
		'summon_usage_add_instance' => [
			'title' => 'Summon Usage - Instance Information',
			'description' => 'Add Instance Information to Summon Usage stats',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE summon_usage ADD COLUMN instance VARCHAR(100)',
				'ALTER TABLE summon_usage DROP INDEX summonId',
				'ALTER TABLE summon_usage ADD UNIQUE INDEX (instance, summonId, year, month)',
				'ALTER TABLE user_summon_usage ADD COLUMN instance VARCHAR(100)',
				'ALTER TABLE user_summon_usage DROP INDEX year',
				'ALTER TABLE user_summon_usage ADD UNIQUE INDEX (instance, userId, year, month)',
			],
		],
		// 'createSummonPermissions' => [
		// 	'title' => 'Create Summon Permissions',
		// 	'description' => 'Create Summon Permissions',
		// 	'continueOnError' => true,
		// 	'sql' => [
		// 		"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Cataloging & eContent', 'Administer Summon', 'Summon', 180, 'Allows the user configure Summon integration for all libraries.')",
		// 		"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('', 'Library Summon Options', '', 49, 'Configure Library fields related to Summon content.')",
		// 		"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryAdmin'), (SELECT id from permissions where name='Library Summon Options'))",
		// 		"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'opacAdmin'), (SELECT id from permissions where name='Library Summon Options'))",
		// 		"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Summon'))",
		// 	],
		// ],
    ];
}