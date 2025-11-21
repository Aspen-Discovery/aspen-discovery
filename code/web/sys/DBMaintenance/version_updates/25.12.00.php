<?php

/** @noinspection PhpUnused */
function getUpdates25_12_00(): array {
	return [
		/*'name' => [
			 'title' => '',
			 'description' => '',
			 'continueOnError' => false,
			 'sql' => [
				 ''
			 ]
		 ], //name*/

		//mark - Grove
		'library_options_to_disable_pickup_locations' => [
			'title' => 'Library Options to Disable Pickup Locations',
			'description' => 'Add options to library settings to allow disabling pickup locations',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library ADD COLUMN hidePickupLocationPrompt TINYINT(1) DEFAULT 0',
				'ALTER TABLE library ADD COLUMN allowChangingPickupLocationForUnavailableHolds TINYINT(1) DEFAULT 1',
			]
		], //library_options_to_disable_pickup_locations
		
		//kirstien - Grove

		//kodi - Grove

		// Myranda - Grove

		//Yanjun Li - ByWater

		// Leo Stoyanov - BWS

		//alexander - Open Fifth

		//chloe - Open Fifth

		//James Staub - Nashville Public Library

		//Lucas Montoya - Theke Solutions

		//other

	];
}
