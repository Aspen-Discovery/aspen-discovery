<?php
/** @noinspection PhpUnused */
function getUpdates21_07_00(): array {
	return [
		'indexing_profiles_add_notes_subfield' => [
			'title' => 'Indexing Profile add notes subfield',
			'description' => 'Add Notes Subfield to Indexing Profile',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE indexing_profiles ADD COLUMN noteSubfield CHAR(1) default ' '",
				"UPDATE indexing_profiles SET noteSubfield = 'z' WHERE catalogDriver = 'Koha'",
			],
		],
		//indexing_profiles_add_notes_subfield
		'indexing_profiles_add_due_date_for_Koha' => [
			'title' => 'Indexing Profile set dueDate for Koha',
			'description' => 'Add Due Date Subfield to Indexing Profile for Koha',
			'continueOnError' => true,
			'sql' => [
				"UPDATE indexing_profiles SET dueDate = 'k' WHERE catalogDriver = 'Koha'",
			],
		],
		//indexing_profiles_add_due_date_for_Koha
		'browse_categories_add_startDate_endDate' => [
			'title' => 'Add startDate and endDate to Browse Categories',
			'description' => 'Add startDate and endDate to Browse Categories',
			'sql' => [
				"ALTER TABLE browse_category ADD COLUMN startDate INT(11) DEFAULT 0",
				"ALTER TABLE browse_category ADD COLUMN endDate INT(11) DEFAULT 0",
			],
		],
		//browse_categories_add_startDate_endDate
		'cloud_library_multiple_scopes' => [
			'title' => 'Cloud Library Multiple Scopes',
			'description' => 'Allow multiple scopes to be provided for locations and libraries',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE library_cloud_library_scope (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					scopeId INT NOT NULL,
					libraryId INT NOT NULL,
					unique (libraryId, scopeId)
				) ENGINE InnoDB',
				'CREATE TABLE location_cloud_library_scope (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					scopeId INT NOT NULL,
					locationId INT NOT NULL,
					unique (locationId, scopeId)
				) ENGINE InnoDB',
				'INSERT INTO library_cloud_library_scope (scopeId, libraryId) SELECT cloudLibraryScopeId, libraryId from library where cloudLibraryScopeId != -1',
				'INSERT INTO location_cloud_library_scope (scopeId, locationId) SELECT cloudLibraryScopeId, locationId from location where cloudLibraryScopeId > 0',
				'INSERT INTO location_cloud_library_scope (scopeId, locationId) SELECT library.cloudLibraryScopeId, locationId from location inner join library on location.libraryId = library.libraryId where location.cloudLibraryScopeId = -1 and library.cloudLibraryScopeId != -1',
				'ALTER TABLE library DROP COLUMN cloudLibraryScopeId',
				'ALTER TABLE location DROP COLUMN cloudLibraryScopeId',
			],
		],
		//cloud_library_multiple_scopes
		'indexing_profiles_date_created_polaris' => [
			'title' => 'Indexing Profile set date created for Polaris',
			'description' => 'Add Date Created Subfield to Indexing Profile for Polaris',
			'continueOnError' => true,
			'sql' => [
				"UPDATE indexing_profiles SET dateCreated = 'e' WHERE indexingClass = 'Polaris'",
				"UPDATE indexing_profiles SET dateCreatedFormat = 'yyyy-MM-dd' WHERE indexingClass = 'Polaris'",
			],
		],
		//indexing_profiles_date_created_polaris
		'library_workstation_id_polaris' => [
			'title' => 'Library - Workstation ID',
			'description' => 'Allow Workstation ID to defined at the library level',
			'sql' => [
				"ALTER TABLE library ADD column workstationId VARCHAR(10) DEFAULT ''",
			],
		],
		//library_workstation_id_polaris
		'regroup_21_07' => [
			'title' => 'Regroup all records for 21.07',
			'description' => 'Regroup all records for 21.07',
			'sql' => [
				'UPDATE indexing_profiles set regroupAllRecords = 1',
			],
		],
		//regroup_21_07
		'syndetics_unbound_account_number' => [
			'title' => 'Syndetics Unbound Account Number',
			'description' => 'Add Syndetics Unbound Account Number ',
			'sql' => [
				'ALTER TABLE syndetics_settings ADD COLUMN unboundAccountNumber INT DEFAULT NULL',
			],
		],
		//syndetics_unbound_account_number
		'amazon_ses' => [
			'title' => 'Add Amazon SES information',
			'description' => 'Add the ability to send email via Amazon SES',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE amazon_ses_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					fromAddress VARCHAR(255),
					accessKeyId VARCHAR(50),
					accessKeySecret VARCHAR(256),
					singleMailConfigSet VARCHAR(50),
					bulkMailConfigSet VARCHAR(50),
					region VARCHAR(20)
				) ENGINE INNODB',
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('System Administration', 'Administer Amazon SES', '', 29, 'Controls if the user can change Amazon SES settings. <em>This has potential security and cost implications.</em>')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Amazon SES'))",
			],
		],
		//amazon_ses
		'increase_showInSearchResultsMainDetails_length' => [
			'title' => 'increase showInSearchResultsMainDetails length',
			'description' => 'Increase the column length for showInSearchResultsMainDetails',
			'sql' => [
				"ALTER TABLE grouped_work_display_settings CHANGE COLUMN showInSearchResultsMainDetails showInSearchResultsMainDetails VARCHAR(512) NULL DEFAULT 'a:5:{i:0;s:10:\"showSeries\";i:1;s:13:\"showPublisher\";i:2;s:19:\"showPublicationDate\";i:3;s:13:\"showLanguages\";i:4;s:10:\"showArInfo\";}'",
			],
		],
		//increase_showInSearchResultsMainDetails_length
