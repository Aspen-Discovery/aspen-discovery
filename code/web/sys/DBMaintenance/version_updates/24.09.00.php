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
		'web_builder_resource_access_library' => [
			'title' => 'Add Web Resource Limit Access to Library',
			'description' => 'Add table to store settings for web resources that have limited access by library',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS web_builder_resource_access_library (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					webResourceId INT(11) NOT NULL, 
					libraryId INT(11) NOT NULL,
					UNIQUE INDEX (webResourceId, libraryId)
				) ENGINE INNODB',
			],
		],
		//web_builder_resource_access_library
		'migrate_web_resource_library_access_rules' => [
			'title' => 'Create web resource limit access rules for existing web resources with required login',
			'description' => 'Create web resource limit access rules for existing web resources with required login',
			'continueOnError' => true,
			'sql' => [
				'migrateWebResourceLibraryAccessRules',
			],
		],
		//migrate_web_resource_library_access_rules

		//kodi - ByWater

		//alexander - PTFS-Europe

		//chloe - PTFS-Europe

		//pedro - PTFS-Europe

		//James Staub - Nashville Public Library


		//other

	];
}

function migrateWebResourceLibraryAccessRules(&$update) {
	$libraries = [];
	$library = new Library();
	$library->find();
	while ($library->fetch()) {
		$libraries[] = $library->libraryId;
	}

	require_once ROOT_DIR . '/sys/WebBuilder/WebResource.php';
	$webResources = [];
	$webResource = new WebResource();
	$webResource->requireLoginUnlessInLibrary = 1;
	$webResource->find();
	while ($webResource->fetch()) {
		$webResources[] = $webResource->id;
	}

	foreach($webResources as $resource) {
		foreach ($libraries as $libraryId) {
			require_once ROOT_DIR . '/sys/WebBuilder/WebResourceAccessLibrary.php';
			$webResourceAccessLibrary = new WebResourceAccessLibrary();
			$webResourceAccessLibrary->webResourceId = $resource;
			$webResourceAccessLibrary->libraryId = $libraryId;
			if(!$webResourceAccessLibrary->find(true)) {
				$webResourceAccessLibrary->insert();
			}
		}
	}
}