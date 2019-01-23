<?php
/**
 * Updates related to hoopla for cleanliness
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/29/14
 * Time: 2:25 PM
 */

function getHooplaUpdates() {
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
								) ENGINE = INNODB",
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
									) ENGINE = INNODB;",
					)
			),
	);
}