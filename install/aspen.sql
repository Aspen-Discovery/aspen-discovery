-- MySQL dump 10.13  Distrib 5.7.23, for Win64 (x86_64)
--
-- Host: localhost    Database: model2
-- ------------------------------------------------------
-- Server version	5.7.23-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `accelerated_reading_isbn`
--

DROP TABLE IF EXISTS `accelerated_reading_isbn`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accelerated_reading_isbn` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `arBookId` int(11) NOT NULL,
  `isbn` varchar(13) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `isbn` (`isbn`),
  KEY `arBookId` (`arBookId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accelerated_reading_isbn`
--

/*!40000 ALTER TABLE `accelerated_reading_isbn` DISABLE KEYS */;
/*!40000 ALTER TABLE `accelerated_reading_isbn` ENABLE KEYS */;

--
-- Table structure for table `accelerated_reading_settings`
--

DROP TABLE IF EXISTS `accelerated_reading_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accelerated_reading_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `indexSeries` tinyint(1) DEFAULT '1',
  `indexSubjects` tinyint(1) DEFAULT '1',
  `arExportPath` varchar(255) NOT NULL,
  `ftpServer` varchar(255) NOT NULL,
  `ftpUser` varchar(255) NOT NULL,
  `ftpPassword` varchar(255) NOT NULL,
  `lastFetched` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accelerated_reading_settings`
--

/*!40000 ALTER TABLE `accelerated_reading_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `accelerated_reading_settings` ENABLE KEYS */;

--
-- Table structure for table `accelerated_reading_subject`
--

DROP TABLE IF EXISTS `accelerated_reading_subject`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accelerated_reading_subject` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `topic` varchar(255) NOT NULL,
  `subTopic` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `topic` (`topic`,`subTopic`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accelerated_reading_subject`
--

/*!40000 ALTER TABLE `accelerated_reading_subject` DISABLE KEYS */;
/*!40000 ALTER TABLE `accelerated_reading_subject` ENABLE KEYS */;

--
-- Table structure for table `accelerated_reading_subject_to_title`
--

