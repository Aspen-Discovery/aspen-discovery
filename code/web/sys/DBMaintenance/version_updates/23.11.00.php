<?php

function getUpdates23_11_00(): array {
	$curTime = time();
	return [
		//mark - ByWater
		//kirstien - ByWater
		//kodi - ByWater
		//Alexander - PTFS
		'display_list_author_control' => [
			'title' => 'User List Author Control',
			'description' => 'Add a setting to allow users to control whether their name appears on public lists they have created.',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE  user_list ADD COLUMN displayListAuthor TINYINT(1) DEFAULT 1',
				'ALTER TABLE user ADD COLUMN displayListAuthor TINYINT(1) DEFAULT 1',
			],
		],
		//Jacob - PTFS
		'user_cookie_preference_essential' => [
			'title' => 'Add user editable cookie preferences for essential cookies',
			'description' => 'Allow essential cookie preferences to be saved on a per user basis',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE user add column userCookiePreferenceEssential INT(1) DEFAULT 0",
			],
		],//user_cookie_preference_essential
		'user_cookie_preference_analytics' => [
			'title' => 'Add user editable cookie preferences for analytics cookies',
			'description' => 'Allow analytics cookie preferences to be saved on a per user basis',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE user add column userCookiePreferenceAnalytics INT(1) DEFAULT 0",
			],
		],//user_cookie_preference_analytics
		//other
    ];
}

