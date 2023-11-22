<?php

function getUpdates23_12_00(): array {
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

		//mark - ByWater

		//kirstien - ByWater

		//kodi - ByWater
		'rename_axis360_permission' => [
			'title' => 'Rename Permission: Administer Axis 360',
			'description' => 'Rename permission "Administer Axis 360" to "Administer Boundless"',
			'continueOnError' => true,
			'sql' => [
				"UPDATE permissions SET description = 'Allows the user configure Boundless integration for all libraries.' WHERE name = 'Administer Axis 360'",
				"UPDATE permissions SET name = 'Administer Boundless' WHERE name = 'Administer Axis 360'",
			]
		], //rename_axis360_permission
		'rename_axis360_module' => [
			'title' => 'Rename Axis 360 Module',
			'description' => 'Rename Axis 360 module to Boundless',
			'continueOnError' => true,
			'sql' => [
				"UPDATE modules SET name = 'Boundless' WHERE name = 'Axis 360'",
			]
		], //rename_axis360_module
    ];
}