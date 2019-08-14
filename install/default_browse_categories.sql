-- MySQL dump 10.17  Distrib 10.3.17-MariaDB, for Linux (x86_64)
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
  `description` mediumtext DEFAULT NULL,
  `defaultFilter` text DEFAULT NULL,
  `defaultSort` enum('relevance','popularity','newest_to_oldest','oldest_to_newest','author','title','user_rating') DEFAULT NULL,
  `searchTerm` varchar(500) NOT NULL DEFAULT '',
  `numTimesShown` mediumint(9) NOT NULL DEFAULT 0,
  `numTitlesClickedOn` mediumint(9) NOT NULL DEFAULT 0,
  `sourceListId` mediumint(9) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `textId` (`textId`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `browse_category`
--

LOCK TABLES `browse_category` WRITE;
/*!40000 ALTER TABLE `browse_category` DISABLE KEYS */;
INSERT INTO `browse_category` VALUES (4,'main_new_fiction',1,'everyone','New Fiction','','literary_form:Fiction','newest_to_oldest','',196,1,-1),(5,'main_new_non_fiction',1,'everyone','New Non Fiction','','literary_form:Non Fiction','newest_to_oldest','',99,1,-1),(6,'default_new_adult_non_fiction',1,'everyone','New Adult Non Fiction','','literary_form:Non Fiction\r\ntarget_audience_full:Adult','newest_to_oldest','',65,6,NULL),(8,'default_new_movies',1,'everyone','New Movies','','format_category:Movies','newest_to_oldest','',67,10,NULL),(10,'duchesne_new_books',1,'everyone','New Books','','local_time_since_added_duchesne:Month\r\nformat_duchesne:Book\r\npublishDate:[2019 TO *]','relevance','',1,0,NULL),(11,'default_fiction',1,'everyone','Fiction','','literary_form:Fiction','relevance','',0,0,-1),(12,'default_romance',1,'everyone','Romance','','literary_form:Fiction\r\ntopic_facet:Romance','relevance','',180,13,NULL),(13,'default_mystery',1,'everyone','Mystery','','literary_form:Fiction\r\ntopic_facet:Mystery','relevance','',81,23,NULL),(14,'default_nyt_bestsellers',1,'everyone','NYT Bestsellers: Fiction','','','relevance','',91,35,3),(15,'default_all_fiction',1,'everyone','All Fiction','','literary_form:Fiction','relevance','',6019,370,NULL),(16,'default_science_fiction',1,'everyone','Science Fiction','','literary_form:Fiction\r\ngenre:Science Fiction','relevance','',28,18,NULL),(17,'default_fantasy',1,'everyone','Fantasy','','literary_form:Fiction\r\ngenre:Fantasy','relevance','',28,10,NULL),(18,'default_manga',1,'everyone','Manga','','subject_facet:Manga\r\nliterary_form:Fiction','relevance','',24,8,NULL),(19,'default_african_american',1,'everyone','African American','','literary_form:Fiction\r\nsubject_facet:African Americans -- Fiction','relevance','',15,6,NULL),(20,'default_audiobooks',1,'everyone','Audiobooks','','literary_form:Fiction\r\nformat_category:Audio Books','relevance','',40,42,NULL),(21,'default_nonfiction',1,'everyone','Nonfiction','','literary_form:Non Fiction','relevance','',0,0,-1),(22,'default_all_nonfiction',1,'everyone','All Nonfiction','','literary_form:Non Fiction','relevance','',534,167,NULL),(23,'default_nyt_bestsellers_nonfiction',1,'everyone','NYT Bestsellers: Nonfiction','','','relevance','',20,7,4),(24,'default_biography',1,'everyone','Biography','','literary_form:Non Fiction','relevance','biography',24,11,NULL),(25,'default_cooking',1,'everyone','Cooking','','literary_form:Non Fiction','relevance','cooking',10,4,NULL),(26,'default_fitness',1,'everyone','Fitness','','subject_facet:Physical fitness\r\nliterary_form:Non Fiction','relevance','',5,0,NULL),(27,'default_home_and_garden',1,'everyone','Home and Garden','','literary_form:Non Fiction','relevance','home and garden',9,0,NULL),(30,'default_children',1,'everyone','Children','','target_audience:Juvenile','relevance','',0,0,NULL),(31,'default_picture_books_easy',1,'everyone','Picture Books (Easy)','','subject_facet:Picture books for children','relevance','',399,18,NULL),(32,'default_chapter_books',1,'everyone','Chapter Books','','target_audience:Juvenile\r\nliterary_form:Fiction','relevance','Chapter Book',26,8,NULL),(33,'default_newbery_award',1,'everyone','Newbery Award','','awards_facet:Newbery Medal','newest_to_oldest','',23,4,-1),(34,'default_caldecott_award',1,'everyone','Caldecott Award','','awards_facet:Caldecott Medal','newest_to_oldest','',13,4,NULL),(35,'default_childrens_audiobooks',1,'everyone','Childrens Audiobooks','','target_audience:Juvenile\r\nformat_category:Audio Books','newest_to_oldest','',0,0,NULL),(36,'default_teens',1,'everyone','Teens','','target_audience:Young Adult','newest_to_oldest','',0,0,NULL),(37,'default_teen_fiction',1,'everyone','Fiction','','target_audience:Young Adult\r\nliterary_form:Fiction','newest_to_oldest','',305,16,-1),(38,'default_teen_romance',1,'everyone','Romance','','target_audience:Young Adult\r\nliterary_form:Fiction','relevance','Romance',25,25,-1),(39,'default_teen_mystery',1,'everyone','Mystery','','target_audience:Young Adult\r\nliterary_form:Fiction','relevance','Mystery',9,0,-1),(40,'default_teen_paranormal',1,'everyone','Paranormal','','target_audience:Young Adult\r\nliterary_form:Fiction','relevance','Paranormal',6,0,-1),(41,'default_teen_science_fiction',1,'everyone','Science Fiction','','target_audience:Young Adult\r\nliterary_form:Fiction','relevance','\"Science Fiction\" OR \"Sci Fi\"',6,1,-1),(42,'default_teen_manga',1,'everyone','Manga','','target_audience:Young Adult\r\nliterary_form:Fiction','relevance','Manga',8,4,-1),(43,'default_teen_fantasy',1,'everyone','Fantasy','','target_audience:Young Adult\r\nliterary_form:Fiction','relevance','Fantasy',11,10,-1),(44,'default_teen_graphic_novels',1,'everyone','Graphic Novels','','target_audience:Young Adult\r\nliterary_form:Fiction\r\nformat:Graphic Novel','relevance','',9,2,-1),(45,'default_magazines',1,'everyone','Magazines','','format:Magazine OR format:eMagazine','relevance','',102,15,-1),(46,'default_movies__tv',1,'everyone','Movies & TV','','format_category:Movies','relevance','',0,0,NULL),(47,'default_movies',1,'everyone','Movies','','format_category:Movies\r\nsubject_facet:Feature films\r\ntarget_audience:Adult','newest_to_oldest','',421,492,NULL),(48,'default_television_series',1,'everyone','Television Series','','format_category:Movies\r\nsubject_facet:Television Series','newest_to_oldest','',42,19,NULL),(49,'default_childrens_movies',1,'everyone','Children\'s Movies','','format_category:Movies\r\ntarget_audience:Juvenile','newest_to_oldest','',33,37,NULL),(50,'default_childrens_tv',1,'everyone','Children\'s TV','','format_category:Movies\r\nsubject_facet:Television series\r\ntarget_audience:Juvenile','newest_to_oldest','',19,11,NULL),(51,'default_documentaries',1,'everyone','Documentaries','','format_category:Movies\r\nsubject_facet:Documentary films','newest_to_oldest','',22,108,NULL),(52,'default_music',1,'everyone','Music','','format_category:Music','newest_to_oldest','',169,52,NULL),(53,'default_on_order',1,'everyone','On Order','','itype:On Order','newest_to_oldest','',233,88,NULL),(54,'default_print_magazines',1,'everyone','Print Magazines','','format:Magazine','relevance','',93,3,NULL),(55,'default_online_magazines',1,'everyone','Online Magazines','','format:eMagazine','relevance','',20,11,NULL);
/*!40000 ALTER TABLE `browse_category` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2019-08-12 12:11:52
