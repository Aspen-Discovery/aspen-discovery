<?php

function getUpdates23_12_00(): array {
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

		//mark - ByWater

		'disable_circulation_actions' => [
			'title' => 'Disable Circulation Actions',
			'description' => 'Add an option to disable circulation actions for a user.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE user ADD COLUMN disableCirculationActions TINYINT(1) DEFAULT 0'
			]
		], //name

		//kirstien - ByWater

		//kodi - ByWater
		'rename_axis360_permission' => [
			'title' => 'Rename Permission: Administer Axis 360',
			'description' => 'Rename permission "Administer Axis 360" to "Administer Boundless"',
			'continueOnError' => true,
			'sql' => [
				"UPDATE permissions SET description = 'Allows the user configure Boundless integration for all libraries.' WHERE name = 'Administer Axis 360'",
				"UPDATE permissions SET name = 'Administer Boundless' WHERE name = 'Administer Axis 360'",
			]
		], //rename_axis360_permission
		'rename_boundless_module' => [
			'title' => 'Rename Boundless Module',
			'description' => 'Revert change where Axis 360 module was renamed to Boundless',
			'continueOnError' => true,
			'sql' => [
				"UPDATE modules SET name = 'Axis 360' WHERE name = 'Boundless'",
			]
		], //rename_boundless_module
		'readerName' => [
			'title' => 'Libby Reader Name',
			'description' => 'Name of Libby product to display to patrons. Default is "Libby"',
			'sql' => [
				"ALTER TABLE overdrive_scopes DROP COLUMN IF EXISTS libbySora",
				"ALTER TABLE overdrive_scopes ADD COLUMN readerName varchar(25) DEFAULT 'Libby'",
			],
		],
		//readerName
		'rename_overdrive_permission' => [
			'title' => 'Rename Permission: Administer OverDrive',
			'description' => 'Rename permission "Administer OverDrive" to "Administer Libby/Sora"',
			'continueOnError' => true,
			'sql' => [
				"UPDATE permissions SET description = 'Allows the user configure Libby/Sora integration for all libraries.' WHERE name = 'Administer OverDrive'",
				"UPDATE permissions SET name = 'Administer Libby/Sora' WHERE name = 'Administer OverDrive'",
			]
		], //rename_overdrive_permission

		//lucas - Theke
		'show_quick_poll_results' => [
			'title' => 'Display Quick Poll Results',
			'description' => 'Allows the user to show the results of quick polls to those patrons who are not logged in, as well as to choose whether to show graphs, tables or both.',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE  web_builder_quick_poll ADD COLUMN showResultsToPatrons TINYINT(1) DEFAULT 0',
			],
		], // show_quick_poll_results

		//alexander - PTFS Europe
		'library_show_language_and_display_in_header' => [
			'title' => 'Library Show Language and Display in Header',
			'description' => 'Add option to allow the language and display settings to be shown in the page header',
			'sql' => [
				"ALTER TABLE library ADD languageAndDisplayInHeader INT(1) DEFAULT 1",
			],
		], //library_show_language_and_display_in_header
		'location_show_language_and_display_in_header' => [
			'title' => 'Location Show Language and Display in Header',
			'description' => 'Add option to allow the language and display settings to be shown in the page header',
			'sql' => [
				"ALTER TABLE location ADD languageAndDisplayInHeader INT(1) DEFAULT 1",
			],
		], //location_show_language_and_display_in_header
	];
}
