<?php
/** @noinspection PhpUnused */
function getUpdates23_05_00(): array {
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

		//mark
		//kirstien
		'drop_securityId_cp' => [
			'title' => 'Drop securityId from Certified Payments',
			'description' => 'Drop securityId from Certified Payments Settings table',
			'sql' => [
				'ALTER TABLE deluxe_certified_payments_settings DROP COLUMN securityId',
			],
		],
		//drop_securityId_cp
		'add_tab_coloring_theme' => [
			'title' => 'Add tab coloring to themes',
			'description' => 'Adds column to specify tab colors in themes',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE themes ADD COLUMN inactiveTabBackgroundColor CHAR(7) DEFAULT '#ffffff'",
				'ALTER TABLE themes ADD COLUMN inactiveTabBackgroundColorDefault tinyint(1) DEFAULT 1',
				"ALTER TABLE themes ADD COLUMN inactiveTabForegroundColor CHAR(7) DEFAULT '#6B6B6B'",
				'ALTER TABLE themes ADD COLUMN inactiveTabForegroundColorDefault tinyint(1) DEFAULT 1',
				"ALTER TABLE themes ADD COLUMN activeTabBackgroundColor CHAR(7) DEFAULT '#e7e7e7'",
				'ALTER TABLE themes ADD COLUMN activeTabBackgroundColorDefault tinyint(1) DEFAULT 1',
				"ALTER TABLE themes ADD COLUMN activeTabForegroundColor CHAR(7) DEFAULT '#333333'",
				'ALTER TABLE themes ADD COLUMN activeTabForegroundColorDefault tinyint(1) DEFAULT 1',
			]
		],
		//add_tab_coloring_theme
		//kodi
		//other
	];
}