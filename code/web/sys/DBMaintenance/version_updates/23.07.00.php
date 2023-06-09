<?php
/** @noinspection PhpUnused */
function getUpdates23_07_00(): array {
	$curTime = time();
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'continueOnError' => false,
			'sql' => [
				''
			]
		], //sample*/

		// mark

		// kodi
		'add_disallow_third_party_covers' => [
			'title' => 'Add option to disallow third party cover images for certain works',
			'description' => 'Add option to disallow third party cover images for certain works',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE bookcover_info ADD COLUMN disallowThirdPartyCover TINYINT(1) DEFAULT 0',
			],
		], //add_disallow_third_party_covers

		// kirstien
		'user_onboard_notifications' => [
			'title' => 'Add column to store if user should be onboarded about notifications',
			'description' => 'Add column in user table to store if they should be onboarded about app notifications when opening Aspen LiDA.',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE user ADD COLUMN onboardAppNotifications TINYINT(1) DEFAULT 1',
			],
		], //user_onboard_notifications

		// other
	];
}