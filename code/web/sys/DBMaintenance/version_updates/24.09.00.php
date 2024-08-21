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
		], //force_reindex_of_old_style_palace_project_identifiers
		'add_additional_info_to_palace_project_availability' => [
			'title' => 'Add Additional Info to Palace Project Availability',
			'description' => 'Store borrow and preview links as well as if a hold is needed',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE palace_project_title_availability ADD COLUMN borrowLink TINYTEXT',
				'ALTER TABLE palace_project_title_availability ADD COLUMN needsHold TINYINT DEFAULT 1',
				'ALTER TABLE palace_project_title_availability ADD COLUMN previewLink TINYTEXT',
			]
		], //add_additional_info_to_palace_project_availability
		'run_full_update_for_palace_project_24_09' =>[
			'title' => 'Run full update for Palace Project',
			'description' => 'Run full update for Palace Project',
			'continueOnError' => false,
			'sql' => [
				'UPDATE palace_project_settings SET runFullUpdate = 1',
			]
		], //run_full_update_for_palace_project_24_09
		//Mark - Grove
		'add_location_stat_group' => [
			'title' => 'Add Location Stat Group',
			'description' => 'Add Location Stat Group',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE location add statGroup INT(11) DEFAULT -1',
			]
		], //add_location_stat_group
		'add_permission_for_testing_checkouts' => [
			'title' => 'Add permission for testing checkouts',
			'description' => 'Add permission for testing checkouts',
			'continueOnError' => false,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Circulation', 'Test Self Check', '', 20, 'Allows users to test checking titles out within Aspen Discovey.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Test Self Check'))",
			]
		], //add_permission_for_testing_checkouts

		//katherine - ByWater

		//kirstien - ByWater
		'add_defaultContent_field' => [
			'title' => 'Add defaultContent to user_ils_messages',
			'description' => 'Add defaultContent to user_ils_messages',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE user_ils_messages ADD COLUMN defaultContent mediumtext',
			]
		], //add_defaultContent_field

		//kodi - ByWater

		//alexander - PTFS-Europe

		//chloe - PTFS-Europe

		//pedro - PTFS-Europe

		//James Staub - Nashville Public Library


		//other

	];
}