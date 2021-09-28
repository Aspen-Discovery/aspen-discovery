<?php
/** @noinspection PhpUnused */
function getUpdates21_13_00() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'open_archives_image_regex' => [
			'title' => 'Open Archives Image Regular Expression',
			'description' => 'Add a regular expression to get thumbnails for Open Archives',
			'sql' => [
				"ALTER TABLE open_archives_collection ADD COLUMN imageRegex VARCHAR(100) DEFAULT ''"
			]
		], //open_archives_image_regex
		'polaris_item_identifiers' => [
			'title' => 'Store item identifiers for Polaris',
			'description' => 'Store item identifiers for Polaris',
			'sql' => [
				"UPDATE indexing_profiles set itemRecordNumber = '9' WHERE indexingClass = 'Polaris'"
			]
		], //polaris_item_identifiers
		'polaris_full_update_21_13' => [
			'title' => 'Run a full update for polaris',
			'description' => 'Run a full update for polaris',
			'sql' => [
				"UPDATE indexing_profiles set runFullUpdate = 1 WHERE indexingClass = 'Polaris'"
			]
		], //polaris_full_update_21_13
	];
}