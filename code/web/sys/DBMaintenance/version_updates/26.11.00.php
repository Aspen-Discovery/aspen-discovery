<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_11_00(): array {
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

		//imani

		//galen

		//chloe

		//pedro

		//mark j

		//lucas

		//tomas

		// stephen

		//jacob - OpenFifth

		'api_usage_client' => [
			'title' => 'API Usage Client',
			'description' => 'Track which third party client made each API call',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE api_usage ADD COLUMN IF NOT EXISTS apiClient VARCHAR(255) NOT NULL DEFAULT ''",
				'ALTER TABLE api_usage DROP INDEX IF EXISTS uniqueness',
				'ALTER TABLE api_usage ADD UNIQUE INDEX uniqueness(instance, year, month, day, module, method, apiClient)',
			]
		], //api_usage_client

	];
}