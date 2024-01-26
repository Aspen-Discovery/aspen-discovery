<?php

function getUpdates24_02_00(): array {
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
		'track_palace_project_user_usage' => [
			'title' => 'Palace Project Usage by user',
			'description' => 'Add a table to track how often a particular user uses Palace Project.',
			'sql' => [
				"CREATE TABLE user_palace_project_usage (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					instance VARCHAR(100),
					userId INT(11) NOT NULL,
					year INT(4) NOT NULL,
					month INT(2) NOT NULL,
					usageCount INT(11) DEFAULT 0
				) ENGINE = InnoDB",
				"ALTER TABLE user_palace_project_usage ADD INDEX (instance, userId, year, month)",
				"ALTER TABLE user_palace_project_usage ADD INDEX (instance, year, month)",
			],
		], //track_palace_project_user_usage

		'track_palace_project_record_usage' => [
			'title' => 'Palace Project Record Usage',
			'description' => 'Add a table to track how records within Palace Project are used.',
			'continueOnError' => true,
			'sql' => [
				"CREATE TABLE palace_project_record_usage (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					instance VARCHAR(100),
					palaceProjectId INT(11),
					year INT(4) NOT NULL,
					month INT(2) NOT NULL,
					timesHeld INT(11) NOT NULL DEFAULT 0,
					timesCheckedOut INT(11) NOT NULL DEFAULT 0
				) ENGINE = InnoDB",
				"ALTER TABLE palace_project_record_usage ADD INDEX (instance, axis360Id, year, month)",
				"ALTER TABLE palace_project_record_usage ADD INDEX (instance, year, month)",
			],
		], //track_palace_project_record_usage

		'palace_project_return_url' => [
			'title' => 'Palace Project Return URL',
			'description' => 'Store the return URL with Checkouts for Palace Project',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE user_checkout ADD COLUMN earlyReturnUrl VARCHAR(255)",
			],
		], //palace_project_return_url

		'palace_project_collection_name' => [
			'title' => 'Palace Project Collection Name',
			'description' => 'Add collection name for titles in Palace Project',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE palace_project_title ADD COLUMN collectionName VARCHAR(255)",
			],
		], //palace_project_collection_name

		'palace_project_cancellation_url' => [
			'title' => 'Palace Project Cancellation URL',
			'description' => 'Store the cancellation URL with Holds for Palace Project',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE user_hold ADD COLUMN cancellationUrl VARCHAR(255)",
			],
		], //palace_project_cancellation_url

		'translatable_text_blocks' => [
			'title' => 'Translatable Text Blocks',
			'description' => 'Add the ability to translate large blocks of text within Aspen',
			'continueOnError' => true,
			'sql' => [
				"CREATE TABLE text_block_translation (
					id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
					objectType VARCHAR(50) NOT NULL,
					objectId INT(11) NOT NULL, 
					languageId INT(11) NOT NULL,
					translation MEDIUMTEXT,
					UNIQUE (objectType, objectId, languageId)
				) ENGINE INNODB"
			],
		], //translatable_text_blocks


		//kirstien - ByWater

		//kodi - ByWater
		'self_reg_sections' => [
			'title' => 'Symphony Self Registration Sections',
			'description' => 'Adds definable sections to the self registration form for a more organized look.',
			'sql' => [
				"ALTER TABLE self_reg_form_values ADD COLUMN section ENUM ('librarySection', 'identitySection', 'mainAddressSection', 'contactInformationSection') NOT NULL DEFAULT 'identitySection'",
			],
		], //self_reg_sections
		'self_reg_sections_assignment' => [
			'title' => 'Symphony Self Registration Sections Assignments',
			'description' => 'Assigns sections for Symphony self registration form values.',
			'sql' => [
				"UPDATE self_reg_form_values SET section = 'librarySection' WHERE symphonyName = 'library'",
				"UPDATE self_reg_form_values SET section = 'identitySection' WHERE symphonyName in (
                	'firstName',
                    'middleName',
                	'lastName',
                    'preferredName',
                    'usePreferredName',
                    'suffix',
                    'title',
                    'dob',
            		'birthdate',
                    'care_of',
                    'careof',
                    'guardian',
                	'parentname')",
				"UPDATE self_reg_form_values SET section = 'mainAddressSection' WHERE symphonyName in (
                	'po_box',
                    'street',
                	'mailingaddr',
                    'primaryAddress',
                    'apt_suite',
                    'city',
                    'state',
                    'zip')",
				"UPDATE self_reg_form_values SET section = 'contactInformationSection' WHERE symphonyName in (
                	'email',
                    'phone',
                	'dayphone',
                    'cellPhone',
                    'workphone',
                    'homephone',
                    'ext',
                    'fax',
            		'primaryPhone')",
			],
		], //self_reg_sections_assignment

		'cloud_library_availability_changes' => [
			'title' => 'Cloud Library Availability - On Order',
			'description' => 'Adds additional API call data to determine if item is "Coming Soon" which will give it the status "On Order" instead of "Available"',
			'sql' => [
				'ALTER TABLE cloud_library_availability ADD COLUMN availabilityType SMALLINT NOT NULL DEFAULT 1',
				'ALTER TABLE cloud_library_availability ADD COLUMN typeRawChecksum BIGINT',
				'ALTER TABLE cloud_library_availability ADD COLUMN typeRawResponse MEDIUMTEXT',
			],
		], //cloud_library_availability_changes

		//lucas - Theke

		//alexander - PTFS Europe

		//jacob - PTFS Europe

        // James Staub
        'permission_hide_series' => [
            'title' => 'Change permission for Hide Subject Facets to umbrella Hide Metadata',
            'description' => 'Add permission for Hide Series from Series Facet and Grouped Work Series Display Information',
            'continueOnError' => false,
            'sql' => [
                "UPDATE permissions 
                    SET name = 'Hide Metadata', 
                        description = 'Controls if the user can hide metadata like Subjects and Series from facets and display information.' 
                    WHERE name = 'Hide Subject Facets'",
            ]
        ], //permission_hide_series

        'hide_series' => [
            'title' => 'Add Series to Hide',
            'description' => 'Add Series to Hide from Series Facet and Grouped Work Series Display Information',
			'continueOnError' => true,
            'sql' => [
                'CREATE TABLE IF NOT EXISTS hide_series (
                            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            seriesTerm VARCHAR(512) NOT NULL UNIQUE,
                            seriesNormalized VARCHAR(512) NOT NULL UNIQUE
                        ) ENGINE = InnoDB',
            ],
        ], // hide_series

        'hide_subjects_drop_date_added' => [
            'title' => 'Drop date added column from hide subject facets table',
            'description' => 'Drop date added column from hide subject facets table',
            'sql' => [
                'ALTER TABLE hide_subject_facets DROP COLUMN dateAdded',
            ],
        ], // hide_subjects_drop_date_added

	];
}