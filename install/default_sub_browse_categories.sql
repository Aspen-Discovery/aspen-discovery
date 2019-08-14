-- MySQL dump 10.17  Distrib 10.3.17-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: aspen
-- ------------------------------------------------------
-- Server version	10.3.17-MariaDB-log

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
-- Table structure for table `browse_category_subcategories`
--

DROP TABLE IF EXISTS `browse_category_subcategories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `browse_category_subcategories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `browseCategoryId` int(11) NOT NULL,
  `subCategoryId` int(11) NOT NULL,
  `weight` smallint(2) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subCategoryId` (`subCategoryId`,`browseCategoryId`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `browse_category_subcategories`
--

LOCK TABLES `browse_category_subcategories` WRITE;
/*!40000 ALTER TABLE `browse_category_subcategories` DISABLE KEYS */;
INSERT INTO `browse_category_subcategories` VALUES (1,11,12,4),(2,11,13,3),(3,11,14,2),(4,11,15,1),(5,11,16,5),(6,11,17,6),(7,11,18,7),(8,11,19,8),(9,11,20,9),(10,21,22,1),(11,21,23,2),(12,21,24,3),(13,21,25,4),(14,21,26,5),(15,21,27,6),(16,21,28,7),(17,21,29,8),(18,30,31,0),(19,30,32,0),(20,30,33,0),(21,30,34,0),(22,20,35,0),(23,36,37,0),(24,36,38,0),(25,36,39,0),(26,36,40,0),(27,36,41,0),(28,36,42,0),(29,36,43,0),(30,36,44,0),(31,46,47,0),(32,46,48,0),(33,46,49,0),(34,46,50,0),(35,46,51,0),(36,45,54,0),(37,45,55,0);
/*!40000 ALTER TABLE `browse_category_subcategories` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2019-08-12 12:25:36
