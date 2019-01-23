<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

/**
 * Provides a method of running SQL updates to the database.
 * Shows a list of updates that are available with a description of the
 *
 * @author Mark Noble
 *
 */
class DBMaintenance extends Admin_Admin {
	function launch() {
		global $configArray;
		global $interface;
		mysql_select_db($configArray['Database']['database_vufind_dbname']);

		//Create updates table if one doesn't exist already
		$this->createUpdatesTable();

		$availableUpdates = $this->getSQLUpdates();

		if (isset($_REQUEST['submit'])) {
			$interface->assign('showStatus', true);

			//Process the updates
			foreach ($availableUpdates as $key => $update) {
				if (isset($_REQUEST["selected"][$key])) {
					$sqlStatements = $update['sql'];
					$updateOk = true;
					foreach ($sqlStatements as $sql) {
						//Give enough time for long queries to run

						if (method_exists($this, $sql)) {
							$this->$sql($update);
						} else {
							if (!$this->runSQLStatement($update, $sql)) {
								break;
							}
						}
					}
					if ($updateOk) {
						$this->markUpdateAsRun($key);
					}
					$availableUpdates[$key] = $update;
				}
			}
		}

		//Check to see which updates have already been performed.
		$availableUpdates = $this->checkWhichUpdatesHaveRun($availableUpdates);

		$interface->assign('sqlUpdates', $availableUpdates);

		$this->display('dbMaintenance.tpl', 'Database Maintenance');

	}

