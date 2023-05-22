<?php
/** @noinspection PhpUnused */
function getUpdates23_05_02(): array {
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

		'track_spammy_urls_by_ip' => [
			'title' => 'Increase Length of New Materials Request Column',
			'description' => 'Increase Length of New Materials Request Column',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE usage_by_ip_address ADD COLUMN numSpammyRequests INT DEFAULT 0',
				'ALTER TABLE ip_lookup ADD COLUMN blockedForSpam TINYINT DEFAULT 0',
			],
		],

	];
}