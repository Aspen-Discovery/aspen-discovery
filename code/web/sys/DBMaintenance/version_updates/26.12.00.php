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
		'add_open_archives_cover_source_type' => [
			'title' => 'Add cover source type to Open Archives collections',
			'description' => 'Adds a column letting an Open Archives collection resolve covers through its platform\'s API (e.g. DSpace 7+) instead of scraping each record\'s page.',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE open_archives_collection ADD COLUMN coverSourceType VARCHAR(50) NOT NULL DEFAULT 'default' AFTER imageRegex",
			]
		], //add_open_archives_cover_source_type

		'add_open_archives_record_cover_url' => [
			'title' => 'Add cover URL to Open Archives records',
			'description' => 'Adds a column to store a cover URL resolved through the platform\'s API, shared across all cover sizes.',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE open_archives_record ADD COLUMN coverUrl TEXT NULL AFTER permanentUrl",
			]
		], //add_open_archives_record_cover_url

		//tomas

		// stephen

		//jacob - OpenFifth


	];
}
