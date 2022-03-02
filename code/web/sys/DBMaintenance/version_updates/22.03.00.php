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
		'search_test_search_index_multiple_terms' => [
			'title' => 'Search Test add index and multiple terms',
			'description' => 'Add search index and allow multiple terms per result ',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE grouped_work_test_search DROP INDEX searchTerm',
				"ALTER TABLE grouped_work_test_search ADD COLUMN searchIndex VARCHAR(40) DEFAULT 'Keyword'",
				'ALTER TABLE grouped_work_test_search CHANGE COLUMN searchTerm searchTerm TEXT COLLATE utf8_bin',
				'ALTER TABLE grouped_work_test_search CHANGE COLUMN notes notes TEXT',
			]
		], //search_test_search_index_multiple_terms
		'library_enableReadingHistory' => [
			'title' => 'Library - Enable Reading History',
			'description' => 'Add an option for if reading history should be enabled for a library',
			'sql' => [
				'ALTER TABLE library add COLUMN enableReadingHistory TINYINT(1) DEFAULT 1'
			]
		], //library_enableReadingHistory
		'library_citationOptions' => [
			'title' => 'Library - Citation Options',
			'description' => 'Add options for the display of Citation Style Guides',
			'sql' => [
				'ALTER TABLE library ADD COLUMN showCitationStyleGuides TINYINT(1) DEFAULT 1'
			]
		], //library_citationOptions
		'addVersionToCachedGreenhouseData' => [
			'title' => 'Add Version to Cached Greenhouse Data',
			'description' => 'Add Aspen Discovery version to cached Greenhouse data to share with LiDA',
			'sql' => [
				'ALTER TABLE greenhouse_cache ADD COLUMN version VARCHAR(25)',
			]
		], //addVersionToCachedGreenhouseData
		'pinResetRules' => [
			'title' => 'PIN Reset Rules',
			'description' => 'Define ruled for PINs to be used during the reset process',
			'sql' => [
				'ALTER TABLE library ADD column minPinLength INT default 4',
				'ALTER TABLE library ADD column maxPinLength INT default 6',
				'ALTER TABLE library ADD column onlyDigitsAllowedInPin INT default 1',
				'setPinResetRulesByILS'
			]
		], //pinResetRules
		'library_enableSavedSearches' => [
			'title' => 'Library - Enable Saved Searches',
			'description' => 'Add an option for if saved searches should be enabled for a library',
			'sql' => [
				'ALTER TABLE library add COLUMN enableSavedSearches TINYINT(1) DEFAULT 1'
			]
		], //library_enableSavedSearches
		'support_connection' => [
			'title' => 'Support - Request Tracker connection',
			'description' => 'Allow Libraries to define connection to request tracker their development priorities',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS request_tracker_connection (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					baseUrl VARCHAR(255),
					activeTicketFeed TEXT
				) ENGINE INNODB',
				'CREATE TABLE IF NOT EXISTS development_priorities (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					priority1 VARCHAR(50),
					priority2 VARCHAR(50),
					priority3 VARCHAR(50)
				) ENGINE INNODB',
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Aspen Discovery Support', 'Administer Request Tracker Connection', '', 10, 'Allows configuration of connection to the support system.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Aspen Discovery Support', 'View Active Tickets', '', 20, 'Allows display of active tickets within the support system.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Aspen Discovery Support', 'Set Development Priorities', '', 30, 'Allows setting of priorities for development.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Request Tracker Connection'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='View Active Tickets'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Set Development Priorities'))",
			]
		], //support_connection
	];
}

function setPinResetRulesByILS(){
	$ils = '';
	$accountProfiles = new AccountProfile();
	$accountProfiles->find();
	while ($accountProfiles->fetch()){
		if ($accountProfiles->ils != 'na'){
			$ils = $accountProfiles->ils;
		}
	}
	$library = new Library();
	if ($ils == 'polaris'){
		$update = 'UPDATE library set maxPinLength = 14, onlyDigitsAllowedInPin = 0';
		$library->query($update);
	}elseif ($ils == 'sierra'){
		$update = 'UPDATE library set maxPinLength = 60, onlyDigitsAllowedInPin = 0';
		$library->query($update);
	}elseif ($ils == 'symphony'){
		$update = 'UPDATE library set maxPinLength = 60, onlyDigitsAllowedInPin = 0';
		$library->query($update);
	}

}
