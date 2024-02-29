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
		'user_payment_lines' => [
			'title' => 'User Payment Lines',
			'description' => 'Add User Payment Lines Table',
			'continueOnError' => false,
			'sql' => [
				'CREATE TABLE user_payment_lines (
					id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
					paymentId INT(11) NOT NULL,
					description TEXT,
					amountPaid FLOAT
				)'
			]
		], //user_payment_lines
		'library_deletePaymentHistoryOlderThan' => [
			'title' => 'Library delete payment history older than',
			'description' => 'Add setting to delete payment history older than a specific date',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library ADD COLUMN deletePaymentHistoryOlderThan INT DEFAULT 0'
			]
		], //library_deletePaymentHistoryOlderThan

		//kirstien - ByWater

		//kodi - ByWater

		//lucas - Theke

		//alexander - PTFS Europe

		//jacob - PTFS Europe

		// James Staub

	];
}