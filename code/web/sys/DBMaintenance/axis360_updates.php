<?php
/** @noinspection SqlResolve */
function getAxis360Updates(){
	return [
		'createAxis360Module' => [
			'title' => 'Create Axis360 modules',
			'description' => 'Setup modules for Axis360 Integration',
			'sql' =>[
				"INSERT INTO modules (name, indexName, backgroundProcess,logClassPath,logClassName) VALUES ('Axis 360', 'grouped_works', 'axis_360_export','/sys/Axis360/Axis360LogEntry.php', 'Axis360LogEntry')",
			]
		],

		'axis360AddSettings' => [
			'title' => 'Add Settings to Axis360 module',
			'description' => 'Add Settings to Axis360 module',
			'sql' => [
				"UPDATE modules set settingsClassPath = '/sys/Axis360/Axis360Setting.php', settingsClassName='Axis360Setting' WHERE name = 'Axis 360'"
			]
		],

		'createAxis360SettingsAndScopes' => [
			'title' => 'Create settings and scopes for Axis360',
			'description' => 'Create settings and scopes for Axis360',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS axis360_settings(
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					apiUrl VARCHAR(255),
					userInterfaceUrl VARCHAR(255),
					vendorUsername VARCHAR(50),
					vendorPassword VARCHAR(50),
					libraryPrefix VARCHAR(50),
					runFullUpdate TINYINT(1) DEFAULT 0,
					lastUpdateOfChangedRecords INT(11) DEFAULT 0,
					lastUpdateOfAllRecords INT(11) DEFAULT 0
				)",
				'CREATE TABLE axis360_scopes (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(50) NOT NULL
				) ENGINE = InnoDB'
			]
		],

		'addSettingIdToAxis360Scopes' => [
			'title' => 'Add Setting Id to Axis 360 Scopes',
			'description' => 'Add Setting Id to Axis 360 Scopes',
			'sql' => [
				'ALTER TABLE axis360_scopes ADD COLUMN settingId INT(11)'
			]
		],

		'axis360_exportLog' => [
			'title' => 'Axis360 export log',
			'description' => 'Create log for Axis360 export.',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS axis360_export_log(
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
			]
		],

		'axis360Title' => array(
			'title' => 'Axis360 title and availability table',
			'description' => 'Create tables to store titles exported from Axis360.',
			'sql' => array(
				"CREATE TABLE axis360_title (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					axis360Id VARCHAR(25) NOT NULL,
					isbn VARCHAR(13) NOT NULL,
					title VARCHAR(255),
					subtitle VARCHAR(255),
					primaryAuthor VARCHAR(255),
					formatType VARCHAR(20),
					rawChecksum BIGINT,
					rawResponse MEDIUMTEXT,
					dateFirstDetected bigint(20) DEFAULT NULL,
					lastChange INT(11) NOT NULL,
					deleted TINYINT NOT NULL DEFAULT 0,
					UNIQUE(axis360Id)
				) ENGINE = InnoDB",
				"ALTER TABLE axis360_title ADD INDEX(lastChange)",
				"CREATE TABLE IF NOT EXISTS axis360_title_availability (
					`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					titleId INT,
					libraryPrefix VARCHAR(50),
					ownedQty INT,
					availableQty INT,
					totalHolds INT,
					totalCheckouts INT,
					INDEX (titleId),
					INDEX (libraryPrefix),
					UNIQUE(titleId, libraryPrefix)
				)",
			),
		),

		'axis360_add_setting_to_availability' => [
			'title' => 'Add settingID to Axis360 availability',
			'description' => 'Define availability based on settings',
			'continueOnError' => true,
			'sql' => [
				'ALTER table axis360_title_availability ADD column settingId INT(11)',
				'ALTER table axis360_title_availability DROP INDEX titleId',
				'ALTER table axis360_title_availability ADD UNIQUE titleId(titleId, settingId)',
			]
		],

		'axis360_add_response_info_to_availability' => [
			'title' => 'Axis 360 Availability Response Info',
			'description' => 'Add additional response information to Axis 360 Availability',
			'sql' => [
				'ALTER table axis360_title_availability ADD column rawChecksum BIGINT',
				'ALTER table axis360_title_availability ADD column rawResponse MEDIUMTEXT',
				'ALTER table axis360_title_availability ADD column lastChange INT(11) NOT NULL',
			]
		],

		'axis360_availability_remove_unused_fields' => [
			'title' => 'Axis 360 Availability remove unused fields',
			'description' => 'Remove unused fields from Axis 360 Availability',
			'continueOnError' => true,
			'sql' => [
				'ALTER table axis360_title_availability DROP column copiesAvailable',
				'ALTER table axis360_title_availability DROP column totalReserves',
			]
		],

		'axis360_availability_update_for_new_method' => [
			'title' => 'Axis 360 Update availability for new titleInfo method',
			'description' => 'Remove unused fields and add available field',
			'sql' => [
				'ALTER table axis360_title_availability DROP column availableQty',
				'ALTER table axis360_title_availability DROP column totalCheckouts',
				'ALTER table axis360_title_availability ADD COLUMN available TINYINT(1) DEFAULT 1'
			]
		],

		'add_settings_axis360_exportLog' => array(
			'title' => 'Add Settings to Axis 360 export log',
			'description' => 'Add settings to axis 360 export log.',
			'sql' => array(
				'ALTER table axis360_export_log ADD column settingId INT(11)'
			)
		),

		'axis360_exportLog_num_skipped' => array(
			'title' => 'Add numSkipped to Axis 360 export log',
			'description' => 'Add numSkipped to axis 360 export log.',
			'sql' => array(
				'ALTER table axis360_export_log ADD column numSkipped INT(11)'
			)
		),

		'track_axis360_user_usage' => array(
			'title' => 'Axis 360 Usage by user',
			'description' => 'Add a table to track how often a particular user uses Axis 360.',
			'sql' => array(
				"CREATE TABLE user_axis360_usage (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					instance VARCHAR(100),
					userId INT(11) NOT NULL,
					year INT(4) NOT NULL,
					month INT(2) NOT NULL,
					usageCount INT(11) DEFAULT 0
				) ENGINE = InnoDB",
				"ALTER TABLE user_axis360_usage ADD INDEX (instance, userId, year, month)",
				"ALTER TABLE user_axis360_usage ADD INDEX (instance, year, month)",
			),
		),

		'track_axis360_record_usage' => array(
			'title' => 'Axis 360 Record Usage',
			'description' => 'Add a table to track how records within Axis 360 are used.',
			'continueOnError' => true,
			'sql' => array(
				"CREATE TABLE axis360_record_usage (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					instance VARCHAR(100),
					axis360Id INT(11),
					year INT(4) NOT NULL,
					month INT(2) NOT NULL,
					timesHeld INT(11) NOT NULL DEFAULT 0,
					timesCheckedOut INT(11) NOT NULL DEFAULT 0
				) ENGINE = InnoDB",
				"ALTER TABLE axis360_record_usage ADD INDEX (instance, axis360Id, year, month)",
				"ALTER TABLE axis360_record_usage ADD INDEX (instance, year, month)",
			),
		),

		'track_axis360_stats' => array(
			'title' => 'Axis 360 Stats',
			'description' => 'Add a table to track how Axis 360 is used.',
			'continueOnError' => true,
			'sql' => array(
				"CREATE TABLE axis360_stats (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					instance VARCHAR(100),
					year INT(4) NOT NULL,
					month INT(2) NOT NULL,
					numCheckouts INT(11) NOT NULL DEFAULT 0,
					numRenewals INT(11) NOT NULL DEFAULT 0,
					numEarlyReturns INT(11) NOT NULL DEFAULT 0,
					numHoldsPlaced INT(11) NOT NULL DEFAULT 0,
					numHoldsCancelled INT(11) NOT NULL DEFAULT 0,
					numHoldsFrozen INT(11) NOT NULL DEFAULT 0,
					numHoldsThawed INT(11) NOT NULL DEFAULT 0,
					numApiErrors INT(11) NOT NULL DEFAULT 0,
					numConnectionFailures INT(11) NOT NULL DEFAULT 0
				) ENGINE = InnoDB",
			),
		),

		'axis360_stats_index' =>[
			'title' => 'Axis 360 Stats Index',
			'description' => 'Add an index to the table for tracking how Axis 360 is used.',
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE axis360_stats ADD INDEX (instance, year, month)",
			),
		]
	];
}
