<?php

function getWebsiteIndexingUpdates()
{
	return array(
		'website_indexing_tables' => array(
			'title' => 'Website Indexing tables',
			'description' => 'Create tables for websites to be indexed.',
			'sql' => array(
				"CREATE TABLE website_indexing_settings (
			    	id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				    name VARCHAR(75) NOT NULL,
				    searchCategory VARCHAR(75) NOT NULL,
				    siteUrl VARCHAR(255),
				    indexFrequency ENUM('hourly', 'daily', 'weekly', 'monthly', 'yearly', 'once'),
				    lastIndexed INT(11),
				    UNIQUE(name)
				) ENGINE = InnoDB",
				"ALTER TABLE website_indexing_settings ADD INDEX(lastIndexed)",
				"CREATE TABLE website_pages (
				    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				    websiteId INT NOT NULL,
				    url VARCHAR(255),
				    checksum BIGINT,
				    deleted TINYINT(1),
				    firstDetected INT(11),
				    UNIQUE (url)
				) ENGINE = InnoDB",
				"ALTER TABLE website_pages ADD INDEX(websiteId)",
				"CREATE TABLE IF NOT EXISTS website_index_log(
				    `id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of log',
				    websiteName VARCHAR(255) NOT NULL, 
				    `startTime` INT(11) NOT NULL COMMENT 'The timestamp when the run started', 
				    `endTime` INT(11) NULL COMMENT 'The timestamp when the run ended', 
				    `lastUpdate` INT(11) NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)', 
				    `notes` TEXT COMMENT 'Additional information about the run',
				    numPages INT(11) DEFAULT 0,
				    numAdded INT(11) DEFAULT 0,
				    numDeleted INT(11) DEFAULT 0,
				    numUpdated INT(11) DEFAULT 0,
				    numErrors INT(11) DEFAULT 0, 
				    PRIMARY KEY ( `id` )
				) ENGINE = InnoDB;",
				"ALTER TABLE website_index_log ADD INDEX(websiteName)",
			),
		),

		'track_website_user_usage' => array(
			'title' => 'Website Usage by user',
			'description' => 'Add a table to track how often a particular user uses indexed websites.',
			'sql' => array(
				"CREATE TABLE user_website_usage (
				    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				    userId INT(11) NOT NULL,
				    websiteId INT(11) NOT NULL,
				    month INT(2) NOT NULL,
				    year INT(4) NOT NULL,
				    usageCount INT(11)
				) ENGINE = InnoDB",
				"ALTER TABLE user_website_usage ADD INDEX (websiteId, year, month, userId)",
			),
		),

		'website_record_usage' => array(
			'title' => 'Website Page Usage',
			'description' => 'Add a table to track how pages within indexed sites are viewed.',
			'continueOnError' => true,
			'sql' => array(
				"CREATE TABLE website_page_usage (
				    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				    webPageId INT(11),
				    month INT(2) NOT NULL,
				    year INT(4) NOT NULL,
				    timesViewedInSearch INT(11) NOT NULL,
				    timesUsed INT(11) NOT NULL
				) ENGINE = InnoDB",
				"ALTER TABLE website_page_usage ADD INDEX (webPageId, year, month)",
			),
		),

		'website_usage_add_instance' => [
			'title' => 'Website Usage - Instance Information',
			'description' => 'Add Instance Information to Website Usage stats',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE website_page_usage ADD COLUMN instance VARCHAR(100)',
				'ALTER TABLE website_page_usage DROP INDEX webPageId',
				'ALTER TABLE website_page_usage ADD INDEX (instance, webPageId, year, month)',
				'ALTER TABLE user_website_usage ADD COLUMN instance VARCHAR(100)',
				'ALTER TABLE user_website_usage DROP INDEX websiteId',
				'ALTER TABLE user_website_usage ADD INDEX (instance, websiteId, year, month)',
			]
		],

		'create_web_indexer_module' => [
			'title' => 'Create Web Indexer Module',
			'description' => 'Setup Web Indexer module',
			'sql' => [
				//oai indexer runs daily so we don't check the background process
				"INSERT INTO modules (name, indexName, backgroundProcess) VALUES ('Web Indexer', 'website_pages', 'web_indexer')"
			]
		],

		'web_indexer_add_paths_to_exclude' => [
			'title' => 'Web Indexer Add Paths To Exclude',
			'description' => 'Allow specific paths to not be indexed using the web indexer',
			'sql' => [
				"ALTER TABLE website_indexing_settings ADD COLUMN pathsToExclude MEDIUMTEXT",
			]
		],

		'web_indexer_module_add_log' =>[
			'title' => 'Web Indexer add log info to module',
			'description' => 'Add logging information to web indexer module',
			'sql' => [
				"UPDATE modules set logClassPath='/sys/WebsiteIndexing/WebsiteIndexLogEntry.php', logClassName='WebsiteIndexLogEntry' WHERE name='Web Indexer'",
			]
		],

		'web_builder_add_settings' => [
			'title' => 'Add Settings to Web Builder module',
			'description' => 'Add Settings to Web Builder module',
			'sql' => [
				"UPDATE modules set settingsClassPath = '/sys/WebsiteIndexing/WebsiteIndexSetting.php', settingsClassName = 'WebsiteIndexSetting' WHERE name = 'Web Indexer'"
			]
		],


		'web_indexer_add_title_expression' =>[
			'title' => 'Web Indexer add title expression',
			'description' => 'Add a regular expression to extract titles from',
			'sql' => [
				"ALTER TABLE website_indexing_settings ADD COLUMN pageTitleExpression VARCHAR(255) DEFAULT ''",
			]
		],

		'web_indexer_add_description_expression' =>[
			'title' => 'Web Indexer add description expression',
			'description' => 'Add a regular expression to extract description from',
			'sql' => [
				"ALTER TABLE website_indexing_settings ADD COLUMN descriptionExpression VARCHAR(255) DEFAULT ''",
			]
		],

		'web_indexer_deleted_settings' => [
			'title' => 'Web Indexer add the ability to delete settings',
			'description' => 'Add deleted field for website indexing settings',
			'sql' => [
				'ALTER TABLE website_indexing_settings ADD COLUMN deleted TINYINT(1) DEFAULT 0'
			]
		],

		'web_indexer_scoping' => [
			'title' => 'Web Indexer scoping',
			'description' => 'Add scoping for the web indexer',
			'sql' => [
				'CREATE TABLE library_website_indexing (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					settingId INT(11) NOT NULL,
					libraryId INT(11) NOT NULL,
					UNIQUE (settingId, libraryId)
				) ENGINE = InnoDB',
				'CREATE TABLE location_website_indexing (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					settingId INT(11) NOT NULL,
					locationId INT(11) NOT NULL,
					UNIQUE (settingId, locationId)
				) ENGINE = InnoDB'
			]
		],

		'web_indexer_max_pages_to_index' => [
			'title' => 'Web Indexer add a maximum number of pages to index',
			'description' => 'Add a maximum number of pages to index for website indexing settings',
			'sql' => [
				'ALTER TABLE website_indexing_settings ADD COLUMN maxPagesToIndex INT(11) DEFAULT 2500'
			]
		],

	);
}