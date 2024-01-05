<?php

function getUpdates23_12_02(): array {
	$curTime = time();
	return [
        /*'name' => [
            'title' => '',
            'description' => '',
            'continueOnError' => false,
            'sql' => [
                ''
            ]
		], //name*/

    // James Staub - Nashville Public Library

        'account_profile_carlx_database_view_version' => [
            'title'=> 'Account Profile CarlX database View Version',
            'description' => 'Adds CarlX database View Version to Account Profile',
            'continueOnError' => false,
            'sql' => [
                "ALTER TABLE account_profiles ADD carlXViewVersion ENUM('v', 'v2') NOT NULL DEFAULT 'v'"
            ]
        ], //account_profile_carlx_database_view_version
    ];
}