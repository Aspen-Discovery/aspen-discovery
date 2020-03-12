<?php
function getEventsIntegrationUpdates(){
	return [
		'create_events_module' => [
			'title' => 'Create Events Module',
			'description' => 'Setup the events module',
			'sql' => [
				"INSERT INTO modules (name, indexName, backgroundProcess) VALUES ('Events', 'events', 'events_indexer')"
			]
		],

		'lm_library_calendar_settings' => [
			'title' => 'Define events settings for Library Market - Library Calendar integration',
			'description' => 'Initial setup of the library market integration',
			'sql' => [
				'CREATE TABLE lm_library_calendar_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(100) NOT NULL UNIQUE,
					baseUrl VARCHAR(255) NOT NULL
				) ENGINE INNODB',
			]
		],

		'library_events_setting' => [
			'title' => 'Library Events Settings',
			'description' => 'Initial setup link between library events settings and libraries',
			'sql' => [
				'CREATE TABLE library_events_setting (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					settingSource VARCHAR(25) NOT NULL,
					settingId INT(11) NOT NULL,
					libraryId INT(11) NOT NULL,
					UNIQUE(settingSource, settingId, libraryId)
				) ENGINE INNODB',
			]
		],

		'lm_library_calendar_events_data' => [
			'title' => 'Library Calendar Events Data' ,
			'description' => 'Setup tables to store events data',
			'sql' => [
				'CREATE TABLE lm_library_calendar_events (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					settingsId INT NOT NULL,
					externalId varchar(36) NOT NULL,
					title varchar(255) NOT NULL,
					rawChecksum BIGINT,
					rawResponse MEDIUMTEXT,
					deleted TINYINT default 0,
					UNIQUE (settingsId, externalId)
				)'
			]
		],

		'events_indexing_log' => [
			'title' => 'Events indexing log',
			'description' => 'Create log for Event indexing.',
			'sql' => array(
				"CREATE TABLE IF NOT EXISTS events_indexing_log(
					`id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of log entry', 
					name VARCHAR(150) NOT NULL,
					`startTime` INT(11) NOT NULL COMMENT 'The timestamp when the run started', 
					`endTime` INT(11) NULL COMMENT 'The timestamp when the run ended', 
					`lastUpdate` INT(11) NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)', 
					`notes` TEXT COMMENT 'Additional information about the run',
					numEvents INT(11) DEFAULT 0,
					numErrors INT(11) DEFAULT 0,
					numAdded INT(11) DEFAULT 0,
					numDeleted INT(11) DEFAULT 0,
					numUpdated INT(11) DEFAULT 0,
					PRIMARY KEY ( `id` )
				) ENGINE = InnoDB;",
			)
		]
	];
}