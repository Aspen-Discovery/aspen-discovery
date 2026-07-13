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
		'display_sort_term_values' => [
			'title' => 'Display Sort Term Values',
			'description' => 'Add configuration option to dynamically show sort term values for total checkouts, date added, number of holds, and call number.',
			'sql' => [
				"ALTER TABLE grouped_work_display_settings ADD displaySortTermValue TINYINT(1) DEFAULT 0",
			],
		], //display_sort_term_values

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
