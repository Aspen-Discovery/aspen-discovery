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
