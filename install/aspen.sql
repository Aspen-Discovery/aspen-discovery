-- phpMyAdmin SQL Dump
-- version 4.8.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 12, 2019 at 09:48 AM
-- Server version: 5.7.23
-- PHP Version: 7.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `aspen`
--

-- --------------------------------------------------------

--
-- Table structure for table `account_profiles`
--

CREATE TABLE `account_profiles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL DEFAULT 'ils',
  `driver` varchar(50) NOT NULL,
  `loginConfiguration` enum('barcode_pin','name_barcode') NOT NULL,
  `authenticationMethod` enum('ils','sip2','db','ldap') NOT NULL DEFAULT 'ils',
  `vendorOpacUrl` varchar(100) NOT NULL,
  `patronApiUrl` varchar(100) NOT NULL,
  `recordSource` varchar(50) NOT NULL,
  `weight` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `account_profiles`
--

INSERT INTO `account_profiles` (`id`, `name`, `driver`, `loginConfiguration`, `authenticationMethod`, `vendorOpacUrl`, `patronApiUrl`, `recordSource`, `weight`) VALUES
(1, 'ils', 'Library', 'name_barcode', 'db', 'defaultURL', 'defaultURL', 'ils', 1);

-- --------------------------------------------------------

--
-- Table structure for table `archive_private_collections`
--

CREATE TABLE `archive_private_collections` (
  `id` int(11) NOT NULL,
  `privateCollections` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `archive_requests`
--

CREATE TABLE `archive_requests` (
  `id` int(11) NOT NULL,
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
  `dateRequested` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `archive_subjects`
--

CREATE TABLE `archive_subjects` (
  `id` int(11) NOT NULL,
  `subjectsToIgnore` mediumtext,
  `subjectsToRestrict` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `author_enrichment`
--

CREATE TABLE `author_enrichment` (
  `id` int(11) NOT NULL,
  `authorName` varchar(255) NOT NULL,
  `hideWikipedia` tinyint(1) DEFAULT NULL,
  `wikipediaUrl` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bad_words`
--

