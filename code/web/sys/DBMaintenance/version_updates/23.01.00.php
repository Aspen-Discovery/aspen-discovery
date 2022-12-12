<?php
/** @noinspection PhpUnused */
function getUpdates23_01_00(): array
{
	$curTime = time();
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/

		//mark
		'users_to_tasks' => [
			'title' => 'Development - Link Users To Tasks',
			'description' => 'Development - Link Users To Tasks',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS development_task_developer_link (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					userId INT(11), 
					taskId INT(11), 
					UNIQUE INDEX (userId, taskId)
				) ENGINE INNODB',
				'CREATE TABLE IF NOT EXISTS development_task_qa_link (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					userId INT(11), 
					taskId INT(11), 
					UNIQUE INDEX (userId, taskId)
				) ENGINE INNODB',
			],
		],
		//development_partners_to_tasks

		//kirstien
		'add_account_alerts_notification' => [
			'title' => 'Add account alert notification type',
			'description' => 'Adds account alert notifications',
			'sql' => [
				'ALTER TABLE user_notification_tokens ADD COLUMN notifyAccount TINYINT(1) DEFAULT 0',
			],
		],
		//add_account_alerts_notification

		//kodi
		'user_browse_add_home' => [
			'title' => 'Add New Browse Categories to Home',
			'description' => 'Store user selection for adding browse categories to home page',
			'sql' => [
				'ALTER TABLE user ADD COLUMN browseAddToHome TINYINT(1) DEFAULT 1',
			],
		],
		//user_browse_add_home
		//other
	];
}