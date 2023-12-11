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

		//kodi - ByWater

		//lucas - Theke

		//alexander - PTFS Europe

		//jacob - PTFS Europe


	];
}