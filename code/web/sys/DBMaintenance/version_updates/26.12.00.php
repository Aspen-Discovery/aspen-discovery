<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_12_00(): array {
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

		//bryan - Nashville Public Library
		'add_simplified_search_box' => [
			'title' => 'Add Simplified Search Box setting',
			'description' => 'Add a library setting to enable the simplified search box',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library ADD COLUMN simplifiedSearchBox TINYINT(1) NOT NULL DEFAULT 0',
			],
		], //add_simplified_search_box


	];
}
