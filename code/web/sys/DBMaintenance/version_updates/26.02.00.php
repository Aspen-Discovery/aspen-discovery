<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_02_00(): array {
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

		//alexander

		//chloe

		//mark j
		'offer_immediate_hold_freeze' => [
			'title' => 'Library - Add the Ability to Freeze Holds Immediately',
			'description' => 'Library - Add the Ability to Freeze Holds Immediately',
			'sql' => [
				"ALTER TABLE library ADD COLUMN offerImmediateHoldFreeze tinyint(1) NOT NULL DEFAULT 0",
			]
		], //offer_immediate_hold_freeze

		//lucas


		//tomas

		//other


	];
}
