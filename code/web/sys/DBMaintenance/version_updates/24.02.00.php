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