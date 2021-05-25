<?php
/** @noinspection PhpUnused */
function getUpdates21_07_00() : array
{
	return [
		'indexing_profiles_add_notes_subfield' => [
			'title' => 'Indexing Profile add notes subfield',
			'description' => 'Add Notes Subfield to Indexing Profile',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE indexing_profiles ADD COLUMN noteSubfield CHAR(1) default ' '",
				"UPDATE indexing_profiles SET noteSubfield = 'z' WHERE catalogDriver = 'Koha'"
			]
		],
		'indexing_profiles_add_due_date_for_Koha' => [
			'title' => 'Indexing Profile set dueDate for Koha',
			'description' => 'Add Due Date Subfield to Indexing Profile for Koha',
			'continueOnError' => true,
			'sql' => [
				"UPDATE indexing_profiles SET dueDate = 'k' WHERE catalogDriver = 'Koha'"
			]
		],
		'browse_categories_add_startDate_endDate' => [
			'title' => 'Add startDate and endDate to Browse Categories',
			'description' => 'Add startDate and endDate to Browse Categories',
			'sql' => [
				"ALTER TABLE browse_category ADD COLUMN startDate INT(11) DEFAULT 0",
				"ALTER TABLE browse_category ADD COLUMN endDate INT(11) DEFAULT 0",
			]
		],
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
				'ALTER TABLE location DROP COLUMN cloudLibraryScopeId'
			],
		],
		'indexing_profiles_date_created_polaris' => [
			'title' => 'Indexing Profile set date created for Polaris',
			'description' => 'Add Date Created Subfield to Indexing Profile for Polaris',
			'continueOnError' => true,
			'sql' => [
				"UPDATE indexing_profiles SET dateCreated = 'e' WHERE indexingClass = 'Polaris'",
				"UPDATE indexing_profiles SET dateCreatedFormat = 'yyyy-MM-dd' WHERE indexingClass = 'Polaris'",
			]
		],
		'library_workstation_id_polaris' => [
			'title' => 'Library - Workstation ID',
			'description' => 'Allow Workstation ID to defined at the library level',
			'sql' => [
				"ALTER TABLE library ADD column workstationId VARCHAR(10) DEFAULT ''"
			]
		],
		'regroup_21_07' => [
			'title' => 'Regroup all records for 21.07',
			'description' => 'Regroup all records for 21.07',
			'sql' => [
				'UPDATE indexing_profiles set regroupAllRecords = 1'
			]
		],
	];
}

