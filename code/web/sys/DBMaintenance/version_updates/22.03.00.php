<?php
/** @noinspection PhpUnused */
function getUpdates22_03_00() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'evergreen_folio_modules' => [
			'title' => 'Add modules for Evergreen and FOLIO',
			'description' => 'Add modules for Evergreen and FOLIO',
			'sql' => [
				"INSERT INTO modules (name, indexName, backgroundProcess,logClassPath,logClassName) VALUES ('Evergreen', 'grouped_works', 'evergreen_export','/sys/ILS/IlsExtractLogEntry.php', 'IlsExtractLogEntry')",
				"INSERT INTO modules (name, indexName, backgroundProcess,logClassPath,logClassName) VALUES ('FOLIO', 'grouped_works', 'folio_export','/sys/ILS/IlsExtractLogEntry.php', 'IlsExtractLogEntry')",
			]
		], //evergreen_folio_modules
		'library_displayName_length' => [
			'title' => 'Increase Library Display Name Length',
			'description' => 'Increase Library Display Name Length',
			'sql' => [
				'ALTER TABLE library change COLUMN displayName displayName VARCHAR(80) COLLATE utf8mb4_general_ci NOT NULL '
			]
		], //library_displayName_length
		'selfRegistrationZipCodeValidation' => [
			'title' => 'Increase Zip Code Validation regex',
			'description' => 'Increase Zip Code Validation regex',
			'sql' => [
				"ALTER TABLE library MODIFY validSelfRegistrationZipCodes VARCHAR(500) DEFAULT ''"
			]
		], //validSelfRegistrationZipCodes
		'search_test_settings' => [
			'title' => 'Add settings for testing grouped work searching',
			'description' => 'Add settings for testing grouped work searching',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS grouped_work_test_search (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					searchTerm VARCHAR(255) UNIQUE COLLATE utf8_bin,
					expectedGroupedWorks TEXT, 
					unexpectedGroupedWorks TEXT,
					status INT DEFAULT 0
				) ENGINE INNODB',
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Cataloging & eContent', 'Administer Grouped Work Tests', '', 200, 'Controls if the user can define and access tests of Grouped Work searches.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Grouped Work Tests'))",
			]
		], //search_test_settings
		'search_test_notes' => [
			'title' => 'Add notes to search tests',
			'description' => 'Add notes to search tests',
			'sql' => [
				'ALTER TABLE grouped_work_test_search ADD COLUMN notes VARCHAR(500)',
			]
		], //search_test_notes
	];
}
