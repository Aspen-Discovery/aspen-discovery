<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_06_00(): array {
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
		'vendor_sso_login' => [
			'title' => 'Add Vendor SSO Login field to IP Address',
			'description' => 'Add a flag for if an IP address is allowed to use vendor sso credentials',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE ip_lookup ADD COLUMN vendorSSOLogin TINYINT(1) DEFAULT 0',
			]
		],
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
