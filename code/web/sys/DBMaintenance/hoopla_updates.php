<?php

function getHooplaUpdates()
{
	return array(
		'hoopla_exportTables' => array(
			'title' => 'Hoopla export tables',
			'description' => 'Create tables to store data exported from hoopla.',
			'sql' => array(
				"CREATE TABLE hoopla_export ( 
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					hooplaId INT NOT NULL,
					active TINYINT NOT NULL DEFAULT 1,
					title VARCHAR(255),
					kind VARCHAR(50),
					pa TINYINT NOT NULL DEFAULT 0,
					demo TINYINT NOT NULL DEFAULT 0,
					profanity TINYINT NOT NULL DEFAULT 0,
					rating VARCHAR(10),
					abridged TINYINT NOT NULL DEFAULT 0,
					children TINYINT NOT NULL DEFAULT 0,
					price DOUBLE NOT NULL DEFAULT 0,
					UNIQUE(hooplaId)
				) ENGINE = InnoDB",
			),
		),

		'hoopla_exportLog' => array(
			'title' => 'Hoopla export log',
			'description' => 'Create log for hoopla export.',
			'sql' => array(
				"CREATE TABLE IF NOT EXISTS hoopla_export_log(
					`id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of log', 
					`startTime` INT(11) NOT NULL COMMENT 'The timestamp when the run started', 
					`endTime` INT(11) NULL COMMENT 'The timestamp when the run ended', 
					`lastUpdate` INT(11) NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)', 
					`notes` TEXT COMMENT 'Additional information about the run', 
					PRIMARY KEY ( `id` )
				) ENGINE = InnoDB;",
			)
		),

		'hoopla_exportLog_update' => array(
			'title' => 'Better Hoopla export log',
			'description' => 'Add additional info to Hoopla export log.',
			'sql' => array(
				"ALTER TABLE hoopla_export_log ADD COLUMN numProducts INT(11) DEFAULT 0",
				"ALTER TABLE hoopla_export_log ADD COLUMN numErrors INT(11) DEFAULT 0",
				"ALTER TABLE hoopla_export_log ADD COLUMN numAdded INT(11) DEFAULT 0",
				"ALTER TABLE hoopla_export_log ADD COLUMN numDeleted INT(11) DEFAULT 0",
				"ALTER TABLE hoopla_export_log ADD COLUMN numUpdated INT(11) DEFAULT 0",
			)
		),

		'hoopla_exportLog_skips' => array(
			'title' => 'Add skips for hoopla export log',
			'description' => 'Add additional info to Hoopla export log.',
			'sql' => array(
				"ALTER TABLE hoopla_export_log ADD COLUMN numSkipped INT(11) DEFAULT 0",
			)
		),

		'hoopla_export_include_raw_data' => array(
			'title' => 'Update Hoopla export raw data',
			'description' => 'Update Hoopla export table to add raw data from API calls',
			'sql' =>array  (
				"ALTER TABLE hoopla_export ADD COLUMN rawChecksum BIGINT",
				"ALTER TABLE hoopla_export ADD COLUMN rawResponse MEDIUMTEXT",
				"ALTER TABLE hoopla_export ADD COLUMN dateFirstDetected bigint(20) DEFAULT NULL"
			)
		),

		'hoopla_add_settings' => array(
			'title' => 'Add Hoopla Settings',
			'description' => 'Add Settings for Hoopla to move configuration out of ini',
			'sql' => array(
				"CREATE TABLE IF NOT EXISTS hoopla_settings(
						id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
						apiUrl VARCHAR(255),
						libraryId INT(11) DEFAULT 0,
						apiUsername VARCHAR(50),
						apiPassword VARCHAR(50),
						runFullUpdate TINYINT(1) DEFAULT 0,
						lastUpdateOfChangedRecords INT(11) DEFAULT 0,
						lastUpdateOfAllRecords INT(11) DEFAULT 0
					)",
			),
		),

		'track_hoopla_user_usage' => array(
			'title' => 'Hoopla Usage by user',
			'description' => 'Add a table to track how often a particular user uses Hoopla.',
			'sql' => array(
				"CREATE TABLE user_hoopla_usage (
                    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    userId INT(11) NOT NULL,
                    year INT(4) NOT NULL,
                    month INT(2) NOT NULL,
                    usageCount INT(11)
                ) ENGINE = InnoDB",
				"ALTER TABLE user_hoopla_usage ADD INDEX (userId, year, month)",
				"ALTER TABLE user_hoopla_usage ADD INDEX (year, month)",
			),
		),

		'track_hoopla_record_usage' => array(
			'title' => 'Hoopla Record Usage',
			'description' => 'Add a table to track how records within Hoopla are used.',
			'continueOnError' => true,
			'sql' => array(
				"CREATE TABLE hoopla_record_usage (
                    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    hooplaId INT(11),
                    year INT(4) NOT NULL,
                    month INT(2) NOT NULL,
                    timesCheckedOut INT(11) NOT NULL
                ) ENGINE = InnoDB",
				"ALTER TABLE hoopla_record_usage ADD INDEX (hooplaId, year, month)",
				"ALTER TABLE hoopla_record_usage ADD INDEX (year, month)",
			),
		),

		'hoopla_scoping' => [
			'title' => 'Hoopla Scoping',
			'description' => 'Add a table to define what information should be included within search results',
			'sql' => [
				'CREATE TABLE hoopla_scopes (
    				id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    				name VARCHAR(50) NOT NULL,
    				includeEBooks TINYINT DEFAULT 1,
    				maxCostPerCheckoutEBooks FLOAT DEFAULT 5,
    				includeEComics TINYINT DEFAULT 1,
    				maxCostPerCheckoutEComics FLOAT DEFAULT 5,
    				includeEAudiobook TINYINT DEFAULT 1,
    				maxCostPerCheckoutEAudiobook FLOAT DEFAULT 5,
    				includeMovies TINYINT DEFAULT 1,
    				maxCostPerCheckoutMovies FLOAT DEFAULT 5,
    				includeMusic TINYINT DEFAULT 1,
    				maxCostPerCheckoutMusic FLOAT DEFAULT 5,
    				includeTelevision TINYINT DEFAULT 1,
    				maxCostPerCheckoutTelevision FLOAT DEFAULT 5,
    				restrictToChildrensMaterial TINYINT DEFAULT 0,
    				ratingsToExclude VARCHAR(100),
    				excludeAbridged TINYINT DEFAULT 0,
    				excludeParentalAdvisory TINYINT DEFAULT 0,
    				excludeProfanity TINYINT DEFAULT 0
				) ENGINE = InnoDB'
			]
		],

		'hoopla_filter_records_from_other_vendors' =>[
			'title'=> 'Hoopla Filter Records Purchased from other vendors',
			'description' =>'Add an option to exclude hoopla titles purchased from other vendors',
			'sql' => [
				'ALTER TABLE hoopla_settings ADD COLUMN excludeTitlesWithCopiesFromOtherVendors TINYINT DEFAULT 0'
			]
		],

		'create_hoopla_module' => [
			'title' => 'Create Hoopla Module',
			'description' => 'Setup Hoopla module',
			'sql' => [
				"INSERT INTO modules (name, indexName, backgroundProcess) VALUES ('Hoopla', 'grouped_works', 'hoopla_export')"
			]
		],

		'disable_hoopla_module_auto_restart' => [
			'title' => 'Disable Hoopla Auto Restart',
			'description' => 'Disable Hoopla Auto Restart',
			'sql' => [
				"UPDATE modules SET backgroundProcess = '' WHERE name = 'Hoopla'",
			]
		],

		're_enable_hoopla_module_auto_restart' => [
			'title' => 'Re-enable Hoopla Auto Restart',
			'description' => 'Re-enable Hoopla Auto Restart',
			'sql' => [
				"UPDATE modules SET backgroundProcess = 'hoopla_export' WHERE name = 'Hoopla'",
			]
		],

		'hoopla_module_add_log' =>[
			'title' => 'Hoopla add log info to module',
			'description' => 'Add logging information to Hoopla module',
			'sql' => [
				"UPDATE modules set logClassPath='/sys/Hoopla/HooplaExportLogEntry.php', logClassName='HooplaExportLogEntry' WHERE name='Hoopla'",
			]
		],

		'hoopla_add_settings' => [
			'title' => 'Add Settings to Hoopla module',
			'description' => 'Add Settings to Hoopla module',
			'sql' => [
				"UPDATE modules set settingsClassPath = '/sys/Hoopla/HooplaSetting.php', settingsClassName = 'HooplaSetting' WHERE name = 'Hoopla'"
			]
		],

		'hoopla_add_setting_to_scope' => [
			'title' => 'Add settingId to Hoopla scope',
			'description' => 'Allow multiple settings to be defined for Hoopla within a consortium',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE hoopla_scopes ADD column settingId INT(11)',
				'updateHooplaScopes'
			]
		],

		'hoopla_usage_add_instance' => [
			'title' => 'Hoopla Usage - Instance Information',
			'description' => 'Add Instance Information to Hoopla Usage stats',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE hoopla_record_usage ADD COLUMN instance VARCHAR(100)',
				'ALTER TABLE hoopla_record_usage DROP INDEX hooplaId',
				'ALTER TABLE hoopla_record_usage ADD UNIQUE INDEX (instance, hooplaId, year, month)',
				'ALTER TABLE user_hoopla_usage ADD COLUMN instance VARCHAR(100)',
				'ALTER TABLE user_hoopla_usage DROP INDEX userId',
				'ALTER TABLE user_hoopla_usage ADD UNIQUE INDEX (instance, userId, year, month)',
			]
		],
	);
}

/** @noinspection PhpUnused */
function updateHooplaScopes(){
	require_once ROOT_DIR . '/sys/Hoopla/HooplaSetting.php';
	require_once ROOT_DIR . '/sys/Hoopla/HooplaScope.php';
	$hooplaSettings = new HooplaSetting();
	if ($hooplaSettings->find(true)){
		$hooplaScopes = new HooplaScope();
		$hooplaScopes->find();
		while ($hooplaScopes->fetch()){
			$hooplaScopes->settingId = $hooplaSettings->id;
			$hooplaScopes->update();
		}
	}
}