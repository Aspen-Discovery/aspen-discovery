<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_07_00(): array {
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


		//kirstien

		//kodi
		'series_to_group_with' => [
			'title' => 'Add Column in Series Table for Grouping',
			'description' => 'Add column for seriesToGroupWithId in Series table to be used for merging/grouping series.',
			'sql' => [
				'ALTER TABLE series ADD COLUMN seriesToGroupWithId CHAR(40)',
			]
		],

		//yanjun
		'add_user_agent_retention_months' => [
			'title' => 'Add User Agent Retention Months',
			'description' => 'Add userAgentRetentionMonths column to system_variables',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE system_variables ADD COLUMN userAgentRetentionMonths INT NOT NULL DEFAULT 3'
			]
		], //add_user_agent_retention_months
		'add_user_agent_usage_year_month_index' => [
			'title' => 'Add User Agent Usage Year Month Index',
			'description' => 'Add an index on year and month to usage_by_user_agent for retention cleanup',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE usage_by_user_agent ADD INDEX idx_year_month (year, month)'
			]
		], //add_user_agent_usage_year_month_index

		//imani

		//galen

		//chloe
		'library_show_checkout_renewal_fee_message' => [
			'title' => 'Add Show Checkout Renewal Fee Message to Library',
			'description' => 'Adds a setting to control whether the checkout renewal fee message is shown to patrons',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library ADD COLUMN showCheckoutRenewalFeeMessage TINYINT(1) DEFAULT 1',
			]
		], //library_show_checkout_renewal_fee_message
		'library_show_hold_fee_message' => [
			'title' => 'Add Show Hold Fee Message to Library',
			'description' => 'Adds a setting to control whether the hold fee message is shown to patrons',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library ADD COLUMN showHoldFeeMessage TINYINT(1) DEFAULT 1',
			]
		], //library_show_hold_fee_message
	
		//pedro

		//mark j

		//lucas

		//tomas

		// stephen
		'theme_full_width_content' => [
			'title' => 'Add fullWidthContent column',
			'description' => 'Add fullWidthContent column to themes table.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE themes ADD COLUMN fullWidthContent TINYINT(1) DEFAULT 0',
			]
		], //theme_full_width_content

		//other

	];
}
