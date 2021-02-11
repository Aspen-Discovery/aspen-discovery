<?php
/**
 * Updates related to rbdigital for cleanliness
 */

function getRBdigitalUpdates() {
	return array(
		'rbdigital_exportTables' => array(
			'title' => 'RBdigital title tables',
			'description' => 'Create tables to store data exported from RBdigital.',
			'sql' => array(
				"CREATE TABLE rbdigital_title (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					rbdigitalId VARCHAR(25) NOT NULL,
					title VARCHAR(255),
					primaryAuthor VARCHAR(255),
					mediaType VARCHAR(50),
					isFiction TINYINT NOT NULL DEFAULT 0,
					audience VARCHAR(50),
					language VARCHAR(50),
					rawChecksum BIGINT,
					rawResponse MEDIUMTEXT,
					dateFirstDetected bigint(20) DEFAULT NULL,
					lastChange INT(11) NOT NULL,
					deleted TINYINT NOT NULL DEFAULT 0,
					UNIQUE(rbdigitalId)
				) ENGINE = InnoDB",
				"ALTER TABLE rbdigital_title ADD INDEX(lastChange)"
			),
		),

		'rbdigital_availability' => array(
			'title' => 'RBdigital availability tables',
			'description' => 'Create tables to store data exported from RBdigital.',
			'sql' => array(
				"CREATE TABLE rbdigital_availability (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					rbdigitalId VARCHAR(25) NOT NULL,
					isAvailable TINYINT NOT NULL DEFAULT 1,
					isOwned TINYINT NOT NULL DEFAULT 1,
					name VARCHAR(50),
					rawChecksum BIGINT,
					rawResponse MEDIUMTEXT,
					lastChange INT(11) NOT NULL,
					UNIQUE(rbdigitalId)
				) ENGINE = InnoDB",
				"ALTER TABLE rbdigital_availability ADD INDEX(lastChange)"
			),
		),

		'rbdigital_exportLog' => array(
			'title' => 'RBdigital export log',
			'description' => 'Create log for RBdigital export.',
			'sql' => array(
				"CREATE TABLE IF NOT EXISTS rbdigital_export_log(
					`id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of log', 
					`startTime` INT(11) NOT NULL COMMENT 'The timestamp when the run started', 
					`endTime` INT(11) NULL COMMENT 'The timestamp when the run ended', 
					`lastUpdate` INT(11) NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)', 
					`notes` TEXT COMMENT 'Additional information about the run', 
					PRIMARY KEY ( `id` )
				) ENGINE = InnoDB;",
			)
		),

		'track_rbdigital_user_usage' => array(
			'title' => 'RBdigital Usage by user',
			'description' => 'Add a table to track how often a particular user uses RBdigital.',
			'sql' => array(
				"CREATE TABLE user_rbdigital_usage (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					userId INT(11) NOT NULL,
					year INT(4) NOT NULL,
					month INT(2) NOT NULL,
					usageCount INT(11)
				) ENGINE = InnoDB",
				"ALTER TABLE user_rbdigital_usage ADD INDEX (userId, year, month)",
				"ALTER TABLE user_rbdigital_usage ADD INDEX (year, month)",
			),
		),

		'track_rbdigital_record_usage' => array(
			'title' => 'RBdigital Record Usage',
			'description' => 'Add a table to track how records within RBdigital are used.',
			'continueOnError' => true,
			'sql' => array(
				"CREATE TABLE rbdigital_record_usage (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					rbdigitalId INT(11),
					year INT(4) NOT NULL,
					month INT(2) NOT NULL,
					timesHeld INT(11) NOT NULL,
					timesCheckedOut INT(11) NOT NULL
				) ENGINE = InnoDB",
				"ALTER TABLE rbdigital_record_usage ADD INDEX (rbdigitalId, year, month)",
				"ALTER TABLE rbdigital_record_usage ADD INDEX (year, month)",
			),
		),

		'track_rbdigital_magazine_usage' => array(
			'title' => 'RBdigital Magazine Usage',
			'description' => 'Add a table to track how magazines within RBdigital are used.',
			'continueOnError' => true,
			'sql' => array(
				"CREATE TABLE rbdigital_magazine_usage (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					magazineId INT(11),
					year INT(4) NOT NULL,
					month INT(2) NOT NULL,
					timesCheckedOut INT(11) NOT NULL
				) ENGINE = InnoDB",
				"ALTER TABLE rbdigital_magazine_usage ADD INDEX (magazineId, year, month)",
				"ALTER TABLE rbdigital_magazine_usage ADD INDEX (year, month)",
			),
		),

		'rbdigital_add_settings' => array(
			'title' => 'Add RBdigital Settings',
			'description' => 'Add Settings for RBdigital to move configuration out of ini',
			'sql' => array(
				"CREATE TABLE IF NOT EXISTS rbdigital_settings(
						id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
						apiUrl VARCHAR(255),
						userInterfaceUrl VARCHAR(255),
						apiToken VARCHAR(50),
						libraryId INT(11) DEFAULT 0,
						runFullUpdate TINYINT(1) DEFAULT 0,
						lastUpdateOfChangedRecords INT(11) DEFAULT 0,
						lastUpdateOfAllRecords INT(11) DEFAULT 0
					)",
			),
		),

		'rbdigital_exportLog_update' => array(
			'title' => 'Better RBdigital export log',
			'description' => 'Add additional info for RBdigital export log.',
			'sql' => array(
				"ALTER TABLE rbdigital_export_log ADD COLUMN numProducts INT(11) DEFAULT 0",
				"ALTER TABLE rbdigital_export_log ADD COLUMN numErrors INT(11) DEFAULT 0",
				"ALTER TABLE rbdigital_export_log ADD COLUMN numAdded INT(11) DEFAULT 0",
				"ALTER TABLE rbdigital_export_log ADD COLUMN numDeleted INT(11) DEFAULT 0",
				"ALTER TABLE rbdigital_export_log ADD COLUMN numUpdated INT(11) DEFAULT 0",
				"ALTER TABLE rbdigital_export_log ADD COLUMN numAvailabilityChanges INT(11) DEFAULT 0",
				"ALTER TABLE rbdigital_export_log ADD COLUMN numMetadataChanges INT(11) DEFAULT 0",
			)
		),

		'rbdigital_magazine_export' => array(
			'title' => 'RBdigital magazine tables',
			'description' => 'Create tables to store data exported from RBdigital.',
			'sql' => array(
				"CREATE TABLE rbdigital_magazine (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					magazineId VARCHAR(25) NOT NULL,
					issueId VARCHAR(25) NOT NULL,
					title VARCHAR(255),
					publisher VARCHAR(255),
					mediaType VARCHAR(50),
					language VARCHAR(50),
					rawChecksum BIGINT,
					rawResponse MEDIUMTEXT,
					dateFirstDetected bigint(20) DEFAULT NULL,
					lastChange INT(11) NOT NULL,
					deleted TINYINT NOT NULL DEFAULT 0,
					UNIQUE(magazineId, issueId)
				) ENGINE = InnoDB",
				"ALTER TABLE rbdigital_magazine ADD INDEX(lastChange)"
			),
		),

		'rbdigital_scoping' => [
			'title' => 'RBdigital Scoping',
			'description' => 'Add a table to define what information should be included within search results',
			'sql' => [
				'CREATE TABLE rbdigital_scopes (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(50) NOT NULL,
					includeEBooks TINYINT DEFAULT 1,
					includeEAudiobook TINYINT DEFAULT 1,
					includeEMagazines TINYINT DEFAULT 1,
					restrictToChildrensMaterial TINYINT DEFAULT 0
				) ENGINE = InnoDB'
			]
		],

		'create_rbdigital_module' => [
			'title' => 'Create RBdigital Module',
			'description' => 'Setup RBdigital module',
			'sql' => [
				"INSERT INTO modules (name, indexName, backgroundProcess) VALUES ('RBdigital', 'grouped_works', 'rbdigital_export')"
			]
		],

		'rbdigital_lookup_patrons_by_email' => [
			'title' => 'RBdigital lookup patrons by email',
			'description' => 'RBdigital add switch to lookup patrons based on their email',
			'sql' => [
				"ALTER TABLE rbdigital_settings ADD COLUMN allowPatronLookupByEmail TINYINT(1) DEFAULT 1"
			]
		],

		'rbdigital_add_setting_to_scope' => [
			'title' => 'Add settingId to RBdigital scope',
			'description' => 'Allow multiple settings to be defined for RBdigital within a consortium',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE rbdigital_scopes ADD column settingId INT(11)',
				'updateRbDigitalScopes'
			]
		],

		'rbdigital_add_setting_to_log' => [
			'title' => 'Add settingID to RBdigital log entry',
			'description' => 'Define which settings are being logged',
			'sql' => [
				'ALTER table rbdigital_export_log ADD column settingId INT(11)'
			]
		],

		'rbdigital_add_setting_to_availability' => [
			'title' => 'Add settingID to RBdigital availability',
			'description' => 'Define availability based on settings',
			'continueOnError' => true,
			'sql' => [
				'ALTER table rbdigital_availability ADD column settingId INT(11)',
				'updateRbDigitalAvailability',
				'ALTER table rbdigital_availability DROP INDEX rbdigitalId',
				'ALTER table rbdigital_availability ADD UNIQUE rbdigitalId(rbdigitalId, settingId)',
			]
		],

		'rbdigital_issues_tables' => [
			'title' => 'Setup RBdigital issues tables',
			'description' => 'Setup tables to store issues information for RBdigital',
			'sql' => [
				'CREATE TABLE rbdigital_magazine_issue (
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					magazineId int(11) NOT NULL, 
					issueId int(11) NOT NULL,
					imageUrl VARCHAR(255), 
					publishedOn varchar(10), 
					coverDate varchar(10)
				) ENGINE=INNODB',
				'ALTER TABLE rbdigital_magazine_issue ADD UNIQUE (magazineId, issueId)',
				'CREATE TABLE rbdigital_magazine_issue_availability (
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					issueId int(11) NOT NULL,
					settingId int(11) NOT NULL,
					isAvailable tinyint(1), 
					isOwned tinyint(1), 
					stateId int
				) ENGINE=INNODB',
				'ALTER TABLE rbdigital_magazine_issue_availability ADD UNIQUE (issueId, settingId)'
			]
		],

		'rbdigital_issue_tracking' => [
			'title' => 'Update RBdigital Tracking for Issues',
			'description' => 'Update RBdigital Tracking for Issues',
			'sql' => [
				'ALTER TABLE rbdigital_magazine_usage ADD COLUMN issueId INT(11)',
				"ALTER TABLE rbdigital_magazine_usage DROP INDEX magazineId",
				"ALTER TABLE rbdigital_magazine_usage ADD INDEX (magazineId, issueId, year, month)",
			]
		],

		'rbdigital_module_add_log' =>[
			'title' => 'RBdigital add log info to module',
			'description' => 'Add logging information to RBdigital module',
			'sql' => [
				"UPDATE modules set logClassPath='/sys/RBdigital/RBdigitalExportLogEntry.php', logClassName='RBdigitalExportLogEntry' WHERE name='RBdigital'",
			]
		],

		'rbdigital_module_add_settings' => [
			'title' => 'Add Settings to RBdigital module',
			'description' => 'Add Settings to RBdigital module',
			'sql' => [
				"UPDATE modules set settingsClassPath = '/sys/RBdigital/RBdigitalSetting.php', settingsClassName = 'RBdigitalSetting' WHERE name = 'Hoopla'"
			]
		],

		'rbdigital_usage_add_instance' => [
			'title' => 'RBdigital Usage - Instance Information',
			'description' => 'Add Instance Information to RBdigital Usage stats',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE rbdigital_record_usage ADD COLUMN instance VARCHAR(100)',
				'ALTER TABLE rbdigital_record_usage DROP INDEX rbdigitalId',
				'ALTER TABLE rbdigital_record_usage ADD UNIQUE INDEX (instance, rbdigitalId, year, month)',
				'ALTER TABLE rbdigital_magazine_usage ADD COLUMN instance VARCHAR(100)',
				'ALTER TABLE rbdigital_magazine_usage DROP INDEX magazineId',
				'ALTER TABLE rbdigital_magazine_usage ADD UNIQUE INDEX (instance, magazineId, year, month)',
				'ALTER TABLE user_rbdigital_usage ADD COLUMN instance VARCHAR(100)',
				'ALTER TABLE user_rbdigital_usage DROP INDEX userId',
				'ALTER TABLE user_rbdigital_usage ADD UNIQUE INDEX (instance, userId, year, month)',
			]
		],
	);
}

/** @noinspection PhpUnused */
function updateRbDigitalScopes(){
	require_once ROOT_DIR . '/sys/RBdigital/RBdigitalSetting.php';
	require_once ROOT_DIR . '/sys/RBdigital/RBdigitalScope.php';
	$rbDigitalSettings = new RBdigitalSetting();
	if ($rbDigitalSettings->find(true)){
		$rbDigitalScopes = new RBdigitalScope();
		$rbDigitalScopes->find();
		while ($rbDigitalScopes->fetch()){
			$rbDigitalScopes->settingId = $rbDigitalSettings->id;
			$rbDigitalScopes->update();
		}
	}
}

/** @noinspection PhpUnused */
function updateRbDigitalAvailability(&$update){
	require_once ROOT_DIR . '/sys/RBdigital/RBdigitalSetting.php';
	$rbDigitalSettings = new RBdigitalSetting();
	if ($rbDigitalSettings->find(true)){
		/** @var PDO $aspen_db */
		global $aspen_db;

		try {
			$aspen_db->query("UPDATE rbdigital_availability set settingId = {$rbDigitalSettings->id}");
			if (!isset($update['status'])) {
				$update['status'] = 'Update succeeded';
			}
		} catch (PDOException $e) {
			if (isset($update['continueOnError']) && $update['continueOnError']) {
				if (!isset($update['status'])) {
					$update['status'] = '';
				}
				$update['status'] .= 'Warning: ' . $e;
			} else {
				$update['status'] = 'Update failed ' . $e;
			}
		}
	}
}