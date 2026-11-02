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
					'eContent - BorrowBox',
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
					includeKids TINYINT(1) DEFAULT 1
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
					libraryId INT(11) NOT NULL
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
					weight INT(11) DEFAULT 0
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
					circulationEnabled TINYINT(1) DEFAULT 1
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
			]
		], //library_borrowbox_settings



	];
}