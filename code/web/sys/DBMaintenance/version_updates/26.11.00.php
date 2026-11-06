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
		'paypal_client_credentials_length' => [
			'title' => 'Expand PayPal Client Credential Lengths',
			'description' => 'Increase the maximum length of PayPal Client ID and Client Secret to 100 characters.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE paypal_settings MODIFY COLUMN clientId VARCHAR(100) DEFAULT NULL',
				'ALTER TABLE paypal_settings MODIFY COLUMN clientSecret VARCHAR(100) DEFAULT NULL',
			]
		], //paypal_client_credentials_length

		//imani

		//galen

		//chloe

		//pedro

		//mark j

		//lucas

		//tomas
		'hide_urls_when_logged_out' => [
			'title' => 'Hide URLs When Logged Out',
			'description' => 'Add a regular expression to hide matching 856 URLs from patrons who are not logged in.',
			'sql' => [
				"ALTER TABLE grouped_work_display_settings ADD COLUMN hideUrlsWhenLoggedOutRegex VARCHAR(500) NOT NULL DEFAULT ''",
			],
		], //hide_urls_when_logged_out

		// stephen

		//jacob - OpenFifth


	];
}