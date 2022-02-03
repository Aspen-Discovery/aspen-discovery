<?php
/** @noinspection PhpUnused */
function getUpdates22_02_00() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'aspen_lida_settings' => [
			'title' => 'Add settings for library branded Aspen LiDA',
			'description' => 'Add settings for library branded Aspen LiDA',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS aspen_lida_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					slugName VARCHAR(50) UNIQUE
				) ENGINE INNODB',
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration', 'Administer Aspen LiDA Settings', '', 10, 'Controls if the user can change Aspen LiDA settings.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Aspen LiDA Settings'))",
			]
		], //aspen_lida_settings
		'aspen_lida_settings_2' => [
			'title' => 'Add additional settings for library branded Aspen LiDA',
			'description' => 'Add additional library settings for library branded Aspen LiDA',
			'sql' => [
				'ALTER TABLE aspen_lida_settings ADD COLUMN logoLogin VARCHAR(100)',
				'ALTER TABLE aspen_lida_settings ADD COLUMN privacyPolicy VARCHAR(255)',
				'CREATE TABLE IF NOT EXISTS aspen_lida_quick_searches (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					libraryId INT(11) DEFAULT -1,
					weight INT NOT NULL DEFAULT 0,
					searchTerm VARCHAR(500) NOT NULL,
					label VARCHAR(50) NOT NULL
				) ENGINE INNODB',
				'ALTER TABLE themes ADD COLUMN logoApp VARCHAR(100)',
				'ALTER TABLE aspen_lida_settings ADD COLUMN logoSplash VARCHAR(100)',
				'ALTER TABLE aspen_lida_settings ADD COLUMN logoAppIcon VARCHAR(100)'
			]
		], //aspen_lida_settings_2
		'open_archives_multiple_imageRegex' => [
			'title' => 'Open Archives Multiple imageRegex',
			'description' => 'Allow multiple Image Regular Expressions to be defined for an Open Archives Collection',
			'sql' => [
				"ALTER TABLE open_archives_collection CHANGE COLUMN imageRegex imageRegex TEXT"
			]
		], //open_archives_multiple_imageRegex
		'aspen_lida_settings_3' => [
			'title' => 'Add additional settings for library branded Aspen LiDA',
			'description' => 'Add additional library settings for library branded Aspen LiDA',
			'sql' => [
				'ALTER TABLE aspen_lida_settings ADD COLUMN showFavicons INT(1) DEFAULT 0',
			]
		], //aspen_lida_settings_3
		'records_to_exclude_increase_length' => [
			'title' => 'Increase the length of records to exclude',
			'description' => 'Make records to exclude fields longer',
			'sql' => [
				"ALTER TABLE library_records_owned CHANGE COLUMN locationsToExclude locationsToExclude VARCHAR(200) NOT NULL DEFAULT ''",
				"ALTER TABLE location_records_owned CHANGE COLUMN locationsToExclude locationsToExclude VARCHAR(200) NOT NULL DEFAULT ''",
				"ALTER TABLE library_records_to_include CHANGE COLUMN locationsToExclude locationsToExclude VARCHAR(200) NOT NULL DEFAULT ''",
				"ALTER TABLE location_records_to_include CHANGE COLUMN locationsToExclude locationsToExclude VARCHAR(200) NOT NULL DEFAULT ''",
				"ALTER TABLE library_records_owned CHANGE COLUMN subLocationsToExclude subLocationsToExclude VARCHAR(200) NOT NULL DEFAULT ''",
				"ALTER TABLE location_records_owned CHANGE COLUMN subLocationsToExclude subLocationsToExclude VARCHAR(200) NOT NULL DEFAULT ''",
				"ALTER TABLE library_records_to_include CHANGE COLUMN subLocationsToExclude subLocationsToExclude VARCHAR(200) NOT NULL DEFAULT ''",
				"ALTER TABLE location_records_to_include CHANGE COLUMN subLocationsToExclude subLocationsToExclude VARCHAR(200) NOT NULL DEFAULT ''",
			]
		], //records_to_exclude_increase_length
		'library_systemHoldNotes' => [
			'title' => 'Library System Hold Notes',
			'description' => 'Add System Hold Notes ',
			'sql' => [
				"ALTER TABLE library ADD COLUMN systemHoldNote VARCHAR(50) DEFAULT ''",
				"ALTER TABLE library ADD COLUMN systemHoldNoteMasquerade VARCHAR(50) DEFAULT ''",
			]
		], //library_systemHoldNotes
	];
}