//		'21_07_00_full_extract_for_koha' => [
//			'title' => 'Reindex all records for 21.07',
//			'description' => 'Reindex all records for 21.07',
//			'sql' => [
//				"UPDATE indexing_profiles set runFullUpdate = 1 where indexingClass = 'Koha'"
//			]
//		], //21_07_00_full_extract_for_koha
		'upload_list_cover_permissions' => [
			'title' => 'Additional Permission to Upload List Covers',
			'description' => 'Additional Permission to Upload List Covers',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('User Lists', 'Upload List Covers', '', 1, 'Allows users to upload covers for a list.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'opacAdmin'), (SELECT id from permissions where name='Upload List Covers'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name = 'cataloging'), (SELECT id from permissions where name='Upload List Covers'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name = 'superCataloger'), (SELECT id from permissions where name='Upload List Covers'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name = 'listPublisher'), (SELECT id from permissions where name='Upload List Covers'))",
			],
		],
		//upload_list_cover_permissions
		'remove_library_themeName' => [
			'title' => 'Remove Library Theme Name',
			'description' => 'Remove unused library theme name',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE library drop column themeName",
			],
		],
		//remove_library_themeName
		'library_field_level_permissions' => [
			'title' => 'Library Field Level Permissions',
			'description' => 'Add permissions to control access to fields within the library configuration',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration - Library Fields', 'Library Domain Settings', '', 1, 'Configure Library fields related to URLs and base configuration to access Aspen.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration - Library Fields', 'Library Theme Configuration', '', 3, 'Configure Library fields related to how theme display is configured for the library.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration - Library Fields', 'Library Contact Settings', '', 6, 'Configure Library fields related to contact information for the library.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration - Library Fields', 'Library ILS Connection', '', 9, 'Configure Library fields related to how Aspen connects to the ILS and settings that depend on how the ILS is configured.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration - Library Fields', 'Library ILS Options', '', 12, 'Configure Library fields related to how Aspen interacts with the ILS.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration - Library Fields', 'Library Self Registration', '', 15, 'Configure Library fields related to how Self Registration is configured in Aspen.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration - Library Fields', 'Library eCommerce Options', '', 18, 'Configure Library fields related to how eCommerce is configured in Aspen.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration - Library Fields', 'Library Catalog Options', '', 21, 'Configure Library fields related to how Catalog results and searching is configured in Aspen.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration - Library Fields', 'Library Browse Category Options', '', 24, 'Configure Library fields related to how browse categories are configured in Aspen.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration - Library Fields', 'Library Materials Request Options', '', 27, 'Configure Library fields related to how materials request is configured in Aspen.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration - Library Fields', 'Library ILL Options', '', 30, 'Configure Library fields related to how ill is configured in Aspen.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration - Library Fields', 'Library Records included in Catalog', '', 33, 'Configure Library fields related to what materials (physical and eContent) are included in the Aspen Catalog.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration - Library Fields', 'Library Genealogy Content', '', 36, 'Configure Library fields related to genealogy content.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration - Library Fields', 'Library Islandora Archive Options', '', 39, 'Configure Library fields related to Islandora based archive.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration - Library Fields', 'Library Open Archive Options', '', 42, 'Configure Library fields related to open archives content.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration - Library Fields', 'Library Web Builder Options', '', 45, 'Configure Library fields related to web builder content.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration - Library Fields', 'Library EDS Options', '', 48, 'Configure Library fields related to EDS content.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration - Library Fields', 'Library Holidays', '', 51, 'Configure Library holidays.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration - Library Fields', 'Library Menu', '', 54, 'Configure Library menu.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'opacAdmin'), (SELECT id from permissions where name='Library Domain Settings'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'opacAdmin'), (SELECT id from permissions where name='Library Theme Configuration'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'opacAdmin'), (SELECT id from permissions where name='Library Contact Settings'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'opacAdmin'), (SELECT id from permissions where name='Library ILS Connection'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'opacAdmin'), (SELECT id from permissions where name='Library ILS Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'opacAdmin'), (SELECT id from permissions where name='Library Self Registration'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'opacAdmin'), (SELECT id from permissions where name='Library eCommerce Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'opacAdmin'), (SELECT id from permissions where name='Library Catalog Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'opacAdmin'), (SELECT id from permissions where name='Library Browse Category Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'opacAdmin'), (SELECT id from permissions where name='Library ILL Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'opacAdmin'), (SELECT id from permissions where name='Library Records included in Catalog'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'opacAdmin'), (SELECT id from permissions where name='Library Genealogy Content'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'opacAdmin'), (SELECT id from permissions where name='Library Islandora Archive Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'opacAdmin'), (SELECT id from permissions where name='Library Web Builder Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'opacAdmin'), (SELECT id from permissions where name='Library EDS Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'opacAdmin'), (SELECT id from permissions where name='Library Holidays'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'opacAdmin'), (SELECT id from permissions where name='Library Menu'))",

				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryAdmin'), (SELECT id from permissions where name='Library Theme Configuration'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryAdmin'), (SELECT id from permissions where name='Library Contact Settings'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryAdmin'), (SELECT id from permissions where name='Library ILS Connection'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryAdmin'), (SELECT id from permissions where name='Library ILS Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryAdmin'), (SELECT id from permissions where name='Library Self Registration'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryAdmin'), (SELECT id from permissions where name='Library eCommerce Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryAdmin'), (SELECT id from permissions where name='Library Catalog Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryAdmin'), (SELECT id from permissions where name='Library Browse Category Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryAdmin'), (SELECT id from permissions where name='Library ILL Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryAdmin'), (SELECT id from permissions where name='Library Records included in Catalog'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryAdmin'), (SELECT id from permissions where name='Library Genealogy Content'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryAdmin'), (SELECT id from permissions where name='Library Islandora Archive Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryAdmin'), (SELECT id from permissions where name='Library Web Builder Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryAdmin'), (SELECT id from permissions where name='Library EDS Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryAdmin'), (SELECT id from permissions where name='Library Holidays'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryAdmin'), (SELECT id from permissions where name='Library Menu'))",

				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryManager'), (SELECT id from permissions where name='Library Contact Settings'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryManager'), (SELECT id from permissions where name='Library ILS Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryManager'), (SELECT id from permissions where name='Library Catalog Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryManager'), (SELECT id from permissions where name='Library Browse Category Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryManager'), (SELECT id from permissions where name='Library Holidays'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryManager'), (SELECT id from permissions where name='Library Menu'))",

			],
		],
		//library_field_level_permissions
		'add_title_user_list_entry' => [
			'title' => 'Add title column to user list entries',
			'description' => 'Add title column to user list entries',
			'sql' => [
				"ALTER TABLE user_list_entry ADD column title VARCHAR(50) DEFAULT ''",
			],
		],
		//add_title_user_list_entry
		'add_titles_to_user_list_entry' => [
			'title' => 'Add titles to user list entries',
			'description' => 'Populate existing user list entries with titles',
			'sql' => [
				"UPDATE user_list_entry SET user_list_entry.title=(SELECT LEFT(grouped_work.full_title, 50) FROM grouped_work WHERE grouped_work.permanent_id = user_list_entry.sourceId)",
			],
		],
		//add_titles_to_user_list_entry
		'location_field_level_permissions' => [
			'title' => 'Location Field Level Permissions',
			'description' => 'Add permissions to control access to fields within the location configuration',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration - Location Fields', 'Location Domain Settings', '', 1, 'Configure Location fields related to URLs and base configuration to access Aspen.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration - Location Fields', 'Location Theme Configuration', '', 3, 'Configure Location fields related to how theme display is configured for the library.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration - Location Fields', 'Location Address and Hours Settings', '', 6, 'Configure Location fields related to the address and hours of operation.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration - Location Fields', 'Location ILS Connection', '', 9, 'Configure Location fields related to how Aspen connects to the ILS and settings that depend on how the ILS is configured.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration - Location Fields', 'Location ILS Options', '', 12, 'Configure Location fields related to how Aspen interacts with the ILS.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration - Location Fields', 'Location Catalog Options', '', 15, 'Configure Location fields related to how Catalog results and searching is configured in Aspen.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration - Location Fields', 'Location Browse Category Options', '', 18, 'Configure Location fields related to how Catalog results and searching is configured in Aspen.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration - Location Fields', 'Location Records included in Catalog', '', 21, 'Configure Location fields related to what materials (physical and eContent) are included in the Aspen Catalog.')",

				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'opacAdmin'), (SELECT id from permissions where name='Location Domain Settings'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'opacAdmin'), (SELECT id from permissions where name='Location Theme Configuration'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'opacAdmin'), (SELECT id from permissions where name='Location Address and Hours Settings'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'opacAdmin'), (SELECT id from permissions where name='Location ILS Connection'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'opacAdmin'), (SELECT id from permissions where name='Location ILS Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'opacAdmin'), (SELECT id from permissions where name='Location Catalog Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'opacAdmin'), (SELECT id from permissions where name='Location Browse Category Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'opacAdmin'), (SELECT id from permissions where name='Location Records included in Catalog'))",

				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryAdmin'), (SELECT id from permissions where name='Location Theme Configuration'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryAdmin'), (SELECT id from permissions where name='Location Address and Hours Settings'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryAdmin'), (SELECT id from permissions where name='Location ILS Connection'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryAdmin'), (SELECT id from permissions where name='Location ILS Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryAdmin'), (SELECT id from permissions where name='Location Catalog Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryAdmin'), (SELECT id from permissions where name='Location Browse Category Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryAdmin'), (SELECT id from permissions where name='Location Records included in Catalog'))",

				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryManager'), (SELECT id from permissions where name='Location Address and Hours Settings'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryManager'), (SELECT id from permissions where name='Location ILS Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryManager'), (SELECT id from permissions where name='Location Catalog Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryManager'), (SELECT id from permissions where name='Location Browse Category Options'))",

				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'locationManager'), (SELECT id from permissions where name='Location Address and Hours Settings'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'locationManager'), (SELECT id from permissions where name='Location Catalog Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'locationManager'), (SELECT id from permissions where name='Location Browse Category Options'))",
			],
		],
		//location_field_level_permissions
		'remove_loan_rules' => [
			'title' => 'Remove Loan Rules and Loan Rule Determiners',
			'description' => 'Remove unused Loan Rules and Loan Rule Determiners',
			'continueOnError' => true,
			'sql' => [
				"DROP TABLE loan_rules",
				"DROP TABLE loan_rule_determiners",
			],
		],
		//remove_loan_rules
		'populate_list_entry_titles' => [
			'title' => 'Populate List Entry Titles',
			'description' => 'Update existing list entries with the title of the item',
			'sql' => [
				'populateListEntryTitles',
			],
		],
	];
}

