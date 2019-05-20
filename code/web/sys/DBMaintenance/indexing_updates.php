<?php

function getIndexingUpdates() {
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
                  `marcEncoding` enum('MARC8','UTF','UNIMARC','ISO8859_1','BESTGUESS') NOT NULL DEFAULT 'MARC8',
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

        'indexing_profile_add_continuous_update_fields' => [
            'title' => 'Indexing Profile Add Continuous Update Fields',
            'description' => 'Add fields to track when last updates were done and to trigger full updates',
            'sql' => [
                'ALTER TABLE indexing_profiles ADD COLUMN runFullUpdate TINYINT(1) DEFAULT 0',
                'ALTER TABLE indexing_profiles ADD COLUMN lastUpdateOfChangedRecords INT(11) DEFAULT 0',
                'ALTER TABLE indexing_profiles ADD COLUMN lastUpdateOfAllRecords INT(11) DEFAULT 0'
            ]
        ],
	);
}
