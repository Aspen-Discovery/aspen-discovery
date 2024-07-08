-- MySQL dump 10.13  Distrib 5.7.23, for Win64 (x86_64)
--
-- Host: localhost    Database: pueblo
-- ------------------------------------------------------
-- Server version	5.7.23-log

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

--
-- Dumping data for table `account_profiles`
--

LOCK TABLES `account_profiles` WRITE;
/*!40000 ALTER TABLE `account_profiles` DISABLE KEYS */;
INSERT INTO `account_profiles` (id, name, driver, loginConfiguration, authenticationMethod, vendorOpacUrl, patronApiUrl, recordSource, weight, databaseHost, databaseName, databaseUser, databasePassword, databasePort, databaseTimezone, oAuthClientId, oAuthClientSecret, ils) VALUES (2,'ils','Koha','barcode_pin','ils','{ilsUrl}','{ilsUrl}','ils',0,'{ilsDatabaseHost}','{ilsDatabaseName}','{ilsDatabaseUser}','{ilsDatabasePassword}','{ilsDatabasePort}','{ilsDatabaseTimezone}','{ilsClientId}','{ilsClientSecret}', 'koha');
/*!40000 ALTER TABLE `account_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `indexing_profiles`
--

LOCK TABLES `indexing_profiles` WRITE;
/*!40000 ALTER TABLE `indexing_profiles` DISABLE KEYS */;
INSERT INTO `indexing_profiles` (id, name, marcPath, marcEncoding, groupingClass, indexingClass, recordDriver, recordUrlComponent, formatSource, recordNumberTag, itemTag, itemRecordNumber, useItemBasedCallNumbers, callNumber, location, shelvingLocation,collection, volume, barcode, totalCheckouts, totalRenewals, iType, dueDate, dateCreated, dateCreatedFormat, format, catalogDriver, filenamesToInclude, doAutomaticEcontentSuppression, recordNumberSubfield, recordNumberPrefix, noteSubfield, lastCheckinFormat) VALUES (1,'ils','/data/aspen-discovery/{sitename}/ils/marc','UTF8','MarcRecordGrouper','Koha','MarcRecordDriver','Record','item','999','952','9',1,'o','a','c','8','h','p','l','m','y','k','d','yyyy-MM-dd','y','Koha','.*\\.ma?rc',1,'c', '', 'z', '');
/*!40000 ALTER TABLE `indexing_profiles` ENABLE KEYS */;
UNLOCK TABLES;


--
-- Dumping data for table `library_records_to_include`
--

LOCK TABLES `library_records_to_include` WRITE;
/*!40000 ALTER TABLE `library_records_to_include` DISABLE KEYS */;
INSERT INTO `library_records_to_include` (id, libraryId, indexingProfileId, location, subLocation, includeHoldableOnly, includeItemsOnOrder, includeEContent, weight, iType, audience, format, marcTagToMatch, marcValueToMatch, includeExcludeMatches, urlToMatch, urlReplacement, locationsToExclude, subLocationsToExclude, markRecordsAsOwned) VALUES (1,1,1,'.*','.*',0,1,1,1,'','','','','',1,'','','','', 1);
/*!40000 ALTER TABLE `library_records_to_include` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `status_map_values`
--

LOCK TABLES `status_map_values` WRITE;
/*!40000 ALTER TABLE `status_map_values` DISABLE KEYS */;
INSERT INTO `status_map_values` (id, indexingProfileId, value, status, groupedStatus, suppress) VALUES (1,1,'Checked Out','Checked Out','Checked Out',0),(2,1,'Claims Returned','Claims Returned','Currently Unavailable',1),(3,1,'On Shelf','On Shelf','On Shelf',0),(4,1,'Damaged','Damaged','Currently Unavailable',1),(5,1,'In Transit','In Transit','In Transit',0),(6,1,'Library Use Only','Library Use Only','Library Use Only',0),(7,1,'Long Overdue (Lost)','Long Overdue (Lost)','Currently Unavailable',1),(8,1,'Lost','Lost','Currently Unavailable',1),(9,1,'Lost and Paid For','Lost and Paid For','Currently Unavailable',1),(10,1,'Missing','Missing','Currently Unavailable',1),(11,1,'On Hold Shelf','On Hold Shelf','Checked Out',0),(12,1,'On Order','On Order','On Order',0),(13,1,'Discard','Discard','Currently Unavailable',1),(14,1,'Lost Claim','Lost Claim','Currently Unavailable',1);
/*!40000 ALTER TABLE `status_map_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `translation_maps`
--

LOCK TABLES `translation_maps` WRITE;
/*!40000 ALTER TABLE `translation_maps` DISABLE KEYS */;
INSERT INTO `translation_maps` VALUES (1,1,'location',0),(2,1,'sub_location',0),(3,1,'shelf_location',0),(5,1,'itype',0);
/*!40000 ALTER TABLE `translation_maps` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

UPDATE modules set enabled = 1 where name = 'Koha';

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2019-11-18  8:22:06
