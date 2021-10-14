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

		'events_module_log_checks' => [
			'title' => 'Events Module Log Checks',
			'description' => 'Automatically monitor logs for the Events module',
			'sql' => [
				"UPDATE modules set logClassPath='/sys/Events/EventsIndexingLogEntry.php', logClassName='EventsIndexingLogEntry' WHERE name = 'Events'"
			]
		],

		'events_add_settings' => [
			'title' => 'Add Settings to Events module',
			'description' => 'Add Settings to Events module',
			'sql' => [
				"UPDATE modules set settingsClassPath = '/sys/Events/LMLibraryCalendarSetting.php', settingsClassName = 'LMLibraryCalendarSetting' WHERE name = 'Events'"
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

		'lm_library_calendar_private_feed_settings' => [
			'title' => 'Library Market Library Calendar - Private Feed Settings',
			'description' => 'Add properties to retrieve the private feed',
			'sql' => [
				'ALTER TABLE lm_library_calendar_settings ADD COLUMN clientId VARCHAR(36)',
				'ALTER TABLE lm_library_calendar_settings ADD COLUMN clientSecret VARCHAR(36)',
				"ALTER TABLE lm_library_calendar_settings ADD COLUMN username VARCHAR(36) default 'lc_feeds_staffadmin'",
				"ALTER TABLE lm_library_calendar_settings ADD COLUMN password VARCHAR(36)",
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
			'description' => 'Setup tables to store events data for Library Calendar',
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
		],

		'aspen_usage_events' => [
			'title' => 'Aspen Usage for Event Searches',
			'description' => 'Add a column to track usage of event searches within Aspen',
			'continueOnError' => false,
			'sql' => array(
				'ALTER TABLE aspen_usage ADD COLUMN eventsSearches INT(11) DEFAULT 0',
			)
		],

		'track_event_user_usage' => [
			'title' => 'Event Usage by user',
			'description' => 'Add a table to track how often a particular user uses indexed events.',
			'sql' => [
				"CREATE TABLE user_events_usage (
				    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				    userId INT(11) NOT NULL,
				    type VARCHAR(25) NOT NULL,
				    source INT(11) NOT NULL,
				    month INT(2) NOT NULL,
				    year INT(4) NOT NULL,
				    usageCount INT(11)
				) ENGINE = InnoDB",
				"ALTER TABLE user_events_usage ADD INDEX (type, source, year, month, userId)",
			],
		],

		'event_record_usage' => [
			'title' => 'Event Usage',
			'description' => 'Add a table to track how events are used.',
			'continueOnError' => true,
			'sql' => [
				"CREATE TABLE events_usage (
				    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				    type VARCHAR(25) NOT NULL,
				    source INT(11) NOT NULL,
				    identifier VARCHAR(36) NOT NULL,
				    month INT(2) NOT NULL,
				    year INT(4) NOT NULL,
				    timesViewedInSearch INT(11) NOT NULL,
				    timesUsed INT(11) NOT NULL
				) ENGINE = InnoDB",
				"ALTER TABLE events_usage ADD INDEX (type, source, identifier, year, month)",
			],
		],

		'events_spotlights' => [
			'title' => 'Create Events Spotlights',
			'description' => 'Add a table for Events Spotlights',
			'sql' => [
				"CREATE TABLE events_spotlights (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(255) NOT NULL,
					showNameAsTitle TINYINT(1),
					description TEXT, 
					showDescription TINYINT(1) DEFAULT 0, 
					showEventImages TINYINT(1) DEFAULT 1,
					showEventDescriptions TINYINT(1) DEFAULT 1,
					searchTerm VARCHAR(500) NOT NULL DEFAULT '',
					defaultFilter TEXT,
					defaultSort ENUM('relevance', 'start_date_sort', 'title_sort')
				) ENGINE = InnoDB"
			]
		]
	];
}