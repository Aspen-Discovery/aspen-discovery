<?php

function getUpdates25_04_00(): array {
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

		//mark - Grove
		'restrict_local_ill_by_patron_type' => [
			'title' => 'Restrict Local ILL by Patron Type',
			'description' => 'Add an option to restrict local ILL by Patron Type',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE ptype ADD COLUMN allowLocalIll TINYINT DEFAULT  1'
			]
		], //restrict_local_ill_by_patron_type

		//katherine - Grove

		//kirstien - Grove

		//kodi - Grove

		//Yanjun Li - ByWater

		// Leo Stoyanov - BWS
		'new_nyt_update_settings' => [
			'title' => 'Add Run Full Update & Enable Extensive Logging to NYT Settings',
			'description' => 'Add a setting to force full updates of New York Times lists regardless of modification date and a setting to enable extensive logging.',
			'sql' => [
				"ALTER TABLE nyt_api_settings ADD COLUMN IF NOT EXISTS runFullUpdate TINYINT(1) NOT NULL DEFAULT 0",
				"ALTER TABLE nyt_api_settings ADD COLUMN IF NOT EXISTS enableExtensiveLogging TINYINT(1) NOT NULL DEFAULT 0",
				"ALTER TABLE nyt_update_log ADD COLUMN haltRequested TINYINT(1) DEFAULT 0 NOT NULL"
			],
		], //nyt_force_full_update

		//alexander - PTFS-Europe

		//chloe - PTFS-Europe

		//James Staub - Nashville Public Library

		//Lucas Montoya - Theke Solutions

		//other

	];
}