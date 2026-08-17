<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_09_00(): array {
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

		// nick
		'exact_location_matching_sierra' => [
			'title' => 'Exact location matching (Sierra only)',
			'description' => 'When using the ILS to determine valid pickup locations use exact matching against locations defined in Aspen.',
			'sql' => [
				'ALTER TABLE system_variables ADD COLUMN exactLocationMatching TINYINT(1) NOT NULL DEFAULT 0',
			]
		], //exact_location_matching_sierra



	];
}
