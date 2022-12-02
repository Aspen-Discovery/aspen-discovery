<?php
/** @noinspection PhpUnused */
function getUpdates21_13_03(): array {
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'addContactEmail' => [
			'title' => 'Add contact email',
			'description' => 'Add contact email to location and library tables',
			'sql' => [
				"ALTER TABLE location ADD COLUMN contactEmail VARCHAR(250)",
				"ALTER TABLE library ADD COLUMN contactEmail VARCHAR(250)",
			],
		],
		//addContactEmail
	];
}

