<?php

function getUpdates24_08_00(): array {
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

		//kirstien - ByWater
		'add_ils_notification_settings' => [
			'title' => 'Add table ils_notification_settings',
			'description' => '',
			'continueOnError' => false,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS ils_notification_setting (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(50)
				) ENGINE INNODB',
			]
		], //add_ils_notification_settings

		'add_user_ils_messages' => [
			 'title' => 'Add table user_ils_messages',
			 'description' => '',
			 'continueOnError' => false,
			 'sql' => [
				 "CREATE TABLE IF NOT EXISTS user_ils_messages (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					messageId VARCHAR(100) NOT NULL,
					userId INT(11),
					type VARCHAR(50),
					status enum('pending', 'sent', 'failed') DEFAULT 'pending',
					title VARCHAR(200),
					content MEDIUMTEXT,
					error VARCHAR(255),
					dateQueued TIMESTAMP,
					dateSent INT(11),
					isRead TINYINT(1) DEFAULT 0
				) ENGINE INNODB",
			 ]
		 ], //add_user_ils_messages

		'add_ils_message_type' => [
			'title' => 'Add table ils_message_type',
			'description' => '',
			'continueOnError' => false,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS ils_message_type (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					module VARCHAR(255),
					code VARCHAR(255),
					name VARCHAR(255),
					isDigest TINYINT(1) DEFAULT 0,
					locationCode VARCHAR(255),
					isEnabled TINYINT(1) DEFAULT 1,
					ilsNotificationSettingId INT(11)
				) ENGINE INNODB',
			]
		], //add_ils_message_type

		'add_ilsNotificationSettingId' => [
			'title' => 'Add ilsNotificationSettingId to aspen_lida_notification_setting',
			'description' => 'Add ilsNotificationSettingId to aspen_lida_notification_setting',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE aspen_lida_notification_setting ADD COLUMN ilsNotificationSettingId INT(11) DEFAULT -1',
			]
		], //add_ilsNotificationSettingId

		//kodi - ByWater
		'overdrive_series_length' => [
			'title' => 'Series Length',
			'description' => 'Increase column length for series in overdrive_api_products table to accommodate long series names in Libby',
			'sql' => [
				'ALTER TABLE overdrive_api_products CHANGE COLUMN series series VARCHAR(255)',
			],
		],//overdrive_series_length

		//katherine - ByWater

		//alexander - PTFS-Europe

		//pedro - PTFS-Europe

		//other

	];
}