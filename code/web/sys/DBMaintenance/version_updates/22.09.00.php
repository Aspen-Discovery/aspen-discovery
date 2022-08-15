<?php
/** @noinspection PhpUnused */
function getUpdates22_09_00() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'move_aspen_lida_settings' => [
			'title' => 'Move Aspen LiDA settings to own section',
			'description' => 'Moves quick searches, general app config, branded app config, and adds notification settings',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS aspen_lida_notification_setting (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					name VARCHAR(50) UNIQUE NOT NULL,
					sendTo TINYINT(1) DEFAULT 0,
					notifySavedSearch TINYINT(1) DEFAULT 0
				) ENGINE INNODB',
				'CREATE TABLE IF NOT EXISTS aspen_lida_quick_search_setting (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					name VARCHAR(50) UNIQUE NOT NULL
				) ENGINE INNODB',
				'CREATE TABLE IF NOT EXISTS aspen_lida_general_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					name VARCHAR(50) UNIQUE,
					enableAccess TINYINT(1) DEFAULT 0,
					releaseChannel TINYINT(1) DEFAULT 0
				) ENGINE INNODB',
				"ALTER TABLE library ADD COLUMN lidaNotificationSettingId INT(11) DEFAULT -1",
				"ALTER TABLE library ADD COLUMN lidaQuickSearchId INT(11) DEFAULT -1",
				"ALTER TABLE location ADD COLUMN lidaGeneralSettingId INT(11) DEFAULT -1",
				"ALTER TABLE aspen_lida_quick_searches ADD COLUMN quickSearchSettingId INT(11) DEFAULT -1",
				"ALTER TABLE aspen_lida_settings RENAME TO aspen_lida_branded_settings",
			]
		], //move_aspen_lida_settings
	];
}