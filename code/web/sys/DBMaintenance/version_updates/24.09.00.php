<?php

function getUpdates24_09_00(): array {
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
		'show_checkout_grid_by_format' => [
			'title' => 'Show Sierra Checkout Grid by Format',
			'description' => 'Add the ability to enable or disable the Sierra checkout grid by Format',
			'continueOnError' => false,
			'sql' => [
				"INSERT INTO record_identifiers_to_reload (type, identifier) SELECT type, identifier from grouped_work_primary_identifiers where type = 'palace_project' and NOT identifier REGEXP '^[0-9]+$'"
			]
		], //show_checkout_grid_by_format
		'force_reindex_of_old_style_palace_project_identifiers' => [
			'title' => 'Force Reindex of Old Style Palace Project Identifiers',
			'description' => 'Force Reindex of Old Style Palace Project Identifiers',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE format_map_values ADD COLUMN displaySierraCheckoutGrid TINYINT(1) DEFAULT 0',
				"UPDATE format_map_values SET displaySierraCheckoutGrid = 1 where format IN ('Journal', 'Newspaper', 'Print Periodical', 'Magazine')"
			]
		]

		//katherine - ByWater

		//kirstien - ByWater

		//kodi - ByWater

		//alexander - PTFS-Europe

		//chloe - PTFS-Europe

		//pedro - PTFS-Europe

		//James Staub - Nashville Public Library


		//other

	];
}