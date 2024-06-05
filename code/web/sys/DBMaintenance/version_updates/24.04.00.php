<?php

function getUpdates24_04_00(): array {
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
		'replace_arial_fonts_2' => [
			 'title' => 'Replace Arial Fonts',
			 'description' => 'Replace Arial Fonts',
			 'continueOnError' => false,
			 'sql' => [
				 "UPDATE themes set bodyFont = 'Arion' where bodyFont = 'Arial'",
				 "UPDATE themes set headingFont = 'Arion' where headingFont = 'Arial'",
			 ]
	 	], //replace_arial_fonts_2
		'palace_project_collection' => [
			'title' => 'Palace Project Collections',
			'description' => 'Add Information about Palace Project collections',
			'continueOnError' => false,
			'sql' => [
				'DROP TABLE IF EXISTS palace_project_collections',
				'CREATE TABLE palace_project_collections (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					settingId INT(11) NOT NULL,
					palaceProjectName VARCHAR(255) NOT NULL, 
					displayName VARCHAR(255) NOT NULL,
					hasCirculation TINYINT(1),
					includeInAspen TINYINT(1) DEFAULT 1,
					lastIndexed INT(11),
					UNIQUE (settingId, palaceProjectName)
				) ENGINE = InnoDB'
			]
		], //palace_project_collection
		'palace_project_restrict_scopes_by_audience' => [
			'title' => 'Palace Project Restrict Scope By Audience',
			'description' => 'Add Audience Restrictions to Palace Project Scopes',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE palace_project_scopes ADD COLUMN includeAdult TINYINT DEFAULT 1',
				'ALTER TABLE palace_project_scopes ADD COLUMN includeTeen TINYINT DEFAULT 1',
				'ALTER TABLE palace_project_scopes ADD COLUMN includeChildren TINYINT DEFAULT 1',
			]
		], //palace_project_restrict_scopes_by_audience
		'axis360_restrict_scopes_by_audience' => [
			'title' => 'Axis 360 Restrict Scope By Audience',
			'description' => 'Add Audience Restrictions to Axis 360 Scopes',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE axis360_scopes ADD COLUMN includeAdult TINYINT DEFAULT 1',
				'ALTER TABLE axis360_scopes ADD COLUMN includeTeen TINYINT DEFAULT 1',
				'ALTER TABLE axis360_scopes ADD COLUMN includeChildren TINYINT DEFAULT 1',
			]
		], //axis360_restrict_scopes_by_audience
		'palace_project_title_availability' => [
			'title' => 'Palace Project Title Availability',
			'description' => 'Add availability per collection for Palace Project titles',
			'continueOnError' => false,
			'sql' => [
				'CREATE TABLE palace_project_title_availability (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					titleId INT(11) NOT NULL,
					collectionId INT(11) NOT NULL, 
					lastSeen INT(11) NOT NULL, 
					deleted TINYINT(1),
					UNIQUE (titleId, collectionId)
				) ENGINE = InnoDB',
			]
		], //palace_project_title_availability
		'remove_collection_from_palace_project' => [
			'title' => 'Remove collection from palace project title',
			'description' => 'Remove collection from palace project title',
			'continueOnError' => false,
			'sql' => [
				'removeCollectionFromPalaceProjectTitle',
				'ALTER TABLE palace_project_title DROP COLUMN collectionName',
			]
		], //remove_collection_from_palace_project
		'cloud_library_restrict_scopes_by_audience' => [
			'title' => 'cloudLibrary Restrict Scope By Audience',
			'description' => 'Add Audience Restrictions to cloudLibrary Scopes',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE cloud_library_scopes ADD COLUMN includeAdult TINYINT DEFAULT 1',
				'ALTER TABLE cloud_library_scopes ADD COLUMN includeTeen TINYINT DEFAULT 1',
				'ALTER TABLE cloud_library_scopes ADD COLUMN includeKids TINYINT DEFAULT 1',
				'UPDATE cloud_library_scopes SET includeAdult =0 where restrictToChildrensMaterial = 1',
				'UPDATE cloud_library_scopes SET includeTeen =0 where restrictToChildrensMaterial = 1',
				'ALTER TABLE cloud_library_scopes DROP COLUMN restrictToChildrensMaterial',
			]
		], //cloud_library_restrict_scopes_by_audience
		'hoopla_restrict_scopes_by_audience' => [
			'title' => 'Hoopla Restrict Scope By Audience',
			'description' => 'Add Audience Restrictions to Hoopla Scopes',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE hoopla_scopes ADD COLUMN includeAdult TINYINT DEFAULT 1',
				'ALTER TABLE hoopla_scopes ADD COLUMN includeTeen TINYINT DEFAULT 1',
				'ALTER TABLE hoopla_scopes ADD COLUMN includeKids TINYINT DEFAULT 1',
				'UPDATE hoopla_scopes SET includeAdult =0 where restrictToChildrensMaterial = 1',
				'UPDATE hoopla_scopes SET includeTeen =0 where restrictToChildrensMaterial = 1',
				'ALTER TABLE hoopla_scopes DROP COLUMN restrictToChildrensMaterial',
			]
		], //hoopla_restrict_scopes_by_audience
		'sideload_restrict_scopes_by_audience' => [
			'title' => 'Side Load Restrict Scope By Audience',
			'description' => 'Add Audience Restrictions to Side Load Scopes',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE sideload_scopes ADD COLUMN includeAdult TINYINT DEFAULT 1',
				'ALTER TABLE sideload_scopes ADD COLUMN includeTeen TINYINT DEFAULT 1',
				'ALTER TABLE sideload_scopes ADD COLUMN includeKids TINYINT DEFAULT 1',
				'UPDATE sideload_scopes SET includeAdult =0 where restrictToChildrensMaterial = 1',
				'UPDATE sideload_scopes SET includeTeen =0 where restrictToChildrensMaterial = 1',
				'ALTER TABLE sideload_scopes DROP COLUMN restrictToChildrensMaterial',
			]
		], //sideload_restrict_scopes_by_audience
		'update_user_list_module_log_settings' => [
			'title' => 'Update User List Module Log Settings',
			'description' => 'Update User List Module Log Settings',
			'continueOnError' => false,
			'sql' => [
				"UPDATE modules set logClassPath = '/sys/UserLists/ListIndexingLogEntry.php', logClassName='ListIndexingLogEntry', settingsClassPath = '/sys/UserLists/ListIndexingSettings.php', settingsClassName = 'ListIndexingSettings' where name = 'User Lists'",
			]
		], //update_user_list_module_log_settings


		//kirstien - ByWater
		'self_check_checkout_location' => [
			'title' => 'Add self-check option to set checkout location',
			'description' => 'Add self-check option to set checkout location',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE aspen_lida_self_check_settings ADD COLUMN checkoutLocation TINYINT(1) DEFAULT 0',
			],
		],
		//self_check_checkout_location

		//kodi - ByWater
		'institution_code' => [
			'title' => 'Institution Code',
			'description' => 'Add institution code for CarlX self registration to library table',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE library ADD COLUMN institutionCode varchar(100) default ''",
			],
		],//institution_code
		'include_children_kids' => [
			'title' => 'Rename includeChildren to includeKids for indexing',
			'description' => 'Rename includeChildren to includeKids for indexing',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE axis360_scopes DROP COLUMN includeChildren',
				'ALTER TABLE axis360_scopes ADD COLUMN includeKids TINYINT DEFAULT 1',
				'ALTER TABLE palace_project_scopes DROP COLUMN includeChildren',
				'ALTER TABLE palace_project_scopes ADD COLUMN includeKids TINYINT DEFAULT 1',
			]
		], //include_children_kids

		//lucas - Theke

		//alexander - PTFS Europe

		//jacob - PTFS Europe

		// James Staub


	];
}

