<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_11_00(): array {
	$now = time();

	return [
		/*'name' => [
			 'title' => '',
			 'description' => '',
			 'continueOnError' => false,
			 'sql' => [
				 ''
			 ]
		 ], //name*/

		//mark n

		//kirstien

		//kodi

		//yanjun

		//imani

		//galen

		//chloe

		//pedro

		//mark j

		//lucas

		//tomas

		// stephen

		//jacob - OpenFifth

		'library_web_resources_open_directly' => [
			'title' => 'Library Web Resources Open Directly',
			'description' => 'Add a library setting to link Web Resources lists straight to the resource',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library ADD COLUMN openWebResourcesDirectly TINYINT(1) NOT NULL DEFAULT 0',
			]
		], //library_web_resources_open_directly

	];
}