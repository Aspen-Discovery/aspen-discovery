<?php
/** @noinspection PhpUnused */
function getUpdates23_09_10(): array {
	$curTime = time();
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'continueOnError' => false,
			'sql' => [
				''
			]
		], //name*/

		'remove_suppress_itemless_bibs_setting' => [
			'title' => 'Indexing Profiles - Remove suppress itemless bibs setting',
			'description' => 'Indexing Profiles - Remove suppress itemless bibs setting',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE indexing_profiles DROP COLUMN suppressItemlessBibs',
				'ALTER TABLE sideloads DROP COLUMN suppressItemlessBibs'
			]
		], //remove_suppress_itemless_bibs_setting
	];
}