function removeCollectionFromPalaceProjectTitle(&$update) {
	require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
	require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
	require_once ROOT_DIR . '/sys/PalaceProject/PalaceProjectTitle.php';
	//remove duplicate titles
	global $aspen_db;
	$query = 'select count(collectionName) as numCollections, GROUP_CONCAT(id) as relatedIds, palaceProjectId from palace_project_title group by palaceProjectId having numCollections > 1;';
	$results = $aspen_db->query($query, PDO::FETCH_ASSOC);
	$row = $results->fetch();
	while ($row != null) {
		//$palaceProjectId = $row['palaceProjectId'];
		$relatedIdsRaw = $row['relatedIds'];
		$relatedIds = explode(',', $relatedIdsRaw);
		//we will preserve the first id
		//$firstId = reset($relatedIds);
		for ($i = 1; $i < count($relatedIds); $i++) {
			$idToRemove = $relatedIds[$i];
			//Get the grouped work for the identifier
			$groupedWorkPrimaryIdentifier = new GroupedWorkPrimaryIdentifier();
			$groupedWorkPrimaryIdentifier->type = 'palace_project';
			$groupedWorkPrimaryIdentifier->identifier = $idToRemove;
			if ($groupedWorkPrimaryIdentifier->find(true)){
				//Get the grouped work id
				$groupedWork = new GroupedWork();
				$groupedWork->id = $groupedWorkPrimaryIdentifier->grouped_work_id;
				if ($groupedWork->find(true)) {
					$groupedWork->forceReindex();
				}
			}
			//Now delete the id from the database
			$palaceTitle = new PalaceProjectTitle();
			$palaceTitle->id = $idToRemove;
			$palaceTitle->delete();
		}

		$row = $results->fetch();
	}
	$results->closeCursor();

	$update['status'] = "Finished removing collection from palace project titles";
	$update['success'] = true;
}