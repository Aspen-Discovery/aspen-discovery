<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_08_10(): array {
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

		//kodi
		'item_price_subfield' => [
			'title' => 'Item Price Subfield',
			'description' => 'Add setting for item price subfield. Sierra only.',
			'sql' => [
				"ALTER TABLE indexing_profiles ADD COLUMN itemPrice CHAR(1) DEFAULT ' '",
			],
		], // item_price_subfield
	];
}
