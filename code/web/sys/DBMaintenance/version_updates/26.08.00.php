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
		'increase_user_username_column' => [
			'title' => 'Increase username column in user table',
			'description' => 'Increase username column in user table',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE user CHANGE COLUMN username username VARCHAR(255) NOT NULL',
			]
		], //name

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
		'display_sort_term_values' => [
			'title' => 'Display Sort Term Values',
			'description' => 'Add configuration option to dynamically show sort term values for total checkouts, date added, number of holds, and call number.',
			'sql' => [
				"ALTER TABLE grouped_work_display_settings ADD displaySortTermValue TINYINT(1) DEFAULT 0",
			],
		], //display_sort_term_values
		'localhop_images' => [
			'title' => 'LocalHop Images',
			'description' => 'Add setting for toggling use of LocalHop images for event covers',
			'sql' => [
				'ALTER TABLE localhop_settings ADD COLUMN useLocalHopImages tinyint(1) NOT NULL DEFAULT 0',
			]
		], //localhop_images

		//yanjun

		//imani

		//galen

		//chloe

		//pedro

		//mark j

		//lucas

		//tomas

		// stephen
		'permissions_edit_payment_status' => [
			'title' => 'Create Edit Payment Status permission',
			'description' => 'Adds a permission to edit statuses in eCommerce reports.',
			'continueOnError' => false,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('eCommerce', 'Edit Payment Status', '', 10, 'Allows the user to manually update payment statuses')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Edit Payment Status'))",
			]
		], //permissions_edit_payment_status
		'add_edited_status_to_user_payments' => [
			'title' => 'Add editedStatus to user_payments table',
			'description' => 'Adds a column to store a manually edited payment status.',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE user_payments ADD COLUMN editedStatus VARCHAR(20) NOT NULL DEFAULT ''",
			]
		], //add_edited_status_to_user_payments

		//other

	];
}
