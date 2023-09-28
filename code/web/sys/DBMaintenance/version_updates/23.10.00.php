<?php
/** @noinspection PhpUnused */
function getUpdates23_10_00(): array {
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
		'add_always_display_renew_count' => [
			'title' => 'Add option to always show renewal count',
			'description' => 'Add option in Library Systems to always show the renewal count for a checkout',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE library ADD alwaysDisplayRenewalCount TINYINT(1) default 0',
			]
		], //add_always_display_renew_count
		'add_lida_system_messages_options' => [
			'title' => 'System messages in LiDA',
			'description' => 'Add options for pushing system messages to LiDA',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE system_messages ADD appMessage VARCHAR(280) default NULL',
				'ALTER TABLE system_messages ADD pushToApp TINYINT(1) default 0',
			]
		], //add_lida_system_messages_options

		//kodi - ByWater
		'theme_explore_more_images' => [
			'title' => 'Theme - Set custom images for explore more categories',
			'description' => 'Update theme table to have custom image values for each explore more category',
			'sql' => [
				"ALTER TABLE themes ADD COLUMN catalogImage VARCHAR(100) default ''",
				"ALTER TABLE themes ADD COLUMN genealogyImage VARCHAR(100) default ''",
				"ALTER TABLE themes ADD COLUMN articlesDBImage VARCHAR(100) default ''",
				"ALTER TABLE themes ADD COLUMN eventsImage VARCHAR(100) default ''",
				"ALTER TABLE themes ADD COLUMN listsImage VARCHAR(100) default ''",
				"ALTER TABLE themes ADD COLUMN libraryWebsiteImage VARCHAR(100) default ''",
				"ALTER TABLE themes ADD COLUMN historyArchivesImage VARCHAR(100) default ''",
			],
		], //theme_explore_more_images
	];
}