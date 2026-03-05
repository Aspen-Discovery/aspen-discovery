<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_03_00(): array {
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
		'add_locations_to_exclude_availability_for' => [
			'title' => 'Add Locations to Exclude Availability For',
			'description' => 'Add Locations to Exclude Availability For',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE library add column locationsToExcludeAvailabilityFor varchar(255) NOT NULL DEFAULT ''",
				"ALTER TABLE location add column locationsToExcludeAvailabilityFor varchar(255) NOT NULL DEFAULT ''",
			]
		], //add_locations_to_exclude_availability_for

		//kirstien
		'add_cloud_library_sunday_reindex_option' => [
			'title' => 'Add option for cloudLibrary to reindex on Sundays',
			'description' => 'Add checkbox for cloudLibrary to reindex on Sundays at 8PM to cloudLibrary Settings',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE cloud_library_settings ADD COLUMN reindexOnSunday TINYINT(1) DEFAULT 1',
			]
		],
		//add_cloud_library_sunday_reindex_option

		//kodi
		'add_bill_reason_translation_map' => [
			'title' => 'Add Bill Reason Translation Map',
			'description' => 'Add bill reason translation map for Symphony libraries',
			'sql' => [
				"addBillReasonTranslationMap",
			]
		],
		//add_bill_reason_translation_map

		//yanjun
		'require_pin_for_palace_project' => [
			'title' => 'Add Require PIN for Palace Project Setting',
			'description' => 'Add Require PIN for Palace Project Setting',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE palace_project_settings ADD COLUMN requirePin TINYINT(1) DEFAULT 1',
			]
		], //allow_require_pin_for_palace_project
		'clean_up_event_fields_allowable_values' => [
			'title' => 'Clean up Event Fields Allowable Values when type is not select lists',
			'description' => 'Clean up Event Fields Allowable Values when type is not select lists',
			'continueOnError' => false,
			'sql' => [
				"UPDATE event_field SET allowableValues = '' WHERE type <> 3",
			]
		], //clean_up_event_fields_allowable_values

		//imani

		//galen

		//alexander
		'add_default_event_calendar_display_dropdown' => [
			'title' => 'Add Default Event Calendar Display Dropdown',
			'description' => 'Add the option of selecting the default display for the native events calendar',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE library ADD COLUMN eventsDefaultCalendarView TINYINT(1) NOT NULL DEFAULT 0",
			],
		], //add_default_event_calendar_display_dropdown

		//chloe

		//mark j
		'notify_saved_searches' => [
			'title' => 'User - Allow patrons to choose if they want email notifications when saved searches are updated.',
			'description' => 'Patrons will gain the choice within Your Preferences to have Aspen notify them via email when updates to their saved searches occur.',
			'sql' => [
				"ALTER TABLE user ADD COLUMN notifySavedSearches tinyint(1) NOT NULL DEFAULT 1",
			]
		], //notify_saved_searches

		//lucas


		//tomas

		// stephen
		'add_success_button_to_theme' => [
			'title' => 'Theme - Add Success button customization',
			'description' => 'In Themes, libraries can now customize the Bootstrap Success button.',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE themes ADD COLUMN successButtonBackgroundColor char(7) DEFAULT '#5cb85c'",
				"ALTER TABLE themes ADD COLUMN successButtonBackgroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE themes ADD COLUMN successButtonForegroundColor char(7) DEFAULT '#000000'",
				"ALTER TABLE themes ADD COLUMN successButtonForegroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE themes ADD COLUMN successButtonBorderColor char(7) DEFAULT '#4cae4c'",
				"ALTER TABLE themes ADD COLUMN successButtonBorderColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE themes ADD COLUMN successButtonHoverBackgroundColor char(7) DEFAULT '#449d44'",
				"ALTER TABLE themes ADD COLUMN successButtonHoverBackgroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE themes ADD COLUMN successButtonHoverForegroundColor char(7) DEFAULT '#000000'",
				"ALTER TABLE themes ADD COLUMN successButtonHoverForegroundColorDefault tinyint(1) DEFAULT 1",
				"ALTER TABLE themes ADD COLUMN successButtonHoverBorderColor char(7) DEFAULT '#398439'",
				"ALTER TABLE themes ADD COLUMN successButtonHoverBorderColorDefault tinyint(1) DEFAULT 1",
			]
		],

		//other


	];
}
function addBillReasonTranslationMap(&$update): void {
	$ils = null;
	require_once ROOT_DIR . '/sys/Account/AccountProfile.php';
	$accountProfiles = new AccountProfile();
	$accountProfiles->find();
	while ($accountProfiles->fetch()) {
		if ($accountProfiles->ils == 'symphony') {
			$indexingProfile = $accountProfiles->getIndexingProfile();
			if ($indexingProfile) {
				global $aspen_db;
				$aspen_db->query("INSERT INTO translation_maps (indexingProfileId, name) VALUES ($indexingProfile->id, 'bill_reason')");
			}
		}
	}
}
