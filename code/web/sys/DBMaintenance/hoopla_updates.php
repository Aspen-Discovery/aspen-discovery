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
	);
}