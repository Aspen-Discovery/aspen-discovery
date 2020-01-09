<?php

function getThemingUpdates()
{
	return [
		'themes_setup' => [
			'title' => 'Theme Setup',
			'description' => 'Initial setup of themes table. ',
			'continueOnError' => false,
			'sql' => [
				"CREATE TABLE IF NOT EXISTS themes (" .
				"id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, " .
				"themeName VARCHAR(100) NOT NULL, " .
				"extendsTheme VARCHAR(100) NULL DEFAULT NULL, " .
				"logoName VARCHAR(100) NOT NULL " .
				")",
				"ALTER TABLE themes ADD INDEX `themeName` (`themeName`)",
			],
		],

		'themes_header_colors' => [
			'title' => 'Theme Header Colors',
			'description' => 'Initial setup of header colors for the theme. ',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE themes ADD COLUMN `headerBackgroundColor` CHAR(7) DEFAULT '#f1f1f1'",
				"ALTER TABLE themes ADD COLUMN `headerBackgroundColorDefault` tinyint(1) DEFAULT 1",
				"ALTER TABLE themes ADD COLUMN `headerForegroundColor` CHAR(7) DEFAULT '#8b8b8b'",
				"ALTER TABLE themes ADD COLUMN `headerForegroundColorDefault` tinyint(1) DEFAULT 1",
			],
		],

		'themes_header_colors_2' => [
			'title' => 'Theme Header Colors 2 + generated css',
			'description' => 'Initial setup of header colors for the theme. ',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE `themes` ADD COLUMN `generatedCss` LONGTEXT",
				"ALTER TABLE `themes` ADD COLUMN `headerBottomBorderColor` CHAR(7) DEFAULT '#f1f1f1'",
				"ALTER TABLE `themes` ADD COLUMN `headerBottomBorderColorDefault` tinyint(1) DEFAULT 1",
			],
		],

		'themes_header_buttons' => [
			'title' => 'Theme Header Buttons',
			'description' => 'Initial setup of header button colors. ',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE `themes` ADD COLUMN `headerBottomBorderWidth` VARCHAR(6) DEFAULT null",
				"ALTER TABLE `themes` ADD COLUMN `headerButtonRadius` VARCHAR(6) DEFAULT null",
				"ALTER TABLE `themes` ADD COLUMN `headerButtonColor` CHAR(7) DEFAULT '#ffffff'",
				"ALTER TABLE `themes` ADD COLUMN `headerButtonColorDefault` tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN `headerButtonBackgroundColor` CHAR(7) DEFAULT '#848484'",
				"ALTER TABLE `themes` ADD COLUMN `headerButtonBackgroundColorDefault` tinyint(1) DEFAULT 1",
			],
		],

		'themes_favicon' => [
			'title' => 'Theme Favicon',
			'description' => 'Allow favicon to be defined. ',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE `themes` ADD COLUMN `favicon` VARCHAR(100)",
			],
		],

		'themes_primary_colors' => [
			'title' => 'Theme Primary Colors',
			'description' => 'Initial setup of primary colors. ',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE `themes` ADD COLUMN `pageBackgroundColor` CHAR(7) DEFAULT '#ffffff'",
				"ALTER TABLE `themes` ADD COLUMN `pageBackgroundColorDefault` tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN `primaryBackgroundColor` CHAR(7) DEFAULT '#147ce2'",
				"ALTER TABLE `themes` ADD COLUMN `primaryBackgroundColorDefault` tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN `primaryForegroundColor` CHAR(7) DEFAULT '#ffffff'",
				"ALTER TABLE `themes` ADD COLUMN `primaryForegroundColorDefault` tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN `bodyBackgroundColor` CHAR(7) DEFAULT '#ffffff'",
				"ALTER TABLE `themes` ADD COLUMN `bodyBackgroundColorDefault` tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN `bodyTextColor` CHAR(7) DEFAULT '#6B6B6B'",
				"ALTER TABLE `themes` ADD COLUMN `bodyTextColorDefault` tinyint(1) DEFAULT 1",
			],
		],

		'themes_secondary_colors' => [
			'title' => 'Theme Secondary and Tertiary Category Colors',
			'description' => 'Initial setup of secondary and tertiary colors. ',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE `themes` ADD COLUMN `secondaryBackgroundColor` CHAR(7) DEFAULT '#de9d03'",
				"ALTER TABLE `themes` ADD COLUMN `secondaryBackgroundColorDefault` tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN `secondaryForegroundColor` CHAR(7) DEFAULT '#ffffff'",
				"ALTER TABLE `themes` ADD COLUMN `secondaryForegroundColorDefault` tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN `tertiaryBackgroundColor` CHAR(7) DEFAULT '#de1f0b'",
				"ALTER TABLE `themes` ADD COLUMN `tertiaryBackgroundColorDefault` tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN `tertiaryForegroundColor` CHAR(7) DEFAULT '#ffffff'",
				"ALTER TABLE `themes` ADD COLUMN `tertiaryForegroundColorDefault` tinyint(1) DEFAULT 1",
			],
		],

		'themes_fonts' => [
			'title' => 'Theme Fonts',
			'description' => 'Fonts for headings and body. ',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE `themes` ADD COLUMN `headingFont` VARCHAR(191)",
				"ALTER TABLE `themes` ADD COLUMN `headingFontDefault` tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN `bodyFont` VARCHAR(191)",
				"ALTER TABLE `themes` ADD COLUMN `bodyFontDefault` tinyint(1) DEFAULT 1",
			],
		],

		'themes_additional_css' => [
			'title' => 'Theme Additional CSS',
			'description' => 'Add additional CSS to customize the display',
			'sql' => [
				'ALTER TABLE themes add COLUMN additionalCss TEXT',
				'ALTER TABLE themes add COLUMN additionalCssType TINYINT(1) DEFAULT 0',
			]
		],

		'themes_button_radius' => [
			'title' => 'Theme Button Radius',
			'description' => 'Allow customization of the button radius',
			'sql' => [
				'ALTER TABLE themes add COLUMN buttonRadius INT DEFAULT 4',
				'ALTER TABLE themes add COLUMN buttonRadiusDefault tinyint(1) DEFAULT 1',
				'ALTER TABLE themes add COLUMN smallButtonRadius INT DEFAULT 3',
				'ALTER TABLE themes add COLUMN smallButtonRadiusDefault tinyint(1) DEFAULT 1',
			]
		],

		'themes_browse_category_colors' => [
			'title' => 'Theme Browse Category Colors',
			'description' => 'Initial setup of browse category colors. ',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE `themes` ADD COLUMN browseCategoryPanelColor CHAR(7) DEFAULT '#d7dce3'",
				"ALTER TABLE `themes` ADD COLUMN browseCategoryPanelColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN selectedBrowseCategoryBackgroundColor CHAR(7) DEFAULT '#0087AB'",
				"ALTER TABLE `themes` ADD COLUMN selectedBrowseCategoryBackgroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN selectedBrowseCategoryForegroundColor CHAR(7) DEFAULT '#ffffff'",
				"ALTER TABLE `themes` ADD COLUMN selectedBrowseCategoryForegroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN selectedBrowseCategoryBorderColor CHAR(7) DEFAULT '#0087AB'",
				"ALTER TABLE `themes` ADD COLUMN selectedBrowseCategoryBorderColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN deselectedBrowseCategoryBackgroundColor CHAR(7) DEFAULT '#ffffff'",
				"ALTER TABLE `themes` ADD COLUMN deselectedBrowseCategoryBackgroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN deselectedBrowseCategoryForegroundColor CHAR(7) DEFAULT '#6B6B6B'",
				"ALTER TABLE `themes` ADD COLUMN deselectedBrowseCategoryForegroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN deselectedBrowseCategoryBorderColor CHAR(7) DEFAULT '#6B6B6B'",
				"ALTER TABLE `themes` ADD COLUMN deselectedBrowseCategoryBorderColorDefault tinyint(1) DEFAULT 1",
			],
		],
	];
}
