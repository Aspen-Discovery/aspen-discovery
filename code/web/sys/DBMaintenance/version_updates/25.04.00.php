<?php

function getUpdates25_04_00(): array {
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

		//mark - Grove

		//katherine - Grove

		//kirstien - Grove

		//kodi - Grove

		//Yanjun Li - ByWater

		// Leo Stoyanov - BWS
		'add_prioritize_available_records_for_title_selection' => [
			'title' => 'Add Prioritize Available Records for Title Selection Option to Indexing Profiles',
			'description' => 'Adds a setting to prioritize available records when selecting titles for display in grouped works (Koha-specific).',
			'sql' => [
				"ALTER TABLE indexing_profiles ADD COLUMN IF NOT EXISTS prioritizeAvailableRecordsForTitleSelection TINYINT(1) DEFAULT 0"
			],
		], // add_ignore_on_order_records_for_title_selection


		//alexander - PTFS-Europe

		//chloe - PTFS-Europe

		//James Staub - Nashville Public Library

		//Lucas Montoya - Theke Solutions

		//other

	];
}