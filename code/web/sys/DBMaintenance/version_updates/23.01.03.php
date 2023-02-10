<?php
/** @noinspection PhpUnused */
function getUpdates23_01_03(): array {
	$curTime = time();
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
        ], //sample*/

		'aspen_site_monitored' => [
			'title' => 'Aspen Site - Monitored',
			'description' => 'Add the ability to not monitor specific sites',
			'sql' => [
				"ALTER TABLE aspen_sites ADD COLUMN monitored TINYINT(1) DEFAULT 1",
			],
		],
	];
}