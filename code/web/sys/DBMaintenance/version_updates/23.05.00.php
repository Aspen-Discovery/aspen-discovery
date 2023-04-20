<?php
/** @noinspection PhpUnused */
function getUpdates23_05_00(): array {
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
		//kirstien
		'drop_securityId_cp' => [
			'title' => 'Drop securityId from Certified Payments',
			'description' => 'Drop securityId from Certified Payments Settings table',
			'sql' => [
				'ALTER TABLE deluxe_certified_payments_settings DROP COLUMN securityId',
			],
		],
		//drop_securityId_cp
		//kodi
		//other
	];
}