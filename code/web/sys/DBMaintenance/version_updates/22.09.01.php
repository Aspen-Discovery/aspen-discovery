
<?php
/** @noinspection PhpUnused */
function getUpdates22_09_01() : array
{
    $curTime = time();
    return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
        ], //sample*/
        'add_additional_library_sso_config_options' => [
			'title' => 'SSO - Additional library config options',
			'description' => 'Allow SSO configuration options to be specified',
			'sql' => [
				"ALTER TABLE library ADD column ssoEntityId VARCHAR(255)"
            ]
		] //add_library_sso_config_options
	];
}