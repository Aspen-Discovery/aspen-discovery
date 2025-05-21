<?php

function getUpdates25_06_00(): array {
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
		'delete_orphaned_series_members' => [
			'title' => 'Delete Orphaned Series Members',
			'description' => 'Delete Series Members that are no longer linked to a valid grouped Work',
			'continueOnError' => false,
			'sql' => [
				'DELETE from series_member where id IN (select series_member.id from series_member left join grouped_work on series_member.groupedWorkPermanentId = permanent_id left join grouped_work_records on grouped_work_records.groupedWorkId = grouped_work.id where grouped_work_records.groupedWorkId IS NULL and userAdded = 0);'
			]
		], //delete_orphaned_series_members
		'correct_default_include_only_holdable_for_records_to_include' => [
			'title' => 'Correct Default Include Only Holdable for Records To Include',
			'description' => 'Correct Default Include Only Holdable for Records To Include',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library_records_to_include CHANGE COLUMN includeHoldableOnly includeHoldableOnly tinyint(1) NOT NULL DEFAULT 0',
				'ALTER TABLE location_records_to_include CHANGE COLUMN includeHoldableOnly includeHoldableOnly tinyint(1) NOT NULL DEFAULT 0'
			]
		], //correct_default_include_only_holdable_for_records_to_include
		'cloud_library_setting_name' => [
			'title' => 'Add CloudLibrary setting name',
			'description' => 'Add a name for CloudLibrary settings',
			'sql' => [
				"ALTER TABLE cloud_library_settings ADD COLUMN name VARCHAR(100) DEFAULT ''",
				"UPDATE cloud_library_settings set name = concat('Setting ', id)",
			]
		], //cloud_library_setting_name
		'indexing_profile_status_alt' => [
			'title' => 'Indexing Profile Status Alt',
			'description' => 'Add the ability to define a second item status field for use while indexing symphony records',
			'sql' => [
				"ALTER TABLE indexing_profiles ADD COLUMN statusAlt CHAR(1) DEFAULT ' '",
				"ALTER TABLE status_map_values ADD COLUMN appliesToStatusSubfield TINYINT(1) DEFAULT 1",
				"ALTER TABLE status_map_values ADD COLUMN appliesToStatusAltSubfield TINYINT(1) DEFAULT 0",
			]
		], //indexing_profile_status_alt

		//katherine - Grove

		//kirstien - Grove

		//kodi - Grove
		'side_loads_library_permissions' => [
			'title' => 'Side Load Home Library Permissions',
			'description' => 'Add permissions for administering side loads and side load scopes based on home library.',
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES
					('Cataloging & eContent', 'Administer Side Loads for Home Library', 'Side Loads', 171, 'Allows the user to administer side loads for their home library only.'),
					('Cataloging & eContent', 'Administer Side Load Scopes for Home Library', 'Side Loads', 172, 'Allows the user to administer side load scopes for their home library only.')",
			],
		], //side_loads_library_permissions
		'side_loads_owning_and_sharing' => [
			'title' => 'Side Load Owning and Sharing Library',
			'description' => 'Add owning and sharing library to side loads table.',
			'sql' => [
				"ALTER TABLE sideloads ADD COLUMN owningLibrary INT(11) NOT NULL DEFAULT -1",
				"ALTER TABLE sideloads ADD COLUMN sharing INT(11) NOT NULL DEFAULT 1",
			],
		], //side_loads_owning_and_sharing

		//Mark - Grove
		'side_loads_uniqueness' => [
			'title' => 'Side Load Uniqueness',
			'description' => 'Update Uniqueness for Side Loads to be unique based on name and owning library and also add uniqueness for marc path and record url component.',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE sideloads DROP INDEX name",
				"ALTER TABLE sideloads ADD UNIQUE name(name, owningLibrary)",
				"ALTER TABLE sideloads ADD UNIQUE (marcPath)",
				"ALTER TABLE sideloads ADD UNIQUE (recordUrlComponent)",
			],
		], //side_loads_uniqueness

		//Yanjun Li - ByWater

		// Leo Stoyanov - BWS
		'add_num_regrouped_to_cloudlibrary_extract_logs' => [
			'title' => 'Add numRegrouped Column to CloudLibrary Extract Logs',
			'description' => 'Adds a numRegrouped column to the cloud_library_export_log table to track the number of works regrouped during an extract.',
			'sql' => [
				"ALTER TABLE cloud_library_export_log ADD COLUMN IF NOT EXISTS numRegrouped int(11) DEFAULT 0 AFTER settingId",
			]
		], //add_num_regrouped_to_cloudlibrary_extract_logs
		'increase_size_of_collection_codes_to_exclude' => [
			'title' => 'Increase the Size of the collectionCodesToExclude Column',
			'description' => 'Increases the size of the collectionCodesToExclude column from VARCHAR(100) to VARCHAR(500).',
			'sql' => [
				"ALTER TABLE `library_records_to_include` MODIFY COLUMN `collectionCodesToExclude` varchar(500) NOT NULL DEFAULT ''",
				"ALTER TABLE `location_records_to_include` MODIFY COLUMN `collectionCodesToExclude` varchar(500) NOT NULL DEFAULT ''"
			]
		], //increase_size_of_collection_codes_to_exclude
		'add_static_location_id_to_portal_cell' => [
			'title' => 'Add staticLocationId Column to Web Builder Portal Cells',
			'description' => 'Adds a staticLocationId column to the web_builder_portal_cell table to represent the location ID of the static location chosen.',
			'sql' => [
				"ALTER TABLE web_builder_portal_cell ADD COLUMN IF NOT EXISTS staticLocationId int(11) NOT NULL DEFAULT -1",
			]
		], //add_static_location_id_to_portal_cell

		// Laura Escamilla - ByWater Solutions

		//alexander - Open Fifth

		//chloe - Open Fifth

		//James Staub - Nashville Public Library

		//Lucas Montoya - Theke Solutions

		//other

	];
}
