<?php

function getUpdates24_08_00(): array {
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

		//kirstien - ByWater
		'add_ils_notification_settings' => [
			'title' => 'Add table ils_notification_settings',
			'description' => '',
			'continueOnError' => false,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS ils_notification_setting (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(50)
				) ENGINE INNODB',
			]
		], //add_ils_notification_settings

		'add_user_ils_messages' => [
			 'title' => 'Add table user_ils_messages',
			 'description' => '',
			 'continueOnError' => false,
			 'sql' => [
				 "CREATE TABLE IF NOT EXISTS user_ils_messages (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					messageId VARCHAR(100) NOT NULL,
					userId INT(11),
					type VARCHAR(50),
					status enum('pending', 'sent', 'failed') DEFAULT 'pending',
					title VARCHAR(200),
					content MEDIUMTEXT,
					error VARCHAR(255),
					dateQueued INT(11),
					dateSent INT(11),
					isRead TINYINT(1) DEFAULT 0
				) ENGINE INNODB",
			 ]
		 ], //add_user_ils_messages

		'add_ils_message_type' => [
			'title' => 'Add table ils_message_type',
			'description' => '',
			'continueOnError' => false,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS ils_message_type (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					module VARCHAR(255),
					code VARCHAR(255),
					name VARCHAR(255),
					isDigest TINYINT(1) DEFAULT 0,
					locationCode VARCHAR(255),
					isEnabled TINYINT(1) DEFAULT 1,
					ilsNotificationSettingId INT(11)
				) ENGINE INNODB',
			]
		], //add_ils_message_type

		'add_ilsNotificationSettingId' => [
			'title' => 'Add ilsNotificationSettingId to aspen_lida_notification_setting',
			'description' => 'Add ilsNotificationSettingId to aspen_lida_notification_setting',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE aspen_lida_notification_setting ADD COLUMN ilsNotificationSettingId INT(11) DEFAULT -1',
			]
		], //add_ilsNotificationSettingId

		//kodi - ByWater
		'overdrive_series_length' => [
			'title' => 'Series Length',
			'description' => 'Increase column length for series in overdrive_api_products table to accommodate long series names in Libby',
			'sql' => [
				'ALTER TABLE overdrive_api_products CHANGE COLUMN series series VARCHAR(255)',
			],
		],//overdrive_series_length

		//katherine - ByWater

		//alexander - PTFS-Europe
		'update_cookie_management_preferences' => [
			'title' => 'Update Cookie Management Preferences',
			'description' => 'Update cookie management preferences for user tracking',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE user ADD COLUMN userCookiePreferenceAxis360 TINYINT(1) DEFAULT 0",
			],
		], //update_user_tracking_cookies
		'update_cookie_management_preferences_more_options' => [
			'title' => 'Update Cookie Management Preferences: More Options',
			'description' => 'Update cookie management preferences for user tracking - adding more options',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE user ADD COLUMN userCookiePreferenceEbscoEds TINYINT(1) DEFAULT 0",
				"ALTER TABLE user ADD COLUMN userCookiePreferenceEbscoHost TINYINT(1) DEFAULT 0",
				"ALTER TABLE user ADD COLUMN userCookiePreferenceSummon TINYINT(1) DEFAULT 0",
				"ALTER TABLE user ADD COLUMN userCookiePreferenceEvents TINYINT(1) DEFAULT 0",
				"ALTER TABLE user ADD COLUMN userCookiePreferenceHoopla TINYINT(1) DEFAULT 0",
				"ALTER TABLE user ADD COLUMN userCookiePreferenceOpenArchives TINYINT(1) DEFAULT 0",
				"ALTER TABLE user ADD COLUMN userCookiePreferenceOverdrive TINYINT(1) DEFAULT 0",
				"ALTER TABLE user ADD COLUMN userCookiePreferencePalaceProject TINYINT(1) DEFAULT 0",
				"ALTER TABLE user ADD COLUMN userCookiePreferenceSideLoad TINYINT(1) DEFAULT 0",
			],
		], //update_user_tracking_cookies
		'add_cookie_management_preferences' => [
			'title' => 'Cookie Management for Cloud Libray and Website',
			'description' => 'Add Cookie Management preferences for website and cloud library',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE user ADD COLUMN userCookiePreferenceCloudLibrary TINYINT(1) DEFAULT 0",
				"ALTER TABLE user ADD COLUMN userCookiePreferenceWebsite TINYINT(1) DEFAULT 0",
			],
		], //add_user_tacking_cookie_preferences
		'add_cookie_management_preference_for_all_external_search_services' => [
			'title' => 'Cookie Management Preference for All External Search Services',
			'description' => 'Add a Cookie Management Preference that controls consent for all external search services',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE user ADD COLUMN userCookiePreferenceExternalSearchServices TINYINT(1) DEFAULT 0",
			],
		], //add_user_tracking_preferences_for_all_external_search_services
		'display_explore_more_bar' => [
			'title' => 'Display Explore More Bar',
			'description' => 'Display Explore More Bar',
			'sql' => [
				'ALTER TABLE library ADD COLUMN displayExploreMoreBarInSummon TINYINT(1) DEFAULT 1',
				'ALTER TABLE location ADD COLUMN displayExploreMoreBarInSummon TINYINT(1) DEFAULT 1',
				'ALTER TABLE library ADD COLUMN displayExploreMoreBarInEbscoEds TINYINT(1) DEFAULT 1',
				'ALTER TABLE location ADD COLUMN displayExploreMoreBarInEbscoEds TINYINT(1) DEFAULT 1',
			],
		],
		'display_explore_more_bar_additional_options' => [
			'title' => 'Display Explore More Bar Additional Options',
			'description' => 'Display Explore More Bar in Catalog Search',
			'sql' => [
				'ALTER TABLE library ADD COLUMN displayExploreMoreBarInCatalogSearch TINYINT(1) DEFAULT 1',
				'ALTER TABLE location ADD COLUMN displayExploreMoreBarInCatalogSearch TINYINT(1) DEFAULT 1',
			],
		],
		'display_explore_more_bar_in_ebsco_host_search' => [
			'title' => 'Display Explore More Bar in Esbco Host Search',
			'description' => 'Display Explore More Bar in Esbco Host Search',
			'sql' => [
				'ALTER TABLE library ADD COLUMN displayExploreMoreBarInEbscoHost TINYINT(1) DEFAULT 1',
				'ALTER TABLE location ADD COLUMN displayExploreMoreBarInEbscoHost TINYINT(1) DEFAULT 1',
			],
		],
		'remove_individual_eContent_analytics_controls' => [
			'title' => 'Remove Individual eContent Analytics Controls',
			'description' => 'Remove individual eContent Analytics Controls',
			'sql' => [
				"ALTER TABLE user DROP COLUMN userCookiePreferenceEbscoEds TINYINT(1) DEFAULT 0",
				"ALTER TABLE user DROP COLUMN userCookiePreferenceEbscoHost TINYINT(1) DEFAULT 0",
				"ALTER TABLE user DROP COLUMN userCookiePreferenceSummon TINYINT(1) DEFAULT 0",
				"ALTER TABLE user DROP COLUMN userCookiePreferenceHoopla TINYINT(1) DEFAULT 0",
				"ALTER TABLE user DROP COLUMN userCookiePreferenceOverdrive TINYINT(1) DEFAULT 0",
				"ALTER TABLE user DROP COLUMN userCookiePreferencePalaceProject TINYINT(1) DEFAULT 0",
				"ALTER TABLE user DROP COLUMN userCookiePreferenceSideLoad TINYINT(1) DEFAULT 0",
			],
		],
		
		

		//pedro - PTFS-Europe

		//James Staub - Nashville Public Library
		'web_builder_custom_form_increase_email' => [
			'title' => 'Increase Web Builder Custom Form "Email Results To" field character limit.',
			'description' => 'Increase Web Builder Custom Form "Email Results To" field character limit.',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE web_builder_custom_form MODIFY COLUMN emailResultsTo VARCHAR(150)",
			]
		], //web_builder_custom_form_increase_email

		//James Staub - Nashville Public Library
		'web_builder_custom_form_increase_email' => [
			'title' => 'Increase Web Builder Custom Form "Email Results To" field character limit.',
			'description' => 'Increase Web Builder Custom Form "Email Results To" field character limit.',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE web_builder_custom_form MODIFY COLUMN emailResultsTo VARCHAR(150)",
			]
		], //web_builder_custom_form_increase_email

		//chloe - PTFS-Europe
		'show_in_search_facet_column' => [
			'title' => 'Show In Search Facet Column',
			'description' => 'Adds the showInSearchFacet column to the Location table',
			// 'continueOnError' => false,
			'sql' => [
				'ALTER TABLE location ADD COLUMN showInSearchFacet TINYINT(1) DEFAULT 1'
			]
			], //show_in_search_facet_column
		//other

	];
}