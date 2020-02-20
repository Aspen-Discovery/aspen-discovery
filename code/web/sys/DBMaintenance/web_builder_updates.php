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
		//TODO: Add roles
		//TODO: Add library to pages
		//TODO: Upload of files

	];
}