	private function getSQLUpdates() {
		global $configArray;

		require_once ROOT_DIR . '/sys/DBMaintenance/library_location_updates.php';
		$library_location_updates = getLibraryLocationUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/grouped_work_updates.php';
		$grouped_work_updates = getGroupedWorkUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/user_updates.php';
		$user_updates = getUserUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/list_widget_updates.php';
		$list_widget_updates = getListWidgetUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/indexing_updates.php';
		$indexing_updates = getIndexingUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/islandora_updates.php';
		$islandora_updates = getIslandoraUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/hoopla_updates.php';
		$hoopla_updates = getHooplaUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/sierra_api_updates.php';
		$sierra_api_updates = getSierraAPIUpdates();

		return array_merge(
			$library_location_updates,
			$user_updates,
			$grouped_work_updates,
			$list_widget_updates,
			$indexing_updates,
			$islandora_updates,
			$hoopla_updates,
			$sierra_api_updates,
			array(
				'index_search_stats' => array(
					'title' => 'Index search stats table',
					'description' => 'Add index to search stats table to improve autocomplete speed',
					'sql' => array(
						"ALTER TABLE `search_stats` ADD INDEX `search_index` ( `type` , `libraryId` , `locationId` , `phrase`, `numResults` )",
					),
				),

				'index_search_stats_counts' => array(
					'title' => 'Index search stats table counts',
					'description' => 'Add index to search stats table to improve autocomplete speed',
					'sql' => array(
						"ALTER TABLE `search_stats` ADD INDEX `numResults` (`numResults` )",
						"ALTER TABLE `search_stats` ADD INDEX `numSearches` (`numSearches` )",
					),
				),

				'new_search_stats' => array(
					'title' => 'Create new search stats table with better performance',
					'description' => 'Create an optimized table for performing auto completes based on prior searches',
					'sql' => array(
						"CREATE TABLE `search_stats_new` (
						  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The unique id of the search statistic',
						  `phrase` varchar(500) NOT NULL COMMENT 'The phrase being searched for',
						  `lastSearch` int(16) NOT NULL COMMENT 'The last time this search was done',
						  `numSearches` int(16) NOT NULL COMMENT 'The number of times this search has been done.',
						  PRIMARY KEY (`id`),
						  KEY `numSearches` (`numSearches`),
						  KEY `lastSearch` (`lastSearch`),
						  KEY `phrase` (`phrase`),
						  FULLTEXT `phrase_text` (`phrase`)
						) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COMMENT='Statistical information about searches for use in reporting '",
						"INSERT INTO search_stats_new (phrase, lastSearch, numSearches) SELECT TRIM(REPLACE(phrase, char(9), '')) as phrase, MAX(lastSearch), sum(numSearches) FROM search_stats WHERE numResults > 0 GROUP BY TRIM(REPLACE(phrase,char(9), ''))",
						"DELETE FROM search_stats_new WHERE phrase LIKE '%(%'",
						"DELETE FROM search_stats_new WHERE phrase LIKE '%)%'",
					),
				),


				'genealogy' => array(
					'title' => 'Genealogy Setup',
					'description' => 'Initial setup of genealogy information',
					'continueOnError' => true,
					'sql' => array(
						//-- setup tables related to the genealogy section
						//-- person table
						"CREATE TABLE `person` (
						`personId` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
						`firstName` VARCHAR( 100 ) NULL ,
						`middleName` VARCHAR( 100 ) NULL ,
						`lastName` VARCHAR( 100 ) NULL ,
						`maidenName` VARCHAR( 100 ) NULL ,
						`otherName` VARCHAR( 100 ) NULL ,
						`nickName` VARCHAR( 100 ) NULL ,
						`birthDate` DATE NULL ,
						`birthDateDay` INT NULL COMMENT 'The day of the month the person was born empty or null if not known',
						`birthDateMonth` INT NULL COMMENT 'The month the person was born, null or blank if not known',
						`birthDateYear` INT NULL COMMENT 'The year the person was born, null or blank if not known',
						`deathDate` DATE NULL ,
						`deathDateDay` INT NULL COMMENT 'The day of the month the person died empty or null if not known',
						`deathDateMonth` INT NULL COMMENT 'The month the person died, null or blank if not known',
						`deathDateYear` INT NULL COMMENT 'The year the person died, null or blank if not known',
						`ageAtDeath` TEXT NULL ,
						`cemeteryName` VARCHAR( 255 ) NULL ,
						`cemeteryLocation` VARCHAR( 255 ) NULL ,
						`mortuaryName` VARCHAR( 255 ) NULL ,
						`comments` MEDIUMTEXT NULL,
						`picture` VARCHAR( 255 ) NULL
						) ENGINE = MYISAM COMMENT = 'Stores information about a particular person for use in genealogy';",

						//-- marriage table
						"CREATE TABLE `marriage` (
						`marriageId` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
						`personId` INT NOT NULL COMMENT 'A link to one person in the marriage',
						`spouseName` VARCHAR( 200 ) NULL COMMENT 'The name of the other person in the marriage if they aren''t in the database',
						`spouseId` INT NULL COMMENT 'A link to the second person in the marriage if the person is in the database',
						`marriageDate` DATE NULL COMMENT 'The date of the marriage if known.',
						`marriageDateDay` INT NULL COMMENT 'The day of the month the marriage occurred empty or null if not known',
						`marriageDateMonth` INT NULL COMMENT 'The month the marriage occurred, null or blank if not known',
						`marriageDateYear` INT NULL COMMENT 'The year the marriage occurred, null or blank if not known',
						`comments` MEDIUMTEXT NULL
						) ENGINE = MYISAM COMMENT = 'Information about a marriage between two people';",


						//-- obituary table
						"CREATE TABLE `obituary` (
						`obituaryId` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
						`personId` INT NOT NULL COMMENT 'The person this obituary is for',
						`source` VARCHAR( 255 ) NULL ,
						`date` DATE NULL ,
						`dateDay` INT NULL COMMENT 'The day of the month the obituary came out empty or null if not known',
						`dateMonth` INT NULL COMMENT 'The month the obituary came out, null or blank if not known',
						`dateYear` INT NULL COMMENT 'The year the obituary came out, null or blank if not known',
						`sourcePage` VARCHAR( 25 ) NULL ,
						`contents` MEDIUMTEXT NULL ,
						`picture` VARCHAR( 255 ) NULL
						) ENGINE = MYISAM	COMMENT = 'Information about an obituary for a person';",
					),
				),

				'genealogy_1' => array(
					'title' => 'Genealogy Update 1',
					'description' => 'Update Genealogy 1 for Steamboat Springs to add cemetery information.',
					'sql' => array(
						"ALTER TABLE person ADD COLUMN veteranOf VARCHAR(100) NULL DEFAULT ''",
						"ALTER TABLE person ADD COLUMN addition VARCHAR(100) NULL DEFAULT ''",
						"ALTER TABLE person ADD COLUMN block VARCHAR(100) NULL DEFAULT ''",
						"ALTER TABLE person ADD COLUMN lot INT(11) NULL",
						"ALTER TABLE person ADD COLUMN grave INT(11) NULL",
						"ALTER TABLE person ADD COLUMN tombstoneInscription TEXT",
						"ALTER TABLE person ADD COLUMN addedBy INT(11) NOT NULL DEFAULT -1",
						"ALTER TABLE person ADD COLUMN dateAdded INT(11) NULL",
						"ALTER TABLE person ADD COLUMN modifiedBy INT(11) NOT NULL DEFAULT -1",
						"ALTER TABLE person ADD COLUMN lastModified INT(11) NULL",
						"ALTER TABLE person ADD COLUMN privateComments TEXT",
						"ALTER TABLE person ADD COLUMN importedFrom VARCHAR(50) NULL",
					),
				),

				'genealogy_nashville_1' => array(
					'title' => 'Genealogy Update : Nashville 1',
					'description' => 'Update Genealogy : for Nashville to add Nashville City Cemetery information.',
					'continueOnError' => true,
					'sql' => array(
						"ALTER TABLE person ADD COLUMN ledgerVolume VARCHAR(20) NULL DEFAULT ''",
						"ALTER TABLE person ADD COLUMN ledgerYear VARCHAR(20) NULL DEFAULT ''",
						"ALTER TABLE person ADD COLUMN ledgerEntry VARCHAR(20) NULL DEFAULT ''",
						"ALTER TABLE person ADD COLUMN sex VARCHAR(20) NULL DEFAULT ''",
						"ALTER TABLE person ADD COLUMN race VARCHAR(20) NULL DEFAULT ''",
						"ALTER TABLE person ADD COLUMN residence VARCHAR(255) NULL DEFAULT ''",
						"ALTER TABLE person ADD COLUMN causeOfDeath VARCHAR(255) NULL DEFAULT ''",
						"ALTER TABLE person ADD COLUMN cemeteryAvenue VARCHAR(255) NULL DEFAULT ''",
						"ALTER TABLE person CHANGE lot lot VARCHAR(20) NULL DEFAULT ''",
					),
				),

				'recommendations_optOut' => array(
					'title' => 'Recommendations Opt Out',
					'description' => 'Add tracking for whether the user wants to opt out of recommendations',
					'sql' => array(
						"ALTER TABLE `user` ADD `disableRecommendations` TINYINT NOT NULL DEFAULT '0'",
					),
				),

				'editorial_review' => array(
					'title' => 'Create Editorial Review table',
					'description' => 'Create editorial review tables for external reviews, i.e. book-a-day blog',
					'sql' => array(
						"CREATE TABLE editorial_reviews (" .
						"editorialReviewId int NOT NULL AUTO_INCREMENT PRIMARY KEY, " .
						"recordId VARCHAR(50) NOT NULL, " .
						"title VARCHAR(255) NOT NULL, " .
						"pubDate BIGINT NOT NULL, " .
						"review TEXT, " .
						"source VARCHAR(50) NOT NULL" .
						")",
					),
				),

				'editorial_review_1' => array(
					'title' => 'Add tabName to editorial reviews',
					'description' => 'Update editorial reviews to include a tab name',
					'sql' => array(
						"ALTER TABLE editorial_reviews ADD tabName VARCHAR(25) DEFAULT 'Reviews';",
					),
				),

				'editorial_review_2' => array(
					'title' => 'Add teaser to editorial reviews',
					'description' => 'Update editorial reviews to include a teaser',
					'sql' => array(
						"ALTER TABLE editorial_reviews ADD teaser VARCHAR(512);",
					),
				),

				'purchase_link_tracking' => array(
					'title' => 'Create Purchase Link Tracking Table',
					'description' => 'Create Purchase Links tables to track links that were clicked',
					'sql' => array(
						'CREATE TABLE IF NOT EXISTS purchase_link_tracking (' .
						'purchaseLinkId int(11) NOT NULL AUTO_INCREMENT, ' .
						'ipAddress varchar(30) NULL, ' .
						'recordId VARCHAR(50) NOT NULL, ' .
						'store VARCHAR(255) NOT NULL, ' .
						'trackingDate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, ' .
						'PRIMARY KEY (purchaseLinkId) ' .
						') ENGINE=InnoDB',

						'ALTER TABLE purchase_link_tracking ADD INDEX ( `purchaseLinkId` )',
					),
				),

				'resource_update_table' => array(
					'title' => 'Update resource table',
					'description' => 'Update resource tracking table to include additional information resources for sorting',
					'sql' => array(
						'ALTER TABLE resource ADD author VARCHAR(255)',
						'ALTER TABLE resource ADD title_sort VARCHAR(255)',
						'ALTER TABLE resource ADD isbn VARCHAR(13)',
						'ALTER TABLE resource ADD upc VARCHAR(13)', //Have to use 13 since some publishers use the ISBN as the UPC.
						'ALTER TABLE resource ADD format VARCHAR(50)',
						'ALTER TABLE resource ADD format_category VARCHAR(50)',
						'ALTER TABLE `resource` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci',
					),
				),

				'resource_update_table_2' => array(
					'title' => 'Update resource table 2',
					'description' => 'Update resource tracking table to make sure that title and author are utf8 encoded',
					'sql' => array(
						"ALTER TABLE `resource` CHANGE `title` `title` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''",
						"ALTER TABLE `resource` CHANGE `source` `source` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'VuFind'",
						"ALTER TABLE `resource` CHANGE `author` `author` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci",
						"ALTER TABLE `resource` CHANGE `title_sort` `title_sort` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci",
					),
				),

				'resource_update3' => array(
					'title' => 'Update resource table 3',
					'description' => 'Update resource table to include the checksum of the marc record so we can skip updating records that haven\'t changed',
					'sql' => array(
						"ALTER TABLE `resource` ADD marc_checksum BIGINT",
						"ALTER TABLE `resource` ADD date_updated INT(11)",
					),
				),

				'resource_update4' => array(
					'title' => 'Update resource table 4',
					'description' => 'Update resource table to include a field for the actual marc record',
					'continueOnError' => true,
					'sql' => array(
						"ALTER TABLE `resource` ADD marc BLOB",
					),
				),

				'resource_update5' => array(
					'title' => 'Update resource table 5',
					'description' => 'Add a short id column for use with certain ILS i.e. Millennium',
					'sql' => array(
						"ALTER TABLE `resource` ADD shortId VARCHAR(20)",
						"ALTER TABLE `resource` ADD INDEX (shortId)",
					),
				),

				'resource_update6' => array(
					'title' => 'Update resource table 6',
					'description' => 'Add a deleted column to determine if a resource has been removed from the catalog',
					'continueOnError' => true,
					'sql' => array(
						"ALTER TABLE `resource` ADD deleted TINYINT DEFAULT '0'",
						"ALTER TABLE `resource` ADD INDEX (deleted)",
					),
				),

				'resource_update7' => array(
					'title' => 'Update resource table 7',
					'description' => 'Increase the size of the marc field to avoid indexing errors updating the resources table. ',
					'sql' => array(
						"ALTER TABLE `resource` CHANGE marc marc LONGBLOB",
					),
				),

				'resource_update8' => array(
					'title' => 'Update resource table 8',
					'description' => 'Updates resources to store marc records in text for easier debugging and UTF compatibility. ',
					'sql' => array(
						//"UPDATE resource set marc = null, marc_checksum = -1;",
						"ALTER TABLE `resource` CHANGE `marc` `marc` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;"
					),
				),

				'resource_subject' => array(
					'title' => 'Resource subject',
					'description' => 'Build table to store subjects for resources',
					'sql' => array(
						'CREATE TABLE IF NOT EXISTS subject (' .
						'id int(11) NOT NULL AUTO_INCREMENT, ' .
						'subject VARCHAR(100) NOT NULL, ' .
						'PRIMARY KEY (id), ' .
						'INDEX (`subject`)' .
						') ENGINE=InnoDB',

						'CREATE TABLE IF NOT EXISTS resource_subject (' .
						'id int(11) NOT NULL AUTO_INCREMENT, ' .
						'resourceId INT(11) NOT NULL, ' .
						'subjectId INT(11) NOT NULL, ' .
						'PRIMARY KEY (id), ' .
						'INDEX (`resourceId`), ' .
						'INDEX (`subjectId`)' .
						') ENGINE=InnoDB',
					),
				),

				'readingHistory' => array(
					'title' => 'Reading History Creation',
					'description' => 'Update reading History to include an id table',
					'sql' => array(
						"CREATE TABLE IF NOT EXISTS	user_reading_history(" .
						"`userId` INT NOT NULL COMMENT 'The id of the user who checked out the item', " .
						"`resourceId` INT NOT NULL COMMENT 'The record id of the item that was checked out', " .
						"`lastCheckoutDate` DATE NOT NULL COMMENT 'The first day we detected that the item was checked out to the patron', " .
						"`firstCheckoutDate` DATE NOT NULL COMMENT 'The last day we detected the item was checked out to the patron', " .
						"`daysCheckedOut` INT NOT NULL COMMENT 'The total number of days the item was checked out even if it was checked out multiple times.', " .
						"PRIMARY KEY ( `userId` , `resourceId` )" .
						") ENGINE = MYISAM COMMENT = 'The reading history for patrons';",
					),
				),

				'readingHistory_work' => array(
					'title' => 'Reading History For Grouped Works',
					'description' => 'Update reading History to remove resources and work with works',
					'sql' => array(
						"CREATE TABLE IF NOT EXISTS	user_reading_history_work(
						id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
						userId INT NOT NULL COMMENT 'The id of the user who checked out the item',
						groupedWorkPermanentId CHAR(36) NOT NULL,
						source VARCHAR(25) NOT NULL COMMENT 'The source of the record being checked out',
						sourceId VARCHAR(50) NOT NULL COMMENT 'The id of the item that item that was checked out within the source',
						title VARCHAR(150) NULL COMMENT 'The title of the item in case this is ever deleted',
						author VARCHAR(75) NULL COMMENT 'The author of the item in case this is ever deleted',
						format VARCHAR(50) NULL COMMENT 'The format of the item in case this is ever deleted',
						checkOutDate INT NOT NULL COMMENT 'The first day we detected that the item was checked out to the patron',
						checkInDate INT NULL COMMENT 'The last day we detected that the item was checked out to the patron.',
						INDEX ( userId, checkOutDate ),
						INDEX ( userId, checkInDate ),
						INDEX ( userId, title ),
						INDEX ( userId, author )
						) ENGINE = INNODB DEFAULT CHARSET=utf8 COMMENT = 'The reading history for patrons';",
						"DROP TABLE user_reading_history"
					),
				),

				'readingHistory_deletion' => array(
					'title' => 'Update Reading History Deletion so we mark it as deleted rather than permanently deleting',
					'description' => 'Update Reading History to handle deletions',
					'sql' => array(
						"ALTER TABLE user_reading_history_work ADD `deleted` TINYINT NOT NULL DEFAULT '0'"
					),
				),

				'coverArt_suppress' => array(
					'title' => 'Cover Art Suppress',
					'description' => 'Add tracking for whether the user wants to suppress cover art',
					'sql' => array(
						"ALTER TABLE `user` ADD `disableCoverArt` TINYINT NOT NULL DEFAULT '0'",
					),
				),

				'externalLinkTracking' => array(
					'title' => 'Create External Link Tracking Table',
					'description' => 'Build table to track links to external sites from 856 tags or eContent',
					'sql' => array(
						'CREATE TABLE IF NOT EXISTS external_link_tracking (' .
						'externalLinkId int(11) NOT NULL AUTO_INCREMENT, ' .
						'ipAddress varchar(30) NULL, ' .
						'recordId varchar(50) NOT NULL, ' .
						'linkUrl varchar(400) NOT NULL, ' .
						'linkHost varchar(200) NOT NULL, ' .
						'trackingDate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, ' .
						'PRIMARY KEY (externalLinkId) ' .
						') ENGINE=InnoDB',
					),
				),

				'readingHistoryUpdate1' => array(
					'title' => 'Reading History Update 1',
					'description' => 'Update reading History to include an id table',
					'sql' => array(
						'ALTER TABLE `user_reading_history` DROP PRIMARY KEY',
						'ALTER TABLE `user_reading_history` ADD UNIQUE `user_resource` ( `userId` , `resourceId` ) ',
						'ALTER TABLE `user_reading_history` ADD `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST ',
					),
				),


				'notInterested' => array(
					'title' => 'Not Interested Table',
					'description' => 'Create a table for records the user is not interested in so they can be ommitted from search results',
					'sql' => array(
						"CREATE TABLE `user_not_interested` (
							id INT(11) NOT NULL AUTO_INCREMENT,
							userId INT(11) NOT NULL,
							resourceId VARCHAR(20) NOT NULL,
							dateMarked INT(11),
							PRIMARY KEY (id),
							UNIQUE INDEX (userId, resourceId),
							INDEX (userId)
						)",
					),
				),

				'notInterestedWorks' => array(
					'title' => 'Not Interested Table Works Update',
					'description' => 'Update Not Interested Table to Link to Works',
					'continueOnError' => true,
					'sql' => array(
						"TRUNCATE TABLE `user_not_interested`",
						"ALTER TABLE `user_not_interested` ADD COLUMN groupedRecordPermanentId VARCHAR(36)",
						"ALTER TABLE `user_not_interested` DROP resourceId",
					),
				),

				'notInterestedWorksRemoveUserIndex' => array(
					'title' => 'Not Interested Table Works Update to remove old indexes',
					'description' => 'Update Not Interested Table to Remove old indexes that limited users to one title as not interested',
					'continueOnError' => true,
					'sql' => array(
						"ALTER TABLE user_not_interested DROP INDEX userId",
						"ALTER TABLE user_not_interested DROP INDEX userId_2",
						"ALTER TABLE user_not_interested ADD INDEX(`userId`)",
					),
				),

				'userRatings1' => array(
					'title' => 'User Ratings Update 1',
					'description' => 'Add date rated for user ratings',
					'sql' => array(
						"ALTER TABLE user_rating ADD COLUMN dateRated INT(11)",
					),
				),

				'materialsRequest' => array(
					'title' => 'Materials Request Table Creation',
					'description' => 'Update reading History to include an id table',
					'sql' => array(
						'CREATE TABLE IF NOT EXISTS materials_request (' .
						'id int(11) NOT NULL AUTO_INCREMENT, ' .
						'title varchar(255), ' .
						'author varchar(255), ' .
						'format varchar(25), ' .
						'ageLevel varchar(25), ' .
						'isbn varchar(15), ' .
						'oclcNumber varchar(30), ' .
						'publisher varchar(255), ' .
						'publicationYear varchar(4), ' .
						'articleInfo varchar(255), ' .
						'abridged TINYINT, ' .
						'about TEXT, ' .
						'comments TEXT, ' .
						"status enum('pending', 'owned', 'purchased', 'referredToILL', 'ILLplaced', 'ILLreturned', 'notEnoughInfo', 'notAcquiredOutOfPrint', 'notAcquiredNotAvailable', 'notAcquiredFormatNotAvailable', 'notAcquiredPrice', 'notAcquiredPublicationDate', 'requestCancelled') DEFAULT 'pending', " .
						'dateCreated int(11), ' .
						'createdBy int(11), ' .
						'dateUpdated int(11), ' .
						'PRIMARY KEY (id) ' .
						') ENGINE=InnoDB',
					),
				),

				'materialsRequest_update1' => array(
					'title' => 'Materials Request Update 1',
					'description' => 'Material Request add fields for sending emails and creating holds',
					'sql' => array(
						'ALTER TABLE `materials_request` ADD `emailSent` TINYINT NOT NULL DEFAULT 0',
						'ALTER TABLE `materials_request` ADD `holdsCreated` TINYINT NOT NULL DEFAULT 0',
					),
				),

				'materialsRequest_update2' => array(
					'title' => 'Materials Request Update 2',
					'description' => 'Material Request add fields phone and email so user can supply a different email address',
					'sql' => array(
						'ALTER TABLE `materials_request` ADD `email` VARCHAR(80)',
						'ALTER TABLE `materials_request` ADD `phone` VARCHAR(15)',
					),
				),

				'materialsRequest_update3' => array(
					'title' => 'Materials Request Update 3',
					'description' => 'Material Request add fields season, magazineTitle, split isbn and upc',
					'sql' => array(
						'ALTER TABLE `materials_request` ADD `season` VARCHAR(80)',
						'ALTER TABLE `materials_request` ADD `magazineTitle` VARCHAR(255)',
						//'ALTER TABLE `materials_request` CHANGE `isbn_upc` `isbn` VARCHAR( 15 )',
						'ALTER TABLE `materials_request` ADD `upc` VARCHAR(15)',
						'ALTER TABLE `materials_request` ADD `issn` VARCHAR(8)',
						'ALTER TABLE `materials_request` ADD `bookType` VARCHAR(20)',
						'ALTER TABLE `materials_request` ADD `subFormat` VARCHAR(20)',
						'ALTER TABLE `materials_request` ADD `magazineDate` VARCHAR(20)',
						'ALTER TABLE `materials_request` ADD `magazineVolume` VARCHAR(20)',
						'ALTER TABLE `materials_request` ADD `magazinePageNumbers` VARCHAR(20)',
						'ALTER TABLE `materials_request` ADD `placeHoldWhenAvailable` TINYINT',
						'ALTER TABLE `materials_request` ADD `holdPickupLocation` VARCHAR(10)',
						'ALTER TABLE `materials_request` ADD `bookmobileStop` VARCHAR(50)',
					),
				),

				'materialsRequest_update4' => array(
					'title' => 'Materials Request Update 4',
					'description' => 'Material Request add illItem field and make status field not an enum so libraries can easily add statuses',
					'sql' => array(
						'ALTER TABLE `materials_request` ADD `illItem` VARCHAR(80)',
					),
				),

				'materialsRequest_update5' => array(
					'title' => 'Materials Request Update 5',
					'description' => 'Material Request add magazine number',
					'sql' => array(
						'ALTER TABLE `materials_request` ADD `magazineNumber` VARCHAR(80)',
					),
				),

				'materialsRequest_update6' => array(
					'title' => 'Materials Request Update 6',
					'description' => 'Updater Materials Requests to add indexes for improved performance',
					'sql' => array(
						'ALTER TABLE `materials_request` ADD INDEX(createdBy)',
						'ALTER TABLE `materials_request` ADD INDEX(dateUpdated)',
						'ALTER TABLE `materials_request` ADD INDEX(dateCreated)',
						'ALTER TABLE `materials_request` ADD INDEX(emailSent)',
						'ALTER TABLE `materials_request` ADD INDEX(holdsCreated)',
						'ALTER TABLE `materials_request` ADD INDEX(format)',
						'ALTER TABLE `materials_request` ADD INDEX(subFormat)',
					),
				),

				'materialsRequest_update7' => array(
					'title' => 'Add Assignee Column to Materials Request Table',
					'description' => 'Column for the id number of the staff member the request is assigned to.',
					'sql' => array(
						'ALTER TABLE `materials_request` ADD COLUMN `assignedTo` INT NULL',
					),
				),

				'materialsRequestStatus' => array(
					'title' => 'Materials Request Status Table Creation',
					'description' => 'Update reading History to include an id table',
					'sql' => array(
						'CREATE TABLE IF NOT EXISTS materials_request_status (' .
						'id int(11) NOT NULL AUTO_INCREMENT, ' .
						'description varchar(80), ' .
						'isDefault TINYINT DEFAULT 0, ' .
						'sendEmailToPatron TINYINT, ' .
						'emailTemplate TEXT, ' .
						'isOpen TINYINT, ' .
						'isPatronCancel TINYINT, ' .
						'PRIMARY KEY (id) ' .
						') ENGINE=InnoDB',

						"INSERT INTO materials_request_status (description, isDefault, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Request Pending', 1, 0, '', 1)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Already owned/On order', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The Library already owns this item or it is already on order. Please access our catalog to place this item on hold.	Please check our online catalog periodically to put a hold for this item.', 0)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Item purchased', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. Outcome: The library is purchasing the item you requested. Please check our online catalog periodically to put yourself on hold for this item. We anticipate that this item will be available soon for you to place a hold.', 0)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Referred to Collection Development - Adult', 0, '', 1)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Referred to Collection Development - J/YA', 0, '', 1)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Referred to Collection Development - AV', 0, '', 1)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('ILL Under Review', 0, '', 1)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Request Referred to ILL', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The library\\'s Interlibrary loan department is reviewing your request. We will attempt to borrow this item from another system. This process generally takes about 2 - 6 weeks.', 1)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Request Filled by ILL', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. Our Interlibrary Loan Department is set to borrow this item from another library.', 0)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Ineligible ILL', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. Your library account is not eligible for interlibrary loan at this time.', 0)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Not enough info - please contact Collection Development to clarify', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We need more specific information in order to locate the exact item you need. Please re-submit your request with more details.', 1)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Unable to acquire the item - out of print', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is out of print.', 0)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Unable to acquire the item - not available in the US', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is not available in the US.', 0)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Unable to acquire the item - not available from vendor', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is not available from a preferred vendor.', 0)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Unable to acquire the item - not published', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The item you requested has not yet been published. Please check our catalog when the publication date draws near.', 0)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Unable to acquire the item - price', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item does not fit our collection guidelines.', 0)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Unable to acquire the item - publication date', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item does not fit our collection guidelines.', 0)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Unavailable', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The item you requested cannot be purchased at this time from any of our regular suppliers and is not available from any of our lending libraries.', 0)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen, isPatronCancel) VALUES ('Cancelled by Patron', 0, '', 0, 1)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Cancelled - Duplicate Request', 0, '', 0)",

						"UPDATE materials_request SET status = (SELECT id FROM materials_request_status WHERE isDefault =1)",

						"ALTER TABLE materials_request CHANGE `status` `status` INT(11)",
					),
				),

				'manageMaterialsRequestFieldsToDisplay' => array(
					'title' => 'Manage Material Requests Fields to Display Table Creation',
					'description' => 'New table to manage columns displayed in lists of materials requests on the manage page.',
					'sql' => array(
						"CREATE TABLE `materials_request_fields_to_display` ("
						."  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,"
						."  `libraryId` int(11) NOT NULL,"
						."  `columnNameToDisplay` varchar(30) NOT NULL,"
						."  `labelForColumnToDisplay` varchar(45) NOT NULL,"
						."  `weight` smallint(2) unsigned NOT NULL DEFAULT '0',"
						."  PRIMARY KEY (`id`),"
						."  UNIQUE KEY `columnNameToDisplay` (`columnNameToDisplay`,`libraryId`),"
						."  KEY `libraryId` (`libraryId`)"
						.") ENGINE=InnoDB DEFAULT CHARSET=utf8;"
					),
				),

				'materialsRequestFormats' => array(
					'title' => 'Material Requests Formats Table Creation',
					'description' => 'New table to manage materials formats that can be requested.',
					'sql' => array(
						'CREATE TABLE `materials_request_formats` ('
						.'`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,'
						.'`libraryId` INT UNSIGNED NOT NULL,'
						.' `format` VARCHAR(30) NOT NULL,'
						.'`formatLabel` VARCHAR(60) NOT NULL,'
						.'`authorLabel` VARCHAR(45) NOT NULL,'
						.'`weight` SMALLINT(2) UNSIGNED NOT NULL DEFAULT 0,'
						."`specialFields` SET('Abridged/Unabridged', 'Article Field', 'Eaudio format', 'Ebook format', 'Season') NULL,"
						.'PRIMARY KEY (`id`),'
						.'INDEX `libraryId` (`libraryId` ASC));'
					),
				),

				'materialsRequestFormFields' => array(
					'title' => 'Material Requests Form Fields Table Creation',
					'description' => 'New table to manage materials request form fields.',
					'sql' => array(
						'CREATE TABLE `materials_request_form_fields` ('
						.'`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,'
						.'`libraryId` INT UNSIGNED NOT NULL,'
						.'`formCategory` VARCHAR(55) NOT NULL,'
						.'`fieldLabel` VARCHAR(255) NOT NULL,'
						.'`fieldType` VARCHAR(30) NULL,'
						.'`weight` SMALLINT(2) UNSIGNED NOT NULL,'
						.'PRIMARY KEY (`id`),'
						.'UNIQUE INDEX `id_UNIQUE` (`id` ASC),'
						.'INDEX `libraryId` (`libraryId` ASC));'
					),
				),

				'staffSettingsTable' => array(
					'title' => 'Staff Settings Table Creation',
					'description' => 'New table to contain user settings for staff users.',
					'sql' => array(
						'CREATE TABLE `user_staff_settings` ('
						.'`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,'
						.'`userId` INT UNSIGNED NOT NULL,'
						.'`materialsRequestReplyToAddress` VARCHAR(70) NULL,'
						.'`materialsRequestEmailSignature` TINYTEXT NULL,'
						.'PRIMARY KEY (`id`),'
						.'UNIQUE INDEX `userId_UNIQUE` (`userId` ASC),'
						.'INDEX `userId` (`userId` ASC));'
					),
				),

				'materialsRequestLibraryId' => array(
					'title' => 'Add LibraryId to Material Requests Table',
					'description' => 'Add LibraryId column to Materials Request table and populate column for existing requests.',
					'sql' => array(
						'ALTER TABLE `materials_request` '
						.'ADD COLUMN `libraryId` INT UNSIGNED NULL AFTER `id`, '
						.'ADD COLUMN `formatId` INT UNSIGNED NULL AFTER `format`; ',

						'UPDATE  `materials_request`'
						 .'LEFT JOIN `user` ON (user.id=materials_request.createdBy) '
						 .'LEFT JOIN `location` ON (location.locationId=user.homeLocationId) '
						 .'SET materials_request.libraryId = location.libraryId '
						 .'WHERE materials_request.libraryId IS null '
						 .'and user.id IS NOT null '
						 .'and location.libraryId IS not null;',

						'UPDATE `materials_request` '
						.'LEFT JOIN `location` ON (location.locationId=materials_request.holdPickupLocation) '
						.'SET materials_request.libraryId = location.libraryId '
						.' WHERE materials_request.libraryId IS null and location.libraryId IS not null;'
					),
				),

				'materialsRequestFixColumns' => array(
					'title' => 'Change a Couple Column Data-Types for Material Requests Table',
					'description' => 'Change illitem and holdPickupLocation column data types for Material Requests Table.',
					'sql' => array(
						'ALTER TABLE `materials_request` '
						.'CHANGE COLUMN `illItem` `illItem` TINYINT(4) NULL DEFAULT NULL ;'
					),
				),

				'materialsRequestStatus_update1' => array(
					'title' => 'Materials Request Status Update 1',
					'description' => 'Material Request Status add library id',
					'sql' => array(
						"ALTER TABLE `materials_request_status` ADD `libraryId` INT(11) DEFAULT '-1'",
						'ALTER TABLE `materials_request_status` ADD INDEX (`libraryId`)',
					),
				),

				'catalogingRole' => array(
					'title' => 'Create cataloging role',
					'description' => 'Create cataloging role to handle materials requests, econtent loading, etc.',
					'sql' => array(
						"INSERT INTO `roles` (`name`, `description`) VALUES ('cataloging', 'Allows user to perform cataloging activities.')",
					),
				),

				'materialRequestsRole' => array(
					'title' => 'Create library material requests role',
					'description' => 'Create library materials request role to handle material requests for a specific library system.',
					'sql' => array(
						"INSERT INTO `roles` (`name`, `description`) VALUES ('library_material_requests', 'Allows user to manage material requests for a specific library.')",
					),
				),

				'newRolesJan2016' => array(
						'title' => 'Create new roles',
						'description' => 'Create library manager, location manager, and circulation reports roles.',
						'sql' => array(
								"INSERT INTO `roles` (`name`, `description`) VALUES ('libraryManager', 'Allows user to do basic configuration for their library.')",
								"INSERT INTO `roles` (`name`, `description`) VALUES ('locationManager', 'Allows user to do basic configuration for their location.')",
								"INSERT INTO `roles` (`name`, `description`) VALUES ('circulationReports', 'Allows user to view offline circulation reports.')",
						),
				),

				'libraryAdmin' => array(
					'title' => 'Create library admin role',
					'description' => 'Create library admin to allow .',
					'sql' => array(
						"INSERT INTO `roles` (`name`, `description`) VALUES ('libraryAdmin', 'Allows user to update library configuration for their library system only for their home location.')",
					),
				),

				'contentEditor' => array(
					'title' => 'Create Content Editor role',
					'description' => 'Create Content Editor Role to allow entering of editorial reviews and creation of widgets.',
					'sql' => array(
						"INSERT INTO `roles` (`name`, `description`) VALUES ('contentEditor', 'Allows entering of editorial reviews and creation of widgets.')",
					),
				),

					'listPublisherRole' => array(
							'title' => 'Create library publisher role',
							'description' => 'Create library publisher role to include lists from specific users within search results.',
							'sql' => array(
									"INSERT INTO `roles` (`name`, `description`) VALUES ('listPublisher', 'Optionally only include lists from people with this role in search results.')",
							),
					),

					'archivesRole' => array(
							'title' => 'Create archives role',
							'description' => 'Create archives role to allow control over archives integration.',
							'sql' => array(
									"INSERT INTO `roles` (`name`, `description`) VALUES ('archives', 'Control overall archives integration.')",
							),
					),

					'ip_lookup_1' => array(
					'title' => 'IP Lookup Update 1',
					'description' => 'Add start and end ranges for IP Lookup table to improve performance.',
					'continueOnError' => true,
					'sql' => array(
						"ALTER TABLE ip_lookup ADD COLUMN startIpVal BIGINT",
						"ALTER TABLE ip_lookup ADD COLUMN endIpVal BIGINT",
						"ALTER TABLE `ip_lookup` ADD INDEX ( `startIpVal` )",
						"ALTER TABLE `ip_lookup` ADD INDEX ( `endIpVal` )",
						"createDefaultIpRanges"
					),
				),

				'ip_lookup_2' => array(
					'title' => 'IP Lookup Update 2',
					'description' => 'Change start and end ranges to be big integers.',
					'continueOnError' => true,
					'sql' => array(
						"ALTER TABLE `ip_lookup` CHANGE `startIpVal` `startIpVal` BIGINT NULL DEFAULT NULL ",
						"ALTER TABLE `ip_lookup` CHANGE `endIpVal` `endIpVal` BIGINT NULL DEFAULT NULL ",
						"createDefaultIpRanges"
					),
				),

				'ip_lookup_3' => array(
					'title' => 'IP Lookup isOpac switch',
					'description' => 'Add an IsOpac switch to each ip address entry',
					'continueOnError' => true,
					'sql' => array(
						"ALTER TABLE `ip_lookup` ADD COLUMN `isOpac` TINYINT UNSIGNED NOT NULL DEFAULT 1",

					),
				),

				'merged_records' => array(
					'title' => 'Merged Records Table',
					'description' => 'Create Merged Records table to store ',
					'sql' => array(
						"CREATE TABLE `merged_records` (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							`original_record` VARCHAR( 20 ) NOT NULL,
							`new_record` VARCHAR( 20 ) NOT NULL,
							UNIQUE INDEX (original_record),
							INDEX(new_record)
						)",
					),
				),

				'nongrouped_records' => array(
						'title' => 'Non-grouped Records Table',
						'description' => 'Create non-grouped Records table to store records that should not be grouped',
						'sql' => array(
								"CREATE TABLE `nongrouped_records` (
									id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
									`source` VARCHAR( 50 ) NOT NULL,
									`recordId` VARCHAR( 36 ) NOT NULL,
									`notes` VARCHAR( 255 ) NOT NULL,
									UNIQUE INDEX (source, recordId)
								)",
						),
				),

				'author_enrichment' => array(
						'title' => 'Author Enrichment',
						'description' => 'Create table to store enrichment for authors',
						'sql' => array(
								"CREATE TABLE `author_enrichment` (
									id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
									`authorName` VARCHAR( 255 ) NOT NULL,
									`hideWikipedia` TINYINT( 1 ),
									`wikipediaUrl` VARCHAR( 255 ),
									INDEX(authorName)
								)",
						),
				),

				'variables_table' => array(
					'title' => 'Variables Table',
					'description' => 'Create Variables Table for storing basic variables for use in programs (system writable config)',
					'sql' => array(
						"CREATE TABLE `variables` (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							`name` VARCHAR( 128 ) NOT NULL,
							`value` VARCHAR( 255 ),
							INDEX(name)
						)",
					),
				),

				'variables_table_uniqueness' => array(
					'title' => 'Variables Table Uniqueness',
					'description' => 'Create Variables Table for storing basic variables for use in programs (system writable config)',
					'sql' => array(
						"DELETE FROM variables where name = 'lastPartialReindexFinish'",
						"ALTER TABLE variables ADD UNIQUE (name)",
					),
				),

				'variables_validateChecksumsFromDisk' => array(
						'title' => 'Variables Validate Checksums from Disk variable',
						'description' => 'Add a variable to control whether or not we should validate checksums on the disk.',
						'sql' => array(
								"INSERT INTO variables (name, value) VALUES ('validateChecksumsFromDisk', 'false')",
						),
				),

				'variables_offline_mode_when_offline_login_allowed' => array(
						'title' => 'Variables Offline Mode When Offline Login is Allowed',
						'description' => 'Add a variable to allow setting offline mode from the Pika interface, as long as offline logins are allowed.',
						'sql' => array(
								"INSERT INTO variables (name, value) VALUES ('offline_mode_when_offline_login_allowed', 'false')",
						),
				),

					'variables_full_index_warnings' => array(
							'title' => 'Variables for how long of an interval to allow between full indexes',
							'description' => 'Add a variable to allow setting offline mode from the Pika interface, as long as offline logins are allowed.',
							'sql' => array(
									"INSERT INTO variables (name, value) VALUES ('fullReindexIntervalWarning', '86400')",
									"INSERT INTO variables (name, value) VALUES ('fullReindexIntervalCritical', '129600')",
							),
					),

				'utf8_update' => array(
					'title' => 'Update to UTF-8',
					'description' => 'Update database to use UTF-8 encoding',
					'continueOnError' => true,
					'sql' => array(
						"ALTER DATABASE " . $configArray['Database']['database_vufind_dbname'] . " DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;",
						//"ALTER TABLE administrators CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE bad_words CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE circulation_status CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE comments CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE db_update CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE editorial_reviews CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE external_link_tracking CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE ip_lookup CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE library CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE list_widgets CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE list_widget_lists CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE location CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						//"ALTER TABLE nonHoldableLocations CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						//"ALTER TABLE ptype_restricted_locations CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE purchase_link_tracking CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE resource CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE resource_tags CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE roles CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE search CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE search_stats CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE session CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE spelling_words CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE tags CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						//"ALTER TABLE usage_tracking CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE user CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE user_list CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE user_rating CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE user_reading_history CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE user_resource CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE user_roles CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE user_suggestions CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
					),
				),

				'index_resources' => array(
					'title' => 'Index resources',
					'description' => 'Add a new index to resources table to make record id and source unique',
					'continueOnError' => true,
					'sql' => array(
						//Update resource table indexes
						"ALTER TABLE `resource` ADD UNIQUE `records_by_source` (`record_id`, `source`)"
					),
				),

				'alpha_browse_setup_2' => array(
					'title' => 'Setup Alphabetic Browse',
					'description' => 'Build tables to handle alphabetic browse functionality.',
					'sql' => array(
						"DROP TABLE IF EXISTS `title_browse`",
						"CREATE TABLE `title_browse` (
							`id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of the browse record in numerical order based on the sort order of the rows',
							`value` VARCHAR( 255 ) NOT NULL COMMENT 'The original value',
							`sortValue` VARCHAR( 255 ) NOT NULL COMMENT 'The value to sort by',
						PRIMARY KEY ( `id` ) ,
						INDEX ( `sortValue` ),
						UNIQUE (`value`)
						) ENGINE = MYISAM;",

						"DROP TABLE IF EXISTS `author_browse`",
						"CREATE TABLE `author_browse` (
							`id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of the browse record in numerical order based on the sort order of the rows',
							`value` VARCHAR( 255 ) NOT NULL COMMENT 'The original value',
							`sortValue` VARCHAR( 255 ) NOT NULL COMMENT 'The value to sort by',
						PRIMARY KEY ( `id` ) ,
						INDEX ( `sortValue` ),
						UNIQUE (`value`)
						) ENGINE = MYISAM;",

						"DROP TABLE IF EXISTS `callnumber_browse`",
						"CREATE TABLE `callnumber_browse` (
							`id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of the browse record in numerical order based on the sort order of the rows',
							`value` VARCHAR( 255 ) NOT NULL COMMENT 'The original value',
							`sortValue` VARCHAR( 255 ) NOT NULL COMMENT 'The value to sort by',
						PRIMARY KEY ( `id` ) ,
						INDEX ( `sortValue` ),
						UNIQUE (`value`)
						) ENGINE = MYISAM;",

						"DROP TABLE IF EXISTS `subject_browse`",
						"CREATE TABLE `subject_browse` (
							`id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of the browse record in numerical order based on the sort order of the rows',
							`value` VARCHAR( 255 ) NOT NULL COMMENT 'The original value',
							`sortValue` VARCHAR( 255 ) NOT NULL COMMENT 'The value to sort by',
						PRIMARY KEY ( `id` ) ,
						INDEX ( `sortValue` ),
						UNIQUE (`value`)
						) ENGINE = MYISAM;",
					),
				),

				'alpha_browse_setup_3' => array(
					'title' => 'Alphabetic Browse Performance',
					'description' => 'Create additional indexes and columns to improve performance of Alphabetic Browse.',
					'sql' => array(
						//Author browse
						//"ALTER TABLE `author_browse_scoped_results` ADD INDEX ( `browseValueId` )",
						//"ALTER TABLE `author_browse_scoped_results` ADD INDEX ( `scope` )",
						//"ALTER TABLE `author_browse_scoped_results` ADD INDEX ( `record` )",
						"ALTER TABLE `author_browse` ADD COLUMN `alphaRank` INT( 11 ) NOT NULL COMMENT 'A numerical ranking of the sort values from a-z'",
						"ALTER TABLE `author_browse` ADD INDEX ( `alphaRank` )",
						"set @r=0;",
						"UPDATE author_browse SET alphaRank = @r:=(@r + 1) ORDER BY `sortValue`;",

						//Call number browse
						//"ALTER TABLE `callnumber_browse_scoped_results` ADD INDEX ( `browseValueId` )",
						//"ALTER TABLE `callnumber_browse_scoped_results` ADD INDEX ( `scope` )",
						//"ALTER TABLE `callnumber_browse_scoped_results` ADD INDEX ( `record` )",
						"ALTER TABLE `callnumber_browse` ADD COLUMN `alphaRank` INT( 11 ) NOT NULL COMMENT 'A numerical ranking of the sort values from a-z'",
						"ALTER TABLE `callnumber_browse` ADD INDEX ( `alphaRank` )",
						"set @r=0;",
						"UPDATE callnumber_browse SET alphaRank = @r:=(@r + 1) ORDER BY `sortValue`;",

						//Subject Browse
						//"ALTER TABLE `subject_browse_scoped_results` ADD INDEX ( `browseValueId` )",
						//"ALTER TABLE `subject_browse_scoped_results` ADD INDEX ( `scope` )",
						//"ALTER TABLE `subject_browse_scoped_results` ADD INDEX ( `record` )",
						"ALTER TABLE `subject_browse` ADD COLUMN `alphaRank` INT( 11 ) NOT NULL COMMENT 'A numerical ranking of the sort values from a-z'",
						"ALTER TABLE `subject_browse` ADD INDEX ( `alphaRank` )",
						"set @r=0;",
						"UPDATE subject_browse SET alphaRank = @r:=(@r + 1) ORDER BY `sortValue`;",

						//Tile Browse
						//"ALTER TABLE `title_browse_scoped_results` ADD INDEX ( `browseValueId` )",
						//"ALTER TABLE `title_browse_scoped_results` ADD INDEX ( `scope` )",
						//"ALTER TABLE `title_browse_scoped_results` ADD INDEX ( `record` )",
						"ALTER TABLE `title_browse` ADD COLUMN `alphaRank` INT( 11 ) NOT NULL COMMENT 'A numerical ranking of the sort values from a-z'",
						"ALTER TABLE `title_browse` ADD INDEX ( `alphaRank` )",
						"set @r=0;",
						"UPDATE title_browse SET alphaRank = @r:=(@r + 1) ORDER BY `sortValue`;",
					),
				),

				'alpha_browse_setup_4' => array(
					'title' => 'Alphabetic Browse Metadata',
					'description' => 'Create metadata about alphabetic browsing improve performance of Alphabetic Browse.',
					'sql' => array(
						"CREATE TABLE author_browse_metadata (
							`scope` TINYINT( 4 ) NOT NULL ,
							`scopeId` INT( 11 ) NOT NULL ,
							`minAlphaRank` INT NOT NULL ,
							`maxAlphaRank` INT NOT NULL ,
							`numResults` INT NOT NULL
						) ENGINE = InnoDB;",
						//"INSERT INTO author_browse_metadata (SELECT scope, scopeId, MIN(alphaRank) as minAlphaRank, MAX(alphaRank) as maxAlphaRank, count(id) as numResults FROM author_browse inner join author_browse_scoped_results ON id = browseValueId GROUP BY scope, scopeId)",

						"CREATE TABLE callnumber_browse_metadata (
							`scope` TINYINT( 4 ) NOT NULL ,
							`scopeId` INT( 11 ) NOT NULL ,
							`minAlphaRank` INT NOT NULL ,
							`maxAlphaRank` INT NOT NULL ,
							`numResults` INT NOT NULL
						) ENGINE = InnoDB;",
						//"INSERT INTO callnumber_browse_metadata (SELECT scope, scopeId, MIN(alphaRank) as minAlphaRank, MAX(alphaRank) as maxAlphaRank, count(id) as numResults FROM callnumber_browse inner join callnumber_browse_scoped_results ON id = browseValueId GROUP BY scope, scopeId)",

						"CREATE TABLE title_browse_metadata (
							`scope` TINYINT( 4 ) NOT NULL ,
							`scopeId` INT( 11 ) NOT NULL ,
							`minAlphaRank` INT NOT NULL ,
							`maxAlphaRank` INT NOT NULL ,
							`numResults` INT NOT NULL
						) ENGINE = InnoDB;",
						//"INSERT INTO title_browse_metadata (SELECT scope, scopeId, MIN(alphaRank) as minAlphaRank, MAX(alphaRank) as maxAlphaRank, count(id) as numResults FROM title_browse inner join title_browse_scoped_results ON id = browseValueId GROUP BY scope, scopeId)",

						"CREATE TABLE subject_browse_metadata (
							`scope` TINYINT( 4 ) NOT NULL ,
							`scopeId` INT( 11 ) NOT NULL ,
							`minAlphaRank` INT NOT NULL ,
							`maxAlphaRank` INT NOT NULL ,
							`numResults` INT NOT NULL
						) ENGINE = InnoDB;",
						//"INSERT INTO subject_browse_metadata (SELECT scope, scopeId, MIN(alphaRank) as minAlphaRank, MAX(alphaRank) as maxAlphaRank, count(id) as numResults FROM subject_browse inner join subject_browse_scoped_results ON id = browseValueId GROUP BY scope, scopeId)",
					),
				),

				'alpha_browse_setup_5' => array(
					'title' => 'Alphabetic Browse scoped tables',
					'description' => 'Create Scoping tables for global and all libraries.',
					'continueOnError' => true,
					'sql' => array(
						//Add firstChar fields
						"ALTER TABLE `title_browse` ADD `firstChar` CHAR( 1 ) NOT NULL",
						"ALTER TABLE title_browse ADD INDEX ( `firstChar` )",
						'UPDATE title_browse SET firstChar = SUBSTR(sortValue, 1, 1);',
						"ALTER TABLE `author_browse` ADD `firstChar` CHAR( 1 ) NOT NULL",
						"ALTER TABLE author_browse ADD INDEX ( `firstChar` )",
						'UPDATE author_browse SET firstChar = SUBSTR(sortValue, 1, 1);',
						"ALTER TABLE `subject_browse` ADD `firstChar` CHAR( 1 ) NOT NULL",
						"ALTER TABLE subject_browse ADD INDEX ( `firstChar` )",
						'UPDATE subject_browse SET firstChar = SUBSTR(sortValue, 1, 1);',
						"ALTER TABLE `callnumber_browse` ADD `firstChar` CHAR( 1 ) NOT NULL",
						"ALTER TABLE callnumber_browse ADD INDEX ( `firstChar` )",
						'UPDATE callnumber_browse SET firstChar = SUBSTR(sortValue, 1, 1);',
						//Create global tables
						'CREATE TABLE `title_browse_scoped_results_global` (
							`browseValueId` INT( 11 ) NOT NULL ,
							`record` VARCHAR( 50 ) NOT NULL ,
							PRIMARY KEY ( `browseValueId` , `record` ) ,
							INDEX ( `browseValueId` )
						) ENGINE = MYISAM',
						'CREATE TABLE `author_browse_scoped_results_global` (
							`browseValueId` INT( 11 ) NOT NULL ,
							`record` VARCHAR( 50 ) NOT NULL ,
							PRIMARY KEY ( `browseValueId` , `record` ) ,
							INDEX ( `browseValueId` )
						) ENGINE = MYISAM',
						'CREATE TABLE `subject_browse_scoped_results_global` (
							`browseValueId` INT( 11 ) NOT NULL ,
							`record` VARCHAR( 50 ) NOT NULL ,
							PRIMARY KEY ( `browseValueId` , `record` ) ,
							INDEX ( `browseValueId` )
						) ENGINE = MYISAM',
						'CREATE TABLE `callnumber_browse_scoped_results_global` (
							`browseValueId` INT( 11 ) NOT NULL ,
							`record` VARCHAR( 50 ) NOT NULL ,
							PRIMARY KEY ( `browseValueId` , `record` ) ,
							INDEX ( `browseValueId` )
						) ENGINE = MYISAM',
						//Truncate old data
						"TRUNCATE TABLE `title_browse_scoped_results_global`",
						"TRUNCATE TABLE `author_browse_scoped_results_global`",
						"TRUNCATE TABLE `subject_browse_scoped_results_global`",
						"TRUNCATE TABLE `callnumber_browse_scoped_results_global`",
						//Load data from old method into tables
						/*'INSERT INTO title_browse_scoped_results_global (`browseValueId`, record)
							SELECT title_browse_scoped_results.browseValueId, title_browse_scoped_results.record
							FROM title_browse_scoped_results
							WHERE scope = 0;',
						'INSERT INTO author_browse_scoped_results_global (`browseValueId`, record)
							SELECT author_browse_scoped_results.browseValueId, author_browse_scoped_results.record
							FROM author_browse_scoped_results
							WHERE scope = 0;',
						'INSERT INTO subject_browse_scoped_results_global (`browseValueId`, record)
							SELECT subject_browse_scoped_results.browseValueId, subject_browse_scoped_results.record
							FROM subject_browse_scoped_results
							WHERE scope = 0;',
						'INSERT INTO callnumber_browse_scoped_results_global (`browseValueId`, record)
							SELECT callnumber_browse_scoped_results.browseValueId, callnumber_browse_scoped_results.record
							FROM callnumber_browse_scoped_results
							WHERE scope = 0;',*/
						'createScopingTables',
						/*'DROP TABLE title_browse_scoped_results',
						'DROP TABLE author_browse_scoped_results',
						'DROP TABLE subject_browse_scoped_results',
						'DROP TABLE callnumber_browse_scoped_results',*/

					),
				),

				'alpha_browse_setup_6' => array(
					'title' => 'Alphabetic Browse second letter',
					'description' => 'Add second char to the tables.',
					'continueOnError' => true,
					'sql' => array(
						"ALTER TABLE `title_browse` ADD `secondChar` CHAR( 1 ) NOT NULL",
						"ALTER TABLE title_browse ADD INDEX ( `secondChar` )",
						'UPDATE title_browse SET secondChar = substr(sortValue, 2, 1);',
						"ALTER TABLE `author_browse` ADD `secondChar` CHAR( 1 ) NOT NULL",
						"ALTER TABLE author_browse ADD INDEX ( `secondChar` )",
						'UPDATE author_browse SET secondChar = substr(sortValue, 2, 1);',
						"ALTER TABLE `subject_browse` ADD `secondChar` CHAR( 1 ) NOT NULL",
						"ALTER TABLE subject_browse ADD INDEX ( `secondChar` )",
						'UPDATE subject_browse SET secondChar = substr(sortValue, 2, 1);',
						"ALTER TABLE `callnumber_browse` ADD `secondChar` CHAR( 1 ) NOT NULL",
						"ALTER TABLE callnumber_browse ADD INDEX ( `secondChar` )",
						'UPDATE callnumber_browse SET secondChar = substr(sortValue, 2, 1);',
					),
				),

				'alpha_browse_setup_7' => array(
					'title' => 'Alphabetic Browse change scoping engine',
					'description' => 'Change DB Engine to INNODB for all scoping tables.',
					'continueOnError' => true,
					'sql' => array(
						"setScopingTableEngine",
					),
				),

				'alpha_browse_setup_8' => array(
					'title' => 'Alphabetic Browse change scoping engine',
					'description' => 'Change DB Engine to INNODB for all scoping tables.',
					'continueOnError' => true,
					'sql' => array(
						"setScopingTableEngine2",
					),
				),

				'alpha_browse_setup_9' => array(
					'title' => 'Alphabetic Browse remove record indices',
					'description' => 'Remove record indices since they are no longer needed and make the import slower, also use MyISAM engine since that is faster for import.',
					'continueOnError' => true,
					'sql' => array(
						"removeScopingTableIndex",
					),
				),

				'reindexLog' => array(
					'title' => 'Reindex Log table',
					'description' => 'Create Reindex Log table to track reindexing.',
					'sql' => array(
						"CREATE TABLE IF NOT EXISTS reindex_log(" .
						"`id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of reindex log', " .
						"`startTime` INT(11) NOT NULL COMMENT 'The timestamp when the reindex started', " .
						"`endTime` INT(11) NULL COMMENT 'The timestamp when the reindex process ended', " .
						"PRIMARY KEY ( `id` )" .
						") ENGINE = MYISAM;",
						"CREATE TABLE IF NOT EXISTS reindex_process_log(" .
						"`id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of reindex process', " .
						"`reindex_id` INT(11) NOT NULL COMMENT 'The id of the reindex log this process ran during', " .
						"`processName` VARCHAR(50) NOT NULL COMMENT 'The name of the process being run', " .
						"`recordsProcessed` INT(11) NOT NULL COMMENT 'The number of records processed from marc files', " .
						"`eContentRecordsProcessed` INT(11) NOT NULL COMMENT 'The number of econtent records processed from the database', " .
						"`resourcesProcessed` INT(11) NOT NULL COMMENT 'The number of resources processed from the database', " .
						"`numErrors` INT(11) NOT NULL COMMENT 'The number of errors that occurred during the process', " .
						"`numAdded` INT(11) NOT NULL COMMENT 'The number of additions that occurred during the process', " .
						"`numUpdated` INT(11) NOT NULL COMMENT 'The number of items updated during the process', " .
						"`numDeleted` INT(11) NOT NULL COMMENT 'The number of items deleted during the process', " .
						"`numSkipped` INT(11) NOT NULL COMMENT 'The number of items skipped during the process', " .
						"`notes` TEXT COMMENT 'Additional information about the process', " .
						"PRIMARY KEY ( `id` ), INDEX ( `reindex_id` ), INDEX ( `processName` )" .
						") ENGINE = MYISAM;",

					),
				),

				'reindexLog_1' => array(
					'title' => 'Reindex Log table update 1',
					'description' => 'Update Reindex Log table to include notes and last update.',
					'sql' => array(
						"ALTER TABLE reindex_log ADD COLUMN `notes` TEXT COMMENT 'Notes related to the overall process'",
						"ALTER TABLE reindex_log ADD `lastUpdate` INT(11) COMMENT 'The last time the log was updated'",
					),
				),

				'reindexLog_2' => array(
					'title' => 'Reindex Log table update 2',
					'description' => 'Update Reindex Log table to include a count of non-marc records that have been processed.',
					'sql' => array(
						"ALTER TABLE reindex_process_log ADD COLUMN `overDriveNonMarcRecordsProcessed` INT(11) COMMENT 'The number of overdrive records processed that do not have a marc record associated with them.'",
					),
				),


				'reindexLog_grouping' => array(
					'title' => 'Reindex Log Grouping Update',
					'description' => 'Update Reindex Logging for Record Grouping.',
					'sql' => array(
						"DROP TABLE reindex_process_log",
						"ALTER TABLE reindex_log ADD COLUMN numWorksProcessed INT(11) NOT NULL DEFAULT 0",
						"ALTER TABLE reindex_log ADD COLUMN numListsProcessed INT(11) NOT NULL DEFAULT 0"
					),
				),


				'cronLog' => array(
					'title' => 'Cron Log table',
					'description' => 'Create Cron Log table to track reindexing.',
					'sql' => array(
						"CREATE TABLE IF NOT EXISTS cron_log(" .
						"`id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of the cron log', " .
						"`startTime` INT(11) NOT NULL COMMENT 'The timestamp when the cron run started', " .
						"`endTime` INT(11) NULL COMMENT 'The timestamp when the cron run ended', " .
						"`lastUpdate` INT(11) NULL COMMENT 'The timestamp when the cron run last updated (to check for stuck processes)', " .
						"`notes` TEXT COMMENT 'Additional information about the cron run', " .
						"PRIMARY KEY ( `id` )" .
						") ENGINE = MYISAM;",
						"CREATE TABLE IF NOT EXISTS cron_process_log(" .
						"`id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of cron process', " .
						"`cronId` INT(11) NOT NULL COMMENT 'The id of the cron run this process ran during', " .
						"`processName` VARCHAR(50) NOT NULL COMMENT 'The name of the process being run', " .
						"`startTime` INT(11) NOT NULL COMMENT 'The timestamp when the process started', " .
						"`lastUpdate` INT(11) NULL COMMENT 'The timestamp when the process last updated (to check for stuck processes)', " .
						"`endTime` INT(11) NULL COMMENT 'The timestamp when the process ended', " .
						"`numErrors` INT(11) NOT NULL DEFAULT 0 COMMENT 'The number of errors that occurred during the process', " .
						"`numUpdates` INT(11) NOT NULL DEFAULT 0 COMMENT 'The number of updates, additions, etc. that occurred', " .
						"`notes` TEXT COMMENT 'Additional information about the process', " .
						"PRIMARY KEY ( `id` ), INDEX ( `cronId` ), INDEX ( `processName` )" .
						") ENGINE = MYISAM;",

					),
				),

				'marcImport' => array(
					'title' => 'Marc Import table',
					'description' => 'Create a table to store information about marc records that are being imported.',
					'sql' => array(
						"CREATE TABLE IF NOT EXISTS marc_import(" .
						"`id` VARCHAR(50) COMMENT 'The id of the marc record in the ils', " .
						"`checksum` INT(11) NOT NULL COMMENT 'The timestamp when the reindex started', " .
						"PRIMARY KEY ( `id` )" .
						") ENGINE = MYISAM;",
					),
				),
				'marcImport_1' => array(
					'title' => 'Marc Import table Update 1',
					'description' => 'Increase the length of the checksum field for the marc import.',
					'sql' => array(
						"ALTER TABLE marc_import CHANGE `checksum` `checksum` BIGINT NOT NULL COMMENT 'The checksum of the id as it currently exists in the active index.'",
					),
				),
				'marcImport_2' => array(
					'title' => 'Marc Import table Update 2',
					'description' => 'Increase the length of the checksum field for the marc import.',
					'sql' => array(
						"ALTER TABLE marc_import ADD COLUMN `backup_checksum` BIGINT COMMENT 'The checksum of the id in the backup index.'",
						"ALTER TABLE marc_import ADD COLUMN `eContent` TINYINT NOT NULL COMMENT 'Whether or not the record was detected as eContent in the active index.'",
						"ALTER TABLE marc_import ADD COLUMN `backup_eContent` TINYINT COMMENT 'Whether or not the record was detected as eContent in the backup index.'",
					),
				),
				'marcImport_3' => array(
					'title' => 'Marc Import table Update 3',
					'description' => 'Make backup fields optional.',
					'sql' => array(
						"ALTER TABLE marc_import CHANGE `backup_checksum` `backup_checksum` BIGINT COMMENT 'The checksum of the id in the backup index.'",
						"ALTER TABLE marc_import CHANGE `backup_eContent` `backup_eContent` TINYINT COMMENT 'Whether or not the record was detected as eContent in the backup index.'",
					),
				),
				'add_indexes' => array(
					'title' => 'Add indexes',
					'description' => 'Add indexes to tables that were not defined originally',
					'continueOnError' => true,
					'sql' => array(
						'ALTER TABLE `editorial_reviews` ADD INDEX `RecordId` ( `recordId` ) ',
						'ALTER TABLE `list_widget_lists` ADD INDEX `ListWidgetId` ( `listWidgetId` ) ',
						'ALTER TABLE `location` ADD INDEX `ValidHoldPickupBranch` ( `validHoldPickupBranch` ) ',
					),
				),

				'add_indexes2' => array(
					'title' => 'Add indexes 2',
					'description' => 'Add additional indexes to tables that were not defined originally',
					'continueOnError' => true,
					'sql' => array(
						'ALTER TABLE `user_rating` ADD INDEX `Resourceid` ( `resourceid` ) ',
						'ALTER TABLE `user_rating` ADD INDEX `UserId` ( `userid` ) ',
						'ALTER TABLE `materials_request_status` ADD INDEX ( `isDefault` )',
						'ALTER TABLE `materials_request_status` ADD INDEX ( `isOpen` )',
						'ALTER TABLE `materials_request_status` ADD INDEX ( `isPatronCancel` )',
						'ALTER TABLE `materials_request` ADD INDEX ( `status` )'
					),
				),

				'spelling_optimization' => array(
					'title' => 'Spelling Optimization',
					'description' => 'Optimizations to spelling to ensure indexes are used',
					'sql' => array(
						'ALTER TABLE `spelling_words` ADD `soundex` VARCHAR(20) ',
						'ALTER TABLE `spelling_words` ADD INDEX `Soundex` (`soundex`)',
						'UPDATE `spelling_words` SET soundex = SOUNDEX(word) '
					),
				),

				'boost_disabling' => array(
					'title' => 'Disabling Lib and Loc Boosting',
					'description' => 'Allow boosting of library and location boosting to be disabled',
					'sql' => array(
						"ALTER TABLE `library` ADD `boostByLibrary` TINYINT DEFAULT '1'",
						"ALTER TABLE `location` ADD `boostByLocation` TINYINT DEFAULT '1'",
					),
				),

				'rename_tables' => array(
					'title' => 'Rename tables',
					'description' => 'Rename tables for consistency and cross platform usage',
					'sql' => array(
						//Update resource table indexes
						'RENAME TABLE usageTracking TO usage_tracking',
						'RENAME TABLE nonHoldableLocations TO non_holdable_locations',
						'RENAME TABLE pTypeRestrictedLocations TO ptype_restricted_locations',
						'RENAME TABLE externalLinkTracking TO external_link_tracking',
						'RENAME TABLE purchaseLinkTracking TO purchase_link_tracking'
					),
				),

				'addTablelistWidgetListsLinks' => array(
					'title' => 'Widget Lists',
					'description' => 'Add a new table: list_widget_lists_links',
					'sql' => array('addTableListWidgetListsLinks'),
				),


				'millenniumTables' => array(
					'title' => 'Millennium table setup',
					'description' => 'Add new tables for millennium installations',
					'continueOnError' => true,
					'sql' => array(
						"CREATE TABLE `millennium_cache` (
								`recordId` VARCHAR( 20 ) NOT NULL COMMENT 'The recordId being checked',
								`scope` INT(16) NOT NULL COMMENT 'The scope that was loaded',
								`holdingsInfo` MEDIUMTEXT NOT NULL COMMENT 'Raw HTML returned from Millennium for holdings',
								`framesetInfo` MEDIUMTEXT NOT NULL COMMENT 'Raw HTML returned from Millennium on the frameset page',
								`cacheDate` INT(16) NOT NULL COMMENT 'When the entry was recorded in the cache'
						) ENGINE = MYISAM COMMENT = 'Caches information from Millennium so we do not have to continually load it.';",
						"ALTER TABLE `millennium_cache` ADD PRIMARY KEY ( `recordId` , `scope` ) ;",

						"CREATE TABLE IF NOT EXISTS `ptype_restricted_locations` (
							`locationId` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique id for the non holdable location',
							`millenniumCode` VARCHAR(5) NOT NULL COMMENT 'The internal 5 letter code within Millennium',
							`holdingDisplay` VARCHAR(30) NOT NULL COMMENT 'The text displayed in the holdings list within Millennium can use regular expression syntax to match multiple locations',
							`allowablePtypes` VARCHAR(50) NOT NULL COMMENT 'A list of PTypes that are allowed to place holds on items with this location separated with pipes (|).',
							PRIMARY KEY (`locationId`)
						) ENGINE=MYISAM",

						"CREATE TABLE IF NOT EXISTS `non_holdable_locations` (
							`locationId` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique id for the non holdable location',
							`millenniumCode` VARCHAR(5) NOT NULL COMMENT 'The internal 5 letter code within Millennium',
							`holdingDisplay` VARCHAR(30) NOT NULL COMMENT 'The text displayed in the holdings list within Millennium',
							`availableAtCircDesk` TINYINT(4) NOT NULL COMMENT 'The item is available if the patron visits the circulation desk.',
							PRIMARY KEY (`locationId`)
						) ENGINE=MYISAM"
					),
				),

				'loan_rule_determiners_1' => array(
					'title' => 'Loan Rule Determiners',
					'description' => 'Build tables to store loan rule determiners',
					'sql' => array(
						"CREATE TABLE IF NOT EXISTS loan_rules (" .
						"`id` INT NOT NULL AUTO_INCREMENT, " .
						"`loanRuleId` INT NOT NULL COMMENT 'The location id', " .
						"`name` varchar(50) NOT NULL COMMENT 'The location code the rule applies to', " .
						"`code` char(1) NOT NULL COMMENT '', " .
						"`normalLoanPeriod` INT(4) NOT NULL COMMENT 'Number of days the item checks out for', " .
						"`holdable` TINYINT NOT NULL DEFAULT '0', " .
						"`bookable` TINYINT NOT NULL DEFAULT '0', " .
						"`homePickup` TINYINT NOT NULL DEFAULT '0', " .
						"`shippable` TINYINT NOT NULL DEFAULT '0', " .
						"PRIMARY KEY ( `id` ), " .
						"INDEX ( `loanRuleId` ), " .
						"INDEX (`holdable`) " .
						") ENGINE=InnoDB",
						"CREATE TABLE IF NOT EXISTS loan_rule_determiners (" .
						"`id` INT NOT NULL AUTO_INCREMENT, " .
						"`rowNumber` INT NOT NULL COMMENT 'The row of the determiner.  Rules are processed in reverse order', " .
						"`location` varchar(10) NOT NULL COMMENT '', " .
						"`patronType` VARCHAR(50) NOT NULL COMMENT 'The patron types that this rule applies to', " .
						"`itemType` VARCHAR(255) NOT NULL DEFAULT '0' COMMENT 'The item types that this rule applies to', " .
						"`ageRange` varchar(10) NOT NULL COMMENT '', " .
						"`loanRuleId` varchar(10) NOT NULL COMMENT 'Close hour (24hr format) HH:MM', " .
						"`active` TINYINT NOT NULL DEFAULT '0', " .
						"PRIMARY KEY ( `id` ), " .
						"INDEX ( `rowNumber` ), " .
						"INDEX (`active`) " .
						") ENGINE=InnoDB",
					),
				),

					'loan_rule_determiners_increase_ptype_length' => array(
							'title' => 'Increase PType field length for Loan Rule Determiners',
							'description' => 'Increase PType field length for Loan Rule Determiners',
							'sql' => array(
								"ALTER TABLE loan_rule_determiners CHANGE COLUMN patronType `patronType` VARCHAR(255) NOT NULL COMMENT 'The patron types that this rule applies to'",
							),
					),

				'location_hours' => array(
					'title' => 'Location Hours',
					'description' => 'Build table to store hours for a location',
					'sql' => array(
						"CREATE TABLE IF NOT EXISTS location_hours (" .
						"`id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of hours entry', " .
						"`locationId` INT NOT NULL COMMENT 'The location id', " .
						"`day` INT NOT NULL COMMENT 'Day of the week 0 to 7 (Sun to Monday)', " .
						"`closed` TINYINT NOT NULL DEFAULT '0' COMMENT 'Whether or not the library is closed on this day', " .
						"`open` varchar(10) NOT NULL COMMENT 'Open hour (24hr format) HH:MM', " .
						"`close` varchar(10) NOT NULL COMMENT 'Close hour (24hr format) HH:MM', " .
						"PRIMARY KEY ( `id` ), " .
						"UNIQUE KEY (`locationId`, `day`) " .
						") ENGINE=InnoDB",
					),
				),
				'holiday' => array(
					'title' => 'Holidays',
					'description' => 'Build table to store holidays',
					'sql' => array(
						"CREATE TABLE IF NOT EXISTS holiday (" .
						"`id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of holiday', " .
						"`libraryId` INT NOT NULL COMMENT 'The library system id', " .
						"`date` date NOT NULL COMMENT 'Date of holiday', " .
						"`name` varchar(100) NOT NULL COMMENT 'Name of holiday', " .
						"PRIMARY KEY ( `id` ), " .
						"UNIQUE KEY (`date`) " .
						") ENGINE=InnoDB",
					),
				),

				'holiday_1' => array(
					'title' => 'Holidays 1',
					'description' => 'Update indexes for holidays',
					'sql' => array(
						"ALTER TABLE holiday DROP INDEX `date`",
						"ALTER TABLE holiday ADD INDEX Date (`date`) ",
						"ALTER TABLE holiday ADD INDEX Library (`libraryId`) ",
						"ALTER TABLE holiday ADD UNIQUE KEY LibraryDate(`date`, `libraryId`) ",
					),
				),
				'book_store' => array(
					'title' => 'Book store table',
					'description' => 'Create a table to store information about book stores.',
					'sql' => array(
						"CREATE TABLE IF NOT EXISTS book_store(" .
						"`id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of the book store', " .
						"`storeName` VARCHAR(100) NOT NULL COMMENT 'The name of the book store', " .
						"`link` VARCHAR(256) NOT NULL COMMENT 'The URL prefix for searching', " .
						"`linkText` VARCHAR(100) NOT NULL COMMENT 'The link text', " .
						"`image` VARCHAR(256) NOT NULL COMMENT 'The URL to the icon/image to display', " .
						"`resultRegEx` VARCHAR(100) NOT NULL COMMENT 'The regex used to check the search results', " .
						"PRIMARY KEY ( `id` )" .
						") ENGINE = InnoDB"
					),
				),
				'book_store_1' => array(
					'title' => 'Book store table update 1',
					'description' => 'Add a default column to determine if a book store should be used if a library does not override.',
					'sql' => array(
						"ALTER TABLE book_store ADD COLUMN `showByDefault` TINYINT NOT NULL DEFAULT 1 COMMENT 'Whether or not the book store should be used by default for al library systems.'",
						"ALTER TABLE book_store CHANGE `image` `image` VARCHAR(256) NULL COMMENT 'The URL to the icon/image to display'",
					),
				),
				'nearby_book_store' => array(
					'title' => 'Nearby book stores',
					'description' => 'Create a table to store book stores near a location.',
					'sql' => array(
						"CREATE TABLE IF NOT EXISTS nearby_book_store(" .
						"`id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of this association', " .
						"`libraryId` INT(11) NOT NULL COMMENT 'The id of the library', " .
						"`storeId` INT(11) NOT NULL COMMENT 'The id of the book store', " .
						"`weight` INT(11) NOT NULL DEFAULT 0 COMMENT 'The listing order of the book store', " .
						"KEY ( `libraryId`, `storeId` ), " .
						"PRIMARY KEY ( `id` )" .
						") ENGINE = InnoDB"
					),
				),

				'ptype' => array(
					'title' => 'P-Type',
					'description' => 'Build tables to store information related to P-Types.',
					'sql' => array(
						'CREATE TABLE IF NOT EXISTS ptype(
							id INT(11) NOT NULL AUTO_INCREMENT,
							pType INT(11) NOT NULL,
							maxHolds INT(11) NOT NULL DEFAULT 300,
							UNIQUE KEY (pType),
							PRIMARY KEY (id)
						)',
					),
				),

				'masquerade_ptypes' => array(
					'title' => 'P-Type setting for Masquerade Permissions',
					'description' => 'Build tables to store information related to P-Types.',
					'sql' => array(
						'ALTER TABLE `ptype` ADD COLUMN `masquerade` VARCHAR(45) NOT NULL DEFAULT \'none\' AFTER `maxHolds`;',
					),
				),

				'non_numeric_ptypes' => array(
					'title' => 'Allow P-Types to be stored as strings',
					'description' => 'This accomodates any ILS that does not use numeric P-Types',
					'sql' => array(
						'ALTER TABLE `ptype`  CHANGE COLUMN `pType` `pType` VARCHAR(20) NOT NULL ;'
					),
				),

				'analytics' => array(
					'title' => 'Analytics',
					'description' => 'Build tables to store analytics information.',
					'continueOnError' => true,
					'sql' => array(
						"CREATE TABLE IF NOT EXISTS analytics_session(" .
						"`id` INT(11) NOT NULL AUTO_INCREMENT, " .
						"`session_id` VARCHAR(128), " .
						"`sessionStartTime` INT(11) NOT NULL, " .
						"`lastRequestTime` INT(11) NOT NULL, " .
						"`country` VARCHAR(128) , " .
						"`city` VARCHAR(128), " .
						"`state` VARCHAR(128), " .
						"`latitude` FLOAT, " .
						"`longitude` FLOAT, " .
						"`ip` CHAR(16), " .
						"`theme` VARCHAR(128), " .
						"`mobile` TINYINT, " .
						"`device` VARCHAR(128), " .
						"`physicalLocation` VARCHAR(128), " .
						"`patronType` VARCHAR(50) NOT NULL DEFAULT 'logged out', " .
						"`homeLocationId` INT(11), " .
						"UNIQUE KEY ( `session_id` ), " .
						"PRIMARY KEY ( `id` )" .
						") ENGINE = InnoDB",
						"CREATE TABLE IF NOT EXISTS analytics_page_view(" .
						"`id` INT(11) NOT NULL AUTO_INCREMENT, " .
						"`sessionId` INT(11), " .
						"`pageStartTime` INT(11), " .
						"`pageEndTime` INT(11), " .
						"`module` VARCHAR(128), " .
						"`action` VARCHAR(128), " .
						"`method` VARCHAR(128), " .
						"`objectId` VARCHAR(128), " .
						"`fullUrl` VARCHAR(1024), " .
						"`language` VARCHAR(128), " .
						"INDEX ( `sessionId` ), " .
						"PRIMARY KEY ( `id` )" .
						") ENGINE = InnoDB",
						"CREATE TABLE IF NOT EXISTS analytics_search(" .
						"`id` INT(11) NOT NULL AUTO_INCREMENT, " .
						"`sessionId` INT(11), " .
						"`searchType` VARCHAR(30), " .
						"`scope` VARCHAR(50), " .
						"`lookfor` VARCHAR(256), " .
						"`isAdvanced` TINYINT, " .
						"`facetsApplied` TINYINT, " .
						"`numResults` INT(11), " .
						"INDEX ( `sessionId` ), " .
						"PRIMARY KEY ( `id` )" .
						") ENGINE = InnoDB",
						"CREATE TABLE IF NOT EXISTS analytics_event(" .
						"`id` INT(11) NOT NULL AUTO_INCREMENT, " .
						"`sessionId` INT(11), " .
						"`category` VARCHAR(100), " .
						"`action` VARCHAR(100), " .
						"`data` VARCHAR(256), " .
						"INDEX ( `sessionId` ), " .
						"INDEX ( `category` ), " .
						"INDEX ( `action` ), " .
						"PRIMARY KEY ( `id` )" .
						") ENGINE = InnoDB",
					),
				),

				'analytics_1' => array(
					'title' => 'Analytics Update 1',
					'description' => 'Add times to searches and events.',
					'continueOnError' => true,
					'sql' => array(
						'ALTER TABLE analytics_event ADD COLUMN eventTime INT(11)',
						'ALTER TABLE analytics_search ADD COLUMN searchTime INT(11)'
					),
				),

				'analytics_2' => array(
					'title' => 'Analytics Update 2',
					'description' => 'Adjust length of searchType Field.',
					'sql' => array(
						'ALTER TABLE analytics_search CHANGE COLUMN searchType searchType VARCHAR(50)'
					),
				),

				'analytics_3' => array(
					'title' => 'Analytics Update 3',
					'description' => 'Index filter information to improve loading seed for reports.',
					'sql' => array(
						'ALTER TABLE `analytics_session` ADD INDEX ( `country`)',
						'ALTER TABLE `analytics_session` ADD INDEX ( `city`)',
						'ALTER TABLE `analytics_session` ADD INDEX ( `state`)',
						'ALTER TABLE `analytics_session` ADD INDEX ( `theme`)',
						'ALTER TABLE `analytics_session` ADD INDEX ( `mobile`)',
						'ALTER TABLE `analytics_session` ADD INDEX ( `device`)',
						'ALTER TABLE `analytics_session` ADD INDEX ( `physicalLocation`)',
						'ALTER TABLE `analytics_session` ADD INDEX ( `patronType`)',
						'ALTER TABLE `analytics_session` ADD INDEX ( `homeLocationId`)',
					),
				),

				'analytics_4' => array(
					'title' => 'Analytics Update 4',
					'description' => 'Add additional data fields for events.',
					'sql' => array(
						'ALTER TABLE `analytics_event` ADD COLUMN data2 VARCHAR(256)',
						'ALTER TABLE `analytics_event` ADD COLUMN data3 VARCHAR(256)',
						'ALTER TABLE `analytics_event` ADD INDEX ( `data`)',
						'ALTER TABLE `analytics_event` ADD INDEX ( `data2`)',
						'ALTER TABLE `analytics_event` ADD INDEX ( `data3`)',
					),
				),

				'analytics_5' => array(
					'title' => 'Analytics Update 5',
					'description' => 'Update analytics search to make display of reports faster.',
					'sql' => array(
						'ALTER TABLE analytics_search ADD INDEX(lookfor)',
						'ALTER TABLE analytics_search ADD INDEX(numResults)',
						'ALTER TABLE analytics_search ADD INDEX(searchType)',
						'ALTER TABLE analytics_search ADD INDEX(scope)',
						'ALTER TABLE analytics_search ADD INDEX(facetsApplied)',
						'ALTER TABLE analytics_search ADD INDEX(isAdvanced)',
					),
				),

				'analytics_6' => array(
					'title' => 'Analytics Update 6',
					'description' => 'Update analytics make display of dashboard and other reports faster.',
					'sql' => array(
						'ALTER TABLE analytics_event ADD INDEX(eventTime)',
						'ALTER TABLE analytics_page_view ADD INDEX(pageStartTime)',
						'ALTER TABLE analytics_page_view ADD INDEX(pageEndTime)',
						'ALTER TABLE analytics_page_view ADD INDEX(module)',
						'ALTER TABLE analytics_page_view ADD INDEX(action)',
						'ALTER TABLE analytics_page_view ADD INDEX(method)',
						'ALTER TABLE analytics_page_view ADD INDEX(objectId)',
						'ALTER TABLE analytics_page_view ADD INDEX(language)',
						'ALTER TABLE analytics_search ADD INDEX(searchTime)',
					),
				),

				'analytics_7' => array(
					'title' => 'Analytics Update 7',
					'description' => 'Normalize Analytics Session for better performance.',
					'sql' => array(
						"CREATE TABLE IF NOT EXISTS analytics_country (
							`id` INT(11) NOT NULL AUTO_INCREMENT,
							`value` VARCHAR(128),
							UNIQUE KEY (`value`),
							PRIMARY KEY ( `id` )
						) ENGINE = MYISAM",
						"CREATE TABLE IF NOT EXISTS analytics_city (
							`id` INT(11) NOT NULL AUTO_INCREMENT,
							`value` VARCHAR(128),
							UNIQUE KEY (`value`),
							PRIMARY KEY ( `id` )
						) ENGINE = MYISAM",
						"CREATE TABLE IF NOT EXISTS analytics_state (
							`id` INT(11) NOT NULL AUTO_INCREMENT,
							`value` VARCHAR(128),
							UNIQUE KEY (`value`),
							PRIMARY KEY ( `id` )
						) ENGINE = MYISAM",
						"CREATE TABLE IF NOT EXISTS analytics_theme (
							`id` INT(11) NOT NULL AUTO_INCREMENT,
							`value` VARCHAR(128),
							UNIQUE KEY (`value`),
							PRIMARY KEY ( `id` )
						) ENGINE = MYISAM",
						"CREATE TABLE IF NOT EXISTS analytics_device (
							`id` INT(11) NOT NULL AUTO_INCREMENT,
							`value` VARCHAR(128),
							UNIQUE KEY (`value`),
							PRIMARY KEY ( `id` )
						) ENGINE = MYISAM",
						"CREATE TABLE IF NOT EXISTS analytics_physical_location (
							`id` INT(11) NOT NULL AUTO_INCREMENT,
							`value` VARCHAR(128),
							UNIQUE KEY (`value`),
							PRIMARY KEY ( `id` )
						) ENGINE = MYISAM",
						"CREATE TABLE IF NOT EXISTS analytics_patron_type (
							`id` INT(11) NOT NULL AUTO_INCREMENT,
							`value` VARCHAR(128),
							UNIQUE KEY (`value`),
							PRIMARY KEY ( `id` )
						) ENGINE = MYISAM",
						"CREATE TABLE IF NOT EXISTS analytics_session_2(
								`id` INT(11) NOT NULL AUTO_INCREMENT,
								`session_id` VARCHAR(128),
								`sessionStartTime` INT(11) NOT NULL,
								`lastRequestTime` INT(11) NOT NULL,
								`countryId` INT(11) ,
								`cityId` INT(11),
								`stateId` INT(11),
								`latitude` FLOAT,
								`longitude` FLOAT,
								`ip` CHAR(16),
								`themeId` INT(11),
								`mobile` TINYINT,
								`deviceId` INT(11),
								`physicalLocationId` INT(11),
								`patronTypeId` INT(11),
								`homeLocationId` INT(11),
								UNIQUE KEY ( `session_id` ),
								INDEX (sessionStartTime),
								INDEX (lastRequestTime),
								INDEX (countryId),
								INDEX (cityId),
								INDEX (stateId),
								INDEX (latitude),
								INDEX (longitude),
								INDEX (ip),
								INDEX (themeId),
								INDEX (mobile),
								INDEX (deviceId),
								INDEX (physicalLocationId),
								INDEX (patronTypeId),
								INDEX (homeLocationId),
								PRIMARY KEY ( `id` )
						) ENGINE = InnoDB",
						'TRUNCATE TABLE analytics_country',
						'INSERT INTO analytics_country (value) SELECT DISTINCT country FROM analytics_session',
						'TRUNCATE TABLE analytics_city',
						'INSERT INTO analytics_city (value) SELECT DISTINCT city FROM analytics_session',
						'TRUNCATE TABLE analytics_state',
						'INSERT INTO analytics_state (value) SELECT DISTINCT state FROM analytics_session',
						'TRUNCATE TABLE analytics_theme',
						'INSERT INTO analytics_theme (value) SELECT DISTINCT theme FROM analytics_session',
						'TRUNCATE TABLE analytics_device',
						'INSERT INTO analytics_device (value) SELECT DISTINCT device FROM analytics_session',
						'TRUNCATE TABLE analytics_physical_location',
						'INSERT INTO analytics_physical_location (value) SELECT DISTINCT physicalLocation FROM analytics_session',
						'TRUNCATE TABLE analytics_patron_type',
						'INSERT INTO analytics_patron_type (value) SELECT DISTINCT patronType FROM analytics_session',
						'TRUNCATE TABLE analytics_session_2',
						"INSERT INTO analytics_session_2 (
								session_id, sessionStartTime, lastRequestTime, countryId, cityId, stateId, latitude, longitude, ip, themeId, mobile, deviceId, physicalLocationId, patronTypeId, homeLocationId
							)
							SELECT session_id, sessionStartTime, lastRequestTime, analytics_country.id, analytics_city.id, analytics_state.id, latitude, longitude, ip, analytics_theme.id, mobile, analytics_device.id, analytics_physical_location.id, analytics_patron_type.id, homeLocationId
							FROM analytics_session
							LEFT JOIN analytics_country ON analytics_session.country = analytics_country.value
							LEFT JOIN analytics_city ON analytics_session.city = analytics_city.value
							LEFT JOIN analytics_state ON analytics_session.state = analytics_state.value
							LEFT JOIN analytics_theme ON analytics_session.theme = analytics_theme.value
							LEFT JOIN analytics_device ON analytics_session.device = analytics_device.value
							LEFT JOIN analytics_physical_location ON analytics_session.physicalLocation= analytics_physical_location.value
							LEFT JOIN analytics_patron_type ON analytics_session.patronType= analytics_patron_type.value",
						'RENAME TABLE analytics_session TO analytics_session_old',
						'RENAME TABLE analytics_session_2 TO analytics_session',
					),
				),

				'analytics_8' => array(
					'title' => 'Analytics Update 8',
					'description' => "Update analytics to store page load time so it doesn't have to be calculated.",
					'sql' => array(
						'ALTER TABLE analytics_page_view ADD COLUMN loadTime INT',
						'ALTER TABLE analytics_page_view ADD INDEX(loadTime)',
						'UPDATE analytics_page_view SET loadTime = pageEndTime - pageStartTime'
					),
				),

				'clear_analytics' => array(
						'title' => 'Clear Analytics',
						'description' => "Clear analytics data since it has grown out of control.",
						'sql' => array(
								'TRUNCATE TABLE analytics_page_view',
								'TRUNCATE TABLE analytics_event',
								'TRUNCATE TABLE analytics_search',
								'TRUNCATE TABLE analytics_session',
						),
				),

				'session_update_1' => array(
					'title' => 'Session Update 1',
					'description' => 'Add a field for whether or not the session was started with remember me on.',
					'sql' => array(
						"ALTER TABLE session ADD COLUMN `remember_me` TINYINT NOT NULL DEFAULT 0 COMMENT 'Whether or not the session was started with remember me on.'",
					),
				),

				'offline_holds' => array(
					'title' => 'Offline Holds',
					'description' => 'Stores information about holds that have been placed while the circulation system is offline',
					'sql' => array(
						"CREATE TABLE offline_hold (
							`id` INT(11) NOT NULL AUTO_INCREMENT,
							`timeEntered` INT(11) NOT NULL,
							`timeProcessed` INT(11) NULL,
							`bibId` VARCHAR(10) NOT NULL,
							`patronId` INT(11) NOT NULL,
							`patronBarcode` VARCHAR(20),
							`status` ENUM('Not Processed', 'Hold Succeeded', 'Hold Failed'),
							`notes` VARCHAR(512),
							INDEX(`timeEntered`),
							INDEX(`timeProcessed`),
							INDEX(`patronBarcode`),
							INDEX(`patronId`),
							INDEX(`bibId`),
							INDEX(`status`),
							PRIMARY KEY(`id`)
						) ENGINE = MYISAM"
					)
				),

				'offline_holds_update_1' => array(
					'title' => 'Offline Holds Update 1',
					'description' => 'Add the ability to store a name for patrons that have not logged in before.  Also used for conversions',
					'sql' => array(
						"ALTER TABLE `offline_hold` CHANGE `patronId` `patronId` INT( 11 ) NULL",
						"ALTER TABLE `offline_hold` ADD COLUMN `patronName` VARCHAR( 200 ) NULL",
					)
				),

					'offline_holds_update_2' => array(
							'title' => 'Offline Holds Update 2',
							'description' => 'Add the ability to store a name for patrons that have not logged in before.  Also used for conversions',
							'sql' => array(
									"ALTER TABLE `offline_hold` ADD COLUMN `itemId` VARCHAR( 20 ) NULL",
							)
					),

				'offline_circulation' => array(
					'title' => 'Offline Circulation',
					'description' => 'Stores information about circulation activities done while the circulation system was offline',
					'sql' => array(
						"CREATE TABLE offline_circulation (
							`id` INT(11) NOT NULL AUTO_INCREMENT,
							`timeEntered` INT(11) NOT NULL,
							`timeProcessed` INT(11) NULL,
							`itemBarcode` VARCHAR(20) NOT NULL,
							`patronBarcode` VARCHAR(20),
							`patronId` INT(11) NULL,
							`login` VARCHAR(50),
							`loginPassword` VARCHAR(50),
							`initials` VARCHAR(50),
							`initialsPassword` VARCHAR(50),
							`type` ENUM('Check In', 'Check Out'),
							`status` ENUM('Not Processed', 'Processing Succeeded', 'Processing Failed'),
							`notes` VARCHAR(512),
							INDEX(`timeEntered`),
							INDEX(`patronBarcode`),
							INDEX(`patronId`),
							INDEX(`itemBarcode`),
							INDEX(`login`),
							INDEX(`initials`),
							INDEX(`type`),
							INDEX(`status`),
							PRIMARY KEY(`id`)
						) ENGINE = MYISAM"
					)
				),

				'novelist_data' => array(
					'title' => 'Novelist Data',
					'description' => 'Stores basic information from Novelist for efficiency purposes.  We can\'t cache everything due to contract.',
					'sql' => array(
						"CREATE TABLE novelist_data (
							id INT(11) NOT NULL AUTO_INCREMENT,
							groupedRecordPermanentId VARCHAR(36),
							lastUpdate INT(11),
							hasNovelistData TINYINT(1),
							groupedRecordHasISBN TINYINT(1),
							primaryISBN VARCHAR(13),
							seriesTitle VARCHAR(255),
							seriesNote VARCHAR(255),
							volume VARCHAR(32),
							INDEX(`groupedRecordPermanentId`),
							PRIMARY KEY(`id`)
						) ENGINE = MYISAM",
					),
				),

				'syndetics_data' => array(
					'title' => 'Syndetics Data',
					'description' => 'Stores basic information from Syndetics for efficiency purposes.',
					'sql' => array(
						"CREATE TABLE syndetics_data (
							id INT(11) NOT NULL AUTO_INCREMENT,
							groupedRecordPermanentId VARCHAR(36),
							lastUpdate INT(11),
							hasSyndeticsData TINYINT(1),
							primaryIsbn VARCHAR(13),
							primaryUpc VARCHAR(25),
							description MEDIUMTEXT,
							tableOfContents MEDIUMTEXT,
							excerpt MEDIUMTEXT,
							INDEX(`groupedRecordPermanentId`),
							PRIMARY KEY(`id`)
						) ENGINE = MYISAM",
					),
				),


				'ils_marc_checksums' => array(
					'title' => 'ILS MARC Checksums',
					'description' => 'Add a table to store checksums of MARC records stored in the ILS so we can determine if the record needs to be updated during grouping.',
					'sql' => array(
						"CREATE TABLE IF NOT EXISTS ils_marc_checksums (
							id INT(11) NOT NULL AUTO_INCREMENT,
							ilsId VARCHAR(20) NOT NULL,
							checksum BIGINT(20) UNSIGNED NOT NULL,
							PRIMARY KEY (id),
							UNIQUE (ilsId)
						) ENGINE=MyISAM  DEFAULT CHARSET=utf8",
					),
				),

				'ils_marc_checksum_first_detected' => array(
					'title' => 'ILS MARC Checksums First Detected',
					'description' => 'Update ILS Marc Checksums to include when the record was first detected.',
					'sql' => array(
						"ALTER TABLE ils_marc_checksums ADD dateFirstDetected BIGINT UNSIGNED NULL",
					),
				),

				'ils_marc_checksum_first_detected_signed' => array(
					'title' => 'ILS MARC Checksums First Detected',
					'description' => 'Update ILS Marc Checksums to make when the record was first detected a signed value.',
					'sql' => array(
						"ALTER TABLE ils_marc_checksums CHANGE dateFirstDetected dateFirstDetected BIGINT SIGNED NULL",
					),
				),

				'ils_marc_checksum_source' => array(
					'title' => 'ILS MARC Checksum Source',
					'description' => 'Add a source to the ILS MARC Checksums table to allow for ',
					'sql' => array(
						"ALTER TABLE ils_marc_checksums ADD source VARCHAR(50) NOT NULL DEFAULT 'ils'",
						"ALTER TABLE ils_marc_checksums ADD UNIQUE (`source`, `ilsId`)",
					),
				),

				'work_level_ratings' => array(
					'title' => 'Work Level Ratings',
					'description' => 'Stores user ratings at the work level rather than the individual record.',
					'sql' => array(
						"CREATE TABLE user_work_review (
							id INT(11) NOT NULL AUTO_INCREMENT,
							groupedRecordPermanentId VARCHAR(36),
							userId INT(11),
							rating TINYINT(1),
							review MEDIUMTEXT,
							dateRated INT(11),
							INDEX(`groupedRecordPermanentId`),
							INDEX(`userId`),
							PRIMARY KEY(`id`)
						) ENGINE = MYISAM",
					),
				),

				'work_level_tagging' => array(
					'title' => 'Work Level Tagging',
					'description' => 'Stores tags at the work level rather than the individual record.',
					'sql' => array(
						"CREATE TABLE user_tags (
							id INT(11) NOT NULL AUTO_INCREMENT,
							groupedRecordPermanentId VARCHAR(36),
							userId INT(11),
							tag VARCHAR(50),
							dateTagged INT(11),
							INDEX(`groupedRecordPermanentId`),
							INDEX(`userId`),
							PRIMARY KEY(`id`)
						) ENGINE = MYISAM",
					),
				),

				'user_list_entry' => array(
					'title' => 'User List Entry (Grouped Work)',
					'description' => 'Add grouped works to lists rather than resources.',
					'sql' => array(
						"CREATE TABLE user_list_entry (
							id INT(11) NOT NULL AUTO_INCREMENT,
							groupedWorkPermanentId VARCHAR(36),
							listId INT(11),
							notes MEDIUMTEXT,
							dateAdded INT(11),
							weight INT(11),
							INDEX(`groupedWorkPermanentId`),
							INDEX(`listId`),
							PRIMARY KEY(`id`)
						) ENGINE = MYISAM",
					),
				),

				'user_list_indexing' => array(
					'title' => 'Update User List to make indexing easier',
					'description' => 'Add date updated and deleted to the table so we can easily do partial indexes of the data.',
					'sql' => array(
						"ALTER TABLE user_list ADD dateUpdated INT(11)",
						"ALTER TABLE user_list ADD deleted TINYINT(1) DEFAULT 0",
						"ALTER TABLE user_list DROP created",
						"ALTER TABLE user_list ADD created INT(11)",
					)
				),

				'user_list_sorting' => array(
					'title' => 'Store a default sorting setting for a user list',
					'description' => 'Allows user to set the way in which their list will be sorted by default.',
					'sql' => array(
						"ALTER TABLE `user_list` ADD `defaultSort` VARCHAR(20)",
					)
				),

				'browse_categories' => array(
					'title' => 'Browse Categories',
					'description' => 'Setup Browse Category Table',
					'sql' => array(
						"CREATE TABLE browse_category (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							textId VARCHAR(60) NOT NULL DEFAULT -1,
							userId INT(11),
							sharing ENUM('private', 'location', 'library', 'everyone') DEFAULT 'everyone',
							label VARCHAR(50) NOT NULL,
							description MEDIUMTEXT,
							catalogScoping ENUM('unscoped', 'library', 'location'),
							defaultFilter TEXT,
							defaultSort ENUM('relevance', 'popularity', 'newest_to_oldest', 'oldest_to_newest', 'author', 'title', 'user_rating'),
							UNIQUE (textId)
						) ENGINE = MYISAM",
					),
				),

				'browse_categories_search_term_and_stats' => array(
					'title' => 'Browse Categories Search Term and Stats',
					'description' => 'Add a search term and statistics to browse categories',
					'sql' => array(
						"ALTER TABLE browse_category ADD searchTerm VARCHAR(100) NOT NULL DEFAULT ''",
						"ALTER TABLE browse_category ADD numTimesShown MEDIUMINT NOT NULL DEFAULT 0",
						"ALTER TABLE browse_category ADD numTitlesClickedOn MEDIUMINT NOT NULL DEFAULT 0",
					),
				),

				'browse_categories_search_term_length' => array(
					'title' => 'Browse Category Search Term Length',
					'description' => 'Increase the length of the search term field',
					'sql' => array(
						"ALTER TABLE browse_category CHANGE searchTerm searchTerm VARCHAR(300) NOT NULL DEFAULT ''",
					),
				),

				'browse_categories_search_term_length' => array(
					'title' => 'Browse Category Search Term Length',
					'description' => 'Increase the length of the search term field',
					'sql' => array(
						"ALTER TABLE browse_category CHANGE searchTerm searchTerm VARCHAR(500) NOT NULL DEFAULT ''",
					),
				),

				'browse_categories_lists' => array(
					'title' => 'Browse Categories from Lists',
					'description' => 'Add a the ability to define a browse category from a list',
					'sql' => array(
						"ALTER TABLE browse_category ADD sourceListId MEDIUMINT NULL DEFAULT NULL",
					),
				),

				'sub-browse_categories' => array(
					'title' => 'Enable Browse Sub-Categories',
					'description' => 'Add a the ability to define a browse category from a list',
					'sql' => array(
							"CREATE TABLE `browse_category_subcategories` (
							  `id` int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
							  `browseCategoryId` int(11) NOT NULL,
							  `subCategoryId` int(11) NOT NULL,
							  `weight` SMALLINT(2) UNSIGNED NOT NULL DEFAULT '0',
							  UNIQUE (`subCategoryId`,`browseCategoryId`)
							) ENGINE=MyISAM DEFAULT CHARSET=utf8"
					),
				),

				'localized_browse_categories' => array(
					'title' => 'Localized Browse Categories',
					'description' => 'Setup Localized Browse Category Tables',
					'sql' => array(
						"CREATE TABLE browse_category_library (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							libraryId INT(11) NOT NULL,
							browseCategoryTextId VARCHAR(60) NOT NULL DEFAULT -1,
							weight INT NOT NULL DEFAULT '0',
							UNIQUE (libraryId, browseCategoryTextId)
						) ENGINE = MYISAM",
						"CREATE TABLE browse_category_location (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							locationId INT(11) NOT NULL,
							browseCategoryTextId VARCHAR(60) NOT NULL DEFAULT -1,
							weight INT NOT NULL DEFAULT '0',
							UNIQUE (locationId, browseCategoryTextId)
						) ENGINE = MYISAM",
					),
				),

				'remove_old_resource_tables' => array(
					'title' => 'Remove old Resource Tables',
					'description' => 'Remove old tables that were used for storing information based on resource',
					'sql' => array(
						"DROP TABLE IF EXISTS comments",
						"DROP TABLE IF EXISTS resource_tags",
						"DROP TABLE IF EXISTS user_resource",
						"DROP TABLE IF EXISTS resource",
					),
				),

				'remove_browse_tables' => array(
					'title' => 'Remove old Browse Tables',
					'description' => 'Remove old tables that were used for alphabetic browsing',
					'sql' => array(
						"dropBrowseTables",
					),
				),

				'remove_unused_options' => array(
					'title' => 'Remove Unused Library and Location Options',
					'description' => 'Remove unused options for library and location tables',
					'sql' => array(
						//"ALTER TABLE library DROP accountingUnit",
						//"ALTER TABLE library DROP makeOrderRecordsAvailableToOtherLibraries",
						"ALTER TABLE library DROP searchesFile",
						"ALTER TABLE library DROP suggestAPurchase",
						"ALTER TABLE library DROP showAmazonReviews",
						"ALTER TABLE library DROP linkToAmazon",
						"ALTER TABLE library DROP illLink",
						"ALTER TABLE library DROP askALibrarianLink",
						"ALTER TABLE library DROP boopsieLink",
						"ALTER TABLE library DROP tabbedDetails",
						"ALTER TABLE library DROP showSeriesAsTab",
						"ALTER TABLE library DROP repeatInAmazon",
						"ALTER TABLE library DROP enableBookCart",
						"ALTER TABLE library DROP enableAlphaBrowse",
						"ALTER TABLE library DROP homePageWidgetId",
						"ALTER TABLE library DROP searchGroupedRecords",
						"ALTER TABLE location DROP showAmazonReviews",
						"ALTER TABLE location DROP footerTemplate",
						"ALTER TABLE location DROP homePageWidgetId",
					),
				),

				'authentication_profiles' => array(
					'title' => 'Setup Authentication Profiles',
					'description' => 'Setup authentication profiles to store information about how to authenticate',
					'sql' => array(
						"CREATE TABLE IF NOT EXISTS `account_profiles` (
						  `id` int(11) NOT NULL AUTO_INCREMENT,
						  `name` varchar(50) NOT NULL DEFAULT 'ils',
						  `driver` varchar(50) NOT NULL,
						  `loginConfiguration` enum('barcode_pin','name_barcode') NOT NULL,
						  `authenticationMethod` enum('ils','sip2','db','ldap') NOT NULL DEFAULT 'ils',
						  `vendorOpacUrl` varchar(100) NOT NULL,
						  `patronApiUrl` varchar(100) NOT NULL,
						  `recordSource` varchar(50) NOT NULL,
						  PRIMARY KEY (`id`),
						  UNIQUE KEY `name` (`name`)
						) ENGINE=InnoDB  DEFAULT CHARSET=utf8",
					)
				),

				'account_profiles_1' => array(
					'title' => 'Update Account Profiles 1',
					'description' => 'Update Account Profiles with additional data to make integration easier',
					'continueOnError' => true,
					'sql' => array(
						"ALTER TABLE `account_profiles` ADD `vendorOpacUrl` varchar(100) NOT NULL",
						"ALTER TABLE `account_profiles` ADD `patronApiUrl` varchar(100) NOT NULL",
						"ALTER TABLE `account_profiles` ADD `recordSource` varchar(50) NOT NULL",
						"ALTER TABLE `account_profiles` ADD `weight` int(11) NOT NULL",
					)
				),

					'archive_private_collections' => array(
							'title' => 'Archive Private Collections',
							'description' => 'Create a table to store information about collections that should be private to the owning library',
							'continueOnError' => true,
							'sql' => array(
									"CREATE TABLE IF NOT EXISTS archive_private_collections (
									  `id` int(11) NOT NULL AUTO_INCREMENT,
									  privateCollections MEDIUMTEXT,
									  PRIMARY KEY (`id`)
									) ENGINE=InnoDB  DEFAULT CHARSET=utf8",
							)
					),

					'archive_subjects' => array(
							'title' => 'Archive Subjects',
							'description' => 'Create a table to store information about what subjects should be ignored and restricted',
							'continueOnError' => true,
							'sql' => array(
									"CREATE TABLE IF NOT EXISTS archive_subjects (
									  `id` int(11) NOT NULL AUTO_INCREMENT,
									  subjectsToIgnore MEDIUMTEXT,
									  subjectsToRestrict MEDIUMTEXT,
									  PRIMARY KEY (`id`)
									) ENGINE=InnoDB  DEFAULT CHARSET=utf8",
							)
					),

					'archive_requests' => array(
							'title' => 'Archive Requests',
							'description' => 'Create a table to store information about the requests for copies of archive information',
							'continueOnError' => true,
							'sql' => array(
									"CREATE TABLE IF NOT EXISTS archive_requests (
									  `id` int(11) NOT NULL AUTO_INCREMENT,
									  name VARCHAR(100) NOT NULL,
									  address VARCHAR(200),
									  address2 VARCHAR(200),
									  city VARCHAR(200),
									  state VARCHAR(200),
									  zip VARCHAR(12),
									  country VARCHAR(50),
									  phone VARCHAR(20),
									  alternatePhone VARCHAR(20),
									  email VARCHAR(100),
									  format MEDIUMTEXT,
									  purpose MEDIUMTEXT,
									  pid VARCHAR(50),
									  dateRequested INT(11),
									  PRIMARY KEY (`id`),
									  INDEX(`pid`),
									  INDEX(`name`)
									) ENGINE=InnoDB  DEFAULT CHARSET=utf8",
							)
					),

					'claim_authorship_requests' => array(
							'title' => 'Claim Authorship Requests',
							'description' => 'Create a table to store information about the people who are claiming authorship of archive information',
							'continueOnError' => true,
							'sql' => array(
									"CREATE TABLE IF NOT EXISTS claim_authorship_requests (
									  `id` int(11) NOT NULL AUTO_INCREMENT,
									  name VARCHAR(100) NOT NULL,
									  phone VARCHAR(20),
									  email VARCHAR(100),
									  message MEDIUMTEXT,
									  pid VARCHAR(50),
									  dateRequested INT(11),
									  PRIMARY KEY (`id`),
									  INDEX(`pid`),
									  INDEX(`name`)
									) ENGINE=InnoDB  DEFAULT CHARSET=utf8",
							)
					),

					'add_search_source_to_saved_searches' => array(
							'title' => 'Store the Search Source with saved searches',
							'description' => 'Add column to store the source for a search in the search table',
							'continueOnError' => true,
							'sql' => array(
									"ALTER TABLE `search` 
									ADD COLUMN `searchSource` VARCHAR(30) NOT NULL DEFAULT 'local' AFTER `search_object`;",
							)
					),

					'record_grouping_log' => array(
							'title' => 'Record Grouping Log',
							'description' => 'Create Log for record grouping',
							'continueOnError' => false,
							'sql' => array(
									"CREATE TABLE IF NOT EXISTS record_grouping_log(
									`id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of log', 
									`startTime` INT(11) NOT NULL COMMENT 'The timestamp when the run started', 
									`endTime` INT(11) NULL COMMENT 'The timestamp when the run ended', 
									`lastUpdate` INT(11) NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)', 
									`notes` TEXT COMMENT 'Additional information about the run includes stats per source', 
									PRIMARY KEY ( `id` )
									) ENGINE = MYISAM;",
							)
					),
			)
		);
	}

	public function dropBrowseTables(&$update) {
		$this->runSQLStatement($update, 'DROP TABLE IF EXISTS title_browse');
		$this->runSQLStatement($update, 'DROP TABLE IF EXISTS title_browse_metadata');
		$this->runSQLStatement($update, 'DROP TABLE IF EXISTS title_browse_scoped_results_global');
		$this->runSQLStatement($update, 'DROP TABLE IF EXISTS author_browse');
		$this->runSQLStatement($update, 'DROP TABLE IF EXISTS author_browse_metadata');
		$this->runSQLStatement($update, 'DROP TABLE IF EXISTS author_browse_scoped_results_global');
		$this->runSQLStatement($update, 'DROP TABLE IF EXISTS subject_browse');
		$this->runSQLStatement($update, 'DROP TABLE IF EXISTS subject_browse_metadata');
		$this->runSQLStatement($update, 'DROP TABLE IF EXISTS subject_browse_scoped_results_global');
		$this->runSQLStatement($update, 'DROP TABLE IF EXISTS callnumber_browse');
		$this->runSQLStatement($update, 'DROP TABLE IF EXISTS callnumber_browse_metadata');
		$this->runSQLStatement($update, 'DROP TABLE IF EXISTS callnumber_browse_scoped_results_global');

		$library = new Library();
		$library->find();
		while ($library->fetch()) {
			$this->runSQLStatement($update, "DROP TABLE IF EXISTS title_browse_scoped_results_library_{$library->subdomain}");
			$this->runSQLStatement($update, "DROP TABLE IF EXISTS author_browse_scoped_results_library_{$library->subdomain}");
			$this->runSQLStatement($update, "DROP TABLE IF EXISTS subject_browse_scoped_results_library_{$library->subdomain}");
			$this->runSQLStatement($update, "DROP TABLE IF EXISTS callnumber_browse_scoped_results_library_{$library->subdomain}");
		}
	}

	public function addTableListWidgetListsLinks() {
		set_time_limit(120);
		$sql = 'CREATE TABLE IF NOT EXISTS `list_widget_lists_links`( ' .
			'`id` int(11) NOT NULL AUTO_INCREMENT, ' .
			'`listWidgetListsId` int(11) NOT NULL, ' .
			'`name` varchar(50) NOT NULL, ' .
			'`link` text NOT NULL, ' .
			'`weight` int(3) NOT NULL DEFAULT \'0\',' .
			'PRIMARY KEY (`id`) ' .
			') ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';
		mysql_query($sql);
	}


	private function checkWhichUpdatesHaveRun($availableUpdates) {
		foreach ($availableUpdates as $key => $update) {
			$update['alreadyRun'] = false;
			$result = mysql_query("SELECT * from db_update where update_key = '" . mysql_escape_string($key) . "'");
			$numRows = mysql_num_rows($result);
			if ($numRows != false) {
				$update['alreadyRun'] = true;
			}
			$availableUpdates[$key] = $update;
		}
		return $availableUpdates;
	}

	private function markUpdateAsRun($update_key) {
		$result = mysql_query("SELECT * from db_update where update_key = '" . mysql_escape_string($update_key) . "'");
		if (mysql_num_rows($result) != false) {
			//Update the existing value
			mysql_query("UPDATE db_update SET date_run = CURRENT_TIMESTAMP WHERE update_key = '" . mysql_escape_string($update_key) . "'");
		} else {
			mysql_query("INSERT INTO db_update (update_key) VALUES ('" . mysql_escape_string($update_key) . "')");
		}
	}

	function getAllowableRoles() {
		return array('userAdmin', 'opacAdmin');
	}

	private function createUpdatesTable() {
		//Check to see if the updates table exists
		$result = mysql_query("SHOW TABLES");
		$tableFound = false;
		if ($result) {
			while ($row = mysql_fetch_array($result, MYSQL_NUM)) {
				if ($row[0] == 'db_update') {
					$tableFound = true;
					break;
				}
			}
		}
		if (!$tableFound) {
			//Create the table to mark which updates have been run.
			mysql_query("CREATE TABLE db_update (" .
				"update_key VARCHAR( 100 ) NOT NULL PRIMARY KEY ," .
				"date_run TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP" .
				") ENGINE = InnoDB");
		}
	}

	function createScopingTables(&$update) {
		//Create global scoping tables
		$library = new Library();
		$library->find();
		while ($library->fetch()) {
			$this->runSQLStatement($update,
				"CREATE TABLE `title_browse_scoped_results_library_{$library->subdomain}` (
					`browseValueId` INT( 11 ) NOT NULL ,
					`record` VARCHAR( 50 ) NOT NULL ,
					PRIMARY KEY ( `browseValueId` , `record` ) ,
					INDEX ( `browseValueId` )
				) ENGINE = MYISAM");
			$this->runSQLStatement($update,
				"CREATE TABLE `author_browse_scoped_results_library_{$library->subdomain}` (
					`browseValueId` INT( 11 ) NOT NULL ,
					`record` VARCHAR( 50 ) NOT NULL ,
					PRIMARY KEY ( `browseValueId` , `record` ) ,
					INDEX ( `browseValueId` )
				) ENGINE = MYISAM");
			$this->runSQLStatement($update,
				"CREATE TABLE `subject_browse_scoped_results_library_{$library->subdomain}` (
					`browseValueId` INT( 11 ) NOT NULL ,
					`record` VARCHAR( 50 ) NOT NULL ,
					PRIMARY KEY ( `browseValueId` , `record` ) ,
					INDEX ( `browseValueId` )
				) ENGINE = MYISAM");
			$this->runSQLStatement($update,
				"CREATE TABLE `callnumber_browse_scoped_results_library_{$library->subdomain}` (
					`browseValueId` INT( 11 ) NOT NULL ,
					`record` VARCHAR( 50 ) NOT NULL ,
					PRIMARY KEY ( `browseValueId` , `record` ) ,
					INDEX ( `browseValueId` )
				) ENGINE = MYISAM");
			//Truncate old data
			$this->runSQLStatement($update, "TRUNCATE TABLE `title_browse_scoped_results_library_{$library->subdomain}`");
			$this->runSQLStatement($update, "TRUNCATE TABLE `author_browse_scoped_results_library_{$library->subdomain}`");
			$this->runSQLStatement($update, "TRUNCATE TABLE `subject_browse_scoped_results_library_{$library->subdomain}`");
			$this->runSQLStatement($update, "TRUNCATE TABLE `callnumber_browse_scoped_results_library_{$library->subdomain}`");
			$this->runSQLStatement($update,
				"INSERT INTO title_browse_scoped_results_library_" . $library->subdomain . " (`browseValueId`, record)
					SELECT title_browse_scoped_results.browseValueId, title_browse_scoped_results.record
					FROM title_browse_scoped_results
					WHERE scope = 1 and scopeId = {$library->libraryId};");
			$this->runSQLStatement($update,
				"INSERT INTO author_browse_scoped_results_library_" . $library->subdomain . " (`browseValueId`, record)
					SELECT author_browse_scoped_results.browseValueId, author_browse_scoped_results.record
					FROM author_browse_scoped_results
					WHERE scope = 1 and scopeId = {$library->libraryId};");
			$this->runSQLStatement($update,
				"INSERT INTO subject_browse_scoped_results_library_" . $library->subdomain . " (`browseValueId`, record)
					SELECT subject_browse_scoped_results.browseValueId, subject_browse_scoped_results.record
					FROM subject_browse_scoped_results
					WHERE scope = 1 and scopeId = {$library->libraryId};");
			$this->runSQLStatement($update,
				"INSERT INTO callnumber_browse_scoped_results_library_" . $library->subdomain . " (`browseValueId`, record)
					SELECT callnumber_browse_scoped_results.browseValueId, callnumber_browse_scoped_results.record
					FROM callnumber_browse_scoped_results
					WHERE scope = 1 and scopeId = {$library->libraryId};");
		}
	}

	function setScopingTableEngine(&$update) {
		//$this->runSQLStatement($update, "ALTER TABLE `title_browse_scoped_results_global` ENGINE = InnoDB");
		$this->runSQLStatement($update, "ALTER TABLE `title_browse_scoped_results_global` ADD INDEX ( `record` )");
		//$this->runSQLStatement($update, "ALTER TABLE `author_browse_scoped_results_global` ENGINE = InnoDB");
		$this->runSQLStatement($update, "ALTER TABLE `author_browse_scoped_results_global` ADD INDEX ( `record` )");
		//$this->runSQLStatement($update, "ALTER TABLE `subject_browse_scoped_results_global` ENGINE = InnoDB");
		$this->runSQLStatement($update, "ALTER TABLE `subject_browse_scoped_results_global` ADD INDEX ( `record` )");
		//$this->runSQLStatement($update, "ALTER TABLE `callnumber_browse_scoped_results_global` ENGINE = InnoDB");
		$this->runSQLStatement($update, "ALTER TABLE `callnumber_browse_scoped_results_global` ADD INDEX ( `record` )");

		$library = new Library();
		$library->find();
		while ($library->fetch()) {
			//$this->runSQLStatement($update, "ALTER TABLE `title_browse_scoped_results_library_{$library->subdomain}` ENGINE = InnoDB");
			$this->runSQLStatement($update, "ALTER TABLE `title_browse_scoped_results_library_" . $library->subdomain . "` ADD INDEX ( `record` )");
			//$this->runSQLStatement($update, "ALTER TABLE `author_browse_scoped_results_library_{$library->subdomain}` ENGINE = InnoDB");
			$this->runSQLStatement($update, "ALTER TABLE `author_browse_scoped_results_library_" . $library->subdomain . "` ADD INDEX ( `record` )");
			//$this->runSQLStatement($update, "ALTER TABLE `subject_browse_scoped_results_library_{$library->subdomain}` ENGINE = InnoDB");
			$this->runSQLStatement($update, "ALTER TABLE `subject_browse_scoped_results_library_" . $library->subdomain . "` ADD INDEX ( `record` )");
			//$this->runSQLStatement($update, "ALTER TABLE `callnumber_browse_scoped_results_library_{$library->subdomain}` ENGINE = InnoDB");
			$this->runSQLStatement($update, "ALTER TABLE `callnumber_browse_scoped_results_library_" . $library->subdomain . "` ADD INDEX ( `record` )");

		}
	}

	function setScopingTableEngine2(&$update) {
		$this->runSQLStatement($update, "TRUNCATE TABLE title_browse_scoped_results_global");
		$this->runSQLStatement($update, "ALTER TABLE `title_browse_scoped_results_global` ENGINE = InnoDB");
		$this->runSQLStatement($update, "TRUNCATE TABLE author_browse_scoped_results_global");
		$this->runSQLStatement($update, "ALTER TABLE `author_browse_scoped_results_global` ENGINE = InnoDB");
		$this->runSQLStatement($update, "TRUNCATE TABLE subject_browse_scoped_results_global");
		$this->runSQLStatement($update, "ALTER TABLE `subject_browse_scoped_results_global` ENGINE = InnoDB");
		$this->runSQLStatement($update, "TRUNCATE TABLE callnumber_browse_scoped_results_global");
		$this->runSQLStatement($update, "ALTER TABLE `callnumber_browse_scoped_results_global` ENGINE = InnoDB");

		$library = new Library();
		$library->find();
		while ($library->fetch()) {
			$this->runSQLStatement($update, "TRUNCATE TABLE `title_browse_scoped_results_library_{$library->subdomain}`");
			$this->runSQLStatement($update, "ALTER TABLE `title_browse_scoped_results_library_{$library->subdomain}` ENGINE = InnoDB");
			$this->runSQLStatement($update, "TRUNCATE TABLE `author_browse_scoped_results_library_{$library->subdomain}`");
			$this->runSQLStatement($update, "ALTER TABLE `author_browse_scoped_results_library_{$library->subdomain}` ENGINE = InnoDB");
			$this->runSQLStatement($update, "TRUNCATE TABLE `subject_browse_scoped_results_library_{$library->subdomain}`");
			$this->runSQLStatement($update, "ALTER TABLE `subject_browse_scoped_results_library_{$library->subdomain}` ENGINE = InnoDB");
			$this->runSQLStatement($update, "TRUNCATE TABLE `callnumber_browse_scoped_results_library_{$library->subdomain}`");
			$this->runSQLStatement($update, "ALTER TABLE `callnumber_browse_scoped_results_library_{$library->subdomain}` ENGINE = InnoDB");
		}
	}

	function removeScopingTableIndex(&$update) {
		$this->runSQLStatement($update, "TRUNCATE TABLE title_browse_scoped_results_global");
		$this->runSQLStatement($update, "ALTER TABLE `title_browse_scoped_results_global` DROP INDEX `record`");
		$this->runSQLStatement($update, "ALTER TABLE `title_browse_scoped_results_global` ENGINE = MYISAM");
		$this->runSQLStatement($update, "TRUNCATE TABLE author_browse_scoped_results_global");
		$this->runSQLStatement($update, "ALTER TABLE `author_browse_scoped_results_global` DROP INDEX `record`");
		$this->runSQLStatement($update, "ALTER TABLE `author_browse_scoped_results_global` ENGINE = MYISAM");
		$this->runSQLStatement($update, "TRUNCATE TABLE subject_browse_scoped_results_global");
		$this->runSQLStatement($update, "ALTER TABLE `subject_browse_scoped_results_global` DROP INDEX `record`");
		$this->runSQLStatement($update, "ALTER TABLE `subject_browse_scoped_results_global` ENGINE = MYISAM");
		$this->runSQLStatement($update, "TRUNCATE TABLE callnumber_browse_scoped_results_global");
		$this->runSQLStatement($update, "ALTER TABLE `callnumber_browse_scoped_results_global` DROP INDEX `record`");
		$this->runSQLStatement($update, "ALTER TABLE `callnumber_browse_scoped_results_global` ENGINE = MYISAM");

		$library = new Library();
		$library->find();
		while ($library->fetch()) {
			$this->runSQLStatement($update, "TRUNCATE TABLE `title_browse_scoped_results_library_{$library->subdomain}`");
			$this->runSQLStatement($update, "ALTER TABLE `title_browse_scoped_results_library_{$library->subdomain}` DROP INDEX `record`");
			$this->runSQLStatement($update, "ALTER TABLE `title_browse_scoped_results_library_{$library->subdomain}` ENGINE = MYISAM");
			$this->runSQLStatement($update, "TRUNCATE TABLE `author_browse_scoped_results_library_{$library->subdomain}`");
			$this->runSQLStatement($update, "ALTER TABLE `author_browse_scoped_results_library_{$library->subdomain}` DROP INDEX `record`");
			$this->runSQLStatement($update, "ALTER TABLE `author_browse_scoped_results_library_{$library->subdomain}` ENGINE = MYISAM");
			$this->runSQLStatement($update, "TRUNCATE TABLE `subject_browse_scoped_results_library_{$library->subdomain}`");
			$this->runSQLStatement($update, "ALTER TABLE `subject_browse_scoped_results_library_{$library->subdomain}` DROP INDEX `record`");
			$this->runSQLStatement($update, "ALTER TABLE `subject_browse_scoped_results_library_{$library->subdomain}` ENGINE = MYISAM");
			$this->runSQLStatement($update, "TRUNCATE TABLE `callnumber_browse_scoped_results_library_{$library->subdomain}`");
			$this->runSQLStatement($update, "ALTER TABLE `callnumber_browse_scoped_results_library_{$library->subdomain}` DROP INDEX `record`");
			$this->runSQLStatement($update, "ALTER TABLE `callnumber_browse_scoped_results_library_{$library->subdomain}` ENGINE = MYISAM");
		}
	}

	function runSQLStatement(&$update, $sql) {
		set_time_limit(500);
		$result = mysql_query($sql);
		$updateOk = true;
		if ($result == 0 || $result == false) {
			if (isset($update['continueOnError']) && $update['continueOnError']) {
				if (!isset($update['status'])) {
					$update['status'] = '';
				}
				$update['status'] .= 'Warning: ' . mysql_error() . "<br/>";
			} else {
				$update['status'] = 'Update failed ' . mysql_error();
				$updateOk = false;
			}
		} else {
			if (!isset($update['status'])) {
				$update['status'] = 'Update succeeded';
			}
		}
		return $updateOk;
	}

	function createDefaultIpRanges() {
		require_once ROOT_DIR . '/Drivers/marmot_inc/ipcalc.php';
		require_once ROOT_DIR . '/Drivers/marmot_inc/subnet.php';
		$subnet = new subnet();
		$subnet->find();
		while ($subnet->fetch()) {
			$subnet->update();
		}
	}

	/**
	 * @param $resource
	 * @return mixed
	 */
	public function getGroupedWorkForResource($resource) {
//Get the identifier for the resource
		if ($resource->source == 'VuFind') {
			$primaryIdentifier = $resource->record_id;
			return $primaryIdentifier;
		}
	}

	function updateDueDateFormat(){
		global $configArray;
		if (isset($configArray['Reindex']['dueDateFormat'])){
			$ilsIndexingProfile = new IndexingProfile();
			$ilsIndexingProfile->name = 'ils';
			if ($ilsIndexingProfile->find(true)){
				$ilsIndexingProfile->dueDateFormat = $configArray['Reindex']['dueDateFormat'];
				$ilsIndexingProfile->update();
			}

			$ilsIndexingProfile = new IndexingProfile();
			$ilsIndexingProfile->name = 'millennium';
			if ($ilsIndexingProfile->find(true)){
				$ilsIndexingProfile->dueDateFormat = $configArray['Reindex']['dueDateFormat'];
				$ilsIndexingProfile->update();
			}
		}
	}

	function updateShowSeriesInMainDetails(){
		$library = new Library();
		$library->find();
		while ($library->fetch()){
			if (!count($library->showInMainDetails) == 0){
				$library->showInMainDetails[] = 'showSeries';
				$library->update();
			}
		}
	}
}
