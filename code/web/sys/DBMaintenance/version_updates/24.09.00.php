<?php

function getUpdates24_09_00(): array {
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

		//katherine - ByWater

		//kirstien - ByWater

		//kodi - ByWater

		//alexander - PTFS-Europe
		'update_cookie_management_preferences_more_options' => [
			'title' => 'Update Cookie Management Preferences: More Options',
			'description' => 'Update cookie management preferences for user tracking - adding more options',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE user ADD COLUMN userCookiePreferenceEvents TINYINT(1) DEFAULT 0",
				"ALTER TABLE user ADD COLUMN userCookiePreferenceOpenArchives TINYINT(1) DEFAULT 0",
				"ALTER TABLE user ADD COLUMN userCookiePreferenceWebsite TINYINT(1) DEFAULT 0",
				"ALTER TABLE user ADD COLUMN userCookiePreferenceExternalSearchServices TINYINT(1) DEFAULT 0",
			],
		], 
		'add_local_analytics_column_to_user' => [
			'title' => 'Add Local Analytics Column To User',
			'description' => 'Add a column to hold local analytics tracking choices',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE user ADD COLUMN userCookiePreferenceLocalAnalytics TINYINT(1) DEFAULT 0",
				"UPDATE user
					INNER JOIN location ON user.homeLocationId = location.locationId
					INNER JOIN library ON location.libraryId = library.libraryId
					SET user.userCookiePreferenceLocalAnalytics = CASE
						WHEN library.cookieStorageConsent = 0 THEN 1
						ELSE 0
					END"
			],
		],
		'drop_columns_from_user_table' => [
			'title' => 'Drop Columns From User Table',
			'description' => 'Remove unneeded columns from user table',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE user DROP COLUMN userCookiePreferenceEvents",
				"ALTER TABLE user DROP COLUMN userCookiePreferenceOpenArchives",
				"ALTER TABLE user DROP COLUMN userCookiePreferenceWebsite",
				"ALTER TABLE user DROP COLUMN userCookiePreferenceExternalSearchServices",
			],
		],
		'add_analytics_data_cleared_flag' => [
			'title' => 'Add Analytics Data Cleared Flag',
			'description' => 'Add a flag to ensure analytics data clearing fucntion runs only once',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE user ADD COLUMN analyticsDataCleared TINYINT DEFAULT 0",
			],
		],

		//chloe - PTFS-Europe

		//pedro - PTFS-Europe

		//James Staub - Nashville Public Library


		//other

	];
}