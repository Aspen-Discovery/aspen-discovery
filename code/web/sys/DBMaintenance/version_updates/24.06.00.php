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
		'accessibleBrowseCategories' => [
			'title' => 'Accessible browse categories',
			'description' => 'Adds an option to enable more accessible browse categories.',
			'sql' => [
				'ALTER TABLE themes ADD COLUMN accessibleBrowseCategories TINYINT NOT NULL DEFAULT 0',
			],
		], //accessibleBrowseCategories

		//kodi - ByWater

		//other


	];
}