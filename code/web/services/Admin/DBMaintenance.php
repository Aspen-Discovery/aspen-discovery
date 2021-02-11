<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

/**
 * Provides a method of running SQL updates to the database.
 * Shows a list of updates that are available with a description of the
 */
class Admin_DBMaintenance extends Admin_Admin
{
	function launch()
	{
		global $interface;

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
						} elseif (function_exists($sql)) {
							$sql($update);
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

	private function getSQLUpdates()
	{
		global $configArray;

		require_once ROOT_DIR . '/sys/DBMaintenance/library_location_updates.php';
		$library_location_updates = getLibraryLocationUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/grouped_work_updates.php';
		$grouped_work_updates = getGroupedWorkUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/user_updates.php';
		$user_updates = getUserUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/genealogy_updates.php';
		$genealogy_updates = getGenealogyUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/browse_updates.php';
		$browse_updates = getBrowseUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/collection_spotlight_updates.php';
		$collection_spotlight_updates = getCollectionSpotlightUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/indexing_updates.php';
		$indexing_updates = getIndexingUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/islandora_updates.php';
		$islandora_updates = getIslandoraUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/hoopla_updates.php';
		$hoopla_updates = getHooplaUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/rbdigital_updates.php';
		$rbdigital_updates = getRBdigitalUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/sierra_api_updates.php';
		$sierra_api_updates = getSierraAPIUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/overdrive_updates.php';
		$overdrive_updates = getOverDriveUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/ebsco_updates.php';
		$ebscoUpdates = getEbscoUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/axis360_updates.php';
		$axis360Updates = getAxis360Updates();
		require_once ROOT_DIR . '/sys/DBMaintenance/theming_updates.php';
		$theming_updates = getThemingUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/translation_updates.php';
		$translation_updates = getTranslationUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/open_archives_updates.php';
		$open_archives_updates = getOpenArchivesUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/redwood_archive_updates.php';
		$redwood_updates = getRedwoodArchiveUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/cloud_library_updates.php';
		$cloudLibraryUpdates = getCloudLibraryUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/website_indexing_updates.php';
		$websiteIndexingUpdates = getWebsiteIndexingUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/web_builder_updates.php';
		$webBuilderUpdates = getWebBuilderUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/events_integration_updates.php';
		$eventsIntegrationUpdates = getEventsIntegrationUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/file_upload_updates.php';
		$fileUploadUpdates = getFileUploadUpdates();

		/** @noinspection SqlResolve */
		/** @noinspection SqlWithoutWhere */
		return array_merge(
			[
				'modules' => [
					'title' => 'Create modules table',
					'description' => 'Create modules table to store information about modules',
					'sql' => [
						'CREATE TABLE modules (
							id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
							name VARCHAR(50) NOT NULL UNIQUE, 
							enabled TINYINT(1) DEFAULT 0,
							indexName VARCHAR(50) DEFAULT "",
							backgroundProcess VARCHAR(50) DEFAULT ""
						) ENGINE=InnoDB DEFAULT CHARSET=utf8',
						'ALTER TABLE modules add INDEX (enabled)',
					]
				],
				'module_log_information' => [
					'title' => 'Module log information',
					'description' => 'Add log information to modules table',
					'sql' => [
						'ALTER TABLE modules ADD COLUMN logClassPath VARCHAR(100)',
						'ALTER TABLE modules ADD COLUMN logClassName VARCHAR(35)',
					]
				],
				'module_settings_information' => [
					'title' => 'Settings Information for modules',
					'description' => 'Add settings information to modules table',
					'sql' => [
						'ALTER TABLE modules ADD COLUMN settingsClassPath VARCHAR(100)',
						'ALTER TABLE modules ADD COLUMN settingsClassName VARCHAR(35)',
					]
				]
			],
			$library_location_updates,
			$user_updates,
			$grouped_work_updates,
			$genealogy_updates,
			$browse_updates,
			$collection_spotlight_updates,
			$indexing_updates,
			$islandora_updates,
			$overdrive_updates,
			$ebscoUpdates,
			$axis360Updates,
			$hoopla_updates,
			$rbdigital_updates,
			$sierra_api_updates,
			$theming_updates,
			$translation_updates,
			$open_archives_updates,
			$redwood_updates,
			$cloudLibraryUpdates,
			$websiteIndexingUpdates,
			$webBuilderUpdates,
			$eventsIntegrationUpdates,
			$fileUploadUpdates,
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
						) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COMMENT='Statistical information about searches for use in reporting '",
						"INSERT INTO search_stats_new (phrase, lastSearch, numSearches) SELECT TRIM(REPLACE(phrase, char(9), '')) as phrase, MAX(lastSearch), sum(numSearches) FROM search_stats WHERE numResults > 0 GROUP BY TRIM(REPLACE(phrase,char(9), ''))",
						"DELETE FROM search_stats_new WHERE phrase LIKE '%(%'",
						"DELETE FROM search_stats_new WHERE phrase LIKE '%)%'",
					),
				),

				'recommendations_optOut' => array(
					'title' => 'Recommendations Opt Out',
					'description' => 'Add tracking for whether the user wants to opt out of recommendations',
					'sql' => array(
						"ALTER TABLE `user` ADD `disableRecommendations` TINYINT NOT NULL DEFAULT '0'",
					),
				),

				'remove_editorial_reviews' => array(
					'title' => 'Remove Editorial Review table',
					'description' => 'Remove editorial review tables',
					'sql' => array(
						"DROP TABLE editorial_reviews;",
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
						") ENGINE = InnoDB COMMENT = 'The reading history for patrons';",
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
						) ENGINE = InnoDB DEFAULT CHARSET=utf8 COMMENT = 'The reading history for patrons';",
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
					'description' => 'Create a table for records the user is not interested in so they can be omitted from search results',
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
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Already owned/On order', 1, 'This email is to let you know the status of your recent request for an item that you did not find in our catalog. The Library already owns this item or it is already on order. Please access our catalog to place this item on hold.	Please check our online catalog periodically to put a hold for this item.', 0)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Item purchased', 1, 'This email is to let you know the status of your recent request for an item that you did not find in our catalog. Outcome: The library is purchasing the item you requested. Please check our online catalog periodically to put yourself on hold for this item. We anticipate that this item will be available soon for you to place a hold.', 0)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Referred to Collection Development - Adult', 0, '', 1)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Referred to Collection Development - J/YA', 0, '', 1)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Referred to Collection Development - AV', 0, '', 1)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('ILL Under Review', 0, '', 1)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Request Referred to ILL', 1, 'This email is to let you know the status of your recent request for an item that you did not find in our catalog. The library\\'s Interlibrary loan department is reviewing your request. We will attempt to borrow this item from another system. This process generally takes about 2 - 6 weeks.', 1)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Request Filled by ILL', 1, 'This email is to let you know the status of your recent request for an item that you did not find in our catalog. Our Interlibrary Loan Department is set to borrow this item from another library.', 0)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Ineligible ILL', 1, 'This email is to let you know the status of your recent request for an item that you did not find in our catalog. Your library account is not eligible for interlibrary loan at this time.', 0)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Not enough info - please contact Collection Development to clarify', 1, 'This email is to let you know the status of your recent request for an item that you did not find in our catalog. We need more specific information in order to locate the exact item you need. Please re-submit your request with more details.', 1)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Unable to acquire the item - out of print', 1, 'This email is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is out of print.', 0)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Unable to acquire the item - not available in the US', 1, 'This email is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is not available in the US.', 0)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Unable to acquire the item - not available from vendor', 1, 'This email is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is not available from a preferred vendor.', 0)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Unable to acquire the item - not published', 1, 'This email is to let you know the status of your recent request for an item that you did not find in our catalog. The item you requested has not yet been published. Please check our catalog when the publication date draws near.', 0)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Unable to acquire the item - price', 1, 'This email is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item does not fit our collection guidelines.', 0)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Unable to acquire the item - publication date', 1, 'This email is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item does not fit our collection guidelines.', 0)",
						"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Unavailable', 1, 'This email is to let you know the status of your recent request for an item that you did not find in our catalog. The item you requested cannot be purchased at this time from any of our regular suppliers and is not available from any of our lending libraries.', 0)",
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
						. "  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,"
						. "  `libraryId` int(11) NOT NULL,"
						. "  `columnNameToDisplay` varchar(30) NOT NULL,"
						. "  `labelForColumnToDisplay` varchar(45) NOT NULL,"
						. "  `weight` smallint(2) unsigned NOT NULL DEFAULT '0',"
						. "  PRIMARY KEY (`id`),"
						. "  UNIQUE KEY `columnNameToDisplay` (`columnNameToDisplay`,`libraryId`),"
						. "  KEY `libraryId` (`libraryId`)"
						. ") ENGINE=InnoDB DEFAULT CHARSET=utf8;"
					),
				),

				'materialsRequestFormats' => array(
					'title' => 'Material Requests Formats Table Creation',
					'description' => 'New table to manage materials formats that can be requested.',
					'sql' => array(
						'CREATE TABLE `materials_request_formats` ('
						. '`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,'
						. '`libraryId` INT UNSIGNED NOT NULL,'
						. ' `format` VARCHAR(30) NOT NULL,'
						. '`formatLabel` VARCHAR(60) NOT NULL,'
						. '`authorLabel` VARCHAR(45) NOT NULL,'
						. '`weight` SMALLINT(2) UNSIGNED NOT NULL DEFAULT 0,'
						. "`specialFields` SET('Abridged/Unabridged', 'Article Field', 'Eaudio format', 'Ebook format', 'Season') NULL,"
						. 'PRIMARY KEY (`id`),'
						. 'INDEX `libraryId` (`libraryId` ASC));'
					),
				),

				'materialsRequestFormFields' => array(
					'title' => 'Material Requests Form Fields Table Creation',
					'description' => 'New table to manage materials request form fields.',
					'sql' => array(
						'CREATE TABLE `materials_request_form_fields` ('
						. '`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,'
						. '`libraryId` INT UNSIGNED NOT NULL,'
						. '`formCategory` VARCHAR(55) NOT NULL,'
						. '`fieldLabel` VARCHAR(255) NOT NULL,'
						. '`fieldType` VARCHAR(30) NULL,'
						. '`weight` SMALLINT(2) UNSIGNED NOT NULL,'
						. 'PRIMARY KEY (`id`),'
						. 'UNIQUE INDEX `id_UNIQUE` (`id` ASC),'
						. 'INDEX `libraryId` (`libraryId` ASC));'
					),
				),

				'staffSettingsTable' => array(
					'title' => 'Staff Settings Table Creation',
					'description' => 'New table to contain user settings for staff users.',
					'sql' => array(
						'CREATE TABLE `user_staff_settings` ('
						. '`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,'
						. '`userId` INT UNSIGNED NOT NULL,'
						. '`materialsRequestReplyToAddress` VARCHAR(70) NULL,'
						. '`materialsRequestEmailSignature` TINYTEXT NULL,'
						. 'PRIMARY KEY (`id`),'
						. 'UNIQUE INDEX `userId_UNIQUE` (`userId` ASC),'
						. 'INDEX `userId` (`userId` ASC));'
					),
				),

				'staffSettingsAllowNegativeUserId' => [
					'title' => 'Staff Settings Allow Negative User ids',
					'description' => 'Allow negative user ids for staff settings',
					'sql' => [
						'ALTER TABLE user_staff_settings change column userId userId INT NOT NULL'
					]
				],

				'materialsRequestLibraryId' => array(
					'title' => 'Add LibraryId to Material Requests Table',
					'description' => 'Add LibraryId column to Materials Request table and populate column for existing requests.',
					'sql' => array(
						'ALTER TABLE `materials_request` '
						. 'ADD COLUMN `libraryId` INT UNSIGNED NULL AFTER `id`, '
						. 'ADD COLUMN `formatId` INT UNSIGNED NULL AFTER `format`; ',

						'UPDATE  `materials_request`'
						. 'LEFT JOIN `user` ON (user.id=materials_request.createdBy) '
						. 'LEFT JOIN `location` ON (location.locationId=user.homeLocationId) '
						. 'SET materials_request.libraryId = location.libraryId '
						. 'WHERE materials_request.libraryId IS null '
						. 'and user.id IS NOT null '
						. 'and location.libraryId IS not null;',

						'UPDATE `materials_request` '
						. 'LEFT JOIN `location` ON (location.locationId=materials_request.holdPickupLocation) '
						. 'SET materials_request.libraryId = location.libraryId '
						. ' WHERE materials_request.libraryId IS null and location.libraryId IS not null;'
					),
				),

				'materialsRequestFixColumns' => array(
					'title' => 'Change a Couple Column Data-Types for Material Requests Table',
					'description' => 'Change illItem column data types for Material Requests Table.',
					'sql' => array(
						'ALTER TABLE `materials_request` CHANGE COLUMN `illItem` `illItem` TINYINT(4) NULL DEFAULT NULL ;'
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

				'superCatalogerRole' => array(
					'title' => 'Create superCataloger role',
					'description' => 'Create cataloging role to handle additional actions typically reserved for consortial offices, etc.',
					'sql' => array(
						"INSERT INTO `roles` (`name`, `description`) VALUES ('superCataloger', 'Allows user to perform cataloging activities that require advanced knowledge.')",
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
					'description' => 'Create Content Editor Role to allow creation of widgets.',
					'sql' => array(
						"INSERT INTO `roles` (`name`, `description`) VALUES ('contentEditor', 'Allows creation of widgets.')",
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

				'ip_lookup_blocking' => [
					'title' => 'IP Lookup Blocking',
					'description' => 'Optionally block access to all of Aspen and APIs by IP address',
					'sql' => [
						"ALTER TABLE ip_lookup ADD COLUMN blockAccess TINYINT NOT NULL DEFAULT 0",
						"ALTER TABLE ip_lookup ADD COLUMN allowAPIAccess TINYINT NOT NULL DEFAULT 0",
						"INSERT INTO ip_lookup (location, ip, locationid, startIpVal, endIpVal, blockAccess, allowAPIAccess, isOpac) VALUES ('Internal', '127.0.0.1', -1, 2130706433, 2130706433, 0, 1, 0)",
					]
				],

				'ip_debugging' =>[
					'title' => 'IP Lookup Debugging',
					'description' => 'Allow debugging based on IP address of the user',
					'sql' => [
						'ALTER TABLE ip_lookup ADD COLUMN showDebuggingInformation TINYINT NOT NULL DEFAULT 0',
						"UPDATE ip_lookup set showDebuggingInformation = 1 where ip ='127.0.0.1'"
					]
				],

				'remove_merged_records' => [
					'title' => 'Remove unused Merged Records Table',
					'description' => 'Remove unused Merged Records Table',
					'sql' => [
						'DROP TABLE IF EXISTS merged_records'
					]
				],

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
					'description' => 'Create a table to store enrichment for authors',
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
					'description' => 'Add a variable to allow setting offline mode from the admin interface, as long as offline logins are allowed.',
					'sql' => array(
						"INSERT INTO variables (name, value) VALUES ('offline_mode_when_offline_login_allowed', 'false')",
					),
				),

				'variables_full_index_warnings' => array(
					'title' => 'Variables for how long of an interval to allow between full indexes',
					'description' => 'Add a variable to allow setting offline mode from the admin interface, as long as offline logins are allowed.',
					'sql' => array(
						"INSERT INTO variables (name, value) VALUES ('fullReindexIntervalWarning', '86400')",
						"INSERT INTO variables (name, value) VALUES ('fullReindexIntervalCritical', '129600')",
					),
				),

				'create_system_variables_table' => [
					'title' => 'Create System Variables Table',
					'description' => 'Create a table to store system variables to avoid hard coding',
					'sql' => [
						'CREATE TABLE system_variables (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							errorEmail VARCHAR( 128 ),
							ticketEmail VARCHAR( 128 ),
							searchErrorEmail VARCHAR( 128 )
						)'
					]
				],

				'loadCoversFrom020z' => [
					'title' => 'Allow loading covers from the 020z',
					'description' => 'Update System variables to allow loading covers from the 020z',
					'sql' => [
						'ALTER TABLE system_variables ADD COLUMN loadCoversFrom020z TINYINT(1) DEFAULT 0'
					]
				],

				'runNightlyFullIndex' => [
					'title' => 'Run Nightly Full Index',
					'description' => 'Whether or not a new full index should be run in the middle of the night',
					'sql' => [
						'ALTER TABLE system_variables ADD COLUMN runNightlyFullIndex TINYINT(1) DEFAULT 0'
					]
				],

				'currencyCode' => [
					'title' => 'Currency code system variable',
					'description' => 'Add currency code to system variables',
					'sql' => [
						"ALTER TABLE system_variables ADD COLUMN currencyCode CHAR(3) DEFAULT 'USD'"
					]
				],

				'utf8_update' => array(
					'title' => 'Update to UTF-8',
					'description' => 'Update database to use UTF-8 encoding',
					'continueOnError' => true,
					'sql' => array(
						"ALTER DATABASE " . $configArray['Database']['database_aspen_dbname'] . " DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;",
						//"ALTER TABLE administrators CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE bad_words CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE db_update CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE ip_lookup CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE library CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE list_widgets CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE list_widget_lists CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE location CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE roles CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE search CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE search_stats CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE session CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE user CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE user_list CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
						"ALTER TABLE user_reading_history CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
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

				'reindexLog' => array(
					'title' => 'Reindex Log table',
					'description' => 'Create Reindex Log table to track reindexing.',
					'sql' => array(
						"CREATE TABLE IF NOT EXISTS reindex_log(" .
						"`id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of reindex log', " .
						"`startTime` INT(11) NOT NULL COMMENT 'The timestamp when the reindex started', " .
						"`endTime` INT(11) NULL COMMENT 'The timestamp when the reindex process ended', " .
						"PRIMARY KEY ( `id` )" .
						") ENGINE = InnoDB;",
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
						") ENGINE = InnoDB;",

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

				'reindexLog_nightly_updates' => [
					'title' => 'Reindex Log Update for Nightly Index',
					'description' => 'Update reindex logging for nightly index',
					'sql' => [
						'ALTER TABLE reindex_log DROP COLUMN numListsProcessed',
						'ALTER TABLE reindex_log ADD COLUMN numErrors INT(11) DEFAULT 0',
					]
				],

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
						") ENGINE = InnoDB;",
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
						") ENGINE = InnoDB;",

					),
				),

				'cron_log_errors' => [
					'title' => 'Cron Log errors',
					'description' => 'Add error counts and notes to the main cron log for consistency',
					'sql' => [
						'ALTER TABLE cron_log ADD COLUMN numErrors INT(11) NOT NULL DEFAULT 0'
					]
				],

				'cron_process_skips' => [
					'title' => 'Cron Process Log skips',
					'description' => 'Add error counts and notes to the main cron log for consistency',
					'sql' => [
						'ALTER TABLE cron_process_log ADD COLUMN numSkipped INT(11) NOT NULL DEFAULT 0'
					]
				],

				'marcImport' => array(
					'title' => 'Marc Import table',
					'description' => 'Create a table to store information about marc records that are being imported.',
					'sql' => array(
						"CREATE TABLE IF NOT EXISTS marc_import(" .
						"`id` VARCHAR(50) COMMENT 'The id of the marc record in the ils', " .
						"`checksum` INT(11) NOT NULL COMMENT 'The timestamp when the reindex started', " .
						"PRIMARY KEY ( `id` )" .
						") ENGINE = InnoDB;",
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
						'ALTER TABLE `list_widget_lists` ADD INDEX `ListWidgetId` ( `listWidgetId` ) ',
						'ALTER TABLE `location` ADD INDEX `ValidHoldPickupBranch` ( `validHoldPickupBranch` ) ',
					),
				),

				'add_indexes2' => array(
					'title' => 'Add indexes 2',
					'description' => 'Add additional indexes to tables that were not defined originally',
					'continueOnError' => true,
					'sql' => array(
						'ALTER TABLE `materials_request_status` ADD INDEX ( `isDefault` )',
						'ALTER TABLE `materials_request_status` ADD INDEX ( `isOpen` )',
						'ALTER TABLE `materials_request_status` ADD INDEX ( `isPatronCancel` )',
						'ALTER TABLE `materials_request` ADD INDEX ( `status` )'
					),
				),

				'remove_spelling_words' => array(
					'title' => 'Remove Spelling Words',
					'description' => 'Optimizations to spelling to ensure indexes are used',
					'continueOnError' => true,
					'sql' => array(
						'DROP TABLE `spelling_words`',
					),
				),

				'remove_library_and location_boost' => array(
					'title' => 'Remove Lib and Loc Boosting',
					'description' => 'Allow boosting of library and location boosting to be disabled',
					'sql' => array(
						"ALTER TABLE `library` DROP COLUMN `boostByLibrary`",
						"ALTER TABLE `location` DROP COLUMN `boostByLocation`",
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
					),
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
						) ENGINE = InnoDB COMMENT = 'Caches information from Millennium so we do not have to continually load it.';",
						"ALTER TABLE `millennium_cache` ADD PRIMARY KEY ( `recordId` , `scope` ) ;",

						"CREATE TABLE IF NOT EXISTS `ptype_restricted_locations` (
							`locationId` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique id for the non holdable location',
							`millenniumCode` VARCHAR(5) NOT NULL COMMENT 'The internal 5 letter code within Millennium',
							`holdingDisplay` VARCHAR(30) NOT NULL COMMENT 'The text displayed in the holdings list within Millennium can use regular expression syntax to match multiple locations',
							`allowablePtypes` VARCHAR(50) NOT NULL COMMENT 'A list of PTypes that are allowed to place holds on items with this location separated with pipes (|).',
							PRIMARY KEY (`locationId`)
						) ENGINE=InnoDB",

						"CREATE TABLE IF NOT EXISTS `non_holdable_locations` (
							`locationId` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique id for the non holdable location',
							`millenniumCode` VARCHAR(5) NOT NULL COMMENT 'The internal 5 letter code within Millennium',
							`holdingDisplay` VARCHAR(30) NOT NULL COMMENT 'The text displayed in the holdings list within Millennium',
							`availableAtCircDesk` TINYINT(4) NOT NULL COMMENT 'The item is available if the patron visits the circulation desk.',
							PRIMARY KEY (`locationId`)
						) ENGINE=InnoDB"
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
					'description' => 'This accommodates any ILS that does not use numeric P-Types',
					'sql' => array(
						'ALTER TABLE `ptype` CHANGE COLUMN `pType` `pType` VARCHAR(20) NOT NULL ;'
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
						) ENGINE = InnoDB"
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
						) ENGINE = InnoDB"
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
						) ENGINE = InnoDB",
					),
				),

				'novelist_data_json' => array(
					'title' => 'Novelist Data JSON',
					'description' => 'Updates to cache full json response for a short period for performance.',
					'sql' => array(
						"ALTER TABLE novelist_data ADD COLUMN jsonResponse MEDIUMTEXT",
						"UPDATE novelist_data set lastUpdate = 0",
					),
				),

				'novelist_data_indexes' => array(
					'title' => 'Novelist Data Add Indexes',
					'description' => 'Add indexes to novelist data for performance.',
					'sql' => array(
						"ALTER TABLE novelist_data ADD INDEX primaryISBN(primaryISBN)",
						"ALTER TABLE novelist_data ADD INDEX series(seriesTitle, volume)",
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
						) ENGINE = InnoDB",
					),
				),

				'syndetics_data_update_1' => array(
					'title' => 'Syndetics Data Update 1',
					'description' => 'Add additional information about when specific content is last updated.',
					'continueOnError' => true,
					'sql' => array(
						"ALTER TABLE syndetics_data CHANGE COLUMN lastUpdate lastDescriptionUpdate INT(11) DEFAULT 0",
						"ALTER TABLE syndetics_data ADD COLUMN lastTableOfContentsUpdate INT(11) DEFAULT 0",
						"ALTER TABLE syndetics_data ADD COLUMN lastExcerptUpdate INT(11) DEFAULT 0",
						"ALTER TABLE syndetics_data DROP COLUMN hasSyndeticsData",
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
						) ENGINE=InnoDB  DEFAULT CHARSET=utf8",
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
						) ENGINE = InnoDB",
					),
				),

				'remove_old_user_rating_table' => [
					'title' => 'Remove user rating',
					'description' => 'Remove old user rating table.',
					'sql' => array(
						"DROP TABLE user_rating",
					),
				],


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
						) ENGINE = InnoDB",
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

				'user_list_searching' => [
					'title' => 'User List Searching',
					'description' => 'Add searchable setting to user lists to give additional control over what is found in search results',
					'continueOnError' => true,
					'sql' => [
						'ALTER TABLE user_list ADD searchable TINYINT(1) DEFAULT 0',
						'updateSearchableLists'
					]
				],

				'user_list_indexing_settings' => [
					'title' => 'User List Indexing Settings',
					'description' => 'Create a table to store List Indexing Settings',
					'sql' => [
						'CREATE TABLE IF NOT EXISTS list_indexing_settings(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							runFullUpdate TINYINT(1) DEFAULT 1,
							lastUpdateOfChangedLists INT(11) DEFAULT 0,
							lastUpdateOfAllLists INT(11) DEFAULT 0
						) ENGINE = InnoDB;'
					]
				],

				'default_list_indexing' => [
					'title' => 'User List Indexing Settings - setup defaults',
					'description' => 'Setup default indexing settings by converting from variables',
					'sql' => [
						'createDefaultListIndexingSettings'
					]
				],

				'user_list_indexing_log' => [
					'title' => 'User List Indexing Log',
					'description' => 'Create a table to store List Indexing Log',
					'sql' => [
						'CREATE TABLE IF NOT EXISTS list_indexing_log(
						    id INT NOT NULL AUTO_INCREMENT,
						    startTime INT(11) NOT NULL,
						    endTime INT(11) NULL, 
						    lastUpdate INT(11) NULL, 
						    notes TEXT,
						    numLists INT(11) DEFAULT 0,
						    numAdded INT(11) DEFAULT 0,
						    numDeleted INT(11) DEFAULT 0,
						    numUpdated INT(11) DEFAULT 0,
						    numSkipped INT(11) DEFAULT 0,
						    numErrors INT(11) DEFAULT 0, 
						    PRIMARY KEY ( `id` )
						) ENGINE = InnoDB;'
					]
				],

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

				'account_profiles_2' => array(
					'title' => 'Update Account Profiles 2',
					'description' => 'Update Account Profiles with additional data to reduce information stored in config',
					'continueOnError' => true,
					'sql' => array(
						"ALTER TABLE `account_profiles` ADD `databaseHost` varchar(100)",
						"ALTER TABLE `account_profiles` ADD `databaseName` varchar(50)",
						"ALTER TABLE `account_profiles` ADD `databaseUser` varchar(50)",
						"ALTER TABLE `account_profiles` ADD `databasePassword` varchar(50)",
						"ALTER TABLE `account_profiles` ADD `sipHost` varchar(100)",
						"ALTER TABLE `account_profiles` ADD `sipPort` varchar(50)",
					)
				),

				'account_profiles_3' => array(
					'title' => 'Update Account Profiles 3',
					'description' => 'Update Account Profiles with additional information about SIP',
					'continueOnError' => true,
					'sql' => array(
						"ALTER TABLE `account_profiles` ADD `sipUser` varchar(50)",
						"ALTER TABLE `account_profiles` ADD `sipPassword` varchar(50)",
					)
				),

				'account_profiles_4' => array(
					'title' => 'Update Account Profiles 4',
					'description' => 'Add database port to connection information',
					'continueOnError' => true,
					'sql' => array(
						"ALTER TABLE `account_profiles` ADD `databasePort` varchar(5)",
					)
				),

				'account_profiles_5' => array(
					'title' => 'Update Account Profiles 5',
					'description' => 'Add database timezone to connection information',
					'continueOnError' => true,
					'sql' => array(
						"ALTER TABLE `account_profiles` ADD `databaseTimezone` varchar(50)",
					)
				),

				'account_profiles_oauth' => array(
					'title' => 'Account Profiles - OAuth',
					'description' => 'Add information for connecting to APIs with OAuth2 credentials',
					'continueOnError' => true,
					'sql' => array(
						"ALTER TABLE `account_profiles` ADD `oAuthClientId` varchar(36)",
						"ALTER TABLE `account_profiles` ADD `oAuthClientSecret` varchar(36)",
					)
				),

				'account_profiles_ils' => array(
					'title' => 'Account Profiles - ILS Type',
					'description' => 'Add information for the type of ILS being used',
					'continueOnError' => false,
					'sql' => array(
						"ALTER TABLE `account_profiles` ADD `ils` varchar(20) DEFAULT 'koha'",
						"UPDATE account_profiles set ils = lcase(driver)",
					)
				),

				'account_profiles_api_version' => array(
					'title' => 'Account Profiles - API Version',
					'description' => 'Add api version for sierra',
					'continueOnError' => false,
					'sql' => array(
						"ALTER TABLE `account_profiles` ADD `apiVersion` varchar(10) DEFAULT ''",
						"UPDATE account_profiles set apiVersion = '5' where ils = 'sierra'",
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
						"ALTER TABLE `search` ADD COLUMN `searchSource` VARCHAR(30) NOT NULL DEFAULT 'local' AFTER `search_object`;",
					)
				),


				'saved_searches_created_default' => array(
					'title' => 'Change default creation date for saved searches',
					'description' => 'Change default creation date for saved searches since it gives errors in newer MySQL versions',
					'continueOnError' => true,
					'sql' => array(
						"ALTER TABLE `search` CHANGE COLUMN `created` `created` DATE NOT NULL;",
					)
				),

				'add_search_url_to_saved_searches' => array(
					'title' => 'Store the Search Url with saved searches',
					'description' => 'Add column to store the url for a search in the search table to optimize finding old versions',
					'continueOnError' => true,
					'sql' => array(
						"ALTER TABLE `search` ADD COLUMN `searchUrl` VARCHAR(255) DEFAULT NULL;",
					)
				),

				'increase_search_url_size' => array(
					'title' => 'Increase allowable length of search url',
					'description' => 'Increase allowable length of search url',
					'continueOnError' => true,
					'sql' => array(
						"ALTER TABLE `search` CHANGE COLUMN `searchUrl` `searchUrl` VARCHAR(1000) DEFAULT NULL;",
					)
				),

				'increase_search_url_size_round_2' => array(
					'title' => 'Increase allowable length of search url again',
					'description' => 'Increase allowable length of search url',
					'continueOnError' => true,
					'sql' => array(
						"ALTER TABLE `search` CHANGE COLUMN `searchUrl` `searchUrl` VARCHAR(2500) DEFAULT NULL;",
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
									) ENGINE = InnoDB;",
					)
				),

				'remove_record_grouping_log' => [
					'title' => 'Remove Record Grouping Log',
					'description' => 'Remove the Record Grouping Log since we no longer use it',
					'sql' => [
						'DROP TABLE record_grouping_log',
					]
				],

				'change_to_innodb' => array(
					'title' => 'Change to INNODB',
					'description' => 'Change all tables to use INNODB rather than MyISAM',
					'continueOnError' => false,
					'sql' => array(
						'convertTablesToInnoDB'
					)
				),

				'bookcover_info' => array(
					'title' => 'Bookcover info',
					'description' => 'Crate a table to store information about bookcover generation process',
					'continueOnError' => false,
					'sql' => [
						"CREATE TABLE IF NOT EXISTS bookcover_info(
									id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
									recordType VARCHAR(20),
									recordId VARCHAR(50),
									firstLoaded INT(11) NOT NULL, 
									lastUsed INT(11) NOT NULL, 
									imageSource VARCHAR(50),
									sourceWidth INT(11),
									sourceHeight INT(11), 
									thumbnailLoaded TINYINT(1) DEFAULT 0,
									mediumLoaded TINYINT(1) DEFAULT 0,
									largeLoaded TINYINT(1) DEFAULT 0,
									uploadedImage TINYINT(1) DEFAULT 0
									) ENGINE = InnoDB;",
						"ALTER TABLE bookcover_info ADD INDEX lastUsed (lastUsed)",
						"ALTER TABLE bookcover_info ADD UNIQUE INDEX record_info (recordType, recordId)",
						"ALTER TABLE bookcover_info ADD INDEX imageSource (imageSource)",
					]
				),

				'sendgrid_settings' => array(
					'title' => 'SendGrid Settings',
					'description' => 'Add settings to handle SendGrid configuration',
					'continueOnError' => false,
					'sql' => array(
						'CREATE TABLE IF NOT EXISTS sendgrid_settings(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							fromAddress VARCHAR(255),
							replyToAddress VARCHAR(255),
							apiKey VARCHAR(255)
						) ENGINE = InnoDB;'
					)
				),

				'aspen_usage' => [
					'title' => 'Aspen Usage Table',
					'description' => 'Add a table to track usage of aspen',
					'continueOnError' => false,
					'sql' => array(
						'CREATE TABLE IF NOT EXISTS aspen_usage(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							year INT(4) NOT NULL,
							month INT(2) NOT NULL,
							pageViews INT(11) DEFAULT 0,
							pageViewsByBots INT(11) DEFAULT 0,
							pageViewsByAuthenticatedUsers INT(11) DEFAULT 0,
							pagesWithErrors INT(11) DEFAULT 0,
							slowPages INT(11) DEFAULT 0,
							ajaxRequests INT(11) DEFAULT 0,
							slowAjaxRequests INT(11) DEFAULT 0,
							coverViews INT(11) DEFAULT 0,
							genealogySearches INT(11) DEFAULT 0,
							groupedWorkSearches INT(11) DEFAULT 0,
							islandoraSearches INT(11) DEFAULT 0,
							openArchivesSearches INT(11) DEFAULT 0,
							userListSearches INT(11) DEFAULT 0
						) ENGINE = InnoDB;',
						"ALTER TABLE aspen_usage ADD INDEX (year, month)",
					)
				],

				'aspen_usage_websites' => [
					'title' => 'Aspen Usage for Website Searches',
					'description' => 'Add a column to track usage of website searches within Aspen',
					'continueOnError' => false,
					'sql' => array(
						'ALTER TABLE aspen_usage ADD COLUMN websiteSearches INT(11) DEFAULT 0',
					)
				],

				'aspen_usage_blocked_requests' => [
					'title' => 'Aspen Usage for Requests that have been blocked',
					'description' => 'Add a column to which requests have been blocked (both regular requests and API)',
					'continueOnError' => false,
					'sql' => array(
						'ALTER TABLE aspen_usage ADD COLUMN blockedRequests INT(11) DEFAULT 0',
						'ALTER TABLE aspen_usage ADD COLUMN blockedApiRequests INT(11) DEFAULT 0',
					)
				],

				'aspen_usage_instance' => [
					'title' => 'Aspen Usage - Instance Information',
					'description' => 'Add Instance Information to Aspen Usage',
					'sql' => [
						'ALTER TABLE aspen_usage ADD COLUMN instance VARCHAR(100)',
						'ALTER TABLE aspen_usage DROP INDEX year',
						'ALTER TABLE aspen_usage ADD INDEX (instance, year, month)'
					]
				],

				'aspen_usage_remove_slow_pages' => [
					'title' => 'Aspen Usage - Remove slow pages',
					'description' => 'Remove slow pages since it was not accurate',
					'sql' => [
						'ALTER TABLE aspen_usage DROP COLUMN slowPages',
						'ALTER TABLE aspen_usage DROP COLUMN slowAjaxRequests',
					]
				],

				'aspen_usage_add_sessions' => [
					'title' => 'Aspen Usage - Add Sessions',
					'description' => 'Add a count of the number of sessions started',
					'sql' => [
						'ALTER TABLE aspen_usage ADD COLUMN sessionsStarted INT(11) DEFAULT 0',
					]
				],

				'slow_pages' => [
					'title' => 'Slow Page Tracking',
					'description' => 'Add tables to track which pages are slow',
					'continueOnError' => false,
					'sql' => array(
						'CREATE TABLE IF NOT EXISTS slow_page(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							year INT(4) NOT NULL,
							month INT(2) NOT NULL,
							module VARCHAR(50) NOT NULL,
							action VARCHAR(50) NOT NULL,
							timesSlow INT(11) DEFAULT 0
						) ENGINE = InnoDB;',
						"ALTER TABLE slow_page ADD INDEX (year, month, module, action)",
						'CREATE TABLE IF NOT EXISTS slow_ajax_request(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							year INT(4) NOT NULL,
							month INT(2) NOT NULL,
							module VARCHAR(50) NOT NULL,
							action VARCHAR(50) NOT NULL,
							method VARCHAR(75) NOT NULL,
							timesSlow INT(11) DEFAULT 0
						) ENGINE = InnoDB;',
						"ALTER TABLE slow_ajax_request ADD INDEX (year, month, module, action, method)",
					)
				],

				'slow_page_granularity' => [
					'title' => 'Slow request granularity',
					'description' => 'Add additional granularity to slow request log',
					'sql' => [
						'ALTER TABLE slow_page add column timesFast INT(11)', //Less than .5 seconds
						'ALTER TABLE slow_page add column timesAcceptable INT(11)', //Less than 1 second
						'ALTER TABLE slow_page add column timesSlower INT(11)', //More than 2 seconds
						'ALTER TABLE slow_page add column timesVerySlow INT(11)', //More than 4 seconds
						'ALTER TABLE slow_ajax_request add column timesFast INT(11)', //Less than .5 seconds
						'ALTER TABLE slow_ajax_request add column timesAcceptable INT(11)', //Less than 1 second
						'ALTER TABLE slow_ajax_request add column timesSlower INT(11)', //More than 2 seconds
						'ALTER TABLE slow_ajax_request add column timesVerySlow INT(11)', //More than 4 seconds
					]
				],

				'memory_table' => [
					'title' => 'cached_values in Memory',
					'description' => 'Memory table for cross platform caching',
					'sql' => [
						'CREATE TABLE IF NOT EXISTS cached_values(
							cacheKey VARCHAR(200) NOT NULL, 
							value VARCHAR(1024),
							expirationTime INT(11)
							) ENGINE = MEMORY;',
					],
				],

				'memory_table_size_increase' => [
					'title' => 'Memory table size increase',
					'description' => 'Memory table for cross platform caching',
					'sql' => [
						'ALTER TABLE cached_values CHANGE COLUMN value value VARCHAR(16384);',
					],
				],

				'memory_index' => [
					'title' => 'Memory table indexing',
					'description' => 'Add Index for memory table',
					'sql' => [
						'ALTER TABLE cached_values ADD UNIQUE INDEX cacheKey(`cacheKey`)',
					],
				],

				'cached_value_case_sensitive' => [
					'title' => 'Memory cache case sensitive keys',
					'description' => 'Make Memory cache keys case sensitive',
					'sql' => [
						'ALTER TABLE cached_values CHANGE COLUMN cacheKey cacheKey VARCHAR(200) COLLATE utf8_bin',
						'TRUNCATE TABLE cached_values',
					],
				],

				'error_table' => [
					'title' => 'Error Logging',
					'description' => 'Table to store error information',
					'sql' => [
						'CREATE TABLE IF NOT EXISTS errors(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							module VARCHAR(50) NOT NULL,
							action VARCHAR(50) NOT NULL,
							url TEXT,
							message TEXT,
							backtrace TEXT,
							timestamp INT(11)
							) ENGINE = INNODB;',
					],
				],

				'error_table_agent' => [
					'title' => 'Error Logging with User Agent',
					'description' => 'Add user agent to error logging',
					'sql' => [
						'ALTER TABLE errors ADD COLUMN userAgent TEXT',
					],
				],

				'placards' => [
					'title' => 'Placard setup',
					'description' => 'Add the ability to add placards to the site',
					'sql' => [
						'CREATE TABLE IF NOT EXISTS placards(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							title VARCHAR(255) NOT NULL,
							body TEXT,
							css TEXT,
							image VARCHAR(100)
						) ENGINE = INNODB;',
						'ALTER TABLE placards ADD INDEX title (title)',
						'CREATE TABLE IF NOT EXISTS placard_trigger(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							placardId INT(11) NOT NULL,
							triggerWord VARCHAR(100) NOT NULL
						) ENGINE = INNODB;',
						'ALTER TABLE placard_trigger ADD INDEX triggerWord (triggerWord)'
					],
				],

				'placard_updates_1' => [
					'title' => 'Placard update 1',
					'description' => 'Add a link to placards, make them (optionally) dismissable, and allow placards to be shown or hidden by library.',
					'continueOnError' => true,
					'sql' => [
						'ALTER TABLE placards ADD COLUMN link VARCHAR(255)',
						'ALTER TABLE placards ADD COLUMN dismissable TINYINT(1)',
						'CREATE TABLE placard_dismissal (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							placardId INT,
							userId INT,
							UNIQUE INDEX userPlacard(userId, placardId)
						) ENGINE = INNODB;',
						'CREATE TABLE placard_library (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							placardId INT,
							libraryId INT,
							UNIQUE INDEX placardLibrary(placardId, libraryId)
						) ENGINE = INNODB;',
						'INSERT INTO placard_library (libraryId, placardId) SELECT libraryId, placards.id from library, placards;'
					]
				],

				'placard_location_scope' => [
					'title' => 'Placard location scope',
					'description' => 'Add location scoping for placards',
					'sql' => [
						'CREATE TABLE placard_location (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							placardId INT,
							locationId INT,
							UNIQUE INDEX placardLocation(placardId, locationId)
						) ENGINE = INNODB;',
						'INSERT INTO placard_location (locationId, placardId) SELECT locationId, placards.id from location, placards;'
					]
				],

				'placard_trigger_exact_match' => [
					'title' => 'Placard Trigger Exact Match',
					'description' => 'Add ability to force triggers to use fuzzy matching',
					'sql' => [
						'ALTER TABLE placard_trigger ADD COLUMN exactMatch TINYINT(1) DEFAULT 0'
					]
				],

				'placard_timing' => [
					'title' => 'Placard Timing',
					'description' => 'Add the ability to set start and end times for when placards are shown',
					'sql' => [
						'ALTER TABLE placards ADD COLUMN startDate INT(11) DEFAULT 0',
						'ALTER TABLE placards ADD COLUMN endDate INT(11) DEFAULT 0'
					]
				],

				'system_messages' => [
					'title' => 'System Message Setup',
					'description' => 'Initial setup of system messages',
					'continueOnError' => true,
					'sql' => [
						'CREATE TABLE IF NOT EXISTS system_messages(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							title VARCHAR(255) NOT NULL,
							message TEXT,
							dismissable TINYINT(1) DEFAULT 0,
							showOn INT DEFAULT 0,
							startDate INT(11) DEFAULT 0,
							endDate INT(11) DEFAULT 0
						) ENGINE = INNODB;',
						'ALTER TABLE system_messages ADD INDEX title (title)',
						'CREATE TABLE system_message_dismissal (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							systemMessageId INT,
							userId INT,
							UNIQUE INDEX userPlacard(userId, systemMessageId)
						) ENGINE = INNODB;',
						'CREATE TABLE system_message_library (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							systemMessageId INT,
							libraryId INT,
							UNIQUE INDEX systemMessageLibrary(systemMessageId, libraryId)
						) ENGINE = INNODB;',
						'CREATE TABLE system_message_location (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							systemMessageId INT,
							locationId INT,
							UNIQUE INDEX systemMessageLocation(systemMessageId, locationId)
						) ENGINE = INNODB;',
					]
				],

				'system_message_style' => [
					'title' => 'System Message Style',
					'description' => 'The default styling to apply to the message',
					'sql' => [
						"ALTER TABLE system_messages ADD COLUMN messageStyle VARCHAR(10) default ''"
					]
				],

				'novelist_settings' => [
					'title' => 'Novelist settings',
					'description' => 'Add the ability to store Novelist settings in the DB rather than config file',
					'sql' => [
						'CREATE TABLE IF NOT EXISTS novelist_settings(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							profile VARCHAR(50) NOT NULL,
							pwd VARCHAR(50) NOT NULL
						) ENGINE = INNODB;',
						'populateNovelistSettings'
					],
				],

				'contentcafe_settings' => [
					'title' => 'ContentCafe settings',
					'description' => 'Add the ability to store ContentCafe settings in the DB rather than config file',
					'sql' => [
						'CREATE TABLE IF NOT EXISTS contentcafe_settings(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							contentCafeId VARCHAR(50) NOT NULL,
							pwd VARCHAR(50) NOT NULL,
							hasSummary TINYINT(1) DEFAULT 1,
							hasToc TINYINT(1) DEFAULT 0,
							hasExcerpt TINYINT(1) DEFAULT 0,
							hasAuthorNotes  TINYINT(1) DEFAULT 0
						) ENGINE = INNODB;',
						'populateContentCafeSettings'
					],
				],

				'syndetics_settings' => [
					'title' => 'Syndetics settings',
					'description' => 'Add the ability to store Syndetics settings in the DB rather than config file',
					'sql' => [
						'CREATE TABLE IF NOT EXISTS syndetics_settings(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							syndeticsKey VARCHAR(50) NOT NULL,
							hasSummary TINYINT(1) DEFAULT 1,
							hasAvSummary TINYINT(1) DEFAULT 0,
							hasAvProfile TINYINT(1) DEFAULT 0,
							hasToc TINYINT(1) DEFAULT 1,
							hasExcerpt TINYINT(1) DEFAULT 1,
							hasVideoClip TINYINT(1) DEFAULT 0,
							hasFictionProfile TINYINT(1) DEFAULT 0,
							hasAuthorNotes  TINYINT(1) DEFAULT 0
						) ENGINE = INNODB;',
						'populateSyndeticsSettings'
					],
				],

				'google_api_settings' => [
					'title' => 'Google API settings',
					'description' => 'Add the ability to store Google API settings in the DB rather than config file',
					'sql' => [
						'CREATE TABLE IF NOT EXISTS google_api_settings(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							googleBooksKey VARCHAR(50) NOT NULL
						) ENGINE = INNODB;',
					],
				],

				'google_more_settings' => [
					'title' => 'Google API - Additional settings',
					'description' => 'Add the ability to store additional keys for Google Analytics in the DB rather than config file',
					'sql' => [
						'ALTER TABLE google_api_settings ADD COLUMN googleAnalyticsTrackingId VARCHAR(50)',
						'ALTER TABLE google_api_settings ADD COLUMN googleAnalyticsLinkingId VARCHAR(50)',
						'ALTER TABLE google_api_settings ADD COLUMN googleAnalyticsLinkedProperties MEDIUMTEXT',
						'ALTER TABLE google_api_settings ADD COLUMN googleAnalyticsDomainName VARCHAR(100)',
						'ALTER TABLE google_api_settings CHANGE COLUMN googleBooksKey googleBooksKey VARCHAR(50)',
						'ALTER TABLE google_api_settings ADD COLUMN googleMapsKey VARCHAR(60)',
						'ALTER TABLE google_api_settings ADD COLUMN googleTranslateKey VARCHAR(60)',
						"ALTER TABLE google_api_settings ADD COLUMN googleTranslateLanguages VARCHAR(100) default 'ar,da,en,es,fr,de,it,ja,pl,pt,ru,sv,th,vi,zh-CN,zh-TW'"
					],
				],

				'google_analytics_version'  => [
					'title' => 'Google API - Analytics Version',
					'description' => 'Add the ability to determine which version of Google Analytics should be embedded.',
					'sql' => [
						"ALTER TABLE google_api_settings ADD COLUMN googleAnalyticsVersion VARCHAR(5) DEFAULT 'v3'"
					]
				],

				'google_remove_google_translate' => [
					'title' => 'Google API - Remove Google Translate',
					'description' => 'Remove Google Translate Settings',
					'sql' => [
						'ALTER TABLE google_api_settings DROP COLUMN googleTranslateKey',
						'ALTER TABLE google_api_settings DROP COLUMN googleTranslateLanguages',
					]
				],

				'coce_settings' => [
					'title' => 'Coce server settings',
					'description' => 'Add the ability to connect to a Coce server to load covers',
					'sql' => [
						'CREATE TABLE IF NOT EXISTS coce_settings(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							coceServerUrl VARCHAR(100) NOT NULL
						) ENGINE = INNODB;',
					],
				],

				'nyt_api_settings' => [
					'title' => 'New York Times API settings',
					'description' => 'Add the ability to store New York Times api settings in the DB rather than config file',
					'sql' => [
						'CREATE TABLE IF NOT EXISTS nyt_api_settings(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							booksApiKey VARCHAR(32) NOT NULL
						) ENGINE = INNODB;',
					],
				],

				'dpla_api_settings' => [
					'title' => 'DP.LA API settings',
					'description' => 'Add the ability to store DP.LA api settings in the DB rather than config file',
					'sql' => [
						'CREATE TABLE IF NOT EXISTS dpla_api_settings(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							apiKey VARCHAR(32) NOT NULL
						) ENGINE = INNODB;',
					],
				],

				'omdb_settings' => [
					'title' => 'OMDB API settings',
					'description' => 'Add the ability to store OMDB API settings in the DB',
					'sql' => [
						'CREATE TABLE omdb_settings(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							apiKey VARCHAR(10) NOT NULL
						) ENGINE = INNODB;'
					]
				],

				'recaptcha_settings' => [
					'title' => 'Recaptcha settings',
					'description' => 'Add the ability to store Recaptcha settings in the DB rather than config file',
					'continueOnError' => 'true',
					'sql' => [
						'CREATE TABLE IF NOT EXISTS recaptcha_settings(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							publicKey VARCHAR(50) NOT NULL,
							privateKey VARCHAR(50) NOT NULL
						) ENGINE = INNODB;',
						'populateRecaptchaSettings'
					],
				],

				'object_history' => [
					'title' => 'Data Object History',
					'description' => 'Add a table to store when properties are changed',
					'sql' => [
						'CREATE TABLE IF NOT EXISTS object_history(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							objectType VARCHAR(75) NOT NULL,
							objectId INT(11) NOT NULL,
							propertyName VARCHAR(75) NOT NULL,
							oldValue VARCHAR(512),
							newValue VARCHAR(512),
							changedBy INT(11) NOT NULL,
							changeDate INT(11) NOT NULL,
							INDEX (objectType, objectId),
							INDEX (changedBy)
						) ENGINE = INNODB;',
					]
				],

				'object_history_field_lengths' => [
					'title' => 'Data Object History Value Lengths',
					'description' => 'Increase the maximum length of values',
					'sql' => [
						'ALTER TABLE object_history CHANGE COLUMN oldValue oldValue TEXT',
						'ALTER TABLE object_history CHANGE COLUMN newValue newValue TEXT',
					]
				],

				'rosen_levelup_settings' => [
					'title' => 'Rosen LevelUP API Settings',
					'description' => 'Add the ability to store Rosen LevelUP API settings in the DB rather than the config file',
					'sql' => [
						'CREATE TABLE IF NOT EXISTS rosen_levelup_settings(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							lu_api_host VARCHAR(50) NOT NULL,
							lu_api_pw VARCHAR(50) NOT NULL,
							lu_api_un VARCHAR(50) NOT NULL,
							lu_district_name VARCHAR(50) NOT NULL,
							lu_eligible_ptypes VARCHAR(50) NOT NULL,
							lu_multi_district_name VARCHAR(50) NOT NULL,
							lu_school_name VARCHAR(50) NOT NULL,
							lu_ptypes_1 VARCHAR(50),
							lu_ptypes_2 VARCHAR(50),
							lu_ptypes_k VARCHAR(50)
						) ENGINE = INNODB;'
					]
				],

				'rosen_levelup_settings_school_prefix' => [
					'title' => 'Rosen LevelUP API Settings - School Code Prefix',
					'description' => 'Add the ability to generate a prefix for location code to accommodate Rosen requirement that school codes be not just numbers. E.g., change Amqui Elementary location code 105 to "Nashville 105"',
					'sql' => [
						'ALTER TABLE rosen_levelup_settings ADD lu_location_code_prefix VARCHAR(50)'
					]
				],

				'ip_address_logs' => [
					'title' => 'Logging by IP Address',
					'description' => 'Add a table to track usage of Aspen by IP Address',
					'sql' => [
						'CREATE TABLE IF NOT EXISTS usage_by_ip_address(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							instance VARCHAR(100),
							ipAddress VARCHAR(25),
							year INT(4) NOT NULL,
							month INT(2) NOT NULL, 
							numRequests INT default 0,
							numBlockedRequests INT default 0,
							numBlockedApiRequests INT default 0,
							lastRequest INT default 0,
							UNIQUE ip(year, month, instance, ipAddress)
						) ENGINE = INNODB;'
					]
				],

				'ip_address_logs_login_info' => [
					'title' => 'Logging by IP Address - Add Login information',
					'description' => 'Add number of login attempts and failed logins to IP Address logs',
					'sql' => [
						'ALTER TABLE usage_by_ip_address ADD COLUMN numLoginAttempts INT default 0',
						'ALTER TABLE usage_by_ip_address ADD COLUMN numFailedLoginAttempts INT default 0',
					]
				],

				'host_information' => [
					'title' => 'Host Information',
					'description' => 'Add a table to allow customization of where a patron goes by default based on host name',
					'sql' => [
						'CREATE TABLE IF NOT EXISTS host_information(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							host VARCHAR(100),
							libraryId INT(11), 
							locationId INT(11) DEFAULT -1,
							defaultPath VARCHAR(50)
						) ENGINE = INNODB'
					]
				],

				'javascript_snippets' => [
					'title' => 'JavaScript Snippet setup',
					'description' => 'Add the ability to add JavaScript Snippets to the site',
					'continueOnError' => true,
					'sql' => [
						'CREATE TABLE IF NOT EXISTS javascript_snippets(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							name VARCHAR(50) NOT NULL,
							snippet TEXT
						) ENGINE = INNODB;',
						'ALTER TABLE javascript_snippets ADD UNIQUE name (name)',
						'CREATE TABLE javascript_snippet_library (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							javascriptSnippetId INT,
							libraryId INT,
							UNIQUE INDEX javascriptSnippetLibrary(javascriptSnippetId, libraryId)
						) ENGINE = INNODB;',
						'CREATE TABLE javascript_snippet_location (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							javascriptSnippetId INT,
							locationId INT,
							UNIQUE INDEX javascriptSnippetLocation(javascriptSnippetId, locationId)
						) ENGINE = INNODB;',
						"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES 
							('Local Enrichment', 'Administer All JavaScript Snippets', '', 70, 'Allows the user to define JavaScript Snippets to be added to the site. This permission has security implications.'),
							('Local Enrichment', 'Administer Library JavaScript Snippets', '', 71, 'Allows the user to define JavaScript Snippets to be added to the site for their library. This permission has security implications.')",
						"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer All JavaScript Snippets'))",
					]
				],

				'user_list_force_reindex_20_18' => [
					'title' => 'Force Reindex of all lists for 20.18',
					'description' => 'Force reindex of all lists due to new functionality in 20.18',
					'sql' => [
						'UPDATE list_indexing_settings set runFullUpdate = 1'
					]
				]
			)
		);
	}

	/** @noinspection PhpUnused */
	public function convertTablesToInnoDB(/** @noinspection PhpUnusedParameterInspection */ &$update)
	{
		global $configArray;
		$sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$configArray['Database']['database_aspen_dbname']}' AND ENGINE = 'MyISAM'";

		global $aspen_db;
		$results = $aspen_db->query($sql, PDO::FETCH_ASSOC);
		$row = $results->fetchObject();
		while ($row != null) {
			/** @noinspection SqlResolve */
			$sql = "ALTER TABLE `{$row->TABLE_NAME}` ENGINE=INNODB";
			$aspen_db->query($sql);
			$row = $results->fetchObject();
		}
	}


	private function checkWhichUpdatesHaveRun($availableUpdates)
	{
		global $aspen_db;
		foreach ($availableUpdates as $key => $update) {
			$update['alreadyRun'] = false;
			$result = $aspen_db->query("SELECT * from db_update where update_key = " . $aspen_db->quote($key));
			if ($result != false && $result->rowCount() > 0) {
				$update['alreadyRun'] = true;
			}
			$availableUpdates[$key] = $update;
		}
		return $availableUpdates;
	}

	private function markUpdateAsRun($update_key)
	{
		global $aspen_db;
		$result = $aspen_db->query("SELECT * from db_update where update_key = " . $aspen_db->quote($update_key));
		if ($result->rowCount() != false) {
			//Update the existing value
			$aspen_db->query("UPDATE db_update SET date_run = CURRENT_TIMESTAMP WHERE update_key = " . $aspen_db->quote($update_key));
		} else {
			$aspen_db->query("INSERT INTO db_update (update_key) VALUES (" . $aspen_db->quote($update_key) . ")");
		}
	}

	private function createUpdatesTable()
	{
		global $aspen_db;
		//Check to see if the updates table exists
		$result = $aspen_db->query("SHOW TABLES");
		$tableFound = false;
		if ($result->rowCount()) {
			while ($row = $result->fetch(PDO::FETCH_NUM)) {
				if ($row[0] == 'db_update') {
					$tableFound = true;
					break;
				}
			}
		}
		if (!$tableFound) {
			//Create the table to mark which updates have been run.
			$aspen_db->query("CREATE TABLE db_update (" .
				"update_key VARCHAR( 100 ) NOT NULL PRIMARY KEY ," .
				"date_run TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP" .
				") ENGINE = InnoDB");
		}
	}

	function runSQLStatement(&$update, $sql)
	{
		global $aspen_db;
		set_time_limit(500);
		$updateOk = true;
		try {
			$aspen_db->query($sql);
			if (!isset($update['status'])) {
				$update['status'] = 'Update succeeded';
			}
		} catch (PDOException $e) {
			if (isset($update['continueOnError']) && $update['continueOnError']) {
				if (!isset($update['status'])) {
					$update['status'] = '';
				}
				$update['status'] .= 'Warning: ' . $e;
			} else {
				$update['status'] = 'Update failed ' . $e;
				$updateOk = false;
			}
		}

		return $updateOk;
	}

	/** @noinspection PhpUnused */
	function createDefaultIpRanges()
	{
		require_once ROOT_DIR . 'sys/IP/IPAddress.php';
		$subnet = new IPAddress();
		$subnet->find();
		while ($subnet->fetch()) {
			$subnet->update();
		}
	}

	/** @noinspection PhpUnused */
	function updateDueDateFormat()
	{
		global $configArray;
		if (isset($configArray['Reindex']['dueDateFormat'])) {
			$ilsIndexingProfile = new IndexingProfile();
			$ilsIndexingProfile->name = 'ils';
			if ($ilsIndexingProfile->find(true)) {
				$ilsIndexingProfile->dueDateFormat = $configArray['Reindex']['dueDateFormat'];
				$ilsIndexingProfile->update();
			}

			$ilsIndexingProfile = new IndexingProfile();
			$ilsIndexingProfile->name = 'millennium';
			if ($ilsIndexingProfile->find(true)) {
				$ilsIndexingProfile->dueDateFormat = $configArray['Reindex']['dueDateFormat'];
				$ilsIndexingProfile->update();
			}
		}
	}

	/** @noinspection PhpUnused */
	function updateShowSeriesInMainDetails()
	{
		$groupedWorkDisplaySettings = new GroupedWorkDisplaySetting();
		$groupedWorkDisplaySettings->find();
		while ($groupedWorkDisplaySettings->fetch()) {
			if (!count($groupedWorkDisplaySettings->showInMainDetails) == 0) {
				$groupedWorkDisplaySettings->showInMainDetails[] = 'showSeries';
				$groupedWorkDisplaySettings->update();
			}
		}
	}

	/** @noinspection PhpUnused */
	function populateNovelistSettings()
	{
		global $configArray;
		if (!empty($configArray['Novelist']['profile'])) {
			require_once ROOT_DIR . '/sys/Enrichment/NovelistSetting.php';
			$novelistSetting = new NovelistSetting();
			$novelistSetting->profile = $configArray['Novelist']['profile'];
			$novelistSetting->pwd = $configArray['Novelist']['pwd'];
			$novelistSetting->insert();
		}
	}

	/** @noinspection PhpUnused */
	function populateContentCafeSettings()
	{
		global $configArray;
		if (!empty($configArray['ContentCafe']['id'])) {
			require_once ROOT_DIR . '/sys/Enrichment/ContentCafeSetting.php';
			$setting = new ContentCafeSetting();
			$setting->contentCafeId = $configArray['ContentCafe']['id'];
			$setting->pwd = $configArray['ContentCafe']['pw'];
			$setting->hasSummary = ($configArray['ContentCafe']['showSummary'] == true);
			$setting->hasToc = ($configArray['ContentCafe']['showToc'] == true);
			$setting->hasExcerpt = ($configArray['ContentCafe']['showExcerpt'] == true);
			$setting->hasAuthorNotes = ($configArray['ContentCafe']['showAuthorNotes'] == true);
			$setting->insert();
		}
	}

	/** @noinspection PhpUnused */
	function populateSyndeticsSettings()
	{
		global $configArray;
		if (!empty($configArray['Syndetics']['key'])) {
			require_once ROOT_DIR . '/sys/Enrichment/SyndeticsSetting.php';
			$setting = new SyndeticsSetting();
			$setting->syndeticsKey = $configArray['Syndetics']['key'];
			$setting->hasSummary = ($configArray['Syndetics']['showSummary'] == true);
			$setting->hasAvSummary = ($configArray['Syndetics']['showAvSummary'] == true);
			$setting->hasAvProfile = ($configArray['Syndetics']['showAvProfile'] == true);
			$setting->hasToc = ($configArray['Syndetics']['showToc'] == true);
			$setting->hasExcerpt = ($configArray['Syndetics']['showExcerpt'] == true);
			$setting->hasFictionProfile = ($configArray['Syndetics']['showFictionProfile'] == true);
			$setting->hasAuthorNotes = ($configArray['Syndetics']['showAuthorNotes'] == true);
			$setting->hasVideoClip = ($configArray['Syndetics']['showVideoClip'] == true);
			$setting->insert();
		}
	}

	/** @noinspection PhpUnused */
	function populateRecaptchaSettings()
	{
		global $configArray;
		if (!empty($configArray['ReCaptcha']['publicKey'])) {
			require_once ROOT_DIR . '/sys/Enrichment/RecaptchaSetting.php';
			$recaptchaSetting = new RecaptchaSetting();
			$recaptchaSetting->publicKey = $configArray['ReCaptcha']['publicKey'];
			$recaptchaSetting->privateKey = $configArray['ReCaptcha']['privateKey'];
			$recaptchaSetting->insert();
		}
	}

	/** @noinspection PhpUnused */
	function updateSearchableLists(){
		//Get a list of users who have permission to create searchable lists
		require_once ROOT_DIR . '/sys/Administration/Permission.php';
		require_once ROOT_DIR . '/sys/Administration/RolePermissions.php';
		require_once ROOT_DIR . '/sys/Administration/UserRoles.php';
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		require_once ROOT_DIR . '/sys/Account/PType.php';
		$permission = new Permission();
		$permission->name = 'Include Lists In Search Results';
		$permission->find(true);

		$permissionRoles = new RolePermissions();
		$permissionRoles->permissionId = $permission->id;
		$permissionRoles->find();
		while ($permissionRoles->fetch()){
			$userRole = new UserRoles();
			$userRole->roleId = $permissionRoles->roleId;
			$userRole->find();
			while($userRole->fetch()){
				$this->makeListsSearchableForUser($userRole->userId);
			}
		}

		//Also update based on ptype
		$pType = new PType();
		$pType->whereAdd('assignedRoleId > -1');
		$pType->find();
		while ($pType->fetch()){
			$user = new User();
			$user->patronType = $pType;
			$user->find();
			while ($user->fetch()){
				$this->makeListsSearchableForUser($user->id);
			}
		}

		//finally update nyt user
		$user = new User();
		$user->cat_username = 'nyt_user';
		if ($user->find(true)){
			$this->makeListsSearchableForUser($user->id);
		}
	}

	/**
	 * @param int $userId
	 */
	protected function makeListsSearchableForUser($userId)
	{
		$userList = new UserList();
		$userList->user_id = $userId;
		$userList->find();
		$allLists = [];
		while ($userList->fetch()) {
			$allLists[] = clone $userList;
		}
		foreach ($allLists as $list){
			if ($list->searchable == 0) {
				$list->searchable = 1;
				$list->update();
			}
		}
	}

	/** @noinspection PhpUnused */
	function createDefaultListIndexingSettings(){
		require_once ROOT_DIR . '/sys/UserLists/ListIndexingSettings.php';
		$listIndexingSettings = new ListIndexingSettings();
		$listIndexingSettings->find();
		if (!$listIndexingSettings->fetch()){
			$listIndexingSettings = new ListIndexingSettings();
			$variable = new Variable();
			$variable->name = 'last_user_list_index_time';
			if ($variable->find(true)){
				$listIndexingSettings->lastUpdateOfChangedLists = $variable->value;
				$variable->delete();
			}
			$listIndexingSettings->insert();
		}
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_admin', 'System Administration');
		$breadcrumbs[] = new Breadcrumb('', 'Database Maintenance');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'system_admin';
	}

	function canView()
	{
		return UserAccount::userHasPermission('Run Database Maintenance');
	}
}