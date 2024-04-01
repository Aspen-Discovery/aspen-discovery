<?php

function getUpdates24_04_00(): array {
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
		'replace_arial_fonts' => [
			 'title' => 'Replace Arial Fonts',
			 'description' => 'Replace Arial Fonts',
			 'continueOnError' => false,
			 'sql' => [
				 "UPDATE Themes set bodyFont = 'Arion' where bodyFont = 'Arial'",
				 "UPDATE Themes set headingFont = 'Arion' where headingFont = 'Arial'",
			 ]
		 ], //replace_arial_fonts

		//kirstien - ByWater
		'self_check_checkout_location' => [
			'title' => 'Add self-check option to set checkout location',
			'description' => 'Add self-check option to set checkout location',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE aspen_lida_self_check_settings ADD COLUMN checkoutLocation TINYINT(1) DEFAULT 0',
			],
		],
		//self_check_checkout_location

		//kodi - ByWater

		//lucas - Theke

		//alexander - PTFS Europe

		//jacob - PTFS Europe

		// James Staub


	];
}