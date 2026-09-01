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
		'theme_font_size' => [
			'title' => 'Theme Font Size',
			'description' => 'Add setting to control the base font size for a theme',
			'sql' => [
				"ALTER TABLE themes ADD COLUMN fontSize VARCHAR(10) NOT NULL DEFAULT 'small'",
			]
		], // theme_font_size
		'regenerate_themes' => [
			'title' => 'Regenerate Themes',
			'description' => 'Regenerate themes to accommodate new font size settings.',
			'sql' => [
				'regenerateThemeCssForFontSize',
			]
		], // theme_font_size
		'user_preferred_text_size' => [
			'title' => 'User Preferred Text Size',
			'description' => 'Allow a user to override the text size of the applied theme',
			'sql' => [
				"ALTER TABLE user ADD COLUMN preferredTextSize VARCHAR(10) NOT NULL DEFAULT ''",
			]
		], // user_preferred_text_size

		'publisher_keyword_exclusion' => [
			'title' => 'Publisher Keyword Exclusion',
			'description' => 'Add a variable in indexing profiles to determine if publisher info is excluded from the keyword index or not.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE `indexing_profiles` ADD COLUMN `excludePublisherFromKeywordIndex` TINYINT(1) NOT NULL DEFAULT 0;'
			]
		], //publisher_keyword_exclusion
		'self_check_location_overrides' => [
			'title' => 'Self-Check Location Overrides',
			'description' => 'Adds a setting in self-check settings to allow checkouts at certain locations if the item is checked out already. Symphony only.',
			'sql' => [
				'ALTER TABLE `aspen_lida_self_check_settings` ADD COLUMN `checkedoutOverrideLocations` VARCHAR(255)',
			]
		], //self_check_location_overrides
		//yanjun
		'hoopla_store_raw_response_length' => [
			'title' => 'Store Raw Response Length for Hoopla',
			'description' => 'Store Raw Response Length for Hoopla and index for performance',
			'sql' => [
				'ALTER TABLE hoopla_export ADD COLUMN rawResponseLength INT AS (UNCOMPRESSED_LENGTH(rawResponse)) STORED',
				'ALTER TABLE hoopla_export ADD INDEX responseIndex(hooplaId, rawChecksum, rawResponseLength)',
			],
		], //hoopla_store_raw_response_length
		'add_staff_members_display_order' => [
			'title' => 'Add staff members display order column',
			'description' => 'Add staff members display order',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE staff_members ADD COLUMN displayOrder INT UNSIGNED NOT NULL DEFAULT 0',
				'UPDATE staff_members sm JOIN (SELECT id, ROW_NUMBER() OVER (PARTITION BY libraryId ORDER BY name ASC, id ASC) AS newDisplayOrder FROM staff_members) orderedStaff ON orderedStaff.id = sm.id SET sm.displayOrder = orderedStaff.newDisplayOrder'
			]
		],

		//imani

		//galen

		//chloe
	
		//pedro

		//mark j
		'add_num_sample_titles_to_email_template' => [
			'title' => 'Add Number of Sample Titles to Email Template',
			'description' => 'Adds a column to control how many sample titles are shown in saved search alert emails.',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE email_template ADD COLUMN numSampleTitles INT NOT NULL DEFAULT 3"
			]
		], //add_num_sample_titles_to_email_template

		//lucas

		//tomas

		// stephen

		//jacob - OpenFifth


	];
}

/**
 * The generated CSS for each theme is cached in themes.generatedCss, so adding the font size rules to
 * theme.css.tpl has no effect until every theme is regenerated.
 */
function regenerateThemeCssForFontSize(&$update): void {
	require_once ROOT_DIR . '/sys/Theming/Theme.php';
	$theme = new Theme();
	$theme->find();
	$numUpdated = 0;
	while ($theme->fetch()) {
		$themeToUpdate = clone $theme;
		$themeToUpdate->generateCss(true);
		$numUpdated++;
	}
	$update['success'] = true;
	$update['status'] = translate([
		'text' => 'Regenerated CSS for %1% themes',
		1 => $numUpdated,
		'isAdminFacing' => true,
	]);
}
