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
		'publisher_keyword_exclusion' => [
			'title' => 'Publisher Keyword Exclusion',
			'description' => 'Add a variable in indexing profiles to determine if publisher info is excluded from the keyword index or not.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE `indexing_profiles` ADD COLUMN `excludePublisherFromKeywordIndex` TINYINT(1) NOT NULL DEFAULT 0;'
			]
		], //publisher_keyword_exclusion
		'self_check_location_overrides' => [
			'title' => 'Self-Check Location Overrides',
			'description' => 'Adds a setting in self-check settings to allow checkouts at certain locations if the item is checked out already. Symphony only.',
			'sql' => [
				'ALTER TABLE `aspen_lida_self_check_settings` ADD COLUMN `checkedoutOverrideLocations` VARCHAR(255)',
			]
		], //self_check_location_overrides
		//yanjun
		'add_staff_members_display_order' => [
			'title' => 'Add staff members display order column',
			'description' => 'Add staff members display order',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE staff_members ADD COLUMN displayOrder INT UNSIGNED NOT NULL DEFAULT 0',
				'UPDATE staff_members sm JOIN (SELECT id, ROW_NUMBER() OVER (PARTITION BY libraryId ORDER BY name ASC, id ASC) AS newDisplayOrder FROM staff_members) orderedStaff ON orderedStaff.id = sm.id SET sm.displayOrder = orderedStaff.newDisplayOrder'
			]
		],

		//imani

		//galen

		//chloe
	
		//pedro

		//mark j
		'add_num_sample_titles_to_email_template' => [
			'title' => 'Add Number of Sample Titles to Email Template',
			'description' => 'Adds a column to control how many sample titles are shown in saved search alert emails.',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE email_template ADD COLUMN numSampleTitles INT NOT NULL DEFAULT 3"
			]
		], //add_num_sample_titles_to_email_template

		//lucas

		//tomas

		// stephen

		//jacob - OpenFifth


	];
}
