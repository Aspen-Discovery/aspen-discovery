<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_08_00(): array {
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
		'permissions_search_settings' => [
			'title' => 'Alter permissions for Search Settings',
			'description' => 'Create permissions for Search Settings',
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Searching', 'Administer All Search Settings', '', 0, 'Allows the user to administer search settings for all libraries.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer All Search Settings'))",
			],
		], // permissions_search_settings
		'search_settings' => [
			'title' => 'Search Settings Table',
			'description' => 'Set up table to store search settings.',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS search_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name varchar(255) NOT NULL
				)',
			],
		], // search_settings
		'search_types' => [
			'title' => 'Search Types Table',
			'description' => 'Set up table to store search types and their related settings.',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS search_types (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					searchSettingId INT NOT NULL,
					type varchar(255) NOT NULL,
					label varchar(255) NOT NULL,
					defaultLabel varchar(255) NOT NULL,
					enabled tinyint(1) NOT NULL DEFAULT 1
				)',
			],
		], // search_types
		'sort_options' => [
			'title' => 'Sort Options Table',
			'description' => 'Set up table to store sort options and their related settings.',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS sort_options (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					searchSettingId INT NOT NULL,
					type varchar(255) NOT NULL,
					label varchar(255) NOT NULL,
    				defaultLabel varchar(255) NOT NULL,
					enabled tinyint(1) NOT NULL DEFAULT 1
				)',
			],
		], // sort_options
		'search_settings_library_location_id' => [
			'title' => 'Search Settings Id',
			'description' => 'Add searchSettingId column to library and location tables',
			'sql' => [
				'ALTER TABLE library ADD COLUMN searchSettingId int(11) NOT NULL DEFAULT -1',
				'ALTER TABLE location ADD COLUMN searchSettingId int(11) NOT NULL DEFAULT -1',
			]
		], //search_settings_library_location_id

		//yanjun

		//imani

		//galen

		//chloe
	
		//pedro

		//mark j

		//lucas

		//tomas

		// stephen

		//other

	];
}
