<?php
/** @noinspection PhpUnused */
function getUpdates23_04_03(): array {
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
		'force_processing_empty_works' => [
			'title' => 'Force processing empty works',
			'description' => 'Force Processing Empty Grouped Works',
			'sql' => [
				"UPDATE system_variables set processEmptyGroupedWorks = 1",
			],
		],
	];
}