CREATE TABLE `bad_words` (
  `id` int(11) NOT NULL COMMENT 'A unique Id for bad_word',
  `word` varchar(50) NOT NULL COMMENT 'The bad word that will be replaced',
  `replacement` varchar(50) NOT NULL COMMENT 'A replacement value for the word.'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores information about bad_words that should be removed fr';

-- --------------------------------------------------------

--
-- Table structure for table `browse_category`
--

CREATE TABLE `browse_category` (
  `id` int(11) NOT NULL,
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
  `sourceListId` mediumint(9) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `browse_category`
--

INSERT INTO `browse_category` (`id`, `textId`, `userId`, `sharing`, `label`, `description`, `defaultFilter`, `defaultSort`, `searchTerm`, `numTimesShown`, `numTitlesClickedOn`, `sourceListId`) VALUES
(4, 'main_new_fiction', 1, 'everyone', 'New Fiction', '', 'literary_form:Fiction', 'newest_to_oldest', '', 1, 0, -1),
(5, 'main_new_non_fiction', 1, 'everyone', 'New Non Fiction', '', 'literary_form:Non Fiction', 'newest_to_oldest', '', 0, 0, -1);

-- --------------------------------------------------------

--
-- Table structure for table `browse_category_library`
--

CREATE TABLE `browse_category_library` (
  `id` int(11) NOT NULL,
  `libraryId` int(11) NOT NULL,
  `browseCategoryTextId` varchar(60) NOT NULL DEFAULT '-1',
  `weight` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `browse_category_library`
--

INSERT INTO `browse_category_library` (`id`, `libraryId`, `browseCategoryTextId`, `weight`) VALUES
(3, 2, 'main_new_fiction', 0),
(5, 2, 'main_new_non_fiction', 0);

-- --------------------------------------------------------

--
-- Table structure for table `browse_category_location`
--

CREATE TABLE `browse_category_location` (
  `id` int(11) NOT NULL,
  `locationId` int(11) NOT NULL,
  `browseCategoryTextId` varchar(60) NOT NULL DEFAULT '-1',
  `weight` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `browse_category_subcategories`
--

CREATE TABLE `browse_category_subcategories` (
  `id` int(10) UNSIGNED NOT NULL,
  `browseCategoryId` int(11) NOT NULL,
  `subCategoryId` int(11) NOT NULL,
  `weight` smallint(2) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `claim_authorship_requests`
--

CREATE TABLE `claim_authorship_requests` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `message` mediumtext,
  `pid` varchar(50) DEFAULT NULL,
  `dateRequested` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `cron_log`
--

CREATE TABLE `cron_log` (
  `id` int(11) NOT NULL COMMENT 'The id of the cron log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the cron run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the cron run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the cron run last updated (to check for stuck processes)',
  `notes` text COMMENT 'Additional information about the cron run'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `cron_process_log`
--

CREATE TABLE `cron_process_log` (
  `id` int(11) NOT NULL COMMENT 'The id of cron process',
  `cronId` int(11) NOT NULL COMMENT 'The id of the cron run this process ran during',
  `processName` varchar(50) NOT NULL COMMENT 'The name of the process being run',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the process started',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the process last updated (to check for stuck processes)',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the process ended',
  `numErrors` int(11) NOT NULL DEFAULT '0' COMMENT 'The number of errors that occurred during the process',
  `numUpdates` int(11) NOT NULL DEFAULT '0' COMMENT 'The number of updates, additions, etc. that occurred',
  `notes` text COMMENT 'Additional information about the process'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `db_update`
--

CREATE TABLE `db_update` (
  `update_key` varchar(100) NOT NULL,
  `date_run` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `db_update`
--

INSERT INTO `db_update` (`update_key`, `date_run`) VALUES
('account_profiles_1', '2019-01-28 20:59:02'),
('acsLog', '2011-12-13 16:04:23'),
('additional_library_contact_links', '2019-01-28 20:58:56'),
('additional_locations_for_availability', '2019-01-28 20:58:56'),
('addTablelistWidgetListsLinks', '2019-01-28 20:59:01'),
('add_indexes', '2019-01-28 20:59:01'),
('add_indexes2', '2019-01-28 20:59:01'),
('add_search_source_to_saved_searches', '2019-01-28 20:59:02'),
('add_sms_indicator_to_phone', '2019-01-28 20:58:56'),
('allow_masquerade_mode', '2019-01-28 20:58:56'),
('allow_reading_history_display_in_masquerade_mode', '2019-01-28 20:58:56'),
('alpha_browse_setup_2', '2019-01-28 20:58:59'),
('alpha_browse_setup_3', '2019-01-28 20:58:59'),
('alpha_browse_setup_4', '2019-01-28 20:58:59'),
('alpha_browse_setup_5', '2019-01-28 20:58:59'),
('alpha_browse_setup_6', '2019-01-28 20:59:00'),
('alpha_browse_setup_7', '2019-01-28 20:59:00'),
('alpha_browse_setup_8', '2019-01-28 20:59:00'),
('alpha_browse_setup_9', '2019-01-28 20:59:01'),
('always_show_search_results_Main_details', '2019-01-28 20:58:56'),
('analytics', '2019-01-28 20:59:01'),
('analytics_1', '2019-01-28 20:59:01'),
('analytics_2', '2019-01-28 20:59:01'),
('analytics_3', '2019-01-28 20:59:01'),
('analytics_4', '2019-01-28 20:59:01'),
('analytics_5', '2019-01-28 20:59:02'),
('analytics_6', '2019-01-28 20:59:02'),
('analytics_7', '2019-01-28 20:59:02'),
('analytics_8', '2019-01-28 20:59:02'),
('archivesRole', '2019-01-28 20:58:59'),
('archive_collection_default_view_mode', '2019-01-28 20:58:56'),
('archive_filtering', '2019-01-28 20:58:56'),
('archive_more_details_customization', '2019-01-28 20:58:56'),
('archive_object_filtering', '2019-01-28 20:58:56'),
('archive_private_collections', '2019-01-28 20:59:02'),
('archive_requests', '2019-01-28 20:59:02'),
('archive_subjects', '2019-01-28 20:59:02'),
('authentication_profiles', '2019-01-28 20:59:02'),
('author_enrichment', '2019-01-28 20:58:59'),
('availability_toggle_customization', '2019-01-28 20:58:56'),
('book_store', '2019-01-28 20:59:01'),
('book_store_1', '2019-01-28 20:59:01'),
('boost_disabling', '2019-01-28 20:59:01'),
('browse_categories', '2019-01-28 20:59:02'),
('browse_categories_lists', '2019-01-28 20:59:02'),
('browse_categories_search_term_and_stats', '2019-01-28 20:59:02'),
('browse_categories_search_term_length', '2019-01-28 20:59:02'),
('browse_category_default_view_mode', '2019-01-28 20:58:56'),
('browse_category_ratings_mode', '2019-01-28 20:58:56'),
('catalogingRole', '2019-01-28 20:58:59'),
('change_to_innodb', '2019-03-05 18:07:51'),
('claim_authorship_requests', '2019-01-28 20:59:02'),
('clear_analytics', '2019-01-28 20:59:02'),
('collapse_facets', '2019-01-28 20:58:55'),
('combined_results', '2019-01-28 20:58:56'),
('contentEditor', '2019-01-28 20:58:59'),
('convertOldEContent', '2011-11-06 22:58:31'),
('coverArt_suppress', '2019-01-28 20:58:58'),
('cronLog', '2019-01-28 20:59:01'),
('default_library', '2019-01-28 20:58:56'),
('detailed_hold_notice_configuration', '2019-01-28 20:58:56'),
('disable_auto_correction_of_searches', '2019-01-28 20:58:56'),
('display_pika_logo', '2019-01-28 20:58:56'),
('dpla_integration', '2019-01-28 20:58:56'),
('eContentCheckout', '2011-11-10 23:57:56'),
('eContentCheckout_1', '2011-12-13 16:04:03'),
('eContentHistory', '2011-11-15 17:56:44'),
('eContentHolds', '2011-11-10 22:39:20'),
('eContentItem_1', '2011-12-04 22:13:19'),
('eContentRating', '2011-11-16 21:53:43'),
('eContentRecord_1', '2011-12-01 21:43:54'),
('eContentRecord_2', '2012-01-11 20:06:48'),
('eContentWishList', '2011-12-08 20:29:48'),
('econtent_attach', '2011-12-30 19:12:22'),
('econtent_locations_to_include', '2019-01-28 20:56:58'),
('econtent_marc_import', '2011-12-15 22:48:22'),
('editorial_review', '2019-01-28 20:58:58'),
('editorial_review_1', '2019-01-28 20:58:58'),
('editorial_review_2', '2019-01-28 20:58:58'),
('enable_archive', '2019-01-28 20:58:56'),
('expiration_message', '2019-01-28 20:58:56'),
('explore_more_configuration', '2019-01-28 20:58:56'),
('externalLinkTracking', '2019-01-28 20:58:58'),
('external_materials_request', '2019-01-28 20:58:56'),
('facet_grouping_updates', '2019-01-28 20:58:55'),
('full_record_view_configuration_options', '2019-01-28 20:58:56'),
('genealogy', '2019-01-28 20:58:58'),
('genealogy_1', '2019-01-28 20:58:58'),
('genealogy_nashville_1', '2019-01-28 20:58:58'),
('goodreads_library_contact_link', '2019-01-28 20:58:56'),
('grouped_works', '2019-01-28 20:58:56'),
('grouped_works_1', '2019-01-28 20:58:56'),
('grouped_works_2', '2019-01-28 20:58:56'),
('grouped_works_partial_updates', '2019-01-28 20:58:57'),
('grouped_works_primary_identifiers', '2019-01-28 20:58:56'),
('grouped_works_primary_identifiers_1', '2019-01-28 20:58:56'),
('grouped_works_remove_split_titles', '2019-01-28 20:58:56'),
('grouped_work_duplicate_identifiers', '2019-01-28 20:58:57'),
('grouped_work_engine', '2019-01-28 20:58:57'),
('grouped_work_evoke', '2019-01-28 20:58:57'),
('grouped_work_identifiers_ref_indexing', '2019-01-28 20:58:57'),
('grouped_work_index_cleanup', '2019-01-28 20:58:57'),
('grouped_work_index_date_updated', '2019-01-28 20:58:57'),
('grouped_work_merging', '2019-01-28 20:58:57'),
('grouped_work_primary_identifiers_hoopla', '2019-01-28 20:58:57'),
('grouped_work_primary_identifier_types', '2019-01-28 20:58:57'),
('header_text', '2019-01-28 20:58:56'),
('holiday', '2019-01-28 20:59:01'),
('holiday_1', '2019-01-28 20:59:01'),
('hoopla_exportLog', '2019-01-28 20:58:57'),
('hoopla_exportTables', '2019-01-28 20:58:57'),
('hoopla_integration', '2019-01-28 20:58:56'),
('hoopla_library_options', '2019-01-28 20:58:56'),
('hoopla_library_options_remove', '2019-01-28 20:58:56'),
('horizontal_search_bar', '2019-01-28 20:58:56'),
('hours_and_locations_control', '2019-01-28 20:58:55'),
('ill_link', '2019-01-28 20:58:56'),
('ils_code_records_owned_length', '2019-01-28 20:58:56'),
('ils_hold_summary', '2019-01-28 20:58:57'),
('ils_marc_checksums', '2019-01-28 20:59:02'),
('ils_marc_checksum_first_detected', '2019-01-28 20:59:02'),
('ils_marc_checksum_first_detected_signed', '2019-01-28 20:59:02'),
('ils_marc_checksum_source', '2019-01-28 20:59:02'),
('increase_ilsID_size_for_ils_marc_checksums', '2019-01-28 20:58:57'),
('increase_login_form_labels', '2019-01-28 20:58:56'),
('indexing_profile', '2019-01-28 20:58:57'),
('indexing_profile_catalog_driver', '2019-01-28 20:58:57'),
('indexing_profile_collection', '2019-01-28 20:58:57'),
('indexing_profile_collectionsToSuppress', '2019-01-28 20:58:57'),
('indexing_profile_doAutomaticEcontentSuppression', '2019-01-28 20:58:57'),
('indexing_profile_dueDateFormat', '2019-01-28 20:58:57'),
('indexing_profile_extendLocationsToSuppress', '2019-01-28 20:58:57'),
('indexing_profile_filenames_to_include', '2019-01-28 20:58:57'),
('indexing_profile_folderCreation', '2019-01-28 20:58:57'),
('indexing_profile_groupUnchangedFiles', '2019-01-28 20:58:57'),
('indexing_profile_holdability', '2019-01-28 20:58:57'),
('indexing_profile_last_checkin_date', '2019-01-28 20:58:57'),
('indexing_profile_marc_encoding', '2019-01-28 20:58:57'),
('indexing_profile_marc_record_subfield', '2019-03-11 05:22:58'),
('indexing_profile_specific_order_location', '2019-01-28 20:58:57'),
('indexing_profile_speicified_formats', '2019-01-28 20:58:57'),
('index_resources', '2019-01-28 20:58:59'),
('index_search_stats', '2019-01-28 20:58:58'),
('index_search_stats_counts', '2019-01-28 20:58:58'),
('index_subsets_of_overdrive', '2019-01-28 20:58:56'),
('initial_setup', '2011-11-15 22:29:11'),
('ip_lookup_1', '2019-01-28 20:58:59'),
('ip_lookup_2', '2019-01-28 20:58:59'),
('ip_lookup_3', '2019-01-28 20:58:59'),
('islandora_cover_cache', '2019-01-28 20:58:57'),
('islandora_driver_cache', '2019-01-28 20:58:57'),
('islandora_lat_long_cache', '2019-01-28 20:58:57'),
('islandora_samePika_cache', '2019-01-28 20:58:57'),
('last_check_in_status_adjustments', '2019-01-28 20:58:57'),
('lexile_branding', '2019-01-28 20:58:56'),
('libraryAdmin', '2019-01-28 20:58:59'),
('library_1', '2019-01-28 20:56:57'),
('library_10', '2019-01-28 20:56:57'),
('library_11', '2019-01-28 20:56:57'),
('library_12', '2019-01-28 20:56:57'),
('library_13', '2019-01-28 20:56:57'),
('library_14', '2019-01-28 20:56:57'),
('library_15', '2019-01-28 20:56:57'),
('library_16', '2019-01-28 20:56:57'),
('library_17', '2019-01-28 20:56:57'),
('library_18', '2019-01-28 20:56:57'),
('library_19', '2019-01-28 20:56:57'),
('library_2', '2019-01-28 20:56:57'),
('library_20', '2019-01-28 20:56:57'),
('library_21', '2019-01-28 20:56:57'),
('library_23', '2019-01-28 20:56:57'),
('library_24', '2019-01-28 20:56:57'),
('library_25', '2019-01-28 20:56:57'),
('library_26', '2019-01-28 20:56:57'),
('library_28', '2019-01-28 20:56:57'),
('library_29', '2019-01-28 20:56:57'),
('library_3', '2019-01-28 20:56:57'),
('library_30', '2019-01-28 20:56:57'),
('library_31', '2019-01-28 20:56:57'),
('library_32', '2019-01-28 20:56:57'),
('library_33', '2019-01-28 20:56:57'),
('library_34', '2019-01-28 20:56:57'),
('library_35_marmot', '2019-01-28 20:56:57'),
('library_35_nashville', '2019-01-28 20:56:57'),
('library_36_nashville', '2019-01-28 20:56:57'),
('library_4', '2019-01-28 20:56:57'),
('library_5', '2019-01-28 20:56:57'),
('library_6', '2019-01-28 20:56:57'),
('library_7', '2019-01-28 20:56:57'),
('library_8', '2019-01-28 20:56:57'),
('library_9', '2019-01-28 20:56:57'),
('library_archive_material_requests', '2019-01-28 20:58:56'),
('library_archive_material_request_form_configurations', '2019-01-28 20:58:56'),
('library_archive_pid', '2019-01-28 20:58:56'),
('library_archive_related_objects_display_mode', '2019-01-28 20:58:56'),
('library_archive_request_customization', '2019-01-28 20:58:56'),
('library_archive_search_facets', '2019-01-28 20:58:55'),
('library_barcodes', '2019-01-28 20:58:55'),
('library_bookings', '2019-01-28 20:58:55'),
('library_cas_configuration', '2019-01-28 20:58:56'),
('library_claim_authorship_customization', '2019-01-28 20:58:56'),
('library_contact_links', '2019-01-28 20:56:57'),
('library_css', '2019-01-28 20:56:57'),
('library_eds_integration', '2019-01-28 20:58:56'),
('library_eds_search_integration', '2019-01-28 20:58:56'),
('library_expiration_warning', '2019-01-28 20:56:57'),
('library_facets', '2019-01-28 20:58:55'),
('library_facets_1', '2019-01-28 20:58:55'),
('library_facets_2', '2019-01-28 20:58:55'),
('library_grouping', '2019-01-28 20:56:57'),
('library_ils_code_expansion', '2019-01-28 20:56:57'),
('library_ils_code_expansion_2', '2019-01-28 20:56:58'),
('library_links', '2019-01-28 20:56:57'),
('library_links_display_options', '2019-01-28 20:56:57'),
('library_links_show_html', '2019-01-28 20:56:57'),
('library_location_availability_toggle_updates', '2019-01-28 20:58:56'),
('library_location_boosting', '2019-01-28 20:56:57'),
('library_location_display_controls', '2019-01-28 20:58:55'),
('library_location_repeat_online', '2019-01-28 20:56:57'),
('library_materials_request_limits', '2019-01-28 20:56:57'),
('library_materials_request_new_request_summary', '2019-01-28 20:56:57'),
('library_max_fines_for_account_update', '2019-01-28 20:58:56'),
('library_on_order_counts', '2019-01-28 20:58:56'),
('library_order_information', '2019-01-28 20:56:57'),
('library_patronNameDisplayStyle', '2019-01-28 20:58:56'),
('library_pin_reset', '2019-01-28 20:56:57'),
('library_prevent_expired_card_login', '2019-01-28 20:56:57'),
('library_prompt_birth_date', '2019-01-28 20:58:55'),
('library_show_display_name', '2019-01-28 20:58:55'),
('library_show_series_in_main_details', '2019-01-28 20:58:56'),
('library_sidebar_menu', '2019-01-28 20:58:56'),
('library_sidebar_menu_button_text', '2019-01-28 20:58:56'),
('library_subject_display', '2019-01-28 20:58:56'),
('library_subject_display_2', '2019-01-28 20:58:56'),
('library_top_links', '2019-01-28 20:56:57'),
('library_use_theme', '2019-02-26 00:09:00'),
('linked_accounts_switch', '2019-01-28 20:58:56'),
('listPublisherRole', '2019-01-28 20:58:59'),
('list_wdiget_list_update_1', '2019-01-28 20:58:57'),
('list_wdiget_update_1', '2019-01-28 20:58:57'),
('list_widgets', '2019-01-28 20:58:57'),
('list_widgets_home', '2019-01-28 20:58:57'),
('list_widgets_update_1', '2019-01-28 20:58:57'),
('list_widgets_update_2', '2019-01-28 20:58:57'),
('list_widget_num_results', '2019-01-28 20:58:57'),
('list_widget_style_update', '2019-01-28 20:58:57'),
('list_widget_update_2', '2019-01-28 20:58:57'),
('list_widget_update_3', '2019-01-28 20:58:57'),
('list_widget_update_4', '2019-01-28 20:58:57'),
('list_widget_update_5', '2019-01-28 20:58:57'),
('loan_rule_determiners_1', '2019-01-28 20:59:01'),
('loan_rule_determiners_increase_ptype_length', '2019-01-28 20:59:01'),
('localized_browse_categories', '2019-01-28 20:59:02'),
('location_1', '2019-01-28 20:58:55'),
('location_10', '2019-01-28 20:58:56'),
('location_2', '2019-01-28 20:58:56'),
('location_3', '2019-01-28 20:58:56'),
('location_4', '2019-01-28 20:58:56'),
('location_5', '2019-01-28 20:58:56'),
('location_6', '2019-01-28 20:58:56'),
('location_7', '2019-01-28 20:58:56'),
('location_8', '2019-01-28 20:58:56'),
('location_9', '2019-01-28 20:58:56'),
('location_additional_branches_to_show_in_facets', '2019-01-28 20:58:56'),
('location_address', '2019-01-28 20:58:56'),
('location_facets', '2019-01-28 20:58:55'),
('location_facets_1', '2019-01-28 20:58:55'),
('location_hours', '2019-01-28 20:59:01'),
('location_include_library_records_to_include', '2019-01-28 20:58:56'),
('location_increase_code_column_size', '2019-01-28 20:58:56'),
('location_library_control_shelf_location_and_date_added_facets', '2019-01-28 20:58:56'),
('location_show_display_name', '2019-01-28 20:58:56'),
('location_subdomain', '2019-01-28 20:58:56'),
('location_sublocation', '2019-01-28 20:58:56'),
('location_sublocation_uniqueness', '2019-01-28 20:58:56'),
('login_form_labels', '2019-01-28 20:58:56'),
('logo_linking', '2019-01-28 20:58:56'),
('main_location_switch', '2019-01-28 20:58:56'),
('manageMaterialsRequestFieldsToDisplay', '2019-01-28 20:58:59'),
('marcImport', '2019-01-28 20:59:01'),
('marcImport_1', '2019-01-28 20:59:01'),
('marcImport_2', '2019-01-28 20:59:01'),
('marcImport_3', '2019-01-28 20:59:01'),
('masquerade_automatic_timeout_length', '2019-01-28 20:58:56'),
('masquerade_ptypes', '2019-01-28 20:59:01'),
('materialRequestsRole', '2019-01-28 20:58:59'),
('materialsRequest', '2019-01-28 20:58:58'),
('materialsRequestFixColumns', '2019-01-28 20:58:59'),
('materialsRequestFormats', '2019-01-28 20:58:59'),
('materialsRequestFormFields', '2019-01-28 20:58:59'),
('materialsRequestLibraryId', '2019-01-28 20:58:59'),
('materialsRequestStatus', '2019-01-28 20:58:59'),
('materialsRequestStatus_update1', '2019-01-28 20:58:59'),
('materialsRequest_update1', '2019-01-28 20:58:58'),
('materialsRequest_update2', '2019-01-28 20:58:58'),
('materialsRequest_update3', '2019-01-28 20:58:58'),
('materialsRequest_update4', '2019-01-28 20:58:58'),
('materialsRequest_update5', '2019-01-28 20:58:58'),
('materialsRequest_update6', '2019-01-28 20:58:58'),
('materialsRequest_update7', '2019-01-28 20:58:59'),
('materials_request_days_to_keep', '2019-01-28 20:58:56'),
('merged_records', '2019-01-28 20:58:59'),
('millenniumTables', '2019-01-28 20:59:01'),
('modifyColumnSizes_1', '2011-11-10 19:46:03'),
('more_details_customization', '2019-01-28 20:58:56'),
('nearby_book_store', '2019-01-28 20:59:01'),
('newRolesJan2016', '2019-01-28 20:58:59'),
('new_search_stats', '2019-01-28 20:58:58'),
('nongrouped_records', '2019-01-28 20:58:59'),
('non_numeric_ptypes', '2019-01-28 20:59:01'),
('notices_1', '2011-12-02 18:26:28'),
('notInterested', '2019-01-28 20:58:58'),
('notInterestedWorks', '2019-01-28 20:58:58'),
('notInterestedWorksRemoveUserIndex', '2019-01-28 20:58:58'),
('novelist_data', '2019-01-28 20:59:02'),
('offline_circulation', '2019-01-28 20:59:02'),
('offline_holds', '2019-01-28 20:59:02'),
('offline_holds_update_1', '2019-01-28 20:59:02'),
('offline_holds_update_2', '2019-01-28 20:59:02'),
('overdrive_account_cache', '2012-01-02 22:16:10'),
('overdrive_api_data', '2016-06-30 17:11:12'),
('overdrive_api_data_availability_type', '2016-06-30 17:11:12'),
('overdrive_api_data_crossRefId', '2019-01-28 21:27:59'),
('overdrive_api_data_metadata_isOwnedByCollections', '2019-01-28 21:27:59'),
('overdrive_api_data_update_1', '2016-06-30 17:11:12'),
('overdrive_api_data_update_2', '2016-06-30 17:11:12'),
('overdrive_integration', '2019-01-28 20:58:56'),
('overdrive_integration_2', '2019-01-28 20:58:56'),
('overdrive_integration_3', '2019-01-28 20:58:56'),
('ptype', '2019-01-28 20:59:01'),
('pTypesForLibrary', '2019-01-28 20:58:55'),
('public_lists_to_include', '2019-01-28 20:58:56'),
('purchase_link_tracking', '2019-01-28 20:58:58'),
('rbdigital_availability', '2019-03-06 15:43:14'),
('rbdigital_exportLog', '2019-03-05 05:46:15'),
('rbdigital_exportTables', '2019-03-05 16:31:43'),
('readingHistory', '2019-01-28 20:58:58'),
('readingHistoryUpdate1', '2019-01-28 20:58:58'),
('readingHistory_deletion', '2019-01-28 20:58:58'),
('readingHistory_work', '2019-01-28 20:58:58'),
('recommendations_optOut', '2019-01-28 20:58:58'),
('records_to_include_2017-06', '2019-01-28 20:58:57'),
('records_to_include_2018-03', '2019-01-28 20:58:57'),
('record_grouping_log', '2019-01-28 20:59:02'),
('reindexLog', '2019-01-28 20:59:01'),
('reindexLog_1', '2019-01-28 20:59:01'),
('reindexLog_2', '2019-01-28 20:59:01'),
('reindexLog_grouping', '2019-01-28 20:59:01'),
('remove_browse_tables', '2019-01-28 20:59:02'),
('remove_consortial_results_in_search', '2019-01-28 20:58:56'),
('remove_old_resource_tables', '2019-01-28 20:59:02'),
('remove_order_options', '2019-01-28 20:58:56'),
('remove_unused_enrichment_and_full_record_options', '2019-01-28 20:58:56'),
('remove_unused_location_options_2015_14_0', '2019-01-28 20:58:56'),
('remove_unused_options', '2019-01-28 20:59:02'),
('rename_tables', '2019-01-28 20:59:01'),
('resource_subject', '2019-01-28 20:58:58'),
('resource_update3', '2019-01-28 20:58:58'),
('resource_update4', '2019-01-28 20:58:58'),
('resource_update5', '2019-01-28 20:58:58'),
('resource_update6', '2019-01-28 20:58:58'),
('resource_update7', '2019-01-28 20:58:58'),
('resource_update8', '2019-01-28 20:58:58'),
('resource_update_table', '2019-01-28 20:58:58'),
('resource_update_table_2', '2019-01-28 20:58:58'),
('right_hand_sidebar', '2019-01-28 20:58:56'),
('roles_1', '2019-01-28 20:58:56'),
('roles_2', '2019-01-28 20:58:56'),
('search_results_view_configuration_options', '2019-01-28 20:58:56'),
('search_sources', '2019-01-28 20:58:56'),
('search_sources_1', '2019-01-28 20:58:56'),
('selfreg_customization', '2019-01-28 20:58:56'),
('selfreg_template', '2019-01-28 20:58:56'),
('session_update_1', '2019-01-28 20:59:02'),
('setup_default_indexing_profiles', '2019-01-28 20:58:57'),
('show_catalog_options_in_profile', '2019-01-28 20:58:56'),
('show_grouped_hold_copies_count', '2019-01-28 20:58:56'),
('show_library_hours_notice_on_account_pages', '2019-01-28 20:58:56'),
('show_place_hold_on_unavailable', '2019-01-28 20:58:56'),
('show_Refresh_Account_Button', '2019-01-28 20:58:56'),
('sierra_exportLog', '2019-01-28 20:58:57'),
('sierra_exportLog_stats', '2019-01-28 20:58:58'),
('sierra_export_field_mapping', '2019-01-28 20:58:58'),
('sierra_export_field_mapping_item_fields', '2019-01-28 20:58:58'),
('spelling_optimization', '2019-01-28 20:59:01'),
('staffSettingsTable', '2019-01-28 20:58:59'),
('sub-browse_categories', '2019-01-28 20:59:02'),
('syndetics_data', '2019-01-28 20:59:02'),
('themes_setup', '2019-02-24 20:32:34'),
('theme_name_length', '2019-01-28 20:58:56'),
('translation_map_regex', '2019-01-28 20:58:57'),
('userRatings1', '2019-01-28 20:58:58'),
('user_account', '2019-01-28 20:58:56'),
('user_display_name', '2019-01-28 20:58:56'),
('user_hoopla_confirmation_checkout', '2019-01-28 20:58:56'),
('user_ilsType', '2019-01-28 20:58:56'),
('user_linking', '2019-01-28 20:58:56'),
('user_linking_1', '2019-01-28 20:58:56'),
('user_link_blocking', '2019-01-28 20:58:56'),
('user_list_entry', '2019-01-28 20:59:02'),
('user_list_indexing', '2019-01-28 20:59:02'),
('user_list_sorting', '2019-01-28 20:59:02'),
('user_overdrive_email', '2019-01-28 20:58:56'),
('user_phone', '2019-01-28 20:58:56'),
('user_preference_review_prompt', '2019-01-28 20:58:56'),
('user_preferred_library_interface', '2019-01-28 20:58:56'),
('user_reading_history_index_source_id', '2019-01-28 20:58:56'),
('user_track_reading_history', '2019-01-28 20:58:56'),
('utf8_update', '2016-06-30 17:11:12'),
('variables_full_index_warnings', '2019-01-28 20:58:59'),
('variables_lastHooplaExport', '2019-01-28 20:58:57'),
('variables_lastRbdigitalExport', '2019-03-05 05:46:15'),
('variables_offline_mode_when_offline_login_allowed', '2019-01-28 20:58:59'),
('variables_table', '2019-01-28 20:58:59'),
('variables_table_uniqueness', '2019-01-28 20:58:59'),
('variables_validateChecksumsFromDisk', '2019-01-28 20:58:59'),
('volume_information', '2019-01-28 20:58:57'),
('work_level_ratings', '2019-01-28 20:59:02'),
('work_level_tagging', '2019-01-28 20:59:02');

-- --------------------------------------------------------

--
-- Table structure for table `editorial_reviews`
--

CREATE TABLE `editorial_reviews` (
  `editorialReviewId` int(11) NOT NULL,
  `recordId` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `pubDate` bigint(20) NOT NULL,
  `review` text,
  `source` varchar(50) NOT NULL,
  `tabName` varchar(25) DEFAULT 'Reviews',
  `teaser` varchar(512) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `grouped_work`
--

CREATE TABLE `grouped_work` (
  `id` bigint(20) NOT NULL,
  `permanent_id` char(36) NOT NULL,
  `author` varchar(50) DEFAULT NULL,
  `grouping_category` varchar(25) NOT NULL,
  `full_title` varchar(276) NOT NULL,
  `date_updated` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `grouped_work_primary_identifiers`
--

CREATE TABLE `grouped_work_primary_identifiers` (
  `id` bigint(20) NOT NULL,
  `grouped_work_id` bigint(20) NOT NULL,
  `type` varchar(50) NOT NULL,
  `identifier` varchar(36) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `holiday`
--

CREATE TABLE `holiday` (
  `id` int(11) NOT NULL COMMENT 'The id of holiday',
  `libraryId` int(11) NOT NULL COMMENT 'The library system id',
  `date` date NOT NULL COMMENT 'Date of holiday',
  `name` varchar(100) NOT NULL COMMENT 'Name of holiday'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `hoopla_export`
--

CREATE TABLE `hoopla_export` (
  `id` int(11) NOT NULL,
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
  `price` double NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `hoopla_export_log`
--

CREATE TABLE `hoopla_export_log` (
  `id` int(11) NOT NULL COMMENT 'The id of log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `notes` text COMMENT 'Additional information about the run'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `ils_hold_summary`
--

CREATE TABLE `ils_hold_summary` (
  `id` int(11) NOT NULL,
  `ilsId` varchar(20) NOT NULL,
  `numHolds` int(11) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `ils_marc_checksums`
--

CREATE TABLE `ils_marc_checksums` (
  `id` int(11) NOT NULL,
  `ilsId` varchar(50) NOT NULL,
  `checksum` bigint(20) UNSIGNED NOT NULL,
  `dateFirstDetected` bigint(20) DEFAULT NULL,
  `source` varchar(50) NOT NULL DEFAULT 'ils'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `ils_volume_info`
--

CREATE TABLE `ils_volume_info` (
  `id` int(11) NOT NULL,
  `recordId` varchar(50) NOT NULL COMMENT 'Full Record ID including the source',
  `displayLabel` varchar(255) NOT NULL,
  `relatedItems` varchar(512) NOT NULL,
  `volumeId` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `indexing_profiles`
--

CREATE TABLE `indexing_profiles` (
  `id` int(11) NOT NULL,
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
  `recordNumberSubfield` char(1) DEFAULT 'a'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `ip_lookup`
--

CREATE TABLE `ip_lookup` (
  `id` int(25) NOT NULL,
  `locationid` int(5) NOT NULL,
  `location` varchar(255) NOT NULL,
  `ip` varchar(255) NOT NULL,
  `startIpVal` bigint(20) DEFAULT NULL,
  `endIpVal` bigint(20) DEFAULT NULL,
  `isOpac` tinyint(3) UNSIGNED NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `islandora_object_cache`
--

CREATE TABLE `islandora_object_cache` (
  `id` int(11) NOT NULL,
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
  `originalCoverUrl` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `islandora_samepika_cache`
--

CREATE TABLE `islandora_samepika_cache` (
  `id` int(11) NOT NULL,
  `groupedWorkId` char(36) NOT NULL,
  `pid` varchar(100) DEFAULT NULL,
  `archiveLink` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `library`
--

CREATE TABLE `library` (
  `libraryId` int(11) NOT NULL COMMENT 'A unique id to identify the library within the system',
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
  `showTextThis` tinyint(4) NOT NULL COMMENT 'Whether or not the Text This link is shown',
  `showEmailThis` tinyint(4) NOT NULL COMMENT 'Whether or not the Email This link is shown',
  `showComments` tinyint(4) NOT NULL COMMENT 'Whether or not comments are shown (also disables adding comments)',
  `showFavorites` tinyint(4) NOT NULL COMMENT 'Whether or not uses can maintain favorites lists',
  `allow` tinyint(4) NOT NULL COMMENT 'A link to a library system specific Ask a Librarian page',
  `inSystemPickupsOnly` tinyint(4) NOT NULL COMMENT 'Restrict pickup locations to only locations within the library system which is active.',
  `defaultPType` int(11) NOT NULL,
  `facetLabel` varchar(50) NOT NULL,
  `showEcommerceLink` tinyint(4) NOT NULL,
  `goldRushCode` varchar(10) NOT NULL,
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
  `enablePospectorIntegration` tinyint(4) NOT NULL DEFAULT '0',
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
  `abbreviatedDisplayName` varchar(30) DEFAULT '',
  `showGoDeeper` tinyint(4) DEFAULT '1',
  `showProspectorResultsAtEndOfSearch` tinyint(4) DEFAULT '1',
  `overdriveAdvantageName` varchar(128) DEFAULT '',
  `overdriveAdvantageProductsKey` varchar(20) DEFAULT '',
  `defaultNotNeededAfterDays` int(11) DEFAULT '0',
  `showCheckInGrid` int(11) DEFAULT '1',
  `recordsToBlackList` mediumtext,
  `homeLinkText` varchar(50) DEFAULT 'Home',
  `showOtherFormatCategory` tinyint(1) DEFAULT '1',
  `showWikipediaContent` tinyint(1) DEFAULT '1',
  `payFinesLink` varchar(512) DEFAULT 'default',
  `payFinesLinkText` varchar(512) DEFAULT 'Click to Pay Fines Online',
  `eContentSupportAddress` varchar(256) DEFAULT 'askmarmot@marmot.org',
  `ilsCode` varchar(75) DEFAULT NULL,
  `systemMessage` varchar(512) DEFAULT '',
  `restrictSearchByLibrary` tinyint(1) DEFAULT '0',
  `enableOverdriveCollection` tinyint(1) DEFAULT '1',
  `includeOutOfSystemExternalLinks` tinyint(1) DEFAULT '0',
  `restrictOwningBranchesAndSystems` tinyint(1) DEFAULT '1',
  `showAvailableAtAnyLocation` tinyint(1) DEFAULT '1',
  `boostByLibrary` tinyint(4) DEFAULT '1',
  `allowPatronAddressUpdates` tinyint(1) DEFAULT '1',
  `showWorkPhoneInProfile` tinyint(1) DEFAULT '0',
  `showNoticeTypeInProfile` tinyint(1) DEFAULT '0',
  `showPickupLocationInProfile` tinyint(1) DEFAULT '0',
  `accountingUnit` int(11) DEFAULT '10',
  `additionalCss` mediumtext,
  `allowPinReset` tinyint(1) DEFAULT NULL,
  `additionalLocalBoostFactor` int(11) DEFAULT '1',
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
  `publicListsToInclude` tinyint(1) DEFAULT NULL,
  `horizontalSearchBar` tinyint(1) DEFAULT '0',
  `sideBarOnRight` tinyint(1) DEFAULT '0',
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
  `showPikaLogo` tinyint(4) DEFAULT '1',
  `masqueradeAutomaticTimeoutLength` tinyint(1) UNSIGNED DEFAULT NULL,
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
  `hooplaLibraryID` int(10) UNSIGNED DEFAULT NULL,
  `showOnOrderCounts` tinyint(1) DEFAULT '1',
  `sharedOverdriveCollection` tinyint(1) DEFAULT '-1',
  `showSeriesAsTab` tinyint(4) NOT NULL DEFAULT '0',
  `enableAlphaBrowse` tinyint(4) DEFAULT '1',
  `boopsieLink` varchar(150) CHARACTER SET latin1 NOT NULL,
  `homePageWidgetId` varchar(50) DEFAULT '',
  `searchGroupedRecords` tinyint(4) DEFAULT '0',
  `showStandardSubjects` tinyint(1) DEFAULT '1',
  `theme` int(11) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `library`
--

INSERT INTO `library` (`libraryId`, `subdomain`, `displayName`, `themeName`, `showLibraryFacet`, `showConsortiumFacet`, `allowInBranchHolds`, `allowInLibraryHolds`, `allowConsortiumHolds`, `scope`, `useScope`, `hideCommentsWithBadWords`, `showStandardReviews`, `showHoldButton`, `showLoginButton`, `showTextThis`, `showEmailThis`, `showComments`, `showFavorites`, `allow`, `inSystemPickupsOnly`, `defaultPType`, `facetLabel`, `showEcommerceLink`, `goldRushCode`, `repeatSearchOption`, `repeatInProspector`, `repeatInWorldCat`, `systemsToRepeatIn`, `repeatInOverdrive`, `overdriveAuthenticationILSName`, `overdriveRequirePin`, `homeLink`, `showAdvancedSearchbox`, `validPickupSystems`, `allowProfileUpdates`, `allowRenewals`, `allowFreezeHolds`, `showItsHere`, `holdDisclaimer`, `showHoldCancelDate`, `enablePospectorIntegration`, `prospectorCode`, `showRatings`, `minimumFineAmount`, `enableGenealogy`, `enableCourseReserves`, `exportOptions`, `enableSelfRegistration`, `useHomeLinkInBreadcrumbs`, `enableMaterialsRequest`, `eContentLinkRules`, `notesTabName`, `showHoldButtonInSearchResults`, `showSimilarAuthors`, `showSimilarTitles`, `show856LinksAsTab`, `applyNumberOfHoldingsBoost`, `worldCatUrl`, `worldCatQt`, `preferSyndeticsSummary`, `abbreviatedDisplayName`, `showGoDeeper`, `showProspectorResultsAtEndOfSearch`, `overdriveAdvantageName`, `overdriveAdvantageProductsKey`, `defaultNotNeededAfterDays`, `showCheckInGrid`, `recordsToBlackList`, `homeLinkText`, `showOtherFormatCategory`, `showWikipediaContent`, `payFinesLink`, `payFinesLinkText`, `eContentSupportAddress`, `ilsCode`, `systemMessage`, `restrictSearchByLibrary`, `enableOverdriveCollection`, `includeOutOfSystemExternalLinks`, `restrictOwningBranchesAndSystems`, `showAvailableAtAnyLocation`, `boostByLibrary`, `allowPatronAddressUpdates`, `showWorkPhoneInProfile`, `showNoticeTypeInProfile`, `showPickupLocationInProfile`, `accountingUnit`, `additionalCss`, `allowPinReset`, `additionalLocalBoostFactor`, `maxRequestsPerYear`, `maxOpenRequests`, `twitterLink`, `youtubeLink`, `instagramLink`, `goodreadsLink`, `facebookLink`, `generalContactLink`, `repeatInOnlineCollection`, `showExpirationWarnings`, `econtentLocationsToInclude`, `pTypes`, `showLibraryHoursAndLocationsLink`, `showLibraryHoursNoticeOnAccountPages`, `showShareOnExternalSites`, `showGoodReadsReviews`, `showStaffView`, `showSearchTools`, `barcodePrefix`, `minBarcodeLength`, `maxBarcodeLength`, `showDisplayNameInHeader`, `headerText`, `promptForBirthDateInSelfReg`, `availabilityToggleLabelSuperScope`, `availabilityToggleLabelLocal`, `availabilityToggleLabelAvailable`, `loginFormUsernameLabel`, `loginFormPasswordLabel`, `showDetailedHoldNoticeInformation`, `treatPrintNoticesAsPhoneNotices`, `additionalLocationsToShowAvailabilityFor`, `showInMainDetails`, `includeDplaResults`, `selfRegistrationFormMessage`, `selfRegistrationSuccessMessage`, `useHomeLinkForLogo`, `addSMSIndicatorToPhone`, `showAlternateLibraryOptionsInProfile`, `selfRegistrationTemplate`, `defaultBrowseMode`, `externalMaterialsRequestUrl`, `browseCategoryRatingsMode`, `enableMaterialsBooking`, `isDefault`, `showHoldButtonForUnavailableOnly`, `allowLinkedAccounts`, `allowAutomaticSearchReplacements`, `includeOverDriveAdult`, `includeOverDriveTeen`, `includeOverDriveKids`, `publicListsToInclude`, `horizontalSearchBar`, `sideBarOnRight`, `enableArchive`, `showLCSubjects`, `showBisacSubjects`, `showFastAddSubjects`, `showOtherSubjects`, `maxFinesToAllowAccountUpdates`, `showRefreshAccountButton`, `edsApiProfile`, `edsApiUsername`, `edsApiPassword`, `patronNameDisplayStyle`, `includeAllRecordsInShelvingFacets`, `includeAllRecordsInDateAddedFacets`, `archiveNamespace`, `hideAllCollectionsFromOtherLibraries`, `collectionsToHide`, `preventExpiredCardLogin`, `showInSearchResultsMainDetails`, `alwaysShowSearchResultsMainDetails`, `casHost`, `casPort`, `casContext`, `showSidebarMenu`, `sidebarMenuButtonText`, `allowRequestsForArchiveMaterials`, `archiveRequestEmail`, `archivePid`, `availabilityToggleLabelAvailableOnline`, `includeOnlineMaterialsInAvailableToggle`, `archiveRequestMaterialsHeader`, `showPikaLogo`, `masqueradeAutomaticTimeoutLength`, `allowMasqueradeMode`, `allowReadingHistoryDisplayInMasqueradeMode`, `newMaterialsRequestSummary`, `claimAuthorshipHeader`, `materialsRequestDaysToPreserve`, `archiveRequestFieldName`, `archiveRequestFieldAddress`, `archiveRequestFieldAddress2`, `archiveRequestFieldCity`, `archiveRequestFieldState`, `archiveRequestFieldZip`, `archiveRequestFieldCountry`, `archiveRequestFieldPhone`, `archiveRequestFieldAlternatePhone`, `archiveRequestFieldFormat`, `archiveRequestFieldPurpose`, `archiveMoreDetailsRelatedObjectsOrEntitiesDisplayMode`, `objectsToHide`, `defaultArchiveCollectionBrowseMode`, `showGroupedHoldCopiesCount`, `interLibraryLoanName`, `interLibraryLoanUrl`, `expirationNearMessage`, `expiredMessage`, `edsSearchProfile`, `enableCombinedResults`, `combinedResultsLabel`, `defaultToCombinedResults`, `hooplaLibraryID`, `showOnOrderCounts`, `sharedOverdriveCollection`, `showSeriesAsTab`, `enableAlphaBrowse`, `boopsieLink`, `homePageWidgetId`, `searchGroupedRecords`, `showStandardSubjects`, `theme`) VALUES
(2, 'main', 'Main Library', 'responsive', 1, 0, 1, 1, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 0, 1, 1, '', 0, '', 'none', 0, 0, '', 0, '', 0, '', 1, '', 1, 1, 1, 1, '', 0, 0, '', 1, 0, 0, 0, 'RefWorks|EndNote', 0, 0, 0, '', 'Notes', 1, 1, 1, 0, 1, '', '', 1, 'Main', 1, 0, '', '', -1, 0, '.b20419582\r\n.b13196054\r\n.b13195621\r\n.b13196066\r\n.b13196042', 'Browse Catalog', 1, 1, '/MyAccount/Fines', 'Click to Pay Fines Online', '', '.*', '', 0, 1, 0, 1, 1, 0, 1, 0, 1, 1, 10, '', 0, 1, 60, 5, '', '', '', '', '', '', 0, 1, '', '1', 1, 1, 1, 1, 1, 1, '', 6, 14, 0, '', 0, 'Entire Collection', '', 'Available Now', 'Username', 'Password', 1, 1, '', 'a:9:{i:0;s:10:\"showSeries\";i:1;s:22:\"showPublicationDetails\";i:2;s:11:\"showFormats\";i:3;s:12:\"showEditions\";i:4;s:24:\"showPhysicalDescriptions\";i:5;s:9:\"showISBNs\";i:6;s:10:\"showArInfo\";i:7;s:14:\"showLexileInfo\";i:8;s:18:\"showFountasPinnell\";}', 0, '', '', 1, 0, 1, 'default', 'covers', '', 'none', 0, 1, 0, 1, 1, 1, 1, 1, 3, 1, 0, 0, 1, 1, 1, 1, -1, 0, '', '', '', 'lastinitial_firstname', 0, 0, '', 0, '', 0, 'a:4:{i:0;s:10:\"showSeries\";i:1;s:13:\"showPublisher\";i:2;s:19:\"showPublicationDate\";i:3;s:13:\"showLanguages\";}', 0, '', 0, '', 1, 'Help', 0, '', '', 'Available Online', 0, '', 0, 120, 0, 0, '', '', 365, 2, 1, 1, 1, 1, 1, 1, 2, 1, 1, 2, 'tiled', '', 'covers', 1, 'Interlibrary Loan', '', '', '', '', 0, 'Combined Results', 0, 0, 1, -1, 0, 1, '', '0', 0, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `library_archive_explore_more_bar`
--

CREATE TABLE `library_archive_explore_more_bar` (
  `id` int(10) UNSIGNED NOT NULL,
  `libraryId` int(11) NOT NULL,
  `section` varchar(45) DEFAULT NULL,
  `displayName` varchar(45) DEFAULT NULL,
  `openByDefault` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `weight` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `library_archive_more_details`
--

CREATE TABLE `library_archive_more_details` (
  `id` int(11) NOT NULL,
  `libraryId` int(11) NOT NULL DEFAULT '-1',
  `weight` int(11) NOT NULL DEFAULT '0',
  `section` varchar(25) NOT NULL,
  `collapseByDefault` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `library_archive_search_facet_setting`
--

CREATE TABLE `library_archive_search_facet_setting` (
  `id` int(11) NOT NULL,
  `libraryId` int(11) NOT NULL,
  `displayName` varchar(50) NOT NULL,
  `facetName` varchar(80) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT '0',
  `numEntriesToShowByDefault` int(11) NOT NULL DEFAULT '5',
  `showAsDropDown` tinyint(4) NOT NULL DEFAULT '0',
  `sortMode` enum('alphabetically','num_results') NOT NULL DEFAULT 'num_results',
  `showAboveResults` tinyint(4) NOT NULL DEFAULT '0',
  `showInResults` tinyint(4) NOT NULL DEFAULT '1',
  `showInAuthorResults` tinyint(4) NOT NULL DEFAULT '1',
  `showInAdvancedSearch` tinyint(4) NOT NULL DEFAULT '1',
  `collapseByDefault` tinyint(4) DEFAULT '0',
  `useMoreFacetPopup` tinyint(4) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `library_combined_results_section`
--

CREATE TABLE `library_combined_results_section` (
  `id` int(10) UNSIGNED NOT NULL,
  `libraryId` int(11) NOT NULL,
  `displayName` varchar(255) DEFAULT NULL,
  `source` varchar(45) DEFAULT NULL,
  `numberOfResultsToShow` int(11) NOT NULL DEFAULT '5',
  `weight` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `library_facet_setting`
--

CREATE TABLE `library_facet_setting` (
  `id` int(11) NOT NULL,
  `libraryId` int(11) NOT NULL,
  `displayName` varchar(50) NOT NULL,
  `facetName` varchar(50) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT '0',
  `numEntriesToShowByDefault` int(11) NOT NULL DEFAULT '5',
  `showAsDropDown` tinyint(4) NOT NULL DEFAULT '0',
  `sortMode` enum('alphabetically','num_results') NOT NULL DEFAULT 'num_results',
  `showAboveResults` tinyint(4) NOT NULL DEFAULT '0',
  `showInResults` tinyint(4) NOT NULL DEFAULT '1',
  `showInAuthorResults` tinyint(4) NOT NULL DEFAULT '1',
  `showInAdvancedSearch` tinyint(4) NOT NULL DEFAULT '1',
  `collapseByDefault` tinyint(4) DEFAULT '0',
  `useMoreFacetPopup` tinyint(4) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='A widget that can be displayed within websites';

-- --------------------------------------------------------

--
-- Table structure for table `library_links`
--

CREATE TABLE `library_links` (
  `id` int(11) NOT NULL,
  `libraryId` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `linkText` varchar(100) NOT NULL,
  `url` varchar(255) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT '0',
  `htmlContents` mediumtext,
  `showInAccount` tinyint(4) DEFAULT '0',
  `showInHelp` tinyint(4) DEFAULT '1',
  `showExpanded` tinyint(4) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `library_more_details`
--

CREATE TABLE `library_more_details` (
  `id` int(11) NOT NULL,
  `libraryId` int(11) NOT NULL DEFAULT '-1',
  `weight` int(11) NOT NULL DEFAULT '0',
  `source` varchar(25) NOT NULL,
  `collapseByDefault` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `library_records_owned`
--

CREATE TABLE `library_records_owned` (
  `id` int(11) NOT NULL,
  `libraryId` int(11) NOT NULL,
  `indexingProfileId` int(11) NOT NULL,
  `location` varchar(100) NOT NULL,
  `subLocation` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `library_records_to_include`
--

CREATE TABLE `library_records_to_include` (
  `id` int(11) NOT NULL,
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
  `urlReplacement` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `library_search_source`
--

CREATE TABLE `library_search_source` (
  `id` int(11) NOT NULL,
  `libraryId` int(11) NOT NULL DEFAULT '-1',
  `label` varchar(50) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT '0',
  `searchWhat` enum('catalog','genealogy','overdrive','worldcat','prospector','goldrush','title_browse','author_browse','subject_browse','tags') DEFAULT NULL,
  `defaultFilter` text,
  `defaultSort` enum('relevance','popularity','newest_to_oldest','oldest_to_newest','author','title','user_rating') DEFAULT NULL,
  `catalogScoping` enum('unscoped','library','location') DEFAULT 'unscoped'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `library_top_links`
--

CREATE TABLE `library_top_links` (
  `id` int(11) NOT NULL,
  `libraryId` int(11) NOT NULL,
  `linkText` varchar(100) NOT NULL,
  `url` varchar(255) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `list_widgets`
--

CREATE TABLE `list_widgets` (
  `id` int(11) NOT NULL,
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
  `numTitlesToShow` int(11) NOT NULL DEFAULT '25'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='A widget that can be displayed within Pika or within other sites';

-- --------------------------------------------------------

--
-- Table structure for table `list_widget_lists`
--

CREATE TABLE `list_widget_lists` (
  `id` int(11) NOT NULL,
  `listWidgetId` int(11) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT '0',
  `displayFor` enum('all','loggedIn','notLoggedIn') NOT NULL DEFAULT 'all',
  `name` varchar(50) NOT NULL,
  `source` varchar(500) NOT NULL,
  `fullListLink` varchar(500) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='The lists that should appear within the widget';

-- --------------------------------------------------------

--
-- Table structure for table `list_widget_lists_links`
--

CREATE TABLE `list_widget_lists_links` (
  `id` int(11) NOT NULL,
  `listWidgetListsId` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `link` text NOT NULL,
  `weight` int(3) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `loan_rules`
--

CREATE TABLE `loan_rules` (
  `id` int(11) NOT NULL,
  `loanRuleId` int(11) NOT NULL COMMENT 'The location id',
  `name` varchar(50) NOT NULL COMMENT 'The location code the rule applies to',
  `code` char(1) NOT NULL,
  `normalLoanPeriod` int(4) NOT NULL COMMENT 'Number of days the item checks out for',
  `holdable` tinyint(4) NOT NULL DEFAULT '0',
  `bookable` tinyint(4) NOT NULL DEFAULT '0',
  `homePickup` tinyint(4) NOT NULL DEFAULT '0',
  `shippable` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `loan_rule_determiners`
--

CREATE TABLE `loan_rule_determiners` (
  `id` int(11) NOT NULL,
  `rowNumber` int(11) NOT NULL COMMENT 'The row of the determiner.  Rules are processed in reverse order',
  `location` varchar(10) NOT NULL,
  `patronType` varchar(255) NOT NULL COMMENT 'The patron types that this rule applies to',
  `itemType` varchar(255) NOT NULL DEFAULT '0' COMMENT 'The item types that this rule applies to',
  `ageRange` varchar(10) NOT NULL,
  `loanRuleId` varchar(10) NOT NULL COMMENT 'Close hour (24hr format) HH:MM',
  `active` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `location`
--

CREATE TABLE `location` (
  `locationId` int(11) NOT NULL COMMENT 'A unique Id for the branch or location within vuFind',
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
  `recordsToBlackList` mediumtext,
  `automaticTimeoutLength` int(11) DEFAULT '90',
  `automaticTimeoutLengthLoggedOut` int(11) DEFAULT '450',
  `restrictSearchByLocation` tinyint(1) DEFAULT '0',
  `enableOverdriveCollection` tinyint(1) DEFAULT '1',
  `suppressHoldings` tinyint(1) DEFAULT '0',
  `boostByLocation` tinyint(4) DEFAULT '1',
  `additionalCss` mediumtext,
  `additionalLocalBoostFactor` int(11) DEFAULT '1',
  `repeatInOnlineCollection` int(11) DEFAULT '1',
  `econtentLocationsToInclude` varchar(255) DEFAULT NULL,
  `showInLocationsAndHoursList` int(11) DEFAULT '1',
  `showShareOnExternalSites` int(11) DEFAULT '1',
  `showTextThis` int(11) DEFAULT '1',
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
  `publicListsToInclude` tinyint(1) DEFAULT NULL,
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
  `theme` int(11) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores information about the various locations that are part';

--
-- Dumping data for table `location`
--

INSERT INTO `location` (`locationId`, `code`, `displayName`, `libraryId`, `validHoldPickupBranch`, `nearbyLocation1`, `nearbyLocation2`, `holdingBranchLabel`, `scope`, `useScope`, `facetFile`, `showHoldButton`, `isMainBranch`, `showStandardReviews`, `repeatSearchOption`, `facetLabel`, `repeatInProspector`, `repeatInWorldCat`, `systemsToRepeatIn`, `repeatInOverdrive`, `homeLink`, `defaultPType`, `ptypesToAllowRenewals`, `recordsToBlackList`, `automaticTimeoutLength`, `automaticTimeoutLengthLoggedOut`, `restrictSearchByLocation`, `enableOverdriveCollection`, `suppressHoldings`, `boostByLocation`, `additionalCss`, `additionalLocalBoostFactor`, `repeatInOnlineCollection`, `econtentLocationsToInclude`, `showInLocationsAndHoursList`, `showShareOnExternalSites`, `showTextThis`, `showEmailThis`, `showFavorites`, `showComments`, `showGoodReadsReviews`, `showStaffView`, `address`, `phone`, `showDisplayNameInHeader`, `headerText`, `availabilityToggleLabelSuperScope`, `availabilityToggleLabelLocal`, `availabilityToggleLabelAvailable`, `defaultBrowseMode`, `browseCategoryRatingsMode`, `subLocation`, `includeOverDriveAdult`, `includeOverDriveTeen`, `includeOverDriveKids`, `publicListsToInclude`, `includeAllLibraryBranchesInFacets`, `additionalLocationsToShowAvailabilityFor`, `includeAllRecordsInShelvingFacets`, `includeAllRecordsInDateAddedFacets`, `availabilityToggleLabelAvailableOnline`, `baseAvailabilityToggleOnLocalHoldingsOnly`, `includeOnlineMaterialsInAvailableToggle`, `subdomain`, `includeLibraryRecordsToInclude`, `useLibraryCombinedResultsSettings`, `enableCombinedResults`, `combinedResultsLabel`, `defaultToCombinedResults`, `footerTemplate`, `homePageWidgetId`, `theme`) VALUES
(1, 'main', 'Main Library', 2, 1, -1, -1, '', 0, 0, 'default', 1, 0, 1, 'marmot', '', 0, 0, '', 0, '', -1, '*', NULL, 90, 450, 0, 1, 0, 1, '', 1, 0, NULL, 1, 1, 1, 1, 1, 1, 1, 1, '', '', 0, '', 'Entire Collection', '{display name}', 'Available Now', '', '', '', 1, 1, 1, 0, 1, '', 0, 0, 'Available Online', 0, 0, '', 1, 1, 0, 'Combined Results', 1, 'default', '', 1);

-- --------------------------------------------------------

--
-- Table structure for table `location_combined_results_section`
--

CREATE TABLE `location_combined_results_section` (
  `id` int(10) UNSIGNED NOT NULL,
  `locationId` int(11) NOT NULL,
  `displayName` varchar(255) DEFAULT NULL,
  `source` varchar(45) DEFAULT NULL,
  `numberOfResultsToShow` int(11) NOT NULL DEFAULT '5',
  `weight` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `location_facet_setting`
--

CREATE TABLE `location_facet_setting` (
  `id` int(11) NOT NULL,
  `locationId` int(11) NOT NULL,
  `displayName` varchar(50) NOT NULL,
  `facetName` varchar(50) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT '0',
  `numEntriesToShowByDefault` int(11) NOT NULL DEFAULT '5',
  `showAsDropDown` tinyint(4) NOT NULL DEFAULT '0',
  `sortMode` enum('alphabetically','num_results') NOT NULL DEFAULT 'num_results',
  `showAboveResults` tinyint(4) NOT NULL DEFAULT '0',
  `showInResults` tinyint(4) NOT NULL DEFAULT '1',
  `showInAuthorResults` tinyint(4) NOT NULL DEFAULT '1',
  `showInAdvancedSearch` tinyint(4) NOT NULL DEFAULT '1',
  `collapseByDefault` tinyint(4) DEFAULT '0',
  `useMoreFacetPopup` tinyint(4) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='A widget that can be displayed within websites';

-- --------------------------------------------------------

--
-- Table structure for table `location_hours`
--

CREATE TABLE `location_hours` (
  `id` int(11) NOT NULL COMMENT 'The id of hours entry',
  `locationId` int(11) NOT NULL COMMENT 'The location id',
  `day` int(11) NOT NULL COMMENT 'Day of the week 0 to 7 (Sun to Monday)',
  `closed` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether or not the library is closed on this day',
  `open` varchar(10) NOT NULL COMMENT 'Open hour (24hr format) HH:MM',
  `close` varchar(10) NOT NULL COMMENT 'Close hour (24hr format) HH:MM'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `location_more_details`
--

CREATE TABLE `location_more_details` (
  `id` int(11) NOT NULL,
  `locationId` int(11) NOT NULL DEFAULT '-1',
  `weight` int(11) NOT NULL DEFAULT '0',
  `source` varchar(25) NOT NULL,
  `collapseByDefault` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `location_records_owned`
--

CREATE TABLE `location_records_owned` (
  `id` int(11) NOT NULL,
  `locationId` int(11) NOT NULL,
  `indexingProfileId` int(11) NOT NULL,
  `location` varchar(100) NOT NULL,
  `subLocation` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `location_records_to_include`
--

CREATE TABLE `location_records_to_include` (
  `id` int(11) NOT NULL,
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
  `urlReplacement` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `location_search_source`
--

CREATE TABLE `location_search_source` (
  `id` int(11) NOT NULL,
  `locationId` int(11) NOT NULL DEFAULT '-1',
  `label` varchar(50) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT '0',
  `searchWhat` enum('catalog','genealogy','overdrive','worldcat','prospector','goldrush','title_browse','author_browse','subject_browse','tags') DEFAULT NULL,
  `defaultFilter` text,
  `defaultSort` enum('relevance','popularity','newest_to_oldest','oldest_to_newest','author','title','user_rating') DEFAULT NULL,
  `catalogScoping` enum('unscoped','library','location') DEFAULT 'unscoped'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `marriage`
--

CREATE TABLE `marriage` (
  `marriageId` int(11) NOT NULL,
  `personId` int(11) NOT NULL COMMENT 'A link to one person in the marriage',
  `spouseName` varchar(200) DEFAULT NULL COMMENT 'The name of the other person in the marriage if they aren''t in the database',
  `spouseId` int(11) DEFAULT NULL COMMENT 'A link to the second person in the marriage if the person is in the database',
  `marriageDate` date DEFAULT NULL COMMENT 'The date of the marriage if known.',
  `comments` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Information about a marriage between two people';

-- --------------------------------------------------------

--
-- Table structure for table `materials_request`
--

CREATE TABLE `materials_request` (
  `id` int(11) NOT NULL,
  `libraryId` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `author` varchar(255) DEFAULT NULL,
  `format` varchar(25) DEFAULT NULL,
  `formatId` int(10) UNSIGNED DEFAULT NULL,
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
  `assignedTo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `materials_request_fields_to_display`
--

CREATE TABLE `materials_request_fields_to_display` (
  `id` int(10) UNSIGNED NOT NULL,
  `libraryId` int(11) NOT NULL,
  `columnNameToDisplay` varchar(30) NOT NULL,
  `labelForColumnToDisplay` varchar(45) NOT NULL,
  `weight` smallint(2) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `materials_request_formats`
--

CREATE TABLE `materials_request_formats` (
  `id` int(10) UNSIGNED NOT NULL,
  `libraryId` int(10) UNSIGNED NOT NULL,
  `format` varchar(30) NOT NULL,
  `formatLabel` varchar(60) NOT NULL,
  `authorLabel` varchar(45) NOT NULL,
  `weight` smallint(2) UNSIGNED NOT NULL DEFAULT '0',
  `specialFields` set('Abridged/Unabridged','Article Field','Eaudio format','Ebook format','Season') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `materials_request_form_fields`
--

CREATE TABLE `materials_request_form_fields` (
  `id` int(10) UNSIGNED NOT NULL,
  `libraryId` int(10) UNSIGNED NOT NULL,
  `formCategory` varchar(55) NOT NULL,
  `fieldLabel` varchar(255) NOT NULL,
  `fieldType` varchar(30) DEFAULT NULL,
  `weight` smallint(2) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `materials_request_status`
--

CREATE TABLE `materials_request_status` (
  `id` int(11) NOT NULL,
  `description` varchar(80) DEFAULT NULL,
  `isDefault` tinyint(4) DEFAULT '0',
  `sendEmailToPatron` tinyint(4) DEFAULT NULL,
  `emailTemplate` text,
  `isOpen` tinyint(4) DEFAULT NULL,
  `isPatronCancel` tinyint(4) DEFAULT NULL,
  `libraryId` int(11) DEFAULT '-1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `materials_request_status`
--

INSERT INTO `materials_request_status` (`id`, `description`, `isDefault`, `sendEmailToPatron`, `emailTemplate`, `isOpen`, `isPatronCancel`, `libraryId`) VALUES
(1, 'Request Pending', 1, 0, '', 1, 0, -1),
(2, 'Already owned/On order', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The Library already owns this item or it is already on order. Please access our catalog to place this item on hold.  Please check our online catalog periodically to put a hold for this item.', 0, 0, -1),
(3, 'Item purchased', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. Outcome: The library is purchasing the item you requested. Please check our online catalog periodically to put yourself on hold for this item. We anticipate that this item will be available soon for you to place a hold.', 0, 0, -1),
(4, 'Referred to Collection Development - Adult', 0, 0, '', 1, 0, -1),
(5, 'Referred to Collection Development - J/YA', 0, 0, '', 1, 0, -1),
(6, 'Referred to Collection Development - AV', 0, 0, '', 1, 0, -1),
(7, 'ILL Under Review', 0, 0, '', 1, 0, -1),
(8, 'Request Referred to ILL', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The library\'s Interlibrary loan department is reviewing your request. We will attempt to borrow this item from another system. This process generally takes about 2 - 6 weeks.', 1, 0, -1),
(9, 'Request Filled by ILL', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. Our Interlibrary Loan Department is set to borrow this item from another library.', 0, 0, -1),
(10, 'Ineligible ILL', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. Your library account is not eligible for interlibrary loan at this time.', 0, 0, -1),
(11, 'Not enough info - please contact Collection Development to clarify', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We need more specific information in order to locate the exact item you need. Please re-submit your request with more details.', 1, 0, -1),
(12, 'Unable to acquire the item - out of print', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is out of print.', 0, 0, -1),
(13, 'Unable to acquire the item - not available in the US', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is not available in the US.', 0, 0, -1),
(14, 'Unable to acquire the item - not available from vendor', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is not available from a preferred vendor.', 0, 0, -1),
(15, 'Unable to acquire the item - not published', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The item you requested has not yet been published. Please check our catalog when the publication date draws near.', 0, 0, -1),
(16, 'Unable to acquire the item - price', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item does not fit our collection guidelines.', 0, 0, -1),
(17, 'Unable to acquire the item - publication date', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item does not fit our collection guidelines.', 0, 0, -1),
(18, 'Unavailable', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The item you requested cannot be purchased at this time from any of our regular suppliers and is not available from any of our lending libraries.', 0, 0, -1),
(19, 'Cancelled by Patron', 0, 0, '', 0, 1, -1),
(20, 'Cancelled - Duplicate Request', 0, 0, '', 0, 0, -1),
(21, 'Request Pending', 1, 0, '', 1, NULL, -1),
(22, 'Already owned/On order', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The Library already owns this item or it is already on order. Please access our catalog to place this item on hold.	Please check our online catalog periodically to put a hold for this item.', 0, NULL, -1),
(23, 'Item purchased', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. Outcome: The library is purchasing the item you requested. Please check our online catalog periodically to put yourself on hold for this item. We anticipate that this item will be available soon for you to place a hold.', 0, NULL, -1),
(24, 'Referred to Collection Development - Adult', 0, 0, '', 1, NULL, -1),
(25, 'Referred to Collection Development - J/YA', 0, 0, '', 1, NULL, -1),
(26, 'Referred to Collection Development - AV', 0, 0, '', 1, NULL, -1),
(27, 'ILL Under Review', 0, 0, '', 1, NULL, -1),
(28, 'Request Referred to ILL', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The library\'s Interlibrary loan department is reviewing your request. We will attempt to borrow this item from another system. This process generally takes about 2 - 6 weeks.', 1, NULL, -1),
(29, 'Request Filled by ILL', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. Our Interlibrary Loan Department is set to borrow this item from another library.', 0, NULL, -1),
(30, 'Ineligible ILL', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. Your library account is not eligible for interlibrary loan at this time.', 0, NULL, -1),
(31, 'Not enough info - please contact Collection Development to clarify', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We need more specific information in order to locate the exact item you need. Please re-submit your request with more details.', 1, NULL, -1),
(32, 'Unable to acquire the item - out of print', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is out of print.', 0, NULL, -1),
(33, 'Unable to acquire the item - not available in the US', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is not available in the US.', 0, NULL, -1),
(34, 'Unable to acquire the item - not available from vendor', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is not available from a preferred vendor.', 0, NULL, -1),
(35, 'Unable to acquire the item - not published', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The item you requested has not yet been published. Please check our catalog when the publication date draws near.', 0, NULL, -1),
(36, 'Unable to acquire the item - price', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item does not fit our collection guidelines.', 0, NULL, -1),
(37, 'Unable to acquire the item - publication date', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item does not fit our collection guidelines.', 0, NULL, -1),
(38, 'Unavailable', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The item you requested cannot be purchased at this time from any of our regular suppliers and is not available from any of our lending libraries.', 0, NULL, -1),
(39, 'Cancelled by Patron', 0, 0, '', 0, 1, -1),
(40, 'Cancelled - Duplicate Request', 0, 0, '', 0, NULL, -1);

-- --------------------------------------------------------

--
-- Table structure for table `merged_grouped_works`
--

CREATE TABLE `merged_grouped_works` (
  `id` bigint(20) NOT NULL,
  `sourceGroupedWorkId` char(36) NOT NULL,
  `destinationGroupedWorkId` char(36) NOT NULL,
  `notes` varchar(250) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `merged_records`
--

CREATE TABLE `merged_records` (
  `id` int(11) NOT NULL,
  `original_record` varchar(20) NOT NULL,
  `new_record` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `millennium_cache`
--

CREATE TABLE `millennium_cache` (
  `recordId` varchar(20) NOT NULL COMMENT 'The recordId being checked',
  `scope` int(16) NOT NULL COMMENT 'The scope that was loaded',
  `holdingsInfo` longtext NOT NULL COMMENT 'Raw HTML returned from Millennium for holdings',
  `framesetInfo` longtext NOT NULL COMMENT 'Raw HTML returned from Millennium on the frameset page',
  `cacheDate` int(16) NOT NULL COMMENT 'When the entry was recorded in the cache'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Caches information from Millennium so we do not have to cont';

-- --------------------------------------------------------

--
-- Table structure for table `nongrouped_records`
--

CREATE TABLE `nongrouped_records` (
  `id` int(11) NOT NULL,
  `source` varchar(50) NOT NULL,
  `recordId` varchar(36) NOT NULL,
  `notes` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `non_holdable_locations`
--

CREATE TABLE `non_holdable_locations` (
  `locationId` int(11) NOT NULL COMMENT 'A unique id for the non holdable location',
  `millenniumCode` varchar(5) NOT NULL COMMENT 'The internal 5 letter code within Millennium',
  `holdingDisplay` varchar(30) NOT NULL COMMENT 'The text displayed in the holdings list within Millennium',
  `availableAtCircDesk` tinyint(4) NOT NULL COMMENT 'The item is available if the patron visits the circulation desk.'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `novelist_data`
--

CREATE TABLE `novelist_data` (
  `id` int(11) NOT NULL,
  `groupedRecordPermanentId` varchar(36) DEFAULT NULL,
  `lastUpdate` int(11) DEFAULT NULL,
  `hasNovelistData` tinyint(1) DEFAULT NULL,
  `groupedRecordHasISBN` tinyint(1) DEFAULT NULL,
  `primaryISBN` varchar(13) DEFAULT NULL,
  `seriesTitle` varchar(255) DEFAULT NULL,
  `seriesNote` varchar(255) DEFAULT NULL,
  `volume` varchar(32) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `obituary`
--

CREATE TABLE `obituary` (
  `obituaryId` int(11) NOT NULL,
  `personId` int(11) NOT NULL COMMENT 'The person this obituary is for',
  `source` varchar(255) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `sourcePage` varchar(25) DEFAULT NULL,
  `contents` mediumtext,
  `picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Information about an obituary for a person';

-- --------------------------------------------------------

--
-- Table structure for table `offline_circulation`
--

CREATE TABLE `offline_circulation` (
  `id` int(11) NOT NULL,
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
  `notes` varchar(512) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `offline_hold`
--

CREATE TABLE `offline_hold` (
  `id` int(11) NOT NULL,
  `timeEntered` int(11) NOT NULL,
  `timeProcessed` int(11) DEFAULT NULL,
  `bibId` varchar(10) NOT NULL,
  `patronId` int(11) DEFAULT NULL,
  `patronBarcode` varchar(20) DEFAULT NULL,
  `status` enum('Not Processed','Hold Succeeded','Hold Failed') DEFAULT NULL,
  `notes` varchar(512) DEFAULT NULL,
  `patronName` varchar(200) DEFAULT NULL,
  `itemId` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `overdrive_account_cache`
--

CREATE TABLE `overdrive_account_cache` (
  `id` int(11) NOT NULL,
  `userId` int(11) DEFAULT NULL,
  `holdPage` longtext,
  `holdPageLastLoaded` int(11) NOT NULL DEFAULT '0',
  `bookshelfPage` longtext,
  `bookshelfPageLastLoaded` int(11) NOT NULL DEFAULT '0',
  `wishlistPage` longtext,
  `wishlistPageLastLoaded` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='A cache to store information about a user''s account within OverDrive.';

-- --------------------------------------------------------

--
-- Table structure for table `overdrive_api_products`
--

CREATE TABLE `overdrive_api_products` (
  `id` int(11) NOT NULL,
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
  `rawData` mediumtext,
  `subtitle` varchar(255) DEFAULT NULL,
  `crossRefId` int(11) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `overdrive_api_product_availability`
--

CREATE TABLE `overdrive_api_product_availability` (
  `id` int(11) NOT NULL,
  `productId` int(11) DEFAULT NULL,
  `libraryId` int(11) DEFAULT NULL,
  `available` tinyint(1) DEFAULT NULL,
  `copiesOwned` int(11) DEFAULT NULL,
  `copiesAvailable` int(11) DEFAULT NULL,
  `numberOfHolds` int(11) DEFAULT NULL,
  `availabilityType` varchar(35) DEFAULT 'Normal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `overdrive_api_product_formats`
--

CREATE TABLE `overdrive_api_product_formats` (
  `id` int(11) NOT NULL,
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
  `sampleUrl_2` varchar(215) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `overdrive_api_product_identifiers`
--

CREATE TABLE `overdrive_api_product_identifiers` (
  `id` int(11) NOT NULL,
  `productId` int(11) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `value` varchar(75) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `overdrive_api_product_metadata`
--

CREATE TABLE `overdrive_api_product_metadata` (
  `id` int(11) NOT NULL,
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
  `isOwnedByCollections` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `overdrive_extract_log`
--

CREATE TABLE `overdrive_extract_log` (
  `id` int(11) NOT NULL,
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
  `notes` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `person`
--

CREATE TABLE `person` (
  `personId` int(11) NOT NULL,
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
  `importedFrom` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores information about a particular person for use in genealogy';

-- --------------------------------------------------------

--
-- Table structure for table `ptype`
--

CREATE TABLE `ptype` (
  `id` int(11) NOT NULL,
  `pType` varchar(20) NOT NULL,
  `maxHolds` int(11) NOT NULL DEFAULT '300',
  `masquerade` varchar(45) NOT NULL DEFAULT 'none'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `ptype_restricted_locations`
--

CREATE TABLE `ptype_restricted_locations` (
  `locationId` int(11) NOT NULL COMMENT 'A unique id for the non holdable location',
  `millenniumCode` varchar(5) NOT NULL COMMENT 'The internal 5 letter code within Millennium',
  `holdingDisplay` varchar(30) NOT NULL COMMENT 'The text displayed in the holdings list within Millennium can use regular expression syntax to match multiple locations',
  `allowablePtypes` varchar(50) NOT NULL COMMENT 'A list of PTypes that are allowed to place holds on items with this location separated with pipes (|).'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rbdigital_availability`
--

CREATE TABLE `rbdigital_availability` (
  `id` int(11) NOT NULL,
  `rbdigitalId` varchar(25) NOT NULL,
  `isAvailable` tinyint(4) NOT NULL DEFAULT '1',
  `isOwned` tinyint(4) NOT NULL DEFAULT '1',
  `name` varchar(50) DEFAULT NULL,
  `rawChecksum` bigint(20) DEFAULT NULL,
  `rawResponse` mediumtext,
  `lastChange` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rbdigital_export_log`
--

CREATE TABLE `rbdigital_export_log` (
  `id` int(11) NOT NULL COMMENT 'The id of log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `notes` text COMMENT 'Additional information about the run'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rbdigital_title`
--

CREATE TABLE `rbdigital_title` (
  `id` int(11) NOT NULL,
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
  `deleted` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `record_grouping_log`
--

CREATE TABLE `record_grouping_log` (
  `id` int(11) NOT NULL COMMENT 'The id of log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `notes` text COMMENT 'Additional information about the run includes stats per source'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `reindex_log`
--

CREATE TABLE `reindex_log` (
  `id` int(11) NOT NULL COMMENT 'The id of reindex log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the reindex started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the reindex process ended',
  `notes` text COMMENT 'Notes related to the overall process',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The last time the log was updated',
  `numWorksProcessed` int(11) NOT NULL DEFAULT '0',
  `numListsProcessed` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `roleId` int(11) NOT NULL,
  `name` varchar(50) NOT NULL COMMENT 'The internal name of the role',
  `description` varchar(100) NOT NULL COMMENT 'A description of what the role allows'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='A role identifying what the user can do.';

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`roleId`, `name`, `description`) VALUES
(1, 'userAdmin', 'Allows administration of users.'),
(2, 'opacAdmin', 'Allows administration of the opac display (libraries, locations, etc).'),
(3, 'genealogyContributor', 'Allows Genealogy data to be entered  by the user.'),
(5, 'cataloging', 'Allows user to perform cataloging activities.'),
(6, 'libraryAdmin', 'Allows user to update library configuration for their library system only for their home location.'),
(7, 'contentEditor', 'Allows entering of editorial reviews and creation of widgets.'),
(8, 'library_material_requests', 'Allows user to manage material requests for a specific library.'),
(9, 'locationReports', 'Allows the user to view reports for their location.'),
(10, 'libraryManager', 'Allows user to do basic configuration for their library.'),
(11, 'locationManager', 'Allows user to do basic configuration for their location.'),
(12, 'circulationReports', 'Allows user to view offline circulation reports.'),
(13, 'listPublisher', 'Optionally only include lists from people with this role in search results.'),
(14, 'archives', 'Control overall archives integration.');

-- --------------------------------------------------------

--
-- Table structure for table `search`
--

CREATE TABLE `search` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `session_id` varchar(128) DEFAULT NULL,
  `folder_id` int(11) DEFAULT NULL,
  `created` date NOT NULL,
  `title` varchar(20) DEFAULT NULL,
  `saved` int(1) NOT NULL DEFAULT '0',
  `search_object` blob,
  `searchSource` varchar(30) NOT NULL DEFAULT 'local'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `search_stats_new`
--

CREATE TABLE `search_stats_new` (
  `id` int(11) NOT NULL COMMENT 'The unique id of the search statistic',
  `phrase` varchar(500) NOT NULL COMMENT 'The phrase being searched for',
  `lastSearch` int(16) NOT NULL COMMENT 'The last time this search was done',
  `numSearches` int(16) NOT NULL COMMENT 'The number of times this search has been done.'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Statistical information about searches for use in reporting ';

-- --------------------------------------------------------

--
-- Table structure for table `session`
--

CREATE TABLE `session` (
  `id` int(11) NOT NULL,
  `session_id` varchar(128) DEFAULT NULL,
  `data` mediumtext,
  `last_used` int(12) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `remember_me` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether or not the session was started with remember me on.'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sierra_api_export_log`
--

CREATE TABLE `sierra_api_export_log` (
  `id` int(11) NOT NULL COMMENT 'The id of log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
  `notes` text COMMENT 'Additional information about the run',
  `numRecordsToProcess` int(11) DEFAULT NULL,
  `numRecordsProcessed` int(11) DEFAULT NULL,
  `numErrors` int(11) DEFAULT NULL,
  `numRemainingRecords` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sierra_export_field_mapping`
--

CREATE TABLE `sierra_export_field_mapping` (
  `id` int(11) NOT NULL COMMENT 'The id of field mapping',
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
  `eContentExportFieldTag` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `syndetics_data`
--

CREATE TABLE `syndetics_data` (
  `id` int(11) NOT NULL,
  `groupedRecordPermanentId` varchar(36) DEFAULT NULL,
  `lastUpdate` int(11) DEFAULT NULL,
  `hasSyndeticsData` tinyint(1) DEFAULT NULL,
  `primaryIsbn` varchar(13) DEFAULT NULL,
  `primaryUpc` varchar(25) DEFAULT NULL,
  `description` mediumtext,
  `tableOfContents` mediumtext,
  `excerpt` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `themes`
--

CREATE TABLE `themes` (
  `id` int(11) NOT NULL,
  `themeName` varchar(100) NOT NULL,
  `extendsTheme` varchar(100) DEFAULT NULL,
  `logoName` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `themes`
--

INSERT INTO `themes` (`id`, `themeName`, `extendsTheme`, `logoName`) VALUES
(1, 'default', '', 'logoNameTL_Logo_final.png');

-- --------------------------------------------------------

--
-- Table structure for table `time_to_reshelve`
--

CREATE TABLE `time_to_reshelve` (
  `id` int(11) NOT NULL,
  `indexingProfileId` int(11) NOT NULL,
  `locations` varchar(100) NOT NULL,
  `numHoursToOverride` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `groupedStatus` varchar(50) NOT NULL,
  `weight` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `translation_maps`
--

CREATE TABLE `translation_maps` (
  `id` int(11) NOT NULL,
  `indexingProfileId` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `usesRegularExpressions` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `translation_map_values`
--

CREATE TABLE `translation_map_values` (
  `id` int(11) NOT NULL,
  `translationMapId` int(11) NOT NULL,
  `value` varchar(50) NOT NULL,
  `translation` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `usage_tracking`
--

CREATE TABLE `usage_tracking` (
  `usageId` int(11) NOT NULL,
  `ipId` int(11) NOT NULL,
  `locationId` int(11) NOT NULL,
  `numPageViews` int(11) NOT NULL DEFAULT '0',
  `numHolds` int(11) NOT NULL DEFAULT '0',
  `numRenewals` int(11) NOT NULL DEFAULT '0',
  `trackingDate` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `username` varchar(30) NOT NULL DEFAULT '',
  `password` varchar(32) NOT NULL DEFAULT '',
  `firstname` varchar(50) NOT NULL DEFAULT '',
  `lastname` varchar(50) NOT NULL DEFAULT '',
  `email` varchar(250) NOT NULL DEFAULT '',
  `cat_username` varchar(50) DEFAULT NULL,
  `cat_password` varchar(50) DEFAULT NULL,
  `college` varchar(100) NOT NULL DEFAULT '',
  `major` varchar(100) NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `homeLocationId` int(11) NOT NULL COMMENT 'A link to the locations table for the users home location (branch) defined in millennium',
  `myLocation1Id` int(11) NOT NULL COMMENT 'A link to the locations table representing an alternate branch the users frequents or that is close by',
  `myLocation2Id` int(11) NOT NULL COMMENT 'A link to the locations table representing an alternate branch the users frequents or that is close by',
  `trackReadingHistory` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether or not Reading History should be tracked.',
  `bypassAutoLogout` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether or not the user wants to bypass the automatic logout code on public workstations.',
  `displayName` varchar(30) NOT NULL DEFAULT '',
  `disableCoverArt` tinyint(4) NOT NULL DEFAULT '0',
  `disableRecommendations` tinyint(4) NOT NULL DEFAULT '0',
  `phone` varchar(30) NOT NULL DEFAULT '',
  `patronType` varchar(30) NOT NULL DEFAULT '',
  `overdriveEmail` varchar(250) NOT NULL DEFAULT '',
  `promptForOverdriveEmail` tinyint(4) DEFAULT '1',
  `preferredLibraryInterface` int(11) DEFAULT NULL,
  `initialReadingHistoryLoaded` tinyint(4) DEFAULT '0',
  `noPromptForUserReviews` tinyint(1) DEFAULT '0',
  `source` varchar(50) DEFAULT 'ils'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `username`, `password`, `firstname`, `lastname`, `email`, `cat_username`, `cat_password`, `college`, `major`, `created`, `homeLocationId`, `myLocation1Id`, `myLocation2Id`, `trackReadingHistory`, `bypassAutoLogout`, `displayName`, `disableCoverArt`, `disableRecommendations`, `phone`, `patronType`, `overdriveEmail`, `promptForOverdriveEmail`, `preferredLibraryInterface`, `initialReadingHistoryLoaded`, `noPromptForUserReviews`, `source`) VALUES
(1, 'aspen_admin', 'password', 'Aspen', 'Administrator', '', 'aspen_admin', 'password', '', '', '0000-00-00 00:00:00', 0, 0, 0, 0, 0, '', 0, 0, '', '', '', 1, NULL, 0, 0, 'ils');

-- --------------------------------------------------------

--
-- Table structure for table `user_link`
--

CREATE TABLE `user_link` (
  `id` int(11) NOT NULL,
  `primaryAccountId` int(11) NOT NULL,
  `linkedAccountId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_link_blocks`
--

CREATE TABLE `user_link_blocks` (
  `id` int(10) UNSIGNED NOT NULL,
  `primaryAccountId` int(10) UNSIGNED NOT NULL,
  `blockedLinkAccountId` int(10) UNSIGNED DEFAULT NULL COMMENT 'A specific account primaryAccountId will not be linked to.',
  `blockLinking` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'Indicates primaryAccountId will not be linked to any other accounts.'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_list`
--

CREATE TABLE `user_list` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` mediumtext,
  `public` int(11) NOT NULL DEFAULT '0',
  `dateUpdated` int(11) DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT '0',
  `created` int(11) DEFAULT NULL,
  `defaultSort` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_list_entry`
--

CREATE TABLE `user_list_entry` (
  `id` int(11) NOT NULL,
  `groupedWorkPermanentId` varchar(36) DEFAULT NULL,
  `listId` int(11) DEFAULT NULL,
  `notes` mediumtext,
  `dateAdded` int(11) DEFAULT NULL,
  `weight` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_not_interested`
--

CREATE TABLE `user_not_interested` (
  `id` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `dateMarked` int(11) DEFAULT NULL,
  `groupedRecordPermanentId` varchar(36) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_rating`
--

CREATE TABLE `user_rating` (
  `id` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `resourceid` int(11) NOT NULL,
  `rating` int(1) NOT NULL,
  `dateRated` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_reading_history_work`
--

CREATE TABLE `user_reading_history_work` (
  `id` int(11) NOT NULL,
  `userId` int(11) NOT NULL COMMENT 'The id of the user who checked out the item',
  `groupedWorkPermanentId` char(36) NOT NULL,
  `source` varchar(25) NOT NULL COMMENT 'The source of the record being checked out',
  `sourceId` varchar(50) NOT NULL COMMENT 'The id of the item that item that was checked out within the source',
  `title` varchar(150) DEFAULT NULL COMMENT 'The title of the item in case this is ever deleted',
  `author` varchar(75) DEFAULT NULL COMMENT 'The author of the item in case this is ever deleted',
  `format` varchar(50) DEFAULT NULL COMMENT 'The format of the item in case this is ever deleted',
  `checkOutDate` int(11) NOT NULL COMMENT 'The first day we detected that the item was checked out to the patron',
  `checkInDate` int(11) DEFAULT NULL COMMENT 'The last day we detected that the item was checked out to the patron.',
  `deleted` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='The reading history for patrons';

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `userId` int(11) NOT NULL,
  `roleId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Links users with roles so users can perform administration f';

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`userId`, `roleId`) VALUES
(1, 1),
(1, 2);

-- --------------------------------------------------------

--
-- Table structure for table `user_staff_settings`
--

CREATE TABLE `user_staff_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `userId` int(10) UNSIGNED NOT NULL,
  `materialsRequestReplyToAddress` varchar(70) DEFAULT NULL,
  `materialsRequestEmailSignature` tinytext
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_work_review`
--

CREATE TABLE `user_work_review` (
  `id` int(11) NOT NULL,
  `groupedRecordPermanentId` varchar(36) DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  `rating` tinyint(1) DEFAULT NULL,
  `review` mediumtext,
  `dateRated` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `variables`
--

CREATE TABLE `variables` (
  `id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `value` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `variables`
--

INSERT INTO `variables` (`id`, `name`, `value`) VALUES
(1, 'lastHooplaExport', 'false'),
(2, 'validateChecksumsFromDisk', 'false'),
(3, 'offline_mode_when_offline_login_allowed', 'false'),
(4, 'fullReindexIntervalWarning', '86400'),
(5, 'fullReindexIntervalCritical', '129600'),
(6, 'bypass_export_validation', '0'),
(7, 'last_validatemarcexport_time', NULL),
(8, 'last_export_valid', '1'),
(9, 'record_grouping_running', 'false'),
(10, 'last_grouping_time', NULL),
(25, 'partial_reindex_running', 'true'),
(26, 'last_reindex_time', NULL),
(27, 'lastPartialReindexFinish', NULL),
(29, 'full_reindex_running', 'false'),
(37, 'lastFullReindexFinish', NULL),
(44, 'num_title_in_unique_sitemap', '20000'),
(45, 'num_titles_in_most_popular_sitemap', '20000'),
(46, 'lastRbdigitalExport', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account_profiles`
--
ALTER TABLE `account_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `archive_private_collections`
--
ALTER TABLE `archive_private_collections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `archive_requests`
--
ALTER TABLE `archive_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pid` (`pid`),
  ADD KEY `name` (`name`);

--
-- Indexes for table `archive_subjects`
--
ALTER TABLE `archive_subjects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `author_enrichment`
--
ALTER TABLE `author_enrichment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `authorName` (`authorName`);

--
-- Indexes for table `bad_words`
--
ALTER TABLE `bad_words`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `browse_category`
--
ALTER TABLE `browse_category`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `textId` (`textId`);

--
-- Indexes for table `browse_category_library`
--
ALTER TABLE `browse_category_library`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `libraryId` (`libraryId`,`browseCategoryTextId`);

--
-- Indexes for table `browse_category_location`
--
ALTER TABLE `browse_category_location`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `locationId` (`locationId`,`browseCategoryTextId`);

--
-- Indexes for table `browse_category_subcategories`
--
ALTER TABLE `browse_category_subcategories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subCategoryId` (`subCategoryId`,`browseCategoryId`);

--
-- Indexes for table `claim_authorship_requests`
--
ALTER TABLE `claim_authorship_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pid` (`pid`),
  ADD KEY `name` (`name`);

--
-- Indexes for table `cron_log`
--
ALTER TABLE `cron_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cron_process_log`
--
ALTER TABLE `cron_process_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cronId` (`cronId`),
  ADD KEY `processName` (`processName`);

--
-- Indexes for table `db_update`
--
ALTER TABLE `db_update`
  ADD PRIMARY KEY (`update_key`);

--
-- Indexes for table `editorial_reviews`
--
ALTER TABLE `editorial_reviews`
  ADD PRIMARY KEY (`editorialReviewId`),
  ADD KEY `RecordId` (`recordId`);

--
-- Indexes for table `grouped_work`
--
ALTER TABLE `grouped_work`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permanent_id` (`permanent_id`),
  ADD KEY `date_updated` (`date_updated`),
  ADD KEY `date_updated_2` (`date_updated`);

--
-- Indexes for table `grouped_work_primary_identifiers`
--
ALTER TABLE `grouped_work_primary_identifiers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `type` (`type`,`identifier`),
  ADD KEY `grouped_record_id` (`grouped_work_id`);

--
-- Indexes for table `holiday`
--
ALTER TABLE `holiday`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `LibraryDate` (`date`,`libraryId`),
  ADD KEY `Library` (`libraryId`),
  ADD KEY `Date` (`date`);

--
-- Indexes for table `hoopla_export`
--
ALTER TABLE `hoopla_export`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `hooplaId` (`hooplaId`);

--
-- Indexes for table `hoopla_export_log`
--
ALTER TABLE `hoopla_export_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ils_hold_summary`
--
ALTER TABLE `ils_hold_summary`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ilsId` (`ilsId`);

--
-- Indexes for table `ils_marc_checksums`
--
ALTER TABLE `ils_marc_checksums`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ilsId` (`ilsId`),
  ADD UNIQUE KEY `source` (`source`,`ilsId`);

--
-- Indexes for table `ils_volume_info`
--
ALTER TABLE `ils_volume_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `volumeId` (`volumeId`),
  ADD KEY `recordId` (`recordId`);

--
-- Indexes for table `indexing_profiles`
--
ALTER TABLE `indexing_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `ip_lookup`
--
ALTER TABLE `ip_lookup`
  ADD PRIMARY KEY (`id`),
  ADD KEY `startIpVal` (`startIpVal`),
  ADD KEY `endIpVal` (`endIpVal`),
  ADD KEY `startIpVal_2` (`startIpVal`),
  ADD KEY `endIpVal_2` (`endIpVal`);

--
-- Indexes for table `islandora_object_cache`
--
ALTER TABLE `islandora_object_cache`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pid` (`pid`);

--
-- Indexes for table `islandora_samepika_cache`
--
ALTER TABLE `islandora_samepika_cache`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `groupedWorkId` (`groupedWorkId`);

--
-- Indexes for table `library`
--
ALTER TABLE `library`
  ADD PRIMARY KEY (`libraryId`),
  ADD UNIQUE KEY `subdomain` (`subdomain`);

--
-- Indexes for table `library_archive_explore_more_bar`
--
ALTER TABLE `library_archive_explore_more_bar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `LibraryIdIndex` (`libraryId`);

--
-- Indexes for table `library_archive_more_details`
--
ALTER TABLE `library_archive_more_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `libraryId` (`libraryId`);

--
-- Indexes for table `library_archive_search_facet_setting`
--
ALTER TABLE `library_archive_search_facet_setting`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `libraryFacet` (`libraryId`,`facetName`),
  ADD KEY `libraryId` (`libraryId`);

--
-- Indexes for table `library_combined_results_section`
--
ALTER TABLE `library_combined_results_section`
  ADD PRIMARY KEY (`id`),
  ADD KEY `LibraryIdIndex` (`libraryId`);

--
-- Indexes for table `library_facet_setting`
--
ALTER TABLE `library_facet_setting`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `libraryFacet` (`libraryId`,`facetName`),
  ADD KEY `libraryId` (`libraryId`),
  ADD KEY `libraryId_2` (`libraryId`);

--
-- Indexes for table `library_links`
--
ALTER TABLE `library_links`
  ADD PRIMARY KEY (`id`),
  ADD KEY `libraryId` (`libraryId`);

--
-- Indexes for table `library_more_details`
--
ALTER TABLE `library_more_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `libraryId` (`libraryId`);

--
-- Indexes for table `library_records_owned`
--
ALTER TABLE `library_records_owned`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `library_records_to_include`
--
ALTER TABLE `library_records_to_include`
  ADD PRIMARY KEY (`id`),
  ADD KEY `libraryId` (`libraryId`,`indexingProfileId`);

--
-- Indexes for table `library_search_source`
--
ALTER TABLE `library_search_source`
  ADD PRIMARY KEY (`id`),
  ADD KEY `libraryId` (`libraryId`);

--
-- Indexes for table `library_top_links`
--
ALTER TABLE `library_top_links`
  ADD PRIMARY KEY (`id`),
  ADD KEY `libraryId` (`libraryId`);

--
-- Indexes for table `list_widgets`
--
ALTER TABLE `list_widgets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `list_widget_lists`
--
ALTER TABLE `list_widget_lists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ListWidgetId` (`listWidgetId`);

--
-- Indexes for table `list_widget_lists_links`
--
ALTER TABLE `list_widget_lists_links`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `loan_rules`
--
ALTER TABLE `loan_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loanRuleId` (`loanRuleId`),
  ADD KEY `holdable` (`holdable`);

--
-- Indexes for table `loan_rule_determiners`
--
ALTER TABLE `loan_rule_determiners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rowNumber` (`rowNumber`),
  ADD KEY `active` (`active`);

--
-- Indexes for table `location`
--
ALTER TABLE `location`
  ADD PRIMARY KEY (`locationId`),
  ADD UNIQUE KEY `code` (`code`,`subLocation`),
  ADD KEY `ValidHoldPickupBranch` (`validHoldPickupBranch`);

--
-- Indexes for table `location_combined_results_section`
--
ALTER TABLE `location_combined_results_section`
  ADD PRIMARY KEY (`id`),
  ADD KEY `LocationIdIndex` (`locationId`);

--
-- Indexes for table `location_facet_setting`
--
ALTER TABLE `location_facet_setting`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `locationFacet` (`locationId`,`facetName`),
  ADD KEY `locationId` (`locationId`);

--
-- Indexes for table `location_hours`
--
ALTER TABLE `location_hours`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `locationId` (`locationId`,`day`);

--
-- Indexes for table `location_more_details`
--
ALTER TABLE `location_more_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `locationId` (`locationId`);

--
-- Indexes for table `location_records_owned`
--
ALTER TABLE `location_records_owned`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `location_records_to_include`
--
ALTER TABLE `location_records_to_include`
  ADD PRIMARY KEY (`id`),
  ADD KEY `locationId` (`locationId`,`indexingProfileId`);

--
-- Indexes for table `location_search_source`
--
ALTER TABLE `location_search_source`
  ADD PRIMARY KEY (`id`),
  ADD KEY `locationId` (`locationId`);

--
-- Indexes for table `marriage`
--
ALTER TABLE `marriage`
  ADD PRIMARY KEY (`marriageId`);

--
-- Indexes for table `materials_request`
--
ALTER TABLE `materials_request`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`),
  ADD KEY `status_2` (`status`),
  ADD KEY `createdBy` (`createdBy`),
  ADD KEY `dateUpdated` (`dateUpdated`),
  ADD KEY `dateCreated` (`dateCreated`),
  ADD KEY `emailSent` (`emailSent`),
  ADD KEY `holdsCreated` (`holdsCreated`),
  ADD KEY `format` (`format`),
  ADD KEY `subFormat` (`subFormat`),
  ADD KEY `createdBy_2` (`createdBy`),
  ADD KEY `dateUpdated_2` (`dateUpdated`),
  ADD KEY `dateCreated_2` (`dateCreated`),
  ADD KEY `emailSent_2` (`emailSent`),
  ADD KEY `holdsCreated_2` (`holdsCreated`),
  ADD KEY `format_2` (`format`),
  ADD KEY `subFormat_2` (`subFormat`),
  ADD KEY `status_3` (`status`);

--
-- Indexes for table `materials_request_fields_to_display`
--
ALTER TABLE `materials_request_fields_to_display`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `columnNameToDisplay` (`columnNameToDisplay`,`libraryId`),
  ADD KEY `libraryId` (`libraryId`);

--
-- Indexes for table `materials_request_formats`
--
ALTER TABLE `materials_request_formats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `libraryId` (`libraryId`);

--
-- Indexes for table `materials_request_form_fields`
--
ALTER TABLE `materials_request_form_fields`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_UNIQUE` (`id`),
  ADD KEY `libraryId` (`libraryId`);

--
-- Indexes for table `materials_request_status`
--
ALTER TABLE `materials_request_status`
  ADD PRIMARY KEY (`id`),
  ADD KEY `isDefault` (`isDefault`),
  ADD KEY `isOpen` (`isOpen`),
  ADD KEY `isPatronCancel` (`isPatronCancel`),
  ADD KEY `isDefault_2` (`isDefault`),
  ADD KEY `isOpen_2` (`isOpen`),
  ADD KEY `isPatronCancel_2` (`isPatronCancel`),
  ADD KEY `libraryId` (`libraryId`),
  ADD KEY `isDefault_3` (`isDefault`),
  ADD KEY `isOpen_3` (`isOpen`),
  ADD KEY `isPatronCancel_3` (`isPatronCancel`);

--
-- Indexes for table `merged_grouped_works`
--
ALTER TABLE `merged_grouped_works`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sourceGroupedWorkId` (`sourceGroupedWorkId`,`destinationGroupedWorkId`);

--
-- Indexes for table `merged_records`
--
ALTER TABLE `merged_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `original_record` (`original_record`),
  ADD KEY `new_record` (`new_record`);

--
-- Indexes for table `millennium_cache`
--
ALTER TABLE `millennium_cache`
  ADD PRIMARY KEY (`recordId`,`scope`);

--
-- Indexes for table `nongrouped_records`
--
ALTER TABLE `nongrouped_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `source` (`source`,`recordId`);

--
-- Indexes for table `non_holdable_locations`
--
ALTER TABLE `non_holdable_locations`
  ADD PRIMARY KEY (`locationId`);

--
-- Indexes for table `novelist_data`
--
ALTER TABLE `novelist_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `groupedRecordPermanentId` (`groupedRecordPermanentId`);

--
-- Indexes for table `obituary`
--
ALTER TABLE `obituary`
  ADD PRIMARY KEY (`obituaryId`);

--
-- Indexes for table `offline_circulation`
--
ALTER TABLE `offline_circulation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `timeEntered` (`timeEntered`),
  ADD KEY `patronBarcode` (`patronBarcode`),
  ADD KEY `patronId` (`patronId`),
  ADD KEY `itemBarcode` (`itemBarcode`),
  ADD KEY `login` (`login`),
  ADD KEY `initials` (`initials`),
  ADD KEY `type` (`type`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `offline_hold`
--
ALTER TABLE `offline_hold`
  ADD PRIMARY KEY (`id`),
  ADD KEY `timeEntered` (`timeEntered`),
  ADD KEY `timeProcessed` (`timeProcessed`),
  ADD KEY `patronBarcode` (`patronBarcode`),
  ADD KEY `patronId` (`patronId`),
  ADD KEY `bibId` (`bibId`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `overdrive_account_cache`
--
ALTER TABLE `overdrive_account_cache`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `overdrive_api_products`
--
ALTER TABLE `overdrive_api_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `overdriveId` (`overdriveId`),
  ADD KEY `dateUpdated` (`dateUpdated`),
  ADD KEY `lastMetadataCheck` (`lastMetadataCheck`),
  ADD KEY `lastAvailabilityCheck` (`lastAvailabilityCheck`),
  ADD KEY `deleted` (`deleted`);

--
-- Indexes for table `overdrive_api_product_availability`
--
ALTER TABLE `overdrive_api_product_availability`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `productId_2` (`productId`,`libraryId`),
  ADD KEY `productId` (`productId`),
  ADD KEY `libraryId` (`libraryId`);

--
-- Indexes for table `overdrive_api_product_formats`
--
ALTER TABLE `overdrive_api_product_formats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `productId_2` (`productId`,`textId`),
  ADD KEY `productId` (`productId`),
  ADD KEY `numericId` (`numericId`);

--
-- Indexes for table `overdrive_api_product_identifiers`
--
ALTER TABLE `overdrive_api_product_identifiers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `productId` (`productId`),
  ADD KEY `type` (`type`);

--
-- Indexes for table `overdrive_api_product_metadata`
--
ALTER TABLE `overdrive_api_product_metadata`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `productId` (`productId`);

--
-- Indexes for table `overdrive_extract_log`
--
ALTER TABLE `overdrive_extract_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `person`
--
ALTER TABLE `person`
  ADD PRIMARY KEY (`personId`);

--
-- Indexes for table `ptype`
--
ALTER TABLE `ptype`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pType` (`pType`);

--
-- Indexes for table `ptype_restricted_locations`
--
ALTER TABLE `ptype_restricted_locations`
  ADD PRIMARY KEY (`locationId`);

--
-- Indexes for table `rbdigital_availability`
--
ALTER TABLE `rbdigital_availability`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rbdigitalId` (`rbdigitalId`),
  ADD KEY `lastChange` (`lastChange`);

--
-- Indexes for table `rbdigital_export_log`
--
ALTER TABLE `rbdigital_export_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rbdigital_title`
--
ALTER TABLE `rbdigital_title`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rbdigitalId` (`rbdigitalId`),
  ADD KEY `lastChange` (`lastChange`);

--
-- Indexes for table `record_grouping_log`
--
ALTER TABLE `record_grouping_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reindex_log`
--
ALTER TABLE `reindex_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`roleId`);

--
-- Indexes for table `search`
--
ALTER TABLE `search`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `folder_id` (`folder_id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `search_stats_new`
--
ALTER TABLE `search_stats_new`
  ADD PRIMARY KEY (`id`),
  ADD KEY `numSearches` (`numSearches`),
  ADD KEY `lastSearch` (`lastSearch`);
ALTER TABLE `search_stats_new` ADD FULLTEXT KEY `phrase_text` (`phrase`);

--
-- Indexes for table `session`
--
ALTER TABLE `session`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`),
  ADD KEY `last_used` (`last_used`);

--
-- Indexes for table `sierra_api_export_log`
--
ALTER TABLE `sierra_api_export_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sierra_export_field_mapping`
--
ALTER TABLE `sierra_export_field_mapping`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `syndetics_data`
--
ALTER TABLE `syndetics_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `groupedRecordPermanentId` (`groupedRecordPermanentId`);

--
-- Indexes for table `themes`
--
ALTER TABLE `themes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `themeName` (`themeName`);

--
-- Indexes for table `time_to_reshelve`
--
ALTER TABLE `time_to_reshelve`
  ADD PRIMARY KEY (`id`),
  ADD KEY `indexingProfileId` (`indexingProfileId`);

--
-- Indexes for table `translation_maps`
--
ALTER TABLE `translation_maps`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `profileName` (`indexingProfileId`,`name`);

--
-- Indexes for table `translation_map_values`
--
ALTER TABLE `translation_map_values`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `translationMapId` (`translationMapId`,`value`);

--
-- Indexes for table `usage_tracking`
--
ALTER TABLE `usage_tracking`
  ADD PRIMARY KEY (`usageId`),
  ADD KEY `usageId` (`usageId`),
  ADD KEY `IP_DATE` (`ipId`,`trackingDate`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`source`,`username`);

--
-- Indexes for table `user_link`
--
ALTER TABLE `user_link`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_link` (`primaryAccountId`,`linkedAccountId`);

--
-- Indexes for table `user_link_blocks`
--
ALTER TABLE `user_link_blocks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_list`
--
ALTER TABLE `user_list`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_list_entry`
--
ALTER TABLE `user_list_entry`
  ADD PRIMARY KEY (`id`),
  ADD KEY `groupedWorkPermanentId` (`groupedWorkPermanentId`),
  ADD KEY `listId` (`listId`);

--
-- Indexes for table `user_not_interested`
--
ALTER TABLE `user_not_interested`
  ADD PRIMARY KEY (`id`),
  ADD KEY `userId` (`userId`);

--
-- Indexes for table `user_rating`
--
ALTER TABLE `user_rating`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniqueness` (`userid`,`resourceid`),
  ADD KEY `Resourceid` (`resourceid`),
  ADD KEY `UserId` (`userid`);

--
-- Indexes for table `user_reading_history_work`
--
ALTER TABLE `user_reading_history_work`
  ADD PRIMARY KEY (`id`),
  ADD KEY `userId` (`userId`,`checkOutDate`),
  ADD KEY `userId_2` (`userId`,`checkInDate`),
  ADD KEY `userId_3` (`userId`,`title`),
  ADD KEY `userId_4` (`userId`,`author`),
  ADD KEY `sourceId` (`sourceId`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`userId`,`roleId`);

--
-- Indexes for table `user_staff_settings`
--
ALTER TABLE `user_staff_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `userId_UNIQUE` (`userId`),
  ADD KEY `userId` (`userId`);

--
-- Indexes for table `user_work_review`
--
ALTER TABLE `user_work_review`
  ADD PRIMARY KEY (`id`),
  ADD KEY `groupedRecordPermanentId` (`groupedRecordPermanentId`),
  ADD KEY `userId` (`userId`);

--
-- Indexes for table `variables`
--
ALTER TABLE `variables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name_2` (`name`),
  ADD UNIQUE KEY `name_3` (`name`),
  ADD KEY `name` (`name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account_profiles`
--
ALTER TABLE `account_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `archive_private_collections`
--
ALTER TABLE `archive_private_collections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `archive_requests`
--
ALTER TABLE `archive_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `archive_subjects`
--
ALTER TABLE `archive_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `author_enrichment`
--
ALTER TABLE `author_enrichment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bad_words`
--
ALTER TABLE `bad_words`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique Id for bad_word', AUTO_INCREMENT=451;

--
-- AUTO_INCREMENT for table `browse_category`
--
ALTER TABLE `browse_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `browse_category_library`
--
ALTER TABLE `browse_category_library`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `browse_category_location`
--
ALTER TABLE `browse_category_location`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `browse_category_subcategories`
--
ALTER TABLE `browse_category_subcategories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `claim_authorship_requests`
--
ALTER TABLE `claim_authorship_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cron_log`
--
ALTER TABLE `cron_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of the cron log';

--
-- AUTO_INCREMENT for table `cron_process_log`
--
ALTER TABLE `cron_process_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of cron process';

--
-- AUTO_INCREMENT for table `editorial_reviews`
--
ALTER TABLE `editorial_reviews`
  MODIFY `editorialReviewId` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grouped_work`
--
ALTER TABLE `grouped_work`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grouped_work_primary_identifiers`
--
ALTER TABLE `grouped_work_primary_identifiers`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `holiday`
--
ALTER TABLE `holiday`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of holiday';

--
-- AUTO_INCREMENT for table `hoopla_export`
--
ALTER TABLE `hoopla_export`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hoopla_export_log`
--
ALTER TABLE `hoopla_export_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log';

--
-- AUTO_INCREMENT for table `ils_hold_summary`
--
ALTER TABLE `ils_hold_summary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ils_marc_checksums`
--
ALTER TABLE `ils_marc_checksums`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ils_volume_info`
--
ALTER TABLE `ils_volume_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `indexing_profiles`
--
ALTER TABLE `indexing_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ip_lookup`
--
ALTER TABLE `ip_lookup`
  MODIFY `id` int(25) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `islandora_object_cache`
--
ALTER TABLE `islandora_object_cache`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `islandora_samepika_cache`
--
ALTER TABLE `islandora_samepika_cache`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library`
--
ALTER TABLE `library`
  MODIFY `libraryId` int(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique id to identify the library within the system', AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `library_archive_explore_more_bar`
--
ALTER TABLE `library_archive_explore_more_bar`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_archive_more_details`
--
ALTER TABLE `library_archive_more_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_archive_search_facet_setting`
--
ALTER TABLE `library_archive_search_facet_setting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_combined_results_section`
--
ALTER TABLE `library_combined_results_section`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_facet_setting`
--
ALTER TABLE `library_facet_setting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_links`
--
ALTER TABLE `library_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_more_details`
--
ALTER TABLE `library_more_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_records_owned`
--
ALTER TABLE `library_records_owned`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_records_to_include`
--
ALTER TABLE `library_records_to_include`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_search_source`
--
ALTER TABLE `library_search_source`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_top_links`
--
ALTER TABLE `library_top_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `list_widgets`
--
ALTER TABLE `list_widgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `list_widget_lists`
--
ALTER TABLE `list_widget_lists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `list_widget_lists_links`
--
ALTER TABLE `list_widget_lists_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan_rules`
--
ALTER TABLE `loan_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan_rule_determiners`
--
ALTER TABLE `loan_rule_determiners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `location`
--
ALTER TABLE `location`
  MODIFY `locationId` int(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique Id for the branch or location within vuFind', AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `location_combined_results_section`
--
ALTER TABLE `location_combined_results_section`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `location_facet_setting`
--
ALTER TABLE `location_facet_setting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `location_hours`
--
ALTER TABLE `location_hours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of hours entry';

--
-- AUTO_INCREMENT for table `location_more_details`
--
ALTER TABLE `location_more_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `location_records_owned`
--
ALTER TABLE `location_records_owned`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `location_records_to_include`
--
ALTER TABLE `location_records_to_include`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `location_search_source`
--
ALTER TABLE `location_search_source`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `marriage`
--
ALTER TABLE `marriage`
  MODIFY `marriageId` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `materials_request`
--
ALTER TABLE `materials_request`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `materials_request_fields_to_display`
--
ALTER TABLE `materials_request_fields_to_display`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `materials_request_formats`
--
ALTER TABLE `materials_request_formats`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `materials_request_form_fields`
--
ALTER TABLE `materials_request_form_fields`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `materials_request_status`
--
ALTER TABLE `materials_request_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `merged_grouped_works`
--
ALTER TABLE `merged_grouped_works`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `merged_records`
--
ALTER TABLE `merged_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nongrouped_records`
--
ALTER TABLE `nongrouped_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `non_holdable_locations`
--
ALTER TABLE `non_holdable_locations`
  MODIFY `locationId` int(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique id for the non holdable location';

--
-- AUTO_INCREMENT for table `novelist_data`
--
ALTER TABLE `novelist_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `obituary`
--
ALTER TABLE `obituary`
  MODIFY `obituaryId` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `offline_circulation`
--
ALTER TABLE `offline_circulation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `offline_hold`
--
ALTER TABLE `offline_hold`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `overdrive_account_cache`
--
ALTER TABLE `overdrive_account_cache`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `overdrive_api_products`
--
ALTER TABLE `overdrive_api_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `overdrive_api_product_availability`
--
ALTER TABLE `overdrive_api_product_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `overdrive_api_product_formats`
--
ALTER TABLE `overdrive_api_product_formats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `overdrive_api_product_identifiers`
--
ALTER TABLE `overdrive_api_product_identifiers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `overdrive_api_product_metadata`
--
ALTER TABLE `overdrive_api_product_metadata`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `overdrive_extract_log`
--
ALTER TABLE `overdrive_extract_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `person`
--
ALTER TABLE `person`
  MODIFY `personId` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ptype`
--
ALTER TABLE `ptype`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ptype_restricted_locations`
--
ALTER TABLE `ptype_restricted_locations`
  MODIFY `locationId` int(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique id for the non holdable location';

--
-- AUTO_INCREMENT for table `rbdigital_availability`
--
ALTER TABLE `rbdigital_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rbdigital_export_log`
--
ALTER TABLE `rbdigital_export_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log';

--
-- AUTO_INCREMENT for table `rbdigital_title`
--
ALTER TABLE `rbdigital_title`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `record_grouping_log`
--
ALTER TABLE `record_grouping_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log';

--
-- AUTO_INCREMENT for table `reindex_log`
--
ALTER TABLE `reindex_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of reindex log';

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `roleId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `search`
--
ALTER TABLE `search`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `search_stats_new`
--
ALTER TABLE `search_stats_new`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The unique id of the search statistic';

--
-- AUTO_INCREMENT for table `session`
--
ALTER TABLE `session`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sierra_api_export_log`
--
ALTER TABLE `sierra_api_export_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log';

--
-- AUTO_INCREMENT for table `sierra_export_field_mapping`
--
ALTER TABLE `sierra_export_field_mapping`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of field mapping';

--
-- AUTO_INCREMENT for table `syndetics_data`
--
ALTER TABLE `syndetics_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `themes`
--
ALTER TABLE `themes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `time_to_reshelve`
--
ALTER TABLE `time_to_reshelve`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `translation_maps`
--
ALTER TABLE `translation_maps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `translation_map_values`
--
ALTER TABLE `translation_map_values`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `usage_tracking`
--
ALTER TABLE `usage_tracking`
  MODIFY `usageId` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_link`
--
ALTER TABLE `user_link`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_link_blocks`
--
ALTER TABLE `user_link_blocks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_list`
--
ALTER TABLE `user_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_list_entry`
--
ALTER TABLE `user_list_entry`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_not_interested`
--
ALTER TABLE `user_not_interested`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_rating`
--
ALTER TABLE `user_rating`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_reading_history_work`
--
ALTER TABLE `user_reading_history_work`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_staff_settings`
--
ALTER TABLE `user_staff_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_work_review`
--
ALTER TABLE `user_work_review`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `variables`
--
ALTER TABLE `variables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=142;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
