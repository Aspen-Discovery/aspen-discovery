<?php
/**
 * Updates related to record grouping for cleanliness
 *
 * @category VuFind-Plus-2014
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/29/14
 * Time: 2:25 PM
 */

function getGroupedWorkUpdates(){
	return array(
		'grouped_works' => array(
			'title' => 'Setup Grouped Works',
			'description' => 'Sets up tables for grouped works so we can index and display them.',
			'sql' => array(
				"CREATE TABLE IF NOT EXISTS grouped_work (
					id BIGINT(20) NOT NULL AUTO_INCREMENT,
					permanent_id CHAR(36) NOT NULL,
					title VARCHAR(100) NULL,
					author VARCHAR(50) NULL,
					subtitle VARCHAR(175) NULL,
					grouping_category VARCHAR(25) NOT NULL,
					PRIMARY KEY (id),
					UNIQUE KEY permanent_id (permanent_id),
					KEY title (title,author,grouping_category)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8",
				"CREATE TABLE IF NOT EXISTS grouped_work_identifiers (
					id BIGINT(20) NOT NULL AUTO_INCREMENT,
					grouped_work_id BIGINT(20) NOT NULL,
					`type` VARCHAR(15) NOT NULL,
					identifier VARCHAR(36) NOT NULL,
					linksToDifferentTitles TINYINT(4) NOT NULL DEFAULT '0',
					PRIMARY KEY (id),
					KEY `type` (`type`,identifier)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8",
			),

		),

		'grouped_works_1' => array(
			'title' => 'Grouped Work update 1',
			'description' => 'Updates grouped works to normalize identifiers and add a reference table to link to .',
			'sql' => array(
				"CREATE TABLE IF NOT EXISTS grouped_work_identifiers_ref (
					grouped_work_id BIGINT(20) NOT NULL,
					identifier_id BIGINT(20) NOT NULL,
					PRIMARY KEY (grouped_work_id, identifier_id)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8",
				"TRUNCATE TABLE grouped_work_identifiers",
				"ALTER TABLE `grouped_work_identifiers` CHANGE `type` `type` ENUM( 'asin', 'ils', 'isbn', 'issn', 'oclc', 'upc', 'order', 'external_econtent', 'acs', 'free', 'overdrive' )",
				"ALTER TABLE grouped_work_identifiers DROP COLUMN grouped_work_id",
				"ALTER TABLE grouped_work_identifiers DROP COLUMN linksToDifferentTitles",
				"ALTER TABLE grouped_work_identifiers ADD UNIQUE (`type`, `identifier`)",
			),
		),

		'grouped_works_2' => array(
			'title' => 'Grouped Work update 2',
			'description' => 'Updates grouped works to add a full title field.',
			'sql' => array(
				"ALTER TABLE `grouped_work` ADD `full_title` VARCHAR( 276 ) NOT NULL",
				"ALTER TABLE `grouped_work` ADD INDEX(`full_title`)",
			),
		),

		'grouped_works_remove_split_titles' => array(
			'title' => 'Grouped Work Remove Split Titles',
			'description' => 'Updates grouped works to add a full title field.',
			'sql' => array(
				"ALTER TABLE `grouped_work` DROP COLUMN `title`",
				"ALTER TABLE `grouped_work` DROP COLUMN `subtitle`",
			),
		),

		'grouped_works_primary_identifiers' => array(
			'title' => 'Grouped Work Primary Identifiers',
			'description' => 'Add primary identifiers table for works.',
			'sql' => array(
				"CREATE TABLE IF NOT EXISTS grouped_work_primary_identifiers (
					id BIGINT(20) NOT NULL AUTO_INCREMENT,
					grouped_work_id BIGINT(20) NOT NULL,
					`type` ENUM('ils', 'external_econtent', 'acs', 'free', 'overdrive' ) NOT NULL,
					identifier VARCHAR(36) NOT NULL,
					PRIMARY KEY (id),
					UNIQUE KEY (`type`,identifier),
					KEY grouped_record_id (grouped_work_id)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8",
			),
		),

		'grouped_works_primary_identifiers_1' => array(
			'title' => 'Grouped Work Primary Identifiers Update 1',
			'description' => 'Add additional types of identifiers.',
			'sql' => array(
				"ALTER TABLE grouped_work_primary_identifiers CHANGE `type` `type` ENUM('ils', 'external', 'drm', 'free', 'overdrive' ) NOT NULL",
			),
		),

		'grouped_work_identifiers_ref_indexing' => array(
			'title' => 'Grouped Work Identifiers Ref Indexing',
			'description' => 'Add indexing to identifiers re.',
			'sql' => array(
				"ALTER TABLE grouped_work_identifiers_ref ADD INDEX(identifier_id)",
				"ALTER TABLE grouped_work_identifiers_ref ADD INDEX(grouped_work_id)",
			),
		),

		'grouped_works_partial_updates' => array(
			'title' => 'Grouped Work Partial Updates',
			'description' => 'Updates to allow only changed records to be regrouped.',
			'sql' => array(
				"ALTER TABLE grouped_work ADD date_updated INT(11)",
				"CREATE TABLE grouped_work_primary_to_secondary_id_ref (
					primary_identifier_id BIGINT(20),
					secondary_identifier_id BIGINT(20),
					UNIQUE KEY (primary_identifier_id, secondary_identifier_id),
					KEY (primary_identifier_id),
					KEY (secondary_identifier_id)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8",
				"ALTER TABLE grouped_work_identifiers ADD valid_for_enrichment TINYINT(1) DEFAULT 1"
			),
		),

		'grouped_work_engine' => array(
			'title' => 'Grouped Work Engine',
			'description' => 'Change storage engine to INNODB for grouped work tables',
			'sql' => array(
				'ALTER TABLE `grouped_work` ENGINE = InnoDB',
				'ALTER TABLE `grouped_work_identifiers` ENGINE = InnoDB',
				'ALTER TABLE `grouped_work_identifiers_ref` ENGINE = InnoDB',
				'ALTER TABLE `grouped_work_primary_identifiers` ENGINE = InnoDB',
				'ALTER TABLE `grouped_work_primary_to_secondary_id_ref` ENGINE = InnoDB',
				'ALTER TABLE `ils_marc_checksums` ENGINE = InnoDB',
			)
		),

		'grouped_work_merging' => array(
			'title' => 'Grouped Work Merging',
			'description' => 'Add a new table to allow manual merging of grouped works',
			'sql' => array(
				"CREATE TABLE IF NOT EXISTS merged_grouped_works(
					id BIGINT(20) NOT NULL AUTO_INCREMENT,
					sourceGroupedWorkId CHAR(36) NOT NULL,
					destinationGroupedWorkId CHAR(36) NOT NULL,
					notes VARCHAR(250) NOT NULL DEFAULT '',
					PRIMARY KEY (id),
					UNIQUE KEY (sourceGroupedWorkId,destinationGroupedWorkId)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8",
			)
		),

		'grouped_work_index_date_updated' => array(
			'title' => 'Grouped Work Index Date Update',
			'description' => 'Index date updated to improve performance',
			'sql' => array(
				"ALTER TABLE `grouped_work` ADD INDEX(`date_updated`)",
			)
		),

		'grouped_work_evoke' => array(
			'title' => 'Grouped Work eVoke',
			'description' => 'Allow eVoke as a valid identifier type ',
			'sql' => array(
				"ALTER TABLE grouped_work_primary_identifiers CHANGE `type` `type` ENUM('ils', 'external', 'drm', 'free', 'overdrive', 'evoke' ) NOT NULL",
			)
		),

		'grouped_work_primary_identifiers_hoopla' => array(
			'title' => 'Grouped Work Updates to support Hoopla',
			'description' => 'Allow hoopla as a valid identifier type',
			'sql' => array(
				"ALTER TABLE grouped_work_primary_identifiers CHANGE `type` `type` ENUM('ils', 'external', 'drm', 'free', 'overdrive', 'evoke', 'hoopla' ) NOT NULL",
			),
		),

		'grouped_work_index_cleanup' => array(
			'title' => 'Cleanup Grouped Work Indexes',
			'description' => 'Cleanup Indexes for better performance',
			'continueOnError' => true,
			'sql' => array(
				"DROP INDEX title on grouped_work",
				"DROP INDEX full_title on grouped_work",
				"DROP INDEX grouped_work_id on grouped_work_identifiers",
				"DROP INDEX type_2 on grouped_work_identifiers",
				"DROP INDEX type_3 on grouped_work_identifiers",
				"DROP INDEX identifier_id_2 on grouped_work_identifiers_ref",
				"DROP INDEX grouped_work_id on grouped_work_identifiers_ref",
				"DROP INDEX grouped_work_id_2 on grouped_work_identifiers_ref",
				"DROP INDEX primary_identifier_id_2 on grouped_work_primary_to_secondary_id_ref",
			),
		),

		'grouped_work_duplicate_identifiers' => array(
			'title' => 'Cleanup Grouped Duplicate Identifiers within ',
			'description' => 'Cleanup Duplicate Identifiers that were added mistakenly',
			'continueOnError' => true,
			'sql' => array(
				"TRUNCATE table grouped_work_identifiers",
				"TRUNCATE table grouped_work_identifiers_ref",
				"TRUNCATE table grouped_work_primary_to_secondary_id_ref",
				"ALTER TABLE grouped_work_identifiers DROP INDEX type",
				"ALTER TABLE grouped_work_identifiers ADD UNIQUE (`type`, `identifier`)",
			),
		),

		'grouped_work_primary_identifier_types' => array(
			'title' => 'Expand Primary Identifiers Types ',
			'description' => 'Expand Primary Identifiers so they can be any type to make it easier to index different collections.',
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE grouped_work_primary_identifiers CHANGE `type` `type` VARCHAR(50) NOT NULL",
			),
		),

		'increase_ilsID_size_for_ils_marc_checksums' => array(
			'title' => 'Expand ilsId Size',
			'description' => 'Increase the column size of the ilsId in the ils_marc_checksums table to accomodate larger Sideload Ids.',
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE `ils_marc_checksums` CHANGE COLUMN `ilsId` `ilsId` VARCHAR(50) NOT NULL ;",
			),
		),
	);
}
