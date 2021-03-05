<?php

function getOverDriveUpdates()
{
	return array(
		'overdrive_api_data' => array(
			'title' => 'OverDrive API Data',
			'description' => 'Build tables to store data loaded fromthe OverDrive API so the reindex process can use cached data and so we can add additional logic for lastupdate time, etc.',
			'sql' => array(
				"CREATE TABLE IF NOT EXISTS overdrive_api_products (
					`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					overdriveId VARCHAR(36) NOT NULL,
					mediaType  VARCHAR(50) NOT NULL,
					title VARCHAR(512) NOT NULL,
					series VARCHAR(215),
					primaryCreatorRole VARCHAR(50),
					primaryCreatorName VARCHAR(215),
					cover VARCHAR(215),
					dateAdded INT(11),
					dateUpdated INT(11),
					lastMetadataCheck INT(11),
					lastMetadataChange INT(11),
					lastAvailabilityCheck INT(11),
					lastAvailabilityChange INT(11),
					deleted TINYINT(1) DEFAULT 0,
					dateDeleted INT(11) DEFAULT NULL,
					UNIQUE(overdriveId),
					INDEX(dateUpdated),
					INDEX(lastMetadataCheck),
					INDEX(lastAvailabilityCheck),
					INDEX(deleted)
                )",
				"CREATE TABLE IF NOT EXISTS overdrive_api_product_formats (
					`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					productId INT,
					textId VARCHAR(25),
					numericId INT,
					name VARCHAR(512),
					fileName  VARCHAR(215),
					fileSize INT,
					partCount TINYINT,
					sampleSource_1 VARCHAR(215),
					sampleUrl_1 VARCHAR(215),
					sampleSource_2 VARCHAR(215),
					sampleUrl_2 VARCHAR(215),
					INDEX(productId),
					INDEX(numericId),
					UNIQUE(productId, textId)
                )",
				"CREATE TABLE IF NOT EXISTS overdrive_api_product_metadata (
					`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					productId INT,
					checksum BIGINT,
					sortTitle VARCHAR(512),
					publisher VARCHAR(215),
					publishDate INT(11),
					isPublicDomain TINYINT(1),
					isPublicPerformanceAllowed TINYINT(1),
					shortDescription TEXT,
					fullDescription TEXT,
					starRating FLOAT,
					popularity INT,
					UNIQUE(productId)
				)",
				"CREATE TABLE IF NOT EXISTS overdrive_api_product_identifiers (
					`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					productId INT,
					type VARCHAR(50),
					value VARCHAR(75),
					INDEX (productId),
					INDEX (type)
				)",
				"CREATE TABLE IF NOT EXISTS overdrive_api_product_languages (
					`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					code VARCHAR(10),
					name VARCHAR(50),
					INDEX (code)
				)",
				"CREATE TABLE IF NOT EXISTS overdrive_api_product_languages_ref (
					`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					productId INT,
					languageId INT,
					UNIQUE (productId, languageId)
				)",
				"CREATE TABLE IF NOT EXISTS overdrive_api_product_subjects (
					`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(512),
					index(name)
				)",
				"CREATE TABLE IF NOT EXISTS overdrive_api_product_subjects_ref (
					productId INT,
					subjectId INT,
					UNIQUE (productId, subjectId)
				)",
				"CREATE TABLE IF NOT EXISTS overdrive_api_product_availability (
					`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					productId INT,
					libraryId INT,
					available TINYINT(1),
					copiesOwned INT,
					copiesAvailable INT,
					numberOfHolds INT,
					INDEX (productId),
					INDEX (libraryId),
					UNIQUE(productId, libraryId)
				)",
				"CREATE TABLE IF NOT EXISTS overdrive_extract_log(
					`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					`startTime` INT(11),
					`endTime` INT(11),
					`lastUpdate` INT(11),
					numProducts INT(11) DEFAULT 0,
					numErrors INT(11) DEFAULT 0,
					numAdded INT(11) DEFAULT 0,
					numDeleted INT(11) DEFAULT 0,
					numUpdated INT(11) DEFAULT 0,
					numSkipped INT(11) DEFAULT 0,
					numAvailabilityChanges INT(11) DEFAULT 0,
					numMetadataChanges INT(11) DEFAULT 0,
					`notes` TEXT
				)",
			)
		),

		'overdrive_api_data_update_1' => array(
			'title' => 'OverDrive API Data Update 1',
			'description' => 'Update MetaData tables to store thumbnail, cover, and raw metadata.  Also update product to store raw metadata',
			'sql' => array(
				"ALTER TABLE overdrive_api_products ADD COLUMN rawData MEDIUMTEXT",
				"ALTER TABLE overdrive_api_product_metadata ADD COLUMN rawData MEDIUMTEXT",
				"ALTER TABLE overdrive_api_product_metadata ADD COLUMN thumbnail VARCHAR(255)",
				"ALTER TABLE overdrive_api_product_metadata ADD COLUMN cover VARCHAR(255)",
			),
		),

		'overdrive_api_data_update_2' => array(
			'title' => 'OverDrive API Data Update 2',
			'description' => 'Update Product table to add subtitle',
			'sql' => array(
				"ALTER TABLE overdrive_api_products ADD COLUMN subtitle VARCHAR(255)",
			),
		),

		'overdrive_api_data_availability_type' => array(
			'title' => 'Add availability type to OverDrive API',
			'description' => 'Update Availability table to add availability type',
			'sql' => array(
				"ALTER TABLE overdrive_api_product_availability ADD COLUMN availabilityType VARCHAR(35) DEFAULT 'Normal'",
			),
		),

		'overdrive_api_data_availability_shared' => array(
			'title' => 'Add shared flag to OverDrive API',
			'description' => 'Update Availability table to add shared flag',
			'sql' => array(
				"ALTER TABLE overdrive_api_product_availability ADD COLUMN shared TINYINT(1) DEFAULT '0'",
			),
		),

		'overdrive_api_data_metadata_isOwnedByCollections' => array(
			'title' => 'Add isOwnedByCollections to OverDrive Metadata API',
			'description' => 'Update isOwnedByCollections table to add metadata table',
			'sql' => array(
				"ALTER TABLE overdrive_api_product_metadata ADD COLUMN isOwnedByCollections TINYINT(1) DEFAULT '1'",
			),
		),

		'remove_overdrive_api_data_needsUpdate' => array(
			'title' => 'Remove needsUpdate from OverDrive Product API',
			'description' => 'Update overdrive_api_product table to remove needsUpdate to determine if the record should be reloaded from the API',
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE overdrive_api_products DROP COLUMN needsUpdate",
			),
		),

		'overdrive_api_data_crossRefId' => array(
			'title' => 'Add crossRefId to OverDrive Product API',
			'description' => 'Update overdrive_api_product table to add crossRefId to allow quering of product data ',
			'sql' => array(
				"ALTER TABLE overdrive_api_products ADD COLUMN crossRefId INT(11) DEFAULT '0'",
			),
		),

		'utf8_update' => array(
			'title' => 'Update to UTF-8',
			'description' => 'Update database to use UTF-8 encoding',
			'sql' => array(
				"ALTER TABLE db_update CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
			),
		),

		'overdrive_api_remove_old_tables' => array(
			'title' => 'Remove old OverDrive tables',
			'description' => 'Remove OverDrive tables that are no longer used',
			'continueOnError' => true,
			'sql' => array(
				"DROP TABLE overdrive_api_product_creators",
				"DROP TABLE overdrive_api_product_languages",
				"DROP TABLE overdrive_api_product_languages_ref",
				"DROP TABLE overdrive_api_product_subjects",
				"DROP TABLE overdrive_api_product_subjects_ref",
				"DROP TABLE overdrive_record_cache",
				"ALTER TABLE overdrive_api_products DROP COLUMN rawData",
			),
		),

		'overdrive_add_settings' => array(
			'title' => 'Add OverDrive Settings',
			'description' => 'Add Settings for OverDrive to move configuration out of ini',
			'sql' => array(
				"CREATE TABLE IF NOT EXISTS overdrive_settings(
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					url VARCHAR(255),
					patronApiUrl VARCHAR(255),
					clientSecret VARCHAR(50),
					clientKey VARCHAR(50),
					accountId INT(11) DEFAULT 0,
					websiteId INT(11) DEFAULT 0,
					productsKey VARCHAR(50) DEFAULT 0,
					runFullUpdate TINYINT(1) DEFAULT 0
				)",
			),
		),

		'overdrive_add_update_info_to_settings' => array(
			'title' => 'Add Update information to OverDrive Settings',
			'description' => 'Add update times to overdrive settings',
			'sql' => array(
				"DELETE FROM variables WHERE name = 'last_overdrive_extract_time' OR name = 'partial_overdrive_extract_running'",
				"ALTER TABLE overdrive_settings ADD COLUMN lastUpdateOfChangedRecords INT(11) DEFAULT 0",
				"ALTER TABLE overdrive_settings ADD COLUMN lastUpdateOfAllRecords INT(11) DEFAULT 0",
			),
		),

		'track_overdrive_user_usage' => array(
			'title' => 'OverDrive Usage by user',
			'description' => 'Add a table to track how often a particular user uses OverDrive.',
			'sql' => array(
				"CREATE TABLE user_overdrive_usage (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					userId INT(11) NOT NULL,
					year INT(4) NOT NULL,
					month INT(2) NOT NULL,
					usageCount INT(11)
				) ENGINE = InnoDB",
				"ALTER TABLE user_overdrive_usage ADD INDEX (userId, year, month)",
				"ALTER TABLE user_overdrive_usage ADD INDEX (year, month)",
			),
		),

		'track_overdrive_record_usage' => array(
			'title' => 'Overdrive Record Usage',
			'description' => 'Add a table to track how records within overdrive are used.',
			'continueOnError' => true,
			'sql' => array(
				"CREATE TABLE overdrive_record_usage (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					overdriveId VARCHAR(36),
					year INT(4) NOT NULL,
					month INT(2) NOT NULL,
					timesHeld INT(11) NOT NULL,
					timesCheckedOut INT(11) NOT NULL
				) ENGINE = InnoDB",
				"ALTER TABLE overdrive_record_usage ADD INDEX (overdriveId, year, month)",
				"ALTER TABLE overdrive_record_usage ADD INDEX (year, month)",
			),
		),

		'create_overdrive_module' => [
			'title' => 'Create OverDrive Module',
			'description' => 'Setup OverDrive module',
			'sql' => [
				"INSERT INTO modules (name, indexName, backgroundProcess) VALUES ('OverDrive', 'grouped_works', 'overdrive_extract')"
			]
		],

		'create_overdrive_scopes' => [
			'title' => 'Create OverDrive Scopes',
			'description' => 'Setup OverDrive scopes',
			'sql' => [
				'CREATE TABLE overdrive_scopes (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(50) NOT NULL UNIQUE,
					includeAdult TINYINT DEFAULT 1,
					includeTeen TINYINT DEFAULT 1,
					includeKids TINYINT DEFAULT 1,
					authenticationILSName VARCHAR(45) NULL,
					requirePin TINYINT(1) DEFAULT 0,
					overdriveAdvantageName VARCHAR(128) DEFAULT \'\',
					overdriveAdvantageProductsKey VARCHAR(20) DEFAULT \'\'
				) ENGINE = InnoDB',
				"INSERT INTO overdrive_scopes(id, name) VALUES (1, 'All Records')",
				'ALTER TABLE library ADD COLUMN overDriveScopeId INT(11) DEFAULT -1',
				'ALTER TABLE location ADD COLUMN overDriveScopeId INT(11) DEFAULT -2',
				'buildDefaultOverDriveScopes',
				'ALTER TABLE library DROP COLUMN includeOverDriveAdult',
				'ALTER TABLE library DROP COLUMN includeOverDriveTeen',
				'ALTER TABLE library DROP COLUMN includeOverDriveKids',
				'ALTER TABLE library DROP COLUMN enableOverdriveCollection',
				'ALTER TABLE library DROP COLUMN repeatInOverdrive',
				'ALTER TABLE library DROP COLUMN overDriveAuthenticationILSName',
				'ALTER TABLE library DROP COLUMN overdriveRequirePin',
				'ALTER TABLE library DROP COLUMN overdriveAdvantageName',
				'ALTER TABLE library DROP COLUMN overdriveAdvantageProductsKey',
				'ALTER TABLE location DROP COLUMN includeOverDriveAdult',
				'ALTER TABLE location DROP COLUMN includeOverDriveTeen',
				'ALTER TABLE location DROP COLUMN includeOverDriveKids',
				'ALTER TABLE location DROP COLUMN enableOverdriveCollection',
				'ALTER TABLE location DROP COLUMN repeatInOverdrive',
			]
		],

		'overdrive_module_add_log' =>[
			'title' => 'OverDrive add log info to module',
			'description' => 'Add logging information to OverDrive module',
			'sql' => [
				"UPDATE modules set logClassPath='/sys/OverDrive/OverDriveExtractLogEntry.php', logClassName='OverDriveExtractLogEntry' WHERE name='OverDrive'",
			]
		],

		'overdrive_module_add_settings' => [
			'title' => 'Add Settings to OverDrive module',
			'description' => 'Add Settings to OverDrive module',
			'sql' => [
				"UPDATE modules set settingsClassPath = '/sys/OverDrive/OverDriveSetting.php', settingsClassName = 'OverDriveSetting' WHERE name = 'OverDrive'"
			]
		],

		'overdrive_part_count' => [
			'title' => 'OverDrive part count',
			'description' => 'Increase the size of the partCount field',
			'sql' => [
				'ALTER TABLE overdrive_api_product_formats CHANGE partCount partCount SMALLINT',
			]
		],

		'overdrive_add_setting_to_scope' => [
			'title' => 'Add settingId to OverDrive scope',
			'description' => 'Allow multiple settings to be defined for OverDrive within a consortium',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE overdrive_scopes ADD column settingId INT(11)',
				'updateOverDriveScopes'
			]
		],

		'overdrive_add_setting_to_log' => [
			'title' => 'Add settingID to OverDrive log entry',
			'description' => 'Define which settings are being logged',
			'sql' => [
				'ALTER table overdrive_extract_log ADD column settingId INT(11)',
				'updateOverDriveLogEntries'
			]
		],

		'overdrive_add_setting_to_product_availability' => [
			'title' => 'Add settingID to OverDrive availability',
			'description' => 'Define which settings the availability belongs to',
			'continueOnError' => true,
			'sql' => [
				'ALTER table overdrive_api_product_availability ADD column settingId INT(11)',
				'updateOverDriveAvailabilities'
			]
		],

		'overdrive_availability_update_indexes' => [
			'title' => 'Update OverDrive Availability Indexes',
			'description' => 'Fix indexes for overdrive availability to include settings',
			'sql' => [
				'ALTER TABLE overdrive_api_product_availability drop index productId',
				'ALTER TABLE overdrive_api_product_availability drop index productId_2',
				'ALTER TABLE overdrive_api_product_availability ADD UNIQUE (productId, settingId, libraryId)'
			]
		],

		'overdrive_usage_add_instance' => [
			'title' => 'OverDrive Usage - Instance Information',
			'description' => 'Add Instance Information to OverDrive Usage stats',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE overdrive_record_usage ADD COLUMN instance VARCHAR(100)',
				'ALTER TABLE overdrive_record_usage DROP INDEX overdriveId',
				'ALTER TABLE overdrive_record_usage ADD UNIQUE INDEX (instance, overdriveId, year, month)',
				'ALTER TABLE user_overdrive_usage ADD COLUMN instance VARCHAR(100)',
				'ALTER TABLE user_overdrive_usage DROP INDEX userId',
				'ALTER TABLE user_overdrive_usage ADD UNIQUE INDEX (instance, userId, year, month)',
			]
		],

		'overdrive_client_credentials' => [
			'title' => 'OverDrive Scope Client Credentials',
			'description' => 'Add client credential informtion to OverDrive Scopes',
			'sql' => [
				'ALTER TABLE overdrive_scopes ADD COLUMN clientSecret VARCHAR(50)',
				'ALTER TABLE overdrive_scopes ADD COLUMN clientKey VARCHAR(50)',
			]
		],

		'overdrive_allow_large_deletes' => [
			'title' => 'OverDrive - Allow Large Deletes',
			'description' => 'Allow the OverDrive process to delete more than 500 records or 5% of the collection',
			'sql' => [
				'ALTER TABLE overdrive_settings ADD COLUMN allowLargeDeletes TINYINT(1) DEFAULT 0'
			]
		],

		'track_overdrive_stats' => array(
			'title' => 'OverDrive Stats',
			'description' => 'Add a table to track how OverDrive is used.',
			'continueOnError' => true,
			'sql' => array(
				"CREATE TABLE overdrive_stats (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					instance VARCHAR(100),
					year INT(4) NOT NULL,
					month INT(2) NOT NULL,
					numCheckouts INT(11) NOT NULL DEFAULT 0,
					numFailedCheckouts INT(11) NOT NULL DEFAULT 0,
					numRenewals INT(11) NOT NULL DEFAULT 0,
					numEarlyReturns INT(11) NOT NULL DEFAULT 0,
					numHoldsPlaced INT(11) NOT NULL DEFAULT 0,
					numFailedHolds INT(11) NOT NULL DEFAULT 0,
					numHoldsCancelled INT(11) NOT NULL DEFAULT 0,
					numHoldsFrozen INT(11) NOT NULL DEFAULT 0,
					numHoldsThawed INT(11) NOT NULL DEFAULT 0,
					numDownloads INT(11) NOT NULL DEFAULT 0,
					numPreviews INT(11) NOT NULL DEFAULT 0, 
					numOptionsUpdates INT(11) NOT NULL DEFAULT 0, 
					numApiErrors INT(11) NOT NULL DEFAULT 0,
					numConnectionFailures INT(11) NOT NULL DEFAULT 0
				) ENGINE = InnoDB",
				"ALTER TABLE overdrive_stats ADD INDEX (instance, year, month)",
			),
		),
	);
}

function buildDefaultOverDriveScopes($update)
{
	global $aspen_db;

	try {

		//Process libraries
		$uniqueOverDriveSettingsSQL = "SELECT libraryId as id, displayName, enableOverdriveCollection, includeOverDriveAdult, includeOverDriveTeen, includeOverDriveKids, overDriveAuthenticationILSName, overdriveRequirePin, overdriveAdvantageName, overdriveAdvantageProductsKey From library";

		$uniqueSettingsRS = $aspen_db->query($uniqueOverDriveSettingsSQL, PDO::FETCH_ASSOC);
		$uniqueRow = $uniqueSettingsRS->fetch();
		while ($uniqueRow != null) {
			$library = new Library();
			$library->libraryId = $uniqueRow['id'];
			if ($library->find(true)) {
				if ($uniqueRow['enableOverDriveCollection'] = 0 || ($uniqueRow['includeOverDriveAdult'] == 0 && $uniqueRow['includeOverDriveTeen'] == 0 && $uniqueRow['includeOverDriveKids'] == 0)) {
					$library->overDriveScopeId = -1;
				} else {
					//Get the correct id
					$overdriveScope = getOverDriveScopeSettings($uniqueRow);
					if ($overdriveScope->find(true)) {
						$library->overDriveScopeId = $overdriveScope->id;
					} else {
						$overdriveScope->name = 'Library: ' . $uniqueRow['displayName'];
						$overdriveScope->insert();
						$library->overDriveScopeId = $overdriveScope->id;
					}
				}
				$library->update();
			}
			$uniqueRow = $uniqueSettingsRS->fetch();
		}

		//Process locations
		$uniqueOverDriveSettingsSQL = "SELECT locationId as id, locationId, location.libraryId, location.displayName, location.enableOverdriveCollection, location.includeOverDriveAdult, location.includeOverDriveTeen, location.includeOverDriveKids, overDriveAuthenticationILSName, overdriveRequirePin, overdriveAdvantageName, overdriveAdvantageProductsKey, library.overDriveScopeId as libraryOverDriveScopeId From location inner join library on location.libraryId = library.libraryId";

		$uniqueSettingsRS = $aspen_db->query($uniqueOverDriveSettingsSQL, PDO::FETCH_ASSOC);
		$uniqueRow = $uniqueSettingsRS->fetch();
		while ($uniqueRow != null) {
			$location = new Location();
			$location->locationId = $uniqueRow['locationId'];
			if ($location->find(true)) {
				if ($uniqueRow['enableOverDriveCollection'] = 0 || ($uniqueRow['includeOverDriveAdult'] == 0 && $uniqueRow['includeOverDriveTeen'] == 0 && $uniqueRow['includeOverDriveKids'] == 0)) {
					$location->overDriveScopeId = -2;
				} else {
					//Get the correct id
					$overdriveScope = getOverDriveScopeSettings($uniqueRow);
					if ($overdriveScope->find(true)) {
						if ($overdriveScope->id == $uniqueRow['libraryOverDriveScopeId']) {
							$location->overDriveScopeId = -1;
						} else {
							$location->overDriveScopeId = $overdriveScope->id;
						}
					} else {
						$overdriveScope->name = 'Location: ' . $uniqueRow['displayName'];
						$overdriveScope->insert();
						$location->overDriveScopeId = $overdriveScope->id;
					}
				}
				$location->update();
			}
			$uniqueRow = $uniqueSettingsRS->fetch();
		}
		$update['status'] = 'Update succeeded';
	}catch (Exception $e){
		if (isset($update['continueOnError']) && $update['continueOnError']) {
			if (!isset($update['status'])) {
				$update['status'] = '';
			}
			$update['status'] .= 'Warning: ' . $e;
		} else {
			$update['status'] = 'Update failed ' . $e;
		}
	}
}

/**
 * @param $uniqueRow
 * @return OverDriveScope
 */
function getOverDriveScopeSettings($uniqueRow): OverDriveScope
{
	require_once ROOT_DIR . '/sys/OverDrive/OverDriveScope.php';
	$overdriveScope = new OverDriveScope();
	$overdriveScope->includeAdult = $uniqueRow['includeOverDriveAdult'];
	$overdriveScope->includeTeen = $uniqueRow['includeOverDriveTeen'];
	$overdriveScope->includeKids = $uniqueRow['includeOverDriveKids'];
	$overdriveScope->authenticationILSName = $uniqueRow['overDriveAuthenticationILSName'];
	$overdriveScope->requirePin = $uniqueRow['overdriveRequirePin'];
	$overdriveScope->overdriveAdvantageName = $uniqueRow['overdriveAdvantageName'];
	$overdriveScope->overdriveAdvantageProductsKey = $uniqueRow['overdriveAdvantageProductsKey'];
	return $overdriveScope;
}

/** @noinspection PhpUnused */
function updateOverDriveScopes(){
	require_once ROOT_DIR . '/sys/OverDrive/OverDriveSetting.php';
	require_once ROOT_DIR . '/sys/OverDrive/OverDriveScope.php';
	$overdriveSettings = new OverDriveSetting();
	if ($overdriveSettings->find(true)){
		$overdriveScopes = new OverDriveScope();
		$overdriveScopes->find();
		while ($overdriveScopes->fetch()){
			$overdriveScopes->settingId = $overdriveSettings->id;
			$overdriveScopes->update();
		}
	}
}




/** @noinspection PhpUnused */
function updateOverDriveLogEntries(){
	global $aspen_db;
	require_once ROOT_DIR . '/sys/OverDrive/OverDriveSetting.php';
	require_once ROOT_DIR . '/sys/OverDrive/OverDriveExtractLogEntry.php';
	$overdriveSettings = new OverDriveSetting();
	if ($overdriveSettings->find(true)){
		$aspen_db->query("update overdrive_extract_log set settingId = {$overdriveSettings->id}");
	}
}

/** @noinspection PhpUnused */
function updateOverDriveAvailabilities(){
	global $aspen_db;
	require_once ROOT_DIR . '/sys/OverDrive/OverDriveSetting.php';
	require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductAvailability.php';
	$overdriveSettings = new OverDriveSetting();
	if ($overdriveSettings->find(true)){
		$aspen_db->query("update overdrive_api_product_availability set settingId = {$overdriveSettings->id}");
	}
}