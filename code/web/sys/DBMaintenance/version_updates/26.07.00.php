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
		'series_to_group_with' => [
			'title' => 'Add Column in Series Table for Grouping',
			'description' => 'Add column for seriesToGroupWithId in Series table to be used for merging/grouping series.',
			'sql' => [
				'ALTER TABLE series ADD COLUMN seriesToGroupWithId CHAR(40)',
			]
		],

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
