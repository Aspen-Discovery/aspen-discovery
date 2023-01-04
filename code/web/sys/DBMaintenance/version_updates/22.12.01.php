<?php
/** @noinspection PhpUnused */
function getUpdates22_12_01(): array {
	$curTime = time();
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/

		//mark

		//kirstien
		'add_url_payment' => [
			'title' => 'Add URL for where the payment was requested from',
			'description' => 'Store the URL for where a user payment request was originated from',
			'sql' => [
				'ALTER TABLE user_payments ADD COLUMN requestingUrl VARCHAR(255)',
			],
		],

		//kodi

		//other
	];
}