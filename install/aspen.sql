-- MySQL dump 10.13  Distrib 8.0.18, for Win64 (x86_64)
--
-- Host: localhost    Database: aspen
-- ------------------------------------------------------
-- Server version	8.0.18

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `accelerated_reading_isbn` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `arBookId` int(11) NOT NULL,
  `isbn` varchar(13) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `isbn` (`isbn`),
  KEY `arBookId` (`arBookId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accelerated_reading_isbn`
--

LOCK TABLES `accelerated_reading_isbn` WRITE;
/*!40000 ALTER TABLE `accelerated_reading_isbn` DISABLE KEYS */;
/*!40000 ALTER TABLE `accelerated_reading_isbn` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accelerated_reading_settings`
--

DROP TABLE IF EXISTS `accelerated_reading_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `accelerated_reading_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `indexSeries` tinyint(1) DEFAULT '1',
  `indexSubjects` tinyint(1) DEFAULT '1',
  `arExportPath` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `ftpServer` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `ftpUser` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `ftpPassword` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `lastFetched` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accelerated_reading_settings`
--

LOCK TABLES `accelerated_reading_settings` WRITE;
/*!40000 ALTER TABLE `accelerated_reading_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `accelerated_reading_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accelerated_reading_subject`
--

DROP TABLE IF EXISTS `accelerated_reading_subject`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `accelerated_reading_subject` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `topic` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `subTopic` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `topic` (`topic`,`subTopic`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accelerated_reading_subject`
--

LOCK TABLES `accelerated_reading_subject` WRITE;
/*!40000 ALTER TABLE `accelerated_reading_subject` DISABLE KEYS */;
/*!40000 ALTER TABLE `accelerated_reading_subject` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accelerated_reading_subject_to_title`
--

DROP TABLE IF EXISTS `accelerated_reading_subject_to_title`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `accelerated_reading_subject_to_title` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `arBookId` int(11) NOT NULL,
  `arSubjectId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `arBookId` (`arBookId`,`arSubjectId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accelerated_reading_subject_to_title`
--

LOCK TABLES `accelerated_reading_subject_to_title` WRITE;
/*!40000 ALTER TABLE `accelerated_reading_subject_to_title` DISABLE KEYS */;
/*!40000 ALTER TABLE `accelerated_reading_subject_to_title` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accelerated_reading_titles`
--

DROP TABLE IF EXISTS `accelerated_reading_titles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `accelerated_reading_titles` (
  `arBookId` int(11) NOT NULL,
  `language` varchar(2) COLLATE utf8mb4_general_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `authorCombined` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `bookLevel` float DEFAULT NULL,
  `arPoints` int(4) DEFAULT NULL,
  `isFiction` tinyint(1) DEFAULT NULL,
  `interestLevel` varchar(5) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`arBookId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accelerated_reading_titles`
--

LOCK TABLES `accelerated_reading_titles` WRITE;
/*!40000 ALTER TABLE `accelerated_reading_titles` DISABLE KEYS */;
/*!40000 ALTER TABLE `accelerated_reading_titles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `account_profiles`
--

DROP TABLE IF EXISTS `account_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ils',
  `driver` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `loginConfiguration` enum('barcode_pin','name_barcode') COLLATE utf8mb4_general_ci NOT NULL,
  `authenticationMethod` enum('ils','sip2','db','ldap') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ils',
  `vendorOpacUrl` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `patronApiUrl` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `recordSource` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `weight` int(11) NOT NULL,
  `databaseHost` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `databaseName` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `databaseUser` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `databasePassword` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sipHost` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sipPort` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sipUser` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sipPassword` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `databasePort` varchar(5) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `databaseTimezone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `oAuthClientId` varchar(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `oAuthClientSecret` varchar(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ils` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'koha',
  `apiVersion` varchar(10) COLLATE utf8mb4_general_ci DEFAULT '',
  `staffUsername` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `staffPassword` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `workstationId` varchar(10) COLLATE utf8mb4_general_ci DEFAULT '',
  `domain` varchar(100) COLLATE utf8mb4_general_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_profiles`
--

LOCK TABLES `account_profiles` WRITE;
/*!40000 ALTER TABLE `account_profiles` DISABLE KEYS */;
INSERT INTO `account_profiles` VALUES (1,'admin','Library','barcode_pin','db','defaultURL','defaultURL','admin',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'library','',NULL,NULL,'','');
/*!40000 ALTER TABLE `account_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `amazon_ses_settings`
--

DROP TABLE IF EXISTS `amazon_ses_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `amazon_ses_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fromAddress` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `replyToAddress` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `accessKeyId` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `accessKeySecret` varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `singleMailConfigSet` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bulkMailConfigSet` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `region` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=16384;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `amazon_ses_settings`
--

LOCK TABLES `amazon_ses_settings` WRITE;
/*!40000 ALTER TABLE `amazon_ses_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `amazon_ses_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `api_usage`
--

DROP TABLE IF EXISTS `api_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `instance` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `module` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `method` varchar(75) COLLATE utf8mb4_general_ci NOT NULL,
  `numCalls` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `year` (`year`,`month`,`module`,`method`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=655;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_usage`
--

LOCK TABLES `api_usage` WRITE;
/*!40000 ALTER TABLE `api_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `api_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `archive_requests`
--

DROP TABLE IF EXISTS `archive_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `archive_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `address` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address2` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `city` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `state` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `zip` varchar(12) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `country` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alternatePhone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `format` longtext COLLATE utf8mb4_general_ci,
  `purpose` longtext COLLATE utf8mb4_general_ci,
  `pid` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dateRequested` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `archive_requests`
--

LOCK TABLES `archive_requests` WRITE;
/*!40000 ALTER TABLE `archive_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `archive_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `aspen_sites`
--

DROP TABLE IF EXISTS `aspen_sites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `aspen_sites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `baseUrl` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `siteType` int(11) DEFAULT '0',
  `libraryType` int(11) DEFAULT '0',
  `libraryServes` int(11) DEFAULT '0',
  `implementationStatus` int(11) DEFAULT '0',
  `hosting` varchar(75) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `operatingSystem` varchar(75) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `internalServerName` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `appAccess` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `baseUrl` (`baseUrl`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=315;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `aspen_sites`
--

LOCK TABLES `aspen_sites` WRITE;
/*!40000 ALTER TABLE `aspen_sites` DISABLE KEYS */;
/*!40000 ALTER TABLE `aspen_sites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `aspen_usage`
--

DROP TABLE IF EXISTS `aspen_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `aspen_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `pageViews` int(11) DEFAULT '0',
  `pageViewsByBots` int(11) DEFAULT '0',
  `pageViewsByAuthenticatedUsers` int(11) DEFAULT '0',
  `pagesWithErrors` int(11) DEFAULT '0',
  `ajaxRequests` int(11) DEFAULT '0',
  `coverViews` int(11) DEFAULT '0',
  `genealogySearches` int(11) DEFAULT '0',
  `groupedWorkSearches` int(11) DEFAULT '0',
  `islandoraSearches` int(11) DEFAULT '0',
  `openArchivesSearches` int(11) DEFAULT '0',
  `userListSearches` int(11) DEFAULT '0',
  `websiteSearches` int(11) DEFAULT '0',
  `eventsSearches` int(11) DEFAULT '0',
  `blockedRequests` int(11) DEFAULT '0',
  `blockedApiRequests` int(11) DEFAULT '0',
  `ebscoEdsSearches` int(11) DEFAULT '0',
  `instance` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sessionsStarted` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `instance` (`instance`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=630;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `aspen_usage`
--

LOCK TABLES `aspen_usage` WRITE;
/*!40000 ALTER TABLE `aspen_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `aspen_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `author_authorities`
--

DROP TABLE IF EXISTS `author_authorities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `author_authorities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `originalName` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `authoritativeName` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `originalName` (`originalName`),
  KEY `authoritativeName` (`authoritativeName`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=819;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `author_authorities`
--

LOCK TABLES `author_authorities` WRITE;
/*!40000 ALTER TABLE `author_authorities` DISABLE KEYS */;
/*!40000 ALTER TABLE `author_authorities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `author_authority`
--

DROP TABLE IF EXISTS `author_authority`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `author_authority` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `author` varchar(512) COLLATE utf8mb4_general_ci NOT NULL,
  `dateAdded` int(11) DEFAULT NULL,
  `normalized` varchar(512) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `author` (`author`)
) ENGINE=InnoDB AUTO_INCREMENT=20238 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=78;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `author_authority`
--

LOCK TABLES `author_authority` WRITE;
/*!40000 ALTER TABLE `author_authority` DISABLE KEYS */;
/*!40000 ALTER TABLE `author_authority` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `author_authority_alternative`
--

DROP TABLE IF EXISTS `author_authority_alternative`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `author_authority_alternative` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `authorId` int(11) DEFAULT NULL,
  `alternativeAuthor` varchar(512) COLLATE utf8mb4_general_ci NOT NULL,
  `normalized` varchar(512) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `alternativeAuthor` (`alternativeAuthor`),
  KEY `authorId` (`authorId`)
) ENGINE=InnoDB AUTO_INCREMENT=39979 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=95;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `author_authority_alternative`
--

LOCK TABLES `author_authority_alternative` WRITE;
/*!40000 ALTER TABLE `author_authority_alternative` DISABLE KEYS */;
/*!40000 ALTER TABLE `author_authority_alternative` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `author_enrichment`
--

DROP TABLE IF EXISTS `author_enrichment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `author_enrichment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `authorName` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `hideWikipedia` tinyint(1) DEFAULT NULL,
  `wikipediaUrl` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `authorName` (`authorName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `author_enrichment`
--

LOCK TABLES `author_enrichment` WRITE;
/*!40000 ALTER TABLE `author_enrichment` DISABLE KEYS */;
/*!40000 ALTER TABLE `author_enrichment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `axis360_export_log`
--

DROP TABLE IF EXISTS `axis360_export_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `axis360_export_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `notes` mediumtext COLLATE utf8mb4_general_ci COMMENT 'Additional information about the run',
  `numProducts` int(11) DEFAULT '0',
  `numErrors` int(11) DEFAULT '0',
  `numAdded` int(11) DEFAULT '0',
  `numDeleted` int(11) DEFAULT '0',
  `numUpdated` int(11) DEFAULT '0',
  `numAvailabilityChanges` int(11) DEFAULT '0',
  `numMetadataChanges` int(11) DEFAULT '0',
  `settingId` int(11) DEFAULT NULL,
  `numSkipped` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=1024;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `axis360_export_log`
--

LOCK TABLES `axis360_export_log` WRITE;
/*!40000 ALTER TABLE `axis360_export_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `axis360_export_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `axis360_record_usage`
--

DROP TABLE IF EXISTS `axis360_record_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `axis360_record_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `axis360Id` int(11) DEFAULT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `timesHeld` int(11) NOT NULL DEFAULT '0',
  `timesCheckedOut` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `instance` (`instance`,`axis360Id`,`year`,`month`),
  KEY `instance_2` (`instance`,`year`,`month`),
  KEY `instance_3` (`instance`,`year`,`month`),
  KEY `instance_4` (`instance`,`axis360Id`,`year`,`month`),
  KEY `instance_5` (`instance`,`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=1489;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `axis360_record_usage`
--

LOCK TABLES `axis360_record_usage` WRITE;
/*!40000 ALTER TABLE `axis360_record_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `axis360_record_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `axis360_scopes`
--

DROP TABLE IF EXISTS `axis360_scopes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `axis360_scopes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `settingId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=8192;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `axis360_scopes`
--

LOCK TABLES `axis360_scopes` WRITE;
/*!40000 ALTER TABLE `axis360_scopes` DISABLE KEYS */;
/*!40000 ALTER TABLE `axis360_scopes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `axis360_settings`
--

DROP TABLE IF EXISTS `axis360_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `axis360_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `apiUrl` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `userInterfaceUrl` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vendorUsername` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vendorPassword` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `libraryPrefix` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `runFullUpdate` tinyint(1) DEFAULT '0',
  `lastUpdateOfChangedRecords` int(11) DEFAULT '0',
  `lastUpdateOfAllRecords` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=8192;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `axis360_settings`
--

LOCK TABLES `axis360_settings` WRITE;
/*!40000 ALTER TABLE `axis360_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `axis360_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `axis360_stats`
--

DROP TABLE IF EXISTS `axis360_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `axis360_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `numCheckouts` int(11) NOT NULL DEFAULT '0',
  `numRenewals` int(11) NOT NULL DEFAULT '0',
  `numEarlyReturns` int(11) NOT NULL DEFAULT '0',
  `numHoldsPlaced` int(11) NOT NULL DEFAULT '0',
  `numHoldsCancelled` int(11) NOT NULL DEFAULT '0',
  `numHoldsFrozen` int(11) NOT NULL DEFAULT '0',
  `numHoldsThawed` int(11) NOT NULL DEFAULT '0',
  `numApiErrors` int(11) NOT NULL DEFAULT '0',
  `numConnectionFailures` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `instance` (`instance`,`year`,`month`),
  KEY `instance_2` (`instance`,`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=1638;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `axis360_stats`
--

LOCK TABLES `axis360_stats` WRITE;
/*!40000 ALTER TABLE `axis360_stats` DISABLE KEYS */;
/*!40000 ALTER TABLE `axis360_stats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `axis360_title`
--

DROP TABLE IF EXISTS `axis360_title`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `axis360_title` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `axis360Id` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `isbn` varchar(13) COLLATE utf8mb4_general_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `subtitle` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `primaryAuthor` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `formatType` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rawChecksum` bigint(20) DEFAULT NULL,
  `rawResponse` longtext COLLATE utf8mb4_general_ci,
  `dateFirstDetected` bigint(20) DEFAULT NULL,
  `lastChange` int(11) NOT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `axis360Id` (`axis360Id`),
  KEY `lastChange` (`lastChange`)
) ENGINE=InnoDB AUTO_INCREMENT=241 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=1302;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `axis360_title`
--

LOCK TABLES `axis360_title` WRITE;
/*!40000 ALTER TABLE `axis360_title` DISABLE KEYS */;
/*!40000 ALTER TABLE `axis360_title` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `axis360_title_availability`
--

DROP TABLE IF EXISTS `axis360_title_availability`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `axis360_title_availability` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titleId` int(11) DEFAULT NULL,
  `libraryPrefix` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ownedQty` int(11) DEFAULT NULL,
  `totalHolds` int(11) DEFAULT NULL,
  `settingId` int(11) DEFAULT NULL,
  `rawChecksum` bigint(20) DEFAULT NULL,
  `rawResponse` longtext COLLATE utf8mb4_general_ci,
  `lastChange` int(11) NOT NULL,
  `available` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `titleId` (`titleId`,`settingId`),
  KEY `libraryPrefix` (`libraryPrefix`),
  KEY `titleId2` (`titleId`)
) ENGINE=InnoDB AUTO_INCREMENT=879 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=651;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `axis360_title_availability`
--

LOCK TABLES `axis360_title_availability` WRITE;
/*!40000 ALTER TABLE `axis360_title_availability` DISABLE KEYS */;
/*!40000 ALTER TABLE `axis360_title_availability` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bad_words`
--

DROP TABLE IF EXISTS `bad_words`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bad_words` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique Id for bad_word',
  `word` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'The bad word that will be replaced',
  `replacement` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'A replacement value for the word.',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores information about bad_words that should be removed fr';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bad_words`
--

LOCK TABLES `bad_words` WRITE;
/*!40000 ALTER TABLE `bad_words` DISABLE KEYS */;
/*!40000 ALTER TABLE `bad_words` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bookcover_info`
--

DROP TABLE IF EXISTS `bookcover_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bookcover_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recordType` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `recordId` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `firstLoaded` int(11) NOT NULL,
  `lastUsed` int(11) NOT NULL,
  `imageSource` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sourceWidth` int(11) DEFAULT NULL,
  `sourceHeight` int(11) DEFAULT NULL,
  `thumbnailLoaded` tinyint(1) DEFAULT '0',
  `mediumLoaded` tinyint(1) DEFAULT '0',
  `largeLoaded` tinyint(1) DEFAULT '0',
  `uploadedImage` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `record_info` (`recordType`,`recordId`),
  KEY `imageSource` (`imageSource`),
  KEY `lastUsed` (`lastUsed`)
) ENGINE=InnoDB AUTO_INCREMENT=9082 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=215;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookcover_info`
--

LOCK TABLES `bookcover_info` WRITE;
/*!40000 ALTER TABLE `bookcover_info` DISABLE KEYS */;
/*!40000 ALTER TABLE `bookcover_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `browse_category`
--

DROP TABLE IF EXISTS `browse_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `browse_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `textId` varchar(60) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '-1',
  `userId` int(11) DEFAULT NULL,
  `sharing` enum('private','location','library','everyone') COLLATE utf8mb4_general_ci DEFAULT 'everyone',
  `label` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_general_ci,
  `defaultFilter` mediumtext COLLATE utf8mb4_general_ci,
  `defaultSort` enum('relevance','popularity','newest_to_oldest','oldest_to_newest','author','title','user_rating','holds','publication_year_desc','publication_year_asc') COLLATE utf8mb4_general_ci DEFAULT 'relevance',
  `searchTerm` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `numTimesShown` mediumint(9) NOT NULL DEFAULT '0',
  `numTitlesClickedOn` mediumint(9) NOT NULL DEFAULT '0',
  `sourceListId` mediumint(9) DEFAULT NULL,
  `source` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `libraryId` int(11) DEFAULT '-1',
  `startDate` int(11) DEFAULT '0',
  `endDate` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `textId` (`textId`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `browse_category`
--

LOCK TABLES `browse_category` WRITE;
/*!40000 ALTER TABLE `browse_category` DISABLE KEYS */;
INSERT INTO `browse_category` VALUES (1,'main_new_fiction',1,'everyone','New Fiction','','literary_form:Fiction','newest_to_oldest','',2,0,-1,'GroupedWork',-1,0,0),(2,'main_new_non_fiction',1,'everyone','New Non Fiction','','literary_form:Non Fiction','newest_to_oldest','',0,0,-1,'GroupedWork',-1,0,0);
/*!40000 ALTER TABLE `browse_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `browse_category_group`
--

DROP TABLE IF EXISTS `browse_category_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `browse_category_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `defaultBrowseMode` tinyint(1) DEFAULT '0',
  `browseCategoryRatingsMode` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `browse_category_group`
--

LOCK TABLES `browse_category_group` WRITE;
/*!40000 ALTER TABLE `browse_category_group` DISABLE KEYS */;
INSERT INTO browse_category_group (id, name) VALUES (1, 'Main Library');
/*!40000 ALTER TABLE `browse_category_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `browse_category_group_entry`
--

DROP TABLE IF EXISTS `browse_category_group_entry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `browse_category_group_entry` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `browseCategoryGroupId` int(11) NOT NULL,
  `browseCategoryId` int(11) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `browseCategoryGroupId` (`browseCategoryGroupId`,`browseCategoryId`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=1092;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `browse_category_group_entry`
--

LOCK TABLES `browse_category_group_entry` WRITE;
/*!40000 ALTER TABLE `browse_category_group_entry` DISABLE KEYS */;
INSERT INTO browse_category_group_entry (browseCategoryGroupId, browseCategoryId) VALUES (1, 1);
INSERT INTO browse_category_group_entry (browseCategoryGroupId, browseCategoryId) VALUES (1, 2);
/*!40000 ALTER TABLE `browse_category_group_entry` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `browse_category_library`
--

DROP TABLE IF EXISTS `browse_category_library`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `browse_category_library` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `browseCategoryTextId` varchar(60) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '-1',
  `weight` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `libraryId` (`libraryId`,`browseCategoryTextId`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `browse_category_library`
--

LOCK TABLES `browse_category_library` WRITE;
/*!40000 ALTER TABLE `browse_category_library` DISABLE KEYS */;
INSERT INTO `browse_category_library` VALUES (1,2,'main_new_fiction',0),(2,2,'main_new_non_fiction',0);
/*!40000 ALTER TABLE `browse_category_library` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `browse_category_location`
--

DROP TABLE IF EXISTS `browse_category_location`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `browse_category_location` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `locationId` int(11) NOT NULL,
  `browseCategoryTextId` varchar(60) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '-1',
  `weight` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `locationId` (`locationId`,`browseCategoryTextId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `browse_category_location`
--

LOCK TABLES `browse_category_location` WRITE;
/*!40000 ALTER TABLE `browse_category_location` DISABLE KEYS */;
/*!40000 ALTER TABLE `browse_category_location` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `browse_category_subcategories`
--

DROP TABLE IF EXISTS `browse_category_subcategories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `browse_category_subcategories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `browseCategoryId` int(11) NOT NULL,
  `subCategoryId` int(11) NOT NULL,
  `weight` smallint(2) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `subCategoryId` (`subCategoryId`,`browseCategoryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `browse_category_subcategories`
--

LOCK TABLES `browse_category_subcategories` WRITE;
/*!40000 ALTER TABLE `browse_category_subcategories` DISABLE KEYS */;
/*!40000 ALTER TABLE `browse_category_subcategories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cached_values`
--

DROP TABLE IF EXISTS `cached_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cached_values` (
  `cacheKey` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `value` varchar(16000) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `expirationTime` int(11) DEFAULT NULL,
  UNIQUE KEY `cacheKey` (`cacheKey`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=64809;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cached_values`
--

LOCK TABLES `cached_values` WRITE;
/*!40000 ALTER TABLE `cached_values` DISABLE KEYS */;
/*!40000 ALTER TABLE `cached_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `claim_authorship_requests`
--

DROP TABLE IF EXISTS `claim_authorship_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `claim_authorship_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `message` longtext COLLATE utf8mb4_general_ci,
  `pid` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dateRequested` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `claim_authorship_requests`
--

LOCK TABLES `claim_authorship_requests` WRITE;
/*!40000 ALTER TABLE `claim_authorship_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `claim_authorship_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cloud_library_availability`
--

DROP TABLE IF EXISTS `cloud_library_availability`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cloud_library_availability` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cloudLibraryId` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `totalCopies` smallint(6) NOT NULL DEFAULT '0',
  `sharedCopies` smallint(6) NOT NULL DEFAULT '0',
  `totalLoanCopies` smallint(6) NOT NULL DEFAULT '0',
  `totalHoldCopies` smallint(6) NOT NULL DEFAULT '0',
  `sharedLoanCopies` smallint(6) NOT NULL DEFAULT '0',
  `rawChecksum` bigint(20) DEFAULT NULL,
  `rawResponse` longtext COLLATE utf8mb4_general_ci,
  `lastChange` int(11) NOT NULL,
  `settingId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cloudLibraryId` (`cloudLibraryId`,`settingId`),
  KEY `lastChange` (`lastChange`)
) ENGINE=InnoDB AUTO_INCREMENT=156930 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=880;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cloud_library_availability`
--

LOCK TABLES `cloud_library_availability` WRITE;
/*!40000 ALTER TABLE `cloud_library_availability` DISABLE KEYS */;
/*!40000 ALTER TABLE `cloud_library_availability` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cloud_library_export_log`
--

DROP TABLE IF EXISTS `cloud_library_export_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cloud_library_export_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `notes` mediumtext COLLATE utf8mb4_general_ci COMMENT 'Additional information about the run',
  `numProducts` int(11) DEFAULT '0',
  `numErrors` int(11) DEFAULT '0',
  `numAdded` int(11) DEFAULT '0',
  `numDeleted` int(11) DEFAULT '0',
  `numUpdated` int(11) DEFAULT '0',
  `numAvailabilityChanges` int(11) DEFAULT '0',
  `numMetadataChanges` int(11) DEFAULT '0',
  `settingId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=586 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=1820;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cloud_library_export_log`
--

LOCK TABLES `cloud_library_export_log` WRITE;
/*!40000 ALTER TABLE `cloud_library_export_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `cloud_library_export_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cloud_library_record_usage`
--

DROP TABLE IF EXISTS `cloud_library_record_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cloud_library_record_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cloudLibraryId` int(11) DEFAULT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `timesHeld` int(11) NOT NULL,
  `timesCheckedOut` int(11) NOT NULL,
  `instance` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`cloudLibraryId`,`year`,`month`),
  UNIQUE KEY `instance_2` (`instance`,`cloudLibraryId`,`year`,`month`),
  UNIQUE KEY `instance_3` (`instance`,`cloudLibraryId`,`year`,`month`),
  KEY `year` (`year`,`month`),
  KEY `year_2` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=1489;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cloud_library_record_usage`
--

LOCK TABLES `cloud_library_record_usage` WRITE;
/*!40000 ALTER TABLE `cloud_library_record_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `cloud_library_record_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cloud_library_scopes`
--

DROP TABLE IF EXISTS `cloud_library_scopes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cloud_library_scopes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `includeEBooks` tinyint(4) DEFAULT '1',
  `includeEAudiobook` tinyint(4) DEFAULT '1',
  `restrictToChildrensMaterial` tinyint(4) DEFAULT '0',
  `settingId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=16384;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cloud_library_scopes`
--

LOCK TABLES `cloud_library_scopes` WRITE;
/*!40000 ALTER TABLE `cloud_library_scopes` DISABLE KEYS */;
/*!40000 ALTER TABLE `cloud_library_scopes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cloud_library_settings`
--

DROP TABLE IF EXISTS `cloud_library_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cloud_library_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `apiUrl` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `userInterfaceUrl` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `libraryId` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `accountId` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `accountKey` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `runFullUpdate` tinyint(1) DEFAULT '0',
  `lastUpdateOfChangedRecords` int(11) DEFAULT '0',
  `lastUpdateOfAllRecords` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=16384;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cloud_library_settings`
--

LOCK TABLES `cloud_library_settings` WRITE;
/*!40000 ALTER TABLE `cloud_library_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `cloud_library_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cloud_library_title`
--

DROP TABLE IF EXISTS `cloud_library_title`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cloud_library_title` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cloudLibraryId` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `subTitle` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `author` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `format` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rawChecksum` bigint(20) DEFAULT NULL,
  `rawResponse` longtext COLLATE utf8mb4_general_ci,
  `dateFirstDetected` bigint(20) DEFAULT NULL,
  `lastChange` int(11) NOT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `cloudLibraryId` (`cloudLibraryId`),
  KEY `lastChange` (`lastChange`)
) ENGINE=InnoDB AUTO_INCREMENT=189313 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=3500;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cloud_library_title`
--

LOCK TABLES `cloud_library_title` WRITE;
/*!40000 ALTER TABLE `cloud_library_title` DISABLE KEYS */;
/*!40000 ALTER TABLE `cloud_library_title` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coce_settings`
--

DROP TABLE IF EXISTS `coce_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coce_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `coceServerUrl` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=16384;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coce_settings`
--

LOCK TABLES `coce_settings` WRITE;
/*!40000 ALTER TABLE `coce_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `coce_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `collection_spotlight_lists`
--

DROP TABLE IF EXISTS `collection_spotlight_lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `collection_spotlight_lists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collectionSpotlightId` int(11) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT '0',
  `displayFor` enum('all','loggedIn','notLoggedIn') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'all',
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `source` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `fullListLink` varchar(500) COLLATE utf8mb4_general_ci DEFAULT '',
  `defaultFilter` mediumtext COLLATE utf8mb4_general_ci,
  `defaultSort` enum('relevance','popularity','newest_to_oldest','oldest_to_newest','author','title','user_rating','holds','publication_year_desc','publication_year_asc') COLLATE utf8mb4_general_ci DEFAULT 'relevance',
  `searchTerm` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `sourceListId` mediumint(9) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ListWidgetId` (`collectionSpotlightId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=3276 COMMENT='The lists that should appear within the widget';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collection_spotlight_lists`
--

LOCK TABLES `collection_spotlight_lists` WRITE;
/*!40000 ALTER TABLE `collection_spotlight_lists` DISABLE KEYS */;
/*!40000 ALTER TABLE `collection_spotlight_lists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `collection_spotlights`
--

DROP TABLE IF EXISTS `collection_spotlights`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `collection_spotlights` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `description` mediumtext COLLATE utf8mb4_general_ci,
  `showTitleDescriptions` tinyint(4) DEFAULT '1',
  `onSelectCallback` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '',
  `customCss` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `listDisplayType` enum('tabs','dropdown') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'tabs',
  `autoRotate` tinyint(4) NOT NULL DEFAULT '0',
  `showMultipleTitles` tinyint(4) NOT NULL DEFAULT '1',
  `libraryId` int(11) NOT NULL DEFAULT '-1',
  `style` enum('vertical','horizontal','single','single-with-next','text-list','horizontal-carousel') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'horizontal',
  `coverSize` enum('small','medium') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'small',
  `showRatings` tinyint(4) NOT NULL DEFAULT '0',
  `showTitle` tinyint(4) NOT NULL DEFAULT '1',
  `showAuthor` tinyint(4) NOT NULL DEFAULT '1',
  `showViewMoreLink` tinyint(4) NOT NULL DEFAULT '0',
  `viewMoreLinkMode` enum('covers','list') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'list',
  `showSpotlightTitle` tinyint(4) NOT NULL DEFAULT '1',
  `numTitlesToShow` int(11) NOT NULL DEFAULT '25',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=4096 COMMENT='A widget that can be displayed within Pika or within other sites';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collection_spotlights`
--

LOCK TABLES `collection_spotlights` WRITE;
/*!40000 ALTER TABLE `collection_spotlights` DISABLE KEYS */;
/*!40000 ALTER TABLE `collection_spotlights` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comprise_settings`
--

DROP TABLE IF EXISTS `comprise_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comprise_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customerName` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `customerId` int(11) DEFAULT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customerId` (`customerId`),
  UNIQUE KEY `customerName` (`customerName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comprise_settings`
--

LOCK TABLES `comprise_settings` WRITE;
/*!40000 ALTER TABLE `comprise_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `comprise_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contentcafe_settings`
--

DROP TABLE IF EXISTS `contentcafe_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contentcafe_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contentCafeId` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `pwd` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `hasSummary` tinyint(1) DEFAULT '1',
  `hasToc` tinyint(1) DEFAULT '0',
  `hasExcerpt` tinyint(1) DEFAULT '0',
  `hasAuthorNotes` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contentcafe_settings`
--

LOCK TABLES `contentcafe_settings` WRITE;
/*!40000 ALTER TABLE `contentcafe_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `contentcafe_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cron_log`
--

DROP TABLE IF EXISTS `cron_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cron_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of the cron log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the cron run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the cron run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the cron run last updated (to check for stuck processes)',
  `notes` mediumtext COLLATE utf8mb4_general_ci COMMENT 'Additional information about the cron run',
  `numErrors` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cron_log`
--

LOCK TABLES `cron_log` WRITE;
/*!40000 ALTER TABLE `cron_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `cron_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cron_process_log`
--

DROP TABLE IF EXISTS `cron_process_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cron_process_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of cron process',
  `cronId` int(11) NOT NULL COMMENT 'The id of the cron run this process ran during',
  `processName` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'The name of the process being run',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the process started',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the process last updated (to check for stuck processes)',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the process ended',
  `numErrors` int(11) NOT NULL DEFAULT '0' COMMENT 'The number of errors that occurred during the process',
  `numUpdates` int(11) NOT NULL DEFAULT '0' COMMENT 'The number of updates, additions, etc. that occurred',
  `notes` mediumtext COLLATE utf8mb4_general_ci COMMENT 'Additional information about the process',
  `numSkipped` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `cronId` (`cronId`),
  KEY `processName` (`processName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cron_process_log`
--

LOCK TABLES `cron_process_log` WRITE;
/*!40000 ALTER TABLE `cron_process_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `cron_process_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `db_update`
--

DROP TABLE IF EXISTS `db_update`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `db_update` (
  `update_key` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `date_run` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`update_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `db_update`
--

LOCK TABLES `db_update` WRITE;
/*!40000 ALTER TABLE `db_update` DISABLE KEYS */;
INSERT INTO `db_update` VALUES ('accelerated_reader','2021-10-15 13:10:31'),('account_profiles_1','2019-01-28 20:59:02'),('account_profiles_2','2021-10-15 13:10:27'),('account_profiles_3','2021-10-15 13:10:27'),('account_profiles_4','2021-10-15 13:10:27'),('account_profiles_5','2021-10-15 13:10:27'),('account_profiles_admin_login_configuration','2021-10-15 13:10:27'),('account_profiles_api_version','2021-10-15 13:10:27'),('account_profiles_domain','2021-10-15 13:10:27'),('account_profiles_ils','2021-10-15 13:10:27'),('account_profiles_oauth','2021-10-15 13:10:27'),('account_profiles_staff_information','2021-10-15 13:10:27'),('account_profiles_workstation_id','2021-10-15 13:10:27'),('acsLog','2011-12-13 16:04:23'),('addDefaultCatPassword','2021-10-15 13:11:02'),('addGeolocation','2021-10-15 13:11:02'),('addGreenhouseUrl','2021-10-15 13:10:57'),('additionalTranslationTermInfo','2021-10-15 13:10:57'),('additional_index_logging','2021-10-15 13:10:55'),('additional_library_contact_links','2019-01-28 20:58:56'),('additional_locations_for_availability','2019-01-28 20:58:56'),('addReleaseChannelToCachedGreenhouseData','2021-10-15 13:11:02'),('addSettingIdToAxis360Scopes','2021-10-15 13:10:37'),('addSiteIdToCachedGreenhouseData','2021-10-15 13:11:02'),('addTablelistWidgetListsLinks','2019-01-28 20:59:01'),('addThemeToCachedGreenhouseData','2021-10-15 13:11:21'),('add_browseLinkText_to_layout_settings','2021-10-15 13:10:50'),('add_colors_to_web_builder','2021-10-15 13:10:56'),('add_displayItemBarcode','2021-10-15 13:10:56'),('add_error_to_user_payments','2021-10-15 13:10:56'),('add_footerLogoAlt','2021-10-15 13:10:55'),('add_indexes','2019-01-28 20:59:01'),('add_indexes2','2019-01-28 20:59:01'),('add_library_links_access','2021-10-15 13:10:56'),('add_makeAccordion_to_portalRow','2021-10-15 13:10:50'),('add_maxDaysToFreeze','2021-10-15 13:10:56'),('add_records_to_delete_for_sideloads','2021-10-15 13:10:55'),('add_referencecover_groupedwork','2021-10-15 13:10:50'),('add_requireLogin_to_basic_page','2021-10-15 13:10:56'),('add_requireLogin_to_portal_page','2021-10-15 13:10:56'),('add_search_source_to_saved_searches','2019-01-28 20:59:02'),('add_search_url_to_saved_searches','2021-10-15 13:10:49'),('add_settings_axis360_exportLog','2021-10-15 13:10:37'),('add_settings_cloud_library_exportLog','2021-10-15 13:10:47'),('add_showBookIcon_to_layout_settings','2021-10-15 13:10:50'),('add_sms_indicator_to_phone','2019-01-28 20:58:56'),('add_sorts_for_browsable_objects','2021-10-15 13:10:56'),('add_titles_to_user_list_entry','2021-10-15 13:10:51'),('add_title_user_list_entry','2021-10-15 13:10:51'),('add_useHomeLink_to_layout_settings','2021-10-15 13:10:50'),('add_web_builder_basic_page_access','2021-10-15 13:10:56'),('add_web_builder_portal_page_access','2021-10-15 13:10:56'),('administer_host_permissions','2021-10-15 13:10:29'),('allow_anyone_to_view_documentation','2021-10-15 13:10:29'),('allow_masquerade_mode','2019-01-28 20:58:56'),('allow_reading_history_display_in_masquerade_mode','2019-01-28 20:58:56'),('alpha_browse_setup_2','2019-01-28 20:58:59'),('alpha_browse_setup_3','2019-01-28 20:58:59'),('alpha_browse_setup_4','2019-01-28 20:58:59'),('alpha_browse_setup_5','2019-01-28 20:58:59'),('alpha_browse_setup_6','2019-01-28 20:59:00'),('alpha_browse_setup_7','2019-01-28 20:59:00'),('alpha_browse_setup_8','2019-01-28 20:59:00'),('alpha_browse_setup_9','2019-01-28 20:59:01'),('always_show_search_results_Main_details','2019-01-28 20:58:56'),('amazon_ses','2021-10-15 13:10:51'),('analytics','2019-01-28 20:59:01'),('analytics_1','2019-01-28 20:59:01'),('analytics_2','2019-01-28 20:59:01'),('analytics_3','2019-01-28 20:59:01'),('analytics_4','2019-01-28 20:59:01'),('analytics_5','2019-01-28 20:59:02'),('analytics_6','2019-01-28 20:59:02'),('analytics_7','2019-01-28 20:59:02'),('analytics_8','2019-01-28 20:59:02'),('api_usage_stats','2021-10-15 13:10:50'),('appReleaseChannel','2021-10-15 13:11:02'),('archivesRole','2019-01-28 20:58:59'),('archive_collection_default_view_mode','2019-01-28 20:58:56'),('archive_filtering','2019-01-28 20:58:56'),('archive_more_details_customization','2019-01-28 20:58:56'),('archive_object_filtering','2019-01-28 20:58:56'),('archive_private_collections','2019-01-28 20:59:02'),('archive_requests','2019-01-28 20:59:02'),('archive_subjects','2019-01-28 20:59:02'),('aspen_sites','2021-10-15 13:10:56'),('aspen_site_internal_name','2021-10-15 13:10:56'),('aspen_usage','2021-10-15 13:10:50'),('aspen_usage_add_sessions','2021-10-15 13:10:50'),('aspen_usage_blocked_requests','2021-10-15 13:10:50'),('aspen_usage_ebsco_eds','2021-10-15 13:10:37'),('aspen_usage_events','2021-10-15 13:10:48'),('aspen_usage_instance','2021-10-15 13:10:50'),('aspen_usage_remove_slow_pages','2021-10-15 13:10:50'),('aspen_usage_websites','2021-10-15 13:10:50'),('authentication_profiles','2019-01-28 20:59:02'),('authorities','2021-10-15 13:10:31'),('author_authorities','2021-10-15 13:10:30'),('author_authorities_normalized_values','2021-10-15 13:10:30'),('author_enrichment','2019-01-28 20:58:59'),('availability_toggle_customization','2019-01-28 20:58:56'),('axis360AddSettings','2021-10-15 13:10:37'),('axis360Title','2021-10-15 13:10:37'),('axis360_add_response_info_to_availability','2021-10-15 13:10:37'),('axis360_add_setting_to_availability','2021-10-15 13:10:37'),('axis360_availability_indexes','2021-10-15 13:10:37'),('axis360_availability_remove_unused_fields','2021-10-15 13:10:37'),('axis360_availability_update_for_new_method','2021-10-15 13:10:37'),('axis360_exportLog','2021-10-15 13:10:37'),('axis360_exportLog_num_skipped','2021-10-15 13:10:37'),('axis360_stats_index','2021-10-15 13:10:37'),('bookcover_info','2021-10-15 13:10:49'),('book_store','2019-01-28 20:59:01'),('book_store_1','2019-01-28 20:59:01'),('boost_disabling','2019-01-28 20:59:01'),('browse_categories','2019-01-28 20:59:02'),('browse_categories_add_startDate_endDate','2021-10-15 13:10:50'),('browse_categories_lists','2019-01-28 20:59:02'),('browse_categories_search_term_and_stats','2019-01-28 20:59:02'),('browse_categories_search_term_length','2019-01-28 20:59:02'),('browse_category_default_view_mode','2019-01-28 20:58:56'),('browse_category_groups','2021-10-15 13:10:30'),('browse_category_library_updates','2021-10-15 13:10:30'),('browse_category_ratings_mode','2019-01-28 20:58:56'),('browse_category_source','2021-10-15 13:10:30'),('cached_value_case_sensitive','2021-10-15 13:10:50'),('cacheGreenhouseData','2021-10-15 13:11:02'),('catalogingRole','2019-01-28 20:58:59'),('change_to_innodb','2019-03-05 18:07:51'),('check_titles_in_user_list_entries','2021-10-15 13:10:56'),('claim_authorship_requests','2019-01-28 20:59:02'),('cleanup_invalid_reading_history_entries','2021-10-15 13:10:29'),('clear_analytics','2019-01-28 20:59:02'),('cloud_library_add_scope_setting_id','2021-10-15 13:10:47'),('cloud_library_add_settings','2021-10-15 13:10:47'),('cloud_library_add_setting_to_availability','2021-10-15 13:10:47'),('cloud_library_availability','2021-10-15 13:10:47'),('cloud_library_cleanup_availability_with_settings','2021-10-15 13:10:47'),('cloud_library_exportLog','2021-10-15 13:10:47'),('cloud_library_exportTable','2021-10-15 13:10:47'),('cloud_library_increase_allowable_copies','2021-10-15 13:10:47'),('cloud_library_module_add_log','2021-10-15 13:10:47'),('cloud_library_multiple_scopes','2021-10-15 13:10:51'),('cloud_library_scoping','2021-10-15 13:10:47'),('cloud_library_settings','2021-10-15 13:10:47'),('cloud_library_usage_add_instance','2021-10-15 13:10:47'),('coce_settings','2021-10-15 13:10:50'),('collapse_facets','2019-01-28 20:58:55'),('collection_spotlights_carousel_style','2021-10-15 13:10:31'),('combined_results','2019-01-28 20:58:56'),('compress_hoopla_fields','2021-10-15 13:10:53'),('compress_novelist_fields','2021-10-15 13:10:53'),('compress_overdrive_fields','2021-10-15 13:10:53'),('comprise_link_to_library','2021-10-15 13:10:52'),('comprise_settings','2021-10-15 13:10:52'),('contentcafe_settings','2021-10-15 13:10:50'),('contentEditor','2019-01-28 20:58:59'),('convertOldEContent','2011-11-06 22:58:31'),('convert_to_format_status_maps','2021-10-15 13:10:35'),('coverArt_suppress','2019-01-28 20:58:58'),('createAxis360Module','2021-10-15 13:10:37'),('createAxis360SettingsAndScopes','2021-10-15 13:10:37'),('createEbscoModules','2021-10-15 13:10:36'),('createSearchInterface_libraries_locations','2021-10-15 13:10:54'),('createSettingsForEbscoEDS','2021-10-15 13:10:37'),('create_cloud_library_module','2021-10-15 13:10:47'),('create_events_module','2021-10-15 13:10:48'),('create_field_encryption_file','2021-10-15 13:10:14'),('create_hoopla_module','2021-10-15 13:10:38'),('create_ils_modules','2021-10-15 13:10:35'),('create_nyt_update_log','2021-10-15 13:10:50'),('create_open_archives_module','2021-10-15 13:10:47'),('create_overdrive_module','2021-10-15 13:10:36'),('create_overdrive_scopes','2021-10-15 13:10:36'),('create_plural_grouped_work_facets','2021-10-15 13:10:52'),('create_polaris_module','2021-10-15 13:10:36'),('create_system_variables_table','2021-10-15 13:10:48'),('create_web_indexer_module','2021-10-15 13:10:48'),('cronLog','2019-01-28 20:59:01'),('cron_log_errors','2021-10-15 13:10:49'),('cron_process_skips','2021-10-15 13:10:49'),('currencyCode','2021-10-15 13:10:48'),('defaultAvailabilityToggle','2021-10-15 13:10:25'),('defaultGroupedWorkDisplaySettings','2021-10-15 13:10:25'),('default_library','2019-01-28 20:58:56'),('default_list_indexing','2021-10-15 13:10:49'),('detailed_hold_notice_configuration','2019-01-28 20:58:56'),('disable_auto_correction_of_searches','2019-01-28 20:58:56'),('disable_hoopla_module_auto_restart','2021-10-15 13:10:38'),('display_pika_logo','2019-01-28 20:58:56'),('dpla_api_settings','2021-10-15 13:10:50'),('dpla_integration','2019-01-28 20:58:56'),('ebsco_eds_increase_id_length','2021-10-15 13:10:37'),('ebsco_eds_record_usage','2021-10-15 13:10:37'),('ebsco_eds_research_starters','2021-10-15 13:10:37'),('ebsco_eds_usage_add_instance','2021-10-15 13:10:37'),('ecommerce_report_permissions','2021-10-15 13:10:56'),('eContentCheckout','2011-11-10 23:57:56'),('eContentCheckout_1','2011-12-13 16:04:03'),('eContentHistory','2011-11-15 17:56:44'),('eContentHolds','2011-11-10 22:39:20'),('eContentItem_1','2011-12-04 22:13:19'),('eContentRating','2011-11-16 21:53:43'),('eContentRecord_1','2011-12-01 21:43:54'),('eContentRecord_2','2012-01-11 20:06:48'),('eContentWishList','2011-12-08 20:29:48'),('econtent_attach','2011-12-30 19:12:22'),('econtent_locations_to_include','2019-01-28 20:56:58'),('econtent_marc_import','2011-12-15 22:48:22'),('editorial_review','2019-01-28 20:58:58'),('editorial_review_1','2019-01-28 20:58:58'),('editorial_review_2','2019-01-28 20:58:58'),('edit_placard_permissions','2021-10-15 13:11:02'),('enableAppAccess','2021-10-15 13:11:02'),('enable_archive','2019-01-28 20:58:56'),('encrypt_user_table','2021-10-15 13:10:30'),('error_table','2021-10-15 13:10:50'),('error_table_agent','2021-10-15 13:10:50'),('events_add_settings','2021-10-15 13:10:48'),('events_indexing_log','2021-10-15 13:10:48'),('events_module_log_checks','2021-10-15 13:10:48'),('events_spotlights','2021-10-15 13:10:48'),('event_record_usage','2021-10-15 13:10:48'),('expiration_message','2019-01-28 20:58:56'),('explore_more_configuration','2019-01-28 20:58:56'),('extend_placard_link','2021-10-15 13:10:50'),('externalLinkTracking','2019-01-28 20:58:58'),('external_materials_request','2019-01-28 20:58:56'),('facetLabel_length','2021-10-15 13:10:25'),('facets_add_multi_select','2021-10-15 13:10:19'),('facets_add_translation','2021-10-15 13:10:19'),('facets_locking','2021-10-15 13:10:19'),('facets_remove_author_results','2021-10-15 13:10:19'),('facet_grouping_updates','2019-01-28 20:58:55'),('fileUploadsThumb','2021-10-15 13:10:54'),('file_uploads_table','2021-10-15 13:10:48'),('fix_dates_in_item_details','2021-10-15 13:10:54'),('fix_ils_record_indexes','2021-10-15 13:10:53'),('fix_ils_volume_indexes','2021-10-15 13:10:56'),('fix_sierra_module_background_process','2021-10-15 13:10:35'),('force_reload_of_cloud_library_21_08','2021-10-15 13:10:52'),('force_reload_of_hoopla_21_08','2021-10-15 13:10:52'),('force_reload_of_overdrive_21_08','2021-10-15 13:10:52'),('format_holdType','2021-10-15 13:10:35'),('format_mustPickupAtHoldingBranch','2021-10-15 13:10:35'),('format_status_in_library_use_only','2021-10-15 13:10:35'),('format_status_maps','2021-10-15 13:10:31'),('format_status_suppression','2021-10-15 13:10:35'),('full_record_view_configuration_options','2019-01-28 20:58:56'),('genealogy','2021-10-15 13:10:30'),('genealogy_1','2021-10-15 13:10:30'),('genealogy_lot_length','2021-10-15 13:10:30'),('genealogy_marriage_date_update','2021-10-15 13:10:30'),('genealogy_module','2021-10-15 13:10:30'),('genealogy_nashville_1','2021-10-15 13:10:30'),('genealogy_obituary_date_update','2021-10-15 13:10:30'),('genealogy_person_date_update','2021-10-15 13:10:30'),('goodreads_library_contact_link','2019-01-28 20:58:56'),('google_analytics_version','2021-10-15 13:10:50'),('google_api_settings','2021-10-15 13:10:50'),('google_more_settings','2021-10-15 13:10:50'),('google_remove_google_translate','2021-10-15 13:10:50'),('greenhouse_appAccess','2021-10-15 13:10:57'),('grouped_works','2019-01-28 20:58:56'),('grouped_works_1','2019-01-28 20:58:56'),('grouped_works_2','2019-01-28 20:58:56'),('grouped_works_partial_updates','2019-01-28 20:58:57'),('grouped_works_primary_identifiers','2019-01-28 20:58:56'),('grouped_works_primary_identifiers_1','2019-01-28 20:58:56'),('grouped_works_remove_split_titles','2019-01-28 20:58:56'),('grouped_work_alternate_titles','2021-10-15 13:10:30'),('grouped_work_display_info','2021-10-15 13:10:30'),('grouped_work_display_settings','2021-10-15 13:10:25'),('grouped_work_display_showItemDueDates','2021-10-15 13:10:51'),('grouped_work_duplicate_identifiers','2019-01-28 20:58:57'),('grouped_work_engine','2019-01-28 20:58:57'),('grouped_work_evoke','2019-01-28 20:58:57'),('grouped_work_identifiers_ref_indexing','2019-01-28 20:58:57'),('grouped_work_index_cleanup','2019-01-28 20:58:57'),('grouped_work_index_date_updated','2019-01-28 20:58:57'),('grouped_work_merging','2019-01-28 20:58:57'),('grouped_work_primary_identifiers_hoopla','2019-01-28 20:58:57'),('grouped_work_primary_identifier_types','2019-01-28 20:58:57'),('grouped_work_title_length','2021-10-15 13:10:30'),('header_text','2019-01-28 20:58:56'),('hold_request_confirmations','2021-10-15 13:10:52'),('holiday','2019-01-28 20:59:01'),('holiday_1','2019-01-28 20:59:01'),('hoopla_add_settings','2021-10-15 13:10:38'),('hoopla_add_settings_2','2021-10-15 13:10:38'),('hoopla_add_setting_to_scope','2021-10-15 13:10:38'),('hoopla_exportLog','2019-01-28 20:58:57'),('hoopla_exportLog_skips','2021-10-15 13:10:38'),('hoopla_exportLog_update','2021-10-15 13:10:38'),('hoopla_exportTables','2019-01-28 20:58:57'),('hoopla_export_include_raw_data','2021-10-15 13:10:38'),('hoopla_filter_records_from_other_vendors','2021-10-15 13:10:38'),('hoopla_integration','2019-01-28 20:58:56'),('hoopla_library_options','2019-01-28 20:58:56'),('hoopla_library_options_remove','2019-01-28 20:58:56'),('hoopla_module_add_log','2021-10-15 13:10:38'),('hoopla_regroup_all_records','2021-10-15 13:11:02'),('hoopla_scoping','2021-10-15 13:10:38'),('hoopla_usage_add_instance','2021-10-15 13:10:38'),('horizontal_search_bar','2019-01-28 20:58:56'),('host_information','2021-10-15 13:10:50'),('hours_and_locations_control','2019-01-28 20:58:55'),('htmlForMarkdown','2021-10-15 13:10:48'),('ill_link','2019-01-28 20:58:56'),('ils_code_records_owned_length','2019-01-28 20:58:56'),('ils_exportLog','2021-10-15 13:10:31'),('ils_exportLog_num_regroups','2021-10-15 13:10:31'),('ils_exportLog_skips','2021-10-15 13:10:31'),('ils_hold_summary','2019-01-28 20:58:57'),('ils_marc_checksums','2019-01-28 20:59:02'),('ils_marc_checksum_first_detected','2019-01-28 20:59:02'),('ils_marc_checksum_first_detected_signed','2019-01-28 20:59:02'),('ils_marc_checksum_source','2019-01-28 20:59:02'),('ils_usage_add_instance','2021-10-15 13:10:31'),('increaseGreenhouseDataNameLength','2021-10-15 13:11:02'),('increaseLengthOfShowInMainDetails','2021-10-15 13:10:25'),('increaseSymphonyPaymentTypeAndPolicyLengths','2021-10-15 13:11:21'),('increase_checkout_due_date','2021-10-15 13:10:56'),('increase_ilsID_size_for_ils_marc_checksums','2019-01-28 20:58:57'),('increase_login_form_labels','2019-01-28 20:58:56'),('increase_nonHoldableITypes','2021-10-15 13:11:21'),('increase_scoping_field_lengths','2021-10-15 13:10:54'),('increase_search_url_size','2021-10-15 13:10:49'),('increase_search_url_size_round_2','2021-10-15 13:10:49'),('increase_showInSearchResultsMainDetails_length','2021-10-15 13:10:51'),('increase_volumeId_length','2021-10-15 13:10:54'),('indexed_information_length','2021-10-15 13:10:52'),('indexed_information_publisher_length','2021-10-15 13:10:53'),('indexing_exclude_locations','2021-10-15 13:10:36'),('indexing_includeLocationNameInDetailedLocation','2021-10-15 13:10:36'),('indexing_lastUpdateOfAuthorities','2021-10-15 13:10:36'),('indexing_module_add_log','2021-10-15 13:10:35'),('indexing_module_add_settings2','2021-10-15 13:10:35'),('indexing_profile','2019-01-28 20:58:57'),('indexing_profiles_add_due_date_for_Koha','2021-10-15 13:10:50'),('indexing_profiles_add_notes_subfield','2021-10-15 13:10:50'),('indexing_profiles_date_created_polaris','2021-10-15 13:10:51'),('indexing_profile_add_continuous_update_fields','2021-10-15 13:10:31'),('indexing_profile_audienceSubfield','2021-10-15 13:10:35'),('indexing_profile_catalog_driver','2019-01-28 20:58:57'),('indexing_profile_collection','2019-01-28 20:58:57'),('indexing_profile_collectionsToSuppress','2019-01-28 20:58:57'),('indexing_profile_determineAudienceBy','2021-10-15 13:10:35'),('indexing_profile_doAutomaticEcontentSuppression','2019-01-28 20:58:57'),('indexing_profile_dueDateFormat','2019-01-28 20:58:57'),('indexing_profile_extendLocationsToSuppress','2019-01-28 20:58:57'),('indexing_profile_filenames_to_include','2019-01-28 20:58:57'),('indexing_profile_folderCreation','2019-01-28 20:58:57'),('indexing_profile_groupUnchangedFiles','2019-01-28 20:58:57'),('indexing_profile_holdability','2019-01-28 20:58:57'),('indexing_profile_lastChangeProcessed','2021-10-15 13:10:36'),('indexing_profile_last_checkin_date','2019-01-28 20:58:57'),('indexing_profile_last_marc_export','2021-10-15 13:10:31'),('indexing_profile_last_volume_export_timestamp','2021-10-15 13:10:31'),('indexing_profile_marc_encoding','2019-01-28 20:58:57'),('indexing_profile_marc_record_subfield','2019-03-11 05:22:58'),('indexing_profile_regroup_all_records','2021-10-15 13:10:31'),('indexing_profile_specific_order_location','2019-01-28 20:58:57'),('indexing_profile_specified_formats','2021-10-15 13:10:31'),('indexing_profile_speicified_formats','2019-01-28 20:58:57'),('indexing_profile__full_export_record_threshold','2021-10-15 13:10:36'),('indexing_profile__remove_groupUnchangedFiles','2021-10-15 13:10:31'),('indexing_records_default_sub_location','2021-10-15 13:10:31'),('indexing_simplify_format_boosting','2021-10-15 13:10:36'),('index_resources','2019-01-28 20:58:59'),('index_search_stats','2019-01-28 20:58:58'),('index_search_stats_counts','2019-01-28 20:58:58'),('index_subsets_of_overdrive','2019-01-28 20:58:56'),('initial_setup','2011-11-15 22:29:11'),('ip_address_logs','2021-10-15 13:10:50'),('ip_address_logs_login_info','2021-10-15 13:10:50'),('ip_debugging','2021-10-15 13:10:48'),('ip_log_queries','2021-10-15 13:10:48'),('ip_log_timing','2021-10-15 13:10:48'),('ip_lookup_1','2019-01-28 20:58:59'),('ip_lookup_2','2019-01-28 20:58:59'),('ip_lookup_3','2019-01-28 20:58:59'),('ip_lookup_blocking','2021-10-15 13:10:48'),('islandora_cover_cache','2019-01-28 20:58:57'),('islandora_driver_cache','2019-01-28 20:58:57'),('islandora_lat_long_cache','2019-01-28 20:58:57'),('islandora_samePika_cache','2019-01-28 20:58:57'),('javascript_snippets','2021-10-15 13:10:50'),('languages_setup','2021-10-15 13:10:47'),('languages_show_for_translators','2021-10-15 13:10:47'),('language_locales','2021-10-15 13:10:47'),('large_print_indexing','2021-10-15 13:10:35'),('last_check_in_status_adjustments','2019-01-28 20:58:57'),('layout_settings','2021-10-15 13:10:25'),('layout_settings_remove_showSidebarMenu','2021-10-15 13:10:25'),('layout_settings_remove_sidebarMenuButtonText','2021-10-15 13:10:25'),('lexile_branding','2019-01-28 20:58:56'),('libraryAdmin','2019-01-28 20:58:59'),('libraryAllowUsernameUpdates','2021-10-15 13:10:26'),('libraryAlternateCardSetup','2021-10-15 13:10:26'),('libraryAvailableHoldDelay','2021-10-15 13:10:26'),('libraryCardBarcode','2021-10-15 13:10:26'),('libraryProfileRequireNumericPhoneNumbersWhenUpdatingProfile','2021-10-15 13:10:26'),('libraryProfileUpdateOptions','2021-10-15 13:10:26'),('library_1','2019-01-28 20:56:57'),('library_10','2019-01-28 20:56:57'),('library_11','2019-01-28 20:56:57'),('library_12','2019-01-28 20:56:57'),('library_13','2019-01-28 20:56:57'),('library_14','2019-01-28 20:56:57'),('library_15','2019-01-28 20:56:57'),('library_16','2019-01-28 20:56:57'),('library_17','2019-01-28 20:56:57'),('library_18','2019-01-28 20:56:57'),('library_19','2019-01-28 20:56:57'),('library_2','2019-01-28 20:56:57'),('library_20','2019-01-28 20:56:57'),('library_21','2019-01-28 20:56:57'),('library_23','2019-01-28 20:56:57'),('library_24','2019-01-28 20:56:57'),('library_25','2019-01-28 20:56:57'),('library_26','2019-01-28 20:56:57'),('library_28','2019-01-28 20:56:57'),('library_29','2019-01-28 20:56:57'),('library_3','2019-01-28 20:56:57'),('library_30','2019-01-28 20:56:57'),('library_31','2019-01-28 20:56:57'),('library_32','2019-01-28 20:56:57'),('library_33','2019-01-28 20:56:57'),('library_34','2019-01-28 20:56:57'),('library_35_marmot','2019-01-28 20:56:57'),('library_35_nashville','2019-01-28 20:56:57'),('library_36_nashville','2019-01-28 20:56:57'),('library_4','2019-01-28 20:56:57'),('library_5','2019-01-28 20:56:57'),('library_6','2019-01-28 20:56:57'),('library_7','2019-01-28 20:56:57'),('library_8','2019-01-28 20:56:57'),('library_9','2019-01-28 20:56:57'),('library_add_can_update_phone_number','2021-10-15 13:10:26'),('library_add_oai_searching','2021-10-15 13:10:17'),('library_allowDeletingILSRequests','2021-10-15 13:10:27'),('library_allow_home_library_updates','2021-10-15 13:10:27'),('library_allow_remember_pickup_location','2021-10-15 13:10:26'),('library_archive_material_requests','2019-01-28 20:58:56'),('library_archive_material_request_form_configurations','2019-01-28 20:58:56'),('library_archive_pid','2019-01-28 20:58:56'),('library_archive_related_objects_display_mode','2019-01-28 20:58:56'),('library_archive_request_customization','2019-01-28 20:58:56'),('library_archive_search_facets','2019-01-28 20:58:55'),('library_barcodes','2019-01-28 20:58:55'),('library_bookings','2019-01-28 20:58:55'),('library_cas_configuration','2019-01-28 20:58:56'),('library_claim_authorship_customization','2019-01-28 20:58:56'),('library_cleanup','2021-10-15 13:10:25'),('library_consortial_interface','2021-10-15 13:11:21'),('library_contact_links','2019-01-28 20:56:57'),('library_css','2019-01-28 20:56:57'),('library_default_materials_request_permissions','2021-10-15 13:10:51'),('library_eds_integration','2019-01-28 20:58:56'),('library_eds_search_integration','2019-01-28 20:58:56'),('library_enableForgotPasswordLink','2021-10-15 13:10:25'),('library_enable_web_builder','2021-10-15 13:10:25'),('library_events_setting','2021-10-15 13:10:48'),('library_expiration_warning','2019-01-28 20:56:57'),('library_facets','2019-01-28 20:58:55'),('library_facets_1','2019-01-28 20:58:55'),('library_facets_2','2019-01-28 20:58:55'),('library_field_level_permissions','2021-10-15 13:10:51'),('library_field_permission_updates_21_07_01','2021-10-15 13:10:51'),('library_fine_payment_order','2021-10-15 13:10:19'),('library_fine_updates_msb','2021-10-15 13:10:19'),('library_fine_updates_paypal','2021-10-15 13:10:19'),('library_grouping','2019-01-28 20:56:57'),('library_ils_code_expansion','2019-01-28 20:56:57'),('library_ils_code_expansion_2','2019-01-28 20:56:58'),('library_indexes','2021-10-15 13:10:18'),('library_links','2019-01-28 20:56:57'),('library_links_display_options','2019-01-28 20:56:57'),('library_links_menu_update','2021-10-15 13:10:15'),('library_links_open_in_new_tab','2021-10-15 13:10:15'),('library_links_showToLoggedInUsersOnly','2021-10-15 13:10:15'),('library_links_show_html','2019-01-28 20:56:57'),('library_location_availability_toggle_updates','2019-01-28 20:58:56'),('library_location_axis360_scoping','2021-10-15 13:10:18'),('library_location_boosting','2019-01-28 20:56:57'),('library_location_cloud_library_scoping','2021-10-15 13:10:18'),('library_location_defaults','2021-10-15 13:10:25'),('library_location_display_controls','2019-01-28 20:58:55'),('library_location_hoopla_scoping','2021-10-15 13:10:18'),('library_location_rbdigital_scoping','2021-10-15 13:10:18'),('library_location_repeat_online','2019-01-28 20:56:57'),('library_location_side_load_scoping','2021-10-15 13:10:35'),('library_login_notes','2021-10-15 13:10:26'),('library_materials_request_limits','2019-01-28 20:56:57'),('library_materials_request_new_request_summary','2019-01-28 20:56:57'),('library_max_fines_for_account_update','2019-01-28 20:58:56'),('library_menu_link_languages','2021-10-15 13:11:02'),('library_on_order_counts','2019-01-28 20:58:56'),('library_order_information','2019-01-28 20:56:57'),('library_patronNameDisplayStyle','2019-01-28 20:58:56'),('library_patron_messages','2021-10-15 13:10:27'),('library_pin_reset','2019-01-28 20:56:57'),('library_prevent_expired_card_login','2019-01-28 20:56:57'),('library_prompt_birth_date','2019-01-28 20:58:55'),('library_propay_settings','2021-10-15 13:10:27'),('library_remove_gold_rush','2021-10-15 13:10:18'),('library_remove_overdrive_advantage_info','2021-10-15 13:10:15'),('library_remove_unusedColumns','2021-10-15 13:10:16'),('library_remove_unusedDisplayOptions_3_18','2021-10-15 13:10:17'),('library_remove_unused_recordsToBlackList','2021-10-15 13:10:17'),('library_rename_prospector','2021-10-15 13:10:14'),('library_rename_showPickupLocationInProfile','2021-10-15 13:10:27'),('library_showConvertListsFromClassic','2021-10-15 13:10:25'),('library_show_display_name','2019-01-28 20:58:55'),('library_show_messaging_settings','2021-10-15 13:11:21'),('library_show_quick_copy','2021-10-15 13:10:18'),('library_show_series_in_main_details','2019-01-28 20:58:56'),('library_sidebar_menu','2019-01-28 20:58:56'),('library_sidebar_menu_button_text','2019-01-28 20:58:56'),('library_sitemap_changes','2021-10-15 13:10:25'),('library_subject_display','2019-01-28 20:58:56'),('library_subject_display_2','2019-01-28 20:58:56'),('library_system_message','2021-10-15 13:10:15'),('library_tiktok_link','2021-10-15 13:10:27'),('library_top_links','2019-01-28 20:56:57'),('library_use_theme','2019-02-26 00:09:00'),('library_workstation_id_polaris','2021-10-15 13:10:51'),('linked_accounts_switch','2019-01-28 20:58:56'),('listPublisherRole','2019-01-28 20:58:59'),('list_indexing_permission','2021-10-15 13:10:29'),('list_wdiget_list_update_1','2019-01-28 20:58:57'),('list_wdiget_update_1','2019-01-28 20:58:57'),('list_widgets','2019-01-28 20:58:57'),('list_widgets_home','2019-01-28 20:58:57'),('list_widgets_update_1','2019-01-28 20:58:57'),('list_widgets_update_2','2019-01-28 20:58:57'),('list_widget_num_results','2019-01-28 20:58:57'),('list_widget_search_terms','2021-10-15 13:10:30'),('list_widget_style_update','2019-01-28 20:58:57'),('list_widget_update_2','2019-01-28 20:58:57'),('list_widget_update_3','2019-01-28 20:58:57'),('list_widget_update_4','2019-01-28 20:58:57'),('list_widget_update_5','2019-01-28 20:58:57'),('literaryFormIndexingUpdates','2021-10-15 13:11:02'),('lm_library_calendar_events_data','2021-10-15 13:10:48'),('lm_library_calendar_private_feed_settings','2021-10-15 13:10:48'),('lm_library_calendar_settings','2021-10-15 13:10:48'),('loadCoversFrom020z','2021-10-15 13:10:48'),('loan_rule_determiners_1','2019-01-28 20:59:01'),('loan_rule_determiners_increase_ptype_length','2019-01-28 20:59:01'),('localized_browse_categories','2019-01-28 20:59:02'),('local_urls','2021-10-15 13:10:54'),('locationHistoricCode','2021-10-15 13:10:26'),('location_1','2019-01-28 20:58:55'),('location_10','2019-01-28 20:58:56'),('location_2','2019-01-28 20:58:56'),('location_3','2019-01-28 20:58:56'),('location_4','2019-01-28 20:58:56'),('location_5','2019-01-28 20:58:56'),('location_6','2019-01-28 20:58:56'),('location_7','2019-01-28 20:58:56'),('location_8','2019-01-28 20:58:56'),('location_9','2019-01-28 20:58:56'),('location_additional_branches_to_show_in_facets','2019-01-28 20:58:56'),('location_address','2019-01-28 20:58:56'),('location_add_notes_to_hours','2021-10-15 13:10:18'),('location_allow_multiple_open_hours_per_day','2021-10-15 13:10:18'),('location_facets','2019-01-28 20:58:55'),('location_facets_1','2019-01-28 20:58:55'),('location_field_level_permissions','2021-10-15 13:10:51'),('location_hours','2019-01-28 20:59:01'),('location_include_library_records_to_include','2019-01-28 20:58:56'),('location_increase_code_column_size','2019-01-28 20:58:56'),('location_library_control_shelf_location_and_date_added_facets','2019-01-28 20:58:56'),('location_show_display_name','2019-01-28 20:58:56'),('location_subdomain','2019-01-28 20:58:56'),('location_sublocation','2019-01-28 20:58:56'),('location_sublocation_uniqueness','2019-01-28 20:58:56'),('location_tty_description','2021-10-15 13:10:26'),('login_form_labels','2019-01-28 20:58:56'),('logo_linking','2019-01-28 20:58:56'),('main_location_switch','2019-01-28 20:58:56'),('make_nyt_user_list_publisher','2021-10-15 13:10:28'),('make_volumes_case_sensitive','2021-10-15 13:10:56'),('manageMaterialsRequestFieldsToDisplay','2019-01-28 20:58:59'),('marcImport','2019-01-28 20:59:01'),('marcImport_1','2019-01-28 20:59:01'),('marcImport_2','2019-01-28 20:59:01'),('marcImport_3','2019-01-28 20:59:01'),('marc_last_modified','2021-10-15 13:10:53'),('masquerade_automatic_timeout_length','2019-01-28 20:58:56'),('masquerade_permissions','2021-10-15 13:10:29'),('masquerade_ptypes','2019-01-28 20:59:01'),('materialRequestsRole','2019-01-28 20:58:59'),('materialsRequest','2019-01-28 20:58:58'),('materialsRequestFixColumns','2019-01-28 20:58:59'),('materialsRequestFormats','2019-01-28 20:58:59'),('materialsRequestFormFields','2019-01-28 20:58:59'),('materialsRequestLibraryId','2019-01-28 20:58:59'),('materialsRequestStaffComments','2021-10-15 13:10:57'),('materialsRequestStatus','2019-01-28 20:58:59'),('materialsRequestStatus_update1','2019-01-28 20:58:59'),('materialsRequest_update1','2019-01-28 20:58:58'),('materialsRequest_update2','2019-01-28 20:58:58'),('materialsRequest_update3','2019-01-28 20:58:58'),('materialsRequest_update4','2019-01-28 20:58:58'),('materialsRequest_update5','2019-01-28 20:58:58'),('materialsRequest_update6','2019-01-28 20:58:58'),('materialsRequest_update7','2019-01-28 20:58:59'),('materials_request_days_to_keep','2019-01-28 20:58:56'),('memory_index','2021-10-15 13:10:50'),('memory_table','2021-10-15 13:10:50'),('memory_table_size_increase','2021-10-15 13:10:50'),('merged_records','2019-01-28 20:58:59'),('millenniumTables','2019-01-28 20:59:01'),('modifyColumnSizes_1','2011-11-10 19:46:03'),('modules','2021-10-15 13:10:14'),('module_log_information','2021-10-15 13:10:14'),('module_settings_information','2021-10-15 13:10:14'),('more_details_customization','2019-01-28 20:58:56'),('move_unchanged_scope_data_to_item','2021-10-15 13:10:54'),('nearby_book_store','2019-01-28 20:59:01'),('newRolesJan2016','2019-01-28 20:58:59'),('new_search_stats','2019-01-28 20:58:58'),('new_york_times_user_updates','2021-10-15 13:10:29'),('nongrouped_records','2019-01-28 20:58:59'),('non_numeric_ptypes','2019-01-28 20:59:01'),('notices_1','2011-12-02 18:26:28'),('notInterested','2019-01-28 20:58:58'),('notInterestedWorks','2019-01-28 20:58:58'),('notInterestedWorksRemoveUserIndex','2019-01-28 20:58:58'),('novelist_data','2019-01-28 20:59:02'),('novelist_data_indexes','2021-10-15 13:10:49'),('novelist_data_json','2021-10-15 13:10:49'),('novelist_settings','2021-10-15 13:10:50'),('nyt_api_settings','2021-10-15 13:10:50'),('nyt_update_log_numSkipped','2021-10-15 13:10:50'),('oai_website_permissions','2021-10-15 13:10:29'),('object_history','2021-10-15 13:10:50'),('object_history_field_lengths','2021-10-15 13:10:50'),('offline_circulation','2019-01-28 20:59:02'),('offline_holds','2019-01-28 20:59:02'),('offline_holds_update_1','2019-01-28 20:59:02'),('offline_holds_update_2','2019-01-28 20:59:02'),('omdb_settings','2021-10-15 13:10:50'),('open_archives_collection','2021-10-15 13:10:47'),('open_archives_collection_filtering','2021-10-15 13:10:47'),('open_archives_collection_subjects','2021-10-15 13:10:47'),('open_archives_image_regex','2021-10-15 13:11:02'),('open_archives_loadOneMonthAtATime','2021-10-15 13:10:47'),('open_archives_log','2021-10-15 13:10:47'),('open_archives_module_add_log','2021-10-15 13:10:47'),('open_archives_module_add_settings','2021-10-15 13:10:47'),('open_archives_record','2021-10-15 13:10:47'),('open_archives_scoping','2021-10-15 13:10:47'),('open_archives_usage_add_instance','2021-10-15 13:10:47'),('open_archive_tracking_adjustments','2021-10-15 13:10:47'),('overdrive_account_cache','2012-01-02 22:16:10'),('overdrive_add_settings','2021-10-15 13:10:36'),('overdrive_add_setting_to_log','2021-10-15 13:10:36'),('overdrive_add_setting_to_product_availability','2021-10-15 13:10:36'),('overdrive_add_setting_to_scope','2021-10-15 13:10:36'),('overdrive_add_update_info_to_settings','2021-10-15 13:10:36'),('overdrive_allow_large_deletes','2021-10-15 13:10:36'),('overdrive_api_data','2016-06-30 17:11:12'),('overdrive_api_data_availability_shared','2021-10-15 13:10:36'),('overdrive_api_data_availability_type','2016-06-30 17:11:12'),('overdrive_api_data_crossRefId','2019-01-28 21:27:59'),('overdrive_api_data_metadata_isOwnedByCollections','2019-01-28 21:27:59'),('overdrive_api_data_needsUpdate','2019-01-28 21:27:59'),('overdrive_api_data_update_1','2016-06-30 17:11:12'),('overdrive_api_data_update_2','2016-06-30 17:11:12'),('overdrive_api_remove_old_tables','2021-10-15 13:10:36'),('overdrive_availability_update_indexes','2021-10-15 13:10:36'),('overdrive_circulationEnabled','2021-10-15 13:11:02'),('overdrive_client_credentials','2021-10-15 13:10:36'),('overdrive_integration','2019-01-28 20:58:56'),('overdrive_integration_2','2019-01-28 20:58:56'),('overdrive_integration_3','2019-01-28 20:58:56'),('overdrive_max_extraction_threads','2021-10-15 13:11:02'),('overdrive_module_add_log','2021-10-15 13:10:36'),('overdrive_module_add_settings','2021-10-15 13:10:36'),('overdrive_part_count','2021-10-15 13:10:36'),('overdrive_record_cache','2012-01-02 21:47:53'),('overdrive_usage_add_instance','2021-10-15 13:10:36'),('paypal_settings','2021-10-15 13:10:53'),('pdfView','2021-10-15 13:10:54'),('pinterest_library_contact_links','2021-10-15 13:10:16'),('placards','2021-10-15 13:10:50'),('placard_alt_text','2021-10-15 13:11:21'),('placard_languages','2021-10-15 13:11:02'),('placard_location_scope','2021-10-15 13:10:50'),('placard_timing','2021-10-15 13:10:50'),('placard_trigger_exact_match','2021-10-15 13:10:50'),('placard_updates_1','2021-10-15 13:10:50'),('plural_grouped_work_facet','2021-10-15 13:10:52'),('polaris_full_update_21_13','2021-10-15 13:11:02'),('polaris_item_identifiers','2021-10-15 13:11:02'),('populate_list_entry_titles','2021-10-15 13:10:51'),('propay_accountId_to_user','2021-10-15 13:10:53'),('propay_certStr_length','2021-10-15 13:10:56'),('propay_settings','2021-10-15 13:10:53'),('propay_settings_additional_fields','2021-10-15 13:10:53'),('ptype','2019-01-28 20:59:01'),('pTypesForLibrary','2019-01-28 20:58:55'),('ptype_descriptions','2021-10-15 13:10:29'),('public_lists_to_include','2019-01-28 20:58:56'),('public_lists_to_include_defaults','2021-10-15 13:10:16'),('purchase_link_tracking','2019-01-28 20:58:58'),('quipu_ecard_settings','2021-10-15 13:10:51'),('rbdigital_availability','2019-03-06 15:43:14'),('rbdigital_exportLog','2019-03-05 05:46:15'),('rbdigital_exportTables','2019-03-05 16:31:43'),('readingHistory','2019-01-28 20:58:58'),('readingHistoryUpdate1','2019-01-28 20:58:58'),('readingHistory_deletion','2019-01-28 20:58:58'),('readingHistory_work','2019-01-28 20:58:58'),('rebuildThemes21_03','2021-10-15 13:10:47'),('recaptcha_settings','2021-10-15 13:10:50'),('recommendations_optOut','2019-01-28 20:58:58'),('records_to_include_2017-06','2019-01-28 20:58:57'),('records_to_include_2018-03','2019-01-28 20:58:57'),('record_files_table','2021-10-15 13:10:48'),('record_grouping_log','2019-01-28 20:59:02'),('record_identifiers_to_reload','2021-10-15 13:10:35'),('record_suppression_no_marc','2021-10-15 13:10:53'),('redwood_user_contribution','2021-10-15 13:10:47'),('refetch_novelist_data_21_09_02','2021-10-15 13:10:56'),('regroup_21_03','2021-10-15 13:10:31'),('regroup_21_07','2021-10-15 13:10:51'),('reindexLog','2019-01-28 20:59:01'),('reindexLog_1','2019-01-28 20:59:01'),('reindexLog_2','2019-01-28 20:59:01'),('reindexLog_grouping','2019-01-28 20:59:01'),('reindexLog_nightly_updates','2021-10-15 13:10:49'),('reindexLog_unique_index','2021-10-15 13:10:49'),('removeGroupedWorkSecondDateUpdatedIndex','2021-10-15 13:11:02'),('removeIslandoraTables','2021-10-15 13:11:02'),('removeProPayFromLibrary','2021-10-15 13:10:53'),('remove_bookings','2021-10-15 13:10:57'),('remove_browse_tables','2019-01-28 20:59:02'),('remove_consortial_results_in_search','2019-01-28 20:58:56'),('remove_econtent_support_address','2021-10-15 13:11:02'),('remove_editorial_reviews','2021-10-15 13:10:48'),('remove_holding_branch_label','2021-10-15 13:10:25'),('remove_library_and location_boost','2021-10-15 13:10:49'),('remove_library_location_boosting','2021-10-15 13:10:15'),('remove_library_themeName','2021-10-15 13:10:51'),('remove_library_top_links','2021-10-15 13:10:15'),('remove_list_widget_list_links','2021-10-15 13:10:30'),('remove_loan_rules','2021-10-15 13:10:51'),('remove_merged_records','2021-10-15 13:10:48'),('remove_old_homeLink','2021-10-15 13:10:50'),('remove_old_resource_tables','2019-01-28 20:59:02'),('remove_old_user_rating_table','2021-10-15 13:10:49'),('remove_order_options','2019-01-28 20:58:56'),('remove_overdrive_api_data_needsUpdate','2021-10-15 13:10:36'),('remove_ptype_from_library_location','2021-10-15 13:10:56'),('remove_rbdigital','2021-10-15 13:10:55'),('remove_record_grouping_log','2021-10-15 13:10:49'),('remove_scope_tables','2021-10-15 13:10:54'),('remove_scope_triggers','2021-10-15 13:10:54'),('remove_spelling_words','2021-10-15 13:10:49'),('remove_unused_enrichment_and_full_record_options','2019-01-28 20:58:56'),('remove_unused_location_options_2015_14_0','2019-01-28 20:58:56'),('remove_unused_options','2019-01-28 20:59:02'),('rename_tables','2019-01-28 20:59:01'),('rename_to_collection_spotlight','2021-10-15 13:10:31'),('renew_error','2021-10-15 13:10:52'),('reporting_permissions','2021-10-15 13:10:29'),('resource_subject','2019-01-28 20:58:58'),('resource_update3','2019-01-28 20:58:58'),('resource_update4','2019-01-28 20:58:58'),('resource_update5','2019-01-28 20:58:58'),('resource_update6','2019-01-28 20:58:58'),('resource_update7','2019-01-28 20:58:58'),('resource_update8','2019-01-28 20:58:58'),('resource_update_table','2019-01-28 20:58:58'),('resource_update_table_2','2019-01-28 20:58:58'),('re_enable_hoopla_module_auto_restart','2021-10-15 13:10:38'),('right_hand_sidebar','2019-01-28 20:58:56'),('roles_1','2019-01-28 20:58:56'),('roles_2','2019-01-28 20:58:56'),('rosen_levelup_settings','2021-10-15 13:10:50'),('rosen_levelup_settings_school_prefix','2021-10-15 13:10:50'),('runNightlyFullIndex','2021-10-15 13:10:48'),('saved_searches_created_default','2021-10-15 13:10:49'),('scheduled_work_index','2021-10-15 13:10:35'),('search_results_view_configuration_options','2019-01-28 20:58:56'),('search_sources','2019-01-28 20:58:56'),('search_sources_1','2019-01-28 20:58:56'),('selfRegistrationCustomizations','2021-10-15 13:10:26'),('selfRegistrationLocationRestrictions','2021-10-15 13:10:25'),('selfRegistrationPasswordNotes','2021-10-15 13:10:26'),('selfRegistrationUrl','2021-10-15 13:10:25'),('selfRegistrationZipCodeValidation','2021-10-15 13:10:26'),('selfreg_customization','2019-01-28 20:58:56'),('selfreg_template','2019-01-28 20:58:56'),('sendgrid_settings','2021-10-15 13:10:50'),('session_update_1','2019-01-28 20:59:02'),('setup_default_indexing_profiles','2019-01-28 20:58:57'),('showCardExpirationDate','2021-10-15 13:10:57'),('showInSelectInterface','2021-10-15 13:11:02'),('showWhileYouWait','2021-10-15 13:10:25'),('show_catalog_options_in_profile','2019-01-28 20:58:56'),('show_grouped_hold_copies_count','2019-01-28 20:58:56'),('show_library_hours_notice_on_account_pages','2019-01-28 20:58:56'),('show_place_hold_on_unavailable','2019-01-28 20:58:56'),('show_Refresh_Account_Button','2019-01-28 20:58:56'),('sideloads','2021-10-15 13:10:35'),('sideload_access_button_label','2021-10-15 13:11:21'),('sideload_defaults','2021-10-15 13:10:35'),('sideload_files','2021-10-15 13:10:35'),('sideload_log','2021-10-15 13:10:35'),('sideload_scope_match_and_rewrite','2021-10-15 13:10:35'),('sideload_scope_url_match_and_rewrite_embiggening','2021-10-15 13:10:35'),('sideload_scoping','2021-10-15 13:10:35'),('sideload_show_status','2021-10-15 13:11:21'),('sideload_usage_add_instance','2021-10-15 13:10:35'),('sierra_exportLog','2019-01-28 20:58:57'),('sierra_exportLog_stats','2019-01-28 20:58:58'),('sierra_export_additional_fixed_fields','2021-10-15 13:10:38'),('sierra_export_field_mapping','2019-01-28 20:58:58'),('sierra_export_field_mapping_item_fields','2019-01-28 20:58:58'),('slow_pages','2021-10-15 13:10:50'),('slow_page_granularity','2021-10-15 13:10:50'),('spelling_optimization','2019-01-28 20:59:01'),('staffSettingsAllowNegativeUserId','2021-10-15 13:10:48'),('staffSettingsTable','2019-01-28 20:58:59'),('staff_members','2021-10-15 13:10:48'),('staff_ptypes','2021-10-15 13:10:29'),('storeNYTLastUpdated','2021-10-15 13:10:54'),('storeRecordDetailsInDatabase','2021-10-15 13:10:53'),('storeRecordDetailsInSolr','2021-10-15 13:10:52'),('store_grouped_work_record_item_scope','2021-10-15 13:10:52'),('store_marc_in_db','2021-10-15 13:10:53'),('store_pickup_location','2021-10-15 13:10:29'),('store_scope_details_in_concatenated_fields','2021-10-15 13:10:54'),('sub-browse_categories','2019-01-28 20:59:02'),('superCatalogerRole','2021-10-15 13:10:48'),('suppressRecordsWithUrlsMatching','2021-10-15 13:11:02'),('syndetics_data','2019-01-28 20:59:02'),('syndetics_data_update_1','2021-10-15 13:10:49'),('syndetics_settings','2021-10-15 13:10:50'),('syndetics_unbound','2021-10-15 13:10:50'),('syndetics_unbound_account_number','2021-10-15 13:10:51'),('system_messages','2021-10-15 13:10:50'),('system_messages_permissions','2021-10-15 13:10:29'),('system_message_style','2021-10-15 13:10:50'),('test_roles_permission','2021-10-15 13:10:29'),('themes_additional_css','2021-10-15 13:10:39'),('themes_additional_fonts','2021-10-15 13:10:40'),('themes_badges','2021-10-15 13:10:44'),('themes_browse_category_colors','2021-10-15 13:10:39'),('themes_button_colors','2021-10-15 13:10:42'),('themes_button_radius','2021-10-15 13:10:39'),('themes_button_radius2','2021-10-15 13:10:39'),('themes_capitalize_browse_categories','2021-10-15 13:10:40'),('themes_editions_button_colors','2021-10-15 13:10:42'),('themes_favicon','2021-10-15 13:10:38'),('themes_fonts','2021-10-15 13:10:38'),('themes_footer_design','2021-10-15 13:10:43'),('themes_header_buttons','2021-10-15 13:10:38'),('themes_header_colors','2021-10-15 13:10:38'),('themes_header_colors_2','2021-10-15 13:10:38'),('themes_link_color','2021-10-15 13:10:43'),('themes_link_hover_color','2021-10-15 13:10:43'),('themes_panel_body_design','2021-10-15 13:10:43'),('themes_panel_design','2021-10-15 13:10:43'),('themes_primary_colors','2021-10-15 13:10:38'),('themes_results_breadcrumbs','2021-10-15 13:10:44'),('themes_search_tools','2021-10-15 13:10:44'),('themes_secondary_colors','2021-10-15 13:10:38'),('themes_setup','2019-02-24 20:32:34'),('themes_sidebar_highlight_colors','2021-10-15 13:10:40'),('themes_tools_button_colors','2021-10-15 13:10:43'),('theme_defaults_for_logo_and_favicon','2021-10-15 13:10:38'),('theme_modal_dialog','2021-10-15 13:10:47'),('theme_name_length','2019-01-28 20:58:56'),('theme_reorganize_menu','2021-10-15 13:10:46'),('track_axis360_record_usage','2021-10-15 13:10:37'),('track_axis360_stats','2021-10-15 13:10:37'),('track_axis360_user_usage','2021-10-15 13:10:37'),('track_cloud_library_record_usage','2021-10-15 13:10:47'),('track_cloud_library_user_usage','2021-10-15 13:10:47'),('track_ebsco_eds_user_usage','2021-10-15 13:10:37'),('track_event_user_usage','2021-10-15 13:10:48'),('track_hoopla_record_usage','2021-10-15 13:10:38'),('track_hoopla_user_usage','2021-10-15 13:10:38'),('track_ils_record_usage','2021-10-15 13:10:31'),('track_ils_self_registrations','2021-10-15 13:10:31'),('track_ils_user_usage','2021-10-15 13:10:31'),('track_open_archive_record_usage','2021-10-15 13:10:47'),('track_open_archive_user_usage','2021-10-15 13:10:47'),('track_overdrive_record_usage','2021-10-15 13:10:36'),('track_overdrive_stats','2021-10-15 13:10:36'),('track_overdrive_user_usage','2021-10-15 13:10:36'),('track_pdf_downloads','2021-10-15 13:10:31'),('track_pdf_views','2021-10-15 13:10:31'),('track_sideload_record_usage','2021-10-15 13:10:35'),('track_sideload_user_usage','2021-10-15 13:10:35'),('track_supplemental_file_downloads','2021-10-15 13:10:31'),('track_website_user_usage','2021-10-15 13:10:47'),('translations','2021-10-15 13:10:47'),('translation_case_sensitivity','2021-10-15 13:10:47'),('translation_map_regex','2019-01-28 20:58:57'),('translation_terms','2021-10-15 13:10:47'),('translation_term_case_sensitivity','2021-10-15 13:10:47'),('translation_term_default_text','2021-10-15 13:10:47'),('translation_term_increase_length','2021-10-15 13:10:47'),('translator_role','2021-10-15 13:10:47'),('treatBibOrItemHoldsAs','2021-10-15 13:10:56'),('treat_unknown_audience_as','2021-10-15 13:10:52'),('unknown_language_handling','2021-10-15 13:10:35'),('update_grouped_work_more_details','2021-10-15 13:10:25'),('update_item_status','2021-10-15 13:10:56'),('update_plural_grouped_work_facet_label','2021-10-15 13:10:52'),('update_spotlight_sources','2021-10-15 13:10:31'),('update_useHomeLink','2021-10-15 13:10:50'),('update_useHomeLink_tinyint','2021-10-15 13:10:50'),('upload_list_cover_permissions','2021-10-15 13:10:51'),('userRatings1','2019-01-28 20:58:58'),('user_account','2019-01-28 20:58:56'),('user_account_cache_volume_length','2021-10-15 13:10:30'),('user_account_summary_cache','2021-10-15 13:10:30'),('user_account_summary_expiration_date_extension','2021-10-15 13:10:30'),('user_account_summary_remaining_checkouts','2021-10-15 13:10:30'),('user_add_last_reading_history_update_time','2021-10-15 13:10:29'),('user_add_rbdigital_id','2021-10-15 13:10:28'),('user_add_rbdigital_username_password','2021-10-15 13:10:28'),('user_assign_role_by_ptype','2021-10-15 13:10:29'),('user_cache_checkouts','2021-10-15 13:10:30'),('user_cache_holds','2021-10-15 13:10:30'),('user_checkout_cache_additional_fields','2021-10-15 13:10:30'),('user_checkout_cache_renewal_information','2021-10-15 13:10:30'),('user_circulation_cache_callnumber_length','2021-10-15 13:10:30'),('user_circulation_cache_cover_link','2021-10-15 13:10:30'),('user_circulation_cache_grouped_work','2021-10-15 13:10:30'),('user_circulation_cache_indexes','2021-10-15 13:10:30'),('user_circulation_cache_overdrive_magazines','2021-10-15 13:10:30'),('user_circulation_cache_overdrive_supplemental_materials','2021-10-15 13:10:30'),('user_display_name','2019-01-28 20:58:56'),('user_display_name_length','2021-10-15 13:10:28'),('user_hold_format','2021-10-15 13:10:30'),('user_hoopla_confirmation_checkout','2019-01-28 20:58:56'),('user_hoopla_confirmation_checkout_prompt','2021-10-15 13:10:27'),('user_ilsType','2019-01-28 20:58:56'),('user_languages','2021-10-15 13:10:28'),('user_last_list_used','2021-10-15 13:10:28'),('user_last_login_validation','2021-10-15 13:10:28'),('user_last_name_length','2021-10-15 13:10:28'),('user_linking','2019-01-28 20:58:56'),('user_linking_1','2019-01-28 20:58:56'),('user_linking_disable_link','2021-10-15 13:10:27'),('user_link_blocking','2019-01-28 20:58:56'),('user_list_entry','2019-01-28 20:59:02'),('user_list_entry_add_additional_types','2021-10-15 13:10:28'),('user_list_force_reindex_20_18','2021-10-15 13:10:50'),('user_list_import_information','2021-10-15 13:10:28'),('user_list_indexing','2019-01-28 20:59:02'),('user_list_indexing_log','2021-10-15 13:10:49'),('user_list_indexing_settings','2021-10-15 13:10:49'),('user_list_searching','2021-10-15 13:10:49'),('user_list_sorting','2019-01-28 20:59:02'),('user_locked_filters','2021-10-15 13:10:28'),('user_messages','2021-10-15 13:10:28'),('user_message_actions','2021-10-15 13:10:28'),('user_overdrive_auto_checkout','2021-10-15 13:10:28'),('user_overdrive_email','2019-01-28 20:58:56'),('user_password_length','2021-10-15 13:10:28'),('user_payments','2021-10-15 13:10:28'),('user_payments_cancelled','2021-10-15 13:10:53'),('user_payments_carlx','2021-10-15 13:10:28'),('user_payments_finesPaid','2021-10-15 13:10:28'),('user_permissions','2021-10-15 13:10:28'),('user_permission_defaults','2021-10-15 13:10:29'),('user_phone','2019-01-28 20:58:56'),('user_phone_length','2021-10-15 13:10:27'),('user_preference_review_prompt','2019-01-28 20:58:56'),('user_preferred_library_interface','2019-01-28 20:58:56'),('user_reading_history_dates_in_past','2021-10-15 13:10:30'),('user_reading_history_index','2021-10-15 13:10:27'),('user_reading_history_index_source_id','2019-01-28 20:58:56'),('user_reading_history_work_index','2021-10-15 13:10:27'),('user_rememberHoldPickupLocation','2021-10-15 13:10:28'),('user_remove_college_major','2021-10-15 13:10:29'),('user_remove_default_created','2021-10-15 13:10:27'),('user_review_imported_from','2021-10-15 13:10:49'),('user_secondary_library_card','2021-10-15 13:10:28'),('user_track_reading_history','2019-01-28 20:58:56'),('user_update_messages','2021-10-15 13:10:28'),('user_username_increase_length','2021-10-15 13:10:30'),('utf8mb4support','2021-10-15 13:11:21'),('utf8_update','2016-06-30 17:11:12'),('variables_full_index_warnings','2019-01-28 20:58:59'),('variables_lastHooplaExport','2019-01-28 20:58:57'),('variables_lastRbdigitalExport','2019-03-05 05:46:15'),('variables_offline_mode_when_offline_login_allowed','2019-01-28 20:58:59'),('variables_table','2019-01-28 20:58:59'),('variables_table_uniqueness','2019-01-28 20:58:59'),('variables_validateChecksumsFromDisk','2019-01-28 20:58:59'),('view_unpublished_content_permissions','2021-10-15 13:10:29'),('volume_display_order','2021-10-15 13:10:31'),('volume_increase_display_order','2021-10-15 13:10:31'),('volume_increase_field_lengths','2021-10-15 13:10:31'),('volume_information','2019-01-28 20:58:57'),('website_indexing_tables','2021-10-15 13:10:47'),('website_record_usage','2021-10-15 13:10:48'),('website_usage_add_instance','2021-10-15 13:10:48'),('web_builder_add_cell_imageURL','2021-10-15 13:10:48'),('web_builder_add_cell_makeCellAccordion','2021-10-15 13:10:48'),('web_builder_add_frameHeight','2021-10-15 13:10:48'),('web_builder_add_settings','2021-10-15 13:10:48'),('web_builder_basic_pages','2021-10-15 13:10:48'),('web_builder_basic_page_teaser','2021-10-15 13:10:48'),('web_builder_categories_and_audiences','2021-10-15 13:10:48'),('web_builder_custom_forms','2021-10-15 13:10:48'),('web_builder_custom_from_submission_isRead','2021-10-15 13:10:50'),('web_builder_custom_page_categories','2021-10-15 13:10:48'),('web_builder_image_upload','2021-10-15 13:10:48'),('web_builder_image_upload_additional_sizes','2021-10-15 13:10:48'),('web_builder_last_update_timestamps','2021-10-15 13:10:48'),('web_builder_menu','2021-10-15 13:10:48'),('web_builder_menu_show_when','2021-10-15 13:10:48'),('web_builder_menu_sorting','2021-10-15 13:10:48'),('web_builder_module','2021-10-15 13:10:48'),('web_builder_module_monitoring_and_indexing','2021-10-15 13:10:48'),('web_builder_portal','2021-10-15 13:10:48'),('web_builder_portal_cell_markdown','2021-10-15 13:10:48'),('web_builder_portal_cell_source_info','2021-10-15 13:10:48'),('web_builder_portal_cell_title','2021-10-15 13:10:48'),('web_builder_portal_weights','2021-10-15 13:10:48'),('web_builder_remove_show_sidebar','2021-10-15 13:10:48'),('web_builder_resources','2021-10-15 13:10:48'),('web_builder_resource_in_library','2021-10-15 13:10:48'),('web_builder_resource_open_in_new_tab','2021-10-15 13:10:48'),('web_builder_resource_teaser','2021-10-15 13:10:48'),('web_builder_roles','2021-10-15 13:10:48'),('web_builder_scope_by_library','2021-10-15 13:10:48'),('web_indexer_add_description_expression','2021-10-15 13:10:48'),('web_indexer_add_paths_to_exclude','2021-10-15 13:10:48'),('web_indexer_add_title_expression','2021-10-15 13:10:48'),('web_indexer_deleted_settings','2021-10-15 13:10:48'),('web_indexer_max_pages_to_index','2021-10-15 13:10:48'),('web_indexer_module_add_log','2021-10-15 13:10:48'),('web_indexer_scoping','2021-10-15 13:10:48'),('web_indexer_url_length','2021-10-15 13:10:48'),('work_level_ratings','2019-01-28 20:59:02'),('work_level_tagging','2019-01-28 20:59:02'),('worldpay_settings','2021-10-15 13:10:53'),('worldpay_setting_typo','2021-10-15 13:10:53');
/*!40000 ALTER TABLE `db_update` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dpla_api_settings`
--

DROP TABLE IF EXISTS `dpla_api_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dpla_api_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `apiKey` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=16384;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dpla_api_settings`
--

LOCK TABLES `dpla_api_settings` WRITE;
/*!40000 ALTER TABLE `dpla_api_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `dpla_api_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ebsco_eds_settings`
--

DROP TABLE IF EXISTS `ebsco_eds_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ebsco_eds_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `edsApiProfile` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '',
  `edsSearchProfile` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '',
  `edsApiUsername` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '',
  `edsApiPassword` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=16384;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ebsco_eds_settings`
--

LOCK TABLES `ebsco_eds_settings` WRITE;
/*!40000 ALTER TABLE `ebsco_eds_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `ebsco_eds_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ebsco_eds_usage`
--

DROP TABLE IF EXISTS `ebsco_eds_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ebsco_eds_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ebscoId` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `month` int(2) NOT NULL,
  `year` int(4) NOT NULL,
  `timesViewedInSearch` int(11) NOT NULL,
  `timesUsed` int(11) NOT NULL,
  `instance` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`ebscoId`,`year`,`month`),
  UNIQUE KEY `instance_2` (`instance`,`ebscoId`,`year`,`month`),
  UNIQUE KEY `instance_3` (`instance`,`ebscoId`,`year`,`month`)
) ENGINE=InnoDB AUTO_INCREMENT=907 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=92;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ebsco_eds_usage`
--

LOCK TABLES `ebsco_eds_usage` WRITE;
/*!40000 ALTER TABLE `ebsco_eds_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `ebsco_eds_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ebsco_research_starter`
--

DROP TABLE IF EXISTS `ebsco_research_starter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ebsco_research_starter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ebscoId` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ebscoId` (`ebscoId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=481;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ebsco_research_starter`
--

LOCK TABLES `ebsco_research_starter` WRITE;
/*!40000 ALTER TABLE `ebsco_research_starter` DISABLE KEYS */;
/*!40000 ALTER TABLE `ebsco_research_starter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ebsco_research_starter_dismissals`
--

DROP TABLE IF EXISTS `ebsco_research_starter_dismissals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ebsco_research_starter_dismissals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `researchStarterId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userId` (`userId`,`researchStarterId`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=8192;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ebsco_research_starter_dismissals`
--

LOCK TABLES `ebsco_research_starter_dismissals` WRITE;
/*!40000 ALTER TABLE `ebsco_research_starter_dismissals` DISABLE KEYS */;
/*!40000 ALTER TABLE `ebsco_research_starter_dismissals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `errors`
--

DROP TABLE IF EXISTS `errors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `errors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `action` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `url` mediumtext COLLATE utf8mb4_general_ci,
  `message` mediumtext COLLATE utf8mb4_general_ci,
  `backtrace` mediumtext COLLATE utf8mb4_general_ci,
  `timestamp` int(11) DEFAULT NULL,
  `userAgent` mediumtext COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=893 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=1976;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `errors`
--

LOCK TABLES `errors` WRITE;
/*!40000 ALTER TABLE `errors` DISABLE KEYS */;
INSERT INTO `errors` VALUES (891,'Search','Home','/','Call to a member function getBrowseCategories() on null','  [30] - C:\\web\\aspen-discovery\\code\\web\\services\\Search\\Home.php<br/>Search_Home->launch  [648] - C:\\web\\aspen-discovery\\code\\web\\index.php<br/>',1634303380,'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:93.0) Gecko/20100101 Firefox/93.0'),(892,'Search','Home','/','Call to a member function getBrowseCategories() on null','  [30] - C:\\web\\aspen-discovery\\code\\web\\services\\Search\\Home.php<br/>Search_Home->launch  [648] - C:\\web\\aspen-discovery\\code\\web\\index.php<br/>',1634303401,'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:93.0) Gecko/20100101 Firefox/93.0');
/*!40000 ALTER TABLE `errors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `events_indexing_log`
--

DROP TABLE IF EXISTS `events_indexing_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `events_indexing_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log entry',
  `name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `notes` mediumtext COLLATE utf8mb4_general_ci COMMENT 'Additional information about the run',
  `numEvents` int(11) DEFAULT '0',
  `numErrors` int(11) DEFAULT '0',
  `numAdded` int(11) DEFAULT '0',
  `numDeleted` int(11) DEFAULT '0',
  `numUpdated` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events_indexing_log`
--

LOCK TABLES `events_indexing_log` WRITE;
/*!40000 ALTER TABLE `events_indexing_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `events_indexing_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `events_spotlights`
--

DROP TABLE IF EXISTS `events_spotlights`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `events_spotlights` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `showNameAsTitle` tinyint(1) DEFAULT NULL,
  `description` mediumtext COLLATE utf8mb4_general_ci,
  `showDescription` tinyint(1) DEFAULT '0',
  `showEventImages` tinyint(1) DEFAULT '1',
  `showEventDescriptions` tinyint(1) DEFAULT '1',
  `searchTerm` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `defaultFilter` mediumtext COLLATE utf8mb4_general_ci,
  `defaultSort` enum('relevance','start_date_sort','title_sort') COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events_spotlights`
--

LOCK TABLES `events_spotlights` WRITE;
/*!40000 ALTER TABLE `events_spotlights` DISABLE KEYS */;
/*!40000 ALTER TABLE `events_spotlights` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `events_usage`
--

DROP TABLE IF EXISTS `events_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `events_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `source` int(11) NOT NULL,
  `identifier` varchar(36) COLLATE utf8mb4_general_ci NOT NULL,
  `month` int(2) NOT NULL,
  `year` int(4) NOT NULL,
  `timesViewedInSearch` int(11) NOT NULL,
  `timesUsed` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`,`source`,`identifier`,`year`,`month`),
  KEY `type_2` (`type`,`source`,`identifier`,`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events_usage`
--

LOCK TABLES `events_usage` WRITE;
/*!40000 ALTER TABLE `events_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `events_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `file_uploads`
--

DROP TABLE IF EXISTS `file_uploads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `file_uploads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `fullPath` varchar(512) COLLATE utf8mb4_general_ci NOT NULL,
  `type` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `thumbFullPath` varchar(512) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `file_uploads`
--

LOCK TABLES `file_uploads` WRITE;
/*!40000 ALTER TABLE `file_uploads` DISABLE KEYS */;
/*!40000 ALTER TABLE `file_uploads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `format_map_values`
--

DROP TABLE IF EXISTS `format_map_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `format_map_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `indexingProfileId` int(11) NOT NULL,
  `value` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `format` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `formatCategory` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `formatBoost` tinyint(4) NOT NULL,
  `suppress` tinyint(1) DEFAULT '0',
  `holdType` enum('bib','item','either','none') COLLATE utf8mb4_general_ci DEFAULT 'bib',
  `inLibraryUseOnly` tinyint(1) DEFAULT '0',
  `mustPickupAtHoldingBranch` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `indexingProfileId` (`indexingProfileId`,`value`)
) ENGINE=InnoDB AUTO_INCREMENT=173 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=712;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `format_map_values`
--

LOCK TABLES `format_map_values` WRITE;
/*!40000 ALTER TABLE `format_map_values` DISABLE KEYS */;
/*!40000 ALTER TABLE `format_map_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `google_api_settings`
--

DROP TABLE IF EXISTS `google_api_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `google_api_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `googleBooksKey` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `googleAnalyticsTrackingId` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `googleAnalyticsLinkingId` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `googleAnalyticsLinkedProperties` longtext COLLATE utf8mb4_general_ci,
  `googleAnalyticsDomainName` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `googleMapsKey` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `googleAnalyticsVersion` varchar(5) COLLATE utf8mb4_general_ci DEFAULT 'v3',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `google_api_settings`
--

LOCK TABLES `google_api_settings` WRITE;
/*!40000 ALTER TABLE `google_api_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `google_api_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `greenhouse_cache`
--

DROP TABLE IF EXISTS `greenhouse_cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `greenhouse_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `locationId` int(3) DEFAULT NULL,
  `libraryId` int(3) DEFAULT NULL,
  `solrScope` varchar(75) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `latitude` varchar(75) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `longitude` varchar(75) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `unit` varchar(3) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `baseUrl` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lastUpdated` int(11) DEFAULT NULL,
  `siteId` int(11) DEFAULT NULL,
  `releaseChannel` tinyint(1) DEFAULT '0',
  `logo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `favicon` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `primaryBackgroundColor` varchar(25) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `primaryForegroundColor` varchar(25) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `secondaryBackgroundColor` varchar(25) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `secondaryForegroundColor` varchar(25) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tertiaryBackgroundColor` varchar(25) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tertiaryForegroundColor` varchar(25) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=740 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=393;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `greenhouse_cache`
--

LOCK TABLES `greenhouse_cache` WRITE;
/*!40000 ALTER TABLE `greenhouse_cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `greenhouse_cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grouped_work`
--

DROP TABLE IF EXISTS `grouped_work`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grouped_work` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `permanent_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `author` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `grouping_category` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `full_title` varchar(750) COLLATE utf8mb4_general_ci NOT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `referenceCover` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permanent_id` (`permanent_id`),
  KEY `date_updated` (`date_updated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grouped_work`
--

LOCK TABLES `grouped_work` WRITE;
/*!40000 ALTER TABLE `grouped_work` DISABLE KEYS */;
/*!40000 ALTER TABLE `grouped_work` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `after_grouped_work_delete` AFTER DELETE ON `grouped_work` FOR EACH ROW BEGIN

					DELETE FROM grouped_work_records where groupedWorkId = old.id;

					DELETE FROM grouped_work_variation where groupedWorkId = old.id;

					END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `grouped_work_alternate_titles`
--

DROP TABLE IF EXISTS `grouped_work_alternate_titles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grouped_work_alternate_titles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `permanent_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `alternateTitle` varchar(709) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alternateAuthor` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `addedBy` int(11) DEFAULT NULL,
  `dateAdded` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `alternateTitle` (`alternateTitle`,`alternateAuthor`),
  KEY `permanent_id` (`permanent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=5461;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grouped_work_alternate_titles`
--

LOCK TABLES `grouped_work_alternate_titles` WRITE;
/*!40000 ALTER TABLE `grouped_work_alternate_titles` DISABLE KEYS */;
/*!40000 ALTER TABLE `grouped_work_alternate_titles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grouped_work_display_info`
--

DROP TABLE IF EXISTS `grouped_work_display_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grouped_work_display_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `permanent_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `title` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `author` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `seriesName` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `seriesDisplayOrder` int(11) DEFAULT NULL,
  `addedBy` int(11) DEFAULT NULL,
  `dateAdded` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permanent_id` (`permanent_id`),
  KEY `permanent_id_2` (`permanent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grouped_work_display_info`
--

LOCK TABLES `grouped_work_display_info` WRITE;
/*!40000 ALTER TABLE `grouped_work_display_info` DISABLE KEYS */;
/*!40000 ALTER TABLE `grouped_work_display_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grouped_work_display_settings`
--

DROP TABLE IF EXISTS `grouped_work_display_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grouped_work_display_settings` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `applyNumberOfHoldingsBoost` tinyint(4) DEFAULT '1',
  `showSearchTools` tinyint(4) DEFAULT '1',
  `showQuickCopy` tinyint(4) DEFAULT '1',
  `showInSearchResultsMainDetails` varchar(512) COLLATE utf8mb4_general_ci DEFAULT 'a:5:{i:0;s:10:"showSeries";i:1;s:13:"showPublisher";i:2;s:19:"showPublicationDate";i:3;s:13:"showLanguages";i:4;s:10:"showArInfo";}',
  `alwaysShowSearchResultsMainDetails` tinyint(4) DEFAULT '0',
  `availabilityToggleLabelSuperScope` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Entire Collection',
  `availabilityToggleLabelLocal` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '{display name}',
  `availabilityToggleLabelAvailable` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Available Now',
  `availabilityToggleLabelAvailableOnline` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Available Online',
  `baseAvailabilityToggleOnLocalHoldingsOnly` tinyint(1) DEFAULT '1',
  `includeOnlineMaterialsInAvailableToggle` tinyint(1) DEFAULT '1',
  `includeAllRecordsInShelvingFacets` tinyint(4) DEFAULT '0',
  `includeAllRecordsInDateAddedFacets` tinyint(4) DEFAULT '0',
  `includeOutOfSystemExternalLinks` tinyint(4) DEFAULT '0',
  `facetGroupId` int(11) DEFAULT '0',
  `showStandardReviews` tinyint(4) DEFAULT '1',
  `showGoodReadsReviews` tinyint(4) DEFAULT '1',
  `preferSyndeticsSummary` tinyint(4) DEFAULT '1',
  `showSimilarTitles` tinyint(4) DEFAULT '1',
  `showSimilarAuthors` tinyint(4) DEFAULT '1',
  `showRatings` tinyint(4) DEFAULT '1',
  `showComments` tinyint(4) DEFAULT '1',
  `hideCommentsWithBadWords` tinyint(4) DEFAULT '0',
  `show856LinksAsTab` tinyint(4) DEFAULT '1',
  `showCheckInGrid` tinyint(4) DEFAULT '1',
  `showStaffView` tinyint(4) DEFAULT '1',
  `showLCSubjects` tinyint(4) DEFAULT '1',
  `showBisacSubjects` tinyint(4) DEFAULT '1',
  `showFastAddSubjects` tinyint(4) DEFAULT '1',
  `showOtherSubjects` tinyint(4) DEFAULT '1',
  `showInMainDetails` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `defaultAvailabilityToggle` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'global',
  `isDefault` tinyint(4) DEFAULT '0',
  `showItemDueDates` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=2730;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grouped_work_display_settings`
--

LOCK TABLES `grouped_work_display_settings` WRITE;
/*!40000 ALTER TABLE `grouped_work_display_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `grouped_work_display_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grouped_work_facet`
--

DROP TABLE IF EXISTS `grouped_work_facet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grouped_work_facet` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `facetGroupId` int(11) NOT NULL,
  `displayName` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `facetName` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `weight` int(11) NOT NULL DEFAULT '0',
  `numEntriesToShowByDefault` int(11) NOT NULL DEFAULT '5',
  `showAsDropDown` tinyint(4) NOT NULL DEFAULT '0',
  `sortMode` enum('alphabetically','num_results') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'num_results',
  `showAboveResults` tinyint(4) NOT NULL DEFAULT '0',
  `showInResults` tinyint(4) NOT NULL DEFAULT '1',
  `showInAdvancedSearch` tinyint(4) NOT NULL DEFAULT '1',
  `collapseByDefault` tinyint(4) DEFAULT '1',
  `useMoreFacetPopup` tinyint(4) DEFAULT '1',
  `translate` tinyint(4) DEFAULT '0',
  `multiSelect` tinyint(4) DEFAULT '0',
  `canLock` tinyint(4) DEFAULT '0',
  `displayNamePlural` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `groupFacet` (`facetGroupId`,`facetName`)
) ENGINE=InnoDB AUTO_INCREMENT=83 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=204;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grouped_work_facet`
--

LOCK TABLES `grouped_work_facet` WRITE;
/*!40000 ALTER TABLE `grouped_work_facet` DISABLE KEYS */;
/*!40000 ALTER TABLE `grouped_work_facet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grouped_work_facet_groups`
--

DROP TABLE IF EXISTS `grouped_work_facet_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grouped_work_facet_groups` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=4096;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grouped_work_facet_groups`
--

LOCK TABLES `grouped_work_facet_groups` WRITE;
/*!40000 ALTER TABLE `grouped_work_facet_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `grouped_work_facet_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grouped_work_more_details`
--

DROP TABLE IF EXISTS `grouped_work_more_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grouped_work_more_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `weight` int(11) NOT NULL DEFAULT '0',
  `source` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `collapseByDefault` tinyint(1) DEFAULT NULL,
  `groupedWorkSettingsId` int(11) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=712;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grouped_work_more_details`
--

LOCK TABLES `grouped_work_more_details` WRITE;
/*!40000 ALTER TABLE `grouped_work_more_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `grouped_work_more_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grouped_work_primary_identifiers`
--

DROP TABLE IF EXISTS `grouped_work_primary_identifiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grouped_work_primary_identifiers` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `grouped_work_id` bigint(20) NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `identifier` varchar(36) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `type` (`type`,`identifier`),
  KEY `grouped_record_id` (`grouped_work_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grouped_work_primary_identifiers`
--

LOCK TABLES `grouped_work_primary_identifiers` WRITE;
/*!40000 ALTER TABLE `grouped_work_primary_identifiers` DISABLE KEYS */;
/*!40000 ALTER TABLE `grouped_work_primary_identifiers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grouped_work_record_item_url`
--

DROP TABLE IF EXISTS `grouped_work_record_item_url`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grouped_work_record_item_url` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupedWorkItemId` int(11) DEFAULT NULL,
  `scopeId` int(11) DEFAULT NULL,
  `url` varchar(1000) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `groupedWorkItemId` (`groupedWorkItemId`,`scopeId`)
) ENGINE=InnoDB AUTO_INCREMENT=2040305 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=83;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grouped_work_record_item_url`
--

LOCK TABLES `grouped_work_record_item_url` WRITE;
/*!40000 ALTER TABLE `grouped_work_record_item_url` DISABLE KEYS */;
/*!40000 ALTER TABLE `grouped_work_record_item_url` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grouped_work_record_items`
--

DROP TABLE IF EXISTS `grouped_work_record_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grouped_work_record_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupedWorkRecordId` int(11) NOT NULL,
  `groupedWorkVariationId` int(11) NOT NULL,
  `itemId` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `shelfLocationId` int(11) DEFAULT NULL,
  `callNumberId` int(11) DEFAULT NULL,
  `sortableCallNumberId` int(11) DEFAULT NULL,
  `numCopies` int(11) DEFAULT NULL,
  `isOrderItem` tinyint(4) DEFAULT '0',
  `statusId` int(11) DEFAULT NULL,
  `dateAdded` bigint(20) DEFAULT NULL,
  `locationCodeId` int(11) DEFAULT NULL,
  `subLocationCodeId` int(11) DEFAULT NULL,
  `lastCheckInDate` bigint(20) DEFAULT NULL,
  `groupedStatusId` int(11) DEFAULT NULL,
  `available` tinyint(1) DEFAULT NULL,
  `holdable` tinyint(1) DEFAULT NULL,
  `inLibraryUseOnly` tinyint(1) DEFAULT NULL,
  `locationOwnedScopes` varchar(1000) COLLATE utf8mb4_general_ci DEFAULT '~',
  `libraryOwnedScopes` varchar(1000) COLLATE utf8mb4_general_ci DEFAULT '~',
  `recordIncludedScopes` varchar(1000) COLLATE utf8mb4_general_ci DEFAULT '~',
  PRIMARY KEY (`id`),
  UNIQUE KEY `itemId` (`itemId`,`groupedWorkRecordId`),
  KEY `groupedWorkRecordId` (`groupedWorkRecordId`),
  KEY `groupedWorkVariationId` (`groupedWorkVariationId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grouped_work_record_items`
--

LOCK TABLES `grouped_work_record_items` WRITE;
/*!40000 ALTER TABLE `grouped_work_record_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `grouped_work_record_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grouped_work_records`
--

DROP TABLE IF EXISTS `grouped_work_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grouped_work_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupedWorkId` int(11) NOT NULL,
  `sourceId` int(11) DEFAULT NULL,
  `recordIdentifier` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `formatId` int(11) DEFAULT NULL,
  `formatCategoryId` int(11) DEFAULT NULL,
  `editionId` int(11) DEFAULT NULL,
  `publisherId` int(11) DEFAULT NULL,
  `publicationDateId` int(11) DEFAULT NULL,
  `physicalDescriptionId` int(11) DEFAULT NULL,
  `languageId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sourceId` (`sourceId`,`recordIdentifier`),
  KEY `groupedWorkId` (`groupedWorkId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grouped_work_records`
--

LOCK TABLES `grouped_work_records` WRITE;
/*!40000 ALTER TABLE `grouped_work_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `grouped_work_records` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `after_grouped_work_records_delete` AFTER DELETE ON `grouped_work_records` FOR EACH ROW DELETE FROM grouped_work_record_items where groupedWorkRecordId = old.id */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `grouped_work_scheduled_index`
--

DROP TABLE IF EXISTS `grouped_work_scheduled_index`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grouped_work_scheduled_index` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `permanent_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `indexAfter` int(11) NOT NULL,
  `processed` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `allfields` (`processed`,`indexAfter`,`permanent_id`),
  KEY `permanent_id` (`permanent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=114452 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=78;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grouped_work_scheduled_index`
--

LOCK TABLES `grouped_work_scheduled_index` WRITE;
/*!40000 ALTER TABLE `grouped_work_scheduled_index` DISABLE KEYS */;
/*!40000 ALTER TABLE `grouped_work_scheduled_index` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grouped_work_variation`
--

DROP TABLE IF EXISTS `grouped_work_variation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grouped_work_variation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupedWorkId` int(11) NOT NULL,
  `primaryLanguageId` int(11) DEFAULT NULL,
  `eContentSourceId` int(11) DEFAULT NULL,
  `formatId` int(11) DEFAULT NULL,
  `formatCategoryId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `groupedWorkId` (`groupedWorkId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grouped_work_variation`
--

LOCK TABLES `grouped_work_variation` WRITE;
/*!40000 ALTER TABLE `grouped_work_variation` DISABLE KEYS */;
/*!40000 ALTER TABLE `grouped_work_variation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hold_request_confirmation`
--

DROP TABLE IF EXISTS `hold_request_confirmation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hold_request_confirmation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `requestId` varchar(36) COLLATE utf8mb4_general_ci NOT NULL,
  `additionalParams` mediumtext COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hold_request_confirmation`
--

LOCK TABLES `hold_request_confirmation` WRITE;
/*!40000 ALTER TABLE `hold_request_confirmation` DISABLE KEYS */;
/*!40000 ALTER TABLE `hold_request_confirmation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `holiday`
--

DROP TABLE IF EXISTS `holiday`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `holiday` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of holiday',
  `libraryId` int(11) NOT NULL COMMENT 'The library system id',
  `date` date NOT NULL COMMENT 'Date of holiday',
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Name of holiday',
  PRIMARY KEY (`id`),
  UNIQUE KEY `LibraryDate` (`date`,`libraryId`),
  KEY `Library` (`libraryId`),
  KEY `Date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `holiday`
--

LOCK TABLES `holiday` WRITE;
/*!40000 ALTER TABLE `holiday` DISABLE KEYS */;
/*!40000 ALTER TABLE `holiday` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hoopla_export`
--

DROP TABLE IF EXISTS `hoopla_export`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hoopla_export` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hooplaId` int(11) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `title` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kind` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pa` tinyint(4) NOT NULL DEFAULT '0',
  `demo` tinyint(4) NOT NULL DEFAULT '0',
  `profanity` tinyint(4) NOT NULL DEFAULT '0',
  `rating` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `abridged` tinyint(4) NOT NULL DEFAULT '0',
  `children` tinyint(4) NOT NULL DEFAULT '0',
  `price` double NOT NULL DEFAULT '0',
  `rawChecksum` bigint(20) DEFAULT NULL,
  `rawResponse` mediumblob,
  `dateFirstDetected` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hooplaId` (`hooplaId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hoopla_export`
--

LOCK TABLES `hoopla_export` WRITE;
/*!40000 ALTER TABLE `hoopla_export` DISABLE KEYS */;
/*!40000 ALTER TABLE `hoopla_export` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hoopla_export_log`
--

DROP TABLE IF EXISTS `hoopla_export_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hoopla_export_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `notes` mediumtext COLLATE utf8mb4_general_ci COMMENT 'Additional information about the run',
  `numProducts` int(11) DEFAULT '0',
  `numErrors` int(11) DEFAULT '0',
  `numAdded` int(11) DEFAULT '0',
  `numDeleted` int(11) DEFAULT '0',
  `numUpdated` int(11) DEFAULT '0',
  `numSkipped` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hoopla_export_log`
--

LOCK TABLES `hoopla_export_log` WRITE;
/*!40000 ALTER TABLE `hoopla_export_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `hoopla_export_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hoopla_record_usage`
--

DROP TABLE IF EXISTS `hoopla_record_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hoopla_record_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hooplaId` int(11) DEFAULT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `timesCheckedOut` int(11) NOT NULL,
  `instance` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`hooplaId`,`year`,`month`),
  UNIQUE KEY `instance_2` (`instance`,`hooplaId`,`year`,`month`),
  UNIQUE KEY `instance_3` (`instance`,`hooplaId`,`year`,`month`),
  KEY `year` (`year`,`month`),
  KEY `year_2` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hoopla_record_usage`
--

LOCK TABLES `hoopla_record_usage` WRITE;
/*!40000 ALTER TABLE `hoopla_record_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `hoopla_record_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hoopla_scopes`
--

DROP TABLE IF EXISTS `hoopla_scopes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hoopla_scopes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
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
  `ratingsToExclude` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `excludeAbridged` tinyint(4) DEFAULT '0',
  `excludeParentalAdvisory` tinyint(4) DEFAULT '0',
  `excludeProfanity` tinyint(4) DEFAULT '0',
  `settingId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=16384;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hoopla_scopes`
--

LOCK TABLES `hoopla_scopes` WRITE;
/*!40000 ALTER TABLE `hoopla_scopes` DISABLE KEYS */;
/*!40000 ALTER TABLE `hoopla_scopes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hoopla_settings`
--

DROP TABLE IF EXISTS `hoopla_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hoopla_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `apiUrl` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `libraryId` int(11) DEFAULT '0',
  `apiUsername` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `apiPassword` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `runFullUpdate` tinyint(1) DEFAULT '0',
  `lastUpdateOfChangedRecords` int(11) DEFAULT '0',
  `lastUpdateOfAllRecords` int(11) DEFAULT '0',
  `excludeTitlesWithCopiesFromOtherVendors` tinyint(4) DEFAULT '0',
  `regroupAllRecords` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=16384;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hoopla_settings`
--

LOCK TABLES `hoopla_settings` WRITE;
/*!40000 ALTER TABLE `hoopla_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `hoopla_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `host_information`
--

DROP TABLE IF EXISTS `host_information`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `host_information` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `host` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `libraryId` int(11) DEFAULT NULL,
  `locationId` int(11) DEFAULT '-1',
  `defaultPath` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `host_information`
--

LOCK TABLES `host_information` WRITE;
/*!40000 ALTER TABLE `host_information` DISABLE KEYS */;
/*!40000 ALTER TABLE `host_information` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ils_extract_log`
--

DROP TABLE IF EXISTS `ils_extract_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ils_extract_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log',
  `indexingProfile` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `numProducts` int(11) DEFAULT '0',
  `numErrors` int(11) DEFAULT '0',
  `numAdded` int(11) DEFAULT '0',
  `numDeleted` int(11) DEFAULT '0',
  `numUpdated` int(11) DEFAULT '0',
  `notes` mediumtext COLLATE utf8mb4_general_ci COMMENT 'Additional information about the run',
  `numSkipped` int(11) DEFAULT '0',
  `numRegrouped` int(11) DEFAULT '0',
  `numChangedAfterGrouping` int(11) DEFAULT '0',
  `isFullUpdate` tinyint(1) DEFAULT NULL,
  `currentId` varchar(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=1503;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ils_extract_log`
--

LOCK TABLES `ils_extract_log` WRITE;
/*!40000 ALTER TABLE `ils_extract_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `ils_extract_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ils_hold_summary`
--

DROP TABLE IF EXISTS `ils_hold_summary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ils_hold_summary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ilsId` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `numHolds` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ilsId` (`ilsId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ils_hold_summary`
--

LOCK TABLES `ils_hold_summary` WRITE;
/*!40000 ALTER TABLE `ils_hold_summary` DISABLE KEYS */;
/*!40000 ALTER TABLE `ils_hold_summary` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ils_marc_checksums`
--

DROP TABLE IF EXISTS `ils_marc_checksums`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ils_marc_checksums` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ilsId` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `checksum` bigint(20) unsigned NOT NULL,
  `dateFirstDetected` bigint(20) DEFAULT NULL,
  `source` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ils',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ilsId` (`ilsId`),
  UNIQUE KEY `source` (`source`,`ilsId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ils_marc_checksums`
--

LOCK TABLES `ils_marc_checksums` WRITE;
/*!40000 ALTER TABLE `ils_marc_checksums` DISABLE KEYS */;
/*!40000 ALTER TABLE `ils_marc_checksums` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ils_record_usage`
--

DROP TABLE IF EXISTS `ils_record_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ils_record_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `indexingProfileId` int(11) NOT NULL,
  `recordId` varchar(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `timesUsed` int(11) NOT NULL,
  `pdfDownloadCount` int(11) DEFAULT '0',
  `supplementalFileDownloadCount` int(11) DEFAULT '0',
  `pdfViewCount` int(11) DEFAULT '0',
  `instance` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`indexingProfileId`,`recordId`,`year`,`month`),
  UNIQUE KEY `instance_2` (`instance`,`indexingProfileId`,`recordId`,`year`,`month`),
  KEY `year` (`year`,`month`),
  KEY `year_2` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=712;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ils_record_usage`
--

LOCK TABLES `ils_record_usage` WRITE;
/*!40000 ALTER TABLE `ils_record_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `ils_record_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ils_records`
--

DROP TABLE IF EXISTS `ils_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ils_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ilsId` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `checksum` bigint(20) unsigned NOT NULL,
  `dateFirstDetected` bigint(20) DEFAULT NULL,
  `source` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ils',
  `deleted` tinyint(1) DEFAULT NULL,
  `dateDeleted` int(11) DEFAULT NULL,
  `suppressedNoMarcAvailable` tinyint(1) DEFAULT NULL,
  `sourceData` mediumblob,
  `lastModified` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `source` (`source`,`ilsId`)
) ENGINE=InnoDB AUTO_INCREMENT=186968 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=1597;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ils_records`
--

LOCK TABLES `ils_records` WRITE;
/*!40000 ALTER TABLE `ils_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `ils_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ils_volume_info`
--

DROP TABLE IF EXISTS `ils_volume_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ils_volume_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recordId` varchar(50) CHARACTER SET utf8mb4 NOT NULL COMMENT 'Full Record ID including the source',
  `displayLabel` varchar(512) CHARACTER SET utf8mb4 NOT NULL,
  `relatedItems` text CHARACTER SET utf8mb4,
  `volumeId` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `displayOrder` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `recordVolume` (`recordId`,`volumeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ils_volume_info`
--

LOCK TABLES `ils_volume_info` WRITE;
/*!40000 ALTER TABLE `ils_volume_info` DISABLE KEYS */;
/*!40000 ALTER TABLE `ils_volume_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `image_uploads`
--

DROP TABLE IF EXISTS `image_uploads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `image_uploads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `fullSizePath` varchar(512) COLLATE utf8mb4_general_ci NOT NULL,
  `generateMediumSize` tinyint(1) NOT NULL DEFAULT '0',
  `mediumSizePath` varchar(512) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `generateSmallSize` tinyint(1) NOT NULL DEFAULT '0',
  `smallSizePath` varchar(512) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `generateLargeSize` tinyint(1) NOT NULL DEFAULT '1',
  `largeSizePath` varchar(512) COLLATE utf8mb4_general_ci DEFAULT '',
  `generateXLargeSize` tinyint(1) NOT NULL DEFAULT '1',
  `xLargeSizePath` varchar(512) COLLATE utf8mb4_general_ci DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `type` (`type`,`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `image_uploads`
--

LOCK TABLES `image_uploads` WRITE;
/*!40000 ALTER TABLE `image_uploads` DISABLE KEYS */;
/*!40000 ALTER TABLE `image_uploads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `indexed_callnumber`
--

DROP TABLE IF EXISTS `indexed_callnumber`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `indexed_callnumber` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `callNumber` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `callNumber` (`callNumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `indexed_callnumber`
--

LOCK TABLES `indexed_callnumber` WRITE;
/*!40000 ALTER TABLE `indexed_callnumber` DISABLE KEYS */;
/*!40000 ALTER TABLE `indexed_callnumber` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `indexed_econtentsource`
--

DROP TABLE IF EXISTS `indexed_econtentsource`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `indexed_econtentsource` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eContentSource` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `eContentSource` (`eContentSource`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `indexed_econtentsource`
--

LOCK TABLES `indexed_econtentsource` WRITE;
/*!40000 ALTER TABLE `indexed_econtentsource` DISABLE KEYS */;
/*!40000 ALTER TABLE `indexed_econtentsource` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `indexed_edition`
--

DROP TABLE IF EXISTS `indexed_edition`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `indexed_edition` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `edition` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `edition` (`edition`(500))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `indexed_edition`
--

LOCK TABLES `indexed_edition` WRITE;
/*!40000 ALTER TABLE `indexed_edition` DISABLE KEYS */;
/*!40000 ALTER TABLE `indexed_edition` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `indexed_format`
--

DROP TABLE IF EXISTS `indexed_format`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `indexed_format` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `format` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `format` (`format`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `indexed_format`
--

LOCK TABLES `indexed_format` WRITE;
/*!40000 ALTER TABLE `indexed_format` DISABLE KEYS */;
/*!40000 ALTER TABLE `indexed_format` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `indexed_format_category`
--

DROP TABLE IF EXISTS `indexed_format_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `indexed_format_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `formatCategory` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `formatCategory` (`formatCategory`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `indexed_format_category`
--

LOCK TABLES `indexed_format_category` WRITE;
/*!40000 ALTER TABLE `indexed_format_category` DISABLE KEYS */;
/*!40000 ALTER TABLE `indexed_format_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `indexed_groupedstatus`
--

DROP TABLE IF EXISTS `indexed_groupedstatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `indexed_groupedstatus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupedStatus` varchar(75) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `groupedStatus` (`groupedStatus`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `indexed_groupedstatus`
--

LOCK TABLES `indexed_groupedstatus` WRITE;
/*!40000 ALTER TABLE `indexed_groupedstatus` DISABLE KEYS */;
/*!40000 ALTER TABLE `indexed_groupedstatus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `indexed_itemtype`
--

DROP TABLE IF EXISTS `indexed_itemtype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `indexed_itemtype` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `itemType` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `itemType` (`itemType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `indexed_itemtype`
--

LOCK TABLES `indexed_itemtype` WRITE;
/*!40000 ALTER TABLE `indexed_itemtype` DISABLE KEYS */;
/*!40000 ALTER TABLE `indexed_itemtype` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `indexed_language`
--

DROP TABLE IF EXISTS `indexed_language`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `indexed_language` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `language` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `language` (`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `indexed_language`
--

LOCK TABLES `indexed_language` WRITE;
/*!40000 ALTER TABLE `indexed_language` DISABLE KEYS */;
/*!40000 ALTER TABLE `indexed_language` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `indexed_locationcode`
--

DROP TABLE IF EXISTS `indexed_locationcode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `indexed_locationcode` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `locationCode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `locationCode` (`locationCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `indexed_locationcode`
--

LOCK TABLES `indexed_locationcode` WRITE;
/*!40000 ALTER TABLE `indexed_locationcode` DISABLE KEYS */;
/*!40000 ALTER TABLE `indexed_locationcode` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `indexed_physicaldescription`
--

DROP TABLE IF EXISTS `indexed_physicaldescription`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `indexed_physicaldescription` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `physicalDescription` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `physicalDescription` (`physicalDescription`(500))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `indexed_physicaldescription`
--

LOCK TABLES `indexed_physicaldescription` WRITE;
/*!40000 ALTER TABLE `indexed_physicaldescription` DISABLE KEYS */;
/*!40000 ALTER TABLE `indexed_physicaldescription` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `indexed_publicationdate`
--

DROP TABLE IF EXISTS `indexed_publicationdate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `indexed_publicationdate` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publicationDate` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `publicationDate` (`publicationDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `indexed_publicationdate`
--

LOCK TABLES `indexed_publicationdate` WRITE;
/*!40000 ALTER TABLE `indexed_publicationdate` DISABLE KEYS */;
/*!40000 ALTER TABLE `indexed_publicationdate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `indexed_publisher`
--

DROP TABLE IF EXISTS `indexed_publisher`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `indexed_publisher` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publisher` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `publisher` (`publisher`),
  UNIQUE KEY `publisher_2` (`publisher`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `indexed_publisher`
--

LOCK TABLES `indexed_publisher` WRITE;
/*!40000 ALTER TABLE `indexed_publisher` DISABLE KEYS */;
/*!40000 ALTER TABLE `indexed_publisher` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `indexed_record_source`
--

DROP TABLE IF EXISTS `indexed_record_source`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `indexed_record_source` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `subSource` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `source` (`source`,`subSource`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `indexed_record_source`
--

LOCK TABLES `indexed_record_source` WRITE;
/*!40000 ALTER TABLE `indexed_record_source` DISABLE KEYS */;
/*!40000 ALTER TABLE `indexed_record_source` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `indexed_shelflocation`
--

DROP TABLE IF EXISTS `indexed_shelflocation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `indexed_shelflocation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shelfLocation` varchar(600) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shelfLocation` (`shelfLocation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `indexed_shelflocation`
--

LOCK TABLES `indexed_shelflocation` WRITE;
/*!40000 ALTER TABLE `indexed_shelflocation` DISABLE KEYS */;
/*!40000 ALTER TABLE `indexed_shelflocation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `indexed_status`
--

DROP TABLE IF EXISTS `indexed_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `indexed_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(75) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `indexed_status`
--

LOCK TABLES `indexed_status` WRITE;
/*!40000 ALTER TABLE `indexed_status` DISABLE KEYS */;
/*!40000 ALTER TABLE `indexed_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `indexed_sublocationcode`
--

DROP TABLE IF EXISTS `indexed_sublocationcode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `indexed_sublocationcode` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subLocationCode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subLocationCode` (`subLocationCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `indexed_sublocationcode`
--

LOCK TABLES `indexed_sublocationcode` WRITE;
/*!40000 ALTER TABLE `indexed_sublocationcode` DISABLE KEYS */;
/*!40000 ALTER TABLE `indexed_sublocationcode` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `indexing_profiles`
--

DROP TABLE IF EXISTS `indexing_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `indexing_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `marcPath` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `marcEncoding` enum('MARC8','UTF8','UNIMARC','ISO8859_1','BESTGUESS') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'MARC8',
  `individualMarcPath` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `groupingClass` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'MarcRecordGrouper',
  `indexingClass` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `recordDriver` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'MarcRecord',
  `recordUrlComponent` varchar(25) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Record',
  `formatSource` enum('bib','item','specified') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'bib',
  `recordNumberTag` char(3) COLLATE utf8mb4_general_ci NOT NULL,
  `recordNumberPrefix` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `suppressItemlessBibs` tinyint(1) NOT NULL DEFAULT '1',
  `itemTag` char(3) COLLATE utf8mb4_general_ci NOT NULL,
  `itemRecordNumber` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `useItemBasedCallNumbers` tinyint(1) NOT NULL DEFAULT '1',
  `callNumberPrestamp` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `callNumber` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `callNumberCutter` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `callNumberPoststamp` varchar(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `location` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `locationsToSuppress` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `subLocation` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `shelvingLocation` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `volume` varchar(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `itemUrl` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `barcode` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `statusesToSuppress` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `totalCheckouts` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lastYearCheckouts` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `yearToDateCheckouts` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `totalRenewals` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `iType` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dueDate` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dateCreated` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dateCreatedFormat` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `iCode2` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `useICode2Suppression` tinyint(1) NOT NULL DEFAULT '1',
  `format` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `eContentDescriptor` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `orderTag` char(3) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `orderStatus` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `orderLocation` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `orderCopies` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `orderCode3` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `collection` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `catalogDriver` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nonHoldableITypes` varchar(600) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nonHoldableStatuses` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nonHoldableLocations` varchar(512) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lastCheckinFormat` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lastCheckinDate` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `orderLocationSingle` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `specifiedFormat` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `specifiedFormatCategory` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `specifiedFormatBoost` int(11) DEFAULT NULL,
  `filenamesToInclude` varchar(250) COLLATE utf8mb4_general_ci DEFAULT '.*\\.ma?rc',
  `collectionsToSuppress` varchar(100) COLLATE utf8mb4_general_ci DEFAULT '',
  `numCharsToCreateFolderFrom` int(11) DEFAULT '4',
  `createFolderFromLeadingCharacters` tinyint(1) DEFAULT '1',
  `dueDateFormat` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'yyMMdd',
  `doAutomaticEcontentSuppression` tinyint(1) DEFAULT '1',
  `iTypesToSuppress` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `iCode2sToSuppress` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bCode3sToSuppress` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sierraRecordFixedFieldsTag` char(3) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bCode3` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `recordNumberField` char(1) COLLATE utf8mb4_general_ci DEFAULT 'a',
  `recordNumberSubfield` char(1) COLLATE utf8mb4_general_ci DEFAULT 'a',
  `runFullUpdate` tinyint(1) DEFAULT '0',
  `lastUpdateOfChangedRecords` int(11) DEFAULT '0',
  `lastUpdateOfAllRecords` int(11) DEFAULT '0',
  `lastUpdateFromMarcExport` int(11) DEFAULT '0',
  `lastVolumeExportTimestamp` int(11) DEFAULT '0',
  `regroupAllRecords` tinyint(1) DEFAULT '0',
  `treatUnknownLanguageAs` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'English',
  `treatUndeterminedLanguageAs` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'English',
  `checkRecordForLargePrint` tinyint(1) DEFAULT '0',
  `determineAudienceBy` tinyint(4) DEFAULT '0',
  `audienceSubfield` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `includeLocationNameInDetailedLocation` tinyint(1) DEFAULT '1',
  `lastUpdateOfAuthorities` int(11) DEFAULT '0',
  `fullMarcExportRecordIdThreshold` int(11) DEFAULT '0',
  `lastChangeProcessed` int(11) DEFAULT '0',
  `noteSubfield` char(1) COLLATE utf8mb4_general_ci DEFAULT '',
  `treatUnknownAudienceAs` varchar(10) COLLATE utf8mb4_general_ci DEFAULT 'Unknown',
  `suppressRecordsWithUrlsMatching` varchar(512) COLLATE utf8mb4_general_ci DEFAULT 'overdrive.com|contentreserve.com|hoopla|yourcloudlibrary|axis360.baker-taylor.com',
  `determineLiteraryFormBy` tinyint(4) DEFAULT '0',
  `literaryFormSubfield` char(1) COLLATE utf8mb4_general_ci DEFAULT '',
  `hideUnknownLiteraryForm` tinyint(4) DEFAULT '0',
  `hideNotCodedLiteraryForm` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `ip_lookup`
--

DROP TABLE IF EXISTS `ip_lookup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ip_lookup` (
  `id` int(25) NOT NULL AUTO_INCREMENT,
  `locationid` int(5) NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `ip` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `startIpVal` bigint(20) DEFAULT NULL,
  `endIpVal` bigint(20) DEFAULT NULL,
  `isOpac` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `blockAccess` tinyint(4) NOT NULL DEFAULT '0',
  `allowAPIAccess` tinyint(4) NOT NULL DEFAULT '0',
  `showDebuggingInformation` tinyint(4) NOT NULL DEFAULT '0',
  `logTimingInformation` tinyint(4) DEFAULT '0',
  `logAllQueries` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `startIpVal` (`startIpVal`),
  KEY `endIpVal` (`endIpVal`),
  KEY `startIpVal_2` (`startIpVal`),
  KEY `endIpVal_2` (`endIpVal`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ip_lookup`
--

LOCK TABLES `ip_lookup` WRITE;
/*!40000 ALTER TABLE `ip_lookup` DISABLE KEYS */;
INSERT INTO `ip_lookup` VALUES (1,-1,'Internal','127.0.0.1',2130706433,2130706433,0,0,1,1,0,0);
/*!40000 ALTER TABLE `ip_lookup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `javascript_snippet_library`
--

DROP TABLE IF EXISTS `javascript_snippet_library`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `javascript_snippet_library` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `javascriptSnippetId` int(11) DEFAULT NULL,
  `libraryId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `javascriptSnippetLibrary` (`javascriptSnippetId`,`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `javascript_snippet_library`
--

LOCK TABLES `javascript_snippet_library` WRITE;
/*!40000 ALTER TABLE `javascript_snippet_library` DISABLE KEYS */;
/*!40000 ALTER TABLE `javascript_snippet_library` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `javascript_snippet_location`
--

DROP TABLE IF EXISTS `javascript_snippet_location`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `javascript_snippet_location` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `javascriptSnippetId` int(11) DEFAULT NULL,
  `locationId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `javascriptSnippetLocation` (`javascriptSnippetId`,`locationId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `javascript_snippet_location`
--

LOCK TABLES `javascript_snippet_location` WRITE;
/*!40000 ALTER TABLE `javascript_snippet_location` DISABLE KEYS */;
/*!40000 ALTER TABLE `javascript_snippet_location` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `javascript_snippets`
--

DROP TABLE IF EXISTS `javascript_snippets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `javascript_snippets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `snippet` mediumtext COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `javascript_snippets`
--

LOCK TABLES `javascript_snippets` WRITE;
/*!40000 ALTER TABLE `javascript_snippets` DISABLE KEYS */;
/*!40000 ALTER TABLE `javascript_snippets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `languages`
--

DROP TABLE IF EXISTS `languages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `languages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `weight` int(11) NOT NULL DEFAULT '0',
  `code` char(3) COLLATE utf8mb4_general_ci NOT NULL,
  `displayName` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `displayNameEnglish` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `facetValue` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `displayToTranslatorsOnly` tinyint(1) DEFAULT '0',
  `locale` varchar(10) COLLATE utf8mb4_general_ci DEFAULT 'en-US',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=4096;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Dumping data for table `languages`
--

LOCK TABLES `languages` WRITE;
/*!40000 ALTER TABLE `languages` DISABLE KEYS */;
INSERT INTO `languages` VALUES (1,0,'en','English','English','English',0,'en-US');
/*!40000 ALTER TABLE `languages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `layout_settings`
--

DROP TABLE IF EXISTS `layout_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `layout_settings` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `homeLinkText` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Home',
  `showLibraryHoursAndLocationsLink` int(11) DEFAULT '1',
  `useHomeLink` tinyint(1) DEFAULT '0',
  `showBookIcon` tinyint(1) DEFAULT '0',
  `browseLinkText` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'Browse',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=8192;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `layout_settings`
--

LOCK TABLES `layout_settings` WRITE;
/*!40000 ALTER TABLE `layout_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `layout_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library`
--

DROP TABLE IF EXISTS `library`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `library` (
  `libraryId` int(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique id to identify the library within the system',
  `subdomain` varchar(25) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'The subdomain which can be used to access settings for the library',
  `displayName` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'The name of the library which should be shown in titles.',
  `showLibraryFacet` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Whether or not the user can see and use the library facet to change to another branch in their library system.',
  `showConsortiumFacet` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether or not the user can see and use the consortium facet to change to other library systems. ',
  `allowInBranchHolds` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Whether or not the user can place holds for their branch.  If this isn''t shown, they won''t be able to place holds for books at the location they are in.  If set to false, they won''t be able to place any holds. ',
  `allowInLibraryHolds` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Whether or not the user can place holds for books at other locations in their library system',
  `allowConsortiumHolds` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether or not the user can place holds for any book anywhere in the consortium.  ',
  `scope` smallint(6) DEFAULT '0',
  `useScope` tinyint(4) DEFAULT '0',
  `hideCommentsWithBadWords` tinyint(4) DEFAULT '0',
  `showStandardReviews` tinyint(4) DEFAULT '1',
  `showHoldButton` tinyint(4) DEFAULT '1',
  `showLoginButton` tinyint(4) DEFAULT '1',
  `showEmailThis` tinyint(4) DEFAULT '1',
  `showComments` tinyint(4) DEFAULT '1',
  `showFavorites` tinyint(4) DEFAULT '1',
  `inSystemPickupsOnly` tinyint(4) DEFAULT '0',
  `facetLabel` varchar(75) COLLATE utf8mb4_general_ci DEFAULT '',
  `finePaymentType` tinyint(1) DEFAULT NULL,
  `repeatSearchOption` enum('none','librarySystem','marmot','all') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'all' COMMENT 'Where to allow repeating search.  Valid options are: none, librarySystem, marmot, all',
  `repeatInProspector` tinyint(4) DEFAULT '0',
  `repeatInWorldCat` tinyint(4) DEFAULT '0',
  `systemsToRepeatIn` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '',
  `repeatInOverdrive` tinyint(4) NOT NULL DEFAULT '0',
  `overdriveAuthenticationILSName` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `overdriveRequirePin` tinyint(1) NOT NULL DEFAULT '0',
  `homeLink` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'default',
  `showAdvancedSearchbox` tinyint(4) NOT NULL DEFAULT '1',
  `validPickupSystems` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '',
  `allowProfileUpdates` tinyint(4) NOT NULL DEFAULT '1',
  `allowRenewals` tinyint(4) NOT NULL DEFAULT '1',
  `allowFreezeHolds` tinyint(4) NOT NULL DEFAULT '0',
  `showItsHere` tinyint(4) NOT NULL DEFAULT '1',
  `holdDisclaimer` longtext COLLATE utf8mb4_general_ci,
  `showHoldCancelDate` tinyint(4) NOT NULL DEFAULT '0',
  `enableProspectorIntegration` tinyint(4) NOT NULL DEFAULT '0',
  `prospectorCode` varchar(10) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `showRatings` tinyint(4) NOT NULL DEFAULT '1',
  `minimumFineAmount` float NOT NULL DEFAULT '0',
  `enableGenealogy` tinyint(4) NOT NULL DEFAULT '0',
  `enableCourseReserves` tinyint(1) NOT NULL DEFAULT '0',
  `exportOptions` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'RefWorks|EndNote',
  `enableSelfRegistration` tinyint(4) NOT NULL DEFAULT '0',
  `useHomeLinkInBreadcrumbs` tinyint(4) NOT NULL DEFAULT '0',
  `enableMaterialsRequest` tinyint(4) DEFAULT '1',
  `eContentLinkRules` varchar(512) COLLATE utf8mb4_general_ci DEFAULT '',
  `notesTabName` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Notes',
  `showHoldButtonInSearchResults` tinyint(4) DEFAULT '1',
  `showSimilarAuthors` tinyint(4) DEFAULT '1',
  `showSimilarTitles` tinyint(4) DEFAULT '1',
  `show856LinksAsTab` tinyint(4) DEFAULT '0',
  `applyNumberOfHoldingsBoost` tinyint(4) DEFAULT '1',
  `worldCatUrl` varchar(100) COLLATE utf8mb4_general_ci DEFAULT '',
  `worldCatQt` varchar(40) COLLATE utf8mb4_general_ci DEFAULT '',
  `preferSyndeticsSummary` tinyint(4) DEFAULT '1',
  `showGoDeeper` tinyint(4) DEFAULT '1',
  `showProspectorResultsAtEndOfSearch` tinyint(4) DEFAULT '1',
  `defaultNotNeededAfterDays` int(11) DEFAULT '0',
  `showCheckInGrid` int(11) DEFAULT '1',
  `homeLinkText` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Home',
  `showOtherFormatCategory` tinyint(1) DEFAULT '1',
  `showWikipediaContent` tinyint(1) DEFAULT '1',
  `payFinesLink` varchar(512) COLLATE utf8mb4_general_ci DEFAULT 'default',
  `payFinesLinkText` varchar(512) COLLATE utf8mb4_general_ci DEFAULT 'Click to Pay Fines Online',
  `ilsCode` varchar(75) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `systemMessage` mediumtext COLLATE utf8mb4_general_ci,
  `restrictSearchByLibrary` tinyint(1) DEFAULT '0',
  `enableOverdriveCollection` tinyint(1) DEFAULT '1',
  `includeOutOfSystemExternalLinks` tinyint(1) DEFAULT '0',
  `restrictOwningBranchesAndSystems` tinyint(1) DEFAULT '1',
  `showAvailableAtAnyLocation` tinyint(1) DEFAULT '1',
  `allowPatronAddressUpdates` tinyint(1) DEFAULT '1',
  `showWorkPhoneInProfile` tinyint(1) DEFAULT '0',
  `showNoticeTypeInProfile` tinyint(1) DEFAULT '0',
  `allowPickupLocationUpdates` tinyint(1) DEFAULT '0',
  `accountingUnit` int(11) DEFAULT '10',
  `additionalCss` longtext COLLATE utf8mb4_general_ci,
  `allowPinReset` tinyint(1) DEFAULT NULL,
  `maxRequestsPerYear` int(11) DEFAULT '60',
  `maxOpenRequests` int(11) DEFAULT '5',
  `twitterLink` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '',
  `pinterestLink` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `youtubeLink` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `instagramLink` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `goodreadsLink` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `facebookLink` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '',
  `generalContactLink` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '',
  `repeatInOnlineCollection` int(11) DEFAULT '1',
  `showExpirationWarnings` tinyint(1) DEFAULT '1',
  `econtentLocationsToInclude` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `showLibraryHoursAndLocationsLink` int(11) DEFAULT '1',
  `showLibraryHoursNoticeOnAccountPages` tinyint(1) DEFAULT '1',
  `showShareOnExternalSites` int(11) DEFAULT '1',
  `showGoodReadsReviews` int(11) DEFAULT '1',
  `showStaffView` int(11) DEFAULT '1',
  `showSearchTools` int(11) DEFAULT '1',
  `barcodePrefix` varchar(15) COLLATE utf8mb4_general_ci DEFAULT '',
  `minBarcodeLength` int(11) DEFAULT '0',
  `maxBarcodeLength` int(11) DEFAULT '0',
  `showDisplayNameInHeader` tinyint(4) DEFAULT '0',
  `headerText` longtext COLLATE utf8mb4_general_ci,
  `promptForBirthDateInSelfReg` tinyint(4) DEFAULT '0',
  `availabilityToggleLabelSuperScope` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Entire Collection',
  `availabilityToggleLabelLocal` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '{display name}',
  `availabilityToggleLabelAvailable` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Available Now',
  `loginFormUsernameLabel` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'Your Name',
  `loginFormPasswordLabel` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'Library Card Number',
  `showDetailedHoldNoticeInformation` tinyint(4) DEFAULT '1',
  `treatPrintNoticesAsPhoneNotices` tinyint(4) DEFAULT '0',
  `additionalLocationsToShowAvailabilityFor` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `showInMainDetails` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `includeDplaResults` tinyint(1) DEFAULT '0',
  `selfRegistrationFormMessage` mediumtext COLLATE utf8mb4_general_ci,
  `selfRegistrationSuccessMessage` mediumtext COLLATE utf8mb4_general_ci,
  `useHomeLinkForLogo` tinyint(1) DEFAULT '0',
  `addSMSIndicatorToPhone` tinyint(1) DEFAULT '0',
  `showAlternateLibraryOptionsInProfile` tinyint(1) DEFAULT '1',
  `selfRegistrationTemplate` varchar(25) COLLATE utf8mb4_general_ci DEFAULT 'default',
  `defaultBrowseMode` varchar(25) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `externalMaterialsRequestUrl` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `browseCategoryRatingsMode` varchar(25) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `isDefault` tinyint(1) DEFAULT NULL,
  `showHoldButtonForUnavailableOnly` tinyint(1) DEFAULT '0',
  `allowLinkedAccounts` tinyint(1) DEFAULT '1',
  `allowAutomaticSearchReplacements` tinyint(1) DEFAULT '1',
  `includeOverDriveAdult` tinyint(1) DEFAULT '1',
  `includeOverDriveTeen` tinyint(1) DEFAULT '1',
  `includeOverDriveKids` tinyint(1) DEFAULT '1',
  `publicListsToInclude` tinyint(1) DEFAULT '4',
  `showLCSubjects` tinyint(1) DEFAULT '1',
  `showBisacSubjects` tinyint(1) DEFAULT '1',
  `showFastAddSubjects` tinyint(1) DEFAULT '1',
  `showOtherSubjects` tinyint(1) DEFAULT '1',
  `maxFinesToAllowAccountUpdates` float DEFAULT '10',
  `showRefreshAccountButton` tinyint(4) NOT NULL DEFAULT '1',
  `patronNameDisplayStyle` enum('firstinitial_lastname','lastinitial_firstname') COLLATE utf8mb4_general_ci DEFAULT 'firstinitial_lastname',
  `includeAllRecordsInShelvingFacets` tinyint(4) DEFAULT '0',
  `includeAllRecordsInDateAddedFacets` tinyint(4) DEFAULT '0',
  `preventExpiredCardLogin` tinyint(1) DEFAULT '0',
  `showInSearchResultsMainDetails` varchar(255) COLLATE utf8mb4_general_ci DEFAULT 'a:4:{i:0;s:10:"showSeries";i:1;s:13:"showPublisher";i:2;s:19:"showPublicationDate";i:3;s:13:"showLanguages";}',
  `alwaysShowSearchResultsMainDetails` tinyint(1) DEFAULT '0',
  `casHost` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `casPort` smallint(6) DEFAULT NULL,
  `casContext` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `showSidebarMenu` tinyint(4) DEFAULT '1',
  `sidebarMenuButtonText` varchar(40) COLLATE utf8mb4_general_ci DEFAULT 'Help',
  `availabilityToggleLabelAvailableOnline` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '',
  `includeOnlineMaterialsInAvailableToggle` tinyint(1) DEFAULT '1',
  `masqueradeAutomaticTimeoutLength` tinyint(1) unsigned DEFAULT NULL,
  `allowMasqueradeMode` tinyint(1) DEFAULT '0',
  `allowReadingHistoryDisplayInMasqueradeMode` tinyint(1) DEFAULT '0',
  `newMaterialsRequestSummary` mediumtext COLLATE utf8mb4_general_ci,
  `materialsRequestDaysToPreserve` int(11) DEFAULT '0',
  `showGroupedHoldCopiesCount` tinyint(1) DEFAULT '1',
  `interLibraryLoanName` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `interLibraryLoanUrl` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `expirationNearMessage` longtext COLLATE utf8mb4_general_ci,
  `expiredMessage` longtext COLLATE utf8mb4_general_ci,
  `enableCombinedResults` tinyint(1) DEFAULT '0',
  `combinedResultsLabel` varchar(255) COLLATE utf8mb4_general_ci DEFAULT 'Combined Results',
  `defaultToCombinedResults` tinyint(1) DEFAULT '0',
  `hooplaLibraryID` int(10) unsigned DEFAULT NULL,
  `showOnOrderCounts` tinyint(1) DEFAULT '1',
  `sharedOverdriveCollection` tinyint(1) DEFAULT '-1',
  `showSeriesAsTab` tinyint(4) NOT NULL DEFAULT '0',
  `enableAlphaBrowse` tinyint(4) DEFAULT '1',
  `homePageWidgetId` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '',
  `searchGroupedRecords` tinyint(4) DEFAULT '0',
  `showStandardSubjects` tinyint(1) DEFAULT '1',
  `theme` int(11) DEFAULT '1',
  `enableOpenArchives` tinyint(1) DEFAULT '0',
  `hooplaScopeId` int(11) DEFAULT '-1',
  `axis360ScopeId` int(11) DEFAULT '-1',
  `showQuickCopy` tinyint(1) DEFAULT '1',
  `finesToPay` tinyint(1) DEFAULT '1',
  `payPalSandboxMode` tinyint(1) DEFAULT '1',
  `payPalClientId` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payPalClientSecret` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `msbUrl` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `finePaymentOrder` varchar(80) COLLATE utf8mb4_general_ci DEFAULT '',
  `showConvertListsFromClassic` tinyint(1) DEFAULT '0',
  `enableForgotPasswordLink` tinyint(1) DEFAULT '1',
  `selfRegistrationLocationRestrictions` int(11) DEFAULT '2',
  `baseUrl` varchar(75) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `generateSitemap` tinyint(1) DEFAULT '1',
  `selfRegistrationUrl` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `showWhileYouWait` tinyint(1) DEFAULT '1',
  `enableWebBuilder` tinyint(1) DEFAULT '0',
  `useAllCapsWhenSubmittingSelfRegistration` tinyint(1) DEFAULT '0',
  `validSelfRegistrationStates` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '',
  `selfRegistrationPasswordNotes` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '',
  `validSelfRegistrationZipCodes` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '',
  `showAlternateLibraryCard` tinyint(4) DEFAULT '0',
  `showAlternateLibraryCardPassword` tinyint(4) DEFAULT '0',
  `alternateLibraryCardLabel` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '',
  `alternateLibraryCardPasswordLabel` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '',
  `libraryCardBarcodeStyle` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'none',
  `alternateLibraryCardStyle` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'none',
  `allowUsernameUpdates` tinyint(1) DEFAULT '0',
  `useAllCapsWhenUpdatingProfile` tinyint(1) DEFAULT '0',
  `bypassReviewQueueWhenUpdatingProfile` tinyint(1) DEFAULT '0',
  `requireNumericPhoneNumbersWhenUpdatingProfile` tinyint(1) DEFAULT '0',
  `availableHoldDelay` int(11) DEFAULT '0',
  `allowPatronPhoneNumberUpdates` tinyint(1) DEFAULT '1',
  `loginNotes` longtext COLLATE utf8mb4_general_ci,
  `allowRememberPickupLocation` tinyint(1) DEFAULT '1',
  `allowHomeLibraryUpdates` tinyint(1) DEFAULT '1',
  `showOpacNotes` tinyint(1) DEFAULT '0',
  `showBorrowerMessages` tinyint(1) DEFAULT '0',
  `showDebarmentNotes` tinyint(1) DEFAULT '0',
  `symphonyPaymentType` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `symphonyPaymentPolicy` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `allowDeletingILSRequests` tinyint(1) DEFAULT '1',
  `tiktokLink` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '',
  `edsSettingsId` int(11) DEFAULT '-1',
  `workstationId` varchar(10) COLLATE utf8mb4_general_ci DEFAULT '',
  `compriseSettingId` int(11) DEFAULT '-1',
  `proPaySettingId` int(11) DEFAULT '-1',
  `payPalSettingId` int(11) DEFAULT '-1',
  `worldPaySettingId` int(11) DEFAULT '-1',
  `createSearchInterface` tinyint(1) DEFAULT '1',
  `maxDaysToFreeze` int(11) DEFAULT '-1',
  `displayItemBarcode` tinyint(1) DEFAULT '0',
  `treatBibOrItemHoldsAs` tinyint(1) DEFAULT '1',
  `showCardExpirationDate` tinyint(1) DEFAULT '1',
  `showInSelectInterface` tinyint(1) DEFAULT '1',
  `isConsortialCatalog` tinyint(1) DEFAULT '0',
  `showMessagingSettings` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`libraryId`),
  UNIQUE KEY `subdomain` (`subdomain`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library`
--

LOCK TABLES `library` WRITE;
/*!40000 ALTER TABLE `library` DISABLE KEYS */;
INSERT INTO `library` VALUES (2,'main','Main Library',1,0,1,1,0,0,0,0,1,1,1,1,1,1,1,'',0,'none',0,0,'',0,'',0,'',1,'',1,1,1,1,'',0,0,'',1,0,0,0,'RefWorks|EndNote',0,0,0,'','Notes',1,1,1,0,1,'','',1,1,0,-1,0,'Browse Catalog',1,1,'/MyAccount/Fines','Click to Pay Fines Online','.*','',0,1,0,1,1,1,0,1,1,10,'',0,60,5,'',NULL,'','','','','',0,1,'',1,1,1,1,1,1,'',6,14,0,'',0,'Entire Collection','','Available Now','Username','Password',1,1,'','a:9:{i:0;s:10:\"showSeries\";i:1;s:22:\"showPublicationDetails\";i:2;s:11:\"showFormats\";i:3;s:12:\"showEditions\";i:4;s:24:\"showPhysicalDescriptions\";i:5;s:9:\"showISBNs\";i:6;s:10:\"showArInfo\";i:7;s:14:\"showLexileInfo\";i:8;s:18:\"showFountasPinnell\";}',0,'','',1,0,1,'default','covers','','none',1,0,1,1,1,1,1,3,1,1,1,1,-1,0,'lastinitial_firstname',0,0,0,'a:4:{i:0;s:10:\"showSeries\";i:1;s:13:\"showPublisher\";i:2;s:19:\"showPublicationDate\";i:3;s:13:\"showLanguages\";}',0,'',0,'',1,'Help','Available Online',0,120,0,0,'',365,1,'Interlibrary Loan','','','',0,'Combined Results',0,0,1,-1,0,1,'0',0,1,1,0,-1,-1,1,1,1,NULL,NULL,NULL,'',0,1,2,NULL,1,NULL,1,0,0,'','','',0,0,'','','none','none',0,0,0,0,0,1,NULL,1,1,0,0,0,NULL,NULL,1,'',-1,'',-1,-1,-1,-1,1,-1,0,1,1,1,0,1);
/*!40000 ALTER TABLE `library` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_cloud_library_scope`
--

DROP TABLE IF EXISTS `library_cloud_library_scope`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_cloud_library_scope` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `scopeId` int(11) NOT NULL,
  `libraryId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `libraryId` (`libraryId`,`scopeId`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_cloud_library_scope`
--

LOCK TABLES `library_cloud_library_scope` WRITE;
/*!40000 ALTER TABLE `library_cloud_library_scope` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_cloud_library_scope` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_combined_results_section`
--

DROP TABLE IF EXISTS `library_combined_results_section`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_combined_results_section` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `displayName` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `source` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numberOfResultsToShow` int(11) NOT NULL DEFAULT '5',
  `weight` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `LibraryIdIndex` (`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_combined_results_section`
--

LOCK TABLES `library_combined_results_section` WRITE;
/*!40000 ALTER TABLE `library_combined_results_section` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_combined_results_section` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_events_setting`
--

DROP TABLE IF EXISTS `library_events_setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_events_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `settingSource` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `settingId` int(11) NOT NULL,
  `libraryId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settingSource` (`settingSource`,`settingId`,`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_events_setting`
--

LOCK TABLES `library_events_setting` WRITE;
/*!40000 ALTER TABLE `library_events_setting` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_events_setting` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_facet_setting`
--

DROP TABLE IF EXISTS `library_facet_setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_facet_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `displayName` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `facetName` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `weight` int(11) NOT NULL DEFAULT '0',
  `numEntriesToShowByDefault` int(11) NOT NULL DEFAULT '5',
  `showAsDropDown` tinyint(4) NOT NULL DEFAULT '0',
  `sortMode` enum('alphabetically','num_results') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'num_results',
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
) ENGINE=InnoDB AUTO_INCREMENT=85 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='A widget that can be displayed within VuFind or within other';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_facet_setting`
--

LOCK TABLES `library_facet_setting` WRITE;
/*!40000 ALTER TABLE `library_facet_setting` DISABLE KEYS */;
INSERT INTO `library_facet_setting` VALUES (1,2,'Format Category','format_category',1,5,0,'num_results',1,1,0,1,1,0,0,0),(3,2,'Collection','collection',5,5,0,'num_results',0,1,1,1,1,0,0,0),(4,2,'Author','authorStr',13,5,0,'num_results',0,1,0,1,1,0,0,0),(5,2,'Format','format',7,5,0,'num_results',0,1,1,1,1,0,0,0),(6,2,'Subject','topic_facet',10,5,0,'num_results',0,1,0,1,1,0,0,0),(7,2,'Publication Year','publishDate',14,5,0,'num_results',0,1,0,1,1,0,0,0),(8,2,'Language','language',12,5,0,'num_results',0,1,1,1,1,0,0,0),(9,2,'Reading Level','target_audience_full',9,5,0,'alphabetically',0,1,1,1,1,0,0,0),(12,2,'Genre','genre_facet',11,5,0,'num_results',0,1,1,1,1,0,0,0),(18,2,'Available','availability_toggle',2,5,0,'alphabetically',1,1,1,1,1,0,0,0),(46,2,'Added in the Last','time_since_added',16,5,0,'alphabetically',0,1,1,1,1,0,0,0),(50,2,'Awards','awards_facet',15,5,0,'num_results',0,1,1,1,1,0,0,0),(52,2,'Audience','target_audience',3,5,0,'num_results',0,1,1,1,1,0,0,0),(57,2,'Available At','available_at',4,5,0,'num_results',0,1,1,1,1,0,0,0),(59,2,'eContent Collection','econtent_source',6,5,0,'num_results',0,1,1,1,1,0,0,0),(84,2,'Fiction / Non-Fiction','literary_form',8,5,0,'num_results',0,1,1,1,1,0,0,0);
/*!40000 ALTER TABLE `library_facet_setting` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_link_language`
--

DROP TABLE IF EXISTS `library_link_language`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_link_language` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryLinkId` int(11) DEFAULT NULL,
  `languageId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `libraryLinkLanguage` (`libraryLinkId`,`languageId`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=630;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_link_language`
--

LOCK TABLES `library_link_language` WRITE;
/*!40000 ALTER TABLE `library_link_language` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_link_language` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_links`
--

DROP TABLE IF EXISTS `library_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `linkText` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `weight` int(11) NOT NULL DEFAULT '0',
  `htmlContents` longtext COLLATE utf8mb4_general_ci,
  `showExpanded` tinyint(4) DEFAULT '0',
  `openInNewTab` tinyint(4) DEFAULT '1',
  `showToLoggedInUsersOnly` tinyint(4) DEFAULT '0',
  `showInTopMenu` tinyint(4) DEFAULT '0',
  `iconName` varchar(30) COLLATE utf8mb4_general_ci DEFAULT '',
  `alwaysShowIconInTopMenu` tinyint(4) DEFAULT '0',
  `published` tinyint(4) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `libraryId` (`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_links`
--

LOCK TABLES `library_links` WRITE;
/*!40000 ALTER TABLE `library_links` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_links_access`
--

DROP TABLE IF EXISTS `library_links_access`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_links_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryLinkId` int(11) NOT NULL,
  `patronTypeId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `libraryLinkId` (`libraryLinkId`,`patronTypeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_links_access`
--

LOCK TABLES `library_links_access` WRITE;
/*!40000 ALTER TABLE `library_links_access` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_links_access` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_more_details`
--

DROP TABLE IF EXISTS `library_more_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_more_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL DEFAULT '-1',
  `weight` int(11) NOT NULL DEFAULT '0',
  `source` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `collapseByDefault` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `libraryId` (`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_more_details`
--

LOCK TABLES `library_more_details` WRITE;
/*!40000 ALTER TABLE `library_more_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_more_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_open_archives_collection`
--

DROP TABLE IF EXISTS `library_open_archives_collection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_open_archives_collection` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collectionId` int(11) NOT NULL,
  `libraryId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `collectionId` (`collectionId`,`libraryId`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=8192;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_open_archives_collection`
--

LOCK TABLES `library_open_archives_collection` WRITE;
/*!40000 ALTER TABLE `library_open_archives_collection` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_open_archives_collection` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_records_owned`
--

DROP TABLE IF EXISTS `library_records_owned`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_records_owned` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `indexingProfileId` int(11) NOT NULL,
  `location` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `subLocation` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `locationsToExclude` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `subLocationsToExclude` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `libraryId` (`libraryId`),
  KEY `indexingProfileId` (`indexingProfileId`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_records_owned`
--

LOCK TABLES `library_records_owned` WRITE;
/*!40000 ALTER TABLE `library_records_owned` DISABLE KEYS */;
INSERT INTO `library_records_owned` VALUES (1,3,11,'.*','','',''),(2,2,11,'.*','','','');
/*!40000 ALTER TABLE `library_records_owned` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_records_to_include`
--

DROP TABLE IF EXISTS `library_records_to_include`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_records_to_include` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `indexingProfileId` int(11) NOT NULL,
  `location` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `subLocation` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `includeHoldableOnly` tinyint(4) NOT NULL DEFAULT '1',
  `includeItemsOnOrder` tinyint(1) NOT NULL DEFAULT '0',
  `includeEContent` tinyint(1) NOT NULL DEFAULT '0',
  `weight` int(11) NOT NULL,
  `iType` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `audience` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `format` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `marcTagToMatch` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `marcValueToMatch` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `includeExcludeMatches` tinyint(4) DEFAULT '1',
  `urlToMatch` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `urlReplacement` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `locationsToExclude` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `subLocationsToExclude` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `libraryId` (`libraryId`,`indexingProfileId`),
  KEY `indexingProfileId` (`indexingProfileId`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_records_to_include`
--

LOCK TABLES `library_records_to_include` WRITE;
/*!40000 ALTER TABLE `library_records_to_include` DISABLE KEYS */;
INSERT INTO `library_records_to_include` VALUES (3,3,11,'.*','.*',0,1,1,1,'','','','','',1,'','','','');
/*!40000 ALTER TABLE `library_records_to_include` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_search_source`
--

DROP TABLE IF EXISTS `library_search_source`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_search_source` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL DEFAULT '-1',
  `label` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `weight` int(11) NOT NULL DEFAULT '0',
  `searchWhat` enum('catalog','genealogy','overdrive','worldcat','prospector','goldrush','title_browse','author_browse','subject_browse','tags') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `defaultFilter` mediumtext COLLATE utf8mb4_general_ci,
  `defaultSort` enum('relevance','popularity','newest_to_oldest','oldest_to_newest','author','title','user_rating') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `catalogScoping` enum('unscoped','library','location') COLLATE utf8mb4_general_ci DEFAULT 'unscoped',
  PRIMARY KEY (`id`),
  KEY `libraryId` (`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_search_source`
--

LOCK TABLES `library_search_source` WRITE;
/*!40000 ALTER TABLE `library_search_source` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_search_source` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_sideload_scopes`
--

DROP TABLE IF EXISTS `library_sideload_scopes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_sideload_scopes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `sideLoadScopeId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `libraryId` (`libraryId`,`sideLoadScopeId`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=4096;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_sideload_scopes`
--

LOCK TABLES `library_sideload_scopes` WRITE;
/*!40000 ALTER TABLE `library_sideload_scopes` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_sideload_scopes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_web_builder_basic_page`
--

DROP TABLE IF EXISTS `library_web_builder_basic_page`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_web_builder_basic_page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `basicPageId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `basicPageId` (`basicPageId`),
  KEY `libraryId` (`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_web_builder_basic_page`
--

LOCK TABLES `library_web_builder_basic_page` WRITE;
/*!40000 ALTER TABLE `library_web_builder_basic_page` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_web_builder_basic_page` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_web_builder_custom_form`
--

DROP TABLE IF EXISTS `library_web_builder_custom_form`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_web_builder_custom_form` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `formId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `formId` (`formId`),
  KEY `libraryId` (`libraryId`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=16384;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_web_builder_custom_form`
--

LOCK TABLES `library_web_builder_custom_form` WRITE;
/*!40000 ALTER TABLE `library_web_builder_custom_form` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_web_builder_custom_form` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_web_builder_portal_page`
--

DROP TABLE IF EXISTS `library_web_builder_portal_page`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_web_builder_portal_page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `portalPageId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `libraryId` (`libraryId`),
  KEY `portalPageId` (`portalPageId`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=4096;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_web_builder_portal_page`
--

LOCK TABLES `library_web_builder_portal_page` WRITE;
/*!40000 ALTER TABLE `library_web_builder_portal_page` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_web_builder_portal_page` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_web_builder_resource`
--

DROP TABLE IF EXISTS `library_web_builder_resource`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_web_builder_resource` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `webResourceId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `libraryId` (`libraryId`),
  KEY `webResourceId` (`webResourceId`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_web_builder_resource`
--

LOCK TABLES `library_web_builder_resource` WRITE;
/*!40000 ALTER TABLE `library_web_builder_resource` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_web_builder_resource` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_website_indexing`
--

DROP TABLE IF EXISTS `library_website_indexing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_website_indexing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `settingId` int(11) NOT NULL,
  `libraryId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settingId` (`settingId`,`libraryId`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=8192;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_website_indexing`
--

LOCK TABLES `library_website_indexing` WRITE;
/*!40000 ALTER TABLE `library_website_indexing` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_website_indexing` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `list_indexing_log`
--

DROP TABLE IF EXISTS `list_indexing_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `list_indexing_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `startTime` int(11) NOT NULL,
  `endTime` int(11) DEFAULT NULL,
  `lastUpdate` int(11) DEFAULT NULL,
  `notes` mediumtext COLLATE utf8mb4_general_ci,
  `numLists` int(11) DEFAULT '0',
  `numAdded` int(11) DEFAULT '0',
  `numDeleted` int(11) DEFAULT '0',
  `numUpdated` int(11) DEFAULT '0',
  `numSkipped` int(11) DEFAULT '0',
  `numErrors` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=292;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `list_indexing_log`
--

LOCK TABLES `list_indexing_log` WRITE;
/*!40000 ALTER TABLE `list_indexing_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `list_indexing_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `list_indexing_settings`
--

DROP TABLE IF EXISTS `list_indexing_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `list_indexing_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `runFullUpdate` tinyint(1) DEFAULT '1',
  `lastUpdateOfChangedLists` int(11) DEFAULT '0',
  `lastUpdateOfAllLists` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `list_indexing_settings`
--

LOCK TABLES `list_indexing_settings` WRITE;
/*!40000 ALTER TABLE `list_indexing_settings` DISABLE KEYS */;
INSERT INTO `list_indexing_settings` VALUES (1,1,0,0);
/*!40000 ALTER TABLE `list_indexing_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `list_widget_lists`
--

DROP TABLE IF EXISTS `list_widget_lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `list_widget_lists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collectionSpotlightId` int(11) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT '0',
  `displayFor` enum('all','loggedIn','notLoggedIn') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'all',
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `source` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `fullListLink` varchar(500) COLLATE utf8mb4_general_ci DEFAULT '',
  `defaultFilter` mediumtext COLLATE utf8mb4_general_ci,
  `defaultSort` enum('relevance','popularity','newest_to_oldest','oldest_to_newest','author','title','user_rating') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `searchTerm` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `sourceListId` mediumint(9) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ListWidgetId` (`collectionSpotlightId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='The lists that should appear within the widget';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `list_widget_lists`
--

LOCK TABLES `list_widget_lists` WRITE;
/*!40000 ALTER TABLE `list_widget_lists` DISABLE KEYS */;
/*!40000 ALTER TABLE `list_widget_lists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `list_widgets`
--

DROP TABLE IF EXISTS `list_widgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `list_widgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `description` mediumtext COLLATE utf8mb4_general_ci,
  `showTitleDescriptions` tinyint(4) DEFAULT '1',
  `onSelectCallback` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '',
  `customCss` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `listDisplayType` enum('tabs','dropdown') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'tabs',
  `autoRotate` tinyint(4) NOT NULL DEFAULT '0',
  `showMultipleTitles` tinyint(4) NOT NULL DEFAULT '1',
  `libraryId` int(11) NOT NULL DEFAULT '-1',
  `style` enum('vertical','horizontal','single','single-with-next','text-list') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'horizontal',
  `coverSize` enum('small','medium') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'small',
  `showRatings` tinyint(4) NOT NULL DEFAULT '0',
  `showTitle` tinyint(4) NOT NULL DEFAULT '1',
  `showAuthor` tinyint(4) NOT NULL DEFAULT '1',
  `showViewMoreLink` tinyint(4) NOT NULL DEFAULT '0',
  `viewMoreLinkMode` enum('covers','list') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'list',
  `showSpotlightTitle` tinyint(4) NOT NULL DEFAULT '1',
  `numTitlesToShow` int(11) NOT NULL DEFAULT '25',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='A widget that can be displayed within Pika or within other sites';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `list_widgets`
--

LOCK TABLES `list_widgets` WRITE;
/*!40000 ALTER TABLE `list_widgets` DISABLE KEYS */;
/*!40000 ALTER TABLE `list_widgets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lm_library_calendar_events`
--

DROP TABLE IF EXISTS `lm_library_calendar_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lm_library_calendar_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `settingsId` int(11) NOT NULL,
  `externalId` varchar(36) COLLATE utf8mb4_general_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `rawChecksum` bigint(20) DEFAULT NULL,
  `rawResponse` longtext COLLATE utf8mb4_general_ci,
  `deleted` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `settingsId` (`settingsId`,`externalId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lm_library_calendar_events`
--

LOCK TABLES `lm_library_calendar_events` WRITE;
/*!40000 ALTER TABLE `lm_library_calendar_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `lm_library_calendar_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lm_library_calendar_settings`
--

DROP TABLE IF EXISTS `lm_library_calendar_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lm_library_calendar_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `baseUrl` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `clientId` varchar(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `clientSecret` varchar(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `username` varchar(36) COLLATE utf8mb4_general_ci DEFAULT 'lc_feeds_staffadmin',
  `password` varchar(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lm_library_calendar_settings`
--

LOCK TABLES `lm_library_calendar_settings` WRITE;
/*!40000 ALTER TABLE `lm_library_calendar_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `lm_library_calendar_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `location`
--

DROP TABLE IF EXISTS `location`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `location` (
  `locationId` int(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique Id for the branch or location within vuFind',
  `code` varchar(75) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `displayName` varchar(60) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'The full name of the location for display to the user',
  `libraryId` int(11) NOT NULL COMMENT 'A link to the library which the location belongs to',
  `validHoldPickupBranch` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Determines if the location can be used as a pickup location if it is not the patrons home location or the location they are in.',
  `nearbyLocation1` int(11) DEFAULT NULL COMMENT 'A secondary location which is nearby and could be used for pickup of materials.',
  `nearbyLocation2` int(11) DEFAULT NULL COMMENT 'A tertiary location which is nearby and could be used for pickup of materials.',
  `scope` smallint(6) DEFAULT '0',
  `useScope` tinyint(4) DEFAULT '0',
  `facetFile` varchar(15) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'default' COMMENT 'The name of the facet file which should be used while searching use default to not override the file',
  `showHoldButton` tinyint(4) DEFAULT '1',
  `isMainBranch` tinyint(1) DEFAULT '0',
  `showStandardReviews` tinyint(4) DEFAULT '1',
  `repeatSearchOption` enum('none','librarySystem','marmot','all') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'all' COMMENT 'Where to allow repeating search. Valid options are: none, librarySystem, marmot, all',
  `facetLabel` varchar(75) COLLATE utf8mb4_general_ci DEFAULT '',
  `repeatInProspector` tinyint(4) DEFAULT '0',
  `repeatInWorldCat` tinyint(4) DEFAULT '0',
  `systemsToRepeatIn` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '',
  `repeatInOverdrive` tinyint(4) NOT NULL DEFAULT '0',
  `homeLink` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'default',
  `ptypesToAllowRenewals` varchar(128) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '*',
  `automaticTimeoutLength` int(11) DEFAULT '90',
  `automaticTimeoutLengthLoggedOut` int(11) DEFAULT '450',
  `restrictSearchByLocation` tinyint(1) DEFAULT '0',
  `enableOverdriveCollection` tinyint(1) DEFAULT '1',
  `suppressHoldings` tinyint(1) DEFAULT '0',
  `additionalCss` longtext COLLATE utf8mb4_general_ci,
  `repeatInOnlineCollection` int(11) DEFAULT '1',
  `econtentLocationsToInclude` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `showInLocationsAndHoursList` int(11) DEFAULT '1',
  `showShareOnExternalSites` int(11) DEFAULT '1',
  `showEmailThis` int(11) DEFAULT '1',
  `showFavorites` int(11) DEFAULT '1',
  `showComments` int(11) DEFAULT '1',
  `showGoodReadsReviews` int(11) DEFAULT '1',
  `showStaffView` int(11) DEFAULT '1',
  `address` longtext COLLATE utf8mb4_general_ci,
  `phone` varchar(25) COLLATE utf8mb4_general_ci DEFAULT '',
  `showDisplayNameInHeader` tinyint(4) DEFAULT '0',
  `headerText` longtext COLLATE utf8mb4_general_ci,
  `availabilityToggleLabelSuperScope` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Entire Collection',
  `availabilityToggleLabelLocal` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '{display name}',
  `availabilityToggleLabelAvailable` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Available Now',
  `defaultBrowseMode` varchar(25) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `browseCategoryRatingsMode` varchar(25) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `subLocation` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `includeOverDriveAdult` tinyint(1) DEFAULT '1',
  `includeOverDriveTeen` tinyint(1) DEFAULT '1',
  `includeOverDriveKids` tinyint(1) DEFAULT '1',
  `publicListsToInclude` tinyint(1) DEFAULT '6',
  `includeAllLibraryBranchesInFacets` tinyint(4) DEFAULT '1',
  `additionalLocationsToShowAvailabilityFor` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `includeAllRecordsInShelvingFacets` tinyint(4) DEFAULT '0',
  `includeAllRecordsInDateAddedFacets` tinyint(4) DEFAULT '0',
  `availabilityToggleLabelAvailableOnline` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '',
  `baseAvailabilityToggleOnLocalHoldingsOnly` tinyint(1) DEFAULT '0',
  `includeOnlineMaterialsInAvailableToggle` tinyint(1) DEFAULT '1',
  `subdomain` varchar(25) COLLATE utf8mb4_general_ci DEFAULT '',
  `includeLibraryRecordsToInclude` tinyint(1) DEFAULT '0',
  `useLibraryCombinedResultsSettings` tinyint(1) DEFAULT '1',
  `enableCombinedResults` tinyint(1) DEFAULT '0',
  `combinedResultsLabel` varchar(255) COLLATE utf8mb4_general_ci DEFAULT 'Combined Results',
  `defaultToCombinedResults` tinyint(1) DEFAULT '0',
  `footerTemplate` varchar(40) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'default',
  `homePageWidgetId` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '',
  `theme` int(11) DEFAULT '1',
  `hooplaScopeId` int(11) DEFAULT '-1',
  `axis360ScopeId` int(11) DEFAULT '-1',
  `historicCode` varchar(20) COLLATE utf8mb4_general_ci DEFAULT '',
  `tty` varchar(25) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_general_ci,
  `createSearchInterface` tinyint(1) DEFAULT '1',
  `showInSelectInterface` tinyint(1) DEFAULT '0',
  `enableAppAccess` tinyint(1) DEFAULT '0',
  `latitude` varchar(75) COLLATE utf8mb4_general_ci DEFAULT '0',
  `longitude` varchar(75) COLLATE utf8mb4_general_ci DEFAULT '0',
  `unit` varchar(3) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `appReleaseChannel` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`locationId`),
  UNIQUE KEY `code` (`code`,`subLocation`),
  KEY `ValidHoldPickupBranch` (`validHoldPickupBranch`),
  KEY `libraryId` (`libraryId`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores information about the various locations that are part';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `location`
--

LOCK TABLES `location` WRITE;
/*!40000 ALTER TABLE `location` DISABLE KEYS */;
INSERT INTO `location` VALUES (1,'main','Main Library',2,1,-1,-1,0,0,'default',1,0,1,'marmot','',0,0,'',0,'','*',90,450,0,1,0,'',0,NULL,1,1,1,1,1,1,1,'','',0,'','Entire Collection','{display name}','Available Now','','','',1,1,1,0,1,'',0,0,'Available Online',0,0,'',1,1,0,'Combined Results',1,'default','',1,-1,-1,'',NULL,NULL,1,0,0,'0','0',NULL,0);
/*!40000 ALTER TABLE `location` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `location_cloud_library_scope`
--

DROP TABLE IF EXISTS `location_cloud_library_scope`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `location_cloud_library_scope` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `scopeId` int(11) NOT NULL,
  `locationId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `locationId` (`locationId`,`scopeId`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=3276;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `location_cloud_library_scope`
--

LOCK TABLES `location_cloud_library_scope` WRITE;
/*!40000 ALTER TABLE `location_cloud_library_scope` DISABLE KEYS */;
/*!40000 ALTER TABLE `location_cloud_library_scope` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `location_combined_results_section`
--

DROP TABLE IF EXISTS `location_combined_results_section`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `location_combined_results_section` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `locationId` int(11) NOT NULL,
  `displayName` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `source` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numberOfResultsToShow` int(11) NOT NULL DEFAULT '5',
  `weight` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `LocationIdIndex` (`locationId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `location_combined_results_section`
--

LOCK TABLES `location_combined_results_section` WRITE;
/*!40000 ALTER TABLE `location_combined_results_section` DISABLE KEYS */;
/*!40000 ALTER TABLE `location_combined_results_section` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `location_facet_setting`
--

DROP TABLE IF EXISTS `location_facet_setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `location_facet_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `locationId` int(11) NOT NULL,
  `displayName` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `facetName` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `weight` int(11) NOT NULL DEFAULT '0',
  `numEntriesToShowByDefault` int(11) NOT NULL DEFAULT '5',
  `showAsDropDown` tinyint(4) NOT NULL DEFAULT '0',
  `sortMode` enum('alphabetically','num_results') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'num_results',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='A widget that can be displayed within VuFind or within other';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `location_facet_setting`
--

LOCK TABLES `location_facet_setting` WRITE;
/*!40000 ALTER TABLE `location_facet_setting` DISABLE KEYS */;
/*!40000 ALTER TABLE `location_facet_setting` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `location_hours`
--

DROP TABLE IF EXISTS `location_hours`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `location_hours` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of hours entry',
  `locationId` int(11) NOT NULL COMMENT 'The location id',
  `day` int(11) NOT NULL COMMENT 'Day of the week 0 to 7 (Sun to Monday)',
  `closed` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether or not the library is closed on this day',
  `open` varchar(10) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Open hour (24hr format) HH:MM',
  `close` varchar(10) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Close hour (24hr format) HH:MM',
  `notes` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `location` (`locationId`,`day`,`open`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `location_hours`
--

LOCK TABLES `location_hours` WRITE;
/*!40000 ALTER TABLE `location_hours` DISABLE KEYS */;
/*!40000 ALTER TABLE `location_hours` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `location_more_details`
--

DROP TABLE IF EXISTS `location_more_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `location_more_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `locationId` int(11) NOT NULL DEFAULT '-1',
  `weight` int(11) NOT NULL DEFAULT '0',
  `source` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `collapseByDefault` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `locationId` (`locationId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `location_more_details`
--

LOCK TABLES `location_more_details` WRITE;
/*!40000 ALTER TABLE `location_more_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `location_more_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `location_open_archives_collection`
--

DROP TABLE IF EXISTS `location_open_archives_collection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `location_open_archives_collection` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collectionId` int(11) NOT NULL,
  `locationId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `collectionId` (`collectionId`,`locationId`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=2730;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `location_open_archives_collection`
--

LOCK TABLES `location_open_archives_collection` WRITE;
/*!40000 ALTER TABLE `location_open_archives_collection` DISABLE KEYS */;
/*!40000 ALTER TABLE `location_open_archives_collection` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `location_records_owned`
--

DROP TABLE IF EXISTS `location_records_owned`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `location_records_owned` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `locationId` int(11) NOT NULL,
  `indexingProfileId` int(11) NOT NULL,
  `location` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `subLocation` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `locationsToExclude` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `subLocationsToExclude` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `locationId` (`locationId`),
  KEY `indexingProfileId` (`indexingProfileId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `location_records_owned`
--

LOCK TABLES `location_records_owned` WRITE;
/*!40000 ALTER TABLE `location_records_owned` DISABLE KEYS */;
/*!40000 ALTER TABLE `location_records_owned` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `location_records_to_include`
--

DROP TABLE IF EXISTS `location_records_to_include`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `location_records_to_include` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `locationId` int(11) NOT NULL,
  `indexingProfileId` int(11) NOT NULL,
  `location` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `subLocation` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `includeHoldableOnly` tinyint(4) NOT NULL DEFAULT '1',
  `includeItemsOnOrder` tinyint(1) NOT NULL DEFAULT '0',
  `includeEContent` tinyint(1) NOT NULL DEFAULT '0',
  `weight` int(11) NOT NULL,
  `iType` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `audience` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `format` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `marcTagToMatch` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `marcValueToMatch` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `includeExcludeMatches` tinyint(4) DEFAULT '1',
  `urlToMatch` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `urlReplacement` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `locationsToExclude` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `subLocationsToExclude` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `locationId` (`locationId`,`indexingProfileId`),
  KEY `indexingProfileId` (`indexingProfileId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `location_records_to_include`
--

LOCK TABLES `location_records_to_include` WRITE;
/*!40000 ALTER TABLE `location_records_to_include` DISABLE KEYS */;
/*!40000 ALTER TABLE `location_records_to_include` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `location_search_source`
--

DROP TABLE IF EXISTS `location_search_source`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `location_search_source` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `locationId` int(11) NOT NULL DEFAULT '-1',
  `label` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `weight` int(11) NOT NULL DEFAULT '0',
  `searchWhat` enum('catalog','genealogy','overdrive','worldcat','prospector','goldrush','title_browse','author_browse','subject_browse','tags') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `defaultFilter` mediumtext COLLATE utf8mb4_general_ci,
  `defaultSort` enum('relevance','popularity','newest_to_oldest','oldest_to_newest','author','title','user_rating') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `catalogScoping` enum('unscoped','library','location') COLLATE utf8mb4_general_ci DEFAULT 'unscoped',
  PRIMARY KEY (`id`),
  KEY `locationId` (`locationId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `location_search_source`
--

LOCK TABLES `location_search_source` WRITE;
/*!40000 ALTER TABLE `location_search_source` DISABLE KEYS */;
/*!40000 ALTER TABLE `location_search_source` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `location_sideload_scopes`
--

DROP TABLE IF EXISTS `location_sideload_scopes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `location_sideload_scopes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `locationId` int(11) NOT NULL,
  `sideLoadScopeId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `locationId` (`locationId`,`sideLoadScopeId`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=2340;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `location_sideload_scopes`
--

LOCK TABLES `location_sideload_scopes` WRITE;
/*!40000 ALTER TABLE `location_sideload_scopes` DISABLE KEYS */;
/*!40000 ALTER TABLE `location_sideload_scopes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `location_website_indexing`
--

DROP TABLE IF EXISTS `location_website_indexing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `location_website_indexing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `settingId` int(11) NOT NULL,
  `locationId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settingId` (`settingId`,`locationId`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=1638;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `location_website_indexing`
--

LOCK TABLES `location_website_indexing` WRITE;
/*!40000 ALTER TABLE `location_website_indexing` DISABLE KEYS */;
/*!40000 ALTER TABLE `location_website_indexing` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `marriage`
--

DROP TABLE IF EXISTS `marriage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `marriage` (
  `marriageId` int(11) NOT NULL AUTO_INCREMENT,
  `personId` int(11) NOT NULL COMMENT 'A link to one person in the marriage',
  `spouseName` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'The name of the other person in the marriage if they aren''t in the database',
  `spouseId` int(11) DEFAULT NULL COMMENT 'A link to the second person in the marriage if the person is in the database',
  `marriageDate` date DEFAULT NULL COMMENT 'The date of the marriage if known.',
  `comments` longtext COLLATE utf8mb4_general_ci,
  `marriageDateDay` int(11) DEFAULT NULL COMMENT 'The day of the month the marriage occurred empty or null if not known',
  `marriageDateMonth` int(11) DEFAULT NULL COMMENT 'The month the marriage occurred, null or blank if not known',
  `marriageDateYear` int(11) DEFAULT NULL COMMENT 'The year the marriage occurred, null or blank if not known',
  PRIMARY KEY (`marriageId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=2340 COMMENT='Information about a marriage between two people';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marriage`
--

LOCK TABLES `marriage` WRITE;
/*!40000 ALTER TABLE `marriage` DISABLE KEYS */;
/*!40000 ALTER TABLE `marriage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materials_request`
--

DROP TABLE IF EXISTS `materials_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `materials_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libraryId` int(10) unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `author` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `format` varchar(25) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `formatId` int(10) unsigned DEFAULT NULL,
  `ageLevel` varchar(25) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `isbn` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `oclcNumber` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `publisher` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `publicationYear` varchar(4) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `articleInfo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `abridged` tinyint(4) DEFAULT NULL,
  `about` mediumtext COLLATE utf8mb4_general_ci,
  `comments` mediumtext COLLATE utf8mb4_general_ci,
  `status` int(11) DEFAULT NULL,
  `dateCreated` int(11) DEFAULT NULL,
  `createdBy` int(11) DEFAULT NULL,
  `dateUpdated` int(11) DEFAULT NULL,
  `emailSent` tinyint(4) NOT NULL DEFAULT '0',
  `holdsCreated` tinyint(4) NOT NULL DEFAULT '0',
  `email` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `season` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `magazineTitle` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `upc` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `issn` varchar(8) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bookType` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `subFormat` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `magazineDate` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `magazineVolume` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `magazinePageNumbers` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `placeHoldWhenAvailable` tinyint(4) DEFAULT NULL,
  `holdPickupLocation` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bookmobileStop` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `illItem` tinyint(4) DEFAULT NULL,
  `magazineNumber` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assignedTo` int(11) DEFAULT NULL,
  `staffComments` mediumtext COLLATE utf8mb4_general_ci,
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materials_request`
--

LOCK TABLES `materials_request` WRITE;
/*!40000 ALTER TABLE `materials_request` DISABLE KEYS */;
/*!40000 ALTER TABLE `materials_request` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materials_request_fields_to_display`
--

DROP TABLE IF EXISTS `materials_request_fields_to_display`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `materials_request_fields_to_display` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `libraryId` int(11) NOT NULL,
  `columnNameToDisplay` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `labelForColumnToDisplay` varchar(45) COLLATE utf8mb4_general_ci NOT NULL,
  `weight` smallint(2) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `columnNameToDisplay` (`columnNameToDisplay`,`libraryId`),
  KEY `libraryId` (`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materials_request_fields_to_display`
--

LOCK TABLES `materials_request_fields_to_display` WRITE;
/*!40000 ALTER TABLE `materials_request_fields_to_display` DISABLE KEYS */;
/*!40000 ALTER TABLE `materials_request_fields_to_display` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materials_request_form_fields`
--

DROP TABLE IF EXISTS `materials_request_form_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `materials_request_form_fields` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `libraryId` int(10) unsigned NOT NULL,
  `formCategory` varchar(55) COLLATE utf8mb4_general_ci NOT NULL,
  `fieldLabel` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `fieldType` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `weight` smallint(2) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  KEY `libraryId` (`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materials_request_form_fields`
--

LOCK TABLES `materials_request_form_fields` WRITE;
/*!40000 ALTER TABLE `materials_request_form_fields` DISABLE KEYS */;
/*!40000 ALTER TABLE `materials_request_form_fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materials_request_formats`
--

DROP TABLE IF EXISTS `materials_request_formats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `materials_request_formats` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `libraryId` int(10) unsigned NOT NULL,
  `format` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `formatLabel` varchar(60) COLLATE utf8mb4_general_ci NOT NULL,
  `authorLabel` varchar(45) COLLATE utf8mb4_general_ci NOT NULL,
  `weight` smallint(2) unsigned NOT NULL DEFAULT '0',
  `specialFields` set('Abridged/Unabridged','Article Field','Eaudio format','Ebook format','Season') COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `libraryId` (`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materials_request_formats`
--

LOCK TABLES `materials_request_formats` WRITE;
/*!40000 ALTER TABLE `materials_request_formats` DISABLE KEYS */;
/*!40000 ALTER TABLE `materials_request_formats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materials_request_status`
--

DROP TABLE IF EXISTS `materials_request_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `materials_request_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `isDefault` tinyint(4) DEFAULT '0',
  `sendEmailToPatron` tinyint(4) DEFAULT NULL,
  `emailTemplate` mediumtext COLLATE utf8mb4_general_ci,
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
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materials_request_status`
--

LOCK TABLES `materials_request_status` WRITE;
/*!40000 ALTER TABLE `materials_request_status` DISABLE KEYS */;
INSERT INTO `materials_request_status` VALUES (1,'Request Pending',1,0,'',1,0,-1),(2,'Already owned/On order',0,1,'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The Library already owns this item or it is already on order. Please access our catalog to place this item on hold.  Please check our online catalog periodically to put a hold for this item.',0,0,-1),(3,'Item purchased',0,1,'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. Outcome: The library is purchasing the item you requested. Please check our online catalog periodically to put yourself on hold for this item. We anticipate that this item will be available soon for you to place a hold.',0,0,-1),(4,'Referred to Collection Development - Adult',0,0,'',1,0,-1),(5,'Referred to Collection Development - J/YA',0,0,'',1,0,-1),(6,'Referred to Collection Development - AV',0,0,'',1,0,-1),(7,'ILL Under Review',0,0,'',1,0,-1),(8,'Request Referred to ILL',0,1,'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The library\'s Interlibrary loan department is reviewing your request. We will attempt to borrow this item from another system. This process generally takes about 2 - 6 weeks.',1,0,-1),(9,'Request Filled by ILL',0,1,'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. Our Interlibrary Loan Department is set to borrow this item from another library.',0,0,-1),(10,'Ineligible ILL',0,1,'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. Your library account is not eligible for interlibrary loan at this time.',0,0,-1),(11,'Not enough info - please contact Collection Development to clarify',0,1,'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We need more specific information in order to locate the exact item you need. Please re-submit your request with more details.',1,0,-1),(12,'Unable to acquire the item - out of print',0,1,'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is out of print.',0,0,-1),(13,'Unable to acquire the item - not available in the US',0,1,'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is not available in the US.',0,0,-1),(14,'Unable to acquire the item - not available from vendor',0,1,'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is not available from a preferred vendor.',0,0,-1),(15,'Unable to acquire the item - not published',0,1,'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The item you requested has not yet been published. Please check our catalog when the publication date draws near.',0,0,-1),(16,'Unable to acquire the item - price',0,1,'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item does not fit our collection guidelines.',0,0,-1),(17,'Unable to acquire the item - publication date',0,1,'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item does not fit our collection guidelines.',0,0,-1),(18,'Unavailable',0,1,'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The item you requested cannot be purchased at this time from any of our regular suppliers and is not available from any of our lending libraries.',0,0,-1),(19,'Cancelled by Patron',0,0,'',0,1,-1),(20,'Cancelled - Duplicate Request',0,0,'',0,0,-1);
/*!40000 ALTER TABLE `materials_request_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `merged_grouped_works`
--

DROP TABLE IF EXISTS `merged_grouped_works`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `merged_grouped_works` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `sourceGroupedWorkId` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `destinationGroupedWorkId` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `notes` varchar(250) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `sourceGroupedWorkId` (`sourceGroupedWorkId`,`destinationGroupedWorkId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `merged_grouped_works`
--

LOCK TABLES `merged_grouped_works` WRITE;
/*!40000 ALTER TABLE `merged_grouped_works` DISABLE KEYS */;
/*!40000 ALTER TABLE `merged_grouped_works` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `millennium_cache`
--

DROP TABLE IF EXISTS `millennium_cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `millennium_cache` (
  `recordId` varchar(20) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'The recordId being checked',
  `scope` int(16) NOT NULL COMMENT 'The scope that was loaded',
  `holdingsInfo` longtext COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Raw HTML returned from Millennium for holdings',
  `framesetInfo` longtext COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Raw HTML returned from Millennium on the frameset page',
  `cacheDate` int(16) NOT NULL COMMENT 'When the entry was recorded in the cache',
  PRIMARY KEY (`recordId`,`scope`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Caches information from Millennium so we do not have to cont';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `millennium_cache`
--

LOCK TABLES `millennium_cache` WRITE;
/*!40000 ALTER TABLE `millennium_cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `millennium_cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `modules`
--

DROP TABLE IF EXISTS `modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `enabled` tinyint(1) DEFAULT '0',
  `indexName` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '',
  `backgroundProcess` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '',
  `logClassPath` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `logClassName` varchar(35) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `settingsClassPath` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `settingsClassName` varchar(35) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `enabled` (`enabled`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=819;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modules`
--

LOCK TABLES `modules` WRITE;
/*!40000 ALTER TABLE `modules` DISABLE KEYS */;
INSERT INTO `modules` VALUES (21,'Genealogy',0,'genealogy','',NULL,NULL,NULL,NULL),(22,'Koha',0,'grouped_works','koha_export','/sys/ILS/IlsExtractLogEntry.php','IlsExtractLogEntry','/sys/Indexing/IndexingProfile.php','IndexingProfile'),(23,'CARL.X',0,'grouped_works','carlx_export','/sys/ILS/IlsExtractLogEntry.php','IlsExtractLogEntry','/sys/Indexing/IndexingProfile.php','IndexingProfile'),(24,'Sierra',0,'grouped_works','sierra_export_api','/sys/ILS/IlsExtractLogEntry.php','IlsExtractLogEntry','/sys/Indexing/IndexingProfile.php','IndexingProfile'),(25,'Horizon',0,'grouped_works','horizon_export','/sys/ILS/IlsExtractLogEntry.php','IlsExtractLogEntry','/sys/Indexing/IndexingProfile.php','IndexingProfile'),(26,'Symphony',0,'grouped_works','symphony_export','/sys/ILS/IlsExtractLogEntry.php','IlsExtractLogEntry','/sys/Indexing/IndexingProfile.php','IndexingProfile'),(27,'Side Loads',0,'grouped_works','sideload_processing','/sys/Indexing/SideLoadLogEntry.php','SideLoadLogEntry',NULL,NULL),(28,'User Lists',1,'lists','user_list_indexer',NULL,NULL,NULL,NULL),(29,'Polaris',0,'grouped_works','polaris_export','/sys/ILS/IlsExtractLogEntry.php','IlsExtractLogEntry','/sys/Indexing/IndexingProfile.php','IndexingProfile'),(30,'OverDrive',0,'grouped_works','overdrive_extract','/sys/OverDrive/OverDriveExtractLogEntry.php','OverDriveExtractLogEntry','/sys/OverDrive/OverDriveSetting.php','OverDriveSetting'),(31,'EBSCO EDS',0,'','',NULL,NULL,NULL,NULL),(32,'EBSCOhost',0,'','',NULL,NULL,NULL,NULL),(33,'Axis 360',0,'grouped_works','axis_360_export','/sys/Axis360/Axis360LogEntry.php','Axis360LogEntry','/sys/Axis360/Axis360Setting.php','Axis360Setting'),(34,'Hoopla',0,'grouped_works','hoopla_export','/sys/Hoopla/HooplaExportLogEntry.php','HooplaExportLogEntry','/sys/Hoopla/HooplaSetting.php','HooplaSetting'),(35,'Open Archives',0,'open_archives','','/sys/OpenArchives/OpenArchivesExportLogEntry.php','OpenArchivesExportLogEntry','/sys/OpenArchives/OpenArchivesCollection.php','OpenArchivesCollection'),(36,'Cloud Library',0,'grouped_works','cloud_library_export','/sys/CloudLibrary/CloudLibraryExportLogEntry.php','CloudLibraryExportLogEntry','/sys/CloudLibrary/CloudLibrarySetting.php','CloudLibrarySetting'),(37,'Web Indexer',0,'website_pages','web_indexer','/sys/WebsiteIndexing/WebsiteIndexLogEntry.php','WebsiteIndexLogEntry','/sys/WebsiteIndexing/WebsiteIndexSetting.php','WebsiteIndexSetting'),(38,'Web Builder',0,'web_builder','web_indexer','/sys/WebsiteIndexing/WebsiteIndexLogEntry.php','WebsiteIndexLogEntry',NULL,NULL),(39,'Events',0,'events','events_indexer','/sys/Events/EventsIndexingLogEntry.php','EventsIndexingLogEntry','/sys/Events/LMLibraryCalendarSetting.php','LMLibraryCalendarSetting');
/*!40000 ALTER TABLE `modules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `non_holdable_locations`
--

DROP TABLE IF EXISTS `non_holdable_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `non_holdable_locations` (
  `locationId` int(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique id for the non holdable location',
  `millenniumCode` varchar(5) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'The internal 5 letter code within Millennium',
  `holdingDisplay` varchar(30) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'The text displayed in the holdings list within Millennium',
  `availableAtCircDesk` tinyint(4) NOT NULL COMMENT 'The item is available if the patron visits the circulation desk.',
  PRIMARY KEY (`locationId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `non_holdable_locations`
--

LOCK TABLES `non_holdable_locations` WRITE;
/*!40000 ALTER TABLE `non_holdable_locations` DISABLE KEYS */;
/*!40000 ALTER TABLE `non_holdable_locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nongrouped_records`
--

DROP TABLE IF EXISTS `nongrouped_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nongrouped_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `recordId` varchar(36) COLLATE utf8mb4_general_ci NOT NULL,
  `notes` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `source` (`source`,`recordId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nongrouped_records`
--

LOCK TABLES `nongrouped_records` WRITE;
/*!40000 ALTER TABLE `nongrouped_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `nongrouped_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `novelist_data`
--

DROP TABLE IF EXISTS `novelist_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `novelist_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupedRecordPermanentId` varchar(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lastUpdate` int(11) DEFAULT NULL,
  `hasNovelistData` tinyint(1) DEFAULT NULL,
  `groupedRecordHasISBN` tinyint(1) DEFAULT NULL,
  `primaryISBN` varchar(13) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `seriesTitle` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `seriesNote` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `volume` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jsonResponse` mediumblob,
  PRIMARY KEY (`id`),
  KEY `groupedRecordPermanentId` (`groupedRecordPermanentId`),
  KEY `primaryISBN` (`primaryISBN`),
  KEY `series` (`seriesTitle`,`volume`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `novelist_data`
--

LOCK TABLES `novelist_data` WRITE;
/*!40000 ALTER TABLE `novelist_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `novelist_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `novelist_settings`
--

DROP TABLE IF EXISTS `novelist_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `novelist_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `profile` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `pwd` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=16384;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `novelist_settings`
--

LOCK TABLES `novelist_settings` WRITE;
/*!40000 ALTER TABLE `novelist_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `novelist_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nyt_api_settings`
--

DROP TABLE IF EXISTS `nyt_api_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nyt_api_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booksApiKey` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=16384;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nyt_api_settings`
--

LOCK TABLES `nyt_api_settings` WRITE;
/*!40000 ALTER TABLE `nyt_api_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `nyt_api_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nyt_update_log`
--

DROP TABLE IF EXISTS `nyt_update_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nyt_update_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `startTime` int(11) NOT NULL,
  `endTime` int(11) DEFAULT NULL,
  `lastUpdate` int(11) DEFAULT NULL,
  `numErrors` int(11) NOT NULL DEFAULT '0',
  `numLists` int(11) NOT NULL DEFAULT '0',
  `numAdded` int(11) NOT NULL DEFAULT '0',
  `numUpdated` int(11) NOT NULL DEFAULT '0',
  `notes` mediumtext COLLATE utf8mb4_general_ci,
  `numSkipped` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=1024;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nyt_update_log`
--

LOCK TABLES `nyt_update_log` WRITE;
/*!40000 ALTER TABLE `nyt_update_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `nyt_update_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `obituary`
--

DROP TABLE IF EXISTS `obituary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `obituary` (
  `obituaryId` int(11) NOT NULL AUTO_INCREMENT,
  `personId` int(11) NOT NULL COMMENT 'The person this obituary is for',
  `source` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date` date DEFAULT NULL,
  `sourcePage` varchar(25) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contents` longtext COLLATE utf8mb4_general_ci,
  `picture` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dateDay` int(11) DEFAULT NULL,
  `dateMonth` int(11) DEFAULT NULL,
  `dateYear` int(11) DEFAULT NULL,
  PRIMARY KEY (`obituaryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Information about an obituary for a person';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `obituary`
--

LOCK TABLES `obituary` WRITE;
/*!40000 ALTER TABLE `obituary` DISABLE KEYS */;
/*!40000 ALTER TABLE `obituary` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `object_history`
--

DROP TABLE IF EXISTS `object_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `object_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `objectType` varchar(75) COLLATE utf8mb4_general_ci NOT NULL,
  `objectId` int(11) NOT NULL,
  `propertyName` varchar(75) COLLATE utf8mb4_general_ci NOT NULL,
  `oldValue` text COLLATE utf8mb4_general_ci,
  `newValue` text COLLATE utf8mb4_general_ci,
  `changedBy` int(11) NOT NULL,
  `changeDate` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `changedBy` (`changedBy`),
  KEY `objectType` (`objectType`,`objectId`)
) ENGINE=InnoDB AUTO_INCREMENT=2610 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=97;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `object_history`
--

LOCK TABLES `object_history` WRITE;
/*!40000 ALTER TABLE `object_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `object_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `offline_circulation`
--

DROP TABLE IF EXISTS `offline_circulation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `offline_circulation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timeEntered` int(11) NOT NULL,
  `timeProcessed` int(11) DEFAULT NULL,
  `itemBarcode` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `patronBarcode` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `patronId` int(11) DEFAULT NULL,
  `login` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `loginPassword` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `initials` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `initialsPassword` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type` enum('Check In','Check Out') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('Not Processed','Processing Succeeded','Processing Failed') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notes` varchar(512) COLLATE utf8mb4_general_ci DEFAULT NULL,
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `offline_circulation`
--

LOCK TABLES `offline_circulation` WRITE;
/*!40000 ALTER TABLE `offline_circulation` DISABLE KEYS */;
/*!40000 ALTER TABLE `offline_circulation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `offline_hold`
--

DROP TABLE IF EXISTS `offline_hold`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `offline_hold` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timeEntered` int(11) NOT NULL,
  `timeProcessed` int(11) DEFAULT NULL,
  `bibId` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `patronId` int(11) DEFAULT NULL,
  `patronBarcode` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('Not Processed','Hold Succeeded','Hold Failed') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notes` varchar(512) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `patronName` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `itemId` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `timeEntered` (`timeEntered`),
  KEY `timeProcessed` (`timeProcessed`),
  KEY `patronBarcode` (`patronBarcode`),
  KEY `patronId` (`patronId`),
  KEY `bibId` (`bibId`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `offline_hold`
--

LOCK TABLES `offline_hold` WRITE;
/*!40000 ALTER TABLE `offline_hold` DISABLE KEYS */;
/*!40000 ALTER TABLE `offline_hold` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `omdb_settings`
--

DROP TABLE IF EXISTS `omdb_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `omdb_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `apiKey` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=16384;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `omdb_settings`
--

LOCK TABLES `omdb_settings` WRITE;
/*!40000 ALTER TABLE `omdb_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `omdb_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `open_archives_collection`
--

DROP TABLE IF EXISTS `open_archives_collection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `open_archives_collection` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `baseUrl` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `setName` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `fetchFrequency` enum('hourly','daily','weekly','monthly','yearly','once') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lastFetched` int(11) DEFAULT NULL,
  `subjectFilters` longtext COLLATE utf8mb4_general_ci,
  `subjects` longtext COLLATE utf8mb4_general_ci,
  `loadOneMonthAtATime` tinyint(1) DEFAULT '1',
  `imageRegex` varchar(100) COLLATE utf8mb4_general_ci DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `open_archives_collection`
--

LOCK TABLES `open_archives_collection` WRITE;
/*!40000 ALTER TABLE `open_archives_collection` DISABLE KEYS */;
/*!40000 ALTER TABLE `open_archives_collection` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `open_archives_export_log`
--

DROP TABLE IF EXISTS `open_archives_export_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `open_archives_export_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `notes` mediumtext COLLATE utf8mb4_general_ci COMMENT 'Additional information about the run',
  `collectionName` longtext COLLATE utf8mb4_general_ci,
  `numRecords` int(11) DEFAULT '0',
  `numErrors` int(11) DEFAULT '0',
  `numAdded` int(11) DEFAULT '0',
  `numDeleted` int(11) DEFAULT '0',
  `numUpdated` int(11) DEFAULT '0',
  `numSkipped` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=3276;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `open_archives_export_log`
--

LOCK TABLES `open_archives_export_log` WRITE;
/*!40000 ALTER TABLE `open_archives_export_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `open_archives_export_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `open_archives_record`
--

DROP TABLE IF EXISTS `open_archives_record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `open_archives_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sourceCollection` int(11) NOT NULL,
  `permanentUrl` varchar(512) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sourceCollection` (`sourceCollection`,`permanentUrl`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=585;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `open_archives_record`
--

LOCK TABLES `open_archives_record` WRITE;
/*!40000 ALTER TABLE `open_archives_record` DISABLE KEYS */;
/*!40000 ALTER TABLE `open_archives_record` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `open_archives_record_usage`
--

DROP TABLE IF EXISTS `open_archives_record_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `open_archives_record_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `openArchivesRecordId` int(11) DEFAULT NULL,
  `year` int(4) NOT NULL,
  `timesViewedInSearch` int(11) NOT NULL,
  `timesUsed` int(11) NOT NULL,
  `month` int(2) NOT NULL DEFAULT '4',
  `instance` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`openArchivesRecordId`,`year`,`month`),
  UNIQUE KEY `instance_2` (`instance`,`openArchivesRecordId`,`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=819;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `open_archives_record_usage`
--

LOCK TABLES `open_archives_record_usage` WRITE;
/*!40000 ALTER TABLE `open_archives_record_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `open_archives_record_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overdrive_account_cache`
--

DROP TABLE IF EXISTS `overdrive_account_cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `overdrive_account_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) DEFAULT NULL,
  `holdPage` longtext COLLATE utf8mb4_general_ci,
  `holdPageLastLoaded` int(11) NOT NULL DEFAULT '0',
  `bookshelfPage` longtext COLLATE utf8mb4_general_ci,
  `bookshelfPageLastLoaded` int(11) NOT NULL DEFAULT '0',
  `wishlistPage` longtext COLLATE utf8mb4_general_ci,
  `wishlistPageLastLoaded` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='A cache to store information about a user''s account within OverDrive.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_account_cache`
--

LOCK TABLES `overdrive_account_cache` WRITE;
/*!40000 ALTER TABLE `overdrive_account_cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_account_cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overdrive_api_product_availability`
--

DROP TABLE IF EXISTS `overdrive_api_product_availability`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `overdrive_api_product_availability` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `productId` int(11) DEFAULT NULL,
  `libraryId` int(11) DEFAULT NULL,
  `available` tinyint(1) DEFAULT NULL,
  `copiesOwned` int(11) DEFAULT NULL,
  `copiesAvailable` int(11) DEFAULT NULL,
  `numberOfHolds` int(11) DEFAULT NULL,
  `availabilityType` varchar(35) COLLATE utf8mb4_general_ci DEFAULT 'Normal',
  `shared` tinyint(1) DEFAULT '0',
  `settingId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `productId` (`productId`,`settingId`,`libraryId`),
  KEY `libraryId` (`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_api_product_availability`
--

LOCK TABLES `overdrive_api_product_availability` WRITE;
/*!40000 ALTER TABLE `overdrive_api_product_availability` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_api_product_availability` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overdrive_api_product_formats`
--

DROP TABLE IF EXISTS `overdrive_api_product_formats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `overdrive_api_product_formats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `productId` int(11) DEFAULT NULL,
  `textId` varchar(25) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numericId` int(11) DEFAULT NULL,
  `name` varchar(512) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fileName` varchar(215) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fileSize` int(11) DEFAULT NULL,
  `partCount` smallint(6) DEFAULT NULL,
  `sampleSource_1` varchar(215) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sampleUrl_1` varchar(215) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sampleSource_2` varchar(215) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sampleUrl_2` varchar(215) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `productId_2` (`productId`,`textId`),
  KEY `productId` (`productId`),
  KEY `numericId` (`numericId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_api_product_formats`
--

LOCK TABLES `overdrive_api_product_formats` WRITE;
/*!40000 ALTER TABLE `overdrive_api_product_formats` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_api_product_formats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overdrive_api_product_identifiers`
--

DROP TABLE IF EXISTS `overdrive_api_product_identifiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `overdrive_api_product_identifiers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `productId` int(11) DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `value` varchar(75) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `productId` (`productId`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_api_product_identifiers`
--

LOCK TABLES `overdrive_api_product_identifiers` WRITE;
/*!40000 ALTER TABLE `overdrive_api_product_identifiers` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_api_product_identifiers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overdrive_api_product_metadata`
--

DROP TABLE IF EXISTS `overdrive_api_product_metadata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `overdrive_api_product_metadata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `productId` int(11) DEFAULT NULL,
  `checksum` bigint(20) DEFAULT NULL,
  `sortTitle` varchar(512) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `publisher` varchar(215) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `publishDate` int(11) DEFAULT NULL,
  `isPublicDomain` tinyint(1) DEFAULT NULL,
  `isPublicPerformanceAllowed` tinyint(1) DEFAULT NULL,
  `shortDescription` mediumtext COLLATE utf8mb4_general_ci,
  `fullDescription` mediumtext COLLATE utf8mb4_general_ci,
  `starRating` float DEFAULT NULL,
  `popularity` int(11) DEFAULT NULL,
  `rawData` mediumblob,
  `thumbnail` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cover` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `isOwnedByCollections` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `productId` (`productId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_api_product_metadata`
--

LOCK TABLES `overdrive_api_product_metadata` WRITE;
/*!40000 ALTER TABLE `overdrive_api_product_metadata` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_api_product_metadata` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overdrive_api_products`
--

DROP TABLE IF EXISTS `overdrive_api_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `overdrive_api_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `overdriveId` varchar(36) COLLATE utf8mb4_general_ci NOT NULL,
  `mediaType` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `title` varchar(512) COLLATE utf8mb4_general_ci NOT NULL,
  `series` varchar(215) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `primaryCreatorRole` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `primaryCreatorName` varchar(215) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cover` varchar(215) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dateAdded` int(11) DEFAULT NULL,
  `dateUpdated` int(11) DEFAULT NULL,
  `lastMetadataCheck` int(11) DEFAULT NULL,
  `lastMetadataChange` int(11) DEFAULT NULL,
  `lastAvailabilityCheck` int(11) DEFAULT NULL,
  `lastAvailabilityChange` int(11) DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT '0',
  `dateDeleted` int(11) DEFAULT NULL,
  `subtitle` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `crossRefId` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `overdriveId` (`overdriveId`),
  KEY `dateUpdated` (`dateUpdated`),
  KEY `lastMetadataCheck` (`lastMetadataCheck`),
  KEY `lastAvailabilityCheck` (`lastAvailabilityCheck`),
  KEY `deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_api_products`
--

LOCK TABLES `overdrive_api_products` WRITE;
/*!40000 ALTER TABLE `overdrive_api_products` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_api_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overdrive_extract_log`
--

DROP TABLE IF EXISTS `overdrive_extract_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  `notes` mediumtext COLLATE utf8mb4_general_ci,
  `settingId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_extract_log`
--

LOCK TABLES `overdrive_extract_log` WRITE;
/*!40000 ALTER TABLE `overdrive_extract_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_extract_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overdrive_record_usage`
--

DROP TABLE IF EXISTS `overdrive_record_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `overdrive_record_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `overdriveId` varchar(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `timesHeld` int(11) NOT NULL,
  `timesCheckedOut` int(11) NOT NULL,
  `instance` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`overdriveId`,`year`,`month`),
  UNIQUE KEY `instance_2` (`instance`,`overdriveId`,`year`,`month`),
  KEY `year` (`year`,`month`),
  KEY `year_2` (`year`,`month`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=1489;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_record_usage`
--

LOCK TABLES `overdrive_record_usage` WRITE;
/*!40000 ALTER TABLE `overdrive_record_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_record_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overdrive_scopes`
--

DROP TABLE IF EXISTS `overdrive_scopes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `overdrive_scopes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `includeAdult` tinyint(4) DEFAULT '1',
  `includeTeen` tinyint(4) DEFAULT '1',
  `includeKids` tinyint(4) DEFAULT '1',
  `authenticationILSName` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `requirePin` tinyint(1) DEFAULT '0',
  `overdriveAdvantageName` varchar(128) COLLATE utf8mb4_general_ci DEFAULT '',
  `overdriveAdvantageProductsKey` varchar(20) COLLATE utf8mb4_general_ci DEFAULT '',
  `settingId` int(11) DEFAULT NULL,
  `clientSecret` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `clientKey` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `circulationEnabled` tinyint(4) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=8192;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_scopes`
--

LOCK TABLES `overdrive_scopes` WRITE;
/*!40000 ALTER TABLE `overdrive_scopes` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_scopes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overdrive_settings`
--

DROP TABLE IF EXISTS `overdrive_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `overdrive_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `patronApiUrl` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `clientSecret` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `clientKey` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `accountId` int(11) DEFAULT '0',
  `websiteId` int(11) DEFAULT '0',
  `productsKey` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '0',
  `runFullUpdate` tinyint(1) DEFAULT '0',
  `lastUpdateOfChangedRecords` int(11) DEFAULT '0',
  `lastUpdateOfAllRecords` int(11) DEFAULT '0',
  `allowLargeDeletes` tinyint(1) DEFAULT '0',
  `numExtractionThreads` int(11) DEFAULT '10',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=16384;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_settings`
--

LOCK TABLES `overdrive_settings` WRITE;
/*!40000 ALTER TABLE `overdrive_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overdrive_stats`
--

DROP TABLE IF EXISTS `overdrive_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `overdrive_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `numCheckouts` int(11) NOT NULL DEFAULT '0',
  `numFailedCheckouts` int(11) NOT NULL DEFAULT '0',
  `numRenewals` int(11) NOT NULL DEFAULT '0',
  `numEarlyReturns` int(11) NOT NULL DEFAULT '0',
  `numHoldsPlaced` int(11) NOT NULL DEFAULT '0',
  `numFailedHolds` int(11) NOT NULL DEFAULT '0',
  `numHoldsCancelled` int(11) NOT NULL DEFAULT '0',
  `numHoldsFrozen` int(11) NOT NULL DEFAULT '0',
  `numHoldsThawed` int(11) NOT NULL DEFAULT '0',
  `numDownloads` int(11) NOT NULL DEFAULT '0',
  `numPreviews` int(11) NOT NULL DEFAULT '0',
  `numOptionsUpdates` int(11) NOT NULL DEFAULT '0',
  `numApiErrors` int(11) NOT NULL DEFAULT '0',
  `numConnectionFailures` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `instance` (`instance`,`year`,`month`),
  KEY `instance_2` (`instance`,`year`,`month`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=2340;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_stats`
--

LOCK TABLES `overdrive_stats` WRITE;
/*!40000 ALTER TABLE `overdrive_stats` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_stats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `paypal_settings`
--

DROP TABLE IF EXISTS `paypal_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `paypal_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sandboxMode` tinyint(1) DEFAULT NULL,
  `clientId` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `clientSecret` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `paypal_settings`
--

LOCK TABLES `paypal_settings` WRITE;
/*!40000 ALTER TABLE `paypal_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `paypal_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(75) COLLATE utf8mb4_general_ci NOT NULL,
  `sectionName` varchar(75) COLLATE utf8mb4_general_ci NOT NULL,
  `requiredModule` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `weight` int(11) NOT NULL DEFAULT '0',
  `description` varchar(250) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=287 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=458;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (148,'Administer Modules','System Administration','',0,'Allow information about Aspen Discovery Modules to be displayed and enabled or disabled.'),(149,'Administer Users','System Administration','',10,'Allows configuration of who has administration privileges within Aspen Discovery. <i>Give to trusted users, this has security implications.</i>'),(150,'Administer Permissions','System Administration','',15,'Allows configuration of the roles within Aspen Discovery and what each role can do. <i>Give to trusted users, this has security implications.</i>'),(151,'Run Database Maintenance','System Administration','',20,'Controls if the user can run database maintenance or not.'),(152,'Administer SendGrid','System Administration','',30,'Controls if the user can change SendGrid settings. <em>This has potential security and cost implications.</em>'),(153,'Administer System Variables','System Administration','',40,'Controls if the user can change system variables.'),(154,'View System Reports','Reporting','',0,'Controls if the user can view System Reports that show how Aspen Discovery performs and how background tasks are operating. Includes Indexing Logs and Dashboards.'),(155,'View Indexing Logs','Reporting','',10,'Controls if the user can view Indexing Logs for the ILS and eContent.'),(156,'View Dashboards','Reporting','',20,'Controls if the user can view Dashboards showing usage information.'),(157,'Administer All Themes','Theme & Layout','',0,'Allows the user to control all themes within Aspen Discovery.'),(158,'Administer Library Themes','Theme & Layout','',10,'Allows the user to control theme for their home library within Aspen Discovery.'),(159,'Administer All Layout Settings','Theme & Layout','',20,'Allows the user to view and change all layout settings within Aspen Discovery.'),(160,'Administer Library Layout Settings','Theme & Layout','',30,'Allows the user to view and change layout settings for their home library within Aspen Discovery.'),(161,'Administer All Libraries','Primary Configuration','',0,'Allows the user to control settings for all libraries within Aspen Discovery.'),(162,'Administer Home Library','Primary Configuration','',10,'Allows the user to control settings for their home library'),(163,'Administer All Locations','Primary Configuration','',20,'Allows the user to control settings for all locations.'),(164,'Administer Home Library Locations','Primary Configuration','',30,'Allows the user to control settings for all locations that are part of their home library.'),(165,'Administer Home Location','Primary Configuration','',40,'Allows the user to control settings for their home location.'),(166,'Administer IP Addresses','Primary Configuration','',50,'Allows the user to administer IP addresses for Aspen Discovery. <em>This has potential security implications</em>'),(167,'Administer Patron Types','Primary Configuration','',60,'Allows the user to administer how patron types in the ILS are handled within for Aspen Discovery. <i>Give to trusted users, this has security implications.</i>'),(168,'Administer Account Profiles','Primary Configuration','',70,'Allows the user to administer patrons are loaded from the ILS and/or the database. <i>Give to trusted users, this has security implications.</i>'),(169,'Block Patron Account Linking','Primary Configuration','',80,'Allows the user to prevent users from linking to other users.'),(170,'Manage Library Materials Requests','Materials Requests','',0,'Allows the user to update and process materials requests for patrons.'),(171,'Administer Materials Requests','Materials Requests','',10,'Allows the user to configure the materials requests system for their library.'),(172,'View Materials Requests Reports','Materials Requests','',20,'Allows the user to view reports about the materials requests system for their library.'),(173,'Import Materials Requests','Materials Requests','',30,'Allows the user to import materials requests from older systems. <em>Not recommended in most cases unless an active conversion is being done.</em>'),(174,'Administer Languages','Languages and Translations','',0,'Allows the user to control which languages are available for the Aspen Discovery interface.'),(175,'Translate Aspen','Languages and Translations','',10,'Allows the user to translate the Aspen Discovery interface.'),(176,'Manually Group and Ungroup Works','Cataloging & eContent','',0,'Allows the user to manually group and ungroup works.'),(177,'Set Grouped Work Display Information','Cataloging & eContent','',10,'Allows the user to override title, author, and series information for a grouped work.'),(178,'Force Reindexing of Records','Cataloging & eContent','',20,'Allows the user to force individual records to be indexed.'),(179,'Upload Covers','Cataloging & eContent','',30,'Allows the user to upload covers for a record.'),(180,'Upload PDFs','Cataloging & eContent','',40,'Allows the user to upload PDFs for a record.'),(181,'Upload Supplemental Files','Cataloging & eContent','',50,'Allows the user to upload supplemental for a record.'),(182,'Download MARC Records','Cataloging & eContent','',52,'Allows the user to download MARC records for individual records.'),(183,'View ILS records in native OPAC','Cataloging & eContent','',55,'Allows the user to view ILS records in the native OPAC for the ILS if available.'),(184,'View ILS records in native Staff Client','Cataloging & eContent','',56,'Allows the user to view ILS records in the staff client for the ILS if available.'),(185,'Administer Indexing Profiles','Cataloging & eContent','',60,'Allows the user to administer Indexing Profiles to define how record from the ILS are indexed in Aspen Discovery.'),(186,'Administer Translation Maps','Cataloging & eContent','',70,'Allows the user to administer how fields within the ILS are mapped to Aspen Discovery.'),(187,'Administer Loan Rules','Cataloging & eContent','',80,'Allows the user to administer load loan rules and loan rules into Aspen Discovery (Sierra & Millenium only).'),(188,'View Offline Holds Report','Cataloging & eContent','',90,'Allows the user to see any holds that were entered while the ILS was offline.'),(189,'Administer Axis 360','Cataloging & eContent','Axis 360',100,'Allows the user configure Axis 360 integration for all libraries.'),(190,'Administer Cloud Library','Cataloging & eContent','Cloud Library',110,'Allows the user configure Cloud Library integration for all libraries.'),(191,'Administer EBSCO EDS','Cataloging & eContent','EBSCO EDS',120,'Allows the user configure EBSCO EDS integration for all libraries.'),(192,'Administer Hoopla','Cataloging & eContent','Hoopla',130,'Allows the user configure Hoopla integration for all libraries.'),(193,'Administer OverDrive','Cataloging & eContent','OverDrive',140,'Allows the user configure OverDrive integration for all libraries.'),(194,'View OverDrive Test Interface','Cataloging & eContent','OverDrive',150,'Allows the user view OverDrive API information and call OverDrive for specific records.'),(195,'Administer RBdigital','Cataloging & eContent','RBdigital',160,'Allows the user configure RBdigital integration for all libraries.'),(196,'Administer Side Loads','Cataloging & eContent','Side Loads',170,'Controls if the user can administer side loads.'),(197,'Administer All Grouped Work Display Settings','Grouped Work Display','',0,'Allows the user to view and change all grouped work display settings within Aspen Discovery.'),(198,'Administer Library Grouped Work Display Settings','Grouped Work Display','',10,'Allows the user to view and change grouped work display settings for their home library within Aspen Discovery.'),(199,'Administer All Grouped Work Facets','Grouped Work Display','',20,'Allows the user to view and change all grouped work facets within Aspen Discovery.'),(200,'Administer Library Grouped Work Facets','Grouped Work Display','',30,'Allows the user to view and change grouped work facets for their home library within Aspen Discovery.'),(201,'Administer All Browse Categories','Local Enrichment','',0,'Allows the user to view and change all browse categories within Aspen Discovery.'),(202,'Administer Library Browse Categories','Local Enrichment','',10,'Allows the user to view and change browse categories for their home library within Aspen Discovery.'),(203,'Administer All Collection Spotlights','Local Enrichment','',20,'Allows the user to view and change all collection spotlights within Aspen Discovery.'),(204,'Administer Library Collection Spotlights','Local Enrichment','',30,'Allows the user to view and change collection spotlights for their home library within Aspen Discovery.'),(205,'Administer All Placards','Local Enrichment','',40,'Allows the user to view and change all placards within Aspen Discovery.'),(206,'Administer Library Placards','Local Enrichment','',50,'Allows the user to view and change placards for their home library within Aspen Discovery.'),(207,'Moderate User Reviews','Local Enrichment','',60,'Allows the delete any user review within Aspen Discovery.'),(208,'Administer Third Party Enrichment API Keys','Third Party Enrichment','',0,'Allows the user to define connection to external enrichment systems like Content Cafe, Syndetics, Google, Novelist etc.'),(209,'Administer Wikipedia Integration','Third Party Enrichment','',10,'Allows the user to control how authors are matched to Wikipedia entries.'),(210,'View New York Times Lists','Third Party Enrichment','',20,'Allows the user to view and update lists loaded from the New York Times.'),(211,'Administer Open Archives','Open Archives','Open Archives',0,'Allows the user to administer integration with Open Archives repositories for all libraries.'),(212,'Administer Library Calendar Settings','Events','Events',10,'Allows the user to administer integration with Library Calendar for all libraries.'),(213,'Administer Website Indexing Settings','Website Indexing','Web Indexer',0,'Allows the user to administer the indexing of websites for all libraries.'),(216,'Submit Ticket','Aspen Discovery Help','',20,'Allows the user to submit Aspen Discovery tickets.'),(217,'Administer Genealogy','Genealogy','Genealogy',0,'Allows the user to add people, marriages, and obituaries to the genealogy interface.'),(218,'Include Lists In Search Results','User Lists','',0,'Allows the user to add public lists to search results.'),(219,'Edit All Lists','User Lists','',10,'Allows the user to edit public lists created by any user.'),(220,'Masquerade as any user','Masquerade','',0,'Allows the user to masquerade as any other user including restricted patron types.'),(221,'Masquerade as unrestricted patron types','Masquerade','',10,'Allows the user to masquerade as any other user if their patron type is unrestricted.'),(222,'Masquerade as patrons with same home library','Masquerade','',20,'Allows the user to masquerade as patrons with the same home library including restricted patron types.'),(223,'Masquerade as unrestricted patrons with same home library','Masquerade','',30,'Allows the user to masquerade as patrons with the same home library if their patron type is unrestricted.'),(224,'Masquerade as patrons with same home location','Masquerade','',40,'Allows the user to masquerade as patrons with the same home location including restricted patron types.'),(225,'Masquerade as unrestricted patrons with same home location','Masquerade','',50,'Allows the user to masquerade as patrons with the same home location if their patron type is unrestricted.'),(226,'Test Roles','System Administration','',17,'Allows the user to use the test_role parameter to act as different role.'),(227,'Administer List Indexing Settings','User Lists','',0,'Allows the user to administer list indexing settings.'),(228,'View Location Holds Reports','Circulation Reports','',0,'Allows the user to view lists of holds to be pulled for their home location (CARL.X) only.'),(229,'View All Holds Reports','Circulation Reports','',10,'Allows the user to view lists of holds to be pulled for any location (CARL.X) only.'),(230,'View Location Student Reports','Circulation Reports','',20,'Allows the user to view barcode and checkout reports for their home location (CARL.X) only.'),(231,'View All Student Reports','Circulation Reports','',30,'Allows the user to view barcode and checkout reports for any location (CARL.X) only.'),(232,'View Unpublished Content','Web Builder','',0,'Allows the user to view unpublished menu items and content.'),(233,'Administer Host Information','System Administration','',50,'Allows the user to change information about the hosts used for Aspen Discovery.'),(234,'Administer All System Messages','Local Enrichment','',70,'Allows the user to define system messages for all libraries within Aspen Discovery.'),(235,'Administer Library System Messages','Local Enrichment','',80,'Allows the user to define system messages for their library within Aspen Discovery.'),(236,'Administer All Menus','Web Builder','Web Builder',0,'Allows the user to define the menu for all libraries.'),(237,'Administer Library Menus','Web Builder','Web Builder',1,'Allows the user to define the menu for their home library.'),(238,'Administer All Basic Pages','Web Builder','Web Builder',10,'Allows the user to define basic pages for all libraries.'),(239,'Administer Library Basic Pages','Web Builder','Web Builder',11,'Allows the user to define basic pages for their home library.'),(240,'Administer All Custom Pages','Web Builder','Web Builder',20,'Allows the user to define custom pages for all libraries.'),(241,'Administer Library Custom Pages','Web Builder','Web Builder',21,'Allows the user to define custom pages for their home library.'),(242,'Administer All Custom Forms','Web Builder','Web Builder',30,'Allows the user to define custom forms for all libraries.'),(243,'Administer Library Custom Forms','Web Builder','Web Builder',31,'Allows the user to define custom forms for their home library.'),(244,'Administer All Web Resources','Web Builder','Web Builder',40,'Allows the user to add web resources for all libraries.'),(245,'Administer Library Web Resources','Web Builder','Web Builder',41,'Allows the user to add web resources for their home library.'),(246,'Administer All Staff Members','Web Builder','Web Builder',50,'Allows the user to add staff members for all libraries.'),(247,'Administer Library Staff Members','Web Builder','Web Builder',51,'Allows the user to add staff members for their home library.'),(248,'Administer All Web Content','Web Builder','Web Builder',60,'Allows the user to add images, pdfs, and videos.'),(249,'Administer All Web Categories','Web Builder','Web Builder',70,'Allows the user to define audiences and categories for content.'),(250,'Administer All JavaScript Snippets','Local Enrichment','',70,'Allows the user to define JavaScript Snippets to be added to the site. This permission has security implications.'),(251,'Administer Library JavaScript Snippets','Local Enrichment','',71,'Allows the user to define JavaScript Snippets to be added to the site for their library. This permission has security implications.'),(252,'Administer Amazon SES','System Administration','',29,'Controls if the user can change Amazon SES settings. <em>This has potential security and cost implications.</em>'),(253,'Upload List Covers','User Lists','',1,'Allows users to upload covers for a list.'),(254,'Library Domain Settings','Primary Configuration - Library Fields','',1,'Configure Library fields related to URLs and base configuration to access Aspen.'),(255,'Library Theme Configuration','Primary Configuration - Library Fields','',3,'Configure Library fields related to how theme display is configured for the library.'),(256,'Library Contact Settings','Primary Configuration - Library Fields','',6,'Configure Library fields related to contact information for the library.'),(257,'Library ILS Connection','Primary Configuration - Library Fields','',9,'Configure Library fields related to how Aspen connects to the ILS and settings that depend on how the ILS is configured.'),(258,'Library ILS Options','Primary Configuration - Library Fields','',12,'Configure Library fields related to how Aspen interacts with the ILS.'),(259,'Library Self Registration','Primary Configuration - Library Fields','',15,'Configure Library fields related to how Self Registration is configured in Aspen.'),(260,'Library eCommerce Options','Primary Configuration - Library Fields','',18,'Configure Library fields related to how eCommerce is configured in Aspen.'),(261,'Library Catalog Options','Primary Configuration - Library Fields','',21,'Configure Library fields related to how Catalog results and searching is configured in Aspen.'),(262,'Library Browse Category Options','Primary Configuration - Library Fields','',24,'Configure Library fields related to how browse categories are configured in Aspen.'),(263,'Library Materials Request Options','Primary Configuration - Library Fields','',27,'Configure Library fields related to how materials request is configured in Aspen.'),(264,'Library ILL Options','Primary Configuration - Library Fields','',30,'Configure Library fields related to how ill is configured in Aspen.'),(265,'Library Records included in Catalog','Primary Configuration - Library Fields','',33,'Configure Library fields related to what materials (physical and eContent) are included in the Aspen Catalog.'),(266,'Library Genealogy Content','Primary Configuration - Library Fields','',36,'Configure Library fields related to genealogy content.'),(267,'Library Islandora Archive Options','Primary Configuration - Library Fields','',39,'Configure Library fields related to Islandora based archive.'),(268,'Library Archive Options','Primary Configuration - Library Fields','',42,'Configure Library fields related to open archives content.'),(269,'Library Web Builder Options','Primary Configuration - Library Fields','',45,'Configure Library fields related to web builder content.'),(270,'Library EDS Options','Primary Configuration - Library Fields','',48,'Configure Library fields related to EDS content.'),(271,'Library Holidays','Primary Configuration - Library Fields','',51,'Configure Library holidays.'),(272,'Library Menu','Primary Configuration - Library Fields','',54,'Configure Library menu.'),(273,'Location Domain Settings','Primary Configuration - Location Fields','',1,'Configure Location fields related to URLs and base configuration to access Aspen.'),(274,'Location Theme Configuration','Primary Configuration - Location Fields','',3,'Configure Location fields related to how theme display is configured for the library.'),(275,'Location Address and Hours Settings','Primary Configuration - Location Fields','',6,'Configure Location fields related to the address and hours of operation.'),(276,'Location ILS Connection','Primary Configuration - Location Fields','',9,'Configure Location fields related to how Aspen connects to the ILS and settings that depend on how the ILS is configured.'),(277,'Location ILS Options','Primary Configuration - Location Fields','',12,'Configure Location fields related to how Aspen interacts with the ILS.'),(278,'Location Catalog Options','Primary Configuration - Location Fields','',15,'Configure Location fields related to how Catalog results and searching is configured in Aspen.'),(279,'Location Browse Category Options','Primary Configuration - Location Fields','',18,'Configure Location fields related to how Catalog results and searching is configured in Aspen.'),(280,'Location Records included in Catalog','Primary Configuration - Location Fields','',21,'Configure Location fields related to what materials (physical and eContent) are included in the Aspen Catalog.'),(281,'Administer Comprise','eCommerce','',10,'Controls if the user can change Comprise settings. <em>This has potential security and cost implications.</em>'),(282,'Administer ProPay','eCommerce','',10,'Controls if the user can change ProPay settings. <em>This has potential security and cost implications.</em>'),(283,'Administer PayPal','eCommerce','',10,'Controls if the user can change PayPal settings. <em>This has potential security and cost implications.</em>'),(284,'Administer WorldPay','eCommerce','',10,'Controls if the user can change WorldPay settings. <em>This has potential security and cost implications.</em>'),(285,'View eCommerce Reports','eCommerce','',5,'Controls if the user can view eCommerce payment information.'),(286,'Edit Library Placards','Local Enrichment','',55,'Allows the user to edit, but not create placards for their library.');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `person`
--

DROP TABLE IF EXISTS `person`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `person` (
  `personId` int(11) NOT NULL AUTO_INCREMENT,
  `firstName` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `middleName` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lastName` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `maidenName` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `otherName` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nickName` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `birthDate` date DEFAULT NULL,
  `deathDate` date DEFAULT NULL,
  `ageAtDeath` mediumtext COLLATE utf8mb4_general_ci,
  `cemeteryName` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cemeteryLocation` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mortuaryName` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `comments` longtext COLLATE utf8mb4_general_ci,
  `picture` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ledgerVolume` varchar(20) COLLATE utf8mb4_general_ci DEFAULT '',
  `ledgerYear` varchar(20) COLLATE utf8mb4_general_ci DEFAULT '',
  `ledgerEntry` varchar(20) COLLATE utf8mb4_general_ci DEFAULT '',
  `sex` varchar(20) COLLATE utf8mb4_general_ci DEFAULT '',
  `race` varchar(20) COLLATE utf8mb4_general_ci DEFAULT '',
  `residence` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '',
  `causeOfDeath` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '',
  `cemeteryAvenue` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '',
  `veteranOf` varchar(100) COLLATE utf8mb4_general_ci DEFAULT '',
  `addition` varchar(100) COLLATE utf8mb4_general_ci DEFAULT '',
  `block` varchar(100) COLLATE utf8mb4_general_ci DEFAULT '',
  `lot` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '',
  `grave` int(11) DEFAULT NULL,
  `tombstoneInscription` mediumtext COLLATE utf8mb4_general_ci,
  `addedBy` int(11) NOT NULL DEFAULT '-1',
  `dateAdded` int(11) DEFAULT NULL,
  `modifiedBy` int(11) NOT NULL DEFAULT '-1',
  `lastModified` int(11) DEFAULT NULL,
  `privateComments` mediumtext COLLATE utf8mb4_general_ci,
  `importedFrom` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `birthDateDay` int(11) DEFAULT NULL COMMENT 'The day of the month the person was born empty or null if not known',
  `birthDateMonth` int(11) DEFAULT NULL COMMENT 'The month the person was born, null or blank if not known',
  `birthDateYear` int(11) DEFAULT NULL COMMENT 'The year the person was born, null or blank if not known',
  `deathDateDay` int(11) DEFAULT NULL COMMENT 'The day of the month the person died empty or null if not known',
  `deathDateMonth` int(11) DEFAULT NULL COMMENT 'The month the person died, null or blank if not known',
  `deathDateYear` int(11) DEFAULT NULL COMMENT 'The year the person died, null or blank if not known',
  PRIMARY KEY (`personId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=1260 COMMENT='Stores information about a particular person for use in genealogy';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `person`
--

LOCK TABLES `person` WRITE;
/*!40000 ALTER TABLE `person` DISABLE KEYS */;
/*!40000 ALTER TABLE `person` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `placard_dismissal`
--

DROP TABLE IF EXISTS `placard_dismissal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `placard_dismissal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `placardId` int(11) DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userPlacard` (`userId`,`placardId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `placard_dismissal`
--

LOCK TABLES `placard_dismissal` WRITE;
/*!40000 ALTER TABLE `placard_dismissal` DISABLE KEYS */;
/*!40000 ALTER TABLE `placard_dismissal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `placard_language`
--

DROP TABLE IF EXISTS `placard_language`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `placard_language` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `placardId` int(11) DEFAULT NULL,
  `languageId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `placardLanguage` (`placardId`,`languageId`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=1489;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `placard_language`
--

LOCK TABLES `placard_language` WRITE;
/*!40000 ALTER TABLE `placard_language` DISABLE KEYS */;
/*!40000 ALTER TABLE `placard_language` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `placard_library`
--

DROP TABLE IF EXISTS `placard_library`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `placard_library` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `placardId` int(11) DEFAULT NULL,
  `libraryId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `placardLibrary` (`placardId`,`libraryId`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=1365;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `placard_library`
--

LOCK TABLES `placard_library` WRITE;
/*!40000 ALTER TABLE `placard_library` DISABLE KEYS */;
/*!40000 ALTER TABLE `placard_library` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `placard_location`
--

DROP TABLE IF EXISTS `placard_location`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `placard_location` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `placardId` int(11) DEFAULT NULL,
  `locationId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `placardLocation` (`placardId`,`locationId`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=682;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `placard_location`
--

LOCK TABLES `placard_location` WRITE;
/*!40000 ALTER TABLE `placard_location` DISABLE KEYS */;
/*!40000 ALTER TABLE `placard_location` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `placard_trigger`
--

DROP TABLE IF EXISTS `placard_trigger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `placard_trigger` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `placardId` int(11) NOT NULL,
  `triggerWord` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `exactMatch` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `triggerWord` (`triggerWord`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=1024;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `placard_trigger`
--

LOCK TABLES `placard_trigger` WRITE;
/*!40000 ALTER TABLE `placard_trigger` DISABLE KEYS */;
/*!40000 ALTER TABLE `placard_trigger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `placards`
--

DROP TABLE IF EXISTS `placards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `placards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `body` mediumtext COLLATE utf8mb4_general_ci,
  `css` mediumtext COLLATE utf8mb4_general_ci,
  `image` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `link` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dismissable` tinyint(1) DEFAULT NULL,
  `startDate` int(11) DEFAULT '0',
  `endDate` int(11) DEFAULT '0',
  `altText` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `title` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=4096;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `placards`
--

LOCK TABLES `placards` WRITE;
/*!40000 ALTER TABLE `placards` DISABLE KEYS */;
/*!40000 ALTER TABLE `placards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `propay_settings`
--

DROP TABLE IF EXISTS `propay_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `propay_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `useTestSystem` tinyint(1) DEFAULT NULL,
  `authenticationToken` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `billerAccountId` bigint(20) DEFAULT NULL,
  `merchantProfileId` bigint(20) DEFAULT NULL,
  `certStr` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `accountNum` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `termId` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `propay_settings`
--

LOCK TABLES `propay_settings` WRITE;
/*!40000 ALTER TABLE `propay_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `propay_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ptype`
--

DROP TABLE IF EXISTS `ptype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ptype` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pType` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `maxHolds` int(11) NOT NULL DEFAULT '300',
  `assignedRoleId` int(11) DEFAULT '-1',
  `restrictMasquerade` tinyint(1) DEFAULT '0',
  `isStaff` tinyint(1) DEFAULT '0',
  `description` varchar(100) COLLATE utf8mb4_general_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `pType` (`pType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ptype`
--

LOCK TABLES `ptype` WRITE;
/*!40000 ALTER TABLE `ptype` DISABLE KEYS */;
/*!40000 ALTER TABLE `ptype` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ptype_restricted_locations`
--

DROP TABLE IF EXISTS `ptype_restricted_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ptype_restricted_locations` (
  `locationId` int(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique id for the non holdable location',
  `millenniumCode` varchar(5) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'The internal 5 letter code within Millennium',
  `holdingDisplay` varchar(30) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'The text displayed in the holdings list within Millennium can use regular expression syntax to match multiple locations',
  `allowablePtypes` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'A list of PTypes that are allowed to place holds on items with this location separated with pipes (|).',
  PRIMARY KEY (`locationId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ptype_restricted_locations`
--

LOCK TABLES `ptype_restricted_locations` WRITE;
/*!40000 ALTER TABLE `ptype_restricted_locations` DISABLE KEYS */;
/*!40000 ALTER TABLE `ptype_restricted_locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quipu_ecard_setting`
--

DROP TABLE IF EXISTS `quipu_ecard_setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quipu_ecard_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `clientId` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quipu_ecard_setting`
--

LOCK TABLES `quipu_ecard_setting` WRITE;
/*!40000 ALTER TABLE `quipu_ecard_setting` DISABLE KEYS */;
/*!40000 ALTER TABLE `quipu_ecard_setting` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rbdigital_availability`
--

DROP TABLE IF EXISTS `rbdigital_availability`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rbdigital_availability` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rbdigitalId` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `isAvailable` tinyint(4) NOT NULL DEFAULT '1',
  `isOwned` tinyint(4) NOT NULL DEFAULT '1',
  `name` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rawChecksum` bigint(20) DEFAULT NULL,
  `rawResponse` longtext COLLATE utf8mb4_general_ci,
  `lastChange` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rbdigitalId` (`rbdigitalId`),
  KEY `lastChange` (`lastChange`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rbdigital_availability`
--

LOCK TABLES `rbdigital_availability` WRITE;
/*!40000 ALTER TABLE `rbdigital_availability` DISABLE KEYS */;
/*!40000 ALTER TABLE `rbdigital_availability` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rbdigital_export_log`
--

DROP TABLE IF EXISTS `rbdigital_export_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rbdigital_export_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `notes` mediumtext COLLATE utf8mb4_general_ci COMMENT 'Additional information about the run',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=99 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rbdigital_export_log`
--

LOCK TABLES `rbdigital_export_log` WRITE;
/*!40000 ALTER TABLE `rbdigital_export_log` DISABLE KEYS */;
INSERT INTO `rbdigital_export_log` VALUES (1,1551765595,NULL,1551765595,'Initialization complete'),(2,1551766148,1551766149,1551766148,'Initialization complete'),(3,1551801745,1551801746,1551801745,'Initialization complete'),(4,1551801807,1551801808,1551801807,'Initialization complete'),(5,1551806270,1551806435,1551806270,'Initialization complete'),(6,1551806464,1551806561,1551806464,'Initialization complete'),(7,1551806569,1551806590,1551806569,'Initialization complete'),(8,1551806757,1551809718,1551806757,'Initialization complete'),(9,1551809725,1551809751,1551809725,'Initialization complete'),(10,1551810119,1551810292,1551810119,'Initialization complete'),(11,1551810310,1551810371,1551810310,'Initialization complete'),(12,1551810377,1551810418,1551810377,'Initialization complete'),(13,1551810438,1551810506,1551810438,'Initialization complete'),(14,1551817149,1551817176,1551817149,'Initialization complete'),(15,1551817402,1551817429,1551817402,'Initialization complete'),(16,1551817492,1551817597,1551817492,'Initialization complete'),(17,1551817607,1551817647,1551817607,'Initialization complete'),(18,1551817866,1551817868,1551817866,'Initialization complete'),(19,1551817902,1551817904,1551817902,'Initialization complete'),(20,1551817926,1551817928,1551817926,'Initialization complete'),(21,1551818052,1551818054,1551818052,'Initialization complete'),(22,1551819170,1551819174,1551819170,'Initialization complete'),(23,1551819269,1551819271,1551819269,'Initialization complete'),(24,1551819353,1551819355,1551819353,'Initialization complete'),(25,1551819365,1551819384,1551819365,'Initialization complete'),(26,1551819486,1551819497,1551819486,'Initialization complete'),(27,1551819515,1551819517,1551819515,'Initialization complete'),(28,1551819558,1551819613,1551819558,'Initialization complete'),(29,1551820099,1551820773,1551820099,'Initialization complete'),(30,1551823616,1551823703,1551823616,'Initialization complete'),(31,1551823719,1551823723,1551823719,'Initialization complete'),(32,1551823749,1551823752,1551823749,'Initialization complete'),(33,1551823785,1551823787,1551823785,'Initialization complete'),(34,1551823799,1551823907,1551823799,'Initialization complete'),(35,1551824192,1551824195,1551824192,'Initialization complete'),(36,1551824238,1551824241,1551824238,'Initialization complete'),(37,1551824272,1551824275,1551824272,'Initialization complete'),(38,1551824286,1551824291,1551824286,'Initialization complete'),(39,1551824487,1551824636,1551824487,'Initialization complete'),(40,1551825883,NULL,1551825883,'Initialization complete'),(41,1551826000,NULL,1551826000,'Initialization complete'),(42,1551826090,NULL,1551826090,'Initialization complete'),(43,1551826438,1551826439,1551826438,'Initialization complete'),(44,1551827924,NULL,1551827924,'Initialization complete'),(45,1551828163,NULL,1551828163,'Initialization complete'),(46,1551831051,1551831148,1551831051,'Initialization complete'),(47,1551831188,1551831398,1551831188,'Initialization complete'),(48,1551831405,NULL,1551831405,'Initialization complete'),(49,1551840649,1551840670,1551840649,'Initialization complete'),(50,1551885040,NULL,1551885040,'Initialization complete'),(51,1551885478,1551885870,1551885478,'Initialization complete'),(52,1551886320,NULL,1551886320,'Initialization complete'),(53,1551889641,NULL,1551889641,'Initialization complete'),(54,1551889819,NULL,1551889819,'Initialization complete'),(55,1551889883,1551889968,1551889883,'Initialization complete'),(56,1551901118,NULL,1551901118,'Initialization complete'),(57,1551906802,1551906805,1551906802,'Initialization complete'),(58,1551906854,1551906858,1551906854,'Initialization complete'),(59,1551910268,1551910276,1551910268,'Initialization complete'),(60,1551912452,1551912461,1551912452,'Initialization complete'),(61,1551924787,1551924795,1551924787,'Initialization complete'),(62,1551926801,1551926810,1551926801,'Initialization complete'),(63,1552067683,NULL,1552067715,'<br>2019-03-08 10:55:15: Error loading existing titlesjava.sql.SQLException: Column \'rbdigitalId\' not found.'),(64,1552067721,NULL,1552067721,'Initialization complete'),(65,1552068087,NULL,1552068087,'Initialization complete'),(66,1552068131,NULL,1552068131,'Initialization complete'),(67,1552068245,NULL,1552068245,'Initialization complete'),(68,1552068668,1552068939,1552068668,'Initialization complete'),(69,1552068989,1552069010,1552068989,'Initialization complete'),(70,1552069386,1552069664,1552069386,'Initialization complete'),(71,1552112705,NULL,1552112705,'Initialization complete'),(72,1552112891,1552113527,1552112891,'Initialization complete'),(73,1552113985,1552114613,1552113985,'Initialization complete'),(74,1552115478,NULL,1552115478,'Initialization complete'),(75,1552115664,1552115796,1552115664,'Initialization complete'),(76,1552115853,1552116417,1552115853,'Initialization complete'),(77,1552150919,NULL,1552150919,'Initialization complete'),(78,1552151717,NULL,1552151717,'Initialization complete'),(79,1552156959,1552171837,1552156959,'Initialization complete'),(80,1552172512,1552173278,1552172512,'Initialization complete'),(81,1552175539,1552176319,1552175539,'Initialization complete'),(82,1552180585,1552180721,1552180585,'Initialization complete'),(83,1552271266,NULL,1552271266,'Initialization complete'),(84,1552271464,1552271606,1552271464,'Initialization complete'),(85,1552314563,1552314568,1552314563,'Initialization complete'),(86,1552314595,NULL,1552314595,'Initialization complete'),(87,1552314794,1552314852,1552314794,'Initialization complete'),(88,1552314860,NULL,1552314860,'Initialization complete'),(89,1552315070,NULL,1552315070,'Initialization complete'),(90,1552315412,NULL,1552315412,'Initialization complete'),(91,1552316267,NULL,1552316267,'Initialization complete'),(92,1552316634,NULL,1552316634,'Initialization complete'),(93,1552317098,1552317369,1552317098,'Initialization complete'),(94,1552318142,NULL,1552318142,'Initialization complete'),(95,1552318181,NULL,1552318181,'Initialization complete'),(96,1552318374,NULL,1552318374,'Initialization complete'),(97,1552318479,NULL,1552318479,'Initialization complete'),(98,1552319169,1552319903,1552319169,'Initialization complete');
/*!40000 ALTER TABLE `rbdigital_export_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rbdigital_magazine`
--

DROP TABLE IF EXISTS `rbdigital_magazine`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rbdigital_magazine` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `magazineId` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `issueId` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `publisher` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mediaType` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `language` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rawChecksum` bigint(20) DEFAULT NULL,
  `rawResponse` longtext COLLATE utf8mb4_general_ci,
  `dateFirstDetected` bigint(20) DEFAULT NULL,
  `lastChange` int(11) NOT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `magazineId` (`magazineId`,`issueId`),
  KEY `lastChange` (`lastChange`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=1606;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rbdigital_magazine`
--

LOCK TABLES `rbdigital_magazine` WRITE;
/*!40000 ALTER TABLE `rbdigital_magazine` DISABLE KEYS */;
/*!40000 ALTER TABLE `rbdigital_magazine` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rbdigital_magazine_issue`
--

DROP TABLE IF EXISTS `rbdigital_magazine_issue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rbdigital_magazine_issue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `magazineId` int(11) NOT NULL,
  `issueId` int(11) NOT NULL,
  `imageUrl` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `publishedOn` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `coverDate` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `magazineId` (`magazineId`,`issueId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=242;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rbdigital_magazine_issue`
--

LOCK TABLES `rbdigital_magazine_issue` WRITE;
/*!40000 ALTER TABLE `rbdigital_magazine_issue` DISABLE KEYS */;
/*!40000 ALTER TABLE `rbdigital_magazine_issue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rbdigital_magazine_issue_availability`
--

DROP TABLE IF EXISTS `rbdigital_magazine_issue_availability`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rbdigital_magazine_issue_availability` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `issueId` int(11) NOT NULL,
  `settingId` int(11) NOT NULL,
  `isAvailable` tinyint(1) DEFAULT NULL,
  `isOwned` tinyint(1) DEFAULT NULL,
  `stateId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `issueId` (`issueId`,`settingId`)
) ENGINE=InnoDB AUTO_INCREMENT=540 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=91;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rbdigital_magazine_issue_availability`
--

LOCK TABLES `rbdigital_magazine_issue_availability` WRITE;
/*!40000 ALTER TABLE `rbdigital_magazine_issue_availability` DISABLE KEYS */;
/*!40000 ALTER TABLE `rbdigital_magazine_issue_availability` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rbdigital_magazine_usage`
--

DROP TABLE IF EXISTS `rbdigital_magazine_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rbdigital_magazine_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `magazineId` int(11) DEFAULT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `timesCheckedOut` int(11) NOT NULL,
  `issueId` int(11) DEFAULT NULL,
  `instance` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`magazineId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rbdigital_magazine_usage`
--

LOCK TABLES `rbdigital_magazine_usage` WRITE;
/*!40000 ALTER TABLE `rbdigital_magazine_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `rbdigital_magazine_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rbdigital_record_usage`
--

DROP TABLE IF EXISTS `rbdigital_record_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rbdigital_record_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rbdigitalId` int(11) DEFAULT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `timesHeld` int(11) NOT NULL,
  `timesCheckedOut` int(11) NOT NULL,
  `instance` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`rbdigitalId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rbdigital_record_usage`
--

LOCK TABLES `rbdigital_record_usage` WRITE;
/*!40000 ALTER TABLE `rbdigital_record_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `rbdigital_record_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rbdigital_title`
--

DROP TABLE IF EXISTS `rbdigital_title`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rbdigital_title` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rbdigitalId` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `primaryAuthor` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mediaType` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `isFiction` tinyint(4) NOT NULL DEFAULT '0',
  `audience` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `language` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rawChecksum` bigint(20) NOT NULL,
  `rawResponse` longtext COLLATE utf8mb4_general_ci,
  `lastChange` int(11) NOT NULL,
  `dateFirstDetected` bigint(20) DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `rbdigitalId` (`rbdigitalId`),
  KEY `lastChange` (`lastChange`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rbdigital_title`
--

LOCK TABLES `rbdigital_title` WRITE;
/*!40000 ALTER TABLE `rbdigital_title` DISABLE KEYS */;
/*!40000 ALTER TABLE `rbdigital_title` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recaptcha_settings`
--

DROP TABLE IF EXISTS `recaptcha_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recaptcha_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publicKey` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `privateKey` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recaptcha_settings`
--

LOCK TABLES `recaptcha_settings` WRITE;
/*!40000 ALTER TABLE `recaptcha_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `recaptcha_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `record_files`
--

DROP TABLE IF EXISTS `record_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `record_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `identifier` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fileId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fileId` (`fileId`),
  KEY `type` (`type`,`identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `record_files`
--

LOCK TABLES `record_files` WRITE;
/*!40000 ALTER TABLE `record_files` DISABLE KEYS */;
/*!40000 ALTER TABLE `record_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `record_identifiers_to_reload`
--

DROP TABLE IF EXISTS `record_identifiers_to_reload`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `record_identifiers_to_reload` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `identifier` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `processed` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `processed` (`processed`,`type`),
  KEY `type` (`type`,`identifier`)
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=252;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `record_identifiers_to_reload`
--

LOCK TABLES `record_identifiers_to_reload` WRITE;
/*!40000 ALTER TABLE `record_identifiers_to_reload` DISABLE KEYS */;
/*!40000 ALTER TABLE `record_identifiers_to_reload` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `redwood_user_contribution`
--

DROP TABLE IF EXISTS `redwood_user_contribution`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `redwood_user_contribution` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `creator` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dateCreated` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_general_ci,
  `suggestedSubjects` longtext COLLATE utf8mb4_general_ci,
  `howAcquired` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `filePath` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('submitted','accepted','rejected') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `license` enum('none','CC0','cc','public') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `allowRemixing` tinyint(1) DEFAULT '0',
  `prohibitCommercialUse` tinyint(1) DEFAULT '0',
  `requireShareAlike` tinyint(1) DEFAULT '0',
  `dateContributed` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`),
  KEY `userId_2` (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `redwood_user_contribution`
--

LOCK TABLES `redwood_user_contribution` WRITE;
/*!40000 ALTER TABLE `redwood_user_contribution` DISABLE KEYS */;
/*!40000 ALTER TABLE `redwood_user_contribution` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reindex_log`
--

DROP TABLE IF EXISTS `reindex_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reindex_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of reindex log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the reindex started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the reindex process ended',
  `notes` mediumtext COLLATE utf8mb4_general_ci COMMENT 'Notes related to the overall process',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The last time the log was updated',
  `numWorksProcessed` int(11) NOT NULL DEFAULT '0',
  `numErrors` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reindex_log`
--

LOCK TABLES `reindex_log` WRITE;
/*!40000 ALTER TABLE `reindex_log` DISABLE KEYS */;
INSERT INTO `reindex_log` VALUES (1,1548717706,NULL,'<br>2019-01-28 16:21:46Initialized Reindex <br>2019-01-28 16:21:47Setting up update server and solr server<br>2019-01-28 16:21:47Index last ran 1548717707 seconds ago<br>2019-01-28 16:21:50Starting to process 236558 grouped works',1548717710,0,0),(2,1548777996,NULL,'<br>2019-01-29 09:06:36Initialized Reindex <br>2019-01-29 09:06:36Setting up update server and solr server<br>2019-01-29 09:06:36Index last ran 1548777996 seconds ago<br>2019-01-29 09:06:36A partial reindex is already running, check to make sure that reindexes don\'t overlap since that can cause poor performance<br>2019-01-29 09:06:37Starting to process 236558 grouped works',1548777997,0,0),(3,1548778036,NULL,'<br>2019-01-29 09:07:16Initialized Reindex <br>2019-01-29 09:07:16Setting up update server and solr server<br>2019-01-29 09:07:16Index last ran 1548778036 seconds ago<br>2019-01-29 09:07:16A partial reindex is already running, check to make sure that reindexes don\'t overlap since that can cause poor performance<br>2019-01-29 09:07:17Starting to process 236558 grouped works',1548778037,0,0),(4,1548780029,1548782973,'<br>2019-01-29 09:40:29Initialized Reindex <br>2019-01-29 09:40:29Setting up update server and solr server<br>2019-01-29 09:40:29Index last ran 1548780029 seconds ago<br>2019-01-29 09:40:29A partial reindex is already running, check to make sure that reindexes don\'t overlap since that can cause poor performance<br>2019-01-29 09:40:29Starting to process 236558 grouped works<br>2019-01-29 10:29:33Starting to process public lists<br>2019-01-29 10:29:33Finished processing public lists<br>2019-01-29 10:29:33Finishing indexing<br>2019-01-29 10:29:33Doing a soft commit to make sure changes are saved<br>2019-01-29 10:29:33Finished Reindex for aspen.demo',1548782973,236558,0),(5,1548813920,1548813921,'<br>2019-01-29 19:05:20Initialized Reindex <br>2019-01-29 19:05:20Setting up update server and solr server<br>2019-01-29 19:05:20Index last ran 33891 seconds ago<br>2019-01-29 19:05:21Starting to process 0 grouped works<br>2019-01-29 19:05:21Starting to process public lists<br>2019-01-29 19:05:21Finished processing public lists<br>2019-01-29 19:05:21Finishing indexing<br>2019-01-29 19:05:21Doing a soft commit to make sure changes are saved<br>2019-01-29 19:05:21Finished Reindex for aspen.demo',1548813921,0,0),(6,1548813938,NULL,'<br>2019-01-29 19:05:38Initialized Reindex <br>2019-01-29 19:05:38Performing full reindex<br>2019-01-29 19:05:38Setting up update server and solr server<br>2019-01-29 19:05:39Starting to process 236558 grouped works<br>2019-01-29 19:06:08Processed 5000 grouped works processed.<br>2019-01-29 19:06:34Processed 10000 grouped works processed.<br>2019-01-29 19:06:59Processed 15000 grouped works processed.<br>2019-01-29 19:07:23Processed 20000 grouped works processed.',1548814043,20000,0),(7,1548814125,NULL,'<br>2019-01-29 19:08:45Initialized Reindex <br>2019-01-29 19:08:45Performing full reindex<br>2019-01-29 19:08:45Setting up update server and solr server<br>2019-01-29 19:08:48Starting to process 236558 grouped works<br>2019-01-29 19:13:06Processed 5000 grouped works processed.<br>2019-01-29 19:18:33Processed 10000 grouped works processed.<br>2019-01-29 19:23:07Processed 15000 grouped works processed.<br>2019-01-29 19:23:35Processed 20000 grouped works processed.<br>2019-01-29 19:24:04Processed 25000 grouped works processed.<br>2019-01-29 19:25:30Processed 30000 grouped works processed.<br>2019-01-29 19:26:23Processed 35000 grouped works processed.<br>2019-01-29 19:26:56Processed 40000 grouped works processed.<br>2019-01-29 19:27:34Processed 45000 grouped works processed.<br>2019-01-29 19:28:34Processed 50000 grouped works processed.<br>2019-01-29 19:29:30Processed 55000 grouped works processed.<br>2019-01-29 19:30:20Processed 60000 grouped works processed.<br>2019-01-29 19:31:12Processed 65000 grouped works processed.<br>2019-01-29 19:32:02Processed 70000 grouped works processed.<br>2019-01-29 19:40:04Processed 75000 grouped works processed.<br>2019-01-29 19:40:35Processed 80000 grouped works processed.<br>2019-01-29 19:41:01Processed 85000 grouped works processed.<br>2019-01-29 19:41:39Processed 90000 grouped works processed.<br>2019-01-29 19:42:15Processed 95000 grouped works processed.<br>2019-01-29 19:42:55Processed 100000 grouped works processed.',1548816175,100000,0),(8,1548952965,NULL,'<br>2019-01-31 09:42:45Initialized Reindex <br>2019-01-31 09:42:45Performing full reindex<br>2019-01-31 09:42:45Setting up update server and solr server<br>2019-01-31 09:42:49Starting to process 236558 grouped works',1548952969,0,0),(9,1549040599,NULL,'<br>2019-02-01 10:03:19Initialized Reindex <br>2019-02-01 10:03:19Performing full reindex<br>2019-02-01 10:03:19Setting up update server and solr server<br>2019-02-01 10:03:21Starting to process 236558 grouped works',1549040601,0,0),(10,1549041853,NULL,'<br>2019-02-01 10:24:13Initialized Reindex <br>2019-02-01 10:24:13Performing full reindex<br>2019-02-01 10:24:13Setting up update server and solr server<br>2019-02-01 10:24:14Starting to process 236558 grouped works',1549041854,0,0),(11,1549042052,NULL,'<br>2019-02-01 10:27:32Initialized Reindex <br>2019-02-01 10:27:32Performing full reindex<br>2019-02-01 10:27:32Setting up update server and solr server<br>2019-02-01 10:27:33Starting to process 236558 grouped works',1549042053,0,0),(12,1549043153,NULL,'<br>2019-02-01 10:45:53Initialized Reindex <br>2019-02-01 10:45:53Performing full reindex<br>2019-02-01 10:45:53Setting up update server and solr server<br>2019-02-01 10:45:55Starting to process 236558 grouped works<br>2019-02-01 10:46:26Processed 5000 grouped works processed.<br>2019-02-01 10:46:56Processed 10000 grouped works processed.<br>2019-02-01 10:47:25Processed 15000 grouped works processed.<br>2019-02-01 10:47:57Processed 20000 grouped works processed.<br>2019-02-01 10:48:32Processed 25000 grouped works processed.',1549043312,25000,0),(13,1549045556,NULL,'<br>2019-02-01 11:25:56Initialized Reindex <br>2019-02-01 11:25:56Performing full reindex<br>2019-02-01 11:25:56Setting up update server and solr server<br>2019-02-01 11:25:57Starting to process 236558 grouped works<br>2019-02-01 11:26:12Processed 5000 grouped works processed.<br>2019-02-01 11:26:22Processed 10000 grouped works processed.<br>2019-02-01 11:26:29Processed 15000 grouped works processed.<br>2019-02-01 11:26:40Processed 20000 grouped works processed.',1549045600,20000,0),(14,1549052281,1549052281,'<br>2019-02-01 13:18:01Initialized Reindex <br>2019-02-01 13:18:01Performing full reindex<br>2019-02-01 13:18:01Setting up update server and solr server<br>2019-02-01 13:18:01Error processing reindex java.lang.NoSuchMethodError: org.apache.http.impl.conn.PoolingHttpClientConnectionManager.setValidateAfterInactivity(I)V<br>2019-02-01 13:18:01Finished Reindex for aspen.demo',1549052281,0,0),(15,1549052641,1549052642,'<br>2019-02-01 13:24:01Initialized Reindex <br>2019-02-01 13:24:01Performing full reindex<br>2019-02-01 13:24:01Setting up update server and solr server<br>2019-02-01 13:24:02Error processing reindex java.lang.NoSuchMethodError: org.apache.http.impl.conn.PoolingHttpClientConnectionManager.setValidateAfterInactivity(I)V<br>2019-02-01 13:24:02Finished Reindex for aspen.demo',1549052642,0,0),(16,1549052732,1549052761,'<br>2019-02-01 13:25:32Initialized Reindex <br>2019-02-01 13:25:32Performing full reindex<br>2019-02-01 13:25:32Setting up update server and solr server<br>2019-02-01 13:26:01Error processing reindex java.lang.NoClassDefFoundError: org/apache/http/client/HttpClient<br>2019-02-01 13:26:01Finished Reindex for aspen.demo',1549052761,0,0),(17,1549052996,NULL,'<br>2019-02-01 13:29:56Initialized Reindex <br>2019-02-01 13:29:56Performing full reindex<br>2019-02-01 13:29:56Setting up update server and solr server<br>2019-02-01 13:30:23Starting to process 236558 grouped works<br>2019-02-01 13:31:06Processed 5000 grouped works processed.<br>2019-02-01 13:31:21Processed 10000 grouped works processed.<br>2019-02-01 13:31:34Processed 15000 grouped works processed.<br>2019-02-01 13:31:49Processed 20000 grouped works processed.<br>2019-02-01 13:32:06Processed 25000 grouped works processed.<br>2019-02-01 13:32:48Processed 30000 grouped works processed.',1549053168,30000,0),(18,1549053844,NULL,'<br>2019-02-01 13:44:04Initialized Reindex <br>2019-02-01 13:44:04Performing full reindex<br>2019-02-01 13:44:04Setting up update server and solr server<br>2019-02-01 13:44:11Starting to process 236558 grouped works<br>2019-02-01 13:44:28Processed 5000 grouped works processed.<br>2019-02-01 13:44:42Processed 10000 grouped works processed.<br>2019-02-01 13:44:55Processed 15000 grouped works processed.<br>2019-02-01 13:45:08Processed 20000 grouped works processed.<br>2019-02-01 13:45:23Processed 25000 grouped works processed.<br>2019-02-01 13:45:52Processed 30000 grouped works processed.<br>2019-02-01 13:46:17Processed 35000 grouped works processed.<br>2019-02-01 13:46:40Processed 40000 grouped works processed.<br>2019-02-01 13:47:01Processed 45000 grouped works processed.<br>2019-02-01 13:47:28Processed 50000 grouped works processed.<br>2019-02-01 13:47:54Processed 55000 grouped works processed.',1549054074,55000,0),(19,1549054592,NULL,'<br>2019-02-01 13:56:32Initialized Reindex <br>2019-02-01 13:56:32Performing full reindex<br>2019-02-01 13:56:32Setting up update server and solr server<br>2019-02-01 13:56:33Starting to process 236558 grouped works<br>2019-02-01 13:56:49Processed 5000 grouped works processed.<br>2019-02-01 13:57:03Processed 10000 grouped works processed.<br>2019-02-01 13:57:17Processed 15000 grouped works processed.<br>2019-02-01 13:57:32Processed 20000 grouped works processed.<br>2019-02-01 13:57:47Processed 25000 grouped works processed.<br>2019-02-01 13:58:25Processed 30000 grouped works processed.<br>2019-02-01 13:58:50Processed 35000 grouped works processed.<br>2019-02-01 13:59:06Processed 40000 grouped works processed.<br>2019-02-01 13:59:17Processed 45000 grouped works processed.<br>2019-02-01 13:59:41Processed 50000 grouped works processed.<br>2019-02-01 14:00:05Processed 55000 grouped works processed.<br>2019-02-01 14:00:31Processed 60000 grouped works processed.<br>2019-02-01 14:00:57Processed 65000 grouped works processed.<br>2019-02-01 14:01:22Processed 70000 grouped works processed.<br>2019-02-01 14:01:48Processed 75000 grouped works processed.<br>2019-02-01 14:02:14Processed 80000 grouped works processed.',1549054934,80000,0),(20,1549057943,NULL,'<br>2019-02-01 14:52:23Initialized Reindex <br>2019-02-01 14:52:23Performing full reindex<br>2019-02-01 14:52:23Setting up update server and solr server<br>2019-02-01 14:52:24Starting to process 236558 grouped works',1549057944,0,0),(21,1550336114,NULL,'<br>2019-02-16 09:55:14Initialized Reindex <br>2019-02-16 09:55:14Performing full reindex<br>2019-02-16 09:55:15Setting up update server and solr server<br>2019-02-16 09:55:16Starting to process 236558 grouped works<br>2019-02-16 09:55:36Processed 5000 grouped works processed.<br>2019-02-16 09:55:59Processed 10000 grouped works processed.<br>2019-02-16 09:56:22Processed 15000 grouped works processed.<br>2019-02-16 09:56:50Processed 20000 grouped works processed.<br>2019-02-16 09:57:19Processed 25000 grouped works processed.<br>2019-02-16 09:58:21Processed 30000 grouped works processed.<br>2019-02-16 09:58:59Processed 35000 grouped works processed.<br>2019-02-16 09:59:24Processed 40000 grouped works processed.<br>2019-02-16 09:59:42Processed 45000 grouped works processed.',1550336382,45000,0),(22,1550336584,NULL,'<br>2019-02-16 10:03:04Initialized Reindex <br>2019-02-16 10:03:04Performing full reindex<br>2019-02-16 10:03:04Setting up update server and solr server<br>2019-02-16 10:03:05Starting to process 236558 grouped works<br>2019-02-16 10:03:22Processed 5000 grouped works processed.<br>2019-02-16 10:03:41Processed 10000 grouped works processed.<br>2019-02-16 10:03:59Processed 15000 grouped works processed.<br>2019-02-16 10:04:21Processed 20000 grouped works processed.<br>2019-02-16 10:04:45Processed 25000 grouped works processed.<br>2019-02-16 10:05:43Processed 30000 grouped works processed.<br>2019-02-16 10:06:09Processed 35000 grouped works processed.<br>2019-02-16 10:06:31Processed 40000 grouped works processed.<br>2019-02-16 10:06:51Processed 45000 grouped works processed.<br>2019-02-16 10:07:24Processed 50000 grouped works processed.',1550336844,50000,0),(23,1550336915,NULL,'<br>2019-02-16 10:08:35Initialized Reindex <br>2019-02-16 10:08:35Performing full reindex<br>2019-02-16 10:08:35Setting up update server and solr server<br>2019-02-16 10:08:36Starting to process 236558 grouped works',1550336916,0,0),(24,1550337026,NULL,'<br>2019-02-16 10:10:26Initialized Reindex <br>2019-02-16 10:10:26Performing full reindex<br>2019-02-16 10:10:26Setting up update server and solr server<br>2019-02-16 10:10:28Starting to process 236558 grouped works',1550337028,0,0),(25,1550337139,NULL,'<br>2019-02-16 10:12:19Initialized Reindex <br>2019-02-16 10:12:19Performing full reindex<br>2019-02-16 10:12:19Setting up update server and solr server<br>2019-02-16 10:12:20Starting to process 236558 grouped works<br>2019-02-16 10:12:37Processed 5000 grouped works processed.<br>2019-02-16 10:12:53Processed 10000 grouped works processed.<br>2019-02-16 10:13:09Processed 15000 grouped works processed.<br>2019-02-16 10:13:27Processed 20000 grouped works processed.<br>2019-02-16 10:13:47Processed 25000 grouped works processed.<br>2019-02-16 10:14:44Processed 30000 grouped works processed.<br>2019-02-16 10:15:17Processed 35000 grouped works processed.<br>2019-02-16 10:15:37Processed 40000 grouped works processed.<br>2019-02-16 10:15:55Processed 45000 grouped works processed.<br>2019-02-16 10:16:26Processed 50000 grouped works processed.',1550337386,50000,0),(26,1550541425,1550541476,'<br>2019-02-18 18:57:05Initialized Reindex <br>2019-02-18 18:57:05Performing full reindex<br>2019-02-18 18:57:05Setting up update server and solr server<br>2019-02-18 18:57:07Starting to process 236558 grouped works<br>2019-02-18 18:57:33Processed 5000 grouped works processed.<br>2019-02-18 18:57:55Processed 10000 grouped works processed.<br>2019-02-18 18:57:55Starting to process public lists<br>2019-02-18 18:57:55Finished processing public lists<br>2019-02-18 18:57:56Finishing indexing<br>2019-02-18 18:57:56Calling final commit<br>2019-02-18 18:57:56Finished Reindex for aspen.demo',1550541476,10000,0),(27,1550541956,1550542000,'<br>2019-02-18 19:05:56Initialized Reindex <br>2019-02-18 19:05:56Performing full reindex<br>2019-02-18 19:05:56Setting up update server and solr server<br>2019-02-18 19:05:58Starting to process 236558 grouped works<br>2019-02-18 19:06:21Processed 5000 grouped works processed.<br>2019-02-18 19:06:38Processed 10000 grouped works processed.<br>2019-02-18 19:06:38Starting to process public lists<br>2019-02-18 19:06:39Finished processing public lists<br>2019-02-18 19:06:39Finishing indexing<br>2019-02-18 19:06:39Calling final commit<br>2019-02-18 19:06:40Finished Reindex for aspen.demo',1550542000,10000,0),(28,1550542304,1550542347,'<br>2019-02-18 19:11:44Initialized Reindex <br>2019-02-18 19:11:44Performing full reindex<br>2019-02-18 19:11:44Setting up update server and solr server<br>2019-02-18 19:11:45Starting to process 236558 grouped works<br>2019-02-18 19:12:06Processed 5000 grouped works processed.<br>2019-02-18 19:12:25Processed 10000 grouped works processed.<br>2019-02-18 19:12:25Starting to process public lists<br>2019-02-18 19:12:26Finished processing public lists<br>2019-02-18 19:12:26Finishing indexing<br>2019-02-18 19:12:26Calling final commit<br>2019-02-18 19:12:27Finished Reindex for aspen.demo',1550542347,10000,0),(29,1550542936,1550542981,'<br>2019-02-18 19:22:16Initialized Reindex <br>2019-02-18 19:22:16Performing full reindex<br>2019-02-18 19:22:17Setting up update server and solr server<br>2019-02-18 19:22:18Starting to process 236558 grouped works<br>2019-02-18 19:22:41Processed 5000 grouped works processed.<br>2019-02-18 19:23:00Processed 10000 grouped works processed.<br>2019-02-18 19:23:00Starting to process public lists<br>2019-02-18 19:23:00Finished processing public lists<br>2019-02-18 19:23:01Finishing indexing<br>2019-02-18 19:23:01Calling final commit<br>2019-02-18 19:23:01Finished Reindex for aspen.demo',1550542981,10000,0),(30,1550543337,1550543380,'<br>2019-02-18 19:28:57Initialized Reindex <br>2019-02-18 19:28:57Performing full reindex<br>2019-02-18 19:28:57Setting up update server and solr server<br>2019-02-18 19:28:58Starting to process 236558 grouped works<br>2019-02-18 19:29:21Processed 5000 grouped works processed.<br>2019-02-18 19:29:38Processed 10000 grouped works processed.<br>2019-02-18 19:29:38Starting to process public lists<br>2019-02-18 19:29:39Finished processing public lists<br>2019-02-18 19:29:39Finishing indexing<br>2019-02-18 19:29:39Calling final commit<br>2019-02-18 19:29:40Finished Reindex for aspen.demo',1550543380,10000,0),(31,1550543901,1550543947,'<br>2019-02-18 19:38:21Initialized Reindex <br>2019-02-18 19:38:21Performing full reindex<br>2019-02-18 19:38:21Setting up update server and solr server<br>2019-02-18 19:38:22Starting to process 236558 grouped works<br>2019-02-18 19:38:47Processed 5000 grouped works processed.<br>2019-02-18 19:39:06Processed 10000 grouped works processed.<br>2019-02-18 19:39:06Starting to process public lists<br>2019-02-18 19:39:07Finished processing public lists<br>2019-02-18 19:39:07Finishing indexing<br>2019-02-18 19:39:07Calling final commit<br>2019-02-18 19:39:07Finished Reindex for aspen.demo',1550543947,10000,0),(32,1550545119,1550545165,'<br>2019-02-18 19:58:39Initialized Reindex <br>2019-02-18 19:58:39Performing full reindex<br>2019-02-18 19:58:39Setting up update server and solr server<br>2019-02-18 19:58:41Starting to process 236558 grouped works<br>2019-02-18 19:59:05Processed 5000 grouped works processed.<br>2019-02-18 19:59:24Processed 10000 grouped works processed.<br>2019-02-18 19:59:24Starting to process public lists<br>2019-02-18 19:59:25Finished processing public lists<br>2019-02-18 19:59:25Finishing indexing<br>2019-02-18 19:59:25Calling final commit<br>2019-02-18 19:59:25Finished Reindex for aspen.demo',1550545165,10000,0),(33,1550617936,1550617985,'<br>2019-02-19 16:12:16Initialized Reindex <br>2019-02-19 16:12:16Performing full reindex<br>2019-02-19 16:12:16Setting up update server and solr server<br>2019-02-19 16:12:17Starting to process 236558 grouped works<br>2019-02-19 16:12:43Processed 5000 grouped works processed.<br>2019-02-19 16:13:05Processed 10000 grouped works processed.<br>2019-02-19 16:13:05Starting to process public lists<br>2019-02-19 16:13:05Finished processing public lists<br>2019-02-19 16:13:05Finishing indexing<br>2019-02-19 16:13:05Calling final commit<br>2019-02-19 16:13:05Finished Reindex for aspen.demo',1550617985,10000,0),(34,1550619827,1550619872,'<br>2019-02-19 16:43:48Initialized Reindex <br>2019-02-19 16:43:48Performing full reindex<br>2019-02-19 16:43:48Setting up update server and solr server<br>2019-02-19 16:43:49Starting to process 236558 grouped works<br>2019-02-19 16:44:11Processed 5000 grouped works processed.<br>2019-02-19 16:44:30Processed 10000 grouped works processed.<br>2019-02-19 16:44:30Starting to process public lists<br>2019-02-19 16:44:31Finished processing public lists<br>2019-02-19 16:44:32Finishing indexing<br>2019-02-19 16:44:32Calling final commit<br>2019-02-19 16:44:32Finished Reindex for aspen.demo',1550619872,10000,0),(35,1550674590,1550674633,'<br>2019-02-20 07:56:30Initialized Reindex <br>2019-02-20 07:56:30Performing full reindex<br>2019-02-20 07:56:30Setting up update server and solr server<br>2019-02-20 07:56:32Starting to process 236558 grouped works<br>2019-02-20 07:56:56Processed 5000 grouped works processed.<br>2019-02-20 07:57:13Processed 10000 grouped works processed.<br>2019-02-20 07:57:13Starting to process public lists<br>2019-02-20 07:57:13Finished processing public lists<br>2019-02-20 07:57:13Finishing indexing<br>2019-02-20 07:57:13Calling final commit<br>2019-02-20 07:57:13Finished Reindex for aspen.demo',1550674633,10000,0),(36,1550705783,1550705821,'<br>2019-02-20 16:36:23Initialized Reindex <br>2019-02-20 16:36:23Performing full reindex<br>2019-02-20 16:36:23Setting up update server and solr server<br>2019-02-20 16:36:24Starting to process 236558 grouped works<br>2019-02-20 16:36:44Processed 5000 grouped works processed.<br>2019-02-20 16:37:00Processed 10000 grouped works processed.<br>2019-02-20 16:37:00Starting to process public lists<br>2019-02-20 16:37:00Finished processing public lists<br>2019-02-20 16:37:01Finishing indexing<br>2019-02-20 16:37:01Calling final commit<br>2019-02-20 16:37:01Finished Reindex for aspen.demo',1550705821,10000,0),(37,1550706044,1550706087,'<br>2019-02-20 16:40:44Initialized Reindex <br>2019-02-20 16:40:44Performing full reindex<br>2019-02-20 16:40:44Setting up update server and solr server<br>2019-02-20 16:40:45Starting to process 236558 grouped works<br>2019-02-20 16:41:08Processed 5000 grouped works processed.<br>2019-02-20 16:41:25Processed 10000 grouped works processed.<br>2019-02-20 16:41:25Starting to process public lists<br>2019-02-20 16:41:26Finished processing public lists<br>2019-02-20 16:41:26Finishing indexing<br>2019-02-20 16:41:26Calling final commit<br>2019-02-20 16:41:27Finished Reindex for aspen.demo',1550706087,10000,0),(38,1550792324,1550792376,'<br>2019-02-21 16:38:44Initialized Reindex <br>2019-02-21 16:38:44Performing full reindex<br>2019-02-21 16:38:44Setting up update server and solr server<br>2019-02-21 16:38:46Starting to process 236558 grouped works<br>2019-02-21 16:39:12Processed 5000 grouped works processed.<br>2019-02-21 16:39:35Processed 10000 grouped works processed.<br>2019-02-21 16:39:35Starting to process public lists<br>2019-02-21 16:39:35Finished processing public lists<br>2019-02-21 16:39:35Finishing indexing<br>2019-02-21 16:39:35Calling final commit<br>2019-02-21 16:39:36Finished Reindex for aspen.demo',1550792376,10000,0),(39,1550797023,1550797064,'<br>2019-02-21 17:57:03Initialized Reindex <br>2019-02-21 17:57:03Performing full reindex<br>2019-02-21 17:57:03Setting up update server and solr server<br>2019-02-21 17:57:05Starting to process 236558 grouped works<br>2019-02-21 17:57:28Processed 5000 grouped works processed.<br>2019-02-21 17:57:42Processed 10000 grouped works processed.<br>2019-02-21 17:57:42Starting to process public lists<br>2019-02-21 17:57:43Finished processing public lists<br>2019-02-21 17:57:43Finishing indexing<br>2019-02-21 17:57:43Calling final commit<br>2019-02-21 17:57:44Finished Reindex for aspen.demo',1550797064,10000,0),(40,1550799563,1550799597,'<br>2019-02-21 18:39:23Initialized Reindex <br>2019-02-21 18:39:23Performing full reindex<br>2019-02-21 18:39:23Setting up update server and solr server<br>2019-02-21 18:39:24Starting to process 236558 grouped works<br>2019-02-21 18:39:42Processed 5000 grouped works processed.<br>2019-02-21 18:39:57Processed 10000 grouped works processed.<br>2019-02-21 18:39:57Starting to process public lists<br>2019-02-21 18:39:57Finished processing public lists<br>2019-02-21 18:39:57Finishing indexing<br>2019-02-21 18:39:57Calling final commit<br>2019-02-21 18:39:57Finished Reindex for aspen.demo',1550799597,10000,0),(41,1550799920,1550799956,'<br>2019-02-21 18:45:20Initialized Reindex <br>2019-02-21 18:45:20Performing full reindex<br>2019-02-21 18:45:20Setting up update server and solr server<br>2019-02-21 18:45:21Starting to process 236558 grouped works<br>2019-02-21 18:45:39Processed 5000 grouped works processed.<br>2019-02-21 18:45:56Processed 10000 grouped works processed.<br>2019-02-21 18:45:56Starting to process public lists<br>2019-02-21 18:45:56Finished processing public lists<br>2019-02-21 18:45:56Finishing indexing<br>2019-02-21 18:45:56Calling final commit<br>2019-02-21 18:45:56Finished Reindex for aspen.demo',1550799956,10000,0),(42,1550800029,1550800064,'<br>2019-02-21 18:47:09Initialized Reindex <br>2019-02-21 18:47:09Performing full reindex<br>2019-02-21 18:47:09Setting up update server and solr server<br>2019-02-21 18:47:10Starting to process 236558 grouped works<br>2019-02-21 18:47:27Processed 5000 grouped works processed.<br>2019-02-21 18:47:44Processed 10000 grouped works processed.<br>2019-02-21 18:47:44Starting to process public lists<br>2019-02-21 18:47:44Finished processing public lists<br>2019-02-21 18:47:44Finishing indexing<br>2019-02-21 18:47:44Calling final commit<br>2019-02-21 18:47:44Finished Reindex for aspen.demo',1550800064,10000,0),(43,1550800300,1550800346,'<br>2019-02-21 18:51:40Initialized Reindex <br>2019-02-21 18:51:40Performing full reindex<br>2019-02-21 18:51:40Setting up update server and solr server<br>2019-02-21 18:51:41Starting to process 236558 grouped works<br>2019-02-21 18:52:05Processed 5000 grouped works processed.<br>2019-02-21 18:52:24Processed 10000 grouped works processed.<br>2019-02-21 18:52:24Starting to process public lists<br>2019-02-21 18:52:25Finished processing public lists<br>2019-02-21 18:52:25Finishing indexing<br>2019-02-21 18:52:25Calling final commit<br>2019-02-21 18:52:26Finished Reindex for aspen.demo',1550800346,10000,0),(44,1550881559,1550881638,'<br>2019-02-22 17:25:59Initialized Reindex <br>2019-02-22 17:25:59Performing full reindex<br>2019-02-22 17:25:59Setting up update server and solr server<br>2019-02-22 17:26:01Starting to process 236558 grouped works<br>2019-02-22 17:26:40Processed 5000 grouped works processed.<br>2019-02-22 17:27:16Processed 10000 grouped works processed.<br>2019-02-22 17:27:16Starting to process public lists<br>2019-02-22 17:27:17Finished processing public lists<br>2019-02-22 17:27:17Finishing indexing<br>2019-02-22 17:27:17Calling final commit<br>2019-02-22 17:27:18Finished Reindex for aspen.demo',1550881638,10000,0),(45,1550881871,1550881933,'<br>2019-02-22 17:31:11Initialized Reindex <br>2019-02-22 17:31:11Performing full reindex<br>2019-02-22 17:31:11Setting up update server and solr server<br>2019-02-22 17:31:12Starting to process 236558 grouped works<br>2019-02-22 17:31:38Processed 5000 grouped works processed.<br>2019-02-22 17:32:10Processed 10000 grouped works processed.<br>2019-02-22 17:32:10Starting to process public lists<br>2019-02-22 17:32:12Finished processing public lists<br>2019-02-22 17:32:12Finishing indexing<br>2019-02-22 17:32:12Calling final commit<br>2019-02-22 17:32:13Finished Reindex for aspen.demo',1550881933,10000,0),(46,1551932748,1551932748,'<br>2019-03-06 21:25:48Initialized Reindex <br>2019-03-06 21:25:48Performing full reindex<br>2019-03-06 21:25:48Error processing reindex java.lang.NoClassDefFoundError: org/apache/solr/client/solrj/request/RequestWriter<br>2019-03-06 21:25:48Finished Reindex for aspen.demo',1551932748,0,0),(47,1551932993,NULL,'<br>2019-03-06 21:29:53Initialized Reindex <br>2019-03-06 21:29:53Performing full reindex<br>2019-03-06 21:29:53Setting up update server and solr server<br>2019-03-06 21:29:54Starting to process 397 grouped works',1551932994,0,0),(48,1551933036,NULL,'<br>2019-03-06 21:30:36Initialized Reindex <br>2019-03-06 21:30:36Performing full reindex<br>2019-03-06 21:30:36Setting up update server and solr server<br>2019-03-06 21:30:37Starting to process 397 grouped works',1551933037,0,0),(49,1551933613,NULL,'<br>2019-03-06 21:40:13Initialized Reindex <br>2019-03-06 21:40:13Performing full reindex<br>2019-03-06 21:40:13Setting up update server and solr server<br>2019-03-06 21:40:13Starting to process 397 grouped works',1551933613,0,0),(50,1551933734,1551935690,'<br>2019-03-06 21:42:14Initialized Reindex <br>2019-03-06 21:42:14Performing full reindex<br>2019-03-06 21:42:14Setting up update server and solr server<br>2019-03-06 21:42:15Starting to process 397 grouped works<br>2019-03-06 22:14:50Exception processing reindex java.lang.NullPointerException<br>2019-03-06 22:14:50Finished Reindex for aspen.demo',1551935690,0,0),(51,1551935698,1551935951,'<br>2019-03-06 22:14:58Initialized Reindex <br>2019-03-06 22:14:58Performing full reindex<br>2019-03-06 22:14:58Setting up update server and solr server<br>2019-03-06 22:14:59Starting to process 397 grouped works<br>2019-03-06 22:19:08Exception processing reindex java.lang.NullPointerException<br>2019-03-06 22:19:11Finished Reindex for aspen.demo',1551935951,0,0),(52,1551935993,1551936008,'<br>2019-03-06 22:19:53Initialized Reindex <br>2019-03-06 22:19:53Performing full reindex<br>2019-03-06 22:19:53Setting up update server and solr server<br>2019-03-06 22:19:53Starting to process 397 grouped works<br>2019-03-06 22:20:08Exception processing reindex java.lang.NullPointerException<br>2019-03-06 22:20:08Finished Reindex for aspen.demo',1551936008,0,0),(53,1551936210,1551936708,'<br>2019-03-06 22:23:30Initialized Reindex <br>2019-03-06 22:23:30Performing full reindex<br>2019-03-06 22:23:30Setting up update server and solr server<br>2019-03-06 22:23:30Starting to process 397 grouped works<br>2019-03-06 22:31:48Exception processing reindex java.lang.NullPointerException<br>2019-03-06 22:31:48Finished Reindex for aspen.demo',1551936708,0,0),(54,1551936846,NULL,'<br>2019-03-06 22:34:06Initialized Reindex <br>2019-03-06 22:34:06Performing full reindex<br>2019-03-06 22:34:06Setting up update server and solr server<br>2019-03-06 22:34:07Starting to process 397 grouped works',1551936847,0,0),(55,1551936964,1551936981,'<br>2019-03-06 22:36:04Initialized Reindex <br>2019-03-06 22:36:04Performing full reindex<br>2019-03-06 22:36:04Setting up update server and solr server<br>2019-03-06 22:36:05Starting to process 397 grouped works<br>2019-03-06 22:36:20Starting to process public lists<br>2019-03-06 22:36:21Finished processing public lists<br>2019-03-06 22:36:21Finishing indexing<br>2019-03-06 22:36:21Calling final commit<br>2019-03-06 22:36:21Finished Reindex for aspen.demo',1551936981,397,0),(56,1551937072,1551937115,'<br>2019-03-06 22:37:52Initialized Reindex <br>2019-03-06 22:37:52Performing full reindex<br>2019-03-06 22:37:52Setting up update server and solr server<br>2019-03-06 22:37:52Starting to process 397 grouped works<br>2019-03-06 22:38:34Starting to process public lists<br>2019-03-06 22:38:34Finished processing public lists<br>2019-03-06 22:38:35Finishing indexing<br>2019-03-06 22:38:35Calling final commit<br>2019-03-06 22:38:35Finished Reindex for aspen.demo',1551937115,397,0),(57,1552057158,NULL,'<br>2019-03-08 07:59:18Initialized Reindex <br>2019-03-08 07:59:18Performing full reindex<br>2019-03-08 07:59:18Setting up update server and solr server',1552057158,0,0),(58,1552057429,1552057430,'<br>2019-03-08 08:03:49Initialized Reindex <br>2019-03-08 08:03:49Setting up update server and solr server<br>2019-03-08 08:03:49Index last ran 120357 seconds ago<br>2019-03-08 08:03:50Starting to process 0 grouped works<br>2019-03-08 08:03:50Starting to process public lists<br>2019-03-08 08:03:50Finished processing public lists<br>2019-03-08 08:03:50Finishing indexing<br>2019-03-08 08:03:50Doing a soft commit to make sure changes are saved<br>2019-03-08 08:03:50Shutting down the update server<br>2019-03-08 08:03:50Finished Reindex for aspen.demo',1552057430,0,0),(59,1552057772,1552057773,'<br>2019-03-08 08:09:32Initialized Reindex <br>2019-03-08 08:09:32Setting up update server and solr server<br>2019-03-08 08:09:32Index last ran 343 seconds ago<br>2019-03-08 08:09:33Starting to process 0 grouped works<br>2019-03-08 08:09:33Starting to process public lists<br>2019-03-08 08:09:33Finished processing public lists<br>2019-03-08 08:09:33Finishing indexing<br>2019-03-08 08:09:33Doing a soft commit to make sure changes are saved<br>2019-03-08 08:09:33Shutting down the update server<br>2019-03-08 08:09:33Finished Reindex for aspen.demo',1552057773,0,0),(60,1552057835,NULL,'<br>2019-03-08 08:10:35Initialized Reindex <br>2019-03-08 08:10:35Performing full reindex<br>2019-03-08 08:10:35Setting up update server and solr server<br>2019-03-08 08:30:44Starting to process 397 grouped works',1552059044,0,0),(61,1552059115,NULL,'<br>2019-03-08 08:31:55Initialized Reindex <br>2019-03-08 08:31:55Performing full reindex<br>2019-03-08 08:31:55Setting up update server and solr server<br>2019-03-08 08:31:56Starting to process 397 grouped works',1552059116,0,0),(62,1552059467,NULL,'<br>2019-03-08 08:37:47Initialized Reindex <br>2019-03-08 08:37:47Performing full reindex<br>2019-03-08 08:37:47Setting up update server and solr server<br>2019-03-08 08:37:48Starting to process 397 grouped works',1552059468,0,0),(63,1552059776,NULL,'<br>2019-03-08 08:42:56Initialized Reindex <br>2019-03-08 08:42:56Performing full reindex<br>2019-03-08 08:42:56Setting up update server and solr server<br>2019-03-08 08:42:57Starting to process 397 grouped works',1552059777,0,0),(64,1552059968,NULL,'<br>2019-03-08 08:46:08Initialized Reindex <br>2019-03-08 08:46:08Performing full reindex<br>2019-03-08 08:46:09Setting up update server and solr server<br>2019-03-08 08:46:09Starting to process 397 grouped works',1552059969,0,0),(65,1552060429,NULL,'<br>2019-03-08 08:53:49Initialized Reindex <br>2019-03-08 08:53:49Performing full reindex<br>2019-03-08 08:53:49Setting up update server and solr server<br>2019-03-08 08:53:50Starting to process 397 grouped works',1552060430,0,0),(66,1552061000,NULL,'<br>2019-03-08 09:03:20Initialized Reindex <br>2019-03-08 09:03:20Performing full reindex<br>2019-03-08 09:03:20Setting up update server and solr server<br>2019-03-08 09:03:21Starting to process 397 grouped works',1552061001,0,0),(67,1552061661,NULL,'<br>2019-03-08 09:14:21Initialized Reindex <br>2019-03-08 09:14:21Performing full reindex<br>2019-03-08 09:14:21Setting up update server and solr server<br>2019-03-08 09:14:22Starting to process 397 grouped works',1552061662,0,0),(68,1552062713,NULL,'<br>2019-03-08 09:31:53Initialized Reindex <br>2019-03-08 09:31:53Performing full reindex<br>2019-03-08 09:31:53Setting up update server and solr server<br>2019-03-08 09:31:54Starting to process 397 grouped works',1552062714,0,0),(69,1552062790,NULL,'<br>2019-03-08 09:33:10Initialized Reindex <br>2019-03-08 09:33:10Performing full reindex<br>2019-03-08 09:33:10Setting up update server and solr server<br>2019-03-08 09:33:11Starting to process 397 grouped works',1552062791,0,0),(70,1552062902,1552062982,'<br>2019-03-08 09:35:02Initialized Reindex <br>2019-03-08 09:35:02Performing full reindex<br>2019-03-08 09:35:02Setting up update server and solr server<br>2019-03-08 09:35:03Starting to process 397 grouped works<br>2019-03-08 09:36:21Starting to process public lists<br>2019-03-08 09:36:21Finished processing public lists<br>2019-03-08 09:36:22Finishing indexing<br>2019-03-08 09:36:22Calling final commit<br>2019-03-08 09:36:22Finished Reindex for aspen.demo',1552062982,397,0),(71,1552063402,1552063415,'<br>2019-03-08 09:43:22Initialized Reindex <br>2019-03-08 09:43:22Performing full reindex<br>2019-03-08 09:43:22Setting up update server and solr server<br>2019-03-08 09:43:23Starting to process 397 grouped works<br>2019-03-08 09:43:34Starting to process public lists<br>2019-03-08 09:43:34Finished processing public lists<br>2019-03-08 09:43:35Finishing indexing<br>2019-03-08 09:43:35Calling final commit<br>2019-03-08 09:43:35Finished Reindex for aspen.demo',1552063415,397,0),(72,1552070632,NULL,'<br>2019-03-08 11:43:52Initialized Reindex <br>2019-03-08 11:43:52Performing full reindex<br>2019-03-08 11:43:52Setting up update server and solr server<br>2019-03-08 11:43:53Starting to process 9878 grouped works',1552070633,0,0),(73,1552070891,NULL,'<br>2019-03-08 11:48:11Initialized Reindex <br>2019-03-08 11:48:11Performing full reindex<br>2019-03-08 11:48:11Setting up update server and solr server<br>2019-03-08 11:48:11Starting to process 9878 grouped works',1552070891,0,0),(74,1552070961,NULL,'<br>2019-03-08 11:49:21Initialized Reindex <br>2019-03-08 11:49:21Performing full reindex<br>2019-03-08 11:49:21Setting up update server and solr server<br>2019-03-08 11:49:22Starting to process 9878 grouped works',1552070962,0,0),(75,1552071014,1552071529,'<br>2019-03-08 11:50:14Initialized Reindex <br>2019-03-08 11:50:14Performing full reindex<br>2019-03-08 11:50:14Setting up update server and solr server<br>2019-03-08 11:50:17Starting to process 9878 grouped works<br>2019-03-08 11:55:46Processed 5000 grouped works processed.<br>2019-03-08 11:58:49Starting to process public lists<br>2019-03-08 11:58:49Finished processing public lists<br>2019-03-08 11:58:49Finishing indexing<br>2019-03-08 11:58:49Calling final commit<br>2019-03-08 11:58:49Finished Reindex for aspen.demo',1552071529,9878,0),(76,1552075678,1552076067,'<br>2019-03-08 13:07:58Initialized Reindex <br>2019-03-08 13:07:58Performing full reindex<br>2019-03-08 13:07:58Setting up update server and solr server<br>2019-03-08 13:07:59Starting to process 9878 grouped works<br>2019-03-08 13:11:38Processed 5000 grouped works processed.<br>2019-03-08 13:14:27Finishing indexing<br>2019-03-08 13:14:27Calling final commit<br>2019-03-08 13:14:27Finished Reindex for aspen.demo',1552076067,9878,0),(77,1552076875,1552077298,'<br>2019-03-08 13:27:55Initialized Reindex <br>2019-03-08 13:27:55Performing full reindex<br>2019-03-08 13:27:55Setting up update server and solr server<br>2019-03-08 13:27:56Starting to process 9878 grouped works<br>2019-03-08 13:31:43Processed 5000 grouped works processed.<br>2019-03-08 13:34:58Finishing indexing<br>2019-03-08 13:34:58Calling final commit<br>2019-03-08 13:34:58Finished Reindex for aspen.demo',1552077298,9878,0),(78,1552077797,1552078149,'<br>2019-03-08 13:43:18Initialized Reindex <br>2019-03-08 13:43:18Performing full reindex<br>2019-03-08 13:43:18Setting up update server and solr server<br>2019-03-08 13:43:19Starting to process 9878 grouped works<br>2019-03-08 13:46:14Processed 5000 grouped works processed.<br>2019-03-08 13:49:09Finishing indexing<br>2019-03-08 13:49:09Calling final commit<br>2019-03-08 13:49:09Finished Reindex for aspen.demo',1552078149,9878,0),(79,1552091272,NULL,'<br>2019-03-08 17:27:52Initialized Reindex <br>2019-03-08 17:27:52Performing full reindex<br>2019-03-08 17:27:52Setting up update server and solr server<br>2019-03-08 17:27:53Starting to process 9878 grouped works',1552091273,0,0),(80,1552092037,1552092453,'<br>2019-03-08 17:40:37Initialized Reindex <br>2019-03-08 17:40:37Performing full reindex<br>2019-03-08 17:40:37Setting up update server and solr server<br>2019-03-08 17:40:38Starting to process 9878 grouped works<br>2019-03-08 17:44:25Processed 5000 grouped works processed.<br>2019-03-08 17:47:32Finishing indexing<br>2019-03-08 17:47:32Calling final commit<br>2019-03-08 17:47:33Finished Reindex for aspen.demo',1552092453,9878,0),(81,1552111759,NULL,'<br>2019-03-08 23:09:19Initialized Reindex <br>2019-03-08 23:09:19Performing full reindex<br>2019-03-08 23:09:19Setting up update server and solr server<br>2019-03-08 23:09:21Starting to process 9878 grouped works',1552111761,0,0),(82,1552111849,NULL,'<br>2019-03-08 23:10:49Initialized Reindex <br>2019-03-08 23:10:49Performing full reindex<br>2019-03-08 23:10:49Setting up update server and solr server',1552111849,0,0),(83,1552111888,NULL,'<br>2019-03-08 23:11:28Initialized Reindex <br>2019-03-08 23:11:28Performing full reindex<br>2019-03-08 23:11:28Setting up update server and solr server<br>2019-03-08 23:11:33Starting to process 9878 grouped works',1552111893,0,0),(84,1552111946,1552112321,'<br>2019-03-08 23:12:26Initialized Reindex <br>2019-03-08 23:12:26Performing full reindex<br>2019-03-08 23:12:26Setting up update server and solr server<br>2019-03-08 23:12:28Starting to process 9878 grouped works<br>2019-03-08 23:15:33Processed 5000 grouped works processed.<br>2019-03-08 23:18:40Finishing indexing<br>2019-03-08 23:18:40Calling final commit<br>2019-03-08 23:18:41Finished Reindex for aspen.demo',1552112321,9878,0),(85,1552112645,1552112646,'<br>2019-03-08 23:24:05Initialized Reindex <br>2019-03-08 23:24:05Performing full reindex<br>2019-03-08 23:24:05Setting up update server and solr server<br>2019-03-08 23:24:06Starting to process 0 grouped works<br>2019-03-08 23:24:06Finishing indexing<br>2019-03-08 23:24:06Calling final commit<br>2019-03-08 23:24:06Finished Reindex for aspen.demo',1552112646,0,0),(86,1552314473,1552314475,'<br>2019-03-11 08:27:53Initialized Reindex <br>2019-03-11 08:27:53Performing full reindex<br>2019-03-11 08:27:53Setting up update server and solr server<br>2019-03-11 08:27:55Finished Reindex for aspen.demo',1552314475,0,0),(87,1552400213,1552400214,'<br>2019-03-12 08:16:53Initialized Reindex <br>2019-03-12 08:16:53Performing full reindex<br>2019-03-12 08:16:53Setting up update server and solr server<br>2019-03-12 08:16:54Error processing reindex java.lang.NoClassDefFoundError: org/marc4j/MarcReader<br>2019-03-12 08:16:54Finished Reindex for aspen.demo',1552400214,0,0),(88,1552400391,1552400466,'<br>2019-03-12 08:19:51Initialized Reindex <br>2019-03-12 08:19:51Performing full reindex<br>2019-03-12 08:19:51Setting up update server and solr server<br>2019-03-12 08:19:53Starting to process 155321 grouped works<br>2019-03-12 08:20:18Processed 5000 grouped works processed.<br>2019-03-12 08:20:35Processed 10000 grouped works processed.<br>2019-03-12 08:20:50Processed 15000 grouped works processed.<br>2019-03-12 08:21:05Processed 20000 grouped works processed.<br>2019-03-12 08:21:05Finishing indexing<br>2019-03-12 08:21:05Calling final commit<br>2019-03-12 08:21:06Finished Reindex for aspen.demo',1552400466,20000,0);
/*!40000 ALTER TABLE `reindex_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `roleId` int(11) NOT NULL,
  `permissionId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roleId_2` (`roleId`,`permissionId`),
  KEY `roleId` (`roleId`)
) ENGINE=InnoDB AUTO_INCREMENT=712 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=73;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (597,1,149),(596,1,150),(601,1,226),(561,2,148),(581,2,151),(566,2,152),(568,2,153),(595,2,154),(589,2,155),(588,2,156),(550,2,157),(546,2,159),(547,2,161),(548,2,163),(556,2,166),(564,2,167),(541,2,168),(573,2,169),(578,2,173),(558,2,174),(584,2,175),(579,2,176),(582,2,177),(576,2,178),(585,2,179),(586,2,180),(587,2,181),(574,2,182),(590,2,183),(591,2,184),(557,2,185),(570,2,186),(560,2,187),(593,2,188),(551,2,189),(552,2,190),(553,2,191),(555,2,192),(563,2,193),(594,2,194),(565,2,195),(567,2,196),(544,2,197),(545,2,199),(542,2,201),(543,2,203),(549,2,205),(580,2,207),(569,2,208),(572,2,209),(592,2,210),(562,2,211),(559,2,212),(571,2,213),(583,2,216),(554,2,217),(577,2,218),(575,2,219),(604,2,227),(609,2,232),(610,2,233),(611,2,234),(613,2,236),(614,2,238),(615,2,240),(616,2,242),(617,2,244),(618,2,246),(619,2,248),(620,2,249),(636,2,250),(637,2,252),(638,2,253),(642,2,254),(643,2,255),(644,2,256),(645,2,257),(646,2,258),(647,2,259),(648,2,260),(649,2,261),(650,2,262),(705,2,263),(651,2,264),(652,2,265),(653,2,266),(654,2,267),(703,2,268),(655,2,269),(656,2,270),(657,2,271),(658,2,272),(681,2,273),(682,2,274),(683,2,275),(684,2,276),(685,2,277),(686,2,278),(687,2,279),(688,2,280),(707,2,281),(708,2,282),(709,2,283),(710,2,284),(711,2,285),(512,3,217),(506,5,155),(500,5,176),(499,5,178),(501,5,179),(502,5,180),(503,5,181),(498,5,182),(504,5,183),(505,5,184),(497,5,209),(639,5,253),(522,6,158),(520,6,160),(514,6,162),(513,6,164),(515,6,165),(523,6,169),(526,6,188),(518,6,198),(519,6,200),(516,6,202),(517,6,204),(521,6,206),(525,6,210),(524,6,216),(612,6,235),(659,6,255),(660,6,256),(661,6,257),(662,6,258),(663,6,259),(664,6,260),(665,6,261),(666,6,262),(706,6,263),(667,6,264),(668,6,265),(669,6,266),(670,6,267),(704,6,268),(671,6,269),(672,6,270),(673,6,271),(674,6,272),(689,6,274),(690,6,275),(691,6,276),(692,6,277),(693,6,278),(694,6,279),(695,6,280),(508,7,202),(509,7,204),(510,7,206),(511,7,210),(534,8,170),(533,8,171),(535,8,172),(605,9,228),(606,9,229),(607,9,230),(608,9,231),(528,10,162),(527,10,164),(531,10,169),(529,10,202),(530,10,204),(532,10,210),(675,10,256),(676,10,258),(677,10,261),(678,10,262),(679,10,271),(680,10,272),(696,10,275),(697,10,277),(698,10,278),(699,10,279),(537,11,165),(540,11,169),(538,11,202),(539,11,204),(700,11,275),(701,11,278),(702,11,279),(507,12,188),(536,13,218),(641,13,253),(598,15,220),(599,16,222),(600,17,224),(621,19,236),(622,19,238),(623,19,240),(624,19,242),(625,19,244),(626,19,246),(627,19,248),(628,19,249),(629,20,237),(630,20,239),(631,20,241),(632,20,243),(633,20,245),(634,20,247),(635,20,248),(640,21,253);
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `roleId` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'The internal name of the role',
  `description` varchar(100) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'A description of what the role allows',
  PRIMARY KEY (`roleId`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='A role identifying what the user can do.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'userAdmin','Allows administration of users.'),(2,'opacAdmin','Allows administration of the opac display (libraries, locations, etc).'),(3,'genealogyContributor','Allows Genealogy data to be entered  by the user.'),(5,'cataloging','Allows user to perform cataloging activities.'),(6,'libraryAdmin','Allows user to update library configuration for their library system only for their home location.'),(7,'contentEditor','Allows entering of editorial reviews and creation of widgets.'),(8,'library_material_requests','Allows user to manage material requests for a specific library.'),(9,'locationReports','Allows the user to view reports for their location.'),(10,'libraryManager','Allows user to do basic configuration for their library.'),(11,'locationManager','Allows user to do basic configuration for their location.'),(12,'circulationReports','Allows user to view offline circulation reports.'),(13,'listPublisher','Optionally only include lists from people with this role in search results.'),(14,'archives','Control overall archives integration.'),(15,'Masquerader','Allows the user to masquerade as any other user.'),(16,'Library Masquerader','Allows the user to masquerade as patrons of their home library only.'),(17,'Location Masquerader','Allows the user to masquerade as patrons of their home location only.'),(18,'translator','Allows the user to translate the system.'),(19,'Web Admin','Allows the user to administer web content for all libraries'),(20,'Library Web Admin','Allows the user to administer web content for their library'),(21,'superCataloger','Allows user to perform cataloging activities that require advanced knowledge.');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rosen_levelup_settings`
--

DROP TABLE IF EXISTS `rosen_levelup_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rosen_levelup_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lu_api_host` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `lu_api_pw` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `lu_api_un` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `lu_district_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `lu_eligible_ptypes` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `lu_multi_district_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `lu_school_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `lu_ptypes_1` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lu_ptypes_2` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lu_ptypes_k` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lu_location_code_prefix` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=16384;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rosen_levelup_settings`
--

LOCK TABLES `rosen_levelup_settings` WRITE;
/*!40000 ALTER TABLE `rosen_levelup_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `rosen_levelup_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `scope`
--

DROP TABLE IF EXISTS `scope`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scope` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `isLibraryScope` tinyint(1) DEFAULT NULL,
  `isLocationScope` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `name_2` (`name`,`isLibraryScope`,`isLocationScope`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scope`
--

LOCK TABLES `scope` WRITE;
/*!40000 ALTER TABLE `scope` DISABLE KEYS */;
/*!40000 ALTER TABLE `scope` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `search`
--

DROP TABLE IF EXISTS `search`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `search` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `session_id` varchar(128) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `folder_id` int(11) DEFAULT NULL,
  `created` date NOT NULL,
  `title` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `saved` int(1) NOT NULL DEFAULT '0',
  `search_object` blob,
  `searchSource` varchar(30) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'local',
  `searchUrl` varchar(2500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `folder_id` (`folder_id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=270 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `search`
--

LOCK TABLES `search` WRITE;
/*!40000 ALTER TABLE `search` DISABLE KEYS */;
INSERT INTO `search` VALUES (24,0,'15a0iteicupsqht9foj95gv7rg',NULL,'2019-01-29',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:7:\"Keyword\";s:1:\"l\";s:12:\"harry potter\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1548814158.283122;s:1:\"s\";d:3.477864980697632;s:1:\"r\";i:4;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:12:\"harry potter\";}','local',NULL),(32,0,'15a0iteicupsqht9foj95gv7rg',NULL,'2019-02-02',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:7:\"Keyword\";s:1:\"l\";s:0:\"\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1549138643.795965;s:1:\"s\";d:0.12243103981018066;s:1:\"r\";i:0;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:3:\"*:*\";}','local',NULL),(87,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-02-19',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:5:\"Title\";s:1:\"l\";s:5:\"romeo\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550620516.631761;s:1:\"s\";d:15.439323902130127;s:1:\"r\";i:9985;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:5:\"romeo\";}','local',NULL),(89,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-02-20',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:6:\"Author\";s:1:\"l\";s:5:\"romeo\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550674668.116076;s:1:\"s\";d:0.1446080207824707;s:1:\"r\";i:0;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:5:\"romeo\";}','local',NULL),(90,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-02-20',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:7:\"Keyword\";s:1:\"l\";s:5:\"romeo\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550674673.360527;s:1:\"s\";d:0.04573988914489746;s:1:\"r\";i:0;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:5:\"romeo\";}','local',NULL),(110,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-02-21',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:7:\"Keyword\";s:1:\"l\";s:5:\"homer\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550797116.540447;s:1:\"s\";d:0.5280089378356934;s:1:\"r\";i:20;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:5:\"homer\";}','local',NULL),(111,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-02-21',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:5:\"Title\";s:1:\"l\";s:5:\"homer\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550797142.103061;s:1:\"s\";d:0.06396985054016113;s:1:\"r\";i:7;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:5:\"homer\";}','local',NULL),(112,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-02-21',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:12:\"StartOfTitle\";s:1:\"l\";s:5:\"homer\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550797154.283331;s:1:\"s\";d:0.10817503929138184;s:1:\"r\";i:0;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:5:\"title\";s:1:\"q\";s:5:\"homer\";}','local',NULL),(116,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-02-21',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:12:\"StartOfTitle\";s:1:\"l\";s:6:\"illiad\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550797229.982069;s:1:\"s\";d:0.0321049690246582;s:1:\"r\";i:0;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:5:\"title\";s:1:\"q\";s:6:\"illiad\";}','local',NULL),(117,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-02-21',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:5:\"Title\";s:1:\"l\";s:6:\"illiad\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550797240.407127;s:1:\"s\";d:0.028401851654052734;s:1:\"r\";i:0;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:6:\"illiad\";}','local',NULL),(118,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-02-21',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:5:\"Title\";s:1:\"l\";s:5:\"iliad\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550797244.850023;s:1:\"s\";d:0.048002004623413086;s:1:\"r\";i:4;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:5:\"iliad\";}','local',NULL),(119,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-02-21',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:12:\"StartOfTitle\";s:1:\"l\";s:5:\"iliad\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550797258.86556;s:1:\"s\";d:0.0571749210357666;s:1:\"r\";i:0;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:5:\"title\";s:1:\"q\";s:5:\"iliad\";}','local',NULL),(123,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-02-21',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:12:\"StartOfTitle\";s:1:\"l\";s:7:\"the ili\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550798413.965185;s:1:\"s\";d:0.04087400436401367;s:1:\"r\";i:0;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:5:\"title\";s:1:\"q\";s:7:\"the ili\";}','local',NULL),(125,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-02-21',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:12:\"StartOfTitle\";s:1:\"l\";s:9:\"the iliad\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550798422.235666;s:1:\"s\";d:0.05624079704284668;s:1:\"r\";i:1;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:5:\"title\";s:1:\"q\";s:9:\"the iliad\";}','local',NULL),(126,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-02-21',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:12:\"StartOfTitle\";s:1:\"l\";s:3:\"the\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550798426.426113;s:1:\"s\";d:0.026281118392944336;s:1:\"r\";i:0;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:5:\"title\";s:1:\"q\";s:3:\"the\";}','local',NULL),(127,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-02-21',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:6:\"Series\";s:1:\"l\";s:3:\"the\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550798431.66275;s:1:\"s\";d:0.03441619873046875;s:1:\"r\";i:0;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:3:\"the\";}','local',NULL),(130,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-02-21',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:6:\"Series\";s:1:\"l\";s:11:\"shakespeare\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550798479.21223;s:1:\"s\";d:0.11329793930053711;s:1:\"r\";i:27;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:11:\"shakespeare\";}','local',NULL),(131,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-02-21',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:6:\"Series\";s:1:\"l\";s:5:\"homer\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550798602.798094;s:1:\"s\";d:0.06647801399230957;s:1:\"r\";i:0;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:5:\"homer\";}','local',NULL),(132,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-02-21',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:6:\"Series\";s:1:\"l\";s:5:\"iliad\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550798608.11644;s:1:\"s\";d:0.0326540470123291;s:1:\"r\";i:0;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:5:\"iliad\";}','local',NULL),(133,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-02-21',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:6:\"Series\";s:1:\"l\";s:9:\"the iliad\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550798608.806381;s:1:\"s\";d:0.16213488578796387;s:1:\"r\";i:342;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:9:\"the iliad\";}','local',NULL),(134,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-02-21',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:6:\"Author\";s:1:\"l\";s:5:\"homer\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550798635.6087;s:1:\"s\";d:0.06715703010559082;s:1:\"r\";i:14;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:5:\"homer\";}','local',NULL),(136,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-02-21',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:7:\"Subject\";s:1:\"l\";s:7:\"\"homer\"\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550798711.761779;s:1:\"s\";d:1.2413818836212158;s:1:\"r\";i:0;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:7:\"\"homer\"\";}','local',NULL),(137,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-02-21',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:7:\"Subject\";s:1:\"l\";s:5:\"homer\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550798730.242579;s:1:\"s\";d:0.07391905784606934;s:1:\"r\";i:6;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:5:\"homer\";}','local',NULL),(142,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-02-21',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:7:\"Subject\";s:1:\"l\";s:7:\"\"Homer\"\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550800625.806337;s:1:\"s\";d:0.2696189880371094;s:1:\"r\";i:6;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:7:\"\"Homer\"\";}','local',NULL),(143,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-02-21',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:6:\"Author\";s:1:\"l\";s:7:\"\"Homer\"\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550800632.811716;s:1:\"s\";d:0.15729904174804688;s:1:\"r\";i:13;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:7:\"\"Homer\"\";}','local',NULL),(144,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-02-21',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:6:\"Series\";s:1:\"l\";s:7:\"\"Homer\"\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550800639.398189;s:1:\"s\";d:0.07744407653808594;s:1:\"r\";i:0;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:7:\"\"Homer\"\";}','local',NULL),(145,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-02-21',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:6:\"Series\";s:1:\"l\";s:13:\"\"Shakespeare\"\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550800648.99877;s:1:\"s\";d:0.14383792877197266;s:1:\"r\";i:27;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:13:\"\"Shakespeare\"\";}','local',NULL),(146,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-02-21',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:12:\"StartOfTitle\";s:1:\"l\";s:13:\"\"Shakespeare\"\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550800657.824859;s:1:\"s\";d:0.08131003379821777;s:1:\"r\";i:0;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:5:\"title\";s:1:\"q\";s:13:\"\"Shakespeare\"\";}','local',NULL),(147,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-02-21',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:12:\"StartOfTitle\";s:1:\"l\";s:7:\"\"Homer\"\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550800662.818521;s:1:\"s\";d:0.03925299644470215;s:1:\"r\";i:0;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:5:\"title\";s:1:\"q\";s:7:\"\"Homer\"\";}','local',NULL),(148,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-02-21',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:12:\"StartOfTitle\";s:1:\"l\";s:11:\"\"The Iliad\"\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550800672.701086;s:1:\"s\";d:0.059870004653930664;s:1:\"r\";i:1;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:5:\"title\";s:1:\"q\";s:11:\"\"The Iliad\"\";}','local',NULL),(149,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-02-21',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:12:\"StartOfTitle\";s:1:\"l\";s:5:\"\"The\"\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550800679.535652;s:1:\"s\";d:0.04286503791809082;s:1:\"r\";i:0;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:5:\"title\";s:1:\"q\";s:5:\"\"The\"\";}','local',NULL),(150,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-02-21',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:5:\"Title\";s:1:\"l\";s:7:\"\"Iliad\"\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550800689.513655;s:1:\"s\";d:0.12012887001037598;s:1:\"r\";i:3;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:7:\"\"Iliad\"\";}','local',NULL),(153,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-02-21',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:7:\"Keyword\";s:1:\"l\";s:7:\"\"Iliad\"\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550800838.876672;s:1:\"s\";d:0.10065579414367676;s:1:\"r\";i:6;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:7:\"\"Iliad\"\";}','local',NULL),(175,0,'b9qljrsshsrvausmgi095vh3ta',NULL,'2019-02-23',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:7:\"Keyword\";s:1:\"l\";s:0:\"\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550947965.186119;s:1:\"s\";d:0.25403380393981934;s:1:\"r\";i:9986;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:3:\"*:*\";}','local',NULL),(178,0,'bdbrkmqhji44tr6fk29rlth8rp',NULL,'2019-02-23',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:7:\"Keyword\";s:1:\"l\";s:0:\"\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1550948264.769736;s:1:\"s\";d:0.04388999938964844;s:1:\"r\";i:9986;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:3:\"*:*\";}','local',NULL),(192,0,'l9hb350fm246tbcslpfjknbj2u',NULL,'2019-02-24',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:7:\"Keyword\";s:1:\"l\";s:0:\"\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1551036302.04892;s:1:\"s\";d:0.08478999137878418;s:1:\"r\";i:9986;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:3:\"*:*\";}','local',NULL),(197,0,'dokle8ivvhtj70ncpmjo9gba0t',NULL,'2019-02-24',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:7:\"Keyword\";s:1:\"l\";s:0:\"\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1551036610.44542;s:1:\"s\";d:0.08244609832763672;s:1:\"r\";i:9986;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:3:\"*:*\";}','local',NULL),(201,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-03-06',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:7:\"Keyword\";s:1:\"l\";s:36:\"90e89a24-b8e6-450d-f08b-dda049792174\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1551936770.064852;s:1:\"s\";d:0.7271630764007568;s:1:\"r\";i:0;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:36:\"90e89a24-b8e6-450d-f08b-dda049792174\";}','local',NULL),(202,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-03-06',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:7:\"Keyword\";s:1:\"l\";s:0:\"\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1551937107.306955;s:1:\"s\";d:0.13942599296569824;s:1:\"r\";i:392;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:3:\"*:*\";}','local',NULL),(205,0,'29dp4di3bnpp5u9aibgfq2ulls',NULL,'2019-03-06',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:7:\"Keyword\";s:1:\"l\";s:0:\"\";}}s:1:\"f\";a:1:{s:23:\"format_category_catalog\";a:1:{i:0;s:5:\"eBook\";}}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1551937141.722734;s:1:\"s\";d:0.03962206840515137;s:1:\"r\";i:397;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:3:\"*:*\";}','local',NULL),(212,0,'0pg6ru69qj3vv6urf751kgk2gr',NULL,'2019-03-07',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:7:\"Keyword\";s:1:\"l\";s:0:\"\";}}s:1:\"f\";a:1:{s:23:\"format_category_catalog\";a:1:{i:0;s:5:\"eBook\";}}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1552024684.14561;s:1:\"s\";d:0.14581298828125;s:1:\"r\";i:397;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:3:\"*:*\";}','local',NULL),(215,0,'0pg6ru69qj3vv6urf751kgk2gr',NULL,'2019-03-08',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:7:\"Keyword\";s:1:\"l\";s:0:\"\";}}s:1:\"f\";a:1:{s:15:\"target_audience\";a:1:{i:0;s:8:\"Juvenile\";}}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1552063054.912645;s:1:\"s\";d:0.17212200164794922;s:1:\"r\";i:47;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:3:\"*:*\";}','local',NULL),(217,0,'0pg6ru69qj3vv6urf751kgk2gr',NULL,'2019-03-08',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:7:\"Keyword\";s:1:\"l\";s:0:\"\";}}s:1:\"f\";a:1:{s:14:\"format_catalog\";a:1:{i:0;s:5:\"eBook\";}}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1552063076.961359;s:1:\"s\";d:0.09910988807678223;s:1:\"r\";i:2;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:3:\"*:*\";}','local',NULL),(220,0,'0pg6ru69qj3vv6urf751kgk2gr',NULL,'2019-03-08',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:7:\"Keyword\";s:1:\"l\";s:0:\"\";}}s:1:\"f\";a:1:{s:11:\"publishDate\";a:1:{i:0;s:11:\"[2018 TO *]\";}}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1552063584.337049;s:1:\"s\";d:0.1046590805053711;s:1:\"r\";i:41;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:3:\"*:*\";}','local',NULL),(221,0,'0pg6ru69qj3vv6urf751kgk2gr',NULL,'2019-03-08',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:7:\"Keyword\";s:1:\"l\";s:0:\"\";}}s:1:\"f\";a:1:{s:11:\"publishDate\";a:1:{i:0;s:11:\"[2014 TO *]\";}}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1552063594.499424;s:1:\"s\";d:0.10897088050842285;s:1:\"r\";i:195;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:3:\"*:*\";}','local',NULL),(222,0,'0pg6ru69qj3vv6urf751kgk2gr',NULL,'2019-03-08',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:7:\"Keyword\";s:1:\"l\";s:0:\"\";}}s:1:\"f\";a:2:{s:11:\"publishDate\";a:1:{i:0;s:11:\"[2014 TO *]\";}s:30:\"local_time_since_added_catalog\";a:1:{i:0;s:4:\"Week\";}}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1552063604.051774;s:1:\"s\";d:0.12235116958618164;s:1:\"r\";i:7;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:3:\"*:*\";}','local',NULL),(223,0,'0pg6ru69qj3vv6urf751kgk2gr',NULL,'2019-03-08',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:7:\"Keyword\";s:1:\"l\";s:0:\"\";}}s:1:\"f\";a:1:{s:30:\"local_time_since_added_catalog\";a:1:{i:0;s:4:\"Week\";}}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1552063610.792677;s:1:\"s\";d:0.10439896583557129;s:1:\"r\";i:7;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:3:\"*:*\";}','local',NULL),(255,0,'0pg6ru69qj3vv6urf751kgk2gr',NULL,'2019-03-12',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:7:\"Keyword\";s:1:\"l\";s:0:\"\";}}s:1:\"f\";a:1:{s:13:\"literary_form\";a:1:{i:0;s:7:\"Fiction\";}}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1552400512.651433;s:1:\"s\";d:0.10040092468261719;s:1:\"r\";i:346;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:20:\"days_since_added asc\";s:1:\"q\";s:3:\"*:*\";}','local',NULL),(256,0,'0pg6ru69qj3vv6urf751kgk2gr',NULL,'2019-03-12',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:7:\"Keyword\";s:1:\"l\";s:0:\"\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1552401034.045921;s:1:\"s\";d:0.03915691375732422;s:1:\"r\";i:387;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:20:\"days_since_added asc\";s:1:\"q\";s:3:\"*:*\";}','local',NULL),(257,0,'0pg6ru69qj3vv6urf751kgk2gr',NULL,'2019-03-12',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:7:\"Keyword\";s:1:\"l\";s:0:\"\";}}s:1:\"f\";a:1:{s:13:\"literary_form\";a:1:{i:0;s:11:\"Non Fiction\";}}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1552401039.870433;s:1:\"s\";d:0.12047290802001953;s:1:\"r\";i:38;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:20:\"days_since_added asc\";s:1:\"q\";s:3:\"*:*\";}','local',NULL),(260,0,'0pg6ru69qj3vv6urf751kgk2gr',NULL,'2019-03-12',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:7:\"Keyword\";s:1:\"l\";s:0:\"\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1552404030.520224;s:1:\"s\";d:0.03775906562805176;s:1:\"r\";i:387;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:3:\"*:*\";}','local',NULL),(261,0,'0pg6ru69qj3vv6urf751kgk2gr',NULL,'2019-03-12',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:7:\"Keyword\";s:1:\"l\";s:0:\"\";}}s:1:\"f\";a:1:{s:13:\"literary_form\";a:1:{i:0;s:7:\"Fiction\";}}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";N;s:1:\"i\";d:1552404038.027829;s:1:\"s\";d:0.02784895896911621;s:1:\"r\";i:346;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:9:\"relevance\";s:1:\"q\";s:3:\"*:*\";}','local',NULL),(267,0,'0pg6ru69qj3vv6urf751kgk2gr',NULL,'2019-03-12',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:7:\"Keyword\";s:1:\"l\";s:0:\"\";}}s:1:\"f\";a:1:{s:13:\"literary_form\";a:1:{i:0;s:7:\"Fiction\";}}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";s:3:\"267\";s:1:\"i\";d:1552406961.484465;s:1:\"s\";d:0.09313297271728516;s:1:\"r\";i:346;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:19:\"year desc,title asc\";s:1:\"q\";s:3:\"*:*\";}','local',NULL),(268,0,'0pg6ru69qj3vv6urf751kgk2gr',NULL,'2019-03-12',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:7:\"Keyword\";s:1:\"l\";s:0:\"\";}}s:1:\"f\";a:0:{}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";s:3:\"268\";s:1:\"i\";d:1552407212.952598;s:1:\"s\";d:0.11974310874938965;s:1:\"r\";i:387;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:19:\"year desc,title asc\";s:1:\"q\";s:3:\"*:*\";}','local',NULL),(269,0,'0pg6ru69qj3vv6urf751kgk2gr',NULL,'2019-03-12',NULL,0,_binary 'O:5:\"minSO\":11:{s:1:\"t\";a:1:{i:0;a:2:{s:1:\"i\";s:7:\"Keyword\";s:1:\"l\";s:0:\"\";}}s:1:\"f\";a:1:{s:13:\"literary_form\";a:1:{i:0;s:11:\"Non Fiction\";}}s:2:\"hf\";a:0:{}s:2:\"fc\";a:0:{}s:2:\"id\";s:3:\"269\";s:1:\"i\";d:1552407240.177459;s:1:\"s\";d:0.13248419761657715;s:1:\"r\";i:38;s:2:\"ty\";s:5:\"basic\";s:2:\"sr\";s:19:\"year desc,title asc\";s:1:\"q\";s:3:\"*:*\";}','local',NULL);
/*!40000 ALTER TABLE `search` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `search_stats_new`
--

DROP TABLE IF EXISTS `search_stats_new`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `search_stats_new` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The unique id of the search statistic',
  `phrase` varchar(500) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'The phrase being searched for',
  `lastSearch` int(16) NOT NULL COMMENT 'The last time this search was done',
  `numSearches` int(16) NOT NULL COMMENT 'The number of times this search has been done.',
  PRIMARY KEY (`id`),
  KEY `numSearches` (`numSearches`),
  KEY `lastSearch` (`lastSearch`),
  FULLTEXT KEY `phrase_text` (`phrase`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Statistical information about searches for use in reporting ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `search_stats_new`
--

LOCK TABLES `search_stats_new` WRITE;
/*!40000 ALTER TABLE `search_stats_new` DISABLE KEYS */;
INSERT INTO `search_stats_new` VALUES (1,'',1552407243,119),(2,'harry potter',1548814163,1),(3,'romeo',1550620533,4),(4,'homer',1550798730,14),(5,'iliad',1550797245,1),(6,'the iliad',1550798611,5),(7,'shakespeare',1550798482,1),(8,'\"homer\"',1550800634,2),(9,'\"shakespeare\"',1550800652,1),(10,'\"the iliad\"',1550800673,1),(11,'\"iliad\"',1550800840,4);
/*!40000 ALTER TABLE `search_stats_new` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sendgrid_settings`
--

DROP TABLE IF EXISTS `sendgrid_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sendgrid_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fromAddress` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `replyToAddress` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `apiKey` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=16384;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sendgrid_settings`
--

LOCK TABLES `sendgrid_settings` WRITE;
/*!40000 ALTER TABLE `sendgrid_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `sendgrid_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `session`
--

DROP TABLE IF EXISTS `session`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `session` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(128) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `data` longtext COLLATE utf8mb4_general_ci,
  `last_used` int(12) NOT NULL DEFAULT '0',
  `created` datetime DEFAULT CURRENT_TIMESTAMP,
  `remember_me` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether or not the session was started with remember me on.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_id` (`session_id`),
  KEY `last_used` (`last_used`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `session`
--

LOCK TABLES `session` WRITE;
/*!40000 ALTER TABLE `session` DISABLE KEYS */;
/*!40000 ALTER TABLE `session` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sideload_files`
--

DROP TABLE IF EXISTS `sideload_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sideload_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sideLoadId` int(11) NOT NULL,
  `filename` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lastChanged` int(11) DEFAULT '0',
  `deletedTime` int(11) DEFAULT '0',
  `lastIndexed` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `sideloadFile` (`sideLoadId`,`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=8192;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sideload_files`
--

LOCK TABLES `sideload_files` WRITE;
/*!40000 ALTER TABLE `sideload_files` DISABLE KEYS */;
/*!40000 ALTER TABLE `sideload_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sideload_log`
--

DROP TABLE IF EXISTS `sideload_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sideload_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `notes` mediumtext COLLATE utf8mb4_general_ci COMMENT 'Additional information about the run',
  `numSideLoadsUpdated` int(11) DEFAULT '0',
  `sideLoadsUpdated` longtext COLLATE utf8mb4_general_ci,
  `numProducts` int(11) DEFAULT '0',
  `numErrors` int(11) DEFAULT '0',
  `numAdded` int(11) DEFAULT '0',
  `numDeleted` int(11) DEFAULT '0',
  `numUpdated` int(11) DEFAULT '0',
  `numSkipped` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sideload_log`
--

LOCK TABLES `sideload_log` WRITE;
/*!40000 ALTER TABLE `sideload_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `sideload_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sideload_record_usage`
--

DROP TABLE IF EXISTS `sideload_record_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sideload_record_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sideloadId` int(11) NOT NULL,
  `recordId` varchar(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `timesUsed` int(11) NOT NULL,
  `instance` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`sideloadId`,`recordId`,`year`,`month`),
  UNIQUE KEY `instance_2` (`instance`,`sideloadId`,`recordId`,`year`,`month`),
  KEY `year` (`year`,`month`),
  KEY `year_2` (`year`,`month`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=5461;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sideload_record_usage`
--

LOCK TABLES `sideload_record_usage` WRITE;
/*!40000 ALTER TABLE `sideload_record_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `sideload_record_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sideload_scopes`
--

DROP TABLE IF EXISTS `sideload_scopes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sideload_scopes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `sideLoadId` int(11) NOT NULL,
  `restrictToChildrensMaterial` tinyint(4) DEFAULT '0',
  `marcTagToMatch` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `marcValueToMatch` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `includeExcludeMatches` tinyint(4) DEFAULT '1',
  `urlToMatch` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `urlReplacement` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=16384;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sideload_scopes`
--

LOCK TABLES `sideload_scopes` WRITE;
/*!40000 ALTER TABLE `sideload_scopes` DISABLE KEYS */;
/*!40000 ALTER TABLE `sideload_scopes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sideloads`
--

DROP TABLE IF EXISTS `sideloads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sideloads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `marcPath` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `filenamesToInclude` varchar(250) COLLATE utf8mb4_general_ci DEFAULT '.*\\.ma?rc',
  `marcEncoding` enum('MARC8','UTF8','UNIMARC','ISO8859_1','BESTGUESS') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'MARC8',
  `individualMarcPath` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `numCharsToCreateFolderFrom` int(11) DEFAULT '4',
  `createFolderFromLeadingCharacters` tinyint(1) DEFAULT '0',
  `groupingClass` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'SideLoadedRecordGrouper',
  `indexingClass` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'SideLoadedEContentProcessor',
  `recordDriver` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'SideLoadedRecord',
  `recordUrlComponent` varchar(25) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'DefineThis',
  `recordNumberTag` char(3) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '001',
  `recordNumberSubfield` char(1) COLLATE utf8mb4_general_ci DEFAULT 'a',
  `recordNumberPrefix` varchar(10) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `suppressItemlessBibs` tinyint(1) NOT NULL DEFAULT '1',
  `itemTag` char(3) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `itemRecordNumber` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `location` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `locationsToSuppress` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `itemUrl` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `format` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `formatSource` enum('bib','item','specified') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'bib',
  `specifiedFormat` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `specifiedFormatCategory` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `specifiedFormatBoost` int(11) DEFAULT NULL,
  `runFullUpdate` tinyint(1) DEFAULT '0',
  `lastUpdateOfChangedRecords` int(11) DEFAULT '0',
  `lastUpdateOfAllRecords` int(11) DEFAULT '0',
  `treatUnknownLanguageAs` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'English',
  `treatUndeterminedLanguageAs` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'English',
  `deletedRecordsIds` longtext COLLATE utf8mb4_general_ci,
  `accessButtonLabel` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Access Online',
  `showStatus` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=16384;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sideloads`
--

LOCK TABLES `sideloads` WRITE;
/*!40000 ALTER TABLE `sideloads` DISABLE KEYS */;
/*!40000 ALTER TABLE `sideloads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sierra_api_export_log`
--

DROP TABLE IF EXISTS `sierra_api_export_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sierra_api_export_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `notes` mediumtext COLLATE utf8mb4_general_ci COMMENT 'Additional information about the run',
  `numRecordsToProcess` int(11) DEFAULT NULL,
  `numRecordsProcessed` int(11) DEFAULT NULL,
  `numErrors` int(11) DEFAULT NULL,
  `numRemainingRecords` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sierra_api_export_log`
--

LOCK TABLES `sierra_api_export_log` WRITE;
/*!40000 ALTER TABLE `sierra_api_export_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `sierra_api_export_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sierra_export_field_mapping`
--

DROP TABLE IF EXISTS `sierra_export_field_mapping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sierra_export_field_mapping` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of field mapping',
  `indexingProfileId` int(11) NOT NULL COMMENT 'The indexing profile this field mapping is associated with',
  `fixedFieldDestinationField` char(3) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'The field to place fixed field data into',
  `bcode3DestinationSubfield` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'The subfield to place bcode3 into',
  `callNumberExportFieldTag` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `callNumberPrestampExportSubfield` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `callNumberExportSubfield` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `callNumberCutterExportSubfield` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `callNumberPoststampExportSubfield` char(5) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `volumeExportFieldTag` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `urlExportFieldTag` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `eContentExportFieldTag` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `materialTypeSubfield` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bibLevelLocationsSubfield` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sierra_export_field_mapping`
--

LOCK TABLES `sierra_export_field_mapping` WRITE;
/*!40000 ALTER TABLE `sierra_export_field_mapping` DISABLE KEYS */;
/*!40000 ALTER TABLE `sierra_export_field_mapping` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `slow_ajax_request`
--

DROP TABLE IF EXISTS `slow_ajax_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `slow_ajax_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `module` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `action` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `method` varchar(75) COLLATE utf8mb4_general_ci NOT NULL,
  `timesSlow` int(11) DEFAULT '0',
  `timesFast` int(11) DEFAULT NULL,
  `timesAcceptable` int(11) DEFAULT NULL,
  `timesSlower` int(11) DEFAULT NULL,
  `timesVerySlow` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `year` (`year`,`month`,`module`,`action`,`method`),
  KEY `year_2` (`year`,`month`,`module`,`action`,`method`)
) ENGINE=InnoDB AUTO_INCREMENT=901 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=112;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `slow_ajax_request`
--

LOCK TABLES `slow_ajax_request` WRITE;
/*!40000 ALTER TABLE `slow_ajax_request` DISABLE KEYS */;
INSERT INTO `slow_ajax_request` VALUES (899,2021,10,'MyAccount','AJAX','getLoginForm',0,1,NULL,NULL,NULL),(900,2021,10,'AJAX','JSON','loginUser',0,2,NULL,NULL,NULL);
/*!40000 ALTER TABLE `slow_ajax_request` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `slow_page`
--

DROP TABLE IF EXISTS `slow_page`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `slow_page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `module` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `action` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `timesSlow` int(11) DEFAULT '0',
  `timesFast` int(11) DEFAULT NULL,
  `timesAcceptable` int(11) DEFAULT NULL,
  `timesSlower` int(11) DEFAULT NULL,
  `timesVerySlow` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `year` (`year`,`month`,`module`,`action`),
  KEY `year_2` (`year`,`month`,`module`,`action`)
) ENGINE=InnoDB AUTO_INCREMENT=869 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=97;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `slow_page`
--

LOCK TABLES `slow_page` WRITE;
/*!40000 ALTER TABLE `slow_page` DISABLE KEYS */;
INSERT INTO `slow_page` VALUES (866,2021,10,'Error','Handle404',0,1,NULL,NULL,NULL),(867,2021,10,'Admin','Home',0,1,NULL,NULL,NULL),(868,2021,10,'Admin','DBMaintenance',0,1,NULL,NULL,1);
/*!40000 ALTER TABLE `slow_page` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_members`
--

DROP TABLE IF EXISTS `staff_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(13) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `libraryId` int(11) DEFAULT NULL,
  `photo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_members`
--

LOCK TABLES `staff_members` WRITE;
/*!40000 ALTER TABLE `staff_members` DISABLE KEYS */;
/*!40000 ALTER TABLE `staff_members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `status_map_values`
--

DROP TABLE IF EXISTS `status_map_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `status_map_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `indexingProfileId` int(11) NOT NULL,
  `value` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `status` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `groupedStatus` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `suppress` tinyint(1) DEFAULT '0',
  `inLibraryUseOnly` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `indexingProfileId` (`indexingProfileId`,`value`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=1170;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `status_map_values`
--

LOCK TABLES `status_map_values` WRITE;
/*!40000 ALTER TABLE `status_map_values` DISABLE KEYS */;
INSERT INTO `status_map_values` VALUES (15,11,'C','Checked Out','Checked Out',0,0),(16,11,'CT','Charged Temporary','Checked Out',0,0),(17,11,'H','On Hold Shelf','Checked Out',0,0),(18,11,'HP','Hold Pending','Checked Out',0,0),(19,11,'HT','On Hold Shelf','Checked Out',0,0),(20,11,'I','In Transit','In Transit',0,0),(21,11,'IH','In Transit for Hold','Checked Out',0,0),(22,11,'IT','In Transit','In Transit',0,0),(23,11,'L','Lost','Currently Unavailable',0,0),(24,11,'LT','Lost Temporary','Currently Unavailable',0,0),(25,11,'N','Ordered','On Order',0,0),(26,11,'O','On Order','On Order',0,0),(27,11,'R','Received','On Order',0,0),(28,11,'RC','Received','On Order',0,0),(29,11,'RF','Received','On Order',0,0),(30,11,'RG','Received as Gift','On Order',0,0),(31,11,'RP','Received partial','On Order',0,0),(32,11,'RT','Item Hold Temp','Currently Unavailable',0,0),(33,11,'S','On Shelf','On Shelf',0,0),(34,11,'SC','Library Use Only','Library Use Only',0,0),(35,11,'SD','Shelving Delay','Currently Unavailable',0,0),(36,11,'SG','Grubby','Currently Unavailable',0,0),(37,11,'SI','On Display','On Shelf',0,0),(38,11,'SM','Missing','Currently Unavailable',0,0),(39,11,'SO','Empty Case','Currently Unavailable',0,0),(40,11,'SP','In Processing','On Order',0,0),(41,11,'SS','Temporarily Unavailable','Currently Unavailable',0,0),(42,11,'ST','On Shelf Temporary','Currently Unavailable',0,0),(43,11,'SU','In Repair','Currently Unavailable',0,0),(44,11,'SW','Withdrawn','Currently Unavailable',0,0),(45,11,'SX','Not on Shelf','Currently Unavailable',0,0),(46,11,'T','Traced','Currently Unavailable',0,0),(47,11,'TT','Traced Temporary','Currently Unavailable',0,0);
/*!40000 ALTER TABLE `status_map_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `syndetics_data`
--

DROP TABLE IF EXISTS `syndetics_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `syndetics_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupedRecordPermanentId` varchar(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lastDescriptionUpdate` int(11) DEFAULT '0',
  `primaryIsbn` varchar(13) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `primaryUpc` varchar(25) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_general_ci,
  `tableOfContents` longtext COLLATE utf8mb4_general_ci,
  `excerpt` longtext COLLATE utf8mb4_general_ci,
  `lastTableOfContentsUpdate` int(11) DEFAULT '0',
  `lastExcerptUpdate` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `groupedRecordPermanentId` (`groupedRecordPermanentId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `syndetics_data`
--

LOCK TABLES `syndetics_data` WRITE;
/*!40000 ALTER TABLE `syndetics_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `syndetics_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `syndetics_settings`
--

DROP TABLE IF EXISTS `syndetics_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `syndetics_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `syndeticsKey` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `hasSummary` tinyint(1) DEFAULT '1',
  `hasAvSummary` tinyint(1) DEFAULT '0',
  `hasAvProfile` tinyint(1) DEFAULT '0',
  `hasToc` tinyint(1) DEFAULT '1',
  `hasExcerpt` tinyint(1) DEFAULT '1',
  `hasVideoClip` tinyint(1) DEFAULT '0',
  `hasFictionProfile` tinyint(1) DEFAULT '0',
  `hasAuthorNotes` tinyint(1) DEFAULT '0',
  `syndeticsUnbound` tinyint(1) DEFAULT '0',
  `unboundAccountNumber` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=16384;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `syndetics_settings`
--

LOCK TABLES `syndetics_settings` WRITE;
/*!40000 ALTER TABLE `syndetics_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `syndetics_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_message_dismissal`
--

DROP TABLE IF EXISTS `system_message_dismissal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_message_dismissal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `systemMessageId` int(11) DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userPlacard` (`userId`,`systemMessageId`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_message_dismissal`
--

LOCK TABLES `system_message_dismissal` WRITE;
/*!40000 ALTER TABLE `system_message_dismissal` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_message_dismissal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_message_library`
--

DROP TABLE IF EXISTS `system_message_library`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_message_library` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `systemMessageId` int(11) DEFAULT NULL,
  `libraryId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `systemMessageLibrary` (`systemMessageId`,`libraryId`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=3276;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_message_library`
--

LOCK TABLES `system_message_library` WRITE;
/*!40000 ALTER TABLE `system_message_library` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_message_library` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_message_location`
--

DROP TABLE IF EXISTS `system_message_location`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_message_location` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `systemMessageId` int(11) DEFAULT NULL,
  `locationId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `systemMessageLocation` (`systemMessageId`,`locationId`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=819;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_message_location`
--

LOCK TABLES `system_message_location` WRITE;
/*!40000 ALTER TABLE `system_message_location` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_message_location` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_messages`
--

DROP TABLE IF EXISTS `system_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `message` mediumtext COLLATE utf8mb4_general_ci,
  `css` mediumtext COLLATE utf8mb4_general_ci,
  `dismissable` tinyint(1) DEFAULT '0',
  `showOn` int(11) DEFAULT '0',
  `startDate` int(11) DEFAULT '0',
  `endDate` int(11) DEFAULT '0',
  `messageStyle` varchar(10) COLLATE utf8mb4_general_ci DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `title` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=3276;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_messages`
--

LOCK TABLES `system_messages` WRITE;
/*!40000 ALTER TABLE `system_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_variables`
--

DROP TABLE IF EXISTS `system_variables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_variables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `errorEmail` varchar(128) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ticketEmail` varchar(128) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `searchErrorEmail` varchar(128) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `loadCoversFrom020z` tinyint(1) DEFAULT '0',
  `runNightlyFullIndex` tinyint(1) DEFAULT '0',
  `currencyCode` char(3) COLLATE utf8mb4_general_ci DEFAULT 'USD',
  `allowableHtmlTags` varchar(512) COLLATE utf8mb4_general_ci DEFAULT 'p|div|span|a|b|em|strong|i|ul|ol|li|br|h1|h2|h3|h4|h5|h6',
  `allowHtmlInMarkdownFields` tinyint(1) DEFAULT '1',
  `useHtmlEditorRatherThanMarkdown` tinyint(1) DEFAULT '0',
  `storeRecordDetailsInSolr` tinyint(1) DEFAULT '0',
  `storeRecordDetailsInDatabase` tinyint(1) DEFAULT '1',
  `greenhouseUrl` varchar(128) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=16384;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_variables`
--

LOCK TABLES `system_variables` WRITE;
/*!40000 ALTER TABLE `system_variables` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_variables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `themes`
--

DROP TABLE IF EXISTS `themes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `themes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `themeName` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `extendsTheme` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `logoName` varchar(100) COLLATE utf8mb4_general_ci DEFAULT '',
  `headerBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#f1f1f1',
  `headerBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `headerForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#8b8b8b',
  `headerForegroundColorDefault` tinyint(1) DEFAULT '1',
  `generatedCss` longtext COLLATE utf8mb4_general_ci,
  `headerBottomBorderWidth` varchar(6) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `favicon` varchar(100) COLLATE utf8mb4_general_ci DEFAULT '',
  `pageBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#ffffff',
  `pageBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `primaryBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#147ce2',
  `primaryBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `primaryForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#ffffff',
  `primaryForegroundColorDefault` tinyint(1) DEFAULT '1',
  `bodyBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#ffffff',
  `bodyBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `bodyTextColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#6B6B6B',
  `bodyTextColorDefault` tinyint(1) DEFAULT '1',
  `secondaryBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#de9d03',
  `secondaryBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `secondaryForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#ffffff',
  `secondaryForegroundColorDefault` tinyint(1) DEFAULT '1',
  `tertiaryBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#de1f0b',
  `tertiaryBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `tertiaryForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#ffffff',
  `tertiaryForegroundColorDefault` tinyint(1) DEFAULT '1',
  `headingFont` varchar(191) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `headingFontDefault` tinyint(1) DEFAULT '1',
  `bodyFont` varchar(191) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bodyFontDefault` tinyint(1) DEFAULT '1',
  `additionalCss` mediumtext COLLATE utf8mb4_general_ci,
  `additionalCssType` tinyint(1) DEFAULT '0',
  `buttonRadius` varchar(6) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `smallButtonRadius` varchar(6) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `browseCategoryPanelColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#d7dce3',
  `browseCategoryPanelColorDefault` tinyint(1) DEFAULT '1',
  `selectedBrowseCategoryBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#0087AB',
  `selectedBrowseCategoryBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `selectedBrowseCategoryForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#ffffff',
  `selectedBrowseCategoryForegroundColorDefault` tinyint(1) DEFAULT '1',
  `selectedBrowseCategoryBorderColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#0087AB',
  `selectedBrowseCategoryBorderColorDefault` tinyint(1) DEFAULT '1',
  `deselectedBrowseCategoryBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#ffffff',
  `deselectedBrowseCategoryBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `deselectedBrowseCategoryForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#6B6B6B',
  `deselectedBrowseCategoryForegroundColorDefault` tinyint(1) DEFAULT '1',
  `deselectedBrowseCategoryBorderColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#6B6B6B',
  `deselectedBrowseCategoryBorderColorDefault` tinyint(1) DEFAULT '1',
  `menubarHighlightBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#f1f1f1',
  `menubarHighlightBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `menubarHighlightForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#265a87',
  `menubarHighlightForegroundColorDefault` tinyint(1) DEFAULT '1',
  `customHeadingFont` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `customBodyFont` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `capitalizeBrowseCategories` tinyint(1) DEFAULT '-1',
  `defaultButtonBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#ffffff',
  `defaultButtonBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `defaultButtonForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#333333',
  `defaultButtonForegroundColorDefault` tinyint(1) DEFAULT '1',
  `defaultButtonBorderColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#cccccc',
  `defaultButtonBorderColorDefault` tinyint(1) DEFAULT '1',
  `defaultButtonHoverBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#ebebeb',
  `defaultButtonHoverBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `defaultButtonHoverForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#333333',
  `defaultButtonHoverForegroundColorDefault` tinyint(1) DEFAULT '1',
  `defaultButtonHoverBorderColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#adadad',
  `defaultButtonHoverBorderColorDefault` tinyint(1) DEFAULT '1',
  `primaryButtonBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#428bca',
  `primaryButtonBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `primaryButtonForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#ffffff',
  `primaryButtonForegroundColorDefault` tinyint(1) DEFAULT '1',
  `primaryButtonBorderColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#357ebd',
  `primaryButtonBorderColorDefault` tinyint(1) DEFAULT '1',
  `primaryButtonHoverBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#3276b1',
  `primaryButtonHoverBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `primaryButtonHoverForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#ffffff',
  `primaryButtonHoverForegroundColorDefault` tinyint(1) DEFAULT '1',
  `primaryButtonHoverBorderColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#285e8e',
  `primaryButtonHoverBorderColorDefault` tinyint(1) DEFAULT '1',
  `actionButtonBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#428bca',
  `actionButtonBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `actionButtonForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#ffffff',
  `actionButtonForegroundColorDefault` tinyint(1) DEFAULT '1',
  `actionButtonBorderColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#357ebd',
  `actionButtonBorderColorDefault` tinyint(1) DEFAULT '1',
  `actionButtonHoverBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#3276b1',
  `actionButtonHoverBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `actionButtonHoverForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#ffffff',
  `actionButtonHoverForegroundColorDefault` tinyint(1) DEFAULT '1',
  `actionButtonHoverBorderColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#285e8e',
  `actionButtonHoverBorderColorDefault` tinyint(1) DEFAULT '1',
  `infoButtonBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#5bc0de',
  `infoButtonBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `infoButtonForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#ffffff',
  `infoButtonForegroundColorDefault` tinyint(1) DEFAULT '1',
  `infoButtonBorderColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#46b8da',
  `infoButtonBorderColorDefault` tinyint(1) DEFAULT '1',
  `infoButtonHoverBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#39b3d7',
  `infoButtonHoverBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `infoButtonHoverForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#ffffff',
  `infoButtonHoverForegroundColorDefault` tinyint(1) DEFAULT '1',
  `infoButtonHoverBorderColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#269abc',
  `infoButtonHoverBorderColorDefault` tinyint(1) DEFAULT '1',
  `warningButtonBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#f0ad4e',
  `warningButtonBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `warningButtonForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#ffffff',
  `warningButtonForegroundColorDefault` tinyint(1) DEFAULT '1',
  `warningButtonBorderColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#eea236',
  `warningButtonBorderColorDefault` tinyint(1) DEFAULT '1',
  `warningButtonHoverBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#ed9c28',
  `warningButtonHoverBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `warningButtonHoverForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#ffffff',
  `warningButtonHoverForegroundColorDefault` tinyint(1) DEFAULT '1',
  `warningButtonHoverBorderColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#d58512',
  `warningButtonHoverBorderColorDefault` tinyint(1) DEFAULT '1',
  `dangerButtonBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#d9534f',
  `dangerButtonBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `dangerButtonForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#ffffff',
  `dangerButtonForegroundColorDefault` tinyint(1) DEFAULT '1',
  `dangerButtonBorderColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#d43f3a',
  `dangerButtonBorderColorDefault` tinyint(1) DEFAULT '1',
  `dangerButtonHoverBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#d2322d',
  `dangerButtonHoverBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `dangerButtonHoverForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#ffffff',
  `dangerButtonHoverForegroundColorDefault` tinyint(1) DEFAULT '1',
  `dangerButtonHoverBorderColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#ac2925',
  `dangerButtonHoverBorderColorDefault` tinyint(1) DEFAULT '1',
  `editionsButtonBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#f8f9fa',
  `editionsButtonBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `editionsButtonForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#212529',
  `editionsButtonForegroundColorDefault` tinyint(1) DEFAULT '1',
  `editionsButtonBorderColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#999999',
  `editionsButtonBorderColorDefault` tinyint(1) DEFAULT '1',
  `editionsButtonHoverBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#e2e6ea',
  `editionsButtonHoverBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `editionsButtonHoverForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#212529',
  `editionsButtonHoverForegroundColorDefault` tinyint(1) DEFAULT '1',
  `editionsButtonHoverBorderColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#dae0e5',
  `editionsButtonHoverBorderColorDefault` tinyint(1) DEFAULT '1',
  `toolsButtonBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#4F4F4F',
  `toolsButtonBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `toolsButtonForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#ffffff',
  `toolsButtonForegroundColorDefault` tinyint(1) DEFAULT '1',
  `toolsButtonBorderColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#636363',
  `toolsButtonBorderColorDefault` tinyint(1) DEFAULT '1',
  `toolsButtonHoverBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#636363',
  `toolsButtonHoverBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `toolsButtonHoverForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#ffffff',
  `toolsButtonHoverForegroundColorDefault` tinyint(1) DEFAULT '1',
  `toolsButtonHoverBorderColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#636363',
  `toolsButtonHoverBorderColorDefault` tinyint(1) DEFAULT '1',
  `footerBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#ffffff',
  `footerBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `footerForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#6b6b6b',
  `footerForegroundColorDefault` tinyint(1) DEFAULT '1',
  `footerLogo` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `footerLogoLink` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `closedPanelBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#e7e7e7',
  `closedPanelBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `closedPanelForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#333333',
  `closedPanelForegroundColorDefault` tinyint(1) DEFAULT '1',
  `openPanelBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#4DACDE',
  `openPanelBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `openPanelForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#ffffff',
  `openPanelForegroundColorDefault` tinyint(1) DEFAULT '1',
  `panelBodyBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#ffffff',
  `panelBodyBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `panelBodyForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#404040',
  `panelBodyForegroundColorDefault` tinyint(1) DEFAULT '1',
  `linkColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#3174AF',
  `linkColorDefault` tinyint(1) DEFAULT '1',
  `linkHoverColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#265a87',
  `linkHoverColorDefault` tinyint(1) DEFAULT '1',
  `badgeBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#666666',
  `badgeBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `badgeForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#ffffff',
  `badgeForegroundColorDefault` tinyint(1) DEFAULT '1',
  `badgeBorderRadius` varchar(6) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `resultLabelColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#44484a',
  `resultLabelColorDefault` tinyint(1) DEFAULT '1',
  `resultValueColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#6B6B6B',
  `resultValueColorDefault` tinyint(1) DEFAULT '1',
  `breadcrumbsBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#f5f5f5',
  `breadcrumbsBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `breadcrumbsForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#6B6B6B',
  `breadcrumbsForegroundColorDefault` tinyint(1) DEFAULT '1',
  `searchToolsBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#f5f5f5',
  `searchToolsBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `searchToolsBorderColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#e3e3e3',
  `searchToolsBorderColorDefault` tinyint(1) DEFAULT '1',
  `searchToolsForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#6B6B6B',
  `searchToolsForegroundColorDefault` tinyint(1) DEFAULT '1',
  `menubarBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#f1f1f1',
  `menubarBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `menubarForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#303030',
  `menubarForegroundColorDefault` tinyint(1) DEFAULT '1',
  `menuDropdownBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#ededed',
  `menuDropdownBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `menuDropdownForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#404040',
  `menuDropdownForegroundColorDefault` tinyint(1) DEFAULT '1',
  `modalDialogBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#ffffff',
  `modalDialogBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `modalDialogForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#333333',
  `modalDialogForegroundColorDefault` tinyint(1) DEFAULT '1',
  `modalDialogHeaderFooterBackgroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#ffffff',
  `modalDialogHeaderFooterBackgroundColorDefault` tinyint(1) DEFAULT '1',
  `modalDialogHeaderFooterForegroundColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#333333',
  `modalDialogHeaderFooterForegroundColorDefault` tinyint(1) DEFAULT '1',
  `modalDialogHeaderFooterBorderColor` char(7) COLLATE utf8mb4_general_ci DEFAULT '#e5e5e5',
  `modalDialogHeaderFooterBorderColorDefault` tinyint(1) DEFAULT '1',
  `footerLogoAlt` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `themeName` (`themeName`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `themes`
--

LOCK TABLES `themes` WRITE;
/*!40000 ALTER TABLE `themes` DISABLE KEYS */;
INSERT INTO `themes` VALUES (1,'default','','logoNameTL_Logo_final.png','#f1f1f1',1,'#303030',1,'<style type=\"text/css\">h1 small, h2 small, h3 small, h4 small, h5 small{color: #6B6B6B;}#header-wrapper{background-color: #f1f1f1;background-image: none;color: #303030;}#library-name-header{color: #303030;}#footer-container{background-color: #f1f1f1;color: #303030;}body {background-color: #ffffff;color: #6B6B6B;}a,a:visited,.result-head,#selected-browse-label a,#selected-browse-label a:visited{color: #3174AF;}a:hover,.result-head:hover,#selected-browse-label a:hover{color: #265a87;}body .container, #home-page-browse-content{background-color: #ffffff;color: #6B6B6B;}#selected-browse-label{background-color: #ffffff;}.table-striped > tbody > tr:nth-child(2n+1) > td, .table-striped > tbody > tr:nth-child(2n+1) > th{background-color: #fafafa;}.table-sticky thead tr th{background-color: #ffffff;}#home-page-search, #horizontal-search-box,.searchTypeHome,.searchSource,.menu-bar {background-color: #0a7589;color: #ffffff;}#horizontal-menu-bar-container{background-color: #f1f1f1;color: #303030;position: relative;}#horizontal-menu-bar-container, #horizontal-menu-bar-container .menu-icon, #horizontal-menu-bar-container .menu-icon .menu-bar-label,#horizontal-menu-bar-container .menu-icon:visited{background-color: #f1f1f1;color: #303030;}#horizontal-menu-bar-container .menu-icon:hover, #horizontal-menu-bar-container .menu-icon:focus,#horizontal-menu-bar-container .menu-icon:hover .menu-bar-label, #horizontal-menu-bar-container .menu-icon:focus .menu-bar-label,#menuToggleButton.selected{background-color: #f1f1f1;color: #265a87;}#horizontal-search-label,#horizontal-search-box #horizontal-search-label{color: #ffffff;}.dropdownMenu, #account-menu, #header-menu, .dropdown .dropdown-menu.dropdownMenu{background-color: #ededed;color: #404040;}.dropdownMenu a, .dropdownMenu a:visited{color: #404040;}.modal-header, .modal-footer{background-color: #ffffff;color: #333333;}.close, .close:hover, .close:focus{color: #333333;}.modal-header{border-bottom-color: #e5e5e5;}.modal-footer{border-top-color: #e5e5e5;}.modal-content{background-color: #ffffff;color: #333333;}.exploreMoreBar{border-color: #0a7589;background: #0a758907;}.exploreMoreBar .label-top, .exploreMoreBar .label-top img{background-color: #0a7589;color: #ffffff;}.exploreMoreBar .exploreMoreBarLabel{color: #ffffff;}#home-page-search-label,#home-page-advanced-search-link,#keepFiltersSwitchLabel,.menu-bar, #horizontal-menu-bar-container {color: #ffffff}.facetTitle, .exploreMoreTitle, .panel-heading, .panel-heading .panel-title,.panel-default > .panel-heading, .sidebar-links .panel-heading, #account-link-accordion .panel .panel-title, #account-settings-accordion .panel .panel-title{background-color: #e7e7e7;}.facetTitle, .exploreMoreTitle,.panel-title,.panel-default > .panel-heading, .sidebar-links .panel-heading, #account-link-accordion .panel .panel-title, #account-settings-accordion .panel .panel-title, .panel-title > a,.panel-default > .panel-heading{color: #333333;}.facetTitle.expanded, .exploreMoreTitle.expanded,.active .panel-heading,#more-details-accordion .active .panel-heading,.active .panel-default > .panel-heading, .sidebar-links .active .panel-heading, #account-link-accordion .panel.active .panel-title, #account-settings-accordion .panel.active .panel-title,.active .panel-title,.active .panel-title > a,.active.panel-default > .panel-heading, .adminSection .adminPanel .adminSectionLabel{background-color: #de9d03;}.facetTitle.expanded, .exploreMoreTitle.expanded,.active .panel-heading,#more-details-accordion .active .panel-heading,#more-details-accordion .active .panel-title,#account-link-accordion .panel.active .panel-title,.active .panel-title,.active .panel-title > a,.active.panel-default > .panel-heading,.adminSection .adminPanel .adminSectionLabel, .facetLock.pull-right a{color: #303030;}.panel-body,.sidebar-links .panel-body,#more-details-accordion .panel-body,.facetDetails,.sidebar-links .panel-body a:not(.btn), .sidebar-links .panel-body a:visited:not(.btn), .sidebar-links .panel-body a:hover:not(.btn),.adminSection .adminPanel{background-color: #ffffff;color: #404040;}.facetValue, .facetValue a,.adminSection .adminPanel .adminActionLabel,.adminSection .adminPanel .adminActionLabel a{color: #404040;}.breadcrumbs{background-color: #f5f5f5;color: #6B6B6B;}.breadcrumb > li + li::before{color: #6B6B6B;}#footer-container{border-top-color: #de1f0b;}#horizontal-menu-bar-container{border-bottom-color: #de1f0b;}#home-page-browse-header{background-color: #d7dce3;}.browse-category,#browse-sub-category-menu button{background-color: #0087AB !important;border-color: #0087AB !important;color: #ffffff !important;}.browse-category.selected,.browse-category.selected:hover,#browse-sub-category-menu button.selected,#browse-sub-category-menu button.selected:hover{border-color: #0087AB !important;background-color: #0087AB !important;color: #ffffff !important;}.btn-default,.btn-default:visited,a.btn-default,a.btn-default:visited{background-color: #ffffff;color: #333333;border-color: #cccccc;}.btn-default:hover, .btn-default:focus, .btn-default:active, .btn-default.active, .open .dropdown-toggle.btn-default{background-color: #eeeeee;color: #333333;border-color: #cccccc;}.btn-primary,.btn-primary:visited,a.btn-primary,a.btn-primary:visited{background-color: #1b6ec2;color: #ffffff;border-color: #1b6ec2;}.btn-primary:hover, .btn-primary:focus, .btn-primary:active, .btn-primary.active, .open .dropdown-toggle.btn-primary{background-color: #ffffff;color: #1b6ec2;border-color: #1b6ec2;}.btn-action,.btn-action:visited,a.btn-action,a.btn-action:visited{background-color: #1b6ec2;color: #ffffff;border-color: #1b6ec2;}.btn-action:hover, .btn-action:focus, .btn-action:active, .btn-action.active, .open .dropdown-toggle.btn-action{background-color: #ffffff;color: #1b6ec2;border-color: #1b6ec2;}.btn-info,.btn-info:visited,a.btn-info,a.btn-info:visited{background-color: #8cd2e7;color: #000000;border-color: #999999;}.btn-info:hover, .btn-info:focus, .btn-info:active, .btn-info.active, .open .dropdown-toggle.btn-info{background-color: #ffffff;color: #217e9b;border-color: #217e9b;}.btn-tools,.btn-tools:visited,a.btn-tools,a.btn-tools:visited{background-color: #747474;color: #ffffff;border-color: #636363;}.btn-tools:hover, .btn-tools:focus, .btn-tools:active, .btn-tools.active, .open .dropdown-toggle.btn-tools{background-color: #636363;color: #ffffff;border-color: #636363;}.btn-warning,.btn-warning:visited,a.btn-warning,a.btn-warning:visited{background-color: #f4d03f;color: #000000;border-color: #999999;}.btn-warning:hover, .btn-warning:focus, .btn-warning:active, .btn-warning.active, .open .dropdown-toggle.btn-warning{background-color: #ffffff;color: #8d6708;border-color: #8d6708;}.label-warning{background-color: #f4d03f;color: #000000;}.btn-danger,.btn-danger:visited,a.btn-danger,a.btn-danger:visited{background-color: #D50000;color: #ffffff;border-color: #999999;}.btn-danger:hover, .btn-danger:focus, .btn-danger:active, .btn-danger.active, .open .dropdown-toggle.btn-danger{background-color: #ffffff;color: #D50000;border-color: #D50000;}.label-danger{background-color: #D50000;color: #ffffff;}.btn-editions,.btn-editions:visited{background-color: #f8f9fa;color: #212529;border-color: #999999;}.btn-editions:hover, .btn-editions:focus, .btn-editions:active, .btn-editions.active{background-color: #ffffff;color: #1b6ec2;border-color: #1b6ec2;}.badge{background-color: #666666;color: #ffffff;}#webMenuNavBar{background-color: #0a7589;margin-bottom: 2px;color: #ffffff;.navbar-nav > li > a, .navbar-nav > li > a:visited {color: #ffffff;}}.dropdown-menu{background-color: white;color: #6B6B6B;}.result-label{color: #44484a}.result-value{color: #6B6B6B}.search_tools{background-color: #f5f5f5;color: #6B6B6B;}</style>',NULL,NULL,'#ffffff',1,'#0a7589',1,'#ffffff',1,'#ffffff',1,'#6B6B6B',1,'#de9d03',1,'#303030',1,'#de1f0b',1,'#000000',1,NULL,1,NULL,1,NULL,0,NULL,NULL,'#d7dce3',1,'#0087AB',1,'#ffffff',1,'#0087AB',1,'#0087AB',1,'#ffffff',1,'#0087AB',1,'#f1f1f1',1,'#265a87',1,NULL,NULL,-1,'#ffffff',1,'#333333',1,'#cccccc',1,'#eeeeee',1,'#333333',1,'#cccccc',1,'#1b6ec2',1,'#ffffff',1,'#1b6ec2',1,'#ffffff',1,'#1b6ec2',1,'#1b6ec2',1,'#1b6ec2',1,'#ffffff',1,'#1b6ec2',1,'#ffffff',1,'#1b6ec2',1,'#1b6ec2',1,'#8cd2e7',1,'#000000',1,'#999999',1,'#ffffff',1,'#217e9b',1,'#217e9b',1,'#f4d03f',1,'#000000',1,'#999999',1,'#ffffff',1,'#8d6708',1,'#8d6708',1,'#D50000',1,'#ffffff',1,'#999999',1,'#ffffff',1,'#D50000',1,'#D50000',1,'#f8f9fa',1,'#212529',1,'#999999',1,'#ffffff',1,'#1b6ec2',1,'#1b6ec2',1,'#747474',1,'#ffffff',1,'#636363',1,'#636363',1,'#ffffff',1,'#636363',1,'#f1f1f1',1,'#303030',1,NULL,NULL,'#e7e7e7',1,'#333333',1,'#de9d03',1,'#303030',1,'#ffffff',1,'#404040',1,'#3174AF',1,'#265a87',1,'#666666',1,'#ffffff',1,NULL,'#44484a',1,'#6B6B6B',1,'#f5f5f5',1,'#6B6B6B',1,'#f5f5f5',1,'#e3e3e3',1,'#6B6B6B',1,'#f1f1f1',1,'#303030',1,'#ededed',1,'#404040',1,'#ffffff',1,'#333333',1,'#ffffff',1,'#333333',1,'#e5e5e5',1,NULL);
/*!40000 ALTER TABLE `themes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `time_to_reshelve`
--

DROP TABLE IF EXISTS `time_to_reshelve`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `time_to_reshelve` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `indexingProfileId` int(11) NOT NULL,
  `locations` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `numHoursToOverride` int(11) NOT NULL,
  `status` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `groupedStatus` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `weight` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `indexingProfileId` (`indexingProfileId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `time_to_reshelve`
--

LOCK TABLES `time_to_reshelve` WRITE;
/*!40000 ALTER TABLE `time_to_reshelve` DISABLE KEYS */;
/*!40000 ALTER TABLE `time_to_reshelve` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `title_authorities`
--

DROP TABLE IF EXISTS `title_authorities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `title_authorities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `originalName` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `authoritativeName` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `originalName` (`originalName`),
  KEY `authoritativeName` (`authoritativeName`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=1365;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `title_authorities`
--

LOCK TABLES `title_authorities` WRITE;
/*!40000 ALTER TABLE `title_authorities` DISABLE KEYS */;
/*!40000 ALTER TABLE `title_authorities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `translation_map_values`
--

DROP TABLE IF EXISTS `translation_map_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `translation_map_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `translationMapId` int(11) NOT NULL,
  `value` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `translation` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `translationMapId` (`translationMapId`,`value`)
) ENGINE=InnoDB AUTO_INCREMENT=13306 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `translation_map_values`
--

LOCK TABLES `translation_map_values` WRITE;
/*!40000 ALTER TABLE `translation_map_values` DISABLE KEYS */;
INSERT INTO `translation_map_values` VALUES (10304,16,'100EBOOK','Margaret Allen Middle - E-Books'),(10305,16,'105EBOOK','Amqui Elementary - E-Books'),(10306,16,'110EBOOK','Antioch High - E-Books'),(10307,16,'111EBOOK','Antioch Middle - E-Books'),(10308,16,'120EBOOK','Jere Baxter Middle - E-Books'),(10309,16,'122EBOOK','Lakeview Elementary - E-Books'),(10310,16,'130EBOOK','Bellevue Middle - E-Books'),(10311,16,'135EBOOK','Bellshire Elementary - E-Books'),(10312,16,'145EBOOK','Norman Binkley Elementary - E-Books'),(10313,16,'165EBOOK','Buena Vista Elementary - E-Books'),(10314,16,'175EBOOK','Ida B. Wells Elementary - E-Books'),(10315,16,'181EBOOK','Cameron College Prep - E-Books'),(10316,16,'182EBOOK','Cane Ridge High - E-Books'),(10317,16,'184EBOOK','Cane Ridge Elementary - E-Books'),(10318,16,'185EBOOK','Carter Lawrence Elementary - E-Books'),(10319,16,'200EBOOK','Chadwell Elementary - E-Books'),(10320,16,'205EBOOK','Charlotte Park Elementary - E-Books'),(10321,16,'215EBOOK','Cockrill Elementary - E-Books'),(10322,16,'225EBOOK','Cole Elementary - E-Books'),(10323,16,'230EBOOK','Hattie Cotton Elementary - E-Books'),(10324,16,'235EBOOK','Crieve Hall Elementary - E-Books'),(10325,16,'238EBOOK','Croft Middle - E-Books'),(10326,16,'240EBOOK','Cumberland Elementary - E-Books'),(10327,16,'242EBOOK','Nashville School of the Arts - E-Books'),(10328,16,'252EBOOK','Dodson Elementary - E-Books'),(10329,16,'260EBOOK','Donelson Middle - E-Books'),(10330,16,'265EBOOK','Dupont Elementary - E-Books'),(10331,16,'270EBOOK','Dupont Hadley Middle - E-Books'),(10332,16,'275EBOOK','Dupont Tyler Middle - E-Books'),(10333,16,'280EBOOK','Eakin Elementary - E-Books'),(10334,16,'285EBOOK','John Early Middle - E-Books'),(10335,16,'290EBOOK','East Nashville Magnet High - E-Books'),(10336,16,'296EBOOK','East Nashville Magnet Middle - E-Books'),(10337,16,'308EBOOK','Fall-Hamilton Elementary - E-Books'),(10338,16,'310EBOOK','JE Moss Elementary - E-Books'),(10339,16,'315EBOOK','Gateway Elementary - E-Books'),(10340,16,'320EBOOK','Glencliff Elementary - E-Books'),(10341,16,'325EBOOK','Glencliff High - E-Books'),(10342,16,'330EBOOK','Glendale Elementary - E-Books'),(10343,16,'335EBOOK','Glengarry Elementary - E-Books'),(10344,16,'340EBOOK','Glenn Elementary - E-Books'),(10345,16,'345EBOOK','Glenview Elementary - E-Books'),(10346,16,'350EBOOK','Goodlettsville Elementary - E-Books'),(10347,16,'355EBOOK','Goodlettsville Middle - E-Books'),(10348,16,'360EBOOK','Gower Elementary - E-Books'),(10349,16,'365EBOOK','Gra-Mar Middle - E-Books'),(10350,16,'370EBOOK','Granbery Elementary - E-Books'),(10351,16,'375EBOOK','Alex Green Elementary - E-Books'),(10352,16,'380EBOOK','Julia Green Elementary - E-Books'),(10353,16,'395EBOOK','Harpeth Valley Elementary - E-Books'),(10354,16,'397EBOOK','Harris Hillman School - E-Books'),(10355,16,'400EBOOK','Haynes Middle - E-Books'),(10356,16,'405EBOOK','Haywood Elementary - E-Books'),(10357,16,'410EBOOK','Head Magnet Middle - E-Books'),(10358,16,'415EBOOK','Hermitage Elementary - E-Books'),(10359,16,'420EBOOK','Hickman Elementary - E-Books'),(10360,16,'434EBOOK','HG Hill Middle - E-Books'),(10361,16,'435EBOOK','Hillsboro High - E-Books'),(10362,16,'440EBOOK','Hillwood High - E-Books'),(10363,16,'448EBOOK','Cora Howe School - E-Books'),(10364,16,'450EBOOK','Hume-Fogg Magnet High - E-Books'),(10365,16,'451EBOOK','Hull-Jackson Montessori - E-Books'),(10366,16,'452EBOOK','Hunter\'s Lane High - E-Books'),(10367,16,'455EBOOK','Inglewood Elementary - E-Books'),(10368,16,'460EBOOK','Andrew Jackson Elementary - E-Books'),(10369,16,'465EBOOK','Joelton Elementary - E-Books'),(10370,16,'470EBOOK','Joelton Middle - E-Books'),(10371,16,'485EBOOK','Jones Paideia Magnet Elementary - E-Books'),(10372,16,'495EBOOK','Tom Joy Elementary - E-Books'),(10373,16,'496EBOOK','AZ Kelley Elementary - E-Books'),(10374,16,'497EBOOK','MLK Jr. Magnet - E-Books'),(10375,16,'498EBOOK','JFK Middle - E-Books'),(10376,16,'500EBOOK','Robert E Lillard Elementary - E-Books'),(10377,16,'508EBOOK','LEAD Academy High - E-Books'),(10378,16,'510EBOOK','Litton Middle - E-Books'),(10379,16,'520EBOOK','Lockeland Elementary - E-Books'),(10380,16,'522EBOOK','Ruby Major Elementary - E-Books'),(10381,16,'530EBOOK','McGavock Elementary - E-Books'),(10382,16,'532EBOOK','McGavock High - E-Books'),(10383,16,'535EBOOK','McKissack Middle - E-Books'),(10384,16,'540EBOOK','McMurray Middle - E-Books'),(10385,16,'545EBOOK','Madison Middle - E-Books'),(10386,16,'550EBOOK','Maplewood High - E-Books'),(10387,16,'551EBOOK','Marshall Middle - E-Books'),(10388,16,'552EBOOK','Maxwell Elementary - E-Books'),(10389,16,'555EBOOK','Meigs Magnet Middle - E-Books'),(10390,16,'560EBOOK','Dan Mills Elementary - E-Books'),(10391,16,'563EBOOK','JT Moore Middle - E-Books'),(10392,16,'575EBOOK','Thomas A Edison Elementary - E-Books'),(10393,16,'576EBOOK','Mt. View Elementary - E-Books'),(10394,16,'577EBOOK','Apollo Middle - E-Books'),(10395,16,'590EBOOK','Napier Elementary - E-Books'),(10396,16,'595EBOOK','Neely\'s Bend Elementary - E-Books'),(10397,16,'610EBOOK','Old Center Elementary - E-Books'),(10398,16,'612EBOOK','Oliver Middle - E-Books'),(10399,16,'615EBOOK','Overton High - E-Books'),(10400,16,'618EBOOK','Paragon Mills Elementary - E-Books'),(10401,16,'620EBOOK','Park Avenue Elementary - E-Books'),(10402,16,'632EBOOK','Pearl-Cohn High - E-Books'),(10403,16,'640EBOOK','Pennington Elementary - E-Books'),(10404,16,'650EBOOK','Percy Priest Elementary - E-Books'),(10405,16,'670EBOOK','Rosebank Elementary - E-Books'),(10406,16,'675EBOOK','Rose Park Middle - E-Books'),(10407,16,'682EBOOK','Shayne Elementary - E-Books'),(10408,16,'685EBOOK','Shwab Elementary - E-Books'),(10409,16,'686EBOOK','Smith Springs Elementary - E-Books'),(10410,16,'690EBOOK','Stanford Montessori Elementary - E-Books'),(10411,16,'705EBOOK','Stratford STEM High - E-Books'),(10412,16,'710EBOOK','Stratton Elementary - E-Books'),(10413,16,'715EBOOK','Sylvan Park Elementary - E-Books'),(10414,16,'717EBOOK','Tulip Grove Elementary - E-Books'),(10415,16,'725EBOOK','Tusculum Elementary - E-Books'),(10416,16,'730EBOOK','Two Rivers Middle - E-Books'),(10417,16,'735EBOOK','Una Elementary - E-Books'),(10418,16,'755EBOOK','Warner Elementary - E-Books'),(10419,16,'765EBOOK','Waverly-Belmont Elementary - E-Books'),(10420,16,'770EBOOK','West End Middle - E-Books'),(10421,16,'775EBOOK','Westmeade Elementary - E-Books'),(10422,16,'783EBOOK','IT Creswell Arts Middle - E-Books'),(10423,16,'784EBOOK','Robert Churchwell Elementary - E-Books'),(10424,16,'787EBOOK','Whites Creek High - E-Books'),(10425,16,'790EBOOK','Whitsitt Elementary - E-Books'),(10426,16,'805EBOOK','Wright Middle - E-Books'),(11235,27,'^A.*','Adult'),(11236,27,'^J.*','Kids'),(11237,27,'^E.*','Everyone'),(11238,27,'^S.*','Staff'),(11239,27,'^Y.*','Teen'),(11979,29,'Charged Temporary','CT'),(11980,29,'Checked Out','C'),(11981,29,'In Transit for Hold','IH'),(11982,29,'On Hold Shelf','H'),(11983,29,'Hold Shelf Temp','HT'),(11984,29,'In Transit','I'),(11986,29,'In Transit Temp Item','IT'),(11987,29,'Lost','L'),(11988,29,'Lost Temporary','LT'),(11989,29,'On Shelf','S'),(11990,29,'Ordered','N'),(11991,29,'Received','RF'),(11992,29,'On Shelf Temporary','ST'),(11993,29,'Traced','T'),(11994,29,'Withdrawn','SW'),(11995,29,'Hold Pending','HP'),(11996,29,'Missing','SM'),(11997,29,'Shelving Delay','SD'),(11998,29,'Grubby','SG'),(11999,29,'In Processing','SP'),(12000,29,'Not on Shelf','SX'),(12001,29,'In Repair','SU'),(12002,29,'Library Use Only','SC'),(12003,29,'On Display','SI'),(12004,29,'Empty Case','SO'),(12005,29,'Temporarily Unavailable','SS'),(12007,29,'Traced Temporary','TT'),(12012,29,'On Order','O'),(12018,27,'^X.*','Unknown'),(12171,24,'BK','Book'),(12172,24,'BKPK','Bookpack'),(12173,24,'BR3','Blu-ray'),(12174,24,'BRAY','Blu-ray'),(12175,24,'BRR','Blu-ray R-rated'),(12176,24,'BRR3','Blu-ray R-rated'),(12177,24,'CASS','Cassette tape audio book'),(12178,24,'CD','Music CD'),(12179,24,'CDA','CD audio book'),(12180,24,'COM','Computer'),(12181,24,'DOC','Document/manuscript/pamphlet/archival material'),(12182,24,'DVD','DVD'),(12183,24,'DVD3','DVD'),(12184,24,'DVDR','DVD R-rated'),(12185,24,'DVR3','DVD R-rated'),(12186,24,'EQ','Equipment - circulating'),(12187,24,'EQX','Equipment - non-circulating'),(12188,24,'ERR','Error'),(12189,24,'GAME','Videogame'),(12190,24,'GDOC','Government document'),(12191,24,'KIT','Kit'),(12192,24,'LP','LP record album'),(12193,24,'MCH','Microfiche'),(12194,24,'MF','Microfilm'),(12195,24,'OTH','Other'),(12196,24,'PBK','Book'),(12197,24,'PLA','Playaway'),(12198,24,'SBK','Book'),(12199,24,'SCDA','CD audio book'),(12200,24,'SEQ','Equipment - circulating'),(12201,24,'SER','Serial'),(12202,24,'SFT','Software'),(12203,24,'SKIT','Kit'),(12204,24,'SPLA','Playaway'),(12205,24,'SSER','Serial'),(12206,24,'SSFT','Software'),(12207,24,'TXT','Textbook'),(12208,24,'VHS','Videocassette'),(12239,17,'^.ADAPT$','Adaptive Books'),(12240,17,'^.AFAM$','African-American'),(12241,17,'^.ARAB$','Arabic'),(12242,17,'^.ARTOV$','Fine Arts - Oversized'),(12243,17,'^.ARTS$','Fine Arts'),(12244,17,'^.ARTSH$','Fine Arts - Hadley'),(12245,17,'^.AUBK$','Audiobook'),(12246,17,'^.BANN$','Banner'),(12247,17,'^.BCIAB$','Book Club In A Bag'),(12248,17,'^.BIGBK$','Big Book'),(12249,17,'^.BIOG$','Biography'),(12250,17,'^.BOARD$','Board Book'),(12251,17,'^.CAR$','Careers'),(12252,17,'^.CASE$','Nashville Room-Case'),(12253,17,'^.CDRM$','CD-ROM'),(12254,17,'^.CIVIL$','Civil Rights Room'),(12255,17,'^.CKIT$','Curriculum Kit'),(12256,17,'^.CKITX$','Curriculum Kit'),(12257,17,'^.CLUB$','Book Club Use'),(12258,17,'^.COM$','Commons'),(12259,17,'^.EAP$','Equal Access'),(12260,17,'^.EASY$','Easy'),(12261,17,'^.ERR$','Error'),(12262,17,'^.FIC$','Fiction'),(12263,17,'^.FOUND$','Foundation Center'),(12264,17,'^.GAME$','Videogame'),(12265,17,'^.GDCEN$','Government Document - Census'),(12266,17,'^.GDREF$','Government Document - Reference'),(12267,17,'^.GDRES$','Government Document - Restricted'),(12268,17,'^.GEN$','Genealogy'),(12269,17,'^.GRANT$','Grantha'),(12270,17,'^.GRAPH$','Comic/Graphic'),(12271,17,'^.GVDOC$','Government Document'),(12272,17,'^.HIST$','Historical'),(12273,17,'^.HOL$','Holiday'),(12274,17,'^.KANTO$','Kantor'),(12275,17,'^.LAP$','Laptops Anytime'),(12276,17,'^.LEGAL$','Legal Reference'),(12277,17,'^.LPAD$','Launchpad'),(12278,17,'^.LTP$','Large Print'),(12279,17,'^.LUCKY$','Lucky Day'),(12280,17,'^.MAKER$','Studio'),(12281,17,'^.MBANK$','Mediabank Movie'),(12282,17,'^.MEDIA$','Historical Audiovisual Collection'),(12283,17,'^.MOVIE$','Movie'),(12284,17,'^.MUIR$','Muirhead'),(12285,17,'^.MUSIC$','Music'),(12286,17,'^.NAC$','Nac'),(12287,17,'^.NEW$','New'),(12288,17,'^.NF$','Non-Fiction'),(12289,17,'^.NFD$','Non-Fiction Reference'),(12290,17,'^.NWN$','New Non-Fiction'),(12291,17,'^.NWREA$','Fresh Reads'),(12292,17,'^.OFF$','LSDHH Office'),(12293,17,'^.ORD$','On Order'),(12294,17,'^.OTHER$','Other'),(12295,17,'^.OVER$','Oversize'),(12296,17,'^.PAREN$','Parenting'),(12297,17,'^.PATH$','Pathways For New Americans'),(12298,17,'^.PER$','Periodical'),(12299,17,'^.PMD$','Pop Mat Desk'),(12300,17,'^.PREC$','Patron Recommendation App'),(12301,17,'^.PROF$','Professional'),(12302,17,'^.READ$','Reader'),(12303,17,'^.REF$','Reference'),(12304,17,'^.RESRV$','Reserves'),(12305,17,'^.SAFER$','Safe Room'),(12306,17,'^.SER$','Series'),(12307,17,'^.SMISC$','Staff Item'),(12308,17,'^.SPA$','Spanish'),(12309,17,'^.STAHL$','Stahlman'),(12310,17,'^.SUP$','Suppressed'),(12311,17,'^.TDISP$','Table Display'),(12312,17,'^.TECH$','Technology/Computers'),(12313,17,'^.TEMP$','Temporary'),(12314,17,'^.TENN$','Tenn.'),(12315,17,'^.TEXT$','Textured Bags'),(12316,17,'^.URBAN$','Urban Fiction'),(12317,17,'^.VEND$','Vending Machine Movie'),(12318,17,'^.WEIL$','Weil Collection'),(12319,17,'^.WLC$','World Language'),(12320,25,'AAFAM','Adult African-American'),(12321,25,'AARAB','Adult Arabic'),(12322,25,'AARTOV','Adult Fine Arts - Oversized'),(12323,25,'AARTS','Adult Fine Arts'),(12324,25,'AARTSH','Adult Fine Arts - Hadley'),(12325,25,'AAUBK','Adult Audiobook'),(12326,25,'ABANN','Banner'),(12327,25,'ABCIAB','Adult Book Club In A Bag'),(12328,25,'ABIOG','Adult Biography'),(12329,25,'ACAR','Adult Careers'),(12330,25,'ACASE','Nashville Room-Case'),(12331,25,'ACLUB','Adult Book Club Use'),(12332,25,'ACOM','Adult Commons'),(12333,25,'AEAP','Adult Equal Access'),(12334,25,'AFIC','Adult Fiction'),(12335,25,'AFOUND','Adult Foundation Center'),(12336,25,'AGEN','Genealogy'),(12337,25,'AGRANT','Grantha'),(12338,25,'AGRAPH','Adult Comic/Graphic'),(12339,25,'AHIST','Adult Historical'),(12340,25,'AKANTO','Kantor'),(12341,25,'ALAP','Adult Laptops Anytime'),(12342,25,'ALEGAL','Adult Legal Reference'),(12343,25,'ALTP','Adult Large Print'),(12344,25,'ALUCKY','Adult Lucky Day'),(12345,25,'AMBANK','Adult Mediabank Movie'),(12346,25,'AMEDIA','Historical Audiovisual Collection'),(12347,25,'AMOVIE','Adult Movie'),(12348,25,'AMUIR','Muirhead'),(12349,25,'AMUSIC','Adult Music'),(12350,25,'ANAC','NAC'),(12351,25,'ANEW','Adult New'),(12352,25,'ANF','Adult Non-Fiction'),(12353,25,'ANFD','Adult Non-Fiction Reference'),(12354,25,'ANWN','Adult New Non-Fiction'),(12355,25,'ANWREA','Adult Fresh Reads'),(12356,25,'AOVER','Adult Oversize'),(12357,25,'APAREN','Adult Parenting'),(12358,25,'APER','Adult Periodical'),(12359,25,'APMD','Adult Pop Mat Desk'),(12360,25,'APROF','Adult Professional'),(12361,25,'AREF','Adult Reference'),(12362,25,'ASAFER','Safe Room'),(12363,25,'ASPA','Adult Spanish'),(12364,25,'ASTAHL','Stahlman'),(12365,25,'ATDISP','Adult Table Display'),(12366,25,'ATENN','Tenn.'),(12367,25,'AURBAN','Adult Urban Fiction'),(12368,25,'AVEND','Adult Vending Machine Movie'),(12369,25,'AWEIL','Adult Weil Collection'),(12370,25,'EADAPT','Adaptive Books'),(12371,25,'ECDRM','Kids CD-ROM'),(12372,25,'ECIVIL','Civil Rights Room'),(12373,25,'ECKIT','Curriculum Kit'),(12374,25,'ECKITX','Curriculum Kit'),(12375,25,'EERR','Error'),(12376,25,'EGDCEN','Government Document - Census'),(12377,25,'EGDREF','Government Document - Reference'),(12378,25,'EGDRES','Government Document - Restricted'),(12379,25,'EGVDOC','Government Document'),(12380,25,'EOFF','LSDHH Office'),(12381,25,'EPATH','Pathways For New Americans'),(12382,25,'ESUP','Suppressed'),(12383,25,'ETECH','Technology/Computers'),(12384,25,'ETEXT','Textured Bags'),(12385,25,'JAUBK','Kids Audiobook'),(12386,25,'JBIGBK','Kids Big Book'),(12387,25,'JBIOG','Kids Biography'),(12388,25,'JBOARD','Kids Board Book'),(12389,25,'JEASY','Kids Easy'),(12390,25,'JFIC','Kids Fiction'),(12391,25,'JGRAPH','Kids Comic/Graphic'),(12392,25,'JHIST','Kids Historical'),(12393,25,'JHOL','Kids Holiday'),(12394,25,'JLPAD','Kids Launchpad'),(12395,25,'JMBANK','Kids Mediabank Movie'),(12396,25,'JMOVIE','Kids Movie'),(12397,25,'JMUSIC','Kids Music'),(12398,25,'JNF','Kids Non-Fiction'),(12399,25,'JOTHER','Kids Other'),(12400,25,'JOVER','Kids Oversize'),(12401,25,'JPER','Kids Periodical'),(12402,25,'JREAD','Kids Reader'),(12403,25,'JREF','Kids Reference'),(12404,25,'JRESRV','Kids Reserves'),(12405,25,'JSER','Kids Series'),(12406,25,'JVEND','Kids Vending Machine Movie'),(12407,25,'JWLC','Kids World Language'),(12408,25,'SMISC','Staff Item'),(12409,25,'TEMP','Temporary'),(12410,25,'XORD','On Order'),(12411,25,'XPREC','Patron Recommendation App'),(12412,25,'YAUBK','Teen Audiobook'),(12413,25,'YBIOG','Teen Biography'),(12414,25,'YEASY','Everyone Book'),(12415,25,'YFIC','Teen Fiction'),(12416,25,'YGAME','Teen Videogame'),(12417,25,'YGRAPH','Teen Comic/Graphic'),(12418,25,'YLUCKY','Teen Lucky Day'),(12419,25,'YMAKER','Teen Studio'),(12420,25,'YMOVIE','Teen Movie'),(12421,25,'YNF','Teen Non-Fiction'),(12422,25,'YOTHER','Teen Other'),(12423,25,'YPER','Teen Periodical'),(12424,25,'YREF','Teen Reference'),(12425,25,'YRESRV','Teen Reserves'),(12426,28,'AAFAM','Adult African-American'),(12427,28,'AARAB','Adult Arabic'),(12428,28,'AARTOV','Adult Fine Arts - Oversized'),(12429,28,'AARTS','Adult Fine Arts'),(12430,28,'AARTSH','Adult Fine Arts - Hadley'),(12431,28,'AAUBK','Adult Audiobook'),(12432,28,'ABANN','Banner'),(12433,28,'ABCIAB','Adult Book Club In A Bag'),(12434,28,'ABIOG','Adult Biography'),(12435,28,'ACAR','Adult Careers'),(12436,28,'ACASE','Nashville Room-Case'),(12437,28,'ACLUB','Adult Book Club Use'),(12438,28,'ACOM','Adult Commons'),(12439,28,'AEAP','Adult Equal Access'),(12440,28,'AFIC','Adult Fiction'),(12441,28,'AFOUND','Adult Foundation Center'),(12442,28,'AGEN','Genealogy'),(12443,28,'AGRANT','Grantha'),(12444,28,'AGRAPH','Adult Comic/Graphic'),(12445,28,'AHIST','Adult Historical'),(12446,28,'AKANTO','Kantor'),(12447,28,'ALAP','Adult Laptops Anytime'),(12448,28,'ALEGAL','Adult Legal Reference'),(12449,28,'ALTP','Adult Large Type'),(12450,28,'ALUCKY','Adult Lucky Day'),(12451,28,'AMBANK','Adult Mediabank Movie'),(12452,28,'AMEDIA','Historical Audiovisual Collection'),(12453,28,'AMOVIE','Adult Movie'),(12454,28,'AMUIR','Muirhead'),(12455,28,'AMUSIC','Adult Music'),(12456,28,'ANAC','NAC'),(12457,28,'ANEW','Adult New'),(12458,28,'ANF','Adult Non-Fiction'),(12459,28,'ANFD','Adult Non-Fiction Reference'),(12460,28,'ANWN','Adult New Non-Fiction'),(12461,28,'ANWREA','Adult New Reader'),(12462,28,'AOVER','Adult Oversize'),(12463,28,'APAREN','Adult Parenting'),(12464,28,'APER','Adult Periodical'),(12465,28,'APMD','Adult Pop Mat Desk'),(12466,28,'APROF','Adult Professional'),(12467,28,'AREF','Adult Reference'),(12468,28,'ASAFER','Safe Room'),(12469,28,'ASPA','Adult Spanish'),(12470,28,'ASTAHL','Stahlman'),(12471,28,'ATDISP','Adult Table Display'),(12472,28,'ATENN','Tenn.'),(12473,28,'AURBAN','Adult Urban Fiction'),(12474,28,'AVEND','Adult Vending Machine Movie'),(12475,28,'AWEIL','Adult Weil Collection'),(12476,28,'EADAPT','Adaptive Books'),(12477,28,'ECDRM (WAS JCDRM)','Kids Cd-Rom'),(12478,28,'ECIVIL','Civil Rights Room'),(12479,28,'ECKIT','Curriculum Kit'),(12480,28,'ECKITX','Curriculum Kit'),(12481,28,'EERR','Error'),(12482,28,'EGDCEN','Government Document - Census'),(12483,28,'EGDREF','Government Document - Reference'),(12484,28,'EGDRES','Government Document - Restricted'),(12485,28,'EGVDOC','Government Document'),(12486,28,'EOFF','LSDHH Office'),(12487,28,'EPATH','Pathways For New Americans'),(12488,28,'ESUP','Suppressed'),(12489,28,'ETECH','Technology/Computers'),(12490,28,'ETEXT','Textured Bags'),(12491,28,'JAUBK','Kids Audiobook'),(12492,28,'JBIGBK','Kids Big Book'),(12493,28,'JBIOG','Kids Biography'),(12494,28,'JBOARD','Kids Board Book'),(12495,28,'JEASY','Kids Easy (Display As Everyone In School)'),(12496,28,'JFIC','Kids Fiction'),(12497,28,'JGRAPH','Kids Comic/Graphic'),(12498,28,'JHIST','Kids Historical'),(12499,28,'JHOL','Kids Holiday'),(12500,28,'JLPAD','Kids Launchpad'),(12501,28,'JMBANK','Kids Mediabank Movie'),(12502,28,'JMOVIE','Kids Movie'),(12503,28,'JMUSIC','Kids Music'),(12504,28,'JNF','Kids Non-Fiction'),(12505,28,'JOTHER','Kids Other'),(12506,28,'JOVER','Kids Oversize'),(12507,28,'JPER','Kids Periodical'),(12508,28,'JREAD','Kids Reader'),(12509,28,'JREF','Kids Reference'),(12510,28,'JRESRV','Kids Reserves'),(12511,28,'JSER','Kids Series'),(12512,28,'JVEND','Kids Vending Machine Movie'),(12513,28,'JWLC','Kids World Language'),(12514,28,'SMISC','Staff Item'),(12515,28,'TEMP','Temporary'),(12516,28,'XORD','On Order'),(12517,28,'XPREC','Patron Recommendation App'),(12518,28,'YAUBK','Teen Audiobook'),(12519,28,'YBIOG','Teen Biography'),(12520,28,'YEASY','Everyone Book'),(12521,28,'YFIC','Teen Fiction'),(12522,28,'YGAME','Teen Videogame'),(12523,28,'YGRAPH','Teen Comic/Graphic'),(12524,28,'YLUCKY','Teen Lucky Day'),(12525,28,'YMAKER','Teen Studio'),(12526,28,'YMOVIE','Teen Movie'),(12527,28,'YNF','Teen Non-Fiction'),(12528,28,'YOTHER','Teen Other'),(12529,28,'YPER','Teen Periodical'),(12530,28,'YREF','Teen Reference'),(12531,28,'YRESRV','Teen Reserves'),(12532,26,'ar','Archives'),(12533,26,'ax','Annex'),(12534,26,'bl','Bellevue'),(12535,26,'bx','Bordeaux'),(12536,26,'ct','Collections and Technology Services'),(12537,26,'do','Donelson'),(12538,26,'ea','East'),(12539,26,'eh','Edgehill'),(12540,26,'ep','Edmondson Pike'),(12541,26,'ERROR','ERROR'),(12542,26,'gh','Green Hills'),(12543,26,'go','Goodlettsville'),(12544,26,'hi','LSDHH'),(12545,26,'hm','Hermitage'),(12546,26,'hp','Hadley Park'),(12547,26,'il','Interlibrary Loan'),(12548,26,'in','Inglewood'),(12549,26,'ll','Limitless Library'),(12550,26,'lo','Looby'),(12551,26,'ma','Madison'),(12552,26,'mn','Main Library'),(12553,26,'no','North'),(12554,26,'oh','Old Hickory'),(12555,26,'pr','Pruitt'),(12556,26,'rp','Richland Park'),(12557,26,'sc','Special Collections'),(12558,26,'se','Southeast'),(12559,26,'ta','Talking Library'),(12560,26,'tl','Thompson Lane'),(12561,26,'vi','Virtual'),(12562,26,'wp','Watkins Park'),(12563,26,'00152','Davis Early Learning Center'),(12564,26,'01681','Ross Early Learning Center'),(12565,26,'03186','Casa Azafran Early Learning Center-delivery via Martin Center'),(12566,26,'04419','Cambridge Early Learning Center-delivery via Martin Center'),(12567,26,'10105','Amqui Elementary'),(12568,26,'11122','Lakeview Elementary'),(12569,26,'12135','Bellshire Elementary'),(12570,26,'13145','Norman Binkley Elementary'),(12571,26,'14165','Buena Vista Elementary'),(12572,26,'15175','Ida B. Wells Elementary'),(12573,26,'16184','Cane Ridge Elementary'),(12574,26,'17185','Lawrence Carter Elementary'),(12575,26,'18200','Chadwell Elementary'),(12576,26,'19205','Charlotte Park Elementary'),(12577,26,'1A215','Cockrill Elementary'),(12578,26,'1B225','Cole Elementary'),(12579,26,'1C230','Hattie Cotton Elementary'),(12580,26,'1D235','Crieve Hall Elementary'),(12581,26,'1E240','Cumberland Elementary'),(12582,26,'1F252','Dodson Elementary'),(12583,26,'1G265','Dupont Elementary'),(12584,26,'1H280','Eakin Elementary'),(12585,26,'1I308','Fall-Hamilton Elementary'),(12586,26,'1J310','Moss Elementary'),(12587,26,'1K315','Gateway Elementary'),(12588,26,'1L320','Glencliff Elementary'),(12589,26,'1M330','Glendale Elementary'),(12590,26,'1N335','Glengarry Elementary'),(12591,26,'1O340','Glenn Elementary'),(12592,26,'1P345','Glenview Elementary'),(12593,26,'1Q350','Goodlettsville Elementary'),(12594,26,'1R360','Gower Elementary'),(12595,26,'1S370','Granbery Elementary'),(12596,26,'1T375','Alex Green Elementary'),(12597,26,'1U380','Julia Green Elementary'),(12598,26,'1V395','Harpeth Valley Elementary'),(12599,26,'1W405','Haywood Elementary'),(12600,26,'1X415','Hermitage Elementary'),(12601,26,'1Y420','Hickman Elementary'),(12602,26,'1Z451','Hull-Jackson Montessori'),(12603,26,'20455','Inglewood Elementary'),(12604,26,'21460','Andrew Jackson Elementary'),(12605,26,'22465','Joelton Elementary'),(12606,26,'23485','Jones Paideia Magnet Elementary'),(12607,26,'24495','Tom Joy Elementary'),(12608,26,'25496','A. Z. Kelley Elementary'),(12609,26,'26500','Robert E Lillard Elementary'),(12610,26,'27505','Kirkpatrick Elementary'),(12611,26,'28520','Lockeland Elementary'),(12612,26,'29522','Ruby Major Elementary'),(12613,26,'2A530','McGavock Elementary'),(12614,26,'2B552','Maxwell Elementary'),(12615,26,'2C560','Dan Mills Elementary'),(12616,26,'2D575','Thomas A. Edison Elementary'),(12617,26,'2E576','Mt. View Elementary'),(12618,26,'2F590','Napier Elementary'),(12619,26,'2G595','Neely\'s Bend Elementary'),(12620,26,'2H610','Old Center Elementary'),(12621,26,'2I618','Paragon Mills Elementary'),(12622,26,'2J620','Park Avenue Elementary'),(12623,26,'2K640','Pennington Elementary'),(12624,26,'2L650','Percy Priest Elementary'),(12625,26,'2M670','Rosebank Elementary'),(12626,26,'2N682','Shayne Elementary'),(12627,26,'2O685','Shwab Elementary'),(12628,26,'2P686','Smith Springs Elementary'),(12629,26,'2Q690','Stanford Montessori Elementary'),(12630,26,'2R710','Stratton Elementary'),(12631,26,'2S715','Sylvan Park  Elementary'),(12632,26,'2T717','Tulip Grove  Elementary'),(12633,26,'2U725','Tusculum Elementary'),(12634,26,'2V735','Una Elementary'),(12635,26,'2W755','Warner Elementary'),(12636,26,'2X765','Waverly-Belmont Elementary'),(12637,26,'2Y775','Westmeade Elementary'),(12638,26,'2Z784','Robert Churchwell Elementary'),(12639,26,'30790','Whitsitt Elementary'),(12640,26,'40100','Margaret Allen Middle'),(12641,26,'41111','Antioch Middle'),(12642,26,'43120','Jere Baxter Middle'),(12643,26,'44130','Bellevue Middle'),(12644,26,'45238','Croft Middle'),(12645,26,'46260','Donelson Middle'),(12646,26,'47270','Dupont Hadley Middle'),(12647,26,'48275','Dupont Tyler Middle'),(12648,26,'49285','John Early Middle'),(12649,26,'4A296','East Nashville Magnet Middle'),(12650,26,'4B355','Goodlettsville Middle'),(12651,26,'4C365','Gra-Mar Middle'),(12652,26,'4D400','Haynes Middle'),(12653,26,'4E410','Head Middle Magnet'),(12654,26,'4F434','Hill, H. G. Middle'),(12655,26,'4G470','Joelton Middle'),(12656,26,'4H498','J F Kennedy Middle'),(12657,26,'4I510','Litton Middle'),(12658,26,'4J535','McKissack Middle'),(12659,26,'4K540','McMurray Middle'),(12660,26,'4L545','Madison Middle'),(12661,26,'4M551','Marshall Middle'),(12662,26,'4N555','Meigs Middle Magnet'),(12663,26,'4O563','J.T. Moore Middle'),(12664,26,'4P577','Apollo Middle'),(12665,26,'4S612','Oliver Middle'),(12666,26,'4T675','Rose Park Middle'),(12667,26,'4U730','Two Rivers Middle'),(12668,26,'4V770','West End Middle'),(12669,26,'4W783','Creswell Arts Middle'),(12670,26,'4X805','Wright Middle'),(12671,26,'60110','Antioch High'),(12672,26,'61182','Cane Ridge High'),(12673,26,'62242','Nashville School of the Arts'),(12674,26,'63290','East Nashville Magnet High'),(12675,26,'64325','Glencliff High'),(12676,26,'65397','Harris Hillman'),(12677,26,'66435','Hillsboro High'),(12678,26,'67440','Hillwood High'),(12679,26,'68448','Cora Howe School'),(12680,26,'69450','Hume-Fogg High Magnet'),(12681,26,'6A452','Hunter\'s Lane High'),(12682,26,'6C497','MLK Jr. Magnet'),(12683,26,'6D532','McGavock High'),(12684,26,'6E550','Maplewood High'),(12685,26,'6F615','Overton High'),(12686,26,'6G632','Pearl-Cohn High'),(12687,26,'6H705','Stratford High'),(12688,26,'6I787','Whites Creek High'),(12689,26,'70142','Nashville Big Picture High-delivery via Martin Center'),(12690,26,'71181','Cameron College Prep'),(12691,26,'72211','Academy at Old Cochrill-delivery via Martin Center'),(12692,26,'73422','Academy at Hickory Hollow-delivery via Martin Center'),(12693,26,'74562','Middle College High-delivery via Martin Center'),(12694,26,'76613','Academy at Opry Mills-delivery via Martin Center'),(12695,26,'77655','Martin Professional Development Center'),(12696,26,'78508','LEAD Academy High'),(12697,18,'C','Checked Out'),(12698,18,'CT','Charged Temporary'),(12699,18,'H','On Hold Shelf'),(12700,18,'HP','Hold Pending'),(12701,18,'HT','On Hold Shelf'),(12702,18,'I','In Transit'),(12703,18,'IH','In Transit for Hold'),(12704,18,'IT','In Transit'),(12705,18,'L','Lost'),(12706,18,'LT','Lost Temporary'),(12707,18,'N','Ordered'),(12708,18,'O','On Order'),(12709,18,'R','Item Hold'),(12710,18,'RF','Received'),(12711,18,'RT','Item Hold Temp'),(12712,18,'S','On Shelf'),(12713,18,'SC','Library Use Only'),(12714,18,'SD','Shelving Delay'),(12715,18,'SG','Grubby'),(12716,18,'SI','On Display'),(12717,18,'SM','Missing'),(12718,18,'SO','Empty Case'),(12719,18,'SP','In Processing'),(12720,18,'SS','Temporarily Unavailable'),(12721,18,'ST','On Shelf Temporary'),(12722,18,'SU','In Repair'),(12723,18,'SW','Withdrawn'),(12724,18,'SX','Not on Shelf'),(12725,18,'T','Traced'),(12726,18,'TT','Traced Temporary'),(12739,30,'a','Adult'),(12740,30,'Adult','Adult'),(12741,30,'e','Everyone'),(12742,30,'Easy','Juvenile'),(12743,30,'j','Juvenile'),(12744,30,'Juvenile','Juvenile'),(12745,30,'s','Staff'),(12746,30,'Unknown','Unknown'),(12747,30,'x','Unknown'),(12748,30,'y','Young Adult'),(12749,30,'YA','Young Adult'),(12750,31,'a','Adult'),(12751,31,'Adult','Adult'),(12752,31,'e','Everyone'),(12753,31,'Easy','Kids'),(12754,31,'j','Kids'),(12755,31,'Juvenile','Kids'),(12756,31,'s','Staff'),(12757,31,'Unknown','Unknown'),(12758,31,'x','Unknown'),(12759,31,'y','Teen'),(12760,31,'YA','Teen'),(12761,29,'On Shelf In Process','O'),(12763,17,'^.AUDBK$','Audiobook'),(12764,26,'79118','Brick Church College Prep'),(12765,26,'ts','Collections and Technology Services'),(12766,25,'EPASS','Community Counts Passport'),(12767,17,'^.PASS2?$','Community Counts Passport'),(12768,25,'EINST','Musical Instrument'),(12769,17,'^.INST$','Musical Instrument'),(13049,25,'JCKIT','Kids Curriculum Kit'),(13050,25,'JCKITX','Kids Curriculum Kit'),(13051,25,'YCKIT','Teen Curriculum Kit'),(13052,25,'YCKITX','Teen Curriculum Kit'),(13053,28,'JCKIT','Kids Curriculum Kit'),(13054,28,'JCKITX','Kids Curriculum Kit'),(13055,28,'YCKIT','Teen Curriculum Kit'),(13056,28,'YCKITX','Teen Curriculum Kit'),(13057,17,'^.EVERY$','Everyone'),(13058,25,'JEVERY','Everyone'),(13059,18,'RG','Received as Gift'),(13062,25,'EGRUB','Grubby'),(13063,17,'^.GRUB$','Grubby'),(13064,26,'SREC','SEE RECORD'),(13065,25,'SEEREC','SEE RECORD'),(13066,17,'^SEEREC$','SEE RECORD'),(13070,24,'SPBK','Book'),(13074,24,'BKLP','Large Print Book'),(13075,24,'ORD','On Order'),(13082,25,'EPASS2','Community Counts Passport - Holdable'),(13083,32,'EAUDIOBOOK','eAudioBook'),(13084,32,'EBOOK','eBook'),(13085,32,'SBK','eBook'),(13086,33,'EAUDIOBOOK','8'),(13087,33,'EBOOK','8'),(13088,33,'SBK','8'),(13089,34,'EAUDIOBOOK','Audio Books'),(13090,34,'EBOOK','eBook'),(13091,34,'SBK','eBook'),(13092,32,'Book','eBook'),(13093,32,'GraphicNovel','eBook'),(13094,32,'Playaway','eAudioBook'),(13095,32,'Map','eBook'),(13097,33,'Book','8'),(13098,33,'GraphicNovel','8'),(13099,33,'Map','8'),(13100,33,'Playaway','8'),(13101,34,'Book','eBook'),(13102,34,'GraphicNovel','eBook'),(13103,34,'Map','eBook'),(13104,34,'Playaway','Audio Books'),(13105,25,'ZDNU1','Errror'),(13106,29,'Received partial','RP'),(13107,27,'^Z.*','Unknown'),(13108,30,'Z','Unknown'),(13109,31,'Z','Unknown'),(13110,17,'^ZDNU1$','Error'),(13111,25,'YBIGBK','Teen Big Book'),(13120,24,'HIDV','LSDHH DVD'),(13121,24,'HIVH','LSDHH VHS'),(13122,15,'00152','Davis Early Learning Center'),(13123,15,'01681','Ross Early Learning Center'),(13124,15,'03186','Casa Azafran Early Learning Center-delivery via Martin Center'),(13125,15,'04419','Cambridge Early Learning Center-delivery via Martin Center'),(13126,15,'10105','Amqui Elementary'),(13127,15,'11122','Lakeview Elementary'),(13128,15,'12135','Bellshire Elementary'),(13129,15,'13145','Norman Binkley Elementary'),(13130,15,'14165','Buena Vista Elementary'),(13131,15,'15175','Ida B. Wells Elementary'),(13132,15,'16184','Cane Ridge Elementary'),(13133,15,'17185','Lawrence Carter Elementary'),(13134,15,'18200','Chadwell Elementary'),(13135,15,'19205','Charlotte Park Elementary'),(13136,15,'1A215','Cockrill Elementary'),(13137,15,'1B225','Cole Elementary'),(13138,15,'1C230','Hattie Cotton Elementary'),(13139,15,'1D235','Crieve Hall Elementary'),(13140,15,'1E240','Cumberland Elementary'),(13141,15,'1F252','Dodson Elementary'),(13142,15,'1G265','Dupont Elementary'),(13143,15,'1H280','Eakin Elementary'),(13144,15,'1I308','Fall-Hamilton Elementary'),(13145,15,'1J310','Moss Elementary'),(13146,15,'1K315','Gateway Elementary'),(13147,15,'1L320','Glencliff Elementary'),(13148,15,'1M330','Glendale Elementary'),(13149,15,'1N335','Glengarry Elementary'),(13150,15,'1O340','Glenn Elementary'),(13151,15,'1P345','Glenview Elementary'),(13152,15,'1Q350','Goodlettsville Elementary'),(13153,15,'1R360','Gower Elementary'),(13154,15,'1S370','Granbery Elementary'),(13155,15,'1T375','Alex Green Elementary'),(13156,15,'1U380','Julia Green Elementary'),(13157,15,'1V395','Harpeth Valley Elementary'),(13158,15,'1W405','Haywood Elementary'),(13159,15,'1X415','Hermitage Elementary'),(13160,15,'1Y420','Hickman Elementary'),(13161,15,'1Z451','Hull-Jackson Montessori'),(13162,15,'20455','Inglewood Elementary'),(13163,15,'21460','Andrew Jackson Elementary'),(13164,15,'22465','Joelton Elementary'),(13165,15,'23485','Jones Paideia Magnet Elementary'),(13166,15,'24495','Tom Joy Elementary'),(13167,15,'25496','A. Z. Kelley Elementary'),(13168,15,'26500','Robert E Lillard Elementary'),(13169,15,'27505','Kirkpatrick Elementary'),(13170,15,'28520','Lockeland Elementary'),(13171,15,'29522','Ruby Major Elementary'),(13172,15,'2A530','McGavock Elementary'),(13173,15,'2B552','Maxwell Elementary'),(13174,15,'2C560','Dan Mills Elementary'),(13175,15,'2D575','Thomas A. Edison Elementary'),(13176,15,'2E576','Mt. View Elementary'),(13177,15,'2F590','Napier Elementary'),(13178,15,'2G595','Neely\'s Bend Elementary'),(13179,15,'2H610','Old Center Elementary'),(13180,15,'2I618','Paragon Mills Elementary'),(13181,15,'2J620','Park Avenue Elementary'),(13182,15,'2K640','Pennington Elementary'),(13183,15,'2L650','Percy Priest Elementary'),(13184,15,'2M670','Rosebank Elementary'),(13185,15,'2N682','Shayne Elementary'),(13186,15,'2O685','Shwab Elementary'),(13187,15,'2P686','Smith Springs Elementary'),(13188,15,'2Q690','Stanford Montessori Elementary'),(13189,15,'2R710','Stratton Elementary'),(13190,15,'2S715','Sylvan Park Elementary'),(13191,15,'2T717','Tulip Grove Elementary'),(13192,15,'2U725','Tusculum Elementary'),(13193,15,'2V735','Una Elementary'),(13194,15,'2W755','Warner Elementary'),(13195,15,'2X765','Waverly-Belmont Elementary'),(13196,15,'2Y775','Westmeade Elementary'),(13197,15,'2Z784','Robert Churchwell Elementary'),(13198,15,'30790','Whitsitt Elementary'),(13199,15,'40100','Margaret Allen Middle'),(13200,15,'41111','Antioch Middle'),(13201,15,'43120','Jere Baxter Middle'),(13202,15,'44130','Bellevue Middle'),(13203,15,'45238','Croft Middle'),(13204,15,'46260','Donelson Middle'),(13205,15,'47270','Dupont Hadley Middle'),(13206,15,'48275','Dupont Tyler Middle'),(13207,15,'49285','John Early Middle'),(13208,15,'4A296','East Nashville Magnet Middle'),(13209,15,'4B355','Goodlettsville Middle'),(13210,15,'4C365','Gra-Mar Middle'),(13211,15,'4D400','Haynes Middle'),(13212,15,'4E410','Head Middle Magnet'),(13213,15,'4F434','Hill, H. G. Middle'),(13214,15,'4G470','Joelton Middle'),(13215,15,'4H498','J F Kennedy Middle'),(13216,15,'4I510','Litton Middle'),(13217,15,'4J535','McKissack Middle'),(13218,15,'4K540','McMurray Middle'),(13219,15,'4L545','Madison Middle'),(13220,15,'4M551','Marshall Middle'),(13221,15,'4N555','Meigs Middle Magnet'),(13222,15,'4O563','J.T. Moore Middle'),(13223,15,'4P577','Apollo Middle'),(13224,15,'4S612','Oliver Middle'),(13225,15,'4T675','Rose Park Middle'),(13226,15,'4U730','Two Rivers Middle'),(13227,15,'4V770','West End Middle'),(13228,15,'4W783','Creswell Arts Middle'),(13229,15,'4X805','Wright Middle'),(13230,15,'60110','Antioch High'),(13231,15,'61182','Cane Ridge High'),(13232,15,'62242','Nashville School of the Arts'),(13233,15,'63290','East Nashville Magnet High'),(13234,15,'64325','Glencliff High'),(13235,15,'65397','Harris Hillman'),(13236,15,'66435','Hillsboro High'),(13237,15,'67440','Hillwood High'),(13238,15,'68448','Cora Howe School'),(13239,15,'69450','Hume-Fogg High Magnet'),(13240,15,'6A452','Hunter\'s Lane High'),(13241,15,'6C497','MLK Jr. Magnet'),(13242,15,'6D532','McGavock High'),(13243,15,'6E550','Maplewood High'),(13244,15,'6F615','Overton High'),(13245,15,'6G632','Pearl-Cohn High'),(13246,15,'6H705','Stratford High'),(13247,15,'6I787','Whites Creek High'),(13248,15,'70142','Nashville Big Picture High-delivery via Martin Center'),(13249,15,'71181','Cameron College Prep'),(13250,15,'72211','Academy at Old Cochrill-delivery via Martin Center'),(13251,15,'73422','Academy at Hickory Hollow-delivery via Martin Center'),(13252,15,'74562','Middle College High-delivery via Martin Center'),(13253,15,'76613','Academy at Opry Mills-delivery via Martin Center'),(13254,15,'77655','Martin Professional Development Center'),(13255,15,'78508','LEAD Academy High'),(13256,15,'79118','Brick Church College Prep'),(13257,24,'SDVD','DVD'),(13261,18,'RC','Received'),(13265,25,'JLUCKY','Kids Lucky Day'),(13266,26,'31278','Eagle View Elementary'),(13267,15,'31278','Eagle View Elementary'),(13268,16,'278EBOOK','Eagle View Elementary - E-Books'),(13269,25,'YEVERY','Teen Everyone'),(13270,17,'^.VOX$','Talking Book'),(13274,24,'VOX','Talking Book'),(13275,25,'JVOX','Kids Talking Book'),(13276,28,'JVOX','Kids Talking Book'),(13286,26,'75585','Murrell @ Glenn'),(13287,15,'75585','Murrell @ Glenn'),(13288,16,'585EBOOK','Murrell @ Glenn - E-Books'),(13292,26,'7G457','Independence Academy'),(13293,15,'7G457','Independence Academy'),(13294,16,'457EBOOK','Independence Academy - E-Books'),(13295,16,'211EBOOK','Academy at Old Cockrill - E-Books'),(13296,16,'422EBOOK','Academy at Hickory Hollow - E-Books'),(13297,16,'613EBOOK','Academy at Opry Mills - E-Books'),(13304,24,'SKTX','Exploratorium'),(13305,24,'OTHX','Other - non-holdable');
/*!40000 ALTER TABLE `translation_map_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `translation_maps`
--

DROP TABLE IF EXISTS `translation_maps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `translation_maps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `indexingProfileId` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `usesRegularExpressions` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `profileName` (`indexingProfileId`,`name`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `translation_maps`
--

LOCK TABLES `translation_maps` WRITE;
/*!40000 ALTER TABLE `translation_maps` DISABLE KEYS */;
INSERT INTO `translation_maps` VALUES (15,9,'location',0),(16,10,'location',0),(17,11,'collection',1),(18,11,'detailed_status',0),(24,11,'itype',0),(25,11,'shelf_location',0),(26,11,'location',0),(27,11,'audience',1),(28,11,'sub_location',0),(29,11,'status_codes',0),(30,11,'target_audience',0),(31,11,'target_audience_full',0),(32,10,'format',0),(33,10,'format_boost',0),(34,10,'format_category',0);
/*!40000 ALTER TABLE `translation_maps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `translation_terms`
--

DROP TABLE IF EXISTS `translation_terms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `translation_terms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `term` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `parameterNotes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `samplePageUrl` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `defaultText` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `isPublicFacing` tinyint(1) DEFAULT '0',
  `isAdminFacing` tinyint(1) DEFAULT '0',
  `isMetadata` tinyint(1) DEFAULT '0',
  `isAdminEnteredData` tinyint(1) DEFAULT '0',
  `lastUpdate` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `url` (`samplePageUrl`),
  KEY `term` (`term`(500))
) ENGINE=InnoDB AUTO_INCREMENT=3129 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=123;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `translation_terms`
--

LOCK TABLES `translation_terms` WRITE;
/*!40000 ALTER TABLE `translation_terms` DISABLE KEYS */;
/*!40000 ALTER TABLE `translation_terms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `translations`
--

DROP TABLE IF EXISTS `translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `translations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `termId` int(11) NOT NULL,
  `languageId` int(11) NOT NULL,
  `translation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `translated` tinyint(4) NOT NULL DEFAULT '0',
  `needsReview` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `term_language` (`termId`,`languageId`),
  KEY `translation_status` (`languageId`,`translated`)
) ENGINE=InnoDB AUTO_INCREMENT=3050 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=72;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `translations`
--

LOCK TABLES `translations` WRITE;
/*!40000 ALTER TABLE `translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usage_by_ip_address`
--

DROP TABLE IF EXISTS `usage_by_ip_address`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usage_by_ip_address` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ipAddress` varchar(25) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `numRequests` int(11) DEFAULT '0',
  `numBlockedRequests` int(11) DEFAULT '0',
  `numBlockedApiRequests` int(11) DEFAULT '0',
  `lastRequest` int(11) DEFAULT '0',
  `numLoginAttempts` int(11) DEFAULT '0',
  `numFailedLoginAttempts` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip` (`year`,`month`,`instance`,`ipAddress`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=744;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usage_by_ip_address`
--

LOCK TABLES `usage_by_ip_address` WRITE;
/*!40000 ALTER TABLE `usage_by_ip_address` DISABLE KEYS */;
/*!40000 ALTER TABLE `usage_by_ip_address` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usage_tracking`
--

DROP TABLE IF EXISTS `usage_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usage_tracking`
--

LOCK TABLES `usage_tracking` WRITE;
/*!40000 ALTER TABLE `usage_tracking` DISABLE KEYS */;
/*!40000 ALTER TABLE `usage_tracking` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(36) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `firstname` varchar(256) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `lastname` varchar(256) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `email` varchar(256) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `cat_username` varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cat_password` varchar(256) COLLATE utf8mb4_general_ci DEFAULT '',
  `created` datetime NOT NULL,
  `homeLocationId` int(11) NOT NULL COMMENT 'A link to the locations table for the users home location (branch) defined in millennium',
  `myLocation1Id` int(11) NOT NULL COMMENT 'A link to the locations table representing an alternate branch the users frequents or that is close by',
  `myLocation2Id` int(11) NOT NULL COMMENT 'A link to the locations table representing an alternate branch the users frequents or that is close by',
  `trackReadingHistory` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether or not Reading History should be tracked within VuFind.',
  `bypassAutoLogout` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether or not the user wants to bypass the automatic logout code on public workstations.',
  `displayName` varchar(256) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `disableCoverArt` tinyint(4) NOT NULL DEFAULT '0',
  `disableRecommendations` tinyint(4) NOT NULL DEFAULT '0',
  `phone` varchar(256) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `patronType` varchar(30) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `overdriveEmail` varchar(256) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `promptForOverdriveEmail` tinyint(4) DEFAULT '1',
  `preferredLibraryInterface` int(11) DEFAULT NULL,
  `initialReadingHistoryLoaded` tinyint(4) DEFAULT '0',
  `noPromptForUserReviews` tinyint(1) DEFAULT '0',
  `source` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'ils',
  `interfaceLanguage` varchar(3) COLLATE utf8mb4_general_ci DEFAULT 'en',
  `searchPreferenceLanguage` tinyint(1) DEFAULT '-1',
  `rememberHoldPickupLocation` tinyint(1) DEFAULT '0',
  `alwaysHoldNextAvailable` tinyint(1) DEFAULT '0',
  `lockedFacets` mediumtext COLLATE utf8mb4_general_ci,
  `lastListUsed` int(11) DEFAULT '-1',
  `lastLoginValidation` int(11) DEFAULT '-1',
  `alternateLibraryCard` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '',
  `alternateLibraryCardPassword` varchar(256) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `updateMessage` mediumtext COLLATE utf8mb4_general_ci,
  `updateMessageIsError` tinyint(4) DEFAULT NULL,
  `pickupLocationId` int(11) DEFAULT '0',
  `lastReadingHistoryUpdate` int(11) DEFAULT '0',
  `holdInfoLastLoaded` int(11) DEFAULT '0',
  `checkoutInfoLastLoaded` int(11) DEFAULT '0',
  `proPayPayerAccountId` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`source`,`username`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (0,'nyt_user','nyt_password','New York Times','The New York Times','','nyt_user','nyt_password','2019-11-19 01:57:54',1,1,1,0,0,'The New York Times',0,1,'','','',1,NULL,0,0,'admin','en',-1,0,0,NULL,-1,-1,'','',NULL,NULL,0,0,0,0,NULL),(1,'aspen_admin','password','Aspen','Administrator','','aspen_admin','password','2019-11-19 01:57:54',1,1,1,0,0,'A. Administrator',0,0,'','','',1,NULL,0,0,'admin','en',-1,0,0,NULL,-1,-1,'','',NULL,NULL,0,0,0,0,NULL);
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_account_summary`
--

DROP TABLE IF EXISTS `user_account_summary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_account_summary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `userId` int(11) NOT NULL,
  `numCheckedOut` int(11) DEFAULT '0',
  `numOverdue` int(11) DEFAULT '0',
  `numAvailableHolds` int(11) DEFAULT '0',
  `numUnavailableHolds` int(11) DEFAULT '0',
  `totalFines` float DEFAULT '0',
  `expirationDate` bigint(20) DEFAULT '0',
  `lastLoaded` int(11) DEFAULT NULL,
  `numCheckoutsRemaining` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `source` (`source`,`userId`)
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=963;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_account_summary`
--

LOCK TABLES `user_account_summary` WRITE;
/*!40000 ALTER TABLE `user_account_summary` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_account_summary` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_axis360_usage`
--

DROP TABLE IF EXISTS `user_axis360_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_axis360_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `userId` int(11) NOT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `usageCount` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `instance` (`instance`,`userId`,`year`,`month`),
  KEY `instance_2` (`instance`,`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=2340;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_axis360_usage`
--

LOCK TABLES `user_axis360_usage` WRITE;
/*!40000 ALTER TABLE `user_axis360_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_axis360_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_checkout`
--

DROP TABLE IF EXISTS `user_checkout`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_checkout` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `source` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `userId` int(11) NOT NULL,
  `sourceId` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `recordId` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `shortId` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `itemId` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `itemIndex` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `renewalId` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `barcode` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `title` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `title2` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `author` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `callNumber` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `volume` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `checkoutDate` int(11) DEFAULT NULL,
  `dueDate` bigint(20) DEFAULT NULL,
  `renewCount` int(11) DEFAULT NULL,
  `canRenew` tinyint(1) DEFAULT NULL,
  `autoRenew` tinyint(1) DEFAULT NULL,
  `autoRenewError` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `maxRenewals` int(11) DEFAULT NULL,
  `fine` float DEFAULT NULL,
  `returnClaim` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `holdQueueLength` int(11) DEFAULT NULL,
  `renewalDate` bigint(20) DEFAULT NULL,
  `allowDownload` tinyint(1) DEFAULT NULL,
  `overdriveRead` tinyint(1) DEFAULT NULL,
  `overdriveReadUrl` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `overdriveListen` tinyint(1) DEFAULT NULL,
  `overdriveListenUrl` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `overdriveVideo` tinyint(1) DEFAULT NULL,
  `overdriveVideoUrl` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `formatSelected` tinyint(1) DEFAULT NULL,
  `selectedFormatName` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `selectedFormatValue` varchar(25) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `canReturnEarly` tinyint(1) DEFAULT NULL,
  `supplementalMaterials` mediumtext COLLATE utf8mb4_general_ci,
  `formats` mediumtext COLLATE utf8mb4_general_ci,
  `downloadUrl` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `accessOnlineUrl` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `transactionId` varchar(40) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `coverUrl` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `format` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `renewIndicator` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `groupedWorkId` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `overdriveMagazine` tinyint(1) DEFAULT NULL,
  `isSupplemental` tinyint(1) DEFAULT '0',
  `linkUrl` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `renewError` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`,`source`,`recordId`),
  KEY `userId_2` (`userId`,`groupedWorkId`),
  KEY `userId_3` (`userId`,`source`,`recordId`),
  KEY `userId_4` (`userId`,`groupedWorkId`)
) ENGINE=InnoDB AUTO_INCREMENT=989 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=862;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_checkout`
--

LOCK TABLES `user_checkout` WRITE;
/*!40000 ALTER TABLE `user_checkout` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_checkout` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_cloud_library_usage`
--

DROP TABLE IF EXISTS `user_cloud_library_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_cloud_library_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `usageCount` int(11) DEFAULT NULL,
  `instance` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`userId`,`year`,`month`),
  UNIQUE KEY `instance_2` (`instance`,`userId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=3276;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_cloud_library_usage`
--

LOCK TABLES `user_cloud_library_usage` WRITE;
/*!40000 ALTER TABLE `user_cloud_library_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_cloud_library_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_ebsco_eds_usage`
--

DROP TABLE IF EXISTS `user_ebsco_eds_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_ebsco_eds_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `month` int(2) NOT NULL,
  `year` int(4) NOT NULL,
  `usageCount` int(11) DEFAULT NULL,
  `instance` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`userId`,`year`,`month`),
  UNIQUE KEY `instance_2` (`instance`,`userId`,`year`,`month`),
  UNIQUE KEY `instance_3` (`instance`,`userId`,`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=4096;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_ebsco_eds_usage`
--

LOCK TABLES `user_ebsco_eds_usage` WRITE;
/*!40000 ALTER TABLE `user_ebsco_eds_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_ebsco_eds_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_events_usage`
--

DROP TABLE IF EXISTS `user_events_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_events_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `type` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `source` int(11) NOT NULL,
  `month` int(2) NOT NULL,
  `year` int(4) NOT NULL,
  `usageCount` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`,`source`,`year`,`month`,`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_events_usage`
--

LOCK TABLES `user_events_usage` WRITE;
/*!40000 ALTER TABLE `user_events_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_events_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_hold`
--

DROP TABLE IF EXISTS `user_hold`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_hold` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `source` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `userId` int(11) NOT NULL,
  `sourceId` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `recordId` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `shortId` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `itemId` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `title` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `title2` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `author` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `volume` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `callNumber` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `available` tinyint(1) DEFAULT NULL,
  `cancelable` tinyint(1) DEFAULT NULL,
  `cancelId` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `locationUpdateable` tinyint(1) DEFAULT NULL,
  `pickupLocationId` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pickupLocationName` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `position` int(11) DEFAULT NULL,
  `holdQueueLength` int(11) DEFAULT NULL,
  `createDate` int(11) DEFAULT NULL,
  `availableDate` int(11) DEFAULT NULL,
  `expirationDate` int(11) DEFAULT NULL,
  `automaticCancellationDate` int(11) DEFAULT NULL,
  `frozen` tinyint(1) DEFAULT NULL,
  `canFreeze` tinyint(1) DEFAULT NULL,
  `reactivateDate` int(11) DEFAULT NULL,
  `groupedWorkId` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `format` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `coverUrl` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `linkUrl` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`,`source`,`recordId`),
  KEY `userId_2` (`userId`,`groupedWorkId`),
  KEY `userId_3` (`userId`,`source`,`recordId`),
  KEY `userId_4` (`userId`,`groupedWorkId`)
) ENGINE=InnoDB AUTO_INCREMENT=1739 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=712;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_hold`
--

LOCK TABLES `user_hold` WRITE;
/*!40000 ALTER TABLE `user_hold` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_hold` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_hoopla_usage`
--

DROP TABLE IF EXISTS `user_hoopla_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_hoopla_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `usageCount` int(11) DEFAULT NULL,
  `instance` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`userId`,`year`,`month`),
  UNIQUE KEY `instance_2` (`instance`,`userId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_hoopla_usage`
--

LOCK TABLES `user_hoopla_usage` WRITE;
/*!40000 ALTER TABLE `user_hoopla_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_hoopla_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_ils_usage`
--

DROP TABLE IF EXISTS `user_ils_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_ils_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `indexingProfileId` int(11) NOT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `usageCount` int(11) DEFAULT '0',
  `selfRegistrationCount` int(11) DEFAULT '0',
  `pdfDownloadCount` int(11) DEFAULT '0',
  `supplementalFileDownloadCount` int(11) DEFAULT '0',
  `pdfViewCount` int(11) DEFAULT '0',
  `instance` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`userId`,`indexingProfileId`,`year`,`month`),
  UNIQUE KEY `instance_2` (`instance`,`userId`,`indexingProfileId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=268;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_ils_usage`
--

LOCK TABLES `user_ils_usage` WRITE;
/*!40000 ALTER TABLE `user_ils_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_ils_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_link`
--

DROP TABLE IF EXISTS `user_link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `primaryAccountId` int(11) NOT NULL,
  `linkedAccountId` int(11) NOT NULL,
  `linkingDisabled` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_link` (`primaryAccountId`,`linkedAccountId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_link`
--

LOCK TABLES `user_link` WRITE;
/*!40000 ALTER TABLE `user_link` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_link` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_link_blocks`
--

DROP TABLE IF EXISTS `user_link_blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_link_blocks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `primaryAccountId` int(10) unsigned NOT NULL,
  `blockedLinkAccountId` int(10) unsigned DEFAULT NULL COMMENT 'A specific account primaryAccountId will not be linked to.',
  `blockLinking` tinyint(3) unsigned DEFAULT NULL COMMENT 'Indicates primaryAccountId will not be linked to any other accounts.',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_link_blocks`
--

LOCK TABLES `user_link_blocks` WRITE;
/*!40000 ALTER TABLE `user_link_blocks` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_link_blocks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_list`
--

DROP TABLE IF EXISTS `user_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_general_ci,
  `public` int(11) NOT NULL DEFAULT '0',
  `dateUpdated` int(11) DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT '0',
  `created` int(11) DEFAULT NULL,
  `defaultSort` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `importedFrom` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `searchable` tinyint(1) DEFAULT '0',
  `nytListModified` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_list`
--

LOCK TABLES `user_list` WRITE;
/*!40000 ALTER TABLE `user_list` DISABLE KEYS */;
INSERT INTO `user_list` VALUES (1,1,'My Favorites','',0,1634303449,0,1550810306,NULL,NULL,1,NULL);
/*!40000 ALTER TABLE `user_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_list_entry`
--

DROP TABLE IF EXISTS `user_list_entry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_list_entry` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sourceId` varchar(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `listId` int(11) DEFAULT NULL,
  `notes` longtext COLLATE utf8mb4_general_ci,
  `dateAdded` int(11) DEFAULT NULL,
  `weight` int(11) DEFAULT NULL,
  `source` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'GroupedWork',
  `importedFrom` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `title` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `groupedWorkPermanentId` (`sourceId`),
  KEY `listId` (`listId`),
  KEY `source` (`source`,`sourceId`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_list_entry`
--

LOCK TABLES `user_list_entry` WRITE;
/*!40000 ALTER TABLE `user_list_entry` DISABLE KEYS */;
INSERT INTO `user_list_entry` VALUES (1,'915e61a2-b19c-2aff-3449-78f8f45a29d5',NULL,'',1550810306,NULL,'GroupedWork',NULL,'romeo and juliet'),(2,'915e61a2-b19c-2aff-3449-78f8f45a29d5',1,'',1550859715,NULL,'GroupedWork',NULL,'romeo and juliet');
/*!40000 ALTER TABLE `user_list_entry` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_messages`
--

DROP TABLE IF EXISTS `user_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) DEFAULT NULL,
  `messageType` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `messageLevel` enum('success','info','warning','danger') COLLATE utf8mb4_general_ci DEFAULT 'info',
  `message` longtext COLLATE utf8mb4_general_ci,
  `isDismissed` tinyint(1) DEFAULT '0',
  `action1` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `action1Title` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `action2` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `action2Title` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`,`isDismissed`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=4096;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_messages`
--

LOCK TABLES `user_messages` WRITE;
/*!40000 ALTER TABLE `user_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_not_interested`
--

DROP TABLE IF EXISTS `user_not_interested`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_not_interested` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `dateMarked` int(11) DEFAULT NULL,
  `groupedRecordPermanentId` varchar(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_not_interested`
--

LOCK TABLES `user_not_interested` WRITE;
/*!40000 ALTER TABLE `user_not_interested` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_not_interested` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_open_archives_usage`
--

DROP TABLE IF EXISTS `user_open_archives_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_open_archives_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `openArchivesCollectionId` int(11) NOT NULL,
  `year` int(4) NOT NULL,
  `usageCount` int(11) DEFAULT NULL,
  `month` int(2) NOT NULL DEFAULT '4',
  `instance` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`openArchivesCollectionId`,`userId`,`year`,`month`),
  UNIQUE KEY `instance_2` (`instance`,`openArchivesCollectionId`,`userId`,`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_open_archives_usage`
--

LOCK TABLES `user_open_archives_usage` WRITE;
/*!40000 ALTER TABLE `user_open_archives_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_open_archives_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_overdrive_usage`
--

DROP TABLE IF EXISTS `user_overdrive_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_overdrive_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `usageCount` int(11) DEFAULT NULL,
  `instance` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`userId`,`year`,`month`),
  UNIQUE KEY `instance_2` (`instance`,`userId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=3276;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_overdrive_usage`
--

LOCK TABLES `user_overdrive_usage` WRITE;
/*!40000 ALTER TABLE `user_overdrive_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_overdrive_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_payments`
--

DROP TABLE IF EXISTS `user_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) DEFAULT NULL,
  `paymentType` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `orderId` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `completed` tinyint(1) DEFAULT NULL,
  `finesPaid` varchar(8192) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `totalPaid` float DEFAULT NULL,
  `transactionDate` int(11) DEFAULT NULL,
  `cancelled` tinyint(1) DEFAULT NULL,
  `error` tinyint(1) DEFAULT NULL,
  `message` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `paymentType` (`paymentType`,`orderId`),
  KEY `userId` (`userId`,`paymentType`,`completed`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=431;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_payments`
--

LOCK TABLES `user_payments` WRITE;
/*!40000 ALTER TABLE `user_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_rbdigital_usage`
--

DROP TABLE IF EXISTS `user_rbdigital_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_rbdigital_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `usageCount` int(11) DEFAULT NULL,
  `instance` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`userId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_rbdigital_usage`
--

LOCK TABLES `user_rbdigital_usage` WRITE;
/*!40000 ALTER TABLE `user_rbdigital_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_rbdigital_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_reading_history_work`
--

DROP TABLE IF EXISTS `user_reading_history_work`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_reading_history_work` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL COMMENT 'The id of the user who checked out the item',
  `groupedWorkPermanentId` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `source` varchar(25) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'The source of the record being checked out',
  `sourceId` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'The id of the item that item that was checked out within the source',
  `title` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'The title of the item in case this is ever deleted',
  `author` varchar(75) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'The author of the item in case this is ever deleted',
  `format` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'The format of the item in case this is ever deleted',
  `checkOutDate` int(11) NOT NULL COMMENT 'The first day we detected that the item was checked out to the patron',
  `checkInDate` bigint(20) DEFAULT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`,`checkOutDate`),
  KEY `userId_2` (`userId`,`checkInDate`),
  KEY `userId_3` (`userId`,`title`),
  KEY `userId_4` (`userId`,`author`),
  KEY `sourceId` (`sourceId`),
  KEY `user_work` (`userId`,`groupedWorkPermanentId`),
  KEY `groupedWorkPermanentId` (`groupedWorkPermanentId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='The reading history for patrons';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_reading_history_work`
--

LOCK TABLES `user_reading_history_work` WRITE;
/*!40000 ALTER TABLE `user_reading_history_work` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_reading_history_work` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_roles`
--

DROP TABLE IF EXISTS `user_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_roles` (
  `userId` int(11) NOT NULL,
  `roleId` int(11) NOT NULL,
  PRIMARY KEY (`userId`,`roleId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Links users with roles so users can perform administration f';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_roles`
--

LOCK TABLES `user_roles` WRITE;
/*!40000 ALTER TABLE `user_roles` DISABLE KEYS */;
INSERT INTO `user_roles` VALUES (1,1),(1,2),(2,13);
/*!40000 ALTER TABLE `user_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_sideload_usage`
--

DROP TABLE IF EXISTS `user_sideload_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_sideload_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `sideloadId` int(11) NOT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `usageCount` int(11) DEFAULT '0',
  `instance` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance` (`instance`,`userId`,`sideloadId`,`year`,`month`),
  UNIQUE KEY `instance_2` (`instance`,`userId`,`sideloadId`,`year`,`month`),
  KEY `year` (`year`,`month`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=5461;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_sideload_usage`
--

LOCK TABLES `user_sideload_usage` WRITE;
/*!40000 ALTER TABLE `user_sideload_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_sideload_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_staff_settings`
--

DROP TABLE IF EXISTS `user_staff_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_staff_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `materialsRequestReplyToAddress` varchar(70) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `materialsRequestEmailSignature` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userId_UNIQUE` (`userId`),
  KEY `userId` (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_staff_settings`
--

LOCK TABLES `user_staff_settings` WRITE;
/*!40000 ALTER TABLE `user_staff_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_staff_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_website_usage`
--

DROP TABLE IF EXISTS `user_website_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_website_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `websiteId` int(11) NOT NULL,
  `month` int(2) NOT NULL,
  `year` int(4) NOT NULL,
  `usageCount` int(11) DEFAULT NULL,
  `instance` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `instance` (`instance`,`websiteId`,`year`,`month`),
  KEY `instance_2` (`instance`,`websiteId`,`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=8192;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_website_usage`
--

LOCK TABLES `user_website_usage` WRITE;
/*!40000 ALTER TABLE `user_website_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_website_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_work_review`
--

DROP TABLE IF EXISTS `user_work_review`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_work_review` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupedRecordPermanentId` varchar(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  `rating` tinyint(1) DEFAULT NULL,
  `review` longtext COLLATE utf8mb4_general_ci,
  `dateRated` int(11) DEFAULT NULL,
  `importedFrom` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `groupedRecordPermanentId` (`groupedRecordPermanentId`),
  KEY `userId` (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_work_review`
--

LOCK TABLES `user_work_review` WRITE;
/*!40000 ALTER TABLE `user_work_review` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_work_review` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `variables`
--

DROP TABLE IF EXISTS `variables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `variables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) COLLATE utf8mb4_general_ci NOT NULL,
  `value` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_2` (`name`),
  UNIQUE KEY `name_3` (`name`),
  KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `variables`
--

LOCK TABLES `variables` WRITE;
/*!40000 ALTER TABLE `variables` DISABLE KEYS */;
INSERT INTO `variables` VALUES (1,'lastHooplaExport','false'),(2,'validateChecksumsFromDisk','false'),(3,'offline_mode_when_offline_login_allowed','false'),(4,'fullReindexIntervalWarning','86400'),(5,'fullReindexIntervalCritical','129600'),(6,'bypass_export_validation','0'),(7,'last_validatemarcexport_time',NULL),(8,'last_export_valid','1'),(9,'record_grouping_running','false'),(10,'last_grouping_time',NULL),(25,'partial_reindex_running','true'),(26,'last_reindex_time',NULL),(27,'lastPartialReindexFinish',NULL),(29,'full_reindex_running','false'),(37,'lastFullReindexFinish',NULL),(44,'num_title_in_unique_sitemap','20000'),(45,'num_titles_in_most_popular_sitemap','20000'),(46,'lastRbdigitalExport',NULL);
/*!40000 ALTER TABLE `variables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `web_builder_audience`
--

DROP TABLE IF EXISTS `web_builder_audience`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `web_builder_audience` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=2340;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `web_builder_audience`
--

LOCK TABLES `web_builder_audience` WRITE;
/*!40000 ALTER TABLE `web_builder_audience` DISABLE KEYS */;
INSERT INTO `web_builder_audience` VALUES (1,'Adults'),(4,'Children'),(7,'Everyone'),(5,'Parents'),(6,'Seniors'),(2,'Teens'),(3,'Tweens');
/*!40000 ALTER TABLE `web_builder_audience` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `web_builder_basic_page`
--

DROP TABLE IF EXISTS `web_builder_basic_page`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `web_builder_basic_page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `urlAlias` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contents` longtext COLLATE utf8mb4_general_ci,
  `teaser` varchar(512) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lastUpdate` int(11) DEFAULT '0',
  `requireLogin` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `web_builder_basic_page`
--

LOCK TABLES `web_builder_basic_page` WRITE;
/*!40000 ALTER TABLE `web_builder_basic_page` DISABLE KEYS */;
/*!40000 ALTER TABLE `web_builder_basic_page` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `web_builder_basic_page_access`
--

DROP TABLE IF EXISTS `web_builder_basic_page_access`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `web_builder_basic_page_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `basicPageId` int(11) NOT NULL,
  `patronTypeId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `basicPageId` (`basicPageId`,`patronTypeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `web_builder_basic_page_access`
--

LOCK TABLES `web_builder_basic_page_access` WRITE;
/*!40000 ALTER TABLE `web_builder_basic_page_access` DISABLE KEYS */;
/*!40000 ALTER TABLE `web_builder_basic_page_access` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `web_builder_basic_page_audience`
--

DROP TABLE IF EXISTS `web_builder_basic_page_audience`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `web_builder_basic_page_audience` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `basicPageId` int(11) NOT NULL,
  `audienceId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `basicPageId` (`basicPageId`,`audienceId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `web_builder_basic_page_audience`
--

LOCK TABLES `web_builder_basic_page_audience` WRITE;
/*!40000 ALTER TABLE `web_builder_basic_page_audience` DISABLE KEYS */;
/*!40000 ALTER TABLE `web_builder_basic_page_audience` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `web_builder_basic_page_category`
--

DROP TABLE IF EXISTS `web_builder_basic_page_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `web_builder_basic_page_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `basicPageId` int(11) NOT NULL,
  `categoryId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `basicPageId` (`basicPageId`,`categoryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `web_builder_basic_page_category`
--

LOCK TABLES `web_builder_basic_page_category` WRITE;
/*!40000 ALTER TABLE `web_builder_basic_page_category` DISABLE KEYS */;
/*!40000 ALTER TABLE `web_builder_basic_page_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `web_builder_category`
--

DROP TABLE IF EXISTS `web_builder_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `web_builder_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `web_builder_category`
--

LOCK TABLES `web_builder_category` WRITE;
/*!40000 ALTER TABLE `web_builder_category` DISABLE KEYS */;
INSERT INTO `web_builder_category` VALUES (10,'Arts and Music'),(1,'eBooks and Audiobooks'),(9,'Homework Help'),(2,'Languages and Culture'),(11,'Library Documents and Policies'),(3,'Lifelong Learning'),(8,'Local History'),(4,'Newspapers and Magazines'),(5,'Reading Recommendations'),(6,'Reference and Research'),(7,'Video Streaming');
/*!40000 ALTER TABLE `web_builder_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `web_builder_custom_form`
--

DROP TABLE IF EXISTS `web_builder_custom_form`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `web_builder_custom_form` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `urlAlias` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `emailResultsTo` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `requireLogin` tinyint(1) DEFAULT NULL,
  `introText` longtext COLLATE utf8mb4_general_ci,
  `submissionResultText` longtext COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `web_builder_custom_form`
--

LOCK TABLES `web_builder_custom_form` WRITE;
/*!40000 ALTER TABLE `web_builder_custom_form` DISABLE KEYS */;
/*!40000 ALTER TABLE `web_builder_custom_form` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `web_builder_custom_form_field`
--

DROP TABLE IF EXISTS `web_builder_custom_form_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `web_builder_custom_form_field` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `formId` int(11) NOT NULL,
  `weight` int(11) DEFAULT '0',
  `label` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '',
  `fieldType` int(11) NOT NULL DEFAULT '0',
  `enumValues` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `defaultValue` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `required` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `formId` (`formId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=16384;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `web_builder_custom_form_field`
--

LOCK TABLES `web_builder_custom_form_field` WRITE;
/*!40000 ALTER TABLE `web_builder_custom_form_field` DISABLE KEYS */;
/*!40000 ALTER TABLE `web_builder_custom_form_field` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `web_builder_custom_from_submission`
--

DROP TABLE IF EXISTS `web_builder_custom_from_submission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `web_builder_custom_from_submission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `formId` int(11) NOT NULL,
  `libraryId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `dateSubmitted` int(11) NOT NULL,
  `submission` longtext COLLATE utf8mb4_general_ci,
  `isRead` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `formId` (`formId`,`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `web_builder_custom_from_submission`
--

LOCK TABLES `web_builder_custom_from_submission` WRITE;
/*!40000 ALTER TABLE `web_builder_custom_from_submission` DISABLE KEYS */;
/*!40000 ALTER TABLE `web_builder_custom_from_submission` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `web_builder_menu`
--

DROP TABLE IF EXISTS `web_builder_menu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `web_builder_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `parentMenuId` int(11) DEFAULT '-1',
  `url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `weight` int(11) DEFAULT '0',
  `showWhen` tinyint(4) DEFAULT '0',
  `libraryId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parentMenuId` (`parentMenuId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `web_builder_menu`
--

LOCK TABLES `web_builder_menu` WRITE;
/*!40000 ALTER TABLE `web_builder_menu` DISABLE KEYS */;
/*!40000 ALTER TABLE `web_builder_menu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `web_builder_portal_cell`
--

DROP TABLE IF EXISTS `web_builder_portal_cell`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `web_builder_portal_cell` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `portalRowId` int(11) DEFAULT NULL,
  `widthTiny` int(11) DEFAULT NULL,
  `widthXs` int(11) DEFAULT NULL,
  `widthSm` int(11) DEFAULT NULL,
  `widthMd` int(11) DEFAULT NULL,
  `widthLg` int(11) DEFAULT NULL,
  `horizontalJustification` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `verticalAlignment` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sourceType` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sourceId` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `weight` int(11) DEFAULT '0',
  `markdown` longtext COLLATE utf8mb4_general_ci,
  `sourceInfo` varchar(512) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '',
  `frameHeight` int(11) DEFAULT '0',
  `makeCellAccordion` tinyint(4) NOT NULL DEFAULT '0',
  `imageURL` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pdfView` varchar(12) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `colorScheme` varchar(25) COLLATE utf8mb4_general_ci DEFAULT 'default',
  `invertColor` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `portalRowId` (`portalRowId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `web_builder_portal_cell`
--

LOCK TABLES `web_builder_portal_cell` WRITE;
/*!40000 ALTER TABLE `web_builder_portal_cell` DISABLE KEYS */;
/*!40000 ALTER TABLE `web_builder_portal_cell` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `web_builder_portal_page`
--

DROP TABLE IF EXISTS `web_builder_portal_page`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `web_builder_portal_page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `urlAlias` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lastUpdate` int(11) DEFAULT '0',
  `requireLogin` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=16384;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `web_builder_portal_page`
--

LOCK TABLES `web_builder_portal_page` WRITE;
/*!40000 ALTER TABLE `web_builder_portal_page` DISABLE KEYS */;
/*!40000 ALTER TABLE `web_builder_portal_page` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `web_builder_portal_page_access`
--

DROP TABLE IF EXISTS `web_builder_portal_page_access`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `web_builder_portal_page_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `portalPageId` int(11) NOT NULL,
  `patronTypeId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `portalPageId` (`portalPageId`,`patronTypeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `web_builder_portal_page_access`
--

LOCK TABLES `web_builder_portal_page_access` WRITE;
/*!40000 ALTER TABLE `web_builder_portal_page_access` DISABLE KEYS */;
/*!40000 ALTER TABLE `web_builder_portal_page_access` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `web_builder_portal_page_audience`
--

DROP TABLE IF EXISTS `web_builder_portal_page_audience`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `web_builder_portal_page_audience` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `portalPageId` int(11) NOT NULL,
  `audienceId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `portalPageId` (`portalPageId`,`audienceId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `web_builder_portal_page_audience`
--

LOCK TABLES `web_builder_portal_page_audience` WRITE;
/*!40000 ALTER TABLE `web_builder_portal_page_audience` DISABLE KEYS */;
/*!40000 ALTER TABLE `web_builder_portal_page_audience` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `web_builder_portal_page_category`
--

DROP TABLE IF EXISTS `web_builder_portal_page_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `web_builder_portal_page_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `portalPageId` int(11) NOT NULL,
  `categoryId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `portalPageId` (`portalPageId`,`categoryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `web_builder_portal_page_category`
--

LOCK TABLES `web_builder_portal_page_category` WRITE;
/*!40000 ALTER TABLE `web_builder_portal_page_category` DISABLE KEYS */;
/*!40000 ALTER TABLE `web_builder_portal_page_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `web_builder_portal_row`
--

DROP TABLE IF EXISTS `web_builder_portal_row`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `web_builder_portal_row` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `portalPageId` int(11) DEFAULT NULL,
  `rowTitle` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `weight` int(11) DEFAULT '0',
  `makeAccordion` tinyint(1) DEFAULT '0',
  `colorScheme` varchar(25) COLLATE utf8mb4_general_ci DEFAULT 'default',
  `invertColor` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `portalPageId` (`portalPageId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=8192;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `web_builder_portal_row`
--

LOCK TABLES `web_builder_portal_row` WRITE;
/*!40000 ALTER TABLE `web_builder_portal_row` DISABLE KEYS */;
/*!40000 ALTER TABLE `web_builder_portal_row` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `web_builder_resource`
--

DROP TABLE IF EXISTS `web_builder_resource`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `web_builder_resource` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `logo` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `featured` tinyint(1) NOT NULL DEFAULT '0',
  `requiresLibraryCard` tinyint(1) NOT NULL DEFAULT '0',
  `description` longtext COLLATE utf8mb4_general_ci,
  `teaser` varchar(512) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lastUpdate` int(11) DEFAULT '0',
  `inLibraryUseOnly` tinyint(1) DEFAULT '0',
  `openInNewTab` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `featured` (`featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `web_builder_resource`
--

LOCK TABLES `web_builder_resource` WRITE;
/*!40000 ALTER TABLE `web_builder_resource` DISABLE KEYS */;
/*!40000 ALTER TABLE `web_builder_resource` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `web_builder_resource_audience`
--

DROP TABLE IF EXISTS `web_builder_resource_audience`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `web_builder_resource_audience` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `webResourceId` int(11) NOT NULL,
  `audienceId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `webResourceId` (`webResourceId`,`audienceId`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=2340;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `web_builder_resource_audience`
--

LOCK TABLES `web_builder_resource_audience` WRITE;
/*!40000 ALTER TABLE `web_builder_resource_audience` DISABLE KEYS */;
/*!40000 ALTER TABLE `web_builder_resource_audience` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `web_builder_resource_category`
--

DROP TABLE IF EXISTS `web_builder_resource_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `web_builder_resource_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `webResourceId` int(11) NOT NULL,
  `categoryId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `webResourceId` (`webResourceId`,`categoryId`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AVG_ROW_LENGTH=8192;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `web_builder_resource_category`
--

LOCK TABLES `web_builder_resource_category` WRITE;
/*!40000 ALTER TABLE `web_builder_resource_category` DISABLE KEYS */;
/*!40000 ALTER TABLE `web_builder_resource_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `website_index_log`
--

DROP TABLE IF EXISTS `website_index_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `website_index_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log',
  `websiteName` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `notes` mediumtext COLLATE utf8mb4_general_ci COMMENT 'Additional information about the run',
  `numPages` int(11) DEFAULT '0',
  `numAdded` int(11) DEFAULT '0',
  `numDeleted` int(11) DEFAULT '0',
  `numUpdated` int(11) DEFAULT '0',
  `numErrors` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `websiteName` (`websiteName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `website_index_log`
--

LOCK TABLES `website_index_log` WRITE;
/*!40000 ALTER TABLE `website_index_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `website_index_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `website_indexing_settings`
--

DROP TABLE IF EXISTS `website_indexing_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `website_indexing_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(75) COLLATE utf8mb4_general_ci NOT NULL,
  `searchCategory` varchar(75) COLLATE utf8mb4_general_ci NOT NULL,
  `siteUrl` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `indexFrequency` enum('hourly','daily','weekly','monthly','yearly','once') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lastIndexed` int(11) DEFAULT NULL,
  `pathsToExclude` longtext COLLATE utf8mb4_general_ci,
  `pageTitleExpression` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '',
  `descriptionExpression` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '',
  `deleted` tinyint(1) DEFAULT '0',
  `maxPagesToIndex` int(11) DEFAULT '2500',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `lastIndexed` (`lastIndexed`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `website_indexing_settings`
--

LOCK TABLES `website_indexing_settings` WRITE;
/*!40000 ALTER TABLE `website_indexing_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `website_indexing_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `website_page_usage`
--

DROP TABLE IF EXISTS `website_page_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `website_page_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `webPageId` int(11) DEFAULT NULL,
  `month` int(2) NOT NULL,
  `year` int(4) NOT NULL,
  `timesViewedInSearch` int(11) NOT NULL,
  `timesUsed` int(11) NOT NULL,
  `instance` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `instance` (`instance`,`webPageId`,`year`,`month`),
  KEY `instance_2` (`instance`,`webPageId`,`year`,`month`),
  KEY `instance_3` (`instance`,`webPageId`,`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `website_page_usage`
--

LOCK TABLES `website_page_usage` WRITE;
/*!40000 ALTER TABLE `website_page_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `website_page_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `website_pages`
--

DROP TABLE IF EXISTS `website_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `website_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `websiteId` int(11) NOT NULL,
  `url` varchar(600) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `checksum` bigint(20) DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT NULL,
  `firstDetected` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `url` (`url`),
  KEY `websiteId` (`websiteId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `website_pages`
--

LOCK TABLES `website_pages` WRITE;
/*!40000 ALTER TABLE `website_pages` DISABLE KEYS */;
/*!40000 ALTER TABLE `website_pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `worldpay_settings`
--

DROP TABLE IF EXISTS `worldpay_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `worldpay_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `merchantCode` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `settleCode` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `worldpay_settings`
--

LOCK TABLES `worldpay_settings` WRITE;
/*!40000 ALTER TABLE `worldpay_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `worldpay_settings` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2021-10-15  7:42:23
