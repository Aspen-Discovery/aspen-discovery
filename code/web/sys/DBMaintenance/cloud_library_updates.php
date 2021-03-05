<?php

function getCloudLibraryUpdates() {
	return array(
		'cloud_library_exportTable' => array(
			'title' => 'Cloud Library title table',
			'description' => 'Create a table to store data exported from Cloud Library.',
			'sql' => array(
				"CREATE TABLE cloud_library_title (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					cloudLibraryId VARCHAR(25) NOT NULL,
					title VARCHAR(255),
					subTitle VARCHAR(255),
					author VARCHAR(255),
					format VARCHAR(50),
					rawChecksum BIGINT,
					rawResponse MEDIUMTEXT,
					dateFirstDetected bigint(20) DEFAULT NULL,
					lastChange INT(11) NOT NULL,
					deleted TINYINT NOT NULL DEFAULT 0,
					UNIQUE(cloudLibraryId)
				) ENGINE = InnoDB",
				"ALTER TABLE cloud_library_title ADD INDEX(lastChange)"
			),
		),

		'cloud_library_availability' => array(
			'title' => 'Cloud Library availability tables',
			'description' => 'Create tables to store data exported from Cloud Library.',
			'sql' => array(
				"CREATE TABLE cloud_library_availability (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					cloudLibraryId VARCHAR(25) NOT NULL,
					totalCopies TINYINT NOT NULL DEFAULT 0,
					sharedCopies TINYINT NOT NULL DEFAULT 0,
					totalLoanCopies TINYINT NOT NULL DEFAULT 0,
					totalHoldCopies TINYINT NOT NULL DEFAULT 0,
					sharedLoanCopies TINYINT NOT NULL DEFAULT 0,
					rawChecksum BIGINT,
					rawResponse MEDIUMTEXT,
					lastChange INT(11) NOT NULL,
					UNIQUE(cloudLibraryId)
				) ENGINE = InnoDB",
				"ALTER TABLE cloud_library_availability ADD INDEX(lastChange)"
			),
		),

		'cloud_library_exportLog' => array(
			'title' => 'Cloud Library export log',
			'description' => 'Create log for Cloud Library export.',
			'sql' => array(
				"CREATE TABLE IF NOT EXISTS cloud_library_export_log(
					`id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of log', 
					`startTime` INT(11) NOT NULL COMMENT 'The timestamp when the run started', 
					`endTime` INT(11) NULL COMMENT 'The timestamp when the run ended', 
					`lastUpdate` INT(11) NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)', 
					`notes` TEXT COMMENT 'Additional information about the run', 
					numProducts INT(11) DEFAULT 0,
					numErrors INT(11) DEFAULT 0,
					numAdded INT(11) DEFAULT 0,
					numDeleted INT(11) DEFAULT 0,
					numUpdated INT(11) DEFAULT 0,
					numAvailabilityChanges INT(11) DEFAULT 0,
					numMetadataChanges INT(11) DEFAULT 0,
					PRIMARY KEY ( `id` )
				) ENGINE = InnoDB;",
			)
		),

		'track_cloud_library_user_usage' => array(
			'title' => 'Cloud Library Usage by user',
			'description' => 'Add a table to track how often a particular user uses Cloud Library.',
			'sql' => array(
				"CREATE TABLE user_cloud_library_usage (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					userId INT(11) NOT NULL,
					year INT(4) NOT NULL,
					month INT(2) NOT NULL,
					usageCount INT(11)
				) ENGINE = InnoDB",
				"ALTER TABLE user_cloud_library_usage ADD INDEX (userId, year, month)",
				"ALTER TABLE user_cloud_library_usage ADD INDEX (year, month)",
			),
		),

		'track_cloud_library_record_usage' => array(
			'title' => 'Cloud Library Record Usage',
			'description' => 'Add a table to track how records within Cloud Library are used.',
			'continueOnError' => true,
			'sql' => array(
				"CREATE TABLE cloud_library_record_usage (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					cloudLibraryId INT(11),
					year INT(4) NOT NULL,
					month INT(2) NOT NULL,
					timesHeld INT(11) NOT NULL,
					timesCheckedOut INT(11) NOT NULL
				) ENGINE = InnoDB",
				"ALTER TABLE cloud_library_record_usage ADD INDEX (cloudLibraryId, year, month)",
				"ALTER TABLE cloud_library_record_usage ADD INDEX (year, month)",
			),
		),

		'cloud_library_settings' => array(
			'title' => 'Cloud Library Settings',
			'description' => 'Add Settings for Cloud Library',
			'sql' => array(
				"CREATE TABLE IF NOT EXISTS cloud_library_settings(
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					apiUrl VARCHAR(255),
					userInterfaceUrl VARCHAR(255),
					libraryId VARCHAR(50),
					accountId VARCHAR(50),
					accountKey VARCHAR(50),
					runFullUpdate TINYINT(1) DEFAULT 0,
					lastUpdateOfChangedRecords INT(11) DEFAULT 0,
					lastUpdateOfAllRecords INT(11) DEFAULT 0
				) ENGINE = InnoDB",
			),
		),

		'cloud_library_scoping' => [
			'title' => 'Cloud Library Scoping',
			'description' => 'Add a table to define what information should be included within search results',
			'sql' => [
				'CREATE TABLE cloud_library_scopes (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(50) NOT NULL,
					includeEBooks TINYINT DEFAULT 1,
					includeEAudiobook TINYINT DEFAULT 1,
					restrictToChildrensMaterial TINYINT DEFAULT 0
				) ENGINE = InnoDB'
			]
		],

		'create_cloud_library_module' => [
			'title' => 'Create OverDrive Module',
			'description' => 'Setup OverDrive module',
			'sql' => [
				"INSERT INTO modules (name, indexName, backgroundProcess) VALUES ('Cloud Library', 'grouped_works', 'cloud_library_export')"
			]
		],

		'cloud_library_module_add_log' =>[
			'title' => 'Cloud Library add log info to module',
			'description' => 'Add logging information to Cloud Library modules',
			'sql' => [
				"UPDATE modules set logClassPath='/sys/CloudLibrary/CloudLibraryExportLogEntry.php', logClassName='CloudLibraryExportLogEntry' WHERE name = 'Cloud Library'",
			]
		],

		'cloud_library_add_settings' => [
			'title' => 'Add Settings to Cloud Library module',
			'description' => 'Add Settings to Cloud Library module',
			'sql' => [
				"UPDATE modules set settingsClassPath = '/sys/CloudLibrary/CloudLibrarySetting.php', settingsClassName = 'CloudLibrarySetting' WHERE name = 'Cloud Library'"
			]
		],

		'cloud_library_increase_allowable_copies' => [
			'title' => 'Cloud Library increase field size for copies fields',
			'description' => 'Cloud Library increase field size for copies fields',
			'sql' => [
				'ALTER TABLE cloud_library_availability CHANGE COLUMN totalCopies totalCopies SMALLINT NOT NULL DEFAULT 0',
				'ALTER TABLE cloud_library_availability CHANGE COLUMN sharedCopies sharedCopies SMALLINT NOT NULL DEFAULT 0',
				'ALTER TABLE cloud_library_availability CHANGE COLUMN totalLoanCopies totalLoanCopies SMALLINT NOT NULL DEFAULT 0',
				'ALTER TABLE cloud_library_availability CHANGE COLUMN totalHoldCopies totalHoldCopies SMALLINT NOT NULL DEFAULT 0',
				'ALTER TABLE cloud_library_availability CHANGE COLUMN sharedLoanCopies sharedLoanCopies SMALLINT NOT NULL DEFAULT 0'
			]
		],

		'cloud_library_usage_add_instance' => [
			'title' => 'Cloud Library Usage - Instance Information',
			'description' => 'Add Instance Information to Cloud Library Usage stats',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE cloud_library_record_usage ADD COLUMN instance VARCHAR(100)',
				'ALTER TABLE cloud_library_record_usage DROP INDEX cloudLibraryId',
				'ALTER TABLE cloud_library_record_usage ADD UNIQUE INDEX (instance, cloudLibraryId, year, month)',
				'ALTER TABLE user_cloud_library_usage ADD COLUMN instance VARCHAR(100)',
				'ALTER TABLE user_cloud_library_usage DROP INDEX userId',
				'ALTER TABLE user_cloud_library_usage ADD UNIQUE INDEX (instance, userId, year, month)',
			]
		],

		'cloud_library_add_scope_setting_id' => [
			'title' => 'Add Setting Id to Cloud Library Scopes',
			'description' => 'Add Setting Id to Cloud Library Scopes',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE cloud_library_scopes ADD COLUMN settingId INT(11)',
				'UPDATE cloud_library_scopes set settingId = (SELECT MIN(id) from cloud_library_settings)'
			]
		],

		'cloud_library_add_setting_to_availability' => [
			'title' => 'Add settingID to Cloud Library availability',
			'description' => 'Define availability based on settings',
			'continueOnError' => true,
			'sql' => [
				'ALTER table cloud_library_availability ADD column settingId INT(11)',
				'UPDATE cloud_library_availability set settingId = (SELECT MIN(id) from cloud_library_settings)',
				'ALTER table cloud_library_availability DROP INDEX cloudLibraryId',
				'ALTER table cloud_library_availability ADD UNIQUE cloudLibraryId(cloudLibraryId, settingId)',
			]
		],

		'add_settings_cloud_library_exportLog' => array(
			'title' => 'Add Settings to Cloud Library export log',
			'description' => 'Add settings to Cloud Library export log.',
			'sql' => array(
				'ALTER table cloud_library_export_log ADD column settingId INT(11)'
			)
		),
	);
}