function populateListEntryTitles() {
	ini_set('memory_limit', '2G');
	require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
	$userListEntries = new UserListEntry();
	$userListEntries->find();
	$allUserListEntries = [];
	while ($userListEntries->fetch()) {
		$allUserListEntries[] = clone $userListEntries;
	}

	foreach ($allUserListEntries as $index => $userListEntry) {
		if ((is_null($userListEntry->title)) || ($userListEntry->title == '')) {
			if ($userListEntry->source == 'Lists') {
				require_once ROOT_DIR . '/sys/UserLists/UserList.php';
				$list = new UserList();
				$list->id = $userListEntry->sourceId;
				if ($list->find(true)) {
					$userListEntry->title = substr($list->title, 0, 50);
					$userListEntry->update();
				}
			} elseif ($userListEntry->source == 'OpenArchives') {
				require_once ROOT_DIR . '/RecordDrivers/OpenArchivesRecordDriver.php';
				$recordDriver = new OpenArchivesRecordDriver($userListEntry->sourceId);
				if ($recordDriver->isValid()) {
					$title = $recordDriver->getTitle();
					$userListEntry->title = substr($title, 0, 50);
					$userListEntry->update();
				}
			} elseif ($userListEntry->source == 'Genealogy') {
				require_once ROOT_DIR . '/sys/Genealogy/Person.php';
				$person = new Person();
				$person->personId = $userListEntry->sourceId;
				if ($person->find(true)) {
					$userListEntry->title = substr($person->firstName . $person->middleName . $person->lastName, 0, 50);
					$userListEntry->update();
				}
			} elseif ($userListEntry->source == 'EbscoEds') {
				require_once ROOT_DIR . '/RecordDrivers/EbscoRecordDriver.php';
				$recordDriver = new EbscoRecordDriver($userListEntry->sourceId);
				if ($recordDriver->isValid()) {
					$title = $recordDriver->getTitle();
					$userListEntry->title = substr($title, 0, 50);
					$userListEntry->update();
				}
			}
		}
	}
}

