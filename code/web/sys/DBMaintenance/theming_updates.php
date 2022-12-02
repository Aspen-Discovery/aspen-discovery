<?php

/** @noinspection SqlResolve */
function getThemingUpdates() {
	return [
		'themes_setup' => [
			'title' => 'Theme Setup',
			'description' => 'Initial setup of themes table. ',
			'continueOnError' => false,
			'sql' => [
				"CREATE TABLE IF NOT EXISTS themes (" . "id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, " . "themeName VARCHAR(100) NOT NULL, " . "extendsTheme VARCHAR(100) NULL DEFAULT NULL, " . "logoName VARCHAR(100) NOT NULL " . ")",
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

		'theme_defaults_for_logo_and_favicon' => [
			'title' => 'Theme - Set defaults for logo and favicon',
			'description' => 'Update theme table to have default values for logo and favicon to prevent errors',
			'sql' => [
				"ALTER TABLE themes CHANGE COLUMN logoName logoName VARCHAR(100) default ''",
				"ALTER TABLE themes CHANGE COLUMN favicon favicon VARCHAR(100) default ''",
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
			],
		],

		'themes_button_radius' => [
			'title' => 'Theme Button Radius',
			'description' => 'Allow customization of the button radius',
			'sql' => [
				'ALTER TABLE themes add COLUMN buttonRadius INT DEFAULT 4',
				'ALTER TABLE themes add COLUMN buttonRadiusDefault tinyint(1) DEFAULT 1',
				'ALTER TABLE themes add COLUMN smallButtonRadius INT DEFAULT 3',
				'ALTER TABLE themes add COLUMN smallButtonRadiusDefault tinyint(1) DEFAULT 1',
			],
		],

		'themes_button_radius2' => [
			'title' => 'Theme Button Radius 2',
			'description' => 'Update customization of the button radius',
			'sql' => [
				'ALTER TABLE themes CHANGE COLUMN buttonRadius buttonRadius VARCHAR(6) DEFAULT null',
				'UPDATE themes set buttonRadius = null',
				'ALTER TABLE themes DROP COLUMN buttonRadiusDefault',
				'ALTER TABLE themes CHANGE COLUMN smallButtonRadius smallButtonRadius VARCHAR(6) DEFAULT null',
				'UPDATE themes set smallButtonRadius = null',
				'ALTER TABLE themes DROP COLUMN smallButtonRadiusDefault',
			],
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

		'themes_sidebar_highlight_colors' => [
			'title' => 'Sidebar Highlight Colors',
			'description' => 'Initial setup of colors for the highlight in the sidebar menu',
			'sql' => [
				"ALTER TABLE `themes` ADD COLUMN sidebarHighlightBackgroundColor CHAR(7) DEFAULT '#16ceff'",
				"ALTER TABLE `themes` ADD COLUMN sidebarHighlightBackgroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN sidebarHighlightForegroundColor CHAR(7) DEFAULT '#ffffff'",
				"ALTER TABLE `themes` ADD COLUMN sidebarHighlightForegroundColorDefault tinyint(1) DEFAULT 1",
			],
		],

		'themes_additional_fonts' => [
			'title' => 'Theme - upload fonts',
			'description' => 'Add the ability to upload fonts',
			'sql' => [
				"ALTER TABLE `themes` ADD COLUMN customHeadingFont VARCHAR(100)",
				"ALTER TABLE `themes` ADD COLUMN customBodyFont VARCHAR(100)",
			],
		],

		'themes_capitalize_browse_categories' => [
			'title' => 'Theme - capitalize browse categories',
			'description' => 'Switch to capitalize browse categories',
			'sql' => [
				"ALTER TABLE `themes` ADD COLUMN capitalizeBrowseCategories TINYINT(1) DEFAULT -1",
			],
		],

		'themes_button_colors' => [
			'title' => 'Theme - button colors',
			'description' => 'Add definition for button colors',
			'sql' => [
				"ALTER TABLE `themes` ADD COLUMN defaultButtonBackgroundColor CHAR(7) DEFAULT '#ffffff'",
				"ALTER TABLE `themes` ADD COLUMN defaultButtonBackgroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN defaultButtonForegroundColor CHAR(7) DEFAULT '#333333'",
				"ALTER TABLE `themes` ADD COLUMN defaultButtonForegroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN defaultButtonBorderColor CHAR(7) DEFAULT '#cccccc'",
				"ALTER TABLE `themes` ADD COLUMN defaultButtonBorderColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN defaultButtonHoverBackgroundColor CHAR(7) DEFAULT '#ebebeb'",
				"ALTER TABLE `themes` ADD COLUMN defaultButtonHoverBackgroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN defaultButtonHoverForegroundColor CHAR(7) DEFAULT '#333333'",
				"ALTER TABLE `themes` ADD COLUMN defaultButtonHoverForegroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN defaultButtonHoverBorderColor CHAR(7) DEFAULT '#adadad'",
				"ALTER TABLE `themes` ADD COLUMN defaultButtonHoverBorderColorDefault tinyint(1) DEFAULT 1",

				"ALTER TABLE `themes` ADD COLUMN primaryButtonBackgroundColor CHAR(7) DEFAULT '#428bca'",
				"ALTER TABLE `themes` ADD COLUMN primaryButtonBackgroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN primaryButtonForegroundColor CHAR(7) DEFAULT '#ffffff'",
				"ALTER TABLE `themes` ADD COLUMN primaryButtonForegroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN primaryButtonBorderColor CHAR(7) DEFAULT '#357ebd'",
				"ALTER TABLE `themes` ADD COLUMN primaryButtonBorderColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN primaryButtonHoverBackgroundColor CHAR(7) DEFAULT '#3276b1'",
				"ALTER TABLE `themes` ADD COLUMN primaryButtonHoverBackgroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN primaryButtonHoverForegroundColor CHAR(7) DEFAULT '#ffffff'",
				"ALTER TABLE `themes` ADD COLUMN primaryButtonHoverForegroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN primaryButtonHoverBorderColor CHAR(7) DEFAULT '#285e8e'",
				"ALTER TABLE `themes` ADD COLUMN primaryButtonHoverBorderColorDefault tinyint(1) DEFAULT 1",

				"ALTER TABLE `themes` ADD COLUMN actionButtonBackgroundColor CHAR(7) DEFAULT '#428bca'",
				"ALTER TABLE `themes` ADD COLUMN actionButtonBackgroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN actionButtonForegroundColor CHAR(7) DEFAULT '#ffffff'",
				"ALTER TABLE `themes` ADD COLUMN actionButtonForegroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN actionButtonBorderColor CHAR(7) DEFAULT '#357ebd'",
				"ALTER TABLE `themes` ADD COLUMN actionButtonBorderColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN actionButtonHoverBackgroundColor CHAR(7) DEFAULT '#3276b1'",
				"ALTER TABLE `themes` ADD COLUMN actionButtonHoverBackgroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN actionButtonHoverForegroundColor CHAR(7) DEFAULT '#ffffff'",
				"ALTER TABLE `themes` ADD COLUMN actionButtonHoverForegroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN actionButtonHoverBorderColor CHAR(7) DEFAULT '#285e8e'",
				"ALTER TABLE `themes` ADD COLUMN actionButtonHoverBorderColorDefault tinyint(1) DEFAULT 1",

				"ALTER TABLE `themes` ADD COLUMN infoButtonBackgroundColor CHAR(7) DEFAULT '#5bc0de'",
				"ALTER TABLE `themes` ADD COLUMN infoButtonBackgroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN infoButtonForegroundColor CHAR(7) DEFAULT '#ffffff'",
				"ALTER TABLE `themes` ADD COLUMN infoButtonForegroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN infoButtonBorderColor CHAR(7) DEFAULT '#46b8da'",
				"ALTER TABLE `themes` ADD COLUMN infoButtonBorderColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN infoButtonHoverBackgroundColor CHAR(7) DEFAULT '#39b3d7'",
				"ALTER TABLE `themes` ADD COLUMN infoButtonHoverBackgroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN infoButtonHoverForegroundColor CHAR(7) DEFAULT '#ffffff'",
				"ALTER TABLE `themes` ADD COLUMN infoButtonHoverForegroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN infoButtonHoverBorderColor CHAR(7) DEFAULT '#269abc'",
				"ALTER TABLE `themes` ADD COLUMN infoButtonHoverBorderColorDefault tinyint(1) DEFAULT 1",

				"ALTER TABLE `themes` ADD COLUMN warningButtonBackgroundColor CHAR(7) DEFAULT '#f0ad4e'",
				"ALTER TABLE `themes` ADD COLUMN warningButtonBackgroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN warningButtonForegroundColor CHAR(7) DEFAULT '#ffffff'",
				"ALTER TABLE `themes` ADD COLUMN warningButtonForegroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN warningButtonBorderColor CHAR(7) DEFAULT '#eea236'",
				"ALTER TABLE `themes` ADD COLUMN warningButtonBorderColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN warningButtonHoverBackgroundColor CHAR(7) DEFAULT '#ed9c28'",
				"ALTER TABLE `themes` ADD COLUMN warningButtonHoverBackgroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN warningButtonHoverForegroundColor CHAR(7) DEFAULT '#ffffff'",
				"ALTER TABLE `themes` ADD COLUMN warningButtonHoverForegroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN warningButtonHoverBorderColor CHAR(7) DEFAULT '#d58512'",
				"ALTER TABLE `themes` ADD COLUMN warningButtonHoverBorderColorDefault tinyint(1) DEFAULT 1",

				"ALTER TABLE `themes` ADD COLUMN dangerButtonBackgroundColor CHAR(7) DEFAULT '#d9534f'",
				"ALTER TABLE `themes` ADD COLUMN dangerButtonBackgroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN dangerButtonForegroundColor CHAR(7) DEFAULT '#ffffff'",
				"ALTER TABLE `themes` ADD COLUMN dangerButtonForegroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN dangerButtonBorderColor CHAR(7) DEFAULT '#d43f3a'",
				"ALTER TABLE `themes` ADD COLUMN dangerButtonBorderColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN dangerButtonHoverBackgroundColor CHAR(7) DEFAULT '#d2322d'",
				"ALTER TABLE `themes` ADD COLUMN dangerButtonHoverBackgroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN dangerButtonHoverForegroundColor CHAR(7) DEFAULT '#ffffff'",
				"ALTER TABLE `themes` ADD COLUMN dangerButtonHoverForegroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN dangerButtonHoverBorderColor CHAR(7) DEFAULT '#ac2925'",
				"ALTER TABLE `themes` ADD COLUMN dangerButtonHoverBorderColorDefault tinyint(1) DEFAULT 1",
			],
		],

		'themes_editions_button_colors' => [
			'title' => 'Theme - editions button colors',
			'description' => 'Add definition for editions button colors',
			'sql' => [
				"ALTER TABLE `themes` ADD COLUMN editionsButtonBackgroundColor CHAR(7) DEFAULT '#f8f9fa'",
				"ALTER TABLE `themes` ADD COLUMN editionsButtonBackgroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN editionsButtonForegroundColor CHAR(7) DEFAULT '#212529'",
				"ALTER TABLE `themes` ADD COLUMN editionsButtonForegroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN editionsButtonBorderColor CHAR(7) DEFAULT '#999999'",
				"ALTER TABLE `themes` ADD COLUMN editionsButtonBorderColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN editionsButtonHoverBackgroundColor CHAR(7) DEFAULT '#e2e6ea'",
				"ALTER TABLE `themes` ADD COLUMN editionsButtonHoverBackgroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN editionsButtonHoverForegroundColor CHAR(7) DEFAULT '#212529'",
				"ALTER TABLE `themes` ADD COLUMN editionsButtonHoverForegroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN editionsButtonHoverBorderColor CHAR(7) DEFAULT '#dae0e5'",
				"ALTER TABLE `themes` ADD COLUMN editionsButtonHoverBorderColorDefault tinyint(1) DEFAULT 1",
			],
		],

		'themes_tools_button_colors' => [
			'title' => 'Theme - tools button colors',
			'description' => 'Add definition for tools button colors',
			'sql' => [
				"ALTER TABLE `themes` ADD COLUMN toolsButtonBackgroundColor CHAR(7) DEFAULT '#4F4F4F'",
				"ALTER TABLE `themes` ADD COLUMN toolsButtonBackgroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN toolsButtonForegroundColor CHAR(7) DEFAULT '#ffffff'",
				"ALTER TABLE `themes` ADD COLUMN toolsButtonForegroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN toolsButtonBorderColor CHAR(7) DEFAULT '#636363'",
				"ALTER TABLE `themes` ADD COLUMN toolsButtonBorderColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN toolsButtonHoverBackgroundColor CHAR(7) DEFAULT '#636363'",
				"ALTER TABLE `themes` ADD COLUMN toolsButtonHoverBackgroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN toolsButtonHoverForegroundColor CHAR(7) DEFAULT '#ffffff'",
				"ALTER TABLE `themes` ADD COLUMN toolsButtonHoverForegroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE `themes` ADD COLUMN toolsButtonHoverBorderColor CHAR(7) DEFAULT '#636363'",
				"ALTER TABLE `themes` ADD COLUMN toolsButtonHoverBorderColorDefault tinyint(1) DEFAULT 1",
			],
		],

		'themes_footer_design' => [
			'title' => 'Theme Footer',
			'description' => 'Initial setup of footer colors and optional logos. ',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE themes ADD COLUMN `footerBackgroundColor` CHAR(7) DEFAULT '#ffffff'",
				"ALTER TABLE themes ADD COLUMN `footerBackgroundColorDefault` tinyint(1) DEFAULT 1",
				"ALTER TABLE themes ADD COLUMN `footerForegroundColor` CHAR(7) DEFAULT '#6b6b6b'",
				"ALTER TABLE themes ADD COLUMN `footerForegroundColorDefault` tinyint(1) DEFAULT 1",
				"ALTER TABLE themes ADD COLUMN footerLogo VARCHAR(100) NULL",
				"ALTER TABLE themes ADD COLUMN footerLogoLink VARCHAR(255) NULL",
			],
		],

		'themes_panel_design' => [
			'title' => 'Theme Panels',
			'description' => 'Initial setup of panel colors. ',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE themes ADD COLUMN closedPanelBackgroundColor CHAR(7) DEFAULT '#e7e7e7'",
				"ALTER TABLE themes ADD COLUMN closedPanelBackgroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE themes ADD COLUMN closedPanelForegroundColor CHAR(7) DEFAULT '#333333'",
				"ALTER TABLE themes ADD COLUMN closedPanelForegroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE themes ADD COLUMN openPanelBackgroundColor CHAR(7) DEFAULT '#4DACDE'",
				"ALTER TABLE themes ADD COLUMN openPanelBackgroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE themes ADD COLUMN openPanelForegroundColor CHAR(7) DEFAULT '#ffffff'",
				"ALTER TABLE themes ADD COLUMN openPanelForegroundColorDefault tinyint(1) DEFAULT 1",
			],
		],

		'themes_panel_body_design' => [
			'title' => 'Theme Panel Body',
			'description' => 'Allow Panel body to be themed. ',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE themes ADD COLUMN panelBodyBackgroundColor CHAR(7) DEFAULT '#ffffff'",
				"ALTER TABLE themes ADD COLUMN panelBodyBackgroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE themes ADD COLUMN panelBodyForegroundColor CHAR(7) DEFAULT '#404040'",
				"ALTER TABLE themes ADD COLUMN panelBodyForegroundColorDefault tinyint(1) DEFAULT 1",
			],
		],

		'themes_link_color' => [
			'title' => 'Theme Link Color',
			'description' => 'Define Link Color. ',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE themes ADD COLUMN linkColor CHAR(7) DEFAULT '#3174AF'",
				"ALTER TABLE themes ADD COLUMN linkColorDefault tinyint(1) DEFAULT 1",
			],
		],

		'themes_link_hover_color' => [
			'title' => 'Theme Link Hover Color',
			'description' => 'Define Link Hover Color. ',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE themes ADD COLUMN linkHoverColor CHAR(7) DEFAULT '#265a87'",
				"ALTER TABLE themes ADD COLUMN linkHoverColorDefault tinyint(1) DEFAULT 1",
			],
		],

		'themes_badges' => [
			'title' => 'Theme Badges',
			'description' => 'Setup Theming for badges ',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE themes ADD COLUMN badgeBackgroundColor CHAR(7) DEFAULT '#666666'",
				"ALTER TABLE themes ADD COLUMN badgeBackgroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE themes ADD COLUMN badgeForegroundColor CHAR(7) DEFAULT '#ffffff'",
				"ALTER TABLE themes ADD COLUMN badgeForegroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE themes ADD COLUMN badgeBorderRadius VARCHAR(6) DEFAULT null",
			],
		],

		'themes_results_breadcrumbs' => [
			'title' => 'Theme results and breadcrumbs',
			'description' => 'Add theming for results text and breadcrumbs',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE themes ADD COLUMN resultLabelColor CHAR(7) DEFAULT '#44484a'",
				"ALTER TABLE themes ADD COLUMN resultLabelColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE themes ADD COLUMN resultValueColor CHAR(7) DEFAULT '#6B6B6B'",
				"ALTER TABLE themes ADD COLUMN resultValueColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE themes ADD COLUMN breadcrumbsBackgroundColor CHAR(7) DEFAULT '#f5f5f5'",
				"ALTER TABLE themes ADD COLUMN breadcrumbsBackgroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE themes ADD COLUMN breadcrumbsForegroundColor CHAR(7) DEFAULT '#6B6B6B'",
				"ALTER TABLE themes ADD COLUMN breadcrumbsForegroundColorDefault tinyint(1) DEFAULT 1",
			],
		],

		'themes_search_tools' => [
			'title' => 'Theme Search tools',
			'description' => 'Add theming for search tools',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE themes ADD COLUMN searchToolsBackgroundColor CHAR(7) DEFAULT '#f5f5f5'",
				"ALTER TABLE themes ADD COLUMN searchToolsBackgroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE themes ADD COLUMN searchToolsBorderColor CHAR(7) DEFAULT '#e3e3e3'",
				"ALTER TABLE themes ADD COLUMN searchToolsBorderColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE themes ADD COLUMN searchToolsForegroundColor CHAR(7) DEFAULT '#6B6B6B'",
				"ALTER TABLE themes ADD COLUMN searchToolsForegroundColorDefault tinyint(1) DEFAULT 1",
			],
		],

		'theme_reorganize_menu' => [
			'title' => 'Theme remove vertical bar',
			'description' => "Remove options related to the vertical bar and add new options for horizontal menu",
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE themes DROP COLUMN headerButtonBackgroundColor",
				"ALTER TABLE themes DROP COLUMN headerButtonBackgroundColorDefault",
				"ALTER TABLE themes DROP COLUMN headerButtonColor",
				"ALTER TABLE themes DROP COLUMN headerButtonColorDefault",
				"ALTER TABLE themes DROP COLUMN headerButtonRadius",
				"ALTER TABLE themes DROP COLUMN headerBottomBorderColor",
				"ALTER TABLE themes DROP COLUMN headerBottomBorderColorDefault",
				"ALTER TABLE themes CHANGE COLUMN sidebarHighlightBackgroundColor menubarHighlightBackgroundColor CHAR(7) DEFAULT '#f1f1f1'",
				"ALTER TABLE themes CHANGE COLUMN sidebarHighlightBackgroundColorDefault menubarHighlightBackgroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE themes CHANGE COLUMN sidebarHighlightForegroundColor menubarHighlightForegroundColor CHAR(7) DEFAULT '#265a87'",
				"ALTER TABLE themes CHANGE COLUMN sidebarHighlightForegroundColorDefault menubarHighlightForegroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE themes ADD COLUMN menubarBackgroundColor CHAR(7) DEFAULT '#f1f1f1'",
				"ALTER TABLE themes ADD COLUMN menubarBackgroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE themes ADD COLUMN menubarForegroundColor CHAR(7) DEFAULT '#303030'",
				"ALTER TABLE themes ADD COLUMN menubarForegroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE themes ADD COLUMN menuDropdownBackgroundColor CHAR(7) DEFAULT '#ededed'",
				"ALTER TABLE themes ADD COLUMN menuDropdownBackgroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE themes ADD COLUMN menuDropdownForegroundColor CHAR(7) DEFAULT '#404040'",
				"ALTER TABLE themes ADD COLUMN menuDropdownForegroundColorDefault tinyint(1) DEFAULT 1",
				"updateAllThemes",
			],
		],

		'theme_modal_dialog' => [
			'title' => 'Theme Modal Dialog',
			'description' => "Add the ability to theme the modal dialog",
			'sql' => [
				"ALTER TABLE themes ADD COLUMN modalDialogBackgroundColor CHAR(7) DEFAULT '#ffffff'",
				"ALTER TABLE themes ADD COLUMN modalDialogBackgroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE themes ADD COLUMN modalDialogForegroundColor CHAR(7) DEFAULT '#333333'",
				"ALTER TABLE themes ADD COLUMN modalDialogForegroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE themes ADD COLUMN modalDialogHeaderFooterBackgroundColor CHAR(7) DEFAULT '#ffffff'",
				"ALTER TABLE themes ADD COLUMN modalDialogHeaderFooterBackgroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE themes ADD COLUMN modalDialogHeaderFooterForegroundColor CHAR(7) DEFAULT '#333333'",
				"ALTER TABLE themes ADD COLUMN modalDialogHeaderFooterForegroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE themes ADD COLUMN modalDialogHeaderFooterBorderColor CHAR(7) DEFAULT '#e5e5e5'",
				"ALTER TABLE themes ADD COLUMN modalDialogHeaderFooterBorderColorDefault tinyint(1) DEFAULT 1",
				"updateAllThemes",
			],
		],

		'rebuildThemes21_03' => [
			'title' => 'Rebuild Themes for 21.03',
			'description' => 'Rebuild Themes for 21.03',
			'sql' => [
				"updateAllThemes",
			],
		],
	];
}

function updateAllThemes() {
	$theme = new Theme();
	$theme->find();
	while ($theme->fetch()) {
		$theme->generateCss(true);
	}
}
