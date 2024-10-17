<?php

function getUpdates24_10_10(): array {
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

		//mark - Grove
		'repeat_in_cloudsource' => [
			'title' => 'Repeat in CloudSource',
			'description' => 'Add information to allow repeat in CloudSource to work properly.',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE library ADD COLUMN repeatInCloudSource TINYINT DEFAULT 0',
				"ALTER TABLE library ADD COLUMN cloudSourceBaseUrl VARCHAR(255) DEFAULT ''",
				'ALTER TABLE location ADD COLUMN repeatInCloudSource TINYINT DEFAULT 0',
			],
		],
	];
}