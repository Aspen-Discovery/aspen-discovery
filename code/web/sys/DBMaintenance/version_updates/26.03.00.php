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
		'local_ill_handle_remote_pickups' => [
			'title' => 'Local ILL handle remote pickups',
			'description' => 'Add settings to handle remote pickups of materials',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE user_checkout ADD COLUMN isLocalILL TINYINT(1) DEFAULT 0',
				'ALTER TABLE user_hold ADD COLUMN isLocalILL TINYINT(1) DEFAULT 0',
				'ALTER TABLE library ADD COLUMN includeRemoteCheckoutsInMaxLocalIllRequests TINYINT(1) DEFAULT 1',
			]
		], //local_ill_handle_remote_pickups
		'force_regrouping_of_hoopla_26_03' => [
			'title' => 'Force Regrouping of Hoopla (26.03)',
			'description' => 'Force Regrouping of Hoopla (26.03)',
			'sql' => [
				"UPDATE hoopla_settings set regroupAllRecords = 1"
			]
		], //force_regrouping_of_hoopla_26_03
		'indexing_profile_use_650_for_picture_books' => [
			'title' => 'Indexing Profile Use 650 for Picture Books',
			'description' => 'Allow using 650 for Picture Books in Indexing Profile',
			'sql' => [
				'ALTER TABLE indexing_profiles ADD COLUMN use650ForPictureBooks TINYINT(1) DEFAULT 0'
			]
		], //indexing_profile_use_650_for_picture_books

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
		'add_generated_rtl_css_to_theme' => [
			'title' => 'Add generated RTL CSS to theme',
			'description' => 'Add generated RTL CSS column to theme to fetch if a RTL language is active',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE themes ADD COLUMN generatedRTLCss LONGTEXT',
			]
		],
		//add_generated_rtl_css_to_theme

		//kodi
		'add_bill_reason_symphony_translation_map' => [
			'title' => 'Add Bill Reason Translation Map',
			'description' => 'Add bill reason translation map for Symphony libraries',
			'sql' => [
				"addBillReasonTranslationMap",
			]
		],
		//add_bill_reason_symphony_translation_map
		'remove_unused_permission_loan_rules' => [
			'title' => 'Remove unused permission loan rules',
			'description' => 'Remove unused permission loan rules at all times',
			'sql' => [
				"DELETE FROM role_permissions WHERE permissionId = (SELECT id FROM permissions WHERE name = 'Administer Loan Rules')",
				"DELETE FROM permissions WHERE name = 'Administer Loan Rules'",
			]
		], //remove_unused_permission_loan_rules
		'add_title_search_behavior_setting' => [
			'title' => 'Title Search Behavior Setting',
			'description' => 'Add title search behavior setting in system variables',
			'sql' => [
				"ALTER TABLE system_variables ADD COLUMN titleSearchBehavior INT DEFAULT 1",
			]
		], //add_title_search_behavior_setting

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
		'add_option_to_add_location_to_event_thumbail_image' => [
			'title' => 'Add Option to Add Location to Event Thumnail Image',
			'description' => 'Add ability to choose to add event location to event thumbnail image',
			'sql' => [
				"ALTER TABLE event ADD COLUMN displayEventBranchOnThumbnail TINYINT(1) DEFAULT 0",
				"ALTER TABLE user_events_entry ADD COLUMN displayEventBranchOnThumbnail TINYINT(1) DEFAULT 0"
			]
		], //add_option_to_add_location_to_event_thumbnail_image 
		'add_default_event_calendar_display_dropdown' => [
			'title' => 'Add Default Event Calendar Display Dropdown',
			'description' => 'Add the option of selecting the default display for the native events calendar',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE library ADD COLUMN eventsDefaultCalendarView TINYINT(1) NOT NULL DEFAULT 0",
			],
		], //add_default_event_calendar_display_dropdown
		'add_user_removed_campaigns_table' =>[
			'title' => 'Add User Removed Campaigns Table',
			'description' => 'Add the ability for user to remove campaigns from their account area',
			'continueOnError' => false,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS user_removed_campaigns (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					userId INT NOT NULL, 
					campaignId INT NOT NULL, 
					UNIQUE KEY user_campaign (userid, campaignId),
					INDEX (userId),
					INDEX (campaignId)
				) ENGINE = InnoDB'
			]
		],// add_user_removed_campaigns_table


		//chloe
		'add_option_to_set_display_event_location_on_event_type' => [
			'title' => 'Add Option to Set Display Event Location On Event Type',
			'description' => 'Add ability to choose to add event location to event thumbnail image at the event type level',
			'sql' => [
				"ALTER TABLE event_type ADD COLUMN displayEventBranchOnThumbnail TINYINT(1) DEFAULT 0",
			]
		], //add_option_to_set_customizability_of_display_event_location_on_event_type
		'add_option_to_set_customizability_of_display_event_location_on_event_type' => [
			'title' => 'Add Option to Set Customizability Of Display Event Location On Event Type',
			'description' => 'Add ability to choose the customizability of including event location to event thumbnail image at the event type level',
			'sql' => [
				"ALTER TABLE event_type ADD COLUMN displayEventBranchOnThumbnailCustomizable TINYINT(1) DEFAULT 0",
			]
		], //add_option_to_set_customizability_of_display_event_location_on_event_type

		//mark j
		'notify_saved_searches' => [
			'title' => 'User - Allow patrons to choose if they want email notifications when saved searches are updated.',
			'description' => 'Patrons will gain the choice within Your Preferences to have Aspen notify them via email when updates to their saved searches occur.',
			'sql' => [
				"ALTER TABLE user ADD COLUMN notifySavedSearches tinyint(1) NOT NULL DEFAULT 0",
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
