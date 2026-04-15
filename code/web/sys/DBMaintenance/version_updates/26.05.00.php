<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_05_00(): array {
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

		//jonah
		'DIS-XXXX_custom_aspenEvent_covers' => [
			'title' => 'AspenEvents Custom Covers',
			'description' => 'Allow the use of uploaded covers in search results',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE event ADD COLUMN useEventImageInSearchResults TINYINT(1) DEFAULT 0'
			]
		], //DIS-XXXX_custom_aspenEvent_covers
		//galen

		//chloe

		//pedro

		//mark j

		//lucas

		//tomas

		// stephen


		//pedro

		//other

	];
}
