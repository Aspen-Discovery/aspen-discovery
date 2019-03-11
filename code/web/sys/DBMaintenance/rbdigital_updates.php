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
	);
}