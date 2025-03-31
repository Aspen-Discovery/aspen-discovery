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
		'force_regrouping_all_works_25_04' => [
			'title' => 'Force Regrouping All Works 25.04',
			'description' => 'Force Regrouping All Works',
			'sql' => [
				"UPDATE system_variables set regroupAllRecordsDuringNightlyIndex = 1",
			],
		], //force_regrouping_all_works_25_04
		'make_local_ill_form_note_optional' => [
			'title' => 'Make Local ILL Form Note Optional',
			'description' => 'Make Local ILL Form Note Optional',
			'sql' => [
				'ALTER TABLE local_ill_form ADD COLUMN showNote TINYINT DEFAULT  1'
			]
		], //make_local_ill_form_note_optional

		//katherine - Grove
		'add_location_to_aspen_events_settings' => [
			'title' => 'Add Location to Aspen Events Settings',
			'description' => 'Add location_events_setting table so that settings can be linked to specific locations',
			'sql' => [
				"CREATE TABLE location_events_setting (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					settingId INT NOT NULL,
					locationId INT NOT NULL
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci",
				"ALTER TABLE events_indexing_settings ADD COLUMN name VARCHAR(100)"
			]
		], //add_location_to_aspen_events_settings

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