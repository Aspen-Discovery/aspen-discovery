<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_10_00(): array {
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

		//bryan

		 'add_simplified_search_box' => [
                        'title' => 'Add Simplified Search Box setting',
                        'description' => 'Adds a toggle to enable the simplified search box UI per library
                        'continueOnError' => false,
                        'sql' => [
                                'ALTER TABLE library ADD COLUMN simplifiedSearchBox TINYINT(1) NOT NULL DE
                        ]
                ], //add_simplified_search_box

	];
}