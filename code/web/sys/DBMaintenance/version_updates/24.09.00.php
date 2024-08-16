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
			'descritpion' => 'Add a column to hold local analytics tracking choices',
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

		//chloe - PTFS-Europe

		//pedro - PTFS-Europe

		//James Staub - Nashville Public Library


		//other

	];
}