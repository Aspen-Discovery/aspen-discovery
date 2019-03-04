<?php

function getThemingUpdates() {
    return array(
        'themes_setup' => array(
            'title' => 'Theme Setup',
            'description' => 'Initial setup of themes table. ',
            'dependencies' => array(),
            'continueOnError' => false,
            'sql' => array(
                "CREATE TABLE IF NOT EXISTS themes (" .
                "id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, " .
                "themeName VARCHAR(100) NOT NULL, " .
                "extendsTheme VARCHAR(100) NULL DEFAULT NULL, " .
                "logoName VARCHAR(100) NULL DEFAULT NULL " .
                ")",
                "ALTER TABLE `themes` ADD INDEX `themeName` (`themeName`)",
            ),
        ),
    );
}
