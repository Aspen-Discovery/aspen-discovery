<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_08_00(): array {
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
		'localhop_images' => [
			'title' => 'LocalHop Images',
			'description' => 'Add setting for toggling use of LocalHop images for event covers',
			'sql' => [
				'ALTER TABLE localhop_settings ADD COLUMN useLocalHopImages tinyint(1) NOT NULL DEFAULT 0',
			]
		], //localhop_images

		//yanjun

		//imani

		//galen

		//chloe
	
		//pedro

		//mark j

		//lucas

		//tomas

		// stephen

		//other

	];
}
