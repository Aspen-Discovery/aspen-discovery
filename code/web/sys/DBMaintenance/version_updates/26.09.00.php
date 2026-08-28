<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_09_00(): array {
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
		'add_aspen_lida_themes_table' => [
			'title' => 'Add Aspen LiDA Themes Table',
			'description' => 'Add table to store Aspen LiDA themes.',
			'continueOnError' => false,
			'sql' => [
				"CREATE TABLE IF NOT EXISTS `aspen_lida_themes` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`name` varchar(100) NOT NULL,
					`baseMode` enum('light','dark') NOT NULL DEFAULT 'light',
					`extendsWebThemeId` int(11) NOT NULL DEFAULT -1,
					`logo` varchar(255) DEFAULT NULL,
					`headerLogo` varchar(255) DEFAULT NULL,
					`headerLogoAlignment` tinyint(1) NOT NULL DEFAULT 2,
					`headerLogoBackgroundColor` varchar(7) NOT NULL DEFAULT '#ffffff',
					`headerLogoBackgroundColorDefault` tinyint(1),
					`primaryColor` varchar(7) NOT NULL DEFAULT '#147ce2',
					`primaryColorDefault` tinyint(1),
					`primaryTextColor` varchar(7) NOT NULL DEFAULT '#ffffff',
					`primaryTextColorDefault` tinyint(1),
					`secondaryColor` varchar(7) NOT NULL DEFAULT '#de9d03',
					`secondaryColorDefault` tinyint(1),
					`secondaryTextColor` varchar(7) NOT NULL DEFAULT '#ffffff',
					`secondaryTextColorDefault` tinyint(1),
					`tertiaryColor` varchar(7) NOT NULL DEFAULT '#de1f0b',
					`tertiaryColorDefault` tinyint(1),
					`tertiaryTextColor` varchar(7) NOT NULL DEFAULT '#ffffff',
					`tertiaryTextColorDefault` tinyint(1),
					PRIMARY KEY (`id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
			],
		],
		//add_aspen_lida_themes_table

		'add_aspen_lida_theme_libraries_table' => [
			'title' => 'Add Aspen LiDA Theme Libraries Junction Table',
			'description' => 'Add table to assign Aspen LiDA themes to libraries.',
			'continueOnError' => false,
			'sql' => [
				"CREATE TABLE IF NOT EXISTS `aspen_lida_theme_libraries` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`themeId` int(11) NOT NULL,
					`libraryId` int(11) NOT NULL,
					`weight` int(11) NOT NULL DEFAULT 0,
					PRIMARY KEY (`id`),
					KEY `themeId` (`themeId`),
					KEY `libraryId` (`libraryId`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
			],
		],
		//add_aspen_lida_theme_libraries_table

		'add_aspen_lida_theme_locations_table' => [
			'title' => 'Add Aspen LiDA Theme Locations Junction Table',
			'description' => 'Add table to assign Aspen LiDA themes to locations.',
			'continueOnError' => false,
			'sql' => [
				"CREATE TABLE IF NOT EXISTS `aspen_lida_theme_locations` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`themeId` int(11) NOT NULL,
					`locationId` int(11) NOT NULL,
					`weight` int(11) NOT NULL DEFAULT 0,
					PRIMARY KEY (`id`),
					KEY `themeId` (`themeId`),
					KEY `locationId` (`locationId`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
			],
		],
		//add_aspen_lida_theme_locations_table

		'add_aspen_lida_theme_permissions' => [
			'title' => 'Add Aspen LiDA Theme Permissions',
			'description' => 'Create permissions for managing Aspen LiDA Themes at the global and library level.',
			'continueOnError' => false,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES
				('Aspen LiDA', 'Administer All Aspen LiDA Themes', '', 162, 'Allows the user to create, edit, and delete Aspen LiDA Themes for all libraries.'),
				('Aspen LiDA', 'Administer Library Aspen LiDA Themes', '', 163, 'Allows the user to manage Aspen LiDA Themes assigned to their home library.')",
			],
		],
		//add_aspen_lida_theme_permissions

		'add_aspen_lida_theme_role_permissions' => [
			'title' => 'Add Aspen LiDA Theme Role Permissions',
			'description' => 'Assign Administer All Aspen LiDA Themes permission to the opacAdmin role.',
			'continueOnError' => false,
			'sql' => [
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer All Aspen LiDA Themes'))",
			],
		],
		//add_aspen_lida_theme_role_permissions

		'add_aspen_lida_themes_location' => [
			'title' => 'Add use Library Aspen LiDA Theme Option to Locations',
			'description' => 'Add columns to locations for inheriting library Aspen LiDA theme.',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE location ADD COLUMN useLibraryAspenLiDAThemes TINYINT(1) DEFAULT 1",
			],
		],
		//add_aspen_lida_themes_location


		//kodi

		//yanjun

		//imani

		//galen

		//chloe
	
		//pedro

		//mark j

		//lucas

		//tomas

		// stephen

		//jacob - OpenFifth


	];
}

