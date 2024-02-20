<?php

function getUpdates24_02_04(): array {
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

		'aspen_site_offline_mode_check' => [
			'title'=> 'Add site offline mode check',
			'description' => 'Add site offline mode check',
			'sql' => [
				'ALTER TABLE aspen_sites ADD COLUMN isOfflineMode TINYINT(1) DEFAULT 0',
			]
		], //aspen_site_offline_mode_check
    ];
}