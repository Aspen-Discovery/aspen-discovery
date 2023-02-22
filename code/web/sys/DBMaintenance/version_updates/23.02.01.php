<?php
/** @noinspection PhpUnused */
function getUpdates23_02_01(): array {
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
		'add_sso_unique_field_match' => [
			'title' => 'Add option for a SAML custom ILS unique attribute to match against',
			'description' => 'Add field to allow library to match against a custom field in the ILS for a unique identifier with SAML',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE sso_setting ADD COLUMN ssoILSUniqueAttribute VARCHAR(255) default NULL',
			]
		],
		//add_sso_unique_field_match

		//kodi

		//other

	];
}