<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_11_00(): array {
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

		//pedro

		//mark j

		//lucas

		//tomas

		// stephen

		//jacob - OpenFifth

		'library_show_fines' => [
			'title' => 'Library Show Fines',
			'description' => 'Add a setting to hide the Fines link in My Account for fine-free libraries',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE library ADD COLUMN showFines TINYINT(1) NOT NULL DEFAULT 1",
			],
		], //library_show_fines

	];
}