DROP TABLE IF EXISTS `accelerated_reading_subject_to_title`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accelerated_reading_subject_to_title` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `arBookId` int(11) NOT NULL,
  `arSubjectId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `arBookId` (`arBookId`,`arSubjectId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accelerated_reading_subject_to_title`
--

/*!40000 ALTER TABLE `accelerated_reading_subject_to_title` DISABLE KEYS */;
/*!40000 ALTER TABLE `accelerated_reading_subject_to_title` ENABLE KEYS */;

--
-- Table structure for table `accelerated_reading_titles`
--

DROP TABLE IF EXISTS `accelerated_reading_titles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accelerated_reading_titles` (
  `arBookId` int(11) NOT NULL,
  `language` varchar(2) NOT NULL,
  `title` varchar(255) NOT NULL,
  `authorCombined` varchar(255) NOT NULL,
  `bookLevel` float DEFAULT NULL,
  `arPoints` int(4) DEFAULT NULL,
  `isFiction` tinyint(1) DEFAULT NULL,
  `interestLevel` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`arBookId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accelerated_reading_titles`
--

/*!40000 ALTER TABLE `accelerated_reading_titles` DISABLE KEYS */;
/*!40000 ALTER TABLE `accelerated_reading_titles` ENABLE KEYS */;

--
-- Table structure for table `account_profiles`
--

DROP TABLE IF EXISTS `account_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT 'ils',
  `driver` varchar(50) NOT NULL,
  `loginConfiguration` enum('barcode_pin','name_barcode') NOT NULL,
  `authenticationMethod` enum('ils','sip2','db','ldap') NOT NULL DEFAULT 'ils',
  `vendorOpacUrl` varchar(100) NOT NULL,
  `patronApiUrl` varchar(100) NOT NULL,
  `recordSource` varchar(50) NOT NULL,
  `weight` int(11) NOT NULL,
  `databaseHost` varchar(100) DEFAULT NULL,
  `databaseName` varchar(50) DEFAULT NULL,
  `databaseUser` varchar(50) DEFAULT NULL,
  `databasePassword` varchar(50) DEFAULT NULL,
  `sipHost` varchar(100) DEFAULT NULL,
  `sipPort` varchar(50) DEFAULT NULL,
  `sipUser` varchar(50) DEFAULT NULL,
  `sipPassword` varchar(50) DEFAULT NULL,
  `databasePort` varchar(5) DEFAULT NULL,
  `databaseTimezone` varchar(50) DEFAULT NULL,
  `oAuthClientId` varchar(36) DEFAULT NULL,
  `oAuthClientSecret` varchar(36) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_profiles`
--

/*!40000 ALTER TABLE `account_profiles` DISABLE KEYS */;
INSERT INTO `account_profiles` VALUES (1,'admin','Library','name_barcode','db','defaultURL','defaultURL','',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `account_profiles` ENABLE KEYS */;

--
-- Table structure for table `archive_private_collections`
--

DROP TABLE IF EXISTS `archive_private_collections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `archive_private_collections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `privateCollections` mediumtext,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `archive_private_collections`
--

/*!40000 ALTER TABLE `archive_private_collections` DISABLE KEYS */;
/*!40000 ALTER TABLE `archive_private_collections` ENABLE KEYS */;

--
-- Table structure for table `archive_requests`
--

DROP TABLE IF EXISTS `archive_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `format` mediumtext,
  `purpose` mediumtext,
  `pid` varchar(50) DEFAULT NULL,
  `dateRequested` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `archive_requests`
--

/*!40000 ALTER TABLE `archive_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `archive_requests` ENABLE KEYS */;

--
-- Table structure for table `archive_subjects`
--

DROP TABLE IF EXISTS `archive_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `archive_subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subjectsToIgnore` mediumtext,
  `subjectsToRestrict` mediumtext,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `archive_subjects`
--

/*!40000 ALTER TABLE `archive_subjects` DISABLE KEYS */;
/*!40000 ALTER TABLE `archive_subjects` ENABLE KEYS */;

--
-- Table structure for table `aspen_usage`
--

DROP TABLE IF EXISTS `aspen_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `aspen_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `pageViews` int(11) DEFAULT '0',
  `pageViewsByBots` int(11) DEFAULT '0',
  `pageViewsByAuthenticatedUsers` int(11) DEFAULT '0',
  `pagesWithErrors` int(11) DEFAULT '0',
  `slowPages` int(11) DEFAULT '0',
  `ajaxRequests` int(11) DEFAULT '0',
  `slowAjaxRequests` int(11) DEFAULT '0',
  `coverViews` int(11) DEFAULT '0',
  `genealogySearches` int(11) DEFAULT '0',
  `groupedWorkSearches` int(11) DEFAULT '0',
  `islandoraSearches` int(11) DEFAULT '0',
  `openArchivesSearches` int(11) DEFAULT '0',
  `userListSearches` int(11) DEFAULT '0',
  `websiteSearches` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `aspen_usage`
--

/*!40000 ALTER TABLE `aspen_usage` DISABLE KEYS */;
INSERT INTO `aspen_usage` VALUES (1,2019,11,3,0,3,1,3,6,5,0,0,0,0,0,0,0);
/*!40000 ALTER TABLE `aspen_usage` ENABLE KEYS */;

--
-- Table structure for table `author_authorities`
--

DROP TABLE IF EXISTS `author_authorities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `author_authorities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `originalName` varchar(255) NOT NULL,
  `authoritativeName` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `originalName` (`originalName`),
  KEY `authoritativeName` (`authoritativeName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `author_authorities`
--

/*!40000 ALTER TABLE `author_authorities` DISABLE KEYS */;
/*!40000 ALTER TABLE `author_authorities` ENABLE KEYS */;

--
-- Table structure for table `author_enrichment`
--

DROP TABLE IF EXISTS `author_enrichment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `author_enrichment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `authorName` varchar(255) NOT NULL,
  `hideWikipedia` tinyint(1) DEFAULT NULL,
  `wikipediaUrl` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `authorName` (`authorName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `author_enrichment`
--

/*!40000 ALTER TABLE `author_enrichment` DISABLE KEYS */;
/*!40000 ALTER TABLE `author_enrichment` ENABLE KEYS */;

--
-- Table structure for table `bad_words`
--

DROP TABLE IF EXISTS `bad_words`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bad_words` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique Id for bad_word',
  `word` varchar(50) NOT NULL COMMENT 'The bad word that will be replaced',
  `replacement` varchar(50) NOT NULL COMMENT 'A replacement value for the word.',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=451 DEFAULT CHARSET=utf8 COMMENT='Stores information about bad_words that should be removed fr';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bad_words`
--

/*!40000 ALTER TABLE `bad_words` DISABLE KEYS */;
/*!40000 ALTER TABLE `bad_words` ENABLE KEYS */;

--
-- Table structure for table `bookcover_info`
--

DROP TABLE IF EXISTS `bookcover_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bookcover_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recordType` varchar(20) DEFAULT NULL,
  `recordId` varchar(50) DEFAULT NULL,
  `firstLoaded` int(11) NOT NULL,
  `lastUsed` int(11) NOT NULL,
  `imageSource` varchar(50) DEFAULT NULL,
  `sourceWidth` int(11) DEFAULT NULL,
  `sourceHeight` int(11) DEFAULT NULL,
  `thumbnailLoaded` tinyint(1) DEFAULT '0',
  `mediumLoaded` tinyint(1) DEFAULT '0',
  `largeLoaded` tinyint(1) DEFAULT '0',
  `uploadedImage` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `record_info` (`recordType`,`recordId`),
  KEY `lastUsed` (`lastUsed`),
  KEY `imageSource` (`imageSource`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookcover_info`
--

/*!40000 ALTER TABLE `bookcover_info` DISABLE KEYS */;
/*!40000 ALTER TABLE `bookcover_info` ENABLE KEYS */;

--
-- Table structure for table `browse_category`
--

DROP TABLE IF EXISTS `browse_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `browse_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `textId` varchar(60) NOT NULL DEFAULT '-1',
  `userId` int(11) DEFAULT NULL,
  `sharing` enum('private','location','library','everyone') DEFAULT 'everyone',
  `label` varchar(50) NOT NULL,
  `description` mediumtext,
  `defaultFilter` text,
  `defaultSort` enum('relevance','popularity','newest_to_oldest','oldest_to_newest','author','title','user_rating') DEFAULT NULL,
  `searchTerm` varchar(500) NOT NULL DEFAULT '',
  `numTimesShown` mediumint(9) NOT NULL DEFAULT '0',
  `numTitlesClickedOn` mediumint(9) NOT NULL DEFAULT '0',
  `sourceListId` mediumint(9) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `textId` (`textId`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `browse_category`
--

/*!40000 ALTER TABLE `browse_category` DISABLE KEYS */;
INSERT INTO `browse_category` VALUES (4,'main_new_fiction',1,'everyone','New Fiction','','literary_form:Fiction','newest_to_oldest','',1,0,-1),(5,'main_new_non_fiction',1,'everyone','New Non Fiction','','literary_form:Non Fiction','newest_to_oldest','',0,0,-1);
/*!40000 ALTER TABLE `browse_category` ENABLE KEYS */;

--
-- Table structure for table `browse_category_library`
--

DROP TABLE IF EXISTS `browse_category_library`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `browse_category_library` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `browseCategoryTextId` varchar(60) NOT NULL DEFAULT '-1',
  `weight` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `libraryId` (`libraryId`,`browseCategoryTextId`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `browse_category_library`
--

/*!40000 ALTER TABLE `browse_category_library` DISABLE KEYS */;
INSERT INTO `browse_category_library` VALUES (3,2,'main_new_fiction',0),(5,2,'main_new_non_fiction',0);
/*!40000 ALTER TABLE `browse_category_library` ENABLE KEYS */;

--
-- Table structure for table `browse_category_location`
--

DROP TABLE IF EXISTS `browse_category_location`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `browse_category_location` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `locationId` int(11) NOT NULL,
  `browseCategoryTextId` varchar(60) NOT NULL DEFAULT '-1',
  `weight` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `locationId` (`locationId`,`browseCategoryTextId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `browse_category_location`
--

/*!40000 ALTER TABLE `browse_category_location` DISABLE KEYS */;
/*!40000 ALTER TABLE `browse_category_location` ENABLE KEYS */;

--
-- Table structure for table `browse_category_subcategories`
--

DROP TABLE IF EXISTS `browse_category_subcategories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `browse_category_subcategories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `browseCategoryId` int(11) NOT NULL,
  `subCategoryId` int(11) NOT NULL,
  `weight` smallint(2) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `subCategoryId` (`subCategoryId`,`browseCategoryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `browse_category_subcategories`
--

/*!40000 ALTER TABLE `browse_category_subcategories` DISABLE KEYS */;
/*!40000 ALTER TABLE `browse_category_subcategories` ENABLE KEYS */;

--
-- Table structure for table `cached_values`
--

DROP TABLE IF EXISTS `cached_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cached_values` (
  `cacheKey` varchar(200) NOT NULL,
  `value` varchar(16384) DEFAULT NULL,
  `expirationTime` int(11) DEFAULT NULL,
  UNIQUE KEY `cacheKey` (`cacheKey`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `claim_authorship_requests`
--

DROP TABLE IF EXISTS `claim_authorship_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `claim_authorship_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `message` mediumtext,
  `pid` varchar(50) DEFAULT NULL,
  `dateRequested` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `claim_authorship_requests`
--

/*!40000 ALTER TABLE `claim_authorship_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `claim_authorship_requests` ENABLE KEYS */;

--
-- Table structure for table `cloud_library_availability`
--

DROP TABLE IF EXISTS `cloud_library_availability`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cloud_library_availability` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cloudLibraryId` varchar(25) NOT NULL,
  `totalCopies` tinyint(4) NOT NULL DEFAULT '0',
  `sharedCopies` tinyint(4) NOT NULL DEFAULT '0',
  `totalLoanCopies` tinyint(4) NOT NULL DEFAULT '0',
  `totalHoldCopies` tinyint(4) NOT NULL DEFAULT '0',
  `sharedLoanCopies` tinyint(4) NOT NULL DEFAULT '0',
  `rawChecksum` bigint(20) DEFAULT NULL,
  `rawResponse` mediumtext,
  `lastChange` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cloudLibraryId` (`cloudLibraryId`),
  KEY `lastChange` (`lastChange`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cloud_library_availability`
--

/*!40000 ALTER TABLE `cloud_library_availability` DISABLE KEYS */;
/*!40000 ALTER TABLE `cloud_library_availability` ENABLE KEYS */;

--
-- Table structure for table `cloud_library_export_log`
--

DROP TABLE IF EXISTS `cloud_library_export_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cloud_library_export_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `notes` text COMMENT 'Additional information about the run',
  `numProducts` int(11) DEFAULT '0',
  `numErrors` int(11) DEFAULT '0',
  `numAdded` int(11) DEFAULT '0',
  `numDeleted` int(11) DEFAULT '0',
  `numUpdated` int(11) DEFAULT '0',
  `numAvailabilityChanges` int(11) DEFAULT '0',
  `numMetadataChanges` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cloud_library_export_log`
--

/*!40000 ALTER TABLE `cloud_library_export_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `cloud_library_export_log` ENABLE KEYS */;

--
-- Table structure for table `cloud_library_record_usage`
--

DROP TABLE IF EXISTS `cloud_library_record_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cloud_library_record_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cloudLibraryId` int(11) DEFAULT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `timesHeld` int(11) NOT NULL,
  `timesCheckedOut` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cloudLibraryId` (`cloudLibraryId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cloud_library_record_usage`
--

/*!40000 ALTER TABLE `cloud_library_record_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `cloud_library_record_usage` ENABLE KEYS */;

--
-- Table structure for table `cloud_library_scopes`
--

DROP TABLE IF EXISTS `cloud_library_scopes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cloud_library_scopes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `includeEBooks` tinyint(4) DEFAULT '1',
  `includeEAudiobook` tinyint(4) DEFAULT '1',
  `restrictToChildrensMaterial` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cloud_library_scopes`
--

/*!40000 ALTER TABLE `cloud_library_scopes` DISABLE KEYS */;
/*!40000 ALTER TABLE `cloud_library_scopes` ENABLE KEYS */;

--
-- Table structure for table `cloud_library_settings`
--

DROP TABLE IF EXISTS `cloud_library_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cloud_library_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `apiUrl` varchar(255) DEFAULT NULL,
  `userInterfaceUrl` varchar(255) DEFAULT NULL,
  `libraryId` varchar(50) DEFAULT NULL,
  `accountId` varchar(50) DEFAULT NULL,
  `accountKey` varchar(50) DEFAULT NULL,
  `runFullUpdate` tinyint(1) DEFAULT '0',
  `lastUpdateOfChangedRecords` int(11) DEFAULT '0',
  `lastUpdateOfAllRecords` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cloud_library_settings`
--

/*!40000 ALTER TABLE `cloud_library_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `cloud_library_settings` ENABLE KEYS */;

--
-- Table structure for table `cloud_library_title`
--

DROP TABLE IF EXISTS `cloud_library_title`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cloud_library_title` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cloudLibraryId` varchar(25) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `subTitle` varchar(255) DEFAULT NULL,
  `author` varchar(255) DEFAULT NULL,
  `format` varchar(50) DEFAULT NULL,
  `rawChecksum` bigint(20) DEFAULT NULL,
  `rawResponse` mediumtext,
  `dateFirstDetected` bigint(20) DEFAULT NULL,
  `lastChange` int(11) NOT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `cloudLibraryId` (`cloudLibraryId`),
  KEY `lastChange` (`lastChange`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cloud_library_title`
--

/*!40000 ALTER TABLE `cloud_library_title` DISABLE KEYS */;
/*!40000 ALTER TABLE `cloud_library_title` ENABLE KEYS */;

--
-- Table structure for table `cron_log`
--

DROP TABLE IF EXISTS `cron_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cron_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of the cron log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the cron run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the cron run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the cron run last updated (to check for stuck processes)',
  `notes` text COMMENT 'Additional information about the cron run',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cron_log`
--

/*!40000 ALTER TABLE `cron_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `cron_log` ENABLE KEYS */;

--
-- Table structure for table `cron_process_log`
--

DROP TABLE IF EXISTS `cron_process_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cron_process_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of cron process',
  `cronId` int(11) NOT NULL COMMENT 'The id of the cron run this process ran during',
  `processName` varchar(50) NOT NULL COMMENT 'The name of the process being run',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the process started',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the process last updated (to check for stuck processes)',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the process ended',
  `numErrors` int(11) NOT NULL DEFAULT '0' COMMENT 'The number of errors that occurred during the process',
  `numUpdates` int(11) NOT NULL DEFAULT '0' COMMENT 'The number of updates, additions, etc. that occurred',
  `notes` text COMMENT 'Additional information about the process',
  PRIMARY KEY (`id`),
  KEY `cronId` (`cronId`),
  KEY `processName` (`processName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cron_process_log`
--

/*!40000 ALTER TABLE `cron_process_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `cron_process_log` ENABLE KEYS */;

--
-- Table structure for table `db_update`
--

DROP TABLE IF EXISTS `db_update`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `db_update` (
  `update_key` varchar(100) NOT NULL,
  `date_run` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`update_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `db_update`
--

/*!40000 ALTER TABLE `db_update` DISABLE KEYS */;
INSERT INTO `db_update` VALUES ('accelerated_reader','2019-11-19 13:31:53'),('account_profiles_1','2019-01-28 20:59:02'),('account_profiles_2','2019-11-19 13:32:04'),('account_profiles_3','2019-11-19 13:32:05'),('account_profiles_4','2019-11-19 13:32:05'),('account_profiles_5','2019-11-19 13:32:05'),('account_profiles_oauth','2019-11-19 13:32:05'),('acsLog','2011-12-13 16:04:23'),('additional_library_contact_links','2019-01-28 20:58:56'),('additional_locations_for_availability','2019-01-28 20:58:56'),('addTablelistWidgetListsLinks','2019-01-28 20:59:01'),('add_indexes','2019-01-28 20:59:01'),('add_indexes2','2019-01-28 20:59:01'),('add_search_source_to_saved_searches','2019-01-28 20:59:02'),('add_search_url_to_saved_searches','2019-11-19 13:32:05'),('add_sms_indicator_to_phone','2019-01-28 20:58:56'),('allow_masquerade_mode','2019-01-28 20:58:56'),('allow_reading_history_display_in_masquerade_mode','2019-01-28 20:58:56'),('alpha_browse_setup_2','2019-01-28 20:58:59'),('alpha_browse_setup_3','2019-01-28 20:58:59'),('alpha_browse_setup_4','2019-01-28 20:58:59'),('alpha_browse_setup_5','2019-01-28 20:58:59'),('alpha_browse_setup_6','2019-01-28 20:59:00'),('alpha_browse_setup_7','2019-01-28 20:59:00'),('alpha_browse_setup_8','2019-01-28 20:59:00'),('alpha_browse_setup_9','2019-01-28 20:59:01'),('always_show_search_results_Main_details','2019-01-28 20:58:56'),('analytics','2019-01-28 20:59:01'),('analytics_1','2019-01-28 20:59:01'),('analytics_2','2019-01-28 20:59:01'),('analytics_3','2019-01-28 20:59:01'),('analytics_4','2019-01-28 20:59:01'),('analytics_5','2019-01-28 20:59:02'),('analytics_6','2019-01-28 20:59:02'),('analytics_7','2019-01-28 20:59:02'),('analytics_8','2019-01-28 20:59:02'),('archivesRole','2019-01-28 20:58:59'),('archive_collection_default_view_mode','2019-01-28 20:58:56'),('archive_filtering','2019-01-28 20:58:56'),('archive_more_details_customization','2019-01-28 20:58:56'),('archive_object_filtering','2019-01-28 20:58:56'),('archive_private_collections','2019-01-28 20:59:02'),('archive_requests','2019-01-28 20:59:02'),('archive_subjects','2019-01-28 20:59:02'),('aspen_usage','2019-11-19 13:32:06'),('aspen_usage_websites','2019-11-19 13:32:06'),('authentication_profiles','2019-01-28 20:59:02'),('authorities','2019-11-19 13:31:53'),('author_enrichment','2019-01-28 20:58:59'),('availability_toggle_customization','2019-01-28 20:58:56'),('bookcover_info','2019-11-19 13:32:05'),('book_store','2019-01-28 20:59:01'),('book_store_1','2019-01-28 20:59:01'),('boost_disabling','2019-01-28 20:59:01'),('browse_categories','2019-01-28 20:59:02'),('browse_categories_lists','2019-01-28 20:59:02'),('browse_categories_search_term_and_stats','2019-01-28 20:59:02'),('browse_categories_search_term_length','2019-01-28 20:59:02'),('browse_category_default_view_mode','2019-01-28 20:58:56'),('browse_category_ratings_mode','2019-01-28 20:58:56'),('catalogingRole','2019-01-28 20:58:59'),('change_to_innodb','2019-03-05 18:07:51'),('claim_authorship_requests','2019-01-28 20:59:02'),('clear_analytics','2019-01-28 20:59:02'),('cloud_library_availability','2019-11-19 13:32:02'),('cloud_library_exportLog','2019-11-19 13:32:02'),('cloud_library_exportTable','2019-11-19 13:32:02'),('cloud_library_scoping','2019-11-19 13:32:02'),('cloud_library_settings','2019-11-19 13:32:02'),('collapse_facets','2019-01-28 20:58:55'),('combined_results','2019-01-28 20:58:56'),('contentEditor','2019-01-28 20:58:59'),('convertOldEContent','2011-11-06 22:58:31'),('convert_to_format_status_maps','2019-11-19 13:31:53'),('coverArt_suppress','2019-01-28 20:58:58'),('create_cloud_library_module','2019-11-19 13:32:02'),('create_hoopla_module','2019-11-19 13:31:56'),('create_ils_modules','2019-11-19 13:31:54'),('create_open_archives_module','2019-11-19 13:32:02'),('create_overdrive_module','2019-11-19 13:31:55'),('create_rbdigital_module','2019-11-19 13:31:57'),('create_web_indexer_module','2019-11-19 13:32:03'),('cronLog','2019-01-28 20:59:01'),('default_library','2019-01-28 20:58:56'),('detailed_hold_notice_configuration','2019-01-28 20:58:56'),('disable_auto_correction_of_searches','2019-01-28 20:58:56'),('display_pika_logo','2019-01-28 20:58:56'),('dpla_integration','2019-01-28 20:58:56'),('eContentCheckout','2011-11-10 23:57:56'),('eContentCheckout_1','2011-12-13 16:04:03'),('eContentHistory','2011-11-15 17:56:44'),('eContentHolds','2011-11-10 22:39:20'),('eContentItem_1','2011-12-04 22:13:19'),('eContentRating','2011-11-16 21:53:43'),('eContentRecord_1','2011-12-01 21:43:54'),('eContentRecord_2','2012-01-11 20:06:48'),('eContentWishList','2011-12-08 20:29:48'),('econtent_attach','2011-12-30 19:12:22'),('econtent_locations_to_include','2019-01-28 20:56:58'),('econtent_marc_import','2011-12-15 22:48:22'),('editorial_review','2019-01-28 20:58:58'),('editorial_review_1','2019-01-28 20:58:58'),('editorial_review_2','2019-01-28 20:58:58'),('enable_archive','2019-01-28 20:58:56'),('error_table','2019-11-19 13:32:07'),('error_table_agent','2019-11-19 13:32:07'),('expiration_message','2019-01-28 20:58:56'),('explore_more_configuration','2019-01-28 20:58:56'),('externalLinkTracking','2019-01-28 20:58:58'),('external_materials_request','2019-01-28 20:58:56'),('facets_add_multi_select','2019-11-19 13:31:50'),('facets_add_translation','2019-11-19 13:31:50'),('facets_locking','2019-11-19 13:31:50'),('facets_remove_author_results','2019-11-19 13:31:50'),('facet_grouping_updates','2019-01-28 20:58:55'),('format_holdType','2019-11-19 13:31:54'),('format_status_maps','2019-11-19 13:31:53'),('format_status_suppression','2019-11-19 13:31:54'),('full_record_view_configuration_options','2019-01-28 20:58:56'),('genealogy','2019-01-28 20:58:58'),('genealogy_1','2019-01-28 20:58:58'),('genealogy_nashville_1','2019-01-28 20:58:58'),('goodreads_library_contact_link','2019-01-28 20:58:56'),('grouped_works','2019-01-28 20:58:56'),('grouped_works_1','2019-01-28 20:58:56'),('grouped_works_2','2019-01-28 20:58:56'),('grouped_works_partial_updates','2019-01-28 20:58:57'),('grouped_works_primary_identifiers','2019-01-28 20:58:56'),('grouped_works_primary_identifiers_1','2019-01-28 20:58:56'),('grouped_works_remove_split_titles','2019-01-28 20:58:56'),('grouped_work_duplicate_identifiers','2019-01-28 20:58:57'),('grouped_work_engine','2019-01-28 20:58:57'),('grouped_work_evoke','2019-01-28 20:58:57'),('grouped_work_identifiers_ref_indexing','2019-01-28 20:58:57'),('grouped_work_index_cleanup','2019-01-28 20:58:57'),('grouped_work_index_date_updated','2019-01-28 20:58:57'),('grouped_work_merging','2019-01-28 20:58:57'),('grouped_work_primary_identifiers_hoopla','2019-01-28 20:58:57'),('grouped_work_primary_identifier_types','2019-01-28 20:58:57'),('header_text','2019-01-28 20:58:56'),('holiday','2019-01-28 20:59:01'),('holiday_1','2019-01-28 20:59:01'),('hoopla_add_settings','2019-11-19 13:31:55'),('hoopla_exportLog','2019-01-28 20:58:57'),('hoopla_exportLog_skips','2019-11-19 13:31:55'),('hoopla_exportLog_update','2019-11-19 13:31:55'),('hoopla_exportTables','2019-01-28 20:58:57'),('hoopla_export_include_raw_data','2019-11-19 13:31:55'),('hoopla_filter_records_from_other_vendors','2019-11-19 13:31:56'),('hoopla_integration','2019-01-28 20:58:56'),('hoopla_library_options','2019-01-28 20:58:56'),('hoopla_library_options_remove','2019-01-28 20:58:56'),('hoopla_scoping','2019-11-19 13:31:56'),('horizontal_search_bar','2019-01-28 20:58:56'),('hours_and_locations_control','2019-01-28 20:58:55'),('ill_link','2019-01-28 20:58:56'),('ils_code_records_owned_length','2019-01-28 20:58:56'),('ils_exportLog','2019-11-19 13:31:52'),('ils_exportLog_skips','2019-11-19 13:31:53'),('ils_hold_summary','2019-01-28 20:58:57'),('ils_marc_checksums','2019-01-28 20:59:02'),('ils_marc_checksum_first_detected','2019-01-28 20:59:02'),('ils_marc_checksum_first_detected_signed','2019-01-28 20:59:02'),('ils_marc_checksum_source','2019-01-28 20:59:02'),('increase_ilsID_size_for_ils_marc_checksums','2019-01-28 20:58:57'),('increase_login_form_labels','2019-01-28 20:58:56'),('increase_search_url_size','2019-11-19 13:32:05'),('indexing_profile','2019-01-28 20:58:57'),('indexing_profile_add_continuous_update_fields','2019-11-19 13:31:53'),('indexing_profile_catalog_driver','2019-01-28 20:58:57'),('indexing_profile_collection','2019-01-28 20:58:57'),('indexing_profile_collectionsToSuppress','2019-01-28 20:58:57'),('indexing_profile_doAutomaticEcontentSuppression','2019-01-28 20:58:57'),('indexing_profile_dueDateFormat','2019-01-28 20:58:57'),('indexing_profile_extendLocationsToSuppress','2019-01-28 20:58:57'),('indexing_profile_filenames_to_include','2019-01-28 20:58:57'),('indexing_profile_folderCreation','2019-01-28 20:58:57'),('indexing_profile_groupUnchangedFiles','2019-01-28 20:58:57'),('indexing_profile_holdability','2019-01-28 20:58:57'),('indexing_profile_last_checkin_date','2019-01-28 20:58:57'),('indexing_profile_last_marc_export','2019-11-19 13:31:53'),('indexing_profile_marc_encoding','2019-01-28 20:58:57'),('indexing_profile_marc_record_subfield','2019-03-11 05:22:58'),('indexing_profile_specific_order_location','2019-01-28 20:58:57'),('indexing_profile_specified_formats','2019-11-19 13:31:52'),('indexing_profile_speicified_formats','2019-01-28 20:58:57'),('index_resources','2019-01-28 20:58:59'),('index_search_stats','2019-01-28 20:58:58'),('index_search_stats_counts','2019-01-28 20:58:58'),('index_subsets_of_overdrive','2019-01-28 20:58:56'),('initial_setup','2011-11-15 22:29:11'),('ip_lookup_1','2019-01-28 20:58:59'),('ip_lookup_2','2019-01-28 20:58:59'),('ip_lookup_3','2019-01-28 20:58:59'),('islandora_cover_cache','2019-01-28 20:58:57'),('islandora_driver_cache','2019-01-28 20:58:57'),('islandora_lat_long_cache','2019-01-28 20:58:57'),('islandora_samePika_cache','2019-01-28 20:58:57'),('languages_setup','2019-11-19 13:32:00'),('languages_show_for_translators','2019-11-19 13:32:01'),('last_check_in_status_adjustments','2019-01-28 20:58:57'),('lexile_branding','2019-01-28 20:58:56'),('libraryAdmin','2019-01-28 20:58:59'),('library_1','2019-01-28 20:56:57'),('library_10','2019-01-28 20:56:57'),('library_11','2019-01-28 20:56:57'),('library_12','2019-01-28 20:56:57'),('library_13','2019-01-28 20:56:57'),('library_14','2019-01-28 20:56:57'),('library_15','2019-01-28 20:56:57'),('library_16','2019-01-28 20:56:57'),('library_17','2019-01-28 20:56:57'),('library_18','2019-01-28 20:56:57'),('library_19','2019-01-28 20:56:57'),('library_2','2019-01-28 20:56:57'),('library_20','2019-01-28 20:56:57'),('library_21','2019-01-28 20:56:57'),('library_23','2019-01-28 20:56:57'),('library_24','2019-01-28 20:56:57'),('library_25','2019-01-28 20:56:57'),('library_26','2019-01-28 20:56:57'),('library_28','2019-01-28 20:56:57'),('library_29','2019-01-28 20:56:57'),('library_3','2019-01-28 20:56:57'),('library_30','2019-01-28 20:56:57'),('library_31','2019-01-28 20:56:57'),('library_32','2019-01-28 20:56:57'),('library_33','2019-01-28 20:56:57'),('library_34','2019-01-28 20:56:57'),('library_35_marmot','2019-01-28 20:56:57'),('library_35_nashville','2019-01-28 20:56:57'),('library_36_nashville','2019-01-28 20:56:57'),('library_4','2019-01-28 20:56:57'),('library_5','2019-01-28 20:56:57'),('library_6','2019-01-28 20:56:57'),('library_7','2019-01-28 20:56:57'),('library_8','2019-01-28 20:56:57'),('library_9','2019-01-28 20:56:57'),('library_add_oai_searching','2019-11-19 13:31:48'),('library_archive_material_requests','2019-01-28 20:58:56'),('library_archive_material_request_form_configurations','2019-01-28 20:58:56'),('library_archive_pid','2019-01-28 20:58:56'),('library_archive_related_objects_display_mode','2019-01-28 20:58:56'),('library_archive_request_customization','2019-01-28 20:58:56'),('library_archive_search_facets','2019-01-28 20:58:55'),('library_barcodes','2019-01-28 20:58:55'),('library_bookings','2019-01-28 20:58:55'),('library_cas_configuration','2019-01-28 20:58:56'),('library_claim_authorship_customization','2019-01-28 20:58:56'),('library_contact_links','2019-01-28 20:56:57'),('library_css','2019-01-28 20:56:57'),('library_eds_integration','2019-01-28 20:58:56'),('library_eds_search_integration','2019-01-28 20:58:56'),('library_expiration_warning','2019-01-28 20:56:57'),('library_facets','2019-01-28 20:58:55'),('library_facets_1','2019-01-28 20:58:55'),('library_facets_2','2019-01-28 20:58:55'),('library_fine_updates_paypal','2019-11-19 13:31:51'),('library_grouping','2019-01-28 20:56:57'),('library_ils_code_expansion','2019-01-28 20:56:57'),('library_ils_code_expansion_2','2019-01-28 20:56:58'),('library_indexes','2019-11-19 13:31:49'),('library_links','2019-01-28 20:56:57'),('library_links_display_options','2019-01-28 20:56:57'),('library_links_show_html','2019-01-28 20:56:57'),('library_location_availability_toggle_updates','2019-01-28 20:58:56'),('library_location_boosting','2019-01-28 20:56:57'),('library_location_cloud_library_scoping','2019-11-19 13:31:49'),('library_location_display_controls','2019-01-28 20:58:55'),('library_location_hoopla_scoping','2019-11-19 13:31:49'),('library_location_rbdigital_scoping','2019-11-19 13:31:49'),('library_location_repeat_online','2019-01-28 20:56:57'),('library_location_side_load_scoping','2019-11-19 13:31:54'),('library_materials_request_limits','2019-01-28 20:56:57'),('library_materials_request_new_request_summary','2019-01-28 20:56:57'),('library_max_fines_for_account_update','2019-01-28 20:58:56'),('library_on_order_counts','2019-01-28 20:58:56'),('library_order_information','2019-01-28 20:56:57'),('library_patronNameDisplayStyle','2019-01-28 20:58:56'),('library_pin_reset','2019-01-28 20:56:57'),('library_prevent_expired_card_login','2019-01-28 20:56:57'),('library_prompt_birth_date','2019-01-28 20:58:55'),('library_remove_gold_rush','2019-11-19 13:31:48'),('library_remove_unusedColumns','2019-11-19 13:31:48'),('library_remove_unusedDisplayOptions_3_18','2019-11-19 13:31:48'),('library_remove_unused_recordsToBlackList','2019-11-19 13:31:48'),('library_rename_prospector','2019-11-19 13:31:47'),('library_show_display_name','2019-01-28 20:58:55'),('library_show_quick_copy','2019-11-19 13:31:49'),('library_show_series_in_main_details','2019-01-28 20:58:56'),('library_sidebar_menu','2019-01-28 20:58:56'),('library_sidebar_menu_button_text','2019-01-28 20:58:56'),('library_subject_display','2019-01-28 20:58:56'),('library_subject_display_2','2019-01-28 20:58:56'),('library_top_links','2019-01-28 20:56:57'),('library_use_theme','2019-02-26 00:09:00'),('linked_accounts_switch','2019-01-28 20:58:56'),('listPublisherRole','2019-01-28 20:58:59'),('list_wdiget_list_update_1','2019-01-28 20:58:57'),('list_wdiget_update_1','2019-01-28 20:58:57'),('list_widgets','2019-01-28 20:58:57'),('list_widgets_home','2019-01-28 20:58:57'),('list_widgets_update_1','2019-01-28 20:58:57'),('list_widgets_update_2','2019-01-28 20:58:57'),('list_widget_num_results','2019-01-28 20:58:57'),('list_widget_style_update','2019-01-28 20:58:57'),('list_widget_update_2','2019-01-28 20:58:57'),('list_widget_update_3','2019-01-28 20:58:57'),('list_widget_update_4','2019-01-28 20:58:57'),('list_widget_update_5','2019-01-28 20:58:57'),('loan_rule_determiners_1','2019-01-28 20:59:01'),('loan_rule_determiners_increase_ptype_length','2019-01-28 20:59:01'),('localized_browse_categories','2019-01-28 20:59:02'),('location_1','2019-01-28 20:58:55'),('location_10','2019-01-28 20:58:56'),('location_2','2019-01-28 20:58:56'),('location_3','2019-01-28 20:58:56'),('location_4','2019-01-28 20:58:56'),('location_5','2019-01-28 20:58:56'),('location_6','2019-01-28 20:58:56'),('location_7','2019-01-28 20:58:56'),('location_8','2019-01-28 20:58:56'),('location_9','2019-01-28 20:58:56'),('location_additional_branches_to_show_in_facets','2019-01-28 20:58:56'),('location_address','2019-01-28 20:58:56'),('location_allow_multiple_open_hours_per_day','2019-11-19 13:31:49'),('location_facets','2019-01-28 20:58:55'),('location_facets_1','2019-01-28 20:58:55'),('location_hours','2019-01-28 20:59:01'),('location_include_library_records_to_include','2019-01-28 20:58:56'),('location_increase_code_column_size','2019-01-28 20:58:56'),('location_library_control_shelf_location_and_date_added_facets','2019-01-28 20:58:56'),('location_show_display_name','2019-01-28 20:58:56'),('location_subdomain','2019-01-28 20:58:56'),('location_sublocation','2019-01-28 20:58:56'),('location_sublocation_uniqueness','2019-01-28 20:58:56'),('login_form_labels','2019-01-28 20:58:56'),('logo_linking','2019-01-28 20:58:56'),('main_location_switch','2019-01-28 20:58:56'),('manageMaterialsRequestFieldsToDisplay','2019-01-28 20:58:59'),('marcImport','2019-01-28 20:59:01'),('marcImport_1','2019-01-28 20:59:01'),('marcImport_2','2019-01-28 20:59:01'),('marcImport_3','2019-01-28 20:59:01'),('masquerade_automatic_timeout_length','2019-01-28 20:58:56'),('masquerade_ptypes','2019-01-28 20:59:01'),('materialRequestsRole','2019-01-28 20:58:59'),('materialsRequest','2019-01-28 20:58:58'),('materialsRequestFixColumns','2019-01-28 20:58:59'),('materialsRequestFormats','2019-01-28 20:58:59'),('materialsRequestFormFields','2019-01-28 20:58:59'),('materialsRequestLibraryId','2019-01-28 20:58:59'),('materialsRequestStatus','2019-01-28 20:58:59'),('materialsRequestStatus_update1','2019-01-28 20:58:59'),('materialsRequest_update1','2019-01-28 20:58:58'),('materialsRequest_update2','2019-01-28 20:58:58'),('materialsRequest_update3','2019-01-28 20:58:58'),('materialsRequest_update4','2019-01-28 20:58:58'),('materialsRequest_update5','2019-01-28 20:58:58'),('materialsRequest_update6','2019-01-28 20:58:58'),('materialsRequest_update7','2019-01-28 20:58:59'),('materials_request_days_to_keep','2019-01-28 20:58:56'),('memory_index','2019-11-19 13:32:07'),('memory_table','2019-11-19 13:32:07'),('memory_table_size_increase','2019-11-19 13:32:07'),('merged_records','2019-01-28 20:58:59'),('millenniumTables','2019-01-28 20:59:01'),('modifyColumnSizes_1','2011-11-10 19:46:03'),('modules','2019-11-19 13:31:47'),('more_details_customization','2019-01-28 20:58:56'),('nearby_book_store','2019-01-28 20:59:01'),('newRolesJan2016','2019-01-28 20:58:59'),('new_search_stats','2019-01-28 20:58:58'),('nongrouped_records','2019-01-28 20:58:59'),('non_numeric_ptypes','2019-01-28 20:59:01'),('notices_1','2011-12-02 18:26:28'),('notInterested','2019-01-28 20:58:58'),('notInterestedWorks','2019-01-28 20:58:58'),('notInterestedWorksRemoveUserIndex','2019-01-28 20:58:58'),('novelist_data','2019-01-28 20:59:02'),('novelist_data_indexes','2019-11-19 13:32:03'),('novelist_data_json','2019-11-19 13:32:03'),('offline_circulation','2019-01-28 20:59:02'),('offline_holds','2019-01-28 20:59:02'),('offline_holds_update_1','2019-01-28 20:59:02'),('offline_holds_update_2','2019-01-28 20:59:02'),('open_archives_collection','2019-11-19 13:32:01'),('open_archives_collection_filtering','2019-11-19 13:32:01'),('open_archives_collection_subjects','2019-11-19 13:32:01'),('open_archives_loadOneMonthAtATime','2019-11-19 13:32:02'),('open_archives_log','2019-11-19 13:32:02'),('open_archives_record','2019-11-19 13:32:01'),('open_archive_tracking_adjustments','2019-11-19 13:32:02'),('overdrive_account_cache','2012-01-02 22:16:10'),('overdrive_add_settings','2019-11-19 13:31:54'),('overdrive_add_update_info_to_settings','2019-11-19 13:31:54'),('overdrive_api_data','2016-06-30 17:11:12'),('overdrive_api_data_availability_shared','2019-11-19 13:31:54'),('overdrive_api_data_availability_type','2016-06-30 17:11:12'),('overdrive_api_data_crossRefId','2019-01-28 21:27:59'),('overdrive_api_data_metadata_isOwnedByCollections','2019-01-28 21:27:59'),('overdrive_api_data_update_1','2016-06-30 17:11:12'),('overdrive_api_data_update_2','2016-06-30 17:11:12'),('overdrive_api_remove_old_tables','2019-11-19 13:31:54'),('overdrive_integration','2019-01-28 20:58:56'),('overdrive_integration_2','2019-01-28 20:58:56'),('overdrive_integration_3','2019-01-28 20:58:56'),('ptype','2019-01-28 20:59:01'),('pTypesForLibrary','2019-01-28 20:58:55'),('public_lists_to_include','2019-01-28 20:58:56'),('public_lists_to_include_defaults','2019-11-19 13:31:47'),('purchase_link_tracking','2019-01-28 20:58:58'),('rbdigital_add_settings','2019-11-19 13:31:56'),('rbdigital_availability','2019-03-06 15:43:14'),('rbdigital_exportLog','2019-03-05 05:46:15'),('rbdigital_exportLog_update','2019-11-19 13:31:56'),('rbdigital_exportTables','2019-03-05 16:31:43'),('rbdigital_magazine_export','2019-11-19 13:31:57'),('rbdigital_scoping','2019-11-19 13:31:57'),('readingHistory','2019-01-28 20:58:58'),('readingHistoryUpdate1','2019-01-28 20:58:58'),('readingHistory_deletion','2019-01-28 20:58:58'),('readingHistory_work','2019-01-28 20:58:58'),('recommendations_optOut','2019-01-28 20:58:58'),('records_to_include_2017-06','2019-01-28 20:58:57'),('records_to_include_2018-03','2019-01-28 20:58:57'),('record_grouping_log','2019-01-28 20:59:02'),('redwood_user_contribution','2019-11-19 13:32:02'),('reindexLog','2019-01-28 20:59:01'),('reindexLog_1','2019-01-28 20:59:01'),('reindexLog_2','2019-01-28 20:59:01'),('reindexLog_grouping','2019-01-28 20:59:01'),('remove_browse_tables','2019-01-28 20:59:02'),('remove_consortial_results_in_search','2019-01-28 20:58:56'),('remove_editorial_reviews','2019-11-19 13:32:03'),('remove_library_and location_boost','2019-11-19 13:32:03'),('remove_library_location_boosting','2019-11-19 13:31:47'),('remove_old_resource_tables','2019-01-28 20:59:02'),('remove_order_options','2019-01-28 20:58:56'),('remove_overdrive_api_data_needsUpdate','2019-11-19 13:31:54'),('remove_spelling_words','2019-11-19 13:32:03'),('remove_unused_enrichment_and_full_record_options','2019-01-28 20:58:56'),('remove_unused_location_options_2015_14_0','2019-01-28 20:58:56'),('remove_unused_options','2019-01-28 20:59:02'),('rename_tables','2019-01-28 20:59:01'),('resource_subject','2019-01-28 20:58:58'),('resource_update3','2019-01-28 20:58:58'),('resource_update4','2019-01-28 20:58:58'),('resource_update5','2019-01-28 20:58:58'),('resource_update6','2019-01-28 20:58:58'),('resource_update7','2019-01-28 20:58:58'),('resource_update8','2019-01-28 20:58:58'),('resource_update_table','2019-01-28 20:58:58'),('resource_update_table_2','2019-01-28 20:58:58'),('right_hand_sidebar','2019-01-28 20:58:56'),('roles_1','2019-01-28 20:58:56'),('roles_2','2019-01-28 20:58:56'),('saved_searches_created_default','2019-11-19 13:32:05'),('search_results_view_configuration_options','2019-01-28 20:58:56'),('search_sources','2019-01-28 20:58:56'),('search_sources_1','2019-01-28 20:58:56'),('selfreg_customization','2019-01-28 20:58:56'),('selfreg_template','2019-01-28 20:58:56'),('sendgrid_settings','2019-11-19 13:32:05'),('session_update_1','2019-01-28 20:59:02'),('setup_default_indexing_profiles','2019-01-28 20:58:57'),('show_catalog_options_in_profile','2019-01-28 20:58:56'),('show_grouped_hold_copies_count','2019-01-28 20:58:56'),('show_library_hours_notice_on_account_pages','2019-01-28 20:58:56'),('show_place_hold_on_unavailable','2019-01-28 20:58:56'),('show_Refresh_Account_Button','2019-01-28 20:58:56'),('sideloads','2019-11-19 13:31:54'),('sideload_log','2019-11-19 13:31:54'),('sideload_scoping','2019-11-19 13:31:54'),('sierra_exportLog','2019-01-28 20:58:57'),('sierra_exportLog_stats','2019-01-28 20:58:58'),('sierra_export_field_mapping','2019-01-28 20:58:58'),('sierra_export_field_mapping_item_fields','2019-01-28 20:58:58'),('slow_pages','2019-11-19 13:32:06'),('slow_page_granularity','2019-11-19 13:32:07'),('spelling_optimization','2019-01-28 20:59:01'),('staffSettingsTable','2019-01-28 20:58:59'),('sub-browse_categories','2019-01-28 20:59:02'),('syndetics_data','2019-01-28 20:59:02'),('syndetics_data_update_1','2019-11-19 13:32:03'),('themes_favicon','2019-11-19 13:31:58'),('themes_fonts','2019-11-19 13:32:00'),('themes_header_buttons','2019-11-19 13:31:58'),('themes_header_colors','2019-11-19 13:31:57'),('themes_header_colors_2','2019-11-19 13:31:57'),('themes_primary_colors','2019-11-19 13:31:59'),('themes_secondary_colors','2019-11-19 13:32:00'),('themes_setup','2019-02-24 20:32:34'),('theme_name_length','2019-01-28 20:58:56'),('track_cloud_library_record_usage','2019-11-19 13:32:02'),('track_cloud_library_user_usage','2019-11-19 13:32:02'),('track_hoopla_record_usage','2019-11-19 13:31:56'),('track_hoopla_user_usage','2019-11-19 13:31:55'),('track_ils_record_usage','2019-11-19 13:31:53'),('track_ils_user_usage','2019-11-19 13:31:53'),('track_open_archive_record_usage','2019-11-19 13:32:01'),('track_open_archive_user_usage','2019-11-19 13:32:01'),('track_overdrive_record_usage','2019-11-19 13:31:55'),('track_overdrive_user_usage','2019-11-19 13:31:55'),('track_rbdigital_magazine_usage','2019-11-19 13:31:56'),('track_rbdigital_record_usage','2019-11-19 13:31:56'),('track_rbdigital_user_usage','2019-11-19 13:31:56'),('track_sideload_record_usage','2019-11-19 13:31:54'),('track_sideload_user_usage','2019-11-19 13:31:54'),('track_website_user_usage','2019-11-19 13:32:03'),('translations','2019-11-19 13:32:01'),('translation_map_regex','2019-01-28 20:58:57'),('translation_terms','2019-11-19 13:32:01'),('translator_role','2019-11-19 13:32:01'),('userRatings1','2019-01-28 20:58:58'),('user_account','2019-01-28 20:58:56'),('user_add_rbdigital_id','2019-11-19 13:31:51'),('user_add_rbdigital_username_password','2019-11-19 13:31:51'),('user_display_name','2019-01-28 20:58:56'),('user_hoopla_confirmation_checkout','2019-01-28 20:58:56'),('user_hoopla_confirmation_checkout_prompt','2019-11-19 13:37:42'),('user_ilsType','2019-01-28 20:58:56'),('user_languages','2019-11-19 13:31:52'),('user_linking','2019-01-28 20:58:56'),('user_linking_1','2019-01-28 20:58:56'),('user_linking_disable_link','2019-11-19 13:31:51'),('user_link_blocking','2019-01-28 20:58:56'),('user_list_entry','2019-01-28 20:59:02'),('user_list_indexing','2019-01-28 20:59:02'),('user_list_sorting','2019-01-28 20:59:02'),('user_locked_filters','2019-11-19 13:31:52'),('user_messages','2019-11-19 13:31:52'),('user_message_actions','2019-11-19 13:31:52'),('user_overdrive_auto_checkout','2019-11-19 13:31:52'),('user_overdrive_email','2019-01-28 20:58:56'),('user_payments','2019-11-19 13:31:52'),('user_phone','2019-01-28 20:58:56'),('user_phone_length','2019-11-19 13:37:42'),('user_preference_review_prompt','2019-01-28 20:58:56'),('user_preferred_library_interface','2019-01-28 20:58:56'),('user_reading_history_index_source_id','2019-01-28 20:58:56'),('user_rememberHoldPickupLocation','2019-11-19 13:31:52'),('user_remove_default_created','2019-11-19 13:31:51'),('user_track_reading_history','2019-01-28 20:58:56'),('utf8_update','2016-06-30 17:11:12'),('variables_full_index_warnings','2019-01-28 20:58:59'),('variables_offline_mode_when_offline_login_allowed','2019-01-28 20:58:59'),('variables_table','2019-01-28 20:58:59'),('variables_table_uniqueness','2019-01-28 20:58:59'),('variables_validateChecksumsFromDisk','2019-01-28 20:58:59'),('volume_information','2019-01-28 20:58:57'),('website_indexing_tables','2019-11-19 13:32:03'),('website_record_usage','2019-11-19 13:32:03'),('work_level_ratings','2019-01-28 20:59:02'),('work_level_tagging','2019-01-28 20:59:02');
/*!40000 ALTER TABLE `db_update` ENABLE KEYS */;

--
-- Table structure for table `errors`
--

DROP TABLE IF EXISTS `errors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `errors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `url` text,
  `message` text,
  `backtrace` text,
  `timestamp` int(11) DEFAULT NULL,
  `userAgent` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `errors`
--

/*!40000 ALTER TABLE `errors` DISABLE KEYS */;
INSERT INTO `errors` VALUES (1,'MyAccount','AJAX','/MyAccount/AJAX?method=getMenuDataIls&activeModule=&activeAction=','Call to a member function getAccountSummary() on null','CatalogConnection->getAccountSummary  [978] - C:\\web\\aspen-discovery\\code\\web\\services\\MyAccount\\AJAX.php<br/>MyAccount_AJAX->getMenuDataIls  [21] - C:\\web\\aspen-discovery\\code\\web\\services\\MyAccount\\AJAX.php<br/>MyAccount_AJAX->launch  [634] - C:\\web\\aspen-discovery\\code\\web\\index.php<br/>',1574170329,'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:70.0) Gecko/20100101 Firefox/70.0'),(2,'MyAccount','AJAX','/MyAccount/AJAX?method=getMenuDataIls&activeModule=&activeAction=','Call to a member function getAccountSummary() on null','CatalogConnection->getAccountSummary  [978] - C:\\web\\aspen-discovery\\code\\web\\services\\MyAccount\\AJAX.php<br/>MyAccount_AJAX->getMenuDataIls  [21] - C:\\web\\aspen-discovery\\code\\web\\services\\MyAccount\\AJAX.php<br/>MyAccount_AJAX->launch  [634] - C:\\web\\aspen-discovery\\code\\web\\index.php<br/>',1574170779,'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:70.0) Gecko/20100101 Firefox/70.0');
/*!40000 ALTER TABLE `errors` ENABLE KEYS */;

--
-- Table structure for table `format_map_values`
--

DROP TABLE IF EXISTS `format_map_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `format_map_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `indexingProfileId` int(11) NOT NULL,
  `value` varchar(50) NOT NULL,
  `format` varchar(255) NOT NULL,
  `formatCategory` varchar(255) NOT NULL,
  `formatBoost` tinyint(4) NOT NULL,
  `suppress` tinyint(1) DEFAULT '0',
  `holdType` enum('bib','item','either','none') DEFAULT 'bib',
  PRIMARY KEY (`id`),
  UNIQUE KEY `indexingProfileId` (`indexingProfileId`,`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `format_map_values`
--

/*!40000 ALTER TABLE `format_map_values` DISABLE KEYS */;
/*!40000 ALTER TABLE `format_map_values` ENABLE KEYS */;

--
-- Table structure for table `grouped_work`
--

DROP TABLE IF EXISTS `grouped_work`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grouped_work` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `permanent_id` char(36) NOT NULL,
  `author` varchar(50) DEFAULT NULL,
  `grouping_category` varchar(25) NOT NULL,
  `full_title` varchar(276) NOT NULL,
  `date_updated` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permanent_id` (`permanent_id`),
  KEY `date_updated` (`date_updated`),
  KEY `date_updated_2` (`date_updated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grouped_work`
--

/*!40000 ALTER TABLE `grouped_work` DISABLE KEYS */;
/*!40000 ALTER TABLE `grouped_work` ENABLE KEYS */;

--
-- Table structure for table `grouped_work_primary_identifiers`
--

DROP TABLE IF EXISTS `grouped_work_primary_identifiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grouped_work_primary_identifiers` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `grouped_work_id` bigint(20) NOT NULL,
  `type` varchar(50) NOT NULL,
  `identifier` varchar(36) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `type` (`type`,`identifier`),
  KEY `grouped_record_id` (`grouped_work_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grouped_work_primary_identifiers`
--

/*!40000 ALTER TABLE `grouped_work_primary_identifiers` DISABLE KEYS */;
/*!40000 ALTER TABLE `grouped_work_primary_identifiers` ENABLE KEYS */;

--
-- Table structure for table `holiday`
--

DROP TABLE IF EXISTS `holiday`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `holiday` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of holiday',
  `libraryId` int(11) NOT NULL COMMENT 'The library system id',
  `date` date NOT NULL COMMENT 'Date of holiday',
  `name` varchar(100) NOT NULL COMMENT 'Name of holiday',
  PRIMARY KEY (`id`),
  UNIQUE KEY `LibraryDate` (`date`,`libraryId`),
  KEY `Library` (`libraryId`),
  KEY `Date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `holiday`
--

/*!40000 ALTER TABLE `holiday` DISABLE KEYS */;
/*!40000 ALTER TABLE `holiday` ENABLE KEYS */;

--
-- Table structure for table `hoopla_export`
--

DROP TABLE IF EXISTS `hoopla_export`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hoopla_export` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hooplaId` int(11) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `title` varchar(255) DEFAULT NULL,
  `kind` varchar(50) DEFAULT NULL,
  `pa` tinyint(4) NOT NULL DEFAULT '0',
  `demo` tinyint(4) NOT NULL DEFAULT '0',
  `profanity` tinyint(4) NOT NULL DEFAULT '0',
  `rating` varchar(10) DEFAULT NULL,
  `abridged` tinyint(4) NOT NULL DEFAULT '0',
  `children` tinyint(4) NOT NULL DEFAULT '0',
  `price` double NOT NULL DEFAULT '0',
  `rawChecksum` bigint(20) DEFAULT NULL,
  `rawResponse` mediumtext,
  `dateFirstDetected` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hooplaId` (`hooplaId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hoopla_export`
--

/*!40000 ALTER TABLE `hoopla_export` DISABLE KEYS */;
/*!40000 ALTER TABLE `hoopla_export` ENABLE KEYS */;

--
-- Table structure for table `hoopla_export_log`
--

DROP TABLE IF EXISTS `hoopla_export_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hoopla_export_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `notes` text COMMENT 'Additional information about the run',
  `numProducts` int(11) DEFAULT '0',
  `numErrors` int(11) DEFAULT '0',
  `numAdded` int(11) DEFAULT '0',
  `numDeleted` int(11) DEFAULT '0',
  `numUpdated` int(11) DEFAULT '0',
  `numSkipped` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hoopla_export_log`
--

/*!40000 ALTER TABLE `hoopla_export_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `hoopla_export_log` ENABLE KEYS */;

--
-- Table structure for table `hoopla_record_usage`
--

DROP TABLE IF EXISTS `hoopla_record_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hoopla_record_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hooplaId` int(11) DEFAULT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `timesCheckedOut` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `hooplaId` (`hooplaId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hoopla_record_usage`
--

/*!40000 ALTER TABLE `hoopla_record_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `hoopla_record_usage` ENABLE KEYS */;

--
-- Table structure for table `hoopla_scopes`
--

DROP TABLE IF EXISTS `hoopla_scopes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hoopla_scopes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `includeEBooks` tinyint(4) DEFAULT '1',
  `maxCostPerCheckoutEBooks` float DEFAULT '5',
  `includeEComics` tinyint(4) DEFAULT '1',
  `maxCostPerCheckoutEComics` float DEFAULT '5',
  `includeEAudiobook` tinyint(4) DEFAULT '1',
  `maxCostPerCheckoutEAudiobook` float DEFAULT '5',
  `includeMovies` tinyint(4) DEFAULT '1',
  `maxCostPerCheckoutMovies` float DEFAULT '5',
  `includeMusic` tinyint(4) DEFAULT '1',
  `maxCostPerCheckoutMusic` float DEFAULT '5',
  `includeTelevision` tinyint(4) DEFAULT '1',
  `maxCostPerCheckoutTelevision` float DEFAULT '5',
  `restrictToChildrensMaterial` tinyint(4) DEFAULT '0',
  `ratingsToExclude` varchar(100) DEFAULT NULL,
  `excludeAbridged` tinyint(4) DEFAULT '0',
  `excludeParentalAdvisory` tinyint(4) DEFAULT '0',
  `excludeProfanity` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hoopla_scopes`
--

/*!40000 ALTER TABLE `hoopla_scopes` DISABLE KEYS */;
/*!40000 ALTER TABLE `hoopla_scopes` ENABLE KEYS */;

--
-- Table structure for table `hoopla_settings`
--

DROP TABLE IF EXISTS `hoopla_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hoopla_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `apiUrl` varchar(255) DEFAULT NULL,
  `libraryId` int(11) DEFAULT '0',
  `apiUsername` varchar(50) DEFAULT NULL,
  `apiPassword` varchar(50) DEFAULT NULL,
  `runFullUpdate` tinyint(1) DEFAULT '0',
  `lastUpdateOfChangedRecords` int(11) DEFAULT '0',
  `lastUpdateOfAllRecords` int(11) DEFAULT '0',
  `excludeTitlesWithCopiesFromOtherVendors` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hoopla_settings`
--

/*!40000 ALTER TABLE `hoopla_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `hoopla_settings` ENABLE KEYS */;

--
-- Table structure for table `ils_extract_log`
--

DROP TABLE IF EXISTS `ils_extract_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ils_extract_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log',
  `indexingProfile` varchar(50) NOT NULL,
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `numProducts` int(11) DEFAULT '0',
  `numErrors` int(11) DEFAULT '0',
  `numAdded` int(11) DEFAULT '0',
  `numDeleted` int(11) DEFAULT '0',
  `numUpdated` int(11) DEFAULT '0',
  `notes` text COMMENT 'Additional information about the run',
  `numSkipped` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ils_extract_log`
--

/*!40000 ALTER TABLE `ils_extract_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `ils_extract_log` ENABLE KEYS */;

--
-- Table structure for table `ils_hold_summary`
--

DROP TABLE IF EXISTS `ils_hold_summary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ils_hold_summary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ilsId` varchar(20) NOT NULL,
  `numHolds` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ilsId` (`ilsId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ils_hold_summary`
--

/*!40000 ALTER TABLE `ils_hold_summary` DISABLE KEYS */;
/*!40000 ALTER TABLE `ils_hold_summary` ENABLE KEYS */;

--
-- Table structure for table `ils_marc_checksums`
--

DROP TABLE IF EXISTS `ils_marc_checksums`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ils_marc_checksums` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ilsId` varchar(50) NOT NULL,
  `checksum` bigint(20) unsigned NOT NULL,
  `dateFirstDetected` bigint(20) DEFAULT NULL,
  `source` varchar(50) NOT NULL DEFAULT 'ils',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ilsId` (`ilsId`),
  UNIQUE KEY `source` (`source`,`ilsId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ils_marc_checksums`
--

/*!40000 ALTER TABLE `ils_marc_checksums` DISABLE KEYS */;
/*!40000 ALTER TABLE `ils_marc_checksums` ENABLE KEYS */;

--
-- Table structure for table `ils_record_usage`
--

DROP TABLE IF EXISTS `ils_record_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ils_record_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `indexingProfileId` int(11) NOT NULL,
  `recordId` varchar(36) DEFAULT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `timesUsed` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `recordId` (`recordId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ils_record_usage`
--

/*!40000 ALTER TABLE `ils_record_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `ils_record_usage` ENABLE KEYS */;

--
-- Table structure for table `ils_volume_info`
--

DROP TABLE IF EXISTS `ils_volume_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ils_volume_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recordId` varchar(50) NOT NULL COMMENT 'Full Record ID including the source',
  `displayLabel` varchar(255) NOT NULL,
  `relatedItems` varchar(512) NOT NULL,
  `volumeId` varchar(30) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `volumeId` (`volumeId`),
  KEY `recordId` (`recordId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ils_volume_info`
--

/*!40000 ALTER TABLE `ils_volume_info` DISABLE KEYS */;
/*!40000 ALTER TABLE `ils_volume_info` ENABLE KEYS */;

--
-- Table structure for table `indexing_profiles`
--

DROP TABLE IF EXISTS `indexing_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `indexing_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `marcPath` varchar(100) NOT NULL,
  `marcEncoding` enum('MARC8','UTF8','UNIMARC','ISO8859_1','BESTGUESS') NOT NULL DEFAULT 'MARC8',
  `individualMarcPath` varchar(100) NOT NULL,
  `groupingClass` varchar(100) NOT NULL DEFAULT 'MarcRecordGrouper',
  `indexingClass` varchar(50) NOT NULL,
  `recordDriver` varchar(100) NOT NULL DEFAULT 'MarcRecord',
  `recordUrlComponent` varchar(25) NOT NULL DEFAULT 'Record',
  `formatSource` enum('bib','item','specified') NOT NULL DEFAULT 'bib',
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
  `locationsToSuppress` varchar(255) DEFAULT NULL,
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
  `collection` char(1) DEFAULT NULL,
  `catalogDriver` varchar(50) DEFAULT NULL,
  `nonHoldableITypes` varchar(255) DEFAULT NULL,
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
  `numCharsToCreateFolderFrom` int(11) DEFAULT '4',
  `createFolderFromLeadingCharacters` tinyint(1) DEFAULT '1',
  `dueDateFormat` varchar(20) DEFAULT 'yyMMdd',
  `doAutomaticEcontentSuppression` tinyint(1) DEFAULT '1',
  `groupUnchangedFiles` tinyint(1) DEFAULT '0',
  `iTypesToSuppress` varchar(100) DEFAULT NULL,
  `iCode2sToSuppress` varchar(100) DEFAULT NULL,
  `bCode3sToSuppress` varchar(100) DEFAULT NULL,
  `sierraRecordFixedFieldsTag` char(3) DEFAULT NULL,
  `bCode3` char(1) DEFAULT NULL,
  `recordNumberField` char(1) DEFAULT 'a',
  `recordNumberSubfield` char(1) DEFAULT 'a',
  `runFullUpdate` tinyint(1) DEFAULT '0',
  `lastUpdateOfChangedRecords` int(11) DEFAULT '0',
  `lastUpdateOfAllRecords` int(11) DEFAULT '0',
  `lastUpdateFromMarcExport` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `indexing_profiles`
--

/*!40000 ALTER TABLE `indexing_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `indexing_profiles` ENABLE KEYS */;

--
-- Table structure for table `ip_lookup`
--

DROP TABLE IF EXISTS `ip_lookup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ip_lookup` (
  `id` int(25) NOT NULL AUTO_INCREMENT,
  `locationid` int(5) NOT NULL,
  `location` varchar(255) NOT NULL,
  `ip` varchar(255) NOT NULL,
  `startIpVal` bigint(20) DEFAULT NULL,
  `endIpVal` bigint(20) DEFAULT NULL,
  `isOpac` tinyint(3) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `startIpVal` (`startIpVal`),
  KEY `endIpVal` (`endIpVal`),
  KEY `startIpVal_2` (`startIpVal`),
  KEY `endIpVal_2` (`endIpVal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ip_lookup`
--

/*!40000 ALTER TABLE `ip_lookup` DISABLE KEYS */;
/*!40000 ALTER TABLE `ip_lookup` ENABLE KEYS */;

--
-- Table structure for table `islandora_object_cache`
--

DROP TABLE IF EXISTS `islandora_object_cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `islandora_object_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` varchar(100) NOT NULL,
  `driverName` varchar(25) NOT NULL,
  `driverPath` varchar(100) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `hasLatLong` tinyint(4) DEFAULT NULL,
  `latitude` float DEFAULT NULL,
  `longitude` float DEFAULT NULL,
  `lastUpdate` int(11) DEFAULT '0',
  `smallCoverUrl` varchar(255) DEFAULT '',
  `mediumCoverUrl` varchar(255) DEFAULT '',
  `largeCoverUrl` varchar(255) DEFAULT '',
  `originalCoverUrl` varchar(255) DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `pid` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `islandora_object_cache`
--

/*!40000 ALTER TABLE `islandora_object_cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `islandora_object_cache` ENABLE KEYS */;

--
-- Table structure for table `islandora_samepika_cache`
--

DROP TABLE IF EXISTS `islandora_samepika_cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `islandora_samepika_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupedWorkId` char(36) NOT NULL,
  `pid` varchar(100) DEFAULT NULL,
  `archiveLink` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `groupedWorkId` (`groupedWorkId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `islandora_samepika_cache`
--

/*!40000 ALTER TABLE `islandora_samepika_cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `islandora_samepika_cache` ENABLE KEYS */;

--
-- Table structure for table `languages`
--

DROP TABLE IF EXISTS `languages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `languages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `weight` int(11) NOT NULL DEFAULT '0',
  `code` char(3) NOT NULL,
  `displayName` varchar(50) DEFAULT NULL,
  `displayNameEnglish` varchar(50) DEFAULT NULL,
  `facetValue` varchar(100) NOT NULL,
  `displayToTranslatorsOnly` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `languages`
--

/*!40000 ALTER TABLE `languages` DISABLE KEYS */;
INSERT INTO `languages` VALUES (1,0,'en','English','English','English',0);
/*!40000 ALTER TABLE `languages` ENABLE KEYS */;

--
-- Table structure for table `library`
--

DROP TABLE IF EXISTS `library`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `library` (
  `libraryId` int(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique id to identify the library within the system',
  `subdomain` varchar(25) NOT NULL COMMENT 'The subdomain which can be used to access settings for the library',
  `displayName` varchar(50) NOT NULL COMMENT 'The name of the library which should be shown in titles.',
  `themeName` varchar(60) DEFAULT NULL,
  `showLibraryFacet` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Whether or not the user can see and use the library facet to change to another branch in their library system.',
  `showConsortiumFacet` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether or not the user can see and use the consortium facet to change to other library systems. ',
  `allowInBranchHolds` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Whether or not the user can place holds for their branch.  If this isn''t shown, they won''t be able to place holds for books at the location they are in.  If set to false, they won''t be able to place any holds. ',
  `allowInLibraryHolds` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Whether or not the user can place holds for books at other locations in their library system',
  `allowConsortiumHolds` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether or not the user can place holds for any book anywhere in the consortium.  ',
  `scope` smallint(6) NOT NULL COMMENT 'The scope for the system in Millennium to refine holdings for the user.',
  `useScope` tinyint(4) NOT NULL COMMENT 'Whether or not the scope should be used when displaying holdings.  ',
  `hideCommentsWithBadWords` tinyint(4) NOT NULL COMMENT 'If set to true (1), any comments with bad words are completely removed from the user interface for everyone except the original poster.',
  `showStandardReviews` tinyint(4) NOT NULL COMMENT 'Whether or not reviews from Content Cafe/Syndetics are displayed on the full record page.',
  `showHoldButton` tinyint(4) NOT NULL COMMENT 'Whether or not the hold button is displayed so patrons can place holds on items',
  `showLoginButton` tinyint(4) NOT NULL COMMENT 'Whether or not the login button is displayed so patrons can login to the site',
  `showEmailThis` tinyint(4) NOT NULL COMMENT 'Whether or not the Email This link is shown',
  `showComments` tinyint(4) NOT NULL COMMENT 'Whether or not comments are shown (also disables adding comments)',
  `showFavorites` tinyint(4) NOT NULL COMMENT 'Whether or not uses can maintain favorites lists',
  `inSystemPickupsOnly` tinyint(4) NOT NULL COMMENT 'Restrict pickup locations to only locations within the library system which is active.',
  `defaultPType` int(11) NOT NULL,
  `facetLabel` varchar(50) NOT NULL,
  `finePaymentType` tinyint(1) DEFAULT NULL,
  `repeatSearchOption` enum('none','librarySystem','marmot','all') NOT NULL DEFAULT 'all' COMMENT 'Where to allow repeating search.  Valid options are: none, librarySystem, marmot, all',
  `repeatInProspector` tinyint(4) NOT NULL,
  `repeatInWorldCat` tinyint(4) NOT NULL,
  `systemsToRepeatIn` varchar(255) NOT NULL,
  `repeatInOverdrive` tinyint(4) NOT NULL DEFAULT '0',
  `overdriveAuthenticationILSName` varchar(45) DEFAULT NULL,
  `overdriveRequirePin` tinyint(1) NOT NULL DEFAULT '0',
  `homeLink` varchar(255) NOT NULL DEFAULT 'default',
  `showAdvancedSearchbox` tinyint(4) NOT NULL DEFAULT '1',
  `validPickupSystems` varchar(255) NOT NULL,
  `allowProfileUpdates` tinyint(4) NOT NULL DEFAULT '1',
  `allowRenewals` tinyint(4) NOT NULL DEFAULT '1',
  `allowFreezeHolds` tinyint(4) NOT NULL DEFAULT '0',
  `showItsHere` tinyint(4) NOT NULL DEFAULT '1',
  `holdDisclaimer` mediumtext,
  `showHoldCancelDate` tinyint(4) NOT NULL DEFAULT '0',
  `enableProspectorIntegration` tinyint(4) NOT NULL DEFAULT '0',
  `prospectorCode` varchar(10) NOT NULL DEFAULT '',
  `showRatings` tinyint(4) NOT NULL DEFAULT '1',
  `minimumFineAmount` float NOT NULL DEFAULT '0',
  `enableGenealogy` tinyint(4) NOT NULL DEFAULT '0',
  `enableCourseReserves` tinyint(1) NOT NULL DEFAULT '0',
  `exportOptions` varchar(100) NOT NULL DEFAULT 'RefWorks|EndNote',
  `enableSelfRegistration` tinyint(4) NOT NULL DEFAULT '0',
  `useHomeLinkInBreadcrumbs` tinyint(4) NOT NULL DEFAULT '0',
  `enableMaterialsRequest` tinyint(4) DEFAULT '1',
  `eContentLinkRules` varchar(512) DEFAULT '',
  `notesTabName` varchar(50) DEFAULT 'Notes',
  `showHoldButtonInSearchResults` tinyint(4) DEFAULT '1',
  `showSimilarAuthors` tinyint(4) DEFAULT '1',
  `showSimilarTitles` tinyint(4) DEFAULT '1',
  `show856LinksAsTab` tinyint(4) DEFAULT '0',
  `applyNumberOfHoldingsBoost` tinyint(4) DEFAULT '1',
  `worldCatUrl` varchar(100) DEFAULT '',
  `worldCatQt` varchar(40) DEFAULT '',
  `preferSyndeticsSummary` tinyint(4) DEFAULT '1',
  `showGoDeeper` tinyint(4) DEFAULT '1',
  `showProspectorResultsAtEndOfSearch` tinyint(4) DEFAULT '1',
  `overdriveAdvantageName` varchar(128) DEFAULT '',
  `overdriveAdvantageProductsKey` varchar(20) DEFAULT '',
  `defaultNotNeededAfterDays` int(11) DEFAULT '0',
  `showCheckInGrid` int(11) DEFAULT '1',
  `homeLinkText` varchar(50) DEFAULT 'Home',
  `showOtherFormatCategory` tinyint(1) DEFAULT '1',
  `showWikipediaContent` tinyint(1) DEFAULT '1',
  `payFinesLink` varchar(512) DEFAULT 'default',
  `payFinesLinkText` varchar(512) DEFAULT 'Click to Pay Fines Online',
  `eContentSupportAddress` varchar(256) DEFAULT '',
  `ilsCode` varchar(75) DEFAULT NULL,
  `systemMessage` varchar(512) DEFAULT '',
  `restrictSearchByLibrary` tinyint(1) DEFAULT '0',
  `enableOverdriveCollection` tinyint(1) DEFAULT '1',
  `includeOutOfSystemExternalLinks` tinyint(1) DEFAULT '0',
  `restrictOwningBranchesAndSystems` tinyint(1) DEFAULT '1',
  `showAvailableAtAnyLocation` tinyint(1) DEFAULT '1',
  `allowPatronAddressUpdates` tinyint(1) DEFAULT '1',
  `showWorkPhoneInProfile` tinyint(1) DEFAULT '0',
  `showNoticeTypeInProfile` tinyint(1) DEFAULT '0',
  `showPickupLocationInProfile` tinyint(1) DEFAULT '0',
  `accountingUnit` int(11) DEFAULT '10',
  `additionalCss` mediumtext,
  `allowPinReset` tinyint(1) DEFAULT NULL,
  `maxRequestsPerYear` int(11) DEFAULT '60',
  `maxOpenRequests` int(11) DEFAULT '5',
  `twitterLink` varchar(255) DEFAULT '',
  `youtubeLink` varchar(255) DEFAULT NULL,
  `instagramLink` varchar(255) DEFAULT NULL,
  `goodreadsLink` varchar(255) DEFAULT NULL,
  `facebookLink` varchar(255) DEFAULT '',
  `generalContactLink` varchar(255) DEFAULT '',
  `repeatInOnlineCollection` int(11) DEFAULT '1',
  `showExpirationWarnings` tinyint(1) DEFAULT '1',
  `econtentLocationsToInclude` varchar(255) DEFAULT NULL,
  `pTypes` varchar(255) DEFAULT NULL,
  `showLibraryHoursAndLocationsLink` int(11) DEFAULT '1',
  `showLibraryHoursNoticeOnAccountPages` tinyint(1) DEFAULT '1',
  `showShareOnExternalSites` int(11) DEFAULT '1',
  `showGoodReadsReviews` int(11) DEFAULT '1',
  `showStaffView` int(11) DEFAULT '1',
  `showSearchTools` int(11) DEFAULT '1',
  `barcodePrefix` varchar(15) DEFAULT '',
  `minBarcodeLength` int(11) DEFAULT '0',
  `maxBarcodeLength` int(11) DEFAULT '0',
  `showDisplayNameInHeader` tinyint(4) DEFAULT '0',
  `headerText` mediumtext,
  `promptForBirthDateInSelfReg` tinyint(4) DEFAULT '0',
  `availabilityToggleLabelSuperScope` varchar(50) DEFAULT 'Entire Collection',
  `availabilityToggleLabelLocal` varchar(50) DEFAULT '{display name}',
  `availabilityToggleLabelAvailable` varchar(50) DEFAULT 'Available Now',
  `loginFormUsernameLabel` varchar(100) DEFAULT 'Your Name',
  `loginFormPasswordLabel` varchar(100) DEFAULT 'Library Card Number',
  `showDetailedHoldNoticeInformation` tinyint(4) DEFAULT '1',
  `treatPrintNoticesAsPhoneNotices` tinyint(4) DEFAULT '0',
  `additionalLocationsToShowAvailabilityFor` varchar(255) NOT NULL DEFAULT '',
  `showInMainDetails` varchar(255) DEFAULT NULL,
  `includeDplaResults` tinyint(1) DEFAULT '0',
  `selfRegistrationFormMessage` text,
  `selfRegistrationSuccessMessage` text,
  `useHomeLinkForLogo` tinyint(1) DEFAULT '0',
  `addSMSIndicatorToPhone` tinyint(1) DEFAULT '0',
  `showAlternateLibraryOptionsInProfile` tinyint(1) DEFAULT '1',
  `selfRegistrationTemplate` varchar(25) DEFAULT 'default',
  `defaultBrowseMode` varchar(25) DEFAULT NULL,
  `externalMaterialsRequestUrl` varchar(255) DEFAULT NULL,
  `browseCategoryRatingsMode` varchar(25) DEFAULT NULL,
  `enableMaterialsBooking` tinyint(4) NOT NULL DEFAULT '0',
  `isDefault` tinyint(1) DEFAULT NULL,
  `showHoldButtonForUnavailableOnly` tinyint(1) DEFAULT '0',
  `allowLinkedAccounts` tinyint(1) DEFAULT '1',
  `allowAutomaticSearchReplacements` tinyint(1) DEFAULT '1',
  `includeOverDriveAdult` tinyint(1) DEFAULT '1',
  `includeOverDriveTeen` tinyint(1) DEFAULT '1',
  `includeOverDriveKids` tinyint(1) DEFAULT '1',
  `publicListsToInclude` tinyint(1) DEFAULT '4',
  `enableArchive` tinyint(1) DEFAULT '0',
  `showLCSubjects` tinyint(1) DEFAULT '1',
  `showBisacSubjects` tinyint(1) DEFAULT '1',
  `showFastAddSubjects` tinyint(1) DEFAULT '1',
  `showOtherSubjects` tinyint(1) DEFAULT '1',
  `maxFinesToAllowAccountUpdates` float DEFAULT '10',
  `showRefreshAccountButton` tinyint(4) NOT NULL DEFAULT '1',
  `edsApiProfile` varchar(50) DEFAULT NULL,
  `edsApiUsername` varchar(50) DEFAULT NULL,
  `edsApiPassword` varchar(50) DEFAULT NULL,
  `patronNameDisplayStyle` enum('firstinitial_lastname','lastinitial_firstname') DEFAULT 'firstinitial_lastname',
  `includeAllRecordsInShelvingFacets` tinyint(4) DEFAULT '0',
  `includeAllRecordsInDateAddedFacets` tinyint(4) DEFAULT '0',
  `archiveNamespace` varchar(30) DEFAULT NULL,
  `hideAllCollectionsFromOtherLibraries` tinyint(1) DEFAULT '0',
  `collectionsToHide` mediumtext,
  `preventExpiredCardLogin` tinyint(1) DEFAULT '0',
  `showInSearchResultsMainDetails` varchar(255) DEFAULT 'a:4:{i:0;s:10:"showSeries";i:1;s:13:"showPublisher";i:2;s:19:"showPublicationDate";i:3;s:13:"showLanguages";}',
  `alwaysShowSearchResultsMainDetails` tinyint(1) DEFAULT '0',
  `casHost` varchar(50) DEFAULT NULL,
  `casPort` smallint(6) DEFAULT NULL,
  `casContext` varchar(50) DEFAULT NULL,
  `showSidebarMenu` tinyint(4) DEFAULT '1',
  `sidebarMenuButtonText` varchar(40) DEFAULT 'Help',
  `allowRequestsForArchiveMaterials` tinyint(4) DEFAULT '0',
  `archiveRequestEmail` varchar(100) DEFAULT NULL,
  `archivePid` varchar(50) DEFAULT NULL,
  `availabilityToggleLabelAvailableOnline` varchar(50) DEFAULT '',
  `includeOnlineMaterialsInAvailableToggle` tinyint(1) DEFAULT '1',
  `archiveRequestMaterialsHeader` mediumtext,
  `masqueradeAutomaticTimeoutLength` tinyint(1) unsigned DEFAULT NULL,
  `allowMasqueradeMode` tinyint(1) DEFAULT '0',
  `allowReadingHistoryDisplayInMasqueradeMode` tinyint(1) DEFAULT '0',
  `newMaterialsRequestSummary` text,
  `claimAuthorshipHeader` mediumtext,
  `materialsRequestDaysToPreserve` int(11) DEFAULT '0',
  `archiveRequestFieldName` tinyint(1) DEFAULT NULL,
  `archiveRequestFieldAddress` tinyint(1) DEFAULT NULL,
  `archiveRequestFieldAddress2` tinyint(1) DEFAULT NULL,
  `archiveRequestFieldCity` tinyint(1) DEFAULT NULL,
  `archiveRequestFieldState` tinyint(1) DEFAULT NULL,
  `archiveRequestFieldZip` tinyint(1) DEFAULT NULL,
  `archiveRequestFieldCountry` tinyint(1) DEFAULT NULL,
  `archiveRequestFieldPhone` tinyint(1) DEFAULT NULL,
  `archiveRequestFieldAlternatePhone` tinyint(1) DEFAULT NULL,
  `archiveRequestFieldFormat` tinyint(1) DEFAULT NULL,
  `archiveRequestFieldPurpose` tinyint(1) DEFAULT NULL,
  `archiveMoreDetailsRelatedObjectsOrEntitiesDisplayMode` varchar(15) DEFAULT NULL,
  `objectsToHide` mediumtext,
  `defaultArchiveCollectionBrowseMode` varchar(25) DEFAULT NULL,
  `showGroupedHoldCopiesCount` tinyint(1) DEFAULT '1',
  `interLibraryLoanName` varchar(30) DEFAULT NULL,
  `interLibraryLoanUrl` varchar(100) DEFAULT NULL,
  `expirationNearMessage` mediumtext,
  `expiredMessage` mediumtext,
  `edsSearchProfile` varchar(50) DEFAULT NULL,
  `enableCombinedResults` tinyint(1) DEFAULT '0',
  `combinedResultsLabel` varchar(255) DEFAULT 'Combined Results',
  `defaultToCombinedResults` tinyint(1) DEFAULT '0',
  `hooplaLibraryID` int(10) unsigned DEFAULT NULL,
  `showOnOrderCounts` tinyint(1) DEFAULT '1',
  `sharedOverdriveCollection` tinyint(1) DEFAULT '-1',
  `showSeriesAsTab` tinyint(4) NOT NULL DEFAULT '0',
  `enableAlphaBrowse` tinyint(4) DEFAULT '1',
  `homePageWidgetId` varchar(50) DEFAULT '',
  `searchGroupedRecords` tinyint(4) DEFAULT '0',
  `showStandardSubjects` tinyint(1) DEFAULT '1',
  `theme` int(11) DEFAULT '1',
  `enableOpenArchives` tinyint(1) DEFAULT '0',
  `hooplaScopeId` int(11) DEFAULT '-1',
  `rbdigitalScopeId` int(11) DEFAULT '-1',
  `cloudLibraryScopeId` int(11) DEFAULT '-1',
  `showQuickCopy` tinyint(1) DEFAULT '1',
  `finesToPay` tinyint(1) DEFAULT '1',
  `payPalSandboxMode` tinyint(1) DEFAULT '1',
  `payPalClientId` varchar(80) DEFAULT NULL,
  `payPalClientSecret` varchar(80) DEFAULT NULL,

  PRIMARY KEY (`libraryId`),
  UNIQUE KEY `subdomain` (`subdomain`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library`
--

/*!40000 ALTER TABLE `library` DISABLE KEYS */;
INSERT INTO `library` VALUES (2,'main','Main Library','responsive',1,0,1,1,0,0,0,0,1,1,1,1,1,1,1,1,'',0,'none',0,0,'',0,'',0,'',1,'',1,1,1,1,'',0,0,'',1,0,0,0,'RefWorks|EndNote',0,0,0,'','Notes',1,1,1,0,1,'','',1,1,0,'','',-1,0,'Browse Catalog',1,1,'/MyAccount/Fines','Click to Pay Fines Online','','.*','',0,1,0,1,1,1,0,1,1,10,'',0,60,5,'','','','','','',0,1,'','1',1,1,1,1,1,1,'',6,14,0,'',0,'Entire Collection','','Available Now','Username','Password',1,1,'','a:9:{i:0;s:10:\"showSeries\";i:1;s:22:\"showPublicationDetails\";i:2;s:11:\"showFormats\";i:3;s:12:\"showEditions\";i:4;s:24:\"showPhysicalDescriptions\";i:5;s:9:\"showISBNs\";i:6;s:10:\"showArInfo\";i:7;s:14:\"showLexileInfo\";i:8;s:18:\"showFountasPinnell\";}',0,'','',1,0,1,'default','covers','','none',0,1,0,1,1,1,1,1,3,0,1,1,1,1,-1,0,'','','','lastinitial_firstname',0,0,'',0,'',0,'a:4:{i:0;s:10:\"showSeries\";i:1;s:13:\"showPublisher\";i:2;s:19:\"showPublicationDate\";i:3;s:13:\"showLanguages\";}',0,'',0,'',1,'Help',0,'','','Available Online',0,'',120,0,0,'','',365,2,1,1,1,1,1,1,2,1,1,2,'tiled','','covers',1,'Interlibrary Loan','','','','',0,'Combined Results',0,0,1,-1,0,1,'0',0,1,1,0,-1,-1,-1,1,1,1,NULL,NULL);
/*!40000 ALTER TABLE `library` ENABLE KEYS */;

--
-- Table structure for table `library_archive_explore_more_bar`
--

DROP TABLE IF EXISTS `library_archive_explore_more_bar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `library_archive_explore_more_bar` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `section` varchar(45) DEFAULT NULL,
  `displayName` varchar(45) DEFAULT NULL,
  `openByDefault` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `weight` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `LibraryIdIndex` (`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_archive_explore_more_bar`
--

/*!40000 ALTER TABLE `library_archive_explore_more_bar` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_archive_explore_more_bar` ENABLE KEYS */;

--
-- Table structure for table `library_archive_more_details`
--

DROP TABLE IF EXISTS `library_archive_more_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `library_archive_more_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL DEFAULT '-1',
  `weight` int(11) NOT NULL DEFAULT '0',
  `section` varchar(25) NOT NULL,
  `collapseByDefault` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `libraryId` (`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_archive_more_details`
--

/*!40000 ALTER TABLE `library_archive_more_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_archive_more_details` ENABLE KEYS */;

--
-- Table structure for table `library_archive_search_facet_setting`
--

DROP TABLE IF EXISTS `library_archive_search_facet_setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `library_archive_search_facet_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `displayName` varchar(50) NOT NULL,
  `facetName` varchar(80) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT '0',
  `numEntriesToShowByDefault` int(11) NOT NULL DEFAULT '5',
  `showAsDropDown` tinyint(4) NOT NULL DEFAULT '0',
  `sortMode` enum('alphabetically','num_results') NOT NULL DEFAULT 'num_results',
  `showAboveResults` tinyint(4) NOT NULL DEFAULT '0',
  `showInResults` tinyint(4) NOT NULL DEFAULT '1',
  `showInAdvancedSearch` tinyint(4) NOT NULL DEFAULT '1',
  `collapseByDefault` tinyint(4) DEFAULT '0',
  `useMoreFacetPopup` tinyint(4) DEFAULT '1',
  `multiSelect` tinyint(1) DEFAULT '0',
  `canLock` tinyint(1) DEFAULT '0',
  `translate` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `libraryFacet` (`libraryId`,`facetName`),
  KEY `libraryId` (`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_archive_search_facet_setting`
--

/*!40000 ALTER TABLE `library_archive_search_facet_setting` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_archive_search_facet_setting` ENABLE KEYS */;

--
-- Table structure for table `library_combined_results_section`
--

DROP TABLE IF EXISTS `library_combined_results_section`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `library_combined_results_section` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `displayName` varchar(255) DEFAULT NULL,
  `source` varchar(45) DEFAULT NULL,
  `numberOfResultsToShow` int(11) NOT NULL DEFAULT '5',
  `weight` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `LibraryIdIndex` (`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_combined_results_section`
--

/*!40000 ALTER TABLE `library_combined_results_section` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_combined_results_section` ENABLE KEYS */;

--
-- Table structure for table `library_facet_setting`
--

DROP TABLE IF EXISTS `library_facet_setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `library_facet_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `displayName` varchar(50) NOT NULL,
  `facetName` varchar(50) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT '0',
  `numEntriesToShowByDefault` int(11) NOT NULL DEFAULT '5',
  `showAsDropDown` tinyint(4) NOT NULL DEFAULT '0',
  `sortMode` enum('alphabetically','num_results') NOT NULL DEFAULT 'num_results',
  `showAboveResults` tinyint(4) NOT NULL DEFAULT '0',
  `showInResults` tinyint(4) NOT NULL DEFAULT '1',
  `showInAdvancedSearch` tinyint(4) NOT NULL DEFAULT '1',
  `collapseByDefault` tinyint(4) DEFAULT '0',
  `useMoreFacetPopup` tinyint(4) DEFAULT '1',
  `multiSelect` tinyint(1) DEFAULT '0',
  `canLock` tinyint(1) DEFAULT '0',
  `translate` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `libraryFacet` (`libraryId`,`facetName`),
  KEY `libraryId` (`libraryId`),
  KEY `libraryId_2` (`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='A widget that can be displayed within websites';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_facet_setting`
--

/*!40000 ALTER TABLE `library_facet_setting` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_facet_setting` ENABLE KEYS */;

--
-- Table structure for table `library_links`
--

DROP TABLE IF EXISTS `library_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `library_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `linkText` varchar(100) NOT NULL,
  `url` varchar(255) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT '0',
  `htmlContents` mediumtext,
  `showInAccount` tinyint(4) DEFAULT '0',
  `showInHelp` tinyint(4) DEFAULT '1',
  `showExpanded` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `libraryId` (`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_links`
--

/*!40000 ALTER TABLE `library_links` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_links` ENABLE KEYS */;

--
-- Table structure for table `library_more_details`
--

DROP TABLE IF EXISTS `library_more_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `library_more_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL DEFAULT '-1',
  `weight` int(11) NOT NULL DEFAULT '0',
  `source` varchar(25) NOT NULL,
  `collapseByDefault` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `libraryId` (`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_more_details`
--

/*!40000 ALTER TABLE `library_more_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_more_details` ENABLE KEYS */;

--
-- Table structure for table `library_records_owned`
--

DROP TABLE IF EXISTS `library_records_owned`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `library_records_owned` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `indexingProfileId` int(11) NOT NULL,
  `location` varchar(100) NOT NULL,
  `subLocation` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `libraryId` (`libraryId`),
  KEY `indexingProfileId` (`indexingProfileId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_records_owned`
--

/*!40000 ALTER TABLE `library_records_owned` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_records_owned` ENABLE KEYS */;

--
-- Table structure for table `library_records_to_include`
--

DROP TABLE IF EXISTS `library_records_to_include`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `library_records_to_include` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `indexingProfileId` int(11) NOT NULL,
  `location` varchar(100) NOT NULL,
  `subLocation` varchar(100) NOT NULL,
  `includeHoldableOnly` tinyint(4) NOT NULL DEFAULT '1',
  `includeItemsOnOrder` tinyint(1) NOT NULL DEFAULT '0',
  `includeEContent` tinyint(1) NOT NULL DEFAULT '0',
  `weight` int(11) NOT NULL,
  `iType` varchar(100) DEFAULT NULL,
  `audience` varchar(100) DEFAULT NULL,
  `format` varchar(100) DEFAULT NULL,
  `marcTagToMatch` varchar(100) DEFAULT NULL,
  `marcValueToMatch` varchar(100) DEFAULT NULL,
  `includeExcludeMatches` tinyint(4) DEFAULT '1',
  `urlToMatch` varchar(100) DEFAULT NULL,
  `urlReplacement` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `libraryId` (`libraryId`,`indexingProfileId`),
  KEY `indexingProfileId` (`indexingProfileId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_records_to_include`
--

/*!40000 ALTER TABLE `library_records_to_include` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_records_to_include` ENABLE KEYS */;

--
-- Table structure for table `library_search_source`
--

DROP TABLE IF EXISTS `library_search_source`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `library_search_source` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL DEFAULT '-1',
  `label` varchar(50) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT '0',
  `searchWhat` enum('catalog','genealogy','overdrive','worldcat','prospector','goldrush','title_browse','author_browse','subject_browse','tags') DEFAULT NULL,
  `defaultFilter` text,
  `defaultSort` enum('relevance','popularity','newest_to_oldest','oldest_to_newest','author','title','user_rating') DEFAULT NULL,
  `catalogScoping` enum('unscoped','library','location') DEFAULT 'unscoped',
  PRIMARY KEY (`id`),
  KEY `libraryId` (`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_search_source`
--

/*!40000 ALTER TABLE `library_search_source` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_search_source` ENABLE KEYS */;

--
-- Table structure for table `library_sideload_scopes`
--

DROP TABLE IF EXISTS `library_sideload_scopes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `library_sideload_scopes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `sideLoadScopeId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `libraryId` (`libraryId`,`sideLoadScopeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_sideload_scopes`
--

/*!40000 ALTER TABLE `library_sideload_scopes` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_sideload_scopes` ENABLE KEYS */;

--
-- Table structure for table `library_top_links`
--

DROP TABLE IF EXISTS `library_top_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `library_top_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `linkText` varchar(100) NOT NULL,
  `url` varchar(255) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `libraryId` (`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_top_links`
--

/*!40000 ALTER TABLE `library_top_links` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_top_links` ENABLE KEYS */;

--
-- Table structure for table `list_widget_lists`
--

DROP TABLE IF EXISTS `list_widget_lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `list_widget_lists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `listWidgetId` int(11) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT '0',
  `displayFor` enum('all','loggedIn','notLoggedIn') NOT NULL DEFAULT 'all',
  `name` varchar(50) NOT NULL,
  `source` varchar(500) NOT NULL,
  `fullListLink` varchar(500) DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `ListWidgetId` (`listWidgetId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='The lists that should appear within the widget';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `list_widget_lists`
--

/*!40000 ALTER TABLE `list_widget_lists` DISABLE KEYS */;
/*!40000 ALTER TABLE `list_widget_lists` ENABLE KEYS */;

--
-- Table structure for table `list_widgets`
--

DROP TABLE IF EXISTS `list_widgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `list_widgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text,
  `showTitleDescriptions` tinyint(4) DEFAULT '1',
  `onSelectCallback` varchar(255) DEFAULT '',
  `customCss` varchar(500) NOT NULL,
  `listDisplayType` enum('tabs','dropdown') NOT NULL DEFAULT 'tabs',
  `autoRotate` tinyint(4) NOT NULL DEFAULT '0',
  `showMultipleTitles` tinyint(4) NOT NULL DEFAULT '1',
  `libraryId` int(11) NOT NULL DEFAULT '-1',
  `style` enum('vertical','horizontal','single','single-with-next','text-list') NOT NULL DEFAULT 'horizontal',
  `coverSize` enum('small','medium') NOT NULL DEFAULT 'small',
  `showRatings` tinyint(4) NOT NULL DEFAULT '0',
  `showTitle` tinyint(4) NOT NULL DEFAULT '1',
  `showAuthor` tinyint(4) NOT NULL DEFAULT '1',
  `showViewMoreLink` tinyint(4) NOT NULL DEFAULT '0',
  `viewMoreLinkMode` enum('covers','list') NOT NULL DEFAULT 'list',
  `showListWidgetTitle` tinyint(4) NOT NULL DEFAULT '1',
  `numTitlesToShow` int(11) NOT NULL DEFAULT '25',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='A widget that can be displayed within Aspen Discovery or within other sites';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `list_widgets`
--

/*!40000 ALTER TABLE `list_widgets` DISABLE KEYS */;
/*!40000 ALTER TABLE `list_widgets` ENABLE KEYS */;

--
-- Table structure for table `loan_rule_determiners`
--

DROP TABLE IF EXISTS `loan_rule_determiners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `loan_rule_determiners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rowNumber` int(11) NOT NULL COMMENT 'The row of the determiner.  Rules are processed in reverse order',
  `location` varchar(10) NOT NULL,
  `patronType` varchar(255) NOT NULL COMMENT 'The patron types that this rule applies to',
  `itemType` varchar(255) NOT NULL DEFAULT '0' COMMENT 'The item types that this rule applies to',
  `ageRange` varchar(10) NOT NULL,
  `loanRuleId` varchar(10) NOT NULL COMMENT 'Close hour (24hr format) HH:MM',
  `active` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `rowNumber` (`rowNumber`),
  KEY `active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_rule_determiners`
--

/*!40000 ALTER TABLE `loan_rule_determiners` DISABLE KEYS */;
/*!40000 ALTER TABLE `loan_rule_determiners` ENABLE KEYS */;

--
-- Table structure for table `loan_rules`
--

DROP TABLE IF EXISTS `loan_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `loan_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loanRuleId` int(11) NOT NULL COMMENT 'The location id',
  `name` varchar(50) NOT NULL COMMENT 'The location code the rule applies to',
  `code` char(1) NOT NULL,
  `normalLoanPeriod` int(4) NOT NULL COMMENT 'Number of days the item checks out for',
  `holdable` tinyint(4) NOT NULL DEFAULT '0',
  `bookable` tinyint(4) NOT NULL DEFAULT '0',
  `homePickup` tinyint(4) NOT NULL DEFAULT '0',
  `shippable` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `loanRuleId` (`loanRuleId`),
  KEY `holdable` (`holdable`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_rules`
--

/*!40000 ALTER TABLE `loan_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `loan_rules` ENABLE KEYS */;

--
-- Table structure for table `location`
--

DROP TABLE IF EXISTS `location`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `location` (
  `locationId` int(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique Id for the branch or location within vuFind',
  `code` varchar(75) DEFAULT NULL,
  `displayName` varchar(60) NOT NULL COMMENT 'The full name of the location for display to the user',
  `libraryId` int(11) NOT NULL COMMENT 'A link to the library which the location belongs to',
  `validHoldPickupBranch` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Determines if the location can be used as a pickup location if it is not the patrons home location or the location they are in.',
  `nearbyLocation1` int(11) DEFAULT NULL COMMENT 'A secondary location which is nearby and could be used for pickup of materials.',
  `nearbyLocation2` int(11) DEFAULT NULL COMMENT 'A tertiary location which is nearby and could be used for pickup of materials.',
  `holdingBranchLabel` varchar(40) NOT NULL COMMENT 'The label used within the Holdings table in Millenium.',
  `scope` smallint(6) NOT NULL COMMENT 'The scope for the system in Millennium to refine holdings to the branch.  If there is no scope defined for the branch, this can be set to 0.',
  `useScope` tinyint(4) NOT NULL COMMENT 'Whether or not the scope should be used when displaying holdings.  ',
  `facetFile` varchar(15) NOT NULL DEFAULT 'default' COMMENT 'The name of the facet file which should be used while searching use default to not override the file',
  `showHoldButton` tinyint(4) NOT NULL COMMENT 'Whether or not the hold button is displayed so patrons can place holds on items',
  `isMainBranch` tinyint(1) DEFAULT '0',
  `showStandardReviews` tinyint(4) NOT NULL COMMENT 'Whether or not reviews from Content Cafe/Syndetics are displayed on the full record page.',
  `repeatSearchOption` enum('none','librarySystem','marmot','all') NOT NULL DEFAULT 'all' COMMENT 'Where to allow repeating search. Valid options are: none, librarySystem, marmot, all',
  `facetLabel` varchar(50) NOT NULL COMMENT 'The Facet value used to identify this system.  If this value is changed, system_map.properties must be updated as well and the catalog must be reindexed.',
  `repeatInProspector` tinyint(4) NOT NULL,
  `repeatInWorldCat` tinyint(4) NOT NULL,
  `systemsToRepeatIn` varchar(255) NOT NULL,
  `repeatInOverdrive` tinyint(4) NOT NULL DEFAULT '0',
  `homeLink` varchar(255) NOT NULL DEFAULT 'default',
  `defaultPType` int(11) NOT NULL DEFAULT '-1',
  `ptypesToAllowRenewals` varchar(128) NOT NULL DEFAULT '*',
  `automaticTimeoutLength` int(11) DEFAULT '90',
  `automaticTimeoutLengthLoggedOut` int(11) DEFAULT '450',
  `restrictSearchByLocation` tinyint(1) DEFAULT '0',
  `enableOverdriveCollection` tinyint(1) DEFAULT '1',
  `suppressHoldings` tinyint(1) DEFAULT '0',
  `additionalCss` mediumtext,
  `repeatInOnlineCollection` int(11) DEFAULT '1',
  `econtentLocationsToInclude` varchar(255) DEFAULT NULL,
  `showInLocationsAndHoursList` int(11) DEFAULT '1',
  `showShareOnExternalSites` int(11) DEFAULT '1',
  `showEmailThis` int(11) DEFAULT '1',
  `showFavorites` int(11) DEFAULT '1',
  `showComments` int(11) DEFAULT '1',
  `showGoodReadsReviews` int(11) DEFAULT '1',
  `showStaffView` int(11) DEFAULT '1',
  `address` mediumtext,
  `phone` varchar(15) DEFAULT '',
  `showDisplayNameInHeader` tinyint(4) DEFAULT '0',
  `headerText` mediumtext,
  `availabilityToggleLabelSuperScope` varchar(50) DEFAULT 'Entire Collection',
  `availabilityToggleLabelLocal` varchar(50) DEFAULT '{display name}',
  `availabilityToggleLabelAvailable` varchar(50) DEFAULT 'Available Now',
  `defaultBrowseMode` varchar(25) DEFAULT NULL,
  `browseCategoryRatingsMode` varchar(25) DEFAULT NULL,
  `subLocation` varchar(50) DEFAULT NULL,
  `includeOverDriveAdult` tinyint(1) DEFAULT '1',
  `includeOverDriveTeen` tinyint(1) DEFAULT '1',
  `includeOverDriveKids` tinyint(1) DEFAULT '1',
  `publicListsToInclude` tinyint(1) DEFAULT '6',
  `includeAllLibraryBranchesInFacets` tinyint(4) DEFAULT '1',
  `additionalLocationsToShowAvailabilityFor` varchar(100) NOT NULL DEFAULT '',
  `includeAllRecordsInShelvingFacets` tinyint(4) DEFAULT '0',
  `includeAllRecordsInDateAddedFacets` tinyint(4) DEFAULT '0',
  `availabilityToggleLabelAvailableOnline` varchar(50) DEFAULT '',
  `baseAvailabilityToggleOnLocalHoldingsOnly` tinyint(1) DEFAULT '0',
  `includeOnlineMaterialsInAvailableToggle` tinyint(1) DEFAULT '1',
  `subdomain` varchar(25) DEFAULT '',
  `includeLibraryRecordsToInclude` tinyint(1) DEFAULT '0',
  `useLibraryCombinedResultsSettings` tinyint(1) DEFAULT '1',
  `enableCombinedResults` tinyint(1) DEFAULT '0',
  `combinedResultsLabel` varchar(255) DEFAULT 'Combined Results',
  `defaultToCombinedResults` tinyint(1) DEFAULT '0',
  `footerTemplate` varchar(40) NOT NULL DEFAULT 'default',
  `homePageWidgetId` varchar(50) DEFAULT '',
  `theme` int(11) DEFAULT '1',
  `hooplaScopeId` int(11) DEFAULT '-1',
  `rbdigitalScopeId` int(11) DEFAULT '-1',
  `cloudLibraryScopeId` int(11) DEFAULT '-1',
  PRIMARY KEY (`locationId`),
  UNIQUE KEY `code` (`code`,`subLocation`),
  KEY `ValidHoldPickupBranch` (`validHoldPickupBranch`),
  KEY `libraryId` (`libraryId`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='Stores information about the various locations that are part';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `location`
--

/*!40000 ALTER TABLE `location` DISABLE KEYS */;
INSERT INTO `location` VALUES (1,'main','Main Library',2,1,-1,-1,'',0,0,'default',1,0,1,'marmot','',0,0,'',0,'',-1,'*',90,450,0,1,0,'',0,NULL,1,1,1,1,1,1,1,'','',0,'','Entire Collection','{display name}','Available Now','','','',1,1,1,0,1,'',0,0,'Available Online',0,0,'',1,1,0,'Combined Results',1,'default','',1,-1,-1,-1);
/*!40000 ALTER TABLE `location` ENABLE KEYS */;

--
-- Table structure for table `location_combined_results_section`
--

DROP TABLE IF EXISTS `location_combined_results_section`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `location_combined_results_section` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `locationId` int(11) NOT NULL,
  `displayName` varchar(255) DEFAULT NULL,
  `source` varchar(45) DEFAULT NULL,
  `numberOfResultsToShow` int(11) NOT NULL DEFAULT '5',
  `weight` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `LocationIdIndex` (`locationId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `location_combined_results_section`
--

/*!40000 ALTER TABLE `location_combined_results_section` DISABLE KEYS */;
/*!40000 ALTER TABLE `location_combined_results_section` ENABLE KEYS */;

--
-- Table structure for table `location_facet_setting`
--

DROP TABLE IF EXISTS `location_facet_setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `location_facet_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `locationId` int(11) NOT NULL,
  `displayName` varchar(50) NOT NULL,
  `facetName` varchar(50) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT '0',
  `numEntriesToShowByDefault` int(11) NOT NULL DEFAULT '5',
  `showAsDropDown` tinyint(4) NOT NULL DEFAULT '0',
  `sortMode` enum('alphabetically','num_results') NOT NULL DEFAULT 'num_results',
  `showAboveResults` tinyint(4) NOT NULL DEFAULT '0',
  `showInResults` tinyint(4) NOT NULL DEFAULT '1',
  `showInAdvancedSearch` tinyint(4) NOT NULL DEFAULT '1',
  `collapseByDefault` tinyint(4) DEFAULT '0',
  `useMoreFacetPopup` tinyint(4) DEFAULT '1',
  `multiSelect` tinyint(1) DEFAULT '0',
  `canLock` tinyint(1) DEFAULT '0',
  `translate` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `locationFacet` (`locationId`,`facetName`),
  KEY `locationId` (`locationId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='A widget that can be displayed within websites';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `location_facet_setting`
--

/*!40000 ALTER TABLE `location_facet_setting` DISABLE KEYS */;
/*!40000 ALTER TABLE `location_facet_setting` ENABLE KEYS */;

--
-- Table structure for table `location_hours`
--

DROP TABLE IF EXISTS `location_hours`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `location_hours` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of hours entry',
  `locationId` int(11) NOT NULL COMMENT 'The location id',
  `day` int(11) NOT NULL COMMENT 'Day of the week 0 to 7 (Sun to Monday)',
  `closed` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether or not the library is closed on this day',
  `open` varchar(10) NOT NULL COMMENT 'Open hour (24hr format) HH:MM',
  `close` varchar(10) NOT NULL COMMENT 'Close hour (24hr format) HH:MM',
  PRIMARY KEY (`id`),
  KEY `location` (`locationId`,`day`,`open`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `location_hours`
--

/*!40000 ALTER TABLE `location_hours` DISABLE KEYS */;
/*!40000 ALTER TABLE `location_hours` ENABLE KEYS */;

--
-- Table structure for table `location_more_details`
--

DROP TABLE IF EXISTS `location_more_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `location_more_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `locationId` int(11) NOT NULL DEFAULT '-1',
  `weight` int(11) NOT NULL DEFAULT '0',
  `source` varchar(25) NOT NULL,
  `collapseByDefault` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `locationId` (`locationId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `location_more_details`
--

/*!40000 ALTER TABLE `location_more_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `location_more_details` ENABLE KEYS */;

--
-- Table structure for table `location_records_owned`
--

DROP TABLE IF EXISTS `location_records_owned`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `location_records_owned` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `locationId` int(11) NOT NULL,
  `indexingProfileId` int(11) NOT NULL,
  `location` varchar(100) NOT NULL,
  `subLocation` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `locationId` (`locationId`),
  KEY `indexingProfileId` (`indexingProfileId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `location_records_owned`
--

/*!40000 ALTER TABLE `location_records_owned` DISABLE KEYS */;
/*!40000 ALTER TABLE `location_records_owned` ENABLE KEYS */;

--
-- Table structure for table `location_records_to_include`
--

DROP TABLE IF EXISTS `location_records_to_include`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `location_records_to_include` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `locationId` int(11) NOT NULL,
  `indexingProfileId` int(11) NOT NULL,
  `location` varchar(100) NOT NULL,
  `subLocation` varchar(100) NOT NULL,
  `includeHoldableOnly` tinyint(4) NOT NULL DEFAULT '1',
  `includeItemsOnOrder` tinyint(1) NOT NULL DEFAULT '0',
  `includeEContent` tinyint(1) NOT NULL DEFAULT '0',
  `weight` int(11) NOT NULL,
  `iType` varchar(100) DEFAULT NULL,
  `audience` varchar(100) DEFAULT NULL,
  `format` varchar(100) DEFAULT NULL,
  `marcTagToMatch` varchar(100) DEFAULT NULL,
  `marcValueToMatch` varchar(100) DEFAULT NULL,
  `includeExcludeMatches` tinyint(4) DEFAULT '1',
  `urlToMatch` varchar(100) DEFAULT NULL,
  `urlReplacement` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `locationId` (`locationId`,`indexingProfileId`),
  KEY `indexingProfileId` (`indexingProfileId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `location_records_to_include`
--

/*!40000 ALTER TABLE `location_records_to_include` DISABLE KEYS */;
/*!40000 ALTER TABLE `location_records_to_include` ENABLE KEYS */;

--
-- Table structure for table `location_search_source`
--

DROP TABLE IF EXISTS `location_search_source`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `location_search_source` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `locationId` int(11) NOT NULL DEFAULT '-1',
  `label` varchar(50) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT '0',
  `searchWhat` enum('catalog','genealogy','overdrive','worldcat','prospector','goldrush','title_browse','author_browse','subject_browse','tags') DEFAULT NULL,
  `defaultFilter` text,
  `defaultSort` enum('relevance','popularity','newest_to_oldest','oldest_to_newest','author','title','user_rating') DEFAULT NULL,
  `catalogScoping` enum('unscoped','library','location') DEFAULT 'unscoped',
  PRIMARY KEY (`id`),
  KEY `locationId` (`locationId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `location_search_source`
--

/*!40000 ALTER TABLE `location_search_source` DISABLE KEYS */;
/*!40000 ALTER TABLE `location_search_source` ENABLE KEYS */;

--
-- Table structure for table `location_sideload_scopes`
--

DROP TABLE IF EXISTS `location_sideload_scopes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `location_sideload_scopes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `locationId` int(11) NOT NULL,
  `sideLoadScopeId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `locationId` (`locationId`,`sideLoadScopeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `location_sideload_scopes`
--

/*!40000 ALTER TABLE `location_sideload_scopes` DISABLE KEYS */;
/*!40000 ALTER TABLE `location_sideload_scopes` ENABLE KEYS */;

--
-- Table structure for table `marriage`
--

DROP TABLE IF EXISTS `marriage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `marriage` (
  `marriageId` int(11) NOT NULL AUTO_INCREMENT,
  `personId` int(11) NOT NULL COMMENT 'A link to one person in the marriage',
  `spouseName` varchar(200) DEFAULT NULL COMMENT 'The name of the other person in the marriage if they aren''t in the database',
  `spouseId` int(11) DEFAULT NULL COMMENT 'A link to the second person in the marriage if the person is in the database',
  `marriageDate` date DEFAULT NULL COMMENT 'The date of the marriage if known.',
  `comments` mediumtext,
  PRIMARY KEY (`marriageId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Information about a marriage between two people';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marriage`
--

/*!40000 ALTER TABLE `marriage` DISABLE KEYS */;
/*!40000 ALTER TABLE `marriage` ENABLE KEYS */;

--
-- Table structure for table `materials_request`
--

DROP TABLE IF EXISTS `materials_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `about` text,
  `comments` text,
  `status` int(11) DEFAULT NULL,
  `dateCreated` int(11) DEFAULT NULL,
  `createdBy` int(11) DEFAULT NULL,
  `dateUpdated` int(11) DEFAULT NULL,
  `emailSent` tinyint(4) NOT NULL DEFAULT '0',
  `holdsCreated` tinyint(4) NOT NULL DEFAULT '0',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materials_request`
--

/*!40000 ALTER TABLE `materials_request` DISABLE KEYS */;
/*!40000 ALTER TABLE `materials_request` ENABLE KEYS */;

--
-- Table structure for table `materials_request_fields_to_display`
--

DROP TABLE IF EXISTS `materials_request_fields_to_display`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `materials_request_fields_to_display` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `columnNameToDisplay` varchar(30) NOT NULL,
  `labelForColumnToDisplay` varchar(45) NOT NULL,
  `weight` smallint(2) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `columnNameToDisplay` (`columnNameToDisplay`,`libraryId`),
  KEY `libraryId` (`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materials_request_fields_to_display`
--

/*!40000 ALTER TABLE `materials_request_fields_to_display` DISABLE KEYS */;
/*!40000 ALTER TABLE `materials_request_fields_to_display` ENABLE KEYS */;

--
-- Table structure for table `materials_request_form_fields`
--

DROP TABLE IF EXISTS `materials_request_form_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `materials_request_form_fields` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `libraryId` int(10) unsigned NOT NULL,
  `formCategory` varchar(55) NOT NULL,
  `fieldLabel` varchar(255) NOT NULL,
  `fieldType` varchar(30) DEFAULT NULL,
  `weight` smallint(2) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  KEY `libraryId` (`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materials_request_form_fields`
--

/*!40000 ALTER TABLE `materials_request_form_fields` DISABLE KEYS */;
/*!40000 ALTER TABLE `materials_request_form_fields` ENABLE KEYS */;

--
-- Table structure for table `materials_request_formats`
--

DROP TABLE IF EXISTS `materials_request_formats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `materials_request_formats` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `libraryId` int(10) unsigned NOT NULL,
  `format` varchar(30) NOT NULL,
  `formatLabel` varchar(60) NOT NULL,
  `authorLabel` varchar(45) NOT NULL,
  `weight` smallint(2) unsigned NOT NULL DEFAULT '0',
  `specialFields` set('Abridged/Unabridged','Article Field','Eaudio format','Ebook format','Season') DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `libraryId` (`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materials_request_formats`
--

/*!40000 ALTER TABLE `materials_request_formats` DISABLE KEYS */;
/*!40000 ALTER TABLE `materials_request_formats` ENABLE KEYS */;

--
-- Table structure for table `materials_request_status`
--

DROP TABLE IF EXISTS `materials_request_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `materials_request_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(80) DEFAULT NULL,
  `isDefault` tinyint(4) DEFAULT '0',
  `sendEmailToPatron` tinyint(4) DEFAULT NULL,
  `emailTemplate` text,
  `isOpen` tinyint(4) DEFAULT NULL,
  `isPatronCancel` tinyint(4) DEFAULT NULL,
  `libraryId` int(11) DEFAULT '-1',
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
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materials_request_status`
--

/*!40000 ALTER TABLE `materials_request_status` DISABLE KEYS */;
INSERT INTO `materials_request_status` VALUES (1,'Request Pending',1,0,'',1,0,-1),(2,'Already owned/On order',0,1,'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The Library already owns this item or it is already on order. Please access our catalog to place this item on hold.  Please check our online catalog periodically to put a hold for this item.',0,0,-1),(3,'Item purchased',0,1,'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. Outcome: The library is purchasing the item you requested. Please check our online catalog periodically to put yourself on hold for this item. We anticipate that this item will be available soon for you to place a hold.',0,0,-1),(4,'Referred to Collection Development - Adult',0,0,'',1,0,-1),(5,'Referred to Collection Development - J/YA',0,0,'',1,0,-1),(6,'Referred to Collection Development - AV',0,0,'',1,0,-1),(7,'ILL Under Review',0,0,'',1,0,-1),(8,'Request Referred to ILL',0,1,'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The library\'s Interlibrary loan department is reviewing your request. We will attempt to borrow this item from another system. This process generally takes about 2 - 6 weeks.',1,0,-1),(9,'Request Filled by ILL',0,1,'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. Our Interlibrary Loan Department is set to borrow this item from another library.',0,0,-1),(10,'Ineligible ILL',0,1,'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. Your library account is not eligible for interlibrary loan at this time.',0,0,-1),(11,'Not enough info - please contact Collection Development to clarify',0,1,'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We need more specific information in order to locate the exact item you need. Please re-submit your request with more details.',1,0,-1),(12,'Unable to acquire the item - out of print',0,1,'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is out of print.',0,0,-1),(13,'Unable to acquire the item - not available in the US',0,1,'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is not available in the US.',0,0,-1),(14,'Unable to acquire the item - not available from vendor',0,1,'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is not available from a preferred vendor.',0,0,-1),(15,'Unable to acquire the item - not published',0,1,'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The item you requested has not yet been published. Please check our catalog when the publication date draws near.',0,0,-1),(16,'Unable to acquire the item - price',0,1,'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item does not fit our collection guidelines.',0,0,-1),(17,'Unable to acquire the item - publication date',0,1,'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item does not fit our collection guidelines.',0,0,-1),(18,'Unavailable',0,1,'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The item you requested cannot be purchased at this time from any of our regular suppliers and is not available from any of our lending libraries.',0,0,-1),(19,'Cancelled by Patron',0,0,'',0,1,-1),(20,'Cancelled - Duplicate Request',0,0,'',0,0,-1);
/*!40000 ALTER TABLE `materials_request_status` ENABLE KEYS */;

--
-- Table structure for table `merged_grouped_works`
--

DROP TABLE IF EXISTS `merged_grouped_works`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `merged_grouped_works` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `sourceGroupedWorkId` char(36) NOT NULL,
  `destinationGroupedWorkId` char(36) NOT NULL,
  `notes` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `sourceGroupedWorkId` (`sourceGroupedWorkId`,`destinationGroupedWorkId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `merged_grouped_works`
--

/*!40000 ALTER TABLE `merged_grouped_works` DISABLE KEYS */;
/*!40000 ALTER TABLE `merged_grouped_works` ENABLE KEYS */;

--
-- Table structure for table `millennium_cache`
--

DROP TABLE IF EXISTS `millennium_cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `millennium_cache` (
  `recordId` varchar(20) NOT NULL COMMENT 'The recordId being checked',
  `scope` int(16) NOT NULL COMMENT 'The scope that was loaded',
  `holdingsInfo` longtext NOT NULL COMMENT 'Raw HTML returned from Millennium for holdings',
  `framesetInfo` longtext NOT NULL COMMENT 'Raw HTML returned from Millennium on the frameset page',
  `cacheDate` int(16) NOT NULL COMMENT 'When the entry was recorded in the cache',
  PRIMARY KEY (`recordId`,`scope`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Caches information from Millennium so we do not have to cont';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `millennium_cache`
--

/*!40000 ALTER TABLE `millennium_cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `millennium_cache` ENABLE KEYS */;

--
-- Table structure for table `modules`
--

DROP TABLE IF EXISTS `modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `enabled` tinyint(1) DEFAULT '0',
  `indexName` varchar(50) DEFAULT '',
  `backgroundProcess` varchar(50) DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `enabled` (`enabled`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modules`
--

/*!40000 ALTER TABLE `modules` DISABLE KEYS */;
INSERT INTO `modules` VALUES (1,'Koha',0,'grouped_works','koha_export'),(2,'CARL.X',0,'grouped_works','carlx_export'),(3,'Sierra',0,'grouped_works','sierra_export'),(4,'Horizon',0,'grouped_works','horizon_export'),(5,'Symphony',0,'grouped_works','symphony_export'),(6,'Side Loads',0,'grouped_works','sideload_processing'),(7,'User Lists',1,'lists','user_list_indexer'),(8,'OverDrive',0,'grouped_works','overdrive_extract'),(9,'Hoopla',0,'grouped_works','hoopla_export'),(10,'RBdigital',0,'grouped_works','rbdigital_export'),(11,'Open Archives',0,'open_archives',''),(12,'Cloud Library',0,'grouped_works','cloud_library_export'),(13,'Web Indexer',0,'website_pages','web_indexer');
/*!40000 ALTER TABLE `modules` ENABLE KEYS */;

--
-- Table structure for table `non_holdable_locations`
--

DROP TABLE IF EXISTS `non_holdable_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `non_holdable_locations` (
  `locationId` int(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique id for the non holdable location',
  `millenniumCode` varchar(5) NOT NULL COMMENT 'The internal 5 letter code within Millennium',
  `holdingDisplay` varchar(30) NOT NULL COMMENT 'The text displayed in the holdings list within Millennium',
  `availableAtCircDesk` tinyint(4) NOT NULL COMMENT 'The item is available if the patron visits the circulation desk.',
  PRIMARY KEY (`locationId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `non_holdable_locations`
--

/*!40000 ALTER TABLE `non_holdable_locations` DISABLE KEYS */;
/*!40000 ALTER TABLE `non_holdable_locations` ENABLE KEYS */;

--
-- Table structure for table `nongrouped_records`
--

DROP TABLE IF EXISTS `nongrouped_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nongrouped_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source` varchar(50) NOT NULL,
  `recordId` varchar(36) NOT NULL,
  `notes` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `source` (`source`,`recordId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nongrouped_records`
--

/*!40000 ALTER TABLE `nongrouped_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `nongrouped_records` ENABLE KEYS */;

--
-- Table structure for table `novelist_data`
--

DROP TABLE IF EXISTS `novelist_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `novelist_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupedRecordPermanentId` varchar(36) DEFAULT NULL,
  `lastUpdate` int(11) DEFAULT NULL,
  `hasNovelistData` tinyint(1) DEFAULT NULL,
  `groupedRecordHasISBN` tinyint(1) DEFAULT NULL,
  `primaryISBN` varchar(13) DEFAULT NULL,
  `seriesTitle` varchar(255) DEFAULT NULL,
  `seriesNote` varchar(255) DEFAULT NULL,
  `volume` varchar(32) DEFAULT NULL,
  `jsonResponse` mediumtext,
  PRIMARY KEY (`id`),
  KEY `groupedRecordPermanentId` (`groupedRecordPermanentId`),
  KEY `primaryISBN` (`primaryISBN`),
  KEY `series` (`seriesTitle`,`volume`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `novelist_data`
--

/*!40000 ALTER TABLE `novelist_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `novelist_data` ENABLE KEYS */;

--
-- Table structure for table `obituary`
--

DROP TABLE IF EXISTS `obituary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `obituary` (
  `obituaryId` int(11) NOT NULL AUTO_INCREMENT,
  `personId` int(11) NOT NULL COMMENT 'The person this obituary is for',
  `source` varchar(255) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `sourcePage` varchar(25) DEFAULT NULL,
  `contents` mediumtext,
  `picture` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`obituaryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Information about an obituary for a person';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `obituary`
--

/*!40000 ALTER TABLE `obituary` DISABLE KEYS */;
/*!40000 ALTER TABLE `obituary` ENABLE KEYS */;

--
-- Table structure for table `offline_circulation`
--

DROP TABLE IF EXISTS `offline_circulation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `offline_circulation`
--

/*!40000 ALTER TABLE `offline_circulation` DISABLE KEYS */;
/*!40000 ALTER TABLE `offline_circulation` ENABLE KEYS */;

--
-- Table structure for table `offline_hold`
--

DROP TABLE IF EXISTS `offline_hold`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `offline_hold`
--

/*!40000 ALTER TABLE `offline_hold` DISABLE KEYS */;
/*!40000 ALTER TABLE `offline_hold` ENABLE KEYS */;

--
-- Table structure for table `open_archives_collection`
--

DROP TABLE IF EXISTS `open_archives_collection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `open_archives_collection` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `baseUrl` varchar(255) NOT NULL,
  `setName` varchar(100) NOT NULL,
  `fetchFrequency` enum('hourly','daily','weekly','monthly','yearly','once') DEFAULT NULL,
  `lastFetched` int(11) DEFAULT NULL,
  `subjectFilters` mediumtext,
  `subjects` mediumtext,
  `loadOneMonthAtATime` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `open_archives_collection`
--

/*!40000 ALTER TABLE `open_archives_collection` DISABLE KEYS */;
/*!40000 ALTER TABLE `open_archives_collection` ENABLE KEYS */;

--
-- Table structure for table `open_archives_export_log`
--

DROP TABLE IF EXISTS `open_archives_export_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `open_archives_export_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `notes` text COMMENT 'Additional information about the run',
  `collectionName` mediumtext,
  `numRecords` int(11) DEFAULT '0',
  `numErrors` int(11) DEFAULT '0',
  `numAdded` int(11) DEFAULT '0',
  `numDeleted` int(11) DEFAULT '0',
  `numUpdated` int(11) DEFAULT '0',
  `numSkipped` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `open_archives_export_log`
--

/*!40000 ALTER TABLE `open_archives_export_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `open_archives_export_log` ENABLE KEYS */;

--
-- Table structure for table `open_archives_record`
--

DROP TABLE IF EXISTS `open_archives_record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `open_archives_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sourceCollection` int(11) NOT NULL,
  `permanentUrl` varchar(512) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sourceCollection` (`sourceCollection`,`permanentUrl`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `open_archives_record`
--

/*!40000 ALTER TABLE `open_archives_record` DISABLE KEYS */;
/*!40000 ALTER TABLE `open_archives_record` ENABLE KEYS */;

--
-- Table structure for table `open_archives_record_usage`
--

DROP TABLE IF EXISTS `open_archives_record_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `open_archives_record_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `openArchivesRecordId` int(11) DEFAULT NULL,
  `year` int(4) NOT NULL,
  `timesViewedInSearch` int(11) NOT NULL,
  `timesUsed` int(11) NOT NULL,
  `month` int(2) NOT NULL DEFAULT '4',
  PRIMARY KEY (`id`),
  KEY `openArchivesRecordId` (`openArchivesRecordId`,`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `open_archives_record_usage`
--

/*!40000 ALTER TABLE `open_archives_record_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `open_archives_record_usage` ENABLE KEYS */;

--
-- Table structure for table `overdrive_account_cache`
--

DROP TABLE IF EXISTS `overdrive_account_cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `overdrive_account_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) DEFAULT NULL,
  `holdPage` longtext,
  `holdPageLastLoaded` int(11) NOT NULL DEFAULT '0',
  `bookshelfPage` longtext,
  `bookshelfPageLastLoaded` int(11) NOT NULL DEFAULT '0',
  `wishlistPage` longtext,
  `wishlistPageLastLoaded` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='A cache to store information about a user''s account within OverDrive.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_account_cache`
--

/*!40000 ALTER TABLE `overdrive_account_cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_account_cache` ENABLE KEYS */;

--
-- Table structure for table `overdrive_api_product_availability`
--

DROP TABLE IF EXISTS `overdrive_api_product_availability`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `overdrive_api_product_availability` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `productId` int(11) DEFAULT NULL,
  `libraryId` int(11) DEFAULT NULL,
  `available` tinyint(1) DEFAULT NULL,
  `copiesOwned` int(11) DEFAULT NULL,
  `copiesAvailable` int(11) DEFAULT NULL,
  `numberOfHolds` int(11) DEFAULT NULL,
  `availabilityType` varchar(35) DEFAULT 'Normal',
  `shared` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `productId_2` (`productId`,`libraryId`),
  KEY `productId` (`productId`),
  KEY `libraryId` (`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_api_product_availability`
--

/*!40000 ALTER TABLE `overdrive_api_product_availability` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_api_product_availability` ENABLE KEYS */;

--
-- Table structure for table `overdrive_api_product_formats`
--

DROP TABLE IF EXISTS `overdrive_api_product_formats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `overdrive_api_product_formats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `productId` int(11) DEFAULT NULL,
  `textId` varchar(25) DEFAULT NULL,
  `numericId` int(11) DEFAULT NULL,
  `name` varchar(512) DEFAULT NULL,
  `fileName` varchar(215) DEFAULT NULL,
  `fileSize` int(11) DEFAULT NULL,
  `partCount` tinyint(4) DEFAULT NULL,
  `sampleSource_1` varchar(215) DEFAULT NULL,
  `sampleUrl_1` varchar(215) DEFAULT NULL,
  `sampleSource_2` varchar(215) DEFAULT NULL,
  `sampleUrl_2` varchar(215) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `productId_2` (`productId`,`textId`),
  KEY `productId` (`productId`),
  KEY `numericId` (`numericId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_api_product_formats`
--

/*!40000 ALTER TABLE `overdrive_api_product_formats` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_api_product_formats` ENABLE KEYS */;

--
-- Table structure for table `overdrive_api_product_identifiers`
--

DROP TABLE IF EXISTS `overdrive_api_product_identifiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `overdrive_api_product_identifiers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `productId` int(11) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `value` varchar(75) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `productId` (`productId`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_api_product_identifiers`
--

/*!40000 ALTER TABLE `overdrive_api_product_identifiers` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_api_product_identifiers` ENABLE KEYS */;

--
-- Table structure for table `overdrive_api_product_metadata`
--

DROP TABLE IF EXISTS `overdrive_api_product_metadata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `overdrive_api_product_metadata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `productId` int(11) DEFAULT NULL,
  `checksum` bigint(20) DEFAULT NULL,
  `sortTitle` varchar(512) DEFAULT NULL,
  `publisher` varchar(215) DEFAULT NULL,
  `publishDate` int(11) DEFAULT NULL,
  `isPublicDomain` tinyint(1) DEFAULT NULL,
  `isPublicPerformanceAllowed` tinyint(1) DEFAULT NULL,
  `shortDescription` text,
  `fullDescription` text,
  `starRating` float DEFAULT NULL,
  `popularity` int(11) DEFAULT NULL,
  `rawData` mediumtext,
  `thumbnail` varchar(255) DEFAULT NULL,
  `cover` varchar(255) DEFAULT NULL,
  `isOwnedByCollections` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `productId` (`productId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_api_product_metadata`
--

/*!40000 ALTER TABLE `overdrive_api_product_metadata` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_api_product_metadata` ENABLE KEYS */;

--
-- Table structure for table `overdrive_api_products`
--

DROP TABLE IF EXISTS `overdrive_api_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `overdrive_api_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `overdriveId` varchar(36) NOT NULL,
  `mediaType` varchar(50) NOT NULL,
  `title` varchar(512) NOT NULL,
  `series` varchar(215) DEFAULT NULL,
  `primaryCreatorRole` varchar(50) DEFAULT NULL,
  `primaryCreatorName` varchar(215) DEFAULT NULL,
  `cover` varchar(215) DEFAULT NULL,
  `dateAdded` int(11) DEFAULT NULL,
  `dateUpdated` int(11) DEFAULT NULL,
  `lastMetadataCheck` int(11) DEFAULT NULL,
  `lastMetadataChange` int(11) DEFAULT NULL,
  `lastAvailabilityCheck` int(11) DEFAULT NULL,
  `lastAvailabilityChange` int(11) DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT '0',
  `dateDeleted` int(11) DEFAULT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `crossRefId` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `overdriveId` (`overdriveId`),
  KEY `dateUpdated` (`dateUpdated`),
  KEY `lastMetadataCheck` (`lastMetadataCheck`),
  KEY `lastAvailabilityCheck` (`lastAvailabilityCheck`),
  KEY `deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_api_products`
--

/*!40000 ALTER TABLE `overdrive_api_products` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_api_products` ENABLE KEYS */;

--
-- Table structure for table `overdrive_extract_log`
--

DROP TABLE IF EXISTS `overdrive_extract_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `overdrive_extract_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `startTime` int(11) DEFAULT NULL,
  `endTime` int(11) DEFAULT NULL,
  `lastUpdate` int(11) DEFAULT NULL,
  `numProducts` int(11) DEFAULT '0',
  `numErrors` int(11) DEFAULT '0',
  `numAdded` int(11) DEFAULT '0',
  `numDeleted` int(11) DEFAULT '0',
  `numUpdated` int(11) DEFAULT '0',
  `numSkipped` int(11) DEFAULT '0',
  `numAvailabilityChanges` int(11) DEFAULT '0',
  `numMetadataChanges` int(11) DEFAULT '0',
  `notes` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_extract_log`
--

/*!40000 ALTER TABLE `overdrive_extract_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_extract_log` ENABLE KEYS */;

--
-- Table structure for table `overdrive_record_usage`
--

DROP TABLE IF EXISTS `overdrive_record_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `overdrive_record_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `overdriveId` varchar(36) DEFAULT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `timesHeld` int(11) NOT NULL,
  `timesCheckedOut` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `overdriveId` (`overdriveId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_record_usage`
--

/*!40000 ALTER TABLE `overdrive_record_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_record_usage` ENABLE KEYS */;

--
-- Table structure for table `overdrive_settings`
--

DROP TABLE IF EXISTS `overdrive_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `overdrive_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(255) DEFAULT NULL,
  `patronApiUrl` varchar(255) DEFAULT NULL,
  `clientSecret` varchar(50) DEFAULT NULL,
  `clientKey` varchar(50) DEFAULT NULL,
  `accountId` int(11) DEFAULT '0',
  `websiteId` int(11) DEFAULT '0',
  `productsKey` varchar(50) DEFAULT '0',
  `runFullUpdate` tinyint(1) DEFAULT '0',
  `lastUpdateOfChangedRecords` int(11) DEFAULT '0',
  `lastUpdateOfAllRecords` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_settings`
--

/*!40000 ALTER TABLE `overdrive_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_settings` ENABLE KEYS */;

--
-- Table structure for table `person`
--

DROP TABLE IF EXISTS `person`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `ageAtDeath` text,
  `cemeteryName` varchar(255) DEFAULT NULL,
  `cemeteryLocation` varchar(255) DEFAULT NULL,
  `mortuaryName` varchar(255) DEFAULT NULL,
  `comments` mediumtext,
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
  `lot` varchar(20) DEFAULT '',
  `grave` int(11) DEFAULT NULL,
  `tombstoneInscription` text,
  `addedBy` int(11) NOT NULL DEFAULT '-1',
  `dateAdded` int(11) DEFAULT NULL,
  `modifiedBy` int(11) NOT NULL DEFAULT '-1',
  `lastModified` int(11) DEFAULT NULL,
  `privateComments` text,
  `importedFrom` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`personId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores information about a particular person for use in genealogy';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `person`
--

/*!40000 ALTER TABLE `person` DISABLE KEYS */;
/*!40000 ALTER TABLE `person` ENABLE KEYS */;

--
-- Table structure for table `ptype`
--

DROP TABLE IF EXISTS `ptype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ptype` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pType` varchar(20) NOT NULL,
  `maxHolds` int(11) NOT NULL DEFAULT '300',
  `masquerade` varchar(45) NOT NULL DEFAULT 'none',
  PRIMARY KEY (`id`),
  UNIQUE KEY `pType` (`pType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ptype`
--

/*!40000 ALTER TABLE `ptype` DISABLE KEYS */;
/*!40000 ALTER TABLE `ptype` ENABLE KEYS */;

--
-- Table structure for table `ptype_restricted_locations`
--

DROP TABLE IF EXISTS `ptype_restricted_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ptype_restricted_locations` (
  `locationId` int(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique id for the non holdable location',
  `millenniumCode` varchar(5) NOT NULL COMMENT 'The internal 5 letter code within Millennium',
  `holdingDisplay` varchar(30) NOT NULL COMMENT 'The text displayed in the holdings list within Millennium can use regular expression syntax to match multiple locations',
  `allowablePtypes` varchar(50) NOT NULL COMMENT 'A list of PTypes that are allowed to place holds on items with this location separated with pipes (|).',
  PRIMARY KEY (`locationId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ptype_restricted_locations`
--

/*!40000 ALTER TABLE `ptype_restricted_locations` DISABLE KEYS */;
/*!40000 ALTER TABLE `ptype_restricted_locations` ENABLE KEYS */;

--
-- Table structure for table `rbdigital_availability`
--

DROP TABLE IF EXISTS `rbdigital_availability`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rbdigital_availability` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rbdigitalId` varchar(25) NOT NULL,
  `isAvailable` tinyint(4) NOT NULL DEFAULT '1',
  `isOwned` tinyint(4) NOT NULL DEFAULT '1',
  `name` varchar(50) DEFAULT NULL,
  `rawChecksum` bigint(20) DEFAULT NULL,
  `rawResponse` mediumtext,
  `lastChange` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rbdigitalId` (`rbdigitalId`),
  KEY `lastChange` (`lastChange`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rbdigital_availability`
--

/*!40000 ALTER TABLE `rbdigital_availability` DISABLE KEYS */;
/*!40000 ALTER TABLE `rbdigital_availability` ENABLE KEYS */;

--
-- Table structure for table `rbdigital_export_log`
--

DROP TABLE IF EXISTS `rbdigital_export_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rbdigital_export_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `notes` text COMMENT 'Additional information about the run',
  `numProducts` int(11) DEFAULT '0',
  `numErrors` int(11) DEFAULT '0',
  `numAdded` int(11) DEFAULT '0',
  `numDeleted` int(11) DEFAULT '0',
  `numUpdated` int(11) DEFAULT '0',
  `numAvailabilityChanges` int(11) DEFAULT '0',
  `numMetadataChanges` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rbdigital_export_log`
--

/*!40000 ALTER TABLE `rbdigital_export_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `rbdigital_export_log` ENABLE KEYS */;

--
-- Table structure for table `rbdigital_magazine`
--

DROP TABLE IF EXISTS `rbdigital_magazine`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rbdigital_magazine` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `magazineId` varchar(25) NOT NULL,
  `issueId` varchar(25) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `mediaType` varchar(50) DEFAULT NULL,
  `language` varchar(50) DEFAULT NULL,
  `rawChecksum` bigint(20) DEFAULT NULL,
  `rawResponse` mediumtext,
  `dateFirstDetected` bigint(20) DEFAULT NULL,
  `lastChange` int(11) NOT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `magazineId` (`magazineId`,`issueId`),
  KEY `lastChange` (`lastChange`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rbdigital_magazine`
--

/*!40000 ALTER TABLE `rbdigital_magazine` DISABLE KEYS */;
/*!40000 ALTER TABLE `rbdigital_magazine` ENABLE KEYS */;

--
-- Table structure for table `rbdigital_magazine_usage`
--

DROP TABLE IF EXISTS `rbdigital_magazine_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rbdigital_magazine_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `magazineId` int(11) DEFAULT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `timesCheckedOut` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `magazineId` (`magazineId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rbdigital_magazine_usage`
--

/*!40000 ALTER TABLE `rbdigital_magazine_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `rbdigital_magazine_usage` ENABLE KEYS */;

--
-- Table structure for table `rbdigital_record_usage`
--

DROP TABLE IF EXISTS `rbdigital_record_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rbdigital_record_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rbdigitalId` int(11) DEFAULT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `timesHeld` int(11) NOT NULL,
  `timesCheckedOut` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `rbdigitalId` (`rbdigitalId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rbdigital_record_usage`
--

/*!40000 ALTER TABLE `rbdigital_record_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `rbdigital_record_usage` ENABLE KEYS */;

--
-- Table structure for table `rbdigital_scopes`
--

DROP TABLE IF EXISTS `rbdigital_scopes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rbdigital_scopes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `includeEBooks` tinyint(4) DEFAULT '1',
  `includeEAudiobook` tinyint(4) DEFAULT '1',
  `includeEMagazines` tinyint(4) DEFAULT '1',
  `restrictToChildrensMaterial` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rbdigital_scopes`
--

/*!40000 ALTER TABLE `rbdigital_scopes` DISABLE KEYS */;
/*!40000 ALTER TABLE `rbdigital_scopes` ENABLE KEYS */;

--
-- Table structure for table `rbdigital_settings`
--

DROP TABLE IF EXISTS `rbdigital_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rbdigital_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `apiUrl` varchar(255) DEFAULT NULL,
  `userInterfaceUrl` varchar(255) DEFAULT NULL,
  `apiToken` varchar(50) DEFAULT NULL,
  `libraryId` int(11) DEFAULT '0',
  `runFullUpdate` tinyint(1) DEFAULT '0',
  `lastUpdateOfChangedRecords` int(11) DEFAULT '0',
  `lastUpdateOfAllRecords` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rbdigital_settings`
--

/*!40000 ALTER TABLE `rbdigital_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `rbdigital_settings` ENABLE KEYS */;

--
-- Table structure for table `rbdigital_title`
--

DROP TABLE IF EXISTS `rbdigital_title`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rbdigital_title` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rbdigitalId` varchar(25) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `primaryAuthor` varchar(255) DEFAULT NULL,
  `mediaType` varchar(50) DEFAULT NULL,
  `isFiction` tinyint(4) NOT NULL DEFAULT '0',
  `audience` varchar(50) DEFAULT NULL,
  `language` varchar(50) DEFAULT NULL,
  `rawChecksum` bigint(20) NOT NULL,
  `rawResponse` mediumtext,
  `lastChange` int(11) NOT NULL,
  `dateFirstDetected` bigint(20) DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `rbdigitalId` (`rbdigitalId`),
  KEY `lastChange` (`lastChange`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rbdigital_title`
--

/*!40000 ALTER TABLE `rbdigital_title` DISABLE KEYS */;
/*!40000 ALTER TABLE `rbdigital_title` ENABLE KEYS */;

--
-- Table structure for table `record_grouping_log`
--

DROP TABLE IF EXISTS `record_grouping_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `record_grouping_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `notes` text COMMENT 'Additional information about the run includes stats per source',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `record_grouping_log`
--

/*!40000 ALTER TABLE `record_grouping_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `record_grouping_log` ENABLE KEYS */;

--
-- Table structure for table `redwood_user_contribution`
--

DROP TABLE IF EXISTS `redwood_user_contribution`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `redwood_user_contribution` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `creator` varchar(255) DEFAULT NULL,
  `dateCreated` varchar(10) DEFAULT NULL,
  `description` mediumtext,
  `suggestedSubjects` mediumtext,
  `howAcquired` varchar(255) DEFAULT NULL,
  `filePath` varchar(255) DEFAULT NULL,
  `status` enum('submitted','accepted','rejected') DEFAULT NULL,
  `license` enum('none','CC0','cc','public') DEFAULT NULL,
  `allowRemixing` tinyint(1) DEFAULT '0',
  `prohibitCommercialUse` tinyint(1) DEFAULT '0',
  `requireShareAlike` tinyint(1) DEFAULT '0',
  `dateContributed` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `redwood_user_contribution`
--

/*!40000 ALTER TABLE `redwood_user_contribution` DISABLE KEYS */;
/*!40000 ALTER TABLE `redwood_user_contribution` ENABLE KEYS */;

--
-- Table structure for table `reindex_log`
--

DROP TABLE IF EXISTS `reindex_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reindex_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of reindex log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the reindex started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the reindex process ended',
  `notes` text COMMENT 'Notes related to the overall process',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The last time the log was updated',
  `numWorksProcessed` int(11) NOT NULL DEFAULT '0',
  `numListsProcessed` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reindex_log`
--

/*!40000 ALTER TABLE `reindex_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `reindex_log` ENABLE KEYS */;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `roleId` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT 'The internal name of the role',
  `description` varchar(100) NOT NULL COMMENT 'A description of what the role allows',
  PRIMARY KEY (`roleId`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8 COMMENT='A role identifying what the user can do.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'userAdmin','Allows administration of users.'),(2,'opacAdmin','Allows administration of the opac display (libraries, locations, etc).'),(3,'genealogyContributor','Allows Genealogy data to be entered  by the user.'),(5,'cataloging','Allows user to perform cataloging activities.'),(6,'libraryAdmin','Allows user to update library configuration for their library system only for their home location.'),(7,'contentEditor','Allows entering of editorial reviews and creation of widgets.'),(8,'library_material_requests','Allows user to manage material requests for a specific library.'),(9,'locationReports','Allows the user to view reports for their location.'),(10,'libraryManager','Allows user to do basic configuration for their library.'),(11,'locationManager','Allows user to do basic configuration for their location.'),(12,'circulationReports','Allows user to view offline circulation reports.'),(13,'listPublisher','Optionally only include lists from people with this role in search results.'),(14,'archives','Control overall archives integration.'),(31,'translator','Allows the user to translate the system.');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;

--
-- Table structure for table `search`
--

DROP TABLE IF EXISTS `search`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `search` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `session_id` varchar(128) DEFAULT NULL,
  `folder_id` int(11) DEFAULT NULL,
  `created` date NOT NULL,
  `title` varchar(20) DEFAULT NULL,
  `saved` int(1) NOT NULL DEFAULT '0',
  `search_object` blob,
  `searchSource` varchar(30) NOT NULL DEFAULT 'local',
  `searchUrl` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `folder_id` (`folder_id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `search`
--

/*!40000 ALTER TABLE `search` DISABLE KEYS */;
/*!40000 ALTER TABLE `search` ENABLE KEYS */;

--
-- Table structure for table `search_stats_new`
--

DROP TABLE IF EXISTS `search_stats_new`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `search_stats_new` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The unique id of the search statistic',
  `phrase` varchar(500) NOT NULL COMMENT 'The phrase being searched for',
  `lastSearch` int(16) NOT NULL COMMENT 'The last time this search was done',
  `numSearches` int(16) NOT NULL COMMENT 'The number of times this search has been done.',
  PRIMARY KEY (`id`),
  KEY `numSearches` (`numSearches`),
  KEY `lastSearch` (`lastSearch`),
  FULLTEXT KEY `phrase_text` (`phrase`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Statistical information about searches for use in reporting ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `search_stats_new`
--

/*!40000 ALTER TABLE `search_stats_new` DISABLE KEYS */;
/*!40000 ALTER TABLE `search_stats_new` ENABLE KEYS */;

--
-- Table structure for table `sendgrid_settings`
--

DROP TABLE IF EXISTS `sendgrid_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sendgrid_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fromAddress` varchar(255) DEFAULT NULL,
  `replyToAddress` varchar(255) DEFAULT NULL,
  `apiKey` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sendgrid_settings`
--

/*!40000 ALTER TABLE `sendgrid_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `sendgrid_settings` ENABLE KEYS */;

--
-- Table structure for table `session`
--

DROP TABLE IF EXISTS `session`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `session` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(128) DEFAULT NULL,
  `data` mediumtext,
  `last_used` int(12) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `remember_me` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether or not the session was started with remember me on.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_id` (`session_id`),
  KEY `last_used` (`last_used`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `session`
--

/*!40000 ALTER TABLE `session` DISABLE KEYS */;
INSERT INTO `session` VALUES (3,'6r6u8fsbnb9j2feqovmgkrj52i','activeUserId|s:1:\"1\";rememberMe|b:0;',1574170290,'2019-11-19 05:31:22',0);
/*!40000 ALTER TABLE `session` ENABLE KEYS */;

--
-- Table structure for table `sideload_log`
--

DROP TABLE IF EXISTS `sideload_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sideload_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `notes` text COMMENT 'Additional information about the run',
  `numSideLoadsUpdated` int(11) DEFAULT '0',
  `sideLoadsUpdated` mediumtext,
  `numProducts` int(11) DEFAULT '0',
  `numErrors` int(11) DEFAULT '0',
  `numAdded` int(11) DEFAULT '0',
  `numDeleted` int(11) DEFAULT '0',
  `numUpdated` int(11) DEFAULT '0',
  `numSkipped` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sideload_log`
--

/*!40000 ALTER TABLE `sideload_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `sideload_log` ENABLE KEYS */;

--
-- Table structure for table `sideload_record_usage`
--

DROP TABLE IF EXISTS `sideload_record_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sideload_record_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sideloadId` int(11) NOT NULL,
  `recordId` varchar(36) DEFAULT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `timesUsed` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sideloadId` (`sideloadId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sideload_record_usage`
--

/*!40000 ALTER TABLE `sideload_record_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `sideload_record_usage` ENABLE KEYS */;

--
-- Table structure for table `sideload_scopes`
--

DROP TABLE IF EXISTS `sideload_scopes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sideload_scopes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `sideLoadId` int(11) NOT NULL,
  `restrictToChildrensMaterial` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sideload_scopes`
--

/*!40000 ALTER TABLE `sideload_scopes` DISABLE KEYS */;
/*!40000 ALTER TABLE `sideload_scopes` ENABLE KEYS */;

--
-- Table structure for table `sideloads`
--

DROP TABLE IF EXISTS `sideloads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sideloads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `marcPath` varchar(100) NOT NULL,
  `filenamesToInclude` varchar(250) DEFAULT '.*\\.ma?rc',
  `marcEncoding` enum('MARC8','UTF8','UNIMARC','ISO8859_1','BESTGUESS') NOT NULL DEFAULT 'MARC8',
  `individualMarcPath` varchar(100) NOT NULL,
  `numCharsToCreateFolderFrom` int(11) DEFAULT '4',
  `createFolderFromLeadingCharacters` tinyint(1) DEFAULT '0',
  `groupingClass` varchar(100) NOT NULL DEFAULT 'SideLoadedRecordGrouper',
  `indexingClass` varchar(50) NOT NULL DEFAULT 'SideLoadedEContentProcessor',
  `recordDriver` varchar(100) NOT NULL DEFAULT 'SideLoadedRecord',
  `recordUrlComponent` varchar(25) NOT NULL DEFAULT 'DefineThis',
  `recordNumberTag` char(3) NOT NULL DEFAULT '001',
  `recordNumberSubfield` char(1) DEFAULT 'a',
  `recordNumberPrefix` varchar(10) NOT NULL,
  `suppressItemlessBibs` tinyint(1) NOT NULL DEFAULT '1',
  `itemTag` char(3) NOT NULL,
  `itemRecordNumber` char(1) DEFAULT NULL,
  `location` char(1) DEFAULT NULL,
  `locationsToSuppress` varchar(255) DEFAULT NULL,
  `itemUrl` char(1) DEFAULT NULL,
  `format` char(1) DEFAULT NULL,
  `formatSource` enum('bib','item','specified') NOT NULL DEFAULT 'bib',
  `specifiedFormat` varchar(50) DEFAULT NULL,
  `specifiedFormatCategory` varchar(50) DEFAULT NULL,
  `specifiedFormatBoost` int(11) DEFAULT NULL,
  `runFullUpdate` tinyint(1) DEFAULT '0',
  `lastUpdateOfChangedRecords` int(11) DEFAULT '0',
  `lastUpdateOfAllRecords` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sideloads`
--

/*!40000 ALTER TABLE `sideloads` DISABLE KEYS */;
/*!40000 ALTER TABLE `sideloads` ENABLE KEYS */;

--
-- Table structure for table `sierra_api_export_log`
--

DROP TABLE IF EXISTS `sierra_api_export_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sierra_api_export_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `notes` text COMMENT 'Additional information about the run',
  `numRecordsToProcess` int(11) DEFAULT NULL,
  `numRecordsProcessed` int(11) DEFAULT NULL,
  `numErrors` int(11) DEFAULT NULL,
  `numRemainingRecords` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sierra_api_export_log`
--

/*!40000 ALTER TABLE `sierra_api_export_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `sierra_api_export_log` ENABLE KEYS */;

--
-- Table structure for table `sierra_export_field_mapping`
--

DROP TABLE IF EXISTS `sierra_export_field_mapping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sierra_export_field_mapping` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of field mapping',
  `indexingProfileId` int(11) NOT NULL COMMENT 'The indexing profile this field mapping is associated with',
  `bcode3DestinationField` char(3) NOT NULL COMMENT 'The field to place bcode3 into',
  `bcode3DestinationSubfield` char(1) DEFAULT NULL COMMENT 'The subfield to place bcode3 into',
  `callNumberExportFieldTag` char(1) DEFAULT NULL,
  `callNumberPrestampExportSubfield` char(1) DEFAULT NULL,
  `callNumberExportSubfield` char(1) DEFAULT NULL,
  `callNumberCutterExportSubfield` char(1) DEFAULT NULL,
  `callNumberPoststampExportSubfield` char(5) DEFAULT NULL,
  `volumeExportFieldTag` char(1) DEFAULT NULL,
  `urlExportFieldTag` char(1) DEFAULT NULL,
  `eContentExportFieldTag` char(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sierra_export_field_mapping`
--

/*!40000 ALTER TABLE `sierra_export_field_mapping` DISABLE KEYS */;
/*!40000 ALTER TABLE `sierra_export_field_mapping` ENABLE KEYS */;

--
-- Table structure for table `slow_ajax_request`
--

DROP TABLE IF EXISTS `slow_ajax_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `slow_ajax_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `module` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `method` varchar(75) NOT NULL,
  `timesSlow` int(11) DEFAULT '0',
  `timesFast` int(11) DEFAULT NULL,
  `timesAcceptable` int(11) DEFAULT NULL,
  `timesSlower` int(11) DEFAULT NULL,
  `timesVerySlow` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `year` (`year`,`month`,`module`,`action`,`method`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `slow_ajax_request`
--

/*!40000 ALTER TABLE `slow_ajax_request` DISABLE KEYS */;
INSERT INTO `slow_ajax_request` VALUES (1,2019,11,'MyAccount','AJAX','getMenuDataRBdigital',0,2,NULL,NULL,NULL),(2,2019,11,'MyAccount','AJAX','getMenuDataCloudLibrary',0,2,NULL,NULL,NULL),(3,2019,11,'MyAccount','AJAX','getMenuDataHoopla',0,2,NULL,NULL,NULL),(4,2019,11,'MyAccount','AJAX','getMenuDataOverDrive',0,2,NULL,NULL,NULL),(5,2019,11,'MyAccount','AJAX','getListData',0,2,NULL,NULL,NULL),(6,2019,11,'MyAccount','AJAX','getRatingsData',0,2,NULL,NULL,NULL);
/*!40000 ALTER TABLE `slow_ajax_request` ENABLE KEYS */;

--
-- Table structure for table `slow_page`
--

DROP TABLE IF EXISTS `slow_page`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `slow_page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `module` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `timesSlow` int(11) DEFAULT '0',
  `timesFast` int(11) DEFAULT NULL,
  `timesAcceptable` int(11) DEFAULT NULL,
  `timesSlower` int(11) DEFAULT NULL,
  `timesVerySlow` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `year` (`year`,`month`,`module`,`action`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `slow_page`
--

/*!40000 ALTER TABLE `slow_page` DISABLE KEYS */;
INSERT INTO `slow_page` VALUES (1,2019,11,'Admin','DBMaintenance',0,1,NULL,NULL,3),(2,2019,11,'Search','Home',0,NULL,NULL,NULL,1);
/*!40000 ALTER TABLE `slow_page` ENABLE KEYS */;

--
-- Table structure for table `status_map_values`
--

DROP TABLE IF EXISTS `status_map_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `status_map_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `indexingProfileId` int(11) NOT NULL,
  `value` varchar(50) NOT NULL,
  `status` varchar(50) NOT NULL,
  `groupedStatus` varchar(50) NOT NULL,
  `suppress` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `indexingProfileId` (`indexingProfileId`,`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `status_map_values`
--

/*!40000 ALTER TABLE `status_map_values` DISABLE KEYS */;
/*!40000 ALTER TABLE `status_map_values` ENABLE KEYS */;

--
-- Table structure for table `syndetics_data`
--

DROP TABLE IF EXISTS `syndetics_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `syndetics_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupedRecordPermanentId` varchar(36) DEFAULT NULL,
  `lastDescriptionUpdate` int(11) DEFAULT '0',
  `primaryIsbn` varchar(13) DEFAULT NULL,
  `primaryUpc` varchar(25) DEFAULT NULL,
  `description` mediumtext,
  `tableOfContents` mediumtext,
  `excerpt` mediumtext,
  `lastTableOfContentsUpdate` int(11) DEFAULT '0',
  `lastExcerptUpdate` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `groupedRecordPermanentId` (`groupedRecordPermanentId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `syndetics_data`
--

/*!40000 ALTER TABLE `syndetics_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `syndetics_data` ENABLE KEYS */;

--
-- Table structure for table `themes`
--

DROP TABLE IF EXISTS `themes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `themes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `themeName` varchar(100) NOT NULL,
  `extendsTheme` varchar(100) DEFAULT NULL,
  `logoName` varchar(100) DEFAULT NULL,
  `headerBackgroundColor` char(7) DEFAULT '#f1f1f1',
  `headerBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `headerForegroundColor` char(7) DEFAULT '#8b8b8b',
  `headerForegroundColorDefault` tinyint(1) DEFAULT '1',
  `generatedCss` longtext,
  `headerBottomBorderColor` char(7) DEFAULT '#f1f1f1',
  `headerBottomBorderColorDefault` tinyint(1) DEFAULT '1',
  `headerBottomBorderWidth` varchar(6) DEFAULT NULL,
  `headerButtonRadius` varchar(6) DEFAULT NULL,
  `headerButtonColor` char(7) DEFAULT '#ffffff',
  `headerButtonColorDefault` tinyint(1) DEFAULT '1',
  `headerButtonBackgroundColor` char(7) DEFAULT '#848484',
  `headerButtonBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `favicon` varchar(100) DEFAULT NULL,
  `pageBackgroundColor` char(7) DEFAULT '#ffffff',
  `pageBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `primaryBackgroundColor` char(7) DEFAULT '#147ce2',
  `primaryBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `primaryForegroundColor` char(7) DEFAULT '#ffffff',
  `primaryForegroundColorDefault` tinyint(1) DEFAULT '1',
  `bodyBackgroundColor` char(7) DEFAULT '#ffffff',
  `bodyBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `bodyTextColor` char(7) DEFAULT '#6B6B6B',
  `bodyTextColorDefault` tinyint(1) DEFAULT '1',
  `secondaryBackgroundColor` char(7) DEFAULT '#de9d03',
  `secondaryBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `secondaryForegroundColor` char(7) DEFAULT '#ffffff',
  `secondaryForegroundColorDefault` tinyint(1) DEFAULT '1',
  `tertiaryBackgroundColor` char(7) DEFAULT '#de1f0b',
  `tertiaryBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `tertiaryForegroundColor` char(7) DEFAULT '#ffffff',
  `tertiaryForegroundColorDefault` tinyint(1) DEFAULT '1',
  `headingFont` varchar(191) DEFAULT NULL,
  `headingFontDefault` tinyint(1) DEFAULT '1',
  `bodyFont` varchar(191) DEFAULT NULL,
  `bodyFontDefault` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `themeName` (`themeName`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `themes`
--

/*!40000 ALTER TABLE `themes` DISABLE KEYS */;
INSERT INTO `themes` VALUES (1,'default','','logoNameTL_Logo_final.png','#f1f1f1',1,'#8b8b8b',1,NULL,'#f1f1f1',1,NULL,NULL,'#ffffff',1,'#848484',1,NULL,'#ffffff',1,'#147ce2',1,'#ffffff',1,'#ffffff',1,'#6B6B6B',1,'#de9d03',1,'#ffffff',1,'#de1f0b',1,'#ffffff',1,NULL,1,NULL,1);
/*!40000 ALTER TABLE `themes` ENABLE KEYS */;

--
-- Table structure for table `time_to_reshelve`
--

DROP TABLE IF EXISTS `time_to_reshelve`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `time_to_reshelve`
--

/*!40000 ALTER TABLE `time_to_reshelve` DISABLE KEYS */;
/*!40000 ALTER TABLE `time_to_reshelve` ENABLE KEYS */;

--
-- Table structure for table `title_authorities`
--

DROP TABLE IF EXISTS `title_authorities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `title_authorities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `originalName` varchar(255) NOT NULL,
  `authoritativeName` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `originalName` (`originalName`),
  KEY `authoritativeName` (`authoritativeName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `title_authorities`
--

/*!40000 ALTER TABLE `title_authorities` DISABLE KEYS */;
/*!40000 ALTER TABLE `title_authorities` ENABLE KEYS */;

--
-- Table structure for table `translation_map_values`
--

DROP TABLE IF EXISTS `translation_map_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `translation_map_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `translationMapId` int(11) NOT NULL,
  `value` varchar(50) NOT NULL,
  `translation` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `translationMapId` (`translationMapId`,`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `translation_map_values`
--

/*!40000 ALTER TABLE `translation_map_values` DISABLE KEYS */;
/*!40000 ALTER TABLE `translation_map_values` ENABLE KEYS */;

--
-- Table structure for table `translation_maps`
--

DROP TABLE IF EXISTS `translation_maps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `translation_maps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `indexingProfileId` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `usesRegularExpressions` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `profileName` (`indexingProfileId`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `translation_maps`
--

/*!40000 ALTER TABLE `translation_maps` DISABLE KEYS */;
/*!40000 ALTER TABLE `translation_maps` ENABLE KEYS */;

--
-- Table structure for table `translation_terms`
--

DROP TABLE IF EXISTS `translation_terms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `translation_terms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `term` varchar(50) NOT NULL,
  `parameterNotes` varchar(255) DEFAULT NULL,
  `samplePageUrl` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `term` (`term`),
  KEY `url` (`samplePageUrl`)
) ENGINE=InnoDB AUTO_INCREMENT=94 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `translation_terms`
--

/*!40000 ALTER TABLE `translation_terms` DISABLE KEYS */;
INSERT INTO `translation_terms` VALUES (1,'Database Maintenance',NULL,'/Admin/DBMaintenance'),(2,'Your Account',NULL,'/Admin/DBMaintenance'),(3,'Log Out',NULL,'/Admin/DBMaintenance'),(4,'LOGIN',NULL,'/Admin/DBMaintenance'),(5,'Search for',NULL,'/Admin/DBMaintenance'),(6,'by',NULL,'/Admin/DBMaintenance'),(7,'Keyword',NULL,'/Admin/DBMaintenance'),(8,'Title',NULL,'/Admin/DBMaintenance'),(9,'Start of Title',NULL,'/Admin/DBMaintenance'),(10,'Series',NULL,'/Admin/DBMaintenance'),(11,'Author',NULL,'/Admin/DBMaintenance'),(12,'Subject',NULL,'/Admin/DBMaintenance'),(13,'in',NULL,'/Admin/DBMaintenance'),(14,'GO',NULL,'/Admin/DBMaintenance'),(15,'Advanced Search',NULL,'/Admin/DBMaintenance'),(16,'Account',NULL,'/Admin/DBMaintenance'),(17,'Help',NULL,'/Admin/DBMaintenance'),(18,'Logged In As %1%',NULL,'/Admin/DBMaintenance'),(19,'My Account',NULL,'/Admin/DBMaintenance'),(20,'Checked Out Titles',NULL,'/Admin/DBMaintenance'),(21,'Physical Materials',NULL,'/Admin/DBMaintenance'),(22,'Overdue',NULL,'/Admin/DBMaintenance'),(23,'Titles On Hold',NULL,'/Admin/DBMaintenance'),(24,'Ready for Pickup',NULL,'/Admin/DBMaintenance'),(25,'Reading History',NULL,'/Admin/DBMaintenance'),(26,'Fines and Messages',NULL,'/Admin/DBMaintenance'),(27,'Titles You Rated',NULL,'/Admin/DBMaintenance'),(28,'Recommended For You',NULL,'/Admin/DBMaintenance'),(29,'Account Settings',NULL,'/Admin/DBMaintenance'),(30,'My Preferences',NULL,'/Admin/DBMaintenance'),(31,'Contact Information',NULL,'/Admin/DBMaintenance'),(32,'Linked Accounts',NULL,'/Admin/DBMaintenance'),(33,'Staff Settings',NULL,'/Admin/DBMaintenance'),(34,'history_saved_searches',NULL,'/Admin/DBMaintenance'),(35,'My Lists',NULL,'/Admin/DBMaintenance'),(36,'Create a New List',NULL,'/Admin/DBMaintenance'),(37,'Primary Configuration',NULL,'/Admin/DBMaintenance'),(38,'Themes',NULL,'/Admin/DBMaintenance'),(39,'Languages',NULL,'/Admin/DBMaintenance'),(40,'Translations',NULL,'/Admin/DBMaintenance'),(41,'Library Systems',NULL,'/Admin/DBMaintenance'),(42,'Locations',NULL,'/Admin/DBMaintenance'),(43,'Block Patron Account Linking',NULL,'/Admin/DBMaintenance'),(44,'IP Addresses',NULL,'/Admin/DBMaintenance'),(45,'List Widgets',NULL,'/Admin/DBMaintenance'),(46,'Browse Categories',NULL,'/Admin/DBMaintenance'),(47,'NY Times Lists',NULL,'/Admin/DBMaintenance'),(48,'Patron Types',NULL,'/Admin/DBMaintenance'),(49,'Account Profiles',NULL,'/Admin/DBMaintenance'),(50,'System Administration',NULL,'/Admin/DBMaintenance'),(51,'Modules',NULL,'/Admin/DBMaintenance'),(52,'Administrators',NULL,'/Admin/DBMaintenance'),(53,'DB Maintenance',NULL,'/Admin/DBMaintenance'),(54,'Usage Dashboard',NULL,'/Admin/DBMaintenance'),(55,'Error Report',NULL,'/Admin/DBMaintenance'),(56,'Performance Report',NULL,'/Admin/DBMaintenance'),(57,'SendGrid Settings',NULL,'/Admin/DBMaintenance'),(58,'Solr Information',NULL,'/Admin/DBMaintenance'),(59,'PHP Information',NULL,'/Admin/DBMaintenance'),(60,'System Variables',NULL,'/Admin/DBMaintenance'),(61,'Cron Log',NULL,'/Admin/DBMaintenance'),(62,'Indexing Information',NULL,'/Admin/DBMaintenance'),(63,'Record Grouping Log',NULL,'/Admin/DBMaintenance'),(64,'Grouped Work Index Log',NULL,'/Admin/DBMaintenance'),(65,'Cataloging',NULL,'/Admin/DBMaintenance'),(66,'Grouped Work Merging',NULL,'/Admin/DBMaintenance'),(67,'Records To Not Group',NULL,'/Admin/DBMaintenance'),(68,'Author Enrichment',NULL,'/Admin/DBMaintenance'),(69,'Accelerated Reader Settings',NULL,'/Admin/DBMaintenance'),(70,'ILS Integration',NULL,'/Admin/DBMaintenance'),(71,'Indexing Profiles',NULL,'/Admin/DBMaintenance'),(72,'Translation Maps',NULL,'/Admin/DBMaintenance'),(73,'Indexing Log',NULL,'/Admin/DBMaintenance'),(74,'Dashboard',NULL,'/Admin/DBMaintenance'),(75,'Circulation',NULL,'/Admin/DBMaintenance'),(76,'Offline Holds Report',NULL,'/Admin/DBMaintenance'),(77,'Library Hours and Locations',NULL,'/Admin/DBMaintenance'),(78,'Location',NULL,'/Admin/DBMaintenance'),(79,'Browse Catalog',NULL,'/Admin/DBMaintenance'),(80,'powered_by_aspen',NULL,'/Admin/DBMaintenance'),(81,'Loading, please wait',NULL,'/Admin/DBMaintenance'),(82,'Close',NULL,'/Admin/DBMaintenance'),(83,'An Error has occurred',NULL,'/MyAccount/AJAX?method=getMenuDataIls&activeModule=&activeAction='),(84,'Oops, an error occurred',NULL,'/MyAccount/AJAX?method=getMenuDataIls&activeModule=&activeAction='),(85,'error_logged_message',NULL,'/MyAccount/AJAX?method=getMenuDataIls&activeModule=&activeAction='),(86,'contact_library_message',NULL,'/MyAccount/AJAX?method=getMenuDataIls&activeModule=&activeAction='),(87,'Debug Information',NULL,'/MyAccount/AJAX?method=getMenuDataIls&activeModule=&activeAction='),(88,'Backtrace',NULL,'/MyAccount/AJAX?method=getMenuDataIls&activeModule=&activeAction='),(89,'Catalog Home',NULL,'/'),(90,'New Fiction',NULL,'/'),(91,'New Non Fiction',NULL,'/'),(92,'Covers',NULL,'/'),(93,'Grid',NULL,'/');
/*!40000 ALTER TABLE `translation_terms` ENABLE KEYS */;

--
-- Table structure for table `translations`
--

DROP TABLE IF EXISTS `translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `translations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `termId` int(11) NOT NULL,
  `languageId` int(11) NOT NULL,
  `translation` text,
  `translated` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `term_language` (`termId`,`languageId`),
  KEY `translation_status` (`languageId`,`translated`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `translations`
--

/*!40000 ALTER TABLE `translations` DISABLE KEYS */;
INSERT INTO `translations` VALUES (1,83,1,'An Error has occurred',1),(2,2,1,'My Account',1),(3,3,1,'Log Out',1),(4,4,1,'LOGIN',1),(5,5,1,'Search for',1),(6,14,1,'GO',1),(7,15,1,'Advanced Search',1),(8,84,1,'Oops, an error occurred',1),(9,85,1,'This error has been logged and we are working on a fix.',1),(10,86,1,'Please contact the Library Reference Department for assistance',1),(11,87,1,'Debug Information',1),(12,88,1,'Backtrace',1),(13,80,1,'Powered By Aspen Discovery from Turning Leaf Technologies',1),(14,81,1,'Loading, please wait',1),(15,82,1,'Close',1),(16,89,1,'Catalog Home',1),(17,6,1,'by',1),(18,7,1,'Keyword',1),(19,8,1,'Title',1),(20,9,1,'Start of Title',1),(21,10,1,'Series',1),(22,11,1,'Author',1),(23,1,1,'Database Maintenance',1),(24,12,1,'Subject',1),(25,13,1,'in',1),(27,16,1,'Account',1),(28,17,1,'Help',1),(29,18,1,'Logged In As %1%',1),(30,19,1,'My Account',1),(31,20,1,'Checked Out Titles',1),(32,21,1,'Physical Materials',1),(33,22,1,'Overdue',1),(34,23,1,'Titles On Hold',1),(35,24,1,'Ready for Pickup',1),(36,25,1,'Reading History',1),(37,26,1,'Fines and Messages',1),(38,27,1,'Titles You Rated',1),(39,28,1,'Recommended For You',1),(40,29,1,'Account Settings',1),(41,30,1,'My Preferences',1),(42,31,1,'Contact Information',1),(43,32,1,'Linked Accounts',1),(44,33,1,'Staff Settings',1),(45,34,1,'Search History',1),(46,35,1,'My Lists',1),(47,36,1,'Create a New List',1),(48,37,1,'Primary Configuration',1),(49,38,1,'Themes',1),(50,39,1,'Languages',1),(51,40,1,'Translations',1),(52,41,1,'Library Systems',1),(53,42,1,'Locations',1),(54,43,1,'Block Patron Account Linking',1),(55,44,1,'IP Addresses',1),(56,45,1,'List Widgets',1),(57,46,1,'Browse Categories',1),(58,47,1,'NY Times Lists',1),(59,48,1,'Patron Types',1),(60,49,1,'Account Profiles',1),(61,50,1,'System Administration',1),(62,51,1,'Modules',1),(63,52,1,'Administrators',1),(64,53,1,'DB Maintenance',1),(65,54,1,'Usage Dashboard',1),(66,55,1,'Error Report',1),(67,56,1,'Performance Report',1),(68,57,1,'SendGrid Settings',1),(70,58,1,'Solr Information',1),(71,59,1,'PHP Information',1),(74,60,1,'System Variables',1),(75,61,1,'Cron Log',1),(76,62,1,'Indexing Information',1),(77,63,1,'Record Grouping Log',1),(78,64,1,'Grouped Work Index Log',1),(79,65,1,'Cataloging',1),(80,66,1,'Grouped Work Merging',1),(81,67,1,'Records To Not Group',1),(82,68,1,'Author Enrichment',1),(83,69,1,'Accelerated Reader Settings',1),(84,70,1,'ILS Integration',1),(85,71,1,'Indexing Profiles',1),(86,72,1,'Translation Maps',1),(87,73,1,'Indexing Log',1),(88,74,1,'Dashboard',1),(89,75,1,'Circulation',1),(90,76,1,'Offline Holds Report',1),(92,77,1,'Library Hours and Locations',1),(93,78,1,'Location',1),(96,79,1,'Browse Catalog',1),(97,90,1,'New Fiction',1),(98,91,1,'New Non Fiction',1),(99,92,1,'Covers',1),(100,93,1,'Grid',1);
/*!40000 ALTER TABLE `translations` ENABLE KEYS */;

--
-- Table structure for table `usage_tracking`
--

DROP TABLE IF EXISTS `usage_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usage_tracking` (
  `usageId` int(11) NOT NULL AUTO_INCREMENT,
  `ipId` int(11) NOT NULL,
  `locationId` int(11) NOT NULL,
  `numPageViews` int(11) NOT NULL DEFAULT '0',
  `numHolds` int(11) NOT NULL DEFAULT '0',
  `numRenewals` int(11) NOT NULL DEFAULT '0',
  `trackingDate` bigint(20) NOT NULL,
  PRIMARY KEY (`usageId`),
  KEY `usageId` (`usageId`),
  KEY `IP_DATE` (`ipId`,`trackingDate`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usage_tracking`
--

/*!40000 ALTER TABLE `usage_tracking` DISABLE KEYS */;
/*!40000 ALTER TABLE `usage_tracking` ENABLE KEYS */;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(30) NOT NULL DEFAULT '',
  `password` varchar(32) NOT NULL DEFAULT '',
  `firstname` varchar(50) NOT NULL DEFAULT '',
  `lastname` varchar(50) NOT NULL DEFAULT '',
  `email` varchar(250) NOT NULL DEFAULT '',
  `cat_username` varchar(50) DEFAULT NULL,
  `cat_password` varchar(50) DEFAULT NULL,
  `college` varchar(100) NOT NULL DEFAULT '',
  `major` varchar(100) NOT NULL DEFAULT '',
  `created` datetime NOT NULL,
  `homeLocationId` int(11) NOT NULL COMMENT 'A link to the locations table for the users home location (branch) defined in millennium',
  `myLocation1Id` int(11) NOT NULL COMMENT 'A link to the locations table representing an alternate branch the users frequents or that is close by',
  `myLocation2Id` int(11) NOT NULL COMMENT 'A link to the locations table representing an alternate branch the users frequents or that is close by',
  `trackReadingHistory` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether or not Reading History should be tracked.',
  `bypassAutoLogout` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether or not the user wants to bypass the automatic logout code on public workstations.',
  `displayName` varchar(30) NOT NULL DEFAULT '',
  `disableCoverArt` tinyint(4) NOT NULL DEFAULT '0',
  `disableRecommendations` tinyint(4) NOT NULL DEFAULT '0',
  `phone` varchar(190) NOT NULL DEFAULT '',
  `patronType` varchar(30) NOT NULL DEFAULT '',
  `overdriveEmail` varchar(250) NOT NULL DEFAULT '',
  `promptForOverdriveEmail` tinyint(4) DEFAULT '1',
  `preferredLibraryInterface` int(11) DEFAULT NULL,
  `initialReadingHistoryLoaded` tinyint(4) DEFAULT '0',
  `noPromptForUserReviews` tinyint(1) DEFAULT '0',
  `source` varchar(50) DEFAULT 'ils',
  `rbdigitalId` int(11) DEFAULT '-1',
  `rbdigitalLastAccountCheck` int(11) DEFAULT NULL,
  `rbdigitalUsername` varchar(50) DEFAULT NULL,
  `rbdigitalPassword` varchar(50) DEFAULT NULL,
  `interfaceLanguage` varchar(3) DEFAULT 'en',
  `searchPreferenceLanguage` tinyint(1) DEFAULT '-1',
  `rememberHoldPickupLocation` tinyint(1) DEFAULT '0',
  `alwaysHoldNextAvailable` tinyint(1) DEFAULT '0',
  `lockedFacets` text,
  `hooplaCheckOutConfirmation` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`source`,`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (0,'nyt_user','nyt_password','New York Times','The New York Times','','nyt_user','nyt_password','','','2019-11-19 01:57:54',1,1,1,0,0,'',0,0,'','','',1,NULL,0,0,'admin',-1,NULL,NULL,NULL,'en',-1,0,0,NULL,1),(1,'aspen_admin','password','Aspen','Administrator','','aspen_admin','password','','','2019-01-01 00:00:00',0,0,0,0,0,'',0,0,'','','',1,NULL,0,0,'admin',-1,NULL,NULL,NULL,'en',-1,0,0,NULL,1);
/*!40000 ALTER TABLE `user` ENABLE KEYS */;

--
-- Table structure for table `user_cloud_library_usage`
--

DROP TABLE IF EXISTS `user_cloud_library_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_cloud_library_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `usageCount` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_cloud_library_usage`
--

/*!40000 ALTER TABLE `user_cloud_library_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_cloud_library_usage` ENABLE KEYS */;

--
-- Table structure for table `user_hoopla_usage`
--

DROP TABLE IF EXISTS `user_hoopla_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_hoopla_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `usageCount` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_hoopla_usage`
--

/*!40000 ALTER TABLE `user_hoopla_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_hoopla_usage` ENABLE KEYS */;

--
-- Table structure for table `user_ils_usage`
--

DROP TABLE IF EXISTS `user_ils_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_ils_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `indexingProfileId` int(11) NOT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `usageCount` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`,`indexingProfileId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_ils_usage`
--

/*!40000 ALTER TABLE `user_ils_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_ils_usage` ENABLE KEYS */;

--
-- Table structure for table `user_link`
--

DROP TABLE IF EXISTS `user_link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `primaryAccountId` int(11) NOT NULL,
  `linkedAccountId` int(11) NOT NULL,
  `linkingDisabled` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_link` (`primaryAccountId`,`linkedAccountId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_link`
--

/*!40000 ALTER TABLE `user_link` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_link` ENABLE KEYS */;

--
-- Table structure for table `user_link_blocks`
--

DROP TABLE IF EXISTS `user_link_blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_link_blocks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `primaryAccountId` int(10) unsigned NOT NULL,
  `blockedLinkAccountId` int(10) unsigned DEFAULT NULL COMMENT 'A specific account primaryAccountId will not be linked to.',
  `blockLinking` tinyint(3) unsigned DEFAULT NULL COMMENT 'Indicates primaryAccountId will not be linked to any other accounts.',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_link_blocks`
--

/*!40000 ALTER TABLE `user_link_blocks` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_link_blocks` ENABLE KEYS */;

--
-- Table structure for table `user_list`
--

DROP TABLE IF EXISTS `user_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` mediumtext,
  `public` int(11) NOT NULL DEFAULT '0',
  `dateUpdated` int(11) DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT '0',
  `created` int(11) DEFAULT NULL,
  `defaultSort` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_list`
--

/*!40000 ALTER TABLE `user_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_list` ENABLE KEYS */;

--
-- Table structure for table `user_list_entry`
--

DROP TABLE IF EXISTS `user_list_entry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_list_entry` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupedWorkPermanentId` varchar(36) DEFAULT NULL,
  `listId` int(11) DEFAULT NULL,
  `notes` mediumtext,
  `dateAdded` int(11) DEFAULT NULL,
  `weight` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `groupedWorkPermanentId` (`groupedWorkPermanentId`),
  KEY `listId` (`listId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_list_entry`
--

/*!40000 ALTER TABLE `user_list_entry` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_list_entry` ENABLE KEYS */;

--
-- Table structure for table `user_messages`
--

DROP TABLE IF EXISTS `user_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) DEFAULT NULL,
  `messageType` varchar(50) DEFAULT NULL,
  `messageLevel` enum('success','info','warning','danger') DEFAULT 'info',
  `message` mediumtext,
  `isDismissed` tinyint(1) DEFAULT '0',
  `action1` varchar(255) DEFAULT NULL,
  `action1Title` varchar(50) DEFAULT NULL,
  `action2` varchar(255) DEFAULT NULL,
  `action2Title` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`,`isDismissed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_messages`
--

/*!40000 ALTER TABLE `user_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_messages` ENABLE KEYS */;

--
-- Table structure for table `user_not_interested`
--

DROP TABLE IF EXISTS `user_not_interested`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_not_interested` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `dateMarked` int(11) DEFAULT NULL,
  `groupedRecordPermanentId` varchar(36) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_not_interested`
--

/*!40000 ALTER TABLE `user_not_interested` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_not_interested` ENABLE KEYS */;

--
-- Table structure for table `user_open_archives_usage`
--

DROP TABLE IF EXISTS `user_open_archives_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_open_archives_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `openArchivesCollectionId` int(11) NOT NULL,
  `year` int(4) NOT NULL,
  `usageCount` int(11) DEFAULT NULL,
  `month` int(2) NOT NULL DEFAULT '4',
  PRIMARY KEY (`id`),
  KEY `openArchivesCollectionId` (`openArchivesCollectionId`,`year`,`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_open_archives_usage`
--

/*!40000 ALTER TABLE `user_open_archives_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_open_archives_usage` ENABLE KEYS */;

--
-- Table structure for table `user_overdrive_usage`
--

DROP TABLE IF EXISTS `user_overdrive_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_overdrive_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `usageCount` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_overdrive_usage`
--

/*!40000 ALTER TABLE `user_overdrive_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_overdrive_usage` ENABLE KEYS */;

--
-- Table structure for table `user_payments`
--

DROP TABLE IF EXISTS `user_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) DEFAULT NULL,
  `paymentType` varchar(20) DEFAULT NULL,
  `orderId` varchar(50) DEFAULT NULL,
  `completed` tinyint(1) DEFAULT NULL,
  `finesPaid` varchar(255) DEFAULT NULL,
  `totalPaid` float DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`,`paymentType`,`completed`),
  KEY `paymentType` (`paymentType`,`orderId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_payments`
--

/*!40000 ALTER TABLE `user_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_payments` ENABLE KEYS */;

--
-- Table structure for table `user_rbdigital_usage`
--

DROP TABLE IF EXISTS `user_rbdigital_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_rbdigital_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `usageCount` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_rbdigital_usage`
--

/*!40000 ALTER TABLE `user_rbdigital_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_rbdigital_usage` ENABLE KEYS */;

--
-- Table structure for table `user_reading_history_work`
--

DROP TABLE IF EXISTS `user_reading_history_work`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_reading_history_work` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL COMMENT 'The id of the user who checked out the item',
  `groupedWorkPermanentId` char(36) NOT NULL,
  `source` varchar(25) NOT NULL COMMENT 'The source of the record being checked out',
  `sourceId` varchar(50) NOT NULL COMMENT 'The id of the item that item that was checked out within the source',
  `title` varchar(150) DEFAULT NULL COMMENT 'The title of the item in case this is ever deleted',
  `author` varchar(75) DEFAULT NULL COMMENT 'The author of the item in case this is ever deleted',
  `format` varchar(50) DEFAULT NULL COMMENT 'The format of the item in case this is ever deleted',
  `checkOutDate` int(11) NOT NULL COMMENT 'The first day we detected that the item was checked out to the patron',
  `checkInDate` int(11) DEFAULT NULL COMMENT 'The last day we detected that the item was checked out to the patron.',
  `deleted` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`,`checkOutDate`),
  KEY `userId_2` (`userId`,`checkInDate`),
  KEY `userId_3` (`userId`,`title`),
  KEY `userId_4` (`userId`,`author`),
  KEY `sourceId` (`sourceId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='The reading history for patrons';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_reading_history_work`
--

/*!40000 ALTER TABLE `user_reading_history_work` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_reading_history_work` ENABLE KEYS */;

--
-- Table structure for table `user_roles`
--

DROP TABLE IF EXISTS `user_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_roles` (
  `userId` int(11) NOT NULL,
  `roleId` int(11) NOT NULL,
  PRIMARY KEY (`userId`,`roleId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Links users with roles so users can perform administration f';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_roles`
--

/*!40000 ALTER TABLE `user_roles` DISABLE KEYS */;
INSERT INTO `user_roles` VALUES (1,1),(1,2);
/*!40000 ALTER TABLE `user_roles` ENABLE KEYS */;

--
-- Table structure for table `user_sideload_usage`
--

DROP TABLE IF EXISTS `user_sideload_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_sideload_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `sideloadId` int(11) NOT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `usageCount` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`,`sideloadId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_sideload_usage`
--

/*!40000 ALTER TABLE `user_sideload_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_sideload_usage` ENABLE KEYS */;

--
-- Table structure for table `user_staff_settings`
--

DROP TABLE IF EXISTS `user_staff_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_staff_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userId` int(10) unsigned NOT NULL,
  `materialsRequestReplyToAddress` varchar(70) DEFAULT NULL,
  `materialsRequestEmailSignature` tinytext,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userId_UNIQUE` (`userId`),
  KEY `userId` (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_staff_settings`
--

/*!40000 ALTER TABLE `user_staff_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_staff_settings` ENABLE KEYS */;

--
-- Table structure for table `user_website_usage`
--

DROP TABLE IF EXISTS `user_website_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_website_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `websiteId` int(11) NOT NULL,
  `month` int(2) NOT NULL,
  `year` int(4) NOT NULL,
  `usageCount` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `websiteId` (`websiteId`,`year`,`month`,`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_website_usage`
--

/*!40000 ALTER TABLE `user_website_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_website_usage` ENABLE KEYS */;

--
-- Table structure for table `user_work_review`
--

DROP TABLE IF EXISTS `user_work_review`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_work_review` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupedRecordPermanentId` varchar(36) DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  `rating` tinyint(1) DEFAULT NULL,
  `review` mediumtext,
  `dateRated` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `groupedRecordPermanentId` (`groupedRecordPermanentId`),
  KEY `userId` (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_work_review`
--

/*!40000 ALTER TABLE `user_work_review` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_work_review` ENABLE KEYS */;

--
-- Table structure for table `variables`
--

DROP TABLE IF EXISTS `variables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `variables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_2` (`name`),
  UNIQUE KEY `name_3` (`name`),
  KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=142 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `variables`
--

/*!40000 ALTER TABLE `variables` DISABLE KEYS */;
INSERT INTO `variables` VALUES (2,'validateChecksumsFromDisk','false'),(3,'offline_mode_when_offline_login_allowed','false'),(4,'fullReindexIntervalWarning','86400'),(5,'fullReindexIntervalCritical','129600'),(6,'bypass_export_validation','0'),(7,'last_validatemarcexport_time',NULL),(8,'last_export_valid','1'),(9,'record_grouping_running','false'),(10,'last_grouping_time',NULL),(25,'partial_reindex_running','true'),(26,'last_reindex_time',NULL),(27,'lastPartialReindexFinish',NULL),(29,'full_reindex_running','false'),(37,'lastFullReindexFinish',NULL);
/*!40000 ALTER TABLE `variables` ENABLE KEYS */;

--
-- Table structure for table `website_index_log`
--

DROP TABLE IF EXISTS `website_index_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `website_index_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log',
  `websiteName` varchar(255) NOT NULL,
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `notes` text COMMENT 'Additional information about the run',
  `numPages` int(11) DEFAULT '0',
  `numAdded` int(11) DEFAULT '0',
  `numDeleted` int(11) DEFAULT '0',
  `numUpdated` int(11) DEFAULT '0',
  `numErrors` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `websiteName` (`websiteName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `website_index_log`
--

/*!40000 ALTER TABLE `website_index_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `website_index_log` ENABLE KEYS */;

--
-- Table structure for table `website_indexing_settings`
--

DROP TABLE IF EXISTS `website_indexing_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `website_indexing_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(75) NOT NULL,
  `searchCategory` varchar(75) NOT NULL,
  `siteUrl` varchar(255) DEFAULT NULL,
  `indexFrequency` enum('hourly','daily','weekly','monthly','yearly','once') DEFAULT NULL,
  `lastIndexed` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `lastIndexed` (`lastIndexed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `website_indexing_settings`
--

/*!40000 ALTER TABLE `website_indexing_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `website_indexing_settings` ENABLE KEYS */;

--
-- Table structure for table `website_page_usage`
--

DROP TABLE IF EXISTS `website_page_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `website_page_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `webPageId` int(11) DEFAULT NULL,
  `month` int(2) NOT NULL,
  `year` int(4) NOT NULL,
  `timesViewedInSearch` int(11) NOT NULL,
  `timesUsed` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `webPageId` (`webPageId`,`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `website_page_usage`
--

/*!40000 ALTER TABLE `website_page_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `website_page_usage` ENABLE KEYS */;

--
-- Table structure for table `website_pages`
--

DROP TABLE IF EXISTS `website_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `website_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `websiteId` int(11) NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `checksum` bigint(20) DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT NULL,
  `firstDetected` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `url` (`url`),
  KEY `websiteId` (`websiteId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `website_pages`
--

/*!40000 ALTER TABLE `website_pages` DISABLE KEYS */;
/*!40000 ALTER TABLE `website_pages` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2019-11-19  7:30:16
