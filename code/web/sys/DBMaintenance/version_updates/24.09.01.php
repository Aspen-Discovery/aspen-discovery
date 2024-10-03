<?php

function getUpdates24_09_01(): array {
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

		//kodi - ByWater
		'debug_info_update' => [
			'title' => 'Update Debug Info Column',
			'description' => 'Increase size of debuginfo column to accommodate more information.',
			'sql' => [
				'ALTER TABLE grouped_work_debug_info CHANGE COLUMN debugInfo debugInfo MEDIUMTEXT',
			],
		],
	];
}