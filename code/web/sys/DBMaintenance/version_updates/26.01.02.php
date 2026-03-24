<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_01_02(): array {
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
		'ip_address_bypass_failed_login_checks' => [
			'title' => 'IP Address Bypass Failed Login checks',
			'description' => 'Add Bypass Failed Login checks to IP Address settings',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE ip_lookup ADD COLUMN bypassFailedLoginChecks TINYINT(1) DEFAULT 0',
			]
		], //ip_address_bypass_failed_login_checks
		'sierra_address_line_for_city_state_zip' => [
			'title' => 'Sierra Address Line for City State Zip',
			'description' => 'Allow the address line which is used for City State Zip to be defined',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library ADD COLUMN sierraAddressLineForCityState TINYINT(1) DEFAULT 2',
				'ALTER TABLE library ADD COLUMN sierraZipOnSameLineAsCityState TINYINT(1) DEFAULT 1',
			]
		], //sierra_address_line_for_city_state_zip
	];
}