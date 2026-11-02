<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_11_00(): array {
	$now = time();

	return [
		/*'name' => [
			 'title' => '',
			 'description' => '',
			 'continueOnError' => false,
			 'sql' => [
				 ''
			 ]
		 ], //name*/

		//mark n

		//kirstien

		//kodi

		//yanjun

		//imani

		//galen

		//chloe

		//pedro

		//mark j

		//lucas

		//tomas

		// stephen

		//jacob - OpenFifth
		//borrowbox
		'borrowbox_module' => [
			'title' => 'Create BorrowBox Module',
			'description' => 'Setup BorrowBox eContent integration module',
			'sql' => [
				"INSERT INTO modules (
					name,
					enabled,
					indexName,
					backgroundProcess,
					logClassPath,
					logClassName,
					settingsClassPath,
					settingsClassName
				) VALUES (
					'BorrowBox',
					0,
					'grouped_works',
					'borrowbox_extract',
					'/sys/BorrowBox/BorrowBoxExtractLogEntry.php',
					'BorrowBoxExtractLogEntry',
					'/sys/BorrowBox/BorrowBoxSetting.php',
					'BorrowBoxSetting'
				)",
			]
		], //borrowbox_module
		'borrowbox_permissions' => [
			'title' => 'BorrowBox Permissions',
			'description' => 'Add permissions for BorrowBox administration',
			'continueOnError' => false,
			'sql' => [
				"INSERT INTO permissions (
					sectionName,
					name,
					requiredModule,
					weight,
					description
				) VALUES (
					'Cataloging & eContent',
					'Administer BorrowBox',
					'BorrowBox',
					10,
					'Allows the user to administer BorrowBox integration settings.'
				)",
				"INSERT INTO role_permissions (
					roleId,
					permissionId
				) VALUES (
					(SELECT roleId from roles where name = 'opacAdmin'),
					(SELECT id from permissions where name = 'Administer BorrowBox')
				)",
			]
		], //borrowbox_permissions
		'borrowbox_settings' => [
			'title' => 'BorrowBox Settings',
			'description' => 'Create settings table for BorrowBox integration',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS borrowbox_settings (
					id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(125) NOT NULL DEFAULT 'BorrowBox',
					apiUrl VARCHAR(255) NOT NULL DEFAULT 'https://api.borrowbox.net',
					apiUsername VARCHAR(255),
					apiPassword VARCHAR(256),
					runFullUpdate TINYINT(1) DEFAULT 0,
					allowLargeDeletes TINYINT(1) DEFAULT 1,
					lastUpdateOfChangedRecords INT(11) DEFAULT 0,
					lastUpdateOfAllRecords INT(11) DEFAULT 0,
					enableRequestLogging TINYINT(1) DEFAULT 0,
					numRetriesOnError INT(11) DEFAULT 1,
					productsToUpdate TEXT DEFAULT NULL
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
			]
		], //borrowbox_settings
		'borrowbox_scopes' => [
			'title' => 'BorrowBox Scopes',
			'description' => 'Create scopes table for BorrowBox integration',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS borrowbox_scopes (
					id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					settingId INT(11) NOT NULL,
					name VARCHAR(50) NOT NULL,
					includeAdult TINYINT(1) DEFAULT 1,
					includeTeen TINYINT(1) DEFAULT 1,
					includeKids TINYINT(1) DEFAULT 1,
					CONSTRAINT fk_borrowbox_scopes_setting FOREIGN KEY (settingId) REFERENCES borrowbox_settings(id) ON DELETE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
			]
		], //borrowbox_scopes
		'library_borrowbox_scope' => [
			'title' => 'Library BorrowBox Scope',
			'description' => 'Create library to BorrowBox scope association table',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS library_borrowbox_scope (
					id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					scopeId INT(11) NOT NULL,
					libraryId INT(11) NOT NULL,
					weight INT(11) NOT NULL DEFAULT 1,
					UNIQUE KEY libraryId (libraryId, scopeId),
					CONSTRAINT fk_library_borrowbox_scope_scope FOREIGN KEY (scopeId) REFERENCES borrowbox_scopes(id) ON DELETE CASCADE,
					CONSTRAINT fk_library_borrowbox_scope_library FOREIGN KEY (libraryId) REFERENCES library(libraryId) ON DELETE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
			]
		], //library_borrowbox_scope
		'location_borrowbox_scope' => [
			'title' => 'Location BorrowBox Scope',
			'description' => 'Create location to BorrowBox scope association table',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS location_borrowbox_scope (
					id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					scopeId INT(11) NOT NULL,
					locationId INT(11) NOT NULL,
					weight INT(11) NOT NULL DEFAULT 1,
					UNIQUE KEY locationId (locationId, scopeId),
					CONSTRAINT fk_location_borrowbox_scope_scope FOREIGN KEY (scopeId) REFERENCES borrowbox_scopes(id) ON DELETE CASCADE,
					CONSTRAINT fk_location_borrowbox_scope_location FOREIGN KEY (locationId) REFERENCES location(locationId) ON DELETE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
			]
		], //location_borrowbox_scope
		'library_borrowbox_settings' => [
			'title' => 'Library BorrowBox Settings',
			'description' => 'Create per-library BorrowBox settings table',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS library_borrowbox_settings (
					id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					weight INT(11) DEFAULT 0,
					settingId INT(11) NOT NULL,
					libraryId INT(11) NOT NULL,
					siteId VARCHAR(50),
					circulationEnabled TINYINT(1) DEFAULT 1,
					UNIQUE KEY settingId (settingId, libraryId),
					CONSTRAINT fk_library_borrowbox_settings_setting FOREIGN KEY (settingId) REFERENCES borrowbox_settings(id) ON DELETE CASCADE,
					CONSTRAINT fk_library_borrowbox_settings_library FOREIGN KEY (libraryId) REFERENCES library(libraryId) ON DELETE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
			]
		], //library_borrowbox_settings
		'borrowbox_api_products' => [
			'title' => 'BorrowBox API Products',
			'description' => 'Create table for BorrowBox product catalog',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS borrowbox_api_products (
					id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					borrowboxId VARCHAR(50) NOT NULL,
					isbn13 VARCHAR(13),
					mediaType VARCHAR(50),
					title VARCHAR(512),
					subtitle VARCHAR(255),
					series VARCHAR(255),
					seriesNumber INT(11),
					primaryCreatorName VARCHAR(215),
					cover VARCHAR(500),
					dateAdded INT(11),
					dateUpdated INT(11),
					lastMetadataCheck INT(11),
					lastMetadataChange INT(11),
					deleted TINYINT(1) DEFAULT 0,
					dateDeleted INT(11),
					lastSeen INT(11) DEFAULT 0,
					UNIQUE KEY borrowboxId (borrowboxId),
					KEY dateUpdated (dateUpdated),
					KEY lastMetadataCheck (lastMetadataCheck),
					KEY deleted (deleted),
					KEY lastSeen (lastSeen)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
			]
		], //borrowbox_api_products
		'borrowbox_api_product_metadata' => [
			'title' => 'BorrowBox API Product Metadata',
			'description' => 'Create table for BorrowBox product metadata',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS borrowbox_api_product_metadata (
					id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					productId INT(11) NOT NULL,
					checksum VARCHAR(40),
					publisher VARCHAR(255),
					releaseDate BIGINT,
					summary TEXT,
					cover VARCHAR(500),
					rawData MEDIUMBLOB,
					UNIQUE KEY productId (productId),
					CONSTRAINT fk_borrowbox_metadata_product FOREIGN KEY (productId) REFERENCES borrowbox_api_products(id) ON DELETE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
			]
		], //borrowbox_api_product_metadata
		'borrowbox_api_product_availability' => [
			'title' => 'BorrowBox API Product Availability',
			'description' => 'Create table for BorrowBox product availability by site',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS borrowbox_api_product_availability (
					id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					productId INT(11) NOT NULL,
					settingId INT(11) NOT NULL,
					borrowboxId VARCHAR(50),
					siteId VARCHAR(50) NOT NULL,
					availabilityStatus VARCHAR(20),
					nextAvailableDate INT(11),
					UNIQUE KEY productId (productId, settingId, siteId),
					CONSTRAINT fk_borrowbox_availability_product FOREIGN KEY (productId) REFERENCES borrowbox_api_products(id) ON DELETE CASCADE,
					CONSTRAINT fk_borrowbox_availability_setting FOREIGN KEY (settingId) REFERENCES borrowbox_settings(id) ON DELETE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
			]
		], //borrowbox_api_product_availability
		'borrowbox_stats' => [
			'title' => 'BorrowBox Stats',
			'description' => 'Create statistics table for BorrowBox',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS borrowbox_stats (
					id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					instance VARCHAR(100),
					year INT(4),
					month INT(2),
					day INT(2),
					numCheckouts INT(11) DEFAULT 0,
					numFailedCheckouts INT(11) DEFAULT 0,
					numRenewals INT(11) DEFAULT 0,
					numEarlyReturns INT(11) DEFAULT 0,
					numHoldsPlaced INT(11) DEFAULT 0,
					numFailedHolds INT(11) DEFAULT 0,
					numHoldsCancelled INT(11) DEFAULT 0,
					numApiErrors INT(11) DEFAULT 0,
					numConnectionFailures INT(11) DEFAULT 0,
					KEY instance (instance, year, month, day)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
			]
		], //borrowbox_stats
		'user_borrowbox_usage' => [
			'title' => 'User BorrowBox Usage',
			'description' => 'Create table for tracking per-user BorrowBox usage',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS user_borrowbox_usage (
					id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					instance VARCHAR(100),
					userId INT(11) NOT NULL,
					year INT(4),
					month INT(2),
					day INT(2),
					usageCount INT(11) DEFAULT 0,
					UNIQUE KEY instance (instance, userId, year, month, day),
					KEY year (year, month, day),
					CONSTRAINT fk_user_borrowbox_usage_user FOREIGN KEY (userId) REFERENCES user(id) ON DELETE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
			]
		], //user_borrowbox_usage
		'borrowbox_record_usage' => [
			'title' => 'BorrowBox Record Usage',
			'description' => 'Create table for tracking per-record BorrowBox usage',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS borrowbox_record_usage (
					id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					instance VARCHAR(100),
					borrowboxId VARCHAR(50) NOT NULL,
					year INT(4),
					month INT(2),
					day INT(2),
					timesHeld INT(11) DEFAULT 0,
					timesCheckedOut INT(11) DEFAULT 0,
					UNIQUE KEY instance (instance, borrowboxId, year, month, day),
					KEY year (year, month, day)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
			]
		], //borrowbox_record_usage
		'borrowbox_extract_log' => [
			'title' => 'BorrowBox Extract Log',
			'description' => 'Create extract log table for BorrowBox indexing',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS borrowbox_extract_log (
					id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					settingId INT(11) NOT NULL,
					startTime INT(11) NOT NULL,
					endTime INT(11),
					lastUpdate INT(11),
					notes TEXT,
					numProducts INT(11) DEFAULT 0,
					numErrors INT(11) DEFAULT 0,
					numAdded INT(11) DEFAULT 0,
					numDeleted INT(11) DEFAULT 0,
					numUpdated INT(11) DEFAULT 0,
					numSkipped INT(11) DEFAULT 0,
					numAvailabilityChanges INT(11) DEFAULT 0,
					numMetadataChanges INT(11) DEFAULT 0,
					numInvalidRecords INT(11) DEFAULT 0,
					KEY startTime (startTime)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
			]
		], //borrowbox_extract_log

	];
}