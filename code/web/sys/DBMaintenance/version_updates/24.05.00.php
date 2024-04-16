<?php

function getUpdates24_05_00(): array {
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
		'remove_individual_marc_path' => [
			'title' => 'Remove Individual MARC Path',
			'description' => 'Remove Individual MARC Path',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE indexing_profiles DROP COLUMN individualMarcPath',
				'ALTER TABLE indexing_profiles DROP COLUMN numCharsToCreateFolderFrom',
				'ALTER TABLE indexing_profiles DROP COLUMN createFolderFromLeadingCharacters',
				'ALTER TABLE sideloads DROP COLUMN individualMarcPath',
				'ALTER TABLE sideloads DROP COLUMN numCharsToCreateFolderFrom',
				'ALTER TABLE sideloads DROP COLUMN createFolderFromLeadingCharacters',
			]
		], //remove_individual_marc_path

		//kirstien - ByWater

		//kodi - ByWater

		//other


	];
}