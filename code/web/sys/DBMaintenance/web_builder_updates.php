<?php
function getWebBuilderUpdates(){
	return [
		'web_builder_module' => [
			'title' => 'Web Builder Module',
			'description' => 'Create Web Builder Module',
			'sql' => [
				"INSERT INTO modules (name, indexName, backgroundProcess) VALUES ('Web Builder', 'web_builder', '')",
			]
		],

		'web_builder_basic_pages' => [
			'title' => 'Web Builder Basic Pages',
			'description' => 'Setup Basic Pages within Web Builder',
			'sql' => [
				"CREATE TABLE web_builder_basic_page  (
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					title VARCHAR(100) NOT NULL,
					urlAlias VARCHAR(100),
					showSidebar TINYINT(1),
					contents MEDIUMTEXT
				) ENGINE=INNODB"
			]
		],

		'web_builder_basic_page_teaser' => [
			'title' => 'Web Builder Basic Page Teaser',
			'description' => 'Add Teaser to Basic Page',
			'sql' => [
				'ALTER TABLE web_builder_basic_page ADD COLUMN teaser VARCHAR(512)'
			]
		],

		'web_builder_menu' => [
			'title' => 'Web Builder Menu',
			'description' => 'Setup Menu for the Web Builder',
			'sql' => [
				"CREATE TABLE web_builder_menu (
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					label VARCHAR(50) NOT NULL,
					parentMenuId INT(11) DEFAULT -1,
					url VARCHAR(255),
					INDEX (parentMenuId)
				) ENGINE=INNODB",
			]
		],

		'web_builder_menu_sorting' => [
			'title' => 'Web Builder Menu Sorting',
			'description' => 'Add a weight to the Web Builder',
			'sql' => [
				"ALTER TABLE web_builder_menu ADD COLUMN weight INT DEFAULT 0",
			]
		],

		'staff_members' => [
			'title' => 'Staff Members',
			'description' => 'Add staff members so we can automatically display a directory',
			'sql' => [
				"CREATE TABLE staff_members (
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(100),
					role VARCHAR(100),
					email VARCHAR(255),
					phone VARCHAR(13),
					libraryId INT(11),
					photo VARCHAR(255),
					description MEDIUMTEXT
				) ENGINE INNODB"
			],
		],

		'web_builder_portal' => [
			'title' => 'Web Builder Portal',
			'description' => 'Setup tables to create portal pages',
			'sql' => [
				'CREATE TABLE web_builder_portal_page (
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					title VARCHAR(255),
					urlAlias VARCHAR(100),
					showSidebar TINYINT(1)
				) ENGINE INNODB',
				'CREATE TABLE web_builder_portal_row(
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					portalPageId INT(11),
					rowTitle VARCHAR(255),
					INDEX (portalPageId)
				) ENGINE INNODB',
				'CREATE TABLE web_builder_portal_cell(
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					portalRowId INT(11),
					widthTiny INT,
					widthXs INT,
					widthSm INT,
					widthMd INT,
					widthLg INT,
					horizontalJustification VARCHAR(20),
					verticalAlignment VARCHAR(20),
					sourceType VARCHAR(30),
					sourceId VARCHAR(30),
					INDEX (portalRowId)
				)'
			]
		],
		'web_builder_portal_weights' => [
			'title' => 'Web Builder Portal Weights',
			'description' => 'Add weights to Portal Rows and cells',
			'sql' => [
				'ALTER TABLE web_builder_portal_row ADD COLUMN weight INT DEFAULT 0',
				'ALTER TABLE web_builder_portal_cell ADD COLUMN weight INT DEFAULT 0'
			]
		],
		//TODO: Add roles
		//TODO: Add library to pages for scoping
		//TODO: Upload of files

	];
}