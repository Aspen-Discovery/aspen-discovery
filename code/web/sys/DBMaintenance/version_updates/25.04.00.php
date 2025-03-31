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

		//alexander - PTFS-Europe

		//chloe - PTFS-Europe

		//James Staub - Nashville Public Library

		//Lucas Montoya - Theke Solutions

		//other

	];
}