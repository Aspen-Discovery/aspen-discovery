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
		'self_check_location_overrides' => [
			'title' => 'Self-Check Location Overrides',
			'description' => 'Adds a setting in self-check settings to allow checkouts at certain locations if the item is checked out already. Symphony only.',
			'sql' => [
				'ALTER TABLE `aspen_lida_self_check_settings` ADD COLUMN `checkedoutOverrideLocations` VARCHAR(255)',
			]
		]

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


	];
}
