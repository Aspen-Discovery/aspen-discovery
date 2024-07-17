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
		'add_ils_notification_setting' => [
			'title' => 'Add table ils_notification_setting',
			'description' => '',
			'continueOnError' => false,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS ils_notification_setting (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					attributeId VARCHAR(100),
					module VARCHAR(255),
					code VARCHAR(255),
					isDigest TINYINT(1) DEFAULT 0,
					locationCode VARCHAR(255),
					isEnabled TINYINT(1) DEFAULT 1,
					notificationSettingId INT(11)
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
					error VARCHAR(255),
					dateQueued TIMESTAMP,
					dateSent INT(11)
				) ENGINE INNODB",
			 ]
		 ], //add_user_ils_messages

		//kodi - ByWater

		//katherine - ByWater

		//alexander - PTFS-Europe
		'display_explore_more_bar' => [
			'title' => 'Display Explore More Bar',
			'description' => 'Display Explore More Bar',
			'sql' => [
				'ALTER TABLE library ADD COLUMN displayExploreMoreBar TINYINT(1) DEFAULT 1',
				'ALTER TABLE location ADD COLUMN displayExploreMoreBar TINYINT(1) DEFAULT 1',
			],
		],
		

		//pedro - PTFS-Europe

		//other

	];
}