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
		'replace_arial_fonts' => [
			 'title' => 'Replace Arial Fonts',
			 'description' => 'Replace Arial Fonts',
			 'continueOnError' => false,
			 'sql' => [
				 "UPDATE Themes set bodyFont = 'Arion' where bodyFont = 'Arial'",
				 "UPDATE Themes set headingFont = 'Arion' where headingFont = 'Arial'",
			 ]
	 	], //replace_arial_fonts
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
