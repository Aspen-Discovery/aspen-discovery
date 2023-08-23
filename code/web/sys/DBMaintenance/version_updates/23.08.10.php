<?php
/** @noinspection PhpUnused */
function getUpdates23_08_10(): array {
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
		'split_user_fields' => [
			'title' => 'Split User Fields',
			'description' => 'Split up user fields including barcode and username',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE user ADD COLUMN unique_ils_id varchar(36) COLLATE utf8mb4_general_ci NOT NULL",
				"ALTER TABLE user ADD COLUMN ils_barcode varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL",
				"ALTER TABLE user ADD COLUMN ils_username varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL",
				"ALTER TABLE user ADD COLUMN ils_password varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL",
				"UPDATE user set unique_ils_id = username where source NOT IN ('admin', 'admin_sso')",
				"UPDATE user set ils_barcode = cat_username where source NOT IN ('admin', 'admin_sso')",
				"UPDATE user set ils_password = cat_password where source NOT IN ('admin', 'admin_sso')",
				"UPDATE user set cat_username = '' where source IN ('admin', 'admin_sso')",
				"UPDATE user set cat_password = '' where source IN ('admin', 'admin_sso')",
			]
		], //split_user_fields

		// kirstien - ByWater
		'checkoutIsILL' => [
			'title' => 'Checkout - Is ILL',
			'description' => 'Add a property to determine if a checkout is ILL',
			'sql' => [
				'ALTER TABLE user_checkout ADD COLUMN isIll TINYINT(1) DEFAULT 0',
			],
		], //checkoutIsILL
		'readingHistoryIsILL' => [
			'title' => 'Reading History Work - Is ILL',
			'description' => 'Add a property to determine if a reading history work is ILL',
			'sql' => [
				'ALTER TABLE user_reading_history_work ADD COLUMN isIll TINYINT(1) DEFAULT 0',
			],
		], //readingHistoryIsILL
	];
}