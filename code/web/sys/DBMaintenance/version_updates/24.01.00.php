<?php

function getUpdates24_01_00(): array {
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
		'add_enable_branded_app_settings' => [
			'title' => 'Add option in System Variables to enable/disable Branded App Settings',
			'description' => 'Add option in System Variables to enable/disable Branded App Settings',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE system_variables ADD COLUMN enableBrandedApp TINYINT(1) DEFAULT 0'
			]
		], //add_enable_branded_app_settings
		'add_shared_session_table' => [
			'title' => 'Add table to store shared session information',
			'description' => 'Add table for temporarily storing session information for sharing sessions between LiDA and Discovery',
			'continueOnError' => false,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS shared_session (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					sessionId VARCHAR(40),
					userId VARCHAR(11),
					createdOn INT(11) DEFAULT 0
				) ENGINE = InnoDB',
			],
		], //add_shared_session_table

		//kodi - ByWater

		//lucas - Theke

		//alexander - PTFS Europe

		//jacob - PTFS Europe


	];
}