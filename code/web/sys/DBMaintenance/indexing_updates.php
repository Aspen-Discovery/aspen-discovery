<?php

function getIndexingUpdates()
{
	/** @noinspection SqlResolve */
	return array(
		'ils_hold_summary' => array(
			'title' => 'ILS Hold Summary',
			'description' => 'Create ils hold summary table to store summary information about the available holds',
			'sql' => array(
				"CREATE TABLE ils_hold_summary (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					ilsId VARCHAR (20) NOT NULL,
					numHolds INT(11) DEFAULT 0,
					UNIQUE(ilsId)
				) ENGINE = InnoDB"
			),
		),

		'indexing_profile' => array(
			'title' => 'Indexing profile setup',
			'description' => 'Setup indexing information table to store information about how to index ',
			'sql' => array(
				"CREATE TABLE IF NOT EXISTS `indexing_profiles` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `name` varchar(50) NOT NULL,
				  `marcPath` varchar(100) NOT NULL,
				  `marcEncoding` enum('MARC8','UTF8','UNIMARC','ISO8859_1','BESTGUESS') NOT NULL DEFAULT 'UTF8',
				  `individualMarcPath` varchar(100) NOT NULL,
				  `groupingClass` varchar(100) NOT NULL DEFAULT 'MarcRecordGrouper',
				  `indexingClass` varchar(50) NOT NULL,
				  `recordDriver` varchar(100) NOT NULL DEFAULT 'MarcRecord',
				  `recordUrlComponent` varchar(25) NOT NULL DEFAULT 'Record',
				  `formatSource` enum('bib','item') NOT NULL DEFAULT 'bib',
				  `recordNumberTag` char(3) NOT NULL,
				  `recordNumberPrefix` varchar(10) NOT NULL,
				  `suppressItemlessBibs` tinyint(1) NOT NULL DEFAULT '1',
				  `itemTag` char(3) NOT NULL,
				  `itemRecordNumber` char(1) DEFAULT NULL,
				  `useItemBasedCallNumbers` tinyint(1) NOT NULL DEFAULT '1',
				  `callNumberPrestamp` char(1) DEFAULT NULL,
				  `callNumber` char(1) DEFAULT NULL,
				  `callNumberCutter` char(1) DEFAULT NULL,
				  `callNumberPoststamp` varchar(1) DEFAULT NULL,
				  `location` char(1) DEFAULT NULL,
				  `locationsToSuppress` varchar(100) DEFAULT NULL,
				  `subLocation` char(1) DEFAULT NULL,
				  `shelvingLocation` char(1) DEFAULT NULL,
				  `volume` varchar(1) DEFAULT NULL,
				  `itemUrl` char(1) DEFAULT NULL,
				  `barcode` char(1) DEFAULT NULL,
				  `status` char(1) DEFAULT NULL,
				  `statusesToSuppress` varchar(100) DEFAULT NULL,
				  `totalCheckouts` char(1) DEFAULT NULL,
				  `lastYearCheckouts` char(1) DEFAULT NULL,
				  `yearToDateCheckouts` char(1) DEFAULT NULL,
				  `totalRenewals` char(1) DEFAULT NULL,
				  `iType` char(1) DEFAULT NULL,
				  `dueDate` char(1) DEFAULT NULL,
				  `dateCreated` char(1) DEFAULT NULL,
				  `dateCreatedFormat` varchar(20) DEFAULT NULL,
				  `iCode2` char(1) DEFAULT NULL,
				  `useICode2Suppression` tinyint(1) NOT NULL DEFAULT '1',
				  `format` char(1) DEFAULT NULL,
				  `eContentDescriptor` char(1) DEFAULT NULL,
				  `orderTag` char(3) DEFAULT NULL,
				  `orderStatus` char(1) DEFAULT NULL,
				  `orderLocation` char(1) DEFAULT NULL,
				  `orderCopies` char(1) DEFAULT NULL,
				  `orderCode3` char(1) DEFAULT NULL,
				  PRIMARY KEY (`id`),
				  UNIQUE KEY `name` (`name`)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8",
				"CREATE TABLE IF NOT EXISTS `translation_maps` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `indexingProfileId` int(11) NOT NULL,
				  `name` varchar(50) NOT NULL,
				  PRIMARY KEY (`id`),
				  UNIQUE KEY `profileName` (`indexingProfileId`,`name`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8",
				"CREATE TABLE IF NOT EXISTS `translation_map_values` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `translationMapId` int(11) NOT NULL,
				  `value` varchar(50) NOT NULL,
				  `translation` varchar(255) NOT NULL,
				  PRIMARY KEY (`id`),
				  UNIQUE KEY (`translationMapId`,`value`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8",
				"CREATE TABLE IF NOT EXISTS `library_records_owned` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `libraryId` int(11) NOT NULL,
				  `indexingProfileId` int(11) NOT NULL,
				  `location` varchar(100) NOT NULL,
				  `subLocation` varchar(100) NOT NULL,
				  PRIMARY KEY (`id`)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8",
				"CREATE TABLE IF NOT EXISTS `library_records_to_include` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `libraryId` int(11) NOT NULL,
				  `indexingProfileId` int(11) NOT NULL,
				  `location` varchar(100) NOT NULL,
				  `subLocation` varchar(100) NOT NULL,
				  `includeHoldableOnly` tinyint(4) NOT NULL DEFAULT '1',
				  `includeItemsOnOrder` tinyint(1) NOT NULL DEFAULT '0',
				  `includeEContent` tinyint(1) NOT NULL DEFAULT '0',
				  `weight` int(11) NOT NULL,
				  PRIMARY KEY (`id`),
				  KEY `libraryId` (`libraryId`,`indexingProfileId`)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8",
				"CREATE TABLE IF NOT EXISTS `location_records_owned` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `locationId` int(11) NOT NULL,
				  `indexingProfileId` int(11) NOT NULL,
				  `location` varchar(100) NOT NULL,
				  `subLocation` varchar(100) NOT NULL,
				  PRIMARY KEY (`id`)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8",
				"CREATE TABLE IF NOT EXISTS `location_records_to_include` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `locationId` int(11) NOT NULL,
				  `indexingProfileId` int(11) NOT NULL,
				  `location` varchar(100) NOT NULL,
				  `subLocation` varchar(100) NOT NULL,
				  `includeHoldableOnly` tinyint(4) NOT NULL DEFAULT '1',
				  `includeItemsOnOrder` tinyint(1) NOT NULL DEFAULT '0',
				  `includeEContent` tinyint(1) NOT NULL DEFAULT '0',
				  `weight` int(11) NOT NULL,
				  PRIMARY KEY (`id`),
				  KEY `locationId` (`locationId`,`indexingProfileId`)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8",
			)
		),

		'indexing_records_default_sub_location' => [
			'title' => 'Set Sub Location defaults for Indexing Records',
			'description' => 'Set Sub Location defaults for records owned and records to include',
			'sql' => [
				"ALTER TABLE library_records_owned CHANGE COLUMN subLocation subLocation varchar(100) NOT NULL DEFAULT ''",
				"ALTER TABLE library_records_to_include CHANGE COLUMN subLocation subLocation varchar(100) NOT NULL DEFAULT ''",
				"ALTER TABLE location_records_owned CHANGE COLUMN subLocation subLocation varchar(100) NOT NULL DEFAULT ''",
				"ALTER TABLE location_records_to_include CHANGE COLUMN subLocation subLocation varchar(100) NOT NULL DEFAULT ''",
			]
		],

		'indexing_profile_collection' => array(
			'title' => 'Indexing profile collections',
			'description' => 'Add handling of collections to indexing profile table',
			'sql' => array(
				"ALTER TABLE indexing_profiles ADD COLUMN `collection` char(1) DEFAULT NULL"
			)
		),

		'indexing_profile_catalog_driver' => array(
			'title' => 'Indexing profile catalog driver',
			'description' => 'Add handling catalog driver to indexing profile table',
			'sql' => array(
				"ALTER TABLE indexing_profiles ADD COLUMN `catalogDriver` varchar(50) DEFAULT NULL"
			)
		),

		'indexing_profile_holdability' => array(
			'title' => 'Setup additional holdability filters',
			'description' => 'Setup additional filters for determining if something is holdable',
			'sql' => array(
				"ALTER TABLE indexing_profiles ADD COLUMN `nonHoldableITypes` varchar(255) DEFAULT NULL",
				"ALTER TABLE indexing_profiles ADD COLUMN `nonHoldableStatuses` varchar(255) DEFAULT NULL",
				"ALTER TABLE indexing_profiles ADD COLUMN `nonHoldableLocations` varchar(512) DEFAULT NULL",
			)
		),

		'indexing_profile_marc_encoding' => array(
			'title' => 'Indexing Profiles - marc encoding',
			'description' => 'Correct UTF8 setting for marc encoding',
			'sql' => array(
				"ALTER TABLE indexing_profiles CHANGE marcEncoding `marcEncoding` enum('MARC8','UTF8','UNIMARC','ISO8859_1','BESTGUESS') NOT NULL DEFAULT 'MARC8'"
			)
		),

		'indexing_profile_last_checkin_date' => array(
			'title' => 'Indexing Profiles - last checkin date',
			'description' => 'add field for last check in date',
			'sql' => array(
				"ALTER TABLE indexing_profiles ADD COLUMN `lastCheckinFormat` varchar(20) DEFAULT NULL",
				"ALTER TABLE indexing_profiles ADD COLUMN `lastCheckinDate` char(1) DEFAULT NULL",
			)
		),

		'indexing_profile_specific_order_location' => array(
			'title' => 'Indexing Profiles - specific order location',
			'description' => 'add field for the specific location code since Millennium/Sierra do not always export the detailed',
			'sql' => array(
				"ALTER TABLE indexing_profiles ADD COLUMN `orderLocationSingle` char(1) DEFAULT NULL",
			)
		),

		'indexing_profile_specified_formats' => array(
			'title' => 'Indexing Profiles - specified format',
			'description' => 'Allow specified formats for use with side loaded eContent',
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE indexing_profiles CHANGE formatSource `formatSource` enum('bib','item', 'specified') NOT NULL DEFAULT 'bib'",
				"ALTER TABLE indexing_profiles ADD COLUMN `specifiedFormat` varchar(50) DEFAULT NULL",
				"ALTER TABLE indexing_profiles ADD COLUMN `specifiedFormatCategory` varchar(50) DEFAULT NULL",
				"ALTER TABLE indexing_profiles ADD COLUMN `specifiedFormatBoost` int DEFAULT NULL",
			)
		),

		'indexing_profile_filenames_to_include' => array(
			'title' => 'Indexing Profiles - filenames to include',
			'description' => 'Allow additional control over which files are included in an indexing profile',
			'sql' => array(
				"ALTER TABLE indexing_profiles ADD COLUMN `filenamesToInclude` varchar(250) DEFAULT '.*\\\\.ma?rc'",
			)
		),

		'indexing_profile_collectionsToSuppress' => array(
			'title' => 'Indexing Profiles - collections to suppress',
			'description' => 'Allow specific collection codes to be suppressed',
			'sql' => array(
				"ALTER TABLE indexing_profiles ADD COLUMN `collectionsToSuppress` varchar(100) DEFAULT ''",
			)
		),

		'indexing_profile_folderCreation' => array(
			'title' => 'Indexing Profiles - Individual Folder Creation',
			'description' => 'Determine how marc record folders should be created',
			'sql' => array(
				"ALTER TABLE indexing_profiles ADD COLUMN `numCharsToCreateFolderFrom` int(11) DEFAULT 4",
				"ALTER TABLE indexing_profiles ADD COLUMN `createFolderFromLeadingCharacters` tinyint(1) DEFAULT 1",
				"UPDATE indexing_profiles SET `numCharsToCreateFolderFrom` = 7 WHERE name = 'hoopla'",
			)
		),

		'indexing_profile_dueDateFormat' => array(
			'title' => 'Indexing Profiles - Due Date Format',
			'description' => 'Set the Due Date Format for an indexing profile',
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE indexing_profiles ADD COLUMN `dueDateFormat` varchar(20) DEFAULT 'yyMMdd'",
				"updateDueDateFormat",
			)
		),

		'indexing_profile_extendLocationsToSuppress' => array(
			'title' => 'Indexing Profiles - Extend Locations To Suppress Size',
			'description' => 'Extend Locations To Suppress Size for an indexing profile',
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE indexing_profiles CHANGE `locationsToSuppress` `locationsToSuppress` varchar(255)",
			)
		),

		'indexing_profile_doAutomaticEcontentSuppression' => array(
			'title' => 'Indexing Profiles - Do Automatic EContent Suppression',
			'description' => 'Allow logic for whether or not automatic econtent suppression is enabled or disabled in an indexing profile',
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE indexing_profiles ADD COLUMN `doAutomaticEcontentSuppression` tinyint(1) DEFAULT 1",
			)
		),

		'indexing_profile_groupUnchangedFiles' => array(
			'title' => 'Indexing Profiles - Group Unchanged Files',
			'description' => 'Allow logic for whether or not files that haven\'t changed since the last grouping are regrouped',
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE indexing_profiles ADD COLUMN `groupUnchangedFiles` tinyint(1) DEFAULT 0",
			)
		),

		'indexing_profile_marc_record_subfield' => array(
			'title' => 'Indexing Profiles - Marc Record Subfield',
			'description' => 'Define the subfield for the marc record',
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE indexing_profiles ADD COLUMN `recordNumberSubfield` char(1) DEFAULT 'a'",
			)
		),

		'translation_map_regex' => array(
			'title' => 'Translation Maps Regex',
			'description' => 'Setup Translation Maps to use regular expressions',
			'sql' => array(
				"ALTER TABLE translation_maps ADD COLUMN `usesRegularExpressions` tinyint(1) DEFAULT 0",
			)
		),

		'volume_information' => array(
			'title' => 'Volume Information',
			'description' => 'Store information about volumes for use within display.  These do not need to be indexed independently.',
			'sql' => array(
				"CREATE TABLE IF NOT EXISTS `ils_volume_info` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `recordId` varchar(50) NOT NULL COMMENT 'Full Record ID including the source',
				  `displayLabel` varchar(255) NOT NULL,
				  `relatedItems` varchar(512) NOT NULL,
				  `volumeId` VARCHAR( 30 ) NOT NULL ,
				  PRIMARY KEY (`id`),
				  KEY `recordId` (`recordId`),
				  UNIQUE `volumeId` (`volumeId`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8",
			)
		),

		'volume_display_order' => [
			'title' => 'Volume display order',
			'description' => 'Add display order to volumes',
			'sql' => [
				'ALTER TABLE ils_volume_info ADD COLUMN displayOrder SMALLINT default 0'
			],
		],

		'volume_increase_display_order' => [
			'title' => 'Volume info - increase display order length',
			'description' => 'Make the field longer to store big things (and negative numbers)',
			'sql' => [
				'ALTER TABLE ils_volume_info CHANGE COLUMN displayOrder displayOrder INT(11) default 0'
			]
		],

		'volume_increase_field_lengths' => [
			'title' => 'Volume info - increase length of volume and related items fields',
			'description' => 'Make the field longer to store big things (and negative numbers)',
			'sql' => [
				'ALTER TABLE ils_volume_info CHANGE COLUMN relatedItems relatedItems TEXT',
				'ALTER TABLE ils_volume_info CHANGE COLUMN displayLabel displayLabel VARCHAR(512) NOT NULL'
			]
		],

		'last_check_in_status_adjustments' => array(
			'title' => 'Last Check In Time Status Adjustments',
			'description' => 'Add additional fields to adjust status based on last check-in time.',
			'sql' => array(
				"CREATE TABLE IF NOT EXISTS `time_to_reshelve` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `indexingProfileId` int(11) NOT NULL,
				  `locations` varchar(100) NOT NULL,
				  `numHoursToOverride` int(11) NOT NULL,
				  `status` varchar(50) NOT NULL,
				  `groupedStatus` varchar(50) NOT NULL,
				  `weight` int(11) NOT NULL,
				  PRIMARY KEY (`id`),
				  KEY (indexingProfileId)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8",
			)
		),

		'records_to_include_2017-06' => array(
			'title' => 'Records To Include Updates 2017.06',
			'description' => 'Additional control over what is included, URL rewriting.',
			'sql' => array(
				"ALTER TABLE library_records_to_include ADD COLUMN iType VARCHAR(100)",
				"ALTER TABLE library_records_to_include ADD COLUMN audience VARCHAR(100)",
				"ALTER TABLE library_records_to_include ADD COLUMN format VARCHAR(100)",
				"ALTER TABLE library_records_to_include ADD COLUMN marcTagToMatch VARCHAR(100)",
				"ALTER TABLE library_records_to_include ADD COLUMN marcValueToMatch VARCHAR(100)",
				"ALTER TABLE library_records_to_include ADD COLUMN includeExcludeMatches TINYINT default 1",
				"ALTER TABLE library_records_to_include ADD COLUMN urlToMatch VARCHAR(100)",
				"ALTER TABLE library_records_to_include ADD COLUMN urlReplacement VARCHAR(100)",

				"ALTER TABLE location_records_to_include ADD COLUMN iType VARCHAR(100)",
				"ALTER TABLE location_records_to_include ADD COLUMN audience VARCHAR(100)",
				"ALTER TABLE location_records_to_include ADD COLUMN format VARCHAR(100)",
				"ALTER TABLE location_records_to_include ADD COLUMN marcTagToMatch VARCHAR(100)",
				"ALTER TABLE location_records_to_include ADD COLUMN marcValueToMatch VARCHAR(100)",
				"ALTER TABLE location_records_to_include ADD COLUMN includeExcludeMatches TINYINT default 1",
				"ALTER TABLE location_records_to_include ADD COLUMN urlToMatch VARCHAR(100)",
				"ALTER TABLE location_records_to_include ADD COLUMN urlReplacement VARCHAR(100)",
			)
		),

		'records_to_include_2018-03' => array(
			'title' => 'Increase Records To Include URL Replacement Column',
			'description' => 'Increase Records To Include URL Replacement Column to 255 characters.',
			'sql' => array(
				"ALTER TABLE `library_records_to_include` CHANGE COLUMN `urlReplacement` `urlReplacement` VARCHAR(255) NULL DEFAULT NULL",
				"ALTER TABLE `location_records_to_include` CHANGE COLUMN `urlReplacement` `urlReplacement` VARCHAR(255) NULL DEFAULT NULL",
			)
		),

		'ils_exportLog' => array(
			'title' => 'ILS export log',
			'description' => 'Create log for ils export via api.',
			'sql' => array(
				"CREATE TABLE IF NOT EXISTS ils_extract_log(
					id INT NOT NULL AUTO_INCREMENT COMMENT 'The id of log', 
					indexingProfile VARCHAR(50) NOT NULL,
					startTime INT(11) NOT NULL COMMENT 'The timestamp when the run started', 
					endTime INT(11) NULL COMMENT 'The timestamp when the run ended', 
					lastUpdate INT(11) NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
					numProducts INT(11) DEFAULT 0,
					numErrors INT(11) DEFAULT 0,
					numAdded INT(11) DEFAULT 0,
					numDeleted INT(11) DEFAULT 0,
					numUpdated INT(11) DEFAULT 0, 
					`notes` TEXT COMMENT 'Additional information about the run', 
					PRIMARY KEY ( `id` )
				) ENGINE = InnoDB;",
			)
		),

		'ils_exportLog_skips' => array(
			'title' => 'ILS export log add skips',
			'description' => 'Add tracking of skipped records to export log',
			'sql' => array(
				"ALTER TABLE ils_extract_log ADD COLUMN numSkipped INT(11) DEFAULT 0",
			)
		),

		'track_ils_user_usage' => array(
			'title' => 'ILS Usage by user',
			'description' => 'Add a table to track how often a particular user uses the ils and side loads.',
			'sql' => array(
				"CREATE TABLE user_ils_usage (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					userId INT(11) NOT NULL,
					indexingProfileId INT(11) NOT NULL,
					year INT(4) NOT NULL,
					month INT(2) NOT NULL,
					usageCount INT(11) DEFAULT 0
				) ENGINE = InnoDB",
				"ALTER TABLE user_ils_usage ADD INDEX (userId, indexingProfileId, year, month)",
				"ALTER TABLE user_ils_usage ADD INDEX (year, month)",
			),
		),

		'track_ils_record_usage' => array(
			'title' => 'ILS/Side Load Record Usage',
			'description' => 'Add a table to track how records within the ils and side loads are used.',
			'continueOnError' => true,
			'sql' => array(
				"CREATE TABLE ils_record_usage (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					indexingProfileId INT(11) NOT NULL,
					recordId VARCHAR(36),
					year INT(4) NOT NULL,
					month INT(2) NOT NULL,
					timesUsed INT(11) NOT NULL
				) ENGINE = InnoDB",
				"ALTER TABLE ils_record_usage ADD INDEX (recordId, year, month)",
				"ALTER TABLE ils_record_usage ADD INDEX (year, month)",
			),
		),

		'track_ils_self_registrations' => [
			'title' => 'Track Self Registrations',
			'description' => 'Track self registrations done by indexing profile',
			'sql' => [
				'ALTER TABLE user_ils_usage ADD COLUMN selfRegistrationCount INT(11) DEFAULT 0'
			]
		],

		'track_pdf_downloads' => [
			'title' => 'Tracking of PDF Downloads',
			'description' => 'Track PDF Downloads by User and by indexing profile',
			'sql' =>[
				'ALTER TABLE user_ils_usage ADD COLUMN pdfDownloadCount INT(11) DEFAULT 0',
				'ALTER TABLE ils_record_usage ADD COLUMN pdfDownloadCount INT(11) DEFAULT 0',
				'ALTER TABLE ils_record_usage CHANGE COLUMN timesUsed timesUsed INT(11) DEFAULT 0',
			]
		],

		'track_pdf_views' => [
			'title' => 'Tracking of PDF Views',
			'description' => 'Track PDF Views by User and by indexing profile',
			'sql' =>[
				'ALTER TABLE user_ils_usage ADD COLUMN pdfViewCount INT(11) DEFAULT 0',
				'ALTER TABLE ils_record_usage ADD COLUMN pdfViewCount INT(11) DEFAULT 0'
			]
		],

		'track_supplemental_file_downloads' => [
			'title' => 'Tracking of Supplemental File Downloads',
			'description' => 'Track Supplemental File Downloads by User and by indexing profile',
			'sql' =>[
				'ALTER TABLE user_ils_usage ADD COLUMN supplementalFileDownloadCount INT(11) DEFAULT 0',
				'ALTER TABLE ils_record_usage ADD COLUMN supplementalFileDownloadCount INT(11) DEFAULT 0',
			]
		],

		'ils_usage_add_instance' => [
			'title' => 'ILS Usage - Instance Information',
			'description' => 'Add Instance Information to ILS Usage stats',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE ils_record_usage ADD COLUMN instance VARCHAR(100)',
				'ALTER TABLE ils_record_usage DROP INDEX recordId',
				'ALTER TABLE ils_record_usage ADD UNIQUE INDEX (instance, indexingProfileId, recordId, year, month)',
				'ALTER TABLE user_ils_usage ADD COLUMN instance VARCHAR(100)',
				'ALTER TABLE user_ils_usage DROP INDEX userId',
				'ALTER TABLE user_ils_usage ADD UNIQUE INDEX (instance, userId, indexingProfileId, year, month)',
			]
		],

		'indexing_profile_add_continuous_update_fields' => [
			'title' => 'Indexing Profile Add Continuous Update Fields',
			'description' => 'Add fields to track when last updates were done and to trigger full updates',
			'sql' => [
				'ALTER TABLE indexing_profiles ADD COLUMN runFullUpdate TINYINT(1) DEFAULT 0',
				'ALTER TABLE indexing_profiles ADD COLUMN lastUpdateOfChangedRecords INT(11) DEFAULT 0',
				'ALTER TABLE indexing_profiles ADD COLUMN lastUpdateOfAllRecords INT(11) DEFAULT 0'
			]
		],

		'indexing_profile_last_marc_export' => [
			'title' => 'Indexing Profile Add Last Update MARC Export time',
			'description' => 'Add fields to track when last updates were done and to trigger full updates',
			'sql' => [
				'ALTER TABLE indexing_profiles ADD COLUMN lastUpdateFromMarcExport INT(11) DEFAULT 0',
			]
		],

		'indexing_profile_last_volume_export_timestamp' => [
			'title' => 'Indexing Profile Add Last Volume Export time',
			'description' => 'Add fields to track when the last volume export file was saved',
			'sql' => [
				'ALTER TABLE indexing_profiles ADD COLUMN lastVolumeExportTimestamp INT(11) DEFAULT 0',
			]
		],

		'authorities' => [
			'title' => 'Authority Tables',
			'description' => 'Setup authorities in the database to reduce memory usage during indexing',
			'sql' => [
				"CREATE TABLE author_authorities (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					originalName VARCHAR(255) NOT NULL,
					authoritativeName VARCHAR(255) NOT NULL
				) ENGINE = InnoDB",
				"ALTER TABLE author_authorities ADD UNIQUE INDEX (originalName)",
				"ALTER TABLE author_authorities ADD INDEX (authoritativeName)",
				"CREATE TABLE title_authorities (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					originalName VARCHAR(255) NOT NULL,
					authoritativeName VARCHAR(255) NOT NULL
				) ENGINE = InnoDB",
				"ALTER TABLE title_authorities ADD UNIQUE INDEX (originalName)",
				"ALTER TABLE title_authorities ADD INDEX (authoritativeName)",
			]
		],

		'accelerated_reader' => [
			'title' => 'Accelerated Reader',
			'description' => 'Setup Accelerated Reader in the database to reduce memory usage during indexing',
			'sql' => [
				"CREATE TABLE accelerated_reading_titles (
					arBookId INT NOT NULL PRIMARY KEY,
					language VARCHAR(2) NOT NULL,
					title VARCHAR(255) NOT NULL,
					authorCombined VARCHAR(255) NOT NULL,
					bookLevel FLOAT,
					arPoints INT(4),
					isFiction TINYINT(1),
					interestLevel VARCHAR(5)															
				) ENGINE = InnoDB",
				"CREATE TABLE accelerated_reading_isbn (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					arBookId INT NOT NULL,
					isbn VARCHAR(13) NOT NULL
				) ENGINE = InnoDB",
				"ALTER TABLE accelerated_reading_isbn ADD INDEX (arBookId)",
				"ALTER TABLE accelerated_reading_isbn ADD UNIQUE INDEX (isbn)",
				"CREATE TABLE accelerated_reading_subject (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					topic VARCHAR(255) NOT NULL,
					subTopic VARCHAR(255) NOT NULL
				) ENGINE = InnoDB",
				"ALTER TABLE accelerated_reading_subject ADD INDEX (topic, subTopic)",
				"CREATE TABLE accelerated_reading_subject_to_title (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					arBookId INT NOT NULL,
					arSubjectId INT NOT NULL
				) ENGINE = InnoDB",
				"ALTER TABLE accelerated_reading_subject_to_title ADD UNIQUE INDEX (arBookId, arSubjectId)",
				"CREATE TABLE accelerated_reading_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					indexSeries TINYINT(1) DEFAULT 1,
					indexSubjects TINYINT(1) DEFAULT 1,
					arExportPath VARCHAR(255) NOT NULL,
					ftpServer VARCHAR(255) NOT NULL,
					ftpUser VARCHAR(255) NOT NULL,
					ftpPassword VARCHAR(255) NOT NULL,
					lastFetched INT NOT NULL default 0
				) ENGINE = InnoDB",
			]
		],

		'format_status_maps' => [
			'title' => 'Format and Status Maps',
			'description' => 'Setup Format and Status Maps for Indexing Profiles',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS `format_map_values` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `indexingProfileId` int(11) NOT NULL,
				  `value` varchar(50) NOT NULL,
				  `format` varchar(255) NOT NULL,
				  `formatCategory` varchar(255) NOT NULL,
				  `formatBoost` tinyint NOT NULL,
				  PRIMARY KEY (`id`),
				  UNIQUE KEY (`indexingProfileId`,`value`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8",
				"CREATE TABLE IF NOT EXISTS `status_map_values` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `indexingProfileId` int(11) NOT NULL,
				  `value` varchar(50) NOT NULL,
				  `status` varchar(50) NOT NULL,
				  `groupedStatus` varchar(50) NOT NULL,
				  PRIMARY KEY (`id`),
				  UNIQUE KEY (`indexingProfileId`,`value`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8"
			],
		],

		'convert_to_format_status_maps' => [
			'title' => 'Convert to Format Status Maps',
			'description' => 'Convert translation maps to format and status maps',
			'sql' => [
				"createFormatAndStatusMapsFromTranslationMaps"
			],
		],

		'format_status_suppression' => [
			'title' => 'Format and status suppression',
			'description' => 'Add suppression to format and status suppression maps',
			'sql' => [
				"ALTER TABLE format_map_values ADD COLUMN suppress TINYINT(1) DEFAULT 0",
				"ALTER TABLE status_map_values ADD COLUMN suppress TINYINT(1) DEFAULT 0",
			],
		],

		'format_holdType' => [
			'title' => 'Set Hold Type by Format',
			'description' => 'Add information about the types of holds to allow for particular formats',
			'sql' => [
				"ALTER TABLE format_map_values ADD holdType ENUM('bib','item','either','none') DEFAULT 'bib'",
			],
		],

		'format_status_in_library_use_only' => [
			'title' => 'Format and status in library use only',
			'description' => 'Add in library use only to format and status suppression maps',
			'sql' => [
				"ALTER TABLE format_map_values ADD COLUMN inLibraryUseOnly TINYINT(1) DEFAULT 0",
				"ALTER TABLE status_map_values ADD COLUMN inLibraryUseOnly TINYINT(1) DEFAULT 0",
			],
		],

		'sideloads' => [
			'title' => 'Sideload setup',
			'description' => 'Setup sideloads table to store information about how to index eContent from MARC record uploads',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS `sideloads` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`name` varchar(50) NOT NULL,
					`marcPath` varchar(100) NOT NULL,
					`filenamesToInclude` varchar(250) DEFAULT '.*\\\\.ma?rc',
					`marcEncoding` enum('MARC8','UTF8','UNIMARC','ISO8859_1','BESTGUESS') NOT NULL DEFAULT 'MARC8',
					`individualMarcPath` varchar(100) NOT NULL,
					`numCharsToCreateFolderFrom` int(11) DEFAULT 4,
					`createFolderFromLeadingCharacters` tinyint(1) DEFAULT 0,
					`groupingClass` varchar(100) NOT NULL DEFAULT 'SideLoadedRecordGrouper',
					`indexingClass` varchar(50) NOT NULL DEFAULT 'SideLoadedEContentProcessor',
					`recordDriver` varchar(100) NOT NULL DEFAULT 'SideLoadedRecord',
					`recordUrlComponent` varchar(25) NOT NULL DEFAULT 'DefineThis',
					`recordNumberTag` char(3) NOT NULL DEFAULT '001',
					`recordNumberSubfield` char(1) DEFAULT 'a',
					`recordNumberPrefix` varchar(10) NOT NULL,
					`suppressItemlessBibs` TINYINT(1) NOT NULL DEFAULT '1',
					`itemTag` char(3) NOT NULL,
					`itemRecordNumber` char(1) DEFAULT NULL,
					`location` char(1) DEFAULT NULL,
					`locationsToSuppress` varchar(255),
					`itemUrl` char(1) DEFAULT NULL,
					`format` char(1) DEFAULT NULL,
					`formatSource` enum('bib','item', 'specified') NOT NULL DEFAULT 'bib',
					`specifiedFormat` varchar(50) DEFAULT NULL,
					`specifiedFormatCategory` varchar(50) DEFAULT NULL,
					`specifiedFormatBoost` int DEFAULT NULL,
					runFullUpdate TINYINT(1) DEFAULT 0,
					lastUpdateOfChangedRecords INT(11) DEFAULT 0,
					lastUpdateOfAllRecords INT(11) DEFAULT 0,
					PRIMARY KEY (`id`),
					UNIQUE KEY `name` (`name`)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8",
			]
		],

		'sideload_defaults' => [
			'title' => 'Default values for sideloads',
			'description' => 'Update default values for sideloads',
			'sql' => [
				"ALTER table sideloads CHANGE COLUMN recordNumberPrefix recordNumberPrefix varchar(10) NOT NULL DEFAULT '';",
				"ALTER table sideloads CHANGE COLUMN itemTag itemTag char(3) NOT NULL DEFAULT '';"
			]
		],

		'track_sideload_user_usage' => array(
			'title' => 'Sideload Usage by user',
			'description' => 'Add a table to track how often a particular user uses side loads.',
			'sql' => array(
				"CREATE TABLE user_sideload_usage (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					userId INT(11) NOT NULL,
					sideloadId INT(11) NOT NULL,
					year INT(4) NOT NULL,
					month INT(2) NOT NULL,
					usageCount INT(11) DEFAULT 0
				) ENGINE = InnoDB",
				"ALTER TABLE user_sideload_usage ADD INDEX (userId, sideloadId, year, month)",
				"ALTER TABLE user_sideload_usage ADD INDEX (year, month)",
			),
		),

		'track_sideload_record_usage' => array(
			'title' => 'Side Load Record Usage',
			'description' => 'Add a table to track how side loaded records are used.',
			'continueOnError' => true,
			'sql' => array(
				"CREATE TABLE sideload_record_usage (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					sideloadId INT(11) NOT NULL,
					recordId VARCHAR(36),
					year INT(4) NOT NULL,
					month INT(2) NOT NULL,
					timesUsed INT(11) NOT NULL
				) ENGINE = InnoDB",
				"ALTER TABLE sideload_record_usage ADD INDEX (sideloadId, year, month)",
				"ALTER TABLE sideload_record_usage ADD INDEX (year, month)",
			),
		),

		'sideload_usage_add_instance' => [
			'title' => 'Side Load Usage - Instance Information',
			'description' => 'Add Instance Information to Side Load Usage stats',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE sideload_record_usage ADD COLUMN instance VARCHAR(100)',
				'ALTER TABLE sideload_record_usage DROP INDEX sideloadId',
				'ALTER TABLE sideload_record_usage ADD UNIQUE INDEX (instance, sideloadId, recordId, year, month)',
				'ALTER TABLE user_sideload_usage ADD COLUMN instance VARCHAR(100)',
				'ALTER TABLE user_sideload_usage DROP INDEX userId',
				'ALTER TABLE user_sideload_usage ADD UNIQUE INDEX (instance, userId, sideLoadId, year, month)',
			]
		],

		'sideload_log' => array(
			'title' => 'Side Load log',
			'description' => 'Create log for Side Load Processing.',
			'sql' => array(
				"CREATE TABLE IF NOT EXISTS sideload_log(
					`id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of log', 
					`startTime` INT(11) NOT NULL COMMENT 'The timestamp when the run started', 
					`endTime` INT(11) NULL COMMENT 'The timestamp when the run ended', 
					`lastUpdate` INT(11) NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)', 
					`notes` TEXT COMMENT 'Additional information about the run', 
					numSideLoadsUpdated INT(11) DEFAULT 0,
					sideLoadsUpdated MEDIUMTEXT,
					numProducts INT(11) DEFAULT 0,
					numErrors INT(11) DEFAULT 0,
					numAdded INT(11) DEFAULT 0,
					numDeleted INT(11) DEFAULT 0,
					numUpdated INT(11) DEFAULT 0,
					numSkipped INT(11) DEFAULT 0,
					PRIMARY KEY ( `id` )
				) ENGINE = InnoDB;",
			)
		),

		'sideload_scoping' => [
			'title' => 'Side Load Scoping',
			'description' => 'Add a table to define what information should be included within search results',
			'sql' => [
				'CREATE TABLE sideload_scopes (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(50) NOT NULL,
					sideLoadId INT(11) NOT NULL,
					restrictToChildrensMaterial TINYINT DEFAULT 0
				) ENGINE = InnoDB'
			]
		],

		'sideload_scope_match_and_rewrite' => [
			'title' => 'Side Load Scopes Match Tags and URL Rewrites',
			'description' => 'Update scopes to allow limitation by MARC tag and perform URL rewriting',
			'sql' => [
				"ALTER TABLE sideload_scopes ADD COLUMN marcTagToMatch VARCHAR(100)",
				"ALTER TABLE sideload_scopes ADD COLUMN marcValueToMatch VARCHAR(100)",
				"ALTER TABLE sideload_scopes ADD COLUMN includeExcludeMatches TINYINT default 1",
				"ALTER TABLE sideload_scopes ADD COLUMN urlToMatch VARCHAR(100)",
				"ALTER TABLE sideload_scopes ADD COLUMN urlReplacement VARCHAR(100)"
			]
		],

		'sideload_files' => [
			'title' => 'Side Loads track indexed files',
			'description' => 'Add a table to track which files were have been indexed for a sideload',
			'sql' => [
				"CREATE TABLE sideload_files (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					sideLoadId INT(11) NOT NULL,
					filename VARCHAR(255),
					lastChanged INT(11) default 0,
					deletedTime INT(11) default 0,
					lastIndexed INT(11) default 0,
					UNIQUE sideloadFile(sideLoadId, filename)
				) ENGINE INNODB"
			]
		],

		'library_location_side_load_scoping' => [
			'title' => 'Library and Location Scoping of Side Loads',
			'description' => 'Add tables to determine how side loads are scoped',
			'sql' => [
				'CREATE TABLE library_sideload_scopes (
					id int(11) NOT NULL AUTO_INCREMENT,
					libraryId INT(11) NOT NULL,
					sideLoadScopeId INT(11) NOT NULL,
					PRIMARY KEY (`id`),
					UNIQUE (libraryId, sideLoadScopeId)
				) ENGINE = InnoDB',
				'CREATE TABLE IF NOT EXISTS location_sideload_scopes (
					id int(11) NOT NULL AUTO_INCREMENT,
					locationId int(11) NOT NULL,
					sideLoadScopeId int(11) NOT NULL,
					PRIMARY KEY (`id`),
					UNIQUE (locationId, sideLoadScopeId)
				) ENGINE=InnoDB',
			]
		],

		'create_ils_modules' => [
			'title' => 'Create base indexing',
			'description' => 'Setup Modules for ILS, Sideloads, and list indexing',
			'sql' => [
				"INSERT INTO modules (name, indexName, backgroundProcess) VALUES ('Koha', 'grouped_works', 'koha_export')",
				"INSERT INTO modules (name, indexName, backgroundProcess) VALUES ('CARL.X', 'grouped_works', 'carlx_export')",
				"INSERT INTO modules (name, indexName, backgroundProcess) VALUES ('Sierra', 'grouped_works', 'sierra_export')",
				"INSERT INTO modules (name, indexName, backgroundProcess) VALUES ('Horizon', 'grouped_works', 'horizon_export')",
				"INSERT INTO modules (name, indexName, backgroundProcess) VALUES ('Symphony', 'grouped_works', 'symphony_export')",
				"INSERT INTO modules (name, indexName, backgroundProcess) VALUES ('Side Loads', 'grouped_works', 'sideload_processing')",
				"INSERT INTO modules (name, indexName, backgroundProcess, enabled) VALUES ('User Lists', 'lists', 'user_list_indexer', 1)"
			]
		],

		'fix_sierra_module_background_process' => [
			'title' => 'Fix Sierra module background process',
			'description' => 'Fix Sierra module background process',
			'sql' => [
				"UPDATE modules set backgroundProcess = 'sierra_export_api' WHERE name = 'Sierra'"
			]
		],
		
		'scheduled_work_index' => [
			'title' => 'Scheduled Work Indexing',
			'description' => 'Create tables to store scheduled indexing for works',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS grouped_work_scheduled_index (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					permanent_id CHAR(36) NOT NULL,
					indexAfter int(11) NOT NULL,
					processed TINYINT(1) DEFAULT 0,
					INDEX allfields(processed, indexAfter, permanent_id),
					INDEX permanent_id(permanent_id)
				) ENGINE=InnoDB'
			]
		],

		'record_identifiers_to_reload' => [
			'title' => 'Record Identifiers To Reload',
			'description' => 'Setup record identifiers to reload to force regrouping',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS record_identifiers_to_reload (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					type VARCHAR(50) NOT NULL,
					identifier VARCHAR(50) NOT NULL,
					processed TINYINT(1) DEFAULT 0,
					INDEX (type, identifier),
					INDEX (processed, type)
				)'
			]
		],

		'unknown_language_handling' => [
			'title' => 'Unknown Language Handling',
			'description' => 'Add options to handle unknown and undetermined languages',
			'sql' => [
				"ALTER TABLE indexing_profiles ADD COLUMN treatUnknownLanguageAs VARCHAR(50) DEFAULT 'English'",
				"ALTER TABLE indexing_profiles ADD COLUMN treatUndeterminedLanguageAs VARCHAR(50) DEFAULT 'English'",
				"ALTER TABLE sideloads ADD COLUMN treatUnknownLanguageAs VARCHAR(50) DEFAULT 'English'",
				"ALTER TABLE sideloads ADD COLUMN treatUndeterminedLanguageAs VARCHAR(50) DEFAULT 'English'",
			]
		],

		'large_print_indexing' => [
			'title' => 'Large Print Indexing',
			'description' => 'Add an option for whether or not the record should be checked for large print subjects',
			'sql' => [
				'ALTER TABLE indexing_profiles ADD COLUMN checkRecordForLargePrint TINYINT(1) DEFAULT 0'
			]
		],

		'indexing_module_add_log' =>[
			'title' => 'Indexing add log info to module',
			'description' => 'Add logging information to Indexing modules',
			'sql' => [
				"UPDATE modules set logClassPath='/sys/ILS/IlsExtractLogEntry.php', logClassName='IlsExtractLogEntry' WHERE name in ('Koha', 'CARL.X', 'Sierra', 'Horizon', 'Symphony')",
				"UPDATE modules set logClassPath='/sys/Indexing/SideLoadLogEntry.php', logClassName='SideLoadLogEntry' WHERE name = 'Side Loads'",
			]
		],

		'indexing_module_add_settings' => [
			'title' => 'Add Settings to Indexing module',
			'description' => 'Add Settings to Indexing module',
			'sql' => [
				"UPDATE modules set settingsClassName = '/sys/Indexing/IndexingProfile.php' WHERE name in ('Koha', 'CARL.X', 'Sierra', 'Horizon', 'Symphony')"
			]
		],

		'indexing_profile_determineAudienceBy' => [
			'title' => 'Indexing Profile - determineAudienceBy',
			'description' => 'Add the ability to control how audience is determined to Indexing Profiles',
			'sql' => [
				'ALTER TABLE indexing_profiles ADD COLUMN determineAudienceBy TINYINT DEFAULT 0'
			]
		],

		'indexing_profile_audienceSubfield' => [
			'title' => 'Indexing Profile - audienceSubfield',
			'description' => 'Add the ability to control which field is used to determine audience',
			'sql' => [
				'ALTER TABLE indexing_profiles ADD COLUMN audienceSubfield char(1) DEFAULT null'
			]
		],

		'indexing_exclude_locations' => [
			'title' => 'Indexing - Exclude Records by Location and Sublocation',
			'description' => 'Add the ability to exclude records from a library/location by location or sublocation',
			'sql' => [
				"ALTER TABLE library_records_owned ADD COLUMN locationsToExclude varchar(100) NOT NULL DEFAULT ''",
				"ALTER TABLE library_records_owned ADD COLUMN subLocationsToExclude varchar(100) NOT NULL DEFAULT ''",
				"ALTER TABLE library_records_to_include ADD COLUMN locationsToExclude varchar(100) NOT NULL DEFAULT ''",
				"ALTER TABLE library_records_to_include ADD COLUMN subLocationsToExclude varchar(100) NOT NULL DEFAULT ''",
				"ALTER TABLE location_records_owned ADD COLUMN locationsToExclude varchar(100) NOT NULL DEFAULT ''",
				"ALTER TABLE location_records_owned ADD COLUMN subLocationsToExclude varchar(100) NOT NULL DEFAULT ''",
				"ALTER TABLE location_records_to_include ADD COLUMN locationsToExclude varchar(100) NOT NULL DEFAULT ''",
				"ALTER TABLE location_records_to_include ADD COLUMN subLocationsToExclude varchar(100) NOT NULL DEFAULT ''",
			]
		],

		'indexing_includeLocationNameInDetailedLocation' => [
			'title' => 'Indexing - includeLocationNameInDetailedLocation',
			'description' => 'Add a new field to determine how detailed location is built',
			'sql' => [
				"ALTER TABLE indexing_profiles ADD COLUMN includeLocationNameInDetailedLocation TINYINT(1) DEFAULT 1"
			]
		],

		'indexing_lastUpdateOfAuthorities' => [
			'title' => 'Indexing add lastUpdateOfAuthorities',
			'description' => 'Add lastUpdateOfAuthorities to Indexing Profiles to optimize loading authorities from the ILS',
			'sql' => [
				'ALTER TABLE indexing_profiles ADD COLUMN lastUpdateOfAuthorities INT(11) DEFAULT 0'
			]
		]
	);
}

/** @noinspection PhpUnused */
function createFormatAndStatusMapsFromTranslationMaps(&$update)
{
	require_once ROOT_DIR . '/sys/Indexing/IndexingProfile.php';
	require_once ROOT_DIR . '/sys/Indexing/TranslationMap.php';
	require_once ROOT_DIR . '/sys/Indexing/TranslationMapValue.php';
	require_once ROOT_DIR . '/sys/Indexing/StatusMapValue.php';
	require_once ROOT_DIR . '/sys/Indexing/FormatMapValue.php';

	$indexingProfile = new IndexingProfile();
	$indexingProfile->find();
	while ($indexingProfile->fetch()) {
		$translationMaps = $indexingProfile->translationMaps;
		$statusMap = null;
		$groupedStatusMap = null;
		$formatMap = null;
		$formatBoostMap = null;
		$formatCategoryMap = null;
		/** @var TranslationMap $translationMap */
		foreach ($translationMaps as $translationMap) {
			if ($translationMap->name == 'item_status') {
				$statusMap = $translationMap;
			} elseif ($translationMap->name == 'item_grouped_status') {
				$groupedStatusMap = $translationMap;
			} elseif ($translationMap->name == 'format') {
				$formatMap = $translationMap;
			} elseif ($translationMap->name == 'format_boost') {
				$formatBoostMap = $translationMap;
			} elseif ($translationMap->name == 'format_category') {
				$formatCategoryMap = $translationMap;
			}
		}

		if ($statusMap != null) {
			/** @var TranslationMapValue $value */
			foreach ($statusMap->translationMapValues as $value) {
				$indexingProfile->setStatusMapValue($value->value, $value->translation, null);
				$value->delete();
			}
			$statusMap->delete();
		}

		if ($groupedStatusMap != null) {
			/** @var TranslationMapValue $value */
			foreach ($groupedStatusMap->translationMapValues as $value) {
				$indexingProfile->setStatusMapValue($value->value, null, $value->translation);
				$value->delete();
			}
			$groupedStatusMap->delete();
		}

		if ($formatMap != null) {
			/** @var TranslationMapValue $value */
			foreach ($formatMap->translationMapValues as $value) {
				$indexingProfile->setFormatMapValue($value->value, $value->translation, null, null);
				$value->delete();
			}
			$formatMap->delete();
		}

		if ($formatCategoryMap != null) {
			/** @var TranslationMapValue $value */
			foreach ($formatCategoryMap->translationMapValues as $value) {
				$indexingProfile->setFormatMapValue($value->value, null, $value->translation, null);
				$value->delete();
			}
			$formatCategoryMap->delete();
		}

		if ($formatBoostMap != null) {
			/** @var TranslationMapValue $value */
			foreach ($formatBoostMap->translationMapValues as $value) {
				$indexingProfile->setFormatMapValue($value->value, null, null, $value->translation);
				$value->delete();
			}
			$formatBoostMap->delete();
		}
	}
}
