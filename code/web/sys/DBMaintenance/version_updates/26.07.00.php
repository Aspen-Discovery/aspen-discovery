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

		//pedro

		//mark j

		//lucas

		//tomas

		// stephen

		//other

	];
}
