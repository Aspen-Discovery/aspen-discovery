<?php

function getThemingUpdates() {
    return [
        'themes_setup' => [
            'title' => 'Theme Setup',
            'description' => 'Initial setup of themes table. ',
            'dependencies' => [],
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
            'dependencies' => [],
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
            'dependencies' => [],
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
            'dependencies' => [],
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

    ];
}
