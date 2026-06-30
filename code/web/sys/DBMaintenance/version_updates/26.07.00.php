<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_07_00(): array {
	$now = time();

	return [
		/*'name' => [
			 'title' => '',
			 'description' => '',
			 'continueOnError' => false,
			 'sql' => [
				 ''
			 ]
		 ], //name*/

		//mark n


		//kirstien

		//kodi

		//yanjun

		//imani

		//galen

		//chloe
		'library_show_checkout_renewal_fee_message' => [
			'title' => 'Add Show Checkout Renewal Fee Message to Library',
			'description' => 'Adds a setting to control whether the checkout renewal fee message is shown to patrons',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library ADD COLUMN showCheckoutRenewalFeeMessage TINYINT(1) DEFAULT 1',
			]
		], //library_show_checkout_renewal_fee_message
		'library_show_hold_fee_message' => [
			'title' => 'Add Show Hold Fee Message to Library',
			'description' => 'Adds a setting to control whether the hold fee message is shown to patrons',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library ADD COLUMN showHoldFeeMessage TINYINT(1) DEFAULT 1',
			]
		], //library_show_hold_fee_message
	
		//pedro

		//mark j

		//lucas

		//tomas

		// stephen

		//other

	];
}
