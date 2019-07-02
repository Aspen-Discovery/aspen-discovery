<?php
/**
 * Updates related to rbdigital for cleanliness
 */

function getRbdigitalUpdates() {
	return array(
        'variables_lastRbdigitalExport' => array(
            'title' => 'Variables Last Rbdigital Export Time',
            'description' => 'Add a variable for when Rbdigital data was extracted from the API last.',
            'sql' => array(
                "INSERT INTO variables (name, value) VALUES ('lastRbdigitalExport', 'false')",
            ),
        ),

        'rbdigital_exportTables' => array(
            'title' => 'Rbdigital title tables',
            'description' => 'Create tables to store data exported from rbdigital.',
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
            'title' => 'Rbdigital availability tables',
            'description' => 'Create tables to store data exported from rbdigital.',
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
            'title' => 'Rbdigital export log',
            'description' => 'Create log for rbdigital export.',
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
            'title' => 'Rbdigital Usage by user',
            'description' => 'Add a table to track how often a particular user uses Rbdigital.',
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
			'title' => 'Rbdigital Record Usage',
			'description' => 'Add a table to track how records within Rbdigital are used.',
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
			'title' => 'Rbdigital Magazine Usage',
			'description' => 'Add a table to track how magazines within Rbdigital are used.',
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
            'title' => 'Add Rbdigital Settings',
            'description' => 'Add Settings for Rbdigital to move configuration out of ini',
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
            'title' => 'Better Rbdigital export log',
            'description' => 'Add additional info for Rbdigital export log.',
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
			'title' => 'Rbdigital magazine tables',
			'description' => 'Create tables to store data exported from rbdigital.',
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
			'title' => 'Rbdigital Scoping',
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
	);
}