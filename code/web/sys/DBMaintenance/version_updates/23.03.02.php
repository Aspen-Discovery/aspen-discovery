<?php
/** @noinspection PhpUnused */
function getUpdates23_03_02(): array {
	$curTime = time();
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'continueOnError' => false,
			'sql' => [
				''
			]
		], //sample*/

		//mark
		'increase_length_of_new_materials_request_column' => [
			'title' => 'Increase Length of New Materials Request Column',
			'description' => 'Increase Length of New Materials Request Column',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library CHANGE COLUMN materialsRequestNewEmail materialsRequestNewEmail VARCHAR(125) DEFAULT NULL',
			],
		],
		//kirstien

		//add_sso_unique_field_match

		//kodi

		//other

	];
}