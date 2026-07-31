<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_07_01(): array {
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
		'reading_history_base_url' => [
			'title' => 'Reading History Base URLs',
			'description' => 'Add the ability to determine how reading history URLs are constructed when updating from cron.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE system_variables ADD COLUMN readingHistoryBaseUrl TINYINT(1) NOT NULL DEFAULT 0',
			],
		], //reading_history_base_url

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

		//other

	];
}