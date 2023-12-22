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

		'reset_last_index_time_23_12_02' => [
			'title'=> 'Reset Last Index Time 23.12.02',
			'description' => 'Reset Last Index Time 23.12.02',
			'sql' => [
				'UPDATE indexing_profiles SET lastUpdateOfChangedRecords = 1703034000',
				'UPDATE overdrive_settings SET lastUpdateOfChangedRecords = 1703034000',
				'UPDATE hoopla_settings SET lastUpdateOfChangedRecords = 1703034000',
				'UPDATE axis360_settings SET lastUpdateOfChangedRecords = 1703034000',
				'UPDATE cloud_library_settings SET lastUpdateOfChangedRecords = 1703034000',
			]
		], //reset_last_index_time_23_12_02
    ];
}