<?php

function getOpenArchivesUpdates()
{
	return [
		'open_archives_collection' => array(
			'title' => 'Open Archive Collections',
			'description' => 'Add a table to track collections of Open Archives Materials.',
			'sql' => array(
				"CREATE TABLE open_archives_collection (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(100) NOT NULL,
					baseUrl VARCHAR(255) NOT NULL,
					setName VARCHAR(100) NOT NULL,
					fetchFrequency ENUM('hourly', 'daily', 'weekly', 'monthly', 'yearly', 'once'),
					lastFetched INT(11)
				) ENGINE = InnoDB",
			),
		),

		'open_archives_collection_filtering' => array(
			'title' => 'Open Archive Collection Filtering',
			'description' => 'Add the ability to filter a collection by subject.',
			'sql' => array(
				"ALTER TABLE open_archives_collection ADD COLUMN subjectFilters MEDIUMTEXT",
			),
		),

		'open_archives_collection_subjects' => array(
			'title' => 'Open Archive Collection Subjects',
			'description' => 'Add a field to list all of the available subjects in a collection (to make filtering easier).',
			'sql' => array(
				"ALTER TABLE open_archives_collection ADD COLUMN subjects MEDIUMTEXT",
			),
		),

		'open_archives_record' => array(
			'title' => 'Open Archive Record',
			'description' => 'Add a table to track records within Open Archives',
			'sql' => array(
				"CREATE TABLE open_archives_record (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					sourceCollection INT(11) NOT NULL,
					permanentUrl VARCHAR(512) NOT NULL
				) ENGINE = InnoDB",
				"ALTER TABLE open_archives_record ADD UNIQUE INDEX (sourceCollection, permanentUrl)"
			),
		),

		'track_open_archive_user_usage' => array(
			'title' => 'Open Archive Usage by user',
			'description' => 'Add a table to track how often a particular user uses the Open Archives.',
			'sql' => array(
				"CREATE TABLE user_open_archives_usage (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					userId INT(11) NOT NULL,
					openArchivesCollectionId INT(11) NOT NULL,
					year INT(4) NOT NULL,
					firstUsed INT(11) NOT NULL,
					lastUsed INT(11) NOT NULL,
					usageCount INT(11)
				) ENGINE = InnoDB",
				"ALTER TABLE user_open_archives_usage ADD INDEX (openArchivesCollectionId, year, userId)",
			),
		),

		'track_open_archive_record_usage' => array(
			'title' => 'Open Archive Record Usage',
			'description' => 'Add a table to track how records within open archives are viewed.',
			'continueOnError' => true,
			'sql' => array(
				"CREATE TABLE open_archives_record_usage (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					openArchivesRecordId INT(11),
					year INT(4) NOT NULL,
					timesViewedInSearch INT(11) NOT NULL,
					timesUsed INT(11) NOT NULL
				) ENGINE = InnoDB",
				"ALTER TABLE open_archives_record_usage ADD INDEX (openArchivesRecordId, year)",
			),
		),

		'open_archive_tracking_adjustments' => array(
			'title' => 'Open Archive Tracking Adjustments',
			'description' => 'Track by month rather than just by year',
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE user_open_archives_usage ADD COLUMN month INT(2) NOT NULL default 4",
				"ALTER TABLE open_archives_record_usage ADD COLUMN month INT(2) NOT NULL default 4",
				"ALTER TABLE user_open_archives_usage DROP COLUMN firstUsed",
				"ALTER TABLE user_open_archives_usage DROP COLUMN lastUsed",
			),
		),

		'create_open_archives_module' => [
			'title' => 'Create Open Archives Module',
			'description' => 'Setup Open Archives module',
			'sql' => [
				//oai indexer runs daily so we don't check the background process
				"INSERT INTO modules (name, indexName, backgroundProcess) VALUES ('Open Archives', 'open_archives', '')"
			]
		],

		'open_archives_loadOneMonthAtATime' => [
			'title' => 'OAI load one month at a time',
			'description' => 'Update OAI settings to control if records are loaded one month at a time',
			'sql' => [
				//oai indexer runs daily so we don't check the background process
				"ALTER TABLE open_archives_collection ADD COLUMN loadOneMonthAtATime TINYINT(1) DEFAULT 1"
			]
		],

		'open_archives_log' => array(
			'title' => 'Open Archives log',
			'description' => 'Create log for Side Load Processing.',
			'sql' => array(
				"CREATE TABLE IF NOT EXISTS open_archives_export_log(
					`id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of log', 
					`startTime` INT(11) NOT NULL COMMENT 'The timestamp when the run started', 
					`endTime` INT(11) NULL COMMENT 'The timestamp when the run ended', 
					`lastUpdate` INT(11) NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)', 
					`notes` TEXT COMMENT 'Additional information about the run', 
					collectionName MEDIUMTEXT,
					numRecords INT(11) DEFAULT 0,
					numErrors INT(11) DEFAULT 0,
					numAdded INT(11) DEFAULT 0,
					numDeleted INT(11) DEFAULT 0,
					numUpdated INT(11) DEFAULT 0,
					numSkipped INT(11) DEFAULT 0,
					PRIMARY KEY ( `id` )
				) ENGINE = InnoDB;",
			)
		),

		'open_archives_module_add_log' =>[
			'title' => 'Open Archives add log info to module',
			'description' => 'Add logging information to open archives module',
			'sql' => [
				"UPDATE modules set logClassPath='/sys/OpenArchives/OpenArchivesExportLogEntry.php', logClassName='OpenArchivesExportLogEntry' WHERE name='Open Archives'",
			]
		],

		'open_archives_module_add_settings' => [
			'title' => 'Add Settings to Open Archives module',
			'description' => 'Add Settings to Open Archives module',
			'sql' => [
				"UPDATE modules set settingsClassPath = '/sys/OpenArchives/OpenArchivesCollection.php', settingsClassName = 'OpenArchivesCollection' WHERE name = 'Open Archives'"
			]
		],

		'open_archives_scoping' => [
			'title' => 'Open Archives scoping',
			'description' => 'Add scoping for open archives',
			'sql' => [
				'CREATE TABLE library_open_archives_collection (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					collectionId INT(11) NOT NULL,
					libraryId INT(11) NOT NULL,
					UNIQUE (collectionId, libraryId)
				) ENGINE = InnoDB',
				'CREATE TABLE location_open_archives_collection (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					collectionId INT(11) NOT NULL,
					locationId INT(11) NOT NULL,
					UNIQUE (collectionId, locationId)
				) ENGINE = InnoDB'
			]
		],

		'open_archives_usage_add_instance' => [
			'title' => 'Open Archives Usage - Instance Information',
			'description' => 'Add Instance Information to Open Archives Usage stats',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE open_archives_record_usage ADD COLUMN instance VARCHAR(100)',
				'ALTER TABLE open_archives_record_usage DROP INDEX openArchivesRecordId',
				'ALTER TABLE open_archives_record_usage ADD UNIQUE INDEX (instance, openArchivesRecordId, year, month)',
				'ALTER TABLE user_open_archives_usage ADD COLUMN instance VARCHAR(100)',
				'ALTER TABLE user_open_archives_usage DROP INDEX openArchivesCollectionId',
				'ALTER TABLE user_open_archives_usage ADD UNIQUE INDEX (instance, openArchivesCollectionId, userId, year, month)',
			]
		],
	];
}