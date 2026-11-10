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

		// stephen

		//jacob - OpenFifth


	];
}