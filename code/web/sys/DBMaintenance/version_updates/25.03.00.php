<?php

function getUpdates25_03_00(): array {
	$curTime = time();
	return [
		/*'name' => [
			 'title' => '',
			 'description' => '',
			 'continueOnError' => false,
			 'sql' => [
				 ''
			 ]
		 ], //name*/

		//mark - Grove
		'make_app_icons_os_specific' => [
			'title' => 'Make App Icons OS Specific',
			'description' => 'Update settings to store separate icons per OS',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE aspen_lida_branded_settings add COLUMN logoAppIconAndroid varchar(100) DEFAULT NULL'
			]
		], //make_app_icons_os_specific

		//katherine - Grove

		//kirstien - Grove

		//kodi - Grove
		'custom_web_resource_pages_permissions' => [
			'title' => 'Custom Web Resource Page Permissions',
			'description' => 'Setup permissions for Custom Web Resource Pages',
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES
					('Web Builder', 'Administer All Custom Web Resource Pages', 'Web Builder', 152, 'Allows the user to define custom web resource pages for all libraries.'),
					('Web Builder', 'Administer Library Custom Web Resource Pages', 'Web Builder', 153, 'Allows the user to define custom web resource pages for their home library.')",
			],
		], //custom_web_resource_pages_roles
		'custom_web_resource_pages_roles_for_permissions' => [
			'title' => 'Custom Web Resource Page Roles',
			'description' => 'Setup roles for Custom Web Resource Page Permissions',
			'sql' => [
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer All Custom Web Resource Pages'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='Web Admin'), (SELECT id from permissions where name='Administer All Custom Web Resource Pages'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='Library Web Admin'), (SELECT id from permissions where name='Administer Library Custom Web Resource Pages'))"
			],
		], //custom_web_resource_pages_roles_for_permissions
		'create_custom_web_resource_page_table' => [
			'title' => 'Create Custom Web Resource Page Table',
			'description' => 'Create custom web resource page table',
			'sql' => [
				"DROP TABLE IF EXISTS web_builder_custom_web_resource_page",
				"CREATE TABLE IF NOT EXISTS web_builder_custom_web_resource_page (
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					title VARCHAR(100),
					urlAlias VARCHAR(100),
					addToIndex TINYINT(1) DEFAULT 0,
					requireLogin TINYINT(1) DEFAULT 0,
					requireLoginUnlessInLibrary TINYINT(1) DEFAULT 0,
					lastUpdate INT(11) DEFAULT 0
				) ENGINE=INNODB",
			],
		], //create_custom_web_resource_page_table
		'create_web_builder_custom_resource_page_access_table' => [
			'title' => 'Create Custom Web Resource Page Access Table',
			'description' => 'Create custom web resource page access table',
			'sql' => [
				"DROP TABLE IF EXISTS web_builder_custom_resource_page_access",
				'CREATE TABLE `web_builder_custom_resource_page_access` (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					customResourcePageId INT(11) NOT NULL,
					patronTypeId int(11) NOT NULL,
					UNIQUE KEY `customResourcePageId` (`customResourcePageId`,`patronTypeId`)
				) ENGINE=InnoDB',
			],
		], //create_web_builder_custom_resource_page_access_table
		'create_web_builder_custom_resource_page_audience_table' => [
			'title' => 'Create Custom Web Resource Page Audience Table',
			'description' => 'Create custom web resource page audience table',
			'sql' => [
				"DROP TABLE IF EXISTS web_builder_custom_web_resource_page_audience",
				'CREATE TABLE `web_builder_custom_web_resource_page_audience` (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					customResourcePageId INT(11) NOT NULL,
					audienceId int(11) NOT NULL,
					UNIQUE KEY `customResourcePageId` (`customResourcePageId`,`audienceId`)
				) ENGINE=InnoDB',
			],
		], //create_web_builder_custom_resource_page_access_table
		'create_web_builder_custom_resource_page_category_table' => [
			'title' => 'Create Custom Web Resource Page Category Table',
			'description' => 'Create custom web resource page category table',
			'sql' => [
				"DROP TABLE IF EXISTS web_builder_custom_web_resource_page_category",
				'CREATE TABLE `web_builder_custom_web_resource_page_category` (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					customResourcePageId INT(11) NOT NULL,
					categoryId int(11) NOT NULL,
					UNIQUE KEY `customResourcePageId` (`customResourcePageId`,`categoryId`)
				) ENGINE=InnoDB',
			],
		], //create_web_builder_custom_resource_page_access_table
		'create_library_web_builder_custom_web_resource_page_table' => [
			'title' => 'Create Library Web Resource Page Table',
			'description' => 'Create custom web resource page table',
			'sql' => [
				"DROP TABLE IF EXISTS library_web_builder_custom_web_resource_page",
				'CREATE TABLE IF NOT EXISTS library_web_builder_custom_web_resource_page (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					libraryId INT(11) NOT NULL,
					customResourcePageId INT(11) NOT NULL,
					INDEX libraryId(libraryId),
					INDEX customResourcePageId(customResourcePageId)
				) ENGINE INNODB',
			],
		], //create_library_web_builder_custom_web_resource_page_table
		'portal_cell_custom_image' => [
			'title' => 'Portal Cell Custom Image',
			'description' => 'Add customImage column to web_builder_portal_cell table.',
			'sql' => [
				"ALTER TABLE web_builder_portal_cell ADD COLUMN customImage VARCHAR(255) DEFAULT NULL",
			],
		], //portal_cell_custom_image
		'portal_cell_show_hide_description' => [
			'title' => 'Portal Cell Show/Hide Description for Custom Web Resource Page',
			'description' => 'Add hideDescription column to web_builder_portal_cell table.',
			'sql' => [
				"ALTER TABLE web_builder_portal_cell ADD COLUMN hideDescription TINYINT(1) DEFAULT 0",
			],
		], //portal_cell_show_hide_description

		// Leo Stoyanov - BWS

		//alexander - PTFS-Europe

		//chloe - PTFS-Europe

		//James Staub - Nashville Public Library

		//Lucas Montoya - Theke Solutions

		//other

	];
}