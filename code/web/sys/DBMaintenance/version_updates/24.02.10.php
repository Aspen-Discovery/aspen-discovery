<?php

function getUpdates24_02_10(): array {
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
		'library_payment_history' => [
			'title' => 'Library - Payment History',
			'description' => 'Add options related to library payment history',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library add column showPaymentHistory TINYINT DEFAULT 0'
			]
		], //library_show_payment_history
//		'user_payment_lines' => [
//			'title' => '',
//			'description' => '',
//			'continueOnError' => false,
//			'sql' => [
//				''
//			]
//		], //user_payment_lines

		//kirstien - ByWater

		//kodi - ByWater

		//lucas - Theke

		//alexander - PTFS Europe

		//jacob - PTFS Europe

		// James Staub

	];
}