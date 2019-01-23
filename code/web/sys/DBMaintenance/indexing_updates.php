<?php
/**
 * Updates related to indexing for cleanliness
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/29/14
 * Time: 2:25 PM
 */

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
						) ENGINE = INNODB"
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

		'indexing_profile_speicified_formats' => array(
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

		'translation_map_regex' => array(
			'title' => 'Translation Maps Regex',
			'description' => 'Setup Translation Maps to use regular expressions',
			'sql' => array(
				"ALTER TABLE translation_maps ADD COLUMN `usesRegularExpressions` tinyint(1) DEFAULT 0",
			)
		),

		'setup_default_indexing_profiles' => array(
			'title' => 'Setup Default Indexing Profiles',
			'description' => 'Setup indexing profiles based off historic information',
			'sql' => array(
				'setupIndexingProfiles'
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

	);
}

function setupIndexingProfiles($update){
	global $configArray;
	$profileExists = false;
	//Create a default indexing profile
	$ilsIndexingProfile = new IndexingProfile();
	$ilsIndexingProfile->name = 'ils';
	if ($ilsIndexingProfile->find(true)){
		$profileExists = true;
	}
	$ilsIndexingProfile->marcPath = $configArray['Reindex']['marcPath'];
	$ilsIndexingProfile->marcEncoding = $configArray['Reindex']['marcEncoding'];
	$ilsIndexingProfile->individualMarcPath = $configArray['Reindex']['individualMarcPath'];
	$ilsIndexingProfile->groupingClass = 'MarcRecordGrouper';
	$ilsIndexingProfile->indexingClass = 'IlsRecordProcessor';
	$ilsIndexingProfile->catalogDriver = $configArray['Catalog']['driver'];
	$ilsIndexingProfile->recordDriver = 'MarcRecord';
	$ilsIndexingProfile->recordUrlComponent = 'Record';
	$ilsIndexingProfile->formatSource = $configArray['Reindex']['useItemBasedCallNumbers'] == true ? 'item' : 'bib';
	$ilsIndexingProfile->recordNumberTag = $configArray['Reindex']['recordNumberTag'];
	$ilsIndexingProfile->recordNumberPrefix = $configArray['Reindex']['recordNumberPrefix'];
	$ilsIndexingProfile->suppressItemlessBibs = $configArray['Reindex']['suppressItemlessBibs'] == true ? 1 : 0;
	$ilsIndexingProfile->itemTag = $configArray['Reindex']['itemTag'];
	$ilsIndexingProfile->itemRecordNumber = $configArray['Reindex']['itemRecordNumberSubfield'];
	$ilsIndexingProfile->useItemBasedCallNumbers = $configArray['Reindex']['useItemBasedCallNumbers'] == true ? 1 : 0;
	$ilsIndexingProfile->callNumberPrestamp = $configArray['Reindex']['callNumberPrestampSubfield'];
	$ilsIndexingProfile->callNumber = $configArray['Reindex']['callNumberSubfield'];
	$ilsIndexingProfile->callNumberCutter = $configArray['Reindex']['callNumberCutterSubfield'];
	$ilsIndexingProfile->callNumberPoststamp = $configArray['Reindex']['callNumberPoststampSubfield'];
	$ilsIndexingProfile->location = $configArray['Reindex']['locationSubfield'];
	$ilsIndexingProfile->locationsToSuppress = isset($configArray['Reindex']['locationsToSuppress']) ? $configArray['Reindex']['locationsToSuppress'] : '';
	$ilsIndexingProfile->subLocation = '';
	$ilsIndexingProfile->shelvingLocation = $configArray['Reindex']['locationSubfield'];
	$ilsIndexingProfile->collection = $configArray['Reindex']['collectionSubfield'];
	$ilsIndexingProfile->volume = $configArray['Reindex']['volumeSubfield'];
	$ilsIndexingProfile->itemUrl = $configArray['Reindex']['itemUrlSubfield'];
	$ilsIndexingProfile->barcode = $configArray['Reindex']['barcodeSubfield'];
	$ilsIndexingProfile->status = $configArray['Reindex']['statusSubfield'];
	$ilsIndexingProfile->statusesToSuppress = '';
	$ilsIndexingProfile->totalCheckouts = $configArray['Reindex']['totalCheckoutSubfield'];
	$ilsIndexingProfile->lastYearCheckouts = $configArray['Reindex']['lastYearCheckoutSubfield'];
	$ilsIndexingProfile->yearToDateCheckouts = $configArray['Reindex']['ytdCheckoutSubfield'];
	$ilsIndexingProfile->totalRenewals = $configArray['Reindex']['totalRenewalSubfield'];
	$ilsIndexingProfile->iType = $configArray['Reindex']['iTypeSubfield'];
	$ilsIndexingProfile->dueDate = $configArray['Reindex']['dueDateSubfield'];
	$ilsIndexingProfile->dateCreated = $configArray['Reindex']['dateCreatedSubfield'];
	$ilsIndexingProfile->dateCreatedFormat = $configArray['Reindex']['dateAddedFormat'];
	$ilsIndexingProfile->iCode2 = $configArray['Reindex']['iCode2Subfield'];
	$ilsIndexingProfile->useICode2Suppression = $configArray['Reindex']['useICode2Suppression'];
	$ilsIndexingProfile->format = isset($configArray['Reindex']['formatSubfield']) ? $configArray['Reindex']['formatSubfield'] : '';
	$ilsIndexingProfile->eContentDescriptor = $configArray['Reindex']['eContentSubfield'];
	$ilsIndexingProfile->orderTag = isset($configArray['Reindex']['orderTag']) ? $configArray['Reindex']['orderTag'] : '';
	$ilsIndexingProfile->orderStatus = isset($configArray['Reindex']['orderStatusSubfield']) ? $configArray['Reindex']['orderStatusSubfield'] : '';
	$ilsIndexingProfile->orderLocation = isset($configArray['Reindex']['orderLocationsSubfield']) ? $configArray['Reindex']['orderLocationsSubfield'] : '';
	$ilsIndexingProfile->orderCopies = isset($configArray['Reindex']['orderCopiesSubfield']) ? $configArray['Reindex']['orderCopiesSubfield'] : '';
	$ilsIndexingProfile->orderCode3 = isset($configArray['Reindex']['orderCode3Subfield']) ? $configArray['Reindex']['orderCode3Subfield'] : '';

	if ($profileExists){
		$ilsIndexingProfile->update();
	}else {
		$ilsIndexingProfile->insert();
	}

	//Create a profile for hoopla
	$profileExists = false;
	$hooplaIndexingProfile = new IndexingProfile();
	$hooplaIndexingProfile->name = 'hoopla';
	if ($hooplaIndexingProfile->find(true)){
		$profileExists = true;
	}
	$hooplaIndexingProfile->marcPath = $configArray['Hoopla']['marcPath'];
	$hooplaIndexingProfile->marcEncoding = $configArray['Hoopla']['marcEncoding'];
	$hooplaIndexingProfile->individualMarcPath = $configArray['Hoopla']['individualMarcPath'];
	$hooplaIndexingProfile->groupingClass = 'HooplaRecordGrouper';
	$hooplaIndexingProfile->indexingClass = 'Hoopla';
	$hooplaIndexingProfile->recordDriver = 'HooplaDriver';
	$hooplaIndexingProfile->recordUrlComponent = 'Hoopla';
	$hooplaIndexingProfile->formatSource = 'bib';
	$hooplaIndexingProfile->recordNumberTag = '001';
	$hooplaIndexingProfile->recordNumberPrefix = '';
	$hooplaIndexingProfile->itemTag = '';
	if ($profileExists){
		$hooplaIndexingProfile->update();
	}else {
		$hooplaIndexingProfile->insert();
	}

	//Setup ownership rules and inclusion rules for libraries
	$allLibraries = new Library();
	$allLibraries->find();
	while ($allLibraries->fetch()){
		$ownershipRule = new LibraryRecordOwned();
		$ownershipRule->indexingProfileId  = $ilsIndexingProfile->id;
		$ownershipRule->libraryId = $allLibraries->libraryId;
		$ownershipRule->location = $allLibraries->ilsCode;
		$ownershipRule->subLocation = '';
		$ownershipRule->insert();

		//Other print titles
		if (!$allLibraries->restrictSearchByLibrary){
			$inclusionRule = new LibraryRecordToInclude();
			$inclusionRule->indexingProfileId = $ilsIndexingProfile->id;
			$inclusionRule->libraryId = $allLibraries->libraryId;
			$inclusionRule->location = ".*";
			$inclusionRule->subLocation = '';
			$inclusionRule->includeHoldableOnly = 1;
			$inclusionRule->includeEContent = 0;
			$inclusionRule->includeItemsOnOrder = 0;
			$inclusionRule->weight = 1;
			$inclusionRule->insert();
		}

		//eContent titles
		if ($allLibraries->econtentLocationsToInclude){
			$inclusionRule = new LibraryRecordToInclude();
			$inclusionRule->indexingProfileId = $ilsIndexingProfile->id;
			$inclusionRule->libraryId = $allLibraries->libraryId;
			$inclusionRule->location = str_replace(',', '|', $allLibraries->econtentLocationsToInclude);
			$inclusionRule->subLocation = '';
			$inclusionRule->includeHoldableOnly = 0;
			$inclusionRule->includeEContent = 1;
			$inclusionRule->includeItemsOnOrder = 0;
			$inclusionRule->weight = 1;
			$inclusionRule->insert();
		}

		//Hoopla titles
		/*if ($allLibraries->includeHoopla){
			$inclusionRule = new LibraryRecordToInclude();
			$inclusionRule->indexingProfileId = $hooplaIndexingProfile->id;
			$inclusionRule->libraryId = $allLibraries->libraryId;
			$inclusionRule->location = '.*';
			$inclusionRule->subLocation = '';
			$inclusionRule->includeHoldableOnly = 0;
			$inclusionRule->includeEContent = 1;
			$inclusionRule->includeItemsOnOrder = 0;
			$inclusionRule->weight = 1;
			$inclusionRule->insert();
		}*/
	}

	//Setup ownership rules and inclusion rules for locations
	$allLocations = new Location();
	$allLocations->find();
	while ($allLocations->fetch()){
		$ownershipRule = new LocationRecordOwned();
		$ownershipRule->indexingProfileId  = $ilsIndexingProfile->id;
		$ownershipRule->locationId = $allLocations->locationId;
		$ownershipRule->location = $allLocations->code;
		$ownershipRule->subLocation = '';
		$ownershipRule->insert();

		//Other print titles
		if ($allLocations->restrictSearchByLocation){
			$inclusionRule = new LocationRecordToInclude();
			$inclusionRule->indexingProfileId = $ilsIndexingProfile->id;
			$inclusionRule->locationId = $allLocations->locationId;
			$inclusionRule->location = ".*";
			$inclusionRule->subLocation = '';
			$inclusionRule->includeHoldableOnly = 1;
			$inclusionRule->includeEContent = 0;
			$inclusionRule->includeItemsOnOrder = 0;
			$inclusionRule->weight = 1;
			$inclusionRule->insert();
		}

		//eContent titles
		if ($allLocations->econtentLocationsToInclude){
			$inclusionRule = new LocationRecordToInclude();
			$inclusionRule->indexingProfileId = $ilsIndexingProfile->id;
			$inclusionRule->locationId = $allLocations->locationId;
			$inclusionRule->location = str_replace(',', '|', $allLibraries->econtentLocationsToInclude);
			$inclusionRule->subLocation = '';
			$inclusionRule->includeHoldableOnly = 0;
			$inclusionRule->includeEContent = 1;
			$inclusionRule->includeItemsOnOrder = 0;
			$inclusionRule->weight = 1;
			$inclusionRule->insert();
		}

		//Hoopla titles
		$relatedLibrary = new Library();
		$relatedLibrary->libraryId = $allLocations->libraryId;
		if ($relatedLibrary->find(true) && $relatedLibrary->includeHoopla){
			$inclusionRule = new LocationRecordToInclude();
			$inclusionRule->indexingProfileId = $hooplaIndexingProfile->id;
			$inclusionRule->locationId = $allLocations->locationId;
			$inclusionRule->location = '.*';
			$inclusionRule->subLocation = '';
			$inclusionRule->includeHoldableOnly = 0;
			$inclusionRule->includeEContent = 1;
			$inclusionRule->includeItemsOnOrder = 0;
			$inclusionRule->weight = 1;
			$inclusionRule->insert();
		}
	}

	//Setup translation maps?


}