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

		// Laura Escamilla - ByWater Solutions

		//alexander - Open Fifth

		//chloe - Open Fifth

		//James Staub - Nashville Public Library

		//Lucas Montoya - Theke Solutions

		//other

	];
}
