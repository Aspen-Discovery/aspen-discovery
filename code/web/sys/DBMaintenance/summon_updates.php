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
		'summon_integration' => [
			'title' => 'Library Sumon Integration',
			'description' => 'Setup information for connection to Summon APIs',
			'sql' => [
				'ALTER TABLE library ADD COLUMN summonApiId VARCHAR(50)',
				'ALTER TABLE library ADD COLUMN summonApiPassword VARCHAR(50)',
			],
		],
		'add_book_cover_display_control_in_library_settings' => [
			'title' => 'Display Available Book Covers in Summon',
			'description' => 'Whether to display available book covers in Summon Searcher',
			'sql' => [
				"ALTER TABLE library ADD COLUMN showAvailableCoversInSummon TINYINT(1) DEFAULT 0",
			],
		],
    ];
}