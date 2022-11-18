<?php
/** @noinspection PhpUnused */
function getUpdates22_12_00(): array {
	$curTime = time();
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
        ], //sample*/

		//mark
		'custom_form_includeIntroductoryTextInEmail' => [
			'title' => 'Custom Form - includeIntroductoryTextInEmail',
			'description' => 'Allow introductory text to be included in the response email',
			'sql' => [
				'ALTER TABLE web_builder_custom_form ADD COLUMN includeIntroductoryTextInEmail TINYINT(1) default 0'
			]
		], //sample

		//kirstien
		'add_oauth_logout' => [
			'title' => 'Add custom OAuth gateway logout URL',
			'description' => 'Add custom OAuth gateway logout URL',
			'sql' => [
				'ALTER TABLE sso_setting ADD COLUMN oAuthLogoutUrl VARCHAR(255)'
			]
		], //add_oauth_logout
		'add_oauth_to_user' => [
			'title' => 'Add OAuth tokens to user table',
			'description' => 'Add columns to store OAuth access and refresh tokens in the user table',
			'sql' => [
				'ALTER TABLE user ADD COLUMN oAuthAccessToken VARCHAR(255)',
				'ALTER TABLE user ADD COLUMN oAuthRefreshToken VARCHAR(255)'
			]
		], //add_oauth_to_user
		'add_oauth_grant_type' => [
			'title' => 'Add custom OAuth grant type',
			'description' => 'Add custom OAuth grant type',
			'sql' => [
				'ALTER TABLE sso_setting ADD COLUMN oAuthGrantType TINYINT(1) DEFAULT 0'
			]
		], //add_oauth_grant_type
		'add_oauth_private_keys' => [
			'title' => 'Add custom OAuth private keys',
			'description' => 'Add custom OAuth private keys for authentication by client credentials',
			'sql' => [
				'ALTER TABLE sso_setting ADD COLUMN oAuthPrivateKeys VARCHAR(255)'
			]
		], //add_oauth_private_keys

		//kodi
        'user_disableAccountLinking' => [
            'title' => 'User Disable Account Linking',
            'description' => 'Adds switch for the user to disable account linking',
            'sql' => [
                "ALTER TABLE user ADD COLUMN disableAccountLinking TINYINT(1) DEFAULT '0'",
            ]
        ],//user_disableAccountLinking

		//other
	];
}