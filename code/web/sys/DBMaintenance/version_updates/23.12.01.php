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
    ];
}