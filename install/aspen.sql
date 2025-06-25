DROP TABLE IF EXISTS accelerated_reading_isbn;
CREATE TABLE `accelerated_reading_isbn` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `arBookId` int(11) NOT NULL,
  `isbn` varchar(13) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `isbn` (`isbn`),
  KEY `arBookId` (`arBookId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS accelerated_reading_settings;
CREATE TABLE `accelerated_reading_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `indexSeries` tinyint(1) DEFAULT 1,
  `indexSubjects` tinyint(1) DEFAULT 1,
  `arExportPath` varchar(255) NOT NULL,
  `ftpServer` varchar(255) NOT NULL,
  `ftpUser` varchar(255) NOT NULL,
  `ftpPassword` varchar(255) NOT NULL,
  `lastFetched` int(11) NOT NULL DEFAULT 0,
  `updateOn` tinyint(4) DEFAULT 0,
  `updateFrequency` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS accelerated_reading_subject;
CREATE TABLE `accelerated_reading_subject` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `topic` varchar(255) NOT NULL,
  `subTopic` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `topic` (`topic`,`subTopic`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS accelerated_reading_subject_to_title;
CREATE TABLE `accelerated_reading_subject_to_title` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `arBookId` int(11) NOT NULL,
  `arSubjectId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `arBookId` (`arBookId`,`arSubjectId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS accelerated_reading_titles;
CREATE TABLE `accelerated_reading_titles` (
  `arBookId` int(11) NOT NULL,
  `language` varchar(2) NOT NULL,
  `title` varchar(255) NOT NULL,
  `authorCombined` varchar(255) NOT NULL,
  `bookLevel` float DEFAULT NULL,
  `arPoints` int(11) DEFAULT NULL,
  `isFiction` tinyint(1) DEFAULT NULL,
  `interestLevel` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`arBookId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS account_profiles;
CREATE TABLE `account_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT 'ils',
  `driver` varchar(50) NOT NULL,
  `loginConfiguration` enum('barcode_pin','name_barcode','barcode_lastname') NOT NULL,
  `authenticationMethod` enum('ils','sip2','db','ldap','sso') DEFAULT NULL,
  `vendorOpacUrl` varchar(100) NOT NULL,
  `patronApiUrl` varchar(100) NOT NULL,
  `recordSource` varchar(50) NOT NULL,
  `weight` int(11) NOT NULL,
  `databaseHost` varchar(100) DEFAULT NULL,
  `databaseName` varchar(75) DEFAULT NULL,
  `databaseUser` varchar(50) DEFAULT NULL,
  `databasePassword` varchar(50) DEFAULT NULL,
  `sipHost` varchar(100) DEFAULT NULL,
  `sipPort` varchar(50) DEFAULT NULL,
  `sipUser` varchar(50) DEFAULT NULL,
  `sipPassword` varchar(50) DEFAULT NULL,
  `databasePort` varchar(5) DEFAULT NULL,
  `databaseTimezone` varchar(50) DEFAULT NULL,
  `oAuthClientId` varchar(36) DEFAULT NULL,
  `oAuthClientSecret` varchar(50) DEFAULT NULL,
  `ils` varchar(20) DEFAULT 'koha',
  `apiVersion` varchar(10) DEFAULT '',
  `staffUsername` varchar(100) DEFAULT NULL,
  `staffPassword` varchar(50) DEFAULT NULL,
  `workstationId` varchar(10) DEFAULT '',
  `domain` varchar(100) DEFAULT '',
  `libraryForRecordingPayments` tinyint(4) DEFAULT 1,
  `ssoSettingId` tinyint(4) DEFAULT -1,
  `iiiLoginConfiguration` enum('','barcode_pin','name_barcode','name_barcode_pin') NOT NULL DEFAULT '',
  `overrideCode` varchar(50) DEFAULT '',
  `carlXViewVersion` enum('','v','v2') NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS aci_speedpay_settings;
CREATE TABLE `aci_speedpay_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `sandboxMode` tinyint(1) DEFAULT 0,
  `clientId` varchar(100) DEFAULT NULL,
  `clientSecret` varchar(100) DEFAULT NULL,
  `apiAuthKey` varchar(100) DEFAULT NULL,
  `billerId` varchar(100) DEFAULT NULL,
  `billerAccountId` varchar(100) DEFAULT NULL,
  `sdkClientId` varchar(100) DEFAULT NULL,
  `sdkClientSecret` varchar(100) DEFAULT NULL,
  `sdkApiAuthKey` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS administration_field_lock;
CREATE TABLE `administration_field_lock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module` varchar(30) NOT NULL,
  `toolName` varchar(100) NOT NULL,
  `field` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
DROP TABLE IF EXISTS amazon_ses_settings;
CREATE TABLE `amazon_ses_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fromAddress` varchar(255) DEFAULT NULL,
  `replyToAddress` varchar(255) DEFAULT NULL,
  `accessKeyId` varchar(50) DEFAULT NULL,
  `accessKeySecret` varchar(600) DEFAULT NULL,
  `singleMailConfigSet` varchar(50) DEFAULT NULL,
  `bulkMailConfigSet` varchar(50) DEFAULT NULL,
  `region` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS api_usage;
CREATE TABLE `api_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `instance` varchar(100) NOT NULL,
  `module` varchar(30) NOT NULL,
  `method` varchar(75) NOT NULL,
  `numCalls` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniqueness` (`year`,`month`,`instance`,`module`,`method`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS archive_requests;
CREATE TABLE `archive_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `address` varchar(200) DEFAULT NULL,
  `address2` varchar(200) DEFAULT NULL,
  `city` varchar(200) DEFAULT NULL,
  `state` varchar(200) DEFAULT NULL,
  `zip` varchar(12) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `alternatePhone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `format` longtext DEFAULT NULL,
  `purpose` longtext DEFAULT NULL,
  `pid` varchar(50) DEFAULT NULL,
  `dateRequested` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS aspen_lida_branded_settings;
CREATE TABLE `aspen_lida_branded_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slugName` varchar(50) DEFAULT NULL,
  `logoLogin` varchar(100) DEFAULT NULL,
  `privacyPolicy` varchar(255) DEFAULT NULL,
  `logoSplash` varchar(100) DEFAULT NULL,
  `logoAppIcon` varchar(100) DEFAULT NULL,
  `showFavicons` int(11) DEFAULT 0,
  `logoNotification` varchar(100) DEFAULT NULL,
  `appName` varchar(100) DEFAULT NULL,
  `privacyPolicyContactAddress` longtext DEFAULT NULL,
  `privacyPolicyContactPhone` varchar(25) DEFAULT NULL,
  `privacyPolicyContactEmail` varchar(250) DEFAULT NULL,
  `autoPickUserHomeLocation` tinyint(4) NOT NULL DEFAULT 1,
  `apiKey1` varchar(256) DEFAULT NULL,
  `apiKey2` varchar(256) DEFAULT NULL,
  `apiKey3` varchar(256) DEFAULT NULL,
  `apiKey4` varchar(256) DEFAULT NULL,
  `apiKey5` varchar(256) DEFAULT NULL,
  `logoAppIconAndroid` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slugName` (`slugName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS aspen_lida_build;
CREATE TABLE `aspen_lida_build` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `buildId` varchar(72) NOT NULL,
  `status` varchar(11) NOT NULL,
  `appId` varchar(72) NOT NULL,
  `name` varchar(72) NOT NULL,
  `version` varchar(72) NOT NULL,
  `buildVersion` varchar(72) NOT NULL,
  `channel` varchar(72) NOT NULL DEFAULT 'default',
  `updateId` varchar(72) NOT NULL DEFAULT '0',
  `patch` varchar(5) DEFAULT '0',
  `updateCreated` varchar(255) DEFAULT NULL,
  `gitCommitHash` varchar(72) DEFAULT NULL,
  `buildMessage` varchar(72) DEFAULT NULL,
  `error` tinyint(1) DEFAULT 0,
  `errorMessage` varchar(255) DEFAULT NULL,
  `createdAt` varchar(255) DEFAULT NULL,
  `completedAt` varchar(255) DEFAULT NULL,
  `updatedAt` varchar(255) DEFAULT NULL,
  `isSupported` tinyint(1) DEFAULT 1,
  `isEASUpdate` tinyint(1) DEFAULT 0,
  `platform` varchar(25) NOT NULL,
  `artifact` varchar(255) DEFAULT NULL,
  `isSubmitted` tinyint(1) DEFAULT 0,
  `storeUrl` varchar(255) DEFAULT NULL,
  `storeIdentifier` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `buildId` (`buildId`,`updateId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS aspen_lida_general_settings;
CREATE TABLE `aspen_lida_general_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `autoRotateCard` tinyint(1) DEFAULT 0,
  `enableSelfRegistration` tinyint(4) DEFAULT 0,
  `showMoreInfoBtn` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS aspen_lida_location_settings;
CREATE TABLE `aspen_lida_location_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `enableAccess` tinyint(1) DEFAULT 0,
  `releaseChannel` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS aspen_lida_notification_setting;
CREATE TABLE `aspen_lida_notification_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `sendTo` tinyint(1) DEFAULT 0,
  `notifySavedSearch` tinyint(1) DEFAULT 0,
  `notifyCustom` tinyint(1) DEFAULT 0,
  `notifyAccount` tinyint(1) DEFAULT 0,
  `ilsNotificationSettingId` int(11) DEFAULT -1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS aspen_lida_notifications;
CREATE TABLE `aspen_lida_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(75) NOT NULL,
  `message` varchar(255) NOT NULL,
  `sendOn` int(11) DEFAULT NULL,
  `expiresOn` int(11) DEFAULT NULL,
  `ctaUrl` varchar(500) DEFAULT NULL,
  `ctaLabel` varchar(25) DEFAULT NULL,
  `sent` int(11) DEFAULT 0,
  `linkType` tinyint(1) DEFAULT 0,
  `deepLinkPath` varchar(75) DEFAULT NULL,
  `deepLinkId` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS aspen_lida_notifications_library;
CREATE TABLE `aspen_lida_notifications_library` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lidaNotificationId` int(11) DEFAULT NULL,
  `libraryId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS aspen_lida_notifications_location;
CREATE TABLE `aspen_lida_notifications_location` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lidaNotificationId` int(11) DEFAULT NULL,
  `locationId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS aspen_lida_notifications_ptype;
CREATE TABLE `aspen_lida_notifications_ptype` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lidaNotificationId` int(11) DEFAULT NULL,
  `patronTypeId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS aspen_lida_quick_search_setting;
CREATE TABLE `aspen_lida_quick_search_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS aspen_lida_quick_searches;
CREATE TABLE `aspen_lida_quick_searches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) DEFAULT -1,
  `weight` int(11) NOT NULL DEFAULT 0,
  `searchTerm` varchar(500) NOT NULL,
  `label` varchar(50) NOT NULL,
  `quickSearchSettingId` int(11) DEFAULT -1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS aspen_lida_self_check_barcode;
CREATE TABLE `aspen_lida_self_check_barcode` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `selfCheckSettingsId` int(11) NOT NULL DEFAULT -1,
  `barcodeStyle` varchar(75) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
DROP TABLE IF EXISTS aspen_lida_self_check_settings;
CREATE TABLE `aspen_lida_self_check_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `isEnabled` tinyint(1) DEFAULT 0,
  `checkoutLocation` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
DROP TABLE IF EXISTS aspen_release;
CREATE TABLE `aspen_release` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(10) NOT NULL,
  `releaseDate` date DEFAULT NULL,
  `releaseDateTest` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS aspen_site_checks;
CREATE TABLE `aspen_site_checks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `siteId` int(11) NOT NULL,
  `checkName` varchar(50) DEFAULT NULL,
  `currentStatus` tinyint(1) DEFAULT NULL,
  `currentNote` varchar(500) DEFAULT NULL,
  `lastOkTime` int(11) DEFAULT NULL,
  `lastWarningTime` int(11) DEFAULT NULL,
  `lastErrorTime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `siteId` (`siteId`,`checkName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS aspen_site_cpu_usage;
CREATE TABLE `aspen_site_cpu_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aspenSiteId` int(11) NOT NULL,
  `loadPerCpu` float NOT NULL,
  `timestamp` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `aspenSiteId` (`aspenSiteId`,`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS aspen_site_memory_usage;
CREATE TABLE `aspen_site_memory_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aspenSiteId` int(11) NOT NULL,
  `percentMemoryUsage` float NOT NULL,
  `totalMemory` float NOT NULL,
  `availableMemory` float NOT NULL,
  `timestamp` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `aspenSiteId` (`aspenSiteId`,`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS aspen_site_scheduled_update;
CREATE TABLE `aspen_site_scheduled_update` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dateScheduled` int(11) DEFAULT 0,
  `updateToVersion` varchar(32) DEFAULT NULL,
  `updateType` varchar(10) DEFAULT NULL,
  `dateRun` int(11) DEFAULT 0,
  `status` varchar(10) DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `siteId` int(11) DEFAULT NULL,
  `greenhouseId` int(11) DEFAULT NULL,
  `remoteUpdate` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS aspen_site_stats;
CREATE TABLE `aspen_site_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aspenSiteId` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `day` int(11) NOT NULL,
  `minDataDiskSpace` float DEFAULT NULL,
  `minUsrDiskSpace` float DEFAULT NULL,
  `minAvailableMemory` float DEFAULT NULL,
  `maxAvailableMemory` float DEFAULT NULL,
  `minLoadPerCPU` float DEFAULT NULL,
  `maxLoadPerCPU` float DEFAULT NULL,
  `maxWaitTime` float DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `siteId` (`aspenSiteId`,`year`,`month`,`day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS aspen_site_wait_time;
CREATE TABLE `aspen_site_wait_time` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aspenSiteId` int(11) NOT NULL,
  `waitTime` float NOT NULL,
  `timestamp` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `aspenSiteId` (`aspenSiteId`,`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS aspen_sites;
CREATE TABLE `aspen_sites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `baseUrl` varchar(255) DEFAULT NULL,
  `siteType` int(11) DEFAULT 0,
  `libraryType` int(11) DEFAULT 0,
  `libraryServes` int(11) DEFAULT 0,
  `implementationStatus` int(11) DEFAULT 0,
  `hosting` varchar(75) DEFAULT NULL,
  `operatingSystem` varchar(75) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `internalServerName` varchar(50) DEFAULT NULL,
  `appAccess` tinyint(1) DEFAULT 0,
  `version` varchar(25) DEFAULT NULL,
  `sendErrorNotificationsTo` varchar(250) DEFAULT NULL,
  `lastNotificationTime` int(11) DEFAULT NULL,
  `ils` int(11) DEFAULT NULL,
  `contractSigningDate` date DEFAULT NULL,
  `goLiveDate` date DEFAULT NULL,
  `contactFrequency` tinyint(4) DEFAULT 3,
  `lastContacted` date DEFAULT NULL,
  `nextMeetingDate` date DEFAULT NULL,
  `nextMeetingPerson` varchar(50) DEFAULT NULL,
  `activeTicketFeed` varchar(1000) DEFAULT '',
  `timezone` tinyint(1) DEFAULT 0,
  `lastOfflineTime` int(11) DEFAULT NULL,
  `lastOfflineNote` varchar(255) DEFAULT NULL,
  `lastOnlineTime` int(11) DEFAULT NULL,
  `isOnline` tinyint(1) DEFAULT 1,
  `monitored` tinyint(1) DEFAULT 1,
  `optOutBatchUpdates` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `baseUrl` (`baseUrl`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS aspen_usage;
CREATE TABLE `aspen_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `pageViews` int(11) DEFAULT 0,
  `pageViewsByBots` int(11) DEFAULT 0,
  `pageViewsByAuthenticatedUsers` int(11) DEFAULT 0,
  `pagesWithErrors` int(11) DEFAULT 0,
  `ajaxRequests` int(11) DEFAULT 0,
  `coverViews` int(11) DEFAULT 0,
  `genealogySearches` int(11) DEFAULT 0,
  `groupedWorkSearches` int(11) DEFAULT 0,
  `openArchivesSearches` int(11) DEFAULT 0,
  `userListSearches` int(11) DEFAULT 0,
  `websiteSearches` int(11) DEFAULT 0,
  `eventsSearches` int(11) DEFAULT 0,
  `blockedRequests` int(11) DEFAULT 0,
  `blockedApiRequests` int(11) DEFAULT 0,
  `ebscoEdsSearches` int(11) DEFAULT 0,
  `instance` varchar(100) DEFAULT NULL,
  `sessionsStarted` int(11) DEFAULT 0,
  `timedOutSearches` int(11) DEFAULT 0,
  `timedOutSearchesWithHighLoad` int(11) DEFAULT 0,
  `searchesWithErrors` int(11) DEFAULT 0,
  `ebscohostSearches` int(11) DEFAULT 0,
  `emailsSent` int(11) DEFAULT 0,
  `emailsFailed` int(11) DEFAULT 0,
  `summonSearches` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `instance` (`instance`,`year`,`month`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS assabet_events;
CREATE TABLE `assabet_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `settingsId` int(11) NOT NULL,
  `externalId` varchar(150) NOT NULL,
  `title` varchar(255) NOT NULL,
  `rawChecksum` bigint(20) DEFAULT NULL,
  `rawResponse` mediumtext DEFAULT NULL,
  `deleted` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settingsId` (`settingsId`,`externalId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS assabet_settings;
CREATE TABLE `assabet_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `baseUrl` varchar(255) NOT NULL,
  `eventsInLists` tinyint(1) DEFAULT 1,
  `bypassAspenEventPages` tinyint(1) DEFAULT 0,
  `registrationModalBody` mediumtext DEFAULT NULL,
  `registrationModalBodyApp` varchar(500) DEFAULT NULL,
  `numberOfDaysToIndex` int(11) DEFAULT 365,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS author_authorities;
CREATE TABLE `author_authorities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `originalName` varchar(255) NOT NULL,
  `authoritativeName` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `originalName` (`originalName`),
  KEY `authoritativeName` (`authoritativeName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS author_authority;
CREATE TABLE `author_authority` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `author` varchar(512) NOT NULL,
  `dateAdded` int(11) DEFAULT NULL,
  `normalized` varchar(512) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `author` (`author`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS author_authority_alternative;
CREATE TABLE `author_authority_alternative` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `authorId` int(11) DEFAULT NULL,
  `alternativeAuthor` varchar(512) NOT NULL,
  `normalized` varchar(512) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `alternativeAuthor` (`alternativeAuthor`),
  KEY `authorId` (`authorId`),
  KEY `normalized` (`normalized`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS author_enrichment;
CREATE TABLE `author_enrichment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `authorName` varchar(255) NOT NULL,
  `hideWikipedia` tinyint(1) DEFAULT NULL,
  `wikipediaUrl` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `authorName` (`authorName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS axis360_export_log;
CREATE TABLE `axis360_export_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `notes` mediumtext DEFAULT NULL COMMENT 'Additional information about the run',
  `numProducts` int(11) DEFAULT 0,
  `numErrors` int(11) DEFAULT 0,
  `numAdded` int(11) DEFAULT 0,
  `numDeleted` int(11) DEFAULT 0,
  `numUpdated` int(11) DEFAULT 0,
  `numAvailabilityChanges` int(11) DEFAULT 0,
  `numMetadataChanges` int(11) DEFAULT 0,
  `settingId` int(11) DEFAULT NULL,
  `numSkipped` int(11) DEFAULT NULL,
  `numInvalidRecords` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `startTime` (`startTime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS axis360_record_usage;
CREATE TABLE `axis360_record_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance` varchar(100) DEFAULT NULL,
  `axis360Id` int(11) DEFAULT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `timesHeld` int(11) NOT NULL DEFAULT 0,
  `timesCheckedOut` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `instance` (`instance`,`axis360Id`,`year`,`month`),
  KEY `instance_2` (`instance`,`year`,`month`),
  KEY `instance_3` (`instance`,`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS axis360_scopes;
CREATE TABLE `axis360_scopes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `settingId` int(11) DEFAULT NULL,
  `includeAdult` tinyint(4) DEFAULT 1,
  `includeTeen` tinyint(4) DEFAULT 1,
  `includeKids` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS axis360_settings;
CREATE TABLE `axis360_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `apiUrl` varchar(255) DEFAULT NULL,
  `userInterfaceUrl` varchar(255) DEFAULT NULL,
  `vendorUsername` varchar(50) DEFAULT NULL,
  `vendorPassword` varchar(50) DEFAULT NULL,
  `libraryPrefix` varchar(50) DEFAULT NULL,
  `runFullUpdate` tinyint(1) DEFAULT 0,
  `lastUpdateOfChangedRecords` int(11) DEFAULT 0,
  `lastUpdateOfAllRecords` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS axis360_stats;
CREATE TABLE `axis360_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance` varchar(100) DEFAULT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `numCheckouts` int(11) NOT NULL DEFAULT 0,
  `numRenewals` int(11) NOT NULL DEFAULT 0,
  `numEarlyReturns` int(11) NOT NULL DEFAULT 0,
  `numHoldsPlaced` int(11) NOT NULL DEFAULT 0,
  `numHoldsCancelled` int(11) NOT NULL DEFAULT 0,
  `numHoldsFrozen` int(11) NOT NULL DEFAULT 0,
  `numHoldsThawed` int(11) NOT NULL DEFAULT 0,
  `numApiErrors` int(11) NOT NULL DEFAULT 0,
  `numConnectionFailures` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `instance` (`instance`,`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS axis360_title;
CREATE TABLE `axis360_title` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `axis360Id` varchar(25) NOT NULL,
  `isbn` varchar(13) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `primaryAuthor` varchar(255) DEFAULT NULL,
  `formatType` varchar(20) DEFAULT NULL,
  `rawChecksum` bigint(20) DEFAULT NULL,
  `rawResponse` longtext DEFAULT NULL,
  `dateFirstDetected` bigint(20) DEFAULT NULL,
  `lastChange` int(11) NOT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `axis360Id` (`axis360Id`),
  KEY `lastChange` (`lastChange`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS axis360_title_availability;
CREATE TABLE `axis360_title_availability` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titleId` int(11) DEFAULT NULL,
  `libraryPrefix` varchar(50) DEFAULT NULL,
  `ownedQty` int(11) DEFAULT NULL,
  `totalHolds` int(11) DEFAULT NULL,
  `settingId` int(11) DEFAULT NULL,
  `rawChecksum` bigint(20) DEFAULT NULL,
  `rawResponse` longtext DEFAULT NULL,
  `lastChange` int(11) NOT NULL,
  `available` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `titleId` (`titleId`,`settingId`),
  KEY `libraryPrefix` (`libraryPrefix`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS background_process;
CREATE TABLE `background_process` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owningUserId` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `notes` mediumtext DEFAULT NULL,
  `startTime` int(11) NOT NULL,
  `endTime` int(11) DEFAULT NULL,
  `isRunning` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `owningUserId` (`owningUserId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `bad_words`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bad_words` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique Id for bad_word',
  `word` varchar(50) NOT NULL COMMENT 'The bad word that will be replaced',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1651 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores information about bad_words that should be removed fr';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `bad_words` WRITE;
/*!40000 ALTER TABLE `bad_words` DISABLE KEYS */;
INSERT INTO `bad_words` VALUES (1,'2 girls 1 cup'),(2,'2g1c'),(3,'4r5e'),(4,'5h1t'),(5,'5hit'),(6,'a$$'),(7,'a$$hole'),(8,'a_s_s'),(9,'a2m'),(10,'a54'),(11,'a55'),(12,'a55hole'),(13,'aeolus'),(14,'ahole'),(15,'alabama hot pocket'),(16,'anal'),(17,'anal impaler'),(18,'anal leakage'),(19,'analannie'),(20,'analprobe'),(21,'analsex'),(22,'anilingus'),(23,'anus'),(24,'apeshit'),(25,'ar5e'),(26,'areola'),(27,'areole'),(28,'arian'),(29,'arrse'),(30,'arse'),(31,'arsehole'),(32,'aryan'),(33,'ass'),(34,'ass fuck'),(35,'ass hole'),(36,'assbag'),(37,'assbagger'),(38,'assbandit'),(39,'assbang'),(40,'assbanged'),(41,'assbanger'),(42,'assbangs'),(43,'assbite'),(44,'assblaster'),(45,'assclown'),(46,'asscock'),(47,'asscracker'),(48,'asses'),(49,'assface'),(50,'assfaces'),(51,'assfuck'),(52,'assfucker'),(53,'ass-fucker'),(54,'assfukka'),(55,'assgoblin'),(56,'assh0le'),(57,'asshat'),(58,'ass-hat'),(59,'asshead'),(60,'assho1e'),(61,'asshole'),(62,'assholes'),(63,'asshopper'),(64,'asshore'),(65,'ass-jabber'),(66,'assjacker'),(67,'assjockey'),(68,'asskiss'),(69,'asskisser'),(70,'assklown'),(71,'asslick'),(72,'asslicker'),(73,'asslover'),(74,'assman'),(75,'assmaster'),(76,'assmonkey'),(77,'assmucus'),(78,'assmunch'),(79,'assmuncher'),(80,'assnigger'),(81,'asspacker'),(82,'asspirate'),(83,'ass-pirate'),(84,'asspuppies'),(85,'assranger'),(86,'assshit'),(87,'assshole'),(88,'asssucker'),(89,'asswad'),(90,'asswhole'),(91,'asswhore'),(92,'asswipe'),(93,'asswipes'),(94,'auto erotic'),(95,'autoerotic'),(96,'axwound'),(97,'azazel'),(98,'azz'),(99,'b!tch'),(100,'b00bs'),(101,'b17ch'),(102,'b1tch'),(103,'baby batter'),(104,'baby juice'),(105,'badfuck'),(106,'ball gag'),(107,'ball gravy'),(108,'ball kicking'),(109,'ball licking'),(110,'ball sack'),(111,'ball sucking'),(112,'ballbag'),(113,'balllicker'),(114,'ballsack'),(115,'bampot'),(116,'bang (one\'s) box'),(117,'bangbros'),(118,'banger'),(119,'banging'),(120,'bareback'),(121,'barely legal'),(122,'barenaked'),(123,'barface'),(124,'barfface'),(125,'bastard'),(126,'bastardo'),(127,'bastards'),(128,'bastinado'),(129,'batty boy'),(130,'bazongas'),(131,'bazooms'),(132,'bbw'),(133,'bdsm'),(134,'beaner'),(135,'beaners'),(136,'beardedclam'),(137,'beastial'),(138,'beastiality'),(139,'beatch'),(140,'beater'),(141,'beatyourmeat'),(142,'beaver cleaver'),(143,'beaver lips'),(144,'beef curtain'),(145,'beef curtains'),(146,'beeyotch'),(147,'bellend'),(148,'beotch'),(149,'bestial'),(150,'bestiality'),(151,'bi+ch'),(152,'biatch'),(153,'big black'),(154,'big breasts'),(155,'big knockers'),(156,'big tits'),(157,'bigbastard'),(158,'bigbutt'),(159,'bigger'),(160,'bigtits'),(161,'bimbo'),(162,'bimbos'),(163,'bint'),(164,'birdlock'),(165,'bitch'),(166,'bitch tit'),(167,'bitchass'),(168,'bitched'),(169,'bitcher'),(170,'bitchers'),(171,'bitches'),(172,'bitchez'),(173,'bitchin'),(174,'bitching'),(175,'bitchtits'),(176,'bitchy'),(177,'black cock'),(178,'blonde action'),(179,'blonde on blonde action'),(180,'bloodclaat'),(181,'bloody hell'),(182,'blow job'),(183,'blow me'),(184,'blow mud'),(185,'blow your load'),(186,'blowjob'),(187,'blowjobs'),(188,'blue waffle'),(189,'blumpkin'),(190,'boang'),(191,'bogan'),(192,'bohunk'),(193,'boink'),(194,'boiolas'),(195,'bollick'),(196,'bollock'),(197,'bollocks'),(198,'bollok'),(199,'bollox'),(200,'bomd'),(201,'boned'),(202,'boner'),(203,'boners'),(204,'bong'),(205,'boob'),(206,'boobies'),(207,'boobs'),(208,'booby'),(209,'booger'),(210,'boong'),(211,'boonga'),(212,'booobs'),(213,'boooobs'),(214,'booooobs'),(215,'booooooobs'),(216,'bootee'),(217,'bootie'),(218,'booty'),(219,'booty call'),(220,'bowel'),(221,'bowels'),(222,'breast'),(223,'breastjob'),(224,'breastlover'),(225,'breastman'),(226,'breasts'),(227,'breeder'),(228,'brotherfucker'),(229,'brown showers'),(230,'brunette action'),(231,'buceta'),(232,'bugger'),(233,'buggered'),(234,'buggery'),(235,'bukkake'),(236,'bull shit'),(237,'bullcrap'),(238,'bulldike'),(239,'bulldyke'),(240,'bullet vibe'),(241,'bullshit'),(242,'bullshits'),(243,'bullshitted'),(244,'bullturds'),(245,'bum boy'),(246,'bumblefuck'),(247,'bumclat'),(248,'bumfuck'),(249,'bung'),(250,'bung hole'),(251,'bunga'),(252,'bunghole'),(253,'bunny fucker'),(254,'bust a load'),(255,'busty'),(256,'butchdike'),(257,'butchdyke'),(258,'butt'),(259,'butt fuck'),(260,'butt plug'),(261,'buttbang'),(262,'butt-bang'),(263,'buttcheeks'),(264,'buttface'),(265,'buttfuck'),(266,'butt-fuck'),(267,'buttfucka'),(268,'buttfucker'),(269,'butt-fucker'),(270,'butthead'),(271,'butthole'),(272,'buttman'),(273,'buttmuch'),(274,'buttmunch'),(275,'buttmuncher'),(276,'butt-pirate'),(277,'buttplug'),(278,'c.0.c.k'),(279,'c.o.c.k.'),(280,'c.u.n.t'),(281,'c0ck'),(282,'c-0-c-k'),(283,'c0cksucker'),(284,'caca'),(285,'cahone'),(286,'camel toe'),(287,'cameltoe'),(288,'camgirl'),(289,'camslut'),(290,'camwhore'),(291,'carpet muncher'),(292,'carpetmuncher'),(293,'cawk'),(294,'cervix'),(295,'chesticle'),(296,'chi-chi man'),(297,'chick with a dick'),(298,'child-fucker'),(299,'chin'),(300,'chinc'),(301,'chincs'),(302,'chink'),(303,'chinky'),(304,'choad'),(305,'choade'),(306,'choc ice'),(307,'chocolate rosebuds'),(308,'chode'),(309,'chodes'),(310,'chota bags'),(311,'cipa'),(312,'circlejerk'),(313,'cl1t'),(314,'cleveland steamer'),(315,'clit'),(316,'clit licker'),(317,'clitface'),(318,'clitfuck'),(319,'clitoris'),(320,'clitorus'),(321,'clits'),(322,'clitty'),(323,'clitty litter'),(324,'clogwog'),(325,'clover clamps'),(326,'clunge'),(327,'clusterfuck'),(328,'cnut'),(329,'cocain'),(330,'cocaine'),(331,'cock'),(332,'c-o-c-k'),(333,'cock pocket'),(334,'cock snot'),(335,'cock sucker'),(336,'cockass'),(337,'cockbite'),(338,'cockblock'),(339,'cockburger'),(340,'cockeye'),(341,'cockface'),(342,'cockfucker'),(343,'cockhead'),(344,'cockholster'),(345,'cockjockey'),(346,'cockknocker'),(347,'cockknoker'),(348,'cocklicker'),(349,'cocklover'),(350,'cocklump'),(351,'cockmaster'),(352,'cockmongler'),(353,'cockmongruel'),(354,'cockmonkey'),(355,'cockmunch'),(356,'cockmuncher'),(357,'cocknose'),(358,'cocknugget'),(359,'cocks'),(360,'cockshit'),(361,'cocksmith'),(362,'cocksmoke'),(363,'cocksmoker'),(364,'cocksniffer'),(365,'cocksucer'),(366,'cocksuck'),(367,'cocksuck'),(368,'cocksucked'),(369,'cocksucker'),(370,'cock-sucker'),(371,'cocksuckers'),(372,'cocksucking'),(373,'cocksucks'),(374,'cocksuka'),(375,'cocksukka'),(376,'cockwaffle'),(377,'coffin dodger'),(378,'coital'),(379,'cok'),(380,'cokmuncher'),(381,'coksucka'),(382,'commie'),(383,'condom'),(384,'coochie'),(385,'coochy'),(386,'coon'),(387,'coonnass'),(388,'coons'),(389,'cooter'),(390,'cop some wood'),(391,'coprolagnia'),(392,'coprophilia'),(393,'corksucker'),(394,'cornhole'),(395,'corp whore'),(396,'cox'),(397,'crack'),(398,'cracker'),(399,'crackwhore'),(400,'crack-whore'),(401,'crap'),(402,'crappy'),(403,'creampie'),(404,'cretin'),(405,'crikey'),(406,'cripple'),(407,'crotte'),(408,'cum'),(409,'cum chugger'),(410,'cum dumpster'),(411,'cum freak'),(412,'cum guzzler'),(413,'cumbubble'),(414,'cumdump'),(415,'cumdumpster'),(416,'cumguzzler'),(417,'cumjockey'),(418,'cummer'),(419,'cummin'),(420,'cumming'),(421,'cums'),(422,'cumshot'),(423,'cumshots'),(424,'cumslut'),(425,'cumstain'),(426,'cumtart'),(427,'cunilingus'),(428,'cunillingus'),(429,'cunn'),(430,'cunnie'),(431,'cunnilingus'),(432,'cunntt'),(433,'cunny'),(434,'cunt'),(435,'c-u-n-t'),(436,'cunt hair'),(437,'cuntass'),(438,'cuntbag'),(439,'cuntface'),(440,'cuntfuck'),(441,'cuntfucker'),(442,'cunthole'),(443,'cunthunter'),(444,'cuntlick'),(445,'cuntlick'),(446,'cuntlicker'),(447,'cuntlicker'),(448,'cuntlicking'),(449,'cuntrag'),(450,'cunts'),(451,'cuntsicle'),(452,'cuntslut'),(453,'cunt-struck'),(454,'cuntsucker'),(455,'cut rope'),(456,'cyalis'),(457,'cyberfuc'),(458,'cyberfuck'),(459,'cyberfucked'),(460,'cyberfucker'),(461,'cyberfuckers'),(462,'cyberfucking'),(463,'cybersex'),(464,'d0ng'),(465,'d0uch3'),(466,'d0uche'),(467,'d1ck'),(468,'d1ld0'),(469,'d1ldo'),(470,'dago'),(471,'dagos'),(472,'dammit'),(473,'damn'),(474,'damned'),(475,'damnit'),(476,'darkie'),(477,'dawgie-style'),(478,'deep throat'),(479,'deepthroat'),(480,'deggo'),(481,'dendrophilia'),(482,'dick'),(483,'dick head'),(484,'dick hole'),(485,'dick shy'),(486,'dickbag'),(487,'dickbeaters'),(488,'dickbrain'),(489,'dickdipper'),(490,'dickface'),(491,'dickflipper'),(492,'dickfuck'),(493,'dickfucker'),(494,'dickhead'),(495,'dickheads'),(496,'dickhole'),(497,'dickish'),(498,'dick-ish'),(499,'dickjuice'),(500,'dickmilk'),(501,'dickmonger'),(502,'dickripper'),(503,'dicks'),(504,'dicksipper'),(505,'dickslap'),(506,'dick-sneeze'),(507,'dicksucker'),(508,'dicksucking'),(509,'dicktickler'),(510,'dickwad'),(511,'dickweasel'),(512,'dickweed'),(513,'dickwhipper'),(514,'dickwod'),(515,'dickzipper'),(516,'diddle'),(517,'dike'),(518,'dildo'),(519,'dildos'),(520,'diligaf'),(521,'dillweed'),(522,'dimwit'),(523,'dingle'),(524,'dingleberries'),(525,'dingleberry'),(526,'dink'),(527,'dinks'),(528,'dipship'),(529,'dipshit'),(530,'dirsa'),(531,'dirty'),(532,'dirty pillows'),(533,'dirty sanchez'),(534,'dlck'),(535,'dog style'),(536,'dog-fucker'),(537,'doggie style'),(538,'doggiestyle'),(539,'doggie-style'),(540,'doggin'),(541,'dogging'),(542,'doggy style'),(543,'doggystyle'),(544,'doggy-style'),(545,'dolcett'),(546,'dominatrix'),(547,'dommes'),(548,'dong'),(549,'donkey punch'),(550,'donkeypunch'),(551,'donkeyribber'),(552,'doochbag'),(553,'doofus'),(554,'dookie'),(555,'doosh'),(556,'dopey'),(557,'double dong'),(558,'double penetration'),(559,'doublelift'),(560,'douch3'),(561,'douche'),(562,'douchebag'),(563,'douchebags'),(564,'douche-fag'),(565,'douchewaffle'),(566,'douchey'),(567,'dp action'),(568,'dry hump'),(569,'duche'),(570,'dumass'),(571,'dumb ass'),(572,'dumbass'),(573,'dumbasses'),(574,'dumbcunt'),(575,'dumbfuck'),(576,'dumbshit'),(577,'dumshit'),(578,'dvda'),(579,'dyke'),(580,'dykes'),(581,'eat a dick'),(582,'eat hair pie'),(583,'eat my ass'),(584,'eatpussy'),(585,'ecchi'),(586,'ejaculate'),(587,'ejaculated'),(588,'ejaculates'),(589,'ejaculating'),(590,'ejaculatings'),(591,'ejaculation'),(592,'ejakulate'),(593,'enlargement'),(594,'erect'),(595,'erection'),(596,'essohbee'),(597,'eunuch'),(598,'f u c k'),(599,'f u c k e r'),(600,'f.u.c.k'),(601,'f_u_c_k'),(602,'f4nny'),(603,'facefucker'),(604,'facial'),(605,'fack'),(606,'fag'),(607,'fagbag'),(608,'fagfucker'),(609,'fagg'),(610,'fagged'),(611,'fagging'),(612,'faggit'),(613,'faggitt'),(614,'faggot'),(615,'faggotcock'),(616,'faggots'),(617,'faggs'),(618,'fagot'),(619,'fagots'),(620,'fags'),(621,'fagtard'),(622,'faig'),(623,'faigt'),(624,'fanny'),(625,'fannybandit'),(626,'fannyflaps'),(627,'fannyfucker'),(628,'fanyy'),(629,'fart'),(630,'fartknocker'),(631,'fastfuck'),(632,'fatass'),(633,'fatfuck'),(634,'fatfucker'),(635,'fcuk'),(636,'fcuker'),(637,'fcuking'),(638,'fecal'),(639,'feck'),(640,'fecker'),(641,'felch'),(642,'felcher'),(643,'felching'),(644,'fellate'),(645,'fellatio'),(646,'feltch'),(647,'feltcher'),(648,'female squirting'),(649,'femdom'),(650,'fenian'),(651,'figging'),(652,'fingerbang'),(653,'fingerfuck'),(654,'fingerfuck'),(655,'fingerfucked'),(656,'fingerfucker'),(657,'fingerfucker'),(658,'fingerfuckers'),(659,'fingerfucking'),(660,'fingerfucks'),(661,'fingering'),(662,'fist fuck'),(663,'fisted'),(664,'fistfuck'),(665,'fistfucked'),(666,'fistfucker'),(667,'fistfucker'),(668,'fistfuckers'),(669,'fistfucking'),(670,'fistfuckings'),(671,'fistfucks'),(672,'fisting'),(673,'fisty'),(674,'flamer'),(675,'flange'),(676,'flaps'),(677,'fleshflute'),(678,'flog the log'),(679,'floozy'),(680,'foad'),(681,'foah'),(682,'fondle'),(683,'foobar'),(684,'fook'),(685,'fooker'),(686,'foot fetish'),(687,'footfuck'),(688,'footfucker'),(689,'footjob'),(690,'footlicker'),(691,'foreskin'),(692,'freakfuck'),(693,'freakyfucker'),(694,'freefuck'),(695,'freex'),(696,'frigg'),(697,'frigga'),(698,'frotting'),(699,'fubar'),(700,'fuc'),(701,'fuck'),(702,'f-u-c-k'),(703,'fuck buttons'),(704,'fuck hole'),(705,'fuck off'),(706,'fuck puppet'),(707,'fuck trophy'),(708,'fuck yo mama'),(709,'fuck you'),(710,'fucka'),(711,'fuckass'),(712,'fuck-ass'),(713,'fuckbag'),(714,'fuck-bitch'),(715,'fuckboy'),(716,'fuckbrain'),(717,'fuckbutt'),(718,'fuckbutter'),(719,'fucked'),(720,'fuckedup'),(721,'fucker'),(722,'fuckers'),(723,'fuckersucker'),(724,'fuckface'),(725,'fuckfreak'),(726,'fuckhead'),(727,'fuckheads'),(728,'fuckher'),(729,'fuckhole'),(730,'fuckin'),(731,'fucking'),(732,'fuckingbitch'),(733,'fuckings'),(734,'fuckingshitmotherfucker'),(735,'fuckme'),(736,'fuckme'),(737,'fuckmeat'),(738,'fuckmehard'),(739,'fuckmonkey'),(740,'fucknugget'),(741,'fucknut'),(742,'fucknutt'),(743,'fuckoff'),(744,'fucks'),(745,'fuckstick'),(746,'fucktard'),(747,'fuck-tard'),(748,'fucktards'),(749,'fucktart'),(750,'fucktoy'),(751,'fucktwat'),(752,'fuckup'),(753,'fuckwad'),(754,'fuckwhit'),(755,'fuckwhore'),(756,'fuckwit'),(757,'fuckwitt'),(758,'fuckyou'),(759,'fudge packer'),(760,'fudgepacker'),(761,'fudge-packer'),(762,'fuk'),(763,'fuker'),(764,'fukker'),(765,'fukkers'),(766,'fukkin'),(767,'fuks'),(768,'fukwhit'),(769,'fukwit'),(770,'fuq'),(771,'futanari'),(772,'fux'),(773,'fux0r'),(774,'fvck'),(775,'fxck'),(776,'gae'),(777,'gai'),(778,'gang bang'),(779,'gangbang'),(780,'gang-bang'),(781,'gangbanged'),(782,'gangbangs'),(783,'ganja'),(784,'gash'),(785,'gassy ass'),(786,'gay sex'),(787,'gayass'),(788,'gaybob'),(789,'gaydo'),(790,'gayfuck'),(791,'gayfuckist'),(792,'gaylord'),(793,'gays'),(794,'gaysex'),(795,'gaytard'),(796,'gaywad'),(797,'genitals'),(798,'gey'),(799,'gfy'),(800,'ghay'),(801,'ghey'),(802,'giant cock'),(803,'gigolo'),(804,'gippo'),(805,'girl on'),(806,'girl on top'),(807,'girls gone wild'),(808,'git'),(809,'glans'),(810,'goatcx'),(811,'goatse'),(812,'god damn'),(813,'godamn'),(814,'godamnit'),(815,'goddam'),(816,'god-dam'),(817,'goddammit'),(818,'goddamn'),(819,'goddamned'),(820,'god-damned'),(821,'goddamnit'),(822,'goddamnmuthafucker'),(823,'godsdamn'),(824,'gokkun'),(825,'golden shower'),(826,'goldenshower'),(827,'golliwog'),(828,'gonad'),(829,'gonads'),(830,'gonorrehea'),(831,'goo girl'),(832,'gooch'),(833,'goodpoop'),(834,'gook'),(835,'gooks'),(836,'goregasm'),(837,'gotohell'),(838,'gringo'),(839,'grope'),(840,'group sex'),(841,'gspot'),(842,'g-spot'),(843,'gtfo'),(844,'guido'),(845,'guro'),(846,'h0m0'),(847,'h0mo'),(848,'ham flap'),(849,'hand job'),(850,'handjob'),(851,'hard core'),(852,'hard on'),(853,'hardcore'),(854,'hardcoresex'),(855,'he11'),(856,'headfuck'),(857,'hebe'),(858,'heeb'),(859,'hell'),(860,'hemp'),(861,'hentai'),(862,'heroin'),(863,'herp'),(864,'herpes'),(865,'herpy'),(866,'heshe'),(867,'he-she'),(868,'hitler'),(869,'hiv'),(870,'ho'),(871,'hoar'),(872,'hoare'),(873,'hobag'),(874,'hoe'),(875,'hoer'),(876,'holy shit'),(877,'hom0'),(878,'homey'),(879,'homo'),(880,'homodumbshit'),(881,'homoerotic'),(882,'homoey'),(883,'honkey'),(884,'honky'),(885,'hooch'),(886,'hookah'),(887,'hoor'),(888,'hootch'),(889,'hooter'),(890,'hooters'),(891,'hore'),(892,'horniest'),(893,'horny'),(894,'hot carl'),(895,'hot chick'),(896,'hotpussy'),(897,'hotsex'),(898,'how to kill'),(899,'how to murdep'),(900,'how to murder'),(901,'huge fat'),(902,'hump'),(903,'humped'),(904,'humping'),(905,'hun'),(906,'hussy'),(907,'hymen'),(908,'iap'),(909,'iberian slap'),(910,'inbred'),(911,'incest'),(912,'injun'),(913,'intercourse'),(914,'j3rk0ff'),(915,'jack off'),(916,'jackass'),(917,'jackasses'),(918,'jackhole'),(919,'jackoff'),(920,'jack-off'),(921,'jaggi'),(922,'jagoff'),(923,'jail bait'),(924,'jailbait'),(925,'jap'),(926,'japs'),(927,'jelly donut'),(928,'jerk'),(929,'jerk off'),(930,'jerk0ff'),(931,'jerkass'),(932,'jerked'),(933,'jerkoff'),(934,'jerk-off'),(935,'jigaboo'),(936,'jiggaboo'),(937,'jiggerboo'),(938,'jism'),(939,'jiz'),(940,'jizm'),(941,'jizz'),(942,'jizzed'),(943,'jock'),(944,'juggs'),(945,'jungle bunny'),(946,'junglebunny'),(947,'junkie'),(948,'junky'),(949,'kafir'),(950,'kawk'),(951,'kike'),(952,'kikes'),(953,'kill'),(954,'kinbaku'),(955,'kinkster'),(956,'kinky'),(957,'kkk'),(958,'klan'),(959,'knob'),(960,'knob end'),(961,'knobbing'),(962,'knobead'),(963,'knobed'),(964,'knobend'),(965,'knobhead'),(966,'knobjocky'),(967,'knobjokey'),(968,'kock'),(969,'kondum'),(970,'kondums'),(971,'kooch'),(972,'kooches'),(973,'kootch'),(974,'kraut'),(975,'kum'),(976,'kummer'),(977,'kumming'),(978,'kums'),(979,'kunilingus'),(980,'kunja'),(981,'kunt'),(982,'kwif'),(983,'kyke'),(984,'l3i+ch'),(985,'l3itch'),(986,'labia'),(987,'lameass'),(988,'lardass'),(989,'leather restraint'),(990,'leather straight jacket'),(991,'lech'),(992,'lemon party'),(993,'leper'),(994,'lesbo'),(995,'lesbos'),(996,'lez'),(997,'lezbian'),(998,'lezbians'),(999,'lezbo'),(1000,'lezbos'),(1001,'lezza'),(1002,'lezzie'),(1003,'lezzies'),(1004,'lezzy'),(1005,'lmao'),(1006,'lmfao'),(1007,'loin'),(1008,'loins'),(1009,'lolita'),(1010,'looney'),(1011,'lube'),(1012,'m0f0'),(1013,'m0fo'),(1014,'m45terbate'),(1015,'ma5terb8'),(1016,'ma5terbate'),(1017,'mafugly'),(1018,'make me come'),(1019,'male squirting'),(1020,'mams'),(1021,'masochist'),(1022,'massa'),(1023,'masterb8'),(1024,'masterbat'),(1025,'masterbat3'),(1026,'masterbate'),(1027,'master-bate'),(1028,'masterbating'),(1029,'masterbation'),(1030,'masterbations'),(1031,'masturbate'),(1032,'masturbating'),(1033,'masturbation'),(1034,'maxi'),(1035,'mcfagget'),(1036,'menage a trois'),(1037,'menses'),(1038,'menstruate'),(1039,'menstruation'),(1040,'meth'),(1041,'m-fucking'),(1042,'mick'),(1043,'middle finger'),(1044,'midget'),(1045,'milf'),(1046,'minge'),(1047,'minger'),(1048,'missionary position'),(1049,'mof0'),(1050,'mofo'),(1051,'mo-fo'),(1052,'molest'),(1053,'mong'),(1054,'moo moo foo foo'),(1055,'moolie'),(1056,'moron'),(1057,'mothafuck'),(1058,'mothafucka'),(1059,'mothafuckas'),(1060,'mothafuckaz'),(1061,'mothafucked'),(1062,'mothafucker'),(1063,'mothafuckers'),(1064,'mothafuckin'),(1065,'mothafucking'),(1066,'mothafuckings'),(1067,'mothafucks'),(1068,'mother fucker'),(1069,'motherfuck'),(1070,'motherfucka'),(1071,'motherfucked'),(1072,'motherfucker'),(1073,'motherfuckers'),(1074,'motherfuckin'),(1075,'motherfucking'),(1076,'motherfuckings'),(1077,'motherfuckka'),(1078,'motherfucks'),(1079,'mound of venus'),(1080,'mr hands'),(1081,'mtherfucker'),(1082,'mthrfucker'),(1083,'mthrfucking'),(1084,'muff'),(1085,'muff diver'),(1086,'muff puff'),(1087,'muffdiver'),(1088,'muffdiving'),(1089,'munging'),(1090,'munter'),(1091,'mutha'),(1092,'muthafecker'),(1093,'muthafuckaz'),(1094,'muthafuckker'),(1095,'muther'),(1096,'mutherfucker'),(1097,'mutherfucking'),(1098,'muthrfucking'),(1099,'n1gga'),(1100,'n1gger'),(1101,'nad'),(1102,'nads'),(1103,'naked'),(1104,'nambla'),(1105,'nappy'),(1106,'nawashi'),(1107,'nazi'),(1108,'nazism'),(1109,'need the dick'),(1110,'negro'),(1111,'neonazi'),(1112,'nig nog'),(1113,'nigaboo'),(1114,'nigg3r'),(1115,'nigg4h'),(1116,'nigga'),(1117,'niggah'),(1118,'niggas'),(1119,'niggaz'),(1120,'nigger'),(1121,'niggers'),(1122,'niggle'),(1123,'niglet'),(1124,'nig-nog'),(1125,'nimphomania'),(1126,'nimrod'),(1127,'ninny'),(1128,'nipple'),(1129,'nipples'),(1130,'nob'),(1131,'nob jokey'),(1132,'nobhead'),(1133,'nobjocky'),(1134,'nobjokey'),(1135,'nonce'),(1136,'nooky'),(1137,'nsfw images'),(1138,'numbnuts'),(1139,'nut butter'),(1140,'nut sack'),(1141,'nutsack'),(1142,'nutter'),(1143,'nympho'),(1144,'nymphomania'),(1145,'octopussy'),(1146,'old bag'),(1147,'omg'),(1148,'omorashi'),(1149,'one cup two girls'),(1150,'one guy one jar'),(1151,'orally'),(1152,'orgasim'),(1153,'orgasims'),(1154,'orgasm'),(1155,'orgasmic'),(1156,'orgasms'),(1157,'orgies'),(1158,'orgy'),(1159,'ovary'),(1160,'ovum'),(1161,'ovums'),(1162,'p.u.s.s.y.'),(1163,'p0rn'),(1164,'paddy'),(1165,'paedophile'),(1166,'paki'),(1167,'panooch'),(1168,'pansy'),(1169,'pantie'),(1170,'panties'),(1171,'panty'),(1172,'pastie'),(1173,'pcp'),(1174,'pecker'),(1175,'peckerhead'),(1176,'pedo'),(1177,'pedobear'),(1178,'pedophile'),(1179,'pedophilia'),(1180,'pedophiliac'),(1181,'pee'),(1182,'peepee'),(1183,'pegging'),(1184,'penetrate'),(1185,'penetration'),(1186,'penial'),(1187,'penile'),(1188,'penis'),(1189,'penisbanger'),(1190,'penisfucker'),(1191,'penispuffer'),(1192,'perversion'),(1193,'peyote'),(1194,'phalli'),(1195,'phallic'),(1196,'phone sex'),(1197,'phonesex'),(1198,'phuck'),(1199,'phuk'),(1200,'phuked'),(1201,'phuking'),(1202,'phukked'),(1203,'phukking'),(1204,'phuks'),(1205,'phuq'),(1206,'piece of shit'),(1207,'pigfucker'),(1208,'pikey'),(1209,'pillowbiter'),(1210,'pimp'),(1211,'pimpis'),(1212,'pinko'),(1213,'piss'),(1214,'piss off'),(1215,'piss pig'),(1216,'pissed'),(1217,'pissed off'),(1218,'pisser'),(1219,'pissers'),(1220,'pisses'),(1221,'pissflaps'),(1222,'pissin'),(1223,'pissing'),(1224,'pissoff'),(1225,'piss-off'),(1226,'pisspig'),(1227,'playboy'),(1228,'pleasure chest'),(1229,'pms'),(1230,'polack'),(1231,'pole smoker'),(1232,'polesmoker'),(1233,'pollock'),(1234,'ponyplay'),(1235,'poof'),(1236,'poon'),(1237,'poonani'),(1238,'poonany'),(1239,'poontang'),(1240,'poop'),(1241,'poop chute'),(1242,'poopchute'),(1243,'poopuncher'),(1244,'porch monkey'),(1245,'porchmonkey'),(1246,'porn'),(1247,'porno'),(1248,'pornography'),(1249,'pornos'),(1250,'pot'),(1251,'potty'),(1252,'prick'),(1253,'pricks'),(1254,'prickteaser'),(1255,'prig'),(1256,'prince albert piercing'),(1257,'prod'),(1258,'pron'),(1259,'prostitute'),(1260,'prude'),(1261,'psycho'),(1262,'pthc'),(1263,'pube'),(1264,'pubes'),(1265,'pubic'),(1266,'pubis'),(1267,'punani'),(1268,'punanny'),(1269,'punany'),(1270,'punkass'),(1271,'punky'),(1272,'punta'),(1273,'puss'),(1274,'pusse'),(1275,'pussi'),(1276,'pussies'),(1277,'pussy'),(1278,'pussy fart'),(1279,'pussy palace'),(1280,'pussylicking'),(1281,'pussypounder'),(1282,'pussys'),(1283,'pust'),(1284,'puto'),(1285,'queaf'),(1286,'queef'),(1287,'queerbait'),(1288,'queerhole'),(1289,'queero'),(1290,'quicky'),(1291,'quim'),(1292,'raghead'),(1293,'raging boner'),(1294,'rapey'),(1295,'raping'),(1296,'rapist'),(1297,'raunch'),(1298,'rectal'),(1299,'rectum'),(1300,'rectus'),(1301,'reefer'),(1302,'reetard'),(1303,'reich'),(1304,'renob'),(1305,'retard'),(1306,'retarded'),(1307,'reverse cowgirl'),(1308,'revue'),(1309,'rimjaw'),(1310,'rimjob'),(1311,'rimming'),(1312,'ritard'),(1313,'rosy palm'),(1314,'rosy palm and her 5 sisters'),(1315,'rtard'),(1316,'r-tard'),(1317,'rum'),(1318,'rump'),(1319,'rumprammer'),(1320,'ruski'),(1321,'rusty trombone'),(1322,'s hit'),(1323,'s&m'),(1324,'s.h.i.t.'),(1325,'s.o.b.'),(1326,'s_h_i_t'),(1327,'s0b'),(1328,'sadism'),(1329,'sadist'),(1330,'sambo'),(1331,'sand nigger'),(1332,'sandbar'),(1333,'sandler'),(1334,'sandnigger'),(1335,'sanger'),(1336,'santorum'),(1337,'sausage queen'),(1338,'scag'),(1339,'scantily'),(1340,'scat'),(1341,'schizo'),(1342,'schlong'),(1343,'scissoring'),(1344,'screwed'),(1345,'screwing'),(1346,'scroat'),(1347,'scrog'),(1348,'scrot'),(1349,'scrote'),(1350,'scrotum'),(1351,'scrud'),(1352,'scum'),(1353,'seaman'),(1354,'seamen'),(1355,'seduce'),(1356,'seks'),(1357,'semen'),(1358,'sexo'),(1359,'sexy'),(1360,'sh!+'),(1361,'sh!t'),(1362,'sh1t'),(1363,'s-h-1-t'),(1364,'shag'),(1365,'shagger'),(1366,'shaggin'),(1367,'shagging'),(1368,'shamedame'),(1369,'shaved beaver'),(1370,'shaved pussy'),(1371,'shemale'),(1372,'shi+'),(1373,'shibari'),(1374,'shirt lifter'),(1375,'shit'),(1376,'s-h-i-t'),(1377,'shit ass'),(1378,'shit fucker'),(1379,'shitass'),(1380,'shitbag'),(1381,'shitbagger'),(1382,'shitblimp'),(1383,'shitbrains'),(1384,'shitbreath'),(1385,'shitcanned'),(1386,'shitcunt'),(1387,'shitdick'),(1388,'shite'),(1389,'shiteater'),(1390,'shited'),(1391,'shitey'),(1392,'shitface'),(1393,'shitfaced'),(1394,'shitfuck'),(1395,'shitfull'),(1396,'shithead'),(1397,'shitheads'),(1398,'shithole'),(1399,'shithouse'),(1400,'shiting'),(1401,'shitings'),(1402,'shits'),(1403,'shitspitter'),(1404,'shitstain'),(1405,'shitt'),(1406,'shitted'),(1407,'shitter'),(1408,'shitters'),(1409,'shittier'),(1410,'shittiest'),(1411,'shitting'),(1412,'shittings'),(1413,'shitty'),(1414,'shiz'),(1415,'shiznit'),(1416,'shota'),(1417,'shrimping'),(1418,'sissy'),(1419,'skag'),(1420,'skank'),(1421,'skeet'),(1422,'skullfuck'),(1423,'slag'),(1424,'slanteye'),(1425,'slave'),(1426,'sleaze'),(1427,'sleazy'),(1428,'slope'),(1429,'slut'),(1430,'slut bucket'),(1431,'slutbag'),(1432,'slutdumper'),(1433,'slutkiss'),(1434,'sluts'),(1435,'smartass'),(1436,'smartasses'),(1437,'smeg'),(1438,'smegma'),(1439,'smut'),(1440,'smutty'),(1441,'snatch'),(1442,'snowballing'),(1443,'snuff'),(1444,'s-o-b'),(1445,'sod off'),(1446,'sodom'),(1447,'sodomize'),(1448,'sodomy'),(1449,'son of a bitch'),(1450,'son of a motherless goat'),(1451,'son of a whore'),(1452,'son-of-a-bitch'),(1453,'souse'),(1454,'soused'),(1455,'spac'),(1456,'sperm'),(1457,'spic'),(1458,'spick'),(1459,'spik'),(1460,'spiks'),(1461,'splooge'),(1462,'splooge moose'),(1463,'spooge'),(1464,'spook'),(1465,'spread legs'),(1466,'spunk'),(1467,'stfu'),(1468,'stiffy'),(1469,'strap on'),(1470,'strapon'),(1471,'strappado'),(1472,'strip'),(1473,'strip club'),(1474,'stroke'),(1475,'stupid'),(1476,'style doggy'),(1477,'suck'),(1478,'suckass'),(1479,'sucked'),(1480,'sucking'),(1481,'sucks'),(1482,'suicide girls'),(1483,'sultry women'),(1484,'sumofabiatch'),(1485,'swastika'),(1486,'swinger'),(1487,'t1t'),(1488,'t1tt1e5'),(1489,'t1tties'),(1490,'taff'),(1491,'taig'),(1492,'tainted love'),(1493,'taking the piss'),(1494,'tampon'),(1495,'tard'),(1496,'tart'),(1497,'taste my'),(1498,'tawdry'),(1499,'tea bagging'),(1500,'teabagging'),(1501,'teat'),(1502,'teets'),(1503,'teez'),(1504,'terd'),(1505,'teste'),(1506,'testee'),(1507,'testes'),(1508,'testical'),(1509,'testicle'),(1510,'testis'),(1511,'threesome'),(1512,'throating'),(1513,'thrust'),(1514,'thug'),(1515,'thundercunt'),(1516,'tied up'),(1517,'tight white'),(1518,'tinkle'),(1519,'tit'),(1520,'tit wank'),(1521,'titfuck'),(1522,'titi'),(1523,'tities'),(1524,'tits'),(1525,'titt'),(1526,'tittie5'),(1527,'tittiefucker'),(1528,'titties'),(1529,'titty'),(1530,'tittyfuck'),(1531,'tittyfucker'),(1532,'tittywank'),(1533,'titwank'),(1534,'toke'),(1535,'tongue in a'),(1536,'toots'),(1537,'topless'),(1538,'tosser'),(1539,'towelhead'),(1540,'tramp'),(1541,'tranny'),(1542,'trashy'),(1543,'tribadism'),(1544,'trumped'),(1545,'tub girl'),(1546,'tubgirl'),(1547,'turd'),(1548,'tush'),(1549,'tushy'),(1550,'tw4t'),(1551,'twat'),(1552,'twathead'),(1553,'twatlips'),(1554,'twats'),(1555,'twatty'),(1556,'twatwaffle'),(1557,'twink'),(1558,'twinkie'),(1559,'two fingers'),(1560,'two fingers with tongue'),(1561,'two girls one cup'),(1562,'twunt'),(1563,'twunter'),(1564,'unclefucker'),(1565,'undies'),(1566,'undressing'),(1567,'unwed'),(1568,'upskirt'),(1569,'urethra play'),(1570,'urinal'),(1571,'urine'),(1572,'urophilia'),(1573,'uterus'),(1574,'uzi'),(1575,'v14gra'),(1576,'v1gra'),(1577,'vag'),(1578,'vagina'),(1579,'vajayjay'),(1580,'va-j-j'),(1581,'valium'),(1582,'venus mound'),(1583,'veqtable'),(1584,'viagra'),(1585,'vibrator'),(1586,'violet wand'),(1587,'vixen'),(1588,'vjayjay'),(1589,'vomit'),(1590,'vorarephilia'),(1591,'voyeur'),(1592,'vulva'),(1593,'w00se'),(1594,'wad'),(1595,'wang'),(1596,'wank'),(1597,'wanker'),(1598,'wankjob'),(1599,'wanky'),(1600,'wazoo'),(1601,'wedgie'),(1602,'weenie'),(1603,'weewee'),(1604,'weiner'),(1605,'weirdo'),(1606,'wench'),(1607,'wet dream'),(1608,'wetback'),(1609,'wh0re'),(1610,'wh0reface'),(1611,'white power'),(1612,'whitey'),(1613,'whiz'),(1614,'whoar'),(1615,'whoralicious'),(1616,'whore'),(1617,'whorealicious'),(1618,'whorebag'),(1619,'whored'),(1620,'whoreface'),(1621,'whorehopper'),(1622,'whorehouse'),(1623,'whores'),(1624,'whoring'),(1625,'wigger'),(1626,'willies'),(1627,'willy'),(1628,'window licker'),(1629,'wiseass'),(1630,'wiseasses'),(1631,'wog'),(1632,'womb'),(1633,'woody'),(1634,'wop'),(1635,'wrapping men'),(1636,'wrinkled starfish'),(1637,'wtf'),(1638,'xrated'),(1639,'x-rated'),(1640,'xx'),(1641,'xxx'),(1642,'yaoi'),(1643,'yeasty'),(1644,'yellow showers'),(1645,'yid'),(1646,'yiffy'),(1647,'yobbo'),(1648,'zoophile'),(1649,'zoophilia'),(1650,'zubb');
/*!40000 ALTER TABLE `bad_words` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

DROP TABLE IF EXISTS bookcover_info;
CREATE TABLE `bookcover_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recordType` varchar(20) DEFAULT NULL,
  `recordId` varchar(50) DEFAULT NULL,
  `firstLoaded` int(11) NOT NULL,
  `lastUsed` int(11) NOT NULL,
  `imageSource` varchar(100) DEFAULT NULL,
  `sourceWidth` int(11) DEFAULT NULL,
  `sourceHeight` int(11) DEFAULT NULL,
  `thumbnailLoaded` tinyint(1) DEFAULT 0,
  `mediumLoaded` tinyint(1) DEFAULT 0,
  `largeLoaded` tinyint(1) DEFAULT 0,
  `uploadedImage` tinyint(1) DEFAULT 0,
  `disallowThirdPartyCover` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `record_info` (`recordType`,`recordId`),
  KEY `lastUsed` (`lastUsed`),
  KEY `imageSource` (`imageSource`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS browse_category;
CREATE TABLE `browse_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `textId` varchar(60) NOT NULL DEFAULT '-1',
  `userId` int(11) DEFAULT NULL,
  `sharing` enum('private','location','library','everyone') DEFAULT 'everyone',
  `label` varchar(50) NOT NULL,
  `description` longtext DEFAULT NULL,
  `defaultFilter` mediumtext DEFAULT NULL,
  `defaultSort` enum('relevance','popularity','newest_to_oldest','oldest_to_newest','author','title','user_rating','holds','publication_year_desc','publication_year_asc') DEFAULT 'relevance',
  `searchTerm` varchar(500) NOT NULL DEFAULT '',
  `numTimesShown` int(11) NOT NULL DEFAULT 0,
  `numTitlesClickedOn` mediumint(9) NOT NULL DEFAULT 0,
  `sourceListId` mediumint(9) DEFAULT NULL,
  `source` varchar(50) NOT NULL,
  `libraryId` int(11) DEFAULT -1,
  `startDate` int(11) DEFAULT 0,
  `endDate` int(11) DEFAULT 0,
  `numTimesDismissed` mediumint(9) NOT NULL DEFAULT 0,
  `sourceCourseReserveId` mediumint(9) DEFAULT -1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `textId` (`textId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS browse_category_dismissal;
CREATE TABLE `browse_category_dismissal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `browseCategoryId` varchar(60) DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userBrowseCategory` (`userId`,`browseCategoryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS browse_category_group;
CREATE TABLE `browse_category_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `defaultBrowseMode` tinyint(1) DEFAULT 0,
  `browseCategoryRatingsMode` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS browse_category_group_entry;
CREATE TABLE `browse_category_group_entry` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `browseCategoryGroupId` int(11) NOT NULL,
  `browseCategoryId` int(11) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `browseCategoryGroupId` (`browseCategoryGroupId`,`browseCategoryId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS browse_category_group_users;
CREATE TABLE `browse_category_group_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `browseCategoryGroupId` int(11) DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `browseCategoryGroupId` (`browseCategoryGroupId`,`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS browse_category_subcategories;
CREATE TABLE `browse_category_subcategories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `browseCategoryId` int(11) NOT NULL,
  `subCategoryId` int(11) NOT NULL,
  `weight` smallint(5) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subCategoryId` (`subCategoryId`,`browseCategoryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS cached_values;
CREATE TABLE `cached_values` (
  `cacheKey` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `value` varchar(16000) DEFAULT NULL,
  `expirationTime` int(11) DEFAULT NULL,
  UNIQUE KEY `cacheKey` (`cacheKey`),
  KEY `expirationTime` (`expirationTime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS calendar_display_settings;
CREATE TABLE `calendar_display_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `cover` varchar(100) DEFAULT NULL,
  `altText` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
DROP TABLE IF EXISTS ce_campaign;
CREATE TABLE `ce_campaign` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `startDate` date DEFAULT NULL,
  `endDate` date DEFAULT NULL,
  `enrollmentCounter` int(11) DEFAULT 0,
  `unenrollmentCounter` int(11) DEFAULT 0,
  `currentEnrollments` int(11) NOT NULL DEFAULT 0,
  `campaignReward` int(11) DEFAULT -1,
  `userAgeRange` varchar(255) DEFAULT 'All Ages',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ce_campaign_data;
CREATE TABLE `ce_campaign_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaignId` int(11) NOT NULL,
  `month` int(2) NOT NULL,
  `year` int(4) NOT NULL,
  `totalEnrollments` int(11) NOT NULL,
  `currentEnrollments` int(11) NOT NULL,
  `totalUnenrollments` int(11) NOT NULL,
  `instance` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ce_campaign_library_access;
CREATE TABLE `ce_campaign_library_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaignId` int(11) NOT NULL,
  `libraryId` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ce_campaign_milestone_progress_entries;
CREATE TABLE `ce_campaign_milestone_progress_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `ce_campaign_id` int(11) NOT NULL,
  `ce_milestone_id` int(11) NOT NULL,
  `ce_campaign_milestone_users_progress_id` int(11) NOT NULL,
  `tableName` varchar(100) DEFAULT NULL,
  `processed` tinyint(4) DEFAULT 0,
  `object` mediumtext DEFAULT NULL,
  `timestamp` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ce_campaign_milestone_users_progress;
CREATE TABLE `ce_campaign_milestone_users_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `ce_campaign_id` int(11) NOT NULL,
  `ce_milestone_id` int(11) NOT NULL,
  `progress` int(11) NOT NULL,
  `rewardGiven` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ce_campaign_milestones;
CREATE TABLE `ce_campaign_milestones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaignId` int(11) NOT NULL,
  `milestoneId` int(11) NOT NULL,
  `goal` int(11) DEFAULT 0,
  `reward` int(11) DEFAULT -1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ce_campaign_patron_type_access;
CREATE TABLE `ce_campaign_patron_type_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaignId` int(11) NOT NULL,
  `patronTypeId` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ce_milestone;
CREATE TABLE `ce_milestone` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `conditionalField` varchar(100) DEFAULT NULL,
  `conditionalOperator` varchar(100) DEFAULT NULL,
  `conditionalValue` varchar(100) DEFAULT NULL,
  `milestoneType` varchar(100) DEFAULT NULL,
  `campaignId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ce_reward;
CREATE TABLE `ce_reward` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `rewardType` int(11) DEFAULT -1,
  `badgeImage` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ce_user_campaign;
CREATE TABLE `ce_user_campaign` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `campaignId` int(11) NOT NULL,
  `enrollmentDate` datetime DEFAULT current_timestamp(),
  `unenrollmentDate` datetime DEFAULT NULL,
  `completed` tinyint(4) DEFAULT 0,
  `rewardGiven` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ce_user_campaign_data;
CREATE TABLE `ce_user_campaign_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `month` int(2) NOT NULL,
  `year` int(4) NOT NULL,
  `enrollmentCount` int(11) DEFAULT 0,
  `campaignId` int(11) NOT NULL,
  `instance` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ce_user_completed_milestones;
CREATE TABLE `ce_user_completed_milestones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `milestoneId` int(11) NOT NULL,
  `campaignId` int(11) NOT NULL,
  `completedAt` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS claim_authorship_requests;
CREATE TABLE `claim_authorship_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `message` longtext DEFAULT NULL,
  `pid` varchar(50) DEFAULT NULL,
  `dateRequested` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS cloud_library_availability;
CREATE TABLE `cloud_library_availability` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cloudLibraryId` varchar(25) NOT NULL,
  `totalCopies` smallint(6) NOT NULL DEFAULT 0,
  `sharedCopies` smallint(6) NOT NULL DEFAULT 0,
  `totalLoanCopies` smallint(6) NOT NULL DEFAULT 0,
  `totalHoldCopies` smallint(6) NOT NULL DEFAULT 0,
  `sharedLoanCopies` smallint(6) NOT NULL DEFAULT 0,
  `rawChecksum` bigint(20) DEFAULT NULL,
  `rawResponse` longtext DEFAULT NULL,
  `lastChange` int(11) NOT NULL,
  `settingId` int(11) DEFAULT NULL,
  `availabilityType` smallint(6) NOT NULL DEFAULT 1,
  `typeRawChecksum` bigint(20) DEFAULT NULL,
  `typeRawResponse` mediumtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cloudLibraryId` (`cloudLibraryId`,`settingId`),
  KEY `lastChange` (`lastChange`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS cloud_library_export_log;
CREATE TABLE `cloud_library_export_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `notes` mediumtext DEFAULT NULL COMMENT 'Additional information about the run',
  `numProducts` int(11) DEFAULT 0,
  `numErrors` int(11) DEFAULT 0,
  `numAdded` int(11) DEFAULT 0,
  `numDeleted` int(11) DEFAULT 0,
  `numUpdated` int(11) DEFAULT 0,
  `numAvailabilityChanges` int(11) DEFAULT 0,
  `numMetadataChanges` int(11) DEFAULT 0,
  `settingId` int(11) DEFAULT NULL,
  `numInvalidRecords` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `startTime` (`startTime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS cloud_library_record_usage;
CREATE TABLE `cloud_library_record_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cloudLibraryId` int(11) DEFAULT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `timesHeld` int(11) NOT NULL,
  `timesCheckedOut` int(11) NOT NULL,
  `instance` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`cloudLibraryId`,`year`,`month`),
  UNIQUE KEY `instance_2` (`instance`,`cloudLibraryId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS cloud_library_scopes;
CREATE TABLE `cloud_library_scopes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `includeEBooks` tinyint(4) DEFAULT 1,
  `includeEAudiobook` tinyint(4) DEFAULT 1,
  `settingId` int(11) DEFAULT NULL,
  `includeAdult` tinyint(4) DEFAULT 1,
  `includeTeen` tinyint(4) DEFAULT 1,
  `includeKids` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS cloud_library_settings;
CREATE TABLE `cloud_library_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `apiUrl` varchar(255) DEFAULT NULL,
  `userInterfaceUrl` varchar(255) DEFAULT NULL,
  `libraryId` varchar(50) DEFAULT NULL,
  `accountId` varchar(50) DEFAULT NULL,
  `accountKey` varchar(50) DEFAULT NULL,
  `runFullUpdate` tinyint(1) DEFAULT 0,
  `lastUpdateOfChangedRecords` int(11) DEFAULT 0,
  `lastUpdateOfAllRecords` int(11) DEFAULT 0,
  `useAlternateLibraryCard` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS cloud_library_title;
CREATE TABLE `cloud_library_title` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cloudLibraryId` varchar(25) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `subTitle` varchar(255) DEFAULT NULL,
  `author` varchar(255) DEFAULT NULL,
  `format` varchar(50) DEFAULT NULL,
  `targetAudience` varchar(25) DEFAULT 'ADULT',
  `rawChecksum` bigint(20) DEFAULT NULL,
  `rawResponse` longtext DEFAULT NULL,
  `dateFirstDetected` bigint(20) DEFAULT NULL,
  `lastChange` int(11) NOT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cloudLibraryId` (`cloudLibraryId`),
  KEY `lastChange` (`lastChange`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS coce_settings;
CREATE TABLE `coce_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `coceServerUrl` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS collection_spotlight_lists;
CREATE TABLE `collection_spotlight_lists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collectionSpotlightId` int(11) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT 0,
  `displayFor` enum('all','loggedIn','notLoggedIn') NOT NULL DEFAULT 'all',
  `name` varchar(50) NOT NULL,
  `source` varchar(500) NOT NULL,
  `fullListLink` varchar(500) DEFAULT '',
  `defaultFilter` mediumtext DEFAULT NULL,
  `defaultSort` enum('relevance','popularity','newest_to_oldest','oldest_to_newest','author','title','user_rating','holds','publication_year_desc','publication_year_asc') DEFAULT 'relevance',
  `searchTerm` varchar(500) NOT NULL DEFAULT '',
  `sourceListId` mediumint(9) DEFAULT NULL,
  `sourceCourseReserveId` mediumint(9) DEFAULT -1,
  PRIMARY KEY (`id`),
  KEY `ListWidgetId` (`collectionSpotlightId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='The lists that should appear within the widget';
DROP TABLE IF EXISTS collection_spotlights;
CREATE TABLE `collection_spotlights` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `showTitleDescriptions` tinyint(4) DEFAULT 1,
  `onSelectCallback` varchar(255) DEFAULT '',
  `customCss` varchar(500) NOT NULL,
  `listDisplayType` enum('tabs','dropdown') NOT NULL DEFAULT 'tabs',
  `autoRotate` tinyint(4) NOT NULL DEFAULT 0,
  `showMultipleTitles` tinyint(4) NOT NULL DEFAULT 1,
  `libraryId` int(11) NOT NULL DEFAULT -1,
  `style` enum('vertical','horizontal','single','single-with-next','text-list','horizontal-carousel') NOT NULL DEFAULT 'horizontal',
  `coverSize` enum('small','medium') NOT NULL DEFAULT 'small',
  `showRatings` tinyint(4) NOT NULL DEFAULT 0,
  `showTitle` tinyint(4) NOT NULL DEFAULT 1,
  `showAuthor` tinyint(4) NOT NULL DEFAULT 1,
  `showViewMoreLink` tinyint(4) NOT NULL DEFAULT 0,
  `viewMoreLinkMode` enum('covers','list') NOT NULL DEFAULT 'list',
  `showSpotlightTitle` tinyint(4) NOT NULL DEFAULT 1,
  `numTitlesToShow` int(11) NOT NULL DEFAULT 25,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='A widget that can be displayed within Pika or within other sites';
DROP TABLE IF EXISTS communico_events;
CREATE TABLE `communico_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `settingsId` int(11) NOT NULL,
  `externalId` varchar(36) NOT NULL,
  `title` varchar(255) NOT NULL,
  `rawChecksum` bigint(20) DEFAULT NULL,
  `rawResponse` mediumtext DEFAULT NULL,
  `deleted` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settingsId` (`settingsId`,`externalId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS communico_settings;
CREATE TABLE `communico_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `baseUrl` varchar(255) NOT NULL,
  `clientId` varchar(36) NOT NULL,
  `clientSecret` varchar(36) NOT NULL,
  `eventsInLists` tinyint(1) DEFAULT 1,
  `bypassAspenEventPages` tinyint(1) DEFAULT 0,
  `registrationModalBody` mediumtext DEFAULT NULL,
  `numberOfDaysToIndex` int(11) DEFAULT 365,
  `registrationModalBodyApp` varchar(500) DEFAULT NULL,
  `lastUpdateOfAllEvents` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS companion_system;
CREATE TABLE `companion_system` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serverName` varchar(72) NOT NULL,
  `serverUrl` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
DROP TABLE IF EXISTS component_development_epic_link;
CREATE TABLE `component_development_epic_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `componentId` int(11) DEFAULT NULL,
  `epicId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `componentId` (`componentId`,`epicId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS component_development_task_link;
CREATE TABLE `component_development_task_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `componentId` int(11) DEFAULT NULL,
  `taskId` int(11) DEFAULT NULL,
  `weight` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `componentId` (`componentId`,`taskId`),
  KEY `componentId_2` (`componentId`,`weight`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS component_ticket_link;
CREATE TABLE `component_ticket_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticketId` int(11) DEFAULT NULL,
  `componentId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticketId` (`ticketId`,`componentId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS comprise_settings;
CREATE TABLE `comprise_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customerName` varchar(50) DEFAULT NULL,
  `customerId` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customerName` (`customerName`),
  UNIQUE KEY `customerId` (`customerId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS contentcafe_settings;
CREATE TABLE `contentcafe_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contentCafeId` varchar(50) NOT NULL,
  `pwd` varchar(50) NOT NULL,
  `hasSummary` tinyint(1) DEFAULT 1,
  `hasToc` tinyint(1) DEFAULT 0,
  `hasExcerpt` tinyint(1) DEFAULT 0,
  `hasAuthorNotes` tinyint(1) DEFAULT 0,
  `enabled` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS course_reserve;
CREATE TABLE `course_reserve` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` int(11) DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0,
  `dateUpdated` int(11) DEFAULT NULL,
  `courseLibrary` varchar(25) DEFAULT NULL,
  `courseInstructor` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `courseNumber` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `courseTitle` varchar(200) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `course` (`courseLibrary`,`courseNumber`,`courseInstructor`,`courseTitle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS course_reserve_entry;
CREATE TABLE `course_reserve_entry` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source` varchar(20) NOT NULL DEFAULT 'GroupedWork',
  `sourceId` varchar(40) DEFAULT NULL,
  `courseReserveId` int(11) DEFAULT NULL,
  `dateAdded` int(11) DEFAULT NULL,
  `title` varchar(50) DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `courseReserveId` (`courseReserveId`),
  KEY `source` (`source`,`sourceId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS course_reserves_indexing_log;
CREATE TABLE `course_reserves_indexing_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `startTime` int(11) NOT NULL,
  `endTime` int(11) DEFAULT NULL,
  `lastUpdate` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `numLists` int(11) DEFAULT 0,
  `numAdded` int(11) DEFAULT 0,
  `numDeleted` int(11) DEFAULT 0,
  `numUpdated` int(11) DEFAULT 0,
  `numSkipped` int(11) DEFAULT 0,
  `numErrors` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS course_reserves_indexing_settings;
CREATE TABLE `course_reserves_indexing_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `runFullUpdate` tinyint(1) DEFAULT 1,
  `lastUpdateOfChangedCourseReserves` int(11) DEFAULT 0,
  `lastUpdateOfAllCourseReserves` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS course_reserves_library_map;
CREATE TABLE `course_reserves_library_map` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `settingId` int(11) DEFAULT NULL,
  `value` varchar(50) NOT NULL,
  `translation` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS cron_log;
CREATE TABLE `cron_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of the cron log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the cron run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the cron run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the cron run last updated (to check for stuck processes)',
  `notes` mediumtext DEFAULT NULL COMMENT 'Additional information about the cron run',
  `numErrors` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `startTime` (`startTime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS cron_process_log;
CREATE TABLE `cron_process_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of cron process',
  `cronId` int(11) NOT NULL COMMENT 'The id of the cron run this process ran during',
  `processName` varchar(50) NOT NULL COMMENT 'The name of the process being run',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the process started',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the process last updated (to check for stuck processes)',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the process ended',
  `numErrors` int(11) NOT NULL DEFAULT 0 COMMENT 'The number of errors that occurred during the process',
  `numUpdates` int(11) NOT NULL DEFAULT 0 COMMENT 'The number of updates, additions, etc. that occurred',
  `notes` mediumtext DEFAULT NULL COMMENT 'Additional information about the process',
  `numSkipped` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `cronId` (`cronId`),
  KEY `processName` (`processName`),
  KEY `startTime` (`startTime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS curbside_pickup_settings;
CREATE TABLE `curbside_pickup_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `alwaysAllowPickups` tinyint(1) DEFAULT 0,
  `allowCheckIn` tinyint(1) DEFAULT 1,
  `useNote` tinyint(1) DEFAULT 1,
  `noteLabel` varchar(75) DEFAULT 'Note',
  `noteInstruction` varchar(255) DEFAULT NULL,
  `instructionSchedule` longtext DEFAULT NULL,
  `instructionNewPickup` longtext DEFAULT NULL,
  `contentSuccess` longtext DEFAULT NULL,
  `contentCheckedIn` longtext DEFAULT NULL,
  `timeAllowedBeforeCheckIn` int(11) DEFAULT 30,
  `curbsidePickupInstructions` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `db_update`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `db_update` (
  `update_key` varchar(100) NOT NULL,
  `date_run` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`update_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `db_update` WRITE;
/*!40000 ALTER TABLE `db_update` DISABLE KEYS */;
INSERT INTO `db_update` VALUES ('21_07_00_full_extract_for_koha','2021-05-29 17:13:17'),('2fa_permissions','2022-01-05 21:15:07'),('accelerated_reader','2019-11-18 17:53:46'),('accessibleBrowseCategories','2024-06-25 15:05:22'),('account_linking_setting_by_ptype','2022-12-28 00:13:51'),('account_link_remove_setting_by_ptype','2023-02-13 14:56:53'),('account_profiles_1','2019-01-28 20:59:02'),('account_profiles_2','2019-11-18 17:53:57'),('account_profiles_3','2019-11-18 17:53:57'),('account_profiles_4','2019-11-18 17:53:57'),('account_profiles_5','2019-11-18 17:53:58'),('account_profiles_admin_login_configuration','2021-02-12 22:47:29'),('account_profiles_api_version','2020-11-16 18:34:53'),('account_profiles_domain','2021-04-08 13:50:39'),('account_profiles_ils','2020-01-03 19:47:11'),('account_profiles_oauth','2019-11-18 17:53:58'),('account_profiles_staff_information','2021-03-26 19:02:34'),('account_profiles_workstation_id','2021-04-08 13:50:39'),('account_profile_admin_updates','2025-03-04 15:54:16'),('account_profile_carlx_database_view_version','2024-03-25 16:02:12'),('account_profile_increaseDatabaseNameLength','2021-11-16 04:58:47'),('account_profile_libraryForRecordingPayments','2021-11-16 05:33:08'),('account_profile_oauth_client_secret_length','2022-07-14 23:19:46'),('account_profile_overrideCode','2023-05-09 15:54:09'),('account_summary_hasUpdatedSavedSearches','2022-07-27 17:23:29'),('aci_speedpay_sdk_config','2022-10-06 14:57:01'),('aci_speedpay_settings','2022-08-30 22:03:33'),('acsLog','2011-12-13 16:04:23'),('addContactEmail','2021-10-27 14:04:27'),('addDefaultCatPassword','2021-09-15 04:46:04'),('addGeolocation','2021-09-01 18:32:47'),('addGreenhouseUrl','2021-08-25 20:21:34'),('additionalTranslationTermInfo','2021-08-25 16:40:52'),('additional_administration_locations','2024-10-15 18:40:04'),('additional_index_logging','2021-07-21 22:25:50'),('additional_library_contact_links','2019-01-28 20:58:56'),('additional_locations_for_availability','2019-01-28 20:58:56'),('addLastSeenToOverDriveProducts','2021-10-25 23:25:05'),('addNewSystemBrowseCategories','2021-11-19 22:02:06'),('addNumDismissedToBrowseCategory','2021-11-19 22:02:06'),('addRecommendedForYou','2022-06-07 19:15:33'),('addReleaseChannelToCachedGreenhouseData','2021-09-22 16:15:42'),('addSettingIdToAxis360Scopes','2020-08-10 13:04:25'),('addSiteIdToCachedGreenhouseData','2021-09-09 00:04:51'),('addTablelistWidgetListsLinks','2019-01-28 20:59:01'),('addThemeToCachedGreenhouseData','2021-10-07 22:56:12'),('addUseLineItems_FISWorldPay','2022-04-20 00:46:29'),('addVersionToCachedGreenhouseData','2022-02-27 18:40:30'),('addWeightToDonationValue','2022-06-07 19:15:33'),('add_account_alerts_notification','2022-12-09 21:56:32'),('add_account_alerts_notification_settings','2022-12-29 21:45:01'),('add_account_display_options','2022-10-06 14:57:02'),('add_account_profile_library_settings','2023-02-09 16:36:13'),('add_aci_token_payment','2022-08-30 22:03:33'),('add_active_status_to_materials_requests','2025-03-04 15:54:15'),('add_additional_control_over_format_mapping','2024-06-25 15:05:21'),('add_additional_control_over_format_mapping_part2','2024-06-25 15:05:21'),('add_additional_format_pickup_options','2022-08-29 14:08:08'),('add_additional_info_to_palace_project_availability','2024-10-15 18:40:03'),('add_additional_library_sso_config_options','2022-10-17 14:54:48'),('add_address_information_for_donations','2024-03-25 16:02:14'),('add_administer_selected_browse_category_groups','2023-05-18 21:13:21'),('add_administer_series_permission','2025-03-04 15:54:16'),('add_always_display_renew_count','2024-01-05 09:20:49'),('add_analytics_data_cleared_flag','2024-10-15 18:40:05'),('add_app_scheme_system_variables','2023-01-30 23:46:02'),('add_aspen_lida_build_tracker','2023-01-18 15:01:51'),('add_aspen_lida_general_settings_table','2023-02-22 20:48:38'),('add_aspen_site_scheduled_update','2023-04-27 14:11:42'),('add_barcode_last_name_login_option','2024-01-05 09:20:35'),('add_batchDeletePermissions','2022-10-06 14:57:02'),('add_blank_tempalte_to_grapes_templates','2024-10-15 18:40:03'),('add_book_cover_display_control_in_library_settings','2024-06-25 15:05:20'),('add_branded_app_name','2023-04-12 15:45:12'),('add_branded_app_privacy_policy_contact','2024-03-25 16:02:13'),('add_browseLinkText_to_layout_settings','2021-04-20 23:30:13'),('add_build_tracker_slack_alert','2023-01-18 15:01:51'),('add_bypass_aspen_login_page','2023-01-30 23:46:02'),('add_bypass_patron_login','2023-04-27 14:11:42'),('add_campaign_data_table','2025-03-05 18:44:05'),('add_campaign_milestones_table','2025-03-05 18:44:04'),('add_ce_campaign_milestone_progress_entries','2025-03-05 18:44:04'),('add_ce_campaign_milestone_users_progress','2025-03-05 18:44:04'),('add_checkout_out_of_hold_group_message','2025-03-04 15:54:15'),('add_childRecords_more_details_section','2022-10-06 14:57:01'),('add_child_title_to_record_parents','2022-10-06 14:57:01'),('add_colors_to_web_builder','2021-07-29 03:16:23'),('add_companion_system','2024-01-05 09:20:48'),('add_configuration_for_index_deletions','2024-10-15 18:40:03'),('add_continuedByRecords_more_details_section','2023-02-22 22:48:38'),('add_continuesRecords_more_details_section','2023-02-22 22:48:38'),('add_cookie_consent_theming','2024-01-05 09:20:39'),('add_coverStyle','2022-10-06 14:57:02'),('add_ctaDeepLinkOptions','2022-10-06 14:57:02'),('add_dateUpdated_and_deleted_fields_to_event_and_eventInstance','2025-03-04 15:54:16'),('add_date_of_birth_to_user','2025-03-05 18:44:05'),('add_defaultContent_field','2024-10-15 18:40:04'),('add_default_system_variables','2022-06-14 15:50:20'),('add_deluxe_remittance_id','2023-03-29 08:00:32'),('add_deluxe_security_id','2023-03-29 08:00:32'),('add_device_notification_tokens','2022-08-22 18:57:13'),('add_disallow_third_party_covers','2024-01-05 09:20:35'),('add_displayHoldsOnCheckout','2022-07-21 16:21:03'),('add_displayItemBarcode','2021-08-23 18:45:43'),('add_display_name_to_themes','2023-03-16 21:38:26'),('add_donateToLibrary','2023-03-02 22:15:39'),('add_donationEarmark','2023-03-02 22:15:39'),('add_donation_notification_fields','2023-03-12 18:08:35'),('add_ecommerce_deluxe','2023-03-27 09:11:11'),('add_ecommerce_options','2024-01-05 09:20:37'),('add_ecommerce_payflow_settings','2023-05-05 21:42:54'),('add_ecommerce_square_settings','2024-01-05 09:20:35'),('add_ecommerce_stripe_settings','2024-01-05 09:20:58'),('add_enableSelfRegistration_LiDA','2024-10-15 18:40:04'),('add_enable_branded_app_settings','2024-01-05 09:20:56'),('add_enable_reading_history_to_ptype','2023-01-25 16:28:18'),('add_end_date_to_year_in_review_settings','2025-03-04 15:54:15'),('add_error_to_user_payments','2021-07-26 21:59:49'),('add_excluded_column_to_series_member','2025-03-05 18:44:05'),('add_expo_eas_build_webhook_key','2023-01-18 15:01:51'),('add_expo_eas_submit_webhook_key','2023-01-26 21:52:44'),('add_failed_login_attempt_logging','2024-01-05 09:20:36'),('add_fallback_sso_mapping','2023-02-09 17:11:58'),('add_footerLogoAlt','2021-07-21 22:25:51'),('add_force_reauth_sso','2024-01-05 09:20:36'),('add_forgot_barcode','2024-01-05 09:20:48'),('add_fullWidthTheme','2022-10-06 14:57:02'),('add_greenhouse_id_scheduled_update','2023-05-01 16:41:04'),('add_high_contrast_checkbox','2023-04-04 21:52:01'),('add_holdings_more_details_section','2022-10-06 14:57:01'),('add_hold_not_needed_for_materials_request_statuses','2024-10-15 18:40:04'),('add_hold_options_for_materials_request_statuses','2024-10-15 18:40:04'),('add_hold_out_of_hold_group_message','2025-03-04 15:54:15'),('add_hold_pending_cancellation','2024-01-05 09:20:37'),('add_iiiLoginConfiguration','2023-03-10 17:01:05'),('add_ill_itype','2024-01-05 09:20:40'),('add_ilsNotificationSettingId','2024-10-15 18:40:03'),('add_ils_message_type','2024-10-15 18:40:03'),('add_ils_notification_settings','2024-10-15 18:40:03'),('add_image_to_ebscohost_database','2022-06-24 19:23:34'),('add_image_uploads_to_rewards','2025-03-05 18:44:05'),('add_imgOptions','2022-10-06 14:57:02'),('add_indexes','2019-01-28 20:59:01'),('add_indexes2','2019-01-28 20:59:01'),('add_index_to_ils_volume_info','2023-01-04 16:14:30'),('add_invoiceCloud','2022-12-12 23:54:16'),('add_invoiceCloudSettingId_to_library','2023-01-13 00:56:35'),('add_isssologin_user','2023-02-02 16:09:44'),('add_isSubmitted_build_tracker','2023-01-26 21:52:44'),('add_is_virtual_info_to_items','2023-02-24 23:09:30'),('add_lastUpdated_search','2022-09-01 22:58:26'),('add_last_check_in_community_for_translations','2023-04-03 23:27:50'),('add_ldap_label','2023-02-09 16:36:13'),('add_ldap_to_sso','2023-02-09 16:36:13'),('add_library_links_access','2021-08-23 18:45:43'),('add_library_sso_config_options','2022-09-01 12:58:39'),('add_library_yearly_request_limit_type','2025-03-04 15:54:15'),('add_lida_event_reg_body','2024-03-25 16:02:15'),('add_lida_system_messages_options','2024-01-05 09:20:49'),('add_local_analytics_column_to_user','2024-10-15 18:40:05'),('add_location_circulation_username','2024-10-15 18:40:03'),('add_location_image','2024-01-05 09:20:57'),('add_location_stat_group','2024-10-15 18:40:03'),('add_logoNotification','2022-09-01 22:57:12'),('add_makeAccordion_to_portalRow','2021-04-20 23:30:14'),('add_masquerade_switch_to_ip_addresses','2024-01-05 09:20:36'),('add_materials_requests_limit_by_ptype','2023-02-22 21:22:19'),('add_materials_request_format_mapping','2024-10-15 18:40:04'),('add_maxDaysToFreeze','2021-08-23 18:45:42'),('add_middle_initial_support','2025-03-04 15:54:15'),('add_moveSearchTools','2022-10-06 14:57:02'),('add_notifyCustom','2022-10-06 14:57:02'),('add_number_of_days_to_index_to_event_indexers','2024-01-05 09:20:39'),('add_numInvalidRecords_to_indexing_logs','2022-11-02 02:59:14'),('add_numInvalidRecords_to_sideload_logs','2022-11-02 23:03:57'),('add_oauth_gateway_options','2022-10-06 14:57:02'),('add_oauth_grant_type','2022-11-16 23:21:46'),('add_oauth_logout','2022-11-16 23:21:46'),('add_oauth_mapping','2022-10-06 14:57:02'),('add_oauth_private_keys','2022-11-16 23:21:46'),('add_oauth_to_user','2022-11-16 23:21:46'),('add_openarchives_dateformatting_field','2024-10-15 18:40:05'),('add_opt_out_batch_updates','2023-04-27 14:11:42'),('add_overdrive_settings name','2025-03-04 15:54:14'),('add_parentRecords_more_details_section','2022-10-06 14:57:01'),('add_parent_child_info_to_records','2023-02-22 22:48:38'),('add_permission_for_format_sorting','2024-10-15 18:40:03'),('add_permission_for_testing_checkouts','2024-10-15 18:40:03'),('add_place_holds_for_materials_request_permission','2024-10-15 18:40:04'),('add_place_of_publication_to_grouped_work','2024-03-25 16:02:14'),('add_pushToken_user_notifications','2022-08-22 18:57:13'),('add_records_to_delete_for_sideloads','2021-07-21 22:25:51'),('add_referencecover_groupedwork','2021-04-08 13:50:51'),('add_regular_expression_for_iTypes_to_treat_as_eContent','2025-03-04 15:54:15'),('add_requestingUrl_payment','2023-01-05 02:46:02'),('add_requireLogin_to_basic_page','2021-08-23 18:45:42'),('add_requireLogin_to_portal_page','2021-08-23 18:45:42'),('add_restrict_sso_ip','2024-01-05 09:20:48'),('add_role_permissions_for_community_engagement_dashboard','2025-03-05 18:44:05'),('add_role_permissions_for_community_engagement_module','2025-03-05 18:44:05'),('add_saml_options_to_sso_settings','2023-01-25 16:28:19'),('add_search_source_to_saved_searches','2019-01-28 20:59:02'),('add_search_url_to_saved_searches','2019-11-18 17:53:58'),('add_selected_users_to_browse_category_groups','2023-05-18 21:13:21'),('add_self_check_barcode_styles','2024-01-05 09:20:51'),('add_send_emails_new_materials_request','2023-02-22 21:22:19'),('add_series_module','2025-03-04 15:54:16'),('add_series_search_tables','2025-03-04 15:54:16'),('add_series_settings','2025-03-04 15:54:16'),('add_settings_axis360_exportLog','2020-08-21 19:41:22'),('add_settings_cloud_library_exportLog','2021-01-14 20:15:09'),('add_shared_session_table','2024-01-05 09:20:57'),('add_showBookIcon_to_layout_settings','2021-04-08 13:50:52'),('add_show_edition_covers','2023-05-18 21:13:21'),('add_show_link_on','2024-01-05 09:20:57'),('add_sms_indicator_to_phone','2019-01-28 20:58:56'),('add_sorts_for_browsable_objects','2021-08-23 18:45:42'),('add_sort_info_to_ebscohost_database','2022-06-27 21:34:25'),('add_sp_logout_url','2023-02-02 16:09:44'),('add_sso_account_profiles','2023-02-09 17:11:58'),('add_sso_aspen_lida_module','2023-02-02 16:09:43'),('add_sso_auth_only','2023-02-09 17:11:58'),('add_sso_mapping_constraints','2023-03-10 17:01:05'),('add_sso_permissions','2022-10-06 14:57:02'),('add_sso_saml_student_attributes','2023-05-15 21:35:29'),('add_sso_settings_account_profile','2023-02-09 17:11:58'),('add_sso_table','2022-10-06 14:57:02'),('add_sso_unique_field_match','2023-02-22 22:48:38'),('add_sso_updateAccount','2024-01-05 09:20:57'),('add_sso_user_options','2023-02-01 16:32:50'),('add_staffonly_to_sso_settings','2023-01-25 16:28:19'),('add_staff_ptypes_to_sso_settings','2023-01-25 16:28:19'),('add_staff_ptype_to_sso_settings','2023-01-25 16:28:18'),('add_staff_settings_to_user','2023-02-22 21:22:19'),('add_style_to_year_in_review_settings','2025-03-04 15:54:15'),('add_supporting_company_system_variables','2024-01-05 09:20:38'),('add_switch_for_grapes_editor','2024-10-15 18:40:03'),('add_table_for_user_campaign_data','2025-03-05 18:44:05'),('add_tab_coloring_theme','2023-04-27 14:11:42'),('add_test_user_flag','2025-03-04 15:54:15'),('add_theme_header_image','2022-10-06 14:57:03'),('add_titles_to_user_list_entry','2021-06-01 20:27:56'),('add_title_user_list_entry','2021-06-01 20:27:55'),('add_useHomeLink_to_layout_settings','2021-04-08 13:50:51'),('add_userAlertPreferences','2022-10-06 14:57:02'),('add_user_age_range_to_campaign','2025-03-05 18:44:05'),('add_user_brightness_permission','2024-01-05 09:20:57'),('add_user_ils_messages','2024-10-15 18:40:03'),('add_user_not_interested_index','2022-08-29 14:08:08'),('add_use_alternate_library_card_setting_for_cloud_library','2024-10-15 18:40:03'),('add_variation_index','2025-03-04 15:54:15'),('add_web_builder_basic_page_access','2021-08-23 18:45:42'),('add_web_builder_portal_page_access','2021-08-23 18:45:42'),('add_web_resources_setting_id_to_library_table','2025-03-04 15:54:16'),('add_work_level_rating_index','2021-02-24 23:04:51'),('add_worldpay_settings','2022-04-08 21:00:43'),('administer_host_permissions','2020-10-27 17:06:20'),('administer_replacement_costs_permission','2024-10-15 18:40:04'),('admin_field_locking','2024-01-05 09:20:43'),('aggregate_usage_by_user_agent','2025-03-04 15:54:16'),('allowCancellingAvailableHolds','2024-01-05 09:20:49'),('allowChangingPickupLocationForAvailableHolds','2024-01-05 09:20:49'),('allow_anyone_to_view_documentation','2020-09-29 21:40:15'),('allow_decimal_series_display_orders','2023-03-02 22:15:39'),('allow_filtering_of_linked_users_in_holds','2025-03-04 15:54:16'),('allow_ip_tracking_to_be_disabled','2023-03-29 09:29:06'),('allow_long_scheduled_update_notes','2023-05-04 22:56:50'),('allow_masquerade_mode','2019-01-28 20:58:56'),('allow_masquerade_with_username','2024-06-25 15:05:20'),('allow_multiple_themes_for_libraries','2023-03-15 23:02:54'),('allow_multiple_themes_for_locations','2023-03-16 18:03:48'),('allow_multiple_translatable_blocks_per_object','2025-03-04 15:54:15'),('allow_reading_history_display_in_masquerade_mode','2019-01-28 20:58:56'),('allow_selecting_holds_to_display','2025-03-04 15:54:16'),('allow_selecting_holds_to_export','2025-03-05 18:44:05'),('allow_users_to_change_themes','2023-03-16 22:29:28'),('alpha_browse_setup_2','2019-01-28 20:58:59'),('alpha_browse_setup_3','2019-01-28 20:58:59'),('alpha_browse_setup_4','2019-01-28 20:58:59'),('alpha_browse_setup_5','2019-01-28 20:58:59'),('alpha_browse_setup_6','2019-01-28 20:59:00'),('alpha_browse_setup_7','2019-01-28 20:59:00'),('alpha_browse_setup_8','2019-01-28 20:59:00'),('alpha_browse_setup_9','2019-01-28 20:59:01'),('alternate_card_varchar_limit','2025-03-04 15:54:15'),('alternate_grouping_category','2024-06-25 15:05:20'),('alternate_library_card_form_message','2024-10-15 18:40:03'),('alter_summonId_length','2024-10-15 18:40:03'),('alwaysFlagNewTitlesInSearchResults','2022-08-04 04:14:02'),('always_show_search_results_Main_details','2019-01-28 20:58:56'),('amazon_ses','2021-05-27 16:14:10'),('amazon_ses_secret_length','2022-01-05 16:27:08'),('analytics','2019-01-28 20:59:01'),('analytics_1','2019-01-28 20:59:01'),('analytics_2','2019-01-28 20:59:01'),('analytics_3','2019-01-28 20:59:01'),('analytics_4','2019-01-28 20:59:01'),('analytics_5','2019-01-28 20:59:02'),('analytics_6','2019-01-28 20:59:02'),('analytics_7','2019-01-28 20:59:02'),('analytics_8','2019-01-28 20:59:02'),('api_usage_stats','2021-03-19 14:13:57'),('appReleaseChannel','2021-09-22 16:15:41'),('archivesRole','2019-01-28 20:58:59'),('archive_collection_default_view_mode','2019-01-28 20:58:56'),('archive_filtering','2019-01-28 20:58:56'),('archive_more_details_customization','2019-01-28 20:58:56'),('archive_object_filtering','2019-01-28 20:58:56'),('archive_private_collections','2019-01-28 20:59:02'),('archive_requests','2019-01-28 20:59:02'),('archive_subjects','2019-01-28 20:59:02'),('ar_update_frequency','2022-04-08 21:00:43'),('aspenSite_activeTicketFeed','2022-03-31 21:48:47'),('aspenSite_activeTicketFeed2','2022-04-01 18:27:04'),('aspen_lida_permissions_update','2024-01-05 09:20:37'),('aspen_lida_self_check_settings','2024-01-05 09:20:37'),('aspen_lida_settings','2022-02-01 15:31:47'),('aspen_lida_settings_2','2022-02-01 15:31:48'),('aspen_lida_settings_3','2022-02-01 15:31:48'),('aspen_release_test_release_date','2022-11-18 15:06:55'),('aspen_sites','2021-07-30 02:19:22'),('aspen_site_internal_name','2021-08-23 18:45:42'),('aspen_site_isOnline','2022-08-04 04:14:02'),('aspen_site_lastOfflineTime','2022-07-21 16:21:03'),('aspen_site_lastOfflineTracking','2022-08-04 04:14:02'),('aspen_site_monitored','2023-01-31 15:40:34'),('aspen_site_timezone','2022-06-08 13:25:56'),('aspen_usage','2019-11-18 17:53:58'),('aspen_usage_add_sessions','2020-12-17 20:24:00'),('aspen_usage_blocked_requests','2020-06-10 12:22:32'),('aspen_usage_ebscohost','2022-06-24 13:38:50'),('aspen_usage_ebsco_eds','2020-07-21 13:33:42'),('aspen_usage_events','2020-04-30 14:33:56'),('aspen_usage_instance','2020-12-15 19:27:15'),('aspen_usage_remove_slow_pages','2020-12-17 20:05:20'),('aspen_usage_summon','2024-03-25 16:02:11'),('aspen_usage_websites','2019-11-18 17:53:58'),('assabet_events','2024-06-25 15:05:20'),('assabet_settings','2024-06-25 15:05:20'),('assign_novelist_settings_to_libraries','2023-03-10 22:28:15'),('authentication_profiles','2019-01-28 20:59:02'),('authorities','2019-11-18 17:53:46'),('author_authorities','2021-01-27 20:56:59'),('author_authorities_index','2022-12-07 16:40:30'),('author_authorities_normalized_values','2021-02-01 15:27:43'),('author_enrichment','2019-01-28 20:58:59'),('automatic_update_settings','2024-01-05 09:20:34'),('autoPickUserHomeLocation','2024-06-25 15:05:22'),('availability_toggle_customization','2019-01-28 20:58:56'),('axis360AddSettings','2021-02-12 01:49:54'),('axis360Title','2020-08-08 20:50:11'),('axis360_add_response_info_to_availability','2020-08-20 15:56:32'),('axis360_add_setting_to_availability','2020-08-08 21:00:00'),('axis360_availability_indexes','2021-05-12 22:50:28'),('axis360_availability_remove_unused_fields','2020-08-21 19:41:21'),('axis360_availability_update_for_new_method','2020-09-24 02:26:32'),('axis360_exportLog','2020-08-07 14:36:05'),('axis360_exportLog_num_skipped','2020-08-21 20:35:41'),('axis360_restrict_scopes_by_audience','2024-04-11 17:23:35'),('axis360_stats_index','2021-02-06 21:31:45'),('axis_360_options','2022-07-03 20:04:14'),('background_process','2025-03-04 15:54:15'),('barcode_generator_report_permissions','2024-10-15 18:40:04'),('basic_page_allow_access_by_home_location','2022-10-06 14:57:01'),('bookcover_info','2019-11-18 17:53:58'),('book_store','2019-01-28 20:59:01'),('book_store_1','2019-01-28 20:59:01'),('boost_disabling','2019-01-28 20:59:01'),('branded_app_api_keys','2025-03-04 15:54:16'),('browsable_course_reserves','2021-11-30 16:30:45'),('browseCategoryDismissal','2021-11-18 04:45:20'),('browse_categories','2019-01-28 20:59:02'),('browse_categories_add_startDate_endDate','2021-05-26 16:19:08'),('browse_categories_lists','2019-01-28 20:59:02'),('browse_categories_search_term_and_stats','2019-01-28 20:59:02'),('browse_categories_search_term_length','2019-01-28 20:59:02'),('browse_category_default_view_mode','2019-01-28 20:58:56'),('browse_category_groups','2020-01-08 18:34:34'),('browse_category_library_updates','2020-08-31 17:13:32'),('browse_category_ratings_mode','2019-01-28 20:58:56'),('browse_category_source','2020-06-10 12:22:31'),('browse_category_times_shown','2022-03-20 19:04:15'),('bypass_event_pages','2024-01-05 09:20:38'),('cached_values_engine','2022-02-21 19:09:04'),('cached_value_case_sensitive','2020-03-05 22:53:47'),('cacheGreenhouseData','2021-09-08 23:42:15'),('callNumberPrestamp2','2024-01-05 09:20:48'),('card_renewal_options','2024-01-05 09:20:56'),('carlx_tos','2024-10-15 18:40:03'),('catalogingRole','2019-01-28 20:58:59'),('catalogStatus','2022-03-20 19:04:14'),('changeBrowseCategoryIdType','2021-11-30 16:30:44'),('change_default_formatSource_KohaOnly','2022-08-29 14:08:08'),('change_ecommerceTerms_to_mediumText','2024-01-05 09:20:39'),('change_greenhouse_url','2023-01-09 23:09:10'),('change_share_it_column_types','2025-03-04 15:54:15'),('change_to_innodb','2019-03-05 18:07:51'),('checkoutFormatLength','2021-11-16 04:58:47'),('checkoutIsILL','2024-01-05 09:20:40'),('check_titles_in_user_list_entries','2021-08-23 18:45:43'),('claim_authorship_requests','2019-01-28 20:59:02'),('cleanupApiUsage_func','2022-10-06 14:57:02'),('cleanup_invalid_reading_history_entries','2021-01-14 16:33:48'),('clean_up_invalid_instances','2022-09-11 03:22:11'),('clear_analytics','2019-01-28 20:59:02'),('clear_default_covers_22_06_04','2022-06-14 21:28:49'),('closed_captioning_in_records','2022-06-28 23:13:37'),('cloud_library_add_scope_setting_id','2021-01-14 16:34:36'),('cloud_library_add_settings','2021-02-12 01:49:54'),('cloud_library_add_setting_to_availability','2021-01-14 20:16:54'),('cloud_library_availability','2019-11-18 17:53:55'),('cloud_library_availability_changes','2024-03-25 16:02:13'),('cloud_library_cleanup_availability_with_settings','2021-05-03 20:49:19'),('cloud_library_exportLog','2019-11-18 17:53:55'),('cloud_library_exportTable','2019-11-18 17:53:55'),('cloud_library_increase_allowable_copies','2020-04-30 14:33:55'),('cloud_library_module_add_log','2020-03-31 18:45:02'),('cloud_library_multiple_scopes','2021-05-18 13:12:51'),('cloud_library_restrict_scopes_by_audience','2024-04-11 17:23:35'),('cloud_library_scoping','2019-11-18 17:53:55'),('cloud_library_settings','2019-11-18 17:53:55'),('cloud_library_target_audience','2024-03-25 16:02:13'),('cloud_library_usage_add_instance','2020-12-21 17:24:10'),('coce_settings','2020-08-24 14:05:19'),('collapse_facets','2019-01-28 20:58:55'),('collection_report_permissions','2024-01-05 09:20:36'),('collection_spotlights_carousel_style','2020-10-26 18:02:00'),('combined_results','2019-01-28 20:58:56'),('communico_events','2023-03-15 23:02:46'),('communico_full_index','2024-03-26 16:08:04'),('communico_settings','2023-03-15 23:02:46'),('community_builder_module','2025-03-05 18:44:04'),('community_engagement_roles','2025-03-05 18:44:05'),('compress_hoopla_fields','2021-07-12 16:44:23'),('compress_novelist_fields','2021-07-11 21:04:57'),('compress_overdrive_fields','2021-07-12 16:44:29'),('comprise_link_to_library','2021-07-06 15:52:36'),('comprise_settings','2021-06-27 17:52:03'),('configurable_solr_timeouts','2022-02-21 19:09:05'),('contentcafe_settings','2020-01-03 19:47:26'),('contentEditor','2019-01-28 20:58:59'),('content_cafe_disable','2022-12-07 16:40:30'),('convertOldEContent','2011-11-06 22:58:31'),('convert_to_format_status_maps','2019-11-18 17:53:46'),('cookie_policy_html','2024-01-05 09:20:36'),('cookie_storage_consent','2024-01-05 09:20:36'),('copyVDXFormsToLocalIllForms','2025-03-04 15:54:15'),('course_reserves_indexing','2021-11-30 16:30:45'),('course_reserves_library_mappings','2021-12-31 21:05:12'),('course_reserves_module','2021-11-30 16:30:45'),('course_reserves_permissions','2021-11-30 16:30:45'),('course_reserves_unique_index','2022-02-21 19:09:04'),('coverArt_suppress','2019-01-28 20:58:58'),('createAxis360Module','2020-08-07 14:36:04'),('createAxis360SettingsAndScopes','2020-08-07 14:36:04'),('createEbscoModules','2020-07-14 14:14:44'),('createMaterialRequestStats','2022-05-26 19:43:21'),('createPalaceProjectModule','2024-01-05 09:20:52'),('createPalaceProjectSettingsAndScopes','2024-01-05 09:20:52'),('createPermissionsforEBSCOhost','2022-05-26 19:42:54'),('createSearchInterface_libraries_locations','2021-07-14 20:52:48'),('createSettingsForEbscoEDS','2020-07-14 14:14:45'),('createSettingsforEBSCOhost','2022-05-26 19:42:54'),('createSettingsForSummon','2024-03-25 16:02:11'),('createSummonModule','2024-03-25 16:02:11'),('create_2024_default_year_in_review_settings','2025-03-04 15:54:15'),('create_campaigns','2025-03-05 18:44:04'),('create_campaign_library_access','2025-03-05 18:44:04'),('create_campaign_patron_type_access','2025-03-05 18:44:04'),('create_cloud_library_module','2019-11-18 17:53:55'),('create_community_content_url','2023-03-31 09:54:49'),('create_custom_web_resource_page_table','2025-03-04 15:54:16'),('create_default_format_sorting','2024-10-15 18:40:04'),('create_events_module','2020-04-30 14:33:55'),('create_field_encryption_file','2021-02-12 02:31:03'),('create_format_sorting_tables','2024-10-15 18:40:04'),('create_hoopla_module','2019-11-18 17:53:49'),('create_ils_modules','2019-11-18 17:53:47'),('create_libkey_permissions','2025-03-04 15:54:15'),('create_libkey_settings_table','2025-03-04 15:54:15'),('create_library_web_builder_custom_web_resource_page_table','2025-03-04 15:54:16'),('create_lida_notifications','2022-10-06 14:57:02'),('create_milestones','2025-03-05 18:44:04'),('create_nyt_update_log','2021-03-29 20:48:56'),('create_open_archives_module','2019-11-18 17:53:55'),('create_overdrive_library_settings','2025-03-04 15:54:15'),('create_overdrive_module','2019-11-18 17:53:48'),('create_overdrive_scopes','2019-12-18 18:48:34'),('create_plural_grouped_work_facets','2021-07-06 15:52:37'),('create_polaris_module','2021-03-05 20:16:16'),('create_rbdigital_module','2019-11-18 17:53:49'),('create_reward_table','2025-03-05 18:44:04'),('create_system_variables_table','2020-04-30 14:33:56'),('create_user_campaign_table','2025-03-05 18:44:04'),('create_user_completed_milestones_table','2025-03-05 18:44:04'),('create_user_notifications','2022-08-22 18:57:13'),('create_user_notification_tokens','2022-08-22 18:57:13'),('create_web_builder_custom_resource_page_access_table','2025-03-04 15:54:16'),('create_web_builder_custom_resource_page_audience_table','2025-03-04 15:54:16'),('create_web_builder_custom_resource_page_category_table','2025-03-04 15:54:16'),('create_web_indexer_module','2019-11-18 17:53:56'),('create_web_resources_settings_table','2025-03-04 15:54:16'),('create_web_resources_to_index_table','2025-03-04 15:54:16'),('cronLog','2019-01-28 20:59:01'),('cron_log_errors','2020-04-07 12:24:03'),('cron_process_skips','2020-04-07 12:24:04'),('curbside_pickup_settings','2021-12-17 23:34:16'),('curbside_pickup_settings_pt2','2021-12-17 23:34:16'),('curbside_pickup_settings_pt3','2021-12-17 23:34:16'),('curbside_pickup_settings_pt4','2021-12-27 20:32:54'),('currencyCode','2020-11-02 16:19:32'),('custom_facets','2024-01-05 09:20:37'),('custom_form_includeIntroductoryTextInEmail','2022-11-08 15:28:53'),('custom_marc_fields_to_index_as_keyword','2022-07-07 15:23:41'),('custom_web_resource_pages_permissions','2025-03-04 15:54:16'),('custom_web_resource_pages_roles_for_permissions','2025-03-04 15:54:16'),('debug_info_update','2024-10-15 18:40:04'),('defaultAvailabilityToggle','2020-03-05 22:53:47'),('defaultGroupedWorkDisplaySettings','2020-05-14 19:25:08'),('defaultSelfRegistrationEmailTemplate','2024-01-05 09:20:50'),('default_library','2019-01-28 20:58:56'),('default_list_indexing','2020-09-09 22:28:45'),('default_records_to_include_weight','2023-04-07 23:29:22'),('delete_null_translations','2024-01-05 09:20:48'),('detailed_hold_notice_configuration','2019-01-28 20:58:56'),('development_components_to_epics','2022-11-23 23:15:39'),('development_components_to_tasks','2022-11-23 23:15:39'),('development_epics','2022-11-21 22:40:54'),('development_epics_to_tasks','2022-11-22 23:46:45'),('development_partners_to_epics','2022-11-22 23:46:45'),('development_partners_to_tasks','2022-11-22 23:46:45'),('development_sprints','2022-11-18 19:41:22'),('development_sprints_to_tasks','2022-11-22 23:46:45'),('development_tasks_take_2','2022-11-19 00:00:43'),('development_tickets_to_components','2022-11-23 23:55:49'),('development_tickets_to_tasks','2022-11-22 23:46:44'),('disable_auto_correction_of_searches','2019-01-28 20:58:56'),('disable_circulation_actions','2024-01-05 09:20:52'),('disable_hoopla_module_auto_restart','2019-12-03 14:45:39'),('disable_ip_spammy_control','2025-03-04 15:54:15'),('disable_linking_changes','2022-12-01 02:32:10'),('dismissPlacardButtonIcon','2021-11-03 23:21:36'),('dismissPlacardButtonLocation','2021-11-03 23:21:36'),('displayMaterialsRequestToPublic','2021-11-03 15:59:06'),('display_explore_more_bar','2024-10-15 18:40:03'),('display_explore_more_bar_additional_options','2024-10-15 18:40:03'),('display_explore_more_bar_in_ebsco_host_search','2024-10-15 18:40:03'),('display_list_author_control','2024-01-05 09:20:51'),('display_pika_logo','2019-01-28 20:58:56'),('donations_addLocationSettings','2021-11-19 22:02:06'),('donations_createDonationsDedicateType','2021-11-19 22:02:06'),('donations_createDonationsDedicateTypeB','2021-11-30 16:30:44'),('donations_createDonationsEarmarks','2021-11-19 22:02:06'),('donations_createDonationsEarmarksA','2021-11-30 16:30:44'),('donations_createDonationsEarmarksB','2021-11-30 16:30:44'),('donations_createDonationsFormFields','2021-11-19 22:02:06'),('donations_createDonationsFormFieldsA','2021-11-30 16:30:44'),('donations_createDonationsFormFieldsB','2021-11-30 16:30:44'),('donations_createDonationsValue','2021-11-19 22:02:06'),('donations_createDonationsValueA','2021-11-30 16:30:44'),('donations_createDonationsValueB','2021-11-30 16:30:44'),('donations_createInitialTable','2021-11-19 22:02:06'),('donations_createInitialTableA','2021-11-30 16:30:44'),('donations_createInitialTableB','2021-11-30 16:30:44'),('donations_disambiguate_library_and_location','2024-01-05 09:20:48'),('donations_donations_createDonationsDedicateTypeA','2021-11-30 16:30:44'),('donations_report_permissions','2021-12-09 02:49:14'),('donations_settings','2021-11-19 22:02:06'),('donation_formFields_uniqueKey','2021-12-01 02:29:06'),('dpla_api_settings','2020-01-09 01:49:33'),('dpla_integration','2019-01-28 20:58:56'),('drop_columns_from_user_table','2024-10-15 18:40:05'),('drop_date_of_birth_from_user_table','2025-03-05 18:44:05'),('drop_securityId_cp','2023-04-21 15:52:07'),('drop_snappayToken_column','2024-10-15 18:40:05'),('drop_sso_mapping_constraints','2023-03-10 17:01:05'),('drop_user_staff_settings','2023-02-22 21:22:19'),('ebscohost_database_logo_default','2022-06-27 22:46:18'),('ebscohost_facets','2022-06-22 15:52:20'),('ebscohost_ip_addresses','2022-06-24 13:38:50'),('ebscohost_record_usage','2022-06-24 13:38:50'),('ebscohost_remove_authType','2022-06-27 21:34:26'),('ebscohost_search_settings','2022-06-16 21:12:29'),('ebsco_eds_increase_id_length','2020-07-22 14:05:26'),('ebsco_eds_record_usage','2020-07-21 13:33:42'),('ebsco_eds_research_starters','2020-07-27 17:16:30'),('ebsco_eds_usage_add_instance','2020-12-21 22:44:24'),('ebsco_passwords_are_stored_as_hash','2025-03-04 15:54:15'),('ecommerce_report_permissions','2021-08-23 18:45:52'),('ecommerce_report_permissions_all_vs_home','2024-01-05 09:20:48'),('eContentCheckout','2011-11-10 23:57:56'),('eContentCheckout_1','2011-12-13 16:04:03'),('eContentHistory','2011-11-15 17:56:44'),('eContentHolds','2011-11-10 22:39:20'),('eContentItem_1','2011-12-04 22:13:19'),('eContentRating','2011-11-16 21:53:43'),('eContentRecord_1','2011-12-01 21:43:54'),('eContentRecord_2','2012-01-11 20:06:48'),('eContentWishList','2011-12-08 20:29:48'),('econtent_attach','2011-12-30 19:12:22'),('econtent_locations_to_include','2019-01-28 20:56:58'),('econtent_marc_import','2011-12-15 22:48:22'),('editorial_review','2019-01-28 20:58:58'),('editorial_review_1','2019-01-28 20:58:58'),('editorial_review_2','2019-01-28 20:58:58'),('edit_placard_permissions','2021-09-08 17:10:17'),('emailTemplates','2024-01-05 09:20:50'),('email_stats','2024-01-05 09:20:54'),('enableAppAccess','2021-09-01 18:32:47'),('enable_add_to_reading_history','2025-03-04 15:54:15'),('enable_archive','2019-01-28 20:58:56'),('encrypt_user_table','2021-02-12 22:47:45'),('error_table','2019-11-18 17:53:59'),('error_table_agent','2019-11-18 17:53:59'),('events_add_settings','2021-02-12 01:49:54'),('events_facets','2024-01-05 09:20:39'),('events_facets_default','2024-01-05 09:20:40'),('events_facet_settingsId','2024-01-05 09:20:40'),('events_indexing_log','2020-04-30 14:33:55'),('events_in_lists','2024-01-05 09:20:37'),('events_module_log_checks','2020-11-09 19:22:45'),('events_spotlights','2020-07-22 12:50:01'),('events_start_date_facet','2024-01-05 09:20:40'),('event_calendar_display_settings','2025-03-04 15:54:16'),('event_library_mapping','2023-05-18 21:13:21'),('event_library_mapping_values','2023-05-18 21:13:21'),('event_record_usage','2020-04-30 14:33:56'),('event_registration_modal','2024-01-05 09:20:38'),('evergreen_extract_number_of_threads','2024-01-05 09:20:54'),('evergreen_extract_options','2024-01-05 09:20:54'),('evergreen_folio_modules','2022-02-21 19:09:05'),('evolve_module','2022-06-22 15:52:20'),('expiration_message','2019-01-28 20:58:56'),('explore_more_configuration','2019-01-28 20:58:56'),('exportingUrlDescription','2024-01-05 09:20:48'),('extend_bookcover_info_source','2023-02-28 00:42:33'),('extend_grouped_work_id_not_interested','2023-02-28 00:42:33'),('extend_placard_link','2021-05-12 22:50:28'),('extend_symphonyPaymentType','2024-01-05 09:20:51'),('extend_web_form_label','2023-03-30 07:32:08'),('externalLinkTracking','2019-01-28 20:58:58'),('externalRequestsLog','2021-11-03 23:21:36'),('externalRequestsLogMethod','2021-11-09 18:04:51'),('externalRequestsRequestMethodLength','2023-01-19 17:23:41'),('external_materials_request','2019-01-28 20:58:56'),('facetLabel_length','2020-01-09 01:49:33'),('facets_add_multi_select','2019-11-18 17:53:42'),('facets_add_translation','2019-11-18 17:53:43'),('facets_locking','2019-11-18 17:53:42'),('facets_remove_author_results','2019-11-18 17:53:42'),('facet_counts_to_show','2022-06-14 15:52:06'),('facet_grouping_updates','2019-01-28 20:58:55'),('facet_setting_ids','2024-01-05 09:20:47'),('failed_login_index','2024-01-05 09:20:52'),('field_encryption','2021-02-12 01:49:54'),('fileUploadsThumb','2021-07-16 16:03:44'),('file_uploads_table','2020-04-30 14:33:56'),('filter_books_from_summon_results','2025-03-04 15:54:16'),('fix_dates_in_item_details','2021-07-17 23:37:56'),('fix_duplicate_variations','2025-03-04 15:54:15'),('fix_ils_record_indexes','2021-07-21 22:25:50'),('fix_ils_volume_indexes','2021-08-23 18:45:42'),('fix_incorrect_available_memory','2022-07-09 21:43:07'),('fix_list_entries_for_grouped_works_with_language','2022-06-14 15:51:04'),('fix_requiredModule_website_facets','2024-01-05 09:20:48'),('fix_sideload_permissions','2022-03-29 17:55:59'),('fix_sierra_module_background_process','2020-11-28 15:55:27'),('fix_user_email','2024-01-05 09:20:43'),('footerText','2022-04-27 15:24:28'),('forceReindexForAxis360_2302','2023-02-02 16:09:43'),('force_overdrive_full_update','2024-01-05 09:20:55'),('force_processing_empty_works','2023-05-07 15:39:54'),('force_regrouping_all_works_24_05','2024-06-25 15:05:20'),('force_reindex_of_old_style_palace_project_identifiers','2024-10-15 18:40:03'),('force_reindex_of_records_with_no_language','2022-09-10 21:14:16'),('force_reindex_of_records_with_pipe_language','2022-06-14 15:50:20'),('force_reindex_of_records_with_spaces','2022-09-01 23:15:02'),('force_reload_of_cloud_library_21_08','2021-06-27 17:52:03'),('force_reload_of_hoopla_21_08','2021-07-06 15:52:37'),('force_reload_of_hoopla_22_10','2022-10-06 14:57:03'),('force_reload_of_overdrive_21_08','2021-07-06 15:52:37'),('force_website_reindex_22_05','2022-04-28 23:20:29'),('format_holdType','2019-11-18 17:53:46'),('format_mustPickupAtHoldingBranch','2021-05-03 20:49:18'),('format_status_in_library_use_only','2020-03-05 22:53:47'),('format_status_maps','2019-11-18 17:53:46'),('format_status_suppression','2019-11-18 17:53:46'),('full_record_view_configuration_options','2019-01-28 20:58:56'),('full_text_limiter','2024-06-25 15:05:22'),('genealogy','2019-01-28 20:58:58'),('genealogy_1','2019-01-28 20:58:58'),('genealogy_lot_length','2020-10-30 19:03:30'),('genealogy_marriage_date_update','2020-12-21 23:11:40'),('genealogy_module','2020-07-23 17:45:42'),('genealogy_nashville_1','2019-01-28 20:58:58'),('genealogy_obituary_date_update','2020-08-18 01:30:03'),('genealogy_person_date_update','2020-12-21 23:11:40'),('goodreads_library_contact_link','2019-01-28 20:58:56'),('google_analytics_version','2020-11-07 18:32:16'),('google_api_settings','2020-01-08 18:34:34'),('google_bucket','2023-03-10 17:01:05'),('google_more_settings','2020-02-09 22:24:59'),('google_remove_google_translate','2020-11-07 18:32:16'),('grapes_js_web_builder_roles','2024-10-15 18:40:03'),('grapes_js_web_builder_roles_for_permissions','2024-10-15 18:40:03'),('grapes_page_web_builder_scope_by_library','2024-10-15 18:40:03'),('grapes_web_builder','2024-10-15 18:40:03'),('greenhouseMonitoring','2021-10-28 01:50:56'),('greenhouseMonitoring2','2021-10-29 22:56:24'),('greenhouseSlackIntegration','2021-10-29 22:27:33'),('greenhouseSlackIntegration2','2024-10-15 18:40:03'),('greenhouse_add_accessToken','2022-08-22 18:57:13'),('greenhouse_add_ils','2021-12-16 23:09:22'),('greenhouse_appAccess','2021-08-25 16:40:51'),('greenhouse_contact_and_go_live','2022-03-04 00:24:28'),('greenhouse_cpu_and_memory_monitoring','2022-07-04 17:35:06'),('greenhouse_rt_auth_token','2022-11-29 23:38:45'),('greenhouse_rt_base_url','2022-11-30 21:22:54'),('greenhouse_setting_apiKeys','2021-11-30 22:37:05'),('greenhouse_wait_time_monitoring','2022-07-14 23:19:46'),('grouped_works','2019-01-28 20:58:56'),('grouped_works_1','2019-01-28 20:58:56'),('grouped_works_2','2019-01-28 20:58:56'),('grouped_works_partial_updates','2019-01-28 20:58:57'),('grouped_works_primary_identifiers','2019-01-28 20:58:56'),('grouped_works_primary_identifiers_1','2019-01-28 20:58:56'),('grouped_works_remove_split_titles','2019-01-28 20:58:56'),('grouped_work_alternate_titles','2020-02-09 22:24:42'),('grouped_work_debugging','2024-06-25 15:05:21'),('grouped_work_display_856_as_access_online','2022-10-06 14:57:01'),('grouped_work_display_info','2020-05-16 13:20:05'),('grouped_work_display_settings','2019-12-18 18:55:36'),('grouped_work_display_showItemDueDates','2021-06-10 16:30:21'),('grouped_work_display_title_author','2020-05-15 15:58:23'),('grouped_work_duplicate_identifiers','2019-01-28 20:58:57'),('grouped_work_engine','2019-01-28 20:58:57'),('grouped_work_evoke','2019-01-28 20:58:57'),('grouped_work_identifiers_ref_indexing','2019-01-28 20:58:57'),('grouped_work_index_cleanup','2019-01-28 20:58:57'),('grouped_work_index_date_updated','2019-01-28 20:58:57'),('grouped_work_language','2022-05-26 19:42:54'),('grouped_work_merging','2019-01-28 20:58:57'),('grouped_work_primary_identifiers_hoopla','2019-01-28 20:58:57'),('grouped_work_primary_identifier_length','2024-01-05 09:20:53'),('grouped_work_primary_identifier_types','2019-01-28 20:58:57'),('grouped_work_title_length','2021-02-25 14:33:53'),('header_text','2019-01-28 20:58:56'),('hide_series','2024-03-25 16:02:14'),('hide_subjects_drop_date_added','2024-03-25 16:02:14'),('hide_subject_facets','2022-09-01 12:55:29'),('hide_subject_facet_permission','2022-09-01 12:55:29'),('holdIsILL','2022-06-01 19:33:16'),('hold_request_confirmations','2021-07-06 15:52:37'),('holiday','2019-01-28 20:59:01'),('holiday_1','2019-01-28 20:59:01'),('hoopla_add_settings','2019-11-18 17:53:48'),('hoopla_add_settings_2','2021-05-06 03:22:55'),('hoopla_add_setting_to_scope','2020-12-14 17:03:19'),('hoopla_bingepass','2022-07-26 17:12:46'),('hoopla_exportLog','2019-01-28 20:58:57'),('hoopla_exportLog_skips','2019-11-18 17:53:48'),('hoopla_exportLog_update','2019-11-18 17:53:48'),('hoopla_exportTables','2019-01-28 20:58:57'),('hoopla_export_include_raw_data','2019-11-18 17:53:48'),('hoopla_filter_records_from_other_vendors','2019-11-18 17:53:49'),('hoopla_genres_to_exclude','2022-10-06 14:57:01'),('hoopla_index_by_day','2022-11-09 00:15:35'),('hoopla_integration','2019-01-28 20:58:56'),('hoopla_library_options','2019-01-28 20:58:56'),('hoopla_library_options_remove','2019-01-28 20:58:56'),('hoopla_module_add_log','2020-03-31 18:45:02'),('hoopla_regroup_all_records','2021-09-15 23:32:28'),('hoopla_restrict_scopes_by_audience','2024-04-11 17:23:35'),('hoopla_scoping','2019-11-18 17:53:48'),('hoopla_title_exclusion_updates','2022-04-06 06:54:10'),('hoopla_usage_add_instance','2020-12-21 17:27:45'),('horizontal_search_bar','2019-01-28 20:58:56'),('host_information','2020-10-27 17:06:37'),('hours_and_locations_control','2019-01-28 20:58:55'),('htmlForMarkdown','2021-03-01 14:37:04'),('ill_link','2019-01-28 20:58:56'),('ils_code_records_owned_length','2019-01-28 20:58:56'),('ils_exportLog','2019-11-18 17:53:45'),('ils_exportLog_num_regroups','2021-02-24 23:42:44'),('ils_exportLog_skips','2019-11-18 17:53:45'),('ils_hold_summary','2019-01-28 20:58:57'),('ils_log_add_records_with_invalid_marc','2022-03-20 19:04:15'),('ils_marc_checksums','2019-01-28 20:59:02'),('ils_marc_checksum_first_detected','2019-01-28 20:59:02'),('ils_marc_checksum_first_detected_signed','2019-01-28 20:59:02'),('ils_marc_checksum_source','2019-01-28 20:59:02'),('ils_record_suppression','2022-06-09 18:26:20'),('ils_usage_add_instance','2020-12-21 17:22:16'),('includePersonalAndCorporateNamesInTopics','2023-02-24 23:09:30'),('include_children_kids','2024-04-11 17:23:35'),('increaseExternalRequestResponseField','2021-11-30 16:30:44'),('increaseFullMarcExportRecordIdThreshold','2021-11-30 16:30:45'),('increaseGreenhouseDataNameLength','2021-09-09 00:16:03'),('increaseLengthOfShowInMainDetails','2021-05-03 20:49:18'),('increaseSymphonyPaymentTypeAndPolicyLengths','2021-10-07 22:56:12'),('increase_checkout_due_date','2021-08-23 18:45:42'),('increase_course_reserves_instructor','2024-03-25 16:02:14'),('increase_course_reserves_source_length','2022-06-22 15:52:20'),('increase_event_field_lengths','2025-03-04 15:54:16'),('increase_format_length_for_circulation_cache','2024-10-15 18:40:03'),('increase_grouped_work_length_for_language','2022-05-26 19:43:21'),('increase_ill_link_size','2023-03-02 22:15:39'),('increase_ilsID_size_for_ils_marc_checksums','2019-01-28 20:58:57'),('increase_length_of_new_materials_request_column','2023-03-17 19:55:10'),('increase_length_of_shelf_locations_to_exclude','2023-03-20 20:44:03'),('increase_login_form_labels','2019-01-28 20:58:56'),('increase_nonHoldableITypes','2021-10-07 22:56:11'),('increase_patron_type_length','2024-06-25 15:05:21'),('increase_scoping_field_lengths','2021-08-23 18:45:42'),('increase_scoping_field_lengths_2','2024-01-05 09:20:49'),('increase_search_url_size','2019-11-18 17:53:58'),('increase_search_url_size_round_2','2020-04-30 14:33:56'),('increase_showInSearchResultsMainDetails_length','2021-05-29 16:17:45'),('increase_sublocation_to_include','2023-01-18 15:01:51'),('increase_translation_map_value_length','2022-03-20 19:04:15'),('increase_volumeId_length','2021-07-16 16:03:44'),('index856Links','2023-02-26 18:21:48'),('indexAndSearchVersionVariables','2022-05-26 19:42:54'),('indexed_information_length','2021-07-06 15:52:37'),('indexed_information_publisher_length','2021-07-11 21:04:56'),('indexing_exclude_locations','2020-07-08 14:56:04'),('indexing_includeLocationNameInDetailedLocation','2020-09-14 14:09:06'),('indexing_lastUpdateOfAuthorities','2021-02-01 15:27:43'),('indexing_module_add_log','2020-03-31 18:45:02'),('indexing_module_add_settings','2021-02-12 01:49:54'),('indexing_module_add_settings2','2021-03-05 20:16:16'),('indexing_profile','2019-01-28 20:58:57'),('indexing_profiles_add_due_date_for_Koha','2021-05-18 13:12:51'),('indexing_profiles_add_notes_subfield','2021-05-18 13:12:51'),('indexing_profiles_date_created_polaris','2021-05-26 16:19:08'),('indexing_profile_add_check_sierra_mat_type_for_format','2023-05-30 19:51:06'),('indexing_profile_add_continuous_update_fields','2019-11-18 17:53:45'),('indexing_profile_audienceSubfield','2020-08-07 14:36:04'),('indexing_profile_bibCallNumberFields','2024-10-15 18:40:03'),('indexing_profile_catalog_driver','2019-01-28 20:58:57'),('indexing_profile_collection','2019-01-28 20:58:57'),('indexing_profile_collectionsToSuppress','2019-01-28 20:58:57'),('indexing_profile_determineAudienceBy','2020-06-26 21:00:14'),('indexing_profile_doAutomaticEcontentSuppression','2019-01-28 20:58:57'),('indexing_profile_dueDateFormat','2019-01-28 20:58:57'),('indexing_profile_evergreen_org_unit_schema','2023-01-30 23:46:02'),('indexing_profile_extendLocationsToSuppress','2019-01-28 20:58:57'),('indexing_profile_fallbackFormatField','2022-01-06 18:46:17'),('indexing_profile_filenames_to_include','2019-01-28 20:58:57'),('indexing_profile_folderCreation','2019-01-28 20:58:57'),('indexing_profile_groupUnchangedFiles','2019-01-28 20:58:57'),('indexing_profile_holdability','2019-01-28 20:58:57'),('indexing_profile_lastChangeProcessed','2021-04-22 01:34:04'),('indexing_profile_lastRecordIdProcessed','2021-04-20 23:30:12'),('indexing_profile_last_checkin_date','2019-01-28 20:58:57'),('indexing_profile_last_marc_export','2019-11-18 17:53:46'),('indexing_profile_last_volume_export_timestamp','2020-12-21 14:47:23'),('indexing_profile_marc_encoding','2019-01-28 20:58:57'),('indexing_profile_marc_record_subfield','2019-03-11 05:22:58'),('indexing_profile_record_linking','2022-10-06 14:57:01'),('indexing_profile_regroup_all_records','2021-02-24 23:04:51'),('indexing_profile_remove_unused_properties','2025-03-04 15:54:16'),('indexing_profile_replacement_cost_subfield','2024-10-15 18:40:04'),('indexing_profile_specific_order_location','2019-01-28 20:58:57'),('indexing_profile_specified_formats','2019-11-18 17:53:45'),('indexing_profile_speicified_formats','2019-01-28 20:58:57'),('indexing_profile_statusesToSuppressLength','2022-10-06 14:57:01'),('indexing_profile_under_consideration_order_records','2024-06-25 15:05:21'),('indexing_profile__full_export_record_threshold','2021-04-20 23:30:12'),('indexing_profile__remove_groupUnchangedFiles','2021-02-24 23:04:51'),('indexing_records_default_sub_location','2020-06-10 12:22:31'),('indexing_simplify_format_boosting','2021-02-22 16:03:55'),('index_common_timestamp_columns','2024-03-25 16:02:15'),('index_common_timestamp_columns_pt_2','2024-03-25 16:02:15'),('index_ils_barcode','2024-03-25 16:02:14'),('index_resources','2019-01-28 20:58:59'),('index_search_stats','2019-01-28 20:58:58'),('index_search_stats_counts','2019-01-28 20:58:58'),('index_subsets_of_overdrive','2019-01-28 20:58:56'),('initial_setup','2011-11-15 22:29:11'),('institution_code','2024-04-11 17:23:35'),('ip_address_logs','2020-09-14 14:09:06'),('ip_address_logs_login_info','2021-01-14 16:33:48'),('ip_debugging','2020-08-27 19:21:51'),('ip_log_queries','2021-03-19 14:13:56'),('ip_log_timing','2021-03-05 20:16:16'),('ip_lookup_1','2019-01-28 20:58:59'),('ip_lookup_2','2019-01-28 20:58:59'),('ip_lookup_3','2019-01-28 20:58:59'),('ip_lookup_blocking','2020-06-10 12:22:32'),('ip_lookup_showlogmeout','2022-01-05 21:16:09'),('islandora_cover_cache','2019-01-28 20:58:57'),('islandora_driver_cache','2019-01-28 20:58:57'),('islandora_lat_long_cache','2019-01-28 20:58:57'),('islandora_samePika_cache','2019-01-28 20:58:57'),('javascript_snippets','2020-10-26 18:02:00'),('languages_setup','2019-11-18 17:53:53'),('languages_show_for_translators','2019-11-18 17:53:54'),('language_locales','2020-11-02 16:19:32'),('large_print_indexing','2020-03-27 19:21:07'),('last_check_in_status_adjustments','2019-01-28 20:58:57'),('layout_settings','2019-12-18 18:48:10'),('layout_settings_contrast','2021-12-23 19:38:39'),('layout_settings_remove_showSidebarMenu','2020-08-27 17:51:27'),('layout_settings_remove_sidebarMenuButtonText','2021-01-16 22:58:46'),('lexile_branding','2019-01-28 20:58:56'),('libraryAdmin','2019-01-28 20:58:59'),('libraryAllowUsernameUpdates','2020-07-08 14:56:02'),('libraryAlternateCardSetup','2020-06-19 20:20:57'),('libraryAvailableHoldDelay','2020-07-21 13:33:42'),('libraryCardBarcode','2020-06-19 20:20:57'),('libraryProfileRequireNumericPhoneNumbersWhenUpdatingProfile','2020-08-03 20:28:58'),('libraryProfileUpdateOptions','2020-07-21 13:33:41'),('library_1','2019-01-28 20:56:57'),('library_10','2019-01-28 20:56:57'),('library_11','2019-01-28 20:56:57'),('library_12','2019-01-28 20:56:57'),('library_13','2019-01-28 20:56:57'),('library_14','2019-01-28 20:56:57'),('library_15','2019-01-28 20:56:57'),('library_16','2019-01-28 20:56:57'),('library_17','2019-01-28 20:56:57'),('library_18','2019-01-28 20:56:57'),('library_19','2019-01-28 20:56:57'),('library_2','2019-01-28 20:56:57'),('library_20','2019-01-28 20:56:57'),('library_21','2019-01-28 20:56:57'),('library_23','2019-01-28 20:56:57'),('library_24','2019-01-28 20:56:57'),('library_25','2019-01-28 20:56:57'),('library_26','2019-01-28 20:56:57'),('library_28','2019-01-28 20:56:57'),('library_29','2019-01-28 20:56:57'),('library_3','2019-01-28 20:56:57'),('library_30','2019-01-28 20:56:57'),('library_31','2019-01-28 20:56:57'),('library_32','2019-01-28 20:56:57'),('library_33','2019-01-28 20:56:57'),('library_34','2019-01-28 20:56:57'),('library_35_marmot','2019-01-28 20:56:57'),('library_35_nashville','2019-01-28 20:56:57'),('library_36_nashville','2019-01-28 20:56:57'),('library_4','2019-01-28 20:56:57'),('library_5','2019-01-28 20:56:57'),('library_6','2019-01-28 20:56:57'),('library_7','2019-01-28 20:56:57'),('library_8','2019-01-28 20:56:57'),('library_9','2019-01-28 20:56:57'),('library_add_can_update_phone_number','2020-09-16 23:42:31'),('library_add_can_update_work_phone_number','2024-06-25 15:05:20'),('library_add_oai_searching','2019-11-18 17:53:40'),('library_allowDeletingILSRequests','2021-05-06 03:26:45'),('library_allow_home_library_updates','2021-01-17 03:04:54'),('library_allow_remember_pickup_location','2021-01-16 22:58:46'),('library_allow_renewing_out_of_hold_group_checkouts','2025-03-04 15:54:15'),('library_archive_material_requests','2019-01-28 20:58:56'),('library_archive_material_request_form_configurations','2019-01-28 20:58:56'),('library_archive_pid','2019-01-28 20:58:56'),('library_archive_related_objects_display_mode','2019-01-28 20:58:56'),('library_archive_request_customization','2019-01-28 20:58:56'),('library_archive_search_facets','2019-01-28 20:58:55'),('library_barcodes','2019-01-28 20:58:55'),('library_bookings','2019-01-28 20:58:55'),('library_cancel_in_transit_holds','2024-01-05 09:20:56'),('library_cas_configuration','2019-01-28 20:58:56'),('library_checkRequestsForExistingTitles','2025-03-04 15:54:16'),('library_citationOptions','2022-02-21 20:49:29'),('library_CityStateField','2022-12-14 23:10:16'),('library_claim_authorship_customization','2019-01-28 20:58:56'),('library_cleanup','2019-12-18 18:42:59'),('library_consortial_interface','2021-10-13 17:18:10'),('library_contact_links','2019-01-28 20:56:57'),('library_course_reserves_libraries_to_include','2021-11-30 16:30:45'),('library_css','2019-01-28 20:56:57'),('library_default_materials_request_permissions','2021-06-08 16:30:34'),('library_deletePaymentHistoryOlderThan','2024-03-25 16:02:14'),('library_displayName_length','2022-02-21 19:09:05'),('library_eds_integration','2019-01-28 20:58:56'),('library_eds_search_integration','2019-01-28 20:58:56'),('library_enableForgotPasswordLink','2020-02-09 22:24:42'),('library_enableReadingHistory','2022-02-21 20:22:50'),('library_enableSavedSearches','2022-03-01 12:54:52'),('library_enable_cost_savings','2024-10-15 18:40:04'),('library_enable_web_builder','2020-07-22 12:49:58'),('library_events_setting','2020-04-30 14:33:55'),('library_expiration_warning','2019-01-28 20:56:57'),('library_facets','2019-01-28 20:58:55'),('library_facets_1','2019-01-28 20:58:55'),('library_facets_2','2019-01-28 20:58:55'),('library_field_level_permissions','2021-05-29 21:27:28'),('library_field_permission_updates_21_07_01','2021-06-08 13:55:03'),('library_fine_payment_order','2019-12-03 14:45:39'),('library_fine_updates_msb','2021-03-05 20:16:16'),('library_fine_updates_paypal','2019-11-18 17:53:43'),('library_grouping','2019-01-28 20:56:57'),('library_holdPlacedAt','2022-03-08 21:20:07'),('library_holdRange','2022-12-19 15:25:00'),('library_ils_code_expansion','2019-01-28 20:56:57'),('library_ils_code_expansion_2','2019-01-28 20:56:58'),('library_indexes','2019-11-18 17:53:41'),('library_links','2019-01-28 20:56:57'),('library_links_display_options','2019-01-28 20:56:57'),('library_links_menu_update','2020-09-27 22:33:11'),('library_links_open_in_new_tab','2020-03-27 19:21:05'),('library_links_showToLoggedInUsersOnly','2020-04-30 14:33:47'),('library_links_show_html','2019-01-28 20:56:57'),('library_lists_without_editable_text','2022-11-29 15:37:00'),('library_location_availability_toggle_updates','2019-01-28 20:58:56'),('library_location_axis360_scoping','2020-08-07 14:36:04'),('library_location_boosting','2019-01-28 20:56:57'),('library_location_cloud_library_scoping','2019-11-18 17:53:42'),('library_location_defaults','2019-12-18 18:42:59'),('library_location_display_controls','2019-01-28 20:58:55'),('library_location_hoopla_scoping','2019-11-18 17:53:41'),('library_location_palace_project_scoping','2024-01-05 09:20:52'),('library_location_rbdigital_scoping','2019-11-18 17:53:41'),('library_location_repeat_online','2019-01-28 20:56:57'),('library_location_side_load_scoping','2019-11-18 17:53:47'),('library_login_notes','2020-11-09 19:22:45'),('library_materials_request_limits','2019-01-28 20:56:57'),('library_materials_request_new_request_summary','2019-01-28 20:56:57'),('library_maximum_local_ill_requests','2025-03-04 15:54:15'),('library_max_fines_for_account_update','2019-01-28 20:58:56'),('library_menu_link_languages','2021-09-11 20:31:09'),('library_nameAndDobUpdates','2022-04-01 23:01:16'),('library_on_order_counts','2019-01-28 20:58:56'),('library_order_information','2019-01-28 20:56:57'),('library_patronNameDisplayStyle','2019-01-28 20:58:56'),('library_patron_messages','2021-03-30 14:30:44'),('library_payment_history','2024-03-25 16:02:14'),('library_pin_reset','2019-01-28 20:56:57'),('library_prevent_expired_card_login','2019-01-28 20:56:57'),('library_prompt_birth_date','2019-01-28 20:58:55'),('library_propay_settings','2021-04-08 13:50:39'),('library_remove_gold_rush','2019-11-18 17:53:41'),('library_remove_overdrive_advantage_info','2020-09-03 19:46:25'),('library_remove_unusedColumns','2019-11-18 17:53:40'),('library_remove_unusedDisplayOptions_3_18','2019-11-18 17:53:40'),('library_remove_unused_recordsToBlackList','2019-11-18 17:53:40'),('library_rename_prospector','2019-11-18 17:53:39'),('library_rename_showPickupLocationInProfile','2021-01-17 03:51:29'),('library_requestCalendarStartDate','2025-03-04 15:54:16'),('library_shareit_credentials','2025-03-04 15:54:14'),('library_shareit_settings','2025-03-04 15:54:14'),('library_showConvertListsFromClassic','2020-02-09 22:24:42'),('library_showVolumesWithLocalCopiesFirst','2022-11-27 21:08:05'),('library_show_card_expiration_date','2021-08-23 18:45:52'),('library_show_display_name','2019-01-28 20:58:55'),('library_show_language_and_display_in_header','2024-01-05 09:20:55'),('library_show_messaging_settings','2021-10-13 17:18:10'),('library_show_quick_copy','2019-11-18 17:53:42'),('library_show_series_in_main_details','2019-01-28 20:58:56'),('library_sidebar_menu','2019-01-28 20:58:56'),('library_sidebar_menu_button_text','2019-01-28 20:58:56'),('library_sitemap_changes','2020-04-30 14:33:47'),('library_subject_display','2019-01-28 20:58:56'),('library_subject_display_2','2019-01-28 20:58:56'),('library_systemHoldNotes','2022-02-03 03:31:28'),('library_system_message','2020-03-31 18:45:02'),('library_tiktok_link','2021-05-12 22:50:27'),('library_toggle_hold_position','2024-03-25 16:02:15'),('library_top_links','2019-01-28 20:56:57'),('library_use_theme','2019-02-26 00:09:00'),('library_validPickupSystemLength','2021-11-17 19:59:38'),('library_workstation_id_polaris','2021-05-26 16:19:08'),('lida_general_settings_add_more_info','2025-03-04 15:54:16'),('linked_accounts_switch','2019-01-28 20:58:56'),('link_format_sorting_to_display_settings','2024-10-15 18:40:04'),('link_syndetics_and_libraries','2025-03-04 15:54:15'),('listPublisherRole','2019-01-28 20:58:59'),('list_indexing_permission','2020-09-09 18:44:18'),('list_wdiget_list_update_1','2019-01-28 20:58:57'),('list_wdiget_update_1','2019-01-28 20:58:57'),('list_widgets','2019-01-28 20:58:57'),('list_widgets_home','2019-01-28 20:58:57'),('list_widgets_update_1','2019-01-28 20:58:57'),('list_widgets_update_2','2019-01-28 20:58:57'),('list_widget_num_results','2019-01-28 20:58:57'),('list_widget_search_terms','2020-02-09 22:24:43'),('list_widget_style_update','2019-01-28 20:58:57'),('list_widget_update_2','2019-01-28 20:58:57'),('list_widget_update_3','2019-01-28 20:58:57'),('list_widget_update_4','2019-01-28 20:58:57'),('list_widget_update_5','2019-01-28 20:58:57'),('literaryFormIndexingUpdates','2021-09-12 17:01:38'),('lm_library_calendar_events_data','2020-04-30 14:33:55'),('lm_library_calendar_private_feed_settings','2020-04-30 14:33:55'),('lm_library_calendar_settings','2020-04-30 14:33:55'),('loadBadWords','2021-10-27 14:05:27'),('loadCoversFrom020z','2020-04-30 14:33:56'),('loan_rule_determiners_1','2019-01-28 20:59:01'),('loan_rule_determiners_increase_ptype_length','2019-01-28 20:59:01'),('localIllRequestType','2025-03-04 15:54:15'),('localized_browse_categories','2019-01-28 20:59:02'),('local_ill_forms','2025-03-04 15:54:15'),('local_ill_form_default_max_fee','2025-03-04 15:54:15'),('local_urls','2021-07-21 22:25:51'),('locationHistoricCode','2020-06-19 20:20:57'),('location_1','2019-01-28 20:58:55'),('location_10','2019-01-28 20:58:56'),('location_2','2019-01-28 20:58:56'),('location_3','2019-01-28 20:58:56'),('location_4','2019-01-28 20:58:56'),('location_5','2019-01-28 20:58:56'),('location_6','2019-01-28 20:58:56'),('location_7','2019-01-28 20:58:56'),('location_8','2019-01-28 20:58:56'),('location_9','2019-01-28 20:58:56'),('location_additional_branches_to_show_in_facets','2019-01-28 20:58:56'),('location_address','2019-01-28 20:58:56'),('location_add_notes_to_hours','2020-08-03 20:28:58'),('location_allow_multiple_open_hours_per_day','2019-11-18 17:53:42'),('location_facets','2019-01-28 20:58:55'),('location_facets_1','2019-01-28 20:58:55'),('location_field_level_permissions','2021-06-01 15:24:41'),('location_hours','2019-01-28 20:59:01'),('location_include_library_records_to_include','2019-01-28 20:58:56'),('location_increase_code_column_size','2019-01-28 20:58:56'),('location_library_control_shelf_location_and_date_added_facets','2019-01-28 20:58:56'),('location_self_registration_branch','2022-10-06 14:57:01'),('location_shareit_settings','2025-03-04 15:54:14'),('location_show_display_name','2019-01-28 20:58:56'),('location_show_language_and_display_in_header','2024-01-05 09:20:55'),('location_subdomain','2019-01-28 20:58:56'),('location_sublocation','2019-01-28 20:58:56'),('location_sublocation_uniqueness','2019-01-28 20:58:56'),('location_tty_description','2020-11-02 16:19:31'),('login_form_labels','2019-01-28 20:58:56'),('login_unless_in_library','2021-12-27 20:24:06'),('logout_after_hold_options','2022-01-07 00:06:56'),('logo_linking','2019-01-28 20:58:56'),('longer_stripe_keys','2024-01-05 09:20:58'),('lowercase_all_tables','2023-03-29 09:39:10'),('main_location_switch','2019-01-28 20:58:56'),('makeVdxHoldGroupsGeneric','2025-03-04 15:54:15'),('make_app_icons_os_specific','2025-03-04 15:54:16'),('make_cloudsource_baseurl_text','2025-03-04 15:54:15'),('make_nyt_user_list_publisher','2020-02-09 22:24:42'),('make_volumes_case_sensitive','2021-08-23 18:45:42'),('manageMaterialsRequestFieldsToDisplay','2019-01-28 20:58:59'),('manage_local_administrators_permission','2025-03-04 15:54:16'),('marcImport','2019-01-28 20:59:01'),('marcImport_1','2019-01-28 20:59:01'),('marcImport_2','2019-01-28 20:59:01'),('marcImport_3','2019-01-28 20:59:01'),('marc_last_modified','2021-07-15 05:01:14'),('masquerade_automatic_timeout_length','2019-01-28 20:58:56'),('masquerade_permissions','2020-09-07 15:12:17'),('masquerade_ptypes','2019-01-28 20:59:01'),('materialRequestsRole','2019-01-28 20:58:59'),('materialsRequest','2019-01-28 20:58:58'),('materialsRequestExistingTitle','2025-03-04 15:54:16'),('materialsRequestFixColumns','2019-01-28 20:58:59'),('materialsRequestFormats','2019-01-28 20:58:59'),('materialsRequestFormFields','2019-01-28 20:58:59'),('materialsRequestLibraryId','2019-01-28 20:58:59'),('materialsRequestStaffComments','2021-08-25 16:40:52'),('materialsRequestStatus','2019-01-28 20:58:59'),('materialsRequestStatus_update1','2019-01-28 20:58:59'),('materialsRequest_update1','2019-01-28 20:58:58'),('materialsRequest_update2','2019-01-28 20:58:58'),('materialsRequest_update3','2019-01-28 20:58:58'),('materialsRequest_update4','2019-01-28 20:58:58'),('materialsRequest_update5','2019-01-28 20:58:58'),('materialsRequest_update6','2019-01-28 20:58:58'),('materialsRequest_update7','2019-01-28 20:58:59'),('materials_request_days_to_keep','2019-01-28 20:58:56'),('materials_request_format_active_for_new_requests','2022-11-02 02:59:14'),('materials_request_hold_candidates','2024-10-15 18:40:04'),('materials_request_hold_candidate_generation_log','2024-10-15 18:40:04'),('materials_request_hold_failure_message','2024-10-15 18:40:04'),('materials_request_ready_for_holds','2024-10-15 18:40:04'),('materials_request_selected_hold_candidate','2024-10-15 18:40:04'),('memory_index','2019-11-18 17:53:59'),('memory_table','2019-11-18 17:53:59'),('memory_table_size_increase','2019-11-18 17:53:59'),('merged_records','2019-01-28 20:58:59'),('migrate_form_submissions','2024-01-05 09:20:58'),('migrate_library_sso_settings','2023-02-09 17:11:58'),('migrate_records_owned','2023-01-04 22:41:33'),('migrate_web_resource_library_access_rules','2024-10-15 18:40:04'),('millenniumTables','2019-01-28 20:59:01'),('modifyColumnSizes_1','2011-11-10 19:46:03'),('modules','2019-11-18 17:53:39'),('module_log_information','2020-03-31 18:45:02'),('module_settings_information','2021-02-12 01:49:54'),('monitorAntivirus','2024-01-05 09:20:55'),('more_details_customization','2019-01-28 20:58:56'),('move_aspen_lida_settings','2022-08-17 22:43:45'),('move_cookieConsent_to_library_settings','2024-01-05 09:20:39'),('move_includePersonalAndCorporateNamesInTopics','2023-03-10 17:47:18'),('move_library_quick_searches','2022-08-17 22:43:45'),('move_location_app_settings','2022-08-17 22:43:45'),('move_overdrive_reader_name_to_settings','2025-03-04 15:54:14'),('move_unchanged_scope_data_to_item','2021-07-16 16:05:20'),('multiple_overdrive_scopes','2025-03-04 15:54:14'),('native_events_indexing_tables','2025-03-04 15:54:16'),('native_events_permissions','2025-03-04 15:54:16'),('native_events_system_variable','2025-03-04 15:54:16'),('native_event_tables','2025-03-04 15:54:16'),('ncr_library_setting','2024-06-25 15:05:22'),('ncr_payments_settings','2024-06-25 15:05:22'),('ncr_permissions','2024-06-25 15:05:22'),('nearby_book_store','2019-01-28 20:59:01'),('newRolesJan2016','2019-01-28 20:58:59'),('new_search_stats','2019-01-28 20:58:58'),('new_york_times_user_updates','2020-10-27 17:06:36'),('nongrouped_records','2019-01-28 20:58:59'),('non_numeric_ptypes','2019-01-28 20:59:01'),('normalize_scope_data','2021-07-15 08:06:59'),('notices_1','2011-12-02 18:26:28'),('notifications_report_permissions','2022-08-22 18:57:13'),('notInterested','2019-01-28 20:58:58'),('notInterestedWorks','2019-01-28 20:58:58'),('notInterestedWorksRemoveUserIndex','2019-01-28 20:58:58'),('novelist_data','2019-01-28 20:59:02'),('novelist_data_indexes','2019-11-18 17:53:56'),('novelist_data_json','2019-11-18 17:53:56'),('novelist_settings','2020-01-03 19:47:26'),('nyt_api_settings','2020-01-09 01:49:33'),('nyt_update_log_numSkipped','2021-03-29 21:10:57'),('OAI_default_image','2024-01-05 09:20:37'),('oai_record_lastSeen','2023-05-08 14:13:51'),('oai_website_permissions','2020-09-09 15:44:06'),('object_history','2020-05-14 19:25:09'),('object_history_action_type','2022-12-22 18:35:24'),('object_history_field_lengths','2020-05-14 19:25:09'),('offline_circulation','2019-01-28 20:59:02'),('offline_holds','2019-01-28 20:59:02'),('offline_holds_update_1','2019-01-28 20:59:02'),('offline_holds_update_2','2019-01-28 20:59:02'),('omdb_disableCoversWithNoDates','2021-11-09 18:41:08'),('omdb_settings','2020-03-27 19:52:27'),('only_allow_100_titles_per_collection_spotlight','2023-05-08 17:10:51'),('open_archives_collection','2019-11-18 17:53:54'),('open_archives_collection_filtering','2019-11-18 17:53:54'),('open_archives_collection_subjects','2019-11-18 17:53:54'),('open_archives_deleted_collections','2022-05-10 13:49:53'),('open_archives_facets','2024-01-05 09:20:45'),('open_archives_facets_default','2024-01-05 09:20:45'),('open_archives_image_regex','2021-09-30 15:57:01'),('open_archives_index_all_sets','2024-03-25 16:02:13'),('open_archives_loadOneMonthAtATime','2019-11-18 17:53:55'),('open_archives_log','2019-11-18 17:53:55'),('open_archives_metadata_format','2024-03-25 16:02:13'),('open_archives_module_add_log','2020-03-31 18:45:02'),('open_archives_module_add_settings','2021-02-12 01:49:54'),('open_archives_multiple_imageRegex','2022-02-01 15:31:48'),('open_archives_record','2019-11-18 17:53:54'),('open_archives_reindex_all_collections_22_05','2022-05-10 13:49:53'),('open_archives_scoping','2020-12-14 17:03:19'),('open_archives_usage_add_instance','2020-12-21 17:31:10'),('open_archive_tracking_adjustments','2019-11-18 17:53:55'),('optionalUpdates','2024-01-05 09:20:48'),('optionalUpdates23_10','2024-01-05 09:20:48'),('optional_show_title_on_grapes_pages','2025-03-04 15:54:15'),('overDriveDisableRequestLogging','2021-11-22 17:42:28'),('overdrive_account_cache','2012-01-02 22:16:10'),('overdrive_add_settings','2019-11-18 17:53:47'),('overdrive_add_setting_to_log','2020-12-14 17:03:19'),('overdrive_add_setting_to_product_availability','2020-12-14 17:03:19'),('overdrive_add_setting_to_scope','2020-12-14 17:03:19'),('overdrive_add_update_info_to_settings','2019-11-18 17:53:47'),('overdrive_allow_large_deletes','2021-02-06 21:31:44'),('overdrive_api_data','2016-06-30 17:11:12'),('overdrive_api_data_availability_shared','2019-11-18 17:53:47'),('overdrive_api_data_availability_type','2016-06-30 17:11:12'),('overdrive_api_data_crossRefId','2019-01-28 21:27:59'),('overdrive_api_data_metadata_isOwnedByCollections','2019-01-28 21:27:59'),('overdrive_api_data_update_1','2016-06-30 17:11:12'),('overdrive_api_data_update_2','2016-06-30 17:11:12'),('overdrive_api_remove_old_tables','2019-11-18 17:53:47'),('overdrive_availability_update_indexes','2021-02-06 21:31:44'),('overdrive_circulationEnabled','2021-09-15 04:46:04'),('overdrive_client_credentials','2021-01-14 16:33:48'),('overdrive_collection_name_for_circ','2025-03-04 15:54:15'),('overdrive_enable_allow_large_deletes','2022-07-28 19:43:51'),('overdrive_encrypt_client_secret','2022-02-27 18:40:30'),('overdrive_encrypt_client_secret_in_scope','2022-02-27 18:40:30'),('overdrive_format_length','2024-10-15 18:40:03'),('overdrive_handle_ise','2022-02-27 18:40:30'),('overdrive_index_cross_ref_id','2024-03-25 16:02:14'),('overdrive_integration','2019-01-28 20:58:56'),('overdrive_integration_2','2019-01-28 20:58:56'),('overdrive_integration_3','2019-01-28 20:58:56'),('overdrive_max_extraction_threads','2021-09-22 16:15:42'),('overdrive_module_add_log','2020-03-31 18:45:02'),('overdrive_module_add_settings','2021-02-12 01:49:54'),('overdrive_part_count','2020-04-30 14:33:48'),('overdrive_series_length','2024-10-15 18:40:03'),('overdrive_showLibbyPromo','2021-11-18 04:45:20'),('overdrive_usage_add_instance','2020-12-21 17:30:33'),('overdrive_useFulfillmentInterface','2021-11-16 04:58:47'),('palace_project_cancellation_url','2024-03-25 16:02:12'),('palace_project_collection','2024-04-11 17:23:35'),('palace_project_collection_name','2024-03-25 16:02:12'),('palace_project_exportLog','2024-01-05 09:20:52'),('palace_project_identifier_length','2024-01-05 09:20:53'),('palace_project_identifier_length2','2024-01-05 09:20:55'),('palace_project_permissions','2024-01-05 09:20:52'),('palace_project_restrict_scopes_by_audience','2024-04-11 17:23:35'),('palace_project_return_url','2024-03-25 16:02:12'),('palace_project_titles','2024-01-05 09:20:53'),('palace_project_title_availability','2024-04-11 17:23:35'),('palace_project_title_length','2024-03-25 16:02:12'),('palace_project_update_title_uniqueness','2024-03-25 16:02:13'),('payment_branch_settings','2025-03-04 15:54:15'),('payment_paidFrom','2021-11-16 05:33:08'),('paypal_error_email','2022-05-06 00:28:41'),('paypal_moveSettingsFromLibrary','2021-11-17 20:41:08'),('paypal_settings','2021-07-12 16:44:30'),('paypal_showPayLater','2021-11-17 19:59:38'),('pdfView','2021-07-16 16:03:44'),('permissions_bad_words','2022-04-04 16:33:39'),('permissions_community_sharing','2023-04-10 18:49:00'),('permissions_create_administer_smtp','2024-10-15 18:40:03'),('permissions_create_administer_user_agents','2024-06-25 15:05:21'),('permissions_create_events_assabet','2024-06-25 15:05:20'),('permissions_create_events_communico','2023-03-15 23:02:46'),('permissions_create_events_springshare','2022-03-23 00:46:39'),('permissions_ecommerce_deluxe','2023-03-27 09:11:11'),('permissions_ecommerce_payflow','2023-05-05 21:42:54'),('permissions_ecommerce_snappay','2024-10-15 18:40:04'),('permissions_ecommerce_square','2024-01-05 09:20:35'),('permissions_ecommerce_stripe','2024-01-05 09:20:58'),('permissions_events_facets','2024-01-05 09:20:39'),('permissions_open_archives_facets','2024-01-05 09:20:44'),('permissions_self_reg_forms','2024-01-05 09:20:49'),('permissions_view_scheduled_updates','2023-04-27 15:41:47'),('permissions_website_facets','2024-01-05 09:20:46'),('permission_hide_series','2024-03-25 16:02:14'),('pinResetRules','2022-03-01 12:54:52'),('pinterest_library_contact_links','2020-06-10 12:22:30'),('pin_reset_token','2022-01-05 16:27:08'),('placards','2019-12-03 14:45:40'),('placard_alt_text','2021-10-01 15:06:15'),('placard_languages','2021-09-08 14:55:34'),('placard_location_scope','2020-03-27 19:21:11'),('placard_source_type','2025-03-04 15:54:16'),('placard_timing','2020-11-30 16:59:15'),('placard_trigger_exact_match','2020-06-29 18:17:29'),('placard_updates_1','2020-03-27 19:21:11'),('plural_grouped_work_facet','2021-07-06 15:52:37'),('polaris_full_update_21_13','2021-09-30 15:57:01'),('polaris_item_identifiers','2021-09-30 15:57:01'),('populate_list_entry_titles','2021-06-08 13:55:03'),('portal_cell_custom_image','2025-03-04 15:54:16'),('portal_cell_show_hide_description','2025-03-04 15:54:16'),('prefer_ils_description','2025-03-04 15:54:16'),('prevent_automatic_hour_updates','2024-10-15 18:40:03'),('processes_to_stop','2024-03-25 16:02:14'),('processes_to_stop_time','2024-03-25 16:02:14'),('process_empty_grouped_works','2022-06-14 22:42:32'),('propay_accountId_to_user','2021-07-26 21:59:48'),('propay_certStr_length','2021-08-23 18:45:43'),('propay_settings','2021-07-12 16:44:30'),('propay_settings_additional_fields','2021-07-26 21:59:48'),('ptype','2019-01-28 20:59:01'),('pTypesForLibrary','2019-01-28 20:58:55'),('ptype_account_profile','2025-03-04 15:54:16'),('ptype_allowStaffViewDisplay','2022-02-21 19:09:05'),('ptype_descriptions','2021-05-03 20:49:18'),('ptype_length','2022-12-14 23:10:16'),('ptype_vdx_client_category','2022-12-19 15:25:00'),('public_lists_to_include','2019-01-28 20:58:56'),('public_lists_to_include_defaults','2019-11-18 17:53:39'),('purchase_link_tracking','2019-01-28 20:58:58'),('quipu_ecard_settings','2021-06-11 21:21:10'),('quipu_e_renew','2024-01-05 09:20:56'),('rbdigital_add_settings','2019-11-18 17:53:49'),('rbdigital_add_setting_to_availability','2020-03-27 19:21:10'),('rbdigital_add_setting_to_log','2020-03-27 19:21:08'),('rbdigital_add_setting_to_scope','2020-03-27 19:21:07'),('rbdigital_availability','2019-03-06 15:43:14'),('rbdigital_exportLog','2019-03-05 05:46:15'),('rbdigital_exportLog_update','2019-11-18 17:53:49'),('rbdigital_exportTables','2019-03-05 16:31:43'),('rbdigital_issues_tables','2020-03-27 19:21:10'),('rbdigital_issue_tracking','2020-03-27 19:21:10'),('rbdigital_lookup_patrons_by_email','2020-02-09 22:24:43'),('rbdigital_magazine_export','2019-11-18 17:53:49'),('rbdigital_module_add_log','2020-03-31 18:45:02'),('rbdigital_module_add_settings','2021-02-12 01:49:54'),('rbdigital_module_add_settings2','2021-05-06 03:22:55'),('rbdigital_scoping','2019-11-18 17:53:49'),('rbdigital_usage_add_instance','2020-12-21 17:30:04'),('readerName2','2024-01-05 09:20:55'),('readingHistory','2019-01-28 20:58:58'),('readingHistoryIsILL','2024-01-05 09:20:40'),('readingHistoryUpdate1','2019-01-28 20:58:58'),('readingHistory_deletion','2019-01-28 20:58:58'),('readingHistory_work','2019-01-28 20:58:58'),('reading_history_entry_cost_savings','2024-10-15 18:40:04'),('reading_history_updates_change_ils','2023-01-31 00:46:53'),('rebuildThemes21_03','2021-03-05 20:16:16'),('recaptcha_settings','2020-04-30 14:33:56'),('recommendations_optOut','2019-01-28 20:58:58'),('records_to_exclude_increase_length','2022-02-01 15:31:57'),('records_to_exclude_increase_length_to_400','2022-12-09 21:56:32'),('records_to_include_2017-06','2019-01-28 20:58:57'),('records_to_include_2018-03','2019-01-28 20:58:57'),('records_to_include_updates','2023-01-04 22:41:33'),('record_files_table','2020-04-30 14:33:56'),('record_grouping_log','2019-01-28 20:59:02'),('record_identifiers_to_reload','2020-02-09 22:24:43'),('record_parents','2022-10-06 14:57:01'),('record_parents_index','2022-12-19 21:52:45'),('record_suppression_no_marc','2021-07-21 22:25:50'),('redwood_user_contribution','2019-11-18 17:53:55'),('refetch_novelist_data_21_09_02','2021-08-23 18:45:42'),('regroupAllRecordsDuringNightlyIndex','2022-05-26 19:42:54'),('regroup_21_03','2021-03-05 20:16:16'),('regroup_21_07','2021-05-26 16:19:08'),('reindexLog','2019-01-28 20:59:01'),('reindexLog_1','2019-01-28 20:59:01'),('reindexLog_2','2019-01-28 20:59:01'),('reindexLog_grouping','2019-01-28 20:59:01'),('reindexLog_nightly_updates','2020-05-14 19:25:09'),('reindexLog_unique_index','2021-05-12 22:50:28'),('reloadBadWords','2022-04-04 17:16:19'),('remove2FADefaultRememberMe','2022-01-05 16:27:08'),('removeGroupedWorkSecondDateUpdatedIndex','2021-09-22 16:15:42'),('removeIslandoraTables','2021-09-01 18:32:47'),('removeProPayFromLibrary','2021-07-12 16:44:30'),('remove_bookings','2021-08-23 18:45:52'),('remove_browse_tables','2019-01-28 20:59:02'),('remove_collection_from_palace_project','2024-04-11 17:23:35'),('remove_consortial_results_in_search','2019-01-28 20:58:56'),('remove_deprecated_self_reg_columns','2024-06-25 15:05:22'),('remove_detailed_hold_notice_configuration','2022-07-05 21:44:33'),('remove_donation_form_fields_table','2024-03-25 16:02:13'),('remove_econtent_support_address','2021-09-01 18:32:47'),('remove_editorial_reviews','2019-11-18 17:53:56'),('remove_empty_MyFavorites_lists','2022-07-05 21:44:33'),('remove_grouped_work_solr_core','2022-06-14 15:52:04'),('remove_holding_branch_label','2020-01-03 19:47:11'),('remove_id_from_grouped_work_record_item_url','2022-06-14 15:52:10'),('remove_individual_marc_path','2024-06-25 15:05:20'),('remove_library_and location_boost','2019-11-18 17:53:56'),('remove_library_location_boosting','2019-11-18 17:53:39'),('remove_library_themeName','2021-05-29 22:52:59'),('remove_library_top_links','2020-08-31 17:13:32'),('remove_list_entries_for_deleted_lists','2022-07-06 22:30:42'),('remove_list_widget_list_links','2020-02-09 22:24:43'),('remove_loan_rules','2021-06-08 13:54:58'),('remove_merged_records','2020-02-09 22:24:58'),('remove_old_homeLink','2021-04-20 23:30:13'),('remove_old_payment_lines','2024-03-26 16:08:04'),('remove_old_resource_tables','2019-01-28 20:59:02'),('remove_old_sso_fields','2025-03-04 15:54:15'),('remove_old_user_rating_table','2020-02-09 22:24:58'),('remove_order_options','2019-01-28 20:58:56'),('remove_overdrive_api_data_needsUpdate','2019-11-18 17:53:47'),('remove_overdrive_fulfillment_method','2025-03-04 15:54:14'),('remove_ptype_from_library_location','2021-07-26 21:59:49'),('remove_rbdigital','2021-07-17 06:39:22'),('remove_record_grouping_log','2020-02-09 22:24:58'),('remove_scope_tables','2021-07-17 23:38:41'),('remove_scope_triggers','2021-07-21 22:25:35'),('remove_showInSearchFacet','2024-10-15 18:40:04'),('remove_spelling_words','2019-11-18 17:53:56'),('remove_suppress_itemless_bibs_setting','2024-01-05 09:20:48'),('remove_titleId2_index_from_axis360_title_availability','2023-05-16 18:09:24'),('remove_unused_enrichment_and_full_record_options','2019-01-28 20:58:56'),('remove_unused_fields_23_07','2024-01-05 09:20:35'),('remove_unused_fields_23_07b','2024-01-05 09:20:35'),('remove_unused_location_options_2015_14_0','2019-01-28 20:58:56'),('remove_unused_options','2019-01-28 20:59:02'),('remove_unused_primary_language_from_grouped_work','2025-03-04 15:54:15'),('remove_unused_updates_properties','2025-03-04 15:54:16'),('remove_used_triggers','2024-06-25 15:05:20'),('remove_web_builder_menu','2024-01-05 09:20:55'),('rename_availability_facet','2024-01-05 09:20:51'),('rename_axis360_permission','2024-01-05 09:20:54'),('rename_boundless_module','2024-01-05 09:20:54'),('rename_general_settings_table','2023-02-22 20:48:38'),('rename_materialreq_usage_locID_column','2023-01-12 15:36:19'),('rename_overdrive_permission','2024-01-05 09:20:55'),('rename_prospector_to_innreach2','2024-01-05 09:20:34'),('rename_prospector_to_innreach3','2024-01-05 09:20:34'),('rename_tables','2019-01-28 20:59:01'),('rename_to_collection_spotlight','2020-02-09 22:24:43'),('renew_by_ptype','2024-01-05 09:20:56'),('renew_error','2021-07-06 15:52:37'),('repeat_in_cloudsource','2025-03-04 15:54:14'),('replacement_costs','2024-10-15 18:40:04'),('replace_arial_fonts','2024-04-11 17:23:35'),('replace_arial_fonts_2','2024-06-25 15:05:20'),('reporting_permissions','2020-09-23 22:36:33'),('reprocess_all_sideloads_22_06_04','2022-06-14 16:54:19'),('requireLogin_webResource','2022-01-05 16:27:08'),('requires_address_info','2024-03-25 16:02:13'),('resource_subject','2019-01-28 20:58:58'),('resource_update3','2019-01-28 20:58:58'),('resource_update4','2019-01-28 20:58:58'),('resource_update5','2019-01-28 20:58:58'),('resource_update6','2019-01-28 20:58:58'),('resource_update7','2019-01-28 20:58:58'),('resource_update8','2019-01-28 20:58:58'),('resource_update_table','2019-01-28 20:58:58'),('resource_update_table_2','2019-01-28 20:58:58'),('restrictLoginOfLibraryMembers','2022-04-18 16:48:25'),('restrictLoginToLibraryMembers','2022-03-15 04:54:50'),('re_enable_hoopla_module_auto_restart','2020-04-30 14:33:48'),('right_hand_sidebar','2019-01-28 20:58:56'),('roles_1','2019-01-28 20:58:56'),('roles_2','2019-01-28 20:58:56'),('rosen_levelup_settings','2020-07-30 21:13:34'),('rosen_levelup_settings_school_prefix','2020-08-27 17:51:27'),('runNightlyFullIndex','2020-05-14 19:25:09'),('run_full_update_for_palace_project_24_09','2024-10-15 18:40:03'),('saved_searches_created_default','2019-11-18 17:53:58'),('saved_search_hasNewResults','2022-07-26 18:27:38'),('saved_search_log','2022-07-26 17:18:51'),('saved_search_newTitles','2022-07-27 18:09:22'),('save_library_ils_consent_feature_toggle_value','2025-03-04 15:54:16'),('scheduled_update_remote_update','2023-05-05 14:22:08'),('scheduled_work_index','2020-02-09 22:24:43'),('search_increaseTitleLength','2021-11-18 04:45:20'),('search_options','2024-01-05 09:20:54'),('search_results_view_configuration_options','2019-01-28 20:58:56'),('search_sources','2019-01-28 20:58:56'),('search_sources_1','2019-01-28 20:58:56'),('search_test_description','2022-03-08 21:20:07'),('search_test_notes','2022-02-21 19:09:05'),('search_test_search_index_multiple_terms','2022-02-21 19:09:05'),('search_test_settings','2022-02-21 19:09:05'),('secondary phone number','2024-01-05 09:20:38'),('select_ILL_system','2024-01-05 09:20:51'),('selfRegistrationCustomizations','2020-06-10 12:22:30'),('selfRegistrationLocationRestrictions','2020-03-27 19:21:05'),('selfRegistrationPasswordNotes','2020-06-10 12:22:31'),('selfRegistrationUrl','2020-04-30 14:33:48'),('selfRegistrationZipCodeValidation','2020-10-30 19:03:30'),('selfreg_customization','2019-01-28 20:58:56'),('selfreg_template','2019-01-28 20:58:56'),('self_check_checkout_location','2024-04-11 17:23:35'),('self_registration_form','2024-01-05 09:20:50'),('self_registration_form_carlx','2024-10-15 18:40:03'),('self_registration_form_sierra','2024-10-15 18:40:03'),('self_registration_parent_sms','2022-10-06 14:57:01'),('self_registration_require_phone_and_email','2022-10-06 14:57:01'),('self_reg_barcode_prefix','2024-01-05 09:20:50'),('self_reg_default','2024-01-05 09:20:50'),('self_reg_form_id','2024-01-05 09:20:50'),('self_reg_form_permission','2024-01-05 09:20:50'),('self_reg_form_update','2024-03-25 16:02:17'),('self_reg_min_age','2024-03-25 16:02:16'),('self_reg_note_field_length','2024-10-15 18:40:03'),('self_reg_no_duplicate_check','2024-01-05 09:20:58'),('self_reg_sections','2024-03-25 16:02:13'),('self_reg_sections_assignment','2024-03-25 16:02:13'),('self_reg_symphony_only','2024-03-25 16:02:17'),('self_reg_tos','2024-03-25 16:02:17'),('self_reg_values_column_name','2024-10-15 18:40:03'),('sendgrid_settings','2019-11-18 17:53:58'),('series_log_class_name','2025-03-04 15:54:16'),('session_update_1','2019-01-28 20:59:02'),('setup_default_indexing_profiles','2019-01-28 20:58:57'),('setup_overdrive_advantage_and_auth_in_link_tables','2025-03-04 15:54:14'),('setUsePreferredNameInIlsOnUpdate','2023-02-01 16:32:49'),('set_hoopla_index_by_day_to_default','2025-03-04 15:54:16'),('set_include_econtent_and_onorder','2023-02-02 16:09:44'),('set_include_holdable_to_zero','2023-01-18 15:01:50'),('shared_content_in_greenhouse','2023-03-22 14:38:40'),('showCardExpirationDate','2021-08-23 18:45:52'),('showInSelectInterface','2021-09-01 18:32:47'),('showRelatedRecordLabels','2022-08-09 13:30:48'),('showTopOfPageButton','2021-11-03 23:21:36'),('showWhileYouWait','2020-05-14 19:25:08'),('show_catalog_options_in_profile','2019-01-28 20:58:56'),('show_cellphone_in_profile','2024-10-15 18:40:03'),('show_checkout_grid_by_format','2024-10-15 18:40:03'),('show_grouped_hold_copies_count','2019-01-28 20:58:56'),('show_in_search_facet_column','2024-10-15 18:40:03'),('show_item_notes_in_copies','2024-06-25 15:05:21'),('show_library_hours_notice_on_account_pages','2019-01-28 20:58:56'),('show_place_hold_on_unavailable','2019-01-28 20:58:56'),('show_quick_poll_results','2024-01-05 09:20:55'),('show_Refresh_Account_Button','2019-01-28 20:58:56'),('sideloads','2019-11-18 17:53:46'),('sideload_access_button_label','2021-10-08 00:07:19'),('sideload_convert_to_econtent','2024-06-25 15:05:21'),('sideload_defaults','2020-06-10 12:22:31'),('sideload_files','2020-09-23 22:36:33'),('sideload_log','2019-11-18 17:53:47'),('sideload_remove_unused_properties','2025-03-04 15:54:16'),('sideload_restrict_scopes_by_audience','2024-04-11 17:23:35'),('sideload_scope_match_and_rewrite','2019-12-11 21:32:56'),('sideload_scope_url_match_and_rewrite_embiggening','2021-04-22 01:34:03'),('sideload_scoping','2019-11-18 17:53:47'),('sideload_show_status','2021-10-13 17:18:10'),('sideload_usage_add_instance','2020-12-21 17:22:26'),('sideload_use_link_text_for_button_label','2024-06-25 15:05:21'),('sierra_exportLog','2019-01-28 20:58:57'),('sierra_exportLog_stats','2019-01-28 20:58:58'),('sierra_export_additional_fixed_fields','2020-01-03 19:47:11'),('sierra_export_field_mapping','2019-01-28 20:58:58'),('sierra_export_field_mapping_item_fields','2019-01-28 20:58:58'),('sierra_order_record_options','2023-04-10 22:54:46'),('sierra_public_note_export','2021-12-16 23:09:22'),('sierra_self_reg_form_updates','2025-03-04 15:54:16'),('sierra_self_reg_patron_type','2024-10-15 18:40:04'),('slow_pages','2019-11-18 17:53:58'),('slow_page_granularity','2019-11-18 17:53:59'),('smtp_settings','2024-10-15 18:40:03'),('snappay_settings','2024-10-15 18:40:04'),('snappay_settings_add_emailNotifications','2025-03-04 15:54:16'),('snippet_contains_analytics_cookies','2024-06-25 15:05:20'),('solrTimeoutStats','2022-02-21 19:09:05'),('sort_owned_editions_first','2022-09-10 21:14:14'),('sourceId_allow_255_char','2024-10-15 18:40:04'),('spelling_optimization','2019-01-28 20:59:01'),('split_user_fields','2024-01-05 09:20:39'),('springshare_libcal_events','2022-03-23 00:46:39'),('springshare_libcal_settings','2022-03-23 00:46:39'),('springshare_libcal_settings_multiple_calId','2022-03-23 00:55:20'),('sso_setting_add_entity_id','2022-11-02 02:59:14'),('staffSettingsAllowNegativeUserId','2020-10-30 19:03:30'),('staffSettingsTable','2019-01-28 20:58:59'),('staff_members','2020-07-22 12:49:59'),('staff_ptypes','2020-09-07 18:23:04'),('storeNYTLastUpdated','2021-07-16 16:03:44'),('storeRecordDetailsInDatabase','2021-07-11 21:04:56'),('storeRecordDetailsInSolr','2021-06-27 13:24:51'),('store_form_submissions_by_field','2024-01-05 09:20:58'),('store_grouped_work_records_items_scopes','2021-05-12 22:50:28'),('store_grouped_work_record_item_scope','2021-06-26 19:58:18'),('store_marc_in_db','2021-07-14 12:58:06'),('store_pickup_location','2021-01-16 22:58:46'),('store_place_of_publication','2024-03-25 16:02:14'),('store_scope_details_in_concatenated_fields','2021-07-17 23:38:41'),('sub-browse_categories','2019-01-28 20:59:02'),('sublocation_hold_data','2025-03-04 15:54:16'),('sublocation_non_unique_names','2025-03-04 15:54:15'),('sublocation_permissions','2025-03-04 15:54:15'),('sublocation_ptype_restriction','2025-03-04 15:54:16'),('sublocation_ptype_uniqueness','2025-03-04 15:54:16'),('sublocation_settings','2025-03-04 15:54:15'),('sublocation_user_preferences','2025-03-04 15:54:16'),('summon_integration','2024-03-25 16:02:12'),('summon_password_length','2024-03-25 16:02:13'),('summon_record_usage','2024-03-25 16:02:11'),('summon_usage_add_instance','2024-03-25 16:02:12'),('superCatalogerRole','2020-06-10 12:22:31'),('support_connection','2022-03-03 00:31:41'),('suppressRecordsWithUrlsMatching','2021-09-08 23:42:15'),('symphony_city_state','2024-01-05 09:20:51'),('symphony_default_phone_field','2024-10-15 18:40:03'),('symphony_self_registration_profile','2022-08-30 22:03:33'),('symphony_user_category_notice_settings','2024-10-15 18:40:03'),('syndetics_add_instance_number','2025-03-04 15:54:15'),('syndetics_data','2019-01-28 20:59:02'),('syndetics_data_update_1','2019-11-18 17:53:56'),('syndetics_settings','2020-01-03 19:47:26'),('syndetics_unbound','2021-05-06 03:22:55'),('syndetics_unbound_account_number','2021-05-26 16:19:08'),('systemVariables_libraryToUseForPayments','2021-11-18 04:14:08'),('system_messages','2020-11-30 20:23:35'),('system_messages_permissions','2020-11-30 20:24:27'),('system_message_style','2020-12-14 17:03:19'),('templates_for_grapes_web_builder','2024-10-15 18:40:03'),('test_roles_permission','2020-09-07 18:23:04'),('themes_additional_css','2020-01-08 18:33:49'),('themes_additional_fonts','2020-02-09 22:24:45'),('themes_badges','2020-08-02 16:33:58'),('themes_browse_category_colors','2020-01-09 19:54:16'),('themes_browse_category_image_size','2022-06-27 23:44:13'),('themes_browse_image_layout','2022-07-06 19:41:47'),('themes_button_colors','2020-02-09 22:24:56'),('themes_button_radius','2020-01-09 19:54:14'),('themes_button_radius2','2020-02-09 22:24:44'),('themes_capitalize_browse_categories','2020-02-09 22:24:45'),('themes_editions_button_colors','2020-08-02 14:44:38'),('themes_favicon','2019-11-18 17:53:51'),('themes_fonts','2019-11-18 17:53:53'),('themes_footer_design','2020-02-09 22:24:57'),('themes_header_buttons','2019-11-18 17:53:51'),('themes_header_colors','2019-11-18 17:53:50'),('themes_header_colors_2','2019-11-18 17:53:50'),('themes_link_color','2020-08-02 16:09:04'),('themes_link_hover_color','2020-08-12 15:04:24'),('themes_panel_body_design','2020-08-02 18:58:16'),('themes_panel_design','2020-06-19 20:20:59'),('themes_primary_colors','2019-11-18 17:53:52'),('themes_results_breadcrumbs','2020-08-12 15:04:26'),('themes_search_tools','2020-08-12 15:04:26'),('themes_secondary_colors','2019-11-18 17:53:53'),('themes_setup','2019-02-24 20:32:34'),('themes_sidebar_highlight_colors','2020-02-09 22:24:44'),('themes_tools_button_colors','2020-08-02 17:36:02'),('theme_app_header_logo','2025-03-04 15:54:16'),('theme_cover_default_image','2024-01-05 09:20:36'),('theme_defaults_for_logo_and_favicon','2020-09-04 12:26:28'),('theme_explore_more_images','2024-01-05 09:20:49'),('theme_format_category_icons','2024-01-05 09:20:36'),('theme_modal_dialog','2020-09-04 20:08:55'),('theme_name_length','2019-01-28 20:58:56'),('theme_reorganize_menu','2020-09-04 15:47:26'),('third_party_registration','2024-01-05 09:20:35'),('ticket_creation','2022-03-30 22:40:45'),('ticket_trends','2024-01-05 09:20:34'),('toggle_novelist_series','2024-06-25 15:05:20'),('track_axis360_record_usage','2020-09-29 21:40:16'),('track_axis360_stats','2020-09-29 21:40:16'),('track_axis360_user_usage','2020-09-29 21:40:16'),('track_cloud_library_record_usage','2019-11-18 17:53:55'),('track_cloud_library_user_usage','2019-11-18 17:53:55'),('track_ebscohost_user_usage','2022-06-24 13:38:50'),('track_ebsco_eds_user_usage','2020-07-21 13:33:42'),('track_event_length_in_minutes','2025-03-04 15:54:16'),('track_event_user_usage','2020-04-30 14:33:56'),('track_hoopla_record_usage','2019-11-18 17:53:48'),('track_hoopla_user_usage','2019-11-18 17:53:48'),('track_ils_record_usage','2019-11-18 17:53:45'),('track_ils_self_registrations','2020-04-30 14:33:48'),('track_ils_user_usage','2019-11-18 17:53:45'),('track_open_archive_record_usage','2019-11-18 17:53:54'),('track_open_archive_user_usage','2019-11-18 17:53:54'),('track_overdrive_record_usage','2019-11-18 17:53:48'),('track_overdrive_stats','2021-02-06 21:31:45'),('track_overdrive_user_usage','2019-11-18 17:53:47'),('track_palace_project_record_usage','2024-03-25 16:02:12'),('track_palace_project_user_usage','2024-03-25 16:02:12'),('track_pdf_downloads','2020-04-30 14:33:48'),('track_pdf_views','2020-07-08 14:56:03'),('track_rbdigital_magazine_usage','2019-11-18 17:53:49'),('track_rbdigital_record_usage','2019-11-18 17:53:49'),('track_rbdigital_user_usage','2019-11-18 17:53:49'),('track_sideload_record_usage','2019-11-18 17:53:47'),('track_sideload_user_usage','2019-11-18 17:53:47'),('track_spammy_urls_by_ip','2023-05-22 02:37:31'),('track_summon_user_usage','2024-03-25 16:02:11'),('track_supplemental_file_downloads','2020-05-14 19:25:09'),('track_website_user_usage','2019-11-18 17:53:55'),('translatable_text_blocks','2024-03-25 16:02:12'),('translations','2019-11-18 17:53:54'),('translation_case_sensitivity','2020-02-09 22:24:58'),('translation_map_regex','2019-01-28 20:58:57'),('translation_terms','2019-11-18 17:53:54'),('translation_term_case_sensitivity','2020-03-05 22:53:47'),('translation_term_default_text','2020-01-08 18:33:50'),('translation_term_increase_length','2020-07-29 19:28:26'),('translator_role','2019-11-18 17:53:54'),('treatBibOrItemHoldsAs','2021-08-23 18:45:52'),('treatLibraryUseOnlyGroupedStatusesAsAvailable','2022-04-13 13:57:12'),('treat_unknown_audience_as','2021-07-06 15:52:37'),('truncate_donation_form_fields','2023-03-02 22:15:39'),('twilio_settings','2024-01-05 09:20:37'),('two_factor_auth','2021-12-23 19:38:39'),('two_factor_authentication','2025-03-04 15:54:16'),('two_factor_auth_permission','2021-12-23 19:38:39'),('unknown_language_handling','2020-03-27 19:21:06'),('updateCatUsername','2024-01-05 09:20:48'),('updateDefaultConfiguration','2022-06-03 19:41:08'),('updateDefaultConfiguration2','2022-06-07 19:15:33'),('updateGroupedWorkFacetReadingtoAudience','2022-06-03 19:41:07'),('updateGroupedWorkFacetReadling','2022-06-03 19:41:07'),('updateThemes','2023-04-05 23:58:09'),('updateThemesFinal','2023-04-07 23:29:23'),('update_api_usage_uniqueness','2023-01-18 17:56:46'),('update_aspen_site_stats','2022-07-11 23:53:34'),('update_carlx_indexing_class','2024-01-05 09:20:43'),('update_cellphone_symphony','2024-01-05 09:20:52'),('update_collection_spotlight_number_of_titles','2024-01-05 09:20:35'),('update_community_engagement_module_name','2025-03-05 18:44:05'),('update_community_engagement_permissions','2025-03-05 18:44:05'),('update_community_engagement_roles','2025-03-05 18:44:05'),('update_cookie_management_preferences_more_options','2024-10-15 18:40:05'),('update_dates_scheduled_updates','2023-04-27 15:41:47'),('update_dates_scheduled_updates2','2023-05-03 00:41:10'),('update_default_boost_limits','2024-01-05 09:20:54'),('update_default_request_statuses','2024-10-15 18:40:04'),('update_default_request_statuses_2','2024-10-15 18:40:04'),('update_deluxe_remittance_id','2023-03-30 07:32:08'),('update_grouped_work_more_details','2019-12-19 15:29:27'),('update_indexes_for_grouped_works','2023-03-14 15:41:44'),('update_item_status','2021-08-23 18:45:52'),('update_list_entry_titles','2023-02-16 22:11:17'),('update_notification_onboarding_status','2024-01-05 09:20:36'),('update_notification_permissions','2022-10-06 14:57:02'),('update_overdrive_fulfillment','2022-01-11 18:52:41'),('update_plural_grouped_work_facet_label','2021-07-11 21:04:55'),('update_spotlight_sources','2020-06-10 12:22:31'),('update_useHomeLink','2021-04-20 23:30:13'),('update_useHomeLink_tinyint','2021-04-20 23:30:13'),('update_userAlertPreferences','2022-10-06 14:57:02'),('update_user_list_module_log_settings','2024-04-11 17:23:35'),('update_user_notification_onboard','2024-01-05 09:20:57'),('upload_list_cover_permissions','2021-05-29 19:03:49'),('username_field','2024-06-25 15:05:20'),('userPayments_addTransactionType','2021-11-19 22:02:06'),('userPaymentTransactionId','2021-11-21 17:29:02'),('userRatings1','2019-01-28 20:58:58'),('users_to_tasks','2022-12-12 15:26:49'),('user_account','2019-01-28 20:58:56'),('user_account_cache_volume_length','2021-04-08 13:50:40'),('user_account_summary_cache','2021-03-22 16:15:20'),('user_account_summary_expiration_date_extension','2021-04-08 13:50:39'),('user_account_summary_remaining_checkouts','2021-03-23 16:36:54'),('user_add_last_reading_history_update_time','2021-01-26 22:43:09'),('user_add_rbdigital_id','2019-11-18 17:53:44'),('user_add_rbdigital_username_password','2019-11-18 17:53:44'),('user_agent_tracking','2024-06-25 15:05:21'),('user_assign_role_by_ptype','2020-09-07 15:11:39'),('user_barcode_index','2024-01-05 09:20:52'),('user_browse_add_home','2022-12-09 21:56:32'),('user_cache_checkouts','2021-03-02 21:09:02'),('user_cache_holds','2021-03-02 17:55:07'),('user_checkout_add_ilsStatus','2025-03-04 15:54:16'),('user_checkout_add_showFineButton','2025-03-04 15:54:16'),('user_checkout_cache_additional_fields','2021-03-19 14:15:56'),('user_checkout_cache_renewal_information','2021-03-19 14:16:23'),('user_circulation_cache_callnumber_length','2021-05-03 20:49:18'),('user_circulation_cache_cover_link','2021-03-29 20:48:56'),('user_circulation_cache_grouped_work','2021-03-19 14:20:46'),('user_circulation_cache_indexes','2021-03-23 16:36:54'),('user_circulation_cache_overdrive_magazines','2021-03-24 17:23:25'),('user_circulation_cache_overdrive_supplemental_materials','2021-03-26 19:02:34'),('user_cookie_preference_analytics','2024-01-05 09:20:51'),('user_cookie_preference_essential','2024-01-05 09:20:51'),('user_cost_savings','2024-10-15 18:40:04'),('user_disableAccountLinking','2022-11-18 14:44:26'),('user_display_name','2019-01-28 20:58:56'),('user_display_name_length','2019-12-11 21:42:32'),('user_events_entry','2023-04-04 23:06:13'),('user_events_entry_length','2023-04-14 16:27:42'),('user_events_entry_location_length','2023-04-19 21:50:09'),('user_events_entry_unique','2023-04-15 21:06:25'),('user_events_registrations','2023-05-03 22:54:39'),('user_hideResearchStarters','2022-03-20 19:04:14'),('user_hold_format','2021-03-26 19:02:34'),('user_hoopla_confirmation_checkout','2019-01-28 20:58:56'),('user_hoopla_confirmation_checkout_prompt','2019-11-18 17:53:43'),('user_hoopla_confirmation_checkout_prompt2','2022-03-20 19:04:14'),('user_ilsType','2019-01-28 20:58:56'),('user_languages','2019-11-18 17:53:44'),('user_last_list_used','2020-06-10 12:22:31'),('user_last_login_validation','2020-06-19 20:20:57'),('user_last_name_length','2020-03-05 22:54:04'),('user_linking','2019-01-28 20:58:56'),('user_linking_1','2019-01-28 20:58:56'),('user_linking_disable_link','2019-11-18 17:53:43'),('user_link_blocking','2019-01-28 20:58:56'),('user_list_course_reserves','2021-10-15 23:06:03'),('user_list_entry','2019-01-28 20:59:02'),('user_list_entry_add_additional_types','2020-05-18 18:16:05'),('user_list_entry_length','2023-03-27 09:11:11'),('user_list_force_reindex_20_18','2021-01-03 21:18:50'),('user_list_import_information','2021-05-03 20:49:18'),('user_list_indexing','2019-01-28 20:59:02'),('user_list_indexing_log','2020-09-09 15:44:06'),('user_list_indexing_settings','2020-09-09 18:40:16'),('user_list_searching','2020-09-09 18:05:20'),('user_list_sorting','2019-01-28 20:59:02'),('user_locked_filters','2019-11-18 17:53:45'),('user_messages','2019-11-18 17:53:44'),('user_message_actions','2019-11-18 17:53:45'),('user_message_addendum','2022-12-01 02:32:10'),('user_message_related_object','2025-03-04 15:54:15'),('user_overdrive_auto_checkout','2019-11-18 17:53:45'),('user_overdrive_email','2019-01-28 20:58:56'),('user_password_length','2020-07-08 14:56:03'),('user_payments','2019-11-18 17:53:45'),('user_payments_cancelled','2021-07-11 21:04:57'),('user_payments_carlx','2021-02-06 21:31:44'),('user_payments_finesPaid','2021-02-06 21:31:44'),('user_payment_lines','2024-03-25 16:02:14'),('user_permissions','2020-09-03 17:44:19'),('user_permission_defaults','2020-09-03 17:44:20'),('user_phone','2019-01-28 20:58:56'),('user_phone_length','2019-11-18 17:53:43'),('user_preference_review_prompt','2019-01-28 20:58:56'),('user_preferred_library_interface','2019-01-28 20:58:56'),('user_reading_history_dates_in_past','2021-04-20 23:30:12'),('user_reading_history_index','2020-09-16 23:41:26'),('user_reading_history_index_source_id','2019-01-28 20:58:56'),('user_reading_history_work_index','2020-09-23 22:36:33'),('user_rememberHoldPickupLocation','2019-11-18 17:53:44'),('user_remove_college_major','2021-02-12 20:52:09'),('user_remove_default_created','2019-11-18 17:53:43'),('user_review_imported_from','2021-05-03 20:49:19'),('user_role_uniqueness','2022-03-20 19:04:14'),('user_secondary_library_card','2020-06-19 20:20:58'),('user_token_onboard_notifications','2024-01-05 09:20:36'),('user_track_reading_history','2019-01-28 20:58:56'),('user_update_messages','2020-07-29 17:40:19'),('user_username_increase_length','2021-03-29 20:48:55'),('use_library_themes_for_location','2023-04-15 21:06:25'),('usps_settings','2024-03-25 16:02:13'),('utf8mb4support','2021-09-30 19:35:59'),('utf8_update','2016-06-30 17:11:12'),('variables_full_index_warnings','2019-01-28 20:58:59'),('variables_lastHooplaExport','2019-01-28 20:58:57'),('variables_lastRbdigitalExport','2019-03-05 05:46:15'),('variables_offline_mode_when_offline_login_allowed','2019-01-28 20:58:59'),('variables_table','2019-01-28 20:58:59'),('variables_table_uniqueness','2019-01-28 20:58:59'),('variables_validateChecksumsFromDisk','2019-01-28 20:58:59'),('vdx_forms','2022-08-17 22:43:44'),('vdx_form_updates_locations','2022-08-29 14:08:08'),('vdx_hold_groups','2022-08-17 22:43:44'),('vdx_requests','2022-08-17 22:43:44'),('vdx_requests_2','2022-08-17 22:43:44'),('vdx_request_id','2022-08-22 18:57:13'),('vdx_settings','2022-08-17 22:43:44'),('vdx_setting_updates','2022-08-22 18:57:13'),('view_community_dashboard_permissions','2025-03-05 18:44:05'),('view_unpublished_content_permissions','2020-09-27 22:33:11'),('volume_display_order','2020-04-30 14:33:48'),('volume_increase_display_order','2020-12-04 17:08:22'),('volume_increase_field_lengths','2020-12-04 17:08:23'),('volume_information','2019-01-28 20:58:57'),('webpage_default_image','2024-01-05 09:20:37'),('website_crawlDelay','2022-05-02 19:45:05'),('website_facets','2024-01-05 09:20:47'),('website_facets_default','2024-01-05 09:20:47'),('website_indexing_tables','2019-11-18 17:53:55'),('website_index_log_num_invalid_pages','2023-01-04 19:15:06'),('website_pages_deletionReason','2021-12-17 21:36:10'),('website_record_usage','2019-11-18 17:53:55'),('website_usage_add_instance','2020-12-21 14:48:22'),('web_builder_add_cell_imageURL','2021-05-03 20:49:19'),('web_builder_add_cell_makeCellAccordion','2021-04-22 01:34:04'),('web_builder_add_frameHeight','2021-04-20 23:30:12'),('web_builder_add_settings','2021-02-12 01:49:54'),('web_builder_basic_pages','2020-07-22 12:49:58'),('web_builder_basic_page_teaser','2020-07-22 12:49:58'),('web_builder_categories_and_audiences','2020-07-22 12:50:01'),('web_builder_custom_forms','2020-07-22 12:50:01'),('web_builder_custom_form_increase_email','2024-10-15 18:40:03'),('web_builder_custom_from_submission_isRead','2021-03-31 13:24:43'),('web_builder_custom_page_categories','2020-12-04 17:08:23'),('web_builder_image_upload','2020-07-22 12:49:59'),('web_builder_image_upload_additional_sizes','2020-07-22 12:49:59'),('web_builder_last_update_timestamps','2020-07-22 12:50:00'),('web_builder_menu','2020-07-22 12:49:58'),('web_builder_menu_show_when','2020-07-22 12:49:59'),('web_builder_menu_sorting','2020-07-22 12:49:58'),('web_builder_module','2020-07-22 12:49:58'),('web_builder_module_monitoring_and_indexing','2020-11-09 19:22:45'),('web_builder_portal','2020-07-22 12:49:59'),('web_builder_portal_cell_markdown','2020-07-22 12:50:01'),('web_builder_portal_cell_source_info','2020-07-22 12:50:01'),('web_builder_portal_cell_title','2020-10-27 17:06:36'),('web_builder_portal_weights','2020-07-22 12:49:59'),('web_builder_quick_polls','2024-01-05 09:20:42'),('web_builder_remove_show_sidebar','2020-10-27 17:06:37'),('web_builder_resources','2020-07-22 12:50:00'),('web_builder_resource_access_library','2024-10-15 18:40:04'),('web_builder_resource_in_library','2020-07-22 12:50:01'),('web_builder_resource_open_in_new_tab','2021-01-03 21:18:50'),('web_builder_resource_teaser','2020-07-22 12:50:00'),('web_builder_roles','2020-10-27 17:06:36'),('web_builder_scope_by_library','2020-07-22 12:50:00'),('web_indexer_add_description_expression','2020-08-07 14:36:05'),('web_indexer_add_paths_to_exclude','2019-12-03 14:45:40'),('web_indexer_add_title_expression','2020-08-07 14:36:05'),('web_indexer_deleted_settings','2020-12-04 17:08:23'),('web_indexer_max_pages_to_index','2021-02-12 01:49:54'),('web_indexer_module_add_log','2020-03-31 18:45:02'),('web_indexer_scoping','2020-12-14 17:03:19'),('web_indexer_url_length','2021-05-18 13:12:51'),('web_resource_generate_placard','2025-03-04 15:54:16'),('web_resource_usage','2022-01-05 16:27:08'),('work_level_ratings','2019-01-28 20:59:02'),('work_level_tagging','2019-01-28 20:59:02'),('worldpay_settings','2021-07-12 16:44:30'),('worldpay_setting_typo','2021-07-14 12:31:50'),('xpressPay_settings','2022-04-08 21:00:43'),('year_in_review_permissions','2025-03-04 15:54:15'),('year_in_review_settings','2025-03-04 15:54:15');
/*!40000 ALTER TABLE `db_update` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

DROP TABLE IF EXISTS deluxe_certified_payments_settings;
CREATE TABLE `deluxe_certified_payments_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `sandboxMode` tinyint(1) DEFAULT 0,
  `applicationId` varchar(500) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS development_epic;
CREATE TABLE `development_epic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `linkToDesign` varchar(255) DEFAULT NULL,
  `linkToRequirements` varchar(255) DEFAULT NULL,
  `internalComments` mediumtext DEFAULT NULL,
  `dueDate` char(10) DEFAULT NULL,
  `dueDateComment` varchar(255) DEFAULT NULL,
  `privateStatus` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS development_epic_partner_link;
CREATE TABLE `development_epic_partner_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partnerId` int(11) DEFAULT NULL,
  `epicId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `partnerId` (`partnerId`,`epicId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS development_priorities;
CREATE TABLE `development_priorities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `priority1` varchar(50) DEFAULT NULL,
  `priority2` varchar(50) DEFAULT NULL,
  `priority3` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS development_sprint;
CREATE TABLE `development_sprint` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `startDate` date DEFAULT NULL,
  `endDate` date DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS development_task;
CREATE TABLE `development_task` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskType` int(11) DEFAULT 0,
  `name` varchar(255) NOT NULL,
  `dueDate` char(10) DEFAULT NULL,
  `dueDateComment` varchar(255) DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `releaseId` int(11) DEFAULT 0,
  `status` int(11) DEFAULT 0,
  `storyPoints` float DEFAULT NULL,
  `devTestingNotes` mediumtext DEFAULT NULL,
  `qaFeedback` mediumtext DEFAULT NULL,
  `releaseNoteText` text DEFAULT NULL,
  `newSettingsAdded` text DEFAULT NULL,
  `suggestedForCommunityDev` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS development_task_developer_link;
CREATE TABLE `development_task_developer_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) DEFAULT NULL,
  `taskId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userId` (`userId`,`taskId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS development_task_epic_link;
CREATE TABLE `development_task_epic_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `epicId` int(11) DEFAULT NULL,
  `taskId` int(11) DEFAULT NULL,
  `weight` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `epicId` (`epicId`,`taskId`),
  KEY `epicId_2` (`epicId`,`weight`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS development_task_partner_link;
CREATE TABLE `development_task_partner_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partnerId` int(11) DEFAULT NULL,
  `taskId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `partnerId` (`partnerId`,`taskId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS development_task_qa_link;
CREATE TABLE `development_task_qa_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) DEFAULT NULL,
  `taskId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userId` (`userId`,`taskId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS development_task_sprint_link;
CREATE TABLE `development_task_sprint_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sprintId` int(11) DEFAULT NULL,
  `taskId` int(11) DEFAULT NULL,
  `weight` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sprintId` (`sprintId`,`taskId`),
  KEY `sprintId_2` (`sprintId`,`weight`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS development_task_ticket_link;
CREATE TABLE `development_task_ticket_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticketId` int(11) DEFAULT NULL,
  `taskId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticketId` (`ticketId`,`taskId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS donations;
CREATE TABLE `donations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paymentId` int(11) DEFAULT NULL,
  `firstName` varchar(256) DEFAULT NULL,
  `lastName` varchar(256) DEFAULT NULL,
  `email` varchar(256) DEFAULT NULL,
  `anonymous` tinyint(1) DEFAULT 0,
  `comments` mediumtext DEFAULT NULL,
  `dedicate` tinyint(1) DEFAULT 0,
  `dedicateType` int(11) DEFAULT NULL,
  `honoreeFirstName` varchar(256) DEFAULT NULL,
  `honoreeLastName` varchar(256) DEFAULT NULL,
  `sendEmailToUser` tinyint(1) DEFAULT 0,
  `donateToLocationId` int(11) DEFAULT NULL,
  `donationSettingId` int(11) DEFAULT NULL,
  `donateToLocation` varchar(60) DEFAULT NULL,
  `shouldBeNotified` tinyint(1) DEFAULT 0,
  `notificationFirstName` varchar(75) DEFAULT NULL,
  `notificationLastName` varchar(75) DEFAULT NULL,
  `notificationAddress` varchar(75) DEFAULT NULL,
  `notificationCity` varchar(75) DEFAULT NULL,
  `notificationState` varchar(75) DEFAULT NULL,
  `notificationZip` varchar(75) DEFAULT NULL,
  `address` varchar(50) DEFAULT NULL,
  `address2` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `zip` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS donations_dedicate_type;
CREATE TABLE `donations_dedicate_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(75) DEFAULT NULL,
  `donationSettingId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS donations_earmark;
CREATE TABLE `donations_earmark` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(75) DEFAULT NULL,
  `weight` smallint(6) DEFAULT NULL,
  `donationSettingId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS donations_settings;
CREATE TABLE `donations_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `allowDonationsToBranch` tinyint(1) DEFAULT 0,
  `allowDonationEarmark` tinyint(1) DEFAULT 0,
  `allowDonationDedication` tinyint(1) DEFAULT 0,
  `donationsContent` longtext DEFAULT NULL,
  `donationEmailTemplate` text DEFAULT NULL,
  `requiresAddressInfo` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS donations_value;
CREATE TABLE `donations_value` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` int(11) DEFAULT NULL,
  `isDefault` tinyint(1) DEFAULT 0,
  `donationSettingId` int(11) DEFAULT NULL,
  `weight` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS dpla_api_settings;
CREATE TABLE `dpla_api_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `apiKey` varchar(32) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ebsco_eds_settings;
CREATE TABLE `ebsco_eds_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `edsApiProfile` varchar(50) DEFAULT '',
  `edsSearchProfile` varchar(50) DEFAULT '',
  `edsApiUsername` varchar(50) DEFAULT '',
  `edsApiPassword` varchar(255) DEFAULT NULL,
  `fullTextLimiter` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ebsco_eds_usage;
CREATE TABLE `ebsco_eds_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ebscoId` varchar(100) NOT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `timesViewedInSearch` int(11) NOT NULL,
  `timesUsed` int(11) NOT NULL,
  `instance` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`ebscoId`,`year`,`month`),
  UNIQUE KEY `instance_2` (`instance`,`ebscoId`,`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ebsco_research_starter;
CREATE TABLE `ebsco_research_starter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ebscoId` varchar(100) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ebscoId` (`ebscoId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ebsco_research_starter_dismissals;
CREATE TABLE `ebsco_research_starter_dismissals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `researchStarterId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userId` (`userId`,`researchStarterId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ebscohost_database;
CREATE TABLE `ebscohost_database` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `searchSettingId` int(11) NOT NULL,
  `shortName` varchar(50) NOT NULL,
  `displayName` varchar(50) NOT NULL,
  `allowSearching` tinyint(4) DEFAULT 1,
  `searchByDefault` tinyint(4) DEFAULT 1,
  `showInExploreMore` tinyint(4) DEFAULT 0,
  `showInCombinedResults` tinyint(4) DEFAULT 0,
  `logo` varchar(512) DEFAULT '',
  `hasDateAndRelevancySorting` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ebscohost_facet;
CREATE TABLE `ebscohost_facet` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shortName` varchar(50) NOT NULL,
  `displayName` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shortName` (`shortName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ebscohost_search_options;
CREATE TABLE `ebscohost_search_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `settingId` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ebscohost_settings;
CREATE TABLE `ebscohost_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `profileId` varchar(50) DEFAULT '',
  `profilePwd` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ebscohost_usage;
CREATE TABLE `ebscohost_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance` varchar(100) DEFAULT NULL,
  `ebscohostId` varchar(50) NOT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `timesViewedInSearch` int(11) NOT NULL,
  `timesUsed` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ebscohostId` (`ebscohostId`,`year`,`instance`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS email_template;
CREATE TABLE `email_template` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `templateType` varchar(50) NOT NULL,
  `languageCode` char(3) NOT NULL,
  `subject` varchar(998) NOT NULL,
  `plainTextBody` mediumtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS errors;
CREATE TABLE `errors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `url` mediumtext DEFAULT NULL,
  `message` mediumtext DEFAULT NULL,
  `backtrace` mediumtext DEFAULT NULL,
  `timestamp` int(11) DEFAULT NULL,
  `userAgent` mediumtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS event;
CREATE TABLE `event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eventTypeId` int(11) NOT NULL,
  `locationId` int(11) NOT NULL,
  `sublocationId` int(11) DEFAULT NULL,
  `title` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `cover` varchar(100) DEFAULT NULL,
  `private` tinyint(1) NOT NULL DEFAULT 1,
  `startDate` date NOT NULL DEFAULT curdate(),
  `startTime` time NOT NULL DEFAULT curtime(),
  `eventLength` int(11) NOT NULL DEFAULT 60,
  `recurrenceOption` tinyint(4) DEFAULT NULL,
  `recurrenceFrequency` tinyint(4) DEFAULT NULL,
  `recurrenceInterval` int(11) NOT NULL DEFAULT 1,
  `weekDays` varchar(25) DEFAULT NULL,
  `monthlyOption` tinyint(4) DEFAULT NULL,
  `monthDay` tinyint(4) DEFAULT NULL,
  `monthDate` tinyint(4) DEFAULT NULL,
  `monthOffset` tinyint(4) DEFAULT NULL,
  `endOption` tinyint(4) DEFAULT NULL,
  `recurrenceEnd` date DEFAULT NULL,
  `recurrenceCount` int(11) DEFAULT NULL,
  `dateUpdated` int(11) DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
DROP TABLE IF EXISTS event_event_field;
CREATE TABLE `event_event_field` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eventId` int(11) NOT NULL,
  `eventFieldId` int(11) NOT NULL,
  `value` varchar(150) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
DROP TABLE IF EXISTS event_field;
CREATE TABLE `event_field` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(100) NOT NULL,
  `type` tinyint(4) NOT NULL DEFAULT 0,
  `allowableValues` text DEFAULT NULL,
  `defaultValue` varchar(150) DEFAULT NULL,
  `facetName` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
DROP TABLE IF EXISTS event_field_set;
CREATE TABLE `event_field_set` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
DROP TABLE IF EXISTS event_field_set_field;
CREATE TABLE `event_field_set_field` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eventFieldId` int(11) NOT NULL,
  `eventFieldSetId` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
DROP TABLE IF EXISTS event_instance;
CREATE TABLE `event_instance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eventId` int(11) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `length` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `note` text DEFAULT NULL,
  `dateUpdated` int(11) DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
DROP TABLE IF EXISTS event_library_map_values;
CREATE TABLE `event_library_map_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aspenLocation` varchar(255) NOT NULL,
  `eventsLocation` varchar(255) NOT NULL,
  `locationId` int(11) NOT NULL,
  `libraryId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `locationId` (`locationId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS event_type;
CREATE TABLE `event_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eventFieldSetId` int(11) NOT NULL,
  `title` varchar(50) NOT NULL,
  `titleCustomizable` tinyint(1) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `descriptionCustomizable` tinyint(1) NOT NULL DEFAULT 1,
  `cover` varchar(100) DEFAULT NULL,
  `coverCustomizable` tinyint(1) NOT NULL DEFAULT 1,
  `eventLength` float NOT NULL DEFAULT 1,
  `lengthCustomizable` tinyint(1) NOT NULL DEFAULT 1,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
DROP TABLE IF EXISTS event_type_library;
CREATE TABLE `event_type_library` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eventTypeId` int(11) NOT NULL,
  `libraryId` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
DROP TABLE IF EXISTS event_type_location;
CREATE TABLE `event_type_location` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eventTypeId` int(11) NOT NULL,
  `locationId` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
DROP TABLE IF EXISTS events_facet;
CREATE TABLE `events_facet` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `facetGroupId` int(11) NOT NULL,
  `displayName` varchar(50) NOT NULL,
  `displayNamePlural` varchar(50) DEFAULT NULL,
  `facetName` varchar(50) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT 0,
  `numEntriesToShowByDefault` int(11) NOT NULL DEFAULT 5,
  `showAsDropDown` tinyint(4) NOT NULL DEFAULT 0,
  `sortMode` enum('alphabetically','num_results') NOT NULL DEFAULT 'num_results',
  `showAboveResults` tinyint(4) NOT NULL DEFAULT 0,
  `showInResults` tinyint(4) NOT NULL DEFAULT 1,
  `showInAdvancedSearch` tinyint(4) NOT NULL DEFAULT 1,
  `collapseByDefault` tinyint(4) DEFAULT 1,
  `useMoreFacetPopup` tinyint(4) DEFAULT 1,
  `translate` tinyint(4) DEFAULT 0,
  `multiSelect` tinyint(4) DEFAULT 0,
  `canLock` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `groupFacet` (`facetGroupId`,`facetName`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
DROP TABLE IF EXISTS events_facet_groups;
CREATE TABLE `events_facet_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `eventFacetCountsToShow` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
DROP TABLE IF EXISTS events_indexing_log;
CREATE TABLE `events_indexing_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log entry',
  `name` varchar(150) NOT NULL,
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `notes` mediumtext DEFAULT NULL COMMENT 'Additional information about the run',
  `numEvents` int(11) DEFAULT 0,
  `numErrors` int(11) DEFAULT 0,
  `numAdded` int(11) DEFAULT 0,
  `numDeleted` int(11) DEFAULT 0,
  `numUpdated` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `startTime` (`startTime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS events_indexing_settings;
CREATE TABLE `events_indexing_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numberOfDaysToIndex` int(11) DEFAULT 365,
  `runFullUpdate` tinyint(1) DEFAULT 0,
  `lastUpdateOfAllEvents` int(11) DEFAULT NULL,
  `lastUpdateOfChangedEvents` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
DROP TABLE IF EXISTS events_spotlights;
CREATE TABLE `events_spotlights` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `showNameAsTitle` tinyint(1) DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `showDescription` tinyint(1) DEFAULT 0,
  `showEventImages` tinyint(1) DEFAULT 1,
  `showEventDescriptions` tinyint(1) DEFAULT 1,
  `searchTerm` varchar(500) NOT NULL DEFAULT '',
  `defaultFilter` mediumtext DEFAULT NULL,
  `defaultSort` enum('relevance','start_date_sort','title_sort') DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS events_usage;
CREATE TABLE `events_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(25) NOT NULL,
  `source` int(11) NOT NULL,
  `identifier` varchar(36) NOT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `timesViewedInSearch` int(11) NOT NULL,
  `timesUsed` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`,`source`,`identifier`,`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS external_request_log;
CREATE TABLE `external_request_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requestUrl` varchar(400) DEFAULT NULL,
  `requestHeaders` text DEFAULT NULL,
  `requestBody` text DEFAULT NULL,
  `response` mediumtext DEFAULT NULL,
  `responseCode` int(11) DEFAULT NULL,
  `requestTime` int(11) DEFAULT NULL,
  `requestType` varchar(50) DEFAULT NULL,
  `requestMethod` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `requestUrl` (`requestUrl`),
  KEY `requestTime` (`requestTime`),
  KEY `responseCode` (`responseCode`),
  KEY `requestType` (`requestType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS failed_logins_by_ip_address;
CREATE TABLE `failed_logins_by_ip_address` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ipAddress` varchar(25) DEFAULT NULL,
  `timestamp` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ipAddress` (`ipAddress`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
DROP TABLE IF EXISTS file_uploads;
CREATE TABLE `file_uploads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `fullPath` varchar(512) NOT NULL,
  `type` varchar(25) NOT NULL,
  `thumbFullPath` varchar(512) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS format_map_values;
CREATE TABLE `format_map_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `indexingProfileId` int(11) NOT NULL,
  `value` varchar(50) NOT NULL,
  `format` varchar(255) NOT NULL,
  `formatCategory` varchar(255) NOT NULL,
  `formatBoost` tinyint(4) NOT NULL,
  `suppress` tinyint(1) DEFAULT 0,
  `holdType` enum('bib','item','either','none') DEFAULT 'bib',
  `inLibraryUseOnly` tinyint(1) DEFAULT 0,
  `pickupAt` tinyint(1) DEFAULT 0,
  `appliesToBibLevel` tinyint(1) DEFAULT 1,
  `appliesToItemShelvingLocation` tinyint(1) DEFAULT 1,
  `appliesToItemSublocation` tinyint(1) DEFAULT 1,
  `appliesToItemCollection` tinyint(1) DEFAULT 1,
  `appliesToItemType` tinyint(1) DEFAULT 1,
  `appliesToItemFormat` tinyint(1) DEFAULT 1,
  `appliesToMatType` tinyint(1) DEFAULT 1,
  `appliesToFallbackFormat` tinyint(1) DEFAULT 1,
  `displaySierraCheckoutGrid` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `indexingProfileId` (`indexingProfileId`,`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS google_api_settings;
CREATE TABLE `google_api_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `googleBooksKey` varchar(50) DEFAULT NULL,
  `googleAnalyticsTrackingId` varchar(50) DEFAULT NULL,
  `googleAnalyticsLinkingId` varchar(50) DEFAULT NULL,
  `googleAnalyticsLinkedProperties` longtext DEFAULT NULL,
  `googleAnalyticsDomainName` varchar(100) DEFAULT NULL,
  `googleMapsKey` varchar(60) DEFAULT NULL,
  `googleAnalyticsVersion` varchar(5) DEFAULT 'v3',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS grapes_templates;
CREATE TABLE `grapes_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `templateName` varchar(100) NOT NULL DEFAULT ' ',
  `templateContent` text NOT NULL,
  `htmlData` text DEFAULT NULL,
  `cssData` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS grapes_web_builder;
CREATE TABLE `grapes_web_builder` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `urlAlias` varchar(100) DEFAULT NULL,
  `teaser` varchar(512) DEFAULT NULL,
  `templatesSelect` int(11) DEFAULT -1,
  `templateContent` text DEFAULT NULL,
  `grapesGenId` varchar(100) NOT NULL DEFAULT '',
  `htmlData` text DEFAULT NULL,
  `cssData` text DEFAULT NULL,
  `showTitleOnPage` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS greenhouse_cache;
CREATE TABLE `greenhouse_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `locationId` int(11) DEFAULT NULL,
  `libraryId` int(11) DEFAULT NULL,
  `solrScope` varchar(75) DEFAULT NULL,
  `latitude` varchar(75) DEFAULT NULL,
  `longitude` varchar(75) DEFAULT NULL,
  `unit` varchar(3) DEFAULT NULL,
  `baseUrl` varchar(255) DEFAULT NULL,
  `lastUpdated` int(11) DEFAULT NULL,
  `siteId` int(11) DEFAULT NULL,
  `releaseChannel` tinyint(1) DEFAULT 0,
  `logo` varchar(255) DEFAULT NULL,
  `favicon` varchar(255) DEFAULT NULL,
  `primaryBackgroundColor` varchar(25) DEFAULT NULL,
  `primaryForegroundColor` varchar(25) DEFAULT NULL,
  `secondaryBackgroundColor` varchar(25) DEFAULT NULL,
  `secondaryForegroundColor` varchar(25) DEFAULT NULL,
  `tertiaryBackgroundColor` varchar(25) DEFAULT NULL,
  `tertiaryForegroundColor` varchar(25) DEFAULT NULL,
  `version` varchar(25) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS greenhouse_settings;
CREATE TABLE `greenhouse_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `greenhouseAlertSlackHook` varchar(255) DEFAULT NULL,
  `apiKey1` varchar(256) DEFAULT NULL,
  `apiKey2` varchar(256) DEFAULT NULL,
  `apiKey3` varchar(256) DEFAULT NULL,
  `apiKey4` varchar(256) DEFAULT NULL,
  `apiKey5` varchar(256) DEFAULT NULL,
  `notificationAccessToken` varchar(256) DEFAULT NULL,
  `requestTrackerAuthToken` varchar(50) DEFAULT NULL,
  `requestTrackerBaseUrl` varchar(100) DEFAULT NULL,
  `expoEASBuildWebhookKey` varchar(256) DEFAULT NULL,
  `sendBuildTrackerAlert` tinyint(1) DEFAULT 0,
  `expoEASSubmitWebhookKey` varchar(256) DEFAULT NULL,
  `greenhouseSystemsAlertSlackHook` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS grouped_work;
CREATE TABLE `grouped_work` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `permanent_id` char(40) NOT NULL,
  `author` varchar(50) DEFAULT NULL,
  `grouping_category` varchar(25) NOT NULL,
  `full_title` varchar(500) NOT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `referenceCover` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permanent_id` (`permanent_id`),
  KEY `date_updated` (`date_updated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS grouped_work_alternate_titles;
CREATE TABLE `grouped_work_alternate_titles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `permanent_id` char(40) NOT NULL,
  `alternateTitle` varchar(709) DEFAULT NULL,
  `alternateAuthor` varchar(50) DEFAULT NULL,
  `addedBy` int(11) DEFAULT NULL,
  `dateAdded` int(11) DEFAULT NULL,
  `alternateGroupingCategory` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `permanent_id` (`permanent_id`),
  KEY `alternateTitle` (`alternateTitle`,`alternateAuthor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS grouped_work_debug_info;
CREATE TABLE `grouped_work_debug_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `permanent_id` char(40) NOT NULL,
  `debugInfo` mediumtext DEFAULT NULL,
  `debugTime` int(11) DEFAULT NULL,
  `processed` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permanent_id` (`permanent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS grouped_work_display_info;
CREATE TABLE `grouped_work_display_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `permanent_id` char(40) NOT NULL,
  `title` varchar(500) DEFAULT NULL,
  `author` varchar(50) DEFAULT NULL,
  `seriesName` varchar(255) DEFAULT NULL,
  `seriesDisplayOrder` decimal(6,2) DEFAULT NULL,
  `addedBy` int(11) DEFAULT NULL,
  `dateAdded` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permanent_id` (`permanent_id`),
  UNIQUE KEY `permanent_id_3` (`permanent_id`),
  KEY `permanent_id_2` (`permanent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS grouped_work_display_settings;
CREATE TABLE `grouped_work_display_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `applyNumberOfHoldingsBoost` tinyint(4) DEFAULT 1,
  `showSearchTools` tinyint(4) DEFAULT 1,
  `showQuickCopy` tinyint(4) DEFAULT 1,
  `showInSearchResultsMainDetails` varchar(512) DEFAULT 'a:5:{i:0;s:10:"showSeries";i:1;s:13:"showPublisher";i:2;s:19:"showPublicationDate";i:3;s:13:"showLanguages";i:4;s:10:"showArInfo";}',
  `alwaysShowSearchResultsMainDetails` tinyint(4) DEFAULT 0,
  `availabilityToggleLabelSuperScope` varchar(50) DEFAULT 'Entire Collection',
  `availabilityToggleLabelLocal` varchar(50) DEFAULT '{display name}',
  `availabilityToggleLabelAvailable` varchar(50) DEFAULT 'Available Now',
  `availabilityToggleLabelAvailableOnline` varchar(50) DEFAULT 'Available Online',
  `baseAvailabilityToggleOnLocalHoldingsOnly` tinyint(1) DEFAULT 1,
  `includeOnlineMaterialsInAvailableToggle` tinyint(1) DEFAULT 1,
  `includeAllRecordsInShelvingFacets` tinyint(4) DEFAULT 0,
  `includeAllRecordsInDateAddedFacets` tinyint(4) DEFAULT 0,
  `includeOutOfSystemExternalLinks` tinyint(4) DEFAULT 0,
  `facetGroupId` int(11) DEFAULT 0,
  `showStandardReviews` tinyint(4) DEFAULT 1,
  `showGoodReadsReviews` tinyint(4) DEFAULT 1,
  `preferSyndeticsSummary` tinyint(4) DEFAULT 1,
  `showSimilarTitles` tinyint(4) DEFAULT 1,
  `showSimilarAuthors` tinyint(4) DEFAULT 1,
  `showRatings` tinyint(4) DEFAULT 1,
  `showComments` tinyint(4) DEFAULT 1,
  `hideCommentsWithBadWords` tinyint(4) DEFAULT 0,
  `show856LinksAsTab` tinyint(4) DEFAULT 1,
  `showCheckInGrid` tinyint(4) DEFAULT 1,
  `showStaffView` tinyint(4) DEFAULT 1,
  `showLCSubjects` tinyint(4) DEFAULT 1,
  `showBisacSubjects` tinyint(4) DEFAULT 1,
  `showFastAddSubjects` tinyint(4) DEFAULT 1,
  `showOtherSubjects` tinyint(4) DEFAULT 1,
  `showInMainDetails` varchar(500) DEFAULT NULL,
  `defaultAvailabilityToggle` varchar(20) DEFAULT 'global',
  `isDefault` tinyint(4) DEFAULT 0,
  `showItemDueDates` tinyint(1) DEFAULT 1,
  `facetCountsToShow` tinyint(4) DEFAULT 1,
  `alwaysFlagNewTitles` tinyint(1) DEFAULT 0,
  `showRelatedRecordLabels` tinyint(1) DEFAULT 1,
  `sortOwnedEditionsFirst` tinyint(1) DEFAULT 0,
  `show856LinksAsAccessOnlineButtons` tinyint(1) DEFAULT 0,
  `showSearchToolsAtTop` tinyint(1) DEFAULT 0,
  `showEditionCovers` tinyint(1) DEFAULT 0,
  `searchSpecVersion` tinyint(1) DEFAULT 2,
  `limitBoosts` tinyint(1) DEFAULT 1,
  `maxTotalBoost` int(11) DEFAULT 500,
  `maxPopularityBoost` int(11) DEFAULT 25,
  `maxFormatBoost` int(11) DEFAULT 25,
  `maxHoldingsBoost` int(11) DEFAULT 25,
  `showItemNotes` tinyint(1) DEFAULT 1,
  `formatSortingGroupId` int(11) DEFAULT 1,
  `preferIlsDescription` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS grouped_work_facet;
CREATE TABLE `grouped_work_facet` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `facetGroupId` int(11) NOT NULL,
  `displayName` varchar(50) NOT NULL,
  `facetName` varchar(50) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT 0,
  `numEntriesToShowByDefault` int(11) NOT NULL DEFAULT 5,
  `showAsDropDown` tinyint(4) NOT NULL DEFAULT 0,
  `sortMode` enum('alphabetically','num_results') NOT NULL DEFAULT 'num_results',
  `showAboveResults` tinyint(4) NOT NULL DEFAULT 0,
  `showInResults` tinyint(4) NOT NULL DEFAULT 1,
  `showInAdvancedSearch` tinyint(4) NOT NULL DEFAULT 1,
  `collapseByDefault` tinyint(4) DEFAULT 1,
  `useMoreFacetPopup` tinyint(4) DEFAULT 1,
  `translate` tinyint(4) DEFAULT 0,
  `multiSelect` tinyint(4) DEFAULT 0,
  `canLock` tinyint(4) DEFAULT 0,
  `displayNamePlural` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `groupFacet` (`facetGroupId`,`facetName`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS grouped_work_facet_groups;
CREATE TABLE `grouped_work_facet_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS grouped_work_format_sort;
CREATE TABLE `grouped_work_format_sort` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `formatSortingGroupId` int(11) NOT NULL,
  `groupingCategory` varchar(6) NOT NULL,
  `format` varchar(255) NOT NULL,
  `weight` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `formatSortingGroupId` (`formatSortingGroupId`,`groupingCategory`,`format`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS grouped_work_format_sort_group;
CREATE TABLE `grouped_work_format_sort_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `bookSortMethod` tinyint(1) DEFAULT 1,
  `comicSortMethod` tinyint(1) DEFAULT 1,
  `movieSortMethod` tinyint(1) DEFAULT 1,
  `musicSortMethod` tinyint(1) DEFAULT 1,
  `otherSortMethod` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS grouped_work_more_details;
CREATE TABLE `grouped_work_more_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `weight` int(11) NOT NULL DEFAULT 0,
  `source` varchar(25) NOT NULL,
  `collapseByDefault` tinyint(1) DEFAULT NULL,
  `groupedWorkSettingsId` int(11) NOT NULL DEFAULT -1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS grouped_work_primary_identifiers;
CREATE TABLE `grouped_work_primary_identifiers` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `grouped_work_id` bigint(20) NOT NULL,
  `type` varchar(50) NOT NULL,
  `identifier` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `type` (`type`,`identifier`),
  KEY `grouped_record_id` (`grouped_work_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS grouped_work_record_item_url;
CREATE TABLE `grouped_work_record_item_url` (
  `groupedWorkItemId` int(11) DEFAULT NULL,
  `scopeId` int(11) DEFAULT NULL,
  `url` varchar(1000) DEFAULT NULL,
  UNIQUE KEY `groupedWorkItemId` (`groupedWorkItemId`,`scopeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS grouped_work_record_items;
CREATE TABLE `grouped_work_record_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupedWorkRecordId` int(11) NOT NULL,
  `groupedWorkVariationId` int(11) NOT NULL,
  `itemId` varchar(255) DEFAULT NULL,
  `shelfLocationId` int(11) DEFAULT NULL,
  `callNumberId` int(11) DEFAULT NULL,
  `sortableCallNumberId` int(11) DEFAULT NULL,
  `numCopies` int(11) DEFAULT NULL,
  `isOrderItem` tinyint(4) DEFAULT 0,
  `statusId` int(11) DEFAULT NULL,
  `dateAdded` bigint(20) DEFAULT NULL,
  `locationCodeId` int(11) DEFAULT NULL,
  `subLocationCodeId` int(11) DEFAULT NULL,
  `lastCheckInDate` bigint(20) DEFAULT NULL,
  `groupedStatusId` int(11) DEFAULT NULL,
  `available` tinyint(1) DEFAULT NULL,
  `holdable` tinyint(1) DEFAULT NULL,
  `inLibraryUseOnly` tinyint(1) DEFAULT NULL,
  `locationOwnedScopes` text DEFAULT _utf8mb4'~',
  `libraryOwnedScopes` text DEFAULT _utf8mb4'~',
  `recordIncludedScopes` text DEFAULT _utf8mb4'~',
  `isVirtual` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `itemId` (`itemId`,`groupedWorkRecordId`),
  KEY `groupedWorkRecordId` (`groupedWorkRecordId`),
  KEY `groupedWorkVariationId` (`groupedWorkVariationId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS grouped_work_records;
CREATE TABLE `grouped_work_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupedWorkId` int(11) NOT NULL,
  `sourceId` int(11) DEFAULT NULL,
  `recordIdentifier` varchar(125) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `formatId` int(11) DEFAULT NULL,
  `formatCategoryId` int(11) DEFAULT NULL,
  `editionId` int(11) DEFAULT NULL,
  `publisherId` int(11) DEFAULT NULL,
  `publicationDateId` int(11) DEFAULT NULL,
  `physicalDescriptionId` int(11) DEFAULT NULL,
  `languageId` int(11) DEFAULT NULL,
  `isClosedCaptioned` tinyint(1) DEFAULT 0,
  `hasParentRecord` tinyint(1) NOT NULL DEFAULT 0,
  `hasChildRecord` tinyint(1) NOT NULL DEFAULT 0,
  `placeOfPublicationId` int(11) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sourceId` (`sourceId`,`recordIdentifier`),
  KEY `groupedWorkId` (`groupedWorkId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS grouped_work_scheduled_index;
CREATE TABLE `grouped_work_scheduled_index` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `permanent_id` char(40) NOT NULL,
  `indexAfter` int(11) NOT NULL,
  `processed` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `allfields` (`processed`,`indexAfter`,`permanent_id`),
  KEY `permanent_id` (`permanent_id`),
  KEY `permanent_id_with_date` (`permanent_id`,`indexAfter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS grouped_work_test_search;
CREATE TABLE `grouped_work_test_search` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `searchTerm` text CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `expectedGroupedWorks` text DEFAULT NULL,
  `unexpectedGroupedWorks` text DEFAULT NULL,
  `status` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `searchIndex` varchar(40) DEFAULT 'Keyword',
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS grouped_work_variation;
CREATE TABLE `grouped_work_variation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupedWorkId` int(11) NOT NULL,
  `primaryLanguageId` int(11) DEFAULT NULL,
  `eContentSourceId` int(11) DEFAULT NULL,
  `formatId` int(11) DEFAULT NULL,
  `formatCategoryId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniqueness` (`groupedWorkId`,`primaryLanguageId`,`eContentSourceId`,`formatId`,`formatCategoryId`),
  KEY `groupedWorkId` (`groupedWorkId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS hide_series;
CREATE TABLE `hide_series` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seriesTerm` varchar(512) NOT NULL,
  `seriesNormalized` varchar(512) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `seriesTerm` (`seriesTerm`),
  UNIQUE KEY `seriesNormalized` (`seriesNormalized`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS hide_subject_facets;
CREATE TABLE `hide_subject_facets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subjectTerm` varchar(512) NOT NULL,
  `subjectNormalized` varchar(512) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subjectTerm` (`subjectTerm`),
  UNIQUE KEY `subjectNormalized` (`subjectNormalized`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS hold_group_location;
CREATE TABLE `hold_group_location` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `holdGroupId` int(11) DEFAULT NULL,
  `locationId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vdxHoldGroupLocation` (`holdGroupId`,`locationId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS hold_groups;
CREATE TABLE `hold_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS hold_request_confirmation;
CREATE TABLE `hold_request_confirmation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `requestId` varchar(36) NOT NULL,
  `additionalParams` mediumtext DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS holiday;
CREATE TABLE `holiday` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of holiday',
  `libraryId` int(11) NOT NULL COMMENT 'The library system id',
  `date` date NOT NULL COMMENT 'Date of holiday',
  `name` varchar(100) NOT NULL COMMENT 'Name of holiday',
  PRIMARY KEY (`id`),
  UNIQUE KEY `LibraryDate` (`date`,`libraryId`),
  KEY `Library` (`libraryId`),
  KEY `Date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS hoopla_export;
CREATE TABLE `hoopla_export` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hooplaId` int(11) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT 1,
  `title` varchar(255) DEFAULT NULL,
  `kind` varchar(50) DEFAULT NULL,
  `pa` tinyint(4) NOT NULL DEFAULT 0,
  `demo` tinyint(4) NOT NULL DEFAULT 0,
  `profanity` tinyint(4) NOT NULL DEFAULT 0,
  `rating` varchar(10) DEFAULT NULL,
  `abridged` tinyint(4) NOT NULL DEFAULT 0,
  `children` tinyint(4) NOT NULL DEFAULT 0,
  `price` double NOT NULL DEFAULT 0,
  `rawChecksum` bigint(20) DEFAULT NULL,
  `rawResponse` mediumblob DEFAULT NULL,
  `dateFirstDetected` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hooplaId` (`hooplaId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS hoopla_export_log;
CREATE TABLE `hoopla_export_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `notes` mediumtext DEFAULT NULL COMMENT 'Additional information about the run',
  `numProducts` int(11) DEFAULT 0,
  `numErrors` int(11) DEFAULT 0,
  `numAdded` int(11) DEFAULT 0,
  `numDeleted` int(11) DEFAULT 0,
  `numUpdated` int(11) DEFAULT 0,
  `numSkipped` int(11) DEFAULT 0,
  `numChangedAfterGrouping` int(11) DEFAULT 0,
  `numRegrouped` int(11) DEFAULT 0,
  `numInvalidRecords` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `startTime` (`startTime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS hoopla_record_usage;
CREATE TABLE `hoopla_record_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hooplaId` int(11) DEFAULT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `timesCheckedOut` int(11) NOT NULL,
  `instance` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`hooplaId`,`year`,`month`),
  UNIQUE KEY `instance_2` (`instance`,`hooplaId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS hoopla_scopes;
CREATE TABLE `hoopla_scopes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `includeEBooks` tinyint(4) DEFAULT 1,
  `maxCostPerCheckoutEBooks` float DEFAULT 5,
  `includeEComics` tinyint(4) DEFAULT 1,
  `maxCostPerCheckoutEComics` float DEFAULT 5,
  `includeEAudiobook` tinyint(4) DEFAULT 1,
  `maxCostPerCheckoutEAudiobook` float DEFAULT 5,
  `includeMovies` tinyint(4) DEFAULT 1,
  `maxCostPerCheckoutMovies` float DEFAULT 5,
  `includeMusic` tinyint(4) DEFAULT 1,
  `maxCostPerCheckoutMusic` float DEFAULT 5,
  `includeTelevision` tinyint(4) DEFAULT 1,
  `maxCostPerCheckoutTelevision` float DEFAULT 5,
  `ratingsToExclude` varchar(100) DEFAULT NULL,
  `excludeAbridged` tinyint(4) DEFAULT 0,
  `excludeParentalAdvisory` tinyint(4) DEFAULT 0,
  `excludeProfanity` tinyint(4) DEFAULT 0,
  `settingId` int(11) DEFAULT NULL,
  `excludeTitlesWithCopiesFromOtherVendors` tinyint(4) DEFAULT 0,
  `includeBingePass` tinyint(4) DEFAULT 1,
  `maxCostPerCheckoutBingePass` float DEFAULT 5,
  `genresToExclude` longtext DEFAULT NULL,
  `includeAdult` tinyint(4) DEFAULT 1,
  `includeTeen` tinyint(4) DEFAULT 1,
  `includeKids` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS hoopla_settings;
CREATE TABLE `hoopla_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `apiUrl` varchar(255) DEFAULT NULL,
  `libraryId` int(11) DEFAULT 0,
  `apiUsername` varchar(50) DEFAULT NULL,
  `apiPassword` varchar(50) DEFAULT NULL,
  `runFullUpdate` tinyint(1) DEFAULT 0,
  `lastUpdateOfChangedRecords` int(11) DEFAULT 0,
  `lastUpdateOfAllRecords` int(11) DEFAULT 0,
  `regroupAllRecords` tinyint(1) DEFAULT 0,
  `indexByDay` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS host_information;
CREATE TABLE `host_information` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `host` varchar(100) DEFAULT NULL,
  `libraryId` int(11) DEFAULT NULL,
  `locationId` int(11) DEFAULT -1,
  `defaultPath` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ils_extract_log;
CREATE TABLE `ils_extract_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log',
  `indexingProfile` varchar(50) NOT NULL,
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `numProducts` int(11) DEFAULT 0,
  `numErrors` int(11) DEFAULT 0,
  `numAdded` int(11) DEFAULT 0,
  `numDeleted` int(11) DEFAULT 0,
  `numUpdated` int(11) DEFAULT 0,
  `notes` mediumtext DEFAULT NULL COMMENT 'Additional information about the run',
  `numSkipped` int(11) DEFAULT 0,
  `numRegrouped` int(11) DEFAULT 0,
  `numChangedAfterGrouping` int(11) DEFAULT 0,
  `isFullUpdate` tinyint(1) DEFAULT NULL,
  `currentId` varchar(36) DEFAULT NULL,
  `numRecordsWithInvalidMarc` int(11) NOT NULL DEFAULT 0,
  `numInvalidRecords` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `indexingProfileTime` (`indexingProfile`,`startTime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ils_hold_summary;
CREATE TABLE `ils_hold_summary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ilsId` varchar(20) NOT NULL,
  `numHolds` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ilsId` (`ilsId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ils_message_type;
CREATE TABLE `ils_message_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module` varchar(255) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `isDigest` tinyint(1) DEFAULT 0,
  `locationCode` varchar(255) DEFAULT NULL,
  `isEnabled` tinyint(1) DEFAULT 1,
  `ilsNotificationSettingId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ils_notification_setting;
CREATE TABLE `ils_notification_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ils_record_usage;
CREATE TABLE `ils_record_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `indexingProfileId` int(11) NOT NULL,
  `recordId` varchar(36) DEFAULT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `timesUsed` int(11) NOT NULL,
  `pdfDownloadCount` int(11) DEFAULT 0,
  `supplementalFileDownloadCount` int(11) DEFAULT 0,
  `pdfViewCount` int(11) DEFAULT 0,
  `instance` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`indexingProfileId`,`recordId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ils_records;
CREATE TABLE `ils_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ilsId` varchar(50) NOT NULL,
  `checksum` bigint(20) unsigned NOT NULL,
  `dateFirstDetected` bigint(20) DEFAULT NULL,
  `source` varchar(50) NOT NULL DEFAULT 'ils',
  `deleted` tinyint(1) DEFAULT NULL,
  `dateDeleted` int(11) DEFAULT NULL,
  `suppressedNoMarcAvailable` tinyint(1) DEFAULT NULL,
  `sourceData` mediumblob DEFAULT NULL,
  `lastModified` int(11) DEFAULT NULL,
  `suppressed` tinyint(1) DEFAULT 0,
  `suppressionNotes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `source` (`source`,`ilsId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ils_volume_info;
CREATE TABLE `ils_volume_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recordId` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Full Record ID including the source',
  `displayLabel` varchar(512) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `relatedItems` text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `volumeId` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `displayOrder` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `recordVolume` (`recordId`,`volumeId`),
  KEY `recordId` (`recordId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS image_uploads;
CREATE TABLE `image_uploads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `fullSizePath` varchar(512) NOT NULL,
  `generateMediumSize` tinyint(1) NOT NULL DEFAULT 0,
  `mediumSizePath` varchar(512) DEFAULT NULL,
  `generateSmallSize` tinyint(1) NOT NULL DEFAULT 0,
  `smallSizePath` varchar(512) DEFAULT NULL,
  `type` varchar(25) NOT NULL,
  `generateLargeSize` tinyint(1) NOT NULL DEFAULT 1,
  `largeSizePath` varchar(512) DEFAULT '',
  `generateXLargeSize` tinyint(1) NOT NULL DEFAULT 1,
  `xLargeSizePath` varchar(512) DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `type` (`type`,`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS indexed_call_number;
CREATE TABLE `indexed_call_number` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `callNumber` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `callNumber` (`callNumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS indexed_econtent_source;
CREATE TABLE `indexed_econtent_source` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eContentSource` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `eContentSource` (`eContentSource`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS indexed_edition;
CREATE TABLE `indexed_edition` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `edition` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `edition` (`edition`(500))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS indexed_format;
CREATE TABLE `indexed_format` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `format` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `format` (`format`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS indexed_format_category;
CREATE TABLE `indexed_format_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `formatCategory` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `formatCategory` (`formatCategory`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS indexed_grouped_status;
CREATE TABLE `indexed_grouped_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupedStatus` varchar(75) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `groupedStatus` (`groupedStatus`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS indexed_item_type;
CREATE TABLE `indexed_item_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `itemType` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `itemType` (`itemType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS indexed_language;
CREATE TABLE `indexed_language` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `language` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `language` (`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS indexed_location_code;
CREATE TABLE `indexed_location_code` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `locationCode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `locationCode` (`locationCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS indexed_physical_description;
CREATE TABLE `indexed_physical_description` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `physicalDescription` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `physicalDescription` (`physicalDescription`(500))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS indexed_place_of_publication;
CREATE TABLE `indexed_place_of_publication` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `placeOfPublication` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `placeOfPublication` (`placeOfPublication`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS indexed_publication_date;
CREATE TABLE `indexed_publication_date` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publicationDate` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `publicationDate` (`publicationDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS indexed_publisher;
CREATE TABLE `indexed_publisher` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publisher` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `publisher` (`publisher`),
  UNIQUE KEY `publisher_2` (`publisher`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS indexed_record_source;
CREATE TABLE `indexed_record_source` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `subSource` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `source` (`source`,`subSource`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS indexed_shelf_location;
CREATE TABLE `indexed_shelf_location` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shelfLocation` varchar(600) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shelfLocation` (`shelfLocation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS indexed_status;
CREATE TABLE `indexed_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(75) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS indexed_sub_location_code;
CREATE TABLE `indexed_sub_location_code` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subLocationCode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subLocationCode` (`subLocationCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS indexing_profiles;
CREATE TABLE `indexing_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `marcPath` varchar(100) NOT NULL,
  `marcEncoding` enum('MARC8','UTF8','UNIMARC','ISO8859_1','BESTGUESS') NOT NULL DEFAULT 'MARC8',
  `indexingClass` varchar(50) NOT NULL,
  `recordUrlComponent` varchar(25) NOT NULL DEFAULT 'Record',
  `formatSource` enum('bib','item','specified') NOT NULL DEFAULT 'bib',
  `recordNumberTag` char(3) NOT NULL,
  `recordNumberPrefix` varchar(10) NOT NULL,
  `itemTag` char(3) NOT NULL,
  `itemRecordNumber` char(1) DEFAULT NULL,
  `useItemBasedCallNumbers` tinyint(1) NOT NULL DEFAULT 1,
  `callNumberPrestamp` char(1) DEFAULT NULL,
  `callNumber` char(1) DEFAULT NULL,
  `callNumberCutter` char(1) DEFAULT NULL,
  `callNumberPoststamp` varchar(1) DEFAULT NULL,
  `location` char(1) DEFAULT NULL,
  `locationsToSuppress` varchar(255) DEFAULT NULL,
  `subLocation` char(1) DEFAULT NULL,
  `shelvingLocation` char(1) DEFAULT NULL,
  `volume` varchar(1) DEFAULT NULL,
  `itemUrl` char(1) DEFAULT NULL,
  `barcode` char(1) DEFAULT NULL,
  `status` char(1) DEFAULT NULL,
  `statusesToSuppress` varchar(255) DEFAULT NULL,
  `totalCheckouts` char(1) DEFAULT NULL,
  `lastYearCheckouts` char(1) DEFAULT NULL,
  `yearToDateCheckouts` char(1) DEFAULT NULL,
  `totalRenewals` char(1) DEFAULT NULL,
  `iType` char(1) DEFAULT NULL,
  `dueDate` char(1) DEFAULT NULL,
  `dateCreated` char(1) DEFAULT NULL,
  `dateCreatedFormat` varchar(20) DEFAULT NULL,
  `iCode2` char(1) DEFAULT NULL,
  `useICode2Suppression` tinyint(1) NOT NULL DEFAULT 1,
  `format` char(1) DEFAULT NULL,
  `eContentDescriptor` char(1) DEFAULT NULL,
  `orderTag` char(3) DEFAULT NULL,
  `orderStatus` char(1) DEFAULT NULL,
  `orderLocation` char(1) DEFAULT NULL,
  `orderCopies` char(1) DEFAULT NULL,
  `orderCode3` char(1) DEFAULT NULL,
  `collection` char(1) DEFAULT NULL,
  `catalogDriver` varchar(50) DEFAULT NULL,
  `nonHoldableITypes` varchar(600) DEFAULT NULL,
  `nonHoldableStatuses` varchar(255) DEFAULT NULL,
  `nonHoldableLocations` varchar(512) DEFAULT NULL,
  `lastCheckinFormat` varchar(20) DEFAULT NULL,
  `lastCheckinDate` char(1) DEFAULT NULL,
  `orderLocationSingle` char(1) DEFAULT NULL,
  `specifiedFormat` varchar(50) DEFAULT NULL,
  `specifiedFormatCategory` varchar(50) DEFAULT NULL,
  `specifiedFormatBoost` int(11) DEFAULT NULL,
  `filenamesToInclude` varchar(250) DEFAULT '.*\\.ma?rc',
  `collectionsToSuppress` varchar(100) DEFAULT '',
  `dueDateFormat` varchar(20) DEFAULT 'yyMMdd',
  `doAutomaticEcontentSuppression` tinyint(1) DEFAULT 1,
  `iTypesToSuppress` varchar(100) DEFAULT NULL,
  `iCode2sToSuppress` varchar(100) DEFAULT NULL,
  `bCode3sToSuppress` varchar(100) DEFAULT NULL,
  `sierraRecordFixedFieldsTag` char(3) DEFAULT NULL,
  `bCode3` char(1) DEFAULT NULL,
  `recordNumberField` char(1) DEFAULT 'a',
  `recordNumberSubfield` char(1) DEFAULT 'a',
  `runFullUpdate` tinyint(1) DEFAULT 0,
  `lastUpdateOfChangedRecords` int(11) DEFAULT 0,
  `lastUpdateOfAllRecords` int(11) DEFAULT 0,
  `lastUpdateFromMarcExport` int(11) DEFAULT 0,
  `treatUnknownLanguageAs` varchar(50) DEFAULT 'English',
  `treatUndeterminedLanguageAs` varchar(50) DEFAULT 'English',
  `checkRecordForLargePrint` tinyint(1) DEFAULT 1,
  `determineAudienceBy` tinyint(4) DEFAULT 0,
  `audienceSubfield` char(1) DEFAULT NULL,
  `includeLocationNameInDetailedLocation` tinyint(1) DEFAULT 1,
  `lastVolumeExportTimestamp` int(11) DEFAULT 0,
  `lastUpdateOfAuthorities` int(11) DEFAULT 0,
  `regroupAllRecords` tinyint(1) DEFAULT 0,
  `fullMarcExportRecordIdThreshold` bigint(20) DEFAULT 0,
  `lastChangeProcessed` int(11) DEFAULT 0,
  `noteSubfield` char(1) DEFAULT '',
  `treatUnknownAudienceAs` varchar(10) DEFAULT 'General',
  `suppressRecordsWithUrlsMatching` varchar(512) DEFAULT 'overdrive.com|contentreserve.com|hoopla|yourcloudlibrary|axis360.baker-taylor.com',
  `determineLiteraryFormBy` tinyint(4) DEFAULT 0,
  `literaryFormSubfield` char(1) DEFAULT '',
  `hideUnknownLiteraryForm` tinyint(4) DEFAULT 1,
  `hideNotCodedLiteraryForm` tinyint(4) DEFAULT 1,
  `fallbackFormatField` varchar(5) DEFAULT NULL,
  `treatLibraryUseOnlyGroupedStatusesAsAvailable` tinyint(4) DEFAULT 1,
  `customMarcFieldsToIndexAsKeyword` varchar(255) DEFAULT '',
  `processRecordLinking` tinyint(1) DEFAULT 0,
  `evergreenOrgUnitSchema` tinyint(1) DEFAULT 1,
  `index856Links` tinyint(1) NOT NULL DEFAULT 0,
  `includePersonalAndCorporateNamesInTopics` tinyint(1) NOT NULL DEFAULT 1,
  `orderRecordsStatusesToInclude` varchar(25) DEFAULT 'o|1',
  `hideOrderRecordsForBibsWithPhysicalItems` tinyint(1) DEFAULT 0,
  `orderRecordsToSuppressByDate` tinyint(1) DEFAULT 1,
  `checkSierraMatTypeForFormat` tinyint(4) DEFAULT 0,
  `customFacet1SourceField` varchar(50) DEFAULT '',
  `customFacet1ValuesToInclude` text DEFAULT NULL,
  `customFacet1ValuesToExclude` text DEFAULT NULL,
  `customFacet2SourceField` varchar(50) DEFAULT '',
  `customFacet2ValuesToInclude` text DEFAULT NULL,
  `customFacet2ValuesToExclude` text DEFAULT NULL,
  `customFacet3SourceField` varchar(50) DEFAULT '',
  `customFacet3ValuesToInclude` text DEFAULT NULL,
  `customFacet3ValuesToExclude` text DEFAULT NULL,
  `callNumberPrestamp2` char(1) DEFAULT NULL,
  `itemUrlDescription` char(1) DEFAULT NULL,
  `numRetriesForBibLookups` tinyint(4) DEFAULT 2,
  `numMillisecondsToPauseAfterBibLookups` int(11) DEFAULT 0,
  `numExtractionThreads` tinyint(4) DEFAULT 10,
  `orderRecordStatusToTreatAsUnderConsideration` varchar(10) DEFAULT '',
  `bibCallNumberFields` varchar(25) DEFAULT '099:092:082',
  `replacementCostSubfield` char(1) DEFAULT '',
  `treatItemsAsEcontent` varchar(512) DEFAULT 'ebook|ebk|eaudio|evideo|online|oneclick|eaudiobook|download|eresource|electronic resource',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS invoice_cloud_settings;
CREATE TABLE `invoice_cloud_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `apiKey` varchar(500) NOT NULL,
  `invoiceTypeId` int(11) DEFAULT NULL,
  `ccServiceFee` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ip_lookup;
CREATE TABLE `ip_lookup` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `locationid` int(11) NOT NULL,
  `location` varchar(255) NOT NULL,
  `ip` varchar(255) NOT NULL,
  `startIpVal` bigint(20) DEFAULT NULL,
  `endIpVal` bigint(20) DEFAULT NULL,
  `isOpac` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `blockAccess` tinyint(4) NOT NULL DEFAULT 0,
  `allowAPIAccess` tinyint(4) NOT NULL DEFAULT 0,
  `showDebuggingInformation` tinyint(4) NOT NULL DEFAULT 0,
  `logTimingInformation` tinyint(4) DEFAULT 0,
  `logAllQueries` tinyint(4) DEFAULT 0,
  `defaultLogMeOutAfterPlacingHoldOn` tinyint(1) DEFAULT 1,
  `authenticatedForEBSCOhost` tinyint(4) DEFAULT 0,
  `blockedForSpam` tinyint(4) DEFAULT 0,
  `masqueradeMode` tinyint(1) DEFAULT 0,
  `ssoLogin` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `startIpVal` (`startIpVal`),
  KEY `endIpVal` (`endIpVal`),
  KEY `startIpVal_2` (`startIpVal`),
  KEY `endIpVal_2` (`endIpVal`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS javascript_snippet_library;
CREATE TABLE `javascript_snippet_library` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `javascriptSnippetId` int(11) DEFAULT NULL,
  `libraryId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `javascriptSnippetLibrary` (`javascriptSnippetId`,`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS javascript_snippet_location;
CREATE TABLE `javascript_snippet_location` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `javascriptSnippetId` int(11) DEFAULT NULL,
  `locationId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `javascriptSnippetLocation` (`javascriptSnippetId`,`locationId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS javascript_snippets;
CREATE TABLE `javascript_snippets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `snippet` mediumtext DEFAULT NULL,
  `containsAnalyticsCookies` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS languages;
CREATE TABLE `languages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `weight` int(11) NOT NULL DEFAULT 0,
  `code` char(3) NOT NULL,
  `displayName` varchar(50) DEFAULT NULL,
  `displayNameEnglish` varchar(50) DEFAULT NULL,
  `facetValue` varchar(100) NOT NULL,
  `displayToTranslatorsOnly` tinyint(1) DEFAULT 0,
  `locale` varchar(10) DEFAULT 'en-US',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS layout_settings;
CREATE TABLE `layout_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `homeLinkText` varchar(50) DEFAULT 'Home',
  `showLibraryHoursAndLocationsLink` int(11) DEFAULT 1,
  `useHomeLink` tinyint(1) DEFAULT 0,
  `showBookIcon` tinyint(1) DEFAULT 0,
  `browseLinkText` varchar(30) DEFAULT 'Browse',
  `showTopOfPageButton` tinyint(1) DEFAULT 1,
  `dismissPlacardButtonLocation` tinyint(1) DEFAULT 0,
  `dismissPlacardButtonIcon` tinyint(1) DEFAULT 0,
  `contrastRatio` varchar(5) DEFAULT '4.50',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS libkey_settings;
CREATE TABLE `libkey_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `libraryId` varchar(20) NOT NULL,
  `apiKey` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS library;
CREATE TABLE `library` (
  `libraryId` int(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique id to identify the library within the system',
  `subdomain` varchar(25) NOT NULL COMMENT 'The subdomain which can be used to access settings for the library',
  `displayName` varchar(80) NOT NULL,
  `showLibraryFacet` tinyint(4) NOT NULL DEFAULT 1 COMMENT 'Whether or not the user can see and use the library facet to change to another branch in their library system.',
  `showConsortiumFacet` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Whether or not the user can see and use the consortium facet to change to other library systems. ',
  `allowInBranchHolds` tinyint(4) NOT NULL DEFAULT 1 COMMENT 'Whether or not the user can place holds for their branch.  If this isn''t shown, they won''t be able to place holds for books at the location they are in.  If set to false, they won''t be able to place any holds. ',
  `allowInLibraryHolds` tinyint(4) NOT NULL DEFAULT 1 COMMENT 'Whether or not the user can place holds for books at other locations in their library system',
  `allowConsortiumHolds` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Whether or not the user can place holds for any book anywhere in the consortium.  ',
  `scope` smallint(6) DEFAULT 0,
  `useScope` tinyint(4) DEFAULT 0,
  `showHoldButton` tinyint(4) DEFAULT 1,
  `showLoginButton` tinyint(4) DEFAULT 1,
  `showEmailThis` tinyint(4) DEFAULT 1,
  `showComments` tinyint(4) DEFAULT 1,
  `showFavorites` tinyint(4) DEFAULT 1,
  `inSystemPickupsOnly` tinyint(4) DEFAULT 0,
  `facetLabel` varchar(75) DEFAULT '',
  `finePaymentType` tinyint(1) DEFAULT NULL,
  `repeatSearchOption` enum('none','librarySystem','marmot','all') NOT NULL DEFAULT 'all' COMMENT 'Where to allow repeating search.  Valid options are: none, librarySystem, marmot, all',
  `repeatInInnReach` tinyint(4) DEFAULT 0,
  `repeatInWorldCat` tinyint(4) DEFAULT 0,
  `systemsToRepeatIn` varchar(255) DEFAULT '',
  `homeLink` varchar(255) NOT NULL DEFAULT 'default',
  `showAdvancedSearchbox` tinyint(4) NOT NULL DEFAULT 1,
  `validPickupSystems` varchar(500) DEFAULT '',
  `allowProfileUpdates` tinyint(4) NOT NULL DEFAULT 1,
  `allowRenewals` tinyint(4) NOT NULL DEFAULT 1,
  `allowFreezeHolds` tinyint(4) NOT NULL DEFAULT 1,
  `showItsHere` tinyint(4) NOT NULL DEFAULT 1,
  `holdDisclaimer` longtext DEFAULT NULL,
  `showHoldCancelDate` tinyint(4) NOT NULL DEFAULT 0,
  `enableInnReachIntegration` tinyint(4) NOT NULL DEFAULT 0,
  `minimumFineAmount` float NOT NULL DEFAULT 0,
  `enableGenealogy` tinyint(4) NOT NULL DEFAULT 0,
  `enableCourseReserves` tinyint(1) NOT NULL DEFAULT 0,
  `exportOptions` varchar(100) NOT NULL DEFAULT 'RefWorks|EndNote',
  `enableSelfRegistration` tinyint(4) NOT NULL DEFAULT 0,
  `enableMaterialsRequest` tinyint(4) DEFAULT 0,
  `eContentLinkRules` varchar(512) DEFAULT '',
  `notesTabName` varchar(50) DEFAULT 'Notes',
  `showHoldButtonInSearchResults` tinyint(4) DEFAULT 1,
  `showSimilarAuthors` tinyint(4) DEFAULT 1,
  `showSimilarTitles` tinyint(4) DEFAULT 1,
  `worldCatUrl` varchar(100) DEFAULT '',
  `worldCatQt` varchar(40) DEFAULT '',
  `showGoDeeper` tinyint(4) DEFAULT 1,
  `showInnReachResultsAtEndOfSearch` tinyint(4) DEFAULT 1,
  `defaultNotNeededAfterDays` int(11) DEFAULT 0,
  `showOtherFormatCategory` tinyint(1) DEFAULT 1,
  `showWikipediaContent` tinyint(1) DEFAULT 1,
  `payFinesLink` varchar(512) DEFAULT 'default',
  `payFinesLinkText` varchar(512) DEFAULT 'Click to Pay Fines Online',
  `ilsCode` varchar(75) DEFAULT NULL,
  `systemMessage` mediumtext DEFAULT NULL,
  `restrictSearchByLibrary` tinyint(1) DEFAULT 0,
  `restrictOwningBranchesAndSystems` tinyint(1) DEFAULT 1,
  `showAvailableAtAnyLocation` tinyint(1) DEFAULT 1,
  `allowPatronAddressUpdates` tinyint(1) DEFAULT 1,
  `showWorkPhoneInProfile` tinyint(1) DEFAULT 0,
  `showNoticeTypeInProfile` tinyint(1) DEFAULT 0,
  `allowPickupLocationUpdates` tinyint(1) DEFAULT 0,
  `accountingUnit` int(11) DEFAULT 10,
  `additionalCss` longtext DEFAULT NULL,
  `allowPinReset` tinyint(1) DEFAULT NULL,
  `maxRequestsPerYear` int(11) DEFAULT 60,
  `maxActiveRequests` int(11) DEFAULT 5,
  `twitterLink` varchar(255) DEFAULT '',
  `pinterestLink` varchar(255) DEFAULT NULL,
  `youtubeLink` varchar(255) DEFAULT NULL,
  `instagramLink` varchar(255) DEFAULT NULL,
  `goodreadsLink` varchar(255) DEFAULT NULL,
  `facebookLink` varchar(255) DEFAULT '',
  `generalContactLink` varchar(255) DEFAULT '',
  `repeatInOnlineCollection` int(11) DEFAULT 1,
  `showExpirationWarnings` tinyint(1) DEFAULT 1,
  `econtentLocationsToInclude` varchar(255) DEFAULT NULL,
  `showLibraryHoursNoticeOnAccountPages` tinyint(1) DEFAULT 1,
  `showShareOnExternalSites` int(11) DEFAULT 1,
  `barcodePrefix` varchar(15) DEFAULT '',
  `minBarcodeLength` int(11) DEFAULT 0,
  `maxBarcodeLength` int(11) DEFAULT 0,
  `showDisplayNameInHeader` tinyint(4) DEFAULT 0,
  `headerText` longtext DEFAULT NULL,
  `promptForBirthDateInSelfReg` tinyint(4) DEFAULT 0,
  `loginFormUsernameLabel` varchar(100) DEFAULT 'Your Name',
  `loginFormPasswordLabel` varchar(100) DEFAULT 'Library Card Number',
  `additionalLocationsToShowAvailabilityFor` varchar(255) NOT NULL DEFAULT '',
  `includeDplaResults` tinyint(1) DEFAULT 0,
  `selfRegistrationFormMessage` mediumtext DEFAULT NULL,
  `selfRegistrationSuccessMessage` mediumtext DEFAULT NULL,
  `addSMSIndicatorToPhone` tinyint(1) DEFAULT 0,
  `showAlternateLibraryOptionsInProfile` tinyint(1) DEFAULT 1,
  `selfRegistrationTemplate` varchar(25) DEFAULT 'default',
  `externalMaterialsRequestUrl` varchar(255) DEFAULT NULL,
  `isDefault` tinyint(1) DEFAULT NULL,
  `showHoldButtonForUnavailableOnly` tinyint(1) DEFAULT 0,
  `allowLinkedAccounts` tinyint(1) DEFAULT 1,
  `allowAutomaticSearchReplacements` tinyint(1) DEFAULT 1,
  `publicListsToInclude` tinyint(1) DEFAULT 4,
  `showOtherSubjects` tinyint(1) DEFAULT 1,
  `maxFinesToAllowAccountUpdates` float DEFAULT 10,
  `showRefreshAccountButton` tinyint(4) NOT NULL DEFAULT 1,
  `patronNameDisplayStyle` enum('firstinitial_lastname','lastinitial_firstname','firstinitial_middleinitial_lastname','firstname_middleinitial_lastinitial') DEFAULT 'firstinitial_lastname',
  `preventExpiredCardLogin` tinyint(1) DEFAULT 0,
  `casHost` varchar(50) DEFAULT NULL,
  `casPort` smallint(6) DEFAULT NULL,
  `casContext` varchar(50) DEFAULT NULL,
  `masqueradeAutomaticTimeoutLength` tinyint(3) unsigned DEFAULT NULL,
  `allowMasqueradeMode` tinyint(1) DEFAULT 1,
  `allowReadingHistoryDisplayInMasqueradeMode` tinyint(1) DEFAULT 0,
  `newMaterialsRequestSummary` mediumtext DEFAULT NULL,
  `materialsRequestDaysToPreserve` int(11) DEFAULT 0,
  `showGroupedHoldCopiesCount` tinyint(1) DEFAULT 1,
  `interLibraryLoanName` varchar(30) DEFAULT NULL,
  `interLibraryLoanUrl` varchar(200) DEFAULT NULL,
  `expirationNearMessage` longtext DEFAULT NULL,
  `expiredMessage` longtext DEFAULT NULL,
  `enableCombinedResults` tinyint(1) DEFAULT 0,
  `combinedResultsLabel` varchar(255) DEFAULT 'Combined Results',
  `defaultToCombinedResults` tinyint(1) DEFAULT 0,
  `hooplaLibraryID` int(10) unsigned DEFAULT NULL,
  `showOnOrderCounts` tinyint(1) DEFAULT 1,
  `sharedOverdriveCollection` tinyint(1) DEFAULT -1,
  `showSeriesAsTab` tinyint(4) NOT NULL DEFAULT 0,
  `enableAlphaBrowse` tinyint(4) DEFAULT 1,
  `homePageWidgetId` varchar(50) DEFAULT '',
  `searchGroupedRecords` tinyint(4) DEFAULT 0,
  `showStandardSubjects` tinyint(1) DEFAULT 1,
  `theme` int(11) DEFAULT 1,
  `enableOpenArchives` tinyint(1) DEFAULT 0,
  `hooplaScopeId` int(11) DEFAULT -1,
  `finesToPay` tinyint(1) DEFAULT 1,
  `finePaymentOrder` varchar(80) DEFAULT '',
  `layoutSettingId` int(11) DEFAULT 0,
  `overDriveScopeId` int(11) DEFAULT -1,
  `groupedWorkDisplaySettingId` int(11) DEFAULT 0,
  `browseCategoryGroupId` int(11) NOT NULL,
  `showConvertListsFromClassic` tinyint(1) DEFAULT 0,
  `enableForgotPasswordLink` tinyint(1) DEFAULT 1,
  `selfRegistrationLocationRestrictions` int(11) DEFAULT 2,
  `baseUrl` varchar(75) DEFAULT NULL,
  `generateSitemap` tinyint(1) DEFAULT 1,
  `selfRegistrationUrl` varchar(255) DEFAULT NULL,
  `showWhileYouWait` tinyint(1) DEFAULT 1,
  `useAllCapsWhenSubmittingSelfRegistration` tinyint(1) DEFAULT 0,
  `validSelfRegistrationStates` varchar(255) DEFAULT '',
  `selfRegistrationPasswordNotes` varchar(255) DEFAULT '',
  `showAlternateLibraryCard` tinyint(4) DEFAULT 0,
  `showAlternateLibraryCardPassword` tinyint(4) DEFAULT 0,
  `alternateLibraryCardLabel` varchar(100) DEFAULT '',
  `alternateLibraryCardPasswordLabel` varchar(100) DEFAULT '',
  `libraryCardBarcodeStyle` varchar(20) DEFAULT 'none',
  `alternateLibraryCardStyle` varchar(20) DEFAULT 'none',
  `allowUsernameUpdates` tinyint(1) DEFAULT 0,
  `edsSettingsId` int(11) DEFAULT -1,
  `useAllCapsWhenUpdatingProfile` tinyint(1) DEFAULT 0,
  `bypassReviewQueueWhenUpdatingProfile` tinyint(1) DEFAULT 0,
  `availableHoldDelay` int(11) DEFAULT 0,
  `enableWebBuilder` tinyint(1) DEFAULT 0,
  `requireNumericPhoneNumbersWhenUpdatingProfile` tinyint(1) DEFAULT 0,
  `axis360ScopeId` int(11) DEFAULT -1,
  `allowPatronPhoneNumberUpdates` tinyint(1) DEFAULT 1,
  `validSelfRegistrationZipCodes` varchar(255) DEFAULT '',
  `loginNotes` longtext DEFAULT NULL,
  `allowRememberPickupLocation` tinyint(1) DEFAULT 1,
  `allowHomeLibraryUpdates` tinyint(1) DEFAULT 1,
  `msbUrl` varchar(80) DEFAULT NULL,
  `showOpacNotes` tinyint(1) DEFAULT 0,
  `showBorrowerMessages` tinyint(1) DEFAULT 0,
  `showDebarmentNotes` tinyint(1) DEFAULT 0,
  `symphonyPaymentType` varchar(12) DEFAULT NULL,
  `symphonyPaymentPolicy` varchar(10) DEFAULT NULL,
  `allowDeletingILSRequests` tinyint(1) DEFAULT 1,
  `tiktokLink` varchar(255) DEFAULT '',
  `workstationId` varchar(10) DEFAULT '',
  `compriseSettingId` int(11) DEFAULT -1,
  `proPaySettingId` int(11) DEFAULT -1,
  `payPalSettingId` int(11) DEFAULT -1,
  `worldPaySettingId` int(11) DEFAULT -1,
  `createSearchInterface` tinyint(1) DEFAULT 1,
  `maxDaysToFreeze` int(11) DEFAULT 365,
  `displayItemBarcode` tinyint(1) DEFAULT 0,
  `treatBibOrItemHoldsAs` tinyint(1) DEFAULT 1,
  `showCardExpirationDate` tinyint(1) DEFAULT 1,
  `showInSelectInterface` tinyint(1) DEFAULT 1,
  `isConsortialCatalog` tinyint(1) DEFAULT 0,
  `showMessagingSettings` tinyint(1) DEFAULT 1,
  `contactEmail` varchar(250) DEFAULT NULL,
  `displayMaterialsRequestToPublic` tinyint(1) DEFAULT 1,
  `donationSettingId` int(11) DEFAULT -1,
  `courseReserveLibrariesToInclude` varchar(50) DEFAULT NULL,
  `curbsidePickupSettingId` int(11) DEFAULT -1,
  `twoFactorAuthSettingId` int(11) DEFAULT -1,
  `defaultRememberMe` tinyint(1) DEFAULT 0,
  `showLogMeOutAfterPlacingHolds` tinyint(1) DEFAULT 1,
  `systemHoldNote` varchar(50) DEFAULT '',
  `systemHoldNoteMasquerade` varchar(50) DEFAULT '',
  `enableReadingHistory` tinyint(1) DEFAULT 1,
  `showCitationStyleGuides` tinyint(1) DEFAULT 1,
  `minPinLength` int(11) DEFAULT 4,
  `maxPinLength` int(11) DEFAULT 6,
  `onlyDigitsAllowedInPin` int(11) DEFAULT 1,
  `enableSavedSearches` tinyint(1) DEFAULT 1,
  `holdPlacedAt` tinyint(1) DEFAULT 0,
  `allowLoginToPatronsOfThisLibraryOnly` tinyint(1) DEFAULT 0,
  `messageForPatronsOfOtherLibraries` text DEFAULT NULL,
  `allowNameUpdates` tinyint(1) DEFAULT 1,
  `allowDateOfBirthUpdates` tinyint(1) DEFAULT 1,
  `xpressPaySettingId` int(11) DEFAULT -1,
  `preventLogin` tinyint(1) DEFAULT 0,
  `preventLoginMessage` text DEFAULT NULL,
  `footerText` mediumtext DEFAULT NULL,
  `ebscohostSettingId` int(11) DEFAULT -1,
  `ebscohostSearchSettingId` int(11) DEFAULT -1,
  `displayHoldsOnCheckout` tinyint(1) DEFAULT 0,
  `lidaNotificationSettingId` int(11) DEFAULT -1,
  `lidaQuickSearchId` int(11) DEFAULT -1,
  `aciSpeedpaySettingId` int(11) DEFAULT -1,
  `ssoSettingId` tinyint(4) DEFAULT -1,
  `showUserCirculationModules` tinyint(1) DEFAULT 1,
  `showUserPreferences` tinyint(1) DEFAULT 1,
  `showUserContactInformation` tinyint(1) DEFAULT 1,
  `showVolumesWithLocalCopiesFirst` tinyint(4) DEFAULT 0,
  `enableListDescriptions` tinyint(1) DEFAULT 1,
  `allowableListNames` varchar(500) DEFAULT '',
  `invoiceCloudSettingId` int(11) DEFAULT -1,
  `holdRange` varchar(20) DEFAULT 'SYSTEM',
  `optInToReadingHistoryUpdatesILS` tinyint(1) DEFAULT 0,
  `optOutOfReadingHistoryUpdatesILS` tinyint(1) DEFAULT 1,
  `setUsePreferredNameInIlsOnUpdate` tinyint(1) DEFAULT 1,
  `accountProfileId` int(11) DEFAULT -1,
  `lidaGeneralSettingId` int(11) DEFAULT -1,
  `materialsRequestSendStaffEmailOnNew` tinyint(1) DEFAULT 0,
  `materialsRequestSendStaffEmailOnAssign` tinyint(1) DEFAULT 0,
  `materialsRequestNewEmail` varchar(125) DEFAULT NULL,
  `novelistSettingId` int(11) DEFAULT -1,
  `deluxeCertifiedPaymentsSettingId` int(11) DEFAULT -1,
  `paypalPayflowSettingId` int(11) DEFAULT -1,
  `thirdPartyRegistrationLocation` int(11) DEFAULT -1,
  `thirdPartyPTypeAddressValidated` int(11) DEFAULT -1,
  `thirdPartyPTypeAddressNotValidated` int(11) DEFAULT -1,
  `squareSettingId` int(11) DEFAULT -1,
  `twilioSettingId` int(11) DEFAULT -1,
  `eCommerceFee` varchar(11) DEFAULT '0',
  `eCommerceTerms` mediumtext DEFAULT NULL,
  `cookieStorageConsent` tinyint(1) DEFAULT 0,
  `cookiePolicyHTML` text DEFAULT NULL,
  `openArchivesFacetSettingId` int(11) DEFAULT 1,
  `websiteIndexingFacetSettingId` int(11) DEFAULT 1,
  `enableForgotBarcode` tinyint(1) DEFAULT 0,
  `allowChangingPickupLocationForAvailableHolds` tinyint(1) DEFAULT 0,
  `allowCancellingAvailableHolds` tinyint(1) DEFAULT 1,
  `alwaysDisplayRenewalCount` tinyint(1) DEFAULT 0,
  `selfRegistrationFormId` int(11) DEFAULT -1,
  `ILLSystem` tinyint(1) DEFAULT 2,
  `palaceProjectScopeId` int(11) DEFAULT -1,
  `languageAndDisplayInHeader` int(11) DEFAULT 1,
  `enableCardRenewal` tinyint(1) DEFAULT 0,
  `showCardRenewalWhenExpirationIsClose` tinyint(1) DEFAULT 1,
  `cardRenewalUrl` varchar(255) DEFAULT NULL,
  `allowCancellingInTransitHolds` tinyint(1) DEFAULT 1,
  `stripeSettingId` int(11) DEFAULT -1,
  `summonSettingsId` int(11) DEFAULT -1,
  `summonApiId` varchar(50) DEFAULT NULL,
  `summonApiPassword` varchar(50) DEFAULT NULL,
  `showPaymentHistory` tinyint(4) DEFAULT 0,
  `deletePaymentHistoryOlderThan` int(11) DEFAULT 0,
  `showHoldPosition` tinyint(1) DEFAULT 1,
  `minSelfRegAge` int(2) DEFAULT 0,
  `institutionCode` varchar(100) DEFAULT '',
  `showAvailableCoversInSummon` tinyint(1) DEFAULT 0,
  `allowMasqueradeWithUsername` tinyint(4) NOT NULL DEFAULT 1,
  `usernameField` varchar(1) NOT NULL DEFAULT 'w',
  `allowPatronWorkPhoneNumberUpdates` tinyint(1) DEFAULT 1,
  `ncrSettingId` int(11) DEFAULT -1,
  `alternateLibraryCardFormMessage` mediumtext DEFAULT NULL,
  `symphonyNoticeCategoryNumber` varchar(2) DEFAULT NULL,
  `symphonyNoticeCategoryOptions` varchar(128) DEFAULT NULL,
  `symphonyBillingNoticeCategoryNumber` varchar(2) DEFAULT NULL,
  `symphonyBillingNoticeCategoryOptions` varchar(128) DEFAULT NULL,
  `symphonyDefaultPhoneField` varchar(16) DEFAULT 'PHONE',
  `showCellphoneInProfile` tinyint(1) DEFAULT 0,
  `allowUpdatingHolidaysFromILS` tinyint(1) DEFAULT 1,
  `displayExploreMoreBarInSummon` tinyint(1) DEFAULT 1,
  `displayExploreMoreBarInEbscoEds` tinyint(1) DEFAULT 1,
  `displayExploreMoreBarInCatalogSearch` tinyint(1) DEFAULT 1,
  `displayExploreMoreBarInEbscoHost` tinyint(1) DEFAULT 1,
  `snapPaySettingId` int(11) DEFAULT -1,
  `enableCostSavings` tinyint(4) DEFAULT 0,
  `repeatInCloudSource` tinyint(4) DEFAULT 0,
  `cloudSourceBaseUrl` tinytext DEFAULT NULL,
  `repeatInShareIt` tinyint(1) DEFAULT 0,
  `shareItCid` tinytext DEFAULT NULL,
  `shareItLid` tinytext DEFAULT NULL,
  `shareItUsername` tinytext DEFAULT NULL,
  `shareItPassword` tinytext DEFAULT NULL,
  `paymentBranchSource` enum('notApplicable','patronHomeLocation','specified') DEFAULT 'notApplicable',
  `specifiedPaymentBranchCode` varchar(6) DEFAULT '',
  `localIllRequestType` tinyint(4) DEFAULT 0,
  `yearlyRequestLimitType` tinyint(4) DEFAULT 0,
  `enableAddToReadingHistory` tinyint(4) DEFAULT 1,
  `allowRenewingOutOfHoldGroupCheckouts` tinyint(4) DEFAULT 1,
  `maximumLocalIllRequests` int(11) DEFAULT 0,
  `libKeySettingId` int(11) NOT NULL DEFAULT -1,
  `syndeticsSettingId` int(11) DEFAULT -1,
  `requestCalendarStartDate` char(5) DEFAULT '01-01',
  `checkRequestsForExistingTitles` tinyint(4) DEFAULT 1,
  `ilsConsentEnabled` tinyint(1) DEFAULT 0,
  `useSeriesSearchIndex` tinyint(1) DEFAULT 0,
  `webResourcesSettingId` int(11) DEFAULT -1,
  `allowFilteringOfLinkedAccountsInHolds` tinyint(1) DEFAULT 0,
  `allowSelectingHoldsToExport` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`libraryId`),
  UNIQUE KEY `subdomain` (`subdomain`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS library_cloud_library_scope;
CREATE TABLE `library_cloud_library_scope` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `scopeId` int(11) NOT NULL,
  `libraryId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `libraryId` (`libraryId`,`scopeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS library_combined_results_section;
CREATE TABLE `library_combined_results_section` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `displayName` varchar(255) DEFAULT NULL,
  `source` varchar(45) DEFAULT NULL,
  `numberOfResultsToShow` int(11) NOT NULL DEFAULT 5,
  `weight` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `LibraryIdIndex` (`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS library_email_template;
CREATE TABLE `library_email_template` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `emailTemplateId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `libraryId` (`libraryId`,`emailTemplateId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
DROP TABLE IF EXISTS library_events_setting;
CREATE TABLE `library_events_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `settingSource` varchar(25) NOT NULL,
  `settingId` int(11) NOT NULL,
  `libraryId` int(11) NOT NULL,
  `eventsFacetSettingsId` int(11) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settingSource` (`settingSource`,`settingId`,`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS library_ill_item_type;
CREATE TABLE `library_ill_item_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `code` varchar(75) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
DROP TABLE IF EXISTS library_link_language;
CREATE TABLE `library_link_language` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryLinkId` int(11) DEFAULT NULL,
  `languageId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `libraryLinkLanguage` (`libraryLinkId`,`languageId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS library_links;
CREATE TABLE `library_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `linkText` varchar(100) NOT NULL,
  `url` varchar(255) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT 0,
  `htmlContents` longtext DEFAULT NULL,
  `showExpanded` tinyint(4) DEFAULT 0,
  `openInNewTab` tinyint(4) DEFAULT 1,
  `showToLoggedInUsersOnly` tinyint(4) DEFAULT 0,
  `showInTopMenu` tinyint(4) DEFAULT 0,
  `iconName` varchar(30) DEFAULT '',
  `alwaysShowIconInTopMenu` tinyint(4) DEFAULT 0,
  `published` tinyint(4) DEFAULT 1,
  `showLinkOn` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `libraryId` (`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS library_links_access;
CREATE TABLE `library_links_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryLinkId` int(11) NOT NULL,
  `patronTypeId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `libraryLinkId` (`libraryLinkId`,`patronTypeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS library_open_archives_collection;
CREATE TABLE `library_open_archives_collection` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collectionId` int(11) NOT NULL,
  `libraryId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `collectionId` (`collectionId`,`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS library_overdrive_scope;
CREATE TABLE `library_overdrive_scope` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `scopeId` int(11) NOT NULL,
  `libraryId` int(11) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT 1,
  `circulationEnabled` tinyint(4) DEFAULT 1,
  `authenticationILSName` varchar(45) DEFAULT NULL,
  `requirePin` tinyint(1) DEFAULT 0,
  `overdriveAdvantageName` varchar(128) DEFAULT '',
  `overdriveAdvantageProductsKey` varchar(20) DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `libraryId` (`libraryId`,`scopeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS library_overdrive_settings;
CREATE TABLE `library_overdrive_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `settingId` int(11) NOT NULL,
  `weight` int(11) DEFAULT 0,
  `libraryId` int(11) NOT NULL,
  `clientSecret` varchar(256) DEFAULT NULL,
  `clientKey` varchar(50) DEFAULT NULL,
  `circulationEnabled` tinyint(4) DEFAULT 1,
  `authenticationILSName` varchar(45) DEFAULT NULL,
  `requirePin` tinyint(1) DEFAULT 0,
  `overdriveAdvantageName` varchar(128) DEFAULT '',
  `overdriveAdvantageProductsKey` varchar(20) DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `librarySetting` (`settingId`,`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
DROP TABLE IF EXISTS library_records_to_include;
CREATE TABLE `library_records_to_include` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `indexingProfileId` int(11) NOT NULL,
  `location` varchar(100) NOT NULL,
  `subLocation` varchar(150) NOT NULL DEFAULT '',
  `includeHoldableOnly` tinyint(4) NOT NULL DEFAULT 1,
  `includeItemsOnOrder` tinyint(1) NOT NULL DEFAULT 0,
  `includeEContent` tinyint(1) NOT NULL DEFAULT 0,
  `weight` int(11) NOT NULL DEFAULT 0,
  `iType` varchar(100) DEFAULT NULL,
  `audience` varchar(100) DEFAULT NULL,
  `format` varchar(100) DEFAULT NULL,
  `marcTagToMatch` varchar(100) DEFAULT NULL,
  `marcValueToMatch` varchar(100) DEFAULT NULL,
  `includeExcludeMatches` tinyint(4) DEFAULT 1,
  `urlToMatch` varchar(100) DEFAULT NULL,
  `urlReplacement` varchar(255) DEFAULT NULL,
  `locationsToExclude` text DEFAULT NULL,
  `subLocationsToExclude` varchar(400) NOT NULL DEFAULT '',
  `markRecordsAsOwned` tinyint(4) DEFAULT 0,
  `iTypesToExclude` varchar(100) NOT NULL DEFAULT '',
  `audiencesToExclude` varchar(100) NOT NULL DEFAULT '',
  `formatsToExclude` varchar(100) NOT NULL DEFAULT '',
  `shelfLocation` varchar(100) NOT NULL DEFAULT '',
  `shelfLocationsToExclude` varchar(100) NOT NULL DEFAULT '',
  `collectionCode` varchar(100) NOT NULL DEFAULT '',
  `collectionCodesToExclude` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `libraryId` (`libraryId`,`indexingProfileId`),
  KEY `indexingProfileId` (`indexingProfileId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS library_search_source;
CREATE TABLE `library_search_source` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL DEFAULT -1,
  `label` varchar(50) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT 0,
  `searchWhat` enum('catalog','genealogy','overdrive','worldcat','prospector','goldrush','title_browse','author_browse','subject_browse','tags') DEFAULT NULL,
  `defaultFilter` mediumtext DEFAULT NULL,
  `defaultSort` enum('relevance','popularity','newest_to_oldest','oldest_to_newest','author','title','user_rating') DEFAULT NULL,
  `catalogScoping` enum('unscoped','library','location') DEFAULT 'unscoped',
  PRIMARY KEY (`id`),
  KEY `libraryId` (`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS library_sideload_scopes;
CREATE TABLE `library_sideload_scopes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `sideLoadScopeId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `libraryId` (`libraryId`,`sideLoadScopeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS library_themes;
CREATE TABLE `library_themes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `themeId` int(11) NOT NULL,
  `weight` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `libraryToTheme` (`libraryId`,`themeId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS library_web_builder_basic_page;
CREATE TABLE `library_web_builder_basic_page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `basicPageId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `libraryId` (`libraryId`),
  KEY `basicPageId` (`basicPageId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS library_web_builder_custom_form;
CREATE TABLE `library_web_builder_custom_form` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `formId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `libraryId` (`libraryId`),
  KEY `formId` (`formId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS library_web_builder_custom_web_resource_page;
CREATE TABLE `library_web_builder_custom_web_resource_page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `customResourcePageId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `libraryId` (`libraryId`),
  KEY `customResourcePageId` (`customResourcePageId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS library_web_builder_grapes_page;
CREATE TABLE `library_web_builder_grapes_page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `grapesPageId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `libraryId` (`libraryId`),
  KEY `grapesPageId` (`grapesPageId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS library_web_builder_portal_page;
CREATE TABLE `library_web_builder_portal_page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `portalPageId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `libraryId` (`libraryId`),
  KEY `portalPageId` (`portalPageId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS library_web_builder_quick_poll;
CREATE TABLE `library_web_builder_quick_poll` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `pollId` int(11) NOT NULL,
  `label` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `libraryId` (`libraryId`,`pollId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
DROP TABLE IF EXISTS library_web_builder_resource;
CREATE TABLE `library_web_builder_resource` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `webResourceId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `libraryId` (`libraryId`),
  KEY `webResourceId` (`webResourceId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS library_website_indexing;
CREATE TABLE `library_website_indexing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `settingId` int(11) NOT NULL,
  `libraryId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settingId` (`settingId`,`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS library_year_in_review;
CREATE TABLE `library_year_in_review` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `yearInReviewId` int(11) NOT NULL,
  `libraryId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `yearInReviewId` (`yearInReviewId`,`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
DROP TABLE IF EXISTS list_indexing_log;
CREATE TABLE `list_indexing_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `startTime` int(11) NOT NULL,
  `endTime` int(11) DEFAULT NULL,
  `lastUpdate` int(11) DEFAULT NULL,
  `notes` mediumtext DEFAULT NULL,
  `numLists` int(11) DEFAULT 0,
  `numAdded` int(11) DEFAULT 0,
  `numDeleted` int(11) DEFAULT 0,
  `numUpdated` int(11) DEFAULT 0,
  `numSkipped` int(11) DEFAULT 0,
  `numErrors` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `startTime` (`startTime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS list_indexing_settings;
CREATE TABLE `list_indexing_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `runFullUpdate` tinyint(1) DEFAULT 1,
  `lastUpdateOfChangedLists` int(11) DEFAULT 0,
  `lastUpdateOfAllLists` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS lm_library_calendar_events;
CREATE TABLE `lm_library_calendar_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `settingsId` int(11) NOT NULL,
  `externalId` varchar(36) NOT NULL,
  `title` varchar(255) NOT NULL,
  `rawChecksum` bigint(20) DEFAULT NULL,
  `rawResponse` longtext DEFAULT NULL,
  `deleted` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settingsId` (`settingsId`,`externalId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS lm_library_calendar_settings;
CREATE TABLE `lm_library_calendar_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `baseUrl` varchar(255) NOT NULL,
  `clientId` varchar(36) DEFAULT NULL,
  `clientSecret` varchar(36) DEFAULT NULL,
  `username` varchar(36) DEFAULT 'lc_feeds_staffadmin',
  `password` varchar(36) DEFAULT NULL,
  `eventsInLists` tinyint(1) DEFAULT 1,
  `bypassAspenEventPages` tinyint(1) DEFAULT 0,
  `registrationModalBody` mediumtext DEFAULT NULL,
  `numberOfDaysToIndex` int(11) DEFAULT 365,
  `registrationModalBodyApp` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS local_ill_form;
CREATE TABLE `local_ill_form` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `introText` text DEFAULT NULL,
  `showAcceptFee` tinyint(1) DEFAULT 0,
  `requireAcceptFee` tinyint(1) DEFAULT 0,
  `showMaximumFee` tinyint(1) DEFAULT 0,
  `feeInformationText` text DEFAULT NULL,
  `defaultMaxFee` varchar(10) DEFAULT '0.00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS location;
CREATE TABLE `location` (
  `locationId` int(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique Id for the branch or location within vuFind',
  `code` varchar(75) DEFAULT NULL,
  `displayName` varchar(60) NOT NULL COMMENT 'The full name of the location for display to the user',
  `libraryId` int(11) NOT NULL COMMENT 'A link to the library which the location belongs to',
  `validHoldPickupBranch` tinyint(4) NOT NULL DEFAULT 1 COMMENT 'Determines if the location can be used as a pickup location if it is not the patrons home location or the location they are in.',
  `nearbyLocation1` int(11) DEFAULT NULL COMMENT 'A secondary location which is nearby and could be used for pickup of materials.',
  `nearbyLocation2` int(11) DEFAULT NULL COMMENT 'A tertiary location which is nearby and could be used for pickup of materials.',
  `scope` smallint(6) DEFAULT 0,
  `useScope` tinyint(4) DEFAULT 0,
  `facetFile` varchar(15) NOT NULL DEFAULT 'default' COMMENT 'The name of the facet file which should be used while searching use default to not override the file',
  `showHoldButton` tinyint(4) DEFAULT 1,
  `isMainBranch` tinyint(1) DEFAULT 0,
  `repeatSearchOption` enum('none','librarySystem','marmot','all') NOT NULL DEFAULT 'all' COMMENT 'Where to allow repeating search. Valid options are: none, librarySystem, marmot, all',
  `facetLabel` varchar(75) DEFAULT '',
  `repeatInInnReach` tinyint(4) DEFAULT 0,
  `repeatInWorldCat` tinyint(4) DEFAULT 0,
  `systemsToRepeatIn` varchar(255) DEFAULT '',
  `homeLink` varchar(255) NOT NULL DEFAULT 'default',
  `ptypesToAllowRenewals` varchar(128) NOT NULL DEFAULT '*',
  `automaticTimeoutLength` int(11) DEFAULT 90,
  `automaticTimeoutLengthLoggedOut` int(11) DEFAULT 450,
  `restrictSearchByLocation` tinyint(1) DEFAULT 0,
  `suppressHoldings` tinyint(1) DEFAULT 0,
  `additionalCss` longtext DEFAULT NULL,
  `repeatInOnlineCollection` int(11) DEFAULT 1,
  `econtentLocationsToInclude` varchar(255) DEFAULT NULL,
  `showInLocationsAndHoursList` int(11) DEFAULT 1,
  `showShareOnExternalSites` int(11) DEFAULT 1,
  `showEmailThis` int(11) DEFAULT 1,
  `showFavorites` int(11) DEFAULT 1,
  `address` longtext DEFAULT NULL,
  `phone` varchar(25) DEFAULT '',
  `showDisplayNameInHeader` tinyint(4) DEFAULT 0,
  `headerText` longtext DEFAULT NULL,
  `subLocation` varchar(50) DEFAULT NULL,
  `publicListsToInclude` tinyint(1) DEFAULT 6,
  `includeAllLibraryBranchesInFacets` tinyint(4) DEFAULT 1,
  `additionalLocationsToShowAvailabilityFor` varchar(100) NOT NULL DEFAULT '',
  `subdomain` varchar(25) DEFAULT '',
  `includeLibraryRecordsToInclude` tinyint(1) DEFAULT 0,
  `useLibraryCombinedResultsSettings` tinyint(1) DEFAULT 1,
  `enableCombinedResults` tinyint(1) DEFAULT 0,
  `combinedResultsLabel` varchar(255) DEFAULT 'Combined Results',
  `defaultToCombinedResults` tinyint(1) DEFAULT 0,
  `footerTemplate` varchar(40) NOT NULL DEFAULT 'default',
  `homePageWidgetId` varchar(50) DEFAULT '',
  `theme` int(11) DEFAULT 1,
  `hooplaScopeId` int(11) DEFAULT -1,
  `overDriveScopeId` int(11) DEFAULT -2,
  `groupedWorkDisplaySettingId` int(11) DEFAULT -1,
  `browseCategoryGroupId` int(11) NOT NULL DEFAULT -1,
  `historicCode` varchar(20) DEFAULT '',
  `axis360ScopeId` int(11) DEFAULT -1,
  `tty` varchar(25) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `createSearchInterface` tinyint(1) DEFAULT 1,
  `showInSelectInterface` tinyint(1) DEFAULT 0,
  `enableAppAccess` tinyint(1) DEFAULT 0,
  `latitude` varchar(75) DEFAULT '0',
  `longitude` varchar(75) DEFAULT '0',
  `unit` varchar(3) DEFAULT NULL,
  `appReleaseChannel` tinyint(1) DEFAULT 0,
  `contactEmail` varchar(250) DEFAULT NULL,
  `showOnDonationsPage` tinyint(1) DEFAULT 1,
  `curbsidePickupInstructions` varchar(255) DEFAULT NULL,
  `ebscohostSettingId` int(11) DEFAULT -2,
  `ebscohostSearchSettingId` int(11) DEFAULT -2,
  `lidaLocationSettingId` int(11) DEFAULT -1,
  `vdxLocation` varchar(50) DEFAULT NULL,
  `vdxFormId` int(11) DEFAULT NULL,
  `validSelfRegistrationBranch` tinyint(4) NOT NULL DEFAULT 1,
  `useLibraryThemes` tinyint(1) DEFAULT 1,
  `lidaSelfCheckSettingId` int(11) DEFAULT -1,
  `secondaryPhoneNumber` varchar(25) DEFAULT '',
  `openArchivesFacetSettingId` int(11) DEFAULT 1,
  `websiteIndexingFacetSettingId` int(11) DEFAULT 1,
  `palaceProjectScopeId` int(11) DEFAULT -1,
  `languageAndDisplayInHeader` int(11) DEFAULT 1,
  `locationImage` varchar(100) DEFAULT NULL,
  `allowUpdatingHoursFromILS` tinyint(1) DEFAULT 1,
  `displayExploreMoreBarInSummon` tinyint(1) DEFAULT 1,
  `displayExploreMoreBarInEbscoEds` tinyint(1) DEFAULT 1,
  `displayExploreMoreBarInCatalogSearch` tinyint(1) DEFAULT 1,
  `displayExploreMoreBarInEbscoHost` tinyint(1) DEFAULT 1,
  `statGroup` int(11) DEFAULT -1,
  `circulationUsername` varchar(20) DEFAULT NULL,
  `repeatInCloudSource` tinyint(4) DEFAULT 0,
  `repeatInShareIt` tinyint(1) DEFAULT 0,
  `localIllFormId` int(11) DEFAULT -1,
  PRIMARY KEY (`locationId`),
  UNIQUE KEY `code` (`code`,`subLocation`),
  KEY `ValidHoldPickupBranch` (`validHoldPickupBranch`),
  KEY `libraryId` (`libraryId`),
  KEY `ValidSelfRegistrationBranch` (`validSelfRegistrationBranch`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores information about the various locations that are part';
DROP TABLE IF EXISTS location_cloud_library_scope;
CREATE TABLE `location_cloud_library_scope` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `scopeId` int(11) NOT NULL,
  `locationId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `locationId` (`locationId`,`scopeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS location_combined_results_section;
CREATE TABLE `location_combined_results_section` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `locationId` int(11) NOT NULL,
  `displayName` varchar(255) DEFAULT NULL,
  `source` varchar(45) DEFAULT NULL,
  `numberOfResultsToShow` int(11) NOT NULL DEFAULT 5,
  `weight` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `LocationIdIndex` (`locationId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS location_hours;
CREATE TABLE `location_hours` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of hours entry',
  `locationId` int(11) NOT NULL COMMENT 'The location id',
  `day` int(11) NOT NULL COMMENT 'Day of the week 0 to 7 (Sun to Monday)',
  `closed` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Whether or not the library is closed on this day',
  `open` varchar(10) NOT NULL COMMENT 'Open hour (24hr format) HH:MM',
  `close` varchar(10) NOT NULL COMMENT 'Close hour (24hr format) HH:MM',
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `location` (`locationId`,`day`,`open`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS location_more_details;
CREATE TABLE `location_more_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `locationId` int(11) NOT NULL DEFAULT -1,
  `weight` int(11) NOT NULL DEFAULT 0,
  `source` varchar(25) NOT NULL,
  `collapseByDefault` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `locationId` (`locationId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS location_open_archives_collection;
CREATE TABLE `location_open_archives_collection` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collectionId` int(11) NOT NULL,
  `locationId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `collectionId` (`collectionId`,`locationId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS location_overdrive_scope;
CREATE TABLE `location_overdrive_scope` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `scopeId` int(11) NOT NULL,
  `locationId` int(11) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `locationId` (`locationId`,`scopeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS location_records_to_include;
CREATE TABLE `location_records_to_include` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `locationId` int(11) NOT NULL,
  `indexingProfileId` int(11) NOT NULL,
  `location` varchar(100) NOT NULL,
  `subLocation` varchar(150) NOT NULL DEFAULT '',
  `includeHoldableOnly` tinyint(4) NOT NULL DEFAULT 1,
  `includeItemsOnOrder` tinyint(1) NOT NULL DEFAULT 0,
  `includeEContent` tinyint(1) NOT NULL DEFAULT 0,
  `weight` int(11) NOT NULL DEFAULT 0,
  `iType` varchar(100) DEFAULT NULL,
  `audience` varchar(100) DEFAULT NULL,
  `format` varchar(100) DEFAULT NULL,
  `marcTagToMatch` varchar(100) DEFAULT NULL,
  `marcValueToMatch` varchar(100) DEFAULT NULL,
  `includeExcludeMatches` tinyint(4) DEFAULT 1,
  `urlToMatch` varchar(100) DEFAULT NULL,
  `urlReplacement` varchar(255) DEFAULT NULL,
  `locationsToExclude` text DEFAULT NULL,
  `subLocationsToExclude` varchar(400) NOT NULL DEFAULT '',
  `markRecordsAsOwned` tinyint(4) DEFAULT 0,
  `iTypesToExclude` varchar(100) NOT NULL DEFAULT '',
  `audiencesToExclude` varchar(100) NOT NULL DEFAULT '',
  `formatsToExclude` varchar(100) NOT NULL DEFAULT '',
  `shelfLocation` varchar(100) NOT NULL DEFAULT '',
  `shelfLocationsToExclude` varchar(100) NOT NULL DEFAULT '',
  `collectionCode` varchar(100) NOT NULL DEFAULT '',
  `collectionCodesToExclude` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `locationId` (`locationId`,`indexingProfileId`),
  KEY `indexingProfileId` (`indexingProfileId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS location_search_source;
CREATE TABLE `location_search_source` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `locationId` int(11) NOT NULL DEFAULT -1,
  `label` varchar(50) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT 0,
  `searchWhat` enum('catalog','genealogy','overdrive','worldcat','prospector','goldrush','title_browse','author_browse','subject_browse','tags') DEFAULT NULL,
  `defaultFilter` mediumtext DEFAULT NULL,
  `defaultSort` enum('relevance','popularity','newest_to_oldest','oldest_to_newest','author','title','user_rating') DEFAULT NULL,
  `catalogScoping` enum('unscoped','library','location') DEFAULT 'unscoped',
  PRIMARY KEY (`id`),
  KEY `locationId` (`locationId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS location_sideload_scopes;
CREATE TABLE `location_sideload_scopes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `locationId` int(11) NOT NULL,
  `sideLoadScopeId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `locationId` (`locationId`,`sideLoadScopeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS location_themes;
CREATE TABLE `location_themes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `locationId` int(11) NOT NULL,
  `themeId` int(11) NOT NULL,
  `weight` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `libraryToTheme` (`locationId`,`themeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS location_website_indexing;
CREATE TABLE `location_website_indexing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `settingId` int(11) NOT NULL,
  `locationId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settingId` (`settingId`,`locationId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS marriage;
CREATE TABLE `marriage` (
  `marriageId` int(11) NOT NULL AUTO_INCREMENT,
  `personId` int(11) NOT NULL COMMENT 'A link to one person in the marriage',
  `spouseName` varchar(200) DEFAULT NULL COMMENT 'The name of the other person in the marriage if they aren''t in the database',
  `spouseId` int(11) DEFAULT NULL COMMENT 'A link to the second person in the marriage if the person is in the database',
  `marriageDate` date DEFAULT NULL COMMENT 'The date of the marriage if known.',
  `comments` longtext DEFAULT NULL,
  `marriageDateDay` int(11) DEFAULT NULL COMMENT 'The day of the month the marriage occurred empty or null if not known',
  `marriageDateMonth` int(11) DEFAULT NULL COMMENT 'The month the marriage occurred, null or blank if not known',
  `marriageDateYear` int(11) DEFAULT NULL COMMENT 'The year the marriage occurred, null or blank if not known',
  PRIMARY KEY (`marriageId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Information about a marriage between two people';
DROP TABLE IF EXISTS materials_request;
CREATE TABLE `materials_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(10) unsigned DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `author` varchar(255) DEFAULT NULL,
  `format` varchar(25) DEFAULT NULL,
  `formatId` int(10) unsigned DEFAULT NULL,
  `ageLevel` varchar(25) DEFAULT NULL,
  `isbn` varchar(15) DEFAULT NULL,
  `oclcNumber` varchar(30) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `publicationYear` varchar(4) DEFAULT NULL,
  `articleInfo` varchar(255) DEFAULT NULL,
  `abridged` tinyint(4) DEFAULT NULL,
  `about` mediumtext DEFAULT NULL,
  `comments` mediumtext DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `dateCreated` int(11) DEFAULT NULL,
  `createdBy` int(11) DEFAULT NULL,
  `dateUpdated` int(11) DEFAULT NULL,
  `emailSent` tinyint(4) NOT NULL DEFAULT 0,
  `holdsCreated` tinyint(4) NOT NULL DEFAULT 0,
  `email` varchar(80) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `season` varchar(80) DEFAULT NULL,
  `magazineTitle` varchar(255) DEFAULT NULL,
  `upc` varchar(15) DEFAULT NULL,
  `issn` varchar(8) DEFAULT NULL,
  `bookType` varchar(20) DEFAULT NULL,
  `subFormat` varchar(20) DEFAULT NULL,
  `magazineDate` varchar(20) DEFAULT NULL,
  `magazineVolume` varchar(20) DEFAULT NULL,
  `magazinePageNumbers` varchar(20) DEFAULT NULL,
  `placeHoldWhenAvailable` tinyint(4) DEFAULT NULL,
  `holdPickupLocation` varchar(10) DEFAULT NULL,
  `bookmobileStop` varchar(50) DEFAULT NULL,
  `illItem` tinyint(4) DEFAULT NULL,
  `magazineNumber` varchar(80) DEFAULT NULL,
  `assignedTo` int(11) DEFAULT NULL,
  `staffComments` mediumtext DEFAULT NULL,
  `createdEmailSent` tinyint(1) DEFAULT 0,
  `readyForHolds` tinyint(1) DEFAULT 0,
  `selectedHoldCandidateId` int(11) DEFAULT 0,
  `holdFailureMessage` text DEFAULT NULL,
  `hasExistingRecord` tinyint(1) DEFAULT 0,
  `lastCheckForExistingRecord` int(11) DEFAULT -1,
  `existingRecordUrl` tinytext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `status_2` (`status`),
  KEY `createdBy` (`createdBy`),
  KEY `dateUpdated` (`dateUpdated`),
  KEY `dateCreated` (`dateCreated`),
  KEY `emailSent` (`emailSent`),
  KEY `holdsCreated` (`holdsCreated`),
  KEY `format` (`format`),
  KEY `subFormat` (`subFormat`),
  KEY `createdBy_2` (`createdBy`),
  KEY `dateUpdated_2` (`dateUpdated`),
  KEY `dateCreated_2` (`dateCreated`),
  KEY `emailSent_2` (`emailSent`),
  KEY `holdsCreated_2` (`holdsCreated`),
  KEY `format_2` (`format`),
  KEY `subFormat_2` (`subFormat`),
  KEY `status_3` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS materials_request_fields_to_display;
CREATE TABLE `materials_request_fields_to_display` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `columnNameToDisplay` varchar(30) NOT NULL,
  `labelForColumnToDisplay` varchar(45) NOT NULL,
  `weight` smallint(5) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `columnNameToDisplay` (`columnNameToDisplay`,`libraryId`),
  KEY `libraryId` (`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS materials_request_form_fields;
CREATE TABLE `materials_request_form_fields` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `libraryId` int(10) unsigned NOT NULL,
  `formCategory` varchar(55) NOT NULL,
  `fieldLabel` varchar(255) NOT NULL,
  `fieldType` varchar(30) DEFAULT NULL,
  `weight` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  KEY `libraryId` (`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS materials_request_format_mapping;
CREATE TABLE `materials_request_format_mapping` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `catalogFormat` varchar(255) NOT NULL,
  `materialsRequestFormatId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `libraryId` (`libraryId`,`catalogFormat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS materials_request_formats;
CREATE TABLE `materials_request_formats` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `libraryId` int(10) unsigned NOT NULL,
  `format` varchar(30) NOT NULL,
  `formatLabel` varchar(60) NOT NULL,
  `authorLabel` varchar(45) NOT NULL,
  `weight` smallint(5) unsigned NOT NULL DEFAULT 0,
  `specialFields` set('Abridged/Unabridged','Article Field','Eaudio format','Ebook format','Season') DEFAULT NULL,
  `activeForNewRequests` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `libraryId` (`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS materials_request_hold_candidate;
CREATE TABLE `materials_request_hold_candidate` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requestId` int(11) NOT NULL,
  `source` varchar(255) NOT NULL,
  `sourceId` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `requestId` (`requestId`,`source`,`sourceId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS materials_request_hold_candidate_generation_log;
CREATE TABLE `materials_request_hold_candidate_generation_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `startTime` int(11) NOT NULL,
  `endTime` int(11) DEFAULT NULL,
  `numRequestsChecked` int(11) DEFAULT 0,
  `numRequestsWithNewSuggestions` int(11) DEFAULT 0,
  `numSearchErrors` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `startTime` (`startTime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS materials_request_status;
CREATE TABLE `materials_request_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(80) DEFAULT NULL,
  `isDefault` tinyint(4) DEFAULT 0,
  `sendEmailToPatron` tinyint(4) DEFAULT NULL,
  `emailTemplate` mediumtext DEFAULT NULL,
  `isOpen` tinyint(4) DEFAULT NULL,
  `isPatronCancel` tinyint(4) DEFAULT NULL,
  `libraryId` int(11) DEFAULT -1,
  `checkForHolds` tinyint(1) DEFAULT 0,
  `holdPlacedSuccessfully` tinyint(1) DEFAULT 0,
  `holdFailed` tinyint(1) DEFAULT 0,
  `holdNotNeeded` tinyint(1) DEFAULT 0,
  `isActive` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `isDefault` (`isDefault`),
  KEY `isOpen` (`isOpen`),
  KEY `isPatronCancel` (`isPatronCancel`),
  KEY `isDefault_2` (`isDefault`),
  KEY `isOpen_2` (`isOpen`),
  KEY `isPatronCancel_2` (`isPatronCancel`),
  KEY `libraryId` (`libraryId`),
  KEY `isDefault_3` (`isDefault`),
  KEY `isOpen_3` (`isOpen`),
  KEY `isPatronCancel_3` (`isPatronCancel`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS materials_request_usage;
CREATE TABLE `materials_request_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) DEFAULT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `statusId` int(11) NOT NULL,
  `numUsed` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS merged_grouped_works;
CREATE TABLE `merged_grouped_works` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `sourceGroupedWorkId` char(36) NOT NULL,
  `destinationGroupedWorkId` char(36) NOT NULL,
  `notes` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `sourceGroupedWorkId` (`sourceGroupedWorkId`,`destinationGroupedWorkId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS millennium_cache;
CREATE TABLE `millennium_cache` (
  `recordId` varchar(20) NOT NULL COMMENT 'The recordId being checked',
  `scope` int(11) NOT NULL COMMENT 'The scope that was loaded',
  `holdingsInfo` longtext NOT NULL COMMENT 'Raw HTML returned from Millennium for holdings',
  `framesetInfo` longtext NOT NULL COMMENT 'Raw HTML returned from Millennium on the frameset page',
  `cacheDate` int(11) NOT NULL COMMENT 'When the entry was recorded in the cache',
  PRIMARY KEY (`recordId`,`scope`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Caches information from Millennium so we do not have to cont';

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `enabled` tinyint(1) DEFAULT 0,
  `indexName` varchar(50) DEFAULT '',
  `backgroundProcess` varchar(50) DEFAULT '',
  `logClassPath` varchar(100) DEFAULT NULL,
  `logClassName` varchar(35) DEFAULT NULL,
  `settingsClassPath` varchar(100) DEFAULT NULL,
  `settingsClassName` varchar(35) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `enabled` (`enabled`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `modules` WRITE;
/*!40000 ALTER TABLE `modules` DISABLE KEYS */;
INSERT INTO `modules` VALUES (1,'Koha',0,'grouped_works','koha_export','/sys/ILS/IlsExtractLogEntry.php','IlsExtractLogEntry','/sys/Indexing/IndexingProfile.php','IndexingProfile'),(2,'CARL.X',0,'grouped_works','carlx_export','/sys/ILS/IlsExtractLogEntry.php','IlsExtractLogEntry','/sys/Indexing/IndexingProfile.php','IndexingProfile'),(3,'Sierra',0,'grouped_works','sierra_export_api','/sys/ILS/IlsExtractLogEntry.php','IlsExtractLogEntry','/sys/Indexing/IndexingProfile.php','IndexingProfile'),(4,'Horizon',0,'grouped_works','horizon_export','/sys/ILS/IlsExtractLogEntry.php','IlsExtractLogEntry','/sys/Indexing/IndexingProfile.php','IndexingProfile'),(5,'Symphony',0,'grouped_works','symphony_export','/sys/ILS/IlsExtractLogEntry.php','IlsExtractLogEntry','/sys/Indexing/IndexingProfile.php','IndexingProfile'),(6,'Side Loads',1,'grouped_works','sideload_processing','/sys/Indexing/SideLoadLogEntry.php','SideLoadLogEntry',NULL,NULL),(7,'User Lists',1,'lists','user_list_indexer','/sys/UserLists/ListIndexingLogEntry.php','ListIndexingLogEntry','/sys/UserLists/ListIndexingSettings.php','ListIndexingSettings'),(8,'OverDrive',0,'grouped_works','overdrive_extract','/sys/OverDrive/OverDriveExtractLogEntry.php','OverDriveExtractLogEntry','/sys/OverDrive/OverDriveSetting.php','OverDriveSetting'),(9,'Hoopla',0,'grouped_works','hoopla_export','/sys/Hoopla/HooplaExportLogEntry.php','HooplaExportLogEntry','/sys/Hoopla/HooplaSetting.php','HooplaSetting'),(10,'RBdigital',0,'grouped_works','rbdigital_export','/sys/RBdigital/RBdigitalExportLogEntry.php','RBdigitalExportLogEntry','/sys/RBdigital/RBdigitalSetting.php','RBdigitalSetting'),(11,'Open Archives',0,'open_archives','','/sys/OpenArchives/OpenArchivesExportLogEntry.php','OpenArchivesExportLogEntry','/sys/OpenArchives/OpenArchivesCollection.php','OpenArchivesCollection'),(12,'Cloud Library',0,'grouped_works','cloud_library_export','/sys/CloudLibrary/CloudLibraryExportLogEntry.php','CloudLibraryExportLogEntry','/sys/CloudLibrary/CloudLibrarySetting.php','CloudLibrarySetting'),(13,'Web Indexer',0,'website_pages','web_indexer','/sys/WebsiteIndexing/WebsiteIndexLogEntry.php','WebsiteIndexLogEntry','/sys/WebsiteIndexing/WebsiteIndexSetting.php','WebsiteIndexSetting'),(14,'Events',0,'events','events_indexer','/sys/Events/EventsIndexingLogEntry.php','EventsIndexingLogEntry','/sys/Events/LMLibraryCalendarSetting.php','LMLibraryCalendarSetting'),(15,'EBSCO EDS',0,'','','','','',''),(16,'EBSCOhost',0,'','','','','',''),(17,'Web Builder',0,'web_builder','web_indexer','/sys/WebsiteIndexing/WebsiteIndexLogEntry.php','WebsiteIndexLogEntry',NULL,NULL),(18,'Genealogy',0,'genealogy','','','',NULL,NULL),(19,'Axis 360',0,'grouped_works','axis_360_export','/sys/Axis360/Axis360LogEntry.php','Axis360LogEntry','/sys/Axis360/Axis360Setting.php','Axis360Setting'),(20,'Polaris',0,'grouped_works','polaris_export','/sys/ILS/IlsExtractLogEntry.php','IlsExtractLogEntry','/sys/Indexing/IndexingProfile.php','IndexingProfile'),(21,'Course Reserves',0,'course_reserves','course_reserves_indexer','/sys/CourseReserves/CourseReservesIndexingLogEntry.php','CourseReservesIndexingLogEntry','/sys/CourseReserves/CourseReservesIndexingSettings.php','CourseReservesIndexingSettings'),(22,'Evergreen',0,'grouped_works','evergreen_export','/sys/ILS/IlsExtractLogEntry.php','IlsExtractLogEntry',NULL,NULL),(23,'FOLIO',0,'grouped_works','folio_export','/sys/ILS/IlsExtractLogEntry.php','IlsExtractLogEntry',NULL,NULL),(24,'Evolve',0,'grouped_works','evolve_export','/sys/ILS/IlsExtractLogEntry.php','IlsExtractLogEntry',NULL,NULL),(25,'Single sign-on',0,'','','','','',''),(26,'Aspen LiDA',0,'','',NULL,NULL,NULL,NULL),(27,'Palace Project',0,'grouped_works','palace_project_export','/sys/PalaceProject/PalaceProjectLogEntry.php','PalaceProjectLogEntry','/sys/PalaceProject/PalaceProjectSetting.php','PalaceProjectSetting'),(28,'Summon',0,'','',NULL,NULL,NULL,NULL),(29,'Series',0,'series','series_indexer','/sys/Series/SeriesIndexingLogEntry.php','SeriesIndexingLogEntry','/sys/Series/SeriesIndexingSettings.php','SeriesIndexingSettings'),(30,'Community Engagement',0,'','',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `modules` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

DROP TABLE IF EXISTS ncr_payments_settings;
CREATE TABLE `ncr_payments_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `clientKey` varchar(500) NOT NULL,
  `webKey` varchar(500) NOT NULL,
  `paymentTypeId` int(1) NOT NULL DEFAULT 0,
  `lastTransactionNumber` int(10) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS non_holdable_locations;
CREATE TABLE `non_holdable_locations` (
  `locationId` int(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique id for the non holdable location',
  `millenniumCode` varchar(5) NOT NULL COMMENT 'The internal 5 letter code within Millennium',
  `holdingDisplay` varchar(30) NOT NULL COMMENT 'The text displayed in the holdings list within Millennium',
  `availableAtCircDesk` tinyint(4) NOT NULL COMMENT 'The item is available if the patron visits the circulation desk.',
  PRIMARY KEY (`locationId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS nongrouped_records;
CREATE TABLE `nongrouped_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source` varchar(50) NOT NULL,
  `recordId` varchar(36) NOT NULL,
  `notes` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `source` (`source`,`recordId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS novelist_data;
CREATE TABLE `novelist_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupedRecordPermanentId` char(40) NOT NULL,
  `lastUpdate` int(11) DEFAULT NULL,
  `hasNovelistData` tinyint(1) DEFAULT NULL,
  `groupedRecordHasISBN` tinyint(1) DEFAULT NULL,
  `primaryISBN` varchar(13) DEFAULT NULL,
  `seriesTitle` varchar(255) DEFAULT NULL,
  `seriesNote` varchar(255) DEFAULT NULL,
  `volume` varchar(32) DEFAULT NULL,
  `jsonResponse` mediumblob DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `groupedRecordPermanentId` (`groupedRecordPermanentId`),
  KEY `primaryISBN` (`primaryISBN`),
  KEY `series` (`seriesTitle`,`volume`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS novelist_settings;
CREATE TABLE `novelist_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `profile` varchar(50) NOT NULL,
  `pwd` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS nyt_api_settings;
CREATE TABLE `nyt_api_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booksApiKey` varchar(32) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS nyt_update_log;
CREATE TABLE `nyt_update_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `startTime` int(11) NOT NULL,
  `endTime` int(11) DEFAULT NULL,
  `lastUpdate` int(11) DEFAULT NULL,
  `numErrors` int(11) NOT NULL DEFAULT 0,
  `numLists` int(11) NOT NULL DEFAULT 0,
  `numAdded` int(11) NOT NULL DEFAULT 0,
  `numUpdated` int(11) NOT NULL DEFAULT 0,
  `notes` mediumtext DEFAULT NULL,
  `numSkipped` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS obituary;
CREATE TABLE `obituary` (
  `obituaryId` int(11) NOT NULL AUTO_INCREMENT,
  `personId` int(11) NOT NULL COMMENT 'The person this obituary is for',
  `source` varchar(255) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `sourcePage` varchar(25) DEFAULT NULL,
  `contents` longtext DEFAULT NULL,
  `picture` varchar(255) DEFAULT NULL,
  `dateDay` int(11) DEFAULT NULL,
  `dateMonth` int(11) DEFAULT NULL,
  `dateYear` int(11) DEFAULT NULL,
  PRIMARY KEY (`obituaryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Information about an obituary for a person';
DROP TABLE IF EXISTS object_history;
CREATE TABLE `object_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `objectType` varchar(75) NOT NULL,
  `objectId` int(11) NOT NULL,
  `propertyName` varchar(75) NOT NULL,
  `oldValue` mediumtext DEFAULT NULL,
  `newValue` mediumtext DEFAULT NULL,
  `changedBy` int(11) NOT NULL,
  `changeDate` int(11) NOT NULL,
  `actionType` tinyint(4) DEFAULT 2,
  PRIMARY KEY (`id`),
  KEY `objectType` (`objectType`,`objectId`),
  KEY `changedBy` (`changedBy`),
  KEY `actionType` (`actionType`),
  KEY `changeDate` (`changeDate`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS offline_circulation;
CREATE TABLE `offline_circulation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timeEntered` int(11) NOT NULL,
  `timeProcessed` int(11) DEFAULT NULL,
  `itemBarcode` varchar(20) NOT NULL,
  `patronBarcode` varchar(20) DEFAULT NULL,
  `patronId` int(11) DEFAULT NULL,
  `login` varchar(50) DEFAULT NULL,
  `loginPassword` varchar(50) DEFAULT NULL,
  `initials` varchar(50) DEFAULT NULL,
  `initialsPassword` varchar(50) DEFAULT NULL,
  `type` enum('Check In','Check Out') DEFAULT NULL,
  `status` enum('Not Processed','Processing Succeeded','Processing Failed') DEFAULT NULL,
  `notes` varchar(512) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `timeEntered` (`timeEntered`),
  KEY `patronBarcode` (`patronBarcode`),
  KEY `patronId` (`patronId`),
  KEY `itemBarcode` (`itemBarcode`),
  KEY `login` (`login`),
  KEY `initials` (`initials`),
  KEY `type` (`type`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS offline_hold;
CREATE TABLE `offline_hold` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timeEntered` int(11) NOT NULL,
  `timeProcessed` int(11) DEFAULT NULL,
  `bibId` varchar(10) NOT NULL,
  `patronId` int(11) DEFAULT NULL,
  `patronBarcode` varchar(20) DEFAULT NULL,
  `status` enum('Not Processed','Hold Succeeded','Hold Failed') DEFAULT NULL,
  `notes` varchar(512) DEFAULT NULL,
  `patronName` varchar(200) DEFAULT NULL,
  `itemId` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `timeEntered` (`timeEntered`),
  KEY `timeProcessed` (`timeProcessed`),
  KEY `patronBarcode` (`patronBarcode`),
  KEY `patronId` (`patronId`),
  KEY `bibId` (`bibId`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS omdb_settings;
CREATE TABLE `omdb_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `apiKey` varchar(10) NOT NULL,
  `fetchCoversWithoutDates` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS open_archives_collection;
CREATE TABLE `open_archives_collection` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `baseUrl` varchar(255) NOT NULL,
  `setName` varchar(100) NOT NULL,
  `fetchFrequency` enum('hourly','daily','weekly','monthly','yearly','once') DEFAULT NULL,
  `lastFetched` int(11) DEFAULT NULL,
  `subjectFilters` longtext DEFAULT NULL,
  `subjects` longtext DEFAULT NULL,
  `loadOneMonthAtATime` tinyint(1) DEFAULT 1,
  `imageRegex` text DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0,
  `defaultCover` varchar(100) DEFAULT '',
  `metadataFormat` varchar(10) DEFAULT 'oai_dc',
  `indexAllSets` tinyint(1) DEFAULT 0,
  `dateFormatting` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS open_archives_export_log;
CREATE TABLE `open_archives_export_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `notes` mediumtext DEFAULT NULL COMMENT 'Additional information about the run',
  `collectionName` longtext DEFAULT NULL,
  `numRecords` int(11) DEFAULT 0,
  `numErrors` int(11) DEFAULT 0,
  `numAdded` int(11) DEFAULT 0,
  `numDeleted` int(11) DEFAULT 0,
  `numUpdated` int(11) DEFAULT 0,
  `numSkipped` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS open_archives_facet_groups;
CREATE TABLE `open_archives_facet_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
DROP TABLE IF EXISTS open_archives_facets;
CREATE TABLE `open_archives_facets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `facetGroupId` int(11) NOT NULL,
  `displayName` varchar(50) NOT NULL,
  `displayNamePlural` varchar(50) DEFAULT NULL,
  `facetName` varchar(50) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT 0,
  `numEntriesToShowByDefault` int(11) NOT NULL DEFAULT 5,
  `sortMode` enum('alphabetically','num_results') NOT NULL DEFAULT 'num_results',
  `collapseByDefault` tinyint(4) DEFAULT 1,
  `useMoreFacetPopup` tinyint(4) DEFAULT 1,
  `translate` tinyint(4) DEFAULT 1,
  `multiSelect` tinyint(4) DEFAULT 1,
  `canLock` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `groupFacet` (`facetGroupId`,`facetName`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
DROP TABLE IF EXISTS open_archives_record;
CREATE TABLE `open_archives_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sourceCollection` int(11) NOT NULL,
  `permanentUrl` varchar(512) NOT NULL,
  `lastSeen` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sourceCollection` (`sourceCollection`,`permanentUrl`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS open_archives_record_usage;
CREATE TABLE `open_archives_record_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `openArchivesRecordId` int(11) DEFAULT NULL,
  `year` int(11) NOT NULL,
  `timesViewedInSearch` int(11) NOT NULL,
  `timesUsed` int(11) NOT NULL,
  `month` int(11) NOT NULL DEFAULT 4,
  `instance` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`openArchivesRecordId`,`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS optional_updates;
CREATE TABLE `optional_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `descriptionFile` varchar(50) NOT NULL,
  `versionIntroduced` varchar(8) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS overdrive_account_cache;
CREATE TABLE `overdrive_account_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) DEFAULT NULL,
  `holdPage` longtext DEFAULT NULL,
  `holdPageLastLoaded` int(11) NOT NULL DEFAULT 0,
  `bookshelfPage` longtext DEFAULT NULL,
  `bookshelfPageLastLoaded` int(11) NOT NULL DEFAULT 0,
  `wishlistPage` longtext DEFAULT NULL,
  `wishlistPageLastLoaded` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='A cache to store information about a user''s account within OverDrive.';
DROP TABLE IF EXISTS overdrive_api_product_availability;
CREATE TABLE `overdrive_api_product_availability` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `productId` int(11) DEFAULT NULL,
  `libraryId` int(11) DEFAULT NULL,
  `available` tinyint(1) DEFAULT NULL,
  `copiesOwned` int(11) DEFAULT NULL,
  `copiesAvailable` int(11) DEFAULT NULL,
  `numberOfHolds` int(11) DEFAULT NULL,
  `availabilityType` varchar(35) DEFAULT 'Normal',
  `shared` tinyint(1) DEFAULT 0,
  `settingId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `productId` (`productId`,`settingId`,`libraryId`),
  KEY `libraryId` (`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS overdrive_api_product_formats;
CREATE TABLE `overdrive_api_product_formats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `productId` int(11) DEFAULT NULL,
  `textId` varchar(25) DEFAULT NULL,
  `numericId` int(11) DEFAULT NULL,
  `name` varchar(512) DEFAULT NULL,
  `fileName` varchar(215) DEFAULT NULL,
  `fileSize` int(11) DEFAULT NULL,
  `partCount` smallint(6) DEFAULT NULL,
  `sampleSource_1` varchar(215) DEFAULT NULL,
  `sampleUrl_1` varchar(215) DEFAULT NULL,
  `sampleSource_2` varchar(215) DEFAULT NULL,
  `sampleUrl_2` varchar(215) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `productId_2` (`productId`,`textId`),
  KEY `productId` (`productId`),
  KEY `numericId` (`numericId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS overdrive_api_product_identifiers;
CREATE TABLE `overdrive_api_product_identifiers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `productId` int(11) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `value` varchar(75) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `productId` (`productId`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS overdrive_api_product_metadata;
CREATE TABLE `overdrive_api_product_metadata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `productId` int(11) DEFAULT NULL,
  `checksum` bigint(20) DEFAULT NULL,
  `sortTitle` varchar(512) DEFAULT NULL,
  `publisher` varchar(215) DEFAULT NULL,
  `publishDate` int(11) DEFAULT NULL,
  `isPublicDomain` tinyint(1) DEFAULT NULL,
  `isPublicPerformanceAllowed` tinyint(1) DEFAULT NULL,
  `shortDescription` mediumtext DEFAULT NULL,
  `fullDescription` mediumtext DEFAULT NULL,
  `starRating` float DEFAULT NULL,
  `popularity` int(11) DEFAULT NULL,
  `rawData` mediumblob DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `cover` varchar(255) DEFAULT NULL,
  `isOwnedByCollections` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `productId` (`productId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS overdrive_api_products;
CREATE TABLE `overdrive_api_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `overdriveId` varchar(36) NOT NULL,
  `mediaType` varchar(50) NOT NULL,
  `title` varchar(512) NOT NULL,
  `series` varchar(255) DEFAULT NULL,
  `primaryCreatorRole` varchar(50) DEFAULT NULL,
  `primaryCreatorName` varchar(215) DEFAULT NULL,
  `cover` varchar(215) DEFAULT NULL,
  `dateAdded` int(11) DEFAULT NULL,
  `dateUpdated` int(11) DEFAULT NULL,
  `lastMetadataCheck` int(11) DEFAULT NULL,
  `lastMetadataChange` int(11) DEFAULT NULL,
  `lastAvailabilityCheck` int(11) DEFAULT NULL,
  `lastAvailabilityChange` int(11) DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0,
  `dateDeleted` int(11) DEFAULT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `crossRefId` int(11) DEFAULT 0,
  `lastSeen` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `overdriveId` (`overdriveId`),
  KEY `dateUpdated` (`dateUpdated`),
  KEY `lastMetadataCheck` (`lastMetadataCheck`),
  KEY `lastAvailabilityCheck` (`lastAvailabilityCheck`),
  KEY `deleted` (`deleted`),
  KEY `crossRefId` (`crossRefId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS overdrive_extract_log;
CREATE TABLE `overdrive_extract_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `startTime` int(11) DEFAULT NULL,
  `endTime` int(11) DEFAULT NULL,
  `lastUpdate` int(11) DEFAULT NULL,
  `numProducts` int(11) DEFAULT 0,
  `numErrors` int(11) DEFAULT 0,
  `numAdded` int(11) DEFAULT 0,
  `numDeleted` int(11) DEFAULT 0,
  `numUpdated` int(11) DEFAULT 0,
  `numSkipped` int(11) DEFAULT 0,
  `numAvailabilityChanges` int(11) DEFAULT 0,
  `numMetadataChanges` int(11) DEFAULT 0,
  `notes` mediumtext DEFAULT NULL,
  `settingId` int(11) DEFAULT NULL,
  `numInvalidRecords` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `startTime` (`startTime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS overdrive_record_usage;
CREATE TABLE `overdrive_record_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `overdriveId` varchar(36) DEFAULT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `timesHeld` int(11) NOT NULL,
  `timesCheckedOut` int(11) NOT NULL,
  `instance` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`overdriveId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS overdrive_scopes;
CREATE TABLE `overdrive_scopes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `includeAdult` tinyint(4) DEFAULT 1,
  `includeTeen` tinyint(4) DEFAULT 1,
  `includeKids` tinyint(4) DEFAULT 1,
  `authenticationILSName` varchar(45) DEFAULT NULL,
  `requirePin` tinyint(1) DEFAULT 0,
  `overdriveAdvantageName` varchar(128) DEFAULT '',
  `overdriveAdvantageProductsKey` varchar(20) DEFAULT '',
  `settingId` int(11) DEFAULT NULL,
  `clientSecret` varchar(256) DEFAULT NULL,
  `clientKey` varchar(50) DEFAULT NULL,
  `circulationEnabled` tinyint(4) DEFAULT 1,
  `readerName` varchar(25) DEFAULT 'Libby',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS overdrive_settings;
CREATE TABLE `overdrive_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(255) DEFAULT NULL,
  `patronApiUrl` varchar(255) DEFAULT NULL,
  `clientSecret` varchar(256) DEFAULT NULL,
  `clientKey` varchar(50) DEFAULT NULL,
  `accountId` int(11) DEFAULT 0,
  `websiteId` int(11) DEFAULT 0,
  `productsKey` varchar(50) DEFAULT '0',
  `runFullUpdate` tinyint(1) DEFAULT 0,
  `lastUpdateOfChangedRecords` int(11) DEFAULT 0,
  `lastUpdateOfAllRecords` int(11) DEFAULT 0,
  `allowLargeDeletes` tinyint(1) DEFAULT 1,
  `numExtractionThreads` int(11) DEFAULT 10,
  `showLibbyPromo` tinyint(1) DEFAULT 1,
  `enableRequestLogging` tinyint(1) DEFAULT NULL,
  `numRetriesOnError` int(11) DEFAULT 1,
  `productsToUpdate` text DEFAULT NULL,
  `readerName` varchar(25) DEFAULT 'Libby',
  `name` varchar(125) DEFAULT 'Libby',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS overdrive_stats;
CREATE TABLE `overdrive_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance` varchar(100) DEFAULT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `numCheckouts` int(11) NOT NULL DEFAULT 0,
  `numFailedCheckouts` int(11) NOT NULL DEFAULT 0,
  `numRenewals` int(11) NOT NULL DEFAULT 0,
  `numEarlyReturns` int(11) NOT NULL DEFAULT 0,
  `numHoldsPlaced` int(11) NOT NULL DEFAULT 0,
  `numFailedHolds` int(11) NOT NULL DEFAULT 0,
  `numHoldsCancelled` int(11) NOT NULL DEFAULT 0,
  `numHoldsFrozen` int(11) NOT NULL DEFAULT 0,
  `numHoldsThawed` int(11) NOT NULL DEFAULT 0,
  `numDownloads` int(11) NOT NULL DEFAULT 0,
  `numPreviews` int(11) NOT NULL DEFAULT 0,
  `numOptionsUpdates` int(11) NOT NULL DEFAULT 0,
  `numApiErrors` int(11) NOT NULL DEFAULT 0,
  `numConnectionFailures` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `instance` (`instance`,`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS palace_project_collections;
CREATE TABLE `palace_project_collections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `settingId` int(11) NOT NULL,
  `palaceProjectName` varchar(255) NOT NULL,
  `displayName` varchar(255) NOT NULL,
  `hasCirculation` tinyint(1) DEFAULT NULL,
  `includeInAspen` tinyint(1) DEFAULT 1,
  `lastIndexed` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settingId` (`settingId`,`palaceProjectName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS palace_project_export_log;
CREATE TABLE `palace_project_export_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `notes` mediumtext DEFAULT NULL COMMENT 'Additional information about the run',
  `numProducts` int(11) DEFAULT 0,
  `numErrors` int(11) DEFAULT 0,
  `numAdded` int(11) DEFAULT 0,
  `numDeleted` int(11) DEFAULT 0,
  `numUpdated` int(11) DEFAULT 0,
  `numSkipped` int(11) DEFAULT 0,
  `numChangedAfterGrouping` int(11) DEFAULT 0,
  `numRegrouped` int(11) DEFAULT 0,
  `numInvalidRecords` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `startTime` (`startTime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS palace_project_record_usage;
CREATE TABLE `palace_project_record_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance` varchar(100) DEFAULT NULL,
  `palaceProjectId` int(11) DEFAULT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `timesHeld` int(11) NOT NULL DEFAULT 0,
  `timesCheckedOut` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `instance` (`instance`,`palaceProjectId`,`year`,`month`),
  KEY `instance_2` (`instance`,`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS palace_project_scopes;
CREATE TABLE `palace_project_scopes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `settingId` int(11) DEFAULT NULL,
  `includeAdult` tinyint(4) DEFAULT 1,
  `includeTeen` tinyint(4) DEFAULT 1,
  `includeKids` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
DROP TABLE IF EXISTS palace_project_settings;
CREATE TABLE `palace_project_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `apiUrl` varchar(255) DEFAULT NULL,
  `libraryId` varchar(50) DEFAULT NULL,
  `regroupAllRecords` tinyint(1) DEFAULT 0,
  `runFullUpdate` tinyint(1) DEFAULT 0,
  `lastUpdateOfChangedRecords` int(11) DEFAULT 0,
  `lastUpdateOfAllRecords` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
DROP TABLE IF EXISTS palace_project_title;
CREATE TABLE `palace_project_title` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `palaceProjectId` varchar(125) DEFAULT NULL,
  `title` varchar(750) DEFAULT NULL,
  `rawChecksum` bigint(20) DEFAULT NULL,
  `rawResponse` mediumblob DEFAULT NULL,
  `dateFirstDetected` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `palaceProjectId` (`palaceProjectId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS palace_project_title_availability;
CREATE TABLE `palace_project_title_availability` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titleId` int(11) NOT NULL,
  `collectionId` int(11) NOT NULL,
  `lastSeen` int(11) NOT NULL,
  `deleted` tinyint(1) DEFAULT NULL,
  `borrowLink` tinytext DEFAULT NULL,
  `needsHold` tinyint(4) DEFAULT 1,
  `previewLink` tinytext DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `titleId` (`titleId`,`collectionId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS paypal_payflow_settings;
CREATE TABLE `paypal_payflow_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `sandboxMode` tinyint(1) DEFAULT 0,
  `partner` varchar(72) NOT NULL,
  `vendor` varchar(72) NOT NULL,
  `user` varchar(72) NOT NULL,
  `password` varchar(72) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS paypal_settings;
CREATE TABLE `paypal_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `sandboxMode` tinyint(1) DEFAULT NULL,
  `clientId` varchar(80) DEFAULT NULL,
  `clientSecret` varchar(80) DEFAULT NULL,
  `showPayLater` tinyint(1) DEFAULT 0,
  `errorEmail` varchar(128) DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(75) NOT NULL,
  `sectionName` varchar(75) NOT NULL,
  `requiredModule` varchar(50) NOT NULL DEFAULT '',
  `weight` int(11) NOT NULL DEFAULT 0,
  `description` varchar(250) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=243 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'Administer Modules','System Administration','',0,'Allow information about Aspen Discovery Modules to be displayed and enabled or disabled.'),(2,'Administer Users','System Administration','',10,'Allows configuration of who has administration privileges within Aspen Discovery. <i>Give to trusted users, this has security implications.</i>'),(3,'Administer Permissions','System Administration','',15,'Allows configuration of the roles within Aspen Discovery and what each role can do. <i>Give to trusted users, this has security implications.</i>'),(4,'Run Database Maintenance','System Administration','',20,'Controls if the user can run database maintenance or not.'),(5,'Administer SendGrid','System Administration','',30,'Controls if the user can change SendGrid settings. <em>This has potential security and cost implications.</em>'),(6,'Administer System Variables','System Administration','',40,'Controls if the user can change system variables.'),(7,'View System Reports','Reporting','',0,'Controls if the user can view System Reports that show how Aspen Discovery performs and how background tasks are operating. Includes Indexing Logs and Dashboards.'),(8,'View Indexing Logs','Reporting','',10,'Controls if the user can view Indexing Logs for the ILS and eContent.'),(9,'View Dashboards','Reporting','',20,'Controls if the user can view Dashboards showing usage information.'),(10,'Administer All Themes','Theme & Layout','',0,'Allows the user to control all themes within Aspen Discovery.'),(11,'Administer Library Themes','Theme & Layout','',10,'Allows the user to control theme for their home library within Aspen Discovery.'),(12,'Administer All Layout Settings','Theme & Layout','',20,'Allows the user to view and change all layout settings within Aspen Discovery.'),(13,'Administer Library Layout Settings','Theme & Layout','',30,'Allows the user to view and change layout settings for their home library within Aspen Discovery.'),(14,'Administer All Libraries','Primary Configuration','',0,'Allows the user to control settings for all libraries within Aspen Discovery.'),(15,'Administer Home Library','Primary Configuration','',10,'Allows the user to control settings for their home library'),(16,'Administer All Locations','Primary Configuration','',20,'Allows the user to control settings for all locations.'),(17,'Administer Home Library Locations','Primary Configuration','',30,'Allows the user to control settings for all locations that are part of their home library.'),(18,'Administer Home Location','Primary Configuration','',40,'Allows the user to control settings for their home location.'),(19,'Administer IP Addresses','Primary Configuration','',50,'Allows the user to administer IP addresses for Aspen Discovery. <em>This has potential security implications</em>'),(20,'Administer Patron Types','Primary Configuration','',60,'Allows the user to administer how patron types in the ILS are handled within for Aspen Discovery. <i>Give to trusted users, this has security implications.</i>'),(21,'Administer Account Profiles','Primary Configuration','',70,'Allows the user to administer patrons are loaded from the ILS and/or the database. <i>Give to trusted users, this has security implications.</i>'),(22,'Block Patron Account Linking','Primary Configuration','',80,'Allows the user to prevent users from linking to other users.'),(23,'Manage Library Materials Requests','Materials Requests','',0,'Allows the user to update and process materials requests for patrons.'),(24,'Administer Materials Requests','Materials Requests','',10,'Allows the user to configure the materials requests system for their library.'),(25,'View Materials Requests Reports','Materials Requests','',20,'Allows the user to view reports about the materials requests system for their library.'),(26,'Import Materials Requests','Materials Requests','',30,'Allows the user to import materials requests from older systems. <em>Not recommended in most cases unless an active conversion is being done.</em>'),(27,'Administer Languages','Languages and Translations','',0,'Allows the user to control which languages are available for the Aspen Discovery interface.'),(28,'Translate Aspen','Languages and Translations','',10,'Allows the user to translate the Aspen Discovery interface.'),(29,'Manually Group and Ungroup Works','Cataloging & eContent','',0,'Allows the user to manually group and ungroup works.'),(30,'Set Grouped Work Display Information','Cataloging & eContent','',10,'Allows the user to override title, author, and series information for a grouped work.'),(31,'Force Reindexing of Records','Cataloging & eContent','',20,'Allows the user to force individual records to be indexed.'),(32,'Upload Covers','Cataloging & eContent','',30,'Allows the user to upload covers for a record.'),(33,'Upload PDFs','Cataloging & eContent','',40,'Allows the user to upload PDFs for a record.'),(34,'Upload Supplemental Files','Cataloging & eContent','',50,'Allows the user to upload supplemental for a record.'),(35,'Download MARC Records','Cataloging & eContent','',52,'Allows the user to download MARC records for individual records.'),(36,'View ILS records in native OPAC','Cataloging & eContent','',55,'Allows the user to view ILS records in the native OPAC for the ILS if available.'),(37,'View ILS records in native Staff Client','Cataloging & eContent','',56,'Allows the user to view ILS records in the staff client for the ILS if available.'),(38,'Administer Indexing Profiles','Cataloging & eContent','',60,'Allows the user to administer Indexing Profiles to define how record from the ILS are indexed in Aspen Discovery.'),(39,'Administer Translation Maps','Cataloging & eContent','',70,'Allows the user to administer how fields within the ILS are mapped to Aspen Discovery.'),(40,'Administer Loan Rules','Cataloging & eContent','',80,'Allows the user to administer load loan rules and loan rules into Aspen Discovery (Sierra & Millenium only).'),(41,'View Offline Holds Report','Cataloging & eContent','',90,'Allows the user to see any holds that were entered while the ILS was offline.'),(42,'Administer Boundless','Cataloging & eContent','Axis 360',100,'Allows the user configure Boundless integration for all libraries.'),(43,'Administer Cloud Library','Cataloging & eContent','Cloud Library',110,'Allows the user configure Cloud Library integration for all libraries.'),(44,'Administer EBSCO EDS','Cataloging & eContent','EBSCO EDS',120,'Allows the user configure EBSCO EDS integration for all libraries.'),(45,'Administer Hoopla','Cataloging & eContent','Hoopla',130,'Allows the user configure Hoopla integration for all libraries.'),(46,'Administer Libby/Sora','Cataloging & eContent','OverDrive',140,'Allows the user configure Libby/Sora integration for all libraries.'),(47,'View OverDrive Test Interface','Cataloging & eContent','OverDrive',150,'Allows the user view OverDrive API information and call OverDrive for specific records.'),(48,'Administer RBdigital','Cataloging & eContent','RBdigital',160,'Allows the user configure RBdigital integration for all libraries.'),(49,'Administer Side Loads','Cataloging & eContent','Side Loads',170,'Controls if the user can administer side loads.'),(50,'Administer All Grouped Work Display Settings','Grouped Work Display','',0,'Allows the user to view and change all grouped work display settings within Aspen Discovery.'),(51,'Administer Library Grouped Work Display Settings','Grouped Work Display','',10,'Allows the user to view and change grouped work display settings for their home library within Aspen Discovery.'),(52,'Administer All Grouped Work Facets','Grouped Work Display','',20,'Allows the user to view and change all grouped work facets within Aspen Discovery.'),(53,'Administer Library Grouped Work Facets','Grouped Work Display','',30,'Allows the user to view and change grouped work facets for their home library within Aspen Discovery.'),(54,'Administer All Browse Categories','Local Enrichment','',0,'Allows the user to view and change all browse categories within Aspen Discovery.'),(55,'Administer Library Browse Categories','Local Enrichment','',10,'Allows the user to view and change browse categories for their home library within Aspen Discovery.'),(56,'Administer All Collection Spotlights','Local Enrichment','',20,'Allows the user to view and change all collection spotlights within Aspen Discovery.'),(57,'Administer Library Collection Spotlights','Local Enrichment','',30,'Allows the user to view and change collection spotlights for their home library within Aspen Discovery.'),(58,'Administer All Placards','Local Enrichment','',40,'Allows the user to view and change all placards within Aspen Discovery.'),(59,'Administer Library Placards','Local Enrichment','',50,'Allows the user to view and change placards for their home library within Aspen Discovery.'),(60,'Moderate User Reviews','Local Enrichment','',60,'Allows the delete any user review within Aspen Discovery.'),(61,'Administer Third Party Enrichment API Keys','Third Party Enrichment','',0,'Allows the user to define connection to external enrichment systems like Content Cafe, Syndetics, Google, NoveList etc.'),(62,'Administer Wikipedia Integration','Third Party Enrichment','',10,'Allows the user to control how authors are matched to Wikipedia entries.'),(63,'View New York Times Lists','Third Party Enrichment','',20,'Allows the user to view and update lists loaded from the New York Times.'),(64,'Administer Islandora Archive','Islandora Archives','Islandora',0,'Allows the user to administer integration with an Islandora archive.'),(65,'View Archive Authorship Claims','Islandora Archives','Islandora',10,'Allows the user to view authorship claims for Islandora archive materials.'),(66,'View Library Archive Authorship Claims','Islandora Archives','Islandora',12,'Allows the user to view authorship claims for Islandora archive materials.'),(67,'View Archive Material Requests','Islandora Archives','Islandora',20,'Allows the user to view material requests for Islandora archive materials.'),(68,'View Library Archive Material Requests','Islandora Archives','Islandora',22,'Allows the user to view material requests for Islandora archive materials.'),(69,'View Islandora Archive Usage','Islandora Archives','Islandora',30,'Allows the view a report of objects in the repository by library.'),(70,'Administer Open Archives','Open Archives','Open Archives',0,'Allows the user to administer integration with Open Archives repositories for all libraries.'),(71,'Administer LibraryMarket LibraryCalendar Settings','Events','Events',10,'Allows the user to administer integration with LibraryMarket LibraryCalendar for all libraries.'),(72,'Administer Website Indexing Settings','Website Indexing','Web Indexer',0,'Allows the user to administer the indexing of websites for all libraries.'),(75,'Submit Ticket','Aspen Discovery Help','',20,'Allows the user to submit Aspen Discovery tickets.'),(76,'Administer Genealogy','Genealogy','Genealogy',0,'Allows the user to add people, marriages, and obituaries to the genealogy interface.'),(77,'Include Lists In Search Results','User Lists','',0,'Allows the user to add public lists to search results.'),(78,'Edit All Lists','User Lists','',10,'Allows the user to edit public lists created by any user.'),(79,'Masquerade as any user','Masquerade','',0,'Allows the user to masquerade as any other user including restricted patron types.'),(80,'Masquerade as unrestricted patron types','Masquerade','',10,'Allows the user to masquerade as any other user if their patron type is unrestricted.'),(81,'Masquerade as patrons with same home library','Masquerade','',20,'Allows the user to masquerade as patrons with the same home library including restricted patron types.'),(82,'Masquerade as unrestricted patrons with same home library','Masquerade','',30,'Allows the user to masquerade as patrons with the same home library if their patron type is unrestricted.'),(83,'Masquerade as patrons with same home location','Masquerade','',40,'Allows the user to masquerade as patrons with the same home location including restricted patron types.'),(84,'Masquerade as unrestricted patrons with same home location','Masquerade','',50,'Allows the user to masquerade as patrons with the same home location if their patron type is unrestricted.'),(85,'Test Roles','System Administration','',17,'Allows the user to use the test_role parameter to act as different role.'),(86,'Administer List Indexing Settings','User Lists','',0,'Allows the user to administer list indexing settings.'),(87,'View Location Holds Reports','Circulation Reports','',0,'Allows the user to view lists of holds to be pulled for their home location (CARL.X) only.'),(88,'View All Holds Reports','Circulation Reports','',10,'Allows the user to view lists of holds to be pulled for any location (CARL.X) only.'),(89,'View Location Student Reports','Circulation Reports','',20,'Allows the user to view barcode and checkout reports for their home location (CARL.X) only.'),(90,'View All Student Reports','Circulation Reports','',30,'Allows the user to view barcode and checkout reports for any location (CARL.X) only.'),(91,'View Unpublished Content','Content Builder','',0,'Allows the user to view unpublished menu items and content.'),(92,'Administer All JavaScript Snippets','Local Enrichment','',70,'Allows the user to define JavaScript Snippets to be added to the site. This permission has security implications.'),(93,'Administer Library JavaScript Snippets','Local Enrichment','',71,'Allows the user to define JavaScript Snippets to be added to the site for their library. This permission has security implications.'),(94,'Administer Host Information','System Administration','',50,'Allows the user to change information about the hosts used for Aspen Discovery.'),(95,'Administer All Menus','Web Builder','Web Builder',0,'Allows the user to define the menu for all libraries.'),(96,'Administer Library Menus','Web Builder','Web Builder',1,'Allows the user to define the menu for their home library.'),(97,'Administer All Basic Pages','Web Builder','Web Builder',10,'Allows the user to define basic pages for all libraries.'),(98,'Administer Library Basic Pages','Web Builder','Web Builder',11,'Allows the user to define basic pages for their home library.'),(99,'Administer All Custom Pages','Web Builder','Web Builder',20,'Allows the user to define custom pages for all libraries.'),(100,'Administer Library Custom Pages','Web Builder','Web Builder',21,'Allows the user to define custom pages for their home library.'),(101,'Administer All Custom Forms','Web Builder','Web Builder',30,'Allows the user to define custom forms for all libraries.'),(102,'Administer Library Custom Forms','Web Builder','Web Builder',31,'Allows the user to define custom forms for their home library.'),(103,'Administer All Web Resources','Web Builder','Web Builder',40,'Allows the user to add web resources for all libraries.'),(104,'Administer Library Web Resources','Web Builder','Web Builder',41,'Allows the user to add web resources for their home library.'),(105,'Administer All Staff Members','Web Builder','Web Builder',50,'Allows the user to add staff members for all libraries.'),(106,'Administer Library Staff Members','Web Builder','Web Builder',51,'Allows the user to add staff members for their home library.'),(107,'Administer All Web Content','Web Builder','Web Builder',60,'Allows the user to add images, pdfs, and videos.'),(108,'Administer All Web Categories','Web Builder','Web Builder',70,'Allows the user to define audiences and categories for content.'),(109,'Administer All System Messages','Local Enrichment','',70,'Allows the user to define system messages for all libraries within Aspen Discovery.'),(110,'Administer Library System Messages','Local Enrichment','',80,'Allows the user to define system messages for their library within Aspen Discovery.'),(111,'Administer Amazon SES','System Administration','',29,'Controls if the user can change Amazon SES settings. <em>This has potential security and cost implications.</em>'),(113,'Upload List Covers','User Lists','',1,'Allows users to upload covers for a list.'),(115,'Library Domain Settings','Primary Configuration - Library Fields','',1,'Configure Library fields related to URLs and base configuration to access Aspen.'),(116,'Library Theme Configuration','Primary Configuration - Library Fields','',3,'Configure Library fields related to how theme display is configured for the library.'),(117,'Library Contact Settings','Primary Configuration - Library Fields','',6,'Configure Library fields related to contact information for the library.'),(118,'Library ILS Connection','Primary Configuration - Library Fields','',9,'Configure Library fields related to how Aspen connects to the ILS and settings that depend on how the ILS is configured.'),(119,'Library ILS Options','Primary Configuration - Library Fields','',12,'Configure Library fields related to how Aspen interacts with the ILS.'),(120,'Library Registration','Primary Configuration - Library Fields','',15,'Configure Library fields related to how Self Registration and Third Party Registration is configured in Aspen.'),(121,'Library eCommerce Options','Primary Configuration - Library Fields','',18,'Configure Library fields related to how eCommerce is configured in Aspen.'),(122,'Library Catalog Options','Primary Configuration - Library Fields','',21,'Configure Library fields related to how Catalog results and searching is configured in Aspen.'),(123,'Library Browse Category Options','Primary Configuration - Library Fields','',24,'Configure Library fields related to how browse categories are configured in Aspen.'),(124,'Library Materials Request Options','Primary Configuration - Library Fields','',27,'Configure Library fields related to how materials request is configured in Aspen.'),(125,'Library ILL Options','Primary Configuration - Library Fields','',30,'Configure Library fields related to how ill is configured in Aspen.'),(126,'Library Records included in Catalog','Primary Configuration - Library Fields','',33,'Configure Library fields related to what materials (physical and eContent) are included in the Aspen Catalog.'),(127,'Library Genealogy Content','Primary Configuration - Library Fields','',36,'Configure Library fields related to genealogy content.'),(128,'Library Islandora Archive Options','Primary Configuration - Library Fields','',39,'Configure Library fields related to Islandora based archive.'),(129,'Library Archive Options','Primary Configuration - Library Fields','',42,'Configure Library fields related to open archives content.'),(130,'Library Web Builder Options','Primary Configuration - Library Fields','',45,'Configure Library fields related to web builder content.'),(131,'Library EDS Options','Primary Configuration - Library Fields','',48,'Configure Library fields related to EDS content.'),(132,'Library Holidays','Primary Configuration - Library Fields','',51,'Configure Library holidays.'),(133,'Library Menu','Primary Configuration - Library Fields','',42,'Configure Library menu.'),(134,'Location Domain Settings','Primary Configuration - Location Fields','',1,'Configure Location fields related to URLs and base configuration to access Aspen.'),(135,'Location Theme Configuration','Primary Configuration - Location Fields','',3,'Configure Location fields related to how theme display is configured for the library.'),(136,'Location Address and Hours Settings','Primary Configuration - Location Fields','',6,'Configure Location fields related to the address and hours of operation.'),(137,'Location ILS Connection','Primary Configuration - Location Fields','',9,'Configure Location fields related to how Aspen connects to the ILS and settings that depend on how the ILS is configured.'),(138,'Location ILS Options','Primary Configuration - Location Fields','',12,'Configure Location fields related to how Aspen interacts with the ILS.'),(139,'Location Catalog Options','Primary Configuration - Location Fields','',15,'Configure Location fields related to how Catalog results and searching is configured in Aspen.'),(140,'Location Browse Category Options','Primary Configuration - Location Fields','',18,'Configure Location fields related to how Catalog results and searching is configured in Aspen.'),(141,'Location Records included in Catalog','Primary Configuration - Location Fields','',21,'Configure Location fields related to what materials (physical and eContent) are included in the Aspen Catalog.'),(142,'Administer Comprise','eCommerce','',10,'Controls if the user can change Comprise settings. <em>This has potential security and cost implications.</em>'),(143,'Administer ProPay','eCommerce','',10,'Controls if the user can change ProPay settings. <em>This has potential security and cost implications.</em>'),(144,'Administer PayPal','eCommerce','',10,'Controls if the user can change PayPal settings. <em>This has potential security and cost implications.</em>'),(145,'Administer WorldPay','eCommerce','',10,'Controls if the user can change WorldPay settings. <em>This has potential security and cost implications.</em>'),(146,'View eCommerce Reports for All Libraries','eCommerce','',5,'Allows the user to view eCommerce reports for all libraries.'),(147,'Edit Library Placards','Local Enrichment','',55,'Allows the user to edit, but not create placards for their library.'),(148,'Administer Donations','eCommerce','',10,'Controls if the user can change Donations settings. <em>This has potential security and cost implications.</em>'),(149,'Administer Course Reserves','Course Reserves','',10,'Controls if the user can change Course Reserve settings.'),(150,'View Donations Reports for All Libraries','eCommerce','',7,'Allows the user to view donations reports for all libraries.'),(151,'Administer Curbside Pickup','Curbside Pickup','',10,'Controls if the user can change Curbside Pickup settings.'),(152,'Administer Two-Factor Authentication','Primary Configuration','',90,'Controls if the user can change Two-Factor Authentication settings. <em>This has potential security and cost implications.</em>'),(153,'Administer Aspen LiDA Settings','Aspen LiDA','Aspen LiDA',10,'Controls if the user can change Aspen LiDA settings.'),(154,'Administer Grouped Work Tests','Cataloging & eContent','',200,'Controls if the user can define and access tests of Grouped Work searches.'),(155,'Administer Request Tracker Connection','Aspen Discovery Support','',10,'Allows configuration of connection to the support system.'),(156,'View Active Tickets','Aspen Discovery Support','',20,'Allows display of active tickets within the support system.'),(157,'Set Development Priorities','Aspen Discovery Support','',30,'Allows setting of priorities for development.'),(158,'Administer Springshare LibCal Settings','Events','Events',20,'Allows the user to administer integration with Springshare LibCal for all libraries.'),(159,'Administer Bad Words','Local Enrichment','',65,'Allows the user to administer bad words list.'),(160,'Administer Xpress-pay','eCommerce','',10,'Controls if the user can change Xpress-pay settings. <em>This has potential security and cost implications.</em>'),(161,'Administer EBSCOhost Settings','Cataloging & eContent','EBSCOhost',20,'Allows the user to administer integration with EBSCOhost'),(162,'Administer Hold Groups','ILL Integration','',15,'Allows the user to define Hold Groups for Interlibrary Loans.'),(163,'Administer VDX Settings','ILL Integration','',10,'Allows the user to define settings for Interlibrary Loans with VDX.'),(164,'Administer All VDX Forms','ILL Integration','',20,'Allows the user to define administer all VDX Forms.'),(165,'Administer Library VDX Forms','ILL Integration','',22,'Allows the user to define administer VDX Forms for their library.'),(166,'View Notifications Reports','Aspen LiDA','Aspen LiDA',6,'Controls if the user can view the Notifications Report.</em>'),(167,'Administer ACI Speedpay','eCommerce','',10,'Controls if the user can change ACI Speedpay settings. <em>This has potential security and cost implications.</em>'),(168,'Hide Metadata','Cataloging & eContent','',85,'Controls if the user can hide metadata like Subjects and Series from facets and display information.'),(169,'Send Notifications to All Libraries','Aspen LiDA','Aspen LiDA',6,'Controls if the user can send notifications to Aspen LiDA users from all libraries'),(170,'Batch Delete','System Administration','',6,'Controls if the user is able to batch delete.</em>'),(171,'Send Notifications to Home Library','Aspen LiDA','Aspen LiDA',6,'Controls if the user can send notifications to Aspen LiDA users from their home library.'),(172,'Send Notifications to Home Location','Aspen LiDA','Aspen LiDA',6,'Controls if the user can send notifications to Aspen LiDA users from their home location.'),(173,'Send Notifications to Home Library Locations','Aspen LiDA','Aspen LiDA',6,'Controls if the user can send notifications to Aspen LiDA users for all locations that are part of their home library.'),(174,'Send Notifications to All Locations','Aspen LiDA','Aspen LiDA',6,'Controls if the user can send notifications to Aspen LiDA users from all locations.'),(175,'Administer Single Sign-on','Primary Configuration','',6,'Controls if the user can change single sign-on (SSO) settings.<em>This has potential security implications.</em>'),(176,'Administer InvoiceCloud','eCommerce','',10,'Controls if the user can change InvoiceCloud settings. <em>This has potential security and cost implications.</em>'),(177,'Administer Communico Settings','Events','Events',20,'Allows the user to administer integration with Communico for all libraries.'),(178,'Administer Certified Payments by Deluxe','eCommerce','',10,'Controls if the user can change Certified Payments by Deluxe settings. <em>This has potential security and cost implications.</em>'),(179,'Share Content with Community','Community Sharing','',10,'Controls if the user can share content with other members of the Aspen Discovery Community they are connected with.'),(180,'Import Content from Community','Community Sharing','',20,'Controls if the user can import content created by other members of the Aspen Discovery Community they are connected with.'),(181,'View Scheduled Updates','System Administration','',10,'Controls if the user can view scheduled updates for Aspen Discovery.'),(182,'Administer PayPal Payflow','eCommerce','',10,'Controls if the user can change PayPal Payflow settings. <em>This has potential security and cost implications.</em>'),(183,'Administer Selected Browse Category Groups','Local Enrichment','',15,'Allows the user to view and edit only the Browse Category Groups they are assigned to.'),(184,'Administer Square','eCommerce','',10,'Controls if the user can change Square settings. <em>This has potential security and cost implications.</em>'),(185,'View Location Collection Reports','Circulation Reports','',40,'Allows the user to view collection reports for their home location (CARL.X) only.'),(186,'View All Collection Reports','Circulation Reports','',50,'Allows the user to view collection reports for any location (CARL.X) only.'),(187,'Administer Twilio','System Administration','',34,'Controls if the user can change Twilio settings. <em>This has potential security and cost implications.</em>'),(188,'Administer Aspen LiDA Self-Check Settings','Aspen LiDA','Aspen LiDA',10,'Controls if the user can change Aspen LiDA Self-Check settings.'),(189,'Administer Events Facet Settings','Events','Events',20,'Allows the user to alter events facets for all libraries.'),(190,'Administer All Quick Polls','Web Builder','Web Builder',45,'Allows the user to administer polls for all libraries.'),(191,'Administer Library Quick Polls','Web Builder','Web Builder',46,'Allows the user to administer polls for their home library.'),(192,'Lock Administration Fields','System Administration','',25,'Allows the user to lock administration fields and change locked fields.'),(193,'Administer All Open Archives Facet Settings','Open Archives','Open Archives',0,'Allows the user to alter Open Archives facets for all libraries.'),(194,'Administer Library Open Archives Facet Settings','Open Archives','Open Archives',0,'Allows the user to alter Open Archives facets for their library.'),(195,'Administer All Website Facet Settings','Website Indexing','Web Indexer',0,'Allows the user to alter website facets for all libraries.'),(196,'Administer Library Website Facet Settings','Website Indexing','Web Indexer',0,'Allows the user to alter website facets for their library.'),(197,'View eCommerce Reports for Home Library','eCommerce','',6,'Allows the user to view eCommerce reports for their home library'),(198,'View Donations Reports for Home Library','eCommerce','',8,'Allows the user to view donations reports for their home library'),(199,'Run Optional Updates','System Administration','',22,'Allows the user to apply optional updates to their system.'),(200,'Administer Self Registration Forms','Cataloging & eContent','',20,'Allows the user to alter custom self registration forms for all libraries.'),(201,'Administer All Email Templates','Email','',10,'Allows the user to edit all email templates in the system.'),(202,'Administer Library Email Templates','Email','',20,'Allows the user to edit email templates for their library.'),(203,'Administer Palace Project','Cataloging & eContent','Palace Project',155,'Allows the user configure Palace Project integration for all libraries.'),(204,'Administer Stripe','eCommerce','',10,'Controls if the user can change Stripe settings. <em>This has potential security and cost implications.</em>'),(205,'Administer Assabet Settings','Events','Events',20,'Allows the user to administer integration with Assabet for all libraries.'),(206,'Administer User Agents','Primary Configuration','',55,'Allows the user to administer User Agents for Aspen Discovery.'),(207,'Administer NCR','eCommerce','',10,'Controls if the user can change NCR settings. <em>This has potential security and cost implications.</em>'),(208,'Administer All Grapes Pages','Web Builder','Web Builder',150,'Allows the user to define grapes pages for all libraries.'),(209,'Administer Library Grapes Pages','Web Builder','Web Builder',151,'Allows the user to define grapes pages for their home library.'),(210,'Administer SMTP','Primary Configuration','',30,'Controls if the user can change SMTP settings.'),(211,'Test Self Check','Circulation','',20,'Allows users to test checking titles out within Aspen Discovey.'),(212,'Administer All Format Sorting','Grouped Work Display','',40,'Allows users to change how formats are sorted within a grouped work for all libraries.'),(213,'Administer Library Format Sorting','Grouped Work Display','',50,'Allows users to change how formats are sorted within a grouped work for their library.'),(214,'Barcode Generators','Circulation Reports','',60,'Allows the user to run the Barcode Generators'),(215,'Administer SnapPay','eCommerce','',10,'Controls if the user can change SnapPay settings. <em>This has potential security and cost implications.</em>'),(216,'Place Holds For Materials Requests','Materials Requests','',25,'Allows users to place holds for users that have active Materials Requests once titles are added to the catalog.'),(217,'Administer Replacement Costs','Primary Configuration','',100,'Allows users to administer replacement costs for all libraries.'),(218,'Administer All Local ILL Forms','ILL Integration','',17,'Allows the user to define administer all Local ILL Forms.'),(219,'Administer Library Local ILL Forms','ILL Integration','',18,'Allows the user to define administer Local ILL Forms for their library.'),(220,'Administer Year in Review for All Libraries','Year in Review','',10,'Allows Year in Review functionality to be configured for all libraries.'),(221,'Administer Year in Review for Home Library','Year in Review','',20,'Allows Year in Review functionality to be configured for the user\'s home library.'),(222,'Administer LibKey Settings','Third Party Enrichment','',0,'Allows the user to administer the integration with LibKey'),(223,'Administer Sublocations for All Libraries','Primary Configuration - Location Sublocations','',10,'Allows Sublocation functionality to be configured for all libraries.'),(224,'Administer Sublocations for Home Library','Primary Configuration - Location Sublocations','',20,'Allows Sublocation functionality to be configured for the user\'s home library.'),(225,'Manage Local Administrators','System Administration','',12,'Allows an administrator to add, edit, and delete local administrators.'),(226,'Administer Field Sets','Events','Events',30,'Allows the user to administer field sets for native events.'),(227,'Administer Event Types','Events','Events',40,'Allows the user to administer native event types.'),(228,'Administer Events for All Locations','Events','Events',50,'Allows the user to administer native events for all locations.'),(229,'Administer Events for Home Library Locations','Events','Events',51,'Allows the user to administer native events for home library locations.'),(230,'Administer Events for Home Location','Events','Events',52,'Allows the user to administer native events for home location.'),(231,'View Private Events for All Locations','Events','Events',60,'Allows the user to view private native events for all locations.'),(232,'View Private Events for Home Library Locations','Events','Events',61,'Allows the user to view private native events for home library locations.'),(233,'View Private Events for Home Location','Events','Events',62,'Allows the user to view private native events for home location.'),(234,'View Event Reports for All Libraries','Events','Events',70,'Allows the user to view event reports for all libraries.'),(235,'View Event Reports for Home Library','Events','Events',71,'Allows the user to view event reports for their home library.'),(236,'Print Calendars with Header Images','Events','Events',80,'Allows the user to print calendars with header images.'),(237,'Administer Series','Local Enrichment','Series',12,'Allows an administrator to add and modify series.'),(238,'Administer All Custom Web Resource Pages','Web Builder','Web Builder',152,'Allows the user to define custom web resource pages for all libraries.'),(239,'Administer Library Custom Web Resource Pages','Web Builder','Web Builder',153,'Allows the user to define custom web resource pages for their home library.'),(241,'View Community Engagement Dashboard','Reporting','Community Engagement',190,'Allows the user to view the community engagement dashboard.'),(242,'Administer Community Engagement Module','Primary Configuration','Community Engagement',180,'Allows the user to create rewards, milestones and campaigns');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

DROP TABLE IF EXISTS person;
CREATE TABLE `person` (
  `personId` int(11) NOT NULL AUTO_INCREMENT,
  `firstName` varchar(100) DEFAULT NULL,
  `middleName` varchar(100) DEFAULT NULL,
  `lastName` varchar(100) DEFAULT NULL,
  `maidenName` varchar(100) DEFAULT NULL,
  `otherName` varchar(100) DEFAULT NULL,
  `nickName` varchar(100) DEFAULT NULL,
  `birthDate` date DEFAULT NULL,
  `deathDate` date DEFAULT NULL,
  `ageAtDeath` mediumtext DEFAULT NULL,
  `cemeteryName` varchar(255) DEFAULT NULL,
  `cemeteryLocation` varchar(255) DEFAULT NULL,
  `mortuaryName` varchar(255) DEFAULT NULL,
  `comments` longtext DEFAULT NULL,
  `picture` varchar(255) DEFAULT NULL,
  `ledgerVolume` varchar(20) DEFAULT '',
  `ledgerYear` varchar(20) DEFAULT '',
  `ledgerEntry` varchar(20) DEFAULT '',
  `sex` varchar(20) DEFAULT '',
  `race` varchar(20) DEFAULT '',
  `residence` varchar(255) DEFAULT '',
  `causeOfDeath` varchar(255) DEFAULT '',
  `cemeteryAvenue` varchar(255) DEFAULT '',
  `veteranOf` varchar(100) DEFAULT '',
  `addition` varchar(100) DEFAULT '',
  `block` varchar(100) DEFAULT '',
  `lot` varchar(50) DEFAULT '',
  `grave` int(11) DEFAULT NULL,
  `tombstoneInscription` mediumtext DEFAULT NULL,
  `addedBy` int(11) NOT NULL DEFAULT -1,
  `dateAdded` int(11) DEFAULT NULL,
  `modifiedBy` int(11) NOT NULL DEFAULT -1,
  `lastModified` int(11) DEFAULT NULL,
  `privateComments` mediumtext DEFAULT NULL,
  `importedFrom` varchar(50) DEFAULT NULL,
  `birthDateDay` int(11) DEFAULT NULL COMMENT 'The day of the month the person was born empty or null if not known',
  `birthDateMonth` int(11) DEFAULT NULL COMMENT 'The month the person was born, null or blank if not known',
  `birthDateYear` int(11) DEFAULT NULL COMMENT 'The year the person was born, null or blank if not known',
  `deathDateDay` int(11) DEFAULT NULL COMMENT 'The day of the month the person died empty or null if not known',
  `deathDateMonth` int(11) DEFAULT NULL COMMENT 'The month the person died, null or blank if not known',
  `deathDateYear` int(11) DEFAULT NULL COMMENT 'The year the person died, null or blank if not known',
  PRIMARY KEY (`personId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores information about a particular person for use in genealogy';
DROP TABLE IF EXISTS pin_reset_token;
CREATE TABLE `pin_reset_token` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `token` varchar(12) NOT NULL,
  `dateIssued` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS placard_dismissal;
CREATE TABLE `placard_dismissal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `placardId` int(11) DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userPlacard` (`userId`,`placardId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS placard_language;
CREATE TABLE `placard_language` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `placardId` int(11) DEFAULT NULL,
  `languageId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `placardLanguage` (`placardId`,`languageId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS placard_library;
CREATE TABLE `placard_library` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `placardId` int(11) DEFAULT NULL,
  `libraryId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `placardLibrary` (`placardId`,`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS placard_location;
CREATE TABLE `placard_location` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `placardId` int(11) DEFAULT NULL,
  `locationId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `placardLocation` (`placardId`,`locationId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS placard_trigger;
CREATE TABLE `placard_trigger` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `placardId` int(11) NOT NULL,
  `triggerWord` varchar(100) NOT NULL,
  `exactMatch` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `triggerWord` (`triggerWord`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS placards;
CREATE TABLE `placards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `body` mediumtext DEFAULT NULL,
  `css` mediumtext DEFAULT NULL,
  `image` varchar(100) DEFAULT NULL,
  `link` varchar(500) DEFAULT NULL,
  `dismissable` tinyint(1) DEFAULT NULL,
  `startDate` int(11) DEFAULT 0,
  `endDate` int(11) DEFAULT 0,
  `altText` varchar(500) DEFAULT NULL,
  `sourceType` varchar(30) DEFAULT NULL,
  `sourceId` varchar(30) DEFAULT NULL,
  `generatedFromSource` varchar(30) DEFAULT NULL,
  `isCustomized` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS processes_to_stop;
CREATE TABLE `processes_to_stop` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `processId` int(11) NOT NULL,
  `processName` varchar(255) NOT NULL,
  `stopAttempted` tinyint(4) DEFAULT 0,
  `stopResults` text DEFAULT NULL,
  `dateSet` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS propay_settings;
CREATE TABLE `propay_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `useTestSystem` tinyint(1) DEFAULT NULL,
  `authenticationToken` char(36) DEFAULT NULL,
  `billerAccountId` bigint(20) DEFAULT NULL,
  `merchantProfileId` bigint(20) DEFAULT NULL,
  `certStr` varchar(30) DEFAULT NULL,
  `accountNum` varchar(20) DEFAULT NULL,
  `termId` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ptype;
CREATE TABLE `ptype` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pType` varchar(50) NOT NULL,
  `maxHolds` int(11) NOT NULL DEFAULT 300,
  `assignedRoleId` int(11) DEFAULT -1,
  `restrictMasquerade` tinyint(1) DEFAULT 0,
  `isStaff` tinyint(1) DEFAULT 0,
  `description` varchar(100) DEFAULT '',
  `twoFactorAuthSettingId` int(11) DEFAULT -1,
  `vdxClientCategory` varchar(10) DEFAULT '',
  `accountLinkingSetting` tinyint(1) DEFAULT 0,
  `enableReadingHistory` tinyint(1) DEFAULT 1,
  `accountLinkRemoveSetting` tinyint(1) DEFAULT 1,
  `canSuggestMaterials` tinyint(1) DEFAULT 1,
  `canRenewOnline` tinyint(1) DEFAULT 1,
  `enableYearInReview` tinyint(4) DEFAULT 1,
  `accountProfileId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ptype_profile` (`pType`,`accountProfileId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ptype_restricted_locations;
CREATE TABLE `ptype_restricted_locations` (
  `locationId` int(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique id for the non holdable location',
  `millenniumCode` varchar(5) NOT NULL COMMENT 'The internal 5 letter code within Millennium',
  `holdingDisplay` varchar(30) NOT NULL COMMENT 'The text displayed in the holdings list within Millennium can use regular expression syntax to match multiple locations',
  `allowablePtypes` varchar(50) NOT NULL COMMENT 'A list of PTypes that are allowed to place holds on items with this location separated with pipes (|).',
  PRIMARY KEY (`locationId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS quipu_ecard_setting;
CREATE TABLE `quipu_ecard_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server` varchar(50) NOT NULL,
  `clientId` int(11) NOT NULL,
  `hasECard` tinyint(1) DEFAULT 1,
  `hasERenew` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS rbdigital_availability;
CREATE TABLE `rbdigital_availability` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rbdigitalId` varchar(25) NOT NULL,
  `isAvailable` tinyint(4) NOT NULL DEFAULT 1,
  `isOwned` tinyint(4) NOT NULL DEFAULT 1,
  `name` varchar(50) DEFAULT NULL,
  `rawChecksum` bigint(20) DEFAULT NULL,
  `rawResponse` longtext DEFAULT NULL,
  `lastChange` int(11) NOT NULL,
  `settingId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rbdigitalId` (`rbdigitalId`,`settingId`),
  KEY `lastChange` (`lastChange`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS rbdigital_magazine;
CREATE TABLE `rbdigital_magazine` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `magazineId` varchar(25) NOT NULL,
  `issueId` varchar(25) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `mediaType` varchar(50) DEFAULT NULL,
  `language` varchar(50) DEFAULT NULL,
  `rawChecksum` bigint(20) DEFAULT NULL,
  `rawResponse` longtext DEFAULT NULL,
  `dateFirstDetected` bigint(20) DEFAULT NULL,
  `lastChange` int(11) NOT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `magazineId` (`magazineId`,`issueId`),
  KEY `lastChange` (`lastChange`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS rbdigital_magazine_issue;
CREATE TABLE `rbdigital_magazine_issue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `magazineId` int(11) NOT NULL,
  `issueId` int(11) NOT NULL,
  `imageUrl` varchar(255) DEFAULT NULL,
  `publishedOn` varchar(10) DEFAULT NULL,
  `coverDate` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `magazineId` (`magazineId`,`issueId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS rbdigital_magazine_issue_availability;
CREATE TABLE `rbdigital_magazine_issue_availability` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `issueId` int(11) NOT NULL,
  `settingId` int(11) NOT NULL,
  `isAvailable` tinyint(1) DEFAULT NULL,
  `isOwned` tinyint(1) DEFAULT NULL,
  `stateId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `issueId` (`issueId`,`settingId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS rbdigital_magazine_usage;
CREATE TABLE `rbdigital_magazine_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `magazineId` int(11) DEFAULT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `timesCheckedOut` int(11) NOT NULL,
  `issueId` int(11) DEFAULT NULL,
  `instance` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`magazineId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS rbdigital_record_usage;
CREATE TABLE `rbdigital_record_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rbdigitalId` int(11) DEFAULT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `timesHeld` int(11) NOT NULL,
  `timesCheckedOut` int(11) NOT NULL,
  `instance` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`rbdigitalId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS rbdigital_title;
CREATE TABLE `rbdigital_title` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rbdigitalId` varchar(25) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `primaryAuthor` varchar(255) DEFAULT NULL,
  `mediaType` varchar(50) DEFAULT NULL,
  `isFiction` tinyint(4) NOT NULL DEFAULT 0,
  `audience` varchar(50) DEFAULT NULL,
  `language` varchar(50) DEFAULT NULL,
  `rawChecksum` bigint(20) NOT NULL,
  `rawResponse` longtext DEFAULT NULL,
  `lastChange` int(11) NOT NULL,
  `dateFirstDetected` bigint(20) DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rbdigitalId` (`rbdigitalId`),
  KEY `lastChange` (`lastChange`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS recaptcha_settings;
CREATE TABLE `recaptcha_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publicKey` varchar(50) NOT NULL,
  `privateKey` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS record_files;
CREATE TABLE `record_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) DEFAULT NULL,
  `identifier` varchar(50) DEFAULT NULL,
  `fileId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fileId` (`fileId`),
  KEY `type` (`type`,`identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS record_identifiers_to_reload;
CREATE TABLE `record_identifiers_to_reload` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL,
  `identifier` varchar(50) NOT NULL,
  `processed` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `type` (`type`,`identifier`),
  KEY `processed` (`processed`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS record_parents;
CREATE TABLE `record_parents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `childRecordId` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `parentRecordId` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `childTitle` varchar(750) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `childRecordId` (`childRecordId`,`parentRecordId`),
  KEY `parentRecordId` (`parentRecordId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS redwood_user_contribution;
CREATE TABLE `redwood_user_contribution` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `creator` varchar(255) DEFAULT NULL,
  `dateCreated` varchar(10) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `suggestedSubjects` longtext DEFAULT NULL,
  `howAcquired` varchar(255) DEFAULT NULL,
  `filePath` varchar(255) DEFAULT NULL,
  `status` enum('submitted','accepted','rejected') DEFAULT NULL,
  `license` enum('none','CC0','cc','public') DEFAULT NULL,
  `allowRemixing` tinyint(1) DEFAULT 0,
  `prohibitCommercialUse` tinyint(1) DEFAULT 0,
  `requireShareAlike` tinyint(1) DEFAULT 0,
  `dateContributed` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS reindex_log;
CREATE TABLE `reindex_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of reindex log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the reindex started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the reindex process ended',
  `notes` mediumtext DEFAULT NULL COMMENT 'Notes related to the overall process',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The last time the log was updated',
  `numWorksProcessed` int(11) NOT NULL DEFAULT 0,
  `numErrors` int(11) DEFAULT 0,
  `numInvalidRecords` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS replacement_costs;
CREATE TABLE `replacement_costs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `catalogFormat` varchar(255) NOT NULL,
  `replacementCost` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `catalogFormat` (`catalogFormat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
DROP TABLE IF EXISTS request_tracker_connection;
CREATE TABLE `request_tracker_connection` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `baseUrl` varchar(255) DEFAULT NULL,
  `activeTicketFeed` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `roleId` int(11) NOT NULL,
  `permissionId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roleId_2` (`roleId`,`permissionId`),
  KEY `roleId` (`roleId`)
) ENGINE=InnoDB AUTO_INCREMENT=1219 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (124,1,2),(123,1,3),(130,1,85),(1203,1,225),(1143,2,1),(1145,2,4),(1147,2,5),(1148,2,6),(1140,2,7),(1141,2,8),(1142,2,9),(1150,2,10),(1151,2,12),(1105,2,14),(1107,2,16),(1108,2,19),(1109,2,20),(1110,2,21),(1111,2,22),(1103,2,26),(1093,2,27),(1094,2,28),(1046,2,29),(1047,2,30),(1048,2,31),(1050,2,32),(1051,2,33),(1052,2,34),(1053,2,35),(1054,2,36),(1055,2,37),(1056,2,38),(1057,2,39),(1058,2,40),(1060,2,41),(1061,2,42),(1062,2,43),(1063,2,44),(1064,2,45),(1065,2,46),(1066,2,47),(1067,2,49),(1088,2,50),(1089,2,52),(1095,2,54),(1096,2,56),(1097,2,58),(1098,2,60),(1152,2,61),(1153,2,62),(1154,2,63),(1104,2,70),(1084,2,71),(1167,2,72),(1039,2,75),(1087,2,76),(1155,2,77),(1158,2,78),(1102,2,79),(1156,2,86),(1071,2,91),(1100,2,92),(1149,2,94),(1159,2,95),(1160,2,97),(1161,2,99),(1162,2,101),(1163,2,103),(1164,2,105),(1165,2,107),(1166,2,108),(1101,2,109),(1146,2,111),(1157,2,113),(1113,2,115),(1114,2,116),(1115,2,117),(1116,2,118),(1117,2,119),(1118,2,120),(1119,2,121),(1120,2,122),(1121,2,123),(1122,2,124),(1123,2,125),(1124,2,126),(1125,2,127),(1126,2,128),(1127,2,129),(1129,2,130),(1130,2,131),(1131,2,132),(1128,2,133),(1132,2,134),(1133,2,135),(1134,2,136),(1135,2,137),(1136,2,138),(1137,2,139),(1138,2,140),(1139,2,141),(1076,2,142),(1077,2,144),(1078,2,145),(1074,2,146),(1079,2,148),(1072,2,149),(1075,2,150),(1073,2,151),(1112,2,152),(1045,2,153),(1068,2,154),(1040,2,155),(1041,2,156),(1042,2,157),(1085,2,158),(1099,2,159),(1080,2,160),(1049,2,161),(1091,2,162),(1090,2,163),(1092,2,164),(1043,2,166),(1081,2,167),(1059,2,168),(1044,2,169),(1144,2,170),(1106,2,175),(1082,2,176),(1086,2,177),(1083,2,178),(1069,2,179),(1070,2,180),(1168,2,181),(1169,2,182),(1170,2,184),(1171,2,187),(1172,2,188),(1173,2,189),(1174,2,190),(1175,2,191),(1176,2,192),(1177,2,193),(1178,2,194),(1179,2,195),(1180,2,196),(1183,2,199),(1184,2,200),(1185,2,201),(1186,2,203),(1187,2,204),(1188,2,205),(1189,2,206),(1190,2,207),(1191,2,208),(1194,2,210),(1195,2,211),(1196,2,212),(1197,2,215),(1198,2,217),(1199,2,218),(1200,2,220),(1201,2,222),(1202,2,223),(1204,2,226),(1205,2,227),(1206,2,228),(1207,2,231),(1208,2,234),(1209,2,236),(1210,2,237),(1211,2,238),(1218,2,241),(1217,2,242),(5,3,76),(474,6,11),(475,6,13),(445,6,15),(446,6,17),(447,6,18),(448,6,22),(436,6,41),(437,6,51),(438,6,53),(439,6,55),(440,6,57),(441,6,59),(476,6,63),(435,6,75),(477,6,77),(478,6,78),(443,6,81),(444,6,82),(442,6,110),(449,6,116),(450,6,117),(451,6,118),(452,6,119),(453,6,120),(454,6,121),(455,6,122),(456,6,123),(457,6,124),(458,6,125),(459,6,126),(460,6,127),(461,6,128),(462,6,129),(464,6,130),(465,6,131),(466,6,132),(463,6,133),(467,6,135),(468,6,136),(469,6,137),(470,6,138),(471,6,139),(472,6,140),(473,6,141),(1181,6,197),(1182,6,198),(1,7,55),(2,7,57),(3,7,59),(4,7,63),(31,8,23),(30,8,24),(33,8,25),(153,9,87),(154,9,88),(155,9,89),(156,9,90),(504,10,15),(505,10,17),(506,10,22),(498,10,46),(499,10,55),(500,10,57),(516,10,63),(502,10,93),(503,10,110),(507,10,117),(508,10,122),(509,10,123),(511,10,132),(510,10,133),(512,10,136),(513,10,138),(514,10,139),(515,10,140),(501,10,147),(36,11,18),(39,11,22),(37,11,55),(38,11,57),(343,11,136),(344,11,139),(345,11,140),(35,13,77),(284,13,113),(119,31,27),(120,31,28),(117,32,8),(113,32,9),(108,32,29),(109,32,30),(107,32,31),(110,32,32),(111,32,33),(112,32,34),(106,32,35),(115,32,36),(116,32,37),(102,32,38),(104,32,39),(103,32,40),(105,32,62),(283,32,113),(131,36,80),(128,37,81),(129,38,83),(168,39,95),(169,39,97),(170,39,99),(171,39,101),(172,39,103),(173,39,105),(174,39,107),(175,39,108),(1192,39,208),(1212,39,238),(176,40,96),(177,40,98),(178,40,100),(179,40,102),(180,40,104),(181,40,106),(182,40,107),(1193,40,209),(1213,40,239);
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `roleId` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT 'The internal name of the role',
  `description` varchar(100) NOT NULL COMMENT 'A description of what the role allows',
  PRIMARY KEY (`roleId`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='A role identifying what the user can do.';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'userAdmin','Allows administration of users.'),(2,'opacAdmin','Allows full administration of Aspen settings for all libraries/locations.'),(3,'Genealogy Contributor','Allows Genealogy data to be entered by the user.'),(6,'Library Admin','Allows user to update library configuration for their library system only for their home location.'),(7,'Content Editor','Allows entering of editorial reviews and creation of widgets.'),(8,'Aspen Materials Requests','Allows user to manage material requests for a specific library.'),(9,'Location Reports','Allows the user to view reports for their location.'),(10,'Library Manager','Allows user to do basic configuration for their library.'),(11,'Location Manager','Allows user to do basic configuration for their location.'),(13,'List Publisher','Optionally only include lists from people with this role in search results.'),(31,'Translator','Allows the user to translate the system.'),(32,'Super Cataloger','Allows user to perform cataloging activities that require advanced knowledge.'),(33,'Cataloging','Allows user to perform basic cataloging activities.'),(36,'Masquerader','Allows the user to masquerade as any other user.'),(37,'Library Masquerader','Allows the user to masquerade as patrons of their home library only.'),(38,'Location Masquerader','Allows the user to masquerade as patrons of their home location only.'),(39,'Web Admin','Allows the user to administer web content for all libraries'),(40,'Library Web Admin','Allows the user to administer web content for their library');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

DROP TABLE IF EXISTS rosen_levelup_settings;
CREATE TABLE `rosen_levelup_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lu_api_host` varchar(50) NOT NULL,
  `lu_api_pw` varchar(50) NOT NULL,
  `lu_api_un` varchar(50) NOT NULL,
  `lu_district_name` varchar(50) NOT NULL,
  `lu_eligible_ptypes` varchar(50) NOT NULL,
  `lu_multi_district_name` varchar(50) NOT NULL,
  `lu_school_name` varchar(50) NOT NULL,
  `lu_ptypes_1` varchar(50) DEFAULT NULL,
  `lu_ptypes_2` varchar(50) DEFAULT NULL,
  `lu_ptypes_k` varchar(50) DEFAULT NULL,
  `lu_location_code_prefix` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS scope;
CREATE TABLE `scope` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `isLibraryScope` tinyint(1) DEFAULT NULL,
  `isLocationScope` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `name_2` (`name`,`isLibraryScope`,`isLocationScope`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS search;
CREATE TABLE `search` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `session_id` varchar(128) DEFAULT NULL,
  `created` date NOT NULL,
  `title` varchar(225) DEFAULT NULL,
  `saved` int(11) NOT NULL DEFAULT 0,
  `search_object` blob DEFAULT NULL,
  `searchSource` varchar(30) NOT NULL DEFAULT 'local',
  `searchUrl` varchar(2500) DEFAULT NULL,
  `hasNewResults` tinyint(4) DEFAULT 0,
  `lastUpdated` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS search_stats_new;
CREATE TABLE `search_stats_new` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The unique id of the search statistic',
  `phrase` varchar(500) NOT NULL COMMENT 'The phrase being searched for',
  `lastSearch` int(11) NOT NULL COMMENT 'The last time this search was done',
  `numSearches` int(11) NOT NULL COMMENT 'The number of times this search has been done.',
  PRIMARY KEY (`id`),
  KEY `numSearches` (`numSearches`),
  KEY `lastSearch` (`lastSearch`),
  FULLTEXT KEY `phrase_text` (`phrase`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Statistical information about searches for use in reporting ';
DROP TABLE IF EXISTS search_update_log;
CREATE TABLE `search_update_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `startTime` int(11) NOT NULL,
  `endTime` int(11) DEFAULT NULL,
  `lastUpdate` int(11) DEFAULT NULL,
  `numErrors` int(11) NOT NULL DEFAULT 0,
  `numSearches` int(11) NOT NULL DEFAULT 0,
  `numUpdated` int(11) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS self_reg_form_values;
CREATE TABLE `self_reg_form_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `selfRegistrationFormId` int(11) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT 0,
  `ilsName` varchar(50) NOT NULL,
  `displayName` varchar(50) NOT NULL,
  `fieldType` enum('text','date') NOT NULL DEFAULT 'text',
  `patronUpdate` enum('read_only','hidden','editable','editable_required') NOT NULL DEFAULT 'editable',
  `required` tinyint(4) NOT NULL DEFAULT 0,
  `note` varchar(255) DEFAULT NULL,
  `section` enum('librarySection','identitySection','mainAddressSection','contactInformationSection') NOT NULL DEFAULT 'identitySection',
  PRIMARY KEY (`id`),
  UNIQUE KEY `groupValue` (`selfRegistrationFormId`,`ilsName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
DROP TABLE IF EXISTS self_registration_form;
CREATE TABLE `self_registration_form` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `selfRegistrationBarcodePrefix` varchar(10) DEFAULT '',
  `selfRegBarcodeSuffixLength` int(11) DEFAULT 0,
  `noDuplicateCheck` tinyint(4) DEFAULT 0,
  `promptForParentInSelfReg` tinyint(1) NOT NULL DEFAULT 0,
  `promptForSMSNoticesInSelfReg` tinyint(1) NOT NULL DEFAULT 0,
  `cityStateField` tinyint(1) NOT NULL DEFAULT 0,
  `selfRegistrationUserProfile` varchar(20) DEFAULT 'SELFREG',
  `termsOfServiceSetting` int(11) NOT NULL DEFAULT -1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
DROP TABLE IF EXISTS self_registration_form_carlx;
CREATE TABLE `self_registration_form_carlx` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `selfRegEmailNotices` varchar(255) DEFAULT NULL,
  `selfRegDefaultBranch` varchar(255) DEFAULT NULL,
  `selfRegPatronExpirationDate` date DEFAULT NULL,
  `selfRegPatronStatusCode` varchar(255) DEFAULT NULL,
  `selfRegPatronType` varchar(255) DEFAULT NULL,
  `selfRegRegBranch` varchar(255) DEFAULT NULL,
  `selfRegRegisteredBy` varchar(255) DEFAULT NULL,
  `lastPatronBarcode` varchar(255) DEFAULT NULL,
  `barcodePrefix` varchar(255) DEFAULT NULL,
  `selfRegIDNumberLength` int(2) DEFAULT NULL,
  `termsOfServiceSetting` int(11) NOT NULL DEFAULT -1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS self_registration_form_sierra;
CREATE TABLE `self_registration_form_sierra` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `selfRegistrationTemplate` varchar(25) DEFAULT 'default',
  `selfRegEmailBarcode` tinyint(4) NOT NULL DEFAULT 0,
  `termsOfServiceSetting` int(11) NOT NULL DEFAULT -1,
  `selfRegPatronType` int(11) DEFAULT NULL,
  `selfRegNoticePref` char(1) DEFAULT '-',
  `selfRegTelephoneField` varchar(5) DEFAULT NULL,
  `selfRegExpirationDays` int(11) DEFAULT 30,
  `selfRegPcode1` varchar(25) DEFAULT NULL,
  `selfRegPcode2` varchar(25) DEFAULT NULL,
  `selfRegPcode3` int(11) DEFAULT NULL,
  `selfRegPcode4` int(11) DEFAULT NULL,
  `selfRegPatronMessage` varchar(35) DEFAULT NULL,
  `selfRegAgency` int(11) DEFAULT NULL,
  `selfRegBarcodePrefix` varchar(10) DEFAULT NULL,
  `selfRegBarcodeSuffixLength` int(11) DEFAULT 7,
  `selfRegGuardianField` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS self_registration_tos;
CREATE TABLE `self_registration_tos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(75) NOT NULL,
  `terms` mediumtext DEFAULT NULL,
  `redirect` mediumtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS sendgrid_settings;
CREATE TABLE `sendgrid_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fromAddress` varchar(255) DEFAULT NULL,
  `replyToAddress` varchar(255) DEFAULT NULL,
  `apiKey` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS series;
CREATE TABLE `series` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupedWorkSeriesTitle` varchar(500) DEFAULT NULL,
  `displayName` varchar(500) DEFAULT NULL,
  `author` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `audience` tinytext DEFAULT NULL,
  `cover` varchar(100) DEFAULT NULL,
  `isIndexed` tinyint(1) DEFAULT 1,
  `deleted` tinyint(1) DEFAULT 0,
  `dateUpdated` int(11) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
DROP TABLE IF EXISTS series_indexing_log;
CREATE TABLE `series_indexing_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `startTime` int(11) NOT NULL,
  `endTime` int(11) DEFAULT NULL,
  `lastUpdate` int(11) DEFAULT NULL,
  `notes` mediumtext DEFAULT NULL,
  `numSeries` int(11) DEFAULT 0,
  `numAdded` int(11) DEFAULT 0,
  `numDeleted` int(11) DEFAULT 0,
  `numUpdated` int(11) DEFAULT 0,
  `numSkipped` int(11) DEFAULT 0,
  `numErrors` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
DROP TABLE IF EXISTS series_indexing_settings;
CREATE TABLE `series_indexing_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `runFullUpdate` tinyint(1) DEFAULT 1,
  `lastUpdateOfChangedSeries` int(11) DEFAULT 0,
  `lastUpdateOfAllSeries` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
DROP TABLE IF EXISTS series_member;
CREATE TABLE `series_member` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seriesId` int(11) NOT NULL,
  `isPlaceholder` tinyint(1) DEFAULT 0,
  `groupedWorkPermanentId` char(40) DEFAULT NULL,
  `displayName` varchar(500) DEFAULT NULL,
  `author` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `cover` varchar(100) DEFAULT NULL,
  `volume` varchar(50) DEFAULT NULL,
  `pubDate` int(11) DEFAULT NULL,
  `weight` int(11) NOT NULL DEFAULT 0,
  `userAdded` tinyint(1) DEFAULT 0,
  `excluded` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
DROP TABLE IF EXISTS session;
CREATE TABLE `session` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(128) DEFAULT NULL,
  `data` longtext DEFAULT NULL,
  `last_used` int(11) NOT NULL DEFAULT 0,
  `created` datetime DEFAULT current_timestamp(),
  `remember_me` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Whether or not the session was started with remember me on.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_id` (`session_id`),
  KEY `last_used` (`last_used`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS shared_content;
CREATE TABLE `shared_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sharedFrom` varchar(50) NOT NULL,
  `sharedByUserName` varchar(256) NOT NULL,
  `shareDate` int(11) DEFAULT NULL,
  `approved` tinyint(1) DEFAULT 0,
  `approvalDate` int(11) DEFAULT NULL,
  `approvedBy` int(11) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `data` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS shared_session;
CREATE TABLE `shared_session` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sessionId` varchar(40) DEFAULT NULL,
  `userId` varchar(11) DEFAULT NULL,
  `createdOn` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
DROP TABLE IF EXISTS sideload_files;
CREATE TABLE `sideload_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sideLoadId` int(11) NOT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `lastChanged` int(11) DEFAULT 0,
  `deletedTime` int(11) DEFAULT 0,
  `lastIndexed` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sideloadFile` (`sideLoadId`,`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS sideload_log;
CREATE TABLE `sideload_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `notes` mediumtext DEFAULT NULL COMMENT 'Additional information about the run',
  `numSideLoadsUpdated` int(11) DEFAULT 0,
  `sideLoadsUpdated` longtext DEFAULT NULL,
  `numProducts` int(11) DEFAULT 0,
  `numErrors` int(11) DEFAULT 0,
  `numAdded` int(11) DEFAULT 0,
  `numDeleted` int(11) DEFAULT 0,
  `numUpdated` int(11) DEFAULT 0,
  `numSkipped` int(11) DEFAULT 0,
  `numInvalidRecords` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `startTime` (`startTime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS sideload_record_usage;
CREATE TABLE `sideload_record_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sideloadId` int(11) NOT NULL,
  `recordId` varchar(36) DEFAULT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `timesUsed` int(11) NOT NULL,
  `instance` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`sideloadId`,`recordId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS sideload_scopes;
CREATE TABLE `sideload_scopes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `sideLoadId` int(11) NOT NULL,
  `marcTagToMatch` varchar(100) DEFAULT NULL,
  `marcValueToMatch` varchar(100) DEFAULT NULL,
  `includeExcludeMatches` tinyint(4) DEFAULT 1,
  `urlToMatch` varchar(255) DEFAULT NULL,
  `urlReplacement` varchar(255) DEFAULT NULL,
  `includeAdult` tinyint(4) DEFAULT 1,
  `includeTeen` tinyint(4) DEFAULT 1,
  `includeKids` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS sideloads;
CREATE TABLE `sideloads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `marcPath` varchar(100) NOT NULL,
  `filenamesToInclude` varchar(250) DEFAULT '.*\\.ma?rc',
  `marcEncoding` enum('MARC8','UTF8','UNIMARC','ISO8859_1','BESTGUESS') NOT NULL DEFAULT 'MARC8',
  `indexingClass` varchar(50) NOT NULL DEFAULT 'SideLoadedEContentProcessor',
  `recordUrlComponent` varchar(25) NOT NULL DEFAULT 'DefineThis',
  `recordNumberTag` char(3) NOT NULL DEFAULT '001',
  `recordNumberSubfield` char(1) DEFAULT 'a',
  `recordNumberPrefix` varchar(10) NOT NULL DEFAULT '',
  `itemTag` char(3) NOT NULL DEFAULT '',
  `itemRecordNumber` char(1) DEFAULT NULL,
  `location` char(1) DEFAULT NULL,
  `locationsToSuppress` varchar(255) DEFAULT NULL,
  `itemUrl` char(1) DEFAULT NULL,
  `format` char(1) DEFAULT NULL,
  `formatSource` enum('bib','item','specified') NOT NULL DEFAULT 'bib',
  `specifiedFormat` varchar(50) DEFAULT NULL,
  `specifiedFormatCategory` varchar(50) DEFAULT NULL,
  `specifiedFormatBoost` int(11) DEFAULT NULL,
  `runFullUpdate` tinyint(1) DEFAULT 0,
  `lastUpdateOfChangedRecords` int(11) DEFAULT 0,
  `lastUpdateOfAllRecords` int(11) DEFAULT 0,
  `treatUnknownLanguageAs` varchar(50) DEFAULT 'English',
  `treatUndeterminedLanguageAs` varchar(50) DEFAULT 'English',
  `deletedRecordsIds` longtext DEFAULT NULL,
  `accessButtonLabel` varchar(50) DEFAULT 'Access Online',
  `showStatus` tinyint(1) DEFAULT 1,
  `includePersonalAndCorporateNamesInTopics` tinyint(1) NOT NULL DEFAULT 1,
  `convertFormatToEContent` tinyint(4) DEFAULT 1,
  `useLinkTextForButtonLabel` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS sierra_api_export_log;
CREATE TABLE `sierra_api_export_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `notes` mediumtext DEFAULT NULL COMMENT 'Additional information about the run',
  `numRecordsToProcess` int(11) DEFAULT NULL,
  `numRecordsProcessed` int(11) DEFAULT NULL,
  `numErrors` int(11) DEFAULT NULL,
  `numRemainingRecords` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS sierra_export_field_mapping;
CREATE TABLE `sierra_export_field_mapping` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of field mapping',
  `indexingProfileId` int(11) NOT NULL COMMENT 'The indexing profile this field mapping is associated with',
  `fixedFieldDestinationField` char(3) NOT NULL COMMENT 'The field to place fixed field data into',
  `bcode3DestinationSubfield` char(1) DEFAULT NULL COMMENT 'The subfield to place bcode3 into',
  `callNumberExportFieldTag` char(1) DEFAULT NULL,
  `callNumberPrestampExportSubfield` char(1) DEFAULT NULL,
  `callNumberExportSubfield` char(1) DEFAULT NULL,
  `callNumberCutterExportSubfield` char(1) DEFAULT NULL,
  `callNumberPoststampExportSubfield` char(5) DEFAULT NULL,
  `volumeExportFieldTag` char(1) DEFAULT NULL,
  `eContentExportFieldTag` char(1) DEFAULT NULL,
  `materialTypeSubfield` char(1) DEFAULT NULL,
  `bibLevelLocationsSubfield` char(1) DEFAULT NULL,
  `itemPublicNoteExportSubfield` varchar(1) DEFAULT '',
  `callNumberPrestamp2ExportSubfield` char(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS slow_ajax_request;
CREATE TABLE `slow_ajax_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `module` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `method` varchar(75) NOT NULL,
  `timesSlow` int(11) DEFAULT 0,
  `timesFast` int(11) DEFAULT NULL,
  `timesAcceptable` int(11) DEFAULT NULL,
  `timesSlower` int(11) DEFAULT NULL,
  `timesVerySlow` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `year` (`year`,`month`,`module`,`action`,`method`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS slow_page;
CREATE TABLE `slow_page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `module` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `timesSlow` int(11) DEFAULT 0,
  `timesFast` int(11) DEFAULT NULL,
  `timesAcceptable` int(11) DEFAULT NULL,
  `timesSlower` int(11) DEFAULT NULL,
  `timesVerySlow` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `year` (`year`,`month`,`module`,`action`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS smtp_settings;
CREATE TABLE `smtp_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `host` varchar(80) NOT NULL DEFAULT 'localhost',
  `port` int(11) NOT NULL DEFAULT 25,
  `ssl_mode` enum('disabled','ssl','tls') NOT NULL,
  `from_address` varchar(80) DEFAULT NULL,
  `from_name` varchar(80) DEFAULT NULL,
  `user_name` varchar(80) DEFAULT NULL,
  `password` varchar(80) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS snappay_settings;
CREATE TABLE `snappay_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `sandboxMode` tinyint(4) NOT NULL DEFAULT 0,
  `accountId` bigint(10) NOT NULL,
  `merchantId` varchar(20) NOT NULL,
  `apiAuthenticationCode` varchar(255) NOT NULL,
  `emailNotifications` tinyint(1) DEFAULT 0,
  `emailNotificationsAddresses` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS springshare_libcal_events;
CREATE TABLE `springshare_libcal_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `settingsId` int(11) NOT NULL,
  `externalId` varchar(36) NOT NULL,
  `title` varchar(255) NOT NULL,
  `rawChecksum` bigint(20) DEFAULT NULL,
  `rawResponse` mediumtext DEFAULT NULL,
  `deleted` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settingsId` (`settingsId`,`externalId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS springshare_libcal_settings;
CREATE TABLE `springshare_libcal_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `baseUrl` varchar(255) NOT NULL,
  `calId` varchar(50) DEFAULT '',
  `clientId` smallint(6) NOT NULL,
  `clientSecret` varchar(36) NOT NULL,
  `eventsInLists` tinyint(1) DEFAULT 1,
  `bypassAspenEventPages` tinyint(1) DEFAULT 0,
  `registrationModalBody` mediumtext DEFAULT NULL,
  `numberOfDaysToIndex` int(11) DEFAULT 365,
  `registrationModalBodyApp` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS square_settings;
CREATE TABLE `square_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `sandboxMode` tinyint(1) DEFAULT 0,
  `applicationId` varchar(80) NOT NULL,
  `accessToken` varchar(80) NOT NULL,
  `locationId` varchar(80) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
DROP TABLE IF EXISTS sso_mapping;
CREATE TABLE `sso_mapping` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aspenField` varchar(75) NOT NULL,
  `responseField` varchar(255) NOT NULL,
  `ssoSettingId` tinyint(4) DEFAULT -1,
  `fallbackValue` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mapping` (`aspenField`,`ssoSettingId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS sso_setting;
CREATE TABLE `sso_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `service` varchar(75) NOT NULL,
  `clientId` varchar(255) DEFAULT NULL,
  `clientSecret` varchar(255) DEFAULT NULL,
  `oAuthGateway` varchar(75) DEFAULT NULL,
  `ssoName` varchar(255) DEFAULT NULL,
  `ssoXmlUrl` varchar(255) DEFAULT NULL,
  `ssoUniqueAttribute` varchar(255) DEFAULT NULL,
  `ssoMetadataFilename` varchar(255) DEFAULT NULL,
  `ssoIdAttr` varchar(255) DEFAULT NULL,
  `ssoUsernameAttr` varchar(255) DEFAULT NULL,
  `ssoFirstnameAttr` varchar(255) DEFAULT NULL,
  `ssoLastnameAttr` varchar(255) DEFAULT NULL,
  `ssoEmailAttr` varchar(255) DEFAULT NULL,
  `ssoDisplayNameAttr` varchar(255) DEFAULT NULL,
  `ssoPhoneAttr` varchar(255) DEFAULT NULL,
  `ssoPatronTypeAttr` varchar(255) DEFAULT NULL,
  `ssoPatronTypeFallback` varchar(255) DEFAULT NULL,
  `ssoAddressAttr` varchar(255) DEFAULT NULL,
  `ssoCityAttr` varchar(255) DEFAULT NULL,
  `ssoLibraryIdAttr` varchar(255) DEFAULT NULL,
  `ssoLibraryIdFallback` varchar(255) DEFAULT NULL,
  `ssoCategoryIdAttr` varchar(255) DEFAULT NULL,
  `ssoCategoryIdFallback` varchar(255) DEFAULT NULL,
  `loginOptions` tinyint(1) DEFAULT 0,
  `loginHelpText` varchar(255) DEFAULT NULL,
  `oAuthButtonBackgroundColor` char(7) DEFAULT '#232323',
  `oAuthButtonTextColor` char(7) DEFAULT '#ffffff',
  `oAuthGatewayLabel` varchar(75) DEFAULT NULL,
  `oAuthAuthorizeUrl` varchar(255) DEFAULT NULL,
  `oAuthAccessTokenUrl` varchar(255) DEFAULT NULL,
  `oAuthResourceOwnerUrl` varchar(255) DEFAULT NULL,
  `oAuthGatewayIcon` varchar(255) DEFAULT NULL,
  `oAuthScope` varchar(255) DEFAULT NULL,
  `ssoEntityId` varchar(255) DEFAULT NULL,
  `oAuthLogoutUrl` varchar(255) DEFAULT NULL,
  `oAuthGrantType` tinyint(1) DEFAULT 0,
  `oAuthPrivateKeys` varchar(255) DEFAULT NULL,
  `samlMetadataOption` varchar(30) DEFAULT NULL,
  `samlBtnIcon` varchar(255) DEFAULT NULL,
  `samlBtnBgColor` char(7) DEFAULT '#de1f0b',
  `samlBtnTextColor` char(7) DEFAULT '#ffffff',
  `samlStaffPTypeAttr` varchar(255) DEFAULT NULL,
  `samlStaffPTypeAttrValue` varchar(255) DEFAULT NULL,
  `samlStaffPType` varchar(30) DEFAULT NULL,
  `oAuthStaffPTypeAttr` varchar(255) DEFAULT NULL,
  `oAuthStaffPTypeAttrValue` varchar(255) DEFAULT NULL,
  `oAuthStaffPType` varchar(30) DEFAULT NULL,
  `staffOnly` tinyint(1) DEFAULT 0,
  `bypassAspenLogin` tinyint(1) DEFAULT 0,
  `ssoUseGivenUserId` tinyint(1) DEFAULT 1,
  `ssoUseGivenUsername` tinyint(1) DEFAULT 1,
  `ssoUsernameFormat` tinyint(1) DEFAULT 0,
  `ssoSPLogoutUrl` varchar(255) DEFAULT NULL,
  `ldapHosts` varchar(500) DEFAULT NULL,
  `ldapUsername` varchar(75) DEFAULT NULL,
  `ldapPassword` varchar(75) DEFAULT NULL,
  `ldapBaseDN` varchar(500) DEFAULT NULL,
  `ldapIdAttr` varchar(75) DEFAULT NULL,
  `ldapOrgUnit` varchar(225) DEFAULT NULL,
  `ldapLabel` varchar(75) DEFAULT NULL,
  `ssoAuthOnly` tinyint(1) DEFAULT 0,
  `ssoILSUniqueAttribute` varchar(255) DEFAULT NULL,
  `bypassAspenPatronLogin` tinyint(1) DEFAULT 0,
  `samlStudentPTypeAttr` varchar(255) DEFAULT NULL,
  `samlStudentPTypeAttrValue` varchar(255) DEFAULT NULL,
  `samlStudentPType` varchar(30) DEFAULT NULL,
  `forceReAuth` tinyint(1) DEFAULT 0,
  `restrictByIP` tinyint(1) DEFAULT 0,
  `updateAccount` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS staff_members;
CREATE TABLE `staff_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(13) DEFAULT NULL,
  `libraryId` int(11) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS status_map_values;
CREATE TABLE `status_map_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `indexingProfileId` int(11) NOT NULL,
  `value` varchar(50) NOT NULL,
  `status` varchar(50) NOT NULL,
  `groupedStatus` varchar(50) NOT NULL,
  `suppress` tinyint(1) DEFAULT 0,
  `inLibraryUseOnly` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `indexingProfileId` (`indexingProfileId`,`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS stripe_settings;
CREATE TABLE `stripe_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `stripePublicKey` varchar(500) NOT NULL,
  `stripeSecretKey` varchar(500) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
DROP TABLE IF EXISTS sublocation;
CREATE TABLE `sublocation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `weight` int(11) DEFAULT 0,
  `ilsId` varchar(50) DEFAULT NULL,
  `locationId` int(11) NOT NULL,
  `isValidHoldPickupAreaILS` tinyint(1) DEFAULT 0,
  `isValidHoldPickupAreaAspen` tinyint(1) DEFAULT 0,
  `isValidEventLocation` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
DROP TABLE IF EXISTS sublocation_ptype;
CREATE TABLE `sublocation_ptype` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sublocationId` int(11) DEFAULT NULL,
  `patronTypeId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `uniqueness` (`sublocationId`,`patronTypeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
DROP TABLE IF EXISTS summon_settings;
CREATE TABLE `summon_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `summonBaseApi` varchar(50) DEFAULT '',
  `summonApiId` varchar(50) DEFAULT '',
  `summonApiPassword` varchar(256) DEFAULT NULL,
  `filterOutBooksAndEbooks` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS summon_usage;
CREATE TABLE `summon_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `summonId` varchar(100) DEFAULT NULL,
  `month` int(2) NOT NULL,
  `year` int(4) NOT NULL,
  `timesViewedInSearch` int(11) NOT NULL,
  `timesUsed` int(11) NOT NULL,
  `instance` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`summonId`,`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS syndetics_data;
CREATE TABLE `syndetics_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupedRecordPermanentId` char(40) NOT NULL,
  `lastDescriptionUpdate` int(11) DEFAULT 0,
  `primaryIsbn` varchar(13) DEFAULT NULL,
  `primaryUpc` varchar(25) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `tableOfContents` longtext DEFAULT NULL,
  `excerpt` longtext DEFAULT NULL,
  `lastTableOfContentsUpdate` int(11) DEFAULT 0,
  `lastExcerptUpdate` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `groupedRecordPermanentId` (`groupedRecordPermanentId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS syndetics_settings;
CREATE TABLE `syndetics_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `syndeticsKey` varchar(50) NOT NULL,
  `hasSummary` tinyint(1) DEFAULT 1,
  `hasAvSummary` tinyint(1) DEFAULT 0,
  `hasAvProfile` tinyint(1) DEFAULT 0,
  `hasToc` tinyint(1) DEFAULT 1,
  `hasExcerpt` tinyint(1) DEFAULT 1,
  `hasVideoClip` tinyint(1) DEFAULT 0,
  `hasFictionProfile` tinyint(1) DEFAULT 0,
  `hasAuthorNotes` tinyint(1) DEFAULT 0,
  `syndeticsUnbound` tinyint(1) DEFAULT 0,
  `unboundAccountNumber` int(11) DEFAULT NULL,
  `name` tinytext DEFAULT 'default',
  `unboundInstanceNumber` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`) USING HASH
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS system_message_dismissal;
CREATE TABLE `system_message_dismissal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `systemMessageId` int(11) DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userPlacard` (`userId`,`systemMessageId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS system_message_library;
CREATE TABLE `system_message_library` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `systemMessageId` int(11) DEFAULT NULL,
  `libraryId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `systemMessageLibrary` (`systemMessageId`,`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS system_message_location;
CREATE TABLE `system_message_location` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `systemMessageId` int(11) DEFAULT NULL,
  `locationId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `systemMessageLocation` (`systemMessageId`,`locationId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS system_messages;
CREATE TABLE `system_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `message` mediumtext DEFAULT NULL,
  `css` mediumtext DEFAULT NULL,
  `dismissable` tinyint(1) DEFAULT 0,
  `showOn` int(11) DEFAULT 0,
  `startDate` int(11) DEFAULT 0,
  `endDate` int(11) DEFAULT 0,
  `messageStyle` varchar(10) DEFAULT '',
  `appMessage` varchar(280) DEFAULT NULL,
  `pushToApp` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS system_variables;
CREATE TABLE `system_variables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `errorEmail` varchar(128) DEFAULT NULL,
  `ticketEmail` varchar(128) DEFAULT NULL,
  `searchErrorEmail` varchar(128) DEFAULT NULL,
  `loadCoversFrom020z` tinyint(1) DEFAULT 0,
  `runNightlyFullIndex` tinyint(1) DEFAULT 0,
  `currencyCode` char(3) DEFAULT 'USD',
  `allowableHtmlTags` varchar(512) DEFAULT 'p|div|span|a|b|em|strong|i|ul|ol|li|br|h1|h2|h3|h4|h5|h6',
  `allowHtmlInMarkdownFields` tinyint(1) DEFAULT 1,
  `useHtmlEditorRatherThanMarkdown` tinyint(1) DEFAULT 1,
  `storeRecordDetailsInSolr` tinyint(1) DEFAULT 0,
  `storeRecordDetailsInDatabase` tinyint(1) DEFAULT 1,
  `greenhouseUrl` varchar(128) DEFAULT NULL,
  `libraryToUseForPayments` tinyint(1) DEFAULT 0,
  `solrConnectTimeout` int(11) DEFAULT 2,
  `solrQueryTimeout` int(11) DEFAULT 10,
  `catalogStatus` tinyint(1) DEFAULT 0,
  `offlineMessage` text DEFAULT NULL,
  `indexVersion` int(11) DEFAULT 2,
  `searchVersion` int(11) DEFAULT 1,
  `regroupAllRecordsDuringNightlyIndex` tinyint(4) DEFAULT 0,
  `processEmptyGroupedWorks` tinyint(4) DEFAULT 1,
  `appScheme` varchar(72) DEFAULT 'aspen-lida',
  `googleBucket` varchar(128) DEFAULT NULL,
  `trackIpAddresses` tinyint(1) DEFAULT 0,
  `communityContentUrl` varchar(128) DEFAULT '',
  `supportingCompany` varchar(72) DEFAULT 'ByWater Solutions',
  `monitorAntivirus` tinyint(1) DEFAULT 1,
  `enableBrandedApp` tinyint(1) DEFAULT 0,
  `enableNovelistSeriesIntegration` tinyint(4) DEFAULT 1,
  `enableGrapesEditor` tinyint(1) DEFAULT 0,
  `deletionCommitInterval` int(11) DEFAULT 1000,
  `waitAfterDeleteCommit` tinyint(4) DEFAULT 0,
  `disableIpSpammyControl` tinyint(1) DEFAULT 0,
  `enableAspenEvents` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS text_block_translation;
CREATE TABLE `text_block_translation` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `objectType` varchar(50) NOT NULL,
  `objectId` int(11) NOT NULL,
  `languageId` int(11) NOT NULL,
  `translation` mediumtext DEFAULT NULL,
  `fieldName` tinytext DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `objectType` (`objectType`,`objectId`,`fieldName`,`languageId`) USING HASH
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS themes;
CREATE TABLE `themes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `themeName` varchar(100) NOT NULL,
  `extendsTheme` varchar(100) DEFAULT NULL,
  `logoName` varchar(100) DEFAULT '',
  `headerBackgroundColor` char(7) DEFAULT '#f1f1f1',
  `headerBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `headerForegroundColor` char(7) DEFAULT '#8b8b8b',
  `headerForegroundColorDefault` tinyint(1) DEFAULT 1,
  `generatedCss` longtext DEFAULT NULL,
  `headerBottomBorderWidth` varchar(6) DEFAULT NULL,
  `favicon` varchar(100) DEFAULT '',
  `pageBackgroundColor` char(7) DEFAULT '#ffffff',
  `pageBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `primaryBackgroundColor` char(7) DEFAULT '#147ce2',
  `primaryBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `primaryForegroundColor` char(7) DEFAULT '#ffffff',
  `primaryForegroundColorDefault` tinyint(1) DEFAULT 1,
  `bodyBackgroundColor` char(7) DEFAULT '#ffffff',
  `bodyBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `bodyTextColor` char(7) DEFAULT '#6B6B6B',
  `bodyTextColorDefault` tinyint(1) DEFAULT 1,
  `secondaryBackgroundColor` char(7) DEFAULT '#de9d03',
  `secondaryBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `secondaryForegroundColor` char(7) DEFAULT '#ffffff',
  `secondaryForegroundColorDefault` tinyint(1) DEFAULT 1,
  `tertiaryBackgroundColor` char(7) DEFAULT '#de1f0b',
  `tertiaryBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `tertiaryForegroundColor` char(7) DEFAULT '#ffffff',
  `tertiaryForegroundColorDefault` tinyint(1) DEFAULT 1,
  `headingFont` varchar(191) DEFAULT NULL,
  `headingFontDefault` tinyint(1) DEFAULT 1,
  `bodyFont` varchar(191) DEFAULT NULL,
  `bodyFontDefault` tinyint(1) DEFAULT 1,
  `additionalCss` mediumtext DEFAULT NULL,
  `additionalCssType` tinyint(1) DEFAULT 0,
  `buttonRadius` varchar(6) DEFAULT NULL,
  `smallButtonRadius` varchar(6) DEFAULT NULL,
  `browseCategoryPanelColor` char(7) DEFAULT '#ffffff',
  `browseCategoryPanelColorDefault` tinyint(1) DEFAULT 1,
  `selectedBrowseCategoryBackgroundColor` char(7) DEFAULT '#0087AB',
  `selectedBrowseCategoryBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `selectedBrowseCategoryForegroundColor` char(7) DEFAULT '#ffffff',
  `selectedBrowseCategoryForegroundColorDefault` tinyint(1) DEFAULT 1,
  `selectedBrowseCategoryBorderColor` char(7) DEFAULT '#0087AB',
  `selectedBrowseCategoryBorderColorDefault` tinyint(1) DEFAULT 1,
  `deselectedBrowseCategoryBackgroundColor` char(7) DEFAULT '#ffffff',
  `deselectedBrowseCategoryBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `deselectedBrowseCategoryForegroundColor` char(7) DEFAULT '#6B6B6B',
  `deselectedBrowseCategoryForegroundColorDefault` tinyint(1) DEFAULT 1,
  `deselectedBrowseCategoryBorderColor` char(7) DEFAULT '#6B6B6B',
  `deselectedBrowseCategoryBorderColorDefault` tinyint(1) DEFAULT 1,
  `menubarHighlightBackgroundColor` char(7) DEFAULT '#f1f1f1',
  `menubarHighlightBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `menubarHighlightForegroundColor` char(7) DEFAULT '#265a87',
  `menubarHighlightForegroundColorDefault` tinyint(1) DEFAULT 1,
  `customHeadingFont` varchar(100) DEFAULT NULL,
  `customBodyFont` varchar(100) DEFAULT NULL,
  `capitalizeBrowseCategories` tinyint(1) DEFAULT -1,
  `defaultButtonBackgroundColor` char(7) DEFAULT '#ffffff',
  `defaultButtonBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `defaultButtonForegroundColor` char(7) DEFAULT '#333333',
  `defaultButtonForegroundColorDefault` tinyint(1) DEFAULT 1,
  `defaultButtonBorderColor` char(7) DEFAULT '#cccccc',
  `defaultButtonBorderColorDefault` tinyint(1) DEFAULT 1,
  `defaultButtonHoverBackgroundColor` char(7) DEFAULT '#ebebeb',
  `defaultButtonHoverBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `defaultButtonHoverForegroundColor` char(7) DEFAULT '#333333',
  `defaultButtonHoverForegroundColorDefault` tinyint(1) DEFAULT 1,
  `defaultButtonHoverBorderColor` char(7) DEFAULT '#adadad',
  `defaultButtonHoverBorderColorDefault` tinyint(1) DEFAULT 1,
  `primaryButtonBackgroundColor` char(7) DEFAULT '#428bca',
  `primaryButtonBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `primaryButtonForegroundColor` char(7) DEFAULT '#ffffff',
  `primaryButtonForegroundColorDefault` tinyint(1) DEFAULT 1,
  `primaryButtonBorderColor` char(7) DEFAULT '#357ebd',
  `primaryButtonBorderColorDefault` tinyint(1) DEFAULT 1,
  `primaryButtonHoverBackgroundColor` char(7) DEFAULT '#3276b1',
  `primaryButtonHoverBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `primaryButtonHoverForegroundColor` char(7) DEFAULT '#ffffff',
  `primaryButtonHoverForegroundColorDefault` tinyint(1) DEFAULT 1,
  `primaryButtonHoverBorderColor` char(7) DEFAULT '#285e8e',
  `primaryButtonHoverBorderColorDefault` tinyint(1) DEFAULT 1,
  `actionButtonBackgroundColor` char(7) DEFAULT '#428bca',
  `actionButtonBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `actionButtonForegroundColor` char(7) DEFAULT '#ffffff',
  `actionButtonForegroundColorDefault` tinyint(1) DEFAULT 1,
  `actionButtonBorderColor` char(7) DEFAULT '#357ebd',
  `actionButtonBorderColorDefault` tinyint(1) DEFAULT 1,
  `actionButtonHoverBackgroundColor` char(7) DEFAULT '#3276b1',
  `actionButtonHoverBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `actionButtonHoverForegroundColor` char(7) DEFAULT '#ffffff',
  `actionButtonHoverForegroundColorDefault` tinyint(1) DEFAULT 1,
  `actionButtonHoverBorderColor` char(7) DEFAULT '#285e8e',
  `actionButtonHoverBorderColorDefault` tinyint(1) DEFAULT 1,
  `infoButtonBackgroundColor` char(7) DEFAULT '#5bc0de',
  `infoButtonBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `infoButtonForegroundColor` char(7) DEFAULT '#ffffff',
  `infoButtonForegroundColorDefault` tinyint(1) DEFAULT 1,
  `infoButtonBorderColor` char(7) DEFAULT '#46b8da',
  `infoButtonBorderColorDefault` tinyint(1) DEFAULT 1,
  `infoButtonHoverBackgroundColor` char(7) DEFAULT '#39b3d7',
  `infoButtonHoverBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `infoButtonHoverForegroundColor` char(7) DEFAULT '#ffffff',
  `infoButtonHoverForegroundColorDefault` tinyint(1) DEFAULT 1,
  `infoButtonHoverBorderColor` char(7) DEFAULT '#269abc',
  `infoButtonHoverBorderColorDefault` tinyint(1) DEFAULT 1,
  `warningButtonBackgroundColor` char(7) DEFAULT '#f0ad4e',
  `warningButtonBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `warningButtonForegroundColor` char(7) DEFAULT '#ffffff',
  `warningButtonForegroundColorDefault` tinyint(1) DEFAULT 1,
  `warningButtonBorderColor` char(7) DEFAULT '#eea236',
  `warningButtonBorderColorDefault` tinyint(1) DEFAULT 1,
  `warningButtonHoverBackgroundColor` char(7) DEFAULT '#ed9c28',
  `warningButtonHoverBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `warningButtonHoverForegroundColor` char(7) DEFAULT '#ffffff',
  `warningButtonHoverForegroundColorDefault` tinyint(1) DEFAULT 1,
  `warningButtonHoverBorderColor` char(7) DEFAULT '#d58512',
  `warningButtonHoverBorderColorDefault` tinyint(1) DEFAULT 1,
  `dangerButtonBackgroundColor` char(7) DEFAULT '#d9534f',
  `dangerButtonBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `dangerButtonForegroundColor` char(7) DEFAULT '#ffffff',
  `dangerButtonForegroundColorDefault` tinyint(1) DEFAULT 1,
  `dangerButtonBorderColor` char(7) DEFAULT '#d43f3a',
  `dangerButtonBorderColorDefault` tinyint(1) DEFAULT 1,
  `dangerButtonHoverBackgroundColor` char(7) DEFAULT '#d2322d',
  `dangerButtonHoverBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `dangerButtonHoverForegroundColor` char(7) DEFAULT '#ffffff',
  `dangerButtonHoverForegroundColorDefault` tinyint(1) DEFAULT 1,
  `dangerButtonHoverBorderColor` char(7) DEFAULT '#ac2925',
  `dangerButtonHoverBorderColorDefault` tinyint(1) DEFAULT 1,
  `footerBackgroundColor` char(7) DEFAULT '#ffffff',
  `footerBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `footerForegroundColor` char(7) DEFAULT '#6b6b6b',
  `footerForegroundColorDefault` tinyint(1) DEFAULT 1,
  `footerLogo` varchar(100) DEFAULT NULL,
  `footerLogoLink` varchar(255) DEFAULT NULL,
  `closedPanelBackgroundColor` char(7) DEFAULT '#e7e7e7',
  `closedPanelBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `closedPanelForegroundColor` char(7) DEFAULT '#333333',
  `closedPanelForegroundColorDefault` tinyint(1) DEFAULT 1,
  `openPanelBackgroundColor` char(7) DEFAULT '#4DACDE',
  `openPanelBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `openPanelForegroundColor` char(7) DEFAULT '#ffffff',
  `openPanelForegroundColorDefault` tinyint(1) DEFAULT 1,
  `editionsButtonBackgroundColor` char(7) DEFAULT '#f8f9fa',
  `editionsButtonBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `editionsButtonForegroundColor` char(7) DEFAULT '#212529',
  `editionsButtonForegroundColorDefault` tinyint(1) DEFAULT 1,
  `editionsButtonBorderColor` char(7) DEFAULT '#999999',
  `editionsButtonBorderColorDefault` tinyint(1) DEFAULT 1,
  `editionsButtonHoverBackgroundColor` char(7) DEFAULT '#e2e6ea',
  `editionsButtonHoverBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `editionsButtonHoverForegroundColor` char(7) DEFAULT '#212529',
  `editionsButtonHoverForegroundColorDefault` tinyint(1) DEFAULT 1,
  `editionsButtonHoverBorderColor` char(7) DEFAULT '#dae0e5',
  `editionsButtonHoverBorderColorDefault` tinyint(1) DEFAULT 1,
  `linkColor` char(7) DEFAULT '#3174AF',
  `linkColorDefault` tinyint(1) DEFAULT 1,
  `badgeBackgroundColor` char(7) DEFAULT '#666666',
  `badgeBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `badgeForegroundColor` char(7) DEFAULT '#ffffff',
  `badgeForegroundColorDefault` tinyint(1) DEFAULT 1,
  `badgeBorderRadius` varchar(6) DEFAULT NULL,
  `toolsButtonBackgroundColor` char(7) DEFAULT '#4F4F4F',
  `toolsButtonBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `toolsButtonForegroundColor` char(7) DEFAULT '#ffffff',
  `toolsButtonForegroundColorDefault` tinyint(1) DEFAULT 1,
  `toolsButtonBorderColor` char(7) DEFAULT '#636363',
  `toolsButtonBorderColorDefault` tinyint(1) DEFAULT 1,
  `toolsButtonHoverBackgroundColor` char(7) DEFAULT '#636363',
  `toolsButtonHoverBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `toolsButtonHoverForegroundColor` char(7) DEFAULT '#ffffff',
  `toolsButtonHoverForegroundColorDefault` tinyint(1) DEFAULT 1,
  `toolsButtonHoverBorderColor` char(7) DEFAULT '#636363',
  `toolsButtonHoverBorderColorDefault` tinyint(1) DEFAULT 1,
  `panelBodyBackgroundColor` char(7) DEFAULT '#ffffff',
  `panelBodyBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `panelBodyForegroundColor` char(7) DEFAULT '#404040',
  `panelBodyForegroundColorDefault` tinyint(1) DEFAULT 1,
  `linkHoverColor` char(7) DEFAULT '#265a87',
  `linkHoverColorDefault` tinyint(1) DEFAULT 1,
  `resultLabelColor` char(7) DEFAULT '#44484a',
  `resultLabelColorDefault` tinyint(1) DEFAULT 1,
  `resultValueColor` char(7) DEFAULT '#6B6B6B',
  `resultValueColorDefault` tinyint(1) DEFAULT 1,
  `breadcrumbsBackgroundColor` char(7) DEFAULT '#f5f5f5',
  `breadcrumbsBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `breadcrumbsForegroundColor` char(7) DEFAULT '#6B6B6B',
  `breadcrumbsForegroundColorDefault` tinyint(1) DEFAULT 1,
  `searchToolsBackgroundColor` char(7) DEFAULT '#f5f5f5',
  `searchToolsBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `searchToolsBorderColor` char(7) DEFAULT '#e3e3e3',
  `searchToolsBorderColorDefault` tinyint(1) DEFAULT 1,
  `searchToolsForegroundColor` char(7) DEFAULT '#6B6B6B',
  `searchToolsForegroundColorDefault` tinyint(1) DEFAULT 1,
  `menubarBackgroundColor` char(7) DEFAULT '#f1f1f1',
  `menubarBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `menubarForegroundColor` char(7) DEFAULT '#303030',
  `menubarForegroundColorDefault` tinyint(1) DEFAULT 1,
  `menuDropdownBackgroundColor` char(7) DEFAULT '#ededed',
  `menuDropdownBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `menuDropdownForegroundColor` char(7) DEFAULT '#404040',
  `menuDropdownForegroundColorDefault` tinyint(1) DEFAULT 1,
  `modalDialogBackgroundColor` char(7) DEFAULT '#ffffff',
  `modalDialogBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `modalDialogForegroundColor` char(7) DEFAULT '#333333',
  `modalDialogForegroundColorDefault` tinyint(1) DEFAULT 1,
  `modalDialogHeaderFooterBackgroundColor` char(7) DEFAULT '#ffffff',
  `modalDialogHeaderFooterBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `modalDialogHeaderFooterForegroundColor` char(7) DEFAULT '#333333',
  `modalDialogHeaderFooterForegroundColorDefault` tinyint(1) DEFAULT 1,
  `modalDialogHeaderFooterBorderColor` char(7) DEFAULT '#e5e5e5',
  `modalDialogHeaderFooterBorderColorDefault` tinyint(1) DEFAULT 1,
  `footerLogoAlt` varchar(255) DEFAULT NULL,
  `logoApp` varchar(100) DEFAULT NULL,
  `browseCategoryImageSize` tinyint(1) DEFAULT -1,
  `browseImageLayout` tinyint(1) DEFAULT -1,
  `fullWidth` tinyint(1) DEFAULT 0,
  `coverStyle` varchar(50) NOT NULL DEFAULT 'border',
  `headerBackgroundImage` varchar(255) DEFAULT NULL,
  `headerBackgroundImageSize` varchar(75) DEFAULT NULL,
  `headerBackgroundImageRepeat` varchar(75) DEFAULT NULL,
  `displayName` varchar(60) NOT NULL,
  `isHighContrast` tinyint(1) DEFAULT 0,
  `inactiveTabBackgroundColor` char(7) DEFAULT '#ffffff',
  `inactiveTabBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `inactiveTabForegroundColor` char(7) DEFAULT '#6B6B6B',
  `inactiveTabForegroundColorDefault` tinyint(1) DEFAULT 1,
  `activeTabBackgroundColor` char(7) DEFAULT '#e7e7e7',
  `activeTabBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `activeTabForegroundColor` char(7) DEFAULT '#333333',
  `activeTabForegroundColorDefault` tinyint(1) DEFAULT 1,
  `defaultCover` varchar(100) DEFAULT '',
  `booksImage` varchar(100) DEFAULT '',
  `eBooksImage` varchar(100) DEFAULT '',
  `audioBooksImage` varchar(100) DEFAULT '',
  `musicImage` varchar(100) DEFAULT '',
  `moviesImage` varchar(100) DEFAULT '',
  `booksImageSelected` varchar(100) DEFAULT '',
  `eBooksImageSelected` varchar(100) DEFAULT '',
  `audioBooksImageSelected` varchar(100) DEFAULT '',
  `musicImageSelected` varchar(100) DEFAULT '',
  `moviesImageSelected` varchar(100) DEFAULT '',
  `cookieConsentBackgroundColor` char(7) DEFAULT '#1D7FF0',
  `cookieConsentBackgroundColorDefault` tinyint(1) DEFAULT 1,
  `cookieConsentButtonColor` char(7) DEFAULT '#1D7FF0',
  `cookieConsentButtonColorDefault` tinyint(1) DEFAULT 1,
  `cookieConsentButtonHoverColor` char(7) DEFAULT '#FF0000',
  `cookieConsentButtonHoverColorDefault` tinyint(1) DEFAULT 1,
  `cookieConsentTextColor` char(7) DEFAULT '#FFFFFF',
  `cookieConsentTextColorDefault` tinyint(1) DEFAULT 1,
  `cookieConsentButtonTextColor` char(7) DEFAULT '#FFFFFF',
  `cookieConsentButtonTextColorDefault` tinyint(1) DEFAULT 1,
  `cookieConsentButtonHoverTextColor` char(7) DEFAULT '#FFFFFF',
  `cookieConsentButtonHoverTextColorDefault` tinyint(1) DEFAULT 1,
  `cookieConsentButtonBorderColor` char(7) DEFAULT '#FFFFFF',
  `cookieConsentButtonBorderColorDefault` tinyint(1) DEFAULT 1,
  `catalogImage` varchar(100) DEFAULT '',
  `genealogyImage` varchar(100) DEFAULT '',
  `articlesDBImage` varchar(100) DEFAULT '',
  `eventsImage` varchar(100) DEFAULT '',
  `listsImage` varchar(100) DEFAULT '',
  `libraryWebsiteImage` varchar(100) DEFAULT '',
  `historyArchivesImage` varchar(100) DEFAULT '',
  `accessibleBrowseCategories` tinyint(4) NOT NULL DEFAULT 0,
  `headerLogoApp` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `themeName` (`themeName`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ticket;
CREATE TABLE `ticket` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticketId` varchar(20) NOT NULL,
  `displayUrl` varchar(500) DEFAULT NULL,
  `title` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `dateCreated` int(11) NOT NULL,
  `requestingPartner` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `queue` varchar(50) DEFAULT NULL,
  `severity` varchar(50) DEFAULT NULL,
  `partnerPriority` int(11) DEFAULT 0,
  `partnerPriorityChangeDate` int(11) DEFAULT NULL,
  `dateClosed` int(11) DEFAULT NULL,
  `developmentTaskId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticketId` (`ticketId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ticket_component_feed;
CREATE TABLE `ticket_component_feed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `rssFeed` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ticket_queue_feed;
CREATE TABLE `ticket_queue_feed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `rssFeed` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ticket_severity_feed;
CREATE TABLE `ticket_severity_feed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `rssFeed` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ticket_status_feed;
CREATE TABLE `ticket_status_feed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `rssFeed` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS ticket_trend_bugs_by_severity;
CREATE TABLE `ticket_trend_bugs_by_severity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `day` int(11) NOT NULL,
  `severity` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `count` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniqueness` (`year`,`month`,`day`,`severity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
DROP TABLE IF EXISTS ticket_trend_by_component;
CREATE TABLE `ticket_trend_by_component` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `day` int(11) NOT NULL,
  `component` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `queue` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `count` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniqueness` (`year`,`month`,`day`,`component`,`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
DROP TABLE IF EXISTS ticket_trend_by_partner;
CREATE TABLE `ticket_trend_by_partner` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `day` int(11) NOT NULL,
  `requestingPartner` int(11) DEFAULT NULL,
  `queue` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `count` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniqueness` (`year`,`month`,`day`,`requestingPartner`,`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
DROP TABLE IF EXISTS ticket_trend_by_queue;
CREATE TABLE `ticket_trend_by_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `day` int(11) NOT NULL,
  `queue` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `count` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniqueness` (`year`,`month`,`day`,`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
DROP TABLE IF EXISTS time_to_reshelve;
CREATE TABLE `time_to_reshelve` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `indexingProfileId` int(11) NOT NULL,
  `locations` varchar(100) NOT NULL,
  `numHoursToOverride` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `groupedStatus` varchar(50) NOT NULL,
  `weight` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `indexingProfileId` (`indexingProfileId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS title_authorities;
CREATE TABLE `title_authorities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `originalName` varchar(255) NOT NULL,
  `authoritativeName` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `originalName` (`originalName`),
  KEY `authoritativeName` (`authoritativeName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS translation_map_values;
CREATE TABLE `translation_map_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `translationMapId` int(11) NOT NULL,
  `value` varchar(255) NOT NULL,
  `translation` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `translationMapId` (`translationMapId`,`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS translation_maps;
CREATE TABLE `translation_maps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `indexingProfileId` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `usesRegularExpressions` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `profileName` (`indexingProfileId`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS translation_terms;
CREATE TABLE `translation_terms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `term` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `parameterNotes` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `samplePageUrl` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `defaultText` text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `isPublicFacing` tinyint(1) DEFAULT 0,
  `isAdminFacing` tinyint(1) DEFAULT 0,
  `isMetadata` tinyint(1) DEFAULT 0,
  `isAdminEnteredData` tinyint(1) DEFAULT 0,
  `lastUpdate` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `url` (`samplePageUrl`),
  KEY `term` (`term`(500))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS translations;
CREATE TABLE `translations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `termId` int(11) NOT NULL,
  `languageId` int(11) NOT NULL,
  `translation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `translated` tinyint(4) NOT NULL DEFAULT 0,
  `needsReview` tinyint(1) DEFAULT 0,
  `lastCheckInCommunity` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `term_language` (`termId`,`languageId`),
  KEY `translation_status` (`languageId`,`translated`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS twilio_settings;
CREATE TABLE `twilio_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `accountSid` varchar(50) DEFAULT NULL,
  `authToken` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
DROP TABLE IF EXISTS two_factor_auth_codes;
CREATE TABLE `two_factor_auth_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) DEFAULT NULL,
  `sessionId` varchar(128) DEFAULT NULL,
  `code` varchar(7) DEFAULT NULL,
  `dateSent` int(11) DEFAULT NULL,
  `status` varchar(75) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS two_factor_auth_settings;
CREATE TABLE `two_factor_auth_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `isEnabled` varchar(25) DEFAULT NULL,
  `deniedMessage` longtext DEFAULT NULL,
  `accountProfileId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS usage_by_ip_address;
CREATE TABLE `usage_by_ip_address` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance` varchar(100) DEFAULT NULL,
  `ipAddress` varchar(25) DEFAULT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `numRequests` int(11) DEFAULT 0,
  `numBlockedRequests` int(11) DEFAULT 0,
  `numBlockedApiRequests` int(11) DEFAULT 0,
  `lastRequest` int(11) DEFAULT 0,
  `numLoginAttempts` int(11) DEFAULT 0,
  `numFailedLoginAttempts` int(11) DEFAULT 0,
  `numSpammyRequests` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip` (`year`,`month`,`instance`,`ipAddress`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS usage_by_user_agent;
CREATE TABLE `usage_by_user_agent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userAgentId` int(11) NOT NULL,
  `instance` varchar(255) DEFAULT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `numRequests` int(11) NOT NULL DEFAULT 0,
  `numBlockedRequests` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userAgentId` (`userAgentId`,`year`,`month`,`instance`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS usage_tracking;
CREATE TABLE `usage_tracking` (
  `usageId` int(11) NOT NULL AUTO_INCREMENT,
  `ipId` int(11) NOT NULL,
  `locationId` int(11) NOT NULL,
  `numPageViews` int(11) NOT NULL DEFAULT 0,
  `numHolds` int(11) NOT NULL DEFAULT 0,
  `numRenewals` int(11) NOT NULL DEFAULT 0,
  `trackingDate` bigint(20) NOT NULL,
  PRIMARY KEY (`usageId`),
  KEY `usageId` (`usageId`),
  KEY `IP_DATE` (`ipId`,`trackingDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user;
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(36) NOT NULL,
  `password` varchar(256) DEFAULT NULL,
  `firstname` varchar(256) NOT NULL,
  `lastname` varchar(256) NOT NULL,
  `email` varchar(256) NOT NULL DEFAULT '',
  `cat_username` varchar(256) DEFAULT NULL,
  `cat_password` varchar(256) DEFAULT '',
  `created` datetime NOT NULL,
  `homeLocationId` int(11) NOT NULL COMMENT 'A link to the locations table for the users home location (branch) defined in millennium',
  `myLocation1Id` int(11) NOT NULL COMMENT 'A link to the locations table representing an alternate branch the users frequents or that is close by',
  `myLocation2Id` int(11) NOT NULL COMMENT 'A link to the locations table representing an alternate branch the users frequents or that is close by',
  `trackReadingHistory` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Whether or not Reading History should be tracked.',
  `bypassAutoLogout` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Whether or not the user wants to bypass the automatic logout code on public workstations.',
  `displayName` varchar(256) NOT NULL,
  `disableCoverArt` tinyint(4) NOT NULL DEFAULT 0,
  `disableRecommendations` tinyint(4) NOT NULL DEFAULT 0,
  `phone` varchar(256) NOT NULL DEFAULT '',
  `patronType` varchar(50) NOT NULL DEFAULT '',
  `overdriveEmail` varchar(256) NOT NULL DEFAULT '',
  `promptForOverdriveEmail` tinyint(4) DEFAULT 1,
  `preferredLibraryInterface` int(11) DEFAULT NULL,
  `initialReadingHistoryLoaded` tinyint(4) DEFAULT 0,
  `noPromptForUserReviews` tinyint(1) DEFAULT 0,
  `source` varchar(50) DEFAULT 'ils',
  `interfaceLanguage` varchar(3) DEFAULT 'en',
  `searchPreferenceLanguage` tinyint(1) DEFAULT -1,
  `rememberHoldPickupLocation` tinyint(1) DEFAULT 0,
  `lockedFacets` mediumtext DEFAULT NULL,
  `lastListUsed` int(11) DEFAULT -1,
  `lastLoginValidation` int(11) DEFAULT -1,
  `alternateLibraryCard` varchar(50) DEFAULT '',
  `alternateLibraryCardPassword` varchar(256) NOT NULL DEFAULT '',
  `hideResearchStarters` tinyint(1) DEFAULT 0,
  `updateMessage` mediumtext DEFAULT NULL,
  `updateMessageIsError` tinyint(4) DEFAULT NULL,
  `pickupLocationId` int(11) DEFAULT 0,
  `lastReadingHistoryUpdate` int(11) DEFAULT 0,
  `holdInfoLastLoaded` int(11) DEFAULT 0,
  `checkoutInfoLastLoaded` int(11) DEFAULT 0,
  `proPayPayerAccountId` bigint(20) DEFAULT NULL,
  `twoFactorStatus` int(11) DEFAULT 0,
  `hooplaCheckOutConfirmation` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `axis360Email` varchar(250) NOT NULL DEFAULT '',
  `promptForAxis360Email` tinyint(4) DEFAULT 1,
  `oAuthAccessToken` varchar(255) DEFAULT NULL,
  `oAuthRefreshToken` varchar(255) DEFAULT NULL,
  `disableAccountLinking` tinyint(1) DEFAULT 0,
  `browseAddToHome` tinyint(1) DEFAULT 1,
  `isLoggedInViaSSO` tinyint(1) DEFAULT 0,
  `materialsRequestSendEmailOnAssign` tinyint(1) DEFAULT 0,
  `materialsRequestReplyToAddress` varchar(70) DEFAULT NULL,
  `materialsRequestEmailSignature` text DEFAULT NULL,
  `preferredTheme` int(11) DEFAULT -1,
  `unique_ils_id` varchar(36) NOT NULL,
  `ils_barcode` varchar(256) DEFAULT NULL,
  `ils_username` varchar(256) DEFAULT NULL,
  `ils_password` varchar(256) DEFAULT NULL,
  `displayListAuthor` tinyint(1) DEFAULT 1,
  `userCookiePreferenceEssential` int(11) DEFAULT 0,
  `userCookiePreferenceAnalytics` int(11) DEFAULT 0,
  `disableCirculationActions` tinyint(1) DEFAULT 0,
  `onboardAppNotifications` tinyint(1) NOT NULL DEFAULT 1,
  `shouldAskBrightness` tinyint(1) NOT NULL DEFAULT 1,
  `enableCostSavings` tinyint(4) DEFAULT 0,
  `totalCostSavings` decimal(10,2) DEFAULT 0.00,
  `currentCostSavings` decimal(10,2) DEFAULT 0.00,
  `userCookiePreferenceLocalAnalytics` tinyint(1) DEFAULT 0,
  `analyticsDataCleared` tinyint(4) DEFAULT 0,
  `isLocalTestUser` tinyint(1) DEFAULT 0,
  `pickupSublocationId` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`source`,`username`),
  KEY `user_barcode` (`source`,`ils_barcode`),
  KEY `ils_barcode` (`ils_barcode`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_account_summary;
CREATE TABLE `user_account_summary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source` varchar(50) NOT NULL,
  `userId` int(11) NOT NULL,
  `numCheckedOut` int(11) DEFAULT 0,
  `numOverdue` int(11) DEFAULT 0,
  `numAvailableHolds` int(11) DEFAULT 0,
  `numUnavailableHolds` int(11) DEFAULT 0,
  `totalFines` float DEFAULT 0,
  `expirationDate` bigint(20) DEFAULT 0,
  `lastLoaded` int(11) DEFAULT NULL,
  `numCheckoutsRemaining` int(11) NOT NULL DEFAULT 0,
  `hasUpdatedSavedSearches` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `source` (`source`,`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_administration_locations;
CREATE TABLE `user_administration_locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `locationId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userId` (`userId`,`locationId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
DROP TABLE IF EXISTS user_agent;
CREATE TABLE `user_agent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userAgent` text DEFAULT NULL,
  `isBot` tinyint(4) NOT NULL DEFAULT 0,
  `blockAccess` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userAgent` (`userAgent`(512))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_axis360_usage;
CREATE TABLE `user_axis360_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance` varchar(100) DEFAULT NULL,
  `userId` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `usageCount` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `instance` (`instance`,`userId`,`year`,`month`),
  KEY `instance_2` (`instance`,`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_checkout;
CREATE TABLE `user_checkout` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(20) NOT NULL,
  `source` varchar(50) NOT NULL,
  `userId` int(11) NOT NULL,
  `sourceId` varchar(50) NOT NULL,
  `recordId` varchar(50) NOT NULL,
  `shortId` varchar(50) DEFAULT NULL,
  `itemId` varchar(50) DEFAULT NULL,
  `itemIndex` varchar(50) DEFAULT NULL,
  `renewalId` varchar(50) DEFAULT NULL,
  `barcode` varchar(50) DEFAULT NULL,
  `title` varchar(500) DEFAULT NULL,
  `title2` varchar(500) DEFAULT NULL,
  `author` varchar(500) DEFAULT NULL,
  `callNumber` varchar(100) DEFAULT NULL,
  `volume` varchar(255) DEFAULT NULL,
  `checkoutDate` int(11) DEFAULT NULL,
  `dueDate` bigint(20) DEFAULT NULL,
  `renewCount` int(11) DEFAULT NULL,
  `canRenew` tinyint(1) DEFAULT NULL,
  `autoRenew` tinyint(1) DEFAULT NULL,
  `autoRenewError` varchar(500) DEFAULT NULL,
  `maxRenewals` int(11) DEFAULT NULL,
  `fine` float DEFAULT NULL,
  `returnClaim` varchar(500) DEFAULT NULL,
  `holdQueueLength` int(11) DEFAULT NULL,
  `renewalDate` bigint(20) DEFAULT NULL,
  `allowDownload` tinyint(1) DEFAULT NULL,
  `overdriveRead` tinyint(1) DEFAULT NULL,
  `overdriveReadUrl` varchar(255) DEFAULT NULL,
  `overdriveListen` tinyint(1) DEFAULT NULL,
  `overdriveListenUrl` varchar(255) DEFAULT NULL,
  `overdriveVideo` tinyint(1) DEFAULT NULL,
  `overdriveVideoUrl` varchar(255) DEFAULT NULL,
  `formatSelected` tinyint(1) DEFAULT NULL,
  `selectedFormatName` varchar(50) DEFAULT NULL,
  `selectedFormatValue` varchar(25) DEFAULT NULL,
  `canReturnEarly` tinyint(1) DEFAULT NULL,
  `supplementalMaterials` mediumtext DEFAULT NULL,
  `formats` mediumtext DEFAULT NULL,
  `downloadUrl` varchar(255) DEFAULT NULL,
  `accessOnlineUrl` varchar(255) DEFAULT NULL,
  `transactionId` varchar(40) DEFAULT NULL,
  `coverUrl` varchar(255) DEFAULT NULL,
  `format` varchar(255) DEFAULT NULL,
  `renewIndicator` varchar(20) DEFAULT NULL,
  `groupedWorkId` char(40) DEFAULT NULL,
  `overdriveMagazine` tinyint(1) DEFAULT NULL,
  `isSupplemental` tinyint(1) DEFAULT 0,
  `linkUrl` varchar(255) DEFAULT NULL,
  `renewError` varchar(500) DEFAULT NULL,
  `isIll` tinyint(1) DEFAULT 0,
  `earlyReturnUrl` varchar(255) DEFAULT NULL,
  `collectionName` varchar(128) DEFAULT NULL,
  `outOfHoldGroupMessage` tinytext DEFAULT NULL,
  `ilsStatus` varchar(50) DEFAULT NULL,
  `showFineButton` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`,`source`,`recordId`),
  KEY `userId_2` (`userId`,`groupedWorkId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_cloud_library_usage;
CREATE TABLE `user_cloud_library_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `usageCount` int(11) DEFAULT NULL,
  `instance` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`userId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_ebsco_eds_usage;
CREATE TABLE `user_ebsco_eds_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `usageCount` int(11) DEFAULT NULL,
  `instance` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`userId`,`year`,`month`),
  UNIQUE KEY `instance_2` (`instance`,`userId`,`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_ebscohost_usage;
CREATE TABLE `user_ebscohost_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `instance` varchar(100) DEFAULT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `usageCount` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `year` (`year`,`month`,`instance`,`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_events_entry;
CREATE TABLE `user_events_entry` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `sourceId` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `eventDate` int(11) DEFAULT NULL,
  `regRequired` tinyint(4) DEFAULT 0,
  `location` varchar(255) NOT NULL,
  `dateAdded` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userId` (`userId`,`sourceId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_events_registrations;
CREATE TABLE `user_events_registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `userBarcode` varchar(256) NOT NULL,
  `sourceId` varchar(50) NOT NULL,
  `waitlist` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userId` (`userId`,`sourceId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_events_usage;
CREATE TABLE `user_events_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `type` varchar(25) NOT NULL,
  `source` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `usageCount` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`,`source`,`year`,`month`,`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_hold;
CREATE TABLE `user_hold` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(20) NOT NULL,
  `source` varchar(50) NOT NULL,
  `userId` int(11) NOT NULL,
  `sourceId` varchar(50) NOT NULL,
  `recordId` varchar(50) NOT NULL,
  `shortId` varchar(50) DEFAULT NULL,
  `itemId` varchar(50) DEFAULT NULL,
  `title` varchar(500) DEFAULT NULL,
  `title2` varchar(500) DEFAULT NULL,
  `author` varchar(500) DEFAULT NULL,
  `volume` varchar(255) DEFAULT NULL,
  `callNumber` varchar(100) DEFAULT NULL,
  `available` tinyint(1) DEFAULT NULL,
  `cancelable` tinyint(1) DEFAULT NULL,
  `cancelId` varchar(50) DEFAULT NULL,
  `locationUpdateable` tinyint(1) DEFAULT NULL,
  `pickupLocationId` varchar(50) DEFAULT NULL,
  `pickupLocationName` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `position` int(11) DEFAULT NULL,
  `holdQueueLength` int(11) DEFAULT NULL,
  `createDate` int(11) DEFAULT NULL,
  `availableDate` int(11) DEFAULT NULL,
  `expirationDate` int(11) DEFAULT NULL,
  `automaticCancellationDate` int(11) DEFAULT NULL,
  `frozen` tinyint(1) DEFAULT NULL,
  `canFreeze` tinyint(1) DEFAULT NULL,
  `reactivateDate` int(11) DEFAULT NULL,
  `groupedWorkId` char(40) DEFAULT NULL,
  `format` varchar(150) DEFAULT NULL,
  `coverUrl` varchar(255) DEFAULT NULL,
  `linkUrl` varchar(255) DEFAULT NULL,
  `isIll` tinyint(1) DEFAULT 0,
  `pendingCancellation` tinyint(1) DEFAULT 0,
  `cancellationUrl` varchar(255) DEFAULT NULL,
  `collectionName` varchar(128) DEFAULT NULL,
  `outOfHoldGroupMessage` tinytext DEFAULT NULL,
  `pickupSublocationId` varchar(50) DEFAULT NULL,
  `pickupSublocationName` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`,`source`,`recordId`),
  KEY `userId_2` (`userId`,`groupedWorkId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_hoopla_usage;
CREATE TABLE `user_hoopla_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `usageCount` int(11) DEFAULT NULL,
  `instance` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`userId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_ils_messages;
CREATE TABLE `user_ils_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `messageId` varchar(100) NOT NULL,
  `userId` int(11) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `title` varchar(200) DEFAULT NULL,
  `content` mediumtext DEFAULT NULL,
  `error` varchar(255) DEFAULT NULL,
  `dateQueued` int(11) DEFAULT NULL,
  `dateSent` int(11) DEFAULT NULL,
  `isRead` tinyint(1) DEFAULT 0,
  `defaultContent` mediumtext DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_ils_usage;
CREATE TABLE `user_ils_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `indexingProfileId` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `usageCount` int(11) DEFAULT 0,
  `selfRegistrationCount` int(11) DEFAULT 0,
  `pdfDownloadCount` int(11) DEFAULT 0,
  `supplementalFileDownloadCount` int(11) DEFAULT 0,
  `pdfViewCount` int(11) DEFAULT 0,
  `instance` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`userId`,`indexingProfileId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_link;
CREATE TABLE `user_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `primaryAccountId` int(11) NOT NULL,
  `linkedAccountId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_link` (`primaryAccountId`,`linkedAccountId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_link_blocks;
CREATE TABLE `user_link_blocks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `primaryAccountId` int(10) unsigned NOT NULL,
  `blockedLinkAccountId` int(10) unsigned DEFAULT NULL COMMENT 'A specific account primaryAccountId will not be linked to.',
  `blockLinking` tinyint(3) unsigned DEFAULT NULL COMMENT 'Indicates primaryAccountId will not be linked to any other accounts.',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_list;
CREATE TABLE `user_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` longtext DEFAULT NULL,
  `public` int(11) NOT NULL DEFAULT 0,
  `dateUpdated` int(11) DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0,
  `created` int(11) DEFAULT NULL,
  `defaultSort` varchar(20) DEFAULT NULL,
  `searchable` tinyint(1) DEFAULT 0,
  `importedFrom` varchar(20) DEFAULT NULL,
  `nytListModified` varchar(20) DEFAULT NULL,
  `displayListAuthor` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `dateUpdated` (`dateUpdated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_list_entry;
CREATE TABLE `user_list_entry` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sourceId` varchar(255) DEFAULT NULL,
  `listId` int(11) DEFAULT NULL,
  `notes` longtext DEFAULT NULL,
  `dateAdded` int(11) DEFAULT NULL,
  `weight` int(11) DEFAULT NULL,
  `source` varchar(20) NOT NULL DEFAULT 'grouped_work',
  `importedFrom` varchar(20) DEFAULT NULL,
  `title` varchar(50) DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `groupedWorkPermanentId` (`sourceId`),
  KEY `listId` (`listId`),
  KEY `source` (`source`,`sourceId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_messages;
CREATE TABLE `user_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) DEFAULT NULL,
  `messageType` varchar(50) DEFAULT NULL,
  `messageLevel` enum('success','info','warning','danger') DEFAULT 'info',
  `message` longtext DEFAULT NULL,
  `isDismissed` tinyint(1) DEFAULT 0,
  `action1` varchar(255) DEFAULT NULL,
  `action1Title` varchar(50) DEFAULT NULL,
  `action2` varchar(255) DEFAULT NULL,
  `action2Title` varchar(50) DEFAULT NULL,
  `addendum` varchar(255) DEFAULT NULL,
  `relatedObjectId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`,`isDismissed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_not_interested;
CREATE TABLE `user_not_interested` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `dateMarked` int(11) DEFAULT NULL,
  `groupedRecordPermanentId` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`),
  KEY `groupedRecordPermanentId` (`groupedRecordPermanentId`,`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_notification_tokens;
CREATE TABLE `user_notification_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) DEFAULT NULL,
  `pushToken` varchar(500) DEFAULT NULL,
  `deviceModel` varchar(75) DEFAULT NULL,
  `notifySavedSearch` tinyint(1) DEFAULT 0,
  `notifyCustom` tinyint(1) DEFAULT 0,
  `notifyAccount` tinyint(1) DEFAULT 0,
  `onboardAppNotifications` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_notifications;
CREATE TABLE `user_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) DEFAULT NULL,
  `notificationType` varchar(75) DEFAULT NULL,
  `notificationDate` int(11) DEFAULT NULL,
  `receiptId` varchar(500) DEFAULT NULL,
  `completed` tinyint(1) DEFAULT NULL,
  `error` tinyint(1) DEFAULT NULL,
  `message` varchar(500) DEFAULT NULL,
  `pushToken` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_open_archives_usage;
CREATE TABLE `user_open_archives_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `openArchivesCollectionId` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `usageCount` int(11) DEFAULT NULL,
  `month` int(11) NOT NULL DEFAULT 4,
  `instance` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`openArchivesCollectionId`,`userId`,`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_overdrive_usage;
CREATE TABLE `user_overdrive_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `usageCount` int(11) DEFAULT NULL,
  `instance` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`userId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_palace_project_usage;
CREATE TABLE `user_palace_project_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance` varchar(100) DEFAULT NULL,
  `userId` int(11) NOT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `usageCount` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `instance` (`instance`,`userId`,`year`,`month`),
  KEY `instance_2` (`instance`,`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_payment_lines;
CREATE TABLE `user_payment_lines` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `paymentId` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `amountPaid` float DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_payments;
CREATE TABLE `user_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) DEFAULT NULL,
  `paymentType` varchar(20) DEFAULT NULL,
  `orderId` varchar(75) DEFAULT NULL,
  `completed` tinyint(1) DEFAULT NULL,
  `finesPaid` varchar(8192) NOT NULL DEFAULT '',
  `totalPaid` float DEFAULT NULL,
  `transactionDate` int(11) DEFAULT NULL,
  `cancelled` tinyint(1) DEFAULT NULL,
  `error` tinyint(1) DEFAULT NULL,
  `message` varchar(500) DEFAULT NULL,
  `paidFromInstance` varchar(100) DEFAULT NULL,
  `transactionType` varchar(75) DEFAULT NULL,
  `transactionId` varchar(75) DEFAULT NULL,
  `aciToken` varchar(255) DEFAULT NULL,
  `requestingUrl` varchar(255) DEFAULT NULL,
  `deluxeRemittanceId` varchar(24) DEFAULT NULL,
  `deluxeSecurityId` varchar(32) DEFAULT NULL,
  `squareToken` varchar(255) DEFAULT NULL,
  `stripeToken` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`,`paymentType`,`completed`),
  KEY `paymentType` (`paymentType`,`orderId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_rbdigital_usage;
CREATE TABLE `user_rbdigital_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `usageCount` int(11) DEFAULT NULL,
  `instance` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`userId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_reading_history_work;
CREATE TABLE `user_reading_history_work` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL COMMENT 'The id of the user who checked out the item',
  `groupedWorkPermanentId` varchar(40) DEFAULT NULL,
  `source` varchar(25) NOT NULL COMMENT 'The source of the record being checked out',
  `sourceId` varchar(50) NOT NULL COMMENT 'The id of the item that item that was checked out within the source',
  `title` varchar(150) DEFAULT NULL COMMENT 'The title of the item in case this is ever deleted',
  `author` varchar(75) DEFAULT NULL COMMENT 'The author of the item in case this is ever deleted',
  `format` varchar(50) DEFAULT NULL COMMENT 'The format of the item in case this is ever deleted',
  `checkOutDate` int(11) NOT NULL COMMENT 'The first day we detected that the item was checked out to the patron',
  `checkInDate` bigint(20) DEFAULT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT 0,
  `isIll` tinyint(1) DEFAULT 0,
  `costSavings` decimal(10,2) DEFAULT 0.00,
  `isManuallyAdded` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`,`checkOutDate`),
  KEY `userId_2` (`userId`,`checkInDate`),
  KEY `userId_3` (`userId`,`title`),
  KEY `userId_4` (`userId`,`author`),
  KEY `sourceId` (`sourceId`),
  KEY `user_work` (`userId`,`groupedWorkPermanentId`),
  KEY `groupedWorkPermanentId` (`groupedWorkPermanentId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='The reading history for patrons';
DROP TABLE IF EXISTS user_roles;
CREATE TABLE `user_roles` (
  `userId` int(11) NOT NULL,
  `roleId` int(11) NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Links users with roles so users can perform administration f';
DROP TABLE IF EXISTS user_sideload_usage;
CREATE TABLE `user_sideload_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `sideloadId` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `usageCount` int(11) DEFAULT 0,
  `instance` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`userId`,`sideloadId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_summon_usage;
CREATE TABLE `user_summon_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `month` int(2) NOT NULL,
  `year` int(4) NOT NULL,
  `usageCount` int(11) DEFAULT NULL,
  `instance` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`userId`,`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_vdx_request;
CREATE TABLE `user_vdx_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) DEFAULT NULL,
  `datePlaced` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `author` varchar(255) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `feeAccepted` tinyint(1) DEFAULT NULL,
  `maximumFeeAmount` varchar(10) DEFAULT NULL,
  `catalogKey` varchar(20) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `pickupLocation` varchar(75) DEFAULT NULL,
  `vdxId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_website_usage;
CREATE TABLE `user_website_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `websiteId` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `usageCount` int(11) DEFAULT NULL,
  `instance` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `instance` (`instance`,`websiteId`,`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_work_review;
CREATE TABLE `user_work_review` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupedRecordPermanentId` varchar(40) NOT NULL,
  `userId` int(11) DEFAULT NULL,
  `rating` tinyint(1) DEFAULT NULL,
  `review` longtext DEFAULT NULL,
  `dateRated` int(11) DEFAULT NULL,
  `importedFrom` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userId_2` (`userId`,`groupedRecordPermanentId`),
  KEY `groupedRecordPermanentId` (`groupedRecordPermanentId`),
  KEY `userId` (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS user_year_in_review;
CREATE TABLE `user_year_in_review` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `settingId` int(11) NOT NULL,
  `wrappedActive` tinyint(1) DEFAULT 0,
  `wrappedViewed` tinyint(1) DEFAULT 0,
  `wrappedResults` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userId` (`userId`,`settingId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
DROP TABLE IF EXISTS usps_settings;
CREATE TABLE `usps_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clientId` varchar(255) DEFAULT NULL,
  `clientSecret` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS variables;
CREATE TABLE `variables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_2` (`name`),
  UNIQUE KEY `name_3` (`name`),
  KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS vdx_form;
CREATE TABLE `vdx_form` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `introText` text DEFAULT NULL,
  `showAuthor` tinyint(1) DEFAULT 0,
  `showPublisher` tinyint(1) DEFAULT 0,
  `showIsbn` tinyint(1) DEFAULT 0,
  `showAcceptFee` tinyint(1) DEFAULT 0,
  `showMaximumFee` tinyint(1) DEFAULT 0,
  `feeInformationText` text DEFAULT NULL,
  `showCatalogKey` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS vdx_settings;
CREATE TABLE `vdx_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `baseUrl` varchar(255) NOT NULL,
  `submissionEmailAddress` varchar(255) NOT NULL,
  `patronKey` varchar(50) DEFAULT NULL,
  `reqVerifySource` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS web_builder_audience;
CREATE TABLE `web_builder_audience` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS web_builder_basic_page;
CREATE TABLE `web_builder_basic_page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `urlAlias` varchar(100) DEFAULT NULL,
  `contents` longtext DEFAULT NULL,
  `teaser` varchar(512) DEFAULT NULL,
  `lastUpdate` int(11) DEFAULT 0,
  `requireLogin` tinyint(1) DEFAULT 0,
  `requireLoginUnlessInLibrary` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS web_builder_basic_page_access;
CREATE TABLE `web_builder_basic_page_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `basicPageId` int(11) NOT NULL,
  `patronTypeId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `basicPageId` (`basicPageId`,`patronTypeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS web_builder_basic_page_audience;
CREATE TABLE `web_builder_basic_page_audience` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `basicPageId` int(11) NOT NULL,
  `audienceId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `basicPageId` (`basicPageId`,`audienceId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS web_builder_basic_page_category;
CREATE TABLE `web_builder_basic_page_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `basicPageId` int(11) NOT NULL,
  `categoryId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `basicPageId` (`basicPageId`,`categoryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS web_builder_basic_page_home_location_access;
CREATE TABLE `web_builder_basic_page_home_location_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `basicPageId` int(11) NOT NULL,
  `homeLocationId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `basicPageId` (`basicPageId`,`homeLocationId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS web_builder_category;
CREATE TABLE `web_builder_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS web_builder_custom_form;
CREATE TABLE `web_builder_custom_form` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `urlAlias` varchar(100) DEFAULT NULL,
  `emailResultsTo` varchar(150) DEFAULT NULL,
  `requireLogin` tinyint(1) DEFAULT NULL,
  `introText` longtext DEFAULT NULL,
  `submissionResultText` longtext DEFAULT NULL,
  `includeIntroductoryTextInEmail` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS web_builder_custom_form_field;
CREATE TABLE `web_builder_custom_form_field` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `formId` int(11) NOT NULL,
  `weight` int(11) DEFAULT 0,
  `label` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT '',
  `fieldType` int(11) NOT NULL DEFAULT 0,
  `enumValues` varchar(255) DEFAULT NULL,
  `defaultValue` varchar(255) DEFAULT NULL,
  `required` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `formId` (`formId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS web_builder_custom_form_field_submission;
CREATE TABLE `web_builder_custom_form_field_submission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `formSubmissionId` int(11) NOT NULL,
  `submissionFieldId` int(11) NOT NULL,
  `formFieldContent` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `formSubmissionId` (`formSubmissionId`,`submissionFieldId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
DROP TABLE IF EXISTS web_builder_custom_from_submission;
CREATE TABLE `web_builder_custom_from_submission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `formId` int(11) NOT NULL,
  `libraryId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `dateSubmitted` int(11) NOT NULL,
  `submission` longtext DEFAULT NULL,
  `isRead` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `formId` (`formId`,`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS web_builder_custom_resource_page_access;
CREATE TABLE `web_builder_custom_resource_page_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customResourcePageId` int(11) NOT NULL,
  `patronTypeId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customResourcePageId` (`customResourcePageId`,`patronTypeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS web_builder_custom_web_resource_page;
CREATE TABLE `web_builder_custom_web_resource_page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) DEFAULT NULL,
  `urlAlias` varchar(100) DEFAULT NULL,
  `addToIndex` tinyint(1) DEFAULT 0,
  `requireLogin` tinyint(1) DEFAULT 0,
  `requireLoginUnlessInLibrary` tinyint(1) DEFAULT 0,
  `lastUpdate` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS web_builder_custom_web_resource_page_audience;
CREATE TABLE `web_builder_custom_web_resource_page_audience` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customResourcePageId` int(11) NOT NULL,
  `audienceId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customResourcePageId` (`customResourcePageId`,`audienceId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS web_builder_custom_web_resource_page_category;
CREATE TABLE `web_builder_custom_web_resource_page_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customResourcePageId` int(11) NOT NULL,
  `categoryId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customResourcePageId` (`customResourcePageId`,`categoryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS web_builder_portal_cell;
CREATE TABLE `web_builder_portal_cell` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `portalRowId` int(11) DEFAULT NULL,
  `widthTiny` int(11) DEFAULT NULL,
  `widthXs` int(11) DEFAULT NULL,
  `widthSm` int(11) DEFAULT NULL,
  `widthMd` int(11) DEFAULT NULL,
  `widthLg` int(11) DEFAULT NULL,
  `horizontalJustification` varchar(20) DEFAULT NULL,
  `verticalAlignment` varchar(20) DEFAULT NULL,
  `sourceType` varchar(30) DEFAULT NULL,
  `sourceId` varchar(30) DEFAULT NULL,
  `weight` int(11) DEFAULT 0,
  `markdown` longtext DEFAULT NULL,
  `sourceInfo` varchar(512) DEFAULT NULL,
  `title` varchar(255) DEFAULT '',
  `frameHeight` int(11) DEFAULT 0,
  `makeCellAccordion` tinyint(4) NOT NULL DEFAULT 0,
  `imageURL` varchar(255) DEFAULT NULL,
  `pdfView` varchar(12) DEFAULT NULL,
  `colorScheme` varchar(25) DEFAULT 'default',
  `invertColor` tinyint(1) DEFAULT 0,
  `imgAction` tinyint(1) DEFAULT 0,
  `imgAlt` varchar(255) DEFAULT NULL,
  `customImage` varchar(255) DEFAULT NULL,
  `hideDescription` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `portalRowId` (`portalRowId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS web_builder_portal_page;
CREATE TABLE `web_builder_portal_page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `urlAlias` varchar(100) DEFAULT NULL,
  `lastUpdate` int(11) DEFAULT 0,
  `requireLogin` tinyint(1) DEFAULT 0,
  `requireLoginUnlessInLibrary` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS web_builder_portal_page_access;
CREATE TABLE `web_builder_portal_page_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `portalPageId` int(11) NOT NULL,
  `patronTypeId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `portalPageId` (`portalPageId`,`patronTypeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS web_builder_portal_page_audience;
CREATE TABLE `web_builder_portal_page_audience` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `portalPageId` int(11) NOT NULL,
  `audienceId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `portalPageId` (`portalPageId`,`audienceId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS web_builder_portal_page_category;
CREATE TABLE `web_builder_portal_page_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `portalPageId` int(11) NOT NULL,
  `categoryId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `portalPageId` (`portalPageId`,`categoryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS web_builder_portal_row;
CREATE TABLE `web_builder_portal_row` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `portalPageId` int(11) DEFAULT NULL,
  `rowTitle` varchar(255) DEFAULT NULL,
  `weight` int(11) DEFAULT 0,
  `makeAccordion` tinyint(1) DEFAULT 0,
  `colorScheme` varchar(25) DEFAULT 'default',
  `invertColor` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `portalPageId` (`portalPageId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS web_builder_quick_poll;
CREATE TABLE `web_builder_quick_poll` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `urlAlias` varchar(100) DEFAULT NULL,
  `introText` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `submissionResultText` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `requireLogin` tinyint(1) DEFAULT NULL,
  `requireName` tinyint(1) DEFAULT NULL,
  `requireEmail` tinyint(1) DEFAULT NULL,
  `allowSuggestingNewOptions` tinyint(1) DEFAULT NULL,
  `allowMultipleSelections` tinyint(1) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `showResultsToPatrons` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
DROP TABLE IF EXISTS web_builder_quick_poll_option;
CREATE TABLE `web_builder_quick_poll_option` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `weight` int(11) DEFAULT 0,
  `pollId` int(11) NOT NULL,
  `label` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
DROP TABLE IF EXISTS web_builder_quick_poll_submission;
CREATE TABLE `web_builder_quick_poll_submission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pollId` int(11) NOT NULL,
  `libraryId` int(11) NOT NULL,
  `userId` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `dateSubmitted` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
DROP TABLE IF EXISTS web_builder_quick_poll_submission_selection;
CREATE TABLE `web_builder_quick_poll_submission_selection` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pollSubmissionId` int(11) NOT NULL,
  `pollOptionId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pollSubmissionId` (`pollSubmissionId`,`pollOptionId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
DROP TABLE IF EXISTS web_builder_resource;
CREATE TABLE `web_builder_resource` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `url` varchar(255) NOT NULL,
  `logo` varchar(200) DEFAULT NULL,
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `requiresLibraryCard` tinyint(1) NOT NULL DEFAULT 0,
  `description` longtext DEFAULT NULL,
  `teaser` varchar(512) DEFAULT NULL,
  `lastUpdate` int(11) DEFAULT 0,
  `inLibraryUseOnly` tinyint(1) DEFAULT 0,
  `openInNewTab` tinyint(1) DEFAULT 0,
  `requireLoginUnlessInLibrary` tinyint(1) DEFAULT 0,
  `generatePlacard` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `featured` (`featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS web_builder_resource_access_library;
CREATE TABLE `web_builder_resource_access_library` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `webResourceId` int(11) NOT NULL,
  `libraryId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `webResourceId` (`webResourceId`,`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS web_builder_resource_audience;
CREATE TABLE `web_builder_resource_audience` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `webResourceId` int(11) NOT NULL,
  `audienceId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `webResourceId` (`webResourceId`,`audienceId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS web_builder_resource_category;
CREATE TABLE `web_builder_resource_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `webResourceId` int(11) NOT NULL,
  `categoryId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `webResourceId` (`webResourceId`,`categoryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS web_builder_resource_usage;
CREATE TABLE `web_builder_resource_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `resourceName` varchar(100) NOT NULL,
  `pageViews` int(11) DEFAULT 0,
  `pageViewsByAuthenticatedUsers` int(11) DEFAULT 0,
  `pageViewsInLibrary` int(11) DEFAULT 0,
  `instance` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `instance` (`instance`,`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS web_builder_web_resources_settings;
CREATE TABLE `web_builder_web_resources_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `indexAtoZ` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS web_builder_web_resources_to_index;
CREATE TABLE `web_builder_web_resources_to_index` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `webResourcesSettingId` int(11) NOT NULL,
  `webResourcePageURL` varchar(100) DEFAULT NULL,
  `webResourcePageType` varchar(100) DEFAULT NULL,
  `customWebResourcePageId` varchar(100) DEFAULT NULL,
  `webResourceAudienceId` varchar(100) DEFAULT NULL,
  `webResourceCategoryId` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS website_facet_groups;
CREATE TABLE `website_facet_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
DROP TABLE IF EXISTS website_facets;
CREATE TABLE `website_facets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `facetGroupId` int(11) NOT NULL,
  `displayName` varchar(50) NOT NULL,
  `displayNamePlural` varchar(50) DEFAULT NULL,
  `facetName` varchar(50) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT 0,
  `numEntriesToShowByDefault` int(11) NOT NULL DEFAULT 5,
  `sortMode` enum('alphabetically','num_results') NOT NULL DEFAULT 'num_results',
  `collapseByDefault` tinyint(4) DEFAULT 1,
  `useMoreFacetPopup` tinyint(4) DEFAULT 1,
  `translate` tinyint(4) DEFAULT 1,
  `multiSelect` tinyint(4) DEFAULT 1,
  `canLock` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `groupFacet` (`facetGroupId`,`facetName`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
DROP TABLE IF EXISTS website_index_log;
CREATE TABLE `website_index_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log',
  `websiteName` varchar(255) NOT NULL,
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `notes` mediumtext DEFAULT NULL COMMENT 'Additional information about the run',
  `numPages` int(11) DEFAULT 0,
  `numAdded` int(11) DEFAULT 0,
  `numDeleted` int(11) DEFAULT 0,
  `numUpdated` int(11) DEFAULT 0,
  `numErrors` int(11) DEFAULT 0,
  `numInvalidPages` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `websiteName` (`websiteName`),
  KEY `startTime` (`startTime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS website_indexing_settings;
CREATE TABLE `website_indexing_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(75) NOT NULL,
  `searchCategory` varchar(75) NOT NULL,
  `siteUrl` varchar(255) DEFAULT NULL,
  `indexFrequency` enum('hourly','daily','weekly','monthly','yearly','once') DEFAULT NULL,
  `lastIndexed` int(11) DEFAULT NULL,
  `pathsToExclude` longtext DEFAULT NULL,
  `pageTitleExpression` varchar(255) DEFAULT '',
  `descriptionExpression` varchar(255) DEFAULT '',
  `deleted` tinyint(1) DEFAULT 0,
  `maxPagesToIndex` int(11) DEFAULT 2500,
  `crawlDelay` int(11) DEFAULT 10,
  `defaultCover` varchar(100) DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `lastIndexed` (`lastIndexed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS website_page_usage;
CREATE TABLE `website_page_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `webPageId` int(11) DEFAULT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `timesViewedInSearch` int(11) NOT NULL,
  `timesUsed` int(11) NOT NULL,
  `instance` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `instance` (`instance`,`webPageId`,`year`,`month`),
  KEY `instance_2` (`instance`,`webPageId`,`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS website_pages;
CREATE TABLE `website_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `websiteId` int(11) NOT NULL,
  `url` varchar(600) DEFAULT NULL,
  `checksum` bigint(20) DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT NULL,
  `firstDetected` int(11) DEFAULT NULL,
  `deleteReason` varchar(255) DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `url` (`url`),
  KEY `websiteId` (`websiteId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS worldpay_settings;
CREATE TABLE `worldpay_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `merchantCode` varchar(20) DEFAULT NULL,
  `settleCode` varchar(20) DEFAULT NULL,
  `paymentSite` varchar(255) NOT NULL DEFAULT '0',
  `useLineItems` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS xpresspay_settings;
CREATE TABLE `xpresspay_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `paymentTypeCode` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;DROP TABLE IF EXISTS talpa_settings;
CREATE TABLE `talpa_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `talpaApiToken` varchar(50) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS year_in_review_settings;
CREATE TABLE `year_in_review_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `year` int(11) DEFAULT NULL,
  `staffStartDate` int(11) DEFAULT NULL,
  `patronStartDate` int(11) DEFAULT NULL,
  `endDate` int(11) DEFAULT NULL,
  `style` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
INSERT INTO account_profiles (id, name, driver, loginConfiguration, authenticationMethod, vendorOpacUrl, patronApiUrl, recordSource, weight, ils) VALUES (1,'admin','Library','barcode_pin','db','defaultURL','defaultURL','admin',1,'library');
INSERT INTO browse_category (id, textId, userId, sharing, label, description, defaultFilter, defaultSort, searchTerm, numTimesShown, numTitlesClickedOn, sourceListId, source, libraryId, startDate, endDate) VALUES (1,'main_new_fiction',2,'everyone','New Fiction','','literary_form:Fiction','newest_to_oldest','',2,0,-1,'GroupedWork',-1,0,0),(2,'main_new_non_fiction',1,'everyone','New Non Fiction','','literary_form:Non Fiction','newest_to_oldest','',0,0,-1,'GroupedWork',-1,0,0);
INSERT INTO browse_category_group (id, name) VALUES (1, 'Main Library');
INSERT INTO browse_category_group_entry (browseCategoryGroupId, browseCategoryId) VALUES (1, 1);
INSERT INTO browse_category_group_entry (browseCategoryGroupId, browseCategoryId) VALUES (1, 2);
INSERT INTO grouped_work_display_settings(id, name, facetGroupId) VALUES (1, 'public', 1);
INSERT INTO grouped_work_display_settings(id, name, facetGroupId, applyNumberOfHoldingsBoost, showSearchTools, showInSearchResultsMainDetails, alwaysShowSearchResultsMainDetails) VALUES (2, 'academic', 2, 0, 0, 'a:4:{i:0;s:10:"showSeries";i:1;s:13:"showPublisher";i:2;s:19:"showPublicationDate";i:3;s:13:"showLanguages";i:4;s:13:"showPlaceOfPublication";}', 1);
INSERT INTO grouped_work_display_settings(id, name, facetGroupId, showSearchTools) VALUES (3, 'school_elem', 3, 0);
INSERT INTO grouped_work_display_settings(id, name, facetGroupId, showSearchTools) VALUES (4, 'school_upper', 3, 0);
INSERT INTO grouped_work_display_settings(id, name, facetGroupId, baseAvailabilityToggleOnLocalHoldingsOnly, includeAllRecordsInShelvingFacets, includeAllRecordsInDateAddedFacets, includeOutOfSystemExternalLinks) VALUES (5, 'consortium', 4, 0, 1, 1, 1);
INSERT INTO grouped_work_facet VALUES (1,1,'Format Category','format_category',1,0,0,'num_results',1,1,1,1,1,0,0,0,'Format Categories'),(2,1,'Search Within','availability_toggle',2,0,0,'num_results',1,1,1,1,1,0,0,0,'Available?'),(3,1,'Fiction / Non-Fiction','literary_form',3,5,0,'num_results',0,1,1,1,1,0,1,1,'Fiction / Non-Fiction'),(4,1,'Reading Level','target_audience',4,8,0,'num_results',0,1,1,1,1,0,1,1,'Reading Levels'),(5,1,'Available Now At','available_at',5,5,0,'num_results',0,1,1,1,1,0,0,0,'Available Now At'),(6,1,'eContent Collection','econtent_source',6,5,0,'num_results',0,1,1,1,1,0,1,0,'eContent Collections'),(7,1,'Format','format',7,5,0,'num_results',0,1,1,1,1,0,1,1,'Formats'),(8,1,'Author','authorStr',8,5,0,'num_results',0,1,1,1,1,0,0,0,'Authors'),(9,1,'Series','series_facet',9,5,0,'num_results',0,1,1,1,1,0,1,0,'Series'),(10,1,'AR Interest Level','accelerated_reader_interest_level',10,5,0,'num_results',0,1,1,1,1,0,0,0,'AR Interest Levels'),(11,1,'AR Reading Level','accelerated_reader_reading_level',11,5,0,'num_results',0,1,1,1,1,0,0,0,'AR Reading Levels'),(12,1,'AR Point Value','accelerated_reader_point_value',12,5,0,'num_results',0,1,1,1,1,0,0,0,'AR Point Values'),(13,1,'Subject','subject_facet',13,5,0,'num_results',0,1,1,1,1,0,1,0,'Subjects'),(14,1,'Added in the Last','time_since_added',14,5,0,'num_results',0,1,1,1,1,0,0,0,'Added in the Last'),(15,1,'Awards','awards_facet',15,5,0,'num_results',0,0,1,1,1,0,0,0,'Awards'),(16,1,'Item Type','itype',16,5,0,'num_results',0,0,1,1,1,0,0,0,'Item Types'),(17,1,'Language','language',17,5,0,'num_results',0,1,1,1,1,0,1,1,'Languages'),(18,1,'Movie Rating','mpaa_rating',18,5,0,'num_results',0,0,1,1,1,0,1,0,'Movie Ratings'),(19,1,'Publication Date','publishDateSort',19,5,0,'num_results',0,1,1,1,1,0,0,0,'Publication Dates'),(20,1,'User Rating','rating_facet',20,5,0,'num_results',0,1,1,1,1,0,0,0,'User Ratings'),(21,2,'Format Category','format_category',1,0,0,'num_results',1,1,1,1,1,0,0,0,'Format Categories'),(22,2,'Available?','availability_toggle',2,0,0,'num_results',1,1,1,1,1,0,0,0,'Available?'),(23,2,'Literary Form','literary_form',3,5,0,'num_results',0,1,1,1,1,0,0,1,'Literary Forms'),(24,2,'Reading Level','target_audience',4,8,0,'num_results',0,1,1,1,1,0,1,1,'Readling Levels'),(25,2,'Available Now At','available_at',5,5,0,'num_results',0,1,1,1,1,0,0,0,'Available Now At'),(26,2,'eContent Collection','econtent_source',6,5,0,'num_results',0,1,1,1,1,0,1,0,'eContent Collections'),(27,2,'Format','format',7,5,0,'num_results',0,1,1,1,1,0,1,1,'Formats'),(28,2,'Author','authorStr',8,5,0,'num_results',0,1,1,1,1,0,0,0,'Authors'),(29,2,'Series','series_facet',9,5,0,'num_results',0,1,1,1,1,0,1,0,'Series'),(30,2,'Subject','topic_facet',10,5,0,'num_results',0,1,1,1,1,0,1,0,'Subjects'),(31,2,'Region','geographic_facet',11,5,0,'num_results',0,0,1,1,1,0,0,0,'Regions'),(32,2,'Era','era',12,5,0,'num_results',0,0,1,1,1,0,0,0,'Eras'),(33,2,'Genre','genre_facet',13,5,0,'num_results',0,1,1,1,1,0,1,0,'Genres'),(34,2,'Added in the Last','time_since_added',14,5,0,'num_results',0,1,1,1,1,0,0,0,'Added in the Last'),(35,2,'Awards','awards_facet',15,5,0,'num_results',0,0,1,1,1,0,0,0,'Awards'),(36,2,'Item Type','itype',16,5,0,'num_results',0,0,1,1,1,0,0,0,'Item Types'),(37,2,'Language','language',17,5,0,'num_results',0,1,1,1,1,0,1,1,'Languages'),(38,2,'Movie Rating','mpaa_rating',18,5,0,'num_results',0,0,1,1,1,0,1,0,'Movie Ratings'),(39,2,'Publication Date','publishDateSort',19,5,0,'num_results',0,1,1,1,1,0,0,0,'Publication Dates'),(40,2,'User Rating','rating_facet',20,5,0,'num_results',0,1,1,1,1,0,0,0,'User Ratings'),(41,3,'Format Category','format_category',1,0,0,'num_results',1,1,1,1,1,0,0,0,'Format Categories'),(42,3,'Available?','availability_toggle',2,0,0,'num_results',1,1,1,1,1,0,0,0,'Available?'),(43,3,'Fiction / Non-Fiction','literary_form',3,5,0,'num_results',0,1,1,1,1,0,1,1,'Fiction / Non-Fiction'),(44,3,'Reading Level','target_audience',4,8,0,'num_results',0,1,1,1,1,0,1,1,'Readling Levels'),(45,3,'Available Now At','available_at',5,5,0,'num_results',0,1,1,1,1,0,0,0,'Available Now At'),(46,3,'eContent Collection','econtent_source',6,5,0,'num_results',0,1,1,1,1,0,1,0,'eContent Collections'),(47,3,'Format','format',7,5,0,'num_results',0,1,1,1,1,0,1,1,'Formats'),(48,3,'Author','authorStr',8,5,0,'num_results',0,1,1,1,1,0,0,0,'Authors'),(49,3,'Series','series_facet',9,5,0,'num_results',0,1,1,1,1,0,1,0,'Series'),(50,3,'AR Interest Level','accelerated_reader_interest_level',10,5,0,'num_results',0,1,1,1,1,0,0,0,'AR Interest Levels'),(51,3,'AR Reading Level','accelerated_reader_reading_level',11,5,0,'num_results',0,1,1,1,1,0,0,0,'AR Reading Levels'),(52,3,'AR Point Value','accelerated_reader_point_value',12,5,0,'num_results',0,1,1,1,1,0,0,0,'AR Point Values'),(53,3,'Subject','subject_facet',13,5,0,'num_results',0,1,1,1,1,0,1,0,'Subjects'),(54,3,'Added in the Last','time_since_added',14,5,0,'num_results',0,1,1,1,1,0,0,0,'Added in the Last'),(55,3,'Awards','awards_facet',15,5,0,'num_results',0,0,1,1,1,0,0,0,'Awards'),(56,3,'Item Type','itype',16,5,0,'num_results',0,0,1,1,1,0,0,0,'Item Types'),(57,3,'Language','language',17,5,0,'num_results',0,1,1,1,1,0,1,1,'Languages'),(58,3,'Movie Rating','mpaa_rating',18,5,0,'num_results',0,0,1,1,1,0,1,0,'Movie Ratings'),(59,3,'Publication Date','publishDateSort',19,5,0,'num_results',0,1,1,1,1,0,0,0,'Publication Dates'),(60,3,'User Rating','rating_facet',20,5,0,'num_results',0,1,1,1,1,0,0,0,'User Ratings'),(61,4,'Format Category','format_category',1,0,0,'num_results',1,1,1,1,1,0,0,0,'Format Categories'),(62,4,'Available?','availability_toggle',2,0,0,'num_results',1,1,1,1,1,0,0,0,'Available?'),(63,4,'Fiction / Non-Fiction','literary_form',3,5,0,'num_results',0,1,1,1,1,0,1,1,'Fiction / Non-Fiction'),(64,4,'Reading Level','target_audience',4,8,0,'num_results',0,1,1,1,1,0,1,1,'Readling Levels'),(65,4,'Available Now At','available_at',5,5,0,'num_results',0,1,1,1,1,0,0,0,'Available Now At'),(66,4,'eContent Collection','econtent_source',6,5,0,'num_results',0,1,1,1,1,0,1,0,'eContent Collections'),(67,4,'Format','format',7,5,0,'num_results',0,1,1,1,1,0,1,1,'Formats'),(68,4,'Author','authorStr',8,5,0,'num_results',0,1,1,1,1,0,0,0,'Authors'),(69,4,'Series','series_facet',9,5,0,'num_results',0,1,1,1,1,0,1,0,'Series'),(70,4,'AR Interest Level','accelerated_reader_interest_level',10,5,0,'num_results',0,1,1,1,1,0,0,0,'AR Interest Levels'),(71,4,'AR Reading Level','accelerated_reader_reading_level',11,5,0,'num_results',0,1,1,1,1,0,0,0,'AR Reading Levels'),(72,4,'AR Point Value','accelerated_reader_point_value',12,5,0,'num_results',0,1,1,1,1,0,0,0,'AR Point Values'),(73,4,'Subject','subject_facet',13,5,0,'num_results',0,1,1,1,1,0,1,0,'Subjects'),(74,4,'Added in the Last','time_since_added',14,5,0,'num_results',0,1,1,1,1,0,0,0,'Added in the Last'),(75,4,'Awards','awards_facet',15,5,0,'num_results',0,0,1,1,1,0,0,0,'Awards'),(76,4,'Item Type','itype',16,5,0,'num_results',0,0,1,1,1,0,0,0,'Item Types'),(77,4,'Language','language',17,5,0,'num_results',0,1,1,1,1,0,1,1,'Languages'),(78,4,'Movie Rating','mpaa_rating',18,5,0,'num_results',0,0,1,1,1,0,1,0,'Movie Ratings'),(79,4,'Publication Date','publishDateSort',19,5,0,'num_results',0,1,1,1,1,0,0,0,'Publication Dates'),(80,4,'User Rating','rating_facet',20,5,0,'num_results',0,1,1,1,1,0,0,0,'User Ratings');
INSERT INTO grouped_work_facet_groups (id, name) VALUES (1, 'public'), (2, 'academic'), (3, 'schools'), (4, 'consortia');
INSERT INTO ip_lookup (id, locationId, location, ip, startIpVal, endIpVal, isOpac, blockAccess, allowAPIAccess, showDebuggingInformation, logTimingInformation, logAllQueries) VALUES (1,-1,'Internal','127.0.0.1',2130706433,2130706433,0,0,1,1,0,0);
INSERT INTO languages VALUES (1,0,'en','English','English','English',0,'en-US');
INSERT INTO layout_settings (id, name) VALUES (1, 'default');
INSERT INTO library (libraryId, subdomain, displayName, finePaymentType, repeatSearchOption, allowPinReset, loginFormUsernameLabel, loginFormPasswordLabel, isDefault, browseCategoryGroupId, groupedWorkDisplaySettingId) VALUES (1,'main','Main Library',0, 'none', 0, 'Library Barcode', 'PIN / Password', 1, 1, 1);
INSERT INTO list_indexing_settings VALUES (1,1,0,0);
INSERT INTO location (locationId, code, displayName, libraryId, groupedWorkDisplaySettingId, browseCategoryGroupId)VALUES (1,'main','Main Library',1, 1, 1);
INSERT INTO `materials_request_status` (`id`, `description`, `isDefault`, `sendEmailToPatron`, `emailTemplate`, `isOpen`, `isPatronCancel`, `libraryId`, `checkForHolds`, `holdPlacedSuccessfully`, `holdFailed`, `holdNotNeeded`) VALUES
	(1, 'Request Pending', 1, 0, '', 1, 0, -1, 0, 0, 0, 0),
	(2, 'Already owned/On order', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The Library already owns this item or it is already on order. Please access our catalog to place this item on hold.  Please check our online catalog periodically to put a hold for this item.', 0, 0, -1, 0, 0, 0, 0),
	(3, 'Item purchased', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. Outcome: The library is purchasing the item you requested. Please check our online catalog periodically to put yourself on hold for this item. We anticipate that this item will be available soon for you to place a hold.', 1, 0, -1, 1, 0, 0, 0),
	(4, 'Referred to Collection Development - Adult', 0, 0, '', 1, 0, -1, 0, 0, 0, 0),
	(5, 'Referred to Collection Development - J/YA', 0, 0, '', 1, 0, -1, 0, 0, 0, 0),
	(6, 'Referred to Collection Development - AV', 0, 0, '', 1, 0, -1, 0, 0, 0, 0),
	(7, 'ILL Under Review', 0, 0, '', 1, 0, -1, 0, 0, 0, 0),
	(8, 'Request Referred to ILL', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The library\'s Interlibrary loan department is reviewing your request. We will attempt to borrow this item from another system. This process generally takes about 2 - 6 weeks.', 1, 0, -1, 0, 0, 0, 0),
	(9, 'Request Filled by ILL', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. Our Interlibrary Loan Department is set to borrow this item from another library.', 0, 0, -1, 0, 0, 0, 0),
	(10, 'Ineligible ILL', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. Your library account is not eligible for interlibrary loan at this time.', 0, 0, -1, 0, 0, 0, 0),
	(11, 'Not enough info - please contact Collection Development to clarify', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We need more specific information in order to locate the exact item you need. Please re-submit your request with more details.', 1, 0, -1, 0, 0, 0, 0),
	(12, 'Unable to acquire the item - out of print', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is out of print.', 0, 0, -1, 0, 0, 0, 0),
	(13, 'Unable to acquire the item - not available in the US', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is not available in the US.', 0, 0, -1, 0, 0, 0, 0),
	(14, 'Unable to acquire the item - not available from vendor', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is not available from a preferred vendor.', 0, 0, -1, 0, 0, 0, 0),
	(15, 'Unable to acquire the item - not published', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The item you requested has not yet been published. Please check our catalog when the publication date draws near.', 0, 0, -1, 0, 0, 0, 0),
	(16, 'Unable to acquire the item - price', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item does not fit our collection guidelines.', 0, 0, -1, 0, 0, 0, 0),
	(17, 'Unable to acquire the item - publication date', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item does not fit our collection guidelines.', 0, 0, -1, 0, 0, 0, 0),
	(18, 'Unavailable', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The item you requested cannot be purchased at this time from any of our regular suppliers and is not available from any of our lending libraries.', 0, 0, -1, 0, 0, 0, 0),
	(19, 'Cancelled by Patron', 0, 0, '', 0, 1, -1, 0, 0, 0, 0),
	(20, 'Cancelled - Duplicate Request', 0, 0, '', 0, 0, -1, 0, 0, 0, 0),
	(21, 'Hold Placed', 0, 1, '{title} has been received by the library and you have been added to the hold queue. 

Thank you for your purchase suggestion!', 0, 0, -1, 0, 1, 0, 0),
	(22, 'Hold Failed', 0, 1, '{title} has been received by the library, however we were not able to add you to the hold queue. Please ensure that your account is in good standing and then visit our catalog to place your hold.

	Thanks', 0, 0, -1, 0, 0, 1, 0),
	(23, 'Hold Not Needed', 0, 0, '', 0, 0, -1, 0, 0, 0, 1);
UPDATE modules set enabled=0;UPDATE modules set enabled=1 where name in ('Side Loads', 'User Lists');
INSERT INTO system_variables (currencyCode, storeRecordDetailsInSolr, storeRecordDetailsInDatabase, indexVersion, searchVersion, appScheme, trackIpAddresses) VALUES ('USD', 0, 1, 2, 2, 'aspen-lida', 0); 
INSERT INTO themes 
  (id,themeName,logoName,headerBackgroundColor,headerBackgroundColorDefault,headerForegroundColor,headerForegroundColorDefault,
  generatedCss,
  pageBackgroundColor,pageBackgroundColorDefault,primaryBackgroundColor,primaryBackgroundColorDefault,primaryForegroundColor,primaryForegroundColorDefault,
  bodyBackgroundColor,bodyBackgroundColorDefault,bodyTextColor,bodyTextColorDefault,
  secondaryBackgroundColor,secondaryBackgroundColorDefault,secondaryForegroundColor,secondaryForegroundColorDefault,
  tertiaryBackgroundColor,tertiaryBackgroundColorDefault,tertiaryForegroundColor,tertiaryForegroundColorDefault,
  browseCategoryPanelColor,browseCategoryPanelColorDefault,selectedBrowseCategoryBackgroundColor,selectedBrowseCategoryBackgroundColorDefault,
  selectedBrowseCategoryForegroundColor,selectedBrowseCategoryForegroundColorDefault,selectedBrowseCategoryBorderColor,selectedBrowseCategoryBorderColorDefault,
  deselectedBrowseCategoryBackgroundColor,deselectedBrowseCategoryBackgroundColorDefault,deselectedBrowseCategoryForegroundColor,deselectedBrowseCategoryForegroundColorDefault,
  deselectedBrowseCategoryBorderColor,deselectedBrowseCategoryBorderColorDefault,menubarHighlightBackgroundColor,menubarHighlightBackgroundColorDefault,
  menubarHighlightForegroundColor,menubarHighlightForegroundColorDefault,capitalizeBrowseCategories,
  browseCategoryImageSize,browseImageLayout,fullWidth,coverStyle,headerBackgroundImage,headerBackgroundImageSize,headerBackgroundImageRepeat,displayName
 ) VALUES (
  1,'default','logoNameTL_Logo_final.png','#f1f1f1',1,'#303030',1,
  '<style>h1 small, h2 small, h3 small, h4 small, h5 small{color: #6B6B6B;}#header-wrapper{background-color: #f1f1f1;background-image: none;color: #303030;}#library-name-header{color: #303030;}#footer-container{background-color: #f1f1f1;color: #303030;}body {background-color: #ffffff;color: #6B6B6B;}a,a:visited,.result-head,#selected-browse-label a,#selected-browse-label a:visited{color: #3174AF;}a:hover,.result-head:hover,#selected-browse-label a:hover{color: #265a87;}body .container, #home-page-browse-content{background-color: #ffffff;color: #6B6B6B;}#selected-browse-label{background-color: #ffffff;}.table-striped > tbody > tr:nth-child(2n+1) > td, .table-striped > tbody > tr:nth-child(2n+1) > th{background-color: #fafafa;}.table-sticky thead tr th{background-color: #ffffff;}#home-page-search, #horizontal-search-box,.searchTypeHome,.searchSource,.menu-bar {background-color: #0a7589;color: #ffffff;}#horizontal-menu-bar-container{background-color: #f1f1f1;color: #303030;position: relative;}#horizontal-menu-bar-container, #horizontal-menu-bar-container .menu-icon, #horizontal-menu-bar-container .menu-icon .menu-bar-label,#horizontal-menu-bar-container .menu-icon:visited{background-color: #f1f1f1;color: #303030;}#horizontal-menu-bar-container .menu-icon:hover, #horizontal-menu-bar-container .menu-icon:focus,#horizontal-menu-bar-container .menu-icon:hover .menu-bar-label, #horizontal-menu-bar-container .menu-icon:focus .menu-bar-label,#menuToggleButton.selected{background-color: #f1f1f1;color: #265a87;}#horizontal-search-label,#horizontal-search-box #horizontal-search-label{color: #ffffff;}.dropdownMenu, #account-menu, #header-menu, .dropdown .dropdown-menu.dropdownMenu{background-color: #ededed;color: #404040;}.dropdownMenu a, .dropdownMenu a:visited{color: #404040;}.modal-header, .modal-footer{background-color: #ffffff;color: #333333;}.close, .close:hover, .close:focus{color: #333333;}.modal-header{border-bottom-color: #e5e5e5;}.modal-footer{border-top-color: #e5e5e5;}.modal-content{background-color: #ffffff;color: #333333;}.exploreMoreBar{border-color: #0a7589;background: #0a758907;}.exploreMoreBar .label-top, .exploreMoreBar .label-top img{background-color: #0a7589;color: #ffffff;}.exploreMoreBar .exploreMoreBarLabel{color: #ffffff;}#home-page-search-label,#home-page-advanced-search-link,#keepFiltersSwitchLabel,.menu-bar, #horizontal-menu-bar-container {color: #ffffff}.facetTitle, .exploreMoreTitle, .panel-heading, .panel-heading .panel-title,.panel-default > .panel-heading, .sidebar-links .panel-heading, #account-link-accordion .panel .panel-title, #account-settings-accordion .panel .panel-title{background-color: #e7e7e7;}.facetTitle, .exploreMoreTitle,.panel-title,.panel-default > .panel-heading, .sidebar-links .panel-heading, #account-link-accordion .panel .panel-title, #account-settings-accordion .panel .panel-title, .panel-title > a,.panel-default > .panel-heading{color: #333333;}.facetTitle.expanded, .exploreMoreTitle.expanded,.active .panel-heading,#more-details-accordion .active .panel-heading,.active .panel-default > .panel-heading, .sidebar-links .active .panel-heading, #account-link-accordion .panel.active .panel-title, #account-settings-accordion .panel.active .panel-title,.active .panel-title,.active .panel-title > a,.active.panel-default > .panel-heading, .adminSection .adminPanel .adminSectionLabel{background-color: #de9d03;}.facetTitle.expanded, .exploreMoreTitle.expanded,.active .panel-heading,#more-details-accordion .active .panel-heading,#more-details-accordion .active .panel-title,#account-link-accordion .panel.active .panel-title,.active .panel-title,.active .panel-title > a,.active.panel-default > .panel-heading,.adminSection .adminPanel .adminSectionLabel, .facetLock.pull-right a{color: #303030;}.panel-body,.sidebar-links .panel-body,#more-details-accordion .panel-body,.facetDetails,.sidebar-links .panel-body a:not(.btn), .sidebar-links .panel-body a:visited:not(.btn), .sidebar-links .panel-body a:hover:not(.btn),.adminSection .adminPanel{background-color: #ffffff;color: #404040;}.facetValue, .facetValue a,.adminSection .adminPanel .adminActionLabel,.adminSection .adminPanel .adminActionLabel a{color: #404040;}.breadcrumbs{background-color: #f5f5f5;color: #6B6B6B;}.breadcrumb > li + li::before{color: #6B6B6B;}#footer-container{border-top-color: #de1f0b;}#horizontal-menu-bar-container{border-bottom-color: #de1f0b;}#home-page-browse-header{background-color: #d7dce3;}.browse-category,#browse-sub-category-menu button{background-color: #0087AB !important;border-color: #0087AB !important;color: #ffffff !important;}.browse-category.selected,.browse-category.selected:hover,#browse-sub-category-menu button.selected,#browse-sub-category-menu button.selected:hover{border-color: #0087AB !important;background-color: #0087AB !important;color: #ffffff !important;}.btn-default,.btn-default:visited,a.btn-default,a.btn-default:visited{background-color: #ffffff;color: #333333;border-color: #cccccc;}.btn-default:hover, .btn-default:focus, .btn-default:active, .btn-default.active, .open .dropdown-toggle.btn-default{background-color: #eeeeee;color: #333333;border-color: #cccccc;}.btn-primary,.btn-primary:visited,a.btn-primary,a.btn-primary:visited{background-color: #1b6ec2;color: #ffffff;border-color: #1b6ec2;}.btn-primary:hover, .btn-primary:focus, .btn-primary:active, .btn-primary.active, .open .dropdown-toggle.btn-primary{background-color: #ffffff;color: #1b6ec2;border-color: #1b6ec2;}.btn-action,.btn-action:visited,a.btn-action,a.btn-action:visited{background-color: #1b6ec2;color: #ffffff;border-color: #1b6ec2;}.btn-action:hover, .btn-action:focus, .btn-action:active, .btn-action.active, .open .dropdown-toggle.btn-action{background-color: #ffffff;color: #1b6ec2;border-color: #1b6ec2;}.btn-info,.btn-info:visited,a.btn-info,a.btn-info:visited{background-color: #8cd2e7;color: #000000;border-color: #999999;}.btn-info:hover, .btn-info:focus, .btn-info:active, .btn-info.active, .open .dropdown-toggle.btn-info{background-color: #ffffff;color: #217e9b;border-color: #217e9b;}.btn-tools,.btn-tools:visited,a.btn-tools,a.btn-tools:visited{background-color: #747474;color: #ffffff;border-color: #636363;}.btn-tools:hover, .btn-tools:focus, .btn-tools:active, .btn-tools.active, .open .dropdown-toggle.btn-tools{background-color: #636363;color: #ffffff;border-color: #636363;}.btn-warning,.btn-warning:visited,a.btn-warning,a.btn-warning:visited{background-color: #f4d03f;color: #000000;border-color: #999999;}.btn-warning:hover, .btn-warning:focus, .btn-warning:active, .btn-warning.active, .open .dropdown-toggle.btn-warning{background-color: #ffffff;color: #8d6708;border-color: #8d6708;}.label-warning{background-color: #f4d03f;color: #000000;}.btn-danger,.btn-danger:visited,a.btn-danger,a.btn-danger:visited{background-color: #D50000;color: #ffffff;border-color: #999999;}.btn-danger:hover, .btn-danger:focus, .btn-danger:active, .btn-danger.active, .open .dropdown-toggle.btn-danger{background-color: #ffffff;color: #D50000;border-color: #D50000;}.label-danger{background-color: #D50000;color: #ffffff;}.btn-editions,.btn-editions:visited{background-color: #f8f9fa;color: #212529;border-color: #999999;}.btn-editions:hover, .btn-editions:focus, .btn-editions:active, .btn-editions.active{background-color: #ffffff;color: #1b6ec2;border-color: #1b6ec2;}.badge{background-color: #666666;color: #ffffff;}#webMenuNavBar{background-color: #0a7589;margin-bottom: 2px;color: #ffffff;.navbar-nav > li > a, .navbar-nav > li > a:visited {color: #ffffff;}}.dropdown-menu{background-color: white;color: #6B6B6B;}.result-label{color: #44484a}.result-value{color: #6B6B6B}.search_tools{background-color: #f5f5f5;color: #6B6B6B;}</style>',
  '#ffffff',1,'#0a7589',1,'#ffffff',1,
  '#ffffff',1,'#6B6B6B',1,
  '#de9d03',1,'#303030',1,
  '#de1f0b',1,'#000000',1,
  '#d7dce3',1,'#0087AB',1,
  '#ffffff',1,'#0087AB',1,
  '#0087AB',1,'#ffffff',1,
  '#0087AB',1,'#f1f1f1',1,
  '#265a87',1,-1,
  0,0,1,'floating','','cover','no-repeat','Default'
);
UPDATE themes set headerBackgroundColor = '#ffffff' where id = 1;
UPDATE themes set browseCategoryPanelColor = '#ffffff' where id = 1;
UPDATE themes set closedPanelBackgroundColor = '#ffffff' where id = 1;
UPDATE themes set panelBodyBackgroundColor = '#ffffff' where id = 1;
INSERT INTO library_themes (libraryId, themeId, weight) VALUES (1, 1, 1);
INSERT INTO user (id, username, password, firstname, lastname,cat_username, cat_password, created, homeLocationId, myLocation1Id, myLocation2Id, displayName, source, email, unique_ils_id) VALUES (1,'nyt_user','nyt_password','New York Times','The New York Times','nyt_user','nyt_password','2019-11-19 01:57:54',1,1,1,'The New York Times','admin', '',''),(2,'aspen_admin','password','Aspen','Administrator','aspen_admin','password','2019-11-19 01:57:54',1,1,1,'A. Administrator','admin', '', '');
INSERT INTO user_roles (userId, roleId) VALUES (2,1),(2,2);
INSERT INTO variables VALUES (1,'lastHooplaExport','false'),(2,'validateChecksumsFromDisk','false'),(3,'offline_mode_when_offline_login_allowed','false'),(4,'fullReindexIntervalWarning','86400'),(5,'fullReindexIntervalCritical','129600'),(6,'bypass_export_validation','0'),(7,'last_validatemarcexport_time',NULL),(8,'last_export_valid','1'),(9,'record_grouping_running','false'),(10,'last_grouping_time',NULL),(25,'partial_reindex_running','true'),(26,'last_reindex_time',NULL),(27,'lastPartialReindexFinish',NULL),(29,'full_reindex_running','false'),(37,'lastFullReindexFinish',NULL),(44,'num_title_in_unique_sitemap','20000'),(45,'num_titles_in_most_popular_sitemap','20000'),(46,'lastRbdigitalExport',NULL);
INSERT INTO web_builder_audience VALUES (1,'Adults'),(4,'Children'),(7,'Everyone'),(5,'Parents'),(6,'Seniors'),(2,'Teens'),(3,'Tweens');
INSERT INTO web_builder_category VALUES (10,'Arts and Music'),(1,'eBooks and Audiobooks'),(9,'Homework Help'),(2,'Languages and Culture'),(11,'Library Documents and Policies'),(3,'Lifelong Learning'),(8,'Local History'),(4,'Newspapers and Magazines'),(5,'Reading Recommendations'),(6,'Reference and Research'),(7,'Video Streaming');
INSERT INTO website_facet_groups (id, name) VALUES (1, 'default');
INSERT INTO website_facets VALUES (1,1, 'Site Name', 'Site Names', 'website_name', 1, 5, 'num_results', 1, 1, 1, 1, 1),(2,1, 'Website Type', 'Website Types', 'search_category', 2, 5, 'num_results', 1, 1, 1, 1, 1),(3,1, 'Audience', 'Audiences', 'audience_facet', 3, 5, 'num_results', 1, 1, 1, 1, 1),(4,1, 'Category', 'Categories', 'category_facet', 4, 5, 'num_results', 1, 1, 1, 1, 1);
INSERT INTO events_facet_groups (id, name) VALUES (1, 'default');
INSERT INTO events_facet VALUES (1,1, 'Age Group/Audience', 'Age Groups/Audiences', 'age_group_facet', 1, 5, 0, 'num_results', 0, 1, 1, 1, 1, 0, 1, 1),(2,1, 'Branch', 'Branches', 'branch', 2, 5, 0, 'num_results', 0, 1, 1, 1, 1, 0, 1, 1),(3,1, 'Room', 'Rooms', 'room', 3, 5, 0, 'num_results', 0, 1, 1, 1, 1, 0, 1, 1),(4,1, 'Event Type', 'Event Types', 'event_type', 4, 5, 0, 'num_results', 0, 1, 1, 1, 1, 0, 1, 1),(5,1, 'Program Type', 'Program Types', 'program_type_facet', 5, 5, 0, 'num_results', 0, 1, 1, 1, 1, 0, 1, 1),(6,1, 'Registration Required?', 'Registration Required?', 'registration_required', 6, 5, 0, 'num_results', 0, 1, 1, 1, 1, 0, 1, 1),(7,1, 'Category', 'Categories', 'internal_category', 7, 5, 0, 'num_results', 0, 1, 1, 1, 1, 0, 1, 1),(8,1, 'Reservation State', 'Reservation State', 'reservation_state', 8, 5, 0, 'num_results', 0, 1, 1, 1, 1, 0, 1, 1),(9,1, 'Event State', 'Event State', 'event_state', 9, 5, 0, 'num_results', 0, 1, 1, 1, 1, 0, 1, 1);
INSERT INTO open_archives_facet_groups (id, name) VALUES (1, 'default');
INSERT INTO open_archives_facets VALUES (1,1, 'Collection', 'Collections', 'collection_name', 1, 5, 'num_results', 1, 1, 1, 1, 1),(2,1, 'Creator', 'Creators', 'creator_facet', 2, 5, 'num_results', 1, 1, 1, 1, 1),(3,1, 'Contributor', 'Contributors', 'contributor_facet', 3, 5, 'num_results', 1, 1, 1, 1, 1),(4,1, 'Type', 'Types', 'type', 4, 5, 'num_results', 1, 1, 1, 1, 1),(5,1, 'Subject', 'Subjects', 'subject_facet', 5, 5, 'num_results', 1, 1, 1, 1, 1),(6,1, 'Publisher', 'Publishers', 'publisher_facet', 6, 5, 'num_results', 1, 1, 1, 1, 1),(7,1, 'Source', 'Sources', 'source', 7, 5, 'num_results', 1, 1, 1, 1, 1);
INSERT INTO grouped_work_format_sort_group (id, name) VALUES (1, 'Default Format Sorting');
