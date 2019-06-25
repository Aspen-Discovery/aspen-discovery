<?php

function getHooplaUpdates()
{
	return array(
		'variables_lastHooplaExport' => array(
			'title' => 'Variables Last Hoopla Export Time',
			'description' => 'Add a variable for when hoopla data was extracted from the API last.',
			'sql' => array(
				"INSERT INTO variables (name, value) VALUES ('lastHooplaExport', 'false')",
			),
		),

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
		]
	);
}