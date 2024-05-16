<?php

function getUpdates24_06_00(): array {
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

		//kodi - ByWater
		'full_text_limiter' => [
			'title' => 'Full Text Limiter',
			'description' => 'Adds toggle for defaulting the full text limiter on/off for Ebsco EDS.',
			'sql' => [
				"ALTER TABLE ebsco_eds_settings ADD COLUMN fullTextLimiter TINYINT NOT NULL DEFAULT 1;",
			],
		], //full_text_limiter

		//other


	];
}