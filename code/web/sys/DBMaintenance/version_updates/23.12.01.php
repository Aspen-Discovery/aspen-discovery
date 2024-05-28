<?php

function getUpdates23_12_01(): array {
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

		'force_overdrive_full_update' => [
			'title'=> 'Force OverDrive Full Update',
			'description' => 'Force a full update of OverDrive',
			'sql' => [
				'UPDATE overdrive_settings SET runFullUpdate = 1',
			]
		], //force_overdrive_full_update
		'summon_ip_addresses' => [
			'title' => 'Summon IP address configuration',
			'description' => 'Allow configuration of which IP addresses should automatically authenticate with Summon',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE ip_lookup ADD COLUMN authenticatedForSummon TINYINT DEFAULT 0',
			]
			], //summon authentication
    ];
}