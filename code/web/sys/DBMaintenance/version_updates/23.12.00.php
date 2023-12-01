<?php

function getUpdates23_12_00(): array {
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

		//mark - ByWater

		'disable_circulation_actions' => [
			'title' => 'Disable Circulation Actions',
			'description' => 'Add an option to disable circulation actions for a user.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE user ADD COLUMN disableCirculationActions TINYINT(1) DEFAULT 0'
			]
		], //disable_circulation_actions
		'createPalaceProjectModule' => [
			'title' => 'Create Palace Project module',
			'description' => 'Setup module for Palace Project Integration',
			'sql' => [
				"INSERT INTO modules (name, indexName, backgroundProcess,logClassPath,logClassName) VALUES ('Palace Project', 'grouped_works', 'palace_project_export','/sys/PalaceProject/PalaceProjectLogEntry.php', 'PalaceProjectLogEntry','/sys/PalaceProject/PalaceProjectSetting.php', 'PalaceProjectSetting')",
			],
		], //createPalaceProjectModule

		'createPalaceProjectSettingsAndScopes' => [
			'title' => 'Create settings and scopes for Palace Project',
			'description' => 'Create settings and scopes for Palace Project',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS palace_project_settings(
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					apiUrl VARCHAR(255),
					libraryId VARCHAR(50),
					regroupAllRecords TINYINT(1) DEFAULT 0,
					runFullUpdate TINYINT(1) DEFAULT 0,
					lastUpdateOfChangedRecords INT(11) DEFAULT 0,
					lastUpdateOfAllRecords INT(11) DEFAULT 0
				)",
				'CREATE TABLE IF NOT EXISTS palace_project_scopes (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(50) NOT NULL,
					settingId INT(11)
				) ENGINE = InnoDB',
			],
		], //createPalaceProjectSettingsAndScopes

		'library_location_palace_project_scoping' => [
			'title' => 'Library and Location Scoping of Palace Project',
			'description' => 'Add information about how to scope hoopla records',
			'sql' => [
				'ALTER TABLE library ADD COLUMN palaceProjectScopeId INT(11) default -1',
				'ALTER TABLE location ADD COLUMN palaceProjectScopeId INT(11) default -1',
			],
		], //library_location_palace_project_scoping

		'palace_project_exportLog' => [
			'title' => 'Palace Project export log',
			'description' => 'Create log for Palace Project export.',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS palace_project_export_log(
				  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of log',
				  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the run started',
				  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the run ended',
				  `lastUpdate` int(11) DEFAULT NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)',
				  `notes` mediumtext COLLATE utf8mb4_general_ci COMMENT 'Additional information about the run',
				  `numProducts` int(11) DEFAULT '0',
				  `numErrors` int(11) DEFAULT '0',
				  `numAdded` int(11) DEFAULT '0',
				  `numDeleted` int(11) DEFAULT '0',
				  `numUpdated` int(11) DEFAULT '0',
				  `numSkipped` int(11) DEFAULT '0',
				  `numChangedAfterGrouping` int(11) DEFAULT '0',
				  `numRegrouped` int(11) DEFAULT '0',
				  `numInvalidRecords` int(11) DEFAULT '0',
				  PRIMARY KEY (`id`)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",
			],
		], //palace_project_exportLog

		'palace_project_permissions' => [
			'title' => 'Palace Project permissions',
			'description' => 'Create permissions for Palace Project administration.',
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES 
						('Cataloging & eContent', 'Administer Palace Project', 'Palace Project', 155, 'Allows the user configure Palace Project integration for all libraries.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Palace Project'))",
			],
		], //palace_project_permissions

		'palace_project_titles' => [
			'title' => 'Palace Project Titles',
			'description' => 'Create table to store information about titles exported from Palace Project',
			'sql' => [
				"CREATE TABLE `palace_project_title` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `palaceProjectId` VARCHAR(50) NOT NULL,
				  `title` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
				  `rawChecksum` bigint(20) DEFAULT NULL,
				  `rawResponse` mediumblob,
				  `dateFirstDetected` bigint(20) DEFAULT NULL,
				  PRIMARY KEY (`id`),
				  UNIQUE KEY `palaceProjectId` (`palaceProjectId`)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
			],
		], //palace_project_titles

		//kirstien - ByWater

		//kodi - ByWater
		'rename_axis360_permission' => [
			'title' => 'Rename Permission: Administer Axis 360',
			'description' => 'Rename permission "Administer Axis 360" to "Administer Boundless"',
			'continueOnError' => true,
			'sql' => [
				"UPDATE permissions SET description = 'Allows the user configure Boundless integration for all libraries.' WHERE name = 'Administer Axis 360'",
				"UPDATE permissions SET name = 'Administer Boundless' WHERE name = 'Administer Axis 360'",
			]
		], //rename_axis360_permission
		'rename_axis360_module' => [
			'title' => 'Rename Axis 360 Module',
			'description' => 'Rename Axis 360 module to Boundless',
			'continueOnError' => true,
			'sql' => [
				"UPDATE modules SET name = 'Boundless' WHERE name = 'Axis 360'",
			]
		], //rename_axis360_module

		//lucas - Theke
		'show_quick_poll_results' => [
			'title' => 'Display Quick Poll Results',
			'description' => 'Allows the user to show the results of quick polls to those patrons who are not logged in, as well as to choose whether to show graphs, tables or both.',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE  web_builder_quick_poll ADD COLUMN showResultsToPatrons TINYINT(1) DEFAULT 0',
			],
		], // show_quick_poll_results

		//alexander - PTFS Europe
		'library_show_language_and_display_in_header' => [
			'title' => 'Library Show Language and Display in Header',
			'description' => 'Add option to allow the language and display settings to be shown in the page header',
			'sql' => [
				"ALTER TABLE library ADD languageAndDisplayInHeader INT(1) DEFAULT 1",
			],
		], //library_show_language_and_display_in_header
		'location_show_language_and_display_in_header' => [
			'title' => 'Location Show Language and Display in Header',
			'description' => 'Add option to allow the language and display settings to be shown in the page header',
			'sql' => [
				"ALTER TABLE location ADD languageAndDisplayInHeader INT(1) DEFAULT 1",
			],
		], //location_show_language_and_display_in_header
	];
}
