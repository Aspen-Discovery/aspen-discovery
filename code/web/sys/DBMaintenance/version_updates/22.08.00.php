<?php
/** @noinspection PhpUnused */
function getUpdates22_08_00() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'account_profile_oauth_client_secret_length' => [
			'title' => 'Adjust length for oAuth Client Secret in Account Profile',
			'description' => 'Adjust length for oAuth Client Secret in Account Profile',
			'sql' => [
				"ALTER TABLE account_profiles CHANGE COLUMN oAuthClientSecret oAuthClientSecret VARCHAR(50)",
			]
		], //account_profile_oauth_client_secret_length
	];
}
